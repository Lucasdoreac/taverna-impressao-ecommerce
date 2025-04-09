<?php
/**
 * PerformanceMonitor - Classe para monitoramento de desempenho
 * 
 * Fornece ferramentas para medir, registrar e analisar o desempenho
 * de componentes críticos do sistema, incluindo tempo de execução,
 * uso de memória e outras métricas relevantes.
 * 
 * @package App\Lib\Performance
 * @version 1.0.0
 * @author Taverna da Impressão 3D
 */
class PerformanceMonitor {
    /**
     * Diretório onde os logs de desempenho são armazenados
     * @var string
     */
    private $logDirectory;
    
    /**
     * Nome do arquivo de log atual
     * @var string
     */
    private $logFile;
    
    /**
     * Formato do timestamp para logs
     * @var string
     */
    private $timestampFormat = 'Y-m-d H:i:s';
    
    /**
     * Marcadores de tempo para medições
     * @var array
     */
    private $timeMarkers = [];
    
    /**
     * Medições de memória
     * @var array
     */
    private $memoryMarkers = [];
    
    /**
     * Indicador de última medição iniciada
     * @var string
     */
    private $lastMeasurement = null;
    
    /**
     * Medições ativas
     * @var array
     */
    private $activeMeasurements = [];
    
    /**
     * Histórico de medições concluídas
     * @var array
     */
    private $completedMeasurements = [];
    
    /**
     * Limites de alerta para métricas
     * @var array
     */
    private $thresholds = [];
    
    /**
     * Construtor
     * 
     * @param string $logDirectory Diretório para armazenar logs de desempenho
     */
    public function __construct($logDirectory = null) {
        // Se não for fornecido, usar diretório padrão
        if ($logDirectory === null) {
            $logDirectory = $_SERVER['DOCUMENT_ROOT'] . '/../logs/performance/';
        }
        
        $this->logDirectory = rtrim($logDirectory, '/\\') . '/';
        
        // Garantir que o diretório exista
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0755, true);
        }
        
        // Definir arquivo de log padrão
        $this->logFile = 'performance_' . date('Y-m-d') . '.log';
        
        // Inicializar thresholds padrão
        $this->initializeDefaultThresholds();
    }
    
    /**
     * Inicializa os limites de alerta padrão para várias métricas
     */
    private function initializeDefaultThresholds() {
        $this->thresholds = [
            'execution_time' => [
                'warning' => 1.0,    // Alerta quando execução > 1 segundo
                'critical' => 3.0    // Crítico quando execução > 3 segundos
            ],
            'memory_usage' => [
                'warning' => 10 * 1024 * 1024,    // Alerta quando uso > 10MB
                'critical' => 50 * 1024 * 1024    // Crítico quando uso > 50MB
            ],
            'db_query_time' => [
                'warning' => 0.5,    // Alerta quando consulta > 0.5 segundos
                'critical' => 2.0    // Crítico quando consulta > 2 segundos
            ],
            'response_time' => [
                'warning' => 2.0,    // Alerta quando resposta > 2 segundos
                'critical' => 5.0    // Crítico quando resposta > 5 segundos
            ]
        ];
    }
    
    /**
     * Define um novo limite de alerta para uma métrica
     * 
     * @param string $metric Nome da métrica
     * @param string $level Nível do limite (warning, critical)
     * @param float $value Valor do limite
     * @return PerformanceMonitor Instância para encadeamento
     */
    public function setThreshold($metric, $level, $value) {
        if (!isset($this->thresholds[$metric])) {
            $this->thresholds[$metric] = [];
        }
        
        $this->thresholds[$metric][$level] = $value;
        return $this;
    }
    
    /**
     * Obtém o valor de um limite de alerta
     * 
     * @param string $metric Nome da métrica
     * @param string $level Nível do limite (warning, critical)
     * @return float|null Valor do limite ou null se não existir
     */
    public function getThreshold($metric, $level) {
        return isset($this->thresholds[$metric][$level]) ? $this->thresholds[$metric][$level] : null;
    }
    
    /**
     * Inicia uma medição de desempenho
     * 
     * @param string $name Nome identificador da medição
     * @param array $context Informações adicionais sobre o contexto da medição
     * @return string ID único da medição
     */
    public function startMeasurement($name, array $context = []) {
        $id = uniqid($name . '_', true);
        
        $this->timeMarkers[$id] = microtime(true);
        $this->memoryMarkers[$id] = memory_get_usage(true);
        
        $this->activeMeasurements[$id] = [
            'id' => $id,
            'name' => $name,
            'context' => $context,
            'start_time' => $this->timeMarkers[$id],
            'start_memory' => $this->memoryMarkers[$id],
            'formatted_start_time' => date($this->timestampFormat),
            'checkpoint_count' => 0,
            'checkpoints' => []
        ];
        
        $this->lastMeasurement = $id;
        
        return $id;
    }
    
    /**
     * Adiciona um checkpoint em uma medição em andamento
     * 
     * @param string $id ID da medição
     * @param string $name Nome do checkpoint
     * @param array $context Informações adicionais sobre o checkpoint
     * @return PerformanceMonitor Instância para encadeamento
     */
    public function addCheckpoint($id = null, $name = null, array $context = []) {
        // Se ID não fornecido, usar última medição
        if ($id === null) {
            $id = $this->lastMeasurement;
        }
        
        // Verificar se medição existe
        if (!isset($this->activeMeasurements[$id])) {
            return $this;
        }
        
        // Se nome não fornecido, gerar nome baseado em contador
        if ($name === null) {
            $name = 'checkpoint_' . (++$this->activeMeasurements[$id]['checkpoint_count']);
        }
        
        $timestamp = microtime(true);
        $memory = memory_get_usage(true);
        
        // Calcular tempos e uso de memória desde o início e desde o último checkpoint
        $timeFromStart = $timestamp - $this->activeMeasurements[$id]['start_time'];
        $memoryDiff = $memory - $this->activeMeasurements[$id]['start_memory'];
        
        $lastCheckpoint = end($this->activeMeasurements[$id]['checkpoints']);
        $timeFromLastCheckpoint = $lastCheckpoint ? $timestamp - $lastCheckpoint['timestamp'] : $timeFromStart;
        
        // Adicionar checkpoint
        $this->activeMeasurements[$id]['checkpoints'][] = [
            'name' => $name,
            'context' => $context,
            'timestamp' => $timestamp,
            'memory' => $memory,
            'time_from_start' => $timeFromStart,
            'time_from_last' => $timeFromLastCheckpoint,
            'memory_diff' => $memoryDiff
        ];
        
        return $this;
    }
    
    /**
     * Finaliza uma medição de desempenho
     * 
     * @param string $id ID da medição a ser finalizada
     * @param array $extraMetrics Métricas adicionais a serem registradas
     * @return array Resultados detalhados da medição
     */
    public function endMeasurement($id = null, array $extraMetrics = []) {
        // Se ID não fornecido, usar última medição
        if ($id === null) {
            $id = $this->lastMeasurement;
        }
        
        // Verificar se medição existe
        if (!isset($this->activeMeasurements[$id])) {
            return null;
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        // Calcular tempo total e uso de memória
        $startTime = $this->activeMeasurements[$id]['start_time'];
        $startMemory = $this->activeMeasurements[$id]['start_memory'];
        
        $totalTime = $endTime - $startTime;
        $totalMemory = $endMemory - $startMemory;
        $peakMemory = memory_get_peak_usage(true) - $startMemory;
        
        // Finalizar medição
        $measurement = $this->activeMeasurements[$id];
        $measurement['end_time'] = $endTime;
        $measurement['end_memory'] = $endMemory;
        $measurement['total_time'] = $totalTime;
        $measurement['total_memory'] = $totalMemory;
        $measurement['peak_memory'] = $peakMemory;
        $measurement['formatted_end_time'] = date($this->timestampFormat);
        
        // Adicionar métricas extras
        $measurement['extra_metrics'] = $extraMetrics;
        
        // Verificar thresholds
        $alerts = [];
        
        // Verificar tempo de execução
        if (isset($this->thresholds['execution_time'])) {
            if ($totalTime >= $this->thresholds['execution_time']['critical']) {
                $alerts[] = [
                    'metric' => 'execution_time',
                    'level' => 'critical',
                    'value' => $totalTime,
                    'threshold' => $this->thresholds['execution_time']['critical'],
                    'message' => "Tempo de execução crítico: {$totalTime}s (limite: {$this->thresholds['execution_time']['critical']}s)"
                ];
            } else if ($totalTime >= $this->thresholds['execution_time']['warning']) {
                $alerts[] = [
                    'metric' => 'execution_time',
                    'level' => 'warning',
                    'value' => $totalTime,
                    'threshold' => $this->thresholds['execution_time']['warning'],
                    'message' => "Tempo de execução alto: {$totalTime}s (limite: {$this->thresholds['execution_time']['warning']}s)"
                ];
            }
        }
        
        // Verificar uso de memória
        if (isset($this->thresholds['memory_usage'])) {
            if ($peakMemory >= $this->thresholds['memory_usage']['critical']) {
                $alerts[] = [
                    'metric' => 'memory_usage',
                    'level' => 'critical',
                    'value' => $peakMemory,
                    'threshold' => $this->thresholds['memory_usage']['critical'],
                    'message' => "Uso de memória crítico: " . $this->formatBytes($peakMemory) . " (limite: " . $this->formatBytes($this->thresholds['memory_usage']['critical']) . ")"
                ];
            } else if ($peakMemory >= $this->thresholds['memory_usage']['warning']) {
                $alerts[] = [
                    'metric' => 'memory_usage',
                    'level' => 'warning',
                    'value' => $peakMemory,
                    'threshold' => $this->thresholds['memory_usage']['warning'],
                    'message' => "Uso de memória alto: " . $this->formatBytes($peakMemory) . " (limite: " . $this->formatBytes($this->thresholds['memory_usage']['warning']) . ")"
                ];
            }
        }
        
        // Verificar métricas extras
        foreach ($extraMetrics as $metric => $value) {
            if (isset($this->thresholds[$metric])) {
                if ($value >= $this->thresholds[$metric]['critical']) {
                    $alerts[] = [
                        'metric' => $metric,
                        'level' => 'critical',
                        'value' => $value,
                        'threshold' => $this->thresholds[$metric]['critical'],
                        'message' => "Métrica {$metric} crítica: {$value} (limite: {$this->thresholds[$metric]['critical']})"
                    ];
                } else if ($value >= $this->thresholds[$metric]['warning']) {
                    $alerts[] = [
                        'metric' => $metric,
                        'level' => 'warning',
                        'value' => $value,
                        'threshold' => $this->thresholds[$metric]['warning'],
                        'message' => "Métrica {$metric} alta: {$value} (limite: {$this->thresholds[$metric]['warning']})"
                    ];
                }
            }
        }
        
        $measurement['alerts'] = $alerts;
        
        // Registrar resultado
        $this->completedMeasurements[$id] = $measurement;
        unset($this->activeMeasurements[$id]);
        unset($this->timeMarkers[$id]);
        unset($this->memoryMarkers[$id]);
        
        // Se esta foi a última medição, atualizar
        if ($this->lastMeasurement === $id) {
            $this->lastMeasurement = null;
        }
        
        // Registrar no log se houver alertas
        if (!empty($alerts)) {
            $this->logAlerts($measurement['name'], $alerts, $measurement['context']);
        }
        
        return $measurement;
    }
    
    /**
     * Registra alertas no log
     * 
     * @param string $name Nome da medição
     * @param array $alerts Lista de alertas
     * @param array $context Contexto da medição
     */
    private function logAlerts($name, array $alerts, array $context) {
        $logEntry = date($this->timestampFormat) . " | MEDIÇÃO: {$name}\n";
        
        // Adicionar informações de contexto
        if (!empty($context)) {
            $logEntry .= "  Contexto: " . json_encode($context) . "\n";
        }
        
        // Adicionar cada alerta
        foreach ($alerts as $alert) {
            $logEntry .= "  [" . strtoupper($alert['level']) . "] " . $alert['message'] . "\n";
        }
        
        $logEntry .= "---------------------------------------------\n";
        
        // Escrever no arquivo de log
        $this->writeToLog($logEntry);
    }
    
    /**
     * Escreve uma entrada no log de desempenho
     * 
     * @param string $entry Texto a ser registrado
     * @return bool Sucesso da operação
     */
    private function writeToLog($entry) {
        $logPath = $this->logDirectory . $this->logFile;
        return file_put_contents($logPath, $entry, FILE_APPEND) !== false;
    }
    
    /**
     * Gera uma medição rápida (one-shot) de um trecho de código
     * 
     * @param callable $callback Função a ser medida
     * @param string $name Nome da medição
     * @param array $context Contexto da medição
     * @return array Resultado da medição e valor retornado pela função
     */
    public function measure($callback, $name = 'quick_measure', array $context = []) {
        $id = $this->startMeasurement($name, $context);
        
        $returnValue = null;
        $exception = null;
        
        try {
            $returnValue = $callback();
        } catch (\Exception $e) {
            $exception = $e;
        }
        
        $measurement = $this->endMeasurement($id);
        
        return [
            'measurement' => $measurement,
            'return_value' => $returnValue,
            'exception' => $exception
        ];
    }
    
    /**
     * Obtém uma lista de todas as medições concluídas
     * 
     * @param bool $reset Se true, limpa o histórico após retornar
     * @return array Lista de medições concluídas
     */
    public function getCompletedMeasurements($reset = false) {
        $measurements = $this->completedMeasurements;
        
        if ($reset) {
            $this->completedMeasurements = [];
        }
        
        return $measurements;
    }
    
    /**
     * Obtém uma lista de medições ativas
     * 
     * @return array Lista de medições ativas
     */
    public function getActiveMeasurements() {
        return $this->activeMeasurements;
    }
    
    /**
     * Calcula estatísticas agregadas das medições concluídas
     * 
     * @param string $filter Filtro opcional por nome de medição
     * @return array Estatísticas calculadas
     */
    public function calculateStatistics($filter = null) {
        $stats = [
            'count' => 0,
            'total_time' => 0,
            'avg_time' => 0,
            'min_time' => PHP_FLOAT_MAX,
            'max_time' => 0,
            'total_memory' => 0,
            'avg_memory' => 0,
            'min_memory' => PHP_INT_MAX,
            'max_memory' => 0,
            'alerts' => [
                'warning' => 0,
                'critical' => 0
            ],
            'measurements_by_name' => []
        ];
        
        foreach ($this->completedMeasurements as $measurement) {
            // Aplicar filtro se fornecido
            if ($filter !== null && $measurement['name'] !== $filter) {
                continue;
            }
            
            $stats['count']++;
            $stats['total_time'] += $measurement['total_time'];
            $stats['min_time'] = min($stats['min_time'], $measurement['total_time']);
            $stats['max_time'] = max($stats['max_time'], $measurement['total_time']);
            
            $stats['total_memory'] += $measurement['peak_memory'];
            $stats['min_memory'] = min($stats['min_memory'], $measurement['peak_memory']);
            $stats['max_memory'] = max($stats['max_memory'], $measurement['peak_memory']);
            
            // Contabilizar alertas
            foreach ($measurement['alerts'] as $alert) {
                $stats['alerts'][$alert['level']]++;
            }
            
            // Agrupar por nome
            $name = $measurement['name'];
            if (!isset($stats['measurements_by_name'][$name])) {
                $stats['measurements_by_name'][$name] = [
                    'count' => 0,
                    'total_time' => 0,
                    'avg_time' => 0,
                    'total_memory' => 0,
                    'avg_memory' => 0
                ];
            }
            
            $stats['measurements_by_name'][$name]['count']++;
            $stats['measurements_by_name'][$name]['total_time'] += $measurement['total_time'];
            $stats['measurements_by_name'][$name]['total_memory'] += $measurement['peak_memory'];
        }
        
        // Calcular médias
        if ($stats['count'] > 0) {
            $stats['avg_time'] = $stats['total_time'] / $stats['count'];
            $stats['avg_memory'] = $stats['total_memory'] / $stats['count'];
            
            // Calcular médias por nome
            foreach ($stats['measurements_by_name'] as $name => $nameStats) {
                if ($nameStats['count'] > 0) {
                    $stats['measurements_by_name'][$name]['avg_time'] = $nameStats['total_time'] / $nameStats['count'];
                    $stats['measurements_by_name'][$name]['avg_memory'] = $nameStats['total_memory'] / $nameStats['count'];
                }
            }
        }
        
        // Se não houve medições, ajustar valores de min
        if ($stats['count'] === 0) {
            $stats['min_time'] = 0;
            $stats['min_memory'] = 0;
        }
        
        return $stats;
    }
    
    /**
     * Formata um valor de bytes para uma string legível
     * 
     * @param int $bytes Número de bytes
     * @return string Valor formatado
     */
    public function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Gera um relatório de desempenho baseado nas medições concluídas
     * 
     * @param string $format Formato do relatório (html, json, text)
     * @param string $filter Filtro opcional por nome de medição
     * @return string Relatório formatado
     */
    public function generateReport($format = 'text', $filter = null) {
        $stats = $this->calculateStatistics($filter);
        
        switch ($format) {
            case 'json':
                return json_encode($stats, JSON_PRETTY_PRINT);
                
            case 'html':
                return $this->generateHtmlReport($stats);
                
            case 'text':
            default:
                return $this->generateTextReport($stats);
        }
    }
    
    /**
     * Gera um relatório em texto simples
     * 
     * @param array $stats Estatísticas calculadas
     * @return string Relatório em texto
     */
    private function generateTextReport($stats) {
        $report = "RELATÓRIO DE DESEMPENHO\n";
        $report .= "=====================\n\n";
        
        $report .= "Medições totais: {$stats['count']}\n";
        $report .= "Tempo total: " . number_format($stats['total_time'], 4) . " segundos\n";
        $report .= "Tempo médio: " . number_format($stats['avg_time'], 4) . " segundos\n";
        $report .= "Tempo mínimo: " . number_format($stats['min_time'], 4) . " segundos\n";
        $report .= "Tempo máximo: " . number_format($stats['max_time'], 4) . " segundos\n\n";
        
        $report .= "Memória total: " . $this->formatBytes($stats['total_memory']) . "\n";
        $report .= "Memória média: " . $this->formatBytes($stats['avg_memory']) . "\n";
        $report .= "Memória mínima: " . $this->formatBytes($stats['min_memory']) . "\n";
        $report .= "Memória máxima: " . $this->formatBytes($stats['max_memory']) . "\n\n";
        
        $report .= "Alertas: {$stats['alerts']['warning']} avisos, {$stats['alerts']['critical']} críticos\n\n";
        
        $report .= "DETALHES POR TIPO DE MEDIÇÃO\n";
        $report .= "===========================\n\n";
        
        foreach ($stats['measurements_by_name'] as $name => $nameStats) {
            $report .= "Medição: {$name}\n";
            $report .= "  Quantidade: {$nameStats['count']}\n";
            $report .= "  Tempo médio: " . number_format($nameStats['avg_time'], 4) . " segundos\n";
            $report .= "  Memória média: " . $this->formatBytes($nameStats['avg_memory']) . "\n\n";
        }
        
        return $report;
    }
    
    /**
     * Gera um relatório em HTML
     * 
     * @param array $stats Estatísticas calculadas
     * @return string Relatório em HTML
     */
    private function generateHtmlReport($stats) {
        $html = '<div class="performance-report">';
        $html .= '<h2>Relatório de Desempenho</h2>';
        
        $html .= '<div class="summary">';
        $html .= '<h3>Resumo</h3>';
        $html .= '<ul>';
        $html .= '<li><strong>Medições totais:</strong> ' . $stats['count'] . '</li>';
        $html .= '<li><strong>Tempo total:</strong> ' . number_format($stats['total_time'], 4) . ' segundos</li>';
        $html .= '<li><strong>Tempo médio:</strong> ' . number_format($stats['avg_time'], 4) . ' segundos</li>';
        $html .= '<li><strong>Tempo mínimo:</strong> ' . number_format($stats['min_time'], 4) . ' segundos</li>';
        $html .= '<li><strong>Tempo máximo:</strong> ' . number_format($stats['max_time'], 4) . ' segundos</li>';
        $html .= '<li><strong>Memória total:</strong> ' . $this->formatBytes($stats['total_memory']) . '</li>';
        $html .= '<li><strong>Memória média:</strong> ' . $this->formatBytes($stats['avg_memory']) . '</li>';
        $html .= '<li><strong>Alertas:</strong> ' . $stats['alerts']['warning'] . ' avisos, ' . $stats['alerts']['critical'] . ' críticos</li>';
        $html .= '</ul>';
        $html .= '</div>';
        
        $html .= '<div class="details">';
        $html .= '<h3>Detalhes por Tipo de Medição</h3>';
        
        $html .= '<table border="1" cellspacing="0" cellpadding="5">';
        $html .= '<tr><th>Medição</th><th>Quantidade</th><th>Tempo Médio (s)</th><th>Memória Média</th></tr>';
        
        foreach ($stats['measurements_by_name'] as $name => $nameStats) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($name) . '</td>';
            $html .= '<td>' . $nameStats['count'] . '</td>';
            $html .= '<td>' . number_format($nameStats['avg_time'], 4) . '</td>';
            $html .= '<td>' . $this->formatBytes($nameStats['avg_memory']) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
}
