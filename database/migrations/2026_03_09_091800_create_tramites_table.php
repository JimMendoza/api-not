<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTramitesTable extends Migration
{
    public function up()
    {
        Schema::create('tramites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('usuario_app_id');
            $table->string('codigo', 30);
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->date('fecha_registro');
            $table->string('estado_actual', 50);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'codigo']);
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('usuario_app_id')->references('id')->on('usuarios_app')->onDelete('cascade');
            $table->index(['usuario_app_id', 'fecha_registro']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tramites');
    }
}
