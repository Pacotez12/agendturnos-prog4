@props([
    'name',
    'horarios' => [],
    'value' => null,
])

@php
    $currentValue = old($name, $value);
@endphp

<div {{ $attributes->merge(['class' => 'grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2']) }}>
    @foreach($horarios as $item)
        @php
            $hora = $item['hora'];
            $libre = (bool) ($item['libre'] ?? false);
            $isSelected = $libre && ((string) $currentValue === (string) $hora);
        @endphp

        <label
            class="relative flex items-center justify-center min-h-11 px-3 py-2 text-sm font-medium rounded-lg border transition-colors duration-150 motion-reduce:transition-none select-none
            @if(! $libre)
                border-line bg-surface text-ink-500 line-through opacity-50 cursor-not-allowed
            @else
                cursor-pointer border-line bg-card text-ink-900 hover:border-brand-600 hover:bg-cream-50 has-checked:border-brand-600 has-checked:bg-brand-100 has-checked:text-brand-700 has-checked:font-semibold has-focus-visible:ring-2 has-focus-visible:ring-brand-600 has-focus-visible:ring-offset-1
            @endif"
        >
            <input
                type="radio"
                name="{{ $name }}"
                value="{{ $hora }}"
                @disabled(! $libre)
                @checked($isSelected)
                class="sr-only"
            />
            <span>{{ $hora }}</span>
        </label>
    @endforeach
</div>
