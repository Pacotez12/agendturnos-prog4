<?php

namespace App\Support;

use App\Enums\RolUsuario;
use Illuminate\Support\Facades\Route;

class Navegacion
{
    public static function inicioPara(RolUsuario $rol): string
    {
        return match ($rol) {
            RolUsuario::Paciente => 'paciente.inicio',
            RolUsuario::Profesional, RolUsuario::Funcionario => 'panel.inicio',
        };
    }

    /**
     * @return array<int, array{etiqueta: string, ruta: string}>
     */
    public static function para(RolUsuario $rol): array
    {
        $definicion = match ($rol) {
            RolUsuario::Paciente => [
                ['etiqueta' => 'Inicio', 'ruta' => 'paciente.inicio'],
                ['etiqueta' => 'Servicios', 'ruta' => 'servicios.catalogo'],
                ['etiqueta' => 'Reservar turno', 'ruta' => 'paciente.reservar'],
                ['etiqueta' => 'Mis turnos', 'ruta' => 'paciente.turnos'],
                ['etiqueta' => 'Mi familia', 'ruta' => 'paciente.familia'],
                ['etiqueta' => 'Mi perfil', 'ruta' => 'paciente.perfil'],
            ],
            RolUsuario::Profesional => [
                ['etiqueta' => 'Inicio', 'ruta' => 'panel.inicio'],
                ['etiqueta' => 'Agenda', 'ruta' => 'panel.agenda'],
                ['etiqueta' => 'Solicitudes', 'ruta' => 'panel.solicitudes'],
                ['etiqueta' => 'Nuevo turno', 'ruta' => 'panel.turnos.crear'],
                ['etiqueta' => 'Pacientes', 'ruta' => 'panel.pacientes'],
                ['etiqueta' => 'Disponibilidad', 'ruta' => 'panel.disponibilidad'],
            ],
            RolUsuario::Funcionario => [
                ['etiqueta' => 'Inicio', 'ruta' => 'panel.inicio'],
                ['etiqueta' => 'Agenda', 'ruta' => 'panel.agenda'],
                ['etiqueta' => 'Solicitudes', 'ruta' => 'panel.solicitudes'],
                ['etiqueta' => 'Nuevo turno', 'ruta' => 'panel.turnos.crear'],
                ['etiqueta' => 'Pacientes', 'ruta' => 'panel.pacientes'],
                ['etiqueta' => 'Disponibilidad', 'ruta' => 'panel.disponibilidad'],
                ['etiqueta' => 'Servicios', 'ruta' => 'panel.servicios'],
                ['etiqueta' => 'Especialidades', 'ruta' => 'panel.especialidades'],
                ['etiqueta' => 'Profesionales', 'ruta' => 'panel.profesionales'],
            ],
        };

        return array_values(array_filter(
            $definicion,
            fn (array $item) => Route::has($item['ruta'])
        ));
    }
}
