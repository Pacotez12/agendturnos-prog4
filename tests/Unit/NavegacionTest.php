<?php

namespace Tests\Unit;

use App\Enums\RolUsuario;
use App\Support\Navegacion;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NavegacionTest extends TestCase
{
    public function test_paciente_ve_solo_rutas_existentes(): void
    {
        Route::get('/a', fn () => '')->name('paciente.inicio');
        Route::get('/b', fn () => '')->name('paciente.turnos');
        Route::getRoutes()->refreshNameLookups();

        $items = Navegacion::para(RolUsuario::Paciente);

        $this->assertSame([
            ['etiqueta' => 'Inicio', 'ruta' => 'paciente.inicio'],
            ['etiqueta' => 'Mis turnos', 'ruta' => 'paciente.turnos'],
        ], $items);

        $etiquetas = array_column($items, 'etiqueta');
        $this->assertSame(['Inicio', 'Mis turnos'], $etiquetas);
    }

    public function test_funcionario_incluye_servicios_y_profesional_no(): void
    {
        Route::get('/agenda', fn () => '')->name('panel.agenda');
        Route::get('/servicios', fn () => '')->name('panel.servicios');
        Route::getRoutes()->refreshNameLookups();

        $itemsFuncionario = Navegacion::para(RolUsuario::Funcionario);
        $etiquetasFuncionario = array_column($itemsFuncionario, 'etiqueta');
        $this->assertContains('Servicios', $etiquetasFuncionario);
        $this->assertContains('Agenda', $etiquetasFuncionario);

        $itemsProfesional = Navegacion::para(RolUsuario::Profesional);
        $etiquetasProfesional = array_column($itemsProfesional, 'etiqueta');
        $this->assertNotContains('Servicios', $etiquetasProfesional);
        $this->assertContains('Agenda', $etiquetasProfesional);
    }
}
