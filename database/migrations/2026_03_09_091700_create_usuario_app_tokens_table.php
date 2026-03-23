<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuarioAppTokensTable extends Migration
{
    public function up()
    {
        Schema::create('usuario_app_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('usuario_app_id');
            $table->string('token', 64)->unique();
            $table->string('token_type', 20)->default('Bearer');
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('usuario_app_id')->references('id')->on('usuarios_app')->onDelete('cascade');
            $table->index(['usuario_app_id', 'revoked_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('usuario_app_tokens');
    }
}
