<?php

return [
    'security_log' => [
        'enabled' => true,
        'table' => 'login_logs',
        'events' => [
            'login' => true,
            'logout' => true,
            'failed_login' => true
        ],
        'retention_days' => 90
    ]
];