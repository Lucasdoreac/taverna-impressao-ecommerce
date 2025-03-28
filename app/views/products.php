<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
            <li class="breadcrumb-item active">Produtos</li>
        </ol>
    </nav>

    <h1 class="h2 mb-4">Produtos</h1>
    
    <div class="row">
        <!-- Filtros laterais -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Filtros</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="<?= BASE_URL ?>produtos">
                        <div class="mb-3">
                            <label class="form-label">Categorias</label>
                            <?php foreach ($categories as $category): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="categories[]" 
                                    value="<?= $category['id'] ?>" id="cat_<?= $category['id'] ?>"
                                    <?= isset($_GET['categories']) && in_array($category['id'], $_GET['categories']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="cat_<?= $category['id'] ?>">
                                    <?= $category['name'] ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="price_min" class="form-label">Preço Mínimo</label>
                            <input type="number" class="form-control" id="price_min" name="price_min" 
                                value="<?= $_GET['price_min'] ?? '' ?>" min="0" step="0.01">
                        </div>
                        
                        <div class="mb-3">
                            <label for="price_max" class="form-label">Preço Máximo</label>
                            <input type="number" class="form-control" id="price_max" name="price_max" 
                                value="<?= $_GET['price_max'] ?? '' ?>" min="0" step="0.01">
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="on_sale" 
                                    value="1" id="on_sale" <?= isset($_GET['on_sale']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="on_sale">
                                    Apenas Promoções
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <?php if (isset($_GET['categories']) || isset($_GET['price_min']) || isset($_GET['price_max']) || isset($_GET['on_sale'])): ?>
                            <a href="<?= BASE_URL ?>produtos" class="btn btn-outline-secondary mt-2">Limpar Filtros</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Listagem de produtos -->
        <div class="col-lg-9">
            <?php if (empty($products['items'])): ?>
            <div class="alert alert-info">
                Nenhum produto encontrado com os filtros selecionados.
            </div>
            <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($products['items'] as $product): ?>
                <div class="col">
                    <div class="card h-100 product-card">
                        <div class="position-relative">
                            <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                            <span class="position-absolute badge bg-danger top-0 start-0 m-2">OFERTA</span>
                            <?php endif; ?>
                            
                            <?php if (!empty($product['image'])): ?>
                            <img src="<?= BASE_URL ?>uploads/products/<?= $product['image'] ?>" class="card-img-top" alt="<?= $product['name'] ?>">
                            <?php else: ?>
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <span class="text-muted">Sem imagem</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body">
                            <h3 class="card-title h6"><?= $product['name'] ?></h3>
                            <p class="card-text small"><?= mb_strimwidth($product['short_description'] ?? '', 0, 60, '...') ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                    <span class="text-decoration-line-through text-muted small">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                                    <span class="ms-1 text-danger fw-bold">R$ <?= number_format($product['sale_price'], 2, ',', '.') ?></span>
                                    <?php else: ?>
                                    <span class="fw-bold">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <a href="<?= BASE_URL ?>produto/<?= $product['slug'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Paginação -->
            <?php if ($products['lastPage'] > 1): ?>
            <nav aria-label="Paginação" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($products['currentPage'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= BASE_URL ?>produtos?page=<?= $products['currentPage'] - 1 ?>">Anterior</a>
                    </li>
                    <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">Anterior</span>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $products['lastPage']; $i++): ?>
                    <li class="page-item <?= $i == $products['currentPage'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= BASE_URL ?>produtos?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($products['currentPage'] < $products['lastPage']): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= BASE_URL ?>produtos?page=<?= $products['currentPage'] + 1 ?>">Próxima</a>
                    </li>
                    <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">Próxima</span>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>