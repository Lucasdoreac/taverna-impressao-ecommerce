<?php

return [
    'default_ttl' => 3600, // 1 hour in seconds
    'query_cache_ttl' => 1800, // 30 minutes
    'page_cache_ttl' => 900, // 15 minutes
    'asset_cache_ttl' => 86400, // 24 hours
    'opcache' => [
        'enable' => true,
        'cli_enable' => true,
        'validate_timestamps' => false,
        'revalidate_freq' => 0,
        'max_files' => 10000,
        'memory' => 128,
        'strings_buffer' => 16,
    ],
    'session' => [
        'cache_limiter' => 'private_no_expire',
        'cache_expire' => 180, // 3 hours
    ],
];