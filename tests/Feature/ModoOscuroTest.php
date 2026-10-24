<?php

namespace Tests\Feature;

use App\Models\Consultorio;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class ModoOscuroTest extends TestCase
{
    use RefreshDatabase;

    private User $pacienteUser;

    private User $funcionarioUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->funcionarioUser = User::factory()->funcionario()->create();

        $this->pacienteUser = User::factory()->paciente()->create();
        Paciente::factory()->create([
            'user_id' => $this->pacienteUser->id,
            'nombre' => 'Ana',
            'apellido' => 'Pérez',
        ]);

        $profUser = User::factory()->profesional()->create();
        $prof = Profesional::factory()->create([
            'user_id' => $profUser->id,
            'nombre' => 'María',
            'apellido' => 'López',
        ]);
        $consultorio = Consultorio::factory()->create(['activo' => true]);
        $especialidad = Especialidad::factory()->create(['activa' => true]);
        $servicio = Servicio::factory()->create([
            'especialidad_id' => $especialidad->id,
            'nombre' => 'Consulta',
            'activo' => true,
        ]);
        $prof->servicios()->attach($servicio->id);
    }

    public function test_los_3_layouts_incluyen_script_de_tema_en_el_head(): void
    {
        // 1. Layout invitado
        $responseInvitado = $this->get('/login');
        $responseInvitado->assertOk();
        $this->assertHeadContieneScriptTema($responseInvitado->getContent(), 'invitado');

        // 2. Layout paciente
        $responsePaciente = $this->actingAs($this->pacienteUser)->get('/inicio');
        $responsePaciente->assertOk();
        $this->assertHeadContieneScriptTema($responsePaciente->getContent(), 'paciente');

        // 3. Layout panel
        $responsePanel = $this->actingAs($this->funcionarioUser)->get('/panel');
        $responsePanel->assertOk();
        $this->assertHeadContieneScriptTema($responsePanel->getContent(), 'panel');
    }

    public function test_los_3_layouts_incluyen_boton_de_tema_con_aria_label(): void
    {
        // 1. Layout invitado (botón con aria-label)
        $responseInvitado = $this->get('/login');
        $responseInvitado->assertOk();
        $this->assertMatchesRegularExpression(
            '/<button[^>]*aria-label=["\'][^"\']*tema[^"\']*["\'][^>]*>/i',
            $responseInvitado->getContent(),
            'El layout invitado no incluye un botón de tema con aria-label.'
        );

        // 2. Layout paciente (botón cuadrado con aria-label)
        $responsePaciente = $this->actingAs($this->pacienteUser)->get('/inicio');
        $responsePaciente->assertOk();
        $this->assertMatchesRegularExpression(
            '/<button[^>]*aria-label=["\'][^"\']*tema[^"\']*["\'][^>]*>/i',
            $responsePaciente->getContent(),
            'El layout paciente no incluye un botón de tema con aria-label.'
        );

        // 3. Layout panel (botón con aria-label y textos Oscuro / Claro)
        $responsePanel = $this->actingAs($this->funcionarioUser)->get('/panel');
        $responsePanel->assertOk();
        $this->assertMatchesRegularExpression(
            '/<button[^>]*aria-label=["\'][^"\']*tema[^"\']*["\'][^>]*>/i',
            $responsePanel->getContent(),
            'El layout panel no incluye un botón de tema con aria-label.'
        );
        $responsePanel->assertSee('Oscuro');
        $responsePanel->assertSee('Claro');
    }

    public function test_ninguna_vista_bajo_resources_views_contiene_bg_white(): void
    {
        $viewsPath = resource_path('views');
        $archivosConBgWhite = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($viewsPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $path = $file->getPathname();

                // Excluir correos (resources/views/mail) y vendor
                if (str_contains($path, '/views/mail/') || str_contains($path, '/views/vendor/')) {
                    continue;
                }

                $content = file_get_contents($path);

                // Buscar clases que usen bg-white (incluyendo opacidades como bg-white/80)
                if (preg_match('/\bbg-white(\/[0-9]+)?\b/', $content)) {
                    $archivosConBgWhite[] = str_replace(base_path().'/', '', $path);
                }
            }
        }

        $this->assertEmpty(
            $archivosConBgWhite,
            "Se encontraron vistas con 'bg-white' que deben usar 'bg-card':\n".implode("\n", $archivosConBgWhite)
        );
    }

    private function assertHeadContieneScriptTema(string $html, string $layout): void
    {
        // Extraer contenido de <head>
        preg_match('/<head[^>]*>(.*?)<\/head>/is', $html, $matches);
        $this->assertNotEmpty($matches, "No se encontró etiqueta <head> en el layout {$layout}.");

        $head = $matches[1];

        $this->assertStringContainsString(
            'localStorage.getItem(\'tema\')',
            $head,
            "El layout {$layout} no contiene el script inline de tema en el <head>."
        );

        $this->assertStringContainsString(
            'prefers-color-scheme: dark',
            $head,
            "El script de tema en el layout {$layout} no comprueba prefers-color-scheme."
        );
    }
}
