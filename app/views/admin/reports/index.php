<?php
/**
 * View de Relatórios do Sistema de Impressão 3D
 * 
 * Esta view apresenta relatórios e métricas consolidadas do sistema de impressão 3D,
 * incluindo estatísticas de fila, tempos de impressão e status de notificações.
 *
 * @package App\Views\Admin\Reports
 * @version 1.0.0
 * @author Taverna da Impressão
 */

// Verificação de segurança - impedir acesso direto
if (!defined('APP_BASE_PATH')) {
    exit('Acesso direto ao arquivo não permitido');
}

// Garantir que o usuário seja administrador
if (!isset($userRole) || $userRole !== 'admin') {
    header('Location: /login');
    exit;
}

// Extrair dados de impressão
$totalJobs = $reportData['total_jobs'] ?? 0;
$completedJobs = $reportData['completed_jobs'] ?? 0;
$failedJobs = $reportData['failed_jobs'] ?? 0;
$averagePrintTime = $reportData['average_print_time'] ?? 0;
$queueSummary = $reportData['queue_summary'] ?? [];
$printerUtilization = $reportData['printer_utilization'] ?? [];
$notificationStats = $reportData['notification_stats'] ?? [];
$dailyStats = $reportData['daily_stats'] ?? [];

// Função para formatar tempo em horas e minutos
function formatHoursMinutes($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return "{$hours}h {$mins}m";
}

// Função para obter classe CSS baseada no status
function getStatusClass($status) {
    switch ($status) {
        case 'completed': return 'success';
        case 'printing': return 'primary';
        case 'queued': return 'info';
        case 'failed': return 'danger';
        case 'cancelled': return 'warning';
        default: return 'secondary';
    }
}

// Calcular taxa de sucesso
$successRate = ($totalJobs > 0) ? round(($completedJobs / $totalJobs) * 100, 1) : 0;
?>

<!-- Cabeçalho da Página -->
<div class="container-fluid px-4">
    <h1 class="mt-4">Relatórios do Sistema de Impressão 3D</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Relatórios</li>
    </ol>
    
    <!-- Alertas de Segurança e/ou Mensagens do Sistema -->
    <?php if (isset($alert)): ?>
        <div class="alert alert-<?= htmlspecialchars($alert['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($alert['message'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>
    
    <!-- Filtros do Relatório -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filtros do Relatório
        </div>
        <div class="card-body">
            <form method="GET" action="/admin/reports" class="row g-3" id="report-filter-form">
                <!-- Token CSRF -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Data Inicial</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?= htmlspecialchars($filters['date_from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Data Final</label>
                    <input type="date" class="form-control" id="date_to" name="date_to"
                           value="<?= htmlspecialchars($filters['date_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-3">
                    <label for="printer_id" class="form-label">Impressora</label>
                    <select class="form-select" id="printer_id" name="printer_id">
                        <option value="">Todas as Impressoras</option>
                        <?php foreach ($printers as $printer): ?>
                            <option value="<?= htmlspecialchars($printer['id'], ENT_QUOTES, 'UTF-8') ?>" 
                                <?= ($filters['printer_id'] ?? '') == $printer['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($printer['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Todos os Status</option>
                        <option value="queued" <?= ($filters['status'] ?? '') == 'queued' ? 'selected' : '' ?>>Na Fila</option>
                        <option value="printing" <?= ($filters['status'] ?? '') == 'printing' ? 'selected' : '' ?>>Imprimindo</option>
                        <option value="completed" <?= ($filters['status'] ?? '') == 'completed' ? 'selected' : '' ?>>Concluído</option>
                        <option value="failed" <?= ($filters['status'] ?? '') == 'failed' ? 'selected' : '' ?>>Falha</option>
                        <option value="cancelled" <?= ($filters['status'] ?? '') == 'cancelled' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Filtrar</button>
                    <a href="/admin/reports" class="btn btn-secondary"><i class="fas fa-redo me-1"></i> Limpar Filtros</a>
                    <button type="button" class="btn btn-success" id="export-csv">
                        <i class="fas fa-file-csv me-1"></i> Exportar CSV
                    </button>
                    <button type="button" class="btn btn-danger" id="export-pdf">
                        <i class="fas fa-file-pdf me-1"></i> Exportar PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Cards de Resumo -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-0"><?= htmlspecialchars($totalJobs, ENT_QUOTES, 'UTF-8') ?></h4>
                    <div>Total de Trabalhos</div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="/admin/print-jobs">Ver Detalhes</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-0"><?= htmlspecialchars($successRate, ENT_QUOTES, 'UTF-8') ?>%</h4>
                    <div>Taxa de Sucesso</div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="/admin/reports/success-rate">Ver Detalhes</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-0"><?= htmlspecialchars(formatHoursMinutes($averagePrintTime), ENT_QUOTES, 'UTF-8') ?></h4>
                    <div>Tempo Médio de Impressão</div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="/admin/reports/print-time">Ver Detalhes</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <h4 class="mb-0"><?= htmlspecialchars($failedJobs, ENT_QUOTES, 'UTF-8') ?></h4>
                    <div>Falhas de Impressão</div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="/admin/reports/failures">Ver Detalhes</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráficos de Relatório -->
    <div class="row">
        <!-- Gráfico de Status da Fila -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Distribuição de Status da Fila
                </div>
                <div class="card-body">
                    <canvas id="queueStatusChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Utilização de Impressoras -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Utilização de Impressoras
                </div>
                <div class="card-body">
                    <canvas id="printerUtilizationChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Estatísticas Diárias -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-line me-1"></i>
            Estatísticas Diárias
        </div>
        <div class="card-body">
            <canvas id="dailyStatsChart" width="100%" height="30"></canvas>
        </div>
    </div>
    
    <!-- Estatísticas de Notificações -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-bell me-1"></i>
            Estatísticas de Notificações
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Taxas de Entrega</h5>
                    <canvas id="notificationDeliveryChart" width="100%" height="50"></canvas>
                </div>
                <div class="col-md-6">
                    <h5>Canais de Notificação</h5>
                    <canvas id="notificationChannelsChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabela de Resumo da Fila -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Resumo da Fila de Impressão
        </div>
        <div class="card-body">
            <table id="queueSummaryTable" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Quantidade</th>
                        <th>Tempo Médio (h)</th>
                        <th>Tempo Estimado Restante (h)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queueSummary as $item): ?>
                    <tr>
                        <td>
                            <span class="badge bg-<?= getStatusClass($item['status']) ?>">
                                <?= htmlspecialchars($item['status_name'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($item['count'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(number_format($item['avg_time'] / 60, 1), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(number_format($item['remaining_time'] / 60, 1), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Scripts específicos para os gráficos -->
<script src="/assets/js/charts/chart.min.js"></script>
<script src="/assets/js/reports/queue-status-chart.js"></script>
<script src="/assets/js/reports/printer-utilization-chart.js"></script>
<script src="/assets/js/reports/daily-stats-chart.js"></script>
<script src="/assets/js/reports/notification-charts.js"></script>

<script>
// Segurança: Garantir que o JavaScript não tenha vulnerabilidades XSS
document.addEventListener('DOMContentLoaded', function() {
    // Dados para os gráficos (com segurança contra XSS)
    const queueStatusData = <?= json_encode($queueSummary) ?>;
    const printerUtilizationData = <?= json_encode($printerUtilization) ?>;
    const dailyStatsData = <?= json_encode($dailyStats) ?>;
    const notificationStatsData = <?= json_encode($notificationStats) ?>;
    
    // Inicializar os gráficos
    initQueueStatusChart('queueStatusChart', queueStatusData);
    initPrinterUtilizationChart('printerUtilizationChart', printerUtilizationData);
    initDailyStatsChart('dailyStatsChart', dailyStatsData);
    initNotificationDeliveryChart('notificationDeliveryChart', notificationStatsData);
    initNotificationChannelsChart('notificationChannelsChart', notificationStatsData);
    
    // Função de exportação CSV
    document.getElementById('export-csv').addEventListener('click', function() {
        const params = new URLSearchParams(new FormData(document.getElementById('report-filter-form')));
        window.location.href = '/admin/reports/export-csv?' + params.toString();
    });
    
    // Função de exportação PDF
    document.getElementById('export-pdf').addEventListener('click', function() {
        const params = new URLSearchParams(new FormData(document.getElementById('report-filter-form')));
        window.location.href = '/admin/reports/export-pdf?' + params.toString();
    });
});
</script>
