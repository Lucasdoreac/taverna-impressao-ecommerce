<?php
/**
 * MonitoringController - Controlador para o painel de monitoramento
 * 
 * Gerencia o painel de monitoramento e fornece endpoints de API para os dados
 * de monitoramento, seguindo os guardrails de segurança estabelecidos.
 * 
 * @package App\Controllers\Admin
 * @version 1.0.0
 * @author Taverna da Impressão
 */
require_once dirname(__FILE__) . '/../../lib/Controller.php';
require_once dirname(__FILE__) . '/../../lib/Security/InputValidationTrait.php';
require_once dirname(__FILE__) . '/../../lib/Security/SecurityManager.php';
require_once dirname(__FILE__) . '/../../lib/Monitoring/PerformanceMonitor.php';
require_once dirname(__FILE__) . '/../../lib/Monitoring/PrintQueueMonitor.php';

class MonitoringController extends Controller {
    use InputValidationTrait;
    
    /**
     * Monitor de desempenho
     *
     * @var PerformanceMonitor
     */
    private $performanceMonitor;
    
    /**
     * Monitor da fila de impressão
     *
     * @var PrintQueueMonitor
     */
    private $printQueueMonitor;
    
    /**
     * Conexão PDO para o banco de dados
     *
     * @var \PDO
     */
    private $pdo;
    
    /**
     * Construtor
     *
     * @param \PDO $pdo Conexão com o banco de dados
     */
    public function __construct($pdo = null) {
        parent::__construct();
        
        $this->pdo = $pdo;
        
        // Inicializar monitores
        $this->performanceMonitor = PerformanceMonitor::getInstance([], $pdo);
        $this->printQueueMonitor = PrintQueueMonitor::getInstance([], $pdo);
    }
    
    /**
     * Exibe a página principal do painel de monitoramento
     */
    public function index() {
        // Verificar autenticação e autorização
        if (!SecurityManager::checkAuthentication()) {
            // Redirecionar para a página de login
            $this->redirect('/login?redirect=' . urlencode('/admin/monitoring'));
            return;
        }
        
        // Verificar permissões específicas (simplificado)
        if (!$this->hasAdminPermission()) {
            $this->renderView('errors/403', [
                'message' => 'Você não tem permissão para acessar o painel de monitoramento'
            ]);
            return;
        }
        
        // Obter dados para o dashboard
        $dashboardData = [
            'system_stats' => $this->performanceMonitor->getDashboardData(),
            'queue_state' => $this->printQueueMonitor->getQueueState(true),
            'alert_history' => $this->printQueueMonitor->getAlertHistory(10)
        ];
        
        // Renderizar view
        $this->renderView('admin/monitoring/index', $dashboardData);
    }
    
    /**
     * Endpoint de API para obter estado da fila de impressão
     * 
     * @return void Retorna JSON com o estado da fila
     */
    public function apiQueueState() {
        // Verificar autenticação via API
        if (!$this->validateApiRequest()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }
        
        // Validar parâmetros
        $update = $this->getValidatedParam('update', 'bool', ['default' => false]);
        
        try {
            // Obter estado da fila
            $queueState = $this->printQueueMonitor->getQueueState($update);
            
            // Adicionar timestamp da resposta
            $queueState['api_timestamp'] = date('Y-m-d H:i:s');
            
            $this->jsonResponse($queueState);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Endpoint de API para obter estatísticas da fila de impressão
     * 
     * @return void Retorna JSON com estatísticas
     */
    public function apiQueueStats() {
        // Verificar autenticação via API
        if (!$this->validateApiRequest()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }
        
        // Validar parâmetros
        $hours = $this->getValidatedParam('hours', 'int', [
            'default' => 24,
            'min' => 1,
            'max' => 168 // 1 semana
        ]);
        
        try {
            // Obter estatísticas
            $stats = $this->printQueueMonitor->getQueuePerformanceStats($hours);
            
            // Adicionar timestamp da resposta
            $stats['api_timestamp'] = date('Y-m-d H:i:s');
            
            $this->jsonResponse($stats);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Endpoint de API para obter histórico de alertas
     * 
     * @return void Retorna JSON com alertas
     */
    public function apiAlertHistory() {
        // Verificar autenticação via API
        if (!$this->validateApiRequest()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }
        
        // Validar parâmetros
        $limit = $this->getValidatedParam('limit', 'int', [
            'default' => 50,
            'min' => 1,
            'max' => 200
        ]);
        
        try {
            // Obter histórico de alertas
            $alerts = $this->printQueueMonitor->getAlertHistory($limit);
            
            $this->jsonResponse([
                'alerts' => $alerts,
                'count' => count($alerts),
                'api_timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Endpoint de API para obter estatísticas de desempenho geral
     * 
     * @return void Retorna JSON com estatísticas
     */
    public function apiPerformanceStats() {
        // Verificar autenticação via API
        if (!$this->validateApiRequest()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }
        
        // Validar parâmetros
        $minutes = $this->getValidatedParam('minutes', 'int', [
            'default' => 15,
            'min' => 5,
            'max' => 1440 // 24 horas
        ]);
        
        try {
            // Obter estatísticas
            $stats = $this->performanceMonitor->getAggregatedStats($minutes);
            
            // Adicionar timestamp da resposta
            $stats['api_timestamp'] = date('Y-m-d H:i:s');
            
            $this->jsonResponse($stats);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Página de monitoramento em tempo real
     */
    public function realtime() {
        // Verificar autenticação e autorização
        if (!SecurityManager::checkAuthentication()) {
            // Redirecionar para a página de login
            $this->redirect('/login?redirect=' . urlencode('/admin/monitoring/realtime'));
            return;
        }
        
        // Verificar permissões específicas (simplificado)
        if (!$this->hasAdminPermission()) {
            $this->renderView('errors/403', [
                'message' => 'Você não tem permissão para acessar o painel de monitoramento'
            ]);
            return;
        }
        
        // Renderizar view
        $this->renderView('admin/monitoring/realtime', [
            'refresh_interval' => 5, // segundos
            'api_endpoints' => [
                'queue_state' => '/admin/monitoring/api/queue-state',
                'queue_stats' => '/admin/monitoring/api/queue-stats',
                'alerts' => '/admin/monitoring/api/alerts',
                'performance' => '/admin/monitoring/api/performance'
            ]
        ]);
    }
    
    /**
     * Página de relatórios de desempenho
     */
    public function reports() {
        // Verificar autenticação e autorização
        if (!SecurityManager::checkAuthentication()) {
            // Redirecionar para a página de login
            $this->redirect('/login?redirect=' . urlencode('/admin/monitoring/reports'));
            return;
        }
        
        // Verificar permissões específicas (simplificado)
        if (!$this->hasAdminPermission()) {
            $this->renderView('errors/403', [
                'message' => 'Você não tem permissão para acessar os relatórios de desempenho'
            ]);
            return;
        }
        
        // Validar parâmetros de filtro
        $timeRange = $this->getValidatedParam('time_range', 'string', [
            'default' => 'today',
            'allowedValues' => ['today', 'yesterday', 'week', 'month', 'custom']
        ]);
        
        $startDate = $this->getValidatedParam('start_date', 'date', [
            'default' => date('Y-m-d', strtotime('-7 days')),
            'format' => 'Y-m-d'
        ]);
        
        $endDate = $this->getValidatedParam('end_date', 'date', [
            'default' => date('Y-m-d'),
            'format' => 'Y-m-d'
        ]);
        
        // Obter dados de relatório
        $reportData = $this->generateReport($timeRange, $startDate, $endDate);
        
        // Renderizar view
        $this->renderView('admin/monitoring/reports', [
            'time_range' => $timeRange,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'report_data' => $reportData
        ]);
    }
    
    /**
     * Gera dados de relatório com base nos filtros
     *
     * @param string $timeRange Intervalo de tempo predefinido
     * @param string $startDate Data de início para intervalo personalizado
     * @param string $endDate Data de fim para intervalo personalizado
     * @return array Dados do relatório
     */
    private function generateReport($timeRange, $startDate, $endDate) {
        // Implementação simplificada - em um cenário real, seria mais complexa
        return [
            'time_range' => $timeRange,
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'summary' => [
                'total_jobs' => 256,
                'completed_jobs' => 220,
                'failed_jobs' => 12,
                'average_time' => 3600, // segundos
                'success_rate' => 85.94
            ],
            'daily_stats' => [
                ['date' => '2025-03-28', 'jobs' => 35, 'avg_time' => 3200],
                ['date' => '2025-03-29', 'jobs' => 42, 'avg_time' => 3500],
                ['date' => '2025-03-30', 'jobs' => 38, 'avg_time' => 3700],
                ['date' => '2025-03-31', 'jobs' => 41, 'avg_time' => 3400],
                ['date' => '2025-04-01', 'jobs' => 39, 'avg_time' => 3600],
                ['date' => '2025-04-02', 'jobs' => 31, 'avg_time' => 3900],
                ['date' => '2025-04-03', 'jobs' => 30, 'avg_time' => 3800]
            ]
        ];
    }
    
    /**
     * Valida uma requisição de API
     * Verifica autenticação e token CSRF para APIs
     *
     * @return bool True se a requisição for válida
     */
    private function validateApiRequest() {
        // Verificar autenticação
        if (!SecurityManager::checkAuthentication()) {
            return false;
        }
        
        // Verificar token CSRF no cabeçalho
        $csrfToken = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : null;
        
        if (!$csrfToken || !require_once dirname(__FILE__) . '/../../lib/Security/CsrfProtection.php' || 
            !CsrfProtection::validateToken($csrfToken, false)) {
            return false;
        }
        
        // Verificar permissões específicas (simplificado)
        if (!$this->hasAdminPermission()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Envia resposta JSON com os cabeçalhos apropriados
     *
     * @param array $data Dados a serem retornados como JSON
     * @param int $status Código de status HTTP
     * @return void
     */
    private function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');
        
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Verifica se o usuário tem permissão administrativa
     * Implementação simplificada - em um cenário real, verificaria permissões específicas
     *
     * @return bool True se o usuário tiver permissão
     */
    private function hasAdminPermission() {
        // Verificar se sessão tem flag de admin (simplificado)
        return isset($_SESSION['user_is_admin']) && $_SESSION['user_is_admin'] === true;
    }
}