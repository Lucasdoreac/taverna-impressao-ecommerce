<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<?php
$isEdit = !empty($category['id']);
$pageTitle = $isEdit ? 'Editar Categoria' : 'Nova Categoria';
$formAction = BASE_URL . 'admin/categorias/save';

// Obter mensagens de erro da sessão
$errors = $_SESSION['form_errors'] ?? [];
$formData = $_SESSION['form_data'] ?? $category;

// Limpar dados da sessão
unset($_SESSION['form_errors']);
unset($_SESSION['form_data']);
?>

<!-- Page Title and Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= $pageTitle ?></h1>
    <a href="<?= BASE_URL ?>admin/categorias" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Voltar
    </a>
</div>

<!-- Category Form -->
<div class="row">
    <div class="col-lg-8">
        <form action="<?= $formAction ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
            <input type="hidden" name="id" value="<?= $isEdit ? $category['id'] : '' ?>">
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Informações da Categoria</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome da Categoria <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= $formData['name'] ?? '' ?>" required autofocus>
                        <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback"><?= $errors['name'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control <?= isset($errors['slug']) ? 'is-invalid' : '' ?>" id="slug" name="slug" value="<?= $formData['slug'] ?? '' ?>">
                        <div class="form-text">Deixe em branco para gerar automaticamente. O slug é usado na URL da categoria.</div>
                        <?php if (isset($errors['slug'])): ?>
                        <div class="invalid-feedback"><?= $errors['slug'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Categoria Pai</label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="">Nenhuma (Categoria Principal)</option>
                            <?php foreach ($parentCategories as $parentCategory): ?>
                                <?php if ($isEdit && in_array($parentCategory['id'], $excludeIds ?? [])) continue; ?>
                                <option value="<?= $parentCategory['id'] ?>" <?= (isset($formData['parent_id']) && $formData['parent_id'] == $parentCategory['id']) ? 'selected' : '' ?>>
                                    <?= $parentCategory['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Selecione a categoria pai, ou deixe em branco para uma categoria de nível superior.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= $formData['description'] ?? '' ?></textarea>
                        <div class="form-text">Uma breve descrição sobre a categoria.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="display_order" class="form-label">Ordem de Exibição</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" value="<?= $formData['display_order'] ?? '0' ?>" min="0">
                        <div class="form-text">Define a ordem de exibição na loja. Categorias com valores menores aparecem primeiro.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Imagem da Categoria</label>
                        <?php if ($isEdit && !empty($category['image'])): ?>
                        <div class="mb-2">
                            <img src="<?= BASE_URL ?>uploads/categories/<?= $category['image'] ?>" alt="Imagem atual" class="img-thumbnail" style="max-height: 150px;">
                        </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="form-text">Imagem que representa a categoria. Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB.</div>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= (!$isEdit || (isset($formData['is_active']) && $formData['is_active'])) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Categoria Ativa</label>
                        <div class="form-text">Desative para ocultar a categoria e seus produtos na loja.</div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Salvar Categoria
                    </button>
                    <a href="<?= BASE_URL ?>admin/categorias" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Dicas</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-3">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        <strong>Hierarquia de Categorias:</strong>
                        <p class="text-muted small mt-1">Você pode criar uma estrutura hierárquica de categorias. Categorias de nível superior não possuem uma categoria pai, enquanto subcategorias são vinculadas a uma categoria principal.</p>
                    </li>
                    <li class="mb-3">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        <strong>Slugs:</strong>
                        <p class="text-muted small mt-1">O slug é usado para criar URLs amigáveis. Se não for fornecido, será gerado automaticamente a partir do nome da categoria.</p>
                    </li>
                    <li class="mb-3">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        <strong>Imagens:</strong>
                        <p class="text-muted small mt-1">As imagens das categorias são usadas em banners e listagens de categorias. Prefira imagens com proporção 16:9 ou 4:3 para melhor exibição.</p>
                    </li>
                    <li>
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        <strong>Ordem de Exibição:</strong>
                        <p class="text-muted small mt-1">A ordem de exibição afeta como as categorias são ordenadas no menu principal e nas listagens. Categorias com valores menores aparecem primeiro.</p>
                    </li>
                </ul>
            </div>
        </div>

        <?php if ($isEdit): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Ações</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>admin/produtos?category_id=<?= $category['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-grid me-2"></i> Ver Produtos</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                    <a href="<?= BASE_URL ?>admin/categorias/delete/<?= $category['id'] ?>" class="list-group-item list-group-item-action list-group-item-danger btn-delete">
                        <i class="bi bi-trash me-2"></i> Excluir Categoria
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Script para slug automático
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    
    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            if (slugInput.value === '') {
                slugInput.value = generateSlug(nameInput.value);
            }
        });
    }
    
    function generateSlug(text) {
        return text
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '') // Remover acentos
            .toLowerCase()
            .trim()
            .replace(/\s+/g, '-') // Espaços para hífens
            .replace(/[^\w\-]+/g, '') // Remover caracteres não alfanuméricos
            .replace(/\-\-+/g, '-') // Remover múltiplos hífens
            .replace(/^-+/, '') // Remover hífens do início
            .replace(/-+$/, ''); // Remover hífens do final
    }
});
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
