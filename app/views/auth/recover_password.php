<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm rounded">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">Recuperar Senha</h4>
                </div>
                
                <div class="card-body">
                    <?php if (isset($message)): ?>
                        <div class="alert alert-<?= $success ? 'success' : 'danger' ?>">
                            <?= $message ?>
                        </div>
                    <?php else: ?>
                        <p class="card-text mb-4">Informe o seu e-mail para receber instruções de recuperação de senha.</p>
                        
                        <?php if (isset($errors['recovery'])): ?>
                            <div class="alert alert-danger">
                                <?= $errors['recovery'] ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="<?= BASE_URL ?>recuperar-senha" method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                       id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback">
                                        <?= $errors['email'] ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Recuperar Senha</button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <div class="mt-3 text-center">
                        <p><a href="<?= BASE_URL ?>login">Voltar para o login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>