<?php
/**
 * AdminDashboardController
 * 
 * Controller responsável pelo gerenciamento do dashboard administrativo
 * com métricas visuais de produção e impressão 3D.
 */
class AdminDashboardController extends Controller
{
    private $dashboardModel;
    private $orderModel;
    private $productModel;
    private $printQueueModel;
    
    /**
     * Construtor
     */
    public function __construct()
    {
        // Verificar autenticação para todas as ações deste controller
        $this->requireAuth(['admin', 'manager']);
        
        // Inicializar models
        $this->dashboardModel = $this->loadModel('DashboardModel');
        $this->orderModel = $this->loadModel('OrderModel');
        $this->productModel = $this->loadModel('ProductModel');
        $this->printQueueModel = $this->loadModel('PrintQueueModel');
    }
    
    /**
     * Ação padrão - página principal do dashboard
     */
    public function index()
    {
        // Obter métricas para o dashboard principal
        $data = [
            'title' => 'Dashboard Administrativo',
            'metrics' => $this->getGeneralMetrics(),
            'recentOrders' => $this->getRecentOrders(),
            'printQueue' => $this->getCurrentPrintQueue(),
            'topProducts' => $this->getTopProducts()
        ];
        
        $this->view('admin/dashboard/index', $data);
    }
    
    /**
     * Dashboard de métricas de vendas
     */
    public function sales()
    {
        // Configurações para período
        $period = $_GET['period'] ?? 'month'; // day, week, month, year
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        // Obter métricas de vendas
        $data = [
            'title' => 'Métricas de Vendas',
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'salesMetrics' => $this->dashboardModel->getSalesMetrics($startDate, $endDate, $period),
            'salesByCategory' => $this->dashboardModel->getSalesByCategory($startDate, $endDate),
            'salesByProduct' => $this->dashboardModel->getSalesByProduct($startDate, $endDate, 10) // Top 10
        ];
        
        $this->view('admin/dashboard/sales', $data);
    }
    
    /**
     * Dashboard de métricas de impressão 3D
     */
    public function printing()
    {
        // Obter métricas de impressão 3D
        $data = [
            'title' => 'Métricas de Impressão 3D',
            'printingMetrics' => $this->dashboardModel->getPrintingMetrics(),
            'printerStatus' => $this->dashboardModel->getPrinterStatus(),
            'filamentUsage' => $this->dashboardModel->getFilamentUsage(),
            'printJobs' => $this->printQueueModel->getActivePrintJobs()
        ];
        
        $this->view('admin/dashboard/printing', $data);
    }
    
    /**
     * Dashboard de produtos
     */
    public function products()
    {
        // Métricas de produtos
        $data = [
            'title' => 'Métricas de Produtos',
            'productMetrics' => $this->dashboardModel->getProductMetrics(),
            'stockAlerts' => $this->dashboardModel->getStockAlerts(),
            'popularProducts' => $this->dashboardModel->getPopularProducts(10),
            'productsByCategory' => $this->dashboardModel->getProductsByCategory()
        ];
        
        $this->view('admin/dashboard/products', $data);
    }
    
    /**
     * Dashboard de clientes
     */
    public function customers()
    {
        // Métricas de clientes
        $data = [
            'title' => 'Métricas de Clientes',
            'customerMetrics' => $this->dashboardModel->getCustomerMetrics(),
            'newCustomers' => $this->dashboardModel->getNewCustomers(30), // Últimos 30 dias
            'topCustomers' => $this->dashboardModel->getTopCustomers(10) // Top 10
        ];
        
        $this->view('admin/dashboard/customers', $data);
    }
    
    /**
     * Obter dados para API AJAX
     */
    public function api($action = '')
    {
        // Verificar se a requisição é Ajax
        if (!$this->isAjaxRequest()) {
            $this->redirect('admin/dashboard');
            return;
        }
        
        // Headers para JSON
        header('Content-Type: application/json');
        
        switch ($action) {
            case 'sales_chart_data':
                echo json_encode($this->dashboardModel->getSalesChartData(
                    $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days')),
                    $_GET['end_date'] ?? date('Y-m-d'),
                    $_GET['period'] ?? 'day'
                ));
                break;
                
            case 'print_queue_status':
                echo json_encode($this->dashboardModel->getPrintQueueStatus());
                break;
                
            case 'printer_status':
                echo json_encode($this->dashboardModel->getPrinterStatus());
                break;
                
            case 'stock_status':
                echo json_encode($this->dashboardModel->getStockStatus());
                break;
                
            default:
                echo json_encode(['error' => 'Ação inválida']);
                break;
        }
        
        exit;
    }
    
    /**
     * Obter métricas gerais para o dashboard principal
     * 
     * @return array Métricas gerais
     */
    private function getGeneralMetrics()
    {
        // Período para métricas (últimos 30 dias por padrão)
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');
        
        // Métricas gerais
        return [
            'totalOrders' => $this->orderModel->getTotalOrders($startDate, $endDate),
            'totalSales' => $this->orderModel->getTotalSales($startDate, $endDate),
            'avgOrderValue' => $this->orderModel->getAverageOrderValue($startDate, $endDate),
            'activeUsers' => $this->dashboardModel->getActiveUsers(30),
            'pendingPrintJobs' => $this->printQueueModel->getTotalPendingJobs(),
            'completedPrintJobs' => $this->printQueueModel->getTotalCompletedJobs($startDate, $endDate),
            'filamentUsage' => $this->printQueueModel->getTotalFilamentUsage($startDate, $endDate),
            'avgPrintTime' => $this->printQueueModel->getAveragePrintTime($startDate, $endDate)
        ];
    }
    
    /**
     * Obter pedidos recentes
     * 
     * @param int $limit Limite de pedidos
     * @return array Pedidos recentes
     */
    private function getRecentOrders($limit = 10)
    {
        return $this->orderModel->getRecentOrders($limit);
    }
    
    /**
     * Obter fila de impressão atual
     * 
     * @param int $limit Limite de itens
     * @return array Itens da fila de impressão
     */
    private function getCurrentPrintQueue($limit = 10)
    {
        return $this->printQueueModel->getCurrentQueue($limit);
    }
    
    /**
     * Obter produtos mais vendidos
     * 
     * @param int $limit Limite de produtos
     * @return array Produtos mais vendidos
     */
    private function getTopProducts($limit = 10)
    {
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');
        
        return $this->productModel->getTopSellingProducts($startDate, $endDate, $limit);
    }
    
    /**
     * Verificar se é uma requisição Ajax
     * 
     * @return bool True se for uma requisição Ajax
     */
    private function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}
