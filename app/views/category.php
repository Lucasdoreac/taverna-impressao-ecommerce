<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
            
            <?php if (!empty($category['breadcrumb']) && is_array($category['breadcrumb'])): ?>
                <?php foreach ($category['breadcrumb'] as $index => $item): ?>
                    <?php if ($index < count($category['breadcrumb']) - 1): ?>
                        <li class="breadcrumb-item">
                            <a href="<?= BASE_URL ?>categoria/<?= $item['slug'] ?>"><?= $item['name'] ?></a>
                        </li>
                    <?php else: ?>
                        <li class="breadcrumb-item active" aria-current="page"><?= $item['name'] ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="breadcrumb-item active"><?= $category['name'] ?></li>
            <?php endif; ?>
        </ol>
    </nav>
    
    <div class="row">
        <!-- Coluna da esquerda - Filtros e menu de categorias -->
        <div class="col-md-3 mb-4">
            <!-- Filtros -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h2 class="h5 mb-0">Filtros</h2>
                </div>
                <div class="card-body">
                    <form id="filter-form" action="<?= BASE_URL ?>categoria/<?= $category['slug'] ?>" method="get">
                        <!-- Manter os valores atuais de paginação e outras opções -->
                        <?php if (isset($_GET['page'])): ?>
                            <input type="hidden" name="page" value="<?= $_GET['page'] ?>">
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['limit'])): ?>
                            <input type="hidden" name="limit" value="<?= $_GET['limit'] ?>">
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['order_by'])): ?>
                            <input type="hidden" name="order_by" value="<?= $_GET['order_by'] ?>">
                        <?php endif; ?>
                        
                        <!-- Inclusão de subcategorias -->
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="include_subcategories" name="include_subcategories" value="1" 
                                <?= (isset($products['includeSubcategories']) && $products['includeSubcategories']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="include_subcategories">
                                Incluir subcategorias
                            </label>
                        </div>
                        
                        <hr>
                        
                        <!-- Faixa de preço -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Faixa de preço</label>
                            
                            <?php 
                            // Valores mínimo e máximo para o filtro de preço
                            $minPrice = isset($category['priceRanges']['min']) ? $category['priceRanges']['min'] : 0;
                            $maxPrice = isset($category['priceRanges']['max']) ? $category['priceRanges']['max'] : 1000;
                            
                            // Valores selecionados pelo usuário
                            $selectedMinPrice = isset($products['filters']['price_min']) ? $products['filters']['price_min'] : $minPrice;
                            $selectedMaxPrice = isset($products['filters']['price_max']) ? $products['filters']['price_max'] : $maxPrice;
                            ?>
                            
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" class="form-control" id="price_min" name="price_min" 
                                            min="<?= $minPrice ?>" max="<?= $maxPrice ?>" step="1" 
                                            value="<?= $selectedMinPrice ?>">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" class="form-control" id="price_max" name="price_max" 
                                            min="<?= $minPrice ?>" max="<?= $maxPrice ?>" step="1" 
                                            value="<?= $selectedMaxPrice ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Faixas de preço pré-definidas -->
                            <?php if (isset($category['priceRanges']['ranges']) && is_array($category['priceRanges']['ranges'])): ?>
                                <div class="mt-2">
                                    <?php foreach ($category['priceRanges']['ranges'] as $range): ?>
                                        <a href="#" class="price-range-btn badge bg-light text-dark border me-1 mb-1" 
                                           data-min="<?= $range['min'] ?>" data-max="<?= $range['max'] ?>">
                                            R$ <?= $range['min'] ?> - R$ <?= $range['max'] ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <hr>
                        
                        <!-- Disponibilidade -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Disponibilidade</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="availability" id="availability_all" value="all"
                                    <?= (!isset($products['filters']['availability']) || $products['filters']['availability'] === 'all') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="availability_all">
                                    Todos
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="availability" id="availability_in_stock" value="in_stock"
                                    <?= (isset($products['filters']['availability']) && $products['filters']['availability'] === 'in_stock') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="availability_in_stock">
                                    Pronta Entrega
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="availability" id="availability_custom" value="custom_order"
                                    <?= (isset($products['filters']['availability']) && $products['filters']['availability'] === 'custom_order') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="availability_custom">
                                    Sob Encomenda
                                </label>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Opções adicionais -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="customizable" name="customizable" value="1"
                                    <?= (isset($products['filters']['customizable']) && $products['filters']['customizable']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="customizable">
                                    Produtos personalizáveis
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="on_sale" name="on_sale" value="1"
                                    <?= (isset($products['filters']['on_sale']) && $products['filters']['on_sale']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="on_sale">
                                    Produtos em oferta
                                </label>
                            </div>
                        </div>
                        
                        <!-- Botões de ação -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">Aplicar Filtros</button>
                            <a href="<?= BASE_URL ?>categoria/<?= $category['slug'] ?>" class="btn btn-outline-secondary btn-sm">Limpar Filtros</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Menu de categorias -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h2 class="h5 mb-0">Categorias</h2>
                </div>
                <div class="card-body p-0">
                    <?php 
                    // Obter todas as categorias principais para o menu lateral
                    $categoryModel = new CategoryModel();
                    $allCategories = $categoryModel->getMainCategories(true);
                    include VIEWS_PATH . '/partials/category_menu.php'; 
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Coluna da direita - Produtos -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h2 mb-0"><?= $category['name'] ?></h1>
                
                <?php if (!empty($products['items'])): ?>
                    <span class="text-muted"><?= $products['total'] ?> produto(s) encontrado(s)</span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($category['description'])): ?>
                <p class="mb-4"><?= $category['description'] ?></p>
            <?php endif; ?>
            
            <!-- Subcategorias (mostrar apenas se não tiver filtro aplicado) -->
            <?php 
            $hasFilters = !empty($products['filters']);
            
            if (!$hasFilters && !empty($category['subcategories'])): 
            ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h2 class="h5 mb-0">Subcategorias</h2>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <?php foreach ($category['subcategories'] as $subcategory): ?>
                                <div class="col-md-4 col-6">
                                    <a href="<?= BASE_URL ?>categoria/<?= $subcategory['slug'] ?>" class="text-decoration-none">
                                        <div class="card subcategory-card h-100">
                                            <div class="card-body py-2 px-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-folder me-2 text-primary"></i>
                                                    <span><?= $subcategory['name'] ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Barra de ordenação e visualização -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <!-- Ordenação -->
                        <div class="col-md-6 mb-2 mb-md-0">
                            <form id="order-form" class="d-flex align-items-center">
                                <label for="order_by" class="me-2 text-nowrap">Ordenar por:</label>
                                <select id="order_by" name="order_by" class="form-select form-select-sm">
                                    <?php foreach ($products['orderByOptions'] as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= (isset($_GET['order_by']) && $_GET['order_by'] === $value) ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                        
                        <!-- Visualização por grid -->
                        <div class="col-md-6 d-flex justify-content-md-end">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary active" data-grid="4">
                                    <i class="fas fa-th-large"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-grid="3">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-grid="2">
                                    <i class="fas fa-th-list"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Produtos -->
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 product-grid" id="products-container">
                <?php if (count($products['items']) > 0): ?>
                    <?php foreach ($products['items'] as $product): ?>
                    <div class="col">
                        <div class="card h-100 product-card border-0 shadow-sm">
                            <div class="position-relative">
                                <?php if (isset($product['sale_price']) && $product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                <span class="position-absolute badge bg-danger top-0 start-0 m-2">OFERTA</span>
                                <?php endif; ?>
                                
                                <?php if (isset($product['availability'])): ?>
                                <span class="position-absolute badge <?= $product['availability'] === 'Pronta Entrega' ? 'bg-success' : 'bg-warning text-dark' ?> top-0 end-0 m-2">
                                    <?= $product['availability'] ?>
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($product['image'])): ?>
                                <img data-src="<?= BASE_URL ?>uploads/products/<?= $product['image'] ?>" 
                                     src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E" 
                                     class="card-img-top product-img lazy" 
                                     alt="<?= $product['name'] ?>"
                                     loading="lazy">
                                <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center product-img-placeholder">
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
                            <i class="fas fa-info-circle me-2"></i> Nenhum produto encontrado nesta categoria com os filtros aplicados.
                            
                            <?php if (!empty($products['filters'])): ?>
                                <div class="mt-2">
                                    <a href="<?= BASE_URL ?>categoria/<?= $category['slug'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-filter me-1"></i> Limpar todos os filtros
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Paginação -->
            <?php if ($products['lastPage'] > 1): ?>
            <nav aria-label="Paginação" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php 
                    // Construir URL base para paginação mantendo os filtros
                    $queryParams = $_GET;
                    unset($queryParams['page']); // Remover page para adicionar novamente
                    $queryString = http_build_query($queryParams);
                    $baseUrl = BASE_URL . 'categoria/' . $category['slug'] . '?' . $queryString;
                    $baseUrl = rtrim($baseUrl, '?'); // Remover ? vazio se não houver outros parâmetros
                    
                    // Separador de parâmetros
                    $sep = empty($queryString) ? '?' : '&';
                    ?>
                    
                    <?php if ($products['currentPage'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= $baseUrl . $sep . 'page=' . ($products['currentPage'] - 1) ?>">Anterior</a>
                    </li>
                    <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">Anterior</span>
                    </li>
                    <?php endif; ?>
                    
                    <?php 
                    // Limitar número de páginas na paginação
                    $maxPagesToShow = 5;
                    $startPage = max(1, $products['currentPage'] - floor($maxPagesToShow / 2));
                    $endPage = min($products['lastPage'], $startPage + $maxPagesToShow - 1);
                    
                    // Ajustar se estiver próximo do início ou fim
                    if ($endPage - $startPage + 1 < $maxPagesToShow) {
                        $startPage = max(1, $endPage - $maxPagesToShow + 1);
                    }
                    
                    // Mostrar primeira página e elipses se necessário
                    if ($startPage > 1) {
                        echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . $sep . 'page=1">1</a></li>';
                        if ($startPage > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    // Páginas numeradas
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?= $i == $products['currentPage'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $baseUrl . $sep . 'page=' . $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; 
                    
                    // Mostrar última página e elipses se necessário
                    if ($endPage < $products['lastPage']) {
                        if ($endPage < $products['lastPage'] - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . $sep . 'page=' . $products['lastPage'] . '">' . $products['lastPage'] . '</a></li>';
                    }
                    ?>
                    
                    <?php if ($products['currentPage'] < $products['lastPage']): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= $baseUrl . $sep . 'page=' . ($products['currentPage'] + 1) ?>">Próxima</a>
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
    </div>
</div>

<style>
.product-img {
    height: 200px;
    object-fit: cover;
}

.product-img-placeholder {
    height: 200px;
}

.product-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.subcategory-card {
    transition: background-color 0.2s;
}

.subcategory-card:hover {
    background-color: #f8f9fa;
}

.price-range-btn {
    cursor: pointer;
    transition: background-color 0.2s;
}

.price-range-btn:hover {
    background-color: #e2e6ea !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Alterar visualização da grid
    document.querySelectorAll('[data-grid]').forEach(function(button) {
        button.addEventListener('click', function() {
            // Remover classe active de todos os botões
            document.querySelectorAll('[data-grid]').forEach(function(btn) {
                btn.classList.remove('active');
            });
            
            // Adicionar classe active ao botão clicado
            this.classList.add('active');
            
            // Obter valor da grid (2, 3 ou 4 colunas)
            const gridValue = this.getAttribute('data-grid');
            
            // Obter container de produtos
            const container = document.getElementById('products-container');
            
            // Remover classes de colunas existentes
            container.classList.remove('row-cols-lg-2', 'row-cols-lg-3', 'row-cols-lg-4');
            
            // Adicionar nova classe de colunas
            container.classList.add('row-cols-lg-' + gridValue);
        });
    });
    
    // Ordenação automática ao mudar o select
    document.getElementById('order_by').addEventListener('change', function() {
        // Obter valores do formulário de filtro
        const filterForm = document.getElementById('filter-form');
        const formData = new FormData(filterForm);
        
        // Adicionar valor de ordenação
        formData.append('order_by', this.value);
        
        // Converter para query string
        const params = new URLSearchParams(formData);
        
        // Redirecionar
        window.location.href = '<?= BASE_URL ?>categoria/<?= $category['slug'] ?>?' + params.toString();
    });
    
    // Botões de faixa de preço
    document.querySelectorAll('.price-range-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Obter valores min e max
            const min = this.getAttribute('data-min');
            const max = this.getAttribute('data-max');
            
            // Atualizar inputs
            document.getElementById('price_min').value = min;
            document.getElementById('price_max').value = max;
            
            // Enviar formulário
            document.getElementById('filter-form').submit();
        });
    });
});
</script>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>