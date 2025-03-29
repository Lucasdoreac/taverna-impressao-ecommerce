<?php
/**
 * NotificationModel - Modelo para gerenciamento de notificações
 * 
 * Este modelo gerencia as notificações para clientes e administradores,
 * especialmente relacionadas ao sistema de impressão 3D.
 * 
 * Características principais:
 * - Gerenciamento completo de notificações (criação, leitura, exclusão)
 * - Filtros avançados para administradores
 * - Notificações automáticas baseadas em eventos do sistema
 * - Integração com sistema de fila de impressão 3D
 * - Controle de configuração via SettingsModel
 * 
 * @version 1.1.0
 * @author Taverna da Impressão
 */
class NotificationModel {
    private $db;
    private $cache = []; // Cache para otimização de consultas repetidas
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Cria uma nova notificação
     * 
     * @param array $data Dados da notificação (user_id, order_id, queue_item_id, type, title, message, created_by, status)
     * @return int ID da notificação criada ou false em caso de erro
     */
    public function create($data) {
        try {
            // Validação básica
            if (empty($data['user_id']) || empty($data['title']) || empty($data['message'])) {
                app_log('ERROR', 'Dados insuficientes para criar notificação');
                return false;
            }
            
            $sql = "INSERT INTO notifications (
                user_id, order_id, queue_item_id, type, title, message, created_by, status
            ) VALUES (
                :user_id, :order_id, :queue_item_id, :type, :title, :message, :created_by, :status
            )";
            
            $params = [
                'user_id' => $data['user_id'],
                'order_id' => $data['order_id'] ?? null,
                'queue_item_id' => $data['queue_item_id'] ?? null,
                'type' => $data['type'],
                'title' => $data['title'],
                'message' => $data['message'],
                'created_by' => $data['created_by'],
                'status' => $data['status'] ?? 'unread'
            ];
            
            $result = $this->db->insert($sql, $params);
            
            // Limpar cache após inserção
            $this->clearCache($data['user_id']);
            
            return $result;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao criar notificação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marca uma notificação como lida
     * 
     * @param int $id ID da notificação
     * @param int $userId ID do usuário (para verificação de permissão)
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function markAsRead($id, $userId) {
        try {
            // Verificar se a notificação existe e pertence ao usuário
            $notification = $this->find($id);
            if (!$notification || $notification['user_id'] != $userId) {
                return false;
            }
            
            // Se já está marcada como lida, retornar verdadeiro
            if ($notification['status'] === 'read') {
                return true;
            }
            
            $sql = "UPDATE notifications SET 
                status = 'read', 
                read_at = NOW() 
                WHERE id = :id AND user_id = :user_id";
            
            $params = [
                'id' => $id,
                'user_id' => $userId
            ];
            
            $result = $this->db->update($sql, $params);
            
            // Limpar cache após atualização
            $this->clearCache($userId);
            
            return $result;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao marcar notificação como lida: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marca todas as notificações de um usuário como lidas
     * 
     * @param int $userId ID do usuário
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function markAllAsRead($userId) {
        try {
            // Verificar se o usuário tem notificações não lidas
            $unreadCount = $this->countUnreadNotifications($userId);
            if ($unreadCount == 0) {
                return true;
            }
            
            $sql = "UPDATE notifications SET 
                status = 'read', 
                read_at = NOW() 
                WHERE user_id = :user_id AND status = 'unread'";
            
            $params = [
                'user_id' => $userId
            ];
            
            $result = $this->db->update($sql, $params);
            
            // Limpar cache após atualização
            $this->clearCache($userId);
            
            return $result;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao marcar todas as notificações como lidas: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém uma notificação específica
     * 
     * @param int $id ID da notificação
     * @return array|null Dados da notificação ou null se não encontrada
     */
    public function find($id) {
        try {
            // Verificar cache
            $cacheKey = "notification_$id";
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }
            
            $sql = "SELECT * FROM notifications WHERE id = :id";
            $params = ['id' => $id];
            
            $result = $this->db->select($sql, $params);
            
            $notification = !empty($result) ? $result[0] : null;
            
            // Atualizar cache
            if ($notification) {
                $this->cache[$cacheKey] = $notification;
            }
            
            return $notification;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao buscar notificação: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém todas as notificações de um usuário
     * 
     * @param int $userId ID do usuário
     * @param string $status Status das notificações ('all', 'read', 'unread')
     * @param int $limit Limite de resultados
     * @param int $offset Deslocamento (para paginação)
     * @return array Lista de notificações
     */
    public function getNotificationsByUser($userId, $status = 'all', $limit = 10, $offset = 0) {
        try {
            // Verificar cache para consultas comuns
            $cacheKey = "user_{$userId}_notifications_{$status}_{$limit}_{$offset}";
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }
            
            // Melhor performance: remover JOINS desnecessários quando não precisamos de dados relacionados
            if ($status === 'unread' && $limit <= 5) {
                // Consulta otimizada para verificações rápidas de notificações não lidas
                $sql = "SELECT id, user_id, type, title, status, created_at 
                    FROM notifications 
                    WHERE user_id = :user_id AND status = 'unread' 
                    ORDER BY created_at DESC 
                    LIMIT :limit";
                
                $params = [
                    'user_id' => $userId,
                    'limit' => $limit
                ];
                
                $result = $this->db->select($sql, $params);
                $this->cache[$cacheKey] = $result;
                return $result;
            }
            
            // Consulta completa para visualizações detalhadas
            $sql = "SELECT n.*, o.order_number, q.product_id, p.name as product_name
                FROM notifications n
                LEFT JOIN orders o ON n.order_id = o.id
                LEFT JOIN print_queue q ON n.queue_item_id = q.id
                LEFT JOIN products p ON q.product_id = p.id
                WHERE n.user_id = :user_id";
            
            $params = ['user_id' => $userId];
            
            if ($status === 'read') {
                $sql .= " AND n.status = 'read'";
            } else if ($status === 'unread') {
                $sql .= " AND n.status = 'unread'";
            }
            
            $sql .= " ORDER BY n.created_at DESC";
            
            if ($limit > 0) {
                $sql .= " LIMIT :limit OFFSET :offset";
                $params['limit'] = $limit;
                $params['offset'] = $offset;
            }
            
            $result = $this->db->select($sql, $params);
            
            // Armazenar em cache
            $this->cache[$cacheKey] = $result;
            
            return $result;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao buscar notificações do usuário: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém todas as notificações com filtros (para administradores)
     * 
     * @param array $filters Filtros (user_id, type, status, date_from, date_to)
     * @param int $page Número da página
     * @param int $perPage Itens por página
     * @return array Lista de notificações
     */
    public function getAllNotifications($filters = [], $page = 1, $perPage = 20) {
        try {
            // Construir SQL base
            $sql = "SELECT n.*, u.name AS user_name, u.email AS user_email,
                o.order_number, q.product_id, p.name AS product_name,
                a.name AS admin_name
                FROM notifications n
                LEFT JOIN users u ON n.user_id = u.id
                LEFT JOIN users a ON n.created_by = a.id
                LEFT JOIN orders o ON n.order_id = o.id
                LEFT JOIN print_queue q ON n.queue_item_id = q.id
                LEFT JOIN products p ON q.product_id = p.id
                WHERE 1=1";
            
            $params = [];
            
            // Aplicar filtros
            $sql = $this->applyNotificationFilters($sql, $filters, $params);
            
            $sql .= " ORDER BY n.created_at DESC";
            
            // Aplicar paginação
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT :limit OFFSET :offset";
            $params['limit'] = $perPage;
            $params['offset'] = $offset;
            
            return $this->db->select($sql, $params);
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao buscar todas as notificações: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Conta o total de notificações com filtros (para paginação)
     * 
     * @param array $filters Filtros (user_id, type, status, date_from, date_to)
     * @return int Total de notificações
     */
    public function countAllNotifications($filters = []) {
        try {
            $sql = "SELECT COUNT(*) AS total FROM notifications n WHERE 1=1";
            $params = [];
            
            // Aplicar filtros
            $sql = $this->applyNotificationFilters($sql, $filters, $params);
            
            $result = $this->db->select($sql, $params);
            
            return !empty($result) ? $result[0]['total'] : 0;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao contar notificações: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Aplica filtros ao SQL de notificações
     * Função auxiliar para reduzir duplicação de código
     * 
     * @param string $sql SQL inicial
     * @param array $filters Filtros a serem aplicados
     * @param array &$params Parâmetros para consulta preparada (passado por referência)
     * @return string SQL com filtros aplicados
     */
    private function applyNotificationFilters($sql, $filters, &$params) {
        if (!empty($filters['user_id'])) {
            $sql .= " AND n.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['type'])) {
            $sql .= " AND n.type = :type";
            $params['type'] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND n.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(n.created_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(n.created_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        return $sql;
    }
    
    /**
     * Conta o número de notificações não lidas de um usuário
     * Função otimizada para desempenho em verificações frequentes.
     * 
     * @param int $userId ID do usuário
     * @return int Número de notificações não lidas
     */
    public function countUnreadNotifications($userId) {
        try {
            // Verificar cache
            $cacheKey = "user_{$userId}_unread_count";
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }
            
            // Query otimizada - usar COUNT(*) diretamente é mais eficiente
            $sql = "SELECT COUNT(*) AS total FROM notifications WHERE user_id = :user_id AND status = 'unread'";
            $params = ['user_id' => $userId];
            
            $result = $this->db->select($sql, $params);
            
            $count = !empty($result) ? $result[0]['total'] : 0;
            
            // Atualizar cache
            $this->cache[$cacheKey] = $count;
            
            return $count;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao contar notificações não lidas: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Exclui uma notificação
     * 
     * @param int $id ID da notificação
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function delete($id) {
        try {
            // Obter a notificação para encontrar o usuário
            $notification = $this->find($id);
            if (!$notification) {
                return false;
            }
            
            $userId = $notification['user_id'];
            
            $sql = "DELETE FROM notifications WHERE id = :id";
            $params = ['id' => $id];
            
            $result = $this->db->delete($sql, $params);
            
            // Limpar cache após exclusão
            $this->clearCache($userId);
            
            return $result;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao excluir notificação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Limpa o cache relacionado a um usuário específico
     * 
     * @param int $userId ID do usuário
     */
    private function clearCache($userId) {
        // Limpar cache de contagem não lida
        unset($this->cache["user_{$userId}_unread_count"]);
        
        // Limpar cache de notificações do usuário
        foreach ($this->cache as $key => $value) {
            if (strpos($key, "user_{$userId}_notifications_") === 0) {
                unset($this->cache[$key]);
            }
        }
    }
    
    /**
     * Cria uma notificação automática quando o status de um item na fila de impressão é alterado
     * 
     * @param int $queueItemId ID do item na fila
     * @param string $status Novo status
     * @param int $userId ID do usuário que receberá a notificação
     * @param int $adminId ID do administrador que fez a alteração
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function createQueueStatusNotification($queueItemId, $status, $userId, $adminId) {
        try {
            // Verificar se as notificações automáticas estão ativadas
            $settingsModel = new SettingsModel();
            $autoNotify = $settingsModel->getSetting('notifications_automatic_notifications', 1);
            $notifyOnStatusChange = $settingsModel->getSetting('notifications_notify_on_status_change', 1);
            
            if (!$autoNotify || !$notifyOnStatusChange) {
                return true; // Notificações automáticas desativadas, retornar como se tivesse sido bem-sucedido
            }
            
            // Obter informações do item na fila
            $printQueueModel = new PrintQueueModel();
            $queueItem = $printQueueModel->getQueueItemById($queueItemId);
            
            if (!$queueItem) {
                return false;
            }
            
            // Obter informações do produto
            $productModel = new ProductModel();
            $product = $productModel->getProductById($queueItem['product_id']);
            
            if (!$product) {
                return false;
            }
            
            // Obter informações do pedido
            $orderModel = new OrderModel();
            $order = $orderModel->getOrderById($queueItem['order_id']);
            
            if (!$order) {
                return false;
            }
            
            // Definir título e mensagem com base no status
            $title = '';
            $message = '';
            $type = 'info';
            
            switch ($status) {
                case 'validating':
                    $title = 'Validação de Modelo 3D Iniciada';
                    $message = "Seu modelo para o produto '{$product['name']}' está sendo validado para impressão 3D. Você receberá uma notificação quando a validação for concluída.";
                    break;
                case 'printing':
                    $title = 'Impressão 3D Iniciada';
                    $message = "Seu produto '{$product['name']}' começou a ser impresso em 3D. Tempo estimado de impressão: {$queueItem['estimated_print_time_hours']} horas.";
                    break;
                case 'completed':
                    $title = 'Impressão 3D Concluída';
                    $message = "A impressão 3D do seu produto '{$product['name']}' foi concluída com sucesso. Seu pedido agora entrará na fase de acabamento.";
                    $type = 'success';
                    break;
                case 'failed':
                    $title = 'Falha na Impressão 3D';
                    $message = "Ocorreu um problema durante a impressão 3D do seu produto '{$product['name']}'. Nossa equipe entrará em contato para resolver a situação.";
                    $type = 'error';
                    break;
                default:
                    $title = 'Atualização do Status da Impressão 3D';
                    $message = "O status da impressão 3D do seu produto '{$product['name']}' foi atualizado para '{$status}'.";
                    break;
            }
            
            // Criar notificação
            $notificationData = [
                'user_id' => $userId,
                'order_id' => $queueItem['order_id'],
                'queue_item_id' => $queueItemId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'created_by' => $adminId,
                'status' => 'unread'
            ];
            
            return $this->create($notificationData);
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao criar notificação automática: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria uma notificação automática quando uma impressora é atribuída a um item na fila
     * 
     * @param int $queueItemId ID do item na fila
     * @param int $printerId ID da impressora
     * @param int $userId ID do usuário que receberá a notificação
     * @param int $adminId ID do administrador que fez a atribuição
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function createPrinterAssignmentNotification($queueItemId, $printerId, $userId, $adminId) {
        try {
            // Verificar se as notificações automáticas estão ativadas
            $settingsModel = new SettingsModel();
            $autoNotify = $settingsModel->getSetting('notifications_automatic_notifications', 1);
            $notifyOnPrinterAssignment = $settingsModel->getSetting('notifications_notify_on_printer_assignment', 1);
            
            if (!$autoNotify || !$notifyOnPrinterAssignment) {
                return true; // Notificações automáticas desativadas, retornar como se tivesse sido bem-sucedido
            }
            
            // Obter informações do item na fila
            $printQueueModel = new PrintQueueModel();
            $queueItem = $printQueueModel->getQueueItemById($queueItemId);
            
            if (!$queueItem) {
                return false;
            }
            
            // Obter informações do produto
            $productModel = new ProductModel();
            $product = $productModel->getProductById($queueItem['product_id']);
            
            if (!$product) {
                return false;
            }
            
            // Obter informações da impressora
            $printer = $printQueueModel->getPrinterById($printerId);
            
            if (!$printer) {
                return false;
            }
            
            // Criar notificação
            $notificationData = [
                'user_id' => $userId,
                'order_id' => $queueItem['order_id'],
                'queue_item_id' => $queueItemId,
                'type' => 'info',
                'title' => 'Impressora Atribuída',
                'message' => "Seu produto '{$product['name']}' foi atribuído à impressora '{$printer['name']}' e será impresso em breve.",
                'created_by' => $adminId,
                'status' => 'unread'
            ];
            
            return $this->create($notificationData);
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao criar notificação de atribuição de impressora: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Limpa notificações antigas para otimizar o banco de dados
     * 
     * @param int $olderThanDays Limpar notificações mais antigas que este número de dias
     * @param string $status Status das notificações a serem limpas ('all', 'read')
     * @return int Número de notificações excluídas
     */
    public function cleanOldNotifications($olderThanDays = 30, $status = 'read') {
        try {
            $sql = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
            $params = ['days' => $olderThanDays];
            
            if ($status === 'read') {
                $sql .= " AND status = 'read'";
            }
            
            $result = $this->db->execute($sql, $params);
            
            // Limpar todo o cache após operação de exclusão em massa
            $this->cache = [];
            
            return $result;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao limpar notificações antigas: ' . $e->getMessage());
            return 0;
        }
    }
}
