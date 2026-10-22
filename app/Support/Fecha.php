<?php

namespace App\Support;

use Carbon\CarbonInterface;

class Fecha
{
    /**
     * Formatea una fecha en formato corto: "Vie 23 oct".
     */
    public static function corta(CarbonInterface $f): string
    {
        $dia = ucfirst(rtrim($f->translatedFormat('D'), '.'));
        $mes = rtrim($f->translatedFormat('M'), '.');

        return "{$dia} {$f->format('j')} {$mes}";
    }
}
