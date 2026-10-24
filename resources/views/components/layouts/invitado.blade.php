@props([
    'titulo' => null,
    'descripcion' => null,
])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo ? "{$titulo} · " : '' }}Glow Dental Protect</title>
    @if ($descripcion)
        <meta name="description" content="{{ $descripcion }}">
    @endif
    @include('partials.tema')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    x-data="{
        menuAbierto: false,
        disparador: null,
        toggleMenu(origen) {
            this.disparador = origen;
            this.menuAbierto = ! this.menuAbierto;
        },
        cerrarMenu() {
            if (this.menuAbierto) {
                this.menuAbierto = false;
                if (this.disparador && typeof this.disparador.focus === 'function') {
                    this.$nextTick(() => this.disparador.focus());
                }
            }
        }
    }"
    @keydown.escape.window="cerrarMenu()"
    class="min-h-screen bg-cream-50 font-sans text-ink-900 antialiased flex flex-col"
>
    <header class="py-6 px-4 sm:px-6 lg:px-8 border-b border-line bg-card/80 backdrop-blur-xs">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <a href="/" class="inline-flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-lg">
                <img src="{{ asset('images/glow-logo.svg') }}" alt="Glow Dental Protect" class="h-10 w-auto dark:hidden">
                <img src="{{ asset('images/glow-logo-blanco.svg') }}" alt="Glow Dental Protect" class="h-10 w-auto hidden dark:block">
            </a>

            <div class="flex items-center gap-2">
                <!-- Navegación escritorio (lg:) -->
                <div class="hidden lg:flex items-center gap-2">
                    @if (Route::has('servicios.catalogo'))
                        <a href="{{ route('servicios.catalogo') }}" class="text-sm font-semibold text-ink-900 hover:text-brand-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-lg px-3 py-2 min-h-11 inline-flex items-center">
                            Servicios
                        </a>
                    @endif
                    @if (Route::has('login') && ! auth()->check())
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-brand-text hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-lg px-3 py-2 min-h-11 inline-flex items-center">
                            Iniciar sesión
                        </a>
                    @endif
                    @if (Route::has('registro') && ! auth()->check())
                        <x-button :pill="true" href="{{ route('registro') }}">
                            Reservar turno
                        </x-button>
                    @elseif (auth()->check())
                        <x-button :pill="true" href="{{ route(\App\Support\Navegacion::inicioPara(auth()->user()->rol)) }}">
                            Ir al panel
                        </x-button>
                    @endif
                </div>

                <!-- Botón de tema -->
                <button
                    type="button"
                    @click="window.toggleTema($event)"
                    class="size-11 rounded-lg border border-line bg-card text-ink-900 hover:bg-cream-50 inline-flex items-center justify-center transition-colors duration-150 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 cursor-pointer"
                    aria-label="Cambiar tema de color"
                >
                    <x-phosphor-moon class="size-5 dark:hidden" aria-hidden="true" />
                    <x-phosphor-sun class="size-5 hidden dark:inline" aria-hidden="true" />
                </button>

                <!-- Botón menú hamburguesa celular -->
                <button
                    type="button"
                    @click="toggleMenu($el)"
                    aria-expanded="false"
                    :aria-expanded="menuAbierto ? 'true' : 'false'"
                    aria-controls="menu-lateral-invitado"
                    aria-label="Abrir menú de navegación"
                    class="lg:hidden p-2 text-ink-900 hover:bg-cream-50 rounded-lg min-h-11 min-w-11 inline-flex items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 cursor-pointer"
                >
                    <x-phosphor-list class="size-6" aria-hidden="true" />
                </button>
            </div>
        </div>
    </header>

    <!-- Menú lateral móvil -->
    <div
        id="menu-lateral-invitado"
        x-show="menuAbierto"
        x-cloak
        style="display: none;"
        class="fixed inset-0 z-50 lg:hidden"
        role="dialog"
        aria-modal="true"
        aria-label="Menú de navegación"
    >
        <!-- Backdrop -->
        <div
            x-show="menuAbierto"
            x-transition:enter="ease-out duration-200 motion-reduce:transition-none"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150 motion-reduce:transition-none"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="cerrarMenu()"
            class="fixed inset-0 bg-ink-900/40 backdrop-blur-xs"
            aria-hidden="true"
        ></div>

        <!-- Panel lateral -->
        <div
            x-show="menuAbierto"
            x-transition:enter="ease-out duration-200 motion-reduce:transition-none"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="ease-in duration-150 motion-reduce:transition-none"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 w-full max-w-xs bg-card p-6 shadow-xl flex flex-col justify-between border-l border-line"
        >
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-line">
                    <img src="{{ asset('images/glow-logo.svg') }}" alt="Glow Dental Protect" class="h-8 w-auto dark:hidden">
                    <img src="{{ asset('images/glow-logo-blanco.svg') }}" alt="Glow Dental Protect" class="h-8 w-auto hidden dark:block">
                    <button
                        type="button"
                        @click="cerrarMenu()"
                        class="p-2 text-ink-500 hover:text-ink-900 rounded-lg min-h-11 min-w-11 inline-flex items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 cursor-pointer"
                        aria-label="Cerrar menú"
                    >
                        <x-phosphor-x class="size-6" aria-hidden="true" />
                    </button>
                </div>

                <nav class="mt-6 flex flex-col gap-3">
                    @if (Route::has('servicios.catalogo'))
                        <a
                            href="{{ route('servicios.catalogo') }}"
                            class="px-4 py-3 text-base font-medium rounded-full min-h-11 flex items-center text-ink-500 hover:text-ink-900 hover:bg-cream-50 transition-colors duration-150 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                        >
                            Servicios
                        </a>
                    @endif

                    @if (Route::has('login') && ! auth()->check())
                        <a
                            href="{{ route('login') }}"
                            class="px-4 py-3 text-base font-semibold rounded-full min-h-11 flex items-center text-brand-text hover:text-brand-700 hover:bg-cream-50 transition-colors duration-150 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                        >
                            Iniciar sesión
                        </a>
                    @endif

                    @if (Route::has('registro') && ! auth()->check())
                        <x-button :pill="true" href="{{ route('registro') }}" class="w-full">
                            Reservar turno
                        </x-button>
                    @elseif (auth()->check())
                        <x-button :pill="true" href="{{ route(\App\Support\Navegacion::inicioPara(auth()->user()->rol)) }}" class="w-full">
                            Ir al panel
                        </x-button>
                    @endif
                </nav>
            </div>
        </div>
    </div>

    <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('success'))
            <x-alert type="success" class="mb-6">
                {{ session('success') }}
            </x-alert>
        @endif

        @if (session('error'))
            <x-alert type="error" class="mb-6">
                {{ session('error') }}
            </x-alert>
        @endif

        {{ $slot }}
    </main>

    <footer class="py-6 pb-24 sm:pb-6 border-t border-line text-xs text-ink-500">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2 text-center sm:text-left">
            <span>&copy; {{ date('Y') }} Glow Dental Protect. Todos los derechos reservados.</span>
            @if (Route::has('privacidad'))
                <a href="{{ route('privacidad') }}" class="hover:text-ink-900 underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded">
                    Privacidad
                </a>
            @endif
        </div>
    </footer>

    <x-whatsapp-button />
</body>
</html>
