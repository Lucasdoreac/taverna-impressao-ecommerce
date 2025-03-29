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
            
            // Obter parâmetros de paginação e filtros
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = 12;
            
            // Processar parâmetros de filtragem
            $conditions = "p.is_active = 1";
            $params = [];
            $orderBy = "p.created_at DESC"; // Padrão: mais recentes
            
            // Filtro de categoria
            if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
                $categorySlug = trim($_GET['categoria']);
                $category = $this->categoryModel->getBySlug($categorySlug);
                if ($category) {
                    $conditions .= " AND p.category_id = :category_id";
                    $params['category_id'] = $category['id'];
                }
            }
            
            // Filtro de personalização
            if (isset($_GET['personalizavel']) && $_GET['personalizavel'] !== '') {
                $isCustomizable = (int)$_GET['personalizavel'];
                $conditions .= " AND p.is_customizable = :is_customizable";
                $params['is_customizable'] = $isCustomizable;
            }
            
            // Filtro de disponibilidade (novo)
            $availability = isset($_GET['disponibilidade']) ? trim($_GET['disponibilidade']) : 'all';
            if (!in_array($availability, ['all', 'tested', 'custom'])) {
                $availability = 'all';
            }
            
            // Ordenação
            if (isset($_GET['ordenar'])) {
                switch ($_GET['ordenar']) {
                    case 'preco_asc':
                        $orderBy = "p.price ASC";
                        break;
                    case 'preco_desc':
                        $orderBy = "p.price DESC";
                        break;
                    case 'nome_asc':
                        $orderBy = "p.name ASC";
                        break;
                    case 'nome_desc':
                        $orderBy = "p.name DESC";
                        break;
                    default:
                        $orderBy = "p.created_at DESC";
                }
            }
            
            // Obter produtos paginados com filtros
            $products = $this->productModel->paginate($page, $limit, $conditions, $params, $availability, $orderBy);
            
            // Validar produtos
            if (!isset($products['items']) || !is_array($products['items'])) {
                error_log("Estrutura inválida retornada pelo método paginate");
                $products = [
                    'items' => [],
                    'total' => 0, 
                    'currentPage' => $page,
                    'perPage' => $limit,
                    'lastPage' => 1,
                    'availability' => $availability
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
            $product['is_tested'] = isset($product['is_tested']) ? $product['is_tested'] : 0;
            $product['print_time_hours'] = isset($product['print_time_hours']) ? $product['print_time_hours'] : 0;
            $product['filament_type'] = isset($product['filament_type']) ? $product['filament_type'] : 'PLA';
            $product['filament_usage_grams'] = isset($product['filament_usage_grams']) ? $product['filament_usage_grams'] : 0;
            $product['dimensions'] = isset($product['dimensions']) ? $product['dimensions'] : '';
            $product['scale'] = isset($product['scale']) ? $product['scale'] : '28mm';
            
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
            
            if (!isset($product['filament_colors']) || !is_array($product['filament_colors'])) {
                $product['filament_colors'] = [];
                error_log("Aviso: Produto sem cores de filamento definidas");
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
            
            // Obter parâmetros de paginação e filtros
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = 12;
            
            // Filtro de disponibilidade (novo)
            $availability = isset($_GET['disponibilidade']) ? trim($_GET['disponibilidade']) : 'all';
            if (!in_array($availability, ['all', 'tested', 'custom'])) {
                $availability = 'all';
            }
            
            // Obter categoria
            $category = $this->categoryModel->getBySlug($slug);
            if (!$category) {
                // Log do erro
                error_log("Categoria não encontrada para o slug: " . $slug);
                
                // Categoria não encontrada
                header('HTTP/1.0 404 Not Found');
                require_once VIEWS_PATH . '/errors/404.php';
                exit;
            }
            
            // Obter produtos da categoria com paginação e filtro de disponibilidade
            $categoryProducts = $this->productModel->getByCategory($category['id'], $page, $limit, $availability);
            $category['products'] = $categoryProducts;
            
            // Debug do resultado para rastreamento
            if (ENVIRONMENT === 'development') {
                error_log("Resultado da busca por categoria: " . ($category ? "Categoria encontrada (ID: {$category['id']})" : "Categoria não encontrada"));
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
                    'lastPage' => 1,
                    'availability' => $availability
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
                if (!isset($category['products']['availability'])) {
                    $category['products']['availability'] = $availability;
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
            
            // Filtro de disponibilidade (novo)
            $availability = isset($_GET['disponibilidade']) ? trim($_GET['disponibilidade']) : 'all';
            if (!in_array($availability, ['all', 'tested', 'custom'])) {
                $availability = 'all';
            }
            
            // Realizar busca com filtro de disponibilidade
            $searchResults = $this->productModel->search($query, $page, $limit, $availability);
            
            // CORREÇÃO: Validar estrutura de resultados da busca
            if (!isset($searchResults['items']) || !is_array($searchResults['items'])) {
                error_log("Estrutura inválida retornada pelo método search");
                $searchResults = [
                    'items' => [],
                    'total' => 0,
                    'currentPage' => $page,
                    'perPage' => $limit,
                    'lastPage' => 1,
                    'query' => $query,
                    'availability' => $availability
                ];
            }
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/search.php')) {
                throw new Exception("View search.php não encontrada em " . VIEWS_PATH);
            }
            
            // Renderizar view
            $searchQuery = $query; // Disponibilizar para a view
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