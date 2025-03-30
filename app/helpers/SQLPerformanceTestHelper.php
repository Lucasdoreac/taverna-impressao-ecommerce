<?php
/**
 * SQLPerformanceTestHelper - Helper para testes de performance de consultas SQL otimizadas
 * 
 * Este helper fornece funções especializadas para testar a performance das otimizações SQL
 * recentemente implementadas, especialmente em ProductModel e CategoryModel. Permite também
 * gerar relatórios comparativos detalhados com métricas de desempenho.
 */
class SQLPerformanceTestHelper {
    // Conexão com o banco de dados
    private $db;
    
    // SQLOptimizationHelper para testes
    private $sqlOptimizer;
    
    // Modelo de produtos para testes
    private $productModel;
    
    // Modelo de categorias para testes
    private $categoryModel;
    
    // Número de iterações para cada teste
    private $iterations = 10;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->sqlOptimizer = new SQLOptimizationHelper();
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
    }
    
    /**
     * Define o número de iterações para os testes
     * 
     * @param int $iterations Número de iterações
     * @return void
     */
    public function setIterations($iterations) {
        $this->iterations = max(1, intval($iterations));
    }
    
    /**
     * Executa testes de performance específicos para o ProductModel
     * 
     * @return array Resultados dos testes
     */
    public function testProductModelPerformance() {
        $results = [];
        
        // 1. Teste de getCustomProducts
        $results['getCustomProducts'] = $this->measureMethodPerformance(
            $this->productModel,
            'getCustomProducts',
            [8]
        );
        
        // 2. Teste de getByCategory
        $results['getByCategory'] = $this->measureMethodPerformance(
            $this->productModel,
            'getByCategory',
            [1, 1, 12, 'all']
        );
        
        // 3. Teste de search com termo comum
        $results['search'] = $this->measureMethodPerformance(
            $this->productModel,
            'search',
            ['impressão', 1, 12, 'all']
        );
        
        // 4. Teste de getFeatured
        $results['getFeatured'] = $this->measureMethodPerformance(
            $this->productModel,
            'getFeatured',
            [8]
        );
        
        // 5. Teste de getBySlug
        // Primeiro, vamos obter um slug real do banco de dados para testar
        $randomProduct = $this->productModel->getLatest(1);
        $testSlug = isset($randomProduct[0]['slug']) ? $randomProduct[0]['slug'] : 'produto-teste';
        
        $results['getBySlug'] = $this->measureMethodPerformance(
            $this->productModel,
            'getBySlug',
            [$testSlug]
        );
        
        return $results;
    }
    
    /**
     * Executa testes de performance específicos para o CategoryModel
     * 
     * @return array Resultados dos testes
     */
    public function testCategoryModelPerformance() {
        $results = [];
        
        // 1. Teste de getAllCategories
        $results['getAllCategories'] = $this->measureMethodPerformance(
            $this->categoryModel,
            'getAllCategories',
            [true]
        );
        
        // 2. Teste de getMainCategories
        $results['getMainCategories'] = $this->measureMethodPerformance(
            $this->categoryModel,
            'getMainCategories',
            [true]
        );
        
        // 3. Teste de getSubcategoriesAll
        // Primeiro, vamos obter uma categoria pai válida
        $mainCategories = $this->categoryModel->getMainCategories(false);
        $testCategoryId = isset($mainCategories[0]['id']) ? $mainCategories[0]['id'] : 1;
        
        $results['getSubcategoriesAll'] = $this->measureMethodPerformance(
            $this->categoryModel,
            'getSubcategoriesAll',
            [$testCategoryId, true]
        );
        
        // 4. Teste de getBreadcrumb
        $results['getBreadcrumb'] = $this->measureMethodPerformance(
            $this->categoryModel,
            'getBreadcrumb',
            [$testCategoryId]
        );
        
        // 5. Teste de getCategoryWithProducts
        // Obter um slug válido para teste
        $testCategory = $this->categoryModel->find($testCategoryId);
        $testSlug = isset($testCategory['slug']) ? $testCategory['slug'] : 'categoria-teste';
        
        $results['getCategoryWithProducts'] = $this->measureMethodPerformance(
            $this->categoryModel,
            'getCategoryWithProducts',
            [$testSlug, 1, 12, true]
        );
        
        return $results;
    }
    
    /**
     * Mede a performance de um método específico
     * 
     * @param object $object Objeto que contém o método
     * @param string $method Nome do método
     * @param array $args Argumentos para o método
     * @return array Resultados das medições
     */
    private function measureMethodPerformance($object, $method, $args) {
        // Resultados
        $times = [];
        $queryCount = 0;
        $memoryUsage = [];
        $results = null;
        
        // Executar método várias vezes para obter média
        for ($i = 0; $i < $this->iterations; $i++) {
            // Limpar estatísticas de consultas
            $queryCountBefore = $this->getQueryCount();
            
            // Registrar memória antes
            $memoryBefore = memory_get_usage(true);
            
            // Registrar tempo inicial
            $startTime = microtime(true);
            
            // Executar método
            try {
                $results = call_user_func_array([$object, $method], $args);
            } catch (Exception $e) {
                // Ignorar erros para fins de teste de performance
            }
            
            // Registrar tempo final
            $endTime = microtime(true);
            $times[] = $endTime - $startTime;
            
            // Registrar memória depois
            $memoryAfter = memory_get_usage(true);
            $memoryUsage[] = $memoryAfter - $memoryBefore;
            
            // Calcular número de consultas executadas
            $queryCountAfter = $this->getQueryCount();
            $queryCount = $queryCountAfter - $queryCountBefore;
            
            // Pequeno delay para dar tempo ao banco de dados
            usleep(50000); // 50ms
        }
        
        // Ordenar tempos para calcular mediana
        sort($times);
        
        // Calcular estatísticas
        $count = count($times);
        $avgTime = array_sum($times) / $count;
        $minTime = min($times);
        $maxTime = max($times);
        $medianTime = $count % 2 === 0 
            ? ($times[$count/2] + $times[$count/2 - 1]) / 2 
            : $times[floor($count/2)];
        
        $avgMemory = array_sum($memoryUsage) / $count;
        
        return [
            'method' => $method,
            'arguments' => $args,
            'iterations' => $this->iterations,
            'times' => $times,
            'avg_time' => $avgTime,
            'min_time' => $minTime,
            'max_time' => $maxTime,
            'median_time' => $medianTime,
            'avg_memory' => $avgMemory,
            'query_count' => $queryCount,
            'result_count' => is_array($results) ? count($results) : 0
        ];
    }
    
    /**
     * Gera relatório HTML com resultados de performance
     * 
     * @param array $results Resultados dos testes
     * @param array $baselineResults Resultados de referência (opcional) para comparação
     * @return string HTML do relatório
     */
    public function generatePerformanceReport($results, $baselineResults = null) {
        $hasBaseline = !empty($baselineResults);
        
        $html = '<div class="performance-report">';
        $html .= '<h2>Relatório de Performance de SQL Otimizado</h2>';
        
        // Sumário geral
        $html .= $this->generateSummarySection($results, $baselineResults);
        
        // Detalhes por modelo
        if (isset($results['ProductModel'])) {
            $baselineProductResults = $hasBaseline && isset($baselineResults['ProductModel']) 
                ? $baselineResults['ProductModel'] 
                : null;
            
            $html .= $this->generateModelSection(
                'ProductModel', 
                $results['ProductModel'], 
                $baselineProductResults
            );
        }
        
        if (isset($results['CategoryModel'])) {
            $baselineCategoryResults = $hasBaseline && isset($baselineResults['CategoryModel']) 
                ? $baselineResults['CategoryModel'] 
                : null;
            
            $html .= $this->generateModelSection(
                'CategoryModel', 
                $results['CategoryModel'], 
                $baselineCategoryResults
            );
        }
        
        // Gráficos (placeholders para implementação JavaScript)
        $html .= '<div class="performance-charts">';
        $html .= '<h3>Visualização Gráfica</h3>';
        $html .= '<div class="chart-container" id="performance-chart-container">';
        $html .= '<p>Os gráficos serão carregados via JavaScript.</p>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Recomendações baseadas nos resultados
        $html .= $this->generateRecommendationsSection($results);
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Gera seção de sumário do relatório
     * 
     * @param array $results Resultados dos testes
     * @param array $baselineResults Resultados de referência (opcional)
     * @return string HTML da seção
     */
    private function generateSummarySection($results, $baselineResults = null) {
        $hasBaseline = !empty($baselineResults);
        
        $html = '<div class="summary-section">';
        $html .= '<h3>Sumário da Performance</h3>';
        
        $html .= '<table class="table table-striped">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Modelo</th>';
        $html .= '<th>Métodos Testados</th>';
        $html .= '<th>Tempo Médio (ms)</th>';
        $html .= '<th>Consultas SQL (média)</th>';
        
        if ($hasBaseline) {
            $html .= '<th>Melhoria (%)</th>';
        }
        
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        foreach ($results as $modelName => $modelResults) {
            $methodCount = count($modelResults);
            $totalTime = 0;
            $totalQueries = 0;
            
            foreach ($modelResults as $methodName => $methodResult) {
                $totalTime += $methodResult['avg_time'];
                $totalQueries += $methodResult['query_count'];
            }
            
            $avgTime = $methodCount > 0 ? $totalTime / $methodCount : 0;
            $avgQueries = $methodCount > 0 ? $totalQueries / $methodCount : 0;
            
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($modelName) . '</td>';
            $html .= '<td>' . $methodCount . '</td>';
            $html .= '<td>' . number_format($avgTime * 1000, 2) . ' ms</td>';
            $html .= '<td>' . number_format($avgQueries, 2) . '</td>';
            
            if ($hasBaseline && isset($baselineResults[$modelName])) {
                $baselineMethodCount = count($baselineResults[$modelName]);
                $baselineTotalTime = 0;
                
                foreach ($baselineResults[$modelName] as $methodName => $methodResult) {
                    $baselineTotalTime += $methodResult['avg_time'];
                }
                
                $baselineAvgTime = $baselineMethodCount > 0 ? $baselineTotalTime / $baselineMethodCount : 0;
                $improvement = $baselineAvgTime > 0 ? ($baselineAvgTime - $avgTime) / $baselineAvgTime * 100 : 0;
                
                $improvementClass = $improvement >= 20 ? 'text-success' : ($improvement >= 5 ? 'text-primary' : 'text-danger');
                $html .= '<td class="' . $improvementClass . '">' . number_format($improvement, 2) . '%</td>';
            }
            
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Gera seção de detalhes para um modelo específico
     * 
     * @param string $modelName Nome do modelo
     * @param array $modelResults Resultados do modelo
     * @param array $baselineResults Resultados de referência (opcional)
     * @return string HTML da seção
     */
    private function generateModelSection($modelName, $modelResults, $baselineResults = null) {
        $hasBaseline = !empty($baselineResults);
        
        $html = '<div class="model-section">';
        $html .= '<h3>Performance do ' . htmlspecialchars($modelName) . '</h3>';
        
        $html .= '<table class="table table-striped">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Método</th>';
        $html .= '<th>Tempo Médio (ms)</th>';
        $html .= '<th>Tempo Mediano (ms)</th>';
        $html .= '<th>Consultas SQL</th>';
        $html .= '<th>Resultados</th>';
        
        if ($hasBaseline) {
            $html .= '<th>Tempo Anterior (ms)</th>';
            $html .= '<th>Melhoria (ms)</th>';
            $html .= '<th>Melhoria (%)</th>';
        }
        
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        foreach ($modelResults as $methodName => $methodResult) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($methodName) . '</td>';
            $html .= '<td>' . number_format($methodResult['avg_time'] * 1000, 2) . ' ms</td>';
            $html .= '<td>' . number_format($methodResult['median_time'] * 1000, 2) . ' ms</td>';
            $html .= '<td>' . $methodResult['query_count'] . '</td>';
            $html .= '<td>' . $methodResult['result_count'] . '</td>';
            
            if ($hasBaseline && isset($baselineResults[$methodName])) {
                $baselineMethodResult = $baselineResults[$methodName];
                $baselineTime = $baselineMethodResult['avg_time'];
                $currentTime = $methodResult['avg_time'];
                
                $improvement = $baselineTime - $currentTime;
                $improvementPercent = $baselineTime > 0 ? ($improvement / $baselineTime * 100) : 0;
                
                $html .= '<td>' . number_format($baselineTime * 1000, 2) . ' ms</td>';
                $html .= '<td>' . number_format($improvement * 1000, 2) . ' ms</td>';
                
                $improvementClass = $improvementPercent >= 20 ? 'text-success' : ($improvementPercent >= 5 ? 'text-primary' : 'text-danger');
                $html .= '<td class="' . $improvementClass . '">' . number_format($improvementPercent, 2) . '%</td>';
            }
            
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Gera seção de recomendações baseadas nos resultados
     * 
     * @param array $results Resultados dos testes
     * @return string HTML da seção
     */
    private function generateRecommendationsSection($results) {
        $html = '<div class="recommendations-section">';
        $html .= '<h3>Recomendações e Próximos Passos</h3>';
        
        $html .= '<div class="alert alert-info">';
        
        // Analisar resultados para identificar possíveis melhorias adicionais
        $slowMethods = [];
        $highQueryCountMethods = [];
        
        foreach ($results as $modelName => $modelResults) {
            foreach ($modelResults as $methodName => $methodResult) {
                // Métodos que demoram mais de 200ms são considerados lentos
                if ($methodResult['avg_time'] > 0.2) {
                    $slowMethods[] = "$modelName::$methodName";
                }
                
                // Métodos que executam mais de 3 consultas podem ser otimizados
                if ($methodResult['query_count'] > 3) {
                    $highQueryCountMethods[] = "$modelName::$methodName";
                }
            }
        }
        
        // Recomendações gerais
        $html .= '<h4>Conclusões da Análise</h4>';
        $html .= '<p>Com base nos testes de performance realizados, podemos concluir que:</p>';
        $html .= '<ul>';
        
        if (empty($slowMethods) && empty($highQueryCountMethods)) {
            $html .= '<li>As otimizações implementadas mostram bons resultados de performance.</li>';
            $html .= '<li>Não foram identificados métodos com tempos de execução críticos.</li>';
            $html .= '<li>O número de consultas SQL por método está dentro do esperado.</li>';
        } else {
            if (!empty($slowMethods)) {
                $html .= '<li>Os seguintes métodos ainda apresentam tempos de execução significativos:<ul>';
                foreach ($slowMethods as $method) {
                    $html .= '<li>' . htmlspecialchars($method) . '</li>';
                }
                $html .= '</ul></li>';
            }
            
            if (!empty($highQueryCountMethods)) {
                $html .= '<li>Os seguintes métodos executam muitas consultas SQL e poderiam ser otimizados:<ul>';
                foreach ($highQueryCountMethods as $method) {
                    $html .= '<li>' . htmlspecialchars($method) . '</li>';
                }
                $html .= '</ul></li>';
            }
        }
        
        $html .= '</ul>';
        
        // Sugestões de próximos passos
        $html .= '<h4>Próximos Passos Recomendados</h4>';
        $html .= '<ol>';
        $html .= '<li>Implementar sistema de cache para métodos frequentemente utilizados</li>';
        $html .= '<li>Considerar a criação de índices compostos para consultas complexas</li>';
        $html .= '<li>Avaliar a implementação de consultas preparadas para uso repetitivo</li>';
        $html .= '<li>Revisar estratégias de paginação para grandes conjuntos de dados</li>';
        $html .= '<li>Implementar monitoramento contínuo de performance em ambiente de produção</li>';
        $html .= '</ol>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Obtém o número atual de consultas SQL executadas
     * 
     * @return int Número de consultas
     */
    private function getQueryCount() {
        try {
            $result = $this->db->select("SHOW SESSION STATUS LIKE 'Questions'");
            return isset($result[0]['Value']) ? intval($result[0]['Value']) : 0;
        } catch (Exception $e) {
            // Fallback para o caso de não conseguir obter a contagem real
            return 0;
        }
    }
    
    /**
     * Executa testes de carga simulando múltiplos usuários
     * 
     * @param int $concurrentUsers Número de usuários simultâneos simulados
     * @param int $iterations Número de iterações por usuário
     * @return array Resultados dos testes
     */
    public function loadTest($concurrentUsers = 10, $iterations = 5) {
        // Definir métodos a serem testados
        $testMethods = [
            ['model' => $this->productModel, 'method' => 'getFeatured', 'args' => [8]],
            ['model' => $this->productModel, 'method' => 'getByCategory', 'args' => [1, 1, 12, 'all']],
            ['model' => $this->categoryModel, 'method' => 'getMainCategories', 'args' => [true]],
        ];
        
        $results = [];
        
        // Simular usuários simultâneos
        for ($user = 0; $user < $concurrentUsers; $user++) {
            $userResults = [];
            
            for ($i = 0; $i < $iterations; $i++) {
                // Selecionar método aleatoriamente
                $methodIndex = rand(0, count($testMethods) - 1);
                $testMethod = $testMethods[$methodIndex];
                
                $modelName = get_class($testMethod['model']);
                $methodName = $testMethod['method'];
                $args = $testMethod['args'];
                
                // Medir performance
                $startTime = microtime(true);
                
                try {
                    call_user_func_array([$testMethod['model'], $methodName], $args);
                } catch (Exception $e) {
                    // Ignorar erros para fins de teste
                }
                
                $endTime = microtime(true);
                $executionTime = $endTime - $startTime;
                
                // Registrar resultado
                $methodKey = "$modelName::$methodName";
                if (!isset($userResults[$methodKey])) {
                    $userResults[$methodKey] = [];
                }
                
                $userResults[$methodKey][] = $executionTime;
                
                // Pequeno delay para simular usuário real
                usleep(rand(50000, 200000)); // 50-200ms
            }
            
            // Calcular médias para este usuário
            foreach ($userResults as $methodKey => $times) {
                if (!isset($results[$methodKey])) {
                    $results[$methodKey] = [];
                }
                
                $results[$methodKey][] = array_sum($times) / count($times);
            }
        }
        
        // Calcular estatísticas finais
        $finalResults = [];
        
        foreach ($results as $methodKey => $userAvgTimes) {
            $finalResults[$methodKey] = [
                'method' => $methodKey,
                'concurrent_users' => $concurrentUsers,
                'iterations_per_user' => $iterations,
                'avg_time' => array_sum($userAvgTimes) / count($userAvgTimes),
                'min_time' => min($userAvgTimes),
                'max_time' => max($userAvgTimes),
                'total_executions' => count($userAvgTimes)
            ];
        }
        
        return $finalResults;
    }
    
    /**
     * Executa uma bateria completa de testes e gera relatório
     * 
     * @param boolean $includeLoadTest Incluir testes de carga
     * @return string HTML do relatório completo
     */
    public function runComprehensiveTests($includeLoadTest = false) {
        // Resultados dos testes
        $results = [
            'ProductModel' => $this->testProductModelPerformance(),
            'CategoryModel' => $this->testCategoryModelPerformance()
        ];
        
        // Adicionar testes de carga se solicitado
        if ($includeLoadTest) {
            $results['LoadTest'] = $this->loadTest(5, 3);
        }
        
        // Gerar relatório
        return $this->generatePerformanceReport($results);
    }
}
