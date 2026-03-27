<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillAppMobileUsuarioTokensExpiration extends Migration
{
    protected const TOKEN_TTL_DAYS = 30;

    public function up()
    {
        $tableName = $this->tableName();

        if (! $this->tableExists($tableName) || ! $this->columnExists($tableName, 'expires_at')) {
            return;
        }

        DB::connection($this->connectionName())
            ->table($tableName)
            ->whereNull('expires_at')
            ->update([
                'expires_at' => now()->addDays(self::TOKEN_TTL_DAYS),
                'updated_at' => now(),
            ]);
    }

    public function down()
    {
        // Data backfill irreversible by design.
    }

    protected function connectionName(): string
    {
        return (string) config('mobile.connection', config('database.default'));
    }

    protected function tableName(): string
    {
        if ($this->schema()->getConnection()->getDriverName() === 'pgsql') {
            return 'app_mobile.usuario_tokens';
        }

        return 'app_mobile_usuario_tokens';
    }

    protected function tableExists(string $qualifiedTable): bool
    {
        if ($this->schema()->getConnection()->getDriverName() !== 'pgsql') {
            return $this->schema()->hasTable($qualifiedTable);
        }

        [$schema, $table] = explode('.', $qualifiedTable, 2);

        $row = DB::connection($this->connectionName())->selectOne(
            'select 1 from information_schema.tables where table_schema = ? and table_name = ? limit 1',
            [$schema, $table]
        );

        return (bool) $row;
    }

    protected function columnExists(string $qualifiedTable, string $column): bool
    {
        if ($this->schema()->getConnection()->getDriverName() !== 'pgsql') {
            return $this->schema()->hasColumn($qualifiedTable, $column);
        }

        [$schema, $table] = explode('.', $qualifiedTable, 2);

        $row = DB::connection($this->connectionName())->selectOne(
            'select 1 from information_schema.columns where table_schema = ? and table_name = ? and column_name = ? limit 1',
            [$schema, $table, $column]
        );

        return (bool) $row;
    }

    protected function schema()
    {
        return Schema::connection($this->connectionName());
    }
}
