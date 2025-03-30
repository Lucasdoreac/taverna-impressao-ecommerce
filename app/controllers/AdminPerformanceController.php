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
    
    // Model de produtos para análise
    private $productModel;
    
    // Model de categorias para análise
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
        
        // Renderizar view com dados
        $this->view->render('admin/query_performance', [
            'title' => 'Análise de Performance de Consultas SQL',
            'report' => $slowQueriesReport,
            'date' => $date,
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
     * @param string $date Data no formato Y-m-d
     * @return void
     */
    public function dailyReport() {
        // Obter data do request
        $date = $this->request->get('date') ?: date('Y-m-d');
        
        // Obter relatório
        $slowQueriesReport = $this->queryOptimizer->analyzeSlowQueries($date);
        
        // Renderizar view com dados
        $this->view->render('admin/query_performance', [
            'title' => 'Análise de Performance de Consultas SQL - ' . $date,
            'report' => $slowQueriesReport,
            'date' => $date,
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
     * @param string $model Nome do modelo
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
                $content = file_get_contents($modelFile);
                
                // Extrair consultas SQL
                preg_match_all('/\\$sql\\s*=\\s*["\']([^"\']+)["\']/s', $content, $matches);
                $queries = $matches[1] ?? [];
                
                $analysis = [];
                foreach ($queries as $index => $query) {
                    // Extrair método onde a query está sendo usada
                    preg_match('/public\\s+function\\s+(\\w+)[^{]*{[^}]*\\$sql\\s*=\\s*["\']' . preg_quote(substr($query, 0, 50), '/') . '/s', $content, $methodMatches);
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
        
        // Renderizar view com dados
        $this->view->render('admin/model_analysis', [
            'title' => 'Análise de Consultas SQL - ' . $modelDisplayName,
            'model' => $model,
            'modelName' => $modelDisplayName,
            'report' => $analysisResult,
            'reportHtml' => $reportHtml
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
        
        // Renderizar view com recomendações
        $this->view->render('admin/query_recommendations', [
            'title' => 'Recomendações de Otimização de Consultas SQL',
            'recommendations' => $recommendations
        ]);
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
        
        // Ler conteúdo do arquivo
        $content = file_get_contents($modelFile);
        
        // Extrair todas as consultas SQL
        preg_match_all('/\\$sql\\s*=\\s*["\']([^"\']+)["\']/s', $content, $matches);
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
}