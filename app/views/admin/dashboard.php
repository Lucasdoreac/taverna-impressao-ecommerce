<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<!-- Page Title -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Dashboard</h1>
    <div>
        <span class="text-muted">Bem-vindo(a), <?= $_SESSION['user']['name'] ?></span>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <!-- Pedidos -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted">Pedidos</h6>
                        <h2 class="mb-0"><?= number_format($stats['orders']['total_orders']) ?></h2>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-2 rounded">
                        <i class="bi bi-cart-check text-primary fs-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between small">
                        <span>Pendentes</span>
                        <span class="fw-bold"><?= number_format($stats['orders']['pending_orders']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between small mt-1">
                        <span>Processando</span>
                        <span class="fw-bold"><?= number_format($stats['orders']['processing_orders']) ?></span>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-0">
                <a href="<?= BASE_URL ?>admin/pedidos" class="btn btn-sm btn-outline-primary w-100">Ver Todos</a>
            </div>
        </div>
    </div>
    
    <!-- Receita -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted">Receita Total</h6>
                        <h2 class="mb-0"><?= AdminHelper::formatMoney($stats['orders']['total_revenue'] ?? 0) ?></h2>
                    </div>
                    <div class="bg-success bg-opacity-10 p-2 rounded">
                        <i class="bi bi-currency-dollar text-success fs-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between small">
                        <span>Média por Pedido</span>
                        <span class="fw-bold"><?= AdminHelper::formatMoney($stats['orders']['average_order'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-0">
                <a href="<?= BASE_URL ?>admin/relatorios/vendas" class="btn btn-sm btn-outline-success w-100">Ver Relatório</a>
            </div>
        </div>
    </div>
    
    <!-- Produtos -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted">Produtos</h6>
                        <h2 class="mb-0"><?= number_format($stats['products']['total_products']) ?></h2>
                    </div>
                    <div class="bg-info bg-opacity-10 p-2 rounded">
                        <i class="bi bi-box-seam text-info fs-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between small">
                        <span>Ativos</span>
                        <span class="fw-bold"><?= number_format($stats['products']['active_products']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between small mt-1">
                        <span>Personalizáveis</span>
                        <span class="fw-bold"><?= number_format($stats['products']['customizable_products']) ?></span>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-0">
                <a href="<?= BASE_URL ?>admin/produtos" class="btn btn-sm btn-outline-info w-100">Gerenciar</a>
            </div>
        </div>
    </div>
    
    <!-- Usuários -->
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted">Usuários</h6>
                        <h2 class="mb-0"><?= number_format($stats['users']['total_users']) ?></h2>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-2 rounded">
                        <i class="bi bi-people text-warning fs-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="d-flex justify-content-between small">
                        <span>Clientes</span>
                        <span class="fw-bold"><?= number_format($stats['users']['customer_users']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between small mt-1">
                        <span>Administradores</span>
                        <span class="fw-bold"><?= number_format($stats['users']['admin_users']) ?></span>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white border-0">
                <a href="<?= BASE_URL ?>admin/usuarios" class="btn btn-sm btn-outline-warning w-100">Gerenciar</a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders and Actions -->
<div class="row g-4">
    <!-- Pedidos Recentes -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Pedidos Recentes</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Data</th>
                                <th scope="col">Valor</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-3">Nenhum pedido encontrado.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><?= $order['order_number'] ?></td>
                                <td><?= $order['user_name'] ?: 'Cliente não registrado' ?></td>
                                <td><?= AdminHelper::formatDate($order['created_at']) ?></td>
                                <td><?= AdminHelper::formatMoney($order['total']) ?></td>
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
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>admin/pedidos/view/<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="<?= BASE_URL ?>admin/pedidos" class="text-decoration-none">Ver todos os pedidos</a>
            </div>
        </div>
    </div>
    
    <!-- Ações Rápidas -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Ações Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-3">
                    <a href="<?= BASE_URL ?>admin/produtos/create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i> Adicionar Produto
                    </a>
                    <a href="<?= BASE_URL ?>admin/categorias/create" class="btn btn-outline-primary">
                        <i class="bi bi-folder-plus me-2"></i> Adicionar Categoria
                    </a>
                    <a href="<?= BASE_URL ?>admin/pedidos?status=pending" class="btn btn-outline-warning">
                        <i class="bi bi-clock-history me-2"></i> Ver Pedidos Pendentes
                    </a>
                    <a href="<?= BASE_URL ?>admin/relatorios" class="btn btn-outline-info">
                        <i class="bi bi-bar-chart me-2"></i> Gerar Relatórios
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Links Úteis -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Links Úteis</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="https://hostinger.com.br" target="_blank" class="text-decoration-none">Painel Hostinger</a>
                        <i class="bi bi-box-arrow-up-right"></i>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="https://github.com/Lucasdoreac/taverna-impressao-ecommerce" target="_blank" class="text-decoration-none">Repositório GitHub</a>
                        <i class="bi bi-box-arrow-up-right"></i>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="https://correios.com.br/rastreamento" target="_blank" class="text-decoration-none">Rastreamento Correios</a>
                        <i class="bi bi-box-arrow-up-right"></i>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="<?= BASE_URL ?>docs" target="_blank" class="text-decoration-none">Documentação</a>
                        <i class="bi bi-box-arrow-up-right"></i>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
