<?php
/**
 * PerformanceMonitoringDashboardController
 * 
 * Controller para o dashboard de monitoramento de performance da Taverna da Impressão 3D.
 * Fornece visualização abrangente de métricas de desempenho, alertas em tempo real 
 * e análise histórica.
 * 
 * @package App\Controllers
 * @version 1.0.0
 */

use App\Lib\Validation\InputValidationTrait;
use App\Lib\Security\SecurityManager;
use App\Lib\Performance\PerformanceMetrics;
use App\Lib\Monitoring\PerformanceAlertingService;

class PerformanceMonitoringDashboardController extends BaseController {
    use InputValidationTrait;
    
    /** @var PerformanceMetrics */
    private $performanceMetrics;
    
    /** @var PerformanceAlertingService */
    private $alertingService;
    
    /** @var PDO */
    private $db;
    
    /**
     * Inicializa dependências do controller
     */
    public function __construct() {
        parent::__construct();
        
        // Verificar se o usuário tem permissão de administrador
        if (!$this->isAdmin()) {
            $this->redirect('login');
            return;
        }
        
        // Inicializar dependências
        $this->db = Database::getConnection();
        $this->performanceMetrics = new PerformanceMetrics($this->db);
        
        // Verificar dependências para o sistema de alertas
        if (class_exists('App\Lib\Notification\NotificationManager') && 
            class_exists('App\Lib\Notification\NotificationThresholds')) {
                
            $notificationManager = new \App\Lib\Notification\NotificationManager($this->db);
            $thresholds = new \App\Lib\Notification\NotificationThresholds($this->db);
            $this->alertingService = new PerformanceAlertingService(
                new \App\Lib\Performance\PerformanceMonitor(),
                $notificationManager,
                $thresholds,
                $this->db
            );
        }
        
        // Configurar cabeçalhos de segurança
        SecurityManager::setupSecurityHeaders();
    }
    
    /**
     * Exibe a página principal do dashboard de monitoramento
     */
    public function index() {
        // Obter intervalo de datas para filtro (padrão: últimas 24 horas)
        $endDate = isset($_GET['end_date']) ? $this->validateInput($_GET['end_date'], 'date') : date('Y-m-d H:i:s');
        $startDate = isset($_GET['start_date']) ? $this->validateInput($_GET['start_date'], 'date') : date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        if ($endDate === null) $endDate = date('Y-m-d H:i:s');
        if ($startDate === null) $startDate = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        // Obter dados de performance para o período
        $performanceData = $this->getPerformanceData($startDate, $endDate);
        
        // Obter alertas recentes
        $recentAlerts = $this->getRecentAlerts();
        
        // Obter estatísticas resumidas
        $summary = $this->getPerformanceSummary($startDate, $endDate);
        
        // Obter métricas de recursos do sistema
        $resourceMetrics = $this->getResourceMetrics($startDate, $endDate);
        
        // Renderizar view
        $this->view->render('admin/performance_monitoring_dashboard', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'performanceData' => $performanceData,
            'recentAlerts' => $recentAlerts,
            'summary' => $summary,
            'resourceMetrics' => $resourceMetrics
        ]);
    }
    
    /**
     * Fornece dados para gráficos via AJAX
     */
    public function getChartData() {
        // Verificar token CSRF
        if (!SecurityManager::validateCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF inválido']);
            exit;
        }
        
        // Validar parâmetros
        $startDate = $this->validateInput($_GET['start_date'] ?? '', 'date');
        $endDate = $this->validateInput($_GET['end_date'] ?? '', 'date');
        $metricType = $this->validateInput($_GET['metric_type'] ?? '', 'string', [
            'allowed' => ['execution_time', 'memory_usage', 'database_queries', 'error_rate']
        ]);
        
        if ($startDate === null || $endDate === null || $metricType === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Parâmetros inválidos']);
            exit;
        }
        
        // Obter dados para o gráfico
        $chartData = $this->getMetricChartData($metricType, $startDate, $endDate);
        
        // Sanitizar saída
        $response = [
            'labels' => array_map(function($label) {
                return htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            }, $chartData['labels']),
            'datasets' => $chartData['datasets']
        ];
        
        // Definir cabeçalhos e retornar resposta
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    
    /**
     * Fornece lista de alertas via AJAX para atualizações em tempo real
     */
    public function getAlerts() {
        // Verificar token CSRF
        if (!SecurityManager::validateCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF inválido']);
            exit;
        }
        
        // Validar parâmetros
        $limit = $this->validateInput($_GET['limit'] ?? '10', 'int', ['min' => 1, 'max' => 100]);
        $offset = $this->validateInput($_GET['offset'] ?? '0', 'int', ['min' => 0]);
        $type = $this->validateInput($_GET['type'] ?? '', 'string');
        
        if ($limit === null) $limit = 10;
        if ($offset === null) $offset = 0;
        
        // Obter alertas
        $alerts = $this->getFilteredAlerts($type, $limit, $offset);
        
        // Sanitizar saída
        $sanitizedAlerts = [];
        foreach ($alerts as $alert) {
            $sanitizedAlert = [];
            foreach ($alert as $key => $value) {
                $sanitizedAlert[$key] = is_string($value) 
                    ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') 
                    : $value;
            }
            $sanitizedAlerts[] = $sanitizedAlert;
        }
        
        // Definir cabeçalhos e retornar resposta
        header('Content-Type: application/json');
        echo json_encode([
            'alerts' => $sanitizedAlerts,
            'total' => $this->getAlertCount($type)
        ]);
    }
    
    /**
     * Fornece métricas de sistema em tempo real via AJAX
     */
    public function getSystemMetrics() {
        // Verificar token CSRF
        if (!SecurityManager::validateCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF inválido']);
            exit;
        }
        
        // Coletar métricas do sistema em tempo real
        $metrics = [
            'cpu_usage' => $this->getCurrentCpuUsage(),
            'memory_usage' => $this->getCurrentMemoryUsage(),
            'disk_usage' => $this->getCurrentDiskUsage(),
            'active_users' => $this->getActiveUserCount(),
            'active_processes' => $this->getActiveProcessCount(),
            'recent_errors' => $this->getRecentErrorCount()
        ];
        
        // Definir cabeçalhos e retornar resposta
        header('Content-Type: application/json');
        echo json_encode($metrics);
    }
    
    /**
     * Exibe relatório detalhado de performance para uma URL específica
     */
    public function urlReport() {
        // Validar parâmetros
        $url = $this->validateInput($_GET['url'] ?? '', 'string');
        $startDate = $this->validateInput($_GET['start_date'] ?? '', 'date');
        $endDate = $this->validateInput($_GET['end_date'] ?? '', 'date');
        
        if ($url === null || $startDate === null || $endDate === null) {
            $this->redirect('admin/performance_monitoring_dashboard');
            return;
        }
        
        // Obter relatório detalhado
        $report = $this->getUrlPerformanceReport($url, $startDate, $endDate);
        
        // Renderizar view
        $this->view->render('admin/performance_url_report', [
            'url' => $url,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'report' => $report
        ]);
    }
    
    /**
     * Exibe detalhes de um alerta específico
     */
    public function alertDetail() {
        // Validar parâmetros
        $alertId = $this->validateInput($_GET['id'] ?? '', 'int');
        
        if ($alertId === null) {
            $this->redirect('admin/performance_monitoring_dashboard');
            return;
        }
        
        // Obter detalhes do alerta
        $alert = $this->getAlertDetail($alertId);
        
        if (!$alert) {
            $this->redirect('admin/performance_monitoring_dashboard');
            return;
        }
        
        // Renderizar view
        $this->view->render('admin/performance_alert_detail', [
            'alert' => $alert
        ]);
    }
    
    /**
     * Configuração de limiares de alerta
     */
    public function thresholds() {
        // Verificar se o formulário foi enviado (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verificar token CSRF
            if (!SecurityManager::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $this->addFlashMessage('error', 'Token CSRF inválido. Tente novamente.');
                $this->redirect('admin/performance_monitoring_dashboard/thresholds');
                return;
            }
            
            // Validar e processar os novos valores de limiar
            $this->processThresholdUpdates($_POST);
            
            $this->addFlashMessage('success', 'Limiares de alerta atualizados com sucesso.');
            $this->redirect('admin/performance_monitoring_dashboard/thresholds');
            return;
        }
        
        // Obter configurações atuais de limiares
        $thresholds = $this->getCurrentThresholds();
        
        // Renderizar view
        $this->view->render('admin/performance_thresholds', [
            'thresholds' => $thresholds
        ]);
    }
    
    /**
     * Obtém dados de performance para o período especificado
     * 
     * @param string $startDate Data inicial (Y-m-d H:i:s)
     * @param string $endDate Data final (Y-m-d H:i:s)
     * @return array Dados de performance
     */
    private function getPerformanceData($startDate, $endDate) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as hour,
                    AVG(execution_time) as avg_execution_time,
                    MAX(execution_time) as max_execution_time,
                    AVG(memory_peak) as avg_memory_peak,
                    COUNT(*) as request_count
                FROM performance_logs
                WHERE timestamp BETWEEN :start_date AND :end_date
                GROUP BY hour
                ORDER BY hour ASC
            ");
            
            $stmt->execute([
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Complementar com dados por URL
            $urlData = $this->getUrlPerformanceData($startDate, $endDate);
            
            return [
                'hourly' => $results,
                'urls' => $urlData
            ];
        } catch (PDOException $e) {
            error_log('Erro ao obter dados de performance: ' . $e->getMessage());
            return [
                'hourly' => [],
                'urls' => []
            ];
        }
    }
    
    /**
     * Obtém dados de performance por URL
     * 
     * @param string $startDate Data inicial (Y-m-d H:i:s)
     * @param string $endDate Data final (Y-m-d H:i:s)
     * @return array Dados de performance por URL
     */
    private function getUrlPerformanceData($startDate, $endDate) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    request_uri,
                    COUNT(*) as request_count,
                    AVG(execution_time) as avg_execution_time,
                    MAX(execution_time) as max_execution_time,
                    MIN(execution_time) as min_execution_time,
                    AVG(memory_peak) as avg_memory_peak
                FROM performance_logs
                WHERE timestamp BETWEEN :start_date AND :end_date
                GROUP BY request_uri
                ORDER BY avg_execution_time DESC
                LIMIT 10
            ");
            
            $stmt->execute([
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erro ao obter dados de performance por URL: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém alertas recentes
     * 
     * @param int $limit Limite de resultados
     * @return array Alertas recentes
     */
    private function getRecentAlerts($limit = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    alert_type,
                    severity,
                    alert_data,
                    created_at
                FROM performance_alerts
                ORDER BY created_at DESC
                LIMIT :limit
            ");
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decodificar dados do alerta
            foreach ($alerts as &$alert) {
                if (isset($alert['alert_data'])) {
                    $alert['data'] = json_decode($alert['alert_data'], true);
                    unset($alert['alert_data']); // Remover versão codificada
                }
            }
            
            return $alerts;
        } catch (PDOException $e) {
            error_log('Erro ao obter alertas recentes: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém resumo de performance para o período
     * 
     * @param string $startDate Data inicial
     * @param string $endDate Data final
     * @return array Resumo de performance
     */
    private function getPerformanceSummary($startDate, $endDate) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_requests,
                    AVG(execution_time) as avg_execution_time,
                    MAX(execution_time) as max_execution_time,
                    AVG(memory_peak) as avg_memory_peak,
                    MAX(memory_peak) as max_memory_peak,
                    COUNT(DISTINCT request_uri) as unique_urls
                FROM performance_logs
                WHERE timestamp BETWEEN :start_date AND :end_date
            ");
            
            $stmt->execute([
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obter contagem de erros
            $stmtErrors = $this->db->prepare("
                SELECT COUNT(*) as error_count
                FROM error_logs
                WHERE timestamp BETWEEN :start_date AND :end_date
            ");
            
            $stmtErrors->execute([
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            
            $errorCount = $stmtErrors->fetchColumn();
            
            // Adicionar contagem de erros ao resumo
            $summary['error_count'] = (int)$errorCount;
            
            // Adicionar taxa de erro
            $summary['error_rate'] = $summary['total_requests'] > 0 
                ? ($errorCount / $summary['total_requests']) * 100 
                : 0;
            
            return $summary;
        } catch (PDOException $e) {
            error_log('Erro ao obter resumo de performance: ' . $e->getMessage());
            return [
                'total_requests' => 0,
                'avg_execution_time' => 0,
                'max_execution_time' => 0,
                'avg_memory_peak' => 0,
                'max_memory_peak' => 0,
                'unique_urls' => 0,
                'error_count' => 0,
                'error_rate' => 0
            ];
        }
    }
    
    /**
     * Obtém métricas de recursos do sistema
     * 
     * @param string $startDate Data inicial
     * @param string $endDate Data final
     * @return array Métricas de recursos
     */
    private function getResourceMetrics($startDate, $endDate) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as hour,
                    AVG(memory_peak) as avg_memory_peak,
                    AVG(cpu_usage) as avg_cpu_usage,
                    AVG(disk_usage) as avg_disk_usage
                FROM resource_metrics
                WHERE timestamp BETWEEN :start_date AND :end_date
                GROUP BY hour
                ORDER BY hour ASC
            ");
            
            $stmt->execute([
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erro ao obter métricas de recursos: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém dados para um gráfico específico
     * 
     * @param string $metricType Tipo de métrica
     * @param string $startDate Data inicial
     * @param string $endDate Data final
     * @return array Dados para o gráfico
     */
    private function getMetricChartData($metricType, $startDate, $endDate) {
        $labels = [];
        $datasets = [];
        
        try {
            switch ($metricType) {
                case 'execution_time':
                    $stmt = $this->db->prepare("
                        SELECT 
                            DATE_FORMAT(timestamp, '%Y-%m-%d %H:%i') as time_interval,
                            AVG(execution_time) as avg_value,
                            MAX(execution_time) as max_value,
                            MIN(execution_time) as min_value
                        FROM performance_logs
                        WHERE timestamp BETWEEN :start_date AND :end_date
                        GROUP BY time_interval
                        ORDER BY time_interval ASC
                    ");
                    
                    $stmt->execute([
                        ':start_date' => $startDate,
                        ':end_date' => $endDate
                    ]);
                    
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($data as $row) {
                        $labels[] = $row['time_interval'];
                    }
                    
                    $datasets = [
                        [
                            'label' => 'Tempo Médio (s)',
                            'data' => array_column($data, 'avg_value'),
                            'borderColor' => 'rgba(54, 162, 235, 1)',
                            'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                            'borderWidth' => 1,
                            'fill' => true
                        ],
                        [
                            'label' => 'Tempo Máximo (s)',
                            'data' => array_column($data, 'max_value'),
                            'borderColor' => 'rgba(255, 99, 132, 1)',
                            'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                            'borderWidth' => 1,
                            'fill' => false
                        ],
                        [
                            'label' => 'Tempo Mínimo (s)',
                            'data' => array_column($data, 'min_value'),
                            'borderColor' => 'rgba(75, 192, 192, 1)',
                            'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                            'borderWidth' => 1,
                            'fill' => false
                        ]
                    ];
                    break;
                    
                case 'memory_usage':
                    $stmt = $this->db->prepare("
                        SELECT 
                            DATE_FORMAT(timestamp, '%Y-%m-%d %H:%i') as time_interval,
                            AVG(memory_peak) as avg_value,
                            MAX(memory_peak) as max_value
                        FROM performance_logs
                        WHERE timestamp BETWEEN :start_date AND :end_date
                        GROUP BY time_interval
                        ORDER BY time_interval ASC
                    ");
                    
                    $stmt->execute([
                        ':start_date' => $startDate,
                        ':end_date' => $endDate
                    ]);
                    
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($data as $row) {
                        $labels[] = $row['time_interval'];
                    }
                    
                    $datasets = [
                        [
                            'label' => 'Uso Médio de Memória (MB)',
                            'data' => array_map(function($value) {
                                return round($value / (1024 * 1024), 2);
                            }, array_column($data, 'avg_value')),
                            'borderColor' => 'rgba(153, 102, 255, 1)',
                            'backgroundColor' => 'rgba(153, 102, 255, 0.2)',
                            'borderWidth' => 1,
                            'fill' => true
                        ],
                        [
                            'label' => 'Pico de Memória (MB)',
                            'data' => array_map(function($value) {
                                return round($value / (1024 * 1024), 2);
                            }, array_column($data, 'max_value')),
                            'borderColor' => 'rgba(255, 159, 64, 1)',
                            'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
                            'borderWidth' => 1,
                            'fill' => false
                        ]
                    ];
                    break;
                    
                case 'database_queries':
                    $stmt = $this->db->prepare("
                        SELECT 
                            DATE_FORMAT(timestamp, '%Y-%m-%d %H:%i') as time_interval,
                            AVG(query_count) as avg_queries,
                            AVG(query_time) as avg_time,
                            AVG(slow_queries) as avg_slow
                        FROM database_metrics
                        WHERE timestamp BETWEEN :start_date AND :end_date
                        GROUP BY time_interval
                        ORDER BY time_interval ASC
                    ");
                    
                    $stmt->execute([
                        ':start_date' => $startDate,
                        ':end_date' => $endDate
                    ]);
                    
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($data as $row) {
                        $labels[] = $row['time_interval'];
                    }
                    
                    $datasets = [
                        [
                            'label' => 'Número Médio de Consultas',
                            'data' => array_column($data, 'avg_queries'),
                            'borderColor' => 'rgba(75, 192, 192, 1)',
                            'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                            'borderWidth' => 1,
                            'fill' => true,
                            'yAxisID' => 'y-queries'
                        ],
                        [
                            'label' => 'Tempo Médio (ms)',
                            'data' => array_map(function($value) {
                                return $value * 1000; // segundos para milissegundos
                            }, array_column($data, 'avg_time')),
                            'borderColor' => 'rgba(54, 162, 235, 1)',
                            'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                            'borderWidth' => 1,
                            'fill' => false,
                            'yAxisID' => 'y-time'
                        ],
                        [
                            'label' => 'Consultas Lentas',
                            'data' => array_column($data, 'avg_slow'),
                            'borderColor' => 'rgba(255, 99, 132, 1)',
                            'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                            'borderWidth' => 1,
                            'fill' => false,
                            'yAxisID' => 'y-queries'
                        ]
                    ];
                    break;
                    
                case 'error_rate':
                    // Obter número de requisições por intervalo
                    $stmtRequests = $this->db->prepare("
                        SELECT 
                            DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as hour,
                            COUNT(*) as request_count
                        FROM performance_logs
                        WHERE timestamp BETWEEN :start_date AND :end_date
                        GROUP BY hour
                        ORDER BY hour ASC
                    ");
                    
                    $stmtRequests->execute([
                        ':start_date' => $startDate,
                        ':end_date' => $endDate
                    ]);
                    
                    $requests = [];
                    while ($row = $stmtRequests->fetch(PDO::FETCH_ASSOC)) {
                        $requests[$row['hour']] = $row['request_count'];
                    }
                    
                    // Obter número de erros por intervalo
                    $stmtErrors = $this->db->prepare("
                        SELECT 
                            DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as hour,
                            COUNT(*) as error_count
                        FROM error_logs
                        WHERE timestamp BETWEEN :start_date AND :end_date
                        GROUP BY hour
                        ORDER BY hour ASC
                    ");
                    
                    $stmtErrors->execute([
                        ':start_date' => $startDate,
                        ':end_date' => $endDate
                    ]);
                    
                    $errors = [];
                    while ($row = $stmtErrors->fetch(PDO::FETCH_ASSOC)) {
                        $errors[$row['hour']] = $row['error_count'];
                    }
                    
                    // Combinar dados e calcular taxa de erro
                    $hourlyData = [];
                    $errorRates = [];
                    $errorCounts = [];
                    $requestCounts = [];
                    
                    foreach ($requests as $hour => $count) {
                        $labels[] = $hour;
                        $errorCount = isset($errors[$hour]) ? $errors[$hour] : 0;
                        $errorRate = $count > 0 ? ($errorCount / $count) * 100 : 0;
                        
                        $errorRates[] = round($errorRate, 2);
                        $errorCounts[] = $errorCount;
                        $requestCounts[] = $count;
                    }
                    
                    $datasets = [
                        [
                            'label' => 'Taxa de Erro (%)',
                            'data' => $errorRates,
                            'borderColor' => 'rgba(255, 99, 132, 1)',
                            'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                            'borderWidth' => 1,
                            'fill' => true,
                            'yAxisID' => 'y-rate'
                        ],
                        [
                            'label' => 'Contagem de Erros',
                            'data' => $errorCounts,
                            'borderColor' => 'rgba(255, 159, 64, 1)',
                            'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
                            'borderWidth' => 1,
                            'fill' => false,
                            'yAxisID' => 'y-count'
                        ],
                        [
                            'label' => 'Requisições',
                            'data' => $requestCounts,
                            'borderColor' => 'rgba(75, 192, 192, 1)',
                            'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                            'borderWidth' => 1,
                            'fill' => false,
                            'yAxisID' => 'y-requests'
                        ]
                    ];
                    break;
            }
            
            return [
                'labels' => $labels,
                'datasets' => $datasets
            ];
        } catch (PDOException $e) {
            error_log('Erro ao obter dados para gráfico: ' . $e->getMessage());
            return [
                'labels' => [],
                'datasets' => []
            ];
        }
    }
    
    /**
     * Obtém alertas filtrados
     * 
     * @param string $type Tipo de alerta (opcional)
     * @param int $limit Limite de resultados
     * @param int $offset Offset para paginação
     * @return array Alertas filtrados
     */
    private function getFilteredAlerts($type = null, $limit = 10, $offset = 0) {
        try {
            $conditions = [];
            $params = [];
            
            if ($type !== null) {
                $conditions[] = "alert_type = :type";
                $params[':type'] = $type;
            }
            
            $whereClause = empty($conditions) ? "" : "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT 
                    id,
                    alert_type,
                    severity,
                    alert_data,
                    created_at
                FROM performance_alerts
                $whereClause
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decodificar dados do alerta
            foreach ($alerts as &$alert) {
                if (isset($alert['alert_data'])) {
                    $alert['data'] = json_decode($alert['alert_data'], true);
                    unset($alert['alert_data']); // Remover versão codificada
                }
            }
            
            return $alerts;
        } catch (PDOException $e) {
            error_log('Erro ao obter alertas filtrados: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém o número total de alertas com o filtro especificado
     * 
     * @param string $type Tipo de alerta (opcional)
     * @return int Número total de alertas
     */
    private function getAlertCount($type = null) {
        try {
            $conditions = [];
            $params = [];
            
            if ($type !== null) {
                $conditions[] = "alert_type = :type";
                $params[':type'] = $type;
            }
            
            $whereClause = empty($conditions) ? "" : "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT COUNT(*) as total
                FROM performance_alerts
                $whereClause
            ";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Erro ao obter contagem de alertas: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtém detalhes de um alerta específico
     * 
     * @param int $alertId ID do alerta
     * @return array|false Detalhes do alerta ou false se não encontrado
     */
    private function getAlertDetail($alertId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    alert_type,
                    severity,
                    alert_data,
                    created_at
                FROM performance_alerts
                WHERE id = :id
            ");
            
            $stmt->execute([':id' => $alertId]);
            
            $alert = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$alert) {
                return false;
            }
            
            // Decodificar dados do alerta
            if (isset($alert['alert_data'])) {
                $alert['data'] = json_decode($alert['alert_data'], true);
                unset($alert['alert_data']); // Remover versão codificada
            }
            
            return $alert;
        } catch (PDOException $e) {
            error_log('Erro ao obter detalhes do alerta: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém relatório detalhado de performance para uma URL específica
     * 
     * @param string $url URL
     * @param string $startDate Data inicial
     * @param string $endDate Data final
     * @return array Relatório detalhado
     */
    private function getUrlPerformanceReport($url, $startDate, $endDate) {
        try {
            // Obter estatísticas gerais
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_requests,
                    AVG(execution_time) as avg_execution_time,
                    MAX(execution_time) as max_execution_time,
                    MIN(execution_time) as min_execution_time,
                    STDDEV(execution_time) as std_dev_execution_time,
                    AVG(memory_peak) as avg_memory_peak,
                    MAX(memory_peak) as max_memory_peak
                FROM performance_logs
                WHERE request_uri = :url
                AND timestamp BETWEEN :start_date AND :end_date
            ");
            
            $stmt->execute([
                ':url' => $url,
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obter distribuição por hora
            $stmtHourly = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as hour,
                    COUNT(*) as request_count,
                    AVG(execution_time) as avg_execution_time,
                    MAX(execution_time) as max_execution_time
                FROM performance_logs
                WHERE request_uri = :url
                AND timestamp BETWEEN :start_date AND :end_date
                GROUP BY hour
                ORDER BY hour ASC
            ");
            
            $stmtHourly->execute([
                ':url' => $url,
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            
            $hourlyData = $stmtHourly->fetchAll(PDO::FETCH_ASSOC);
            
            // Obter exemplos de requisições (lentas e rápidas)
            $stmtSlowest = $this->db->prepare("
                SELECT 
                    request_id,
                    method,
                    execution_time,
                    memory_peak,
                    timestamp
                FROM performance_logs
                WHERE request_uri = :url
                AND timestamp BETWEEN :start_date AND :end_date
                ORDER BY execution_time DESC
                LIMIT 5
            ");
            
            $stmtSlowest->execute([
                ':url' => $url,
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            
            $slowestRequests = $stmtSlowest->fetchAll(PDO::FETCH_ASSOC);
            
            $stmtFastest = $this->db->prepare("
                SELECT 
                    request_id,
                    method,
                    execution_time,
                    memory_peak,
                    timestamp
                FROM performance_logs
                WHERE request_uri = :url
                AND timestamp BETWEEN :start_date AND :end_date
                ORDER BY execution_time ASC
                LIMIT 5
            ");
            
            $stmtFastest->execute([
                ':url' => $url,
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            
            $fastestRequests = $stmtFastest->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'summary' => $summary,
                'hourly_data' => $hourlyData,
                'slowest_requests' => $slowestRequests,
                'fastest_requests' => $fastestRequests
            ];
        } catch (PDOException $e) {
            error_log('Erro ao obter relatório de URL: ' . $e->getMessage());
            return [
                'summary' => [],
                'hourly_data' => [],
                'slowest_requests' => [],
                'fastest_requests' => []
            ];
        }
    }
    
    /**
     * Obtém configurações atuais de limiares
     * 
     * @return array Configurações de limiares
     */
    private function getCurrentThresholds() {
        try {
            // Verificar se a classe de limiares está disponível
            if (class_exists('App\Lib\Notification\NotificationThresholds')) {
                $thresholds = new \App\Lib\Notification\NotificationThresholds($this->db);
                return $thresholds->getAllThresholds();
            }
            
            // Implementação alternativa se a classe não estiver disponível
            $stmt = $this->db->prepare("
                SELECT 
                    context,
                    metric,
                    threshold_value,
                    description
                FROM performance_thresholds
                ORDER BY context, metric
            ");
            
            $stmt->execute();
            
            $results = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!isset($results[$row['context']])) {
                    $results[$row['context']] = [];
                }
                $results[$row['context']][$row['metric']] = [
                    'value' => $row['threshold_value'],
                    'description' => $row['description']
                ];
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter configurações de limiares: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Processa atualizações de limiares
     * 
     * @param array $postData Dados do formulário
     * @return bool Sucesso da operação
     */
    private function processThresholdUpdates($postData) {
        try {
            // Verificar se a classe de limiares está disponível
            if (class_exists('App\Lib\Notification\NotificationThresholds')) {
                $thresholds = new \App\Lib\Notification\NotificationThresholds($this->db);
                
                // Processar cada limiar atualizado
                foreach ($postData as $key => $value) {
                    if (strpos($key, 'threshold_') === 0) {
                        $parts = explode('_', $key);
                        if (count($parts) >= 3) {
                            $context = $parts[1];
                            $metric = $parts[2];
                            
                            // Validar valor
                            $thresholdValue = $this->validateInput($value, 'float', ['min' => 0]);
                            
                            if ($thresholdValue !== null) {
                                $thresholds->updateThreshold($context, $metric, $thresholdValue);
                            }
                        }
                    }
                }
                
                return true;
            }
            
            // Implementação alternativa se a classe não estiver disponível
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                UPDATE performance_thresholds
                SET threshold_value = :value
                WHERE context = :context AND metric = :metric
            ");
            
            // Processar cada limiar atualizado
            foreach ($postData as $key => $value) {
                if (strpos($key, 'threshold_') === 0) {
                    $parts = explode('_', $key);
                    if (count($parts) >= 3) {
                        $context = $parts[1];
                        $metric = $parts[2];
                        
                        // Validar valor
                        $thresholdValue = $this->validateInput($value, 'float', ['min' => 0]);
                        
                        if ($thresholdValue !== null) {
                            $stmt->execute([
                                ':value' => $thresholdValue,
                                ':context' => $context,
                                ':metric' => $metric
                            ]);
                        }
                    }
                }
            }
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Erro ao processar atualizações de limiares: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém uso atual de CPU
     * 
     * @return float Uso atual de CPU em porcentagem
     */
    private function getCurrentCpuUsage() {
        // Implementação segura para ambientes Windows e Linux
        try {
            if (function_exists('sys_getloadavg') && stripos(PHP_OS, 'win') === false) {
                $load = sys_getloadavg();
                return $load[0] * 100 / 4; // Assumindo 4 cores
            }
            
            // Fallback: obter o último valor registrado no banco de dados
            $stmt = $this->db->prepare("
                SELECT cpu_usage
                FROM resource_metrics
                ORDER BY timestamp DESC
                LIMIT 1
            ");
            
            $stmt->execute();
            $usage = $stmt->fetchColumn();
            
            return $usage !== false ? (float)$usage : 0;
        } catch (Exception $e) {
            error_log('Erro ao obter uso de CPU: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtém uso atual de memória
     * 
     * @return array Uso atual de memória (usado, total, porcentagem)
     */
    private function getCurrentMemoryUsage() {
        // Implementação segura para ambientes Windows e Linux
        try {
            $memoryInfo = [
                'used' => memory_get_usage(true),
                'total' => 0,
                'percentage' => 0
            ];
            
            // Tentar obter memória total do sistema
            if (stripos(PHP_OS, 'win') === false) {
                // Linux/Unix
                if (is_readable('/proc/meminfo')) {
                    $memInfo = file_get_contents('/proc/meminfo');
                    $matches = [];
                    if (preg_match('/MemTotal:\s+(\d+)\s+kB/i', $memInfo, $matches)) {
                        $memoryInfo['total'] = $matches[1] * 1024; // KB para bytes
                    }
                }
            }
            
            // Fallback: usar valor do último registro no banco de dados
            if ($memoryInfo['total'] === 0) {
                $stmt = $this->db->prepare("
                    SELECT memory_peak
                    FROM resource_metrics
                    ORDER BY timestamp DESC
                    LIMIT 1
                ");
                
                $stmt->execute();
                $peak = $stmt->fetchColumn();
                
                if ($peak !== false) {
                    $memoryInfo['used'] = (float)$peak;
                    // Usar um valor aproximado para memória total
                    $memoryInfo['total'] = $memoryInfo['used'] * 5; // Estimativa: 5x o pico
                }
            }
            
            // Calcular porcentagem
            if ($memoryInfo['total'] > 0) {
                $memoryInfo['percentage'] = ($memoryInfo['used'] / $memoryInfo['total']) * 100;
            }
            
            return $memoryInfo;
        } catch (Exception $e) {
            error_log('Erro ao obter uso de memória: ' . $e->getMessage());
            return [
                'used' => 0,
                'total' => 0,
                'percentage' => 0
            ];
        }
    }
    
    /**
     * Obtém uso atual de disco
     * 
     * @return array Uso atual de disco (usado, total, porcentagem)
     */
    private function getCurrentDiskUsage() {
        try {
            $directory = __DIR__;
            $diskInfo = [
                'used' => 0,
                'total' => 0,
                'percentage' => 0
            ];
            
            if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
                $diskInfo['total'] = disk_total_space($directory);
                $free = disk_free_space($directory);
                $diskInfo['used'] = $diskInfo['total'] - $free;
                
                if ($diskInfo['total'] > 0) {
                    $diskInfo['percentage'] = ($diskInfo['used'] / $diskInfo['total']) * 100;
                }
            } else {
                // Fallback: obter o último valor registrado no banco de dados
                $stmt = $this->db->prepare("
                    SELECT disk_usage
                    FROM resource_metrics
                    WHERE disk_usage IS NOT NULL
                    ORDER BY timestamp DESC
                    LIMIT 1
                ");
                
                $stmt->execute();
                $usage = $stmt->fetchColumn();
                
                $diskInfo['percentage'] = $usage !== false ? (float)$usage : 0;
            }
            
            return $diskInfo;
        } catch (Exception $e) {
            error_log('Erro ao obter uso de disco: ' . $e->getMessage());
            return [
                'used' => 0,
                'total' => 0,
                'percentage' => 0
            ];
        }
    }
    
    /**
     * Obtém número de usuários ativos
     * 
     * @return int Número de usuários ativos
     */
    private function getActiveUserCount() {
        try {
            // Usuários ativos nas últimas 15 minutos
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT user_id) as active_users
                FROM user_sessions
                WHERE last_activity > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
                AND user_id > 0
            ");
            
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('Erro ao obter contagem de usuários ativos: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtém número de processos ativos
     * 
     * @return int Número de processos ativos
     */
    private function getActiveProcessCount() {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as active_processes
                FROM async_processes
                WHERE status IN ('queued', 'processing', 'paused')
            ");
            
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('Erro ao obter contagem de processos ativos: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtém número de erros recentes
     * 
     * @return int Número de erros nas últimas 24 horas
     */
    private function getRecentErrorCount() {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as error_count
                FROM error_logs
                WHERE timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log('Erro ao obter contagem de erros recentes: ' . $e->getMessage());
            return 0;
        }
    }
}