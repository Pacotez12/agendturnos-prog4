@props([
    'type' => 'info',
])

@php
    $role = $type === 'error' ? 'alert' : 'status';

    $classes = match ($type) {
        'error' => 'bg-red-50 text-danger-700 border-red-200 dark:bg-red-950/40 dark:text-red-300 dark:border-red-900/50',
        'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900/50',
        'info' => 'bg-brand-100 text-brand-700 border-brand-300 dark:border-brand-700/50',
        default => 'bg-brand-100 text-brand-700 border-brand-300 dark:border-brand-700/50',
    };
@endphp

<div role="{{ $role }}" {{ $attributes->merge(['class' => "rounded-lg border p-4 text-sm {$classes}"]) }}>
    {{ $slot }}
</div>
