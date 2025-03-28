<?php
/**
 * CategoryController - Controlador para páginas de categorias
 */
class CategoryController {
    private $categoryModel;
    private $productModel;
    
    public function __construct() {
        $this->categoryModel = new CategoryModel();
        $this->productModel = new ProductModel();
    }
    
    /**
     * Exibe a página de uma categoria com seus produtos
     */
    public function show($params) {
        $slug = $params['slug'] ?? '';
        
        if (empty($slug)) {
            header('Location: ' . BASE_URL);
            exit;
        }
        
        // Obter categoria pelo slug
        $category = $this->categoryModel->getBySlug($slug);
        
        if (!$category) {
            // Categoria não encontrada
            header("HTTP/1.0 404 Not Found");
            include VIEWS_PATH . '/errors/404.php';
            exit;
        }
        
        // Obter produtos da categoria com paginação
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 12; // produtos por página
        $products = $this->productModel->getByCategory($category['id'], $page, $limit);
        
        // Renderizar view
        require_once VIEWS_PATH . '/category.php';
    }
    
    /**
     * Lista todas as categorias principais
     */
    public function index() {
        // Obter todas as categorias principais
        $categories = $this->categoryModel->getMainCategories();
        
        // Renderizar view de categorias
        require_once VIEWS_PATH . '/categories.php';
    }
    
    /**
     * Exibe subcategorias de uma categoria principal
     */
    public function subcategories($params) {
        $slug = $params['slug'] ?? '';
        
        if (empty($slug)) {
            header('Location: ' . BASE_URL);
            exit;
        }
        
        // Obter categoria principal pelo slug
        $category = $this->categoryModel->getBySlug($slug);
        
        if (!$category) {
            // Categoria não encontrada
            header("HTTP/1.0 404 Not Found");
            include VIEWS_PATH . '/errors/404.php';
            exit;
        }
        
        // Obter subcategorias
        $subcategories = $this->categoryModel->getSubcategories($category['id']);
        
        if (empty($subcategories)) {
            // Se não houver subcategorias, redirecionar direto para produtos da categoria
            header('Location: ' . BASE_URL . 'categoria/' . $slug);
            exit;
        }
        
        // Renderizar view de subcategorias
        require_once VIEWS_PATH . '/subcategories.php';
    }
}