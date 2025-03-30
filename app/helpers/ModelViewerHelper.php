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
     * @return string Código HTML para incluir as bibliotecas
     */
    public static function includeThreeJs() {
        return '
        <!-- Three.js e extensões -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/STLLoader.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/OBJLoader.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/MTLLoader.min.js"></script>
        <script src="' . BASE_URL . 'assets/js/model-viewer.js"></script>
        ';
    }
    
    /**
     * Retorna o código HTML para um container de visualização 3D
     * 
     * @param string $id ID único para o visualizador
     * @param string $filePath Caminho para o arquivo 3D
     * @param string $fileType Tipo de arquivo (stl ou obj)
     * @param array $options Opções adicionais para o visualizador
     * @return string Código HTML para o visualizador
     */
    public static function createViewer($id, $filePath, $fileType, $options = []) {
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
        
        // Gerar código para o container
        $containerStyle = "width: {$options['width']}; height: {$options['height']}; background-color: {$options['backgroundColor']};";
        $containerHtml = "<div id=\"{$id}\" class=\"{$options['class']}\" style=\"{$containerStyle}\"></div>";
        
        // Gerar código JavaScript para inicializar o visualizador
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
                    enablePan: " . ($options['enablePan'] ? 'true' : 'false') . "
                });
            } else {
                console.error('ModelViewer não está definido. Verifique se o script model-viewer.js foi carregado corretamente.');
            }
        });
        </script>
        ";
        
        // Retornar HTML final
        return $containerHtml . $initScript;
    }
    
    /**
     * Retorna o código HTML para um visualizador de modelos do cliente
     * 
     * @param array $model Dados do modelo do cliente
     * @param array $options Opções adicionais para o visualizador
     * @return string Código HTML para o visualizador
     */
    public static function createCustomerModelViewer($model, $options = []) {
        // Verificar se o modelo é válido
        if (!isset($model['id']) || !isset($model['file_name']) || !isset($model['file_type'])) {
            return '<div class="alert alert-warning">Informações do modelo incompletas para visualização 3D.</div>';
        }
        
        // Construir caminho para o arquivo
        $filePath = BASE_URL . 'uploads/3d_models/' . $model['file_name'];
        
        // Opções padrão específicas para modelos de cliente
        $defaultOptions = [
            'height' => '350px',
            'backgroundColor' => '#f0f0f0',
            'modelColor' => '#3d7b9c',
            'showControls' => true,
            'autoRotate' => true,
            'class' => 'customer-model-viewer',
        ];
        
        // Mesclar com opções fornecidas
        $options = array_merge($defaultOptions, $options);
        
        // Gerar ID único para o visualizador
        $id = 'customer-model-viewer-' . $model['id'];
        
        // Obter visualizador
        return self::createViewer($id, $filePath, $model['file_type'], $options);
    }
    
    /**
     * Retorna o código HTML para um visualizador de produtos
     * 
     * @param array $product Dados do produto
     * @param array $options Opções adicionais para o visualizador
     * @return string Código HTML para o visualizador
     */
    public static function createProductModelViewer($product, $options = []) {
        // Verificar se o produto tem arquivo de modelo
        if (!isset($product['model_file']) || empty($product['model_file'])) {
            return '<div class="alert alert-info">Este produto não possui um modelo 3D para visualização.</div>';
        }
        
        // Verificar tipo de arquivo do modelo
        $fileType = pathinfo($product['model_file'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileType), ['stl', 'obj'])) {
            return '<div class="alert alert-warning">Formato de arquivo não suportado para visualização 3D.</div>';
        }
        
        // Construir caminho para o arquivo
        $filePath = BASE_URL . 'uploads/products/models/' . $product['model_file'];
        
        // Opções padrão específicas para produtos
        $defaultOptions = [
            'height' => '400px',
            'backgroundColor' => '#ffffff',
            'modelColor' => '#5a5a5a',
            'showControls' => true,
            'autoRotate' => true,
            'showGrid' => true,
            'class' => 'product-model-viewer',
        ];
        
        // Mesclar com opções fornecidas
        $options = array_merge($defaultOptions, $options);
        
        // Gerar ID único para o visualizador
        $id = 'product-model-viewer-' . $product['id'];
        
        // Obter visualizador
        return self::createViewer($id, $filePath, $fileType, $options);
    }
    
    /**
     * Retorna o CSS para os visualizadores 3D
     * 
     * @return string Código CSS
     */
    public static function getViewerCSS() {
        return '
        <style>
        .model-viewer {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .customer-model-viewer {
            border: 1px solid #ddd;
        }
        
        .product-model-viewer {
            margin-bottom: 1.5rem;
        }
        
        .model-viewer canvas {
            display: block;
            width: 100%;
            height: 100%;
        }
        
        .model-viewer-controls {
            position: absolute;
            bottom: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
            z-index: 100;
        }
        
        .model-viewer-control-btn {
            background-color: rgba(255, 255, 255, 0.7);
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .model-viewer-control-btn:hover {
            background-color: rgba(255, 255, 255, 0.9);
        }
        
        .model-viewer-control-btn i {
            font-size: 18px;
            color: #333;
        }
        
        .model-viewer-loading {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(248, 249, 250, 0.8);
            z-index: 50;
        }
        
        .model-viewer-loading-spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: model-viewer-spin 1s linear infinite;
        }
        
        @keyframes model-viewer-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>
        ';
    }
}
