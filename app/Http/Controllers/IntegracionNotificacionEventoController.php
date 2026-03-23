<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Models\Tramite;
use App\Support\App\Push\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class IntegracionNotificacionEventoController extends Controller
{
    const EVENTOS_SOPORTADOS = [
        'tramite_registrado',
        'tramite_derivado',
        'cambio_estado',
        'movimiento_hoja_ruta',
    ];

    public function __invoke(Request $request, PushNotificationService $pushNotificationService)
    {
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

        $tramite = Tramite::query()
            ->with('usuarioApp')
            ->where('id', $data['tramiteId'])
            ->where('activo', true)
            ->first();

        if (! $tramite || ! $tramite->usuarioApp || ! $tramite->usuarioApp->activo) {
            return response()->json([
                'mensaje' => 'Trámite no encontrado.',
            ], 404);
        }

        $notificationAttributes = $this->notificationAttributes(
            $tramite,
            $data['evento'],
            $data['payload'] ?? []
        );

        $resultado = $pushNotificationService->createInboxAndSend(
            $tramite->usuarioApp,
            $tramite,
            $notificationAttributes
        );

        return response()->json([
            'mensaje' => 'Evento de notificación procesado correctamente.',
            'notificacion' => $this->notificacionPayload($resultado['notificacion']),
            'push' => $resultado['push'],
        ]);
    }

    protected function validationData(Request $request)
    {
        return [
            'tramiteId' => $request->input('tramiteId', $request->input('tramite_id')),
            'evento' => $request->input('evento'),
            'payload' => $request->input('payload', []),
        ];
    }

    protected function notificationAttributes(Tramite $tramite, $evento, array $payload)
    {
        $codigoTramite = (string) ($tramite->codigo ?: ('TRM-'.$tramite->id));

        switch ($evento) {
            case 'tramite_registrado':
                $tipo = 'tramite_registrado';
                $titulo = 'Trámite registrado';
                $mensaje = 'Se registró el trámite '.$codigoTramite.'.';
                break;

            case 'tramite_derivado':
                $tipo = 'tramite_derivado';
                $titulo = 'Trámite derivado';
                $destino = $this->payloadText($payload, 'destino');
                $mensaje = $destino
                    ? 'El trámite '.$codigoTramite.' fue derivado a '.$destino.'.'
                    : 'El trámite '.$codigoTramite.' fue derivado.';
                break;

            case 'cambio_estado':
                $tipo = 'estado';
                $titulo = 'Cambio de estado';
                $estado = $this->payloadText($payload, 'estado');
                $mensaje = $estado
                    ? 'El trámite '.$codigoTramite.' cambió a estado '.$estado.'.'
                    : 'El trámite '.$codigoTramite.' cambió de estado.';
                break;

            case 'movimiento_hoja_ruta':
            default:
                $tipo = 'movimiento_hoja_ruta';
                $titulo = 'Movimiento en hoja de ruta';
                $detalle = $this->payloadText($payload, 'detalle');
                $mensaje = $detalle
                    ? 'Nuevo movimiento en '.$codigoTramite.': '.$detalle.'.'
                    : 'Se registró un nuevo movimiento en la hoja de ruta de '.$codigoTramite.'.';
                break;
        }

        return [
            'tipo' => $tipo,
            'titulo' => $this->payloadText($payload, 'titulo', $titulo),
            'mensaje' => $this->payloadText($payload, 'mensaje', $mensaje),
        ];
    }

    protected function payloadText(array $payload, $key, $default = null)
    {
        $value = $payload[$key] ?? null;

        if (! is_string($value)) {
            return $default;
        }

        $value = trim($value);

        return $value !== '' ? $value : $default;
    }

    protected function notificacionPayload(Notificacion $notificacion)
    {
        return [
            'id' => $notificacion->id,
            'tramiteId' => (int) $notificacion->tramite_id,
            'codigoTramite' => optional($notificacion->tramite)->codigo,
            'titulo' => $notificacion->titulo,
            'mensaje' => $notificacion->mensaje,
            'tipo' => $notificacion->tipo,
            'leida' => (bool) $notificacion->leida,
            'fechaHora' => optional($notificacion->fecha_hora)->format('Y-m-d H:i'),
        ];
    }
}
