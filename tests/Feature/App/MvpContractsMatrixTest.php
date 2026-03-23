<?php

namespace Tests\Feature\App;

use App\Models\Empresa;
use App\Models\Notificacion;
use App\Models\Tramite;
use App\Models\TramiteMovimiento;
use App\Models\TramiteSeguimiento;
use App\Models\UsuarioApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MvpContractsMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_entidades_contrato_v1()
    {
        Empresa::query()->create([
            'codigo' => 'EMP-Z',
            'nombre' => 'Zeta',
            'imagen' => 'z.png',
            'activo' => true,
        ]);

        Empresa::query()->create([
            'codigo' => 'EMP-X',
            'nombre' => 'Inactiva',
            'imagen' => 'x.png',
            'activo' => false,
        ]);

        Empresa::query()->create([
            'codigo' => 'EMP-A',
            'nombre' => 'Alfa',
            'imagen' => 'a.png',
            'activo' => true,
        ]);

        $response = $this->postJson('/api/app/entidades');

        $response->assertOk()->assertExactJson([
            [
                'codigo' => 'EMP-A',
                'id' => 'EMP-A',
                'nombre' => 'Alfa',
                'imagen' => 'a.png',
            ],
            [
                'codigo' => 'EMP-Z',
                'id' => 'EMP-Z',
                'nombre' => 'Zeta',
                'imagen' => 'z.png',
            ],
        ]);
    }

    public function test_modulos_contrato_v1_y_401_sin_token()
    {
        $this->getJson('/api/app/modulos')
            ->assertStatus(401)
            ->assertExactJson([
                'mensaje' => 'No autenticado.',
            ]);

        $contexto = $this->crearContextoAutenticado();

        $this->withHeaders($contexto['headers'])
            ->getJson('/api/app/modulos')
            ->assertOk()
            ->assertExactJson(array_values(config('app_mobile.modules', [])));
    }

    public function test_logout_contrato_v1_y_comportamiento_token_invalido()
    {
        $contexto = $this->crearContextoAutenticado();
        $usuario = $contexto['usuario'];

        $this->postJson('/api/app/logout')
            ->assertStatus(401)
            ->assertExactJson([
                'mensaje' => 'No autenticado.',
            ]);

        $this->assertSame(1, DB::table('usuario_app_tokens')
            ->where('usuario_app_id', $usuario->id)
            ->whereNull('revoked_at')
            ->count());

        $this->withHeaders($contexto['headers'])
            ->postJson('/api/app/logout')
            ->assertOk()
            ->assertExactJson([
                'mensaje' => 'Sesión cerrada correctamente.',
            ]);

        $this->assertSame(0, DB::table('usuario_app_tokens')
            ->where('usuario_app_id', $usuario->id)
            ->whereNull('revoked_at')
            ->count());

        $this->assertSame(1, DB::table('usuario_app_tokens')
            ->where('usuario_app_id', $usuario->id)
            ->whereNotNull('revoked_at')
            ->count());

        $this->withHeaders($contexto['headers'])
            ->postJson('/api/app/logout')
            ->assertStatus(401)
            ->assertExactJson([
                'mensaje' => 'No autenticado.',
            ]);

        $this->withHeaders([
            'Authorization' => 'Bearer token-invalido',
        ])->postJson('/api/app/logout')
            ->assertStatus(401)
            ->assertExactJson([
                'mensaje' => 'No autenticado.',
            ]);
    }

    public function test_tramites_index_y_show_contrato_v1()
    {
        $contexto = $this->crearContextoAutenticado();
        $usuario = $contexto['usuario'];

        $tramiteA = $this->crearTramite($usuario, [
            'codigo' => 'TRM-001',
            'titulo' => 'Trámite A',
            'descripcion' => 'Detalle A',
            'fecha_registro' => '2026-03-18',
            'estado_actual' => 'Registrado',
        ]);

        $tramiteB = $this->crearTramite($usuario, [
            'codigo' => 'TRM-002',
            'titulo' => 'Trámite B',
            'descripcion' => 'Detalle B',
            'fecha_registro' => '2026-03-19',
            'estado_actual' => 'En proceso',
        ]);

        TramiteSeguimiento::query()->create([
            'tramite_id' => $tramiteA->id,
            'usuario_app_id' => $usuario->id,
            'activo' => true,
        ]);

        Notificacion::query()->create([
            'tramite_id' => $tramiteA->id,
            'usuario_app_id' => $usuario->id,
            'titulo' => 'N1',
            'mensaje' => 'Pendiente 1',
            'tipo' => 'estado',
            'leida' => false,
            'fecha_hora' => '2026-03-19 08:00:00',
        ]);

        Notificacion::query()->create([
            'tramite_id' => $tramiteA->id,
            'usuario_app_id' => $usuario->id,
            'titulo' => 'N2',
            'mensaje' => 'Pendiente 2',
            'tipo' => 'estado',
            'leida' => false,
            'fecha_hora' => '2026-03-19 09:00:00',
        ]);

        $indexResponse = $this->withHeaders($contexto['headers'])->getJson('/api/app/tramites');

        $indexResponse->assertOk()->assertJsonCount(2);
        $indexResponse->assertJsonFragment([
            'id' => $tramiteA->id,
            'codigo' => 'TRM-001',
            'estadoActual' => 'Registrado',
            'siguiendo' => true,
            'notificacionesNoLeidas' => 2,
        ]);

        $indexResponse->assertJsonFragment([
            'id' => $tramiteB->id,
            'codigo' => 'TRM-002',
            'estadoActual' => 'En proceso',
            'siguiendo' => false,
            'notificacionesNoLeidas' => 0,
        ]);

        $this->withHeaders($contexto['headers'])
            ->getJson('/api/app/tramites/'.$tramiteA->id)
            ->assertOk()
            ->assertJson([
                'id' => $tramiteA->id,
                'codigo' => 'TRM-001',
                'descripcion' => 'Detalle A',
            ]);

        $this->withHeaders($contexto['headers'])
            ->getJson('/api/app/tramites/999999')
            ->assertStatus(404)
            ->assertExactJson([
                'mensaje' => 'Trámite no encontrado.',
            ]);
    }

    public function test_tramites_hoja_ruta_contrato_v1()
    {
        $contexto = $this->crearContextoAutenticado();
        $usuario = $contexto['usuario'];

        $tramite = $this->crearTramite($usuario, [
            'codigo' => 'TRM-RUTA',
        ]);

        TramiteMovimiento::query()->create([
            'tramite_id' => $tramite->id,
            'fecha_hora' => '2026-03-19 11:00:00',
            'nro_doc' => 'DOC-002',
            'destino' => 'Mesa B',
            'estado' => 'Derivado',
        ]);

        TramiteMovimiento::query()->create([
            'tramite_id' => $tramite->id,
            'fecha_hora' => '2026-03-19 09:00:00',
            'nro_doc' => 'DOC-001',
            'destino' => 'Mesa A',
            'estado' => 'Registrado',
        ]);

        $this->getJson('/api/app/tramites/'.$tramite->id.'/hoja-ruta')
            ->assertStatus(401)
            ->assertExactJson([
                'mensaje' => 'No autenticado.',
            ]);

        $this->withHeaders($contexto['headers'])
            ->getJson('/api/app/tramites/'.$tramite->id.'/hoja-ruta')
            ->assertOk()
            ->assertExactJson([
                [
                    'fechaHora' => '2026-03-19 09:00',
                    'nroDoc' => 'DOC-001',
                    'destino' => 'Mesa A',
                    'estado' => 'Registrado',
                ],
                [
                    'fechaHora' => '2026-03-19 11:00',
                    'nroDoc' => 'DOC-002',
                    'destino' => 'Mesa B',
                    'estado' => 'Derivado',
                ],
            ]);

        $this->withHeaders($contexto['headers'])
            ->getJson('/api/app/tramites/999999/hoja-ruta')
            ->assertStatus(404)
            ->assertExactJson([
                'mensaje' => 'Trámite no encontrado.',
            ]);
    }

    public function test_tramites_seguir_y_dejar_seguir_contrato_v1()
    {
        $contexto = $this->crearContextoAutenticado();
        $usuario = $contexto['usuario'];

        $tramite = $this->crearTramite($usuario, [
            'codigo' => 'TRM-SEG',
        ]);

        $this->withHeaders($contexto['headers'])
            ->postJson('/api/app/tramites/'.$tramite->id.'/seguir')
            ->assertOk()
            ->assertExactJson([
                'mensaje' => 'Trámite marcado para seguimiento.',
            ]);

        $this->assertDatabaseHas('tramite_seguimientos', [
            'tramite_id' => $tramite->id,
            'usuario_app_id' => $usuario->id,
            'activo' => 1,
        ]);

        $this->withHeaders($contexto['headers'])
            ->deleteJson('/api/app/tramites/'.$tramite->id.'/seguir')
            ->assertOk()
            ->assertExactJson([
                'mensaje' => 'Seguimiento eliminado.',
            ]);

        $this->assertDatabaseHas('tramite_seguimientos', [
            'tramite_id' => $tramite->id,
            'usuario_app_id' => $usuario->id,
            'activo' => 0,
        ]);
    }

    public function test_notificaciones_index_resumen_y_marcar_leida_contrato_v1()
    {
        $contexto = $this->crearContextoAutenticado();
        $usuario = $contexto['usuario'];

        $tramite = $this->crearTramite($usuario, [
            'codigo' => 'TRM-NOTI',
            'titulo' => 'Trámite Noti',
        ]);

        TramiteSeguimiento::query()->create([
            'tramite_id' => $tramite->id,
            'usuario_app_id' => $usuario->id,
            'activo' => true,
        ]);

        $noLeida = Notificacion::query()->create([
            'tramite_id' => $tramite->id,
            'usuario_app_id' => $usuario->id,
            'titulo' => 'Pendiente',
            'mensaje' => 'Mensaje pendiente',
            'tipo' => 'estado',
            'leida' => false,
            'fecha_hora' => '2026-03-19 08:15:00',
        ]);

        $leida = Notificacion::query()->create([
            'tramite_id' => $tramite->id,
            'usuario_app_id' => $usuario->id,
            'titulo' => 'Leída',
            'mensaje' => 'Mensaje leído',
            'tipo' => 'estado',
            'leida' => true,
            'fecha_hora' => '2026-03-19 09:15:00',
        ]);

        $this->withHeaders($contexto['headers'])
            ->getJson('/api/app/notificaciones/resumen')
            ->assertOk()
            ->assertExactJson([
                'noLeidas' => 1,
            ]);

        $indexResponse = $this->withHeaders($contexto['headers'])->getJson('/api/app/notificaciones');
        $indexResponse->assertOk()->assertJsonCount(2);

        $items = $indexResponse->json();
        $this->assertSame($noLeida->id, $items[0]['id']);
        $this->assertSame($leida->id, $items[1]['id']);

        $indexResponse->assertJsonFragment([
            'id' => $noLeida->id,
            'tramiteId' => $tramite->id,
            'codigoTramite' => 'TRM-NOTI',
            'titulo' => 'Pendiente',
            'mensaje' => 'Mensaje pendiente',
            'tipo' => 'estado',
            'leida' => false,
            'fechaHora' => '2026-03-19 08:15',
        ]);

        $this->withHeaders($contexto['headers'])
            ->patchJson('/api/app/notificaciones/'.$noLeida->id.'/leida')
            ->assertOk()
            ->assertExactJson([
                'mensaje' => 'Notificación marcada como leída.',
            ]);

        $this->assertDatabaseHas('notificaciones', [
            'id' => $noLeida->id,
            'leida' => 1,
        ]);

        $this->withHeaders($contexto['headers'])
            ->getJson('/api/app/notificaciones/resumen')
            ->assertOk()
            ->assertExactJson([
                'noLeidas' => 0,
            ]);

        $this->withHeaders($contexto['headers'])
            ->patchJson('/api/app/notificaciones/999999/leida')
            ->assertStatus(404)
            ->assertExactJson([
                'mensaje' => 'Notificación no encontrada.',
            ]);
    }

    private function crearContextoAutenticado()
    {
        $empresa = Empresa::query()->create([
            'codigo' => 'EMP-001',
            'nombre' => 'Empresa Demo',
            'imagen' => 'logo.png',
            'activo' => true,
        ]);

        $password = 'Secret123!';

        $usuario = UsuarioApp::query()->create([
            'empresa_id' => $empresa->id,
            'username' => 'movil.user',
            'full_name' => 'Usuario Movil',
            'password' => Hash::make($password),
            'activo' => true,
        ]);

        $login = $this->postJson('/api/app/login', [
            'username' => $usuario->username,
            'password' => $password,
            'codEmp' => $empresa->codigo,
        ]);

        $login->assertOk();

        return [
            'empresa' => $empresa,
            'usuario' => $usuario,
            'accessToken' => $login->json('accessToken'),
            'headers' => [
                'Authorization' => 'Bearer '.$login->json('accessToken'),
            ],
        ];
    }

    private function crearTramite(UsuarioApp $usuario, array $overrides = [])
    {
        $defaults = [
            'empresa_id' => $usuario->empresa_id,
            'usuario_app_id' => $usuario->id,
            'codigo' => 'TRM-'.strtoupper(substr(md5(uniqid('', true)), 0, 6)),
            'titulo' => 'Trámite de prueba',
            'descripcion' => 'Descripción de prueba',
            'fecha_registro' => '2026-03-19',
            'estado_actual' => 'Registrado',
            'activo' => true,
        ];

        return Tramite::query()->create(array_merge($defaults, $overrides));
    }
}



