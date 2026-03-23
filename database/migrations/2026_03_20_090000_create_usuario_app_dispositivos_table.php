<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuarioAppDispositivosTable extends Migration
{
    public function up()
    {
        Schema::create('usuario_app_dispositivos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('usuario_app_id');
            $table->string('device_id')->unique();
            $table->text('push_token');
            $table->string('platform', 20);
            $table->string('device_name')->nullable();
            $table->string('app_version', 30)->nullable();
            $table->boolean('activo')->default(true);
            $table->dateTime('ultimo_registro_at')->nullable();
            $table->dateTime('ultimo_push_at')->nullable();
            $table->dateTime('invalidado_at')->nullable();
            $table->timestamps();

            $table->foreign('usuario_app_id')
                ->references('id')
                ->on('usuarios_app')
                ->onDelete('cascade');

            $table->index(['usuario_app_id', 'activo']);
            $table->index(['platform', 'activo']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('usuario_app_dispositivos');
    }
}
