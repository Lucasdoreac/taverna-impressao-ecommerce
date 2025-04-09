<?php
/**
 * PerformanceMonitor - Sistema de monitoramento de performance em tempo real
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Lib\Monitoring
 * @version    1.0.0
 * @author     Claude
 */

class PerformanceMonitor {
    /**
     * Pontos de verificação para medição de tempo
     * 
     * @var array
     */
    private static $checkpoints = [];
    
    /**
     * Contadores de eventos e métricas
     * 
     * @var array
     */
    private static $counters = [];
    
    /**
     * Tempos de resposta armazenados para cálculo de médias
     * 
     * @var array
     */
    private static $responseTimes = [];
    
    /**
     * Uso de memória em pontos específicos
     * 
     * @var array
     */
    private static $memoryUsage = [];
    
    /**
     * Armazena uma amostra de queries SQL para análise
     * 
     * @var array
     */
    private static $sqlQueries = [];
    
    /**
     * Limite de consultas SQL armazenadas para análise
     * 
     * @var int
     */
    private static $sqlQueriesLimit = 100;
    
    /**
     * Flag que indica se o monitoramento está ativo
     * 
     * @var bool
     */
    private static $enabled = false;
    
    /**
     * Timestamp de início da requisição atual
     * 
     * @var float
     */
    private static $requestStartTime = 0;
    
    /**
     * Identificador único da requisição atual
     * 
     * @var string
     */
    private static $requestId = '';
    
    /**
     * Inicializa o sistema de monitoramento
     * 
     * @param bool $enabled Se o monitoramento deve ser ativado
     * @return void
     */
    public static function initialize($enabled = true) {
        self::$enabled = $enabled;
        self::$requestStartTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        self::$requestId = uniqid('req_', true);
        
        // Inicializar contadores básicos
        self::$counters = [
            'db_queries' => 0,
            'db_query_time' => 0,
            'file_operations' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'queue_operations' => 0,
            'model_operations' => 0,
            'authentication_attempts' => 0,
            'validation_failures' => 0,
            'csrf_validations' => 0,
            'http_requests' => 0
        ];
        
        // Registrar uso de memória inicial
        self::$memoryUsage['start'] = memory_get_usage(true);
        
        // Registrar checkpoint inicial
        self::addCheckpoint('request_start');
        
        // Registrar função de finalização ao término do script
        register_shutdown_function([__CLASS__, 'finalize']);
    }
    
    /**
     * Finaliza o monitoramento e registra os dados
     * 
     * @return void
     */
    public static function finalize() {
        if (!self::$enabled) {
            return;
        }
        
        // Registrar uso de memória final
        self::$memoryUsage['end'] = memory_get_usage(true);
        
        // Registrar checkpoint final
        self::addCheckpoint('request_end');
        
        // Calcular tempo total da requisição
        $requestTime = microtime(true) - self::$requestStartTime;
        
        // Compilar dados de desempenho
        $performanceData = [
            'request_id' => self::$requestId,
            'timestamp' => date('Y-m-d H:i:s'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'execution_time' => $requestTime,
            'memory_start' => self::$memoryUsage['start'],
            'memory_end' => self::$memoryUsage['end'],
            'memory_peak' => memory_get_peak_usage(true),
            'checkpoints' => self::$checkpoints,
            'counters' => self::$counters,
            'response_times' => [
                'avg' => self::calculateAverageResponseTime(),
                'samples' => count(self::$responseTimes)
            ],
            'sql_queries' => array_slice(self::$sqlQueries, 0, 10) // Apenas as 10 primeiras para o log
        ];
        
        // Registrar dados no sistema de log
        self::logPerformanceData($performanceData);
        
        // Armazenar dados para análise posterior
        self::storePerformanceData($performanceData);
    }
    
    /**
     * Adiciona um checkpoint para medição de tempo
     * 
     * @param string $name Nome do checkpoint
     * @return float Tempo desde o início da requisição
     */
    public static function addCheckpoint($name) {
        if (!self::$enabled) {
            return 0;
        }
        
        $time = microtime(true);
        $timeSinceStart = $time - self::$requestStartTime;
        
        self::$checkpoints[$name] = [
            'timestamp' => $time,
            'seconds_since_start' => $timeSinceStart
        ];
        
        return $timeSinceStart;
    }
    
    /**
     * Registra o início de uma operação
     * 
     * @param string $operation Nome da operação
     * @return string ID único da operação para uso em endOperation
     */
    public static function startOperation($operation) {
        if (!self::$enabled) {
            return '';
        }
        
        $opId = uniqid($operation . '_', true);
        self::$checkpoints['op_start_' . $opId] = [
            'timestamp' => microtime(true),
            'operation' => $operation
        ];
        
        return $opId;
    }
    
    /**
     * Registra o término de uma operação e seu tempo de execução
     * 
     * @param string $opId ID da operação retornado por startOperation
     * @param bool $success Se a operação foi bem-sucedida
     * @return float Tempo de execução da operação em segundos
     */
    public static function endOperation($opId, $success = true) {
        if (!self::$enabled || empty($opId)) {
            return 0;
        }
        
        $startKey = 'op_start_' . $opId;
        if (!isset(self::$checkpoints[$startKey])) {
            return 0;
        }
        
        $startTime = self::$checkpoints[$startKey]['timestamp'];
        $operation = self::$checkpoints[$startKey]['operation'];
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Registrar checkpoint de término
        self::$checkpoints['op_end_' . $opId] = [
            'timestamp' => $endTime,
            'operation' => $operation,
            'execution_time' => $executionTime,
            'success' => $success
        ];
        
        // Atualizar métricas de tempo de resposta
        self::$responseTimes[] = $executionTime;
        
        // Incrementar contadores específicos
        if (isset(self::$counters[$operation])) {
            self::$counters[$operation]++;
        }
        
        return $executionTime;
    }
    
    /**
     * Incrementa um contador específico
     * 
     * @param string $counter Nome do contador
     * @param int $value Valor a incrementar (padrão: 1)
     * @return void
     */
    public static function incrementCounter($counter, $value = 1) {
        if (!self::$enabled) {
            return;
        }
        
        if (!isset(self::$counters[$counter])) {
            self::$counters[$counter] = 0;
        }
        
        self::$counters[$counter] += $value;
    }
    
    /**
     * Registra uma consulta SQL para análise de performance
     * 
     * @param string $query Consulta SQL
     * @param float $executionTime Tempo de execução da consulta
     * @param int $rowCount Número de linhas afetadas/retornadas
     * @return void
     */
    public static function logSqlQuery($query, $executionTime, $rowCount = 0) {
        if (!self::$enabled) {
            return;
        }
        
        // Incrementar contador de consultas
        self::incrementCounter('db_queries');
        self::$counters['db_query_time'] += $executionTime;
        
        // Limitação para não sobrecarregar a memória
        if (count(self::$sqlQueries) >= self::$sqlQueriesLimit) {
            return;
        }
        
        // Remover dados sensíveis da consulta para logging
        $sanitizedQuery = self::sanitizeSqlQuery($query);
        
        self::$sqlQueries[] = [
            'query' => $sanitizedQuery,
            'execution_time' => $executionTime,
            'row_count' => $rowCount,
            'timestamp' => microtime(true)
        ];
    }
    
    /**
     * Registra o tempo de uma operação específica
     * 
     * @param string $operation Nome da operação
     * @param float $executionTime Tempo de execução
     * @param bool $success Se a operação foi bem-sucedida
     * @return void
     */
    public static function logOperationTime($operation, $executionTime, $success = true) {
        if (!self::$enabled) {
            return;
        }
        
        // Atualizar métricas de tempo de resposta
        self::$responseTimes[] = $executionTime;
        
        // Registrar checkpoint com detalhes da operação
        $opId = uniqid($operation . '_', true);
        self::$checkpoints['op_' . $opId] = [
            'timestamp' => microtime(true),
            'operation' => $operation,
            'execution_time' => $executionTime,
            'success' => $success
        ];
        
        // Incrementar contadores específicos
        if (isset(self::$counters[$operation])) {
            self::$counters[$operation]++;
        }
    }
    
    /**
     * Registra uso de memória em um ponto específico
     * 
     * @param string $checkpoint Nome do checkpoint
     * @return int Uso de memória em bytes
     */
    public static function logMemoryUsage($checkpoint) {
        if (!self::$enabled) {
            return 0;
        }
        
        $memoryUsage = memory_get_usage(true);
        self::$memoryUsage[$checkpoint] = $memoryUsage;
        
        return $memoryUsage;
    }
    
    /**
     * Calcula tempo médio de resposta com base nas amostras coletadas
     * 
     * @return float Tempo médio de resposta
     */
    private static function calculateAverageResponseTime() {
        if (empty(self::$responseTimes)) {
            return 0;
        }
        
        return array_sum(self::$responseTimes) / count(self::$responseTimes);
    }
    
    /**
     * Sanitiza uma consulta SQL para logging (remove dados sensíveis)
     * 
     * @param string $query Consulta SQL original
     * @return string Consulta sanitizada
     */
    private static function sanitizeSqlQuery($query) {
        // Substituir valores sensíveis em consultas SQL
        $patterns = [
            '/password\s*=\s*\'[^\']*\'/i' => 'password=\'***\'',
            '/pwd\s*=\s*\'[^\']*\'/i' => 'pwd=\'***\'',
            '/token\s*=\s*\'[^\']*\'/i' => 'token=\'***\''
        ];
        
        return preg_replace(array_keys($patterns), array_values($patterns), $query);
    }
    
    /**
     * Registra dados de performance no arquivo de log
     * 
     * @param array $data Dados de performance
     * @return void
     */
    private static function logPerformanceData($data) {
        // Criar string de log resumida
        $logEntry = sprintf(
            "[%s] %s %s - Tempo: %.4fs, Memória: %s, Queries: %d\n",
            $data['timestamp'],
            $data['method'],
            $data['request_uri'],
            $data['execution_time'],
            self::formatBytes($data['memory_peak']),
            $data['counters']['db_queries']
        );
        
        // Caminho do arquivo de log
        $logFile = __DIR__ . '/../../../logs/performance.log';
        
        // Garantir que o diretório existe
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Adicionar ao arquivo de log
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Armazena dados de performance para análise posterior
     * 
     * @param array $data Dados de performance
     * @return void
     */
    private static function storePerformanceData($data) {
        // Armazenar dados detalhados em formato JSON
        $dataFile = __DIR__ . '/../../../logs/performance/detail_' . date('Y-m-d') . '.json';
        
        // Garantir que o diretório existe
        $dataDir = dirname($dataFile);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        // Formato dos dados detalhados para armazenamento
        $storageData = json_encode($data) . "\n";
        
        // Adicionar ao arquivo de dados
        file_put_contents($dataFile, $storageData, FILE_APPEND);
    }
    
    /**
     * Formata um valor em bytes para uma representação legível
     * 
     * @param int $bytes Valor em bytes
     * @return string Valor formatado
     */
    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
