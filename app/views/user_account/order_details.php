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
                    <h4 class="mb-0">Pedido #<?php echo h($order['id']); ?></h4>
                    <a href="<?php echo BASE_URL; ?>minha-conta/pedidos" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Voltar para Pedidos
                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="card-title">Informações do Pedido</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th style="width: 35%">Número do Pedido:</th>
                                    <td>#<?php echo h($order['id']); ?></td>
                                </tr>
                                <tr>
                                    <th>Data:</th>
                                    <td><?php echo date('d/m/Y H:i', strtotime(h($order['created_at']))); ?></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge bg-<?php echo getOrderStatusBadge(h($order['status'])); ?>">
                                            <?php echo getOrderStatusText(h($order['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Método de Pagamento:</th>
                                    <td><?php echo h($order['payment_method'] ?? 'Não informado'); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5 class="card-title">Informações de Entrega</h5>
                            <address>
                                <strong><?php echo h($order['shipping_name']); ?></strong><br>
                                <?php echo h($order['shipping_address']); ?>, <?php echo h($order['shipping_number']); ?><br>
                                <?php if (!empty($order['shipping_complement'])): ?>
                                    <?php echo h($order['shipping_complement']); ?><br>
                                <?php endif; ?>
                                <?php echo h($order['shipping_neighborhood']); ?><br>
                                <?php echo h($order['shipping_city']); ?> - <?php echo h($order['shipping_state']); ?><br>
                                CEP: <?php echo h($order['shipping_postal_code']); ?><br>
                                Telefone: <?php echo h($order['shipping_phone'] ?? 'Não informado'); ?>
                            </address>
                        </div>
                    </div>
                    
                    <h5 class="card-title">Itens do Pedido</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 70px">Qtd</th>
                                    <th>Produto</th>
                                    <th>Opções</th>
                                    <th class="text-end">Preço Unit.</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $itemsTotal = 0;
                                foreach ($items as $item): 
                                    $subtotal = $item['price'] * $item['quantity'];
                                    $itemsTotal += $subtotal;
                                ?>
                                    <tr>
                                        <td class="text-center"><?php echo h($item['quantity']); ?></td>
                                        <td>
                                            <?php if (!empty($item['product_id'])): ?>
                                                <a href="<?php echo BASE_URL; ?>produto/<?php echo h($item['product_slug']); ?>" class="text-decoration-none">
                                                    <?php echo h($item['product_name']); ?>
                                                </a>
                                            <?php else: ?>
                                                <?php echo h($item['product_name']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($item['options'])): ?>
                                                <small class="text-muted">
                                                    <?php echo h($item['options']); ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">R$ <?php echo number_format(h($item['price']), 2, ',', '.'); ?></td>
                                        <td class="text-end">R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Subtotal:</td>
                                    <td class="text-end">R$ <?php echo number_format($itemsTotal, 2, ',', '.'); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Frete:</td>
                                    <td class="text-end">R$ <?php echo number_format(h($order['shipping_cost']), 2, ',', '.'); ?></td>
                                </tr>
                                <?php if (!empty($order['discount']) && $order['discount'] > 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">Desconto:</td>
                                        <td class="text-end">- R$ <?php echo number_format(h($order['discount']), 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total:</td>
                                    <td class="text-end fw-bold fs-5">R$ <?php echo number_format(h($order['total']), 2, ',', '.'); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <?php if (!empty($order['notes'])): ?>
                        <div class="mt-4">
                            <h5 class="card-title">Observações</h5>
                            <div class="alert alert-light">
                                <?php echo nl2br(h($order['notes'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($order['status'] == 'in_production' || $order['status'] == 'processing'): ?>
                        <div class="mt-4">
                            <h5 class="card-title">Status de Produção</h5>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> 
                                <?php if ($order['status'] == 'in_production'): ?>
                                    Seu pedido está atualmente em produção. Você pode acompanhar o status da impressão 3D na área <a href="<?php echo BASE_URL; ?>print-monitor/order/<?php echo h($order['id']); ?>" class="alert-link">Monitor de Impressão</a>.
                                <?php else: ?>
                                    Seu pedido está sendo processado e em breve entrará em produção.
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?php echo BASE_URL; ?>minha-conta/pedidos" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-1"></i> Listar Pedidos
                        </a>
                        
                        <?php if ($order['status'] == 'completed' || $order['status'] == 'delivered'): ?>
                            <a href="#" class="btn btn-success" onclick="window.print(); return false;">
                                <i class="fas fa-print me-1"></i> Imprimir Pedido
                            </a>
                        <?php endif; ?>
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