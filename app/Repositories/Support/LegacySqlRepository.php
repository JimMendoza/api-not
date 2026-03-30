<?php

namespace App\Repositories\Support;

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

    protected function isPgsql(): bool
    {
        return $this->connection()->getDriverName() === 'pgsql';
    }

    protected function schemaTable(string $schema, string $table): string
    {
        if ($this->isPgsql()) {
            return $schema.'."'.$table.'"';
        }

        return '"'.$schema.'_'.$table.'"';
    }

    protected function mobileTable(string $table)
    {
        return $this->connection()->table($this->mobileTableName($table));
    }

    protected function mobileTableName(string $table): string
    {
        if ($this->isPgsql()) {
            return 'app_mobile.'.$table;
        }

        return 'app_mobile_'.$table;
    }

    protected function virtualTableName(string $table): string
    {
        return $this->schemaTable('virtual', $table);
    }
}
