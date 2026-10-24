<?php

namespace Tests\Unit;

use App\Rules\CelularParaguay;
use App\Support\Telefono;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TelefonoTest extends TestCase
{
    public function test_normalizar_formatos_validos(): void
    {
        $this->assertSame('0981123456', Telefono::normalizar('0981 123 456'));
        $this->assertSame('0971123456', Telefono::normalizar('+595 971 123456'));
        $this->assertSame('0991123456', Telefono::normalizar('991123456'));
        $this->assertSame('0981123456', Telefono::normalizar('595981123456'));
        $this->assertSame('0981123456', Telefono::normalizar('(0981) 123-456'));
    }

    public function test_normalizar_formatos_invalidos_retorna_null(): void
    {
        $this->assertNull(Telefono::normalizar(null));
        $this->assertNull(Telefono::normalizar(''));
        $this->assertNull(Telefono::normalizar('   '));
        $this->assertNull(Telefono::normalizar('021 123 456')); // Fijo
        $this->assertNull(Telefono::normalizar('0981 12345')); // Corto
        $this->assertNull(Telefono::normalizar('0981 abc 456')); // Letras
        $this->assertNull(Telefono::normalizar('letras')); // Letras
        $this->assertNull(Telefono::normalizar('0951 123 456')); // 095 no es celular
    }

    public function test_legible_formatea_correctamente(): void
    {
        $this->assertSame('0981 123 456', Telefono::legible('0981123456'));
        $this->assertSame('0971 123 456', Telefono::legible('+595 971 123456'));
        $this->assertSame('0991 123 456', Telefono::legible('991123456'));
        $this->assertNull(Telefono::legible(null));
        $this->assertNull(Telefono::legible(''));
    }

    public function test_regla_celular_paraguay_acepta_validos(): void
    {
        $rule = new CelularParaguay;

        $valido1 = Validator::make(['tel' => '0981 123 456'], ['tel' => [$rule]]);
        $this->assertTrue($valido1->passes());

        $valido2 = Validator::make(['tel' => '+595 971 123456'], ['tel' => [$rule]]);
        $this->assertTrue($valido2->passes());

        $valido3 = Validator::make(['tel' => '991123456'], ['tel' => [$rule]]);
        $this->assertTrue($valido3->passes());

        $opcional = Validator::make(['tel' => null], ['tel' => ['nullable', $rule]]);
        $this->assertTrue($opcional->passes());
    }

    public function test_regla_celular_paraguay_rechaza_invalidos_con_mensaje(): void
    {
        $rule = new CelularParaguay;
        $mensajeEsperado = 'Ingresá un celular válido, por ejemplo 0981 123 456.';

        // Fijo Asunción
        $v1 = Validator::make(['tel' => '021 123 456'], ['tel' => [$rule]]);
        $this->assertTrue($v1->fails());
        $this->assertSame($mensajeEsperado, $v1->errors()->first('tel'));

        // Corto
        $v2 = Validator::make(['tel' => '0981 12345'], ['tel' => [$rule]]);
        $this->assertTrue($v2->fails());
        $this->assertSame($mensajeEsperado, $v2->errors()->first('tel'));

        // Letras
        $v3 = Validator::make(['tel' => '0981 abc 456'], ['tel' => [$rule]]);
        $this->assertTrue($v3->fails());
        $this->assertSame($mensajeEsperado, $v3->errors()->first('tel'));

        // 0951 no es celular
        $v4 = Validator::make(['tel' => '0951 123 456'], ['tel' => [$rule]]);
        $this->assertTrue($v4->fails());
        $this->assertSame($mensajeEsperado, $v4->errors()->first('tel'));
    }
}
