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

// Inicializar ResourceOptimizerHelper
if (class_exists('ResourceOptimizerHelper')) {
    ResourceOptimizerHelper::init();
    ResourceOptimizerHelper::setProductionMode($isProduction);
    
    // Habilitar CSS crítico apenas em produção para facilitar desenvolvimento
    if (!$isProduction) {
        ResourceOptimizerHelper::enableCriticalCSS(false);
    }
}

// Inicializar ExternalResourceManager
// Este helper é responsável por minimizar recursos externos
if (class_exists('ExternalResourceManager')) {
    // O ExternalResourceManager não requer inicialização específica
    // mas podemos verificar e baixar recursos comuns aqui se necessário
    if ($isProduction) {
        // Array de recursos comuns para verificar
        $commonResources = [
            'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            'https://cdn.jsdelivr.net/npm/three@0.132.2/build/three.min.js'
        ];
        
        // Verificar disponibilidade local para cada recurso
        foreach ($commonResources as $resource) {
            // Se existe caminho local, mas o arquivo não existe, baixá-lo
            $localPath = ExternalResourceManager::getLocalPath($resource);
            if ($localPath) {
                $fullPath = ROOT_PATH . '/public/' . $localPath;
                if (!file_exists($fullPath)) {
                    // Tentar baixar o recurso
                    ExternalResourceManager::downloadExternalResource($resource);
                }
            }
        }
    }
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

/**
 * Função para otimizar carregamento de recursos externos
 * 
 * @param string $url URL do recurso externo
 * @param string $type Tipo de recurso (css, js, font)
 * @param array $options Opções adicionais
 * @return string Tag HTML otimizada para o recurso
 */
function optimizeExternalResource($url, $type = 'js', $options = []) {
    // Usar ExternalResourceManager se disponível (prioridade)
    if (class_exists('ExternalResourceManager')) {
        return ExternalResourceManager::optimizeExternalResource($url, $type, $options);
    }
    // Fallback para ResourceOptimizerHelper se disponível
    else if (class_exists('ResourceOptimizerHelper')) {
        // Determinar opções padrão com base no tipo
        $defaultOptions = [];
        
        if ($type === 'js') {
            $defaultOptions = [
                'async' => ResourceOptimizerHelper::shouldLoadAsync($url),
                'defer' => true,
                'module' => false
            ];
        } elseif ($type === 'font') {
            $defaultOptions = [
                'preload' => ResourceOptimizerHelper::isCriticalResource($url)
            ];
        }
        
        // Mesclar opções padrão com opções fornecidas
        $options = array_merge($defaultOptions, $options);
        
        // Otimizar com base no tipo
        if ($type === 'js') {
            return ResourceOptimizerHelper::optimizeScriptLoad(
                $url, 
                $options['async'], 
                $options['defer'],
                $options['module']
            );
        } elseif ($type === 'css') {
            // Para CSS, apenas usar a versão local se disponível
            $localUrl = ResourceOptimizerHelper::getLocalResourceUrl($url);
            return '<link rel="stylesheet" href="' . $localUrl . '">';
        } elseif ($type === 'font') {
            return ResourceOptimizerHelper::optimizeFontLoad($url, $options['preload']);
        }
    }
    
    // Fallback para links/scripts padrão
    if ($type === 'css') {
        return '<link rel="stylesheet" href="' . $url . '">';
    } elseif ($type === 'js') {
        $defer = isset($options['defer']) && $options['defer'] ? ' defer' : '';
        $async = isset($options['async']) && $options['async'] ? ' async' : '';
        return '<script src="' . $url . '"' . $defer . $async . '></script>';
    } elseif ($type === 'font') {
        return '<link rel="stylesheet" href="' . $url . '">';
    }
}

/**
 * Função para analisar recursos externos em uma página
 * 
 * @param string $html Conteúdo HTML a analisar
 * @return array Recomendações para otimização
 */
function analyzeExternalResources($html) {
    if (class_exists('ExternalResourceManager')) {
        return ExternalResourceManager::analyzeHtml($html);
    }
    return ['Classe ExternalResourceManager não encontrada'];
}
