<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Error') · Glow Dental Protect</title>
    @include('partials.tema')
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <meta name="theme-color" media="(prefers-color-scheme: light)" content="#FBF7F2">
    <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#171311">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface text-ink-900 min-h-screen flex flex-col justify-between font-sans antialiased selection:bg-brand-100 selection:text-brand-700">
    <header class="max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 flex items-center justify-between">
        <a href="{{ route('inicio') }}" class="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded-lg">
            <img src="{{ asset('images/glow-logo.svg') }}" alt="Glow Dental Protect" class="h-9 w-auto dark:hidden">
            <img src="{{ asset('images/glow-logo-blanco.svg') }}" alt="Glow Dental Protect" class="h-9 w-auto hidden dark:block">
        </a>
    </header>

    <main class="flex-1 flex items-center justify-center px-4 py-12">
        <div class="max-w-md w-full text-center">
            <div class="inline-flex items-center justify-center size-16 rounded-full bg-brand-100 text-brand-text font-display font-bold text-xl mb-4">
                @yield('code')
            </div>

            <h1 class="font-display font-bold text-2xl sm:text-3xl text-ink-900 tracking-tight">
                @yield('heading', View::yieldContent('title'))
            </h1>

            <p class="mt-3 text-base text-ink-500 leading-relaxed">
                @yield('message')
            </p>

            @hasSection('extra')
                <div class="mt-3 text-sm text-ink-500">
                    @yield('extra')
                </div>
            @endif

            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                @yield('actions')
                <a
                    href="{{ route('inicio') }}"
                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-full bg-brand-600 hover:bg-brand-700 text-white font-medium text-sm transition-colors duration-150 shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 min-h-11 w-full sm:w-auto"
                >
                    Ir al inicio
                </a>
            </div>

            <div class="mt-6">
                <a
                    href="https://wa.me/595982774586"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-text hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 rounded"
                >
                    <x-phosphor-whatsapp-logo class="size-4 shrink-0 text-whatsapp" aria-hidden="true" />
                    <span>Escribinos por WhatsApp</span>
                    <x-phosphor-arrow-up-right class="size-3.5 shrink-0" aria-hidden="true" />
                </a>
            </div>
        </div>
    </main>

    <footer class="max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-xs text-ink-500 border-t border-line">
        &copy; {{ date('Y') }} Glow Dental Protect. Todos los derechos reservados.
    </footer>
</body>
</html>
