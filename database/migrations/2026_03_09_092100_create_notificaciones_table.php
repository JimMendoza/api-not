<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificacionesTable extends Migration
{
    public function up()
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tramite_id');
            $table->unsignedBigInteger('usuario_app_id');
            $table->string('titulo');
            $table->text('mensaje');
            $table->string('tipo', 30);
            $table->boolean('leida')->default(false);
            $table->dateTime('fecha_hora');
            $table->timestamps();

            $table->foreign('tramite_id')->references('id')->on('tramites')->onDelete('cascade');
            $table->foreign('usuario_app_id')->references('id')->on('usuarios_app')->onDelete('cascade');
            $table->index(['usuario_app_id', 'leida']);
            $table->index(['tramite_id', 'fecha_hora']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notificaciones');
    }
}
