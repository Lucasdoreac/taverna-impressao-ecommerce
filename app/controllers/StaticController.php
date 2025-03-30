<?php
/**
 * StaticController - Controlador para servir arquivos estáticos com cache otimizado
 * 
 * Este controlador gerencia a entrega de arquivos estáticos (CSS, JS, imagens)
 * utilizando o CacheHelper para gerenciar os cabeçalhos HTTP de cache.
 */
class StaticController {
    /**
     * Serve um arquivo CSS com cabeçalhos de cache adequados
     * 
     * @param string $filename Nome do arquivo CSS
     */
    public function css($filename) {
        $filePath = ROOT_PATH . '/public/assets/css/' . $filename;
        
        if (class_exists('CacheHelper')) {
            CacheHelper::serveStaticFile($filePath);
        } else {
            // Fallback
            if (file_exists($filePath)) {
                header('Content-Type: text/css');
                readfile($filePath);
            } else {
                header('HTTP/1.0 404 Not Found');
                echo 'File not found: ' . htmlspecialchars($filename);
            }
        }
        exit;
    }
    
    /**
     * Serve um arquivo JavaScript com cabeçalhos de cache adequados
     * 
     * @param string $filename Nome do arquivo JavaScript
     */
    public function js($filename) {
        $filePath = ROOT_PATH . '/public/assets/js/' . $filename;
        
        if (class_exists('CacheHelper')) {
            CacheHelper::serveStaticFile($filePath);
        } else {
            // Fallback
            if (file_exists($filePath)) {
                header('Content-Type: application/javascript');
                readfile($filePath);
            } else {
                header('HTTP/1.0 404 Not Found');
                echo 'File not found: ' . htmlspecialchars($filename);
            }
        }
        exit;
    }
    
    /**
     * Serve uma imagem com cabeçalhos de cache adequados
     * 
     * @param string $filename Nome do arquivo de imagem
     */
    public function image($filename) {
        $filePath = ROOT_PATH . '/public/assets/images/' . $filename;
        
        if (class_exists('CacheHelper')) {
            CacheHelper::serveStaticFile($filePath);
        } else {
            // Fallback
            if (file_exists($filePath)) {
                // Determinar o tipo MIME com base na extensão
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $contentTypes = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml',
                    'ico' => 'image/x-icon'
                ];
                
                if (isset($contentTypes[$ext])) {
                    header('Content-Type: ' . $contentTypes[$ext]);
                }
                
                readfile($filePath);
            } else {
                header('HTTP/1.0 404 Not Found');
                echo 'File not found: ' . htmlspecialchars($filename);
            }
        }
        exit;
    }
    
    /**
     * Serve um arquivo de fonte com cabeçalhos de cache adequados
     * 
     * @param string $filename Nome do arquivo de fonte
     */
    public function font($filename) {
        $filePath = ROOT_PATH . '/public/assets/fonts/' . $filename;
        
        if (class_exists('CacheHelper')) {
            CacheHelper::serveStaticFile($filePath);
        } else {
            // Fallback
            if (file_exists($filePath)) {
                // Determinar o tipo MIME com base na extensão
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $contentTypes = [
                    'woff' => 'font/woff',
                    'woff2' => 'font/woff2',
                    'ttf' => 'font/ttf',
                    'eot' => 'application/vnd.ms-fontobject',
                    'otf' => 'font/otf'
                ];
                
                if (isset($contentTypes[$ext])) {
                    header('Content-Type: ' . $contentTypes[$ext]);
                }
                
                // Configurar CORS para fontes
                header('Access-Control-Allow-Origin: *');
                
                readfile($filePath);
            } else {
                header('HTTP/1.0 404 Not Found');
                echo 'File not found: ' . htmlspecialchars($filename);
            }
        }
        exit;
    }
    
    /**
     * Serve um arquivo combinado do cache
     * 
     * @param string $filename Nome do arquivo no cache
     */
    public function cache($filename) {
        $filePath = ROOT_PATH . '/public/assets/cache/' . $filename;
        
        if (class_exists('CacheHelper')) {
            CacheHelper::serveStaticFile($filePath);
        } else {
            // Fallback
            if (file_exists($filePath)) {
                // Determinar o tipo MIME com base na extensão
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $contentTypes = [
                    'css' => 'text/css',
                    'js' => 'application/javascript'
                ];
                
                if (isset($contentTypes[$ext])) {
                    header('Content-Type: ' . $contentTypes[$ext]);
                }
                
                readfile($filePath);
            } else {
                header('HTTP/1.0 404 Not Found');
                echo 'File not found: ' . htmlspecialchars($filename);
            }
        }
        exit;
    }
}
