@php
    $tz = config('app.timezone', 'America/Asuncion');
    $now = now($tz);
    $serverMs = $now->getTimestampMs();
    $horaInicial = $now->format('H:i:s');
    $fechaInicial = str_replace('.', '', mb_strtolower($now->translatedFormat('D j M')));
@endphp

<time
    datetime="{{ $now->toIso8601String() }}"
    aria-label="Hora actual"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-xs text-ink-500']) }}
    x-data="{
        offset: {{ $serverMs }} - Date.now(),
        hora: '{{ $horaInicial }}',
        fecha: '{{ $fechaInicial }}',
        formateadorHora: new Intl.DateTimeFormat('es-PY', { timeZone: '{{ $tz }}', hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' }),
        formateadorFecha: new Intl.DateTimeFormat('es-PY', { timeZone: '{{ $tz }}', weekday: 'short', day: 'numeric', month: 'short' }),
        actualizar() {
            const d = new Date(Date.now() + this.offset);
            this.hora = this.formateadorHora.format(d);
            this.fecha = this.formateadorFecha.format(d).replace(/[.,]/g, '').toLowerCase();
        },
        init() {
            setInterval(() => this.actualizar(), 1000);
        }
    }"
>
    <x-phosphor-clock class="size-4 shrink-0 text-ink-500" aria-hidden="true" />
    <span class="font-bold text-ink-900 tabular-nums" x-text="hora">{{ $horaInicial }}</span>
    <span class="text-ink-500" x-text="fecha">{{ $fechaInicial }}</span>
</time>
