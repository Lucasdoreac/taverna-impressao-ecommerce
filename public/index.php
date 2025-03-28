<?php
// Ponto de entrada da aplicação
define('START_TIME', microtime(true));
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/routes.php';

// Carregar autoloader
spl_autoload_register(function($class) {
    $paths = [
        APP_PATH . '/controllers/',
        APP_PATH . '/models/',
        APP_PATH . '/helpers/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Inicializar o roteador
$router = new Router();
$router->dispatch();