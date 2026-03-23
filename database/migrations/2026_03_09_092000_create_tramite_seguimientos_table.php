<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTramiteSeguimientosTable extends Migration
{
    public function up()
    {
        Schema::create('tramite_seguimientos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tramite_id');
            $table->unsignedBigInteger('usuario_app_id');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['tramite_id', 'usuario_app_id']);
            $table->foreign('tramite_id')->references('id')->on('tramites')->onDelete('cascade');
            $table->foreign('usuario_app_id')->references('id')->on('usuarios_app')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tramite_seguimientos');
    }
}
