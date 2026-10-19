<x-layouts.panel titulo="Especialidades">
    <div class="space-y-6">
        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-ink-900">
                    Especialidades
                </h1>
                <p class="text-sm text-ink-500 mt-1">
                    Gestioná las especialidades odontológicas de la clínica.
                </p>
            </div>
            <div>
                <x-button :href="route('panel.especialidades.create')">
                    + Nueva especialidad
                </x-button>
            </div>
        </div>

        <!-- Tabla de especialidades -->
        <div class="bg-white rounded-lg border border-line overflow-hidden shadow-2xs">
            @if ($especialidades->isEmpty())
                <div class="p-8">
                    <x-empty-state
                        titulo="No hay especialidades registradas"
                        texto="Creá una nueva especialidad para comenzar a clasificar los servicios."
                    />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-ink-900 border-collapse">
                        <thead class="bg-surface border-b border-line text-xs font-semibold uppercase tracking-wider text-ink-500">
                            <tr>
                                <th class="px-4 py-3">Especialidad</th>
                                <th class="px-4 py-3">Servicios</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($especialidades as $esp)
                                <tr class="hover:bg-surface/50 transition-colors">
                                    <td class="px-4 py-3 font-semibold text-ink-900">
                                        {{ $esp->nombre }}
                                    </td>
                                    <td class="px-4 py-3 text-ink-500">
                                        {{ $esp->servicios_count }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($esp->activa)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                Activa
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                                Inactiva
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a
                                            href="{{ route('panel.especialidades.edit', $esp) }}"
                                            class="text-xs font-semibold text-brand-600 hover:text-brand-700 p-2 rounded-lg hover:bg-cream-50 inline-flex items-center min-h-11 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                        >
                                            Editar
                                        </a>
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
