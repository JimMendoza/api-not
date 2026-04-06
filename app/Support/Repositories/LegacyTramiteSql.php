<?php

namespace App\Support\Repositories;

use Illuminate\Support\Carbon;

class LegacyTramiteSql extends LegacySqlRepository
{
    public function summarySelect(string $tramiteAlias = 'r', string $estadoAlias = 'e'): string
    {
        return $this->codeSelect($tramiteAlias).', '
            .$tramiteAlias.'."ASUNTO" as asunto, '
            .$tramiteAlias.'."OBSERVACION" as observacion, '
            .'coalesce('.$tramiteAlias.'."FECHA_EMISION", '.$tramiteAlias.'."FECHA", '.$tramiteAlias.'."CREATED_AT") as fecha_referencia, '
            .$estadoAlias.'."DESCRIPCION_USER" as estado_descripcion_user, '
            .$estadoAlias.'."DESCRIPCION" as estado_descripcion, '
            .$estadoAlias.'."DESCRIPCION_MP" as estado_descripcion_mp';
    }

    public function codeSelect(string $tramiteAlias = 'r'): string
    {
        return $tramiteAlias.'."NUMERO_DOCUMENTO" as numero_documento, '
            .$tramiteAlias.'."NUMERO_EMISION" as numero_emision, '
            .$tramiteAlias.'."NRO_EXPEDIENTE" as nro_expediente';
    }

    public function fromWithEstado(string $tramiteAlias = 'r', string $estadoAlias = 'e'): string
    {
        return 'from '.$this->virtualTableName('REMITO').' '.$tramiteAlias.' '
            .'left join '.$this->virtualTableName('ESTADO').' '.$estadoAlias.' '
                .'on '.$estadoAlias.'."ID" = '.$tramiteAlias.'."ESTADO_ID"';
    }

    public function visiblePredicate(string $tramiteAlias = 'r'): string
    {
        return $this->nonDeletedPredicate($tramiteAlias)
            .' and trim('.$tramiteAlias.'."ADMINISTRADO_ID") = ?'
            .' and trim('.$tramiteAlias.'."COD_EMP") = ?';
    }

    public function nonDeletedPredicate(string $tramiteAlias = 'r'): string
    {
        return $tramiteAlias.'."DELETED_AT" is null';
    }

    public function visibilityBindings(string $administradoId, string $empresaCodigo): array
    {
        return [
            $this->normalizeKey($administradoId),
            $this->normalizeKey($empresaCodigo),
        ];
    }

    public function summaryPayloadFromRow($row): array
    {
        $codigo = $this->codigoFromRow($row);

        return [
            'codigo' => $codigo,
            'titulo' => $this->firstFilled([
                $row->asunto ?? null,
                $codigo,
            ]),
            'descripcion' => $this->firstFilled([
                $row->observacion ?? null,
                $row->asunto ?? null,
                $codigo,
            ]),
            'fecha' => $this->formatDate($row->fecha_referencia ?? null),
            'estadoActual' => $this->firstFilled([
                $row->estado_descripcion_user ?? null,
                $row->estado_descripcion ?? null,
                $row->estado_descripcion_mp ?? null,
                'Sin estado',
            ]),
        ];
    }

    public function codigoFromRow($row): ?string
    {
        return $this->firstFilled([
            $row->numero_documento ?? null,
            $row->numero_emision ?? null,
            $row->nro_expediente ?? null,
            isset($row->tramite_id) ? (string) $row->tramite_id : (isset($row->id) ? (string) $row->id : null),
        ]);
    }

    public function normalizeText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }

    protected function formatDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    protected function firstFilled(array $values): ?string
    {
        foreach ($values as $value) {
            $normalized = $this->normalizeText($value);

            if ($normalized !== null && $normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }
}
