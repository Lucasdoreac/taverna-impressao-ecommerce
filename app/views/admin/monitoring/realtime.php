<?php
/**
 * View de monitoramento em tempo real
 * 
 * Esta view apresenta visualizações em tempo real do estado do sistema e da fila de impressão,
 * seguindo os guardrails de segurança estabelecidos.
 * 
 * @package App\Views\Admin\Monitoring
 * @version 1.0.0
 * @author Taverna da Impressão
 */

// Garantir que está sendo incluído a partir do controller
if (!defined('BASE_PATH')) {
    http_response_code(403);
    exit('Acesso proibido');
}

// Título da página
$pageTitle = 'Monitoramento em Tempo Real';

// Intervalo de atualização em segundos
$refreshInterval = isset($refresh_interval) ? (int)$refresh_interval : 5;

// Endpoints da API
$apiEndpoints = isset($api_endpoints) ? $api_endpoints : [
    'queue_state' => '/admin/monitoring/api/queue-state',
    'queue_stats' => '/admin/monitoring/api/queue-stats',
    'alerts' => '/admin/monitoring/api/alerts',
    'performance' => '/admin/monitoring/api/performance'
];
?>

<!-- Incluir header administrativo -->
<?php include_once BASE_PATH . '/app/views/admin/partials/header.php'; ?>

<!-- Meta tags específicas -->
<meta name="csrf-token" content="<?= htmlspecialchars(CsrfProtection::getToken(), ENT_QUOTES, 'UTF-8'); ?>">
<meta name="refresh-interval" content="<?= htmlspecialchars($refreshInterval); ?>">

<!-- Estilos específicos para monitoramento em tempo real -->
<style>
.card-header-tabs {
    margin-bottom: -0.5rem;
}
.metric-value {
    font-size: 2.5rem;
    font-weight: 300;
}
.metric-label {
    color: #6c757d;
    font-size: 0.875rem;
}
.status-indicator {
    width: 15px;
    height: 15px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
}
.status-online {
    background-color: #28a745;
}
.status-warning {
    background-color: #ffc107;
}
.status-critical {
    background-color: #dc3545;
}
.status-offline {
    background-color: #6c757d;
}
.realtime-section {
    transition: background-color 0.3s ease;
    position: relative;
}
.realtime-section.update-highlight {
    background-color: rgba(0, 123, 255, 0.1);
}
.last-update-indicator {
    position: absolute;
    top: 8px;
    right: 15px;
    font-size: 11px;
    color: #6c757d;
}
.print-job-card {
    transition: transform 0.2s ease;
}
.print-job-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
}
.job-progress-bar {
    height: 10px;
    border-radius: 5px;
}
</style>

<!-- Conteúdo específico da página -->
<div class="container-fluid px-4">
    <h1 class="mt-4"><?= htmlspecialchars($pageTitle); ?></h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/admin">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="/admin/monitoring">Monitoramento</a></li>
        <li class="breadcrumb-item active">Tempo Real</li>
    </ol>
    
    <!-- Controles gerais -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="auto-refresh" checked>
                            <label class="form-check-label" for="auto-refresh">Atualização automática</label>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <label for="refresh-interval" class="me-2">Intervalo:</label>
                        <select id="refresh-interval" class="form-select form-select-sm" style="width: 100px;">
                            <option value="1" <?= $refreshInterval === 1 ? 'selected' : ''; ?>>1 segundo</option>
                            <option value="3" <?= $refreshInterval === 3 ? 'selected' : ''; ?>>3 segundos</option>
                            <option value="5" <?= $refreshInterval === 5 ? 'selected' : ''; ?>>5 segundos</option>
                            <option value="10" <?= $refreshInterval === 10 ? 'selected' : ''; ?>>10 segundos</option>
                            <option value="30" <?= $refreshInterval === 30 ? 'selected' : ''; ?>>30 segundos</option>
                        </select>
                        <button id="manual-refresh" class="btn btn-primary btn-sm ms-3">
                            <i class="fas fa-sync-alt"></i> Atualizar agora
                        </button>
                    </div>
                    <div>
                        <span id="real-time-clock" class="badge bg-dark">--:--:--</span>
                        <span id="connection-status" class="badge bg-success ms-2">Conectado</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted me-2">Status do Sistema:</span>
                        <span id="system-status-indicator" class="status-indicator status-online"></span>
                        <span id="system-status-text" class="fw-bold">ONLINE</span>
                    </div>
                    <div>
                        <span class="text-muted me-2">Carga:</span>
                        <div class="progress" style="width: 150px; height: 10px;">
                            <div id="system-load-bar" class="progress-bar bg-success" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <span id="system-load-text" class="ms-2">25%</span>
                    </div>
                    <div>
                        <span class="text-muted me-2">Memória:</span>
                        <div class="progress" style="width: 150px; height: 10px;">
                            <div id="system-memory-bar" class="progress-bar bg-info" role="progressbar" style="width: 40%;" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <span id="system-memory-text" class="ms-2">40%</span>
                    </div>
                    <div>
                        <button id="toggle-advanced-view" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-chart-line"></i> Visualização Avançada
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Layout principal: Duas colunas em desktop, empilhadas em mobile -->
    <div class="row">
        <!-- Coluna esquerda: Visão geral -->
        <div class="col-lg-6">
            <!-- Status da Fila de Impressão -->
            <div class="card mb-4 realtime-section" id="queue-status-section">
                <div class="card-header">
                    <i class="fas fa-list me-1"></i>
                    Status da Fila de Impressão
                    <span class="last-update-indicator">Atualizado: <span id="queue-status-update-time">Agora</span></span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="metric-value" id="pending-jobs-count">0</div>
                            <div class="metric-label">Pendentes</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="metric-value" id="processing-jobs-count">0</div>
                            <div class="metric-label">Em Processamento</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="metric-value" id="printing-jobs-count">0</div>
                            <div class="metric-label">Em Impressão</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="metric-value" id="completed-jobs-count">0</div>
                            <div class="metric-label">Completados Hoje</div>
                        </div>
                    </div>
                    <hr>
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <h6>Tempo Médio de Espera</h6>
                            <h4 id="avg-wait-time">00:00:00</h4>
                        </div>
                        <div class="col-md-4">
                            <h6>Idade do Item Mais Antigo</h6>
                            <h4 id="oldest-item-age">00:00:00</h4>
                        </div>
                        <div class="col-md-4">
                            <h6>Previsão de Conclusão</h6>
                            <h4 id="completion-forecast">--:--</h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Status de Impressoras -->
            <div class="card mb-4 realtime-section" id="printer-status-section">
                <div class="card-header">
                    <i class="fas fa-print me-1"></i>
                    Status de Impressoras
                    <span class="last-update-indicator">Atualizado: <span id="printer-status-update-time">Agora</span></span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="metric-value" id="total-printers-count">0</div>
                            <div class="metric-label">Total</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="metric-value" id="idle-printers-count">0</div>
                            <div class="metric-label">Disponíveis</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="metric-value" id="busy-printers-count">0</div>
                            <div class="metric-label">Ocupadas</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="metric-value" id="offline-printers-count">0</div>
                            <div class="metric-label">Offline</div>
                        </div>
                    </div>
                    <hr>
                    <div id="printer-list" class="mt-3">
                        <div class="d-flex align-items-center mb-2">
                            <span class="status-indicator status-online"></span>
                            <span class="fw-bold me-2">Printer 1</span>
                            <span class="badge bg-success me-2">Disponível</span>
                            <span class="text-muted small">Última atividade: 10 minutos atrás</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <span class="status-indicator status-warning"></span>
                            <span class="fw-bold me-2">Printer 2</span>
                            <span class="badge bg-warning text-dark me-2">Ocupada (50%)</span>
                            <span class="text-muted small">Imprimindo: Model_XYZ.stl (2h restantes)</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="status-indicator status-offline"></span>
                            <span class="fw-bold me-2">Printer 3</span>
                            <span class="badge bg-secondary me-2">Offline</span>
                            <span class="text-muted small">Último contato: 2 horas atrás</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Alertas Ativos -->
            <div class="card mb-4 realtime-section" id="alerts-section">
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Alertas Ativos
                    <span class="last-update-indicator">Atualizado: <span id="alerts-update-time">Agora</span></span>
                </div>
                <div class="card-body">
                    <div id="alerts-list">
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <div class="me-3">
                                <i class="fas fa-exclamation-circle fa-2x"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="alert-heading">Alerta Crítico: Tempo de Espera</h5>
                                <p class="mb-0">Item na fila há 48h 30m (limite: 48h 00m)</p>
                                <small class="text-muted">10:15:30</small>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-danger acknowledge-alert" data-alert-id="1">
                                    <i class="fas fa-check"></i> Reconhecer
                                </button>
                            </div>
                        </div>
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <div class="me-3">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="alert-heading">Alerta: Impressoras Ociosas</h5>
                                <p class="mb-0">2 impressora(s) ociosa(s) com 10 itens pendentes na fila</p>
                                <small class="text-muted">10:10:15</small>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-warning acknowledge-alert" data-alert-id="2">
                                    <i class="fas fa-check"></i> Reconhecer
                                </button>
                            </div>
                        </div>
                        <div id="no-alerts-message" class="text-center py-3 d-none">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <p class="mb-0">Nenhum alerta ativo no momento</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Coluna direita: Trabalhos ativos e métricas detalhadas -->
        <div class="col-lg-6">
            <!-- Jobs Ativos -->
            <div class="card mb-4 realtime-section" id="active-jobs-section">
                <div class="card-header">
                    <i class="fas fa-tasks me-1"></i>
                    Trabalhos de Impressão Ativos
                    <span class="last-update-indicator">Atualizado: <span id="active-jobs-update-time">Agora</span></span>
                </div>
                <div class="card-body">
                    <div id="active-jobs-list" class="row">
                        <!-- Job Card 1 -->
                        <div class="col-md-6 mb-3">
                            <div class="card print-job-card h-100">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <span>Job #12345</span>
                                    <span class="badge bg-primary">Em Impressão</span>
                                </div>
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-muted">Modelo:</small>
                                        <small class="text-truncate">dragon_statue.stl</small>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-muted">Cliente:</small>
                                        <small>João Silva</small>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-muted">Impressora:</small>
                                        <small>Printer 2</small>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-muted">Tempo restante:</small>
                                        <small>2h 15m</small>
                                    </div>
                                    <div class="mt-2">
                                        <div class="progress job-progress-bar mb-1">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small>Progresso: 50%</small>
                                            <small>Estimativa: 16:45</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer py-1 text-center">
                                    <a href="/admin/print-jobs/12345" class="btn btn-sm btn-link">Ver detalhes</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Job Card 2 -->
                        <div class="col-md-6 mb-3">
                            <div class="card print-job-card h-100">
                                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                    <span>Job #12346</span>
                                    <span class="badge bg-warning text-dark">Em Processamento</span>
                                </div>
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-muted">Modelo:</small>
                                        <small class="text-truncate">mechanical_parts.stl</small>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-muted">Cliente:</small>
                                        <small>Maria Souza</small>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-muted">Prioridade:</small>
                                        <small><span class="badge bg-danger">Alta</span></small>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-muted">Status:</small>
                                        <small>Preparando modelo</small>
                                    </div>
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-between mt-3">
                                            <small>Adicionado: 14:30</small>
                                            <small>Tempo na fila: 30m</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer py-1 text-center">
                                    <a href="/admin/print-jobs/12346" class="btn btn-sm btn-link">Ver detalhes</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Estado vazio -->
                        <div id="no-active-jobs-message" class="col-12 text-center py-5 d-none">
                            <i class="fas fa-print text-muted fa-3x mb-3"></i>
                            <p class="mb-0">Nenhum trabalho ativo no momento</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gráficos de Performance -->
            <div class="card mb-4 realtime-section" id="performance-section">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="performanceTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="queue-performance-tab" data-bs-toggle="tab" data-bs-target="#queue-performance" type="button" role="tab" aria-controls="queue-performance" aria-selected="true">Fila</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="system-performance-tab" data-bs-toggle="tab" data-bs-target="#system-performance" type="button" role="tab" aria-controls="system-performance" aria-selected="false">Sistema</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="printers-performance-tab" data-bs-toggle="tab" data-bs-target="#printers-performance" type="button" role="tab" aria-controls="printers-performance" aria-selected="false">Impressoras</button>
                        </li>
                    </ul>
                    <span class="last-update-indicator">Atualizado: <span id="performance-update-time">Agora</span></span>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="performanceTabContent">
                        <div class="tab-pane fade show active" id="queue-performance" role="tabpanel" aria-labelledby="queue-performance-tab">
                            <canvas id="queue-activity-chart" height="250"></canvas>
                        </div>
                        <div class="tab-pane fade" id="system-performance" role="tabpanel" aria-labelledby="system-performance-tab">
                            <canvas id="system-performance-chart" height="250"></canvas>
                        </div>
                        <div class="tab-pane fade" id="printers-performance" role="tabpanel" aria-labelledby="printers-performance-tab">
                            <canvas id="printers-performance-chart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Logs em Tempo Real -->
            <div class="card mb-4 realtime-section" id="logs-section">
                <div class="card-header">
                    <i class="fas fa-list-alt me-1"></i>
                    Logs em Tempo Real
                    <span class="last-update-indicator">Atualizado: <span id="logs-update-time">Agora</span></span>
                </div>
                <div class="card-body p-0">
                    <div class="bg-dark text-light p-2" style="max-height: 200px; overflow-y: auto; font-family: monospace; font-size: 0.8rem;">
                        <div id="realtime-logs">
                            <div class="log-line"><span class="text-secondary">[10:30:15]</span> <span class="text-success">[INFO]</span> Sistema de fila de impressão iniciado</div>
                            <div class="log-line"><span class="text-secondary">[10:30:20]</span> <span class="text-success">[INFO]</span> 3 impressoras conectadas</div>
                            <div class="log-line"><span class="text-secondary">[10:31:05]</span> <span class="text-warning">[WARN]</span> Impressora 'Printer 3' desconectada</div>
                            <div class="log-line"><span class="text-secondary">[10:32:10]</span> <span class="text-success">[INFO]</span> Trabalho #12345 adicionado à fila</div>
                            <div class="log-line"><span class="text-secondary">[10:33:25]</span> <span class="text-success">[INFO]</span> Trabalho #12345 enviado para impressora 'Printer 2'</div>
                            <div class="log-line"><span class="text-secondary">[10:35:15]</span> <span class="text-success">[INFO]</span> Trabalho #12346 adicionado à fila</div>
                            <div class="log-line"><span class="text-secondary">[10:38:30]</span> <span class="text-success">[INFO]</span> Início do processamento do trabalho #12346</div>
                            <div class="log-line"><span class="text-secondary">[10:40:42]</span> <span class="text-danger">[ERROR]</span> Falha no processamento do arquivo 'broken_model.stl'</div>
                            <div class="log-line"><span class="text-secondary">[10:45:10]</span> <span class="text-success">[INFO]</span> Trabalho #12345 em progresso: 15% concluído</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between py-1">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="auto-scroll-logs" checked>
                        <label class="form-check-label" for="auto-scroll-logs">Auto-scroll</label>
                    </div>
                    <div>
                        <select id="log-filter" class="form-select form-select-sm" style="width: 120px;">
                            <option value="all">Todos</option>
                            <option value="info">Info</option>
                            <option value="warn">Warnings</option>
                            <option value="error">Erros</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script específico para monitoramento em tempo real -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Referências globais
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let refreshInterval = parseInt(document.querySelector('meta[name="refresh-interval"]').getAttribute('content')) || 5;
    let autoRefresh = true;
    let refreshTimer = null;
    let charts = {};
    
    // Elementos do DOM
    const autoRefreshToggle = document.getElementById('auto-refresh');
    const refreshIntervalSelect = document.getElementById('refresh-interval');
    const manualRefreshButton = document.getElementById('manual-refresh');
    const realtimeClock = document.getElementById('real-time-clock');
    const connectionStatus = document.getElementById('connection-status');
    const toggleAdvancedButton = document.getElementById('toggle-advanced-view');
    
    // Constantes de configuração para requisições API
    const API_ENDPOINTS = {
        queueState: '<?= htmlspecialchars($apiEndpoints['queue_state']); ?>',
        queueStats: '<?= htmlspecialchars($apiEndpoints['queue_stats']); ?>',
        alerts: '<?= htmlspecialchars($apiEndpoints['alerts']); ?>',
        performance: '<?= htmlspecialchars($apiEndpoints['performance']); ?>'
    };
    
    // Inicialização
    initializePage();
    
    /**
     * Inicializa a página de monitoramento em tempo real
     */
    function initializePage() {
        // Inicializar relógio
        updateClock();
        setInterval(updateClock, 1000);
        
        // Inicializar gráficos
        initializeCharts();
        
        // Inicializar eventos da UI
        initializeUIEvents();
        
        // Realizar primeiro carregamento de dados
        refreshAllData();
        
        // Iniciar timer de atualização automática
        startAutoRefresh();
    }
    
    /**
     * Inicializa eventos da UI
     */
    function initializeUIEvents() {
        // Toggle de atualização automática
        autoRefreshToggle.addEventListener('change', function() {
            autoRefresh = this.checked;
            if (autoRefresh) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });
        
        // Alteração do intervalo de atualização
        refreshIntervalSelect.addEventListener('change', function() {
            refreshInterval = parseInt(this.value) || 5;
            if (autoRefresh) {
                restartAutoRefresh();
            }
        });
        
        // Botão de atualização manual
        manualRefreshButton.addEventListener('click', function() {
            refreshAllData();
        });
        
        // Botão de visualização avançada
        toggleAdvancedButton.addEventListener('click', function() {
            const isAdvanced = this.classList.contains('active');
            if (isAdvanced) {
                this.classList.remove('active');
                this.classList.remove('btn-primary');
                this.classList.add('btn-outline-secondary');
                this.innerHTML = '<i class="fas fa-chart-line"></i> Visualização Avançada';
                document.querySelectorAll('.advanced-metric').forEach(el => el.classList.add('d-none'));
            } else {
                this.classList.add('active');
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-primary');
                this.innerHTML = '<i class="fas fa-chart-bar"></i> Visualização Simples';
                document.querySelectorAll('.advanced-metric').forEach(el => el.classList.remove('d-none'));
            }
        });
        
        // Auto-scroll de logs
        document.getElementById('auto-scroll-logs').addEventListener('change', function() {
            if (this.checked) {
                scrollLogsToBottom();
            }
        });
        
        // Filtro de logs
        document.getElementById('log-filter').addEventListener('change', function() {
            filterLogs(this.value);
        });
        
        // Reconhecimento de alertas
        document.querySelectorAll('.acknowledge-alert').forEach(button => {
            button.addEventListener('click', function() {
                const alertId = this.getAttribute('data-alert-id');
                acknowledgeAlert(alertId);
            });
        });
    }
    
    /**
     * Inicializa os gráficos
     */
    function initializeCharts() {
        // Inicializar Chart.js com configurações padrão
        const defaultOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 500
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            }
        };
        
        // Cores para os gráficos
        const chartColors = {
            pending: 'rgba(255, 159, 64, 0.8)',
            processing: 'rgba(54, 162, 235, 0.8)',
            printing: 'rgba(75, 192, 192, 0.8)',
            completed: 'rgba(40, 167, 69, 0.8)',
            failed: 'rgba(220, 53, 69, 0.8)',
            responseTime: 'rgba(54, 162, 235, 0.8)',
            memory: 'rgba(255, 99, 132, 0.8)',
            cpu: 'rgba(153, 102, 255, 0.8)',
            printerUtilization: 'rgba(255, 205, 86, 0.8)',
            printerIdle: 'rgba(201, 203, 207, 0.8)'
        };
        
        // Gráfico de atividade da fila
        const queueCtx = document.getElementById('queue-activity-chart');
        if (queueCtx) {
            charts.queueActivity = new Chart(queueCtx, {
                type: 'line',
                data: {
                    labels: generateTimeLabels(12, 5),
                    datasets: [
                        {
                            label: 'Pendentes',
                            data: generateRandomData(12, 5, 20),
                            backgroundColor: chartColors.pending,
                            borderColor: chartColors.pending,
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'Em Processamento',
                            data: generateRandomData(12, 1, 5),
                            backgroundColor: chartColors.processing,
                            borderColor: chartColors.processing,
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'Em Impressão',
                            data: generateRandomData(12, 1, 3),
                            backgroundColor: chartColors.printing,
                            borderColor: chartColors.printing,
                            tension: 0.4,
                            fill: false
                        }
                    ]
                },
                options: {
                    ...defaultOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Número de Itens'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Últimos 60 minutos'
                            }
                        }
                    }
                }
            });
        }
        
        // Gráfico de performance do sistema
        const perfCtx = document.getElementById('system-performance-chart');
        if (perfCtx) {
            charts.systemPerformance = new Chart(perfCtx, {
                type: 'line',
                data: {
                    labels: generateTimeLabels(20, 3),
                    datasets: [
                        {
                            label: 'Tempo de Resposta (ms)',
                            data: generateRandomData(20, 50, 300),
                            backgroundColor: chartColors.responseTime,
                            borderColor: chartColors.responseTime,
                            tension: 0.4,
                            fill: false,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Uso de Memória (MB)',
                            data: generateRandomData(20, 20, 80),
                            backgroundColor: chartColors.memory,
                            borderColor: chartColors.memory,
                            tension: 0.4,
                            fill: false,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    ...defaultOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Tempo (ms)'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: 'Memória (MB)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Últimos 60 segundos'
                            }
                        }
                    }
                }
            });
        }
        
        // Gráfico de performance das impressoras
        const printerCtx = document.getElementById('printers-performance-chart');
        if (printerCtx) {
            charts.printersPerformance = new Chart(printerCtx, {
                type: 'bar',
                data: {
                    labels: ['Printer 1', 'Printer 2', 'Printer 3'],
                    datasets: [
                        {
                            label: 'Utilização (%)',
                            data: [75, 50, 0],
                            backgroundColor: chartColors.printerUtilization,
                            borderColor: chartColors.printerUtilization,
                            borderWidth: 1
                        },
                        {
                            label: 'Tempo de Impressão (h)',
                            data: [3.5, 2, 0],
                            backgroundColor: chartColors.printerIdle,
                            borderColor: chartColors.printerIdle,
                            borderWidth: 1,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    ...defaultOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Utilização (%)'
                            },
                            max: 100
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: 'Tempo (h)'
                            }
                        }
                    }
                }
            });
        }
    }
    
    /**
     * Atualiza todos os dados em tempo real
     */
    function refreshAllData() {
        updateConnectionStatus('conectando');
        
        // Executar todos os fetches em paralelo
        Promise.all([
            fetchQueueState(),
            fetchPerformanceStats(),
            fetchAlerts()
        ])
        .then(() => {
            updateConnectionStatus('conectado');
        })
        .catch(error => {
            console.error('Erro ao atualizar dados:', error);
            updateConnectionStatus('erro');
        });
    }
    
    /**
     * Busca estado da fila
     */
    function fetchQueueState() {
        return fetch(API_ENDPOINTS.queueState + '?update=true', {
            method: 'GET',
            headers: {
                'X-CSRF-Token': csrfToken,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao buscar estado da fila');
            }
            return response.json();
        })
        .then(data => {
            // Atualizar contadores da fila
            updateQueueCounters(data);
            
            // Atualizar contadores de impressoras
            updatePrinterCounters(data);
            
            // Atualizar lista de impressoras
            updatePrintersList(data);
            
            // Atualizar lista de trabalhos ativos
            updateActiveJobsList(data);
            
            // Destacar seção atualizada
            highlightSection('queue-status-section');
            highlightSection('printer-status-section');
            highlightSection('active-jobs-section');
            
            // Atualizar timestamp
            updateSectionTimestamp('queue-status-update-time');
            updateSectionTimestamp('printer-status-update-time');
            updateSectionTimestamp('active-jobs-update-time');
            
            // Adicionar ao log
            addLogEntry('INFO', 'Estado da fila atualizado');
        })
        .catch(error => {
            console.error('Erro ao buscar estado da fila:', error);
            addLogEntry('ERROR', 'Falha ao atualizar estado da fila: ' + error.message);
            throw error;
        });
    }
    
    /**
     * Busca estatísticas de performance
     */
    function fetchPerformanceStats() {
        return fetch(API_ENDPOINTS.performance + '?minutes=60', {
            method: 'GET',
            headers: {
                'X-CSRF-Token': csrfToken,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao buscar estatísticas de performance');
            }
            return response.json();
        })
        .then(data => {
            // Atualizar indicadores de sistema
            updateSystemIndicators(data);
            
            // Atualizar gráficos
            updatePerformanceCharts(data);
            
            // Destacar seção atualizada
            highlightSection('performance-section');
            
            // Atualizar timestamp
            updateSectionTimestamp('performance-update-time');
            
            // Adicionar ao log
            addLogEntry('INFO', 'Estatísticas de performance atualizadas');
        })
        .catch(error => {
            console.error('Erro ao buscar estatísticas de performance:', error);
            addLogEntry('ERROR', 'Falha ao atualizar estatísticas de performance: ' + error.message);
            throw error;
        });
    }
    
    /**
     * Busca alertas ativos
     */
    function fetchAlerts() {
        return fetch(API_ENDPOINTS.alerts, {
            method: 'GET',
            headers: {
                'X-CSRF-Token': csrfToken,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao buscar alertas');
            }
            return response.json();
        })
        .then(data => {
            // Atualizar lista de alertas
            updateAlertsList(data.alerts);
            
            // Destacar seção atualizada
            highlightSection('alerts-section');
            
            // Atualizar timestamp
            updateSectionTimestamp('alerts-update-time');
            
            // Adicionar ao log
            addLogEntry('INFO', `${data.alerts.length} alertas ativos encontrados`);
        })
        .catch(error => {
            console.error('Erro ao buscar alertas:', error);
            addLogEntry('ERROR', 'Falha ao atualizar alertas: ' + error.message);
            throw error;
        });
    }
    
    /**
     * Atualiza contadores da fila
     * 
     * @param {Object} data Dados do servidor
     */
    function updateQueueCounters(data) {
        // Esta implementação é simplificada. Em um ambiente real,
        // você processaria os dados recebidos da API
        
        // Exemplo:
        document.getElementById('pending-jobs-count').textContent = data.pending_items || 0;
        document.getElementById('processing-jobs-count').textContent = data.processing_items || 0;
        document.getElementById('printing-jobs-count').textContent = data.printing_items || 0;
        document.getElementById('completed-jobs-count').textContent = data.completed_items || 0;
        
        // Formatar tempos
        document.getElementById('avg-wait-time').textContent = formatTime(data.average_wait_time || 0);
        document.getElementById('oldest-item-age').textContent = formatTime(data.oldest_item_age || 0);
        
        // Formatação simulada de previsão
        const now = new Date();
        now.setHours(now.getHours() + 2);
        document.getElementById('completion-forecast').textContent = 
            now.getHours().toString().padStart(2, '0') + ':' +
            now.getMinutes().toString().padStart(2, '0');
    }
    
    /**
     * Atualiza contadores de impressoras
     * 
     * @param {Object} data Dados do servidor
     */
    function updatePrinterCounters(data) {
        document.getElementById('total-printers-count').textContent = data.total_printers || 0;
        document.getElementById('idle-printers-count').textContent = data.idle_printers || 0;
        document.getElementById('busy-printers-count').textContent = data.busy_printers || 0;
        document.getElementById('offline-printers-count').textContent = data.offline_printers || 0;
    }
    
    /**
     * Atualiza lista de impressoras
     * 
     * @param {Object} data Dados do servidor
     */
    function updatePrintersList(data) {
        // Esta é uma implementação simulada
        // Em um ambiente real, você receberia uma lista de impressoras do servidor
        
        // Aqui apenas atualizamos o conteúdo existente para demonstração
    }
    
    /**
     * Atualiza lista de trabalhos ativos
     * 
     * @param {Object} data Dados do servidor
     */
    function updateActiveJobsList(data) {
        // Esta é uma implementação simulada
        // Em um ambiente real, você receberia uma lista de trabalhos do servidor
        
        // Aqui apenas verificamos se há trabalhos ativos
        const hasActiveJobs = (data.processing_items || 0) + (data.printing_items || 0) > 0;
        
        if (hasActiveJobs) {
            document.getElementById('no-active-jobs-message')?.classList.add('d-none');
        } else {
            document.getElementById('no-active-jobs-message')?.classList.remove('d-none');
        }
    }
    
    /**
     * Atualiza indicadores de sistema
     * 
     * @param {Object} data Dados do servidor
     */
    function updateSystemIndicators(data) {
        // Esta é uma implementação simulada
        // Em um ambiente real, você receberia métricas do sistema do servidor
        
        // Simular carga do sistema
        const systemLoad = Math.floor(Math.random() * 100);
        const systemMemory = Math.floor(Math.random() * 100);
        
        // Atualizar barras de progresso
        document.getElementById('system-load-bar').style.width = systemLoad + '%';
        document.getElementById('system-load-bar').setAttribute('aria-valuenow', systemLoad);
        document.getElementById('system-load-text').textContent = systemLoad + '%';
        
        document.getElementById('system-memory-bar').style.width = systemMemory + '%';
        document.getElementById('system-memory-bar').setAttribute('aria-valuenow', systemMemory);
        document.getElementById('system-memory-text').textContent = systemMemory + '%';
        
        // Atualizar cor de acordo com a carga
        if (systemLoad > 80) {
            document.getElementById('system-load-bar').className = 'progress-bar bg-danger';
        } else if (systemLoad > 60) {
            document.getElementById('system-load-bar').className = 'progress-bar bg-warning';
        } else {
            document.getElementById('system-load-bar').className = 'progress-bar bg-success';
        }
        
        // Atualizar indicador de status
        const statusIndicator = document.getElementById('system-status-indicator');
        const statusText = document.getElementById('system-status-text');
        
        if (systemLoad > 90) {
            statusIndicator.className = 'status-indicator status-critical';
            statusText.textContent = 'CRÍTICO';
        } else if (systemLoad > 70) {
            statusIndicator.className = 'status-indicator status-warning';
            statusText.textContent = 'ALERTA';
        } else {
            statusIndicator.className = 'status-indicator status-online';
            statusText.textContent = 'ONLINE';
        }
    }
    
    /**
     * Atualiza gráficos de performance
     * 
     * @param {Object} data Dados do servidor
     */
    function updatePerformanceCharts(data) {
        // Esta é uma implementação simulada
        // Em um ambiente real, você receberia dados históricos do servidor
        
        // Atualizar gráfico de atividade da fila com dados simulados
        if (charts.queueActivity) {
            // Remover o primeiro ponto e adicionar um novo no final
            charts.queueActivity.data.datasets.forEach((dataset, i) => {
                const data = dataset.data;
                data.shift();
                data.push(Math.floor(Math.random() * 30) + 5);
            });
            
            // Atualizar labels
            charts.queueActivity.data.labels = generateTimeLabels(12, 5);
            
            // Atualizar gráfico
            charts.queueActivity.update();
        }
        
        // Atualizar gráfico de performance do sistema com dados simulados
        if (charts.systemPerformance) {
            // Remover o primeiro ponto e adicionar um novo no final
            charts.systemPerformance.data.datasets[0].data.shift();
            charts.systemPerformance.data.datasets[0].data.push(Math.floor(Math.random() * 250) + 50);
            
            charts.systemPerformance.data.datasets[1].data.shift();
            charts.systemPerformance.data.datasets[1].data.push(Math.floor(Math.random() * 60) + 20);
            
            // Atualizar labels
            charts.systemPerformance.data.labels = generateTimeLabels(20, 3);
            
            // Atualizar gráfico
            charts.systemPerformance.update();
        }
    }
    
    /**
     * Atualiza lista de alertas
     * 
     * @param {Array} alerts Lista de alertas
     */
    function updateAlertsList(alerts) {
        // Esta é uma implementação simulada
        // Em um ambiente real, você receberia uma lista de alertas do servidor
        
        // Apenas verificar se há alertas ativos
        if (alerts && alerts.length === 0) {
            document.getElementById('no-alerts-message')?.classList.remove('d-none');
        } else {
            document.getElementById('no-alerts-message')?.classList.add('d-none');
        }
    }
    
    /**
     * Reconhece um alerta
     * 
     * @param {string} alertId ID do alerta
     */
    function acknowledgeAlert(alertId) {
        // Esta é uma implementação simulada
        // Em um ambiente real, você enviaria uma requisição para o servidor
        
        // Simular reconhecimento do alerta
        const alertElement = document.querySelector(`.acknowledge-alert[data-alert-id="${alertId}"]`)?.closest('.alert');
        if (alertElement) {
            alertElement.classList.add('fade');
            setTimeout(() => {
                alertElement.remove();
                
                // Verificar se ainda há alertas
                if (document.getElementById('alerts-list').children.length === 0) {
                    document.getElementById('no-alerts-message')?.classList.remove('d-none');
                }
                
                // Adicionar ao log
                addLogEntry('INFO', `Alerta #${alertId} reconhecido`);
            }, 500);
        }
    }
    
    /**
     * Adiciona uma entrada de log
     * 
     * @param {string} level Nível do log (INFO, WARN, ERROR)
     * @param {string} message Mensagem do log
     */
    function addLogEntry(level, message) {
        const logContainer = document.getElementById('realtime-logs');
        if (!logContainer) return;
        
        const now = new Date();
        const timeString = now.getHours().toString().padStart(2, '0') + ':' +
                         now.getMinutes().toString().padStart(2, '0') + ':' +
                         now.getSeconds().toString().padStart(2, '0');
        
        let levelClass = 'text-success';
        if (level === 'WARN') levelClass = 'text-warning';
        if (level === 'ERROR') levelClass = 'text-danger';
        
        const logLine = document.createElement('div');
        logLine.className = `log-line log-${level.toLowerCase()}`;
        logLine.innerHTML = `<span class="text-secondary">[${timeString}]</span> <span class="${levelClass}">[${level}]</span> ${escapeHtml(message)}`;
        
        logContainer.appendChild(logLine);
        
        // Auto-scroll se habilitado
        if (document.getElementById('auto-scroll-logs').checked) {
            scrollLogsToBottom();
        }
        
        // Aplicar filtro atual
        const currentFilter = document.getElementById('log-filter').value;
        if (currentFilter !== 'all' && level.toLowerCase() !== currentFilter.toLowerCase()) {
            logLine.style.display = 'none';
        }
        
        // Destacar seção de logs
        highlightSection('logs-section');
        
        // Atualizar timestamp
        updateSectionTimestamp('logs-update-time');
    }
    
    /**
     * Filtra logs por nível
     * 
     * @param {string} level Nível de log (all, info, warn, error)
     */
    function filterLogs(level) {
        const logLines = document.querySelectorAll('.log-line');
        
        logLines.forEach(line => {
            if (level === 'all') {
                line.style.display = '';
            } else {
                if (line.classList.contains(`log-${level}`)) {
                    line.style.display = '';
                } else {
                    line.style.display = 'none';
                }
            }
        });
    }
    
    /**
     * Rola a área de logs para o final
     */
    function scrollLogsToBottom() {
        const logContainer = document.getElementById('realtime-logs').parentElement;
        logContainer.scrollTop = logContainer.scrollHeight;
    }
    
    /**
     * Destaca uma seção que foi atualizada
     * 
     * @param {string} sectionId ID da seção
     */
    function highlightSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (!section) return;
        
        section.classList.add('update-highlight');
        setTimeout(() => {
            section.classList.remove('update-highlight');
        }, 1000);
    }
    
    /**
     * Atualiza o timestamp de atualização de uma seção
     * 
     * @param {string} elementId ID do elemento
     */
    function updateSectionTimestamp(elementId) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        element.textContent = 'Agora';
        
        // Atualizar para tempo relativo após alguns segundos
        setTimeout(() => {
            element.textContent = 'Há alguns segundos';
        }, 5000);
    }
    
    /**
     * Atualiza o relógio em tempo real
     */
    function updateClock() {
        const now = new Date();
        const timeString = now.getHours().toString().padStart(2, '0') + ':' +
                         now.getMinutes().toString().padStart(2, '0') + ':' +
                         now.getSeconds().toString().padStart(2, '0');
        
        realtimeClock.textContent = timeString;
    }
    
    /**
     * Atualiza o indicador de status de conexão
     * 
     * @param {string} status Status (conectado, conectando, erro)
     */
    function updateConnectionStatus(status) {
        switch (status) {
            case 'conectado':
                connectionStatus.className = 'badge bg-success ms-2';
                connectionStatus.textContent = 'Conectado';
                break;
                
            case 'conectando':
                connectionStatus.className = 'badge bg-warning text-dark ms-2';
                connectionStatus.textContent = 'Atualizando...';
                break;
                
            case 'erro':
                connectionStatus.className = 'badge bg-danger ms-2';
                connectionStatus.textContent = 'Erro de Conexão';
                break;
        }
    }
    
    /**
     * Inicia o timer de atualização automática
     */
    function startAutoRefresh() {
        if (refreshTimer) clearInterval(refreshTimer);
        
        refreshTimer = setInterval(() => {
            if (autoRefresh) {
                refreshAllData();
            }
        }, refreshInterval * 1000);
    }
    
    /**
     * Para o timer de atualização automática
     */
    function stopAutoRefresh() {
        if (refreshTimer) {
            clearInterval(refreshTimer);
            refreshTimer = null;
        }
    }
    
    /**
     * Reinicia o timer de atualização automática
     */
    function restartAutoRefresh() {
        stopAutoRefresh();
        startAutoRefresh();
    }
    
    /**
     * Formata um tempo em segundos para formato legível
     * 
     * @param {number} seconds Tempo em segundos
     * @return {string} Tempo formatado (HH:MM:SS)
     */
    function formatTime(seconds) {
        if (typeof seconds !== 'number') seconds = 0;
        
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);
        
        return hours.toString().padStart(2, '0') + ':' +
               minutes.toString().padStart(2, '0') + ':' +
               secs.toString().padStart(2, '0');
    }
    
    /**
     * Gera labels de tempo para gráficos
     * 
     * @param {number} count Número de rótulos
     * @param {number} intervalMinutes Intervalo em minutos
     * @return {Array} Array de rótulos de tempo
     */
    function generateTimeLabels(count, intervalMinutes) {
        const labels = [];
        const now = new Date();
        
        for (let i = count - 1; i >= 0; i--) {
            const time = new Date(now.getTime() - (i * intervalMinutes * 60000));
            labels.push(
                time.getHours().toString().padStart(2, '0') + ':' +
                time.getMinutes().toString().padStart(2, '0')
            );
        }
        
        return labels;
    }
    
    /**
     * Gera dados aleatórios para fins de demonstração
     * 
     * @param {number} count Número de pontos de dados
     * @param {number} min Valor mínimo
     * @param {number} max Valor máximo
     * @return {Array} Array de dados aleatórios
     */
    function generateRandomData(count, min, max) {
        return Array.from({ length: count }, () => 
            Math.floor(Math.random() * (max - min + 1)) + min
        );
    }
    
    /**
     * Escapa caracteres HTML para prevenir XSS
     * 
     * @param {string} unsafe String potencialmente perigosa
     * @return {string} String segura para inserção no HTML
     */
    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') {
            return '';
        }
        
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
</script>

<!-- Incluir footer administrativo -->
<?php include_once BASE_PATH . '/app/views/admin/partials/footer.php'; ?>