<?php
/**
 * SecurityHeaders - Aplicação de cabeçalhos de segurança HTTP
 * 
 * @package App\Lib\Security
 * @category Security
 * @author Taverna da Impressão 3D Dev Team
 */

namespace App\Lib\Security;

class SecurityHeaders
{
    /**
     * Aplica todos os cabeçalhos de segurança padrão
     * 
     * @param array $options Opções adicionais
     * @return void
     */
    public static function apply(array $options = []): void
    {
        $config = array_merge(self::getDefaultConfig(), $options);
        
        // Content Security Policy (CSP)
        if ($config['enable_csp']) {
            self::applyCSP($config['csp_policy']);
        }
        
        // HTTP Strict Transport Security (HSTS)
        if ($config['enable_hsts']) {
            header('Strict-Transport-Security: max-age=' . $config['hsts_max_age'] . '; includeSubDomains');
        }
        
        // X-Frame-Options (proteção contra clickjacking)
        header('X-Frame-Options: ' . $config['x_frame_options']);
        
        // X-Content-Type-Options (evita MIME-sniffing)
        header('X-Content-Type-Options: nosniff');
        
        // X-XSS-Protection (mitigação de XSS para browsers legados)
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer-Policy
        header('Referrer-Policy: ' . $config['referrer_policy']);
        
        // Permissions-Policy (antigo Feature-Policy)
        if ($config['enable_permissions_policy']) {
            header('Permissions-Policy: ' . $config['permissions_policy']);
        }
        
        // Cache control para conteúdo não-estático
        if ($config['no_cache']) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
    
    /**
     * Aplica cabeçalho Content-Security-Policy
     * 
     * @param array $policy Política CSP
     * @return void
     */
    private static function applyCSP(array $policy): void
    {
        $cspHeader = '';
        
        foreach ($policy as $directive => $value) {
            $cspHeader .= $directive . ' ' . $value . '; ';
        }
        
        header('Content-Security-Policy: ' . trim($cspHeader));
    }
    
    /**
     * Retorna configurações padrão para os cabeçalhos de segurança
     * 
     * @return array Configurações
     */
    private static function getDefaultConfig(): array
    {
        return [
            'enable_csp' => true,
            'csp_policy' => [
                'default-src' => "'self'",
                'script-src' => "'self' 'nonce-" . self::generateNonce() . "'",
                'style-src' => "'self' 'unsafe-inline'",
                'img-src' => "'self' data:",
                'connect-src' => "'self'",
                'font-src' => "'self'",
                'object-src' => "'none'",
                'media-src' => "'self'",
                'frame-src' => "'self'",
                'base-uri' => "'self'",
                'form-action' => "'self'"
            ],
            'enable_hsts' => true,
            'hsts_max_age' => 31536000, // 1 ano
            'x_frame_options' => 'SAMEORIGIN',
            'referrer_policy' => 'strict-origin-when-cross-origin',
            'enable_permissions_policy' => true,
            'permissions_policy' => 'camera=(), microphone=(), geolocation=(self), payment=()',
            'no_cache' => true
        ];
    }
    
    /**
     * Gera um nonce criptograficamente seguro para uso em CSP
     * 
     * @return string Nonce gerado
     */
    public static function generateNonce(): string
    {
        $nonce = bin2hex(random_bytes(16));
        
        // Armazenar nonce na sessão para verificação
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['csp_nonce'] = $nonce;
        }
        
        return $nonce;
    }
    
    /**
     * Obtém o nonce CSP atual para uso em tags script ou style
     * 
     * @return string|null Nonce ou null se não disponível
     */
    public static function getNonce(): ?string
    {
        return $_SESSION['csp_nonce'] ?? null;
    }
}
