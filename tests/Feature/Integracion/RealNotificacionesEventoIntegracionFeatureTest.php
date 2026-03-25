<?php

namespace Tests\Feature\Integracion;

use App\Models\AppMobile\UsuarioDispositivo;
use App\Models\AppMobile\UsuarioNotificacionConfiguracion;
use App\Services\Push\PushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\App\Concerns\SeedsRealIdentityContext;
use Tests\TestCase;

class RealNotificacionesEventoIntegracionFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsRealIdentityContext;

    protected $integrationToken = 'integration-real-token';

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableRealIdentityMode();
        config()->set('services.integration.token', $this->integrationToken);
    }

    public function test_evento_valido_crea_inbox_real_y_envia_push_real()
    {
        $contexto = $this->crearContextoReal();

        DB::table('app_mobile_usuario_dispositivos')->insert([
            'usuario_id' => 101,
            'device_id' => 'device-int-real-001',
            'push_token' => 'token-int-real-001',
            'platform' => 'android',
            'activo' => true,
            'ultimo_registro_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
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

            public function send(UsuarioDispositivo $dispositivo, array $message)
            {
                $this->lastMessage = $message;

                return [
                    'success' => true,
                    'invalidToken' => false,
                    'providerMessageId' => 'projects/demo/messages/real-1',
                    'errorCode' => null,
                    'errorMessage' => null,
                ];
            }
        };

        $this->app->instance(PushSender::class, $fakeSender);

        $this->withHeaders($this->integrationHeaders())
            ->postJson('/api/integracion/notificaciones/evento', [
                'tramiteId' => $contexto['tramiteId'],
                'evento' => 'tramite_derivado',
                'payload' => [
                    'destino' => 'Gerencia General',
                ],
            ])
            ->assertOk()
            ->assertJson([
                'mensaje' => 'Evento de notificación procesado correctamente.',
                'notificacion' => [
                    'tramiteId' => $contexto['tramiteId'],
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

        $this->assertDatabaseHas('app_mobile_notificaciones', [
            'usuario_id' => 101,
            'tramite_id' => $contexto['tramiteId'],
            'tipo' => 'tramite_derivado',
            'leida' => 0,
        ]);

        $this->assertSame((string) $contexto['tramiteId'], data_get($fakeSender->lastMessage, 'data.tramiteId'));
        $this->assertSame('tramite_derivado', data_get($fakeSender->lastMessage, 'data.tipo'));
        $this->assertSame('notificaciones', data_get($fakeSender->lastMessage, 'data.targetScreen'));
        $this->assertSame('1', data_get($fakeSender->lastMessage, 'data.noLeidas'));
        $this->assertSame(1, data_get($fakeSender->lastMessage, 'android.notification.notification_count'));
    }

    public function test_evento_invalido_retorna_422_runtime_real()
    {
        $contexto = $this->crearContextoReal();

        $this->withHeaders($this->integrationHeaders())
            ->postJson('/api/integracion/notificaciones/evento', [
                'tramiteId' => $contexto['tramiteId'],
                'evento' => 'evento_inexistente',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'evento',
            ], 'errores');
    }

    public function test_tramite_invalido_retorna_404_runtime_real()
    {
        $this->seedRealIdentityContext();

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

    public function test_push_fallido_no_pierde_inbox_runtime_real()
    {
        $contexto = $this->crearContextoReal();

        DB::table('app_mobile_usuario_dispositivos')->insert([
            'usuario_id' => 101,
            'device_id' => 'device-int-real-fail',
            'push_token' => 'token-int-real-fail',
            'platform' => 'android',
            'activo' => true,
            'ultimo_registro_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
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

            public function send(UsuarioDispositivo $dispositivo, array $message)
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
                'tramiteId' => $contexto['tramiteId'],
                'evento' => 'movimiento_hoja_ruta',
            ])
            ->assertOk()
            ->assertJson([
                'push' => [
                    'provider' => 'fcm',
                    'configured' => true,
                    'attemptedDevices' => 1,
                    'sentDevices' => 0,
                    'reason' => 'send_failed',
                ],
            ]);

        $this->assertDatabaseHas('app_mobile_notificaciones', [
            'usuario_id' => 101,
            'tramite_id' => $contexto['tramiteId'],
            'tipo' => 'movimiento_hoja_ruta',
            'leida' => 0,
        ]);
    }

    public function test_push_se_silencia_en_quiet_hours_runtime_real()
    {
        $contexto = $this->crearContextoReal();

        DB::table('app_mobile_usuario_notificacion_configuraciones')->insert([
            'usuario_id' => 101,
            'silenciar_fuera_de_horario' => true,
            'hora_silencio_inicio' => '22:00',
            'hora_silencio_fin' => '07:00',
            'zona_horaria' => UsuarioNotificacionConfiguracion::ZONA_HORARIA_FIJA,
            'mostrar_contador_no_leidas' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('app_mobile_usuario_dispositivos')->insert([
            'usuario_id' => 101,
            'device_id' => 'device-int-real-silent',
            'push_token' => 'token-int-real-silent',
            'platform' => 'android',
            'activo' => true,
            'ultimo_registro_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $fakeSender = new class implements PushSender {
            public $calls = 0;

            public function provider()
            {
                return 'fcm';
            }

            public function isConfigured()
            {
                return true;
            }

            public function send(UsuarioDispositivo $dispositivo, array $message)
            {
                $this->calls++;

                return [
                    'success' => true,
                    'invalidToken' => false,
                    'providerMessageId' => 'projects/demo/messages/real-silent',
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
                    'tramiteId' => $contexto['tramiteId'],
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

        $this->assertSame(0, $fakeSender->calls);
    }

    protected function integrationHeaders()
    {
        return [
            'X-Integracion-Token' => $this->integrationToken,
        ];
    }

    protected function crearContextoReal()
    {
        $this->seedRealIdentityContext();

        DB::table('virtual_ESTADO')->insert([
            'ID' => 1,
            'DESCRIPCION' => 'Registrado',
            'DESCRIPCION_MP' => 'Registrado',
            'DESCRIPCION_USER' => 'Registrado',
        ]);

        DB::table('virtual_REMITO')->insert([
            'ID' => 9901,
            'NUMERO_DOCUMENTO' => 'TRM-INT-REAL-001',
            'NUMERO_EMISION' => '0000009901',
            'NRO_EXPEDIENTE' => null,
            'ASUNTO' => 'Tramite integracion real',
            'OBSERVACION' => null,
            'FECHA_EMISION' => '2026-03-24 09:00:00',
            'FECHA' => '2026-03-24 08:00:00',
            'ADMINISTRADO_ID' => 'movil.user',
            'ESTADO_ID' => 1,
            'COD_EMP' => 'EMP-001',
            'CREATED_AT' => '2026-03-24 08:00:00',
            'UPDATED_AT' => '2026-03-24 08:00:00',
            'DELETED_AT' => null,
        ]);

        DB::table('app_mobile_tramite_seguimientos')->insert([
            'usuario_id' => 101,
            'tramite_id' => 9901,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'tramiteId' => 9901,
        ];
    }
}
