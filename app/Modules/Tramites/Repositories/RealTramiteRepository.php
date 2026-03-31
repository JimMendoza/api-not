<?php

namespace App\Modules\Tramites\Repositories;

use App\Support\Repositories\LegacySqlRepository;
use App\Support\Repositories\LegacyTramiteSql;
use App\Modules\Auth\Support\AuthenticatedAppUser;

class RealTramiteRepository extends LegacySqlRepository
{
    protected LegacyTramiteSql $legacyTramiteSql;

    public function __construct(LegacyTramiteSql $legacyTramiteSql)
    {
        $this->legacyTramiteSql = $legacyTramiteSql;
    }

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
            .$this->legacyTramiteSql->summarySelect('r', 'e').', '
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
            .$this->legacyTramiteSql->fromWithEstado('r', 'e').' '
            .'where '.$this->legacyTramiteSql->visiblePredicate('r');
    }

    protected function orderBySql(): string
    {
        return ' order by coalesce(r."FECHA_EMISION", r."FECHA", r."CREATED_AT") desc, r."ID" desc';
    }

    protected function bindingsForUser(AuthenticatedAppUser $usuario): array
    {
        return array_merge([
            $usuario->id,
            $usuario->id,
        ], $this->legacyTramiteSql->visibilityBindings(
            (string) $usuario->codUsuario,
            (string) $usuario->empresaCodigo
        ));
    }

    protected function mapRow($row): array
    {
        return array_merge([
            'id' => (int) $row->id,
        ], $this->legacyTramiteSql->summaryPayloadFromRow($row), [
            'siguiendo' => (int) ($row->siguiendo ?? 0) > 0,
            'notificacionesNoLeidas' => (int) ($row->notificaciones_no_leidas ?? 0),
        ]);
    }
}
