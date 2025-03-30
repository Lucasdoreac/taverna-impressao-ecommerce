<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Controlador para Monitoramento de Performance em Ambiente de Produção
 * Responsável por gerenciar a coleta e visualização de métricas de performance em ambiente real,
 * oferecendo insights sobre a experiência do usuário e alertas sobre deterioração de performance
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

class PerformanceMonitorController {
    private $model;
    private $baseView = 'admin/';
    private $enabledUserAgents = ['chrome', 'firefox', 'safari', 'edge'];
    
    /**
     * Construtor
     * Inicializa o modelo de monitoramento de performance
     */
    public function __construct() {
        require_once 'app/models/PerformanceMonitorModel.php';
        $this->model = new PerformanceMonitorModel();
        
        if (file_exists('app/helpers/PerformanceHelper.php')) {
            require_once 'app/helpers/PerformanceHelper.php';
        }
    }
    
    /**
     * Dashboard de monitoramento de performance
     * Exibe uma visão geral das métricas de performance em ambiente de produção
     */
    public function index() {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        // Determinar o período de análise
        $period = isset($_GET['period']) ? (int)$_GET['period'] : 30;
        $validPeriods = [7, 14, 30, 90, 180];
        $period = in_array($period, $validPeriods) ? $period : 30;
        
        // Obter métricas para o dashboard
        $data = [
            'title' => 'Monitoramento de Performance | Painel Administrativo',
            'metrics' => $this->model->getDashboardMetrics($period),
            'period' => $period,
            'settings' => $this->model->getMonitorSettings(),
            'degradation' => $this->model->checkPerformanceDegradation(min($period, 14))
        ];
        
        // Verificar se temos dados suficientes
        if (empty($data['metrics']) || empty($data['metrics']['page_views']) || $data['metrics']['page_views'] < 10) {
            $data['warning'] = 'Dados insuficientes para análise completa. Continue coletando métricas.';
        }
        
        // Renderizar a view
        require_once 'app/views/' . $this->baseView . 'performance_monitor.php';
    }
    
    /**
     * Endpoint para receber métricas de clientes em produção
     * Implementa sampling para minimizar o impacto na experiência do usuário
     * 
     * @return array Resposta em JSON
     */
    public function collectMetrics() {
        // Verificar se a requisição é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['error' => 'Método não permitido'], 405);
        }
        
        // Obter configurações de monitoramento
        $settings = $this->model->getMonitorSettings();
        
        // Se não houver configurações ou o monitoramento estiver desabilitado
        if (!$settings || isset($settings['sampling_rate']) && $settings['sampling_rate'] <= 0) {
            return $this->jsonResponse(['status' => 'disabled']);
        }
        
        // Amostragem com base na taxa configurada
        if (isset($settings['sampling_rate']) && mt_rand(1, 100) > ($settings['sampling_rate'] * 100)) {
            return $this->jsonResponse(['status' => 'sampled_out']);
        }
        
        // Obter dados da requisição
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar dados básicos
        if (!isset($data['pageUrl']) || !isset($data['metrics'])) {
            return $this->jsonResponse(['error' => 'Dados incompletos'], 400);
        }
        
        // Sanitizar e processar dados
        $metrics = $data['metrics'];
        $pageUrl = filter_var($data['pageUrl'], FILTER_SANITIZE_URL);
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
        
        // Verificar se a página está na lista de habilitadas
        $enabledPages = isset($settings['enabled_pages']) ? $settings['enabled_pages'] : [];
        $pageEnabled = $this->isPageEnabled($pageUrl, $enabledPages);
        
        if (!$pageEnabled) {
            return $this->jsonResponse(['status' => 'page_not_monitored']);
        }
        
        // Determinar o tipo de dispositivo
        $deviceType = $this->detectDeviceType($userAgent);
        
        // Obter ID de sessão (se disponível)
        $sessionId = isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : null;
        
        // Salvar métricas no modelo
        $result = $this->model->saveProductionMetrics($pageUrl, $metrics, $userAgent, $deviceType, $sessionId);
        
        if ($result) {
            return $this->jsonResponse(['success' => true, 'message' => 'Métricas recebidas com sucesso']);
        } else {
            return $this->jsonResponse(['error' => 'Erro ao salvar métricas'], 500);
        }
    }
    
    /**
     * Visualização detalhada de métricas para uma página específica
     * 
     * @param string $pageUrl URL da página para análise
     */
    public function pageDetail($pageUrl = null) {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        // Verificar se a URL da página foi fornecida
        if ($pageUrl === null) {
            if (isset($_GET['url'])) {
                $pageUrl = urldecode($_GET['url']);
            } else {
                header('Location: ?page=performance_monitor');
                exit;
            }
        }
        
        // Determinar o período e tipo de dispositivo para filtro
        $period = isset($_GET['period']) ? (int)$_GET['period'] : 30;
        $validPeriods = [7, 14, 30, 90, 180];
        $period = in_array($period, $validPeriods) ? $period : 30;
        
        $deviceType = isset($_GET['device']) ? $_GET['device'] : null;
        $validDevices = ['desktop', 'tablet', 'mobile', 'all'];
        $deviceType = in_array($deviceType, $validDevices) ? $deviceType : null;
        
        // Calcular datas de início e fim
        $endDate = date('Y-m-d H:i:s');
        $startDate = date('Y-m-d H:i:s', strtotime("-{$period} days"));
        
        // Obter métricas para a página
        $metrics = $this->model->getPageMetrics(
            $pageUrl,
            $startDate,
            $endDate,
            $deviceType === 'all' ? null : $deviceType
        );
        
        // Processar métricas para visualização
        $processedMetrics = $this->processPageMetrics($metrics);
        
        // Preparar dados para a view
        $data = [
            'title' => 'Análise de Performance: ' . $this->getPageTitle($pageUrl),
            'pageUrl' => $pageUrl,
            'period' => $period,
            'deviceType' => $deviceType ?: 'all',
            'metrics' => $metrics,
            'processedMetrics' => $processedMetrics,
            'devices' => $this->getDeviceDistribution($metrics)
        ];
        
        // Verificar se temos dados suficientes
        if (empty($metrics)) {
            $data['warning'] = 'Nenhum dado disponível para esta página no período selecionado.';
        } elseif (count($metrics) < 5) {
            $data['warning'] = 'Dados limitados disponíveis. As análises podem não ser estatisticamente significativas.';
        }
        
        // Renderizar a view
        require_once 'app/views/' . $this->baseView . 'performance_page_detail.php';
    }
    
    /**
     * Configurações do monitoramento de performance
     */
    public function settings() {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        // Processar envio de formulário
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settings = [
                'sampling_rate' => isset($_POST['sampling_rate']) ? floatval($_POST['sampling_rate']) / 100 : 0.1,
                'enabled_pages' => isset($_POST['enabled_pages']) ? $_POST['enabled_pages'] : [],
                'alert_threshold' => isset($_POST['alert_threshold']) ? floatval($_POST['alert_threshold']) : 20.0,
                'data_retention_days' => isset($_POST['data_retention_days']) ? (int)$_POST['data_retention_days'] : 90,
                'notification_email' => isset($_POST['notification_email']) ? $_POST['notification_email'] : ''
            ];
            
            // Validar e ajustar valores
            $settings['sampling_rate'] = max(0, min(1, $settings['sampling_rate']));
            $settings['alert_threshold'] = max(5, min(50, $settings['alert_threshold']));
            $settings['data_retention_days'] = max(7, min(365, $settings['data_retention_days']));
            
            $this->model->saveMonitorSettings($settings);
            $_SESSION['success'] = 'Configurações de monitoramento salvas com sucesso';
            
            header('Location: ?page=performance_monitor&action=settings');
            exit;
        }
        
        // Obter configurações atuais
        $data = [
            'title' => 'Configurações de Monitoramento | Painel Administrativo',
            'settings' => $this->model->getMonitorSettings(),
            'availablePages' => $this->getAvailablePages()
        ];
        
        // Renderizar a view
        require_once 'app/views/' . $this->baseView . 'performance_monitor_settings.php';
    }
    
    /**
     * Visualiza alertas de deterioração de performance
     */
    public function alerts() {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        // Determinar o período para análise
        $period = isset($_GET['period']) ? (int)$_GET['period'] : 14;
        $validPeriods = [7, 14, 30];
        $period = in_array($period, $validPeriods) ? $period : 14;
        
        // Obter configurações para threshold
        $settings = $this->model->getMonitorSettings();
        $threshold = isset($settings['alert_threshold']) ? $settings['alert_threshold'] : 20.0;
        
        // Verificar deterioração
        $degradation = $this->model->checkPerformanceDegradation($period, $threshold);
        
        // Obter métricas para contexto
        $metrics = $this->model->getDashboardMetrics($period);
        
        // Preparar dados para a view
        $data = [
            'title' => 'Alertas de Performance | Painel Administrativo',
            'period' => $period,
            'threshold' => $threshold,
            'degradation' => $degradation,
            'metrics' => $metrics
        ];
        
        // Renderizar a view
        require_once 'app/views/' . $this->baseView . 'performance_alerts.php';
    }
    
    /**
     * Endpoint para manutenção de dados
     * Limpa dados antigos conforme configurações
     * 
     * @return array Resposta em JSON
     */
    public function maintenance() {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Acesso negado'], 403);
        }
        
        // Obter configurações
        $settings = $this->model->getMonitorSettings();
        $daysToKeep = isset($settings['data_retention_days']) ? $settings['data_retention_days'] : 90;
        
        // Executar limpeza
        $result = $this->model->cleanupOldData($daysToKeep);
        
        if ($result) {
            return $this->jsonResponse([
                'success' => true, 
                'message' => 'Manutenção concluída com sucesso',
                'retention_days' => $daysToKeep
            ]);
        } else {
            return $this->jsonResponse(['error' => 'Erro ao executar manutenção'], 500);
        }
    }
    
    /**
     * Processa métricas de página para visualização e análise
     * 
     * @param array $metrics Métricas da página
     * @return array Métricas processadas
     */
    private function processPageMetrics($metrics) {
        if (empty($metrics)) {
            return [];
        }
        
        // Inicializar estatísticas
        $stats = [
            'count' => count($metrics),
            'averages' => [
                'loadTime' => 0,
                'ttfb' => 0,
                'fcp' => 0,
                'lcp' => 0,
                'cls' => 0,
                'fid' => 0,
                'tbt' => 0
            ],
            'percentiles' => [
                'loadTime' => [],
                'ttfb' => [],
                'fcp' => [],
                'lcp' => [],
                'cls' => [],
                'fid' => [],
                'tbt' => []
            ],
            'trends' => [
                'dates' => [],
                'loadTime' => [],
                'lcp' => []
            ]
        ];
        
        // Extrair valores para análise
        $values = [
            'loadTime' => [],
            'ttfb' => [],
            'fcp' => [],
            'lcp' => [],
            'cls' => [],
            'fid' => [],
            'tbt' => []
        ];
        
        // Agrupar por data para tendências
        $byDate = [];
        
        foreach ($metrics as $metric) {
            $metricData = isset($metric['metrics']) ? $metric['metrics'] : [];
            $date = substr($metric['timestamp'], 0, 10);
            
            // Coletar valores para estatísticas
            foreach ($values as $key => $val) {
                if (isset($metricData[$key])) {
                    $values[$key][] = $metricData[$key];
                }
            }
            
            // Agrupar por data
            if (!isset($byDate[$date])) {
                $byDate[$date] = [
                    'count' => 0,
                    'loadTime' => 0,
                    'lcp' => 0
                ];
            }
            
            $byDate[$date]['count']++;
            
            if (isset($metricData['loadTime'])) {
                $byDate[$date]['loadTime'] += $metricData['loadTime'];
            }
            
            if (isset($metricData['lcp'])) {
                $byDate[$date]['lcp'] += $metricData['lcp'];
            }
        }
        
        // Calcular médias para cada data
        foreach ($byDate as $date => $data) {
            if ($data['count'] > 0) {
                $stats['trends']['dates'][] = $date;
                $stats['trends']['loadTime'][] = round($data['loadTime'] / $data['count'], 2);
                $stats['trends']['lcp'][] = round($data['lcp'] / $data['count'], 2);
            }
        }
        
        // Calcular médias gerais
        foreach ($values as $key => $vals) {
            if (!empty($vals)) {
                $stats['averages'][$key] = round(array_sum($vals) / count($vals), 2);
                
                // Ordenar para percentis
                sort($vals);
                $count = count($vals);
                
                // Calcular percentis relevantes (p50, p75, p90, p95)
                $stats['percentiles'][$key] = [
                    'p50' => $vals[floor($count * 0.5)],
                    'p75' => $vals[floor($count * 0.75)],
                    'p90' => $vals[floor($count * 0.9)],
                    'p95' => $vals[floor($count * 0.95)]
                ];
            }
        }
        
        return $stats;
    }
    
    /**
     * Determina se a página deve ser monitorada com base nas configurações
     * 
     * @param string $pageUrl URL da página
     * @param array $enabledPages Lista de padrões de URL habilitados
     * @return bool True se a página deve ser monitorada
     */
    private function isPageEnabled($pageUrl, $enabledPages) {
        if (empty($enabledPages)) {
            return true; // Se não houver configuração, monitorar todas
        }
        
        // Verificar padrões exatos e com wildcards
        foreach ($enabledPages as $pattern) {
            // Converter padrão para expressão regular
            if (strpos($pattern, '*') !== false) {
                $regexPattern = str_replace(['/', '*'], ['\/', '.*'], $pattern);
                $regexPattern = '/^' . $regexPattern . '$/';
                
                if (preg_match($regexPattern, $pageUrl)) {
                    return true;
                }
            } else {
                // Comparação exata
                if ($pattern === $pageUrl) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Detecta o tipo de dispositivo com base no User Agent
     * 
     * @param string $userAgent String do User Agent
     * @return string Tipo de dispositivo (desktop, tablet, mobile)
     */
    private function detectDeviceType($userAgent) {
        $userAgent = strtolower($userAgent);
        
        // Verificar se é um dispositivo móvel
        if (
            strpos($userAgent, 'mobile') !== false || 
            strpos($userAgent, 'android') !== false ||
            strpos($userAgent, 'iphone') !== false
        ) {
            // Verificar se é um tablet
            if (
                strpos($userAgent, 'ipad') !== false || 
                strpos($userAgent, 'tablet') !== false
            ) {
                return 'tablet';
            }
            
            return 'mobile';
        }
        
        return 'desktop';
    }
    
    /**
     * Obtém a distribuição de tipos de dispositivos
     * 
     * @param array $metrics Métricas coletadas
     * @return array Distribuição de dispositivos
     */
    private function getDeviceDistribution($metrics) {
        $devices = [
            'desktop' => 0,
            'tablet' => 0,
            'mobile' => 0
        ];
        
        if (empty($metrics)) {
            return $devices;
        }
        
        foreach ($metrics as $metric) {
            if (isset($metric['device_type'])) {
                $deviceType = $metric['device_type'];
                if (isset($devices[$deviceType])) {
                    $devices[$deviceType]++;
                }
            }
        }
        
        return $devices;
    }
    
    /**
     * Obtém o título amigável para uma URL de página
     * 
     * @param string $pageUrl URL da página
     * @return string Título amigável
     */
    private function getPageTitle($pageUrl) {
        // Remover domínio se presente
        $path = parse_url($pageUrl, PHP_URL_PATH);
        if (!$path) {
            $path = $pageUrl;
        }
        
        // Sanitizar e formatar
        $path = trim($path, '/');
        if (empty($path)) {
            return 'Página Inicial';
        }
        
        // Verificar rotas comuns
        $routes = [
            'products' => 'Listagem de Produtos',
            'product' => 'Página de Produto',
            'cart' => 'Carrinho de Compras',
            'checkout' => 'Checkout',
            'categories' => 'Categorias',
            'search' => 'Busca de Produtos',
            'user' => 'Perfil do Usuário',
            'orders' => 'Histórico de Pedidos',
            'customization' => 'Customização 3D'
        ];
        
        foreach ($routes as $route => $title) {
            if (strpos($path, $route) === 0) {
                return $title;
            }
        }
        
        // Formatar outros caminhos
        return ucfirst(str_replace(['-', '_', '/'], ' ', $path));
    }
    
    /**
     * Obtém uma lista de páginas disponíveis para monitoramento
     * 
     * @return array Lista de páginas
     */
    private function getAvailablePages() {
        return [
            '/' => 'Página Inicial',
            '/products' => 'Listagem de Produtos',
            '/product/*' => 'Páginas de Produto (todas)',
            '/cart' => 'Carrinho de Compras',
            '/checkout' => 'Checkout',
            '/categories' => 'Categorias',
            '/category/*' => 'Páginas de Categoria (todas)',
            '/search' => 'Busca de Produtos',
            '/user/*' => 'Área do Usuário',
            '/orders' => 'Histórico de Pedidos',
            '/customization/*' => 'Customização 3D'
        ];
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
        header('Location: ?page=login&redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
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