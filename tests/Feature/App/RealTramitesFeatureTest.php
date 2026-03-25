<?php

namespace Tests\Feature\App;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\App\Concerns\SeedsRealIdentityContext;
use Tests\TestCase;

class RealTramitesFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsRealIdentityContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableRealIdentityMode();
    }

    public function test_tramites_index_y_show_usan_remito_y_estado_reales()
    {
        $token = $this->loginRealIdentityUser();

        $this->seedEstado(1, 'Registrado');
        $this->seedEstado(2, 'En proceso');

        $this->seedRemito([
            'ID' => 501,
            'NUMERO_DOCUMENTO' => 'TRM-REAL-001',
            'NUMERO_EMISION' => '0000001501',
            'ASUNTO' => "Solicitud de acceso\ncon salto",
            'OBSERVACION' => 'Detalle A',
            'FECHA_EMISION' => '2026-03-24 10:15:00',
            'FECHA' => '2026-03-24 09:15:00',
            'ADMINISTRADO_ID' => 'movil.user',
            'COD_EMP' => 'EMP-001',
            'ESTADO_ID' => 1,
        ]);

        $this->seedRemito([
            'ID' => 502,
            'NUMERO_DOCUMENTO' => null,
            'NUMERO_EMISION' => '0000001502',
            'ASUNTO' => 'Segundo trámite real',
            'OBSERVACION' => null,
            'FECHA_EMISION' => '2026-03-25 08:00:00',
            'FECHA' => '2026-03-25 07:00:00',
            'ADMINISTRADO_ID' => 'movil.user',
            'COD_EMP' => 'EMP-001',
            'ESTADO_ID' => 2,
        ]);

        $this->seedRemito([
            'ID' => 503,
            'NUMERO_DOCUMENTO' => 'TRM-OTHER',
            'NUMERO_EMISION' => '0000001503',
            'ASUNTO' => 'No visible por empresa',
            'OBSERVACION' => null,
            'FECHA_EMISION' => '2026-03-26 08:00:00',
            'FECHA' => '2026-03-26 07:00:00',
            'ADMINISTRADO_ID' => 'movil.user',
            'COD_EMP' => 'EMP-999',
            'ESTADO_ID' => 2,
        ]);

        DB::table('app_mobile_tramite_seguimientos')->insert([
            'usuario_id' => 101,
            'tramite_id' => 501,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('app_mobile_notificaciones')->insert([
            [
                'usuario_id' => 101,
                'tramite_id' => 501,
                'titulo' => 'Pendiente 1',
                'mensaje' => 'Mensaje 1',
                'tipo' => 'MOVIMIENTO',
                'leida' => false,
                'fecha_hora' => '2026-03-24 11:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => 101,
                'tramite_id' => 501,
                'titulo' => 'Pendiente 2',
                'mensaje' => 'Mensaje 2',
                'tipo' => 'MOVIMIENTO',
                'leida' => false,
                'fecha_hora' => '2026-03-24 12:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => 101,
                'tramite_id' => 502,
                'titulo' => 'No seguida',
                'mensaje' => 'Mensaje 3',
                'tipo' => 'MOVIMIENTO',
                'leida' => false,
                'fecha_hora' => '2026-03-24 13:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $indexResponse = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/app/tramites');

        $indexResponse->assertOk()->assertExactJson([
            [
                'id' => 502,
                'codigo' => '0000001502',
                'titulo' => 'Segundo trámite real',
                'fecha' => '2026-03-25',
                'estadoActual' => 'En proceso',
                'siguiendo' => false,
                'notificacionesNoLeidas' => 0,
            ],
            [
                'id' => 501,
                'codigo' => 'TRM-REAL-001',
                'titulo' => 'Solicitud de acceso con salto',
                'fecha' => '2026-03-24',
                'estadoActual' => 'Registrado',
                'siguiendo' => true,
                'notificacionesNoLeidas' => 2,
            ],
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/app/tramites/501')
            ->assertOk()
            ->assertExactJson([
                'id' => 501,
                'codigo' => 'TRM-REAL-001',
                'titulo' => 'Solicitud de acceso con salto',
                'fecha' => '2026-03-24',
                'estadoActual' => 'Registrado',
                'siguiendo' => true,
                'notificacionesNoLeidas' => 2,
                'descripcion' => 'Detalle A',
            ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/app/tramites/503')
            ->assertStatus(404)
            ->assertExactJson([
                'mensaje' => 'Trámite no encontrado.',
            ]);
    }

    public function test_seguir_y_dejar_seguir_usan_app_mobile_tramite_seguimientos()
    {
        $token = $this->loginRealIdentityUser();

        $this->seedEstado(1, 'Registrado');
        $this->seedRemito([
            'ID' => 601,
            'NUMERO_DOCUMENTO' => 'TRM-REAL-SEG',
            'NUMERO_EMISION' => '0000001601',
            'ASUNTO' => 'Trámite a seguir',
            'OBSERVACION' => null,
            'FECHA_EMISION' => '2026-03-24 10:00:00',
            'FECHA' => '2026-03-24 09:00:00',
            'ADMINISTRADO_ID' => 'movil.user',
            'COD_EMP' => 'EMP-001',
            'ESTADO_ID' => 1,
        ]);

        $headers = [
            'Authorization' => 'Bearer '.$token,
        ];

        $this->withHeaders($headers)
            ->postJson('/api/app/tramites/601/seguir')
            ->assertOk()
            ->assertExactJson([
                'mensaje' => 'Trámite marcado para seguimiento.',
            ]);

        $this->assertDatabaseHas('app_mobile_tramite_seguimientos', [
            'usuario_id' => 101,
            'tramite_id' => 601,
            'activo' => 1,
        ]);

        $this->withHeaders($headers)
            ->deleteJson('/api/app/tramites/601/seguir')
            ->assertOk()
            ->assertExactJson([
                'mensaje' => 'Seguimiento eliminado.',
            ]);

        $this->assertDatabaseHas('app_mobile_tramite_seguimientos', [
            'usuario_id' => 101,
            'tramite_id' => 601,
            'activo' => 0,
        ]);
    }

    public function test_hoja_ruta_queda_controlado_en_modo_real()
    {
        $token = $this->loginRealIdentityUser();

        $this->seedEstado(1, 'Registrado');
        $this->seedRemito([
            'ID' => 701,
            'NUMERO_DOCUMENTO' => 'TRM-REAL-RUTA',
            'NUMERO_EMISION' => '0000001701',
            'ASUNTO' => 'Trámite sin hoja de ruta real',
            'OBSERVACION' => null,
            'FECHA_EMISION' => '2026-03-24 10:00:00',
            'FECHA' => '2026-03-24 09:00:00',
            'ADMINISTRADO_ID' => 'movil.user',
            'COD_EMP' => 'EMP-001',
            'ESTADO_ID' => 1,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/app/tramites/701/hoja-ruta')
            ->assertStatus(501)
            ->assertExactJson([
                'mensaje' => 'Hoja de ruta no disponible mientras no exista una fuente real de movimientos.',
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
