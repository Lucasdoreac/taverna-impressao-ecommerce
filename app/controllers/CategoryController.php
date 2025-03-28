<?php
/**
 * CategoryController - Controlador para páginas de categorias
 */
class CategoryController {
    private $categoryModel;
    private $productModel;
    
    public function __construct() {
        try {
            $this->categoryModel = new CategoryModel();
            $this->productModel = new ProductModel();
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao inicializar CategoryController");
        }
    }
    
    /**
     * Exibe a página de uma categoria com seus produtos
     */
    public function show($params) {
        try {
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
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/category.php')) {
                throw new Exception("View category.php não encontrada");
            }
            
            // Renderizar view
            require_once VIEWS_PATH . '/category.php';
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao exibir categoria");
        }
    }
    
    /**
     * Lista todas as categorias principais
     */
    public function index() {
        try {
            // Obter todas as categorias principais
            $categories = $this->categoryModel->getMainCategories();
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/categories.php')) {
                throw new Exception("View categories.php não encontrada");
            }
            
            // Renderizar view de categorias
            require_once VIEWS_PATH . '/categories.php';
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao listar categorias");
        }
    }
    
    /**
     * Exibe subcategorias de uma categoria principal
     */
    public function subcategories($params) {
        try {
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
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/subcategories.php')) {
                throw new Exception("View subcategories.php não encontrada");
            }
            
            // Renderizar view de subcategorias
            require_once VIEWS_PATH . '/subcategories.php';
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao exibir subcategorias");
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