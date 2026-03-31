<?php

namespace App\Modules\Notificaciones\Controllers\Api;

use App\Modules\Auth\Support\AuthenticatedAppUser;
use App\Modules\Notificaciones\Repositories\RealNotificationRepository;
use App\Modules\Notificaciones\Resources\NotificationResource;
use App\Support\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class NotificacionController extends ApiController
{
    protected RealNotificationRepository $realNotifications;

    public function __construct(RealNotificationRepository $realNotifications)
    {
        $this->realNotifications = $realNotifications;
    }

    public function index(Request $request)
    {
        /** @var AuthenticatedAppUser $usuario */
        $usuario = $request->user();

        $notificaciones = $this->realNotifications->listVisibleForUser($usuario);

        return $this->ok(
            NotificationResource::collection(collect($notificaciones))->resolve($request)
        );
    }

    public function resumen(Request $request)
    {
        /** @var AuthenticatedAppUser $usuario */
        $usuario = $request->user();

        return $this->ok([
            'noLeidas' => $this->realNotifications->unreadCountForUser($usuario),
        ]);
    }

    public function marcarLeida(Request $request, $id)
    {
        /** @var AuthenticatedAppUser $usuario */
        $usuario = $request->user();

        if (! $this->realNotifications->markReadForUser($usuario, (int) $id)) {
            return $this->error('Notificación no encontrada.', 404);
        }

        return $this->ok([
            'mensaje' => 'Notificación marcada como leída.',
        ]);
    }
}