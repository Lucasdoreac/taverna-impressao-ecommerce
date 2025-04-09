<?php
/**
 * ProductController - Controlador para páginas de produtos
 * 
 * Implementa validação consistente de entradas usando InputValidationTrait.
 */
class ProductController {
    /**
     * Incluir o trait de validação de entrada
     */
    use InputValidationTrait;
    
    private $productModel;
    private $categoryModel;
    
    public function __construct() {
        try {
            // Incluir o trait
            require_once dirname(__FILE__) . '/../lib/Security/InputValidationTrait.php';
            
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
            
            // Validar parâmetros de entrada com o novo sistema de validação
            $validatedParams = $this->getValidatedParams([
                'page' => [
                    'type' => 'int',
                    'min' => 1,
                    'default' => 1
                ],
                'categoria' => [
                    'type' => 'string',
                    'default' => null
                ],
                'personalizavel' => [
                    'type' => 'bool',
                    'default' => null
                ],
                'disponibilidade' => [
                    'type' => 'enum',
                    'allowedValues' => ['all', 'tested', 'custom'],
                    'default' => 'all'
                ],
                'ordenar' => [
                    'type' => 'enum',
                    'allowedValues' => ['preco_asc', 'preco_desc', 'nome_asc', 'nome_desc', 'recentes'],
                    'default' => 'recentes'
                ]
            ]);
            
            // Extrair os parâmetros validados
            $page = $validatedParams['page'];
            $categorySlug = $validatedParams['categoria'];
            $isCustomizable = $validatedParams['personalizavel'];
            $availability = $validatedParams['disponibilidade'];
            $orderBy = $validatedParams['ordenar'];
            
            // Número de itens por página
            $limit = 12;
            
            // Preparar condições e parâmetros para a consulta
            $conditions = "p.is_active = 1";
            $params = [];
            
            // Aplicar filtro de categoria se fornecido
            if ($categorySlug !== null) {
                $category = $this->categoryModel->getBySlug($categorySlug);
                if ($category) {
                    $conditions .= " AND p.category_id = :category_id";
                    $params['category_id'] = $category['id'];
                }
            }
            
            // Aplicar filtro de personalização se fornecido
            if ($isCustomizable !== null) {
                $conditions .= " AND p.is_customizable = :is_customizable";
                $params['is_customizable'] = $isCustomizable ? 1 : 0;
            }
            
            // Aplicar ordenação
            switch ($orderBy) {
                case 'preco_asc':
                    $orderByClause = "p.price ASC";
                    break;
                case 'preco_desc':
                    $orderByClause = "p.price DESC";
                    break;
                case 'nome_asc':
                    $orderByClause = "p.name ASC";
                    break;
                case 'nome_desc':
                    $orderByClause = "p.name DESC";
                    break;
                default: // recentes
                    $orderByClause = "p.created_at DESC";
            }
            
            // Obter produtos paginados com filtros
            $products = $this->productModel->paginate($page, $limit, $conditions, $params, $availability, $orderByClause);
            
            // Validar a estrutura dos resultados
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
            
            // Validar slug com o novo sistema de validação
            $slug = isset($params['slug']) ? $params['slug'] : null;
            $slug = $this->validateSlug($slug);
            
            if ($slug === null) {
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
            
            // Verificar se o produto é um array válido
            if (!is_array($product)) {
                error_log("Produto retornado não é um array válido");
                throw new Exception("Dados de produto inválidos");
            }
            
            // Verificar campos obrigatórios do produto
            $this->validateProductFields($product);
            
            // Garantir campos opcionais com valores padrão
            $product = $this->ensureProductDefaultValues($product);
            
            // Obter produtos relacionados
            $related_products = $this->getRelatedProducts($product);
            
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
            
            // Validar slug com o novo sistema de validação
            $slug = isset($params['slug']) ? $params['slug'] : null;
            $slug = $this->validateSlug($slug);
            
            if ($slug === null) {
                error_log("Erro: Slug de categoria vazio ou não fornecido");
                header('Location: ' . BASE_URL . 'produtos');
                exit;
            }
            
            // Validar parâmetros de entrada
            $validatedParams = $this->getValidatedParams([
                'page' => [
                    'type' => 'int',
                    'min' => 1,
                    'default' => 1
                ],
                'disponibilidade' => [
                    'type' => 'enum',
                    'allowedValues' => ['all', 'tested', 'custom'],
                    'default' => 'all'
                ]
            ]);
            
            $page = $validatedParams['page'];
            $availability = $validatedParams['disponibilidade'];
            $limit = 12;
            
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
            
            // Validar estrutura da categoria
            $category = $this->validateCategoryStructure($category, $page, $limit, $availability);
            
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
            
            // Validar parâmetros de entrada
            $validatedParams = $this->getValidatedParams([
                'q' => [
                    'type' => 'string',
                    'required' => true,
                    'minLength' => 1,
                    'requiredMessage' => 'O termo de busca é obrigatório'
                ],
                'page' => [
                    'type' => 'int',
                    'min' => 1,
                    'default' => 1
                ],
                'disponibilidade' => [
                    'type' => 'enum',
                    'allowedValues' => ['all', 'tested', 'custom'],
                    'default' => 'all'
                ]
            ]);
            
            // Se houver erros de validação, redirecionar para produtos
            if ($this->hasValidationErrors()) {
                header('Location: ' . BASE_URL . 'produtos');
                exit;
            }
            
            $query = $validatedParams['q'];
            $page = $validatedParams['page'];
            $availability = $validatedParams['disponibilidade'];
            $limit = 12;
            
            // Realizar busca com filtro de disponibilidade
            $searchResults = $this->productModel->search($query, $page, $limit, $availability);
            
            // Validar estrutura de resultados da busca
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
     * Valida um slug manualmente (método auxiliar)
     * 
     * @param string $slug Slug a ser validado
     * @return string|null Slug validado ou null se inválido
     */
    private function validateSlug($slug) {
        if (empty($slug)) {
            return null;
        }
        
        // Remover espaços e garantir apenas caracteres válidos para slug
        $slug = trim($slug);
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            return null;
        }
        
        return $slug;
    }
    
    /**
     * Valida os campos obrigatórios de um produto
     * 
     * @param array $product Dados do produto
     * @throws Exception Se campos obrigatórios estiverem ausentes
     */
    private function validateProductFields($product) {
        $requiredFields = ['id', 'name', 'price'];
        foreach ($requiredFields as $field) {
            if (!isset($product[$field])) {
                error_log("Campo obrigatório ausente no produto: " . $field);
                throw new Exception("Dados incompletos do produto: campo {$field} ausente");
            }
        }
    }
    
    /**
     * Garante que todos os campos opcionais importantes existam com valores padrão
     * 
     * @param array $product Dados do produto
     * @return array Produto com valores padrão para campos opcionais
     */
    private function ensureProductDefaultValues($product) {
        // Definir valores padrão para campos opcionais
        $defaultValues = [
            'description' => '',
            'short_description' => '',
            'sale_price' => null,
            'stock' => 0,
            'is_customizable' => 0,
            'is_tested' => 0,
            'print_time_hours' => 0,
            'filament_type' => 'PLA',
            'filament_usage_grams' => 0,
            'dimensions' => '',
            'scale' => '28mm',
            'category_name' => 'Sem categoria',
            'category_slug' => 'produtos',
            'images' => [],
            'filament_colors' => []
        ];
        
        // Aplicar valores padrão apenas para campos não definidos
        foreach ($defaultValues as $field => $value) {
            if (!isset($product[$field]) || ($field === 'images' && !is_array($product[$field])) || ($field === 'filament_colors' && !is_array($product[$field]))) {
                $product[$field] = $value;
                
                if (ENVIRONMENT === 'development') {
                    error_log("Aviso: Produto sem {$field} definido, usando valor padrão");
                }
            }
        }
        
        return $product;
    }
    
    /**
     * Obtém produtos relacionados a um produto
     * 
     * @param array $product Dados do produto
     * @return array Produtos relacionados
     */
    private function getRelatedProducts($product) {
        $related_products = [];
        
        try {
            // Verificar se temos category_id antes de tentar buscar produtos relacionados
            if (isset($product['category_id']) && !empty($product['category_id'])) {
                $related_products = $this->productModel->getRelated($product['id'], $product['category_id']);
            } else {
                if (ENVIRONMENT === 'development') {
                    error_log("Aviso: Produto sem category_id, não é possível buscar produtos relacionados");
                }
            }
        } catch (Exception $e) {
            error_log("Erro ao obter produtos relacionados: " . $e->getMessage());
        }
        
        return $related_products;
    }
    
    /**
     * Valida a estrutura de categoria e garante campos padrão
     * 
     * @param array $category Dados da categoria
     * @param int $page Página atual
     * @param int $limit Itens por página
     * @param string $availability Filtro de disponibilidade
     * @return array Categoria com estrutura validada
     */
    private function validateCategoryStructure($category, $page, $limit, $availability) {
        // Verificar se a categoria é um array válido
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
        
        return $category;
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