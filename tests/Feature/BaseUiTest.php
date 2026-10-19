<?php

namespace Tests\Feature;

use Tests\TestCase;

class BaseUiTest extends TestCase
{
    public function test_logo_disponible(): void
    {
        $this->assertFileExists(public_path('images/glow-logo.svg'));
        $this->assertFileExists(public_path('images/glow-logo-blanco.svg'));

        $logoColor = file_get_contents(public_path('images/glow-logo.svg'));
        $this->assertStringContainsString('<svg', $logoColor);
        $this->assertStringContainsString('viewBox', $logoColor);

        $logoBlanco = file_get_contents(public_path('images/glow-logo-blanco.svg'));
        $this->assertStringContainsString('<svg', $logoBlanco);
        $this->assertStringContainsString('viewBox', $logoBlanco);
        $this->assertStringContainsString('#FFFFFF', $logoBlanco);
    }

    public function test_whatsapp_configurable(): void
    {
        config(['services.whatsapp.numero' => '595971131182']);
        $this->assertSame('595971131182', config('services.whatsapp.numero'));

        config(['services.whatsapp.numero' => null]);
        $this->assertNull(config('services.whatsapp.numero'));
    }

    public function test_tokens_de_marca_y_fuentes_configurados_en_css(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('--color-brand-700: #7A3F22', $css);
        $this->assertStringContainsString('--color-brand-600: #995432', $css);
        $this->assertStringContainsString('--color-brand-300: #D8B78A', $css);
        $this->assertStringContainsString('--color-brand-100: #F3E6D3', $css);
        $this->assertStringContainsString('--color-cream-50: #FBF7F2', $css);
        $this->assertStringContainsString('--color-ink-900: #1F1A17', $css);
        $this->assertStringContainsString('--color-ink-500: #6B635D', $css);
        $this->assertStringContainsString('--color-line: #E5E2DE', $css);
        $this->assertStringContainsString('--color-surface: #F7F6F4', $css);
        $this->assertStringContainsString('--color-danger-700: #B91C1C', $css);

        $this->assertStringContainsString('--font-sans', $css);
        $this->assertStringContainsString('Open Sans', $css);
        $this->assertStringContainsString('--font-display', $css);
        $this->assertStringContainsString('Quicksand', $css);
    }

    public function test_alpine_inicializado_en_js(): void
    {
        $js = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("import Alpine from 'alpinejs'", $js);
        $this->assertStringContainsString('window.Alpine = Alpine', $js);
        $this->assertStringContainsString('Alpine.start()', $js);
    }
}
