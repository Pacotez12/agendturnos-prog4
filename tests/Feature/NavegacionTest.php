<?php

namespace Tests\Feature;

use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Profesional;
use App\Models\Servicio;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavegacionTest extends TestCase
{
    use RefreshDatabase;

    private function extraerEnlacesInternos(string $html): array
    {
        $dom = new DOMDocument;
        @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);
        $nodos = $xpath->query('//a[@href]');

        $enlaces = [];
        foreach ($nodos as $nodo) {
            $href = $nodo->getAttribute('href');

            // Ignorar enlaces vacíos, anclas, esquemas especiales o externos
            if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'javascript:') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }

            // Normalizar a ruta relativa si coincide con el host local
            $parsed = parse_url($href);
            if (isset($parsed['host'])) {
                if ($parsed['host'] !== 'localhost' && $parsed['host'] !== '127.0.0.1') {
                    continue; // externo (p.ej. wa.me)
                }
                $href = ($parsed['path'] ?? '/').(isset($parsed['query']) ? '?'.$parsed['query'] : '');
            }

            if (! str_starts_with($href, '/')) {
                continue;
            }

            // Excluir logout
            if (str_contains($href, 'logout')) {
                continue;
            }

            $enlaces[] = $href;
        }

        return array_unique($enlaces);
    }

    private function verificarRutasYEnlaces(?User $usuario, array $rutasConNombre): void
    {
        $enlacesVisitados = [];

        foreach ($rutasConNombre as $nombreRuta) {
            $url = route($nombreRuta);
            $cliente = $usuario ? $this->actingAs($usuario) : $this;
            $respuesta = $cliente->get($url);

            $this->assertContains(
                $respuesta->status(),
                [200, 302],
                "La ruta {$nombreRuta} ({$url}) respondió con status inesperado {$respuesta->status()}"
            );

            // Si es 200, analizar enlaces internos
            if ($respuesta->status() === 200) {
                $enlaces = $this->extraerEnlacesInternos($respuesta->getContent());
                foreach ($enlaces as $enlace) {
                    if (isset($enlacesVisitados[$enlace])) {
                        continue;
                    }
                    $enlacesVisitados[$enlace] = true;

                    $respEnlace = $cliente->get($enlace);
                    $this->assertNotEquals(
                        404,
                        $respEnlace->status(),
                        "El enlace interno {$enlace} en la página {$nombreRuta} devolvió 404"
                    );
                    $this->assertNotEquals(
                        500,
                        $respEnlace->status(),
                        "El enlace interno {$enlace} en la página {$nombreRuta} devolvió 500"
                    );
                }
            }
        }
    }

    public function test_navegacion_invitado(): void
    {
        $rutas = [
            'inicio',
            'servicios.catalogo',
            'privacidad',
            'login',
            'registro',
            'password.request',
        ];

        $this->verificarRutasYEnlaces(null, $rutas);
    }

    public function test_navegacion_paciente(): void
    {
        $paciente = User::factory()->paciente()->create();

        $rutas = [
            'inicio',
            'paciente.inicio',
            'servicios.catalogo',
            'paciente.reservar',
            'paciente.turnos',
            'paciente.familia',
            'privacidad',
        ];

        $this->verificarRutasYEnlaces($paciente, $rutas);
    }

    public function test_navegacion_tutor(): void
    {
        $tutor = User::factory()->paciente()->create();
        Paciente::factory()->create([
            'user_id' => null,
            'responsable_id' => $tutor->id,
            'nombre' => 'Hijo Menor',
        ]);

        $rutas = [
            'inicio',
            'paciente.inicio',
            'servicios.catalogo',
            'paciente.reservar',
            'paciente.turnos',
            'paciente.familia',
            'privacidad',
        ];

        $this->verificarRutasYEnlaces($tutor, $rutas);
    }

    public function test_navegacion_funcionario(): void
    {
        $funcionario = User::factory()->funcionario()->create();
        $profesional = Profesional::factory()->create();
        $especialidad = Especialidad::factory()->create();
        $servicio = Servicio::factory()->create(['especialidad_id' => $especialidad->id]);
        $profesional->servicios()->attach($servicio);

        $rutas = [
            'panel.inicio',
            'panel.agenda',
            'panel.solicitudes',
            'panel.turnos.crear',
            'panel.pacientes',
            'panel.disponibilidad',
            'panel.servicios',
            'panel.servicios.create',
            'panel.especialidades',
            'panel.especialidades.create',
            'panel.profesionales',
            'privacidad',
        ];

        $this->verificarRutasYEnlaces($funcionario, $rutas);
    }

    public function test_navegacion_profesional(): void
    {
        $profesional = Profesional::factory()->create();
        $usuario = $profesional->user;

        $rutas = [
            'panel.inicio',
            'panel.agenda',
            'panel.solicitudes',
            'panel.turnos.crear',
            'panel.pacientes',
            'panel.disponibilidad',
            'privacidad',
        ];

        $this->verificarRutasYEnlaces($usuario, $rutas);
    }
}
