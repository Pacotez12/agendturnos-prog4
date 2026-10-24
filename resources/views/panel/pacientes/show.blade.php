<x-layouts.panel :titulo="$paciente->nombreCompleto()">
    <div
        class="space-y-6 max-w-5xl"
        x-data
        @if ($errors->any()) x-init="$nextTick(() => $dispatch('abrir-modal', 'editar-contacto'))" @endif
    >
        <!-- Migas / Volver -->
        <div>
            <a
                href="{{ route('panel.pacientes') }}"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-text hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded"
            >
                <x-phosphor-arrow-left class="size-3.5 shrink-0" aria-hidden="true" />
                <span>Volver a pacientes</span>
            </a>
        </div>

        <!-- Encabezado de la ficha -->
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 border-b border-line pb-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold font-display text-ink-900 tracking-tight">
                    {{ $paciente->nombreCompleto() }}
                </h1>
                <p class="text-sm text-ink-500 mt-1">
                    Paciente desde {{ $paciente->created_at->translatedFormat('F Y') }} · {{ $paciente->user_id ? 'Con cuenta' : 'Sin cuenta' }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-button
                    type="button"
                    variant="secondary"
                    x-on:click="$dispatch('abrir-modal', 'editar-contacto')"
                >
                    <x-phosphor-pencil-simple class="size-4 shrink-0 text-brand-text" aria-hidden="true" />
                    <span>Editar contacto</span>
                </x-button>

                <x-button :href="route('panel.turnos.crear', ['paciente' => $paciente->id])">
                    <x-phosphor-plus class="size-4 shrink-0" aria-hidden="true" />
                    <span>Nuevo turno</span>
                </x-button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Tarjeta Datos -->
            <x-card class="space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-ink-500 flex items-center gap-2">
                    <x-phosphor-user class="size-4 text-brand-text" aria-hidden="true" />
                    <span>Datos del paciente</span>
                </h2>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs text-ink-500">Documento</dt>
                        <dd class="font-semibold text-ink-900 mt-0.5">
                            {{ $paciente->tipo_documento }} {{ $paciente->documento }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-ink-500">Fecha de nacimiento</dt>
                        <dd class="font-semibold text-ink-900 mt-0.5">
                            {{ $paciente->fecha_nacimiento->format('d/m/Y') }} ({{ $paciente->fecha_nacimiento->age }} años)
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-ink-500">Teléfono</dt>
                        <dd class="font-semibold text-ink-900 mt-0.5">
                            {{ \App\Support\Telefono::legible($paciente->telefono) ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-ink-500">Correo electrónico</dt>
                        <dd class="font-semibold text-ink-900 mt-0.5">
                            @if ($paciente->user?->email)
                                {{ $paciente->user->email }}
                            @elseif ($paciente->responsable?->email)
                                {{ $paciente->responsable->email }}
                                <span class="text-xs font-normal text-ink-500">(responsable)</span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>

            <!-- Tarjeta Familia -->
            <x-card class="space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-ink-500 flex items-center gap-2">
                    <x-phosphor-users class="size-4 text-brand-text" aria-hidden="true" />
                    <span>Familia</span>
                </h2>

                @if ($paciente->user_id)
                    @if ($familiares->isNotEmpty())
                        <div class="space-y-2">
                            <p class="text-xs text-ink-500">Familiares a cargo:</p>
                            <div class="divide-y divide-line border border-line rounded-lg overflow-hidden">
                                @foreach ($familiares as $familiar)
                                    <div class="p-3 flex items-center justify-between hover:bg-surface/50 transition-colors">
                                        <div>
                                            <p class="font-semibold text-sm text-ink-900">
                                                {{ $familiar->nombreCompleto() }}
                                            </p>
                                            <p class="text-xs text-ink-500">
                                                {{ $familiar->fecha_nacimiento->age }} años · {{ $familiar->tipo_documento }} {{ $familiar->documento }}
                                            </p>
                                        </div>
                                        <a
                                            href="{{ route('panel.pacientes.show', $familiar) }}"
                                            class="text-xs font-semibold text-brand-text hover:text-brand-700 p-1.5 rounded-lg hover:bg-cream-50"
                                        >
                                            Ver ficha
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-ink-500 italic">
                            No tiene familiares a cargo registrados en su cuenta.
                        </p>
                    @endif
                @elseif ($paciente->responsable_id)
                    <div class="p-3 bg-surface rounded-lg border border-line">
                        <span class="text-xs text-ink-500 block">Responsable de la cuenta:</span>
                        <div class="flex items-center justify-between mt-1">
                            <div>
                                <span class="font-bold text-sm text-ink-900">
                                    A cargo de {{ $responsablePaciente?->nombreCompleto() ?? $paciente->responsable?->nombreVisible() }}
                                </span>
                                @if ($paciente->responsable?->email)
                                    <span class="text-xs text-ink-500 block">
                                        {{ $paciente->responsable->email }}
                                    </span>
                                @endif
                            </div>
                            @if ($responsablePaciente)
                                <a
                                    href="{{ route('panel.pacientes.show', $responsablePaciente) }}"
                                    class="text-xs font-semibold text-brand-text hover:text-brand-700 px-2 py-1 rounded hover:bg-cream-50"
                                >
                                    Ver titular
                                </a>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="text-sm text-ink-500 italic">
                        Paciente sin familiares a cargo ni responsable asociado.
                    </p>
                @endif
            </x-card>
        </div>

        <!-- Tarjeta Turnos -->
        <x-card class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-line pb-3">
                <h2 class="text-sm font-bold uppercase tracking-wider text-ink-500 flex items-center gap-2">
                    <x-phosphor-calendar class="size-4 text-brand-text" aria-hidden="true" />
                    <span>Historial de turnos</span>
                </h2>
                <div class="text-xs font-medium text-ink-500">
                    {{ $resumenTurnos['atendidos'] }} atendidos · {{ $resumenTurnos['ausentes'] }} ausentes · {{ $resumenTurnos['cancelados'] }} cancelados
                </div>
            </div>

            @if ($turnos->isEmpty())
                <div class="py-6">
                    <x-empty-state
                        titulo="Sin turnos registrados"
                        texto="Este paciente no tiene turnos registrados en la clínica."
                    />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-ink-900 border-collapse">
                        <thead class="bg-surface border-b border-line text-xs font-semibold uppercase tracking-wider text-ink-500">
                            <tr>
                                <th class="px-4 py-2.5">Fecha y hora</th>
                                <th class="px-4 py-2.5">Tratamiento · Profesional</th>
                                <th class="px-4 py-2.5">Estado</th>
                                <th class="px-4 py-2.5 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($turnos as $turno)
                                <tr class="hover:bg-surface/50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap font-medium text-ink-900">
                                        {{ \App\Support\Fecha::corta($turno->inicio) }}
                                        <span class="text-xs text-ink-500 font-normal block sm:inline sm:ml-1">
                                            {{ $turno->inicio->format('H:i') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-ink-900">
                                            {{ $turno->servicio->nombre }}
                                        </p>
                                        <p class="text-xs text-ink-500 mt-0.5">
                                            {{ $turno->profesional->nombre }} {{ $turno->profesional->apellido }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <x-estado-turno :estado="$turno->estado" />
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a
                                            href="{{ route('panel.turnos.show', $turno) }}"
                                            class="text-xs font-semibold text-brand-text hover:text-brand-700 p-2 rounded-lg hover:bg-cream-50 inline-flex items-center min-h-11 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 transition-colors"
                                        >
                                            Ver turno
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($turnos->hasPages())
                    <div class="pt-3 border-t border-line">
                        {{ $turnos->links() }}
                    </div>
                @endif
            @endif
        </x-card>
    </div>

    <!-- Modal Editar Contacto -->
    <x-modal name="editar-contacto" titulo="Editar datos de contacto">
        <form method="POST" action="{{ route('panel.pacientes.contacto.update', $paciente) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="space-y-4 text-left">
                <div>
                    <x-input
                        name="telefono"
                        label="Teléfono"
                        placeholder="0981 123 456"
                        :value="old('telefono', $paciente->telefono)"
                    />
                    <p class="text-xs text-ink-500 mt-1">
                        Formato paraguayo, por ejemplo 0981 123 456.
                    </p>
                </div>

                @if ($paciente->user_id)
                    <div class="p-3 bg-surface rounded-lg border border-line text-xs text-ink-500">
                        Este paciente tiene cuenta propia. Su correo electrónico de acceso (<span class="font-medium text-ink-900">{{ $paciente->user->email }}</span>) no se puede modificar desde aquí.
                    </div>
                @endif
            </div>

            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-3 border-t border-line">
                <x-button
                    type="button"
                    variant="secondary"
                    x-on:click="$dispatch('cerrar-modal', 'editar-contacto')"
                >
                    Cancelar
                </x-button>

                <x-button type="submit">
                    Guardar cambios
                </x-button>
            </div>
        </form>
    </x-modal>
</x-layouts.panel>
