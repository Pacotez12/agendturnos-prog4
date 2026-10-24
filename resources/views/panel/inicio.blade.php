<x-layouts.panel titulo="Inicio">
    <div class="space-y-6">
        <!-- Encabezado con saludo y acciones -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-ink-900">
                    {{ $saludo }}, {{ $nombreUsuario }}
                </h1>
                <p class="text-sm text-ink-500 mt-1">
                    {{ $fechaHoyTexto }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <x-button :href="route('panel.agenda')" variant="secondary">
                    <x-phosphor-calendar-blank class="w-4 h-4 mr-2" aria-hidden="true" />
                    Ver agenda
                </x-button>
                <x-button :href="route('panel.turnos.crear')" variant="primary">
                    <x-phosphor-plus class="w-4 h-4 mr-2" aria-hidden="true" />
                    Nuevo turno
                </x-button>
            </div>
        </div>

        <!-- 4 Indicadores en tarjetas -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Turnos hoy -->
            <x-card class="p-4 sm:p-5">
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">
                    Turnos hoy
                </p>
                <p class="text-2xl sm:text-3xl font-bold text-ink-900 mt-2 font-display">
                    {{ $indicadores['turnosHoy'] }}
                </p>
                <p class="text-xs text-ink-500 mt-1">
                    {{ $indicadores['atendidosHoy'] }} ya atendidos
                </p>
            </x-card>

            <!-- Por confirmar -->
            <x-card class="p-4 sm:p-5 {{ $indicadores['solicitudes'] > 0 ? 'border-brand-600 ring-1 ring-brand-600/30' : '' }}">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">
                        Por confirmar
                    </p>
                    @if ($indicadores['solicitudes'] > 0)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                            Pendiente
                        </span>
                    @endif
                </div>
                <p class="text-2xl sm:text-3xl font-bold text-ink-900 mt-2 font-display">
                    {{ $indicadores['solicitudes'] }}
                </p>
                <div class="mt-1 text-xs">
                    @if ($indicadores['solicitudes'] > 0)
                        <a href="{{ route('panel.solicitudes') }}" class="font-semibold text-brand-text hover:underline inline-flex items-center gap-1">
                            Revisar
                            <x-phosphor-arrow-right class="w-3 h-3" aria-hidden="true" />
                        </a>
                    @else
                        <span class="text-ink-500">Al día</span>
                    @endif
                </div>
            </x-card>

            <!-- Atendidos de la semana -->
            <x-card class="p-4 sm:p-5">
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">
                    Atendidos de la semana
                </p>
                <p class="text-2xl sm:text-3xl font-bold text-ink-900 mt-2 font-display">
                    {{ $indicadores['atendidosSemana'] }}
                </p>
                <p class="text-xs text-ink-500 mt-1">
                    de {{ $indicadores['confirmadosSemana'] }} confirmados
                </p>
            </x-card>

            <!-- Ausentes de la semana -->
            <x-card class="p-4 sm:p-5">
                <p class="text-xs font-semibold text-ink-500 uppercase tracking-wider">
                    Ausentes de la semana
                </p>
                <p class="text-2xl sm:text-3xl font-bold text-ink-900 mt-2 font-display">
                    {{ $indicadores['ausentesSemana'] }}
                </p>
                <p class="text-xs text-ink-500 mt-1">
                    {{ $indicadores['porcentajeInasistencia'] }}% de inasistencia
                </p>
            </x-card>
        </div>

        <!-- Grilla principal: Próximos de hoy y Pendientes -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <!-- Próximos de hoy (2 columnas en desktop) -->
            <div class="lg:col-span-2 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold font-display text-ink-900">
                        Próximos de hoy
                    </h2>
                    <span class="text-xs font-medium text-ink-500">
                        {{ $proximosDeHoy->count() }} {{ $proximosDeHoy->count() === 1 ? 'turno' : 'turnos' }}
                    </span>
                </div>

                @if ($proximosDeHoy->isEmpty())
                    <x-card class="p-6">
                        <x-empty-state
                            titulo="No quedan turnos para hoy"
                            texto="No hay turnos pendientes para lo que resta de la jornada."
                        >
                            <x-slot:accion>
                                <x-button :href="route('panel.turnos.crear')">
                                    Nuevo turno
                                </x-button>
                            </x-slot:accion>
                        </x-empty-state>
                    </x-card>
                @else
                    <div class="space-y-3">
                        @foreach ($proximosDeHoy as $index => $turno)
                            @php
                                $esPrimero = $index === 0;
                            @endphp
                            <div class="bg-card border rounded-xl p-4 transition-all duration-150 motion-reduce:transition-none {{ $esPrimero ? 'border-brand-600 bg-brand-50/20 dark:bg-brand-100/10 shadow-xs' : 'border-line hover:border-brand-600/40' }}">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div class="flex items-start gap-3 sm:gap-4">
                                        <div class="shrink-0 flex flex-col items-center justify-center w-14 py-1.5 rounded-lg {{ $esPrimero ? 'bg-brand-600 text-white' : 'bg-surface text-ink-900 border border-line' }}">
                                            <span class="text-xs font-medium leading-none {{ $esPrimero ? 'text-white/80' : 'text-ink-500' }}">Hora</span>
                                            <span class="text-sm font-bold mt-0.5 leading-none">{{ $turno->inicio->format('H:i') }}</span>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <a href="{{ route('panel.turnos.show', $turno) }}" class="font-bold text-ink-900 hover:text-brand-text truncate focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded">
                                                    {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }}
                                                </a>
                                                <x-estado-turno :estado="$turno->estado" />
                                                @if ($esPrimero)
                                                    <span class="text-2xs font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-brand-600/10 text-brand-text">
                                                        Próximo
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-ink-500 mt-1">
                                                {{ $turno->servicio->nombre }}
                                                @if ($esFuncionario)
                                                    <span class="text-line">·</span> Dra. {{ $turno->profesional->nombre }} {{ $turno->profesional->apellido }}
                                                @endif
                                                <span class="text-line">·</span> {{ $turno->inicio->format('H:i') }} – {{ $turno->fin->format('H:i') }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-end gap-2 pt-2 sm:pt-0 border-t sm:border-t-0 border-line">
                                        @if ($turno->estado === \App\Enums\EstadoTurno::Confirmado)
                                            @php
                                                $minutosRecordatorio = $turno->minutosDesdeUltimoRecordatorio();
                                            @endphp
                                            @if ($minutosRecordatorio !== null && $minutosRecordatorio < 15)
                                                <button
                                                    type="button"
                                                    disabled
                                                    class="text-xs font-semibold text-ink-500 bg-surface border border-line px-2.5 py-1.5 rounded-lg inline-flex items-center gap-1.5 opacity-70 cursor-not-allowed"
                                                >
                                                    <x-phosphor-bell class="w-3.5 h-3.5" aria-hidden="true" />
                                                    Enviado hace {{ $minutosRecordatorio }} min
                                                </button>
                                            @else
                                                <form method="POST" action="{{ route('panel.turnos.recordatorio', $turno) }}" class="inline">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="text-xs font-semibold text-ink-900 bg-card hover:bg-cream-50 active:bg-cream-50 border border-line px-2.5 py-1.5 rounded-lg inline-flex items-center gap-1.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 cursor-pointer"
                                                    >
                                                        <x-phosphor-bell class="w-3.5 h-3.5" aria-hidden="true" />
                                                        Recordar
                                                    </button>
                                                </form>
                                            @endif
                                        @endif

                                        <a
                                            href="{{ route('panel.turnos.show', $turno) }}"
                                            class="text-xs font-semibold text-brand-text hover:underline px-2 py-1.5 inline-flex items-center gap-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded"
                                        >
                                            Ver detalle
                                            <x-phosphor-arrow-right class="w-3 h-3" aria-hidden="true" />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Columna lateral: Pendientes -->
            <div class="space-y-4">
                <h2 class="text-lg font-bold font-display text-ink-900">
                    Pendientes
                </h2>

                <x-card class="p-5 space-y-4">
                    @php
                        $totalPendientes = $pendientes['solicitudes'] + $pendientes['sinMarcar'];
                    @endphp

                    @if ($totalPendientes === 0)
                        <div class="flex items-center gap-3 text-ink-500 py-2">
                            <x-phosphor-check-circle class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" aria-hidden="true" />
                            <p class="text-sm font-medium">Todo al día, no hay acciones pendientes.</p>
                        </div>
                    @else
                        @if ($pendientes['solicitudes'] > 0)
                            <a
                                href="{{ route('panel.solicitudes') }}"
                                class="flex items-center justify-between p-3 rounded-lg bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40 hover:border-amber-400 dark:hover:border-amber-800 transition-colors group"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200">
                                        <x-phosphor-clock class="w-4 h-4" aria-hidden="true" />
                                    </div>
                                    <span class="text-sm font-semibold text-ink-900 group-hover:text-brand-text">
                                        {{ $pendientes['solicitudes'] }} {{ $pendientes['solicitudes'] === 1 ? 'solicitud' : 'solicitudes' }} por confirmar
                                    </span>
                                </div>
                                <x-phosphor-arrow-right class="w-4 h-4 text-ink-500 group-hover:translate-x-0.5 transition-transform" aria-hidden="true" />
                            </a>
                        @endif

                        @if ($pendientes['sinMarcar'] > 0)
                            <a
                                href="{{ route('panel.agenda') }}"
                                class="flex items-center justify-between p-3 rounded-lg bg-surface border border-line hover:border-brand-600/40 transition-colors group"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg bg-card text-ink-500 border border-line">
                                        <x-phosphor-warning class="w-4 h-4 text-amber-600 dark:text-amber-400" aria-hidden="true" />
                                    </div>
                                    <span class="text-sm font-semibold text-ink-900 group-hover:text-brand-text">
                                        {{ $pendientes['sinMarcar'] }} {{ $pendientes['sinMarcar'] === 1 ? 'turno pasado' : 'turnos pasados' }} sin marcar
                                    </span>
                                </div>
                                <x-phosphor-arrow-right class="w-4 h-4 text-ink-500 group-hover:translate-x-0.5 transition-transform" aria-hidden="true" />
                            </a>
                        @endif
                    @endif
                </x-card>
            </div>
        </div>
    </div>
</x-layouts.panel>
