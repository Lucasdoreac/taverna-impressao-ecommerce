<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Helper para funções auxiliares do monitoramento em ambiente de produção
 * Facilita o acesso às configurações e métodos de análise
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

/**
 * Obtém as configurações atuais de monitoramento de performance
 * 
 * @return array Configurações de monitoramento
 */
function getMonitorSettings() {
    // Verificar se a classe Model está disponível
    if (!class_exists('Model')) {
        return getDefaultMonitorSettings();
    }
    
    // Tentar obter configurações do banco de dados
    try {
        // Conectar ao banco de dados
        $db = Database::getInstance();
        
        // Verificar se a tabela existe
        $checkTable = $db->query("SHOW TABLES LIKE 'performance_monitor_settings'");
        
        if ($checkTable->num_rows == 0) {
            // A tabela não existe, usar configurações padrão
            return getDefaultMonitorSettings();
        }
        
        // Obter configurações
        $query = "SELECT * FROM performance_monitor_settings ORDER BY id DESC LIMIT 1";
        $result = $db->query($query);
        
        if ($result && $result->num_rows > 0) {
            $settings = $result->fetch_assoc();
            
            // Processar configurações
            $settings['ignore_paths'] = $settings['ignore_paths'] ? json_decode($settings['ignore_paths'], true) : ['/admin', '/api', '/login'];
            $settings['enabled'] = (bool)$settings['enabled'];
            $settings['sampling_rate'] = (int)$settings['sampling_rate'];
            $settings['min_time_between_sends'] = (int)$settings['min_time_between_sends'];
            
            return $settings;
        }
    } catch (Exception $e) {
        // Em caso de erro, registrar e usar configurações padrão
        error_log("Erro ao obter configurações de monitoramento: " . $e->getMessage());
    }
    
    // Se chegou aqui, usar configurações padrão
    return getDefaultMonitorSettings();
}

/**
 * Retorna as configurações padrão de monitoramento
 * 
 * @return array Configurações padrão
 */
function getDefaultMonitorSettings() {
    return [
        'enabled' => true,
        'sampling_rate' => 10, // 10% dos usuários
        'ignore_paths' => ['/admin', '/api', '/login'],
        'min_time_between_sends' => 3600000, // 1 hora em ms
        'alert_threshold' => 20, // 20% de mudança para alertas
        'metrics_to_collect' => ['navigation', 'resource', 'paint', 'memory', 'layout', 'firstInput', 'largestPaint'],
        'notification_email' => ''
    ];
}

/**
 * Salva as configurações de monitoramento
 * 
 * @param array $settings Configurações a serem salvas
 * @return bool True se salvas com sucesso, false caso contrário
 */
function saveMonitorSettings($settings) {
    // Verificar se a classe Model está disponível
    if (!class_exists('Model')) {
        return false;
    }
    
    // Processar e validar configurações
    $enabled = isset($settings['enabled']) ? (bool)$settings['enabled'] : true;
    $samplingRate = isset($settings['sampling_rate']) ? min(100, max(1, (int)$settings['sampling_rate'])) : 10;
    $ignorePaths = isset($settings['ignore_paths']) ? $settings['ignore_paths'] : ['/admin', '/api', '/login'];
    $minTimeBetweenSends = isset($settings['min_time_between_sends']) ? (int)$settings['min_time_between_sends'] : 3600000;
    $alertThreshold = isset($settings['alert_threshold']) ? (int)$settings['alert_threshold'] : 20;
    $notificationEmail = isset($settings['notification_email']) ? $settings['notification_email'] : '';
    
    // Codificar arrays para JSON
    $ignorePathsJson = json_encode($ignorePaths);
    
    try {
        // Conectar ao banco de dados
        $db = Database::getInstance();
        
        // Verificar se a tabela existe
        $checkTable = $db->query("SHOW TABLES LIKE 'performance_monitor_settings'");
        
        if ($checkTable->num_rows == 0) {
            // Criar tabela se não existir
            $createTable = "CREATE TABLE performance_monitor_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                enabled BOOLEAN DEFAULT TRUE,
                sampling_rate INT DEFAULT 10,
                ignore_paths TEXT,
                min_time_between_sends INT DEFAULT 3600000,
                alert_threshold INT DEFAULT 20,
                notification_email VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            
            $db->query($createTable);
        }
        
        // Verificar se já existem configurações
        $checkSettings = $db->query("SELECT COUNT(*) as count FROM performance_monitor_settings");
        $row = $checkSettings->fetch_assoc();
        $count = $row['count'];
        
        if ($count > 0) {
            // Atualizar configurações existentes
            $query = "UPDATE performance_monitor_settings SET 
                        enabled = ?, 
                        sampling_rate = ?, 
                        ignore_paths = ?, 
                        min_time_between_sends = ?,
                        alert_threshold = ?,
                        notification_email = ?,
                        updated_at = NOW()
                      ORDER BY id DESC LIMIT 1";
                      
            $stmt = $db->prepare($query);
            $stmt->bind_param("iisiss", $enabled, $samplingRate, $ignorePathsJson, $minTimeBetweenSends, $alertThreshold, $notificationEmail);
            $result = $stmt->execute();
            $stmt->close();
        } else {
            // Inserir novas configurações
            $query = "INSERT INTO performance_monitor_settings 
                        (enabled, sampling_rate, ignore_paths, min_time_between_sends, alert_threshold, notification_email) 
                      VALUES (?, ?, ?, ?, ?, ?)";
                      
            $stmt = $db->prepare($query);
            $stmt->bind_param("iisiss", $enabled, $samplingRate, $ignorePathsJson, $minTimeBetweenSends, $alertThreshold, $notificationEmail);
            $result = $stmt->execute();
            $stmt->close();
        }
        
        return $result;
    } catch (Exception $e) {
        // Em caso de erro, registrar e retornar false
        error_log("Erro ao salvar configurações de monitoramento: " . $e->getMessage());
        return false;
    }
}

/**
 * Formata um valor de tempo para exibição amigável
 * 
 * @param float $time Tempo em milissegundos
 * @param bool $includeUnit Se deve incluir a unidade (ms)
 * @return string Tempo formatado
 */
function formatTime($time, $includeUnit = true) {
    if ($time === null) {
        return 'N/A';
    }
    
    $unit = $includeUnit ? ' ms' : '';
    
    if ($time >= 1000) {
        // Converter para segundos se for maior que 1000ms
        return round($time / 1000, 2) . ($includeUnit ? ' s' : '');
    } else {
        return round($time, 0) . $unit;
    }
}

/**
 * Formata um tamanho de bytes para exibição amigável
 * 
 * @param int $bytes Tamanho em bytes
 * @return string Tamanho formatado
 */
function formatBytes($bytes) {
    if ($bytes === null) {
        return 'N/A';
    }
    
    if ($bytes < 1024) {
        return $bytes . ' B';
    } else if ($bytes < 1048576) {
        return round($bytes / 1024, 2) . ' KB';
    } else if ($bytes < 1073741824) {
        return round($bytes / 1048576, 2) . ' MB';
    } else {
        return round($bytes / 1073741824, 2) . ' GB';
    }
}

/**
 * Obtém a cor de status com base no valor de uma métrica
 * 
 * @param float $value Valor numérico
 * @param string $metric Tipo de métrica (lcp, cls, fid, load)
 * @return string Cor de status (success, warning, danger)
 */
function getMetricStatusColor($value, $metric = 'load') {
    // Thresholds para cada tipo de métrica
    $thresholds = [
        'lcp' => [
            'success' => 2500, // Menor que 2500ms é bom
            'warning' => 4000  // Menor que 4000ms é razoável
        ],
        'cls' => [
            'success' => 0.1,  // Menor que 0.1 é bom
            'warning' => 0.25  // Menor que 0.25 é razoável
        ],
        'fid' => [
            'success' => 100,  // Menor que 100ms é bom
            'warning' => 300   // Menor que 300ms é razoável
        ],
        'load' => [
            'success' => 2000, // Menor que 2000ms é bom
            'warning' => 4000  // Menor que 4000ms é razoável
        ]
    ];
    
    // Usar thresholds padrão se o tipo não estiver definido
    $limits = isset($thresholds[$metric]) ? $thresholds[$metric] : $thresholds['load'];
    
    if ($value === null) {
        return 'secondary';
    }
    
    if ($value <= $limits['success']) {
        return 'success';
    } else if ($value <= $limits['warning']) {
        return 'warning';
    } else {
        return 'danger';
    }
}

/**
 * Analisa dados de monitoramento para identificar alertas e tendências
 * 
 * @param array $monitorData Dados de monitoramento a analisar
 * @param int $days Período de análise em dias
 * @return array Alertas e tendências identificadas
 */
function analyzeMonitoringData($monitorData, $days = 7) {
    $analysis = [
        'alerts' => [],
        'trends' => [],
        'period' => $days
    ];
    
    // Verificar se temos dados suficientes
    if (empty($monitorData) || count($monitorData) < 2) {
        $analysis['status'] = 'insufficient_data';
        return $analysis;
    }
    
    // Obter configurações para threshold de alerta
    $settings = getMonitorSettings();
    $alertThreshold = isset($settings['alert_threshold']) ? (int)$settings['alert_threshold'] : 20;
    
    // Dividir o período em dois para comparação
    $halfPoint = count($monitorData) / 2;
    $recentData = array_slice($monitorData, 0, $halfPoint);
    $olderData = array_slice($monitorData, $halfPoint);
    
    // Calcular médias para métricas importantes
    $metrics = [
        'loadTime' => ['recent' => 0, 'older' => 0, 'count_recent' => 0, 'count_older' => 0],
        'lcp' => ['recent' => 0, 'older' => 0, 'count_recent' => 0, 'count_older' => 0],
        'cls' => ['recent' => 0, 'older' => 0, 'count_recent' => 0, 'count_older' => 0],
        'fid' => ['recent' => 0, 'older' => 0, 'count_recent' => 0, 'count_older' => 0]
    ];
    
    // Calcular médias para o período recente
    foreach ($recentData as $data) {
        if (!empty($data['metrics'])) {
            // Extrair métricas de carregamento
            if (isset($data['metrics']['navigation']) && isset($data['metrics']['navigation']['loadTime'])) {
                $metrics['loadTime']['recent'] += $data['metrics']['navigation']['loadTime'];
                $metrics['loadTime']['count_recent']++;
            }
            
            // Extrair LCP
            if (isset($data['metrics']['lcp']) && isset($data['metrics']['lcp']['value'])) {
                $metrics['lcp']['recent'] += $data['metrics']['lcp']['value'];
                $metrics['lcp']['count_recent']++;
            }
            
            // Extrair CLS
            if (isset($data['metrics']['cls'])) {
                $metrics['cls']['recent'] += $data['metrics']['cls'];
                $metrics['cls']['count_recent']++;
            }
            
            // Extrair FID
            if (isset($data['metrics']['fid']) && isset($data['metrics']['fid']['delay'])) {
                $metrics['fid']['recent'] += $data['metrics']['fid']['delay'];
                $metrics['fid']['count_recent']++;
            }
        }
    }
    
    // Calcular médias para o período anterior
    foreach ($olderData as $data) {
        if (!empty($data['metrics'])) {
            // Extrair métricas de carregamento
            if (isset($data['metrics']['navigation']) && isset($data['metrics']['navigation']['loadTime'])) {
                $metrics['loadTime']['older'] += $data['metrics']['navigation']['loadTime'];
                $metrics['loadTime']['count_older']++;
            }
            
            // Extrair LCP
            if (isset($data['metrics']['lcp']) && isset($data['metrics']['lcp']['value'])) {
                $metrics['lcp']['older'] += $data['metrics']['lcp']['value'];
                $metrics['lcp']['count_older']++;
            }
            
            // Extrair CLS
            if (isset($data['metrics']['cls'])) {
                $metrics['cls']['older'] += $data['metrics']['cls'];
                $metrics['cls']['count_older']++;
            }
            
            // Extrair FID
            if (isset($data['metrics']['fid']) && isset($data['metrics']['fid']['delay'])) {
                $metrics['fid']['older'] += $data['metrics']['fid']['delay'];
                $metrics['fid']['count_older']++;
            }
        }
    }
    
    // Calcular médias finais
    foreach ($metrics as $key => $values) {
        if ($values['count_recent'] > 0) {
            $metrics[$key]['recent_avg'] = $values['recent'] / $values['count_recent'];
        } else {
            $metrics[$key]['recent_avg'] = null;
        }
        
        if ($values['count_older'] > 0) {
            $metrics[$key]['older_avg'] = $values['older'] / $values['count_older'];
        } else {
            $metrics[$key]['older_avg'] = null;
        }
    }
    
    // Verificar mudanças significativas
    foreach ($metrics as $metric => $values) {
        if ($values['recent_avg'] !== null && $values['older_avg'] !== null && $values['older_avg'] > 0) {
            // Calcular mudança percentual
            $percentChange = (($values['recent_avg'] - $values['older_avg']) / $values['older_avg']) * 100;
            
            // Se a mudança for significativa, adicionar alerta
            if (abs($percentChange) >= $alertThreshold) {
                $improved = $percentChange < 0;
                
                // Para CLS, menor é melhor
                if ($metric === 'cls') {
                    $improved = $percentChange < 0;
                }
                
                $analysis['alerts'][] = [
                    'metric' => $metric,
                    'recent_value' => $values['recent_avg'],
                    'older_value' => $values['older_avg'],
                    'percent_change' => round($percentChange, 1),
                    'improved' => $improved,
                    'label' => getMetricLabel($metric)
                ];
            }
            
            // Registrar tendência
            $analysis['trends'][$metric] = [
                'recent' => $values['recent_avg'],
                'older' => $values['older_avg'],
                'change' => round($percentChange, 1),
                'label' => getMetricLabel($metric)
            ];
        }
    }
    
    // Adicionar status geral
    $analysis['status'] = !empty($analysis['alerts']) ? 'alerts_found' : 'normal';
    
    return $analysis;
}

/**
 * Obtém rótulo legível para uma métrica
 * 
 * @param string $metric Código da métrica
 * @return string Rótulo legível
 */
function getMetricLabel($metric) {
    $labels = [
        'loadTime' => 'Tempo de Carregamento',
        'lcp' => 'Largest Contentful Paint',
        'cls' => 'Cumulative Layout Shift',
        'fid' => 'First Input Delay',
        'ttfb' => 'Time to First Byte',
        'fcp' => 'First Contentful Paint'
    ];
    
    return isset($labels[$metric]) ? $labels[$metric] : $metric;
}
