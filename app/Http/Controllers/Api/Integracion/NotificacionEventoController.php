<?php

namespace App\Http\Controllers\Api\Integracion;

use App\Http\Controllers\Controller;
use App\Repositories\Identity\RealIdentityRepository;
use App\Repositories\Notifications\RealNotificationRepository;
use App\Services\Push\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NotificacionEventoController extends Controller
{
    const EVENTOS_SOPORTADOS = [
        'tramite_registrado',
        'tramite_derivado',
        'cambio_estado',
        'movimiento_hoja_ruta',
    ];

    public function __invoke(
        Request $request,
        PushNotificationService $pushNotificationService,
        RealIdentityRepository $realIdentityRepository,
        RealNotificationRepository $realNotifications
    ) {
        $validator = Validator::make($this->validationData($request), [
            'tramiteId' => ['required', 'integer'],
            'evento' => ['required', 'string', Rule::in(self::EVENTOS_SOPORTADOS)],
            'payload' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'mensaje' => 'Datos inválidos.',
                'errores' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

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
            $this->notificationAttributes(
                $tramite['codigo'] ?? null,
                $data['evento'],
                $data['payload'] ?? []
            )
        );

        return response()->json([
            'mensaje' => 'Evento de notificación procesado correctamente.',
            'notificacion' => $resultado['notificacion'],
            'push' => $resultado['push'],
        ]);
    }

    protected function validationData(Request $request): array
    {
        return [
            'tramiteId' => $request->input('tramiteId', $request->input('tramite_id')),
            'evento' => $request->input('evento'),
            'payload' => $request->input('payload', []),
        ];
    }

    protected function notificationAttributes($codigoTramite, string $evento, array $payload): array
    {
        $codigo = is_string($codigoTramite) && trim($codigoTramite) !== ''
            ? trim($codigoTramite)
            : 'TRM-SIN-CODIGO';

        switch ($evento) {
            case 'tramite_registrado':
                $tipo = 'tramite_registrado';
                $titulo = 'Trámite registrado';
                $mensaje = 'Se registró el trámite '.$codigo.'.';
                break;

            case 'tramite_derivado':
                $tipo = 'tramite_derivado';
                $titulo = 'Trámite derivado';
                $destino = $this->payloadText($payload, 'destino');
                $mensaje = $destino
                    ? 'El trámite '.$codigo.' fue derivado a '.$destino.'.'
                    : 'El trámite '.$codigo.' fue derivado.';
                break;

            case 'cambio_estado':
                $tipo = 'estado';
                $titulo = 'Cambio de estado';
                $estado = $this->payloadText($payload, 'estado');
                $mensaje = $estado
                    ? 'El trámite '.$codigo.' cambió a estado '.$estado.'.'
                    : 'El trámite '.$codigo.' cambió de estado.';
                break;

            case 'movimiento_hoja_ruta':
            default:
                $tipo = 'movimiento_hoja_ruta';
                $titulo = 'Movimiento en hoja de ruta';
                $detalle = $this->payloadText($payload, 'detalle');
                $mensaje = $detalle
                    ? 'Nuevo movimiento en '.$codigo.': '.$detalle.'.'
                    : 'Se registró un nuevo movimiento en la hoja de ruta de '.$codigo.'.';
                break;
        }

        return [
            'evento' => $evento,
            'tipo' => $tipo,
            'titulo' => $this->payloadText($payload, 'titulo', $titulo),
            'mensaje' => $this->payloadText($payload, 'mensaje', $mensaje),
        ];
    }

    protected function payloadText(array $payload, string $key, ?string $default = null): ?string
    {
        $value = $payload[$key] ?? null;

        if (! is_string($value)) {
            return $default;
        }

        $value = trim($value);

        return $value !== '' ? $value : $default;
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        Log::channel((string) config('mobile.log_channel', config('logging.default')))
            ->{$level}($message, $context);
    }
}
