<?php

namespace App\Http\Controllers\Panel;

use App\Enums\EstadoTurno;
use App\Http\Controllers\Controller;
use App\Models\Profesional;
use App\Services\AgendaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function __invoke(Request $request, AgendaService $agenda): View
    {
        $user = $request->user();
        $tz = config('app.timezone', 'America/Asuncion');

        $vista = $request->input('vista') === 'semana' ? 'semana' : 'dia';

        $fechaStr = $request->input('fecha');
        try {
            $fecha = $fechaStr ? Carbon::parse($fechaStr, $tz) : now($tz);
        } catch (\Exception) {
            $fecha = now($tz);
        }

        $estadoStr = $request->input('estado');
        $estado = $estadoStr ? EstadoTurno::tryFrom($estadoStr) : null;

        $solicitudes = $agenda->solicitudesPendientes($user)
            ->with(['paciente', 'servicio', 'profesional'])
            ->orderBy('inicio', 'asc')
            ->limit(5)
            ->get();
        $conteoPendientes = $agenda->solicitudesPendientes($user)->count();

        if ($vista === 'semana' && ! $request->filled('profesional') && ! Profesional::activas()->exists()) {
            $vista = 'dia';
        }

        if ($vista === 'semana') {
            $profesionalId = $request->input('profesional');
            $profesional = $profesionalId ? Profesional::find($profesionalId) : null;
            $datosSemana = $agenda->agendaDeLaSemana($user, $profesional, $fecha, $estado);

            return view('panel.agenda', array_merge($datosSemana, [
                'vista' => 'semana',
                'fecha' => $fecha,
                'estado' => $estado,
                'solicitudes' => $solicitudes,
                'conteoPendientes' => $conteoPendientes,
            ]));
        }

        $datosDia = $agenda->agendaDelDia($user, $fecha, $estado);

        return view('panel.agenda', array_merge($datosDia, [
            'vista' => 'dia',
            'fecha' => $fecha,
            'estado' => $estado,
            'solicitudes' => $solicitudes,
            'conteoPendientes' => $conteoPendientes,
        ]));
    }
}
