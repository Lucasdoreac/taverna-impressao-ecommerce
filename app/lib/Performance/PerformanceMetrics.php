<?php
/**
 * PerformanceMetrics - Coleta e armazenamento de métricas de desempenho
 * 
 * Classe responsável por coletar, armazenar e recuperar métricas de desempenho
 * do sistema, permitindo análise posterior e detecção de anomalias.
 * 
 * @package App\Lib\Performance
 * @author Taverna da Impressão 3D
 * @version 1.0.0
 */
class PerformanceMetrics {
    /** @var PDO Conexão com o banco de dados */
    private $db;
    
    /** @var string Contexto da medição atual (ex: "sales_report", "user_report") */
    private $context;
    
    /** @var array Buffer de métricas para inserção em lote */
    private $metricsBuffer = [];
    
    /** @var int Tamanho máximo do buffer antes de persistir */
    private $bufferSize = 50;
    
    /**
     * Construtor
     * 
     * @param PDO $db Conexão com o banco de dados
     */
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Inicia uma nova medição de performance
     * 
     * @param string $context Contexto da medição (ex: nome do relatório)
     * @param array $tags Tags adicionais para categorização
     * @return string ID único da medição
     */
    public function startMeasurement($context, array $tags = []) {
        $this->context = $context;
        $measurementId = uniqid($context . '_', true);
        
        $this->recordMetric('measurement_start', microtime(true), [
            'measurement_id' => $measurementId,
            'context' => $context,
            'tags' => json_encode($tags)
        ]);
        
        return $measurementId;
    }
    
    /**
     * Finaliza uma medição de performance
     * 
     * @param string $measurementId ID da medição a ser finalizada
     * @param array $additionalMetrics Métricas adicionais para registrar
     * @return array Resumo da medição
     */
    public function endMeasurement($measurementId, array $additionalMetrics = []) {
        $endTime = microtime(true);
        
        // Registrar fim da medição
        $this->recordMetric('measurement_end', $endTime, [
            'measurement_id' => $measurementId
        ]);
        
        // Registrar métricas adicionais
        foreach ($additionalMetrics as $name => $value) {
            $this->recordMetric($name, $value, [
                'measurement_id' => $measurementId
            ]);
        }
        
        // Persistir buffer imediatamente
        $this->persistBuffer();
        
        // Retornar resumo da medição
        return $this->getMeasurementSummary($measurementId);
    }
    
    /**
     * Registra uma métrica individual
     * 
     * @param string $name Nome da métrica
     * @param mixed $value Valor da métrica
     * @param array $attributes Atributos adicionais
     * @return void
     */
    public function recordMetric($name, $value, array $attributes = []) {
        $metric = [
            'name' => $name,
            'value' => $value,
            'context' => $attributes['context'] ?? $this->context,
            'measurement_id' => $attributes['measurement_id'] ?? null,
            'timestamp' => microtime(true),
            'tags' => isset($attributes['tags']) ? $attributes['tags'] : null
        ];
        
        // Adicionar ao buffer
        $this->metricsBuffer[] = $metric;
        
        // Persistir quando o buffer atingir o tamanho máximo
        if (count($this->metricsBuffer) >= $this->bufferSize) {
            $this->persistBuffer();
        }
    }
    
    /**
     * Persiste o buffer de métricas no banco de dados
     * 
     * @return bool True se bem-sucedido, false caso contrário
     */
    private function persistBuffer() {
        if (empty($this->metricsBuffer)) {
            return true;
        }
        
        try {
            // Iniciar transação
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                INSERT INTO performance_metrics 
                    (name, value, context, measurement_id, timestamp, tags) 
                VALUES 
                    (:name, :value, :context, :measurement_id, :timestamp, :tags)
            ");
            
            foreach ($this->metricsBuffer as $metric) {
                $stmt->execute([
                    ':name' => $metric['name'],
                    ':value' => is_numeric($metric['value']) ? $metric['value'] : json_encode($metric['value']),
                    ':context' => $metric['context'],
                    ':measurement_id' => $metric['measurement_id'],
                    ':timestamp' => $metric['timestamp'],
                    ':tags' => $metric['tags']
                ]);
            }
            
            // Commit da transação
            $this->db->commit();
            
            // Limpar buffer
            $this->metricsBuffer = [];
            
            return true;
        } catch (PDOException $e) {
            // Rollback em caso de erro
            $this->db->rollBack();
            
            // Registrar erro (mas não lançar exceção para não interromper o fluxo principal)
            error_log("Erro ao persistir métricas de performance: " . $e->getMessage());
            
            return false;
        }
    }
    
    /**
     * Obtém o resumo de uma medição
     * 
     * @param string $measurementId ID da medição
     * @return array Resumo da medição
     */
    public function getMeasurementSummary($measurementId) {
        // Forçar persistência do buffer para garantir que todas as métricas estejam no BD
        $this->persistBuffer();
        
        try {
            // Obter timestamp inicial
            $stmtStart = $this->db->prepare("
                SELECT value FROM performance_metrics 
                WHERE measurement_id = :id AND name = 'measurement_start'
                LIMIT 1
            ");
            $stmtStart->execute([':id' => $measurementId]);
            $startTime = (float)$stmtStart->fetchColumn();
            
            // Obter timestamp final
            $stmtEnd = $this->db->prepare("
                SELECT value FROM performance_metrics 
                WHERE measurement_id = :id AND name = 'measurement_end'
                LIMIT 1
            ");
            $stmtEnd->execute([':id' => $measurementId]);
            $endTime = (float)$stmtEnd->fetchColumn();
            
            // Obter todas as métricas da medição
            $stmtMetrics = $this->db->prepare("
                SELECT name, value, context, timestamp, tags
                FROM performance_metrics 
                WHERE measurement_id = :id
                ORDER BY timestamp ASC
            ");
            $stmtMetrics->execute([':id' => $measurementId]);
            $metrics = $stmtMetrics->fetchAll(PDO::FETCH_ASSOC);
            
            // Compilar resumo
            $summary = [
                'measurement_id' => $measurementId,
                'duration' => $endTime - $startTime,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'metrics' => $metrics
            ];
            
            return $summary;
        } catch (PDOException $e) {
            // Registrar erro
            error_log("Erro ao obter resumo da medição: " . $e->getMessage());
            
            return [
                'measurement_id' => $measurementId,
                'error' => 'Erro ao obter resumo da medição'
            ];
        }
    }
    
    /**
     * Obtém métricas históricas com base em filtros
     * 
     * @param array $filters Filtros a serem aplicados
     * @param int $limit Limite de resultados
     * @param int $offset Offset para paginação
     * @return array Métricas históricas
     */
    public function getHistoricalMetrics(array $filters = [], $limit = 100, $offset = 0) {
        try {
            $conditions = [];
            $params = [];
            
            // Construir condições baseadas nos filtros
            if (isset($filters['context'])) {
                $conditions[] = "context = :context";
                $params[':context'] = $filters['context'];
            }
            
            if (isset($filters['name'])) {
                $conditions[] = "name = :name";
                $params[':name'] = $filters['name'];
            }
            
            if (isset($filters['start_timestamp'])) {
                $conditions[] = "timestamp >= :start_timestamp";
                $params[':start_timestamp'] = $filters['start_timestamp'];
            }
            
            if (isset($filters['end_timestamp'])) {
                $conditions[] = "timestamp <= :end_timestamp";
                $params[':end_timestamp'] = $filters['end_timestamp'];
            }
            
            if (isset($filters['tag_search'])) {
                $conditions[] = "tags LIKE :tag_search";
                $params[':tag_search'] = '%' . $filters['tag_search'] . '%';
            }
            
            // Construir cláusula WHERE
            $where = empty($conditions) ? "" : "WHERE " . implode(" AND ", $conditions);
            
            // Preparar e executar consulta
            $stmt = $this->db->prepare("
                SELECT * FROM performance_metrics 
                {$where}
                ORDER BY timestamp DESC
                LIMIT :limit OFFSET :offset
            ");
            
            // Adicionar parâmetros de limite e offset
            $params[':limit'] = (int)$limit;
            $params[':offset'] = (int)$offset;
            
            // Executar com os parâmetros
            foreach ($params as $key => $value) {
                if (in_array($key, [':limit', ':offset'])) {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Registrar erro
            error_log("Erro ao obter métricas históricas: " . $e->getMessage());
            
            return [];
        }
    }
    
    /**
     * Calcula estatísticas agregadas para um tipo específico de métrica
     * 
     * @param string $metricName Nome da métrica
     * @param string $context Contexto opcional para filtrar
     * @param int $timeWindow Janela de tempo em segundos (0 = sem limite)
     * @return array Estatísticas agregadas
     */
    public function getMetricStatistics($metricName, $context = null, $timeWindow = 3600) {
        try {
            $conditions = ["name = :metric_name"];
            $params = [':metric_name' => $metricName];
            
            if ($context !== null) {
                $conditions[] = "context = :context";
                $params[':context'] = $context;
            }
            
            if ($timeWindow > 0) {
                $conditions[] = "timestamp >= :since";
                $params[':since'] = microtime(true) - $timeWindow;
            }
            
            $where = "WHERE " . implode(" AND ", $conditions);
            
            // Preparar e executar consulta
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as count,
                    MIN(CAST(value AS FLOAT)) as min_value,
                    MAX(CAST(value AS FLOAT)) as max_value,
                    AVG(CAST(value AS FLOAT)) as avg_value,
                    STDDEV(CAST(value AS FLOAT)) as std_dev
                FROM performance_metrics 
                {$where}
            ");
            
            $stmt->execute($params);
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Adicionar percentis
            $percentilesStmt = $this->db->prepare("
                SELECT CAST(value AS FLOAT) as value
                FROM performance_metrics 
                {$where}
                ORDER BY CAST(value AS FLOAT) ASC
            ");
            
            $percentilesStmt->execute($params);
            $values = $percentilesStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($values)) {
                $count = count($values);
                $stats['p50'] = $this->percentile($values, 50);
                $stats['p90'] = $this->percentile($values, 90);
                $stats['p95'] = $this->percentile($values, 95);
                $stats['p99'] = $this->percentile($values, 99);
            }
            
            return $stats;
        } catch (PDOException $e) {
            // Registrar erro
            error_log("Erro ao calcular estatísticas da métrica: " . $e->getMessage());
            
            return [
                'error' => 'Erro ao calcular estatísticas da métrica',
                'details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Calcula o valor do percentil para um conjunto de dados
     * 
     * @param array $values Valores ordenados
     * @param int $percentile Percentil desejado (0-100)
     * @return float Valor do percentil
     */
    private function percentile(array $values, $percentile) {
        $count = count($values);
        if ($count === 0) {
            return null;
        }
        
        $index = ceil($percentile / 100 * $count) - 1;
        return $values[max(0, min($index, $count - 1))];
    }
    
    /**
     * Limpa métricas antigas para evitar crescimento excessivo do banco de dados
     * 
     * @param int $maxAge Idade máxima em segundos (padrão: 30 dias)
     * @return int Número de métricas removidas
     */
    public function cleanupOldMetrics($maxAge = 2592000) {
        try {
            $cutoffTime = microtime(true) - $maxAge;
            
            $stmt = $this->db->prepare("
                DELETE FROM performance_metrics 
                WHERE timestamp < :cutoff_time
            ");
            
            $stmt->execute([':cutoff_time' => $cutoffTime]);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            // Registrar erro
            error_log("Erro ao limpar métricas antigas: " . $e->getMessage());
            
            return 0;
        }
    }
}