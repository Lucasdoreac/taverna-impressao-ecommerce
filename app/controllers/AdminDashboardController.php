<?php
/**
 * AdminDashboardController
 * 
 * Controller responsável pelo gerenciamento do dashboard administrativo
 * com métricas visuais de produção e impressão 3D.
 */
class AdminDashboardController extends Controller
{
    use \App\Lib\Validation\InputValidationTrait;
    
    private $dashboardModel;
    private $orderModel;
    private $productModel;
    private $printQueueModel;
    private $performanceMonitor;
    private $printQueueMonitor;
    private $notificationManager;
    private $userModel;
    
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
        $this->userModel = $this->loadModel('UserModel');
        
        // Carregar bibliotecas de segurança
        require_once APP_PATH . '/lib/Security/SecurityManager.php';
        require_once APP_PATH . '/lib/Security/AccessControl.php';
        
        // Carregar monitoramento e notificações
        require_once APP_PATH . '/lib/Monitoring/PerformanceMonitor.php';
        require_once APP_PATH . '/lib/Monitoring/PrintQueueMonitor.php';
        require_once APP_PATH . '/lib/Notification/NotificationManager.php';
        
        $this->performanceMonitor = new PerformanceMonitor();
        $this->printQueueMonitor = PrintQueueMonitor::getInstance();
        $this->notificationManager = NotificationManager::getInstance();
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
            'topProducts' => $this->getTopProducts(),
            'alerts' => $this->getSystemAlerts(),
            'recentUsers' => $this->getRecentUsers(),
            'popularProducts' => $this->getPopularProducts(),
            'userMetrics' => $this->getUserMetrics(),
            'productMetrics' => $this->getProductMetrics()
        ];
        
        $this->view('admin/dashboard/index', $data);
    }
    
    /**
     * Dashboard de métricas de vendas
     */
    public function sales()
    {
        // Verificar permissão para acessar métricas de vendas
        if (!$this->canUserAccessSales()) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar as métricas de vendas.');
            $this->redirect('admin/dashboard');
            return;
        }
        
        // Validar parâmetros de data e período
        $period = $this->getValidatedParam('period', 'string', [
            'default' => 'month',
            'allowedValues' => ['day', 'week', 'month', 'year']
        ]);
        
        $startDate = $this->getValidatedParam('start_date', 'date', [
            'default' => date('Y-m-d', strtotime('-30 days'))
        ]);
        
        $endDate = $this->getValidatedParam('end_date', 'date', [
            'default' => date('Y-m-d')
        ]);
        
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
        // Verificar permissão para acessar métricas de impressão
        if (!$this->canUserAccessPrinting()) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar as métricas de impressão.');
            $this->redirect('admin/dashboard');
            return;
        }
        
        // Obter métricas de impressão 3D
        $data = [
            'title' => 'Métricas de Impressão 3D',
            'printingMetrics' => $this->dashboardModel->getPrintingMetrics(),
            'printerStatus' => $this->dashboardModel->getPrinterStatus(),
            'filamentUsage' => $this->dashboardModel->getFilamentUsage(),
            'printJobs' => $this->printQueueModel->getActivePrintJobs(),
            'queueStats' => $this->printQueueMonitor->getQueueStats()
        ];
        
        $this->view('admin/dashboard/printing', $data);
    }
    
    /**
     * Dashboard de produtos
     */
    public function products()
    {
        // Verificar permissão para acessar métricas de produtos
        if (!$this->canUserAccessProducts()) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar as métricas de produtos.');
            $this->redirect('admin/dashboard');
            return;
        }
        
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
        // Verificar permissão para acessar métricas de clientes
        if (!$this->canUserAccessCustomers()) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar as métricas de clientes.');
            $this->redirect('admin/dashboard');
            return;
        }
        
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
     * Dashboard de monitoramento de sistema
     */
    public function monitoring()
    {
        // Verificar permissão para acessar monitoramento de sistema
        if (!$this->canUserAccessMonitoring()) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar o monitoramento de sistema.');
            $this->redirect('admin/dashboard');
            return;
        }
        
        // Validar período de monitoramento
        $hours = $this->getValidatedParam('hours', 'int', [
            'default' => 24,
            'min' => 1,
            'max' => 168 // 7 dias
        ]);
        
        // Carregar dados de monitoramento
        $performanceModel = $this->loadModel('PerformanceMonitorModel');
        
        $data = [
            'title' => 'Monitoramento do Sistema',
            'hours' => $hours,
            'performance' => $performanceModel->getPerformanceMetrics($hours),
            'errors' => $performanceModel->getErrorMetrics($hours),
            'resources' => $performanceModel->getResourceMetrics($hours),
            'responseTime' => $performanceModel->getResponseTimeMetrics($hours),
            'databaseMetrics' => $performanceModel->getDatabaseMetrics($hours),
            'securityEvents' => $performanceModel->getSecurityEvents($hours)
        ];
        
        $this->view('admin/dashboard/monitoring', $data);
    }
    
    /**
     * Dashboard de notificações
     */
    public function notifications()
    {
        // Verificar permissão para acessar gerenciamento de notificações
        if (!$this->canUserAccessNotifications()) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar o gerenciamento de notificações.');
            $this->redirect('admin/dashboard');
            return;
        }
        
        // Validar parâmetros
        $page = $this->getValidatedParam('page', 'int', [
            'default' => 1,
            'min' => 1
        ]);
        
        $limit = $this->getValidatedParam('limit', 'int', [
            'default' => 20,
            'min' => 1,
            'max' => 100
        ]);
        
        $type = $this->getValidatedParam('type', 'string', [
            'default' => 'all',
            'allowedValues' => ['all', 'info', 'warning', 'success', 'error']
        ]);
        
        // Carregar notificações
        $notificationModel = $this->loadModel('NotificationModel');
        $notifications = $notificationModel->getAdminNotifications($page, $limit, $type);
        $totalNotifications = $notificationModel->getTotalAdminNotifications($type);
        
        $data = [
            'title' => 'Gerenciamento de Notificações',
            'notifications' => $notifications,
            'page' => $page,
            'limit' => $limit,
            'type' => $type,
            'totalNotifications' => $totalNotifications,
            'totalPages' => ceil($totalNotifications / $limit)
        ];
        
        $this->view('admin/dashboard/notifications', $data);
    }
    
    /**
     * Criar nova notificação push
     */
    public function createNotification()
    {
        // Verificar permissão para gerenciar notificações
        if (!$this->canUserManageNotifications()) {
            $this->setFlashMessage('error', 'Você não tem permissão para enviar notificações.');
            $this->redirect('admin/dashboard/notifications');
            return;
        }
        
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
            if (!SecurityManager::validateCsrfToken($csrfToken)) {
                $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
                $this->redirect('admin/dashboard/notifications');
                return;
            }
            
            // Validar dados da notificação
            $title = $this->postValidatedParam('title', 'string', ['required' => true, 'maxLength' => 255]);
            $message = $this->postValidatedParam('message', 'string', ['required' => true]);
            $type = $this->postValidatedParam('type', 'string', [
                'required' => true, 
                'allowedValues' => ['info', 'warning', 'success', 'error']
            ]);
            $userRoles = $this->postValidatedParam('user_roles', 'array', ['default' => ['customer']]);
            
            // Verificar erros de validação
            if ($this->hasValidationErrors()) {
                $this->setFlashMessage('error', 'Por favor, corrija os erros no formulário.');
                $this->view('admin/dashboard/create_notification', [
                    'title' => 'Enviar Notificação',
                    'errors' => $this->getValidationErrors(),
                    'formData' => [
                        'title' => $title,
                        'message' => $message,
                        'type' => $type,
                        'user_roles' => $userRoles
                    ]
                ]);
                return;
            }
            
            // Enviar notificação
            $success = $this->notificationManager->createSystemNotification($title, $message, $type, $userRoles);
            
            if ($success) {
                $this->setFlashMessage('success', 'Notificação enviada com sucesso.');
                $this->redirect('admin/dashboard/notifications');
            } else {
                $this->setFlashMessage('error', 'Ocorreu um erro ao enviar a notificação.');
                $this->view('admin/dashboard/create_notification', [
                    'title' => 'Enviar Notificação',
                    'error' => 'Falha ao enviar notificação',
                    'formData' => [
                        'title' => $title,
                        'message' => $message,
                        'type' => $type,
                        'user_roles' => $userRoles
                    ]
                ]);
            }
            
            return;
        }
        
        // Exibir formulário
        $this->view('admin/dashboard/create_notification', [
            'title' => 'Enviar Notificação',
            'formData' => [
                'title' => '',
                'message' => '',
                'type' => 'info',
                'user_roles' => ['customer']
            ]
        ]);
    }
    
    /**
     * Visualização de detalhes de pedido
     * 
     * @param int $orderId ID do pedido
     */
    public function orderDetails($orderId)
    {
        // Sanitizar e validar o ID do pedido
        $orderId = (int)$orderId;
        
        // Verificar permissão para acessar o pedido específico (CORREÇÃO IDOR)
        if (!$this->canUserAccessOrder($orderId)) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar este pedido.');
            $this->redirect('admin/dashboard');
            return;
        }
        
        // Obter dados do pedido
        $order = $this->orderModel->getOrderDetails($orderId);
        
        if (!$order) {
            $this->setFlashMessage('error', 'Pedido não encontrado.');
            $this->redirect('admin/dashboard');
            return;
        }
        
        $data = [
            'title' => 'Detalhes do Pedido #' . $orderId,
            'order' => $order,
            'items' => $this->orderModel->getOrderItems($orderId),
            'statusHistory' => $this->orderModel->getOrderStatusHistory($orderId),
            'printJobs' => $this->printQueueModel->getOrderPrintJobs($orderId)
        ];
        
        $this->view('admin/orders/view', $data);
    }
    
    /**
     * Visualização de detalhes de trabalho de impressão
     * 
     * @param int $printJobId ID do trabalho de impressão
     */
    public function printJobDetails($printJobId)
    {
        // Sanitizar e validar o ID do trabalho de impressão
        $printJobId = (int)$printJobId;
        
        // Verificar permissão para acessar o trabalho de impressão específico (CORREÇÃO IDOR)
        if (!$this->canUserAccessPrintJob($printJobId)) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar este trabalho de impressão.');
            $this->redirect('admin/dashboard');
            return;
        }
        
        // Obter dados do trabalho de impressão
        $printJob = $this->printQueueModel->getPrintJobDetails($printJobId);
        
        if (!$printJob) {
            $this->setFlashMessage('error', 'Trabalho de impressão não encontrado.');
            $this->redirect('admin/dashboard');
            return;
        }
        
        $data = [
            'title' => 'Detalhes do Trabalho de Impressão #' . $printJobId,
            'printJob' => $printJob,
            'order' => $this->orderModel->getOrderSummary($printJob['order_id']),
            'statusHistory' => $this->printQueueModel->getPrintJobStatusHistory($printJobId),
            'realtimeStats' => $this->printQueueMonitor->getJobStats($printJobId)
        ];
        
        $this->view('admin/print_queue/details', $data);
    }
    
    /**
     * Dashboard de gerenciamento de usuários e produtos
     */
    public function usersProducts()
    {
        // Verificar permissão
        if (!$this->canUserAccessUsers() || !$this->canUserAccessProducts()) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar esta área.');
            $this->redirect('admin/dashboard');
            return;
        }
        
        // Carregar models necessários
        $userModel = $this->loadModel('UserModel');
        $productModel = $this->loadModel('ProductModel');
        $categoryModel = $this->loadModel('CategoryModel');
        
        // Dados para a view
        $data = [
            'title' => 'Gerenciamento de Usuários e Produtos',
            'totalUsers' => $userModel->getTotalUsers(),
            'totalProducts' => $productModel->getTotalProducts(),
            'recentUsers' => $userModel->getRecentUsers(5),
            'popularProducts' => $productModel->getPopularProducts(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'), 5),
            'newUsers' => [
                'day' => $userModel->getNewUsersCount(1),
                'week' => $userModel->getNewUsersCount(7),
                'month' => $userModel->getNewUsersCount(30)
            ],
            'usersByType' => [
                'customer' => $userModel->getUserCountByRole('customer'),
                'admin' => $userModel->getUserCountByRole('admin'),
                'manager' => $userModel->getUserCountByRole('manager'),
                'operator' => $userModel->getUserCountByRole('printer_operator')
            ],
            'productsByCategory' => $categoryModel->getProductCountByCategory(),
            'stockStatus' => [
                'normal' => $productModel->getProductCountByStockStatus('normal'),
                'low' => $productModel->getProductCountByStockStatus('low'),
                'out' => $productModel->getProductCountByStockStatus('out')
            ],
            'currentUser' => ['role' => $_SESSION['user_role'] ?? '']
        ];
        
        // Renderizar view
        $this->view('admin/dashboard/users_products', $data);
    }

    /**
     * Visualização de detalhes de produto
     * 
     * @param int $productId ID do produto
     */
    public function productDetails($productId)
    {
        // Sanitizar e validar o ID do produto
        $productId = (int)$productId;
        
        // Verificar permissão para acessar o produto específico (CORREÇÃO IDOR)
        if (!$this->canUserAccessProduct($productId)) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar este produto.');
            $this->redirect('admin/dashboard');
            return;
        }
        
        // Obter dados do produto
        $product = $this->productModel->find($productId);
        
        if (!$product) {
            $this->setFlashMessage('error', 'Produto não encontrado.');
            $this->redirect('admin/dashboard');
            return;
        }
        
        $data = [
            'title' => 'Detalhes do Produto: ' . htmlspecialchars($product['name']),
            'product' => $product,
            'images' => $this->productModel->getProductImages($productId),
            'salesHistory' => $this->dashboardModel->getProductSalesHistory($productId, 30) // Últimos 30 dias
        ];
        
        $this->view('admin/products/view', $data);
    }
    
    /**
     * Visualização de detalhes de usuário
     * 
     * @param int $userId ID do usuário
     */
    public function userDetails($userId)
    {
        // Sanitizar e validar o ID do usuário
        $userId = (int)$userId;
        
        // Verificar permissão para acessar o usuário específico (CORREÇÃO IDOR)
        if (!$this->canUserAccessUser($userId)) {
            $this->setFlashMessage('error', 'Você não tem permissão para acessar este usuário.');
            $this->redirect('admin/dashboard');
            return;
        }
        
        // Obter modelo de usuário
        $userModel = $this->loadModel('UserModel');
        
        // Obter dados do usuário
        $user = $userModel->getUserById($userId);
        
        if (!$user) {
            $this->setFlashMessage('error', 'Usuário não encontrado.');
            $this->redirect('admin/dashboard');
            return;
        }
        
        $data = [
            'title' => 'Detalhes do Usuário: ' . htmlspecialchars($user['name']),
            'user' => $user,
            'orders' => $this->orderModel->getUserOrders($userId, 10), // Últimos 10 pedidos
            'loginHistory' => $userModel->getUserLoginHistory($userId, 10), // Últimos 10 logins
            'notifications' => $this->notificationManager->getUserNotifications($userId, 'all', 10, 0) // 10 notificações mais recentes
        ];
        
        $this->view('admin/users/view', $data);
    }
    
    /**
     * API para dados do dashboard (AJAX)
     * 
     * @param string $action Ação solicitada
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
        
        // Verificar CSRF token para todas as ações
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!SecurityManager::validateCsrfToken($csrfToken)) {
            echo json_encode(['error' => 'Token de segurança inválido']);
            return;
        }
        
        // Sanitizar parâmetros comuns
        $startDate = $this->getValidatedParam('start_date', 'date', [
            'default' => date('Y-m-d', strtotime('-30 days'))
        ]);
        
        $endDate = $this->getValidatedParam('end_date', 'date', [
            'default' => date('Y-m-d')
        ]);
        
        $period = $this->getValidatedParam('period', 'string', [
            'default' => 'day',
            'allowedValues' => ['day', 'week', 'month', 'year']
        ]);
        
        // Verificar permissão para a ação solicitada
        $hasPermission = true;
        
        switch ($action) {
            case 'dashboard_stats':
                $hasPermission = $this->canUserAccessUsers() || $this->canUserAccessProducts();
                break;
                
            case 'sales_chart_data':
                $hasPermission = $this->canUserAccessSales();
                break;
                
            case 'print_queue_status':
            case 'printer_status':
            case 'print_queue_alerts':
                $hasPermission = $this->canUserAccessPrinting();
                break;
                
            case 'stock_status':
                $hasPermission = $this->canUserAccessProducts();
                break;
                
            case 'performance_metrics':
                $hasPermission = $this->canUserAccessMonitoring();
                break;
                
            case 'notification_stats':
                $hasPermission = $this->canUserAccessNotifications();
                break;
                
            case 'order_details':
                $orderId = (int)($this->getValidatedParam('order_id', 'int', ['required' => true]));
                $hasPermission = $this->canUserAccessOrder($orderId);
                break;
                
            case 'print_job_details':
                $printJobId = (int)($this->getValidatedParam('print_job_id', 'int', ['required' => true]));
                $hasPermission = $this->canUserAccessPrintJob($printJobId);
                break;
                
            case 'product_details':
                $productId = (int)($this->getValidatedParam('product_id', 'int', ['required' => true]));
                $hasPermission = $this->canUserAccessProduct($productId);
                break;
                
            case 'user_details':
                $userId = (int)($this->getValidatedParam('user_id', 'int', ['required' => true]));
                $hasPermission = $this->canUserAccessUser($userId);
                break;
                
            default:
                $hasPermission = false;
                break;
        }
        
        // Se não tiver permissão, retornar erro
        if (!$hasPermission) {
            echo json_encode(['error' => 'Você não tem permissão para acessar estes dados']);
            return;
        }
        
        // Processar ação solicitada
        switch ($action) {
            case 'dashboard_stats':
                // Preparar dados para estatísticas do dashboard
                $userModel = $this->loadModel('UserModel');
                $productModel = $this->loadModel('ProductModel');
                
                $stats = [
                    'users' => [
                        'total' => $userModel->getTotalUsers(),
                        'new_today' => $userModel->getNewUsersCount(1),
                        'active' => $userModel->getActiveUsers()
                    ],
                    'products' => [
                        'total' => $productModel->getTotalProducts(),
                        'low_stock' => $productModel->getLowStockProductsCount(),
                        'out_of_stock' => $productModel->getOutOfStockProductsCount()
                    ]
                ];
                
                echo json_encode($stats);
                break;
                
            case 'sales_chart_data':
                echo json_encode($this->dashboardModel->getSalesChartData($startDate, $endDate, $period));
                break;
                
            case 'print_queue_status':
                echo json_encode($this->printQueueMonitor->getDashboardData());
                break;
                
            case 'printer_status':
                echo json_encode($this->dashboardModel->getPrinterStatus());
                break;
                
            case 'print_queue_alerts':
                echo json_encode($this->printQueueMonitor->getAlerts());
                break;
                
            case 'stock_status':
                echo json_encode($this->dashboardModel->getStockStatus());
                break;
                
            case 'performance_metrics':
                $hours = $this->getValidatedParam('hours', 'int', ['default' => 24, 'min' => 1, 'max' => 168]);
                $performanceModel = $this->loadModel('PerformanceMonitorModel');
                echo json_encode($performanceModel->getRealtimeMetrics($hours));
                break;
                
            case 'notification_stats':
                $notificationModel = $this->loadModel('NotificationModel');
                echo json_encode($notificationModel->getNotificationStats());
                break;
                
            case 'order_details':
                $orderId = (int)($this->getValidatedParam('order_id', 'int', ['required' => true]));
                echo json_encode($this->orderModel->getOrderDetails($orderId));
                break;
                
            case 'print_job_details':
                $printJobId = (int)($this->getValidatedParam('print_job_id', 'int', ['required' => true]));
                echo json_encode($this->printQueueModel->getPrintJobDetails($printJobId));
                break;
                
            case 'product_details':
                $productId = (int)($this->getValidatedParam('product_id', 'int', ['required' => true]));
                echo json_encode($this->productModel->find($productId));
                break;
                
            case 'user_details':
                $userId = (int)($this->getValidatedParam('user_id', 'int', ['required' => true]));
                $userModel = $this->loadModel('UserModel');
                echo json_encode($userModel->getUserById($userId));
                break;
                
            default:
                echo json_encode(['error' => 'Ação inválida']);
                break;
        }
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
        $metrics = [
            'totalOrders' => $this->orderModel->getTotalOrders($startDate, $endDate),
            'totalSales' => $this->orderModel->getTotalSales($startDate, $endDate),
            'avgOrderValue' => $this->orderModel->getAverageOrderValue($startDate, $endDate),
            'activeUsers' => $this->dashboardModel->getActiveUsers(30),
            'pendingPrintJobs' => $this->printQueueModel->getTotalPendingJobs(),
            'completedPrintJobs' => $this->printQueueModel->getTotalCompletedJobs($startDate, $endDate),
            'filamentUsage' => $this->printQueueModel->getTotalFilamentUsage($startDate, $endDate),
            'avgPrintTime' => $this->printQueueModel->getAveragePrintTime($startDate, $endDate)
        ];
        
        // Adicionar métricas de desempenho
        $performanceModel = $this->loadModel('PerformanceMonitorModel');
        $performance = $performanceModel->getPerformanceSummary(24); // Últimas 24 horas
        
        $metrics['avgResponseTime'] = $performance['avgResponseTime'] ?? 0;
        $metrics['peakMemoryUsage'] = $performance['peakMemoryUsage'] ?? 0;
        $metrics['errorRate'] = $performance['errorRate'] ?? 0;
        
        return $metrics;
    }
    
    /**
     * Obter usuários recentes
     * 
     * @param int $limit Limite de usuários
     * @return array Usuários recentes
     */
    private function getRecentUsers($limit = 5)
    {
        return $this->userModel->getRecentUsers($limit);
    }
    
    /**
     * Obter produtos populares
     * 
     * @param int $limit Limite de produtos
     * @return array Produtos populares
     */
    private function getPopularProducts($limit = 5)
    {
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');
        
        return $this->productModel->getPopularProducts($startDate, $endDate, $limit);
    }
    
    /**
     * Obter métricas de usuários
     * 
     * @return array Métricas de usuários
     */
    private function getUserMetrics()
    {
        return [
            'total' => $this->userModel->getTotalUsers(),
            'newLastMonth' => $this->userModel->getNewUsers(30), // Últimos 30 dias
            'active' => $this->userModel->getActiveUsers()
        ];
    }
    
    /**
     * Obter métricas de produtos
     * 
     * @return array Métricas de produtos
     */
    private function getProductMetrics()
    {
        return [
            'total' => $this->productModel->getTotalProducts(),
            'active' => $this->productModel->getTotalActiveProducts(),
            'lowStock' => $this->productModel->getLowStockProductsCount()
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
     * Obter alertas de sistema ativos
     * 
     * @return array Alertas ativos
     */
    private function getSystemAlerts()
    {
        $alerts = [];
        
        // Alertas da fila de impressão
        $queueAlerts = $this->printQueueMonitor->getAlerts();
        $alerts = array_merge($alerts, $queueAlerts);
        
        // Alertas de estoque
        $stockAlerts = $this->dashboardModel->getStockAlerts();
        foreach ($stockAlerts as $product) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Estoque baixo: {$product['name']} ({$product['stock']} unidades)",
                'details' => "ID: {$product['id']}, SKU: {$product['sku']}"
            ];
        }
        
        // Alertas de desempenho
        $performanceModel = $this->loadModel('PerformanceMonitorModel');
        $performanceAlerts = $performanceModel->getPerformanceAlerts();
        $alerts = array_merge($alerts, $performanceAlerts);
        
        return $alerts;
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
    
    /**
     * Definir mensagem flash para exibição na próxima requisição
     * 
     * @param string $type Tipo da mensagem (success, error, info, warning)
     * @param string $message Texto da mensagem
     * @return void
     */
    private function setFlashMessage($type, $message)
    {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    /**
     * Verificar se o usuário atual tem permissão para acessar métricas de vendas
     * 
     * @return bool True se tiver permissão
     */
    private function canUserAccessSales()
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $userRole = $_SESSION['user_role'] ?? '';
        
        // Admins e gerentes podem acessar
        if ($userRole === 'admin' || $userRole === 'manager') {
            return true;
        }
        
        // Verificar permissão específica
        return AccessControl::canUserAccessObject($userId, 0, 'sales_report', 'view');
    }
    
    /**
     * Verificar se o usuário atual tem permissão para acessar métricas de impressão
     * 
     * @return bool True se tiver permissão
     */
    private function canUserAccessPrinting()
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $userRole = $_SESSION['user_role'] ?? '';
        
        // Admins, gerentes e operadores de impressora podem acessar
        if ($userRole === 'admin' || $userRole === 'manager' || $userRole === 'printer_operator') {
            return true;
        }
        
        // Verificar permissão específica
        return AccessControl::canUserAccessObject($userId, 0, 'printing_report', 'view');
    }
    
    /**
     * Verificar se o usuário atual tem permissão para acessar métricas de produtos
     * 
     * @return bool True se tiver permissão
     */
    private function canUserAccessProducts()
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $userRole = $_SESSION['user_role'] ?? '';
        
        // Admins e gerentes podem acessar
        if ($userRole === 'admin' || $userRole === 'manager') {
            return true;
        }
        
        // Verificar permissão específica
        return AccessControl::canUserAccessObject($userId, 0, 'product_report', 'view');
    }
    
    /**
     * Verificar se o usuário atual tem permissão para acessar métricas de clientes
     * 
     * @return bool True se tiver permissão
     */
    private function canUserAccessCustomers()
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $userRole = $_SESSION['user_role'] ?? '';
        
        // Apenas admins e gerentes podem acessar
        if ($userRole === 'admin' || $userRole === 'manager') {
            return true;
        }
        
        // Verificar permissão específica
        return AccessControl::canUserAccessObject($userId, 0, 'customer_report', 'view');
    }
    
    /**
     * Verificar se o usuário atual tem permissão para acessar monitoramento de sistema
     * 
     * @return bool True se tiver permissão
     */
    private function canUserAccessMonitoring()
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $userRole = $_SESSION['user_role'] ?? '';
        
        // Apenas admins podem acessar
        if ($userRole === 'admin') {
            return true;
        }
        
        // Verificar permissão específica
        return AccessControl::canUserAccessObject($userId, 0, 'system_monitoring', 'view');
    }
    
    /**
     * Verificar se o usuário atual tem permissão para acessar gerenciamento de notificações
     * 
     * @return bool True se tiver permissão
     */
    private function canUserAccessNotifications()
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $userRole = $_SESSION['user_role'] ?? '';
        
        // Admins e gerentes podem acessar
        if ($userRole === 'admin' || $userRole === 'manager') {
            return true;
        }
        
        // Verificar permissão específica
        return AccessControl::canUserAccessObject($userId, 0, 'notification_management', 'view');
    }
    
    /**
     * Verificar se o usuário atual tem permissão para gerenciar notificações
     * 
     * @return bool True se tiver permissão
     */
    private function canUserManageNotifications()
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $userRole = $_SESSION['user_role'] ?? '';
        
        // Apenas admins podem gerenciar
        if ($userRole === 'admin') {
            return true;
        }
        
        // Verificar permissão específica
        return AccessControl::canUserAccessObject($userId, 0, 'notification_management', 'manage');
    }
    
    /**
     * Verificar se o usuário atual tem permissão para acessar um pedido específico
     * 
     * @param int $orderId ID do pedido
     * @return bool True se tiver permissão
     */
    private function canUserAccessOrder($orderId)
    {
        $userId = $_SESSION['user_id'] ?? 0;
        
        // Verificar permissão específica para este objeto
        return AccessControl::canUserAccessObject($userId, $orderId, 'order', 'view');
    }
    
    /**
     * Verificar se o usuário atual tem permissão para acessar um trabalho de impressão específico
     * 
     * @param int $printJobId ID do trabalho de impressão
     * @return bool True se tiver permissão
     */
    private function canUserAccessPrintJob($printJobId)
    {
        $userId = $_SESSION['user_id'] ?? 0;
        
        // Verificar permissão específica para este objeto
        return AccessControl::canUserAccessObject($userId, $printJobId, 'print_job', 'view');
    }
    
    /**
     * Verificar se o usuário atual tem permissão para acessar um produto específico
     * 
     * @param int $productId ID do produto
     * @return bool True se tiver permissão
     */
    private function canUserAccessProduct($productId)
    {
        $userId = $_SESSION['user_id'] ?? 0;
        
        // Verificar permissão específica para este objeto
        return AccessControl::canUserAccessObject($userId, $productId, 'product', 'view');
    }
    
    /**
     * Verificar se o usuário atual tem permissão para acessar um usuário específico
     * 
     * @param int $targetUserId ID do usuário a ser acessado
     * @return bool True se tiver permissão
     */
    private function canUserAccessUser($targetUserId)
    {
        $userId = $_SESSION['user_id'] ?? 0;
        
        // Verificar permissão específica para este objeto
        return AccessControl::canUserAccessObject($userId, $targetUserId, 'user', 'view');
    }
    
    /**
     * Verificar se o usuário atual tem permissão para acessar gerenciamento de usuários
     * 
     * @return bool True se tiver permissão
     */
    private function canUserAccessUsers()
    {
        $userId = $_SESSION['user_id'] ?? 0;
        $userRole = $_SESSION['user_role'] ?? '';
        
        // Apenas admins e gerentes podem acessar
        if ($userRole === 'admin' || $userRole === 'manager') {
            return true;
        }
        
        // Verificar permissão específica
        return AccessControl::canUserAccessObject($userId, 0, 'user_management', 'view');
    }
}
