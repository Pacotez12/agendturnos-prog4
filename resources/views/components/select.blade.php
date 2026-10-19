@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'help' => null,
    'id' => null,
])

@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $selectId = $id ?? $name;
    $hasError = $errors->has($name);
    $errorId = "{$selectId}-error";
    $helpId = "{$selectId}-help";

    $describedBy = [];
    if ($hasError) {
        $describedBy[] = $errorId;
    }
    if ($help) {
        $describedBy[] = $helpId;
    }
    $ariaDescribedBy = ! empty($describedBy) ? implode(' ', $describedBy) : null;

    $baseClasses = 'block w-full min-h-11 rounded-lg border bg-white px-3 py-2 text-ink-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 disabled:opacity-50 transition-colors duration-150 motion-reduce:transition-none';
    $borderClass = $hasError ? 'border-danger-700 focus-visible:ring-danger-700' : 'border-line';
    $currentValue = old($name, $value);
@endphp

<div>
    <label for="{{ $selectId }}" class="block text-sm font-semibold text-ink-900 mb-1">
        {{ $label }}
    </label>

    <select
        name="{{ $name }}"
        id="{{ $selectId }}"
        @if($hasError) aria-invalid="true" @endif
        @if($ariaDescribedBy) aria-describedby="{{ $ariaDescribedBy }}" @endif
        {{ $attributes->merge(['class' => "{$baseClasses} {$borderClass}"]) }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach($options as $val => $text)
            <option value="{{ $val }}" @selected((string) $currentValue === (string) $val)>
                {{ $text }}
            </option>
        @endforeach
    </select>

    @if($help)
        <p id="{{ $helpId }}" class="mt-1 text-xs text-ink-500">{{ $help }}</p>
    @endif

    @if($hasError)
        <p id="{{ $errorId }}" role="alert" class="mt-1 text-xs text-danger-700">
            {{ $errors->first($name) }}
        </p>
    @endif
</div>
