<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * View do Dashboard de Monitoramento de Performance
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

// Incluir header administrativo
require_once 'app/views/admin/partials/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dashboard de Monitoramento de Performance</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin">Dashboard</a></li>
                        <li class="breadcrumb-item active">Monitoramento de Performance</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Filtros e controles -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Filtrar Período</h3>
                        </div>
                        <div class="card-body">
                            <form method="get" action="<?= BASE_URL ?>admin/performance_monitoring_dashboard" id="filter-form" class="form-inline">
                                <div class="form-group mr-3">
                                    <label for="start_date" class="mr-2">Data Inicial:</label>
                                    <input type="datetime-local" id="start_date" name="start_date" 
                                        class="form-control" value="<?= htmlspecialchars(str_replace(' ', 'T', $startDate)) ?>">
                                </div>
                                <div class="form-group mr-3">
                                    <label for="end_date" class="mr-2">Data Final:</label>
                                    <input type="datetime-local" id="end_date" name="end_date" 
                                        class="form-control" value="<?= htmlspecialchars(str_replace(' ', 'T', $endDate)) ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">Aplicar Filtro</button>
                                <button type="button" class="btn btn-outline-secondary ml-2" id="btn-last-hour">Última Hora</button>
                                <button type="button" class="btn btn-outline-secondary ml-2" id="btn-last-day">Último Dia</button>
                                <button type="button" class="btn btn-outline-secondary ml-2" id="btn-last-week">Última Semana</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cartões de resumo -->
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= number_format($summary['total_requests'] ?? 0) ?></h3>
                            <p>Requisições Totais</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-globe"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= number_format(($summary['avg_execution_time'] ?? 0) * 1000, 2) ?> <small>ms</small></h3>
                            <p>Tempo Médio de Resposta</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= number_format(($summary['avg_memory_peak'] ?? 0) / (1024 * 1024), 2) ?> <small>MB</small></h3>
                            <p>Uso Médio de Memória</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-memory"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= number_format($summary['error_rate'] ?? 0, 2) ?><small>%</small></h3>
                            <p>Taxa de Erro</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Métricas em tempo real -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-1"></i>
                                Métricas em Tempo Real
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" id="refresh-metrics">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-info"><i class="fas fa-microchip"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">CPU</span>
                                            <span class="info-box-number" id="cpu-usage">Carregando...</span>
                                            <div class="progress">
                                                <div class="progress-bar bg-info" id="cpu-progress" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-success"><i class="fas fa-memory"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Memória</span>
                                            <span class="info-box-number" id="memory-usage">Carregando...</span>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" id="memory-progress" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-warning"><i class="fas fa-hdd"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Disco</span>
                                            <span class="info-box-number" id="disk-usage">Carregando...</span>
                                            <div class="progress">
                                                <div class="progress-bar bg-warning" id="disk-progress" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Usuários Ativos</span>
                                            <span class="info-box-number" id="active-users">Carregando...</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-secondary"><i class="fas fa-tasks"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Processos Ativos</span>
                                            <span class="info-box-number" id="active-processes">Carregando...</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-danger"><i class="fas fa-bug"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Erros Recentes (24h)</span>
                                            <span class="info-box-number" id="recent-errors">Carregando...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos principais -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Tempo de Execução</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="executionTimeChart" style="min-height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Uso de Memória</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="memoryUsageChart" style="min-height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Consultas de Banco de Dados</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="databaseQueriesChart" style="min-height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Taxa de Erro</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="errorRateChart" style="min-height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas recentes e URLs mais lentas (duas colunas) -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Alertas Recentes</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" id="refresh-alerts">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped" id="alerts-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px">Tipo</th>
                                        <th>Mensagem</th>
                                        <th style="width: 80px">Severidade</th>
                                        <th style="width: 100px">Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentAlerts)) : ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Nenhum alerta recente.</td>
                                        </tr>
                                    <?php else : ?>
                                        <?php foreach ($recentAlerts as $alert) : ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                    $icon = 'info-circle';
                                                    switch ($alert['alert_type']) {
                                                        case 'performance':
                                                            $icon = 'tachometer-alt';
                                                            break;
                                                        case 'timeout':
                                                            $icon = 'clock';
                                                            break;
                                                        case 'error':
                                                            $icon = 'exclamation-triangle';
                                                            break;
                                                    }
                                                    ?>
                                                    <i class="fas fa-<?= $icon ?>"></i>
                                                </td>
                                                <td>
                                                    <a href="<?= BASE_URL ?>admin/performance_monitoring_dashboard/alertDetail?id=<?= htmlspecialchars($alert['id']) ?>">
                                                        <?php
                                                        $message = '';
                                                        if (isset($alert['data']['metric'])) {
                                                            $message = 'Alerta de ' . htmlspecialchars($alert['data']['metric']) . ' em ' . htmlspecialchars($alert['data']['context'] ?? 'desconhecido');
                                                        } elseif (isset($alert['data']['process_name'])) {
                                                            $message = 'Processo ' . htmlspecialchars($alert['data']['process_name']) . ' ' . ($alert['alert_type'] === 'timeout' ? 'atingiu timeout' : 'com erro');
                                                        } else {
                                                            $message = 'Alerta de ' . htmlspecialchars($alert['alert_type']);
                                                        }
                                                        echo $message;
                                                        ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php
                                                    $class = 'info';
                                                    switch ($alert['severity']) {
                                                        case 'warning':
                                                            $class = 'warning';
                                                            break;
                                                        case 'error':
                                                            $class = 'danger';
                                                            break;
                                                        case 'critical':
                                                            $class = 'dark';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?= $class ?>"><?= htmlspecialchars(ucfirst($alert['severity'])) ?></span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= date('d/m H:i', strtotime($alert['created_at'])) ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-center">
                            <a href="<?= BASE_URL ?>admin/performance_monitoring_dashboard/getAlerts" class="btn btn-sm btn-primary">
                                Ver Todos os Alertas
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">URLs Mais Lentas</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>URL</th>
                                        <th style="width: 100px">Requisições</th>
                                        <th style="width: 120px">Tempo Médio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($performanceData['urls'])) : ?>
                                        <tr>
                                            <td colspan="3" class="text-center">Nenhum dado disponível.</td>
                                        </tr>
                                    <?php else : ?>
                                        <?php foreach ($performanceData['urls'] as $urlData) : ?>
                                            <tr>
                                                <td>
                                                    <div class="d-inline-block text-truncate" style="max-width: 250px;">
                                                        <a href="<?= BASE_URL ?>admin/performance_monitoring_dashboard/urlReport?url=<?= urlencode($urlData['request_uri']) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>">
                                                            <?= htmlspecialchars($urlData['request_uri']) ?>
                                                        </a>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($urlData['request_count']) ?></td>
                                                <td>
                                                    <?php
                                                    $avgTime = round($urlData['avg_execution_time'] * 1000, 2); // em ms
                                                    $timeClass = $avgTime > 500 ? 'text-danger' : ($avgTime > 200 ? 'text-warning' : 'text-success');
                                                    ?>
                                                    <span class="<?= $timeClass ?>"><?= $avgTime ?> ms</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-center">
                            <a href="<?= BASE_URL ?>admin/performance_monitoring_dashboard/urlReport?url=all&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn btn-sm btn-primary">
                                Ver Todas as URLs
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- CSRF Token para requisições AJAX -->
<input type="hidden" id="csrf_token" value="<?= SecurityManager::getCsrfToken() ?>">

<!-- Scripts para o dashboard -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // CSRF token para todas as requisições AJAX
    const csrfToken = document.getElementById('csrf_token').value;
    
    // Inicializar gráficos
    loadChartData('execution_time', 'executionTimeChart');
    loadChartData('memory_usage', 'memoryUsageChart');
    loadChartData('database_queries', 'databaseQueriesChart');
    loadChartData('error_rate', 'errorRateChart');
    
    // Carregar métricas em tempo real
    loadSystemMetrics();
    
    // Configurar botões de atalho de período
    document.getElementById('btn-last-hour').addEventListener('click', function() {
        const endDate = new Date();
        const startDate = new Date(endDate);
        startDate.setHours(startDate.getHours() - 1);
        
        setDateTimeInputs(startDate, endDate);
    });
    
    document.getElementById('btn-last-day').addEventListener('click', function() {
        const endDate = new Date();
        const startDate = new Date(endDate);
        startDate.setDate(startDate.getDate() - 1);
        
        setDateTimeInputs(startDate, endDate);
    });
    
    document.getElementById('btn-last-week').addEventListener('click', function() {
        const endDate = new Date();
        const startDate = new Date(endDate);
        startDate.setDate(startDate.getDate() - 7);
        
        setDateTimeInputs(startDate, endDate);
    });
    
    // Configurar botão de atualização de métricas
    document.getElementById('refresh-metrics').addEventListener('click', loadSystemMetrics);
    
    // Configurar botão de atualização de alertas
    document.getElementById('refresh-alerts').addEventListener('click', refreshAlerts);
    
    // Atualizar métricas a cada minuto
    setInterval(loadSystemMetrics, 60000);
    
    // Função para carregar dados dos gráficos
    function loadChartData(metricType, chartId) {
        const startDate = document.getElementById('start_date').value.replace('T', ' ');
        const endDate = document.getElementById('end_date').value.replace('T', ' ');
        
        fetch(`<?= BASE_URL ?>admin/performance_monitoring_dashboard/getChartData?metric_type=${metricType}&start_date=${startDate}&end_date=${endDate}`, {
            method: 'GET',
            headers: {
                'X-CSRF-Token': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            renderChart(chartId, data, metricType);
        })
        .catch(error => {
            console.error('Erro ao carregar dados do gráfico:', error);
        });
    }
    
    // Função para renderizar o gráfico
    function renderChart(chartId, data, metricType) {
        const ctx = document.getElementById(chartId).getContext('2d');
        
        // Configurações específicas de cada tipo de gráfico
        let options = {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1000
            },
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        };
        
        // Configurações específicas para cada tipo de métrica
        switch (metricType) {
            case 'database_queries':
                options.scales.y = {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    id: 'y-queries'
                };
                options.scales['y-time'] = {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    }
                };
                break;
                
            case 'error_rate':
                options.scales.y = {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    id: 'y-rate',
                    min: 0,
                    max: Math.max(...data.datasets[0].data) * 1.2 || 5
                };
                options.scales['y-count'] = {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    }
                };
                options.scales['y-requests'] = {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    }
                };
                break;
        }
        
        // Criar ou atualizar o gráfico
        if (window[chartId]) {
            window[chartId].data = data;
            window[chartId].options = options;
            window[chartId].update();
        } else {
            window[chartId] = new Chart(ctx, {
                type: 'line',
                data: data,
                options: options
            });
        }
    }
    
    // Função para carregar métricas do sistema em tempo real
    function loadSystemMetrics() {
        fetch('<?= BASE_URL ?>admin/performance_monitoring_dashboard/getSystemMetrics', {
            method: 'GET',
            headers: {
                'X-CSRF-Token': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            // Atualizar CPU
            document.getElementById('cpu-usage').textContent = data.cpu_usage.toFixed(1) + '%';
            document.getElementById('cpu-progress').style.width = data.cpu_usage + '%';
            
            // Atualizar Memória
            const memoryUsed = (data.memory_usage.used / (1024 * 1024)).toFixed(2);
            const memoryTotal = (data.memory_usage.total / (1024 * 1024)).toFixed(2);
            document.getElementById('memory-usage').textContent = `${memoryUsed} MB / ${memoryTotal} MB (${data.memory_usage.percentage.toFixed(1)}%)`;
            document.getElementById('memory-progress').style.width = data.memory_usage.percentage + '%';
            
            // Atualizar Disco
            const diskUsed = (data.disk_usage.used / (1024 * 1024 * 1024)).toFixed(2);
            const diskTotal = (data.disk_usage.total / (1024 * 1024 * 1024)).toFixed(2);
            document.getElementById('disk-usage').textContent = `${diskUsed} GB / ${diskTotal} GB (${data.disk_usage.percentage.toFixed(1)}%)`;
            document.getElementById('disk-progress').style.width = data.disk_usage.percentage + '%';
            
            // Atualizar outras métricas
            document.getElementById('active-users').textContent = data.active_users;
            document.getElementById('active-processes').textContent = data.active_processes;
            document.getElementById('recent-errors').textContent = data.recent_errors;
        })
        .catch(error => {
            console.error('Erro ao carregar métricas do sistema:', error);
        });
    }
    
    // Função para atualizar a lista de alertas
    function refreshAlerts() {
        fetch('<?= BASE_URL ?>admin/performance_monitoring_dashboard/getAlerts?limit=5', {
            method: 'GET',
            headers: {
                'X-CSRF-Token': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            const alertsTable = document.getElementById('alerts-table').getElementsByTagName('tbody')[0];
            alertsTable.innerHTML = '';
            
            if (data.alerts.length === 0) {
                const row = alertsTable.insertRow();
                const cell = row.insertCell(0);
                cell.colSpan = 4;
                cell.className = 'text-center';
                cell.textContent = 'Nenhum alerta recente.';
            } else {
                data.alerts.forEach(alert => {
                    const row = alertsTable.insertRow();
                    
                    // Ícone
                    const cellIcon = row.insertCell(0);
                    let icon = 'info-circle';
                    switch (alert.alert_type) {
                        case 'performance':
                            icon = 'tachometer-alt';
                            break;
                        case 'timeout':
                            icon = 'clock';
                            break;
                        case 'error':
                            icon = 'exclamation-triangle';
                            break;
                    }
                    cellIcon.innerHTML = `<i class="fas fa-${icon}"></i>`;
                    
                    // Mensagem
                    const cellMessage = row.insertCell(1);
                    let message = '';
                    if (alert.data.metric) {
                        message = `Alerta de ${alert.data.metric} em ${alert.data.context || 'desconhecido'}`;
                    } else if (alert.data.process_name) {
                        message = `Processo ${alert.data.process_name} ${alert.alert_type === 'timeout' ? 'atingiu timeout' : 'com erro'}`;
                    } else {
                        message = `Alerta de ${alert.alert_type}`;
                    }
                    cellMessage.innerHTML = `<a href="<?= BASE_URL ?>admin/performance_monitoring_dashboard/alertDetail?id=${alert.id}">${message}</a>`;
                    
                    // Severidade
                    const cellSeverity = row.insertCell(2);
                    let severityClass = 'info';
                    switch (alert.severity) {
                        case 'warning':
                            severityClass = 'warning';
                            break;
                        case 'error':
                            severityClass = 'danger';
                            break;
                        case 'critical':
                            severityClass = 'dark';
                            break;
                    }
                    cellSeverity.innerHTML = `<span class="badge bg-${severityClass}">${alert.severity.charAt(0).toUpperCase() + alert.severity.slice(1)}</span>`;
                    
                    // Data
                    const cellDate = row.insertCell(3);
                    const date = new Date(alert.created_at);
                    const formattedDate = `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1).toString().padStart(2, '0')} ${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`;
                    cellDate.innerHTML = `<small class="text-muted">${formattedDate}</small>`;
                });
            }
        })
        .catch(error => {
            console.error('Erro ao atualizar alertas:', error);
        });
    }
    
    // Função auxiliar para definir valores dos inputs de data/hora
    function setDateTimeInputs(startDate, endDate) {
        document.getElementById('start_date').value = formatDatetimeLocal(startDate);
        document.getElementById('end_date').value = formatDatetimeLocal(endDate);
        document.getElementById('filter-form').submit();
    }
    
    // Função auxiliar para formatar data para datetime-local
    function formatDatetimeLocal(date) {
        return date.getFullYear() + '-' +
               String(date.getMonth() + 1).padStart(2, '0') + '-' +
               String(date.getDate()).padStart(2, '0') + 'T' +
               String(date.getHours()).padStart(2, '0') + ':' +
               String(date.getMinutes()).padStart(2, '0');
    }
});
</script>

<!-- Incluir footer administrativo -->
<?php require_once 'app/views/admin/partials/footer.php'; ?>
