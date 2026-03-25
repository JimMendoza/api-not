<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestingTramiteVirtualTables extends Migration
{
    public function up()
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        Schema::create('virtual_ESTADO', function (Blueprint $table) {
            $table->integer('ID')->unique();
            $table->string('DESCRIPCION')->nullable();
            $table->string('DESCRIPCION_MP')->nullable();
            $table->string('DESCRIPCION_USER')->nullable();
        });

        Schema::create('virtual_REMITO', function (Blueprint $table) {
            $table->integer('ID')->unique();
            $table->string('NUMERO_EMISION')->nullable();
            $table->dateTime('FECHA_EMISION')->nullable();
            $table->dateTime('FECHA')->nullable();
            $table->string('NRO_EXPEDIENTE')->nullable();
            $table->string('NUMERO_DOCUMENTO')->nullable();
            $table->string('ASUNTO')->nullable();
            $table->text('OBSERVACION')->nullable();
            $table->string('ADMINISTRADO_ID')->nullable();
            $table->integer('ESTADO_ID')->nullable();
            $table->dateTime('CREATED_AT')->nullable();
            $table->dateTime('UPDATED_AT')->nullable();
            $table->dateTime('DELETED_AT')->nullable();
            $table->char('COD_EMP', 20)->nullable();
        });
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        Schema::dropIfExists('virtual_REMITO');
        Schema::dropIfExists('virtual_ESTADO');
    }
}
