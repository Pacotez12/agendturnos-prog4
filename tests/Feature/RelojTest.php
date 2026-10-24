<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelojTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_muestra_reloj_con_hora_del_servidor_y_aria_label(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-24 14:30:15', config('app.timezone')));

        $funcionario = User::factory()->funcionario()->create();

        $response = $this->actingAs($funcionario)->get(route('panel.inicio'));

        $response->assertOk();
        $response->assertSee('aria-label="Hora actual"', false);
        $response->assertSee('14:30:15');
        $response->assertSee('sáb 24 oct');
    }

    public function test_inicio_paciente_no_muestra_reloj(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-24 14:30:15', config('app.timezone')));

        $pacienteUser = User::factory()->paciente()->create();

        $response = $this->actingAs($pacienteUser)->get(route('paciente.inicio'));

        $response->assertOk();
        $response->assertDontSee('aria-label="Hora actual"', false);
    }
}
