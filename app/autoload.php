<?php
/**
 * Autoloader personalizado para carregar automaticamente as classes
 * Corrige problemas com classes não encontradas
 */

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
    'Database' => APP_PATH . '/core/Database.php',
    'Router' => APP_PATH . '/core/Router.php',
    'Request' => APP_PATH . '/core/Request.php',
    'Response' => APP_PATH . '/core/Response.php',
    'Security' => APP_PATH . '/helpers/Security.php',
    'Validator' => APP_PATH . '/helpers/Validator.php',
    'ImageHelper' => APP_PATH . '/helpers/ImageHelper.php',
    'ModelValidator' => APP_PATH . '/helpers/ModelValidator.php',
    'QueryOptimizerHelper' => APP_PATH . '/helpers/QueryOptimizerHelper.php',
    'ProductionMonitoringHelper' => APP_PATH . '/helpers/ProductionMonitoringHelper.php',
];

/**
 * Função de autoload
 * 
 * @param string $class Nome da classe a ser carregada
 * @return bool True se a classe foi carregada, false caso contrário
 */
function app_autoload($class) {
    global $classMap;
    
    // Verificar se a classe está no mapa de classes
    if (isset($classMap[$class])) {
        if (file_exists($classMap[$class])) {
            require_once $classMap[$class];
            return true;
        } else {
            // Log para auxiliar na depuração
            error_log("Autoloader: Arquivo {$classMap[$class]} não encontrado para a classe {$class}");
        }
    }
    
    // Verificar diretórios padrão para a classe
    $directories = [
        APP_PATH . '/models/',
        APP_PATH . '/controllers/',
        APP_PATH . '/helpers/',
        APP_PATH . '/core/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    // Log para auxiliar na depuração
    error_log("Autoloader: Classe {$class} não encontrada em nenhum diretório padrão");
    
    return false;
}

// Registrar a função de autoload
spl_autoload_register('app_autoload');

// Carregar diretamente classes essenciais (fallback caso o autoloader falhe)
if (!class_exists('Database')) {
    require_once APP_PATH . '/core/Database.php';
}

if (!class_exists('Model')) {
    require_once APP_PATH . '/core/Model.php';
}

// Log de inicialização
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_log("Autoloader inicializado com " . count($classMap) . " classes mapeadas");
}
