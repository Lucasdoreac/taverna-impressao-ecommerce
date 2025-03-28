<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
            <li class="breadcrumb-item active"><?= $category['name'] ?></li>
        </ol>
    </nav>
    
    <h1 class="h2 mb-4"><?= $category['name'] ?></h1>
    
    <?php if (!empty($category['description'])): ?>
    <p class="mb-4"><?= $category['description'] ?></p>
    <?php endif; ?>
    
    <!-- Subcategorias -->
    <?php if (!empty($category['subcategories'])): ?>
    <h2 class="h4 mb-3">Subcategorias</h2>
    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <?php foreach ($category['subcategories'] as $subcategory): ?>
        <div class="col">
            <a href="<?= BASE_URL ?>categoria/<?= $subcategory['slug'] ?>" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm">
                    <?php if (!empty($subcategory['image'])): ?>
                    <img src="<?= BASE_URL ?>uploads/categories/<?= $subcategory['image'] ?>" class="card-img-top" alt="<?= $subcategory['name'] ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <h3 class="card-title h5"><?= $subcategory['name'] ?></h3>
                        <?php if (!empty($subcategory['description'])): ?>
                        <p class="card-text"><?= mb_strimwidth($subcategory['description'], 0, 100, '...') ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Produtos -->
    <h2 class="h4 mb-3">Produtos nesta categoria</h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        <?php if (count($products['items']) > 0): ?>
            <?php foreach ($products['items'] as $product): ?>
            <div class="col">
                <div class="card h-100 product-card border-0 shadow-sm">
                    <div class="position-relative">
                        <?php if (isset($product['sale_price']) && $product['sale_price'] && $product['sale_price'] < $product['price']): ?>
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
                                <?php if (isset($product['sale_price']) && $product['sale_price'] && $product['sale_price'] < $product['price']): ?>
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
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    Nenhum produto encontrado nesta categoria.
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Paginação -->
    <?php if ($products['lastPage'] > 1): ?>
    <nav aria-label="Paginação" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($products['currentPage'] > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= BASE_URL ?>categoria/<?= $category['slug'] ?>?page=<?= $products['currentPage'] - 1 ?>">Anterior</a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">Anterior</span>
            </li>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $products['lastPage']; $i++): ?>
            <li class="page-item <?= $i == $products['currentPage'] ? 'active' : '' ?>">
                <a class="page-link" href="<?= BASE_URL ?>categoria/<?= $category['slug'] ?>?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            
            <?php if ($products['currentPage'] < $products['lastPage']): ?>
            <li class="page-item">
                <a class="page-link" href="<?= BASE_URL ?>categoria/<?= $category['slug'] ?>?page=<?= $products['currentPage'] + 1 ?>">Próxima</a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">Próxima</span>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>