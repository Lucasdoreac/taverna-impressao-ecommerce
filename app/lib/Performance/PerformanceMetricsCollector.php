<?php
/**
 * PerformanceMetricsCollector - Coletor de métricas de performance
 * 
 * Responsável pela coleta de métricas de performance do sistema e dos componentes.
 * Implementa métodos específicos para diferentes tipos de métricas.
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Lib\Performance
 * @version    1.0.0
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Security/InputValidationTrait.php';

class PerformanceMetricsCollector {
    use InputValidationTrait;
    
    /**
     * Conexão com o banco de dados
     * 
     * @var Database
     */
    private $db;
    
    /**
     * Armazenamento de métricas em memória
     * 
     * @var array
     */
    private static $metricsStore = [];
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Registra o início de uma medição de tempo
     * 
     * @param string $key Identificador da medição
     * @return void
     */
    public static function startTimer($key) {
        self::$metricsStore['timers'][$key] = microtime(true);
    }
    
    /**
     * Registra o fim de uma medição de tempo e retorna a duração
     * 
     * @param string $key Identificador da medição
     * @return float Duração em segundos ou null se não encontrada
     */
    public static function endTimer($key) {
        if (!isset(self::$metricsStore['timers'][$key])) {
            return null;
        }
        
        $duration = microtime(true) - self::$metricsStore['timers'][$key];
        
        // Armazenar resultado para análise posterior
        if (!isset(self::$metricsStore['durations'][$key])) {
            self::$metricsStore['durations'][$key] = [];
        }
        
        self::$metricsStore['durations'][$key][] = $duration;
        
        // Limitar o número de registros armazenados (últimos 100)
        if (count(self::$metricsStore['durations'][$key]) > 100) {
            array_shift(self::$metricsStore['durations'][$key]);
        }
        
        return $duration;
    }
    
    /**
     * Incrementa um contador de ocorrências
     * 
     * @param string $key Identificador do contador
     * @param int $increment Valor do incremento (default: 1)
     * @return int Valor atual do contador
     */
    public static function incrementCounter($key, $increment = 1) {
        if (!isset(self::$metricsStore['counters'][$key])) {
            self::$metricsStore['counters'][$key] = 0;
        }
        
        self::$metricsStore['counters'][$key] += $increment;
        
        return self::$metricsStore['counters'][$key];
    }
    
    /**
     * Registra um valor para uma métrica específica
     * 
     * @param string $key Identificador da métrica
     * @param mixed $value Valor da métrica
     * @return void
     */
    public static function recordValue($key, $value) {
        if (!isset(self::$metricsStore['values'][$key])) {
            self::$metricsStore['values'][$key] = [];
        }
        
        self::$metricsStore['values'][$key][] = $value;
        
        // Limitar o número de registros armazenados (últimos 100)
        if (count(self::$metricsStore['values'][$key]) > 100) {
            array_shift(self::$metricsStore['values'][$key]);
        }
    }
    
    /**
     * Registra um erro ou exceção
     * 
     * @param string $type Tipo de erro
     * @param string $message Mensagem de erro
     * @param string $context Contexto do erro
     * @return void
     */
    public static function recordError($type, $message, $context = '') {
        if (!isset(self::$metricsStore['errors'])) {
            self::$metricsStore['errors'] = [];
        }
        
        self::$metricsStore['errors'][] = [
            'type' => $type,
            'message' => $message,
            'context' => $context,
            'timestamp' => time()
        ];
        
        // Limitar o número de erros armazenados (últimos 100)
        if (count(self::$metricsStore['errors']) > 100) {
            array_shift(self::$metricsStore['errors']);
        }
        
        // Incrementar contador de erros
        self::incrementCounter('error_count');
    }
    
    /**
     * Registra um acesso ao cache
     * 
     * @param string $cacheType Tipo de cache
     * @param bool $hit True para hit, false para miss
     * @return void
     */
    public static function recordCacheAccess($cacheType, $hit) {
        $key = 'cache_' . $cacheType;
        
        if (!isset(self::$metricsStore['cache'][$key])) {
            self::$metricsStore['cache'][$key] = [
                'hits' => 0,
                'misses' => 0,
                'total' => 0
            ];
        }
        
        if ($hit) {
            self::$metricsStore['cache'][$key]['hits']++;
        } else {
            self::$metricsStore['cache'][$key]['misses']++;
        }
        
        self::$metricsStore['cache'][$key]['total']++;
    }
    
    /**
     * Limpa todos os dados de métricas em memória
     * 
     * @return void
     */
    public static function resetMetrics() {
        self::$metricsStore = [];
    }
    
    // =================================================================
    // Métodos de coleta de métricas específicas
    // =================================================================
    
    /**
     * Obtém o uso de memória atual
     * 
     * @return float Uso de memória em MB
     */
    public function getMemoryUsage() {
        return round(memory_get_usage(true) / 1048576, 2); // Converter para MB
    }
    
    /**
     * Obtém o uso de memória de pico
     * 
     * @return float Uso de memória de pico em MB
     */
    public function getPeakMemoryUsage() {
        return round(memory_get_peak_usage(true) / 1048576, 2); // Converter para MB
    }
    
    /**
     * Obtém o uso atual de disco no diretório de uploads
     * 
     * @param string $directory Diretório para verificar (padrão: diretório de uploads)
     * @return float Porcentagem de uso do disco
     */
    public function getDiskUsage($directory = null) {
        if ($directory === null) {
            $directory = __DIR__ . '/../../../uploads';
        }
        
        $directory = $this->validateString($directory);
        
        // Verificar se o diretório existe
        if (!is_dir($directory)) {
            return 0;
        }
        
        $disk = disk_free_space($directory);
        $total = disk_total_space($directory);
        
        if ($disk === false || $total === false) {
            return 0;
        }
        
        $usedSpace = $total - $disk;
        $percentUsed = ($usedSpace / $total) * 100;
        
        return round($percentUsed, 2);
    }
    
    /**
     * Obtém o número de usuários concorrentes ativos
     * 
     * @param int $timeWindow Janela de tempo em minutos (padrão: 15 minutos)
     * @return int Número de usuários ativos
     */
    public function getConcurrentUsers($timeWindow = 15) {
        $timeWindow = max(1, min(60, (int)$timeWindow));
        $timestamp = date('Y-m-d H:i:s', time() - ($timeWindow * 60));
        
        $sql = "SELECT COUNT(DISTINCT user_id) as count 
                FROM user_sessions 
                WHERE last_activity > :timestamp 
                AND status = 'active'";
        
        $params = [':timestamp' => $timestamp];
        $result = $this->db->fetchSingle($sql, $params);
        
        return isset($result['count']) ? (int)$result['count'] : 0;
    }
    
    /**
     * Obtém a taxa de erros das últimas operações
     * 
     * @return float Porcentagem de taxa de erros
     */
    public function getErrorRate() {
        if (!isset(self::$metricsStore['counters']['error_count'])) {
            return 0;
        }
        
        $totalRequests = isset(self::$metricsStore['counters']['request_count']) 
            ? self::$metricsStore['counters']['request_count'] 
            : 100; // Valor padrão para evitar divisão por zero
        
        $errorCount = self::$metricsStore['counters']['error_count'];
        
        return round(($errorCount / $totalRequests) * 100, 2);
    }
    
    /**
     * Obtém o tempo médio de resposta das operações recentes
     * 
     * @param string $key Identificador específico (opcional)
     * @return float Tempo médio de resposta em segundos
     */
    public function getAverageResponseTime($key = null) {
        if ($key !== null) {
            // Tempo de resposta para uma operação específica
            if (!isset(self::$metricsStore['durations'][$key]) || empty(self::$metricsStore['durations'][$key])) {
                return 0;
            }
            
            return array_sum(self::$metricsStore['durations'][$key]) / count(self::$metricsStore['durations'][$key]);
        } else {
            // Tempo médio geral (todas as operações)
            if (!isset(self::$metricsStore['durations']) || empty(self::$metricsStore['durations'])) {
                return 0;
            }
            
            $totalDuration = 0;
            $totalOperations = 0;
            
            foreach (self::$metricsStore['durations'] as $durations) {
                $totalDuration += array_sum($durations);
                $totalOperations += count($durations);
            }
            
            return $totalOperations > 0 ? $totalDuration / $totalOperations : 0;
        }
    }
    
    /**
     * Obtém o percentil de tempo de resposta
     * 
     * @param string $key Identificador da operação
     * @param float $percentile Percentil (0-100)
     * @return float Valor do percentil ou null se não houver dados
     */
    public function getResponseTimePercentile($key, $percentile = 95) {
        if (!isset(self::$metricsStore['durations'][$key]) || empty(self::$metricsStore['durations'][$key])) {
            return null;
        }
        
        $durations = self::$metricsStore['durations'][$key];
        sort($durations);
        
        $index = ceil(($percentile / 100) * count($durations)) - 1;
        return $durations[$index];
    }
    
    /**
     * Obtém a taxa de acertos de cache
     * 
     * @param string $cacheType Tipo de cache (opcional)
     * @return float Porcentagem de acertos de cache
     */
    public function getCacheHitRatio($cacheType = null) {
        if (!isset(self::$metricsStore['cache'])) {
            return 0;
        }
        
        if ($cacheType !== null) {
            $key = 'cache_' . $cacheType;
            if (!isset(self::$metricsStore['cache'][$key]) || self::$metricsStore['cache'][$key]['total'] === 0) {
                return 0;
            }
            
            return round(
                (self::$metricsStore['cache'][$key]['hits'] / self::$metricsStore['cache'][$key]['total']) * 100, 
                2
            );
        } else {
            // Taxa de acertos global
            $totalHits = 0;
            $totalAccesses = 0;
            
            foreach (self::$metricsStore['cache'] as $cache) {
                $totalHits += $cache['hits'];
                $totalAccesses += $cache['total'];
            }
            
            return $totalAccesses > 0 ? round(($totalHits / $totalAccesses) * 100, 2) : 0;
        }
    }
    
    /**
     * Obtém o número de conexões de banco de dados ativas
     * 
     * @return int Número de conexões
     */
    public function getDatabaseConnectionCount() {
        $sql = "SHOW STATUS WHERE Variable_name = 'Threads_connected'";
        $result = $this->db->fetchSingle($sql);
        
        return isset($result['Value']) ? (int)$result['Value'] : 0;
    }
    
    /**
     * Obtém o tempo de execução médio de consultas SQL
     * 
     * @param int $minutesAgo Consultas nos últimos X minutos
     * @return float Tempo médio em segundos
     */
    public function getAverageQueryTime($minutesAgo = 5) {
        if (!extension_loaded('mysqli')) {
            return 0;
        }
        
        $sql = "SELECT AVG(query_time) as avg_time 
                FROM query_performance_log 
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)";
        
        $params = [':minutes' => max(1, min(60, (int)$minutesAgo))];
        $result = $this->db->fetchSingle($sql, $params);
        
        return isset($result['avg_time']) ? (float)$result['avg_time'] : 0;
    }
    
    /**
     * Obtém o número de consultas lentas recentes
     * 
     * @param float $slowThreshold Limiar em segundos para considerar consulta lenta
     * @param int $minutesAgo Consultas nos últimos X minutos
     * @return int Número de consultas lentas
     */
    public function getSlowQueryCount($slowThreshold = 1.0, $minutesAgo = 60) {
        $sql = "SELECT COUNT(*) as count 
                FROM query_performance_log 
                WHERE query_time > :threshold 
                AND timestamp >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)";
        
        $params = [
            ':threshold' => (float)$slowThreshold,
            ':minutes' => max(1, min(1440, (int)$minutesAgo))
        ];
        
        $result = $this->db->fetchSingle($sql, $params);
        
        return isset($result['count']) ? (int)$result['count'] : 0;
    }
    
    /**
     * Obtém a taxa de requisições por segundo
     * 
     * @param int $minutesAgo Requisições nos últimos X minutos
     * @return float Requisições por segundo
     */
    public function getRequestRate($minutesAgo = 5) {
        $sql = "SELECT COUNT(*) as count 
                FROM request_log 
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL :minutes MINUTE)";
        
        $params = [':minutes' => max(1, min(60, (int)$minutesAgo))];
        $result = $this->db->fetchSingle($sql, $params);
        
        $requestCount = isset($result['count']) ? (int)$result['count'] : 0;
        $seconds = $minutesAgo * 60;
        
        return $seconds > 0 ? round($requestCount / $seconds, 2) : 0;
    }
    
    /**
     * Obtém o número total de filas de impressão ativas
     * 
     * @return int Número de filas ativas
     */
    public function getActivePrintQueues() {
        $sql = "SELECT COUNT(*) as count 
                FROM print_queue 
                WHERE status IN ('waiting', 'processing', 'printing')";
        
        $result = $this->db->fetchSingle($sql);
        
        return isset($result['count']) ? (int)$result['count'] : 0;
    }
    
    /**
     * Obtém o tempo médio de processamento na fila de impressão
     * 
     * @param int $daysAgo Consultar últimos X dias
     * @return float Tempo médio em horas
     */
    public function getAveragePrintQueueTime($daysAgo = 7) {
        $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_time 
                FROM print_queue 
                WHERE status = 'completed' 
                AND completed_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $params = [':days' => max(1, min(90, (int)$daysAgo))];
        $result = $this->db->fetchSingle($sql, $params);
        
        return isset($result['avg_time']) ? round((float)$result['avg_time'], 2) : 0;
    }
    
    /**
     * Obtém o uso de CPU atual (apenas Linux)
     * 
     * @return float Porcentagem de uso de CPU
     */
    public function getCpuUsage() {
        if (PHP_OS !== 'Linux') {
            return 0;
        }
        
        $load = sys_getloadavg();
        if (!$load) {
            return 0;
        }
        
        // Obter número de núcleos
        $cores = 1;
        if (is_readable('/proc/cpuinfo')) {
            $cpuInfo = file_get_contents('/proc/cpuinfo');
            $cores = substr_count($cpuInfo, 'processor');
            if ($cores < 1) {
                $cores = 1;
            }
        }
        
        // Calcular porcentagem de uso (load average / número de núcleos * 100)
        return round(($load[0] / $cores) * 100, 2);
    }
    
    /**
     * Obtém o tamanho total da fila de email
     * 
     * @return int Número de emails na fila
     */
    public function getEmailQueueSize() {
        $sql = "SELECT COUNT(*) as count FROM email_queue WHERE sent = 0";
        $result = $this->db->fetchSingle($sql);
        
        return isset($result['count']) ? (int)$result['count'] : 0;
    }
    
    /**
     * Obtém o número de jobs agendados pendentes
     * 
     * @return int Número de jobs pendentes
     */
    public function getPendingScheduledJobs() {
        $sql = "SELECT COUNT(*) as count 
                FROM scheduled_jobs 
                WHERE status = 'pending' 
                AND scheduled_at <= NOW()";
        
        $result = $this->db->fetchSingle($sql);
        
        return isset($result['count']) ? (int)$result['count'] : 0;
    }
    
    /**
     * Obtém a taxa de conversão de vendas (visitantes -> compras)
     * 
     * @param int $daysAgo Consultar últimos X dias
     * @return float Porcentagem de conversão
     */
    public function getSalesConversionRate($daysAgo = 7) {
        // Número de visitantes únicos
        $sql1 = "SELECT COUNT(DISTINCT user_id) as visitors 
                FROM visitor_log 
                WHERE visited_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        // Número de compras
        $sql2 = "SELECT COUNT(*) as purchases 
                FROM orders 
                WHERE status != 'cancelled' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $params = [':days' => max(1, min(90, (int)$daysAgo))];
        
        $visitorsResult = $this->db->fetchSingle($sql1, $params);
        $purchasesResult = $this->db->fetchSingle($sql2, $params);
        
        $visitors = isset($visitorsResult['visitors']) ? (int)$visitorsResult['visitors'] : 0;
        $purchases = isset($purchasesResult['purchases']) ? (int)$purchasesResult['purchases'] : 0;
        
        if ($visitors === 0) {
            return 0;
        }
        
        return round(($purchases / $visitors) * 100, 2);
    }
    
    /**
     * Obtém a latência média de APIs externas
     * 
     * @param int $hoursAgo Consultar últimas X horas
     * @return float Latência média em segundos
     */
    public function getExternalApiLatency($hoursAgo = 24) {
        $sql = "SELECT AVG(response_time) as avg_latency 
                FROM api_requests 
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL :hours HOUR)";
        
        $params = [':hours' => max(1, min(168, (int)$hoursAgo))];
        $result = $this->db->fetchSingle($sql, $params);
        
        return isset($result['avg_latency']) ? round((float)$result['avg_latency'], 3) : 0;
    }
    
    /**
     * Registra uma requisição HTTP no log de requisições
     * 
     * @param string $endpoint Endpoint acessado
     * @param string $method Método HTTP
     * @param int $statusCode Código de status HTTP
     * @param float $responseTime Tempo de resposta em segundos
     * @return bool Sucesso da operação
     */
    public function logRequest($endpoint, $method, $statusCode, $responseTime) {
        $sql = "INSERT INTO request_log 
                (endpoint, method, status_code, response_time, timestamp) 
                VALUES 
                (:endpoint, :method, :status, :time, NOW())";
        
        $params = [
            ':endpoint' => $this->validateString($endpoint, ['maxLength' => 255]),
            ':method' => $this->validateString($method, ['maxLength' => 10]),
            ':status' => (int)$statusCode,
            ':time' => (float)$responseTime
        ];
        
        $result = $this->db->execute($sql, $params);
        
        // Incrementar contador de requisições para cálculo de taxa de erro
        self::incrementCounter('request_count');
        
        // Registrar tempo de resposta para cálculo de média
        self::recordValue('response_time', $responseTime);
        
        return $result !== false;
    }
    
    /**
     * Registra uma consulta SQL no log de performance
     * 
     * @param string $query Consulta SQL
     * @param float $executionTime Tempo de execução em segundos
     * @param int $rowsAffected Número de linhas afetadas
     * @return bool Sucesso da operação
     */
    public function logQueryPerformance($query, $executionTime, $rowsAffected = 0) {
        // Sanitizar consulta para evitar problemas
        $query = $this->validateString($query, ['maxLength' => 1000]);
        
        // Armazenar somente consultas lentas para economizar espaço
        if ($executionTime < 0.1) {
            return true;
        }
        
        $sql = "INSERT INTO query_performance_log 
                (query, query_time, rows_affected, timestamp) 
                VALUES 
                (:query, :time, :rows, NOW())";
        
        $params = [
            ':query' => $query,
            ':time' => (float)$executionTime,
            ':rows' => (int)$rowsAffected
        ];
        
        return $this->db->execute($sql, $params) !== false;
    }
}
