<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Controlador para Testes de Performance
 * Responsável por gerenciar testes de performance em ambiente de produção,
 * incluindo coleta de métricas, análise de resultados e geração de relatórios
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

class PerformanceTestController {
    private $model;
    private $baseView = 'admin/';
    private $testTypes = [
        'page_load',      // Teste de tempo de carregamento de página
        'resource_load',  // Teste de tempo de carregamento de recursos (CSS, JS, imagens)
        'api_response',   // Teste de tempo de resposta de API
        'db_query',       // Teste de performance de consultas ao banco de dados
        'render_time',    // Teste de tempo de renderização no cliente
        'memory_usage',   // Teste de uso de memória
        'network',        // Teste de uso de rede
        'full_page'       // Teste completo de página (combina outros testes)
    ];
    
    /**
     * Construtor
     * Inicializa o modelo de teste de performance
     */
    public function __construct() {
        require_once 'app/models/PerformanceTestModel.php';
        $this->model = new PerformanceTestModel();
        
        // Verificar se o helper existe e incluí-lo
        if (file_exists('app/helpers/PerformanceHelper.php')) {
            require_once 'app/helpers/PerformanceHelper.php';
        }
    }
    
    /**
     * Página principal para visualização e execução de testes
     * Exibe uma interface para gerenciar testes de performance
     */
    public function index() {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        // Obter os testes existentes para exibição
        $data = [
            'title' => 'Testes de Performance | Painel Administrativo',
            'tests' => $this->model->getTests(),
            'testTypes' => $this->testTypes,
            'pages' => $this->getTestablePages(),
            'summary' => $this->model->getPerformanceSummary()
        ];
        
        // Renderizar a view
        require_once 'app/views/' . $this->baseView . 'performance_tests.php';
    }
    
    /**
     * Executa um teste de performance específico
     * 
     * @param string $type Tipo de teste (page_load, resource_load, etc.)
     * @param array $params Parâmetros adicionais para o teste
     * @return array Resultados do teste em formato JSON
     */
    public function runTest($type = 'page_load', $params = []) {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Acesso negado'], 403);
        }
        
        // Verificar se o tipo de teste é válido
        if (!in_array($type, $this->testTypes)) {
            return $this->jsonResponse(['error' => 'Tipo de teste inválido'], 400);
        }
        
        // Inicializar resultados
        $results = [];
        
        // Executar teste baseado no tipo
        switch ($type) {
            case 'page_load':
                $results = $this->runPageLoadTest($params);
                break;
                
            case 'resource_load':
                $results = $this->runResourceLoadTest($params);
                break;
                
            case 'api_response':
                $results = $this->runApiResponseTest($params);
                break;
                
            case 'db_query':
                $results = $this->runDbQueryTest($params);
                break;
                
            case 'render_time':
                $results = $this->runRenderTimeTest($params);
                break;
                
            case 'memory_usage':
                $results = $this->runMemoryUsageTest($params);
                break;
                
            case 'network':
                $results = $this->runNetworkTest($params);
                break;
                
            case 'full_page':
                $results = $this->runFullPageTest($params);
                break;
        }
        
        // Salvar resultados no banco de dados
        $testId = $this->model->saveTestResults($type, $params, $results);
        
        // Adicionar ID do teste aos resultados
        $results['test_id'] = $testId;
        
        return $this->jsonResponse($results);
    }
    
    /**
     * Recebe métricas de performance coletadas no cliente
     * 
     * @return array Confirmação de recebimento em formato JSON
     */
    public function collectMetrics() {
        // Verificar se a requisição é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['error' => 'Método não permitido'], 405);
        }
        
        // Obter dados da requisição
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar dados básicos
        if (!isset($data['pageUrl']) || !isset($data['metrics'])) {
            return $this->jsonResponse(['error' => 'Dados incompletos'], 400);
        }
        
        // Sanitizar e processar dados
        $metrics = $data['metrics'];
        $pageUrl = filter_var($data['pageUrl'], FILTER_SANITIZE_URL);
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Desconhecido';
        $timestamp = date('Y-m-d H:i:s');
        
        // Salvar métricas no modelo
        $result = $this->model->saveClientMetrics($pageUrl, $metrics, $userAgent, $timestamp);
        
        if ($result) {
            return $this->jsonResponse(['success' => true, 'message' => 'Métricas recebidas com sucesso']);
        } else {
            return $this->jsonResponse(['error' => 'Erro ao salvar métricas'], 500);
        }
    }
    
    /**
     * Exibe relatório detalhado de um teste específico
     * 
     * @param int $testId ID do teste a ser exibido
     */
    public function viewReport($testId = null) {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        // Verificar se o ID do teste foi fornecido
        if ($testId === null) {
            header('Location: ?page=performance_test');
            exit;
        }
        
        // Obter dados do teste
        $testData = $this->model->getTestById($testId);
        
        if (!$testData) {
            $_SESSION['error'] = 'Teste não encontrado';
            header('Location: ?page=performance_test');
            exit;
        }
        
        // Preparar dados para a view
        $data = [
            'title' => 'Relatório de Teste de Performance | Painel Administrativo',
            'test' => $testData,
            'recommendations' => $this->generateRecommendations($testData)
        ];
        
        // Renderizar a view
        require_once 'app/views/' . $this->baseView . 'performance_report.php';
    }
    
    /**
     * Gera um relatório comparativo de performance
     * 
     * @param array $testIds IDs dos testes a serem comparados
     */
    public function compareTests($testIds = []) {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        // Verificar se foram fornecidos IDs de teste
        if (empty($testIds)) {
            $_SESSION['error'] = 'Nenhum teste selecionado para comparação';
            header('Location: ?page=performance_test');
            exit;
        }
        
        // Obter dados dos testes
        $testsData = [];
        foreach ($testIds as $id) {
            $test = $this->model->getTestById($id);
            if ($test) {
                $testsData[] = $test;
            }
        }
        
        if (empty($testsData)) {
            $_SESSION['error'] = 'Nenhum teste válido encontrado para comparação';
            header('Location: ?page=performance_test');
            exit;
        }
        
        // Preparar dados para a view
        $data = [
            'title' => 'Comparação de Testes de Performance | Painel Administrativo',
            'tests' => $testsData,
            'comparisonData' => $this->generateComparisonData($testsData)
        ];
        
        // Renderizar a view
        require_once 'app/views/' . $this->baseView . 'performance_comparison.php';
    }
    
    /**
     * Configuração dos testes de performance
     */
    public function settings() {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        // Processar envio de formulário
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settings = [
                'automatic_tests' => isset($_POST['automatic_tests']) ? (bool)$_POST['automatic_tests'] : false,
                'test_interval' => isset($_POST['test_interval']) ? (int)$_POST['test_interval'] : 24,
                'pages_to_test' => isset($_POST['pages_to_test']) ? $_POST['pages_to_test'] : [],
                'notification_email' => isset($_POST['notification_email']) ? $_POST['notification_email'] : '',
                'performance_threshold' => isset($_POST['performance_threshold']) ? (int)$_POST['performance_threshold'] : 1000
            ];
            
            $this->model->saveSettings($settings);
            $_SESSION['success'] = 'Configurações salvas com sucesso';
            
            header('Location: ?page=performance_test&action=settings');
            exit;
        }
        
        // Obter configurações atuais
        $data = [
            'title' => 'Configurações de Testes de Performance | Painel Administrativo',
            'settings' => $this->model->getSettings(),
            'pages' => $this->getTestablePages()
        ];
        
        // Renderizar a view
        require_once 'app/views/' . $this->baseView . 'performance_settings.php';
    }
    
    /**
     * Executar teste de carregamento de página
     * 
     * @param array $params Parâmetros do teste
     * @return array Resultados do teste
     */
    private function runPageLoadTest($params) {
        // Verificar parâmetros obrigatórios
        if (!isset($params['url'])) {
            return ['error' => 'URL não especificada'];
        }
        
        $url = $params['url'];
        $iterations = isset($params['iterations']) ? (int)$params['iterations'] : 3;
        $results = [];
        
        // Inicializar métricas
        $totalTime = 0;
        $minTime = PHP_FLOAT_MAX;
        $maxTime = 0;
        
        // Executar múltiplas iterações para obter uma média
        for ($i = 0; $i < $iterations; $i++) {
            // Medir tempo de carregamento da página
            $startTime = microtime(true);
            
            // Usar curl para simular carregamento da página
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Taverna-Performance-Test-Bot/1.0');
            
            $response = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            
            $endTime = microtime(true);
            $loadTime = ($endTime - $startTime) * 1000; // Converter para milissegundos
            
            // Atualizar estatísticas
            $totalTime += $loadTime;
            $minTime = min($minTime, $loadTime);
            $maxTime = max($maxTime, $loadTime);
            
            // Guardar informações desta iteração
            $results['iterations'][] = [
                'time' => round($loadTime, 2),
                'http_code' => $info['http_code'],
                'size' => $info['size_download'],
                'time_to_first_byte' => round($info['starttransfer_time'] * 1000, 2),
                'connect_time' => round($info['connect_time'] * 1000, 2),
                'redirect_time' => round($info['redirect_time'] * 1000, 2),
                'redirect_count' => $info['redirect_count']
            ];
        }
        
        // Calcular médias
        $avgTime = $totalTime / $iterations;
        
        // Preparar resultado final
        $results['summary'] = [
            'url' => $url,
            'iterations' => $iterations,
            'avg_load_time' => round($avgTime, 2),
            'min_load_time' => round($minTime, 2),
            'max_load_time' => round($maxTime, 2),
            'timestamp' => date('Y-m-d H:i:s'),
            'test_type' => 'page_load'
        ];
        
        // Adicionar avaliação de desempenho
        if ($avgTime < 500) {
            $results['summary']['performance_rating'] = 'Excelente';
        } elseif ($avgTime < 1000) {
            $results['summary']['performance_rating'] = 'Bom';
        } elseif ($avgTime < 2000) {
            $results['summary']['performance_rating'] = 'Regular';
        } else {
            $results['summary']['performance_rating'] = 'Ruim';
        }
        
        return $results;
    }
    
    /**
     * Executar teste de carregamento de recursos
     * 
     * @param array $params Parâmetros do teste
     * @return array Resultados do teste
     */
    private function runResourceLoadTest($params) {
        // Implementação básica - será expandida em versões futuras
        return [
            'status' => 'pending',
            'message' => 'Teste de carregamento de recursos requer implementação de JavaScript no cliente',
            'timestamp' => date('Y-m-d H:i:s'),
            'test_type' => 'resource_load'
        ];
    }
    
    /**
     * Executar teste de resposta da API
     * 
     * @param array $params Parâmetros do teste
     * @return array Resultados do teste
     */
    private function runApiResponseTest($params) {
        // Verificar parâmetros obrigatórios
        if (!isset($params['endpoint'])) {
            return ['error' => 'Endpoint não especificado'];
        }
        
        $endpoint = $params['endpoint'];
        $method = isset($params['method']) ? strtoupper($params['method']) : 'GET';
        $data = isset($params['data']) ? $params['data'] : null;
        $iterations = isset($params['iterations']) ? (int)$params['iterations'] : 5;
        $results = [];
        
        // Inicializar métricas
        $totalTime = 0;
        $minTime = PHP_FLOAT_MAX;
        $maxTime = 0;
        
        // Base URL para endpoints da API
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";
        $url = $baseUrl . '/api/' . ltrim($endpoint, '/');
        
        // Executar múltiplas iterações para obter uma média
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            
            // Configurar curl para a requisição
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            if ($method === 'POST' && $data) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            } elseif ($method !== 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                }
            }
            
            $response = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // Converter para milissegundos
            
            // Atualizar estatísticas
            $totalTime += $responseTime;
            $minTime = min($minTime, $responseTime);
            $maxTime = max($maxTime, $responseTime);
            
            // Guardar informações desta iteração
            $results['iterations'][] = [
                'time' => round($responseTime, 2),
                'http_code' => $info['http_code'],
                'size' => $info['size_download'],
                'time_to_first_byte' => round($info['starttransfer_time'] * 1000, 2)
            ];
        }
        
        // Calcular médias
        $avgTime = $totalTime / $iterations;
        
        // Preparar resultado final
        $results['summary'] = [
            'endpoint' => $endpoint,
            'method' => $method,
            'iterations' => $iterations,
            'avg_response_time' => round($avgTime, 2),
            'min_response_time' => round($minTime, 2),
            'max_response_time' => round($maxTime, 2),
            'timestamp' => date('Y-m-d H:i:s'),
            'test_type' => 'api_response'
        ];
        
        // Adicionar avaliação de desempenho
        if ($avgTime < 100) {
            $results['summary']['performance_rating'] = 'Excelente';
        } elseif ($avgTime < 300) {
            $results['summary']['performance_rating'] = 'Bom';
        } elseif ($avgTime < 500) {
            $results['summary']['performance_rating'] = 'Regular';
        } else {
            $results['summary']['performance_rating'] = 'Ruim';
        }
        
        return $results;
    }
    
    /**
     * Executar teste de consultas ao banco de dados
     * 
     * @param array $params Parâmetros do teste
     * @return array Resultados do teste
     */
    private function runDbQueryTest($params) {
        // Verificar parâmetros obrigatórios
        if (!isset($params['query_type'])) {
            return ['error' => 'Tipo de consulta não especificado'];
        }
        
        $queryType = $params['query_type'];
        $iterations = isset($params['iterations']) ? (int)$params['iterations'] : 10;
        $results = [];
        
        // Tempos médios para diferentes tipos de consultas
        $queryTypes = [
            'products_all' => 'Todos os produtos',
            'products_category' => 'Produtos por categoria',
            'products_search' => 'Busca de produtos',
            'order_details' => 'Detalhes de pedido',
            'dashboard_stats' => 'Estatísticas do dashboard'
        ];
        
        if (!array_key_exists($queryType, $queryTypes)) {
            return ['error' => 'Tipo de consulta inválido'];
        }
        
        // Inicializar métricas
        $totalTime = 0;
        $minTime = PHP_FLOAT_MAX;
        $maxTime = 0;
        
        // Executar consulta várias vezes
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            
            // Executar consulta baseada no tipo
            switch ($queryType) {
                case 'products_all':
                    $query = "SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT 100";
                    break;
                    
                case 'products_category':
                    $categoryId = isset($params['category_id']) ? (int)$params['category_id'] : 1;
                    $query = "SELECT p.*, pi.image FROM products p 
                              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                              WHERE p.category_id = {$categoryId} AND p.is_active = 1 
                              ORDER BY p.created_at DESC LIMIT 50";
                    break;
                    
                case 'products_search':
                    $searchTerm = isset($params['search_term']) ? $params['search_term'] : 'miniatura';
                    $query = "SELECT p.*, pi.image FROM products p 
                              LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                              WHERE (p.name LIKE '%{$searchTerm}%' OR p.description LIKE '%{$searchTerm}%') 
                                    AND p.is_active = 1 
                              ORDER BY p.name ASC LIMIT 50";
                    break;
                    
                case 'order_details':
                    $orderId = isset($params['order_id']) ? (int)$params['order_id'] : 1;
                    $query = "SELECT o.*, oi.*, p.name as product_name, p.sku
                              FROM orders o
                              JOIN order_items oi ON o.id = oi.order_id
                              JOIN products p ON oi.product_id = p.id
                              WHERE o.id = {$orderId}";
                    break;
                    
                case 'dashboard_stats':
                    $query = "SELECT 
                                (SELECT COUNT(*) FROM orders WHERE status = 'completed') as completed_orders,
                                (SELECT COUNT(*) FROM orders WHERE status = 'processing') as processing_orders,
                                (SELECT COUNT(*) FROM products WHERE is_active = 1) as active_products,
                                (SELECT COUNT(*) FROM print_queue WHERE status = 'pending') as pending_prints,
                                (SELECT COUNT(*) FROM print_queue WHERE status = 'printing') as active_prints,
                                (SELECT COUNT(*) FROM users) as total_users";
                    break;
            }
            
            // Executar a consulta usando o modelo
            $this->model->testDatabaseQuery($query);
            
            $endTime = microtime(true);
            $queryTime = ($endTime - $startTime) * 1000; // Converter para milissegundos
            
            // Atualizar estatísticas
            $totalTime += $queryTime;
            $minTime = min($minTime, $queryTime);
            $maxTime = max($maxTime, $queryTime);
            
            // Guardar informações desta iteração
            $results['iterations'][] = [
                'time' => round($queryTime, 2),
                'iteration' => $i + 1
            ];
            
            // Pequena pausa para não sobrecarregar o banco
            usleep(50000); // 50ms
        }
        
        // Calcular médias
        $avgTime = $totalTime / $iterations;
        
        // Preparar resultado final
        $results['summary'] = [
            'query_type' => $queryType,
            'query_description' => $queryTypes[$queryType],
            'iterations' => $iterations,
            'avg_query_time' => round($avgTime, 2),
            'min_query_time' => round($minTime, 2),
            'max_query_time' => round($maxTime, 2),
            'timestamp' => date('Y-m-d H:i:s'),
            'test_type' => 'db_query'
        ];
        
        // Adicionar avaliação de desempenho
        if ($avgTime < 50) {
            $results['summary']['performance_rating'] = 'Excelente';
        } elseif ($avgTime < 100) {
            $results['summary']['performance_rating'] = 'Bom';
        } elseif ($avgTime < 200) {
            $results['summary']['performance_rating'] = 'Regular';
        } else {
            $results['summary']['performance_rating'] = 'Ruim';
        }
        
        return $results;
    }
    
    /**
     * Executar teste de tempo de renderização no cliente
     * 
     * @param array $params Parâmetros do teste
     * @return array Resultados do teste
     */
    private function runRenderTimeTest($params) {
        // Implementação básica - será expandida em versões futuras
        return [
            'status' => 'pending',
            'message' => 'Teste de tempo de renderização requer implementação de JavaScript no cliente',
            'timestamp' => date('Y-m-d H:i:s'),
            'test_type' => 'render_time'
        ];
    }
    
    /**
     * Executar teste de uso de memória
     * 
     * @param array $params Parâmetros do teste
     * @return array Resultados do teste
     */
    private function runMemoryUsageTest($params) {
        // Captura do uso de memória do servidor
        $memoryBefore = memory_get_usage();
        
        // Executar alguma operação que consome memória
        $operation = isset($params['operation']) ? $params['operation'] : 'default';
        $results = [];
        
        switch ($operation) {
            case 'product_load':
                // Simular carregamento de produtos
                $products = $this->model->getAllProducts();
                break;
                
            case 'category_tree':
                // Simular carregamento da árvore de categorias
                $categories = $this->model->getAllCategoriesWithProducts();
                break;
                
            case 'order_history':
                // Simular carregamento do histórico de pedidos
                $orders = $this->model->getAllOrders();
                break;
                
            default:
                // Operação padrão: carregamento básico de produtos
                $products = $this->model->getRecentProducts(50);
                break;
        }
        
        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        // Preparar resultado
        $results['summary'] = [
            'operation' => $operation,
            'memory_before' => round($memoryBefore / 1024 / 1024, 2) . ' MB',
            'memory_after' => round($memoryAfter / 1024 / 1024, 2) . ' MB',
            'memory_used' => round($memoryUsed / 1024 / 1024, 2) . ' MB',
            'memory_peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
            'timestamp' => date('Y-m-d H:i:s'),
            'test_type' => 'memory_usage'
        ];
        
        return $results;
    }
    
    /**
     * Executar teste de rede
     * 
     * @param array $params Parâmetros do teste
     * @return array Resultados do teste
     */
    private function runNetworkTest($params) {
        // Implementação básica - será expandida em versões futuras
        return [
            'status' => 'pending',
            'message' => 'Teste de rede requer implementação de JavaScript no cliente',
            'timestamp' => date('Y-m-d H:i:s'),
            'test_type' => 'network'
        ];
    }
    
    /**
     * Executar teste completo de página
     * 
     * @param array $params Parâmetros do teste
     * @return array Resultados do teste
     */
    private function runFullPageTest($params) {
        // Executa todos os testes em sequência para uma página específica
        $url = isset($params['url']) ? $params['url'] : '';
        
        if (empty($url)) {
            return ['error' => 'URL não especificada para teste completo'];
        }
        
        $results = [
            'url' => $url,
            'timestamp' => date('Y-m-d H:i:s'),
            'test_type' => 'full_page'
        ];
        
        // Executar teste de carregamento de página
        $pageLoadParams = ['url' => $url, 'iterations' => 3];
        $results['page_load'] = $this->runPageLoadTest($pageLoadParams)['summary'];
        
        // Executar teste de banco de dados relacionado
        $dbQueryParams = ['query_type' => 'products_all'];
        $results['db_query'] = $this->runDbQueryTest($dbQueryParams)['summary'];
        
        // Executar teste de uso de memória
        $memoryParams = ['operation' => 'default'];
        $results['memory_usage'] = $this->runMemoryUsageTest($memoryParams)['summary'];
        
        // Resultado consolidado
        $avgLoadTime = $results['page_load']['avg_load_time'];
        $avgQueryTime = $results['db_query']['avg_query_time'];
        
        if ($avgLoadTime < 1000 && $avgQueryTime < 100) {
            $results['overall_rating'] = 'Excelente';
        } elseif ($avgLoadTime < 2000 && $avgQueryTime < 200) {
            $results['overall_rating'] = 'Bom';
        } elseif ($avgLoadTime < 3000 && $avgQueryTime < 300) {
            $results['overall_rating'] = 'Regular';
        } else {
            $results['overall_rating'] = 'Ruim';
        }
        
        return ['summary' => $results];
    }
    
    /**
     * Gera recomendações baseadas nos resultados do teste
     * 
     * @param array $testData Dados do teste
     * @return array Lista de recomendações
     */
    private function generateRecommendations($testData) {
        $recommendations = [];
        
        // Verificar o tipo de teste
        switch ($testData['test_type']) {
            case 'page_load':
                // Recomendações para tempo de carregamento de página
                $avgTime = $testData['summary']['avg_load_time'];
                
                if ($avgTime > 2000) {
                    $recommendations[] = 'O tempo de carregamento está muito alto. Considere otimizar recursos da página.';
                    $recommendations[] = 'Verifique recursos externos que podem estar atrasando o carregamento.';
                    $recommendations[] = 'Considere implementar carregamento assíncrono para scripts não essenciais.';
                } elseif ($avgTime > 1000) {
                    $recommendations[] = 'O tempo de carregamento está acima do ideal. Considere otimizações adicionais.';
                    $recommendations[] = 'Verifique o tamanho das imagens e considere a compressão.';
                }
                
                if (isset($testData['iterations']) && count($testData['iterations']) > 0) {
                    // Verificar tempo de first byte
                    $ttfbAvg = array_reduce($testData['iterations'], function($carry, $item) {
                        return $carry + $item['time_to_first_byte'];
                    }, 0) / count($testData['iterations']);
                    
                    if ($ttfbAvg > 300) {
                        $recommendations[] = 'O tempo até o primeiro byte está alto. Verifique a configuração do servidor ou otimize processamento no backend.';
                    }
                }
                break;
                
            case 'db_query':
                // Recomendações para consultas de banco de dados
                $avgTime = $testData['summary']['avg_query_time'];
                
                if ($avgTime > 200) {
                    $recommendations[] = 'O tempo de consulta está muito alto. Considere otimizar a consulta ou adicionar índices.';
                    $recommendations[] = 'Verifique a possibilidade de cache para consultas frequentes.';
                } elseif ($avgTime > 100) {
                    $recommendations[] = 'O tempo de consulta está acima do ideal. Verifique a estrutura da consulta.';
                }
                break;
                
            case 'memory_usage':
                // Recomendações para uso de memória
                $memoryUsed = str_replace(' MB', '', $testData['summary']['memory_used']);
                
                if ($memoryUsed > 20) {
                    $recommendations[] = 'O uso de memória está alto. Considere otimizar o carregamento de dados.';
                    $recommendations[] = 'Verifique se há vazamentos de memória ou objetos grandes desnecessários.';
                }
                break;
                
            case 'full_page':
                // Recomendações para teste completo
                $overallRating = $testData['summary']['overall_rating'];
                
                if ($overallRating === 'Ruim') {
                    $recommendations[] = 'O desempenho geral da página está comprometido. É recomendável uma revisão ampla das otimizações.';
                    $recommendations[] = 'Considere consultar os resultados detalhados de cada teste para identificar os gargalos específicos.';
                } elseif ($overallRating === 'Regular') {
                    $recommendations[] = 'O desempenho da página pode ser melhorado. Verifique os componentes com pior desempenho.';
                }
                break;
        }
        
        // Recomendações gerais
        if (empty($recommendations)) {
            $recommendations[] = 'O desempenho está dentro dos parâmetros esperados. Continue monitorando regularmente.';
        }
        
        return $recommendations;
    }
    
    /**
     * Gera dados para comparação entre testes
     * 
     * @param array $testsData Array de dados de testes
     * @return array Dados formatados para comparação
     */
    private function generateComparisonData($testsData) {
        $comparisonData = [
            'labels' => [],
            'datasets' => []
        ];
        
        // Verificar se todos os testes são do mesmo tipo
        $testType = $testsData[0]['test_type'];
        $sameType = true;
        
        foreach ($testsData as $test) {
            if ($test['test_type'] !== $testType) {
                $sameType = false;
                break;
            }
        }
        
        if (!$sameType) {
            return [
                'error' => 'Os testes selecionados são de tipos diferentes. A comparação só é possível entre testes do mesmo tipo.'
            ];
        }
        
        // Gerar dados específicos para cada tipo de teste
        switch ($testType) {
            case 'page_load':
                foreach ($testsData as $index => $test) {
                    $comparisonData['labels'][] = "Teste " . ($index + 1) . " (" . date('d/m/Y H:i', strtotime($test['timestamp'])) . ")";
                    $loadTimes[] = $test['summary']['avg_load_time'];
                }
                
                $comparisonData['datasets'][] = [
                    'label' => 'Tempo Médio de Carregamento (ms)',
                    'data' => $loadTimes,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.5)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'borderWidth' => 1
                ];
                break;
                
            case 'db_query':
                foreach ($testsData as $index => $test) {
                    $comparisonData['labels'][] = "Teste " . ($index + 1) . " (" . date('d/m/Y H:i', strtotime($test['timestamp'])) . ")";
                    $queryTimes[] = $test['summary']['avg_query_time'];
                }
                
                $comparisonData['datasets'][] = [
                    'label' => 'Tempo Médio de Consulta (ms)',
                    'data' => $queryTimes,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.5)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'borderWidth' => 1
                ];
                break;
                
            // Outros tipos de testes podem ser adicionados aqui
        }
        
        return $comparisonData;
    }
    
    /**
     * Obtém a lista de páginas que podem ser testadas
     * 
     * @return array Lista de páginas testáveis
     */
    private function getTestablePages() {
        // Lista de páginas principais do sistema
        return [
            'home' => [
                'name' => 'Página Inicial',
                'url' => '/'
            ],
            'products' => [
                'name' => 'Listagem de Produtos',
                'url' => '/?page=products'
            ],
            'product_detail' => [
                'name' => 'Detalhe de Produto',
                'url' => '/?page=product&slug=produto-exemplo'
            ],
            'categories' => [
                'name' => 'Categorias',
                'url' => '/?page=categories'
            ],
            'cart' => [
                'name' => 'Carrinho',
                'url' => '/?page=cart'
            ],
            'checkout' => [
                'name' => 'Checkout',
                'url' => '/?page=checkout'
            ],
            'customization' => [
                'name' => 'Personalização 3D',
                'url' => '/?page=customization'
            ],
            'admin_dashboard' => [
                'name' => 'Dashboard Admin',
                'url' => '/?page=admin&action=dashboard'
            ]
        ];
    }
    
    /**
     * Verifica se o usuário atual tem permissões administrativas
     * 
     * @return bool True se o usuário for admin, false caso contrário
     */
    private function isAdmin() {
        // Verificar se a sessão está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar se o usuário está logado e é admin
        return isset($_SESSION['user']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
    }
    
    /**
     * Redireciona o usuário para a página de login
     */
    private function redirectToLogin() {
        $_SESSION['error'] = 'Acesso restrito. Faça login como administrador.';
        header('Location: ?page=login&redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    /**
     * Retorna uma resposta JSON
     * 
     * @param array $data Dados a serem retornados
     * @param int $status Código de status HTTP
     * @return string JSON formatado
     */
    private function jsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}
?>