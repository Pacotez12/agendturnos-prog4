<?php

return [
    'intervalo_minutos' => (int) env('AGENDA_INTERVALO_MINUTOS', 15),
    'cancelacion_horas' => (int) env('AGENDA_CANCELACION_HORAS', 24),
    'silencio_desde' => env('AVISOS_SILENCIO_DESDE', '21:00'),
    'silencio_hasta' => env('AVISOS_SILENCIO_HASTA', '08:00'),
    'max_solicitudes_pendientes' => (int) env('AGENDA_MAX_SOLICITUDES', 2),
];
