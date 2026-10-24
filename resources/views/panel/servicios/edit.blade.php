<x-layouts.panel titulo="Editar servicio">
    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <a
                href="{{ route('panel.servicios') }}"
                class="text-xs font-semibold text-brand-text hover:text-brand-700 inline-flex items-center gap-1 mb-2"
            >
                &larr; Volver a servicios
            </a>
            <h1 class="text-2xl font-bold font-display text-ink-900">
                Editar Servicio
            </h1>
            <p class="text-sm text-ink-500 mt-1">
                Modificá los datos de {{ $servicio->nombre }}.
            </p>
        </div>

        <div class="bg-card p-6 rounded-lg border border-line shadow-2xs">
            <form action="{{ route('panel.servicios.update', $servicio) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <!-- Nombre -->
                <x-input
                    name="nombre"
                    label="Nombre del servicio"
                    required
                    :value="old('nombre', $servicio->nombre)"
                />

                <!-- Especialidad -->
                <x-select
                    name="especialidad_id"
                    label="Especialidad"
                    required
                    :options="$especialidades"
                    :value="old('especialidad_id', $servicio->especialidad_id)"
                />

                <!-- Duración -->
                <x-input
                    name="duracion_min"
                    label="Duración estimada (minutos)"
                    type="number"
                    required
                    min="5"
                    max="480"
                    :value="old('duracion_min', $servicio->duracion_min)"
                    help="Tiempo en minutos reservado en la agenda (mínimo 5 min)."
                />

                <!-- Precio -->
                <x-input
                    name="precio"
                    label="Precio en guaraníes (opcional)"
                    type="number"
                    min="0"
                    :value="old('precio', $servicio->precio)"
                    help="Dejalo vacío para que figure como 'Consultar precio'."
                />

                <!-- Descripción -->
                <div>
                    <label for="descripcion" class="block text-sm font-semibold text-ink-900 mb-1">
                        Descripción (opcional)
                    </label>
                    <textarea
                        id="descripcion"
                        name="descripcion"
                        rows="3"
                        class="block w-full rounded-lg border bg-card px-3 py-2 text-ink-900 placeholder:text-ink-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 disabled:opacity-50 transition-colors duration-150 motion-reduce:transition-none @error('descripcion') border-danger-700 @else border-line @enderror"
                    >{{ old('descripcion', $servicio->descripcion) }}</textarea>
                    @error('descripcion')
                        <p class="mt-1 text-xs text-danger-700" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Estado Activo -->
                <div class="flex items-center pt-2">
                    <label for="activo" class="flex items-center gap-2 cursor-pointer select-none text-sm text-ink-900 font-medium">
                        <input
                            id="activo"
                            name="activo"
                            type="checkbox"
                            value="1"
                            @checked(old('activo', $servicio->activo))
                            class="rounded border-line text-brand-600 focus:ring-brand-600 h-4 w-4"
                        />
                        <span>Servicio activo (visible en el catálogo público)</span>
                    </label>
                </div>

                <!-- Botones de acción -->
                <div class="pt-4 border-t border-line flex items-center justify-end gap-3">
                    <x-button variant="secondary" :href="route('panel.servicios')">
                        Cancelar
                    </x-button>
                    <x-button type="submit">
                        Guardar cambios
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.panel>
