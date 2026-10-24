@props([
    'paciente' => false,
])

@php
    $radiusClass = $paciente ? 'rounded-2xl' : 'rounded-lg';
    $classes = "border border-line bg-card p-4 shadow-2xs {$radiusClass}";
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
