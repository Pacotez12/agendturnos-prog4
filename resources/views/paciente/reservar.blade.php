<x-layouts.paciente titulo="Reservar turno">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold font-display text-ink-900">Reservar turno</h1>
            <p class="text-sm text-ink-500 mt-1">
                Elegí la profesional, el servicio y el día para ver los horarios disponibles.
            </p>
        </div>

        @error('solicitud')
            <x-alert type="error">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <span>{{ $message }}</span>
                    <a href="{{ route('paciente.turnos') }}" class="font-semibold underline hover:no-underline whitespace-nowrap">
                        Mis turnos
                    </a>
                </div>
            </x-alert>
        @enderror

        <x-card :paciente="true">
            <form method="GET" action="{{ route('paciente.reservar') }}" class="space-y-5">
                @if ($para ?? null)
                    <input type="hidden" name="para" value="{{ $para->id }}">
                @endif
                <x-select
                    name="profesional"
                    label="Profesional"
                    placeholder="Elegí la profesional"
                    :options="$profesionales->mapWithKeys(fn ($p) => [$p->id => $p->nombre.' '.$p->apellido])"
                    :value="$profesional?->id"
                    x-data
                    x-on:change="$el.form.servicio?.remove(); $el.form.fecha?.remove(); $el.form.submit()"
                />

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

                <noscript>
                    <x-button type="submit" variant="secondary">Continuar</x-button>
                </noscript>
            </form>
        </x-card>

        @if ($fecha)
            @if (collect($horarios)->contains('libre', true))
                @php
                    $fechaFormateada = \App\Support\Fecha::corta($fecha);
                @endphp

                <form
                    method="POST"
                    action="{{ route('paciente.turnos.store') }}"
                    class="space-y-6"
                    x-data="{ horaSeleccionada: '{{ old('hora', '') }}' }"
                >
                    @csrf
                    <input type="hidden" name="profesional" value="{{ $profesional?->id }}">
                    <input type="hidden" name="servicio" value="{{ $servicio?->id }}">
                    <input type="hidden" name="fecha" value="{{ $fecha->toDateString() }}">

                    <x-card :paciente="true">
                        <h2 class="text-lg font-bold font-display text-ink-900 mb-4">
                            Horarios del {{ $fecha->translatedFormat('l j \d\e F') }}
                        </h2>

                        <x-slot-picker
                            name="hora"
                            :horarios="$horarios"
                            :value="old('hora')"
                            x-on:change="horaSeleccionada = $event.target.value"
                        />

                        @error('hora')
                            <p class="text-xs text-danger-700 mt-2">{{ $message }}</p>
                        @enderror
                    </x-card>

                    @if ($pacienteActual || ($familiares ?? collect())->isNotEmpty())
                        <x-card :paciente="true" class="space-y-3">
                            <h2 class="text-lg font-bold font-display text-ink-900">
                                ¿Para quién es el turno?
                            </h2>

                            <div class="space-y-2">
                                @if ($pacienteActual)
                                    <label class="flex items-center gap-3 p-3 rounded-xl border border-line hover:bg-cream-50 cursor-pointer has-checked:border-brand-600 has-checked:bg-brand-100">
                                        <input
                                            type="radio"
                                            name="paciente_id"
                                            value="{{ $pacienteActual->id }}"
                                            {{ old('paciente_id', (string) ($preseleccionado ?? $pacienteActual->id)) == $pacienteActual->id ? 'checked' : '' }}
                                            class="size-4 text-brand-600 focus:ring-brand-600 border-line"
                                        >
                                        <div>
                                            <span class="block text-sm font-semibold text-ink-900">Para mí</span>
                                            <span class="block text-xs text-ink-500">{{ $pacienteActual->nombre }} {{ $pacienteActual->apellido }}</span>
                                        </div>
                                    </label>
                                @endif

                                @foreach ($familiares ?? [] as $familiar)
                                    <label class="flex items-center gap-3 p-3 rounded-xl border border-line hover:bg-cream-50 cursor-pointer has-checked:border-brand-600 has-checked:bg-brand-100">
                                        <input
                                            type="radio"
                                            name="paciente_id"
                                            value="{{ $familiar->id }}"
                                            {{ old('paciente_id', (string) ($preseleccionado ?? '')) == $familiar->id ? 'checked' : '' }}
                                            class="size-4 text-brand-600 focus:ring-brand-600 border-line"
                                        >
                                        <div>
                                            <span class="block text-sm font-semibold text-ink-900">Para {{ $familiar->nombre }}</span>
                                            <span class="block text-xs text-ink-500">{{ $familiar->nombre }} {{ $familiar->apellido }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            @error('paciente_id')
                                <p class="text-xs text-danger-700 mt-1">{{ $message }}</p>
                            @enderror
                        </x-card>
                    @endif

                    <div x-show="horaSeleccionada" x-cloak style="display: none;">
                        <x-button type="submit" :pill="true">
                            <span x-text="'Solicitar turno · {{ $fechaFormateada }}, ' + horaSeleccionada">Solicitar turno</span>
                        </x-button>
                    </div>

                    <noscript>
                        <div>
                            <x-button type="submit" :pill="true">
                                Solicitar turno
                            </x-button>
                        </div>
                    </noscript>
                </form>
            @else
                <x-card :paciente="true">
                    <h2 class="text-lg font-bold font-display text-ink-900 mb-4">
                        Horarios del {{ $fecha->translatedFormat('l j \d\e F') }}
                    </h2>

                    <x-empty-state
                        :titulo="filled($horarios) ? 'Agenda completa' : ($fecha->isToday() ? 'No quedan horarios hoy' : 'No atiende este día')"
                        :texto="filled($horarios) ? 'Todos los horarios de este día ya están reservados. Probá con otra fecha.' : 'Probá con otra fecha.'"
                    />
                </x-card>
            @endif
        @endif
    </div>
</x-layouts.paciente>
