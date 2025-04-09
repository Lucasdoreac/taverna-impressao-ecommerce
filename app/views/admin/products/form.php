<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<?php
$isEdit = !empty($product['id']);
$pageTitle = $isEdit ? 'Editar Produto' : 'Novo Produto';
$formAction = BASE_URL . 'admin/produtos/save';

// Obter mensagens de erro da sessão
$errors = $_SESSION['form_errors'] ?? [];
$formData = $_SESSION['form_data'] ?? $product;

// Limpar dados da sessão
unset($_SESSION['form_errors']);
unset($_SESSION['form_data']);
?>

<!-- Page Title and Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= $pageTitle ?></h1>
    <div>
        <a href="<?= BASE_URL ?>admin/produtos" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
        <?php if ($isEdit): ?>
        <a href="<?= BASE_URL ?>admin/produtos/view/<?= $product['id'] ?>" class="btn btn-info">
            <i class="bi bi-eye me-1"></i> Visualizar
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Product Form -->
<form action="<?= $formAction ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
    <?php echo CsrfProtection::getFormField(); ?>
    <input type="hidden" name="id" value="<?= $isEdit ? $product['id'] : '' ?>">
    
    <div class="row">
        <!-- Main Form Column -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Informações Básicas</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome do Produto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= $formData['name'] ?? '' ?>" required autofocus>
                        <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback"><?= $errors['name'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" class="form-control <?= isset($errors['slug']) ? 'is-invalid' : '' ?>" id="slug" name="slug" value="<?= $formData['slug'] ?? '' ?>">
                            <div class="form-text">Deixe em branco para gerar automaticamente.</div>
                            <?php if (isset($errors['slug'])): ?>
                            <div class="invalid-feedback"><?= $errors['slug'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="sku" class="form-label">SKU</label>
                            <input type="text" class="form-control" id="sku" name="sku" value="<?= $formData['sku'] ?? '' ?>">
                            <div class="form-text">Código de referência do produto (opcional).</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Categoria <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" id="category_id" name="category_id" required>
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= (isset($formData['category_id']) && $formData['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                    <?= $category['name'] ?>
                                </option>
                                <?php if (!empty($category['subcategories'])): ?>
                                    <?php foreach ($category['subcategories'] as $subcategory): ?>
                                        <option value="<?= $subcategory['id'] ?>" <?= (isset($formData['category_id']) && $formData['category_id'] == $subcategory['id']) ? 'selected' : '' ?>>
                                            &nbsp;&nbsp;└ <?= $subcategory['name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['category_id'])): ?>
                        <div class="invalid-feedback"><?= $errors['category_id'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="short_description" class="form-label">Descrição Curta</label>
                        <input type="text" class="form-control" id="short_description" name="short_description" value="<?= $formData['short_description'] ?? '' ?>">
                        <div class="form-text">Uma breve descrição para exibição em listagens (máx. 255 caracteres).</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição Completa</label>
                        <textarea class="form-control" id="description" name="description" rows="5"><?= $formData['description'] ?? '' ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Precificação e Estoque</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="price" class="form-label">Preço <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>" id="price" name="price" value="<?= $formData['price'] ?? '0.00' ?>" step="0.01" min="0" required>
                            </div>
                            <?php if (isset($errors['price'])): ?>
                            <div class="invalid-feedback"><?= $errors['price'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="sale_price" class="form-label">Preço Promocional</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" class="form-control <?= isset($errors['sale_price']) ? 'is-invalid' : '' ?>" id="sale_price" name="sale_price" value="<?= $formData['sale_price'] ?? '' ?>" step="0.01" min="0">
                            </div>
                            <div class="form-text">Deixe em branco se não houver promoção.</div>
                            <?php if (isset($errors['sale_price'])): ?>
                            <div class="invalid-feedback"><?= $errors['sale_price'] ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="stock" class="form-label">Estoque</label>
                            <input type="number" class="form-control" id="stock" name="stock" value="<?= $formData['stock'] ?? '0' ?>" min="0">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="weight" class="form-label">Peso (kg)</label>
                            <input type="number" class="form-control" id="weight" name="weight" value="<?= $formData['weight'] ?? '0' ?>" step="0.01" min="0">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="dimensions" class="form-label">Dimensões (cm)</label>
                            <input type="text" class="form-control" id="dimensions" name="dimensions" value="<?= $formData['dimensions'] ?? '' ?>" placeholder="Ex: 30x20x10">
                            <div class="form-text">Formato: comprimento x largura x altura</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Imagens do Produto</h5>
                    <?php if ($isEdit && !empty($product['images'])): ?>
                    <span class="badge bg-primary"><?= count($product['images']) ?> imagens</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="images" class="form-label">Adicionar Imagens</label>
                        <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                        <div class="form-text">Você pode selecionar múltiplas imagens. Formatos aceitos: JPG, PNG.</div>
                    </div>
                    
                    <?php if ($isEdit && !empty($product['images'])): ?>
                    <div class="mt-4">
                        <h6>Imagens Atuais</h6>
                        <div class="row g-3">
                            <?php foreach ($product['images'] as $image): ?>
                            <div class="col-md-3 col-sm-4 col-6">
                                <div class="card">
                                    <img src="<?= BASE_URL ?>uploads/products/<?= $image['image'] ?>" class="card-img-top" alt="Imagem do produto">
                                    <div class="card-body p-2 text-center">
                                        <div class="btn-group btn-group-sm">
                                            <?php if (!$image['is_main']): ?>
                                            <a href="<?= BASE_URL ?>admin/produtos/set-main-image/<?= $product['id'] ?>/<?= $image['id'] ?>" class="btn btn-outline-primary" title="Definir como principal">
                                                <i class="bi bi-star"></i>
                                            </a>
                                            <?php else: ?>
                                            <button type="button" class="btn btn-primary" disabled title="Imagem principal">
                                                <i class="bi bi-star-fill"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <a href="<?= BASE_URL ?>admin/produtos/delete-image/<?= $product['id'] ?>/<?= $image['id'] ?>" class="btn btn-outline-danger btn-delete" title="Excluir imagem">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm mb-4" id="customization-section" style="<?= isset($formData['is_customizable']) && $formData['is_customizable'] ? '' : 'display: none;' ?>">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Opções de Personalização</h5>
                    <button type="button" id="add-customization" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-circle me-1"></i> Adicionar Opção
                    </button>
                </div>
                <div class="card-body">
                    <div id="customization-options">
                        <?php if ($isEdit && !empty($product['customization_options'])): ?>
                            <?php foreach ($product['customization_options'] as $index => $option): ?>
                            <div class="customization-option">
                                <div class="customization-option-header">
                                    <h6><?= $option['name'] ?></h6>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-customization">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nome</label>
                                        <input type="text" class="form-control" name="customization[<?= $index ?>][name]" value="<?= $option['name'] ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tipo</label>
                                        <select class="form-select" name="customization[<?= $index ?>][type]" required>
                                            <option value="text" <?= $option['type'] === 'text' ? 'selected' : '' ?>>Texto</option>
                                            <option value="select" <?= $option['type'] === 'select' ? 'selected' : '' ?>>Seleção</option>
                                            <option value="upload" <?= $option['type'] === 'upload' ? 'selected' : '' ?>>Upload</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Descrição</label>
                                    <textarea class="form-control" name="customization[<?= $index ?>][description]" rows="2"><?= $option['description'] ?></textarea>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="customization[<?= $index ?>][required]" value="1" id="required_<?= $index ?>" <?= $option['required'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="required_<?= $index ?>">
                                                Obrigatório
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="option-fields mb-3" style="<?= $option['type'] === 'select' ? '' : 'display: none;' ?>">
                                    <label class="form-label">Opções (uma por linha no formato "valor=texto")</label>
                                    <textarea class="form-control" name="customization[<?= $index ?>][options]" rows="3"><?= $option['options'] ?></textarea>
                                    <div class="form-text">Exemplo: 1=Azul, 2=Vermelho, 3=Verde</div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="alert alert-info mt-3" id="no-customization-options" style="<?= ($isEdit && !empty($product['customization_options'])) ? 'display: none;' : '' ?>">
                        <i class="bi bi-info-circle me-2"></i> Nenhuma opção de personalização definida. Clique no botão "Adicionar Opção" para começar.
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Column -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Publicação</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= (isset($formData['is_active']) && $formData['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Produto Ativo</label>
                        <div class="form-text">Desative para ocultar o produto na loja.</div>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" <?= (isset($formData['is_featured']) && $formData['is_featured']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_featured">Produto em Destaque</label>
                        <div class="form-text">Exibe o produto na seção de destaques.</div>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_customizable" name="is_customizable" value="1" <?= (isset($formData['is_customizable']) && $formData['is_customizable']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_customizable">Produto Personalizável</label>
                        <div class="form-text">Permite que o cliente personalize o produto antes da compra.</div>
                    </div>
                    
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save me-1"></i> Salvar Produto
                        </button>
                        <a href="<?= BASE_URL ?>admin/produtos" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Dicas</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Adicione múltiplas imagens para melhorar a visualização do produto.
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Use uma descrição completa e detalhada para SEO.
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Defina a primeira imagem como a imagem principal.
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Configure as opções de personalização se o produto for customizável.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</form>

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
    
    // Mostrar/ocultar seção de personalização
    const isCustomizableCheckbox = document.getElementById('is_customizable');
    const customizationSection = document.getElementById('customization-section');
    
    if (isCustomizableCheckbox && customizationSection) {
        isCustomizableCheckbox.addEventListener('change', function() {
            customizationSection.style.display = this.checked ? 'block' : 'none';
        });
    }
    
    // Adicionar opção de personalização
    const addCustomizationButton = document.getElementById('add-customization');
    const customizationOptions = document.getElementById('customization-options');
    const noCustomizationOptions = document.getElementById('no-customization-options');
    
    if (addCustomizationButton && customizationOptions) {
        addCustomizationButton.addEventListener('click', function() {
            const index = document.querySelectorAll('.customization-option').length;
            
            const template = `
                <div class="customization-option">
                    <div class="customization-option-header">
                        <h6>Nova Opção</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-customization">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" name="customization[${index}][name]" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="customization[${index}][type]" required>
                                <option value="text">Texto</option>
                                <option value="select">Seleção</option>
                                <option value="upload">Upload</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="customization[${index}][description]" rows="2"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="customization[${index}][required]" value="1" id="required_${index}">
                                <label class="form-check-label" for="required_${index}">
                                    Obrigatório
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="option-fields mb-3" style="display: none;">
                        <label class="form-label">Opções (uma por linha no formato "valor=texto")</label>
                        <textarea class="form-control" name="customization[${index}][options]" rows="3"></textarea>
                        <div class="form-text">Exemplo: 1=Azul, 2=Vermelho, 3=Verde</div>
                    </div>
                </div>
            `;
            
            customizationOptions.insertAdjacentHTML('beforeend', template);
            
            if (noCustomizationOptions) {
                noCustomizationOptions.style.display = 'none';
            }
            
            // Inicializar eventos para a nova opção
            initCustomizationOption(customizationOptions.lastElementChild);
        });
    }
    
    // Inicializar eventos para opções existentes
    function initCustomizationOption(option) {
        // Remover opção
        const removeButton = option.querySelector('.remove-customization');
        if (removeButton) {
            removeButton.addEventListener('click', function() {
                option.remove();
                
                // Mostrar mensagem se não houver opções
                if (document.querySelectorAll('.customization-option').length === 0 && noCustomizationOptions) {
                    noCustomizationOptions.style.display = 'block';
                }
            });
        }
        
        // Mostrar/ocultar campos de opções
        const typeSelect = option.querySelector('select');
        if (typeSelect) {
            typeSelect.addEventListener('change', function() {
                const optionFields = option.querySelector('.option-fields');
                if (optionFields) {
                    optionFields.style.display = this.value === 'select' ? 'block' : 'none';
                }
            });
        }
    }
    
    // Inicializar opções existentes
    document.querySelectorAll('.customization-option').forEach(function(option) {
        initCustomizationOption(option);
    });
});
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>