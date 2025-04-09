<?php
/**
 * Arquivo de configuração - Taverna da Impressão 3D
 * 
 * Este é um arquivo modelo. Copie para config.php e configure conforme seu ambiente.
 * NÃO VERSIONE o arquivo config.php
 */

// Ambiente de execução ('development', 'production')
define('ENVIRONMENT', 'development');

// Definições de URLs
if (ENVIRONMENT === 'development') {
    // Ambiente local
    define('BASE_URL', 'http://localhost/taverna/');
    define('SITE_TITLE', 'Taverna da Impressão 3D - DEV');
    define('DEBUG', true);
} else {
    // Ambiente de produção (Hostinger)
    define('BASE_URL', 'https://darkblue-cattle-647559.hostingersite.com/');
    define('SITE_TITLE', 'Taverna da Impressão 3D');
    define('DEBUG', false);
}

// Configurações de banco de dados
if (ENVIRONMENT === 'development') {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'taverna_dev');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    define('DB_HOST', 'localhost'); // Normalmente é localhost no Hostinger
    define('DB_NAME', 'u123456789_taverna'); // Substitua pelo nome do banco Hostinger
    define('DB_USER', 'u123456789_tavuser'); // Substitua pelo usuário do banco Hostinger
    define('DB_PASS', '********'); // Substitua pela senha correta
}

// Configurações de e-mail (Hostinger SMTP)
if (ENVIRONMENT === 'development') {
    define('MAIL_HOST', 'smtp.mailtrap.io'); // Para testes em desenvolvimento
    define('MAIL_PORT', 2525);
    define('MAIL_USER', 'test_user');
    define('MAIL_PASS', 'test_pass');
} else {
    define('MAIL_HOST', 'smtp.hostinger.com');
    define('MAIL_PORT', 587);
    define('MAIL_USER', 'contato@seudominio.com');
    define('MAIL_PASS', '********');
}

// Configurações de segurança
define('CSRF_TOKEN_EXPIRY', 3600); // Tempo de validade do token CSRF em segundos
define('SESSION_EXPIRY', 86400); // Tempo de sessão do usuário em segundos (24 horas)
define('PASSWORD_HASH_COST', 12); // Custo do algoritmo de hash bcrypt (mais alto = mais seguro, mas mais lento)

// Configurações de upload
define('UPLOAD_DIR', __DIR__ . '/../../uploads/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_UPLOAD_EXTENSIONS', ['stl', 'obj', 'zip', 'jpg', 'jpeg', 'png', 'gif']);

// Configurações de cache
define('CACHE_ENABLED', ENVIRONMENT === 'production');
define('CACHE_DIR', __DIR__ . '/../../cache/');
define('CACHE_EXPIRY', 3600); // Tempo de expiração do cache em segundos

// Configurações de erro
define('ERROR_LOG', __DIR__ . '/../../logs/error.log');

/**
 * Função auxiliar para carregar configuração
 */
function config($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

/**
 * Configurar tratamento de erros baseado no ambiente
 */
if (config('DEBUG', false)) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    ini_set('log_errors', 1);
    ini_set('error_log', config('ERROR_LOG'));
}
