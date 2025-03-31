<?php
/**
 * ModelViewerHelper - Classe para renderizar visualizadores 3D de modelos STL/OBJ
 * 
 * Esta classe fornece métodos para gerar o código HTML e JavaScript necessário
 * para visualizar modelos 3D diretamente no navegador usando a biblioteca Three.js
 */
class ModelViewerHelper {
    /**
     * Retorna o código HTML para incluir a biblioteca Three.js e extensões necessárias
     * 
     * @param bool $includeOptimizations Se deve incluir os arquivos de otimização
     * @return string Código HTML para incluir as bibliotecas
     */
    public static function includeThreeJs($includeOptimizations = true) {
        $baseCode = '
        <!-- Three.js e extensões -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/STLLoader.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/OBJLoader.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/MTLLoader.min.js"></script>
        <script src="' . BASE_URL . 'assets/js/model-viewer.js"></script>
        <!-- Gerenciador de cache de modelos 3D -->
        <script src="' . BASE_URL . 'assets/js/model-cache-manager.js"></script>
        <script src="' . BASE_URL . 'assets/js/model-viewer-cache-integration.js"></script>
        <link rel="stylesheet" href="' . BASE_URL . 'assets/css/model-viewer.css">
        ';
        
        // Adicionar arquivos de otimização
        if ($includeOptimizations) {
            $baseCode .= '
            <!-- Otimizações do Visualizador 3D -->
            <script src="' . BASE_URL . 'assets/js/model-viewer-enhancement.js"></script>
            <script src="' . BASE_URL . 'assets/js/model-viewer-integration.js"></script>
            <link rel="stylesheet" href="' . BASE_URL . 'assets/css/model-viewer-advanced.css">
            ';
        }
        
        return $baseCode;
    }
    
    /**
     * Retorna o código HTML para um container de visualização 3D
     * 
     * @param string $id ID único para o visualizador
     * @param string $filePath Caminho para o arquivo 3D
     * @param string $fileType Tipo de arquivo (stl ou obj)
     * @param array $options Opções adicionais para o visualizador
     * @param bool $useOptimizedViewer Se deve usar o visualizador otimizado
     * @return string Código HTML para o visualizador
     */
    public static function createViewer($id, $filePath, $fileType, $options = [], $useOptimizedViewer = true) {
        // Definir opções padrão
        $defaultOptions = [
            'width' => '100%',
            'height' => '400px',
            'backgroundColor' => '#f8f9fa',
            'modelColor' => '#6c757d',
            'autoRotate' => true,
            'showGrid' => true,
            'showAxes' => false,
            'showControls' => true,
            'showStats' => false,
            'enableZoom' => true,
            'enablePan' => true,
            'class' => 'model-viewer',
            'optimizeForMobile' => true,
            'progressiveLoading' => true,
            'adaptiveQuality' => true,
            'showAdvancedOptions' => true,
            'useCache' => true
        ];
        
        // Mesclar opções fornecidas com padrões
        $options = array_merge($defaultOptions, $options);
        
        // Sanitizar o tipo de arquivo
        $fileType = strtolower($fileType);
        if (!in_array($fileType, ['stl', 'obj'])) {
            $fileType = 'stl'; // Padrão para STL se tipo inválido
        }
        
        // Sanitizar caminho do arquivo
        $filePath = htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8');
        
        // Verificar se devemos adicionar uma classe responsiva para altura
        $heightClass = '';
        if ($options['height'] === '250px' || $options['height'] === '250') {
            $heightClass = 'model-viewer-height-sm';
        } elseif ($options['height'] === '350px' || $options['height'] === '350') {
            $heightClass = 'model-viewer-height-md';
        } elseif ($options['height'] === '450px' || $options['height'] === '450') {
            $heightClass = 'model-viewer-height-lg';
        }
        
        if (!empty($heightClass)) {
            $options['class'] .= ' ' . $heightClass;
            // Remover altura fixa se usarmos classes de altura
            $options['height'] = '';
        }
        
        // Gerar código para o container
        $containerStyle = '';
        if (!empty($options['width'])) {
            $containerStyle .= "width: {$options['width']};";
        }
        if (!empty($options['height'])) {
            $containerStyle .= "height: {$options['height']};";
        }
        if (!empty($options['backgroundColor'])) {
            $containerStyle .= "background-color: {$options['backgroundColor']};";
        }
        
        // Adicionar atributo para integração com cache, se habilitado
        $dataCacheAttr = $options['useCache'] ? " data-use-cache=\"true\"" : "";
        
        $containerHtml = "<div id=\"{$id}\" class=\"{$options['class']}\"" . 
                         (!empty($containerStyle) ? " style=\"{$containerStyle}\"" : "") . 
                         $dataCacheAttr . "></div>";
        
        // Gerar código JavaScript para inicializar o visualizador
        if ($useOptimizedViewer) {
            // Usar o visualizador otimizado
            $initScript = "
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Inicializar o visualizador otimizado quando o DOM estiver pronto
                if (typeof initOptimizedViewer !== 'undefined') {
                    const viewer = initOptimizedViewer('{$id}', {
                        filePath: '{$filePath}',
                        fileType: '{$fileType}',
                        modelColor: '{$options['modelColor']}',
                        backgroundColor: '{$options['backgroundColor']}',
                        autoRotate: " . ($options['autoRotate'] ? 'true' : 'false') . ",
                        showGrid: " . ($options['showGrid'] ? 'true' : 'false') . ",
                        showAxes: " . ($options['showAxes'] ? 'true' : 'false') . ",
                        showControls: " . ($options['showControls'] ? 'true' : 'false') . ",
                        showStats: " . ($options['showStats'] ? 'true' : 'false') . ",
                        enableZoom: " . ($options['enableZoom'] ? 'true' : 'false') . ",
                        enablePan: " . ($options['enablePan'] ? 'true' : 'false') . ",
                        optimizeForMobile: " . ($options['optimizeForMobile'] ? 'true' : 'false') . ",
                        progressiveLoading: " . ($options['progressiveLoading'] ? 'true' : 'false') . ",
                        adaptiveQuality: " . ($options['adaptiveQuality'] ? 'true' : 'false') . ",
                        showAdvancedOptions: " . ($options['showAdvancedOptions'] ? 'true' : 'false') . ",
                        useCache: " . ($options['useCache'] ? 'true' : 'false') . "
                    });
                    
                    // Conectar ao gerenciador de cache se disponível
                    if (window.modelCacheManager && " . ($options['useCache'] ? 'true' : 'false') . ") {
                        viewer.setCacheManager(window.modelCacheManager);
                    }
                } else {
                    // Fallback para o visualizador padrão
                    console.warn('Visualizador otimizado não encontrado. Usando visualizador padrão.');
                    if (typeof ModelViewer !== 'undefined') {
                        const viewer = new ModelViewer({
                            containerId: '{$id}',
                            filePath: '{$filePath}',
                            fileType: '{$fileType}',
                            modelColor: '{$options['modelColor']}',
                            backgroundColor: '{$options['backgroundColor']}',
                            autoRotate: " . ($options['autoRotate'] ? 'true' : 'false') . ",
                            showGrid: " . ($options['showGrid'] ? 'true' : 'false') . ",
                            showAxes: " . ($options['showAxes'] ? 'true' : 'false') . ",
                            showControls: " . ($options['showControls'] ? 'true' : 'false') . ",
                            showStats: " . ($options['showStats'] ? 'true' : 'false') . ",
                            enableZoom: " . ($options['enableZoom'] ? 'true' : 'false') . ",
                            enablePan: " . ($options['enablePan'] ? 'true' : 'false') . ",
                            optimizeForMobile: " . ($options['optimizeForMobile'] ? 'true' : 'false') . ",
                            progressiveLoading: " . ($options['progressiveLoading'] ? 'true' : 'false') . "
                        });
                        
                        // Salvar instância do viewer no escopo global para acesso posterior
                        if (!window.modelViewers) {
                            window.modelViewers = {};
                        }
                        window.modelViewers['{$id}'] = viewer;
                        
                        // Conectar ao gerenciador de cache se disponível
                        if (window.modelCacheManager && " . ($options['useCache'] ? 'true' : 'false') . ") {
                            viewer.setCacheManager(window.modelCacheManager);
                            
                            // Extrair ID do modelo da URL, se presente
                            const url = new URL('{$filePath}', window.location.href);
                            const modelId = url.searchParams.get('id');
                            if (modelId) {
                                viewer.setModelId(modelId);
                            }
                        }
                    } else {
                        console.error('ModelViewer não está definido. Verifique se o script model-viewer.js foi carregado corretamente.');
                    }
                }
            });
            </script>
            ";
        } else {
            // Usar o visualizador original
            $initScript = "
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Inicializar o visualizador quando o DOM estiver pronto
                if (typeof ModelViewer !== 'undefined') {
                    const viewer = new ModelViewer({
                        containerId: '{$id}',
                        filePath: '{$filePath}',
                        fileType: '{$fileType}',
                        modelColor: '{$options['modelColor']}',
                        backgroundColor: '{$options['backgroundColor']}',
                        autoRotate: " . ($options['autoRotate'] ? 'true' : 'false') . ",
                        showGrid: " . ($options['showGrid'] ? 'true' : 'false') . ",
                        showAxes: " . ($options['showAxes'] ? 'true' : 'false') . ",
                        showControls: " . ($options['showControls'] ? 'true' : 'false') . ",
                        showStats: " . ($options['showStats'] ? 'true' : 'false') . ",
                        enableZoom: " . ($options['enableZoom'] ? 'true' : 'false') . ",
                        enablePan: " . ($options['enablePan'] ? 'true' : 'false') . ",
                        optimizeForMobile: " . ($options['optimizeForMobile'] ? 'true' : 'false') . ",
                        progressiveLoading: " . ($options['progressiveLoading'] ? 'true' : 'false') . "
                    });
                    
                    // Salvar instância do viewer no escopo global para acesso posterior
                    if (!window.modelViewers) {
                        window.modelViewers = {};
                    }
                    window.modelViewers['{$id}'] = viewer;
                    
                    // Conectar ao gerenciador de cache se disponível
                    if (window.modelCacheManager && " . ($options['useCache'] ? 'true' : 'false') . ") {
                        viewer.setCacheManager(window.modelCacheManager);
                        
                        // Extrair ID do modelo da URL, se presente
                        const url = new URL('{$filePath}', window.location.href);
                        const modelId = url.searchParams.get('id');
                        if (modelId) {
                            viewer.setModelId(modelId);
                        }
                    }
                } else {
                    console.error('ModelViewer não está definido. Verifique se o script model-viewer.js foi carregado corretamente.');
                }
            });
            </script>
            ";
        }
        
        // Retornar HTML final
        return $containerHtml . $initScript;
    }
    
    /**
     * Retorna o código HTML para um visualizador de modelos do cliente
     * 
     * @param array $model Dados do modelo do cliente
     * @param array $options Opções adicionais para o visualizador
     * @param bool $useOptimizedViewer Se deve usar o visualizador otimizado
     * @return string Código HTML para o visualizador
     */
    public static function createCustomerModelViewer($model, $options = [], $useOptimizedViewer = true) {
        // Verificar se o modelo é válido
        if (!isset($model['id']) || !isset($model['file_name']) || !isset($model['file_type'])) {
            return '<div class="alert alert-warning">Informações do modelo incompletas para visualização 3D.</div>';
        }
        
        // Construir caminho para o arquivo
        $filePath = BASE_URL . 'uploads/3d_models/' . $model['file_name'];
        
        // Gerar ID de cache para o modelo, se suportado
        if (class_exists('ResourceOptimizerHelper') && class_exists('ModelCacheHelper')) {
            $fullPath = ROOT_PATH . '/public/uploads/3d_models/' . $model['file_name'];
            if (file_exists($fullPath)) {
                $modelId = ModelCacheHelper::generateModelId($fullPath);
                if ($modelId) {
                    // Adicionar parâmetros de cache à URL
                    $filePath .= '?id=' . $modelId . '&v=' . ModelCacheHelper::getVersion();
                }
            }
        }
        
        // Opções padrão específicas para modelos de cliente
        $defaultOptions = [
            'height' => '350px',
            'backgroundColor' => '#f0f0f0',
            'modelColor' => '#3d7b9c',
            'showControls' => true,
            'autoRotate' => true,
            'class' => 'customer-model-viewer',
            'optimizeForMobile' => true,
            'progressiveLoading' => true,
            'adaptiveQuality' => true,
            'showAdvancedOptions' => true,
            'useCache' => true
        ];
        
        // Mesclar com opções fornecidas
        $options = array_merge($defaultOptions, $options);
        
        // Gerar ID único para o visualizador
        $id = 'customer-model-viewer-' . $model['id'];
        
        // Obter visualizador
        return self::createViewer($id, $filePath, $model['file_type'], $options, $useOptimizedViewer);
    }
    
    /**
     * Retorna o código HTML para um visualizador de produtos
     * 
     * @param array $product Dados do produto
     * @param array $options Opções adicionais para o visualizador
     * @param bool $useOptimizedViewer Se deve usar o visualizador otimizado
     * @return string Código HTML para o visualizador
     */
    public static function createProductModelViewer($product, $options = [], $useOptimizedViewer = true) {
        // Verificar se o produto tem arquivo de modelo
        if (!isset($product['model_file']) || empty($product['model_file'])) {
            return '<div class="alert alert-info">Este produto não possui um modelo 3D para visualização.</div>';
        }
        
        // Verificar tipo de arquivo do modelo
        $fileType = pathinfo($product['model_file'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileType), ['stl', 'obj'])) {
            return '<div class="alert alert-warning">Formato de arquivo não suportado para visualização 3D.</div>';
        }
        
        // Construir caminho base para o arquivo
        $baseFilePath = 'uploads/products/models/' . $product['model_file'];
        $fullPath = ROOT_PATH . '/public/' . $baseFilePath;
        $filePath = BASE_URL . $baseFilePath;
        
        // Otimizar URL para cache, se disponível
        if (class_exists('ResourceOptimizerHelper') && class_exists('ModelCacheHelper')) {
            // Verificar se o arquivo existe
            if (file_exists($fullPath)) {
                // Gerar ID de cache para o modelo
                $modelId = ModelCacheHelper::generateModelId($fullPath);
                if ($modelId) {
                    // Adicionar parâmetros de cache à URL
                    $filePath .= '?id=' . $modelId . '&v=' . ModelCacheHelper::getVersion();
                    
                    // Adicionar ao cache do servidor se ainda não estiver em cache
                    if (!ModelCacheHelper::isModelCached($modelId)) {
                        ModelCacheHelper::cacheModel($fullPath, $modelId);
                    }
                }
            }
        }
        
        // Opções padrão específicas para produtos
        $defaultOptions = [
            'height' => '400px',
            'backgroundColor' => '#ffffff',
            'modelColor' => '#5a5a5a',
            'showControls' => true,
            'autoRotate' => true,
            'showGrid' => true,
            'class' => 'product-model-viewer',
            'optimizeForMobile' => true,
            'progressiveLoading' => true,
            'adaptiveQuality' => true,
            'showAdvancedOptions' => true,
            'useCache' => true
        ];
        
        // Mesclar com opções fornecidas
        $options = array_merge($defaultOptions, $options);
        
        // Gerar ID único para o visualizador
        $id = 'product-model-viewer-' . $product['id'];
        
        // Obter visualizador
        return self::createViewer($id, $filePath, $fileType, $options, $useOptimizedViewer);
    }
    
    /**
     * Retorna o CSS para os visualizadores 3D (depreciado - usar arquivo CSS externo agora)
     * 
     * @deprecated Usar arquivo model-viewer.css externo em vez disso
     * @return string Código CSS
     */
    public static function getViewerCSS() {
        // Aviso de depreciação
        trigger_error('ModelViewerHelper::getViewerCSS() está depreciado. Use o arquivo model-viewer.css externo.', E_USER_DEPRECATED);
        
        return '
        <!-- CSS depreciado: Use o arquivo model-viewer.css externo -->
        <link rel="stylesheet" href="' . BASE_URL . 'assets/css/model-viewer.css">
        ';
    }
    
    /**
     * Retorna código para manipular o redimensionamento responsivo em orientação paisagem no mobile
     * 
     * @return string Código JavaScript
     */
    public static function getResponsiveOrientationScript() {
        return '
        <script>
        // Ajuste responsivo para mudanças de orientação em dispositivos móveis
        (function() {
            function adjustModelViewersForOrientation() {
                const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
                               (window.innerWidth <= 768);
                
                if (isMobile && window.modelViewers) {
                    // Para cada visualizador registrado
                    for (const viewerId in window.modelViewers) {
                        if (window.modelViewers.hasOwnProperty(viewerId)) {
                            const viewer = window.modelViewers[viewerId];
                            if (viewer && viewer.onWindowResize) {
                                // Forçar atualização do tamanho do visualizador
                                viewer.onWindowResize();
                                
                                // Para dispositivos iOS que às vezes não redimensionam corretamente
                                setTimeout(() => {
                                    viewer.onWindowResize();
                                }, 500);
                            }
                        }
                    }
                }
            }
            
            // Observar mudanças de orientação
            window.addEventListener("orientationchange", adjustModelViewersForOrientation);
            window.addEventListener("resize", adjustModelViewersForOrientation);
        })();
        </script>
        ';
    }
    
    /**
     * Retorna código para script de otimização do visualizador
     * 
     * @return string Código JavaScript
     */
    public static function getOptimizationScript() {
        return '
        <script>
        // Script para otimização do visualizador 3D
        (function() {
            // Verifica se as otimizações estão habilitadas no localStorage
            function areOptimizationsEnabled() {
                return localStorage.getItem("tavernaOptimizerEnabled") !== "false";
            }
            
            // Ativa/desativa as otimizações
            function toggleOptimizations(enable) {
                localStorage.setItem("tavernaOptimizerEnabled", enable ? "true" : "false");
                // Recarregar a página para aplicar a configuração
                window.location.reload();
            }
            
            // Exibe um botão de controle de otimizações no canto inferior direito (apenas em desenvolvimento)
            function addOptimizationToggle() {
                if (window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1") {
                    const toggleBtn = document.createElement("button");
                    toggleBtn.innerHTML = areOptimizationsEnabled() ? "Desativar Otimizações 3D" : "Ativar Otimizações 3D";
                    toggleBtn.style.position = "fixed";
                    toggleBtn.style.bottom = "10px";
                    toggleBtn.style.right = "10px";
                    toggleBtn.style.zIndex = "9999";
                    toggleBtn.style.padding = "5px 10px";
                    toggleBtn.style.background = areOptimizationsEnabled() ? "#4CAF50" : "#f44336";
                    toggleBtn.style.color = "white";
                    toggleBtn.style.border = "none";
                    toggleBtn.style.borderRadius = "4px";
                    toggleBtn.style.cursor = "pointer";
                    toggleBtn.style.fontSize = "12px";
                    
                    toggleBtn.addEventListener("click", function() {
                        toggleOptimizations(!areOptimizationsEnabled());
                    });
                    
                    document.body.appendChild(toggleBtn);
                }
            }
            
            // Adicionar toggle ao carregar a página (apenas em desenvolvimento)
            window.addEventListener("DOMContentLoaded", addOptimizationToggle);
        })();
        </script>
        ';
    }
    
    /**
     * Retorna código para inicializar o sistema de cache de modelos 3D
     * 
     * @param array $options Opções para o sistema de cache
     * @return string Código JavaScript
     */
    public static function getModelCacheScript($options = []) {
        // Opções padrão
        $defaultOptions = [
            'debug' => false,
            'maxCacheSize' => 50 * 1024 * 1024, // 50MB
            'maxEntries' => 100,
            'expirationTime' => 30 * 24 * 60 * 60 * 1000, // 30 dias em ms
            'version' => '1.0.0'
        ];
        
        // Atualizar versão se a classe ModelCacheHelper estiver disponível
        if (class_exists('ModelCacheHelper')) {
            $defaultOptions['version'] = ModelCacheHelper::getVersion();
        }
        
        // Mesclar opções
        $options = array_merge($defaultOptions, $options);
        
        // Converter opções para JSON
        $optionsJson = json_encode($options);
        
        return '
        <script>
        // Inicialização do sistema de cache de modelos 3D
        document.addEventListener("DOMContentLoaded", function() {
            // Verificar suporte a cache no navegador
            const indexedDBSupported = window.indexedDB || window.mozIndexedDB || window.webkitIndexedDB || window.msIndexedDB;
            
            if (indexedDBSupported || window.localStorage) {
                // Criar instância do gerenciador de cache
                window.modelCacheManager = new ModelCacheManager(' . $optionsJson . ');
                
                // Informações de depuração
                if (' . ($options['debug'] ? 'true' : 'false') . ') {
                    window.modelCacheManager.getCacheStats().then(stats => {
                        console.log("ModelCacheManager: Cache inicializado", stats);
                    });
                }
            }
        });
        </script>
        ';
    }
}