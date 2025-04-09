# Documentação de Métricas de Performance e Monitoramento

## 1. Visão Geral

Este documento descreve a implementação do sistema de métricas de performance para o módulo de relatórios da Taverna da Impressão 3D. O sistema visa monitorar, registrar e analisar métricas críticas de desempenho para garantir operação eficiente e identificar precocemente potenciais degradações.

## 2. Métricas Coletadas

### 2.1 Métricas de Tempo de Execução

| Métrica | Descrição | Limiar Aceitável | Limiar de Alerta |
|---------|-----------|------------------|------------------|
| `query_execution_time` | Tempo para execução de consulta SQL | < 200ms | > 500ms |
| `report_generation_time` | Tempo total para geração de relatório | < 300ms | > 800ms |
| `rendering_time` | Tempo para renderização da view | < 100ms | > 300ms |
| `export_generation_time` | Tempo para exportação (PDF/Excel) | < 2000ms | > 5000ms |
| `cache_retrieval_time` | Tempo para recuperação de cache | < 20ms | > 50ms |

### 2.2 Métricas de Utilização de Recursos

| Métrica | Descrição | Limiar Aceitável | Limiar de Alerta |
|---------|-----------|------------------|------------------|
| `peak_memory_usage` | Uso máximo de memória durante operação | < 32MB | > 64MB |
| `query_result_size` | Tamanho do conjunto de resultados | < 1000 linhas | > 5000 linhas |
| `dataset_compression_ratio` | Taxa de compressão de dados em cache | > 3:1 | < 1.5:1 |
| `concurrent_requests` | Número de solicitações simultâneas | < 10 | > 25 |

### 2.3 Métricas de Cache

| Métrica | Descrição | Limiar Aceitável | Limiar de Alerta |
|---------|-----------|------------------|------------------|
| `cache_hit_ratio` | Proporção de acertos no cache | > 80% | < 50% |
| `cache_invalidations` | Número de invalidações de cache por hora | < 10 | > 30 |
| `cache_evictions` | Número de entradas removidas por política TTL | < 20/hora | > 50/hora |
| `average_ttl` | Tempo médio de vida de entradas no cache | > 30 min | < 10 min |

## 3. Instrumentação de Código

### 3.1 Medição de Tempo

```php
/**
 * Exemplo de instrumentação para medição de tempo de execução
 */
$startTime = microtime(true);

// Operação a ser medida
$result = $this->optimizedReportModel->getSalesData($startDate, $endDate, $groupBy);

$endTime = microtime(true);
$executionTime = round(($endTime - $startTime) * 1000, 2); // Em milissegundos

// Registro da métrica
$this->performanceMonitor->recordMetric(
    'query_execution_time',
    $executionTime,
    [
        'report_type' => 'sales',
        'group_by' => $groupBy,
        'date_range' => $endDate - $startDate
    ]
);
```

### 3.2 Monitoramento de Uso de Memória

```php
/**
 * Exemplo de instrumentação para medição de uso de memória
 */
$startMemory = memory_get_usage();

// Operação a ser medida
$data = $this->optimizedReportModel->getCustomerActivityReport($startDate, $endDate, true);

$endMemory = memory_get_usage();
$peakMemory = memory_get_peak_usage();
$memoryUsed = $endMemory - $startMemory;

// Registro das métricas
$this->performanceMonitor->recordMetric('memory_used', $memoryUsed);
$this->performanceMonitor->recordMetric('peak_memory', $peakMemory);
```

### 3.3 Monitoramento de Cache

```php
/**
 * Exemplo de instrumentação para métricas de cache
 */
public function get($key)
{
    $startTime = microtime(true);
    $data = $this->memoryCache[$key] ?? null;
    
    if ($data !== null) {
        // Cache hit em memória
        $this->cacheStats['memory_hits']++;
        $endTime = microtime(true);
        $this->performanceMonitor->recordMetric('cache_retrieval_time', ($endTime - $startTime) * 1000);
        return $data;
    }
    
    // Tentar cache em disco
    $data = $this->diskCache->get($key);
    
    if ($data !== null) {
        // Cache hit em disco
        $this->cacheStats['disk_hits']++;
        // Atualizar cache em memória para futuras requisições
        $this->memoryCache[$key] = $data;
    } else {
        // Cache miss
        $this->cacheStats['misses']++;
    }
    
    $endTime = microtime(true);
    $this->performanceMonitor->recordMetric('cache_retrieval_time', ($endTime - $startTime) * 1000);
    return $data;
}
```

## 4. Armazenamento e Análise de Métricas

### 4.1 Estrutura de Dados para Métricas

As métricas são armazenadas em formato JSON em dois níveis:

1. **Armazenamento de curto prazo**: Mantido em memória para análise em tempo real
2. **Armazenamento persistente**: Registrado em arquivo para análise histórica

Exemplo de estrutura de dados:

```json
{
  "timestamp": "2025-04-06T14:15:22.123Z",
  "metric": "query_execution_time",
  "value": 156.78,
  "context": {
    "report_type": "sales",
    "group_by": "month",
    "date_range": 90,
    "user_id": 42,
    "user_role": "admin",
    "query_hash": "a1b2c3d4"
  }
}
```

### 4.2 Cálculo de Estatísticas

```php
/**
 * Exemplo de cálculo de estatísticas de performance
 */
public function calculateStatistics($metricName, $timeframeMinutes = 60)
{
    $cutoffTime = time() - ($timeframeMinutes * 60);
    $relevantMetrics = array_filter(
        $this->metrics[$metricName] ?? [],
        function($record) use ($cutoffTime) {
            return $record['timestamp'] >= $cutoffTime;
        }
    );
    
    if (empty($relevantMetrics)) {
        return [
            'count' => 0,
            'min' => null,
            'max' => null,
            'avg' => null,
            'p95' => null,
            'stddev' => null
        ];
    }
    
    // Extrair valores
    $values = array_column($relevantMetrics, 'value');
    
    // Ordenar para cálculos de percentil
    sort($values);
    
    // Calcular estatísticas
    $count = count($values);
    $sum = array_sum($values);
    $min = min($values);
    $max = max($values);
    $avg = $sum / $count;
    
    // Percentil 95
    $p95Index = ceil($count * 0.95) - 1;
    $p95 = $values[$p95Index];
    
    // Desvio padrão
    $variance = array_reduce(
        $values,
        function($carry, $item) use ($avg) {
            return $carry + pow($item - $avg, 2);
        },
        0
    ) / $count;
    $stddev = sqrt($variance);
    
    return [
        'count' => $count,
        'min' => $min,
        'max' => $max,
        'avg' => $avg,
        'p95' => $p95,
        'stddev' => $stddev
    ];
}
```

## 5. Sistema de Alertas

### 5.1 Detecção de Anomalias

```php
/**
 * Exemplo de detecção de anomalias de performance
 */
public function detectAnomalies()
{
    $anomalies = [];
    
    // Verificar cada tipo de métrica
    foreach ($this->metricThresholds as $metric => $thresholds) {
        // Obter estatísticas recentes
        $stats = $this->calculateStatistics($metric, 15); // últimos 15 minutos
        
        if ($stats['count'] < 5) {
            // Dados insuficientes para análise
            continue;
        }
        
        // Verificar média contra limiar de alerta
        if ($stats['avg'] > $thresholds['alert']) {
            $anomalies[$metric] = [
                'type' => 'threshold_exceeded',
                'metric' => $metric,
                'value' => $stats['avg'],
                'threshold' => $thresholds['alert'],
                'severity' => 'high',
                'description' => "Média de {$metric} ({$stats['avg']}) excede limiar de alerta ({$thresholds['alert']})"
            ];
        }
        // Verificar média contra limiar aceitável
        else if ($stats['avg'] > $thresholds['acceptable']) {
            $anomalies[$metric] = [
                'type' => 'threshold_exceeded',
                'metric' => $metric,
                'value' => $stats['avg'],
                'threshold' => $thresholds['acceptable'],
                'severity' => 'medium',
                'description' => "Média de {$metric} ({$stats['avg']}) excede limiar aceitável ({$thresholds['acceptable']})"
            ];
        }
        
        // Verificar tendência (aumento significativo)
        $prevStats = $this->calculateStatistics($metric, 60, 15); // 15-60 minutos atrás
        if ($prevStats['count'] >= 5) {
            $percentChange = (($stats['avg'] - $prevStats['avg']) / $prevStats['avg']) * 100;
            if ($percentChange > 50) { // aumento de 50%
                $anomalies["{$metric}_trend"] = [
                    'type' => 'trend',
                    'metric' => $metric,
                    'value' => $percentChange,
                    'threshold' => 50,
                    'severity' => 'medium',
                    'description' => "Aumento de {$percentChange}% em {$metric} nos últimos 15 minutos"
                ];
            }
        }
    }
    
    return $anomalies;
}
```

### 5.2 Notificação de Alertas

As anomalias detectadas serão tratadas pelo sistema de notificações que será implementado na próxima fase, seguindo estes princípios:

- **Agregação inteligente**: Alertas relacionados são agrupados para evitar sobrecarga
- **Priorização**: Alertas são priorizados com base em severidade e impacto
- **Canais configuráveis**: Suporte para notificações via:
  - Email
  - Sistema interno de notificações
  - Webhooks para integração com sistemas externos
  - Logging centralizado

## 6. Visualização de Métricas

A interface de visualização de métricas será implementada na próxima fase do projeto, incluindo:

1. **Dashboard principal**: Visão geral com indicadores críticos
2. **Relatórios detalhados**:
   - Desempenho de consultas por período
   - Eficiência de cache por tipo de relatório
   - Uso de recursos por tipo de operação
3. **Gráficos de tendência**:
   - Evolução dos tempos de resposta 
   - Taxas de hit/miss de cache
   - Correlação entre volume de dados e performance

## 7. Considerações de Segurança

- **Sanitização de dados**: Todos os parâmetros de consulta em logs de performance são sanitizados
- **Controle de acesso**: Dashboards de performance são restritos a administradores
- **Rate limiting**: Coleta de métricas possui proteção contra sobrecarga
- **Dados sensíveis**: Métricas não contêm dados sensíveis de usuários ou negócio

## 8. Próximos Passos

- Implementar retenção e rotação de dados de métricas históricas
- Desenvolver mecanismos avançados de auto-tuning baseados em métricas
- Criar interface de administração para configuração de limiares
- Implementar sistema de notificações push para alertas de anomalias
- Desenvolver visualizações interativas para análise de métricas

## 9. Referências

- [ReportPerformanceMonitor.php](../../app/lib/Reports/ReportPerformanceMonitor.php)
- [AdvancedReportCache.php](../../app/lib/Reports/AdvancedReportCache.php)
- [AdaptiveCacheManager.php](../../app/lib/Reports/AdaptiveCacheManager.php)
- [OptimizedReportSystem.md](./OptimizedReportSystem.md)