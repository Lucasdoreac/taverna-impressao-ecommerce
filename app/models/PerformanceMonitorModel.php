<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Modelo para Monitor de Testes de Performance
 * Responsável por monitorar e registrar resultados dos testes de performance em ambiente de produção,
 * incluindo coleta de métricas em tempo real, alertas e comparações com baseline
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

class PerformanceMonitorModel extends Model {
    protected $table = 'performance_monitors';
    protected $primaryKey = 'id';
    protected $fillable = [
        'test_id', 'start_time', 'end_time', 'status', 'config', 'results', 'alerts'
    ];
    
    /**
     * Construtor
     * Inicializa o modelo e verifica se as tabelas necessárias existem
     */
    public function __construct() {
        parent::__construct();
        
        // Verificar se as tabelas necessárias existem
        $this->checkAndCreateTables();
    }
    
    /**
     * Inicia o monitoramento de um teste de performance
     * 
     * @param int $testId ID do teste associado (opcional)
     * @param array $config Configurações de monitoramento
     * @return int|bool ID do monitor criado ou false em caso de erro
     */
    public function startMonitoring($testId = null, $config = []) {
        try {
            $data = [
                'test_id' => $testId,
                'start_time' => date('Y-m-d H:i:s'),
                'status' => 'running',
                'config' => json_encode($config),
                'results' => json_encode([]),
                'alerts' => json_encode([])
            ];
            
            return $this->create($data);
        } catch (Exception $e) {
            error_log("Erro ao iniciar monitoramento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Finaliza o monitoramento
     * 
     * @param int $monitorId ID do monitor
     * @param array $results Resultados finais do monitoramento
     * @return bool True se atualizado com sucesso, false caso contrário
     */
    public function stopMonitoring($monitorId, $results = []) {
        try {
            // Obter o monitor atual
            $monitor = $this->find($monitorId);
            if (!$monitor) {
                return false;
            }
            
            // Se já existem resultados, mesclar com os novos
            $existingResults = json_decode($monitor['results'], true) ?: [];
            $updatedResults = array_merge($existingResults, $results);
            
            // Atualizar o registro
            $data = [
                'end_time' => date('Y-m-d H:i:s'),
                'status' => 'completed',
                'results' => json_encode($updatedResults)
            ];
            
            return $this->update($monitorId, $data);
        } catch (Exception $e) {
            error_log("Erro ao finalizar monitoramento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra uma métrica de monitoramento
     * 
     * @param int $monitorId ID do monitor
     * @param string $metricName Nome da métrica
     * @param mixed $value Valor da métrica
     * @param string $timestamp Data e hora da coleta (opcional)
     * @return bool True se registrado com sucesso, false caso contrário
     */
    public function recordMetric($monitorId, $metricName, $value, $timestamp = null) {
        try {
            // Definir timestamp se não fornecido
            if ($timestamp === null) {
                $timestamp = date('Y-m-d H:i:s');
            }
            
            // Obter o monitor atual
            $monitor = $this->find($monitorId);
            if (!$monitor) {
                return false;
            }
            
            // Decodificar resultados existentes
            $results = json_decode($monitor['results'], true) ?: [];
            
            // Adicionar nova métrica
            if (!isset($results['metrics'])) {
                $results['metrics'] = [];
            }
            if (!isset($results['metrics'][$metricName])) {
                $results['metrics'][$metricName] = [];
            }
            
            $results['metrics'][$metricName][] = [
                'value' => $value,
                'timestamp' => $timestamp
            ];
            
            // Atualizar o registro
            return $this->update($monitorId, [
                'results' => json_encode($results)
            ]);
        } catch (Exception $e) {
            error_log("Erro ao registrar métrica: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria um alerta para uma métrica que ultrapassou o limite
     * 
     * @param int $monitorId ID do monitor
     * @param string $metricName Nome da métrica
     * @param float $threshold Valor limite
     * @param mixed $value Valor atual
     * @param string $timestamp Data e hora da detecção (opcional)
     * @return bool True se criado com sucesso, false caso contrário
     */
    public function createAlert($monitorId, $metricName, $threshold, $value, $timestamp = null) {
        try {
            // Definir timestamp se não fornecido
            if ($timestamp === null) {
                $timestamp = date('Y-m-d H:i:s');
            }
            
            // Obter o monitor atual
            $monitor = $this->find($monitorId);
            if (!$monitor) {
                return false;
            }
            
            // Decodificar alertas existentes
            $alerts = json_decode($monitor['alerts'], true) ?: [];
            
            // Adicionar novo alerta
            $alerts[] = [
                'metric' => $metricName,
                'threshold' => $threshold,
                'value' => $value,
                'timestamp' => $timestamp,
                'acknowledged' => false
            ];
            
            // Atualizar o registro
            return $this->update($monitorId, [
                'alerts' => json_encode($alerts)
            ]);
        } catch (Exception $e) {
            error_log("Erro ao criar alerta: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém todos os monitores ativos
     * 
     * @return array Lista de monitores ativos
     */
    public function getActiveMonitors() {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE status = 'running' ORDER BY start_time DESC";
            $monitors = $this->db()->select($sql);
            
            // Processar monitores
            foreach ($monitors as &$monitor) {
                $monitor['config'] = json_decode($monitor['config'], true);
                $monitor['results'] = json_decode($monitor['results'], true);
                $monitor['alerts'] = json_decode($monitor['alerts'], true);
            }
            
            return $monitors;
        } catch (Exception $e) {
            error_log("Erro ao obter monitores ativos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém um monitor específico pelo ID
     * 
     * @param int $id ID do monitor
     * @return array|null Dados do monitor ou null se não encontrado
     */
    public function getMonitorById($id) {
        try {
            $monitor = $this->find($id);
            if (!$monitor) {
                return null;
            }
            
            // Decodificar campos JSON
            $monitor['config'] = json_decode($monitor['config'], true);
            $monitor['results'] = json_decode($monitor['results'], true);
            $monitor['alerts'] = json_decode($monitor['alerts'], true);
            
            return $monitor;
        } catch (Exception $e) {
            error_log("Erro ao obter monitor por ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém resultados de um monitor específico
     * 
     * @param int $id ID do monitor
     * @return array Resultados do monitor
     */
    public function getMonitorResults($id) {
        try {
            $sql = "SELECT results FROM {$this->table} WHERE id = :id";
            $result = $this->db()->select($sql, ['id' => $id]);
            
            if (empty($result)) {
                return [];
            }
            
            return json_decode($result[0]['results'], true) ?: [];
        } catch (Exception $e) {
            error_log("Erro ao obter resultados do monitor: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém os resultados mais recentes dos monitores
     * 
     * @param int $limit Número máximo de resultados a retornar
     * @return array Lista de resultados recentes
     */
    public function getLatestResults($limit = 5) {
        try {
            $sql = "SELECT id, test_id, start_time, end_time, status, 
                    JSON_LENGTH(JSON_EXTRACT(alerts, '$')) as alert_count 
                    FROM {$this->table} 
                    ORDER BY start_time DESC 
                    LIMIT :limit";
            
            return $this->db()->select($sql, ['limit' => $limit]);
        } catch (Exception $e) {
            error_log("Erro ao obter resultados recentes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Compara os resultados de um monitor com um baseline
     * 
     * @param int $monitorId ID do monitor a ser comparado
     * @param int $baselineId ID do monitor de baseline
     * @return array Dados comparativos
     */
    public function compareWithBaseline($monitorId, $baselineId) {
        try {
            // Obter os dois monitores
            $monitor = $this->getMonitorById($monitorId);
            $baseline = $this->getMonitorById($baselineId);
            
            if (!$monitor || !$baseline) {
                return ['error' => 'Monitor ou baseline não encontrado'];
            }
            
            $comparison = [
                'monitor' => [
                    'id' => $monitorId,
                    'start_time' => $monitor['start_time'],
                    'status' => $monitor['status']
                ],
                'baseline' => [
                    'id' => $baselineId,
                    'start_time' => $baseline['start_time'],
                    'status' => $baseline['status']
                ],
                'metrics' => []
            ];
            
            // Comparar métricas entre os dois monitores
            if (isset($monitor['results']['metrics']) && isset($baseline['results']['metrics'])) {
                foreach ($monitor['results']['metrics'] as $metricName => $metricValues) {
                    // Verificar se a métrica existe no baseline
                    if (!isset($baseline['results']['metrics'][$metricName])) {
                        continue;
                    }
                    
                    // Calcular médias
                    $monitorAvg = $this->calculateMetricAverage($metricValues);
                    $baselineAvg = $this->calculateMetricAverage($baseline['results']['metrics'][$metricName]);
                    
                    // Calcular diferença percentual
                    $percentDiff = 0;
                    if ($baselineAvg != 0) {
                        $percentDiff = (($monitorAvg - $baselineAvg) / $baselineAvg) * 100;
                    }
                    
                    $comparison['metrics'][$metricName] = [
                        'monitor_avg' => $monitorAvg,
                        'baseline_avg' => $baselineAvg,
                        'diff' => $monitorAvg - $baselineAvg,
                        'percent_diff' => round($percentDiff, 2),
                        'improved' => $monitorAvg < $baselineAvg // Para métricas de tempo, menor é melhor
                    ];
                }
            }
            
            return $comparison;
        } catch (Exception $e) {
            error_log("Erro na comparação com baseline: " . $e->getMessage());
            return ['error' => 'Erro ao comparar com baseline: ' . $e->getMessage()];
        }
    }
    
    /**
     * Calcula a média de uma métrica
     * 
     * @param array $metricValues Valores da métrica
     * @return float Média da métrica
     */
    private function calculateMetricAverage($metricValues) {
        if (empty($metricValues)) {
            return 0;
        }
        
        $sum = 0;
        $count = 0;
        
        foreach ($metricValues as $value) {
            if (isset($value['value']) && is_numeric($value['value'])) {
                $sum += $value['value'];
                $count++;
            }
        }
        
        return $count > 0 ? $sum / $count : 0;
    }
    
    /**
     * Verifica se as tabelas necessárias existem e as cria se não existirem
     */
    private function checkAndCreateTables() {
        try {
            // Verificar se a tabela de monitores existe
            $sql = "SHOW TABLES LIKE '{$this->table}'";
            $result = $this->db()->select($sql);
            
            if (empty($result)) {
                // Criar tabela de monitores
                $sql = "CREATE TABLE {$this->table} (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          test_id INT,
                          start_time DATETIME,
                          end_time DATETIME,
                          status ENUM('running', 'completed', 'failed') NOT NULL DEFAULT 'running',
                          config TEXT,
                          results TEXT,
                          alerts TEXT,
                          INDEX (test_id),
                          INDEX (status),
                          INDEX (start_time)
                        )";
                $this->db()->execute($sql);
                
                // Registrar no log
                error_log("Tabela {$this->table} criada com sucesso.");
            }
            
            // Verificar se a tabela de métricas em tempo real existe
            $sql = "SHOW TABLES LIKE 'performance_realtime_metrics'";
            $result = $this->db()->select($sql);
            
            if (empty($result)) {
                // Criar tabela de métricas em tempo real
                $sql = "CREATE TABLE performance_realtime_metrics (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          monitor_id INT NOT NULL,
                          metric_name VARCHAR(255) NOT NULL,
                          value TEXT NOT NULL,
                          timestamp DATETIME NOT NULL,
                          INDEX (monitor_id),
                          INDEX (metric_name),
                          INDEX (timestamp)
                        )";
                $this->db()->execute($sql);
                
                // Registrar no log
                error_log("Tabela performance_realtime_metrics criada com sucesso.");
            }
            
            // Verificar se a tabela de baselines existe
            $sql = "SHOW TABLES LIKE 'performance_baselines'";
            $result = $this->db()->select($sql);
            
            if (empty($result)) {
                // Criar tabela de baselines
                $sql = "CREATE TABLE performance_baselines (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          name VARCHAR(255) NOT NULL,
                          monitor_id INT NOT NULL,
                          is_active BOOLEAN DEFAULT TRUE,
                          created_at DATETIME,
                          notes TEXT,
                          INDEX (monitor_id),
                          INDEX (is_active)
                        )";
                $this->db()->execute($sql);
                
                // Registrar no log
                error_log("Tabela performance_baselines criada com sucesso.");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao verificar/criar tabelas: " . $e->getMessage());
            return false;
        }
    }
}
?>