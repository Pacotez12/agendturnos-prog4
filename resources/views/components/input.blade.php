@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'help' => null,
    'id' => null,
])

@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $inputId = $id ?? $name;
    $hasError = $errors->has($name);
    $errorId = "{$inputId}-error";
    $helpId = "{$inputId}-help";

    $describedBy = [];
    if ($hasError) {
        $describedBy[] = $errorId;
    }
    if ($help) {
        $describedBy[] = $helpId;
    }
    $ariaDescribedBy = ! empty($describedBy) ? implode(' ', $describedBy) : null;

    $baseClasses = 'block w-full min-h-11 rounded-lg border bg-white px-3 py-2 text-ink-900 placeholder:text-ink-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 disabled:opacity-50 transition-colors duration-150 motion-reduce:transition-none';
    $borderClass = $hasError ? 'border-danger-700 focus-visible:ring-danger-700' : 'border-line';
@endphp

<div>
    <label for="{{ $inputId }}" class="block text-sm font-semibold text-ink-900 mb-1">
        {{ $label }}
    </label>

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $inputId }}"
        value="{{ old($name, $value) }}"
        @if($hasError) aria-invalid="true" @endif
        @if($ariaDescribedBy) aria-describedby="{{ $ariaDescribedBy }}" @endif
        {{ $attributes->merge(['class' => "{$baseClasses} {$borderClass}"]) }}
    />

    @if($help)
        <p id="{{ $helpId }}" class="mt-1 text-xs text-ink-500">{{ $help }}</p>
    @endif

    @if($hasError)
        <p id="{{ $errorId }}" role="alert" class="mt-1 text-xs text-danger-700">
            {{ $errors->first($name) }}
        </p>
    @endif
</div>
