<?php
namespace App\Controllers;

use App\Lib\Security\RateLimiter;
use App\Lib\Security\CsrfProtection;
use App\Lib\Security\InputValidationTrait;
use App\Lib\Security\SecurityManager;
use App\Lib\Security\Logger;
use App\Models\DashboardModel;
use App\Lib\Export\PdfExport;
use App\Lib\Export\ExcelExport;
use App\Lib\Repositories\IReportRepository;
use App\Lib\Repositories\ReportRepositoryFactory;

/**
 * AdminReportController
 * 
 * Controlador responsável pelo gerenciamento de relatórios avançados no painel administrativo.
 * Implementa relatórios de vendas, produtos, clientes e análises de tendência.
 * Inclui proteções de segurança como validação de entrada, CSRF, controle de acesso e rate limiting.
 *
 * @version 2.0.0
 * @author Taverna da Impressão
 */
class AdminReportController extends Controller
{
    use InputValidationTrait;
    
    /**
     * @var IReportRepository Repositório de relatórios
     */
    private $reportRepository;
    
    /**
     * @var DashboardModel Modelo de dashboard
     */
    private $dashboardModel;
    
    /**
     * @var Logger Sistema de registro de logs
     */
    private $logger;
    
    /**
     * @var array Lista de relatórios frequentes para prefetching
     */
    private $frequentReports = [
        'sales' => ['period' => 'month'],
        'products' => ['period' => 'month', 'limit' => 20],
        'customers' => ['period' => 'month', 'limit' => 20],
        'printing' => ['period' => 'month']
    ];
    
    /**
     * Construtor
     */
    public function __construct()
    {
        parent::__construct();
        
        // Inicializar logger
        $this->logger = new Logger('admin_report_controller');
        
        // Verificar autenticação para todas as ações deste controller
        if (!$this->securityManager->isAuthenticated() || 
            !$this->securityManager->hasPermission('admin_reports_view')) {
            $this->setFlashMessage('error', 'Acesso negado.');
            $this->redirect('admin/login');
            return;
        }
        
        // Aplicar headers de segurança
        $this->securityHeaders->apply();
        
        // Inicializar models
        $this->dashboardModel = new DashboardModel();
        
        // Inicializar repositório de relatórios (otimizado ou legacy, dependendo da configuração)
        $forceType = $this->validateInput('repository_type', 'string', ['default' => '']);
        $this->reportRepository = ReportRepositoryFactory::create($forceType);
        
        // Registrar inicialização do controller
        $this->logger->info('AdminReportController inicializado', [
            'repository_type' => $forceType ?: 'default',
            'user_id' => $this->securityManager->getCurrentUserId()
        ]);
    }
    
    /**
     * Página inicial de relatórios
     */
    public function index()
    {
        // Controle de taxa: 30 requisições por minuto
        $userId = $this->securityManager->getCurrentUserId();
        if (!RateLimiter::check('reports_index', $userId, 30, 60)) {
            $this->setFlashMessage('error', 'Muitas requisições. Aguarde alguns segundos.');
            $this->redirect('admin/dashboard');
            return;
        }
        
        // Carregar estatísticas de performance de cache
        $cacheStats = $this->reportRepository->getCacheStats();
        
        // Obter métricas de performance do repositório
        $performanceMetrics = $this->reportRepository->getPerformanceMetrics();
        
        $data = [
            'title' => 'Relatórios',
            'reportTypes' => [
                [
                    'id' => 'sales',
                    'name' => 'Vendas',
                    'description' => 'Relatórios detalhados de vendas, análise por período, cliente e região.',
                    'icon' => 'fa-chart-line',
                    'color' => 'primary',
                    'url' => BASE_URL . 'admin/reports/sales'
                ],
                [
                    'id' => 'products',
                    'name' => 'Produtos',
                    'description' => 'Análise de desempenho de produtos, vendas por categoria e tendências.',
                    'icon' => 'fa-box',
                    'color' => 'success',
                    'url' => BASE_URL . 'admin/reports/products'
                ],
                [
                    'id' => 'customers',
                    'name' => 'Clientes',
                    'description' => 'Relatórios de clientes, fidelidade, comportamento de compra e segmentação.',
                    'icon' => 'fa-users',
                    'color' => 'info',
                    'url' => BASE_URL . 'admin/reports/customers'
                ],
                [
                    'id' => 'trends',
                    'name' => 'Tendências',
                    'description' => 'Análise de tendências, sazonalidade e previsões de vendas futuras.',
                    'icon' => 'fa-chart-area',
                    'color' => 'warning',
                    'url' => BASE_URL . 'admin/reports/trends'
                ],
                [
                    'id' => 'printing',
                    'name' => 'Impressão 3D',
                    'description' => 'Relatórios de impressão 3D, filamentos, tempo de impressão e desempenho.',
                    'icon' => 'fa-print',
                    'color' => 'danger',
                    'url' => BASE_URL . 'admin/reports/printing'
                ]
            ],
            'cacheStats' => $cacheStats,
            'performanceMetrics' => $performanceMetrics
        ];
        
        $this->view('admin/reports/index', $data);
    }
    
    /**
     * Relatório de vendas
     */
    public function sales()
    {
        // Controle de taxa: 15 requisições por minuto
        $userId = $this->securityManager->getCurrentUserId();
        if (!RateLimiter::check('reports_sales', $userId, 15, 60)) {
            $this->setFlashMessage('error', 'Muitas requisições. Aguarde alguns segundos.');
            $this->redirect('admin/reports');
            return;
        }
        
        // Validação e sanitização de entrada
        $period = $this->validateInput('period', 'string', [
            'default' => 'month',
            'allowed' => ['day', 'week', 'month', 'quarter', 'year']
        ]);
        
        $startDate = $this->validateInput('start_date', 'date', [
            'default' => date('Y-m-d', strtotime('-30 days'))
        ]);
        
        $endDate = $this->validateInput('end_date', 'date', [
            'default' => date('Y-m-d')
        ]);
        
        // Validar tipo de relatório
        $reportType = $this->validateInput('report_type', 'string', [
            'default' => 'overview',
            'allowed' => ['overview', 'by_date', 'by_status', 'by_payment', 'by_customer', 'by_region']
        ]);
        
        // Verificar se é uma solicitação para limpar o cache
        $clearCache = $this->validateInput('clear_cache', 'bool', ['default' => false]);
        if ($clearCache && $this->securityManager->hasPermission('admin_reports_manage')) {
            $this->reportRepository->invalidateCache('sales');
            $this->setFlashMessage('success', 'Cache de relatórios de vendas limpo com sucesso.');
        }
        
        // Obter dados do relatório selecionado
        $reportData = [];
        $start = microtime(true); // Medição de performance
        
        switch ($reportType) {
            case 'overview':
                $reportData = [
                    'salesMetrics' => $this->dashboardModel->getSalesMetrics($startDate, $endDate, $period),
                    'salesByCategory' => $this->dashboardModel->getSalesByCategory($startDate, $endDate),
                    'salesByProduct' => $this->dashboardModel->getSalesByProduct($startDate, $endDate, 10),
                    'dailySales' => $this->reportRepository->getSalesReport($period)
                ];
                break;
                
            case 'by_date':
                if ($period === 'day') {
                    $reportData['dailySales'] = $this->reportRepository->getSalesReport('day');
                } else {
                    $reportData['monthlySales'] = $this->reportRepository->getSalesReport($period);
                }
                break;
                
            case 'by_status':
                $reportData['salesByStatus'] = $this->reportRepository->getSalesByStatusReport($startDate, $endDate);
                break;
                
            case 'by_payment':
                $reportData['salesByPayment'] = $this->reportRepository->getSalesByPaymentMethodReport($startDate, $endDate);
                break;
                
            case 'by_customer':
                $limit = $this->validateInput('limit', 'int', ['default' => 20, 'min' => 5, 'max' => 100]);
                $reportData['salesByCustomer'] = $this->reportRepository->getActiveCustomersReport($period, $limit);
                break;
                
            case 'by_region':
                $reportData['salesByRegion'] = $this->reportRepository->getSalesByRegionReport($startDate, $endDate);
                $reportData['salesByCity'] = $this->reportRepository->getSalesByCityReport($startDate, $endDate, 20);
                break;
        }
        
        // Cálculo de tempo de execução
        $executionTime = round((microtime(true) - $start) * 1000, 2); // em ms
        
        // Preparar dados para a view
        $data = [
            'title' => 'Relatório de Vendas',
            'reportType' => $reportType,
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'data' => $reportData,
            'executionTime' => $executionTime,
            'csrfToken' => CsrfProtection::getToken(),
            'cacheUsed' => $this->reportRepository->wasCacheUsed(),
            'repositoryMetrics' => $this->reportRepository->getPerformanceMetrics()
        ];
        
        $this->view('admin/reports/sales', $data);
    }
    
    /**
     * Relatório de produtos
     */
    public function products()
    {
        // Controle de taxa: 15 requisições por minuto
        $userId = $this->securityManager->getCurrentUserId();
        if (!RateLimiter::check('reports_products', $userId, 15, 60)) {
            $this->setFlashMessage('error', 'Muitas requisições. Aguarde alguns segundos.');
            $this->redirect('admin/reports');
            return;
        }
        
        // Validação e sanitização de entrada
        $period = $this->validateInput('period', 'string', [
            'default' => 'month',
            'allowed' => ['day', 'week', 'month', 'quarter', 'year']
        ]);
        
        // Verificar se é uma solicitação para limpar o cache
        $clearCache = $this->validateInput('clear_cache', 'bool', ['default' => false]);
        if ($clearCache && $this->securityManager->hasPermission('admin_reports_manage')) {
            $this->reportRepository->invalidateCache('products');
            $this->setFlashMessage('success', 'Cache de relatórios de produtos limpo com sucesso.');
        }
        
        // Medição de performance
        $start = microtime(true);
        
        // Obtenção de dados
        $topProducts = $this->reportRepository->getTopProducts($period, 20);
        $productCategories = $this->reportRepository->getProductCategoriesReport($period);
        $stockStatus = $this->reportRepository->getStockStatusReport();
        
        // Cálculo de tempo de execução
        $executionTime = round((microtime(true) - $start) * 1000, 2); // em ms
        
        // Passagem de dados sanitizados para a view
        $data = [
            'title' => 'Relatório de Produtos',
            'topProducts' => $topProducts,
            'productCategories' => $productCategories,
            'stockStatus' => $stockStatus,
            'period' => $period,
            'executionTime' => $executionTime,
            'csrfToken' => CsrfProtection::getToken(),
            'cacheUsed' => $this->reportRepository->wasCacheUsed(),
            'repositoryMetrics' => $this->reportRepository->getPerformanceMetrics()
        ];
        
        $this->view('admin/reports/products', $data);
    }
    
    /**
     * Relatório de clientes
     */
    public function customers()
    {
        // Controle de taxa: 15 requisições por minuto
        $userId = $this->securityManager->getCurrentUserId();
        if (!RateLimiter::check('reports_customers', $userId, 15, 60)) {
            $this->setFlashMessage('error', 'Muitas requisições. Aguarde alguns segundos.');
            $this->redirect('admin/reports');
            return;
        }
        
        // Validação e sanitização de entrada
        $period = $this->validateInput('period', 'string', [
            'default' => 'month',
            'allowed' => ['day', 'week', 'month', 'quarter', 'year']
        ]);
        
        // Verificar se é uma solicitação para limpar o cache
        $clearCache = $this->validateInput('clear_cache', 'bool', ['default' => false]);
        if ($clearCache && $this->securityManager->hasPermission('admin_reports_manage')) {
            $this->reportRepository->invalidateCache('customers');
            $this->setFlashMessage('success', 'Cache de relatórios de clientes limpo com sucesso.');
        }
        
        // Medição de performance
        $start = microtime(true);
        
        // Obtenção de dados
        $newCustomers = $this->reportRepository->getNewCustomersReport($period);
        $activeCustomers = $this->reportRepository->getActiveCustomersReport($period, 20);
        $customerSegments = $this->reportRepository->getCustomerSegmentsReport($period);
        $customerRetention = $this->reportRepository->getCustomerRetentionReport();
        
        // Cálculo de tempo de execução
        $executionTime = round((microtime(true) - $start) * 1000, 2); // em ms
        
        // Passagem de dados sanitizados para a view
        $data = [
            'title' => 'Relatório de Clientes',
            'newCustomers' => $newCustomers,
            'activeCustomers' => $activeCustomers,
            'customerSegments' => $customerSegments,
            'customerRetention' => $customerRetention,
            'period' => $period,
            'executionTime' => $executionTime,
            'csrfToken' => CsrfProtection::getToken(),
            'cacheUsed' => $this->reportRepository->wasCacheUsed(),
            'repositoryMetrics' => $this->reportRepository->getPerformanceMetrics()
        ];
        
        $this->view('admin/reports/customers', $data);
    }
    
    /**
     * Relatório de tendências
     */
    public function trends()
    {
        // Controle de taxa: 15 requisições por minuto
        $userId = $this->securityManager->getCurrentUserId();
        if (!RateLimiter::check('reports_trends', $userId, 15, 60)) {
            $this->setFlashMessage('error', 'Muitas requisições. Aguarde alguns segundos.');
            $this->redirect('admin/reports');
            return;
        }
        
        // Validar período
        $period = $this->validateInput('period', 'string', [
            'default' => 'year',
            'allowed' => ['quarter', 'year', 'all']
        ]);
        
        // Verificar se é uma solicitação para limpar o cache
        $clearCache = $this->validateInput('clear_cache', 'bool', ['default' => false]);
        if ($clearCache && $this->securityManager->hasPermission('admin_reports_manage')) {
            $this->reportRepository->invalidateCache('trends');
            $this->setFlashMessage('success', 'Cache de relatórios de tendências limpo com sucesso.');
        }
        
        // Medição de performance
        $start = microtime(true);
        
        // Obter dados para análise de tendências
        $salesTrend = $this->reportRepository->getSalesTrendReport($period);
        $productTrends = $this->reportRepository->getProductTrendsReport($period);
        $seasonalityData = $this->reportRepository->getSeasonalityReport();
        $forecastData = $this->reportRepository->getSalesForecastReport($period);
        
        // Cálculo de tempo de execução
        $executionTime = round((microtime(true) - $start) * 1000, 2); // em ms
        
        // Preparar dados para a view
        $data = [
            'title' => 'Análise de Tendências',
            'salesTrend' => $salesTrend,
            'productTrends' => $productTrends,
            'seasonalityData' => $seasonalityData,
            'forecastData' => $forecastData,
            'period' => $period,
            'executionTime' => $executionTime,
            'csrfToken' => CsrfProtection::getToken(),
            'cacheUsed' => $this->reportRepository->wasCacheUsed(),
            'repositoryMetrics' => $this->reportRepository->getPerformanceMetrics()
        ];
        
        $this->view('admin/reports/trends', $data);
    }
    
    /**
     * Relatório de impressão 3D
     */
    public function printing()
    {
        // Controle de taxa: 15 requisições por minuto
        $userId = $this->securityManager->getCurrentUserId();
        if (!RateLimiter::check('reports_printing', $userId, 15, 60)) {
            $this->setFlashMessage('error', 'Muitas requisições. Aguarde alguns segundos.');
            $this->redirect('admin/reports');
            return;
        }
        
        // Validar período
        $period = $this->validateInput('period', 'string', [
            'default' => 'month',
            'allowed' => ['month', 'quarter', 'year', 'all']
        ]);
        
        // Verificar se é uma solicitação para limpar o cache
        $clearCache = $this->validateInput('clear_cache', 'bool', ['default' => false]);
        if ($clearCache && $this->securityManager->hasPermission('admin_reports_manage')) {
            $this->reportRepository->invalidateCache('printing');
            $this->setFlashMessage('success', 'Cache de relatórios de impressão 3D limpo com sucesso.');
        }
        
        // Medição de performance
        $start = microtime(true);
        
        // Obter dados para relatório de impressão
        $printerUsage = $this->reportRepository->getPrinterUsageReport($period);
        $filamentUsage = $this->reportRepository->getFilamentUsageReport($period);
        $printTimeReport = $this->reportRepository->getPrintTimeReport($period);
        $failureAnalysis = $this->reportRepository->getPrintFailureReport($period);
        
        // Cálculo de tempo de execução
        $executionTime = round((microtime(true) - $start) * 1000, 2); // em ms
        
        // Preparar dados para a view
        $data = [
            'title' => 'Relatório de Impressão 3D',
            'printerUsage' => $printerUsage,
            'filamentUsage' => $filamentUsage,
            'printTimeReport' => $printTimeReport,
            'failureAnalysis' => $failureAnalysis,
            'period' => $period,
            'executionTime' => $executionTime,
            'csrfToken' => CsrfProtection::getToken(),
            'cacheUsed' => $this->reportRepository->wasCacheUsed(),
            'repositoryMetrics' => $this->reportRepository->getPerformanceMetrics()
        ];
        
        $this->view('admin/reports/printing', $data);
    }
    
    /**
     * Gerenciamento de cache de relatórios
     */
    public function manageCache()
    {
        // Verificar permissões de acesso
        if (!$this->securityManager->hasPermission('admin_reports_manage')) {
            $this->setFlashMessage('error', 'Acesso negado.');
            $this->redirect('admin/reports');
            return;
        }
        
        // Controle de taxa: 10 requisições por minuto
        $userId = $this->securityManager->getCurrentUserId();
        if (!RateLimiter::check('reports_manage_cache', $userId, 10, 60)) {
            $this->setFlashMessage('error', 'Muitas requisições. Aguarde alguns segundos.');
            $this->redirect('admin/reports');
            return;
        }
        
        // Verificação de CSRF para ações de modificação
        $action = $this->validateInput('action', 'string', [
            'default' => 'view',
            'allowed' => ['view', 'clear_all', 'clear_expired', 'clear_type']
        ]);
        
        // Ações que requerem validação CSRF
        if ($action !== 'view') {
            if (!$this->securityManager->validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->setFlashMessage('error', 'Token de segurança inválido. Tente novamente.');
                $this->redirect('admin/reports/manageCache');
                return;
            }
        }
        
        // Executar ação solicitada
        $actionResult = null;
        
        switch ($action) {
            case 'clear_all':
                $actionResult = [
                    'action' => 'Limpar todo o cache',
                    'result' => $this->reportRepository->clearAllCaches(),
                    'message' => 'Cache de relatórios completamente limpo.'
                ];
                break;
                
            case 'clear_expired':
                $actionResult = [
                    'action' => 'Limpar cache expirado',
                    'result' => $this->reportRepository->clearExpiredCaches(),
                    'message' => 'Cache expirado removido com sucesso.'
                ];
                break;
                
            case 'clear_type':
                $reportType = $this->validateInput('report_type', 'string', [
                    'required' => true,
                    'allowed' => ['sales', 'products', 'customers', 'trends', 'printing']
                ]);
                
                if ($reportType) {
                    $actionResult = [
                        'action' => 'Limpar cache de ' . $reportType,
                        'result' => $this->reportRepository->invalidateCache($reportType),
                        'message' => 'Cache do relatório ' . $reportType . ' limpo com sucesso.'
                    ];
                }
                break;
        }
        
        // Se uma ação foi executada com sucesso, mostrar mensagem
        if ($actionResult) {
            $this->setFlashMessage('success', $actionResult['message']);
        }
        
        // Obter estatísticas de cache e performance
        $cacheStats = $this->reportRepository->getDetailedCacheStats();
        $performanceMetrics = $this->reportRepository->getPerformanceMetrics();
        
        // Preparar dados para a view
        $data = [
            'title' => 'Gerenciamento de Cache de Relatórios',
            'cacheStats' => $cacheStats,
            'actionResult' => $actionResult,
            'performanceMetrics' => $performanceMetrics,
            'csrfToken' => CsrfProtection::getToken()
        ];
        
        $this->view('admin/reports/cache', $data);
    }
    
    /**
     * Dashboard de performance do sistema de relatórios
     */
    public function performance()
    {
        // Verificar permissões de acesso
        if (!$this->securityManager->hasPermission('admin_reports_manage')) {
            $this->setFlashMessage('error', 'Acesso negado.');
            $this->redirect('admin/reports');
            return;
        }
        
        // Controle de taxa: 10 requisições por minuto
        $userId = $this->securityManager->getCurrentUserId();
        if (!RateLimiter::check('reports_performance', $userId, 10, 60)) {
            $this->setFlashMessage('error', 'Muitas requisições. Aguarde alguns segundos.');
            $this->redirect('admin/reports');
            return;
        }
        
        // Executar benchmark se solicitado
        $runBenchmark = $this->validateInput('run_benchmark', 'bool', ['default' => false]);
        $benchmarkResults = null;
        
        if ($runBenchmark && $this->securityManager->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            // Criar ambos os repositórios para benchmark
            $repositories = ReportRepositoryFactory::createBothForBenchmark();
            
            // Executar testes comparativos
            $benchmarkResults = $this->runComparativeBenchmark($repositories);
            
            // Registrar realização do benchmark
            $this->logger->info("Benchmark de repositórios realizado", [
                'user_id' => $userId,
                'results' => $benchmarkResults['summary'] ?? []
            ]);
        }
        
        // Obter métricas detalhadas de desempenho
        $detailedMetrics = $this->reportRepository->getPerformanceMetrics();
        $cacheStats = $this->reportRepository->getDetailedCacheStats();
        
        // Preparar dados para a view
        $data = [
            'title' => 'Dashboard de Performance de Relatórios',
            'metrics' => $detailedMetrics,
            'cacheStats' => $cacheStats,
            'benchmarkResults' => $benchmarkResults,
            'csrfToken' => CsrfProtection::getToken()
        ];
        
        $this->view('admin/reports/performance', $data);
    }
    
    /**
     * Executa benchmark comparativo entre implementações
     * 
     * @param array $repositories Repositórios para teste
     * @return array Resultados comparativos
     */
    private function runComparativeBenchmark(array $repositories): array
    {
        $tests = [
            'getSalesReport' => ['period' => 'month'],
            'getTopProducts' => ['period' => 'month', 'limit' => 50],
            'getActiveCustomersReport' => ['period' => 'month', 'limit' => 50],
            'getSalesTrendReport' => ['period' => 'year'],
            'getPrinterUsageReport' => ['period' => 'month']
        ];
        
        $results = [
            'tests' => [],
            'summary' => []
        ];
        
        // Executar cada teste para cada repositório
        foreach ($tests as $method => $params) {
            $results['tests'][$method] = [];
            
            foreach ($repositories as $type => $repo) {
                // Medição de tempo e memória
                $startTime = microtime(true);
                $startMemory = memory_get_usage();
                
                // Obter parâmetros na ordem correta
                $callParams = [];
                foreach (array_keys($params) as $paramName) {
                    $callParams[] = $params[$paramName];
                }
                
                // Executar método
                $data = call_user_func_array([$repo, $method], $callParams);
                
                // Cálculo de métricas
                $endTime = microtime(true);
                $endMemory = memory_get_usage();
                $executionTime = round(($endTime - $startTime) * 1000, 2); // em ms
                $memoryUsage = round(($endMemory - $startMemory) / 1024, 2); // em KB
                
                // Armazenar resultado
                $results['tests'][$method][$type] = [
                    'execution_time_ms' => $executionTime,
                    'memory_usage_kb' => $memoryUsage,
                    'result_count' => is_array($data) ? count($data) : 0,
                    'cache_used' => $repo->wasCacheUsed($method)
                ];
            }
        }
        
        // Calcular estatísticas sumárias
        $optimizedTotal = 0;
        $legacyTotal = 0;
        $optimizedMemory = 0;
        $legacyMemory = 0;
        $count = 0;
        
        foreach ($results['tests'] as $method => $typeResults) {
            if (isset($typeResults['optimized']) && isset($typeResults['legacy'])) {
                $optimizedTotal += $typeResults['optimized']['execution_time_ms'];
                $legacyTotal += $typeResults['legacy']['execution_time_ms'];
                $optimizedMemory += $typeResults['optimized']['memory_usage_kb'];
                $legacyMemory += $typeResults['legacy']['memory_usage_kb'];
                $count++;
            }
        }
        
        if ($count > 0) {
            $results['summary'] = [
                'optimized_avg_time_ms' => round($optimizedTotal / $count, 2),
                'legacy_avg_time_ms' => round($legacyTotal / $count, 2),
                'optimized_avg_memory_kb' => round($optimizedMemory / $count, 2),
                'legacy_avg_memory_kb' => round($legacyMemory / $count, 2),
                'time_improvement_percentage' => $legacyTotal > 0 ? 
                    round((($legacyTotal - $optimizedTotal) / $legacyTotal) * 100, 2) : 0,
                'memory_improvement_percentage' => $legacyMemory > 0 ? 
                    round((($legacyMemory - $optimizedMemory) / $legacyMemory) * 100, 2) : 0,
                'test_count' => $count,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        return $results;
    }
    
    /**
     * Exportação de relatórios em diferentes formatos
     * com verificação rigorosa de CSRF e validação de entrada
     * 
     * @param string $reportType Tipo de relatório a ser exportado (opcional)
     * @return void
     */
    public function export($reportType = null)
    {
        // Verificação de CSRF para proteção contra ataques de falsificação de requisição
        if (!$this->securityManager->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->setFlashMessage('error', 'Erro de validação. Tente novamente.');
            $this->redirect('admin/reports');
            return;
        }
        
        // Controle de taxa mais restritivo: 5 exportações por minuto
        $userId = $this->securityManager->getCurrentUserId();
        if (!RateLimiter::check('reports_export', $userId, 5, 60)) {
            $this->setFlashMessage('error', 'Muitas exportações. Aguarde alguns minutos.');
            $this->redirect('admin/reports');
            return;
        }
        
        // Validação de entrada para tipo de relatório e formato
        $reportType = $this->validateInput('reportType', 'string', [
            'default' => $reportType ?? 'sales',
            'allowed' => ['sales', 'products', 'customers', 'trends', 'printing']
        ]);
        
        $format = $this->validateInput('format', 'string', [
            'default' => 'pdf',
            'allowed' => ['pdf', 'excel']
        ]);
        
        $period = $this->validateInput('period', 'string', [
            'default' => 'month',
            'allowed' => ['day', 'week', 'month', 'quarter', 'year', 'all']
        ]);
        
        // Log para auditoria de segurança
        $this->logger->info("Exportação de relatório solicitada", [
            'type' => $reportType,
            'format' => $format,
            'period' => $period,
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
        
        try {
            // Medição de performance
            $start = microtime(true);
            
            // Obter dados para o relatório
            $reportData = $this->getReportData($reportType, $period);
            
            // Verificar se os dados estão disponíveis
            if (empty($reportData)) {
                throw new \RuntimeException("Sem dados disponíveis para exportação");
            }
            
            // Gerar nome de arquivo seguro
            $filename = $this->generateSafeFilename($reportType, $period);
            
            // Cálculo de tempo de execução para log de performance
            $preparationTime = round((microtime(true) - $start) * 1000, 2); // em ms
            $exportStart = microtime(true);
            
            // Usar método apropriado baseado no formato solicitado
            if ($format === 'pdf') {
                $this->exportPdf($reportType, $reportData, $filename);
            } else {
                $this->exportExcel($reportType, $reportData, $filename);
            }
            
            // Log de performance
            $exportTime = round((microtime(true) - $exportStart) * 1000, 2); // em ms
            $totalTime = round((microtime(true) - $start) * 1000, 2); // em ms
            
            $this->logger->info("Exportação de relatório concluída", [
                'type' => $reportType,
                'format' => $format,
                'preparation_time_ms' => $preparationTime,
                'export_time_ms' => $exportTime,
                'total_time_ms' => $totalTime,
                'data_rows' => count($reportData),
                'cache_used' => $this->reportRepository->wasCacheUsed()
            ]);
            
        } catch (\Exception $e) {
            // Log detalhado do erro para análise interna - NUNCA expor ao usuário
            $this->logger->error("Erro na exportação: " . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Mensagem genérica para o usuário
            $this->setFlashMessage('error', 'Não foi possível gerar o relatório. Tente novamente mais tarde.');
            $this->redirect('admin/reports/' . $reportType);
        }
    }
    
    /**
     * Obtém os dados para exportação de relatório
     * 
     * @param string $reportType Tipo de relatório
     * @param string $period Período do relatório
     * @return array Dados para o relatório
     */
    private function getReportData($reportType, $period)
    {
        switch ($reportType) {
            case 'sales':
                return $this->reportRepository->getSalesReport($period);
                
            case 'products':
                return $this->reportRepository->getTopProducts($period, 100);
                
            case 'customers':
                return $this->reportRepository->getActiveCustomersReport($period, 100);
                
            case 'trends':
                return $this->reportRepository->getSalesTrendReport($period);
                
            case 'printing':
                return $this->reportRepository->getPrinterUsageReport($period);
                
            default:
                throw new \InvalidArgumentException("Tipo de relatório inválido");
        }
    }
    
    /**
     * Gera um nome de arquivo seguro para exportação
     * 
     * @param string $reportType Tipo de relatório
     * @param string $period Período do relatório
     * @return string Nome de arquivo seguro
     */
    private function generateSafeFilename($reportType, $period)
    {
        $reportTypeNames = [
            'sales' => 'vendas',
            'products' => 'produtos',
            'customers' => 'clientes',
            'trends' => 'tendencias',
            'printing' => 'impressao3d'
        ];
        
        $reportName = $reportTypeNames[$reportType] ?? 'relatorio';
        $timestamp = date('Ymd_His');
        $random = bin2hex(random_bytes(4)); // Adiciona aleatoriedade para prevenir colisões
        
        // Nome do arquivo seguro
        return "taverna_" . $reportName . "_" . $period . "_" . $timestamp . "_" . $random;
    }
    
    /**
     * Exportação em formato PDF
     * 
     * @param string $reportType Tipo de relatório
     * @param array $data Dados para o relatório
     * @param string $filename Nome do arquivo
     * @return void
     */
    private function exportPdf($reportType, $data, $filename)
    {
        // Títulos dos relatórios
        $titles = [
            'sales' => 'Relatório de Vendas',
            'products' => 'Relatório de Produtos',
            'customers' => 'Relatório de Clientes',
            'trends' => 'Análise de Tendências',
            'printing' => 'Relatório de Impressão 3D'
        ];
        
        // Configurações específicas por tipo de relatório
        $config = [
            'logo' => BASE_URL . 'public/img/logo.png',
            'author' => 'Sistema de Relatórios - Taverna da Impressão 3D',
            'header_text' => 'Relatório gerado em ' . date('d/m/Y H:i:s'),
            'footer_text' => 'Taverna da Impressão 3D - Confidencial - Página {PAGE_NUM} de {PAGE_COUNT}',
            'security' => [
                'user_id' => $this->securityManager->getCurrentUserId(),
                'username' => $this->securityManager->getCurrentUsername(),
                'timestamp' => time(),
                'document_id' => uniqid('doc_', true)
            ]
        ];
        
        // Criar exportador PDF
        $pdfExport = new PdfExport($titles[$reportType] ?? 'Relatório', $config);
        
        // Definir dados
        $pdfExport->setData($data);
        
        // Download do PDF
        $pdfExport->download($filename);
    }
    
    /**
     * Exportação em formato Excel
     * 
     * @param string $reportType Tipo de relatório
     * @param array $data Dados para o relatório
     * @param string $filename Nome do arquivo
     * @return void
     */
    private function exportExcel($reportType, $data, $filename)
    {
        // Títulos dos relatórios
        $titles = [
            'sales' => 'Relatório de Vendas',
            'products' => 'Relatório de Produtos',
            'customers' => 'Relatório de Clientes',
            'trends' => 'Análise de Tendências',
            'printing' => 'Relatório de Impressão 3D'
        ];
        
        // Configurações específicas por tipo de relatório
        $config = [
            'creator' => 'Taverna da Impressão 3D',
            'company' => 'Taverna da Impressão 3D',
            'subject' => $titles[$reportType] ?? 'Relatório',
            'keywords' => 'relatório, taverna, impressão 3d, ' . $reportType,
            'category' => 'Relatórios',
            'security' => [
                'user_id' => $this->securityManager->getCurrentUserId(),
                'username' => $this->securityManager->getCurrentUsername(),
                'timestamp' => time(),
                'document_id' => uniqid('doc_', true)
            ]
        ];
        
        // Criar exportador Excel
        $excelExport = new ExcelExport($titles[$reportType] ?? 'Relatório', $config);
        
        // Definir cabeçalhos e dados
        if (!empty($data)) {
            $headers = array_keys($data[0]);
            $excelExport->setHeaders($headers);
            $excelExport->setData($data);
        }
        
        // Download do Excel
        $excelExport->download($filename);
    }
    
    /**
     * API para dados de relatórios (AJAX)
     * 
     * @param string $action Ação solicitada
     * @return void
     */
    public function api($action = '')
    {
        // Verificar se a requisição é Ajax
        if (!$this->isAjaxRequest()) {
            $this->redirect('admin/reports');
            return;
        }
        
        // Controle de taxa: 20 requisições por minuto para API
        $userId = $this->securityManager->getCurrentUserId();
        if (!RateLimiter::check('reports_api', $userId, 20, 60)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Taxa de requisições excedida. Aguarde alguns segundos.']);
            return;
        }
        
        // Headers para JSON
        header('Content-Type: application/json');
        
        // Verificar CSRF token para todas as ações
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!$this->securityManager->validateCsrfToken($csrfToken)) {
            echo json_encode(['error' => 'Token de segurança inválido']);
            return;
        }
        
        // Sanitizar parâmetros comuns
        $period = $this->validateInput('period', 'string', [
            'default' => 'month',
            'allowed' => ['day', 'week', 'month', 'quarter', 'year', 'all']
        ]);
        
        // Processar ação solicitada
        switch ($action) {
            case 'sales_data':
                echo json_encode($this->reportRepository->getSalesReport($period));
                break;
                
            case 'product_data':
                $limit = $this->validateInput('limit', 'int', ['default' => 20, 'min' => 5, 'max' => 100]);
                echo json_encode($this->reportRepository->getTopProducts($period, $limit));
                break;
                
            case 'customer_data':
                $limit = $this->validateInput('limit', 'int', ['default' => 20, 'min' => 5, 'max' => 100]);
                echo json_encode($this->reportRepository->getActiveCustomersReport($period, $limit));
                break;
                
            case 'trend_data':
                echo json_encode($this->reportRepository->getSalesTrendReport($period));
                break;
                
            case 'printing_data':
                echo json_encode($this->reportRepository->getPrinterUsageReport($period));
                break;
            
            case 'cache_stats':
                // Verificar permissões para acessar estatísticas de cache
                if (!$this->securityManager->hasPermission('admin_reports_manage')) {
                    echo json_encode(['error' => 'Permissão negada']);
                    return;
                }
                echo json_encode($this->reportRepository->getDetailedCacheStats());
                break;
                
            case 'performance_metrics':
                // Verificar permissões para acessar métricas de desempenho
                if (!$this->securityManager->hasPermission('admin_reports_manage')) {
                    echo json_encode(['error' => 'Permissão negada']);
                    return;
                }
                echo json_encode($this->reportRepository->getPerformanceMetrics());
                break;
                
            default:
                echo json_encode(['error' => 'Ação inválida']);
                break;
        }
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
     * Verifica se é uma requisição de exportação
     * 
     * @return bool True se for uma requisição de exportação
     */
    private function isExportRequest(): bool
    {
        return isset($_GET['export']) || 
               isset($_POST['export']) || 
               strpos($_SERVER['REQUEST_URI'], '/export') !== false;
    }
}