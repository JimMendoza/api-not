<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Requests\Api\App\LoginRequest;
use App\Repositories\Identity\RealIdentityRepository;
use App\Repositories\Notifications\RealPushDeviceRepository;
use App\Services\Auth\AccessTokenManager;
use App\Services\Auth\AuthenticatedAppUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AutenticacionController extends ApiController
{
    protected RealIdentityRepository $realIdentityRepository;
    protected RealPushDeviceRepository $realPushDeviceRepository;

    public function __construct(
        RealIdentityRepository $realIdentityRepository,
        RealPushDeviceRepository $realPushDeviceRepository
    ) {
        $this->realIdentityRepository = $realIdentityRepository;
        $this->realPushDeviceRepository = $realPushDeviceRepository;
    }

    public function login(LoginRequest $request, AccessTokenManager $accessTokenManager)
    {
        $usuario = $this->realIdentityRepository->findUserForLogin(
            $request->input('codEmp'),
            $request->input('username')
        );

        if (! $usuario || ! Hash::check($request->input('password'), $usuario->password)) {
            return $this->error('Credenciales inválidas.', 401);
        }

        $issuedToken = $accessTokenManager->issue($usuario);

        return $this->ok([
            'accessToken' => $issuedToken['plainTextToken'],
            'tokenType' => config('mobile.token_type', 'Bearer'),
        ]);
    }

    public function me(Request $request)
    {
        /** @var AuthenticatedAppUser $usuario */
        $usuario = $request->user();

        return $this->ok([
            'username' => $usuario->username,
            'fullName' => $usuario->fullName,
            'empresa' => [
                'codigo' => $usuario->empresaCodigo,
                'id' => $usuario->empresaCodigo,
                'nombre' => $usuario->empresaNombre,
                'imagen' => $usuario->empresaImagen,
            ],
            'permisos' => array_values($usuario->permisos ?? []),
            'session' => [
                'authenticated' => true,
                'tokenType' => config('mobile.token_type', 'Bearer'),
            ],
        ]);
    }

    public function logout(Request $request, AccessTokenManager $accessTokenManager)
    {
        $token = $request->attributes->get('appToken');

        if (! $token) {
            return $this->error('No autenticado.', 401);
        }

        $this->invalidatePushDevice($request, $request->user());
        $accessTokenManager->revoke($token);

        return $this->ok([
            'mensaje' => 'Sesión cerrada correctamente.',
        ]);
    }

    protected function invalidatePushDevice(Request $request, ?AuthenticatedAppUser $usuario): void
    {
        if (! $usuario) {
            return;
        }

        $deviceId = $request->input('deviceId', $request->input('device_id', $request->header('X-App-Device-Id')));

        if (! $deviceId) {
            return;
        }

        $this->realPushDeviceRepository->invalidateForUser($usuario, (string) $deviceId);
    }
}