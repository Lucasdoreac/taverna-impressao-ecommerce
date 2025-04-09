<?php
/**
 * View para visualização 3D de modelos
 * 
 * @var array $model Dados do modelo
 * @var bool $isOwner Se o usuário atual é o proprietário do modelo
 * @var bool $isAdmin Se o usuário atual é administrador
 * @var string $csrfToken Token CSRF para operações seguras
 * @var array $viewerConfig Configurações do visualizador
 * @var string $modelType Tipo do modelo (extensão)
 */

// Definir o título da página
$pageTitle = htmlspecialchars($model['original_name']) . ' - Visualizador 3D';
$originalName = htmlspecialchars($model['original_name']);
$metadata = json_decode($model['metadata'], true);

// Inclusão do cabeçalho do site
include_once __DIR__ . '/../shared/header.php';
?>

<main class="viewer-container">
    <div class="model-header">
        <div class="model-info">
            <h1><?= $originalName ?></h1>
            <div class="model-details">
                <span class="model-type"><?= strtoupper($modelType) ?></span>
                <?php if (isset($metadata['file_size'])): ?>
                <span class="model-size"><?= $metadata['file_size_formatted'] ?? number_format($model['file_size'] / 1024 / 1024, 2) . ' MB' ?></span>
                <?php endif; ?>
                <?php if (isset($metadata['format'])): ?>
                <span class="model-format"><?= htmlspecialchars($metadata['format']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="model-actions">
            <?php if ($isOwner || $isAdmin): ?>
            <a href="/viewer3d/download/<?= $model['id'] ?>/<?= $csrfToken ?>" class="btn btn-primary">
                <i class="fas fa-download"></i> Download
            </a>
            <?php endif; ?>
            
            <a href="/customer-models/details/<?= $model['id'] ?>" class="btn btn-secondary">
                <i class="fas fa-info-circle"></i> Detalhes
            </a>
            
            <a href="/customer-models/list" class="btn btn-outline">
                <i class="fas fa-list"></i> Voltar à Lista
            </a>
        </div>
    </div>
    
    <div class="viewer-content">
        <div class="viewer-sidebar">
            <div class="viewer-controls">
                <h3>Controles</h3>
                
                <div class="control-group">
                    <label for="wireframe">Wireframe</label>
                    <div class="toggle-switch">
                        <input type="checkbox" id="wireframe" class="toggle-input">
                        <label for="wireframe" class="toggle-label"></label>
                    </div>
                </div>
                
                <div class="control-group">
                    <label for="grid">Mostrar Grid</label>
                    <div class="toggle-switch">
                        <input type="checkbox" id="grid" class="toggle-input" checked>
                        <label for="grid" class="toggle-label"></label>
                    </div>
                </div>
                
                <div class="control-group">
                    <label for="rotate">Auto-rotação</label>
                    <div class="toggle-switch">
                        <input type="checkbox" id="rotate" class="toggle-input">
                        <label for="rotate" class="toggle-label"></label>
                    </div>
                </div>
                
                <div class="control-group">
                    <label for="lighting">Intensidade da luz</label>
                    <input type="range" id="lighting" min="0" max="2" step="0.1" value="1">
                </div>
                
                <div class="control-group">
                    <label for="bgColor">Cor de fundo</label>
                    <input type="color" id="bgColor" value="#f5f5f5">
                </div>
                
                <button id="resetCamera" class="btn btn-full">
                    <i class="fas fa-sync"></i> Resetar Câmera
                </button>
            </div>
            
            <div class="model-dimensions">
                <h3>Dimensões</h3>
                <?php if (isset($metadata['width']) && isset($metadata['height']) && isset($metadata['depth'])): ?>
                <div class="dimension-item">
                    <span class="dimension-label">Largura:</span>
                    <span class="dimension-value"><?= number_format($metadata['width'], 2) ?> mm</span>
                </div>
                <div class="dimension-item">
                    <span class="dimension-label">Altura:</span>
                    <span class="dimension-value"><?= number_format($metadata['height'], 2) ?> mm</span>
                </div>
                <div class="dimension-item">
                    <span class="dimension-label">Profundidade:</span>
                    <span class="dimension-value"><?= number_format($metadata['depth'], 2) ?> mm</span>
                </div>
                <?php else: ?>
                <p class="dimension-not-available">Informações de dimensão não disponíveis para este modelo.</p>
                <?php endif; ?>
            </div>
            
            <div class="model-metadata">
                <h3>Informações Técnicas</h3>
                <?php if (isset($metadata['triangles']) || isset($metadata['vertices']) || isset($metadata['faces'])): ?>
                <?php if (isset($metadata['triangles'])): ?>
                <div class="metadata-item">
                    <span class="metadata-label">Triângulos:</span>
                    <span class="metadata-value"><?= number_format($metadata['triangles']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (isset($metadata['vertices'])): ?>
                <div class="metadata-item">
                    <span class="metadata-label">Vértices:</span>
                    <span class="metadata-value"><?= number_format($metadata['vertices']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (isset($metadata['faces'])): ?>
                <div class="metadata-item">
                    <span class="metadata-label">Faces:</span>
                    <span class="metadata-value"><?= number_format($metadata['faces']) ?></span>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <p class="metadata-not-available">Informações técnicas não disponíveis para este modelo.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="viewer3d-container" class="viewer-canvas">
            <div id="viewer-loading" class="viewer-loading">
                <div class="spinner"></div>
                <p>Carregando modelo...</p>
            </div>
            <div id="viewer-error" class="viewer-error" style="display: none;">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Erro ao carregar o modelo. Por favor, tente novamente.</p>
            </div>
            <canvas id="viewer3d-canvas"></canvas>
        </div>
    </div>
</main>

<!-- Inclusão dos scripts necessários -->
<script type="text/javascript">
    // Configurações do visualizador (passadas do controlador)
    const viewerConfig = <?= json_encode($viewerConfig) ?>;
</script>

<!-- Carregamento do Three.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/examples/js/controls/OrbitControls.js"></script>

<!-- Carregadores de modelos 3D específicos -->
<?php if ($modelType === 'stl'): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/examples/js/loaders/STLLoader.js"></script>
<?php elseif ($modelType === 'obj'): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/examples/js/loaders/OBJLoader.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/examples/js/loaders/MTLLoader.js"></script>
<?php elseif ($modelType === '3mf'): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/examples/js/loaders/3MFLoader.js"></script>
<?php endif; ?>

<!-- Carregamento do script do visualizador -->
<script src="/assets/js/model-viewer.js"></script>

<style>
    .viewer-container {
        display: flex;
        flex-direction: column;
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .model-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .model-info h1 {
        margin: 0 0 10px 0;
        font-size: 1.8rem;
    }
    
    .model-details {
        display: flex;
        gap: 15px;
    }
    
    .model-type, .model-size, .model-format {
        padding: 3px 8px;
        border-radius: 4px;
        background-color: #f0f0f0;
        font-size: 0.9rem;
    }
    
    .model-actions {
        display: flex;
        gap: 10px;
    }
    
    .viewer-content {
        display: flex;
        height: 80vh;
        min-height: 500px;
        gap: 20px;
    }
    
    .viewer-sidebar {
        flex: 0 0 300px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .viewer-canvas {
        flex: 1;
        position: relative;
        background-color: #f5f5f5;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .viewer-loading, .viewer-error {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        background-color: rgba(245, 245, 245, 0.9);
        z-index: 10;
    }
    
    .viewer-error {
        color: #d32f2f;
    }
    
    .viewer-error i {
        font-size: 48px;
        margin-bottom: 10px;
    }
    
    .spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 15px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .viewer-controls, .model-dimensions, .model-metadata {
        background-color: #f9f9f9;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .viewer-controls h3, .model-dimensions h3, .model-metadata h3 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 1.2rem;
        border-bottom: 1px solid #e0e0e0;
        padding-bottom: 10px;
    }
    
    .control-group {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .toggle-switch {
        position: relative;
        width: 50px;
        height: 24px;
    }
    
    .toggle-input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .toggle-label {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }
    
    .toggle-label:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    .toggle-input:checked + .toggle-label {
        background-color: #3498db;
    }
    
    .toggle-input:checked + .toggle-label:before {
        transform: translateX(26px);
    }
    
    #lighting, #bgColor {
        width: 100px;
    }
    
    .btn {
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s;
    }
    
    .btn i {
        font-size: 0.9em;
    }
    
    .btn-primary {
        background-color: #3498db;
        color: white;
        border: none;
    }
    
    .btn-primary:hover {
        background-color: #2980b9;
    }
    
    .btn-secondary {
        background-color: #2ecc71;
        color: white;
        border: none;
    }
    
    .btn-secondary:hover {
        background-color: #27ae60;
    }
    
    .btn-outline {
        background-color: transparent;
        color: #555;
        border: 1px solid #ccc;
    }
    
    .btn-outline:hover {
        background-color: #f5f5f5;
        border-color: #aaa;
    }
    
    .btn-full {
        width: 100%;
        justify-content: center;
        padding: 10px;
    }
    
    .dimension-item, .metadata-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .dimension-not-available, .metadata-not-available {
        color: #777;
        font-style: italic;
        font-size: 0.9em;
    }
    
    #viewer3d-canvas {
        width: 100%;
        height: 100%;
        display: block;
    }
    
    /* Responsividade */
    @media (max-width: 992px) {
        .viewer-content {
            flex-direction: column;
            height: auto;
        }
        
        .viewer-sidebar {
            flex: none;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .viewer-canvas {
            height: 60vh;
        }
    }
    
    @media (max-width: 768px) {
        .model-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .model-actions {
            margin-top: 15px;
        }
    }
</style>

<?php
// Inclusão do rodapé do site
include_once __DIR__ . '/../shared/footer.php';
?>
