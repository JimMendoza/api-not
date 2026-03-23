<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuarioAppNotificacionConfiguracionesTable extends Migration
{
    public function up()
    {
        Schema::create('usuario_app_notificacion_configuraciones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('usuario_app_id')->unique();
            $table->boolean('solo_tramites_seguidos')->default(true);
            $table->boolean('notificar_cambios_estado')->default(true);
            $table->boolean('notificar_movimientos_hoja_ruta')->default(true);
            $table->boolean('solo_eventos_importantes')->default(false);
            $table->string('frecuencia_notificacion', 20)->default('inmediatas');
            $table->boolean('silenciar_fuera_de_horario')->default(false);
            $table->boolean('mostrar_contador_no_leidas')->default(true);
            $table->timestamps();

            $table->foreign('usuario_app_id')
                ->references('id')
                ->on('usuarios_app')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('usuario_app_notificacion_configuraciones');
    }
}
