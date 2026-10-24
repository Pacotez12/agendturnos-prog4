<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_panel_incluye_link_manifest(): void
    {
        $funcionario = User::factory()->funcionario()->create();

        $response = $this->actingAs($funcionario)->get(route('panel.inicio'));

        $response->assertOk();
        $response->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false);
    }

    public function test_layout_paciente_no_incluye_link_manifest(): void
    {
        $paciente = User::factory()->paciente()->create();

        $response = $this->actingAs($paciente)->get(route('paciente.inicio'));

        $response->assertOk();
        $response->assertDontSee('rel="manifest"', false);
    }

    public function test_layout_invitado_no_incluye_link_manifest(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('rel="manifest"', false);
    }

    public function test_manifest_webmanifest_es_json_valido_y_tiene_campos_requeridos(): void
    {
        $path = public_path('manifest.webmanifest');
        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $manifest = json_decode($content, true);

        $this->assertIsArray($manifest, 'El archivo manifest.webmanifest debe ser un JSON válido.');
        $this->assertSame('Glow Dental · Panel', $manifest['name'] ?? null);
        $this->assertSame('Glow Panel', $manifest['short_name'] ?? null);
        $this->assertSame('/panel', $manifest['start_url'] ?? null);
        $this->assertSame('/', $manifest['scope'] ?? null);
        $this->assertSame('standalone', $manifest['display'] ?? null);
        $this->assertSame('#FBF7F2', $manifest['background_color'] ?? null);
        $this->assertSame('#995432', $manifest['theme_color'] ?? null);
        $this->assertSame('es', $manifest['lang'] ?? null);

        $this->assertNotEmpty($manifest['icons'] ?? []);

        $iconSrcs = array_column($manifest['icons'], 'src');
        $this->assertContains('/icons/icon-192.png', $iconSrcs);
        $this->assertContains('/icons/icon-512.png', $iconSrcs);

        // Al menos un icono debe ser maskable con tamaño 512
        $hasMaskable512 = false;
        foreach ($manifest['icons'] as $icon) {
            $purpose = $icon['purpose'] ?? 'any';
            $sizes = $icon['sizes'] ?? '';
            if (str_contains($purpose, 'maskable') && str_contains($sizes, '512x512')) {
                $hasMaskable512 = true;
                break;
            }
        }
        $this->assertTrue($hasMaskable512, 'El manifest debe incluir un icono 512x512 maskable.');

        // Todos los iconos declarados deben existir en el disco
        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }

    public function test_service_worker_existe_y_no_cachea_datos_ni_paginas(): void
    {
        $path = public_path('sw.js');
        $this->assertFileExists($path);

        $sw = file_get_contents($path);

        // install / activate con skipWaiting y clients.claim
        $this->assertStringContainsString('skipWaiting', $sw);
        $this->assertStringContainsString('clients.claim', $sw);

        // No cachea datos ni páginas (sin caches.put, caches.open ni addAll)
        $this->assertStringNotContainsString('caches.put', $sw);
        $this->assertStringNotContainsString('addAll', $sw);
        $this->assertStringNotContainsString('caches.open', $sw);

        // Mensaje offline inline cuando falla la red en navegación
        $this->assertStringContainsString('Sin conexión. Revisá tu internet y volvé a intentar.', $sw);
    }

    public function test_app_js_registra_service_worker_solo_si_existe_manifest(): void
    {
        $appJsPath = resource_path('js/app.js');
        $this->assertFileExists($appJsPath);

        $appJs = file_get_contents($appJsPath);

        $this->assertStringContainsString('serviceWorker', $appJs);
        $this->assertStringContainsString('navigator.serviceWorker.register', $appJs);
        $this->assertStringContainsString('rel="manifest"', $appJs);
    }

    public function test_nginx_configuracion_incluye_manifest_y_sw_sin_cache(): void
    {
        $nginxConfPath = base_path('docker/nginx/default.conf');
        $this->assertFileExists($nginxConfPath);

        $nginxConf = file_get_contents($nginxConfPath);

        $this->assertStringContainsString('manifest.webmanifest', $nginxConf);
        $this->assertStringContainsString('application/manifest+json', $nginxConf);
        $this->assertStringContainsString('sw.js', $nginxConf);
        $this->assertStringContainsString('no-cache', $nginxConf);
    }
}
