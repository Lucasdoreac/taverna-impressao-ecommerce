<?php
/**
 * Script de Monitoramento Periódico
 * 
 * Este script é projetado para ser executado periodicamente via cron job
 * para monitorar métricas de sistema e gerar alertas quando necessário.
 * 
 * Uso:
 * php scripts/run_monitoring.php --full
 * php scripts/run_monitoring.php --critical-only
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Scripts
 * @version    1.0.0
 */

// Definir fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Caminho base da aplicação
define('BASE_PATH', dirname(__DIR__));

// Carregar dependências
require_once BASE_PATH . '/app/lib/Database.php';
require_once BASE_PATH . '/app/lib/Security/InputValidationTrait.php';
require_once BASE_PATH . '/app/lib/Notification/NotificationManager.php';
require_once BASE_PATH . '/app/lib/Notification/NotificationThresholds.php';
require_once BASE_PATH . '/app/lib/Monitoring/ProactiveMonitoringService.php';

/**
 * Classe de Script de Monitoramento
 * 
 * Encapsula a lógica para executar verificações de monitoramento
 */
class MonitoringScript {
    /**
     * Serviço de monitoramento proativo
     * 
     * @var ProactiveMonitoringService
     */
    private $monitoringService;
    
    /**
     * Flag para modo debug
     * 
     * @var bool
     */
    private $debugMode = false;
    
    /**
     * Flag para monitoramento completo
     * 
     * @var bool
     */
    private $fullMonitoring = false;
    
    /**
     * Flag para apenas componentes críticos
     * 
     * @var bool
     */
    private $criticalOnly = false;
    
    /**
     * Timestamp de início da execução
     * 
     * @var int
     */
    private $startTime;
    
    /**
     * Construtor
     */
    public function __construct($options = []) {
        $this->startTime = microtime(true);
        $this->monitoringService = ProactiveMonitoringService::getInstance();
        
        // Processar opções
        if (isset($options['debug'])) {
            $this->debugMode = (bool)$options['debug'];
        }
        
        if (isset($options['full'])) {
            $this->fullMonitoring = (bool)$options['full'];
        }
        
        if (isset($options['critical'])) {
            $this->criticalOnly = (bool)$options['critical'];
        }
        
        // Configurar relatório de erros
        if ($this->debugMode) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
            error_reporting(E_ERROR | E_PARSE);
        }
    }
    
    /**
     * Executa o script de monitoramento
     * 
     * @return bool Sucesso da operação
     */
    public function run() {
        try {
            $this->log('Iniciando script de monitoramento...');
            
            // Coletar métricas do sistema
            $this->collectSystemMetrics();
            
            // Executar tipo apropriado de monitoramento
            if ($this->fullMonitoring) {
                $this->log('Executando monitoramento completo...');
                $result = $this->monitoringService->runProactiveMonitoring();
            } elseif ($this->criticalOnly) {
                $this->log('Executando monitoramento apenas de componentes críticos...');
                $result = $this->monitorCriticalComponents();
            } else {
                $this->log('Executando monitoramento padrão...');
                $result = $this->monitoringService->checkCurrentMetricsAgainstThresholds();
            }
            
            // Agendar próximo ciclo de monitoramento
            $this->monitoringService->scheduleNextMonitoringCycle();
            
            $duration = number_format(microtime(true) - $this->startTime, 2);
            $this->log("Monitoramento concluído em {$duration}s");
            
            return $result;
        } catch (Exception $e) {
            $this->log('ERRO: ' . $e->getMessage(), true);
            return false;
        }
    }
    
    /**
     * Monitora apenas componentes críticos do sistema
     * 
     * @return bool Sucesso da operação
     */
    private function monitorCriticalComponents() {
        try {
            $criticalComponents = [
                'HttpServer',
                'Database',
                'PrintQueue',
                'ReportGenerator'
            ];
            
            $success = true;
            
            foreach ($criticalComponents as $component) {
                $this->log("Monitorando componente crítico: {$component}");
                $result = $this->monitoringService->monitorComponent($component);
                $success = $success && $result;
            }
            
            return $success;
        } catch (Exception $e) {
            $this->log('ERRO ao monitorar componentes críticos: ' . $e->getMessage(), true);
            return false;
        }
    }
    
    /**
     * Coleta métricas do sistema
     * 
     * @return void
     */
    private function collectSystemMetrics() {
        try {
            $this->log('Coletando métricas do sistema...');
            
            // Coletar métricas de servidor
            $serverMetrics = $this->collectServerMetrics();
            $this->recordMetrics('HttpServer', $serverMetrics);
            
            // Coletar métricas de banco de dados
            $dbMetrics = $this->collectDatabaseMetrics();
            $this->recordMetrics('Database', $dbMetrics);
            
            // Coletar métricas de filas
            $queueMetrics = $this->collectQueueMetrics();
            $this->recordMetrics('PrintQueue', $queueMetrics);
            
            // Coletar métricas de caches
            $cacheMetrics = $this->collectCacheMetrics();
            $this->recordMetrics('CacheSystem', $cacheMetrics);
            
            $this->log('Coleta de métricas concluída');
        } catch (Exception $e) {
            $this->log('ERRO ao coletar métricas do sistema: ' . $e->getMessage(), true);
        }
    }
    
    /**
     * Coleta métricas do servidor
     * 
     * @return array Métricas coletadas
     */
    private function collectServerMetrics() {
        $metrics = [];
        
        // Uso de memória PHP
        $metrics['memory_usage'] = memory_get_usage(true) / 1024 / 1024; // MB
        $metrics['memory_peak'] = memory_get_peak_usage(true) / 1024 / 1024; // MB
        
        // Tempo de execução
        $metrics['uptime'] = time() - $_SERVER['REQUEST_TIME'];
        
        // Carga do sistema (apenas em sistemas Unix/Linux)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $metrics['cpu_load_1m'] = $load[0];
            $metrics['cpu_load_5m'] = $load[1];
            $metrics['cpu_load_15m'] = $load[2];
        }
        
        // Número de requisições
        if (function_exists('apache_get_scoreboard')) {
            $sb = apache_get_scoreboard();
            $metrics['active_connections'] = count(array_filter($sb, function($worker) {
                return $worker['status'] === 'W';
            }));
        }
        
        // Espaço em disco
        $metrics['disk_free'] = disk_free_space('/') / 1024 / 1024 / 1024; // GB
        $metrics['disk_total'] = disk_total_space('/') / 1024 / 1024 / 1024; // GB
        $metrics['disk_usage'] = 100 - ($metrics['disk_free'] / $metrics['disk_total'] * 100); // Percentage
        
        return $metrics;
    }
    
    /**
     * Coleta métricas do banco de dados
     * 
     * @return array Métricas coletadas
     */
    private function collectDatabaseMetrics() {
        $metrics = [];
        $db = Database::getInstance();
        
        try {
            // Tempo de consulta
            $startTime = microtime(true);
            $db->fetchSingle("SELECT 1");
            $metrics['query_time'] = microtime(true) - $startTime;
            
            // Conexões ativas
            $result = $db->fetchSingle("SHOW STATUS LIKE 'Threads_connected'");
            if ($result) {
                $metrics['active_connections'] = (int)$result['Value'];
            }
            
            // Consultas lentas
            $result = $db->fetchSingle("SHOW GLOBAL STATUS LIKE 'Slow_queries'");
            if ($result) {
                $metrics['slow_queries'] = (int)$result['Value'];
            }
            
            // Tamanho do cache de consultas
            $result = $db->fetchSingle("SHOW GLOBAL STATUS LIKE 'Qcache_queries_in_cache'");
            if ($result) {
                $metrics['query_cache_size'] = (int)$result['Value'];
            }
            
            // Taxa de acertos do cache de consultas
            $hitsResult = $db->fetchSingle("SHOW GLOBAL STATUS LIKE 'Qcache_hits'");
            $insResult = $db->fetchSingle("SHOW GLOBAL STATUS LIKE 'Qcache_inserts'");
            
            if ($hitsResult && $insResult) {
                $hits = (int)$hitsResult['Value'];
                $inserts = (int)$insResult['Value'];
                $total = $hits + $inserts;
                
                if ($total > 0) {
                    $metrics['query_cache_hit_ratio'] = ($hits / $total) * 100;
                }
            }
        } catch (Exception $e) {
            $this->log('ERRO ao coletar métricas de banco de dados: ' . $e->getMessage(), true);
        }
        
        return $metrics;
    }
    
    /**
     * Coleta métricas da fila de impressão
     * 
     * @return array Métricas coletadas
     */
    private function collectQueueMetrics() {
        $metrics = [];
        $db = Database::getInstance();
        
        try {
            // Tamanho da fila
            $result = $db->fetchSingle("SELECT COUNT(*) as count FROM print_queue WHERE status NOT IN ('completed', 'cancelled')");
            if ($result) {
                $metrics['queue_length'] = (int)$result['count'];
            }
            
            // Itens de alta prioridade
            $result = $db->fetchSingle("SELECT COUNT(*) as count FROM print_queue WHERE priority >= 8 AND status NOT IN ('completed', 'cancelled')");
            if ($result) {
                $metrics['high_priority_items'] = (int)$result['count'];
            }
            
            // Tempo médio na fila
            $result = $db->fetchSingle("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, NOW())) as avg_time FROM print_queue WHERE status NOT IN ('completed', 'cancelled')");
            if ($result && $result['avg_time'] !== null) {
                $metrics['average_queue_time'] = (float)$result['avg_time'];
            }
            
            // Taxa de falhas
            $completedResult = $db->fetchSingle("SELECT COUNT(*) as count FROM print_queue WHERE status = 'completed' AND updated_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $failedResult = $db->fetchSingle("SELECT COUNT(*) as count FROM print_queue WHERE status = 'failed' AND updated_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            
            if ($completedResult && $failedResult) {
                $completed = (int)$completedResult['count'];
                $failed = (int)$failedResult['count'];
                $total = $completed + $failed;
                
                if ($total > 0) {
                    $metrics['failure_rate'] = ($failed / $total) * 100;
                } else {
                    $metrics['failure_rate'] = 0;
                }
            }
        } catch (Exception $e) {
            $this->log('ERRO ao coletar métricas da fila: ' . $e->getMessage(), true);
        }
        
        return $metrics;
    }
    
    /**
     * Coleta métricas do sistema de cache
     * 
     * @return array Métricas coletadas
     */
    private function collectCacheMetrics() {
        $metrics = [];
        
        try {
            // Teste de obter/definir cache com tempo
            $cacheKey = 'monitoring_test_' . uniqid();
            $cacheData = ['test' => true, 'timestamp' => time()];
            
            $startTime = microtime(true);
            apc_store($cacheKey, $cacheData, 300);
            $metrics['cache_write_time'] = microtime(true) - $startTime;
            
            $startTime = microtime(true);
            $retrieved = apc_fetch($cacheKey);
            $metrics['cache_read_time'] = microtime(true) - $startTime;
            
            // Verificar acurácia do cache
            $metrics['cache_accurate'] = ($retrieved && isset($retrieved['timestamp']) && $retrieved['timestamp'] === $cacheData['timestamp']) ? 1 : 0;
            
            // Obter estatísticas do APC
            if (function_exists('apc_cache_info')) {
                $info = apc_cache_info('user');
                
                if ($info) {
                    $metrics['cache_num_entries'] = $info['num_entries'];
                    $metrics['cache_mem_size'] = $info['mem_size'] / 1024 / 1024; // MB
                    $metrics['cache_num_hits'] = $info['num_hits'];
                    $metrics['cache_num_misses'] = $info['num_misses'];
                    
                    $total = $info['num_hits'] + $info['num_misses'];
                    if ($total > 0) {
                        $metrics['cache_hit_ratio'] = ($info['num_hits'] / $total) * 100;
                    }
                }
            }
        } catch (Exception $e) {
            $this->log('ERRO ao coletar métricas de cache: ' . $e->getMessage(), true);
        }
        
        return $metrics;
    }
    
    /**
     * Registra métricas coletadas no sistema
     * 
     * @param string $component Nome do componente
     * @param array $metrics Array de métricas (chave => valor)
     * @return void
     */
    private function recordMetrics($component, $metrics) {
        if (empty($metrics)) {
            return;
        }
        
        $this->log("Registrando " . count($metrics) . " métricas para o componente {$component}");
        
        // Obter o gerenciador de notificações
        $notificationManager = NotificationManager::getInstance();
        
        // Registrar métricas
        $notificationManager->recordPerformanceMetrics($component, $metrics);
    }
    
    /**
     * Registra uma mensagem no log
     * 
     * @param string $message Mensagem a ser registrada
     * @param bool $isError Se é uma mensagem de erro
     * @return void
     */
    private function log($message, $isError = false) {
        $timestamp = date('Y-m-d H:i:s');
        $prefix = $isError ? '[ERRO]' : '[INFO]';
        $logMessage = "[{$timestamp}] {$prefix} {$message}" . PHP_EOL;
        
        if ($this->debugMode) {
            echo $logMessage;
        }
        
        // Registrar no arquivo de log
        $logFile = BASE_PATH . '/logs/monitoring.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

// Processar argumentos da linha de comando
$options = [
    'debug' => false,
    'full' => false,
    'critical' => false
];

// Parse command line arguments
foreach ($argv as $arg) {
    if ($arg === '--debug') {
        $options['debug'] = true;
    } elseif ($arg === '--full') {
        $options['full'] = true;
    } elseif ($arg === '--critical-only') {
        $options['critical'] = true;
    }
}

// Executar o script
$script = new MonitoringScript($options);
$result = $script->run();

// Definir código de saída
exit($result ? 0 : 1);
