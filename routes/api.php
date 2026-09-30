<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SolicitudController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('role:cliente')->group(function () {
        Route::post('/solicitudes', [SolicitudController::class, 'store']);
    });

    Route::middleware('role:agente')->group(function () {
        Route::patch('/solicitudes/{solicitud}/asignar', [SolicitudController::class, 'asignar']);
        Route::post('/solicitudes/{solicitud}/comentarios', [SolicitudController::class, 'comentar']);
    });

    Route::get('/solicitudes', [SolicitudController::class, 'index']);
    Route::get('/solicitudes/{solicitud}', [SolicitudController::class, 'show']);
    Route::patch('/solicitudes/{solicitud}/estado', [SolicitudController::class, 'cambiarEstado']);
});
