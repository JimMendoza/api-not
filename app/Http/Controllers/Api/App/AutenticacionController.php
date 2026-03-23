<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Requests\Api\App\LoginRequest;
use App\Models\Empresa;
use App\Models\UsuarioApp;
use App\Models\UsuarioAppDispositivo;
use App\Support\App\Auth\AccessTokenManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AutenticacionController extends ApiController
{
    public function login(LoginRequest $request, AccessTokenManager $accessTokenManager)
    {
        $empresa = Empresa::query()
            ->where('codigo', $request->input('codEmp'))
            ->where('activo', true)
            ->first();

        if (! $empresa) {
            return $this->error('Credenciales inválidas.', 401);
        }

        $usuario = UsuarioApp::query()
            ->where('empresa_id', $empresa->id)
            ->where('username', $request->input('username'))
            ->where('activo', true)
            ->first();

        if (! $usuario || ! Hash::check($request->input('password'), $usuario->password)) {
            return $this->error('Credenciales inválidas.', 401);
        }

        $issuedToken = $accessTokenManager->issue($usuario);

        return $this->ok([
            'accessToken' => $issuedToken['plainTextToken'],
            'tokenType' => config('app_mobile.token_type', 'Bearer'),
        ]);
    }

    public function me(Request $request)
    {
        /** @var \App\Models\UsuarioApp $usuario */
        $usuario = $request->user();
        $usuario->loadMissing('empresa');

        return $this->ok([
            'username' => $usuario->username,
            'fullName' => $usuario->full_name,
            'empresa' => [
                'codigo' => $usuario->empresa->codigo,
                // Alias transicional para clientes antiguos: usar `codigo` como canónico.
                'id' => $usuario->empresa->codigo,
                'nombre' => $usuario->empresa->nombre,
                'imagen' => $usuario->empresa->imagen,
            ],
            'permisos' => $this->appPermissions(),
            'session' => [
                'authenticated' => true,
                'tokenType' => config('app_mobile.token_type', 'Bearer'),
            ],
        ]);
    }

    public function logout(Request $request, AccessTokenManager $accessTokenManager)
    {
        $token = $request->attributes->get('appToken');

        if (! $token) {
            return $this->error('No autenticado.', 401);
        }

        /** @var UsuarioApp|null $usuario */
        $usuario = $request->user();
        $this->invalidatePushDevice($request, $usuario);

        $accessTokenManager->revoke($token);

        return $this->ok([
            'mensaje' => 'Sesión cerrada correctamente.',
        ]);
    }

    protected function invalidatePushDevice(Request $request, UsuarioApp $usuario = null)
    {
        if (! $usuario) {
            return;
        }

        $deviceId = $request->input('deviceId', $request->input('device_id', $request->header('X-App-Device-Id')));

        if (! $deviceId) {
            return;
        }

        UsuarioAppDispositivo::query()
            ->where('usuario_app_id', $usuario->id)
            ->where('device_id', $deviceId)
            ->update([
                'activo' => false,
                'invalidado_at' => now(),
            ]);
    }
}
