<?php

namespace App\Support;

use App\Enums\RolUsuario;
use Illuminate\Support\Facades\Route;

class Navegacion
{
    /**
     * @return array<int, array{etiqueta: string, ruta: string}>
     */
    public static function para(RolUsuario $rol): array
    {
        $definicion = match ($rol) {
            RolUsuario::Paciente => [
                ['etiqueta' => 'Inicio', 'ruta' => 'paciente.inicio'],
                ['etiqueta' => 'Reservar turno', 'ruta' => 'paciente.reservar'],
                ['etiqueta' => 'Mis turnos', 'ruta' => 'paciente.turnos'],
                ['etiqueta' => 'Mi familia', 'ruta' => 'paciente.familia'],
                ['etiqueta' => 'Mi perfil', 'ruta' => 'paciente.perfil'],
            ],
            RolUsuario::Profesional => [
                ['etiqueta' => 'Agenda', 'ruta' => 'panel.agenda'],
                ['etiqueta' => 'Solicitudes', 'ruta' => 'panel.solicitudes'],
                ['etiqueta' => 'Nuevo turno', 'ruta' => 'panel.turnos.crear'],
                ['etiqueta' => 'Pacientes', 'ruta' => 'panel.pacientes'],
                ['etiqueta' => 'Disponibilidad', 'ruta' => 'panel.disponibilidad'],
            ],
            RolUsuario::Funcionario => [
                ['etiqueta' => 'Agenda', 'ruta' => 'panel.agenda'],
                ['etiqueta' => 'Solicitudes', 'ruta' => 'panel.solicitudes'],
                ['etiqueta' => 'Nuevo turno', 'ruta' => 'panel.turnos.crear'],
                ['etiqueta' => 'Pacientes', 'ruta' => 'panel.pacientes'],
                ['etiqueta' => 'Disponibilidad', 'ruta' => 'panel.disponibilidad'],
                ['etiqueta' => 'Servicios', 'ruta' => 'panel.servicios'],
            ],
        };

        return array_values(array_filter(
            $definicion,
            fn (array $item) => Route::has($item['ruta'])
        ));
    }
}
