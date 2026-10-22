<x-layouts.panel titulo="Nuevo turno">
    <div class="space-y-6 max-w-4xl">
        <div>
            <h1 class="text-2xl font-bold font-display text-ink-900">
                Nuevo turno
            </h1>
            <p class="text-sm text-ink-500 mt-1">
                Para pacientes que llaman o vienen al consultorio. Queda confirmado.
            </p>
        </div>

        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-ink-500 flex items-center gap-2">
                    <span class="inline-flex size-5 items-center justify-center rounded-full bg-brand-600 text-white text-xs font-semibold">1</span>
                    <span>{{ $nuevoPaciente ? 'Paciente nuevo' : 'Paciente' }}</span>
                </h2>
                @if ($pacienteSeleccionado)
                    <a href="{{ route('panel.turnos.crear') }}" class="text-xs font-semibold text-brand-600 hover:underline">
                        Cambiar
                    </a>
                @elseif ($nuevoPaciente)
                    <a href="{{ route('panel.turnos.crear') }}" class="text-xs font-semibold text-brand-600 hover:underline">
                        Buscar uno existente
                    </a>
                @endif
            </div>

            @if ($pacienteSeleccionado)
                <div>
                    <span class="font-bold text-ink-900 text-base block sm:inline">
                        {{ $pacienteSeleccionado->nombre }} {{ $pacienteSeleccionado->apellido }}
                    </span>
                    <span class="text-sm text-ink-500">
                        · {{ $pacienteSeleccionado->tipo_documento }} {{ $pacienteSeleccionado->documento }}
                        @if ($pacienteSeleccionado->telefono)
                            · {{ $pacienteSeleccionado->telefono }}
                        @endif
                        @if ($pacienteSeleccionado->responsable)
                            · a cargo de {{ $pacienteSeleccionado->responsable->nombreVisible() }}
                        @endif
                    </span>
                </div>
            @elseif ($nuevoPaciente)
                <form method="POST" action="{{ route('panel.pacientes.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input name="nombre" label="Nombre" required />
                        <x-input name="apellido" label="Apellido" required />
                        <x-select
                            name="tipo_documento"
                            label="Tipo de documento"
                            :options="['CI' => 'Cédula de Identidad', 'pasaporte' => 'Pasaporte']"
                            value="CI"
                            required
                        />
                        <x-input name="documento" label="Documento" required />
                        <x-input name="telefono" label="Teléfono" />
                        <x-date
                            name="fecha_nacimiento"
                            label="Fecha de nacimiento"
                            :max="now()->toDateString()"
                            required
                        />
                        <div class="sm:col-span-2">
                            <x-input
                                name="responsable_documento"
                                label="Documento del responsable (opcional)"
                                help="Si es menor o está a cargo de un paciente con cuenta activa en el sistema."
                            />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <x-button type="submit">
                            Guardar paciente
                        </x-button>
                    </div>
                </form>
            @else
                <div class="space-y-4">
                    <form method="GET" action="{{ route('panel.turnos.crear') }}">
                        @if (request('profesional')) <input type="hidden" name="profesional" value="{{ request('profesional') }}"> @endif
                        @if (request('servicio')) <input type="hidden" name="servicio" value="{{ request('servicio') }}"> @endif
                        @if (request('fecha')) <input type="hidden" name="fecha" value="{{ request('fecha') }}"> @endif

                        <div class="flex gap-2">
                            <div class="flex-1">
                                <x-input
                                    name="q"
                                    label="Buscar paciente"
                                    placeholder="Nombre, apellido o cédula..."
                                    :value="$q"
                                    help="Ingresá al menos 3 caracteres."
                                />
                            </div>
                            <div class="self-start pt-6">
                                <x-button type="submit" variant="secondary">
                                    Buscar
                                </x-button>
                            </div>
                        </div>
                    </form>

                    @if (mb_strlen($q) >= 3)
                        @if ($pacientes->isNotEmpty())
                            <div class="border border-line rounded-lg divide-y divide-line overflow-hidden">
                                @foreach ($pacientes as $p)
                                    <div class="p-3 flex items-center justify-between hover:bg-cream-50/50 transition-colors">
                                        <div class="min-w-0 pr-4">
                                            <p class="font-bold text-ink-900 text-sm">
                                                {{ $p->nombre }} {{ $p->apellido }}
                                            </p>
                                            <p class="text-xs text-ink-500">
                                                {{ $p->tipo_documento }} {{ $p->documento }}
                                                @if ($p->telefono) · {{ $p->telefono }} @endif
                                                @if ($p->responsable) · a cargo de {{ $p->responsable->nombreVisible() }} @endif
                                            </p>
                                        </div>
                                        <a
                                            href="{{ route('panel.turnos.crear', array_filter([...request()->except('q'), 'paciente' => $p->id])) }}"
                                            class="inline-flex items-center text-xs font-bold text-brand-600 hover:text-brand-700 px-3 py-1.5 rounded-md hover:bg-brand-100 transition-colors shrink-0"
                                        >
                                            Elegir
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-ink-500 italic py-2">
                                No encontramos pacientes que coincidan con "{{ $q }}".
                            </p>
                        @endif
                    @endif

                    <p class="text-xs text-ink-500">
                        ¿No aparece?
                        <a href="{{ route('panel.turnos.crear', ['nuevo_paciente' => 1]) }}" class="font-semibold text-brand-600 hover:underline">
                            Cargar paciente nuevo
                        </a>
                    </p>
                </div>
            @endif
        </x-card>

        @if (! $pacienteSeleccionado)
            <x-card class="opacity-60">
                <h2 class="text-sm font-bold uppercase tracking-wider text-ink-500 flex items-center gap-2 mb-2">
                    <span class="inline-flex size-5 items-center justify-center rounded-full bg-ink-500 text-white text-xs font-semibold">2</span>
                    <span>Turno</span>
                </h2>
                <p class="text-sm text-ink-500">
                    Elegí primero el paciente para continuar.
                </p>
            </x-card>
        @else
            <x-card>
                <h2 class="text-sm font-bold uppercase tracking-wider text-ink-500 flex items-center gap-2 mb-4">
                    <span class="inline-flex size-5 items-center justify-center rounded-full bg-brand-600 text-white text-xs font-semibold">2</span>
                    <span>Turno</span>
                </h2>

                <form method="GET" action="{{ route('panel.turnos.crear') }}" class="space-y-4">
                    <input type="hidden" name="paciente" value="{{ $pacienteSeleccionado->id }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        @if ($esFuncionario)
                            <x-select
                                name="profesional"
                                label="Profesional"
                                placeholder="Elegí la profesional"
                                :options="$profesionales->mapWithKeys(fn ($p) => [$p->id => $p->nombre.' '.$p->apellido])"
                                :value="$profesional?->id"
                                x-data
                                x-on:change="$el.form.servicio?.remove(); $el.form.fecha?.remove(); $el.form.submit()"
                            />
                        @endif

                        @if ($profesional)
                            <x-select
                                name="servicio"
                                label="Servicio"
                                placeholder="Elegí un servicio"
                                :options="$servicios->mapWithKeys(fn ($s) => [$s->id => $s->nombre.' ('.$s->duracion_min.' min)'])"
                                :value="$servicio?->id"
                                x-data
                                x-on:change="$el.form.fecha?.remove(); $el.form.submit()"
                            />
                        @endif

                        @if ($servicio)
                            <x-date
                                name="fecha"
                                label="Día"
                                :value="$fecha?->toDateString()"
                                :min="now()->toDateString()"
                                x-data
                                x-on:change="$el.form.submit()"
                            />
                        @endif
                    </div>

                    <noscript>
                        <x-button type="submit" variant="secondary">Continuar</x-button>
                    </noscript>
                </form>

                @if ($fecha)
                    <div class="mt-6 pt-6 border-t border-line">
                        @if (collect($horarios)->contains('libre', true))
                            @php
                                $fechaFormateada = \App\Support\Fecha::corta($fecha);
                            @endphp

                            <form
                                method="POST"
                                action="{{ route('panel.turnos.store') }}"
                                class="space-y-6"
                                x-data="{ horaSeleccionada: '{{ old('hora', '') }}' }"
                            >
                                @csrf
                                <input type="hidden" name="paciente_id" value="{{ $pacienteSeleccionado->id }}">
                                <input type="hidden" name="profesional" value="{{ $profesional?->id }}">
                                <input type="hidden" name="servicio" value="{{ $servicio?->id }}">
                                <input type="hidden" name="fecha" value="{{ $fecha->toDateString() }}">

                                <div>
                                    <label class="block text-sm font-semibold text-ink-900 mb-2">
                                        Horarios disponibles · {{ $fecha->translatedFormat('l j \d\e F') }}
                                    </label>

                                    <x-slot-picker
                                        name="hora"
                                        :horarios="$horarios"
                                        :value="old('hora')"
                                        x-on:change="horaSeleccionada = $event.target.value"
                                    />

                                    @error('hora')
                                        <p class="text-xs text-danger-700 mt-2">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div x-show="horaSeleccionada" x-cloak class="flex justify-end">
                                    <x-button type="submit">
                                        <span x-text="'Agendar turno · {{ $fechaFormateada }}, ' + horaSeleccionada">
                                            Agendar turno
                                        </span>
                                    </x-button>
                                </div>

                                <noscript>
                                    <div class="flex justify-end">
                                        <x-button type="submit">
                                            Agendar turno
                                        </x-button>
                                    </div>
                                </noscript>
                            </form>
                        @else
                            <x-empty-state
                                titulo="No hay horarios para este día"
                                texto="Probá con otra fecha u otra profesional."
                            />
                        @endif
                    </div>
                @endif
            </x-card>
        @endif
    </div>
</x-layouts.panel>
