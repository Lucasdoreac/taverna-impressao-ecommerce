<?php
/**
 * ProductController - Controlador para páginas de produtos
 */
class ProductController {
    private $productModel;
    private $categoryModel;
    
    public function __construct() {
        try {
            $this->productModel = new ProductModel();
            $this->categoryModel = new CategoryModel();
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao inicializar ProductController");
        }
    }
    
    /**
     * Exibe a listagem de produtos
     */
    public function index() {
        try {
            // Obter parâmetros de paginação
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = 12;
            
            // Obter produtos paginados
            $products = $this->productModel->paginate($page, $limit, 'is_active = 1');
            
            // Obter categorias para filtros
            $categories = $this->categoryModel->getMainCategories();
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/products.php')) {
                throw new Exception("View products.php não encontrada");
            }
            
            // Renderizar view
            require_once VIEWS_PATH . '/products.php';
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao listar produtos");
        }
    }
    
    /**
     * Exibe a página de detalhes de um produto
     */
    public function show($params) {
        try {
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
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/product.php')) {
                throw new Exception("View product.php não encontrada");
            }
            
            // Renderizar view
            require_once VIEWS_PATH . '/product.php';
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao exibir produto");
        }
    }
    
    /**
     * Exibe os produtos de uma categoria
     */
    public function category($params) {
        try {
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
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/category.php')) {
                throw new Exception("View category.php não encontrada");
            }
            
            // Renderizar view
            require_once VIEWS_PATH . '/category.php';
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao exibir produtos da categoria");
        }
    }
    
    /**
     * Busca de produtos
     */
    public function search() {
        try {
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
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/search.php')) {
                throw new Exception("View search.php não encontrada");
            }
            
            // Renderizar view
            require_once VIEWS_PATH . '/search.php';
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao buscar produtos");
        }
    }
    
    /**
     * Tratamento de erros centralizado
     */
    private function handleError(Exception $e, $context = '') {
        // Registrar erro no log
        error_log("$context: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Variáveis para a view de erro (visíveis apenas em ambiente de desenvolvimento)
        $error_message = $e->getMessage();
        $error_trace = $e->getTraceAsString();
        
        // Renderizar página de erro
        header("HTTP/1.0 500 Internal Server Error");
        include VIEWS_PATH . '/errors/500.php';
        exit;
    }
}