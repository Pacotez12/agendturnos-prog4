<?php

namespace Tests\Feature;

use App\Enums\EstadoNotificacion;
use App\Enums\EstadoTurno;
use App\Jobs\EnviarNotificacion;
use App\Models\Consultorio;
use App\Models\Disponibilidad;
use App\Models\Especialidad;
use App\Models\Notificacion;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use App\Notifications\AvisoTurno;
use App\Services\AgendaService;
use App\Services\ReservaService;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificacionesTest extends TestCase
{
    use RefreshDatabase;

    private User $funcionario;

    private User $profesionalUser;

    private Profesional $profesional;

    private User $pacienteUser;

    private Paciente $paciente;

    private Consultorio $consultorio;

    private Servicio $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-18 12:00:00', config('app.timezone')));

        $this->funcionario = User::factory()->funcionario()->create(['email' => 'admin@glowdental.test']);

        $this->profesionalUser = User::factory()->profesional()->create(['email' => 'dra.ana@glowdental.test']);
        $this->profesional = Profesional::factory()->create([
            'user_id' => $this->profesionalUser->id,
            'nombre' => 'Ana',
            'apellido' => 'González',
        ]);

        $this->pacienteUser = User::factory()->paciente()->create(['email' => 'carlos@paciente.test']);
        $this->paciente = Paciente::factory()->create([
            'user_id' => $this->pacienteUser->id,
            'nombre' => 'Carlos',
            'apellido' => 'Gómez',
        ]);

        $this->consultorio = Consultorio::factory()->create(['activo' => true]);

        $especialidad = Especialidad::factory()->create(['activa' => true]);
        $this->servicio = Servicio::factory()->create([
            'especialidad_id' => $especialidad->id,
            'nombre' => 'Consulta general',
            'duracion_min' => 30,
            'activo' => true,
        ]);

        $this->profesional->servicios()->attach($this->servicio->id);

        // Disponibilidad: Lunes (1) y Viernes (5) de 08:00 a 12:00
        Disponibilidad::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
        ]);
        Disponibilidad::factory()->create([
            'profesional_id' => $this->profesional->id,
            'consultorio_id' => $this->consultorio->id,
            'dia_semana' => 5,
            'hora_inicio' => '08:00',
            'hora_fin' => '12:00',
        ]);
    }

    private function crearTurno(string $inicio = '2026-10-23 09:00:00', string $fin = '2026-10-23 09:30:00', EstadoTurno $estado = EstadoTurno::Solicitado, ?Paciente $paciente = null): Turno
    {
        return Turno::create([
            'paciente_id' => ($paciente ?? $this->paciente)->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => $inicio,
            'fin' => $fin,
            'estado' => $estado,
        ]);
    }

    public function test_confirmar_crea_fila_pendiente_y_despacha_job(): void
    {
        Queue::fake();

        $turno = $this->crearTurno();

        app(AgendaService::class)->confirmar($turno, $this->profesionalUser);

        $this->assertDatabaseHas('notificaciones', [
            'turno_id' => $turno->id,
            'tipo' => 'confirmacion',
            'destinatario_id' => $this->pacienteUser->id,
            'estado' => EstadoNotificacion::Pendiente->value,
            'enviado_at' => null,
        ]);

        Queue::assertPushed(EnviarNotificacion::class, function ($job) use ($turno) {
            return $job->notificacion->turno_id === $turno->id
                && $job->notificacion->tipo === 'confirmacion';
        });
    }

    public function test_job_envia_aviso_turno_y_marca_enviada(): void
    {
        Notification::fake();

        $turno = $this->crearTurno('2026-10-23 09:00:00', '2026-10-23 09:30:00', EstadoTurno::Confirmado);

        $notificacion = Notificacion::create([
            'turno_id' => $turno->id,
            'tipo' => 'confirmacion',
            'destinatario_id' => $this->pacienteUser->id,
            'canal' => 'correo',
            'estado' => EstadoNotificacion::Pendiente,
        ]);

        $job = new EnviarNotificacion($notificacion);
        $job->handle();

        Notification::assertSentTo(
            $this->pacienteUser,
            AvisoTurno::class,
            function ($n) use ($notificacion) {
                return $n->notificacion->id === $notificacion->id;
            }
        );

        $notificacion->refresh();
        $this->assertSame(EstadoNotificacion::Enviada, $notificacion->estado);
        $this->assertNotNull($notificacion->enviado_at);
    }

    public function test_job_si_ya_esta_enviada_no_hace_nada(): void
    {
        Notification::fake();

        $turno = $this->crearTurno();
        $notificacion = Notificacion::create([
            'turno_id' => $turno->id,
            'tipo' => 'confirmacion',
            'destinatario_id' => $this->pacienteUser->id,
            'canal' => 'correo',
            'estado' => EstadoNotificacion::Enviada,
            'enviado_at' => now(),
        ]);

        $job = new EnviarNotificacion($notificacion);
        $job->handle();

        Notification::assertNothingSent();
    }

    public function test_job_falla_marca_fallida(): void
    {
        $turno = $this->crearTurno();
        $notificacion = Notificacion::create([
            'turno_id' => $turno->id,
            'tipo' => 'confirmacion',
            'destinatario_id' => $this->pacienteUser->id,
            'canal' => 'correo',
            'estado' => EstadoNotificacion::Pendiente,
        ]);

        $job = new EnviarNotificacion($notificacion);
        $job->failed(new Exception('SMTP error'));

        $notificacion->refresh();
        $this->assertSame(EstadoNotificacion::Fallida, $notificacion->estado);
    }

    public function test_cancelar_con_motivo_notifica_cancelacion(): void
    {
        Queue::fake();

        $turno = $this->crearTurno('2026-10-23 09:00:00', '2026-10-23 09:30:00', EstadoTurno::Confirmado);

        app(AgendaService::class)->cancelar($turno, 'La doctora no atiende ese horario', $this->funcionario);

        $this->assertDatabaseHas('notificaciones', [
            'turno_id' => $turno->id,
            'tipo' => 'cancelacion',
            'destinatario_id' => $this->pacienteUser->id,
            'estado' => EstadoNotificacion::Pendiente->value,
        ]);

        Queue::assertPushed(EnviarNotificacion::class, function ($job) use ($turno) {
            return $job->notificacion->turno_id === $turno->id
                && $job->notificacion->tipo === 'cancelacion';
        });
    }

    public function test_reprogramar_dos_veces_reusa_fila_y_borra_el_recordatorio(): void
    {
        Queue::fake();

        $turno = $this->crearTurno('2026-10-23 09:00:00', '2026-10-23 09:30:00', EstadoTurno::Confirmado);

        // Simulamos un recordatorio existente para el turno viejo
        Notificacion::create([
            'turno_id' => $turno->id,
            'tipo' => 'recordatorio',
            'destinatario_id' => $this->pacienteUser->id,
            'canal' => 'correo',
            'estado' => EstadoNotificacion::Enviada,
            'enviado_at' => now(),
        ]);

        $agenda = app(AgendaService::class);

        // Primera reprogramación: al viernes 23 a las 10:00
        $agenda->reprogramar($turno, Carbon::parse('2026-10-23 10:00:00'), $this->profesionalUser);

        // Se borró el recordatorio anterior
        $this->assertDatabaseMissing('notificaciones', [
            'turno_id' => $turno->id,
            'tipo' => 'recordatorio',
        ]);

        $this->assertDatabaseHas('notificaciones', [
            'turno_id' => $turno->id,
            'tipo' => 'reprogramacion',
            'estado' => EstadoNotificacion::Pendiente->value,
        ]);

        // Simulamos que la notificación de reprogramación se envió
        $notifReprog = Notificacion::where('turno_id', $turno->id)->where('tipo', 'reprogramacion')->first();
        $notifReprog->update(['estado' => EstadoNotificacion::Enviada, 'enviado_at' => now()]);

        // Segunda reprogramación: al lunes 26 a las 10:00
        $agenda->reprogramar($turno, Carbon::parse('2026-10-26 10:00:00'), $this->profesionalUser);

        // Debe seguir habiendo UNA SOLA fila de reprogramación, vuelta a pendiente
        $this->assertSame(1, Notificacion::where('turno_id', $turno->id)->where('tipo', 'reprogramacion')->count());

        $notifReprog->refresh();
        $this->assertSame(EstadoNotificacion::Pendiente, $notifReprog->estado);
        $this->assertNull($notifReprog->enviado_at);
    }

    public function test_familiar_sin_cuenta_va_al_responsable_con_el_turno_de_nombre(): void
    {
        Queue::fake();

        // Paciente dependiente sin usuario
        $familiar = Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => $this->pacienteUser->id,
            'nombre' => 'Sofía',
            'apellido' => 'Gómez',
        ]);

        $turno = $this->crearTurno('2026-10-23 09:00:00', '2026-10-23 09:30:00', EstadoTurno::Solicitado, $familiar);

        app(AgendaService::class)->confirmar($turno, $this->profesionalUser);

        $this->assertDatabaseHas('notificaciones', [
            'turno_id' => $turno->id,
            'destinatario_id' => $this->pacienteUser->id,
            'tipo' => 'confirmacion',
        ]);

        $notificacion = Notificacion::where('turno_id', $turno->id)->first();
        $aviso = new AvisoTurno($notificacion);
        $mail = $aviso->toMail($this->pacienteUser);
        $rendered = $mail->render();

        $this->assertStringContainsString('Hola Carlos Gómez,', $rendered);
        $this->assertStringContainsString('el turno de Sofía', $rendered);
    }

    public function test_paciente_sin_cuenta_ni_responsable_no_crea_fila(): void
    {
        Queue::fake();

        $pacienteSinCuenta = Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => null,
            'nombre' => 'Anónimo',
            'apellido' => 'Pérez',
        ]);

        $turno = $this->crearTurno('2026-10-23 09:00:00', '2026-10-23 09:30:00', EstadoTurno::Solicitado, $pacienteSinCuenta);

        app(AgendaService::class)->confirmar($turno, $this->profesionalUser);

        $this->assertDatabaseMissing('notificaciones', [
            'turno_id' => $turno->id,
        ]);
        Queue::assertNothingPushed();
    }

    public function test_solicitar_no_notifica(): void
    {
        Queue::fake();

        $reservaService = app(ReservaService::class);
        $reservaService->solicitar(
            $this->profesional,
            $this->servicio,
            $this->paciente,
            Carbon::parse('2026-10-23 09:00:00'),
            $this->pacienteUser
        );

        $this->assertDatabaseCount('notificaciones', 0);
        Queue::assertNothingPushed();
    }

    public function test_comando_recordatorios_toma_solo_confirmados_entre_1h_y_24h_y_no_duplica(): void
    {
        Queue::fake();

        Carbon::setTestNow(Carbon::parse('2026-10-22 10:00:00', config('app.timezone')));

        // 1. Turno confirmado en +2h (hoy a las 12:00) -> DEBE notificar
        $t1 = $this->crearTurno('2026-10-22 12:00:00', '2026-10-22 12:30:00', EstadoTurno::Confirmado);

        // 2. Turno confirmado en +30m (hoy a las 10:30) -> NO (< 1h)
        $t2 = $this->crearTurno('2026-10-22 10:30:00', '2026-10-22 11:00:00', EstadoTurno::Confirmado);

        // 3. Turno confirmado en +30h (mañana a las 16:00) -> NO (> 24h)
        $t3 = $this->crearTurno('2026-10-23 16:00:00', '2026-10-23 16:30:00', EstadoTurno::Confirmado);

        // 4. Turno solicitado en +2.5h -> NO (no está confirmado)
        $t4 = $this->crearTurno('2026-10-22 12:30:00', '2026-10-22 13:00:00', EstadoTurno::Solicitado);

        // 5. Turno confirmado en +3h que YA tiene recordatorio -> NO duplica
        $t5 = $this->crearTurno('2026-10-22 13:00:00', '2026-10-22 13:30:00', EstadoTurno::Confirmado);
        Notificacion::create([
            'turno_id' => $t5->id,
            'tipo' => 'recordatorio',
            'destinatario_id' => $this->pacienteUser->id,
            'canal' => 'correo',
            'estado' => EstadoNotificacion::Enviada,
            'enviado_at' => now(),
        ]);

        $this->artisan('turnos:recordatorios')->assertSuccessful();

        // Solo t1 debe haber generado notificación
        $this->assertDatabaseHas('notificaciones', [
            'turno_id' => $t1->id,
            'tipo' => 'recordatorio',
            'estado' => EstadoNotificacion::Pendiente->value,
        ]);

        $this->assertDatabaseMissing('notificaciones', ['turno_id' => $t2->id]);
        $this->assertDatabaseMissing('notificaciones', ['turno_id' => $t3->id]);
        $this->assertDatabaseMissing('notificaciones', ['turno_id' => $t4->id]);

        $this->assertSame(1, Notificacion::where('turno_id', $t5->id)->where('tipo', 'recordatorio')->count());
    }

    public function test_recordatorio_de_un_turno_del_mismo_dia_dice_hoy(): void
    {
        $turno = $this->crearTurno('2026-10-18 15:00:00', '2026-10-18 15:30:00', EstadoTurno::Confirmado);
        $notificacion = Notificacion::create([
            'turno_id' => $turno->id,
            'tipo' => 'recordatorio',
            'destinatario_id' => $this->pacienteUser->id,
            'canal' => 'correo',
            'estado' => EstadoNotificacion::Pendiente,
        ]);

        $mail = (new AvisoTurno($notificacion))->toMail($this->pacienteUser);

        $this->assertSame('Recordatorio: hoy a las 15:00 en Glow Dental', $mail->subject);
        $this->assertStringContainsString('Te esperamos hoy', $mail->render());
    }

    public function test_render_correo_contiene_asunto_y_textos_segun_tipo(): void
    {
        // 1. Confirmación
        $turnoConf = $this->crearTurno('2026-10-23 09:00:00', '2026-10-23 09:30:00', EstadoTurno::Confirmado);
        $notifConf = Notificacion::create([
            'turno_id' => $turnoConf->id,
            'tipo' => 'confirmacion',
            'destinatario_id' => $this->pacienteUser->id,
            'canal' => 'correo',
            'estado' => EstadoNotificacion::Pendiente,
        ]);
        $avisoConf = new AvisoTurno($notifConf);
        $mailConf = $avisoConf->toMail($this->pacienteUser);

        $this->assertSame('Tu turno está confirmado · Vie 23 oct, 09:00', $mailConf->subject);
        $renderedConf = $mailConf->render();
        $this->assertStringContainsString('Tu turno está confirmado', $renderedConf);
        $this->assertStringContainsString('Hola Carlos Gómez,', $renderedConf);
        $this->assertStringContainsString('la clínica confirmó tu turno.', $renderedConf);
        $this->assertStringContainsString('Viernes 23 de octubre, 09:00', $renderedConf);
        $this->assertStringContainsString('Ana González', $renderedConf);
        $this->assertStringContainsString('Consulta general · 30 min', $renderedConf);
        $this->assertStringContainsString('Carlos Gómez', $renderedConf);
        $this->assertStringContainsString('Ver mi turno', $renderedConf);
        $this->assertStringContainsString('+595 982 774586', $renderedConf);

        // 2. Cancelación
        $turnoCanc = $this->crearTurno('2026-10-23 10:00:00', '2026-10-23 10:30:00', EstadoTurno::Cancelado);
        $turnoCanc->motivo_cancelacion = 'La doctora no atiende ese horario';
        $turnoCanc->save();
        $notifCanc = Notificacion::create([
            'turno_id' => $turnoCanc->id,
            'tipo' => 'cancelacion',
            'destinatario_id' => $this->pacienteUser->id,
            'canal' => 'correo',
            'estado' => EstadoNotificacion::Pendiente,
        ]);
        $avisoCanc = new AvisoTurno($notifCanc);
        $mailCanc = $avisoCanc->toMail($this->pacienteUser);

        $this->assertSame('Tu turno fue cancelado · Vie 23 oct, 10:00', $mailCanc->subject);
        $renderedCanc = $mailCanc->render();
        $this->assertStringContainsString('Tu turno fue cancelado', $renderedCanc);
        $this->assertStringContainsString('la clínica canceló tu turno del viernes 23 de octubre a las 10:00', $renderedCanc);
        $this->assertStringContainsString('La doctora no atiende ese horario', $renderedCanc);
        $this->assertStringContainsString('Reservar otro turno', $renderedCanc);

        // 3. Reprogramación
        $turnoReprog = $this->crearTurno('2026-10-26 10:00:00', '2026-10-26 10:30:00', EstadoTurno::Confirmado);
        $notifReprog = Notificacion::create([
            'turno_id' => $turnoReprog->id,
            'tipo' => 'reprogramacion',
            'destinatario_id' => $this->pacienteUser->id,
            'canal' => 'correo',
            'estado' => EstadoNotificacion::Pendiente,
        ]);
        $avisoReprog = new AvisoTurno($notifReprog, ['anterior' => '2026-10-23T09:00:00-03:00']);
        $mailReprog = $avisoReprog->toMail($this->pacienteUser);

        $this->assertSame('Tu turno cambió de horario · Lun 26 oct, 10:00', $mailReprog->subject);
        $renderedReprog = $mailReprog->render();
        $this->assertStringContainsString('Tu turno cambió de horario', $renderedReprog);
        $this->assertStringContainsString('la clínica movió tu turno.', $renderedReprog);
        $this->assertStringContainsString('Antes', $renderedReprog);
        $this->assertStringContainsString('<del', $renderedReprog);
        $this->assertStringNotContainsString('~~', $renderedReprog);
        $this->assertStringContainsString('Viernes 23 de octubre, 09:00', $renderedReprog);
        $this->assertStringContainsString('Ahora', $renderedReprog);
        $this->assertStringContainsString('Lunes 26 de octubre, 10:00', $renderedReprog);
        $this->assertStringContainsString('Ver mi turno', $renderedReprog);
        $this->assertStringContainsString('+595 982 774586', $renderedReprog);

        // 4. Recordatorio
        $prof2 = Profesional::factory()->create(['nombre' => 'Gloria', 'apellido' => 'Godoy']);
        $prof2->servicios()->attach($this->servicio->id);
        $turnoRec = Turno::create([
            'paciente_id' => $this->paciente->id,
            'profesional_id' => $prof2->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => '2026-10-23 09:00:00',
            'fin' => '2026-10-23 09:30:00',
            'estado' => EstadoTurno::Confirmado,
        ]);
        $notifRec = Notificacion::create([
            'turno_id' => $turnoRec->id,
            'tipo' => 'recordatorio',
            'destinatario_id' => $this->pacienteUser->id,
            'canal' => 'correo',
            'estado' => EstadoNotificacion::Pendiente,
        ]);
        $avisoRec = new AvisoTurno($notifRec);
        $mailRec = $avisoRec->toMail($this->pacienteUser);

        $this->assertSame('Recordatorio: mañana a las 09:00 en Glow Dental', $mailRec->subject);
        $renderedRec = $mailRec->render();
        $this->assertStringContainsString('Te esperamos mañana', $renderedRec);
        $this->assertStringContainsString('te recordamos tu turno.', $renderedRec);
        $this->assertStringContainsString('Viernes 23 de octubre, 09:00', $renderedRec);
        $this->assertStringContainsString('Ver mi turno', $renderedRec);
        $this->assertStringContainsString('+595 982 774586', $renderedRec);
    }
}
