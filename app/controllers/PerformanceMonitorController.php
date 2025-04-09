<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Controlador para monitoramento de testes de performance em ambiente de produção
 * Responsável por coletar, analisar e exibir métricas de performance reais
 * baseadas nos dados coletados de clientes
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

class PerformanceMonitorController {
    private $model;
    private $baseView = 'admin/';
    
    /**
     * Construtor
     * Inicializa os modelos necessários
     */
    public function __construct() {
        require_once 'app/models/PerformanceTestModel.php';
        $this->model = new PerformanceTestModel();
        
        // Verificar se o helper existe e incluí-lo
        if (file_exists('app/helpers/PerformanceHelper.php')) {
            require_once 'app/helpers/PerformanceHelper.php';
        }
    }
    
    /**
     * Sanitiza dados de entrada para prevenir XSS
     *
     * @param mixed $input Dados a serem sanitizados
     * @return mixed Dados sanitizados
     */
    private function sanitizeInput($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = $this->sanitizeInput($value);
            }
            return $input;
        }
        return htmlentities($input, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Valida token CSRF
     *
     * @param string $token Token CSRF a ser validado
     * @return bool True se o token for válido
     */
    private function validateCsrfToken($token) {
        if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        return true;
    }
    
    /**
     * Gera token CSRF
     *
     * @return string Token CSRF gerado
     */
    private function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Redireciona para uma URL específica
     *
     * @param string $path Caminho para redirecionar
     */
    private function redirect($path) {
        header('Location: ' . $path);
    }
    
    /**
     * Página principal do monitor de performance
     * Exibe um dashboard com métricas coletadas em ambiente de produção
     */
    public function index() {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        // Obter período de análise (padrão: 30 dias)
        $period = isset($_GET['period']) ? (int)$_GET['period'] : 30;
        if (!in_array($period, [7, 14, 30, 90, 180])) {
            $period = 30;
        }
        
        // Obter métricas para o período
        $metrics = $this->getProductionMetrics($period);
        
        // Verificar se há alertas de degradação de performance
        $degradation = $this->checkPerformanceDegradation($period);
        
        // Renderizar a view
        $data = [
            'title' => 'Monitoramento de Performance | Painel Administrativo',
            'metrics' => $metrics,
            'period' => $period,
            'degradation' => $degradation,
            'csrf_token' => $this->generateCsrfToken()
        ];
        
        require_once 'app/views/' . $this->baseView . 'performance_monitor.php';
    }
    
    /**
     * Exibe detalhes sobre o desempenho de uma página específica
     */
    public function pageDetail() {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        // Obter URL da página
        $pageUrl = isset($_GET['url']) ? $this->sanitizeInput($_GET['url']) : null;
        if (!$pageUrl) {
            $this->redirect('?page=performance_monitor');
            return;
        }
        
        // Obter período de análise (padrão: 30 dias)
        $period = isset($_GET['period']) ? (int)$_GET['period'] : 30;
        if (!in_array($period, [7, 14, 30, 90, 180])) {
            $period = 30;
        }
        
        // Obter métricas específicas da página
        $pageMetrics = $this->getPageMetrics($pageUrl, $period);
        
        // Obter tendências da página
        $trends = $this->getPageTrends($pageUrl, $period);
        
        // Renderizar a view
        $data = [
            'title' => 'Detalhes de Performance da Página | Painel Administrativo',
            'page_url' => $pageUrl,
            'metrics' => $pageMetrics,
            'trends' => $trends,
            'period' => $period,
            'csrf_token' => $this->generateCsrfToken()
        ];
        
        require_once 'app/views/' . $this->baseView . 'performance_page_detail.php';
    }
    
    /**
     * Exibe alertas de deterioração de performance
     */
    public function alerts() {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        // Obter período de análise (padrão: 30 dias)
        $period = isset($_GET['period']) ? (int)$_GET['period'] : 30;
        if (!in_array($period, [7, 14, 30, 90, 180])) {
            $period = 30;
        }
        
        // Obter todos os alertas
        $allAlerts = $this->getAllPerformanceAlerts($period);
        
        // Renderizar a view
        $data = [
            'title' => 'Alertas de Performance | Painel Administrativo',
            'alerts' => $allAlerts,
            'period' => $period,
            'csrf_token' => $this->generateCsrfToken()
        ];
        
        require_once 'app/views/' . $this->baseView . 'performance_alerts.php';
    }
    
    /**
     * Página de configurações do monitor de performance
     */
    public function settings() {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        // Processar envio de formulário
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verificar token CSRF
            if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido';
                $this->redirect('?page=performance_monitor&action=settings');
                return;
            }
            
            $settings = [
                'metrics_to_collect' => isset($_POST['metrics_to_collect']) ? $this->sanitizeInput($_POST['metrics_to_collect']) : [],
                'alert_thresholds' => [
                    'load_time' => isset($_POST['threshold_load_time']) ? (int)$_POST['threshold_load_time'] : 2000,
                    'lcp' => isset($_POST['threshold_lcp']) ? (int)$_POST['threshold_lcp'] : 2500,
                    'cls' => isset($_POST['threshold_cls']) ? (float)$_POST['threshold_cls'] : 0.1,
                    'change_percentage' => isset($_POST['threshold_change']) ? (int)$_POST['threshold_change'] : 20
                ],
                'sampling_rate' => isset($_POST['sampling_rate']) ? (int)$_POST['sampling_rate'] : 100,
                'excluded_paths' => isset($_POST['excluded_paths']) ? $this->parseMultilineInput($_POST['excluded_paths']) : [],
                'email_alerts' => isset($_POST['email_alerts']) ? (bool)$_POST['email_alerts'] : false,
                'alert_email' => isset($_POST['alert_email']) ? $this->sanitizeInput($_POST['alert_email']) : '',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Validar email se email_alerts estiver habilitado
            if ($settings['email_alerts'] && !filter_var($settings['alert_email'], FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = 'E-mail inválido para alertas';
                $this->redirect('?page=performance_monitor&action=settings');
                return;
            }
            
            // Salvar configurações
            $this->saveSettings($settings);
            
            // Redirecionar para evitar reenvio do formulário
            $_SESSION['success'] = 'Configurações salvas com sucesso';
            $this->redirect('?page=performance_monitor&action=settings');
            return;
        }
        
        // Obter configurações atuais
        $settings = $this->getSettings();
        
        // Renderizar a view
        $data = [
            'title' => 'Configurações de Monitoramento | Painel Administrativo',
            'settings' => $settings,
            'csrf_token' => $this->generateCsrfToken()
        ];
        
        require_once 'app/views/' . $this->baseView . 'performance_settings.php';
    }
    
    /**
     * Processa webhooks para coleta de métricas
     * Endpoint para receber métricas enviadas pelo cliente
     */
    public function processWebhook() {
        // Verificar se a requisição é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['error' => 'Método não permitido'], 405);
        }
        
        // Verificar token CSRF para requisições normais (não aplicável para webhooks de API)
        $isApiCall = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
        if (!$isApiCall && !$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            return $this->jsonResponse(['error' => 'Token CSRF inválido'], 403);
        }
        
        // Obter dados da requisição
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar dados básicos
        if (!isset($data['metrics'])) {
            return $this->jsonResponse(['error' => 'Dados incompletos'], 400);
        }
        
        // Sanitizar e processar dados
        $metrics = $this->sanitizeInput($data['metrics']);
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $this->sanitizeInput($_SERVER['HTTP_USER_AGENT']) : 'Desconhecido';
        $timestamp = date('Y-m-d H:i:s');
        $clientIp = $this->getClientIp();
        
        // Determinar tipo de dispositivo
        $deviceType = $this->detectDeviceType($userAgent);
        
        // Obter configurações
        $settings = $this->getSettings();
        
        // Aplicar taxa de amostragem
        if ($settings['sampling_rate'] < 100) {
            // Gerar número aleatório entre 1 e 100
            $random = mt_rand(1, 100);
            
            // Se o número for maior que a taxa de amostragem, ignorar esta métrica
            if ($random > $settings['sampling_rate']) {
                return $this->jsonResponse(['success' => true, 'message' => 'Amostra ignorada (sampling rate)']);
            }
        }
        
        // Verificar se o caminho está na lista de exclusões
        $pageUrl = isset($data['pageUrl']) ? $this->sanitizeInput($data['pageUrl']) : '';
        $excludedPaths = $settings['excluded_paths'];
        
        foreach ($excludedPaths as $path) {
            if (!empty($path) && strpos($pageUrl, $path) !== false) {
                return $this->jsonResponse(['success' => true, 'message' => 'URL excluída da coleta']);
            }
        }
        
        // Salvar métricas no banco de dados
        $saved = $this->model->saveProductionMetrics(
            $pageUrl,
            $metrics,
            $userAgent,
            $deviceType,
            $clientIp,
            $timestamp
        );
        
        // Verificar se é necessário enviar alertas
        if ($saved && $settings['email_alerts'] && !empty($settings['alert_email'])) {
            $this->checkAndSendAlerts($pageUrl, $metrics, $settings);
        }
        
        if ($saved) {
            return $this->jsonResponse(['success' => true, 'message' => 'Métricas recebidas com sucesso']);
        } else {
            return $this->jsonResponse(['error' => 'Erro ao salvar métricas'], 500);
        }
    }
    
    /**
     * Obtém métricas de performance em ambiente de produção
     * 
     * @param int $period Período em dias
     * @return array Métricas formatadas
     */
    private function getProductionMetrics($period = 30) {
        try {
            // Validar período
            $period = (int)$period;
            if ($period <= 0 || $period > 365) {
                $period = 30; // Valor seguro padrão
            }
            
            // Obter métricas básicas
            $metrics = $this->model->getProductionMetricsSummary($period);
            
            // Obter métricas ao longo do tempo
            $metrics['metrics_over_time'] = $this->model->getMetricsOverTime($period);
            
            // Obter distribuição de dispositivos
            $metrics['device_breakdown'] = $this->model->getDeviceBreakdown($period);
            
            // Obter páginas mais lentas
            $metrics['slowest_pages'] = $this->model->getSlowestPages($period, 10);
            
            // Obter páginas mais acessadas
            $metrics['top_pages'] = $this->model->getTopPages($period, 10);
            
            return $metrics;
        } catch (Exception $e) {
            error_log("Erro ao obter métricas de produção: " . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Erro ao obter métricas de produção'
            ];
        }
    }
    
    /**
     * Obtém métricas específicas de uma página
     * 
     * @param string $pageUrl URL da página
     * @param int $period Período em dias
     * @return array Métricas da página
     */
    private function getPageMetrics($pageUrl, $period = 30) {
        try {
            // Validar parâmetros
            $pageUrl = $this->sanitizeInput($pageUrl);
            $period = (int)$period;
            
            if (empty($pageUrl) || $period <= 0 || $period > 365) {
                throw new InvalidArgumentException('Parâmetros inválidos');
            }
            
            return $this->model->getPageMetrics($pageUrl, $period);
        } catch (Exception $e) {
            error_log("Erro ao obter métricas da página: " . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Erro ao obter métricas da página'
            ];
        }
    }
    
    /**
     * Obtém tendências de uma página ao longo do tempo
     * 
     * @param string $pageUrl URL da página
     * @param int $period Período em dias
     * @return array Tendências da página
     */
    private function getPageTrends($pageUrl, $period = 30) {
        try {
            // Validar parâmetros
            $pageUrl = $this->sanitizeInput($pageUrl);
            $period = (int)$period;
            
            if (empty($pageUrl) || $period <= 0 || $period > 365) {
                throw new InvalidArgumentException('Parâmetros inválidos');
            }
            
            return $this->model->getPageTrends($pageUrl, $period);
        } catch (Exception $e) {
            error_log("Erro ao obter tendências da página: " . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Erro ao obter tendências da página'
            ];
        }
    }
    
    /**
     * Verifica se há deterioração na performance
     * 
     * @param int $period Período em dias
     * @return array Informações sobre deterioração de performance
     */
    private function checkPerformanceDegradation($period = 30) {
        try {
            // Validar período
            $period = (int)$period;
            if ($period <= 0 || $period > 365) {
                $period = 30; // Valor seguro padrão
            }
            
            // Configurações para verificação
            $settings = $this->getSettings();
            $thresholdPercentage = $settings['alert_thresholds']['change_percentage'];
            
            // Dividir o período em duas partes para comparação
            $halfPeriod = ceil($period / 2);
            
            // Obter métricas médias para a primeira metade do período
            $oldMetrics = $this->model->getAverageMetricsForPeriod($period - $halfPeriod, $period);
            
            // Obter métricas médias para a segunda metade do período
            $newMetrics = $this->model->getAverageMetricsForPeriod(0, $halfPeriod);
            
            // Se não houver dados suficientes, retornar vazio
            if (empty($oldMetrics) || empty($newMetrics)) {
                return [
                    'alerts' => [],
                    'has_alerts' => false
                ];
            }
            
            // Comparar métricas e identificar deteriorações significativas
            $alerts = [];
            $metricsToCheck = [
                'avg_load_time' => 'Tempo de carregamento',
                'avg_lcp' => 'Largest Contentful Paint',
                'avg_fid' => 'First Input Delay',
                'avg_cls' => 'Cumulative Layout Shift'
            ];
            
            foreach ($metricsToCheck as $metric => $label) {
                if (isset($oldMetrics[$metric]) && isset($newMetrics[$metric]) && 
                    $oldMetrics[$metric] > 0 && $newMetrics[$metric] > 0) {
                    
                    // Calcular a mudança percentual
                    $change = (($newMetrics[$metric] - $oldMetrics[$metric]) / $oldMetrics[$metric]) * 100;
                    
                    // Se a mudança for significativa (pior), adicionar alerta
                    if (abs($change) >= $thresholdPercentage) {
                        $alerts[] = [
                            'metric' => $metric,
                            'label' => $label,
                            'previous' => $oldMetrics[$metric],
                            'recent' => $newMetrics[$metric],
                            'change' => $change,
                            'percent_change' => round($change, 1),
                            'direction' => $change > 0 ? 'worse' : 'better'
                        ];
                    }
                }
            }
            
            return [
                'alerts' => $alerts,
                'has_alerts' => count($alerts) > 0,
                'old_metrics' => $oldMetrics,
                'new_metrics' => $newMetrics
            ];
        } catch (Exception $e) {
            error_log("Erro ao verificar degradação de performance: " . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Erro ao verificar degradação de performance'
            ];
        }
    }
    
    /**
     * Obtém todos os alertas de performance
     * 
     * @param int $period Período em dias
     * @return array Lista de alertas
     */
    private function getAllPerformanceAlerts($period = 30) {
        try {
            // Validar período
            $period = (int)$period;
            if ($period <= 0 || $period > 365) {
                $period = 30; // Valor seguro padrão
            }
            
            // Obter configurações
            $settings = $this->getSettings();
            $thresholds = $settings['alert_thresholds'];
            
            // Obter métricas por página
            $pageMetrics = $this->model->getAllPagesMetrics($period);
            
            // Analisar métricas e gerar alertas
            $alerts = [];
            
            foreach ($pageMetrics as $page) {
                // Verificar tempo de carregamento
                if (isset($page['avg_load_time']) && $page['avg_load_time'] > $thresholds['load_time']) {
                    $alerts[] = [
                        'type' => 'threshold',
                        'metric' => 'load_time',
                        'label' => 'Tempo de carregamento',
                        'value' => $page['avg_load_time'],
                        'threshold' => $thresholds['load_time'],
                        'page_url' => $page['page_url'],
                        'views' => $page['count'],
                        'severity' => $this->calculateSeverity($page['avg_load_time'], $thresholds['load_time'])
                    ];
                }
                
                // Verificar LCP
                if (isset($page['avg_lcp']) && $page['avg_lcp'] > $thresholds['lcp']) {
                    $alerts[] = [
                        'type' => 'threshold',
                        'metric' => 'lcp',
                        'label' => 'Largest Contentful Paint',
                        'value' => $page['avg_lcp'],
                        'threshold' => $thresholds['lcp'],
                        'page_url' => $page['page_url'],
                        'views' => $page['count'],
                        'severity' => $this->calculateSeverity($page['avg_lcp'], $thresholds['lcp'])
                    ];
                }
                
                // Verificar CLS
                if (isset($page['avg_cls']) && $page['avg_cls'] > $thresholds['cls']) {
                    $alerts[] = [
                        'type' => 'threshold',
                        'metric' => 'cls',
                        'label' => 'Cumulative Layout Shift',
                        'value' => $page['avg_cls'],
                        'threshold' => $thresholds['cls'],
                        'page_url' => $page['page_url'],
                        'views' => $page['count'],
                        'severity' => $this->calculateSeverity($page['avg_cls'] / $thresholds['cls'], 1) // Normalizado
                    ];
                }
            }
            
            // Adicionar alertas de tendência
            $degradation = $this->checkPerformanceDegradation($period);
            if (!empty($degradation['alerts'])) {
                foreach ($degradation['alerts'] as $alert) {
                    if ($alert['direction'] === 'worse') {
                        $alerts[] = [
                            'type' => 'trend',
                            'metric' => $alert['metric'],
                            'label' => $alert['label'],
                            'previous' => $alert['previous'],
                            'recent' => $alert['recent'],
                            'percent_change' => $alert['percent_change'],
                            'severity' => $this->calculateSeverityFromChange($alert['percent_change'])
                        ];
                    }
                }
            }
            
            // Ordenar alertas por severidade (decrescente)
            usort($alerts, function($a, $b) {
                return $b['severity'] - $a['severity'];
            });
            
            return $alerts;
        } catch (Exception $e) {
            error_log("Erro ao obter alertas de performance: " . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Erro ao obter alertas de performance'
            ];
        }
    }
    
    /**
     * Verifica se há alertas graves e envia e-mail se necessário
     * 
     * @param string $pageUrl URL da página
     * @param array $metrics Métricas coletadas
     * @param array $settings Configurações
     * @return bool True se os alertas foram verificados com sucesso
     */
    private function checkAndSendAlerts($pageUrl, $metrics, $settings) {
        try {
            // Verificar se há métricas relevantes
            if (!isset($metrics['navigation']) && !isset($metrics['paint']) && !isset($metrics['largestPaint'])) {
                return false;
            }
            
            // Extrair métricas importantes
            $loadTime = isset($metrics['navigation']['loadEvent']) ? (float)$metrics['navigation']['loadEvent'] : null;
            $lcp = isset($metrics['largestPaint']['startTime']) ? (float)$metrics['largestPaint']['startTime'] : null;
            $cls = isset($metrics['layoutShift']['value']) ? (float)$metrics['layoutShift']['value'] : null;
            
            // Verificar se alguma métrica excede o limiar por uma margem significativa (50% acima)
            $thresholds = $settings['alert_thresholds'];
            $alertsToSend = [];
            
            if ($loadTime !== null && $loadTime > $thresholds['load_time'] * 1.5) {
                $alertsToSend[] = [
                    'metric' => 'Tempo de carregamento',
                    'value' => round($loadTime, 1) . ' ms',
                    'threshold' => $thresholds['load_time'] . ' ms',
                    'exceedBy' => round(($loadTime / $thresholds['load_time'] - 1) * 100, 1) . '%'
                ];
            }
            
            if ($lcp !== null && $lcp > $thresholds['lcp'] * 1.5) {
                $alertsToSend[] = [
                    'metric' => 'Largest Contentful Paint',
                    'value' => round($lcp, 1) . ' ms',
                    'threshold' => $thresholds['lcp'] . ' ms',
                    'exceedBy' => round(($lcp / $thresholds['lcp'] - 1) * 100, 1) . '%'
                ];
            }
            
            if ($cls !== null && $cls > $thresholds['cls'] * 1.5) {
                $alertsToSend[] = [
                    'metric' => 'Cumulative Layout Shift',
                    'value' => round($cls, 3),
                    'threshold' => $thresholds['cls'],
                    'exceedBy' => round(($cls / $thresholds['cls'] - 1) * 100, 1) . '%'
                ];
            }
            
            // Se houver alertas para enviar, montar e enviar e-mail
            if (!empty($alertsToSend)) {
                $subject = 'Alerta de Performance - Taverna da Impressão 3D';
                
                $body = "<h2>Alerta de Performance Crítica</h2>";
                $body .= "<p><strong>URL:</strong> " . htmlspecialchars($pageUrl) . "</p>";
                $body .= "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
                $body .= "<p>As seguintes métricas excederam significativamente os limiares estabelecidos:</p>";
                $body .= "<ul>";
                
                foreach ($alertsToSend as $alert) {
                    $body .= "<li><strong>{$alert['metric']}:</strong> {$alert['value']} (limite: {$alert['threshold']}, excedido em {$alert['exceedBy']})</li>";
                }
                
                $body .= "</ul>";
                $body .= "<p>Recomendamos verificar a página para otimizações de performance.</p>";
                $body .= "<p><a href='" . BASE_URL . "admin/performance_monitor'>Acesse o painel de monitoramento</a></p>";
                
                // Enviar e-mail
                return $this->sendEmail($settings['alert_email'], $subject, $body);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao verificar alertas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calcula a severidade de um alerta com base em quanto o valor excede o limiar
     * 
     * @param float $value Valor atual
     * @param float $threshold Limiar para comparação
     * @return int Severidade (1-10)
     */
    private function calculateSeverity($value, $threshold) {
        if ($value <= $threshold) return 0;
        
        // Calcular o quanto o valor excede o limiar (%)
        $exceedPercent = ($value / $threshold - 1) * 100;
        
        // Mapear para uma escala de 1-10
        if ($exceedPercent <= 10) return 1;
        if ($exceedPercent <= 25) return 3;
        if ($exceedPercent <= 50) return 5;
        if ($exceedPercent <= 100) return 7;
        return 10;
    }
    
    /**
     * Calcula a severidade com base na mudança percentual
     * 
     * @param float $percentChange Mudança percentual
     * @return int Severidade (1-10)
     */
    private function calculateSeverityFromChange($percentChange) {
        $absChange = abs($percentChange);
        
        if ($absChange <= 20) return 1;
        if ($absChange <= 30) return 3;
        if ($absChange <= 50) return 5;
        if ($absChange <= 100) return 7;
        return 10;
    }
    
    /**
     * Envia um e-mail
     * 
     * @param string $to Destinatário
     * @param string $subject Assunto
     * @param string $body Corpo da mensagem (HTML)
     * @return bool True se o e-mail foi enviado com sucesso
     */
    private function sendEmail($to, $subject, $body) {
        // Validar e-mail antes de enviar
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("Tentativa de envio de e-mail para endereço inválido: " . $to);
            return false;
        }
        
        // Sanitizar conteúdo
        $subject = $this->sanitizeInput($subject);
        
        // Cabeçalhos de e-mail seguros (sem injeção de cabeçalho)
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Taverna da Impressão 3D <no-reply@tavernaimpressao3d.com.br>\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        return mail($to, $subject, $body, $headers);
    }
    
    /**
     * Obtém o endereço IP do cliente, levando em conta proxies
     * 
     * @return string Endereço IP
     */
    private function getClientIp() {
        $ip = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Sanitizar IP
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }
    
    /**
     * Detecta o tipo de dispositivo com base no User Agent
     * 
     * @param string $userAgent User Agent do cliente
     * @return string Tipo de dispositivo (desktop, mobile, tablet)
     */
    private function detectDeviceType($userAgent) {
        $userAgent = strtolower($userAgent);
        
        $mobileKeywords = ['mobile', 'android', 'iphone', 'ipod', 'windows phone'];
        $tabletKeywords = ['ipad', 'tablet', 'android 3.0', 'xoom'];
        
        // Verificar se é um tablet
        foreach ($tabletKeywords as $keyword) {
            if (strpos($userAgent, $keyword) !== false) {
                return 'tablet';
            }
        }
        
        // Verificar se é um dispositivo móvel
        foreach ($mobileKeywords as $keyword) {
            if (strpos($userAgent, $keyword) !== false) {
                return 'mobile';
            }
        }
        
        // Caso contrário, considerar desktop
        return 'desktop';
    }
    
    /**
     * Converte texto de várias linhas em uma array
     * 
     * @param string $input Texto com múltiplas linhas
     * @return array Linhas individuais em uma array
     */
    private function parseMultilineInput($input) {
        // Validar entrada
        if (!is_string($input)) {
            return [];
        }
        
        $input = $this->sanitizeInput($input);
        $lines = explode("\n", str_replace("\r", "", $input));
        $result = [];
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (!empty($trimmed)) {
                $result[] = $trimmed;
            }
        }
        
        return $result;
    }
    
    /**
     * Obtém as configurações do monitor de performance
     * 
     * @return array Configurações
     */
    private function getSettings() {
        try {
            $settings = $this->model->getPerformanceMonitorSettings();
            
            // Se não houver configurações, retornar valores padrão
            if (empty($settings)) {
                return [
                    'metrics_to_collect' => ['navigation', 'resource', 'paint', 'memory', 'layout', 'firstInput', 'largestPaint'],
                    'alert_thresholds' => [
                        'load_time' => 2000,
                        'lcp' => 2500,
                        'cls' => 0.1,
                        'change_percentage' => 20
                    ],
                    'sampling_rate' => 100,
                    'excluded_paths' => ['/admin/', '/api/'],
                    'email_alerts' => false,
                    'alert_email' => '',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
            
            return $settings;
        } catch (Exception $e) {
            error_log("Erro ao obter configurações: " . $e->getMessage());
            
            // Retornar configurações padrão em caso de erro
            return [
                'metrics_to_collect' => ['navigation', 'resource', 'paint', 'memory', 'layout', 'firstInput', 'largestPaint'],
                'alert_thresholds' => [
                    'load_time' => 2000,
                    'lcp' => 2500,
                    'cls' => 0.1,
                    'change_percentage' => 20
                ],
                'sampling_rate' => 100,
                'excluded_paths' => ['/admin/', '/api/'],
                'email_alerts' => false,
                'alert_email' => '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Salva as configurações do monitor de performance
     * 
     * @param array $settings Configurações a serem salvas
     * @return bool True se salvas com sucesso
     */
    private function saveSettings($settings) {
        try {
            // Validar settings antes de salvar
            if (!is_array($settings)) {
                throw new InvalidArgumentException('Settings must be an array');
            }
            
            // Validar campos críticos
            if (!isset($settings['alert_thresholds']) || !is_array($settings['alert_thresholds'])) {
                throw new InvalidArgumentException('Invalid alert thresholds');
            }
            
            // Sanitizar valores antes de salvar
            $settings = $this->sanitizeInput($settings);
            
            return $this->model->savePerformanceMonitorSettings($settings);
        } catch (Exception $e) {
            error_log("Erro ao salvar configurações: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se o usuário atual tem permissões administrativas
     * 
     * @return bool True se o usuário for admin, false caso contrário
     */
    private function isAdmin() {
        // Verificar se a sessão está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar se o usuário está logado e é admin
        return isset($_SESSION['user']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
    }
    
    /**
     * Redireciona o usuário para a página de login
     */
    private function redirectToLogin() {
        $_SESSION['error'] = 'Acesso restrito. Faça login como administrador.';
        $this->redirect('?page=login&redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    
    /**
     * Retorna uma resposta JSON
     * 
     * @param array $data Dados a serem retornados
     * @param int $status Código de status HTTP
     * @return string JSON formatado
     */
    private function jsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}
?>