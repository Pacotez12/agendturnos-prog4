@php
    $layout = (auth()->check() && auth()->user()->rol === \App\Enums\RolUsuario::Paciente)
        ? 'layouts.paciente'
        : 'layouts.invitado';
@endphp

<x-dynamic-component :component="$layout" titulo="Nuestros servicios">
    <div class="space-y-8">
        <!-- Encabezado -->
        <div>
            <h1 class="text-3xl font-bold font-display text-ink-900">
                Nuestros servicios
            </h1>
            <p class="text-ink-500 mt-2">
                Elegí el tratamiento y reservá tu turno.
            </p>
        </div>

        <!-- Filtros por Especialidad -->
        @if ($especialidades->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('servicios.catalogo') }}"
                    class="px-4 py-2 text-sm font-medium rounded-full min-h-11 inline-flex items-center transition-colors duration-150 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 {{ is_null($especialidadSeleccionada) ? 'bg-brand-600 text-white font-semibold shadow-xs' : 'bg-card border border-line text-ink-900 hover:bg-cream-50' }}"
                >
                    Todos
                </a>

                @foreach ($especialidades as $esp)
                    <a
                        href="{{ route('servicios.catalogo', ['especialidad' => $esp->id]) }}"
                        class="px-4 py-2 text-sm font-medium rounded-full min-h-11 inline-flex items-center transition-colors duration-150 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 {{ $especialidadSeleccionada === $esp->id ? 'bg-brand-600 text-white font-semibold shadow-xs' : 'bg-card border border-line text-ink-900 hover:bg-cream-50' }}"
                    >
                        {{ $esp->nombre }}
                    </a>
                @endforeach
            </div>
        @endif

        <!-- Listado de servicios agrupados -->
        @if ($serviciosPorEspecialidad->isEmpty())
            <x-empty-state
                titulo="No hay servicios disponibles"
                texto="Actualmente no encontramos servicios en esta categoría."
            />
        @else
            <div class="space-y-10">
                @foreach ($serviciosPorEspecialidad as $nombreEspecialidad => $grupoServicios)
                    <div>
                        <h2 class="text-xl font-bold font-display text-ink-900 mb-4 pb-2 border-b border-line">
                            {{ $nombreEspecialidad }}
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($grupoServicios as $servicio)
                                <x-card :paciente="true" class="flex flex-col justify-between">
                                    <div class="space-y-3">
                                        <div class="flex items-start justify-between gap-2">
                                            <h3 class="font-display font-bold text-lg text-ink-900">
                                                {{ $servicio->nombre }}
                                            </h3>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-100 text-brand-700 shrink-0">
                                                {{ $servicio->duracion_min }} min
                                            </span>
                                        </div>

                                        @if ($servicio->descripcion)
                                            <p class="text-sm text-ink-500">
                                                {{ $servicio->descripcion }}
                                            </p>
                                        @endif

                                        @if ($servicio->profesionales->isNotEmpty())
                                            <div class="text-xs text-ink-500">
                                                <span class="font-medium text-ink-900">Profesionales:</span>
                                                {{ $servicio->profesionales->map(fn ($p) => "{$p->nombre} {$p->apellido}")->implode(', ') }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="mt-6 pt-4 border-t border-line flex items-center justify-between gap-4">
                                        <div>
                                            <p class="text-xs text-ink-500">Precio estimado</p>
                                            <p class="text-base font-bold text-ink-900">
                                                @if ($servicio->precio !== null)
                                                    Gs. {{ number_format($servicio->precio, 0, ',', '.') }}
                                                @else
                                                    Consultar precio
                                                @endif
                                            </p>
                                        </div>

                                        <x-button
                                            :pill="true"
                                            :href="auth()->check() ? route('paciente.inicio') : route('login')"
                                        >
                                            Reservar
                                        </x-button>
                                    </div>
                                </x-card>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-dynamic-component>
