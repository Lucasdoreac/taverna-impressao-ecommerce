<?php
/**
 * DashboardModel
 * 
 * Modelo responsável por fornecer dados e métricas para o dashboard administrativo
 */
class DashboardModel extends Model
{
    /**
     * Construtor
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Obter métricas de vendas para um período específico
     * 
     * @param string $startDate Data inicial (formato Y-m-d)
     * @param string $endDate Data final (formato Y-m-d)
     * @param string $period Período (day, week, month, year)
     * @return array Métricas de vendas
     */
    public function getSalesMetrics($startDate, $endDate, $period = 'month')
    {
        // Calcular total de vendas no período
        $totalSales = $this->getTotalSales($startDate, $endDate);
        
        // Calcular vendas no período anterior (para comparação)
        $daysDiff = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24);
        $prevStartDate = date('Y-m-d', strtotime($startDate . ' -' . $daysDiff . ' days'));
        $prevEndDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $prevTotalSales = $this->getTotalSales($prevStartDate, $prevEndDate);
        
        // Calcular crescimento percentual
        $growthPercent = 0;
        if ($prevTotalSales > 0) {
            $growthPercent = (($totalSales - $prevTotalSales) / $prevTotalSales) * 100;
        }
        
        // Obter número de pedidos no período
        $totalOrders = $this->getTotalOrders($startDate, $endDate);
        
        // Obter ticket médio
        $avgOrderValue = 0;
        if ($totalOrders > 0) {
            $avgOrderValue = $totalSales / $totalOrders;
        }
        
        return [
            'totalSales' => $totalSales,
            'prevTotalSales' => $prevTotalSales,
            'growthPercent' => $growthPercent,
            'totalOrders' => $totalOrders,
            'avgOrderValue' => $avgOrderValue,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'period' => $period
        ];
    }
    
    /**
     * Obter total de vendas para um período
     * 
     * @param string $startDate Data inicial (formato Y-m-d)
     * @param string $endDate Data final (formato Y-m-d)
     * @return float Total de vendas
     */
    public function getTotalSales($startDate, $endDate)
    {
        $query = "SELECT SUM(total) AS total_sales 
                 FROM orders 
                 WHERE created_at BETWEEN :start_date AND :end_date 
                 AND status != 'canceled'";
        
        $params = [
            ':start_date' => $startDate . ' 00:00:00',
            ':end_date' => $endDate . ' 23:59:59'
        ];
        
        $result = $this->db->query($query, $params);
        
        return isset($result[0]['total_sales']) ? (float)$result[0]['total_sales'] : 0;
    }
    
    /**
     * Obter número total de pedidos para um período
     * 
     * @param string $startDate Data inicial (formato Y-m-d)
     * @param string $endDate Data final (formato Y-m-d)
     * @return int Total de pedidos
     */
    public function getTotalOrders($startDate, $endDate)
    {
        $query = "SELECT COUNT(*) AS total_orders 
                 FROM orders 
                 WHERE created_at BETWEEN :start_date AND :end_date 
                 AND status != 'canceled'";
        
        $params = [
            ':start_date' => $startDate . ' 00:00:00',
            ':end_date' => $endDate . ' 23:59:59'
        ];
        
        $result = $this->db->query($query, $params);
        
        return isset($result[0]['total_orders']) ? (int)$result[0]['total_orders'] : 0;
    }
    
    /**
     * Obter vendas por categoria
     * 
     * @param string $startDate Data inicial (formato Y-m-d)
     * @param string $endDate Data final (formato Y-m-d)
     * @return array Vendas por categoria
     */
    public function getSalesByCategory($startDate, $endDate)
    {
        $query = "SELECT c.name AS category_name, 
                       SUM(oi.price * oi.quantity) AS total_sales,
                       COUNT(DISTINCT o.id) AS order_count
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                JOIN categories c ON p.category_id = c.id
                WHERE o.created_at BETWEEN :start_date AND :end_date 
                AND o.status != 'canceled'
                GROUP BY c.id
                ORDER BY total_sales DESC";
        
        $params = [
            ':start_date' => $startDate . ' 00:00:00',
            ':end_date' => $endDate . ' 23:59:59'
        ];
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Obter vendas por produto
     * 
     * @param string $startDate Data inicial (formato Y-m-d)
     * @param string $endDate Data final (formato Y-m-d)
     * @param int $limit Limite de produtos
     * @return array Vendas por produto
     */
    public function getSalesByProduct($startDate, $endDate, $limit = 10)
    {
        $query = "SELECT p.id, p.name AS product_name, 
                       SUM(oi.quantity) AS quantity_sold,
                       SUM(oi.price * oi.quantity) AS total_sales,
                       COUNT(DISTINCT o.id) AS order_count,
                       p.image
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                WHERE o.created_at BETWEEN :start_date AND :end_date 
                AND o.status != 'canceled'
                GROUP BY p.id
                ORDER BY total_sales DESC
                LIMIT :limit";
        
        $params = [
            ':start_date' => $startDate . ' 00:00:00',
            ':end_date' => $endDate . ' 23:59:59',
            ':limit' => $limit
        ];
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Obter dados para gráfico de vendas
     * 
     * @param string $startDate Data inicial (formato Y-m-d)
     * @param string $endDate Data final (formato Y-m-d)
     * @param string $groupBy Agrupamento (day, week, month, year)
     * @return array Dados do gráfico de vendas
     */
    public function getSalesChartData($startDate, $endDate, $groupBy = 'day')
    {
        $format = '';
        
        switch ($groupBy) {
            case 'day':
                $format = '%Y-%m-%d';
                break;
            case 'week':
                $format = '%Y-%u'; // Ano-Semana
                break;
            case 'month':
                $format = '%Y-%m';
                break;
            case 'year':
                $format = '%Y';
                break;
            default:
                $format = '%Y-%m-%d';
        }
        
        $query = "SELECT 
                    DATE_FORMAT(created_at, :format) AS date_group,
                    SUM(total) AS total_sales,
                    COUNT(*) AS order_count
                FROM orders
                WHERE created_at BETWEEN :start_date AND :end_date
                AND status != 'canceled'
                GROUP BY date_group
                ORDER BY date_group ASC";
        
        $params = [
            ':format' => $format,
            ':start_date' => $startDate . ' 00:00:00',
            ':end_date' => $endDate . ' 23:59:59'
        ];
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Obter métricas de impressão 3D
     * 
     * @return array Métricas de impressão 3D
     */
    public function getPrintingMetrics()
    {
        // Período para métricas (últimos 30 dias por padrão)
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');
        
        // Total de jobs de impressão
        $query = "SELECT 
                    COUNT(*) AS total_jobs,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_jobs,
                    SUM(CASE WHEN status = 'printing' THEN 1 ELSE 0 END) AS active_jobs,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_jobs,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_jobs,
                    SUM(estimated_print_time_hours) AS total_print_hours,
                    SUM(filament_usage_grams) AS total_filament_grams
                FROM print_queue
                WHERE created_at BETWEEN :start_date AND :end_date";
        
        $params = [
            ':start_date' => $startDate . ' 00:00:00',
            ':end_date' => $endDate . ' 23:59:59'
        ];
        
        $result = $this->db->query($query, $params);
        
        // Calcular taxa de sucesso
        $metrics = $result[0];
        $metrics['success_rate'] = 0;
        
        if (($metrics['completed_jobs'] + $metrics['failed_jobs']) > 0) {
            $metrics['success_rate'] = ($metrics['completed_jobs'] / ($metrics['completed_jobs'] + $metrics['failed_jobs'])) * 100;
        }
        
        return $metrics;
    }
    
    /**
     * Obter status atual das impressoras
     * 
     * @return array Status das impressoras
     */
    public function getPrinterStatus()
    {
        $query = "SELECT 
                    p.id, p.name, p.model, p.status,
                    COALESCE(pq.product_name, '') AS current_job,
                    COALESCE(pq.estimated_print_time_hours, 0) AS current_job_time,
                    COALESCE(pq.progress, 0) AS current_job_progress,
                    COALESCE(pq.filament_type, '') AS current_filament_type,
                    COALESCE(pq.filament_color, '') AS current_filament_color
                FROM printers p
                LEFT JOIN print_queue pq ON p.id = pq.printer_id AND pq.status = 'printing'
                ORDER BY p.name ASC";
        
        return $this->db->query($query);
    }
    
    /**
     * Obter uso de filamento
     * 
     * @param string $period Período (month, year, all)
     * @return array Uso de filamento por tipo e cor
     */
    public function getFilamentUsage($period = 'month')
    {
        $startDate = '';
        
        switch ($period) {
            case 'month':
                $startDate = date('Y-m-d', strtotime('-30 days'));
                break;
            case 'year':
                $startDate = date('Y-m-d', strtotime('-1 year'));
                break;
            case 'all':
                $startDate = '2000-01-01'; // Data antiga para incluir todos
                break;
            default:
                $startDate = date('Y-m-d', strtotime('-30 days'));
        }
        
        $endDate = date('Y-m-d');
        
        // Uso por tipo de filamento
        $queryByType = "SELECT 
                         filament_type,
                         SUM(filament_usage_grams) AS total_grams
                       FROM print_queue
                       WHERE (status = 'completed' OR status = 'printing')
                       AND created_at BETWEEN :start_date AND :end_date
                       GROUP BY filament_type
                       ORDER BY total_grams DESC";
        
        $paramsByType = [
            ':start_date' => $startDate . ' 00:00:00',
            ':end_date' => $endDate . ' 23:59:59'
        ];
        
        $usageByType = $this->db->query($queryByType, $paramsByType);
        
        // Uso por cor de filamento
        $queryByColor = "SELECT 
                          filament_color,
                          SUM(filament_usage_grams) AS total_grams
                        FROM print_queue
                        WHERE (status = 'completed' OR status = 'printing')
                        AND created_at BETWEEN :start_date AND :end_date
                        GROUP BY filament_color
                        ORDER BY total_grams DESC";
        
        $paramsByColor = [
            ':start_date' => $startDate . ' 00:00:00',
            ':end_date' => $endDate . ' 23:59:59'
        ];
        
        $usageByColor = $this->db->query($queryByColor, $paramsByColor);
        
        return [
            'byType' => $usageByType,
            'byColor' => $usageByColor,
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }
    
    /**
     * Obter status atual da fila de impressão
     * 
     * @return array Status da fila de impressão
     */
    public function getPrintQueueStatus()
    {
        $query = "SELECT 
                    status,
                    COUNT(*) AS count,
                    SUM(estimated_print_time_hours) AS total_hours,
                    SUM(filament_usage_grams) AS total_grams
                  FROM print_queue
                  WHERE status IN ('pending', 'scheduled', 'printing', 'paused')
                  GROUP BY status";
        
        return $this->db->query($query);
    }
    
    /**
     * Obter métricas de produtos
     * 
     * @return array Métricas de produtos
     */
    public function getProductMetrics()
    {
        $query = "SELECT 
                    COUNT(*) AS total_products,
                    SUM(CASE WHEN stock > 0 THEN 1 ELSE 0 END) AS products_in_stock,
                    SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) AS products_out_of_stock,
                    SUM(CASE WHEN model_file IS NOT NULL AND model_file != '' THEN 1 ELSE 0 END) AS products_with_3d_model
                  FROM products";
        
        $result = $this->db->query($query);
        
        // Adicionar contagem de produtos testados e sob encomenda
        $queryCustomProducts = "SELECT 
                                 SUM(CASE WHEN is_tested = 1 THEN 1 ELSE 0 END) AS tested_products,
                                 SUM(CASE WHEN is_tested = 0 THEN 1 ELSE 0 END) AS custom_order_products
                               FROM products
                               WHERE is_3d_printable = 1";
        
        $customResult = $this->db->query($queryCustomProducts);
        
        // Mesclar resultados
        return array_merge($result[0], $customResult[0]);
    }
    
    /**
     * Obter alertas de estoque
     * 
     * @param int $threshold Limite mínimo de estoque
     * @return array Produtos com estoque baixo
     */
    public function getStockAlerts($threshold = 5)
    {
        $query = "SELECT 
                    id, name, sku, stock, price, image,
                    (SELECT COUNT(*) FROM order_items WHERE product_id = products.id) AS order_count
                  FROM products
                  WHERE stock <= :threshold AND stock > 0
                  ORDER BY stock ASC";
        
        $params = [':threshold' => $threshold];
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Obter produtos populares
     * 
     * @param int $limit Limite de produtos
     * @return array Produtos populares
     */
    public function getPopularProducts($limit = 10)
    {
        $query = "SELECT 
                    p.id, p.name, p.sku, p.stock, p.price, p.image,
                    COUNT(oi.id) AS order_count,
                    SUM(oi.quantity) AS total_quantity_sold
                  FROM products p
                  JOIN order_items oi ON p.id = oi.product_id
                  JOIN orders o ON oi.order_id = o.id
                  WHERE o.status != 'canceled'
                  GROUP BY p.id
                  ORDER BY total_quantity_sold DESC
                  LIMIT :limit";
        
        $params = [':limit' => $limit];
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Obter produtos por categoria
     * 
     * @return array Produtos por categoria
     */
    public function getProductsByCategory()
    {
        $query = "SELECT 
                    c.id, c.name AS category_name,
                    COUNT(p.id) AS product_count
                  FROM categories c
                  LEFT JOIN products p ON c.id = p.category_id
                  GROUP BY c.id
                  ORDER BY product_count DESC";
        
        return $this->db->query($query);
    }
    
    /**
     * Obter status de estoque
     * 
     * @return array Status de estoque
     */
    public function getStockStatus()
    {
        $query = "SELECT 
                    SUM(stock) AS total_stock,
                    COUNT(*) AS total_products,
                    AVG(stock) AS avg_stock,
                    MAX(stock) AS max_stock,
                    MIN(stock) AS min_stock
                  FROM products";
        
        return $this->db->query($query);
    }
    
    /**
     * Obter métricas de clientes
     * 
     * @return array Métricas de clientes
     */
    public function getCustomerMetrics()
    {
        $query = "SELECT 
                    COUNT(*) AS total_customers,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS new_customers_30_days,
                    SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS active_customers_30_days
                  FROM users
                  WHERE role = 'customer'";
        
        return $this->db->query($query);
    }
    
    /**
     * Obter novos clientes
     * 
     * @param int $days Dias para considerar novos clientes
     * @return array Novos clientes
     */
    public function getNewCustomers($days = 30)
    {
        $query = "SELECT 
                    id, name, email, phone, created_at
                  FROM users
                  WHERE role = 'customer' 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                  ORDER BY created_at DESC";
        
        $params = [':days' => $days];
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Obter top clientes
     * 
     * @param int $limit Limite de clientes
     * @return array Top clientes
     */
    public function getTopCustomers($limit = 10)
    {
        $query = "SELECT 
                    u.id, u.name, u.email, u.phone,
                    COUNT(o.id) AS order_count,
                    SUM(o.total) AS total_spent
                  FROM users u
                  JOIN orders o ON u.id = o.user_id
                  WHERE u.role = 'customer' AND o.status != 'canceled'
                  GROUP BY u.id
                  ORDER BY total_spent DESC
                  LIMIT :limit";
        
        $params = [':limit' => $limit];
        
        return $this->db->query($query, $params);
    }
    
    /**
     * Obter usuários ativos
     * 
     * @param int $days Dias para considerar usuários ativos
     * @return int Número de usuários ativos
     */
    public function getActiveUsers($days = 30)
    {
        $query = "SELECT COUNT(*) AS active_users
                 FROM users
                 WHERE last_login >= DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $params = [':days' => $days];
        
        $result = $this->db->query($query, $params);
        
        return isset($result[0]['active_users']) ? (int)$result[0]['active_users'] : 0;
    }
}
