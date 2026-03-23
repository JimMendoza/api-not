<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefactorUsuarioAppNotificacionConfiguracionesV2 extends Migration
{
    protected $table = 'usuario_app_notificacion_configuraciones';

    public function up()
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteV2();

            return;
        }

        foreach ([
            'solo_tramites_seguidos',
            'notificar_cambios_estado',
            'notificar_movimientos_hoja_ruta',
            'solo_eventos_importantes',
            'frecuencia_notificacion',
        ] as $column) {
            if (Schema::hasColumn($this->table, $column)) {
                Schema::table($this->table, function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        if (! Schema::hasColumn($this->table, 'hora_silencio_inicio')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->string('hora_silencio_inicio', 5)->default('22:00');
            });
        }

        if (! Schema::hasColumn($this->table, 'hora_silencio_fin')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->string('hora_silencio_fin', 5)->default('07:00');
            });
        }

        if (! Schema::hasColumn($this->table, 'zona_horaria')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->string('zona_horaria', 100)->default('America/Lima');
            });
        }
    }

    public function down()
    {
        if (! Schema::hasTable($this->table)) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        foreach (['hora_silencio_inicio', 'hora_silencio_fin', 'zona_horaria'] as $column) {
            if (Schema::hasColumn($this->table, $column)) {
                Schema::table($this->table, function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        if (! Schema::hasColumn($this->table, 'solo_tramites_seguidos')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->boolean('solo_tramites_seguidos')->default(true);
            });
        }

        if (! Schema::hasColumn($this->table, 'notificar_cambios_estado')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->boolean('notificar_cambios_estado')->default(true);
            });
        }

        if (! Schema::hasColumn($this->table, 'notificar_movimientos_hoja_ruta')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->boolean('notificar_movimientos_hoja_ruta')->default(true);
            });
        }

        if (! Schema::hasColumn($this->table, 'solo_eventos_importantes')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->boolean('solo_eventos_importantes')->default(false);
            });
        }

        if (! Schema::hasColumn($this->table, 'frecuencia_notificacion')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->string('frecuencia_notificacion', 20)->default('inmediatas');
            });
        }
    }

    protected function rebuildSqliteV2()
    {
        DB::statement('PRAGMA foreign_keys=OFF');

        DB::statement("CREATE TABLE {$this->table}_tmp (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            usuario_app_id INTEGER NOT NULL UNIQUE,
            silenciar_fuera_de_horario INTEGER NOT NULL DEFAULT 0,
            hora_silencio_inicio VARCHAR(5) NOT NULL DEFAULT '22:00',
            hora_silencio_fin VARCHAR(5) NOT NULL DEFAULT '07:00',
            zona_horaria VARCHAR(100) NOT NULL DEFAULT 'America/Lima',
            mostrar_contador_no_leidas INTEGER NOT NULL DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            FOREIGN KEY(usuario_app_id) REFERENCES usuarios_app(id) ON DELETE CASCADE
        )");

        DB::statement("INSERT INTO {$this->table}_tmp (
            id,
            usuario_app_id,
            silenciar_fuera_de_horario,
            hora_silencio_inicio,
            hora_silencio_fin,
            zona_horaria,
            mostrar_contador_no_leidas,
            created_at,
            updated_at
        )
        SELECT
            id,
            usuario_app_id,
            COALESCE(silenciar_fuera_de_horario, 0),
            '22:00',
            '07:00',
            'America/Lima',
            COALESCE(mostrar_contador_no_leidas, 1),
            created_at,
            updated_at
        FROM {$this->table}");

        DB::statement("DROP TABLE {$this->table}");
        DB::statement("ALTER TABLE {$this->table}_tmp RENAME TO {$this->table}");

        DB::statement('PRAGMA foreign_keys=ON');
    }
}
