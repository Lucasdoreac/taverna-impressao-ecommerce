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
    private static $model3dCacheEnabled = true;
    
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
    
    // Tipos de modelos 3D suportados para cache
    private static $supportedModelFormats = ['stl', 'obj', 'mtl'];
    
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
        
        // Inicializar cache de modelos 3D se disponível
        if (class_exists('ModelCacheHelper')) {
            ModelCacheHelper::init();
            self::$model3dCacheEnabled = ModelCacheHelper::isCacheEnabled();
        } else {
            self::$model3dCacheEnabled = false;
        }
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
     * Habilita ou desabilita o cache de modelos 3D
     * 
     * @param bool $enabled Verdadeiro para habilitar cache de modelos 3D
     */
    public static function enableModel3DCache($enabled) {
        self::$model3dCacheEnabled = $enabled;
        
        // Propagar configuração para o ModelCacheHelper
        if (class_exists('ModelCacheHelper')) {
            ModelCacheHelper::setCacheEnabled($enabled);
        }
    }
    
    /**
     * Define a versão do cache para modelos 3D
     * 
     * @param string $version Nova versão para invalidação de cache
     */
    public static function setModel3DCacheVersion($version) {
        if (class_exists('ModelCacheHelper')) {
            ModelCacheHelper::setVersion($version);
        }
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
    
    /**
     * Verifica se um arquivo é um modelo 3D suportado
     * 
     * @param string $filePath Caminho do arquivo
     * @return bool Verdadeiro se for um modelo 3D suportado
     */
    public static function isSupported3DModel($filePath) {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($extension, self::$supportedModelFormats);
    }
    
    /**
     * Otimiza a URL de um modelo 3D para uso com cache
     * 
     * @param string $filePath Caminho do arquivo de modelo 3D
     * @param bool $useCache Se deve usar o sistema de cache (padrão: verdadeiro)
     * @return array Informações otimizadas do modelo
     */
    public static function optimize3DModelUrl($filePath, $useCache = true) {
        // Verificar se o modelo é suportado
        if (!self::isSupported3DModel($filePath)) {
            return [
                'url' => $filePath,
                'cached' => false,
                'modelId' => null
            ];
        }
        
        // Verificar se o cache está habilitado e disponível
        if (!$useCache || !self::$model3dCacheEnabled || !class_exists('ModelCacheHelper')) {
            return [
                'url' => $filePath,
                'cached' => false,
                'modelId' => null
            ];
        }
        
        // Gerar ID do modelo
        $fullPath = strpos($filePath, ROOT_PATH) === 0 ? $filePath : ROOT_PATH . '/public/' . ltrim($filePath, '/');
        $modelId = ModelCacheHelper::generateModelId($fullPath);
        
        // Verificar se o modelo está em cache
        $isCached = ModelCacheHelper::isModelCached($modelId);
        
        // Se não estiver em cache e for um arquivo local válido, adicionar ao cache
        if (!$isCached && file_exists($fullPath)) {
            $modelId = ModelCacheHelper::cacheModel($fullPath);
            $isCached = $modelId !== false;
        }
        
        // Determinar a URL final
        if ($isCached) {
            $cachedPath = ModelCacheHelper::getCachedModelPath($modelId);
            $url = BASE_URL . ltrim($cachedPath, '/');
            
            // Registrar acesso ao modelo
            ModelCacheHelper::recordAccess($modelId);
        } else {
            $url = $filePath;
        }
        
        // Adicionar parâmetros para controle de cache
        if ($modelId) {
            $glue = strpos($url, '?') === false ? '?' : '&';
            $url .= $glue . 'v=' . ModelCacheHelper::getVersion() . '&id=' . $modelId;
        }
        
        return [
            'url' => $url,
            'cached' => $isCached,
            'modelId' => $modelId
        ];
    }
    
    /**
     * Gera o script JavaScript para inicializar o gerenciador de cache de modelos 3D no cliente
     * 
     * @param array $options Opções de configuração para o gerenciador de cache
     * @return string Script JavaScript para inicialização
     */
    public static function generateModel3DCacheManagerScript($options = []) {
        // Opções padrão
        $defaultOptions = [
            'debug' => !self::$productionMode,
            'maxCacheSize' => 50 * 1024 * 1024, // 50MB
            'version' => ModelCacheHelper::getVersion()
        ];
        
        // Mesclar opções
        $options = array_merge($defaultOptions, $options);
        
        // Converter opções para JSON
        $optionsJson = json_encode($options);
        
        // Gerar script
        $script = "<script>\n";
        $script .= "// Inicialização do gerenciador de cache de modelos 3D\n";
        $script .= "document.addEventListener('DOMContentLoaded', function() {\n";
        $script .= "  if (typeof ModelCacheManager !== 'undefined') {\n";
        $script .= "    window.modelCacheManager = new ModelCacheManager({$optionsJson});\n";
        $script .= "    // Disponibilizar para o visualizador 3D\n";
        $script .= "    window.getModelCache = function() {\n";
        $script .= "      return window.modelCacheManager;\n";
        $script .= "    };\n";
        $script .= "  } else {\n";
        $script .= "    console.warn('ModelCacheManager não disponível. Cache de modelos 3D desabilitado.');\n";
        $script .= "  }\n";
        $script .= "});\n";
        $script .= "</script>\n";
        
        return $script;
    }
    
    /**
     * Configura headers HTTP para controle de cache para modelos 3D
     * 
     * @param string $modelId ID do modelo no cache
     */
    public static function setModel3DCacheHeaders($modelId) {
        if (!self::$model3dCacheEnabled || !class_exists('ModelCacheHelper')) {
            return;
        }
        
        // Verificar se o cliente tem uma versão em cache válida
        if (ModelCacheHelper::clientHasValidCache()) {
            ModelCacheHelper::sendNotModifiedIfValid();
            return;
        }
        
        // Configurar headers de cache
        ModelCacheHelper::setCacheHeaders();
        
        // Registrar acesso ao modelo
        if ($modelId) {
            ModelCacheHelper::recordAccess($modelId);
        }
    }
    
    /**
     * Limpa o cache de modelos 3D
     * 
     * @return bool Sucesso da operação
     */
    public static function clearModel3DCache() {
        if (!self::$model3dCacheEnabled || !class_exists('ModelCacheHelper')) {
            return false;
        }
        
        return ModelCacheHelper::clearCache();
    }
    
    /**
     * Obtém informações sobre o cache de modelos 3D
     * 
     * @return array Informações sobre o cache
     */
    public static function getModel3DCacheInfo() {
        if (!self::$model3dCacheEnabled || !class_exists('ModelCacheHelper')) {
            return [
                'enabled' => false,
                'reason' => 'Cache de modelos 3D desabilitado ou ModelCacheHelper não disponível'
            ];
        }
        
        return ModelCacheHelper::getCacheInfo();
    }
}
