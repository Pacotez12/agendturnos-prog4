@if ($prof->activo)
    <x-button variant="ghost" :href="route('panel.profesionales.servicios', $prof)" aria-label="Servicios que ofrece {{ $prof->nombreCompleto() }}">Servicios</x-button>
    <x-button
        variant="ghost"
        aria-label="Editar a {{ $prof->nombreCompleto() }}"
        x-on:click="editar({{ \Illuminate\Support\Js::from([
            'id' => $prof->id,
            'nombre' => $prof->nombre,
            'apellido' => $prof->apellido,
            'matricula' => $prof->matricula,
            'email' => $prof->user?->email ?? '',
        ]) }}); $dispatch('abrir-modal', 'profesional')"
    >Editar</x-button>
    <x-button variant="ghost" x-on:click="$dispatch('abrir-modal', 'baja-{{ $prof->id }}')">
        <x-phosphor-user-minus class="size-4" aria-hidden="true" />
        Dar de baja
    </x-button>
@else
    <x-button variant="ghost" x-on:click="$dispatch('abrir-modal', 'reactivar-{{ $prof->id }}')">
        <x-phosphor-arrow-counter-clockwise class="size-4" aria-hidden="true" />
        Reactivar
    </x-button>
@endif
