<?php
/**
 * AdminController - Controlador base para o painel administrativo
 */
class AdminController {
    
    /**
     * Construtor - verifica se o usuário é administrador
     */
    public function __construct() {
        // Verificar se o usuário está logado e é administrador
        AdminHelper::checkAdminAccess();
    }
    
    /**
     * Exibe o dashboard administrativo
     */
    public function index() {
        // Obter estatísticas gerais
        $stats = $this->getStatistics();
        
        // Obter pedidos recentes
        $orderModel = new OrderModel();
        $recentOrders = $orderModel->getRecent(5);
        
        // Renderizar view
        require_once VIEWS_PATH . '/admin/dashboard.php';
    }
    
    /**
     * Obtém estatísticas gerais para o dashboard
     */
    private function getStatistics() {
        $db = Database::getInstance();
        $stats = [];
        
        // Total de pedidos
        $ordersResult = $db->select("SELECT 
            COUNT(*) as total_orders,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
            COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
            COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_orders,
            COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
            COUNT(CASE WHEN status = 'canceled' THEN 1 END) as canceled_orders,
            SUM(total) as total_revenue,
            AVG(total) as average_order
            FROM orders");
        
        if ($ordersResult) {
            $stats['orders'] = $ordersResult[0];
        } else {
            $stats['orders'] = [
                'total_orders' => 0,
                'pending_orders' => 0,
                'processing_orders' => 0,
                'shipped_orders' => 0,
                'delivered_orders' => 0,
                'canceled_orders' => 0,
                'total_revenue' => 0,
                'average_order' => 0
            ];
        }
        
        // Total de produtos
        $productsResult = $db->select("SELECT 
            COUNT(*) as total_products,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_products,
            COUNT(CASE WHEN is_customizable = 1 THEN 1 END) as customizable_products,
            AVG(price) as average_price,
            SUM(stock) as total_stock
            FROM products");
        
        if ($productsResult) {
            $stats['products'] = $productsResult[0];
        } else {
            $stats['products'] = [
                'total_products' => 0,
                'active_products' => 0,
                'customizable_products' => 0,
                'average_price' => 0,
                'total_stock' => 0
            ];
        }
        
        // Total de usuários
        $usersResult = $db->select("SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_users,
            COUNT(CASE WHEN role = 'customer' THEN 1 END) as customer_users
            FROM users");
        
        if ($usersResult) {
            $stats['users'] = $usersResult[0];
        } else {
            $stats['users'] = [
                'total_users' => 0,
                'admin_users' => 0,
                'customer_users' => 0
            ];
        }
        
        return $stats;
    }
}
