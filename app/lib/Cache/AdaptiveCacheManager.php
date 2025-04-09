<?php
namespace App\Lib\Cache;

/**
 * AdaptiveCacheManager
 * 
 * Gerenciador de cache adaptativo que otimiza automaticamente as estratégias 
 * de caching com base em padrões de acesso, frequência, tamanho e volatilidade.
 * 
 * Implementa:
 * - Políticas de expiração adaptativas
 * - Prefetching inteligente baseado em análise estatística
 * - Compressão seletiva baseada no tamanho e frequência de acesso
 * - Monitoramento de performance com ajuste automático
 * 
 * @package App\Lib\Cache
 * @version 1.0.0
 */
class AdaptiveCacheManager 
{
    /**
     * @var AdvancedReportCache Instância do cache de relatórios
     */
    private $cache;
    
    /**
     * @var array Configurações de expiração adaptativa
     */
    private $expirationConfig = [
        'default' => 3600,             // 1 hora (padrão)
        'min_expiration' => 300,       // 5 minutos (mínimo)
        'max_expiration' => 86400,     // 24 horas (máximo)
        'high_hit_multiplier' => 2.0,  // Multiplicador para alta frequência
        'low_hit_multiplier' => 0.5,   // Multiplicador para baixa frequência
        'high_hit_threshold' => 20,    // Limite para considerar alta frequência
        'low_hit_threshold' => 5,      // Limite para considerar baixa frequência
        'response_time_weight' => 0.3  // Peso do tempo de resposta na adaptação
    ];
    
    /**
     * @var array Configurações de prefetching
     */
    private $prefetchConfig = [
        'enabled' => true,
        'max_items' => 15,             // Máximo de itens para prefetch
        'min_hits' => 10,              // Mínimo de hits para prefetch
        'frequency_threshold' => 0.05, // Limiar de frequência relativa
        'freshness_window' => 86400,   // Janela de tempo para considerar dados "frescos" (24h)
        'excluded_types' => []         // Tipos de relatório excluídos do prefetch
    ];
    
    /**
     * @var array Configurações de compressão seletiva
     */
    private $compressionConfig = [
        'enabled' => true,
        'min_size' => 2048,            // Tamanho mínimo para considerar compressão (2KB)
        'compression_level' => 7,      // Nível de compressão (1-9)
        'size_threshold_ratio' => 0.7  // Relação de tamanho mínima para manter compressão
    ];
    
    /**
     * @var array Registros de performance para análise
     */
    private $performanceLog = [];
    
    /**
     * @var int Limite de registros de performance
     */
    private $performanceLogLimit = 1000;
    
    /**
     * Construtor
     * 
     * @param AdvancedReportCache $cache Instância do cache de relatórios
     * @param array $config Configurações opcionais
     */
    public function __construct(AdvancedReportCache $cache, array $config = [])
    {
        $this->cache = $cache;
        
        // Aplicar configurações personalizadas
        if (isset($config['expiration'])) {
            $this->expirationConfig = array_merge($this->expirationConfig, $config['expiration']);
        }
        
        if (isset($config['prefetch'])) {
            $this->prefetchConfig = array_merge($this->prefetchConfig, $config['prefetch']);
        }
        
        if (isset($config['compression'])) {
            $this->compressionConfig = array_merge($this->compressionConfig, $config['compression']);
        }
        
        // Carregar registros de performance anteriores
        $this->loadPerformanceLog();
    }
    
    /**
     * Calcula o tempo de expiração adaptativo para um item de cache
     * 
     * @param string $key Chave do item de cache
     * @param string $reportType Tipo de relatório
     * @param int $defaultExpiration Expiração padrão em segundos
     * @return int Tempo de expiração adaptado em segundos
     */
    public function calculateAdaptiveExpiration(string $key, string $reportType, int $defaultExpiration = null): int
    {
        // Usar expiração padrão fornecida ou configurada
        $baseExpiration = $defaultExpiration ?? $this->expirationConfig['default'];
        
        // Obter estatísticas do cache
        $stats = $this->cache->getStats();
        
        // Obter métricas de hits para esta chave
        $hitCount = $stats['hit_counts'][$key] ?? 0;
        
        // Obter métricas de tempo de resposta
        $avgResponseTime = $this->getAverageResponseTime($reportType);
        
        // Aplicar ajustes baseados em hits
        $expirationMultiplier = 1.0;
        
        if ($hitCount >= $this->expirationConfig['high_hit_threshold']) {
            // Alta frequência de acesso = maior expiração
            $expirationMultiplier *= $this->expirationConfig['high_hit_multiplier'];
        } elseif ($hitCount <= $this->expirationConfig['low_hit_threshold']) {
            // Baixa frequência de acesso = menor expiração
            $expirationMultiplier *= $this->expirationConfig['low_hit_multiplier'];
        }
        
        // Ajustar com base no tempo de resposta (mais lento = maior expiração)
        if ($avgResponseTime > 0) {
            $responseTimeFactor = min(2.0, max(0.5, $avgResponseTime / 1000));
            $expirationMultiplier += ($responseTimeFactor - 1) * $this->expirationConfig['response_time_weight'];
        }
        
        // Calcular expiração final
        $adaptiveExpiration = (int)($baseExpiration * $expirationMultiplier);
        
        // Garantir que a expiração esteja dentro dos limites configurados
        $adaptiveExpiration = min(
            $this->expirationConfig['max_expiration'],
            max($this->expirationConfig['min_expiration'], $adaptiveExpiration)
        );
        
        // Registrar para debugging e otimização futura
        $this->logAdaptiveExpiration($key, $reportType, $baseExpiration, $adaptiveExpiration, [
            'hit_count' => $hitCount,
            'avg_response_time' => $avgResponseTime,
            'multiplier' => $expirationMultiplier
        ]);
        
        return $adaptiveExpiration;
    }
    
    /**
     * Determina os itens de cache para prefetching baseado em análise de padrões
     * 
     * @return array Lista de chaves para prefetching
     */
    public function determinePrefetchItems(): array
    {
        if (!$this->prefetchConfig['enabled']) {
            return [];
        }
        
        $prefetchItems = [];
        $stats = $this->cache->getStats();
        
        // Verificar se há dados suficientes para análise
        if (!isset($stats['hit_counts']) || count($stats['hit_counts']) < 5) {
            return [];
        }
        
        // Calcular total de hits para normalização
        $totalHits = array_sum($stats['hit_counts']);
        
        // Ordenar por número de hits (decrescente)
        $hitCounts = $stats['hit_counts'];
        arsort($hitCounts);
        
        // Extrair padrões de acesso por hora do dia
        $hourPatterns = $this->extractHourPatterns();
        
        // Obter hora atual
        $currentHour = (int)date('G');
        
        // Filtrar e classificar candidatos a prefetching
        foreach ($hitCounts as $key => $hits) {
            // Verificar limiar de hits
            if ($hits < $this->prefetchConfig['min_hits']) {
                continue;
            }
            
            // Calcular frequência relativa
            $frequency = $hits / $totalHits;
            if ($frequency < $this->prefetchConfig['frequency_threshold']) {
                continue;
            }
            
            // Extrair tipo de relatório da chave
            $reportType = $this->extractReportTypeFromKey($key);
            
            // Verificar se o tipo está excluído
            if (in_array($reportType, $this->prefetchConfig['excluded_types'])) {
                continue;
            }
            
            // Verificar se o relatório é frequentemente acessado nesta hora do dia
            $hourScore = $hourPatterns[$reportType][$currentHour] ?? 0;
            
            // Calcular pontuação composta
            $score = $frequency * ($hourScore + 1); // Adicionar 1 para não zerar a pontuação
            
            // Adicionar à lista de candidatos
            $prefetchItems[$key] = $score;
        }
        
        // Ordenar por pontuação
        arsort($prefetchItems);
        
        // Limitar ao número máximo configurado
        $prefetchItems = array_slice($prefetchItems, 0, $this->prefetchConfig['max_items'], true);
        
        // Retornar apenas as chaves
        return array_keys($prefetchItems);
    }
    
    /**
     * Extrai padrões de acesso por hora do dia
     * 
     * @return array Padrões de acesso por tipo de relatório e hora
     */
    private function extractHourPatterns(): array
    {
        $patterns = [];
        
        // Usar dados do log de performance
        foreach ($this->performanceLog as $entry) {
            if (!isset($entry['timestamp'])) {
                continue;
            }
            
            // Obter hora do timestamp
            $hour = (int)date('G', strtotime($entry['timestamp']));
            $reportType = $entry['report_type'] ?? 'unknown';
            
            if (!isset($patterns[$reportType])) {
                $patterns[$reportType] = array_fill(0, 24, 0);
            }
            
            // Incrementar contagem para esta hora
            $patterns[$reportType][$hour]++;
        }
        
        // Normalizar contagens para cada tipo de relatório
        foreach ($patterns as $reportType => $hourCounts) {
            $max = max($hourCounts);
            if ($max > 0) {
                for ($i = 0; $i < 24; $i++) {
                    $patterns[$reportType][$i] = $hourCounts[$i] / $max;
                }
            }
        }
        
        return $patterns;
    }
    
    /**
     * Extrai o tipo de relatório de uma chave de cache
     * 
     * @param string $key Chave de cache
     * @return string Tipo de relatório
     */
    private function extractReportTypeFromKey(string $key): string
    {
        $parts = explode('_', $key);
        return $parts[0] ?? 'unknown';
    }
    
    /**
     * Determina se deve aplicar compressão para um item de cache
     * 
     * @param string $key Chave do item
     * @param mixed $data Dados a serem armazenados
     * @param int $size Tamanho aproximado dos dados (opcional)
     * @return bool True se deve comprimir
     */
    public function shouldCompress(string $key, $data, int $size = null): bool
    {
        if (!$this->compressionConfig['enabled']) {
            return false;
        }
        
        // Estimar tamanho se não fornecido
        if ($size === null) {
            $tempJson = json_encode($data);
            if ($tempJson === false) {
                return false;
            }
            $size = strlen($tempJson);
        }
        
        // Verificar tamanho mínimo
        if ($size < $this->compressionConfig['min_size']) {
            return false;
        }
        
        // Verificar frequência de acesso
        $stats = $this->cache->getStats();
        $hitCount = $stats['hit_counts'][$key] ?? 0;
        
        // Para itens frequentemente acessados, podemos preferir não comprimir
        // para minimizar overhead de descompressão
        if ($hitCount > $this->expirationConfig['high_hit_threshold'] * 3) {
            // Apenas comprimir se for realmente grande mesmo para itens frequentes
            return $size > $this->compressionConfig['min_size'] * 5;
        }
        
        return true;
    }
    
    /**
     * Obtém o nível de compressão adaptativo para um item
     * 
     * @param string $key Chave do item
     * @param int $size Tamanho aproximado dos dados
     * @return int Nível de compressão (1-9)
     */
    public function getCompressionLevel(string $key, int $size): int
    {
        // Nível base de compressão
        $level = $this->compressionConfig['compression_level'];
        
        // Verificar frequência de acesso
        $stats = $this->cache->getStats();
        $hitCount = $stats['hit_counts'][$key] ?? 0;
        
        // Para itens muito frequentes, usar compressão mais leve
        if ($hitCount > $this->expirationConfig['high_hit_threshold'] * 2) {
            $level = max(1, $level - 2);
        } 
        // Para itens raramente acessados, usar compressão mais forte
        elseif ($hitCount < $this->expirationConfig['low_hit_threshold']) {
            $level = min(9, $level + 1);
        }
        
        // Para itens muito grandes, usar compressão mais forte
        if ($size > $this->compressionConfig['min_size'] * 10) {
            $level = min(9, $level + 1);
        }
        
        return $level;
    }
    
    /**
     * Registra o tempo de resposta para um tipo de relatório
     * 
     * @param string $reportType Tipo de relatório
     * @param float $responseTimeMs Tempo de resposta em milissegundos
     * @param string $key Chave de cache (opcional)
     * @param bool $cacheHit Se foi um cache hit (opcional)
     * @return void
     */
    public function logResponseTime(string $reportType, float $responseTimeMs, string $key = null, bool $cacheHit = false): void
    {
        // Criar entrada de log
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'report_type' => $reportType,
            'response_time_ms' => $responseTimeMs,
            'cache_hit' => $cacheHit
        ];
        
        if ($key !== null) {
            $entry['cache_key'] = $key;
        }
        
        // Adicionar ao log
        $this->performanceLog[] = $entry;
        
        // Limitar tamanho do log
        if (count($this->performanceLog) > $this->performanceLogLimit) {
            // Remover entradas mais antigas
            $this->performanceLog = array_slice($this->performanceLog, -$this->performanceLogLimit);
        }
        
        // Salvar periodicamente
        if (count($this->performanceLog) % 10 === 0) {
            $this->savePerformanceLog();
        }
    }
    
    /**
     * Obtém o tempo médio de resposta para um tipo de relatório
     * 
     * @param string $reportType Tipo de relatório
     * @param bool $cacheHitOnly Considerar apenas cache hits
     * @param int $timeWindow Janela de tempo em segundos para considerar (0 = todos)
     * @return float Tempo médio de resposta em milissegundos
     */
    public function getAverageResponseTime(string $reportType, bool $cacheHitOnly = false, int $timeWindow = 0): float
    {
        $times = [];
        $cutoffTime = $timeWindow > 0 ? time() - $timeWindow : 0;
        
        foreach ($this->performanceLog as $entry) {
            if ($entry['report_type'] !== $reportType) {
                continue;
            }
            
            if ($cacheHitOnly && !($entry['cache_hit'] ?? false)) {
                continue;
            }
            
            if ($timeWindow > 0 && strtotime($entry['timestamp']) < $cutoffTime) {
                continue;
            }
            
            $times[] = $entry['response_time_ms'];
        }
        
        if (empty($times)) {
            return 0;
        }
        
        return array_sum($times) / count($times);
    }
    
    /**
     * Registra informações sobre expiração adaptativa para análise
     * 
     * @param string $key Chave de cache
     * @param string $reportType Tipo de relatório
     * @param int $baseExpiration Expiração base
     * @param int $adaptiveExpiration Expiração adaptativa
     * @param array $factors Fatores usados no cálculo
     */
    private function logAdaptiveExpiration(string $key, string $reportType, int $baseExpiration, int $adaptiveExpiration, array $factors): void
    {
        // Este método pode ser expandido para registrar em um arquivo de log
        // ou em uma tabela de banco de dados para análise posterior
    }
    
    /**
     * Carrega o log de performance do armazenamento persistente
     * 
     * @return void
     */
    private function loadPerformanceLog(): void
    {
        $logFile = $this->getPerformanceLogPath();
        
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            $data = json_decode($content, true);
            
            if (is_array($data)) {
                $this->performanceLog = $data;
            }
        }
    }
    
    /**
     * Salva o log de performance em armazenamento persistente
     * 
     * @return bool Sucesso da operação
     */
    private function savePerformanceLog(): bool
    {
        $logFile = $this->getPerformanceLogPath();
        $logDir = dirname($logFile);
        
        // Garantir que o diretório existe
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true)) {
                return false;
            }
        }
        
        // Salvar dados
        $content = json_encode($this->performanceLog);
        return file_put_contents($logFile, $content) !== false;
    }
    
    /**
     * Obtém o caminho para o arquivo de log de performance
     * 
     * @return string Caminho completo
     */
    private function getPerformanceLogPath(): string
    {
        return dirname(__DIR__, 3) . '/cache/adaptive_cache_performance.json';
    }
    
    /**
     * Obtém estatísticas detalhadas do gerenciador adaptativo
     * 
     * @return array Estatísticas detalhadas
     */
    public function getDetailedStats(): array
    {
        // Obter estatísticas do cache
        $cacheStats = $this->cache->getStats();
        
        // Calcular estatísticas por tipo de relatório
        $reportTypeStats = [];
        
        // Agrupar log de performance por tipo de relatório
        foreach ($this->performanceLog as $entry) {
            $reportType = $entry['report_type'] ?? 'unknown';
            
            if (!isset($reportTypeStats[$reportType])) {
                $reportTypeStats[$reportType] = [
                    'count' => 0,
                    'hits' => 0,
                    'misses' => 0,
                    'avg_response_time_ms' => 0,
                    'total_response_time' => 0,
                    'max_response_time' => 0,
                    'min_response_time' => PHP_INT_MAX,
                    'by_hour' => array_fill(0, 24, 0)
                ];
            }
            
            $reportTypeStats[$reportType]['count']++;
            
            if (isset($entry['cache_hit'])) {
                if ($entry['cache_hit']) {
                    $reportTypeStats[$reportType]['hits']++;
                } else {
                    $reportTypeStats[$reportType]['misses']++;
                }
            }
            
            if (isset($entry['response_time_ms'])) {
                $responseTime = $entry['response_time_ms'];
                $reportTypeStats[$reportType]['total_response_time'] += $responseTime;
                $reportTypeStats[$reportType]['max_response_time'] = max($reportTypeStats[$reportType]['max_response_time'], $responseTime);
                $reportTypeStats[$reportType]['min_response_time'] = min($reportTypeStats[$reportType]['min_response_time'], $responseTime);
            }
            
            if (isset($entry['timestamp'])) {
                $hour = (int)date('G', strtotime($entry['timestamp']));
                $reportTypeStats[$reportType]['by_hour'][$hour]++;
            }
        }
        
        // Calcular médias
        foreach ($reportTypeStats as &$stats) {
            if ($stats['count'] > 0) {
                $stats['avg_response_time_ms'] = $stats['total_response_time'] / $stats['count'];
                
                if (($stats['hits'] + $stats['misses']) > 0) {
                    $stats['hit_ratio'] = $stats['hits'] / ($stats['hits'] + $stats['misses']);
                } else {
                    $stats['hit_ratio'] = 0;
                }
            }
            
            // Normalizar distribuição por hora
            $maxByHour = max($stats['by_hour']);
            if ($maxByHour > 0) {
                for ($i = 0; $i < 24; $i++) {
                    $stats['by_hour_normalized'][$i] = $stats['by_hour'][$i] / $maxByHour;
                }
            } else {
                $stats['by_hour_normalized'] = array_fill(0, 24, 0);
            }
        }
        
        // Construir estatísticas completas
        return [
            'cache_stats' => $cacheStats,
            'report_type_stats' => $reportTypeStats,
            'adaptive_config' => [
                'expiration' => $this->expirationConfig,
                'prefetch' => $this->prefetchConfig,
                'compression' => $this->compressionConfig
            ],
            'performance_log_entries' => count($this->performanceLog),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
