<?php
/**
 * MonitoringController - Controlador para sistema de monitoramento em tempo real
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Controllers
 * @version    1.0.0
 * @author     Claude
 */

require_once __DIR__ . '/../lib/Security/InputValidationTrait.php';
require_once __DIR__ . '/../lib/Security/SecurityManager.php';
require_once __DIR__ . '/../lib/Monitoring/PrintQueueMonitor.php';
require_once __DIR__ . '/../lib/Monitoring/PerformanceMonitor.php';

class MonitoringController {
    use InputValidationTrait;
    
    /**
     * Construtor
     */
    public function __construct() {
        // Inicializar monitoramento de performance
        PerformanceMonitor::initialize(true);
    }
    
    /**
     * Exibe o painel de monitoramento em tempo real
     */
    public function dashboard() {
        // Verificar autenticação do usuário
        if (!SecurityManager::checkAuthentication()) {
            header('Location: /login?redirect=' . urlencode('/monitoring/dashboard'));
            exit;
        }
        
        // Verificar se o usuário tem permissão de administrador
        // Código de verificação de permissão seria implementado aqui
        
        // Obter token CSRF
        $csrfToken = SecurityManager::generateCsrfToken();
        
        // Monitoramento da fila de impressão
        $queueMonitor = PrintQueueMonitor::getInstance();
        $dashboardData = $queueMonitor->getDashboardData();
        $alerts = $queueMonitor->getAlerts();
        
        // Registrar acesso ao dashboard
        PerformanceMonitor::addCheckpoint('dashboard_access');
        
        // Exibir view do dashboard
        $pageTitle = 'Dashboard de Monitoramento';
        
        include __DIR__ . '/../views/admin/monitoring/dashboard.php';
    }
    
    /**
     * Endpoint para obter dados do dashboard em formato JSON via AJAX
     */
    public function ajaxDashboardData() {
        // Verificar autenticação do usuário via AJAX
        if (!SecurityManager::checkAuthentication()) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autorizado']);
            exit;
        }
        
        // Verificar token CSRF
        $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        if (!SecurityManager::validateCsrfToken($token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF inválido']);
            exit;
        }
        
        // Verificar permissão de administrador
        // Código de verificação de permissão seria implementado aqui
        
        // Obter dados atualizados
        $queueMonitor = PrintQueueMonitor::getInstance();
        $dashboardData = $queueMonitor->getDashboardData();
        $alerts = $queueMonitor->getAlerts();
        
        // Combinar dados para resposta
        $response = [
            'dashboard' => $dashboardData,
            'alerts' => $alerts,
            'timestamp' => time(),
            'csrf_token' => SecurityManager::generateCsrfToken()
        ];
        
        // Registrar acesso AJAX
        PerformanceMonitor::addCheckpoint('ajax_dashboard_data');
        
        // Definir cabeçalhos e enviar resposta JSON
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    
    /**
     * Exibe detalhes de performance do sistema
     */
    public function performanceMetrics() {
        // Verificar autenticação do usuário
        if (!SecurityManager::checkAuthentication()) {
            header('Location: /login?redirect=' . urlencode('/monitoring/performance'));
            exit;
        }
        
        // Verificar se o usuário tem permissão de administrador
        // Código de verificação de permissão seria implementado aqui
        
        // Obter token CSRF
        $csrfToken = SecurityManager::generateCsrfToken();
        
        // Carregar métricas de performance
        $performanceData = $this->loadPerformanceData();
        
        // Registrar acesso às métricas
        PerformanceMonitor::addCheckpoint('performance_metrics_access');
        
        // Exibir view de métricas de performance
        $pageTitle = 'Métricas de Performance';
        
        include __DIR__ . '/../views/admin/monitoring/performance.php';
    }
    
    /**
     * Exibe estatísticas detalhadas da fila de impressão
     */
    public function queueStatistics() {
        // Verificar autenticação do usuário
        if (!SecurityManager::checkAuthentication()) {
            header('Location: /login?redirect=' . urlencode('/monitoring/queue-stats'));
            exit;
        }
        
        // Verificar se o usuário tem permissão de administrador
        // Código de verificação de permissão seria implementado aqui
        
        // Obter token CSRF
        $csrfToken = SecurityManager::generateCsrfToken();
        
        // Verificar período de análise
        $period = $this->validateString($_GET['period'] ?? '30d', [
            'allowedValues' => ['24h', '7d', '30d', '90d']
        ]) ?: '30d';
        
        // Obter estatísticas detalhadas
        $queueMonitor = PrintQueueMonitor::getInstance();
        $stats = $queueMonitor->getQueueStats(true);
        
        // Registrar acesso às estatísticas
        PerformanceMonitor::addCheckpoint('queue_statistics_access');
        
        // Exibir view de estatísticas da fila
        $pageTitle = 'Estatísticas da Fila de Impressão';
        
        include __DIR__ . '/../views/admin/monitoring/queue_statistics.php';
    }
    
    /**
     * Endpoint para obter alertas em formato JSON via AJAX
     */
    public function ajaxAlerts() {
        // Verificar autenticação do usuário via AJAX
        if (!SecurityManager::checkAuthentication()) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autorizado']);
            exit;
        }
        
        // Verificar token CSRF
        $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        if (!SecurityManager::validateCsrfToken($token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF inválido']);
            exit;
        }
        
        // Verificar permissão de administrador
        // Código de verificação de permissão seria implementado aqui
        
        // Obter alertas atualizados
        $queueMonitor = PrintQueueMonitor::getInstance();
        $alerts = $queueMonitor->getAlerts();
        
        // Criar resposta
        $response = [
            'alerts' => $alerts,
            'timestamp' => time(),
            'csrf_token' => SecurityManager::generateCsrfToken()
        ];
        
        // Registrar acesso AJAX
        PerformanceMonitor::addCheckpoint('ajax_alerts');
        
        // Definir cabeçalhos e enviar resposta JSON
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    
    /**
     * Carrega dados de performance do sistema
     * 
     * @return array Dados de performance
     */
    private function loadPerformanceData() {
        $performanceData = [
            'response_times' => [],
            'memory_usage' => [],
            'database_stats' => [],
            'error_rates' => [],
            'top_slow_operations' => []
        ];
        
        // Carregar dados de logs de performance
        $logFile = __DIR__ . '/../../logs/performance.log';
        if (file_exists($logFile)) {
            $logs = file($logFile);
            $logs = array_slice($logs, -100); // Últimas 100 entradas
            
            $responseTimes = [];
            foreach ($logs as $log) {
                // Extrair tempo de resposta
                if (preg_match('/Tempo: ([\d\.]+)s/', $log, $matches)) {
                    $responseTimes[] = (float)$matches[1];
                }
            }
            
            // Calcular estatísticas
            if (!empty($responseTimes)) {
                sort($responseTimes);
                $count = count($responseTimes);
                
                $performanceData['response_times'] = [
                    'average' => array_sum($responseTimes) / $count,
                    'median' => $responseTimes[floor($count / 2)],
                    'p95' => $responseTimes[floor($count * 0.95)],
                    'p99' => $responseTimes[floor($count * 0.99)],
                    'min' => $responseTimes[0],
                    'max' => $responseTimes[$count - 1]
                ];
            }
        }
        
        // Obter estatísticas de desempenho detalhadas
        try {
            $dataFile = __DIR__ . '/../../logs/performance/detail_' . date('Y-m-d') . '.json';
            if (file_exists($dataFile)) {
                $detailLines = file($dataFile);
                
                $dbQueryTimes = [];
                $memoryUsage = [];
                $operations = [];
                
                // Processar as últimas 50 entradas
                $detailLines = array_slice($detailLines, -50);
                foreach ($detailLines as $line) {
                    $data = json_decode($line, true);
                    if (isset($data['sql_queries'])) {
                        foreach ($data['sql_queries'] as $query) {
                            $dbQueryTimes[] = $query['execution_time'];
                        }
                    }
                    
                    if (isset($data['memory_peak'])) {
                        $memoryUsage[] = $data['memory_peak'];
                    }
                    
                    if (isset($data['checkpoints'])) {
                        foreach ($data['checkpoints'] as $checkpoint) {
                            if (isset($checkpoint['operation']) && isset($checkpoint['execution_time'])) {
                                $op = $checkpoint['operation'];
                                if (!isset($operations[$op])) {
                                    $operations[$op] = [
                                        'times' => [],
                                        'count' => 0
                                    ];
                                }
                                
                                $operations[$op]['times'][] = $checkpoint['execution_time'];
                                $operations[$op]['count']++;
                            }
                        }
                    }
                }
                
                // Processar dados de consultas DB
                if (!empty($dbQueryTimes)) {
                    $performanceData['database_stats'] = [
                        'avg_query_time' => array_sum($dbQueryTimes) / count($dbQueryTimes),
                        'total_queries' => count($dbQueryTimes),
                        'slow_queries' => count(array_filter($dbQueryTimes, function($time) { 
                            return $time > 0.1; // consultas >100ms
                        }))
                    ];
                }
                
                // Processar dados de memória
                if (!empty($memoryUsage)) {
                    $performanceData['memory_usage'] = [
                        'average' => array_sum($memoryUsage) / count($memoryUsage),
                        'peak' => max($memoryUsage)
                    ];
                }
                
                // Encontrar operações mais lentas
                $slowOperations = [];
                foreach ($operations as $op => $data) {
                    if (count($data['times']) > 0) {
                        $avgTime = array_sum($data['times']) / count($data['times']);
                        $slowOperations[$op] = [
                            'name' => $op,
                            'avg_time' => $avgTime,
                            'count' => $data['count']
                        ];
                    }
                }
                
                // Ordenar por tempo médio e pegar as 5 mais lentas
                usort($slowOperations, function($a, $b) {
                    return $b['avg_time'] <=> $a['avg_time'];
                });
                
                $performanceData['top_slow_operations'] = array_slice($slowOperations, 0, 5);
            }
        } catch (Exception $e) {
            error_log('Erro ao processar dados de performance: ' . $e->getMessage());
        }
        
        return $performanceData;
    }
}
