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
                <li class="breadcrumb-item active" aria-current="page"><?= $category['name'] ?></li>
            <?php endif; ?>
        </ol>
    </nav>

    <div class="row">
        <!-- Coluna da esquerda - Menu de categorias -->
        <div class="col-md-3 mb-4">
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
        
        <!-- Coluna da direita - Subcategorias -->
        <div class="col-md-9">
            <h1 class="h2 mb-4"><?= $category['name'] ?></h1>
            
            <?php if (!empty($category['description'])): ?>
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <p class="mb-0"><?= $category['description'] ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <h2 class="h4 mb-3">Subcategorias de <?= $category['name'] ?></h2>
            
            <?php if (!empty($category['subcategories'])): ?>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($category['subcategories'] as $subcategory): ?>
                        <div class="col">
                            <a href="<?= BASE_URL ?>categoria/<?= $subcategory['slug'] ?>" class="text-decoration-none">
                                <div class="card h-100 border-0 shadow-sm category-card">
                                    <?php if (!empty($subcategory['image'])): ?>
                                        <img src="<?= BASE_URL ?>uploads/categories/<?= $subcategory['image'] ?>" class="card-img-top category-image" alt="<?= $subcategory['name'] ?>">
                                    <?php else: ?>
                                        <div class="card-img-top category-placeholder d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-folder fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-body">
                                        <h3 class="card-title h5"><?= $subcategory['name'] ?></h3>
                                        
                                        <?php if (!empty($subcategory['description'])): ?>
                                            <p class="card-text small"><?= mb_strimwidth($subcategory['description'], 0, 100, '...') ?></p>
                                        <?php endif; ?>
                                        
                                        <?php 
                                        // Informação sobre subcategorias aninhadas
                                        $hasChildren = !empty($subcategory['subcategories']);
                                        if ($hasChildren): 
                                        ?>
                                            <div class="mt-2">
                                                <span class="badge bg-primary">
                                                    <?= count($subcategory['subcategories']) ?> subcategoria<?= count($subcategory['subcategories']) > 1 ? 's' : '' ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-footer bg-transparent border-0 text-end">
                                        <span class="btn btn-sm btn-outline-primary">Ver produtos</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Nenhuma subcategoria encontrada.</div>
            <?php endif; ?>
            
            <!-- CTA para ver todos os produtos da categoria atual -->
            <div class="text-center mt-5">
                <p class="lead">Deseja ver todos os produtos desta categoria?</p>
                <a href="<?= BASE_URL ?>categoria/<?= $category['slug'] ?>?include_subcategories=1" class="btn btn-primary">
                    Ver todos os produtos
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.category-placeholder {
    height: 160px;
}

.category-image {
    height: 160px;
    object-fit: cover;
}

.category-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}
</style>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
