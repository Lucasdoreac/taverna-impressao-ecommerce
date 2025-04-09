<?php
/**
 * QuotationSystemPerformanceTest - Testes de performance para o Sistema de Cotação Automatizada
 * 
 * Este script executa testes de performance abrangentes para validar o desempenho
 * do sistema de cotação automatizada sob diferentes condições de carga e com diferentes
 * tipos de modelos 3D.
 * 
 * @package App\Tests\Performance
 * @author Taverna da Impressão 3D Team
 * @version 1.0.0
 */

namespace App\Tests\Performance;

use App\Lib\Analysis\ModelComplexityAnalyzer;
use App\Lib\Analysis\QuotationCalculator;
use App\Lib\Analysis\QuotationManager;
use App\Models\ModelUploadModel;
use App\Models\UserModel;
use App\Lib\Security\SecurityManager;
use App\Lib\Database\DatabaseConnection;
use App\Lib\Performance\PerformanceMetrics;

class QuotationSystemPerformanceTest {
    /** @var PerformanceMetrics Coletor de métricas de performance */
    private $metrics;
    
    /** @var DatabaseConnection Conexão com o banco de dados */
    private $db;
    
    /** @var array Configurações de teste */
    private $config;
    
    /** @var array Resultados consolidados dos testes */
    private $results = [];
    
    /**
     * Construtor - inicializa o ambiente de testes
     * 
     * @param array $config Configurações opcionais para os testes
     */
    public function __construct(array $config = []) {
        // Inicializar coletor de métricas
        $this->metrics = new PerformanceMetrics();
        
        // Estabelecer conexão com o banco de dados
        $this->db = DatabaseConnection::getInstance();
        
        // Configurações padrão
        $this->config = array_merge([
            'iterations' => 100,
            'concurrentUsers' => 10,
            'modelSizes' => ['small', 'medium', 'large', 'complex'],
            'outputFormat' => 'json',
            'logPath' => __DIR__ . '/../../logs/performance/',
            'sampleModelsPath' => __DIR__ . '/../test_data/sample_models/',
            'timeLimit' => 300, // 5 minutos
            'memoryLimit' => 128 * 1024 * 1024, // 128MB
        ], $config);
        
        // Garantir que o diretório de logs existe
        if (!file_exists($this->config['logPath'])) {
            mkdir($this->config['logPath'], 0755, true);
        }
        
        // Registrar início dos testes
        $this->log("Iniciando testes de performance - " . date('Y-m-d H:i:s'));
    }
    
    /**
     * Executa todos os testes de performance para o sistema de cotação
     * 
     * @return array Resultados consolidados dos testes
     */
    public function runAllTests() {
        $testStartTime = microtime(true);
        
        // Fase 1: Testes de análise de complexidade
        $this->results['complexityAnalysis'] = $this->testModelComplexityAnalyzer();
        
        // Fase 2: Testes de cálculo de cotação
        $this->results['quotationCalculation'] = $this->testQuotationCalculator();
        
        // Fase 3: Testes de geração completa de cotações
        $this->results['fullQuotation'] = $this->testFullQuotationProcess();
        
        // Fase 4: Testes de concorrência
        $this->results['concurrency'] = $this->testConcurrency();
        
        // Fase 5: Testes de consumo de memória
        $this->results['memoryUsage'] = $this->testMemoryUsage();
        
        // Fase 6: Testes de interação com banco de dados
        $this->results['database'] = $this->testDatabaseInteraction();
        
        // Calcular estatísticas consolidadas
        $this->results['summary'] = $this->calculateSummaryStatistics();
        
        // Adicionar meta-informações
        $this->results['meta'] = [
            'testDuration' => microtime(true) - $testStartTime,
            'timestamp' => date('Y-m-d H:i:s'),
            'config' => $this->config,
            'phpVersion' => phpversion(),
            'serverInfo' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        ];
        
        // Salvar resultados em arquivo
        $this->saveResults();
        
        return $this->results;
    }
    
    /**
     * Testa o desempenho do analisador de complexidade de modelos
     * 
     * @return array Resultados dos testes
     */
    public function testModelComplexityAnalyzer() {
        $this->log("Iniciando testes do ModelComplexityAnalyzer");
        $results = [];
        
        $analyzer = new ModelComplexityAnalyzer();
        
        foreach ($this->config['modelSizes'] as $modelSize) {
            $modelFiles = $this->getSampleModels($modelSize);
            
            $sizeResults = [
                'averageTime' => 0,
                'maxTime' => 0,
                'minTime' => PHP_FLOAT_MAX,
                'totalMemory' => 0,
                'peakMemory' => 0,
                'samples' => count($modelFiles),
                'details' => []
            ];
            
            foreach ($modelFiles as $modelFile) {
                // Iniciar medição
                $measurementId = $this->metrics->startMeasurement('complexity_analysis', [
                    'model_size' => $modelSize,
                    'model_file' => basename($modelFile)
                ]);
                
                $memBefore = memory_get_usage(true);
                $startTime = microtime(true);
                
                // Executar análise
                $complexity = $analyzer->analyzeModel($modelFile);
                
                // Calcular métricas
                $executionTime = microtime(true) - $startTime;
                $memoryUsed = memory_get_usage(true) - $memBefore;
                
                // Registrar métricas
                $this->metrics->recordMetric('execution_time', $executionTime);
                $this->metrics->recordMetric('memory_used', $memoryUsed);
                $this->metrics->recordMetric('complexity_score', $complexity->getComplexityScore());
                
                // Finalizar medição
                $summary = $this->metrics->endMeasurement($measurementId, [
                    'vertices' => $complexity->getVertexCount(),
                    'faces' => $complexity->getFaceCount(),
                    'volume' => $complexity->getVolume(),
                    'complexity_score' => $complexity->getComplexityScore()
                ]);
                
                // Atualizar resultados agregados
                $sizeResults['averageTime'] += $executionTime;
                $sizeResults['totalMemory'] += $memoryUsed;
                $sizeResults['maxTime'] = max($sizeResults['maxTime'], $executionTime);
                $sizeResults['minTime'] = min($sizeResults['minTime'], $executionTime);
                $sizeResults['peakMemory'] = max($sizeResults['peakMemory'], $memoryUsed);
                
                // Adicionar detalhes desta execução
                $sizeResults['details'][] = [
                    'file' => basename($modelFile),
                    'executionTime' => $executionTime,
                    'memoryUsed' => $memoryUsed,
                    'vertices' => $complexity->getVertexCount(),
                    'faces' => $complexity->getFaceCount(),
                    'complexity' => $complexity->getComplexityScore()
                ];
            }
            
            // Calcular médias
            if (count($modelFiles) > 0) {
                $sizeResults['averageTime'] /= count($modelFiles);
                $sizeResults['totalMemory'] /= count($modelFiles);
            }
            
            $results[$modelSize] = $sizeResults;
        }
        
        $this->log("Testes do ModelComplexityAnalyzer concluídos");
        return $results;
    }
    
    /**
     * Testa o desempenho do calculador de cotações
     * 
     * @return array Resultados dos testes
     */
    public function testQuotationCalculator() {
        $this->log("Iniciando testes do QuotationCalculator");
        $results = [];
        
        $calculator = new QuotationCalculator();
        $analyzer = new ModelComplexityAnalyzer();
        
        foreach ($this->config['modelSizes'] as $modelSize) {
            $modelFiles = $this->getSampleModels($modelSize);
            
            $sizeResults = [
                'averageTime' => 0,
                'maxTime' => 0,
                'minTime' => PHP_FLOAT_MAX,
                'totalMemory' => 0,
                'peakMemory' => 0,
                'samples' => count($modelFiles),
                'details' => []
            ];
            
            foreach ($modelFiles as $modelFile) {
                // Analisar complexidade primeiro
                $complexity = $analyzer->analyzeModel($modelFile);
                
                // Iniciar medição
                $measurementId = $this->metrics->startMeasurement('quotation_calculation', [
                    'model_size' => $modelSize,
                    'model_file' => basename($modelFile)
                ]);
                
                $memBefore = memory_get_usage(true);
                $startTime = microtime(true);
                
                // Executar cálculo de cotação com diferentes parâmetros
                $printerParams = [
                    'material' => 'PLA',
                    'quality' => 'standard',
                    'infill' => 20,
                    'supports' => true
                ];
                
                $quotation = $calculator->calculateQuotation($complexity, $printerParams);
                
                // Calcular métricas
                $executionTime = microtime(true) - $startTime;
                $memoryUsed = memory_get_usage(true) - $memBefore;
                
                // Registrar métricas
                $this->metrics->recordMetric('execution_time', $executionTime);
                $this->metrics->recordMetric('memory_used', $memoryUsed);
                $this->metrics->recordMetric('estimated_price', $quotation->getTotal());
                
                // Finalizar medição
                $summary = $this->metrics->endMeasurement($measurementId, [
                    'material_cost' => $quotation->getMaterialCost(),
                    'time_cost' => $quotation->getTimeCost(),
                    'energy_cost' => $quotation->getEnergyCost(),
                    'overhead_cost' => $quotation->getOverheadCost(),
                    'total_price' => $quotation->getTotal()
                ]);
                
                // Atualizar resultados agregados
                $sizeResults['averageTime'] += $executionTime;
                $sizeResults['totalMemory'] += $memoryUsed;
                $sizeResults['maxTime'] = max($sizeResults['maxTime'], $executionTime);
                $sizeResults['minTime'] = min($sizeResults['minTime'], $executionTime);
                $sizeResults['peakMemory'] = max($sizeResults['peakMemory'], $memoryUsed);
                
                // Adicionar detalhes desta execução
                $sizeResults['details'][] = [
                    'file' => basename($modelFile),
                    'executionTime' => $executionTime,
                    'memoryUsed' => $memoryUsed,
                    'materialCost' => $quotation->getMaterialCost(),
                    'timeCost' => $quotation->getTimeCost(),
                    'totalPrice' => $quotation->getTotal()
                ];
            }
            
            // Calcular médias
            if (count($modelFiles) > 0) {
                $sizeResults['averageTime'] /= count($modelFiles);
                $sizeResults['totalMemory'] /= count($modelFiles);
            }
            
            $results[$modelSize] = $sizeResults;
        }
        
        $this->log("Testes do QuotationCalculator concluídos");
        return $results;
    }
    
    /**
     * Testa o processo completo de geração de cotações
     * 
     * @return array Resultados dos testes
     */
    public function testFullQuotationProcess() {
        $this->log("Iniciando testes do processo completo de cotação");
        $results = [];
        
        $quotationManager = new QuotationManager($this->db);
        
        foreach ($this->config['modelSizes'] as $modelSize) {
            $modelFiles = $this->getSampleModels($modelSize);
            
            $sizeResults = [
                'averageTime' => 0,
                'maxTime' => 0,
                'minTime' => PHP_FLOAT_MAX,
                'totalMemory' => 0,
                'peakMemory' => 0,
                'samples' => count($modelFiles),
                'details' => []
            ];
            
            foreach ($modelFiles as $modelFile) {
                // Simular um usuário
                $userId = 1000 + mt_rand(1, 100); // ID fictício para testes
                
                // Iniciar medição
                $measurementId = $this->metrics->startMeasurement('full_quotation_process', [
                    'model_size' => $modelSize,
                    'model_file' => basename($modelFile),
                    'user_id' => $userId
                ]);
                
                $memBefore = memory_get_usage(true);
                $startTime = microtime(true);
                
                // Executar processo completo de cotação
                $options = [
                    'material' => ['PLA', 'ABS', 'PETG'][mt_rand(0, 2)],
                    'quality' => ['draft', 'standard', 'high'][mt_rand(0, 2)],
                    'infill' => [10, 20, 50, 100][mt_rand(0, 3)],
                    'supports' => (bool)mt_rand(0, 1),
                    'platform_adhesion' => (bool)mt_rand(0, 1),
                    'quantity' => mt_rand(1, 5)
                ];
                
                // Executar cotação completa (análise + cálculo + persistência)
                $quotation = $quotationManager->createQuotationFromFile($modelFile, $userId, $options);
                
                // Calcular métricas
                $executionTime = microtime(true) - $startTime;
                $memoryUsed = memory_get_usage(true) - $memBefore;
                
                // Registrar métricas
                $this->metrics->recordMetric('execution_time', $executionTime);
                $this->metrics->recordMetric('memory_used', $memoryUsed);
                $this->metrics->recordMetric('estimated_print_time', $quotation->getEstimatedPrintTime());
                
                // Finalizar medição
                $summary = $this->metrics->endMeasurement($measurementId, [
                    'quotation_id' => $quotation->getId(),
                    'total_price' => $quotation->getTotal(),
                    'material' => $options['material'],
                    'quality' => $options['quality'],
                    'quantity' => $options['quantity']
                ]);
                
                // Atualizar resultados agregados
                $sizeResults['averageTime'] += $executionTime;
                $sizeResults['totalMemory'] += $memoryUsed;
                $sizeResults['maxTime'] = max($sizeResults['maxTime'], $executionTime);
                $sizeResults['minTime'] = min($sizeResults['minTime'], $executionTime);
                $sizeResults['peakMemory'] = max($sizeResults['peakMemory'], $memoryUsed);
                
                // Adicionar detalhes desta execução
                $sizeResults['details'][] = [
                    'file' => basename($modelFile),
                    'executionTime' => $executionTime,
                    'memoryUsed' => $memoryUsed,
                    'quotationId' => $quotation->getId(),
                    'totalPrice' => $quotation->getTotal(),
                    'estimatedPrintTime' => $quotation->getEstimatedPrintTime(),
                    'options' => $options
                ];
                
                // Limpar cotação do banco para não poluir
                $quotationManager->removeQuotation($quotation->getId());
            }
            
            // Calcular médias
            if (count($modelFiles) > 0) {
                $sizeResults['averageTime'] /= count($modelFiles);
                $sizeResults['totalMemory'] /= count($modelFiles);
            }
            
            $results[$modelSize] = $sizeResults;
        }
        
        $this->log("Testes do processo completo de cotação concluídos");
        return $results;
    }
    
    /**
     * Testa o desempenho sob condições de concorrência
     * 
     * @return array Resultados dos testes
     */
    public function testConcurrency() {
        $this->log("Iniciando testes de concorrência");
        
        // Simular requisições simultâneas (em PHP CLI, usamos processos paralelos)
        $concurrentUsers = $this->config['concurrentUsers'];
        $iterations = max(10, intval($this->config['iterations'] / 10)); // Reduzir para teste de concorrência
        
        $results = [
            'totalRequests' => $concurrentUsers * $iterations,
            'successfulRequests' => 0,
            'failedRequests' => 0,
            'averageResponseTime' => 0,
            'p95ResponseTime' => 0,
            'p99ResponseTime' => 0,
            'maxResponseTime' => 0,
            'minResponseTime' => PHP_FLOAT_MAX,
            'responseTimes' => []
        ];
        
        // No ambiente real, isso usaria um sistema de testes de carga como Apache JMeter
        // Aqui simulamos com execução sequencial para ambiente de desenvolvimento
        for ($user = 1; $user <= $concurrentUsers; $user++) {
            for ($i = 1; $i <= $iterations; $i++) {
                $modelSize = $this->config['modelSizes'][mt_rand(0, count($this->config['modelSizes']) - 1)];
                $modelFiles = $this->getSampleModels($modelSize);
                
                if (empty($modelFiles)) {
                    continue;
                }
                
                $modelFile = $modelFiles[mt_rand(0, count($modelFiles) - 1)];
                
                // Simular um usuário
                $userId = 1000 + $user;
                
                // Iniciar medição
                $measurementId = $this->metrics->startMeasurement('concurrency_test', [
                    'user' => $user,
                    'iteration' => $i,
                    'model_size' => $modelSize
                ]);
                
                $startTime = microtime(true);
                
                try {
                    // Criar instâncias para cada "usuário" simulado
                    $quotationManager = new QuotationManager($this->db);
                    
                    // Executar processo completo de cotação
                    $options = [
                        'material' => 'PLA',
                        'quality' => 'standard',
                        'infill' => 20,
                        'supports' => true,
                        'quantity' => 1
                    ];
                    
                    $quotation = $quotationManager->createQuotationFromFile($modelFile, $userId, $options);
                    
                    // Cálculo de tempo de resposta
                    $responseTime = microtime(true) - $startTime;
                    
                    // Registrar métricas
                    $this->metrics->recordMetric('response_time', $responseTime);
                    $this->metrics->recordMetric('successful', 1);
                    
                    // Finalizar medição
                    $this->metrics->endMeasurement($measurementId, [
                        'success' => true,
                        'response_time' => $responseTime,
                        'quotation_id' => $quotation->getId()
                    ]);
                    
                    // Atualizar estatísticas
                    $results['successfulRequests']++;
                    $results['averageResponseTime'] += $responseTime;
                    $results['maxResponseTime'] = max($results['maxResponseTime'], $responseTime);
                    $results['minResponseTime'] = min($results['minResponseTime'], $responseTime);
                    $results['responseTimes'][] = $responseTime;
                    
                    // Limpar cotação do banco para não poluir
                    $quotationManager->removeQuotation($quotation->getId());
                    
                } catch (\Exception $e) {
                    // Registrar falha
                    $this->metrics->recordMetric('failed', 1);
                    $this->metrics->endMeasurement($measurementId, [
                        'success' => false,
                        'error' => $e->getMessage()
                    ]);
                    
                    $results['failedRequests']++;
                    $this->log("Erro em teste de concorrência: " . $e->getMessage());
                }
            }
        }
        
        // Calcular médias e percentis
        if ($results['successfulRequests'] > 0) {
            $results['averageResponseTime'] /= $results['successfulRequests'];
            
            // Ordenar para cálculo de percentis
            sort($results['responseTimes']);
            $count = count($results['responseTimes']);
            
            $p95Index = (int)($count * 0.95) - 1;
            $p99Index = (int)($count * 0.99) - 1;
            
            $results['p95ResponseTime'] = $results['responseTimes'][$p95Index] ?? 0;
            $results['p99ResponseTime'] = $results['responseTimes'][$p99Index] ?? 0;
        }
        
        $this->log("Testes de concorrência concluídos");
        return $results;
    }
    
    /**
     * Testa o consumo de memória durante operações intensivas
     * 
     * @return array Resultados dos testes
     */
    public function testMemoryUsage() {
        $this->log("Iniciando testes de consumo de memória");
        
        $results = [
            'baselineMemory' => memory_get_usage(true),
            'peakMemory' => 0,
            'averageMemory' => 0,
            'memoryTimeSeries' => [],
            'leakDetection' => false,
            'operations' => []
        ];
        
        // Medição inicial
        $startMemory = memory_get_usage(true);
        $results['baselineMemory'] = $startMemory;
        
        // Sequência de operações com medição de memória
        $operations = [
            'init_manager' => 'Inicialização do QuotationManager',
            'load_models' => 'Carregamento de modelos em lote',
            'analyze_complexity' => 'Análise de complexidade em lote',
            'calculate_quotations' => 'Cálculo de cotações em lote',
            'persist_quotations' => 'Persistência de cotações em lote',
            'retrieve_quotations' => 'Recuperação de cotações em lote',
            'cleanup' => 'Limpeza de recursos'
        ];
        
        $memoryReadings = [];
        $quotationIds = [];
        
        // Inicializar componentes
        $quotationManager = new QuotationManager($this->db);
        $memoryReadings[] = [
            'operation' => 'init_manager',
            'memory' => memory_get_usage(true) - $startMemory
        ];
        
        // Carregar modelos em lote
        $modelSize = 'medium'; // Usar tamanho médio para este teste
        $modelFiles = $this->getSampleModels($modelSize);
        $memoryReadings[] = [
            'operation' => 'load_models',
            'memory' => memory_get_usage(true) - $startMemory
        ];
        
        // Analisar complexidade em lote
        $complexityAnalyzer = new ModelComplexityAnalyzer();
        $complexities = [];
        
        foreach ($modelFiles as $modelFile) {
            $complexities[$modelFile] = $complexityAnalyzer->analyzeModel($modelFile);
            
            // Registrar memória após cada análise
            $currentMemory = memory_get_usage(true) - $startMemory;
            $results['peakMemory'] = max($results['peakMemory'], $currentMemory);
            
            $memoryReadings[] = [
                'operation' => 'analyze_complexity',
                'file' => basename($modelFile),
                'memory' => $currentMemory
            ];
        }
        
        // Calcular cotações em lote
        $quotationCalculator = new QuotationCalculator();
        $quotations = [];
        
        foreach ($complexities as $modelFile => $complexity) {
            $options = [
                'material' => 'PLA',
                'quality' => 'standard',
                'infill' => 20,
                'supports' => true
            ];
            
            $quotations[$modelFile] = $quotationCalculator->calculateQuotation($complexity, $options);
            
            // Registrar memória após cada cálculo
            $currentMemory = memory_get_usage(true) - $startMemory;
            $results['peakMemory'] = max($results['peakMemory'], $currentMemory);
            
            $memoryReadings[] = [
                'operation' => 'calculate_quotations',
                'file' => basename($modelFile),
                'memory' => $currentMemory
            ];
        }
        
        // Persistir cotações em lote
        foreach ($quotations as $modelFile => $quotation) {
            $userId = 1000 + mt_rand(1, 100);
            
            $options = [
                'material' => 'PLA',
                'quality' => 'standard',
                'infill' => 20,
                'supports' => true,
                'quantity' => 1
            ];
            
            $persistedQuotation = $quotationManager->createQuotationFromExisting(
                $quotation,
                $complexity,
                $userId,
                $options
            );
            
            $quotationIds[] = $persistedQuotation->getId();
            
            // Registrar memória após cada persistência
            $currentMemory = memory_get_usage(true) - $startMemory;
            $results['peakMemory'] = max($results['peakMemory'], $currentMemory);
            
            $memoryReadings[] = [
                'operation' => 'persist_quotations',
                'file' => basename($modelFile),
                'quotation_id' => $persistedQuotation->getId(),
                'memory' => $currentMemory
            ];
        }
        
        // Recuperar cotações em lote
        foreach ($quotationIds as $quotationId) {
            $retrievedQuotation = $quotationManager->getQuotationById($quotationId);
            
            // Registrar memória após cada recuperação
            $currentMemory = memory_get_usage(true) - $startMemory;
            $results['peakMemory'] = max($results['peakMemory'], $currentMemory);
            
            $memoryReadings[] = [
                'operation' => 'retrieve_quotations',
                'quotation_id' => $quotationId,
                'memory' => $currentMemory
            ];
        }
        
        // Limpeza de recursos
        foreach ($quotationIds as $quotationId) {
            $quotationManager->removeQuotation($quotationId);
        }
        
        $complexities = null;
        $quotations = null;
        
        // Forçar GC
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        // Registrar memória final
        $finalMemory = memory_get_usage(true) - $startMemory;
        $memoryReadings[] = [
            'operation' => 'cleanup',
            'memory' => $finalMemory
        ];
        
        // Detectar possíveis vazamentos de memória
        $results['leakDetection'] = ($finalMemory > $startMemory * 1.1);
        
        // Processar resultados
        $results['memoryTimeSeries'] = $memoryReadings;
        
        // Calcular médias por operação
        $operationCounts = [];
        $operationTotals = [];
        
        foreach ($memoryReadings as $reading) {
            $op = $reading['operation'];
            if (!isset($operationCounts[$op])) {
                $operationCounts[$op] = 0;
                $operationTotals[$op] = 0;
            }
            
            $operationCounts[$op]++;
            $operationTotals[$op] += $reading['memory'];
        }
        
        foreach ($operations as $op => $description) {
            if (isset($operationCounts[$op]) && $operationCounts[$op] > 0) {
                $results['operations'][$op] = [
                    'description' => $description,
                    'count' => $operationCounts[$op],
                    'averageMemory' => $operationTotals[$op] / $operationCounts[$op],
                    'totalMemory' => $operationTotals[$op]
                ];
            }
        }
        
        // Calcular média geral
        $totalReadings = count($memoryReadings);
        $totalMemory = array_sum(array_column($memoryReadings, 'memory'));
        $results['averageMemory'] = $totalReadings > 0 ? $totalMemory / $totalReadings : 0;
        
        $this->log("Testes de consumo de memória concluídos");
        return $results;
    }
    
    /**
     * Testa a interação com o banco de dados
     * 
     * @return array Resultados dos testes
     */
    public function testDatabaseInteraction() {
        $this->log("Iniciando testes de interação com banco de dados");
        
        $results = [
            'inserts' => [
                'count' => 0,
                'averageTime' => 0,
                'maxTime' => 0,
                'minTime' => PHP_FLOAT_MAX
            ],
            'selects' => [
                'count' => 0,
                'averageTime' => 0,
                'maxTime' => 0,
                'minTime' => PHP_FLOAT_MAX
            ],
            'updates' => [
                'count' => 0,
                'averageTime' => 0,
                'maxTime' => 0,
                'minTime' => PHP_FLOAT_MAX
            ],
            'deletes' => [
                'count' => 0,
                'averageTime' => 0,
                'maxTime' => 0,
                'minTime' => PHP_FLOAT_MAX
            ],
            'quotationQueries' => []
        ];
        
        $quotationManager = new QuotationManager($this->db);
        $complexityAnalyzer = new ModelComplexityAnalyzer();
        $quotationCalculator = new QuotationCalculator();
        
        // Preparar modelos de teste
        $modelFiles = $this->getSampleModels('medium', 5); // Limitar a 5 para teste de BD
        $quotationIds = [];
        
        // 1. Testes de INSERT
        foreach ($modelFiles as $modelFile) {
            $complexity = $complexityAnalyzer->analyzeModel($modelFile);
            
            $options = [
                'material' => 'PLA',
                'quality' => 'standard',
                'infill' => 20,
                'supports' => true,
                'quantity' => 1
            ];
            
            $userId = 1000 + mt_rand(1, 100);
            
            // Medir tempo de inserção
            $startTime = microtime(true);
            
            // Usar método que realiza operação de BD
            $quotation = $quotationManager->createQuotationFromFile($modelFile, $userId, $options);
            
            $executionTime = microtime(true) - $startTime;
            $quotationIds[] = $quotation->getId();
            
            // Atualizar estatísticas
            $results['inserts']['count']++;
            $results['inserts']['averageTime'] += $executionTime;
            $results['inserts']['maxTime'] = max($results['inserts']['maxTime'], $executionTime);
            $results['inserts']['minTime'] = min($results['inserts']['minTime'], $executionTime);
            
            $this->metrics->recordMetric('db_insert_time', $executionTime);
        }
        
        // Calcular média para inserts
        if ($results['inserts']['count'] > 0) {
            $results['inserts']['averageTime'] /= $results['inserts']['count'];
        }
        
        // 2. Testes de SELECT
        foreach ($quotationIds as $quotationId) {
            // Medir tempo de seleção
            $startTime = microtime(true);
            
            $quotation = $quotationManager->getQuotationById($quotationId);
            
            $executionTime = microtime(true) - $startTime;
            
            // Atualizar estatísticas
            $results['selects']['count']++;
            $results['selects']['averageTime'] += $executionTime;
            $results['selects']['maxTime'] = max($results['selects']['maxTime'], $executionTime);
            $results['selects']['minTime'] = min($results['selects']['minTime'], $executionTime);
            
            $this->metrics->recordMetric('db_select_time', $executionTime);
        }
        
        // Testar query de listagem
        $startTime = microtime(true);
        $userQuotations = $quotationManager->getUserQuotations(1000 + mt_rand(1, 100));
        $executionTime = microtime(true) - $startTime;
        
        $results['quotationQueries']['list_user_quotations'] = [
            'time' => $executionTime,
            'count' => count($userQuotations)
        ];
        
        // Calcular média para selects
        if ($results['selects']['count'] > 0) {
            $results['selects']['averageTime'] /= $results['selects']['count'];
        }
        
        // 3. Testes de UPDATE
        foreach ($quotationIds as $quotationId) {
            // Medir tempo de atualização
            $startTime = microtime(true);
            
            $quotation = $quotationManager->getQuotationById($quotationId);
            $quotation->setStatus('approved');
            $quotationManager->updateQuotation($quotation);
            
            $executionTime = microtime(true) - $startTime;
            
            // Atualizar estatísticas
            $results['updates']['count']++;
            $results['updates']['averageTime'] += $executionTime;
            $results['updates']['maxTime'] = max($results['updates']['maxTime'], $executionTime);
            $results['updates']['minTime'] = min($results['updates']['minTime'], $executionTime);
            
            $this->metrics->recordMetric('db_update_time', $executionTime);
        }
        
        // Calcular média para updates
        if ($results['updates']['count'] > 0) {
            $results['updates']['averageTime'] /= $results['updates']['count'];
        }
        
        // 4. Testes de DELETE
        foreach ($quotationIds as $quotationId) {
            // Medir tempo de exclusão
            $startTime = microtime(true);
            
            $quotationManager->removeQuotation($quotationId);
            
            $executionTime = microtime(true) - $startTime;
            
            // Atualizar estatísticas
            $results['deletes']['count']++;
            $results['deletes']['averageTime'] += $executionTime;
            $results['deletes']['maxTime'] = max($results['deletes']['maxTime'], $executionTime);
            $results['deletes']['minTime'] = min($results['deletes']['minTime'], $executionTime);
            
            $this->metrics->recordMetric('db_delete_time', $executionTime);
        }
        
        // Calcular média para deletes
        if ($results['deletes']['count'] > 0) {
            $results['deletes']['averageTime'] /= $results['deletes']['count'];
        }
        
        // 5. Testes de consultas específicas para cotações
        $startTime = microtime(true);
        $pendingQuotations = $quotationManager->getQuotationsByStatus('pending');
        $executionTime = microtime(true) - $startTime;
        
        $results['quotationQueries']['pending_quotations'] = [
            'time' => $executionTime,
            'count' => count($pendingQuotations)
        ];
        
        $startTime = microtime(true);
        $quotationStats = $quotationManager->getQuotationStatistics();
        $executionTime = microtime(true) - $startTime;
        
        $results['quotationQueries']['quotation_statistics'] = [
            'time' => $executionTime,
            'data' => $quotationStats
        ];
        
        $this->log("Testes de interação com banco de dados concluídos");
        return $results;
    }
    
    /**
     * Calcula estatísticas consolidadas de todos os testes
     * 
     * @return array Estatísticas consolidadas
     */
    protected function calculateSummaryStatistics() {
        $summary = [
            'averageExecutionTimes' => [],
            'memoryUsage' => [
                'average' => 0,
                'peak' => 0
            ],
            'databasePerformance' => [
                'averageQueryTime' => 0
            ],
            'bottlenecks' => [],
            'recommendations' => []
        ];
        
        // Consolidar tempos de execução médios por componente
        if (isset($this->results['complexityAnalysis'])) {
            $avgTimes = [];
            foreach ($this->results['complexityAnalysis'] as $modelSize => $results) {
                $avgTimes[$modelSize] = $results['averageTime'];
            }
            $summary['averageExecutionTimes']['complexityAnalysis'] = $avgTimes;
        }
        
        if (isset($this->results['quotationCalculation'])) {
            $avgTimes = [];
            foreach ($this->results['quotationCalculation'] as $modelSize => $results) {
                $avgTimes[$modelSize] = $results['averageTime'];
            }
            $summary['averageExecutionTimes']['quotationCalculation'] = $avgTimes;
        }
        
        if (isset($this->results['fullQuotation'])) {
            $avgTimes = [];
            foreach ($this->results['fullQuotation'] as $modelSize => $results) {
                $avgTimes[$modelSize] = $results['averageTime'];
            }
            $summary['averageExecutionTimes']['fullQuotation'] = $avgTimes;
        }
        
        // Consolidar uso de memória
        if (isset($this->results['memoryUsage'])) {
            $summary['memoryUsage']['average'] = $this->results['memoryUsage']['averageMemory'];
            $summary['memoryUsage']['peak'] = $this->results['memoryUsage']['peakMemory'];
            $summary['memoryUsage']['leakDetected'] = $this->results['memoryUsage']['leakDetection'];
        }
        
        // Consolidar desempenho de banco de dados
        if (isset($this->results['database'])) {
            $totalTime = 0;
            $totalQueries = 0;
            
            foreach (['inserts', 'selects', 'updates', 'deletes'] as $operation) {
                if ($this->results['database'][$operation]['count'] > 0) {
                    $totalTime += $this->results['database'][$operation]['averageTime'] * 
                                  $this->results['database'][$operation]['count'];
                    $totalQueries += $this->results['database'][$operation]['count'];
                }
            }
            
            $summary['databasePerformance']['averageQueryTime'] = $totalQueries > 0 ? 
                                                                $totalTime / $totalQueries : 0;
        }
        
        // Identificar gargalos potenciais
        $bottlenecks = [];
        
        // Verificar tempos de análise de complexidade para modelos grandes
        if (isset($this->results['complexityAnalysis']['large'])) {
            $largeModelTime = $this->results['complexityAnalysis']['large']['averageTime'];
            if ($largeModelTime > 2.0) { // limiar de 2 segundos
                $bottlenecks[] = [
                    'component' => 'ModelComplexityAnalyzer',
                    'issue' => 'Tempo elevado para análise de modelos grandes',
                    'metric' => $largeModelTime,
                    'threshold' => 2.0,
                    'impact' => 'Alto',
                    'recommendation' => 'Otimizar algorítimo de análise ou implementar cache de resultados'
                ];
            }
        }
        
        // Verificar tempo de resposta p95 em testes de concorrência
        if (isset($this->results['concurrency']['p95ResponseTime'])) {
            $p95Time = $this->results['concurrency']['p95ResponseTime'];
            if ($p95Time > 5.0) { // limiar de 5 segundos
                $bottlenecks[] = [
                    'component' => 'QuotationManager',
                    'issue' => 'Tempo de resposta P95 elevado em concorrência',
                    'metric' => $p95Time,
                    'threshold' => 5.0,
                    'impact' => 'Alto',
                    'recommendation' => 'Implementar cache de modelos frequentes e otimizar consultas de banco'
                ];
            }
        }
        
        // Verificar consumo de memória
        if (isset($this->results['memoryUsage']['peakMemory'])) {
            $peakMemory = $this->results['memoryUsage']['peakMemory'] / (1024 * 1024); // MB
            if ($peakMemory > 64) { // limiar de 64MB
                $bottlenecks[] = [
                    'component' => 'Sistema de Cotação',
                    'issue' => 'Consumo elevado de memória',
                    'metric' => $peakMemory . ' MB',
                    'threshold' => '64 MB',
                    'impact' => 'Médio',
                    'recommendation' => 'Revisar alocação de memória, especialmente ao processar modelos complexos'
                ];
            }
        }
        
        // Verificar vazamento de memória
        if (isset($this->results['memoryUsage']['leakDetection']) && 
            $this->results['memoryUsage']['leakDetection']) {
            $bottlenecks[] = [
                'component' => 'Sistema de Cotação',
                'issue' => 'Possível vazamento de memória detectado',
                'metric' => 'Uso final > 110% do uso inicial',
                'impact' => 'Alto',
                'recommendation' => 'Verificar liberação de recursos em loops e revisar ciclo de vida de objetos'
            ];
        }
        
        $summary['bottlenecks'] = $bottlenecks;
        
        // Gerar recomendações
        $recommendations = [];
        
        // Recomendações baseadas em gargalos
        foreach ($bottlenecks as $bottleneck) {
            $recommendations[] = $bottleneck['recommendation'];
        }
        
        // Recomendações gerais
        $recommendations = array_merge($recommendations, [
            'Implementar sistema de cache para cotações de modelos frequentes',
            'Considerar processamento assíncrono para modelos complexos',
            'Otimizar queries de banco de dados com índices apropriados',
            'Implementar mecanismo de enfileiramento para alta concorrência'
        ]);
        
        $summary['recommendations'] = array_unique($recommendations);
        
        return $summary;
    }
    
    /**
     * Obtém arquivos de amostra de um determinado tamanho para testes
     * 
     * @param string $size Tamanho dos modelos (small, medium, large, complex)
     * @param int $limit Limite de arquivos a retornar
     * @return array Array de caminhos para arquivos de modelo
     */
    protected function getSampleModels($size, $limit = 0) {
        $basePath = $this->config['sampleModelsPath'] . '/' . $size;
        
        // Em ambiente de produção, verificaria se o diretório existe
        // e usaria glob ou scandir para obter arquivos reais
        
        // Para simulação, criamos caminhos fictícios
        $extensions = ['stl', 'obj', '3mf'];
        $modelCount = [
            'small' => 10,
            'medium' => 8,
            'large' => 5,
            'complex' => 3
        ];
        
        $count = $modelCount[$size] ?? 5;
        if ($limit > 0 && $limit < $count) {
            $count = $limit;
        }
        
        $models = [];
        for ($i = 1; $i <= $count; $i++) {
            $ext = $extensions[mt_rand(0, count($extensions) - 1)];
            $models[] = $basePath . '/model_' . $size . '_' . $i . '.' . $ext;
        }
        
        return $models;
    }
    
    /**
     * Salva os resultados dos testes em um arquivo
     * 
     * @return void
     */
    protected function saveResults() {
        $timestamp = date('Y-m-d_H-i-s');
        $outputFormat = $this->config['outputFormat'];
        
        $filename = $this->config['logPath'] . '/quotation_perf_' . $timestamp;
        
        if ($outputFormat === 'json') {
            $filename .= '.json';
            file_put_contents($filename, json_encode($this->results, JSON_PRETTY_PRINT));
        } else {
            $filename .= '.txt';
            $output = "Resultados dos Testes de Performance - Sistema de Cotação\n";
            $output .= "Data: " . date('Y-m-d H:i:s') . "\n\n";
            
            // Resumo geral
            $output .= "=== RESUMO GERAL ===\n";
            if (isset($this->results['summary'])) {
                $output .= "Gargalos Identificados: " . count($this->results['summary']['bottlenecks']) . "\n";
                $output .= "Recomendações: " . count($this->results['summary']['recommendations']) . "\n\n";
                
                // Detalhar recomendações
                $output .= "Recomendações:\n";
                foreach ($this->results['summary']['recommendations'] as $i => $rec) {
                    $output .= ($i + 1) . ". " . $rec . "\n";
                }
                $output .= "\n";
            }
            
            file_put_contents($filename, $output);
        }
        
        $this->log("Resultados salvos em: " . $filename);
    }
    
    /**
     * Registra mensagem no log
     * 
     * @param string $message Mensagem a ser registrada
     * @param string $level Nível do log (info, warning, error)
     * @return void
     */
    protected function log($message, $level = 'info') {
        $timestamp = date('Y-m-d H:i:s');
        $logLine = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        $logFile = $this->config['logPath'] . '/performance_test.log';
        file_put_contents($logFile, $logLine, FILE_APPEND);
        
        // Echo para saída padrão durante execução
        echo $logLine;
    }
}

// Função de execução direta via CLI
if (php_sapi_name() === 'cli' && isset($argv[0]) && basename($argv[0]) === basename(__FILE__)) {
    // Carregar configurações
    $config = [];
    
    // Verificar argumentos de linha de comando
    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];
        if (strpos($arg, '--') === 0) {
            $argName = substr($arg, 2);
            $argValue = null;
            
            if (strpos($argName, '=') !== false) {
                list($argName, $argValue) = explode('=', $argName, 2);
            } elseif ($i + 1 < $argc && strpos($argv[$i + 1], '--') !== 0) {
                $argValue = $argv[$i + 1];
                $i++;
            } else {
                $argValue = true; // flag sem valor
            }
            
            // Interpretar valores específicos
            if ($argValue === 'true') $argValue = true;
            if ($argValue === 'false') $argValue = false;
            if (is_numeric($argValue)) {
                $argValue = strpos($argValue, '.') !== false ? 
                            (float)$argValue : (int)$argValue;
            }
            
            $config[$argName] = $argValue;
        }
    }
    
    // Iniciar os testes
    $tester = new QuotationSystemPerformanceTest($config);
    $results = $tester->runAllTests();
    
    // Exibir resumo
    if (isset($results['summary'])) {
        echo "\n=== RESUMO DOS TESTES ===\n";
        
        if (!empty($results['summary']['bottlenecks'])) {
            echo "\nGargalos Identificados:\n";
            foreach ($results['summary']['bottlenecks'] as $i => $bottleneck) {
                echo ($i + 1) . ". {$bottleneck['component']}: {$bottleneck['issue']} ({$bottleneck['impact']})\n";
            }
        } else {
            echo "\nNenhum gargalo crítico identificado.\n";
        }
        
        if (!empty($results['summary']['recommendations'])) {
            echo "\nRecomendações:\n";
            foreach ($results['summary']['recommendations'] as $i => $rec) {
                echo ($i + 1) . ". {$rec}\n";
            }
        }
    }
}
