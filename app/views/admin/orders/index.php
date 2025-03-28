<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<!-- Page Title and Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Pedidos</h1>
    <div>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
            <i class="bi bi-download me-1"></i> Exportar
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Filtros</h5>
    </div>
    <div class="card-body">
        <form action="<?= BASE_URL ?>admin/pedidos" method="get" class="row g-3">
            <div class="col-md-3">
                <label for="order_number" class="form-label">Número do Pedido</label>
                <input type="text" class="form-control" id="order_number" name="order_number" value="<?= $_GET['order_number'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label for="customer" class="form-label">Cliente</label>
                <input type="text" class="form-control" id="customer" name="customer" value="<?= $_GET['customer'] ?? '' ?>" placeholder="Nome ou e-mail">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    <option value="pending" <?= (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : '' ?>>Pendente</option>
                    <option value="processing" <?= (isset($_GET['status']) && $_GET['status'] == 'processing') ? 'selected' : '' ?>>Processando</option>
                    <option value="shipped" <?= (isset($_GET['status']) && $_GET['status'] == 'shipped') ? 'selected' : '' ?>>Enviado</option>
                    <option value="delivered" <?= (isset($_GET['status']) && $_GET['status'] == 'delivered') ? 'selected' : '' ?>>Entregue</option>
                    <option value="canceled" <?= (isset($_GET['status']) && $_GET['status'] == 'canceled') ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="payment_status" class="form-label">Status de Pagamento</label>
                <select class="form-select" id="payment_status" name="payment_status">
                    <option value="">Todos</option>
                    <option value="pending" <?= (isset($_GET['payment_status']) && $_GET['payment_status'] == 'pending') ? 'selected' : '' ?>>Pendente</option>
                    <option value="paid" <?= (isset($_GET['payment_status']) && $_GET['payment_status'] == 'paid') ? 'selected' : '' ?>>Pago</option>
                    <option value="refunded" <?= (isset($_GET['payment_status']) && $_GET['payment_status'] == 'refunded') ? 'selected' : '' ?>>Estornado</option>
                    <option value="canceled" <?= (isset($_GET['payment_status']) && $_GET['payment_status'] == 'canceled') ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="payment_method" class="form-label">Método de Pagamento</label>
                <select class="form-select" id="payment_method" name="payment_method">
                    <option value="">Todos</option>
                    <option value="credit_card" <?= (isset($_GET['payment_method']) && $_GET['payment_method'] == 'credit_card') ? 'selected' : '' ?>>Cartão de Crédito</option>
                    <option value="boleto" <?= (isset($_GET['payment_method']) && $_GET['payment_method'] == 'boleto') ? 'selected' : '' ?>>Boleto</option>
                    <option value="pix" <?= (isset($_GET['payment_method']) && $_GET['payment_method'] == 'pix') ? 'selected' : '' ?>>PIX</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Período</label>
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $_GET['date_from'] ?? '' ?>" placeholder="De">
                    </div>
                    <div class="col-md-6">
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $_GET['date_to'] ?? '' ?>" placeholder="Até">
                    </div>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i> Filtrar
                </button>
                <a href="<?= BASE_URL ?>admin/pedidos" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Limpar Filtros
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Quick Status Tabs -->
<div class="mb-4">
    <ul class="nav nav-pills">
        <li class="nav-item">
            <a class="nav-link <?= !isset($_GET['status']) ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/pedidos">
                Todos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/pedidos?status=pending">
                Pendentes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= (isset($_GET['status']) && $_GET['status'] == 'processing') ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/pedidos?status=processing">
                Processando
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= (isset($_GET['status']) && $_GET['status'] == 'shipped') ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/pedidos?status=shipped">
                Enviados
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= (isset($_GET['status']) && $_GET['status'] == 'delivered') ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/pedidos?status=delivered">
                Entregues
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= (isset($_GET['status']) && $_GET['status'] == 'canceled') ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/pedidos?status=canceled">
                Cancelados
            </a>
        </li>
    </ul>
</div>

<!-- Orders List -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Lista de Pedidos</h5>
        <span class="text-muted small">
            Total: <?= $orders['total'] ?> pedidos 
            | Exibindo: <?= $orders['from'] ?>-<?= $orders['to'] ?>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Pedido</th>
                        <th scope="col">Data</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Total</th>
                        <th scope="col">Status</th>
                        <th scope="col">Pagamento</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders['items'])): ?>
                    <tr>
                        <td colspan="7" class="text-center py-3">Nenhum pedido encontrado.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($orders['items'] as $order): ?>
                    <tr>
                        <td>
                            <span class="fw-medium"><?= $order['order_number'] ?></span>
                        </td>
                        <td><?= AdminHelper::formatDateTime($order['created_at']) ?></td>
                        <td>
                            <?php if ($order['user_name']): ?>
                            <div><?= $order['user_name'] ?></div>
                            <div class="small text-muted"><?= $order['user_email'] ?></div>
                            <?php else: ?>
                            <span class="text-muted">Cliente não registrado</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="fw-medium"><?= AdminHelper::formatMoney($order['total']) ?></span></td>
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
                        <td>
                            <?php switch ($order['payment_status']):
                                case 'pending': ?>
                                    <span class="badge bg-warning">Pendente</span>
                                    <?php break; ?>
                                <?php case 'paid': ?>
                                    <span class="badge bg-success">Pago</span>
                                    <?php break; ?>
                                <?php case 'refunded': ?>
                                    <span class="badge bg-info">Estornado</span>
                                    <?php break; ?>
                                <?php case 'canceled': ?>
                                    <span class="badge bg-danger">Cancelado</span>
                                    <?php break; ?>
                                <?php default: ?>
                                    <span class="badge bg-secondary">Desconhecido</span>
                            <?php endswitch; ?>
                            <div class="small text-muted mt-1">
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
                            </div>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>admin/pedidos/view/<?= $order['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye me-1"></i> Ver
                            </a>
                            
                            <?php if ($order['status'] != 'canceled'): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-gear me-1"></i> Ações
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($order['status'] == 'pending'): ?>
                                <li>
                                    <button type="button" class="dropdown-item update-status" data-id="<?= $order['id'] ?>" data-status="processing">
                                        <i class="bi bi-arrow-right-circle me-1 text-info"></i> Marcar como Processando
                                    </button>
                                </li>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] == 'processing'): ?>
                                <li>
                                    <button type="button" class="dropdown-item add-tracking" data-id="<?= $order['id'] ?>">
                                        <i class="bi bi-truck me-1 text-primary"></i> Adicionar Rastreio
                                    </button>
                                </li>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] == 'shipped'): ?>
                                <li>
                                    <button type="button" class="dropdown-item update-status" data-id="<?= $order['id'] ?>" data-status="delivered">
                                        <i class="bi bi-check-circle me-1 text-success"></i> Marcar como Entregue
                                    </button>
                                </li>
                                <?php endif; ?>
                                
                                <?php if ($order['payment_status'] == 'pending'): ?>
                                <li>
                                    <button type="button" class="dropdown-item update-payment" data-id="<?= $order['id'] ?>" data-status="paid">
                                        <i class="bi bi-credit-card me-1 text-success"></i> Marcar como Pago
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                
                                <li>
                                    <button type="button" class="dropdown-item cancel-order" data-id="<?= $order['id'] ?>">
                                        <i class="bi bi-x-circle me-1 text-danger"></i> Cancelar Pedido
                                    </button>
                                </li>
                            </ul>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($orders['lastPage'] > 1): ?>
    <div class="card-footer bg-white">
        <nav aria-label="Paginação">
            <ul class="pagination mb-0 justify-content-center">
                <li class="page-item <?= $orders['currentPage'] == 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>admin/pedidos?page=1<?= isset($_GET['order_number']) ? '&order_number=' . $_GET['order_number'] : '' ?><?= isset($_GET['customer']) ? '&customer=' . $_GET['customer'] : '' ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['payment_status']) ? '&payment_status=' . $_GET['payment_status'] : '' ?><?= isset($_GET['payment_method']) ? '&payment_method=' . $_GET['payment_method'] : '' ?><?= isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '' ?><?= isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '' ?>">
                        <i class="bi bi-chevron-double-left"></i>
                    </a>
                </li>
                <li class="page-item <?= $orders['currentPage'] == 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>admin/pedidos?page=<?= $orders['currentPage'] - 1 ?><?= isset($_GET['order_number']) ? '&order_number=' . $_GET['order_number'] : '' ?><?= isset($_GET['customer']) ? '&customer=' . $_GET['customer'] : '' ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['payment_status']) ? '&payment_status=' . $_GET['payment_status'] : '' ?><?= isset($_GET['payment_method']) ? '&payment_method=' . $_GET['payment_method'] : '' ?><?= isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '' ?><?= isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '' ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>

                <?php
                $startPage = max(1, $orders['currentPage'] - 2);
                $endPage = min($orders['lastPage'], $orders['currentPage'] + 2);
                
                if ($startPage > 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                
                for ($i = $startPage; $i <= $endPage; $i++): 
                ?>
                <li class="page-item <?= $orders['currentPage'] == $i ? 'active' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>admin/pedidos?page=<?= $i ?><?= isset($_GET['order_number']) ? '&order_number=' . $_GET['order_number'] : '' ?><?= isset($_GET['customer']) ? '&customer=' . $_GET['customer'] : '' ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['payment_status']) ? '&payment_status=' . $_GET['payment_status'] : '' ?><?= isset($_GET['payment_method']) ? '&payment_method=' . $_GET['payment_method'] : '' ?><?= isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '' ?><?= isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '' ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>

                <?php
                if ($endPage < $orders['lastPage']) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                ?>

                <li class="page-item <?= $orders['currentPage'] == $orders['lastPage'] ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>admin/pedidos?page=<?= $orders['currentPage'] + 1 ?><?= isset($_GET['order_number']) ? '&order_number=' . $_GET['order_number'] : '' ?><?= isset($_GET['customer']) ? '&customer=' . $_GET['customer'] : '' ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['payment_status']) ? '&payment_status=' . $_GET['payment_status'] : '' ?><?= isset($_GET['payment_method']) ? '&payment_method=' . $_GET['payment_method'] : '' ?><?= isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '' ?><?= isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '' ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
                <li class="page-item <?= $orders['currentPage'] == $orders['lastPage'] ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>admin/pedidos?page=<?= $orders['lastPage'] ?><?= isset($_GET['order_number']) ? '&order_number=' . $_GET['order_number'] : '' ?><?= isset($_GET['customer']) ? '&customer=' . $_GET['customer'] : '' ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['payment_status']) ? '&payment_status=' . $_GET['payment_status'] : '' ?><?= isset($_GET['payment_method']) ? '&payment_method=' . $_GET['payment_method'] : '' ?><?= isset($_GET['date_from']) ? '&date_from=' . $_GET['date_from'] : '' ?><?= isset($_GET['date_to']) ? '&date_to=' . $_GET['date_to'] : '' ?>">
                        <i class="bi bi-chevron-double-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
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
                    <input type="hidden" name="order_id" id="status_order_id">
                    <input type="hidden" name="status" id="status_value">
                    
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
                    <input type="hidden" name="order_id" id="payment_order_id">
                    <input type="hidden" name="payment_status" id="payment_status_value">
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Observações (opcional)</label>
                        <textarea class="form-control" name="notes" id="payment_notes" rows="3"></textarea>
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
                    <input type="hidden" name="order_id" id="tracking_order_id">
                    
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
                    <button type="submit" class="btn btn-primary">Adicionar</button>
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
                    <input type="hidden" name="order_id" id="cancel_order_id">
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i> Tem certeza que deseja cancelar este pedido? Esta ação não pode ser desfeita.
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Motivo do Cancelamento</label>
                        <textarea class="form-control" name="reason" id="cancel_reason" rows="3" required></textarea>
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

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>admin/pedidos/export" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Exportar Pedidos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Formato</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="format" id="format_csv" value="csv" checked>
                                <label class="form-check-label" for="format_csv">CSV</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="format" id="format_xlsx" value="xlsx">
                                <label class="form-check-label" for="format_xlsx">Excel (XLSX)</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Período</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="date" class="form-control" name="export_date_from" placeholder="De">
                            </div>
                            <div class="col-md-6">
                                <input type="date" class="form-control" name="export_date_to" placeholder="Até">
                            </div>
                        </div>
                        <div class="form-text">Deixe em branco para exportar todos os pedidos.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="export_status">
                            <option value="">Todos</option>
                            <option value="pending">Pendente</option>
                            <option value="processing">Processando</option>
                            <option value="shipped">Enviado</option>
                            <option value="delivered">Entregue</option>
                            <option value="canceled">Cancelado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-download me-1"></i> Exportar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update Status
    const updateStatusButtons = document.querySelectorAll('.update-status');
    const updateStatusModal = document.getElementById('updateStatusModal');
    
    updateStatusButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            
            document.getElementById('status_order_id').value = orderId;
            document.getElementById('status_value').value = status;
            
            const modal = new bootstrap.Modal(updateStatusModal);
            modal.show();
        });
    });
    
    // Update Payment Status
    const updatePaymentButtons = document.querySelectorAll('.update-payment');
    const updatePaymentModal = document.getElementById('updatePaymentModal');
    
    updatePaymentButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            
            document.getElementById('payment_order_id').value = orderId;
            document.getElementById('payment_status_value').value = status;
            
            const modal = new bootstrap.Modal(updatePaymentModal);
            modal.show();
        });
    });
    
    // Add Tracking
    const addTrackingButtons = document.querySelectorAll('.add-tracking');
    const addTrackingModal = document.getElementById('addTrackingModal');
    
    addTrackingButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            
            document.getElementById('tracking_order_id').value = orderId;
            
            const modal = new bootstrap.Modal(addTrackingModal);
            modal.show();
        });
    });
    
    // Cancel Order
    const cancelOrderButtons = document.querySelectorAll('.cancel-order');
    const cancelOrderModal = document.getElementById('cancelOrderModal');
    
    cancelOrderButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            
            document.getElementById('cancel_order_id').value = orderId;
            
            const modal = new bootstrap.Modal(cancelOrderModal);
            modal.show();
        });
    });
});
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
