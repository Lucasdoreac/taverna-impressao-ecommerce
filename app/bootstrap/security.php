<?php
/**
 * Carregar e aplicar configurações de segurança
 * 
 * Este arquivo é incluído no início da aplicação para aplicar
 * configurações de segurança, como cabeçalhos HTTP
 */

// Carregar classes de segurança
require_once APP_PATH . '/lib/Security/SecurityHeaders.php';
require_once APP_PATH . '/lib/Security/SecurityManager.php';
require_once APP_PATH . '/lib/Security/CsrfProtection.php';

// Aplicar cabeçalhos de segurança
SecurityHeaders::applyAll();

// Configurações específicas do ambiente
if (defined('ENVIRONMENT')) {
    if (ENVIRONMENT === 'development') {
        // Em desenvolvimento, permitir fontes locais para facilitar debug
        SecurityHeaders::updateCspDirective('script-src', ["'self'", "'unsafe-inline'", "'unsafe-eval'", "https://cdnjs.cloudflare.com", "http://localhost:*"]);
        SecurityHeaders::updateCspDirective('connect-src', ["'self'", "http://localhost:*"]);
    } elseif (ENVIRONMENT === 'production') {
        // Em produção, aplicar políticas mais restritivas
        // Por exemplo, desativar unsafe-inline para scripts, se possível
    }
}

// Configurações para o Hostinger (ambiente de produção)
if (strpos($_SERVER['HTTP_HOST'] ?? '', 'hostinger') !== false) {
    // Verificar se estamos no Hostinger
    
    // Ajustar CSP para recursos específicos do Hostinger, se necessário
    SecurityHeaders::updateCspDirective('img-src', ["'self'", "data:", "*.hostinger.com"]);
    
    // Habilitar HSTS para melhor segurança
    // Security::setHstsEnabled(true);
}

// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
