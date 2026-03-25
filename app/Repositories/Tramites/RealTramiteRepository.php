<?php

namespace App\Repositories\Tramites;

use App\Services\Auth\AuthenticatedAppUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RealTramiteRepository
{
    public function listVisibleForUser(AuthenticatedAppUser $usuario): array
    {
        return collect($this->connection()->select(
            $this->baseSql().$this->orderBySql(),
            $this->bindingsForUser($usuario)
        ))->map(function ($row) {
            return $this->mapRow($row);
        })->values()->all();
    }

    public function findVisibleForUser(AuthenticatedAppUser $usuario, $tramiteId): ?array
    {
        $row = $this->connection()->selectOne(
            $this->baseSql().' and r."ID" = ?'.$this->orderBySql().' limit 1',
            array_merge($this->bindingsForUser($usuario), [(int) $tramiteId])
        );

        return $row ? $this->mapRow($row) : null;
    }

    public function markFollowed(AuthenticatedAppUser $usuario, int $tramiteId): void
    {
        $query = $this->mobileTable('tramite_seguimientos')
            ->where('usuario_id', $usuario->id)
            ->where('tramite_id', $tramiteId);

        $existing = $query->first();

        if ($existing) {
            $query->update([
                'activo' => true,
                'updated_at' => now(),
            ]);

            return;
        }

        $this->mobileTable('tramite_seguimientos')->insert([
            'usuario_id' => $usuario->id,
            'tramite_id' => $tramiteId,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function unmarkFollowed(AuthenticatedAppUser $usuario, int $tramiteId): void
    {
        $this->mobileTable('tramite_seguimientos')
            ->where('usuario_id', $usuario->id)
            ->where('tramite_id', $tramiteId)
            ->update([
                'activo' => false,
                'updated_at' => now(),
            ]);
    }

    protected function baseSql(): string
    {
        return 'select '
            .'r."ID" as id, '
            .'r."NUMERO_DOCUMENTO" as numero_documento, '
            .'r."NUMERO_EMISION" as numero_emision, '
            .'r."NRO_EXPEDIENTE" as nro_expediente, '
            .'r."ASUNTO" as asunto, '
            .'r."OBSERVACION" as observacion, '
            .'coalesce(r."FECHA_EMISION", r."FECHA", r."CREATED_AT") as fecha_referencia, '
            .'e."DESCRIPCION_USER" as estado_descripcion_user, '
            .'e."DESCRIPCION" as estado_descripcion, '
            .'e."DESCRIPCION_MP" as estado_descripcion_mp, '
            .'case when exists ('
                .'select 1 from '.$this->mobileTableName('tramite_seguimientos').' ts '
                .'where ts.usuario_id = ? '
                    .'and ts.tramite_id = r."ID" '
                    .'and ts.activo = true'
            .') then 1 else 0 end as siguiendo, '
            .'coalesce(('
                .'select count(*) from '.$this->mobileTableName('notificaciones').' n '
                .'where n.usuario_id = ? '
                    .'and n.tramite_id = r."ID" '
                    .'and n.leida = false'
            .'), 0) as notificaciones_no_leidas '
            .'from '.$this->tramiteTableName('REMITO').' r '
            .'left join '.$this->tramiteTableName('ESTADO').' e '
                .'on e."ID" = r."ESTADO_ID" '
            .'where r."DELETED_AT" is null '
                .'and trim(r."ADMINISTRADO_ID") = ? '
                .'and trim(r."COD_EMP") = ?';
    }

    protected function orderBySql(): string
    {
        return ' order by coalesce(r."FECHA_EMISION", r."FECHA", r."CREATED_AT") desc, r."ID" desc';
    }

    protected function bindingsForUser(AuthenticatedAppUser $usuario): array
    {
        return [
            $usuario->id,
            $usuario->id,
            trim((string) $usuario->codUsuario),
            trim((string) $usuario->empresaCodigo),
        ];
    }

    protected function mapRow($row): array
    {
        $codigo = $this->firstFilled([
            $row->numero_documento ?? null,
            $row->numero_emision ?? null,
            $row->nro_expediente ?? null,
            (string) $row->id,
        ]);

        return [
            'id' => (int) $row->id,
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
            'siguiendo' => (int) ($row->siguiendo ?? 0) > 0,
            'notificacionesNoLeidas' => (int) ($row->notificaciones_no_leidas ?? 0),
        ];
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

    protected function normalizeText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }

    protected function mobileTable(string $table)
    {
        return DB::connection($this->connectionName())
            ->table($this->mobileTableName($table));
    }

    protected function mobileTableName(string $table): string
    {
        if ($this->connection()->getDriverName() === 'pgsql') {
            return 'app_mobile.'.$table;
        }

        return 'app_mobile_'.$table;
    }

    protected function tramiteTableName(string $table): string
    {
        if ($this->connection()->getDriverName() === 'pgsql') {
            return 'virtual."'.$table.'"';
        }

        return '"virtual_'.$table.'"';
    }

    protected function connection()
    {
        return DB::connection($this->connectionName());
    }

    protected function connectionName(): string
    {
        return (string) config('mobile.connection', config('database.default'));
    }
}
