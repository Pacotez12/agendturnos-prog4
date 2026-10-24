@props([
    'titulo',
    'texto' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center p-8 text-center rounded-lg border border-dashed border-line bg-card']) }}>
    <h3 class="font-display font-bold text-lg text-ink-900">{{ $titulo }}</h3>
    @if($texto)
        <p class="mt-1 text-sm text-ink-500 max-w-sm">{{ $texto }}</p>
    @endif
    @if(! $slot->isEmpty())
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>
