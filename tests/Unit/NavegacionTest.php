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
            ['etiqueta' => 'Servicios', 'ruta' => 'servicios.catalogo'],
            ['etiqueta' => 'Reservar turno', 'ruta' => 'paciente.reservar'],
            ['etiqueta' => 'Mis turnos', 'ruta' => 'paciente.turnos'],
            ['etiqueta' => 'Mi familia', 'ruta' => 'paciente.familia'],
        ], $items);

        // "Mi perfil" todavía no tiene ruta, así que no aparece
        $etiquetas = array_column($items, 'etiqueta');
        $this->assertSame(['Inicio', 'Servicios', 'Reservar turno', 'Mis turnos', 'Mi familia'], $etiquetas);
    }

    public function test_funcionario_incluye_servicios_especialidades_y_profesionales(): void
    {
        Route::get('/panel', fn () => '')->name('panel.inicio');
        Route::get('/agenda', fn () => '')->name('panel.agenda');
        Route::get('/servicios', fn () => '')->name('panel.servicios');
        Route::get('/especialidades', fn () => '')->name('panel.especialidades');
        Route::get('/profesionales', fn () => '')->name('panel.profesionales');
        Route::getRoutes()->refreshNameLookups();

        $itemsFuncionario = Navegacion::para(RolUsuario::Funcionario);
        $etiquetasFuncionario = array_column($itemsFuncionario, 'etiqueta');
        $this->assertContains('Inicio', $etiquetasFuncionario);
        $this->assertContains('Servicios', $etiquetasFuncionario);
        $this->assertContains('Especialidades', $etiquetasFuncionario);
        $this->assertContains('Profesionales', $etiquetasFuncionario);
        $this->assertContains('Agenda', $etiquetasFuncionario);

        $itemsProfesional = Navegacion::para(RolUsuario::Profesional);
        $etiquetasProfesional = array_column($itemsProfesional, 'etiqueta');
        $this->assertContains('Inicio', $etiquetasProfesional);
        $this->assertNotContains('Servicios', $etiquetasProfesional);
        $this->assertNotContains('Especialidades', $etiquetasProfesional);
        $this->assertNotContains('Profesionales', $etiquetasProfesional);
        $this->assertContains('Agenda', $etiquetasProfesional);
    }

    public function test_inicio_para_devuelve_ruta_segun_rol(): void
    {
        $this->assertSame('paciente.inicio', Navegacion::inicioPara(RolUsuario::Paciente));
        $this->assertSame('panel.inicio', Navegacion::inicioPara(RolUsuario::Profesional));
        $this->assertSame('panel.inicio', Navegacion::inicioPara(RolUsuario::Funcionario));
    }
}
