<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReferenciaUiTest extends TestCase
{
    public function test_referencia_visible_en_local(): void
    {
        putenv('APP_ENV=local');
        $_ENV['APP_ENV'] = 'local';
        $_SERVER['APP_ENV'] = 'local';

        try {
            $this->refreshApplication();
            // La aplicación nueva no hereda el withoutVite() de setUp().
            $this->withoutVite();

            $response = $this->get('/ui');
        } finally {
            putenv('APP_ENV=testing');
            $_ENV['APP_ENV'] = 'testing';
            $_SERVER['APP_ENV'] = 'testing';
        }

        $response->assertOk();
        $response->assertSee('Solicitado');
        $response->assertSee('Confirmado');
        $response->assertSee('Atendido');
        $response->assertSee('Cancelado');
        $response->assertSee('Ausente');

        // Los cuatro tipos de botones
        $response->assertSee('bg-brand-600');
        $response->assertSee('border-line');
        $response->assertSee('text-brand-600');
        $response->assertSee('bg-danger-700');
        $response->assertSee('rounded-full');
    }

    public function test_referencia_no_disponible_en_produccion(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->get('/ui');

        $response->assertNotFound();
    }

    public function test_registro_usa_layout(): void
    {
        $response = $this->get('/registro');

        $response->assertOk();
        $response->assertSee('images/glow-logo.svg');
        $response->assertSee('Registro · Glow Dental Protect');
    }

    protected function tearDown(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';
        $_SERVER['APP_ENV'] = 'testing';
        $this->refreshApplication();

        parent::tearDown();
    }
}
