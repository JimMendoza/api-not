<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMobileSchema extends Migration
{
    public function up()
    {
        DB::statement('create schema if not exists app_mobile');
    }

    public function down()
    {
        DB::statement('drop schema if exists app_mobile');
    }
}
