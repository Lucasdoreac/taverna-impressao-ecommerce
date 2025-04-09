<?php
/**
 * Dashboard de Monitoramento de Performance - View principal
 * 
 * Exibe visão geral do estado do sistema, alertas ativos e métricas principais.
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Views\Admin\Monitoring
 * @version    1.0.0
 */

// Incluir cabeçalho e menu da área administrativa
require_once __DIR__ . '/../../partials/admin/header.php';
require_once __DIR__ . '/../../partials/admin/sidebar.php';

// CSRF Token para formulários
$csrfToken = $this->securityManager->generateCsrfToken();
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dashboard de Monitoramento</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                        <li class="breadcrumb-item active">Monitoramento</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <?php
                    $message = '';
                    switch ($_GET['message']) {
                        case 'alert_resolved':
                            $message = 'Alerta resolvido com sucesso!';
                            break;
                        default:
                            $message = 'Operação realizada com sucesso!';
                    }
                    echo htmlspecialchars($message);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <?php
                    $error = '';
                    switch ($_GET['error']) {
                        case 'invalid_csrf':
                            $error = 'Token de segurança inválido. Tente novamente.';
                            break;
                        case 'failed_to_resolve':
                            $error = 'Não foi possível resolver o alerta. Verifique o log de erros.';
                            break;
                        default:
                            $error = 'Ocorreu um erro durante a operação.';
                    }
                    echo htmlspecialchars($error);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Resumo do Status do Sistema -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= count($healthReport['components']) ?></h3>
                            <p>Componentes Monitorados</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-server"></i>
                        </div>
                        <a href="/admin/monitoring/components" class="small-box-footer">
                            Mais informações <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box <?= $healthReport['alerts']['active'] > 0 ? 'bg-warning' : 'bg-success' ?>">
                        <div class="inner">
                            <h3><?= $healthReport['alerts']['active'] ?></h3>
                            <p>Alertas Ativos</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <a href="#active-alerts" class="small-box-footer">
                            Ver detalhes <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= count($alertHistory) ?></h3>
                            <p>Alertas Resolvidos Recentemente</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <a href="/admin/monitoring/alert-history" class="small-box-footer">
                            Histórico completo <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>Relatório</h3>
                            <p>Saúde do Sistema</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <a href="/admin/monitoring/trends" class="small-box-footer">
                            Análise de tendências <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Alertas Ativos -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-warning" id="active-alerts">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Alertas Ativos
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($activeAlerts)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                                    <p class="lead">Nenhum alerta ativo no momento.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Severidade</th>
                                                <th>Componente</th>
                                                <th>Métrica</th>
                                                <th>Valor</th>
                                                <th>Threshold</th>
                                                <th>Data</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($activeAlerts as $alert): ?>
                                                <?php
                                                $severityClass = '';
                                                switch ($alert['severity']) {
                                                    case 'critical': $severityClass = 'badge-danger'; break;
                                                    case 'high': $severityClass = 'badge-warning'; break;
                                                    case 'medium': $severityClass = 'badge-primary'; break;
                                                    case 'low': $severityClass = 'badge-info'; break;
                                                    default: $severityClass = 'badge-secondary';
                                                }
                                                ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge <?= $severityClass ?>">
                                                            <?= htmlspecialchars(ucfirst($alert['severity'])) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($alert['component']) ?></td>
                                                    <td><?= htmlspecialchars($alert['metric']) ?></td>
                                                    <td><?= htmlspecialchars(number_format($alert['value'], 2)) ?></td>
                                                    <td><?= htmlspecialchars(number_format($alert['threshold'], 2)) ?></td>
                                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($alert['created_at']))) ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-primary" 
                                                                data-toggle="modal" 
                                                                data-target="#resolveAlertModal" 
                                                                data-alert-id="<?= $alert['id'] ?>"
                                                                data-alert-title="<?= htmlspecialchars($alert['title']) ?>"
                                                                data-alert-metric="<?= htmlspecialchars($alert['metric']) ?>"
                                                                data-alert-component="<?= htmlspecialchars($alert['component']) ?>">
                                                            Resolver
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status dos Componentes -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-server mr-1"></i>
                                Status dos Componentes
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($healthReport['components'] as $componentName => $component): ?>
                                    <?php
                                    $statusClass = '';
                                    $statusIcon = '';
                                    switch ($component['status']) {
                                        case 'critical':
                                            $statusClass = 'bg-danger';
                                            $statusIcon = 'fa-exclamation-circle';
                                            break;
                                        case 'warning':
                                            $statusClass = 'bg-warning';
                                            $statusIcon = 'fa-exclamation-triangle';
                                            break;
                                        case 'attention':
                                            $statusClass = 'bg-primary';
                                            $statusIcon = 'fa-info-circle';
                                            break;
                                        case 'healthy':
                                        default:
                                            $statusClass = 'bg-success';
                                            $statusIcon = 'fa-check-circle';
                                            break;
                                    }
                                    ?>
                                    <div class="col-md-4">
                                        <div class="info-box <?= $statusClass ?>">
                                            <span class="info-box-icon">
                                                <i class="fas <?= $statusIcon ?>"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text"><?= htmlspecialchars($componentName) ?></span>
                                                <span class="info-box-number">
                                                    <?= $component['alert_count'] ?> alertas ativos
                                                </span>
                                                <?php if (!empty($component['metrics'])): ?>
                                                    <div class="mt-2">
                                                        <?php foreach ($component['metrics'] as $metricName => $value): ?>
                                                            <small><?= htmlspecialchars($metricName) ?>: <?= htmlspecialchars(number_format($value, 2)) ?></small><br>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos de Métricas-chave -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Tempo de Resposta (segundos)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:300px;">
                                <canvas id="responseTimeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Uso de Memória (MB)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:300px;">
                                <canvas id="memoryUsageChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Uso de CPU (%)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:300px;">
                                <canvas id="cpuUsageChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Tempo de Consulta (segundos)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:300px;">
                                <canvas id="queryTimeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recomendações do Sistema -->
            <?php if (!empty($healthReport['recommendations'])): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-lightbulb mr-1"></i>
                                Recomendações Automatizadas
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php foreach ($healthReport['recommendations'] as $recommendation): ?>
                                    <li class="list-group-item">
                                        <i class="fas fa-angle-right mr-2"></i>
                                        <?= htmlspecialchars($recommendation) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para Resolver Alertas -->
<div class="modal fade" id="resolveAlertModal" tabindex="-1" role="dialog" aria-labelledby="resolveAlertModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resolveAlertModalLabel">Resolver Alerta</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="/admin/monitoring/resolve-alert" method="post">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="alert_id" id="modalAlertId">
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <p><strong>Métrica:</strong> <span id="modalAlertMetric"></span></p>
                        <p><strong>Componente:</strong> <span id="modalAlertComponent"></span></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="resolution">Descrição da Resolução</label>
                        <textarea class="form-control" id="resolution" name="resolution" rows="3" 
                                  placeholder="Descreva brevemente como o problema foi resolvido"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Resolver Alerta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Prepara dados para os gráficos -->
<script>
    // Dados de métricas para gráficos
    const metricsData = <?= json_encode($metricsData) ?>;
</script>

<!-- Scripts para geração dos gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cores para componentes (para consistência visual)
    const componentColors = {
        'HttpServer': 'rgba(75, 192, 192, 0.7)',
        'Database': 'rgba(255, 99, 132, 0.7)',
        'FileUpload': 'rgba(255, 205, 86, 0.7)',
        'PrintQueue': 'rgba(54, 162, 235, 0.7)',
        'ReportGenerator': 'rgba(153, 102, 255, 0.7)',
        '3DViewer': 'rgba(255, 159, 64, 0.7)'
    };
    
    // Função para obter cor para um componente
    function getColorForComponent(component) {
        if (componentColors[component]) {
            return componentColors[component];
        }
        
        // Gerar cor aleatória para componentes não mapeados
        const r = Math.floor(Math.random() * 255);
        const g = Math.floor(Math.random() * 255);
        const b = Math.floor(Math.random() * 255);
        return `rgba(${r}, ${g}, ${b}, 0.7)`;
    }
    
    // Função para formatar data para exibição nos gráficos
    function formatDate(timestamp) {
        const date = new Date(timestamp * 1000);
        return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
    
    // Função para preparar dados para um gráfico
    function prepareChartData(metricName) {
        if (!metricsData[metricName] || !metricsData[metricName].data) {
            return {
                labels: [],
                datasets: []
            };
        }
        
        // Obter threshold para esta métrica
        const threshold = metricsData[metricName].threshold;
        
        // Preparar datasets para cada componente
        const datasets = [];
        const allTimestamps = new Set();
        
        // Coletar todos os timestamps únicos
        Object.keys(metricsData[metricName].data).forEach(component => {
            metricsData[metricName].data[component].forEach(point => {
                allTimestamps.add(point.timestamp);
            });
        });
        
        // Ordenar timestamps
        const sortedTimestamps = Array.from(allTimestamps).sort();
        
        // Criar dataset para cada componente
        Object.keys(metricsData[metricName].data).forEach(component => {
            const data = metricsData[metricName].data[component];
            const pointsMap = {};
            
            // Mapear valores por timestamp
            data.forEach(point => {
                pointsMap[point.timestamp] = point.value;
            });
            
            // Construir array de pontos alinhados com timestamps ordenados
            const points = sortedTimestamps.map(timestamp => 
                pointsMap[timestamp] !== undefined ? pointsMap[timestamp] : null
            );
            
            datasets.push({
                label: component,
                data: points,
                borderColor: getColorForComponent(component),
                backgroundColor: getColorForComponent(component).replace('0.7', '0.2'),
                borderWidth: 2,
                pointRadius: 3,
                fill: false,
                tension: 0.1
            });
        });
        
        // Adicionar linha de threshold se disponível
        if (threshold) {
            datasets.push({
                label: 'Threshold',
                data: Array(sortedTimestamps.length).fill(threshold.value),
                borderColor: 'rgba(255, 0, 0, 0.7)',
                backgroundColor: 'rgba(255, 0, 0, 0.1)',
                borderWidth: 2,
                borderDash: [5, 5],
                pointRadius: 0,
                fill: false
            });
        }
        
        // Preparar labels formatados
        const labels = sortedTimestamps.map(formatDate);
        
        return {
            labels: labels,
            datasets: datasets
        };
    }
    
    // Configuração comum para todos os gráficos
    const commonOptions = {
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
                title: {
                    display: true,
                    text: 'Horário'
                }
            },
            y: {
                beginAtZero: true
            }
        }
    };
    
    // Criar gráfico de tempo de resposta
    const responseTimeCtx = document.getElementById('responseTimeChart').getContext('2d');
    new Chart(responseTimeCtx, {
        type: 'line',
        data: prepareChartData('response_time'),
        options: {
            ...commonOptions,
            scales: {
                ...commonOptions.scales,
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Tempo (segundos)'
                    }
                }
            }
        }
    });
    
    // Criar gráfico de uso de memória
    const memoryUsageCtx = document.getElementById('memoryUsageChart').getContext('2d');
    new Chart(memoryUsageCtx, {
        type: 'line',
        data: prepareChartData('memory_usage'),
        options: {
            ...commonOptions,
            scales: {
                ...commonOptions.scales,
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Memória (MB)'
                    }
                }
            }
        }
    });
    
    // Criar gráfico de uso de CPU
    const cpuUsageCtx = document.getElementById('cpuUsageChart').getContext('2d');
    new Chart(cpuUsageCtx, {
        type: 'line',
        data: prepareChartData('cpu_usage'),
        options: {
            ...commonOptions,
            scales: {
                ...commonOptions.scales,
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'CPU (%)'
                    }
                }
            }
        }
    });
    
    // Criar gráfico de tempo de consulta
    const queryTimeCtx = document.getElementById('queryTimeChart').getContext('2d');
    new Chart(queryTimeCtx, {
        type: 'line',
        data: prepareChartData('query_time'),
        options: {
            ...commonOptions,
            scales: {
                ...commonOptions.scales,
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Tempo (segundos)'
                    }
                }
            }
        }
    });
    
    // Configurar modal para resolver alertas
    $('#resolveAlertModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const alertId = button.data('alert-id');
        const alertMetric = button.data('alert-metric');
        const alertComponent = button.data('alert-component');
        
        const modal = $(this);
        modal.find('#modalAlertId').val(alertId);
        modal.find('#modalAlertMetric').text(alertMetric);
        modal.find('#modalAlertComponent').text(alertComponent);
    });
});
</script>

<?php
// Incluir rodapé da área administrativa
require_once __DIR__ . '/../../partials/admin/footer.php';
?>
