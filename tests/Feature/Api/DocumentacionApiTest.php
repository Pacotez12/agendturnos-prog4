<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class DocumentacionApiTest extends TestCase
{
    public function test_documento_openapi_json_devuelve_200_en_entorno_local(): void
    {
        $this->app['env'] = 'local';

        $response = $this->getJson('/docs/api.json');

        $response->assertOk()
            ->assertJsonPath('openapi', fn (string $version) => str_starts_with($version, '3.'))
            ->assertJsonPath('info.title', 'Glow Dental Protect API')
            ->assertJsonPath('info.version', '1.0.0');
    }

    public function test_documento_openapi_contiene_los_endpoints_de_la_api(): void
    {
        $this->app['env'] = 'local';

        $response = $this->getJson('/docs/api.json');
        $response->assertOk();

        $data = $response->json();
        $paths = $data['paths'] ?? [];

        $this->assertArrayHasKey('/servicios', $paths);
        $this->assertArrayHasKey('get', $paths['/servicios']);

        $this->assertArrayHasKey('/profesionales', $paths);
        $this->assertArrayHasKey('get', $paths['/profesionales']);

        $this->assertArrayHasKey('/disponibilidad', $paths);
        $this->assertArrayHasKey('get', $paths['/disponibilidad']);

        $this->assertArrayHasKey('/token', $paths);
        $this->assertArrayHasKey('post', $paths['/token']);
        $this->assertArrayHasKey('delete', $paths['/token']);

        $this->assertArrayHasKey('/mis-turnos', $paths);
        $this->assertArrayHasKey('get', $paths['/mis-turnos']);
    }

    public function test_rutas_protegidas_tienen_seguridad_bearer_y_publicas_no(): void
    {
        $this->app['env'] = 'local';

        $response = $this->getJson('/docs/api.json');
        $response->assertOk();

        $data = $response->json();

        // Esquema Bearer aplicado a todo el documento
        $this->assertSame(['type' => 'http', 'scheme' => 'bearer'], $data['components']['securitySchemes']['http'] ?? null);
        $this->assertSame([['http' => []]], $data['security'] ?? null);

        // Las rutas con token heredan la seguridad global
        $this->assertArrayNotHasKey('security', $data['paths']['/mis-turnos']['get']);
        $this->assertArrayNotHasKey('security', $data['paths']['/token']['delete']);

        // Las públicas la anulan con una lista vacía
        foreach ([['/servicios', 'get'], ['/profesionales', 'get'], ['/disponibilidad', 'get'], ['/token', 'post']] as [$ruta, $metodo]) {
            $this->assertSame([], $data['paths'][$ruta][$metodo]['security'] ?? null, "{$metodo} {$ruta}");
        }
    }

    public function test_ui_documentacion_devuelve_200_en_entorno_local(): void
    {
        $this->app['env'] = 'local';

        $response = $this->get('/docs/api');

        $response->assertOk();
    }

    public function test_ui_documentacion_devuelve_403_en_entorno_production(): void
    {
        $this->app['env'] = 'production';

        $response = $this->get('/docs/api');

        $response->assertForbidden();
    }
}
