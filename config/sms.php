<?php

return [
    'enabled' => filter_var(env('SMS_ENABLED', false), FILTER_VALIDATE_BOOL),
    'api_url' => env('SMS_API_URL', ''),
    'api_key' => env('SMS_API_KEY', ''),
    'sender_id' => env('SMS_SENDER_ID', ''),
    'method' => strtoupper((string) env('SMS_HTTP_METHOD', 'GET')),
    'phone_param' => env('SMS_PHONE_PARAM', 'phone'),
    'message_param' => env('SMS_MESSAGE_PARAM', 'message'),
    'key_param' => env('SMS_KEY_PARAM', 'api_key'),
    'sender_param' => env('SMS_SENDER_PARAM', 'sender'),
];
