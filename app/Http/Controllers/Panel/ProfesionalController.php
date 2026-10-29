<?php

namespace App\Http\Controllers\Panel;

use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ProfesionalRequest;
use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\User;
use App\Notifications\InvitacionProfesional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class ProfesionalController extends Controller
{
    /**
     * Display a listing of professionals.
     */
    public function index(): View
    {
        $profesionales = Profesional::with('user')
            ->withCount('servicios')
            ->ordenados()
            ->get();

        return view('panel.profesionales.index', [
            'profesionales' => $profesionales,
        ]);
    }

    /**
     * Store a newly created professional in storage.
     */
    public function store(ProfesionalRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        [$user, $profesional] = DB::transaction(function () use ($datos) {
            $user = User::create([
                'email' => $datos['email'],
                'password' => Hash::make(Str::random(64)),
                'rol' => RolUsuario::Profesional,
            ]);

            $orden = ((int) Profesional::max('orden')) + 1;

            $profesional = Profesional::create([
                'user_id' => $user->id,
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'matricula' => $datos['matricula'],
                'orden' => $orden,
            ]);

            return [$user, $profesional];
        });

        $correoEnviado = true;

        try {
            $token = Password::broker('invitaciones')->createToken($user);
            $user->notify(app(InvitacionProfesional::class, ['token' => $token]));
        } catch (Throwable $e) {
            $correoEnviado = false;

            Log::warning('Invitación a profesional no enviada', [
                'profesional_id' => $profesional->id,
                'email' => $user->email,
                'mensaje' => $e->getMessage(),
            ]);
        }

        $mensaje = $correoEnviado
            ? "{$profesional->nombreCompleto()} quedó dada de alta. Le enviamos un correo a {$user->email} para que cree su contraseña. Siguiente paso: elegí los servicios que ofrece y cargale el horario."
            : "{$profesional->nombreCompleto()} quedó dada de alta, pero el correo de invitación a {$user->email} no se pudo enviar. Siguiente paso: elegí los servicios que ofrece y cargale el horario.";

        return redirect()
            ->route('panel.profesionales.servicios', $profesional)
            ->with('success', $mensaje);
    }

    /**
     * Update the specified professional in storage.
     */
    public function update(ProfesionalRequest $request, Profesional $profesional): RedirectResponse
    {
        $datos = $request->validated();

        DB::transaction(function () use ($profesional, $datos) {
            $profesional->update([
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'matricula' => $datos['matricula'],
            ]);

            $profesional->user->update([
                'email' => $datos['email'],
            ]);
        });

        return redirect()
            ->route('panel.profesionales')
            ->with('success', "Guardamos los datos de {$profesional->nombreCompleto()}.");
    }

    /**
     * Show the form for managing services offered by the professional.
     */
    public function servicios(Profesional $profesional): View
    {
        $especialidades = Especialidad::where('activa', true)
            ->whereHas('servicios', fn ($q) => $q->where('activo', true))
            ->with(['servicios' => fn ($q) => $q->where('activo', true)->orderBy('nombre')])
            ->orderBy('nombre')
            ->get();

        $serviciosOfrecidosIds = $profesional->servicios()->pluck('servicios.id')->all();
        $serviciosConTurnosIds = $profesional->turnos()->pluck('servicio_id')->unique()->all();

        return view('panel.profesionales.servicios', [
            'profesional' => $profesional,
            'especialidades' => $especialidades,
            'serviciosOfrecidosIds' => $serviciosOfrecidosIds,
            'serviciosConTurnosIds' => $serviciosConTurnosIds,
        ]);
    }

    /**
     * Update the services offered by the professional.
     */
    public function updateServicios(Request $request, Profesional $profesional): RedirectResponse
    {
        $request->validate([
            'servicios' => ['sometimes', 'array'],
            'servicios.*' => ['integer', 'exists:servicios,id'],
        ]);

        $serviciosIds = array_map('intval', (array) $request->input('servicios', []));
        $serviciosConTurnosIds = $profesional->turnos()
            ->pluck('servicio_id')
            ->unique()
            ->map('intval')
            ->all();

        // Los servicios inactivos (o de especialidades inactivas) no se muestran en la pantalla:
        // se conservan tal cual para no perder la asignación si se reactivan.
        $serviciosOcultosIds = $profesional->servicios()
            ->where(fn ($q) => $q->where('servicios.activo', false)
                ->orWhereHas('especialidad', fn ($e) => $e->where('activa', false)))
            ->pluck('servicios.id')
            ->map('intval')
            ->all();

        $finalServiciosIds = array_values(array_unique(array_merge($serviciosIds, $serviciosConTurnosIds, $serviciosOcultosIds)));

        $profesional->servicios()->sync($finalServiciosIds);

        return redirect()
            ->route('panel.profesionales.servicios', $profesional)
            ->with('success', 'Servicios actualizados.');
    }
}
