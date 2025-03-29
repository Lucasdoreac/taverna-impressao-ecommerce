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
                
                // Notificar cliente sobre adição à fila
                $this->createAddedToQueueNotification($queueId, $data);
                
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
                
                // Criar notificação para o cliente usando o novo sistema de notificações
                $this->createStatusChangeNotification($queueId, $status, $userId, $notes);
                
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
                
                // Notificar cliente sobre atribuição de impressora
                $this->createPrinterAssignmentNotification($queueId, $printerId, $userId);
                
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
     * Cria uma notificação para o cliente sobre a adição à fila de impressão
     * 
     * @param int $queueId ID do item na fila
     * @param array $data Dados do item
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    private function createAddedToQueueNotification($queueId, $data) {
        try {
            // Obter informações do pedido
            $orderModel = new OrderModel();
            $order = $orderModel->getOrderById($data['order_id']);
            
            if (!$order || !$order['user_id']) {
                return false;
            }
            
            // Obter informações do produto
            $productModel = new ProductModel();
            $product = $productModel->getProductById($data['product_id']);
            
            if (!$product) {
                return false;
            }
            
            // Criar notificação
            $notificationModel = new NotificationModel();
            $notificationData = [
                'user_id' => $order['user_id'],
                'order_id' => $data['order_id'],
                'queue_item_id' => $queueId,
                'type' => 'info',
                'title' => 'Seu item entrou na fila de impressão 3D',
                'message' => "Seu produto '{$product['name']}' foi adicionado à fila de impressão 3D. Você receberá notificações sobre o progresso da impressão.",
                'created_by' => isset($data['created_by']) ? $data['created_by'] : 1, // ID de sistema
                'status' => 'unread'
            ];
            
            return $notificationModel->create($notificationData);
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao criar notificação de adição à fila: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria uma notificação para o cliente sobre mudança de status
     * 
     * @param int $queueId ID do item na fila
     * @param string $status Novo status
     * @param int $userId ID do usuário que fez a alteração
     * @param string $notes Notas adicionais
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    private function createStatusChangeNotification($queueId, $status, $userId, $notes = '') {
        try {
            // Verificar se o item existe
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
            
            // Obter informações do produto
            $productModel = new ProductModel();
            $product = $productModel->getProductById($item['product_id']);
            
            if (!$product) {
                return false;
            }
            
            // Definir título e mensagem com base no status
            $title = '';
            $message = '';
            $type = 'info';
            
            switch ($status) {
                case 'scheduled':
                    $title = 'Sua impressão 3D foi agendada';
                    $message = "Sua impressão para o produto '{$product['name']}' (Pedido #{$order['order_number']}) foi agendada. Acompanhe o status em tempo real na sua conta.";
                    break;
                    
                case 'printing':
                    $title = 'Sua impressão 3D começou!';
                    $message = "Sua impressão para o produto '{$product['name']}' (Pedido #{$order['order_number']}) começou. O processo deve levar aproximadamente {$item['estimated_print_time_hours']} horas.";
                    break;
                    
                case 'completed':
                    $title = 'Sua impressão 3D foi concluída';
                    $message = "Sua impressão para o produto '{$product['name']}' (Pedido #{$order['order_number']}) foi concluída com sucesso! O processo de acabamento e envio começará em breve.";
                    $type = 'success';
                    break;
                    
                case 'failed':
                    $title = 'Problema na sua impressão 3D';
                    $message = "Houve um problema durante a impressão do seu produto '{$product['name']}' (Pedido #{$order['order_number']}). Nossa equipe já foi notificada e entrará em contato em breve.";
                    $type = 'error';
                    break;
                    
                case 'canceled':
                    $title = 'Impressão 3D cancelada';
                    $message = "A impressão do seu produto '{$product['name']}' (Pedido #{$order['order_number']}) foi cancelada. " . ($notes ? "Motivo: {$notes}" : "");
                    $type = 'warning';
                    break;
                    
                default:
                    // Para outros status, verificar se devemos notificar
                    $settingsModel = new SettingsModel();
                    $notifyOnStatusChange = $settingsModel->getSetting('notifications_notify_on_status_change', 1);
                    
                    if (!$notifyOnStatusChange) {
                        return true; // Skip notification
                    }
                    
                    $title = 'Atualização na sua impressão 3D';
                    $message = "O status da impressão do seu produto '{$product['name']}' (Pedido #{$order['order_number']}) foi atualizado para '{$status}'. " . ($notes ? "Observações: {$notes}" : "");
                    break;
            }
            
            // Criar notificação usando o novo sistema de notificações
            $notificationModel = new NotificationModel();
            $notificationData = [
                'user_id' => $order['user_id'],
                'order_id' => $item['order_id'],
                'queue_item_id' => $queueId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'created_by' => $userId,
                'status' => 'unread'
            ];
            
            return $notificationModel->create($notificationData);
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao criar notificação de mudança de status: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria uma notificação para o cliente sobre atribuição de impressora
     * 
     * @param int $queueId ID do item na fila
     * @param int $printerId ID da impressora
     * @param int $userId ID do usuário que fez a atribuição
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    private function createPrinterAssignmentNotification($queueId, $printerId, $userId) {
        try {
            // Verificar se a notificação deve ser enviada
            $settingsModel = new SettingsModel();
            $notifyOnPrinterAssignment = $settingsModel->getSetting('notifications_notify_on_printer_assignment', 1);
            
            if (!$notifyOnPrinterAssignment) {
                return true; // Skip notification
            }
            
            // Obter informações do item
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
            
            // Obter informações do produto
            $productModel = new ProductModel();
            $product = $productModel->getProductById($item['product_id']);
            
            if (!$product) {
                return false;
            }
            
            // Obter informações da impressora
            $printer = $this->getPrinterById($printerId);
            
            if (!$printer) {
                return false;
            }
            
            // Criar notificação usando o novo modelo de notificações
            $notificationModel = new NotificationModel();
            return $notificationModel->createPrinterAssignmentNotification(
                $queueId,
                $printerId,
                $order['user_id'],
                $userId
            );
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao criar notificação de atribuição de impressora: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancela itens na fila de impressão por ID de pedido
     * 
     * @param int $orderId ID do pedido
     * @param string $reason Motivo do cancelamento
     * @return bool Verdadeiro se a operação foi bem-sucedida
     */
    public function cancelQueueItemsByOrderId($orderId, $reason = 'Pedido cancelado pelo cliente') {
        try {
            // Obter todos os itens da fila para este pedido
            $items = $this->getQueueItemsByOrderId($orderId);
            $result = true;
            
            // Cancelar cada item
            foreach ($items as $item) {
                // Só cancelar itens que não foram concluídos ou já cancelados
                if ($item['status'] != 'completed' && $item['status'] != 'canceled') {
                    $updateResult = $this->updateStatus($item['id'], 'canceled', null, $reason);
                    $result = $result && $updateResult;
                }
            }
            
            return $result;
        } catch (Exception $e) {
            app_log('ERROR', 'Erro ao cancelar itens da fila por pedido: ' . $e->getMessage());
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
            
            if (isset($filters['order_item_id']) && !empty($filters['order_item_id'])) {
                $query .= " AND pq.order_item_id = ?";
                $params[] = $filters['order_item_id'];
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
     * Visualiza a fila de impressão agrupada por pedido
     * 
     * @return array Pedidos com seus itens na fila
     */
    public function viewByOrder() {
        try {
            // Primeiro, obter todos os pedidos que possuem itens na fila
            $query = "SELECT DISTINCT o.id, o.order_number, o.status, o.created_at, 
                      u.name AS customer_name, u.email AS customer_email
                      FROM print_queue pq
                      JOIN orders o ON pq.order_id = o.id
                      LEFT JOIN users u ON o.user_id = u.id
                      ORDER BY o.created_at DESC";
            
            $orders = $this->db->query($query);
            
            if (!$orders) {
                return [];
            }
            
            // Para cada pedido, obter os itens na fila
            $result = [];
            foreach ($orders as $order) {
                $order['queue_items'] = $this->getQueueItemsByOrderId($order['id']);
                $result[] = $order;
            }
            
            return $result;
        } catch (Exception $e) {
            app_log("Erro ao visualizar fila por pedido: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Gera um relatório de produção
     * 
     * @param array $filters Filtros para o relatório
     * @return array Dados do relatório
     */
    public function productionReport($filters = []) {
        try {
            // Definir período padrão se não especificado
            if (!isset($filters['date_from'])) {
                $filters['date_from'] = date('Y-m-d', strtotime('-30 days'));
            }
            
            if (!isset($filters['date_to'])) {
                $filters['date_to'] = date('Y-m-d');
            }
            
            // Obter estatísticas de uso de impressoras
            $query = "SELECT pr.name, pr.model,
                      COUNT(pq.id) AS total_prints,
                      SUM(pq.actual_print_time_hours) AS total_hours,
                      SUM(pq.filament_usage_grams) AS total_filament,
                      COUNT(CASE WHEN pq.status = 'completed' THEN 1 END) AS completed_prints,
                      COUNT(CASE WHEN pq.status = 'failed' THEN 1 END) AS failed_prints
                      FROM printers pr
                      LEFT JOIN print_queue pq ON pr.id = pq.printer_id
                      WHERE pq.actual_start_date BETWEEN ? AND ?
                      GROUP BY pr.id
                      ORDER BY total_prints DESC";
            
            $printerStats = $this->db->query($query, [
                $filters['date_from'] . ' 00:00:00',
                $filters['date_to'] . ' 23:59:59'
            ]);
            
            // Obter estatísticas de uso de filamento por tipo
            $query = "SELECT filament_type,
                      COUNT(id) AS total_prints,
                      SUM(filament_usage_grams) AS total_filament
                      FROM print_queue
                      WHERE actual_start_date BETWEEN ? AND ?
                      GROUP BY filament_type
                      ORDER BY total_filament DESC";
            
            $filamentStats = $this->db->query($query, [
                $filters['date_from'] . ' 00:00:00',
                $filters['date_to'] . ' 23:59:59'
            ]);
            
            // Obter estatísticas por status
            $query = "SELECT status,
                      COUNT(id) AS count
                      FROM print_queue
                      WHERE created_at BETWEEN ? AND ?
                      GROUP BY status";
            
            $statusStats = $this->db->query($query, [
                $filters['date_from'] . ' 00:00:00',
                $filters['date_to'] . ' 23:59:59'
            ]);
            
            // Obter tempo médio de impressão
            $query = "SELECT AVG(actual_print_time_hours) AS avg_print_time
                      FROM print_queue
                      WHERE status = 'completed'
                      AND actual_start_date BETWEEN ? AND ?
                      AND actual_end_date IS NOT NULL";
            
            $avgTimeResult = $this->db->query($query, [
                $filters['date_from'] . ' 00:00:00',
                $filters['date_to'] . ' 23:59:59'
            ]);
            
            $avgPrintTime = $avgTimeResult && isset($avgTimeResult[0]['avg_print_time']) ? 
                $avgTimeResult[0]['avg_print_time'] : 0;
            
            // Retornar todos os dados do relatório
            return [
                'period' => [
                    'from' => $filters['date_from'],
                    'to' => $filters['date_to']
                ],
                'printer_stats' => $printerStats ? $printerStats : [],
                'filament_stats' => $filamentStats ? $filamentStats : [],
                'status_stats' => $statusStats ? $statusStats : [],
                'avg_print_time' => $avgPrintTime
            ];
        } catch (Exception $e) {
            app_log("Erro ao gerar relatório de produção: " . $e->getMessage(), 'error');
            return [];
        }
    }
    
    /**
     * Dashboard com estatísticas da fila de impressão
     * 
     * @return array Dados do dashboard
     */
    public function dashboard() {
        try {
            // Estatísticas gerais
            $stats = [
                'total_queue_items' => 0,
                'pending_items' => 0,
                'printing_items' => 0,
                'completed_items' => 0,
                'failed_items' => 0,
                'total_printers' => 0,
                'available_printers' => 0,
                'printing_printers' => 0,
                'maintenance_printers' => 0,
                'estimated_completion_time' => null
            ];
            
            // Contar itens na fila por status
            $query = "SELECT status, COUNT(*) AS count FROM print_queue GROUP BY status";
            $statusCounts = $this->db->query($query);
            
            if ($statusCounts) {
                foreach ($statusCounts as $row) {
                    $stats['total_queue_items'] += $row['count'];
                    
                    switch ($row['status']) {
                        case 'pending':
                        case 'scheduled':
                            $stats['pending_items'] += $row['count'];
                            break;
                        case 'printing':
                            $stats['printing_items'] += $row['count'];
                            break;
                        case 'completed':
                            $stats['completed_items'] += $row['count'];
                            break;
                        case 'failed':
                            $stats['failed_items'] += $row['count'];
                            break;
                    }
                }
            }
            
            // Contar impressoras por status
            $query = "SELECT status, COUNT(*) AS count FROM printers GROUP BY status";
            $printerCounts = $this->db->query($query);
            
            if ($printerCounts) {
                foreach ($printerCounts as $row) {
                    $stats['total_printers'] += $row['count'];
                    
                    switch ($row['status']) {
                        case 'available':
                            $stats['available_printers'] += $row['count'];
                            break;
                        case 'printing':
                            $stats['printing_printers'] += $row['count'];
                            break;
                        case 'maintenance':
                            $stats['maintenance_printers'] += $row['count'];
                            break;
                    }
                }
            }
            
            // Calcular tempo estimado para conclusão de todos os itens pendentes
            if ($stats['printing_printers'] > 0) {
                $query = "SELECT SUM(estimated_print_time_hours) AS total_hours 
                          FROM print_queue 
                          WHERE status IN ('pending', 'scheduled')";
                $result = $this->db->query($query);
                
                if ($result && isset($result[0]['total_hours']) && $result[0]['total_hours'] > 0) {
                    $totalHours = $result[0]['total_hours'];
                    $hoursPerPrinter = $totalHours / $stats['printing_printers'];
                    
                    // Calcular data e hora estimada de conclusão
                    $now = new DateTime();
                    $now->add(new DateInterval('PT' . ceil($hoursPerPrinter) . 'H'));
                    $stats['estimated_completion_time'] = $now->format('Y-m-d H:i:s');
                }
            }
            
            // Obter os próximos 5 itens a serem impressos
            $query = "SELECT pq.id, pq.order_id, o.order_number, pq.product_id, p.name AS product_name,
                      pq.estimated_print_time_hours, pq.priority, pq.status,
                      pq.created_at, u.name AS customer_name
                      FROM print_queue pq
                      JOIN orders o ON pq.order_id = o.id
                      JOIN products p ON pq.product_id = p.id
                      LEFT JOIN users u ON o.user_id = u.id
                      WHERE pq.status IN ('pending', 'scheduled')
                      ORDER BY pq.priority ASC, pq.created_at ASC
                      LIMIT 5";
            
            $nextItems = $this->db->query($query);
            
            // Obter os 5 itens impressos mais recentemente
            $query = "SELECT pq.id, pq.order_id, o.order_number, pq.product_id, p.name AS product_name,
                      pq.actual_print_time_hours, pq.status, pq.actual_end_date,
                      u.name AS customer_name, pr.name AS printer_name
                      FROM print_queue pq
                      JOIN orders o ON pq.order_id = o.id
                      JOIN products p ON pq.product_id = p.id
                      LEFT JOIN users u ON o.user_id = u.id
                      LEFT JOIN printers pr ON pq.printer_id = pr.id
                      WHERE pq.status IN ('completed', 'failed')
                      AND pq.actual_end_date IS NOT NULL
                      ORDER BY pq.actual_end_date DESC
                      LIMIT 5";
            
            $recentItems = $this->db->query($query);
            
            // Retornar todos os dados do dashboard
            return [
                'stats' => $stats,
                'next_items' => $nextItems ? $nextItems : [],
                'recent_items' => $recentItems ? $recentItems : []
            ];
        } catch (Exception $e) {
            app_log("Erro ao gerar dashboard: " . $e->getMessage(), 'error');
            return [
                'stats' => [],
                'next_items' => [],
                'recent_items' => []
            ];
        }
    }
}