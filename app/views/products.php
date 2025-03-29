<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <h1 class="h2 mb-4">Produtos</h1>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="<?= BASE_URL ?>produtos" method="get" class="row row-cols-1 row-cols-md-4 g-3">
                <?php if (isset($_GET['q'])): ?>
                <input type="hidden" name="q" value="<?= htmlspecialchars($_GET['q']) ?>">
                <?php endif; ?>
                
                <div class="col">
                    <label for="categoria" class="form-label">Categoria</label>
                    <select name="categoria" id="categoria" class="form-select">
                        <option value="">Todas as categorias</option>
                        <?php 
                        $categoryModel = new CategoryModel();
                        $categories = $categoryModel->getMainCategories();
                        foreach ($categories as $cat): 
                        $selected = (isset($_GET['categoria']) && $_GET['categoria'] == $cat['slug']) ? 'selected' : '';
                        ?>
                        <option value="<?= $cat['slug'] ?>" <?= $selected ?>><?= $cat['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col">
                    <label for="ordenar" class="form-label">Ordenar por</label>
                    <select name="ordenar" id="ordenar" class="form-select">
                        <option value="recentes" <?= (!isset($_GET['ordenar']) || $_GET['ordenar'] == 'recentes') ? 'selected' : '' ?>>Mais recentes</option>
                        <option value="preco_asc" <?= (isset($_GET['ordenar']) && $_GET['ordenar'] == 'preco_asc') ? 'selected' : '' ?>>Menor preço</option>
                        <option value="preco_desc" <?= (isset($_GET['ordenar']) && $_GET['ordenar'] == 'preco_desc') ? 'selected' : '' ?>>Maior preço</option>
                        <option value="nome_asc" <?= (isset($_GET['ordenar']) && $_GET['ordenar'] == 'nome_asc') ? 'selected' : '' ?>>Nome (A-Z)</option>
                        <option value="nome_desc" <?= (isset($_GET['ordenar']) && $_GET['ordenar'] == 'nome_desc') ? 'selected' : '' ?>>Nome (Z-A)</option>
                    </select>
                </div>
                
                <div class="col">
                    <label for="personalizavel" class="form-label">Personalização</label>
                    <select name="personalizavel" id="personalizavel" class="form-select">
                        <option value="">Todos os produtos</option>
                        <option value="1" <?= (isset($_GET['personalizavel']) && $_GET['personalizavel'] == '1') ? 'selected' : '' ?>>Personalizáveis</option>
                        <option value="0" <?= (isset($_GET['personalizavel']) && $_GET['personalizavel'] == '0') ? 'selected' : '' ?>>Não personalizáveis</option>
                    </select>
                </div>
                
                <div class="col d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Resultados da busca se aplicável -->
    <?php if (isset($searchQuery) && !empty($searchQuery)): ?>
    <div class="alert alert-info mb-4">
        <p class="mb-0">Resultados da busca por: <strong><?= htmlspecialchars($searchQuery) ?></strong></p>
        <p class="mb-0 mt-1">Encontrados <?= $products['total'] ?> produtos</p>
    </div>
    <?php endif; ?>
    
    <!-- Lista de produtos -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-4">
        <?php if (isset($products['items']) && count($products['items']) > 0): ?>
            <?php foreach ($products['items'] as $product): ?>
            <div class="col">
                <div class="card h-100 product-card border-0 shadow-sm">
                    <div class="position-relative">
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                        <span class="position-absolute badge bg-danger top-0 start-0 m-2">OFERTA</span>
                        <?php endif; ?>
                        
                        <?php if ($product['is_customizable']): ?>
                        <span class="position-absolute badge bg-primary top-0 end-0 m-2" title="Produto personalizável">
                            <i class="bi bi-brush"></i>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($product['image']) && file_exists(UPLOADS_PATH . '/products/' . $product['image'])): ?>
                        <img src="<?= BASE_URL ?>uploads/products/<?= $product['image'] ?>" class="card-img-top" alt="<?= $product['name'] ?>">
                        <?php else: ?>
                        <div class="placeholder-product" role="img" aria-label="<?= htmlspecialchars($product['name']) ?>"></div>
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
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    Nenhum produto encontrado com os filtros atuais.
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Paginação -->
    <?php if (isset($products['lastPage']) && $products['lastPage'] > 1): ?>
    <nav aria-label="Paginação" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php 
            $queryParams = $_GET;
            unset($queryParams['page']);
            $queryString = http_build_query($queryParams);
            $queryPrefix = empty($queryString) ? '?' : '?' . $queryString . '&';
            ?>
            
            <?php if ($products['currentPage'] > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= BASE_URL ?>produtos<?= $queryPrefix ?>page=<?= $products['currentPage'] - 1 ?>">Anterior</a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">Anterior</span>
            </li>
            <?php endif; ?>
            
            <?php for ($i = max(1, $products['currentPage'] - 2); $i <= min($products['lastPage'], $products['currentPage'] + 2); $i++): ?>
            <li class="page-item <?= $i == $products['currentPage'] ? 'active' : '' ?>">
                <a class="page-link" href="<?= BASE_URL ?>produtos<?= $queryPrefix ?>page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            
            <?php if ($products['currentPage'] < $products['lastPage']): ?>
            <li class="page-item">
                <a class="page-link" href="<?= BASE_URL ?>produtos<?= $queryPrefix ?>page=<?= $products['currentPage'] + 1 ?>">Próxima</a>
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