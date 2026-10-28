<?php

namespace Tests\Feature;

use App\Enums\EstadoTurno;
use Tests\TestCase;

class ComponentesTest extends TestCase
{
    public function test_boton_primario(): void
    {
        $view = $this->blade('<x-button>Guardar</x-button>');
        $view->assertSee('bg-brand-600');
        $view->assertSee('min-h-11');
        $view->assertSee('Guardar');
        $view->assertDontSee('<a', false);

        $viewDanger = $this->blade('<x-button variant="danger">Eliminar</x-button>');
        $viewDanger->assertSee('bg-danger-700');
        $viewDanger->assertSee('Eliminar');

        $viewLink = $this->blade('<x-button href="/x">Ir a X</x-button>');
        $viewLink->assertSee('<a href="/x"', false);
        $viewLink->assertSee('Ir a X');
    }

    public function test_boton_primario_usa_el_token_de_hover_y_no_brand_700(): void
    {
        $html = (string) $this->blade('<x-button>Guardar</x-button>');

        $this->assertStringContainsString('hover:bg-brand-hover', $html);
        $this->assertStringNotContainsString('bg-brand-700', $html);
    }

    /**
     * En modo oscuro brand-700 es un color claro (se usa como texto), así que
     * el hover del botón principal tiene su propio token. Con texto blanco tiene
     * que cumplir contraste AA (4.5:1) en los dos temas.
     */
    public function test_el_hover_del_boton_principal_cumple_contraste_con_texto_blanco(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        preg_match_all('/--color-brand-hover:\s*#([0-9A-Fa-f]{6})/', $css, $coincidencias);
        $this->assertCount(2, $coincidencias[1], 'brand-hover tiene que estar definido en el tema claro y en .dark');

        $luminancia = function (string $hex): float {
            $canales = array_map(function (string $par) {
                $c = hexdec($par) / 255;

                return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            }, str_split($hex, 2));

            return 0.2126 * $canales[0] + 0.7152 * $canales[1] + 0.0722 * $canales[2];
        };

        foreach ($coincidencias[1] as $hex) {
            $contraste = 1.05 / ($luminancia($hex) + 0.05);
            $this->assertGreaterThanOrEqual(4.5, $contraste, "#{$hex} con texto blanco tiene contraste {$contraste}:1");
        }
    }

    public function test_boton_variantes_y_pill(): void
    {
        $viewSecondary = $this->blade('<x-button variant="secondary">Cancelar</x-button>');
        $viewSecondary->assertSee('border-line');
        $viewSecondary->assertSee('Cancelar');

        $viewGhost = $this->blade('<x-button variant="ghost">Enlace</x-button>');
        $viewGhost->assertSee('text-brand-600');
        $viewGhost->assertSee('Enlace');

        $viewPill = $this->blade('<x-button :pill="true">Píldora</x-button>');
        $viewPill->assertSee('rounded-full');

        $viewNoPill = $this->blade('<x-button>Recto</x-button>');
        $viewNoPill->assertSee('rounded-lg');
    }

    public function test_input_muestra_error_y_old(): void
    {
        $viewConError = $this->withViewErrors(['email' => 'El correo es obligatorio.'])
            ->blade('<x-input name="email" label="Correo" help="Ingresá tu correo personal" />');

        $viewConError->assertSee('<label for="email"', false);
        $viewConError->assertSee('Correo');
        $viewConError->assertSee('Ingresá tu correo personal');
        $viewConError->assertSee('El correo es obligatorio.');
        $viewConError->assertSee('aria-invalid="true"', false);
        $viewConError->assertSee('text-danger-700');

        $viewSinError = $this->withViewErrors([])->blade('<x-input name="email" label="Correo" />');
        $viewSinError->assertDontSee('aria-invalid="true"', false);
    }

    public function test_select_muestra_opciones_y_error(): void
    {
        $options = [
            'CI' => 'Cédula de Identidad',
            'pasaporte' => 'Pasaporte',
        ];

        $view = $this->blade('<x-select name="tipo_documento" label="Tipo de documento" :options="$options" />', [
            'options' => $options,
        ]);

        $view->assertSee('<label for="tipo_documento"', false);
        $view->assertSee('Tipo de documento');
        $view->assertSee('<select', false);
        $view->assertSee('Cédula de Identidad');
        $view->assertSee('Pasaporte');
        $view->assertSee('value="CI"', false);

        $viewError = $this->withViewErrors(['tipo_documento' => 'Seleccioná un tipo'])
            ->blade('<x-select name="tipo_documento" label="Tipo" :options="$options" />', [
                'options' => $options,
            ]);
        $viewError->assertSee('aria-invalid="true"', false);
        $viewError->assertSee('Seleccioná un tipo');
    }

    public function test_date_renderiza_input_tipo_date(): void
    {
        $view = $this->blade('<x-date name="fecha_nacimiento" label="Fecha de Nacimiento" max="2026-10-18" />');

        $view->assertSee('type="date"', false);
        $view->assertSee('max="2026-10-18"', false);
        $view->assertSee('Fecha de Nacimiento');
        $view->assertSee('name="fecha_nacimiento"', false);
    }

    public function test_estado_turno(): void
    {
        $nombres = [
            EstadoTurno::Solicitado->value => 'Solicitado',
            EstadoTurno::Confirmado->value => 'Confirmado',
            EstadoTurno::Atendido->value => 'Atendido',
            EstadoTurno::Cancelado->value => 'Cancelado',
            EstadoTurno::Ausente->value => 'Ausente',
        ];

        foreach (EstadoTurno::cases() as $estado) {
            $view = $this->blade('<x-estado-turno :estado="$estado" />', ['estado' => $estado]);

            $view->assertSee($nombres[$estado->value]);
            $view->assertSee("estado-{$estado->value}-bg");
            $view->assertSee("estado-{$estado->value}-fg");
        }
    }

    public function test_alert(): void
    {
        $viewError = $this->blade('<x-alert type="error">Ocurrió un error al guardar</x-alert>');
        $viewError->assertSee('role="alert"', false);
        $viewError->assertSee('Ocurrió un error al guardar');

        $viewSuccess = $this->blade('<x-alert type="success">Turno solicitado con éxito</x-alert>');
        $viewSuccess->assertSee('role="status"', false);
        $viewSuccess->assertSee('Turno solicitado con éxito');

        $viewInfo = $this->blade('<x-alert type="info">Recordatorio informativo</x-alert>');
        $viewInfo->assertSee('role="status"', false);
        $viewInfo->assertSee('Recordatorio informativo');
    }

    public function test_card(): void
    {
        $viewNormal = $this->blade('<x-card>Contenido de prueba</x-card>');
        $viewNormal->assertSee('Contenido de prueba');
        $viewNormal->assertSee('rounded-lg');

        $viewPaciente = $this->blade('<x-card :paciente="true">Contenido paciente</x-card>');
        $viewPaciente->assertSee('Contenido paciente');
        $viewPaciente->assertSee('rounded-2xl');
    }

    public function test_empty_state(): void
    {
        $view = $this->blade(
            '<x-empty-state titulo="Sin turnos" texto="No tenés turnos programados."><x-button>Reservar turno</x-button></x-empty-state>'
        );

        $view->assertSee('Sin turnos');
        $view->assertSee('No tenés turnos programados.');
        $view->assertSee('Reservar turno');
        $view->assertSee('bg-brand-600');
    }

    public function test_empty_state_con_icono(): void
    {
        $view = $this->blade(
            '<x-empty-state titulo="Sin familiares"><x-slot:icono><x-phosphor-users class="size-8" /></x-slot:icono></x-empty-state>'
        );

        $view->assertSee('Sin familiares');
        $view->assertSee('<svg', false);
        $view->assertSee('text-brand-text', false);
    }

    public function test_empty_state_sin_icono_no_agrega_contenedor(): void
    {
        $view = $this->blade('<x-empty-state titulo="Sin turnos" />');

        $view->assertDontSee('<svg', false);
        $view->assertDontSee('text-brand-text', false);
    }
}
