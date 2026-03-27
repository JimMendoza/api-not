<?php

namespace Tests\Feature\App;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

        $storedToken = DB::table('app_mobile_usuario_tokens')
            ->where('usuario_id', 101)
            ->first();

        $this->assertNotNull($storedToken);
        $this->assertNotNull($storedToken->expires_at);
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

    public function test_token_expirado_no_autentica()
    {
        $token = $this->loginRealIdentityUser();

        DB::table('app_mobile_usuario_tokens')
            ->update([
                'expires_at' => now()->subMinute(),
                'updated_at' => now(),
            ]);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/app/me')
            ->assertStatus(401)
            ->assertExactJson([
                'mensaje' => 'No autenticado.',
            ]);
    }

    public function test_request_autenticado_renueva_expiracion_del_token()
    {
        Carbon::setTestNow(Carbon::parse('2026-03-27 10:00:00'));

        $token = $this->loginRealIdentityUser();
        $hashedToken = hash('sha256', $token);

        $initialExpiration = Carbon::parse(
            DB::table('app_mobile_usuario_tokens')
                ->where('token', $hashedToken)
                ->value('expires_at')
        );

        Carbon::setTestNow(Carbon::parse('2026-03-28 10:00:00'));

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/app/me')
            ->assertOk();

        $renewedExpiration = Carbon::parse(
            DB::table('app_mobile_usuario_tokens')
                ->where('token', $hashedToken)
                ->value('expires_at')
        );

        $this->assertTrue($renewedExpiration->gt($initialExpiration));

        Carbon::setTestNow();
    }

    public function test_entidades_contrato_final_es_publico_por_post()
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

        $this->getJson('/api/app/entidades')
            ->assertStatus(405);
    }

    public function test_modulos_requiere_auth_y_devuelve_catalogo_configurado()
    {
        $this->getJson('/api/app/modulos')
            ->assertStatus(401)
            ->assertExactJson([
                'mensaje' => 'No autenticado.',
            ]);

        $token = $this->loginRealIdentityUser();

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/app/modulos')
            ->assertOk()
            ->assertExactJson(config('mobile.modules'));
    }
}
