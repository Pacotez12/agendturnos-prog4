<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ConsoleScheduleTest extends TestCase
{
    public function test_turnos_recordatorios_esta_programado_sin_solapamiento_y_en_un_solo_servidor(): void
    {
        $schedule = app(Schedule::class);

        $event = collect($schedule->events())->first(function (Event $e) {
            return str_contains($e->command ?? '', 'turnos:recordatorios');
        });

        $this->assertNotNull($event, 'El comando turnos:recordatorios debe estar en el scheduler.');
        $this->assertTrue($event->withoutOverlapping, 'El comando debe ejecutarse con withoutOverlapping.');
        $this->assertTrue($event->onOneServer, 'El comando debe ejecutarse con onOneServer.');
        $this->assertSame('0 * * * *', $event->expression, 'El comando debe ejecutarse cada hora.');
    }
}
