<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Helper para funções de monitoramento em ambiente de produção
 * Fornece métodos para coleta e análise de métricas de desempenho em ambiente de produção
 * com foco em impacto mínimo para os usuários.
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

class ProductionMonitoringHelper {
    // Configurações padrão
    private static $samplingRate = 0.05; // 5% dos usuários
    private static $enabled = false;
    private static $initialized = false;
    private static $logPath = '';
    private static $metricBuffer = [];
    private static $bufferLimit = 10;
    
    /**
     * Inicializa o helper
     * 
     * @param array $config Configurações opcionais
     * @return void
     */
    public static function init($config = []) {
        if (self::$initialized) {
            return;
        }
        
        self::$initialized = true;
        
        // Aplicar configurações
        if (isset($config['sampling_rate'])) {
            self::$samplingRate = floatval($config['sampling_rate']);
        }
        
        if (isset($config['enabled'])) {
            self::$enabled = (bool)$config['enabled'];
        } else {
            // Habilitar automaticamente apenas em ambiente de produção
            self::$enabled = defined('ENVIRONMENT') && ENVIRONMENT === 'production';
        }
        
        if (isset($config['log_path'])) {
            self::$logPath = $config['log_path'];
        } else {
            self::$logPath = ROOT_PATH . '/logs/performance_monitoring';
        }
        
        if (isset($config['buffer_limit'])) {
            self::$bufferLimit = intval($config['buffer_limit']);
        }
        
        // Criar diretório de logs se não existir
        if (!file_exists(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
        
        // Iniciar monitoramento se estiver habilitado
        if (self::$enabled) {
            self::startMonitoring();
        }
    }
    
    /**
     * Verifica se o usuário atual deve ser incluído na amostragem
     * 
     * @return bool Verdadeiro se o usuário faz parte da amostra
     */
    public static function shouldMonitorUser() {
        if (!self::$enabled) {
            return false;
        }
        
        // Usar uma cookie persistente para manter consistência na amostragem
        $cookieName = 'taverna_perf_sample';
        
        if (isset($_COOKIE[$cookieName])) {
            return $_COOKIE[$cookieName] === '1';
        }
        
        // Determinar se este usuário fará parte da amostra
        $inSample = (mt_rand(1, 100) / 100) <= self::$samplingRate;
        
        // Definir cookie com expiração em 30 dias
        setcookie(
            $cookieName,
            $inSample ? '1' : '0',
            time() + (86400 * 30),
            '/',
            '',
            isset($_SERVER['HTTPS']),
            true
        );
        
        return $inSample;
    }
    
    /**
     * Inicia o monitoramento para o usuário atual
     * 
     * @return void
     */
    public static function startMonitoring() {
        if (!self::$enabled || !self::shouldMonitorUser()) {
            return;
        }
        
        // Registrar tempo de início
        $_SERVER['TAVERNA_PERF_START_TIME'] = microtime(true);
        
        // Registrar uso inicial de memória
        $_SERVER['TAVERNA_PERF_START_MEMORY'] = memory_get_usage();
        
        // Registrar timestamp para cálculos
        $_SERVER['TAVERNA_PERF_TIMESTAMP'] = date('Y-m-d H:i:s');
        
        // Adicionar script para coleta no client-side se a função existe no framework
        if (function_exists('add_action')) {
            add_action('wp_footer', [__CLASS__, 'addClientMonitoringScript'], 999);
        } else if (function_exists('register_shutdown_function')) {
            // Como alternativa, use register_shutdown_function para páginas que não usam hooks
            register_shutdown_function([__CLASS__, 'addClientMonitoringScript']);
        }
    }
    
    /**
     * Adiciona script para monitoramento no cliente
     * 
     * @return void
     */
    public static function addClientMonitoringScript() {
        echo '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                if (window.PerformanceMetrics && typeof window.PerformanceMetrics.collectBasicMetrics === "function") {
                    // Coletar métricas básicas após carregamento completo
                    window.PerformanceMetrics.collectBasicMetrics({
                        sampleId: "' . self::generateSampleId() . '",
                        sendUrl: "' . (defined('BASE_URL') ? BASE_URL : '/') . 'api/performance/collect",
                        pageInfo: {
                            path: window.location.pathname,
                            query: window.location.search,
                            referrer: document.referrer,
                            userAgent: navigator.userAgent,
                            timestamp: new Date().toISOString()
                        }
                    });
                }
            });
        </script>';
    }
    
    /**
     * Finaliza o monitoramento e registra métricas
     * 
     * @param array $additionalData Dados adicionais para incluir no registro
     * @return void
     */
    public static function endMonitoring($additionalData = []) {
        if (!self::$enabled || !isset($_SERVER['TAVERNA_PERF_START_TIME'])) {
            return;
        }
        
        // Calcular métricas do lado do servidor
        $endTime = microtime(true);
        $executionTime = ($endTime - $_SERVER['TAVERNA_PERF_START_TIME']) * 1000; // em ms
        
        $endMemory = memory_get_usage();
        $memoryUsed = $endMemory - $_SERVER['TAVERNA_PERF_START_MEMORY'];
        
        // Preparar dados para registro
        $data = [
            'timestamp' => $_SERVER['TAVERNA_PERF_TIMESTAMP'],
            'sample_id' => self::generateSampleId(),
            'url' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'ip' => self::getAnonymizedIp(),
            'execution_time_ms' => round($executionTime, 2),
            'memory_used_bytes' => $memoryUsed,
            'additional' => $additionalData
        ];
        
        // Adicionar ao buffer e salvar se atingir o limite
        self::$metricBuffer[] = $data;
        
        if (count(self::$metricBuffer) >= self::$bufferLimit) {
            self::flushMetricsBuffer();
        }
    }
    
    /**
     * Salva o buffer de métricas em arquivo
     * 
     * @return void
     */
    public static function flushMetricsBuffer() {
        if (empty(self::$metricBuffer)) {
            return;
        }
        
        $logFile = self::$logPath . '/metrics_' . date('Y-m-d') . '.json';
        
        // Verificar se arquivo existe para anexar
        $existingData = [];
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            if (!empty($content)) {
                $existingData = json_decode($content, true) ?: [];
            }
        }
        
        // Combinar dados existentes com novos
        $allData = array_merge($existingData, self::$metricBuffer);
        
        // Salvar arquivo
        file_put_contents($logFile, json_encode($allData, JSON_PRETTY_PRINT));
        
        // Limpar buffer
        self::$metricBuffer = [];
    }
    
    /**
     * Gera um ID único para amostra
     * 
     * @return string ID da amostra
     */
    private static function generateSampleId() {
        if (isset($_SERVER['TAVERNA_PERF_SAMPLE_ID'])) {
            return $_SERVER['TAVERNA_PERF_SAMPLE_ID'];
        }
        
        $sampleId = uniqid('perf_', true);
        $_SERVER['TAVERNA_PERF_SAMPLE_ID'] = $sampleId;
        
        return $sampleId;
    }
    
    /**
     * Obtém o IP anonimizado para privacidade do usuário
     * 
     * @return string IP parcialmente anonimizado
     */
    private static function getAnonymizedIp() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        
        if (empty($ip)) {
            return '';
        }
        
        // Se for IPv4, manter apenas os três primeiros octetos
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
        }
        
        // Se for IPv6, manter apenas os quatro primeiros grupos
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            return $parts[0] . ':' . $parts[1] . ':' . $parts[2] . ':' . $parts[3] . '::';
        }
        
        return $ip;
    }
    
    /**
     * Registra uma métrica específica durante o ciclo de vida da requisição
     * 
     * @param string $metricName Nome da métrica
     * @param mixed $value Valor da métrica
     * @param string $category Categoria da métrica
     * @return void
     */
    public static function recordMetric($metricName, $value, $category = 'custom') {
        if (!self::$enabled || !self::shouldMonitorUser()) {
            return;
        }
        
        $metric = [
            'timestamp' => date('Y-m-d H:i:s'),
            'sample_id' => self::generateSampleId(),
            'name' => $metricName,
            'value' => $value,
            'category' => $category,
            'url' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''
        ];
        
        self::$metricBuffer[] = $metric;
        
        if (count(self::$metricBuffer) >= self::$bufferLimit) {
            self::flushMetricsBuffer();
        }
    }
    
    /**
     * Analisa dados coletados em um período específico
     * 
     * @param string $startDate Data inicial (formato Y-m-d)
     * @param string $endDate Data final (formato Y-m-d)
     * @return array Resumo das métricas analisadas
     */
    public static function analyzeData($startDate, $endDate) {
        $metrics = self::loadMetricsForDateRange($startDate, $endDate);
        
        if (empty($metrics)) {
            return [
                'error' => 'Nenhum dado disponível para o período especificado'
            ];
        }
        
        // Agrupar métricas por URL
        $urlGroups = [];
        foreach ($metrics as $metric) {
            if (!isset($metric['url']) || empty($metric['url'])) {
                continue;
            }
            
            $url = $metric['url'];
            
            if (!isset($urlGroups[$url])) {
                $urlGroups[$url] = [];
            }
            
            $urlGroups[$url][] = $metric;
        }
        
        // Calcular estatísticas por URL
        $results = [];
        foreach ($urlGroups as $url => $urlMetrics) {
            $executionTimes = array_map(function($m) {
                return isset($m['execution_time_ms']) ? $m['execution_time_ms'] : null;
            }, array_filter($urlMetrics, function($m) {
                return isset($m['execution_time_ms']);
            }));
            
            $memoryUsages = array_map(function($m) {
                return isset($m['memory_used_bytes']) ? $m['memory_used_bytes'] : null;
            }, array_filter($urlMetrics, function($m) {
                return isset($m['memory_used_bytes']);
            }));
            
            if (empty($executionTimes)) {
                continue;
            }
            
            $results[$url] = [
                'url' => $url,
                'sample_count' => count($urlMetrics),
                'avg_execution_time_ms' => array_sum($executionTimes) / count($executionTimes),
                'min_execution_time_ms' => min($executionTimes),
                'max_execution_time_ms' => max($executionTimes),
                'avg_memory_used_bytes' => !empty($memoryUsages) ? array_sum($memoryUsages) / count($memoryUsages) : 0
            ];
        }
        
        // Ordenar por tempo médio de execução (decrescente)
        usort($results, function($a, $b) {
            return $b['avg_execution_time_ms'] <=> $a['avg_execution_time_ms'];
        });
        
        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'total_samples' => count($metrics),
            'urls_analyzed' => count($results),
            'results' => $results
        ];
    }
    
    /**
     * Carrega métricas de um intervalo de datas
     * 
     * @param string $startDate Data inicial (formato Y-m-d)
     * @param string $endDate Data final (formato Y-m-d)
     * @return array Métricas no intervalo de datas
     */
    private static function loadMetricsForDateRange($startDate, $endDate) {
        $metrics = [];
        
        $currentDate = new DateTime($startDate);
        $lastDate = new DateTime($endDate);
        
        while ($currentDate <= $lastDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $logFile = self::$logPath . '/metrics_' . $dateStr . '.json';
            
            if (file_exists($logFile)) {
                $content = file_get_contents($logFile);
                if (!empty($content)) {
                    $dailyMetrics = json_decode($content, true) ?: [];
                    $metrics = array_merge($metrics, $dailyMetrics);
                }
            }
            
            $currentDate->modify('+1 day');
        }
        
        return $metrics;
    }
    
    /**
     * Obtém recomendações com base nas métricas analisadas
     * 
     * @param array $analysisData Dados de análise de métricas
     * @return array Lista de recomendações
     */
    public static function getRecommendations($analysisData) {
        if (empty($analysisData) || !isset($analysisData['results']) || empty($analysisData['results'])) {
            return [
                'error' => 'Dados insuficientes para gerar recomendações'
            ];
        }
        
        $recommendations = [];
        $slowUrls = [];
        $highMemoryUrls = [];
        
        // Identificar URLs lentas ou com alto uso de memória
        foreach ($analysisData['results'] as $result) {
            if ($result['avg_execution_time_ms'] > 500) {
                $slowUrls[] = $result;
            }
            
            if ($result['avg_memory_used_bytes'] > 5 * 1024 * 1024) { // 5 MB
                $highMemoryUrls[] = $result;
            }
        }
        
        // Recomendações para URLs lentas
        if (!empty($slowUrls)) {
            $recommendations[] = 'As seguintes URLs apresentam tempos médios de execução acima de 500ms e devem ser otimizadas:';
            foreach ($slowUrls as $url) {
                $recommendations[] = "- {$url['url']}: " . round($url['avg_execution_time_ms'], 2) . "ms (média de {$url['sample_count']} amostras)";
            }
            
            $recommendations[] = 'Recomendações para melhorar o desempenho:';
            $recommendations[] = '- Analise as consultas SQL nestas páginas e verifique se podem ser otimizadas';
            $recommendations[] = '- Considere implementar mais cache para reduzir processamento no servidor';
            $recommendations[] = '- Verifique se há chamadas a APIs externas que podem estar causando atrasos';
        }
        
        // Recomendações para alto uso de memória
        if (!empty($highMemoryUrls)) {
            $recommendations[] = 'As seguintes URLs apresentam alto uso de memória (acima de 5 MB):';
            foreach ($highMemoryUrls as $url) {
                $recommendations[] = "- {$url['url']}: " . round($url['avg_memory_used_bytes'] / (1024 * 1024), 2) . "MB (média de {$url['sample_count']} amostras)";
            }
            
            $recommendations[] = 'Recomendações para reduzir uso de memória:';
            $recommendations[] = '- Verifique se há vazamentos de memória em código PHP';
            $recommendations[] = '- Otimize consultas SQL para retornar apenas os dados necessários';
            $recommendations[] = '- Considere paginar resultados de grandes conjuntos de dados';
        }
        
        // Recomendações gerais
        if (empty($recommendations)) {
            $recommendations[] = 'Todas as URLs analisadas estão dentro dos parâmetros de desempenho esperados.';
            $recommendations[] = 'Continue monitorando regularmente e compare os resultados ao longo do tempo.';
        } else {
            $recommendations[] = 'Recomendações gerais para todas as páginas:';
            $recommendations[] = '- Continue monitorando regularmente para identificar tendências de desempenho';
            $recommendations[] = '- Compare métricas após implementar otimizações para verificar eficácia';
            $recommendations[] = '- Ajuste a taxa de amostragem se necessário para obter mais dados';
        }
        
        return $recommendations;
    }
}
