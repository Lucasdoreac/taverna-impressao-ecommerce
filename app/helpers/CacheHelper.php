<?php
/**
 * CacheHelper - Classe para gerenciamento de cache de recursos estáticos
 * 
 * Este helper fornece métodos para:
 * - Configuração de cabeçalhos HTTP para cache
 * - Geração e validação de ETags
 * - Controle de validade de cache
 * - Interface com sistema de armazenamento em cache
 */
class CacheHelper {
    /**
     * Tempo padrão de cache em segundos (1 semana)
     */
    private static $defaultCacheTime = 604800;
    
    /**
     * Tempo de cache para diferentes tipos de arquivos
     */
    private static $cacheTimeByType = [
        'css' => 604800,      // 1 semana
        'js'  => 604800,      // 1 semana
        'jpg' => 2592000,     // 30 dias
        'jpeg' => 2592000,    // 30 dias
        'png' => 2592000,     // 30 dias
        'gif' => 2592000,     // 30 dias
        'svg' => 2592000,     // 30 dias
        'ico' => 2592000,     // 30 dias
        'woff' => 31536000,   // 1 ano
        'woff2' => 31536000,  // 1 ano
        'ttf' => 31536000,    // 1 ano
        'eot' => 31536000,    // 1 ano
        'otf' => 31536000     // 1 ano
    ];

    /**
     * Tipos de conteúdo para os diferentes arquivos
     */
    private static $contentTypeByExt = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
        'otf' => 'font/otf'
    ];
    
    /**
     * Define se estamos em modo de produção
     */
    private static $productionMode = null;
    
    /**
     * Inicializa o helper
     */
    public static function init() {
        // Verificar se estamos em modo de produção
        if (self::$productionMode === null) {
            self::$productionMode = defined('ENVIRONMENT') && ENVIRONMENT === 'production';
        }
    }
    
    /**
     * Configura cabeçalhos HTTP para cache de arquivos estáticos
     * 
     * @param string $filePath Caminho do arquivo
     * @param int $cacheTime Tempo de cache em segundos (opcional)
     * @return bool True se os cabeçalhos foram configurados, false caso contrário
     */
    public static function setCacheHeaders($filePath, $cacheTime = null) {
        self::init();
        
        // Se não estamos em modo de produção, desabilitar cache
        if (!self::$productionMode) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: Mon, 01 Jan 1990 00:00:00 GMT');
            return true;
        }
        
        if (!file_exists($filePath)) {
            return false;
        }
        
        // Obter extensão do arquivo
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // Determinar tempo de cache
        if ($cacheTime === null) {
            $cacheTime = isset(self::$cacheTimeByType[$ext]) 
                ? self::$cacheTimeByType[$ext] 
                : self::$defaultCacheTime;
        }
        
        // Definir tipo de conteúdo
        if (isset(self::$contentTypeByExt[$ext])) {
            header('Content-Type: ' . self::$contentTypeByExt[$ext]);
        }
        
        // Gerar ETag baseado no conteúdo e última modificação
        $lastModified = filemtime($filePath);
        $etagSource = $lastModified . fileinode($filePath) . filesize($filePath);
        $etag = '"' . md5($etagSource) . '"';
        
        // Verificar se o cliente tem uma versão atualizada (If-None-Match)
        $ifNoneMatch = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : false;
        if ($ifNoneMatch === $etag) {
            // Cliente já tem a versão mais recente
            header('HTTP/1.1 304 Not Modified');
            header('ETag: ' . $etag);
            header('Cache-Control: public, max-age=' . $cacheTime);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT');
            exit;
        }
        
        // Definir cabeçalhos de cache
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        header('ETag: ' . $etag);
        header('Cache-Control: public, max-age=' . $cacheTime);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheTime) . ' GMT');
        
        return true;
    }
    
    /**
     * Verifica se um cache está válido com base no If-Modified-Since
     * 
     * @param string $filePath Caminho do arquivo
     * @return bool True se o cache for válido (saída com 304), false caso contrário
     */
    public static function checkModifiedSince($filePath) {
        self::init();
        
        // Se não estamos em modo de produção, sempre retornar falso
        if (!self::$productionMode || !file_exists($filePath)) {
            return false;
        }
        
        $lastModified = filemtime($filePath);
        $ifModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) 
            ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) 
            : false;
        
        if ($ifModifiedSince && $lastModified <= $ifModifiedSince) {
            // Arquivo não foi modificado desde a última solicitação
            header('HTTP/1.1 304 Not Modified');
            exit;
        }
        
        return false;
    }
    
    /**
     * Serve um arquivo estático com cabeçalhos de cache apropriados
     * 
     * @param string $filePath Caminho do arquivo
     * @param int $cacheTime Tempo de cache em segundos (opcional)
     * @return bool True se o arquivo foi servido, false caso contrário
     */
    public static function serveStaticFile($filePath, $cacheTime = null) {
        // Verificar se o arquivo existe
        if (!file_exists($filePath)) {
            return false;
        }
        
        // Verificar If-Modified-Since
        self::checkModifiedSince($filePath);
        
        // Configurar cabeçalhos de cache
        self::setCacheHeaders($filePath, $cacheTime);
        
        // Servir o arquivo
        readfile($filePath);
        exit;
    }
    
    /**
     * Gera uma URL com timestamp para controle de cache
     * 
     * @param string $url URL original
     * @param string $filePath Caminho do arquivo real (opcional)
     * @return string URL com parâmetro de versão
     */
    public static function getVersionedUrl($url, $filePath = null) {
        self::init();
        
        // Se não estamos em modo de produção, usar timestamp atual
        if (!self::$productionMode) {
            return $url . (strpos($url, '?') === false ? '?' : '&') . 'v=' . time();
        }
        
        // Se temos o caminho do arquivo, usar última modificação
        if ($filePath && file_exists($filePath)) {
            $version = filemtime($filePath);
        } else {
            // Usar versão definida globalmente ou data atual
            $version = defined('ASSETS_VERSION') ? ASSETS_VERSION : date('YmdHi');
        }
        
        return $url . (strpos($url, '?') === false ? '?' : '&') . 'v=' . $version;
    }
    
    /**
     * Define o modo de produção
     * 
     * @param boolean $mode Modo de produção
     */
    public static function setProductionMode($mode) {
        self::$productionMode = (bool) $mode;
    }
    
    /**
     * Define um tempo de cache personalizado para um tipo de arquivo
     * 
     * @param string $fileType Tipo de arquivo (extensão)
     * @param int $cacheTime Tempo de cache em segundos
     */
    public static function setCacheTimeForType($fileType, $cacheTime) {
        self::$cacheTimeByType[strtolower($fileType)] = (int) $cacheTime;
    }
    
    /**
     * Gera as regras para o .htaccess para habilitar cache de navegador
     * 
     * @return string Regras para o .htaccess
     */
    public static function generateHtaccessRules() {
        $rules = "# Begin Cache Control\n";
        $rules .= "<IfModule mod_expires.c>\n";
        $rules .= "  ExpiresActive On\n\n";
        
        // Definir regras por tipo
        $rules .= "  # CSS\n";
        $rules .= "  ExpiresByType text/css \"access plus 1 week\"\n\n";
        
        $rules .= "  # JavaScript\n";
        $rules .= "  ExpiresByType application/javascript \"access plus 1 week\"\n";
        $rules .= "  ExpiresByType text/javascript \"access plus 1 week\"\n\n";
        
        $rules .= "  # Images\n";
        $rules .= "  ExpiresByType image/jpeg \"access plus 30 days\"\n";
        $rules .= "  ExpiresByType image/png \"access plus 30 days\"\n";
        $rules .= "  ExpiresByType image/gif \"access plus 30 days\"\n";
        $rules .= "  ExpiresByType image/svg+xml \"access plus 30 days\"\n";
        $rules .= "  ExpiresByType image/x-icon \"access plus 30 days\"\n\n";
        
        $rules .= "  # Fonts\n";
        $rules .= "  ExpiresByType font/woff \"access plus 1 year\"\n";
        $rules .= "  ExpiresByType font/woff2 \"access plus 1 year\"\n";
        $rules .= "  ExpiresByType font/ttf \"access plus 1 year\"\n";
        $rules .= "  ExpiresByType application/vnd.ms-fontobject \"access plus 1 year\"\n";
        $rules .= "  ExpiresByType font/otf \"access plus 1 year\"\n";
        $rules .= "</IfModule>\n\n";
        
        $rules .= "<IfModule mod_deflate.c>\n";
        $rules .= "  # Compress HTML, CSS, JavaScript, Text, XML and fonts\n";
        $rules .= "  AddOutputFilterByType DEFLATE application/javascript\n";
        $rules .= "  AddOutputFilterByType DEFLATE application/rss+xml\n";
        $rules .= "  AddOutputFilterByType DEFLATE application/vnd.ms-fontobject\n";
        $rules .= "  AddOutputFilterByType DEFLATE application/x-font\n";
        $rules .= "  AddOutputFilterByType DEFLATE application/x-font-opentype\n";
        $rules .= "  AddOutputFilterByType DEFLATE application/x-font-otf\n";
        $rules .= "  AddOutputFilterByType DEFLATE application/x-font-truetype\n";
        $rules .= "  AddOutputFilterByType DEFLATE application/x-font-ttf\n";
        $rules .= "  AddOutputFilterByType DEFLATE application/x-javascript\n";
        $rules .= "  AddOutputFilterByType DEFLATE application/xhtml+xml\n";
        $rules .= "  AddOutputFilterByType DEFLATE application/xml\n";
        $rules .= "  AddOutputFilterByType DEFLATE font/opentype\n";
        $rules .= "  AddOutputFilterByType DEFLATE font/otf\n";
        $rules .= "  AddOutputFilterByType DEFLATE font/ttf\n";
        $rules .= "  AddOutputFilterByType DEFLATE image/svg+xml\n";
        $rules .= "  AddOutputFilterByType DEFLATE image/x-icon\n";
        $rules .= "  AddOutputFilterByType DEFLATE text/css\n";
        $rules .= "  AddOutputFilterByType DEFLATE text/html\n";
        $rules .= "  AddOutputFilterByType DEFLATE text/javascript\n";
        $rules .= "  AddOutputFilterByType DEFLATE text/plain\n";
        $rules .= "  AddOutputFilterByType DEFLATE text/xml\n";
        $rules .= "</IfModule>\n";
        $rules .= "# End Cache Control";
        
        return $rules;
    }
}
