<?php
// Definir ambiente
define('ENVIRONMENT', 'production'); // 'development' ou 'production'

// Configurações de exibição de erros baseadas no ambiente
if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    define('DISPLAY_ERRORS', true);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    define('DISPLAY_ERRORS', false);
}

// Configurar log de erros
ini_set('log_errors', 1);
ini_set('error_log', dirname(dirname(__DIR__)) . '/logs/php-errors.log');

// Configurações da URL base
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseDir = dirname($scriptName);
$baseDir = $baseDir !== '/' ? $baseDir : '';

define('BASE_URL', "{$protocol}://{$host}{$baseDir}/");

// Configurações de caminhos
define('ROOT_PATH', dirname(dirname(__DIR__)));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEWS_PATH', APP_PATH . '/views');
define('UPLOADS_PATH', ROOT_PATH . '/public/uploads');
define('LOGS_PATH', ROOT_PATH . '/logs');

// Garantir que o diretório de logs exista
if (!is_dir(LOGS_PATH)) {
    mkdir(LOGS_PATH, 0755, true);
}

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'taverna_impressao');
define('DB_USER', 'root'); // Alterar em produção
define('DB_PASS', '');     // Alterar em produção

// Opções do PDO para melhor performance e segurança
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_PERSISTENT => false,
    PDO::MYSQL_ATTR_FOUND_ROWS => true,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);

// Configurações de e-mail
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_USER', 'user@example.com');
define('SMTP_PASS', 'password');
define('SMTP_PORT', 587);

// Configurações da loja
define('STORE_NAME', 'TAVERNA DA IMPRESSÃO');
define('STORE_EMAIL', 'contato@tavernaimpressao.com.br');
define('STORE_PHONE', '(00) 0000-0000');
define('ITEMS_PER_PAGE', 12);
define('CURRENCY_SYMBOL', 'R$');

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if ($protocol === 'https') {
    ini_set('session.cookie_secure', 1);
}
session_start();

// Função para registrar logs da aplicação
if (!function_exists('app_log')) {
    function app_log($message, $level = 'info') {
        $logFile = LOGS_PATH . '/app-' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    }
}

// Tempo máximo de execução para evitar travamento de scripts
set_time_limit(30);

// Configuração de timezone
date_default_timezone_set('America/Sao_Paulo');

// Carregamento automático de classes
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
    
    if (ENVIRONMENT === 'development') {
        app_log("Classe não encontrada: {$class}", 'error');
    }
});