<?php
// Ponto de entrada da aplicação
define('START_TIME', microtime(true));
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Database.php'; // Carregar explicitamente Database.php
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

// Carregar e inicializar helpers de otimização
$initHelpersFile = APP_PATH . '/helpers/init_helpers.php';
if (file_exists($initHelpersFile)) {
    require_once $initHelpersFile;
}

// Verificar se há uma solicitação para servir arquivos estáticos diretamente
// Isso permite um fallback direto para arquivos comuns sem passar pelo roteador
if (isset($_SERVER['REQUEST_URI'])) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Array de extensões comuns e suas pastas correspondentes
    $staticExtensions = [
        'css'   => 'public/assets/css/',
        'js'    => 'public/assets/js/',
        'jpg'   => 'public/assets/images/',
        'jpeg'  => 'public/assets/images/',
        'png'   => 'public/assets/images/',
        'gif'   => 'public/assets/images/',
        'svg'   => 'public/assets/images/',
        'ico'   => 'public/assets/images/',
        'woff'  => 'public/assets/fonts/',
        'woff2' => 'public/assets/fonts/',
        'ttf'   => 'public/assets/fonts/',
        'eot'   => 'public/assets/fonts/',
        'otf'   => 'public/assets/fonts/'
    ];
    
    $ext = pathinfo($uri, PATHINFO_EXTENSION);
    if (!empty($ext) && isset($staticExtensions[$ext])) {
        $filename = basename($uri);
        $filePath = ROOT_PATH . '/' . $staticExtensions[$ext] . $filename;
        
        // Se o arquivo existir e o CacheHelper estiver disponível
        if (file_exists($filePath) && class_exists('CacheHelper')) {
            // Servir arquivo com cache
            CacheHelper::serveStaticFile($filePath);
            exit;
        }
    }
}

// Inicializar o roteador
$router = new Router();
$router->dispatch();