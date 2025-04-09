<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="mb-0">Recuperação de Senha</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($success) && $success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo h($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                        <div class="text-center mt-4">
                            <a href="<?php echo BASE_URL; ?>login" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-1"></i> Voltar para Login
                            </a>
                        </div>
                    <?php else: ?>
                        <?php if (isset($error) && $error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo h($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> 
                            Informe seu e-mail de cadastro para receber instruções de recuperação de senha.
                        </div>
                        
                        <form action="<?php echo BASE_URL; ?>recuperar-senha/enviar" method="post" class="mt-4">
                            <?php echo $csrfToken; ?>
                            
                            <div class="mb-4">
                                <label for="email" class="form-label">E-mail</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="seu-email@exemplo.com" required>
                                </div>
                                <div class="form-text">
                                    Digite o e-mail que você usou para criar sua conta.
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i> Enviar Instruções
                                </button>
                            </div>
                        </form>
                        
                        <div class="mt-4 text-center">
                            <p>Lembrou sua senha? <a href="<?php echo BASE_URL; ?>login">Voltar para Login</a></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center bg-light py-3">
                    <a href="<?php echo BASE_URL; ?>" class="text-decoration-none">
                        <i class="fas fa-home me-1"></i> Voltar para a Loja
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validação do formulário
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(event) {
            const email = document.getElementById('email');
            
            // Validar formato de e-mail
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value)) {
                event.preventDefault();
                alert('Por favor, informe um e-mail válido');
                email.focus();
                return;
            }
        });
    }
});
</script>