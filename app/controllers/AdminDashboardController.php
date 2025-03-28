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
     * Exibe a página de relatórios
     */
    public function reports() {
        // Renderizar view
        require_once VIEWS_PATH . '/admin/reports.php';
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
     * Gera um relatório de vendas
     */
    public function salesReport() {
        // Verificar parâmetros
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $groupBy = isset($_GET['group_by']) ? $_GET['group_by'] : 'daily';
        $format = isset($_GET['format']) ? $_GET['format'] : '';
        
        // Obter dados do relatório
        $sales = $this->orderModel->getSalesByDateRange($startDate, $endDate, $groupBy);
        
        // Verificar formato de saída
        if ($format === 'csv') {
            // Cabeçalhos para CSV
            $headers = ['Data', 'Quantidade de Pedidos', 'Total Vendido (R$)'];
            
            // Preparar dados para o CSV
            $csvData = [];
            foreach ($sales as $sale) {
                $csvData[] = [
                    $sale['date'],
                    $sale['count'],
                    $sale['total']
                ];
            }
            
            // Gerar arquivo CSV
            ReportHelper::generateCSV(
                $csvData, 
                $headers, 
                'relatorio_vendas_' . date('Y-m-d') . '.csv'
            );
        } else if ($format === 'pdf') {
            // Cabeçalhos para PDF
            $headers = ['Data', 'Quantidade de Pedidos', 'Total Vendido (R$)'];
            
            // Preparar dados para o PDF
            $pdfData = [];
            foreach ($sales as $sale) {
                $pdfData[] = [
                    $sale['date'],
                    $sale['count'],
                    AdminHelper::formatMoney($sale['total'])
                ];
            }
            
            // Configurações do relatório
            $config = [
                'dateRange' => 'Período: ' . AdminHelper::formatDate($startDate) . ' a ' . AdminHelper::formatDate($endDate),
                'extraInfo' => [
                    'Agrupado por' => ($groupBy === 'daily' ? 'Dia' : ($groupBy === 'weekly' ? 'Semana' : 'Mês')),
                    'Total de Pedidos' => array_sum(array_column($sales, 'count')),
                    'Total Vendido' => AdminHelper::formatMoney(array_sum(array_column($sales, 'total')))
                ]
            ];
            
            // Gerar arquivo PDF
            ReportHelper::generatePDF(
                $pdfData, 
                $headers, 
                'Relatório de Vendas', 
                'relatorio_vendas_' . date('Y-m-d') . '.pdf',
                $config
            );
        } else {
            // Renderizar view com os dados
            $reportTitle = 'Relatório de Vendas';
            $reportPeriod = 'Período: ' . AdminHelper::formatDate($startDate) . ' a ' . AdminHelper::formatDate($endDate);
            $reportData = $sales;
            $groupByLabel = ($groupBy === 'daily' ? 'Dia' : ($groupBy === 'weekly' ? 'Semana' : 'Mês'));
            
            require_once VIEWS_PATH . '/admin/reports/sales_report.php';
        }
    }
    
    /**
     * Gera um relatório de produtos
     */
    public function productsReport() {
        // Verificar parâmetros
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0;
        $format = isset($_GET['format']) ? $_GET['format'] : '';
        
        // Obter dados do relatório
        $products = $this->productModel->getProductsSalesReport($startDate, $endDate, $limit);
        
        // Verificar formato de saída
        if ($format === 'csv') {
            // Cabeçalhos para CSV
            $headers = ['ID', 'Nome do Produto', 'Categoria', 'Quantidade Vendida', 'Total Vendido (R$)', 'Estoque Atual'];
            
            // Preparar dados para o CSV
            $csvData = [];
            foreach ($products as $product) {
                $csvData[] = [
                    $product['id'],
                    $product['name'],
                    $product['category_name'],
                    $product['quantity_sold'],
                    $product['total_sales'],
                    $product['stock']
                ];
            }
            
            // Gerar arquivo CSV
            ReportHelper::generateCSV(
                $csvData, 
                $headers, 
                'relatorio_produtos_' . date('Y-m-d') . '.csv'
            );
        } else if ($format === 'pdf') {
            // Cabeçalhos para PDF
            $headers = ['ID', 'Nome do Produto', 'Categoria', 'Qtd. Vendida', 'Total Vendido (R$)', 'Estoque'];
            
            // Preparar dados para o PDF
            $pdfData = [];
            foreach ($products as $product) {
                $pdfData[] = [
                    $product['id'],
                    $product['name'],
                    $product['category_name'],
                    $product['quantity_sold'],
                    AdminHelper::formatMoney($product['total_sales']),
                    $product['stock']
                ];
            }
            
            // Configurações do relatório
            $config = [
                'orientation' => 'L', // Paisagem
                'dateRange' => 'Período: ' . AdminHelper::formatDate($startDate) . ' a ' . AdminHelper::formatDate($endDate),
                'extraInfo' => [
                    'Total de Produtos' => count($products),
                    'Total Vendido' => AdminHelper::formatMoney(array_sum(array_column($products, 'total_sales'))),
                    'Quantidade Total Vendida' => array_sum(array_column($products, 'quantity_sold')) . ' unidades'
                ]
            ];
            
            // Gerar arquivo PDF
            ReportHelper::generatePDF(
                $pdfData, 
                $headers, 
                'Relatório de Produtos', 
                'relatorio_produtos_' . date('Y-m-d') . '.pdf',
                $config
            );
        } else {
            // Renderizar view com os dados
            $reportTitle = 'Relatório de Produtos';
            $reportPeriod = 'Período: ' . AdminHelper::formatDate($startDate) . ' a ' . AdminHelper::formatDate($endDate);
            $reportData = $products;
            
            require_once VIEWS_PATH . '/admin/reports/products_report.php';
        }
    }
    
    /**
     * Gera um relatório de clientes
     */
    public function customersReport() {
        // Verificar parâmetros
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0;
        $format = isset($_GET['format']) ? $_GET['format'] : '';
        
        // Obter dados do relatório
        $customers = $this->userModel->getCustomersSalesReport($startDate, $endDate, $limit);
        
        // Verificar formato de saída
        if ($format === 'csv') {
            // Cabeçalhos para CSV
            $headers = ['ID', 'Nome do Cliente', 'E-mail', 'Telefone', 'Quantidade de Pedidos', 'Total Gasto (R$)', 'Data de Cadastro'];
            
            // Preparar dados para o CSV
            $csvData = [];
            foreach ($customers as $customer) {
                $csvData[] = [
                    $customer['id'],
                    $customer['name'],
                    $customer['email'],
                    $customer['phone'] ?? 'Não informado',
                    $customer['order_count'],
                    $customer['total_spent'],
                    $customer['created_at']
                ];
            }
            
            // Gerar arquivo CSV
            ReportHelper::generateCSV(
                $csvData, 
                $headers, 
                'relatorio_clientes_' . date('Y-m-d') . '.csv'
            );
        } else if ($format === 'pdf') {
            // Cabeçalhos para PDF
            $headers = ['ID', 'Nome do Cliente', 'E-mail', 'Qtd. Pedidos', 'Total Gasto (R$)', 'Cadastro'];
            
            // Preparar dados para o PDF
            $pdfData = [];
            foreach ($customers as $customer) {
                $pdfData[] = [
                    $customer['id'],
                    $customer['name'],
                    $customer['email'],
                    $customer['order_count'],
                    AdminHelper::formatMoney($customer['total_spent']),
                    AdminHelper::formatDate($customer['created_at'])
                ];
            }
            
            // Configurações do relatório
            $config = [
                'orientation' => 'L', // Paisagem
                'dateRange' => 'Período: ' . AdminHelper::formatDate($startDate) . ' a ' . AdminHelper::formatDate($endDate),
                'extraInfo' => [
                    'Total de Clientes' => count($customers),
                    'Total Gasto' => AdminHelper::formatMoney(array_sum(array_column($customers, 'total_spent'))),
                    'Total de Pedidos' => array_sum(array_column($customers, 'order_count'))
                ]
            ];
            
            // Gerar arquivo PDF
            ReportHelper::generatePDF(
                $pdfData, 
                $headers, 
                'Relatório de Clientes', 
                'relatorio_clientes_' . date('Y-m-d') . '.pdf',
                $config
            );
        } else {
            // Renderizar view com os dados
            $reportTitle = 'Relatório de Clientes';
            $reportPeriod = 'Período: ' . AdminHelper::formatDate($startDate) . ' a ' . AdminHelper::formatDate($endDate);
            $reportData = $customers;
            
            require_once VIEWS_PATH . '/admin/reports/customers_report.php';
        }
    }
    
    /**
     * Gera um relatório de categorias
     */
    public function categoriesReport() {
        // Verificar parâmetros
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        $parentOnly = isset($_GET['parent_only']) ? intval($_GET['parent_only']) : 0;
        $format = isset($_GET['format']) ? $_GET['format'] : '';
        
        // Obter dados do relatório
        $categories = $this->categoryModel->getCategoriesSalesReport($startDate, $endDate, $parentOnly);
        
        // Verificar formato de saída
        if ($format === 'csv') {
            // Cabeçalhos para CSV
            $headers = ['ID', 'Nome da Categoria', 'Categoria Pai', 'Total de Produtos', 'Produtos Vendidos', 'Total Vendido (R$)'];
            
            // Preparar dados para o CSV
            $csvData = [];
            foreach ($categories as $category) {
                $csvData[] = [
                    $category['id'],
                    $category['name'],
                    $category['parent_name'] ?? 'Principal',
                    $category['product_count'],
                    $category['sold_count'],
                    $category['total_sales']
                ];
            }
            
            // Gerar arquivo CSV
            ReportHelper::generateCSV(
                $csvData, 
                $headers, 
                'relatorio_categorias_' . date('Y-m-d') . '.csv'
            );
        } else if ($format === 'pdf') {
            // Cabeçalhos para PDF
            $headers = ['ID', 'Nome da Categoria', 'Categoria Pai', 'Total Produtos', 'Produtos Vendidos', 'Total Vendido (R$)'];
            
            // Preparar dados para o PDF
            $pdfData = [];
            foreach ($categories as $category) {
                $pdfData[] = [
                    $category['id'],
                    $category['name'],
                    $category['parent_name'] ?? 'Principal',
                    $category['product_count'],
                    $category['sold_count'],
                    AdminHelper::formatMoney($category['total_sales'])
                ];
            }
            
            // Configurações do relatório
            $config = [
                'dateRange' => 'Período: ' . AdminHelper::formatDate($startDate) . ' a ' . AdminHelper::formatDate($endDate),
                'extraInfo' => [
                    'Total de Categorias' => count($categories),
                    'Total Vendido' => AdminHelper::formatMoney(array_sum(array_column($categories, 'total_sales'))),
                    'Filtro' => $parentOnly ? 'Apenas categorias principais' : 'Todas as categorias'
                ]
            ];
            
            // Gerar arquivo PDF
            ReportHelper::generatePDF(
                $pdfData, 
                $headers, 
                'Relatório de Categorias', 
                'relatorio_categorias_' . date('Y-m-d') . '.pdf',
                $config
            );
        } else {
            // Renderizar view com os dados
            $reportTitle = 'Relatório de Categorias';
            $reportPeriod = 'Período: ' . AdminHelper::formatDate($startDate) . ' a ' . AdminHelper::formatDate($endDate);
            $reportData = $categories;
            
            require_once VIEWS_PATH . '/admin/reports/categories_report.php';
        }
    }
}