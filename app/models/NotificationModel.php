<?php
/**
 * NotificationModel - Modelo para gerenciamento de notificações
 * 
 * Gerencia armazenamento, recuperação e estatísticas para o sistema de notificações.
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Models
 * @version    1.0.0
 * @author     Claude
 */
class NotificationModel extends Model {
    /**
     * Construtor
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Obtém notificações administrativas com paginação e filtragem
     * 
     * @param int $page Número da página
     * @param int $limit Limite de itens por página
     * @param string $type Tipo de notificação (all, info, success, warning, error)
     * @return array Lista de notificações
     */
    public function getAdminNotifications($page = 1, $limit = 20, $type = 'all') {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT n.*, 
                (SELECT COUNT(*) FROM notification_deliveries WHERE notification_id = n.id) as delivery_count,
                (SELECT COUNT(*) FROM notification_deliveries WHERE notification_id = n.id AND status = 'delivered') as delivered_count,
                JSON_OBJECT('total', 
                            (SELECT COUNT(*) FROM notification_targets WHERE notification_id = n.id),
                            'delivered', 
                            (SELECT COUNT(*) FROM notification_deliveries WHERE notification_id = n.id AND status = 'delivered')) as delivery_stats
                FROM notifications n
                WHERE n.is_system = 1";
        
        $params = [];
        
        if ($type !== 'all') {
            $sql .= " AND n.type = :type";
            $params[':type'] = $type;
        }
        
        $sql .= " ORDER BY n.created_at DESC
                 LIMIT :limit OFFSET :offset";
        
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Obtém o número total de notificações administrativas
     * 
     * @param string $type Tipo de notificação (all, info, success, warning, error)
     * @return int Número total de notificações
     */
    public function getTotalAdminNotifications($type = 'all') {
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE is_system = 1";
        $params = [];
        
        if ($type !== 'all') {
            $sql .= " AND type = :type";
            $params[':type'] = $type;
        }
        
        $result = $this->db->query($sql, $params);
        
        return isset($result[0]['count']) ? (int)$result[0]['count'] : 0;
    }
    
    /**
     * Obtém detalhes de uma notificação específica
     * 
     * @param int $notificationId ID da notificação
     * @return array|null Detalhes da notificação ou null se não encontrada
     */
    public function getNotificationDetails($notificationId) {
        $sql = "SELECT n.*,
                (SELECT COUNT(*) FROM notification_targets WHERE notification_id = n.id) as target_count,
                (SELECT COUNT(*) FROM notification_deliveries WHERE notification_id = n.id) as delivery_count,
                (SELECT COUNT(*) FROM notification_deliveries WHERE notification_id = n.id AND status = 'delivered') as delivered_count,
                (SELECT COUNT(*) FROM notification_deliveries WHERE notification_id = n.id AND status = 'failed') as failed_count,
                JSON_OBJECT('total', 
                            (SELECT COUNT(*) FROM notification_targets WHERE notification_id = n.id),
                            'delivered', 
                            (SELECT COUNT(*) FROM notification_deliveries WHERE notification_id = n.id AND status = 'delivered'),
                            'failed',
                            (SELECT COUNT(*) FROM notification_deliveries WHERE notification_id = n.id AND status = 'failed')) as delivery_stats,
                (SELECT JSON_ARRAYAGG(role) FROM notification_targets WHERE notification_id = n.id AND role IS NOT NULL) as target_roles
                FROM notifications n
                WHERE n.id = :notification_id";
        
        $params = [':notification_id' => $notificationId];
        
        $result = $this->db->query($sql, $params);
        
        return isset($result[0]) ? $result[0] : null;
    }
    
    /**
     * Cria uma nova notificação
     * 
     * @param string $title Título da notificação
     * @param string $message Corpo da notificação
     * @param string $type Tipo da notificação (info, success, warning, error)
     * @param array $userRoles Papéis de usuário para envio
     * @param bool $isSystem Se é uma notificação de sistema
     * @param int $createdBy ID do usuário que criou a notificação (0 para sistema)
     * @return int|bool ID da notificação ou false em caso de erro
     */
    public function createNotification($title, $message, $type, $userRoles, $isSystem = true, $createdBy = 0) {
        try {
            // Iniciar transação para garantir integridade dos dados
            $this->db->beginTransaction();
            
            // Verificar parâmetros básicos
            if (empty($title) || empty($message) || empty($userRoles)) {
                throw new Exception("Parâmetros inválidos para criação de notificação");
            }
            
            // Validar tipo de notificação
            $validTypes = ['info', 'success', 'warning', 'error'];
            if (!in_array($type, $validTypes)) {
                $type = 'info'; // Valor padrão seguro
            }
            
            // Criar a notificação
            $sql = "INSERT INTO notifications 
                    (title, message, type, is_system, created_by, status, created_at, updated_at) 
                    VALUES 
                    (:title, :message, :type, :is_system, :created_by, 'pending', NOW(), NOW())";
            
            $params = [
                ':title' => $title,
                ':message' => $message,
                ':type' => $type,
                ':is_system' => $isSystem ? 1 : 0,
                ':created_by' => $createdBy
            ];
            
            $this->db->execute($sql, $params);
            $notificationId = $this->db->lastInsertId();
            
            if (!$notificationId) {
                throw new Exception("Falha ao criar notificação no banco de dados");
            }
            
            // Adicionar alvos (roles)
            foreach ($userRoles as $role) {
                $sql = "INSERT INTO notification_targets 
                        (notification_id, role, created_at) 
                        VALUES 
                        (:notification_id, :role, NOW())";
                
                $params = [
                    ':notification_id' => $notificationId,
                    ':role' => $role
                ];
                
                $this->db->execute($sql, $params);
            }
            
            // Obter total de usuários para alvos
            $placeholders = implode(',', array_fill(0, count($userRoles), '?'));
            $sql = "SELECT COUNT(*) as total FROM users WHERE role IN ($placeholders)";
            
            $result = $this->db->query($sql, $userRoles);
            $totalTargets = isset($result[0]['total']) ? (int)$result[0]['total'] : 0;
            
            // Atualizar estatísticas na notificação
            $sql = "UPDATE notifications 
                    SET target_count = :target_count, 
                        updated_at = NOW() 
                    WHERE id = :notification_id";
            
            $params = [
                ':target_count' => $totalTargets,
                ':notification_id' => $notificationId
            ];
            
            $this->db->execute($sql, $params);
            
            // Confirmar transação
            $this->db->commit();
            
            return $notificationId;
        } catch (Exception $e) {
            // Reverter em caso de erro
            $this->db->rollBack();
            error_log('Erro ao criar notificação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza o status de processamento de uma notificação
     * 
     * @param int $notificationId ID da notificação
     * @param string $status Novo status (pending, in_progress, completed, failed)
     * @param array $stats Estatísticas de entrega [optional]
     * @return bool Sucesso da operação
     */
    public function updateNotificationStatus($notificationId, $status, $stats = null) {
        try {
            $sql = "UPDATE notifications 
                    SET status = :status, 
                        updated_at = NOW()";
            
            $params = [
                ':status' => $status,
                ':notification_id' => $notificationId
            ];
            
            // Adicionar estatísticas se fornecidas
            if ($stats !== null) {
                $sql .= ", delivered_count = :delivered_count, 
                          failed_count = :failed_count";
                
                $params[':delivered_count'] = $stats['delivered'] ?? 0;
                $params[':failed_count'] = $stats['failed'] ?? 0;
            }
            
            $sql .= " WHERE id = :notification_id";
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao atualizar status da notificação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra uma tentativa de entrega
     * 
     * @param int $notificationId ID da notificação
     * @param int $userId ID do usuário
     * @param string $channel Canal de entrega (database, push, email)
     * @param string $status Status da entrega (delivered, failed)
     * @param string $details Detalhes adicionais [optional]
     * @return bool Sucesso da operação
     */
    public function logDeliveryAttempt($notificationId, $userId, $channel, $status, $details = null) {
        try {
            $sql = "INSERT INTO notification_deliveries 
                    (notification_id, user_id, channel, status, details, timestamp) 
                    VALUES 
                    (:notification_id, :user_id, :channel, :status, :details, NOW())";
            
            $params = [
                ':notification_id' => $notificationId,
                ':user_id' => $userId,
                ':channel' => $channel,
                ':status' => $status,
                ':details' => $details ? json_encode($details) : null
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao registrar tentativa de entrega: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marca uma notificação como lida por um usuário
     * 
     * @param int $notificationId ID da notificação
     * @param int $userId ID do usuário
     * @return bool Sucesso da operação
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $sql = "UPDATE user_notifications 
                    SET status = 'read', 
                        read_at = NOW() 
                    WHERE notification_id = :notification_id 
                    AND user_id = :user_id";
            
            $params = [
                ':notification_id' => $notificationId,
                ':user_id' => $userId
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao marcar notificação como lida: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Prepara uma notificação para reenvio para destinatários com falhas
     * 
     * @param int $notificationId ID da notificação
     * @return bool Sucesso da operação
     */
    public function prepareForResend($notificationId) {
        try {
            // Iniciar transação
            $this->db->beginTransaction();
            
            // Verificar se a notificação existe e não está já concluída
            $sql = "SELECT status FROM notifications WHERE id = :notification_id";
            $params = [':notification_id' => $notificationId];
            
            $result = $this->db->query($sql, $params);
            
            if (empty($result) || $result[0]['status'] === 'completed') {
                // Notificação não existe ou já está concluída
                return false;
            }
            
            // Atualizar status para pendente
            $sql = "UPDATE notifications 
                    SET status = 'pending', 
                        updated_at = NOW() 
                    WHERE id = :notification_id";
            
            $this->db->execute($sql, $params);
            
            // Confirmar transação
            $this->db->commit();
            
            return true;
        } catch (Exception $e) {
            // Reverter em caso de erro
            $this->db->rollBack();
            error_log('Erro ao preparar notificação para reenvio: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém estatísticas de notificações
     * 
     * @return array Estatísticas de notificações
     */
    public function getNotificationStats() {
        try {
            $stats = [
                'total' => 0,
                'byType' => [],
                'deliveryRate' => 0,
                'recentActivity' => []
            ];
            
            // Total de notificações
            $sql = "SELECT COUNT(*) as count FROM notifications";
            $result = $this->db->query($sql);
            $stats['total'] = isset($result[0]['count']) ? (int)$result[0]['count'] : 0;
            
            // Notificações por tipo
            $sql = "SELECT type, COUNT(*) as count FROM notifications GROUP BY type";
            $results = $this->db->query($sql);
            
            foreach ($results as $row) {
                $stats['byType'][$row['type']] = (int)$row['count'];
            }
            
            // Taxa de entrega
            $sql = "SELECT 
                    (SELECT COUNT(*) FROM notification_deliveries WHERE status = 'delivered') as delivered,
                    (SELECT COUNT(*) FROM notification_deliveries) as total";
            
            $result = $this->db->query($sql);
            
            if (isset($result[0]['total']) && $result[0]['total'] > 0) {
                $stats['deliveryRate'] = round(($result[0]['delivered'] / $result[0]['total']) * 100, 2);
            }
            
            // Atividade recente
            $sql = "SELECT 
                    DATE(created_at) as date, 
                    COUNT(*) as count 
                    FROM notifications 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date DESC";
            
            $results = $this->db->query($sql);
            
            foreach ($results as $row) {
                $stats['recentActivity'][$row['date']] = (int)$row['count'];
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log('Erro ao obter estatísticas de notificações: ' . $e->getMessage());
            return [
                'total' => 0,
                'byType' => [],
                'deliveryRate' => 0,
                'recentActivity' => []
            ];
        }
    }
}
