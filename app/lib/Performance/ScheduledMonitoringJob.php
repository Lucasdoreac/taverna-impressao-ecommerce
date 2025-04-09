<?php
/**
 * ScheduledMonitoringJob - Agendador de tarefas de monitoramento de performance
 * 
 * Gerencia a execução periódica de verificações de performance e métricas
 * do sistema, garantindo monitoramento contínuo.
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Lib\Performance
 * @version    1.0.0
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Security/InputValidationTrait.php';
require_once __DIR__ . '/PerformanceMonitor.php';

class ScheduledMonitoringJob {
    use InputValidationTrait;
    
    /**
     * Conexão com o banco de dados
     * 
     * @var Database
     */
    private $db;
    
    /**
     * Monitor de performance
     * 
     * @var PerformanceMonitor
     */
    private $monitor;
    
    /**
     * Identificador do job em execução
     * 
     * @var int
     */
    private $jobId;
    
    /**
     * Caminho para arquivo de lock
     * 
     * @var string
     */
    private $lockFile;
    
    /**
     * Timeout de lock em segundos
     * 
     * @var int
     */
    private $lockTimeout = 1800; // 30 minutos
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->monitor = PerformanceMonitor::getInstance();
        $this->lockFile = sys_get_temp_dir() . '/performance_monitor.lock';
    }
    
    /**
     * Executa o job de monitoramento
     * 
     * @param array $options Opções de execução
     * @return bool Sucesso da operação
     */
    public function run($options = []) {
        try {
            // Verificar lock para evitar execuções concorrentes
            if (!$this->acquireLock()) {
                error_log('Monitoramento já em execução, ignorando.');
                return false;
            }
            
            // Registrar início do job
            $this->jobId = $this->registerJobStart($options);
            
            // Executar ciclo de monitoramento
            $result = $this->monitor->runMonitoringCycle();
            
            // Executar verificação adaptativa de thresholds periodicamente
            if (isset($options['adaptThresholds']) && $options['adaptThresholds'] === true) {
                $this->monitor->adaptThresholds();
            }
            
            // Registrar conclusão do job
            $this->registerJobEnd($result);
            
            // Liberar lock
            $this->releaseLock();
            
            return $result;
        } catch (Exception $e) {
            error_log('Erro ao executar job de monitoramento: ' . $e->getMessage());
            
            // Registrar falha do job
            if ($this->jobId) {
                $this->registerJobFailure($e->getMessage());
            }
            
            // Garantir que o lock seja liberado mesmo em caso de erro
            $this->releaseLock();
            
            return false;
        }
    }
    
    /**
     * Adquire lock exclusivo para execução
     * 
     * @return bool Sucesso da aquisição
     */
    private function acquireLock() {
        // Verificar se já existe um lock
        if (file_exists($this->lockFile)) {
            $lockTime = filemtime($this->lockFile);
            
            // Verificar se o lock expirou
            if (time() - $lockTime < $this->lockTimeout) {
                return false;
            }
            
            // Lock expirado, remover
            @unlink($this->lockFile);
        }
        
        // Criar novo lock
        $handle = @fopen($this->lockFile, 'w');
        if ($handle === false) {
            error_log('Não foi possível criar arquivo de lock.');
            return false;
        }
        
        // Escrever ID do processo no lock
        fwrite($handle, getmypid());
        fclose($handle);
        
        return true;
    }
    
    /**
     * Libera o lock de execução
     * 
     * @return bool Sucesso da operação
     */
    private function releaseLock() {
        if (file_exists($this->lockFile)) {
            return @unlink($this->lockFile);
        }
        
        return true;
    }
    
    /**
     * Registra início de execução do job
     * 
     * @param array $options Opções de execução
     * @return int ID do job
     */
    private function registerJobStart($options) {
        $sql = "INSERT INTO scheduled_jobs 
                (job_type, parameters, status, scheduled_at, started_at) 
                VALUES 
                ('performance_monitoring', :params, 'running', NOW(), NOW())";
        
        $params = [':params' => json_encode($options)];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Registra conclusão bem-sucedida do job
     * 
     * @param bool $result Resultado da execução
     * @return bool Sucesso da operação
     */
    private function registerJobEnd($result) {
        $status = $result ? 'completed' : 'failed';
        
        $sql = "UPDATE scheduled_jobs 
                SET status = :status, 
                    completed_at = NOW(), 
                    result = :result 
                WHERE id = :job_id";
        
        $params = [
            ':job_id' => $this->jobId,
            ':status' => $status,
            ':result' => json_encode(['success' => $result])
        ];
        
        return $this->db->execute($sql, $params) !== false;
    }
    
    /**
     * Registra falha na execução do job
     * 
     * @param string $errorMessage Mensagem de erro
     * @return bool Sucesso da operação
     */
    private function registerJobFailure($errorMessage) {
        $sql = "UPDATE scheduled_jobs 
                SET status = 'failed', 
                    completed_at = NOW(), 
                    result = :result 
                WHERE id = :job_id";
        
        $params = [
            ':job_id' => $this->jobId,
            ':result' => json_encode([
                'success' => false,
                'error' => $errorMessage
            ])
        ];
        
        return $this->db->execute($sql, $params) !== false;
    }
    
    /**
     * Agenda jobs de monitoramento com base na configuração de componentes
     * 
     * @return bool Sucesso da operação
     */
    public function scheduleMonitoringJobs() {
        try {
            // Obter componentes monitorados e seus intervalos
            $sql = "SELECT component_name, check_interval 
                    FROM monitored_components 
                    WHERE active = 1";
            
            $components = $this->db->fetchAll($sql);
            
            if (empty($components)) {
                // Configurar pelo menos o monitoramento do sistema
                $this->scheduleSystemMonitoring();
                return true;
            }
            
            // Agendar monitoramento para cada componente
            foreach ($components as $component) {
                $interval = isset($component['check_interval']) && $component['check_interval'] > 0 
                    ? $component['check_interval'] 
                    : 300; // 5 minutos por padrão
                
                $this->scheduleComponentMonitoring($component['component_name'], $interval);
            }
            
            // Agendar verificação adaptativa de thresholds (diariamente)
            $this->scheduleAdaptiveThresholds();
            
            return true;
        } catch (Exception $e) {
            error_log('Erro ao agendar jobs de monitoramento: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Agenda monitoramento para um componente específico
     * 
     * @param string $componentName Nome do componente
     * @param int $interval Intervalo em segundos
     * @return bool Sucesso da operação
     */
    private function scheduleComponentMonitoring($componentName, $interval) {
        // Verificar se já existe um job agendado para este componente
        $sql = "SELECT id, scheduled_at 
                FROM scheduled_jobs 
                WHERE job_type = 'performance_monitoring' 
                AND status = 'pending' 
                AND parameters LIKE :component 
                ORDER BY scheduled_at ASC 
                LIMIT 1";
        
        $params = [':component' => '%' . $componentName . '%'];
        $existingJob = $this->db->fetchSingle($sql, $params);
        
        if ($existingJob) {
            // Já existe um job agendado, não fazer nada
            return true;
        }
        
        // Agendar novo job
        $scheduledTime = date('Y-m-d H:i:s', time() + $interval);
        
        $sql = "INSERT INTO scheduled_jobs 
                (job_type, parameters, status, scheduled_at) 
                VALUES 
                ('performance_monitoring', :params, 'pending', :scheduled_at)";
        
        $params = [
            ':params' => json_encode([
                'component' => $componentName,
                'interval' => $interval
            ]),
            ':scheduled_at' => $scheduledTime
        ];
        
        return $this->db->execute($sql, $params) !== false;
    }
    
    /**
     * Agenda monitoramento geral do sistema
     * 
     * @param int $interval Intervalo em segundos (padrão: 5 minutos)
     * @return bool Sucesso da operação
     */
    private function scheduleSystemMonitoring($interval = 300) {
        // Verificar se já existe um job de sistema agendado
        $sql = "SELECT id 
                FROM scheduled_jobs 
                WHERE job_type = 'performance_monitoring' 
                AND parameters LIKE '%\"component\":\"System\"%' 
                AND status = 'pending' 
                ORDER BY scheduled_at ASC 
                LIMIT 1";
        
        $existingJob = $this->db->fetchSingle($sql);
        
        if ($existingJob) {
            // Já existe um job agendado, não fazer nada
            return true;
        }
        
        // Agendar novo job
        $scheduledTime = date('Y-m-d H:i:s', time() + $interval);
        
        $sql = "INSERT INTO scheduled_jobs 
                (job_type, parameters, status, scheduled_at) 
                VALUES 
                ('performance_monitoring', :params, 'pending', :scheduled_at)";
        
        $params = [
            ':params' => json_encode([
                'component' => 'System',
                'interval' => $interval
            ]),
            ':scheduled_at' => $scheduledTime
        ];
        
        return $this->db->execute($sql, $params) !== false;
    }
    
    /**
     * Agenda verificação adaptativa de thresholds
     * 
     * @return bool Sucesso da operação
     */
    private function scheduleAdaptiveThresholds() {
        // Verificar se já existe um job agendado para hoje
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');
        
        $sql = "SELECT id 
                FROM scheduled_jobs 
                WHERE job_type = 'performance_monitoring' 
                AND parameters LIKE '%\"adaptThresholds\":true%' 
                AND scheduled_at BETWEEN :start AND :end 
                AND status IN ('pending', 'running', 'completed')";
        
        $params = [
            ':start' => $todayStart,
            ':end' => $todayEnd
        ];
        
        $existingJob = $this->db->fetchSingle($sql, $params);
        
        if ($existingJob) {
            // Já existe um job agendado para hoje
            return true;
        }
        
        // Agendar para executar à noite (3:00 AM)
        $scheduledTime = date('Y-m-d 03:00:00', strtotime('tomorrow'));
        
        $sql = "INSERT INTO scheduled_jobs 
                (job_type, parameters, status, scheduled_at) 
                VALUES 
                ('performance_monitoring', :params, 'pending', :scheduled_at)";
        
        $params = [
            ':params' => json_encode([
                'adaptThresholds' => true,
                'component' => 'All'
            ]),
            ':scheduled_at' => $scheduledTime
        ];
        
        return $this->db->execute($sql, $params) !== false;
    }
    
    /**
     * Executa jobs pendentes agendados
     * 
     * @return array Resultados da execução
     */
    public function runPendingJobs() {
        // Obter jobs pendentes e programados para execução
        $sql = "SELECT id, parameters 
                FROM scheduled_jobs 
                WHERE job_type = 'performance_monitoring' 
                AND status = 'pending' 
                AND scheduled_at <= NOW() 
                ORDER BY scheduled_at ASC";
        
        $pendingJobs = $this->db->fetchAll($sql);
        
        if (empty($pendingJobs)) {
            return ['status' => 'success', 'message' => 'Nenhum job pendente.'];
        }
        
        $results = [];
        
        foreach ($pendingJobs as $job) {
            // Marcar job como "em execução"
            $sql = "UPDATE scheduled_jobs 
                    SET status = 'running', started_at = NOW() 
                    WHERE id = :job_id";
            
            $this->db->execute($sql, [':job_id' => $job['id']]);
            
            // Definir job ID
            $this->jobId = $job['id'];
            
            // Executar o job
            $parameters = json_decode($job['parameters'], true) ?: [];
            $result = $this->monitor->runMonitoringCycle();
            
            // Adaptar thresholds se necessário
            if (isset($parameters['adaptThresholds']) && $parameters['adaptThresholds'] === true) {
                $this->monitor->adaptThresholds();
            }
            
            // Atualizar status do job
            $status = $result ? 'completed' : 'failed';
            
            $sql = "UPDATE scheduled_jobs 
                    SET status = :status, 
                        completed_at = NOW(), 
                        result = :result 
                    WHERE id = :job_id";
            
            $params = [
                ':job_id' => $job['id'],
                ':status' => $status,
                ':result' => json_encode(['success' => $result])
            ];
            
            $this->db->execute($sql, $params);
            
            // Reagendar para o próximo intervalo se for um job recorrente
            if (isset($parameters['interval']) && $parameters['interval'] > 0) {
                $componentName = $parameters['component'] ?? 'System';
                $this->scheduleComponentMonitoring($componentName, $parameters['interval']);
            }
            
            // Registrar resultado
            $results[] = [
                'job_id' => $job['id'],
                'component' => $parameters['component'] ?? 'System',
                'success' => $result
            ];
        }
        
        return [
            'status' => 'success', 
            'message' => count($results) . ' job(s) executado(s).', 
            'details' => $results
        ];
    }
    
    /**
     * Instala as tabelas de banco de dados necessárias para o sistema de monitoramento
     * 
     * @return bool Sucesso da operação
     */
    public function installMonitoringTables() {
        try {
            $queries = [
                // Tabela para ciclos de monitoramento
                "CREATE TABLE IF NOT EXISTS monitoring_cycles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    start_time DATETIME NOT NULL,
                    end_time DATETIME NULL,
                    status ENUM('running', 'completed', 'failed') NOT NULL DEFAULT 'running',
                    anomalies_detected INT NOT NULL DEFAULT 0,
                    INDEX idx_status (status),
                    INDEX idx_start_time (start_time)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Tabela para métricas de performance
                "CREATE TABLE IF NOT EXISTS performance_metrics (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    cycle_id INT NULL,
                    component VARCHAR(100) NOT NULL,
                    metric_name VARCHAR(100) NOT NULL,
                    metric_value FLOAT NOT NULL,
                    timestamp DATETIME NOT NULL,
                    INDEX idx_component (component),
                    INDEX idx_metric (metric_name),
                    INDEX idx_timestamp (timestamp),
                    INDEX idx_cycle (cycle_id),
                    FOREIGN KEY (cycle_id) REFERENCES monitoring_cycles(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Tabela para anomalias detectadas
                "CREATE TABLE IF NOT EXISTS detected_anomalies (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    metric_name VARCHAR(100) NOT NULL,
                    metric_value FLOAT NOT NULL,
                    component VARCHAR(100) NOT NULL,
                    threshold FLOAT NOT NULL,
                    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
                    detection_method VARCHAR(100) NOT NULL,
                    additional_data TEXT NULL,
                    detected_at DATETIME NOT NULL,
                    resolved BOOLEAN NOT NULL DEFAULT FALSE,
                    resolved_at DATETIME NULL,
                    resolution_notes TEXT NULL,
                    INDEX idx_metric (metric_name),
                    INDEX idx_component (component),
                    INDEX idx_severity (severity),
                    INDEX idx_detected_at (detected_at),
                    INDEX idx_resolved (resolved)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Tabela para componentes monitorados
                "CREATE TABLE IF NOT EXISTS monitored_components (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    component_name VARCHAR(100) NOT NULL,
                    description VARCHAR(255) NULL,
                    check_interval INT NOT NULL DEFAULT 300,
                    active BOOLEAN NOT NULL DEFAULT TRUE,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    UNIQUE KEY idx_component_name (component_name),
                    INDEX idx_active (active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Tabela para métricas monitoradas por componente
                "CREATE TABLE IF NOT EXISTS monitored_metrics (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    component_name VARCHAR(100) NOT NULL,
                    metric_name VARCHAR(100) NOT NULL,
                    collection_method VARCHAR(100) NOT NULL,
                    parameters TEXT NULL,
                    description VARCHAR(255) NULL,
                    active BOOLEAN NOT NULL DEFAULT TRUE,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    UNIQUE KEY idx_component_metric (component_name, metric_name),
                    INDEX idx_active (active),
                    FOREIGN KEY (component_name) REFERENCES monitored_components(component_name) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Tabela para jobs agendados
                "CREATE TABLE IF NOT EXISTS scheduled_jobs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    job_type VARCHAR(100) NOT NULL,
                    parameters TEXT NULL,
                    status ENUM('pending', 'running', 'completed', 'failed') NOT NULL,
                    scheduled_at DATETIME NOT NULL,
                    started_at DATETIME NULL,
                    completed_at DATETIME NULL,
                    result TEXT NULL,
                    INDEX idx_job_type (job_type),
                    INDEX idx_status (status),
                    INDEX idx_scheduled_at (scheduled_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Tabela para log de consultas
                "CREATE TABLE IF NOT EXISTS query_performance_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    query VARCHAR(1000) NOT NULL,
                    query_time FLOAT NOT NULL,
                    rows_affected INT NOT NULL DEFAULT 0,
                    timestamp DATETIME NOT NULL,
                    INDEX idx_query_time (query_time),
                    INDEX idx_timestamp (timestamp)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                
                // Tabela para log de requisições
                "CREATE TABLE IF NOT EXISTS request_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    endpoint VARCHAR(255) NOT NULL,
                    method VARCHAR(10) NOT NULL,
                    status_code INT NOT NULL,
                    response_time FLOAT NOT NULL,
                    timestamp DATETIME NOT NULL,
                    INDEX idx_endpoint (endpoint),
                    INDEX idx_status_code (status_code),
                    INDEX idx_timestamp (timestamp)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ];
            
            // Executar queries como transação
            $this->db->beginTransaction();
            
            foreach ($queries as $query) {
                $this->db->execute($query);
            }
            
            $this->db->commit();
            
            // Registrar componentes e métricas padrão
            $this->registerDefaultComponentsAndMetrics();
            
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Erro ao instalar tabelas de monitoramento: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra componentes e métricas padrão para monitoramento
     * 
     * @return bool Sucesso da operação
     */
    private function registerDefaultComponentsAndMetrics() {
        try {
            // Definir componentes padrão
            $components = [
                [
                    'name' => 'System',
                    'description' => 'Métricas gerais do sistema',
                    'interval' => 300,
                    'metrics' => [
                        [
                            'name' => 'memory_usage',
                            'method' => 'getMemoryUsage',
                            'description' => 'Uso de memória em MB'
                        ],
                        [
                            'name' => 'disk_usage',
                            'method' => 'getDiskUsage',
                            'description' => 'Percentual de uso de disco'
                        ],
                        [
                            'name' => 'concurrent_users',
                            'method' => 'getConcurrentUsers',
                            'description' => 'Número de usuários conectados simultaneamente'
                        ],
                        [
                            'name' => 'cpu_usage',
                            'method' => 'getCpuUsage',
                            'description' => 'Percentual de uso de CPU'
                        ]
                    ]
                ],
                [
                    'name' => 'Database',
                    'description' => 'Métricas de performance do banco de dados',
                    'interval' => 600,
                    'metrics' => [
                        [
                            'name' => 'db_connection_count',
                            'method' => 'getDatabaseConnectionCount',
                            'description' => 'Número de conexões ativas no banco de dados'
                        ],
                        [
                            'name' => 'avg_query_time',
                            'method' => 'getAverageQueryTime',
                            'parameters' => [5],
                            'description' => 'Tempo médio de execução de consultas em segundos'
                        ],
                        [
                            'name' => 'slow_queries',
                            'method' => 'getSlowQueryCount',
                            'parameters' => [1.0, 60],
                            'description' => 'Número de consultas lentas (>1s) na última hora'
                        ]
                    ]
                ],
                [
                    'name' => 'RequestHandling',
                    'description' => 'Métricas de processamento de requisições HTTP',
                    'interval' => 300,
                    'metrics' => [
                        [
                            'name' => 'response_time',
                            'method' => 'getAverageResponseTime',
                            'description' => 'Tempo médio de resposta em segundos'
                        ],
                        [
                            'name' => 'error_rate',
                            'method' => 'getErrorRate',
                            'description' => 'Percentual de erros nas requisições'
                        ],
                        [
                            'name' => 'request_rate',
                            'method' => 'getRequestRate',
                            'parameters' => [5],
                            'description' => 'Requisições por segundo'
                        ]
                    ]
                ],
                [
                    'name' => 'Caching',
                    'description' => 'Métricas de desempenho de cache',
                    'interval' => 600,
                    'metrics' => [
                        [
                            'name' => 'cache_hit_ratio',
                            'method' => 'getCacheHitRatio',
                            'description' => 'Taxa de acertos de cache em percentual'
                        ]
                    ]
                ],
                [
                    'name' => 'PrintQueue',
                    'description' => 'Métricas do sistema de fila de impressão',
                    'interval' => 900,
                    'metrics' => [
                        [
                            'name' => 'active_queues',
                            'method' => 'getActivePrintQueues',
                            'description' => 'Número de filas de impressão ativas'
                        ],
                        [
                            'name' => 'avg_queue_time',
                            'method' => 'getAveragePrintQueueTime',
                            'parameters' => [7],
                            'description' => 'Tempo médio de processamento na fila em horas'
                        ]
                    ]
                ],
                [
                    'name' => 'Sales',
                    'description' => 'Métricas de vendas e conversão',
                    'interval' => 3600,
                    'metrics' => [
                        [
                            'name' => 'conversion_rate',
                            'method' => 'getSalesConversionRate',
                            'parameters' => [7],
                            'description' => 'Taxa de conversão de vendas em percentual'
                        ]
                    ]
                ],
                [
                    'name' => 'ExternalServices',
                    'description' => 'Métricas de integração com serviços externos',
                    'interval' => 1800,
                    'metrics' => [
                        [
                            'name' => 'api_latency',
                            'method' => 'getExternalApiLatency',
                            'parameters' => [24],
                            'description' => 'Latência média de APIs externas em segundos'
                        ]
                    ]
                ]
            ];
            
            // Registrar componentes e métricas
            foreach ($components as $component) {
                $metrics = $component['metrics'];
                unset($component['metrics']);
                
                $this->monitor->registerComponent(
                    $component['name'],
                    $component['description'],
                    $metrics
                );
                
                if (isset($component['interval'])) {
                    $this->monitor->setCheckInterval($component['name'], $component['interval']);
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Erro ao registrar componentes padrão: ' . $e->getMessage());
            return false;
        }
    }
}
