@props([
    'titulo' => null,
])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo ? "{$titulo} · " : '' }}Glow Dental Protect</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cream-50 font-sans text-ink-900 antialiased flex flex-col">
    <header class="py-6 px-4 sm:px-6 lg:px-8 border-b border-line bg-white/80 backdrop-blur-xs">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <a href="/" class="inline-flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-lg">
                <img src="{{ asset('images/glow-logo.svg') }}" alt="Glow Dental Protect" class="h-10 w-auto">
            </a>
            @if (Route::has('login') && ! auth()->check())
                <a href="{{ route('login') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-lg px-3 py-2 min-h-11 inline-flex items-center">
                    Iniciar sesión
                </a>
            @endif
        </div>
    </header>

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

    <footer class="py-6 border-t border-line text-center text-xs text-ink-500">
        &copy; {{ date('Y') }} Glow Dental Protect. Todos los derechos reservados.
    </footer>
</body>
</html>
