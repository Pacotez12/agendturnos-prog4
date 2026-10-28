@props([
    'variant' => 'primary',
    'pill' => false,
    'type' => 'button',
    'href' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-2 font-medium px-5 py-2.5 text-sm min-h-11 transition-colors duration-150 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none cursor-pointer';

    $radiusClass = $pill ? 'rounded-full' : 'rounded-lg';

    $variantClasses = match ($variant) {
        'primary' => 'bg-brand-600 hover:bg-brand-hover active:bg-brand-hover text-white shadow-xs',
        'secondary' => 'border border-line bg-card hover:bg-cream-50 active:bg-cream-50 text-ink-900',
        'ghost' => 'text-brand-600 dark:text-brand-text hover:text-brand-700 hover:bg-cream-50 active:bg-cream-50',
        'danger' => 'bg-danger-700 dark:bg-red-700 hover:bg-red-800 active:bg-red-800 text-white shadow-xs',
        default => 'bg-brand-600 hover:bg-brand-hover active:bg-brand-hover text-white shadow-xs',
    };

    $classes = "{$baseClasses} {$radiusClass} {$variantClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
