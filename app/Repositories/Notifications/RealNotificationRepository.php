<?php

namespace App\Repositories\Notifications;

use App\Repositories\Support\LegacySqlRepository;
use App\Repositories\Support\LegacyTramiteSql;
use App\Services\Auth\AuthenticatedAppUser;
use Illuminate\Support\Carbon;

class RealNotificationRepository extends LegacySqlRepository
{
    protected LegacyTramiteSql $legacyTramiteSql;

    public function __construct(LegacyTramiteSql $legacyTramiteSql)
    {
        $this->legacyTramiteSql = $legacyTramiteSql;
    }

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
            .$this->legacyTramiteSql->codeSelect('r').' '
            .'from '.$this->mobileTableName('notificaciones').' n '
            .'left join '.$this->virtualTableName('REMITO').' r on r."ID" = n.tramite_id '
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
            .$this->legacyTramiteSql->summarySelect('r', 'e').' '
            .$this->legacyTramiteSql->fromWithEstado('r', 'e').' '
            .'where '.$this->legacyTramiteSql->nonDeletedPredicate('r').' '
                .'and r."ID" = ? '
            .'limit 1',
            [$tramiteId]
        );

        if (! $row) {
            return null;
        }

        return array_merge([
            'id' => (int) $row->id,
            'administradoId' => $this->legacyTramiteSql->normalizeText($row->administrado_id),
            'empresaCodigo' => $this->legacyTramiteSql->normalizeText($row->empresa_codigo),
        ], $this->legacyTramiteSql->summaryPayloadFromRow($row));
    }

    public function payloadFromRow($row): array
    {
        return [
            'id' => (int) $row->id,
            'tramiteId' => (int) $row->tramite_id,
            'codigoTramite' => $this->legacyTramiteSql->codigoFromRow($row),
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
            .$this->legacyTramiteSql->codeSelect('r').' '
            .'from '.$this->mobileTableName('notificaciones').' n '
            .'inner join '.$this->virtualTableName('REMITO').' r on r."ID" = n.tramite_id '
            .'where n.usuario_id = ? '
                .'and '.$this->legacyTramiteSql->visiblePredicate('r');
    }

    protected function orderBySql(): string
    {
        return ' order by n.leida asc, n.fecha_hora desc, n.id desc';
    }

    protected function visibleBindings(AuthenticatedAppUser $usuario): array
    {
        return array_merge([
            $usuario->id,
        ], $this->legacyTramiteSql->visibilityBindings(
            (string) $usuario->codUsuario,
            (string) $usuario->empresaCodigo
        ));
    }

    protected function formatDateTime($value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i');
    }
}
