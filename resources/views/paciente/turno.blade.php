<x-layouts.paciente titulo="Turno solicitado">
    <div class="max-w-xl mx-auto py-4 sm:py-8">
        <x-card :paciente="true" class="p-6 sm:p-8 space-y-6">
            <div class="flex flex-col items-center text-center space-y-3">
                <div class="size-16 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center">
                    <x-phosphor-check class="size-8" aria-hidden="true" />
                </div>
                <h1 class="text-2xl font-bold font-display text-ink-900">
                    Tu turno fue solicitado
                </h1>
                <x-estado-turno :estado="$turno->estado" />
            </div>

            <div class="border-t border-b border-line py-4 divide-y divide-line/60 text-sm">
                <div class="py-2.5 flex justify-between gap-4">
                    <span class="text-ink-500 font-medium">Día</span>
                    <span class="font-semibold text-ink-900 text-right">
                        {{ ucfirst($turno->inicio->translatedFormat('l j \d\e F, H:i')) }}
                    </span>
                </div>
                <div class="py-2.5 flex justify-between gap-4">
                    <span class="text-ink-500 font-medium">Profesional</span>
                    <span class="font-semibold text-ink-900 text-right">
                        {{ $turno->profesional->nombre }} {{ $turno->profesional->apellido }}
                    </span>
                </div>
                <div class="py-2.5 flex justify-between gap-4">
                    <span class="text-ink-500 font-medium">Servicio</span>
                    <span class="font-semibold text-ink-900 text-right">
                        {{ $turno->servicio->nombre }} · {{ $turno->servicio->duracion_min }} min
                    </span>
                </div>
                <div class="py-2.5 flex justify-between gap-4">
                    <span class="text-ink-500 font-medium">Paciente</span>
                    <span class="font-semibold text-ink-900 text-right">
                        {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }}
                    </span>
                </div>
            </div>

            <p class="text-xs text-ink-500 text-center">
                La clínica lo revisa y lo confirma. Vas a ver el cambio de estado en tu inicio.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                <x-button :pill="true" href="{{ route('paciente.inicio') }}" class="w-full sm:w-auto">
                    Volver al inicio
                </x-button>
                <x-button variant="secondary" :pill="true" href="{{ route('paciente.reservar') }}" class="w-full sm:w-auto">
                    Reservar otro turno
                </x-button>
            </div>
        </x-card>
    </div>
</x-layouts.paciente>
