<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<!-- Page Title and Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Produtos</h1>
    <a href="<?= BASE_URL ?>admin/produtos/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Novo Produto
    </a>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Filtros</h5>
    </div>
    <div class="card-body">
        <form action="<?= BASE_URL ?>admin/produtos" method="get" class="row g-3">
            <div class="col-md-4">
                <label for="name" class="form-label">Nome</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= $_GET['name'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <label for="category_id" class="form-label">Categoria</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= (isset($_GET['category_id']) && $_GET['category_id'] == $category['id']) ? 'selected' : '' ?>>
                            <?= $category['name'] ?>
                        </option>
                        <?php if (!empty($category['subcategories'])): ?>
                            <?php foreach ($category['subcategories'] as $subcategory): ?>
                                <option value="<?= $subcategory['id'] ?>" <?= (isset($_GET['category_id']) && $_GET['category_id'] == $subcategory['id']) ? 'selected' : '' ?>>
                                    &nbsp;&nbsp;└ <?= $subcategory['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="is_active" class="form-label">Status</label>
                <select class="form-select" id="is_active" name="is_active">
                    <option value="">Todos</option>
                    <option value="1" <?= (isset($_GET['is_active']) && $_GET['is_active'] == '1') ? 'selected' : '' ?>>Ativos</option>
                    <option value="0" <?= (isset($_GET['is_active']) && $_GET['is_active'] == '0') ? 'selected' : '' ?>>Inativos</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="is_featured" class="form-label">Tipo</label>
                <div class="d-flex gap-3 mt-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" <?= (isset($_GET['is_featured']) && $_GET['is_featured'] == '1') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_featured">Destaque</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_customizable" name="is_customizable" value="1" <?= (isset($_GET['is_customizable']) && $_GET['is_customizable'] == '1') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_customizable">Personalizável</label>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i> Filtrar
                </button>
                <a href="<?= BASE_URL ?>admin/produtos" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Limpar Filtros
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Products List -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Lista de Produtos</h5>
        <span class="text-muted small">
            Total: <?= $products['total'] ?> produtos 
            | Exibindo: <?= $products['from'] ?>-<?= $products['to'] ?>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col" width="80"></th>
                        <th scope="col">Nome</th>
                        <th scope="col">Categoria</th>
                        <th scope="col">Preço</th>
                        <th scope="col">Estoque</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products['items'])): ?>
                    <tr>
                        <td colspan="7" class="text-center py-3">Nenhum produto encontrado.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($products['items'] as $product): ?>
                    <tr>
                        <td>
                            <?php if (!empty($product['main_image'])): ?>
                            <img src="<?= BASE_URL ?>uploads/products/<?= $product['main_image'] ?>" alt="<?= $product['name'] ?>" class="product-thumbnail">
                            <?php else: ?>
                            <div class="product-thumbnail bg-light d-flex align-items-center justify-content-center">
                                <i class="bi bi-image text-muted"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-medium"><?= $product['name'] ?></div>
                            <div class="small text-muted">SKU: <?= $product['sku'] ?></div>
                        </td>
                        <td><?= $product['category_name'] ?></td>
                        <td>
                            <?php if (!empty($product['sale_price'])): ?>
                            <span class="text-decoration-line-through text-muted small">
                                <?= AdminHelper::formatMoney($product['price']) ?>
                            </span>
                            <div><?= AdminHelper::formatMoney($product['sale_price']) ?></div>
                            <?php else: ?>
                            <?= AdminHelper::formatMoney($product['price']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($product['stock'] <= 0): ?>
                            <span class="badge bg-danger">Esgotado</span>
                            <?php elseif ($product['stock'] <= 5): ?>
                            <span class="badge bg-warning"><?= $product['stock'] ?></span>
                            <?php else: ?>
                            <span class="badge bg-success"><?= $product['stock'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <?php if ($product['is_active']): ?>
                                <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Inativo</span>
                                <?php endif; ?>
                                
                                <?php if ($product['is_featured']): ?>
                                <span class="badge bg-primary">Destaque</span>
                                <?php endif; ?>
                                
                                <?php if ($product['is_customizable']): ?>
                                <span class="badge bg-info">Personalizável</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="<?= BASE_URL ?>admin/produtos/view/<?= $product['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>admin/produtos/edit/<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="<?= BASE_URL ?>admin/produtos/delete/<?= $product['id'] ?>" class="btn btn-sm btn-outline-danger btn-delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                            <div class="btn-group mt-1">
                                <?php if ($product['is_active']): ?>
                                <a href="<?= BASE_URL ?>admin/produtos/toggle-active/<?= $product['id'] ?>" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-toggle-on"></i>
                                </a>
                                <?php else: ?>
                                <a href="<?= BASE_URL ?>admin/produtos/toggle-active/<?= $product['id'] ?>" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-toggle-off"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($product['is_featured']): ?>
                                <a href="<?= BASE_URL ?>admin/produtos/toggle-featured/<?= $product['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-star-fill"></i>
                                </a>
                                <?php else: ?>
                                <a href="<?= BASE_URL ?>admin/produtos/toggle-featured/<?= $product['id'] ?>" class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-star"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($products['lastPage'] > 1): ?>
    <div class="card-footer bg-white">
        <nav aria-label="Paginação">
            <ul class="pagination mb-0 justify-content-center">
                <li class="page-item <?= $products['currentPage'] == 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>admin/produtos?page=1<?= isset($_GET['name']) ? '&name=' . $_GET['name'] : '' ?><?= isset($_GET['category_id']) ? '&category_id=' . $_GET['category_id'] : '' ?><?= isset($_GET['is_active']) ? '&is_active=' . $_GET['is_active'] : '' ?><?= isset($_GET['is_featured']) ? '&is_featured=' . $_GET['is_featured'] : '' ?><?= isset($_GET['is_customizable']) ? '&is_customizable=' . $_GET['is_customizable'] : '' ?>">
                        <i class="bi bi-chevron-double-left"></i>
                    </a>
                </li>
                <li class="page-item <?= $products['currentPage'] == 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>admin/produtos?page=<?= $products['currentPage'] - 1 ?><?= isset($_GET['name']) ? '&name=' . $_GET['name'] : '' ?><?= isset($_GET['category_id']) ? '&category_id=' . $_GET['category_id'] : '' ?><?= isset($_GET['is_active']) ? '&is_active=' . $_GET['is_active'] : '' ?><?= isset($_GET['is_featured']) ? '&is_featured=' . $_GET['is_featured'] : '' ?><?= isset($_GET['is_customizable']) ? '&is_customizable=' . $_GET['is_customizable'] : '' ?>">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>

                <?php
                $startPage = max(1, $products['currentPage'] - 2);
                $endPage = min($products['lastPage'], $products['currentPage'] + 2);
                
                if ($startPage > 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                
                for ($i = $startPage; $i <= $endPage; $i++): 
                ?>
                <li class="page-item <?= $products['currentPage'] == $i ? 'active' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>admin/produtos?page=<?= $i ?><?= isset($_GET['name']) ? '&name=' . $_GET['name'] : '' ?><?= isset($_GET['category_id']) ? '&category_id=' . $_GET['category_id'] : '' ?><?= isset($_GET['is_active']) ? '&is_active=' . $_GET['is_active'] : '' ?><?= isset($_GET['is_featured']) ? '&is_featured=' . $_GET['is_featured'] : '' ?><?= isset($_GET['is_customizable']) ? '&is_customizable=' . $_GET['is_customizable'] : '' ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>

                <?php
                if ($endPage < $products['lastPage']) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                ?>

                <li class="page-item <?= $products['currentPage'] == $products['lastPage'] ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>admin/produtos?page=<?= $products['currentPage'] + 1 ?><?= isset($_GET['name']) ? '&name=' . $_GET['name'] : '' ?><?= isset($_GET['category_id']) ? '&category_id=' . $_GET['category_id'] : '' ?><?= isset($_GET['is_active']) ? '&is_active=' . $_GET['is_active'] : '' ?><?= isset($_GET['is_featured']) ? '&is_featured=' . $_GET['is_featured'] : '' ?><?= isset($_GET['is_customizable']) ? '&is_customizable=' . $_GET['is_customizable'] : '' ?>">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
                <li class="page-item <?= $products['currentPage'] == $products['lastPage'] ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL ?>admin/produtos?page=<?= $products['lastPage'] ?><?= isset($_GET['name']) ? '&name=' . $_GET['name'] : '' ?><?= isset($_GET['category_id']) ? '&category_id=' . $_GET['category_id'] : '' ?><?= isset($_GET['is_active']) ? '&is_active=' . $_GET['is_active'] : '' ?><?= isset($_GET['is_featured']) ? '&is_featured=' . $_GET['is_featured'] : '' ?><?= isset($_GET['is_customizable']) ? '&is_customizable=' . $_GET['is_customizable'] : '' ?>">
                        <i class="bi bi-chevron-double-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
