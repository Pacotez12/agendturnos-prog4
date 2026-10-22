<x-layouts.panel :titulo="'Turno de ' . $turno->paciente->nombre . ' ' . $turno->paciente->apellido">
    <div class="space-y-6">
        <!-- Encabezado con estado -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold font-display text-ink-900">
                        Turno de {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }}
                    </h1>
                    <x-estado-turno :estado="$turno->estado" />
                </div>
                <p class="text-sm text-ink-500 mt-1">
                    Detalle del turno e historial de cambios de estado.
                </p>
            </div>
            <div>
                <a
                    href="{{ route('panel.solicitudes') }}"
                    class="text-sm font-semibold text-brand-600 hover:text-brand-700 min-h-11 inline-flex items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-lg"
                >
                    &larr; Volver a solicitudes
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Columna principal (detalles y acciones) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Tarjeta Turno -->
                <x-card>
                    <h2 class="text-xs font-semibold text-ink-500 uppercase tracking-wider mb-4">
                        Información del turno
                    </h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                        <div>
                            <dt class="text-xs text-ink-500">Día y horario</dt>
                            <dd class="font-semibold text-ink-900 mt-0.5">
                                {{ ucfirst($turno->inicio->translatedFormat('l j \d\e F')) }}
                                <br>
                                <span class="text-xs font-normal text-ink-500">
                                    {{ $turno->inicio->format('H:i') }} – {{ $turno->fin->format('H:i') }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">Servicio</dt>
                            <dd class="font-semibold text-ink-900 mt-0.5">
                                {{ $turno->servicio->nombre }}
                                <br>
                                <span class="text-xs font-normal text-ink-500">
                                    {{ $turno->servicio->duracion_min }} min
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">Profesional y consultorio</dt>
                            <dd class="font-semibold text-ink-900 mt-0.5">
                                {{ $turno->profesional->nombre }} {{ $turno->profesional->apellido }}
                                <br>
                                <span class="text-xs font-normal text-ink-500">
                                    Consultorio {{ $turno->consultorio?->nombre ?? '—' }}
                                </span>
                            </dd>
                        </div>
                    </dl>

                    @if ($turno->motivo_cancelacion)
                        <div class="mt-4 pt-4 border-t border-line">
                            <p class="text-xs text-ink-500">Motivo de cancelación</p>
                            <p class="text-sm font-medium text-danger-700 mt-0.5">
                                {{ $turno->motivo_cancelacion }}
                            </p>
                        </div>
                    @endif
                </x-card>

                <!-- Tarjeta Paciente -->
                <x-card>
                    <h2 class="text-xs font-semibold text-ink-500 uppercase tracking-wider mb-4">
                        Datos del paciente
                    </h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                        <div>
                            <dt class="text-xs text-ink-500">Nombre completo</dt>
                            <dd class="font-semibold text-ink-900 mt-0.5">
                                {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">Documento</dt>
                            <dd class="font-semibold text-ink-900 mt-0.5">
                                {{ $turno->paciente->tipo_documento ?? 'CI' }} {{ $turno->paciente->documento }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-500">Teléfono</dt>
                            <dd class="font-semibold text-ink-900 mt-0.5">
                                {{ $turno->paciente->telefono ?? '—' }}
                            </dd>
                        </div>
                    </dl>
                </x-card>

                <!-- Acciones según el estado -->
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    @if ($turno->estado === \App\Enums\EstadoTurno::Solicitado)
                        <form method="POST" action="{{ route('panel.turnos.confirmar', $turno) }}" class="inline">
                            @csrf
                            <x-button type="submit" variant="primary">
                                Confirmar turno
                            </x-button>
                        </form>

                        <x-button :href="route('panel.turnos.reprogramar', $turno)" variant="secondary">
                            Reprogramar
                        </x-button>

                        <button
                            type="button"
                            @click="$dispatch('abrir-modal', 'modal-rechazar-turno')"
                            class="text-sm font-bold text-danger-700 px-3 py-2 rounded-lg min-h-11 inline-flex items-center cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danger-700"
                        >
                            Rechazar solicitud
                        </button>

                        <x-modal name="modal-rechazar-turno" titulo="¿Rechazar la solicitud?">
                            <p class="text-sm text-ink-500 mb-4">
                                {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }} · {{ \App\Support\Fecha::corta($turno->inicio) }}, {{ $turno->inicio->format('H:i') }} · {{ $turno->servicio->nombre }}. El turno queda cancelado y el horario se libera.
                            </p>
                            <form method="POST" action="{{ route('panel.turnos.cancelar', $turno) }}" class="space-y-4">
                                @csrf
                                <div class="text-left">
                                    <x-input
                                        name="motivo"
                                        :id="'motivo-rechazo-'.$turno->id"
                                        label="Motivo (opcional, lo ve el paciente)"
                                        maxlength="255"
                                        placeholder="Ej.: la profesional no atiende ese día"
                                    />
                                </div>
                                <div class="flex justify-end gap-2 pt-2">
                                    <x-button
                                        type="button"
                                        variant="secondary"
                                        @click="$dispatch('cerrar-modal', 'modal-rechazar-turno')"
                                    >
                                        Volver
                                    </x-button>
                                    <x-button type="submit" variant="danger">
                                        Rechazar solicitud
                                    </x-button>
                                </div>
                            </form>
                        </x-modal>
                    @elseif ($turno->estado === \App\Enums\EstadoTurno::Confirmado)
                        @if ($turno->inicio->lte(now()))
                            <form method="POST" action="{{ route('panel.turnos.atendido', $turno) }}" class="inline">
                                @csrf
                                <x-button type="submit" variant="primary">
                                    Marcar atendido
                                </x-button>
                            </form>

                            <form method="POST" action="{{ route('panel.turnos.ausente', $turno) }}" class="inline">
                                @csrf
                                <x-button type="submit" variant="secondary">
                                    Marcar ausente
                                </x-button>
                            </form>
                        @endif

                        <x-button :href="route('panel.turnos.reprogramar', $turno)" variant="secondary">
                            Reprogramar
                        </x-button>

                        <button
                            type="button"
                            @click="$dispatch('abrir-modal', 'modal-cancelar-turno')"
                            class="text-sm font-bold text-danger-700 px-3 py-2 rounded-lg min-h-11 inline-flex items-center cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danger-700"
                        >
                            Cancelar turno
                        </button>

                        <x-modal name="modal-cancelar-turno" titulo="¿Cancelar el turno?">
                            <p class="text-sm text-ink-500 mb-4">
                                {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }} · {{ \App\Support\Fecha::corta($turno->inicio) }}, {{ $turno->inicio->format('H:i') }} · {{ $turno->servicio->nombre }}. El turno queda cancelado y el horario se libera.
                            </p>
                            <form method="POST" action="{{ route('panel.turnos.cancelar', $turno) }}" class="space-y-4">
                                @csrf
                                <div class="text-left">
                                    <x-input
                                        name="motivo"
                                        :id="'motivo-cancelacion-'.$turno->id"
                                        label="Motivo (opcional, lo ve el paciente)"
                                        maxlength="255"
                                        placeholder="Ej.: cancelación por parte del paciente"
                                    />
                                </div>
                                <div class="flex justify-end gap-2 pt-2">
                                    <x-button
                                        type="button"
                                        variant="secondary"
                                        @click="$dispatch('cerrar-modal', 'modal-cancelar-turno')"
                                    >
                                        Volver
                                    </x-button>
                                    <x-button type="submit" variant="danger">
                                        Cancelar turno
                                    </x-button>
                                </div>
                            </form>
                        </x-modal>
                    @endif
                </div>
            </div>

            <!-- Columna lateral (Historial) -->
            <div class="lg:col-span-1">
                <x-card>
                    <h2 class="text-xs font-semibold text-ink-500 uppercase tracking-wider mb-4">
                        Historial de cambios
                    </h2>
                    @if ($turno->historial->isEmpty())
                        <p class="text-xs text-ink-500">Sin historial registrado.</p>
                    @else
                        <ul class="relative border-l-2 border-line ml-2 space-y-4">
                            @foreach ($turno->historial as $h)
                                <li class="relative pl-4">
                                    <span class="absolute -left-[5px] top-1.5 size-2 rounded-full bg-brand-600"></span>
                                    <p class="text-sm font-semibold text-ink-900">
                                        {{ ucfirst($h->estado_nuevo->value) }}
                                    </p>
                                    <p class="text-xs text-ink-500">
                                        {{ $h->created_at->format('d/m H:i') }} · {{ $h->usuario?->nombreVisible() ?? 'Sistema' }}
                                    </p>
                                    @if ($h->detalle)
                                        <p class="text-xs text-ink-900 bg-surface rounded p-1.5 mt-1 border border-line/60">
                                            {{ $h->detalle }}
                                        </p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            </div>
        </div>
    </div>
</x-layouts.panel>
