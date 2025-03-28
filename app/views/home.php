<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="main-banner" style="background-image: url('https://images.unsplash.com/photo-1605106702734-205df224ecce?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80');">
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
      <a href="<?= BASE_URL ?>categoria/fichas-de-personagem" class="category-card">
        <div class="category-image" style="background-image: url('https://images.unsplash.com/photo-1604343574184-6869125c2b0f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');"></div>
        <div class="category-title">Fichas de Personagem</div>
      </a>
      <a href="<?= BASE_URL ?>categoria/mapas-de-aventura" class="category-card">
        <div class="category-image" style="background-image: url('https://images.unsplash.com/photo-1613246558842-b41dbf111262?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');"></div>
        <div class="category-title">Mapas de Aventura</div>
      </a>
      <a href="<?= BASE_URL ?>categoria/livros-e-modulos" class="category-card">
        <div class="category-image" style="background-image: url('https://images.unsplash.com/photo-1490633874781-1c63cc424610?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');"></div>
        <div class="category-title">Livros e Módulos</div>
      </a>
      <a href="<?= BASE_URL ?>categoria/telas-do-mestre" class="category-card">
        <div class="category-image" style="background-image: url('https://images.unsplash.com/photo-1518791841217-8f162f1e1131?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');"></div>
        <div class="category-title">Telas do Mestre</div>
      </a>
    </div>
  </section>

  <!-- Produtos em Destaque -->
  <section class="section">
    <h2 class="section-title">Produtos em Destaque</h2>
    <div class="products-grid">
      <!-- Produto 1 -->
      <div class="product-card">
        <div class="product-image" style="background-image: url('https://images.unsplash.com/photo-1634898180359-f2e3dfca7d1a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');">
          <span class="product-badge">DESTAQUE</span>
        </div>
        <div class="product-content">
          <h3 class="product-title">Ficha de Personagem D&D 5e - Premium</h3>
          <div class="product-price">
            <span class="original-price">R$ 29,90</span>
            <span class="current-price discount-price">R$ 24,90</span>
          </div>
          <p>Ficha premium personalizada para D&D 5e, com acabamento especial.</p>
          <div class="product-actions">
            <a href="<?= BASE_URL ?>produto/ficha-personagem-dd-5e-premium" class="btn btn-sm">Ver Detalhes</a>
            <button class="btn btn-sm"><i class="fas fa-cart-plus"></i></button>
          </div>
        </div>
      </div>
      
      <!-- Produto 2 -->
      <div class="product-card">
        <div class="product-image" style="background-image: url('https://images.unsplash.com/photo-1519074069444-1ba4fff66d16?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');">
        </div>
        <div class="product-content">
          <h3 class="product-title">Mapa de Dungeon - Cripta Ancestral</h3>
          <div class="product-price">
            <span class="current-price">R$ 39,90</span>
          </div>
          <p>Mapa detalhado de uma cripta ancestral, perfeito para aventuras.</p>
          <div class="product-actions">
            <a href="<?= BASE_URL ?>produto/mapa-dungeon-cripta-ancestral" class="btn btn-sm">Ver Detalhes</a>
            <button class="btn btn-sm"><i class="fas fa-cart-plus"></i></button>
          </div>
        </div>
      </div>
      
      <!-- Produto 3 -->
      <div class="product-card">
        <div class="product-image" style="background-image: url('https://images.unsplash.com/photo-1577083553270-5efd990c0031?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');">
          <span class="product-badge">NOVO</span>
        </div>
        <div class="product-content">
          <h3 class="product-title">Tela do Mestre Personalizada</h3>
          <div class="product-price">
            <span class="current-price">R$ 79,90</span>
          </div>
          <p>Tela do mestre personalizada com informações úteis e arte exclusiva.</p>
          <div class="product-actions">
            <a href="<?= BASE_URL ?>produto/tela-mestre-personalizada" class="btn btn-sm">Ver Detalhes</a>
            <button class="btn btn-sm"><i class="fas fa-cart-plus"></i></button>
          </div>
        </div>
      </div>
      
      <!-- Produto 4 -->
      <div class="product-card">
        <div class="product-image" style="background-image: url('https://images.unsplash.com/photo-1456086272160-b28b0645b729?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80');">
          <span class="product-badge">PROMOÇÃO</span>
        </div>
        <div class="product-content">
          <h3 class="product-title">Kit de Tokens para RPG</h3>
          <div class="product-price">
            <span class="original-price">R$ 45,90</span>
            <span class="current-price discount-price">R$ 35,90</span>
          </div>
          <p>Kit completo com 50 tokens para representar personagens e inimigos.</p>
          <div class="product-actions">
            <a href="<?= BASE_URL ?>produto/kit-tokens-rpg" class="btn btn-sm">Ver Detalhes</a>
            <button class="btn btn-sm"><i class="fas fa-cart-plus"></i></button>
          </div>
        </div>
      </div>
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