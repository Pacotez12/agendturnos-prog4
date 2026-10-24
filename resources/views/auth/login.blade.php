<x-layouts.invitado titulo="Iniciar sesión">
    <div class="max-w-md mx-auto bg-card p-8 rounded-2xl border border-line shadow-xs">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold font-display text-ink-900">
                Iniciá sesión
            </h1>
            <p class="mt-1 text-sm text-ink-500">
                Entrá para reservar y ver tus turnos
            </p>
        </div>

        @if ($errors->any())
            <x-alert type="error" class="mb-4">
                {{ $errors->first('email') ?: 'Revisá los datos ingresados.' }}
            </x-alert>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Correo Electrónico -->
            <x-input
                id="email"
                name="email"
                label="Correo electrónico"
                type="email"
                autocomplete="email"
                required
                :value="old('email')"
            />

            <!-- Contraseña -->
            <x-input
                id="password"
                name="password"
                label="Contraseña"
                type="password"
                autocomplete="current-password"
                required
            />

            <!-- Recordarme y Olvidé mi contraseña -->
            <div class="flex items-center justify-between gap-2">
                <label for="remember" class="flex items-center gap-2 cursor-pointer select-none text-sm text-ink-500">
                    <input
                        id="remember"
                        name="remember"
                        type="checkbox"
                        class="rounded border-line text-brand-600 focus:ring-brand-600 h-4 w-4"
                    />
                    <span>Recordarme</span>
                </label>

                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-brand-text hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-lg">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>

            <!-- Botón de Envío -->
            <div class="pt-2">
                <x-button
                    type="submit"
                    variant="primary"
                    :pill="true"
                    class="w-full"
                >
                    Ingresar
                </x-button>
            </div>

            <div class="text-center pt-2">
                <a href="{{ route('registro') }}" class="text-sm font-semibold text-brand-text hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-lg">
                    ¿No tenés cuenta? Registrate
                </a>
            </div>
        </form>
    </div>
</x-layouts.invitado>
