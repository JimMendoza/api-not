<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeviceIdToAppMobileUsuarioTokensTable extends Migration
{
    public function up()
    {
        $tableName = $this->tableName();

        if (! $this->columnExists($tableName, 'device_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('device_id', 191)->nullable()->after('empresa_codigo');
            });
        }

        if (! $this->indexExists($tableName, 'app_mobile_usuario_tokens_device_context_index')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index(
                    ['usuario_id', 'empresa_codigo', 'device_id', 'revoked_at'],
                    'app_mobile_usuario_tokens_device_context_index'
                );
            });
        }
    }

    public function down()
    {
        $tableName = $this->tableName();

        if ($this->indexExists($tableName, 'app_mobile_usuario_tokens_device_context_index')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex('app_mobile_usuario_tokens_device_context_index');
            });
        }

        if ($this->columnExists($tableName, 'device_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('device_id');
            });
        }
    }

    protected function tableName()
    {
        return 'app_mobile.usuario_tokens';
    }

    protected function columnExists($qualifiedTable, $column)
    {
        [$schema, $table] = explode('.', $qualifiedTable, 2);

        $row = Schema::getConnection()->selectOne(
            'select 1 from information_schema.columns where table_schema = ? and table_name = ? and column_name = ? limit 1',
            [$schema, $table, $column]
        );

        return (bool) $row;
    }

    protected function indexExists($qualifiedTable, $indexName)
    {
        [$schema, $table] = explode('.', $qualifiedTable, 2);

        $row = Schema::getConnection()->selectOne(
            'select 1 from pg_indexes where schemaname = ? and tablename = ? and indexname = ? limit 1',
            [$schema, $table, $indexName]
        );

        return (bool) $row;
    }
}
