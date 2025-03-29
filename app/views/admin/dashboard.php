<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="<?= BASE_URL ?>admin/relatorios" class="btn btn-sm btn-outline-secondary">Relatórios</a>
            <a href="<?= BASE_URL ?>admin/relatorios/vendas" class="btn btn-sm btn-outline-secondary">Vendas</a>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-calendar3"></i> Período
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="?period=today">Hoje</a></li>
            <li><a class="dropdown-item" href="?period=week">Esta Semana</a></li>
            <li><a class="dropdown-item" href="?period=month">Este Mês</a></li>
            <li><a class="dropdown-item" href="?period=year">Este Ano</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="?period=all">Todo o período</a></li>
        </ul>
    </div>
</div>

<!-- Cards de resumo -->
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4 mb-4">
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded">
                        <i class="bi bi-cart-check fs-3 text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-title text-muted mb-0">Total de Pedidos</h6>
                        <h2 class="my-2"><?= $totalOrders ?></h2>
                        <p class="card-text text-muted mb-0">
                            <span class="text-success">
                                <i class="bi bi-clock"></i> <?= $pendingOrders ?> pendentes
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="<?= BASE_URL ?>admin/pedidos" class="btn btn-sm btn-light w-100">Ver todos</a>
            </div>
        </div>
    </div>
    
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-success bg-opacity-10 p-3 rounded">
                        <i class="bi bi-currency-dollar fs-3 text-success"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-title text-muted mb-0">Faturamento Total</h6>
                        <h2 class="my-2">R$ <?= number_format($totalRevenue, 2, ',', '.') ?></h2>
                        <p class="card-text text-muted mb-0">
                            <span class="text-success">
                                <i class="bi bi-graph-up"></i> Todos os pedidos não cancelados
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="<?= BASE_URL ?>admin/relatorios/vendas" class="btn btn-sm btn-light w-100">Ver relatório</a>
            </div>
        </div>
    </div>
    
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-info bg-opacity-10 p-3 rounded">
                        <i class="bi bi-box-seam fs-3 text-info"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-title text-muted mb-0">Total de Produtos</h6>
                        <h2 class="my-2"><?= $totalProducts ?></h2>
                        <p class="card-text text-muted mb-0">
                            <span class="text-info">
                                <i class="bi bi-tags"></i> Em <?= count($categories ?? []) ?> categorias
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="<?= BASE_URL ?>admin/produtos" class="btn btn-sm btn-light w-100">Gerenciar</a>
            </div>
        </div>
    </div>
    
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-warning bg-opacity-10 p-3 rounded">
                        <i class="bi bi-people fs-3 text-warning"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-title text-muted mb-0">Clientes Cadastrados</h6>
                        <h2 class="my-2"><?= $totalUsers ?></h2>
                        <p class="card-text text-muted mb-0">
                            <span class="text-warning">
                                <i class="bi bi-person-check"></i> Cadastros ativos
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0">
                <a href="<?= BASE_URL ?>admin/usuarios" class="btn btn-sm btn-light w-100">Ver detalhes</a>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Pedidos Recentes</h5>
                    <a href="<?= BASE_URL ?>admin/pedidos" class="btn btn-sm btn-outline-primary">Ver todos</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Nº</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Data</th>
                                <th scope="col">Status</th>
                                <th scope="col">Total</th>
                                <th scope="col">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentOrders)): ?>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><?= $order['order_number'] ?></td>
                                    <td>
                                        <?php 
                                        $userModel = new UserModel();
                                        $user = $userModel->find($order['user_id']);
                                        echo $user ? $user['name'] : 'Cliente #' . $order['user_id'];
                                        ?>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <?php
                                        $statusClasses = [
                                            'pending' => 'bg-warning',
                                            'processing' => 'bg-info',
                                            'shipped' => 'bg-primary',
                                            'delivered' => 'bg-success',
                                            'canceled' => 'bg-danger'
                                        ];
                                        $statusLabels = [
                                            'pending' => 'Pendente',
                                            'processing' => 'Em Processamento',
                                            'shipped' => 'Enviado',
                                            'delivered' => 'Entregue',
                                            'canceled' => 'Cancelado'
                                        ];
                                        $statusClass = $statusClasses[$order['status']] ?? 'bg-secondary';
                                        $statusLabel = $statusLabels[$order['status']] ?? $order['status'];
                                        ?>
                                        <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td>R$ <?= number_format($order['total'], 2, ',', '.') ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>admin/pedidos/view/<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-3">Nenhum pedido encontrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">Status dos Pedidos</h5>
            </div>
            <div class="card-body">
                <canvas id="orderStatusChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuração do gráfico de status de pedidos
    var orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
    
    // Obter dados para o gráfico
    var statusLabels = ['Pendente', 'Em Processamento', 'Enviado', 'Entregue', 'Cancelado'];
    var statusData = [
        <?= $pendingOrders ?>,
        <?= $processingOrders ?? 0 ?>,
        <?= $shippedOrders ?? 0 ?>,
        <?= $deliveredOrders ?? 0 ?>,
        <?= $canceledOrders ?? 0 ?>
    ];
    
    var backgroundColors = [
        'rgba(255, 193, 7, 0.8)',
        'rgba(13, 202, 240, 0.8)',
        'rgba(13, 110, 253, 0.8)',
        'rgba(25, 135, 84, 0.8)',
        'rgba(220, 53, 69, 0.8)'
    ];
    
    var orderStatusChart = new Chart(orderStatusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusData,
                backgroundColor: backgroundColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>
