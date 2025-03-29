<?php
// Configurações globais da aplicação
define('ENVIRONMENT', 'development'); // 'development' ou 'production'
define('DISPLAY_ERRORS', true);

// Configurar exibição de erros com base no ambiente
if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}

// Definir base URL
$base_url = 'https://darkblue-cattle-647559.hostingersite.com/';
define('BASE_URL', $base_url);

// Definir caminhos
define('ROOT_PATH', dirname(dirname(__DIR__)));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEWS_PATH', APP_PATH . '/views');
define('UPLOADS_PATH', ROOT_PATH . '/public/uploads');

// Configurações do banco de dados para Hostinger
define('DB_HOST', '127.0.0.1:3306');
define('DB_NAME', 'u135851624_taverna');
define('DB_USER', 'u135851624_teverna');
define('DB_PASS', '#Taverna1');

// Configurações de e-mail
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_USER', 'contato@tavernaimpressao.com.br');
define('SMTP_PASS', '');
define('SMTP_PORT', 587);

// Configurações da loja
define('STORE_NAME', 'TAVERNA DA IMPRESSÃO');
define('STORE_EMAIL', 'contato@tavernaimpressao.com.br');
define('STORE_PHONE', '(00) 0000-0000');

// Moeda - usar defined() para evitar redefinição
if (!defined('CURRENCY')) define('CURRENCY', 'BRL');
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', 'R$');

// Configurações adicionais
define('ITEMS_PER_PAGE', 12);
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB

// Inicializar sessão se já não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função auxiliar para debug (apenas em desenvolvimento)
function debug($var, $die = false) {
    if (ENVIRONMENT === 'development') {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
        if ($die) die();
    }
}

// Carregar o Logger para habilitar a função app_log
require_once APP_PATH . '/helpers/Logger.php';
