<x-layouts.invitado titulo="Recuperá tu contraseña">
    <div class="max-w-md mx-auto bg-card p-8 rounded-2xl border border-line shadow-xs">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold font-display text-ink-900">
                Recuperá tu contraseña
            </h1>
            <p class="mt-1 text-sm text-ink-500">
                Te enviamos un enlace a tu correo para crear una nueva.
            </p>
        </div>

        @if (session('status'))
            <x-alert type="success" class="mb-4">
                {{ session('status') }}
            </x-alert>
        @endif


        <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
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

            <!-- Botón de Envío -->
            <div class="pt-2">
                <x-button
                    type="submit"
                    variant="primary"
                    :pill="true"
                    class="w-full"
                >
                    Enviar enlace
                </x-button>
            </div>

            <div class="text-center pt-2">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-brand-text hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-lg">
                    Volver a iniciar sesión
                </a>
            </div>
        </form>
    </div>
</x-layouts.invitado>
