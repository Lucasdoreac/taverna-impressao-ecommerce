<?php include_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<!-- Dashboard de Monitoramento de Impressões -->
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="bi bi-printer"></i> Monitoramento de Impressões</h1>
                
                <div>
                    <a href="<?= BASE_URL ?>admin/impressoes/list" class="btn btn-outline-primary me-2">
                        <i class="bi bi-list"></i> Listar Todas as Impressões
                    </a>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newPrintStatusModal">
                        <i class="bi bi-plus-circle"></i> Novo Monitoramento
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Resumo Estatístico -->
    <div class="row">
        <!-- Total de Impressões Ativas -->
        <div class="col-md-3 mb-4">
            <div class="card h-100 border-primary">
                <div class="card-body">
                    <h5 class="card-title text-primary">
                        <i class="bi bi-activity"></i> Impressões Ativas
                    </h5>
                    <p class="display-4 text-center">
                        <?= isset($statistics['by_status']['printing']['count']) ? 
                            $statistics['by_status']['printing']['count'] : 0 ?>
                    </p>
                    <div class="text-center text-muted small">
                        <?= isset($statistics['by_status']['paused']['count']) ? 
                            $statistics['by_status']['paused']['count'] . ' pausadas' : '0 pausadas' ?>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?= BASE_URL ?>admin/impressoes/list?status=printing" class="card-link">
                        Ver impressões ativas
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Total de Impressões Pendentes -->
        <div class="col-md-3 mb-4">
            <div class="card h-100 border-info">
                <div class="card-body">
                    <h5 class="card-title text-info">
                        <i class="bi bi-hourglass-split"></i> Impressões Pendentes
                    </h5>
                    <p class="display-4 text-center">
                        <?= isset($statistics['by_status']['pending']['count']) ? 
                            $statistics['by_status']['pending']['count'] + 
                            (isset($statistics['by_status']['preparing']['count']) ? 
                             $statistics['by_status']['preparing']['count'] : 0) : 0 ?>
                    </p>
                    <div class="text-center text-muted small">
                        Aguardando processamento
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?= BASE_URL ?>admin/impressoes/list?status=pending" class="card-link">
                        Ver impressões pendentes
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Conclusões Hoje -->
        <div class="col-md-3 mb-4">
            <div class="card h-100 border-success">
                <div class="card-body">
                    <h5 class="card-title text-success">
                        <i class="bi bi-check-circle"></i> Conclusões Hoje
                    </h5>
                    <p class="display-4 text-center">
                        <?= count($recentlyCompleted) ?>
                    </p>
                    <div class="text-center text-muted small">
                        Média semanal: <?= isset($statistics['temporal']['completed']) ? 
                            round($statistics['temporal']['completed'] / 7, 1) : '0' ?> por dia
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?= BASE_URL ?>admin/impressoes/list?status=completed" class="card-link">
                        Ver impressões concluídas
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Falhas Recentes -->
        <div class="col-md-3 mb-4">
            <div class="card h-100 border-danger">
                <div class="card-body">
                    <h5 class="card-title text-danger">
                        <i class="bi bi-exclamation-triangle"></i> Falhas Recentes
                    </h5>
                    <p class="display-4 text-center">
                        <?= isset($statistics['temporal']['failed']) ? 
                            $statistics['temporal']['failed'] : 0 ?>
                    </p>
                    <div class="text-center text-muted small">
                        Nos últimos 7 dias
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <a href="<?= BASE_URL ?>admin/impressoes/list?status=failed" class="card-link">
                        Ver impressões falhas
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Impressões Ativas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="bi bi-printer"></i> Impressões em Andamento</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($activePrints)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Não há impressões em andamento no momento.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Produto</th>
                                        <th>Cliente</th>
                                        <th>Status</th>
                                        <th>Progresso</th>
                                        <th>Início</th>
                                        <th>Est. Conclusão</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activePrints as $print): 
                                        $statusClass = 'badge bg-' . PrintStatusHelper::getStatusBootstrapClass($print['status']);
                                        $productName = isset($print['product_name']) ? $print['product_name'] : 'Produto #' . $print['product_id'];
                                        $customerName = isset($print['customer_name']) ? $print['customer_name'] : 'Cliente #' . $print['order_id'];
                                        $startedAt = isset($print['started_at']) ? 
                                            date('d/m/Y H:i', strtotime($print['started_at'])) : '-';
                                        $estimatedCompletion = isset($print['estimated_completion']) ? 
                                            date('d/m/Y H:i', strtotime($print['estimated_completion'])) : '-';
                                    ?>
                                    <tr>
                                        <td><?= $print['id'] ?></td>
                                        <td><?= htmlspecialchars($productName) ?></td>
                                        <td><?= htmlspecialchars($customerName) ?></td>
                                        <td>
                                            <span class="<?= $statusClass ?>">
                                                <?= ucfirst(PrintStatusModel::getAvailableStatuses()[$print['status']] ?? $print['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-<?= PrintStatusHelper::getStatusBootstrapClass($print['status']) ?>" 
                                                     role="progressbar" 
                                                     style="width: <?= $print['progress_percentage'] ?>%;" 
                                                     aria-valuenow="<?= $print['progress_percentage'] ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <?= number_format($print['progress_percentage'], 1) ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= $startedAt ?></td>
                                        <td><?= $estimatedCompletion ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="<?= BASE_URL ?>admin/impressao/<?= $print['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                
                                                <?php if ($print['status'] === 'printing'): ?>
                                                <button onclick="executePrintAction(<?= $print['id'] ?>, 'pause')" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pause-fill"></i>
                                                </button>
                                                <?php elseif ($print['status'] === 'paused'): ?>
                                                <button onclick="executePrintAction(<?= $print['id'] ?>, 'resume')" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-play-fill"></i>
                                                </button>
                                                <?php elseif ($print['status'] === 'pending' || $print['status'] === 'preparing'): ?>
                                                <button onclick="executePrintAction(<?= $print['id'] ?>, 'start')" class="btn btn-sm btn-success">
                                                    <i class="bi bi-play-fill"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($print['status'] !== 'completed' && $print['status'] !== 'failed' && $print['status'] !== 'canceled'): ?>
                                                <button onclick="executePrintAction(<?= $print['id'] ?>, 'cancel')" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
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
    
    <!-- Estatísticas e Gráficos -->
    <div class="row mb-4">
        <!-- Estatísticas por Status -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h4>Distribuição por Status</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Contagem</th>
                                    <th>Progresso Médio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $availableStatuses = PrintStatusModel::getAvailableStatuses();
                                foreach ($availableStatuses as $statusKey => $statusName): 
                                    $count = isset($statistics['by_status'][$statusKey]['count']) ? 
                                        $statistics['by_status'][$statusKey]['count'] : 0;
                                    $avgProgress = isset($statistics['by_status'][$statusKey]['avg_progress']) ? 
                                        $statistics['by_status'][$statusKey]['avg_progress'] : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?= PrintStatusHelper::getStatusBootstrapClass($statusKey) ?>">
                                            <?= $statusName ?>
                                        </span>
                                    </td>
                                    <td><?= $count ?></td>
                                    <td>
                                        <?php if (in_array($statusKey, ['printing', 'paused', 'completed'])): ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-<?= PrintStatusHelper::getStatusBootstrapClass($statusKey) ?>" 
                                                 role="progressbar" 
                                                 style="width: <?= $avgProgress ?>%;" 
                                                 aria-valuenow="<?= $avgProgress ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?= number_format($avgProgress, 1) ?>%
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Estatísticas Temporais -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h4>Estatísticas da Semana</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($statistics['temporal'])): ?>
                    <div class="row mb-4">
                        <div class="col-6">
                            <div class="mb-3">
                                <h5>Total de Impressões</h5>
                                <p class="fs-4"><?= $statistics['temporal']['total_prints'] ?? 0 ?></p>
                            </div>
                            <div class="mb-3">
                                <h5>Taxa de Sucesso</h5>
                                <p class="fs-4">
                                    <?php
                                    $completed = $statistics['temporal']['completed'] ?? 0;
                                    $total = $statistics['temporal']['total_prints'] ?? 0;
                                    $successRate = $total > 0 ? ($completed / $total) * 100 : 0;
                                    echo number_format($successRate, 1) . '%';
                                    ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <h5>Tempo Médio de Impressão</h5>
                                <p class="fs-4"><?= $statistics['temporal']['avg_print_time_formatted'] ?? 'N/A' ?></p>
                            </div>
                            <div class="mb-3">
                                <h5>Impressões Ativas</h5>
                                <p class="fs-4"><?= $statistics['temporal']['active'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($statistics['by_day'])): ?>
                    <h5 class="mb-3">Impressões por Dia da Semana</h5>
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="weekdayChart"></canvas>
                    </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Estatísticas semanais não disponíveis.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Impressões Recentes Concluídas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="bi bi-check-circle"></i> Impressões Concluídas Recentemente</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($recentlyCompleted)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Não há impressões concluídas recentemente.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Produto</th>
                                        <th>Cliente</th>
                                        <th>Status</th>
                                        <th>Início</th>
                                        <th>Conclusão</th>
                                        <th>Tempo Total</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentlyCompleted as $print): 
                                        $statusClass = 'badge bg-' . PrintStatusHelper::getStatusBootstrapClass($print['status']);
                                        $productName = isset($print['product_name']) ? $print['product_name'] : 'Produto #' . $print['product_id'];
                                        $customerName = isset($print['customer_name']) ? $print['customer_name'] : 'Cliente #' . $print['order_id'];
                                        $startedAt = isset($print['started_at']) ? 
                                            date('d/m/Y H:i', strtotime($print['started_at'])) : '-';
                                        $completedAt = isset($print['completed_at']) ? 
                                            date('d/m/Y H:i', strtotime($print['completed_at'])) : '-';
                                            
                                        // Calcular tempo total
                                        $totalTime = '-';
                                        if (isset($print['started_at']) && isset($print['completed_at'])) {
                                            $start = strtotime($print['started_at']);
                                            $end = strtotime($print['completed_at']);
                                            $totalSeconds = $end - $start;
                                            $hours = floor($totalSeconds / 3600);
                                            $minutes = floor(($totalSeconds % 3600) / 60);
                                            $totalTime = $hours > 0 ? "$hours h $minutes min" : "$minutes min";
                                        }
                                    ?>
                                    <tr>
                                        <td><?= $print['id'] ?></td>
                                        <td><?= htmlspecialchars($productName) ?></td>
                                        <td><?= htmlspecialchars($customerName) ?></td>
                                        <td>
                                            <span class="<?= $statusClass ?>">
                                                <?= ucfirst(PrintStatusModel::getAvailableStatuses()[$print['status']] ?? $print['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= $startedAt ?></td>
                                        <td><?= $completedAt ?></td>
                                        <td><?= $totalTime ?></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>admin/impressao/<?= $print['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> Detalhes
                                            </a>
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
</div>

<!-- Modal para Novo Status de Impressão -->
<div class="modal fade" id="newPrintStatusModal" tabindex="-1" aria-labelledby="newPrintStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newPrintStatusModalLabel">Novo Monitoramento de Impressão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?= BASE_URL ?>admin/impressoes/add-status" method="post">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="order_id" class="form-label">Pedido</label>
                            <input type="number" class="form-control" id="order_id" name="order_id" required>
                        </div>
                        <div class="col-md-4">
                            <label for="product_id" class="form-label">Produto</label>
                            <input type="number" class="form-control" id="product_id" name="product_id" required>
                        </div>
                        <div class="col-md-4">
                            <label for="print_queue_id" class="form-label">ID da Fila</label>
                            <input type="number" class="form-control" id="print_queue_id" name="print_queue_id" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status Inicial</label>
                            <select class="form-select" id="status" name="status" required>
                                <?php foreach (PrintStatusModel::getAvailableStatuses() as $key => $value): ?>
                                <option value="<?= $key ?>"><?= $value ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="progress_percentage" class="form-label">Progresso (%)</label>
                            <input type="number" class="form-control" id="progress_percentage" name="progress_percentage" 
                                   min="0" max="100" step="0.1" value="0">
                        </div>
                        <div class="col-md-4">
                            <label for="printer_id" class="form-label">ID da Impressora (opcional)</label>
                            <input type="text" class="form-control" id="printer_id" name="printer_id">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Observações</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Criar Monitoramento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Script para ações de impressão -->
<script>
function executePrintAction(printId, action) {
    if (!confirm(`Tem certeza que deseja ${getActionText(action)} esta impressão?`)) {
        return;
    }
    
    // Obter o token CSRF do meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Preparar dados para envio
    const formData = new FormData();
    formData.append('print_status_id', printId);
    formData.append('action', action);
    formData.append('csrf_token', csrfToken);
    
    // Enviar requisição
    fetch('<?= BASE_URL ?>admin/impressoes/action', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recarregar a página para mostrar a mudança
            window.location.reload();
        } else {
            alert(`Erro: ${data.message}`);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Ocorreu um erro ao executar a ação. Por favor, tente novamente.');
    });
}

function getActionText(action) {
    switch (action) {
        case 'pause': return 'pausar';
        case 'resume': return 'retomar';
        case 'cancel': return 'cancelar';
        case 'start': return 'iniciar';
        case 'complete': return 'marcar como concluída';
        case 'fail': return 'marcar como falha';
        default: return action;
    }
}

// Configuração do gráfico (se houver dados)
<?php if (!empty($statistics['by_day'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('weekdayChart').getContext('2d');
    
    const labels = <?= json_encode(array_keys($statistics['by_day'])) ?>;
    const data = <?= json_encode(array_values($statistics['by_day'])) ?>;
    
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Impressões por Dia',
                data: data,
                backgroundColor: 'rgba(0, 123, 255, 0.5)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
});
<?php endif; ?>
</script>

<?php include_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
