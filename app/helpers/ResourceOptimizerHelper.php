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
    
    // Configurações para cache de modelos 3D
    private static $model3dCacheSettings = [
        'preloadFrequentModels' => true,      // Pré-carregar modelos frequentemente acessados
        'maxPreloadModels' => 5,              // Número máximo de modelos a pré-carregar
        'prioritizeProductPage' => true,      // Priorizar cache em páginas de produto
        'recordMetrics' => true,              // Registrar métricas de uso e performance
        'prefetchThreshold' => 3,             // Limiar de acesso para pré-carregamento (# acessos)
        'autoCleanThreshold' => 0.8,          // Limiar para limpeza automática (% do tamanho máximo)
        'autoInvalidateAfterDays' => 14       // Invalidar cache automaticamente após X dias
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
     * Define configurações específicas para o cache de modelos 3D
     * 
     * @param array $settings Configurações a serem atualizadas
     */
    public static function setModel3DCacheSettings($settings) {
        if (!is_array($settings)) {
            return;
        }
        
        self::$model3dCacheSettings = array_merge(self::$model3dCacheSettings, $settings);
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
            'version' => class_exists('ModelCacheHelper') ? ModelCacheHelper::getVersion() : '1.0.0'
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
    
    /**
     * Prepara o modelo 3D para visualização com suporte a cache integrado
     * 
     * @param array $modelData Dados do modelo (path, type, etc.)
     * @param array $options Opções de visualização
     * @return array Dados processados para uso pelo visualizador
     */
    public static function prepareModel3DForViewer($modelData, $options = []) {
        if (!is_array($modelData) || empty($modelData['path'])) {
            return false;
        }
        
        // Opções padrão
        $defaultOptions = [
            'useCache' => self::$model3dCacheEnabled,
            'preload' => self::$model3dCacheSettings['preloadFrequentModels'],
            'recordMetrics' => self::$model3dCacheSettings['recordMetrics']
        ];
        
        // Mesclar opções
        $options = array_merge($defaultOptions, $options);
        
        // Obter tipo do modelo
        $modelType = isset($modelData['type']) ? $modelData['type'] : pathinfo($modelData['path'], PATHINFO_EXTENSION);
        
        // Otimizar URL com cache
        $optimizedModel = self::optimize3DModelUrl($modelData['path'], $options['useCache']);
        
        // Registrar métricas de uso se configurado
        if ($options['recordMetrics'] && $optimizedModel['modelId']) {
            self::recordModel3DUsageMetrics($optimizedModel['modelId'], $modelType);
        }
        
        // Adicionar dados ao resultado
        $result = [
            'url' => $optimizedModel['url'],
            'modelId' => $optimizedModel['modelId'],
            'cached' => $optimizedModel['cached'],
            'type' => $modelType,
            'options' => $options
        ];
        
        // Gerar script de integração para o cliente, se necessário
        if ($options['useCache'] && $optimizedModel['modelId']) {
            $result['integrationScript'] = self::generateModel3DViewerIntegrationScript($optimizedModel['modelId'], $modelType);
        }
        
        return $result;
    }
    
    /**
     * Registra métricas de uso para um modelo 3D
     * 
     * @param string $modelId ID do modelo
     * @param string $modelType Tipo do modelo
     * @return bool Sucesso da operação
     */
    private static function recordModel3DUsageMetrics($modelId, $modelType) {
        if (!self::$model3dCacheEnabled || !class_exists('ModelCacheHelper')) {
            return false;
        }
        
        // Arquivo de métricas
        $metricsFile = ROOT_PATH . '/public/cache/models/metrics.json';
        
        // Carregar dados existentes
        $metrics = [];
        if (file_exists($metricsFile)) {
            $content = file_get_contents($metricsFile);
            if (!empty($content)) {
                $metrics = json_decode($content, true) ?: [];
            }
        }
        
        // Inicializar dados para este modelo se não existirem
        if (!isset($metrics[$modelId])) {
            $metrics[$modelId] = [
                'modelId' => $modelId,
                'modelType' => $modelType,
                'accessCount' => 0,
                'firstAccess' => time(),
                'lastAccess' => time(),
                'browsers' => [],
                'devices' => []
            ];
        }
        
        // Atualizar métricas
        $metrics[$modelId]['accessCount']++;
        $metrics[$modelId]['lastAccess'] = time();
        
        // Capturar informações do navegador e dispositivo
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
        $isMobile = preg_match('/(android|iphone|ipad|mobile)/i', $userAgent) ? 'mobile' : 'desktop';
        
        // Registrar tipo de dispositivo
        if (!isset($metrics[$modelId]['devices'][$isMobile])) {
            $metrics[$modelId]['devices'][$isMobile] = 0;
        }
        $metrics[$modelId]['devices'][$isMobile]++;
        
        // Determinar navegador
        $browser = 'other';
        if (strpos($userAgent, 'Chrome') !== false) {
            $browser = 'chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            $browser = 'firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            $browser = 'safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            $browser = 'edge';
        }
        
        // Registrar navegador
        if (!isset($metrics[$modelId]['browsers'][$browser])) {
            $metrics[$modelId]['browsers'][$browser] = 0;
        }
        $metrics[$modelId]['browsers'][$browser]++;
        
        // Verificar se este modelo deve ser pré-carregado
        if ($metrics[$modelId]['accessCount'] >= self::$model3dCacheSettings['prefetchThreshold']) {
            $metrics[$modelId]['prefetch'] = true;
        }
        
        // Salvar métricas
        return file_put_contents($metricsFile, json_encode($metrics, JSON_PRETTY_PRINT)) !== false;
    }
    
    /**
     * Gera script de integração para o visualizador 3D usar o cache
     * 
     * @param string $modelId ID do modelo
     * @param string $modelType Tipo do modelo
     * @return string Script de integração
     */
    private static function generateModel3DViewerIntegrationScript($modelId, $modelType) {
        // Script para integrar model-viewer.js com o cache
        $script = "// Integração do ModelViewer com o sistema de cache\n";
        $script .= "window.modelCacheIntegration = window.modelCacheIntegration || {};\n";
        $script .= "window.modelCacheIntegration['{$modelId}'] = {\n";
        $script .= "  modelId: '{$modelId}',\n";
        $script .= "  modelType: '{$modelType}',\n";
        $script .= "  timestamp: " . time() . "\n";
        $script .= "};\n\n";
        
        // Adicionar hook para o ModelViewer usar o cache
        $script .= "if (window.modelViewerInitHooks) {\n";
        $script .= "  window.modelViewerInitHooks.push(function(viewer) {\n";
        $script .= "    if (viewer && window.modelCacheManager && viewer.modelId === '{$modelId}') {\n";
        $script .= "      viewer.setCacheManager(window.modelCacheManager);\n";
        $script .= "    }\n";
        $script .= "  });\n";
        $script .= "} else {\n";
        $script .= "  window.modelViewerInitHooks = [function(viewer) {\n";
        $script .= "    if (viewer && window.modelCacheManager && viewer.modelId === '{$modelId}') {\n";
        $script .= "      viewer.setCacheManager(window.modelCacheManager);\n";
        $script .= "    }\n";
        $script .= "  }];\n";
        $script .= "}\n";
        
        return $script;
    }
    
    /**
     * Obtém os modelos 3D que devem ser pré-carregados com base em métricas de uso
     * 
     * @param int $limit Número máximo de modelos a retornar
     * @return array Lista de modelos para pré-carregamento
     */
    public static function getModels3DForPreloading($limit = null) {
        if (!self::$model3dCacheEnabled || !class_exists('ModelCacheHelper') || 
            !self::$model3dCacheSettings['preloadFrequentModels']) {
            return [];
        }
        
        // Usar limite configurado se não especificado
        $limit = $limit ?: self::$model3dCacheSettings['maxPreloadModels'];
        
        // Arquivo de métricas
        $metricsFile = ROOT_PATH . '/public/cache/models/metrics.json';
        
        // Verificar se o arquivo existe
        if (!file_exists($metricsFile)) {
            return [];
        }
        
        // Carregar métricas
        $metrics = json_decode(file_get_contents($metricsFile), true);
        if (!$metrics) {
            return [];
        }
        
        // Filtrar modelos marcados para pré-carregamento
        $preloadModels = [];
        foreach ($metrics as $modelId => $data) {
            // Verificar se modelo deve ser pré-carregado e se está no cache
            if (isset($data['prefetch']) && $data['prefetch'] && 
                ModelCacheHelper::isModelCached($modelId)) {
                
                // Verificar idade do modelo
                $age = time() - $data['lastAccess'];
                $maxAge = self::$model3dCacheSettings['autoInvalidateAfterDays'] * 24 * 60 * 60;
                
                if ($age < $maxAge) {
                    $preloadModels[$modelId] = [
                        'modelId' => $modelId,
                        'accessCount' => $data['accessCount'],
                        'lastAccess' => $data['lastAccess'],
                        'modelType' => $data['modelType'] ?? 'unknown'
                    ];
                }
            }
        }
        
        // Ordenar por contagem de acesso (mais acessados primeiro)
        usort($preloadModels, function($a, $b) {
            return $b['accessCount'] - $a['accessCount'];
        });
        
        // Limitar número de modelos
        $preloadModels = array_slice($preloadModels, 0, $limit);
        
        // Obter URLs para pré-carregamento
        $result = [];
        foreach ($preloadModels as $model) {
            $cachedPath = ModelCacheHelper::getCachedModelPath($model['modelId']);
            if ($cachedPath) {
                $result[] = [
                    'modelId' => $model['modelId'],
                    'url' => BASE_URL . ltrim($cachedPath, '/'),
                    'modelType' => $model['modelType'],
                    'accessCount' => $model['accessCount']
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Gera tags de preload para modelos 3D frequentemente acessados
     * 
     * @param int $limit Número máximo de modelos a pré-carregar
     * @return string Tags HTML para pré-carregamento
     */
    public static function generateModel3DPreloadTags($limit = null) {
        $models = self::getModels3DForPreloading($limit);
        if (empty($models)) {
            return '';
        }
        
        $html = "<!-- Preload de modelos 3D frequentemente acessados -->\n";
        foreach ($models as $model) {
            $html .= "<link rel=\"prefetch\" href=\"{$model['url']}\" as=\"fetch\" crossorigin=\"anonymous\">\n";
        }
        
        return $html;
    }
    
    /**
     * Verifica e realiza limpeza automática do cache de modelos 3D se necessário
     * 
     * @return bool Verdadeiro se a limpeza foi realizada
     */
    public static function checkAndCleanModel3DCache() {
        if (!self::$model3dCacheEnabled || !class_exists('ModelCacheHelper')) {
            return false;
        }
        
        // Verificar última limpeza
        $metadataFile = ROOT_PATH . '/public/cache/models/metadata.json';
        if (!file_exists($metadataFile)) {
            return false;
        }
        
        $metadata = json_decode(file_get_contents($metadataFile), true);
        if (!$metadata || !isset($metadata['lastCleaned'])) {
            return false;
        }
        
        // Verificar se precisa limpar (uma vez por dia)
        $lastCleaned = $metadata['lastCleaned'] ?? 0;
        $daysSinceLastClean = (time() - $lastCleaned) / (60 * 60 * 24);
        if ($daysSinceLastClean < 1) {
            return false;
        }
        
        // Obter informações do cache
        $cacheInfo = ModelCacheHelper::getCacheInfo();
        if ($cacheInfo['enabled'] && 
            isset($cacheInfo['usagePercent']) && 
            $cacheInfo['usagePercent'] > (self::$model3dCacheSettings['autoCleanThreshold'] * 100)) {
            
            // Realizar limpeza
            $targetSize = $cacheInfo['maxSize'] * 0.7; // 70% do tamanho máximo
            ModelCacheHelper::cleanCache($targetSize);
            
            // Atualizar timestamp de limpeza
            $metadata['lastCleaned'] = time();
            file_put_contents($metadataFile, json_encode($metadata));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Retorna um objeto JavaScript contendo os modelos 3D em cache
     * para utilização pelo cliente em pré-carregamento
     * 
     * @return string Script com informações de cache para o cliente
     */
    public static function getModel3DCacheInfoForClient() {
        if (!self::$model3dCacheEnabled || !class_exists('ModelCacheHelper')) {
            return "window.model3dCacheInfo = {enabled: false};";
        }
        
        // Obter modelos para pré-carregamento
        $preloadModels = self::getModels3DForPreloading();
        
        // Obter informações gerais do cache
        $cacheInfo = ModelCacheHelper::getCacheInfo();
        
        // Criar objeto para o cliente
        $clientInfo = [
            'enabled' => $cacheInfo['enabled'],
            'version' => $cacheInfo['version'],
            'preloadModels' => $preloadModels,
            'configuredForBrowser' => true
        ];
        
        // Converter para JSON
        $jsonInfo = json_encode($clientInfo);
        
        // Gerar script
        return "window.model3dCacheInfo = {$jsonInfo};";
    }
    
    /**
     * Integra o visualizador 3D com o sistema de cache de modelos
     * 
     * @param string $modelFilePath Caminho para o arquivo do modelo
     * @param array $options Opções de visualização
     * @return array Dados para inicialização do visualizador com suporte a cache
     */
    public static function integrateModel3DViewerWithCache($modelFilePath, $options = []) {
        // Preparar dados do modelo com suporte a cache
        $modelData = self::prepareModel3DForViewer([
            'path' => $modelFilePath,
            'type' => pathinfo($modelFilePath, PATHINFO_EXTENSION)
        ], $options);
        
        if (!$modelData) {
            return [
                'url' => $modelFilePath,
                'useCache' => false,
                'cacheSupported' => false
            ];
        }
        
        // Dados para retorno
        $viewerData = [
            'url' => $modelData['url'],
            'modelId' => $modelData['modelId'],
            'modelType' => $modelData['type'],
            'cached' => $modelData['cached'],
            'useCache' => $modelData['options']['useCache'],
            'cacheSupported' => self::$model3dCacheEnabled && class_exists('ModelCacheHelper'),
            'integrationScript' => $modelData['integrationScript'] ?? null
        ];
        
        // Verificar e realizar limpeza automática do cache se necessário
        self::checkAndCleanModel3DCache();
        
        return $viewerData;
    }
}
