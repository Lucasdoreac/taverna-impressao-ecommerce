<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2">Pedido #<?= $order['order_number'] ?></h1>
        <p class="text-muted mb-0">
            Criado em <?= date('d/m/Y \à\s H:i', strtotime($order['created_at'])) ?>
        </p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= BASE_URL ?>admin/pedidos" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-gear me-1"></i> Ações
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php if ($order['status'] == 'pending'): ?>
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-status="processing">Marcar como Em Processamento</a></li>
                <?php elseif ($order['status'] == 'processing'): ?>
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-status="shipped">Marcar como Enviado</a></li>
                <?php elseif ($order['status'] == 'shipped'): ?>
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-status="delivered">Marcar como Entregue</a></li>
                <?php endif; ?>
                
                <?php if (in_array($order['status'], ['pending', 'processing', 'shipped'])): ?>
                <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-status="canceled">Cancelar Pedido</a></li>
                <?php endif; ?>
                
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#notesModal">Adicionar Notas</a></li>
                <li><a class="dropdown-item" href="#" onclick="window.print();">Imprimir Pedido</a></li>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    <!-- Order Details Column -->
    <div class="col-md-8">
        <!-- Status Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">Status do Pedido</h5>
            </div>
            <div class="card-body">
                <div class="row row-cols-1 row-cols-md-4 g-4 text-center">
                    <div class="col">
                        <div class="status-step <?= in_array($order['status'], ['pending', 'processing', 'shipped', 'delivered']) ? 'active' : ($order['status'] === 'canceled' ? 'canceled' : '') ?>">
                            <div class="status-icon">
                                <i class="bi bi-clipboard-check"></i>
                            </div>
                            <div class="status-label">Pendente</div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="status-step <?= in_array($order['status'], ['processing', 'shipped', 'delivered']) ? 'active' : ($order['status'] === 'canceled' ? 'canceled' : '') ?>">
                            <div class="status-icon">
                                <i class="bi bi-gear"></i>
                            </div>
                            <div class="status-label">Em Processamento</div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="status-step <?= in_array($order['status'], ['shipped', 'delivered']) ? 'active' : ($order['status'] === 'canceled' ? 'canceled' : '') ?>">
                            <div class="status-icon">
                                <i class="bi bi-truck"></i>
                            </div>
                            <div class="status-label">Enviado</div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="status-step <?= $order['status'] === 'delivered' ? 'active' : ($order['status'] === 'canceled' ? 'canceled' : '') ?>">
                            <div class="status-icon">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="status-label">Entregue</div>
                        </div>
                    </div>
                </div>
                
                <?php if ($order['status'] === 'canceled'): ?>
                <div class="alert alert-danger text-center mt-3 mb-0">
                    <i class="bi bi-x-circle"></i> Este pedido foi cancelado
                </div>
                <?php endif; ?>
                
                <?php if (!empty($order['tracking_code']) && in_array($order['status'], ['shipped', 'delivered'])): ?>
                <div class="alert alert-info mt-3 mb-0">
                    <strong>Código de Rastreamento:</strong> <?= $order['tracking_code'] ?>
                    <?php if (strpos($order['shipping_method'], 'Correios') !== false): ?>
                    <a href="https://rastreamento.correios.com.br/app/index.php" target="_blank" class="alert-link ms-2">
                        Rastrear <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Order Items Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Itens do Pedido</h5>
                <span class="badge bg-primary"><?= count($items) ?> <?= count($items) > 1 ? 'itens' : 'item' ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produto</th>
                                <th class="text-center">Quantidade</th>
                                <th class="text-end">Preço</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($items)): ?>
                                <?php $totalQuantity = 0; ?>
                                <?php foreach ($items as $item): ?>
                                <?php $totalQuantity += $item['quantity']; ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($item['product_slug'])): ?>
                                            <a href="<?= BASE_URL ?>produto/<?= $item['product_slug'] ?>" target="_blank" class="me-3">
                                                <i class="bi bi-box fs-2"></i>
                                            </a>
                                            <?php else: ?>
                                            <div class="me-3">
                                                <i class="bi bi-box fs-2"></i>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div>
                                                <div class="fw-bold"><?= $item['product_name'] ?></div>
                                                <?php if (!empty($item['product_sku'])): ?>
                                                <small class="text-muted">SKU: <?= $item['product_sku'] ?></small>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($item['customization_data'])): ?>
                                                <div class="mt-1">
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#customization-<?= $item['id'] ?>">
                                                        <i class="bi bi-info-circle"></i> Personalização
                                                    </button>
                                                    <div class="collapse mt-2" id="customization-<?= $item['id'] ?>">
                                                        <div class="card card-body bg-light">
                                                            <?php 
                                                            $customizationData = json_decode($item['customization_data'], true);
                                                            if (is_array($customizationData)): 
                                                            ?>
                                                                <ul class="list-unstyled mb-0">
                                                                <?php foreach ($customizationData as $key => $value): ?>
                                                                    <li><strong><?= ucfirst($key) ?>:</strong> <?= is_array($value) ? implode(", ", $value) : $value ?></li>
                                                                <?php endforeach; ?>
                                                                </ul>
                                                            <?php else: ?>
                                                                <?= $item['customization_data'] ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center"><?= $item['quantity'] ?></td>
                                    <td class="text-end">R$ <?= number_format($item['price'], 2, ',', '.') ?></td>
                                    <td class="text-end">R$ <?= number_format($item['price'] * $item['quantity'], 2, ',', '.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3">Nenhum item encontrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>Total</th>
                                <th class="text-center"><?= $totalQuantity ?? 0 ?></th>
                                <th></th>
                                <th class="text-end">R$ <?= number_format($order['subtotal'], 2, ',', '.') ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Status History Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">Histórico do Pedido</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php if (!empty($statusHistory)): ?>
                        <?php foreach($statusHistory as $history): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h5 class="timeline-title">
                                    <?php
                                    $statusLabels = [
                                        'pending' => 'Pendente',
                                        'processing' => 'Em Processamento',
                                        'shipped' => 'Enviado',
                                        'delivered' => 'Entregue',
                                        'canceled' => 'Cancelado'
                                    ];
                                    echo $statusLabels[$history['status']] ?? $history['status'];
                                    ?>
                                </h5>
                                <p class="timeline-date">
                                    <?= date('d/m/Y H:i:s', strtotime($history['created_at'])) ?>
                                    <?php
                                    $userModel = new UserModel();
                                    $user = $userModel->find($history['user_id']);
                                    if ($user) {
                                        echo ' - Por: ' . $user['name'];
                                    }
                                    ?>
                                </p>
                                <?php if (!empty($history['notes'])): ?>
                                <p class="timeline-text"><?= nl2br(htmlspecialchars($history['notes'])) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h5 class="timeline-title">Pedido Criado</h5>
                                <p class="timeline-date"><?= date('d/m/Y H:i:s', strtotime($order['created_at'])) ?></p>
                                <p class="timeline-text">Status inicial: Pendente</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Side Column -->
    <div class="col-md-4">
        <!-- Customer Info Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">Informações do Cliente</h5>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>Nome:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
                
                <?php 
                $userModel = new UserModel();
                $customer = $userModel->find($order['user_id']);
                if ($customer && !empty($customer['phone'])): 
                ?>
                <p class="mb-1"><strong>Telefone:</strong> <?= htmlspecialchars($customer['phone']) ?></p>
                <?php endif; ?>
                
                <p class="mb-3"><strong>Cliente desde:</strong> <?= date('d/m/Y', strtotime($customer['created_at'] ?? $order['created_at'])) ?></p>
                
                <a href="<?= BASE_URL ?>admin/usuarios/view/<?= $order['user_id'] ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-person me-1"></i> Perfil do Cliente
                </a>
            </div>
        </div>
        
        <!-- Shipping Address Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">Endereço de Entrega</h5>
            </div>
            <div class="card-body">
                <?php if ($address): ?>
                <p class="mb-1"><?= htmlspecialchars($address['address']) ?>, <?= htmlspecialchars($address['number']) ?></p>
                <?php if (!empty($address['complement'])): ?>
                <p class="mb-1"><?= htmlspecialchars($address['complement']) ?></p>
                <?php endif; ?>
                <p class="mb-1"><?= htmlspecialchars($address['neighborhood']) ?></p>
                <p class="mb-1"><?= htmlspecialchars($address['city']) ?> - <?= htmlspecialchars($address['state']) ?></p>
                <p class="mb-3">CEP: <?= htmlspecialchars($address['zipcode']) ?></p>
                <?php else: ?>
                <p class="text-muted">Endereço não disponível.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Payment and Shipping Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">Pagamento e Entrega</h5>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-5"><strong>Método de Pagamento:</strong></div>
                    <div class="col-7">
                        <?php
                        $paymentMethods = [
                            'credit_card' => 'Cartão de Crédito',
                            'boleto' => 'Boleto',
                            'pix' => 'PIX'
                        ];
                        echo $paymentMethods[$order['payment_method']] ?? $order['payment_method'];
                        ?>
                    </div>
                </div>
                
                <div class="row mb-2">
                    <div class="col-5"><strong>Status do Pagamento:</strong></div>
                    <div class="col-7">
                        <?php
                        $paymentStatusLabels = [
                            'pending' => 'Pendente',
                            'paid' => 'Pago',
                            'refunded' => 'Reembolsado',
                            'canceled' => 'Cancelado'
                        ];
                        $paymentStatusClass = [
                            'pending' => 'text-warning',
                            'paid' => 'text-success',
                            'refunded' => 'text-info',
                            'canceled' => 'text-danger'
                        ][$order['payment_status']] ?? '';
                        ?>
                        <span class="<?= $paymentStatusClass ?>">
                            <?= $paymentStatusLabels[$order['payment_status']] ?? $order['payment_status'] ?>
                        </span>
                    </div>
                </div>
                
                <div class="row mb-2">
                    <div class="col-5"><strong>Método de Envio:</strong></div>
                    <div class="col-7"><?= $order['shipping_method'] ?></div>
                </div>
                
                <?php if (!empty($order['tracking_code'])): ?>
                <div class="row mb-2">
                    <div class="col-5"><strong>Rastreamento:</strong></div>
                    <div class="col-7"><?= $order['tracking_code'] ?></div>
                </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="row mb-2">
                    <div class="col-5"><strong>Subtotal:</strong></div>
                    <div class="col-7">R$ <?= number_format($order['subtotal'], 2, ',', '.') ?></div>
                </div>
                
                <div class="row mb-2">
                    <div class="col-5"><strong>Frete:</strong></div>
                    <div class="col-7">R$ <?= number_format($order['shipping_cost'], 2, ',', '.') ?></div>
                </div>
                
                <?php if ($order['discount'] > 0): ?>
                <div class="row mb-2">
                    <div class="col-5"><strong>Desconto:</strong></div>
                    <div class="col-7">-R$ <?= number_format($order['discount'], 2, ',', '.') ?></div>
                </div>
                <?php endif; ?>
                
                <div class="row fw-bold">
                    <div class="col-5"><strong>Total:</strong></div>
                    <div class="col-7">R$ <?= number_format($order['total'], 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
        
        <!-- Notes Card -->
        <?php if (!empty($order['notes'])): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">Observações do Pedido</h5>
            </div>
            <div class="card-body">
                <?= nl2br(htmlspecialchars($order['notes'])) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>admin/pedidos/update-status/<?= $order['id'] ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Atualizar Status do Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="status" id="statusInput" value="">
                    
                    <div class="mb-3">
                        <label for="statusNotes" class="form-label">Notas / Observações</label>
                        <textarea class="form-control" id="statusNotes" name="notes" rows="4" placeholder="Adicione informações sobre esta mudança de status (opcional)"></textarea>
                    </div>
                    
                    <div id="cancelWarning" class="alert alert-danger d-none">
                        <i class="bi bi-exclamation-triangle"></i> <strong>Atenção!</strong> Cancelar um pedido retornará os itens ao estoque e não poderá ser desfeito.
                    </div>
                    
                    <div id="trackingCodeField" class="mb-3 d-none">
                        <label for="trackingCode" class="form-label">Código de Rastreamento</label>
                        <input type="text" class="form-control" id="trackingCode" name="tracking_code" placeholder="Informe o código de rastreamento">
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

<!-- Add Notes Modal -->
<div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>admin/pedidos/add-notes/<?= $order['id'] ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="notesModalLabel">Adicionar Observações ao Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="orderNotes" class="form-label">Observações</label>
                        <textarea class="form-control" id="orderNotes" name="notes" rows="5" placeholder="Adicione observações ao pedido"><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Status Steps */
.status-step {
    text-align: center;
    position: relative;
}

.status-step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 25px;
    right: -50%;
    width: 100%;
    height: 2px;
    background-color: #dee2e6;
    z-index: 1;
}

.status-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #f8f9fa;
    border: 2px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    position: relative;
    z-index: 2;
}

.status-icon i {
    font-size: 20px;
    color: #6c757d;
}

.status-step.active .status-icon {
    border-color: #0d6efd;
    background-color: #e7f1ff;
}

.status-step.active .status-icon i {
    color: #0d6efd;
}

.status-step.active:not(:last-child)::after {
    background-color: #0d6efd;
}

.status-step.canceled .status-icon {
    border-color: #dc3545;
    background-color: #f8d7da;
}

.status-step.canceled .status-icon i {
    color: #dc3545;
}

.status-step.canceled:not(:last-child)::after {
    background-color: #dc3545;
}

/* Timeline */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 7px;
    width: 2px;
    background-color: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background-color: #0d6efd;
    border: 2px solid #fff;
}

.timeline-title {
    font-size: 1rem;
    margin-bottom: 5px;
}

.timeline-date {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 10px;
}

.timeline-text {
    margin-bottom: 0;
}

/* Print Styles */
@media print {
    .btn-toolbar, .dropdown-toggle, .btn-group, .timeline-marker, .card-header {
        display: none !important;
    }
    
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }
    
    .timeline::before {
        display: none;
    }
    
    .timeline {
        padding-left: 0;
    }
    
    .timeline-item {
        margin-bottom: 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar modal de atualização de status
    var updateStatusModal = document.getElementById('updateStatusModal');
    if (updateStatusModal) {
        updateStatusModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var status = button.getAttribute('data-status');
            
            var statusField = document.getElementById('statusInput');
            var cancelWarning = document.getElementById('cancelWarning');
            var trackingCodeField = document.getElementById('trackingCodeField');
            
            // Preencher campos
            statusField.value = status;
            
            // Mostrar/esconder avisos e campos adicionais
            if (status === 'canceled') {
                cancelWarning.classList.remove('d-none');
            } else {
                cancelWarning.classList.add('d-none');
            }
            
            if (status === 'shipped') {
                trackingCodeField.classList.remove('d-none');
            } else {
                trackingCodeField.classList.add('d-none');
            }
            
            // Atualizar título da modal
            var statusLabels = {
                'processing': 'Em Processamento',
                'shipped': 'Enviado',
                'delivered': 'Entregue',
                'canceled': 'Cancelado'
            };
            
            var modalTitle = document.getElementById('updateStatusModalLabel');
            modalTitle.textContent = 'Atualizar Status para ' + (statusLabels[status] || status);
        });
    }
});
</script>
