<?php
/**
 * AdminDashboardController - Controlador para o dashboard do painel administrativo
 */
class AdminDashboardController {
    private $orderModel;
    private $productModel;
    private $userModel;
    private $categoryModel;
    
    public function __construct() {
        // Verificar se é administrador
        AdminHelper::checkAdminAccess();
        
        // Carregar modelos
        $this->orderModel = new OrderModel();
        $this->productModel = new ProductModel();
        $this->userModel = new UserModel();
        $this->categoryModel = new CategoryModel();
    }
    
    /**
     * Exibe o dashboard do painel administrativo
     */
    public function index() {
        // Obter estatísticas gerais
        $stats = $this->getStats();
        
        // Obter período para relatórios
        $period = isset($_GET['period']) ? $_GET['period'] : 'month';
        
        // Obter dados para gráficos
        $salesChart = $this->getSalesChartData($period);
        $categoriesChart = $this->getCategoriesChartData();
        
        // Buscar pedidos recentes
        $recentOrders = $this->orderModel->getRecentOrders(5);
        
        // Buscar usuários recentes
        $recentUsers = $this->userModel->getRecentUsers(5);
        
        // Buscar produtos mais vendidos
        $topProducts = $this->productModel->getTopSellingProducts(5);
        
        // Dados para a view
        $data = [
            'stats' => $stats,
            'period' => $period,
            'salesChart' => $salesChart,
            'categoriesChart' => $categoriesChart,
            'recentOrders' => $recentOrders,
            'recentUsers' => $recentUsers,
            'topProducts' => $topProducts
        ];
        
        // Renderizar view
        require_once VIEWS_PATH . '/admin/dashboard.php';
    }
    
    /**
     * Obter estatísticas gerais
     */
    private function getStats() {
        $stats = [
            'orders' => [
                'total' => $this->orderModel->countAll(),
                'pending' => $this->orderModel->countByStatus('pending'),
                'processing' => $this->orderModel->countByStatus('processing'),
                'shipped' => $this->orderModel->countByStatus('shipped'),
                'delivered' => $this->orderModel->countByStatus('delivered'),
                'canceled' => $this->orderModel->countByStatus('canceled')
            ],
            'sales' => [
                'total' => $this->orderModel->getTotalSales(),
                'today' => $this->orderModel->getTotalSales('today'),
                'week' => $this->orderModel->getTotalSales('week'),
                'month' => $this->orderModel->getTotalSales('month')
            ],
            'users' => [
                'total' => $this->userModel->countAll(),
                'admin' => $this->userModel->countByRole('admin'),
                'customer' => $this->userModel->countByRole('customer'),
                'new_today' => $this->userModel->countNewUsers('today'),
                'new_week' => $this->userModel->countNewUsers('week'),
                'new_month' => $this->userModel->countNewUsers('month')
            ],
            'products' => [
                'total' => $this->productModel->countAll(),
                'active' => $this->productModel->countByStatus(true),
                'inactive' => $this->productModel->countByStatus(false),
                'out_of_stock' => $this->productModel->countOutOfStock(),
                'low_stock' => $this->productModel->countLowStock(5)
            ],
            'categories' => [
                'total' => $this->categoryModel->countAll(),
                'main' => $this->categoryModel->countMain(),
                'sub' => $this->categoryModel->countSub()
            ]
        ];
        
        return $stats;
    }
    
    /**
     * Obter dados para o gráfico de vendas
     */
    private function getSalesChartData($period = 'month') {
        $data = [];
        
        switch ($period) {
            case 'week':
                // Dados dos últimos 7 dias
                $endDate = date('Y-m-d');
                $startDate = date('Y-m-d', strtotime('-6 days'));
                
                $rangeDays = 7;
                $format = 'Y-m-d';
                $labelFormat = 'd/m';
                
                $sales = $this->orderModel->getSalesByDateRange($startDate, $endDate, 'daily');
                break;
                
            case 'month':
                // Dados dos últimos 30 dias
                $endDate = date('Y-m-d');
                $startDate = date('Y-m-d', strtotime('-29 days'));
                
                $rangeDays = 30;
                $format = 'Y-m-d';
                $labelFormat = 'd/m';
                
                $sales = $this->orderModel->getSalesByDateRange($startDate, $endDate, 'daily');
                break;
                
            case 'year':
                // Dados dos últimos 12 meses
                $endMonth = date('Y-m');
                $startMonth = date('Y-m', strtotime('-11 months'));
                
                $rangeDays = 12;
                $format = 'Y-m';
                $labelFormat = 'm/Y';
                
                $sales = $this->orderModel->getSalesByDateRange($startMonth . '-01', date('Y-m-t', strtotime($endMonth)), 'monthly');
                break;
                
            default:
                // Padrão: dados do mês atual
                $endDate = date('Y-m-d');
                $startDate = date('Y-m-01');
                
                $rangeDays = intval(date('t'));
                $format = 'Y-m-d';
                $labelFormat = 'd/m';
                
                $sales = $this->orderModel->getSalesByDateRange($startDate, $endDate, 'daily');
        }
        
        // Preparar array de datas para o período
        $dates = [];
        $allDates = [];
        
        if ($period === 'year') {
            // Período por mês
            $currentDate = new DateTime($startMonth . '-01');
            $endDate = new DateTime($endMonth . '-01');
            
            while ($currentDate <= $endDate) {
                $formattedDate = $currentDate->format($format);
                $allDates[$formattedDate] = [
                    'label' => $currentDate->format($labelFormat),
                    'value' => 0,
                    'count' => 0
                ];
                $currentDate->modify('+1 month');
            }
        } else {
            // Período por dia
            $currentDate = new DateTime($startDate);
            $endDateTime = new DateTime($endDate);
            
            while ($currentDate <= $endDateTime) {
                $formattedDate = $currentDate->format($format);
                $allDates[$formattedDate] = [
                    'label' => $currentDate->format($labelFormat),
                    'value' => 0,
                    'count' => 0
                ];
                $currentDate->modify('+1 day');
            }
        }
        
        // Preencher com dados reais
        foreach ($sales as $sale) {
            $saleDate = ($period === 'year') ? substr($sale['date'], 0, 7) : $sale['date'];
            
            if (isset($allDates[$saleDate])) {
                $allDates[$saleDate]['value'] = floatval($sale['total']);
                $allDates[$saleDate]['count'] = intval($sale['count']);
            }
        }
        
        // Preparar dados para o gráfico
        foreach ($allDates as $date => $info) {
            $data[] = [
                'date' => $date,
                'label' => $info['label'],
                'value' => $info['value'],
                'count' => $info['count']
            ];
        }
        
        return $data;
    }
    
    /**
     * Obter dados para o gráfico de vendas por categoria
     */
    private function getCategoriesChartData() {
        return $this->orderModel->getSalesByCategory();
    }
    
    /**
     * Gera um relatório de vendas em CSV
     */
    public function exportSalesReport() {
        // Verificar parâmetros
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $groupBy = isset($_GET['group_by']) ? $_GET['group_by'] : 'daily';
        
        // Obter dados do relatório
        $sales = $this->orderModel->getSalesByDateRange($startDate, $endDate, $groupBy);
        
        // Definir cabeçalhos para download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_vendas_' . date('Y-m-d') . '.csv"');
        
        // Criar arquivo CSV
        $output = fopen('php://output', 'w');
        
        // Adicionar BOM para suporte a caracteres UTF-8
        fputs($output, "\xEF\xBB\xBF");
        
        // Cabeçalhos do CSV
        fputcsv($output, ['Data', 'Quantidade de Pedidos', 'Total Vendido (R$)']);
        
        // Dados do CSV
        foreach ($sales as $sale) {
            fputcsv($output, [
                $sale['date'],
                $sale['count'],
                $sale['total']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Gera um relatório de produtos em CSV
     */
    public function exportProductsReport() {
        // Verificar parâmetros
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        
        // Obter dados do relatório
        $products = $this->productModel->getProductsSalesReport($startDate, $endDate);
        
        // Definir cabeçalhos para download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_produtos_' . date('Y-m-d') . '.csv"');
        
        // Criar arquivo CSV
        $output = fopen('php://output', 'w');
        
        // Adicionar BOM para suporte a caracteres UTF-8
        fputs($output, "\xEF\xBB\xBF");
        
        // Cabeçalhos do CSV
        fputcsv($output, ['ID', 'Nome do Produto', 'Categoria', 'Quantidade Vendida', 'Total Vendido (R$)', 'Estoque Atual']);
        
        // Dados do CSV
        foreach ($products as $product) {
            fputcsv($output, [
                $product['id'],
                $product['name'],
                $product['category_name'],
                $product['quantity_sold'],
                $product['total_sales'],
                $product['stock']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Gera um relatório de clientes em CSV
     */
    public function exportCustomersReport() {
        // Verificar parâmetros
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        
        // Obter dados do relatório
        $customers = $this->userModel->getCustomersSalesReport($startDate, $endDate);
        
        // Definir cabeçalhos para download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_clientes_' . date('Y-m-d') . '.csv"');
        
        // Criar arquivo CSV
        $output = fopen('php://output', 'w');
        
        // Adicionar BOM para suporte a caracteres UTF-8
        fputs($output, "\xEF\xBB\xBF");
        
        // Cabeçalhos do CSV
        fputcsv($output, ['ID', 'Nome do Cliente', 'E-mail', 'Telefone', 'Quantidade de Pedidos', 'Total Gasto (R$)', 'Data de Cadastro']);
        
        // Dados do CSV
        foreach ($customers as $customer) {
            fputcsv($output, [
                $customer['id'],
                $customer['name'],
                $customer['email'],
                $customer['phone'] ?? 'Não informado',
                $customer['order_count'],
                $customer['total_spent'],
                $customer['created_at']
            ]);
        }
        
        fclose($output);
        exit;
    }
}