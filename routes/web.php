<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\Panel\ServicioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/registro', [RegistroController::class, 'create'])->name('registro');
    Route::post('/registro', [RegistroController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'rol:paciente'])->group(function () {
    Route::get('/inicio', function () {
        return view('paciente.inicio');
    })->name('paciente.inicio');
});

Route::get('/servicios', [CatalogoController::class, 'index'])->name('servicios.catalogo');

Route::middleware(['auth', 'rol:funcionario'])->prefix('panel/servicios')->group(function () {
    Route::get('/', [ServicioController::class, 'index'])->name('panel.servicios');
    Route::get('/crear', [ServicioController::class, 'create'])->name('panel.servicios.create');
    Route::post('/', [ServicioController::class, 'store'])->name('panel.servicios.store');
    Route::get('/{servicio}/editar', [ServicioController::class, 'edit'])->name('panel.servicios.edit');
    Route::put('/{servicio}', [ServicioController::class, 'update'])->name('panel.servicios.update');
});

Route::middleware(['auth', 'rol:profesional,funcionario'])->group(function () {
    Route::get('/panel', function () {
        return view('panel.inicio');
    })->name('panel.inicio');
});

if (app()->environment('local')) {
    Route::get('/ui', function () {
        return view('ui.componentes');
    })->name('ui.componentes');
}
