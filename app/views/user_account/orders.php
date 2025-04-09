<div class="container my-5">
    <div class="row">
        <!-- Menu lateral -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Minha Conta</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo BASE_URL; ?>minha-conta" class="list-group-item list-group-item-action">
                        <i class="fas fa-home me-2"></i> Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>minha-conta/perfil" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Meu Perfil
                    </a>
                    <a href="<?php echo BASE_URL; ?>minha-conta/pedidos" class="list-group-item list-group-item-action active">
                        <i class="fas fa-shopping-bag me-2"></i> Meus Pedidos
                    </a>
                    <a href="<?php echo BASE_URL; ?>minha-conta/enderecos" class="list-group-item list-group-item-action">
                        <i class="fas fa-map-marker-alt me-2"></i> Meus Endereços
                    </a>
                    <a href="<?php echo BASE_URL; ?>logout" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Sair
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Conteúdo principal -->
        <div class="col-md-9">
            <?php if (isset($success) && $success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo h($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error) && $error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo h($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Meus Pedidos</h4>
                    <a href="<?php echo BASE_URL; ?>minha-conta" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Voltar
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Você ainda não realizou nenhum pedido.
                        </div>
                        <div class="text-center mt-4">
                            <a href="<?php echo BASE_URL; ?>produtos" class="btn btn-primary">
                                <i class="fas fa-shopping-cart me-1"></i> Começar a Comprar
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pedido</th>
                                        <th>Data</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo h($order['id']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime(h($order['created_at']))); ?></td>
                                            <td>R$ <?php echo number_format(h($order['total']), 2, ',', '.'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getOrderStatusBadge(h($order['status'])); ?>">
                                                    <?php echo getOrderStatusText(h($order['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>minha-conta/pedido/<?php echo h($order['id']); ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Detalhes
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($pagination['lastPage'] > 1): ?>
                            <nav aria-label="Navegação de páginas" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($pagination['currentPage'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo BASE_URL; ?>minha-conta/pedidos?page=1" aria-label="Primeira">
                                                <span aria-hidden="true">&laquo;&laquo;</span>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo BASE_URL; ?>minha-conta/pedidos?page=<?php echo $pagination['currentPage'] - 1; ?>" aria-label="Anterior">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start = max(1, $pagination['currentPage'] - 2);
                                    $end = min($pagination['lastPage'], $pagination['currentPage'] + 2);
                                    
                                    if ($start > 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    
                                    for ($i = $start; $i <= $end; $i++): 
                                    ?>
                                        <li class="page-item <?php echo $i == $pagination['currentPage'] ? 'active' : ''; ?>">
                                            <a class="page-link" href="<?php echo BASE_URL; ?>minha-conta/pedidos?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; 
                                    
                                    if ($end < $pagination['lastPage']) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    ?>
                                    
                                    <?php if ($pagination['currentPage'] < $pagination['lastPage']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo BASE_URL; ?>minha-conta/pedidos?page=<?php echo $pagination['currentPage'] + 1; ?>" aria-label="Próxima">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo BASE_URL; ?>minha-conta/pedidos?page=<?php echo $pagination['lastPage']; ?>" aria-label="Última">
                                                <span aria-hidden="true">&raquo;&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Retorna a classe do badge de acordo com o status do pedido
 */
function getOrderStatusBadge($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'processing':
            return 'info';
        case 'in_production':
            return 'primary';
        case 'completed':
            return 'success';
        case 'delivered':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

/**
 * Retorna o texto do status do pedido
 */
function getOrderStatusText($status) {
    switch ($status) {
        case 'pending':
            return 'Pendente';
        case 'processing':
            return 'Processando';
        case 'in_production':
            return 'Em Produção';
        case 'completed':
            return 'Concluído';
        case 'delivered':
            return 'Entregue';
        case 'cancelled':
            return 'Cancelado';
        default:
            return 'Desconhecido';
    }
}
?>