<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<!-- Page Title and Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Categorias</h1>
    <a href="<?= BASE_URL ?>admin/categorias/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Nova Categoria
    </a>
</div>

<!-- Categories List -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Lista de Categorias</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" width="50">ID</th>
                        <th scope="col">Nome</th>
                        <th scope="col">Slug</th>
                        <th scope="col">Categoria Pai</th>
                        <th scope="col">Status</th>
                        <th scope="col">Ordem</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-3">Nenhuma categoria encontrada.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?= $category['id'] ?></td>
                            <td>
                                <strong><?= $category['name'] ?></strong>
                            </td>
                            <td><code><?= $category['slug'] ?></code></td>
                            <td><span class="badge bg-secondary">Principal</span></td>
                            <td>
                                <?php if ($category['is_active']): ?>
                                <span class="badge bg-success">Ativa</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Inativa</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $category['display_order'] ?></td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="<?= BASE_URL ?>admin/categorias/edit/<?= $category['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>admin/categorias/delete/<?= $category['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                                <div class="btn-group mt-1">
                                    <?php if ($category['is_active']): ?>
                                    <a href="<?= BASE_URL ?>admin/categorias/toggle-active/<?= $category['id'] ?>" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-toggle-on"></i>
                                    </a>
                                    <?php else: ?>
                                    <a href="<?= BASE_URL ?>admin/categorias/toggle-active/<?= $category['id'] ?>" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-toggle-off"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Subcategories -->
                        <?php if (!empty($category['subcategories'])): ?>
                            <?php foreach ($category['subcategories'] as $subcategory): ?>
                            <tr class="table-light">
                                <td><?= $subcategory['id'] ?></td>
                                <td>
                                    <div class="ms-3">└ <?= $subcategory['name'] ?></div>
                                </td>
                                <td><code><?= $subcategory['slug'] ?></code></td>
                                <td><?= $category['name'] ?></td>
                                <td>
                                    <?php if ($subcategory['is_active']): ?>
                                    <span class="badge bg-success">Ativa</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">Inativa</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $subcategory['display_order'] ?></td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="<?= BASE_URL ?>admin/categorias/edit/<?= $subcategory['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>admin/categorias/delete/<?= $subcategory['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                    <div class="btn-group mt-1">
                                        <?php if ($subcategory['is_active']): ?>
                                        <a href="<?= BASE_URL ?>admin/categorias/toggle-active/<?= $subcategory['id'] ?>" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-toggle-on"></i>
                                        </a>
                                        <?php else: ?>
                                        <a href="<?= BASE_URL ?>admin/categorias/toggle-active/<?= $subcategory['id'] ?>" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-toggle-off"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Dicas Sobre Categorias</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <h6><i class="bi bi-info-circle text-primary me-2"></i> Hierarquia</h6>
                <p class="small text-muted">As categorias podem ser organizadas em uma estrutura hierárquica com categorias principais e subcategorias. Uma subcategoria é vinculada a uma categoria principal.</p>
            </div>
            <div class="col-md-4">
                <h6><i class="bi bi-info-circle text-primary me-2"></i> Status</h6>
                <p class="small text-muted">Categorias inativas não serão exibidas na loja. Se uma categoria principal for inativada, suas subcategorias permanecerão visíveis se estiverem ativas.</p>
            </div>
            <div class="col-md-4">
                <h6><i class="bi bi-info-circle text-primary me-2"></i> Ordem de Exibição</h6>
                <p class="small text-muted">A ordem de exibição determina a posição em que a categoria aparece no menu. Categorias com valores menores são exibidas primeiro.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
