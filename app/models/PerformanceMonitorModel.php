<?php
/**
 * PerformanceMonitorModel - Modelo para gerenciamento de métricas de performance
 * 
 * Gerencia a coleta, armazenamento e recuperação de métricas de performance do sistema,
 * incluindo tempos de resposta, uso de recursos, métricas de banco de dados e eventos de segurança.
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Models
 * @version    1.0.0
 * @author     Claude
 */
class PerformanceMonitorModel extends Model {
    /**
     * Construtor
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Obtém métricas de performance para um período específico
     * 
     * @param int $hours Número de horas para visualizar
     * @return array Métricas de performance
     */
    public function getPerformanceMetrics($hours = 24) {
        try {
            $startTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
            
            $sql = "SELECT 
                    AVG(execution_time) as avg_execution_time,
                    MAX(execution_time) as max_execution_time,
                    MIN(execution_time) as min_execution_time,
                    AVG(memory_peak) as avg_memory_peak,
                    MAX(memory_peak) as max_memory_peak,
                    COUNT(*) as request_count
                    FROM performance_logs
                    WHERE timestamp >= :start_time";
            
            $params = [':start_time' => $startTime];
            
            $result = $this->db->query($sql, $params);
            
            if (empty($result)) {
                return [
                    'avgResponseTime' => 0,
                    'maxResponseTime' => 0,
                    'minResponseTime' => 0,
                    'avgMemoryPeak' => 0,
                    'maxMemoryPeak' => 0,
                    'requestCount' => 0
                ];
            }
            
            return [
                'avgResponseTime' => round($result[0]['avg_execution_time'] * 1000, 2), // Converter para ms
                'maxResponseTime' => round($result[0]['max_execution_time'] * 1000, 2), // Converter para ms
                'minResponseTime' => round($result[0]['min_execution_time'] * 1000, 2), // Converter para ms
                'avgMemoryPeak' => (int)$result[0]['avg_memory_peak'],
                'maxMemoryPeak' => (int)$result[0]['max_memory_peak'],
                'requestCount' => (int)$result[0]['request_count']
            ];
        } catch (Exception $e) {
            error_log('Erro ao obter métricas de performance: ' . $e->getMessage());
            return [
                'avgResponseTime' => 0,
                'maxResponseTime' => 0,
                'minResponseTime' => 0,
                'avgMemoryPeak' => 0,
                'maxMemoryPeak' => 0,
                'requestCount' => 0
            ];
        }
    }
    
    /**
     * Obtém métricas de erros para um período específico
     * 
     * @param int $hours Número de horas para visualizar
     * @return array Métricas de erros
     */
    public function getErrorMetrics($hours = 24) {
        try {
            $startTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
            
            // Total de erros no período
            $sql = "SELECT COUNT(*) as error_count FROM error_logs WHERE timestamp >= :start_time";
            $params = [':start_time' => $startTime];
            $errorCount = $this->db->query($sql, $params);
            
            // Total de requisições no período
            $sql = "SELECT COUNT(*) as request_count FROM performance_logs WHERE timestamp >= :start_time";
            $requestCount = $this->db->query($sql, $params);
            
            // Taxa de erro
            $totalErrors = isset($errorCount[0]['error_count']) ? (int)$errorCount[0]['error_count'] : 0;
            $totalRequests = isset($requestCount[0]['request_count']) ? (int)$requestCount[0]['request_count'] : 0;
            $errorRate = $totalRequests > 0 ? ($totalErrors / $totalRequests) * 100 : 0;
            
            // Distribuição de erros por tipo
            $sql = "SELECT type, COUNT(*) as count 
                    FROM error_logs 
                    WHERE timestamp >= :start_time 
                    GROUP BY type 
                    ORDER BY count DESC";
            
            $distribution = $this->db->query($sql, $params);
            
            $errorDistribution = [];
            foreach ($distribution as $row) {
                $errorDistribution[$row['type']] = (int)$row['count'];
            }
            
            // Erros recentes
            $sql = "SELECT id, type, url, message, timestamp 
                    FROM error_logs 
                    WHERE timestamp >= :start_time 
                    ORDER BY timestamp DESC 
                    LIMIT 10";
            
            $recentErrors = $this->db->query($sql, $params);
            
            return [
                'errorCount' => $totalErrors,
                'errorRate' => round($errorRate, 2),
                'distribution' => $errorDistribution,
                'recentErrors' => $recentErrors
            ];
        } catch (Exception $e) {
            error_log('Erro ao obter métricas de erros: ' . $e->getMessage());
            return [
                'errorCount' => 0,
                'errorRate' => 0,
                'distribution' => [],
                'recentErrors' => []
            ];
        }
    }
    
    /**
     * Obtém métricas de recursos para um período específico
     * 
     * @param int $hours Número de horas para visualizar
     * @return array Métricas de recursos
     */
    public function getResourceMetrics($hours = 24) {
        try {
            $startTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
            
            // Uso médio de recursos
            $sql = "SELECT 
                    AVG(memory_peak) as avg_memory_usage,
                    MAX(memory_peak) as max_memory_usage,
                    AVG(cpu_usage) as avg_cpu_usage,
                    MAX(cpu_usage) as max_cpu_usage
                    FROM resource_metrics
                    WHERE timestamp >= :start_time";
            
            $params = [':start_time' => $startTime];
            
            $result = $this->db->query($sql, $params);
            
            // Dados por hora para gráficos
            $sql = "SELECT 
                    DATE_FORMAT(timestamp, '%H:00') as time,
                    AVG(memory_peak) as memory_usage,
                    AVG(cpu_usage) as cpu_usage
                    FROM resource_metrics
                    WHERE timestamp >= :start_time
                    GROUP BY DATE_FORMAT(timestamp, '%Y-%m-%d %H')
                    ORDER BY timestamp ASC";
            
            $timeData = $this->db->query($sql, $params);
            
            return [
                'avgMemoryUsage' => isset($result[0]['avg_memory_usage']) ? (int)$result[0]['avg_memory_usage'] : 0,
                'maxMemoryUsage' => isset($result[0]['max_memory_usage']) ? (int)$result[0]['max_memory_usage'] : 0,
                'avgCpuUsage' => isset($result[0]['avg_cpu_usage']) ? (float)$result[0]['avg_cpu_usage'] : 0,
                'maxCpuUsage' => isset($result[0]['max_cpu_usage']) ? (float)$result[0]['max_cpu_usage'] : 0,
                'timeData' => $timeData
            ];
        } catch (Exception $e) {
            error_log('Erro ao obter métricas de recursos: ' . $e->getMessage());
            return [
                'avgMemoryUsage' => 0,
                'maxMemoryUsage' => 0,
                'avgCpuUsage' => 0,
                'maxCpuUsage' => 0,
                'timeData' => []
            ];
        }
    }
    
    /**
     * Obtém métricas de tempo de resposta para um período específico
     * 
     * @param int $hours Número de horas para visualizar
     * @return array Métricas de tempo de resposta
     */
    public function getResponseTimeMetrics($hours = 24) {
        try {
            $startTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
            
            // Dados por hora para gráficos
            $sql = "SELECT 
                    DATE_FORMAT(timestamp, '%H:00') as time,
                    AVG(execution_time * 1000) as avg_time,
                    (SELECT ROUND(tmp.execution_time * 1000, 2)
                     FROM performance_logs tmp
                     WHERE DATE_FORMAT(tmp.timestamp, '%Y-%m-%d %H') = DATE_FORMAT(pl.timestamp, '%Y-%m-%d %H')
                     ORDER BY tmp.execution_time DESC
                     LIMIT 1
                     OFFSET FLOOR(COUNT(*) * 0.95)) as p95_time
                    FROM performance_logs pl
                    WHERE timestamp >= :start_time
                    GROUP BY DATE_FORMAT(timestamp, '%Y-%m-%d %H')
                    ORDER BY timestamp ASC";
            
            $params = [':start_time' => $startTime];
            
            $timeData = $this->db->query($sql, $params);
            
            // Formatar dados para o gráfico
            foreach ($timeData as &$row) {
                $row['avgTime'] = round($row['avg_time'], 2);
                $row['p95Time'] = $row['p95_time'] ?: round($row['avg_time'] * 1.5, 2); // Fallback se p95 não estiver disponível
                unset($row['avg_time']);
                unset($row['p95_time']);
            }
            
            return [
                'timeData' => $timeData
            ];
        } catch (Exception $e) {
            error_log('Erro ao obter métricas de tempo de resposta: ' . $e->getMessage());
            return [
                'timeData' => []
            ];
        }
    }
    
    /**
     * Obtém métricas de banco de dados para um período específico
     * 
     * @param int $hours Número de horas para visualizar
     * @return array Métricas de banco de dados
     */
    public function getDatabaseMetrics($hours = 24) {
        try {
            $startTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
            
            // Métricas gerais
            $sql = "SELECT 
                    AVG(query_time) as avg_query_time,
                    MAX(query_time) as max_query_time,
                    SUM(query_count) as total_queries
                    FROM database_metrics
                    WHERE timestamp >= :start_time";
            
            $params = [':start_time' => $startTime];
            
            $result = $this->db->query($sql, $params);
            
            // Dados por hora para gráficos
            $sql = "SELECT 
                    DATE_FORMAT(timestamp, '%H:00') as time,
                    AVG(query_time) as avg_query_time,
                    SUM(query_count) as query_count
                    FROM database_metrics
                    WHERE timestamp >= :start_time
                    GROUP BY DATE_FORMAT(timestamp, '%Y-%m-%d %H')
                    ORDER BY timestamp ASC";
            
            $timeData = $this->db->query($sql, $params);
            
            // Formatar dados para o gráfico
            foreach ($timeData as &$row) {
                $row['avgQueryTime'] = round($row['avg_query_time'] * 1000, 2); // Converter para ms
                $row['queryCount'] = (int)$row['query_count'];
                unset($row['avg_query_time']);
                unset($row['query_count']);
            }
            
            return [
                'avgQueryTime' => isset($result[0]['avg_query_time']) ? round($result[0]['avg_query_time'] * 1000, 2) : 0, // Converter para ms
                'maxQueryTime' => isset($result[0]['max_query_time']) ? round($result[0]['max_query_time'] * 1000, 2) : 0, // Converter para ms
                'totalQueries' => isset($result[0]['total_queries']) ? (int)$result[0]['total_queries'] : 0,
                'timeData' => $timeData
            ];
        } catch (Exception $e) {
            error_log('Erro ao obter métricas de banco de dados: ' . $e->getMessage());
            return [
                'avgQueryTime' => 0,
                'maxQueryTime' => 0,
                'totalQueries' => 0,
                'timeData' => []
            ];
        }
    }
    
    /**
     * Obtém eventos de segurança para um período específico
     * 
     * @param int $hours Número de horas para visualizar
     * @return array Eventos de segurança
     */
    public function getSecurityEvents($hours = 24) {
        try {
            $startTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
            
            $sql = "SELECT id, type, ip_address, description, user_id, timestamp
                    FROM security_events
                    WHERE timestamp >= :start_time
                    ORDER BY timestamp DESC
                    LIMIT 20";
            
            $params = [':start_time' => $startTime];
            
            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            error_log('Erro ao obter eventos de segurança: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém um resumo de performance para exibição no dashboard principal
     * 
     * @param int $hours Número de horas para considerar
     * @return array Resumo de performance
     */
    public function getPerformanceSummary($hours = 24) {
        try {
            $startTime = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
            
            $sql = "SELECT 
                    AVG(execution_time * 1000) as avg_response_time,
                    AVG(memory_peak) as avg_memory_usage,
                    (SELECT COUNT(*) FROM error_logs WHERE timestamp >= :start_time) as error_count,
                    COUNT(*) as request_count
                    FROM performance_logs
                    WHERE timestamp >= :start_time";
            
            $params = [':start_time' => $startTime];
            
            $result = $this->db->query($sql, $params);
            
            if (empty($result)) {
                return [
                    'avgResponseTime' => 0,
                    'peakMemoryUsage' => 0,
                    'errorRate' => 0
                ];
            }
            
            $errorCount = (int)$result[0]['error_count'];
            $requestCount = (int)$result[0]['request_count'];
            $errorRate = $requestCount > 0 ? ($errorCount / $requestCount) * 100 : 0;
            
            return [
                'avgResponseTime' => round($result[0]['avg_response_time'], 2),
                'peakMemoryUsage' => (int)$result[0]['avg_memory_usage'],
                'errorRate' => round($errorRate, 2)
            ];
        } catch (Exception $e) {
            error_log('Erro ao obter resumo de performance: ' . $e->getMessage());
            return [
                'avgResponseTime' => 0,
                'peakMemoryUsage' => 0,
                'errorRate' => 0
            ];
        }
    }
    
    /**
     * Obtém alertas de performance ativos
     * 
     * @return array Alertas de performance
     */
    public function getPerformanceAlerts() {
        try {
            $alerts = [];
            
            // Verificar tempo de resposta nas últimas 3 horas
            $sql = "SELECT AVG(execution_time * 1000) as avg_time 
                    FROM performance_logs 
                    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 3 HOUR)";
            
            $result = $this->db->query($sql);
            $avgResponseTime = isset($result[0]['avg_time']) ? (float)$result[0]['avg_time'] : 0;
            
            if ($avgResponseTime > 500) {
                $alerts[] = [
                    'type' => 'danger',
                    'message' => "Tempo médio de resposta alto: " . round($avgResponseTime, 2) . " ms",
                    'details' => "O tempo médio de resposta está acima do limite recomendado (500ms) nas últimas 3 horas."
                ];
            } else if ($avgResponseTime > 300) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => "Tempo médio de resposta elevado: " . round($avgResponseTime, 2) . " ms",
                    'details' => "O tempo médio de resposta está elevado nas últimas 3 horas."
                ];
            }
            
            // Verificar taxa de erros nas últimas 3 horas
            $sql = "SELECT COUNT(*) as error_count 
                    FROM error_logs 
                    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 3 HOUR)";
            
            $errorResult = $this->db->query($sql);
            $errorCount = isset($errorResult[0]['error_count']) ? (int)$errorResult[0]['error_count'] : 0;
            
            $sql = "SELECT COUNT(*) as request_count 
                    FROM performance_logs 
                    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 3 HOUR)";
            
            $requestResult = $this->db->query($sql);
            $requestCount = isset($requestResult[0]['request_count']) ? (int)$requestResult[0]['request_count'] : 0;
            
            $errorRate = $requestCount > 0 ? ($errorCount / $requestCount) * 100 : 0;
            
            if ($errorRate > 5) {
                $alerts[] = [
                    'type' => 'danger',
                    'message' => "Taxa de erros alta: " . round($errorRate, 2) . "%",
                    'details' => "A taxa de erros está acima do limite aceitável (5%) nas últimas 3 horas."
                ];
            } else if ($errorRate > 2) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => "Taxa de erros elevada: " . round($errorRate, 2) . "%",
                    'details' => "A taxa de erros está elevada nas últimas 3 horas."
                ];
            }
            
            // Verificar uso de memória
            $sql = "SELECT AVG(memory_peak) as avg_memory 
                    FROM resource_metrics 
                    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 3 HOUR)";
            
            $result = $this->db->query($sql);
            $avgMemory = isset($result[0]['avg_memory']) ? (int)$result[0]['avg_memory'] : 0;
            
            // Limite arbitrário de 100MB para exemplo
            if ($avgMemory > 104857600) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => "Uso médio de memória elevado: " . round($avgMemory / 1048576, 2) . " MB",
                    'details' => "O uso médio de memória está elevado nas últimas 3 horas."
                ];
            }
            
            return $alerts;
        } catch (Exception $e) {
            error_log('Erro ao obter alertas de performance: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém métricas em tempo real para o dashboard de monitoramento
     * 
     * @param int $hours Número de horas para visualizar
     * @return array Todas as métricas para o dashboard
     */
    public function getRealtimeMetrics($hours = 24) {
        // Combinar todas as métricas em uma única estrutura
        return [
            'performance' => $this->getPerformanceMetrics($hours),
            'errors' => $this->getErrorMetrics($hours),
            'resources' => $this->getResourceMetrics($hours),
            'responseTime' => $this->getResponseTimeMetrics($hours),
            'databaseMetrics' => $this->getDatabaseMetrics($hours),
            'securityEvents' => $this->getSecurityEvents($hours)
        ];
    }
    
    /**
     * Registra uma métrica de performance
     * 
     * @param array $data Dados de performance
     * @return bool Sucesso da operação
     */
    public function logPerformanceMetric($data) {
        try {
            $sql = "INSERT INTO performance_logs 
                    (request_id, request_uri, method, execution_time, memory_start, memory_end, memory_peak, timestamp) 
                    VALUES 
                    (:request_id, :request_uri, :method, :execution_time, :memory_start, :memory_end, :memory_peak, NOW())";
            
            $params = [
                ':request_id' => $data['request_id'],
                ':request_uri' => $data['request_uri'],
                ':method' => $data['method'],
                ':execution_time' => $data['execution_time'],
                ':memory_start' => $data['memory_start'],
                ':memory_end' => $data['memory_end'],
                ':memory_peak' => $data['memory_peak']
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao registrar métrica de performance: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra um erro no sistema
     * 
     * @param string $type Tipo do erro
     * @param string $message Mensagem de erro
     * @param string $url URL onde ocorreu o erro
     * @param array $context Contexto adicional
     * @return bool Sucesso da operação
     */
    public function logError($type, $message, $url, $context = []) {
        try {
            $sql = "INSERT INTO error_logs 
                    (type, message, url, context, timestamp) 
                    VALUES 
                    (:type, :message, :url, :context, NOW())";
            
            $params = [
                ':type' => $type,
                ':message' => $message,
                ':url' => $url,
                ':context' => !empty($context) ? json_encode($context) : null
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao registrar erro: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra um evento de segurança
     * 
     * @param string $type Tipo do evento
     * @param string $description Descrição do evento
     * @param string $ipAddress Endereço IP
     * @param int $userId ID do usuário relacionado (0 para anônimo)
     * @return bool Sucesso da operação
     */
    public function logSecurityEvent($type, $description, $ipAddress, $userId = 0) {
        try {
            $sql = "INSERT INTO security_events 
                    (type, description, ip_address, user_id, timestamp) 
                    VALUES 
                    (:type, :description, :ip_address, :user_id, NOW())";
            
            $params = [
                ':type' => $type,
                ':description' => $description,
                ':ip_address' => $ipAddress,
                ':user_id' => $userId
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao registrar evento de segurança: ' . $e->getMessage());
            return false;
        }
    }
}
