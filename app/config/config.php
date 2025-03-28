<?php
// Configurações globais da aplicação
define('BASE_URL', 'https://darkblue-cattle-647559.hostingersite.com/');
define('ROOT_PATH', dirname(dirname(__DIR__)));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEWS_PATH', APP_PATH . '/views');
define('UPLOADS_PATH', ROOT_PATH . '/public/uploads');
define('LOG_PATH', ROOT_PATH . '/logs');

// Configurações do banco de dados para Hostinger
// Usando conexão TCP/IP explícita com porta 3306
define('DB_HOST', '127.0.0.1:3306');
define('DB_NAME', 'u135851624_taverna');
define('DB_USER', 'u135851624_teverna'); // Corrigido para teverna com "v"
define('DB_PASS', '#Taverna1'); // Senha completa correta

// Adicionando opções de PDO específicas para Hostinger
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 10
]);

// Configurações de e-mail
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_USER', 'contato@tavernaimpressao.com.br');
define('SMTP_PASS', 'sua-senha-aqui'); // Alterar para senha real em produção
define('SMTP_PORT', 587);

// Configurações da loja
define('STORE_NAME', 'TAVERNA DA IMPRESSÃO');
define('STORE_EMAIL', 'contato@tavernaimpressao.com.br');
define('STORE_PHONE', '(21) 98765-4321');

// Configurações de segurança
define('CSRF_TOKEN_NAME', 'taverna_token');
define('SESSION_NAME', 'TAVERNA_SESSION');
define('COOKIE_DOMAIN', '.hostingersite.com');
define('COOKIE_SECURE', true);
define('COOKIE_HTTP_ONLY', true);

// Configurações de ambiente
define('ENVIRONMENT', 'development'); // Alterado para 'development' temporariamente
define('DISPLAY_ERRORS', true);      // Habilitado temporariamente para debug
define('LOG_ERRORS', true);

// Configuração do diretório de logs
if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

// Inicializar sessão com configuração explícita para compatibilidade
ini_set('session.cookie_domain', COOKIE_DOMAIN);
ini_set('session.cookie_secure', COOKIE_SECURE);
ini_set('session.cookie_httponly', COOKIE_HTTP_ONLY);
session_name(SESSION_NAME);
session_start();

// Configurar exibição de erros conforme ambiente
if (DISPLAY_ERRORS) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Função de log aprimorada
function app_log($message, $level = 'info') {
    if (!LOG_ERRORS && $level != 'error') return;
    
    $log_file = LOG_PATH . '/app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$level] $message" . PHP_EOL;
    
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
    
    // Para logs de erro, registrar também no log de erro do PHP
    if ($level == 'error') {
        error_log($message);
    }
}

// Registrar inicialização da aplicação
app_log('===== Aplicação Inicializada =====');
app_log('Ambiente: ' . ENVIRONMENT);
app_log('URI: ' . ($_SERVER['REQUEST_URI'] ?? 'Não definido'));
