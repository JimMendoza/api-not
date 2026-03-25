<?php

namespace Tests\Feature\App;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\App\Concerns\SeedsRealIdentityContext;
use Tests\TestCase;

class RealPushDispositivosFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsRealIdentityContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableRealIdentityMode();
    }

    public function test_upsert_invalidate_y_logout_dispositivo_runtime_real()
    {
        $token = $this->loginRealIdentityUser();
        $headers = [
            'Authorization' => 'Bearer '.$token,
        ];

        $this->withHeaders($headers)
            ->putJson('/api/app/dispositivos/push-token', [
                'deviceId' => 'device-real-001',
                'pushToken' => 'token-real-001',
                'platform' => 'android',
                'deviceName' => 'Pixel Real',
                'appVersion' => '2.0.0',
            ])
            ->assertOk()
            ->assertJson([
                'mensaje' => 'Token push registrado correctamente.',
                'dispositivo' => [
                    'deviceId' => 'device-real-001',
                    'platform' => 'android',
                    'deviceName' => 'Pixel Real',
                    'appVersion' => '2.0.0',
                    'activo' => true,
                ],
            ]);

        $this->assertDatabaseHas('app_mobile_usuario_dispositivos', [
            'usuario_id' => 101,
            'device_id' => 'device-real-001',
            'push_token' => 'token-real-001',
            'activo' => 1,
        ]);

        $this->withHeaders($headers)
            ->putJson('/api/app/dispositivos/push-token', [
                'deviceId' => 'device-real-001',
                'pushToken' => 'token-real-002',
                'platform' => 'android',
                'deviceName' => 'Pixel Real Pro',
                'appVersion' => '2.1.0',
            ])
            ->assertOk();

        $this->assertDatabaseHas('app_mobile_usuario_dispositivos', [
            'usuario_id' => 101,
            'device_id' => 'device-real-001',
            'push_token' => 'token-real-002',
            'device_name' => 'Pixel Real Pro',
            'app_version' => '2.1.0',
            'activo' => 1,
        ]);

        $this->withHeaders($headers)
            ->deleteJson('/api/app/dispositivos/push-token', [
                'deviceId' => 'device-real-001',
            ])
            ->assertOk()
            ->assertExactJson([
                'mensaje' => 'Token push invalidado correctamente.',
            ]);

        $this->assertDatabaseHas('app_mobile_usuario_dispositivos', [
            'usuario_id' => 101,
            'device_id' => 'device-real-001',
            'activo' => 0,
        ]);

        $this->withHeaders($headers)
            ->putJson('/api/app/dispositivos/push-token', [
                'deviceId' => 'device-real-001',
                'pushToken' => 'token-real-003',
                'platform' => 'android',
            ])
            ->assertOk();

        $this->withHeaders($headers)
            ->postJson('/api/app/logout', [
                'deviceId' => 'device-real-001',
            ])
            ->assertOk()
            ->assertExactJson([
                'mensaje' => 'Sesión cerrada correctamente.',
            ]);

        $this->assertDatabaseHas('app_mobile_usuario_dispositivos', [
            'usuario_id' => 101,
            'device_id' => 'device-real-001',
            'activo' => 0,
        ]);
    }

    public function test_upsert_dispositivo_invalido_retorna_422_runtime_real()
    {
        $token = $this->loginRealIdentityUser();

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->putJson('/api/app/dispositivos/push-token', [
            'deviceId' => '',
            'pushToken' => '',
            'platform' => 'web',
        ])->assertStatus(422)
            ->assertJsonValidationErrors([
                'deviceId',
                'pushToken',
                'platform',
            ], 'errores');
    }
}
