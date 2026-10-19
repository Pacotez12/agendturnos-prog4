@props([
    'type' => 'info',
])

@php
    $role = $type === 'error' ? 'alert' : 'status';

    $classes = match ($type) {
        'error' => 'bg-red-50 text-danger-700 border-red-200',
        'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'info' => 'bg-brand-100 text-brand-700 border-brand-300',
        default => 'bg-brand-100 text-brand-700 border-brand-300',
    };
@endphp

<div role="{{ $role }}" {{ $attributes->merge(['class' => "rounded-lg border p-4 text-sm {$classes}"]) }}>
    {{ $slot }}
</div>
