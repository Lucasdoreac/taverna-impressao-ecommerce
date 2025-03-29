<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Pedidos</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="<?= BASE_URL ?>admin/pedidos/export<?= !empty($currentStatus) ? "?status={$currentStatus}" : "" ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-download me-1"></i> Exportar
            </a>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#filterModal">
                <i class="bi bi-funnel me-1"></i> Filtrar
            </button>
        </div>
    </div>
</div>

<!-- Status Tabs -->
<div class="mb-4">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link <?= empty($currentStatus) ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/pedidos">
                Todos <span class="badge text-bg-secondary ms-1"><?= $statusCounts['all'] ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentStatus === 'pending' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/pedidos?status=pending">
                Pendentes <span class="badge text-bg-warning ms-1"><?= $statusCounts['pending'] ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentStatus === 'processing' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/pedidos?status=processing">
                Em Processamento <span class="badge text-bg-info ms-1"><?= $statusCounts['processing'] ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentStatus === 'shipped' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/pedidos?status=shipped">
                Enviados <span class="badge text-bg-primary ms-1"><?= $statusCounts['shipped'] ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentStatus === 'delivered' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/pedidos?status=delivered">
                Entregues <span class="badge text-bg-success ms-1"><?= $statusCounts['delivered'] ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentStatus === 'canceled' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/pedidos?status=canceled">
                Cancelados <span class="badge text-bg-danger ms-1"><?= $statusCounts['canceled'] ?></span>
            </a>
        </li>
    </ul>
</div>

<!-- Search Form -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <form action="<?= BASE_URL ?>admin/pedidos" method="get" class="row g-3">
            <?php if (!empty($currentStatus)): ?>
            <input type="hidden" name="status" value="<?= $currentStatus ?>">
            <?php endif; ?>
            
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Buscar por número do pedido" value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-outline-primary" type="submit">Buscar</button>
                </div>
            </div>
            
            <div class="col-md-6 text-md-end">
                <span class="text-muted">
                    <?= $pagination['totalItems'] ?> pedido(s) encontrado(s)
                </span>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Nº</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Data</th>
                        <th scope="col">Status</th>
                        <th scope="col">Pagamento</th>
                        <th scope="col">Total</th>
                        <th scope="col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= $order['order_number'] ?></td>
                            <td><?= htmlspecialchars($order['customer_name']) ?><br><small class="text-muted"><?= $order['customer_email'] ?></small></td>
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
                            <td>
                                <?php
                                $paymentMethods = [
                                    'credit_card' => 'Cartão de Crédito',
                                    'boleto' => 'Boleto',
                                    'pix' => 'PIX'
                                ];
                                
                                $paymentStatusClasses = [
                                    'pending' => 'bg-warning',
                                    'paid' => 'bg-success',
                                    'refunded' => 'bg-info',
                                    'canceled' => 'bg-danger'
                                ];
                                $paymentStatusLabels = [
                                    'pending' => 'Pendente',
                                    'paid' => 'Pago',
                                    'refunded' => 'Reembolsado',
                                    'canceled' => 'Cancelado'
                                ];
                                
                                $paymentMethod = $paymentMethods[$order['payment_method']] ?? $order['payment_method'];
                                $paymentStatusClass = $paymentStatusClasses[$order['payment_status']] ?? 'bg-secondary';
                                $paymentStatusLabel = $paymentStatusLabels[$order['payment_status']] ?? $order['payment_status'];
                                ?>
                                <?= $paymentMethod ?><br>
                                <span class="badge <?= $paymentStatusClass ?>"><?= $paymentStatusLabel ?></span>
                            </td>
                            <td>R$ <?= number_format($order['total'], 2, ',', '.') ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?= BASE_URL ?>admin/pedidos/view/<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/pedidos/view/<?= $order['id'] ?>">Ver detalhes</a></li>
                                        <?php if ($order['status'] == 'pending'): ?>
                                        <li><a class="dropdown-item text-primary" href="#" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-order-id="<?= $order['id'] ?>" data-status="processing">Marcar como Em Processamento</a></li>
                                        <?php elseif ($order['status'] == 'processing'): ?>
                                        <li><a class="dropdown-item text-primary" href="#" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-order-id="<?= $order['id'] ?>" data-status="shipped">Marcar como Enviado</a></li>
                                        <?php elseif ($order['status'] == 'shipped'): ?>
                                        <li><a class="dropdown-item text-success" href="#" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-order-id="<?= $order['id'] ?>" data-status="delivered">Marcar como Entregue</a></li>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($order['status'], ['pending', 'processing', 'shipped'])): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-order-id="<?= $order['id'] ?>" data-status="canceled">Cancelar Pedido</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-3">Nenhum pedido encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($pagination['totalPages'] > 1): ?>
<div class="d-flex justify-content-between align-items-center mt-4">
    <div class="text-muted">
        Exibindo <?= (($pagination['currentPage'] - 1) * $pagination['itemsPerPage']) + 1 ?> a 
        <?= min($pagination['currentPage'] * $pagination['itemsPerPage'], $pagination['totalItems']) ?> 
        de <?= $pagination['totalItems'] ?> pedidos
    </div>
    
    <nav>
        <ul class="pagination mb-0">
            <?php if ($pagination['hasPrevPage']): ?>
            <li class="page-item">
                <a class="page-link" href="<?= BASE_URL ?>admin/pedidos?page=<?= $pagination['currentPage'] - 1 ?><?= !empty($currentStatus) ? "&status={$currentStatus}" : "" ?><?= !empty($search) ? "&search={$search}" : "" ?>">
                    Anterior
                </a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">Anterior</span>
            </li>
            <?php endif; ?>
            
            <?php
            $startPage = max(1, min($pagination['currentPage'] - 2, $pagination['totalPages'] - 4));
            $endPage = min($pagination['totalPages'], max($pagination['currentPage'] + 2, 5));
            ?>
            
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li class="page-item <?= $i == $pagination['currentPage'] ? 'active' : '' ?>">
                <a class="page-link" href="<?= BASE_URL ?>admin/pedidos?page=<?= $i ?><?= !empty($currentStatus) ? "&status={$currentStatus}" : "" ?><?= !empty($search) ? "&search={$search}" : "" ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>
            
            <?php if ($pagination['hasNextPage']): ?>
            <li class="page-item">
                <a class="page-link" href="<?= BASE_URL ?>admin/pedidos?page=<?= $pagination['currentPage'] + 1 ?><?= !empty($currentStatus) ? "&status={$currentStatus}" : "" ?><?= !empty($search) ? "&search={$search}" : "" ?>">
                    Próxima
                </a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">Próxima</span>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
<?php endif; ?>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="updateStatusForm" action="<?= BASE_URL ?>admin/pedidos/update-status/" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Atualizar Status do Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="statusOrderId" name="order_id">
                    <input type="hidden" id="newStatus" name="status">
                    
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

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>admin/pedidos" method="get">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filtrar Pedidos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="filterStatus" class="form-label">Status</label>
                        <select class="form-select" id="filterStatus" name="status">
                            <option value="">Todos</option>
                            <option value="pending" <?= $currentStatus === 'pending' ? 'selected' : '' ?>>Pendente</option>
                            <option value="processing" <?= $currentStatus === 'processing' ? 'selected' : '' ?>>Em Processamento</option>
                            <option value="shipped" <?= $currentStatus === 'shipped' ? 'selected' : '' ?>>Enviado</option>
                            <option value="delivered" <?= $currentStatus === 'delivered' ? 'selected' : '' ?>>Entregue</option>
                            <option value="canceled" <?= $currentStatus === 'canceled' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="filterPaymentMethod" class="form-label">Método de Pagamento</label>
                        <select class="form-select" id="filterPaymentMethod" name="payment_method">
                            <option value="">Todos</option>
                            <option value="credit_card" <?= isset($_GET['payment_method']) && $_GET['payment_method'] === 'credit_card' ? 'selected' : '' ?>>Cartão de Crédito</option>
                            <option value="boleto" <?= isset($_GET['payment_method']) && $_GET['payment_method'] === 'boleto' ? 'selected' : '' ?>>Boleto</option>
                            <option value="pix" <?= isset($_GET['payment_method']) && $_GET['payment_method'] === 'pix' ? 'selected' : '' ?>>PIX</option>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="filterStartDate" class="form-label">Data Inicial</label>
                            <input type="date" class="form-control" id="filterStartDate" name="start_date" value="<?= isset($_GET['start_date']) ? $_GET['start_date'] : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="filterEndDate" class="form-label">Data Final</label>
                            <input type="date" class="form-control" id="filterEndDate" name="end_date" value="<?= isset($_GET['end_date']) ? $_GET['end_date'] : '' ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="filterSearch" class="form-label">Busca</label>
                        <input type="text" class="form-control" id="filterSearch" name="search" placeholder="Número do pedido" value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="<?= BASE_URL ?>admin/pedidos" class="btn btn-outline-secondary">Limpar Filtros</a>
                    <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar modal de atualização de status
    var updateStatusModal = document.getElementById('updateStatusModal');
    if (updateStatusModal) {
        updateStatusModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var orderId = button.getAttribute('data-order-id');
            var status = button.getAttribute('data-status');
            
            var form = document.getElementById('updateStatusForm');
            var orderIdField = document.getElementById('statusOrderId');
            var statusField = document.getElementById('newStatus');
            var cancelWarning = document.getElementById('cancelWarning');
            var trackingCodeField = document.getElementById('trackingCodeField');
            
            // Atualizar action do form
            form.action = '<?= BASE_URL ?>admin/pedidos/update-status/' + orderId;
            
            // Preencher campos
            orderIdField.value = orderId;
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
