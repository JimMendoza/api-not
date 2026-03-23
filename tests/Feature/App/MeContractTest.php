<?php

namespace Tests\Feature\App;

use App\Models\Empresa;
use App\Models\UsuarioApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MeContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_devuelve_solo_payload_de_autenticacion()
    {
        $credenciales = $this->crearCredencialesUsuarioApp();

        $response = $this->postJson('/api/app/login', [
            'username' => $credenciales['usuario']->username,
            'password' => $credenciales['password'],
            'codEmp' => $credenciales['empresa']->codigo,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'accessToken',
                'tokenType',
            ]);

        $payload = $response->json();

        $this->assertArrayNotHasKey('username', $payload);
        $this->assertArrayNotHasKey('fullName', $payload);
        $this->assertArrayNotHasKey('permisos', $payload);
    }

    public function test_me_devuelve_contrato_v1_canonico()
    {
        $credenciales = $this->crearCredencialesUsuarioApp();

        $loginResponse = $this->postJson('/api/app/login', [
            'username' => $credenciales['usuario']->username,
            'password' => $credenciales['password'],
            'codEmp' => $credenciales['empresa']->codigo,
        ]);

        $loginResponse->assertOk();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$loginResponse->json('accessToken'),
        ])->getJson('/api/app/me');

        $response->assertOk()->assertExactJson([
            'username' => $credenciales['usuario']->username,
            'fullName' => $credenciales['usuario']->full_name,
            'empresa' => [
                'codigo' => $credenciales['empresa']->codigo,
                'id' => $credenciales['empresa']->codigo,
                'nombre' => $credenciales['empresa']->nombre,
                'imagen' => $credenciales['empresa']->imagen,
            ],
            'permisos' => $this->permisosEsperados(),
            'session' => [
                'authenticated' => true,
                'tokenType' => config('app_mobile.token_type', 'Bearer'),
            ],
        ]);
    }

    public function test_me_retorna_401_si_no_envia_token()
    {
        $response = $this->getJson('/api/app/me');

        $response->assertStatus(401)
            ->assertExactJson([
                'mensaje' => 'No autenticado.',
            ]);
    }

    private function crearCredencialesUsuarioApp()
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

        return [
            'empresa' => $empresa,
            'usuario' => $usuario,
            'password' => $password,
        ];
    }

    private function permisosEsperados()
    {
        return collect(config('app_mobile.modules', []))
            ->pluck('id')
            ->values()
            ->all();
    }
}
