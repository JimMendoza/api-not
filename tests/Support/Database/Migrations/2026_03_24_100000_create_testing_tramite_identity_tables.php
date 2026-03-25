<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestingTramiteIdentityTables extends Migration
{
    public function up()
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        Schema::create('maestro_EMPRESA', function (Blueprint $table) {
            $table->string('COD_EMP', 20)->primary();
            $table->string('DES_EMP');
            $table->text('IMAGEN')->nullable();
            $table->char('IND_ESTADO', 1)->nullable();
        });

        Schema::create('seguridad_USUARIO', function (Blueprint $table) {
            $table->integer('ID')->unique();
            $table->string('COD_USUARIO', 50);
            $table->string('NOM_USUARIO')->nullable();
            $table->string('NOMBRES')->nullable();
            $table->string('DES_APELLP')->nullable();
            $table->string('DES_APELLM')->nullable();
            $table->string('USU_CLAVE');
            $table->char('IND_ESTADO', 1)->nullable();
        });

        Schema::create('seguridad_USUARIO_EMPRESA', function (Blueprint $table) {
            $table->string('COD_EMP', 20);
            $table->string('COD_USUARIO', 50);
            $table->char('IND_ESTADO', 1)->nullable();
        });

        Schema::create('seguridad_SISTEMA', function (Blueprint $table) {
            $table->string('COD_SISTEMA', 20);
            $table->string('DES_SISTEMA')->nullable();
            $table->char('IND_ESTADO', 1)->nullable();
        });

        Schema::create('seguridad_USUARIO_SISTEMA', function (Blueprint $table) {
            $table->string('COD_EMP', 20);
            $table->string('COD_USUARIO', 50);
            $table->string('COD_SISTEMA', 20);
            $table->char('IND_ESTADO', 1)->nullable();
        });
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        Schema::dropIfExists('seguridad_USUARIO_SISTEMA');
        Schema::dropIfExists('seguridad_SISTEMA');
        Schema::dropIfExists('seguridad_USUARIO_EMPRESA');
        Schema::dropIfExists('seguridad_USUARIO');
        Schema::dropIfExists('maestro_EMPRESA');
    }
}
