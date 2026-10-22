<x-layouts.paciente titulo="Mis turnos">
    <div class="max-w-xl mx-auto space-y-6">
        <div>
            <h1 class="text-2xl font-bold font-display text-ink-900">
                Mis turnos
            </h1>
            <p class="text-sm text-ink-500 mt-1">
                Consultá tus turnos y los de tus familiares a cargo.
            </p>
        </div>

        <!-- Pestañas -->
        <div class="flex items-center gap-2">
            <a
                href="{{ route('paciente.turnos') }}"
                class="rounded-full px-4 py-1.5 text-xs font-semibold border transition-colors {{ $ver === 'proximos' ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-ink-900 border-line hover:bg-cream-50' }}"
            >
                Próximos ({{ $proximosCount }})
            </a>
            <a
                href="{{ route('paciente.turnos', ['ver' => 'historial']) }}"
                class="rounded-full px-4 py-1.5 text-xs font-semibold border transition-colors {{ $ver === 'historial' ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-ink-900 border-line hover:bg-cream-50' }}"
            >
                Historial
            </a>
        </div>

        @if ($turnos->isEmpty())
            <x-card :paciente="true">
                <x-empty-state
                    :titulo="$ver === 'proximos' ? 'No tenés turnos próximos' : 'No tenés turnos en tu historial'"
                    texto="Reservá tu próxima consulta en la clínica."
                >
                    <x-button :href="route('paciente.reservar')" :pill="true">
                        Reservar un turno
                    </x-button>
                </x-empty-state>
            </x-card>
        @else
            <div class="space-y-4">
                @foreach ($turnos as $turno)
                    <x-card :paciente="true" class="space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <b class="text-sm sm:text-base font-bold text-ink-900 block">
                                    {{ \App\Support\Fecha::corta($turno->inicio) }}, {{ $turno->inicio->format('H:i') }}
                                </b>
                                <p class="text-xs sm:text-sm text-ink-900 mt-0.5">
                                    {{ $turno->servicio->nombre }} · {{ $turno->profesional->nombre }} {{ $turno->profesional->apellido }}
                                </p>
                                <p class="text-xs text-ink-500 mt-0.5">
                                    Para: {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }}
                                </p>
                                @if ($turno->estado === \App\Enums\EstadoTurno::Cancelado && $turno->motivo_cancelacion)
                                    <p class="text-xs text-danger-700 font-medium mt-1">
                                        Motivo: {{ $turno->motivo_cancelacion }}
                                    </p>
                                @endif
                            </div>
                            <div class="shrink-0">
                                <x-estado-turno :estado="$turno->estado" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-line text-xs font-bold">
                            <a href="{{ route('paciente.turnos.show', $turno) }}" class="text-brand-600 hover:text-brand-700">
                                Ver detalle
                            </a>

                            @if (in_array($turno->estado, [\App\Enums\EstadoTurno::Solicitado, \App\Enums\EstadoTurno::Confirmado], true))
                                @if ($agenda->puedeCancelarPaciente($turno))
                                    <button
                                        type="button"
                                        x-data
                                        x-on:click="$dispatch('abrir-modal', 'modal-cancelar-{{ $turno->id }}')"
                                        class="text-danger-700 hover:underline cursor-pointer"
                                    >
                                        Cancelar
                                    </button>
                                @elseif ($turno->inicio->isFuture())
                                    <a
                                        href="https://wa.me/{{ config('services.whatsapp.numero') }}?text={{ urlencode('Hola, necesito cancelar mi turno del ' . \App\Support\Fecha::corta($turno->inicio) . ' ' . $turno->inicio->format('H:i')) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="text-ink-500 hover:text-brand-600 font-normal hover:underline inline-flex items-center gap-1"
                                    >
                                        <x-phosphor-whatsapp-logo class="size-3.5 text-whatsapp" aria-hidden="true" />
                                        <span>Para cancelar, escribinos por WhatsApp</span>
                                    </a>
                                @endif
                            @endif
                        </div>
                    </x-card>

                    @if (in_array($turno->estado, [\App\Enums\EstadoTurno::Solicitado, \App\Enums\EstadoTurno::Confirmado], true) && $agenda->puedeCancelarPaciente($turno))
                        <x-modal name="modal-cancelar-{{ $turno->id }}" titulo="¿Cancelar el turno?">
                            <p class="text-sm text-ink-500 mb-6">
                                {{ \App\Support\Fecha::corta($turno->inicio) }}, {{ $turno->inicio->format('H:i') }} · {{ $turno->servicio->nombre }} con {{ $turno->profesional->nombre }} {{ $turno->profesional->apellido }}. El horario queda libre para otra persona.
                            </p>

                            <form method="POST" action="{{ route('paciente.turnos.cancelar', $turno) }}" class="space-y-3">
                                @csrf
                                <x-button type="submit" variant="danger" :pill="true" class="w-full">
                                    Sí, cancelar turno
                                </x-button>
                                <x-button
                                    type="button"
                                    variant="secondary"
                                    :pill="true"
                                    class="w-full"
                                    x-on:click="$dispatch('cerrar-modal', 'modal-cancelar-{{ $turno->id }}')"
                                >
                                    Volver
                                </x-button>
                            </form>
                        </x-modal>
                    @endif
                @endforeach
            </div>

            @if ($ver === 'historial' && $turnos instanceof \Illuminate\Contracts\Pagination\Paginator)
                <div class="pt-2">
                    {{ $turnos->links() }}
                </div>
            @endif
        @endif

        <div class="pt-2">
            <x-button :href="route('paciente.reservar')" :pill="true" class="w-full">
                Reservar un turno
            </x-button>
        </div>
    </div>
</x-layouts.paciente>
