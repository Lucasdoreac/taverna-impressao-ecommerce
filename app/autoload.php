<?php
/**
 * Autoloader personalizado para carregar automaticamente as classes
 * Corrige problemas com classes não encontradas
 */

// Log para diagnóstico
error_log("Inicializando autoloader - " . date('Y-m-d H:i:s'));
if (defined('APP_PATH')) {
    error_log("APP_PATH definido como: " . APP_PATH);
} else {
    error_log("ATENÇÃO: APP_PATH não está definido no autoloader!");
    // Definir APP_PATH localmente caso não esteja definido globalmente
    define('APP_PATH', dirname(__FILE__)); // Define app/autoload.php parent directory
}

// Mapear classes a caminhos de arquivo
$classMap = [
    // Models
    'ProductModel' => APP_PATH . '/models/ProductModel.php',
    'CategoryModel' => APP_PATH . '/models/CategoryModel.php',
    'UserModel' => APP_PATH . '/models/UserModel.php',
    'OrderModel' => APP_PATH . '/models/OrderModel.php',
    'FilamentModel' => APP_PATH . '/models/FilamentModel.php',
    'PrintJobModel' => APP_PATH . '/models/PrintJobModel.php',
    'CartModel' => APP_PATH . '/models/CartModel.php',
    'NotificationPreferenceModel' => APP_PATH . '/models/NotificationPreferenceModel.php',
    'Model' => APP_PATH . '/core/Model.php',
    
    // Controllers
    'Controller' => APP_PATH . '/core/Controller.php',
    'ProductController' => APP_PATH . '/controllers/ProductController.php',
    'CategoryController' => APP_PATH . '/controllers/CategoryController.php',
    'AuthController' => APP_PATH . '/controllers/AuthController.php',
    'UserController' => APP_PATH . '/controllers/UserController.php',
    'AdminController' => APP_PATH . '/controllers/AdminController.php',
    'CartController' => APP_PATH . '/controllers/CartController.php',
    'CustomizationController' => APP_PATH . '/controllers/CustomizationController.php',
    'NotificationPreferenceController' => APP_PATH . '/controllers/NotificationPreferenceController.php',
    
    // Helpers e Utilitários
    'Database' => APP_PATH . '/helpers/Database.php',
    'Router' => APP_PATH . '/helpers/Router.php',
    'Request' => APP_PATH . '/core/Request.php',
    'Response' => APP_PATH . '/core/Response.php',
    'Security' => APP_PATH . '/helpers/Security.php',
    'Validator' => APP_PATH . '/helpers/Validator.php',
    'ImageHelper' => APP_PATH . '/helpers/ImageHelper.php',
    'ModelValidator' => APP_PATH . '/helpers/ModelValidator.php',
    'QueryOptimizerHelper' => APP_PATH . '/helpers/QueryOptimizerHelper.php',
    'ProductionMonitoringHelper' => APP_PATH . '/helpers/ProductionMonitoringHelper.php',
];

// Verificar existência de arquivos críticos
$criticalClasses = ['Controller', 'Model', 'Database'];
foreach ($criticalClasses as $class) {
    if (isset($classMap[$class])) {
        if (file_exists($classMap[$class])) {
            error_log("OK: Arquivo '{$classMap[$class]}' para a classe '{$class}' existe");
        } else {
            error_log("ERRO: Arquivo '{$classMap[$class]}' para a classe '{$class}' NÃO existe!");
            
            // Tentar caminhos alternativos para classes críticas
            if ($class === 'Controller' || $class === 'Model') {
                $altPath = dirname(APP_PATH) . "/app/core/{$class}.php";
                if (file_exists($altPath)) {
                    error_log("Encontrado caminho alternativo para {$class}: {$altPath}");
                    $classMap[$class] = $altPath;
                }
            }
        }
    }
}

/**
 * Função de autoload
 * 
 * @param string $class Nome da classe a ser carregada
 * @return bool True se a classe foi carregada, false caso contrário
 */
function app_autoload($class) {
    global $classMap;
    
    // Log para diagnóstico
    error_log("Tentando carregar classe: " . $class);
    
    // Verificar se a classe está no mapa de classes
    if (isset($classMap[$class])) {
        $filePath = realpath($classMap[$class]);
        if ($filePath !== false && file_exists($filePath) && strpos($filePath, APP_PATH) === 0) {
            require_once $filePath;
            error_log("Classe {$class} carregada com sucesso de {$filePath}");
            return true;
        } else {
            error_log("Autoloader: Arquivo {$classMap[$class]} não encontrado ou inválido para a classe {$class}");
        }
    }
    
    // Verificar diretórios padrão para a classe
    $directories = [
        APP_PATH . '/models/',
        APP_PATH . '/controllers/',
        APP_PATH . '/helpers/',
        APP_PATH . '/core/',
        // CORREÇÃO: Adicionar caminhos alternativos para ambiente de produção
        dirname(APP_PATH) . '/app/models/',
        dirname(APP_PATH) . '/app/controllers/',
        dirname(APP_PATH) . '/app/helpers/',
        dirname(APP_PATH) . '/app/core/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        $filePath = realpath($file);
        if ($filePath !== false && file_exists($filePath) && strpos($filePath, APP_PATH) === 0) {
            require_once $filePath;
            error_log("Classe {$class} carregada de diretório padrão: {$filePath}");
            return true;
        }
    }
    
    error_log("Autoloader: Classe {$class} não encontrada em nenhum diretório padrão");
    
    return false;
}


// Registrar a função de autoload
spl_autoload_register('app_autoload');

// Carregar diretamente classes essenciais (fallback caso o autoloader falhe)
// CORREÇÃO: Verificar múltiplos caminhos possíveis para as classes cruciais
$essentialClasses = [
    'Database' => [
        APP_PATH . '/helpers/Database.php',
        APP_PATH . '/core/Database.php',
        dirname(APP_PATH) . '/app/helpers/Database.php',
        dirname(APP_PATH) . '/app/core/Database.php'
    ],
    'Model' => [
        APP_PATH . '/core/Model.php',
        dirname(APP_PATH) . '/app/core/Model.php'
    ],
    'Controller' => [
        APP_PATH . '/core/Controller.php',
        dirname(APP_PATH) . '/app/core/Controller.php'
    ]
];

foreach ($essentialClasses as $class => $paths) {
    if (!class_exists($class)) {
        foreach ($paths as $path) {
            $sanitizedPath = realpath($path);
            if ($sanitizedPath !== false && file_exists($sanitizedPath) && strpos($sanitizedPath, __DIR__) === 0) {
                require_once $sanitizedPath;
                error_log("Classe essencial {$class} carregada manualmente de {$sanitizedPath}");
                break;
            }
        }

    }
}

// Log de inicialização
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_log("Autoloader inicializado com " . count($classMap) . " classes mapeadas");
}

// Log das classes carregadas após o autoload 
$loadedClasses = get_declared_classes();
$appClasses = array_filter($loadedClasses, function($class) {
    return !strpos($class, '\\') && !in_array($class, ['stdClass', 'Exception', 'Error', 'PDO', 'DateTime']);
});
error_log("Classes disponíveis após autoload: " . implode(', ', $appClasses));
