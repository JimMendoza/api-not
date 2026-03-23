<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmpresasTable extends Migration
{
    public function up()
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('codigo', 20)->unique();
            $table->string('nombre');
            $table->string('imagen')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('empresas');
    }
}
