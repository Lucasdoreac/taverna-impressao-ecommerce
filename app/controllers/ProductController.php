<?php
/**
 * ProductController - Controlador para páginas de produtos
 */
class ProductController {
    private $productModel;
    private $categoryModel;
    
    public function __construct() {
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
    }
    
    /**
     * Exibe a listagem de produtos
     */
    public function index() {
        // Obter parâmetros de paginação
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 12;
        
        // Obter produtos paginados
        $products = $this->productModel->paginate($page, $limit, 'is_active = 1');
        
        // Obter categorias para filtros
        $categories = $this->categoryModel->getMainCategories();
        
        // Renderizar view
        require_once VIEWS_PATH . '/products.php';
    }
    
    /**
     * Exibe a página de detalhes de um produto
     */
    public function show($params) {
        $slug = $params['slug'] ?? null;
        
        if (!$slug) {
            header('Location: ' . BASE_URL . 'produtos');
            exit;
        }
        
        // Obter produto pelo slug
        $product = $this->productModel->getBySlug($slug);
        
        if (!$product) {
            // Produto não encontrado
            header('HTTP/1.0 404 Not Found');
            require_once VIEWS_PATH . '/errors/404.php';
            exit;
        }
        
        // Obter produtos relacionados
        $related_products = $this->productModel->getRelated($product['id'], $product['category_id']);
        
        // Renderizar view
        require_once VIEWS_PATH . '/product.php';
    }
    
    /**
     * Exibe os produtos de uma categoria
     */
    public function category($params) {
        $slug = $params['slug'] ?? null;
        
        if (!$slug) {
            header('Location: ' . BASE_URL . 'produtos');
            exit;
        }
        
        // Obter parâmetros de paginação
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 12;
        
        // Obter categoria com produtos
        $category = $this->categoryModel->getCategoryWithProducts($slug, $page, $limit);
        
        if (!$category) {
            // Categoria não encontrada
            header('HTTP/1.0 404 Not Found');
            require_once VIEWS_PATH . '/errors/404.php';
            exit;
        }
        
        // Renderizar view
        require_once VIEWS_PATH . '/category.php';
    }
    
    /**
     * Busca de produtos
     */
    public function search() {
        $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        
        if (empty($query)) {
            header('Location: ' . BASE_URL . 'produtos');
            exit;
        }
        
        // Obter parâmetros de paginação
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 12;
        
        // Realizar busca
        $searchResults = $this->productModel->search($query, $page, $limit);
        
        // Renderizar view
        require_once VIEWS_PATH . '/search.php';
    }
}