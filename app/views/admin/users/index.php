<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<!-- Page Title and Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Gerenciamento de Usuários</h1>
    <a href="<?= BASE_URL ?>admin/usuarios/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Novo Usuário
    </a>
</div>

<!-- Search and Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="<?= BASE_URL ?>admin/usuarios" method="get" class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Buscar por nome ou e-mail" name="search" value="<?= htmlspecialchars($search ?? '') ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search me-1"></i> Buscar
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="role" onchange="this.form.submit()">
                    <option value="">Todos os tipos de usuário</option>
                    <option value="admin" <?= ($role == 'admin') ? 'selected' : '' ?>>Administradores</option>
                    <option value="customer" <?= ($role == 'customer') ? 'selected' : '' ?>>Clientes</option>
                </select>
            </div>
            <div class="col-md-2">
                <?php if (!empty($search) || !empty($role)): ?>
                <a href="<?= BASE_URL ?>admin/usuarios" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle me-1"></i> Limpar
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Users List -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th scope="col" class="ps-3">#</th>
                        <th scope="col">Nome</th>
                        <th scope="col">E-mail</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-center">Cadastro</th>
                        <th scope="col" class="text-end pe-3">Ações</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-search mb-2 fs-2"></i>
                                <p>Nenhum usuário encontrado.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="ps-3"><?= $user['id'] ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-2 bg-<?= $user['role'] == 'admin' ? 'primary' : 'success' ?>">
                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <span class="fw-medium"><?= htmlspecialchars($user['name']) ?></span>
                                    <?php if ($user['id'] == $_SESSION['user']['id']): ?>
                                        <span class="badge bg-primary ms-1">Você</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <?php if ($user['role'] == 'admin'): ?>
                                <span class="badge bg-primary">Administrador</span>
                            <?php else: ?>
                                <span class="badge bg-success">Cliente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($user['is_active']) && $user['is_active']): ?>
                                <span class="badge bg-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?= isset($user['created_at']) ? AdminHelper::formatDate($user['created_at']) : 'N/A' ?>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group">
                                <a href="<?= BASE_URL ?>admin/usuarios/view/<?= $user['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Visualizar">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>admin/usuarios/edit/<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($user['id'] != $_SESSION['user']['id']): ?>
                                <button type="button" class="btn btn-sm btn-outline-<?= $user['is_active'] ? 'warning' : 'success' ?>" title="<?= $user['is_active'] ? 'Desativar' : 'Ativar' ?>" data-bs-toggle="modal" data-bs-target="#statusModal" data-user-id="<?= $user['id'] ?>" data-user-name="<?= htmlspecialchars($user['name']) ?>" data-user-status="<?= $user['is_active'] ? '1' : '0' ?>">
                                    <i class="bi bi-<?= $user['is_active'] ? 'slash-circle' : 'check-circle' ?>"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" title="Excluir" data-bs-toggle="modal" data-bs-target="#deleteModal" data-user-id="<?= $user['id'] ?>" data-user-name="<?= htmlspecialchars($user['name']) ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if (!empty($pagination) && $pagination['lastPage'] > 1): ?>
    <div class="card-footer bg-white border-0 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                Mostrando <?= $pagination['from'] ?> a <?= $pagination['to'] ?> de <?= $pagination['total'] ?> registros
            </div>
            <nav aria-label="Paginação">
                <ul class="pagination pagination-sm mb-0">
                    <!-- Previous Page -->
                    <?php if ($pagination['currentPage'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= BASE_URL ?>admin/usuarios?page=<?= $pagination['currentPage'] - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $role ? '&role=' . urlencode($role) : '' ?>" aria-label="Anterior">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="page-item disabled">
                        <a class="page-link" href="#" aria-label="Anterior">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Page Numbers -->
                    <?php
                    $startPage = max(1, $pagination['currentPage'] - 2);
                    $endPage = min($pagination['lastPage'], $pagination['currentPage'] + 2);
                    
                    if ($startPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= BASE_URL ?>admin/usuarios?page=1<?= $search ? '&search=' . urlencode($search) : '' ?><?= $role ? '&role=' . urlencode($role) : '' ?>">1</a>
                    </li>
                    <?php if ($startPage > 2): ?>
                    <li class="page-item disabled">
                        <a class="page-link" href="#">...</a>
                    </li>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?= $i == $pagination['currentPage'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= BASE_URL ?>admin/usuarios?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $role ? '&role=' . urlencode($role) : '' ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $pagination['lastPage']): ?>
                    <?php if ($endPage < $pagination['lastPage'] - 1): ?>
                    <li class="page-item disabled">
                        <a class="page-link" href="#">...</a>
                    </li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= BASE_URL ?>admin/usuarios?page=<?= $pagination['lastPage'] ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $role ? '&role=' . urlencode($role) : '' ?>"><?= $pagination['lastPage'] ?></a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Next Page -->
                    <?php if ($pagination['currentPage'] < $pagination['lastPage']): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= BASE_URL ?>admin/usuarios?page=<?= $pagination['currentPage'] + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $role ? '&role=' . urlencode($role) : '' ?>" aria-label="Próximo">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="page-item disabled">
                        <a class="page-link" href="#" aria-label="Próximo">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
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
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: #6c757d;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}
</style>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>