<?php
// Ponto de entrada da aplicação
define('START_TIME', microtime(true));

// Carregar configurações
require_once __DIR__ . '/../app/config/config.php';

// CORREÇÃO: Carregar novo autoloader antes de qualquer outro código
require_once __DIR__ . '/../app/autoload.php';

// Carregar rotas
require_once __DIR__ . '/../app/config/routes.php';

// Backup de carregamento de database para compatibilidade
if (!class_exists('Database')) {
    require_once __DIR__ . '/../app/core/Database.php';
}

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

// Log de depuração em ambiente de desenvolvimento
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    $loadedClasses = get_declared_classes();
    $appClasses = array_filter($loadedClasses, function($class) {
        return !strpos($class, '\\') && !in_array($class, ['stdClass', 'Exception', 'Error', 'PDO', 'DateTime']);
    });
    error_log("Classes carregadas antes da inicialização do roteador: " . implode(', ', $appClasses));
}

// Inicializar o roteador
$router = new Router();
$router->dispatch();
