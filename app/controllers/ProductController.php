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
            // Log para rastreamento
            if (ENVIRONMENT === 'development') {
                error_log("Executando ProductController::index()");
            }
            
            // Obter parâmetros de paginação
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = 12;
            
            // Obter produtos paginados
            $products = $this->productModel->paginate($page, $limit, 'is_active = 1');
            
            // Validar produtos
            if (!isset($products['items']) || !is_array($products['items'])) {
                error_log("Estrutura inválida retornada pelo método paginate");
                $products = [
                    'items' => [],
                    'total' => 0, 
                    'currentPage' => $page,
                    'perPage' => $limit,
                    'lastPage' => 1
                ];
            }
            
            // Obter categorias para filtros
            $categories = $this->categoryModel->getMainCategories();
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/products.php')) {
                throw new Exception("View products.php não encontrada em " . VIEWS_PATH);
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
            // Log para rastreamento
            if (ENVIRONMENT === 'development') {
                error_log("Executando ProductController::show() com parâmetros: " . json_encode($params));
            }
            
            // Validar slug
            $slug = isset($params['slug']) ? trim($params['slug']) : null;
            
            if (empty($slug)) {
                error_log("Erro: Slug vazio ou não fornecido");
                header('Location: ' . BASE_URL . 'produtos');
                exit;
            }
            
            // Log do slug para debug
            if (ENVIRONMENT === 'development') {
                error_log("Buscando produto com slug: " . $slug);
            }
            
            // Obter produto pelo slug
            $product = $this->productModel->getBySlug($slug);
            
            // Debug do resultado para rastreamento
            if (ENVIRONMENT === 'development') {
                error_log("Resultado da busca por slug: " . ($product ? "Produto encontrado (ID: {$product['id']})" : "Produto não encontrado"));
            }
            
            if (!$product) {
                // Log do erro
                error_log("Produto não encontrado para o slug: " . $slug);
                
                // Produto não encontrado
                header('HTTP/1.0 404 Not Found');
                require_once VIEWS_PATH . '/errors/404.php';
                exit;
            }
            
            // CORREÇÃO: Verificar se o produto é um array antes de tentar acessá-lo
            if (!is_array($product)) {
                error_log("Produto retornado não é um array válido");
                throw new Exception("Dados de produto inválidos");
            }
            
            // Verificar campos obrigatórios do produto
            $requiredFields = ['id', 'name', 'price'];
            foreach ($requiredFields as $field) {
                if (!isset($product[$field])) {
                    error_log("Campo obrigatório ausente no produto: " . $field);
                    throw new Exception("Dados incompletos do produto: campo {$field} ausente");
                }
            }
            
            // CORREÇÃO: Garantir que todos os campos opcionais importantes existam com valores padrão
            $product['description'] = isset($product['description']) ? $product['description'] : '';
            $product['short_description'] = isset($product['short_description']) ? $product['short_description'] : '';
            $product['sale_price'] = isset($product['sale_price']) ? $product['sale_price'] : null;
            $product['stock'] = isset($product['stock']) ? $product['stock'] : 0;
            $product['is_customizable'] = isset($product['is_customizable']) ? $product['is_customizable'] : 0;
            
            // Valores padrão para campos importantes mas não obrigatórios
            if (!isset($product['category_name'])) {
                $product['category_name'] = 'Sem categoria';
                error_log("Aviso: Produto sem category_name definido");
            }
            
            if (!isset($product['category_slug'])) {
                $product['category_slug'] = 'produtos';
                error_log("Aviso: Produto sem category_slug definido");
            }
            
            if (!isset($product['images']) || !is_array($product['images'])) {
                $product['images'] = [];
                error_log("Aviso: Produto sem imagens");
            }
            
            // CORREÇÃO: Tratamento mais robusto para produtos relacionados
            $related_products = [];
            try {
                // Verificar se temos category_id antes de tentar buscar produtos relacionados
                if (isset($product['category_id']) && !empty($product['category_id'])) {
                    $related_products = $this->productModel->getRelated($product['id'], $product['category_id']);
                } else {
                    error_log("Aviso: Produto sem category_id, não é possível buscar produtos relacionados");
                }
            } catch (Exception $e) {
                error_log("Erro ao obter produtos relacionados: " . $e->getMessage());
            }
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/product.php')) {
                throw new Exception("View product.php não encontrada em " . VIEWS_PATH);
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
            // Log para rastreamento
            if (ENVIRONMENT === 'development') {
                error_log("Executando ProductController::category() com parâmetros: " . json_encode($params));
            }
            
            $slug = isset($params['slug']) ? trim($params['slug']) : null;
            
            if (empty($slug)) {
                error_log("Erro: Slug de categoria vazio ou não fornecido");
                header('Location: ' . BASE_URL . 'produtos');
                exit;
            }
            
            // Log do slug para debug
            if (ENVIRONMENT === 'development') {
                error_log("Buscando categoria com slug: " . $slug);
            }
            
            // Obter parâmetros de paginação
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = 12;
            
            // Obter categoria com produtos
            $category = $this->categoryModel->getCategoryWithProducts($slug, $page, $limit);
            
            // Debug do resultado para rastreamento
            if (ENVIRONMENT === 'development') {
                error_log("Resultado da busca por categoria: " . ($category ? "Categoria encontrada (ID: {$category['id']})" : "Categoria não encontrada"));
            }
            
            if (!$category) {
                // Log do erro
                error_log("Categoria não encontrada para o slug: " . $slug);
                
                // Categoria não encontrada
                header('HTTP/1.0 404 Not Found');
                require_once VIEWS_PATH . '/errors/404.php';
                exit;
            }
            
            // CORREÇÃO: Verificar se a categoria é um array válido
            if (!is_array($category)) {
                error_log("Categoria retornada não é um array válido");
                throw new Exception("Dados de categoria inválidos");
            }
            
            // Validar estrutura da categoria
            if (!isset($category['products']) || !is_array($category['products'])) {
                error_log("Dados de produtos ausentes na resposta do getCategoryWithProducts");
                $category['products'] = [
                    'items' => [],
                    'total' => 0,
                    'currentPage' => $page,
                    'perPage' => $limit,
                    'lastPage' => 1
                ];
            } else {
                // Validar a estrutura completa de produtos
                if (!isset($category['products']['items'])) {
                    $category['products']['items'] = [];
                }
                if (!isset($category['products']['total'])) {
                    $category['products']['total'] = 0;
                }
                if (!isset($category['products']['currentPage'])) {
                    $category['products']['currentPage'] = $page;
                }
                if (!isset($category['products']['perPage'])) {
                    $category['products']['perPage'] = $limit;
                }
                if (!isset($category['products']['lastPage'])) {
                    $category['products']['lastPage'] = 1;
                }
            }
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/category.php')) {
                throw new Exception("View category.php não encontrada em " . VIEWS_PATH);
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
            // Log para rastreamento
            if (ENVIRONMENT === 'development') {
                error_log("Executando ProductController::search()");
            }
            
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
            
            // CORREÇÃO: Validar estrutura de resultados da busca
            if (!isset($searchResults['items']) || !is_array($searchResults['items'])) {
                error_log("Estrutura inválida retornada pelo método search");
                $searchResults = [
                    'items' => [],
                    'total' => 0,
                    'currentPage' => $page,
                    'perPage' => $limit,
                    'lastPage' => 1,
                    'query' => $query
                ];
            }
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/search.php')) {
                throw new Exception("View search.php não encontrada em " . VIEWS_PATH);
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
        $error_message = ENVIRONMENT === 'development' ? $e->getMessage() : 'Ocorreu um erro interno. Por favor, tente novamente mais tarde.';
        $error_trace = ENVIRONMENT === 'development' ? $e->getTraceAsString() : '';
        $error_context = ENVIRONMENT === 'development' ? $context : '';
        
        // Renderizar página de erro
        header("HTTP/1.0 500 Internal Server Error");
        
        // Verificar se a view de erro existe
        if (file_exists(VIEWS_PATH . '/errors/500.php')) {
            include VIEWS_PATH . '/errors/500.php';
        } else {
            // Fallback para erro simples se a view não existir
            echo '<h1>Erro 500 - Erro Interno do Servidor</h1>';
            
            if (ENVIRONMENT === 'development') {
                echo '<h2>' . htmlspecialchars($context) . '</h2>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            } else {
                echo '<p>Ocorreu um erro interno. Por favor, tente novamente mais tarde.</p>';
            }
        }
        
        exit;
    }
}