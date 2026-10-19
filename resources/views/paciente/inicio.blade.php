<x-layouts.paciente titulo="Inicio">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold font-display text-ink-900">
                @if (auth()->user()?->paciente?->nombre)
                    Hola, {{ auth()->user()->paciente->nombre }}
                @else
                    Hola
                @endif
            </h1>
            <p class="text-sm text-ink-500 mt-1">
                Bienvenido a tu portal de turnos odontológicos.
            </p>
        </div>

        <x-empty-state
            titulo="Todavía no tenés turnos"
            texto="Pronto vas a poder reservar desde acá."
        />
    </div>
</x-layouts.paciente>
