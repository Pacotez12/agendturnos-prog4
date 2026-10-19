@props([
    'titulo' => null,
])

@php
    $user = auth()->user();
    $navItems = $user ? \App\Support\Navegacion::para($user->rol) : [];
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo ? "{$titulo} · " : '' }}Glow Dental Protect</title>
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
    <!-- Barra superior -->
    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur-xs border-b border-line">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="/" class="inline-flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-lg">
                <img src="{{ asset('images/glow-logo.svg') }}" alt="Glow Dental Protect" class="h-10 w-auto">
            </a>

            <!-- Navegación escritorio (lg:) -->
            <nav class="hidden lg:flex items-center gap-1">
                @foreach ($navItems as $item)
                    @php
                        $isActive = request()->routeIs($item['ruta'], $item['ruta'].'.*');
                    @endphp
                    <a
                        href="{{ route($item['ruta']) }}"
                        class="px-3 py-2 text-sm font-medium rounded-full transition-colors duration-150 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 {{ $isActive ? 'bg-brand-100 text-brand-700 font-semibold' : 'text-ink-500 hover:text-ink-900 hover:bg-cream-50' }}"
                    >
                        {{ $item['etiqueta'] }}
                    </a>
                @endforeach

                @if (Route::has('logout') && $user)
                    <form method="POST" action="{{ route('logout') }}" class="inline ml-2">
                        @csrf
                        <button
                            type="submit"
                            class="px-3 py-2 text-sm font-medium text-ink-500 hover:text-danger-700 rounded-full transition-colors duration-150 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 cursor-pointer min-h-11 inline-flex items-center"
                        >
                            Cerrar sesión
                        </button>
                    </form>
                @endif
            </nav>

            <!-- Botón menú hamburguesa celular -->
            <button
                type="button"
                @click="toggleMenu($el)"
                aria-expanded="false"
                :aria-expanded="menuAbierto ? 'true' : 'false'"
                aria-controls="menu-lateral-paciente"
                aria-label="Abrir menú de navegación"
                class="lg:hidden p-2 text-ink-900 hover:bg-cream-50 rounded-lg min-h-11 min-w-11 inline-flex items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 cursor-pointer"
            >
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
        </div>
    </header>

    <!-- Menú lateral móvil -->
    <div
        id="menu-lateral-paciente"
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
            class="fixed inset-y-0 right-0 w-full max-w-xs bg-white p-6 shadow-xl flex flex-col justify-between border-l border-line"
        >
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-line">
                    <img src="{{ asset('images/glow-logo.svg') }}" alt="Glow Dental Protect" class="h-8 w-auto">
                    <button
                        type="button"
                        @click="cerrarMenu()"
                        class="p-2 text-ink-500 hover:text-ink-900 rounded-lg min-h-11 min-w-11 inline-flex items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                        aria-label="Cerrar menú"
                    >
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <nav class="mt-6 flex flex-col gap-2">
                    @foreach ($navItems as $item)
                        @php
                            $isActive = request()->routeIs($item['ruta'], $item['ruta'].'.*');
                        @endphp
                        <a
                            href="{{ route($item['ruta']) }}"
                            class="px-4 py-3 text-base font-medium rounded-full min-h-11 flex items-center transition-colors duration-150 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 {{ $isActive ? 'bg-brand-100 text-brand-700 font-semibold' : 'text-ink-500 hover:text-ink-900 hover:bg-cream-50' }}"
                        >
                            {{ $item['etiqueta'] }}
                        </a>
                    @endforeach
                </nav>
            </div>

            @if (Route::has('logout') && $user)
                <div class="pt-4 border-t border-line">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="w-full px-4 py-3 text-left text-base font-medium text-danger-700 hover:bg-red-50 rounded-full min-h-11 flex items-center transition-colors duration-150 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danger-700 cursor-pointer"
                        >
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <!-- Contenido principal -->
    <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
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

    <!-- Botón flotante WhatsApp -->
    <x-whatsapp-button />
</body>
</html>
