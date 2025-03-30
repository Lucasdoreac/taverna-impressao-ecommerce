<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <h1 class="h2 mb-4">Produtos Personalizáveis</h1>
    
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        Aqui você encontra todos os produtos que podem ser personalizados. 
        Escolha um produto e clique em "Personalizar" para configurar de acordo com suas necessidades.
    </div>
    
    <!-- Filtros -->
    <div class="mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Filtros</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="availability" class="form-label">Disponibilidade</label>
                            <select id="availability" class="form-select">
                                <option value="all" <?= $availability === 'all' ? 'selected' : '' ?>>Todos</option>
                                <option value="tested" <?= $availability === 'tested' ? 'selected' : '' ?>>Pronta Entrega</option>
                                <option value="custom" <?= $availability === 'custom' ? 'selected' : '' ?>>Sob Encomenda</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sort" class="form-label">Ordenar por</label>
                            <select id="sort" class="form-select">
                                <option value="newest">Mais recentes</option>
                                <option value="price_asc">Menor preço</option>
                                <option value="price_desc">Maior preço</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($customizableProducts)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Nenhum produto personalizável encontrado no momento.
    </div>
    <?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        <?php foreach ($customizableProducts as $product): ?>
        <div class="col">
            <div class="card h-100 product-card border-0 shadow-sm">
                <div class="position-relative">
                    <?php if (isset($product['sale_price']) && $product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                    <span class="position-absolute badge bg-danger top-0 start-0 m-2">OFERTA</span>
                    <?php endif; ?>
                    
                    <span class="position-absolute badge <?= $product['availability'] === 'Pronta Entrega' ? 'bg-success' : 'bg-primary' ?> top-0 end-0 m-2">
                        <?= $product['availability'] ?>
                    </span>
                    
                    <?php if (isset($product['image']) && !empty($product['image']) && file_exists(UPLOADS_PATH . '/products/' . $product['image'])): ?>
                    <img src="<?= BASE_URL ?>uploads/products/<?= $product['image'] ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>">
                    <?php else: ?>
                    <div class="placeholder-product" role="img" aria-label="<?= htmlspecialchars($product['name']) ?>"></div>
                    <?php endif; ?>
                </div>
                
                <div class="card-body">
                    <h2 class="card-title h6"><?= htmlspecialchars($product['name']) ?></h2>
                    
                    <?php if (isset($product['short_description'])): ?>
                    <p class="card-text small"><?= mb_strimwidth(htmlspecialchars($product['short_description']), 0, 60, '...') ?></p>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <?php if (isset($product['sale_price']) && $product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                            <span class="text-decoration-line-through text-muted small"><?= CURRENCY_SYMBOL ?> <?= number_format($product['price'], 2, ',', '.') ?></span>
                            <span class="ms-1 text-danger fw-bold"><?= CURRENCY_SYMBOL ?> <?= number_format($product['sale_price'], 2, ',', '.') ?></span>
                            <?php else: ?>
                            <span class="fw-bold"><?= CURRENCY_SYMBOL ?> <?= number_format($product['price'], 2, ',', '.') ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <a href="<?= BASE_URL ?>personalizar/<?= $product['slug'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-brush me-1"></i> Personalizar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const availabilitySelect = document.getElementById('availability');
    
    availabilitySelect.addEventListener('change', function() {
        const availability = this.value;
        window.location.href = '<?= BASE_URL ?>personalizados?availability=' + availability;
    });
    
    const sortSelect = document.getElementById('sort');
    
    sortSelect.addEventListener('change', function() {
        const sort = this.value;
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('sort', sort);
        window.location.href = currentUrl.toString();
    });
});
</script>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>