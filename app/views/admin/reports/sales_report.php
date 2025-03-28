<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<!-- Page Title -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0"><?= $reportTitle ?></h1>
        <p class="text-muted mb-0"><?= $reportPeriod ?></p>
    </div>
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/relatorios">Relatórios</a></li>
        <li class="breadcrumb-item active">Vendas</li>
    </ol>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Filtros</h5>
    </div>
    <div class="card-body">
        <form action="<?= BASE_URL ?>admin/relatorios/vendas" method="get" class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Data Inicial</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01') ?>" required>
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">Data Final</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?= isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-3">
                <label for="group_by" class="form-label">Agrupar por</label>
                <select id="group_by" name="group_by" class="form-select">
                    <option value="daily" <?= (isset($_GET['group_by']) && $_GET['group_by'] == 'daily') ? 'selected' : '' ?>>Diário</option>
                    <option value="weekly" <?= (isset($_GET['group_by']) && $_GET['group_by'] == 'weekly') ? 'selected' : '' ?>>Semanal</option>
                    <option value="monthly" <?= (isset($_GET['group_by']) && $_GET['group_by'] == 'monthly') ? 'selected' : '' ?>>Mensal</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i> Aplicar Filtros
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i> Exportar
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <button type="submit" class="dropdown-item" name="format" value="csv">
                                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV
                                </button>
                            </li>
                            <li>
                                <button type="submit" class="dropdown-item" name="format" value="pdf">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Resumo -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total de Vendas</h6>
                        <h3 class="mb-0"><?= AdminHelper::formatMoney(array_sum(array_column($reportData, 'total'))) ?></h3>
                    </div>
                    <div class="bg-primary-subtle p-3 rounded-circle">
                        <i class="bi bi-cash-stack text-primary fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total de Pedidos</h6>
                        <h3 class="mb-0"><?= array_sum(array_column($reportData, 'count')) ?></h3>
                    </div>
                    <div class="bg-success-subtle p-3 rounded-circle">
                        <i class="bi bi-cart-check text-success fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Ticket Médio</h6>
                        <?php
                        $totalSales = array_sum(array_column($reportData, 'total'));
                        $totalOrders = array_sum(array_column($reportData, 'count'));
                        $avgTicket = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
                        ?>
                        <h3 class="mb-0"><?= AdminHelper::formatMoney($avgTicket) ?></h3>
                    </div>
                    <div class="bg-info-subtle p-3 rounded-circle">
                        <i class="bi bi-receipt text-info fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gráfico -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Evolução de Vendas</h5>
    </div>
    <div class="card-body">
        <div class="sales-chart-container" style="min-height: 400px;">
            <canvas id="salesReportChart" height="350"></canvas>
        </div>
    </div>
</div>

<!-- Tabela de Dados -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Detalhamento por <?= $groupByLabel ?></h5>
        <span class="badge bg-primary"><?= count($reportData) ?> registros</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th scope="col" class="ps-3">Data</th>
                        <th scope="col" class="text-center">Qtd. Pedidos</th>
                        <th scope="col" class="text-end pe-3">Total Vendido</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if (empty($reportData)): ?>
                    <tr>
                        <td colspan="3" class="text-center py-3">Nenhum registro encontrado para o período selecionado.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($reportData as $item): ?>
                    <tr>
                        <td class="ps-3"><?= $item['date'] ?></td>
                        <td class="text-center"><?= $item['count'] ?></td>
                        <td class="text-end pe-3 fw-medium"><?= AdminHelper::formatMoney($item['total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="border-top">
                    <tr class="fw-bold">
                        <td class="ps-3">Total</td>
                        <td class="text-center"><?= array_sum(array_column($reportData, 'count')) ?></td>
                        <td class="text-end pe-3"><?= AdminHelper::formatMoney(array_sum(array_column($reportData, 'total'))) ?></td>
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
    // Sales Chart
    const salesChartCtx = document.getElementById('salesReportChart').getContext('2d');
    const salesChartData = <?= json_encode($reportData) ?>;
    
    new Chart(salesChartCtx, {
        type: 'line',
        data: {
            labels: salesChartData.map(item => item.date),
            datasets: [
                {
                    label: 'Vendas (R$)',
                    data: salesChartData.map(item => item.total),
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Pedidos',
                    data: salesChartData.map(item => item.count),
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0)',
                    borderWidth: 2,
                    tension: 0.4,
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
                },
                x: {
                    grid: {
                        display: false
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