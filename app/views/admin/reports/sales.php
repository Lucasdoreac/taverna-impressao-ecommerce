<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<!-- Page Title -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Relatório de Vendas</h1>
        <p class="text-muted">Gere relatórios detalhados das vendas por período</p>
    </div>
    
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Relatório de Vendas</li>
        </ol>
    </nav>
</div>

<!-- Filter Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Filtros</h5>
    </div>
    <div class="card-body">
        <form id="salesReportForm" method="get" action="<?= BASE_URL ?>admin/relatorios/vendas">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Data Inicial</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?= isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : date('Y-m-01') ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">Data Final</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?= isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                    <label for="group_by" class="form-label">Agrupar por</label>
                    <select class="form-select" id="group_by" name="group_by">
                        <option value="daily" <?= (isset($_GET['group_by']) && $_GET['group_by'] == 'daily') ? 'selected' : '' ?>>Diário</option>
                        <option value="weekly" <?= (isset($_GET['group_by']) && $_GET['group_by'] == 'weekly') ? 'selected' : '' ?>>Semanal</option>
                        <option value="monthly" <?= (isset($_GET['group_by']) && $_GET['group_by'] == 'monthly') ? 'selected' : '' ?>>Mensal</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="payment_status" class="form-label">Status de Pagamento</label>
                    <select class="form-select" id="payment_status" name="payment_status">
                        <option value="all" <?= (!isset($_GET['payment_status']) || $_GET['payment_status'] == 'all') ? 'selected' : '' ?>>Todos</option>
                        <option value="paid" <?= (isset($_GET['payment_status']) && $_GET['payment_status'] == 'paid') ? 'selected' : '' ?>>Pagos</option>
                        <option value="pending" <?= (isset($_GET['payment_status']) && $_GET['payment_status'] == 'pending') ? 'selected' : '' ?>>Pendentes</option>
                        <option value="refunded" <?= (isset($_GET['payment_status']) && $_GET['payment_status'] == 'refunded') ? 'selected' : '' ?>>Reembolsados</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="order_status" class="form-label">Status do Pedido</label>
                    <select class="form-select" id="order_status" name="order_status">
                        <option value="all" <?= (!isset($_GET['order_status']) || $_GET['order_status'] == 'all') ? 'selected' : '' ?>>Todos</option>
                        <option value="pending" <?= (isset($_GET['order_status']) && $_GET['order_status'] == 'pending') ? 'selected' : '' ?>>Pendentes</option>
                        <option value="processing" <?= (isset($_GET['order_status']) && $_GET['order_status'] == 'processing') ? 'selected' : '' ?>>Processando</option>
                        <option value="shipped" <?= (isset($_GET['order_status']) && $_GET['order_status'] == 'shipped') ? 'selected' : '' ?>>Enviados</option>
                        <option value="delivered" <?= (isset($_GET['order_status']) && $_GET['order_status'] == 'delivered') ? 'selected' : '' ?>>Entregues</option>
                        <option value="canceled" <?= (isset($_GET['order_status']) && $_GET['order_status'] == 'canceled') ? 'selected' : '' ?>>Cancelados</option>
                    </select>
                </div>
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i> Filtrar
                        </button>
                        <button type="button" class="btn btn-success export-btn">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV
                        </button>
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-repeat me-1"></i> Limpar
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Sales Data -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Dados de Vendas</h5>
        <div class="d-flex align-items-center">
            <span class="me-3">Total: <strong class="fw-bold"><?= AdminHelper::formatMoney($totalSales) ?></strong></span>
            <span>Pedidos: <strong class="fw-bold"><?= $totalOrders ?></strong></span>
        </div>
    </div>
    <div class="card-body">
        <!-- Sales Chart -->
        <div class="sales-chart-container mb-4">
            <canvas id="salesReportChart" height="300"></canvas>
        </div>
        
        <!-- Sales Table -->
        <div class="table-responsive mt-4">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Período</th>
                        <th class="text-end">Nº de Pedidos</th>
                        <th class="text-end">Valor Total</th>
                        <th class="text-end">Ticket Médio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($salesData)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-3">Nenhum dado de venda encontrado para o período selecionado.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($salesData as $sale): ?>
                    <tr>
                        <td><?= $sale['date_formatted'] ?></td>
                        <td class="text-end"><?= $sale['count'] ?></td>
                        <td class="text-end"><?= AdminHelper::formatMoney($sale['total']) ?></td>
                        <td class="text-end"><?= AdminHelper::formatMoney($sale['count'] > 0 ? $sale['total'] / $sale['count'] : 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-group-divider">
                    <tr class="fw-bold">
                        <td>Total</td>
                        <td class="text-end"><?= $totalOrders ?></td>
                        <td class="text-end"><?= AdminHelper::formatMoney($totalSales) ?></td>
                        <td class="text-end"><?= AdminHelper::formatMoney($totalOrders > 0 ? $totalSales / $totalOrders : 0) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get form data and prepare export URL
    document.querySelector('.export-btn').addEventListener('click', function() {
        const form = document.getElementById('salesReportForm');
        const formData = new FormData(form);
        
        let queryParams = new URLSearchParams();
        for (let pair of formData.entries()) {
            queryParams.append(pair[0], pair[1]);
        }
        
        // Add export parameter
        queryParams.append('export', 'csv');
        
        // Redirect to export URL
        window.location.href = `<?= BASE_URL ?>admin/exportar/vendas?${queryParams.toString()}`;
    });
    
    // Reset button handler
    document.querySelector('button[type="reset"]').addEventListener('click', function() {
        setTimeout(function() {
            document.getElementById('start_date').value = '<?= date('Y-m-01') ?>';
            document.getElementById('end_date').value = '<?= date('Y-m-d') ?>';
        }, 10);
    });
    
    // Sales Chart
    const salesChartCtx = document.getElementById('salesReportChart').getContext('2d');
    const salesChartData = <?= json_encode($salesData) ?>;
    
    new Chart(salesChartCtx, {
        type: 'bar',
        data: {
            labels: salesChartData.map(item => item.date_formatted),
            datasets: [
                {
                    label: 'Vendas (R$)',
                    data: salesChartData.map(item => item.total),
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Número de Pedidos',
                    data: salesChartData.map(item => item.count),
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                    type: 'line',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Vendas (R$)'
                    },
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Número de Pedidos'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.datasetIndex === 0) {
                                label += 'R$ ' + context.raw.toLocaleString('pt-BR');
                            } else {
                                label += context.raw;
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>