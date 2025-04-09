<?php
/**
 * PrintQueueMonitor - Monitoramento específico para o sistema de fila de impressão
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Lib\Monitoring
 * @version    1.0.0
 * @author     Claude
 */

require_once __DIR__ . '/../Database.php';

class PrintQueueMonitor {
    /**
     * Singleton instance
     * 
     * @var PrintQueueMonitor
     */
    private static $instance = null;
    
    /**
     * Conexão com banco de dados
     * 
     * @var Database
     */
    private $db;
    
    /**
     * Estatísticas em cache
     * 
     * @var array
     */
    private $cachedStats = [];
    
    /**
     * Timestamp da última atualização de estatísticas
     * 
     * @var int
     */
    private $lastStatsUpdate = 0;
    
    /**
     * Tempo de vida do cache em segundos (2 minutos)
     * 
     * @var int
     */
    private $cacheTTL = 120;
    
    /**
     * Construtor privado (padrão singleton)
     */
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtém instância única
     * 
     * @return PrintQueueMonitor
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Coleta estatísticas atuais da fila de impressão
     * 
     * @param bool $forceRefresh Forçar atualização do cache
     * @return array Estatísticas da fila
     */
    public function getQueueStats($forceRefresh = false) {
        // Verificar se pode usar cache
        if (!$forceRefresh && $this->lastStatsUpdate > (time() - $this->cacheTTL) && !empty($this->cachedStats)) {
            return $this->cachedStats;
        }
        
        // Estatísticas básicas por status
        $stats = $this->getBasicQueueStats();
        
        // Estatísticas de tempo médio em cada status
        $avgTimeStats = $this->getAverageTimeStats();
        $stats['average_times'] = $avgTimeStats;
        
        // Tendências das últimas 24 horas
        $trendStats = $this->getQueueTrends();
        $stats['trends'] = $trendStats;
        
        // Estatísticas de prioridade
        $priorityStats = $this->getPriorityStats();
        $stats['priority'] = $priorityStats;
        
        // Estatísticas de falhas
        $failureStats = $this->getFailureStats();
        $stats['failures'] = $failureStats;
        
        // Métricas de SLA
        $slaMetrics = $this->getSLAMetrics();
        $stats['sla'] = $slaMetrics;
        
        // Estatísticas por usuário (top 5)
        $userStats = $this->getUserStats();
        $stats['users'] = $userStats;
        
        // Atualizar cache
        $this->cachedStats = $stats;
        $this->lastStatsUpdate = time();
        
        return $stats;
    }
    
    /**
     * Obtém estatísticas básicas da fila
     * 
     * @return array Estatísticas básicas
     */
    private function getBasicQueueStats() {
        try {
            // Contagem por status
            $sql = "SELECT status, COUNT(*) as count FROM print_queue GROUP BY status";
            $statusCounts = $this->db->fetchAll($sql);
            
            $stats = [
                'total' => 0,
                'by_status' => [
                    'pending' => 0,
                    'assigned' => 0,
                    'printing' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                    'failed' => 0
                ]
            ];
            
            // Preencher contagens por status
            foreach ($statusCounts as $row) {
                $stats['by_status'][$row['status']] = (int)$row['count'];
                $stats['total'] += (int)$row['count'];
            }
            
            // Itens adicionados hoje
            $sql = "SELECT COUNT(*) as count FROM print_queue WHERE created_at >= CURDATE()";
            $result = $this->db->fetchSingle($sql);
            $stats['added_today'] = (int)$result['count'];
            
            // Itens concluídos hoje
            $sql = "SELECT COUNT(*) as count FROM print_queue 
                    WHERE status = 'completed' AND updated_at >= CURDATE()";
            $result = $this->db->fetchSingle($sql);
            $stats['completed_today'] = (int)$result['count'];
            
            // Taxa de conclusão
            $stats['completion_rate'] = $stats['total'] > 0 
                ? round(($stats['by_status']['completed'] / $stats['total']) * 100, 2) 
                : 0;
            
            // Taxa de falha
            $stats['failure_rate'] = $stats['total'] > 0 
                ? round(($stats['by_status']['failed'] / $stats['total']) * 100, 2) 
                : 0;
            
            return $stats;
        } catch (Exception $e) {
            error_log('Erro ao obter estatísticas básicas da fila: ' . $e->getMessage());
            return [
                'total' => 0,
                'by_status' => [
                    'pending' => 0,
                    'assigned' => 0,
                    'printing' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                    'failed' => 0
                ],
                'added_today' => 0,
                'completed_today' => 0,
                'completion_rate' => 0,
                'failure_rate' => 0
            ];
        }
    }
    
    /**
     * Obtém tempos médios em cada status
     * 
     * @return array Estatísticas de tempo
     */
    private function getAverageTimeStats() {
        try {
            $timeStats = [
                'pending_to_assigned' => 0,
                'assigned_to_printing' => 0,
                'printing_to_completed' => 0,
                'total_processing' => 0
            ];
            
            // Tempo médio de pendente para atribuído
            $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, t1.created_at, t2.created_at)) as avg_time
                    FROM print_queue_history t1
                    JOIN print_queue_history t2 ON t1.queue_id = t2.queue_id
                    WHERE t1.event_type = 'status_change'
                    AND JSON_EXTRACT(t1.new_value, '$.status') = 'pending'
                    AND t2.event_type = 'status_change'
                    AND JSON_EXTRACT(t2.new_value, '$.status') = 'assigned'
                    AND t1.created_at < t2.created_at
                    AND t2.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
            $result = $this->db->fetchSingle($sql);
            if ($result && $result['avg_time'] !== null) {
                $timeStats['pending_to_assigned'] = round($result['avg_time'], 2);
            }
            
            // Tempo médio de atribuído para impressão
            $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, t1.created_at, t2.created_at)) as avg_time
                    FROM print_queue_history t1
                    JOIN print_queue_history t2 ON t1.queue_id = t2.queue_id
                    WHERE t1.event_type = 'status_change'
                    AND JSON_EXTRACT(t1.new_value, '$.status') = 'assigned'
                    AND t2.event_type = 'status_change'
                    AND JSON_EXTRACT(t2.new_value, '$.status') = 'printing'
                    AND t1.created_at < t2.created_at
                    AND t2.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
            $result = $this->db->fetchSingle($sql);
            if ($result && $result['avg_time'] !== null) {
                $timeStats['assigned_to_printing'] = round($result['avg_time'], 2);
            }
            
            // Tempo médio de impressão para conclusão
            $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, t1.created_at, t2.created_at)) as avg_time
                    FROM print_queue_history t1
                    JOIN print_queue_history t2 ON t1.queue_id = t2.queue_id
                    WHERE t1.event_type = 'status_change'
                    AND JSON_EXTRACT(t1.new_value, '$.status') = 'printing'
                    AND t2.event_type = 'status_change'
                    AND JSON_EXTRACT(t2.new_value, '$.status') = 'completed'
                    AND t1.created_at < t2.created_at
                    AND t2.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
            $result = $this->db->fetchSingle($sql);
            if ($result && $result['avg_time'] !== null) {
                $timeStats['printing_to_completed'] = round($result['avg_time'], 2);
            }
            
            // Tempo médio total (pendente para concluído)
            $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, p.created_at, p.updated_at)) as avg_time
                    FROM print_queue p
                    WHERE p.status = 'completed'
                    AND p.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
            $result = $this->db->fetchSingle($sql);
            if ($result && $result['avg_time'] !== null) {
                $timeStats['total_processing'] = round($result['avg_time'], 2);
            }
            
            return $timeStats;
        } catch (Exception $e) {
            error_log('Erro ao obter estatísticas de tempo da fila: ' . $e->getMessage());
            return [
                'pending_to_assigned' => 0,
                'assigned_to_printing' => 0,
                'printing_to_completed' => 0,
                'total_processing' => 0
            ];
        }
    }
    
    /**
     * Obtém tendências da fila das últimas 24 horas
     * 
     * @return array Dados de tendência
     */
    private function getQueueTrends() {
        try {
            $trends = [
                'additions' => [],
                'completions' => [],
                'failures' => [],
                'hourly_comparison' => []
            ];
            
            // Adições por hora nas últimas 24 horas
            $sql = "SELECT HOUR(created_at) as hour, COUNT(*) as count
                    FROM print_queue
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    GROUP BY HOUR(created_at)
                    ORDER BY hour";
            
            $results = $this->db->fetchAll($sql);
            
            $hourlyData = array_fill(0, 24, 0);
            foreach ($results as $row) {
                $hour = (int)$row['hour'];
                $hourlyData[$hour] = (int)$row['count'];
            }
            
            $trends['additions'] = $hourlyData;
            
            // Conclusões por hora nas últimas 24 horas
            $sql = "SELECT HOUR(updated_at) as hour, COUNT(*) as count
                    FROM print_queue
                    WHERE status = 'completed' 
                    AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    GROUP BY HOUR(updated_at)
                    ORDER BY hour";
            
            $results = $this->db->fetchAll($sql);
            
            $hourlyData = array_fill(0, 24, 0);
            foreach ($results as $row) {
                $hour = (int)$row['hour'];
                $hourlyData[$hour] = (int)$row['count'];
            }
            
            $trends['completions'] = $hourlyData;
            
            // Falhas por hora nas últimas 24 horas
            $sql = "SELECT HOUR(updated_at) as hour, COUNT(*) as count
                    FROM print_queue
                    WHERE status = 'failed' 
                    AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    GROUP BY HOUR(updated_at)
                    ORDER BY hour";
            
            $results = $this->db->fetchAll($sql);
            
            $hourlyData = array_fill(0, 24, 0);
            foreach ($results as $row) {
                $hour = (int)$row['hour'];
                $hourlyData[$hour] = (int)$row['count'];
            }
            
            $trends['failures'] = $hourlyData;
            
            // Comparação com 24 horas anteriores
            $sql = "SELECT 
                    (SELECT COUNT(*) FROM print_queue 
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as current_additions,
                    (SELECT COUNT(*) FROM print_queue 
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR) 
                     AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)) as previous_additions,
                    (SELECT COUNT(*) FROM print_queue 
                     WHERE status = 'completed' AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as current_completions,
                    (SELECT COUNT(*) FROM print_queue 
                     WHERE status = 'completed' AND updated_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR) 
                     AND updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)) as previous_completions,
                    (SELECT COUNT(*) FROM print_queue 
                     WHERE status = 'failed' AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as current_failures,
                    (SELECT COUNT(*) FROM print_queue 
                     WHERE status = 'failed' AND updated_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR) 
                     AND updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)) as previous_failures";
            
            $result = $this->db->fetchSingle($sql);
            
            if ($result) {
                $trends['hourly_comparison'] = [
                    'additions' => [
                        'current' => (int)$result['current_additions'],
                        'previous' => (int)$result['previous_additions'],
                        'change_percent' => $result['previous_additions'] > 0 
                            ? round((($result['current_additions'] - $result['previous_additions']) / $result['previous_additions']) * 100, 2)
                            : 0
                    ],
                    'completions' => [
                        'current' => (int)$result['current_completions'],
                        'previous' => (int)$result['previous_completions'],
                        'change_percent' => $result['previous_completions'] > 0 
                            ? round((($result['current_completions'] - $result['previous_completions']) / $result['previous_completions']) * 100, 2)
                            : 0
                    ],
                    'failures' => [
                        'current' => (int)$result['current_failures'],
                        'previous' => (int)$result['previous_failures'],
                        'change_percent' => $result['previous_failures'] > 0 
                            ? round((($result['current_failures'] - $result['previous_failures']) / $result['previous_failures']) * 100, 2)
                            : 0
                    ]
                ];
            }
            
            return $trends;
        } catch (Exception $e) {
            error_log('Erro ao obter tendências da fila: ' . $e->getMessage());
            return [
                'additions' => array_fill(0, 24, 0),
                'completions' => array_fill(0, 24, 0),
                'failures' => array_fill(0, 24, 0),
                'hourly_comparison' => []
            ];
        }
    }
    
    /**
     * Obtém estatísticas de prioridade da fila
     * 
     * @return array Estatísticas de prioridade
     */
    private function getPriorityStats() {
        try {
            $priorityStats = [
                'distribution' => [],
                'avg_priority' => 0,
                'high_priority_items' => 0
            ];
            
            // Distribuição de prioridades
            $sql = "SELECT priority, COUNT(*) as count 
                    FROM print_queue 
                    WHERE status IN ('pending', 'assigned', 'printing')
                    GROUP BY priority 
                    ORDER BY priority";
            
            $results = $this->db->fetchAll($sql);
            
            $distribution = [];
            foreach ($results as $row) {
                $distribution[(int)$row['priority']] = (int)$row['count'];
            }
            
            $priorityStats['distribution'] = $distribution;
            
            // Prioridade média
            $sql = "SELECT AVG(priority) as avg_priority 
                    FROM print_queue 
                    WHERE status IN ('pending', 'assigned', 'printing')";
            
            $result = $this->db->fetchSingle($sql);
            if ($result && $result['avg_priority'] !== null) {
                $priorityStats['avg_priority'] = round($result['avg_priority'], 2);
            }
            
            // Itens de alta prioridade (8-10)
            $sql = "SELECT COUNT(*) as count 
                    FROM print_queue 
                    WHERE priority >= 8 
                    AND status IN ('pending', 'assigned', 'printing')";
            
            $result = $this->db->fetchSingle($sql);
            if ($result) {
                $priorityStats['high_priority_items'] = (int)$result['count'];
            }
            
            return $priorityStats;
        } catch (Exception $e) {
            error_log('Erro ao obter estatísticas de prioridade: ' . $e->getMessage());
            return [
                'distribution' => [],
                'avg_priority' => 0,
                'high_priority_items' => 0
            ];
        }
    }
    
    /**
     * Obtém estatísticas de falhas
     * 
     * @return array Estatísticas de falhas
     */
    private function getFailureStats() {
        try {
            $failureStats = [
                'count_last_30_days' => 0,
                'common_causes' => [],
                'retry_success_rate' => 0
            ];
            
            // Falhas nos últimos 30 dias
            $sql = "SELECT COUNT(*) as count 
                    FROM print_queue 
                    WHERE status = 'failed' 
                    AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
            $result = $this->db->fetchSingle($sql);
            if ($result) {
                $failureStats['count_last_30_days'] = (int)$result['count'];
            }
            
            // Causas comuns de falha
            $sql = "SELECT SUBSTRING_INDEX(notes, ':', 1) as cause, COUNT(*) as count 
                    FROM print_queue 
                    WHERE status = 'failed' 
                    AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY cause 
                    ORDER BY count DESC 
                    LIMIT 5";
            
            $results = $this->db->fetchAll($sql);
            
            $causes = [];
            foreach ($results as $row) {
                $causes[$row['cause']] = (int)$row['count'];
            }
            
            $failureStats['common_causes'] = $causes;
            
            // Taxa de sucesso em reenvios
            $sql = "SELECT 
                    COUNT(DISTINCT q1.id) as failed_count,
                    SUM(CASE WHEN q2.status = 'completed' THEN 1 ELSE 0 END) as success_count
                    FROM print_queue q1
                    LEFT JOIN print_queue q2 ON q1.model_id = q2.model_id AND q1.id < q2.id
                    WHERE q1.status = 'failed'
                    AND q1.updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
            $result = $this->db->fetchSingle($sql);
            if ($result && $result['failed_count'] > 0) {
                $failureStats['retry_success_rate'] = round(($result['success_count'] / $result['failed_count']) * 100, 2);
            }
            
            return $failureStats;
        } catch (Exception $e) {
            error_log('Erro ao obter estatísticas de falhas: ' . $e->getMessage());
            return [
                'count_last_30_days' => 0,
                'common_causes' => [],
                'retry_success_rate' => 0
            ];
        }
    }
    
    /**
     * Obtém métricas de SLA
     * 
     * @return array Métricas de SLA
     */
    private function getSLAMetrics() {
        try {
            $slaMetrics = [
                'avg_waiting_time' => 0,
                'avg_completion_time' => 0,
                'sla_compliance' => 0,
                'overdue_items' => 0
            ];
            
            // Tempo médio de espera
            $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_time
                    FROM print_queue
                    WHERE status = 'assigned' 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
            $result = $this->db->fetchSingle($sql);
            if ($result && $result['avg_time'] !== null) {
                $slaMetrics['avg_waiting_time'] = round($result['avg_time'], 2);
            }
            
            // Tempo médio para conclusão
            $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_time
                    FROM print_queue
                    WHERE status = 'completed' 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
            $result = $this->db->fetchSingle($sql);
            if ($result && $result['avg_time'] !== null) {
                $slaMetrics['avg_completion_time'] = round($result['avg_time'], 2);
            }
            
            // SLA padrão (48 horas para conclusão)
            $slaHours = 48;
            
            // Cálculo de conformidade com SLA
            $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN TIMESTAMPDIFF(HOUR, created_at, updated_at) <= {$slaHours} THEN 1 ELSE 0 END) as within_sla
                    FROM print_queue
                    WHERE status = 'completed' 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            
            $result = $this->db->fetchSingle($sql);
            if ($result && $result['total'] > 0) {
                $slaMetrics['sla_compliance'] = round(($result['within_sla'] / $result['total']) * 100, 2);
            }
            
            // Itens em atraso (pendentes há mais de 48 horas)
            $sql = "SELECT COUNT(*) as count
                    FROM print_queue
                    WHERE status IN ('pending', 'assigned') 
                    AND TIMESTAMPDIFF(HOUR, created_at, NOW()) > {$slaHours}";
            
            $result = $this->db->fetchSingle($sql);
            if ($result) {
                $slaMetrics['overdue_items'] = (int)$result['count'];
            }
            
            return $slaMetrics;
        } catch (Exception $e) {
            error_log('Erro ao obter métricas de SLA: ' . $e->getMessage());
            return [
                'avg_waiting_time' => 0,
                'avg_completion_time' => 0,
                'sla_compliance' => 0,
                'overdue_items' => 0
            ];
        }
    }
    
    /**
     * Obtém estatísticas por usuário
     * 
     * @return array Estatísticas por usuário
     */
    private function getUserStats() {
        try {
            $userStats = [
                'top_submitters' => [],
                'completion_by_user' => []
            ];
            
            // Top 5 usuários por submissões
            $sql = "SELECT u.name, COUNT(pq.id) as count
                    FROM print_queue pq
                    JOIN users u ON pq.user_id = u.id
                    WHERE pq.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY pq.user_id
                    ORDER BY count DESC
                    LIMIT 5";
            
            $results = $this->db->fetchAll($sql);
            
            $topSubmitters = [];
            foreach ($results as $row) {
                $topSubmitters[$row['name']] = (int)$row['count'];
            }
            
            $userStats['top_submitters'] = $topSubmitters;
            
            // Taxa de conclusão por usuário (top 5)
            $sql = "SELECT 
                    u.name,
                    COUNT(pq.id) as total,
                    SUM(CASE WHEN pq.status = 'completed' THEN 1 ELSE 0 END) as completed
                    FROM print_queue pq
                    JOIN users u ON pq.user_id = u.id
                    WHERE pq.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY pq.user_id
                    HAVING total >= 5
                    ORDER BY (completed / total) DESC
                    LIMIT 5";
            
            $results = $this->db->fetchAll($sql);
            
            $completionRates = [];
            foreach ($results as $row) {
                $rate = $row['total'] > 0 ? round(($row['completed'] / $row['total']) * 100, 2) : 0;
                $completionRates[$row['name']] = [
                    'total' => (int)$row['total'],
                    'completed' => (int)$row['completed'],
                    'rate' => $rate
                ];
            }
            
            $userStats['completion_by_user'] = $completionRates;
            
            return $userStats;
        } catch (Exception $e) {
            error_log('Erro ao obter estatísticas por usuário: ' . $e->getMessage());
            return [
                'top_submitters' => [],
                'completion_by_user' => []
            ];
        }
    }
    
    /**
     * Obtém dados para o painel de monitoramento em tempo real
     * 
     * @return array Dados do painel
     */
    public function getDashboardData() {
        // Obter estatísticas atuais
        $stats = $this->getQueueStats();
        
        // Formatação para o dashboard
        $dashboardData = [
            'summary' => [
                'total_items' => $stats['total'],
                'items_pending' => $stats['by_status']['pending'],
                'items_printing' => $stats['by_status']['printing'],
                'completed_today' => $stats['completed_today'],
                'completion_rate' => $stats['completion_rate'] . '%',
                'failure_rate' => $stats['failure_rate'] . '%'
            ],
            'waiting_times' => [
                'avg_waiting' => $stats['average_times']['pending_to_assigned'],
                'avg_printing' => $stats['average_times']['printing_to_completed'],
                'avg_total' => $stats['average_times']['total_processing']
            ],
            'trends' => [
                'additions' => $stats['trends']['additions'],
                'completions' => $stats['trends']['completions'],
                'additions_change' => $stats['trends']['hourly_comparison']['additions']['change_percent'] ?? 0,
                'completions_change' => $stats['trends']['hourly_comparison']['completions']['change_percent'] ?? 0
            ],
            'priorities' => [
                'high_priority' => $stats['priority']['high_priority_items'],
                'avg_priority' => $stats['priority']['avg_priority'],
                'distribution' => $stats['priority']['distribution']
            ],
            'sla' => [
                'compliance' => $stats['sla']['sla_compliance'] . '%',
                'overdue' => $stats['sla']['overdue_items']
            ],
            'failures' => [
                'count' => $stats['failures']['count_last_30_days'],
                'retry_success' => $stats['failures']['retry_success_rate'] . '%',
                'common_causes' => $stats['failures']['common_causes']
            ],
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
        return $dashboardData;
    }
    
    /**
     * Obtém alertas ativos com base nas métricas atuais
     * 
     * @return array Lista de alertas
     */
    public function getAlerts() {
        // Obter estatísticas atuais
        $stats = $this->getQueueStats();
        
        $alerts = [];
        
        // Alerta para itens com espera longa
        if ($stats['sla']['overdue_items'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$stats['sla']['overdue_items']} itens estão aguardando há mais de 48 horas",
                'details' => "Verifique a fila de itens pendentes para priorizar os mais antigos."
            ];
        }
        
        // Alerta para alta taxa de falha
        if ($stats['failure_rate'] > 15) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "Taxa de falha alta: {$stats['failure_rate']}%",
                'details' => "Verificar problemas recorrentes nas impressoras."
            ];
        }
        
        // Alerta para tempo médio de processamento alto
        if ($stats['average_times']['total_processing'] > 72) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Tempo médio de processamento muito alto: {$stats['average_times']['total_processing']} horas",
                'details' => "Considere otimizar fluxos de trabalho ou aumentar capacidade."
            ];
        }
        
        // Alerta para muitos itens de alta prioridade
        if ($stats['priority']['high_priority_items'] > 5) {
            $alerts[] = [
                'type' => 'info',
                'message' => "{$stats['priority']['high_priority_items']} itens de alta prioridade aguardando",
                'details' => "Atenção especial necessária para itens prioritários."
            ];
        }
        
        // Alerta para baixa conformidade com SLA
        if ($stats['sla']['sla_compliance'] < 80) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "Baixa conformidade com SLA: {$stats['sla']['sla_compliance']}%",
                'details' => "Analisar processos e aumentar eficiência."
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Registra evento de monitoramento
     * 
     * @param string $event Tipo de evento
     * @param array $data Dados do evento
     * @return bool Sucesso do registro
     */
    public function logMonitoringEvent($event, $data) {
        try {
            $sql = "INSERT INTO print_queue_monitoring (event_type, event_data, created_at) 
                    VALUES (:event_type, :event_data, NOW())";
            
            $params = [
                ':event_type' => $event,
                ':event_data' => json_encode($data)
            ];
            
            $this->db->execute($sql, $params);
            return true;
        } catch (Exception $e) {
            error_log('Erro ao registrar evento de monitoramento: ' . $e->getMessage());
            return false;
        }
    }
}
