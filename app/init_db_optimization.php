<?php

require_once __DIR__ . '/helpers/SQLOptimizationHelper.php';
require_once __DIR__ . '/config/database.php';

// Apply database optimization settings
$dbConfig = require(__DIR__ . '/config/database.php');
$optimization = $dbConfig['query_optimization'];

// Apply query cache settings
ini_set('mysql.query_cache_size', $optimization['query_cache_size']);
ini_set('mysql.join_buffer_size', $optimization['enable_join_buffer_size']);
ini_set('mysql.table_open_cache', $optimization['table_open_cache']);
ini_set('mysql.thread_cache_size', $optimization['thread_cache_size']);
ini_set('mysql.max_connections', $optimization['max_connections']);

// Enable performance monitoring
if ($dbConfig['performance']['slow_query_log']) {
    ini_set('mysql.slow_query_log', 1);
    ini_set('mysql.slow_query_log_file', ROOT_PATH . '/logs/mysql-slow.log');
    ini_set('mysql.long_query_time', $dbConfig['performance']['slow_query_log_time']);
}

if ($dbConfig['performance']['log_queries_not_using_indexes']) {
    ini_set('mysql.log_queries_not_using_indexes', 1);
}

// Apply indices
$optimizer = new SQLOptimizationHelper();
$optimizer->applyRecommendedIndices();