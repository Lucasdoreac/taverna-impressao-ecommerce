<?php
/**
 * View de métricas de impressão 3D do dashboard administrativo
 * 
 * Esta view exibe métricas detalhadas de impressão 3D, status das impressoras,
 * uso de filamentos e jobs de impressão ativos.
 */

// Incluir header
include_once APP_PATH . '/views/admin/includes/header.php';
include_once APP_PATH . '/views/admin/includes/sidebar.php';
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><?= $title ?></h1>
        
        <div class="refresh-controls">
            <span id="last-update-time" class="text-muted small"></span>
            <button id="refresh-data" class="btn btn-sm btn-outline-primary ms-2">
                <i class="fa fa-sync-alt"></i> Atualizar
            </button>
        </div>
    </div>

    <!-- Métricas principais -->
    <div class="metrics-summary row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="metric-icon">
                        <i class="fa fa-print"></i>
                    </div>
                    <h5 class="card-title">Jobs Totais</h5>
                    <h2 class="metric-value"><?= number_format($printingMetrics['total_jobs'], 0, ',', '.') ?></h2>
                    <p class="metric-status-counts">
                        <span class="badge bg-warning"><?= $printingMetrics['pending_jobs'] ?> pendentes</span>
                        <span class="badge bg-primary"><?= $printingMetrics['active_jobs'] ?> ativos</span>
                        <span class="badge bg-success"><?= $printingMetrics['completed_jobs'] ?> concluídos</span>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="metric-icon">
                        <i class="fa fa-clock"></i>
                    </div>
                    <h5 class="card-title">Tempo de Impressão</h5>
                    <h2 class="metric-value"><?= number_format($printingMetrics['total_print_hours'], 1, ',', '.') ?> h</h2>
                    <p class="metric-label">Tempo total no período</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="metric-icon">
                        <i class="fa fa-weight"></i>
                    </div>
                    <h5 class="card-title">Filamento Usado</h5>
                    <h2 class="metric-value"><?= number_format($printingMetrics['total_filament_grams'] / 1000, 2, ',', '.') ?> kg</h2>
                    <p class="metric-label"><?= number_format($printingMetrics['total_filament_grams'], 0, ',', '.') ?> gramas de filamento</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="metric-icon">
                        <i class="fa fa-check-circle"></i>
                    </div>
                    <h5 class="card-title">Taxa de Sucesso</h5>
                    <?php 
                    $successRate = $printingMetrics['success_rate'];
                    $successClass = $successRate >= 90 ? 'success' : ($successRate >= 75 ? 'warning' : 'danger');
                    ?>
                    <h2 class="metric-value text-<?= $successClass ?>"><?= number_format($successRate, 1, ',', '.') ?>%</h2>
                    <p class="metric-label">
                        <?= $printingMetrics['completed_jobs'] ?> concluídos / 
                        <?= $printingMetrics['failed_jobs'] ?> falhas
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status das Impressoras e Fila de Impressão -->
    <div class="row mt-4">
        <!-- Status das Impressoras -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Status das Impressoras</h5>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addPrinterModal">
                            <i class="fa fa-plus"></i> Adicionar Impressora
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="printer-grid">
                        <?php if (empty($printerStatus)): ?>
                            <div class="alert alert-info">
                                Nenhuma impressora cadastrada. Adicione impressoras para gerenciar a produção.
                            </div>
                        <?php else: ?>
                            <?php foreach ($printerStatus as $printer): ?>
                                <div class="printer-card <?= getPrinterCardClass($printer['status']) ?>">
                                    <div class="printer-header">
                                        <h6 class="printer-name"><?= htmlspecialchars($printer['name']) ?></h6>
                                        <span class="printer-status badge <?= getPrinterStatusBadgeClass($printer['status']) ?>">
                                            <?= getPrinterStatusName($printer['status']) ?>
                                        </span>
                                    </div>
                                    <div class="printer-body">
                                        <div class="printer-model text-muted small"><?= htmlspecialchars($printer['model']) ?></div>
                                        
                                        <?php if ($printer['status'] === 'printing' && !empty($printer['current_job'])): ?>
                                            <div class="current-job mt-2">
                                                <div class="job-title"><?= htmlspecialchars($printer['current_job']) ?></div>
                                                <div class="progress mt-1 mb-1">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: <?= $printer['current_job_progress'] ?>%;" 
                                                         aria-valuenow="<?= $printer['current_job_progress'] ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?= $printer['current_job_progress'] ?>%
                                                    </div>
                                                </div>
                                                <div class="job-details d-flex justify-content-between small">
                                                    <span><?= htmlspecialchars($printer['current_filament_type']) ?> · 
                                                           <?= htmlspecialchars($printer['current_filament_color']) ?></span>
                                                    <span><?= number_format($printer['current_job_time'], 1, ',', '.') ?> h restantes</span>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="printer-idle mt-2">
                                                <div class="idle-message text-muted">
                                                    <?php if ($printer['status'] === 'maintenance'): ?>
                                                        <i class="fa fa-tools"></i> Em manutenção
                                                    <?php elseif ($printer['status'] === 'error'): ?>
                                                        <i class="fa fa-exclamation-triangle"></i> Erro detectado
                                                    <?php elseif ($printer['status'] === 'offline'): ?>
                                                        <i class="fa fa-power-off"></i> Impressora offline
                                                    <?php else: ?>
                                                        <i class="fa fa-check-circle"></i> Disponível para impressão
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="printer-footer">
                                        <div class="btn-group printer-actions" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewPrinterDetails(<?= $printer['id'] ?>)">
                                                <i class="fa fa-info-circle"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    onclick="changePrinterStatus(<?= $printer['id'] ?>)">
                                                <i class="fa fa-cog"></i>
                                            </button>
                                            <?php if ($printer['status'] === 'printing'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning"
                                                        onclick="pausePrinting(<?= $printer['id'] ?>)">
                                                    <i class="fa fa-pause"></i>
                                                </button>
                                            <?php elseif ($printer['status'] === 'idle' || $printer['status'] === 'active'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success"
                                                        onclick="assignJob(<?= $printer['id'] ?>)">
                                                    <i class="fa fa-play"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fila de Impressão -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Fila de Impressão</h5>
                    <div class="card-tools">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary filter-print-queue active" data-status="all">
                                Todos
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary filter-print-queue" data-status="pending">
                                Pendentes
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary filter-print-queue" data-status="printing">
                                Imprimindo
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="print-queue-list">
                        <?php if (empty($printJobs)): ?>
                            <div class="alert alert-info">
                                Nenhum job de impressão na fila.
                            </div>
                        <?php else: ?>
                            <?php foreach ($printJobs as $job): ?>
                                <div class="print-job-item" data-status="<?= $job['status'] ?>">
                                    <div class="job-header">
                                        <div class="job-info">
                                            <h6 class="job-title"><?= htmlspecialchars($job['product_name']) ?></h6>
                                            <span class="job-status badge <?= getPrintJobStatusBadgeClass($job['status']) ?>">
                                                <?= getPrintJobStatusName($job['status']) ?>
                                            </span>
                                        </div>
                                        <div class="job-actions">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                                    <i class="fa fa-ellipsis-v"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="#" onclick="viewJobDetails(<?= $job['id'] ?>)">
                                                        <i class="fa fa-eye"></i> Ver Detalhes
                                                    </a>
                                                    <?php if ($job['status'] === 'pending'): ?>
                                                        <a class="dropdown-item" href="#" onclick="assignJobToPrinter(<?= $job['id'] ?>)">
                                                            <i class="fa fa-print"></i> Atribuir Impressora
                                                        </a>
                                                        <a class="dropdown-item" href="#" onclick="editJob(<?= $job['id'] ?>)">
                                                            <i class="fa fa-edit"></i> Editar
                                                        </a>
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item text-danger" href="#" onclick="cancelJob(<?= $job['id'] ?>)">
                                                            <i class="fa fa-times"></i> Cancelar
                                                        </a>
                                                    <?php elseif ($job['status'] === 'printing'): ?>
                                                        <a class="dropdown-item" href="#" onclick="updateJobProgress(<?= $job['id'] ?>)">
                                                            <i class="fa fa-sync"></i> Atualizar Progresso
                                                        </a>
                                                        <a class="dropdown-item" href="#" onclick="pauseJob(<?= $job['id'] ?>)">
                                                            <i class="fa fa-pause"></i> Pausar
                                                        </a>
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item text-danger" href="#" onclick="markJobFailed(<?= $job['id'] ?>)">
                                                            <i class="fa fa-times-circle"></i> Marcar Falha
                                                        </a>
                                                    <?php elseif ($job['status'] === 'paused'): ?>
                                                        <a class="dropdown-item" href="#" onclick="resumeJob(<?= $job['id'] ?>)">
                                                            <i class="fa fa-play"></i> Retomar
                                                        </a>
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item text-danger" href="#" onclick="cancelJob(<?= $job['id'] ?>)">
                                                            <i class="fa fa-times"></i> Cancelar
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="job-details small">
                                        <div class="row">
                                            <div class="col-6">
                                                <div><strong>Cliente:</strong> <?= htmlspecialchars($job['customer_name']) ?></div>
                                                <div><strong>Pedido:</strong> #<?= $job['order_id'] ?></div>
                                                <div><strong>Criado:</strong> <?= date('d/m/Y H:i', strtotime($job['created_at'])) ?></div>
                                            </div>
                                            <div class="col-6">
                                                <div><strong>Filamento:</strong> <?= htmlspecialchars($job['filament_type']) ?> · <?= htmlspecialchars($job['filament_color']) ?></div>
                                                <div><strong>Uso Est.:</strong> <?= number_format($job['filament_usage_grams'], 0, ',', '.') ?> g</div>
                                                <div><strong>Tempo Est.:</strong> <?= number_format($job['estimated_print_time_hours'], 1, ',', '.') ?> h</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($job['status'] === 'printing'): ?>
                                        <div class="job-progress">
                                            <div class="d-flex justify-content-between small text-muted">
                                                <span>Progresso</span>
                                                <span><?= $job['progress'] ?>%</span>
                                            </div>
                                            <div class="progress mt-1">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?= $job['progress'] ?>%;" 
                                                     aria-valuenow="<?= $job['progress'] ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($job['printer_name']) && $job['status'] !== 'pending'): ?>
                                        <div class="job-printer small mt-2">
                                            <span class="text-muted"><i class="fa fa-print"></i> <?= htmlspecialchars($job['printer_name']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="<?= BASE_URL ?>admin/print-queue" class="btn btn-outline-primary btn-sm">
                        Ver toda a fila
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos de Uso de Filamento e Estatísticas -->
    <div class="row mt-4">
        <!-- Gráfico de Uso de Filamento -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Uso de Filamento</h5>
                    <div class="card-tools">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary filament-view-toggle active" data-view="type">
                                Por Tipo
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary filament-view-toggle" data-view="color">
                                Por Cor
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="filamentChart" width="100%" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Estatísticas de Impressão -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Estatísticas de Impressão</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="printStatsChart" width="100%" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Histórico de Impressões e Alertas -->
    <div class="row mt-4">
        <!-- Histórico de Impressões -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Histórico de Impressões</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="print-history-table">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Cliente</th>
                                    <th>Filamento</th>
                                    <th>Impressora</th>
                                    <th>Tempo</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody id="print-history-body">
                                <!-- Carregado via JavaScript -->
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Carregando...</span>
                                        </div>
                                        Carregando histórico...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="<?= BASE_URL ?>admin/print-queue/history" class="btn btn-outline-primary btn-sm">
                        Ver histórico completo
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Alertas de Filamento -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Alertas de Filamento</h5>
                </div>
                <div class="card-body">
                    <div id="filament-alerts">
                        <!-- Carregado via JavaScript -->
                        <div class="text-center">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            Verificando alertas...
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="<?= BASE_URL ?>admin/inventory/filaments" class="btn btn-outline-primary btn-sm">
                        Gerenciar filamentos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para adicionar impressora -->
<div class="modal fade" id="addPrinterModal" tabindex="-1" aria-labelledby="addPrinterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPrinterModalLabel">Adicionar Nova Impressora</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="add-printer-form">
                    <div class="mb-3">
                        <label for="printer-name" class="form-label">Nome da Impressora</label>
                        <input type="text" class="form-control" id="printer-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="printer-model" class="form-label">Modelo</label>
                        <input type="text" class="form-control" id="printer-model" name="model" required>
                    </div>
                    <div class="mb-3">
                        <label for="printer-status" class="form-label">Status Inicial</label>
                        <select class="form-select" id="printer-status" name="status" required>
                            <option value="idle">Ocioso</option>
                            <option value="active">Ativo</option>
                            <option value="maintenance">Manutenção</option>
                            <option value="offline">Offline</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="printer-notes" class="form-label">Notas (opcional)</label>
                        <textarea class="form-control" id="printer-notes" name="notes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="save-printer-btn">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar o horário da última atualização
    updateLastUpdateTime();
    
    // Carregar gráficos e dados
    loadFilamentUsageChart('type');
    loadPrintStatsChart();
    loadPrintHistory();
    loadFilamentAlerts();
    
    // Botão de atualizar dados
    document.getElementById('refresh-data').addEventListener('click', function() {
        refreshAllData();
    });
    
    // Alternar visualização de filamento
    document.querySelectorAll('.filament-view-toggle').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.filament-view-toggle').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            const view = this.getAttribute('data-view');
            loadFilamentUsageChart(view);
        });
    });
    
    // Filtrar fila de impressão
    document.querySelectorAll('.filter-print-queue').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.filter-print-queue').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            const status = this.getAttribute('data-status');
            filterPrintQueue(status);
        });
    });
    
    // Salvar nova impressora
    document.getElementById('save-printer-btn').addEventListener('click', function() {
        saveNewPrinter();
    });
});

// Função para atualizar o horário da última atualização
function updateLastUpdateTime() {
    const now = new Date();
    const formattedTime = now.toLocaleTimeString('pt-BR');
    document.getElementById('last-update-time').textContent = 'Última atualização: ' + formattedTime;
}

// Função para atualizar todos os dados
function refreshAllData() {
    // Mostrar indicador de carregamento
    const refreshBtn = document.getElementById('refresh-data');
    refreshBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Atualizando...';
    refreshBtn.disabled = true;
    
    // Recarregar todos os dados
    Promise.all([
        fetch('<?= BASE_URL ?>admin/dashboard/api/printer_status').then(res => res.json()),
        fetch('<?= BASE_URL ?>admin/dashboard/api/print_queue_status').then(res => res.json())
    ])
    .then(([printerData, queueData]) => {
        // Atualizar a página com os novos dados
        location.reload();
    })
    .catch(error => {
        console.error('Erro ao atualizar dados:', error);
        alert('Ocorreu um erro ao atualizar os dados. Por favor, tente novamente.');
    })
    .finally(() => {
        // Restaurar botão
        refreshBtn.innerHTML = '<i class="fa fa-sync-alt"></i> Atualizar';
        refreshBtn.disabled = false;
        
        // Atualizar horário
        updateLastUpdateTime();
    });
}

// Função para carregar o gráfico de uso de filamento
function loadFilamentUsageChart(view) {
    const filamentData = <?= json_encode($filamentUsage) ?>;
    renderFilamentUsageChart(filamentData, view);
}

// Função para renderizar o gráfico de uso de filamento
function renderFilamentUsageChart(data, view) {
    const ctx = document.getElementById('filamentChart').getContext('2d');
    
    // Se já existe um gráfico, destruí-lo
    if (window.filamentChart instanceof Chart) {
        window.filamentChart.destroy();
    }
    
    // Determinar quais dados usar com base na visualização
    let chartData;
    if (view === 'type') {
        chartData = data.byType;
    } else {
        chartData = data.byColor;
    }
    
    // Preparar os dados para o gráfico
    const labels = chartData.map(item => view === 'type' ? item.filament_type : item.filament_color);
    const values = chartData.map(item => parseFloat(item.total_grams));
    
    // Gerar cores para o gráfico
    const backgroundColors = generateColorsForFilament(labels, view);
    
    // Criar o gráfico
    window.filamentChart = new Chart(ctx, {
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
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = parseFloat(context.raw).toFixed(0) + 'g';
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

// Função para gerar cores para filamentos
function generateColorsForFilament(labels, view) {
    // Cores predefinidas para tipos comuns de filamento
    const typeColors = {
        'PLA': '#4e73df',
        'ABS': '#e74a3b',
        'PETG': '#1cc88a',
        'TPU': '#f6c23e',
        'Resina': '#6f42c1',
        'Nylon': '#36b9cc',
        'PC': '#5a5c69',
        'PVA': '#fd7e14'
    };
    
    // Cores predefinidas para cores comuns de filamento
    const colorColors = {
        'Preto': '#000000',
        'Branco': '#ffffff',
        'Cinza': '#a0a0a0',
        'Vermelho': '#e74a3b',
        'Azul': '#4e73df',
        'Verde': '#1cc88a',
        'Amarelo': '#f6c23e',
        'Laranja': '#fd7e14',
        'Roxo': '#6f42c1',
        'Rosa': '#e83e8c',
        'Marrom': '#825444',
        'Transparente': 'rgba(200, 200, 200, 0.3)'
    };
    
    // Escolher o conjunto de cores com base na visualização
    const colorMap = view === 'type' ? typeColors : colorColors;
    
    // Gerar cores para cada item
    return labels.map(label => {
        // Se temos uma cor predefinida, usá-la
        if (colorMap[label]) {
            return colorMap[label];
        }
        
        // Caso contrário, gerar uma cor aleatória
        const r = Math.floor(Math.random() * 200) + 55;
        const g = Math.floor(Math.random() * 200) + 55;
        const b = Math.floor(Math.random() * 200) + 55;
        return `rgb(${r}, ${g}, ${b})`;
    });
}

// Função para carregar o gráfico de estatísticas de impressão
function loadPrintStatsChart() {
    // Obter dados simulados (substitua por uma chamada AJAX para dados reais)
    const printStats = {
        lastWeek: {
            completed: 12,
            failed: 2
        },
        thisWeek: {
            completed: 14,
            failed: 1
        }
    };
    
    renderPrintStatsChart(printStats);
}

// Função para renderizar o gráfico de estatísticas de impressão
function renderPrintStatsChart(data) {
    const ctx = document.getElementById('printStatsChart').getContext('2d');
    
    // Se já existe um gráfico, destruí-lo
    if (window.printStatsChart instanceof Chart) {
        window.printStatsChart.destroy();
    }
    
    // Preparar os dados para o gráfico
    const labels = ['Semana Passada', 'Esta Semana'];
    const completedData = [data.lastWeek.completed, data.thisWeek.completed];
    const failedData = [data.lastWeek.failed, data.thisWeek.failed];
    
    // Calcular taxas de sucesso
    const successRateLastWeek = (data.lastWeek.completed / (data.lastWeek.completed + data.lastWeek.failed)) * 100;
    const successRateThisWeek = (data.thisWeek.completed / (data.thisWeek.completed + data.thisWeek.failed)) * 100;
    
    // Criar o gráfico
    window.printStatsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Concluídos',
                    data: completedData,
                    backgroundColor: '#1cc88a',
                    borderColor: '#1cc88a',
                    borderWidth: 1
                },
                {
                    label: 'Falhas',
                    data: failedData,
                    backgroundColor: '#e74a3b',
                    borderColor: '#e74a3b',
                    borderWidth: 1
                },
                {
                    label: 'Taxa de Sucesso (%)',
                    data: [successRateLastWeek, successRateThisWeek],
                    type: 'line',
                    fill: false,
                    borderColor: '#4e73df',
                    backgroundColor: '#4e73df',
                    pointBackgroundColor: '#4e73df',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#4e73df',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
                        text: 'Número de Impressões'
                    }
                },
                y1: {
                    beginAtZero: true,
                    max: 100,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Taxa de Sucesso (%)'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
}

// Função para carregar o histórico de impressões
function loadPrintHistory() {
    // Simular uma chamada AJAX (substitua por uma chamada real)
    setTimeout(() => {
        const historyTableBody = document.getElementById('print-history-body');
        
        // Dados simulados (substitua por dados reais)
        const historyData = [
            {
                product_name: 'Miniatura Dragão',
                customer_name: 'João Silva',
                filament: 'PLA Vermelho',
                printer_name: 'Ender 3 Pro',
                print_time_hours: 4.5,
                status: 'completed',
                completed_at: '2025-03-29 15:32:00'
            },
            {
                product_name: 'Suporte de Mesa',
                customer_name: 'Maria Oliveira',
                filament: 'PETG Preto',
                printer_name: 'Prusa i3 MK3S',
                print_time_hours: 2.2,
                status: 'completed',
                completed_at: '2025-03-28 18:45:00'
            },
            {
                product_name: 'Case para Dice',
                customer_name: 'Pedro Santos',
                filament: 'PLA Azul',
                printer_name: 'Ender 5 Plus',
                print_time_hours: 3.8,
                status: 'failed',
                completed_at: '2025-03-28 12:20:00'
            },
            {
                product_name: 'Miniatura Guerreiro',
                customer_name: 'Ana Costa',
                filament: 'PLA Dourado',
                printer_name: 'Ender 3 Pro',
                print_time_hours: 5.1,
                status: 'completed',
                completed_at: '2025-03-27 21:10:00'
            },
            {
                product_name: 'Porta Cartas RPG',
                customer_name: 'Lucas Mendes',
                filament: 'PETG Transparente',
                printer_name: 'Prusa i3 MK3S',
                print_time_hours: 6.3,
                status: 'completed',
                completed_at: '2025-03-27 16:40:00'
            }
        ];
        
        // Gerar HTML para a tabela
        let html = '';
        
        historyData.forEach(item => {
            html += `
                <tr>
                    <td>${htmlEscape(item.product_name)}</td>
                    <td>${htmlEscape(item.customer_name)}</td>
                    <td>${htmlEscape(item.filament)}</td>
                    <td>${htmlEscape(item.printer_name)}</td>
                    <td>${item.print_time_hours.toFixed(1)} h</td>
                    <td><span class="badge ${getJobStatusBadgeClass(item.status)}">${getJobStatusName(item.status)}</span></td>
                    <td>${formatDateTime(item.completed_at)}</td>
                </tr>
            `;
        });
        
        historyTableBody.innerHTML = html;
    }, 1000);
}

// Função para carregar alertas de filamento
function loadFilamentAlerts() {
    // Simular uma chamada AJAX (substitua por uma chamada real)
    setTimeout(() => {
        const alertsContainer = document.getElementById('filament-alerts');
        
        // Dados simulados (substitua por dados reais)
        const alertsData = [
            {
                filament_type: 'PLA',
                filament_color: 'Preto',
                stock_grams: 250,
                threshold_grams: 500,
                alert_level: 'danger'
            },
            {
                filament_type: 'PETG',
                filament_color: 'Azul',
                stock_grams: 450,
                threshold_grams: 500,
                alert_level: 'warning'
            },
            {
                filament_type: 'TPU',
                filament_color: 'Branco',
                stock_grams: 180,
                threshold_grams: 300,
                alert_level: 'danger'
            }
        ];
        
        // Gerar HTML para os alertas
        let html = '';
        
        if (alertsData.length === 0) {
            html = '<div class="alert alert-success">Nenhum alerta de filamento. Todos os níveis estão adequados.</div>';
        } else {
            html = '<div class="filament-alerts-list">';
            
            alertsData.forEach(alert => {
                html += `
                    <div class="alert alert-${alert.alert_level} d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${htmlEscape(alert.filament_type)} ${htmlEscape(alert.filament_color)}</strong>
                            <div class="small">Estoque: ${alert.stock_grams}g (Mín: ${alert.threshold_grams}g)</div>
                        </div>
                        <button class="btn btn-sm btn-outline-${alert.alert_level}" onclick="orderFilament('${alert.filament_type}', '${alert.filament_color}')">
                            <i class="fa fa-shopping-cart"></i>
                        </button>
                    </div>
                `;
            });
            
            html += '</div>';
        }
        
        alertsContainer.innerHTML = html;
    }, 1200);
}

// Função para filtrar a fila de impressão por status
function filterPrintQueue(status) {
    const items = document.querySelectorAll('.print-job-item');
    
    items.forEach(item => {
        if (status === 'all' || item.getAttribute('data-status') === status) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

// Funções para ações da interface
function viewPrinterDetails(printerId) {
    alert('Visualizar detalhes da impressora ' + printerId);
    // Implementar visualização detalhada da impressora
}

function changePrinterStatus(printerId) {
    alert('Alterar status da impressora ' + printerId);
    // Implementar mudança de status da impressora
}

function pausePrinting(printerId) {
    alert('Pausar impressão na impressora ' + printerId);
    // Implementar pausa de impressão
}

function assignJob(printerId) {
    alert('Atribuir job à impressora ' + printerId);
    // Implementar atribuição de job
}

function viewJobDetails(jobId) {
    alert('Visualizar detalhes do job ' + jobId);
    // Implementar visualização detalhada do job
}

function assignJobToPrinter(jobId) {
    alert('Atribuir job ' + jobId + ' a uma impressora');
    // Implementar atribuição de job a uma impressora
}

function editJob(jobId) {
    alert('Editar job ' + jobId);
    // Implementar edição de job
}

function cancelJob(jobId) {
    if (confirm('Tem certeza que deseja cancelar o job ' + jobId + '?')) {
        alert('Job ' + jobId + ' cancelado');
        // Implementar cancelamento de job
    }
}

function updateJobProgress(jobId) {
    alert('Atualizar progresso do job ' + jobId);
    // Implementar atualização de progresso
}

function pauseJob(jobId) {
    alert('Pausar job ' + jobId);
    // Implementar pausa de job
}

function resumeJob(jobId) {
    alert('Retomar job ' + jobId);
    // Implementar retomada de job
}

function markJobFailed(jobId) {
    if (confirm('Tem certeza que deseja marcar o job ' + jobId + ' como falha?')) {
        alert('Job ' + jobId + ' marcado como falha');
        // Implementar marcação de falha
    }
}

function orderFilament(type, color) {
    alert('Solicitar compra de filamento ' + type + ' ' + color);
    // Implementar solicitação de compra
}

function saveNewPrinter() {
    const form = document.getElementById('add-printer-form');
    
    // Validar formulário
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Obter dados do formulário
    const formData = new FormData(form);
    const printerData = Object.fromEntries(formData.entries());
    
    // Enviar dados para o servidor (simular)
    alert('Impressora adicionada com sucesso!');
    
    // Fechar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('addPrinterModal'));
    modal.hide();
    
    // Recarregar a página para mostrar a nova impressora
    setTimeout(() => {
        location.reload();
    }, 500);
}

// Funções auxiliares
function getPrinterCardClass(status) {
    const classMap = {
        'active': 'printer-active',
        'idle': 'printer-idle',
        'printing': 'printer-printing',
        'maintenance': 'printer-maintenance',
        'offline': 'printer-offline',
        'error': 'printer-error'
    };
    
    return classMap[status] || '';
}

function getPrinterStatusBadgeClass(status) {
    const classMap = {
        'active': 'bg-success',
        'idle': 'bg-info',
        'printing': 'bg-primary',
        'maintenance': 'bg-warning',
        'offline': 'bg-secondary',
        'error': 'bg-danger'
    };
    
    return classMap[status] || 'bg-secondary';
}

function getPrinterStatusName(status) {
    const nameMap = {
        'active': 'Ativo',
        'idle': 'Ocioso',
        'printing': 'Imprimindo',
        'maintenance': 'Manutenção',
        'offline': 'Offline',
        'error': 'Erro'
    };
    
    return nameMap[status] || status;
}

function getPrintJobStatusBadgeClass(status) {
    const classMap = {
        'pending': 'bg-warning',
        'scheduled': 'bg-info',
        'printing': 'bg-primary',
        'paused': 'bg-secondary',
        'completed': 'bg-success',
        'failed': 'bg-danger',
        'canceled': 'bg-dark'
    };
    
    return classMap[status] || 'bg-secondary';
}

function getPrintJobStatusName(status) {
    const nameMap = {
        'pending': 'Pendente',
        'scheduled': 'Agendado',
        'printing': 'Imprimindo',
        'paused': 'Pausado',
        'completed': 'Concluído',
        'failed': 'Falha',
        'canceled': 'Cancelado'
    };
    
    return nameMap[status] || status;
}

function getJobStatusBadgeClass(status) {
    return status === 'completed' ? 'bg-success' : 'bg-danger';
}

function getJobStatusName(status) {
    return status === 'completed' ? 'Concluído' : 'Falha';
}

function formatDateTime(dateTimeStr) {
    const date = new Date(dateTimeStr);
    return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

function htmlEscape(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
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

.refresh-controls {
    display: flex;
    align-items: center;
}

.metrics-summary .card {
    height: 100%;
    transition: all 0.3s ease;
    border-left: 4px solid #4e73df;
}

.metrics-summary .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.metrics-summary .card:nth-child(1) {
    border-left-color: #4e73df; /* Azul */
}

.metrics-summary .card:nth-child(2) {
    border-left-color: #1cc88a; /* Verde */
}

.metrics-summary .card:nth-child(3) {
    border-left-color: #36b9cc; /* Ciano */
}

.metrics-summary .card:nth-child(4) {
    border-left-color: #f6c23e; /* Amarelo */
}

.metric-icon {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 2rem;
    opacity: 0.3;
}

.metric-value {
    margin-bottom: 5px;
    font-weight: 700;
}

.metric-label {
    font-size: 0.8rem;
    color: #888;
    margin-bottom: 0;
}

.metric-status-counts {
    display: flex;
    gap: 5px;
    margin-top: 5px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

/* Estilos para o grid de impressoras */
.printer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.printer-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
}

.printer-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.15rem 0.5rem rgba(0, 0, 0, 0.1);
}

.printer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.printer-name {
    margin: 0;
    font-weight: 600;
}

.printer-body {
    flex-grow: 1;
    min-height: 80px;
}

.printer-footer {
    margin-top: 15px;
    display: flex;
    justify-content: flex-end;
}

/* Classes de status para cartões de impressora */
.printer-idle {
    background-color: #f8f9fc;
    border-left: 4px solid #36b9cc;
}

.printer-active {
    background-color: #f7fff9;
    border-left: 4px solid #1cc88a;
}

.printer-printing {
    background-color: #f1f8ff;
    border-left: 4px solid #4e73df;
}

.printer-maintenance {
    background-color: #fff8e6;
    border-left: 4px solid #f6c23e;
}

.printer-offline {
    background-color: #f8f9fc;
    border-left: 4px solid #858796;
}

.printer-error {
    background-color: #fff5f5;
    border-left: 4px solid #e74a3b;
}

/* Estilos para itens da fila de impressão */
.print-queue-list {
    max-height: 500px;
    overflow-y: auto;
}

.print-job-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.print-job-item:last-child {
    margin-bottom: 0;
}

.job-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.job-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.job-title {
    margin: 0;
    font-weight: 600;
}

.job-details {
    background-color: #f8f9fc;
    border-radius: 5px;
    padding: 10px;
}

.job-progress {
    margin-top: 10px;
}

/* Estilos para alertas de filamento */
.filament-alerts-list {
    max-height: 300px;
    overflow-y: auto;
}

/* Ajustes para telas menores */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .refresh-controls {
        margin-top: 15px;
    }
    
    .metrics-summary .col-md-3 {
        margin-bottom: 15px;
    }
    
    .printer-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
// Funções auxiliares para formatar status na view
function getPrinterCardClass($status) {
    $classMap = [
        'active' => 'printer-active',
        'idle' => 'printer-idle',
        'printing' => 'printer-printing',
        'maintenance' => 'printer-maintenance',
        'offline' => 'printer-offline',
        'error' => 'printer-error'
    ];
    
    return isset($classMap[$status]) ? $classMap[$status] : '';
}

function getPrinterStatusBadgeClass($status) {
    $classMap = [
        'active' => 'bg-success',
        'idle' => 'bg-info',
        'printing' => 'bg-primary',
        'maintenance' => 'bg-warning',
        'offline' => 'bg-secondary',
        'error' => 'bg-danger'
    ];
    
    return isset($classMap[$status]) ? $classMap[$status] : 'bg-secondary';
}

function getPrinterStatusName($status) {
    $nameMap = [
        'active' => 'Ativo',
        'idle' => 'Ocioso',
        'printing' => 'Imprimindo',
        'maintenance' => 'Manutenção',
        'offline' => 'Offline',
        'error' => 'Erro'
    ];
    
    return isset($nameMap[$status]) ? $nameMap[$status] : $status;
}

function getPrintJobStatusBadgeClass($status) {
    $classMap = [
        'pending' => 'bg-warning',
        'scheduled' => 'bg-info',
        'printing' => 'bg-primary',
        'paused' => 'bg-secondary',
        'completed' => 'bg-success',
        'failed' => 'bg-danger',
        'canceled' => 'bg-dark'
    ];
    
    return isset($classMap[$status]) ? $classMap[$status] : 'bg-secondary';
}

function getPrintJobStatusName($status) {
    $nameMap = [
        'pending' => 'Pendente',
        'scheduled' => 'Agendado',
        'printing' => 'Imprimindo',
        'paused' => 'Pausado',
        'completed' => 'Concluído',
        'failed' => 'Falha',
        'canceled' => 'Cancelado'
    ];
    
    return isset($nameMap[$status]) ? $nameMap[$status] : $status;
}

// Incluir footer
include_once APP_PATH . '/views/admin/includes/footer.php';
?>
