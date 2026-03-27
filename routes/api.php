<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\App\AutenticacionController;
use App\Http\Controllers\Api\App\DispositivoPushController;
use App\Http\Controllers\Api\App\EntidadController;
use App\Http\Controllers\Api\App\ModuloController;
use App\Http\Controllers\Api\App\NotificacionConfiguracionController;
use App\Http\Controllers\Api\App\NotificacionController;
use App\Http\Controllers\Api\App\TramiteController;
use App\Http\Controllers\Api\Integracion\NotificacionEventoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('app')->group(function () {
    Route::post('entidades', [EntidadController::class, 'index']);
    Route::post('login', [AutenticacionController::class, 'login']);

    Route::middleware('app.token')->group(function () {
        Route::get('me', [AutenticacionController::class, 'me']);
        Route::post('logout', [AutenticacionController::class, 'logout']);
        Route::get('modulos', [ModuloController::class, 'index']);

        Route::put('dispositivos/push-token', [DispositivoPushController::class, 'upsert']);
        Route::delete('dispositivos/push-token', [DispositivoPushController::class, 'invalidate']);

        Route::get('tramites', [TramiteController::class, 'index']);
        Route::get('tramites/{id}', [TramiteController::class, 'show'])->where('id', '[0-9]+');
        Route::get('tramites/{id}/hoja-ruta', [TramiteController::class, 'hojaRuta'])->where('id', '[0-9]+');
        Route::post('tramites/{id}/seguir', [TramiteController::class, 'seguir'])->where('id', '[0-9]+');
        Route::delete('tramites/{id}/seguir', [TramiteController::class, 'dejarSeguir'])->where('id', '[0-9]+');

        Route::get('notificaciones', [NotificacionController::class, 'index']);
        Route::get('notificaciones/resumen', [NotificacionController::class, 'resumen']);
        Route::patch('notificaciones/{id}/leida', [NotificacionController::class, 'marcarLeida'])->where('id', '[0-9]+');

        Route::get('notificaciones/configuracion', [NotificacionConfiguracionController::class, 'show']);
        Route::put('notificaciones/configuracion', [NotificacionConfiguracionController::class, 'update']);
    });
});

Route::prefix('integracion')
    ->middleware('integracion.token')
    ->group(function () {
        Route::post('notificaciones/evento', [NotificacionEventoController::class, '__invoke']);
    });



