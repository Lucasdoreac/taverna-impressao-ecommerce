<?php
/**
 * AdminMonitoringController - Controlador para dashboard de monitoramento de performance
 * 
 * Gerencia interface de exibição de métricas e alertas de performance,
 * bem como controles para silenciamento, ajuste de thresholds e resolução de problemas.
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Controllers
 * @version    1.0.0
 */

require_once __DIR__ . '/../lib/Security/InputValidationTrait.php';
require_once __DIR__ . '/../lib/Security/SecurityManager.php';
require_once __DIR__ . '/../lib/Notification/NotificationManager.php';
require_once __DIR__ . '/../lib/Notification/NotificationThresholds.php';
require_once __DIR__ . '/../lib/Monitoring/ProactiveMonitoringService.php';

class AdminMonitoringController {
    use InputValidationTrait;
    
    /**
     * Gerenciador de notificações
     * 
     * @var NotificationManager
     */
    private $notificationManager;
    
    /**
     * Gerenciador de thresholds
     * 
     * @var NotificationThresholds
     */
    private $thresholds;
    
    /**
     * Serviço de monitoramento proativo
     * 
     * @var ProactiveMonitoringService
     */
    private $monitoringService;
    
    /**
     * Gerenciador de segurança
     * 
     * @var SecurityManager
     */
    private $securityManager;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->notificationManager = NotificationManager::getInstance();
        $this->thresholds = NotificationThresholds::getInstance();
        $this->monitoringService = ProactiveMonitoringService::getInstance();
        $this->securityManager = SecurityManager::getInstance();
    }
    
    /**
     * Verifica se o usuário tem permissão para acessar o controlador
     * 
     * @return bool
     */
    private function checkPermission() {
        // Verificar se está autenticado
        if (!$this->securityManager->isUserLoggedIn()) {
            header('Location: /login?redirect=/admin/monitoring');
            exit;
        }
        
        // Verificar se é administrador
        if (!$this->securityManager->hasRole('admin')) {
            header('Location: /dashboard?error=unauthorized');
            exit;
        }
        
        return true;
    }
    
    /**
     * Exibe o dashboard principal de monitoramento
     * 
     * @return void
     */
    public function dashboard() {
        $this->checkPermission();
        
        // Gerar relatório de saúde do sistema
        $healthReport = $this->monitoringService->generateSystemHealthReport();
        
        // Obter alertas ativos
        $activeAlerts = $this->notificationManager->getActivePerformanceAlerts();
        
        // Obter histórico recente de alertas (resolvidos)
        $alertHistory = $this->getRecentAlertHistory();
        
        // Obter métricas das últimas 24 horas para visualização
        $metricsData = $this->getMetricsForVisualization();
        
        // Renderizar o dashboard
        require_once __DIR__ . '/../views/admin/monitoring/dashboard.php';
    }
    
    /**
     * Exibe a página de gerenciamento de thresholds
     * 
     * @return void
     */
    public function thresholds() {
        $this->checkPermission();
        
        // Obter todos os thresholds ativos
        $allThresholds = $this->thresholds->getAllThresholds();
        
        // Obter histórico de ajustes de threshold
        $thresholdAdjustments = $this->getThresholdAdjustmentHistory();
        
        // Renderizar a página
        require_once __DIR__ . '/../views/admin/monitoring/thresholds.php';
    }
    
    /**
     * Exibe a página de histórico de alertas
     * 
     * @return void
     */
    public function alertHistory() {
        $this->checkPermission();
        
        // Obter parâmetros de filtragem
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $component = isset($_GET['component']) ? 
            $this->validateString($_GET['component'], ['maxLength' => 255]) : null;
        
        $severity = isset($_GET['severity']) ? 
            $this->validateString($_GET['severity'], ['maxLength' => 50]) : null;
        
        // Obter histórico completo com paginação
        $alertHistory = $this->notificationManager->getPerformanceAlertHistory($component, $limit, $offset);
        
        // Obter contagem total para paginação
        $totalAlerts = $this->getPerformanceAlertCount($component, $severity);
        $totalPages = ceil($totalAlerts / $limit);
        
        // Obter componentes distintos para filtro
        $availableComponents = $this->getDistinctComponents();
        
        // Renderizar a página
        require_once __DIR__ . '/../views/admin/monitoring/alert_history.php';
    }
    
    /**
     * Exibe a página de métricas em tempo real
     * 
     * @return void
     */
    public function liveMetrics() {
        $this->checkPermission();
        
        // Obter componente para visualização (opcional)
        $component = isset($_GET['component']) ? 
            $this->validateString($_GET['component'], ['maxLength' => 255]) : null;
        
        // Obter lista de componentes disponíveis
        $availableComponents = $this->getDistinctComponents();
        
        // Obter métricas para visualização em tempo real
        $liveMetricsData = $this->getLiveMetrics($component);
        
        // Renderizar a página
        require_once __DIR__ . '/../views/admin/monitoring/live_metrics.php';
    }
    
    /**
     * Endpoint AJAX para obter métricas em tempo real
     * 
     * @return void
     */
    public function ajaxGetLiveMetrics() {
        $this->checkPermission();
        
        // Verificar token CSRF
        if (!$this->securityManager->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Token CSRF inválido']);
            exit;
        }
        
        // Obter componente para visualização (opcional)
        $component = isset($_POST['component']) ? 
            $this->validateString($_POST['component'], ['maxLength' => 255]) : null;
        
        // Obter métricas para visualização em tempo real
        $liveMetricsData = $this->getLiveMetrics($component);
        
        // Retornar como JSON
        header('Content-Type: application/json');
        echo json_encode($liveMetricsData);
        exit;
    }
    
    /**
     * Processa a ação de resolver um alerta de performance
     * 
     * @return void
     */
    public function resolveAlert() {
        $this->checkPermission();
        
        // Verificar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/monitoring');
            exit;
        }
        
        // Verificar token CSRF
        if (!$this->securityManager->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: /admin/monitoring?error=invalid_csrf');
            exit;
        }
        
        // Validar parâmetros
        $alertId = isset($_POST['alert_id']) ? (int)$_POST['alert_id'] : 0;
        $resolution = isset($_POST['resolution']) ? 
            $this->validateString($_POST['resolution'], ['maxLength' => 1000]) : '';
        
        // Obter ID do usuário atual
        $userId = $this->securityManager->getCurrentUserId();
        
        // Resolver o alerta
        $success = $this->notificationManager->resolvePerformanceAlert($alertId, $userId, $resolution);
        
        if ($success) {
            header('Location: /admin/monitoring?message=alert_resolved');
        } else {
            header('Location: /admin/monitoring?error=failed_to_resolve');
        }
        
        exit;
    }
    
    /**
     * Processa a ação de silenciar uma métrica
     * 
     * @return void
     */
    public function silenceMetric() {
        $this->checkPermission();
        
        // Verificar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/monitoring/thresholds');
            exit;
        }
        
        // Verificar token CSRF
        if (!$this->securityManager->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: /admin/monitoring/thresholds?error=invalid_csrf');
            exit;
        }
        
        // Validar parâmetros
        $metric = isset($_POST['metric']) ? 
            $this->validateString($_POST['metric'], ['maxLength' => 255, 'required' => true]) : '';
        
        $component = isset($_POST['component']) ? 
            $this->validateString($_POST['component'], ['maxLength' => 255]) : null;
        
        $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 3600;
        
        // Obter ID do usuário atual
        $userId = $this->securityManager->getCurrentUserId();
        
        if (empty($metric)) {
            header('Location: /admin/monitoring/thresholds?error=invalid_metric');
            exit;
        }
        
        // Silenciar a métrica
        $success = $this->notificationManager->silenceMetric($metric, $component, $duration, $userId);
        
        if ($success) {
            header('Location: /admin/monitoring/thresholds?message=metric_silenced');
        } else {
            header('Location: /admin/monitoring/thresholds?error=failed_to_silence');
        }
        
        exit;
    }
    
    /**
     * Processa a ação de remover o silenciamento de uma métrica
     * 
     * @return void
     */
    public function unsilenceMetric() {
        $this->checkPermission();
        
        // Verificar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/monitoring/thresholds');
            exit;
        }
        
        // Verificar token CSRF
        if (!$this->securityManager->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: /admin/monitoring/thresholds?error=invalid_csrf');
            exit;
        }
        
        // Validar parâmetros
        $metric = isset($_POST['metric']) ? 
            $this->validateString($_POST['metric'], ['maxLength' => 255, 'required' => true]) : '';
        
        $component = isset($_POST['component']) ? 
            $this->validateString($_POST['component'], ['maxLength' => 255]) : null;
        
        if (empty($metric)) {
            header('Location: /admin/monitoring/thresholds?error=invalid_metric');
            exit;
        }
        
        // Remover silenciamento
        $success = $this->notificationManager->unsilenceMetric($metric, $component);
        
        if ($success) {
            header('Location: /admin/monitoring/thresholds?message=metric_unsilenced');
        } else {
            header('Location: /admin/monitoring/thresholds?error=failed_to_unsilence');
        }
        
        exit;
    }
    
    /**
     * Processa a ação de atualizar um threshold
     * 
     * @return void
     */
    public function updateThreshold() {
        $this->checkPermission();
        
        // Verificar método POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/monitoring/thresholds');
            exit;
        }
        
        // Verificar token CSRF
        if (!$this->securityManager->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: /admin/monitoring/thresholds?error=invalid_csrf');
            exit;
        }
        
        // Validar parâmetros
        $metric = isset($_POST['metric']) ? 
            $this->validateString($_POST['metric'], ['maxLength' => 255, 'required' => true]) : '';
        
        $threshold = isset($_POST['threshold']) ? (float)$_POST['threshold'] : 0;
        $operator = isset($_POST['operator']) ? 
            $this->validateString($_POST['operator'], ['maxLength' => 10]) : '>';
        
        $description = isset($_POST['description']) ? 
            $this->validateString($_POST['description'], ['maxLength' => 1000]) : '';
        
        if (empty($metric)) {
            header('Location: /admin/monitoring/thresholds?error=invalid_metric');
            exit;
        }
        
        // Atualizar o threshold
        $success = $this->thresholds->updateThreshold($metric, $threshold, $operator, $description);
        
        if ($success) {
            // Registrar o ajuste manualmente
            $this->logManualThresholdAdjustment($metric, $threshold, $operator);
            
            header('Location: /admin/monitoring/thresholds?message=threshold_updated');
        } else {
            header('Location: /admin/monitoring/thresholds?error=failed_to_update');
        }
        
        exit;
    }
    
    /**
     * Exibe a página de análise de tendências
     * 
     * @return void
     */
    public function trends() {
        $this->checkPermission();
        
        // Obter componente para visualização (opcional)
        $component = isset($_GET['component']) ? 
            $this->validateString($_GET['component'], ['maxLength' => 255]) : null;
        
        // Obter métrica para visualização (opcional)
        $metric = isset($_GET['metric']) ? 
            $this->validateString($_GET['metric'], ['maxLength' => 255]) : null;
        
        // Obter período para visualização (em dias)
        $period = isset($_GET['period']) ? (int)$_GET['period'] : 7;
        $period = max(1, min(90, $period)); // Entre 1 e 90 dias
        
        // Obter componentes disponíveis
        $availableComponents = $this->getDistinctComponents();
        
        // Obter métricas disponíveis
        $availableMetrics = $this->getDistinctMetrics();
        
        // Obter dados de tendência
        $trendsData = $this->getTrendsData($component, $metric, $period);
        
        // Renderizar a página
        require_once __DIR__ . '/../views/admin/monitoring/trends.php';
    }
    
    /**
     * Obtém histórico recente de alertas para o dashboard
     * 
     * @param int $limit Número máximo de alertas a retornar
     * @return array
     */
    private function getRecentAlertHistory($limit = 10) {
        try {
            $sql = "SELECT pd.id, n.title, n.message, n.created_at, pd.metric, pd.value, 
                    pd.threshold, pd.component, pd.severity, pd.resolved,
                    pd.resolved_at, pd.resolved_by, pd.resolution, u.name as resolver_name
                    FROM performance_dashboard pd
                    JOIN notifications n ON pd.notification_id = n.id
                    LEFT JOIN users u ON pd.resolved_by = u.id
                    WHERE pd.resolved = 1
                    ORDER BY pd.resolved_at DESC
                    LIMIT :limit";
            
            $db = Database::getInstance();
            $params = [':limit' => (int)$limit];
            
            return $db->fetchAll($sql, $params) ?: [];
        } catch (Exception $e) {
            error_log('Erro ao obter histórico recente de alertas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém métricas para visualização no dashboard
     * 
     * @return array
     */
    private function getMetricsForVisualization() {
        try {
            $metricsToShow = [
                'response_time',
                'memory_usage',
                'cpu_usage',
                'query_time'
            ];
            
            $result = [];
            
            foreach ($metricsToShow as $metric) {
                // Obter dados das últimas 24 horas para este métrica
                $sql = "SELECT component, metric_value, timestamp 
                        FROM performance_metrics 
                        WHERE metric_name = :metric 
                        AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        ORDER BY timestamp ASC";
                
                $db = Database::getInstance();
                $params = [':metric' => $metric];
                
                $data = $db->fetchAll($sql, $params) ?: [];
                
                // Agrupar por componente
                $byComponent = [];
                
                foreach ($data as $row) {
                    $component = $row['component'];
                    $timestamp = strtotime($row['timestamp']);
                    $value = (float)$row['metric_value'];
                    
                    if (!isset($byComponent[$component])) {
                        $byComponent[$component] = [];
                    }
                    
                    $byComponent[$component][] = [
                        'timestamp' => $timestamp,
                        'value' => $value
                    ];
                }
                
                $result[$metric] = [
                    'data' => $byComponent,
                    'threshold' => $this->thresholds->getThresholdForMetric($metric)
                ];
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Erro ao obter métricas para visualização: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém a contagem total de alertas de performance
     * 
     * @param string|null $component Filtro opcional por componente
     * @param string|null $severity Filtro opcional por severidade
     * @return int
     */
    private function getPerformanceAlertCount($component = null, $severity = null) {
        try {
            $sql = "SELECT COUNT(*) as count FROM performance_alerts_log WHERE 1=1";
            $params = [];
            
            if ($component !== null) {
                $sql .= " AND component = :component";
                $params[':component'] = $component;
            }
            
            if ($severity !== null) {
                $sql .= " AND severity = :severity";
                $params[':severity'] = $severity;
            }
            
            $db = Database::getInstance();
            $result = $db->fetchSingle($sql, $params);
            
            return ($result && isset($result['count'])) ? (int)$result['count'] : 0;
        } catch (Exception $e) {
            error_log('Erro ao obter contagem de alertas: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtém a lista de componentes distintos no sistema
     * 
     * @return array
     */
    private function getDistinctComponents() {
        try {
            $sql = "SELECT DISTINCT component FROM performance_metrics ORDER BY component";
            $db = Database::getInstance();
            $result = $db->fetchAll($sql);
            
            return $result ? array_column($result, 'component') : [];
        } catch (Exception $e) {
            error_log('Erro ao obter componentes distintos: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém a lista de métricas distintas no sistema
     * 
     * @return array
     */
    private function getDistinctMetrics() {
        try {
            $sql = "SELECT DISTINCT metric_name FROM performance_metrics ORDER BY metric_name";
            $db = Database::getInstance();
            $result = $db->fetchAll($sql);
            
            return $result ? array_column($result, 'metric_name') : [];
        } catch (Exception $e) {
            error_log('Erro ao obter métricas distintas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém métricas em tempo real para visualização
     * 
     * @param string|null $component Filtro opcional por componente
     * @return array
     */
    private function getLiveMetrics($component = null) {
        try {
            $sql = "SELECT metric_name, component, metric_value, timestamp 
                    FROM performance_metrics 
                    WHERE timestamp > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
            
            $params = [];
            
            if ($component !== null) {
                $sql .= " AND component = :component";
                $params[':component'] = $component;
            }
            
            $sql .= " ORDER BY timestamp DESC";
            
            $db = Database::getInstance();
            $metrics = $db->fetchAll($sql, $params) ?: [];
            
            // Agrupar por métrica e componente
            $result = [];
            
            foreach ($metrics as $metric) {
                $name = $metric['metric_name'];
                $comp = $metric['component'];
                
                if (!isset($result[$name])) {
                    $result[$name] = [];
                }
                
                if (!isset($result[$name][$comp])) {
                    $result[$name][$comp] = [];
                }
                
                $result[$name][$comp][] = [
                    'value' => (float)$metric['metric_value'],
                    'timestamp' => strtotime($metric['timestamp'])
                ];
            }
            
            // Adicionar informações de threshold
            foreach ($result as $name => &$components) {
                $threshold = $this->thresholds->getThresholdForMetric($name);
                $components['_threshold'] = $threshold;
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Erro ao obter métricas em tempo real: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém histórico de ajustes de threshold
     * 
     * @param int $limit Número máximo de ajustes a retornar
     * @return array
     */
    private function getThresholdAdjustmentHistory($limit = 50) {
        try {
            $sql = "SELECT ta.id, ta.metric, ta.new_value, ta.operator, 
                    ta.adjustment_type, ta.timestamp, u.name as user_name
                    FROM threshold_adjustments ta
                    LEFT JOIN users u ON ta.user_id = u.id
                    ORDER BY ta.timestamp DESC
                    LIMIT :limit";
            
            $db = Database::getInstance();
            $params = [':limit' => (int)$limit];
            
            return $db->fetchAll($sql, $params) ?: [];
        } catch (Exception $e) {
            error_log('Erro ao obter histórico de ajustes de threshold: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Registra um ajuste manual de threshold
     * 
     * @param string $metric Nome da métrica
     * @param float $value Novo valor
     * @param string $operator Operador
     * @return bool
     */
    private function logManualThresholdAdjustment($metric, $value, $operator) {
        try {
            $userId = $this->securityManager->getCurrentUserId();
            
            $sql = "INSERT INTO threshold_adjustments 
                    (metric, new_value, operator, adjustment_type, user_id, timestamp) 
                    VALUES 
                    (:metric, :new_value, :operator, 'manual', :user_id, NOW())";
            
            $db = Database::getInstance();
            $params = [
                ':metric' => $metric,
                ':new_value' => $value,
                ':operator' => $operator,
                ':user_id' => $userId
            ];
            
            return $db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao registrar ajuste manual de threshold: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém dados de tendência para análise
     * 
     * @param string|null $component Componente a ser analisado
     * @param string|null $metric Métrica a ser analisada
     * @param int $period Período em dias
     * @return array
     */
    private function getTrendsData($component = null, $metric = null, $period = 7) {
        try {
            $sql = "SELECT metric_name, component, metric_value, timestamp 
                    FROM performance_metrics 
                    WHERE timestamp > DATE_SUB(NOW(), INTERVAL :period DAY)";
            
            $params = [':period' => $period];
            
            if ($component !== null) {
                $sql .= " AND component = :component";
                $params[':component'] = $component;
            }
            
            if ($metric !== null) {
                $sql .= " AND metric_name = :metric";
                $params[':metric'] = $metric;
            }
            
            $sql .= " ORDER BY timestamp ASC";
            
            $db = Database::getInstance();
            $data = $db->fetchAll($sql, $params) ?: [];
            
            // Se não houver dados ou forem muito poucos, retornar vazio
            if (count($data) < 10) {
                return [];
            }
            
            // Agrupar por métrica e componente
            $grouped = [];
            
            foreach ($data as $row) {
                $metricName = $row['metric_name'];
                $comp = $row['component'];
                $key = "{$metricName}_{$comp}";
                
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'metric' => $metricName,
                        'component' => $comp,
                        'data' => []
                    ];
                }
                
                $grouped[$key]['data'][] = [
                    'value' => (float)$row['metric_value'],
                    'timestamp' => strtotime($row['timestamp'])
                ];
            }
            
            // Calcular tendências para cada grupo
            $result = [];
            
            foreach ($grouped as $key => $group) {
                if (count($group['data']) < 10) {
                    continue;
                }
                
                // Extrair dados para análise
                $timestamps = [];
                $values = [];
                
                foreach ($group['data'] as $point) {
                    $timestamps[] = $point['timestamp'];
                    $values[] = $point['value'];
                }
                
                // Calcular estatísticas
                $min = min($values);
                $max = max($values);
                $avg = array_sum($values) / count($values);
                
                // Calcular tendência linear
                $trend = $this->calculateLinearTrend($group['data']);
                
                // Estimar valor futuro em 24 horas
                $lastTimestamp = end($timestamps);
                $lastValue = end($values);
                $projection24h = $lastValue + ($trend * 24);
                
                // Obter threshold para esta métrica
                $threshold = $this->thresholds->getThresholdForMetric($group['metric']);
                
                // Determinar quando o threshold seria atingido (se aplicável)
                $timeToThreshold = null;
                
                if ($threshold) {
                    $timeToThreshold = $this->calculateTimeToThreshold(
                        $lastValue,
                        $threshold['value'],
                        $trend
                    );
                }
                
                $result[$key] = [
                    'metric' => $group['metric'],
                    'component' => $group['component'],
                    'data' => $group['data'],
                    'stats' => [
                        'min' => $min,
                        'max' => $max,
                        'avg' => $avg,
                        'trend' => $trend,
                        'projection_24h' => $projection24h,
                        'threshold' => $threshold ? $threshold['value'] : null,
                        'threshold_operator' => $threshold ? $threshold['operator'] : null,
                        'time_to_threshold' => $timeToThreshold
                    ]
                ];
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Erro ao obter dados de tendência: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calcula o coeficiente de tendência linear para um conjunto de pontos de dados
     * 
     * @param array $dataPoints Array de pontos de dados com timestamp e value
     * @return float Coeficiente de tendência (positivo = crescendo, negativo = diminuindo)
     */
    private function calculateLinearTrend($dataPoints) {
        // Extrair timestamps e valores
        $timestamps = [];
        $values = [];
        
        foreach ($dataPoints as $point) {
            $timestamps[] = $point['timestamp'];
            $values[] = $point['value'];
        }
        
        // Normalizar timestamps para horas a partir do primeiro ponto
        $firstTimestamp = $timestamps[0];
        $normalizedTimes = [];
        
        foreach ($timestamps as $timestamp) {
            $normalizedTimes[] = ($timestamp - $firstTimestamp) / 3600; // Converter para horas
        }
        
        // Calcular médias
        $meanTime = array_sum($normalizedTimes) / count($normalizedTimes);
        $meanValue = array_sum($values) / count($values);
        
        // Calcular coeficientes para regressão linear (y = ax + b)
        $numerator = 0;
        $denominator = 0;
        
        for ($i = 0; $i < count($normalizedTimes); $i++) {
            $numerator += ($normalizedTimes[$i] - $meanTime) * ($values[$i] - $meanValue);
            $denominator += pow($normalizedTimes[$i] - $meanTime, 2);
        }
        
        if ($denominator == 0) {
            return 0; // Evitar divisão por zero
        }
        
        // Retornar coeficiente angular (a)
        return $numerator / $denominator;
    }
    
    /**
     * Calcula quanto tempo levará até um valor atingir um threshold com base na tendência
     * 
     * @param float $currentValue Valor atual
     * @param float $thresholdValue Valor do threshold
     * @param float $trendCoefficient Coeficiente de tendência (unidades por hora)
     * @return float|null Horas até atingir o threshold, ou null se nunca atingir
     */
    private function calculateTimeToThreshold($currentValue, $thresholdValue, $trendCoefficient) {
        // Se não houver tendência, nunca atingirá o threshold
        if ($trendCoefficient == 0) {
            return null;
        }
        
        // Calcular diferença
        $difference = $thresholdValue - $currentValue;
        
        // Se a tendência for positiva (crescendo) e o valor atual for menor que o threshold
        if ($trendCoefficient > 0 && $difference > 0) {
            return $difference / $trendCoefficient;
        }
        
        // Se a tendência for negativa (diminuindo) e o valor atual for maior que o threshold
        if ($trendCoefficient < 0 && $difference < 0) {
            return abs($difference) / abs($trendCoefficient);
        }
        
        // Nos outros casos, ou já ultrapassou o threshold ou nunca vai atingir
        return null;
    }
}
