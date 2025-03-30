<?php
/**
 * IntegrationLogModel - Modelo para registro e monitoramento de eventos de integração
 * 
 * Esta classe é responsável por registrar e consultar eventos de integração
 * entre o sistema de pedidos e a fila de impressão 3D, permitindo
 * diagnósticos e monitoramento de possíveis problemas.
 */
class IntegrationLogModel {
    private $db;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Registra um evento de integração entre o sistema de pedidos e a fila de impressão
     * 
     * @param int $orderId ID do pedido (opcional)
     * @param int $printJobId ID do job de impressão (opcional)
     * @param string $event Descrição do evento
     * @param string $status Status do evento (success, warning, error)
     * @param array $details Detalhes adicionais do evento (será convertido para JSON)
     * @return int|bool ID do log criado ou false em caso de erro
     */
    public function logEvent($orderId = null, $printJobId = null, $event, $status = 'success', $details = []) {
        try {
            // Validar parâmetros
            if (empty($event)) {
                app_log("ERRO: Tentativa de registrar evento de integração sem descrição", 'error');
                return false;
            }
            
            // Preparar dados
            $data = [
                'order_id' => $orderId,
                'print_job_id' => $printJobId,
                'event' => $event,
                'status' => in_array($status, ['success', 'warning', 'error']) ? $status : 'info',
                'details' => !empty($details) ? json_encode($details) : null,
                'created_at' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ];
            
            // Inserir no banco de dados
            $logId = $this->db->insert('integration_logs', $data);
            
            // Registrar no log do sistema se for erro
            if ($status === 'error') {
                $logMessage = "Erro de integração: " . $event;
                if ($orderId) $logMessage .= " (Pedido #$orderId)";
                if ($printJobId) $logMessage .= " (Job #$printJobId)";
                app_log($logMessage, 'error');
            }
            
            return $logId;
        } catch (Exception $e) {
            app_log("ERRO ao registrar evento de integração: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Obtém eventos de integração recentes
     * 
     * @param int $limit Número máximo de eventos a retornar
     * @param string $orderBy Campo para ordenação
     * @param string $orderDirection Direção da ordenação (ASC ou DESC)
     * @return array Lista de eventos de integração
     */
    public function getRecentEvents($limit = 100, $orderBy = 'created_at', $orderDirection = 'DESC') {
        $sql = "SELECT * FROM integration_logs 
                ORDER BY $orderBy $orderDirection 
                LIMIT $limit";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Obtém eventos de integração relacionados a um pedido específico
     * 
     * @param int $orderId ID do pedido
     * @return array Lista de eventos de integração do pedido
     */
    public function getEventsByOrderId($orderId) {
        $sql = "SELECT * FROM integration_logs 
                WHERE order_id = :order_id 
                ORDER BY created_at DESC";
        
        return $this->db->query($sql, ['order_id' => $orderId])->fetchAll();
    }
    
    /**
     * Obtém eventos de integração relacionados a um job de impressão específico
     * 
     * @param int $printJobId ID do job de impressão
     * @return array Lista de eventos de integração do job
     */
    public function getEventsByPrintJobId($printJobId) {
        $sql = "SELECT * FROM integration_logs 
                WHERE print_job_id = :print_job_id 
                ORDER BY created_at DESC";
        
        return $this->db->query($sql, ['print_job_id' => $printJobId])->fetchAll();
    }
    
    /**
     * Obtém eventos de integração por status
     * 
     * @param string $status Status a filtrar (success, warning, error, info)
     * @param int $limit Número máximo de eventos a retornar
     * @return array Lista de eventos de integração com o status especificado
     */
    public function getEventsByStatus($status, $limit = 100) {
        $sql = "SELECT * FROM integration_logs 
                WHERE status = :status 
                ORDER BY created_at DESC 
                LIMIT $limit";
        
        return $this->db->query($sql, ['status' => $status])->fetchAll();
    }
    
    /**
     * Obtém contagem de eventos por status agrupados por tipo
     * 
     * @param int $daysBack Número de dias para olhar para trás
     * @return array Estatísticas de eventos
     */
    public function getEventsStatistics($daysBack = 7) {
        $startDate = date('Y-m-d H:i:s', strtotime("-$daysBack days"));
        
        $sql = "SELECT 
                    event, 
                    status, 
                    COUNT(*) as count 
                FROM integration_logs 
                WHERE created_at >= :start_date
                GROUP BY event, status 
                ORDER BY count DESC";
        
        return $this->db->query($sql, ['start_date' => $startDate])->fetchAll();
    }
    
    /**
     * Encontra jobs de impressão órfãos (sem eventos de conclusão)
     * 
     * @param int $daysBack Dias para olhar para trás
     * @return array Lista de jobs potencialmente órfãos
     */
    public function findOrphanedJobs($daysBack = 7) {
        // Esta consulta precisa ser adaptada ao esquema específico do banco de dados
        // Aqui está uma versão simplificada do conceito
        $startDate = date('Y-m-d H:i:s', strtotime("-$daysBack days"));
        
        $sql = "SELECT pj.* 
                FROM print_jobs pj
                WHERE 
                    pj.created_at >= :start_date 
                    AND pj.status NOT IN ('completed', 'cancelled', 'failed') 
                    AND NOT EXISTS (
                        SELECT 1 FROM integration_logs il 
                        WHERE 
                            il.print_job_id = pj.id 
                            AND il.event LIKE '%concluído%'
                    )
                ORDER BY pj.created_at ASC";
        
        return $this->db->query($sql, ['start_date' => $startDate])->fetchAll();
    }
    
    /**
     * Encontra fluxos de integração incompletos
     * 
     * @param int $daysBack Dias para olhar para trás
     * @return array Lista de fluxos potencialmente incompletos
     */
    public function findIncompleteIntegrationFlows($daysBack = 7) {
        // Esta consulta precisa ser adaptada ao esquema específico do banco de dados
        // Aqui está uma versão simplificada do conceito
        $startDate = date('Y-m-d H:i:s', strtotime("-$daysBack days"));
        
        $sql = "SELECT o.* 
                FROM orders o
                WHERE 
                    o.created_at >= :start_date 
                    AND o.test_order = 0
                    AND o.status NOT IN ('completed', 'cancelled', 'delivered') 
                    AND EXISTS (
                        SELECT 1 FROM order_items oi 
                        WHERE 
                            oi.order_id = o.id 
                            AND (
                                SELECT COUNT(*) FROM print_jobs pj 
                                WHERE pj.order_item_id = oi.id
                            ) = 0
                    )
                ORDER BY o.created_at ASC";
        
        return $this->db->query($sql, ['start_date' => $startDate])->fetchAll();
    }
    
    /**
     * Cria a tabela de logs de integração no banco de dados se não existir
     * 
     * @return bool Sucesso da operação
     */
    public function createTableIfNotExists() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS integration_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NULL,
                print_job_id INT NULL,
                event VARCHAR(255) NOT NULL,
                status ENUM('success', 'warning', 'error', 'info') DEFAULT 'info',
                details TEXT NULL,
                created_at DATETIME NOT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                INDEX idx_order_id (order_id),
                INDEX idx_print_job_id (print_job_id),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->db->exec($sql);
            return true;
        } catch (Exception $e) {
            app_log("ERRO ao criar tabela de logs de integração: " . $e->getMessage(), 'error');
            return false;
        }
    }
}
