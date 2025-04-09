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
                    <a href="<?php echo BASE_URL; ?>minha-conta/perfil" class="list-group-item list-group-item-action active">
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Editar Perfil</h4>
                    <a href="<?php echo BASE_URL; ?>minha-conta" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Voltar
                    </a>
                </div>
                <div class="card-body">
                    <form action="<?php echo BASE_URL; ?>minha-conta/perfil" method="post">
                        <?php echo $csrfToken; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo h($user['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo h($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Telefone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo h($user['phone'] ?? ''); ?>">
                                <small class="text-muted">Formato: (00) 00000-0000</small>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        <h5>Alterar Senha</h5>
                        <p class="text-muted small">Preencha os campos abaixo apenas se desejar alterar sua senha</p>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                                <small class="text-muted">Mínimo de 6 caracteres</small>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> Para confirmar as alterações, é necessário informar sua senha atual.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="current_password" class="form-label">Senha Atual</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-outline-secondary">Cancelar</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validação do formulário
    const form = document.querySelector('form');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    form.addEventListener('submit', function(event) {
        // Verificar se as senhas conferem
        if (newPassword.value && newPassword.value !== confirmPassword.value) {
            event.preventDefault();
            alert('As senhas não conferem');
            confirmPassword.focus();
        }
    });
    
    // Máscara para o telefone
    const phone = document.getElementById('phone');
    if (phone) {
        phone.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 2) {
                value = '(' + value.slice(0, 2) + ') ' + value.slice(2);
            }
            if (value.length > 10) {
                value = value.slice(0, 10) + '-' + value.slice(10);
            }
            
            e.target.value = value;
        });
    }
});
</script>