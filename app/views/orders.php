<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <div class="row">
        <!-- Menu lateral da conta -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Minha Conta</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>minha-conta" class="list-group-item list-group-item-action">
                        <i class="bi bi-person me-2"></i> Dados Pessoais
                    </a>
                    <a href="<?= BASE_URL ?>minha-conta/endereco" class="list-group-item list-group-item-action">
                        <i class="bi bi-geo-alt me-2"></i> Endereços
                    </a>
                    <a href="<?= BASE_URL ?>minha-conta/pedidos" class="list-group-item list-group-item-action active">
                        <i class="bi bi-box me-2"></i> Meus Pedidos
                    </a>
                    <a href="<?= BASE_URL ?>logout" class="list-group-item list-group-item-action text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> Sair
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Conteúdo principal -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Meus Pedidos</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-box display-1 text-muted mb-3"></i>
                        <h4>Você ainda não fez nenhum pedido</h4>
                        <p class="text-muted mb-4">Encontre produtos incríveis em nossa loja e faça seu primeiro pedido!</p>
                        <a href="<?= BASE_URL ?>produtos" class="btn btn-primary">Ver Produtos</a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Pedido</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?= $order['order_number'] ?></strong></td>
                                    <td><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = '';
                                        $statusText = '';
                                        
                                        switch($order['status']) {
                                            case 'pending':
                                                $statusClass = 'bg-warning';
                                                $statusText = 'Pendente';
                                                break;
                                            case 'processing':
                                                $statusClass = 'bg-info';
                                                $statusText = 'Em Processamento';
                                                break;
                                            case 'shipped':
                                                $statusClass = 'bg-primary';
                                                $statusText = 'Enviado';
                                                break;
                                            case 'delivered':
                                                $statusClass = 'bg-success';
                                                $statusText = 'Entregue';
                                                break;
                                            case 'canceled':
                                                $statusClass = 'bg-danger';
                                                $statusText = 'Cancelado';
                                                break;
                                            default:
                                                $statusClass = 'bg-secondary';
                                                $statusText = ucfirst($order['status']);
                                        }
                                        ?>
                                        <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                    </td>
                                    <td class="text-end">R$ <?= number_format($order['total'], 2, ',', '.') ?></td>
                                    <td class="text-center">
                                        <a href="<?= BASE_URL ?>minha-conta/pedido/<?= $order['order_number'] ?>" class="btn btn-sm btn-outline-primary">
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

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>