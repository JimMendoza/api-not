<?php

namespace App\Http\Controllers\Api\App;

use App\Repositories\Notifications\RealNotificationRepository;
use App\Services\Auth\AuthenticatedAppUser;
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

        return $this->ok($this->realNotifications->listVisibleForUser($usuario));
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
