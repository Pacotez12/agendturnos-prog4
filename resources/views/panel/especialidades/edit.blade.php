<x-layouts.panel titulo="Editar especialidad">
    <div class="max-w-xl mx-auto space-y-6">
        <div>
            <a
                href="{{ route('panel.especialidades') }}"
                class="text-xs font-semibold text-brand-text hover:text-brand-700 inline-flex items-center gap-1 mb-2"
            >
                &larr; Volver a especialidades
            </a>
            <h1 class="text-2xl font-bold font-display text-ink-900">
                Editar Especialidad
            </h1>
            <p class="text-sm text-ink-500 mt-1">
                Modificá el nombre o el estado de la especialidad.
            </p>
        </div>

        <div class="bg-card p-6 rounded-lg border border-line shadow-2xs">
            <form action="{{ route('panel.especialidades.update', $especialidad) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <!-- Nombre -->
                <x-input
                    name="nombre"
                    label="Nombre de la especialidad"
                    required
                    :value="old('nombre', $especialidad->nombre)"
                    placeholder="Ej. Periodoncia"
                />

                <!-- Estado Activa -->
                <div class="flex items-center pt-2">
                    <label for="activa" class="flex items-center gap-2 cursor-pointer select-none text-sm text-ink-900 font-medium">
                        <input
                            id="activa"
                            name="activa"
                            type="checkbox"
                            value="1"
                            @checked(old('activa', $especialidad->activa))
                            class="rounded border-line text-brand-600 focus:ring-brand-600 h-4 w-4"
                        />
                        <span>Especialidad activa</span>
                    </label>
                </div>

                <!-- Botones de acción -->
                <div class="pt-4 border-t border-line flex items-center justify-end gap-3">
                    <x-button variant="secondary" :href="route('panel.especialidades')">
                        Cancelar
                    </x-button>
                    <x-button type="submit">
                        Guardar especialidad
                    </x-button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.panel>
