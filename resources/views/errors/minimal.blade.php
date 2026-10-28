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
    {{-- Fuentes de la app si el build existe; si no, queda la fuente del sistema --}}
    {!! rescue(fn () => app(\Illuminate\Foundation\Vite::class)->fonts(), '', false) !!}
    <style>
        :root {
            --bg-surface: #F7F6F4;
            --text-ink-900: #1F1A17;
            --text-ink-500: #6B635D;
            --border-line: #E5E2DE;
            --brand-600: #995432;
            --brand-700: #7A3F22;
            --brand-100: #F3E6D3;
            --brand-text: #995432;
            --whatsapp: #25D366;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-surface: #171311;
                --text-ink-900: #F2ECE6;
                --text-ink-500: #A89E96;
                --border-line: #3A322D;
                --brand-600: #995432;
                --brand-700: #F0CFAE;
                --brand-100: #3A2A20;
                --brand-text: #E0A47C;
            }
        }
        html.dark {
            --bg-surface: #171311;
            --text-ink-900: #F2ECE6;
            --text-ink-500: #A89E96;
            --border-line: #3A322D;
            --brand-600: #995432;
            --brand-700: #F0CFAE;
            --brand-100: #3A2A20;
            --brand-text: #E0A47C;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: var(--bg-surface);
            color: var(--text-ink-900);
            font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            line-height: 1.5;
        }
        header, footer {
            max-width: 64rem;
            width: 100%;
            margin: 0 auto;
            padding: 1.5rem 1rem;
        }
        footer {
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-ink-500);
            border-top: 1px solid var(--border-line);
        }
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
        }
        .container {
            max-width: 28rem;
            width: 100%;
            text-align: center;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 4rem;
            height: 4rem;
            border-radius: 9999px;
            background-color: var(--brand-100);
            color: var(--brand-text);
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        h1 {
            font-family: 'Quicksand', 'Open Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-ink-900);
            letter-spacing: -0.025em;
        }
        p {
            margin-top: 0.75rem;
            font-size: 1rem;
            color: var(--text-ink-500);
        }
        .extra {
            margin-top: 0.75rem;
            font-size: 0.875rem;
            color: var(--text-ink-500);
        }
        .actions {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        @media (min-width: 640px) {
            .actions {
                flex-direction: row;
            }
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.25rem;
            border-radius: 9999px;
            background-color: var(--brand-600);
            color: #ffffff;
            font-weight: 500;
            font-size: 0.875rem;
            text-decoration: none;
            min-height: 2.75rem;
            width: 100%;
        }
        @media (min-width: 640px) {
            .btn {
                width: auto;
            }
        }
        .btn:hover {
            background-color: var(--brand-700);
        }
        .whatsapp {
            margin-top: 1.5rem;
        }
        .whatsapp a {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--brand-text);
            text-decoration: none;
        }
        .whatsapp a:hover {
            text-decoration: underline;
        }
        .whatsapp svg {
            width: 1rem;
            height: 1rem;
            vertical-align: middle;
        }
        .logo {
            height: 2.25rem;
            width: auto;
            display: block;
        }
        html.dark .logo {
            filter: brightness(0) invert(1);
        }
        @media (prefers-color-scheme: dark) {
            html:not(.light) .logo {
                filter: brightness(0) invert(1);
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="{{ route('inicio') }}" style="display: inline-block;">
            <img src="/images/glow-logo.svg" alt="Glow Dental Protect" class="logo">
        </a>
    </header>

    <main>
        <div class="container">
            <div class="badge">
                @yield('code')
            </div>

            <h1>
                @yield('heading', View::yieldContent('title'))
            </h1>

            <p>
                @yield('message')
            </p>

            @hasSection('extra')
                <div class="extra">
                    @yield('extra')
                </div>
            @endif

            <div class="actions">
                @yield('actions')
                <a href="{{ route('inicio') }}" class="btn">
                    Ir al inicio
                </a>
            </div>

            <div class="whatsapp">
                <a href="https://wa.me/595982774586" target="_blank" rel="noopener">
                    <x-phosphor-whatsapp-logo style="color: var(--whatsapp);" aria-hidden="true" />
                    <span>Escribinos por WhatsApp</span>
                    <x-phosphor-arrow-up-right aria-hidden="true" />
                </a>
            </div>
        </div>
    </main>

    <footer>
        &copy; {{ date('Y') }} Glow Dental Protect. Todos los derechos reservados.
    </footer>
</body>
</html>
