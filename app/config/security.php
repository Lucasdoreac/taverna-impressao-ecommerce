<?php

return [
    'csrf' => [
        'token_lifetime' => 1800, // 30 minutes in seconds
        'regenerate_on_valid' => true,
    ],
    'brute_force' => [
        'max_attempts' => 3,
        'time_window' => 1800, // 30 minutes in seconds
        'block_duration' => 3600, // 1 hour in seconds
    ],
    'session' => [
        'lifetime' => 7200, // 2 hours in seconds
        'regenerate_id' => true,
        'secure_cookie' => true,
        'http_only' => true,
    ],
    'headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
        'X-Content-Type-Options' => 'nosniff',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
    ],
];