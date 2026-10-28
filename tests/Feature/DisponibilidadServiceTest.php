<?php

namespace Tests\Feature;

use App\Enums\EstadoTurno;
use App\Models\Bloqueo;
use App\Models\Consultorio;
use App\Models\Disponibilidad;
use App\Models\Especialidad;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Services\DisponibilidadService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisponibilidadServiceTest extends TestCase
{
    use RefreshDatabase;

    private DisponibilidadService $service;

    private Profesional $profesional;

    private Consultorio $consultorio;

    private Servicio $servicio45;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DisponibilidadService::class);

        // Fijamos now un domingo a las 12:00
        Carbon::setTestNow(Carbon::parse('2026-10-18 12:00:00', config('app.timezone')));

        $this->consultorio = Consultorio::factory()->create(['activo' => true]);
        $this->profesional = Profesional::factory()->create();

        $esp = Especialidad::factory()->create(['activa' => true]);
        $this->servicio45 = Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Limpieza dental',
            'duracion_min' => 45,
            'activo' => true,
        ]);

        $this->profesional->servicios()->attach($this->servicio45->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_pasos_de_15_minutos_y_limite_de_duracion_en_franja(): void
    {
        // Lunes 19/10/2026, franja 08:00 a 12:00 (dia_semana ISO = 1)
        Disponibilidad::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
        ]);

        $fechaLunes = Carbon::parse('2026-10-19', config('app.timezone'));
        $franjas = $this->service->franjasLibres($this->profesional, $this->servicio45, $fechaLunes);

        $horas = array_column($franjas, 'hora');

        $this->assertContains('08:00', $horas);
        $this->assertContains('08:15', $horas);
        $this->assertContains('11:15', $horas);
        // 11:30 + 45 min = 12:15 > 12:00, no debe aparecer
        $this->assertNotContains('11:30', $horas);
    }

    public function test_turno_confirmado_bloquea_los_inicios_superpuestos(): void
    {
        Disponibilidad::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
        ]);

        $fechaLunes = Carbon::parse('2026-10-19', config('app.timezone'));

        // Turno confirmado 09:00 a 09:45
        Turno::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'servicio_id' => $this->servicio45->id,
            'inicio' => Carbon::parse('2026-10-19 09:00:00', config('app.timezone')),
            'fin' => Carbon::parse('2026-10-19 09:45:00', config('app.timezone')),
            'estado' => EstadoTurno::Confirmado,
        ]);

        $franjas = $this->service->franjasLibres($this->profesional, $this->servicio45, $fechaLunes);
        $librePorHora = array_column($franjas, 'libre', 'hora');

        // Para un servicio de 45 min:
        // 08:30 (hasta 09:15) choca con 09:00-09:45 -> false
        // 08:45 (hasta 09:30) choca -> false
        // 09:00 (hasta 09:45) choca -> false
        // 09:15 (hasta 10:00) choca -> false
        // 09:30 (hasta 10:15) choca -> false
        // 09:45 (hasta 10:30) NO choca (rango semiabierto) -> true
        $this->assertFalse($librePorHora['08:30']);
        $this->assertFalse($librePorHora['08:45']);
        $this->assertFalse($librePorHora['09:00']);
        $this->assertFalse($librePorHora['09:15']);
        $this->assertFalse($librePorHora['09:30']);
        $this->assertTrue($librePorHora['09:45']);
        $this->assertTrue($librePorHora['08:00']);
        $this->assertTrue($librePorHora['08:15']);
    }

    public function test_turno_cancelado_no_bloquea_horario(): void
    {
        Disponibilidad::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
        ]);

        $fechaLunes = Carbon::parse('2026-10-19', config('app.timezone'));

        Turno::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'servicio_id' => $this->servicio45->id,
            'inicio' => '2026-10-19 09:00:00',
            'fin' => '2026-10-19 09:45:00',
            'estado' => EstadoTurno::Cancelado,
        ]);

        $franjas = $this->service->franjasLibres($this->profesional, $this->servicio45, $fechaLunes);
        $librePorHora = array_column($franjas, 'libre', 'hora');

        $this->assertTrue($librePorHora['09:00']);
    }

    public function test_bloqueo_sin_consultorio_deja_todas_las_franjas_en_false(): void
    {
        Disponibilidad::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
        ]);

        $fechaLunes = Carbon::parse('2026-10-19', config('app.timezone'));

        Bloqueo::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => null,
            'inicio' => '2026-10-19 00:00:00',
            'fin' => '2026-10-19 23:59:59',
        ]);

        $franjas = $this->service->franjasLibres($this->profesional, $this->servicio45, $fechaLunes);

        foreach ($franjas as $f) {
            $this->assertFalse($f['libre']);
        }
    }

    public function test_dia_sin_franjas_retorna_arreglo_vacio(): void
    {
        $fechaMartes = Carbon::parse('2026-10-20', config('app.timezone'));
        $franjas = $this->service->franjasLibres($this->profesional, $this->servicio45, $fechaMartes);

        $this->assertSame([], $franjas);
    }

    public function test_inicios_pasados_no_aparecen(): void
    {
        // Now lunes a las 09:30
        Carbon::setTestNow(Carbon::parse('2026-10-19 09:30:00', config('app.timezone')));

        Disponibilidad::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
        ]);

        $fechaLunes = Carbon::parse('2026-10-19', config('app.timezone'));
        $franjas = $this->service->franjasLibres($this->profesional, $this->servicio45, $fechaLunes);
        $horas = array_column($franjas, 'hora');

        // <= 09:30 no deben aparecer
        $this->assertNotContains('08:00', $horas);
        $this->assertNotContains('09:15', $horas);
        $this->assertNotContains('09:30', $horas);
        $this->assertContains('09:45', $horas);
    }

    public function test_servicio_no_ofrecido_retorna_vacio_y_esta_libre_false(): void
    {
        $otroServicio = Servicio::factory()->create([
            'duracion_min' => 30,
            'activo' => true,
        ]);

        Disponibilidad::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
        ]);

        $fechaLunes = Carbon::parse('2026-10-19', config('app.timezone'));
        $franjas = $this->service->franjasLibres($this->profesional, $otroServicio, $fechaLunes);

        $this->assertSame([], $franjas);

        $inicio = Carbon::parse('2026-10-19 08:00:00', config('app.timezone'));
        $this->assertFalse($this->service->estaLibre($this->profesional, $otroServicio, $inicio));
    }

    public function test_esta_libre_y_consultorio_para_coherentes_con_franjas_libres(): void
    {
        Disponibilidad::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
        ]);

        $inicioLibre = Carbon::parse('2026-10-19 08:00:00', config('app.timezone'));
        $this->assertTrue($this->service->estaLibre($this->profesional, $this->servicio45, $inicioLibre));
        $this->assertSame($this->consultorio->id, $this->service->consultorioPara($this->profesional, $inicioLibre));

        // Fuera de disponibilidad
        $inicioFuera = Carbon::parse('2026-10-19 07:00:00', config('app.timezone'));
        $this->assertFalse($this->service->estaLibre($this->profesional, $this->servicio45, $inicioFuera));
        $this->assertNull($this->service->consultorioPara($this->profesional, $inicioFuera));
    }

    public function test_cambiar_config_intervalo_minutos_cambia_los_pasos(): void
    {
        config(['agenda.intervalo_minutos' => 30]);

        Disponibilidad::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
        ]);

        $fechaLunes = Carbon::parse('2026-10-19', config('app.timezone'));
        $franjas = $this->service->franjasLibres($this->profesional, $this->servicio45, $fechaLunes);
        $horas = array_column($franjas, 'hora');

        $this->assertSame(['08:00', '08:30', '09:00'], $horas);
        $this->assertNotContains('08:15', $horas);
        $this->assertNotContains('08:45', $horas);
    }

    public function test_esta_libre_rechaza_horario_no_alineado_al_intervalo(): void
    {
        Disponibilidad::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
        ]);

        $inicioDesalineado = Carbon::parse('2026-10-19 09:07:00', config('app.timezone'));
        $this->assertFalse($this->service->estaLibre($this->profesional, $this->servicio45, $inicioDesalineado));
    }

    public function test_servicio_con_especialidad_inactiva_retorna_vacio_y_esta_libre_false(): void
    {
        $espInactiva = Especialidad::factory()->create(['activa' => false]);
        $servicioInactivo = Servicio::factory()->create([
            'especialidad_id' => $espInactiva->id,
            'duracion_min' => 30,
            'activo' => true,
        ]);
        $this->profesional->servicios()->attach($servicioInactivo->id);

        Disponibilidad::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
        ]);

        $fechaLunes = Carbon::parse('2026-10-19', config('app.timezone'));
        $this->assertSame([], $this->service->franjasLibres($this->profesional, $servicioInactivo, $fechaLunes));
        $this->assertFalse($this->service->estaLibre($this->profesional, $servicioInactivo, Carbon::parse('2026-10-19 09:00:00', config('app.timezone'))));
    }

    public function test_alineacion_respeta_inicio_de_franja_y_franjas_libres_pasan_esta_libre(): void
    {
        $esp = Especialidad::factory()->create(['activa' => true]);
        $servicio15 = Servicio::factory()->create([
            'especialidad_id' => $esp->id,
            'nombre' => 'Control breve',
            'duracion_min' => 15,
            'activo' => true,
        ]);
        $this->profesional->servicios()->attach($servicio15->id);

        Disponibilidad::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:10',
            'hora_fin' => '10:00',
        ]);

        $tz = config('app.timezone');
        $inicio0810 = Carbon::parse('2026-10-19 08:10:00', $tz);
        $inicio0825 = Carbon::parse('2026-10-19 08:25:00', $tz);
        $inicio0815 = Carbon::parse('2026-10-19 08:15:00', $tz);

        $this->assertTrue($this->service->estaLibre($this->profesional, $servicio15, $inicio0810));
        $this->assertTrue($this->service->estaLibre($this->profesional, $servicio15, $inicio0825));
        $this->assertFalse($this->service->estaLibre($this->profesional, $servicio15, $inicio0815));

        $fechaLunes = Carbon::parse('2026-10-19', $tz);
        $franjas = $this->service->franjasLibres($this->profesional, $servicio15, $fechaLunes);
        $this->assertNotEmpty($franjas);

        foreach ($franjas as $franja) {
            if ($franja['libre']) {
                $inicioFranja = Carbon::parse('2026-10-19 '.$franja['hora'].':00', $tz);
                $this->assertTrue(
                    $this->service->estaLibre($this->profesional, $servicio15, $inicioFranja),
                    "El horario {$franja['hora']} devuelto por franjasLibres debería ser aceptado por estaLibre."
                );
            }
        }
    }
}
