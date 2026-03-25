<?php

namespace Tests\Feature\App;

use App\Models\AppMobile\UsuarioNotificacionConfiguracion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\App\Concerns\SeedsRealIdentityContext;
use Tests\TestCase;

class RealNotificacionesFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsRealIdentityContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableRealIdentityMode();
    }

    public function test_notificaciones_index_resumen_y_marcar_leida_runtime_real()
    {
        $token = $this->loginRealIdentityUser();

        $this->seedEstado(1, 'Registrado');

        $this->seedRemito([
            'ID' => 801,
            'NUMERO_DOCUMENTO' => 'TRM-NOTI-801',
            'NUMERO_EMISION' => '0000001801',
            'ASUNTO' => 'Tramite visible',
            'OBSERVACION' => null,
            'FECHA_EMISION' => '2026-03-24 10:00:00',
            'FECHA' => '2026-03-24 09:00:00',
            'ADMINISTRADO_ID' => 'movil.user',
            'COD_EMP' => 'EMP-001',
            'ESTADO_ID' => 1,
        ]);

        $this->seedRemito([
            'ID' => 802,
            'NUMERO_DOCUMENTO' => 'TRM-NOTI-802',
            'NUMERO_EMISION' => '0000001802',
            'ASUNTO' => 'Tramite oculto por empresa',
            'OBSERVACION' => null,
            'FECHA_EMISION' => '2026-03-24 11:00:00',
            'FECHA' => '2026-03-24 10:00:00',
            'ADMINISTRADO_ID' => 'movil.user',
            'COD_EMP' => 'EMP-999',
            'ESTADO_ID' => 1,
        ]);

        DB::table('app_mobile_tramite_seguimientos')->insert([
            [
                'usuario_id' => 101,
                'tramite_id' => 801,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => 101,
                'tramite_id' => 802,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('app_mobile_notificaciones')->insert([
            [
                'id' => 9001,
                'usuario_id' => 101,
                'tramite_id' => 801,
                'titulo' => 'Pendiente 1',
                'mensaje' => 'Mensaje 1',
                'tipo' => 'estado',
                'leida' => false,
                'fecha_hora' => '2026-03-24 12:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9002,
                'usuario_id' => 101,
                'tramite_id' => 801,
                'titulo' => 'Pendiente 2',
                'mensaje' => 'Mensaje 2',
                'tipo' => 'estado',
                'leida' => false,
                'fecha_hora' => '2026-03-24 11:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9003,
                'usuario_id' => 101,
                'tramite_id' => 801,
                'titulo' => 'Leida',
                'mensaje' => 'Mensaje 3',
                'tipo' => 'estado',
                'leida' => true,
                'fecha_hora' => '2026-03-24 10:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9004,
                'usuario_id' => 101,
                'tramite_id' => 802,
                'titulo' => 'Oculta',
                'mensaje' => 'Mensaje 4',
                'tipo' => 'estado',
                'leida' => false,
                'fecha_hora' => '2026-03-24 13:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $headers = [
            'Authorization' => 'Bearer '.$token,
        ];

        $this->withHeaders($headers)
            ->getJson('/api/app/notificaciones/resumen')
            ->assertOk()
            ->assertExactJson([
                'noLeidas' => 2,
            ]);

        $this->withHeaders($headers)
            ->getJson('/api/app/notificaciones')
            ->assertOk()
            ->assertExactJson([
                [
                    'id' => 9001,
                    'tramiteId' => 801,
                    'codigoTramite' => 'TRM-NOTI-801',
                    'titulo' => 'Pendiente 1',
                    'mensaje' => 'Mensaje 1',
                    'tipo' => 'estado',
                    'leida' => false,
                    'fechaHora' => '2026-03-24 12:00',
                ],
                [
                    'id' => 9002,
                    'tramiteId' => 801,
                    'codigoTramite' => 'TRM-NOTI-801',
                    'titulo' => 'Pendiente 2',
                    'mensaje' => 'Mensaje 2',
                    'tipo' => 'estado',
                    'leida' => false,
                    'fechaHora' => '2026-03-24 11:00',
                ],
                [
                    'id' => 9003,
                    'tramiteId' => 801,
                    'codigoTramite' => 'TRM-NOTI-801',
                    'titulo' => 'Leida',
                    'mensaje' => 'Mensaje 3',
                    'tipo' => 'estado',
                    'leida' => true,
                    'fechaHora' => '2026-03-24 10:00',
                ],
            ]);

        $this->withHeaders($headers)
            ->patchJson('/api/app/notificaciones/9002/leida')
            ->assertOk()
            ->assertExactJson([
                'mensaje' => 'Notificación marcada como leída.',
            ]);

        $this->assertDatabaseHas('app_mobile_notificaciones', [
            'id' => 9002,
            'leida' => 1,
        ]);

        $this->withHeaders($headers)
            ->getJson('/api/app/notificaciones/resumen')
            ->assertOk()
            ->assertExactJson([
                'noLeidas' => 1,
            ]);

        $this->withHeaders($headers)
            ->patchJson('/api/app/notificaciones/999999/leida')
            ->assertStatus(404)
            ->assertExactJson([
                'mensaje' => 'Notificación no encontrada.',
            ]);
    }

    public function test_notificaciones_configuracion_runtime_real()
    {
        $token = $this->loginRealIdentityUser();

        $headers = [
            'Authorization' => 'Bearer '.$token,
        ];

        $this->withHeaders($headers)
            ->getJson('/api/app/notificaciones/configuracion')
            ->assertOk()
            ->assertExactJson(UsuarioNotificacionConfiguracion::defaultSettings());

        $this->withHeaders($headers)
            ->putJson('/api/app/notificaciones/configuracion', [
                'silenciar_fuera_de_horario' => true,
                'hora_silencio_inicio' => '21:00',
                'hora_silencio_fin' => '06:00',
                'zona_horaria' => 'Europe/Madrid',
                'mostrar_contador_no_leidas' => false,
            ])
            ->assertOk()
            ->assertExactJson([
                'silenciar_fuera_de_horario' => true,
                'hora_silencio_inicio' => '21:00',
                'hora_silencio_fin' => '06:00',
                'zona_horaria' => UsuarioNotificacionConfiguracion::ZONA_HORARIA_FIJA,
                'mostrar_contador_no_leidas' => false,
            ]);

        $this->assertDatabaseHas('app_mobile_usuario_notificacion_configuraciones', [
            'usuario_id' => 101,
            'silenciar_fuera_de_horario' => 1,
            'hora_silencio_inicio' => '21:00',
            'hora_silencio_fin' => '06:00',
            'zona_horaria' => UsuarioNotificacionConfiguracion::ZONA_HORARIA_FIJA,
            'mostrar_contador_no_leidas' => 0,
        ]);
    }

    protected function seedEstado($id, $descripcionUser)
    {
        DB::table('virtual_ESTADO')->insert([
            'ID' => $id,
            'DESCRIPCION' => $descripcionUser,
            'DESCRIPCION_MP' => $descripcionUser,
            'DESCRIPCION_USER' => $descripcionUser,
        ]);
    }

    protected function seedRemito(array $data)
    {
        DB::table('virtual_REMITO')->insert(array_merge([
            'CREATED_AT' => '2026-03-24 08:00:00',
            'UPDATED_AT' => '2026-03-24 08:00:00',
            'DELETED_AT' => null,
        ], $data));
    }
}
