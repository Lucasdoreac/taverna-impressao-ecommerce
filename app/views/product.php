<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>categoria/<?= $product['category_slug'] ?>"><?= $product['category_name'] ?></a></li>
            <li class="breadcrumb-item active"><?= $product['name'] ?></li>
        </ol>
    </nav>
    
    <div class="row">
        <!-- Galeria de Imagens -->
        <div class="col-md-6 mb-4">
            <div class="product-gallery">
                <!-- Imagem Principal -->
                <div class="main-image mb-3">
                    <?php if (!empty($product['images']) && file_exists(UPLOADS_PATH . '/products/' . $product['images'][0]['image'])): ?>
                    <img id="main-product-image" src="<?= BASE_URL ?>uploads/products/<?= $product['images'][0]['image'] ?>" 
                         class="img-fluid rounded" alt="<?= $product['name'] ?>">
                    <?php else: ?>
                    <div class="placeholder-product" role="img" aria-label="<?= htmlspecialchars($product['name']) ?>"></div>
                    <?php endif; ?>
                </div>
                
                <!-- Thumbnails -->
                <?php if (!empty($product['images']) && count($product['images']) > 1): ?>
                <div class="thumbnails d-flex flex-wrap">
                    <?php foreach ($product['images'] as $index => $image): ?>
                    <?php if (file_exists(UPLOADS_PATH . '/products/' . $image['image'])): ?>
                    <div class="thumbnail-item me-2 mb-2">
                        <img src="<?= BASE_URL ?>uploads/products/<?= $image['image'] ?>" 
                             class="img-thumbnail thumbnail-image <?= $index === 0 ? 'active' : '' ?>" 
                             alt="<?= $product['name'] ?> - Imagem <?= $index + 1 ?>"
                             data-image="<?= BASE_URL ?>uploads/products/<?= $image['image'] ?>">
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Informações do Produto -->
        <div class="col-md-6">
            <h1 class="h2 mb-3"><?= $product['name'] ?></h1>
            
            <!-- Preço -->
            <div class="mb-3">
                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                <div class="d-flex align-items-center">
                    <span class="text-decoration-line-through text-muted">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                    <span class="ms-2 h4 text-danger mb-0">R$ <?= number_format($product['sale_price'], 2, ',', '.') ?></span>
                    <span class="badge bg-danger ms-2">
                        <?= round((1 - $product['sale_price'] / $product['price']) * 100) ?>% OFF
                    </span>
                </div>
                <?php else: ?>
                <span class="h4 mb-0">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                <?php endif; ?>
                
                <div class="text-muted small mt-1">
                    Em até 12x no cartão de crédito
                </div>
            </div>
            
            <!-- Disponibilidade -->
            <div class="mb-3">
                <?php if ($product['stock'] > 0): ?>
                <span class="badge bg-success">Em estoque</span>
                <?php else: ?>
                <span class="badge bg-danger">Fora de estoque</span>
                <?php endif; ?>
            </div>
            
            <!-- Descrição Curta -->
            <?php if (!empty($product['short_description'])): ?>
            <div class="mb-3">
                <p><?= $product['short_description'] ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Formulário de Compra -->
            <form action="<?= BASE_URL ?>carrinho/adicionar" method="post" class="mb-4">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                
                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantidade</label>
                    <div class="input-group">
                        <button type="button" class="btn btn-outline-secondary quantity-btn" data-action="minus">-</button>
                        <input type="number" id="quantity" name="quantity" class="form-control text-center" value="1" min="1" max="<?= $product['stock'] ?>">
                        <button type="button" class="btn btn-outline-secondary quantity-btn" data-action="plus">+</button>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <?php if ($product['stock'] > 0): ?>
                        <?php if ($product['is_customizable']): ?>
                        <a href="<?= BASE_URL ?>personalizar/<?= $product['slug'] ?>" class="btn btn-primary">
                            <i class="bi bi-brush me-1"></i> Personalizar
                        </a>
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-cart-plus me-1"></i> Adicionar ao Carrinho
                        </button>
                        <?php else: ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cart-plus me-1"></i> Adicionar ao Carrinho
                        </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary" disabled>
                            <i class="bi bi-x-circle me-1"></i> Produto Indisponível
                        </button>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- Informações Adicionais -->
            <div class="mb-3">
                <div class="row g-3">
                    <?php if (!empty($product['sku'])): ?>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-upc me-2"></i>
                            <div>
                                <div class="small text-muted">Código:</div>
                                <div><?= $product['sku'] ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['weight'])): ?>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-box-seam me-2"></i>
                            <div>
                                <div class="small text-muted">Peso:</div>
                                <div><?= number_format($product['weight'], 2, ',', '.') ?> kg</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['dimensions'])): ?>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-rulers me-2"></i>
                            <div>
                                <div class="small text-muted">Dimensões:</div>
                                <div><?= $product['dimensions'] ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-truck me-2"></i>
                            <div>
                                <div class="small text-muted">Entrega:</div>
                                <div>Todo o Brasil</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Descrição Detalhada -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="productTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description-content" 
                                   type="button" role="tab" aria-controls="description-content" aria-selected="true">
                                Descrição
                            </button>
                        </li>
                        <?php if ($product['is_customizable']): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="customization-tab" data-bs-toggle="tab" data-bs-target="#customization-content" 
                                   type="button" role="tab" aria-controls="customization-content" aria-selected="false">
                                Personalização
                            </button>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping-content" 
                                   type="button" role="tab" aria-controls="shipping-content" aria-selected="false">
                                Entrega
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="productTabsContent">
                        <div class="tab-pane fade show active" id="description-content" role="tabpanel" aria-labelledby="description-tab">
                            <div class="product-description">
                                <?= $product['description'] ?? '<p>Sem descrição detalhada disponível.</p>' ?>
                            </div>
                        </div>
                        
                        <?php if ($product['is_customizable']): ?>
                        <div class="tab-pane fade" id="customization-content" role="tabpanel" aria-labelledby="customization-tab">
                            <h4 class="h5 mb-3">Opções de Personalização</h4>
                            
                            <?php if (!empty($product['customization_options'])): ?>
                            <ul class="list-group mb-3">
                                <?php foreach ($product['customization_options'] as $option): ?>
                                <li class="list-group-item">
                                    <h5 class="h6 mb-1"><?= $option['name'] ?></h5>
                                    <p class="mb-0 text-muted small"><?= $option['description'] ?></p>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                            
                            <p>Clique no botão "Personalizar" acima para configurar seu produto de acordo com suas necessidades.</p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="tab-pane fade" id="shipping-content" role="tabpanel" aria-labelledby="shipping-tab">
                            <h4 class="h5 mb-3">Informações de Entrega</h4>
                            <p>Enviamos para todo o Brasil. Os prazos e valores de frete são calculados no carrinho de compras.</p>
                            <p>Opções de envio disponíveis:</p>
                            <ul>
                                <li>PAC: 5 a 15 dias úteis (dependendo da região)</li>
                                <li>SEDEX: 1 a 5 dias úteis (dependendo da região)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Produtos Relacionados -->
    <?php if (!empty($related_products)): ?>
    <div class="mt-5">
        <h2 class="h4 mb-4">Produtos Relacionados</h2>
        
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($related_products as $related): ?>
            <div class="col">
                <div class="card h-100 product-card border-0 shadow-sm">
                    <div class="position-relative">
                        <?php if ($related['sale_price'] && $related['sale_price'] < $related['price']): ?>
                        <span class="position-absolute badge bg-danger top-0 start-0 m-2">OFERTA</span>
                        <?php endif; ?>
                        
                        <?php if (!empty($related['image']) && file_exists(UPLOADS_PATH . '/products/' . $related['image'])): ?>
                        <img src="<?= BASE_URL ?>uploads/products/<?= $related['image'] ?>" class="card-img-top" alt="<?= $related['name'] ?>">
                        <?php else: ?>
                        <div class="placeholder-product" role="img" aria-label="<?= htmlspecialchars($related['name']) ?>"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body">
                        <h3 class="card-title h6"><?= $related['name'] ?></h3>
                        <p class="card-text small"><?= mb_strimwidth($related['short_description'] ?? '', 0, 60, '...') ?></p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <?php if ($related['sale_price'] && $related['sale_price'] < $related['price']): ?>
                                <span class="text-decoration-line-through text-muted small">R$ <?= number_format($related['price'], 2, ',', '.') ?></span>
                                <span class="ms-1 text-danger fw-bold">R$ <?= number_format($related['sale_price'], 2, ',', '.') ?></span>
                                <?php else: ?>
                                <span class="fw-bold">R$ <?= number_format($related['price'], 2, ',', '.') ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a href="<?= BASE_URL ?>produto/<?= $related['slug'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Script para galeria de imagens -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Script para thumbnails de imagem
    const thumbnails = document.querySelectorAll('.thumbnail-image');
    const mainImage = document.getElementById('main-product-image');
    
    if (thumbnails.length > 0 && mainImage) {
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                // Atualizar imagem principal
                mainImage.src = this.getAttribute('data-image');
                
                // Atualizar classe ativa
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }
    
    // Script para botões de quantidade
    const quantityInput = document.getElementById('quantity');
    const quantityBtns = document.querySelectorAll('.quantity-btn');
    
    if (quantityInput && quantityBtns.length > 0) {
        quantityBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.getAttribute('data-action');
                const currentValue = parseInt(quantityInput.value);
                const max = parseInt(quantityInput.getAttribute('max'));
                
                if (action === 'minus' && currentValue > 1) {
                    quantityInput.value = currentValue - 1;
                } else if (action === 'plus' && currentValue < max) {
                    quantityInput.value = currentValue + 1;
                }
            });
        });
    }
});
</script>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>