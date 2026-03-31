<?php

namespace App\Modules\Integracion\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Repositories\RealIdentityRepository;
use App\Modules\Integracion\Requests\NotificacionEventoRequest;
use App\Modules\Integracion\Services\NotificationEventAttributesFactory;
use App\Modules\Notificaciones\Repositories\RealNotificationRepository;
use App\Modules\Notificaciones\Services\PushNotificationService;
use Illuminate\Support\Facades\Log;

class NotificacionEventoController extends Controller
{
    public function __invoke(
        NotificacionEventoRequest $request,
        PushNotificationService $pushNotificationService,
        RealIdentityRepository $realIdentityRepository,
        RealNotificationRepository $realNotifications,
        NotificationEventAttributesFactory $attributesFactory
    ) {
        $data = $request->validated();

        $this->log('info', 'integracion.evento_recibido', [
            'tramiteId' => (int) $data['tramiteId'],
            'evento' => $data['evento'],
            'payloadKeys' => array_keys($data['payload'] ?? []),
        ]);

        $tramite = $realNotifications->findTramiteById((int) $data['tramiteId']);

        if (! $tramite || ! $tramite['administradoId'] || ! $tramite['empresaCodigo']) {
            $this->log('warning', 'integracion.tramite_no_encontrado', [
                'tramiteId' => (int) $data['tramiteId'],
                'evento' => $data['evento'],
            ]);

            return response()->json([
                'mensaje' => 'Trámite no encontrado.',
            ], 404);
        }

        $usuario = $realIdentityRepository->findUserForLogin(
            $tramite['empresaCodigo'],
            $tramite['administradoId']
        );

        if (! $usuario) {
            $this->log('warning', 'integracion.usuario_no_encontrado', [
                'tramiteId' => (int) $data['tramiteId'],
                'evento' => $data['evento'],
                'administradoId' => $tramite['administradoId'],
                'empresaCodigo' => $tramite['empresaCodigo'],
            ]);

            return response()->json([
                'mensaje' => 'Trámite no encontrado.',
            ], 404);
        }

        $this->log('info', 'integracion.contexto_resuelto', [
            'tramiteId' => (int) $tramite['id'],
            'evento' => $data['evento'],
            'usuarioId' => $usuario->id,
            'empresaCodigo' => $usuario->empresaCodigo,
        ]);

        $resultado = $pushNotificationService->createInboxAndDispatch(
            $usuario,
            $tramite,
            $attributesFactory->make(
                $data['evento'],
                $data['payload'] ?? [],
                $tramite['codigo'] ?? null
            )
        );

        return response()->json([
            'mensaje' => 'Evento de notificación procesado correctamente.',
            'notificacion' => $resultado['notificacion'],
            'push' => $resultado['push'],
        ]);
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        Log::channel((string) config('mobile.log_channel', config('logging.default')))
            ->{$level}($message, $context);
    }
}