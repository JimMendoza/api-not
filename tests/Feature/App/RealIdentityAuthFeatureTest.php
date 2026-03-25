<?php

namespace Tests\Feature\App;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\App\Concerns\SeedsRealIdentityContext;
use Tests\TestCase;

class RealIdentityAuthFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsRealIdentityContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableRealIdentityMode();
    }

    public function test_login_autentica_contra_identidad_real_y_guarda_token_en_app_mobile()
    {
        $this->seedRealIdentityContext();

        $response = $this->postJson('/api/app/login', [
            'username' => 'movil.user',
            'password' => 'Secret123!',
            'codEmp' => 'EMP-001',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'accessToken',
                'tokenType',
            ]);

        $this->assertDatabaseHas('app_mobile_usuario_tokens', [
            'usuario_id' => 101,
            'empresa_codigo' => 'EMP-001',
            'token_type' => config('mobile.token_type', 'Bearer'),
        ]);
    }

    public function test_me_devuelve_contrato_real_desde_tramite()
    {
        $token = $this->loginRealIdentityUser();

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/app/me')
            ->assertOk()
            ->assertExactJson([
                'username' => 'movil.user',
                'fullName' => 'Usuario Movil Demo',
                'empresa' => [
                    'codigo' => 'EMP-001',
                    'id' => 'EMP-001',
                    'nombre' => 'Empresa Real Demo',
                    'imagen' => 'logo-real.png',
                ],
                'permisos' => [
                    'mesa_partes_virtual',
                    'notificaciones',
                ],
                'session' => [
                    'authenticated' => true,
                    'tokenType' => config('mobile.token_type', 'Bearer'),
                ],
            ]);
    }

    public function test_me_usa_nom_usuario_como_fallback_de_nombre_visible()
    {
        $token = $this->loginRealIdentityUser([
            'username' => '20131257750',
            'nomUsuario' => 'Nombre Perfil Visible',
            'fullName' => [
                'nombres' => '',
                'apellidoPaterno' => '',
                'apellidoMaterno' => '',
            ],
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/app/me')
            ->assertOk()
            ->assertJson([
                'username' => '20131257750',
                'fullName' => 'Nombre Perfil Visible',
            ]);
    }

    public function test_entidades_lee_empresas_reales_activas()
    {
        $this->seedRealIdentityContext();

        DB::table('maestro_EMPRESA')->insert([
            'COD_EMP' => 'EMP-999',
            'DES_EMP' => 'Empresa Inactiva',
            'IMAGEN' => 'x.png',
            'IND_ESTADO' => 'I',
        ]);

        $this->postJson('/api/app/entidades')
            ->assertOk()
            ->assertExactJson([
                [
                    'codigo' => 'EMP-001',
                    'id' => 'EMP-001',
                    'nombre' => 'Empresa Real Demo',
                    'imagen' => 'logo-real.png',
                ],
            ]);
    }
}
