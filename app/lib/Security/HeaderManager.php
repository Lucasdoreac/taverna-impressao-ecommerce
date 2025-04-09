<?php
/**
 * HeaderManager - Classe para gerenciamento de cabeçalhos HTTP de segurança
 * 
 * Esta classe fornece métodos para configurar e definir cabeçalhos HTTP
 * relacionados à segurança, como Content Security Policy (CSP), HSTS, etc.
 * 
 * @package     App\Lib\Security
 * @version     1.0.0
 * @author      Taverna da Impressão
 */
class HeaderManager {
    
    /**
     * Define os cabeçalhos de segurança HTTP padrão
     * 
     * @return void
     */
    public static function setSecurityHeaders() {
        // Prevenção contra clickjacking
        self::setHeader('X-Frame-Options', 'DENY');
        
        // Prevenção contra MIME sniffing
        self::setHeader('X-Content-Type-Options', 'nosniff');
        
        // Proteção XSS para navegadores antigos (obsoleto em navegadores modernos, mas mantido para compatibilidade)
        self::setHeader('X-XSS-Protection', '1; mode=block');
        
        // Política de referenciador
        self::setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Política de recursos de permissões
        self::setHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');
        
        // Cache-control para contenção de dados sensíveis
        if (isset($_SESSION['user_id'])) {
            self::setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            self::setHeader('Pragma', 'no-cache');
            self::setHeader('Expires', '0');
        }
    }
    
    /**
     * Define o Content Security Policy (CSP)
     * 
     * @param array $customPolicy Política CSP personalizada
     * @return void
     */
    public static function setContentSecurityPolicy(array $customPolicy = []) {
        // Política CSP padrão
        $defaultPolicy = [
            'default-src' => "'self'",
            'script-src' => "'self' https://cdnjs.cloudflare.com",
            'style-src' => "'self' 'unsafe-inline' https://cdnjs.cloudflare.com",
            'img-src' => "'self' data:",
            'font-src' => "'self' https://cdnjs.cloudflare.com",
            'connect-src' => "'self'",
            'frame-src' => "'none'",
            'object-src' => "'none'",
            'base-uri' => "'self'"
        ];
        
        // Mesclar com política personalizada
        $policy = array_merge($defaultPolicy, $customPolicy);
        
        // Construir string da política
        $policyString = '';
        foreach ($policy as $directive => $value) {
            if (!empty($value)) {
                $policyString .= $directive . ' ' . $value . '; ';
            } else {
                $policyString .= $directive . '; ';
            }
        }
        
        // Definir cabeçalho CSP
        self::setHeader('Content-Security-Policy', trim($policyString));
    }
    
    /**
     * Define o HTTP Strict Transport Security (HSTS)
     * 
     * @param int $maxAge Tempo em segundos para o navegador lembrar
     * @param bool $includeSubDomains Incluir subdomínios
     * @param bool $preload Incluir diretiva preload
     * @return void
     */
    public static function setStrictTransportSecurity($maxAge = 31536000, $includeSubDomains = true, $preload = false) {
        // Verificar se estamos em HTTPS
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            return;
        }
        
        // Construir valor do cabeçalho
        $value = "max-age=$maxAge";
        
        if ($includeSubDomains) {
            $value .= '; includeSubDomains';
        }
        
        if ($preload) {
            $value .= '; preload';
        }
        
        // Definir cabeçalho HSTS
        self::setHeader('Strict-Transport-Security', $value);
    }
    
    /**
     * Define o cabeçalho Feature-Policy (ou Permissions-Policy)
     * 
     * @param array $features Lista de features e permissões
     * @return void
     */
    public static function setFeaturePolicy(array $features = []) {
        // Política de features padrão
        $defaultFeatures = [
            'camera' => '()',
            'microphone' => '()',
            'geolocation' => '()',
            'payment' => '()',
            'usb' => '()',
            'magnetometer' => '()',
            'accelerometer' => '()',
            'gyroscope' => '()',
            'speaker' => '()',
            'vibrate' => '()',
            'fullscreen' => "'self'",
            'sync-xhr' => "'self'"
        ];
        
        // Mesclar com features personalizadas
        $policy = array_merge($defaultFeatures, $features);
        
        // Construir string da política
        $policyString = '';
        foreach ($policy as $feature => $value) {
            $policyString .= $feature . '=' . $value . ', ';
        }
        
        // Remover vírgula final
        $policyString = rtrim($policyString, ', ');
        
        // Definir cabeçalho Feature-Policy e seu substituto moderno Permissions-Policy
        self::setHeader('Feature-Policy', $policyString);
        self::setHeader('Permissions-Policy', $policyString);
    }
    
    /**
     * Define cabeçalhos para prevenção de cache
     * 
     * @return void
     */
    public static function setNoCacheHeaders() {
        self::setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        self::setHeader('Pragma', 'no-cache');
        self::setHeader('Expires', '0');
    }
    
    /**
     * Define cabeçalhos para prevenção de clicjacking
     * 
     * @param string $mode Modo de proteção (DENY, SAMEORIGIN, ALLOW-FROM)
     * @param string $allowFrom Domínio permitido (para ALLOW-FROM)
     * @return void
     */
    public static function setFrameOptions($mode = 'DENY', $allowFrom = '') {
        $value = $mode;
        
        if ($mode === 'ALLOW-FROM' && !empty($allowFrom)) {
            $value .= ' ' . $allowFrom;
        }
        
        self::setHeader('X-Frame-Options', $value);
    }
    
    /**
     * Define cabeçalhos para downloads de arquivos
     * 
     * @param string $filename Nome do arquivo
     * @param string $contentType Tipo de conteúdo (MIME type)
     * @param int $contentLength Tamanho do conteúdo em bytes
     * @param bool $inline Se deve ser exibido no navegador (true) ou baixado (false)
     * @return void
     */
    public static function setDownloadHeaders($filename, $contentType, $contentLength, $inline = false) {
        // Sanitizar nome do arquivo
        $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
        
        // Definir cabeçalhos para download
        self::setHeader('Content-Type', $contentType);
        self::setHeader('Content-Length', $contentLength);
        
        $disposition = $inline ? 'inline' : 'attachment';
        self::setHeader('Content-Disposition', $disposition . '; filename="' . $filename . '"');
        
        // Prevenção de cache para conteúdo sensível
        self::setNoCacheHeaders();
    }
    
    /**
     * Define o cabeçalho Access-Control-Allow-Origin para CORS
     * 
     * @param string|array $allowedOrigins Origens permitidas
     * @return void
     */
    public static function setCorsHeaders($allowedOrigins = '*') {
        // Se for um array de origens permitidas
        if (is_array($allowedOrigins)) {
            $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
            
            if (in_array($origin, $allowedOrigins)) {
                self::setHeader('Access-Control-Allow-Origin', $origin);
                self::setHeader('Vary', 'Origin');
            }
        } else {
            // Permitir qualquer origem (não recomendado para produção)
            self::setHeader('Access-Control-Allow-Origin', $allowedOrigins);
        }
        
        // Métodos HTTP permitidos
        self::setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        
        // Cabeçalhos permitidos
        self::setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        
        // Permitir credenciais
        self::setHeader('Access-Control-Allow-Credentials', 'true');
        
        // Tempo de cache da preflight
        self::setHeader('Access-Control-Max-Age', '86400'); // 24 horas
    }
    
    /**
     * Define o cabeçalho para um redirecionamento HTTP
     * 
     * @param string $url URL de destino
     * @param int $statusCode Código de status HTTP (301, 302, 303, 307, 308)
     * @return void
     */
    public static function setRedirectHeader($url, $statusCode = 302) {
        // Sanitizar URL para prevenir redirecionamentos maliciosos
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        // Verificar se a URL é válida
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            // Se não for válida, redirecionar para a página inicial
            $url = '/';
        }
        
        // Definir código de status
        $statusTexts = [
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect'
        ];
        
        if (!isset($statusTexts[$statusCode])) {
            $statusCode = 302;
        }
        
        // Definir cabeçalho de status
        self::setResponseCode($statusCode);
        
        // Definir cabeçalho de localização
        self::setHeader('Location', $url);
    }
    
    /**
     * Define o código de resposta HTTP
     * 
     * @param int $code Código de status HTTP
     * @return void
     */
    public static function setResponseCode($code) {
        http_response_code($code);
    }
    
    /**
     * Define um cabeçalho HTTP se ainda não tiver sido enviado
     * 
     * @param string $name Nome do cabeçalho
     * @param string $value Valor do cabeçalho
     * @param bool $replace Se deve substituir cabeçalhos existentes
     * @return void
     */
    private static function setHeader($name, $value, $replace = true) {
        if (!headers_sent()) {
            header("$name: $value", $replace);
        }
    }
    
    /**
     * Remove um cabeçalho HTTP se ainda não tiver sido enviado
     * 
     * @param string $name Nome do cabeçalho
     * @return void
     */
    public static function removeHeader($name) {
        if (!headers_sent()) {
            header_remove($name);
        }
    }
    
    /**
     * Verifica se um cabeçalho específico já foi enviado
     * 
     * @param string $name Nome do cabeçalho
     * @return bool Verdadeiro se o cabeçalho já foi enviado
     */
    public static function headerExists($name) {
        $headers = headers_list();
        
        foreach ($headers as $header) {
            if (stripos($header, $name . ':') === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Obtém todos os cabeçalhos enviados
     * 
     * @return array Lista de cabeçalhos
     */
    public static function getAllHeaders() {
        return headers_list();
    }
    
    /**
     * Limpa todos os cabeçalhos se ainda não tiverem sido enviados
     * 
     * @return void
     */
    public static function clearAllHeaders() {
        if (!headers_sent()) {
            header_remove();
        }
    }
}
