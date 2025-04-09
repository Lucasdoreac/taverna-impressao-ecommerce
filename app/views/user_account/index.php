<div class="container my-5">
    <div class="row">
        <!-- Menu lateral -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Minha Conta</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo BASE_URL; ?>minha-conta" class="list-group-item list-group-item-action active">
                        <i class="fas fa-home me-2"></i> Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>minha-conta/perfil" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Meu Perfil
                    </a>
                    <a href="<?php echo BASE_URL; ?>minha-conta/pedidos" class="list-group-item list-group-item-action">
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
                <div class="card-header">
                    <h4 class="mb-0">Bem-vindo, <?php echo h($user['name']); ?>!</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-user text-primary me-2"></i> Informações da Conta
                                    </h5>
                                    <p class="card-text">
                                        <strong>Nome:</strong> <?php echo h($user['name']); ?><br>
                                        <strong>E-mail:</strong> <?php echo h($user['email']); ?><br>
                                        <strong>Telefone:</strong> <?php echo h($user['phone'] ?? 'Não informado'); ?><br>
                                        <strong>Cliente desde:</strong> <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                    </p>
                                    <a href="<?php echo BASE_URL; ?>minha-conta/perfil" class="btn btn-outline-primary">
                                        <i class="fas fa-edit me-1"></i> Editar Perfil
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-shopping-bag text-primary me-2"></i> Seus Pedidos
                                    </h5>
                                    <?php
                                    $orderModel = new OrderModel();
                                    $recentOrders = $orderModel->getUserRecentOrders($user['id'], 3);
                                    
                                    if ($recentOrders && count($recentOrders) > 0):
                                    ?>
                                        <p class="card-text">Seus pedidos mais recentes:</p>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($recentOrders as $order): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <span class="badge bg-primary rounded-pill me-2">#<?php echo $order['id']; ?></span>
                                                        <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                                                    </div>
                                                    <span class="badge bg-<?php echo getOrderStatusBadge($order['status']); ?>">
                                                        <?php echo getOrderStatusText($order['status']); ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="card-text">Você ainda não possui pedidos.</p>
                                    <?php endif; ?>
                                    <a href="<?php echo BASE_URL; ?>minha-conta/pedidos" class="btn btn-outline-primary mt-2">
                                        <i class="fas fa-list me-1"></i> Ver Todos os Pedidos
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-map-marker-alt text-primary me-2"></i> Endereços
                                    </h5>
                                    <?php
                                    $addressModel = new AddressModel();
                                    $addresses = $addressModel->getUserAddresses($user['id']);
                                    
                                    if ($addresses && count($addresses) > 0):
                                        $defaultAddress = array_filter($addresses, function($addr) {
                                            return $addr['is_default'] == 1;
                                        });
                                        
                                        if (count($defaultAddress) > 0):
                                            $defaultAddress = current($defaultAddress);
                                    ?>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <p><strong>Endereço principal:</strong></p>
                                                <address>
                                                    <?php echo h($defaultAddress['street']); ?>, <?php echo h($defaultAddress['number']); ?><br>
                                                    <?php if (!empty($defaultAddress['complement'])): ?>
                                                        <?php echo h($defaultAddress['complement']); ?><br>
                                                    <?php endif; ?>
                                                    <?php echo h($defaultAddress['neighborhood']); ?><br>
                                                    <?php echo h($defaultAddress['city']); ?> - <?php echo h($defaultAddress['state']); ?><br>
                                                    CEP: <?php echo h($defaultAddress['postal_code']); ?>
                                                </address>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <a href="<?php echo BASE_URL; ?>minha-conta/enderecos" class="btn btn-outline-primary">
                                        <i class="fas fa-edit me-1"></i> Gerenciar Endereços
                                    </a>
                                    <?php else: ?>
                                        <p class="card-text">Você ainda não possui endereços cadastrados.</p>
                                        <a href="<?php echo BASE_URL; ?>minha-conta/enderecos/adicionar" class="btn btn-outline-primary">
                                            <i class="fas fa-plus me-1"></i> Adicionar Endereço
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
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