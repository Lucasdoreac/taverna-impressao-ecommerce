<?php
/**
 * View de relatórios de monitoramento
 * 
 * Esta view apresenta relatórios analíticos e históricos sobre desempenho
 * do sistema e da fila de impressão, seguindo os guardrails de segurança.
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
$pageTitle = 'Relatórios de Desempenho';

// Sanitizar parâmetros
$timeRange = isset($time_range) ? htmlspecialchars($time_range, ENT_QUOTES, 'UTF-8') : 'today';
$startDate = isset($start_date) ? htmlspecialchars($start_date, ENT_QUOTES, 'UTF-8') : date('Y-m-d', strtotime('-7 days'));
$endDate = isset($end_date) ? htmlspecialchars($end_date, ENT_QUOTES, 'UTF-8') : date('Y-m-d');
$reportData = isset($report_data) ? $report_data : [];
?>

<!-- Incluir header administrativo -->
<?php include_once BASE_PATH . '/app/views/admin/partials/header.php'; ?>

<!-- Meta tag CSRF para requisições AJAX -->
<meta name="csrf-token" content="<?= htmlspecialchars(CsrfProtection::getToken(), ENT_QUOTES, 'UTF-8'); ?>">

<!-- Conteúdo específico da página -->
<div class="container-fluid px-4">
    <h1 class="mt-4"><?= htmlspecialchars($pageTitle); ?></h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/admin">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="/admin/monitoring">Monitoramento</a></li>
        <li class="breadcrumb-item active">Relatórios</li>
    </ol>
    
    <!-- Filtros de relatório -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filtros
        </div>
        <div class="card-body">
            <form id="report-filter-form" action="/admin/monitoring/reports" method="GET" class="row g-3 align-items-end">
                <!-- Token CSRF -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CsrfProtection::getToken(), ENT_QUOTES, 'UTF-8'); ?>">
                
                <!-- Filtro por período -->
                <div class="col-md-3">
                    <label for="time-range" class="form-label">Período</label>
                    <select id="time-range" name="time_range" class="form-select">
                        <option value="today" <?= $timeRange === 'today' ? 'selected' : ''; ?>>Hoje</option>
                        <option value="yesterday" <?= $timeRange === 'yesterday' ? 'selected' : ''; ?>>Ontem</option>
                        <option value="week" <?= $timeRange === 'week' ? 'selected' : ''; ?>>Últimos 7 dias</option>
                        <option value="month" <?= $timeRange === 'month' ? 'selected' : ''; ?>>Últimos 30 dias</option>
                        <option value="custom" <?= $timeRange === 'custom' ? 'selected' : ''; ?>>Personalizado</option>
                    </select>
                </div>
                
                <!-- Datas personalizadas -->
                <div class="col-md-3 custom-date-range <?= $timeRange !== 'custom' ? 'd-none' : ''; ?>">
                    <label for="start-date" class="form-label">Data Inicial</label>
                    <input type="date" id="start-date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate); ?>">
                </div>
                <div class="col-md-3 custom-date-range <?= $timeRange !== 'custom' ? 'd-none' : ''; ?>">
                    <label for="end-date" class="form-label">Data Final</label>
                    <input type="date" id="end-date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate); ?>">
                </div>
                
                <!-- Botões de ação -->
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i> Aplicar Filtros
                    </button>
                    <button type="button" id="export-report" class="btn btn-success">
                        <i class="fas fa-file-export me-1"></i> Exportar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Sumário do relatório -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i>
                    Sumário do Relatório
                    <span class="float-end"><?= htmlspecialchars($reportData['period']['start'] ?? ''); ?> até <?= htmlspecialchars($reportData['period']['end'] ?? ''); ?></span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 text-center border-end">
                            <h4><?= htmlspecialchars($reportData['summary']['total_jobs'] ?? 0); ?></h4>
                            <p class="text-muted mb-0">Total de Trabalhos</p>
                        </div>
                        <div class="col-md-2 text-center border-end">
                            <h4><?= htmlspecialchars($reportData['summary']['completed_jobs'] ?? 0); ?></h4>
                            <p class="text-muted mb-0">Trabalhos Concluídos</p>
                        </div>
                        <div class="col-md-2 text-center border-end">
                            <h4><?= htmlspecialchars($reportData['summary']['failed_jobs'] ?? 0); ?></h4>
                            <p class="text-muted mb-0">Trabalhos Falhos</p>
                        </div>
                        <div class="col-md-2 text-center border-end">
                            <h4><?= htmlspecialchars(number_format($reportData['summary']['success_rate'] ?? 0, 2)); ?>%</h4>
                            <p class="text-muted mb-0">Taxa de Sucesso</p>
                        </div>
                        <div class="col-md-2 text-center border-end">
                            <h4><?= isset($reportData['summary']['average_time']) ? htmlspecialchars(formatTime($reportData['summary']['average_time'])) : '--:--:--'; ?></h4>
                            <p class="text-muted mb-0">Tempo Médio</p>
                        </div>
                        <div class="col-md-2 text-center">
                            <h4><?= htmlspecialchars(calculateDailyAverage($reportData['daily_stats'] ?? [])); ?></h4>
                            <p class="text-muted mb-0">Média Diária</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráficos do relatório -->
    <div class="row">
        <!-- Gráfico de Trabalhos por Dia -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Trabalhos por Dia
                </div>
                <div class="card-body">
                    <canvas id="daily-jobs-chart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Tempo Médio de Impressão -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i>
                    Tempo Médio de Impressão
                </div>
                <div class="card-body">
                    <canvas id="avg-time-chart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Distribuição de Status -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Distribuição de Status
                </div>
                <div class="card-body">
                    <canvas id="status-distribution-chart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Carga do Sistema -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-area me-1"></i>
                    Carga do Sistema
                </div>
                <div class="card-body">
                    <canvas id="system-load-chart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabela de dados detalhados -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Dados Detalhados
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="detailed-data-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Total de Trabalhos</th>
                            <th>Concluídos</th>
                            <th>Falhos</th>
                            <th>Em Processamento</th>
                            <th>Tempo Médio</th>
                            <th>Taxa de Sucesso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($reportData['daily_stats']) && !empty($reportData['daily_stats'])): ?>
                            <?php foreach ($reportData['daily_stats'] as $dayStat): ?>
                                <tr>
                                    <td><?= htmlspecialchars($dayStat['date'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($dayStat['jobs'] ?? 0); ?></td>
                                    <td><?= htmlspecialchars(($dayStat['jobs'] ?? 0) - ($dayStat['failed'] ?? 0)); ?></td>
                                    <td><?= htmlspecialchars($dayStat['failed'] ?? 0); ?></td>
                                    <td><?= htmlspecialchars($dayStat['processing'] ?? 0); ?></td>
                                    <td><?= isset($dayStat['avg_time']) ? htmlspecialchars(formatTime($dayStat['avg_time'])) : '--:--:--'; ?></td>
                                    <td><?= htmlspecialchars(calculateSuccessRate($dayStat['jobs'] ?? 0, $dayStat['failed'] ?? 0)); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">Nenhum dado disponível para o período selecionado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Script específico para a página de relatórios -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Referência ao token CSRF
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Elementos do DOM
    const timeRangeSelect = document.getElementById('time-range');
    const customDateFields = document.querySelectorAll('.custom-date-range');
    const exportButton = document.getElementById('export-report');
    
    // Inicialização dos gráficos
    initializeCharts();
    
    // Event Listeners
    timeRangeSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            customDateFields.forEach(field => field.classList.remove('d-none'));
        } else {
            customDateFields.forEach(field => field.classList.add('d-none'));
        }
    });
    
    exportButton.addEventListener('click', function() {
        exportReport();
    });
    
    /**
     * Inicializa os gráficos com os dados do relatório
     */
    function initializeCharts() {
        // Configurações padrão para gráficos
        const defaultOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            }
        };
        
        // Dados para os gráficos - Em uma implementação real, estes dados viriam do servidor
        // Aqui estamos usando dados simulados para demonstração
        <?php 
        // Preparar dados para os gráficos a partir dos dados do relatório
        $labels = [];
        $jobsData = [];
        $avgTimeData = [];
        
        if (isset($reportData['daily_stats']) && !empty($reportData['daily_stats'])) {
            foreach ($reportData['daily_stats'] as $stat) {
                $labels[] = isset($stat['date']) ? formatDateForDisplay($stat['date']) : '';
                $jobsData[] = $stat['jobs'] ?? 0;
                $avgTimeData[] = isset($stat['avg_time']) ? ($stat['avg_time'] / 60) : 0; // Converter para minutos
            }
        }
        
        // Dados para gráfico de distribuição de status
        $statusLabels = ['Concluídos', 'Falhos', 'Em Processamento', 'Pendentes'];
        $statusData = [
            $reportData['summary']['completed_jobs'] ?? 0,
            $reportData['summary']['failed_jobs'] ?? 0,
            $reportData['summary']['processing_jobs'] ?? 0,
            $reportData['summary']['pending_jobs'] ?? 0
        ];
        
        // Dados para gráfico de carga do sistema
        $loadLabels = $labels; // Reutilizar as mesmas datas
        $loadData = [];
        $memoryData = [];
        
        // Gerar dados aleatórios para simulação
        if (!empty($loadLabels)) {
            for ($i = 0; i < count($loadLabels); $i++) {
                $loadData[] = rand(20, 80);
                $memoryData[] = rand(30, 70);
            }
        }
        ?>
        
        // Gráfico de Trabalhos por Dia
        const dailyJobsCtx = document.getElementById('daily-jobs-chart');
        if (dailyJobsCtx) {
            new Chart(dailyJobsCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($labels) ?>,
                    datasets: [{
                        label: 'Número de Trabalhos',
                        data: <?= json_encode($jobsData) ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    ...defaultOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Quantidade'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Data'
                            }
                        }
                    }
                }
            });
        }
        
        // Gráfico de Tempo Médio de Impressão
        const avgTimeCtx = document.getElementById('avg-time-chart');
        if (avgTimeCtx) {
            new Chart(avgTimeCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($labels) ?>,
                    datasets: [{
                        label: 'Tempo Médio (minutos)',
                        data: <?= json_encode($avgTimeData) ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.8)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: false
                    }]
                },
                options: {
                    ...defaultOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Tempo (minutos)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Data'
                            }
                        }
                    }
                }
            });
        }
        
        // Gráfico de Distribuição de Status
        const statusCtx = document.getElementById('status-distribution-chart');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode($statusLabels) ?>,
                    datasets: [{
                        data: <?= json_encode($statusData) ?>,
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(220, 53, 69, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 159, 64, 0.8)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    ...defaultOptions,
                    maintainAspectRatio: false,
                    responsive: true
                }
            });
        }
        
        // Gráfico de Carga do Sistema
        const systemLoadCtx = document.getElementById('system-load-chart');
        if (systemLoadCtx) {
            new Chart(systemLoadCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($loadLabels) ?>,
                    datasets: [{
                        label: 'Carga CPU (%)',
                        data: <?= json_encode($loadData) ?>,
                        backgroundColor: 'rgba(153, 102, 255, 0.8)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: false,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Uso de Memória (%)',
                        data: <?= json_encode($memoryData) ?>,
                        backgroundColor: 'rgba(255, 205, 86, 0.8)',
                        borderColor: 'rgba(255, 205, 86, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: false,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    ...defaultOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'CPU (%)'
                            },
                            min: 0,
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
                                text: 'Memória (%)'
                            },
                            min: 0,
                            max: 100
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Data'
                            }
                        }
                    }
                }
            });
        }
    }
    
    /**
     * Exporta o relatório em formato CSV
     */
    function exportReport() {
        // Em uma implementação real, isso seria uma requisição para o servidor
        // que retornaria um arquivo CSV
        
        // Aqui apenas simulamos o download de um CSV
        
        // Construir dados CSV
        let csvContent = "data:text/csv;charset=utf-8,";
        
        // Cabeçalhos
        csvContent += "Data,Total de Trabalhos,Concluídos,Falhos,Em Processamento,Tempo Médio,Taxa de Sucesso\n";
        
        // Dados
        const table = document.getElementById('detailed-data-table');
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const columns = row.querySelectorAll('td');
            let rowData = [];
            
            columns.forEach(column => {
                // Escapar aspas duplas
                const cellData = column.textContent.replace(/"/g, '""');
                rowData.push(`"${cellData}"`);
            });
            
            csvContent += rowData.join(',') + '\n';
        });
        
        // Criar link de download
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', `relatorio-impressao-3d-${formatDateForFilename(new Date())}.csv`);
        document.body.appendChild(link);
        
        // Simular clique para iniciar download
        link.click();
        
        // Remover link
        document.body.removeChild(link);
    }
    
    /**
     * Formata uma data para nome de arquivo
     * 
     * @param {Date} date Objeto de data
     * @return {string} Data formatada (YYYY-MM-DD)
     */
    function formatDateForFilename(date) {
        return date.getFullYear() + '-' +
               String(date.getMonth() + 1).padStart(2, '0') + '-' +
               String(date.getDate()).padStart(2, '0');
    }
});
</script>

<?php
/**
 * Funções auxiliares para formatação e cálculos
 */

/**
 * Formata um tempo em segundos para formato legível
 * 
 * @param int $seconds Tempo em segundos
 * @return string Tempo formatado (HH:MM:SS)
 */
function formatTime($seconds) {
    $seconds = (int)$seconds;
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = floor($seconds % 60);
    
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}

/**
 * Calcula a média diária de trabalhos
 * 
 * @param array $dailyStats Array de estatísticas diárias
 * @return string Média formatada com 2 casas decimais
 */
function calculateDailyAverage($dailyStats) {
    if (empty($dailyStats)) {
        return '0.00';
    }
    
    $total = 0;
    foreach ($dailyStats as $stat) {
        $total += isset($stat['jobs']) ? (int)$stat['jobs'] : 0;
    }
    
    return number_format($total / count($dailyStats), 2);
}

/**
 * Calcula a taxa de sucesso
 * 
 * @param int $total Total de trabalhos
 * @param int $failed Trabalhos falhos
 * @return string Taxa de sucesso formatada com 2 casas decimais
 */
function calculateSuccessRate($total, $failed) {
    if ($total == 0) {
        return '0.00';
    }
    
    $successRate = (($total - $failed) / $total) * 100;
    return number_format($successRate, 2);
}

/**
 * Formata data para exibição
 * 
 * @param string $date Data no formato YYYY-MM-DD
 * @return string Data formatada (DD/MM/YYYY)
 */
function formatDateForDisplay($date) {
    $timestamp = strtotime($date);
    return date('d/m/Y', $timestamp);
}
?>

<!-- Incluir footer administrativo -->
<?php include_once BASE_PATH . '/app/views/admin/partials/footer.php'; ?>