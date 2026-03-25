<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppMobileCacheTable extends Migration
{
    public function up()
    {
        $tableName = $this->tableName('cache');

        if ($this->tableExists($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->string('key')->unique();
            $table->mediumText('value');
            $table->integer('expiration')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->tableName('cache'));
    }

    protected function tableName($table)
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            return 'app_mobile.'.$table;
        }

        return 'app_mobile_'.$table;
    }

    protected function tableExists($qualifiedTable)
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return Schema::hasTable($qualifiedTable);
        }

        [$schema, $table] = explode('.', $qualifiedTable, 2);

        $row = Schema::getConnection()->selectOne(
            'select 1 from information_schema.tables where table_schema = ? and table_name = ? limit 1',
            [$schema, $table]
        );

        return (bool) $row;
    }
}
