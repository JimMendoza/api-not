<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MoveCacheTableToPublicSchema extends Migration
{
    public function up()
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() !== 'pgsql') {
            $this->createCacheTableIfMissing('cache');

            return;
        }

        if (! $this->tableExists('public', 'cache')) {
            $this->createCacheTableIfMissing('cache');
        }

        if ($this->tableExists('app_mobile', 'cache')) {
            $connection->statement(
                'insert into public.cache ("key", value, expiration)
                 select src."key", src.value, src.expiration
                 from app_mobile.cache as src
                 left join public.cache as dst on dst."key" = src."key"
                 where dst."key" is null'
            );

            $connection->statement('drop table if exists app_mobile.cache');
        }
    }

    public function down()
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() !== 'pgsql') {
            return;
        }

        if (! $this->tableExists('app_mobile', 'cache')) {
            $connection->statement(
                'create table app_mobile.cache (
                    "key" varchar(255) not null primary key,
                    value text not null,
                    expiration integer not null
                )'
            );
            $connection->statement('create index if not exists app_mobile_cache_expiration_index on app_mobile.cache (expiration)');
        }

        if ($this->tableExists('public', 'cache')) {
            $connection->statement(
                'insert into app_mobile.cache ("key", value, expiration)
                 select src."key", src.value, src.expiration
                 from public.cache as src
                 left join app_mobile.cache as dst on dst."key" = src."key"
                 where dst."key" is null'
            );

            $connection->statement('drop table if exists public.cache');
        }
    }

    protected function createCacheTableIfMissing($tableName)
    {
        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->string('key')->unique();
            $table->mediumText('value');
            $table->integer('expiration')->index();
        });
    }

    protected function tableExists($schema, $table)
    {
        $row = Schema::getConnection()->selectOne(
            'select 1 from information_schema.tables where table_schema = ? and table_name = ? limit 1',
            [$schema, $table]
        );

        return (bool) $row;
    }
}
