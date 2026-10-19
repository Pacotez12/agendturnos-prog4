<x-layouts.panel titulo="Panel">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold font-display text-ink-900">Panel de Control</h1>
            <p class="text-sm text-ink-500 mt-1">
                Bienvenido, {{ auth()->user()?->email }}.
            </p>
        </div>
    </div>
</x-layouts.panel>
