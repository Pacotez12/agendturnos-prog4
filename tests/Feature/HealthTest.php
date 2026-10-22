<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Redis;
use RuntimeException;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_responde_ok_con_base_y_redis_disponibles(): void
    {
        $this->get('/health')->assertOk();
    }

    public function test_health_falla_si_redis_no_responde(): void
    {
        config(['app.debug' => false]);
        Redis::shouldReceive('ping')->andThrow(new RuntimeException('Redis caído'));

        $this->get('/health')->assertStatus(500);
    }

    public function test_health_no_inicia_sesion(): void
    {
        $this->get('/health')->assertCookieMissing(config('session.cookie'));
    }
}
