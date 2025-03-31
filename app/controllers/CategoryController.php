<?php
/**
 * CategoryController - Controlador para páginas de categorias
 */
class CategoryController extends Controller {
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
            // Log para rastreamento
            if (ENVIRONMENT === 'development') {
                error_log("Executando CategoryController::show() com parâmetros: " . json_encode($params));
            }
            
            $slug = isset($params['slug']) ? trim($params['slug']) : '';
            
            if (empty($slug)) {
                error_log("Erro: Slug de categoria vazio ou não fornecido");
                header('Location: ' . BASE_URL);
                exit;
            }
            
            // Log do slug para debug
            if (ENVIRONMENT === 'development') {
                error_log("Buscando categoria com slug: " . $slug);
            }
            
            // Obter categoria pelo slug com subcategorias recursivas
            $category = $this->categoryModel->getBySlug($slug, true);
            
            // Debug do resultado para rastreamento
            if (ENVIRONMENT === 'development') {
                error_log("Resultado da busca por categoria: " . ($category ? "Categoria encontrada (ID: {$category['id']})" : "Categoria não encontrada"));
            }
            
            if (!$category) {
                // Log do erro
                error_log("Categoria não encontrada para o slug: " . $slug);
                
                // Categoria não encontrada
                header("HTTP/1.0 404 Not Found");
                include VIEWS_PATH . '/errors/404.php';
                exit;
            }
            
            // CORREÇÃO: Verificar se a categoria é um array válido
            if (!is_array($category)) {
                error_log("Categoria retornada não é um array válido");
                throw new Exception("Dados de categoria inválidos");
            }
            
            // Verificar campos obrigatórios da categoria
            $requiredFields = ['id', 'name', 'slug'];
            foreach ($requiredFields as $field) {
                if (!isset($category[$field])) {
                    error_log("Campo obrigatório ausente na categoria: " . $field);
                    throw new Exception("Dados incompletos da categoria: campo {$field} ausente");
                }
            }
            
            // CORREÇÃO: Garantir que todos os campos opcionais importantes existam com valores padrão
            $category['description'] = isset($category['description']) ? $category['description'] : '';
            $category['image'] = isset($category['image']) ? $category['image'] : '';
            
            // Verificar subcategorias
            if (!isset($category['subcategories']) || !is_array($category['subcategories'])) {
                $category['subcategories'] = [];
                error_log("Aviso: Categoria sem subcategorias ou não é array");
            }
            
            // Obter parâmetros de paginação, ordenação e filtragem dos query params
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? min(48, max(4, intval($_GET['limit']))) : 12;
            
            // Processar inclusão de subcategorias
            $includeSubcategories = isset($_GET['include_subcategories']) ? 
                                    filter_var($_GET['include_subcategories'], FILTER_VALIDATE_BOOLEAN) : 
                                    true;
            
            // Processar ordenação
            $validOrderBy = [
                'name_asc' => 'name ASC',
                'name_desc' => 'name DESC',
                'price_asc' => 'price ASC',
                'price_desc' => 'price DESC',
                'newest' => 'newest DESC',
                'availability' => 'availability DESC',
                'default' => 'p.is_tested DESC, p.name ASC'
            ];
            
            $orderBy = isset($_GET['order_by']) && isset($validOrderBy[$_GET['order_by']]) ? 
                       $validOrderBy[$_GET['order_by']] : 
                       $validOrderBy['default'];
            
            // Processar filtros
            $filters = [];
            
            // Filtro de preço
            if (isset($_GET['price_min']) && is_numeric($_GET['price_min'])) {
                $filters['price_min'] = floatval($_GET['price_min']);
            }
            
            if (isset($_GET['price_max']) && is_numeric($_GET['price_max'])) {
                $filters['price_max'] = floatval($_GET['price_max']);
            }
            
            // Filtro de disponibilidade
            if (isset($_GET['availability']) && in_array($_GET['availability'], ['all', 'in_stock', 'custom_order'])) {
                $filters['availability'] = $_GET['availability'];
            }
            
            // Filtro para produtos personalizáveis
            if (isset($_GET['customizable'])) {
                $filters['customizable'] = filter_var($_GET['customizable'], FILTER_VALIDATE_BOOLEAN);
            }
            
            // Filtro para produtos em oferta
            if (isset($_GET['on_sale'])) {
                $filters['on_sale'] = filter_var($_GET['on_sale'], FILTER_VALIDATE_BOOLEAN);
            }
            
            // Log dos parâmetros de filtragem para debug
            if (ENVIRONMENT === 'development') {
                error_log("Parâmetros de filtragem: " . json_encode([
                    'page' => $page, 
                    'limit' => $limit, 
                    'orderBy' => $orderBy, 
                    'includeSubcategories' => $includeSubcategories,
                    'filters' => $filters
                ]));
            }
            
            // Obter produtos da categoria com paginação, ordenação e filtragem
            try {
                // Verificar se a categoria já tem produtos carregados
                if (!isset($category['products']) || !is_array($category['products'])) {
                    $categoryWithProducts = $this->categoryModel->getCategoryWithProducts(
                        $slug, 
                        $page, 
                        $limit, 
                        $includeSubcategories,
                        $orderBy,
                        $filters
                    );
                    
                    if ($categoryWithProducts && isset($categoryWithProducts['products']) && is_array($categoryWithProducts['products'])) {
                        $products = $categoryWithProducts['products'];
                    } else {
                        // Fallback para método antigo se getCategoryWithProducts falhar
                        error_log("Aviso: getCategoryWithProducts falhou, usando getByCategory como fallback");
                        $products = $this->productModel->getByCategory($category['id'], $page, $limit);
                    }
                } else {
                    $products = $category['products'];
                }
                
                // CORREÇÃO: Validar a estrutura de produtos para garantir que todos os campos necessários existam
                if (!isset($products['items'])) {
                    error_log("Estrutura de produtos inválida: 'items' ausente");
                    $products['items'] = [];
                }
                
                if (!isset($products['total'])) {
                    error_log("Estrutura de produtos inválida: 'total' ausente");
                    $products['total'] = 0;
                }
                
                if (!isset($products['currentPage'])) {
                    error_log("Estrutura de produtos inválida: 'currentPage' ausente");
                    $products['currentPage'] = $page;
                }
                
                if (!isset($products['perPage'])) {
                    error_log("Estrutura de produtos inválida: 'perPage' ausente");
                    $products['perPage'] = $limit;
                }
                
                if (!isset($products['lastPage'])) {
                    error_log("Estrutura de produtos inválida: 'lastPage' ausente");
                    $products['lastPage'] = ceil($products['total'] / $limit);
                }
                
                // Adicionar informações de ordenação e filtragem para uso na view
                $products['orderBy'] = $orderBy;
                $products['filters'] = $filters;
                $products['includeSubcategories'] = $includeSubcategories;
                
                // Preparar opções de ordenação para o select na view
                $products['orderByOptions'] = [
                    'default' => 'Relevância',
                    'name_asc' => 'Nome (A-Z)',
                    'name_desc' => 'Nome (Z-A)',
                    'price_asc' => 'Preço (menor-maior)',
                    'price_desc' => 'Preço (maior-menor)',
                    'newest' => 'Mais recentes',
                    'availability' => 'Disponibilidade'
                ];
                
                // Adicionar produtos à categoria para uso na view
                $category['products'] = $products;
            } catch (Exception $e) {
                error_log("Erro ao obter produtos da categoria: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                
                // Criar estrutura vazia em caso de erro
                $category['products'] = [
                    'items' => [],
                    'total' => 0,
                    'currentPage' => $page,
                    'perPage' => $limit,
                    'lastPage' => 1,
                    'orderBy' => $orderBy,
                    'filters' => $filters,
                    'includeSubcategories' => $includeSubcategories,
                    'orderByOptions' => [
                        'default' => 'Relevância',
                        'name_asc' => 'Nome (A-Z)',
                        'name_desc' => 'Nome (Z-A)',
                        'price_asc' => 'Preço (menor-maior)',
                        'price_desc' => 'Preço (maior-menor)',
                        'newest' => 'Mais recentes',
                        'availability' => 'Disponibilidade'
                    ]
                ];
            }
            
            // Adicionar faixas de preço para o filtro de preço
            try {
                $priceRanges = $this->getPriceRangesForCategory($category['id'], $includeSubcategories);
                $category['priceRanges'] = $priceRanges;
            } catch (Exception $e) {
                error_log("Erro ao obter faixas de preço: " . $e->getMessage());
                $category['priceRanges'] = [
                    'min' => 0,
                    'max' => 1000,
                    'ranges' => [
                        ['min' => 0, 'max' => 50],
                        ['min' => 50, 'max' => 100],
                        ['min' => 100, 'max' => 200],
                        ['min' => 200, 'max' => 500],
                        ['min' => 500, 'max' => 1000]
                    ]
                ];
            }
            
            // Obter caminho/breadcrumb da categoria atual
            try {
                $breadcrumb = $this->categoryModel->getBreadcrumb($category['id']);
                $category['breadcrumb'] = $breadcrumb;
            } catch (Exception $e) {
                error_log("Erro ao obter breadcrumb: " . $e->getMessage());
                $category['breadcrumb'] = [];
            }
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/category.php')) {
                throw new Exception("View category.php não encontrada em " . VIEWS_PATH);
            }
            
            // Renderizar view
            require_once VIEWS_PATH . '/category.php';
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao exibir categoria");
        }
    }
    
    /**
     * Obtém as faixas de preço para o filtro de preço
     * 
     * @param int $categoryId ID da categoria
     * @param bool $includeSubcategories Se deve incluir subcategorias
     * @return array Faixas de preço
     */
    private function getPriceRangesForCategory($categoryId, $includeSubcategories = true) {
        try {
            $productModel = new ProductModel();
            $categoryModel = new CategoryModel();
            
            // Obter IDs de subcategorias se necessário
            $categoryIds = [$categoryId];
            
            if ($includeSubcategories) {
                // Obter categoria para acessar left_value e right_value
                $category = $categoryModel->find($categoryId);
                
                if ($category && isset($category['left_value']) && isset($category['right_value'])) {
                    // Usar Nested Sets para obter todas as subcategorias de forma eficiente
                    $sql = "SELECT id FROM {$categoryModel->getTable()} 
                            WHERE left_value > :left_value AND right_value < :right_value AND is_active = 1";
                    
                    $subcategoryRows = $this->db()->select($sql, [
                        'left_value' => $category['left_value'],
                        'right_value' => $category['right_value']
                    ]);
                    
                    if (!empty($subcategoryRows)) {
                        foreach ($subcategoryRows as $row) {
                            $categoryIds[] = $row['id'];
                        }
                    }
                } else {
                    // Método alternativo: obter subcategorias recursivas
                    $subcategories = $categoryModel->getSubcategoriesRecursive($categoryId);
                    $this->collectSubcategoryIds($subcategories, $categoryIds);
                }
            }
            
            // Criar placeholders para os IDs
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            
            // Obter preço mínimo e máximo
            $sql = "SELECT 
                    MIN(CASE WHEN sale_price > 0 THEN sale_price ELSE price END) as min_price,
                    MAX(CASE WHEN sale_price > 0 THEN sale_price ELSE price END) as max_price
                    FROM {$productModel->getTable()}
                    WHERE category_id IN ({$placeholders}) AND is_active = 1";
            
            $result = $this->db()->select($sql, $categoryIds);
            
            $minPrice = isset($result[0]['min_price']) ? floor($result[0]['min_price']) : 0;
            $maxPrice = isset($result[0]['max_price']) ? ceil($result[0]['max_price']) : 1000;
            
            // Criar faixas de preço dinâmicas
            $ranges = [];
            
            // Se a diferença de preço for pequena, criar faixas menores
            if ($maxPrice - $minPrice <= 100) {
                $step = 20;
            } elseif ($maxPrice - $minPrice <= 500) {
                $step = 50;
            } elseif ($maxPrice - $minPrice <= 1000) {
                $step = 100;
            } elseif ($maxPrice - $minPrice <= 5000) {
                $step = 500;
            } else {
                $step = 1000;
            }
            
            // Arredondar o preço mínimo para o múltiplo de step inferior
            $minPrice = floor($minPrice / $step) * $step;
            
            // Arredondar o preço máximo para o múltiplo de step superior
            $maxPrice = ceil($maxPrice / $step) * $step;
            
            // Criar as faixas
            for ($i = $minPrice; $i < $maxPrice; $i += $step) {
                $ranges[] = [
                    'min' => $i, 
                    'max' => $i + $step
                ];
            }
            
            return [
                'min' => $minPrice,
                'max' => $maxPrice,
                'ranges' => $ranges
            ];
        } catch (Exception $e) {
            error_log("Erro ao calcular faixas de preço: " . $e->getMessage());
            
            // Retornar faixas padrão em caso de erro
            return [
                'min' => 0,
                'max' => 1000,
                'ranges' => [
                    ['min' => 0, 'max' => 50],
                    ['min' => 50, 'max' => 100],
                    ['min' => 100, 'max' => 200],
                    ['min' => 200, 'max' => 500],
                    ['min' => 500, 'max' => 1000]
                ]
            ];
        }
    }
    
    /**
     * Coleta IDs de subcategorias recursivamente (método auxiliar)
     * 
     * @param array $subcategories Lista de subcategorias
     * @param array &$categoryIds Array para armazenar os IDs
     */
    private function collectSubcategoryIds($subcategories, &$categoryIds) {
        foreach ($subcategories as $subcategory) {
            if (isset($subcategory['id'])) {
                $categoryIds[] = $subcategory['id'];
            }
            
            if (isset($subcategory['subcategories']) && is_array($subcategory['subcategories'])) {
                $this->collectSubcategoryIds($subcategory['subcategories'], $categoryIds);
            }
        }
    }
    
    /**
     * Lista todas as categorias principais
     */
    public function index() {
        try {
            // Log para rastreamento
            if (ENVIRONMENT === 'development') {
                error_log("Executando CategoryController::index()");
            }
            
            // Obter todas as categorias principais com subcategorias
            $categories = $this->categoryModel->getMainCategories(true);
            
            // CORREÇÃO: Validar resultado
            if (!is_array($categories)) {
                error_log("getMainCategories não retornou um array válido");
                $categories = [];
            }
            
            if (empty($categories)) {
                error_log("Aviso: Nenhuma categoria principal encontrada");
            } else if (ENVIRONMENT === 'development') {
                error_log("Categorias principais encontradas: " . count($categories));
            }
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/categories.php')) {
                throw new Exception("View categories.php não encontrada em " . VIEWS_PATH);
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
            // Log para rastreamento
            if (ENVIRONMENT === 'development') {
                error_log("Executando CategoryController::subcategories() com parâmetros: " . json_encode($params));
            }
            
            $slug = isset($params['slug']) ? trim($params['slug']) : '';
            
            if (empty($slug)) {
                error_log("Erro: Slug de categoria vazio ou não fornecido para subcategorias");
                header('Location: ' . BASE_URL);
                exit;
            }
            
            // Log do slug para debug
            if (ENVIRONMENT === 'development') {
                error_log("Buscando categoria para subcategorias com slug: " . $slug);
            }
            
            // Obter categoria principal pelo slug com subcategorias recursivas
            $category = $this->categoryModel->getBySlug($slug, true);
            
            if (!$category) {
                // Log do erro
                error_log("Categoria principal não encontrada para subcategorias, slug: " . $slug);
                
                // Categoria não encontrada
                header("HTTP/1.0 404 Not Found");
                include VIEWS_PATH . '/errors/404.php';
                exit;
            }
            
            // CORREÇÃO: Verificar se category é um array
            if (!is_array($category)) {
                error_log("getBySlug não retornou um array válido");
                throw new Exception("Dados de categoria inválidos");
            }
            
            // Verificar subcategorias
            if (!isset($category['subcategories']) || !is_array($category['subcategories']) || empty($category['subcategories'])) {
                // Se não houver subcategorias, redirecionar direto para produtos da categoria
                error_log("Nenhuma subcategoria encontrada para " . $slug . ", redirecionando para página de categoria");
                header('Location: ' . BASE_URL . 'categoria/' . $slug);
                exit;
            }
            
            // Obter caminho/breadcrumb da categoria atual
            try {
                $breadcrumb = $this->categoryModel->getBreadcrumb($category['id']);
                $category['breadcrumb'] = $breadcrumb;
            } catch (Exception $e) {
                error_log("Erro ao obter breadcrumb: " . $e->getMessage());
                $category['breadcrumb'] = [];
            }
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/subcategories.php')) {
                throw new Exception("View subcategories.php não encontrada em " . VIEWS_PATH);
            }
            
            // Renderizar view de subcategorias
            require_once VIEWS_PATH . '/subcategories.php';
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao exibir subcategorias");
        }
    }
    
    /**
     * Retorna a hierarquia de categorias em formato JSON
     * Útil para APIs e ajax
     */
    public function hierarchy() {
        try {
            // Definir cabeçalho JSON
            header('Content-Type: application/json');
            
            // Obter hierarquia completa
            $hierarchy = $this->categoryModel->getFullHierarchy();
            
            // Retornar hierarquia em formato JSON
            echo json_encode([
                'success' => true,
                'data' => $hierarchy
            ]);
        } catch (Exception $e) {
            // Retornar erro em formato JSON
            header('Content-Type: application/json');
            http_response_code(500);
            
            echo json_encode([
                'success' => false,
                'error' => ENVIRONMENT === 'development' ? $e->getMessage() : 'Erro ao obter hierarquia de categorias'
            ]);
        }
    }
    
    /**
     * Exibe um menu completo de categorias
     */
    public function menu() {
        try {
            // Log para rastreamento
            if (ENVIRONMENT === 'development') {
                error_log("Executando CategoryController::menu()");
            }
            
            // Obter hierarquia completa de categorias
            $categories = $this->categoryModel->getFullHierarchy();
            
            // CORREÇÃO: Validar resultado
            if (!is_array($categories)) {
                error_log("getFullHierarchy não retornou um array válido");
                $categories = [];
            }
            
            // Verificar se a view existe
            if (!file_exists(VIEWS_PATH . '/partials/category_menu.php')) {
                throw new Exception("View partials/category_menu.php não encontrada em " . VIEWS_PATH);
            }
            
            // Renderizar view de menu de categorias
            require_once VIEWS_PATH . '/partials/category_menu.php';
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao exibir menu de categorias");
        }
    }
    
    /**
     * Retorna a estrutura de breadcrumb para uma categoria 
     * Útil para componentes de interface que precisam do breadcrumb
     */
    public function breadcrumb($params) {
        try {
            $id = isset($params['id']) ? intval($params['id']) : 0;
            $slug = isset($params['slug']) ? trim($params['slug']) : '';
            
            if ($id <= 0 && empty($slug)) {
                // Sem identificador válido
                header("HTTP/1.0 400 Bad Request");
                
                if (ENVIRONMENT === 'development') {
                    echo "Erro: ID ou slug de categoria não fornecido";
                }
                
                exit;
            }
            
            // Se temos slug mas não ID, buscar a categoria pelo slug
            if (empty($id) && !empty($slug)) {
                $category = $this->categoryModel->getBySlug($slug);
                if (!$category || !isset($category['id'])) {
                    header("HTTP/1.0 404 Not Found");
                    
                    if (ENVIRONMENT === 'development') {
                        echo "Categoria não encontrada para o slug: " . $slug;
                    }
                    
                    exit;
                }
                
                $id = $category['id'];
            }
            
            // Obter breadcrumb
            $breadcrumb = $this->categoryModel->getBreadcrumb($id);
            
            // Verificar se a resposta foi solicitada como JSON
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            $wantsJson = $isAjax || (isset($_GET['format']) && $_GET['format'] === 'json');
            
            if ($wantsJson) {
                // Retornar como JSON
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'data' => $breadcrumb
                ]);
            } else {
                // Renderizar como HTML
                if (!file_exists(VIEWS_PATH . '/partials/breadcrumb.php')) {
                    throw new Exception("View partials/breadcrumb.php não encontrada em " . VIEWS_PATH);
                }
                
                require_once VIEWS_PATH . '/partials/breadcrumb.php';
            }
        } catch (Exception $e) {
            if (isset($wantsJson) && $wantsJson) {
                // Retornar erro como JSON
                header('Content-Type: application/json');
                http_response_code(500);
                
                echo json_encode([
                    'success' => false,
                    'error' => ENVIRONMENT === 'development' ? $e->getMessage() : 'Erro ao obter breadcrumb'
                ]);
            } else {
                $this->handleError($e, "Erro ao obter breadcrumb");
            }
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