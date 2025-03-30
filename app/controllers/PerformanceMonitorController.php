<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Controlador para Monitor de Testes de Performance
 * Responsável por gerenciar o monitoramento em tempo real dos testes de performance,
 * incluindo visualização de métricas, alertas e relatórios
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

class PerformanceMonitorController {
    private $model;
    private $baseView = 'admin/';
    private $metricTypes = [
        'page_load_time',     // Tempo de carregamento de página
        'ttfb',               // Time to First Byte
        'fcp',                // First Contentful Paint
        'lcp',                // Largest Contentful Paint
        'cls',                // Cumulative Layout Shift
        'fid',                // First Input Delay
        'resource_size',      // Tamanho dos recursos
        'resource_count',     // Número de recursos
        'memory_usage',       // Uso de memória
        'cpu_usage',          // Uso de CPU
        'query_time',         // Tempo de consulta ao banco de dados
        'api_response_time',  // Tempo de resposta da API
        'render_time',        // Tempo de renderização
        'fps'                 // Frames por segundo (visualizador 3D)
    ];
    
    /**
     * Construtor
     * Inicializa o modelo de monitor de performance
     */
    public function __construct() {
        require_once 'app/models/PerformanceMonitorModel.php';
        $this->model = new PerformanceMonitorModel();
        
        // Verificar se o helper existe e incluí-lo
        if (file_exists('app/helpers/PerformanceMonitorHelper.php')) {
            require_once 'app/helpers/PerformanceMonitorHelper.php';
        }
    }
    
    /**
     * Página principal do monitor de performance
     * Exibe dashboard para monitoramento em tempo real
     */
    public function index() {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        // Obter monitores ativos
        $activeMonitors = $this->model->getActiveMonitors();
        
        // Obter resultados mais recentes
        $latestResults = $this->model->getLatestResults(10);
        
        // Preparar dados para a view
        $data = [
            'title' => 'Monitor de Performance | Painel Administrativo',
            'activeMonitors' => $activeMonitors,
            'latestResults' => $latestResults,
            'metricTypes' => $this->metricTypes
        ];
        
        // Renderizar a view
        require_once 'app/views/' . $this->baseView . 'performance_monitor.php';
    }
    
    /**
     * Inicia um novo monitoramento de teste
     * 
     * @return array Resposta em formato JSON
     */
    public function startMonitoring() {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Acesso negado'], 403);
        }
        
        // Verificar se a requisição é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['error' => 'Método não permitido'], 405);
        }
        
        // Obter dados da requisição
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar dados básicos
        if (!isset($data['config']) || !is_array($data['config'])) {
            $data['config'] = [];
        }
        
        // Adicionar configurações padrão se não existirem
        if (!isset($data['config']['alert_thresholds'])) {
            $data['config']['alert_thresholds'] = [
                'page_load_time' => 2000,  // ms
                'ttfb' => 200,             // ms
                'lcp' => 2500,             // ms
                'cls' => 0.1,              // score
                'fid' => 100,              // ms
                'query_time' => 200,       // ms
                'api_response_time' => 300 // ms
            ];
        }
        
        if (!isset($data['config']['sample_interval'])) {
            $data['config']['sample_interval'] = 5; // segundos
        }
        
        if (!isset($data['config']['duration'])) {
            $data['config']['duration'] = 300; // segundos (5 minutos)
        }
        
        // Obter ID do teste associado (se fornecido)
        $testId = isset($data['test_id']) ? (int)$data['test_id'] : null;
        
        // Iniciar monitoramento
        $monitorId = $this->model->startMonitoring($testId, $data['config']);
        
        if (!$monitorId) {
            return $this->jsonResponse(['error' => 'Falha ao iniciar monitoramento'], 500);
        }
        
        return $this->jsonResponse([
            'success' => true,
            'monitor_id' => $monitorId,
            'message' => 'Monitoramento iniciado com sucesso'
        ]);
    }
    
    /**
     * Finaliza um monitoramento em andamento
     * 
     * @param int $monitorId ID do monitor a ser finalizado
     * @return array Resposta em formato JSON
     */
    public function stopMonitoring($monitorId) {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Acesso negado'], 403);
        }
        
        // Verificar se o monitoramento existe
        $monitor = $this->model->getMonitorById($monitorId);
        if (!$monitor) {
            return $this->jsonResponse(['error' => 'Monitoramento não encontrado'], 404);
        }
        
        // Verificar se o monitoramento já foi finalizado
        if ($monitor['status'] !== 'running') {
            return $this->jsonResponse(['error' => 'Monitoramento já finalizado'], 400);
        }
        
        // Preparar resultados finais (resumo)
        $results = [
            'summary' => [
                'duration' => $this->calculateDuration($monitor['start_time'], date('Y-m-d H:i:s')),
                'alert_count' => count($monitor['alerts']),
                'status' => 'completed',
                'completion_time' => date('Y-m-d H:i:s')
            ]
        ];
        
        // Adicionar análise das métricas se disponíveis
        if (isset($monitor['results']['metrics'])) {
            $results['summary']['metrics_analysis'] = $this->analyzeMetrics($monitor['results']['metrics']);
        }
        
        // Finalizar monitoramento
        $success = $this->model->stopMonitoring($monitorId, $results);
        
        if (!$success) {
            return $this->jsonResponse(['error' => 'Falha ao finalizar monitoramento'], 500);
        }
        
        return $this->jsonResponse([
            'success' => true,
            'message' => 'Monitoramento finalizado com sucesso',
            'summary' => $results['summary']
        ]);
    }
    
    /**
     * Registra uma métrica de monitoramento
     * 
     * @return array Resposta em formato JSON
     */
    public function recordMetric() {
        // Verificar se a requisição é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['error' => 'Método não permitido'], 405);
        }
        
        // Obter dados da requisição
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar dados básicos
        if (!isset($data['monitor_id']) || !isset($data['metric_name']) || !isset($data['value'])) {
            return $this->jsonResponse(['error' => 'Dados incompletos'], 400);
        }
        
        $monitorId = (int)$data['monitor_id'];
        $metricName = $data['metric_name'];
        $value = $data['value'];
        $timestamp = isset($data['timestamp']) ? $data['timestamp'] : null;
        
        // Verificar se o monitoramento existe e está ativo
        $monitor = $this->model->getMonitorById($monitorId);
        if (!$monitor || $monitor['status'] !== 'running') {
            return $this->jsonResponse(['error' => 'Monitoramento não encontrado ou inativo'], 404);
        }
        
        // Registrar métrica
        $success = $this->model->recordMetric($monitorId, $metricName, $value, $timestamp);
        
        if (!$success) {
            return $this->jsonResponse(['error' => 'Falha ao registrar métrica'], 500);
        }
        
        // Verificar se deve gerar alerta
        $alertCreated = false;
        if (isset($monitor['config']['alert_thresholds'][$metricName])) {
            $threshold = $monitor['config']['alert_thresholds'][$metricName];
            
            // Comparar valor com threshold (assumindo que valores mais altos são ruins)
            $shouldAlert = false;
            
            // Métricas onde valores mais altos são ruins
            $highIsWorse = ['page_load_time', 'ttfb', 'lcp', 'cls', 'fid', 'query_time', 'api_response_time'];
            if (in_array($metricName, $highIsWorse) && $value > $threshold) {
                $shouldAlert = true;
            }
            
            // Métricas onde valores mais baixos são ruins (ex: fps)
            $lowIsWorse = ['fps'];
            if (in_array($metricName, $lowIsWorse) && $value < $threshold) {
                $shouldAlert = true;
            }
            
            if ($shouldAlert) {
                $alertCreated = $this->model->createAlert($monitorId, $metricName, $threshold, $value, $timestamp);
            }
        }
        
        return $this->jsonResponse([
            'success' => true,
            'alert_created' => $alertCreated
        ]);
    }
    
    /**
     * Obtém dados de um monitoramento para exibição em tempo real
     * 
     * @param int $monitorId ID do monitor
     * @return array Resposta em formato JSON
     */
    public function getMonitorData($monitorId) {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Acesso negado'], 403);
        }
        
        // Obter dados do monitor
        $monitor = $this->model->getMonitorById($monitorId);
        if (!$monitor) {
            return $this->jsonResponse(['error' => 'Monitoramento não encontrado'], 404);
        }
        
        // Preparar dados para resposta
        $data = [
            'id' => $monitor['id'],
            'test_id' => $monitor['test_id'],
            'start_time' => $monitor['start_time'],
            'end_time' => $monitor['end_time'],
            'status' => $monitor['status'],
            'config' => $monitor['config'],
            'results' => $monitor['results'],
            'alerts' => $monitor['alerts'],
            'duration' => $this->calculateDuration($monitor['start_time'], 
                           $monitor['status'] === 'running' ? date('Y-m-d H:i:s') : $monitor['end_time'])
        ];
        
        return $this->jsonResponse($data);
    }
    
    /**
     * Exibe relatório detalhado de um monitoramento
     * 
     * @param int $monitorId ID do monitor
     */
    public function viewReport($monitorId) {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            $this->redirectToLogin();
            return;
        }
        
        // Obter dados do monitor
        $monitor = $this->model->getMonitorById($monitorId);
        if (!$monitor) {
            $_SESSION['error'] = 'Monitoramento não encontrado';
            header('Location: ?page=performance_monitor');
            exit;
        }
        
        // Obter teste associado se existir
        $testData = null;
        if ($monitor['test_id']) {
            // Incluir modelo de teste se necessário
            if (!class_exists('PerformanceTestModel')) {
                require_once 'app/models/PerformanceTestModel.php';
            }
            $testModel = new PerformanceTestModel();
            $testData = $testModel->getTestById($monitor['test_id']);
        }
        
        // Gerar recomendações
        $recommendations = $this->generateRecommendations($monitor);
        
        // Preparar dados para a view
        $data = [
            'title' => 'Relatório de Monitoramento | Painel Administrativo',
            'monitor' => $monitor,
            'testData' => $testData,
            'recommendations' => $recommendations,
            'metricAnalysis' => $this->analyzeMetrics(isset($monitor['results']['metrics']) ? $monitor['results']['metrics'] : [])
        ];
        
        // Renderizar a view
        require_once 'app/views/' . $this->baseView . 'performance_monitor_report.php';
    }
    
    /**
     * Compara os resultados de um monitor com um baseline
     * 
     * @param int $monitorId ID do monitor a ser comparado
     * @param int $baselineId ID do monitor de baseline
     * @return array Resposta em formato JSON
     */
    public function compareWithBaseline($monitorId, $baselineId) {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Acesso negado'], 403);
        }
        
        // Realizar comparação
        $comparison = $this->model->compareWithBaseline($monitorId, $baselineId);
        
        return $this->jsonResponse($comparison);
    }
    
    /**
     * Define um monitor como baseline para comparações futuras
     * 
     * @return array Resposta em formato JSON
     */
    public function setAsBaseline() {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Acesso negado'], 403);
        }
        
        // Verificar se a requisição é POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->jsonResponse(['error' => 'Método não permitido'], 405);
        }
        
        // Obter dados da requisição
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar dados básicos
        if (!isset($data['monitor_id']) || !isset($data['name'])) {
            return $this->jsonResponse(['error' => 'Dados incompletos'], 400);
        }
        
        $monitorId = (int)$data['monitor_id'];
        $name = $data['name'];
        $notes = isset($data['notes']) ? $data['notes'] : '';
        
        // Verificar se o monitoramento existe e está finalizado
        $monitor = $this->model->getMonitorById($monitorId);
        if (!$monitor || $monitor['status'] !== 'completed') {
            return $this->jsonResponse([
                'error' => 'Monitoramento não encontrado ou não finalizado'
            ], 404);
        }
        
        // Salvar como baseline
        try {
            $sql = "INSERT INTO performance_baselines (name, monitor_id, is_active, created_at, notes) 
                    VALUES (:name, :monitor_id, TRUE, NOW(), :notes)";
            
            $params = [
                'name' => $name,
                'monitor_id' => $monitorId,
                'notes' => $notes
            ];
            
            $this->model->db()->execute($sql, $params);
            $baselineId = $this->model->db()->lastInsertId();
            
            return $this->jsonResponse([
                'success' => true,
                'baseline_id' => $baselineId,
                'message' => 'Baseline criado com sucesso'
            ]);
        } catch (Exception $e) {
            error_log("Erro ao criar baseline: " . $e->getMessage());
            return $this->jsonResponse([
                'error' => 'Falha ao criar baseline'
            ], 500);
        }
    }
    
    /**
     * Obtém lista de baselines disponíveis
     * 
     * @return array Resposta em formato JSON
     */
    public function getBaselines() {
        // Verificar se o usuário tem permissão administrativa
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Acesso negado'], 403);
        }
        
        try {
            $sql = "SELECT b.id, b.name, b.monitor_id, b.created_at, b.notes, 
                           m.start_time, m.status
                    FROM performance_baselines b
                    JOIN performance_monitors m ON b.monitor_id = m.id
                    WHERE b.is_active = TRUE
                    ORDER BY b.created_at DESC";
            
            $baselines = $this->model->db()->select($sql);
            
            return $this->jsonResponse($baselines);
        } catch (Exception $e) {
            error_log("Erro ao obter baselines: " . $e->getMessage());
            return $this->jsonResponse([
                'error' => 'Falha ao obter baselines'
            ], 500);
        }
    }
    
    /**
     * Calcula a duração entre duas datas em formato legível
     * 
     * @param string $startTime Data/hora de início
     * @param string $endTime Data/hora de fim
     * @return string Duração formatada
     */
    private function calculateDuration($startTime, $endTime) {
        $start = new DateTime($startTime);
        $end = new DateTime($endTime);
        $interval = $start->diff($end);
        
        if ($interval->h > 0) {
            return $interval->format('%h horas, %i minutos, %s segundos');
        } elseif ($interval->i > 0) {
            return $interval->format('%i minutos, %s segundos');
        } else {
            return $interval->format('%s segundos');
        }
    }
    
    /**
     * Analisa métricas coletadas para gerar estatísticas
     * 
     * @param array $metrics Array de métricas coletadas
     * @return array Análise estatística das métricas
     */
    private function analyzeMetrics($metrics) {
        $analysis = [];
        
        foreach ($metrics as $metricName => $values) {
            if (empty($values)) {
                continue;
            }
            
            $numericValues = [];
            foreach ($values as $value) {
                if (isset($value['value']) && is_numeric($value['value'])) {
                    $numericValues[] = (float)$value['value'];
                }
            }
            
            if (empty($numericValues)) {
                continue;
            }
            
            // Calcular estatísticas
            $count = count($numericValues);
            $min = min($numericValues);
            $max = max($numericValues);
            $avg = array_sum($numericValues) / $count;
            
            // Calcular desvio padrão
            $variance = 0;
            foreach ($numericValues as $value) {
                $variance += pow($value - $avg, 2);
            }
            $stdDev = sqrt($variance / $count);
            
            // Determinar tendência (últimos 5 valores)
            $trend = 'stable';
            if ($count >= 5) {
                $recent = array_slice($numericValues, -5);
                $firstHalf = array_slice($recent, 0, 2);
                $secondHalf = array_slice($recent, -2);
                $firstAvg = array_sum($firstHalf) / count($firstHalf);
                $secondAvg = array_sum($secondHalf) / count($secondHalf);
                
                if ($secondAvg < $firstAvg * 0.95) {
                    $trend = 'improving';
                } elseif ($secondAvg > $firstAvg * 1.05) {
                    $trend = 'worsening';
                }
            }
            
            // Preparar análise
            $analysis[$metricName] = [
                'count' => $count,
                'min' => round($min, 2),
                'max' => round($max, 2),
                'avg' => round($avg, 2),
                'std_dev' => round($stdDev, 2),
                'trend' => $trend,
                'first_value' => $numericValues[0],
                'last_value' => $numericValues[$count - 1]
            ];
        }
        
        return $analysis;
    }
    
    /**
     * Gera recomendações baseadas nos dados do monitoramento
     * 
     * @param array $monitor Dados do monitor
     * @return array Lista de recomendações
     */
    private function generateRecommendations($monitor) {
        $recommendations = [];
        
        // Verificar métricas coletadas
        if (!isset($monitor['results']['metrics']) || empty($monitor['results']['metrics'])) {
            $recommendations[] = 'Não há métricas suficientes para gerar recomendações detalhadas.';
            return $recommendations;
        }
        
        $metrics = $monitor['results']['metrics'];
        $analysis = $this->analyzeMetrics($metrics);
        
        // Verificar tempo de carregamento
        if (isset($analysis['page_load_time'])) {
            $loadTimeAvg = $analysis['page_load_time']['avg'];
            
            if ($loadTimeAvg > 3000) {
                $recommendations[] = 'O tempo médio de carregamento de página é muito alto (> 3s). Considere otimizar recursos ou implementar melhorias de caching.';
            } elseif ($loadTimeAvg > 2000) {
                $recommendations[] = 'O tempo médio de carregamento de página está acima do recomendado (> 2s). Revise o carregamento de recursos externos.';
            }
            
            if (isset($analysis['page_load_time']['trend']) && $analysis['page_load_time']['trend'] === 'worsening') {
                $recommendations[] = 'O tempo de carregamento está piorando ao longo do teste. Verifique possíveis gargalos progressivos.';
            }
        }
        
        // Verificar TTFB
        if (isset($analysis['ttfb'])) {
            $ttfbAvg = $analysis['ttfb']['avg'];
            
            if ($ttfbAvg > 300) {
                $recommendations[] = 'O tempo até o primeiro byte (TTFB) é muito alto (> 300ms). Verifique a performance do servidor ou considere otimizações de backend.';
            } elseif ($ttfbAvg > 200) {
                $recommendations[] = 'O tempo até o primeiro byte (TTFB) está acima do ideal (> 200ms). Considere ajustes no servidor.';
            }
        }
        
        // Verificar LCP
        if (isset($analysis['lcp'])) {
            $lcpAvg = $analysis['lcp']['avg'];
            
            if ($lcpAvg > 2500) {
                $recommendations[] = 'O Largest Contentful Paint (LCP) está acima do recomendado (> 2.5s). Otimize o carregamento do conteúdo principal.';
            }
        }
        
        // Verificar CLS
        if (isset($analysis['cls'])) {
            $clsAvg = $analysis['cls']['avg'];
            
            if ($clsAvg > 0.1) {
                $recommendations[] = 'O Cumulative Layout Shift (CLS) está acima do recomendado (> 0.1). Verifique elementos que causam mudanças no layout durante o carregamento.';
            }
        }
        
        // Verificar tempo de consulta ao banco
        if (isset($analysis['query_time'])) {
            $queryTimeAvg = $analysis['query_time']['avg'];
            
            if ($queryTimeAvg > 200) {
                $recommendations[] = 'O tempo médio de consulta ao banco de dados é alto (> 200ms). Considere otimizar consultas ou adicionar índices apropriados.';
            }
        }
        
        // Verificar contagem de recursos
        if (isset($analysis['resource_count'])) {
            $resourceCountAvg = $analysis['resource_count']['avg'];
            
            if ($resourceCountAvg > 50) {
                $recommendations[] = 'O número de recursos carregados é alto (> 50). Considere combinar recursos ou utilizar carregamento assíncrono.';
            }
        }
        
        // Verificar uso de memória
        if (isset($analysis['memory_usage'])) {
            $memoryUsageAvg = $analysis['memory_usage']['avg'];
            
            if ($memoryUsageAvg > 100) { // assumindo MB
                $recommendations[] = 'O uso médio de memória é alto (> 100MB). Verifique possíveis vazamentos de memória ou otimize o consumo de recursos.';
            }
        }
        
        // Verificar tempo de resposta da API
        if (isset($analysis['api_response_time'])) {
            $apiResponseTimeAvg = $analysis['api_response_time']['avg'];
            
            if ($apiResponseTimeAvg > 300) {
                $recommendations[] = 'O tempo médio de resposta da API é alto (> 300ms). Considere otimizações no processamento de requisições.';
            }
        }
        
        // Verificar alertas
        if (isset($monitor['alerts']) && !empty($monitor['alerts'])) {
            $alertCount = count($monitor['alerts']);
            $recommendations[] = "Foram detectados {$alertCount} alertas durante o monitoramento. Verifique as métricas que ultrapassaram os limiares configurados.";
        }
        
        // Recomendações gerais se nenhuma específica foi gerada
        if (empty($recommendations)) {
            $recommendations[] = 'Todas as métricas monitoradas estão dentro dos limites aceitáveis. Continue monitorando regularmente.';
        }
        
        return $recommendations;
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