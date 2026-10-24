<x-layouts.panel titulo="Servicios">
    <div class="space-y-6">
        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-display text-ink-900">
                    Administración de Servicios
                </h1>
                <p class="text-sm text-ink-500 mt-1">
                    Gestioná los tratamientos, duraciones, precios y estados del catálogo.
                </p>
            </div>
            <div>
                <x-button :href="route('panel.servicios.create')">
                    <x-phosphor-plus class="size-4" aria-hidden="true" />
                    Nuevo servicio
                </x-button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-card p-4 rounded-lg border border-line">
            <form method="GET" action="{{ route('panel.servicios') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="filtro-especialidad" class="block text-xs font-semibold text-ink-900 mb-1">
                        Especialidad
                    </label>
                    <select
                        id="filtro-especialidad"
                        name="especialidad"
                        class="block w-full min-h-11 rounded-lg border border-line bg-card px-3 py-2 text-sm text-ink-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                    >
                        <option value="">Todas las especialidades</option>
                        @foreach ($especialidades as $esp)
                            <option value="{{ $esp->id }}" @selected(request('especialidad') == $esp->id)>
                                {{ $esp->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filtro-estado" class="block text-xs font-semibold text-ink-900 mb-1">
                        Estado
                    </label>
                    <select
                        id="filtro-estado"
                        name="estado"
                        class="block w-full min-h-11 rounded-lg border border-line bg-card px-3 py-2 text-sm text-ink-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                    >
                        <option value="">Todos los estados</option>
                        <option value="activo" @selected(request('estado') === 'activo')>Activos</option>
                        <option value="inactivo" @selected(request('estado') === 'inactivo')>Inactivos</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <x-button type="submit" variant="secondary" class="w-full sm:w-auto">
                        Filtrar
                    </x-button>
                    @if (request()->hasAny(['especialidad', 'estado']))
                        <a
                            href="{{ route('panel.servicios') }}"
                            class="text-xs text-ink-500 hover:text-ink-900 py-3 px-2 inline-flex items-center"
                        >
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Tabla de servicios -->
        <div class="bg-card rounded-lg border border-line overflow-hidden shadow-2xs">
            @if ($servicios->isEmpty())
                <div class="p-8">
                    <x-empty-state
                        titulo="No hay servicios registrados"
                        texto="No se encontraron servicios que coincidan con los filtros seleccionados."
                    />
                </div>
            @else
                <!-- Tabla para escritorio -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-left text-sm text-ink-900 border-collapse">
                        <thead class="bg-surface border-b border-line text-xs font-semibold uppercase tracking-wider text-ink-500">
                            <tr>
                                <th class="px-4 py-3">Servicio</th>
                                <th class="px-4 py-3">Especialidad</th>
                                <th class="px-4 py-3">Duración</th>
                                <th class="px-4 py-3">Precio</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($servicios as $servicio)
                                <tr class="hover:bg-surface/50 transition-colors">
                                    <td class="px-4 py-3 font-semibold text-ink-900">
                                        {{ $servicio->nombre }}
                                        @if ($servicio->descripcion)
                                            <p class="text-xs font-normal text-ink-500 truncate max-w-xs mt-0.5">
                                                {{ $servicio->descripcion }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-ink-500">
                                        {{ $servicio->especialidad?->nombre ?? 'Sin especialidad' }}
                                    </td>
                                    <td class="px-4 py-3 text-ink-500">
                                        {{ $servicio->duracion_min }} min
                                    </td>
                                    <td class="px-4 py-3 text-ink-900 font-medium">
                                        @if ($servicio->precio !== null)
                                            Gs. {{ number_format($servicio->precio, 0, ',', '.') }}
                                        @else
                                            <span class="text-xs text-ink-500 italic">Consultar</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($servicio->activo)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                                Activo
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-stone-800 dark:text-stone-300">
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a
                                            href="{{ route('panel.servicios.edit', $servicio) }}"
                                            class="text-xs font-semibold text-brand-text hover:text-brand-700 p-2 rounded-lg hover:bg-cream-50 inline-flex items-center min-h-11 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                        >
                                            Editar
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Tarjetas para móvil -->
                <div class="sm:hidden divide-y divide-line">
                    @foreach ($servicios as $servicio)
                        <div class="p-4 space-y-2">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-bold text-ink-900 text-base">
                                        {{ $servicio->nombre }}
                                    </p>
                                    @if ($servicio->descripcion)
                                        <p class="text-xs text-ink-500 line-clamp-2 mt-0.5">
                                            {{ $servicio->descripcion }}
                                        </p>
                                    @endif
                                </div>
                                <a
                                    href="{{ route('panel.servicios.edit', $servicio) }}"
                                    class="text-xs font-semibold text-brand-text hover:text-brand-700 p-2 rounded-lg hover:bg-cream-50 inline-flex items-center min-h-11 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 shrink-0"
                                >
                                    Editar
                                </a>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-ink-500">
                                <span>{{ $servicio->especialidad?->nombre ?? 'Sin especialidad' }}</span>
                                <span>·</span>
                                <span>{{ $servicio->duracion_min }} min</span>
                                <span>·</span>
                                <span class="font-medium text-ink-900">
                                    @if ($servicio->precio !== null)
                                        Gs. {{ number_format($servicio->precio, 0, ',', '.') }}
                                    @else
                                        <span class="italic text-ink-500">Consultar</span>
                                    @endif
                                </span>
                            </div>
                            <div>
                                @if ($servicio->activo)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-stone-800 dark:text-stone-300">
                                        Inactivo
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.panel>
