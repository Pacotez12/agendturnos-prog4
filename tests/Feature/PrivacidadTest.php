<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacidadTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_privacidad_responde_ok_y_contiene_secciones(): void
    {
        $response = $this->get(route('privacidad'));

        $response->assertOk();
        $response->assertSee('Política de privacidad');

        // Quién es responsable
        $response->assertSee('Glow Dental Protect');
        $response->assertSee('Asunción');
        $response->assertSee('glowdentalprotect@gmail.com');
        $response->assertSee('+595 982 774586');

        // Qué datos guardamos y qué NO
        $response->assertSee('nombre');
        $response->assertSee('documento');
        $response->assertSee('fecha de nacimiento');
        $response->assertSee('teléfono');
        $response->assertSee('correo');
        $response->assertSee('turnos');
        $response->assertSee('NO guardamos historia clínica ni diagnósticos en este sistema');

        // Para qué
        $response->assertSee('gestionar turnos');
        $response->assertSee('enviar avisos');

        // Quién los ve
        $response->assertSee('recepción');
        $response->assertSee('profesionales');
        $response->assertSee('no se venden ni se comparten con terceros');
        $response->assertSee('WhatsApp');

        // Cuánto tiempo
        $response->assertSee('mientras la cuenta esté activa');

        // Derechos y normativa
        $response->assertSee('acceso, corrección o baja');
        $response->assertSee('normativa paraguaya de protección de datos personales');

        // Fecha de última actualización
        $response->assertSee('24 de octubre de 2026');
    }

    public function test_registro_sin_la_casilla_de_privacidad_falla(): void
    {
        $response = $this->post('/registro', [
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'tipo_documento' => 'CI',
            'documento' => '1234567',
            'fecha_nacimiento' => '1990-01-01',
            'telefono' => '0981 123 456',
            'email' => 'juan@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            // Sin 'privacidad'
        ]);

        $response->assertSessionHasErrors('privacidad');
        $this->assertDatabaseMissing('users', ['email' => 'juan@example.com']);
    }

    public function test_registro_con_la_casilla_guarda_privacidad_aceptada_at(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-24 10:30:00'));

        $response = $this->post('/registro', [
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'tipo_documento' => 'CI',
            'documento' => '1234567',
            'fecha_nacimiento' => '1990-01-01',
            'telefono' => '0981 123 456',
            'email' => 'juan@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'privacidad' => '1',
        ]);

        $response->assertSessionDoesntHaveErrors();

        $user = User::where('email', 'juan@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->privacidad_aceptada_at);
        $this->assertSame('2026-10-24 10:30:00', $user->privacidad_aceptada_at->format('Y-m-d H:i:s'));
    }

    public function test_enlace_privacidad_aparece_en_layout_invitado_y_paciente(): void
    {
        // Layout invitado
        $responseInvitado = $this->get('/');
        $responseInvitado->assertOk();
        $responseInvitado->assertSee(route('privacidad'));
        $responseInvitado->assertSee('Privacidad');

        // Layout paciente
        $pacienteUser = User::factory()->paciente()->create();
        $responsePaciente = $this->actingAs($pacienteUser)->get('/inicio');
        $responsePaciente->assertOk();
        $responsePaciente->assertSee(route('privacidad'));
        $responsePaciente->assertSee('Privacidad');
    }
}
