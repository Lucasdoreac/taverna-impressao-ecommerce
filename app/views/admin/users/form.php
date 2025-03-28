<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<?php
// Determinar se é edição ou criação
$isEdit = isset($user);
$pageTitle = $isEdit ? 'Editar Usuário' : 'Novo Usuário';
?>

<!-- Page Title and Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= $pageTitle ?></h1>
    <a href="<?= $isEdit ? BASE_URL . 'admin/usuarios/view/' . $user['id'] : BASE_URL . 'admin/usuarios' ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Voltar
    </a>
</div>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><?= $isEdit ? 'Dados do Usuário' : 'Dados do Novo Usuário' ?></h5>
            </div>
            <div class="card-body">
                <form action="<?= BASE_URL ?>admin/usuarios/save" method="post" class="needs-validation" novalidate>
                    <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                    <?php endif; ?>
                    
                    <!-- Personal Information -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">Informações Pessoais</h6>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= $isEdit ? htmlspecialchars($user['name']) : '' ?>" required>
                            <div class="invalid-feedback">
                                Por favor, informe o nome completo.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= $isEdit ? htmlspecialchars($user['email']) : '' ?>" required>
                            <div class="invalid-feedback">
                                Por favor, informe um e-mail válido.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= $isEdit && isset($user['phone']) ? htmlspecialchars($user['phone']) : '' ?>">
                            <div class="form-text">
                                Formato: (00) 00000-0000
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Tipo de Usuário <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Selecione...</option>
                                <option value="customer" <?= $isEdit && $user['role'] == 'customer' ? 'selected' : '' ?>>Cliente</option>
                                <option value="admin" <?= $isEdit && $user['role'] == 'admin' ? 'selected' : '' ?>>Administrador</option>
                            </select>
                            <div class="invalid-feedback">
                                Por favor, selecione o tipo de usuário.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Password -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">Senha</h6>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">
                                <?= $isEdit ? 'Nova Senha <small class="text-muted">(deixe em branco para manter a atual)</small>' : 'Senha <span class="text-danger">*</span>' ?>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" <?= $isEdit ? '' : 'required' ?>>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                <?= $isEdit ? 'A senha deve ter pelo menos 6 caracteres.' : 'Por favor, informe uma senha.' ?>
                            </div>
                            <?php if (!$isEdit): ?>
                            <div class="form-text">
                                A senha deve ter pelo menos 6 caracteres.
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">
                                <?= $isEdit ? 'Confirmar Nova Senha' : 'Confirmar Senha <span class="text-danger">*</span>' ?>
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" <?= $isEdit ? '' : 'required' ?>>
                            <div class="invalid-feedback">
                                As senhas não conferem.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">Status</h6>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?= $isEdit && isset($user['is_active']) && $user['is_active'] ? 'checked' : (!$isEdit ? 'checked' : '') ?>>
                                <label class="form-check-label" for="is_active">Usuário Ativo</label>
                            </div>
                            <div class="form-text">
                                Usuários inativos não podem fazer login no sistema.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= $isEdit ? BASE_URL . 'admin/usuarios/view/' . $user['id'] : BASE_URL . 'admin/usuarios' ?>" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <?= $isEdit ? 'Atualizar Usuário' : 'Criar Usuário' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form Validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        } else {
            // Check if passwords match when provided
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password.value || confirmPassword.value) {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('As senhas não conferem');
                    event.preventDefault();
                    event.stopPropagation();
                } else {
                    confirmPassword.setCustomValidity('');
                }
                
                // Check minimum password length
                if (password.value && password.value.length < 6) {
                    password.setCustomValidity('A senha deve ter pelo menos 6 caracteres');
                    event.preventDefault();
                    event.stopPropagation();
                } else {
                    password.setCustomValidity('');
                }
            }
        }
        
        form.classList.add('was-validated');
    });
    
    // Toggle Password Visibility
    const togglePasswordButton = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    togglePasswordButton.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle eye icon
        const eyeIcon = this.querySelector('i');
        eyeIcon.classList.toggle('bi-eye');
        eyeIcon.classList.toggle('bi-eye-slash');
    });
    
    // Mask for phone input
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length <= 11) {
                // Format as (00) 00000-0000
                if (value.length > 2) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2);
                }
                if (value.length > 10) {
                    value = value.substring(0, 10) + '-' + value.substring(10);
                }
            }
            
            e.target.value = value;
        });
    }
});
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>