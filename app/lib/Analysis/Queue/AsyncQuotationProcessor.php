<?php
/**
 * AsyncQuotationProcessor
 * 
 * Processador assíncrono para cotações de modelos 3D complexos. Este componente
 * é responsável por processar tarefas da fila de cotação em background, utilizando
 * o ModelComplexityAnalyzer e QuotationCalculator otimizados.
 * 
 * Implementa mecanismos de controle de falhas, registro de progresso e notificação
 * para fornecer uma experiência de usuário responsiva mesmo para modelos muito complexos.
 * 
 * @package App\Lib\Analysis\Queue
 * @version 1.0.0
 * @author Taverna da Impressão 3D
 */

require_once __DIR__ . '/../Optimizer/ModelAnalysisOptimizer.php';
require_once __DIR__ . '/../ModelComplexityAnalyzer.php';
require_once __DIR__ . '/../QuotationCalculator.php';
require_once __DIR__ . '/../QuotationCache.php';
require_once __DIR__ . '/QuotationQueue.php';
require_once __DIR__ . '/QuotationNotifier.php';

class AsyncQuotationProcessor {
    /**
     * ID do worker atual
     * 
     * @var string
     */
    private $workerId;
    
    /**
     * Instância da fila
     * 
     * @var QuotationQueue
     */
    private $queue;
    
    /**
     * Instância do analisador de modelos
     * 
     * @var ModelComplexityAnalyzer
     */
    private $analyzer;
    
    /**
     * Instância do otimizador de análise
     * 
     * @var ModelAnalysisOptimizer
     */
    private $optimizer;
    
    /**
     * Instância do calculador de cotações
     * 
     * @var QuotationCalculator
     */
    private $calculator;
    
    /**
     * Instância do cache de cotações
     * 
     * @var QuotationCache
     */
    private $cache;
    
    /**
     * Instância do notificador
     * 
     * @var QuotationNotifier
     */
    private $notifier;
    
    /**
     * Configuração do processador
     * 
     * @var array
     */
    private $config;
    
    /**
     * Tarefa atual em processamento
     * 
     * @var array|null
     */
    private $currentTask = null;
    
    /**
     * Flag para processamento contínuo
     * 
     * @var bool
     */
    private $keepProcessing = true;
    
    /**
     * Flag para modo de depuração
     * 
     * @var bool
     */
    private $debugMode = false;
    
    /**
     * Construtor
     * 
     * @param array $config Configuração do processador
     */
    public function __construct(array $config = []) {
        // Configurar worker ID único
        $this->workerId = 'worker-' . gethex(microtime(true)) . '-' . substr(md5(random_bytes(8)), 0, 8);
        
        // Instanciar componentes
        $this->queue = new QuotationQueue();
        $this->analyzer = new ModelComplexityAnalyzer();
        $this->optimizer = new ModelAnalysisOptimizer($this->analyzer);
        $this->calculator = new QuotationCalculator();
        $this->cache = QuotationCache::getInstance();
        $this->notifier = new QuotationNotifier();
        
        // Configuração padrão
        $defaultConfig = [
            'max_processing_time' => 1800,     // 30 minutos
            'progress_update_interval' => 10,  // A cada 10% de progresso
            'check_interval' => 5,             // 5 segundos entre iterações
            'memory_limit' => '256M',          // Limite de memória
            'use_cache' => true,               // Usar cache de cotações
            'debug_mode' => false              // Modo de depuração
        ];
        
        // Aplicar configuração personalizada
        $this->config = array_merge($defaultConfig, $config);
        
        // Aplicar modo de depuração
        $this->debugMode = (bool)$this->config['debug_mode'];
        
        // Configurar limites de recursos
        ini_set('memory_limit', $this->config['memory_limit']);
        ini_set('max_execution_time', $this->config['max_processing_time']);
    }
    
    /**
     * Executa o processador de forma contínua
     * 
     * @param int $maxIterations Número máximo de iterações (null para infinito)
     * @return int Número de tarefas processadas
     */
    public function run(int $maxIterations = null): int {
        $this->log("Iniciando processador assíncrono (Worker ID: {$this->workerId})");
        
        $iterations = 0;
        $tasksProcessed = 0;
        
        // Loop principal
        while ($this->keepProcessing && ($maxIterations === null || $iterations < $maxIterations)) {
            $iterations++;
            
            try {
                // Obter próxima tarefa
                $task = $this->queue->getNextTask($this->workerId);
                
                if ($task) {
                    $this->currentTask = $task;
                    $this->log("Processando tarefa: {$task['task_id']}");
                    
                    // Processar tarefa
                    $processed = $this->processTask($task);
                    
                    if ($processed) {
                        $tasksProcessed++;
                        $this->log("Tarefa concluída: {$task['task_id']}");
                    } else {
                        $this->log("Erro ao processar tarefa: {$task['task_id']}", true);
                    }
                    
                    $this->currentTask = null;
                } else {
                    // Nenhuma tarefa disponível
                    $this->log("Nenhuma tarefa disponível. Aguardando...");
                    sleep($this->config['check_interval']);
                }
            } catch (Exception $e) {
                $this->log("Erro no loop principal: " . $e->getMessage(), true);
                
                // Se houver uma tarefa em processamento, marcá-la como falha
                if ($this->currentTask) {
                    $this->failTask($this->currentTask['task_id'], "Erro interno: " . $e->getMessage());
                    $this->currentTask = null;
                }
                
                // Aguardar antes de tentar novamente
                sleep($this->config['check_interval']);
            }
        }
        
        $this->log("Finalizando processador após {$iterations} iterações. Tarefas processadas: {$tasksProcessed}");
        
        return $tasksProcessed;
    }
    
    /**
     * Processa uma tarefa da fila
     * 
     * @param array $task Dados da tarefa
     * @return bool Sucesso da operação
     */
    public function processTask(array $task): bool {
        try {
            // Reportar progresso inicial
            $this->updateProgress($task['task_id'], 5, "Iniciando análise do modelo");
            
            // Verificar cache primeiro se configurado para usar
            if ($this->config['use_cache']) {
                $cachedResult = $this->checkCache($task);
                if ($cachedResult) {
                    // Usar resultado em cache
                    $this->updateProgress($task['task_id'], 95, "Usando resultado em cache");
                    
                    // Atualizar status para concluído
                    $this->completeTask($task['task_id'], $cachedResult);
                    
                    // Notificar resultado
                    $this->sendNotification($task, $cachedResult);
                    
                    return true;
                }
            }
            
            // Obter dados do modelo
            $modelData = $this->getModelData($task);
            
            // Atualizar progresso
            $this->updateProgress($task['task_id'], 10, "Preparando análise de complexidade");
            
            // Realizar análise de complexidade
            $analysisResult = $this->analyzeModelComplexity($modelData);
            
            // Atualizar progresso
            $this->updateProgress($task['task_id'], 60, "Análise completa. Calculando cotação");
            
            // Calcular cotação completa
            $quotationResult = $this->calculateQuotation($analysisResult, $task['parameters']);
            
            // Atualizar progresso
            $this->updateProgress($task['task_id'], 80, "Cotação calculada. Finalizando");
            
            // Adicionar informações da tarefa ao resultado
            $quotationResult['task_id'] = $task['task_id'];
            $quotationResult['model_id'] = $task['model_id'];
            $quotationResult['source'] = 'async_processor';
            $quotationResult['processed_at'] = date('Y-m-d H:i:s');
            
            // Armazenar no cache se configurado para usar
            if ($this->config['use_cache']) {
                $this->saveToCache($task, $quotationResult);
            }
            
            // Atualizar status para concluído
            $this->completeTask($task['task_id'], $quotationResult);
            
            // Atualizar progresso final
            $this->updateProgress($task['task_id'], 100, "Cotação concluída");
            
            // Notificar resultado
            $this->sendNotification($task, $quotationResult);
            
            return true;
        } catch (Exception $e) {
            // Registrar erro
            $this->log("Erro ao processar tarefa {$task['task_id']}: " . $e->getMessage(), true);
            
            // Marcar tarefa como falha
            $this->failTask($task['task_id'], "Erro durante processamento: " . $e->getMessage());
            
            // Notificar falha
            $this->sendErrorNotification($task, $e->getMessage());
            
            return false;
        }
    }
    
    /**
     * Verifica se há resultado em cache para a tarefa
     * 
     * @param array $task Dados da tarefa
     * @return array|null Resultado em cache ou null se não encontrado
     */
    private function checkCache(array $task): ?array {
        if (!$this->config['use_cache']) {
            return null;
        }
        
        try {
            // Gerar chave de cache para a tarefa
            $cacheKey = $this->generateCacheKey($task);
            
            // Verificar no cache
            $cachedResult = $this->cache->get($cacheKey);
            
            if ($cachedResult) {
                $this->log("Cache hit para tarefa {$task['task_id']}");
                return $cachedResult;
            }
            
            $this->log("Cache miss para tarefa {$task['task_id']}");
            return null;
        } catch (Exception $e) {
            $this->log("Erro ao verificar cache: " . $e->getMessage(), true);
            return null;
        }
    }
    
    /**
     * Salva o resultado no cache
     * 
     * @param array $task Dados da tarefa
     * @param array $result Resultado a salvar
     * @return bool Sucesso da operação
     */
    private function saveToCache(array $task, array $result): bool {
        if (!$this->config['use_cache']) {
            return false;
        }
        
        try {
            // Gerar chave de cache para a tarefa
            $cacheKey = $this->generateCacheKey($task);
            
            // Calcular TTL adaptativo com base na complexidade
            $complexityScore = $result['complexity_score'] ?? 50;
            $ttl = $this->cache->calculateAdaptiveTtl($cacheKey, $complexityScore);
            
            // Salvar no cache
            $success = $this->cache->set($cacheKey, $result, $ttl);
            
            if ($success) {
                $this->log("Resultado salvo no cache para tarefa {$task['task_id']}, TTL: {$ttl}s");
            } else {
                $this->log("Falha ao salvar no cache para tarefa {$task['task_id']}", true);
            }
            
            return $success;
        } catch (Exception $e) {
            $this->log("Erro ao salvar no cache: " . $e->getMessage(), true);
            return false;
        }
    }
    
    /**
     * Gera uma chave de cache para a tarefa
     * 
     * @param array $task Dados da tarefa
     * @return string Chave de cache
     */
    private function generateCacheKey(array $task): string {
        $cacheParams = [
            'model_id' => $task['model_id'] ?? null,
            'file_hash' => $task['file_hash'] ?? null
        ];
        
        // Adicionar parâmetros relevantes
        if (isset($task['parameters']) && is_array($task['parameters'])) {
            // Incluir apenas parâmetros que afetam o resultado
            foreach (['material', 'quality', 'scale'] as $param) {
                if (isset($task['parameters'][$param])) {
                    $cacheParams[$param] = $task['parameters'][$param];
                }
            }
            
            // Incluir configurações personalizadas se existirem
            if (isset($task['parameters']['custom_settings']) && is_array($task['parameters']['custom_settings'])) {
                $cacheParams['custom_settings'] = $task['parameters']['custom_settings'];
            }
        }
        
        return $this->cache->generateKey($cacheParams);
    }
    
    /**
     * Obtém dados do modelo a partir da tarefa
     * 
     * @param array $task Dados da tarefa
     * @return array Dados do modelo
     * @throws Exception Se não for possível obter dados do modelo
     */
    private function getModelData(array $task): array {
        // Inicializar array de dados do modelo
        $modelData = [
            'task_id' => $task['task_id'],
            'file_type' => null,
            'file_path' => null,
            'metadata' => []
        ];
        
        // Se temos model_id, carregar do banco de dados
        if (!empty($task['model_id'])) {
            $db = Database::getInstance();
            $sql = "SELECT * FROM customer_models WHERE id = :id";
            $model = $db->fetchSingle($sql, [':id' => $task['model_id']]);
            
            if (!$model) {
                throw new Exception('Modelo não encontrado');
            }
            
            if ($model['status'] !== 'approved') {
                throw new Exception('Apenas modelos aprovados podem ser cotados');
            }
            
            // Preparar caminho do arquivo
            $approvedDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/models/approved/';
            $modelData['file_path'] = $approvedDir . $model['file_name'];
            $modelData['file_type'] = pathinfo($model['file_name'], PATHINFO_EXTENSION);
            
            // Carregar metadados se disponíveis
            if (!empty($model['metadata'])) {
                $modelData['metadata'] = json_decode($model['metadata'], true) ?: [];
            }
            
            // Adicionar informações do modelo
            $modelData['original_name'] = $model['original_name'];
            $modelData['user_id'] = $model['user_id'];
            $modelData['created_at'] = $model['created_at'];
        } 
        // Se temos file_path, usar diretamente
        elseif (!empty($task['file_path'])) {
            // Verificar se o arquivo existe
            if (!file_exists($task['file_path'])) {
                throw new Exception('Arquivo não encontrado: ' . $task['file_path']);
            }
            
            $modelData['file_path'] = $task['file_path'];
            $modelData['file_type'] = pathinfo($task['file_path'], PATHINFO_EXTENSION);
            $modelData['original_name'] = basename($task['file_path']);
        } else {
            throw new Exception('Dados de modelo insuficientes na tarefa');
        }
        
        // Verificar se o arquivo existe
        if (!file_exists($modelData['file_path'])) {
            throw new Exception('Arquivo do modelo não encontrado: ' . $modelData['file_path']);
        }
        
        // Verificar extensão de arquivo
        $allowedExtensions = ['stl', 'obj', '3mf', 'gcode'];
        $extension = strtolower($modelData['file_type']);
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('Tipo de arquivo não suportado: ' . $extension);
        }
        
        return $modelData;
    }
    
    /**
     * Analisa a complexidade do modelo
     * 
     * @param array $modelData Dados do modelo
     * @return array Resultado da análise
     * @throws Exception Se ocorrer um erro durante a análise
     */
    private function analyzeModelComplexity(array $modelData): array {
        try {
            // Iniciar análise otimizada para modelos grandes
            $this->log("Iniciando análise de complexidade para {$modelData['file_path']}");
            
            // Obter tamanho do arquivo
            $fileSize = filesize($modelData['file_path']);
            $this->log("Tamanho do arquivo: " . $this->formatSize($fileSize));
            
            // Usar otimizador para modelos grandes
            $result = $this->optimizer->analyzeModelOptimized($modelData, true);
            
            // Obter métricas de performance
            $performanceMetrics = $this->optimizer->getPerformanceMetrics();
            
            // Registrar métricas
            $this->log("Análise concluída em " . round($performanceMetrics['duration_seconds'], 2) . " segundos");
            $this->log("Memória utilizada: " . $this->formatSize($performanceMetrics['memory_peak']));
            $this->log("Triângulos processados: " . number_format($performanceMetrics['processed_triangles']));
            
            if ($performanceMetrics['early_stopped']) {
                $this->log("Early-stopping ativado, completado " . 
                    number_format($performanceMetrics['completion_percentage'], 1) . "% da análise");
            }
            
            return $result;
        } catch (Exception $e) {
            $this->log("Erro durante análise de complexidade: " . $e->getMessage(), true);
            
            // Tentar fallback para estimativa usando metadados
            if (!empty($modelData['metadata'])) {
                $this->log("Usando fallback com metadados para estimativa");
                return $this->generateEstimatedResults($modelData);
            }
            
            // Propagar exceção se não for possível estimar
            throw $e;
        }
    }
    
    /**
     * Calcula a cotação baseada na análise de complexidade
     * 
     * @param array $analysisResult Resultado da análise
     * @param array $parameters Parâmetros de cotação
     * @return array Cotação calculada
     */
    private function calculateQuotation(array $analysisResult, array $parameters): array {
        try {
            // Material padrão se não especificado
            $material = $parameters['material'] ?? 'pla';
            
            // Opções adicionais
            $options = $parameters['options'] ?? [];
            
            // Se temos configurações personalizadas
            if (isset($parameters['custom_settings']) && is_array($parameters['custom_settings'])) {
                $options = array_merge($options, $parameters['custom_settings']);
            }
            
            // Calcular cotação usando o calculador
            $quotation = $this->calculator->calculateQuotation($analysisResult, $material, $options);
            
            // Adicionar a análise de complexidade ao resultado
            $quotation['complexity_analysis'] = $analysisResult;
            
            // Adicionar flag de sucesso
            $quotation['success'] = true;
            
            return $quotation;
        } catch (Exception $e) {
            $this->log("Erro ao calcular cotação: " . $e->getMessage(), true);
            
            // Gerar cotação estimada em caso de erro
            $quotation = [
                'success' => true,
                'is_estimated' => true,
                'estimation_reason' => 'Erro durante cálculo detalhado: ' . $e->getMessage(),
                'material' => $parameters['material'] ?? 'pla',
                'complexity_score' => $analysisResult['complexity_score'] ?? 50,
                'estimated_print_time_minutes' => $analysisResult['estimated_print_time_minutes'] ?? 120,
                'material_cost' => $analysisResult['material_cost'] ?? 15.0,
                'printing_cost' => $analysisResult['printing_cost'] ?? 30.0,
                'total_cost' => $analysisResult['total_cost'] ?? 45.0
            ];
            
            return $quotation;
        }
    }
    
    /**
     * Gera resultados estimados com base em metadados limitados
     * 
     * @param array $modelData Dados do modelo
     * @return array Resultados estimados
     */
    private function generateEstimatedResults(array $modelData): array {
        $fileSize = filesize($modelData['file_path']);
        
        // Métricas estimadas
        $metrics = [
            'polygon_count' => (int)($fileSize / 50),
            'volume' => 0,
            'surface_area' => 0,
            'hollow_spaces' => 0.3,
            'overhangs' => 0.5,
            'thin_walls' => 0.5,
            'dimensions' => [
                'width' => 0,
                'height' => 0,
                'depth' => 0,
            ],
            'bounding_box_volume' => 0
        ];
        
        // Adicionar metadados se disponíveis
        if (!empty($modelData['metadata'])) {
            foreach ($metrics as $key => $value) {
                if (isset($modelData['metadata'][$key])) {
                    $metrics[$key] = $modelData['metadata'][$key];
                }
            }
        }
        
        // Estimar dimensões com base na contagem de polígonos
        $estimatedRadius = pow($metrics['polygon_count'] / 1000, 1/3) * 10;
        $metrics['dimensions']['width'] = $estimatedRadius * 2;
        $metrics['dimensions']['height'] = $estimatedRadius * 2;
        $metrics['dimensions']['depth'] = $estimatedRadius * 2;
        
        // Estimar volume
        $metrics['bounding_box_volume'] = 
            $metrics['dimensions']['width'] * 
            $metrics['dimensions']['height'] * 
            $metrics['dimensions']['depth'];
        
        $metrics['volume'] = $metrics['bounding_box_volume'] * 0.7;
        
        // Estimar área de superfície
        $metrics['surface_area'] = 4 * M_PI * pow($estimatedRadius, 2);
        
        // Calcular pontuação de complexidade
        $complexityScore = min(100, max(0, 20 + $metrics['polygon_count'] / 5000));
        
        // Estimar tempo de impressão
        $printTime = max(30, $metrics['volume'] * 0.2);
        
        // Estimar custos
        $materialCost = max(5, $metrics['volume'] * 0.05);
        $printingCost = max(10, $printTime / 60 * 15);
        
        return [
            'complexity_score' => $complexityScore,
            'metrics' => $metrics,
            'estimated_print_time_minutes' => $printTime,
            'material_cost' => $materialCost,
            'printing_cost' => $printingCost,
            'total_cost' => $materialCost + $printingCost,
            'is_estimated' => true,
            'estimation_method' => 'metadata_fallback',
            'analysis_timestamp' => time()
        ];
    }
    
    /**
     * Atualiza o progresso de uma tarefa
     * 
     * @param string $taskId ID da tarefa
     * @param int $progress Porcentagem de progresso (0-100)
     * @param string $message Mensagem de progresso
     * @return bool Sucesso da operação
     */
    private function updateProgress(string $taskId, int $progress, string $message = ''): bool {
        try {
            $this->log("Progresso [{$progress}%]: {$message}");
            
            return $this->queue->updateTaskStatus(
                $taskId,
                QuotationQueue::STATUS_PROCESSING,
                $progress,
                null,
                $message,
                $this->workerId
            );
        } catch (Exception $e) {
            $this->log("Erro ao atualizar progresso: " . $e->getMessage(), true);
            return false;
        }
    }
    
    /**
     * Marca uma tarefa como concluída
     * 
     * @param string $taskId ID da tarefa
     * @param array $result Resultado da tarefa
     * @return bool Sucesso da operação
     */
    private function completeTask(string $taskId, array $result): bool {
        try {
            return $this->queue->updateTaskStatus(
                $taskId,
                QuotationQueue::STATUS_COMPLETED,
                100,
                $result,
                null,
                $this->workerId
            );
        } catch (Exception $e) {
            $this->log("Erro ao completar tarefa: " . $e->getMessage(), true);
            return false;
        }
    }
    
    /**
     * Marca uma tarefa como falha
     * 
     * @param string $taskId ID da tarefa
     * @param string $errorMessage Mensagem de erro
     * @return bool Sucesso da operação
     */
    private function failTask(string $taskId, string $errorMessage): bool {
        try {
            return $this->queue->updateTaskStatus(
                $taskId,
                QuotationQueue::STATUS_FAILED,
                null,
                null,
                $errorMessage,
                $this->workerId
            );
        } catch (Exception $e) {
            $this->log("Erro ao marcar tarefa como falha: " . $e->getMessage(), true);
            return false;
        }
    }
    
    /**
     * Envia notificação de conclusão da tarefa
     * 
     * @param array $task Dados da tarefa
     * @param array $result Resultado da tarefa
     * @return bool Sucesso da operação
     */
    private function sendNotification(array $task, array $result): bool {
        try {
            return $this->notifier->sendCompletionNotification($task, $result);
        } catch (Exception $e) {
            $this->log("Erro ao enviar notificação: " . $e->getMessage(), true);
            return false;
        }
    }
    
    /**
     * Envia notificação de erro da tarefa
     * 
     * @param array $task Dados da tarefa
     * @param string $errorMessage Mensagem de erro
     * @return bool Sucesso da operação
     */
    private function sendErrorNotification(array $task, string $errorMessage): bool {
        try {
            return $this->notifier->sendErrorNotification($task, $errorMessage);
        } catch (Exception $e) {
            $this->log("Erro ao enviar notificação de erro: " . $e->getMessage(), true);
            return false;
        }
    }
    
    /**
     * Interrompe o processamento
     */
    public function stop(): void {
        $this->log("Interrompendo processador...");
        $this->keepProcessing = false;
    }
    
    /**
     * Registra uma mensagem de log
     * 
     * @param string $message Mensagem a ser registrada
     * @param bool $isError Se é um erro
     */
    private function log(string $message, bool $isError = false): void {
        $timestamp = date('Y-m-d H:i:s');
        $prefix = $isError ? '[ERRO]' : '[INFO]';
        
        // Adicionar informações da tarefa atual, se existir
        $taskInfo = '';
        if ($this->currentTask) {
            $taskInfo = " [Tarefa: {$this->currentTask['task_id']}]";
        }
        
        $logMessage = "[{$timestamp}] {$prefix}{$taskInfo} {$message}";
        
        // Se em modo de depuração, exibir na saída
        if ($this->debugMode) {
            echo $logMessage . PHP_EOL;
        }
        
        // Registrar no log do sistema
        error_log($logMessage);
    }
    
    /**
     * Formata um tamanho em bytes para uma string legível
     * 
     * @param int $bytes Tamanho em bytes
     * @return string Tamanho formatado
     */
    private function formatSize(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
