<?php
/**
 * test-mobile-viewer.php
 * 
 * Página específica para testes do visualizador 3D em dispositivos móveis
 * utilizando o checklist definido em docs/mobile-testing-checklist.json
 */

// Incluir configurações e helpers
require_once '../app/config/config.php';
require_once APP_PATH . '/helpers/ModelViewerHelper.php';
require_once APP_PATH . '/helpers/WebGLDetector.php';

// Obter teste específico se fornecido
$testCase = isset($_GET['test']) ? $_GET['test'] : null;

// Configurações de teste avançado
$advancedTesting = isset($_GET['advanced']) && $_GET['advanced'] == '1';

// Obter modelos de teste disponíveis
$testModels = [
    'small' => [
        'path' => 'assets/models/test-cube.stl',
        'type' => 'stl',
        'name' => 'Cubo Simples (Modelo Pequeno)',
        'size' => '< 1MB',
        'complexity' => 'Simples - 12 triângulos'
    ],
    'medium' => [
        'path' => 'assets/models/test-medium.stl',
        'type' => 'stl',
        'name' => 'Modelo de Complexidade Média',
        'size' => '~3MB',
        'complexity' => 'Médio - ~15.000 triângulos'
    ],
    'complex' => [
        'path' => 'assets/models/test-complex.obj',
        'type' => 'obj',
        'name' => 'Modelo Complexo',
        'size' => '~10MB',
        'complexity' => 'Complexo - ~50.000 triângulos'
    ]
];

// Selecionar modelo para teste
$currentModel = isset($_GET['model']) ? $_GET['model'] : 'small';
if (!array_key_exists($currentModel, $testModels)) {
    $currentModel = 'small';
}

// Modelo selecionado
$model = $testModels[$currentModel];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Teste de Visualizador 3D - Dispositivos Móveis</title>
    
    <!-- Incluir CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Incluir Three.js e extensões -->
    <?= ModelViewerHelper::includeThreeJs() ?>
    
    <!-- Incluir WebGL Detector -->
    <?= WebGLDetector::getDetectionScript() ?>
    <?= WebGLDetector::getFallbackCSS() ?>
    
    <style>
        body {
            padding-top: 65px;
            position: relative;
            min-height: 100vh;
        }
        
        .header-fixed {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .model-container {
            width: 100%;
            height: 50vh;
            background-color: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        
        .test-info {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: rgba(0,0,0,0.5);
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 100;
        }
        
        .test-controls {
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .model-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .model-selector .btn {
            flex: 1;
            min-width: 120px;
            white-space: normal;
            text-align: left;
            padding: 8px 12px;
        }
        
        .performance-metrics {
            background-color: rgba(0,0,0,0.7);
            color: #fff;
            position: absolute;
            bottom: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            z-index: 100;
        }
        
        .test-result-form {
            margin-top: 20px;
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .test-cases-list {
            margin-top: 20px;
        }
        
        .test-case-item {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .test-case-item h5 {
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        
        .step-list {
            list-style-type: decimal;
            padding-left: 20px;
        }
        
        .device-info {
            margin-top: 20px;
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        /* Estilos para exibição de gráficos/métricas */
        .chart-container {
            height: 150px;
            position: relative;
        }
    </style>
</head>
<body>
    <!-- Cabeçalho fixo -->
    <div class="header-fixed">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center py-2">
                <h4>Teste de Visualizador 3D</h4>
                <div>
                    <a href="<?= BASE_URL ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                    <?php if (!$advancedTesting): ?>
                    <a href="?advanced=1" class="btn btn-primary btn-sm">
                        <i class="bi bi-gear-fill"></i> Modo Avançado
                    </a>
                    <?php else: ?>
                    <a href="?" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-counterclockwise"></i> Modo Básico
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <!-- Visualizador 3D -->
        <div class="model-container mb-3" id="model-viewer-container">
            <div class="test-info">
                <div id="model-info">
                    Modelo: <?= $model['name'] ?> (<?= $model['complexity'] ?>)
                </div>
                <div id="test-type">
                    <?php if ($testCase): ?>
                    Teste: <?= htmlspecialchars($testCase) ?>
                    <?php else: ?>
                    Modo: Teste livre
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Container para métricas de performance -->
            <div class="performance-metrics" id="performance-metrics">
                Carregando métricas...
            </div>
        </div>
        
        <!-- Seletor de modelos -->
        <div class="test-controls">
            <h5>Selecionar Modelo para Teste</h5>
            <div class="model-selector">
                <?php foreach ($testModels as $key => $testModel): ?>
                <a href="?model=<?= $key ?><?= $advancedTesting ? '&advanced=1' : '' ?>" 
                   class="btn <?= $currentModel == $key ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    <strong><?= $testModel['name'] ?></strong><br>
                    <small><?= $testModel['size'] ?> - <?= $testModel['complexity'] ?></small>
                </a>
                <?php endforeach; ?>
            </div>
            
            <?php if ($advancedTesting): ?>
            <div class="advanced-controls">
                <h5>Controles Avançados</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Qualidade de Renderização</label>
                            <select class="form-select" id="quality-setting">
                                <option value="auto">Automática (detectada pelo dispositivo)</option>
                                <option value="high">Alta (detalhes máximos)</option>
                                <option value="medium">Média</option>
                                <option value="low">Baixa (otimizada para performance)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Simulação de Conexão</label>
                            <select class="form-select" id="connection-simulation">
                                <option value="normal">Normal</option>
                                <option value="fast-3g">3G Rápido</option>
                                <option value="slow-3g">3G Lento</option>
                                <option value="2g">2G</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="toggle-stats" checked>
                        <label class="form-check-label" for="toggle-stats">Mostrar métricas de performance</label>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <button class="btn btn-warning btn-sm" id="simulate-memory-pressure">
                            <i class="bi bi-memory"></i> Simular Pressão de Memória
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-danger btn-sm" id="test-webgl-fallback">
                            <i class="bi bi-exclamation-triangle"></i> Testar Fallback WebGL
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Gráficos de performance (somente no modo avançado) -->
            <div class="mt-4">
                <h5>Métricas de Performance</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">FPS</div>
                            <div class="card-body">
                                <div class="chart-container" id="fps-chart"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">Uso de Memória</div>
                            <div class="card-body">
                                <div class="chart-container" id="memory-chart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!$testCase): ?>
        <!-- Lista de Casos de Teste (quando não está executando um teste específico) -->
        <div class="test-cases-list">
            <h5>Casos de Teste Disponíveis</h5>
            
            <!-- TC001: Carregar modelo pequeno -->
            <div class="test-case-item">
                <h5>TC001: Carregar modelo pequeno (< 1MB)</h5>
                <p><strong>Descrição:</strong> Verifica o carregamento de um modelo 3D simples e pequeno.</p>
                <p><strong>Passos:</strong></p>
                <ol class="step-list">
                    <li>Abrir página do produto em dispositivo móvel</li>
                    <li>Verificar o carregamento do modelo 3D</li>
                    <li>Medir tempo de carregamento e uso de memória</li>
                </ol>
                <p><strong>Resultado Esperado:</strong> Modelo carrega em menos de 5 segundos com frame rate estável</p>
                <a href="?test=TC001&model=small<?= $advancedTesting ? '&advanced=1' : '' ?>" class="btn btn-primary btn-sm">Executar Teste</a>
            </div>
            
            <!-- TC002: Verificar gestos de interação -->
            <div class="test-case-item">
                <h5>TC002: Verificar gestos de interação</h5>
                <p><strong>Descrição:</strong> Testa a resposta do visualizador 3D aos gestos touch.</p>
                <p><strong>Passos:</strong></p>
                <ol class="step-list">
                    <li>Carregar modelo 3D</li>
                    <li>Testar rotação com um dedo</li>
                    <li>Testar zoom com dois dedos</li>
                    <li>Testar pan com dois dedos</li>
                </ol>
                <p><strong>Resultado Esperado:</strong> Todas as interações responsivas sem travamentos</p>
                <a href="?test=TC002&model=medium<?= $advancedTesting ? '&advanced=1' : '' ?>" class="btn btn-primary btn-sm">Executar Teste</a>
            </div>
            
            <!-- TC003: Testar modelo complexo -->
            <div class="test-case-item">
                <h5>TC003: Testar modelo complexo (> 5MB)</h5>
                <p><strong>Descrição:</strong> Verifica o desempenho do visualizador com um modelo 3D complexo.</p>
                <p><strong>Passos:</strong></p>
                <ol class="step-list">
                    <li>Abrir página com modelo complexo</li>
                    <li>Verificar performance durante rotação</li>
                    <li>Medir uso de memória e frame rate</li>
                </ol>
                <p><strong>Resultado Esperado:</strong> Modelo carrega em menos de 10 segundos, mantém pelo menos 30fps</p>
                <a href="?test=TC003&model=complex<?= $advancedTesting ? '&advanced=1' : '' ?>" class="btn btn-primary btn-sm">Executar Teste</a>
            </div>
            
            <!-- TC004: Verificar adaptação ao girar dispositivo -->
            <div class="test-case-item">
                <h5>TC004: Verificar adaptação ao girar dispositivo</h5>
                <p><strong>Descrição:</strong> Testa a adaptação da visualização quando o dispositivo é girado.</p>
                <p><strong>Passos:</strong></p>
                <ol class="step-list">
                    <li>Carregar modelo 3D</li>
                    <li>Girar dispositivo para modo paisagem</li>
                    <li>Girar dispositivo para modo retrato</li>
                </ol>
                <p><strong>Resultado Esperado:</strong> Visualizador se adapta imediatamente, mantendo o modelo centralizado</p>
                <a href="?test=TC004&model=medium<?= $advancedTesting ? '&advanced=1' : '' ?>" class="btn btn-primary btn-sm">Executar Teste</a>
            </div>
            
            <!-- TC005: Testar fallback em dispositivo sem WebGL -->
            <div class="test-case-item">
                <h5>TC005: Testar fallback em dispositivo sem WebGL</h5>
                <p><strong>Descrição:</strong> Verifica o comportamento quando WebGL não está disponível.</p>
                <p><strong>Passos:</strong></p>
                <ol class="step-list">
                    <li>Desabilitar WebGL nas configurações do navegador</li>
                    <li>Abrir página do produto com modelo 3D</li>
                    <li>Verificar comportamento</li>
                </ol>
                <p><strong>Resultado Esperado:</strong> Sistema exibe mensagem de incompatibilidade e oferece alternativa (imagens estáticas)</p>
                <a href="?test=TC005&model=small<?= $advancedTesting ? '&advanced=1' : '' ?>" class="btn btn-primary btn-sm">Executar Teste</a>
            </div>
        </div>
        <?php else: ?>
        <!-- Formulário de Resultados de Teste (quando está executando um teste específico) -->
        <div class="test-result-form">
            <h5>Resultados do Teste: <?= htmlspecialchars($testCase) ?></h5>
            
            <form id="test-result-form" method="post" action="test-results-save.php">
                <input type="hidden" name="test_case" value="<?= htmlspecialchars($testCase) ?>">
                <input type="hidden" name="device_info" id="device-info-json">
                <input type="hidden" name="model" value="<?= htmlspecialchars($currentModel) ?>">
                
                <div class="mb-3">
                    <label class="form-label">Dispositivo</label>
                    <input type="text" class="form-control" name="device" placeholder="Ex: iPhone 12, Samsung Galaxy S21">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Navegador</label>
                    <input type="text" class="form-control" name="browser" placeholder="Ex: Chrome, Safari">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Tempo de Carregamento</label>
                    <div class="input-group">
                        <input type="number" step="0.1" class="form-control" name="load_time" id="measured-load-time">
                        <span class="input-group-text">segundos</span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Frame Rate (FPS)</label>
                    <div class="input-group">
                        <input type="number" step="0.1" class="form-control" name="fps" id="measured-fps">
                        <span class="input-group-text">FPS</span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Resultado do Teste</label>
                    <select class="form-select" name="test_result">
                        <option value="pass">Passou</option>
                        <option value="fail">Falhou</option>
                        <option value="partial">Passou Parcialmente</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Observações</label>
                    <textarea class="form-control" name="observations" rows="3" placeholder="Descreva qualquer problema ou observação relevante"></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="?" class="btn btn-outline-secondary">Voltar para Lista de Testes</a>
                    <button type="submit" class="btn btn-success">Salvar Resultados</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Informações do Dispositivo -->
        <div class="device-info">
            <h5>Informações do Dispositivo</h5>
            <div id="device-info-display">Carregando informações do dispositivo...</div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Instanciar visualizador 3D
        const modelViewer = new ModelViewer({
            containerId: 'model-viewer-container',
            filePath: '<?= BASE_URL . $model['path'] ?>',
            fileType: '<?= $model['type'] ?>',
            backgroundColor: '#f8f9fa',
            modelColor: '#6c757d',
            autoRotate: true,
            showGrid: true,
            showControls: true,
            showStats: true,
            optimizeForMobile: true,
            progressiveLoading: true,
            adaptiveQuality: true
        });
        
        // Definir tempo de início para medir carregamento
        const startTime = performance.now();
        let loadComplete = false;
        let loadTime = 0;
        
        // Métricas de performance
        const performanceMetrics = {
            fps: [],
            memory: [],
            loadTime: 0,
            triangles: 0
        };
        
        // Atualizar métricas periodicamente
        let lastTime = performance.now();
        let frameCount = 0;
        
        function updateMetrics() {
            // Incrementar contador de frames
            frameCount++;
            
            // Calcular FPS a cada segundo
            const now = performance.now();
            const elapsed = now - lastTime;
            
            if (elapsed >= 1000) {
                const fps = Math.round((frameCount * 1000) / elapsed);
                performanceMetrics.fps.push(fps);
                
                // Limitador para histórico (manter apenas últimos 60 valores)
                if (performanceMetrics.fps.length > 60) {
                    performanceMetrics.fps.shift();
                }
                
                // Reset para próxima medição
                frameCount = 0;
                lastTime = now;
                
                // Atualizar métricas exibidas
                updateMetricsDisplay();
                
                // Se estamos no modo avançado, atualizar gráficos
                if (document.getElementById('fps-chart')) {
                    updatePerformanceCharts();
                }
                
                // Atualizar campos do formulário de teste se presente
                if (document.getElementById('measured-fps')) {
                    // Calcular média dos últimos 10 valores de FPS
                    const recentFps = performanceMetrics.fps.slice(-10);
                    const avgFps = recentFps.reduce((a, b) => a + b, 0) / recentFps.length;
                    document.getElementById('measured-fps').value = Math.round(avgFps);
                }
                
                // Se a carga terminou, mas ainda não registramos o tempo
                if (!loadComplete && window.modelViewers && window.modelViewers['model-viewer-container']) {
                    loadComplete = true;
                    loadTime = (now - startTime) / 1000;
                    performanceMetrics.loadTime = loadTime;
                    
                    // Atualizar campo de tempo de carregamento no formulário de teste
                    if (document.getElementById('measured-load-time')) {
                        document.getElementById('measured-load-time').value = loadTime.toFixed(1);
                    }
                    
                    // Contar triângulos
                    if (window.modelViewers['model-viewer-container'].countTriangles) {
                        performanceMetrics.triangles = window.modelViewers['model-viewer-container'].countTriangles();
                    }
                }
            }
            
            // Continuar monitorando
            requestAnimationFrame(updateMetrics);
        }
        
        // Iniciar monitoramento
        updateMetrics();
        
        // Função para atualizar exibição de métricas
        function updateMetricsDisplay() {
            const metricsElement = document.getElementById('performance-metrics');
            if (!metricsElement) return;
            
            const lastFps = performanceMetrics.fps.length ? performanceMetrics.fps[performanceMetrics.fps.length - 1] : 'N/A';
            const avgFps = performanceMetrics.fps.length ? 
                Math.round(performanceMetrics.fps.reduce((a, b) => a + b, 0) / performanceMetrics.fps.length) : 'N/A';
            
            let html = `FPS: ${lastFps} (Média: ${avgFps})`;
            
            if (loadComplete) {
                html += `<br>Tempo de Carregamento: ${loadTime.toFixed(1)}s`;
            }
            
            if (performanceMetrics.triangles) {
                html += `<br>Triângulos: ${performanceMetrics.triangles}`;
            }
            
            if (window.tavernaWebGLDetection) {
                const webglInfo = window.tavernaWebGLDetection.getDetectionResults();
                html += `<br>WebGL: v${webglInfo.webGLVersion}`;
            }
            
            metricsElement.innerHTML = html;
        }
        
        // Coletar e exibir informações do dispositivo
        function collectDeviceInfo() {
            const deviceInfo = {
                userAgent: navigator.userAgent,
                platform: navigator.platform,
                screenWidth: window.screen.width,
                screenHeight: window.screen.height,
                devicePixelRatio: window.devicePixelRatio,
                viewport: {
                    width: window.innerWidth,
                    height: window.innerHeight
                },
                touchPoints: navigator.maxTouchPoints || 0,
                connection: navigator.connection ? {
                    type: navigator.connection.effectiveType,
                    downlink: navigator.connection.downlink,
                    rtt: navigator.connection.rtt,
                    saveData: navigator.connection.saveData
                } : 'Não disponível',
                memory: navigator.deviceMemory ? navigator.deviceMemory : 'Não disponível',
                webgl: window.tavernaWebGLDetection ? 
                      window.tavernaWebGLDetection.getDetectionResults() : 
                      'Não disponível'
            };
            
            // Exibir informações do dispositivo
            const deviceInfoElement = document.getElementById('device-info-display');
            if (deviceInfoElement) {
                let html = '<dl class="row">';
                
                html += `<dt class="col-sm-4">Navegador</dt>
                        <dd class="col-sm-8">${deviceInfo.userAgent}</dd>`;
                
                html += `<dt class="col-sm-4">Plataforma</dt>
                        <dd class="col-sm-8">${deviceInfo.platform}</dd>`;
                
                html += `<dt class="col-sm-4">Resolução de Tela</dt>
                        <dd class="col-sm-8">${deviceInfo.screenWidth} x ${deviceInfo.screenHeight} (Pixel Ratio: ${deviceInfo.devicePixelRatio})</dd>`;
                
                html += `<dt class="col-sm-4">Viewport</dt>
                        <dd class="col-sm-8">${deviceInfo.viewport.width} x ${deviceInfo.viewport.height}</dd>`;
                
                html += `<dt class="col-sm-4">Pontos de Toque</dt>
                        <dd class="col-sm-8">${deviceInfo.touchPoints}</dd>`;
                
                if (deviceInfo.webgl !== 'Não disponível') {
                    html += `<dt class="col-sm-4">WebGL</dt>
                            <dd class="col-sm-8">
                                Versão: ${deviceInfo.webgl.webGLVersion}<br>
                                Renderizador: ${deviceInfo.webgl.renderer || 'Não disponível'}<br>
                                Fabricante: ${deviceInfo.webgl.vendor || 'Não disponível'}
                            </dd>`;
                }
                
                if (deviceInfo.connection !== 'Não disponível') {
                    html += `<dt class="col-sm-4">Conexão</dt>
                            <dd class="col-sm-8">
                                Tipo: ${deviceInfo.connection.type}<br>
                                Downlink: ${deviceInfo.connection.downlink} Mbps<br>
                                RTT: ${deviceInfo.connection.rtt} ms
                            </dd>`;
                }
                
                html += '</dl>';
                
                deviceInfoElement.innerHTML = html;
                
                // Salvar para o formulário, se existe
                if (document.getElementById('device-info-json')) {
                    document.getElementById('device-info-json').value = JSON.stringify(deviceInfo);
                }
            }
        }
        
        // Coletar informações do dispositivo
        collectDeviceInfo();
        
        // Inicializar gráficos de performance se estamos em modo avançado
        let fpsChart = null;
        let memoryChart = null;
        
        function initPerformanceCharts() {
            const fpsCtx = document.getElementById('fps-chart');
            const memoryCtx = document.getElementById('memory-chart');
            
            if (!fpsCtx || !memoryCtx) return;
            
            // Configurar gráfico de FPS
            fpsChart = new Chart(fpsCtx, {
                type: 'line',
                data: {
                    labels: Array(30).fill(''),
                    datasets: [{
                        label: 'FPS',
                        data: Array(30).fill(0),
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.2,
                        fill: false
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            suggestedMax: 60
                        }
                    },
                    maintainAspectRatio: false,
                    animation: false
                }
            });
            
            // Configurar gráfico de memória
            memoryChart = new Chart(memoryCtx, {
                type: 'line',
                data: {
                    labels: Array(30).fill(''),
                    datasets: [{
                        label: 'Uso de Memória (MB)',
                        data: Array(30).fill(0),
                        borderColor: 'rgb(255, 99, 132)',
                        tension: 0.2,
                        fill: false
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    maintainAspectRatio: false,
                    animation: false
                }
            });
        }
        
        // Atualizar gráficos de performance
        function updatePerformanceCharts() {
            if (!fpsChart || !memoryChart) return;
            
            // Atualizar dados do gráfico de FPS
            if (performanceMetrics.fps.length > 0) {
                const recentFps = performanceMetrics.fps.slice(-30);
                fpsChart.data.datasets[0].data = recentFps;
                fpsChart.data.labels = Array(recentFps.length).fill('');
                fpsChart.update();
            }
            
            // Simulação de memória para o gráfico (valores fictícios)
            // Na implementação real, usaríamos dados reais se possível
            if (performanceMetrics.fps.length > 0) {
                // Simular um padrão de uso de memória baseado no tempo
                const baseMemory = 50; // 50MB base
                const maxVariation = 20; // até 20MB de variação
                
                // Criar uma série temporal com alguma variação
                const time = Date.now() / 1000; // tempo em segundos
                const variation = Math.sin(time * 0.1) * maxVariation; // variação sinusoidal
                
                // Adicionar ruído à variação
                const noise = Math.random() * 5; // ruído de até 5MB
                
                // Calcular valor simulado de memória
                const memory = baseMemory + variation + noise;
                
                performanceMetrics.memory.push(memory);
                if (performanceMetrics.memory.length > 60) {
                    performanceMetrics.memory.shift();
                }
                
                const recentMemory = performanceMetrics.memory.slice(-30);
                memoryChart.data.datasets[0].data = recentMemory;
                memoryChart.data.labels = Array(recentMemory.length).fill('');
                memoryChart.update();
            }
        }
        
        // Inicializar gráficos se estamos em modo avançado
        if (document.getElementById('fps-chart')) {
            initPerformanceCharts();
            
            // Configurar controles avançados
            if (document.getElementById('quality-setting')) {
                document.getElementById('quality-setting').addEventListener('change', function() {
                    const quality = this.value;
                    if (quality === 'auto') {
                        modelViewer.updateOptions({ adaptiveQuality: true });
                    } else {
                        modelViewer.updateOptions({ adaptiveQuality: false });
                        if (window.modelViewers && window.modelViewers['model-viewer-container']) {
                            window.modelViewers['model-viewer-container'].applyLOD(quality);
                        }
                    }
                });
            }
            
            // Simulação de pressão de memória
            if (document.getElementById('simulate-memory-pressure')) {
                document.getElementById('simulate-memory-pressure').addEventListener('click', function() {
                    // Criar um grande array para consumir memória
                    const arrays = [];
                    try {
                        for (let i = 0; i < 10; i++) {
                            arrays.push(new Array(1000000).fill(Math.random()));
                        }
                        alert('Pressão de memória simulada. Verifique o impacto na performance do visualizador.');
                        
                        // Manter a referência para evitar coleta de lixo durante o teste
                        window._memoryPressureArrays = arrays;
                        
                        // Restaurar após 10 segundos
                        setTimeout(() => {
                            delete window._memoryPressureArrays;
                            alert('Simulação de pressão de memória finalizada.');
                        }, 10000);
                    } catch (e) {
                        alert('Erro ao simular pressão de memória: ' + e.message);
                    }
                });
            }
            
            // Teste de fallback WebGL
            if (document.getElementById('test-webgl-fallback')) {
                document.getElementById('test-webgl-fallback').addEventListener('click', function() {
                    if (confirm('Isso irá simular a ausência de WebGL. Continuar?')) {
                        // Sobrescrever a detecção de WebGL para simular falha
                        window.tavernaWebGLDetection.hasWebGLSupport = function() { return false; };
                        
                        // Recarregar a página para aplicar a simulação
                        location.reload();
                    }
                });
            }
            
            // Toggle de estatísticas
            if (document.getElementById('toggle-stats')) {
                document.getElementById('toggle-stats').addEventListener('change', function() {
                    document.getElementById('performance-metrics').style.display = this.checked ? 'block' : 'none';
                });
            }
        }
    });
    </script>
</body>
</html>
