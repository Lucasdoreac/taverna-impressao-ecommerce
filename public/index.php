<?php
// Ponto de entrada da aplicação
define('START_TIME', microtime(true));

// Acompanhamento para diagnóstico
$loadingSteps = [];
$loadingSteps[] = "Início do carregamento: " . date('H:i:s');

// Carregar configurações
require_once __DIR__ . '/../app/config/config.php';
$loadingSteps[] = "Config carregado";

// CORREÇÃO: Carregar autoloader com verificação de erros
$autoloaderPath = __DIR__ . '/../app/autoload.php';
if (file_exists($autoloaderPath)) {
    require_once $autoloaderPath;
    $loadingSteps[] = "Autoloader carregado";
} else {
    $loadingSteps[] = "ERRO: Autoloader não encontrado em " . $autoloaderPath;
}

// Carregar rotas com verificação de erros
$routesPath = __DIR__ . '/../app/config/routes.php';
if (file_exists($routesPath)) {
    require_once $routesPath;
    $loadingSteps[] = "Rotas carregadas";
} else {
    $loadingSteps[] = "ERRO: Arquivo de rotas não encontrado em " . $routesPath;
}

// Verificação de existência de classes críticas
$criticalClasses = [
    'Router' => ['app/helpers/Router.php', 'app/core/Router.php'],
    'Database' => ['app/helpers/Database.php', 'app/core/Database.php'],
    'Controller' => ['app/core/Controller.php'],
    'Model' => ['app/core/Model.php']
];

foreach ($criticalClasses as $className => $possiblePaths) {
    if (!class_exists($className)) {
        $loaded = false;
        foreach ($possiblePaths as $path) {
            $fullPath = __DIR__ . '/../' . $path;
            if (file_exists($fullPath)) {
                require_once $fullPath;
                $loaded = true;
                $loadingSteps[] = "Classe {$className} carregada de {$path}";
                break;
            }
        }
        
        if (!$loaded) {
            $loadingSteps[] = "ERRO: Classe {$className} não encontrada em nenhum dos caminhos: " . implode(', ', $possiblePaths);
        }
    } else {
        $loadingSteps[] = "Classe {$className} já estava carregada";
    }
}

// Carregar e inicializar helpers de otimização
$initHelpersFile = APP_PATH . '/helpers/init_helpers.php';
if (file_exists($initHelpersFile)) {
    require_once $initHelpersFile;
    $loadingSteps[] = "Helpers de otimização inicializados";
}

// Verificar se há uma solicitação de diagnóstico
if (isset($_GET['diagnostico']) || isset($_GET['debug'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>Diagnóstico de Carregamento</h1>";
    echo "<pre>";
    foreach ($loadingSteps as $step) {
        echo htmlspecialchars($step) . "\n";
    }
    
    // Listar classes carregadas
    echo "\nClasses carregadas:\n";
    $loadedClasses = get_declared_classes();
    $appClasses = array_filter($loadedClasses, function($class) {
        return !strpos($class, '\\') && !in_array($class, ['stdClass', 'Exception', 'Error', 'PDO', 'DateTime']);
    });
    echo implode(', ', $appClasses);
    
    echo "</pre>";
    exit;
}

// Verificar se há uma solicitação para servir arquivos estáticos diretamente
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

// TRATAMENTO DE EXCEÇÕES GLOBAL
try {
    // Verificar se a classe Router existe
    if (class_exists('Router')) {
        // Inicializar o roteador
        $router = new Router();
        $router->dispatch();
    } else {
        throw new Exception("Classe Router não encontrada. Verifique a instalação.");
    }
} catch (Exception $e) {
    // Exibir erro de forma amigável
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        echo "<h1>Erro na Inicialização</h1>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<h2>Detalhes Técnicos</h2>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
        
        echo "<h2>Passos de Carregamento</h2>";
        echo "<pre>";
        foreach ($loadingSteps as $step) {
            echo htmlspecialchars($step) . "\n";
        }
        echo "</pre>";
    } else {
        // Em produção, mostrar mensagem genérica
        header("HTTP/1.1 500 Internal Server Error");
        if (defined('VIEWS_PATH') && file_exists(VIEWS_PATH . '/errors/500.php')) {
            include VIEWS_PATH . '/errors/500.php';
        } else {
            echo "<h1>Erro Interno do Servidor</h1>";
            echo "<p>Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.</p>";
        }
    }
}