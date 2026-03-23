<?php

namespace Tests\Feature\App;

use App\Models\Empresa;
use App\Models\UsuarioApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PushNotificationsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_y_actualiza_token_push()
    {
        $contexto = $this->crearContextoAutenticado();

        $this->withHeaders($contexto['headers'])
            ->putJson('/api/app/dispositivos/push-token', [
                'deviceId' => 'device-001',
                'pushToken' => 'push-token-001',
                'platform' => 'android',
                'deviceName' => 'Pixel 8',
                'appVersion' => '1.0.0',
            ])
            ->assertOk()
            ->assertJson([
                'mensaje' => 'Token push registrado correctamente.',
                'dispositivo' => [
                    'deviceId' => 'device-001',
                    'platform' => 'android',
                    'deviceName' => 'Pixel 8',
                    'appVersion' => '1.0.0',
                    'activo' => true,
                ],
            ]);

        $this->assertDatabaseHas('usuario_app_dispositivos', [
            'usuario_app_id' => $contexto['usuario']->id,
            'device_id' => 'device-001',
            'push_token' => 'push-token-001',
            'platform' => 'android',
            'activo' => 1,
        ]);

        $this->withHeaders($contexto['headers'])
            ->putJson('/api/app/dispositivos/push-token', [
                'deviceId' => 'device-001',
                'pushToken' => 'push-token-002',
                'platform' => 'android',
                'deviceName' => 'Pixel 8 Pro',
                'appVersion' => '1.1.0',
            ])
            ->assertOk()
            ->assertJson([
                'mensaje' => 'Token push registrado correctamente.',
                'dispositivo' => [
                    'deviceId' => 'device-001',
                    'deviceName' => 'Pixel 8 Pro',
                    'appVersion' => '1.1.0',
                ],
            ]);

        $this->assertSame(1, DB::table('usuario_app_dispositivos')->count());
        $this->assertDatabaseHas('usuario_app_dispositivos', [
            'usuario_app_id' => $contexto['usuario']->id,
            'device_id' => 'device-001',
            'push_token' => 'push-token-002',
            'device_name' => 'Pixel 8 Pro',
            'app_version' => '1.1.0',
            'activo' => 1,
        ]);
    }

    public function test_invalida_token_push_y_logout_invalida_dispositivo_actual()
    {
        $contexto = $this->crearContextoAutenticado();

        $this->withHeaders($contexto['headers'])
            ->putJson('/api/app/dispositivos/push-token', [
                'deviceId' => 'device-logout',
                'pushToken' => 'push-token-logout',
                'platform' => 'android',
            ])
            ->assertOk();

        $this->withHeaders($contexto['headers'])
            ->deleteJson('/api/app/dispositivos/push-token', [
                'deviceId' => 'device-logout',
            ])
            ->assertOk()
            ->assertExactJson([
                'mensaje' => 'Token push invalidado correctamente.',
            ]);

        $this->assertDatabaseHas('usuario_app_dispositivos', [
            'usuario_app_id' => $contexto['usuario']->id,
            'device_id' => 'device-logout',
            'activo' => 0,
        ]);

        $this->withHeaders($contexto['headers'])
            ->putJson('/api/app/dispositivos/push-token', [
                'deviceId' => 'device-logout',
                'pushToken' => 'push-token-logout',
                'platform' => 'android',
            ])
            ->assertOk();

        $this->withHeaders($contexto['headers'])
            ->postJson('/api/app/logout', [
                'deviceId' => 'device-logout',
            ])
            ->assertOk()
            ->assertExactJson([
                'mensaje' => 'Sesión cerrada correctamente.',
            ]);

        $this->assertDatabaseHas('usuario_app_dispositivos', [
            'usuario_app_id' => $contexto['usuario']->id,
            'device_id' => 'device-logout',
            'activo' => 0,
        ]);
    }

    public function test_prueba_push_esta_deprecado()
    {
        $contexto = $this->crearContextoAutenticado();

        $this->withHeaders($contexto['headers'])
            ->postJson('/api/app/notificaciones/prueba-push', [
                'tramiteId' => 1,
                'titulo' => 'No usar',
                'mensaje' => 'Deprecated',
            ])
            ->assertStatus(410)
            ->assertExactJson([
                'mensaje' => 'Endpoint deprecado. Use /api/integracion/notificaciones/evento.',
            ]);
    }

    public function test_registro_push_invalido_retorna_422()
    {
        $contexto = $this->crearContextoAutenticado();

        $this->withHeaders($contexto['headers'])
            ->putJson('/api/app/dispositivos/push-token', [
                'deviceId' => '',
                'pushToken' => '',
                'platform' => 'web',
            ])
            ->assertStatus(422)
            ->assertJson([
                'mensaje' => 'Datos inválidos.',
                'errores' => [
                    'deviceId' => [],
                    'pushToken' => [],
                    'platform' => [],
                ],
            ]);
    }

    protected function crearContextoAutenticado()
    {
        $empresa = Empresa::query()->create([
            'codigo' => 'EMP-PUSH',
            'nombre' => 'Empresa Push',
            'imagen' => 'logo.png',
            'activo' => true,
        ]);

        $password = 'Secret123!';

        $usuario = UsuarioApp::query()->create([
            'empresa_id' => $empresa->id,
            'username' => 'push.user',
            'full_name' => 'Usuario Push',
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
}
