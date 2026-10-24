<x-layouts.invitado titulo="Nueva contraseña">
    <div class="max-w-md mx-auto bg-card p-8 rounded-2xl border border-line shadow-xs">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold font-display text-ink-900">
                Nueva contraseña
            </h1>
            <p class="mt-1 text-sm text-ink-500">
                Elegí una contraseña de al menos 8 caracteres.
            </p>
        </div>

        @if ($errors->any())
            <x-alert type="error" class="mb-4">
                {{ $errors->first('email') ?: ($errors->first('password') ?: 'Revisá los datos ingresados.') }}
            </x-alert>
        @endif

        <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <!-- Correo Electrónico -->
            <x-input
                id="email"
                name="email"
                label="Correo electrónico"
                type="email"
                autocomplete="email"
                required
                :value="old('email', $request->email)"
            />

            <!-- Nueva Contraseña -->
            <x-input
                id="password"
                name="password"
                label="Nueva contraseña"
                type="password"
                autocomplete="new-password"
                required
            />

            <!-- Repetir Contraseña -->
            <x-input
                id="password_confirmation"
                name="password_confirmation"
                label="Repetí la contraseña"
                type="password"
                autocomplete="new-password"
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
                    Guardar contraseña
                </x-button>
            </div>
        </form>
    </div>
</x-layouts.invitado>
