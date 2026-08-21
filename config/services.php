<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    /*
    | Passerelle locale d'envoi WhatsApp automatique des reçus (disciples,
    | mensualités) — cf. whatsapp-bridge/. Le navigateur du poste du club
    | contacte cette adresse directement (le serveur web ne peut pas
    | forcément atteindre le PC qui héberge la passerelle).
    */
    'whatsapp_bridge' => [
        'default_host' => env('WHATSAPP_BRIDGE_HOST', '127.0.0.1:9300'),
        'token' => env('WHATSAPP_BRIDGE_TOKEN'),
        'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '223'),
    ],

];
