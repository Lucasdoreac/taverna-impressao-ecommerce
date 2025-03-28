<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<!-- Page Title and Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Detalhes do Pedido</h1>
    <div>
        <a href="<?= BASE_URL ?>admin/pedidos" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
        <a href="#" class="btn btn-primary" onclick="window.print();">
            <i class="bi bi-printer me-1"></i> Imprimir
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Main Order Info Column -->
    <div class="col-lg-8">
        <!-- Order Header -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Pedido #<?= $order['order_number'] ?></h5>
                <div>
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
                    
                    <?php switch ($order['payment_status']):
                        case 'pending': ?>
                            <span class="badge bg-warning">Pagamento Pendente</span>
                            <?php break; ?>
                        <?php case 'paid': ?>
                            <span class="badge bg-success">Pagamento Realizado</span>
                            <?php break; ?>
                        <?php case 'refunded': ?>
                            <span class="badge bg-info">Pagamento Estornado</span>
                            <?php break; ?>
                        <?php case 'canceled': ?>
                            <span class="badge bg-danger">Pagamento Cancelado</span>
                            <?php break; ?>
                        <?php default: ?>
                            <span class="badge bg-secondary">Status de Pagamento Desconhecido</span>
                    <?php endswitch; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th class="ps-0">Data do Pedido:</th>
                                <td><?= AdminHelper::formatDateTime($order['created_at']) ?></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Última Atualização:</th>
                                <td><?= AdminHelper::formatDateTime($order['updated_at']) ?></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Método de Pagamento:</th>
                                <td>
                                    <?php switch ($order['payment_method']):
                                        case 'credit_card': ?>
                                            <i class="bi bi-credit-card me-1"></i> Cartão de Crédito
                                            <?php break; ?>
                                        <?php case 'boleto': ?>
                                            <i class="bi bi-upc me-1"></i> Boleto
                                            <?php break; ?>
                                        <?php case 'pix': ?>
                                            <i class="bi bi-qr-code me-1"></i> PIX
                                            <?php break; ?>
                                        <?php default: ?>
                                            <?= $order['payment_method'] ?>
                                    <?php endswitch; ?>
                                </td>
                            </tr>
                            <?php if (!empty($order['tracking_code'])): ?>
                            <tr>
                                <th class="ps-0">Código de Rastreio:</th>
                                <td>
                                    <?= $order['tracking_code'] ?>
                                    <a href="https://correios.com.br/rastreamento" target="_blank" class="ms-2 text-decoration-none">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th class="ps-0">Subtotal:</th>
                                <td class="text-end"><?= AdminHelper::formatMoney($order['subtotal']) ?></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Frete:</th>
                                <td class="text-end"><?= AdminHelper::formatMoney($order['shipping_cost']) ?></td>
                            </tr>
                            <?php if ($order['discount'] > 0): ?>
                            <tr>
                                <th class="ps-0">Desconto:</th>
                                <td class="text-end text-danger">- <?= AdminHelper::formatMoney($order['discount']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th class="ps-0 fw-bold">Total:</th>
                                <td class="text-end fw-bold fs-5"><?= AdminHelper::formatMoney($order['total']) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Itens do Pedido</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Produto</th>
                                <th scope="col" class="text-center">Preço</th>
                                <th scope="col" class="text-center">Quantidade</th>
                                <th scope="col" class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="fw-medium"><?= $item['product_name'] ?></div>
                                            <?php if (!empty($item['customization_data'])): ?>
                                            <button class="btn btn-sm btn-link p-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#customization-<?= $item['id'] ?>">
                                                <i class="bi bi-magic me-1"></i> Ver personalização
                                            </button>
                                            <div class="collapse mt-2" id="customization-<?= $item['id'] ?>">
                                                <div class="card card-body bg-light">
                                                    <pre class="mb-0"><?= $item['customization_data'] ?></pre>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center"><?= AdminHelper::formatMoney($item['price']) ?></td>
                                <td class="text-center"><?= $item['quantity'] ?></td>
                                <td class="text-end"><?= AdminHelper::formatMoney($item['price'] * $item['quantity']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">Subtotal:</th>
                                <th class="text-end"><?= AdminHelper::formatMoney($order['subtotal']) ?></th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end">Frete (<?= $order['shipping_method'] ?>):</th>
                                <th class="text-end"><?= AdminHelper::formatMoney($order['shipping_cost']) ?></th>
                            </tr>
                            <?php if ($order['discount'] > 0): ?>
                            <tr>
                                <th colspan="3" class="text-end">Desconto:</th>
                                <th class="text-end text-danger">- <?= AdminHelper::formatMoney($order['discount']) ?></th>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th colspan="3" class="text-end">Total:</th>
                                <th class="text-end fs-5"><?= AdminHelper::formatMoney($order['total']) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Customer Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Informações do Cliente</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-3">Dados do Cliente</h6>
                        <?php if ($customer): ?>
                        <table class="table table-borderless">
                            <tr>
                                <th class="ps-0">Nome:</th>
                                <td><?= $customer['name'] ?></td>
                            </tr>
                            <tr>
                                <th class="ps-0">E-mail:</th>
                                <td><?= $customer['email'] ?></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Telefone:</th>
                                <td><?= $customer['phone'] ?: 'Não informado' ?></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Cliente desde:</th>
                                <td><?= AdminHelper::formatDate($customer['created_at']) ?></td>
                            </tr>
                        </table>
                        <a href="<?= BASE_URL ?>admin/usuarios/view/<?= $customer['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-person me-1"></i> Ver Perfil Completo
                        </a>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i> Este pedido foi feito sem cadastro.
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-3">Endereço de Entrega</h6>
                        <?php if ($shippingAddress): ?>
                        <address>
                            <?= $shippingAddress['address'] ?>, <?= $shippingAddress['number'] ?><br>
                            <?= $shippingAddress['complement'] ? $shippingAddress['complement'] . '<br>' : '' ?>
                            <?= $shippingAddress['neighborhood'] ?><br>
                            <?= $shippingAddress['city'] ?> - <?= $shippingAddress['state'] ?><br>
                            CEP: <?= $shippingAddress['zipcode'] ?>
                        </address>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i> Endereço de entrega não disponível.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Order Notes -->
        <?php
        $notes = $orderModel->getNotes($order['id']);
        ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Histórico e Observações</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                    <i class="bi bi-plus-circle me-1"></i> Adicionar Nota
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($notes) && empty($order['notes'])): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i> Este pedido não possui notas.
                </div>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($notes as $note): ?>
                    <div class="timeline-item">
                        <div class="timeline-date">
                            <?= !empty($note['timestamp']) ? AdminHelper::formatDateTime($note['timestamp']) : '' ?>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-body">
                                <?= nl2br($note['content']) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar Column -->
    <div class="col-lg-4">
        <!-- Order Actions -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Ações</h5>
            </div>
            <div class="card-body">
                <?php if ($order['status'] != 'canceled'): ?>
                <div class="d-grid gap-2">
                    <?php if ($order['status'] == 'pending'): ?>
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-status="processing">
                        <i class="bi bi-arrow-right-circle me-1"></i> Marcar como Processando
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($order['status'] == 'processing'): ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTrackingModal">
                        <i class="bi bi-truck me-1"></i> Adicionar Rastreio e Enviar
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($order['status'] == 'shipped'): ?>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-status="delivered">
                        <i class="bi bi-check-circle me-1"></i> Marcar como Entregue
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($order['payment_status'] == 'pending'): ?>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#updatePaymentModal" data-status="paid">
                        <i class="bi bi-credit-card me-1"></i> Marcar como Pago
                    </button>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                        <i class="bi bi-x-circle me-1"></i> Cancelar Pedido
                    </button>
                </div>
                <?php else: ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Este pedido foi cancelado.
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Order Timeline -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Status do Pedido</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php 
                    $statusOrder = ['pending', 'processing', 'shipped', 'delivered'];
                    $currentStatusIndex = array_search($order['status'], $statusOrder);
                    $currentStatusIndex = $currentStatusIndex !== false ? $currentStatusIndex : -1;
                    
                    if ($order['status'] === 'canceled') {
                        $currentStatusIndex = -1;
                    }
                    ?>
                    
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 <?= $currentStatusIndex >= 0 ? 'list-group-item-success' : ($order['status'] === 'canceled' ? 'list-group-item-danger' : '') ?>">
                        <div>
                            <i class="bi <?= $currentStatusIndex >= 0 ? 'bi-check-circle-fill text-success' : ($order['status'] === 'canceled' ? 'bi-x-circle-fill text-danger' : 'bi-circle text-muted') ?> me-2"></i>
                            <strong>Pendente</strong>
                        </div>
                        <span class="text-muted small"><?= AdminHelper::formatDateTime($order['created_at']) ?></span>
                    </li>
                    
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 <?= $currentStatusIndex >= 1 ? 'list-group-item-success' : ($order['status'] === 'canceled' ? 'list-group-item-danger' : '') ?>">
                        <div>
                            <i class="bi <?= $currentStatusIndex >= 1 ? 'bi-check-circle-fill text-success' : ($order['status'] === 'canceled' ? 'bi-x-circle-fill text-danger' : 'bi-circle text-muted') ?> me-2"></i>
                            <strong>Processando</strong>
                        </div>
                        <span class="text-muted small">
                            <?php 
                            // Idealmente, teríamos um histórico de status para mostrar as datas exatas
                            echo $currentStatusIndex >= 1 ? AdminHelper::formatDateTime($order['updated_at']) : '';
                            ?>
                        </span>
                    </li>
                    
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 <?= $currentStatusIndex >= 2 ? 'list-group-item-success' : ($order['status'] === 'canceled' ? 'list-group-item-danger' : '') ?>">
                        <div>
                            <i class="bi <?= $currentStatusIndex >= 2 ? 'bi-check-circle-fill text-success' : ($order['status'] === 'canceled' ? 'bi-x-circle-fill text-danger' : 'bi-circle text-muted') ?> me-2"></i>
                            <strong>Enviado</strong>
                        </div>
                        <span class="text-muted small">
                            <?php 
                            echo $currentStatusIndex >= 2 ? AdminHelper::formatDateTime($order['updated_at']) : '';
                            ?>
                        </span>
                    </li>
                    
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 <?= $currentStatusIndex >= 3 ? 'list-group-item-success' : ($order['status'] === 'canceled' ? 'list-group-item-danger' : '') ?>">
                        <div>
                            <i class="bi <?= $currentStatusIndex >= 3 ? 'bi-check-circle-fill text-success' : ($order['status'] === 'canceled' ? 'bi-x-circle-fill text-danger' : 'bi-circle text-muted') ?> me-2"></i>
                            <strong>Entregue</strong>
                        </div>
                        <span class="text-muted small">
                            <?php 
                            echo $currentStatusIndex >= 3 ? AdminHelper::formatDateTime($order['updated_at']) : '';
                            ?>
                        </span>
                    </li>
                    
                    <?php if ($order['status'] === 'canceled'): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-3 list-group-item-danger">
                        <div>
                            <i class="bi bi-x-circle-fill text-danger me-2"></i>
                            <strong>Cancelado</strong>
                        </div>
                        <span class="text-muted small"><?= AdminHelper::formatDateTime($order['updated_at']) ?></span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <!-- Payment Status -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Status do Pagamento</h5>
            </div>
            <div class="card-body">
                <?php switch ($order['payment_status']):
                    case 'pending': ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-clock me-2"></i> Pagamento Pendente
                        </div>
                        <?php break; ?>
                    <?php case 'paid': ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i> Pagamento Realizado
                        </div>
                        <?php break; ?>
                    <?php case 'refunded': ?>
                        <div class="alert alert-info">
                            <i class="bi bi-arrow-counterclockwise me-2"></i> Pagamento Estornado
                        </div>
                        <?php break; ?>
                    <?php case 'canceled': ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle me-2"></i> Pagamento Cancelado
                        </div>
                        <?php break; ?>
                <?php endswitch; ?>
                
                <table class="table table-borderless">
                    <tr>
                        <th class="ps-0 w-50">Método de Pagamento:</th>
                        <td>
                            <?php switch ($order['payment_method']):
                                case 'credit_card': ?>
                                    <i class="bi bi-credit-card me-1"></i> Cartão de Crédito
                                    <?php break; ?>
                                <?php case 'boleto': ?>
                                    <i class="bi bi-upc me-1"></i> Boleto
                                    <?php break; ?>
                                <?php case 'pix': ?>
                                    <i class="bi bi-qr-code me-1"></i> PIX
                                    <?php break; ?>
                                <?php default: ?>
                                    <?= $order['payment_method'] ?>
                            <?php endswitch; ?>
                        </td>
                    </tr>
                    <tr>
                        <th class="ps-0">Total do Pedido:</th>
                        <td><?= AdminHelper::formatMoney($order['total']) ?></td>
                    </tr>
                </table>
                
                <?php if ($order['payment_status'] == 'pending'): ?>
                <div class="d-grid">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#updatePaymentModal" data-status="paid">
                        <i class="bi bi-credit-card me-1"></i> Marcar como Pago
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>admin/pedidos/update-status" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Atualizar Status do Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="status" id="status_value">
                    
                    <div id="status_description" class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <span id="status_desc_text">Processando pedido...</span>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Observações (opcional)</label>
                        <textarea class="form-control" name="notes" id="status_notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Atualizar Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Payment Status Modal -->
<div class="modal fade" id="updatePaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>admin/pedidos/update-payment-status" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Atualizar Status de Pagamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="payment_status" id="payment_status_value" value="paid">
                    
                    <div class="alert alert-success mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        Você está marcando este pedido como pago. Isso atualizará apenas o status de pagamento, não o status do pedido.
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Observações (opcional)</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Marcar como Pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Tracking Modal -->
<div class="modal fade" id="addTrackingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>admin/pedidos/add-tracking-code" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Código de Rastreamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    
                    <div class="mb-3">
                        <label for="tracking_code" class="form-label">Código de Rastreamento</label>
                        <input type="text" class="form-control" name="tracking_code" id="tracking_code" required>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i> Ao adicionar o código de rastreamento, o status do pedido será automaticamente alterado para "Enviado".
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar e Marcar como Enviado</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>admin/pedidos/cancel" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Cancelar Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i> Tem certeza que deseja cancelar este pedido? Esta ação não pode ser desfeita.
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Motivo do Cancelamento</label>
                        <textarea class="form-control" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Voltar</button>
                    <button type="submit" class="btn btn-danger">Cancelar Pedido</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>admin/pedidos/add-note" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Nota ao Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    
                    <div class="mb-3">
                        <label for="note" class="form-label">Nota</label>
                        <textarea class="form-control" name="note" rows="4" required></textarea>
                        <div class="form-text">
                            Adicione informações importantes sobre o pedido. Estas notas são visíveis apenas para administradores.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar Nota</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update Status Modal
    const updateStatusModal = document.getElementById('updateStatusModal');
    if (updateStatusModal) {
        updateStatusModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const status = button.getAttribute('data-status');
            const statusValue = document.getElementById('status_value');
            const statusDescription = document.getElementById('status_desc_text');
            
            statusValue.value = status;
            
            // Atualizar descrição do status
            switch (status) {
                case 'processing':
                    statusDescription.textContent = 'Você está marcando este pedido como "Processando". Isso indica que o pedido foi aprovado e está sendo preparado.';
                    break;
                case 'shipped':
                    statusDescription.textContent = 'Você está marcando este pedido como "Enviado". Isso indica que o pedido foi enviado ao cliente.';
                    break;
                case 'delivered':
                    statusDescription.textContent = 'Você está marcando este pedido como "Entregue". Isso indica que o pedido foi recebido pelo cliente.';
                    break;
                default:
                    statusDescription.textContent = 'Atualizando status do pedido...';
            }
        });
    }
    
    // Copy to clipboard functionality for tracking code
    const trackingCode = document.querySelector('.tracking-code');
    if (trackingCode) {
        trackingCode.addEventListener('click', function() {
            const code = this.textContent.trim();
            navigator.clipboard.writeText(code).then(() => {
                const tooltip = document.createElement('div');
                tooltip.classList.add('tooltip', 'show');
                tooltip.textContent = 'Copiado!';
                
                this.appendChild(tooltip);
                
                setTimeout(() => {
                    tooltip.remove();
                }, 2000);
            });
        });
    }
});
</script>

<!-- Print styles -->
<style media="print">
    @page {
        size: A4;
        margin: 15mm;
    }
    body {
        font-size: 12pt;
    }
    #sidebar, nav, .card-header h5 button, .btn, footer {
        display: none !important;
    }
    .container-fluid {
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .card {
        border: 1px solid #ddd !important;
        margin-bottom: 15px !important;
        box-shadow: none !important;
    }
    .collapse {
        display: block !important;
    }
    .timeline-item {
        page-break-inside: avoid;
    }
</style>

<!-- Timeline styles -->
<style>
.timeline {
    position: relative;
    padding: 1rem 0;
}

.timeline:before {
    content: '';
    position: absolute;
    width: 2px;
    height: 100%;
    background: #e9ecef;
    left: 1rem;
    top: 0;
}

.timeline-item {
    position: relative;
    padding-left: 2.5rem;
    padding-bottom: 1.5rem;
}

.timeline-date {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.timeline-content {
    background: #f8f9fa;
    border-radius: 0.25rem;
    padding: 1rem;
    position: relative;
}

.timeline-content:before {
    content: '';
    position: absolute;
    width: 1rem;
    height: 2px;
    background: #e9ecef;
    left: -1rem;
    top: 1rem;
}

.timeline-body {
    font-size: 0.9rem;
}

.tracking-code {
    cursor: pointer;
    font-weight: 500;
    position: relative;
}

.tracking-code:hover {
    text-decoration: underline;
}

.tooltip {
    position: absolute;
    bottom: 120%;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 0.3rem 0.6rem;
    border-radius: 0.25rem;
    font-size: 0.8rem;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.2s;
}

.tooltip.show {
    opacity: 1;
}
</style>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
