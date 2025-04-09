<?php
/**
 * View para solicitação de cotação de modelo 3D
 * 
 * Esta view implementa o formulário de cotação automatizada
 * com integração ao visualizador 3D
 * 
 * @var array $model Dados do modelo
 * @var bool $isOwner Se o usuário atual é o proprietário do modelo
 * @var string $csrfToken Token CSRF para operações seguras
 * @var array $materials Materiais disponíveis para cotação
 */

// Definir o título da página
$pageTitle = 'Solicitar Cotação - ' . htmlspecialchars($model['original_name']);

// Inclusão do cabeçalho do site
include_once __DIR__ . '/../shared/header.php';

// Converter metadados
$metadata = is_string($model['metadata']) ? json_decode($model['metadata'], true) : $model['metadata'];
?>

<main class="quotation-container">
    <div class="page-header">
        <h1>Solicitar Cotação</h1>
        <div class="breadcrumbs">
            <a href="/customer-models/list">Meus Modelos</a> &gt;
            <a href="/customer-models/details/<?= $model['id'] ?>"><?= htmlspecialchars($model['original_name']) ?></a> &gt;
            <span>Solicitar Cotação</span>
        </div>
    </div>

    <div class="quotation-layout">
        <!-- Lado esquerdo: Visualizador 3D -->
        <div class="model-viewer-section">
            <div class="viewer-header">
                <h2>Visualização 3D</h2>
                <span class="model-info-badge"><?= strtoupper(pathinfo($model['file_name'], PATHINFO_EXTENSION)) ?></span>
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
            
            <div class="model-dimensions">
                <h3>Dimensões do Modelo</h3>
                <?php if (isset($metadata['width']) && isset($metadata['height']) && isset($metadata['depth'])): ?>
                <div class="dimension-grid">
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
                    <?php if (isset($metadata['triangles'])): ?>
                    <div class="dimension-item">
                        <span class="dimension-label">Triângulos:</span>
                        <span class="dimension-value"><?= number_format($metadata['triangles']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <p class="no-data-message">Informações de dimensão não disponíveis.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Lado direito: Formulário de cotação -->
        <div class="quotation-form-section">
            <div class="form-header">
                <h2>Parâmetros da Cotação</h2>
            </div>
            
            <!-- Loader para cotação rápida -->
            <div id="quick-quote-loader" class="quick-quote-loader" style="display: none;">
                <div class="spinner"></div>
                <p>Calculando cotação rápida...</p>
            </div>
            
            <!-- Resultado da cotação rápida -->
            <div id="quick-quote-result" class="quick-quote-result" style="display: none;">
                <h3>Cotação Rápida (PLA)</h3>
                <div class="quick-quote-info">
                    <div class="quote-price">
                        <span class="price-label">Valor estimado:</span>
                        <span class="price-value">R$ <span id="quick-quote-price">--,--</span></span>
                    </div>
                    <div class="quote-details">
                        <div class="detail-item">
                            <span class="detail-label">Complexidade:</span>
                            <span id="quick-quote-complexity" class="detail-value">--</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Prazo estimado:</span>
                            <span id="quick-quote-days" class="detail-value">-- dias</span>
                        </div>
                    </div>
                </div>
                <p class="quick-quote-disclaimer">Esta é uma estimativa rápida apenas com PLA. Personalize as opções abaixo para uma cotação detalhada.</p>
            </div>
            
            <form id="quotationForm" action="/customer-quotation/process" method="post" class="quotation-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="model_id" value="<?= $model['id'] ?>">
                
                <div class="form-section">
                    <h3>Material de Impressão</h3>
                    <p class="section-description">Escolha o material mais adequado para o seu modelo.</p>
                    
                    <div class="materials-grid">
                        <?php foreach ($materials as $code => $material): ?>
                        <div class="material-option">
                            <input type="radio" name="material" id="material-<?= $code ?>" value="<?= $code ?>" <?= ($code === 'pla') ? 'checked' : '' ?> class="material-radio">
                            <label for="material-<?= $code ?>" class="material-label">
                                <div class="material-icon material-<?= $code ?>"></div>
                                <div class="material-info">
                                    <span class="material-name"><?= htmlspecialchars($material['name']) ?></span>
                                    <span class="material-cost">R$ <?= number_format($material['cost_per_gram'], 2, ',', '.') ?>/g</span>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="material-details">
                        <div id="pla-details" class="material-details-panel active">
                            <h4>PLA (Ácido Polilático)</h4>
                            <p>Material versátil e biodegradável, ideal para a maioria das peças. Oferece bom detalhamento e acabamento estético.</p>
                            <ul>
                                <li><strong>Pontos fortes:</strong> Fácil de imprimir, pouca deformação, variedade de cores</li>
                                <li><strong>Limitações:</strong> Menor resistência térmica e mecânica</li>
                                <li><strong>Ideal para:</strong> Protótipos, modelos decorativos, peças não-estruturais</li>
                            </ul>
                        </div>
                        <div id="abs-details" class="material-details-panel">
                            <h4>ABS (Acrilonitrila Butadieno Estireno)</h4>
                            <p>Material durável e resistente a impactos, ideal para peças funcionais.</p>
                            <ul>
                                <li><strong>Pontos fortes:</strong> Alta resistência mecânica, resistente a temperaturas mais altas</li>
                                <li><strong>Limitações:</strong> Maior contração, pode deformar durante impressão</li>
                                <li><strong>Ideal para:</strong> Peças funcionais, componentes mecânicos, produtos finais</li>
                            </ul>
                        </div>
                        <div id="petg-details" class="material-details-panel">
                            <h4>PETG (Polietileno Tereftalato de Etileno Glicol)</h4>
                            <p>Combina resistência com facilidade de impressão. Boa opção para peças que exigem resistência e estabilidade.</p>
                            <ul>
                                <li><strong>Pontos fortes:</strong> Resistente à água, durabilidade, pouca contração</li>
                                <li><strong>Limitações:</strong> Pode formar filamentos durante a impressão</li>
                                <li><strong>Ideal para:</strong> Recipientes, peças mecânicas, componentes à prova d'água</li>
                            </ul>
                        </div>
                        <div id="flex-details" class="material-details-panel">
                            <h4>TPU (Poliuretano Termoplástico Flexível)</h4>
                            <p>Material flexível e elástico, para peças que precisam dobrar, esticar ou comprimir.</p>
                            <ul>
                                <li><strong>Pontos fortes:</strong> Elasticidade, resistência à abrasão, absorção de impacto</li>
                                <li><strong>Limitações:</strong> Mais difícil de imprimir, velocidade mais lenta</li>
                                <li><strong>Ideal para:</strong> Protetores, peças flexíveis, juntas, selos</li>
                            </ul>
                        </div>
                        <div id="resin-details" class="material-details-panel">
                            <h4>Resina (Fotopolímero)</h4>
                            <p>Oferece detalhes ultra finos e acabamento de superfície superior, ideal para modelos com detalhes complexos.</p>
                            <ul>
                                <li><strong>Pontos fortes:</strong> Resolução extremamente alta, superfície lisa</li>
                                <li><strong>Limitações:</strong> Mais frágil, requer pós-processamento</li>
                                <li><strong>Ideal para:</strong> Miniaturas, joalheria, modelos de alta precisão</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Opções de Impressão</h3>
                    
                    <div class="option-row">
                        <div class="option-label-container">
                            <label class="option-label" for="is_urgent">Impressão Urgente</label>
                            <span class="option-tooltip" title="Prioriza seu pedido, reduzindo o tempo de entrega pela metade, porém com acréscimo no valor.">
                                <i class="fas fa-info-circle"></i>
                            </span>
                        </div>
                        <div class="toggle-switch">
                            <input type="checkbox" name="is_urgent" id="is_urgent" value="1" class="toggle-input">
                            <label for="is_urgent" class="toggle-label"></label>
                        </div>
                    </div>
                    
                    <div class="option-row notes-row">
                        <div class="option-label-container">
                            <label class="option-label" for="notes">Observações</label>
                            <span class="option-tooltip" title="Informe requisitos específicos, acabamentos especiais ou outras considerações para sua impressão.">
                                <i class="fas fa-info-circle"></i>
                            </span>
                        </div>
                        <textarea name="notes" id="notes" rows="3" maxlength="1000" placeholder="Requisitos especiais, acabamento, etc. (opcional)"></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="/customer-models/details/<?= $model['id'] ?>" class="btn btn-outline">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Solicitar Cotação Detalhada</button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- Scripts específicos -->
<script>
    // Configuração do visualizador 3D
    const viewerConfig = {
        modelUrl: '/viewer3d/get-model/<?= $model['id'] ?>/<?= $csrfToken ?>',
        backgroundColor: '#f5f5f5',
        gridEnabled: true,
        controlsEnabled: true,
        wireframeEnabled: false,
        autoRotate: false,
        defaultCameraPosition: [0, 0, 5],
        lightIntensity: 1.0
    };
    
    // Adicionar dimensões do modelo, se disponíveis
    <?php if (isset($metadata['width']) && isset($metadata['height']) && isset($metadata['depth'])): ?>
    const maxDimension = Math.max(
        <?= $metadata['width'] ?? 0 ?>, 
        <?= $metadata['height'] ?? 0 ?>, 
        <?= $metadata['depth'] ?? 0 ?>
    );
    
    // Ajustar posição da câmera com base no tamanho do modelo
    viewerConfig.defaultCameraPosition = [0, 0, maxDimension * 2];
    
    // Adicionar informações de dimensão
    viewerConfig.dimensions = {
        width: <?= $metadata['width'] ?? 0 ?>,
        height: <?= $metadata['height'] ?? 0 ?>,
        depth: <?= $metadata['depth'] ?? 0 ?>
    };
    <?php endif; ?>
    
    // Tipo do modelo
    const modelType = '<?= strtolower(pathinfo($model['file_name'], PATHINFO_EXTENSION)) ?>';
    
    // Função para carregar a cotação rápida
    function loadQuickQuote() {
        const quickQuoteLoader = document.getElementById('quick-quote-loader');
        const quickQuoteResult = document.getElementById('quick-quote-result');
        
        // Mostrar loader
        quickQuoteLoader.style.display = 'flex';
        quickQuoteResult.style.display = 'none';
        
        // Fazer requisição AJAX para o endpoint de cotação rápida
        fetch('/customer-quotation/quick-quote/<?= $model['id'] ?>')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao obter cotação rápida');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Preencher dados da cotação rápida
                    document.getElementById('quick-quote-price').textContent = data.total_cost.toFixed(2).replace('.', ',');
                    
                    // Formatar nível de complexidade
                    let complexityText = 'Desconhecida';
                    switch (data.complexity_level) {
                        case 'simple': complexityText = 'Simples'; break;
                        case 'moderate': complexityText = 'Moderada'; break;
                        case 'complex': complexityText = 'Complexa'; break;
                        case 'very_complex': complexityText = 'Muito Complexa'; break;
                    }
                    document.getElementById('quick-quote-complexity').textContent = complexityText;
                    
                    // Definir prazo de entrega
                    document.getElementById('quick-quote-days').textContent = 
                        data.delivery_days + (data.delivery_days === 1 ? ' dia' : ' dias');
                    
                    // Mostrar resultado
                    quickQuoteResult.style.display = 'block';
                } else {
                    console.error('Erro:', data.error);
                    // Esconder resultado em caso de erro
                    quickQuoteResult.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                // Esconder resultado em caso de erro
                quickQuoteResult.style.display = 'none';
            })
            .finally(() => {
                // Esconder loader
                quickQuoteLoader.style.display = 'none';
            });
    }
    
    // Toggle entre os painéis de detalhes do material
    function showMaterialDetails(materialCode) {
        // Esconder todos os painéis
        document.querySelectorAll('.material-details-panel').forEach(panel => {
            panel.classList.remove('active');
        });
        
        // Mostrar o painel selecionado
        const selectedPanel = document.getElementById(materialCode + '-details');
        if (selectedPanel) {
            selectedPanel.classList.add('active');
        }
    }
    
    // Inicialização quando o DOM estiver carregado
    document.addEventListener('DOMContentLoaded', function() {
        // Carregar cotação rápida
        loadQuickQuote();
        
        // Configurar eventos para troca de material
        document.querySelectorAll('.material-radio').forEach(radio => {
            radio.addEventListener('change', function() {
                showMaterialDetails(this.value);
            });
        });
        
        // Mostrar detalhes do material padrão (PLA)
        showMaterialDetails('pla');
    });
</script>

<!-- Carregamento do Three.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/examples/js/controls/OrbitControls.js"></script>

<!-- Carregadores de modelos 3D específicos -->
<?php $modelType = strtolower(pathinfo($model['file_name'], PATHINFO_EXTENSION)); ?>
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
    .quotation-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .page-header {
        margin-bottom: 30px;
    }
    
    .page-header h1 {
        margin: 0 0 10px 0;
        font-size: 1.8rem;
        color: #333;
    }
    
    .breadcrumbs {
        font-size: 0.9rem;
        color: #777;
    }
    
    .breadcrumbs a {
        color: #3498db;
        text-decoration: none;
    }
    
    .breadcrumbs a:hover {
        text-decoration: underline;
    }
    
    .quotation-layout {
        display: flex;
        gap: 30px;
    }
    
    .model-viewer-section {
        flex: 1;
        min-width: 0;
    }
    
    .quotation-form-section {
        flex: 1;
        min-width: 0;
    }
    
    .viewer-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .viewer-header h2 {
        margin: 0;
        font-size: 1.4rem;
    }
    
    .model-info-badge {
        background-color: #f0f0f0;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: bold;
    }
    
    .viewer-canvas {
        aspect-ratio: 4/3;
        background-color: #f5f5f5;
        border-radius: 8px;
        overflow: hidden;
        position: relative;
    }
    
    .viewer-loading, .viewer-error, .quick-quote-loader {
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
    
    .quick-quote-loader {
        position: relative;
        background-color: #f9f9f9;
        border-radius: 8px;
        height: 200px;
        margin-bottom: 20px;
    }
    
    .viewer-error {
        color: #d32f2f;
    }
    
    .viewer-error i {
        font-size: 48px;
        margin-bottom: 10px;
    }
    
    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 15px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .model-dimensions {
        margin-top: 20px;
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 8px;
    }
    
    .model-dimensions h3 {
        margin-top: 0;
        font-size: 1.1rem;
        margin-bottom: 15px;
    }
    
    .dimension-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
    }
    
    .dimension-item {
        display: flex;
        flex-direction: column;
    }
    
    .dimension-label {
        font-size: 0.9rem;
        color: #777;
    }
    
    .dimension-value {
        font-weight: 500;
    }
    
    .no-data-message {
        color: #777;
        font-style: italic;
    }
    
    .form-header {
        margin-bottom: 20px;
    }
    
    .form-header h2 {
        margin: 0;
        font-size: 1.4rem;
    }
    
    .quick-quote-result {
        background-color: #f1f9fe;
        border: 1px solid #d0e8f9;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 25px;
    }
    
    .quick-quote-result h3 {
        margin-top: 0;
        font-size: 1.1rem;
        margin-bottom: 15px;
        color: #2980b9;
    }
    
    .quick-quote-info {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .quote-price {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 10px;
        border-bottom: 1px solid #d0e8f9;
    }
    
    .price-label {
        font-size: 1rem;
    }
    
    .price-value {
        font-size: 1.3rem;
        font-weight: bold;
        color: #2980b9;
    }
    
    .quote-details {
        display: flex;
        justify-content: space-between;
    }
    
    .detail-item {
        display: flex;
        flex-direction: column;
    }
    
    .detail-label {
        font-size: 0.85rem;
        color: #777;
    }
    
    .detail-value {
        font-weight: 500;
    }
    
    .quick-quote-disclaimer {
        margin: 15px 0 0 0;
        font-size: 0.85rem;
        color: #777;
        font-style: italic;
    }
    
    .quotation-form {
        background-color: #fff;
    }
    
    .form-section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }
    
    .form-section h3 {
        margin-top: 0;
        font-size: 1.2rem;
        margin-bottom: 10px;
    }
    
    .section-description {
        color: #777;
        font-size: 0.9rem;
        margin-bottom: 20px;
    }
    
    .materials-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .material-option {
        position: relative;
    }
    
    .material-radio {
        position: absolute;
        opacity: 0;
        cursor: pointer;
    }
    
    .material-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 15px 10px;
        background-color: #f9f9f9;
        border: 2px solid #eee;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .material-radio:checked + .material-label {
        border-color: #3498db;
        background-color: #edf7fd;
    }
    
    .material-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        margin-bottom: 10px;
        background-position: center;
        background-size: cover;
    }
    
    .material-pla {
        background-color: #2ecc71;
    }
    
    .material-abs {
        background-color: #e74c3c;
    }
    
    .material-petg {
        background-color: #3498db;
    }
    
    .material-flex {
        background-color: #9b59b6;
    }
    
    .material-resin {
        background-color: #f1c40f;
    }
    
    .material-info {
        text-align: center;
    }
    
    .material-name {
        display: block;
        font-weight: 500;
        margin-bottom: 5px;
        font-size: 0.9rem;
    }
    
    .material-cost {
        display: block;
        font-size: 0.8rem;
        color: #777;
    }
    
    .material-details {
        background-color: #f9f9f9;
        border-radius: 8px;
        padding: 15px;
        margin-top: 10px;
    }
    
    .material-details-panel {
        display: none;
    }
    
    .material-details-panel.active {
        display: block;
    }
    
    .material-details-panel h4 {
        margin-top: 0;
        margin-bottom: 10px;
    }
    
    .material-details-panel p {
        margin-bottom: 15px;
    }
    
    .material-details-panel ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .material-details-panel li {
        margin-bottom: 5px;
    }
    
    .option-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .option-label-container {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .option-tooltip {
        color: #777;
        cursor: help;
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
    
    .notes-row {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .notes-row textarea {
        width: 100%;
        margin-top: 10px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        resize: vertical;
        font-family: inherit;
    }
    
    .form-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 30px;
    }
    
    .btn {
        padding: 10px 20px;
        border-radius: 4px;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .btn-primary {
        background-color: #3498db;
        color: white;
        border: none;
    }
    
    .btn-primary:hover {
        background-color: #2980b9;
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
    
    /* Responsividade */
    @media (max-width: 992px) {
        .quotation-layout {
            flex-direction: column;
        }
        
        .materials-grid {
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        }
    }
    
    @media (max-width: 768px) {
        .quote-details {
            flex-direction: column;
            gap: 10px;
        }
    }
</style>

<?php
// Inclusão do rodapé do site
include_once __DIR__ . '/../shared/footer.php';
?>
