<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
        return 'app_mobile.usuario_tokens';
    }

    protected function tableExists(string $qualifiedTable): bool
    {
        [$schema, $table] = explode('.', $qualifiedTable, 2);

        $row = DB::connection($this->connectionName())->selectOne(
            'select 1 from information_schema.tables where table_schema = ? and table_name = ? limit 1',
            [$schema, $table]
        );

        return (bool) $row;
    }

    protected function columnExists(string $qualifiedTable, string $column): bool
    {
        [$schema, $table] = explode('.', $qualifiedTable, 2);

        $row = DB::connection($this->connectionName())->selectOne(
            'select 1 from information_schema.columns where table_schema = ? and table_name = ? and column_name = ? limit 1',
            [$schema, $table, $column]
        );

        return (bool) $row;
    }
}
