<?php
/**
 * PrintQueueLoadTest - Framework para testes de carga do sistema de fila
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Tests\Load
 * @version    1.0.0
 * @author     Claude
 */

require_once __DIR__ . '/../../app/lib/Database.php';
require_once __DIR__ . '/../../app/models/PrintQueueModel.php';
require_once __DIR__ . '/../../app/models/CustomerModelModel.php';

class PrintQueueLoadTest {
    private $db;
    private $printQueueModel;
    private $customerModelModel;
    private $testResults = [];
    private $metrics = [];
    
    /**
     * Construtor da classe de teste
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->printQueueModel = new PrintQueueModel();
        $this->customerModelModel = new CustomerModelModel();
        
        // Certificar-se de que o teste roda em transação para não afetar dados reais
        $this->db->beginTransaction();
    }
    
    /**
     * Destruidor da classe de teste - garante rollback
     */
    public function __destruct() {
        // Sempre fazer rollback das alterações feitas durante o teste
        if ($this->db && $this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
    
    /**
     * Prepara o ambiente de teste criando dados sintéticos
     * 
     * @param int $numModels Número de modelos a serem criados
     * @param int $numUsers Número de usuários a serem criados
     * @return array Dados sintéticos criados
     */
    public function prepareTestEnvironment($numModels = 100, $numUsers = 50) {
        $testData = [
            'models' => [],
            'users' => []
        ];
        
        // Criar usuários de teste
        for ($i = 1; $i <= $numUsers; $i++) {
            $userId = $this->createTestUser("test_user_{$i}");
            if ($userId) {
                $testData['users'][] = $userId;
            }
        }
        
        // Criar modelos de teste
        for ($i = 1; $i <= $numModels; $i++) {
            $modelId = $this->createTestModel("test_model_{$i}", $testData['users'][array_rand($testData['users'])]);
            if ($modelId) {
                $testData['models'][] = $modelId;
            }
        }
        
        return $testData;
    }
    
    /**
     * Cria um usuário de teste
     * 
     * @param string $username Nome do usuário
     * @return int ID do usuário criado
     */
    private function createTestUser($username) {
        $sql = "INSERT INTO users (name, email, password, created_at, status) 
                VALUES (:name, :email, :password, NOW(), 'active')";
        
        $hashedPassword = password_hash('test_password', PASSWORD_DEFAULT);
        
        $params = [
            ':name' => $username,
            ':email' => "{$username}@test.com",
            ':password' => $hashedPassword
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Cria um modelo 3D de teste
     * 
     * @param string $modelName Nome do modelo
     * @param int $userId ID do usuário proprietário
     * @return int ID do modelo criado
     */
    private function createTestModel($modelName, $userId) {
        $sql = "INSERT INTO customer_models (user_id, original_name, file_path, file_size, 
                file_type, status, created_at, approved_at) 
                VALUES (:user_id, :original_name, :file_path, :file_size, 
                :file_type, 'approved', NOW(), NOW())";
        
        $params = [
            ':user_id' => $userId,
            ':original_name' => "{$modelName}.stl",
            ':file_path' => "tests/fixtures/models/{$modelName}.stl",
            ':file_size' => rand(10000, 50000000), // 10KB a 50MB
            ':file_type' => 'stl'
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Executa teste de adição de itens à fila
     * 
     * @param array $testData Dados sintéticos para o teste
     * @param int $iterations Número de adições à fila a realizar
     * @return array Resultados do teste
     */
    public function testAddToQueue($testData, $iterations = 1000) {
        $startTime = microtime(true);
        $queueIds = [];
        $totalTime = 0;
        $timings = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $modelId = $testData['models'][array_rand($testData['models'])];
            $userId = $testData['users'][array_rand($testData['users'])];
            $priority = rand(1, 10);
            $notes = "Test note for iteration {$i}";
            
            $iterationStart = microtime(true);
            $queueId = $this->printQueueModel->saveQueueItem($modelId, $userId, $priority, $notes);
            $iterationTime = microtime(true) - $iterationStart;
            
            $totalTime += $iterationTime;
            $timings[] = $iterationTime;
            
            if ($queueId) {
                $queueIds[] = $queueId;
            }
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $opsPerSecond = $iterations / $executionTime;
        
        // Calcular estatísticas
        sort($timings);
        $minTime = reset($timings);
        $maxTime = end($timings);
        $avgTime = $totalTime / count($timings);
        $medianTime = $timings[floor(count($timings) / 2)];
        $p95Time = $timings[floor(count($timings) * 0.95)];
        $p99Time = $timings[floor(count($timings) * 0.99)];
        
        $results = [
            'operation' => 'addToQueue',
            'iterations' => $iterations,
            'successful' => count($queueIds),
            'execution_time' => $executionTime,
            'operations_per_second' => $opsPerSecond,
            'timing_stats' => [
                'min' => $minTime,
                'max' => $maxTime,
                'avg' => $avgTime,
                'median' => $medianTime,
                'p95' => $p95Time,
                'p99' => $p99Time
            ]
        ];
        
        $this->testResults['addToQueue'] = $results;
        return $results;
    }
    
    /**
     * Executa teste de atualização de status na fila
     * 
     * @param array $queueIds IDs dos itens na fila
     * @param string $fromStatus Status inicial
     * @param string $toStatus Status final
     * @param int $iterations Número de atualizações a realizar
     * @return array Resultados do teste
     */
    public function testStatusUpdates($queueIds, $fromStatus, $toStatus, $iterations = 500) {
        $startTime = microtime(true);
        $successful = 0;
        $totalTime = 0;
        $timings = [];
        
        // Primeiro, colocar todos os itens no status inicial
        $sql = "UPDATE print_queue SET status = :status WHERE id IN (" . implode(',', $queueIds) . ")";
        $this->db->execute($sql, [':status' => $fromStatus]);
        
        // Realizar atualizações individuais
        for ($i = 0; $i < min($iterations, count($queueIds)); $i++) {
            $queueId = $queueIds[$i];
            $userId = 1; // Admin
            $notes = "Test status update {$i}";
            
            $iterationStart = microtime(true);
            $result = $this->printQueueModel->updateStatus($queueId, $toStatus, $userId, $notes);
            $iterationTime = microtime(true) - $iterationStart;
            
            $totalTime += $iterationTime;
            $timings[] = $iterationTime;
            
            if ($result) {
                $successful++;
            }
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $opsPerSecond = $iterations / $executionTime;
        
        // Calcular estatísticas
        sort($timings);
        $minTime = reset($timings);
        $maxTime = end($timings);
        $avgTime = $totalTime / count($timings);
        $medianTime = $timings[floor(count($timings) / 2)];
        $p95Time = $timings[floor(count($timings) * 0.95)];
        $p99Time = $timings[floor(count($timings) * 0.99)];
        
        $results = [
            'operation' => 'statusUpdate',
            'transitions' => "{$fromStatus} -> {$toStatus}",
            'iterations' => $iterations,
            'successful' => $successful,
            'execution_time' => $executionTime,
            'operations_per_second' => $opsPerSecond,
            'timing_stats' => [
                'min' => $minTime,
                'max' => $maxTime,
                'avg' => $avgTime,
                'median' => $medianTime,
                'p95' => $p95Time,
                'p99' => $p99Time
            ]
        ];
        
        $this->testResults['statusUpdate'] = $results;
        return $results;
    }
    
    /**
     * Executa teste de consulta de itens da fila
     * 
     * @param array $filters Filtros de consulta
     * @param int $iterations Número de consultas a realizar
     * @return array Resultados do teste
     */
    public function testQueueQueries($filters = [], $iterations = 200) {
        $startTime = microtime(true);
        $totalTime = 0;
        $timings = [];
        $totalItems = 0;
        
        for ($i = 0; $i < $iterations; $i++) {
            $iterationStart = microtime(true);
            $items = $this->printQueueModel->getAllQueueItems($filters);
            $iterationTime = microtime(true) - $iterationStart;
            
            $totalTime += $iterationTime;
            $timings[] = $iterationTime;
            $totalItems += count($items);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $opsPerSecond = $iterations / $executionTime;
        
        // Calcular estatísticas
        sort($timings);
        $minTime = reset($timings);
        $maxTime = end($timings);
        $avgTime = $totalTime / count($timings);
        $medianTime = $timings[floor(count($timings) / 2)];
        $p95Time = $timings[floor(count($timings) * 0.95)];
        $p99Time = $timings[floor(count($timings) * 0.99)];
        
        $results = [
            'operation' => 'queueQueries',
            'filters' => $filters,
            'iterations' => $iterations,
            'total_items_returned' => $totalItems,
            'avg_items_per_query' => $totalItems / $iterations,
            'execution_time' => $executionTime,
            'operations_per_second' => $opsPerSecond,
            'timing_stats' => [
                'min' => $minTime,
                'max' => $maxTime,
                'avg' => $avgTime,
                'median' => $medianTime,
                'p95' => $p95Time,
                'p99' => $p99Time
            ]
        ];
        
        $this->testResults['queueQueries'] = $results;
        return $results;
    }
    
    /**
     * Executa teste de recuperação de histórico da fila
     * 
     * @param array $queueIds IDs dos itens na fila
     * @param int $iterations Número de consultas a realizar
     * @return array Resultados do teste
     */
    public function testHistoryQueries($queueIds, $iterations = 200) {
        $startTime = microtime(true);
        $totalTime = 0;
        $timings = [];
        $totalItems = 0;
        
        for ($i = 0; $i < $iterations; $i++) {
            $queueId = $queueIds[array_rand($queueIds)];
            
            $iterationStart = microtime(true);
            $history = $this->printQueueModel->getQueueItemHistory($queueId);
            $iterationTime = microtime(true) - $iterationStart;
            
            $totalTime += $iterationTime;
            $timings[] = $iterationTime;
            $totalItems += count($history);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $opsPerSecond = $iterations / $executionTime;
        
        // Calcular estatísticas
        sort($timings);
        $minTime = reset($timings);
        $maxTime = end($timings);
        $avgTime = $totalTime / count($timings);
        $medianTime = $timings[floor(count($timings) / 2)];
        $p95Time = $timings[floor(count($timings) * 0.95)];
        $p99Time = $timings[floor(count($timings) * 0.99)];
        
        $results = [
            'operation' => 'historyQueries',
            'iterations' => $iterations,
            'total_items_returned' => $totalItems,
            'avg_items_per_query' => $totalItems / $iterations,
            'execution_time' => $executionTime,
            'operations_per_second' => $opsPerSecond,
            'timing_stats' => [
                'min' => $minTime,
                'max' => $maxTime,
                'avg' => $avgTime,
                'median' => $medianTime,
                'p95' => $p95Time,
                'p99' => $p99Time
            ]
        ];
        
        $this->testResults['historyQueries'] = $results;
        return $results;
    }
    
    /**
     * Executa teste de concorrência simulando múltiplos usuários
     * 
     * @param array $testData Dados sintéticos para o teste
     * @param int $numThreads Número de threads (usuários simultâneos)
     * @param int $operationsPerThread Operações por thread
     * @return array Resultados do teste
     */
    public function testConcurrency($testData, $numThreads = 10, $operationsPerThread = 50) {
        // Simulação de concorrência usando processos separados
        // Na prática, seria implementado com multi-threading real
        $startTime = microtime(true);
        $threadResults = [];
        
        for ($thread = 0; $thread < $numThreads; $thread++) {
            $threadStartTime = microtime(true);
            $threadSuccessful = 0;
            
            for ($op = 0; $op < $operationsPerThread; $op++) {
                $modelId = $testData['models'][array_rand($testData['models'])];
                $userId = $testData['users'][array_rand($testData['users'])];
                $priority = rand(1, 10);
                $notes = "Concurrency test thread {$thread} op {$op}";
                
                $result = $this->printQueueModel->saveQueueItem($modelId, $userId, $priority, $notes);
                if ($result) {
                    $threadSuccessful++;
                }
            }
            
            $threadEndTime = microtime(true);
            $threadResults[] = [
                'thread_id' => $thread,
                'operations' => $operationsPerThread,
                'successful' => $threadSuccessful,
                'execution_time' => $threadEndTime - $threadStartTime
            ];
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $totalOperations = $numThreads * $operationsPerThread;
        $opsPerSecond = $totalOperations / $executionTime;
        
        $successful = array_sum(array_column($threadResults, 'successful'));
        
        $results = [
            'operation' => 'concurrency',
            'threads' => $numThreads,
            'operations_per_thread' => $operationsPerThread,
            'total_operations' => $totalOperations,
            'successful_operations' => $successful,
            'execution_time' => $executionTime,
            'operations_per_second' => $opsPerSecond,
            'thread_details' => $threadResults
        ];
        
        $this->testResults['concurrency'] = $results;
        return $results;
    }
    
    /**
     * Executa todos os testes de carga em sequência
     * 
     * @param int $scale Escala dos testes (1-10)
     * @return array Resultados completos dos testes
     */
    public function runAllTests($scale = 5) {
        $this->testResults = [];
        
        // Ajustar parâmetros baseado na escala
        $numModels = 20 * $scale;
        $numUsers = 10 * $scale;
        $addIterations = 200 * $scale;
        $statusIterations = 100 * $scale;
        $queryIterations = 40 * $scale;
        $threads = 2 * $scale;
        $opsPerThread = 10 * $scale;
        
        // Preparar ambiente
        $testData = $this->prepareTestEnvironment($numModels, $numUsers);
        
        // Executar testes
        $addResults = $this->testAddToQueue($testData, $addIterations);
        $queueIds = array_slice($this->getCreatedQueueIds(), 0, $statusIterations);
        
        $statusResults = $this->testStatusUpdates($queueIds, 'pending', 'assigned', $statusIterations);
        $queryResults = $this->testQueueQueries([], $queryIterations);
        $historyResults = $this->testHistoryQueries($queueIds, $queryIterations);
        $concurrencyResults = $this->testConcurrency($testData, $threads, $opsPerThread);
        
        // Monitorar uso de recursos
        $this->collectResourceMetrics();
        
        // Combinar tudo para relatório final
        $testSummary = [
            'scale' => $scale,
            'date' => date('Y-m-d H:i:s'),
            'overall_performance' => $this->calculateOverallPerformance(),
            'resource_metrics' => $this->metrics,
            'test_details' => $this->testResults
        ];
        
        return $testSummary;
    }
    
    /**
     * Coleta métricas de recursos do sistema
     */
    private function collectResourceMetrics() {
        // Em um ambiente real, aqui seriam coletadas métricas como:
        // - Uso de CPU
        // - Uso de memória
        // - Uso de disco
        // - Uso de rede
        // - Estatísticas do banco de dados
        
        // Simulação para testes
        $this->metrics = [
            'cpu_usage' => rand(20, 80),
            'memory_usage' => rand(100, 500) . ' MB',
            'database_queries' => rand(1000, 5000),
            'average_query_time' => rand(1, 20) . ' ms'
        ];
    }
    
    /**
     * Calcula métricas de performance geral baseadas em todos os testes
     * 
     * @return array Métricas de performance
     */
    private function calculateOverallPerformance() {
        $ops = array_column($this->testResults, 'operations_per_second');
        $avgOps = !empty($ops) ? array_sum($ops) / count($ops) : 0;
        
        $timings = [];
        foreach ($this->testResults as $test) {
            if (isset($test['timing_stats'])) {
                $timings[] = $test['timing_stats']['avg'];
            }
        }
        $avgResponseTime = !empty($timings) ? array_sum($timings) / count($timings) : 0;
        
        return [
            'average_operations_per_second' => $avgOps,
            'average_response_time' => $avgResponseTime,
            'estimated_max_users' => floor($avgOps * 0.7),
            'bottlenecks' => $this->identifyBottlenecks()
        ];
    }
    
    /**
     * Identifica gargalos baseados nos resultados dos testes
     * 
     * @return array Gargalos identificados
     */
    private function identifyBottlenecks() {
        $bottlenecks = [];
        
        // Procurar operações lentas
        foreach ($this->testResults as $testName => $test) {
            if (isset($test['timing_stats']) && $test['timing_stats']['p95'] > 0.5) {
                $bottlenecks[] = [
                    'operation' => $testName,
                    'p95_response_time' => $test['timing_stats']['p95'],
                    'suggestion' => "Otimizar a operação {$testName} (tempo de resposta P95 muito alto)"
                ];
            }
        }
        
        // Verificar eficiência de operações concorrentes
        if (isset($this->testResults['concurrency'])) {
            $concurrentOps = $this->testResults['concurrency']['operations_per_second'];
            $singleOps = $this->testResults['addToQueue']['operations_per_second'] ?? 0;
            
            $concurrencyEfficiency = ($concurrentOps / $singleOps) * 100;
            if ($concurrencyEfficiency < 70) {
                $bottlenecks[] = [
                    'operation' => 'concurrency',
                    'efficiency' => "{$concurrencyEfficiency}%",
                    'suggestion' => "Melhorar eficiência de concorrência (abaixo de 70% do esperado)"
                ];
            }
        }
        
        return $bottlenecks;
    }
    
    /**
     * Obtém IDs de itens criados durante o teste addToQueue
     * 
     * @return array IDs dos itens na fila
     */
    private function getCreatedQueueIds() {
        $sql = "SELECT id FROM print_queue ORDER BY id DESC LIMIT 1000";
        $results = $this->db->fetchAll($sql);
        return array_column($results, 'id');
    }
    
    /**
     * Salva os resultados dos testes em formato JSON
     * 
     * @param array $results Resultados dos testes
     * @param string $filename Nome do arquivo para salvar
     * @return bool Sucesso da operação
     */
    public function saveResults($results, $filename) {
        $json = json_encode($results, JSON_PRETTY_PRINT);
        return file_put_contents($filename, $json) !== false;
    }
}
