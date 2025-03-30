<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * View para Dashboard de Monitoramento de Performance em Ambiente de Produção
 * Exibe métricas de performance coletadas de usuários reais em ambiente de produção
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

// Verificar se PerformanceHelper existe
$hasHelper = class_exists('PerformanceHelper');

// Helper para formatação condicional
function getStatusClass($value, $type) {
    return $hasHelper ? PerformanceHelper::getPerformanceClass($value, $type) : '';
}

function formatTime($ms) {
    return $hasHelper ? PerformanceHelper::formatTime($ms) : number_format($ms, 1) . ' ms';
}

// Verificar se temos dados
$hasData = !empty($metrics) && !empty($metrics['page_views']) && $metrics['page_views'] > 0;
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Monitoramento de Performance</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="?page=admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Monitoramento de Performance</li>
    </ol>
    
    <!-- Seletor de período -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-calendar me-1"></i>
            Período de Análise
        </div>
        <div class="card-body">
            <form method="get" class="row align-items-center">
                <input type="hidden" name="page" value="performance_monitor">
                
                <div class="col-md-6">
                    <div class="btn-group" role="group">
                        <?php $periods = [7 => '7 dias', 14 => '14 dias', 30 => '30 dias', 90 => '90 dias', 180 => '6 meses']; ?>
                        <?php foreach ($periods as $days => $label): ?>
                            <a href="?page=performance_monitor&period=<?= $days ?>" 
                               class="btn btn-<?= $period == $days ? 'primary' : 'outline-secondary' ?>">
                                <?= $label ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="col-md-6 text-end">
                    <a href="?page=performance_monitor&action=settings" class="btn btn-outline-primary">
                        <i class="fas fa-cog"></i> Configurações
                    </a>
                    <a href="?page=performance_monitor&action=alerts" class="btn btn-outline-warning">
                        <i class="fas fa-bell"></i> Alertas
                        <?php if (!empty($degradation) && !empty($degradation['alerts']) && count($degradation['alerts']) > 0): ?>
                            <span class="badge bg-danger"><?= count($degradation['alerts']) ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (isset($warning)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?= $warning ?>
    </div>
    <?php endif; ?>
    
    <!-- Resumo de métricas -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Visualizações de Página</h5>
                    <h2><?= $hasData ? number_format($metrics['page_views']) : 'N/A' ?></h2>
                    <p class="card-text">Últimos <?= $period ?> dias</p>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#">Ver Detalhes</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Tempo Médio de Carregamento</h5>
                    <h2>
                        <?= $hasData && isset($metrics['average_metrics']['avg_load_time']) ? 
                            formatTime($metrics['average_metrics']['avg_load_time']) : 'N/A' ?>
                    </h2>
                    <p class="card-text">Todas as páginas</p>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#">Ver Detalhes</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">LCP Médio</h5>
                    <h2>
                        <?= $hasData && isset($metrics['average_metrics']['avg_lcp']) ? 
                            formatTime($metrics['average_metrics']['avg_lcp']) : 'N/A' ?>
                    </h2>
                    <p class="card-text">Largest Contentful Paint</p>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#">Ver Detalhes</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">CLS Médio</h5>
                    <h2>
                        <?= $hasData && isset($metrics['average_metrics']['avg_cls']) ? 
                            number_format($metrics['average_metrics']['avg_cls'], 3) : 'N/A' ?>
                    </h2>
                    <p class="card-text">Cumulative Layout Shift</p>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#">Ver Detalhes</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Gráfico de tendências -->
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i>
                    Tendências de Performance
                </div>
                <div class="card-body">
                    <?php if ($hasData && !empty($metrics['metrics_over_time'])): ?>
                        <canvas id="performanceTrends" width="100%" height="40"></canvas>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Dados insuficientes para exibir tendências de performance.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Distribuição de dispositivos -->
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-mobile-alt me-1"></i>
                    Distribuição de Dispositivos
                </div>
                <div class="card-body">
                    <?php if ($hasData && !empty($metrics['device_breakdown'])): ?>
                        <canvas id="deviceBreakdown" width="100%" height="50"></canvas>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Dados insuficientes para exibir distribuição de dispositivos.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Páginas mais lentas -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Páginas Mais Lentas
                </div>
                <div class="card-body">
                    <?php if ($hasData && !empty($metrics['slowest_pages'])): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Página</th>
                                        <th>Tempo Médio</th>
                                        <th>Visualizações</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($metrics['slowest_pages'] as $page): ?>
                                        <tr>
                                            <td title="<?= htmlspecialchars($page['page_url']) ?>">
                                                <?= htmlspecialchars(substr($page['page_url'], 0, 30) . (strlen($page['page_url']) > 30 ? '...' : '')) ?>
                                            </td>
                                            <td class="text-<?= getStatusClass($page['avg_load_time'], 'page_load') ?>">
                                                <?= formatTime($page['avg_load_time']) ?>
                                            </td>
                                            <td><?= number_format($page['view_count']) ?></td>
                                            <td>
                                                <a href="?page=performance_monitor&action=page_detail&url=<?= urlencode($page['page_url']) ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-search"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Dados insuficientes para exibir páginas mais lentas.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Páginas mais acessadas -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Páginas Mais Acessadas
                </div>
                <div class="card-body">
                    <?php if ($hasData && !empty($metrics['top_pages'])): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Página</th>
                                        <th>Visualizações</th>
                                        <th>Tempo Médio</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($metrics['top_pages'] as $page): ?>
                                        <tr>
                                            <td title="<?= htmlspecialchars($page['page_url']) ?>">
                                                <?= htmlspecialchars(substr($page['page_url'], 0, 30) . (strlen($page['page_url']) > 30 ? '...' : '')) ?>
                                            </td>
                                            <td><strong><?= number_format($page['view_count']) ?></strong></td>
                                            <td class="text-<?= getStatusClass($page['avg_load_time'], 'page_load') ?>">
                                                <?= formatTime($page['avg_load_time']) ?>
                                            </td>
                                            <td>
                                                <a href="?page=performance_monitor&action=page_detail&url=<?= urlencode($page['page_url']) ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-search"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Dados insuficientes para exibir páginas mais acessadas.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Alertas de deterioração de performance -->
    <?php if (!empty($degradation) && !empty($degradation['alerts']) && count($degradation['alerts']) > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <i class="fas fa-bell me-1"></i>
                Alertas de Deterioração de Performance
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <p><strong>Detectamos mudanças significativas na performance do site:</strong></p>
                    <ul>
                        <?php foreach ($degradation['alerts'] as $alert): ?>
                            <li>
                                <strong><?= ucfirst(str_replace('avg_', '', $alert['metric'])) ?>:</strong>
                                <?= $alert['direction'] === 'worse' ? 'Piorou' : 'Melhorou' ?> 
                                <strong><?= abs($alert['percent_change']) ?>%</strong>
                                (de <?= formatTime($alert['previous']) ?> para <?= formatTime($alert['recent']) ?>)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="?page=performance_monitor&action=alerts" class="btn btn-warning btn-sm">
                        Ver Todos os Alertas <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Scripts para gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($hasData && !empty($metrics['metrics_over_time'])): ?>
            // Dados para gráfico de tendências
            const trendsData = {
                labels: [
                    <?php foreach ($metrics['metrics_over_time'] as $day): ?>
                        '<?= substr($day['date'], 5) ?>', // Formato MM-DD
                    <?php endforeach; ?>
                ],
                datasets: [
                    {
                        label: 'Tempo de Carregamento (ms)',
                        data: [
                            <?php foreach ($metrics['metrics_over_time'] as $day): ?>
                                <?= round($day['avg_load_time'], 2) ?>,
                            <?php endforeach; ?>
                        ],
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    },
                    {
                        label: 'LCP (ms)',
                        data: [
                            <?php foreach ($metrics['metrics_over_time'] as $day): ?>
                                <?= round($day['avg_lcp'], 2) ?>,
                            <?php endforeach; ?>
                        ],
                        borderColor: 'rgba(255, 159, 64, 1)',
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        tension: 0.1
                    }
                ]
            };
            
            new Chart(document.getElementById('performanceTrends'), {
                type: 'line',
                data: trendsData,
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Tendências de Performance ao Longo do Tempo'
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
                                text: 'Tempo (ms)'
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
        
        <?php if ($hasData && !empty($metrics['device_breakdown'])): ?>
            // Dados para gráfico de dispositivos
            const deviceData = {
                labels: [
                    <?php foreach ($metrics['device_breakdown'] as $device): ?>
                        '<?= ucfirst($device['device_type']) ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($metrics['device_breakdown'] as $device): ?>
                            <?= round($device['percentage'], 1) ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(255, 206, 86, 0.8)'
                    ],
                    borderWidth: 1
                }]
            };
            
            new Chart(document.getElementById('deviceBreakdown'), {
                type: 'pie',
                data: deviceData,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.raw + '%';
                                }
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    });
</script>
