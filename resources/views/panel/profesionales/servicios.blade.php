<x-layouts.panel :titulo="'Servicios de ' . $profesional->nombre . ' ' . $profesional->apellido">
    <div class="max-w-3xl space-y-6">
        <div>
            <a
                href="{{ route('panel.profesionales') }}"
                class="text-xs font-semibold text-brand-text hover:text-brand-700 inline-flex items-center gap-1 mb-2"
            >
                &larr; Volver a profesionales
            </a>
            <h1 class="text-2xl font-bold font-display text-ink-900">
                {{ $profesional->nombre }} {{ $profesional->apellido }} · servicios que ofrece
            </h1>
            <p class="text-sm text-ink-500 mt-1">
                Marcá los tratamientos que realiza esta profesional. Los servicios con turnos históricos o activos están protegidos y no pueden quitarse.
            </p>
        </div>

        <form action="{{ route('panel.profesionales.servicios.update', $profesional) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            @foreach ($especialidades as $esp)
                <div class="bg-card rounded-lg border border-line p-5 shadow-2xs">
                    <h2 class="text-xs font-bold text-ink-500 uppercase tracking-wider mb-4 pb-2 border-b border-line">
                        {{ $esp->nombre }}
                    </h2>

                    <div class="space-y-3">
                        @foreach ($esp->servicios as $servicio)
                            @php
                                $tieneTurnos = in_array($servicio->id, $serviciosConTurnosIds);
                                $estaMarcado = in_array($servicio->id, $serviciosOfrecidosIds) || $tieneTurnos;
                            @endphp

                            <div class="flex items-start justify-between gap-3 py-1">
                                <label
                                    for="servicio-{{ $servicio->id }}"
                                    class="flex items-center gap-3 select-none text-sm text-ink-900 font-medium @if($tieneTurnos) cursor-not-allowed opacity-80 @else cursor-pointer @endif"
                                >
                                    @if ($tieneTurnos)
                                        <input type="hidden" name="servicios[]" value="{{ $servicio->id }}">
                                    @endif
                                    <input
                                        type="checkbox"
                                        id="servicio-{{ $servicio->id }}"
                                        @if (! $tieneTurnos) name="servicios[]" @endif
                                        value="{{ $servicio->id }}"
                                        @checked($estaMarcado)
                                        @if ($tieneTurnos)
                                            {{-- No usar disabled: el navegador lo pinta gris y parece desmarcado. El valor lo manda el hidden. --}}
                                            aria-disabled="true" onclick="return false"
                                        @endif
                                        class="rounded border-line text-brand-600 focus:ring-brand-600 h-4 w-4 accent-brand-600 @if($tieneTurnos) cursor-not-allowed opacity-75 @endif"
                                    />
                                    <span>{{ $servicio->nombre }}</span>
                                    <span class="text-xs text-ink-500 font-normal">({{ $servicio->duracion_min }} min)</span>
                                </label>

                                @if ($tieneTurnos)
                                    <span class="inline-flex items-center gap-1.5 text-xs text-ink-500 bg-surface px-2.5 py-1 rounded-md border border-line">
                                        <x-phosphor-lock class="size-3.5 text-ink-500" aria-hidden="true" />
                                        <span>Tiene turnos registrados: no se puede quitar</span>
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <!-- Botones de acción -->
            <div class="bg-card p-4 rounded-lg border border-line shadow-2xs flex items-center justify-end gap-3">
                <x-button variant="secondary" :href="route('panel.profesionales')">
                    Cancelar
                </x-button>
                <x-button type="submit">
                    Guardar
                </x-button>
            </div>
        </form>
    </div>
</x-layouts.panel>
