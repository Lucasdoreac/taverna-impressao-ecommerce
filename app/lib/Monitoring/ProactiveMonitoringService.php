<?php
/**
 * ProactiveMonitoringService - Serviço de monitoramento proativo de performance
 * 
 * Implementa verificação contínua de métricas de sistema, detecção avançada de anomalias
 * e previsão de tendências para antecipar problemas potenciais antes que afetem usuários.
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Lib\Monitoring
 * @version    1.0.0
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Security/InputValidationTrait.php';
require_once __DIR__ . '/../Notification/NotificationManager.php';
require_once __DIR__ . '/../Notification/NotificationThresholds.php';

class ProactiveMonitoringService {
    use InputValidationTrait;
    
    /**
     * Instância singleton
     * 
     * @var ProactiveMonitoringService
     */
    private static $instance;
    
    /**
     * Conexão com o banco de dados
     * 
     * @var Database
     */
    private $db;
    
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
     * Janela de tempo para análise de tendências (em segundos)
     * 
     * @var int
     */
    private $trendAnalysisWindow = 86400; // 24 horas
    
    /**
     * Componentes críticos a serem monitorados com maior frequência
     * 
     * @var array
     */
    private $criticalComponents = [
        'HttpServer',
        'Database',
        'FileUpload',
        'PrintQueue',
        'ReportGenerator',
        '3DViewer'
    ];
    
    /**
     * Último timestamp de verificação para cada métrica
     * 
     * @var array
     */
    private $lastCheckTimestamps = [];
    
    /**
     * Construtor privado (padrão singleton)
     */
    private function __construct() {
        $this->db = Database::getInstance();
        $this->notificationManager = NotificationManager::getInstance();
        $this->thresholds = NotificationThresholds::getInstance();
        $this->initializeLastCheckTimestamps();
    }
    
    /**
     * Obtém a instância do ProactiveMonitoringService
     * 
     * @return ProactiveMonitoringService
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Inicializa o registro de timestamps da última verificação para cada métrica
     * 
     * @return void
     */
    private function initializeLastCheckTimestamps() {
        try {
            $sql = "SELECT metric_name FROM performance_metrics GROUP BY metric_name";
            $metrics = $this->db->fetchAll($sql);
            
            if ($metrics) {
                foreach ($metrics as $metric) {
                    $this->lastCheckTimestamps[$metric['metric_name']] = time() - 3600; // 1 hora atrás por padrão
                }
            }
        } catch (Exception $e) {
            error_log('Erro ao inicializar timestamps de verificação: ' . $e->getMessage());
        }
    }
    
    /**
     * Executa o monitoramento proativo de todas as métricas e componentes
     * 
     * @return bool Sucesso da operação
     */
    public function runProactiveMonitoring() {
        try {
            // 1. Verificar métricas atuais contra thresholds
            $this->checkCurrentMetricsAgainstThresholds();
            
            // 2. Analisar tendências para detecção precoce
            $this->analyzeTrends();
            
            // 3. Detectar anomalias estatísticas
            $this->detectAnomalies();
            
            // 4. Realizar previsões de carga
            $this->predictLoadPatterns();
            
            // 5. Realizar auto-ajuste de thresholds se necessário
            $this->autoAdjustThresholds();
            
            // 6. Registrar execução bem-sucedida
            $this->logMonitoringRun();
            
            return true;
        } catch (Exception $e) {
            error_log('Erro ao executar monitoramento proativo: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica as métricas atuais contra seus thresholds
     * 
     * @return void
     */
    private function checkCurrentMetricsAgainstThresholds() {
        try {
            // Obter thresholds ativos
            $activeThresholds = $this->thresholds->getAllThresholds();
            
            if (empty($activeThresholds)) {
                error_log('Nenhum threshold ativo encontrado para verificação');
                return;
            }
            
            // Para cada threshold, verificar métricas recentes
            foreach ($activeThresholds as $metric => $threshold) {
                // Obter a métrica mais recente
                $sql = "SELECT component, metric_value, timestamp 
                        FROM performance_metrics 
                        WHERE metric_name = :metric 
                        AND timestamp > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
                        ORDER BY timestamp DESC";
                
                $params = [':metric' => $metric];
                $recentMetrics = $this->db->fetchAll($sql, $params);
                
                if (!$recentMetrics) {
                    continue; // Nenhuma métrica recente para este threshold
                }
                
                // Agrupar por componente para verificar cada um individualmente
                $metricsByComponent = [];
                foreach ($recentMetrics as $metricData) {
                    $component = $metricData['component'];
                    if (!isset($metricsByComponent[$component])) {
                        $metricsByComponent[$component] = [];
                    }
                    $metricsByComponent[$component][] = $metricData;
                }
                
                // Verificar cada componente
                foreach ($metricsByComponent as $component => $metrics) {
                    // Calcular valor médio para este componente
                    $values = array_column($metrics, 'metric_value');
                    $avgValue = array_sum($values) / count($values);
                    
                    // Verificar se excede o threshold
                    if ($this->thresholds->isThresholdExceeded($metric, $avgValue)) {
                        // Verificar se este componente não está silenciado
                        if (!$this->notificationManager->isMetricSilenced($metric, $component)) {
                            // Determinar severidade
                            $severity = $this->thresholds->determineSeverity($metric, $avgValue);
                            
                            // Criar alerta de performance
                            $this->notificationManager->createPerformanceAlert(
                                $metric,
                                $avgValue,
                                $component,
                                ['admin'] // Apenas admins recebem estes alertas inicialmente
                            );
                            
                            // Registrar detecção
                            error_log("Alerta de performance: {$component} - {$metric} = {$avgValue} (severidade: {$severity})");
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Erro ao verificar métricas contra thresholds: ' . $e->getMessage());
        }
    }
    
    /**
     * Analisa tendências para detecção precoce de problemas
     * 
     * @return void
     */
    private function analyzeTrends() {
        try {
            // Lista de métricas a serem analisadas para tendências
            $metricsToAnalyze = [
                'response_time',
                'memory_usage',
                'query_time',
                'cpu_usage'
            ];
            
            foreach ($metricsToAnalyze as $metric) {
                // Obter dados históricos para análise de tendência
                $sql = "SELECT component, metric_value, timestamp 
                        FROM performance_metrics 
                        WHERE metric_name = :metric 
                        AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        ORDER BY timestamp ASC";
                
                $params = [':metric' => $metric];
                $historicalData = $this->db->fetchAll($sql, $params);
                
                if (count($historicalData) < 10) {
                    // Dados insuficientes para análise de tendências
                    continue;
                }
                
                // Agrupar por componente
                $componentData = [];
                foreach ($historicalData as $data) {
                    $component = $data['component'];
                    if (!isset($componentData[$component])) {
                        $componentData[$component] = [];
                    }
                    $componentData[$component][] = $data;
                }
                
                // Analisar tendência para cada componente
                foreach ($componentData as $component => $dataPoints) {
                    if (count($dataPoints) < 10) {
                        continue; // Pular componentes com poucos pontos de dados
                    }
                    
                    // Calcular coeficiente de tendência linear
                    $trend = $this->calculateLinearTrend($dataPoints);
                    
                    // Threshold para considerar uma tendência significativa (ajustar conforme necessário)
                    $trendThreshold = $this->getTrendThresholdForMetric($metric);
                    
                    // Se a tendência for significativa e positiva (piorando)
                    if ($trend > $trendThreshold) {
                        // Obter threshold atual para esta métrica
                        $metricThreshold = $this->thresholds->getThresholdForMetric($metric);
                        
                        if ($metricThreshold) {
                            // Calcular quando o threshold será atingido se a tendência continuar
                            $lastValue = end($dataPoints)['metric_value'];
                            $timeToThreshold = $this->calculateTimeToThreshold(
                                $lastValue,
                                $metricThreshold['value'],
                                $trend
                            );
                            
                            // Se o threshold será atingido nas próximas 6 horas
                            if ($timeToThreshold !== null && $timeToThreshold <= 6) {
                                // Criar alerta de tendência negativa
                                $context = [
                                    'metric' => $metric,
                                    'component' => $component,
                                    'current_value' => $lastValue,
                                    'threshold' => $metricThreshold['value'],
                                    'trend_coefficient' => $trend,
                                    'estimated_hours_to_threshold' => $timeToThreshold
                                ];
                                
                                // Notificar apenas administradores sobre tendências
                                $adminUsers = $this->getAdminUserIds();
                                
                                foreach ($adminUsers as $userId) {
                                    $this->notificationManager->createNotification(
                                        $userId,
                                        "Tendência de degradação: {$component}",
                                        "A métrica '{$metric}' está em tendência de piora e poderá exceder o limite em aproximadamente {$timeToThreshold} horas.",
                                        'warning',
                                        $context,
                                        ['database', 'dashboard']
                                    );
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Erro ao analisar tendências: ' . $e->getMessage());
        }
    }
    
    /**
     * Calcula o coeficiente de tendência linear para um conjunto de pontos de dados
     * 
     * @param array $dataPoints Array de pontos de dados com timestamp e metric_value
     * @return float Coeficiente de tendência (positivo = piorando, negativo = melhorando)
     */
    private function calculateLinearTrend($dataPoints) {
        // Extrair timestamps e valores
        $timestamps = [];
        $values = [];
        
        foreach ($dataPoints as $point) {
            $timestamps[] = strtotime($point['timestamp']);
            $values[] = $point['metric_value'];
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
     * Calcula quanto tempo levará até um valor atingir um threshold com base na tendência atual
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
        
        // Se a tendência for positiva (piorando) e o valor atual for menor que o threshold
        if ($trendCoefficient > 0 && $difference > 0) {
            return $difference / $trendCoefficient;
        }
        
        // Se a tendência for negativa (melhorando) e o valor atual for maior que o threshold
        if ($trendCoefficient < 0 && $difference < 0) {
            return abs($difference) / abs($trendCoefficient);
        }
        
        // Nos outros casos, ou já ultrapassou o threshold ou nunca vai atingir
        return null;
    }
    
    /**
     * Obtém o threshold para considerar uma tendência significativa para uma métrica específica
     * 
     * @param string $metric Nome da métrica
     * @return float Valor do threshold para tendência
     */
    private function getTrendThresholdForMetric($metric) {
        $defaults = [
            'response_time' => 0.05,   // 0.05 segundos por hora
            'memory_usage' => 2.0,     // 2 MB por hora
            'query_time' => 0.02,      // 0.02 segundos por hora
            'cpu_usage' => 1.5,        // 1.5% por hora
            'default' => 0.1           // Valor padrão para outras métricas
        ];
        
        return $defaults[$metric] ?? $defaults['default'];
    }
    
    /**
     * Detecta anomalias estatísticas nas métricas de performance
     * 
     * @return void
     */
    private function detectAnomalies() {
        try {
            // Obter componentes críticos para análise de anomalias
            foreach ($this->criticalComponents as $component) {
                // Obter todas as métricas para este componente nas últimas 3 horas
                $sql = "SELECT metric_name, metric_value, timestamp 
                        FROM performance_metrics 
                        WHERE component = :component 
                        AND timestamp > DATE_SUB(NOW(), INTERVAL 3 HOUR)
                        ORDER BY timestamp ASC";
                
                $params = [':component' => $component];
                $metrics = $this->db->fetchAll($sql, $params);
                
                if (!$metrics || count($metrics) < 20) {
                    // Dados insuficientes para detecção de anomalias
                    continue;
                }
                
                // Agrupar por tipo de métrica
                $metricGroups = [];
                foreach ($metrics as $metric) {
                    $name = $metric['metric_name'];
                    if (!isset($metricGroups[$name])) {
                        $metricGroups[$name] = [];
                    }
                    $metricGroups[$name][] = $metric;
                }
                
                // Analisar cada grupo de métricas
                foreach ($metricGroups as $metricName => $metricData) {
                    if (count($metricData) < 20) {
                        continue; // Pular métricas com poucos pontos de dados
                    }
                    
                    // Extrair apenas os valores para análise estatística
                    $values = array_column($metricData, 'metric_value');
                    
                    // Calcular média e desvio padrão
                    $mean = array_sum($values) / count($values);
                    $variance = 0;
                    
                    foreach ($values as $value) {
                        $variance += pow($value - $mean, 2);
                    }
                    
                    $variance /= count($values);
                    $stdDev = sqrt($variance);
                    
                    // Verificar os últimos 5 pontos por anomalias
                    $recentPoints = array_slice($values, -5);
                    $anomalies = [];
                    
                    foreach ($recentPoints as $index => $value) {
                        // Calcular Z-score (número de desvios padrão da média)
                        $zScore = ($stdDev > 0) ? abs($value - $mean) / $stdDev : 0;
                        
                        // Considerar anomalia se Z-score > 3 (99.7% de intervalo de confiança)
                        if ($zScore > 3) {
                            $anomalies[] = [
                                'value' => $value,
                                'z_score' => $zScore,
                                'index' => count($values) - 5 + $index
                            ];
                        }
                    }
                    
                    // Se houver anomalias, gerar alerta
                    if (!empty($anomalies)) {
                        // Determinar a direção da anomalia (alto ou baixo)
                        $lastAnomaly = end($anomalies);
                        $lastValue = $values[$lastAnomaly['index']];
                        $direction = ($lastValue > $mean) ? 'alta' : 'baixa';
                        
                        // Construir mensagem e contexto
                        $title = "Anomalia detectada: {$component} - {$metricName}";
                        $message = "Valores anômalos de {$direction} detectados para a métrica '{$metricName}' no componente '{$component}'. ";
                        $message .= "Último valor: {$lastValue}, média normal: {$mean}.";
                        
                        $context = [
                            'metric' => $metricName,
                            'component' => $component,
                            'last_value' => $lastValue,
                            'mean' => $mean,
                            'std_dev' => $stdDev,
                            'z_score' => $lastAnomaly['z_score'],
                            'direction' => $direction,
                            'anomaly_count' => count($anomalies)
                        ];
                        
                        // Notificar apenas administradores sobre anomalias
                        $adminUsers = $this->getAdminUserIds();
                        
                        foreach ($adminUsers as $userId) {
                            $this->notificationManager->createNotification(
                                $userId,
                                $title,
                                $message,
                                'warning',
                                $context,
                                ['database', 'dashboard']
                            );
                        }
                        
                        // Registrar a detecção de anomalia
                        error_log("Anomalia detectada: {$component}/{$metricName} - Z-score: {$lastAnomaly['z_score']}");
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Erro ao detectar anomalias: ' . $e->getMessage());
        }
    }
    
    /**
     * Prediz padrões de carga com base em dados históricos
     * 
     * @return void
     */
    private function predictLoadPatterns() {
        try {
            // Obter o dia da semana atual (0 = Domingo, 6 = Sábado)
            $currentDayOfWeek = date('w');
            $currentHour = date('G');
            
            // Métricas relacionadas a carga do sistema
            $loadMetrics = ['response_time', 'memory_usage', 'query_count', 'cpu_usage'];
            
            foreach ($loadMetrics as $metric) {
                // Obter dados históricos para este dia da semana e hora aproximada
                $sql = "SELECT AVG(metric_value) as avg_value, component
                        FROM performance_metrics
                        WHERE metric_name = :metric
                        AND DAYOFWEEK(timestamp) = :day_of_week
                        AND HOUR(timestamp) BETWEEN :hour_min AND :hour_max
                        AND timestamp > DATE_SUB(NOW(), INTERVAL 4 WEEK)
                        GROUP BY component";
                
                $params = [
                    ':metric' => $metric,
                    ':day_of_week' => $currentDayOfWeek + 1, // MySQL usa 1-7 para dias da semana
                    ':hour_min' => max(0, $currentHour - 1),
                    ':hour_max' => min(23, $currentHour + 1)
                ];
                
                $historicalAverages = $this->db->fetchAll($sql, $params);
                
                if (!$historicalAverages) {
                    continue;
                }
                
                // Obter médias atuais para comparação (última hora)
                $sql = "SELECT AVG(metric_value) as avg_value, component
                        FROM performance_metrics
                        WHERE metric_name = :metric
                        AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                        GROUP BY component";
                
                $params = [':metric' => $metric];
                $currentAverages = $this->db->fetchAll($sql, $params);
                
                if (!$currentAverages) {
                    continue;
                }
                
                // Converter para formato mais fácil de comparar
                $historicalByComponent = [];
                foreach ($historicalAverages as $average) {
                    $historicalByComponent[$average['component']] = $average['avg_value'];
                }
                
                $currentByComponent = [];
                foreach ($currentAverages as $average) {
                    $currentByComponent[$average['component']] = $average['avg_value'];
                }
                
                // Comparar valores atuais com históricos para cada componente
                foreach ($currentByComponent as $component => $currentValue) {
                    if (!isset($historicalByComponent[$component])) {
                        continue;
                    }
                    
                    $historicalValue = $historicalByComponent[$component];
                    
                    // Calcular diferença percentual
                    $percentDifference = (($currentValue - $historicalValue) / $historicalValue) * 100;
                    
                    // Se o valor atual for significativamente maior que o histórico (ajustar conforme necessário)
                    if ($percentDifference > 30) {
                        // Isso pode indicar uma carga anormal para este período
                        $title = "Carga anormal detectada: {$component}";
                        $message = "A métrica '{$metric}' está {$percentDifference}% acima do histórico para este dia/hora. " .
                                  "Atual: {$currentValue}, Histórico: {$historicalValue}.";
                        
                        $context = [
                            'metric' => $metric,
                            'component' => $component,
                            'current_value' => $currentValue,
                            'historical_value' => $historicalValue,
                            'percent_difference' => $percentDifference,
                            'day_of_week' => $currentDayOfWeek,
                            'hour' => $currentHour
                        ];
                        
                        // Notificar apenas administradores
                        $adminUsers = $this->getAdminUserIds();
                        
                        foreach ($adminUsers as $userId) {
                            $this->notificationManager->createNotification(
                                $userId,
                                $title,
                                $message,
                                'warning',
                                $context,
                                ['database', 'dashboard']
                            );
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Erro ao prever padrões de carga: ' . $e->getMessage());
        }
    }
    
    /**
     * Realiza auto-ajuste dos thresholds com base em dados históricos
     * 
     * @return void
     */
    private function autoAdjustThresholds() {
        try {
            // Obter todos os thresholds ativos
            $activeThresholds = $this->thresholds->getAllThresholds();
            
            if (empty($activeThresholds)) {
                return;
            }
            
            // Métricas que devem ter ajuste adaptativo
            $adaptiveMetrics = [
                'response_time',
                'memory_usage',
                'query_time',
                'cpu_usage'
            ];
            
            foreach ($adaptiveMetrics as $metric) {
                if (!isset($activeThresholds[$metric])) {
                    continue;
                }
                
                // Verificar se é hora de ajustar este threshold
                // Ajustar no máximo uma vez por dia
                $lastAdjustment = $this->getLastThresholdAdjustment($metric);
                
                if ($lastAdjustment && (time() - $lastAdjustment) < 86400) {
                    continue;
                }
                
                // Auto-ajustar o threshold
                $success = $this->thresholds->autoAdjustThreshold(
                    $metric,
                    30, // 30 dias de dados históricos
                    2.0 // 2 desvios padrão
                );
                
                if ($success) {
                    // Registrar o ajuste
                    $this->logThresholdAdjustment($metric);
                    
                    // Obter o novo valor do threshold
                    $newThreshold = $this->thresholds->getThresholdForMetric($metric);
                    
                    // Notificar administradores sobre o ajuste
                    $title = "Threshold auto-ajustado: {$metric}";
                    $message = "O threshold para a métrica '{$metric}' foi auto-ajustado para {$newThreshold['value']} {$newThreshold['operator']} " .
                               "com base em análise de dados históricos.";
                    
                    $context = [
                        'metric' => $metric,
                        'new_threshold' => $newThreshold['value'],
                        'operator' => $newThreshold['operator'],
                        'adjustment_date' => date('Y-m-d H:i:s')
                    ];
                    
                    // Notificar apenas administradores
                    $adminUsers = $this->getAdminUserIds();
                    
                    foreach ($adminUsers as $userId) {
                        $this->notificationManager->createNotification(
                            $userId,
                            $title,
                            $message,
                            'info',
                            $context,
                            ['database']
                        );
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Erro ao auto-ajustar thresholds: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtém a data do último ajuste de threshold para uma métrica
     * 
     * @param string $metric Nome da métrica
     * @return int|null Timestamp do último ajuste ou null se nunca ajustado
     */
    private function getLastThresholdAdjustment($metric) {
        try {
            $sql = "SELECT MAX(timestamp) as last_adjustment 
                    FROM threshold_adjustments 
                    WHERE metric = :metric";
            
            $params = [':metric' => $metric];
            $result = $this->db->fetchSingle($sql, $params);
            
            if ($result && isset($result['last_adjustment'])) {
                return strtotime($result['last_adjustment']);
            }
            
            return null;
        } catch (Exception $e) {
            error_log('Erro ao obter último ajuste de threshold: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Registra um ajuste de threshold no log
     * 
     * @param string $metric Nome da métrica
     * @return bool Sucesso da operação
     */
    private function logThresholdAdjustment($metric) {
        try {
            $threshold = $this->thresholds->getThresholdForMetric($metric);
            
            if (!$threshold) {
                return false;
            }
            
            $sql = "INSERT INTO threshold_adjustments 
                    (metric, new_value, operator, adjustment_type, timestamp) 
                    VALUES 
                    (:metric, :new_value, :operator, 'auto', NOW())";
            
            $params = [
                ':metric' => $metric,
                ':new_value' => $threshold['value'],
                ':operator' => $threshold['operator']
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao registrar ajuste de threshold: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra a execução do monitoramento no log
     * 
     * @return bool Sucesso da operação
     */
    private function logMonitoringRun() {
        try {
            $sql = "INSERT INTO monitoring_runs (timestamp, status, metrics_checked) 
                    VALUES (NOW(), 'completed', :count)";
            
            $metricsCount = count($this->lastCheckTimestamps);
            $params = [':count' => $metricsCount];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao registrar execução do monitoramento: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém os IDs de todos os usuários administradores
     * 
     * @return array Lista de IDs de usuários
     */
    private function getAdminUserIds() {
        try {
            $sql = "SELECT id FROM users WHERE role = 'admin'";
            $admins = $this->db->fetchAll($sql);
            
            if (!$admins) {
                return [];
            }
            
            return array_column($admins, 'id');
        } catch (Exception $e) {
            error_log('Erro ao obter IDs de administradores: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Agenda o próximo ciclo de monitoramento proativo
     * 
     * @param int $delaySeconds Atraso em segundos para o próximo ciclo
     * @return bool Sucesso da operação
     */
    public function scheduleNextMonitoringCycle($delaySeconds = 300) {
        try {
            // Em um sistema real, isso poderia ser implementado usando um cron job,
            // um sistema de filas, ou um agendador de tarefas
            
            // Para esta implementação, apenas simulamos o agendamento
            $nextRun = time() + $delaySeconds;
            
            $sql = "INSERT INTO scheduled_tasks 
                    (task_name, scheduled_time, parameters) 
                    VALUES 
                    ('proactive_monitoring', FROM_UNIXTIME(:next_run), :params)";
            
            $params = [
                ':next_run' => $nextRun,
                ':params' => json_encode(['full_cycle' => true])
            ];
            
            return $this->db->execute($sql, $params) !== false;
        } catch (Exception $e) {
            error_log('Erro ao agendar próximo ciclo de monitoramento: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Executa monitoramento específico para um componente
     * 
     * @param string $component Nome do componente
     * @return bool Sucesso da operação
     */
    public function monitorComponent($component) {
        try {
            $component = $this->validateString($component, ['maxLength' => 255, 'required' => true]);
            
            // Obter últimas métricas para este componente
            $sql = "SELECT metric_name, metric_value, timestamp 
                    FROM performance_metrics 
                    WHERE component = :component 
                    AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                    ORDER BY timestamp DESC";
            
            $params = [':component' => $component];
            $metrics = $this->db->fetchAll($sql, $params);
            
            if (!$metrics) {
                error_log("Sem métricas recentes para o componente: {$component}");
                return false;
            }
            
            // Verificar cada métrica contra seu threshold
            $groupedMetrics = [];
            foreach ($metrics as $metric) {
                $name = $metric['metric_name'];
                if (!isset($groupedMetrics[$name])) {
                    $groupedMetrics[$name] = [];
                }
                $groupedMetrics[$name][] = $metric['metric_value'];
            }
            
            foreach ($groupedMetrics as $metricName => $values) {
                // Calcular média
                $avgValue = array_sum($values) / count($values);
                
                // Verificar threshold
                $threshold = $this->thresholds->getThresholdForMetric($metricName);
                
                if ($threshold && $this->thresholds->isThresholdExceeded($metricName, $avgValue)) {
                    // Criar alerta
                    $this->notificationManager->createPerformanceAlert(
                        $metricName,
                        $avgValue,
                        $component,
                        ['admin'] // Apenas administradores
                    );
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Erro ao monitorar componente: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gera um relatório de saúde do sistema
     * 
     * @return array Dados do relatório
     */
    public function generateSystemHealthReport() {
        try {
            $report = [
                'timestamp' => date('Y-m-d H:i:s'),
                'components' => [],
                'alerts' => [
                    'active' => 0,
                    'critical' => 0,
                    'high' => 0,
                    'medium' => 0,
                    'low' => 0
                ],
                'metrics' => [],
                'trends' => [],
                'recommendations' => []
            ];
            
            // Obter status dos componentes críticos
            foreach ($this->criticalComponents as $component) {
                // Verificar alertas ativos para este componente
                $sql = "SELECT COUNT(*) as alert_count, MAX(severity) as max_severity 
                        FROM performance_dashboard pd
                        JOIN notifications n ON pd.notification_id = n.id
                        WHERE pd.component = :component
                        AND pd.resolved = 0";
                
                $params = [':component' => $component];
                $alertStatus = $this->db->fetchSingle($sql, $params);
                
                // Determinar status geral com base em alertas
                $status = 'healthy'; // Padrão
                
                if ($alertStatus && $alertStatus['alert_count'] > 0) {
                    switch ($alertStatus['max_severity']) {
                        case 'critical':
                            $status = 'critical';
                            break;
                        case 'high':
                            $status = 'warning';
                            break;
                        case 'medium':
                        case 'low':
                            $status = 'attention';
                            break;
                    }
                }
                
                // Obter métricas mais recentes
                $sql = "SELECT metric_name, AVG(metric_value) as avg_value 
                        FROM performance_metrics 
                        WHERE component = :component 
                        AND timestamp > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
                        GROUP BY metric_name";
                
                $recentMetrics = $this->db->fetchAll($sql, $params);
                
                $componentMetrics = [];
                if ($recentMetrics) {
                    foreach ($recentMetrics as $metric) {
                        $componentMetrics[$metric['metric_name']] = $metric['avg_value'];
                    }
                }
                
                // Adicionar ao relatório
                $report['components'][$component] = [
                    'status' => $status,
                    'alert_count' => $alertStatus ? $alertStatus['alert_count'] : 0,
                    'metrics' => $componentMetrics
                ];
            }
            
            // Contabilizar alertas ativos
            $sql = "SELECT severity, COUNT(*) as count 
                    FROM performance_dashboard 
                    WHERE resolved = 0 
                    GROUP BY severity";
            
            $alertCounts = $this->db->fetchAll($sql);
            
            if ($alertCounts) {
                foreach ($alertCounts as $count) {
                    $severity = $count['severity'];
                    $report['alerts'][$severity] = $count['count'];
                    $report['alerts']['active'] += $count['count'];
                }
            }
            
            // Obter métricas críticas do sistema
            $criticalMetrics = [
                'response_time',
                'memory_usage',
                'cpu_usage',
                'query_time'
            ];
            
            foreach ($criticalMetrics as $metric) {
                // Obter valor médio geral nas últimas 3 horas
                $sql = "SELECT AVG(metric_value) as avg_value 
                        FROM performance_metrics 
                        WHERE metric_name = :metric 
                        AND timestamp > DATE_SUB(NOW(), INTERVAL 3 HOUR)";
                
                $params = [':metric' => $metric];
                $result = $this->db->fetchSingle($sql, $params);
                
                if ($result) {
                    $threshold = $this->thresholds->getThresholdForMetric($metric);
                    
                    $status = 'normal';
                    if ($threshold && $this->thresholds->isThresholdExceeded($metric, $result['avg_value'])) {
                        $severity = $this->thresholds->determineSeverity($metric, $result['avg_value']);
                        $status = $severity;
                    }
                    
                    $report['metrics'][$metric] = [
                        'value' => $result['avg_value'],
                        'threshold' => $threshold ? $threshold['value'] : null,
                        'status' => $status
                    ];
                }
            }
            
            // Adicionar tendências e recomendações
            if ($report['alerts']['critical'] > 0 || $report['alerts']['high'] > 0) {
                $report['recommendations'][] = "Priorize a resolução de alertas críticos e de alta severidade.";
            }
            
            // Verificar componentes com problemas
            $problematicComponents = [];
            foreach ($report['components'] as $component => $data) {
                if ($data['status'] === 'critical' || $data['status'] === 'warning') {
                    $problematicComponents[] = $component;
                }
            }
            
            if (!empty($problematicComponents)) {
                $report['recommendations'][] = "Investigue os componentes problemáticos: " . implode(', ', $problematicComponents);
            }
            
            return $report;
        } catch (Exception $e) {
            error_log('Erro ao gerar relatório de saúde do sistema: ' . $e->getMessage());
            return [
                'error' => 'Não foi possível gerar o relatório completo',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
}
