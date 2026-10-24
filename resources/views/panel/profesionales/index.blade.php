<x-layouts.panel titulo="Profesionales">
    <div class="space-y-6">
        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-ink-900">
                    Profesionales
                </h1>
                <p class="text-sm text-ink-500 mt-1">
                    Gestioná las profesionales de la clínica y los tratamientos que ofrece cada una.
                </p>
            </div>
        </div>

        <!-- Tabla de profesionales -->
        <div class="bg-card rounded-lg border border-line overflow-hidden shadow-2xs">
            @if ($profesionales->isEmpty())
                <div class="p-8">
                    <x-empty-state
                        titulo="No hay profesionales registrados"
                        texto="No se encontraron profesionales activos en el sistema."
                    />
                </div>
            @else
                <!-- Tabla para escritorio -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-left text-sm text-ink-900 border-collapse">
                        <thead class="bg-surface border-b border-line text-xs font-semibold uppercase tracking-wider text-ink-500">
                            <tr>
                                <th class="px-4 py-3">Profesional</th>
                                <th class="px-4 py-3">Matrícula</th>
                                <th class="px-4 py-3">Servicios que ofrece</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($profesionales as $prof)
                                <tr class="hover:bg-surface/50 transition-colors">
                                    <td class="px-4 py-3 font-semibold text-ink-900">
                                        {{ $prof->nombre }} {{ $prof->apellido }}
                                    </td>
                                    <td class="px-4 py-3 text-ink-500 font-mono text-xs">
                                        {{ $prof->matricula }}
                                    </td>
                                    <td class="px-4 py-3 text-ink-500">
                                        {{ $prof->servicios_count }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a
                                            href="{{ route('panel.profesionales.servicios', $prof) }}"
                                            class="text-xs font-semibold text-brand-text hover:text-brand-700 p-2 rounded-lg hover:bg-cream-50 inline-flex items-center min-h-11 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                        >
                                            Servicios que ofrece
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Tarjetas para móvil -->
                <div class="sm:hidden divide-y divide-line">
                    @foreach ($profesionales as $prof)
                        <div class="p-4 space-y-2">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-bold text-ink-900 text-base">
                                        {{ $prof->nombre }} {{ $prof->apellido }}
                                    </p>
                                    <p class="text-xs text-ink-500 font-mono mt-0.5">
                                        Matrícula: {{ $prof->matricula }}
                                    </p>
                                </div>
                                <a
                                    href="{{ route('panel.profesionales.servicios', $prof) }}"
                                    class="text-xs font-semibold text-brand-text hover:text-brand-700 p-2 rounded-lg hover:bg-cream-50 inline-flex items-center min-h-11 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 shrink-0 text-right"
                                >
                                    Servicios que ofrece
                                </a>
                            </div>
                            <div class="text-xs text-ink-500">
                                {{ $prof->servicios_count }} {{ $prof->servicios_count === 1 ? 'servicio asignado' : 'servicios asignados' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.panel>
