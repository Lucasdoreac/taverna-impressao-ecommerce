<?php
/**
 * OrderModel - Modelo para pedidos
 */
class OrderModel extends Model {
    protected $table = 'orders';
    protected $fillable = [
        'user_id', 'order_number', 'status', 'payment_method', 'payment_status',
        'shipping_address_id', 'shipping_method', 'shipping_cost', 'subtotal',
        'discount', 'total', 'notes', 'tracking_code'
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
        $sql = "SELECT * FROM order_items WHERE order_id = :order_id";
        return $this->db()->select($sql, ['order_id' => $orderId]);
    }
    
    /**
     * Adiciona itens a um pedido
     */
    public function addOrderItem($orderId, $data) {
        $data['order_id'] = $orderId;
        
        $sql = "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, customization_data)
                VALUES (:order_id, :product_id, :product_name, :quantity, :price, :customization_data)";
        
        $this->db()->query($sql, [
            'order_id' => $data['order_id'],
            'product_id' => $data['product_id'],
            'product_name' => $data['product_name'],
            'quantity' => $data['quantity'],
            'price' => $data['price'],
            'customization_data' => $data['customization_data'] ?? null
        ]);
    }
    
    /**
     * Atualiza o status de um pedido
     */
    public function updateStatus($orderId, $status, $note = null) {
        // Atualizar status
        $this->update($orderId, ['status' => $status]);
        
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
        $this->update($orderId, ['payment_status' => $status]);
        
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
            'status' => 'shipped'
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
            'payment_status' => 'canceled'
        ]);
        
        // Adicionar nota com motivo do cancelamento
        $this->addNote($orderId, "Pedido cancelado. Motivo: {$reason}");
        
        return true;
    }
}