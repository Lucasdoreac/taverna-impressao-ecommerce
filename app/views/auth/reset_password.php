<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm rounded">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">Redefinir Senha</h4>
                </div>
                
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?= $message ?>
                            <div class="mt-3 text-center">
                                <a href="<?= BASE_URL ?>login" class="btn btn-primary">Ir para o Login</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if (isset($errors['reset']) || isset($errors['token'])): ?>
                            <div class="alert alert-danger">
                                <?= $errors['reset'] ?? $errors['token'] ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!isset($errors['token'])): ?>
                            <p class="card-text mb-4">Digite sua nova senha abaixo.</p>
                            
                            <form action="<?= BASE_URL ?>recuperar-senha/redefinir?token=<?= htmlspecialchars($token) ?>" method="post">
                                <?= CsrfProtection::getFormField() ?>
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
                        <?php endif; ?>
                        
                        <div class="mt-3 text-center">
                            <p><a href="<?= BASE_URL ?>login">Voltar para o login</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>