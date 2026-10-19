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
        toggleMenu() {
            this.menuAbierto = ! this.menuAbierto;
        },
        cerrarMenu() {
            this.menuAbierto = false;
        }
    }"
    @keydown.escape.window="cerrarMenu()"
    class="min-h-screen bg-surface font-sans text-ink-900 antialiased flex flex-col lg:flex-row"
>
    <!-- Header móvil con botón hamburguesa -->
    <header class="lg:hidden bg-white border-b border-line px-4 h-16 flex items-center justify-between sticky top-0 z-30">
        <a href="/" class="inline-flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-lg">
            <img src="{{ asset('images/glow-logo.svg') }}" alt="Glow Dental Protect" class="h-8 w-auto">
        </a>
        <button
            type="button"
            @click="toggleMenu()"
            aria-expanded="false"
            :aria-expanded="menuAbierto ? 'true' : 'false'"
            aria-controls="sidebar-panel"
            aria-label="Abrir menú"
            class="p-2 text-ink-900 hover:bg-surface rounded-lg min-h-11 min-w-11 inline-flex items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 cursor-pointer"
        >
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>
    </header>

    <!-- Barra lateral de escritorio y drawer móvil -->
    <aside
        id="sidebar-panel"
        :class="menuAbierto ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-line flex flex-col justify-between transition-transform duration-200 motion-reduce:transition-none lg:static lg:translate-x-0 lg:min-h-screen shrink-0"
    >
        <div class="p-6">
            <div class="flex items-center justify-between pb-6 border-b border-line">
                <a href="/" class="inline-flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-lg">
                    <img src="{{ asset('images/glow-logo.svg') }}" alt="Glow Dental Protect" class="h-10 w-auto">
                </a>
                <button
                    type="button"
                    @click="cerrarMenu()"
                    class="lg:hidden p-2 text-ink-500 hover:text-ink-900 rounded-lg min-h-11 min-w-11 inline-flex items-center justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                    aria-label="Cerrar menú"
                >
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Menú de navegación -->
            <nav class="mt-6 flex flex-col gap-1">
                @foreach ($navItems as $item)
                    @php
                        $isActive = request()->routeIs($item['ruta'], $item['ruta'].'.*');
                    @endphp
                    <a
                        href="{{ route($item['ruta']) }}"
                        class="px-3 py-2 text-sm font-medium rounded-lg min-h-11 flex items-center transition-colors duration-150 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 {{ $isActive ? 'bg-brand-100 text-brand-600 font-semibold' : 'text-ink-500 hover:text-ink-900 hover:bg-surface' }}"
                    >
                        {{ $item['etiqueta'] }}
                    </a>
                @endforeach
            </nav>
        </div>

        <!-- Pie de la barra lateral con email y rol -->
        @if ($user)
            <div class="p-4 border-t border-line bg-surface/50">
                <div class="mb-3 px-2">
                    <p class="text-sm font-semibold text-ink-900 truncate" title="{{ $user->email }}">
                        {{ $user->email }}
                    </p>
                    <p class="text-xs text-ink-500 capitalize">
                        {{ $user->rol?->value ?? '' }}
                    </p>
                </div>

                @if (Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="w-full px-3 py-2 text-left text-xs font-medium text-ink-500 hover:text-danger-700 hover:bg-red-50 rounded-lg min-h-11 flex items-center transition-colors duration-150 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danger-700 cursor-pointer"
                        >
                            Cerrar sesión
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </aside>

    <!-- Backdrop móvil -->
    <div
        x-show="menuAbierto"
        x-cloak
        style="display: none;"
        @click="cerrarMenu()"
        class="fixed inset-0 z-30 bg-ink-900/40 backdrop-blur-xs lg:hidden"
        aria-hidden="true"
    ></div>

    <!-- Contenido principal -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
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
    </div>
</body>
</html>
