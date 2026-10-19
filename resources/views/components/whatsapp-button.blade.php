@php
    $numero = config('services.whatsapp.numero');
@endphp

@if (! empty($numero))
    <a
        href="https://wa.me/{{ $numero }}"
        target="_blank"
        rel="noopener"
        {{ $attributes->merge(['class' => 'fixed bottom-6 right-6 z-40 inline-flex items-center gap-2 px-4 py-2.5 rounded-full border border-brand-300 bg-white hover:bg-cream-50 text-ink-900 font-semibold text-sm shadow-md transition-colors duration-150 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 min-h-11']) }}
    >
        <x-phosphor-whatsapp-logo class="w-5 h-5 shrink-0 text-whatsapp" aria-hidden="true" />
        <span>¿Dudas? Escribinos</span>
    </a>
@endif
