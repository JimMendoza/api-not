<?php

namespace Tests\Feature\Integracion;

use App\Models\Empresa;
use App\Models\Tramite;
use App\Models\TramiteSeguimiento;
use App\Models\UsuarioApp;
use App\Models\UsuarioAppDispositivo;
use App\Models\UsuarioAppNotificacionConfiguracion;
use App\Support\App\Push\PushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificacionesEventoIntegracionFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $integrationToken = 'integration-test-token';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.integration.token', $this->integrationToken);
    }

    public function test_evento_valido_crea_inbox_y_envia_push()
    {
        $contexto = $this->crearContexto();

        UsuarioAppDispositivo::query()->create([
            'usuario_app_id' => $contexto['usuario']->id,
            'device_id' => 'device-int-001',
            'push_token' => 'token-int-001',
            'platform' => 'android',
            'activo' => true,
            'ultimo_registro_at' => now(),
        ]);

        $fakeSender = new class implements PushSender {
            public $lastMessage;

            public function provider()
            {
                return 'fcm';
            }

            public function isConfigured()
            {
                return true;
            }

            public function send(\App\Models\UsuarioAppDispositivo $dispositivo, array $message)
            {
                $this->lastMessage = $message;

                return [
                    'success' => true,
                    'invalidToken' => false,
                    'providerMessageId' => 'projects/demo/messages/evt-1',
                    'errorCode' => null,
                    'errorMessage' => null,
                ];
            }
        };

        $this->app->instance(PushSender::class, $fakeSender);

        $this->withHeaders($this->integrationHeaders())
            ->postJson('/api/integracion/notificaciones/evento', [
                'tramiteId' => $contexto['tramite']->id,
                'evento' => 'tramite_derivado',
                'payload' => [
                    'destino' => 'Gerencia General',
                ],
            ])
            ->assertOk()
            ->assertJson([
                'mensaje' => 'Evento de notificación procesado correctamente.',
                'notificacion' => [
                    'tramiteId' => $contexto['tramite']->id,
                    'tipo' => 'tramite_derivado',
                    'leida' => false,
                ],
                'push' => [
                    'provider' => 'fcm',
                    'configured' => true,
                    'attemptedDevices' => 1,
                    'sentDevices' => 1,
                    'invalidatedDevices' => 0,
                    'reason' => null,
                ],
            ]);

        $this->assertDatabaseHas('notificaciones', [
            'tramite_id' => $contexto['tramite']->id,
            'usuario_app_id' => $contexto['usuario']->id,
            'tipo' => 'tramite_derivado',
            'leida' => 0,
        ]);

        $this->assertSame((string) $contexto['tramite']->id, data_get($fakeSender->lastMessage, 'data.tramiteId'));
        $this->assertSame('tramite_derivado', data_get($fakeSender->lastMessage, 'data.tipo'));
        $this->assertSame('notificaciones', data_get($fakeSender->lastMessage, 'data.targetScreen'));
        $this->assertSame('1', data_get($fakeSender->lastMessage, 'data.noLeidas'));
        $this->assertSame(1, data_get($fakeSender->lastMessage, 'android.notification.notification_count'));
    }

    public function test_evento_invalido_retorna_422()
    {
        $contexto = $this->crearContexto();

        $this->withHeaders($this->integrationHeaders())
            ->postJson('/api/integracion/notificaciones/evento', [
                'tramiteId' => $contexto['tramite']->id,
                'evento' => 'evento_inexistente',
            ])
            ->assertStatus(422)
            ->assertJson([
                'mensaje' => 'Datos inválidos.',
                'errores' => [
                    'evento' => [],
                ],
            ]);
    }

    public function test_tramite_invalido_retorna_404()
    {
        $this->crearContexto();

        $this->withHeaders($this->integrationHeaders())
            ->postJson('/api/integracion/notificaciones/evento', [
                'tramiteId' => 999999,
                'evento' => 'tramite_registrado',
            ])
            ->assertStatus(404)
            ->assertExactJson([
                'mensaje' => 'Trámite no encontrado.',
            ]);
    }

    public function test_push_fallido_no_pierde_inbox()
    {
        $contexto = $this->crearContexto();

        UsuarioAppDispositivo::query()->create([
            'usuario_app_id' => $contexto['usuario']->id,
            'device_id' => 'device-int-err',
            'push_token' => 'token-int-err',
            'platform' => 'android',
            'activo' => true,
            'ultimo_registro_at' => now(),
        ]);

        $fakeSender = new class implements PushSender {
            public function provider()
            {
                return 'fcm';
            }

            public function isConfigured()
            {
                return true;
            }

            public function send(\App\Models\UsuarioAppDispositivo $dispositivo, array $message)
            {
                return [
                    'success' => false,
                    'invalidToken' => false,
                    'providerMessageId' => null,
                    'errorCode' => 'INTERNAL',
                    'errorMessage' => 'Error provider',
                ];
            }
        };

        $this->app->instance(PushSender::class, $fakeSender);

        $this->withHeaders($this->integrationHeaders())
            ->postJson('/api/integracion/notificaciones/evento', [
                'tramiteId' => $contexto['tramite']->id,
                'evento' => 'movimiento_hoja_ruta',
            ])
            ->assertOk()
            ->assertJson([
                'mensaje' => 'Evento de notificación procesado correctamente.',
                'notificacion' => [
                    'tramiteId' => $contexto['tramite']->id,
                    'tipo' => 'movimiento_hoja_ruta',
                ],
                'push' => [
                    'provider' => 'fcm',
                    'configured' => true,
                    'attemptedDevices' => 1,
                    'sentDevices' => 0,
                    'reason' => 'send_failed',
                ],
            ]);

        $this->assertDatabaseHas('notificaciones', [
            'tramite_id' => $contexto['tramite']->id,
            'usuario_app_id' => $contexto['usuario']->id,
            'tipo' => 'movimiento_hoja_ruta',
            'leida' => 0,
        ]);
    }

    public function test_push_se_silencia_dentro_de_horario_configurado_y_conserva_inbox()
    {
        $contexto = $this->crearContexto();

        UsuarioAppNotificacionConfiguracion::query()->create([
            'usuario_app_id' => $contexto['usuario']->id,
            'silenciar_fuera_de_horario' => true,
            'hora_silencio_inicio' => '22:00',
            'hora_silencio_fin' => '07:00',
            'zona_horaria' => 'America/Lima',
            'mostrar_contador_no_leidas' => true,
        ]);

        UsuarioAppDispositivo::query()->create([
            'usuario_app_id' => $contexto['usuario']->id,
            'device_id' => 'device-int-silent',
            'push_token' => 'token-int-silent',
            'platform' => 'android',
            'activo' => true,
            'ultimo_registro_at' => now(),
        ]);

        $fakeSender = new class implements PushSender {
            public $sendCalls = 0;

            public function provider()
            {
                return 'fcm';
            }

            public function isConfigured()
            {
                return true;
            }

            public function send(\App\Models\UsuarioAppDispositivo $dispositivo, array $message)
            {
                $this->sendCalls++;

                return [
                    'success' => true,
                    'invalidToken' => false,
                    'providerMessageId' => 'projects/demo/messages/silent',
                    'errorCode' => null,
                    'errorMessage' => null,
                ];
            }
        };

        $this->app->instance(PushSender::class, $fakeSender);

        Carbon::setTestNow(Carbon::parse('2026-03-23 23:30:00', 'America/Lima')->setTimezone('UTC'));

        try {
            $this->withHeaders($this->integrationHeaders())
                ->postJson('/api/integracion/notificaciones/evento', [
                    'tramiteId' => $contexto['tramite']->id,
                    'evento' => 'tramite_registrado',
                ])
                ->assertOk()
                ->assertJson([
                    'push' => [
                        'provider' => 'fcm',
                        'configured' => true,
                        'attemptedDevices' => 0,
                        'sentDevices' => 0,
                        'invalidatedDevices' => 0,
                        'reason' => 'silenciar_fuera_de_horario',
                    ],
                ]);
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame(0, $fakeSender->sendCalls);

        $this->assertDatabaseHas('notificaciones', [
            'tramite_id' => $contexto['tramite']->id,
            'usuario_app_id' => $contexto['usuario']->id,
            'tipo' => 'tramite_registrado',
            'leida' => 0,
        ]);
    }

    public function test_push_se_envia_fuera_de_horario_silencioso()
    {
        $contexto = $this->crearContexto();

        UsuarioAppNotificacionConfiguracion::query()->create([
            'usuario_app_id' => $contexto['usuario']->id,
            'silenciar_fuera_de_horario' => true,
            'hora_silencio_inicio' => '22:00',
            'hora_silencio_fin' => '07:00',
            'zona_horaria' => 'America/Lima',
            'mostrar_contador_no_leidas' => true,
        ]);

        UsuarioAppDispositivo::query()->create([
            'usuario_app_id' => $contexto['usuario']->id,
            'device_id' => 'device-int-day',
            'push_token' => 'token-int-day',
            'platform' => 'android',
            'activo' => true,
            'ultimo_registro_at' => now(),
        ]);

        $fakeSender = new class implements PushSender {
            public $sendCalls = 0;

            public function provider()
            {
                return 'fcm';
            }

            public function isConfigured()
            {
                return true;
            }

            public function send(\App\Models\UsuarioAppDispositivo $dispositivo, array $message)
            {
                $this->sendCalls++;

                return [
                    'success' => true,
                    'invalidToken' => false,
                    'providerMessageId' => 'projects/demo/messages/day',
                    'errorCode' => null,
                    'errorMessage' => null,
                ];
            }
        };

        $this->app->instance(PushSender::class, $fakeSender);

        Carbon::setTestNow(Carbon::parse('2026-03-23 10:30:00', 'America/Lima')->setTimezone('UTC'));

        try {
            $this->withHeaders($this->integrationHeaders())
                ->postJson('/api/integracion/notificaciones/evento', [
                    'tramiteId' => $contexto['tramite']->id,
                    'evento' => 'tramite_registrado',
                ])
                ->assertOk()
                ->assertJson([
                    'push' => [
                        'provider' => 'fcm',
                        'configured' => true,
                        'attemptedDevices' => 1,
                        'sentDevices' => 1,
                        'invalidatedDevices' => 0,
                        'reason' => null,
                    ],
                ]);
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame(1, $fakeSender->sendCalls);
    }

    public function test_quiet_hours_ignora_zona_persistida_y_evalua_siempre_en_lima()
    {
        $contexto = $this->crearContexto();

        DB::table('usuario_app_notificacion_configuraciones')->insert([
            'usuario_app_id' => $contexto['usuario']->id,
            'silenciar_fuera_de_horario' => true,
            'hora_silencio_inicio' => '22:00',
            'hora_silencio_fin' => '07:00',
            'zona_horaria' => 'UTC',
            'mostrar_contador_no_leidas' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        UsuarioAppDispositivo::query()->create([
            'usuario_app_id' => $contexto['usuario']->id,
            'device_id' => 'device-int-fixed-lima',
            'push_token' => 'token-int-fixed-lima',
            'platform' => 'android',
            'activo' => true,
            'ultimo_registro_at' => now(),
        ]);

        $fakeSender = new class implements PushSender {
            public $sendCalls = 0;

            public function provider()
            {
                return 'fcm';
            }

            public function isConfigured()
            {
                return true;
            }

            public function send(\App\Models\UsuarioAppDispositivo $dispositivo, array $message)
            {
                $this->sendCalls++;

                return [
                    'success' => true,
                    'invalidToken' => false,
                    'providerMessageId' => 'projects/demo/messages/fixed-lima',
                    'errorCode' => null,
                    'errorMessage' => null,
                ];
            }
        };

        $this->app->instance(PushSender::class, $fakeSender);

        Carbon::setTestNow(Carbon::parse('2026-03-23 20:30:00', 'America/Lima')->setTimezone('UTC'));

        try {
            $this->withHeaders($this->integrationHeaders())
                ->postJson('/api/integracion/notificaciones/evento', [
                    'tramiteId' => $contexto['tramite']->id,
                    'evento' => 'tramite_registrado',
                ])
                ->assertOk()
                ->assertJson([
                    'push' => [
                        'provider' => 'fcm',
                        'configured' => true,
                        'attemptedDevices' => 1,
                        'sentDevices' => 1,
                        'invalidatedDevices' => 0,
                        'reason' => null,
                    ],
                ]);
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame(1, $fakeSender->sendCalls);
    }

    protected function integrationHeaders()
    {
        return [
            'X-Integracion-Token' => $this->integrationToken,
        ];
    }

    protected function crearContexto()
    {
        $empresa = Empresa::query()->create([
            'codigo' => 'INT-EMP',
            'nombre' => 'Empresa Integracion',
            'imagen' => 'logo.png',
            'activo' => true,
        ]);

        $usuario = UsuarioApp::query()->create([
            'empresa_id' => $empresa->id,
            'username' => 'integracion.user',
            'full_name' => 'Usuario Integracion',
            'password' => Hash::make('Secret123!'),
            'activo' => true,
        ]);

        $tramite = Tramite::query()->create([
            'empresa_id' => $empresa->id,
            'usuario_app_id' => $usuario->id,
            'codigo' => 'TRM-INT-001',
            'titulo' => 'Trámite Integración',
            'descripcion' => 'Trámite para pruebas de integración',
            'fecha_registro' => '2026-03-20',
            'estado_actual' => 'Registrado',
            'activo' => true,
        ]);

        TramiteSeguimiento::query()->create([
            'tramite_id' => $tramite->id,
            'usuario_app_id' => $usuario->id,
            'activo' => true,
        ]);

        return [
            'empresa' => $empresa,
            'usuario' => $usuario,
            'tramite' => $tramite,
        ];
    }
}
