<?php
/**
 * View para criação de novas notificações push
 * 
 * Esta view implementa formulário seguro para envio de notificações push
 * para usuários do sistema, com validação CSRF e sanitização adequada.
 * 
 * @package Taverna\Admin\Dashboard
 * @author Claude
 * @version 1.0.0
 */

// Incluir header
include_once APP_PATH . '/views/admin/partials/header.php';
include_once APP_PATH . '/views/admin/partials/sidebar.php';

// Obter token CSRF
$csrfToken = SecurityManager::getCsrfToken();

// Valores padrão ou do formulário preenchido em caso de erro
$title = $formData['title'] ?? '';
$message = $formData['message'] ?? '';
$type = $formData['type'] ?? 'info';
$userRoles = $formData['user_roles'] ?? ['customer'];

// Mensagens de erro de validação
$errors = $errors ?? [];
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><?= htmlspecialchars($title) ?></h1>
        <div class="dashboard-actions">
            <a href="<?= BASE_URL ?>admin/dashboard/notifications" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Card do formulário -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Enviar Nova Notificação</h5>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>admin/dashboard/createNotification" method="post" id="notificationForm">
                <!-- Token CSRF -->
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <!-- Título da notificação -->
                <div class="mb-3">
                    <label for="title" class="form-label">Título da Notificação <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" 
                           id="title" name="title" value="<?= htmlspecialchars($title) ?>" 
                           maxlength="255" required>
                    <?php if (isset($errors['title'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars($errors['title']) ?>
                        </div>
                    <?php endif; ?>
                    <div class="form-text">Máximo 255 caracteres</div>
                </div>
                
                <!-- Tipo de notificação -->
                <div class="mb-3">
                    <label for="type" class="form-label">Tipo de Notificação <span class="text-danger">*</span></label>
                    <select class="form-select <?= isset($errors['type']) ? 'is-invalid' : '' ?>" 
                            id="type" name="type" required>
                        <option value="info" <?= $type === 'info' ? 'selected' : '' ?>>Informação</option>
                        <option value="success" <?= $type === 'success' ? 'selected' : '' ?>>Sucesso</option>
                        <option value="warning" <?= $type === 'warning' ? 'selected' : '' ?>>Alerta</option>
                        <option value="error" <?= $type === 'error' ? 'selected' : '' ?>>Erro</option>
                    </select>
                    <?php if (isset($errors['type'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars($errors['type']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Papéis de usuário -->
                <div class="mb-3">
                    <label class="form-label">Destinatários <span class="text-danger">*</span></label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="customer" 
                               id="role_customer" name="user_roles[]"
                               <?= in_array('customer', $userRoles) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="role_customer">
                            Clientes
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="printer_operator" 
                               id="role_printer_operator" name="user_roles[]"
                               <?= in_array('printer_operator', $userRoles) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="role_printer_operator">
                            Operadores de Impressora
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="manager" 
                               id="role_manager" name="user_roles[]"
                               <?= in_array('manager', $userRoles) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="role_manager">
                            Gerentes
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="admin" 
                               id="role_admin" name="user_roles[]"
                               <?= in_array('admin', $userRoles) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="role_admin">
                            Administradores
                        </label>
                    </div>
                    <?php if (isset($errors['user_roles'])): ?>
                        <div class="text-danger mt-1">
                            <?= htmlspecialchars($errors['user_roles']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Mensagem da notificação -->
                <div class="mb-3">
                    <label for="message" class="form-label">Mensagem da Notificação <span class="text-danger">*</span></label>
                    <textarea class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>" 
                              id="message" name="message" rows="6" required><?= htmlspecialchars($message) ?></textarea>
                    <?php if (isset($errors['message'])): ?>
                        <div class="invalid-feedback">
                            <?= htmlspecialchars($errors['message']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Botão de envio -->
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-paper-plane"></i> Enviar Notificação
                    </button>
                    <a href="<?= BASE_URL ?>admin/dashboard/notifications" class="btn btn-secondary ms-2">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Informações adicionais -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Informações sobre Notificações</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                <h6 class="alert-heading"><i class="fa fa-info-circle me-2"></i>Sobre as Notificações Push</h6>
                <p class="mb-0">As notificações serão entregues através dos seguintes canais:</p>
                <ul>
                    <li><strong>Banco de Dados:</strong> Todas as notificações são armazenadas no sistema e exibidas na área de notificações dos usuários.</li>
                    <li><strong>Push:</strong> Notificações push para usuários que habilitaram esta opção em seus navegadores.</li>
                    <li><strong>Email:</strong> Notificações importantes como erros e sucessos também são enviadas por email.</li>
                </ul>
                <p>As notificações expiram automaticamente após 30 dias, mas continuam disponíveis no histórico do usuário.</p>
                <p class="mb-0"><strong>Nota:</strong> Os usuários podem desativar notificações push a qualquer momento em suas configurações.</p>
            </div>
        </div>
    </div>
</div>

<script>
// Validação do lado do cliente
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('notificationForm');
    
    form.addEventListener('submit', function(event) {
        let isValid = true;
        
        // Validar título
        const title = document.getElementById('title').value.trim();
        if (!title) {
            document.getElementById('title').classList.add('is-invalid');
            isValid = false;
        } else {
            document.getElementById('title').classList.remove('is-invalid');
        }
        
        // Validar mensagem
        const message = document.getElementById('message').value.trim();
        if (!message) {
            document.getElementById('message').classList.add('is-invalid');
            isValid = false;
        } else {
            document.getElementById('message').classList.remove('is-invalid');
        }
        
        // Validar que pelo menos um papel de usuário foi selecionado
        const roleCheckboxes = document.querySelectorAll('input[name="user_roles[]"]:checked');
        if (roleCheckboxes.length === 0) {
            document.querySelector('input[name="user_roles[]"]').closest('.mb-3').insertAdjacentHTML(
                'beforeend',
                '<div class="text-danger mt-1">Selecione pelo menos um grupo de destinatários</div>'
            );
            isValid = false;
        }
        
        if (!isValid) {
            event.preventDefault();
        }
    });
    
    // Visualizar notificação (atualização em tempo real)
    const titleInput = document.getElementById('title');
    const messageInput = document.getElementById('message');
    const typeSelect = document.getElementById('type');
    
    function updatePreview() {
        // Aqui poderíamos implementar uma visualização em tempo real da notificação
        // Por enquanto, deixamos apenas a validação
    }
    
    titleInput.addEventListener('input', updatePreview);
    messageInput.addEventListener('input', updatePreview);
    typeSelect.addEventListener('change', updatePreview);
});
</script>

<style>
.dashboard-container {
    padding: 20px;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

/* Ajustes para telas menores */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .dashboard-actions {
        margin-top: 15px;
        width: 100%;
    }
    
    .dashboard-actions .btn {
        width: 100%;
    }
}
</style>

<?php
// Incluir footer
include_once APP_PATH . '/views/admin/partials/footer.php';
?>
