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
            
            // Obter categoria pelo slug
            $category = $this->categoryModel->getBySlug($slug);
            
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
            
            // Verificar campos obrigatórios da categoria
            $requiredFields = ['id', 'name', 'slug'];
            foreach ($requiredFields as $field) {
                if (!isset($category[$field])) {
                    error_log("Campo obrigatório ausente na categoria: " . $field);
                    throw new Exception("Dados incompletos da categoria: campo {$field} ausente");
                }
            }
            
            // Verificar subcategorias
            if (!isset($category['subcategories']) || !is_array($category['subcategories'])) {
                $category['subcategories'] = [];
                error_log("Aviso: Categoria sem subcategorias ou não é array");
            }
            
            // Obter produtos da categoria com paginação usando método melhorado
            try {
                $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                $limit = 12; // produtos por página
                
                // Usar getCategoryWithProducts em vez de getByCategory para tratar produtos de subcategorias
                $categoryWithProducts = $this->categoryModel->getCategoryWithProducts($slug, $page, $limit);
                
                if ($categoryWithProducts && isset($categoryWithProducts['products'])) {
                    $products = $categoryWithProducts['products'];
                    
                    // Verificar se products tem a estrutura esperada
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
                        $products['lastPage'] = 1;
                    }
                } else {
                    // Fallback para método antigo se getCategoryWithProducts falhar
                    error_log("Aviso: getCategoryWithProducts falhou, usando getByCategory como fallback");
                    $products = $this->productModel->getByCategory($category['id'], $page, $limit);
                }
            } catch (Exception $e) {
                error_log("Erro ao obter produtos da categoria: " . $e->getMessage());
                
                // Criar estrutura vazia em caso de erro
                $products = [
                    'items' => [],
                    'total' => 0,
                    'currentPage' => $page,
                    'perPage' => $limit,
                    'lastPage' => 1
                ];
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
     * Lista todas as categorias principais
     */
    public function index() {
        try {
            // Log para rastreamento
            if (ENVIRONMENT === 'development') {
                error_log("Executando CategoryController::index()");
            }
            
            // Obter todas as categorias principais
            $categories = $this->categoryModel->getMainCategories();
            
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
            
            // Obter categoria principal pelo slug
            $category = $this->categoryModel->getBySlug($slug);
            
            if (!$category) {
                // Log do erro
                error_log("Categoria principal não encontrada para subcategorias, slug: " . $slug);
                
                // Categoria não encontrada
                header("HTTP/1.0 404 Not Found");
                include VIEWS_PATH . '/errors/404.php';
                exit;
            }
            
            // Obter subcategorias
            try {
                $subcategories = $this->categoryModel->getSubcategories($category['id']);
                
                if (ENVIRONMENT === 'development') {
                    error_log("Subcategorias encontradas: " . count($subcategories));
                }
            } catch (Exception $e) {
                error_log("Erro ao obter subcategorias: " . $e->getMessage());
                $subcategories = [];
            }
            
            if (empty($subcategories)) {
                // Se não houver subcategorias, redirecionar direto para produtos da categoria
                error_log("Nenhuma subcategoria encontrada para " . $slug . ", redirecionando para página de categoria");
                header('Location: ' . BASE_URL . 'categoria/' . $slug);
                exit;
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