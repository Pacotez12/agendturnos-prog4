@php
    $coloresTurno = [
        'solicitado' => 'estado-solicitado-bg estado-solicitado-fg border-l-amber-600',
        'confirmado' => 'estado-confirmado-bg estado-confirmado-fg border-l-blue-800',
        'atendido' => 'estado-atendido-bg estado-atendido-fg border-l-green-800',
        'cancelado' => 'estado-cancelado-bg estado-cancelado-fg border-l-red-800',
        'ausente' => 'estado-ausente-bg estado-ausente-fg border-l-gray-600',
    ];

    $slotHeight = 44;
    $pxPorMinuto = $slotHeight / 30;
    $totalSlots = count($franjas);
    $alturaGrilla = $totalSlots * $slotHeight;

    $tz = config('app.timezone', 'America/Asuncion');
    $esFuncionario = auth()->user()->rol === \App\Enums\RolUsuario::Funcionario;

    $fechaAnterior = $vista === 'semana'
        ? $fecha->copy()->subWeek()->toDateString()
        : $fecha->copy()->subDay()->toDateString();

    $fechaHoy = now($tz)->toDateString();

    $fechaSiguiente = $vista === 'semana'
        ? $fecha->copy()->addWeek()->toDateString()
        : $fecha->copy()->addDay()->toDateString();

    $estados = [
        'solicitado' => 'Solicitado',
        'confirmado' => 'Confirmado',
        'atendido' => 'Atendido',
        'cancelado' => 'Cancelado',
        'ausente' => 'Ausente',
    ];
@endphp

<x-layouts.panel titulo="Agenda">
    <div class="space-y-6">
        <!-- Barra de navegación y controles -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-ink-900">
                    @if ($vista === 'semana')
                        Semana del {{ $dias[0]->translatedFormat('j \d\e F') }} al {{ $dias[4]->translatedFormat('j \d\e F') }}
                    @else
                        {{ ucfirst($fecha->translatedFormat('l j \d\e F')) }}
                    @endif
                </h1>
                <p class="text-sm text-ink-500 mt-1">
                    Grilla horaria y turnos del consultorio.
                </p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <!-- Segmentado Día / Semana -->
                <div class="inline-flex rounded-lg border border-line bg-card p-0.5 text-xs font-semibold">
                    <a
                        href="{{ route('panel.agenda', array_filter(['vista' => 'dia', 'fecha' => $fecha->toDateString(), 'estado' => $estado?->value, 'profesional' => request('profesional')])) }}"
                        class="px-3 py-1.5 rounded-md transition-colors {{ $vista === 'dia' ? 'bg-brand-100 text-brand-700 font-bold' : 'text-ink-700 hover:bg-cream-50' }}"
                    >
                        Día
                    </a>
                    <a
                        href="{{ route('panel.agenda', array_filter(['vista' => 'semana', 'fecha' => $fecha->toDateString(), 'estado' => $estado?->value, 'profesional' => request('profesional')])) }}"
                        class="px-3 py-1.5 rounded-md transition-colors {{ $vista === 'semana' ? 'bg-brand-100 text-brand-700 font-bold' : 'text-ink-700 hover:bg-cream-50' }}"
                    >
                        Semana
                    </a>
                </div>

                <!-- Botones Anterior / Hoy / Siguiente -->
                <div class="inline-flex rounded-lg border border-line bg-card text-xs font-semibold overflow-hidden">
                    <a
                        href="{{ route('panel.agenda', array_filter(['vista' => $vista, 'fecha' => $fechaAnterior, 'estado' => $estado?->value, 'profesional' => request('profesional')])) }}"
                        class="px-3 py-1.5 hover:bg-cream-50 text-ink-900 border-r border-line"
                    >
                        Anterior
                    </a>
                    <a
                        href="{{ route('panel.agenda', array_filter(['vista' => $vista, 'fecha' => $fechaHoy, 'estado' => $estado?->value, 'profesional' => request('profesional')])) }}"
                        class="px-3 py-1.5 hover:bg-cream-50 text-ink-900 border-r border-line"
                    >
                        Hoy
                    </a>
                    <a
                        href="{{ route('panel.agenda', array_filter(['vista' => $vista, 'fecha' => $fechaSiguiente, 'estado' => $estado?->value, 'profesional' => request('profesional')])) }}"
                        class="px-3 py-1.5 hover:bg-cream-50 text-ink-900"
                    >
                        Siguiente
                    </a>
                </div>
            </div>
        </div>

        <!-- Filtros por estado -->
        <div class="flex items-center gap-1.5 flex-wrap text-xs">
            <span class="font-semibold text-ink-500 mr-1">Filtrar:</span>
            <a
                href="{{ route('panel.agenda', array_filter(['vista' => $vista, 'fecha' => $fecha->toDateString(), 'profesional' => request('profesional')])) }}"
                class="rounded-full px-3 py-1 font-semibold border transition-colors {{ is_null($estado) ? 'bg-brand-600 text-white border-brand-600' : 'bg-card text-ink-700 border-line hover:bg-cream-50' }}"
            >
                Todos
            </a>
            @foreach ($estados as $val => $nom)
                <a
                    href="{{ route('panel.agenda', array_filter(['vista' => $vista, 'fecha' => $fecha->toDateString(), 'estado' => $val, 'profesional' => request('profesional')])) }}"
                    class="rounded-full px-3 py-1 font-semibold border transition-colors {{ $estado?->value === $val ? 'bg-brand-600 text-white border-brand-600' : 'bg-card text-ink-700 border-line hover:bg-cream-50' }}"
                >
                    {{ $nom }}
                </a>
            @endforeach
        </div>

        <!-- Selector de profesional en vista semana (solo funcionario) -->
        @if ($vista === 'semana' && $esFuncionario && isset($todosProfesionales))
            <div class="flex items-center gap-2 flex-wrap text-xs">
                <span class="font-semibold text-ink-500">Profesional:</span>
                @foreach ($todosProfesionales as $p)
                    <a
                        href="{{ route('panel.agenda', array_filter(['vista' => 'semana', 'fecha' => $fecha->toDateString(), 'estado' => $estado?->value, 'profesional' => $p->id])) }}"
                        class="rounded-full px-3 py-1 font-semibold border transition-colors {{ $profesional->id === $p->id ? 'bg-brand-600 text-white border-brand-600' : 'bg-card text-ink-900 border-line hover:bg-cream-50' }}"
                    >
                        {{ $p->nombre }} {{ $p->apellido }}
                    </a>
                @endforeach
            </div>
        @endif

        <!-- Grilla y sidebar -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
            <!-- Grilla horaria -->
            <div class="lg:col-span-3 bg-card rounded-lg border border-line p-4 shadow-2xs overflow-hidden">
                <div class="overflow-x-auto">
                    <div class="min-w-[600px]">
                        @if ($vista === 'dia')
                            @php
                                $cantProf = $profesionales->count();
                            @endphp

                            <!-- Encabezado de columnas: Profesionales -->
                            <div class="grid border-b border-line pb-2 mb-2 text-xs font-bold text-ink-900" style="grid-template-columns: 50px repeat({{ $cantProf }}, minmax(0, 1fr));">
                                <div></div>
                                @foreach ($profesionales as $p)
                                    <div class="px-2 truncate">
                                        {{ $p->nombre }} {{ $p->apellido }}
                                    </div>
                                @endforeach
                            </div>

                            <!-- Cuerpo de la grilla -->
                            <div class="relative grid" style="grid-template-columns: 50px repeat({{ $cantProf }}, minmax(0, 1fr)); height: {{ $alturaGrilla }}px;">
                                <!-- Columna de horas -->
                                <div class="border-r border-line select-none">
                                    @foreach ($franjas as $hora)
                                        <div class="h-11 text-xs text-ink-500 border-t border-line/50 pr-2 pt-0.5 text-right">
                                            {{ $hora }}
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Columnas por profesional -->
                                @foreach ($profesionales as $p)
                                    <div class="relative border-r border-line last:border-r-0">
                                        <!-- Líneas de fondo -->
                                        @foreach ($franjas as $hora)
                                            <div class="h-11 border-t border-line/50"></div>
                                        @endforeach

                                        <!-- Turnos posicionados -->
                                        @php
                                            $turnosProf = $turnosPorProfesional->get($p->id, collect());
                                            $inicioGrilla = \Carbon\Carbon::parse($fecha->toDateString().' '.$horaMin, $tz);
                                        @endphp

                                        @foreach ($turnosProf as $turno)
                                            @php
                                                $minutosDesdeInicio = $inicioGrilla->diffInMinutes($turno->inicio, false);
                                                $duracionMinutos = $turno->inicio->diffInMinutes($turno->fin);
                                                $top = max(0, round($minutosDesdeInicio * $pxPorMinuto));
                                                $height = max(round($duracionMinutos * $pxPorMinuto) - 2, 22);
                                            @endphp

                                            <a
                                                href="{{ route('panel.turnos.show', $turno) }}"
                                                style="top: {{ $top }}px; height: {{ $height }}px;"
                                                class="absolute left-1 right-1 rounded-md p-1.5 text-xs overflow-hidden border-l-4 transition hover:shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 {{ $coloresTurno[$turno->estado->value] ?? 'bg-surface text-ink-900 border-l-line' }}"
                                            >
                                                <div class="font-bold leading-tight truncate">
                                                    {{ $turno->inicio->format('H:i') }} {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }}
                                                </div>
                                                <div class="truncate opacity-90">
                                                    {{ $turno->servicio->nombre }} · <span class="font-semibold">{{ ucfirst($turno->estado->value) }}</span>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <!-- Vista Semana: 5 días (Lunes a Viernes) -->
                            <!-- Encabezado de columnas: Días -->
                            <div class="grid border-b border-line pb-2 mb-2 text-xs font-bold text-ink-900" style="grid-template-columns: 50px repeat(5, minmax(0, 1fr));">
                                <div></div>
                                @foreach ($dias as $dia)
                                    <div class="px-2 truncate">
                                        <a
                                            href="{{ route('panel.agenda', array_filter(['vista' => 'dia', 'fecha' => $dia->toDateString(), 'estado' => $estado?->value, 'profesional' => request('profesional')])) }}"
                                            class="hover:text-brand-text block"
                                        >
                                            {{ ucfirst($dia->translatedFormat('l j')) }}
                                        </a>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Cuerpo de la grilla -->
                            <div class="relative grid" style="grid-template-columns: 50px repeat(5, minmax(0, 1fr)); height: {{ $alturaGrilla }}px;">
                                <!-- Columna de horas -->
                                <div class="border-r border-line select-none">
                                    @foreach ($franjas as $hora)
                                        <div class="h-11 text-xs text-ink-500 border-t border-line/50 pr-2 pt-0.5 text-right">
                                            {{ $hora }}
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Columnas por día -->
                                @foreach ($dias as $dia)
                                    <div class="relative border-r border-line last:border-r-0">
                                        <!-- Líneas de fondo -->
                                        @foreach ($franjas as $hora)
                                            <div class="h-11 border-t border-line/50"></div>
                                        @endforeach

                                        <!-- Turnos del día -->
                                        @php
                                            $turnosDia = $turnosPorDia->get($dia->toDateString(), collect());
                                            $inicioGrilla = \Carbon\Carbon::parse($dia->toDateString().' '.$horaMin, $tz);
                                        @endphp

                                        @foreach ($turnosDia as $turno)
                                            @php
                                                $minutosDesdeInicio = $inicioGrilla->diffInMinutes($turno->inicio, false);
                                                $duracionMinutos = $turno->inicio->diffInMinutes($turno->fin);
                                                $top = max(0, round($minutosDesdeInicio * $pxPorMinuto));
                                                $height = max(round($duracionMinutos * $pxPorMinuto) - 2, 22);
                                            @endphp

                                            <a
                                                href="{{ route('panel.turnos.show', $turno) }}"
                                                style="top: {{ $top }}px; height: {{ $height }}px;"
                                                class="absolute left-1 right-1 rounded-md p-1.5 text-xs overflow-hidden border-l-4 transition hover:shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 {{ $coloresTurno[$turno->estado->value] ?? 'bg-surface text-ink-900 border-l-line' }}"
                                            >
                                                <div class="font-bold leading-tight truncate">
                                                    {{ $turno->inicio->format('H:i') }} {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }}
                                                </div>
                                                <div class="truncate opacity-90">
                                                    {{ $turno->servicio->nombre }} · <span class="font-semibold">{{ ucfirst($turno->estado->value) }}</span>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar: Por confirmar -->
            <div class="lg:col-span-1">
                <x-card class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xs font-semibold text-ink-500 uppercase tracking-wider">
                            Por confirmar ({{ $conteoPendientes }})
                        </h2>
                        @if ($conteoPendientes > 0)
                            <a href="{{ route('panel.solicitudes') }}" class="text-xs font-semibold text-brand-text hover:underline">
                                Ver todas
                            </a>
                        @endif
                    </div>

                    @if ($solicitudes->isEmpty())
                        <p class="text-xs text-ink-500 py-2">
                            No hay solicitudes pendientes.
                        </p>
                    @else
                        <div class="divide-y divide-line text-xs">
                            @foreach ($solicitudes as $sol)
                                <div class="py-2.5 space-y-1.5">
                                    <div>
                                        <a href="{{ route('panel.turnos.show', $sol) }}" class="font-bold text-ink-900 hover:text-brand-text block">
                                            {{ $sol->paciente->nombre }} {{ $sol->paciente->apellido }}
                                        </a>
                                        <p class="text-ink-500">
                                            {{ $sol->servicio->nombre }}
                                        </p>
                                        <p class="text-ink-500">
                                            {{ \App\Support\Fecha::corta($sol->inicio) }}, {{ $sol->inicio->format('H:i') }}
                                        </p>
                                    </div>
                                    <form method="POST" action="{{ route('panel.turnos.confirmar', $sol) }}">
                                        @csrf
                                        <x-button type="submit" variant="primary" class="w-full !min-h-8 !py-1 text-xs">
                                            Confirmar
                                        </x-button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>
        </div>
    </div>
</x-layouts.panel>
