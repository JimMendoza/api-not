<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTramiteMovimientosTable extends Migration
{
    public function up()
    {
        Schema::create('tramite_movimientos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tramite_id');
            $table->dateTime('fecha_hora');
            $table->string('nro_doc', 100);
            $table->string('destino');
            $table->string('estado', 50);
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->foreign('tramite_id')->references('id')->on('tramites')->onDelete('cascade');
            $table->index(['tramite_id', 'fecha_hora']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tramite_movimientos');
    }
}
