<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="main-banner">
  <?php if (file_exists(UPLOADS_PATH . '/banners/main-banner.jpg')): ?>
  <div class="banner-image" style="background-image: url('<?= BASE_URL ?>uploads/banners/main-banner.jpg');"></div>
  <?php else: ?>
  <div class="placeholder-banner"></div>
  <?php endif; ?>
  <div class="banner-content">
    <h1 class="banner-title">TAVERNA DA IMPRESSÃO</h1>
    <p class="banner-text">Materiais impressos para elevar sua experiência de RPG</p>
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
        <div class="category-image" style="background-image: url('<?= BASE_URL ?>uploads/categories/<?= $category['image'] ?>');"></div>
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
        <div class="product-image" style="background-image: url('<?= BASE_URL ?>uploads/products/<?= $product['image'] ?>');">
        <?php else: ?>
        <div class="product-image">
          <div class="placeholder-product" role="img" aria-label="<?= htmlspecialchars($product['name']) ?>"></div>
        <?php endif; ?>
          <?php if ($index === 0): ?>
          <span class="product-badge">DESTAQUE</span>
          <?php elseif ($index === 2): ?>
          <span class="product-badge">NOVO</span>
          <?php elseif ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
          <span class="product-badge">PROMOÇÃO</span>
          <?php endif; ?>
        </div>
        <div class="product-content">
          <h3 class="product-title"><?= $product['name'] ?></h3>
          <div class="product-price">
            <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
            <span class="original-price">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
            <span class="current-price discount-price">R$ <?= number_format($product['sale_price'], 2, ',', '.') ?></span>
            <?php else: ?>
            <span class="current-price">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
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
  
  <!-- Diferenciais -->
  <section class="section">
    <div class="features">
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-print"></i>
        </div>
        <h3 class="feature-title">Impressão de Qualidade</h3>
        <p>Utilizamos materiais premium e impressão de alta resolução para garantir o melhor resultado.</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-magic"></i>
        </div>
        <h3 class="feature-title">Personalização</h3>
        <p>Personalize seus materiais com seus próprios designs ou escolha entre nossas opções exclusivas.</p>
      </div>
      
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-truck"></i>
        </div>
        <h3 class="feature-title">Entrega Rápida</h3>
        <p>Enviamos para todo o Brasil com rapidez e segurança para você receber seus itens o quanto antes.</p>
      </div>
    </div>
  </section>
</div>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>