<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppMobileTables extends Migration
{
    public function up()
    {
        $this->createUsuarioTokensTable();
        $this->createTramiteSeguimientosTable();
        $this->createUsuarioNotificacionConfiguracionesTable();
        $this->createUsuarioDispositivosTable();
        $this->createNotificacionesTable();
    }

    public function down()
    {
        Schema::dropIfExists($this->tableName('notificaciones'));
        Schema::dropIfExists($this->tableName('usuario_dispositivos'));
        Schema::dropIfExists($this->tableName('usuario_notificacion_configuraciones'));
        Schema::dropIfExists($this->tableName('tramite_seguimientos'));
        Schema::dropIfExists($this->tableName('usuario_tokens'));
    }

    protected function createUsuarioTokensTable()
    {
        $tableName = $this->tableName('usuario_tokens');

        if ($this->tableExists($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('usuario_id');
            $table->string('token', 64)->unique();
            $table->string('token_type', 20)->default('Bearer');
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['usuario_id', 'revoked_at']);
        });
    }

    protected function createTramiteSeguimientosTable()
    {
        $tableName = $this->tableName('tramite_seguimientos');

        if ($this->tableExists($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('usuario_id');
            $table->integer('tramite_id');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['usuario_id', 'tramite_id']);
            $table->index(['usuario_id', 'activo']);
            $table->index(['tramite_id', 'activo']);
        });
    }

    protected function createUsuarioNotificacionConfiguracionesTable()
    {
        $tableName = $this->tableName('usuario_notificacion_configuraciones');

        if ($this->tableExists($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('usuario_id')->unique();
            $table->boolean('silenciar_fuera_de_horario')->default(false);
            $table->string('hora_silencio_inicio', 5)->default('22:00');
            $table->string('hora_silencio_fin', 5)->default('07:00');
            $table->string('zona_horaria', 100)->default('America/Lima');
            $table->boolean('mostrar_contador_no_leidas')->default(true);
            $table->timestamps();
        });
    }

    protected function createUsuarioDispositivosTable()
    {
        $tableName = $this->tableName('usuario_dispositivos');

        if ($this->tableExists($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('usuario_id');
            $table->string('device_id', 191)->unique();
            $table->text('push_token');
            $table->string('platform', 20);
            $table->string('device_name', 120)->nullable();
            $table->string('app_version', 30)->nullable();
            $table->boolean('activo')->default(true);
            $table->dateTime('ultimo_registro_at')->nullable();
            $table->dateTime('ultimo_push_at')->nullable();
            $table->dateTime('invalidado_at')->nullable();
            $table->timestamps();

            $table->index(['usuario_id', 'activo']);
            $table->index(['platform', 'activo']);
        });
    }

    protected function createNotificacionesTable()
    {
        $tableName = $this->tableName('notificaciones');

        if ($this->tableExists($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('usuario_id');
            $table->integer('tramite_id');
            $table->string('titulo');
            $table->text('mensaje');
            $table->string('tipo', 50);
            $table->boolean('leida')->default(false);
            $table->dateTime('fecha_hora');
            $table->timestamps();

            $table->index(['usuario_id', 'leida']);
            $table->index(['tramite_id', 'fecha_hora']);
            $table->index(['usuario_id', 'tramite_id']);
        });
    }

    protected function tableName($table)
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            return 'app_mobile.'.$table;
        }

        return 'app_mobile_'.$table;
    }

    protected function tableExists($qualifiedTable)
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return Schema::hasTable($qualifiedTable);
        }

        [$schema, $table] = explode('.', $qualifiedTable, 2);

        $row = Schema::getConnection()->selectOne(
            'select 1 from information_schema.tables where table_schema = ? and table_name = ? limit 1',
            [$schema, $table]
        );

        return (bool) $row;
    }
}
