<?php

namespace App\Repositories\Notifications;

use App\Services\Auth\AuthenticatedAppUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RealNotificationRepository
{
    public function hasActiveFollowForUser(AuthenticatedAppUser $usuario, int $tramiteId): bool
    {
        return $this->mobileTable('tramite_seguimientos')
            ->where('usuario_id', $usuario->id)
            ->where('tramite_id', $tramiteId)
            ->where('activo', true)
            ->exists();
    }

    public function listVisibleForUser(AuthenticatedAppUser $usuario): array
    {
        return collect($this->connection()->select(
            $this->visibleNotificationsSql().$this->orderBySql(),
            $this->visibleBindings($usuario)
        ))->map(function ($row) {
            return $this->payloadFromRow($row);
        })->values()->all();
    }

    public function unreadCountForUser(AuthenticatedAppUser $usuario): int
    {
        $row = $this->connection()->selectOne(
            'select count(*) as total from ('.$this->visibleNotificationsSql().' and n.leida = false) x',
            $this->visibleBindings($usuario)
        );

        return (int) ($row->total ?? 0);
    }

    public function markReadForUser(AuthenticatedAppUser $usuario, int $notificationId): bool
    {
        $visibleRow = $this->connection()->selectOne(
            $this->visibleNotificationsSql().' and n.id = ? limit 1',
            array_merge($this->visibleBindings($usuario), [$notificationId])
        );

        if (! $visibleRow) {
            return false;
        }

        $this->mobileTable('notificaciones')
            ->where('id', $notificationId)
            ->update([
                'leida' => true,
                'updated_at' => now(),
            ]);

        return true;
    }

    public function createForUser(AuthenticatedAppUser $usuario, array $tramite, array $attributes = [])
    {
        $id = $this->mobileTable('notificaciones')->insertGetId([
            'usuario_id' => $usuario->id,
            'tramite_id' => (int) $tramite['id'],
            'titulo' => $attributes['titulo'] ?? 'Nueva notificacion',
            'mensaje' => $attributes['mensaje'] ?? 'Push generado desde backend.',
            'tipo' => $attributes['tipo'] ?? 'evento',
            'leida' => false,
            'fecha_hora' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->findById((int) $id);
    }

    public function findById(int $notificationId)
    {
        return $this->connection()->selectOne(
            'select '
            .'n.id, n.usuario_id, n.tramite_id, n.titulo, n.mensaje, n.tipo, n.leida, n.fecha_hora, '
            .'r."NUMERO_DOCUMENTO" as numero_documento, '
            .'r."NUMERO_EMISION" as numero_emision, '
            .'r."NRO_EXPEDIENTE" as nro_expediente '
            .'from '.$this->mobileTableName('notificaciones').' n '
            .'left join '.$this->tramiteTableName('REMITO').' r on r."ID" = n.tramite_id '
            .'where n.id = ? '
            .'limit 1',
            [$notificationId]
        );
    }

    public function findTramiteById(int $tramiteId): ?array
    {
        $row = $this->connection()->selectOne(
            'select '
            .'r."ID" as id, '
            .'trim(r."ADMINISTRADO_ID") as administrado_id, '
            .'trim(r."COD_EMP") as empresa_codigo, '
            .'r."NUMERO_DOCUMENTO" as numero_documento, '
            .'r."NUMERO_EMISION" as numero_emision, '
            .'r."NRO_EXPEDIENTE" as nro_expediente, '
            .'r."ASUNTO" as asunto, '
            .'r."OBSERVACION" as observacion, '
            .'coalesce(r."FECHA_EMISION", r."FECHA", r."CREATED_AT") as fecha_referencia, '
            .'e."DESCRIPCION_USER" as estado_descripcion_user, '
            .'e."DESCRIPCION" as estado_descripcion, '
            .'e."DESCRIPCION_MP" as estado_descripcion_mp '
            .'from '.$this->tramiteTableName('REMITO').' r '
            .'left join '.$this->tramiteTableName('ESTADO').' e on e."ID" = r."ESTADO_ID" '
            .'where r."DELETED_AT" is null '
                .'and r."ID" = ? '
            .'limit 1',
            [$tramiteId]
        );

        if (! $row) {
            return null;
        }

        $codigo = $this->codigoFromRow($row);

        return [
            'id' => (int) $row->id,
            'administradoId' => $this->normalizeText($row->administrado_id),
            'empresaCodigo' => $this->normalizeText($row->empresa_codigo),
            'codigo' => $codigo,
            'titulo' => $this->firstFilled([$row->asunto, $codigo]),
            'descripcion' => $this->firstFilled([$row->observacion, $row->asunto, $codigo]),
            'fecha' => $this->formatDate($row->fecha_referencia ?? null),
            'estadoActual' => $this->firstFilled([
                $row->estado_descripcion_user,
                $row->estado_descripcion,
                $row->estado_descripcion_mp,
                'Sin estado',
            ]),
        ];
    }

    public function payloadFromRow($row): array
    {
        return [
            'id' => (int) $row->id,
            'tramiteId' => (int) $row->tramite_id,
            'codigoTramite' => $this->codigoFromRow($row),
            'titulo' => (string) $row->titulo,
            'mensaje' => (string) $row->mensaje,
            'tipo' => (string) $row->tipo,
            'leida' => (bool) $row->leida,
            'fechaHora' => $this->formatDateTime($row->fecha_hora),
        ];
    }

    protected function visibleNotificationsSql(): string
    {
        return 'select '
            .'n.id, n.usuario_id, n.tramite_id, n.titulo, n.mensaje, n.tipo, n.leida, n.fecha_hora, '
            .'r."NUMERO_DOCUMENTO" as numero_documento, '
            .'r."NUMERO_EMISION" as numero_emision, '
            .'r."NRO_EXPEDIENTE" as nro_expediente '
            .'from '.$this->mobileTableName('notificaciones').' n '
            .'inner join '.$this->tramiteTableName('REMITO').' r on r."ID" = n.tramite_id '
            .'where n.usuario_id = ? '
                .'and r."DELETED_AT" is null '
                .'and trim(r."ADMINISTRADO_ID") = ? '
                .'and trim(r."COD_EMP") = ?';
    }

    protected function orderBySql(): string
    {
        return ' order by n.leida asc, n.fecha_hora desc, n.id desc';
    }

    protected function visibleBindings(AuthenticatedAppUser $usuario): array
    {
        return [
            $usuario->id,
            trim((string) $usuario->codUsuario),
            trim((string) $usuario->empresaCodigo),
        ];
    }

    protected function codigoFromRow($row): ?string
    {
        return $this->firstFilled([
            $row->numero_documento ?? null,
            $row->numero_emision ?? null,
            $row->nro_expediente ?? null,
            isset($row->tramite_id) ? (string) $row->tramite_id : (isset($row->id) ? (string) $row->id : null),
        ]);
    }

    protected function formatDateTime($value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i');
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
