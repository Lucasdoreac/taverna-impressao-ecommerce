<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<!-- Page Title and Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Detalhes do Produto</h1>
    <div>
        <a href="<?= BASE_URL ?>admin/produtos" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
        <a href="<?= BASE_URL ?>admin/produtos/edit/<?= $product['id'] ?>" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Editar
        </a>
        <a href="<?= BASE_URL ?>produto/<?= $product['slug'] ?>" target="_blank" class="btn btn-info">
            <i class="bi bi-eye me-1"></i> Ver na Loja
        </a>
    </div>
</div>

<div class="row">
    <!-- Main Column -->
    <div class="col-lg-8">
        <!-- Product Images -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Imagens do Produto</h5>
            </div>
            <div class="card-body">
                <?php if (empty($product['images'])): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i> Este produto não possui imagens.
                </div>
                <?php else: ?>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <?php 
                        // Find main image
                        $mainImage = array_filter($product['images'], function($img) {
                            return $img['is_main'] == 1;
                        });
                        $mainImage = !empty($mainImage) ? reset($mainImage) : $product['images'][0];
                        ?>
                        <img src="<?= BASE_URL ?>uploads/products/<?= $mainImage['image'] ?>" class="img-fluid rounded" alt="<?= $product['name'] ?>">
                    </div>
                    <div class="col-md-6">
                        <div class="row g-2">
                            <?php foreach ($product['images'] as $image): ?>
                            <?php if ($image['id'] != $mainImage['id']): ?>
                            <div class="col-4">
                                <img src="<?= BASE_URL ?>uploads/products/<?= $image['image'] ?>" class="img-fluid rounded" alt="<?= $product['name'] ?>">
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Product Details -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Informações Básicas</h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h2 class="h4 mb-1"><?= $product['name'] ?></h2>
                    <div class="text-muted small">
                        <span class="me-3"><i class="bi bi-tag me-1"></i> <?= $category['name'] ?></span>
                        <span><i class="bi bi-link-45deg me-1"></i> <?= $product['slug'] ?></span>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold">Preço</h6>
                        <?php if (!empty($product['sale_price'])): ?>
                        <div>
                            <span class="text-decoration-line-through text-muted me-2">
                                <?= AdminHelper::formatMoney($product['price']) ?>
                            </span>
                            <span class="text-danger fs-5 fw-bold">
                                <?= AdminHelper::formatMoney($product['sale_price']) ?>
                            </span>
                        </div>
                        <?php else: ?>
                        <div class="fs-5 fw-bold">
                            <?= AdminHelper::formatMoney($product['price']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="fw-bold">Estoque</h6>
                        <?php if ($product['stock'] <= 0): ?>
                        <span class="badge bg-danger fs-6">Esgotado</span>
                        <?php elseif ($product['stock'] <= 5): ?>
                        <span class="badge bg-warning fs-6"><?= $product['stock'] ?> unidades</span>
                        <?php else: ?>
                        <span class="badge bg-success fs-6"><?= $product['stock'] ?> unidades</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($product['short_description'])): ?>
                <div class="mb-4">
                    <h6 class="fw-bold">Descrição Curta</h6>
                    <p><?= $product['short_description'] ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($product['description'])): ?>
                <div class="mb-4">
                    <h6 class="fw-bold">Descrição Completa</h6>
                    <div class="bg-light p-3 rounded">
                        <?= nl2br($product['description']) ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6 class="fw-bold">SKU</h6>
                            <p><?= $product['sku'] ?: 'Não definido' ?></p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6 class="fw-bold">Peso</h6>
                            <p><?= $product['weight'] ? $product['weight'] . ' kg' : 'Não definido' ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6 class="fw-bold">Dimensões</h6>
                    <p><?= $product['dimensions'] ?: 'Não definido' ?></p>
                </div>
                
                <div class="mb-3">
                    <h6 class="fw-bold">Data de Criação</h6>
                    <p><?= AdminHelper::formatDateTime($product['created_at']) ?></p>
                </div>
                
                <?php if ($product['created_at'] != $product['updated_at']): ?>
                <div class="mb-3">
                    <h6 class="fw-bold">Última Atualização</h6>
                    <p><?= AdminHelper::formatDateTime($product['updated_at']) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Customization Options -->
        <?php if ($product['is_customizable'] && !empty($product['customization_options'])): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Opções de Personalização</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Obrigatório</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($product['customization_options'] as $option): ?>
                            <tr>
                                <td><?= $option['name'] ?></td>
                                <td>
                                    <?php switch ($option['type']):
                                        case 'text': ?>
                                            <span class="badge bg-secondary">Texto</span>
                                            <?php break; ?>
                                        <?php case 'select': ?>
                                            <span class="badge bg-primary">Seleção</span>
                                            <?php break; ?>
                                        <?php case 'upload': ?>
                                            <span class="badge bg-info">Upload</span>
                                            <?php break; ?>
                                    <?php endswitch; ?>
                                </td>
                                <td>
                                    <?php if ($option['required']): ?>
                                    <span class="badge bg-danger">Sim</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Não</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $option['description'] ?></td>
                            </tr>
                            <?php if ($option['type'] === 'select' && !empty($option['options'])): ?>
                            <tr>
                                <td colspan="4" class="bg-light">
                                    <strong>Opções:</strong> <?= $option['options'] ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar Column -->
    <div class="col-lg-4">
        <!-- Product Status -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Status do Produto</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>Status:</span>
                    <?php if ($product['is_active']): ?>
                    <span class="badge bg-success">Ativo</span>
                    <?php else: ?>
                    <span class="badge bg-danger">Inativo</span>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>Destaque:</span>
                    <?php if ($product['is_featured']): ?>
                    <span class="badge bg-primary">Sim</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Não</span>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <span>Personalizável:</span>
                    <?php if ($product['is_customizable']): ?>
                    <span class="badge bg-info">Sim</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Não</span>
                    <?php endif; ?>
                </div>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <?php if ($product['is_active']): ?>
                    <a href="<?= BASE_URL ?>admin/produtos/toggle-active/<?= $product['id'] ?>" class="btn btn-outline-danger">
                        <i class="bi bi-x-circle me-1"></i> Desativar Produto
                    </a>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>admin/produtos/toggle-active/<?= $product['id'] ?>" class="btn btn-outline-success">
                        <i class="bi bi-check-circle me-1"></i> Ativar Produto
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($product['is_featured']): ?>
                    <a href="<?= BASE_URL ?>admin/produtos/toggle-featured/<?= $product['id'] ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-star me-1"></i> Remover Destaque
                    </a>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>admin/produtos/toggle-featured/<?= $product['id'] ?>" class="btn btn-outline-primary">
                        <i class="bi bi-star-fill me-1"></i> Destacar Produto
                    </a>
                    <?php endif; ?>
                    
                    <a href="<?= BASE_URL ?>admin/produtos/delete/<?= $product['id'] ?>" class="btn btn-danger btn-delete">
                        <i class="bi bi-trash me-1"></i> Excluir Produto
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Quick Links -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Links Rápidos</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="<?= BASE_URL ?>produto/<?= $product['slug'] ?>" class="text-decoration-none d-flex justify-content-between align-items-center" target="_blank">
                            <span><i class="bi bi-eye me-2"></i> Ver na Loja</span>
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?= BASE_URL ?>admin/categorias/edit/<?= $product['category_id'] ?>" class="text-decoration-none d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-folder me-2"></i> Editar Categoria</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?= BASE_URL ?>admin/produtos/edit/<?= $product['id'] ?>" class="text-decoration-none d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-pencil me-2"></i> Editar Produto</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <li class="list-group-item">
                        <a href="<?= BASE_URL ?>admin/produtos?category_id=<?= $product['category_id'] ?>" class="text-decoration-none d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-grid me-2"></i> Ver Produtos Relacionados</span>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
