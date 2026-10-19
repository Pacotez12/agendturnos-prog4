<x-layouts.panel titulo="Guía de Componentes UI">
    <div class="max-w-4xl mx-auto space-y-10">
        <div>
            <h1 class="text-2xl font-bold font-display text-ink-900">Catálogo de Componentes UI</h1>
            <p class="text-sm text-ink-500 mt-1">
                Referencia visual de los componentes estándar de Glow Dental Protect (SCRUM-38).
            </p>
        </div>

        <!-- 1. Botones -->
        <section class="space-y-4">
            <h2 class="text-lg font-bold text-ink-900 border-b border-line pb-2">Botones</h2>
            <div class="flex flex-wrap items-center gap-3">
                <x-button variant="primary">Guardar</x-button>
                <x-button variant="secondary">Cancelar</x-button>
                <x-button variant="ghost">Enlace secundario</x-button>
                <x-button variant="danger">Eliminar</x-button>
                <x-button variant="primary" :pill="true">Variante Píldora</x-button>
            </div>
        </section>

        <!-- 2. Estados del turno -->
        <section class="space-y-4">
            <h2 class="text-lg font-bold text-ink-900 border-b border-line pb-2">Estados del Turno</h2>
            <div class="flex flex-wrap items-center gap-3">
                <x-estado-turno :estado="\App\Enums\EstadoTurno::Solicitado" />
                <x-estado-turno :estado="\App\Enums\EstadoTurno::Confirmado" />
                <x-estado-turno :estado="\App\Enums\EstadoTurno::Atendido" />
                <x-estado-turno :estado="\App\Enums\EstadoTurno::Cancelado" />
                <x-estado-turno :estado="\App\Enums\EstadoTurno::Ausente" />
            </div>
        </section>

        <!-- 3. Campos de Formulario -->
        <section class="space-y-4">
            <h2 class="text-lg font-bold text-ink-900 border-b border-line pb-2">Formularios</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input
                    name="nombre_ejemplo"
                    label="Nombre completo"
                    placeholder="Ej. María Segovia"
                    help="Ingresá nombre y apellido completos"
                />

                @php
                    $demoErrors = new \Illuminate\Support\ViewErrorBag;
                    $demoErrors->put('default', new \Illuminate\Support\MessageBag(['campo_error' => ['El campo es obligatorio.']]));
                @endphp
                <x-input
                    name="campo_error"
                    label="Campo con error"
                    value="Valor incorrecto"
                    :errors="$demoErrors"
                />

                <x-select
                    name="tipo_documento_demo"
                    label="Tipo de documento"
                    :options="['CI' => 'Cédula de Identidad', 'pasaporte' => 'Pasaporte']"
                />

                <x-date
                    name="fecha_demo"
                    label="Fecha de nacimiento"
                    max="2026-10-18"
                />
            </div>
        </section>

        <!-- 4. Alertas y Notificaciones -->
        <section class="space-y-4">
            <h2 class="text-lg font-bold text-ink-900 border-b border-line pb-2">Alertas</h2>
            <div class="space-y-3">
                <x-alert type="success">
                    El turno ha sido confirmado exitosamente.
                </x-alert>
                <x-alert type="error">
                    Ocurrió un error al procesar la reserva. Por favor intentá de nuevo.
                </x-alert>
                <x-alert type="info">
                    Recordá asistir 10 minutos antes del horario programado.
                </x-alert>
            </div>
        </section>

        <!-- 5. Tarjetas y Estados Vacíos -->
        <section class="space-y-4">
            <h2 class="text-lg font-bold text-ink-900 border-b border-line pb-2">Tarjetas y Estados Vacíos</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-card>
                    <h3 class="font-bold text-ink-900">Tarjeta Estándar (Panel)</h3>
                    <p class="text-sm text-ink-500 mt-1">
                        Utilizada para interfaces administrativas, agenda y paneles clínicos.
                    </p>
                </x-card>

                <x-card :paciente="true">
                    <h3 class="font-bold font-display text-ink-900">Tarjeta Paciente</h3>
                    <p class="text-sm text-ink-500 mt-1">
                        Esquinas redondeadas suaves y estilo acogedor para la vista del paciente.
                    </p>
                </x-card>
            </div>

            <div class="mt-4">
                <x-empty-state
                    titulo="Sin turnos asignados"
                    texto="No tenés turnos programados para esta fecha."
                >
                    <x-button variant="primary" :pill="true">
                        Reservar un turno
                    </x-button>
                </x-empty-state>
            </div>
        </section>

        <!-- 6. Selector de Horarios -->
        <section class="space-y-4">
            <h2 class="text-lg font-bold text-ink-900 border-b border-line pb-2">Selector de Horarios (Slot Picker)</h2>
            <x-slot-picker
                name="horario_demo"
                :horarios="[
                    ['hora' => '08:00', 'libre' => true],
                    ['hora' => '08:30', 'libre' => false],
                    ['hora' => '09:00', 'libre' => true],
                    ['hora' => '09:30', 'libre' => true],
                    ['hora' => '10:00', 'libre' => false],
                    ['hora' => '10:30', 'libre' => true],
                ]"
            />
        </section>

        <!-- 7. Modales e Interacción -->
        <section class="space-y-4">
            <h2 class="text-lg font-bold text-ink-900 border-b border-line pb-2">Diálogos y Modales</h2>
            <div>
                <x-button @click="$dispatch('abrir-modal', 'modal-ejemplo')">
                    Abrir Modal de Confirmación
                </x-button>

                <x-modal name="modal-ejemplo" titulo="¿Cancelar turno?">
                    <p>¿Estás seguro de que deseás cancelar este turno? Esta acción no se puede deshacer.</p>
                    <div class="mt-6 flex justify-end gap-2">
                        <x-button variant="secondary" @click="$dispatch('cerrar-modal', 'modal-ejemplo')">
                            Volver
                        </x-button>
                        <x-button variant="danger" @click="$dispatch('cerrar-modal', 'modal-ejemplo')">
                            Sí, cancelar
                        </x-button>
                    </div>
                </x-modal>
            </div>
        </section>

        <!-- 8. Botón flotante WhatsApp -->
        <x-whatsapp-button />
    </div>
</x-layouts.panel>
