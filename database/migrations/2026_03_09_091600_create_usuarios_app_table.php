<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuariosAppTable extends Migration
{
    public function up()
    {
        Schema::create('usuarios_app', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('empresa_id');
            $table->string('username', 50);
            $table->string('full_name');
            $table->string('password');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'username']);
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('usuarios_app');
    }
}
