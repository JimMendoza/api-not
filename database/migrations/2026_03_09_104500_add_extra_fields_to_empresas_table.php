<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtraFieldsToEmpresasTable extends Migration
{
    public function up()
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('abrv', 50)->nullable();
            $table->text('claims')->nullable();
            $table->string('color', 50)->nullable();
            $table->text('direccion')->nullable();
            $table->string('ruc', 50)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('url', 100)->nullable();
        });
    }

    public function down()
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'abrv',
                'claims',
                'color',
                'direccion',
                'ruc',
                'telefono',
                'url',
            ]);
        });
    }
}
