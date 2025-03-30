<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Modelo para Monitoramento de Performance em Ambiente de Produção
 * Responsável por gerenciar dados de testes de performance em ambiente de produção,
 * incluindo coleta transparente de métricas, análise de longo prazo e alertas
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

class PerformanceMonitorModel extends Model {
    protected $table = 'performance_monitoring';
    protected $primaryKey = 'id';
    protected $fillable = [
        'page_url', 'user_agent', 'device_type', 'metrics', 'timestamp', 'session_id'
    ];
    
    /**
     * Construtor
     * Inicializa o modelo e verifica se as tabelas necessárias existem
     */
    public function __construct() {
        parent::__construct();
        
        // Verificar se as tabelas necessárias existem
        $this->checkAndCreateTables();
    }
    
    /**
     * Salva métricas coletadas em ambiente de produção
     * 
     * @param string $pageUrl URL da página
     * @param array $metrics Métricas coletadas
     * @param string $userAgent User agent do cliente
     * @param string $deviceType Tipo de dispositivo (desktop, tablet, mobile)
     * @param string $sessionId ID da sessão do usuário (opcional, anonimizado)
     * @return int|bool ID do registro ou false em caso de erro
     */
    public function saveProductionMetrics($pageUrl, $metrics, $userAgent, $deviceType, $sessionId = null) {
        try {
            $data = [
                'page_url' => $pageUrl,
                'user_agent' => $userAgent,
                'device_type' => $deviceType,
                'metrics' => json_encode($metrics),
                'timestamp' => date('Y-m-d H:i:s'),
                'session_id' => $sessionId ? md5($sessionId) : null // Anonimizar o ID da sessão
            ];
            
            return $this->create($data);
        } catch (Exception $e) {
            error_log("Erro ao salvar métricas de produção: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém métricas de performance para uma página específica
     * 
     * @param string $pageUrl URL da página (opcional)
     * @param string $startDate Data inicial (opcional)
     * @param string $endDate Data final (opcional)
     * @param string $deviceType Tipo de dispositivo (opcional)
     * @param int $limit Limite de resultados (opcional)
     * @return array Métricas de performance
     */
    public function getPageMetrics($pageUrl = null, $startDate = null, $endDate = null, $deviceType = null, $limit = 1000) {
        try {
            $conditions = [];
            $params = [];
            
            // Construir condições da consulta
            if ($pageUrl) {
                $conditions[] = "page_url = :page_url";
                $params['page_url'] = $pageUrl;
            }
            
            if ($startDate) {
                $conditions[] = "timestamp >= :start_date";
                $params['start_date'] = $startDate;
            }
            
            if ($endDate) {
                $conditions[] = "timestamp <= :end_date";
                $params['end_date'] = $endDate;
            }
            
            if ($deviceType) {
                $conditions[] = "device_type = :device_type";
                $params['device_type'] = $deviceType;
            }
            
            // Montar consulta SQL
            $sql = "SELECT * FROM {$this->table}";
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $sql .= " ORDER BY timestamp DESC LIMIT :limit";
            $params['limit'] = $limit;
            
            $results = $this->db()->select($sql, $params);
            
            // Processar resultados para decodificar JSON
            foreach ($results as &$result) {
                if (isset($result['metrics'])) {
                    $result['metrics'] = json_decode($result['metrics'], true);
                }
            }
            
            return $results;
        } catch (Exception $e) {
            error_log("Erro ao obter métricas de página: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém métricas agregadas para dashboard de monitoramento
     * 
     * @param int $days Número de dias para considerar
     * @return array Métricas agregadas
     */
    public function getDashboardMetrics($days = 30) {
        try {
            $metrics = [
                'page_views' => $this->getPageViewCount($days),
                'average_metrics' => $this->getAverageMetrics($days),
                'device_breakdown' => $this->getDeviceBreakdown($days),
                'top_pages' => $this->getTopPages($days, 10),
                'slowest_pages' => $this->getSlowestPages($days, 10),
                'metrics_over_time' => $this->getMetricsOverTime($days)
            ];
            
            return $metrics;
        } catch (Exception $e) {
            error_log("Erro ao obter métricas para dashboard: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém contagem de visualizações de página
     * 
     * @param int $days Número de dias para considerar
     * @return int Contagem de visualizações
     */
    public function getPageViewCount($days = 30) {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL :days DAY)";
            
            $result = $this->db()->select($sql, ['days' => $days]);
            
            return isset($result[0]['count']) ? (int)$result[0]['count'] : 0;
        } catch (Exception $e) {
            error_log("Erro ao obter contagem de visualizações: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtém métricas médias para o período especificado
     * 
     * @param int $days Número de dias para considerar
     * @return array Métricas médias
     */
    public function getAverageMetrics($days = 30) {
        try {
            $sql = "SELECT 
                    AVG(JSON_EXTRACT(metrics, '$.loadTime')) as avg_load_time,
                    AVG(JSON_EXTRACT(metrics, '$.ttfb')) as avg_ttfb,
                    AVG(JSON_EXTRACT(metrics, '$.fcp')) as avg_fcp,
                    AVG(JSON_EXTRACT(metrics, '$.lcp')) as avg_lcp,
                    AVG(JSON_EXTRACT(metrics, '$.cls')) as avg_cls,
                    AVG(JSON_EXTRACT(metrics, '$.fid')) as avg_fid,
                    AVG(JSON_EXTRACT(metrics, '$.tbt')) as avg_tbt
                FROM {$this->table}
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL :days DAY)";
            
            $result = $this->db()->select($sql, ['days' => $days]);
            
            if (empty($result)) {
                return [];
            }
            
            // Processar e arredondar valores
            $metrics = $result[0];
            foreach ($metrics as $key => $value) {
                if ($value !== null) {
                    $metrics[$key] = round(floatval($value), 2);
                }
            }
            
            return $metrics;
        } catch (Exception $e) {
            error_log("Erro ao obter métricas médias: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém distribuição de tipos de dispositivo
     * 
     * @param int $days Número de dias para considerar
     * @return array Distribuição de dispositivos
     */
    public function getDeviceBreakdown($days = 30) {
        try {
            $sql = "SELECT 
                    device_type,
                    COUNT(*) as count,
                    (COUNT(*) / (SELECT COUNT(*) FROM {$this->table} 
                                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL :days DAY))) * 100 as percentage
                FROM {$this->table}
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL :days_inner DAY)
                GROUP BY device_type
                ORDER BY count DESC";
            
            return $this->db()->select($sql, ['days' => $days, 'days_inner' => $days]);
        } catch (Exception $e) {
            error_log("Erro ao obter distribuição de dispositivos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém as páginas mais acessadas
     * 
     * @param int $days Número de dias para considerar
     * @param int $limit Número de páginas a retornar
     * @return array Páginas mais acessadas
     */
    public function getTopPages($days = 30, $limit = 10) {
        try {
            $sql = "SELECT 
                    page_url,
                    COUNT(*) as view_count,
                    AVG(JSON_EXTRACT(metrics, '$.loadTime')) as avg_load_time
                FROM {$this->table}
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY page_url
                ORDER BY view_count DESC
                LIMIT :limit";
            
            return $this->db()->select($sql, ['days' => $days, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log("Erro ao obter páginas mais acessadas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém as páginas mais lentas
     * 
     * @param int $days Número de dias para considerar
     * @param int $limit Número de páginas a retornar
     * @return array Páginas mais lentas
     */
    public function getSlowestPages($days = 30, $limit = 10) {
        try {
            $sql = "SELECT 
                    page_url,
                    COUNT(*) as view_count,
                    AVG(JSON_EXTRACT(metrics, '$.loadTime')) as avg_load_time,
                    AVG(JSON_EXTRACT(metrics, '$.ttfb')) as avg_ttfb,
                    AVG(JSON_EXTRACT(metrics, '$.fcp')) as avg_fcp,
                    AVG(JSON_EXTRACT(metrics, '$.lcp')) as avg_lcp
                FROM {$this->table}
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY page_url
                HAVING COUNT(*) >= 5 -- Mínimo de 5 visualizações para relevância estatística
                ORDER BY avg_load_time DESC
                LIMIT :limit";
            
            return $this->db()->select($sql, ['days' => $days, 'limit' => $limit]);
        } catch (Exception $e) {
            error_log("Erro ao obter páginas mais lentas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém a evolução de métricas ao longo do tempo
     * 
     * @param int $days Número de dias para considerar
     * @return array Métricas ao longo do tempo
     */
    public function getMetricsOverTime($days = 30) {
        try {
            $sql = "SELECT 
                    DATE(timestamp) as date,
                    COUNT(*) as view_count,
                    AVG(JSON_EXTRACT(metrics, '$.loadTime')) as avg_load_time,
                    AVG(JSON_EXTRACT(metrics, '$.lcp')) as avg_lcp,
                    AVG(JSON_EXTRACT(metrics, '$.cls')) as avg_cls
                FROM {$this->table}
                WHERE timestamp >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY DATE(timestamp)
                ORDER BY date ASC";
            
            return $this->db()->select($sql, ['days' => $days]);
        } catch (Exception $e) {
            error_log("Erro ao obter métricas ao longo do tempo: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verifica se há deterioração de performance
     * 
     * @param int $days Número de dias para considerar
     * @param float $threshold Limite percentual para alertas (por exemplo, 20 para 20%)
     * @return array Alertas de deterioração
     */
    public function checkPerformanceDegradation($days = 7, $threshold = 20) {
        try {
            // Dividir o período em duas partes para comparação
            $halfDays = max(1, floor($days / 2));
            
            // Obter métricas para período recente
            $sqlRecent = "SELECT 
                        AVG(JSON_EXTRACT(metrics, '$.loadTime')) as avg_load_time,
                        AVG(JSON_EXTRACT(metrics, '$.lcp')) as avg_lcp,
                        AVG(JSON_EXTRACT(metrics, '$.fid')) as avg_fid,
                        AVG(JSON_EXTRACT(metrics, '$.cls')) as avg_cls
                    FROM {$this->table}
                    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL :days DAY)";
            
            $recentMetrics = $this->db()->select($sqlRecent, ['days' => $halfDays]);
            
            // Obter métricas para período anterior
            $sqlPrevious = "SELECT 
                        AVG(JSON_EXTRACT(metrics, '$.loadTime')) as avg_load_time,
                        AVG(JSON_EXTRACT(metrics, '$.lcp')) as avg_lcp,
                        AVG(JSON_EXTRACT(metrics, '$.fid')) as avg_fid,
                        AVG(JSON_EXTRACT(metrics, '$.cls')) as avg_cls
                    FROM {$this->table}
                    WHERE 
                        timestamp >= DATE_SUB(NOW(), INTERVAL :older_days DAY) AND
                        timestamp < DATE_SUB(NOW(), INTERVAL :recent_days DAY)";
            
            $previousMetrics = $this->db()->select($sqlPrevious, [
                'older_days' => $days,
                'recent_days' => $halfDays
            ]);
            
            // Verificar se temos dados suficientes
            if (empty($recentMetrics) || empty($previousMetrics)) {
                return ['status' => 'insufficient_data'];
            }
            
            // Inicializar array de alertas
            $alerts = [];
            
            // Verificar cada métrica
            foreach (['avg_load_time', 'avg_lcp', 'avg_fid', 'avg_cls'] as $metric) {
                $recent = floatval($recentMetrics[0][$metric]);
                $previous = floatval($previousMetrics[0][$metric]);
                
                // Ignorar caso não tenhamos valores válidos
                if ($previous <= 0 || $recent <= 0) {
                    continue;
                }
                
                // Para CLS, menor é melhor, então invertemos a lógica
                if ($metric === 'avg_cls') {
                    $percentChange = ($recent - $previous) / $previous * 100;
                } else {
                    $percentChange = ($recent - $previous) / $previous * 100;
                }
                
                // Se a mudança percentual exceder o threshold, adicionar alerta
                if (abs($percentChange) >= $threshold) {
                    $direction = $percentChange > 0 ? 'worse' : 'better';
                    
                    // Para CLS, a lógica é invertida
                    if ($metric === 'avg_cls') {
                        $direction = $percentChange > 0 ? 'worse' : 'better';
                    }
                    
                    $alerts[] = [
                        'metric' => $metric,
                        'previous' => round($previous, 2),
                        'recent' => round($recent, 2),
                        'percent_change' => round($percentChange, 2),
                        'direction' => $direction
                    ];
                }
            }
            
            return [
                'status' => 'success',
                'alerts' => $alerts,
                'period_days' => $days,
                'threshold' => $threshold
            ];
        } catch (Exception $e) {
            error_log("Erro ao verificar degradação de performance: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Executa limpeza de dados antigos
     * 
     * @param int $daysToKeep Número de dias de dados a manter
     * @return bool True se sucesso, false caso contrário
     */
    public function cleanupOldData($daysToKeep = 90) {
        try {
            $sql = "DELETE FROM {$this->table} 
                    WHERE timestamp < DATE_SUB(NOW(), INTERVAL :days DAY)";
            
            $this->db()->execute($sql, ['days' => $daysToKeep]);
            return true;
        } catch (Exception $e) {
            error_log("Erro ao limpar dados antigos: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se as tabelas necessárias existem e as cria se não existirem
     */
    private function checkAndCreateTables() {
        try {
            // Verificar se a tabela principal existe
            $sql = "SHOW TABLES LIKE '{$this->table}'";
            $result = $this->db()->select($sql);
            
            if (empty($result)) {
                // Criar tabela de monitoramento
                $sql = "CREATE TABLE {$this->table} (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          page_url VARCHAR(255) NOT NULL,
                          user_agent VARCHAR(255),
                          device_type VARCHAR(50),
                          metrics TEXT,
                          timestamp DATETIME,
                          session_id VARCHAR(32),
                          INDEX (page_url),
                          INDEX (device_type),
                          INDEX (timestamp)
                        )";
                $this->db()->execute($sql);
                
                error_log("Tabela {$this->table} criada com sucesso.");
            }
            
            // Verificar se a tabela de configurações existe
            $sql = "SHOW TABLES LIKE 'performance_monitor_settings'";
            $result = $this->db()->select($sql);
            
            if (empty($result)) {
                // Criar tabela de configurações
                $sql = "CREATE TABLE performance_monitor_settings (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          sampling_rate FLOAT DEFAULT 0.1,
                          enabled_pages TEXT,
                          alert_threshold FLOAT DEFAULT 20.0,
                          data_retention_days INT DEFAULT 90,
                          notification_email VARCHAR(255),
                          created_at DATETIME,
                          updated_at DATETIME
                        )";
                $this->db()->execute($sql);
                
                // Inserir configurações padrão
                $defaultSettings = [
                    'sampling_rate' => 0.1, // 10% dos usuários
                    'enabled_pages' => json_encode(['/', '/products', '/product/*', '/cart', '/checkout']),
                    'alert_threshold' => 20.0,
                    'data_retention_days' => 90,
                    'notification_email' => '',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $sql = "INSERT INTO performance_monitor_settings 
                        (sampling_rate, enabled_pages, alert_threshold, data_retention_days, notification_email, created_at, updated_at) 
                        VALUES 
                        (:sampling_rate, :enabled_pages, :alert_threshold, :data_retention_days, :notification_email, :created_at, :updated_at)";
                        
                $this->db()->execute($sql, $defaultSettings);
                
                error_log("Tabela performance_monitor_settings criada com sucesso e configurações padrão inseridas.");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao verificar/criar tabelas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém as configurações de monitoramento
     * 
     * @return array Configurações de monitoramento
     */
    public function getMonitorSettings() {
        try {
            $sql = "SELECT * FROM performance_monitor_settings LIMIT 1";
            $result = $this->db()->select($sql);
            
            if (empty($result)) {
                return null;
            }
            
            $settings = $result[0];
            
            // Decodificar campo JSON
            if (isset($settings['enabled_pages'])) {
                $settings['enabled_pages'] = json_decode($settings['enabled_pages'], true);
            }
            
            return $settings;
        } catch (Exception $e) {
            error_log("Erro ao obter configurações de monitoramento: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Salva as configurações de monitoramento
     * 
     * @param array $settings Configurações a serem salvas
     * @return bool True se salvo com sucesso, false caso contrário
     */
    public function saveMonitorSettings($settings) {
        try {
            // Codificar array para JSON
            if (isset($settings['enabled_pages']) && is_array($settings['enabled_pages'])) {
                $settings['enabled_pages'] = json_encode($settings['enabled_pages']);
            }
            
            // Verificar se já existem configurações
            $sql = "SELECT COUNT(*) as count FROM performance_monitor_settings";
            $result = $this->db()->select($sql);
            $count = $result[0]['count'];
            
            if ($count > 0) {
                // Atualizar configurações existentes
                $sql = "UPDATE performance_monitor_settings SET 
                            sampling_rate = :sampling_rate,
                            enabled_pages = :enabled_pages,
                            alert_threshold = :alert_threshold,
                            data_retention_days = :data_retention_days,
                            notification_email = :notification_email,
                            updated_at = NOW()
                        WHERE id = 1";
                            
                $this->db()->execute($sql, $settings);
            } else {
                // Inserir novas configurações
                $settings['created_at'] = date('Y-m-d H:i:s');
                $settings['updated_at'] = date('Y-m-d H:i:s');
                
                $sql = "INSERT INTO performance_monitor_settings 
                        (sampling_rate, enabled_pages, alert_threshold, data_retention_days, notification_email, created_at, updated_at) 
                        VALUES 
                        (:sampling_rate, :enabled_pages, :alert_threshold, :data_retention_days, :notification_email, :created_at, :updated_at)";
                        
                $this->db()->execute($sql, $settings);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao salvar configurações de monitoramento: " . $e->getMessage());
            return false;
        }
    }
}
?>