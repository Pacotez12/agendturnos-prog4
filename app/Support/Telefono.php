<?php

namespace App\Support;

class Telefono
{
    /**
     * Normaliza un celular paraguayo al formato local canónico 09XXXXXXXX (10 dígitos).
     * Devuelve null si no es un celular paraguayo válido o si está vacío.
     */
    public static function normalizar(?string $telefono): ?string
    {
        if ($telefono === null || trim($telefono) === '') {
            return null;
        }

        $limpio = trim($telefono);

        // Solo se permiten dígitos, espacios, guiones, paréntesis y '+' opcional al inicio
        if (! preg_match('/^\+?[0-9\s\-\(\)]+$/', $limpio)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $limpio);
        if (empty($digits)) {
            return null;
        }

        // Si empieza con prefijo internacional 595
        if (str_starts_with($digits, '595')) {
            $digits = substr($digits, 3);
        }

        // Si empieza con 0, quitarlo para evaluar el cuerpo
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        // Celular paraguayo: después del 0 viene 9 y luego 6, 7, 8 o 9 (09[6-9] + 7 dígitos)
        if (preg_match('/^9[6-9]\d{7}$/', $digits)) {
            return '0'.$digits;
        }

        return null;
    }

    /**
     * Formatea un celular paraguayo como "0981 123 456".
     */
    public static function legible(?string $telefono): ?string
    {
        if ($telefono === null || trim($telefono) === '') {
            return null;
        }

        $normalizado = self::normalizar($telefono);
        if (! $normalizado || strlen($normalizado) !== 10) {
            return $telefono;
        }

        return substr($normalizado, 0, 4).' '.substr($normalizado, 4, 3).' '.substr($normalizado, 7, 3);
    }
}
