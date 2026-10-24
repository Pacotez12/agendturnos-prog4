<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\DisponibilidadController;
use App\Http\Controllers\Api\v1\MisTurnosController;
use App\Http\Controllers\Api\v1\ProfesionalController;
use App\Http\Controllers\Api\v1\ServicioController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    // Endpoints públicos
    Route::get('/servicios', [ServicioController::class, 'index'])->name('api.v1.servicios');
    Route::get('/profesionales', [ProfesionalController::class, 'index'])->name('api.v1.profesionales');
    Route::get('/disponibilidad', [DisponibilidadController::class, 'index'])->name('api.v1.disponibilidad');

    // Autenticación por token
    Route::post('/token', [AuthController::class, 'crearToken'])
        ->middleware('throttle:api-token')
        ->name('api.v1.token.crear');

    // Rutas protegidas con Sanctum
    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('/token', [AuthController::class, 'revocarToken'])->name('api.v1.token.revocar');
        Route::get('/mis-turnos', [MisTurnosController::class, 'index'])->name('api.v1.mis-turnos');
    });
});
