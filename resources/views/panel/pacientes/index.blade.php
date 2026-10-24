<x-layouts.panel titulo="Pacientes">
    <div class="space-y-6">
        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold font-display text-ink-900">
                        Pacientes
                    </h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-100 text-brand-text">
                        {{ $total }}
                    </span>
                </div>
                <p class="text-sm text-ink-500 mt-1">
                    Directorio de pacientes atendidos en la clínica.
                </p>
            </div>
            <div>
                <x-button :href="route('panel.turnos.crear')">
                    Nuevo turno
                </x-button>
            </div>
        </div>

        <!-- Buscador -->
        <div class="bg-card p-4 rounded-lg border border-line shadow-2xs">
            <form method="GET" action="{{ route('panel.pacientes') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <div class="relative flex-1">
                    <x-input
                        name="q"
                        label="Buscar paciente"
                        placeholder="Buscar por apellido, nombre o documento (mínimo 3 letras)..."
                        :value="$q"
                        class="w-full"
                    />
                </div>
                <div class="flex items-end gap-2">
                    <x-button type="submit" variant="secondary" class="w-full sm:w-auto">
                        <x-phosphor-magnifying-glass class="size-4 shrink-0 text-brand-text" aria-hidden="true" />
                        <span>Buscar</span>
                    </x-button>
                    @if (! empty($q))
                        <a
                            href="{{ route('panel.pacientes') }}"
                            class="text-xs text-ink-500 hover:text-ink-900 py-2.5 px-3 rounded-lg border border-line bg-card hover:bg-surface inline-flex items-center min-h-11 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 transition-colors"
                        >
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Listado -->
        <div class="bg-card rounded-lg border border-line overflow-hidden shadow-2xs">
            @if ($pacientes->isEmpty())
                <div class="p-8">
                    <x-empty-state
                        titulo="No se encontraron pacientes"
                        texto="{{ !empty($q) ? 'Probá con otro término de búsqueda.' : 'Aún no hay pacientes registrados.' }}"
                    />
                </div>
            @else
                <!-- Tabla para escritorio -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-left text-sm text-ink-900 border-collapse">
                        <thead class="bg-surface border-b border-line text-xs font-semibold uppercase tracking-wider text-ink-500">
                            <tr>
                                <th class="px-4 py-3">Paciente</th>
                                <th class="px-4 py-3">Documento</th>
                                <th class="px-4 py-3">Teléfono</th>
                                <th class="px-4 py-3">Próximo turno</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($pacientes as $paciente)
                                @php
                                    $familiaresCount = $paciente->user?->dependientes?->count() ?? 0;
                                    $proximoTurno = $paciente->turnos->first();
                                @endphp
                                <tr class="hover:bg-surface/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-ink-900">
                                            {{ $paciente->nombre }} {{ $paciente->apellido }}
                                        </p>
                                        <p class="text-xs text-ink-500 mt-0.5">
                                            @if ($paciente->user_id)
                                                Con cuenta{{ $familiaresCount > 0 ? " · {$familiaresCount} " . ($familiaresCount === 1 ? 'familiar' : 'familiares') : '' }}
                                            @elseif ($paciente->responsable_id)
                                                A cargo de {{ $paciente->responsable?->paciente?->nombreCompleto() ?? $paciente->responsable?->nombreVisible() }}
                                            @else
                                                Sin cuenta (cargado por teléfono)
                                            @endif
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 text-ink-500 whitespace-nowrap">
                                        {{ $paciente->tipo_documento }} {{ $paciente->documento }}
                                    </td>
                                    <td class="px-4 py-3 text-ink-500 whitespace-nowrap">
                                        {{ \App\Support\Telefono::legible($paciente->telefono) ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-ink-900 whitespace-nowrap">
                                        @if ($proximoTurno)
                                            <span class="font-medium">{{ \App\Support\Fecha::corta($proximoTurno->inicio) }}</span>
                                            <span class="text-xs text-ink-500">{{ $proximoTurno->inicio->format('H:i') }}</span>
                                        @else
                                            <span class="text-ink-500">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a
                                            href="{{ route('panel.pacientes.show', $paciente) }}"
                                            class="text-xs font-semibold text-brand-text hover:text-brand-700 p-2 rounded-lg hover:bg-cream-50 inline-flex items-center min-h-11 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 transition-colors"
                                        >
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Tarjetas para móvil -->
                <div class="sm:hidden divide-y divide-line">
                    @foreach ($pacientes as $paciente)
                        @php
                            $familiaresCount = $paciente->user?->dependientes?->count() ?? 0;
                            $proximoTurno = $paciente->turnos->first();
                        @endphp
                        <div class="p-4 space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-bold text-ink-900 text-base">
                                        {{ $paciente->nombre }} {{ $paciente->apellido }}
                                    </p>
                                    <p class="text-xs text-ink-500">
                                        @if ($paciente->user_id)
                                            Con cuenta{{ $familiaresCount > 0 ? " · {$familiaresCount} " . ($familiaresCount === 1 ? 'familiar' : 'familiares') : '' }}
                                        @elseif ($paciente->responsable_id)
                                            A cargo de {{ $paciente->responsable?->paciente?->nombreCompleto() ?? $paciente->responsable?->nombreVisible() }}
                                        @else
                                            Sin cuenta (cargado por teléfono)
                                        @endif
                                    </p>
                                </div>
                                <a
                                    href="{{ route('panel.pacientes.show', $paciente) }}"
                                    class="text-xs font-semibold text-brand-text hover:text-brand-700 px-3 py-1.5 rounded-lg border border-line hover:bg-surface shrink-0 min-h-11 inline-flex items-center"
                                >
                                    Ver
                                </a>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs pt-1 border-t border-line/60">
                                <div>
                                    <span class="text-ink-500 block">Documento:</span>
                                    <span class="font-medium text-ink-900">{{ $paciente->tipo_documento }} {{ $paciente->documento }}</span>
                                </div>
                                <div>
                                    <span class="text-ink-500 block">Teléfono:</span>
                                    <span class="font-medium text-ink-900">{{ \App\Support\Telefono::legible($paciente->telefono) ?? '—' }}</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-ink-500 block">Próximo turno:</span>
                                    @if ($proximoTurno)
                                        <span class="font-medium text-ink-900">{{ \App\Support\Fecha::corta($proximoTurno->inicio) }} {{ $proximoTurno->inicio->format('H:i') }}</span>
                                    @else
                                        <span class="text-ink-500">—</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($pacientes->hasPages())
                    <div class="p-4 border-t border-line">
                        {{ $pacientes->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-layouts.panel>
