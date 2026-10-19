<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutsTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_paciente(): void
    {
        $user = User::factory()->paciente()->create();

        $view = $this->actingAs($user)->blade(
            '<x-layouts.paciente titulo="Inicio">contenido</x-layouts.paciente>'
        );

        $view->assertSee('contenido');
        $view->assertSee('Inicio · Glow Dental Protect');
        $view->assertSee('aria-expanded="false"', false);
        $view->assertSee('images/glow-logo.svg');
    }

    public function test_layout_panel_funcionario_sin_perfil(): void
    {
        $user = User::factory()->funcionario()->create();

        $view = $this->actingAs($user)->blade(
            '<x-layouts.panel titulo="Panel">contenido panel</x-layouts.panel>'
        );

        $view->assertSee('contenido panel');
        $view->assertSee('Panel · Glow Dental Protect');
        $view->assertSee($user->email);
    }

    public function test_layout_muestra_flash(): void
    {
        session()->flash('success', 'Turno solicitado');
        $user = User::factory()->paciente()->create();

        $view = $this->actingAs($user)->blade(
            '<x-layouts.paciente titulo="Inicio">contenido</x-layouts.paciente>'
        );

        $view->assertSee('Turno solicitado');
        $view->assertSee('role="status"', false);
    }

    public function test_layout_invitado(): void
    {
        $view = $this->blade(
            '<x-layouts.invitado titulo="Acceso">contenido invitado</x-layouts.invitado>'
        );

        $view->assertSee('contenido invitado');
        $view->assertSee('Acceso · Glow Dental Protect');
        $view->assertSee('images/glow-logo.svg');
        $view->assertDontSee('Cerrar sesión');
    }
}
