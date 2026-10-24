<x-layouts.panel titulo="Disponibilidad">
    <div class="space-y-6">
        <!-- Encabezado con selector para funcionario o nombre de la profesional -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-line">
            <div>
                <h1 class="text-2xl font-bold font-display text-ink-900">Disponibilidad</h1>
                <p class="text-sm text-ink-500 mt-1">
                    Gestioná los horarios semanales de atención y los bloqueos por vacaciones o licencias.
                </p>
            </div>

            @if(auth()->user()->rol === \App\Enums\RolUsuario::Funcionario && $profesionales->count() > 1)
                <form method="GET" action="{{ route('panel.disponibilidad') }}" class="flex items-center gap-2">
                    <label for="selector_profesional" class="text-sm font-semibold text-ink-900 whitespace-nowrap">
                        Profesional:
                    </label>
                    <select
                        name="profesional_id"
                        id="selector_profesional"
                        onchange="this.form.submit()"
                        class="min-h-11 rounded-lg border border-line bg-card px-3 py-2 text-sm font-medium text-ink-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                    >
                        @foreach($profesionales as $p)
                            <option value="{{ $p->id }}" @selected($profesional->id === $p->id)>
                                Dra. {{ $p->nombre }} {{ $p->apellido }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @else
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-cream-50 border border-brand-300 text-brand-700 text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>Dra. {{ $profesional->nombre }} {{ $profesional->apellido }}</span>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Columna Izquierda: Horario Semanal -->
            <div class="lg:col-span-7">
                <x-card>
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-line">
                        <h2 class="text-lg font-bold font-display text-ink-900">Horario semanal</h2>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('panel.disponibilidad.horario') }}"
                        x-data="{
                            dias: [
                                { id: 1, nombre: 'Lunes' },
                                { id: 2, nombre: 'Martes' },
                                { id: 3, nombre: 'Miércoles' },
                                { id: 4, nombre: 'Jueves' },
                                { id: 5, nombre: 'Viernes' },
                                { id: 6, nombre: 'Sábado' },
                                { id: 7, nombre: 'Domingo' }
                            ],
                            franjas: @js($franjasPorDia),
                            agregarFranja(diaId) {
                                if (!this.franjas[diaId]) this.franjas[diaId] = [];
                                this.franjas[diaId].push({ dia_semana: diaId, hora_inicio: '08:00', hora_fin: '12:00' });
                            },
                            eliminarFranja(diaId, index) {
                                this.franjas[diaId].splice(index, 1);
                            }
                        }"
                    >
                        @csrf
                        @method('PUT')

                        @if(auth()->user()->rol === \App\Enums\RolUsuario::Funcionario)
                            <input type="hidden" name="profesional_id" value="{{ $profesional->id }}">
                        @endif

                        <div class="divide-y divide-line">
                            <template x-for="dia in dias" :key="dia.id">
                                <div class="py-3 flex flex-col sm:flex-row sm:items-center gap-2">
                                    <div class="w-24 font-bold text-sm text-ink-900" x-text="dia.nombre"></div>

                                    <div class="flex flex-wrap gap-2 items-center flex-1">
                                        <template x-for="(f, idx) in (franjas[dia.id] || [])" :key="idx">
                                            <div class="inline-flex items-center gap-1.5 bg-surface border border-line rounded-lg px-2.5 py-1 text-sm text-ink-900">
                                                <input
                                                    type="time"
                                                    :name="`franjas[${dia.id}_${idx}][hora_inicio]`"
                                                    x-model="f.hora_inicio"
                                                    class="bg-transparent border-0 p-0 text-sm focus:ring-0 w-28 text-center font-medium"
                                                    required
                                                >
                                                <span class="text-ink-500">–</span>
                                                <input
                                                    type="time"
                                                    :name="`franjas[${dia.id}_${idx}][hora_fin]`"
                                                    x-model="f.hora_fin"
                                                    class="bg-transparent border-0 p-0 text-sm focus:ring-0 w-28 text-center font-medium"
                                                    required
                                                >
                                                <input type="hidden" :name="`franjas[${dia.id}_${idx}][dia_semana]`" :value="dia.id">
                                                <button
                                                    type="button"
                                                    @click="eliminarFranja(dia.id, idx)"
                                                    class="text-danger-700 ml-1 font-bold p-1 cursor-pointer min-h-6 min-w-6 inline-flex items-center justify-center rounded hover:bg-card"
                                                    title="Quitar franja"
                                                    aria-label="Quitar franja"
                                                >
                                                    <x-phosphor-x class="size-4" aria-hidden="true" />
                                                </button>
                                            </div>
                                        </template>

                                        <span
                                            x-show="!franjas[dia.id] || franjas[dia.id].length === 0"
                                            class="text-ink-500 italic text-sm py-1"
                                        >
                                            No atiende
                                        </span>

                                        <button
                                            type="button"
                                            @click="agregarFranja(dia.id)"
                                            class="text-brand-text hover:text-brand-700 text-sm font-semibold inline-flex items-center cursor-pointer min-h-9 px-2.5 rounded-lg hover:bg-cream-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 transition-colors"
                                        >
                                            + franja
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        @if($errors->has('franjas'))
                            <p class="text-sm text-danger-700 mt-3 font-medium" role="alert">
                                {{ $errors->first('franjas') }}
                            </p>
                        @endif

                        <div class="mt-6 pt-4 border-t border-line flex flex-col sm:flex-row items-center justify-between gap-3">
                            <span class="text-xs text-ink-500">
                                Las franjas de un mismo día no pueden superponerse.
                            </span>
                            <x-button type="submit" variant="primary">
                                Guardar horario
                            </x-button>
                        </div>
                    </form>
                </x-card>
            </div>

            <!-- Columna Derecha: Bloqueos y Vista Previa -->
            <div class="lg:col-span-5 space-y-6">
                <!-- Bloqueos -->
                <x-card>
                    <div class="pb-3 mb-4 border-b border-line">
                        <h2 class="text-lg font-bold font-display text-ink-900">Bloqueos</h2>
                        <p class="text-xs text-ink-500 mt-0.5">Vacaciones, feriados o ausencias programadas.</p>
                    </div>

                    <!-- Lista de bloqueos -->
                    <div class="space-y-2 mb-6">
                        @forelse($bloqueos as $b)
                            <div class="flex items-center justify-between py-2 border-b border-line last:border-0 text-sm">
                                <div class="pr-2">
                                    <span class="font-bold text-ink-900">
                                        {{ $b->inicio->format('d/m/Y H:i') }} al {{ $b->fin->format('d/m/Y H:i') }}
                                    </span>
                                    @if($b->motivo)
                                        <span class="text-ink-500 block text-xs mt-0.5">Motivo: {{ $b->motivo }}</span>
                                    @endif
                                </div>
                                <button
                                    type="button"
                                    @click="$dispatch('abrir-modal', 'eliminar-bloqueo-{{ $b->id }}')"
                                    class="text-danger-700 hover:underline font-semibold text-xs cursor-pointer min-h-9 px-2 rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danger-700"
                                >
                                    Quitar
                                </button>

                                <x-modal name="eliminar-bloqueo-{{ $b->id }}" titulo="Quitar bloqueo">
                                    <p class="text-sm text-ink-500 mb-4">
                                        ¿Confirmás que querés quitar el bloqueo desde el <strong>{{ $b->inicio->format('d/m/Y H:i') }}</strong> hasta el <strong>{{ $b->fin->format('d/m/Y H:i') }}</strong>?
                                    </p>
                                    <form method="POST" action="{{ route('panel.disponibilidad.bloqueos.destroy', $b) }}" class="flex justify-end gap-3">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="button" variant="secondary" @click="$dispatch('cerrar-modal', 'eliminar-bloqueo-{{ $b->id }}')">
                                            Cancelar
                                        </x-button>
                                        <x-button type="submit" variant="danger">
                                            Quitar bloqueo
                                        </x-button>
                                    </form>
                                </x-modal>
                            </div>
                        @empty
                            <p class="text-sm text-ink-500 py-1">No hay bloqueos registrados.</p>
                        @endforelse
                    </div>

                    <!-- Formulario nuevo bloqueo -->
                    <div class="pt-4 border-t border-line">
                        <h3 class="text-sm font-bold text-ink-900 mb-3">Nuevo bloqueo</h3>

                        <form method="POST" action="{{ route('panel.disponibilidad.bloqueos.store') }}" class="space-y-3">
                            @csrf
                            @if(auth()->user()->rol === \App\Enums\RolUsuario::Funcionario)
                                <input type="hidden" name="profesional_id" value="{{ $profesional->id }}">
                            @endif

                            <x-input
                                type="datetime-local"
                                name="desde"
                                id="bloqueo_desde"
                                label="Desde"
                                :value="old('desde')"
                                required
                            />

                            <x-input
                                type="datetime-local"
                                name="hasta"
                                id="bloqueo_hasta"
                                label="Hasta"
                                :value="old('hasta')"
                                required
                            />

                            <x-input
                                type="text"
                                name="motivo"
                                id="bloqueo_motivo"
                                label="Motivo (opcional)"
                                :value="old('motivo')"
                                placeholder="Ej. Vacaciones, Congreso..."
                            />

                            @if($errors->has('confirmar'))
                                <div class="bg-amber-50 border border-amber-200 text-amber-900 dark:bg-amber-950/40 dark:border-amber-900/50 dark:text-amber-200 rounded-lg p-3 text-xs space-y-2 mt-2">
                                    <p class="font-medium flex items-center gap-1.5"><x-phosphor-warning class="size-4 shrink-0 text-amber-700 dark:text-amber-400" aria-hidden="true" /><span>{{ $errors->first('confirmar') }}</span></p>
                                    <label class="flex items-center gap-2 cursor-pointer font-semibold pt-1">
                                        <input
                                            type="checkbox"
                                            name="confirmar"
                                            value="1"
                                            class="rounded border-line text-brand-600 focus:ring-brand-600 h-4 w-4"
                                        >
                                        <span>Entiendo, bloquear igual</span>
                                    </label>
                                </div>
                            @endif

                            <x-button type="submit" variant="primary" class="w-full justify-center mt-2">
                                Agregar bloqueo
                            </x-button>
                        </form>
                    </div>
                </x-card>

                <!-- Vista previa -->
                <x-card>
                    <div class="pb-3 mb-3 border-b border-line">
                        <h2 class="text-lg font-bold font-display text-ink-900">Vista previa</h2>
                        <p class="text-xs text-ink-500 mt-0.5">Horarios que verá el paciente según el servicio y la fecha.</p>
                    </div>

                    <form method="GET" action="{{ route('panel.disponibilidad') }}" class="space-y-3">
                        @if(auth()->user()->rol === \App\Enums\RolUsuario::Funcionario)
                            <input type="hidden" name="profesional_id" value="{{ $profesional->id }}">
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <x-select
                                name="servicio_id"
                                id="preview_servicio"
                                label="Servicio"
                                :options="$servicios->pluck('nombre', 'id')->toArray()"
                                :value="$servicioSeleccionado?->id"
                                onchange="this.form.submit()"
                            />

                            <x-date
                                name="fecha"
                                id="preview_fecha"
                                label="Fecha"
                                :value="$fechaInput->format('Y-m-d')"
                                onchange="this.form.submit()"
                            />
                        </div>
                    </form>

                    <div class="mt-4 pt-3 border-t border-line">
                        @if(!empty($horariosPreview))
                            <x-slot-picker name="slot_preview" :horarios="$horariosPreview" />
                        @else
                            <p class="text-xs text-ink-500 italic py-2">
                                No hay horarios disponibles para esta fecha o la profesional no atiende este día.
                            </p>
                        @endif
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-layouts.panel>
