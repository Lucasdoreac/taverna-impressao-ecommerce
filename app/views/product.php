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
                <?php if (isset($product['availability'])): ?>
                    <?php if ($product['availability'] === 'Pronta Entrega'): ?>
                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Pronta Entrega</span>
                    <div class="small text-success mt-1">
                        <i class="bi bi-truck me-1"></i> Entrega estimada em <?= $product['estimated_delivery'] ?>
                    </div>
                    <?php else: ?>
                    <span class="badge bg-primary"><i class="bi bi-printer me-1"></i> Sob Encomenda</span>
                    <div class="small text-primary mt-1">
                        <i class="bi bi-clock-history me-1"></i> Impressão e entrega estimadas em <?= $product['estimated_delivery'] ?>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if ($product['stock'] > 0): ?>
                    <span class="badge bg-success">Em estoque</span>
                    <?php else: ?>
                    <span class="badge bg-danger">Fora de estoque</span>
                    <?php endif; ?>
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
                
                <!-- Seleção de Escala (Novo) -->
                <?php if (isset($product['scale'])): ?>
                <div class="mb-3">
                    <label for="selected_scale" class="form-label">Escala</label>
                    <select id="selected_scale" name="selected_scale" class="form-select">
                        <?php
                        // Obter escalas disponíveis das configurações
                        $scales = [
                            ['id' => '28mm', 'name' => '28mm (Padrão)'],
                            ['id' => '32mm', 'name' => '32mm (Heroic)'],
                            ['id' => '54mm', 'name' => '54mm (Colecionável)']
                        ];
                        
                        if (defined('AVAILABLE_SCALES')) {
                            $configScales = json_decode(AVAILABLE_SCALES, true);
                            if (is_array($configScales)) {
                                $scales = $configScales;
                            }
                        }
                        
                        foreach ($scales as $scale):
                        ?>
                        <option value="<?= $scale['id'] ?>" <?= $product['scale'] === $scale['id'] ? 'selected' : '' ?>>
                            <?= $scale['name'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text small">Escolha a escala da sua miniatura</div>
                </div>
                <?php endif; ?>
                
                <!-- Seleção de Cor de Filamento (Novo) -->
                <?php if (!empty($product['filament_colors'])): ?>
                <div class="mb-3">
                    <label class="form-label">Cor do Filamento</label>
                    <div class="d-flex flex-wrap">
                        <?php foreach ($product['filament_colors'] as $index => $color): ?>
                        <div class="form-check form-check-inline color-option mb-2">
                            <input 
                                class="form-check-input" 
                                type="radio" 
                                name="selected_color" 
                                id="color_<?= $color['id'] ?>" 
                                value="<?= $color['id'] ?>"
                                <?= $index === 0 ? 'checked' : '' ?>
                            >
                            <label class="form-check-label d-flex align-items-center" for="color_<?= $color['id'] ?>">
                                <span class="color-swatch me-1" style="background-color: <?= $color['hex_code'] ?>; width: 20px; height: 20px; display: inline-block; border-radius: 4px; border: 1px solid #ddd;"></span>
                                <?= $color['name'] ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantidade</label>
                    <div class="input-group">
                        <button type="button" class="btn btn-outline-secondary quantity-btn" data-action="minus">-</button>
                        <input type="number" id="quantity" name="quantity" class="form-control text-center" value="1" min="1" max="<?= $product['stock'] ?? 99 ?>">
                        <button type="button" class="btn btn-outline-secondary quantity-btn" data-action="plus">+</button>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <?php if (isset($product['availability']) && $product['availability'] === 'Pronta Entrega'): ?>
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
                    <?php elseif (isset($product['availability']) && $product['availability'] === 'Sob Encomenda'): ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-printer me-1"></i> Encomendar Impressão
                        </button>
                        <div class="text-center small text-muted">
                            <i class="bi bi-info-circle me-1"></i> Este produto será impresso sob demanda após seu pedido
                        </div>
                    <?php else: ?>
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
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- Informações Técnicas de Impressão 3D (Novo) -->
            <div class="card mb-3 border-primary">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-printer-fill me-1"></i> Informações Técnicas de Impressão 3D
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php if (isset($product['print_time_hours'])): ?>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-clock me-2"></i>
                                <div>
                                    <div class="small text-muted">Tempo de Impressão:</div>
                                    <div>
                                        <?php
                                        $hours = floor($product['print_time_hours']);
                                        $minutes = round(($product['print_time_hours'] - $hours) * 60);
                                        echo $hours > 0 ? $hours . 'h ' : '';
                                        echo $minutes > 0 ? $minutes . 'min' : '';
                                        if ($hours == 0 && $minutes == 0) echo 'Menos de 1 min';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($product['filament_type'])): ?>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-droplet me-2"></i>
                                <div>
                                    <div class="small text-muted">Tipo de Filamento:</div>
                                    <div><?= $product['filament_type'] ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($product['filament_usage_grams'])): ?>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-speedometer me-2"></i>
                                <div>
                                    <div class="small text-muted">Filamento Usado:</div>
                                    <div><?= $product['filament_usage_grams'] ?>g</div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($product['dimensions'])): ?>
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
                        
                        <?php if (isset($product['scale'])): ?>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-aspect-ratio me-2"></i>
                                <div>
                                    <div class="small text-muted">Escala Padrão:</div>
                                    <div><?= $product['scale'] ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
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
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="printing-tab" data-bs-toggle="tab" data-bs-target="#printing-content" 
                                   type="button" role="tab" aria-controls="printing-content" aria-selected="false">
                                Impressão 3D
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
                        
                        <div class="tab-pane fade" id="printing-content" role="tabpanel" aria-labelledby="printing-tab">
                            <h4 class="h5 mb-3">Sobre nossa Impressão 3D</h4>
                            <p>Todos os nossos produtos são impressos utilizando tecnologia FDM (Modelagem por Deposição Fundida) com impressoras de alta qualidade e configurações otimizadas para cada modelo.</p>
                            
                            <h5 class="h6 mt-4 mb-2">Características da Impressão</h5>
                            <ul>
                                <li><strong>Filamento:</strong> Utilizamos <?= $product['filament_type'] ?? 'PLA' ?> de alta qualidade</li>
                                <li><strong>Precisão:</strong> Altura de camada de 0.1 a 0.2mm para capturar detalhes finos</li>
                                <li><strong>Acabamento:</strong> Todas as miniaturas passam por processo de pós-processamento para remoção de suportes</li>
                                <li><strong>Durabilidade:</strong> Nossas peças são robustas o suficiente para o uso em jogos</li>
                            </ul>
                            
                            <?php if ($product['availability'] === 'Sob Encomenda'): ?>
                            <div class="alert alert-info mt-3">
                                <h5 class="h6"><i class="bi bi-info-circle me-2"></i>Produtos Sob Encomenda</h5>
                                <p class="mb-0">Este é um produto sob encomenda. Após seu pedido, o item será impresso especialmente para você usando as configurações escolhidas. O tempo de produção é de aproximadamente <?= $product['print_time_hours'] ?? '4-8' ?> horas, mais o tempo para acabamento e envio.</p>
                            </div>
                            <?php elseif ($product['availability'] === 'Pronta Entrega'): ?>
                            <div class="alert alert-success mt-3">
                                <h5 class="h6"><i class="bi bi-check-circle me-2"></i>Produto Testado - Pronta Entrega</h5>
                                <p class="mb-0">Este produto já foi testado e está disponível para pronta entrega. Nós já imprimimos, validamos a qualidade e temos em estoque, garantindo envio rápido e qualidade comprovada.</p>
                            </div>
                            <?php endif; ?>
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
                            
                            <?php if ($product['availability'] === 'Sob Encomenda'): ?>
                            <div class="alert alert-warning mt-3">
                                <h5 class="h6"><i class="bi bi-clock me-2"></i>Importante: Prazo Adicional para Impressão</h5>
                                <p class="mb-0">Este produto é impresso sob demanda. Por favor, adicione 2-3 dias úteis ao prazo de entrega para o tempo de impressão e preparação antes do envio.</p>
                            </div>
                            <?php endif; ?>
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
                        
                        <?php if (isset($related['availability'])): ?>
                        <span class="position-absolute badge <?= $related['availability'] === 'Pronta Entrega' ? 'bg-success' : 'bg-primary' ?> top-0 end-0 m-2">
                            <?= $related['availability'] ?>
                        </span>
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

<!-- Estilos para seletores de cores -->
<style>
.color-option {
    margin-right: 10px;
    margin-bottom: 10px;
}
.color-swatch {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
    border: 1px solid #ddd;
}
.form-check-input:checked + .form-check-label .color-swatch {
    border: 2px solid #0d6efd;
    box-shadow: 0 0 0 1px #fff inset;
}
</style>

<!-- Script para galeria de imagens e outros elementos interativos -->
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
