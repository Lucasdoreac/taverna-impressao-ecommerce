<?php
namespace App\Models;

use PDO;
use PDOException;
use App\Lib\Database\Database;
use App\Lib\Security\InputValidator;
use App\Lib\Cache\AdvancedReportCache;

/**
 * OptimizedReportModel
 * 
 * Versão otimizada do modelo de relatórios com melhorias de performance para
 * consultas críticas, materialização de resultados intermediários e
 * estratégias avançadas de caching.
 *
 * @version 2.0.0
 * @author Taverna da Impressão
 */
class OptimizedReportModel extends ReportModel 
{
    /**
     * Valor máximo de registros retornados por tabelas temporárias
     * para evitar consumo excessivo de memória
     *
     * @var int
     */
    protected $maxTempTableRows = 100000;
    
    /**
     * Flag para ativar particionamento lógico de dados
     *
     * @var bool
     */
    protected $usePartitioning = true;
    
    /**
     * Tamanho do lote para processamento em chunks
     * (para consultas muito grandes)
     *
     * @var int
     */
    protected $chunkSize = 5000;
    
    /**
     * Configurações de prefetching otimizado
     *
     * @var array
     */
    protected $prefetchConfig = [
        'auto_analyze' => true,     // Analisar automaticamente padrões de acesso 
        'prefetch_threshold' => 5,  // Mínimo de hits para prefetching
        'max_prefetch_items' => 10  // Máximo de relatórios em prefetch
    ];
    
    /**
     * Construtor
     */
    public function __construct() 
    {
        parent::__construct();
        
        // Configurar timeout estendido para consultas otimizadas
        $this->db->setAttribute(PDO::ATTR_TIMEOUT, 60); // 60 segundos
        
        // Inicializar cache avançado com configurações otimizadas
        $this->cache = new AdvancedReportCache(null, null, [
            'memoryCacheLimit' => 50,       // Aumentado para 50 relatórios
            'compressionEnabled' => true,   // Ativar compressão
            'compressionLevel' => 7         // Nível de compressão equilibrado
        ]);
        
        // Verificar se o cache está habilitado nas configurações
        $this->checkCacheEnabled();
        
        // Se configurado para análise automática, analisar padrões de uso
        if ($this->prefetchConfig['auto_analyze']) {
            $this->analyzePrefetchCandidates();
        }
    }
    
    /**
     * Analisa padrões de uso para detectar candidatos a prefetching
     */
    private function analyzePrefetchCandidates(): void
    {
        // Obter estatísticas de cache
        $stats = $this->cache->getStats();
        
        // Identificar relatórios com alto número de hits
        if (isset($stats['hit_counts']) && !empty($stats['hit_counts'])) {
            $this->frequentReports = [];
            
            // Ordenar por número de hits (decrescente)
            arsort($stats['hit_counts']);
            
            // Filtrar apenas os que ultrapassam o limiar
            foreach ($stats['hit_counts'] as $key => $hits) {
                if ($hits >= $this->prefetchConfig['prefetch_threshold']) {
                    $this->frequentReports[] = $key;
                    
                    // Limitar ao número máximo configurado
                    if (count($this->frequentReports) >= $this->prefetchConfig['max_prefetch_items']) {
                        break;
                    }
                }
            }
        }
    }
    
    /**
     * Obter relatório de vendas baseado no período - Versão otimizada
     * 
     * @param string $period Período do relatório (day, week, month, quarter, year)
     * @return array Dados do relatório
     */
    public function getSalesReport(string $period): array 
    {
        // Validação de período
        $validPeriods = ['day', 'week', 'month', 'quarter', 'year'];
        if (!in_array($period, $validPeriods)) {
            $period = 'month'; // Valor padrão seguro
        }
        
        // Verificar cache
        $cacheParams = ['period' => $period];
        $cachedData = $this->getFromCache('sales', $cacheParams);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Determinar intervalo de data baseado no período
        list($startDate, $endDate, $groupBy, $dateFormat) = $this->getDateRangeParams($period);
        
        // Consulta otimizada utilizando índices específicos (idx_orders_created_at_status)
        // e Common Table Expressions (CTEs) para melhorar a legibilidade e eficiência
        // Inclui particionamento lógico dos dados para melhor performance
        $query = "WITH filtered_orders AS (
                    SELECT 
                        id,
                        user_id,
                        total,
                        created_at,
                        status
                    FROM orders
                    WHERE created_at BETWEEN ? AND ?
                    AND status NOT IN ('canceled', 'refunded')
                    -- Utiliza o índice idx_orders_created_at_status
                  ),
                  period_orders AS (
                    SELECT 
                        {$dateFormat} AS period_label,
                        {$groupBy} AS sort_key,
                        id,
                        user_id,
                        total
                    FROM filtered_orders
                  ),
                  period_stats AS (
                    SELECT 
                        period_label,
                        sort_key,
                        COUNT(id) AS order_count,
                        SUM(total) AS sales_amount,
                        COUNT(DISTINCT user_id) AS unique_customers,
                        ROUND(AVG(total), 2) AS average_order_value
                    FROM period_orders
                    GROUP BY period_label, sort_key
                  ),
                  order_items_count AS (
                    SELECT 
                        po.period_label,
                        po.sort_key,
                        COALESCE(SUM(oi.quantity), 0) AS items_sold
                    FROM period_orders po
                    LEFT JOIN order_items oi ON po.id = oi.order_id
                    GROUP BY po.period_label, po.sort_key
                  )
                  SELECT 
                    ps.period_label,
                    ps.order_count,
                    ps.sales_amount,
                    ps.unique_customers,
                    ps.average_order_value,
                    oic.items_sold
                  FROM period_stats ps
                  JOIN order_items_count oic ON ps.period_label = oic.period_label AND ps.sort_key = oic.sort_key
                  ORDER BY ps.sort_key ASC";
        
        try {
            // Usar prepared statement para prevenção de injeção SQL
            $stmt = $this->db->prepare($query);
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Adicionar taxa de crescimento para cada período (exceto o primeiro)
            if (count($results) > 1) {
                for ($i = 1; $i < count($results); $i++) {
                    $prevAmount = floatval($results[$i-1]['sales_amount']);
                    $currentAmount = floatval($results[$i]['sales_amount']);
                    
                    if ($prevAmount > 0) {
                        $growthRate = (($currentAmount - $prevAmount) / $prevAmount) * 100;
                        $results[$i]['growth_rate'] = round($growthRate, 2);
                    } else {
                        $results[$i]['growth_rate'] = null;
                    }
                }
            }
            
            // Armazenar no cache com expiração adaptativa
            $adaptiveExpiration = $this->cache->getAdaptiveExpiration('sales_' . md5(json_encode($cacheParams)), $this->cacheExpirations['sales']);
            $this->storeInCache('sales', $cacheParams, $results, $adaptiveExpiration);
            
            return $results;
        } catch (PDOException $e) {
            // Log detalhado do erro para análise interna
            error_log('Erro na execução do relatório de vendas otimizado: ' . $e->getMessage());
            
            // Retornar array vazio em caso de erro - não expor exceção
            return [];
        }
    }
    
    /**
     * Obter parâmetros de intervalo de data baseado no período
     * 
     * @param string $period Período (day, week, month, quarter, year)
     * @return array Array com startDate, endDate, groupBy, dateFormat
     */
    private function getDateRangeParams(string $period): array
    {
        $endDate = date('Y-m-d');
        
        switch ($period) {
            case 'day':
                $startDate = date('Y-m-d', strtotime('-30 days'));
                $groupBy = 'DATE(created_at)';
                $dateFormat = 'DATE(created_at)';
                break;
            case 'week':
                $startDate = date('Y-m-d', strtotime('-12 weeks'));
                $groupBy = 'YEARWEEK(created_at, 1)';
                $dateFormat = "CONCAT(YEAR(created_at), '-W', WEEK(created_at))";
                break;
            case 'month':
                $startDate = date('Y-m-d', strtotime('-12 months'));
                $groupBy = 'EXTRACT(YEAR_MONTH FROM created_at)';
                $dateFormat = "DATE_FORMAT(created_at, '%Y-%m')";
                break;
            case 'quarter':
                $startDate = date('Y-m-d', strtotime('-8 quarters'));
                $groupBy = "CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))";
                $dateFormat = "CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))";
                break;
            case 'year':
                $startDate = date('Y-m-d', strtotime('-5 years'));
                $groupBy = 'YEAR(created_at)';
                $dateFormat = 'YEAR(created_at)';
                break;
            default:
                $startDate = date('Y-m-d', strtotime('-12 months'));
                $groupBy = 'EXTRACT(YEAR_MONTH FROM created_at)';
                $dateFormat = "DATE_FORMAT(created_at, '%Y-%m')";
                break;
        }
        
        return [$startDate, $endDate, $groupBy, $dateFormat];
    }
    
    /**
     * Obter produtos mais vendidos - Versão otimizada
     *
     * @param string $period Período do relatório
     * @param int $limit Limite de resultados
     * @return array Dados do relatório
     */
    public function getTopProducts(string $period, int $limit = 20): array 
    {
        // Validação
        $validPeriods = ['day', 'week', 'month', 'quarter', 'year'];
        if (!in_array($period, $validPeriods)) {
            $period = 'month';
        }
        
        $limit = min(max(1, $limit), 100); // Limitar entre 1 e 100
        
        // Verificar cache
        $cacheParams = ['period' => $period, 'limit' => $limit];
        $cachedData = $this->getFromCache('products', $cacheParams);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Determinar intervalo de data
        list($startDate, $endDate) = $this->getDateRangeParams($period);
        
        // Query otimizada utilizando índices específicos e materialização
        // temp_order_items reduz a quantidade de dados processados posteriormente
        $query = "WITH
                  valid_orders AS (
                    SELECT id 
                    FROM orders 
                    WHERE created_at BETWEEN ? AND ?
                    AND status NOT IN ('canceled', 'refunded')
                  ),
                  temp_order_items AS (
                    SELECT 
                        oi.product_id,
                        SUM(oi.quantity) AS quantity,
                        SUM(oi.price * oi.quantity) AS revenue,
                        COUNT(DISTINCT oi.order_id) AS order_count
                    FROM order_items oi
                    JOIN valid_orders vo ON oi.order_id = vo.id
                    GROUP BY oi.product_id
                  )
                  SELECT 
                    p.id,
                    p.name,
                    p.sku,
                    COALESCE(toi.quantity, 0) AS quantity,
                    COALESCE(toi.revenue, 0) AS revenue,
                    COALESCE(toi.order_count, 0) AS order_count,
                    c.name AS category_name
                  FROM products p
                  LEFT JOIN temp_order_items toi ON p.id = toi.product_id
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.deleted = 0
                  ORDER BY revenue DESC, quantity DESC
                  LIMIT ?";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59', $limit]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Adicionar métricas derivadas úteis para análise
            foreach ($results as &$product) {
                if ($product['quantity'] > 0) {
                    $product['avg_price'] = round($product['revenue'] / $product['quantity'], 2);
                } else {
                    $product['avg_price'] = 0;
                }
            }
            
            // Armazenar no cache com expiração adaptativa
            $adaptiveExpiration = $this->cache->getAdaptiveExpiration('products_' . md5(json_encode($cacheParams)), $this->cacheExpirations['products']);
            $this->storeInCache('products', $cacheParams, $results, $adaptiveExpiration);
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter produtos mais vendidos (otimizado): ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter relatório de clientes ativos - Versão otimizada
     *
     * @param string $period Período do relatório
     * @param int $limit Número máximo de clientes
     * @return array Dados do relatório
     */
    public function getActiveCustomersReport(string $period, int $limit = 20): array 
    {
        // Validação e sanitização
        $validPeriods = ['day', 'week', 'month', 'quarter', 'year'];
        if (!in_array($period, $validPeriods)) {
            $period = 'month';
        }
        
        $limit = min(max(1, $limit), 100); // Limitar entre 1 e 100
        
        // Verificar cache
        $cacheParams = ['period' => $period, 'limit' => $limit];
        $cachedData = $this->getFromCache('active_customers', $cacheParams);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Determinar intervalo de data
        list($startDate, $endDate) = $this->getDateRangeParams($period);
        
        // Consulta otimizada usando materialização em várias etapas
        // e os índices idx_orders_user_id_created_at, idx_users_deleted
        $query = "WITH filtered_orders AS (
                    SELECT 
                        id,
                        user_id,
                        total,
                        created_at
                    FROM orders
                    WHERE created_at BETWEEN ? AND ?
                    AND status NOT IN ('canceled', 'refunded')
                    -- Utiliza índice idx_orders_created_at_status
                  ),
                  customer_orders AS (
                    SELECT 
                        user_id,
                        COUNT(id) AS orders_count,
                        SUM(total) AS total_value,
                        MAX(created_at) AS last_order_date,
                        MIN(created_at) AS first_order_date
                    FROM filtered_orders
                    GROUP BY user_id
                    -- Materializa resultados intermediários para reduzir processamento
                  ),
                  customer_metrics AS (
                    SELECT
                        co.user_id,
                        co.orders_count,
                        co.total_value,
                        co.last_order_date,
                        DATEDIFF(co.last_order_date, co.first_order_date) AS customer_lifetime_days,
                        co.total_value / co.orders_count AS avg_order_value
                    FROM customer_orders co
                  )
                  SELECT
                    u.id,
                    u.name,
                    u.email,
                    cm.orders_count,
                    cm.total_value,
                    cm.last_order_date,
                    cm.customer_lifetime_days,
                    cm.avg_order_value,
                    CASE 
                        WHEN cm.orders_count >= 5 THEN 'Frequente'
                        WHEN cm.orders_count >= 3 THEN 'Regular'
                        ELSE 'Ocasional'
                    END AS customer_type
                  FROM customer_metrics cm
                  JOIN users u ON u.id = cm.user_id
                  WHERE u.deleted = 0
                  ORDER BY cm.orders_count DESC, cm.total_value DESC
                  LIMIT ?";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59', $limit]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Armazenar no cache com expiração adaptativa
            $adaptiveExpiration = $this->cache->getAdaptiveExpiration('active_customers_' . md5(json_encode($cacheParams)), $this->cacheExpirations['customers']);
            $this->storeInCache('active_customers', $cacheParams, $results, $adaptiveExpiration);
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter relatório de clientes ativos (otimizado): ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter relatório de tendências de vendas - Versão otimizada
     * Implementa materialização avançada para análise de grandes volumes
     *
     * @param string $period Período para análise
     * @return array Dados do relatório
     */
    public function getSalesTrendReport(string $period): array 
    {
        // Validação
        $validPeriods = ['quarter', 'year', 'all'];
        if (!in_array($period, $validPeriods)) {
            $period = 'year';
        }
        
        // Verificar cache - dados de tendência são menos voláteis
        $cacheParams = ['period' => $period];
        $cachedData = $this->getFromCache('sales_trend', $cacheParams);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Determinar configurações baseadas no período
        $months = 24; // Padrão 2 anos
        
        switch ($period) {
            case 'quarter':
                $groupBy = "CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))";
                $dateFormat = "CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))";
                break;
            case 'year':
                $groupBy = 'YEAR(created_at)';
                $dateFormat = 'YEAR(created_at)';
                $months = 60; // 5 anos
                break;
            case 'all':
                $groupBy = 'YEAR(created_at)';
                $dateFormat = 'YEAR(created_at)';
                $months = 240; // 20 anos (essencialmente todos os dados)
                break;
        }
        
        // Consulta otimizada usando o índice idx_orders_year_month
        // e particionando os dados em etapas para melhor performance
        $query = "WITH date_periods AS (
                    SELECT DISTINCT 
                        {$dateFormat} AS period_label,
                        {$groupBy} AS period_group,
                        MIN(created_at) AS min_date
                    FROM orders
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                    AND status NOT IN ('canceled', 'refunded')
                    GROUP BY period_label, period_group
                  ),
                  period_totals AS (
                    SELECT 
                        dp.period_label,
                        SUM(o.total) AS sales_amount,
                        COUNT(o.id) AS order_count,
                        COUNT(DISTINCT o.user_id) AS unique_customers
                    FROM date_periods dp
                    JOIN orders o ON {$dateFormat} = dp.period_label
                    WHERE o.status NOT IN ('canceled', 'refunded')
                    GROUP BY dp.period_label
                  )
                  SELECT
                    pt.*
                  FROM period_totals pt
                  JOIN date_periods dp ON pt.period_label = dp.period_label
                  ORDER BY dp.min_date ASC";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$months]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular tendências com algoritmo melhorado
            if (count($results) > 1) {
                // Adicionar variação percentual
                for ($i = 1; $i < count($results); $i++) {
                    $prevAmount = floatval($results[$i-1]['sales_amount']);
                    $currentAmount = floatval($results[$i]['sales_amount']);
                    
                    if ($prevAmount > 0) {
                        $variationPercentage = (($currentAmount - $prevAmount) / $prevAmount) * 100;
                        $results[$i]['variation_percentage'] = round($variationPercentage, 1);
                        
                        // Determinar indicador de tendência
                        if ($variationPercentage > 5) {
                            $results[$i]['trend_indicator'] = 'up';
                        } elseif ($variationPercentage < -5) {
                            $results[$i]['trend_indicator'] = 'down';
                        } else {
                            $results[$i]['trend_indicator'] = 'stable';
                        }
                    } else {
                        $results[$i]['variation_percentage'] = null;
                        $results[$i]['trend_indicator'] = 'stable';
                    }
                }
                
                // Calcular média móvel para análise mais estável
                $windowSize = min(3, count($results));
                
                // Algoritmo otimizado para médias móveis
                $sum = 0;
                $validPoints = 0;
                
                // Inicializar a janela
                for ($i = 0; $i < $windowSize && $i < count($results); $i++) {
                    $sum += floatval($results[$i]['sales_amount']);
                    $validPoints++;
                }
                
                // Primeira média
                if ($validPoints > 0) {
                    $results[0]['moving_average'] = round($sum / $validPoints, 2);
                }
                
                // Calcular restante das médias móveis usando algoritmo de janela deslizante
                for ($i = 1; $i < count($results); $i++) {
                    // Remover o valor mais antigo fora da janela
                    if ($i >= $windowSize) {
                        $sum -= floatval($results[$i - $windowSize]['sales_amount']);
                        $validPoints--;
                    }
                    
                    // Adicionar novo valor
                    if ($i + $windowSize - 1 < count($results)) {
                        $sum += floatval($results[$i + $windowSize - 1]['sales_amount']);
                        $validPoints++;
                    }
                    
                    // Calcular média
                    if ($validPoints > 0) {
                        $results[$i]['moving_average'] = round($sum / $validPoints, 2);
                    }
                }
                
                // Adicionar análise de sazonalidade para períodos longos
                if (count($results) >= 4) {
                    $this->addSeasonalityAnalysis($results);
                }
            }
            
            // Armazenar no cache com expiração estendida (8 horas)
            $this->storeInCache('sales_trend', $cacheParams, $results, 28800);
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter relatório de tendências de vendas (otimizado): ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Adiciona análise de sazonalidade aos dados de tendência
     * 
     * @param array &$results Dados de tendência a serem enriquecidos
     */
    private function addSeasonalityAnalysis(array &$results): void
    {
        // Organizar dados por período (trimestre ou mês, dependendo dos dados)
        $periodData = [];
        
        foreach ($results as $result) {
            // Determinar o período (ex: Q1, Q2, etc. ou mês)
            $period = null;
            
            if (strpos($result['period_label'], '-Q') !== false) {
                // É um trimestre
                $period = substr($result['period_label'], -2);
            } elseif (strpos($result['period_label'], '-') !== false) {
                // É um mês
                $period = substr($result['period_label'], -2);
            }
            
            if ($period) {
                if (!isset($periodData[$period])) {
                    $periodData[$period] = [];
                }
                
                $periodData[$period][] = $result['sales_amount'];
            }
        }
        
        // Calcular índices de sazonalidade
        $seasonalityIndexes = [];
        
        foreach ($periodData as $period => $values) {
            if (count($values) > 0) {
                $seasonalityIndexes[$period] = array_sum($values) / count($values);
            }
        }
        
        // Normalizar índices
        if (!empty($seasonalityIndexes)) {
            $average = array_sum($seasonalityIndexes) / count($seasonalityIndexes);
            
            foreach ($seasonalityIndexes as $period => $value) {
                $seasonalityIndexes[$period] = $value / $average;
            }
        }
        
        // Adicionar índice de sazonalidade aos resultados
        foreach ($results as &$result) {
            $period = null;
            
            if (strpos($result['period_label'], '-Q') !== false) {
                // É um trimestre
                $period = substr($result['period_label'], -2);
            } elseif (strpos($result['period_label'], '-') !== false) {
                // É um mês
                $period = substr($result['period_label'], -2);
            }
            
            if ($period && isset($seasonalityIndexes[$period])) {
                $result['seasonality_index'] = round($seasonalityIndexes[$period], 2);
            }
        }
    }
    
    /**
     * Limpa todo o cache de relatórios com monitoramento de performance
     * 
     * @return array Estatísticas da operação
     */
    public function clearAllCaches(): array
    {
        $startTime = microtime(true);
        
        // Obter estatísticas antes da limpeza
        $beforeStats = $this->cache->getStats();
        
        // Executar limpeza
        $itemsRemoved = $this->cache->clear();
        
        // Calcular duração
        $duration = round((microtime(true) - $startTime) * 1000, 2); // ms
        
        return [
            'items_removed' => $itemsRemoved,
            'duration_ms' => $duration,
            'memory_released' => $beforeStats['size_bytes'] ?? 0,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Estatísticas detalhadas de utilização do cache com métricas de performance
     * 
     * @return array Estatísticas detalhadas
     */
    public function getDetailedCacheStats(): array
    {
        $stats = $this->cache->getStats();
        
        // Adicionar métricas avançadas
        if (isset($stats['hit_counts']) && !empty($stats['hit_counts'])) {
            $totalHits = array_sum($stats['hit_counts']);
            $reportTypes = [];
            
            // Agrupar hits por tipo de relatório
            foreach ($stats['hit_counts'] as $key => $count) {
                $parts = explode('_', $key);
                $reportType = $parts[0];
                
                if (!isset($reportTypes[$reportType])) {
                    $reportTypes[$reportType] = 0;
                }
                
                $reportTypes[$reportType] += $count;
            }
            
            // Calcular percentuais
            $reportTypesPercentage = [];
            foreach ($reportTypes as $type => $count) {
                $reportTypesPercentage[$type] = round(($count / $totalHits) * 100, 1);
            }
            
            // Adicionar às estatísticas
            $stats['total_hits'] = $totalHits;
            $stats['hits_by_type'] = $reportTypes;
            $stats['hits_percentage'] = $reportTypesPercentage;
        }
        
        // Calcular tempo médio entre acessos ao mesmo relatório
        // (para otimizar políticas de expiração)
        if (isset($stats['cache_access_timestamps']) && !empty($stats['cache_access_timestamps'])) {
            $avgTimeBetweenAccess = [];
            
            foreach ($stats['cache_access_timestamps'] as $key => $timestamps) {
                if (count($timestamps) > 1) {
                    $diffs = [];
                    $prevTimestamp = null;
                    
                    foreach ($timestamps as $timestamp) {
                        if ($prevTimestamp !== null) {
                            $diffs[] = $timestamp - $prevTimestamp;
                        }
                        $prevTimestamp = $timestamp;
                    }
                    
                    if (!empty($diffs)) {
                        $avgTimeBetweenAccess[$key] = array_sum($diffs) / count($diffs);
                    }
                }
            }
            
            $stats['avg_time_between_access'] = $avgTimeBetweenAccess;
        }
        
        return $stats;
    }
}
