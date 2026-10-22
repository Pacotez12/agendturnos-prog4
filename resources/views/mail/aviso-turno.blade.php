<x-mail::message>
@php
    $sujetoTurno = $esFamiliar ? "el turno de {$nombrePaciente}" : "tu turno";
@endphp

@if ($tipo === 'confirmacion')
# Tu turno está confirmado

Hola {{ $nombreDestinatario }}, la clínica confirmó {{ $sujetoTurno }}.

<x-mail::panel>
**Día:** {{ ucfirst($turno->inicio->translatedFormat('l j \d\e F, H:i')) }}  
**Profesional:** {{ $turno->profesional->nombre }} {{ $turno->profesional->apellido }}  
**Servicio:** {{ $turno->servicio->nombre }} · {{ $turno->servicio->duracion_min }} min  
**Paciente:** {{ $nombrePaciente }}
</x-mail::panel>

<x-mail::button :url="route('paciente.turnos.show', $turno)">
Ver mi turno
</x-mail::button>

Si no podés venir, avisanos por WhatsApp al {{ $whatsapp }}.

@elseif ($tipo === 'cancelacion')
# Tu turno fue cancelado

Hola {{ $nombreDestinatario }}, la clínica canceló {{ $sujetoTurno }} del {{ $turno->inicio->translatedFormat('l j \d\e F') }} a las {{ $turno->inicio->format('H:i') }} ({{ $turno->servicio->nombre }} con {{ $turno->profesional->nombre }} {{ $turno->profesional->apellido }}).

@if ($turno->motivo_cancelacion)
<x-mail::panel>
**Motivo:** {{ $turno->motivo_cancelacion }}
</x-mail::panel>
@endif

<x-mail::button :url="route('paciente.reservar')">
Reservar otro turno
</x-mail::button>

@elseif ($tipo === 'reprogramacion')
# Tu turno cambió de horario

Hola {{ $nombreDestinatario }}, la clínica movió {{ $sujetoTurno }}.

@php
    $inicioAnterior = isset($datos['anterior']) ? \Carbon\Carbon::parse($datos['anterior'])->setTimezone(config('app.timezone')) : null;
@endphp
<x-mail::panel>
@if ($inicioAnterior)
**Antes:** <del>{{ ucfirst($inicioAnterior->translatedFormat('l j \d\e F, H:i')) }}</del>  
@endif
**Ahora:** {{ ucfirst($turno->inicio->translatedFormat('l j \d\e F, H:i')) }}  
**Profesional:** {{ $turno->profesional->nombre }} {{ $turno->profesional->apellido }}  
**Servicio:** {{ $turno->servicio->nombre }} · {{ $turno->servicio->duracion_min }} min  
**Paciente:** {{ $nombrePaciente }}
</x-mail::panel>

<x-mail::button :url="route('paciente.turnos.show', $turno)">
Ver mi turno
</x-mail::button>

Si el nuevo horario no te sirve, escribinos por WhatsApp al {{ $whatsapp }}.

@elseif ($tipo === 'recordatorio')
# Te esperamos {{ $cuando }}

Hola {{ $nombreDestinatario }}, te recordamos {{ $sujetoTurno }}.

<x-mail::panel>
**Día:** {{ ucfirst($turno->inicio->translatedFormat('l j \d\e F, H:i')) }}  
**Profesional:** {{ $turno->profesional->nombre }} {{ $turno->profesional->apellido }}  
**Servicio:** {{ $turno->servicio->nombre }} · {{ $turno->servicio->duracion_min }} min  
**Paciente:** {{ $nombrePaciente }}
</x-mail::panel>

<x-mail::button :url="route('paciente.turnos.show', $turno)">
Ver mi turno
</x-mail::button>

Si no podés venir, avisanos por WhatsApp al {{ $whatsapp }} así liberamos el horario.
@endif
</x-mail::message>
