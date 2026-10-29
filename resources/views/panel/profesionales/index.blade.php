@php
    $vacio = ['id' => null, 'nombre' => '', 'apellido' => '', 'matricula' => '', 'email' => ''];

    // Si la validación falló, el modal vuelve a abrirse con lo que se había escrito.
    $formInicial = $errors->any()
        ? [
            'id' => old('_profesional_id') ? (int) old('_profesional_id') : null,
            'nombre' => old('nombre', ''),
            'apellido' => old('apellido', ''),
            'matricula' => old('matricula', ''),
            'email' => old('email', ''),
        ]
        : $vacio;

    $urlEditar = route('panel.profesionales.update', ['profesional' => '__ID__']);

    // El mismo modal sirve para dar de alta y para editar; el título cambia con Alpine.
    $tituloModal = new \Illuminate\Support\HtmlString(
        '<span x-text="form.id ? \'Editar profesional\' : \'Nueva profesional\'">Nueva profesional</span>'
    );
@endphp

<x-layouts.panel titulo="Profesionales">
    <div
        class="space-y-6"
        x-data="{
            form: @js($formInicial),
            agregar() {
                this.form = @js($vacio);
            },
            editar(profesional) {
                this.form = { ...profesional };
            },
            accion() {
                return this.form.id ? @js($urlEditar).replace('__ID__', this.form.id) : @js(route('panel.profesionales.store'));
            },
        }"
        @if ($errors->any())
            x-init="$nextTick(() => $dispatch('abrir-modal', 'profesional'))"
        @endif
    >
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
            <div class="sm:shrink-0">
                <x-button
                    type="button"
                    class="w-full sm:w-auto"
                    x-on:click="agregar(); $dispatch('abrir-modal', 'profesional')"
                >
                    <x-phosphor-plus class="size-4 shrink-0" aria-hidden="true" />
                    <span>Nueva profesional</span>
                </x-button>
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
                                <th class="px-4 py-3">Servicios</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($profesionales as $prof)
                                <tr class="hover:bg-surface/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-ink-900">
                                            {{ $prof->nombre }} {{ $prof->apellido }}
                                        </p>
                                        @if ($prof->user?->email)
                                            <p class="font-mono text-xs text-ink-500 mt-0.5">
                                                {{ $prof->user->email }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-ink-500 font-mono text-xs">
                                        {{ $prof->matricula }}
                                    </td>
                                    <td class="px-4 py-3 text-ink-500">
                                        {{ $prof->servicios_count }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($prof->servicios_count === 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold estado-solicitado-bg estado-solicitado-fg">
                                                Sin servicios
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold estado-atendido-bg estado-atendido-fg">
                                                Activa
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center justify-end gap-1">
                                            <a
                                                href="{{ route('panel.profesionales.servicios', $prof) }}"
                                                aria-label="Servicios que ofrece {{ $prof->nombre }} {{ $prof->apellido }}"
                                                class="text-xs font-semibold text-brand-text hover:text-brand-700 p-2 rounded-lg hover:bg-cream-50 inline-flex items-center min-h-11 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                            >
                                                Servicios
                                            </a>
                                            <button
                                                type="button"
                                                aria-label="Editar a {{ $prof->nombre }} {{ $prof->apellido }}"
                                                x-on:click="editar({{ \Illuminate\Support\Js::from([
                                                    'id' => $prof->id,
                                                    'nombre' => $prof->nombre,
                                                    'apellido' => $prof->apellido,
                                                    'matricula' => $prof->matricula,
                                                    'email' => $prof->user?->email ?? '',
                                                ]) }}); $dispatch('abrir-modal', 'profesional')"
                                                class="text-xs font-semibold text-brand-text hover:text-brand-700 p-2 rounded-lg hover:bg-cream-50 inline-flex items-center min-h-11 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 cursor-pointer"
                                            >
                                                Editar
                                            </button>
                                        </div>
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
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <p class="font-bold text-ink-900 text-base">
                                    {{ $prof->nombre }} {{ $prof->apellido }}
                                </p>
                                @if ($prof->servicios_count === 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold estado-solicitado-bg estado-solicitado-fg">
                                        Sin servicios
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold estado-atendido-bg estado-atendido-fg">
                                        Activa
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-ink-500 font-mono">
                                {{ $prof->matricula }} · {{ $prof->servicios_count }} {{ $prof->servicios_count === 1 ? 'servicio' : 'servicios' }}
                            </p>
                            @if ($prof->user?->email)
                                <p class="text-xs text-ink-500 font-mono">
                                    {{ $prof->user->email }}
                                </p>
                            @endif
                            <div class="flex items-center gap-1 pt-1">
                                <a
                                    href="{{ route('panel.profesionales.servicios', $prof) }}"
                                    aria-label="Servicios que ofrece {{ $prof->nombre }} {{ $prof->apellido }}"
                                    class="text-xs font-semibold text-brand-text hover:text-brand-700 p-2 rounded-lg hover:bg-cream-50 inline-flex items-center min-h-11 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                >
                                    Servicios
                                </a>
                                <button
                                    type="button"
                                    aria-label="Editar a {{ $prof->nombre }} {{ $prof->apellido }}"
                                    x-on:click="editar({{ \Illuminate\Support\Js::from([
                                        'id' => $prof->id,
                                        'nombre' => $prof->nombre,
                                        'apellido' => $prof->apellido,
                                        'matricula' => $prof->matricula,
                                        'email' => $prof->user?->email ?? '',
                                    ]) }}); $dispatch('abrir-modal', 'profesional')"
                                    class="text-xs font-semibold text-brand-text hover:text-brand-700 p-2 rounded-lg hover:bg-cream-50 inline-flex items-center min-h-11 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 cursor-pointer"
                                >
                                    Editar
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Modal Alta y Edición -->
        <x-modal name="profesional" :titulo="$tituloModal">
            <p
                class="mb-4 text-sm text-ink-500"
                x-text="form.id ? 'Si cambiás el correo, va a iniciar sesión con el nuevo.' : 'Le vamos a mandar un correo para que cree su contraseña y pueda entrar al panel.'"
            >
                Le vamos a mandar un correo para que cree su contraseña y pueda entrar al panel.
            </p>

            <form method="POST" x-bind:action="accion()" action="{{ route('panel.profesionales.store') }}" class="space-y-4">
                @csrf
                <template x-if="form.id">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="_profesional_id" x-bind:value="form.id ?? ''">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input name="nombre" label="Nombre" autocomplete="off" x-model="form.nombre" required />
                    <x-input name="apellido" label="Apellido" autocomplete="off" x-model="form.apellido" required />
                    <x-input name="matricula" label="Matrícula" autocomplete="off" x-model="form.matricula" required />
                    <x-input name="email" label="Correo" type="email" autocomplete="off" x-model="form.email" required />
                </div>

                <p x-show="! form.id" class="text-xs text-ink-500">
                    Después del alta te llevamos a elegir sus servicios. El horario se carga en Disponibilidad.
                </p>

                <div class="flex justify-end gap-2 pt-2">
                    <x-button type="button" variant="secondary" x-on:click="$dispatch('cerrar-modal', 'profesional')">
                        Cancelar
                    </x-button>
                    <x-button type="submit">
                        <span x-text="form.id ? 'Guardar' : 'Dar de alta'">Dar de alta</span>
                    </x-button>
                </div>
            </form>
        </x-modal>
    </div>
</x-layouts.panel>
