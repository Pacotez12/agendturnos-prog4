<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\Paciente\FamiliaController;
use App\Http\Controllers\Paciente\InicioController as PacienteInicioController;
use App\Http\Controllers\Paciente\ReservaController;
use App\Http\Controllers\Paciente\TurnoController;
use App\Http\Controllers\Panel\AgendaController;
use App\Http\Controllers\Panel\DisponibilidadController;
use App\Http\Controllers\Panel\EspecialidadController;
use App\Http\Controllers\Panel\InicioController as PanelInicioController;
use App\Http\Controllers\Panel\PacienteController as PanelPacienteController;
use App\Http\Controllers\Panel\ProfesionalController;
use App\Http\Controllers\Panel\ServicioController;
use App\Http\Controllers\Panel\TurnoController as PanelTurnoController;
use Illuminate\Support\Facades\Route;

Route::get('/', InicioController::class)->name('inicio');
Route::view('/privacidad', 'privacidad')->name('privacidad');

Route::middleware('guest')->group(function () {
    Route::get('/registro', [RegistroController::class, 'create'])->name('registro');
    Route::post('/registro', [RegistroController::class, 'store'])->middleware('throttle:registro');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/olvide-mi-contrasena', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/olvide-mi-contrasena', [PasswordResetLinkController::class, 'store'])->middleware('throttle:recuperar-contrasena')->name('password.email');

    Route::get('/restablecer-contrasena/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/restablecer-contrasena', [NewPasswordController::class, 'store'])->middleware('throttle:restablecer-contrasena')->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'rol:paciente'])->group(function () {
    Route::get('/inicio', PacienteInicioController::class)->name('paciente.inicio');

    Route::get('/turnos/nuevo', [ReservaController::class, 'create'])->name('paciente.reservar');
    Route::post('/turnos', [TurnoController::class, 'store'])->middleware('throttle:solicitar-turno')->name('paciente.turnos.store');
    Route::get('/mis-turnos', [TurnoController::class, 'index'])->name('paciente.turnos');
    Route::get('/turnos/{turno}', [TurnoController::class, 'show'])->name('paciente.turnos.show');
    Route::post('/turnos/{turno}/cancelar', [TurnoController::class, 'cancelar'])->name('paciente.turnos.cancelar');

    Route::get('/mi-familia', [FamiliaController::class, 'index'])->name('paciente.familia');
    Route::post('/mi-familia', [FamiliaController::class, 'store'])->middleware('throttle:familia')->name('paciente.familia.store');
    Route::put('/mi-familia/{paciente}', [FamiliaController::class, 'update'])->middleware('throttle:familia')->name('paciente.familia.update');
});

Route::get('/servicios', [CatalogoController::class, 'index'])->name('servicios.catalogo');

Route::middleware(['auth', 'rol:funcionario'])->group(function () {
    Route::prefix('panel/servicios')->group(function () {
        Route::get('/', [ServicioController::class, 'index'])->name('panel.servicios');
        Route::get('/crear', [ServicioController::class, 'create'])->name('panel.servicios.create');
        Route::post('/', [ServicioController::class, 'store'])->name('panel.servicios.store');
        Route::get('/{servicio}/editar', [ServicioController::class, 'edit'])->name('panel.servicios.edit');
        Route::put('/{servicio}', [ServicioController::class, 'update'])->name('panel.servicios.update');
    });

    Route::prefix('panel/especialidades')->group(function () {
        Route::get('/', [EspecialidadController::class, 'index'])->name('panel.especialidades');
        Route::get('/crear', [EspecialidadController::class, 'create'])->name('panel.especialidades.create');
        Route::post('/', [EspecialidadController::class, 'store'])->name('panel.especialidades.store');
        Route::get('/{especialidad}/editar', [EspecialidadController::class, 'edit'])->name('panel.especialidades.edit');
        Route::put('/{especialidad}', [EspecialidadController::class, 'update'])->name('panel.especialidades.update');
    });

    Route::prefix('panel/profesionales')->group(function () {
        Route::get('/', [ProfesionalController::class, 'index'])->name('panel.profesionales');
        Route::post('/', [ProfesionalController::class, 'store'])->name('panel.profesionales.store');
        Route::put('/{profesional}', [ProfesionalController::class, 'update'])->name('panel.profesionales.update');
        Route::post('/{profesional}/baja', [ProfesionalController::class, 'baja'])->name('panel.profesionales.baja');
        Route::post('/{profesional}/reactivar', [ProfesionalController::class, 'reactivar'])->name('panel.profesionales.reactivar');
        Route::get('/{profesional}/servicios', [ProfesionalController::class, 'servicios'])->name('panel.profesionales.servicios');
        Route::put('/{profesional}/servicios', [ProfesionalController::class, 'updateServicios'])->name('panel.profesionales.servicios.update');
    });
});

Route::middleware(['auth', 'rol:profesional,funcionario'])->group(function () {
    Route::get('/panel', PanelInicioController::class)->name('panel.inicio');

    Route::get('/panel/agenda', AgendaController::class)->name('panel.agenda');

    Route::get('/panel/disponibilidad', [DisponibilidadController::class, 'index'])->name('panel.disponibilidad');
    Route::put('/panel/disponibilidad/horario', [DisponibilidadController::class, 'guardarHorario'])->name('panel.disponibilidad.horario');
    Route::post('/panel/disponibilidad/bloqueos', [DisponibilidadController::class, 'guardarBloqueo'])->name('panel.disponibilidad.bloqueos.store');
    Route::delete('/panel/disponibilidad/bloqueos/{bloqueo}', [DisponibilidadController::class, 'eliminarBloqueo'])->name('panel.disponibilidad.bloqueos.destroy');

    Route::get('/panel/solicitudes', [PanelTurnoController::class, 'solicitudes'])->name('panel.solicitudes');
    Route::get('/panel/turnos/nuevo', [PanelTurnoController::class, 'crear'])->name('panel.turnos.crear');
    Route::post('/panel/turnos', [PanelTurnoController::class, 'store'])->name('panel.turnos.store');
    Route::get('/panel/pacientes', [PanelPacienteController::class, 'index'])->name('panel.pacientes');
    Route::post('/panel/pacientes', [PanelPacienteController::class, 'store'])->name('panel.pacientes.store');
    Route::get('/panel/pacientes/{paciente}', [PanelPacienteController::class, 'show'])->name('panel.pacientes.show');
    Route::put('/panel/pacientes/{paciente}/contacto', [PanelPacienteController::class, 'actualizarContacto'])->name('panel.pacientes.contacto.update');
    Route::get('/panel/turnos/{turno}', [PanelTurnoController::class, 'show'])->name('panel.turnos.show');
    Route::post('/panel/turnos/{turno}/confirmar', [PanelTurnoController::class, 'confirmar'])->name('panel.turnos.confirmar');
    Route::post('/panel/turnos/{turno}/cancelar', [PanelTurnoController::class, 'cancelar'])->name('panel.turnos.cancelar');
    Route::post('/panel/turnos/{turno}/atendido', [PanelTurnoController::class, 'atendido'])->name('panel.turnos.atendido');
    Route::post('/panel/turnos/{turno}/ausente', [PanelTurnoController::class, 'ausente'])->name('panel.turnos.ausente');
    Route::post('/panel/turnos/{turno}/recordatorio', [PanelTurnoController::class, 'recordatorio'])
        ->middleware('throttle:recordatorio')
        ->name('panel.turnos.recordatorio');
    Route::get('/panel/turnos/{turno}/reprogramar', [PanelTurnoController::class, 'reprogramar'])->name('panel.turnos.reprogramar');
    Route::put('/panel/turnos/{turno}/reprogramar', [PanelTurnoController::class, 'updateReprogramar'])->name('panel.turnos.reprogramar.update');
});

if (app()->environment('local')) {
    Route::get('/ui', function () {
        return view('ui.componentes');
    })->name('ui.componentes');
}
