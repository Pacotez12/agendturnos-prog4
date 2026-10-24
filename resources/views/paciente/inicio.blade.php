<x-layouts.paciente titulo="Inicio">
    <div class="space-y-6 max-w-3xl mx-auto">
        <!-- Encabezado con saludo -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold font-display text-ink-900">
                    Hola{{ $nombre ? ', ' . $nombre : '' }}
                </h1>
                <p class="text-sm text-ink-500 mt-1">
                    Gestioná tus turnos odontológicos y los de tu familia en Glow Dental Protect.
                </p>
            </div>
            <div class="sm:shrink-0">
                <x-button :href="route('paciente.reservar')" :pill="true" variant="primary" class="w-full sm:w-auto shadow-xs">
                    <x-phosphor-plus class="w-4 h-4 mr-2" aria-hidden="true" />
                    Reservar turno
                </x-button>
            </div>
        </div>

        @if (! $tieneProximos)
            <!-- Estado vacío cuando no hay ningún turno próximo -->
            <x-card class="p-8 text-center">
                <x-empty-state
                    titulo="No tenés turnos próximos"
                    texto="Todavía no tenés turnos agendados. Elegí el servicio y la profesional que necesitás para agendar tu consulta."
                >
                    <x-slot:accion>
                        <x-button :href="route('paciente.reservar')" :pill="true" variant="primary">
                            Reservar turno
                        </x-button>
                    </x-slot:accion>
                </x-empty-state>
            </x-card>
        @else
            <!-- Próximo turno propio (tarjeta grande destacada) -->
            @if ($proximoPropio)
                <div class="space-y-2">
                    <h2 class="text-xs font-semibold text-ink-500 uppercase tracking-wider">
                        Tu próximo turno
                    </h2>
                    <div class="bg-card border-2 border-brand-600 rounded-2xl p-5 sm:p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <span class="text-xs font-semibold uppercase tracking-wider text-brand-text bg-brand-50/50 dark:bg-brand-100/10 px-2.5 py-1 rounded-full">
                                Próxima consulta
                            </span>
                            <x-estado-turno :estado="$proximoPropio->estado" />
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-2 pb-4 border-b border-line">
                            <div>
                                <p class="text-2xl sm:text-3xl font-bold font-display text-ink-900 capitalize">
                                    {{ ucfirst($proximoPropio->inicio->translatedFormat('l j \d\e F')) }}
                                </p>
                                <p class="text-3xl sm:text-4xl font-extrabold text-brand-text font-display mt-0.5">
                                    {{ $proximoPropio->inicio->format('H:i') }}
                                    <span class="text-xs font-normal text-ink-500">hs ({{ $proximoPropio->servicio->duracion_min }} min)</span>
                                </p>
                            </div>
                        </div>

                        <div class="pt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <p class="text-base font-bold text-ink-900">
                                    {{ $proximoPropio->servicio->nombre }}
                                </p>
                                <p class="text-sm text-ink-500 mt-0.5">
                                    Dra. {{ $proximoPropio->profesional->nombre }} {{ $proximoPropio->profesional->apellido }}
                                    @if ($proximoPropio->consultorio)
                                        <span class="text-line">·</span> Consultorio {{ $proximoPropio->consultorio->nombre }}
                                    @endif
                                </p>
                            </div>

                            <a
                                href="{{ route('paciente.turnos.show', $proximoPropio) }}"
                                class="inline-flex items-center justify-center font-semibold text-sm text-brand-text hover:text-brand-700 min-h-11 px-4 py-2 rounded-lg bg-cream-50 dark:bg-surface border border-line hover:border-brand-600/40 transition-colors"
                            >
                                Ver detalle
                                <x-phosphor-arrow-right class="w-4 h-4 ml-1.5" aria-hidden="true" />
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Próximos turnos de familiares a cargo -->
            @if (count($proximosDependientes) > 0)
                <div class="space-y-3 pt-2">
                    <h2 class="text-xs font-semibold text-ink-500 uppercase tracking-wider">
                        Turnos de tu familia
                    </h2>

                    <div class="grid grid-cols-1 gap-3">
                        @foreach ($proximosDependientes as $item)
                            @php
                                $dep = $item['paciente'];
                                $tDep = $item['turno'];
                            @endphp
                            <div class="bg-card border border-line rounded-xl p-4 sm:p-5 hover:border-brand-600/40 transition-colors">
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-brand-600"></div>
                                        <h3 class="font-bold text-ink-900 text-base">
                                            Turno de {{ $dep->nombre }}
                                        </h3>
                                    </div>
                                    <x-estado-turno :estado="$tDep->estado" />
                                </div>

                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2 border-t border-line">
                                    <div>
                                        <p class="text-sm font-semibold text-ink-900">
                                            {{ ucfirst($tDep->inicio->translatedFormat('l j \d\e F')) }} a las {{ $tDep->inicio->format('H:i') }}
                                        </p>
                                        <p class="text-xs text-ink-500 mt-0.5">
                                            {{ $tDep->servicio->nombre }} <span class="text-line">·</span> Dra. {{ $tDep->profesional->nombre }} {{ $tDep->profesional->apellido }}
                                        </p>
                                    </div>

                                    <a
                                        href="{{ route('paciente.turnos.show', $tDep) }}"
                                        class="inline-flex items-center text-xs font-semibold text-brand-text hover:underline min-h-11 sm:min-h-0"
                                    >
                                        Ver detalle
                                        <x-phosphor-arrow-right class="w-3.5 h-3.5 ml-1" aria-hidden="true" />
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Acciones rápidas al pie -->
            <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-line">
                <x-button :href="route('paciente.reservar')" :pill="true" variant="primary" class="w-full sm:w-auto">
                    <x-phosphor-plus class="w-4 h-4 mr-2" aria-hidden="true" />
                    Reservar turno
                </x-button>

                <a
                    href="{{ route('paciente.turnos') }}"
                    class="text-sm font-semibold text-brand-text hover:underline inline-flex items-center gap-1.5 min-h-11 py-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded"
                >
                    <x-phosphor-calendar-check class="w-4 h-4" aria-hidden="true" />
                    Ver todos mis turnos
                </a>
            </div>
        @endif
    </div>
</x-layouts.paciente>
