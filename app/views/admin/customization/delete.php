<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Confirmar Exclusão</h1>
        <a href="<?= BASE_URL ?>admin/customization" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Exclusão de Opção de Personalização</h6>
        </div>
        <div class="card-body">
            <div class="text-center mb-4">
                <i class="bi bi-exclamation-triangle text-warning display-1"></i>
                <h4 class="mt-3">Tem certeza que deseja excluir esta opção de personalização?</h4>
                <p class="text-muted">Esta ação não poderá ser desfeita.</p>
            </div>
            
            <?php
            // Verificar se há conflitos (pedidos ou itens de carrinho usando esta opção)
            $customizationModel = new CustomizationModel();
            $hasConflicts = $customizationModel->hasConflicts($option['id']);
            
            if ($hasConflicts):
            ?>
            <div class="alert alert-danger mb-4">
                <h5 class="alert-heading"><i class="bi bi-x-circle me-2"></i> Exclusão Não Recomendada</h5>
                <p>Esta opção de personalização está sendo usada em pedidos ou carrinhos ativos.</p>
                <p class="mb-0">Excluí-la pode causar problemas em pedidos existentes e na visualização de personalizações em pedidos anteriores.</p>
            </div>
            <?php endif; ?>
            
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <tr>
                        <th class="bg-light" width="30%">ID</th>
                        <td><?= $option['id'] ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Nome</th>
                        <td><?= $option['name'] ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Produto</th>
                        <td><?= $option['product_name'] ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Tipo</th>
                        <td>
                            <?php if ($option['type'] === 'upload'): ?>
                                <span class="badge bg-info">Upload de Arquivo</span>
                            <?php elseif ($option['type'] === 'text'): ?>
                                <span class="badge bg-secondary">Campo de Texto</span>
                            <?php elseif ($option['type'] === 'select'): ?>
                                <span class="badge bg-primary">Seleção de Opções</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <form method="post" action="<?= BASE_URL ?>admin/customization/delete/<?= $option['id'] ?>" class="text-center">
                <!-- Token CSRF -->
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?= BASE_URL ?>admin/customization" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Confirmar Exclusão
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
