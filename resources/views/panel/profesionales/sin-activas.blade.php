<x-layouts.panel titulo="Disponibilidad">
    <x-empty-state titulo="No hay profesionales activas" texto="Reactivá una profesional para gestionar su disponibilidad.">
        <x-button :href="route('panel.profesionales')">Ver profesionales</x-button>
    </x-empty-state>
</x-layouts.panel>
