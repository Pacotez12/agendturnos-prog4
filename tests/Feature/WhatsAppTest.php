<?php

namespace Tests\Feature;

use App\Enums\EstadoNotificacion;
use App\Enums\EstadoTurno;
use App\Jobs\EnviarNotificacion;
use App\Models\Consultorio;
use App\Models\Especialidad;
use App\Models\Notificacion;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\Turno;
use App\Models\User;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WhatsAppTest extends TestCase
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
            'telefono' => '0981 123 456',
        ]);

        $this->consultorio = Consultorio::factory()->create(['activo' => true]);

        $especialidad = Especialidad::factory()->create(['activa' => true]);
        $this->servicio = Servicio::factory()->create([
            'especialidad_id' => $especialidad->id,
            'nombre' => 'Limpieza dental',
            'duracion_min' => 30,
            'precio' => 150000,
            'activo' => true,
        ]);

        $this->profesional->servicios()->attach($this->servicio->id);
    }

    private function crearTurno(string $inicio, string $fin, EstadoTurno $estado = EstadoTurno::Solicitado, ?Paciente $paciente = null, ?string $motivoCancelacion = null): Turno
    {
        return Turno::create([
            'paciente_id' => $paciente ? $paciente->id : $this->paciente->id,
            'profesional_id' => $this->profesional->id,
            'servicio_id' => $this->servicio->id,
            'consultorio_id' => $this->consultorio->id,
            'inicio' => Carbon::parse($inicio, config('app.timezone')),
            'fin' => Carbon::parse($fin, config('app.timezone')),
            'estado' => $estado,
            'motivo_cancelacion' => $motivoCancelacion,
        ]);
    }

    private function configurarCredencialesWhatsApp(): void
    {
        config()->set([
            'services.whatsapp.token' => 'meta-secret-token',
            'services.whatsapp.phone_id' => '10987654321',
            'services.whatsapp.plantillas' => [
                'confirmacion' => 'turno_confirmado',
                'cancelacion' => 'turno_cancelado',
                'reprogramacion' => 'turno_reprogramado',
                'recordatorio' => 'turno_recordatorio',
            ],
        ]);
    }

    public function test_normalizar_telefono_formatos_paraguay(): void
    {
        $service = app(WhatsAppService::class);

        $this->assertSame('595981123456', $service->normalizarTelefono('0981 123 456'));
        $this->assertSame('595981123456', $service->normalizarTelefono('+595 981 123456'));
        $this->assertSame('595981123456', $service->normalizarTelefono('981123456'));
        $this->assertSame('595981123456', $service->normalizarTelefono('595981123456'));
        $this->assertNull($service->normalizarTelefono(null));
        $this->assertNull($service->normalizarTelefono(''));
        $this->assertNull($service->normalizarTelefono('   '));
        $this->assertNull($service->normalizarTelefono('abc-+-'));
    }

    public function test_sin_token_o_phone_id_no_hace_request_http(): void
    {
        Http::preventStrayRequests();

        config()->set([
            'services.whatsapp.token' => null,
            'services.whatsapp.phone_id' => null,
        ]);

        $turno = $this->crearTurno('2026-10-23 09:00:00', '2026-10-23 09:30:00', EstadoTurno::Confirmado);
        $notificacion = Notificacion::create([
            'turno_id' => $turno->id,
            'destinatario_id' => $this->pacienteUser->id,
            'tipo' => 'confirmacion',
            'estado' => EstadoNotificacion::Pendiente,
        ]);

        app(WhatsAppService::class)->enviarAviso($notificacion);

        Http::assertNothingSent();
    }

    public function test_paciente_sin_telefono_no_hace_request_http(): void
    {
        Http::preventStrayRequests();
        $this->configurarCredencialesWhatsApp();

        $this->paciente->update(['telefono' => null]);

        $turno = $this->crearTurno('2026-10-23 09:00:00', '2026-10-23 09:30:00', EstadoTurno::Confirmado);
        $notificacion = Notificacion::create([
            'turno_id' => $turno->id,
            'destinatario_id' => $this->pacienteUser->id,
            'tipo' => 'confirmacion',
            'estado' => EstadoNotificacion::Pendiente,
        ]);

        app(WhatsAppService::class)->enviarAviso($notificacion);

        Http::assertNothingSent();
    }

    public function test_tipo_sin_plantilla_configurada_no_hace_request_http(): void
    {
        Http::preventStrayRequests();
        $this->configurarCredencialesWhatsApp();
        config()->set(['services.whatsapp.plantillas' => []]);

        $turno = $this->crearTurno('2026-10-23 09:00:00', '2026-10-23 09:30:00', EstadoTurno::Confirmado);
        $notificacion = Notificacion::create([
            'turno_id' => $turno->id,
            'destinatario_id' => $this->pacienteUser->id,
            'tipo' => 'confirmacion',
            'estado' => EstadoNotificacion::Pendiente,
        ]);

        app(WhatsAppService::class)->enviarAviso($notificacion);

        Http::assertNothingSent();
    }

    public function test_envio_confirmacion_usa_plantilla_turno_confirmado_y_parametros_en_orden(): void
    {
        Http::preventStrayRequests();
        $this->configurarCredencialesWhatsApp();

        Http::fake([
            'https://graph.facebook.com/v25.0/10987654321/messages' => Http::response(['messages' => [['id' => 'wamid.123']]], 200),
        ]);

        $turno = $this->crearTurno('2026-10-23 09:00:00', '2026-10-23 09:30:00', EstadoTurno::Confirmado);
        $notificacion = Notificacion::create([
            'turno_id' => $turno->id,
            'destinatario_id' => $this->pacienteUser->id,
            'tipo' => 'confirmacion',
            'estado' => EstadoNotificacion::Pendiente,
        ]);

        app(WhatsAppService::class)->enviarAviso($notificacion);

        Http::assertSent(function (Request $request) use ($turno) {
            $data = $request->data();

            return $request->url() === 'https://graph.facebook.com/v25.0/10987654321/messages'
                && $request->hasHeader('Authorization', 'Bearer meta-secret-token')
                && $data['messaging_product'] === 'whatsapp'
                && $data['to'] === '595981123456'
                && $data['type'] === 'template'
                && $data['template']['name'] === 'turno_confirmado'
                && $data['template']['language']['code'] === 'es'
                && $data['template']['components'][0]['parameters'][0]['text'] === 'Carlos Gómez'
                && $data['template']['components'][0]['parameters'][1]['text'] === 'Carlos Gómez'
                && $data['template']['components'][0]['parameters'][2]['text'] === ucfirst($turno->inicio->translatedFormat('l j \d\e F, H:i'))
                && $data['template']['components'][0]['parameters'][3]['text'] === 'Ana González'
                && $data['template']['components'][0]['parameters'][4]['text'] === 'Limpieza dental';
        });
    }

    public function test_envio_cancelacion_usa_plantilla_turno_cancelado(): void
    {
        Http::preventStrayRequests();
        $this->configurarCredencialesWhatsApp();

        Http::fake([
            'https://graph.facebook.com/v25.0/10987654321/messages' => Http::response(['messages' => [['id' => 'wamid.123']]], 200),
        ]);

        $turno = $this->crearTurno('2026-10-23 09:00:00', '2026-10-23 09:30:00', EstadoTurno::Cancelado, null, 'Urgencia médica');
        $notif = Notificacion::create([
            'turno_id' => $turno->id,
            'destinatario_id' => $this->pacienteUser->id,
            'tipo' => 'cancelacion',
            'estado' => EstadoNotificacion::Pendiente,
        ]);

        app(WhatsAppService::class)->enviarAviso($notif);

        Http::assertSent(function (Request $request) use ($turno) {
            $data = $request->data();

            return $data['template']['name'] === 'turno_cancelado'
                && $data['template']['components'][0]['parameters'][0]['text'] === 'Carlos Gómez'
                && $data['template']['components'][0]['parameters'][1]['text'] === 'Carlos Gómez'
                && $data['template']['components'][0]['parameters'][2]['text'] === ucfirst($turno->inicio->translatedFormat('l j \d\e F, H:i'))
                && $data['template']['components'][0]['parameters'][3]['text'] === 'Ana González'
                && $data['template']['components'][0]['parameters'][4]['text'] === 'Limpieza dental';
        });
    }

    public function test_envio_reprogramacion_usa_plantilla_turno_reprogramado(): void
    {
        Http::preventStrayRequests();
        $this->configurarCredencialesWhatsApp();

        Http::fake([
            'https://graph.facebook.com/v25.0/10987654321/messages' => Http::response(['messages' => [['id' => 'wamid.123']]], 200),
        ]);

        $turno = $this->crearTurno('2026-10-23 11:00:00', '2026-10-23 11:30:00', EstadoTurno::Confirmado);
        $notificacion = Notificacion::create([
            'turno_id' => $turno->id,
            'destinatario_id' => $this->pacienteUser->id,
            'tipo' => 'reprogramacion',
            'estado' => EstadoNotificacion::Pendiente,
        ]);

        app(WhatsAppService::class)->enviarAviso($notificacion);

        Http::assertSent(function (Request $request) use ($turno) {
            $data = $request->data();

            return $data['template']['name'] === 'turno_reprogramado'
                && $data['template']['components'][0]['parameters'][0]['text'] === 'Carlos Gómez'
                && $data['template']['components'][0]['parameters'][1]['text'] === 'Carlos Gómez'
                && $data['template']['components'][0]['parameters'][2]['text'] === ucfirst($turno->inicio->translatedFormat('l j \d\e F, H:i'))
                && $data['template']['components'][0]['parameters'][3]['text'] === 'Ana González'
                && $data['template']['components'][0]['parameters'][4]['text'] === 'Limpieza dental';
        });
    }

    public function test_envio_recordatorio_usa_plantilla_turno_recordatorio(): void
    {
        Http::preventStrayRequests();
        $this->configurarCredencialesWhatsApp();

        Http::fake([
            'https://graph.facebook.com/v25.0/10987654321/messages' => Http::response(['messages' => [['id' => 'wamid.123']]], 200),
        ]);

        $turno = $this->crearTurno('2026-10-19 10:00:00', '2026-10-19 10:30:00', EstadoTurno::Confirmado);
        $notificacion = Notificacion::create([
            'turno_id' => $turno->id,
            'destinatario_id' => $this->pacienteUser->id,
            'tipo' => 'recordatorio',
            'estado' => EstadoNotificacion::Pendiente,
        ]);

        app(WhatsAppService::class)->enviarAviso($notificacion);

        Http::assertSent(function (Request $request) use ($turno) {
            $data = $request->data();

            return $data['template']['name'] === 'turno_recordatorio'
                && $data['template']['components'][0]['parameters'][0]['text'] === 'Carlos Gómez'
                && $data['template']['components'][0]['parameters'][1]['text'] === 'Carlos Gómez'
                && $data['template']['components'][0]['parameters'][2]['text'] === ucfirst($turno->inicio->translatedFormat('l j \d\e F, H:i'))
                && $data['template']['components'][0]['parameters'][3]['text'] === 'Ana González'
                && $data['template']['components'][0]['parameters'][4]['text'] === 'Limpieza dental';
        });
    }

    public function test_turno_familiar_envia_al_responsable_con_parametro_2_nombre_del_familiar(): void
    {
        Http::preventStrayRequests();
        $this->configurarCredencialesWhatsApp();

        Http::fake([
            'https://graph.facebook.com/v25.0/10987654321/messages' => Http::response(['messages' => [['id' => 'wamid.123']]], 200),
        ]);

        $familiar = Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => $this->pacienteUser->id,
            'nombre' => 'Sofía',
            'apellido' => 'Gómez',
            'telefono' => '0999 999 999', // El dependiente no tiene cuenta, el mensaje debe ir al responsable
        ]);

        $turno = $this->crearTurno('2026-10-23 09:00:00', '2026-10-23 09:30:00', EstadoTurno::Confirmado, $familiar);
        $notificacion = Notificacion::create([
            'turno_id' => $turno->id,
            'destinatario_id' => $this->pacienteUser->id,
            'tipo' => 'confirmacion',
            'estado' => EstadoNotificacion::Pendiente,
        ]);

        app(WhatsAppService::class)->enviarAviso($notificacion);

        Http::assertSent(function (Request $request) {
            $data = $request->data();
            $params = $data['template']['components'][0]['parameters'];

            return $data['to'] === '595981123456'
                && $data['template']['name'] === 'turno_confirmado'
                && $params[0]['text'] === 'Carlos Gómez'
                && $params[1]['text'] === 'Sofía Gómez'
                && $params[3]['text'] === 'Ana González'
                && $params[4]['text'] === 'Limpieza dental';
        });
    }

    public function test_job_enviar_notificacion_dispara_whatsapp_y_si_falla_guarda_warning_sin_fallar_el_job(): void
    {
        Http::preventStrayRequests();
        $this->configurarCredencialesWhatsApp();

        // Meta responde 500 error
        Http::fake([
            'https://graph.facebook.com/v25.0/10987654321/messages' => Http::response(['error' => ['message' => 'Internal Meta Error']], 500),
        ]);

        Log::spy();

        $turno = $this->crearTurno('2026-10-23 09:00:00', '2026-10-23 09:30:00', EstadoTurno::Confirmado);
        $notificacion = Notificacion::create([
            'turno_id' => $turno->id,
            'destinatario_id' => $this->pacienteUser->id,
            'tipo' => 'confirmacion',
            'estado' => EstadoNotificacion::Pendiente,
        ]);

        $job = new EnviarNotificacion($notificacion);
        app()->call([$job, 'handle']);

        $notificacion->refresh();
        $this->assertSame(EstadoNotificacion::Enviada, $notificacion->estado);
        $this->assertNotNull($notificacion->enviado_at);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('WhatsApp no enviado', \Mockery::on(function (array $context) use ($notificacion) {
                return isset($context['notificacion_id'], $context['mensaje'])
                    && $context['notificacion_id'] === $notificacion->id;
            }));
    }
}
