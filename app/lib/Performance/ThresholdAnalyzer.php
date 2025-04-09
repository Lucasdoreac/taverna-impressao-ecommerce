<?php
/**
 * ThresholdAnalyzer - Análise de métricas e detecção de anomalias
 * 
 * Classe responsável por analisar métricas de performance, detectar
 * anomalias e gerar alertas quando valores ultrapassam limiares configurados.
 * 
 * @package App\Lib\Performance
 * @author Taverna da Impressão 3D
 * @version 1.0.0
 */
class ThresholdAnalyzer {
    /** @var PDO Conexão com o banco de dados */
    private $db;
    
    /** @var PerformanceMetrics Instância do coletor de métricas */
    private $metrics;
    
    /** @var array Cache de configurações de threshold */
    private $thresholdConfig = null;
    
    /**
     * Construtor
     * 
     * @param PDO $db Conexão com o banco de dados
     * @param PerformanceMetrics $metrics Instância do coletor de métricas
     */
    public function __construct(PDO $db, PerformanceMetrics $metrics) {
        $this->db = $db;
        $this->metrics = $metrics;
    }
    
    /**
     * Analisa métricas recentes para identificar anomalias
     * 
     * @param string $context Contexto opcional para filtrar (ex: "sales_report")
     * @return array Lista de anomalias detectadas
     */
    public function analyzeMetrics($context = null) {
        // Carregar configurações se ainda não estiverem em cache
        if ($this->thresholdConfig === null) {
            $this->loadThresholdConfig();
        }
        
        $anomalies = [];
        
        // Para cada tipo de métrica configurado
        foreach ($this->thresholdConfig as $metricName => $config) {
            // Pular se o contexto não corresponder e houver um filtro
            if ($context !== null && isset($config['context']) && $config['context'] !== $context) {
                continue;
            }
            
            // Obter estatísticas recentes para a métrica
            $stats = $this->metrics->getMetricStatistics(
                $metricName,
                $context,
                $config['time_window'] ?? 3600
            );
            
            // Verificar erro nas estatísticas
            if (isset($stats['error'])) {
                continue;
            }
            
            // Aplicar verificações baseadas no tipo de limiar
            $anomaly = $this->checkThresholds($metricName, $stats, $config, $context);
            if ($anomaly !== null) {
                $anomalies[] = $anomaly;
            }
        }
        
        return $anomalies;
    }
    
    /**
     * Verifica se uma métrica excedeu os limiares configurados
     * 
     * @param string $metricName Nome da métrica
     * @param array $stats Estatísticas da métrica
     * @param array $config Configuração dos limiares
     * @param string|null $context Contexto da análise
     * @return array|null Dados da anomalia ou null se dentro dos limites
     */
    private function checkThresholds($metricName, $stats, $config, $context = null) {
        $threshold = null;
        $actual = null;
        $thresholdType = $config['type'] ?? 'fixed';
        
        // Determinar o valor a ser verificado com base no tipo de limiar
        switch ($thresholdType) {
            case 'fixed':
                $threshold = $config['max_value'] ?? null;
                $actual = $stats['max_value'] ?? null;
                if ($threshold !== null && $actual !== null && $actual > $threshold) {
                    return $this->createAnomaly($metricName, $actual, $threshold, $config, $stats, $context);
                }
                break;
                
            case 'percentile':
                $percentileKey = 'p' . ($config['percentile'] ?? 95);
                $threshold = $config['max_percentile'] ?? null;
                $actual = $stats[$percentileKey] ?? null;
                if ($threshold !== null && $actual !== null && $actual > $threshold) {
                    return $this->createAnomaly($metricName, $actual, $threshold, $config, $stats, $context);
                }
                break;
                
            case 'stddev':
                $baseValue = $stats['avg_value'] ?? null;
                $stdDev = $stats['std_dev'] ?? null;
                $multiplier = $config['stddev_multiplier'] ?? 3;
                
                if ($baseValue !== null && $stdDev !== null) {
                    $threshold = $baseValue + ($stdDev * $multiplier);
                    $actual = $stats['max_value'] ?? null;
                    
                    if ($actual !== null && $actual > $threshold) {
                        return $this->createAnomaly($metricName, $actual, $threshold, $config, $stats, $context);
                    }
                }
                break;
                
            case 'trend':
                // Implementação de detecção de tendências usando séries temporais
                try {
                    $historicalData = $this->getHistoricalTrend($metricName, $context, $config);
                    $recentData = $this->getRecentTrend($metricName, $context, $config);
                    
                    if (!empty($historicalData) && !empty($recentData)) {
                        // Calcular taxa de crescimento
                        $historicalSlope = $this->calculateTrendSlope($historicalData);
                        $recentSlope = $this->calculateTrendSlope($recentData);
                        
                        // Verificar se a tendência recente é significativamente maior
                        $trendFactor = $config['trend_factor'] ?? 2.0;
                        if ($recentSlope > $historicalSlope * $trendFactor && $recentSlope > 0) {
                            // Criar anomalia de tendência
                            return $this->createTrendAnomaly(
                                $metricName, 
                                $recentSlope, 
                                $historicalSlope, 
                                $trendFactor, 
                                $config, 
                                $context
                            );
                        }
                    }
                } catch (Exception $e) {
                    error_log("Erro ao analisar tendência: " . $e->getMessage());
                }
                break;
        }
        
        return null;
    }
    
    /**
     * Obtém dados históricos para análise de tendência
     * 
     * @param string $metricName Nome da métrica
     * @param string|null $context Contexto da análise
     * @param array $config Configuração dos limiares
     * @return array Dados históricos
     */
    private function getHistoricalTrend($metricName, $context, $config) {
        $historicalWindow = $config['historical_window'] ?? 86400; // 24 horas padrão
        $now = microtime(true);
        $start = $now - $historicalWindow;
        $end = $now - ($config['recent_window'] ?? 3600); // Excluir dados recentes
        
        return $this->getTrendData($metricName, $context, $start, $end);
    }
    
    /**
     * Obtém dados recentes para análise de tendência
     * 
     * @param string $metricName Nome da métrica
     * @param string|null $context Contexto da análise
     * @param array $config Configuração dos limiares
     * @return array Dados recentes
     */
    private function getRecentTrend($metricName, $context, $config) {
        $recentWindow = $config['recent_window'] ?? 3600; // 1 hora padrão
        $now = microtime(true);
        $start = $now - $recentWindow;
        
        return $this->getTrendData($metricName, $context, $start, $now);
    }
    
    /**
     * Obtém dados para um período específico
     * 
     * @param string $metricName Nome da métrica
     * @param string|null $context Contexto da análise
     * @param float $start Timestamp de início
     * @param float $end Timestamp de fim
     * @return array Dados do período
     */
    private function getTrendData($metricName, $context, $start, $end) {
        try {
            $params = [
                ':metric_name' => $metricName,
                ':start_time' => $start,
                ':end_time' => $end
            ];
            
            $conditions = ["name = :metric_name", "timestamp >= :start_time", "timestamp <= :end_time"];
            
            if ($context !== null) {
                $conditions[] = "context = :context";
                $params[':context'] = $context;
            }
            
            $where = "WHERE " . implode(" AND ", $conditions);
            
            $sql = "
                SELECT timestamp, CAST(value AS FLOAT) as value
                FROM performance_metrics
                {$where}
                ORDER BY timestamp ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao obter dados de tendência: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calcula a inclinação da linha de tendência (taxa de crescimento)
     * 
     * @param array $data Dados para cálculo
     * @return float Inclinação da linha de tendência
     */
    private function calculateTrendSlope($data) {
        if (count($data) < 2) {
            return 0;
        }
        
        // Extrair timestamps e valores
        $timestamps = array_column($data, 'timestamp');
        $values = array_column($data, 'value');
        
        // Normalizar timestamps para evitar problemas de escala
        $baseTime = $timestamps[0];
        $normalizedTimestamps = array_map(function($t) use ($baseTime) {
            return $t - $baseTime;
        }, $timestamps);
        
        // Calcular médias
        $meanX = array_sum($normalizedTimestamps) / count($normalizedTimestamps);
        $meanY = array_sum($values) / count($values);
        
        // Calcular coeficientes
        $numerator = 0;
        $denominator = 0;
        
        for ($i = 0; $i < count($data); $i++) {
            $numerator += ($normalizedTimestamps[$i] - $meanX) * ($values[$i] - $meanY);
            $denominator += pow($normalizedTimestamps[$i] - $meanX, 2);
        }
        
        if ($denominator == 0) {
            return 0;
        }
        
        return $numerator / $denominator;
    }
    
    /**
     * Cria uma anomalia de tendência
     * 
     * @param string $metricName Nome da métrica
     * @param float $recentSlope Inclinação recente
     * @param float $historicalSlope Inclinação histórica
     * @param float $trendFactor Fator de tendência
     * @param array $config Configuração dos limiares
     * @param string|null $context Contexto da análise
     * @return array Dados da anomalia
     */
    private function createTrendAnomaly($metricName, $recentSlope, $historicalSlope, $trendFactor, $config, $context = null) {
        // Determinar a severidade com base na configuração
        $severityConfig = $config['severity'] ?? ['default' => 'medium'];
        $growthRate = $recentSlope / max(0.0001, $historicalSlope); // Evitar divisão por zero
        $severity = $severityConfig['default'] ?? 'medium';
        
        // Aplicar regras de severidade baseadas na taxa de crescimento
        if (isset($severityConfig['rules'])) {
            foreach ($severityConfig['rules'] as $rule) {
                if (isset($rule['min_growth_rate']) && 
                    isset($rule['severity']) && 
                    $growthRate >= $rule['min_growth_rate']) {
                    $severity = $rule['severity'];
                    break;
                }
            }
        }
        
        // Gerar um ID único para a anomalia
        $anomalyId = uniqid('trend_anomaly_', true);
        
        // Criar descrição
        $description = "Alerta de tendência: {$metricName}";
        if ($context !== null) {
            $description .= " em {$context}";
        }
        $description .= " apresenta crescimento acelerado (" . round($growthRate, 2) . "x acima do padrão histórico).";
        
        // Criar e retornar a estrutura de dados da anomalia
        return [
            'id' => $anomalyId,
            'metric_name' => $metricName,
            'context' => $context,
            'timestamp' => microtime(true),
            'recent_slope' => $recentSlope,
            'historical_slope' => $historicalSlope,
            'growth_rate' => $growthRate,
            'trend_factor' => $trendFactor,
            'severity' => $severity,
            'threshold_type' => 'trend',
            'description' => $description
        ];
    }
    
    /**
     * Cria uma estrutura de dados para representar uma anomalia detectada
     * 
     * @param string $metricName Nome da métrica
     * @param float $actualValue Valor atual
     * @param float $thresholdValue Valor limite
     * @param array $config Configuração do limiar
     * @param array $stats Estatísticas da métrica
     * @param string|null $context Contexto da análise
     * @return array Dados da anomalia
     */
    private function createAnomaly($metricName, $actualValue, $thresholdValue, $config, $stats, $context = null) {
        // Determinar a severidade com base na configuração e no desvio
        $severityConfig = $config['severity'] ?? ['default' => 'medium'];
        $deviation = ($actualValue - $thresholdValue) / max(0.0001, $thresholdValue); // Evitar divisão por zero
        $severity = $severityConfig['default'] ?? 'medium';
        
        // Aplicar regras de severidade baseadas no desvio percentual
        if (isset($severityConfig['rules'])) {
            foreach ($severityConfig['rules'] as $rule) {
                if (isset($rule['min_deviation']) && 
                    isset($rule['severity']) && 
                    $deviation >= $rule['min_deviation']) {
                    $severity = $rule['severity'];
                    break;
                }
            }
        }
        
        // Gerar um ID único para a anomalia
        $anomalyId = uniqid('anomaly_', true);
        
        // Criar e retornar a estrutura de dados da anomalia
        return [
            'id' => $anomalyId,
            'metric_name' => $metricName,
            'context' => $context,
            'timestamp' => microtime(true),
            'actual_value' => $actualValue,
            'threshold_value' => $thresholdValue,
            'deviation' => $deviation,
            'deviation_percentage' => $deviation * 100,
            'severity' => $severity,
            'threshold_type' => $config['type'] ?? 'fixed',
            'stats' => $stats,
            'description' => $this->generateAnomalyDescription(
                $metricName, 
                $actualValue, 
                $thresholdValue, 
                $deviation, 
                $context
            )
        ];
    }
    
    /**
     * Gera uma descrição textual da anomalia para notificações
     * 
     * @param string $metricName Nome da métrica
     * @param float $actualValue Valor atual
     * @param float $thresholdValue Valor limite
     * @param float $deviation Desvio
     * @param string|null $context Contexto da análise
     * @return string Descrição da anomalia
     */
    private function generateAnomalyDescription($metricName, $actualValue, $thresholdValue, $deviation, $context = null) {
        // Formatar os valores para exibição
        $formattedActual = $this->formatMetricValue($metricName, $actualValue);
        $formattedThreshold = $this->formatMetricValue($metricName, $thresholdValue);
        $deviationPercentage = round($deviation * 100, 2);
        
        // Construir a descrição base
        $description = "Alerta de performance: {$metricName}";
        
        if ($context !== null) {
            $description .= " em {$context}";
        }
        
        $description .= " excedeu o limite de {$formattedThreshold} atingindo {$formattedActual} ";
        $description .= "(+{$deviationPercentage}% acima do limite).";
        
        return $description;
    }
    
    /**
     * Formata o valor da métrica para exibição com base em seu tipo
     * 
     * @param string $metricName Nome da métrica
     * @param float $value Valor a ser formatado
     * @return string Valor formatado
     */
    private function formatMetricValue($metricName, $value) {
        // Aplicar formatação específica com base no tipo de métrica
        if (strpos($metricName, 'time') !== false || strpos($metricName, 'duration') !== false) {
            // Formatar como tempo (em ms ou s)
            if ($value < 1) {
                return round($value * 1000, 2) . "ms";
            } else {
                return round($value, 2) . "s";
            }
        } else if (strpos($metricName, 'memory') !== false || strpos($metricName, 'size') !== false) {
            // Formatar como tamanho (bytes, KB, MB, etc.)
            $units = ['B', 'KB', 'MB', 'GB'];
            $size = $value;
            $i = 0;
            
            while ($size >= 1024 && $i < count($units) - 1) {
                $size /= 1024;
                $i++;
            }
            
            return round($size, 2) . " " . $units[$i];
        } else if (strpos($metricName, 'percentage') !== false || strpos($metricName, 'ratio') !== false) {
            // Formatar como percentual
            return round($value, 2) . "%";
        } else {
            // Formatação genérica para números
            return round($value, 2);
        }
    }
    
    /**
     * Carrega as configurações de limiares do banco de dados
     * 
     * @return void
     */
    private function loadThresholdConfig() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM performance_thresholds 
                WHERE active = 1
            ");
            
            $stmt->execute();
            $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->thresholdConfig = [];
            
            foreach ($configs as $config) {
                $metricName = $config['metric_name'];
                
                // Converter valores JSON para arrays
                $config['settings'] = json_decode($config['settings'], true);
                $config['severity'] = json_decode($config['severity'], true);
                
                // Armazenar configuração
                $this->thresholdConfig[$metricName] = array_merge(
                    [
                        'type' => $config['threshold_type'], 
                        'context' => $config['context'],
                        'time_window' => $config['time_window']
                    ],
                    $config['settings'],
                    ['severity' => $config['severity']]
                );
            }
        } catch (PDOException $e) {
            // Registrar erro
            error_log("Erro ao carregar configurações de limiares: " . $e->getMessage());
            
            // Configuração padrão para evitar falhas
            $this->thresholdConfig = [
                'query_time' => [
                    'type' => 'fixed',
                    'max_value' => 5.0,
                    'time_window' => 3600,
                    'severity' => ['default' => 'medium']
                ],
                'memory_usage' => [
                    'type' => 'fixed',
                    'max_value' => 100 * 1024 * 1024, // 100MB
                    'time_window' => 3600,
                    'severity' => ['default' => 'medium']
                ]
            ];
        }
    }
    
    /**
     * Atualiza ou adiciona uma configuração de limiar
     * 
     * @param string $metricName Nome da métrica
     * @param array $config Configuração do limiar
     * @return bool True se bem-sucedido, false caso contrário
     */
    public function setThreshold($metricName, array $config) {
        try {
            // Verificar se a configuração já existe
            $stmt = $this->db->prepare("
                SELECT id FROM performance_thresholds 
                WHERE metric_name = :metric_name
                AND context = :context
            ");
            
            $context = $config['context'] ?? null;
            
            $stmt->execute([
                ':metric_name' => $metricName,
                ':context' => $context
            ]);
            
            $existingId = $stmt->fetchColumn();
            
            // Extrair valores relevantes da configuração
            $thresholdType = $config['type'] ?? 'fixed';
            $timeWindow = $config['time_window'] ?? 3600;
            $active = isset($config['active']) ? (int)$config['active'] : 1;
            
            // Separar configurações específicas do tipo e severidade
            $settings = $config;
            unset($settings['type'], $settings['context'], $settings['time_window'], $settings['active'], $settings['severity']);
            
            // Converter para JSON
            $settingsJson = json_encode($settings);
            $severityJson = json_encode($config['severity'] ?? ['default' => 'medium']);
            
            if ($existingId) {
                // Atualizar configuração existente
                $updateStmt = $this->db->prepare("
                    UPDATE performance_thresholds 
                    SET threshold_type = :threshold_type,
                        time_window = :time_window,
                        settings = :settings,
                        severity = :severity,
                        active = :active,
                        updated_at = :updated_at
                    WHERE id = :id
                ");
                
                $result = $updateStmt->execute([
                    ':id' => $existingId,
                    ':threshold_type' => $thresholdType,
                    ':time_window' => $timeWindow,
                    ':settings' => $settingsJson,
                    ':severity' => $severityJson,
                    ':active' => $active,
                    ':updated_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                // Criar nova configuração
                $insertStmt = $this->db->prepare("
                    INSERT INTO performance_thresholds 
                        (metric_name, context, threshold_type, time_window, settings, severity, active, created_at, updated_at)
                    VALUES 
                        (:metric_name, :context, :threshold_type, :time_window, :settings, :severity, :active, :created_at, :updated_at)
                ");
                
                $now = date('Y-m-d H:i:s');
                
                $result = $insertStmt->execute([
                    ':metric_name' => $metricName,
                    ':context' => $context,
                    ':threshold_type' => $thresholdType,
                    ':time_window' => $timeWindow,
                    ':settings' => $settingsJson,
                    ':severity' => $severityJson,
                    ':active' => $active,
                    ':created_at' => $now,
                    ':updated_at' => $now
                ]);
            }
            
            // Atualizar cache se bem-sucedido
            if ($result) {
                if ($this->thresholdConfig === null) {
                    $this->thresholdConfig = [];
                }
                
                $this->thresholdConfig[$metricName] = array_merge(
                    [
                        'type' => $thresholdType, 
                        'context' => $context,
                        'time_window' => $timeWindow
                    ],
                    $settings,
                    ['severity' => $config['severity'] ?? ['default' => 'medium']]
                );
            }
            
            return $result;
        } catch (PDOException $e) {
            // Registrar erro
            error_log("Erro ao definir limiar: " . $e->getMessage());
            
            return false;
        }
    }
    
    /**
     * Remove uma configuração de limiar
     * 
     * @param string $metricName Nome da métrica
     * @param string|null $context Contexto da configuração
     * @return bool True se bem-sucedido, false caso contrário
     */
    public function removeThreshold($metricName, $context = null) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM performance_thresholds 
                WHERE metric_name = :metric_name
                AND context = :context
            ");
            
            $result = $stmt->execute([
                ':metric_name' => $metricName,
                ':context' => $context
            ]);
            
            // Atualizar cache se bem-sucedido
            if ($result && $this->thresholdConfig !== null && isset($this->thresholdConfig[$metricName])) {
                unset($this->thresholdConfig[$metricName]);
            }
            
            return $result;
        } catch (PDOException $e) {
            // Registrar erro
            error_log("Erro ao remover limiar: " . $e->getMessage());
            
            return false;
        }
    }
    
    /**
     * Obtém todas as configurações de limiares
     * 
     * @param bool $includeInactive Incluir configurações inativas
     * @return array Lista de configurações
     */
    public function getAllThresholds($includeInactive = false) {
        try {
            $sql = "SELECT * FROM performance_thresholds";
            
            if (!$includeInactive) {
                $sql .= " WHERE active = 1";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Processar configurações
            foreach ($configs as &$config) {
                $config['settings'] = json_decode($config['settings'], true);
                $config['severity'] = json_decode($config['severity'], true);
            }
            
            return $configs;
        } catch (PDOException $e) {
            // Registrar erro
            error_log("Erro ao obter limiares: " . $e->getMessage());
            
            return [];
        }
    }
    
    /**
     * Obtém uma configuração de limiar específica
     * 
     * @param string $metricName Nome da métrica
     * @param string|null $context Contexto da configuração
     * @return array|null Configuração do limiar ou null se não encontrada
     */
    public function getThreshold($metricName, $context = null) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM performance_thresholds 
                WHERE metric_name = :metric_name
                AND context = :context
            ");
            
            $stmt->execute([
                ':metric_name' => $metricName,
                ':context' => $context
            ]);
            
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$config) {
                return null;
            }
            
            // Processar configuração
            $config['settings'] = json_decode($config['settings'], true);
            $config['severity'] = json_decode($config['severity'], true);
            
            return $config;
        } catch (PDOException $e) {
            // Registrar erro
            error_log("Erro ao obter limiar: " . $e->getMessage());
            
            return null;
        }
    }
}