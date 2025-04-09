<?php
/**
 * View principal do painel de monitoramento
 * 
 * Esta view apresenta o dashboard principal de monitoramento da aplicação,
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
$pageTitle = 'Painel de Monitoramento';
?>

<!-- Incluir header administrativo -->
<?php include_once BASE_PATH . '/app/views/admin/partials/header.php'; ?>

<!-- CSRF Token para requisições AJAX -->
<meta name="csrf-token" content="<?= htmlspecialchars(CsrfProtection::getToken(), ENT_QUOTES, 'UTF-8'); ?>">

<!-- Conteúdo específico da página -->
<div class="container-fluid px-4">
    <h1 class="mt-4"><?= htmlspecialchars($pageTitle); ?></h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Monitoramento</li>
    </ol>
    
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-tachometer-alt me-1"></i>
                        Status do Sistema
                    </div>
                    <div>
                        <button id="refresh-dashboard" class="btn btn-sm btn-primary">
                            <i class="fas fa-sync-alt"></i> Atualizar
                        </button>
                        <span id="last-updated" class="ms-2 text-muted">
                            Última atualização: <?= htmlspecialchars(date('H:i:s')); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Status cards -->
                    <div class="row">
                        <!-- Fila de Impressão -->
                        <div class="col-md-3">
                            <div class="card bg-primary text-white mb-4">
                                <div class="card-body">
                                    <h5>Fila de Impressão</h5>
                                    <h2 id="queue-pending-count"><?= htmlspecialchars($queue_state['pending_items'] ?? 0); ?></h2>
                                    <small>itens pendentes</small>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <div>
                                        <span id="queue-total-count"><?= htmlspecialchars($queue_state['total_items'] ?? 0); ?></span> itens no total
                                    </div>
                                    <div class="small text-white">
                                        <a href="/admin/print-queue" class="text-white">Detalhes</a>
                                        <i class="fas fa-angle-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Impressoras -->
                        <div class="col-md-3">
                            <div class="card bg-success text-white mb-4">
                                <div class="card-body">
                                    <h5>Impressoras</h5>
                                    <h2 id="printers-idle-count"><?= htmlspecialchars($queue_state['idle_printers'] ?? 0); ?></h2>
                                    <small>impressoras disponíveis</small>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <div>
                                        <span id="printers-total-count"><?= htmlspecialchars($queue_state['total_printers'] ?? 0); ?></span> impressoras no total
                                    </div>
                                    <div class="small text-white">
                                        <a href="/admin/printers" class="text-white">Detalhes</a>
                                        <i class="fas fa-angle-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Performance -->
                        <div class="col-md-3">
                            <div class="card bg-warning text-white mb-4">
                                <div class="card-body">
                                    <h5>Performance</h5>
                                    <h2 id="avg-response-time"><?= htmlspecialchars(number_format($system_stats['last_15m']['avg_response_time'] ?? 0, 1)); ?></h2>
                                    <small>ms (tempo médio de resposta)</small>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <div>
                                        <span id="error-rate"><?= htmlspecialchars(number_format($system_stats['last_15m']['error_rate'] ?? 0, 2)); ?>%</span> taxa de erro
                                    </div>
                                    <div class="small text-white">
                                        <a href="/admin/monitoring/reports" class="text-white">Relatórios</a>
                                        <i class="fas fa-angle-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Alertas -->
                        <div class="col-md-3">
                            <div class="card bg-danger text-white mb-4">
                                <div class="card-body">
                                    <h5>Alertas</h5>
                                    <h2 id="active-alerts-count"><?= htmlspecialchars(count($alert_history ?? [])); ?></h2>
                                    <small>alertas ativos</small>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <div>
                                        <span id="critical-alerts-count"><?= htmlspecialchars(count(array_filter($alert_history ?? [], function($alert) { return $alert['level'] === 'CRÍTICO'; }))); ?></span> alertas críticos
                                    </div>
                                    <div class="small text-white">
                                        <a href="#alerts-section" class="text-white">Ver alertas</a>
                                        <i class="fas fa-angle-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Gráfico de Atividade da Fila -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-area me-1"></i>
                    Atividade da Fila de Impressão (24h)
                </div>
                <div class="card-body">
                    <div id="queue-activity-chart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Performance do Sistema -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Performance do Sistema (1h)
                </div>
                <div class="card-body">
                    <div id="system-performance-chart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Alertas Ativos -->
        <div class="col-xl-12">
            <div class="card mb-4" id="alerts-section">
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Alertas Ativos
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Nível</th>
                                    <th>Tipo</th>
                                    <th>Mensagem</th>
                                    <th>Data/Hora</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="alerts-table-body">
                                <?php if (empty($alert_history)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Nenhum alerta ativo no momento</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($alert_history as $alert): ?>
                                    <tr class="<?= $alert['level'] === 'CRÍTICO' ? 'table-danger' : 'table-warning' ?>">
                                        <td><?= htmlspecialchars($alert['level']); ?></td>
                                        <td><?= htmlspecialchars($alert['type']); ?></td>
                                        <td><?= htmlspecialchars($alert['message']); ?></td>
                                        <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($alert['created_at']))); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary acknowledge-alert" data-alert-id="<?= htmlspecialchars($alert['id']); ?>">
                                                Reconhecer
                                            </button>
                                        </td>
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
    
    <div class="row">
        <!-- Ações Rápidas -->
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-bolt me-1"></i>
                    Ações Rápidas
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="/admin/monitoring/realtime" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-clock me-1"></i> Monitoramento em Tempo Real
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="/admin/print-queue" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-print me-1"></i> Gerenciar Fila de Impressão
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="/admin/monitoring/reports" class="btn btn-info btn-lg w-100 text-white">
                                <i class="fas fa-file-alt me-1"></i> Relatórios de Desempenho
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button id="run-load-test" class="btn btn-danger btn-lg w-100">
                                <i class="fas fa-tachometer-alt me-1"></i> Executar Teste de Carga
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos para o dashboard de monitoramento -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Obter token CSRF da metatag para requisições AJAX
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Referências aos elementos do DOM
    const refreshButton = document.getElementById('refresh-dashboard');
    const lastUpdatedSpan = document.getElementById('last-updated');
    const runLoadTestButton = document.getElementById('run-load-test');
    
    // Constantes para os gráficos
    const queueColors = {
        pending: 'rgba(255, 159, 64, 0.8)',
        processing: 'rgba(54, 162, 235, 0.8)',
        printing: 'rgba(75, 192, 192, 0.8)',
        completed: 'rgba(40, 167, 69, 0.8)',
        failed: 'rgba(220, 53, 69, 0.8)'
    };
    
    // Dados iniciais para gráficos
    let queueActivityChart;
    let systemPerformanceChart;
    
    // Inicializar gráficos
    initializeCharts();
    
    // Event Listeners
    if (refreshButton) {
        refreshButton.addEventListener('click', refreshDashboard);
    }
    
    if (runLoadTestButton) {
        runLoadTestButton.addEventListener('click', runLoadTest);
    }
    
    // Reconhecer alertas
    document.querySelectorAll('.acknowledge-alert').forEach(button => {
        button.addEventListener('click', function() {
            const alertId = this.getAttribute('data-alert-id');
            acknowledgeAlert(alertId);
        });
    });
    
    // Configurar atualização automática a cada 60 segundos
    setInterval(refreshDashboard, 60000);
    
    /**
     * Inicializa os gráficos do dashboard
     */
    function initializeCharts() {
        // Inicializar gráfico de atividade da fila
        const queueCtx = document.getElementById('queue-activity-chart');
        if (queueCtx) {
            queueActivityChart = new Chart(queueCtx, {
                type: 'line',
                data: {
                    labels: generateHourLabels(24),
                    datasets: [
                        {
                            label: 'Pendentes',
                            data: generateRandomData(24, 10, 30),
                            backgroundColor: queueColors.pending,
                            borderColor: queueColors.pending,
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'Em Processamento',
                            data: generateRandomData(24, 5, 15),
                            backgroundColor: queueColors.processing,
                            borderColor: queueColors.processing,
                            tension: 0.4,
                            fill: false
                        },
                        {
                            label: 'Em Impressão',
                            data: generateRandomData(24, 1, 10),
                            backgroundColor: queueColors.printing,
                            borderColor: queueColors.printing,
                            tension: 0.4,
                            fill: false
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
                                text: 'Hora'
                            }
                        }
                    }
                }
            });
        }
        
        // Inicializar gráfico de performance do sistema
        const perfCtx = document.getElementById('system-performance-chart');
        if (perfCtx) {
            systemPerformanceChart = new Chart(perfCtx, {
                type: 'bar',
                data: {
                    labels: generateMinuteLabels(12, 5),
                    datasets: [
                        {
                            label: 'Tempo de Resposta (ms)',
                            data: generateRandomData(12, 50, 300),
                            backgroundColor: 'rgba(54, 162, 235, 0.8)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Uso de Memória (MB)',
                            data: generateRandomData(12, 20, 80),
                            backgroundColor: 'rgba(255, 99, 132, 0.8)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1,
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
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Tempo de Resposta (ms)'
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
                                text: 'Uso de Memória (MB)'
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
        
        // Atualizar os gráficos com dados reais
        fetchChartData();
    }
    
    /**
     * Busca dados reais para os gráficos via API
     */
    function fetchChartData() {
        // Fetch para API de estatísticas da fila
        fetch('/admin/monitoring/api/queue-stats?hours=24', {
            method: 'GET',
            headers: {
                'X-CSRF-Token': csrfToken,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao buscar estatísticas da fila');
            }
            return response.json();
        })
        .then(data => {
            // Atualizar gráfico de atividade da fila com dados reais
            updateQueueActivityChart(data);
        })
        .catch(error => {
            console.error('Erro ao carregar dados do gráfico de fila:', error);
        });
        
        // Fetch para API de estatísticas de performance
        fetch('/admin/monitoring/api/performance-stats?minutes=60', {
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
            // Atualizar gráfico de performance do sistema com dados reais
            updateSystemPerformanceChart(data);
        })
        .catch(error => {
            console.error('Erro ao carregar dados do gráfico de performance:', error);
        });
    }
    
    /**
     * Atualiza o gráfico de atividade da fila com dados reais
     * 
     * @param {Object} data Dados da API
     */
    function updateQueueActivityChart(data) {
        // Implementação para atualizar o gráfico com dados reais
        // Este é um exemplo simplificado - em uma implementação real,
        // processaríamos dados da resposta da API
        if (queueActivityChart && data.hourly_distribution) {
            // Atualize o gráfico com os dados fornecidos
            // Adapte conforme a estrutura real da resposta da API
        }
    }
    
    /**
     * Atualiza o gráfico de performance do sistema com dados reais
     * 
     * @param {Object} data Dados da API
     */
    function updateSystemPerformanceChart(data) {
        // Implementação para atualizar o gráfico com dados reais
        // Este é um exemplo simplificado - em uma implementação real,
        // processaríamos dados da resposta da API
        if (systemPerformanceChart && data.metrics) {
            // Atualize o gráfico com os dados fornecidos
            // Adapte conforme a estrutura real da resposta da API
        }
    }
    
    /**
     * Atualiza todo o dashboard
     */
    function refreshDashboard() {
        fetch('/admin/monitoring/api/queue-state?update=true', {
            method: 'GET',
            headers: {
                'X-CSRF-Token': csrfToken,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao atualizar estado da fila');
            }
            return response.json();
        })
        .then(data => {
            // Atualizar contadores
            document.getElementById('queue-pending-count').textContent = data.pending_items || 0;
            document.getElementById('queue-total-count').textContent = data.total_items || 0;
            document.getElementById('printers-idle-count').textContent = data.idle_printers || 0;
            document.getElementById('printers-total-count').textContent = data.total_printers || 0;
            
            // Atualizar timestamp
            const now = new Date();
            lastUpdatedSpan.textContent = 'Última atualização: ' + 
                now.getHours().toString().padStart(2, '0') + ':' +
                now.getMinutes().toString().padStart(2, '0') + ':' +
                now.getSeconds().toString().padStart(2, '0');
        })
        .catch(error => {
            console.error('Erro ao atualizar dashboard:', error);
        });
        
        // Atualizar estatísticas de sistema
        fetch('/admin/monitoring/api/performance-stats?minutes=15', {
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
            document.getElementById('avg-response-time').textContent = 
                data.average_response_time ? parseFloat(data.average_response_time).toFixed(1) : '0.0';
                
            document.getElementById('error-rate').textContent = 
                data.error_rate ? parseFloat(data.error_rate).toFixed(2) + '%' : '0.00%';
        })
        .catch(error => {
            console.error('Erro ao atualizar estatísticas de performance:', error);
        });
        
        // Atualizar alertas
        fetch('/admin/monitoring/api/alert-history', {
            method: 'GET',
            headers: {
                'X-CSRF-Token': csrfToken,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao buscar histórico de alertas');
            }
            return response.json();
        })
        .then(data => {
            // Atualizar contadores de alertas
            document.getElementById('active-alerts-count').textContent = data.alerts.length || 0;
            
            const criticalCount = data.alerts.filter(alert => alert.level === 'CRÍTICO').length || 0;
            document.getElementById('critical-alerts-count').textContent = criticalCount;
            
            // Atualizar tabela de alertas
            updateAlertsTable(data.alerts);
        })
        .catch(error => {
            console.error('Erro ao atualizar alertas:', error);
        });
        
        // Atualizar gráficos
        fetchChartData();
    }
    
    /**
     * Atualiza a tabela de alertas
     * 
     * @param {Array} alerts Array de alertas
     */
    function updateAlertsTable(alerts) {
        const tableBody = document.getElementById('alerts-table-body');
        if (!tableBody) return;
        
        // Limpar tabela
        tableBody.innerHTML = '';
        
        if (alerts.length === 0) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="5" class="text-center">Nenhum alerta ativo no momento</td>';
            tableBody.appendChild(row);
            return;
        }
        
        // Adicionar alertas à tabela
        alerts.forEach(alert => {
            const row = document.createElement('tr');
            row.className = alert.level === 'CRÍTICO' ? 'table-danger' : 'table-warning';
            
            // Formatação de data
            const createdDate = new Date(alert.created_at);
            const formattedDate = createdDate.toLocaleDateString() + ' ' + 
                                 createdDate.toLocaleTimeString();
            
            row.innerHTML = `
                <td>${escapeHtml(alert.level)}</td>
                <td>${escapeHtml(alert.type)}</td>
                <td>${escapeHtml(alert.message)}</td>
                <td>${escapeHtml(formattedDate)}</td>
                <td>
                    <button class="btn btn-sm btn-primary acknowledge-alert" data-alert-id="${escapeHtml(alert.id)}">
                        Reconhecer
                    </button>
                </td>
            `;
            
            tableBody.appendChild(row);
            
            // Adicionar event listener para o botão de reconhecimento
            const acknowledgeButton = row.querySelector('.acknowledge-alert');
            acknowledgeButton.addEventListener('click', function() {
                const alertId = this.getAttribute('data-alert-id');
                acknowledgeAlert(alertId);
            });
        });
    }
    
    /**
     * Reconhece um alerta
     * 
     * @param {string} alertId ID do alerta
     */
    function acknowledgeAlert(alertId) {
        fetch('/admin/monitoring/api/acknowledge-alert', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                alert_id: alertId
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao reconhecer alerta');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Remover o alerta da tabela
                const alertRow = document.querySelector(`.acknowledge-alert[data-alert-id="${alertId}"]`).closest('tr');
                alertRow.remove();
                
                // Atualizar contadores
                const activeCount = parseInt(document.getElementById('active-alerts-count').textContent) - 1;
                document.getElementById('active-alerts-count').textContent = activeCount >= 0 ? activeCount : 0;
                
                // Verificar se era um alerta crítico
                if (alertRow.classList.contains('table-danger')) {
                    const criticalCount = parseInt(document.getElementById('critical-alerts-count').textContent) - 1;
                    document.getElementById('critical-alerts-count').textContent = criticalCount >= 0 ? criticalCount : 0;
                }
                
                // Se não houver mais alertas, adicionar a linha "nenhum alerta"
                const tableBody = document.getElementById('alerts-table-body');
                if (tableBody.children.length === 0) {
                    const row = document.createElement('tr');
                    row.innerHTML = '<td colspan="5" class="text-center">Nenhum alerta ativo no momento</td>';
                    tableBody.appendChild(row);
                }
            }
        })
        .catch(error => {
            console.error('Erro ao reconhecer alerta:', error);
        });
    }
    
    /**
     * Executa um teste de carga
     */
    function runLoadTest() {
        if (!confirm('Tem certeza que deseja executar um teste de carga? Isso pode afetar temporariamente o desempenho do sistema.')) {
            return;
        }
        
        runLoadTestButton.disabled = true;
        runLoadTestButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Executando teste...';
        
        fetch('/admin/monitoring/run-load-test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                test_type: 'queue_load',
                users: 10,
                iterations: 10
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao executar teste de carga');
            }
            return response.json();
        })
        .then(data => {
            alert(`Teste de carga concluído em ${data.execution_time}s. Veja os relatórios para detalhes.`);
        })
        .catch(error => {
            console.error('Erro ao executar teste de carga:', error);
            alert('Erro ao executar teste de carga. Verifique o console para detalhes.');
        })
        .finally(() => {
            runLoadTestButton.disabled = false;
            runLoadTestButton.innerHTML = '<i class="fas fa-tachometer-alt me-1"></i> Executar Teste de Carga';
        });
    }
    
    /**
     * Gera labels de horas para gráficos (ex: 01:00, 02:00, etc)
     * 
     * @param {number} count Número de horas
     * @return {Array} Array de strings formatadas de hora
     */
    function generateHourLabels(count) {
        const labels = [];
        const now = new Date();
        
        for (let i = count - 1; i >= 0; i--) {
            const date = new Date(now);
            date.setHours(now.getHours() - i);
            labels.push(date.getHours().toString().padStart(2, '0') + ':00');
        }
        
        return labels;
    }
    
    /**
     * Gera labels de minutos para gráficos (ex: 13:05, 13:10, etc)
     * 
     * @param {number} count Número de intervalos
     * @param {number} interval Intervalo em minutos
     * @return {Array} Array de strings formatadas de hora
     */
    function generateMinuteLabels(count, interval) {
        const labels = [];
        const now = new Date();
        
        for (let i = count - 1; i >= 0; i--) {
            const date = new Date(now);
            date.setMinutes(now.getMinutes() - i * interval);
            labels.push(
                date.getHours().toString().padStart(2, '0') + ':' +
                date.getMinutes().toString().padStart(2, '0')
            );
        }
        
        return labels;
    }
    
    /**
     * Gera dados aleatórios para gráficos (apenas para visualização inicial)
     * 
     * @param {number} count Número de pontos de dados
     * @param {number} min Valor mínimo
     * @param {number} max Valor máximo
     * @return {Array} Array de valores aleatórios
     */
    function generateRandomData(count, min, max) {
        return Array.from({ length: count }, () => 
            Math.floor(Math.random() * (max - min + 1)) + min
        );
    }
    
    /**
     * Escapa caracteres HTML para prevenir XSS
     * 
     * @param {string} unsafe String não segura
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