<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class SinEmojisTest extends TestCase
{
    public function test_las_vistas_no_contienen_emojis_ni_simbolos_unicode_como_iconos(): void
    {
        $viewsPath = resource_path('views');
        $archivosConEmojis = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($viewsPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        // Rango unicode para emojis y símbolos misceláneos/dingbats
        $pattern = '/[\x{2600}-\x{27BF}\x{1F300}-\x{1FAFF}]/u';

        foreach ($iterator as $archivo) {
            if ($archivo->isFile() && str_ends_with($archivo->getFilename(), '.blade.php')) {
                $contenido = file_get_contents($archivo->getPathname());
                if (preg_match($pattern, $contenido, $matches)) {
                    $archivosConEmojis[$archivo->getPathname()] = $matches[0];
                }
            }
        }

        $this->assertEmpty(
            $archivosConEmojis,
            'Se encontraron emojis o símbolos unicode en las siguientes vistas: '.print_r($archivosConEmojis, true)
        );
    }
}
