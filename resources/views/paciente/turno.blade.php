@php
    $esFamiliar = $turno->paciente->user_id !== auth()->id();
    $nombre = $turno->paciente->nombre;

    $info = match ($turno->estado) {
        \App\Enums\EstadoTurno::Solicitado => [
            'titulo' => $esFamiliar ? "El turno de {$nombre} fue solicitado" : 'Tu turno fue solicitado',
            'subtitulo' => 'La clínica lo revisa y lo confirma. Vas a ver el cambio de estado en tu inicio.',
            'icono' => 'phosphor-clock',
            'icono_color' => 'estado-solicitado-bg estado-solicitado-fg',
        ],
        \App\Enums\EstadoTurno::Confirmado => [
            'titulo' => $esFamiliar ? "El turno de {$nombre} está confirmado" : 'Tu turno está confirmado',
            'subtitulo' => 'Te esperamos. Si no podés venir, cancelalo con anticipación.',
            'icono' => 'phosphor-check',
            'icono_color' => 'estado-confirmado-bg estado-confirmado-fg',
        ],
        \App\Enums\EstadoTurno::Cancelado => [
            'titulo' => $esFamiliar ? "El turno de {$nombre} fue cancelado" : 'Este turno fue cancelado',
            'subtitulo' => null,
            'icono' => 'phosphor-x',
            'icono_color' => 'estado-cancelado-bg estado-cancelado-fg',
        ],
        \App\Enums\EstadoTurno::Atendido => [
            'titulo' => $esFamiliar ? "El turno de {$nombre} fue atendido" : 'Turno atendido',
            'subtitulo' => null,
            'icono' => 'phosphor-check',
            'icono_color' => 'estado-atendido-bg estado-atendido-fg',
        ],
        \App\Enums\EstadoTurno::Ausente => [
            'titulo' => $esFamiliar ? "{$nombre} no asistió a este turno" : 'No asististe a este turno',
            'subtitulo' => null,
            'icono' => 'phosphor-x',
            'icono_color' => 'estado-ausente-bg estado-ausente-fg',
        ],
    };
@endphp

<x-layouts.paciente :titulo="$info['titulo']">
    <div class="max-w-xl mx-auto py-4 sm:py-8">
        <x-card :paciente="true" class="p-6 sm:p-8 space-y-6">
            <div class="flex flex-col items-center text-center space-y-3">
                <div class="size-16 rounded-full {{ $info['icono_color'] }} flex items-center justify-center">
                    @if ($info['icono'] === 'phosphor-clock')
                        <x-phosphor-clock class="size-8" aria-hidden="true" />
                    @elseif ($info['icono'] === 'phosphor-check')
                        <x-phosphor-check class="size-8" aria-hidden="true" />
                    @else
                        <x-phosphor-x class="size-8" aria-hidden="true" />
                    @endif
                </div>
                <h1 class="text-2xl font-bold font-display text-ink-900">
                    {{ $info['titulo'] }}
                </h1>
                <x-estado-turno :estado="$turno->estado" />
            </div>

            <div class="border-t border-b border-line py-4 divide-y divide-line/60 text-sm">
                <div class="py-2.5 flex justify-between gap-4">
                    <span class="text-ink-500 font-medium">Día</span>
                    <span class="font-semibold text-ink-900 text-right">
                        {{ ucfirst($turno->inicio->translatedFormat('l j \d\e F, H:i')) }}
                    </span>
                </div>
                <div class="py-2.5 flex justify-between gap-4">
                    <span class="text-ink-500 font-medium">Profesional</span>
                    <span class="font-semibold text-ink-900 text-right">
                        {{ $turno->profesional->nombre }} {{ $turno->profesional->apellido }}
                    </span>
                </div>
                <div class="py-2.5 flex justify-between gap-4">
                    <span class="text-ink-500 font-medium">Servicio</span>
                    <span class="font-semibold text-ink-900 text-right">
                        {{ $turno->servicio->nombre }} · {{ $turno->servicio->duracion_min }} min
                    </span>
                </div>
                <div class="py-2.5 flex justify-between gap-4">
                    <span class="text-ink-500 font-medium">Paciente</span>
                    <span class="font-semibold text-ink-900 text-right">
                        {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }}
                    </span>
                </div>
            </div>

            @if ($info['subtitulo'])
                <p class="text-xs text-ink-500 text-center">
                    {{ $info['subtitulo'] }}
                </p>
            @endif

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                <x-button :pill="true" href="{{ route('paciente.inicio') }}" class="w-full sm:w-auto">
                    Volver al inicio
                </x-button>
                <x-button variant="secondary" :pill="true" href="{{ route('paciente.turnos') }}" class="w-full sm:w-auto">
                    Mis turnos
                </x-button>
                <x-button variant="secondary" :pill="true" href="{{ route('paciente.reservar') }}" class="w-full sm:w-auto">
                    Reservar otro turno
                </x-button>
            </div>
        </x-card>
    </div>
</x-layouts.paciente>
