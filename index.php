<?php
/**
 * Taverna da Impressão 3D - Front Controller
 * 
 * Este é o ponto de entrada principal da aplicação.
 * Carrega configurações, inicializa o sistema e roteia requisições.
 * 
 * @version 0.2.4
 */

// Definir o diretório raiz
define('ROOT_PATH', __DIR__);
define('APP_PATH', __DIR__ . '/app');
define('VIEWS_PATH', APP_PATH . '/views');

// Carregar configurações
require_once APP_PATH . '/config/config.php';

// Carregar helpers
require_once APP_PATH . '/lib/helpers.php';

// Carregar classes de segurança
require_once APP_PATH . '/lib/Security/SecurityManager.php';
require_once APP_PATH . '/lib/Security/CsrfProtection.php';
require_once APP_PATH . '/lib/Security/SecurityHeaders.php';

// Iniciar sessão
session_start();

// Aplicar cabeçalhos de segurança
SecurityHeaders::applyAll();

// Processar rotas
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remover a base do URI (para funcionar em subpastas)
$baseFolder = getenv('BASE_FOLDER') ?: '';
if ($baseFolder && strpos($uri, $baseFolder) === 0) {
    $uri = substr($uri, strlen($baseFolder));
}

// Garantir que URI começa com '/'
if (substr($uri, 0, 1) !== '/') {
    $uri = '/' . $uri;
}

// Remover trailing slash, exceto para o URI raiz
if ($uri !== '/' && substr($uri, -1) === '/') {
    $uri = rtrim($uri, '/');
    header('Location: ' . $baseFolder . $uri);
    exit;
}

// Mapear URI para controllador e método
$router = new Router();
$router->dispatch($uri);

// Função para obter a URL base
function base_url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

// Função para obter símbolo da moeda
function getCurrencySymbol() {
    return 'R$';
}
