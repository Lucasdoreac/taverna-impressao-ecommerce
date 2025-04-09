# Métricas de Performance - Documentação Técnica
**Taverna da Impressão 3D - Sistema de Monitoramento**

## Introdução

Este documento define as métricas de performance monitoradas pelo sistema, seus thresholds, métodos de coleta e processamento. As métricas aqui descritas são utilizadas pelo `NotificationManager` e `NotificationThresholds` para detecção proativa de problemas de performance.

## Categorias de Métricas

### 1. Métricas de Resposta do Servidor

| Métrica | Descrição | Unidade | Threshold | Operador | Severidade |
|---------|-----------|---------|-----------|----------|------------|
| `response_time` | Tempo total de resposta do servidor | segundos | 1.5 | > | Média-Alta |
| `ttfb` | Time To First Byte | milissegundos | 100 | > | Média |
| `processing_time` | Tempo de processamento sem I/O | milissegundos | 500 | > | Alta |
| `rendering_time` | Tempo de renderização dos templates | milissegundos | 300 | > | Média |

**Método de coleta**: Middleware de performance que intercepta requisições HTTP.

**Frequência de coleta**: Cada requisição, com agregação a cada 5 minutos.

**Código de implementação**:
```php
// Implementado em app/lib/Performance/RequestPerformanceMiddleware.php
$startTime = microtime(true);
// Execução da requisição
$responseTime = microtime(true) - $startTime;
NotificationManager::getInstance()->recordPerformanceMetrics('HttpServer', [
    'response_time' => $responseTime
]);
```

### 2. Métricas de Banco de Dados

| Métrica | Descrição | Unidade | Threshold | Operador | Severidade |
|---------|-----------|---------|-----------|----------|------------|
| `query_time` | Tempo de execução de consulta | segundos | 0.5 | > | Alta |
| `query_count` | Número de consultas por requisição | inteiro | 20 | > | Média |
| `slow_queries` | Consultas acima de 1s em 5min | inteiro | 5 | > | Alta |
| `failed_transactions` | Transações com falha em 15min | inteiro | 3 | > | Crítica |

**Método de coleta**: Hook no adaptador de banco de dados que registra tempos de execução.

**Frequência de coleta**: Cada operação de banco de dados, com agregação a cada 5 minutos.

**Tratamento de anomalias**: Consultas excepcionalmente lentas (>2s) geram alertas imediatos.

### 3. Métricas de Recursos do Sistema

| Métrica | Descrição | Unidade | Threshold | Operador | Severidade |
|---------|-----------|---------|-----------|----------|------------|
| `memory_usage` | Uso de memória do processo PHP | MB | 128 | > | Alta |
| `cpu_usage` | Uso de CPU do servidor | porcentagem | 85 | > | Alta |
| `disk_usage` | Uso de espaço em disco | porcentagem | 90 | > | Alta |
| `disk_iops` | Operações de I/O por segundo | operações | 1000 | > | Média |

**Método de coleta**: Sonda cron executada a cada 5 minutos.

**Limiares adaptativos**: Aplicados a `cpu_usage` e `memory_usage` com base nos padrões históricos.

### 4. Métricas de Cache

| Métrica | Descrição | Unidade | Threshold | Operador | Severidade |
|---------|-----------|---------|-----------|----------|------------|
| `cache_hit_ratio` | Taxa de acertos do cache | porcentagem | 60 | < | Média |
| `cache_expiration_rate` | Taxa de expiração prematura | porcentagem | 15 | > | Baixa |
| `cache_size` | Tamanho total do cache | MB | 200 | > | Baixa |
| `cache_fragmentation` | Fragmentação de memória do cache | porcentagem | 30 | > | Média |

**Método de coleta**: Instrumentação no adaptador de cache com registro a cada operação.

### 5. Métricas do Visualizador 3D

| Métrica | Descrição | Unidade | Threshold | Operador | Severidade |
|---------|-----------|---------|-----------|----------|------------|
| `model_load_time` | Tempo para carregar modelo 3D | segundos | 3 | > | Alta |
| `rendering_fps` | Frames por segundo na renderização | FPS | 30 | < | Média |
| `texture_memory` | Memória usada para texturas | MB | 50 | > | Média |
| `gpu_utilization` | Utilização da GPU do cliente | porcentagem | 90 | > | Baixa |

**Método de coleta**: Telemetria do cliente via API JavaScript com envio periódico.

### 6. Métricas de Fila de Impressão

| Métrica | Descrição | Unidade | Threshold | Operador | Severidade |
|---------|-----------|---------|-----------|----------|------------|
| `queue_length` | Tamanho da fila de impressão | inteiro | 30 | > | Média |
| `queue_wait_time` | Tempo médio de espera | horas | 48 | > | Alta |
| `processing_failures` | Falhas de processamento em 24h | inteiro | 5 | > | Alta |
| `stalled_jobs` | Trabalhos paralisados | inteiro | 3 | > | Crítica |

**Método de coleta**: Análise periódica da tabela `print_queue` a cada 30 minutos.

### 7. Métricas de Relatórios Administrativos

| Métrica | Descrição | Unidade | Threshold | Operador | Severidade |
|---------|-----------|---------|-----------|----------|------------|
| `report_generation_time` | Tempo para gerar relatório | segundos | 10 | > | Alta |
| `report_data_points` | Pontos de dados processados | inteiro | 10000 | > | Média |
| `concurrent_reports` | Relatórios simultâneos | inteiro | 5 | > | Média |
| `report_export_size` | Tamanho de arquivo exportado | MB | 15 | > | Baixa |

**Método de coleta**: Instrumentação no sistema de relatórios com registro no início e fim do processo.

## Implementação de Coleta

### Middleware de Performance

O `PerformanceMiddleware` atua como um interceptador que registra métricas para cada requisição HTTP:

```php
class PerformanceMiddleware {
    public function process($request, $next) {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Execução da requisição
        $response = $next($request);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $metrics = [
            'response_time' => $endTime - $startTime,
            'memory_delta' => ($endMemory - $startMemory) / 1024 / 1024, // MB
            'memory_peak' => memory_get_peak_usage() / 1024 / 1024 // MB
        ];
        
        NotificationManager::getInstance()->recordPerformanceMetrics('HttpServer', $metrics);
        
        return $response;
    }
}
```

### Adaptador de Database com Métricas

O `DatabaseMetricsAdapter` decora operações de banco de dados com coleta de métricas:

```php
class DatabaseMetricsAdapter {
    private $db;
    private $queryCount = 0;
    private $metrics = [];
    
    public function execute($sql, $params = []) {
        $this->queryCount++;
        $startTime = microtime(true);
        
        $result = $this->db->execute($sql, $params);
        
        $queryTime = microtime(true) - $startTime;
        $this->metrics['query_time'][] = $queryTime;
        
        if ($queryTime > 0.5) {
            // Registrar consulta lenta
            $this->metrics['slow_queries'][] = [
                'sql' => $sql,
                'time' => $queryTime,
                'timestamp' => time()
            ];
        }
        
        return $result;
    }
    
    public function __destruct() {
        if ($this->queryCount > 0) {
            NotificationManager::getInstance()->recordPerformanceMetrics('Database', [
                'query_count' => $this->queryCount,
                'avg_query_time' => array_sum($this->metrics['query_time']) / count($this->metrics['query_time']),
                'max_query_time' => max($this->metrics['query_time']),
                'slow_queries_count' => count($this->metrics['slow_queries'] ?? [])
            ]);
        }
    }
}
```

## Armazenamento e Retenção

As métricas coletadas são armazenadas nas seguintes tabelas:

1. `performance_metrics`: Armazena todas as métricas brutas
   - Política de retenção: 90 dias para dados brutos
   - Agregação: Dados agregados por hora após 7 dias

2. `performance_alerts_log`: Registra alertas gerados
   - Política de retenção: 365 dias
   - Inclui contexto completo do alerta

3. `performance_dashboard`: Visualização em tempo real
   - Mantém apenas alertas ativos não resolvidos
   - Dados históricos são mantidos em `performance_alerts_log`

## Testes Automatizados

Cada métrica possui testes automatizados que:

1. Verificam a correta coleta de dados
2. Validam o funcionamento dos thresholds 
3. Testam o disparo de alertas em condições anômalas
4. Simulam condições de carga para verificar o comportamento do sistema

Os testes estão implementados no diretório `tests/performance/`.

## Implementação de Dashboards

Os dashboards de monitoramento utilizam as seguintes visualizações:

1. **Dashboard de Visão Geral**: Métricas agregadas com status atual
2. **Dashboard de Tendências**: Gráficos temporais mostrando evolução das métricas
3. **Dashboard de Alertas**: Lista de alertas ativos com ações possíveis
4. **Dashboard de Capacidade**: Projeções de uso de recursos baseadas em tendências

## Integração com Serviços Externos

O sistema está preparado para integração com:

1. **Serviços de Monitoramento**: Compatibilidade para exportação no formato Prometheus
2. **Plataformas de Alerta**: Integração com PagerDuty, OpsGenie (planejado)
3. **Sistemas de Logging**: Exportação para ELK Stack ou Graylog (planejado)

## Referências

- [Web Vitals (Google)](https://web.dev/vitals/)
- [Prometheus Metrics Naming](https://prometheus.io/docs/practices/naming/)
- [Database Performance Metrics (Oracle)](https://docs.oracle.com/en/database/oracle/oracle-database/19/tgdba/monitoring-database-operations.html)
