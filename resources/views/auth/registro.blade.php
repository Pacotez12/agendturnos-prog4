<x-layouts.invitado titulo="Registro">
    <div class="max-w-md mx-auto bg-card p-8 rounded-2xl border border-line shadow-xs">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold font-display text-ink-900">
                Creá tu cuenta de paciente
            </h1>
            <p class="mt-1 text-sm text-ink-500">
                Registrate para solicitar y gestionar tus turnos
            </p>
        </div>

        <form action="{{ url('/registro') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Nombre -->
            <x-input
                id="nombre"
                name="nombre"
                label="Nombre"
                type="text"
                required
                :value="old('nombre')"
            />

            <!-- Apellido -->
            <x-input
                id="apellido"
                name="apellido"
                label="Apellido"
                type="text"
                required
                :value="old('apellido')"
            />

            <!-- Tipo de Documento -->
            <x-select
                id="tipo_documento"
                name="tipo_documento"
                label="Tipo de Documento"
                required
                :options="['CI' => 'CI', 'pasaporte' => 'Pasaporte']"
                :value="old('tipo_documento')"
            />

            <!-- Número de Documento -->
            <x-input
                id="documento"
                name="documento"
                label="Número de Documento"
                type="text"
                required
                :value="old('documento')"
            />

            <!-- Fecha de Nacimiento -->
            <x-date
                id="fecha_nacimiento"
                name="fecha_nacimiento"
                label="Fecha de Nacimiento"
                required
                :value="old('fecha_nacimiento')"
            />

            <!-- Teléfono -->
            <x-input
                id="telefono"
                name="telefono"
                label="Teléfono (Opcional)"
                type="text"
                :value="old('telefono')"
            />

            <!-- Correo Electrónico -->
            <x-input
                id="email"
                name="email"
                label="Correo Electrónico"
                type="email"
                required
                :value="old('email')"
            />

            <!-- Contraseña -->
            <x-input
                id="password"
                name="password"
                label="Contraseña"
                type="password"
                required
            />

            <!-- Confirmar Contraseña -->
            <x-input
                id="password_confirmation"
                name="password_confirmation"
                label="Confirmar Contraseña"
                type="password"
                required
            />

            <!-- Botón de Envío -->
            <div class="pt-2">
                <x-button
                    type="submit"
                    variant="primary"
                    :pill="true"
                    class="w-full"
                >
                    Registrarse
                </x-button>
            </div>

            <div class="text-center pt-2">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-brand-text hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-lg">
                    ¿Ya tenés cuenta? Iniciá sesión
                </a>
            </div>
        </form>
    </div>
</x-layouts.invitado>
