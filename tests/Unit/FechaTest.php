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

    public function test_cuando_devuelve_hoy_manana_o_fecha_corta(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-18 10:00:00', config('app.timezone')));

        $hoy = Carbon::parse('2026-10-18 16:00:00', config('app.timezone'));
        $manana = Carbon::parse('2026-10-19 10:00:00', config('app.timezone'));
        $en5Dias = Carbon::parse('2026-10-23 09:00:00', config('app.timezone'));

        $this->assertSame('hoy', Fecha::cuando($hoy));
        $this->assertSame('mañana', Fecha::cuando($manana));
        $this->assertSame('Vie 23 oct', Fecha::cuando($en5Dias));
    }
}
