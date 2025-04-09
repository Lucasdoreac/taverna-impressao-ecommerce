<?php

require_once __DIR__ . '/CacheHelper.php';

// Initialize cache settings
ini_set('opcache.enable', 1);
ini_set('opcache.enable_cli', 1);
ini_set('opcache.revalidate_freq', 0);
ini_set('opcache.validate_timestamps', 0);
ini_set('opcache.max_accelerated_files', 10000);
ini_set('opcache.memory_consumption', 128);
ini_set('opcache.interned_strings_buffer', 16);

// Set session cache limiter
session_cache_limiter('private_no_expire');
session_cache_expire(180); // 3 hours

// Initialize CacheHelper
CacheHelper::getInstance();