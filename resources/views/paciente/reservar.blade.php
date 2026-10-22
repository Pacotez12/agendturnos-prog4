<x-layouts.paciente titulo="Reservar turno">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold font-display text-ink-900">Reservar turno</h1>
            <p class="text-sm text-ink-500 mt-1">
                Elegí la profesional, el servicio y el día para ver los horarios disponibles.
            </p>
        </div>

        <x-card :paciente="true">
            <form method="GET" action="{{ route('paciente.reservar') }}" class="space-y-5">
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
            <x-card :paciente="true">
                <h2 class="text-lg font-bold font-display text-ink-900 mb-4">
                    Horarios del {{ $fecha->translatedFormat('l j \d\e F') }}
                </h2>

                @if (collect($horarios)->contains('libre', true))
                    <x-slot-picker name="hora" :horarios="$horarios" />
                @else
                    <x-empty-state
                        titulo="No hay horarios para este día"
                        texto="Probá con otra fecha."
                    />
                @endif
            </x-card>
        @endif
    </div>
</x-layouts.paciente>
