<?php
/**
 * Arquivo de inicialização para helpers de otimização
 * 
 * Este arquivo inicializa todos os helpers necessários para otimização do site
 * e deve ser incluído no início da aplicação (index.php ou bootstrap.php)
 */

// Verificar ambiente
$isProduction = defined('ENVIRONMENT') && ENVIRONMENT === 'production';

// Inicializar AssetOptimizerHelper
if (class_exists('AssetOptimizerHelper')) {
    AssetOptimizerHelper::init();
    AssetOptimizerHelper::setProductionMode($isProduction);
    
    // Definir versão baseada no timestamp do último deploy ou uma constante
    $version = defined('ASSETS_VERSION') ? ASSETS_VERSION : date('YmdHi');
    AssetOptimizerHelper::setVersion($version);
}

// Inicializar CacheHelper
if (class_exists('CacheHelper')) {
    CacheHelper::setProductionMode($isProduction);
    
    // Configurações personalizadas de cache (opcional)
    // CacheHelper::setCacheTimeForType('css', 1209600); // 2 semanas para CSS
    // CacheHelper::setCacheTimeForType('js', 1209600);  // 2 semanas para JS
}

/**
 * Função para facilitar o uso de imagens com lazy loading em toda a aplicação
 * 
 * @param string $image Caminho relativo da imagem
 * @param string $alt Texto alternativo
 * @param string $class Classes CSS adicionais
 * @param array $attributes Atributos adicionais
 * @return string Tag HTML da imagem com lazy loading
 */
function lazyImage($image, $alt = '', $class = '', $attributes = []) {
    if (class_exists('AssetOptimizerHelper')) {
        return AssetOptimizerHelper::lazyImage($image, $alt, $class, $attributes);
    } else {
        // Fallback para o método convencional
        $attributesStr = '';
        foreach ($attributes as $key => $value) {
            $attributesStr .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        return '<img src="' . BASE_URL . 'assets/images/' . $image . '" alt="' . htmlspecialchars($alt) . '" class="' . $class . '"' . $attributesStr . '>';
    }
}

/**
 * Função para otimizar arquivos CSS
 * 
 * @param string|array $files Arquivo ou array de arquivos CSS para otimizar
 * @param boolean $combine Se deve combinar vários arquivos em um único
 * @return string Tag HTML para o(s) arquivo(s) CSS
 */
function optimizeCSS($files, $combine = true) {
    if (class_exists('AssetOptimizerHelper')) {
        return AssetOptimizerHelper::css($files, $combine);
    } else {
        // Fallback para o método convencional
        if (is_array($files)) {
            $html = '';
            foreach ($files as $file) {
                $html .= '<link rel="stylesheet" href="' . BASE_URL . 'assets/css/' . $file . '">' . PHP_EOL;
            }
            return $html;
        } else {
            return '<link rel="stylesheet" href="' . BASE_URL . 'assets/css/' . $files . '">';
        }
    }
}

/**
 * Função para otimizar arquivos JavaScript
 * 
 * @param string|array $files Arquivo ou array de arquivos JavaScript para otimizar
 * @param boolean $combine Se deve combinar vários arquivos em um único
 * @param boolean $defer Se deve adicionar o atributo defer
 * @return string Tag HTML para o(s) arquivo(s) JavaScript
 */
function optimizeJS($files, $combine = true, $defer = true) {
    if (class_exists('AssetOptimizerHelper')) {
        return AssetOptimizerHelper::js($files, $combine, $defer);
    } else {
        // Fallback para o método convencional
        $deferAttr = $defer ? ' defer' : '';
        
        if (is_array($files)) {
            $html = '';
            foreach ($files as $file) {
                $html .= '<script src="' . BASE_URL . 'assets/js/' . $file . '"' . $deferAttr . '></script>' . PHP_EOL;
            }
            return $html;
        } else {
            return '<script src="' . BASE_URL . 'assets/js/' . $files . '"' . $deferAttr . '></script>';
        }
    }
}

/**
 * Função para servir arquivos estáticos com cabeçalhos de cache apropriados
 * 
 * @param string $filePath Caminho do arquivo
 * @param int $cacheTime Tempo de cache em segundos (opcional)
 * @return bool True se o arquivo foi servido, false caso contrário
 */
function serveStaticFile($filePath, $cacheTime = null) {
    if (class_exists('CacheHelper')) {
        return CacheHelper::serveStaticFile($filePath, $cacheTime);
    } else {
        // Fallback básico
        if (!file_exists($filePath)) {
            return false;
        }
        
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $contentTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml'
        ];
        
        if (isset($contentTypes[$ext])) {
            header('Content-Type: ' . $contentTypes[$ext]);
        }
        
        readfile($filePath);
        exit;
    }
}
