<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/registro', [RegistroController::class, 'create'])->name('registro');
    Route::post('/registro', [RegistroController::class, 'store']);
});

// Ruta temporal para evitar errores en la redirección post-registro si aún no existe la vista de inicio del paciente
Route::get('/inicio', function () {
    return 'Bienvenido al panel del paciente';
})->name('paciente.inicio');
