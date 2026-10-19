<?php

namespace Tests\Feature;

use Tests\TestCase;

class ComponentesInteractivosTest extends TestCase
{
    public function test_whatsapp_oculto_sin_numero(): void
    {
        config(['services.whatsapp.numero' => null]);
        $viewVacio = $this->blade('<x-whatsapp-button />');
        $viewVacio->assertDontSee('wa.me');

        config(['services.whatsapp.numero' => '595971131182']);
        $viewConNumero = $this->blade('<x-whatsapp-button />');
        $viewConNumero->assertSee('https://wa.me/595971131182', false);
        $viewConNumero->assertSee('rel="noopener"', false);
        $viewConNumero->assertSee('¿Dudas? Escribinos');
    }

    public function test_whatsapp_renderiza_svg_dentro_del_enlace(): void
    {
        config(['services.whatsapp.numero' => '595971131182']);
        $html = (string) $this->blade('<x-whatsapp-button />');

        $this->assertMatchesRegularExpression('/<a[^>]*wa\.me[^>]*>\s*<svg/s', $html);
    }

    public function test_slot_picker(): void
    {
        $horarios = [
            ['hora' => '08:00', 'libre' => true],
            ['hora' => '08:30', 'libre' => false],
        ];

        $view = $this->blade('<x-slot-picker name="horario" :horarios="$horarios" />', [
            'horarios' => $horarios,
        ]);

        $view->assertSee('value="08:00"', false);
        $view->assertSee('value="08:30"', false);
        $view->assertSee('disabled', false);
        $view->assertSee('name="horario"', false);
    }

    public function test_modal(): void
    {
        $view = $this->blade(
            '<x-modal name="confirmar-cancelacion" titulo="¿Cancelar turno?">¿Estás seguro de cancelar el turno?</x-modal>'
        );

        $view->assertSee('role="dialog"', false);
        $view->assertSee('aria-modal="true"', false);
        $view->assertSee('¿Cancelar turno?');
        $view->assertSee('¿Estás seguro de cancelar el turno?');
        $view->assertSee('@keydown.escape', false);
    }
}
