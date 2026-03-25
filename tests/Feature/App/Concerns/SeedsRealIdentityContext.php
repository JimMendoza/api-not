<?php

namespace Tests\Feature\App\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

trait SeedsRealIdentityContext
{
    protected function enableRealIdentityMode()
    {
        config([
            'mobile.connection' => config('database.default'),
        ]);
    }

    protected function loginRealIdentityUser(array $credentials = [])
    {
        $this->seedRealIdentityContext($credentials);

        $response = $this->postJson('/api/app/login', [
            'username' => $credentials['username'] ?? 'movil.user',
            'password' => $credentials['password'] ?? 'Secret123!',
            'codEmp' => $credentials['codEmp'] ?? 'EMP-001',
        ]);

        $response->assertOk();

        return $response->json('accessToken');
    }

    protected function seedRealIdentityContext(array $overrides = [])
    {
        $empresaCodigo = $overrides['codEmp'] ?? 'EMP-001';
        $username = $overrides['username'] ?? 'movil.user';
        $password = $overrides['password'] ?? 'Secret123!';
        $usuarioId = $overrides['usuarioId'] ?? 101;
        $empresaNombre = $overrides['empresaNombre'] ?? 'Empresa Real Demo';
        $empresaImagen = $overrides['empresaImagen'] ?? 'logo-real.png';
        $fullName = $overrides['fullName'] ?? [
            'nombres' => 'Usuario',
            'apellidoPaterno' => 'Movil',
            'apellidoMaterno' => 'Demo',
        ];

        DB::table('maestro_EMPRESA')->insert([
            'COD_EMP' => $empresaCodigo,
            'DES_EMP' => $empresaNombre,
            'IMAGEN' => $empresaImagen,
            'IND_ESTADO' => 'A',
        ]);

        DB::table('seguridad_USUARIO')->insert([
            'ID' => $usuarioId,
            'COD_USUARIO' => $username,
            'NOM_USUARIO' => $overrides['nomUsuario'] ?? 'Usuario Movil',
            'NOMBRES' => $fullName['nombres'],
            'DES_APELLP' => $fullName['apellidoPaterno'],
            'DES_APELLM' => $fullName['apellidoMaterno'],
            'USU_CLAVE' => Hash::make($password),
            'IND_ESTADO' => 'A',
        ]);

        DB::table('seguridad_USUARIO_EMPRESA')->insert([
            'COD_EMP' => $empresaCodigo,
            'COD_USUARIO' => $username,
            'IND_ESTADO' => 'A',
        ]);

        DB::table('seguridad_SISTEMA')->insert([
            [
                'COD_SISTEMA' => '014',
                'DES_SISTEMA' => 'Mesa de Partes Virtual',
                'IND_ESTADO' => 'A',
            ],
            [
                'COD_SISTEMA' => '009',
                'DES_SISTEMA' => 'Notificaciones',
                'IND_ESTADO' => 'A',
            ],
        ]);

        DB::table('seguridad_USUARIO_SISTEMA')->insert([
            [
                'COD_EMP' => $empresaCodigo,
                'COD_USUARIO' => $username,
                'COD_SISTEMA' => '014',
                'IND_ESTADO' => 'A',
            ],
            [
                'COD_EMP' => $empresaCodigo,
                'COD_USUARIO' => $username,
                'COD_SISTEMA' => '009',
                'IND_ESTADO' => 'A',
            ],
        ]);
    }
}
