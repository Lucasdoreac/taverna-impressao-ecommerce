<?php
namespace App\Lib\Repositories;

use App\Lib\Security\InputValidator;

/**
 * AbstractReportRepository
 * 
 * Classe base abstrata que implementa funcionalidades comuns para repositórios de relatório.
 * Inclui métodos para medição de performance, coleta de métricas e validação de entrada.
 *
 * @package App\Lib\Repositories
 * @version 1.0.0
 */
abstract class AbstractReportRepository implements IReportRepository
{
    /**
     * @var array Armazena informações de performance para relatórios executados
     */
    protected $executionMetrics = [];
    
    /**
     * @var array Armazena informações de cache para relatórios executados
     */
    protected $cacheMetrics = [];
    
    /**
     * Executa uma função de relatório com medição de performance
     *
     * @param string $reportType Tipo do relatório
     * @param callable $callback Função a ser executada
     * @param array $params Parâmetros usados (para cache de métricas)
     * @return array Resultado da função
     */
    protected function executeWithMetrics(string $reportType, callable $callback, array $params = []): array
    {
        // Iniciar medição de tempo
        $startTime = microtime(true);
        $memoryBefore = memory_get_usage();
        
        // Executar função
        $result = $callback();
        
        // Calcular métricas
        $endTime = microtime(true);
        $memoryAfter = memory_get_usage();
        $executionTime = round(($endTime - $startTime) * 1000, 2); // em milissegundos
        $memoryUsage = $memoryAfter - $memoryBefore;
        
        // Registrar métricas
        $this->executionMetrics[$reportType] = [
            'execution_time_ms' => $executionTime,
            'memory_usage_bytes' => $memoryUsage,
            'timestamp' => time(),
            'parameters' => $params,
            'result_size' => is_array($result) ? count($result) : 0
        ];
        
        return $result;
    }
    
    /**
     * Registra informação de uso de cache
     *
     * @param string $reportType Tipo do relatório
     * @param bool $cacheUsed Se o cache foi utilizado
     * @return void
     */
    protected function registerCacheUsage(string $reportType, bool $cacheUsed): void
    {
        $this->cacheMetrics[$reportType] = [
            'cache_used' => $cacheUsed,
            'timestamp' => time()
        ];
    }
    
    /**
     * Retorna métricas de performance do repositório
     * 
     * @return array Métricas de performance
     */
    public function getPerformanceMetrics(): array
    {
        // Métricas básicas
        $metrics = [
            'execution_metrics' => $this->executionMetrics,
            'cache_metrics' => $this->cacheMetrics
        ];
        
        // Adicionar estatísticas agregadas
        if (!empty($this->executionMetrics)) {
            $totalExecutions = count($this->executionMetrics);
            $totalTimeMs = array_sum(array_column($this->executionMetrics, 'execution_time_ms'));
            $averageTimeMs = $totalTimeMs / $totalExecutions;
            
            // Calcular taxa de cache hits se tiver métricas de cache
            $cacheHitRate = 0;
            if (!empty($this->cacheMetrics)) {
                $cacheHits = count(array_filter($this->cacheMetrics, function ($metric) {
                    return $metric['cache_used'] === true;
                }));
                $cacheHitRate = ($cacheHits / count($this->cacheMetrics)) * 100;
            }
            
            // Adicionar métricas agregadas
            $metrics['summary'] = [
                'total_executions' => $totalExecutions,
                'total_time_ms' => round($totalTimeMs, 2),
                'average_time_ms' => round($averageTimeMs, 2),
                'cache_hit_rate_percentage' => round($cacheHitRate, 2),
                'total_memory_usage_bytes' => array_sum(array_column($this->executionMetrics, 'memory_usage_bytes')),
                'slowest_reports' => $this->getSlowestReports(),
                'fastest_reports' => $this->getFastestReports()
            ];
        }
        
        return $metrics;
    }
    
    /**
     * Retorna os relatórios mais lentos
     *
     * @param int $limit Número máximo de resultados
     * @return array Lista dos relatórios mais lentos
     */
    protected function getSlowestReports(int $limit = 5): array
    {
        if (empty($this->executionMetrics)) {
            return [];
        }
        
        // Clonar array para não modificar o original
        $metrics = $this->executionMetrics;
        
        // Ordenar por tempo de execução (decrescente)
        uasort($metrics, function ($a, $b) {
            return $b['execution_time_ms'] <=> $a['execution_time_ms'];
        });
        
        // Retornar apenas os mais lentos
        return array_slice($metrics, 0, $limit, true);
    }
    
    /**
     * Retorna os relatórios mais rápidos
     *
     * @param int $limit Número máximo de resultados
     * @return array Lista dos relatórios mais rápidos
     */
    protected function getFastestReports(int $limit = 5): array
    {
        if (empty($this->executionMetrics)) {
            return [];
        }
        
        // Clonar array para não modificar o original
        $metrics = $this->executionMetrics;
        
        // Ordenar por tempo de execução (crescente)
        uasort($metrics, function ($a, $b) {
            return $a['execution_time_ms'] <=> $b['execution_time_ms'];
        });
        
        // Retornar apenas os mais rápidos
        return array_slice($metrics, 0, $limit, true);
    }
    
    /**
     * Verifica se o cache foi usado para um tipo específico de relatório
     *
     * @param string $reportType Tipo do relatório (opcional)
     * @return bool True se o cache foi usado
     */
    public function wasCacheUsed(string $reportType = ''): bool
    {
        // Se nenhum tipo específico for informado, verificar o último
        if (empty($reportType) && !empty($this->cacheMetrics)) {
            $lastKey = array_key_last($this->cacheMetrics);
            return $this->cacheMetrics[$lastKey]['cache_used'] ?? false;
        }
        
        // Verificar o tipo específico
        return isset($this->cacheMetrics[$reportType]) && $this->cacheMetrics[$reportType]['cache_used'];
    }
    
    /**
     * Valida um período para garantir que esteja dentro dos valores permitidos
     *
     * @param string $period Período a ser validado
     * @param array $allowedValues Valores permitidos
     * @param string $default Valor padrão
     * @return string Período validado
     */
    protected function validatePeriod(string $period, array $allowedValues, string $default): string
    {
        if (!in_array($period, $allowedValues)) {
            return $default;
        }
        return $period;
    }
}