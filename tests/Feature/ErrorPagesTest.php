<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_error_404_renderiza_sin_depender_de_manifest_de_vite(): void
    {
        $this->app->instance(\Illuminate\Foundation\Vite::class, new \Illuminate\Foundation\Vite);
        Vite::useManifestFilename('manifest-inexistente.json');

        $response = $this->get('/ruta-que-definitivamente-no-existe-404');

        $response->assertNotFound();
        $response->assertSee('Página no encontrada');
        $response->assertSee('No encontramos esa página.');
        $response->assertSee('/images/glow-logo.svg');
    }

    public function test_error_500_renderiza_sin_depender_de_manifest_de_vite(): void
    {
        $this->app->instance(\Illuminate\Foundation\Vite::class, new \Illuminate\Foundation\Vite);
        Vite::useManifestFilename('manifest-inexistente.json');

        $view = view('errors.500')->render();

        $this->assertStringContainsString('Algo salió mal', $view);
        $this->assertStringContainsString('500', $view);
        $this->assertStringContainsString('/images/glow-logo.svg', $view);
    }
}
