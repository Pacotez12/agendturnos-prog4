<x-layouts.panel titulo="Solicitudes">
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-ink-900">
                    Solicitudes por confirmar
                </h1>
                <p class="text-sm text-ink-500 mt-1">
                    Más próximas primero. Confirmá o rechazá las reservas de los pacientes.
                </p>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-line overflow-hidden shadow-2xs">
            @if ($solicitudes->isEmpty())
                <div class="p-8">
                    <x-empty-state
                        titulo="No hay solicitudes pendientes"
                        texto="Todas las solicitudes han sido confirmadas o atendidas."
                    />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-ink-900 border-collapse">
                        <thead class="bg-surface border-b border-line text-xs font-semibold uppercase tracking-wider text-ink-500">
                            <tr>
                                <th class="px-4 py-3">Turno</th>
                                <th class="px-4 py-3">Paciente</th>
                                <th class="px-4 py-3">Servicio</th>
                                @if ($esFuncionario)
                                    <th class="px-4 py-3">Profesional</th>
                                @endif
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($solicitudes as $solicitud)
                                <tr class="hover:bg-surface/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('panel.turnos.show', $solicitud) }}" class="font-semibold text-ink-900 hover:text-brand-600 block">
                                            {{ ucfirst($solicitud->inicio->translatedFormat('D j M')) }}
                                        </a>
                                        <span class="text-xs text-ink-500">
                                            {{ $solicitud->inicio->format('H:i') }} – {{ $solicitud->fin->format('H:i') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-medium text-ink-900 block">
                                            {{ $solicitud->paciente->nombre }} {{ $solicitud->paciente->apellido }}
                                        </span>
                                        <span class="text-xs text-ink-500">
                                            {{ $solicitud->created_at->diffForHumans() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-ink-900">
                                        {{ $solicitud->servicio->nombre }}
                                    </td>
                                    @if ($esFuncionario)
                                        <td class="px-4 py-3 text-ink-900">
                                            {{ $solicitud->profesional->nombre }} {{ $solicitud->profesional->apellido }}
                                        </td>
                                    @endif
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a
                                                href="{{ route('panel.turnos.show', $solicitud) }}"
                                                class="text-xs font-medium text-ink-500 hover:text-ink-900 px-2 py-1 rounded min-h-9 inline-flex items-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                            >
                                                Ver
                                            </a>
                                            <form method="POST" action="{{ route('panel.turnos.confirmar', $solicitud) }}" class="inline">
                                                @csrf
                                                <x-button type="submit" variant="primary">
                                                    Confirmar
                                                </x-button>
                                            </form>
                                            <button
                                                type="button"
                                                @click="$dispatch('abrir-modal', 'rechazar-solicitud-{{ $solicitud->id }}')"
                                                class="text-xs font-bold text-danger-700 px-2 py-1 rounded min-h-9 inline-flex items-center cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danger-700"
                                            >
                                                Rechazar
                                            </button>
                                        </div>

                                        <x-modal name="rechazar-solicitud-{{ $solicitud->id }}" titulo="¿Rechazar la solicitud?">
                                            <p class="text-sm text-ink-500 mb-4">
                                                {{ $solicitud->paciente->nombre }} {{ $solicitud->paciente->apellido }} · {{ ucfirst($solicitud->inicio->translatedFormat('D j M, H:i')) }} · {{ $solicitud->servicio->nombre }}. El turno queda cancelado y el horario se libera.
                                            </p>
                                            <form method="POST" action="{{ route('panel.turnos.cancelar', $solicitud) }}" class="space-y-4">
                                                @csrf
                                                <div class="text-left">
                                                    <x-input
                                                        name="motivo"
                                                        :id="'motivo-'.$solicitud->id"
                                                        label="Motivo (opcional, lo ve el paciente)"
                                                        maxlength="255"
                                                        placeholder="Ej.: la profesional no atiende ese día"
                                                    />
                                                </div>
                                                <div class="flex justify-end gap-2 pt-2">
                                                    <x-button
                                                        type="button"
                                                        variant="secondary"
                                                        @click="$dispatch('cerrar-modal', 'rechazar-solicitud-{{ $solicitud->id }}')"
                                                    >
                                                        Volver
                                                    </x-button>
                                                    <x-button type="submit" variant="danger">
                                                        Rechazar solicitud
                                                    </x-button>
                                                </div>
                                            </form>
                                        </x-modal>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-layouts.panel>
