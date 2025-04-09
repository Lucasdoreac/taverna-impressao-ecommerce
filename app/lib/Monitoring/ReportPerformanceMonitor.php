<?php
namespace App\Lib\Monitoring;

use App\Lib\Cache\AdaptiveCacheManager;

/**
 * ReportPerformanceMonitor
 * 
 * Serviço de monitoramento de performance para relatórios que registra métricas
 * detalhadas sobre tempo de execução, uso de memória, tamanho dos resultados e
 * eficiência de cache para otimizações futuras.
 * 
 * @package App\Lib\Monitoring
 * @version 1.0.0
 */
class ReportPerformanceMonitor
{
    /**
     * @var AdaptiveCacheManager Gerenciador de cache adaptativo
     */
    private $cacheManager;
    
    /**
     * @var array Registros de métricas de desempenho
     */
    private $metricsLog = [];
    
    /**
     * @var array Relatórios em execução no momento
     */
    private $activeReports = [];
    
    /**
     * @var string Caminho para o arquivo de log de métricas
     */
    private $metricsLogPath;
    
    /**
     * @var int Número máximo de entradas de log a manter em memória
     */
    private $maxLogEntries = 1000;
    
    /**
     * Construtor
     * 
     * @param AdaptiveCacheManager $cacheManager Gerenciador de cache adaptativo
     */
    public function __construct(AdaptiveCacheManager $cacheManager = null)
    {
        $this->cacheManager = $cacheManager;
        $this->metricsLogPath = dirname(__DIR__, 3) . '/logs/report_performance.json';
        
        // Inicializar log de métricas
        $this->loadMetricsLog();
    }
    
    /**
     * Inicia o monitoramento de um relatório
     * 
     * @param string $reportType Tipo de relatório
     * @param string $reportName Nome do relatório
     * @param array $parameters Parâmetros do relatório
     * @param string $cacheKey Chave de cache (opcional)
     * @return string ID de rastreamento
     */
    public function startReportExecution(string $reportType, string $reportName, array $parameters = [], string $cacheKey = null): string
    {
        // Gerar ID único para esta execução
        $trackingId = uniqid('report_', true);
        
        // Registrar início da execução
        $this->activeReports[$trackingId] = [
            'tracking_id' => $trackingId,
            'report_type' => $reportType,
            'report_name' => $reportName,
            'parameters' => $parameters,
            'cache_key' => $cacheKey,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'cache_hit' => false,
            'timestamp' => date('Y-m-d H:i:s'),
            'finished' => false
        ];
        
        return $trackingId;
    }
    
    /**
     * Registra um hit de cache para um relatório
     * 
     * @param string $trackingId ID de rastreamento
     * @param float $cacheRetrievalTimeMs Tempo de recuperação do cache em ms
     * @return bool Sucesso da operação
     */
    public function registerCacheHit(string $trackingId, float $cacheRetrievalTimeMs): bool
    {
        if (!isset($this->activeReports[$trackingId])) {
            return false;
        }
        
        $this->activeReports[$trackingId]['cache_hit'] = true;
        $this->activeReports[$trackingId]['cache_retrieval_time_ms'] = $cacheRetrievalTimeMs;
        
        // Finalizar e registrar métricas
        return $this->finishReportExecution($trackingId, null, 0);
    }
    
    /**
     * Finaliza o monitoramento de um relatório
     * 
     * @param string $trackingId ID de rastreamento
     * @param mixed $result Resultado do relatório
     * @param int $resultSize Tamanho aproximado do resultado em bytes (0 para calcular)
     * @return bool Sucesso da operação
     */
    public function finishReportExecution(string $trackingId, $result = null, int $resultSize = 0): bool
    {
        if (!isset($this->activeReports[$trackingId])) {
            return false;
        }
        
        // Evitar finalização duplicada
        if ($this->activeReports[$trackingId]['finished']) {
            return true;
        }
        
        // Calcular métricas
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $metrics = $this->activeReports[$trackingId];
        $metrics['end_time'] = $endTime;
        $metrics['end_memory'] = $endMemory;
        $metrics['execution_time_ms'] = round(($endTime - $metrics['start_time']) * 1000, 2);
        $metrics['memory_usage_bytes'] = $endMemory - $metrics['start_memory'];
        $metrics['memory_peak_bytes'] = memory_get_peak_usage(true);
        $metrics['finished'] = true;
        
        // Calcular tamanho do resultado, se fornecido
        if ($result !== null && $resultSize === 0) {
            $tempJson = json_encode($result);
            if ($tempJson !== false) {
                $metrics['result_size_bytes'] = strlen($tempJson);
            }
        } elseif ($resultSize > 0) {
            $metrics['result_size_bytes'] = $resultSize;
        }
        
        // Atualizar cache de relatório ativo
        $this->activeReports[$trackingId] = $metrics;
        
        // Adicionar ao log de métricas
        $this->addToMetricsLog($metrics);
        
        // Informar o gerenciador de cache adaptativo
        if ($this->cacheManager !== null && isset($metrics['report_type'])) {
            $this->cacheManager->logResponseTime(
                $metrics['report_type'],
                $metrics['execution_time_ms'],
                $metrics['cache_key'] ?? null,
                $metrics['cache_hit']
            );
        }
        
        return true;
    }
    
    /**
     * Adiciona métricas ao log
     * 
     * @param array $metrics Métricas a serem adicionadas
     */
    private function addToMetricsLog(array $metrics): void
    {
        // Adicionar ao início do array para priorizar entradas mais recentes
        array_unshift($this->metricsLog, $metrics);
        
        // Limitar tamanho do log
        if (count($this->metricsLog) > $this->maxLogEntries) {
            $this->metricsLog = array_slice($this->metricsLog, 0, $this->maxLogEntries);
        }
        
        // Salvar periodicamente
        if (count($this->metricsLog) % 10 === 0) {
            $this->saveMetricsLog();
        }
    }
    
    /**
     * Carrega o log de métricas do armazenamento persistente
     */
    private function loadMetricsLog(): void
    {
        if (file_exists($this->metricsLogPath)) {
            $content = file_get_contents($this->metricsLogPath);
            $data = json_decode($content, true);
            
            if (is_array($data)) {
                $this->metricsLog = $data;
            }
        }
    }
    
    /**
     * Salva o log de métricas em armazenamento persistente
     * 
     * @return bool Sucesso da operação
     */
    public function saveMetricsLog(): bool
    {
        $logDir = dirname($this->metricsLogPath);
        
        // Garantir que o diretório existe
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true)) {
                return false;
            }
        }
        
        // Salvar dados
        return file_put_contents($this->metricsLogPath, json_encode($this->metricsLog)) !== false;
    }
    
    /**
     * Obtém estatísticas de performance para relatórios
     * 
     * @param string $reportType Tipo de relatório específico (opcional)
     * @param int $timeWindow Janela de tempo em segundos (0 = todos)
     * @return array Estatísticas de performance
     */
    public function getPerformanceStats(string $reportType = null, int $timeWindow = 0): array
    {
        $stats = [
            'overall' => [
                'count' => 0,
                'cache_hits' => 0,
                'cache_misses' => 0,
                'avg_execution_time_ms' => 0,
                'max_execution_time_ms' => 0,
                'min_execution_time_ms' => PHP_INT_MAX,
                'total_execution_time_ms' => 0,
                'avg_memory_usage_mb' => 0,
                'avg_result_size_kb' => 0
            ],
            'by_type' => []
        ];
        
        $cutoffTime = $timeWindow > 0 ? time() - $timeWindow : 0;
        
        // Processar entradas de log
        foreach ($this->metricsLog as $entry) {
            // Verificar janela de tempo
            if ($timeWindow > 0 && strtotime($entry['timestamp']) < $cutoffTime) {
                continue;
            }
            
            // Verificar tipo específico
            if ($reportType !== null && $entry['report_type'] !== $reportType) {
                continue;
            }
            
            // Ignorar entradas incompletas
            if (!isset($entry['execution_time_ms'])) {
                continue;
            }
            
            // Adicionar aos totais gerais
            $stats['overall']['count']++;
            $stats['overall']['total_execution_time_ms'] += $entry['execution_time_ms'];
            $stats['overall']['max_execution_time_ms'] = max($stats['overall']['max_execution_time_ms'], $entry['execution_time_ms']);
            $stats['overall']['min_execution_time_ms'] = min($stats['overall']['min_execution_time_ms'], $entry['execution_time_ms']);
            
            if (isset($entry['cache_hit'])) {
                if ($entry['cache_hit']) {
                    $stats['overall']['cache_hits']++;
                } else {
                    $stats['overall']['cache_misses']++;
                }
            }
            
            if (isset($entry['memory_usage_bytes'])) {
                $stats['overall']['total_memory_usage_bytes'] = ($stats['overall']['total_memory_usage_bytes'] ?? 0) + $entry['memory_usage_bytes'];
            }
            
            if (isset($entry['result_size_bytes'])) {
                $stats['overall']['total_result_size_bytes'] = ($stats['overall']['total_result_size_bytes'] ?? 0) + $entry['result_size_bytes'];
            }
            
            // Adicionar às estatísticas por tipo
            $type = $entry['report_type'];
            if (!isset($stats['by_type'][$type])) {
                $stats['by_type'][$type] = [
                    'count' => 0,
                    'cache_hits' => 0,
                    'cache_misses' => 0,
                    'avg_execution_time_ms' => 0,
                    'max_execution_time_ms' => 0,
                    'min_execution_time_ms' => PHP_INT_MAX,
                    'total_execution_time_ms' => 0,
                    'avg_memory_usage_mb' => 0,
                    'avg_result_size_kb' => 0
                ];
            }
            
            $stats['by_type'][$type]['count']++;
            $stats['by_type'][$type]['total_execution_time_ms'] += $entry['execution_time_ms'];
            $stats['by_type'][$type]['max_execution_time_ms'] = max($stats['by_type'][$type]['max_execution_time_ms'], $entry['execution_time_ms']);
            $stats['by_type'][$type]['min_execution_time_ms'] = min($stats['by_type'][$type]['min_execution_time_ms'], $entry['execution_time_ms']);
            
            if (isset($entry['cache_hit'])) {
                if ($entry['cache_hit']) {
                    $stats['by_type'][$type]['cache_hits']++;
                } else {
                    $stats['by_type'][$type]['cache_misses']++;
                }
            }
            
            if (isset($entry['memory_usage_bytes'])) {
                $stats['by_type'][$type]['total_memory_usage_bytes'] = ($stats['by_type'][$type]['total_memory_usage_bytes'] ?? 0) + $entry['memory_usage_bytes'];
            }
            
            if (isset($entry['result_size_bytes'])) {
                $stats['by_type'][$type]['total_result_size_bytes'] = ($stats['by_type'][$type]['total_result_size_bytes'] ?? 0) + $entry['result_size_bytes'];
            }
        }
        
        // Calcular médias para estatísticas gerais
        if ($stats['overall']['count'] > 0) {
            $stats['overall']['avg_execution_time_ms'] = $stats['overall']['total_execution_time_ms'] / $stats['overall']['count'];
            
            if (isset($stats['overall']['total_memory_usage_bytes'])) {
                $stats['overall']['avg_memory_usage_mb'] = $stats['overall']['total_memory_usage_bytes'] / (1024 * 1024) / $stats['overall']['count'];
            }
            
            if (isset($stats['overall']['total_result_size_bytes'])) {
                $stats['overall']['avg_result_size_kb'] = $stats['overall']['total_result_size_bytes'] / 1024 / $stats['overall']['count'];
            }
            
            $totalRequests = $stats['overall']['cache_hits'] + $stats['overall']['cache_misses'];
            if ($totalRequests > 0) {
                $stats['overall']['cache_hit_ratio'] = $stats['overall']['cache_hits'] / $totalRequests;
            }
        }
        
        // Calcular médias para cada tipo
        foreach ($stats['by_type'] as $type => &$typeStats) {
            if ($typeStats['count'] > 0) {
                $typeStats['avg_execution_time_ms'] = $typeStats['total_execution_time_ms'] / $typeStats['count'];
                
                if (isset($typeStats['total_memory_usage_bytes'])) {
                    $typeStats['avg_memory_usage_mb'] = $typeStats['total_memory_usage_bytes'] / (1024 * 1024) / $typeStats['count'];
                }
                
                if (isset($typeStats['total_result_size_bytes'])) {
                    $typeStats['avg_result_size_kb'] = $typeStats['total_result_size_bytes'] / 1024 / $typeStats['count'];
                }
                
                $totalRequests = $typeStats['cache_hits'] + $typeStats['cache_misses'];
                if ($totalRequests > 0) {
                    $typeStats['cache_hit_ratio'] = $typeStats['cache_hits'] / $totalRequests;
                }
            }
        }
        
        // Ordenar tipos por contagem (decrescente)
        uasort($stats['by_type'], function ($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return [
            'stats' => $stats,
            'log_entries' => count($this->metricsLog),
            'time_window' => $timeWindow,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Identifica relatórios potencialmente problemáticos
     * 
     * @param float $timeThresholdMs Limiar de tempo de execução em ms
     * @param float $memorySizeThresholdMb Limiar de uso de memória em MB
     * @return array Lista de relatórios problemáticos
     */
    public function identifyProblemReports(float $timeThresholdMs = 1000, float $memorySizeThresholdMb = 50): array
    {
        $problemReports = [
            'slow_reports' => [],
            'memory_intensive_reports' => [],
            'large_result_reports' => [],
            'low_cache_hit_reports' => []
        ];
        
        // Obter estatísticas de desempenho
        $stats = $this->getPerformanceStats();
        
        // Verificar cada tipo de relatório
        foreach ($stats['stats']['by_type'] as $type => $typeStats) {
            // Relatórios lentos
            if ($typeStats['avg_execution_time_ms'] > $timeThresholdMs) {
                $problemReports['slow_reports'][] = [
                    'type' => $type,
                    'avg_execution_time_ms' => $typeStats['avg_execution_time_ms'],
                    'max_execution_time_ms' => $typeStats['max_execution_time_ms'],
                    'count' => $typeStats['count']
                ];
            }
            
            // Relatórios com uso intensivo de memória
            if (isset($typeStats['avg_memory_usage_mb']) && $typeStats['avg_memory_usage_mb'] > $memorySizeThresholdMb) {
                $problemReports['memory_intensive_reports'][] = [
                    'type' => $type,
                    'avg_memory_usage_mb' => $typeStats['avg_memory_usage_mb'],
                    'count' => $typeStats['count']
                ];
            }
            
            // Relatórios com resultados grandes
            if (isset($typeStats['avg_result_size_kb']) && $typeStats['avg_result_size_kb'] > 500) {
                $problemReports['large_result_reports'][] = [
                    'type' => $type,
                    'avg_result_size_kb' => $typeStats['avg_result_size_kb'],
                    'count' => $typeStats['count']
                ];
            }
            
            // Relatórios com baixa taxa de cache hit
            if (isset($typeStats['cache_hit_ratio']) && 
                $typeStats['cache_hit_ratio'] < 0.5 && 
                $typeStats['count'] >= 10) {
                $problemReports['low_cache_hit_reports'][] = [
                    'type' => $type,
                    'cache_hit_ratio' => $typeStats['cache_hit_ratio'],
                    'count' => $typeStats['count']
                ];
            }
        }
        
        // Ordenar cada categoria
        usort($problemReports['slow_reports'], function ($a, $b) {
            return $b['avg_execution_time_ms'] <=> $a['avg_execution_time_ms'];
        });
        
        usort($problemReports['memory_intensive_reports'], function ($a, $b) {
            return $b['avg_memory_usage_mb'] <=> $a['avg_memory_usage_mb'];
        });
        
        usort($problemReports['large_result_reports'], function ($a, $b) {
            return $b['avg_result_size_kb'] <=> $a['avg_result_size_kb'];
        });
        
        usort($problemReports['low_cache_hit_reports'], function ($a, $b) {
            return $a['cache_hit_ratio'] <=> $b['cache_hit_ratio'];
        });
        
        return $problemReports;
    }
    
    /**
     * Gera recomendações de otimização baseadas em métricas de desempenho
     * 
     * @return array Recomendações de otimização
     */
    public function generateOptimizationRecommendations(): array
    {
        $recommendations = [];
        
        // Identificar relatórios problemáticos
        $problemReports = $this->identifyProblemReports();
        
        // Recomendações para relatórios lentos
        foreach ($problemReports['slow_reports'] as $report) {
            $recommendations[] = [
                'report_type' => $report['type'],
                'issue' => 'Tempo de execução elevado',
                'metric' => round($report['avg_execution_time_ms']) . ' ms (média)',
                'recommendation' => 'Otimizar consulta SQL, implementar materialização de resultados intermediários, adicionar índices específicos',
                'priority' => $report['avg_execution_time_ms'] > 3000 ? 'Alta' : 'Média'
            ];
        }
        
        // Recomendações para relatórios com uso intensivo de memória
        foreach ($problemReports['memory_intensive_reports'] as $report) {
            $recommendations[] = [
                'report_type' => $report['type'],
                'issue' => 'Uso elevado de memória',
                'metric' => round($report['avg_memory_usage_mb'], 1) . ' MB (média)',
                'recommendation' => 'Implementar processamento em chunks, limitar conjuntos de resultados, otimizar estruturas de dados',
                'priority' => $report['avg_memory_usage_mb'] > 100 ? 'Alta' : 'Média'
            ];
        }
        
        // Recomendações para relatórios com resultados grandes
        foreach ($problemReports['large_result_reports'] as $report) {
            $recommendations[] = [
                'report_type' => $report['type'],
                'issue' => 'Tamanho elevado de resultados',
                'metric' => round($report['avg_result_size_kb']) . ' KB (média)',
                'recommendation' => 'Implementar paginação, reduzir campos retornados, aumentar compressão de cache',
                'priority' => $report['avg_result_size_kb'] > 1024 ? 'Alta' : 'Média'
            ];
        }
        
        // Recomendações para relatórios com baixa taxa de cache hit
        foreach ($problemReports['low_cache_hit_reports'] as $report) {
            $recommendations[] = [
                'report_type' => $report['type'],
                'issue' => 'Baixa taxa de cache hit',
                'metric' => round($report['cache_hit_ratio'] * 100) . '% (hit ratio)',
                'recommendation' => 'Aumentar tempo de expiração do cache, implementar prefetching, analisar padrões de invalidação',
                'priority' => $report['cache_hit_ratio'] < 0.3 ? 'Alta' : 'Média'
            ];
        }
        
        // Ordenar por prioridade
        usort($recommendations, function ($a, $b) {
            $priorityMap = ['Alta' => 3, 'Média' => 2, 'Baixa' => 1];
            return $priorityMap[$b['priority']] - $priorityMap[$a['priority']];
        });
        
        return [
            'recommendations' => $recommendations,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
