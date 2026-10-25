@php
    $vacio = ['id' => null, 'nombre' => '', 'apellido' => '', 'tipo_documento' => 'CI', 'documento' => '', 'fecha_nacimiento' => ''];

    // Si la validación falló, el modal vuelve a abrirse con lo que se había escrito.
    $formInicial = $errors->any()
        ? [
            'id' => old('_familiar_id') ? (int) old('_familiar_id') : null,
            'nombre' => old('nombre', ''),
            'apellido' => old('apellido', ''),
            'tipo_documento' => old('tipo_documento', 'CI'),
            'documento' => old('documento', ''),
            'fecha_nacimiento' => old('fecha_nacimiento', ''),
        ]
        : $vacio;

    $urlEditar = route('paciente.familia.update', ['paciente' => '__ID__']);

    // El mismo modal sirve para agregar y para editar; el título cambia con Alpine.
    $tituloModal = new \Illuminate\Support\HtmlString(
        '<span x-text="form.id ? \'Editar familiar\' : \'Agregar familiar\'">Agregar familiar</span>'
    );
@endphp

<x-layouts.paciente titulo="Mi familia">
    <div
        class="space-y-6 max-w-3xl mx-auto"
        x-data="{
            form: @js($formInicial),
            agregar() {
                this.form = @js($vacio);
            },
            editar(familiar) {
                this.form = { ...familiar };
            },
            accion() {
                return this.form.id ? @js($urlEditar).replace('__ID__', this.form.id) : @js(route('paciente.familia.store'));
            },
        }"
        @if ($errors->any())
            x-init="$nextTick(() => $dispatch('abrir-modal', 'familiar'))"
        @endif
    >
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold font-display text-ink-900">Mi familia</h1>
                <p class="text-sm text-ink-500 mt-1">
                    Personas a tu cargo. Podés pedirles turnos desde tu cuenta.
                </p>
            </div>
            @if ($familiares->isNotEmpty())
                <div class="sm:shrink-0">
                    <x-button :pill="true" variant="primary" class="w-full sm:w-auto" x-on:click="agregar(); $dispatch('abrir-modal', 'familiar')">
                        <x-phosphor-plus class="w-4 h-4" aria-hidden="true" />
                        Agregar familiar
                    </x-button>
                </div>
            @endif
        </div>

        @if ($familiares->isEmpty())
            <x-empty-state
                titulo="Todavía no agregaste a nadie"
                texto="Agregá a tus hijos o personas a cargo para pedirles turnos."
            >
                <x-slot:icono>
                    <x-phosphor-users class="size-8" />
                </x-slot:icono>
                <x-button :pill="true" variant="primary" x-on:click="agregar(); $dispatch('abrir-modal', 'familiar')">
                    Agregar familiar
                </x-button>
            </x-empty-state>
        @else
            <ul class="space-y-3">
                @foreach ($familiares as $familiar)
                    @php
                        $proximo = $familiar->turnos->first();
                        $iniciales = mb_strtoupper(mb_substr($familiar->nombre, 0, 1).mb_substr($familiar->apellido, 0, 1));
                        $edad = $familiar->fecha_nacimiento->age;
                    @endphp
                    <li>
                        <x-card :paciente="true" class="p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="flex items-start gap-4 flex-1 min-w-0">
                                <span class="shrink-0 inline-flex items-center justify-center size-11 rounded-full bg-brand-100 text-brand-text font-semibold" aria-hidden="true">
                                    {{ $iniciales }}
                                </span>
                                <div class="min-w-0">
                                    <p class="font-semibold text-ink-900">{{ $familiar->nombreCompleto() }}</p>
                                    <p class="text-sm text-ink-500">
                                        {{ $edad }} {{ $edad === 1 ? 'año' : 'años' }} · {{ $familiar->tipo_documento }} {{ $familiar->documento }}
                                    </p>
                                    @if ($proximo)
                                        <p class="text-sm text-ink-900 mt-1 leading-relaxed">
                                            Próximo turno:
                                            <span class="font-semibold">{{ \App\Support\Fecha::corta($proximo->inicio) }}, {{ $proximo->inicio->format('H:i') }}</span>
                                            · {{ $proximo->servicio->nombre }}
                                            <x-estado-turno :estado="$proximo->estado" class="ml-1 align-middle" />
                                        </p>
                                    @else
                                        <p class="text-sm text-ink-500 mt-1">Sin turnos próximos</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2 sm:shrink-0">
                                <x-button
                                    variant="ghost"
                                    :pill="true"
                                    :href="route('paciente.reservar', ['para' => $familiar->id])"
                                    aria-label="Pedir turno para {{ $familiar->nombre }}"
                                >
                                    Pedir turno
                                </x-button>
                                <x-button
                                    variant="secondary"
                                    :pill="true"
                                    aria-label="Editar a {{ $familiar->nombre }}"
                                    x-on:click="editar({{ \Illuminate\Support\Js::from([
                                        'id' => $familiar->id,
                                        'nombre' => $familiar->nombre,
                                        'apellido' => $familiar->apellido,
                                        'tipo_documento' => $familiar->tipo_documento,
                                        'documento' => $familiar->documento,
                                        'fecha_nacimiento' => $familiar->fecha_nacimiento->toDateString(),
                                    ]) }}); $dispatch('abrir-modal', 'familiar')"
                                >
                                    Editar
                                </x-button>
                            </div>
                        </x-card>
                    </li>
                @endforeach
            </ul>

            <p class="rounded-2xl border border-dashed border-line px-4 py-3 text-sm text-ink-500">
                Los datos de tus familiares solo los ves vos y la clínica. Para quitar a alguien de tu familia, escribinos por WhatsApp.
            </p>
        @endif

        <x-modal name="familiar" :titulo="$tituloModal">
            <p class="mb-4">Un hijo, hija o persona a tu cargo que no tiene cuenta propia.</p>

            <form method="POST" x-bind:action="accion()" action="{{ route('paciente.familia.store') }}" class="space-y-4">
                @csrf
                <template x-if="form.id">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="_familiar_id" x-bind:value="form.id ?? ''">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input name="nombre" label="Nombre" autocomplete="off" x-model="form.nombre" required />
                    <x-input name="apellido" label="Apellido" autocomplete="off" x-model="form.apellido" required />
                    <x-select
                        name="tipo_documento"
                        label="Tipo de documento"
                        :options="['CI' => 'CI', 'pasaporte' => 'Pasaporte']"
                        x-model="form.tipo_documento"
                        required
                    />
                    <x-input name="documento" label="Número de documento" inputmode="numeric" autocomplete="off" x-model="form.documento" required />
                </div>

                <x-date name="fecha_nacimiento" label="Fecha de nacimiento" :max="now()->toDateString()" x-model="form.fecha_nacimiento" required />

                <div class="flex justify-end gap-2 pt-2">
                    <x-button variant="secondary" :pill="true" x-on:click="$dispatch('cerrar-modal', 'familiar')">
                        Cancelar
                    </x-button>
                    <x-button type="submit" :pill="true">
                        Guardar
                    </x-button>
                </div>
            </form>
        </x-modal>
    </div>
</x-layouts.paciente>
