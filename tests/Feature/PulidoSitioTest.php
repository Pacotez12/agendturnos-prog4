<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PulidoSitioTest extends TestCase
{
    use RefreshDatabase;

    public function test_favicon_ico_no_esta_vacio(): void
    {
        $path = public_path('favicon.ico');

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));
    }

    public function test_archivos_iconos_existen(): void
    {
        $this->assertFileExists(public_path('favicon.svg'));
        $this->assertFileExists(public_path('apple-touch-icon.png'));
        $this->assertFileExists(public_path('icons/icon-192.png'));
        $this->assertFileExists(public_path('icons/icon-512.png'));
    }

    public function test_favicon_y_theme_color_presentes_en_layouts(): void
    {
        $paciente = User::factory()->paciente()->create();
        $funcionario = User::factory()->funcionario()->create();

        // 1. Invitado layout
        $respInvitado = $this->get('/');
        $respInvitado->assertSee('<link rel="icon" href="/favicon.svg" type="image/svg+xml">', false);
        $respInvitado->assertSee('<link rel="icon" href="/favicon.ico" sizes="32x32">', false);
        $respInvitado->assertSee('<link rel="apple-touch-icon" href="/apple-touch-icon.png">', false);
        $respInvitado->assertSee('<meta name="theme-color" media="(prefers-color-scheme: light)" content="#FBF7F2">', false);
        $respInvitado->assertSee('<meta name="theme-color" media="(prefers-color-scheme: dark)" content="#171311">', false);

        // 2. Paciente layout
        $respPaciente = $this->actingAs($paciente)->get(route('paciente.inicio'));
        $respPaciente->assertSee('<link rel="icon" href="/favicon.svg" type="image/svg+xml">', false);
        $respPaciente->assertSee('<link rel="icon" href="/favicon.ico" sizes="32x32">', false);
        $respPaciente->assertSee('<link rel="apple-touch-icon" href="/apple-touch-icon.png">', false);
        $respPaciente->assertSee('<meta name="theme-color" media="(prefers-color-scheme: light)" content="#FBF7F2">', false);
        $respPaciente->assertSee('<meta name="theme-color" media="(prefers-color-scheme: dark)" content="#171311">', false);

        // 3. Panel layout
        $respPanel = $this->actingAs($funcionario)->get(route('panel.inicio'));
        $respPanel->assertSee('<link rel="icon" href="/favicon.svg" type="image/svg+xml">', false);
        $respPanel->assertSee('<link rel="icon" href="/favicon.ico" sizes="32x32">', false);
        $respPanel->assertSee('<link rel="apple-touch-icon" href="/apple-touch-icon.png">', false);
        $respPanel->assertSee('<meta name="theme-color" media="(prefers-color-scheme: light)" content="#FBF7F2">', false);
        $respPanel->assertSee('<meta name="theme-color" media="(prefers-color-scheme: dark)" content="#171311">', false);
    }

    public function test_cta_portada_dice_reservar_turno(): void
    {
        // Invitado
        $respInvitado = $this->get('/');
        $respInvitado->assertOk();
        $respInvitado->assertSee('Reservar turno');
        $respInvitado->assertSee(route('registro'));
        $respInvitado->assertSee('¿Ya tenés cuenta?');
        $respInvitado->assertSee(route('login'));

        // Paciente logueado
        $paciente = User::factory()->paciente()->create();
        $respPaciente = $this->actingAs($paciente)->get('/');
        $respPaciente->assertOk();
        $respPaciente->assertSee('Reservar turno');
        $respPaciente->assertSee(route('paciente.reservar'));

        // Personal logueado: va a cargar un turno desde el panel
        foreach ([User::factory()->funcionario()->create(), User::factory()->profesional()->create()] as $personal) {
            $this->actingAs($personal)->get('/')
                ->assertOk()
                ->assertSee(route('panel.turnos.crear'));
        }
    }

    public function test_paginas_de_error_renderizan_su_texto_con_el_codigo_correcto(): void
    {
        // 404
        $resp404 = $this->get('/ruta-inexistente-xyz-987');
        $resp404->assertStatus(404);
        $resp404->assertSee('404');
        $resp404->assertSee('No encontramos esa página');
        $resp404->assertSee('Ir al inicio');
        $resp404->assertSee('wa.me/595982774586');

        // 403
        $paciente = User::factory()->paciente()->create();
        $resp403 = $this->actingAs($paciente)->get(route('panel.inicio'));
        $resp403->assertStatus(403);
        $resp403->assertSee('403');
        $resp403->assertSee('No tenés permiso para ver esto');
        $resp403->assertSee('Ir al inicio');
        $resp403->assertSee('wa.me/595982774586');

        // 419
        $view419 = view('errors.419')->render();
        $this->assertStringContainsString('419', $view419);
        $this->assertStringContainsString('Tu sesión venció. Volvé a iniciar sesión', $view419);
        $this->assertStringContainsString(route('login'), $view419);
        $this->assertStringContainsString('Ir al inicio', $view419);
        $this->assertStringContainsString('wa.me/595982774586', $view419);

        // 429
        $view429 = view('errors.429')->render();
        $this->assertStringContainsString('429', $view429);
        $this->assertStringContainsString('Demasiados intentos, esperá un minuto', $view429);
        $this->assertStringContainsString('Ir al inicio', $view429);
        $this->assertStringContainsString('wa.me/595982774586', $view429);

        // 500
        $view500 = view('errors.500')->render();
        $this->assertStringContainsString('500', $view500);
        $this->assertStringContainsString('Algo salió mal de nuestro lado', $view500);
        $this->assertStringContainsString('Ir al inicio', $view500);
        $this->assertStringContainsString('wa.me/595982774586', $view500);

        // 503
        $view503 = view('errors.503')->render();
        $this->assertStringContainsString('503', $view503);
        $this->assertStringContainsString('Estamos en mantenimiento. Volvemos en unos minutos', $view503);
        $this->assertStringContainsString('Ir al inicio', $view503);
        $this->assertStringContainsString('wa.me/595982774586', $view503);
    }
}
