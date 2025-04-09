<?php
/**
 * View de monitoramento de sistema do dashboard administrativo
 * 
 * Esta view exibe métricas de desempenho do sistema, incluindo tempo de resposta,
 * uso de recursos, métricas de banco de dados e eventos de segurança.
 */

// Incluir header
include_once APP_PATH . '/views/admin/partials/header.php';
include_once APP_PATH . '/views/admin/partials/sidebar.php';

// Obter token CSRF
$csrfToken = SecurityManager::getCsrfToken();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><?= htmlspecialchars($title) ?></h1>
        <div class="dashboard-period">
            <label for="monitoring-period">Período: </label>
            <select id="monitoring-period" class="form-select" onchange="updateMonitoringPeriod()">
                <option value="3" <?= $hours == 3 ? 'selected' : '' ?>>Últimas 3 horas</option>
                <option value="6" <?= $hours == 6 ? 'selected' : '' ?>>Últimas 6 horas</option>
                <option value="12" <?= $hours == 12 ? 'selected' : '' ?>>Últimas 12 horas</option>
                <option value="24" <?= $hours == 24 ? 'selected' : '' ?>>Últimas 24 horas</option>
                <option value="48" <?= $hours == 48 ? 'selected' : '' ?>>Últimos 2 dias</option>
                <option value="168" <?= $hours == 168 ? 'selected' : '' ?>>Últimos 7 dias</option>
            </select>
            <button id="btn-refresh" class="btn btn-outline-primary ms-2" onclick="refreshData()">
                <i class="fa fa-sync-alt"></i> Atualizar
            </button>
        </div>
    </div>

    <!-- Alertas do sistema -->
    <div id="system-alerts" class="mb-4">
        <!-- Os alertas serão carregados via AJAX -->
    </div>

    <!-- Métricas principais -->
    <div class="row">
        <div class="col-md-3">
            <div class="card metric-card">
                <div class="card-body">
                    <div class="metric-icon">
                        <i class="fa fa-tachometer-alt"></i>
                    </div>
                    <h5 class="card-title">Resposta</h5>
                    <h2 class="metric-value"><?= $performance['avgResponseTime'] ? number_format($performance['avgResponseTime'], 2) : '0' ?> ms</h2>
                    <p class="metric-label">Tempo médio de resposta</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card">
                <div class="card-body">
                    <div class="metric-icon">
                        <i class="fa fa-memory"></i>
                    </div>
                    <h5 class="card-title">Memória</h5>
                    <h2 class="metric-value"><?= $resources['avgMemoryUsage'] ? formatBytes($resources['avgMemoryUsage']) : '0' ?></h2>
                    <p class="metric-label">Uso médio de memória</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card">
                <div class="card-body">
                    <div class="metric-icon">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <h5 class="card-title">Erros</h5>
                    <h2 class="metric-value"><?= number_format($errors['errorRate'], 2) ?>%</h2>
                    <p class="metric-label">Taxa de erros</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card">
                <div class="card-body">
                    <div class="metric-icon">
                        <i class="fa fa-database"></i>
                    </div>
                    <h5 class="card-title">Banco de Dados</h5>
                    <h2 class="metric-value"><?= number_format($databaseMetrics['avgQueryTime'], 2) ?> ms</h2>
                    <p class="metric-label">Tempo médio de consulta</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos e dados detalhados -->
    <div class="dashboard-widgets row mt-4">
        <!-- Gráfico de tempo de resposta -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Tempo de Resposta</h5>
                </div>
                <div class="card-body">
                    <canvas id="responseTimeChart" width="100%" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Distribuição de erros -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Distribuição de Erros</h5>
                </div>
                <div class="card-body">
                    <canvas id="errorDistributionChart" width="100%" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-widgets row mt-4">
        <!-- Uso de recursos -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Utilização de Recursos</h5>
                </div>
                <div class="card-body">
                    <canvas id="resourcesChart" width="100%" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Consultas de banco de dados -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Performance do Banco de Dados</h5>
                </div>
                <div class="card-body">
                    <canvas id="databaseChart" width="100%" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabelas de detalhes -->
    <div class="dashboard-widgets row mt-4">
        <!-- Tabela de erros recentes -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Erros Recentes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Horário</th>
                                    <th>Tipo</th>
                                    <th>URL</th>
                                    <th>Mensagem</th>
                                </tr>
                            </thead>
                            <tbody id="errors-table-body">
                                <?php if (empty($errors['recentErrors'])): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Nenhum erro recente encontrado</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($errors['recentErrors'] as $error): ?>
                                        <tr>
                                            <td><?= date('H:i:s', strtotime($error['timestamp'])) ?></td>
                                            <td><?= htmlspecialchars($error['type']) ?></td>
                                            <td><?= htmlspecialchars($error['url']) ?></td>
                                            <td><?= htmlspecialchars(substr($error['message'], 0, 50)) . (strlen($error['message']) > 50 ? '...' : '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de eventos de segurança -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Eventos de Segurança</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Horário</th>
                                    <th>Tipo</th>
                                    <th>IP</th>
                                    <th>Descrição</th>
                                </tr>
                            </thead>
                            <tbody id="security-events-table-body">
                                <?php if (empty($securityEvents)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Nenhum evento de segurança recente encontrado</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($securityEvents as $event): ?>
                                        <tr>
                                            <td><?= date('H:i:s', strtotime($event['timestamp'])) ?></td>
                                            <td>
                                                <span class="badge bg-<?= getSecurityEventColor($event['type']) ?>">
                                                    <?= htmlspecialchars($event['type']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($event['ip_address']) ?></td>
                                            <td><?= htmlspecialchars(substr($event['description'], 0, 50)) . (strlen($event['description']) > 50 ? '...' : '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Elementos globais
let responseTimeChart = null;
let errorDistributionChart = null;
let resourcesChart = null;
let databaseChart = null;

// Cores para gráficos
const chartColors = {
    blue: 'rgba(78, 115, 223, 0.8)',
    blueFaded: 'rgba(78, 115, 223, 0.2)',
    green: 'rgba(28, 200, 138, 0.8)',
    greenFaded: 'rgba(28, 200, 138, 0.2)',
    red: 'rgba(231, 74, 59, 0.8)',
    redFaded: 'rgba(231, 74, 59, 0.2)',
    yellow: 'rgba(246, 194, 62, 0.8)',
    yellowFaded: 'rgba(246, 194, 62, 0.2)',
    cyan: 'rgba(54, 185, 204, 0.8)',
    cyanFaded: 'rgba(54, 185, 204, 0.2)',
    gray: 'rgba(133, 135, 150, 0.8)',
    grayFaded: 'rgba(133, 135, 150, 0.2)'
};

// Carregar dados e gráficos quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Configuração Chart.js
    Chart.defaults.font.family = "'Nunito', 'Segoe UI', 'Arial'";
    Chart.defaults.color = '#555';
    
    // Inicializar gráficos com dados iniciais
    initializeCharts();
    
    // Carregar alertas
    loadSystemAlerts();
    
    // Atualizar dados a cada 60 segundos
    setInterval(refreshData, 60000);
});

// Inicializar todos os gráficos
function initializeCharts() {
    // Dados iniciais dos gráficos
    const responseTimeData = <?= json_encode($responseTime['timeData']) ?>;
    const errorDistributionData = <?= json_encode($errors['distribution']) ?>;
    const resourcesData = <?= json_encode($resources['timeData']) ?>;
    const databaseData = <?= json_encode($databaseMetrics['timeData']) ?>;
    
    // Inicializar gráficos com os dados iniciais
    initResponseTimeChart(responseTimeData);
    initErrorDistributionChart(errorDistributionData);
    initResourcesChart(resourcesData);
    initDatabaseChart(databaseData);
}

// Inicializar gráfico de tempo de resposta
function initResponseTimeChart(data) {
    const ctx = document.getElementById('responseTimeChart').getContext('2d');
    
    // Preparar dados
    const labels = data.map(item => item.time);
    const avgTimes = data.map(item => item.avgTime);
    const p95Times = data.map(item => item.p95Time);
    
    // Criar gráfico
    responseTimeChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Tempo Médio (ms)',
                    data: avgTimes,
                    borderColor: chartColors.blue,
                    backgroundColor: chartColors.blueFaded,
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'P95 (ms)',
                    data: p95Times,
                    borderColor: chartColors.red,
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Tempo de Resposta (ms)'
                    }
                }
            }
        }
    });
}

// Inicializar gráfico de distribuição de erros
function initErrorDistributionChart(data) {
    const ctx = document.getElementById('errorDistributionChart').getContext('2d');
    
    // Preparar dados
    const labels = Object.keys(data);
    const values = Object.values(data);
    const backgroundColors = [
        chartColors.red,
        chartColors.yellow,
        chartColors.cyan,
        chartColors.green,
        chartColors.gray
    ];
    
    // Criar gráfico
    errorDistributionChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: backgroundColors,
                hoverBackgroundColor: backgroundColors,
                hoverBorderColor: 'white',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'right',
                    align: 'start'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.formattedValue;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.raw / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Inicializar gráfico de recursos
function initResourcesChart(data) {
    const ctx = document.getElementById('resourcesChart').getContext('2d');
    
    // Preparar dados
    const labels = data.map(item => item.time);
    const memoryData = data.map(item => (item.memoryUsage / (1024 * 1024)).toFixed(2)); // Converter para MB
    const cpuData = data.map(item => item.cpuUsage);
    
    // Criar gráfico
    resourcesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Memória (MB)',
                    data: memoryData,
                    borderColor: chartColors.green,
                    backgroundColor: chartColors.greenFaded,
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y'
                },
                {
                    label: 'CPU (%)',
                    data: cpuData,
                    borderColor: chartColors.cyan,
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Memória (MB)'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    min: 0,
                    max: 100,
                    title: {
                        display: true,
                        text: 'CPU (%)'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
}

// Inicializar gráfico de banco de dados
function initDatabaseChart(data) {
    const ctx = document.getElementById('databaseChart').getContext('2d');
    
    // Preparar dados
    const labels = data.map(item => item.time);
    const queryTimes = data.map(item => item.avgQueryTime);
    const queryCount = data.map(item => item.queryCount);
    
    // Criar gráfico
    databaseChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Tempo Médio de Consulta (ms)',
                    data: queryTimes,
                    borderColor: chartColors.blue,
                    backgroundColor: chartColors.blueFaded,
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y'
                },
                {
                    label: 'Número de Consultas',
                    data: queryCount,
                    borderColor: chartColors.yellow,
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Tempo de Consulta (ms)'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Quantidade de Consultas'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
}

// Carregar alertas do sistema
function loadSystemAlerts() {
    fetch(`<?= BASE_URL ?>admin/dashboard/api/print_queue_alerts`, {
        headers: {
            'X-CSRF-Token': '<?= $csrfToken ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        renderSystemAlerts(data);
    })
    .catch(error => {
        console.error('Erro ao carregar alertas do sistema:', error);
    });
}

// Renderizar alertas do sistema
function renderSystemAlerts(alerts) {
    const container = document.getElementById('system-alerts');
    
    if (!alerts || alerts.length === 0) {
        container.innerHTML = '<div class="alert alert-success">Nenhum alerta de sistema ativo.</div>';
        return;
    }
    
    let html = '';
    
    alerts.forEach(alert => {
        html += `
            <div class="alert alert-${alert.type} alert-dismissible fade show" role="alert">
                <strong>${alert.message}</strong>
                ${alert.details ? `<br><small>${alert.details}</small>` : ''}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Atualizar dados quando o período for alterado
function updateMonitoringPeriod() {
    const hours = document.getElementById('monitoring-period').value;
    window.location.href = `<?= BASE_URL ?>admin/dashboard/monitoring?hours=${hours}`;
}

// Atualizar todos os dados
function refreshData() {
    const hours = document.getElementById('monitoring-period').value;
    
    // Mostrar indicador de atualização
    const btnRefresh = document.getElementById('btn-refresh');
    btnRefresh.innerHTML = '<i class="fa fa-spin fa-spinner"></i> Atualizando...';
    btnRefresh.disabled = true;
    
    // Carregar novos dados
    fetch(`<?= BASE_URL ?>admin/dashboard/api/performance_metrics?hours=${hours}`, {
        headers: {
            'X-CSRF-Token': '<?= $csrfToken ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Atualizar gráficos com novos dados
        updateResponseTimeChart(data.responseTime.timeData);
        updateErrorDistributionChart(data.errors.distribution);
        updateResourcesChart(data.resources.timeData);
        updateDatabaseChart(data.databaseMetrics.timeData);
        
        // Atualizar tabelas
        updateErrorsTable(data.errors.recentErrors);
        updateSecurityEventsTable(data.securityEvents);
        
        // Atualizar métricas principais
        updateMetrics(data);
        
        // Restaurar botão de atualização
        btnRefresh.innerHTML = '<i class="fa fa-sync-alt"></i> Atualizar';
        btnRefresh.disabled = false;
        
        // Carregar alertas também
        loadSystemAlerts();
    })
    .catch(error => {
        console.error('Erro ao atualizar dados:', error);
        btnRefresh.innerHTML = '<i class="fa fa-sync-alt"></i> Atualizar';
        btnRefresh.disabled = false;
    });
}

// Atualizar gráfico de tempo de resposta com novos dados
function updateResponseTimeChart(data) {
    if (!responseTimeChart) return;
    
    responseTimeChart.data.labels = data.map(item => item.time);
    responseTimeChart.data.datasets[0].data = data.map(item => item.avgTime);
    responseTimeChart.data.datasets[1].data = data.map(item => item.p95Time);
    responseTimeChart.update();
}

// Atualizar gráfico de distribuição de erros com novos dados
function updateErrorDistributionChart(data) {
    if (!errorDistributionChart) return;
    
    errorDistributionChart.data.labels = Object.keys(data);
    errorDistributionChart.data.datasets[0].data = Object.values(data);
    errorDistributionChart.update();
}

// Atualizar gráfico de recursos com novos dados
function updateResourcesChart(data) {
    if (!resourcesChart) return;
    
    resourcesChart.data.labels = data.map(item => item.time);
    resourcesChart.data.datasets[0].data = data.map(item => (item.memoryUsage / (1024 * 1024)).toFixed(2));
    resourcesChart.data.datasets[1].data = data.map(item => item.cpuUsage);
    resourcesChart.update();
}

// Atualizar gráfico de banco de dados com novos dados
function updateDatabaseChart(data) {
    if (!databaseChart) return;
    
    databaseChart.data.labels = data.map(item => item.time);
    databaseChart.data.datasets[0].data = data.map(item => item.avgQueryTime);
    databaseChart.data.datasets[1].data = data.map(item => item.queryCount);
    databaseChart.update();
}

// Atualizar tabela de erros
function updateErrorsTable(errors) {
    const tableBody = document.getElementById('errors-table-body');
    
    if (!errors || errors.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="4" class="text-center">Nenhum erro recente encontrado</td></tr>';
        return;
    }
    
    let html = '';
    
    errors.forEach(error => {
        html += `
            <tr>
                <td>${formatTime(error.timestamp)}</td>
                <td>${escapeHtml(error.type)}</td>
                <td>${escapeHtml(error.url)}</td>
                <td>${escapeHtml(truncateText(error.message, 50))}</td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
}

// Atualizar tabela de eventos de segurança
function updateSecurityEventsTable(events) {
    const tableBody = document.getElementById('security-events-table-body');
    
    if (!events || events.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="4" class="text-center">Nenhum evento de segurança recente encontrado</td></tr>';
        return;
    }
    
    let html = '';
    
    events.forEach(event => {
        html += `
            <tr>
                <td>${formatTime(event.timestamp)}</td>
                <td>
                    <span class="badge bg-${getSecurityEventColorJs(event.type)}">
                        ${escapeHtml(event.type)}
                    </span>
                </td>
                <td>${escapeHtml(event.ip_address)}</td>
                <td>${escapeHtml(truncateText(event.description, 50))}</td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
}

// Atualizar métricas principais
function updateMetrics(data) {
    // Atualizar tempo de resposta
    document.querySelector('.metric-card:nth-child(1) .metric-value').textContent = 
        `${data.performance.avgResponseTime ? Number(data.performance.avgResponseTime).toFixed(2) : '0'} ms`;
    
    // Atualizar uso de memória
    document.querySelector('.metric-card:nth-child(2) .metric-value').textContent = 
        formatBytesJs(data.resources.avgMemoryUsage);
    
    // Atualizar taxa de erros
    document.querySelector('.metric-card:nth-child(3) .metric-value').textContent = 
        `${Number(data.errors.errorRate).toFixed(2)}%`;
    
    // Atualizar tempo médio de consulta
    document.querySelector('.metric-card:nth-child(4) .metric-value').textContent = 
        `${Number(data.databaseMetrics.avgQueryTime).toFixed(2)} ms`;
}

// Funções utilitárias
function formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toTimeString().substr(0, 8);
}

function truncateText(text, maxLength) {
    if (!text) return '';
    return text.length > maxLength ? text.substr(0, maxLength) + '...' : text;
}

function escapeHtml(text) {
    if (!text) return '';
    
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getSecurityEventColorJs(type) {
    const colors = {
        'login_success': 'success',
        'login_failure': 'warning',
        'csrf_failure': 'danger',
        'xss_attempt': 'danger',
        'sql_injection': 'danger',
        'access_denied': 'warning',
        'file_upload': 'info',
        'password_reset': 'info',
        'admin_action': 'primary'
    };
    
    return colors[type] || 'secondary';
}

function formatBytesJs(bytes) {
    if (bytes === 0 || !bytes) return '0 B';
    
    const units = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    
    while (bytes >= 1024 && i < units.length - 1) {
        bytes /= 1024;
        i++;
    }
    
    return `${bytes.toFixed(2)} ${units[i]}`;
}
</script>

<style>
.dashboard-container {
    padding: 20px;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.dashboard-period {
    display: flex;
    align-items: center;
}

.dashboard-period label {
    margin-right: 10px;
    white-space: nowrap;
}

.metric-card {
    transition: all 0.3s ease;
    border-left: 4px solid #4e73df;
    height: 100%;
}

.metric-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.metric-card:nth-child(1) {
    border-left-color: #4e73df; /* Azul */
}

.metric-card:nth-child(2) {
    border-left-color: #1cc88a; /* Verde */
}

.metric-card:nth-child(3) {
    border-left-color: #e74a3b; /* Vermelho */
}

.metric-card:nth-child(4) {
    border-left-color: #36b9cc; /* Ciano */
}

.metric-icon {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 2rem;
    opacity: 0.3;
}

.metric-value {
    margin-bottom: 0;
    font-weight: 700;
}

.metric-label {
    font-size: 0.8rem;
    color: #888;
    margin-bottom: 0;
}

/* Ajustes para telas menores */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .dashboard-period {
        margin-top: 15px;
    }
    
    .metric-card {
        margin-bottom: 15px;
    }
}
</style>

<?php
/**
 * Retorna a cor para um tipo de evento de segurança
 * 
 * @param string $type Tipo do evento
 * @return string Classe CSS da cor
 */
function getSecurityEventColor($type) {
    $colors = [
        'login_success' => 'success',
        'login_failure' => 'warning',
        'csrf_failure' => 'danger',
        'xss_attempt' => 'danger',
        'sql_injection' => 'danger',
        'access_denied' => 'warning',
        'file_upload' => 'info',
        'password_reset' => 'info',
        'admin_action' => 'primary'
    ];
    
    return $colors[$type] ?? 'secondary';
}

/**
 * Formata um valor em bytes para uma representação legível
 * 
 * @param int $bytes Valor em bytes
 * @return string Valor formatado
 */
function formatBytes($bytes) {
    if ($bytes === 0 || $bytes === null) return '0 B';
    
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

// Incluir footer
include_once APP_PATH . '/views/admin/partials/footer.php';
?>
