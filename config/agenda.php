<?php

return [
    'intervalo_minutos' => (int) env('AGENDA_INTERVALO_MINUTOS', 15),
    'cancelacion_horas' => (int) env('AGENDA_CANCELACION_HORAS', 24),
];
