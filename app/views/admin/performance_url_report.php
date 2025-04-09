<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * View de Relatório Detalhado de Performance por URL
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
                    <h1 class="m-0">Relatório de Performance: URL</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/performance_monitoring_dashboard">Monitoramento de Performance</a></li>
                        <li class="breadcrumb-item active">Relatório de URL</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Filtros e informações básicas -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <?php if ($url == 'all'): ?>
                                    Todas as URLs
                                <?php else: ?>
                                    URL: <code><?= htmlspecialchars($url) ?></code>
                                <?php endif; ?>
                            </h3>
                        </div>
                        <div class="card-body">
                            <form method="get" action="<?= BASE_URL ?>admin/performance_monitoring_dashboard/urlReport" class="form-inline">
                                <?php if ($url != 'all'): ?>
                                    <input type="hidden" name="url" value="<?= htmlspecialchars($url) ?>">
                                <?php else: ?>
                                    <div class="form-group mr-3">
                                        <label for="url" class="mr-2">URL:</label>
                                        <select name="url" id="url" class="form-control select2" style="min-width: 250px;">
                                            <option value="all">Todas as URLs</option>
                                            <?php 
                                            // Se temos dados de todas as URLs, criar opções do select
                                            if ($url == 'all' && isset($report['urls'])) {
                                                foreach ($report['urls'] as $urlData) {
                                                    $selected = '';
                                                    $displayUrl = strlen($urlData['request_uri']) > 50 
                                                        ? substr($urlData['request_uri'], 0, 47) . '...' 
                                                        : $urlData['request_uri'];
                                                    echo '<option value="' . htmlspecialchars($urlData['request_uri']) . '" ' . $selected . '>' . 
                                                        htmlspecialchars($displayUrl) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="form-group mr-3">
                                    <label for="start_date" class="mr-2">De:</label>
                                    <input type="datetime-local" id="start_date" name="start_date" 
                                        class="form-control" value="<?= htmlspecialchars(str_replace(' ', 'T', $startDate)) ?>">
                                </div>
                                <div class="form-group mr-3">
                                    <label for="end_date" class="mr-2">Até:</label>
                                    <input type="datetime-local" id="end_date" name="end_date" 
                                        class="form-control" value="<?= htmlspecialchars(str_replace(' ', 'T', $endDate)) ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">Aplicar Filtro</button>
                            </form>
                            
                            <hr>
                            
                            <?php if ($url == 'all'): ?>
                                <!-- Exibir tabela de todas as URLs -->
                                <h5 class="mt-4">Todas as URLs no Período</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="all-urls-table">
                                        <thead>
                                            <tr>
                                                <th>URL</th>
                                                <th>Requisições</th>
                                                <th>Tempo Médio (ms)</th>
                                                <th>Tempo Mín. (ms)</th>
                                                <th>Tempo Máx. (ms)</th>
                                                <th>Memória (MB)</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($report['urls'])): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">Nenhum dado disponível.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($report['urls'] as $urlData): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="text-truncate" style="max-width: 300px;" title="<?= htmlspecialchars($urlData['request_uri']) ?>">
                                                                <?= htmlspecialchars($urlData['request_uri']) ?>
                                                            </div>
                                                        </td>
                                                        <td><?= htmlspecialchars($urlData['request_count']) ?></td>
                                                        <td>
                                                            <?php
                                                            $avgTime = round($urlData['avg_execution_time'] * 1000, 2); // em ms
                                                            $timeClass = $avgTime > 500 ? 'text-danger' : ($avgTime > 200 ? 'text-warning' : 'text-success');
                                                            ?>
                                                            <span class="<?= $timeClass ?>"><?= $avgTime ?></span>
                                                        </td>
                                                        <td>
                                                            <?= round($urlData['min_execution_time'] * 1000, 2) ?>
                                                        </td>
                                                        <td>
                                                            <?= round($urlData['max_execution_time'] * 1000, 2) ?>
                                                        </td>
                                                        <td>
                                                            <?= round($urlData['avg_memory_peak'] / (1024 * 1024), 2) ?>
                                                        </td>
                                                        <td>
                                                            <a href="<?= BASE_URL ?>admin/performance_monitoring_dashboard/urlReport?url=<?= urlencode($urlData['request_uri']) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn btn-sm btn-info">
                                                                <i class="fas fa-chart-line"></i> Detalhar
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                            <?php else: ?>
                                <!-- Exibir resumo da URL específica -->
                                <?php if (isset($report['summary']) && !empty($report['summary'])): ?>
                                    <div class="row">
                                        <div class="col-md-3 col-sm-6 col-12">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-info"><i class="fas fa-globe"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Total de Requisições</span>
                                                    <span class="info-box-number"><?= htmlspecialchars($report['summary']['total_requests']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6 col-12">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-success"><i class="fas fa-tachometer-alt"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Tempo Médio</span>
                                                    <span class="info-box-number"><?= round($report['summary']['avg_execution_time'] * 1000, 2) ?> ms</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6 col-12">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-warning"><i class="fas fa-stopwatch"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Tempo Máximo</span>
                                                    <span class="info-box-number"><?= round($report['summary']['max_execution_time'] * 1000, 2) ?> ms</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6 col-12">
                                            <div class="info-box">
                                                <span class="info-box-icon bg-danger"><i class="fas fa-memory"></i></span>
                                                <div class="info-box-content">
                                                    <span class="info-box-text">Memória Média</span>
                                                    <span class="info-box-number"><?= round($report['summary']['avg_memory_peak'] / (1024 * 1024), 2) ?> MB</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Informações estatísticas adicionais -->
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="card card-outline card-info">
                                                <div class="card-header">
                                                    <h3 class="card-title">Estatísticas Detalhadas</h3>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <dl class="row">
                                                                <dt class="col-sm-6">Tempo Mínimo:</dt>
                                                                <dd class="col-sm-6"><?= round($report['summary']['min_execution_time'] * 1000, 2) ?> ms</dd>
                                                                
                                                                <dt class="col-sm-6">Desvio Padrão (Tempo):</dt>
                                                                <dd class="col-sm-6"><?= round($report['summary']['std_dev_execution_time'] * 1000, 2) ?> ms</dd>
                                                                
                                                                <dt class="col-sm-6">Variabilidade:</dt>
                                                                <dd class="col-sm-6">
                                                                    <?php
                                                                    $cv = $report['summary']['avg_execution_time'] > 0 
                                                                        ? ($report['summary']['std_dev_execution_time'] / $report['summary']['avg_execution_time']) * 100 
                                                                        : 0;
                                                                    echo round($cv, 2) . '%';
                                                                    ?>
                                                                </dd>
                                                            </dl>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <dl class="row">
                                                                <dt class="col-sm-6">Memória Máxima:</dt>
                                                                <dd class="col-sm-6"><?= round($report['summary']['max_memory_peak'] / (1024 * 1024), 2) ?> MB</dd>
                                                                
                                                                <dt class="col-sm-6">P90 (Tempo):</dt>
                                                                <dd class="col-sm-6">
                                                                    <?= isset($report['summary']['p90_execution_time']) 
                                                                        ? round($report['summary']['p90_execution_time'] * 1000, 2) . ' ms' 
                                                                        : 'N/A' ?>
                                                                </dd>
                                                                
                                                                <dt class="col-sm-6">P95 (Tempo):</dt>
                                                                <dd class="col-sm-6">
                                                                    <?= isset($report['summary']['p95_execution_time']) 
                                                                        ? round($report['summary']['p95_execution_time'] * 1000, 2) . ' ms' 
                                                                        : 'N/A' ?>
                                                                </dd>
                                                            </dl>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($url != 'all'): ?>
                <!-- Gráficos para URL específica -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-line mr-1"></i>
                                    Distribuição de Tempo de Execução
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="chart">
                                    <canvas id="hourlyExecutionChart" style="min-height: 300px;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Requisições mais lentas e mais rápidas -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-danger">
                                <h3 class="card-title">Requisições Mais Lentas</h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>Método</th>
                                            <th>Tempo (ms)</th>
                                            <th>Memória (MB)</th>
                                            <th>Data</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($report['slowest_requests'])): ?>
                                            <tr><td colspan="5" class="text-center">Nenhum dado disponível.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($report['slowest_requests'] as $req): ?>
                                                <tr>
                                                    <td title="<?= htmlspecialchars($req['request_id']) ?>">
                                                        <?= substr(htmlspecialchars($req['request_id']), 0, 10) ?>...
                                                    </td>
                                                    <td><?= htmlspecialchars($req['method']) ?></td>
                                                    <td class="text-danger"><?= round($req['execution_time'] * 1000, 2) ?></td>
                                                    <td><?= round($req['memory_peak'] / (1024 * 1024), 2) ?></td>
                                                    <td><?= date('d/m/Y H:i:s', strtotime($req['timestamp'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success">
                                <h3 class="card-title">Requisições Mais Rápidas</h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>Método</th>
                                            <th>Tempo (ms)</th>
                                            <th>Memória (MB)</th>
                                            <th>Data</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($report['fastest_requests'])): ?>
                                            <tr><td colspan="5" class="text-center">Nenhum dado disponível.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($report['fastest_requests'] as $req): ?>
                                                <tr>
                                                    <td title="<?= htmlspecialchars($req['request_id']) ?>">
                                                        <?= substr(htmlspecialchars($req['request_id']), 0, 10) ?>...
                                                    </td>
                                                    <td><?= htmlspecialchars($req['method']) ?></td>
                                                    <td class="text-success"><?= round($req['execution_time'] * 1000, 2) ?></td>
                                                    <td><?= round($req['memory_peak'] / (1024 * 1024), 2) ?></td>
                                                    <td><?= date('d/m/Y H:i:s', strtotime($req['timestamp'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recomendações automáticas -->
                <div class="row">
                    <div class="col-12">
                        <div class="card card-warning">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-lightbulb mr-1"></i>
                                    Recomendações Automáticas
                                </h3>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled">
                                    <?php
                                    // Gerar recomendações automáticas com base nas métricas
                                    if (isset($report['summary'])) {
                                        $recommendations = [];
                                        
                                        // Verificar tempo de execução
                                        $avgTime = $report['summary']['avg_execution_time'] * 1000; // ms
                                        if ($avgTime > 500) {
                                            $recommendations[] = 'O tempo médio de resposta (<strong>' . round($avgTime, 2) . ' ms</strong>) é muito alto. ' . 
                                                'Considere otimizar esta URL para melhorar o desempenho geral do sistema.';
                                        } elseif ($avgTime > 200) {
                                            $recommendations[] = 'O tempo médio de resposta (<strong>' . round($avgTime, 2) . ' ms</strong>) é moderadamente alto. ' . 
                                                'Verifique oportunidades de otimização.';
                                        }
                                        
                                        // Verificar variabilidade
                                        if (isset($report['summary']['std_dev_execution_time'])) {
                                            $cv = $report['summary']['avg_execution_time'] > 0 
                                                ? ($report['summary']['std_dev_execution_time'] / $report['summary']['avg_execution_time']) * 100 
                                                : 0;
                                            
                                            if ($cv > 100) {
                                                $recommendations[] = 'A variabilidade dos tempos de resposta é muito alta (<strong>' . round($cv, 2) . '%</strong>). ' . 
                                                    'Isso pode indicar problemas de concorrência ou contenção de recursos.';
                                            } elseif ($cv > 50) {
                                                $recommendations[] = 'A variabilidade dos tempos de resposta é moderadamente alta (<strong>' . round($cv, 2) . '%</strong>). ' . 
                                                    'Monitore para identificar padrões ou condições específicas que afetam o desempenho.';
                                            }
                                        }
                                        
                                        // Verificar uso de memória
                                        $avgMemory = $report['summary']['avg_memory_peak'] / (1024 * 1024); // MB
                                        if ($avgMemory > 64) {
                                            $recommendations[] = 'O uso médio de memória (<strong>' . round($avgMemory, 2) . ' MB</strong>) é muito alto. ' . 
                                                'Verifique possíveis vazamentos de memória ou otimize o processamento de dados.';
                                        } elseif ($avgMemory > 32) {
                                            $recommendations[] = 'O uso médio de memória (<strong>' . round($avgMemory, 2) . ' MB</strong>) é moderadamente alto. ' . 
                                                'Considere otimizar para reduzir o consumo de recursos.';
                                        }
                                        
                                        // Verificar diferença entre pico e média
                                        if (isset($report['summary']['max_execution_time'])) {
                                            $peakRatio = $report['summary']['avg_execution_time'] > 0 
                                                ? $report['summary']['max_execution_time'] / $report['summary']['avg_execution_time'] 
                                                : 0;
                                            
                                            if ($peakRatio > 10) {
                                                $recommendations[] = 'O tempo máximo de resposta é <strong>' . round($peakRatio, 1) . 'x</strong> maior que a média. ' . 
                                                    'Investigue casos extremos que podem estar prejudicando a experiência de alguns usuários.';
                                            } elseif ($peakRatio > 5) {
                                                $recommendations[] = 'O tempo máximo de resposta é <strong>' . round($peakRatio, 1) . 'x</strong> maior que a média. ' . 
                                                    'Verifique condições atípicas que podem estar causando picos ocasionais.';
                                            }
                                        }
                                        
                                        // Se não houver recomendações
                                        if (empty($recommendations)) {
                                            $recommendations[] = 'As métricas atuais estão dentro dos parâmetros esperados. Continue monitorando para identificar alterações no padrão de desempenho.';
                                        }
                                        
                                        // Exibir recomendações
                                        foreach ($recommendations as $rec) {
                                            echo '<li class="mb-2"><i class="fas fa-check-circle text-warning mr-2"></i> ' . $rec . '</li>';
                                        }
                                    } else {
                                        echo '<li>Sem dados suficientes para gerar recomendações.</li>';
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- Script para gráficos e datatable -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTable para todas as URLs
    if (document.getElementById('all-urls-table')) {
        $('#all-urls-table').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[2, 'desc']], // Ordenar por tempo médio (decrescente)
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
            }
        });
    }
    
    // Inicializar Select2
    if ($('.select2').length) {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: 'resolve'
        });
    }
    
    <?php if ($url != 'all' && isset($report['hourly_data'])): ?>
    // Criar gráfico de distribuição horária para URL específica
    const hourlyData = <?= json_encode($report['hourly_data']) ?>;
    
    const hourlyLabels = hourlyData.map(item => item.hour);
    const requestCounts = hourlyData.map(item => parseInt(item.request_count));
    const avgTimes = hourlyData.map(item => parseFloat(item.avg_execution_time) * 1000); // Converter para ms
    const maxTimes = hourlyData.map(item => parseFloat(item.max_execution_time) * 1000); // Converter para ms
    
    const hourlyCtx = document.getElementById('hourlyExecutionChart').getContext('2d');
    new Chart(hourlyCtx, {
        type: 'bar',
        data: {
            labels: hourlyLabels,
            datasets: [
                {
                    label: 'Requisições',
                    data: requestCounts,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y-requests'
                },
                {
                    label: 'Tempo Médio (ms)',
                    data: avgTimes,
                    type: 'line',
                    fill: false,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    yAxisID: 'y-time'
                },
                {
                    label: 'Tempo Máximo (ms)',
                    data: maxTimes,
                    type: 'line',
                    fill: false,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    borderDash: [5, 5],
                    yAxisID: 'y-time'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                'y-requests': {
                    type: 'linear',
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Número de Requisições'
                    },
                    min: 0
                },
                'y-time': {
                    type: 'linear',
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Tempo (ms)'
                    },
                    min: 0,
                    grid: {
                        drawOnChartArea: false
                    }
                }
            },
            plugins: {
                tooltip: {
                    mode: 'index',
                    intersect: false
                },
                legend: {
                    position: 'top',
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<!-- Incluir footer administrativo -->
<?php require_once 'app/views/admin/partials/footer.php'; ?>
