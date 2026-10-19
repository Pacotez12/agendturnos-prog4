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

    $label = $nombres[$valor] ?? ucfirst($valor);
    $classes = "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold estado-{$valor}-bg estado-{$valor}-fg";
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $label }}
</span>
