<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp' => [
        'numero' => env('WHATSAPP_NUMERO'),
        'token' => env('WHATSAPP_TOKEN'),
        'phone_id' => env('WHATSAPP_PHONE_ID'),
        // Plantillas aprobadas en Meta, una por tipo de aviso
        'plantillas' => [
            'confirmacion' => 'turno_confirmado',
            'cancelacion' => 'turno_cancelado',
            'reprogramacion' => 'turno_reprogramado',
            'recordatorio' => 'turno_recordatorio',
        ],
        // Plantillas de 4 parámetros para turnos propios del titular
        'plantillas_propio' => [
            'confirmacion' => 'turno_confirmado_propio',
            'cancelacion' => 'turno_cancelado_propio',
            'reprogramacion' => 'turno_reprogramado_propio',
            'recordatorio' => 'turno_recordatorio_propio',
        ],
    ],

];
