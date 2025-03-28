<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<!-- Page Title and Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Detalhes do Usuário</h1>
    <div>
        <a href="<?= BASE_URL ?>admin/usuarios" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
        <a href="<?= BASE_URL ?>admin/usuarios/edit/<?= $user['id'] ?>" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Editar
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- User Info Column -->
    <div class="col-lg-4">
        <!-- User Profile Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Perfil</h5>
                <span class="badge <?= $user['role'] == 'admin' ? 'bg-primary' : 'bg-success' ?>">
                    <?= $user['role'] == 'admin' ? 'Administrador' : 'Cliente' ?>
                </span>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="avatar-circle mx-auto mb-3 bg-<?= $user['role'] == 'admin' ? 'primary' : 'success' ?>">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <h5 class="mb-0"><?= htmlspecialchars($user['name']) ?></h5>
                    <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                    
                    <?php if (isset($user['is_active'])): ?>
                        <span class="badge <?= $user['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                            <?= $user['is_active'] ? 'Ativo' : 'Inativo' ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <hr>
                
                <div class="user-details">
                    <div class="mb-3">
                        <div class="fw-bold text-muted small">Telefone</div>
                        <div><?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : '<span class="text-muted">Não informado</span>' ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="fw-bold text-muted small">Data de Cadastro</div>
                        <div><?= isset($user['created_at']) ? AdminHelper::formatDateTime($user['created_at']) : 'N/A' ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="fw-bold text-muted small">Última Atualização</div>
                        <div><?= isset($user['updated_at']) ? AdminHelper::formatDateTime($user['updated_at']) : 'N/A' ?></div>
                    </div>
                </div>
                
                <?php if ($user['id'] != $_SESSION['user']['id']): ?>
                <div class="mt-4">
                    <div class="d-grid gap-2">
                        <?php if (isset($user['is_active']) && $user['is_active']): ?>
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#statusModal" data-user-id="<?= $user['id'] ?>" data-user-name="<?= htmlspecialchars($user['name']) ?>" data-user-status="1">
                            <i class="bi bi-slash-circle me-1"></i> Desativar Usuário
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#statusModal" data-user-id="<?= $user['id'] ?>" data-user-name="<?= htmlspecialchars($user['name']) ?>" data-user-status="0">
                            <i class="bi bi-check-circle me-1"></i> Ativar Usuário
                        </button>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-user-id="<?= $user['id'] ?>" data-user-name="<?= htmlspecialchars($user['name']) ?>">
                            <i class="bi bi-trash me-1"></i> Excluir Usuário
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- User Details Column -->
    <div class="col-lg-8">
        <!-- Addresses -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Endereços</h5>
                <span class="badge bg-secondary"><?= count($addresses) ?></span>
            </div>
            <div class="card-body">
                <?php if (empty($addresses)): ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i> Este usuário não possui endereços cadastrados.
                </div>
                <?php else: ?>
                <div class="accordion" id="addressesAccordion">
                    <?php foreach ($addresses as $index => $address): ?>
                    <div class="accordion-item border-0 border-bottom">
                        <h2 class="accordion-header" id="address-heading-<?= $address['id'] ?>">
                            <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#address-collapse-<?= $address['id'] ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="address-collapse-<?= $address['id'] ?>">
                                <div>
                                    <?= htmlspecialchars($address['address']) ?>, <?= htmlspecialchars($address['number']) ?>
                                    <?php if ($address['is_default']): ?>
                                    <span class="badge bg-primary ms-2">Padrão</span>
                                    <?php endif; ?>
                                </div>
                            </button>
                        </h2>
                        <div id="address-collapse-<?= $address['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="address-heading-<?= $address['id'] ?>" data-bs-parent="#addressesAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <address>
                                            <?= htmlspecialchars($address['address']) ?>, <?= htmlspecialchars($address['number']) ?><br>
                                            <?= !empty($address['complement']) ? htmlspecialchars($address['complement']) . '<br>' : '' ?>
                                            <?= htmlspecialchars($address['neighborhood']) ?><br>
                                            <?= htmlspecialchars($address['city']) ?> - <?= htmlspecialchars($address['state']) ?><br>
                                            CEP: <?= htmlspecialchars($address['zipcode']) ?>
                                        </address>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Orders -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Pedidos</h5>
                <span class="badge bg-secondary"><?= count($orders) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($orders)): ?>
                <div class="p-4">
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i> Este usuário não possui pedidos realizados.
                    </div>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th scope="col" class="ps-3">Pedido</th>
                                <th scope="col">Data</th>
                                <th scope="col">Status</th>
                                <th scope="col">Pagamento</th>
                                <th scope="col" class="text-end pe-3">Total</th>
                                <th scope="col" class="text-end pe-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="ps-3">#<?= $order['order_number'] ?></td>
                                <td><?= AdminHelper::formatDate($order['created_at']) ?></td>
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
                                </td>
                                <td class="text-end pe-3"><?= AdminHelper::formatMoney($order['total']) ?></td>
                                <td class="text-end pe-3">
                                    <a href="<?= BASE_URL ?>admin/pedidos/view/<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
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

<!-- Modals -->

<!-- Status Change Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>admin/usuarios/toggle-status" method="post">
                <input type="hidden" name="id" id="statusUserId">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">Alterar Status do Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div id="statusMessage"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="statusConfirmButton">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>admin/usuarios/delete" method="post">
                <input type="hidden" name="id" id="deleteUserId">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <span>Esta ação não pode ser desfeita!</span>
                    </div>
                    <p>Você tem certeza que deseja excluir o usuário <strong id="deleteUserName"></strong>?</p>
                    <p>Todos os dados associados a este usuário, como endereços, serão removidos. 
                    Esta operação não será permitida se o usuário possuir pedidos no sistema.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Excluir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status Modal
    const statusModal = document.getElementById('statusModal');
    if (statusModal) {
        statusModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            const userStatus = button.getAttribute('data-user-status');
            
            const statusUserId = document.getElementById('statusUserId');
            const statusMessage = document.getElementById('statusMessage');
            const statusConfirmButton = document.getElementById('statusConfirmButton');
            
            statusUserId.value = userId;
            
            if (userStatus === '1') {
                // Currently active, will be deactivated
                statusMessage.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <span>Desativar acesso de usuário</span>
                    </div>
                    <p>Você está prestes a desativar o usuário <strong>${userName}</strong>.</p>
                    <p>Usuários desativados não poderão fazer login no sistema.</p>
                `;
                statusConfirmButton.classList.remove('btn-success');
                statusConfirmButton.classList.add('btn-warning');
                statusConfirmButton.innerHTML = '<i class="bi bi-slash-circle me-1"></i> Desativar';
            } else {
                // Currently inactive, will be activated
                statusMessage.innerHTML = `
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <span>Ativar acesso de usuário</span>
                    </div>
                    <p>Você está prestes a ativar o usuário <strong>${userName}</strong>.</p>
                    <p>Usuários ativos podem fazer login e acessar o sistema normalmente.</p>
                `;
                statusConfirmButton.classList.remove('btn-warning');
                statusConfirmButton.classList.add('btn-success');
                statusConfirmButton.innerHTML = '<i class="bi bi-check-circle me-1"></i> Ativar';
            }
        });
    }
    
    // Delete Modal
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = userName;
        });
    }
});
</script>

<!-- Custom CSS for User Avatar -->
<style>
.avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 2rem;
}
</style>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>