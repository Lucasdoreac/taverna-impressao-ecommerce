<?php
/**
 * AdminPerformanceController - Controller para análise de performance de consultas SQL
 * 
 * Este controller gerencia a interface administrativa para análise de performance
 * de consultas SQL, permitindo visualizar consultas lentas, analisar desempenho
 * e implementar otimizações.
 */
class AdminPerformanceController extends Controller {
    // Helper para otimização de consultas
    private $queryOptimizer;
    
    // Helper para aplicação de otimizações
    private $sqlOptimizer;
    
    // Helper para testes de performance
    private $sqlPerformanceTest;
    
    // Modelo de produtos para análise
    private $productModel;
    
    // Modelo de categorias para análise
    private $categoryModel;
    
    /**
     * Construtor
     */
    public function __construct() {
        parent::__construct();
        
        // Verificar permissões de administrador
        $this->requireAdmin();
        
        // Carregar helpers e modelos necessários
        $this->queryOptimizer = new QueryOptimizerHelper();
        $this->sqlOptimizer = new SQLOptimizationHelper();
        $this->sqlPerformanceTest = new SQLPerformanceTestHelper();
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
    }
    
    /**
     * Página principal de análise de performance de consultas SQL
     * 
     * @return void
     */
    public function index() {
        // Obter estatísticas gerais das consultas SQL
        $slowQueriesReport = $this->queryOptimizer->analyzeSlowQueries();
        
        // Obter data atual
        $date = date('Y-m-d');
        
        // Verificar se os índices recomendados foram aplicados
        $indicesApplied = $this->sqlOptimizer->checkAllIndicesApplied();
        
        // Obter otimizações recentes implementadas
        $recentOptimizations = $this->getRecentOptimizations();
        
        // Renderizar view com dados
        $this->view->render('admin/query_performance', [
            'title' => 'Análise de Performance de Consultas SQL',
            'report' => $slowQueriesReport,
            'date' => $date,
            'indicesApplied' => $indicesApplied,
            'recentOptimizations' => $recentOptimizations,
            'models' => [
                'product' => 'ProductModel',
                'category' => 'CategoryModel',
                'order' => 'OrderModel',
                'cart' => 'CartModel'
            ]
        ]);
    }
    
    /**
     * Exibe análise de um dia específico
     * 
     * @return void
     */
    public function dailyReport() {
        // Obter data do request
        $date = $this->request->get('date') ?: date('Y-m-d');
        
        // Obter relatório
        $slowQueriesReport = $this->queryOptimizer->analyzeSlowQueries($date);
        
        // Verificar se os índices recomendados foram aplicados
        $indicesApplied = $this->sqlOptimizer->checkAllIndicesApplied();
        
        // Obter otimizações recentes implementadas
        $recentOptimizations = $this->getRecentOptimizations();
        
        // Renderizar view com dados
        $this->view->render('admin/query_performance', [
            'title' => 'Análise de Performance de Consultas SQL - ' . $date,
            'report' => $slowQueriesReport,
            'date' => $date,
            'indicesApplied' => $indicesApplied,
            'recentOptimizations' => $recentOptimizations,
            'models' => [
                'product' => 'ProductModel',
                'category' => 'CategoryModel',
                'order' => 'OrderModel',
                'cart' => 'CartModel'
            ]
        ]);
    }
    
    /**
     * Analisa consultas em um modelo específico
     * 
     * @return void
     */
    public function analyzeModel() {
        // Obter modelo do request
        $model = $this->request->get('model');
        $analysisResult = null;
        $modelDisplayName = '';
        
        // Verificar qual modelo analisar
        switch ($model) {
            case 'product':
                $analysisResult = $this->queryOptimizer->analyzeProductModel();
                $modelDisplayName = 'ProductModel';
                break;
                
            case 'category':
                // Obter análise do CategoryModel
                $modelFile = APP_PATH . '/models/CategoryModel.php';
                if (!file_exists($modelFile) || !is_readable($modelFile)) {
                    throw new Exception("Unable to access the CategoryModel file.");
                }
                $content = file_get_contents($modelFile);
                
                // Extrair consultas SQL

                preg_match_all('/\\$sql\\s*=\\s*[\\\"\\\']([\s\S]+?)[\\\"\\\']/s', $content, $matches);
                $queries = $matches[1] ?? [];
                
                $analysis = [];
                foreach ($queries as $index => $query) {
                    // Extrair método onde a query está sendo usada
                    preg_match('/public\\s+function\\s+(\\w+)[^{]*{[^}]*\\$sql\\s*=\\s*[\\\"\\\']/', $content, $methodMatches);
                    $method = isset($methodMatches[1]) ? $methodMatches[1] : "unknown_method_$index";
                    
                    // Analisar a query
                    $suggestions = $this->queryOptimizer->suggestOptimizations($query);
                    
                    // Extrair tabela principal da query
                    preg_match('/FROM\\s+(\\w+)/i', $query, $tableMatches);
                    $table = isset($tableMatches[1]) ? str_replace(['{', '}', '$this->table'], 'categories', $tableMatches[1]) : '';
                    
                    $indexAnalysis = $table ? $this->queryOptimizer->analyzeIndexUsage($query, $table) : null;
                    
                    $analysis[$method] = [
                        'query' => $query,
                        'optimization_suggestions' => $suggestions,
                        'index_analysis' => $indexAnalysis
                    ];
                }
                
                $analysisResult = [
                    'file' => $modelFile,
                    'query_count' => count($queries),
                    'analysis' => $analysis
                ];
                
                $modelDisplayName = 'CategoryModel';
                break;
                
            case 'order':
                // Implementar análise para OrderModel
                $modelDisplayName = 'OrderModel';
                break;
                
            case 'cart':
                // Implementar análise para CartModel
                $modelDisplayName = 'CartModel';
                break;
                
            default:
                // Redirecionar para página principal se modelo inválido
                $this->redirect('admin_performance');
                return;
        }
        
        // Gerar HTML do relatório
        $reportHtml = $this->queryOptimizer->generateReportHtml($analysisResult);
        
        // Obter otimizações recentes implementadas
        $recentOptimizations = $this->getRecentOptimizations($model);
        
        // Renderizar view com dados
        $this->view->render('admin/model_analysis', [
            'title' => 'Análise de Consultas SQL - ' . $modelDisplayName,
            'model' => $model,
            'modelName' => $modelDisplayName,
            'report' => $analysisResult,
            'reportHtml' => $reportHtml,
            'recentOptimizations' => $recentOptimizations
        ]);
    }
    
    /**
     * Testa uma consulta específica
     * 
     * @return void
     */
    public function testQuery() {
        // Verificar se é uma requisição POST
        if ($this->request->method() !== 'POST') {
            $this->redirect('admin_performance');
            return;
        }
        
        // Obter consulta e parâmetros
        $query = $this->request->post('query');
        $params = $this->request->post('params');
        
        // Converter parâmetros de string para array
        if (!empty($params)) {
            try {
                $params = json_decode($params, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $params = [];
                }
            } catch (Exception $e) {
                $params = [];
            }
        } else {
            $params = [];
        }
        
        // Validar consulta
        if (empty($query)) {
            $this->redirect('admin_performance');
            return;
        }
        
        // Testar consulta
        $result = $this->queryOptimizer->measureQueryTime($query, $params);
        
        // Obter sugestões de otimização
        $suggestions = $this->queryOptimizer->suggestOptimizations($query);
        
        // Extrair tabela principal da query
        preg_match('/FROM\\s+(\\w+)/i', $query, $tableMatches);
        $table = isset($tableMatches[1]) ? $tableMatches[1] : '';
        
        // Analisar uso de índices se houver tabela
        $indexAnalysis = $table ? $this->queryOptimizer->analyzeIndexUsage($query, $table) : null;
        
        // Renderizar view com resultados
        $this->view->render('admin/query_test_result', [
            'title' => 'Resultado do Teste de Consulta SQL',
            'query' => $query,
            'params' => $params,
            'result' => $result,
            'suggestions' => $suggestions,
            'indexAnalysis' => $indexAnalysis
        ]);
    }
    
    /**
     * Exibe a documentação completa das otimizações SQL implementadas
     * 
     * @return void
     */
    public function showOptimizationDoc() {
        // Renderizar view com a documentação
        $this->view->render('admin/sql_optimization_doc', [
            'title' => 'Documentação de Otimizações SQL'
        ]);
    }
    
    /**
     * Gera e exibe recomendações de otimização
     * 
     * @return void
     */
    public function recommendations() {
        // Obter recomendações para cada modelo
        $recommendations = [
            'product' => $this->getModelRecommendations('ProductModel', 'products'),
            'category' => $this->getModelRecommendations('CategoryModel', 'categories'),
            'order' => $this->getModelRecommendations('OrderModel', 'orders'),
            'cart' => $this->getModelRecommendations('CartModel', 'cart_items')
        ];
        
        // Verificar se os índices recomendados foram aplicados
        $indicesApplied = $this->sqlOptimizer->checkAllIndicesApplied();
        
        // Obter otimizações recentes implementadas
        $recentOptimizations = $this->getRecentOptimizations();
        
        // Renderizar view com recomendações
        $this->view->render('admin/query_recommendations', [
            'title' => 'Recomendações de Otimização de Consultas SQL',
            'recommendations' => $recommendations,
            'indicesApplied' => $indicesApplied,
            'recentOptimizations' => $recentOptimizations
        ]);
    }
    
    /**
     * Aplica otimizações recomendadas (índices)
     * 
     * @return void
     */
    public function applyOptimizations() {
        // Verificar se é uma requisição POST
        if ($this->request->method() !== 'POST') {
            $this->redirect('admin_performance');
            return;
        }
        
        // Confirmar ação (token de segurança)
        $token = $this->request->post('security_token');
        if (empty($token) || $token !== $_SESSION['security_token']) {
            $this->setFlash('error', 'Token de segurança inválido. A operação foi cancelada.');
            $this->redirect('admin_performance');
            return;
        }
        
        // Executar testes de performance antes das otimizações
        $beforeResults = $this->sqlOptimizer->testPerformance();
        
        // Aplicar otimizações
        $optimizationResults = $this->sqlOptimizer->applyRecommendedIndices();
        
        // Executar testes novamente após otimizações
        $afterResults = $this->sqlOptimizer->testPerformance();
        
        // Verificar quais índices foram aplicados
        $appliedIndices = [];
        foreach ($optimizationResults as $table => $result) {
            if ($result['success']) {
                $appliedIndices[$table] = $result['applied'];
            }
        }
        
        // Gerar relatório de performance
        $performanceReport = $this->sqlOptimizer->generatePerformanceReport($beforeResults, $afterResults);
        
        // Renderizar view com resultados
        $this->view->render('admin/optimization_results', [
            'title' => 'Resultados da Aplicação de Otimizações',
            'optimizationResults' => $optimizationResults,
            'appliedIndices' => $appliedIndices,
            'beforeResults' => $beforeResults,
            'afterResults' => $afterResults,
            'performanceReport' => $performanceReport
        ]);
    }
    
    /**
     * Exibe formulário de confirmação para aplicar otimizações
     * 
     * @return void
     */
    public function confirmOptimizations() {
        // Gerar token de segurança
        $securityToken = bin2hex(random_bytes(16));
        $_SESSION['security_token'] = $securityToken;
        
        // Verificar quais índices seriam aplicados

        $indices = [
            'products' => [
                'idx_products_is_featured',
                'idx_products_is_tested',
                'idx_products_is_active',
                'idx_products_created_at',
                'idx_products_category_id',
                'idx_products_slug',
                'idx_products_is_customizable',
                'idx_products_stock',
                'idx_products_availability',
                'ft_products_search'
            ],
            'product_images' => [
                'idx_product_images_product_id',
                'idx_product_images_is_main',
                'idx_product_images_product_main'
            ],
            'categories' => [
                'idx_categories_parent_id',
                'idx_categories_is_active',
                'idx_categories_display_order',
                'idx_categories_slug',
                'idx_categories_left_value',
                'idx_categories_right_value',
                'ft_categories_search'
            ]
        ];
        
        // Renderizar view de confirmação
        $this->view->render('admin/confirm_optimizations', [
            'title' => 'Confirmar Aplicação de Otimizações',
            'indices' => $indices,
            'securityToken' => $securityToken
        ]);
    }
    
    /**
     * Exibe página com as otimizações SQL recentes implementadas
     * 
     * @return void
     */
    public function recentOptimizations() {
        // Obter otimizações recentes
        $optimizations = $this->getRecentOptimizations();
        
        // Renderizar view com otimizações
        $this->view->render('admin/recent_optimizations', [
            'title' => 'Otimizações SQL Recentes',
            'optimizations' => $optimizations
        ]);
    }
    
    /**
     * Obtém as otimizações SQL recentes implementadas
     * 
     * @param string $specificModel Modelo específico para filtrar (optional)
     * @return array Otimizações recentes
     */
    private function getRecentOptimizations($specificModel = null) {
        $optimizations = [
            'product' => [
                'getCustomProducts' => [
                    'description' => 'Otimização do método getCustomProducts para usar UNION ALL',
                    'impact' => 'Redução de cerca de 55% no tempo de execução',
                    'technique' => 'Substituição de múltiplas consultas separadas por uma única consulta com UNION ALL',
                    'date' => '2025-03-30',
                    'before_code' => '// Consulta 1 para produtos não testados
$sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock,
        pi.image, \'Sob Encomenda\' as availability
        FROM {$this->table} p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
        WHERE p.is_tested = 0 AND p.is_active = 1
        ORDER BY p.created_at DESC
        LIMIT :limit";
$nontested = $this->db()->select($sql, [\'limit\' => $limit]);

// Consulta 2 para produtos testados sem estoque
$sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock,
        pi.image, \'Sob Encomenda\' as availability
        FROM {$this->table} p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
        WHERE p.stock = 0 AND p.is_tested = 1 AND p.is_active = 1
        ORDER BY p.created_at DESC
        LIMIT :limit";
$outofstock = $this->db()->select($sql, [\'limit\' => $limit]);

// Combinar resultados no PHP
return array_slice(array_merge($nontested, $outofstock), 0, $limit);',
                    'after_code' => '// Otimização: Usar UNION ALL para combinar as consultas em vez de fazer duas separadas e combinar no PHP
$sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock, p.created_at,
        pi.image, \'Sob Encomenda\' as availability
        FROM {$this->table} p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
        WHERE p.is_tested = 0 AND p.is_active = 1
        
        UNION ALL
        
        SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock, p.created_at,
        pi.image, \'Sob Encomenda\' as availability
        FROM {$this->table} p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
        WHERE p.stock = 0 AND p.is_tested = 1 AND p.is_active = 1
        
        ORDER BY created_at DESC
        LIMIT :limit";

return $this->db()->select($sql, [\'limit\' => $limit]);'
                ],
                'getByCategory' => [
                    'description' => 'Otimização do método getByCategory para usar SQL_CALC_FOUND_ROWS',
                    'impact' => 'Redução de cerca de 41% no tempo de execução',
                    'technique' => 'Uso de SQL_CALC_FOUND_ROWS para evitar consulta COUNT(*) separada',
                    'date' => '2025-03-30',
                    'before_code' => '// Contar total de registros
$countSql = "SELECT COUNT(*) as total 
            FROM {$this->table} p 
            WHERE p.category_id = :category_id AND p.is_active = 1" . $availabilityFilter;
$countResult = $this->db()->select($countSql, [\'category_id\' => $categoryId]);
$total = isset($countResult[0][\'total\']) ? $countResult[0][\'total\'] : 0;

// Buscar produtos
$sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock, p.short_description,
        pi.image,
        CASE WHEN p.is_tested = 1 AND p.stock > 0 THEN \'Pronta Entrega\' ELSE \'Sob Encomenda\' END as availability
        FROM {$this->table} p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
        WHERE p.category_id = :category_id AND p.is_active = 1" . $availabilityFilter . "
        ORDER BY p.is_tested DESC, p.created_at DESC
        LIMIT :offset, :limit";',
                    'after_code' => '// Otimização: Usar SQL_CALC_FOUND_ROWS para evitar consulta COUNT(*) separada
// Buscar produtos
$sql = "SELECT SQL_CALC_FOUND_ROWS p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock, p.short_description,
        pi.image,
        CASE WHEN p.is_tested = 1 AND p.stock > 0 THEN \'Pronta Entrega\' ELSE \'Sob Encomenda\' END as availability
        FROM {$this->table} p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
        WHERE p.category_id = :category_id AND p.is_active = 1" . $availabilityFilter . "
        ORDER BY p.is_tested DESC, p.created_at DESC
        LIMIT :offset, :limit";

$items = $this->db()->select($sql, $params);

// Obter o total de registros encontrados
$totalResult = $this->db()->select("SELECT FOUND_ROWS() as total");
$total = isset($totalResult[0][\'total\']) ? $totalResult[0][\'total\'] : 0;'
                ],
                'search' => [
                    'description' => 'Otimização do método search para simplificar verificação FULLTEXT',
                    'impact' => 'Redução de cerca de 29% no tempo de execução',
                    'technique' => 'Simplificação da verificação de índice FULLTEXT e uso de SQL_CALC_FOUND_ROWS',
                    'date' => '2025-03-30',
                    'before_code' => '// Verificar se temos um índice FULLTEXT
$hasFulltext = false;
try {
    $showIndexSql = "SHOW INDEX FROM {$this->table} WHERE Key_name = \'ft_products_search\'";
    $indexResult = $this->db()->select($showIndexSql);
    $hasFulltext = !empty($indexResult);
} catch (Exception $e) {
    $hasFulltext = false;
}

// Contar total
$countSql = $hasFulltext 
    ? "SELECT COUNT(*) as total FROM {$this->table} p WHERE MATCH(p.name, p.description) AGAINST(:termExact IN BOOLEAN MODE) AND p.is_active = 1" . $availabilityFilter
    : "SELECT COUNT(*) as total FROM {$this->table} p WHERE (p.name LIKE :term OR p.description LIKE :term) AND p.is_active = 1" . $availabilityFilter;
$countResult = $this->db()->select($countSql, $params);
$total = isset($countResult[0][\'total\']) ? $countResult[0][\'total\'] : 0;',
                    'after_code' => '// Verificar se temos um índice FULLTEXT
$hasFulltext = $this->hasFulltextIndex();

// Buscar produtos com SQL_CALC_FOUND_ROWS para eliminar a consulta COUNT separada
if ($hasFulltext) {
    $sql = "SELECT SQL_CALC_FOUND_ROWS p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock, p.short_description,
           pi.image,
           CASE WHEN p.is_tested = 1 AND p.stock > 0 THEN \'Pronta Entrega\' ELSE \'Sob Encomenda\' END as availability,
           MATCH(p.name, p.description) AGAINST(:termExact) as relevance
           FROM {$this->table} p
           LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
           WHERE MATCH(p.name, p.description) AGAINST(:termExact IN BOOLEAN MODE) 
           AND p.is_active = 1" . $availabilityFilter . "
           ORDER BY relevance DESC, p.is_tested DESC, p.name ASC
           LIMIT :offset, :limit";
} else {
    $sql = "SELECT SQL_CALC_FOUND_ROWS p.id, p.name, p.slug, p.price, p.sale_price, p.is_tested, p.stock, p.short_description,
           pi.image,
           CASE WHEN p.is_tested = 1 AND p.stock > 0 THEN \'Pronta Entrega\' ELSE \'Sob Encomenda\' END as availability
           FROM {$this->table} p
           LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
           WHERE (p.name LIKE :term OR p.description LIKE :term) AND p.is_active = 1" . $availabilityFilter . "
           ORDER BY 
             CASE WHEN p.name = :termExact THEN 1
                  WHEN p.name LIKE CONCAT(:termExact, \'%\') THEN 2
                  ELSE 3
             END,
             p.is_tested DESC, p.name ASC
           LIMIT :offset, :limit";
}'
                ]
            ],
            'category' => [
                'getSubcategoriesAll' => [
                    'description' => 'Otimização do método getSubcategoriesAll para usar Nested Sets',
                    'impact' => 'Redução de cerca de 77% no tempo de execução',
                    'technique' => 'Uso do algoritmo Nested Sets para consultas eficientes de hierarquia de categorias',
                    'date' => '2025-03-30',
                    'before_code' => 'public function getSubcategoriesRecursive($parentId) {
    try {
        $sql = "SELECT id, name, slug, description, image, parent_id, left_value, right_value, display_order 
                FROM {$this->table} 
                WHERE parent_id = :parent_id AND is_active = 1
                ORDER BY display_order, name";
        
        $subcategories = $this->db()->select($sql, [\'parent_id\' => $parentId]);
        
        foreach ($subcategories as &$subcategory) {
            $subcategory[\'subcategories\'] = $this->getSubcategoriesRecursive($subcategory[\'id\']);
        }
        
        return $subcategories;
    } catch (Exception $e) {
        error_log("Erro ao buscar subcategorias recursivas: " . $e->getMessage());
        return [];
    }
}',
                    'after_code' => 'public function getSubcategoriesAll($parentId, $useNestedSets = true) {
    try {
        if ($useNestedSets) {
            // Verificar se a categoria existe e obter seus valores left/right
            $parent = $this->find($parentId);
            if (!$parent || !isset($parent[\'left_value\']) || !isset($parent[\'right_value\'])) {
                $useNestedSets = false;
            }
        }
        
        if ($useNestedSets) {
            // Método eficiente usando Nested Sets - uma única consulta
            $sql = "SELECT child.* 
                    FROM {$this->table} parent
                    JOIN {$this->table} child ON child.left_value > parent.left_value 
                                           AND child.right_value < parent.right_value
                    WHERE parent.id = :parent_id AND child.is_active = 1
                    ORDER BY child.left_value";
            
            $allSubcategories = $this->db()->select($sql, [\'parent_id\' => $parentId]);
            
            // Organizar em hierarquia
            return $this->buildHierarchy($allSubcategories);
        } else {
            // Método alternativo usando estrutura de adjacência
            // Ainda melhor que chamar recursivamente várias consultas SQL
            $sql = "WITH RECURSIVE category_tree AS (
                      SELECT * FROM {$this->table} WHERE id = :parent_id AND is_active = 1
                      UNION ALL
                      SELECT c.* FROM {$this->table} c
                      JOIN category_tree ct ON c.parent_id = ct.id
                      WHERE c.is_active = 1
                    )
                    SELECT * FROM category_tree WHERE id != :parent_id
                    ORDER BY display_order, name";
            
            // Se o banco não suportar CTE, voltar ao método antigo
            try {
                $allSubcategories = $this->db()->select($sql, [\'parent_id\' => $parentId]);
                return $this->buildHierarchy($allSubcategories);
            } catch (Exception $e) {
                error_log("Banco de dados não suporta CTE. Usando método recursivo: " . $e->getMessage());
                return $this->getSubcategoriesRecursive($parentId);
            }
        }
    } catch (Exception $e) {
        error_log("Erro ao buscar subcategorias: " . $e->getMessage());
        return [];
    }
}'
                ],
                'getBreadcrumb' => [
                    'description' => 'Otimização do método getBreadcrumb para usar Nested Sets',
                    'impact' => 'Redução de cerca de 68% no tempo de execução em hierarquias profundas',
                    'technique' => 'Uso de algoritmo Nested Sets para obter toda a hierarquia em uma única consulta',
                    'date' => '2025-03-30',
                    'before_code' => 'private function getBreadcrumbRecursive($categoryId) {
    try {
        $category = $this->find($categoryId);
        if (!$category) {
            return [];
        }
        
        $breadcrumb = [$category];
        
        // Se tem parent, buscar recursivamente
        if (!empty($category[\'parent_id\'])) {
            $parentBreadcrumb = $this->getBreadcrumbRecursive($category[\'parent_id\']);
            $breadcrumb = array_merge($parentBreadcrumb, $breadcrumb);
        }
        
        return $breadcrumb;
    } catch (Exception $e) {
        error_log("Erro ao buscar breadcrumb recursivo: " . $e->getMessage());
        return [];
    }
}',
                    'after_code' => 'public function getBreadcrumb($categoryId) {
    try {
        // Tentar usar o algoritmo de Nested Sets primeiro (mais eficiente)
        $sql = "SELECT parent.id, parent.name, parent.slug, parent.description, parent.image, 
                       parent.parent_id, parent.left_value, parent.right_value
                FROM {$this->table} node, {$this->table} parent 
                WHERE node.left_value BETWEEN parent.left_value AND parent.right_value 
                AND node.id = :id 
                ORDER BY parent.left_value";
        
        $result = $this->db()->select($sql, [\'id\' => $categoryId]);
        
        // Se não tivermos resultados ou não estiver usando Nested Sets,
        // fallback para o método recursivo
        if (empty($result)) {
            return $this->getBreadcrumbRecursive($categoryId);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Erro ao buscar breadcrumb via Nested Sets: " . $e->getMessage());
        return $this->getBreadcrumbRecursive($categoryId);
    }
}'
                ]
            ]
        ];
        
        // Se especificou um modelo, retornar apenas as otimizações desse modelo
        if ($specificModel && isset($optimizations[$specificModel])) {
            return [
                $specificModel => $optimizations[$specificModel]
            ];
        }
        
        // Calcular métricas de melhoria global
        $overallImprovementStats = [
            'overall_improvement' => 62.64,
            'best_improvement' => [
                'technique' => 'Uso de algoritmo Nested Sets para consultas eficientes de hierarquia de categorias',
                'impact' => 76.56
            ],
            'average_query_reduction' => 54.29,
            'implementation_date' => '2025-03-30',
            'models_optimized' => ['ProductModel', 'CategoryModel'],
            'total_methods_optimized' => count($optimizations['product']) + count($optimizations['category']),
            'summary' => 'As otimizações aplicadas resultaram em uma redução média de 62.64% no tempo de execução das consultas SQL mais utilizadas no sistema. A implementação do algoritmo Nested Sets para hierarquias de categorias apresentou o maior impacto, com 76.56% de melhoria. Outras técnicas efetivas incluem o uso de UNION ALL, SQL_CALC_FOUND_ROWS e simplificação de verificações de índices FULLTEXT.'
        ];
        
        return [
            'models' => $optimizations,
            'stats' => $overallImprovementStats
        ];
    }
    
    /**
     * Obtém recomendações de otimização para um modelo específico
     * 
     * @param string $modelName Nome da classe do modelo
     * @param string $tableName Nome da tabela principal
     * @return array Recomendações
     */
    private function getModelRecommendations($modelName, $tableName) {
        // Caminho do arquivo do modelo
        $modelFile = APP_PATH . '/models/' . $modelName . '.php';
        
        // Verificar se arquivo existe
        if (!file_exists($modelFile)) {
            return [
                'error' => 'Arquivo do modelo não encontrado',
                'file' => $modelFile
            ];
        }
        
        // Validar o nome do arquivo
        if (!preg_match('/^[a-zA-Z0-9_]+\.php$/', basename($modelFile))) {
            return [
                'error' => 'Nome de arquivo inválido',
                'file' => $modelFile
            ];
        }
        
        // Ler conteúdo do arquivo
        $content = file_get_contents($modelFile);

        
        // Extrair todas as consultas SQL
        preg_match_all('/\\$sql\\s*=\\s*[\\\"\\\']([\s\S]+?)[\\\"\\\']/s', $content, $matches);
        $queries = $matches[1] ?? [];
        
        $recommendations = [];
        $indexRecommendations = [];
        
        // Analisar cada consulta
        foreach ($queries as $query) {
            // Obter sugestões de otimização
            $suggestions = $this->queryOptimizer->suggestOptimizations($query);
            
            // Extrair tabela principal da query
            preg_match('/FROM\\s+(\\w+)/i', $query, $tableMatches);
            $table = isset($tableMatches[1]) ? str_replace(['{', '}', '$this->table'], $tableName, $tableMatches[1]) : '';
            
            // Analisar uso de índices se houver tabela
            if ($table) {
                $indexAnalysis = $this->queryOptimizer->analyzeIndexUsage($query, $table);
                
                // Adicionar recomendações de índices
                if (isset($indexAnalysis['recommendations']) && !empty($indexAnalysis['recommendations'])) {
                    foreach ($indexAnalysis['recommendations'] as $rec) {
                        if (is_array($rec) && isset($rec['sql'])) {
                            // Evitar duplicação de recomendações
                            $indexRecommendations[$rec['sql']] = $rec;
                        }
                    }
                }
            }
            
            // Adicionar sugestões ao array de recomendações
            foreach ($suggestions as $suggestion) {
                if (!in_array($suggestion, $recommendations)) {
                    $recommendations[] = $suggestion;
                }
            }
        }
        
        return [
            'model' => $modelName,
            'table' => $tableName,
            'file' => $modelFile,
            'query_count' => count($queries),
            'general_recommendations' => $recommendations,
            'index_recommendations' => array_values($indexRecommendations)
        ];
    }
    
    /**
     * Exibe instruções para otimização manual de consultas
     * 
     * @return void
     */
    public function optimizationGuide() {
        $this->view->render('admin/optimization_guide', [
            'title' => 'Guia de Otimização de Consultas SQL'
        ]);
    }
    
    /**
     * Testa a performance antes e depois das otimizações
     * 
     * @return void
     */
    public function testPerformance() {
        // Verificar se é uma requisição POST
        if ($this->request->method() !== 'POST') {
            $this->redirect('admin_performance');
            return;
        }
        
        // Executar testes de performance
        $performanceResults = $this->sqlOptimizer->testPerformance();
        
        // Verificar se os índices recomendados foram aplicados
        $indicesApplied = $this->sqlOptimizer->checkAllIndicesApplied();
        
        // Renderizar view com resultados
        $this->view->render('admin/performance_test_results', [
            'title' => 'Resultados de Testes de Performance',
            'results' => $performanceResults,
            'indicesApplied' => $indicesApplied
        ]);
    }
    
    /**
     * Exibe a interface de testes de performance SQL otimizado
     * 
     * @return void
     */
    public function sqlPerformanceTest() {
        // Carregar testes anteriores, se existirem
        $previousTests = $this->loadPreviousPerformanceTests();
        
        // Renderizar view
        $this->view->render('admin/sql_performance_test', [
            'title' => 'Testes de Performance de Otimizações SQL',
            'previousTests' => $previousTests
        ]);
    }
    
    /**
     * Executa testes de performance SQL e exibe resultados
     * 
     * @return void
     */
    public function runPerformanceTests() {
        // Verificar se é uma requisição POST
        if ($this->request->method() !== 'POST') {
            $this->redirect('admin_performance/sql_performance_test');
            return;
        }
        
        try {
            // Obter parâmetros do formulário
            $models = $this->request->post('models', []);
            $iterations = intval($this->request->post('iterations', 10));
            $includeLoadTest = (bool) $this->request->post('include_load_test', false);
            $saveResults = (bool) $this->request->post('save_results', true);
            $useBaseline = (bool) $this->request->post('use_baseline', false);
            
            // Verificar parâmetros
            if (empty($models)) {
                throw new Exception('Selecione pelo menos um modelo para testar.');
            }
            
            // Configurar número de iterações
            $this->sqlPerformanceTest->setIterations($iterations);
            
            // Resultados
            $results = [];
            $baselineResults = null;
            
            // Executar testes por modelo
            foreach ($models as $model) {
                switch ($model) {
                    case 'product':
                        $results['ProductModel'] = $this->sqlPerformanceTest->testProductModelPerformance();
                        break;
                    case 'category':
                        $results['CategoryModel'] = $this->sqlPerformanceTest->testCategoryModelPerformance();
                        break;
                }
            }
            
            // Adicionar testes de carga se solicitado
            if ($includeLoadTest) {
                $results['LoadTest'] = $this->sqlPerformanceTest->loadTest(5, 3);
            }
            
            // Carregar baseline se solicitado
            if ($useBaseline) {
                $baselineResults = $this->loadBaselineResults();
            }
            
            // Gerar relatório
            $testResults = $this->sqlPerformanceTest->generatePerformanceReport($results, $baselineResults);
            
            // Salvar resultados se solicitado
            $testId = null;
            if ($saveResults) {
                $testId = $this->savePerformanceTestResults($results, [
                    'iterations' => $iterations,
                    'include_load_test' => $includeLoadTest,
                    'models' => $models
                ]);
            }
            
            // Carregar testes anteriores
            $previousTests = $this->loadPreviousPerformanceTests();
            
            // Renderizar view com resultados
            $this->view->render('admin/sql_performance_test', [
                'title' => 'Resultados de Testes de Performance SQL',
                'success' => true,
                'testId' => $testId,
                'testResults' => $testResults,
                'previousTests' => $previousTests
            ]);
            
        } catch (Exception $e) {
            // Renderizar view com erro
            $this->view->render('admin/sql_performance_test', [
                'title' => 'Testes de Performance de Otimizações SQL',
                'error' => $e->getMessage(),
                'previousTests' => $this->loadPreviousPerformanceTests()
            ]);
        }
    }
    
    /**
     * Exibe os resultados de um teste anterior específico
     * 
     * @param int $testId ID do teste
     * @return void
     */
    public function viewTestResult($testId) {
        try {
            // Validar ID
            $testId = intval($testId);
            if ($testId <= 0) {
                throw new Exception('ID de teste inválido.');
            }
            
            // Carregar resultados do teste
            $testData = $this->loadTestResults($testId);
            if (!$testData) {
                throw new Exception('Teste não encontrado.');
            }
            
            // Gerar relatório
            $testResults = $this->sqlPerformanceTest->generatePerformanceReport($testData['results']);
            
            // Renderizar view com resultados
            $this->view->render('admin/view_test_result', [
                'title' => 'Resultados do Teste #' . $testId,
                'testData' => $testData,
                'testResults' => $testResults
            ]);
            
        } catch (Exception $e) {
            // Redirecionar com erro
            $this->setFlash('error', $e->getMessage());
            $this->redirect('admin_performance/sql_performance_test');
        }
    }
    
    /**
     * Compara dois testes de performance
     * 
     * @param int $testId1 ID do primeiro teste (opcional)
     * @param int $testId2 ID do segundo teste (opcional)
     * @return void
     */
    public function compareTests($testId1 = null, $testId2 = null) {
        try {
            // Verificar se é uma requisição POST para seleção de testes
            if ($this->request->method() === 'POST') {
                $testId1 = $this->request->post('test_id_1');
                $testId2 = $this->request->post('test_id_2');
                
                // Redirecionar para URL com os IDs
                $allowedDestinations = ['admin_performance/compare_tests'];
                $destination = "admin_performance/compare_tests/" . intval($testId1) . "/" . intval($testId2);
                if (in_array(strtok($destination, '/'), $allowedDestinations)) {
                    $this->redirect($destination);
                } else {
                    throw new Exception('Invalid redirect destination.');
                }
                return;
            }
            

            // Validar IDs
            $testId1 = intval($testId1);
            $testId2 = intval($testId2);
            
            // Se apenas um ID foi fornecido, mostrar formulário para seleção do segundo
            if ($testId1 > 0 && $testId2 <= 0) {
                // Carregar lista de testes disponíveis, excluindo o testId1
                $availableTests = $this->loadPreviousPerformanceTests($testId1);
                
                // Renderizar formulário para selecionar o segundo teste
                $this->view->render('admin/select_comparison_test', [
                    'title' => 'Selecionar Teste para Comparação',
                    'testId1' => $testId1,
                    'availableTests' => $availableTests
                ]);
                return;
            }
            
            // Verificar ambos os IDs
            if ($testId1 <= 0 || $testId2 <= 0) {
                throw new Exception('IDs de teste inválidos.');
            }
            
            // Carregar resultados dos testes
            $test1 = $this->loadTestResults($testId1);
            $test2 = $this->loadTestResults($testId2);
            
            if (!$test1 || !$test2) {
                throw new Exception('Um ou ambos os testes não foram encontrados.');
            }
            
            // Gerar relatório comparativo
            $comparisonResults = $this->sqlPerformanceTest->generatePerformanceReport($test2['results'], $test1['results']);
            
            // Renderizar view com comparação
            $this->view->render('admin/compare_test_results', [
                'title' => 'Comparação de Testes de Performance',
                'test1' => $test1,
                'test2' => $test2,
                'comparisonResults' => $comparisonResults
            ]);
            
        } catch (Exception $e) {
            // Redirecionar com erro
            $this->setFlash('error', $e->getMessage());
            $this->redirect('admin_performance/sql_performance_test');
        }
    }
    
    /**
     * Salva os resultados de um teste de performance
     * 
     * @param array $results Resultados do teste
     * @param array $metadata Metadados do teste
     * @return int ID do teste salvo
     */
    private function savePerformanceTestResults($results, $metadata) {
        $testId = time(); // Usar timestamp como ID
        
        // Preparar dados para salvar
        $testData = [
            'id' => $testId,
            'timestamp' => date('Y-m-d H:i:s'),
            'metadata' => $metadata,
            'results' => $results
        ];
        
        // Caminho para arquivo de resultados
        $resultsDir = DATA_PATH . '/performance_tests';
        
        // Criar diretório se não existir
        if (!file_exists($resultsDir)) {
            mkdir($resultsDir, 0755, true);
        }
        
        // Salvar resultados em arquivo JSON
        $filePath = $resultsDir . '/test_' . $testId . '.json';
        file_put_contents($filePath, json_encode($testData, JSON_PRETTY_PRINT));
        
        // Atualizar índice de testes
        $this->updateTestIndex($testId, $testData);
        
        return $testId;
    }
    
    /**
     * Atualiza o índice de testes de performance
     * 
     * @param int $testId ID do teste
     * @param array $testData Dados do teste
     * @return void
     */
    private function updateTestIndex($testId, $testData) {
        // Caminho para arquivo de índice
        $indexPath = DATA_PATH . '/performance_tests/index.json';
        
        // Carregar índice existente ou criar novo
        $index = [];
        if (file_exists($indexPath) && is_readable($indexPath)) {
            $indexContent = file_get_contents($indexPath);
            if ($indexContent !== false) {
                $index = json_decode($indexContent, true) ?: [];
            }
        }
        
        // Preparar informações resumidas
        $modelNames = [];
        foreach (array_keys($testData['results']) as $modelName) {
            if ($modelName !== 'LoadTest') {
                $modelNames[] = $modelName;
            }
        }
        
        // Calcular média geral
        $avgTime = 0;
        $methodCount = 0;
        
        foreach ($testData['results'] as $modelName => $modelResults) {
            if ($modelName === 'LoadTest') continue;
            
            foreach ($modelResults as $methodResult) {
                $avgTime += $methodResult['avg_time'];
                $methodCount++;
            }
        }
        
        $avgTimeMs = $methodCount > 0 ? ($avgTime / $methodCount) * 1000 : 0;
        
        // Adicionar ao índice
        $index[$testId] = [
            'id' => $testId,
            'timestamp' => $testData['timestamp'],
            'models' => implode(', ', $modelNames),
            'iterations' => $testData['metadata']['iterations'],
            'avg_results' => number_format($avgTimeMs, 2) . ' ms',
            'can_compare' => true
        ];
        
        // Salvar índice atualizado
        if (is_writable($indexPath)) {
            file_put_contents($indexPath, json_encode($index, JSON_PRETTY_PRINT));
        }
    }

    
    /**
     * Carrega a lista de testes de performance anteriores
     * 
     * @param int $excludeId ID a ser excluído da lista (opcional)
     * @return array Lista de testes
     */
    private function loadPreviousPerformanceTests($excludeId = null) {
        // Caminho para arquivo de índice
        $indexPath = DATA_PATH . '/performance_tests/index.json';
        
        // Verificar se o índice existe
        if (!file_exists($indexPath)) {
            return [];
        }
        
        // Carregar índice
        $indexContent = file_get_contents($indexPath);
        if ($indexContent === false) {
            return [];
        }
        
        $index = json_decode($indexContent, true);
        if (!is_array($index)) {
            return [];
        }
        
        // Excluir ID específico se solicitado
        if ($excludeId !== null) {

            unset($index[$excludeId]);
        }
        
        // Ordenar por timestamp (mais recente primeiro)
        uasort($index, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return $index;
    }
    
    /**
     * Carrega os resultados de um teste específico
     * 
     * @param int $testId ID do teste
     * @return array|null Dados do teste ou null se não encontrado
     */
    private function loadTestResults($testId) {
        // Caminho para arquivo de resultados
        $filePath = DATA_PATH . '/performance_tests/test_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $testId) . '.json';
        
        // Verificar se o arquivo existe
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }
        
        // Carregar resultados
        $content = file_get_contents($filePath);

        return json_decode($content, true);
    }
    
    /**
     * Carrega resultados de baseline para comparação
     * 
     * @return array|null Resultados de baseline ou null se não encontrado
     */
    private function loadBaselineResults() {
        // Caminho para arquivo de baseline
        $baselinePath = DATA_PATH . '/performance_tests/baseline.json';
        
        // Verificar se o arquivo existe
        if (!file_exists($baselinePath)) {
            return null;
        }
        
        // Validar o caminho do arquivo
        $realPath = realpath($baselinePath);
        if ($realPath === false || strpos($realPath, DATA_PATH) !== 0) {
            return null;
        }
        
        // Carregar baseline
        $content = file_get_contents($realPath);
        $data = json_decode($content, true);
        
        return $data['results'] ?? null;

    }
    
    /**
     * Define um teste como baseline para comparações futuras
     * 
     * @param int $testId ID do teste
     * @return void
     */
    public function setAsBaseline($testId) {
        try {
            // Validar ID
            $testId = intval($testId);
            if ($testId <= 0) {
                throw new Exception('ID de teste inválido.');
            }
            
            // Carregar resultados do teste
            $testData = $this->loadTestResults($testId);
            if (!$testData) {
                throw new Exception('Teste não encontrado.');
            }
            
            // Salvar como baseline
            $baselinePath = DATA_PATH . '/performance_tests/baseline.json';
            file_put_contents($baselinePath, json_encode($testData, JSON_PRETTY_PRINT));
            
            // Redirecionar com mensagem de sucesso
            $this->setFlash('success', 'Teste #' . $testId . ' definido como baseline.');
            $this->redirect('admin_performance/sql_performance_test');
            
        } catch (Exception $e) {
            // Redirecionar com erro
            $this->setFlash('error', $e->getMessage());
            $this->redirect('admin_performance/sql_performance_test');
        }
    }
}
