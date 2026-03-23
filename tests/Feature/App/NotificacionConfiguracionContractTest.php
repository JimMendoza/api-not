<?php

namespace Tests\Feature\App;

use App\Models\Empresa;
use App\Models\UsuarioApp;
use App\Models\UsuarioAppNotificacionConfiguracion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificacionConfiguracionContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_exitoso_para_usuario_app()
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

        $this->assertNotEmpty($response->json('accessToken'));
    }

    public function test_get_configuracion_devuelve_defaults_v2_si_no_existe_registro()
    {
        $credenciales = $this->crearCredencialesUsuarioApp();
        $headers = $this->loginHeaders($credenciales);

        $response = $this->withHeaders($headers)->getJson('/api/app/notificaciones/configuracion');

        $response->assertOk()
            ->assertExactJson(UsuarioAppNotificacionConfiguracion::defaultSettings());

        $this->assertSame(0, DB::table('usuario_app_notificacion_configuraciones')->count());
    }

    public function test_put_configuracion_v2_valida_persiste_y_retorna_payload()
    {
        $credenciales = $this->crearCredencialesUsuarioApp();
        $headers = $this->loginHeaders($credenciales);

        $payload = [
            'silenciar_fuera_de_horario' => true,
            'hora_silencio_inicio' => '21:30',
            'hora_silencio_fin' => '06:15',
            'zona_horaria' => 'America/Lima',
            'mostrar_contador_no_leidas' => false,
        ];

        $response = $this->withHeaders($headers)->putJson('/api/app/notificaciones/configuracion', $payload);

        $response->assertOk()->assertExactJson($payload);

        $this->assertDatabaseHas('usuario_app_notificacion_configuraciones', [
            'usuario_app_id' => $credenciales['usuario']->id,
            'silenciar_fuera_de_horario' => 1,
            'hora_silencio_inicio' => '21:30',
            'hora_silencio_fin' => '06:15',
            'zona_horaria' => 'America/Lima',
            'mostrar_contador_no_leidas' => 0,
        ]);

        $this->withHeaders($headers)
            ->getJson('/api/app/notificaciones/configuracion')
            ->assertOk()
            ->assertExactJson($payload);
    }

    public function test_put_configuracion_v2_normaliza_zona_horaria_a_america_lima()
    {
        $credenciales = $this->crearCredencialesUsuarioApp();
        $headers = $this->loginHeaders($credenciales);

        $payload = [
            'silenciar_fuera_de_horario' => true,
            'hora_silencio_inicio' => '21:00',
            'hora_silencio_fin' => '06:00',
            'zona_horaria' => 'Europe/Madrid',
            'mostrar_contador_no_leidas' => true,
        ];

        $response = $this->withHeaders($headers)->putJson('/api/app/notificaciones/configuracion', $payload);

        $response->assertOk()->assertExactJson([
            'silenciar_fuera_de_horario' => true,
            'hora_silencio_inicio' => '21:00',
            'hora_silencio_fin' => '06:00',
            'zona_horaria' => UsuarioAppNotificacionConfiguracion::ZONA_HORARIA_FIJA,
            'mostrar_contador_no_leidas' => true,
        ]);

        $this->assertDatabaseHas('usuario_app_notificacion_configuraciones', [
            'usuario_app_id' => $credenciales['usuario']->id,
            'zona_horaria' => UsuarioAppNotificacionConfiguracion::ZONA_HORARIA_FIJA,
        ]);
    }

    public function test_put_configuracion_v2_invalida_retorna_422_con_errores_de_hora_y_flags()
    {
        $credenciales = $this->crearCredencialesUsuarioApp();
        $headers = $this->loginHeaders($credenciales);

        $payload = [
            'silenciar_fuera_de_horario' => 'si',
            'hora_silencio_inicio' => '25:00',
            'hora_silencio_fin' => '10:90',
            'zona_horaria' => 'Lima/Peru',
            // mostrar_contador_no_leidas faltante para validar required
        ];

        $response = $this->withHeaders($headers)->putJson('/api/app/notificaciones/configuracion', $payload);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'mensaje',
                'errores',
            ])
            ->assertJsonValidationErrors([
                'silenciar_fuera_de_horario',
                'hora_silencio_inicio',
                'hora_silencio_fin',
                'mostrar_contador_no_leidas',
            ], 'errores');

        $this->assertSame(0, DB::table('usuario_app_notificacion_configuraciones')->count());
    }

    public function test_put_configuracion_v2_rechaza_horas_iguales()
    {
        $credenciales = $this->crearCredencialesUsuarioApp();
        $headers = $this->loginHeaders($credenciales);

        $payload = [
            'silenciar_fuera_de_horario' => true,
            'hora_silencio_inicio' => '22:00',
            'hora_silencio_fin' => '22:00',
            'zona_horaria' => 'America/Lima',
            'mostrar_contador_no_leidas' => true,
        ];

        $this->withHeaders($headers)
            ->putJson('/api/app/notificaciones/configuracion', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'hora_silencio_inicio',
                'hora_silencio_fin',
            ], 'errores');
    }

    private function crearCredencialesUsuarioApp()
    {
        $empresa = Empresa::query()->create([
            'codigo' => 'EMP-001',
            'nombre' => 'Empresa Demo',
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

    private function loginHeaders(array $credenciales)
    {
        $loginResponse = $this->postJson('/api/app/login', [
            'username' => $credenciales['usuario']->username,
            'password' => $credenciales['password'],
            'codEmp' => $credenciales['empresa']->codigo,
        ]);

        $loginResponse->assertOk();

        return [
            'Authorization' => 'Bearer '.$loginResponse->json('accessToken'),
        ];
    }
}
