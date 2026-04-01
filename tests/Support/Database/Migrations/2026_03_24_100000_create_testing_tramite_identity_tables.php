<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTestingTramiteIdentityTables extends Migration
{
    public function up()
    {
        DB::statement('create schema if not exists maestro');
        DB::statement('create schema if not exists seguridad');

        Schema::dropIfExists('seguridad.USUARIO_SISTEMA');
        Schema::dropIfExists('seguridad.SISTEMA');
        Schema::dropIfExists('seguridad.USUARIO_EMPRESA');
        Schema::dropIfExists('seguridad.USUARIO');
        Schema::dropIfExists('maestro.EMPRESA');

        Schema::create('maestro.EMPRESA', function (Blueprint $table) {
            $table->string('COD_EMP', 20)->primary();
            $table->string('DES_EMP');
            $table->text('IMAGEN')->nullable();
            $table->char('IND_ESTADO', 1)->nullable();
        });

        Schema::create('seguridad.USUARIO', function (Blueprint $table) {
            $table->integer('ID')->unique();
            $table->string('COD_USUARIO', 50);
            $table->string('NOM_USUARIO')->nullable();
            $table->string('NOMBRES')->nullable();
            $table->string('DES_APELLP')->nullable();
            $table->string('DES_APELLM')->nullable();
            $table->string('USU_CLAVE');
            $table->char('IND_ESTADO', 1)->nullable();
        });

        Schema::create('seguridad.USUARIO_EMPRESA', function (Blueprint $table) {
            $table->string('COD_EMP', 20);
            $table->string('COD_USUARIO', 50);
            $table->char('IND_ESTADO', 1)->nullable();
        });

        Schema::create('seguridad.SISTEMA', function (Blueprint $table) {
            $table->string('COD_SISTEMA', 20);
            $table->string('DES_SISTEMA')->nullable();
            $table->char('IND_ESTADO', 1)->nullable();
        });

        Schema::create('seguridad.USUARIO_SISTEMA', function (Blueprint $table) {
            $table->string('COD_EMP', 20);
            $table->string('COD_USUARIO', 50);
            $table->string('COD_SISTEMA', 20);
            $table->char('IND_ESTADO', 1)->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('seguridad.USUARIO_SISTEMA');
        Schema::dropIfExists('seguridad.SISTEMA');
        Schema::dropIfExists('seguridad.USUARIO_EMPRESA');
        Schema::dropIfExists('seguridad.USUARIO');
        Schema::dropIfExists('maestro.EMPRESA');
    }
}
