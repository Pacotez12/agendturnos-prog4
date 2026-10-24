<x-layouts.panel :titulo="'Reprogramar turno de ' . $turno->paciente->nombre . ' ' . $turno->paciente->apellido">
    <div class="max-w-3xl space-y-6" x-data="{ horaSeleccionada: '{{ old('hora', '') }}' }">
        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-ink-900">
                    Reprogramar turno de {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }}
                </h1>
                <p class="text-sm text-ink-500 mt-1">
                    Elegí una nueva fecha y horario disponible.
                </p>
            </div>
            <div>
                <a
                    href="{{ route('panel.turnos.show', $turno) }}"
                    class="text-sm font-semibold text-brand-text hover:text-brand-700 min-h-11 inline-flex items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-lg"
                >
                    &larr; Volver al turno
                </a>
            </div>
        </div>

        @if ($errors->any())
            <x-alert type="error">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <!-- Tarjeta Turno actual -->
        <x-card class="bg-surface/50">
            <span class="text-xs text-ink-500 font-medium">Turno actual:</span>
            <div class="font-semibold text-ink-900 text-sm mt-0.5">
                {{ \App\Support\Fecha::corta($turno->inicio) }}, {{ $turno->inicio->format('H:i') }} · {{ $turno->servicio->nombre }} · {{ $turno->profesional->nombre }} {{ $turno->profesional->apellido }}
            </div>
        </x-card>

        <!-- Selección de nuevo día -->
        <x-card class="space-y-6">
            <div>
                <label for="selector-fecha" class="block text-xs font-semibold text-ink-900 mb-2">
                    Nuevo día
                </label>
                <form method="GET" action="{{ route('panel.turnos.reprogramar', $turno) }}" id="form-cambiar-fecha" class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                    <input
                        type="date"
                        id="selector-fecha"
                        name="fecha"
                        value="{{ $fecha->toDateString() }}"
                        min="{{ now()->toDateString() }}"
                        onchange="this.form.submit()"
                        class="min-h-11 rounded-lg border border-line bg-card px-3 py-2 text-sm text-ink-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                    >
                    <noscript>
                        <x-button type="submit" variant="secondary">
                            Cambiar fecha
                        </x-button>
                    </noscript>
                </form>
            </div>

            <!-- Horarios disponibles -->
            <form method="POST" action="{{ route('panel.turnos.reprogramar.update', $turno) }}" class="space-y-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="fecha" value="{{ $fecha->toDateString() }}">

                <div>
                    <h2 class="text-xs font-semibold text-ink-900 uppercase tracking-wider mb-3">
                        Horarios del {{ ucfirst($fecha->translatedFormat('l j \d\e F')) }}
                    </h2>

                    @if (empty($horarios))
                        <p class="text-sm text-ink-500 py-4">
                            No hay horarios disponibles para esta fecha.
                        </p>
                    @else
                        <x-slot-picker
                            name="hora"
                            :horarios="$horarios"
                            :value="old('hora')"
                            x-on:change="horaSeleccionada = $event.target.value"
                        />
                    @endif

                    @error('hora')
                        <p class="text-xs text-danger-700 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-line">
                    <x-button
                        :href="route('panel.turnos.show', $turno)"
                        variant="secondary"
                    >
                        Volver
                    </x-button>

                    <x-button
                        type="submit"
                        variant="primary"
                        x-bind:disabled="! horaSeleccionada"
                    >
                        <span x-show="! horaSeleccionada">Reprogramar turno</span>
                        <span x-show="horaSeleccionada" x-cloak>
                            Reprogramar a {{ \App\Support\Fecha::corta($fecha) }}, <span x-text="horaSeleccionada"></span>
                        </span>
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.panel>
