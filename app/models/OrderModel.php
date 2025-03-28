<?php
/**
 * OrderModel - Modelo para pedidos
 */
class OrderModel extends Model {
    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id', 'order_number', 'status', 'payment_method', 'payment_status',
        'shipping_address_id', 'shipping_method', 'shipping_cost', 'subtotal',
        'discount', 'total', 'notes', 'tracking_code'
    ];
    
    /**
     * Busca um pedido pelo número de pedido
     */
    public function findByOrderNumber($orderNumber) {
        return $this->findBy('order_number', $orderNumber);
    }
    
    /**
     * Busca pedidos de um usuário específico
     */
    public function getByUser($userId, $page = 1, $limit = 10) {
        return $this->paginate($page, $limit, 'user_id = :user_id', ['user_id' => $userId]);
    }
    
    /**
     * Busca os pedidos recentes
     */
    public function getRecent($limit = 5) {
        $sql = "SELECT o.*, u.name as user_name, u.email as user_email 
                FROM {$this->table} o 
                LEFT JOIN users u ON o.user_id = u.id 
                ORDER BY o.created_at DESC 
                LIMIT {$limit}";
        
        return $this->db()->select($sql);
    }
    
    /**
     * Busca pedidos com filtros
     */
    public function getWithFilters($filters = [], $page = 1, $limit = 20) {
        $where = '1=1';
        $params = [];
        
        // Filtro por status
        if (!empty($filters['status'])) {
            $where .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }
        
        // Filtro por método de pagamento
        if (!empty($filters['payment_method'])) {
            $where .= ' AND payment_method = :payment_method';
            $params['payment_method'] = $filters['payment_method'];
        }
        
        // Filtro por status de pagamento
        if (!empty($filters['payment_status'])) {
            $where .= ' AND payment_status = :payment_status';
            $params['payment_status'] = $filters['payment_status'];
        }
        
        // Filtro por data inicial
        if (!empty($filters['date_from'])) {
            $where .= ' AND created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        
        // Filtro por data final
        if (!empty($filters['date_to'])) {
            $where .= ' AND created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        
        // Filtro por número de pedido
        if (!empty($filters['order_number'])) {
            $where .= ' AND order_number LIKE :order_number';
            $params['order_number'] = '%' . $filters['order_number'] . '%';
        }
        
        // Filtro por cliente
        if (!empty($filters['customer'])) {
            $where .= ' AND user_id IN (SELECT id FROM users WHERE name LIKE :customer OR email LIKE :customer)';
            $params['customer'] = '%' . $filters['customer'] . '%';
        }
        
        // Buscar com paginação
        $offset = ($page - 1) * $limit;
        
        // Contar total de registros
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$where}";
        $countResult = $this->db()->select($countSql, $params);
        $total = $countResult[0]['total'];
        
        // Buscar pedidos
        $sql = "SELECT o.*, u.name as user_name, u.email as user_email 
                FROM {$this->table} o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE {$where} 
                ORDER BY o.created_at DESC 
                LIMIT {$offset}, {$limit}";
        
        $items = $this->db()->select($sql, $params);
        
        return [
            'items' => $items,
            'total' => $total,
            'currentPage' => $page,
            'perPage' => $limit,
            'lastPage' => ceil($total / $limit),
            'from' => $offset + 1,
            'to' => min($offset + $limit, $total)
        ];
    }
    
    /**
     * Obtém os itens de um pedido
     */
    public function getItems($orderId) {
        $sql = "SELECT oi.*, p.name as product_name, p.slug as product_slug 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = :order_id";
        
        return $this->db()->select($sql, ['order_id' => $orderId]);
    }
    
    /**
     * Obtém o endereço de entrega de um pedido
     */
    public function getShippingAddress($addressId) {
        if (!$addressId) {
            return null;
        }
        
        $sql = "SELECT * FROM addresses WHERE id = :id LIMIT 1";
        $result = $this->db()->select($sql, ['id' => $addressId]);
        
        return $result ? $result[0] : null;
    }
    
    /**
     * Atualiza o status de um pedido
     */
    public function updateStatus($orderId, $status) {
        return $this->update($orderId, ['status' => $status]);
    }
    
    /**
     * Atualiza o status de pagamento de um pedido
     */
    public function updatePaymentStatus($orderId, $paymentStatus) {
        return $this->update($orderId, ['payment_status' => $paymentStatus]);
    }
    
    /**
     * Adiciona um código de rastreamento ao pedido
     */
    public function addTrackingCode($orderId, $trackingCode) {
        return $this->update($orderId, [
            'tracking_code' => $trackingCode,
            'status' => 'shipped'
        ]);
    }
    
    /**
     * Obtém estatísticas de vendas por período
     */
    public function getSalesStats($period = 'monthly') {
        $groupBy = '';
        $dateFormat = '';
        
        switch ($period) {
            case 'daily':
                $groupBy = 'DATE(created_at)';
                $dateFormat = '%Y-%m-%d';
                break;
            case 'weekly':
                $groupBy = 'YEAR(created_at), WEEK(created_at)';
                $dateFormat = '%x-W%v';
                break;
            case 'monthly':
                $groupBy = 'YEAR(created_at), MONTH(created_at)';
                $dateFormat = '%Y-%m';
                break;
            case 'yearly':
                $groupBy = 'YEAR(created_at)';
                $dateFormat = '%Y';
                break;
            default:
                $groupBy = 'YEAR(created_at), MONTH(created_at)';
                $dateFormat = '%Y-%m';
        }
        
        $sql = "SELECT 
                DATE_FORMAT(created_at, '{$dateFormat}') as period,
                COUNT(*) as order_count,
                SUM(total) as revenue,
                AVG(total) as average_order
                FROM {$this->table}
                WHERE status != 'canceled'
                GROUP BY {$groupBy}
                ORDER BY created_at DESC
                LIMIT 12";
        
        return $this->db()->select($sql);
    }
    
    /**
     * Obtém estatísticas de produtos mais vendidos
     */
    public function getTopProducts($limit = 10) {
        $sql = "SELECT 
                oi.product_id,
                p.name as product_name,
                p.slug as product_slug,
                SUM(oi.quantity) as total_quantity,
                COUNT(DISTINCT oi.order_id) as order_count,
                SUM(oi.price * oi.quantity) as total_revenue
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status != 'canceled'
                GROUP BY oi.product_id
                ORDER BY total_quantity DESC
                LIMIT {$limit}";
        
        return $this->db()->select($sql);
    }
    
    /**
     * Gera um número de pedido único
     */
    public function generateOrderNumber() {
        $prefix = 'TAV';
        $timestamp = date('YmdHis');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        
        return $prefix . $timestamp . $random;
    }
}
