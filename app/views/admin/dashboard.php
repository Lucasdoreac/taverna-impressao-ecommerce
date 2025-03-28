<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<!-- Page Title -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Dashboard</h1>
    <div class="btn-group">
        <a href="<?= BASE_URL ?>admin?period=week" class="btn btn-outline-secondary <?= $period == 'week' ? 'active' : '' ?>">7 dias</a>
        <a href="<?= BASE_URL ?>admin?period=month" class="btn btn-outline-secondary <?= $period == 'month' ? 'active' : '' ?>">30 dias</a>
        <a href="<?= BASE_URL ?>admin?period=year" class="btn btn-outline-secondary <?= $period == 'year' ? 'active' : '' ?>">12 meses</a>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <!-- Sales Stats -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h6 class="card-title text-muted mb-1">Vendas Totais</h6>
                        <h2 class="mb-0 fw-bold"><?= AdminHelper::formatMoney($stats['sales']['total']) ?></h2>
                    </div>
                    <div class="stats-icon bg-primary-subtle">
                        <i class="bi bi-cash-stack text-primary fs-4"></i>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        <span class="d-block text-success small">
                            <i class="bi bi-calendar-day me-1"></i> Hoje:
                        </span>
                        <span class="fw-medium"><?= AdminHelper::formatMoney($stats['sales']['today']) ?></span>
                    </div>
                    <div>
                        <span class="d-block text-info small">
                            <i class="bi bi-calendar-week me-1"></i> 7 dias:
                        </span>
                        <span class="fw-medium"><?= AdminHelper::formatMoney($stats['sales']['week']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Orders Stats -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h6 class="card-title text-muted mb-1">Pedidos</h6>
                        <h2 class="mb-0 fw-bold"><?= $stats['orders']['total'] ?></h2>
                    </div>
                    <div class="stats-icon bg-success-subtle">
                        <i class="bi bi-cart-check text-success fs-4"></i>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-warning"><?= $stats['orders']['pending'] ?> Pendentes</span>
                    <span class="badge bg-info"><?= $stats['orders']['processing'] ?> Processando</span>
                    <span class="badge bg-primary"><?= $stats['orders']['shipped'] ?> Enviados</span>
                    <span class="badge bg-success"><?= $stats['orders']['delivered'] ?> Entregues</span>
                    <span class="badge bg-danger"><?= $stats['orders']['canceled'] ?> Cancelados</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Products Stats -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h6 class="card-title text-muted mb-1">Produtos</h6>
                        <h2 class="mb-0 fw-bold"><?= $stats['products']['total'] ?></h2>
                    </div>
                    <div class="stats-icon bg-warning-subtle">
                        <i class="bi bi-box-seam text-warning fs-4"></i>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        <span class="badge <?= $stats['products']['out_of_stock'] > 0 ? 'bg-danger' : 'bg-success' ?>">
                            <?= $stats['products']['out_of_stock'] ?> sem estoque
                        </span>
                    </div>
                    <div>
                        <span class="badge <?= $stats['products']['low_stock'] > 0 ? 'bg-warning' : 'bg-success' ?>">
                            <?= $stats['products']['low_stock'] ?> baixo estoque
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Users Stats -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h6 class="card-title text-muted mb-1">Usuários</h6>
                        <h2 class="mb-0 fw-bold"><?= $stats['users']['total'] ?></h2>
                    </div>
                    <div class="stats-icon bg-info-subtle">
                        <i class="bi bi-people text-info fs-4"></i>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        <span class="d-block text-primary small">
                            <i class="bi bi-person-badge me-1"></i> Admins:
                        </span>
                        <span class="fw-medium"><?= $stats['users']['admin'] ?></span>
                    </div>
                    <div>
                        <span class="d-block text-success small">
                            <i class="bi bi-person me-1"></i> Clientes:
                        </span>
                        <span class="fw-medium"><?= $stats['users']['customer'] ?></span>
                    </div>
                    <div>
                        <span class="d-block text-info small">
                            <i class="bi bi-person-plus me-1"></i> Hoje:
                        </span>
                        <span class="fw-medium"><?= $stats['users']['new_today'] ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Sales Chart -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Vendas por Período</h5>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-download me-1"></i> Exportar
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>admin/relatorios/vendas?<?= http_build_query(['start_date' => $period == 'year' ? date('Y-m-01', strtotime('-11 months')) : date('Y-m-d', strtotime('-29 days')), 'end_date' => date('Y-m-d'), 'group_by' => $period == 'year' ? 'monthly' : 'daily']) ?>">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="sales-chart-container">
                    <canvas id="salesChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Categories Chart -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Vendas por Categoria</h5>
            </div>
            <div class="card-body">
                <div class="categories-chart-container">
                    <canvas id="categoriesChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Row -->
<div class="row g-4">
    <!-- Recent Orders -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Pedidos Recentes</h5>
                <a href="<?= BASE_URL ?>admin/pedidos" class="btn btn-sm btn-outline-primary">
                    Ver Todos
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th scope="col" class="ps-3">Pedido</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end pe-3">Total</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php if (empty($recentOrders)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-3">Nenhum pedido recente.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td class="ps-3">
                                    <a href="<?= BASE_URL ?>admin/pedidos/view/<?= $order['id'] ?>" class="text-decoration-none fw-medium">
                                        #<?= $order['order_number'] ?>
                                    </a>
                                    <div class="small text-muted"><?= AdminHelper::formatDate($order['created_at']) ?></div>
                                </td>
                                <td>
                                    <?php if (isset($order['customer_name'])): ?>
                                    <?= htmlspecialchars($order['customer_name']) ?>
                                    <?php else: ?>
                                    <span class="text-muted">Cliente não registrado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php switch ($order['status']):
                                        case 'pending': ?>
                                            <span class="badge bg-warning">Pendente</span>
                                            <?php break; ?>
                                        <?php case 'processing': ?>
                                            <span class="badge bg-info">Processando</span>
                                            <?php break; ?>
                                        <?php case 'shipped': ?>
                                            <span class="badge bg-primary">Enviado</span>
                                            <?php break; ?>
                                        <?php case 'delivered': ?>
                                            <span class="badge bg-success">Entregue</span>
                                            <?php break; ?>
                                        <?php case 'canceled': ?>
                                            <span class="badge bg-danger">Cancelado</span>
                                            <?php break; ?>
                                        <?php default: ?>
                                            <span class="badge bg-secondary">Desconhecido</span>
                                    <?php endswitch; ?>
                                </td>
                                <td class="text-end pe-3 fw-medium"><?= AdminHelper::formatMoney($order['total']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Products -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Produtos Mais Vendidos</h5>
                <a href="<?= BASE_URL ?>admin/relatorios/produtos?<?= http_build_query(['start_date' => date('Y-m-01'), 'end_date' => date('Y-m-d')]) ?>" class="btn btn-sm btn-outline-primary">
                    Gerar Relatório
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th scope="col" class="ps-3">Produto</th>
                                <th scope="col" class="text-center">Categoria</th>
                                <th scope="col" class="text-center">Qtd. Vendida</th>
                                <th scope="col" class="text-end pe-3">Total</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php if (empty($topProducts)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-3">Nenhum produto vendido ainda.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($topProducts as $product): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center">
                                        <div class="product-img me-2">
                                            <?php if (isset($product['image'])): ?>
                                            <img src="<?= BASE_URL ?>uploads/products/<?= $product['image'] ?>" alt="<?= $product['name'] ?>" class="rounded" width="40" height="40">
                                            <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-box text-muted"></i>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <a href="<?= BASE_URL ?>admin/produtos/view/<?= $product['id'] ?>" class="text-decoration-none fw-medium">
                                                <?= htmlspecialchars($product['name']) ?>
                                            </a>
                                            <div class="small text-muted">
                                                <?= $product['stock'] > 0 ? $product['stock'] . ' em estoque' : 'Sem estoque' ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center"><?= htmlspecialchars($product['category_name']) ?></td>
                                <td class="text-center fw-medium"><?= $product['total_quantity'] ?></td>
                                <td class="text-end pe-3 fw-medium"><?= AdminHelper::formatMoney($product['total_sales']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sales Chart
    const salesChartCtx = document.getElementById('salesChart').getContext('2d');
    const salesChartData = <?= json_encode($salesChart) ?>;
    
    new Chart(salesChartCtx, {
        type: 'line',
        data: {
            labels: salesChartData.map(item => item.label),
            datasets: [
                {
                    label: 'Vendas (R$)',
                    data: salesChartData.map(item => item.value),
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
    
    // Categories Chart
    const categoriesChartCtx = document.getElementById('categoriesChart').getContext('2d');
    const categoriesChartData = <?= json_encode($categoriesChart) ?>;
    
    new Chart(categoriesChartCtx, {
        type: 'doughnut',
        data: {
            labels: categoriesChartData.map(item => item.category),
            datasets: [{
                data: categoriesChartData.map(item => item.total),
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(199, 199, 199, 0.7)',
                    'rgba(83, 102, 255, 0.7)',
                    'rgba(40, 159, 64, 0.7)',
                    'rgba(210, 30, 30, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += 'R$ ' + context.raw.toLocaleString('pt-BR');
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>

<!-- Custom Styles -->
<style>
.stats-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sales-chart-container,
.categories-chart-container {
    width: 100%;
    position: relative;
}

.product-img img {
    object-fit: cover;
}
</style>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>