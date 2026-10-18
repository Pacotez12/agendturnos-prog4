<?php

namespace Tests\Feature;

use App\Enums\EstadoNotificacion;
use App\Enums\EstadoTurno;
use App\Enums\RolUsuario;
use App\Models\Bloqueo;
use App\Models\Consultorio;
use App\Models\Disponibilidad;
use App\Models\Especialidad;
use App\Models\HistorialTurno;
use App\Models\Notificacion;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelosRelacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_casts_y_relaciones(): void
    {
        $userPaciente = User::factory()->paciente()->create();
        $this->assertInstanceOf(RolUsuario::class, $userPaciente->rol);
        $this->assertSame(RolUsuario::Paciente, $userPaciente->rol);

        $userProfesional = User::factory()->profesional()->create();
        $this->assertSame(RolUsuario::Profesional, $userProfesional->rol);

        $userFuncionario = User::factory()->funcionario()->create();
        $this->assertSame(RolUsuario::Funcionario, $userFuncionario->rol);

        $paciente = Paciente::factory()->create(['user_id' => $userPaciente->id]);
        $profesional = Profesional::factory()->create(['user_id' => $userProfesional->id]);
        $dependiente = Paciente::factory()->create(['responsable_id' => $userPaciente->id, 'user_id' => null]);

        $this->assertInstanceOf(Paciente::class, $userPaciente->fresh()->paciente);
        $this->assertSame($paciente->id, $userPaciente->fresh()->paciente->id);

        $this->assertInstanceOf(Profesional::class, $userProfesional->fresh()->profesional);
        $this->assertSame($profesional->id, $userProfesional->fresh()->profesional->id);

        $this->assertTrue($userPaciente->fresh()->dependientes->contains($dependiente));
    }

    public function test_paciente_relaciones_y_casts(): void
    {
        $user = User::factory()->paciente()->create();
        $responsable = User::factory()->paciente()->create();

        $paciente = Paciente::factory()->create([
            'user_id' => $user->id,
            'responsable_id' => $responsable->id,
            'fecha_nacimiento' => '1995-04-12',
        ]);

        $this->assertInstanceOf(\DateTimeInterface::class, $paciente->fecha_nacimiento);
        $this->assertSame('1995-04-12', $paciente->fecha_nacimiento->format('Y-m-d'));

        $this->assertInstanceOf(User::class, $paciente->user);
        $this->assertSame($user->id, $paciente->user->id);

        $this->assertInstanceOf(User::class, $paciente->responsable);
        $this->assertSame($responsable->id, $paciente->responsable->id);

        $turno = Turno::factory()->create(['paciente_id' => $paciente->id]);
        $this->assertTrue($paciente->fresh()->turnos->contains($turno));
    }

    public function test_profesional_relaciones(): void
    {
        $profesional = Profesional::factory()->create();
        $this->assertInstanceOf(User::class, $profesional->user);

        $servicio = Servicio::factory()->create();
        $profesional->servicios()->attach($servicio->id);
        $this->assertTrue($profesional->fresh()->servicios->contains($servicio));

        $consultorio = Consultorio::factory()->create();
        $disponibilidad = Disponibilidad::factory()->create([
            'profesional_id' => $profesional->id,
            'consultorio_id' => $consultorio->id,
        ]);
        $this->assertTrue($profesional->fresh()->disponibilidades->contains($disponibilidad));

        $bloqueo = Bloqueo::factory()->create([
            'profesional_id' => $profesional->id,
            'consultorio_id' => $consultorio->id,
        ]);
        $this->assertTrue($profesional->fresh()->bloqueos->contains($bloqueo));

        $turno = Turno::factory()->create([
            'profesional_id' => $profesional->id,
            'servicio_id' => $servicio->id,
            'consultorio_id' => $consultorio->id,
        ]);
        $this->assertTrue($profesional->fresh()->turnos->contains($turno));
    }

    public function test_especialidad_relaciones_y_casts(): void
    {
        $especialidad = Especialidad::factory()->create(['activa' => true]);
        $this->assertIsBool($especialidad->activa);
        $this->assertTrue($especialidad->activa);

        $servicio = Servicio::factory()->create(['especialidad_id' => $especialidad->id]);
        $this->assertTrue($especialidad->fresh()->servicios->contains($servicio));
    }

    public function test_servicio_relaciones_y_casts(): void
    {
        $servicio = Servicio::factory()->create([
            'activo' => 1,
            'duracion_min' => '45',
            'precio' => '150000',
        ]);

        $this->assertIsBool($servicio->activo);
        $this->assertTrue($servicio->activo);
        $this->assertIsInt($servicio->duracion_min);
        $this->assertSame(45, $servicio->duracion_min);
        $this->assertIsInt($servicio->precio);
        $this->assertSame(150000, $servicio->precio);

        $this->assertInstanceOf(Especialidad::class, $servicio->especialidad);

        $profesional = Profesional::factory()->create();
        $servicio->profesionales()->attach($profesional->id);
        $this->assertTrue($servicio->fresh()->profesionales->contains($profesional));

        $turno = Turno::factory()->create([
            'profesional_id' => $profesional->id,
            'servicio_id' => $servicio->id,
        ]);
        $this->assertTrue($servicio->fresh()->turnos->contains($turno));
    }

    public function test_consultorio_relaciones_y_casts(): void
    {
        $consultorio = Consultorio::factory()->create(['activo' => 1]);
        $this->assertIsBool($consultorio->activo);
        $this->assertTrue($consultorio->activo);

        $disponibilidad = Disponibilidad::factory()->create(['consultorio_id' => $consultorio->id]);
        $this->assertTrue($consultorio->fresh()->disponibilidades->contains($disponibilidad));

        $bloqueo = Bloqueo::factory()->create(['consultorio_id' => $consultorio->id]);
        $this->assertTrue($consultorio->fresh()->bloqueos->contains($bloqueo));

        $turno = Turno::factory()->create(['consultorio_id' => $consultorio->id]);
        $this->assertTrue($consultorio->fresh()->turnos->contains($turno));
    }

    public function test_disponibilidad_relaciones_y_casts(): void
    {
        $disponibilidad = Disponibilidad::factory()->create([
            'dia_semana' => '3',
        ]);

        $this->assertIsInt($disponibilidad->dia_semana);
        $this->assertSame(3, $disponibilidad->dia_semana);
        $this->assertInstanceOf(Profesional::class, $disponibilidad->profesional);
        $this->assertInstanceOf(Consultorio::class, $disponibilidad->consultorio);
    }

    public function test_bloqueo_relaciones_y_casts(): void
    {
        $bloqueo = Bloqueo::factory()->create([
            'consultorio_id' => null,
        ]);

        $this->assertInstanceOf(\DateTimeInterface::class, $bloqueo->inicio);
        $this->assertInstanceOf(\DateTimeInterface::class, $bloqueo->fin);
        $this->assertInstanceOf(Profesional::class, $bloqueo->profesional);
        $this->assertNull($bloqueo->consultorio);

        $consultorio = Consultorio::factory()->create();
        $bloqueoConConsultorio = Bloqueo::factory()->create([
            'consultorio_id' => $consultorio->id,
        ]);
        $this->assertInstanceOf(Consultorio::class, $bloqueoConConsultorio->consultorio);
    }

    public function test_turno_relaciones_y_casts(): void
    {
        $turno = Turno::factory()->create();

        $this->assertInstanceOf(EstadoTurno::class, $turno->estado);
        $this->assertSame(EstadoTurno::Solicitado, $turno->estado);
        $this->assertInstanceOf(\DateTimeInterface::class, $turno->inicio);
        $this->assertInstanceOf(\DateTimeInterface::class, $turno->fin);

        $this->assertInstanceOf(Paciente::class, $turno->paciente);
        $this->assertInstanceOf(Profesional::class, $turno->profesional);
        $this->assertInstanceOf(Servicio::class, $turno->servicio);
        $this->assertInstanceOf(Consultorio::class, $turno->consultorio);

        $historial = HistorialTurno::create([
            'turno_id' => $turno->id,
            'estado_anterior' => null,
            'estado_nuevo' => EstadoTurno::Solicitado,
            'usuario_id' => null,
        ]);
        $this->assertTrue($turno->fresh()->historial->contains($historial));

        $notificacion = Notificacion::factory()->create([
            'turno_id' => $turno->id,
        ]);
        $this->assertTrue($turno->fresh()->notificaciones->contains($notificacion));
    }

    public function test_historial_turno_crea_sin_usuario_y_casts(): void
    {
        $turno = Turno::factory()->create();

        $historial = HistorialTurno::create([
            'turno_id' => $turno->id,
            'estado_anterior' => 'solicitado',
            'estado_nuevo' => 'confirmado',
            'usuario_id' => null,
        ]);

        $this->assertDatabaseHas('historial_turnos', [
            'id' => $historial->id,
            'usuario_id' => null,
            'estado_anterior' => 'solicitado',
            'estado_nuevo' => 'confirmado',
        ]);

        $this->assertNull($historial->usuario);
        $this->assertInstanceOf(Turno::class, $historial->turno);
        $this->assertInstanceOf(EstadoTurno::class, $historial->estado_anterior);
        $this->assertSame(EstadoTurno::Solicitado, $historial->estado_anterior);
        $this->assertInstanceOf(EstadoTurno::class, $historial->estado_nuevo);
        $this->assertSame(EstadoTurno::Confirmado, $historial->estado_nuevo);
    }

    public function test_notificacion_relaciones_y_casts(): void
    {
        $user = User::factory()->paciente()->create();
        $notificacion = Notificacion::factory()->create([
            'destinatario_id' => $user->id,
            'turno_id' => null,
            'estado' => 'pendiente',
        ]);

        $this->assertInstanceOf(EstadoNotificacion::class, $notificacion->estado);
        $this->assertSame(EstadoNotificacion::Pendiente, $notificacion->estado);
        $this->assertInstanceOf(User::class, $notificacion->destinatario);
        $this->assertSame($user->id, $notificacion->destinatario->id);
        $this->assertNull($notificacion->turno);
    }

    public function test_turno_factory_respeta_fk_compuesta_y_exclusion_constraint(): void
    {
        $turno = Turno::factory()->create();

        $this->assertDatabaseHas('profesional_servicio', [
            'profesional_id' => $turno->profesional_id,
            'servicio_id' => $turno->servicio_id,
        ]);

        $this->assertDatabaseHas('turnos', [
            'id' => $turno->id,
            'profesional_id' => $turno->profesional_id,
            'servicio_id' => $turno->servicio_id,
        ]);
    }
}
