<?php

namespace Tests\Unit;

use App\Enums\EstadoTurno;
use PHPUnit\Framework\TestCase;

class EstadoTurnoTest extends TestCase
{
    public function test_transiciones_validas_desde_solicitado(): void
    {
        $solicitado = EstadoTurno::Solicitado;

        $this->assertTrue($solicitado->puedePasarA(EstadoTurno::Confirmado));
        $this->assertTrue($solicitado->puedePasarA(EstadoTurno::Cancelado));

        $this->assertFalse($solicitado->puedePasarA(EstadoTurno::Solicitado));
        $this->assertFalse($solicitado->puedePasarA(EstadoTurno::Atendido));
        $this->assertFalse($solicitado->puedePasarA(EstadoTurno::Ausente));
    }

    public function test_transiciones_validas_desde_confirmado(): void
    {
        $confirmado = EstadoTurno::Confirmado;

        $this->assertTrue($confirmado->puedePasarA(EstadoTurno::Atendido));
        $this->assertTrue($confirmado->puedePasarA(EstadoTurno::Cancelado));
        $this->assertTrue($confirmado->puedePasarA(EstadoTurno::Ausente));

        $this->assertFalse($confirmado->puedePasarA(EstadoTurno::Confirmado));
        $this->assertFalse($confirmado->puedePasarA(EstadoTurno::Solicitado));
    }

    public function test_estados_terminales_no_tienen_transiciones_validas(): void
    {
        $terminales = [
            EstadoTurno::Atendido,
            EstadoTurno::Cancelado,
            EstadoTurno::Ausente,
        ];

        foreach ($terminales as $terminal) {
            foreach (EstadoTurno::cases() as $destino) {
                $this->assertFalse(
                    $terminal->puedePasarA($destino),
                    "Estado {$terminal->value} no debería poder pasar a {$destino->value}"
                );
            }
        }
    }
}
