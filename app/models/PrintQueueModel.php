<?php
/**
 * Modelo para gerenciamento da fila de impressão 3D
 * 
 * Este modelo gerencia a fila de impressão 3D, incluindo adição de itens à fila,
 * atualização de status, priorização, alocação de impressoras e histórico de eventos.
 */
class PrintQueueModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Adiciona um item à fila de impressão
     * 
     * @param array $data Dados do item a ser adicionado
     * @return int|bool O ID do item adicionado ou false em caso de erro
     */
    public function addToQueue($data) {
        try {
            // Preparar os campos obrigatórios
            $required = [
                'order_id', 
                'order_item_id', 
                'product_id', 
                'estimated_print_time_hours',
                'filament_type',
                'filament_usage_grams',
                'scale'
            ];
            
            // Verificar se todos os campos obrigatórios estão presentes
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    app_log("Campo obrigatório ausente: {$field}", 'error');
                    return false;
                }
            }
            
            // Definir campos opcionais com valores padrão se não fornecidos
            $data['priority'] = isset($data['priority']) ? $data['priority'] : 5;
            $data['status'] = isset($data['status']) ? $data['status'] : 'pending';
            
            // Executar a inserção
            $result = $this->db->insert('print_queue', $data);
            
            if ($result) {
                $queueId = $this->db->lastInsertId();
                
                // Registrar evento no histórico
                $this->addHistoryEvent($queueId, [
                    'event_type' => 'status_change',
                    'new_status' => 'pending',
                    'description' => 'Item adicionado à fila de impressão',
                    'created_by' => isset($data['created_by']) ? $data['created_by'] : null
                ]);
                
                return $queueId;
            }
            
            return false;
        } catch (Exception $e) {
            app_log("Erro ao adicionar item à fila: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Atualiza o status de um item na fila
     * 
     * @param int $queueId ID do item na fila
     * @param string $status Novo status
     * @param int $userId ID do usuário que fez a alteração
     * @param string $notes Notas adicionais sobre a alteração
     * @return bool
     */
    public function updateStatus($queueId, $status, $userId = null, $notes = '') {
        try {
            // Verificar status válido
            $validStatuses = ['pending', 'scheduled', 'printing', 'paused', 'completed', 'failed', 'canceled'];
            if (!in_array($status, $validStatuses)) {
                app_log("Status inválido: {$status}", 'error');
                return false;
            }
            
            // Obter status atual
            $currentItem = $this->getQueueItemById($queueId);
            if (!$currentItem) {
                return false;
            }
            
            $previousStatus = $currentItem['status'];
            
            // Atualizar campos específicos dependendo do status
            $updateData = ['status' => $status];
            
            if ($status == 'printing' && $previousStatus != 'printing') {
                $updateData['actual_start_date'] = date('Y-m-d H:i:s');
            } else if (($status == 'completed' || $status == 'failed') && $previousStatus != 'completed' && $previousStatus != 'failed') {
                $updateData['actual_end_date'] = date('Y-m-d H:i:s');
                
                // Calcular tempo real de impressão se iniciado anteriormente
                if (!empty($currentItem['actual_start_date'])) {
                    $start = new DateTime($currentItem['actual_start_date']);
                    $end = new DateTime();
                    $interval = $start->diff($end);
                    $hours = $interval->h + ($interval->days * 24) + ($interval->i / 60) + ($interval->s / 3600);
                    $updateData['actual_print_time_hours'] = round($hours, 2);
                }
            }
            
            // Atualizar o item
            $result = $this->db->update('print_queue', $updateData, ['id' => $queueId]);
            
            if ($result) {
                // Registrar evento no histórico
                $this->addHistoryEvent($queueId, [
                    'event_type' => 'status_change',
                    'previous_status' => $previousStatus,
                    'new_status' => $status,
                    'description' => "Status alterado de {$previousStatus} para {$status}" . ($notes ? ": {$notes}" : ""),
                    'created_by' => $userId
                ]);
                
                // Criar notificação para o cliente
                $this->createStatusNotification($queueId, $status);
                
                // Se o status for 'completed' ou 'failed', atualizar o pedido
                if ($status == 'completed' || $status == 'failed') {
                    $this->updateOrderStatus($currentItem['order_id']);
                }
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            app_log("Erro ao atualizar status: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Atualiza o status do pedido com base nos itens na fila
     * 
     * @param int $orderId ID do pedido
     * @return bool
     */
    private function updateOrderStatus($orderId) {
        try {
            // Obter todos os itens da fila para este pedido
            $items = $this->getQueueItemsByOrderId($orderId);
            
            // Verificar se todos os itens foram concluídos
            $allCompleted = true;
            $anyFailed = false;
            
            foreach ($items as $item) {
                if ($item['status'] != 'completed' && $item['status'] != 'canceled') {
                    $allCompleted = false;
                }
                if ($item['status'] == 'failed') {
                    $anyFailed = true;
                }
            }
            
            // Atualizar status do pedido se necessário
            if ($allCompleted) {
                $orderModel = new OrderModel();
                $orderStatus = $anyFailed ? 'problem' : 'finishing';
                return $orderModel->updateOrderStatus($orderId, $orderStatus);
            }
            
            return true;
        } catch (Exception $e) {
            app_log("Erro ao atualizar status do pedido: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Atribui uma impressora a um item na fila
     * 
     * @param int $queueId ID do item na fila
     * @param int $printerId ID da impressora
     * @param int $userId ID do usuário que fez a alteração
     * @return bool
     */
    public function assignPrinter($queueId, $printerId, $userId = null) {
        try {
            // Verificar se o item existe
            $currentItem = $this->getQueueItemById($queueId);
            if (!$currentItem) {
                return false;
            }
            
            // Verificar se a impressora existe e está disponível
            $printer = $this->getPrinterById($printerId);
            if (!$printer || $printer['status'] != 'available') {
                app_log("Impressora não disponível: {$printerId}", 'error');
                return false;
            }
            
            $previousPrinterId = $currentItem['printer_id'];
            
            // Atualizar o item
            $result = $this->db->update('print_queue', [
                'printer_id' => $printerId
            ], ['id' => $queueId]);
            
            // Atualizar status da impressora
            $this->db->update('printers', [
                'status' => 'printing'
            ], ['id' => $printerId]);
            
            if ($result) {
                // Registrar evento no histórico
                $this->addHistoryEvent($queueId, [
                    'event_type' => 'printer_assigned',
                    'previous_printer_id' => $previousPrinterId,
                    'new_printer_id' => $printerId,
                    'description' => "Impressora #{$printerId} ({$printer['name']}) atribuída ao item",
                    'created_by' => $userId
                ]);
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            app_log("Erro ao atribuir impressora: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Atualiza a prioridade de um item na fila
     * 
     * @param int $queueId ID do item na fila
     * @param int $priority Nova prioridade (1-10)
     * @param int $userId ID do usuário que fez a alteração
     * @return bool
     */
    public function updatePriority($queueId, $priority, $userId = null) {
        try {
            // Verificar prioridade válida
            if ($priority < 1 || $priority > 10) {
                app_log("Prioridade inválida: {$priority}", 'error');
                return false;
            }
            
            // Verificar se o item existe
            $currentItem = $this->getQueueItemById($queueId);
            if (!$currentItem) {
                return false;
            }
            
            $previousPriority = $currentItem['priority'];
            
            // Atualizar o item
            $result = $this->db->update('print_queue', [
                'priority' => $priority
            ], ['id' => $queueId]);
            
            if ($result) {
                // Registrar evento no histórico
                $this->addHistoryEvent($queueId, [
                    'event_type' => 'priority_change',
                    'previous_priority' => $previousPriority,
                    'new_priority' => $priority,
                    'description' => "Prioridade alterada de {$previousPriority} para {$priority}",
                    'created_by' => $userId
                ]);
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            app_log("Erro ao atualizar prioridade: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Registra um evento no histórico da fila
     * 
     * @param int $queueId ID do item na fila
     * @param array $data Dados do evento
     * @return int|bool O ID do evento ou false em caso de erro
     */
    public function addHistoryEvent($queueId, $data) {
        try {
            if (!isset($data['event_type']) || !isset($data['description'])) {
                return false;
            }
            
            $data['print_queue_id'] = $queueId;
            
            $result = $this->db->insert('print_queue_history', $data);
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (Exception $e) {
            app_log("Erro ao registrar evento no histórico: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Cria uma notificação para o cliente sobre alteração de status
     * 
     * @param int $queueId ID do item na fila
     * @param string $status Novo status
     * @return int|bool O ID da notificação ou false em caso de erro
     */
    private function createStatusNotification($queueId, $status) {
        try {
            // Obter informações do item na fila
            $item = $this->getQueueItemById($queueId);
            if (!$item) {
                return false;
            }
            
            // Obter informações do pedido
            $orderModel = new OrderModel();
            $order = $orderModel->getOrderById($item['order_id']);
            
            if (!$order || !$order['user_id']) {
                return false;
            }
            
            // Definir título e mensagem com base no status
            $title = 'Atualização na sua impressão 3D';
            $message = 'Houve uma atualização no status da sua impressão 3D.';
            $notificationType = 'status_change';
            
            switch ($status) {
                case 'scheduled':
                    $title = 'Sua impressão 3D foi agendada';
                    $message = "Sua impressão para o pedido #{$order['order_number']} foi agendada. Acompanhe o status em tempo real na sua conta.";
                    $notificationType = 'scheduled';
                    break;
                    
                case 'printing':
                    $title = 'Sua impressão 3D começou!';
                    $message = "Sua impressão para o pedido #{$order['order_number']} começou. O processo deve levar aproximadamente {$item['estimated_print_time_hours']} horas.";
                    $notificationType = 'started';
                    break;
                    
                case 'completed':
                    $title = 'Sua impressão 3D foi concluída';
                    $message = "Sua impressão para o pedido #{$order['order_number']} foi concluída com sucesso! O processo de acabamento e envio começará em breve.";
                    $notificationType = 'completed';
                    break;
                    
                case 'failed':
                    $title = 'Problema na sua impressão 3D';
                    $message = "Houve um problema durante a impressão do seu pedido #{$order['order_number']}. Nossa equipe já foi notificada e entrará em contato em breve.";
                    $notificationType = 'failed';
                    break;
            }
            
            // Criar notificação
            $notificationData = [
                'user_id' => $order['user_id'],
                'print_queue_id' => $queueId,
                'notification_type' => $notificationType,
                'title' => $title,
                'message' => $message
            ];
            
            $result = $this->db->insert('print_notifications', $notificationData);
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (Exception $e) {
            app_log("Erro ao criar notificação: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Obtém um item da fila pelo ID
     * 
     * @param int $queueId ID do item na fila
     * @return array|bool Dados do item ou false se não encontrado
     */
    public function getQueueItemById($queueId) {
        try {
            $query = "SELECT pq.*, 
                      p.name AS product_name, 
                      pr.name AS printer_name, 
                      fc.name AS filament_color, 
                      fc.hex_code AS filament_hex_code
                      FROM print_queue pq
                      LEFT JOIN products p ON pq.product_id = p.id
                      LEFT JOIN printers pr ON pq.printer_id = pr.id
                      LEFT JOIN filament_colors fc ON pq.filament_color_id = fc.id
                      WHERE pq.id = ?";
            
            $result = $this->db->query($query, [$queueId]);
            
            if ($result && count($result) > 0) {
                return $result[0];
            }
            
            return false;
        } catch (Exception $e) {
            app_log("Erro ao obter item da fila: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Obtém itens da fila pelo ID do pedido
     * 
     * @param int $orderId ID do pedido
     * @return array Itens da fila para o pedido
     */
    public function getQueueItemsByOrderId($orderId) {
        try {
            $query = "SELECT pq.*, 
                      p.name AS product_name, 
                      pr.name AS printer_name, 
                      fc.name AS filament_color, 
                      fc.hex_code AS filament_hex_code
                      FROM print_queue pq
                      LEFT JOIN products p ON pq.product_id = p.id
                      LEFT JOIN printers pr ON pq.printer_id = pr.id
                      LEFT JOIN filament_colors fc ON pq.filament_color_id = fc.id
                      WHERE pq.order_id = ?
                      ORDER BY pq.priority, pq.created_at";
            
            $result = $this->db->query($query, [$orderId]);
            
            return $result ? $result : [];
        } catch (Exception $e) {
            app_log("Erro ao obter itens da fila por pedido: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Obtém o histórico de eventos de um item da fila
     * 
     * @param int $queueId ID do item na fila
     * @return array Eventos do item
     */
    public function getQueueItemHistory($queueId) {
        try {
            $query = "SELECT pqh.*, u.name AS user_name
                      FROM print_queue_history pqh
                      LEFT JOIN users u ON pqh.created_by = u.id
                      WHERE pqh.print_queue_id = ?
                      ORDER BY pqh.created_at DESC";
            
            $result = $this->db->query($query, [$queueId]);
            
            return $result ? $result : [];
        } catch (Exception $e) {
            app_log("Erro ao obter histórico do item da fila: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Obtém todos os itens da fila com filtros
     * 
     * @param array $filters Filtros a serem aplicados
     * @return array Itens da fila
     */
    public function getQueueItems($filters = []) {
        try {
            $query = "SELECT pq.*, 
                      p.name AS product_name, 
                      pr.name AS printer_name, 
                      o.order_number,
                      u.name AS customer_name,
                      fc.name AS filament_color, 
                      fc.hex_code AS filament_hex_code
                      FROM print_queue pq
                      LEFT JOIN products p ON pq.product_id = p.id
                      LEFT JOIN printers pr ON pq.printer_id = pr.id
                      LEFT JOIN orders o ON pq.order_id = o.id
                      LEFT JOIN users u ON o.user_id = u.id
                      LEFT JOIN filament_colors fc ON pq.filament_color_id = fc.id
                      WHERE 1=1";
            
            $params = [];
            
            // Aplicar filtros
            if (isset($filters['status']) && !empty($filters['status'])) {
                $query .= " AND pq.status = ?";
                $params[] = $filters['status'];
            }
            
            if (isset($filters['printer_id']) && !empty($filters['printer_id'])) {
                $query .= " AND pq.printer_id = ?";
                $params[] = $filters['printer_id'];
            }
            
            if (isset($filters['customer_id']) && !empty($filters['customer_id'])) {
                $query .= " AND o.user_id = ?";
                $params[] = $filters['customer_id'];
            }
            
            // Ordenação
            $query .= " ORDER BY ";
            if (isset($filters['order_by']) && !empty($filters['order_by'])) {
                $validOrderBy = ['priority', 'created_at', 'scheduled_start_date'];
                $orderBy = in_array($filters['order_by'], $validOrderBy) ? $filters['order_by'] : 'priority';
                $query .= "pq.{$orderBy} ";
            } else {
                $query .= "pq.priority ";
            }
            
            $query .= isset($filters['order_dir']) && strtolower($filters['order_dir']) == 'desc' ? "DESC" : "ASC";
            
            if (!isset($filters['order_by']) || $filters['order_by'] != 'created_at') {
                $query .= ", pq.created_at ASC";
            }
            
            $result = $this->db->query($query, $params);
            
            return $result ? $result : [];
        } catch (Exception $e) {
            app_log("Erro ao obter itens da fila: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Obtém todas as impressoras disponíveis
     * 
     * @return array Lista de impressoras
     */
    public function getAllPrinters() {
        try {
            $query = "SELECT * FROM printers ORDER BY name";
            $result = $this->db->query($query);
            
            return $result ? $result : [];
        } catch (Exception $e) {
            app_log("Erro ao obter impressoras: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Obtém uma impressora pelo ID
     * 
     * @param int $printerId ID da impressora
     * @return array|bool Dados da impressora ou false se não encontrada
     */
    public function getPrinterById($printerId) {
        try {
            $query = "SELECT * FROM printers WHERE id = ?";
            $result = $this->db->query($query, [$printerId]);
            
            if ($result && count($result) > 0) {
                return $result[0];
            }
            
            return false;
        } catch (Exception $e) {
            app_log("Erro ao obter impressora: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Atualiza o status de uma impressora
     * 
     * @param int $printerId ID da impressora
     * @param string $status Novo status
     * @return bool
     */
    public function updatePrinterStatus($printerId, $status) {
        try {
            $validStatuses = ['available', 'printing', 'maintenance', 'offline'];
            if (!in_array($status, $validStatuses)) {
                app_log("Status inválido para impressora: {$status}", 'error');
                return false;
            }
            
            $result = $this->db->update('printers', [
                'status' => $status
            ], ['id' => $printerId]);
            
            return $result !== false;
        } catch (Exception $e) {
            app_log("Erro ao atualizar status da impressora: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Adiciona uma nova impressora
     * 
     * @param array $data Dados da impressora
     * @return int|bool O ID da impressora ou false em caso de erro
     */
    public function addPrinter($data) {
        try {
            // Verificar campos obrigatórios
            $required = ['name', 'model', 'max_width', 'max_depth', 'max_height'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    app_log("Campo obrigatório ausente: {$field}", 'error');
                    return false;
                }
            }
            
            $result = $this->db->insert('printers', $data);
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (Exception $e) {
            app_log("Erro ao adicionar impressora: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Obtém as notificações não lidas de um usuário
     * 
     * @param int $userId ID do usuário
     * @return array Notificações não lidas
     */
    public function getUnreadNotifications($userId) {
        try {
            $query = "SELECT pn.*, 
                      pq.product_id, 
                      p.name AS product_name, 
                      o.order_number
                      FROM print_notifications pn
                      JOIN print_queue pq ON pn.print_queue_id = pq.id
                      JOIN products p ON pq.product_id = p.id
                      JOIN orders o ON pq.order_id = o.id
                      WHERE pn.user_id = ? AND pn.is_read = 0
                      ORDER BY pn.created_at DESC";
            
            $result = $this->db->query($query, [$userId]);
            
            return $result ? $result : [];
        } catch (Exception $e) {
            app_log("Erro ao obter notificações: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Marca uma notificação como lida
     * 
     * @param int $notificationId ID da notificação
     * @param int $userId ID do usuário (para verificar permissão)
     * @return bool
     */
    public function markNotificationAsRead($notificationId, $userId) {
        try {
            $result = $this->db->update('print_notifications', [
                'is_read' => 1
            ], ['id' => $notificationId, 'user_id' => $userId]);
            
            return $result !== false;
        } catch (Exception $e) {
            app_log("Erro ao marcar notificação como lida: " . $e->getMessage(), 'error');
            return false;
        }
    }
}
