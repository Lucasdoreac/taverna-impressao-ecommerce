<?php
/**
 * Arquivo de inicialização para bibliotecas de segurança
 * 
 * Este arquivo inicializa e configura as proteções de segurança globais
 * para toda a aplicação. Deve ser incluído no início da execução.
 * 
 * @package     App\Lib\Security
 * @version     1.0.0
 * @author      Taverna da Impressão
 */

// Verificar se as classes de segurança já foram incluídas
if (!class_exists('SecurityManager')) {
    require_once __DIR__ . '/SecurityManager.php';
}

if (!class_exists('CsrfProtection')) {
    require_once __DIR__ . '/CsrfProtection.php';
}

if (!class_exists('HeaderManager')) {
    require_once __DIR__ . '/HeaderManager.php';
}

// Inicializar o gerenciador de segurança com todas as proteções ativas
SecurityManager::init([
    'csrf' => true,
    'contentSecurityPolicy' => true,
    'errorHandler' => true
]);

// Definir a política CSP personalizada para a aplicação
$customCspPolicy = [
    'default-src' => "'self'",
    'script-src' => "'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
    'style-src' => "'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com",
    'img-src' => "'self' data: https://via.placeholder.com",
    'font-src' => "'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com",
    'connect-src' => "'self'",
    'frame-src' => "'self'",
    'media-src' => "'self'",
    'object-src' => "'none'",
    'base-uri' => "'self'",
    'form-action' => "'self'",
    'frame-ancestors' => "'self'",
    'upgrade-insecure-requests' => "",
    'block-all-mixed-content' => ""
];

// Definir cabeçalhos de segurança adicionais
HeaderManager::setSecurityHeaders();

// Configurar política CSP personalizada
HeaderManager::setContentSecurityPolicy($customCspPolicy);

// Configurar HSTS (HTTP Strict Transport Security)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    HeaderManager::setStrictTransportSecurity(31536000, true); // 1 ano com includeSubDomains
}

// Registrar handlers de erro seguros
SecurityManager::registerErrorHandlers();

// Inicializar proteção CSRF
CsrfProtection::init();

// Inicializar proteção contra força bruta
if (class_exists('BruteForceProtection')) {
    require_once __DIR__ . '/BruteForceProtection.php';
    BruteForceProtection::init();
}

// Inicializar sistema de logs de segurança
if (class_exists('SecurityLogger')) {
    require_once __DIR__ . '/SecurityLogger.php';
    SecurityLogger::init();
}

// Se as permissões de acesso não estiverem inicializadas e o usuário estiver logado
if (class_exists('AccessControl') && isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/AccessControl.php';
    AccessControl::initUserPermissions($_SESSION['user_id']);
}
