<?php
/**
 * NotificationThresholds - Gerenciamento de limiares para alertas de performance
 * 
 * Define e verifica thresholds para métricas de performance,
 * determinando quando alertas devem ser gerados.
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Lib\Notification
 * @version    1.0.0
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Security/InputValidationTrait.php';

class NotificationThresholds {
    use InputValidationTrait;
    
    /**
     * Instância singleton
     * 
     * @var NotificationThresholds
     */
    private static $instance;
    
    /**
     * Conexão com o banco de dados
     * 
     * @var Database
     */
    private $db;
    
    /**
     * Cache de thresholds para evitar consultas repetidas
     * 
     * @var array
     */
    private $thresholdsCache = [];
    
    /**
     * Tempo de expiração do cache em segundos
     * 
     * @var int
     */
    private $cacheExpiration = 300; // 5 minutos
    
    /**
     * Timestamp da última atualização do cache
     * 
     * @var int
     */
    private $lastCacheUpdate = 0;
    
    /**
     * Operadores válidos para comparação de thresholds
     * 
     * @var array
     */
    private static $validOperators = ['>', '<', '>=', '<=', '=='];
    
    /**
     * Categorias de severidade e seus multiplicadores
     * 
     * @var array
     */
    private static $severityCategories = [
        'low' => 1.0,
        'medium' => 1.5,
        'high' => 2.0,
        'critical' => 3.0
    ];
    
    /**
     * Construtor privado (padrão singleton)
     */
    private function __construct() {
        $this->db = Database::getInstance();
        $this->loadThresholds();
    }
    
    /**
     * Obtém a instância do NotificationThresholds
     * 
     * @return NotificationThresholds
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Carrega todos os thresholds do banco de dados para o cache
     * 
     * @return void
     */
    private function loadThresholds() {
        try {
            $currentTime = time();
            
            // Verificar se o cache ainda é válido
            if ($this->lastCacheUpdate > 0 && 
                ($currentTime - $this->lastCacheUpdate) < $this->cacheExpiration &&
                !empty($this->thresholdsCache)) {
                return;
            }
            
            $sql = "SELECT metric, threshold_value, operator, description, created_at, updated_at 
                    FROM performance_thresholds 
                    WHERE active = 1";
            
            $thresholds = $this->db->fetchAll($sql);
            
            if (!$thresholds) {
                // Se não encontrar thresholds, manter o cache atual ou inicializar vazio
                if (empty($this->thresholdsCache)) {
                    $this->thresholdsCache = [];
                }
                return;
            }
            
            // Reconstruir o cache
            $this->thresholdsCache = [];
            
            foreach ($thresholds as $threshold) {
                $this->thresholdsCache[$threshold['metric']] = [
                    'value' => $threshold['threshold_value'],
                    'operator' => $threshold['operator'],
                    'description' => $threshold['description'],
                    'created_at' => $threshold['created_at'],
                    'updated_at' => $threshold['updated_at']
                ];
            }
            
            $this->lastCacheUpdate = $currentTime;
        } catch (Exception $e) {
            error_log('Erro ao carregar thresholds: ' . $e->getMessage());
            
            // Em caso de erro, manter o cache atual (se existir)
            if (empty($this->thresholdsCache)) {
                $this->thresholdsCache = [];
            }
        }
    }
    
    /**
     * Obtém o threshold para uma métrica específica
     * 
     * @param string $metric Nome da métrica
     * @return array|null Dados do threshold ou null se não encontrado
     */
    public function getThresholdForMetric($metric) {
        $this->loadThresholds(); // Garante que o cache está atualizado
        
        $metric = $this->validateString($metric, ['maxLength' => 255]);
        
        if (isset($this->thresholdsCache[$metric])) {
            return $this->thresholdsCache[$metric];
        }
        
        return null;
    }
    
    /**
     * Verifica se um valor excede o threshold para uma métrica
     * 
     * @param string $metric Nome da métrica
     * @param float $value Valor a ser verificado
     * @return bool True se exceder o threshold
     */
    public function isThresholdExceeded($metric, $value) {
        $threshold = $this->getThresholdForMetric($metric);
        
        if (!$threshold) {
            return false; // Sem threshold definido
        }
        
        // Comparar baseado no operador
        switch ($threshold['operator']) {
            case '>':
                return $value > $threshold['value'];
            case '<':
                return $value < $threshold['value'];
            case '>=':
                return $value >= $threshold['value'];
            case '<=':
                return $value <= $threshold['value'];
            case '==':
                return $value == $threshold['value'];
            default:
                return false;
        }
    }
    
    /**
     * Calcula a porcentagem de excesso em relação ao threshold
     * 
     * @param string $metric Nome da métrica
     * @param float $value Valor atual
     * @return float Porcentagem de excesso (0 se não exceder)
     */
    public function calculatePercentExceeded($metric, $value) {
        $threshold = $this->getThresholdForMetric($metric);
        
        if (!$threshold || !$this->isThresholdExceeded($metric, $value)) {
            return 0;
        }
        
        // Cálculo depende do operador
        switch ($threshold['operator']) {
            case '>' || '>=':
                if ($threshold['value'] <= 0) {
                    return ($value > 0) ? 100 : 0;
                }
                return (($value - $threshold['value']) / abs($threshold['value'])) * 100;
                
            case '<' || '<=':
                if ($threshold['value'] <= 0) {
                    return ($value < 0) ? abs(($value - $threshold['value']) / 0.01) : 0;
                }
                return (($threshold['value'] - $value) / $threshold['value']) * 100;
                
            case '==':
                return ($value != $threshold['value']) ? 100 : 0;
                
            default:
                return 0;
        }
    }
    
    /**
     * Determina a severidade de um alerta com base na diferença percentual
     * 
     * @param string $metric Nome da métrica
     * @param float $value Valor atual
     * @return string Nível de severidade (low, medium, high, critical)
     */
    public function determineSeverity($metric, $value) {
        $percentExceeded = $this->calculatePercentExceeded($metric, $value);
        
        if ($percentExceeded <= 10) {
            return 'low';
        } elseif ($percentExceeded <= 50) {
            return 'medium';
        } elseif ($percentExceeded <= 100) {
            return 'high';
        } else {
            return 'critical';
        }
    }
    
    /**
     * Verifica se uma métrica deve gerar alerta com base no valor atual
     * 
     * @param string $metric Nome da métrica
     * @param float $value Valor atual
     * @return bool True se deve alertar
     */
    public function shouldAlert($metric, $value) {
        // Verificar se existe um threshold para a métrica
        if (!$this->getThresholdForMetric($metric)) {
            return false;
        }
        
        // Verificar se excede o threshold
        return $this->isThresholdExceeded($metric, $value);
    }
    
    /**
     * Atualiza ou cria um threshold para uma métrica
     * 
     * @param string $metric Nome da métrica
     * @param float $threshold Valor do threshold
     * @param string $operator Operador de comparação
     * @param string $description Descrição opcional do threshold
     * @return bool Sucesso da operação
     */
    public function updateThreshold($metric, $threshold, $operator = '>', $description = '') {
        try {
            $metric = $this->validateString($metric, ['maxLength' => 255, 'required' => true]);
            $description = $this->validateString($description, ['maxLength' => 1000]);
            
            // Validar operador
            if (!in_array($operator, self::$validOperators)) {
                $operator = '>';
            }
            
            // Verificar se o threshold já existe
            $sql = "SELECT id FROM performance_thresholds WHERE metric = :metric";
            $exists = $this->db->fetchSingle($sql, [':metric' => $metric]);
            
            if ($exists) {
                // Atualizar existente
                $sql = "UPDATE performance_thresholds 
                        SET threshold_value = :threshold,
                            operator = :operator,
                            description = :description,
                            updated_at = NOW()
                        WHERE metric = :metric";
            } else {
                // Criar novo
                $sql = "INSERT INTO performance_thresholds 
                        (metric, threshold_value, operator, description, active, created_at, updated_at) 
                        VALUES 
                        (:metric, :threshold, :operator, :description, 1, NOW(), NOW())";
            }
            
            $params = [
                ':metric' => $metric,
                ':threshold' => $threshold,
                ':operator' => $operator,
                ':description' => $description
            ];
            
            $result = $this->db->execute($sql, $params);
            
            if ($result) {
                // Atualizar o cache
                $this->thresholdsCache[$metric] = [
                    'value' => $threshold,
                    'operator' => $operator,
                    'description' => $description,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                if (!$exists) {
                    $this->thresholdsCache[$metric]['created_at'] = $this->thresholdsCache[$metric]['updated_at'];
                }
            }
            
            return $result !== false;
        } catch (Exception $e) {
            error_log('Erro ao atualizar threshold: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Desativa um threshold para uma métrica específica
     * 
     * @param string $metric Nome da métrica
     * @return bool Sucesso da operação
     */
    public function disableThreshold($metric) {
        try {
            $metric = $this->validateString($metric, ['maxLength' => 255, 'required' => true]);
            
            $sql = "UPDATE performance_thresholds 
                    SET active = 0, updated_at = NOW() 
                    WHERE metric = :metric";
            
            $params = [':metric' => $metric];
            
            $result = $this->db->execute($sql, $params);
            
            if ($result) {
                // Remover do cache
                if (isset($this->thresholdsCache[$metric])) {
                    unset($this->thresholdsCache[$metric]);
                }
            }
            
            return $result !== false;
        } catch (Exception $e) {
            error_log('Erro ao desativar threshold: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém todos os thresholds ativos
     * 
     * @return array Lista de thresholds
     */
    public function getAllThresholds() {
        $this->loadThresholds(); // Garante que o cache está atualizado
        return $this->thresholdsCache;
    }
    
    /**
     * Retorna as categorias de severidade disponíveis
     * 
     * @return array Categorias de severidade
     */
    public static function getSeverityCategories() {
        return array_keys(self::$severityCategories);
    }
    
    /**
     * Define thresholds padrão para métricas comuns
     * 
     * @return bool Sucesso da operação
     */
    public function setDefaultThresholds() {
        try {
            $defaults = [
                'response_time' => [
                    'value' => 1.5, // segundos
                    'operator' => '>',
                    'description' => 'Tempo máximo de resposta em segundos'
                ],
                'memory_usage' => [
                    'value' => 128, // MB
                    'operator' => '>',
                    'description' => 'Uso máximo de memória em MB'
                ],
                'cpu_usage' => [
                    'value' => 85, // porcentagem
                    'operator' => '>',
                    'description' => 'Uso máximo de CPU em porcentagem'
                ],
                'query_time' => [
                    'value' => 0.5, // segundos
                    'operator' => '>',
                    'description' => 'Tempo máximo de execução de consulta em segundos'
                ],
                'cache_hit_ratio' => [
                    'value' => 60, // porcentagem
                    'operator' => '<',
                    'description' => 'Taxa mínima de acertos de cache em porcentagem'
                ],
                'error_rate' => [
                    'value' => 1, // porcentagem
                    'operator' => '>',
                    'description' => 'Taxa máxima de erros em porcentagem'
                ],
                'concurrent_users' => [
                    'value' => 50, // usuários
                    'operator' => '>',
                    'description' => 'Número máximo de usuários concorrentes'
                ],
                'disk_usage' => [
                    'value' => 90, // porcentagem
                    'operator' => '>',
                    'description' => 'Uso máximo de disco em porcentagem'
                ]
            ];
            
            $success = true;
            
            foreach ($defaults as $metric => $config) {
                $result = $this->updateThreshold(
                    $metric,
                    $config['value'],
                    $config['operator'],
                    $config['description']
                );
                
                $success = $success && $result;
            }
            
            return $success;
        } catch (Exception $e) {
            error_log('Erro ao definir thresholds padrão: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica e atualiza automaticamente thresholds com base em dados históricos
     * 
     * @param string $metric Nome da métrica
     * @param int $days Número de dias para análise
     * @param float $stdDevFactor Fator de desvio padrão (ex: 2.0 para 2 desvios padrão)
     * @return bool Sucesso da operação
     */
    public function autoAdjustThreshold($metric, $days = 30, $stdDevFactor = 2.0) {
        try {
            $metric = $this->validateString($metric, ['maxLength' => 255, 'required' => true]);
            $days = max(7, min(90, (int)$days));
            $stdDevFactor = max(1.0, min(5.0, (float)$stdDevFactor));
            
            // Obter dados históricos da métrica
            $sql = "SELECT metric_value 
                    FROM performance_metrics 
                    WHERE metric_name = :metric 
                    AND timestamp > DATE_SUB(NOW(), INTERVAL :days DAY)";
            
            $params = [
                ':metric' => $metric,
                ':days' => $days
            ];
            
            $metrics = $this->db->fetchAll($sql, $params);
            
            if (empty($metrics)) {
                error_log("Sem dados históricos suficientes para ajustar threshold para {$metric}");
                return false;
            }
            
            // Calcular média e desvio padrão
            $values = array_column($metrics, 'metric_value');
            $count = count($values);
            $sum = array_sum($values);
            $mean = $sum / $count;
            
            // Calcular desvio padrão
            $variance = 0;
            foreach ($values as $value) {
                $variance += pow($value - $mean, 2);
            }
            $variance /= $count;
            $stdDev = sqrt($variance);
            
            // Determinar o threshold baseado na média e desvio padrão
            $threshold = $mean + ($stdDev * $stdDevFactor);
            
            // Obter o threshold atual para determinar o operador correto
            $currentThreshold = $this->getThresholdForMetric($metric);
            $operator = ($currentThreshold) ? $currentThreshold['operator'] : '>';
            
            // Se o operador for < ou <=, ajustar o cálculo
            if ($operator === '<' || $operator === '<=') {
                $threshold = $mean - ($stdDev * $stdDevFactor);
            }
            
            // Aplicar o novo threshold
            return $this->updateThreshold(
                $metric,
                $threshold,
                $operator,
                "Auto-ajustado com base em {$days} dias de dados históricos (média: {$mean}, desvio: {$stdDev})"
            );
        } catch (Exception $e) {
            error_log('Erro ao auto-ajustar threshold: ' . $e->getMessage());
            return false;
        }
    }
}
