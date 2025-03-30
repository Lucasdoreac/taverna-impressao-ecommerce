<?php
/**
 * ResourceOptimizerHelper - Helper para otimização de recursos externos
 * 
 * Esta classe oferece métodos para reduzir a dependência de recursos externos
 * através de técnicas como carregamento assíncrono, preloading, e hospedagem local
 * de bibliotecas essenciais.
 */
class ResourceOptimizerHelper {
    // Configurações
    private static $productionMode = false;
    private static $criticalCSSEnabled = true;
    private static $initialized = false;
    private static $criticalCSSCache = [];
    
    // Lista de recursos internos para substituir versões externas
    private static $localResources = [
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' => 'vendor/fontawesome/css/all.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js' => 'vendor/jquery/jquery-3.6.0.min.js',
        'https://cdn.jsdelivr.net/npm/three@0.132.2/build/three.min.js' => 'vendor/threejs/three.min.js'
    ];
    
    // Lista de recursos que podem ser carregados assíncronamente
    private static $asyncResources = [
        'https://cdn.jsdelivr.net/npm/three@0.132.2/build/three.min.js',
        'https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/loaders/STLLoader.js',
        'https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/loaders/OBJLoader.js',
        'https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/loaders/MTLLoader.js'
    ];
    
    // Lista de recursos críticos que devem ser pré-carregados
    private static $criticalResources = [
        'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js'
    ];
    
    /**
     * Inicializa o helper
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        self::$initialized = true;
        
        // Determinar modo de produção
        self::$productionMode = defined('ENVIRONMENT') && ENVIRONMENT === 'production';
        
        // Inicializar recursos locais
        self::initLocalResources();
    }
    
    /**
     * Define o modo de produção
     * 
     * @param bool $productionMode Verdadeiro para modo de produção
     */
    public static function setProductionMode($productionMode) {
        self::$productionMode = $productionMode;
    }
    
    /**
     * Habilita ou desabilita o CSS crítico
     * 
     * @param bool $enabled Verdadeiro para habilitar CSS crítico
     */
    public static function enableCriticalCSS($enabled) {
        self::$criticalCSSEnabled = $enabled;
    }
    
    /**
     * Inicializa a estrutura para recursos locais
     */
    private static function initLocalResources() {
        // Verificar se as pastas vendor existem, caso contrário criá-las
        $vendorPath = ROOT_PATH . '/public/vendor';
        
        if (!file_exists($vendorPath)) {
            mkdir($vendorPath, 0755, true);
        }
        
        // Verificar e criar subdiretórios para cada recurso local
        $subDirs = ['fontawesome', 'jquery', 'threejs', 'fonts'];
        foreach ($subDirs as $dir) {
            $path = $vendorPath . '/' . $dir;
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
        }
    }
    
    /**
     * Verifica se deve usar a versão local de um recurso
     * 
     * @param string $externalUrl URL do recurso externo
     * @return bool Verdadeiro se deve usar a versão local
     */
    public static function shouldUseLocalResource($externalUrl) {
        // Verificar se temos uma versão local
        if (isset(self::$localResources[$externalUrl])) {
            $localPath = ROOT_PATH . '/public/' . self::$localResources[$externalUrl];
            return file_exists($localPath);
        }
        
        return false;
    }
    
    /**
     * Obtém a URL local para um recurso externo
     * 
     * @param string $externalUrl URL do recurso externo
     * @return string URL local do recurso ou original se não existir versão local
     */
    public static function getLocalResourceUrl($externalUrl) {
        if (self::shouldUseLocalResource($externalUrl)) {
            return BASE_URL . self::$localResources[$externalUrl];
        }
        
        return $externalUrl;
    }
    
    /**
     * Verifica se um recurso deve ser carregado assíncronamente
     * 
     * @param string $url URL do recurso
     * @return bool Verdadeiro se deve ser carregado assíncronamente
     */
    public static function shouldLoadAsync($url) {
        return in_array($url, self::$asyncResources);
    }
    
    /**
     * Verifica se um recurso é crítico
     * 
     * @param string $url URL do recurso
     * @return bool Verdadeiro se o recurso é crítico
     */
    public static function isCriticalResource($url) {
        return in_array($url, self::$criticalResources);
    }
    
    /**
     * Gera tags de preloading para recursos críticos
     * 
     * @return string Tags HTML de preloading
     */
    public static function generatePreloadTags() {
        $html = '';
        
        foreach (self::$criticalResources as $resource) {
            // Determinar o tipo de recurso
            $type = 'style';
            if (strpos($resource, '.js') !== false) {
                $type = 'script';
            } elseif (strpos($resource, '.woff') !== false || 
                      strpos($resource, '.woff2') !== false ||
                      strpos($resource, '.ttf') !== false) {
                $type = 'font';
            }
            
            // Usar versão local se disponível
            $resourceUrl = self::getLocalResourceUrl($resource);
            
            // Gerar tag de preload
            $as = ($type === 'style') ? 'style' : (($type === 'script') ? 'script' : 'font');
            $crossorigin = ($type === 'font' || strpos($resourceUrl, 'fonts.googleapis.com') !== false) ? ' crossorigin' : '';
            
            $html .= "<link rel=\"preload\" href=\"{$resourceUrl}\" as=\"{$as}\"{$crossorigin}>\n";
        }
        
        return $html;
    }
    
    /**
     * Gera tags para preconexões com domínios externos
     * 
     * @return string Tags HTML de preconexão
     */
    public static function generatePreconnectTags() {
        // Lista de domínios para preconectar
        $domains = [
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com',
            'https://cdnjs.cloudflare.com'
        ];
        
        $html = '';
        foreach ($domains as $domain) {
            $crossorigin = (strpos($domain, 'fonts.') !== false) ? ' crossorigin' : '';
            $html .= "<link rel=\"preconnect\" href=\"{$domain}\"{$crossorigin}>\n";
        }
        
        return $html;
    }
    
    /**
     * Gera CSS crítico para os estilos essenciais
     * 
     * @param string $page Nome da página atual para cache específico
     * @return string CSS crítico inline
     */
    public static function getCriticalCSS($page = 'default') {
        // Se o CSS crítico está desabilitado, retornar vazio
        if (!self::$criticalCSSEnabled) {
            return '';
        }
        
        // Verificar cache
        if (isset(self::$criticalCSSCache[$page])) {
            return self::$criticalCSSCache[$page];
        }
        
        // Caminho para o arquivo de CSS crítico
        $criticalCSSPath = ROOT_PATH . '/public/assets/css/critical/' . $page . '.css';
        
        // Se não encontrar específico da página, usar o default
        if (!file_exists($criticalCSSPath)) {
            $criticalCSSPath = ROOT_PATH . '/public/assets/css/critical/default.css';
        }
        
        // Se não existir arquivo de CSS crítico, retornar vazio
        if (!file_exists($criticalCSSPath)) {
            return '';
        }
        
        // Ler e cachear o CSS crítico
        $criticalCSS = file_get_contents($criticalCSSPath);
        self::$criticalCSSCache[$page] = $criticalCSS;
        
        return $criticalCSS;
    }
    
    /**
     * Otimiza carregamento de uma fonte web
     * 
     * @param string $url URL da fonte
     * @param bool $preload Se deve precarregar a fonte
     * @return string Tag HTML otimizada
     */
    public static function optimizeFontLoad($url, $preload = false) {
        // Verificar se temos uma versão local
        $fontUrl = self::getLocalResourceUrl($url);
        
        $html = '';
        
        // Adicionar preload se solicitado
        if ($preload) {
            $html .= "<link rel=\"preload\" href=\"{$fontUrl}\" as=\"font\" crossorigin>\n";
        }
        
        // Adicionar link com font-display swap
        $html .= "<link rel=\"stylesheet\" href=\"{$fontUrl}\" media=\"print\" onload=\"this.media='all'\">\n";
        $html .= "<noscript><link rel=\"stylesheet\" href=\"{$fontUrl}\"></noscript>\n";
        
        return $html;
    }
    
    /**
     * Otimiza o carregamento de um script externo
     * 
     * @param string $url URL do script
     * @param bool $async Se deve carregar de forma assíncrona
     * @param bool $defer Se deve adiar o carregamento
     * @param bool $module Se é um módulo ES6
     * @return string Tag script otimizada
     */
    public static function optimizeScriptLoad($url, $async = false, $defer = true, $module = false) {
        // Verificar se temos uma versão local
        $scriptUrl = self::getLocalResourceUrl($url);
        
        // Determinar atributos
        $asyncAttr = $async ? ' async' : '';
        $deferAttr = $defer ? ' defer' : '';
        $moduleAttr = $module ? ' type="module"' : '';
        
        // Gerar tag script otimizada
        return "<script src=\"{$scriptUrl}\"{$asyncAttr}{$deferAttr}{$moduleAttr}></script>\n";
    }
    
    /**
     * Gera uma versão consolidada dos scripts Three.js
     * 
     * @param array $components Componentes do Three.js a incluir
     * @return string URL do script consolidado
     */
    public static function getConsolidatedThreeJS($components = []) {
        $defaultComponents = [
            'three.min.js',
            'STLLoader.js',
            'OBJLoader.js',
            'MTLLoader.js'
        ];
        
        // Se não especificou componentes, usar os padrão
        if (empty($components)) {
            $components = $defaultComponents;
        }
        
        // Verificar se o arquivo consolidado já existe
        $hash = md5(implode('', $components));
        $consolidatedPath = ROOT_PATH . '/public/vendor/threejs/consolidated-' . $hash . '.js';
        $consolidatedUrl = BASE_URL . 'vendor/threejs/consolidated-' . $hash . '.js';
        
        // Se já existe, retornar a URL
        if (file_exists($consolidatedPath)) {
            return $consolidatedUrl;
        }
        
        // Se estamos em produção, criar o arquivo consolidado
        if (self::$productionMode) {
            $content = '';
            
            // Base paths para os componentes
            $basePaths = [
                'three.min.js' => 'https://cdn.jsdelivr.net/npm/three@0.132.2/build/three.min.js',
                'STLLoader.js' => 'https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/loaders/STLLoader.js',
                'OBJLoader.js' => 'https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/loaders/OBJLoader.js',
                'MTLLoader.js' => 'https://cdn.jsdelivr.net/npm/three@0.132.2/examples/js/loaders/MTLLoader.js'
            ];
            
            // Concatenar o conteúdo de cada componente
            foreach ($components as $component) {
                if (isset($basePaths[$component])) {
                    $fileContent = @file_get_contents($basePaths[$component]);
                    if ($fileContent !== false) {
                        $content .= "/* {$component} */\n" . $fileContent . "\n\n";
                    }
                }
            }
            
            // Salvar arquivo consolidado
            if (!empty($content)) {
                file_put_contents($consolidatedPath, $content);
                return $consolidatedUrl;
            }
        }
        
        // Se não conseguiu consolidar, retornar a URL padrão
        return BASE_URL . 'vendor/threejs/three.min.js';
    }
    
    /**
     * Adiciona marcador para carregar Three.js apenas quando necessário
     * 
     * @param bool $withLoaders Se deve incluir os loaders
     * @return string Script para carregamento condicional
     */
    public static function loadThreeJSOnDemand($withLoaders = true) {
        $components = ['three.min.js'];
        
        if ($withLoaders) {
            $components[] = 'STLLoader.js';
            $components[] = 'OBJLoader.js';
            $components[] = 'MTLLoader.js';
        }
        
        $consolidatedUrl = self::getConsolidatedThreeJS($components);
        
        // Criar script para carregamento condicional
        $script = "<script>\n";
        $script .= "// Carregamento condicional de Three.js\n";
        $script .= "window.loadThreeJS = function(callback) {\n";
        $script .= "  if (window.THREE) {\n";
        $script .= "    if (callback) callback();\n";
        $script .= "    return;\n";
        $script .= "  }\n\n";
        $script .= "  var script = document.createElement('script');\n";
        $script .= "  script.src = '{$consolidatedUrl}';\n";
        $script .= "  script.onload = function() {\n";
        $script .= "    if (callback) callback();\n";
        $script .= "  };\n";
        $script .= "  document.head.appendChild(script);\n";
        $script .= "};\n\n";
        $script .= "// Detectar se a página atual precisa do Three.js\n";
        $script .= "document.addEventListener('DOMContentLoaded', function() {\n";
        $script .= "  if (document.getElementById('model-viewer-container') || \n";
        $script .= "      document.querySelector('[data-needs-threejs]')) {\n";
        $script .= "    loadThreeJS();\n";
        $script .= "  }\n";
        $script .= "});\n";
        $script .= "</script>\n";
        
        return $script;
    }
}
