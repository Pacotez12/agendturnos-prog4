@props([
    'name',
    'label',
    'value' => null,
    'help' => null,
    'id' => null,
    'min' => null,
    'max' => null,
])

<x-input
    type="date"
    :name="$name"
    :label="$label"
    :value="$value"
    :help="$help"
    :id="$id"
    {{ $attributes->merge(array_filter(['min' => $min, 'max' => $max], fn ($v) => ! is_null($v))) }}
/>
