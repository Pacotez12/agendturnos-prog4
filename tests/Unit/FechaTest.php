<?php

namespace Tests\Unit;

use App\Support\Fecha;
use Carbon\Carbon;
use Tests\TestCase;

class FechaTest extends TestCase
{
    public function test_formato_fecha_corta(): void
    {
        $fecha = Carbon::parse('2026-10-23 09:00:00', config('app.timezone'));

        $this->assertSame('Vie 23 oct', Fecha::corta($fecha));
    }

    public function test_formato_fecha_corta_otro_dia(): void
    {
        // 2026-10-19 es lunes
        $fecha = Carbon::parse('2026-10-19 14:30:00', config('app.timezone'));

        $this->assertSame('Lun 19 oct', Fecha::corta($fecha));
    }
}
