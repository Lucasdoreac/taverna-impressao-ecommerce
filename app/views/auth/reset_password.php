<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm rounded">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">Redefinir Senha</h4>
                </div>
                
                <div class="card-body">
                    <?php if (isset($errors['reset'])): ?>
                        <div class="alert alert-danger">
                            <?= $errors['reset'] ?>
                        </div>
                    <?php endif; ?>
                    
                    <p class="card-text mb-4">Digite sua nova senha abaixo.</p>
                    
                    <form action="<?= BASE_URL ?>recuperar-senha?token=<?= htmlspecialchars($token) ?>" method="post">
                        <div class="mb-3">
                            <label for="password" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                   id="password" name="password" required>
                            <div class="form-text">A senha deve ter pelo menos 6 caracteres.</div>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?= $errors['password'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                                   id="confirm_password" name="confirm_password" required>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback">
                                    <?= $errors['confirm_password'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Redefinir Senha</button>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <p><a href="<?= BASE_URL ?>login">Voltar para o login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>