<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmpresaCodigoToAppMobileUsuarioTokensTable extends Migration
{
    public function up()
    {
        $tableName = $this->tableName();

        if (! $this->columnExists($tableName, 'empresa_codigo')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('empresa_codigo', 20)->nullable()->after('usuario_id');
            });
        }

        if (! $this->indexExists($tableName, 'app_mobile_usuario_tokens_context_index')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index(
                    ['usuario_id', 'empresa_codigo', 'revoked_at'],
                    'app_mobile_usuario_tokens_context_index'
                );
            });
        }
    }

    public function down()
    {
        $tableName = $this->tableName();

        if ($this->indexExists($tableName, 'app_mobile_usuario_tokens_context_index')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex('app_mobile_usuario_tokens_context_index');
            });
        }

        if ($this->columnExists($tableName, 'empresa_codigo')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('empresa_codigo');
            });
        }
    }

    protected function tableName()
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            return 'app_mobile.usuario_tokens';
        }

        return 'app_mobile_usuario_tokens';
    }

    protected function columnExists($qualifiedTable, $column)
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return Schema::hasColumn($qualifiedTable, $column);
        }

        [$schema, $table] = explode('.', $qualifiedTable, 2);

        $row = Schema::getConnection()->selectOne(
            'select 1 from information_schema.columns where table_schema = ? and table_name = ? and column_name = ? limit 1',
            [$schema, $table, $column]
        );

        return (bool) $row;
    }

    protected function indexExists($qualifiedTable, $indexName)
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return false;
        }

        [$schema, $table] = explode('.', $qualifiedTable, 2);

        $row = Schema::getConnection()->selectOne(
            'select 1 from pg_indexes where schemaname = ? and tablename = ? and indexname = ? limit 1',
            [$schema, $table, $indexName]
        );

        return (bool) $row;
    }
}
