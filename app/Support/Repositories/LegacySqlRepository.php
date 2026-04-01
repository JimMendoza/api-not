<?php

namespace App\Support\Repositories;

use Illuminate\Support\Facades\DB;

abstract class LegacySqlRepository
{
    protected function connection()
    {
        return DB::connection($this->connectionName());
    }

    protected function connectionName(): string
    {
        return (string) config('mobile.connection', config('database.default'));
    }

    protected function schemaTable(string $schema, string $table): string
    {
        return $schema.'."'.$table.'"';
    }

    protected function mobileTable(string $table)
    {
        return $this->connection()->table($this->mobileTableName($table));
    }

    protected function mobileTableName(string $table): string
    {
        return 'app_mobile.'.$table;
    }

    protected function virtualTableName(string $table): string
    {
        return $this->schemaTable('virtual', $table);
    }
}
