<?php
/**
 * OrderModel - Modelo para pedidos
 */
class OrderModel extends Model {
    protected $table = 'orders';
    protected $fillable = [
        'user_id', 'order_number', 'status', 'payment_method', 'payment_status',
        'shipping_address_id', 'shipping_method', 'shipping_cost', 'subtotal',
        'discount', 'total', 'notes', 'tracking_code', 'estimated_print_time_hours',
        'print_start_date', 'print_finish_date'
    ];
    
    /**
     * Gera um número único para o pedido
     */
    public function generateOrderNumber() {
        // Prefixo + Timestamp + Random
        return 'TI' . date('Ymd') . substr(uniqid(), -4);
    }
    
    /**
     * Busca um pedido pelo número
     */
    public function findByOrderNumber($orderNumber) {
        return $this->findBy('order_number', $orderNumber);
    }
    
    /**
     * Busca pedidos de um usuário
     */
    public function getOrdersByUser($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC";
        return $this->db()->select($sql, ['user_id' => $userId]);
    }
    
    /**
     * Busca pedidos recentes
     */
    public function getRecentOrders($limit = 5) {
        $sql = "SELECT o.*, u.name as customer_name 
                FROM {$this->table} o 
                LEFT JOIN users u ON o.user_id = u.id 
                ORDER BY o.created_at DESC 
                LIMIT {$limit}";
        return $this->db()->select($sql);
    }
    
    /**
     * Busca pedidos que precisam ser impressos
     */
    public function getPendingPrintOrders() {
        $sql = "SELECT o.*, u.name as customer_name 
                FROM {$this->table} o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.status = 'validating' 
                ORDER BY o.created_at ASC";
        return $this->db()->select($sql);
    }
    
    /**
     * Busca pedidos atualmente em impressão
     */
    public function getCurrentlyPrintingOrders() {
        $sql = "SELECT o.*, u.name as customer_name,
                TIMEDIFF(NOW(), o.print_start_date) as elapsed_time,
                o.estimated_print_time_hours * 3600 - TIME_TO_SEC(TIMEDIFF(NOW(), o.print_start_date)) as remaining_seconds
                FROM {$this->table} o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.status = 'printing' 
                ORDER BY o.print_start_date ASC";
        return $this->db()->select($sql);
    }
    
    /**
     * Verifica se um pedido possui itens sob encomenda
     * 
     * @param int $orderId ID do pedido
     * @return bool
     */
    public function hasCustomItems($orderId) {
        $sql = "SELECT COUNT(*) as count 
                FROM order_items 
                WHERE order_id = :order_id 
                AND is_stock_item = 0";
        
        $result = $this->db()->select($sql, ['order_id' => $orderId]);
        return ($result[0]['count'] > 0);
    }
    
    /**
     * Verifica se um pedido possui apenas itens de estoque (pronta entrega)
     * 
     * @param int $orderId ID do pedido
     * @return bool
     */
    public function hasOnlyStockItems($orderId) {
        $sql = "SELECT COUNT(*) as count 
                FROM order_items 
                WHERE order_id = :order_id 
                AND is_stock_item = 0";
        
        $result = $this->db()->select($sql, ['order_id' => $orderId]);
        return ($result[0]['count'] == 0);
    }
    
    /**
     * Inicia a impressão de um pedido
     * 
     * @param int $orderId ID do pedido
     * @return bool Sucesso da operação
     */
    public function startPrinting($orderId) {
        // Verificar se o pedido pode iniciar impressão (deve estar em status 'validating')
        $order = $this->find($orderId);
        if (!$order || $order['status'] !== 'validating') {
            return false;
        }
        
        // Atualizar pedido
        $this->update($orderId, [
            'status' => 'printing',
            'print_start_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Adicionar nota ao histórico
        $this->addNote($orderId, "Impressão 3D iniciada.");
        
        return true;
    }
    
    /**
     * Finaliza a impressão de um pedido
     * 
     * @param int $orderId ID do pedido
     * @return bool Sucesso da operação
     */
    public function finishPrinting($orderId) {
        // Verificar se o pedido está em impressão
        $order = $this->find($orderId);
        if (!$order || $order['status'] !== 'printing') {
            return false;
        }
        
        // Atualizar pedido
        $this->update($orderId, [
            'status' => 'finishing',
            'print_finish_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Adicionar nota ao histórico
        $this->addNote($orderId, "Impressão 3D concluída. Pedido em acabamento.");
        
        return true;
    }
    
    /**
     * Marca um pedido como finalizado e pronto para envio
     * 
     * @param int $orderId ID do pedido
     * @return bool Sucesso da operação
     */
    public function finishProcessing($orderId) {
        // Verificar se o pedido está em acabamento
        $order = $this->find($orderId);
        if (!$order || $order['status'] !== 'finishing') {
            return false;
        }
        
        // Atualizar pedido
        $this->update($orderId, [
            'status' => 'pending',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Adicionar nota ao histórico
        $this->addNote($orderId, "Acabamento e preparo concluídos. Pedido pronto para envio.");
        
        return true;
    }
    
    /**
     * Conta o total de pedidos
     */
    public function countAll() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->db()->select($sql);
        return $result[0]['total'];
    }
    
    /**
     * Conta pedidos por status
     */
    public function countByStatus($status) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = :status";
        $result = $this->db()->select($sql, ['status' => $status]);
        return $result[0]['total'];
    }
    
    /**
     * Calcula tempo total de impressão pendente
     */
    public function getTotalPendingPrintTime() {
        $sql = "SELECT SUM(estimated_print_time_hours) as total FROM {$this->table} 
                WHERE status IN ('validating', 'printing')";
        $result = $this->db()->select($sql);
        return floatval($result[0]['total'] ?? 0);
    }
    
    /**
     * Calcula o total de vendas
     * @param string $period today|week|month|all
     */
    public function getTotalSales($period = 'all') {
        $whereClause = "WHERE payment_status = 'paid'";
        $params = [];
        
        if ($period === 'today') {
            $whereClause .= " AND DATE(created_at) = CURDATE()";
        } else if ($period === 'week') {
            $whereClause .= " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        } else if ($period === 'month') {
            $whereClause .= " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }
        
        $sql = "SELECT SUM(total) as total FROM {$this->table} {$whereClause}";
        $result = $this->db()->select($sql, $params);
        
        return $result[0]['total'] ?: 0;
    }
    
    /**
     * Obtém vendas por período
     * @param string $startDate Data inicial (Y-m-d)
     * @param string $endDate Data final (Y-m-d)
     * @param string $groupBy daily|monthly|yearly
     */
    public function getSalesByDateRange($startDate, $endDate, $groupBy = 'daily') {
        $dateFormat = "DATE(created_at)";
        
        if ($groupBy === 'monthly') {
            $dateFormat = "DATE_FORMAT(created_at, '%Y-%m')";
        } else if ($groupBy === 'yearly') {
            $dateFormat = "YEAR(created_at)";
        }
        
        $sql = "SELECT 
                    {$dateFormat} as date,
                    COUNT(*) as count,
                    SUM(total) as total
                FROM {$this->table}
                WHERE 
                    created_at BETWEEN :start_date AND :end_date
                    AND payment_status = 'paid'
                GROUP BY date
                ORDER BY date ASC";
        
        return $this->db()->select($sql, [
            'start_date' => $startDate,
            'end_date' => $endDate . ' 23:59:59'
        ]);
    }
    
    /**
     * Obtém vendas por categoria
     */
    public function getSalesByCategory() {
        $sql = "SELECT 
                    c.name as category,
                    COUNT(oi.id) as count,
                    SUM(oi.price * oi.quantity) as total
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN categories c ON p.category_id = c.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.payment_status = 'paid'
                GROUP BY c.id
                ORDER BY total DESC";
        
        return $this->db()->select($sql);
    }
    
    /**
     * Obtém itens de um pedido
     */
    public function getOrderItems($orderId) {
        $sql = "SELECT oi.*, 
                p.is_tested, 
                p.stock,
                (SELECT image FROM product_images WHERE product_id = oi.product_id AND is_main = 1 LIMIT 1) as image,
                CASE WHEN oi.is_stock_item = 1 THEN 'Pronta Entrega' ELSE 'Sob Encomenda' END as availability,
                (SELECT name FROM filament_colors WHERE id = oi.selected_color) as color_name,
                (SELECT hex_code FROM filament_colors WHERE id = oi.selected_color) as color_hex
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :order_id";
        return $this->db()->select($sql, ['order_id' => $orderId]);
    }
    
    /**
     * Adiciona itens a um pedido
     */
    public function addOrderItem($orderId, $data) {
        $data['order_id'] = $orderId;
        
        $sql = "INSERT INTO order_items (
                    order_id, product_id, product_name, quantity, price, 
                    selected_scale, selected_filament, selected_color, 
                    customer_model_id, print_time_hours, is_stock_item, customization_data
                ) VALUES (
                    :order_id, :product_id, :product_name, :quantity, :price,
                    :selected_scale, :selected_filament, :selected_color,
                    :customer_model_id, :print_time_hours, :is_stock_item, :customization_data
                )";
        
        $this->db()->query($sql, [
            'order_id' => $data['order_id'],
            'product_id' => $data['product_id'],
            'product_name' => $data['product_name'],
            'quantity' => $data['quantity'],
            'price' => $data['price'],
            'selected_scale' => $data['selected_scale'] ?? null,
            'selected_filament' => $data['selected_filament'] ?? null,
            'selected_color' => $data['selected_color'] ?? null,
            'customer_model_id' => $data['customer_model_id'] ?? null,
            'print_time_hours' => $data['print_time_hours'] ?? null,
            'is_stock_item' => $data['is_stock_item'] ?? 1,
            'customization_data' => $data['customization_data'] ?? null
        ]);
    }
    
    /**
     * Atualiza o status de um pedido
     */
    public function updateStatus($orderId, $status, $note = null) {
        // Atualizar status
        $this->update($orderId, [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Se houver nota, adicionar ao histórico
        if ($note) {
            $this->addNote($orderId, $note);
        }
        
        return true;
    }
    
    /**
     * Atualiza o status de pagamento de um pedido
     */
    public function updatePaymentStatus($orderId, $status, $note = null) {
        // Atualizar status de pagamento
        $this->update($orderId, [
            'payment_status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Se houver nota, adicionar ao histórico
        if ($note) {
            $this->addNote($orderId, $note);
        }
        
        return true;
    }
    
    /**
     * Adiciona código de rastreio ao pedido
     */
    public function addTrackingCode($orderId, $trackingCode, $note = null) {
        // Atualizar pedido
        $this->update($orderId, [
            'tracking_code' => $trackingCode,
            'status' => 'shipped',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Se houver nota, adicionar ao histórico
        if (!$note) {
            $note = "Pedido enviado. Código de rastreio: {$trackingCode}";
        }
        
        $this->addNote($orderId, $note);
        
        return true;
    }
    
    /**
     * Adiciona uma nota/histórico ao pedido
     */
    public function addNote($orderId, $content) {
        $sql = "INSERT INTO order_notes (order_id, content, created_by)
                VALUES (:order_id, :content, :created_by)";
        
        $createdBy = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
        
        $this->db()->query($sql, [
            'order_id' => $orderId,
            'content' => $content,
            'created_by' => $createdBy
        ]);
    }
    
    /**
     * Obtém as notas/histórico de um pedido
     */
    public function getNotes($orderId) {
        $sql = "SELECT n.*, u.name as user_name
                FROM order_notes n
                LEFT JOIN users u ON n.created_by = u.id
                WHERE n.order_id = :order_id
                ORDER BY n.created_at DESC";
        
        return $this->db()->select($sql, ['order_id' => $orderId]);
    }
    
    /**
     * Cancela um pedido
     */
    public function cancelOrder($orderId, $reason) {
        // Atualizar status
        $this->update($orderId, [
            'status' => 'canceled',
            'payment_status' => 'canceled',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Adicionar nota com motivo do cancelamento
        $this->addNote($orderId, "Pedido cancelado. Motivo: {$reason}");
        
        return true;
    }
    
    /**
     * Obtém o tempo de impressão total para um pedido
     */
    public function getTotalPrintTime($orderId) {
        $sql = "SELECT SUM(print_time_hours) as total_time 
                FROM order_items 
                WHERE order_id = :order_id";
                
        $result = $this->db()->select($sql, ['order_id' => $orderId]);
        return floatval($result[0]['total_time'] ?? 0);
    }
    
    /**
     * Calcula a data estimada de conclusão de um pedido sob encomenda
     */
    public function calculateEstimatedCompletionDate($orderId) {
        $order = $this->find($orderId);
        if (!$order) {
            return null;
        }
        
        $totalPrintTime = $this->getTotalPrintTime($orderId);
        if ($totalPrintTime <= 0) {
            return null;
        }
        
        // Estimar dias de impressão (considerando 8 horas de impressão por dia)
        $estimatedPrintingDays = ceil($totalPrintTime / 8);
        
        // Adicionar 1 dia para preparação (validação do pedido)
        // Adicionar dias de impressão
        // Adicionar 1 dia para acabamento e embalagem
        $totalProcessingDays = 1 + $estimatedPrintingDays + 1;
        
        // Calcular data estimada (dias úteis)
        $startDate = new DateTime($order['created_at']);
        $daysAdded = 0;
        
        while ($daysAdded < $totalProcessingDays) {
            $startDate->modify('+1 day');
            
            // Se não for fim de semana (6 = sábado, 0 = domingo)
            $weekDay = $startDate->format('w');
            if ($weekDay != 0 && $weekDay != 6) {
                $daysAdded++;
            }
        }
        
        return $startDate->format('Y-m-d');
    }
    
    /**
     * Filtra pedidos com itens de impressão 3D
     */
    public function getCustomPrintingOrders($status = null, $limit = null) {
        $whereClause = "WHERE o.estimated_print_time_hours > 0";
        $params = [];
        
        if ($status) {
            $whereClause .= " AND o.status = :status";
            $params['status'] = $status;
        }
        
        $limitClause = $limit ? "LIMIT {$limit}" : "";
        
        $sql = "SELECT o.*, u.name as customer_name 
                FROM {$this->table} o 
                LEFT JOIN users u ON o.user_id = u.id 
                {$whereClause}
                ORDER BY o.created_at DESC
                {$limitClause}";
                
        return $this->db()->select($sql, $params);
    }
}