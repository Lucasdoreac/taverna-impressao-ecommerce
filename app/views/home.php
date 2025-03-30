<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="main-banner">
  <?php if (file_exists(UPLOADS_PATH . '/banners/main-banner.jpg')): ?>
  <?php 
  // Mantemos o carregamento imediato para a imagem do banner principal,
  // pois é a primeira coisa que o usuário vê (above the fold)
  ?>
  <div class="banner-image" style="background-image: url('<?= BASE_URL ?>uploads/banners/main-banner.jpg');"></div>
  <?php else: ?>
  <div class="placeholder-banner"></div>
  <?php endif; ?>
  <div class="banner-content">
    <h1 class="banner-title">TAVERNA DA IMPRESSÃO</h1>
    <p class="banner-text">Miniaturas e acessórios impressos em 3D para RPG e jogos de tabuleiro</p>
    <a href="<?= BASE_URL ?>produtos" class="btn">Ver Catálogo</a>
  </div>
</div>

<div class="container">
  <!-- Categorias -->
  <section class="section">
    <h2 class="section-title">Explore por Categoria</h2>
    <div class="categories">
      <?php foreach ($mainCategories as $category): ?>
      <a href="<?= BASE_URL ?>categoria/<?= $category['slug'] ?>" class="category-card">
        <?php if (!empty($category['image']) && file_exists(UPLOADS_PATH . '/categories/' . $category['image'])): ?>
        <?php 
        // Implementar lazy loading para imagens de categorias
        if (class_exists('AssetOptimizerHelper')) {
            echo AssetOptimizerHelper::lazyBackgroundImage(
                BASE_URL . 'uploads/categories/' . $category['image'], 
                'category-image', 
                ['aria-label' => htmlspecialchars($category['name'])]
            );
        } else {
        ?>
        <div class="category-image" style="background-image: url('<?= BASE_URL ?>uploads/categories/<?= $category['image'] ?>');"></div>
        <?php } ?>
        <?php else: ?>
        <div class="placeholder-category" role="img" aria-label="<?= htmlspecialchars($category['name']) ?>"></div>
        <?php endif; ?>
        <div class="category-title"><?= $category['name'] ?></div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Produtos em Destaque -->
  <section class="section">
    <h2 class="section-title">Produtos em Destaque</h2>
    <div class="products-grid">
      <?php if (!empty($featuredProducts)): ?>
        <?php foreach ($featuredProducts as $index => $product): ?>
      <div class="product-card">
        <?php if (!empty($product['image']) && file_exists(UPLOADS_PATH . '/products/' . $product['image'])): ?>
        <?php 
        // Implementar lazy loading para imagens de produtos em destaque
        if (class_exists('AssetOptimizerHelper')) {
            echo AssetOptimizerHelper::lazyBackgroundImage(
                BASE_URL . 'uploads/products/' . $product['image'], 
                'product-image', 
                ['aria-label' => htmlspecialchars($product['name'])]
            );
        } else {
        ?>
        <div class="product-image" style="background-image: url('<?= BASE_URL ?>uploads/products/<?= $product['image'] ?>');"></div>
        <?php } ?>
        <?php else: ?>
        <div class="product-image">
          <div class="placeholder-product" role="img" aria-label="<?= htmlspecialchars($product['name']) ?>"></div>
        <?php endif; ?>
          <?php if ($index === 0): ?>
          <span class="product-badge">DESTAQUE</span>
          <?php elseif (isset($product['availability']) && $product['availability'] === 'Pronta Entrega'): ?>
          <span class="product-badge product-badge-success">PRONTA ENTREGA</span>
          <?php elseif (isset($product['availability']) && $product['availability'] === 'Sob Encomenda'): ?>
          <span class="product-badge product-badge-primary">SOB ENCOMENDA</span>
          <?php elseif ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
          <span class="product-badge product-badge-danger">PROMOÇÃO</span>
          <?php endif; ?>
        </div>
        <div class="product-content">
          <h3 class="product-title"><?= $product['name'] ?></h3>
          <div class="product-price">
            <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
            <span class="original-price"><?= getCurrencySymbol() ?> <?= number_format($product['price'], 2, ',', '.') ?></span>
            <span class="current-price discount-price"><?= getCurrencySymbol() ?> <?= number_format($product['sale_price'], 2, ',', '.') ?></span>
            <?php else: ?>
            <span class="current-price"><?= getCurrencySymbol() ?> <?= number_format($product['price'], 2, ',', '.') ?></span>
            <?php endif; ?>
          </div>
          <p><?= mb_strimwidth($product['short_description'] ?? '', 0, 60, '...') ?></p>
          <div class="product-actions">
            <a href="<?= BASE_URL ?>produto/<?= $product['slug'] ?>" class="btn btn-sm">Ver Detalhes</a>
            <form action="<?= BASE_URL ?>carrinho/adicionar" method="post" style="display:inline;">
              <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
              <input type="hidden" name="quantity" value="1">
              <button type="submit" class="btn btn-sm"><i class="fas fa-cart-plus"></i></button>
            </form>
          </div>
        </div>
      </div>
        <?php endforeach; ?>
      <?php else: ?>
      <div class="alert alert-info">
        Nenhum produto em destaque encontrado.
      </div>
      <?php endif; ?>
    </div>
    
    <div class="text-center" style="margin-top: 2rem;">
      <a href="<?= BASE_URL ?>produtos" class="btn">Ver Todos os Produtos</a>
    </div>
  </section>

  <!-- Produtos Testados - Pronta Entrega (Novo) -->
  <?php if (!empty($testedProducts)): ?>
  <section class="section">
    <div class="section-header">
      <h2 class="section-title">Pronta Entrega</h2>
      <div class="section-subtitle">Produtos testados disponíveis para envio imediato</div>
    </div>
    
    <div class="products-grid">
      <?php foreach ($testedProducts as $product): ?>
      <div class="product-card">
        <?php if (!empty($product['image']) && file_exists(UPLOADS_PATH . '/products/' . $product['image'])): ?>
        <?php 
        // Implementar lazy loading para imagens de produtos testados
        if (class_exists('AssetOptimizerHelper')) {
            echo AssetOptimizerHelper::lazyBackgroundImage(
                BASE_URL . 'uploads/products/' . $product['image'], 
                'product-image', 
                ['aria-label' => htmlspecialchars($product['name'])]
            );
        } else {
        ?>
        <div class="product-image" style="background-image: url('<?= BASE_URL ?>uploads/products/<?= $product['image'] ?>');"></div>
        <?php } ?>
        <?php else: ?>
        <div class="product-image">
          <div class="placeholder-product" role="img" aria-label="<?= htmlspecialchars($product['name']) ?>"></div>
        <?php endif; ?>
          <span class="product-badge product-badge-success">PRONTA ENTREGA</span>
        </div>
        <div class="product-content">
          <h3 class="product-title"><?= $product['name'] ?></h3>
          <div class="product-price">
            <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
            <span class="original-price"><?= getCurrencySymbol() ?> <?= number_format($product['price'], 2, ',', '.') ?></span>
            <span class="current-price discount-price"><?= getCurrencySymbol() ?> <?= number_format($product['sale_price'], 2, ',', '.') ?></span>
            <?php else: ?>
            <span class="current-price"><?= getCurrencySymbol() ?> <?= number_format($product['price'], 2, ',', '.') ?></span>
            <?php endif; ?>
          </div>
          <div class="product-meta">
            <span class="product-meta-item"><i class="fas fa-cube"></i> <?= $product['filament_type'] ?? 'PLA' ?></span>
            <?php if (isset($product['print_time_hours'])): ?>
            <span class="product-meta-item"><i class="fas fa-clock"></i> <?= number_format($product['print_time_hours'], 1) ?>h</span>
            <?php endif; ?>
          </div>
          <div class="product-actions">
            <a href="<?= BASE_URL ?>produto/<?= $product['slug'] ?>" class="btn btn-sm">Ver Detalhes</a>
            <form action="<?= BASE_URL ?>carrinho/adicionar" method="post" style="display:inline;">
              <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
              <input type="hidden" name="quantity" value="1">
              <button type="submit" class="btn btn-sm"><i class="fas fa-cart-plus"></i></button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    
    <div class="text-center" style="margin-top: 2rem;">
      <a href="<?= BASE_URL ?>produtos?disponibilidade=tested" class="btn">Ver Todos Produtos em Pronta Entrega</a>
    </div>
  </section>
  <?php endif; ?>

  <!-- Produtos Sob Encomenda (Novo) -->
  <?php if (!empty($customProducts)): ?>
  <section class="section">
    <div class="section-header">
      <h2 class="section-title">Sob Encomenda</h2>
      <div class="section-subtitle">Produtos impressos de acordo com suas preferências</div>
    </div>
    
    <div class="products-grid">
      <?php foreach ($customProducts as $product): ?>
      <div class="product-card">
        <?php if (!empty($product['image']) && file_exists(UPLOADS_PATH . '/products/' . $product['image'])): ?>
        <?php 
        // Implementar lazy loading para imagens de produtos sob encomenda
        if (class_exists('AssetOptimizerHelper')) {
            echo AssetOptimizerHelper::lazyBackgroundImage(
                BASE_URL . 'uploads/products/' . $product['image'], 
                'product-image', 
                ['aria-label' => htmlspecialchars($product['name'])]
            );
        } else {
        ?>
        <div class="product-image" style="background-image: url('<?= BASE_URL ?>uploads/products/<?= $product['image'] ?>');"></div>
        <?php } ?>
        <?php else: ?>
        <div class="product-image">
          <div class="placeholder-product" role="img" aria-label="<?= htmlspecialchars($product['name']) ?>"></div>
        <?php endif; ?>
          <span class="product-badge product-badge-primary">SOB ENCOMENDA</span>
        </div>
        <div class="product-content">
          <h3 class="product-title"><?= $product['name'] ?></h3>
          <div class="product-price">
            <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
            <span class="original-price"><?= getCurrencySymbol() ?> <?= number_format($product['price'], 2, ',', '.') ?></span>
            <span class="current-price discount-price"><?= getCurrencySymbol() ?> <?= number_format($product['sale_price'], 2, ',', '.') ?></span>
            <?php else: ?>
            <span class="current-price"><?= getCurrencySymbol() ?> <?= number_format($product['price'], 2, ',', '.') ?></span>
            <?php endif; ?>
          </div>
          <div class="product-meta">
            <span class="product-meta-item"><i class="fas fa-cube"></i> <?= $product['filament_type'] ?? 'PLA' ?></span>
            <?php if (isset($product['print_time_hours'])): ?>
            <span class="product-meta-item"><i class="fas fa-clock"></i> <?= number_format($product['print_time_hours'], 1) ?>h</span>
            <?php endif; ?>
          </div>
          <div class="product-actions">
            <a href="<?= BASE_URL ?>produto/<?= $product['slug'] ?>" class="btn btn-sm">Ver Detalhes</a>
            <form action="<?= BASE_URL ?>carrinho/adicionar" method="post" style="display:inline;">
              <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
              <input type="hidden" name="quantity" value="1">
              <button type="submit" class="btn btn-sm"><i class="fas fa-cart-plus"></i></button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    
    <div class="text-center" style="margin-top: 2rem;">
      <a href="<?= BASE_URL ?>produtos?disponibilidade=custom" class="btn">Ver Todos Produtos Sob Encomenda</a>
    </div>
  </section>
  <?php endif; ?>
  
  <!-- Diferenciais -->
  <section class="section">
    <div class="features">
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-print"></i>
        </div>
        <h3 class="feature-title">Impressão 3D de Qualidade</h3>
        <p>Utilizamos tecnologia FDM com filamentos premium e impressoras ajustadas para garantir miniaturas detalhadas e duráveis.</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-magic"></i>
        </div>
        <h3 class="feature-title">Personalização</h3>
        <p>Escolha cores, escalas e até mesmo envie seus próprios modelos 3D para impressão sob medida para suas aventuras.</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-users"></i>
        </div>
        <h3 class="feature-title">Feito por Jogadores</h3>
        <p>Somos jogadores de RPG e Board Games criando produtos que nós mesmos utilizamos em nossas mesas.</p>
      </div>
    </div>
  </section>
</div>

<!-- Estilos para novos elementos -->
<style>
.section-header {
  text-align: center;
  margin-bottom: 2rem;
}

.section-subtitle {
  color: #666;
  font-size: 1.1rem;
  margin-top: 0.5rem;
}

.product-badge-success {
  background-color: #28a745;
}

.product-badge-primary {
  background-color: #007bff;
}

.product-badge-danger {
  background-color: #dc3545;
}

.product-meta {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  font-size: 0.85rem;
  color: #666;
}

.product-meta-item {
  display: inline-flex;
  align-items: center;
}

.product-meta-item i {
  margin-right: 0.25rem;
}
</style>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>