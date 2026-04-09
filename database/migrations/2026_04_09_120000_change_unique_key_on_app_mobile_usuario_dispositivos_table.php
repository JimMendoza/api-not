<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangeUniqueKeyOnAppMobileUsuarioDispositivosTable extends Migration
{
    public function up()
    {
        $qualifiedTable = 'app_mobile.usuario_dispositivos';
        $connection = DB::connection(config('mobile.connection'));

        foreach ($this->singleColumnDeviceUniqueConstraints() as $constraintName) {
            $connection->statement(sprintf(
                'alter table app_mobile.usuario_dispositivos drop constraint if exists %s',
                $this->quoteIdentifier($constraintName)
            ));
        }

        foreach ($this->singleColumnDeviceUniqueIndexes() as $indexName) {
            $connection->statement(sprintf(
                'drop index if exists app_mobile.%s',
                $this->quoteIdentifier($indexName)
            ));
        }

        if (! $this->indexExists($qualifiedTable, 'app_mobile_usuario_dispositivos_usuario_device_unique')) {
            $connection->statement(
                'create unique index app_mobile_usuario_dispositivos_usuario_device_unique ' .
                'on app_mobile.usuario_dispositivos (usuario_id, device_id)'
            );
        }
    }

    public function down()
    {
        $qualifiedTable = 'app_mobile.usuario_dispositivos';
        $connection = DB::connection(config('mobile.connection'));

        if ($this->indexExists($qualifiedTable, 'app_mobile_usuario_dispositivos_usuario_device_unique')) {
            $connection->statement(
                'drop index if exists app_mobile.app_mobile_usuario_dispositivos_usuario_device_unique'
            );
        }

        if (! $this->indexExists($qualifiedTable, 'app_mobile_usuario_dispositivos_device_id_unique')) {
            $connection->statement(
                'create unique index app_mobile_usuario_dispositivos_device_id_unique ' .
                'on app_mobile.usuario_dispositivos (device_id)'
            );
        }
    }

    protected function singleColumnDeviceUniqueIndexes()
    {
        $rows = DB::connection(config('mobile.connection'))->select(
            'select i.relname as indexname
             from pg_class t
             inner join pg_namespace ns on ns.oid = t.relnamespace
             inner join pg_index ix on ix.indrelid = t.oid
             inner join pg_class i on i.oid = ix.indexrelid
             inner join pg_attribute a on a.attrelid = t.oid and a.attnum = any(ix.indkey)
             where ns.nspname = ? and t.relname = ? and ix.indisunique = true
             group by i.relname
             having count(*) = 1 and max(a.attname) = ?',
            ['app_mobile', 'usuario_dispositivos', 'device_id']
        );

        return array_map(function ($row) {
            return $row->indexname;
        }, $rows);
    }

    protected function singleColumnDeviceUniqueConstraints()
    {
        $rows = DB::connection(config('mobile.connection'))->select(
            'select c.conname as constraint_name
             from pg_constraint c
             inner join pg_class t on t.oid = c.conrelid
             inner join pg_namespace ns on ns.oid = t.relnamespace
             inner join unnest(c.conkey) with ordinality as cols(attnum, ord) on true
             inner join pg_attribute a on a.attrelid = t.oid and a.attnum = cols.attnum
             where ns.nspname = ? and t.relname = ? and c.contype = ?
             group by c.conname
             having count(*) = 1 and max(a.attname) = ?',
            ['app_mobile', 'usuario_dispositivos', 'u', 'device_id']
        );

        return array_map(function ($row) {
            return $row->constraint_name;
        }, $rows);
    }

    protected function indexExists($qualifiedTable, $indexName)
    {
        [$schema, $table] = explode('.', $qualifiedTable, 2);

        $row = DB::connection(config('mobile.connection'))->selectOne(
            'select 1 from pg_indexes where schemaname = ? and tablename = ? and indexname = ? limit 1',
            [$schema, $table, $indexName]
        );

        return (bool) $row;
    }

    protected function quoteIdentifier($value)
    {
        return '"'.str_replace('"', '""', $value).'"';
    }
}
