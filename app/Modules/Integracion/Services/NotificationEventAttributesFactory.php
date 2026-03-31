<?php

namespace App\Modules\Integracion\Services;

class NotificationEventAttributesFactory
{
    public function make(string $evento, array $payload, ?string $codigoTramite): array
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
}