@props([
    'estado',
])

@php
    $valor = $estado instanceof \App\Enums\EstadoTurno ? $estado->value : (string) $estado;

    $nombres = [
        'solicitado' => 'Solicitado',
        'confirmado' => 'Confirmado',
        'atendido' => 'Atendido',
        'cancelado' => 'Cancelado',
        'ausente' => 'Ausente',
    ];

    // Clases completas y literales: Tailwind no detecta nombres armados con variables.
    $colores = [
        'solicitado' => 'estado-solicitado-bg estado-solicitado-fg',
        'confirmado' => 'estado-confirmado-bg estado-confirmado-fg',
        'atendido' => 'estado-atendido-bg estado-atendido-fg',
        'cancelado' => 'estado-cancelado-bg estado-cancelado-fg',
        'ausente' => 'estado-ausente-bg estado-ausente-fg',
    ];

    $label = $nombres[$valor] ?? ucfirst($valor);
    $classes = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold '.($colores[$valor] ?? 'bg-surface text-ink-900');
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $label }}
</span>
