<?php
namespace App\Models;

use PDO;
use PDOException;
use App\Lib\Database\Database;
use App\Lib\Security\InputValidator;
use App\Lib\Cache\AdvancedReportCache;

/**
 * ReportModel
 * 
 * Modelo responsável por gerar relatórios detalhados para o dashboard administrativo.
 * Implementa análises avançadas de dados, tendências e exportação em múltiplos formatos.
 * Inclui otimizações para grandes volumes de dados e proteções de segurança.
 *
 * @version 1.3.0
 * @author Taverna da Impressão
 */
class ReportModel 
{
    /**
     * Instância do banco de dados
     *
     * @var \PDO
     */
    protected $db;
    
    /**
     * Tempo máximo de execução de consultas (em segundos)
     *
     * @var int
     */
    protected $queryTimeout = 30;
    
    /**
     * Limite de resultados por padrão para prevenção de DoS
     *
     * @var int
     */
    protected $defaultResultLimit = 1000;
    
    /**
     * Sistema de cache de relatórios
     * 
     * @var AdvancedReportCache
     */
    protected $cache;
    
    /**
     * Se o cache está habilitado globalmente
     * 
     * @var bool
     */
    protected $cacheEnabled = true;
    
    /**
     * Tempos de expiração de cache para diferentes tipos de relatório (em segundos)
     * 
     * @var array
     */
    protected $cacheExpirations = [
        'sales' => 1800,           // 30 minutos para relatórios de vendas
        'products' => 3600,        // 1 hora para relatórios de produtos
        'customers' => 7200,       // 2 horas para relatórios de clientes
        'trends' => 14400,         // 4 horas para análises de tendências
        'printing' => 3600         // 1 hora para relatórios de impressão
    ];
    
    /**
     * Construtor
     */
    public function __construct() 
    {
        $this->db = Database::getInstance()->getConnection();
        
        // Configurar timeout para prevenir consultas excessivamente longas
        $this->db->setAttribute(PDO::ATTR_TIMEOUT, $this->queryTimeout);
        
        // Inicializar cache avançado com configurações otimizadas
        $this->cache = new AdvancedReportCache(null, null, [
            'memoryCacheLimit' => 30,     // Cache em memória para 30 relatórios frequentes
            'compressionEnabled' => true, // Ativar compressão para economizar espaço
            'compressionLevel' => 7       // Nível de compressão equilibrado (1-9)
        ]);
        
        // Verificar se o cache está habilitado nas configurações
        $this->checkCacheEnabled();
    }
    
    /**
     * Verifica se o cache está habilitado nas configurações
     */
    private function checkCacheEnabled(): void
    {
        // Se existir a configuração para desabilitar cache
        if (defined('DISABLE_REPORT_CACHE') && DISABLE_REPORT_CACHE === true) {
            $this->cacheEnabled = false;
        }
    }
    
    /**
     * Tenta obter dados do cache para um tipo de relatório e parâmetros específicos
     * 
     * @param string $reportType Tipo de relatório
     * @param array $parameters Parâmetros do relatório
     * @return array|null Dados do cache ou null se não encontrado/expirado
     */
    protected function getFromCache(string $reportType, array $parameters = []): ?array
    {
        if (!$this->cacheEnabled) {
            return null;
        }
        
        // Gerar chave de cache baseada no tipo e parâmetros
        $cacheKey = $this->cache->generateKey($reportType, $parameters);
        
        // Tentar obter do cache
        return $this->cache->get($cacheKey);
    }
    
    /**
     * Armazena dados de relatório no cache
     * 
     * @param string $reportType Tipo de relatório
     * @param array $parameters Parâmetros do relatório
     * @param array $data Dados do relatório
     * @param int|null $expiration Tempo de expiração personalizado
     * @return bool Sucesso da operação
     */
    protected function storeInCache(string $reportType, array $parameters, array $data, int $expiration = null): bool
    {
        if (!$this->cacheEnabled) {
            return false;
        }
        
        // Gerar chave de cache
        $cacheKey = $this->cache->generateKey($reportType, $parameters);
        
        // Determinar tempo de expiração baseado no tipo de relatório se não especificado
        if ($expiration === null) {
            $expiration = $this->cacheExpirations[$reportType] ?? 3600; // Padrão: 1 hora
        }
        
        // Armazenar dados no cache
        return $this->cache->set($cacheKey, $data, $expiration);
    }
    
    /**
     * Invalida cache para um tipo de relatório
     * 
     * @param string $reportType Tipo de relatório
     * @return int Número de itens invalidados
     */
    public function invalidateCache(string $reportType): int
    {
        if (!$this->cacheEnabled) {
            return 0;
        }
        
        return $this->cache->invalidateReportType($reportType);
    }
    
    /**
     * Limpa todos os caches de relatórios expirados
     * 
     * @return int Número de itens removidos
     */
    public function clearExpiredCaches(): int
    {
        if (!$this->cacheEnabled) {
            return 0;
        }
        
        return $this->cache->clearExpired();
    }
    
    /**
     * Retorna estatísticas de uso do cache de relatórios
     * 
     * @return array Estatísticas de uso
     */
    public function getCacheStats(): array
    {
        if (!$this->cacheEnabled) {
            return [
                'enabled' => false,
                'message' => 'Cache de relatórios está desabilitado'
            ];
        }
        
        $stats = $this->cache->getStats();
        $stats['enabled'] = true;
        $stats['expirations'] = $this->cacheExpirations;
        
        return $stats;
    }
    
    /**
     * Obter relatório de vendas baseado no período
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
        $endDate = date('Y-m-d');
        
        switch ($period) {
            case 'day':
                $startDate = date('Y-m-d', strtotime('-30 days'));
                $groupBy = 'DATE(o.created_at)';
                $dateFormat = 'DATE(o.created_at)';
                break;
            case 'week':
                $startDate = date('Y-m-d', strtotime('-12 weeks'));
                $groupBy = 'YEARWEEK(o.created_at, 1)';
                $dateFormat = "CONCAT(YEAR(o.created_at), '-W', WEEK(o.created_at))";
                break;
            case 'month':
                $startDate = date('Y-m-d', strtotime('-12 months'));
                $groupBy = 'EXTRACT(YEAR_MONTH FROM o.created_at)';
                $dateFormat = "DATE_FORMAT(o.created_at, '%Y-%m')";
                break;
            case 'quarter':
                $startDate = date('Y-m-d', strtotime('-8 quarters'));
                $groupBy = "CONCAT(YEAR(o.created_at), '-Q', QUARTER(o.created_at))";
                $dateFormat = "CONCAT(YEAR(o.created_at), '-Q', QUARTER(o.created_at))";
                break;
            case 'year':
                $startDate = date('Y-m-d', strtotime('-5 years'));
                $groupBy = 'YEAR(o.created_at)';
                $dateFormat = 'YEAR(o.created_at)';
                break;
        }
        
        // Consulta otimizada utilizando índices específicos (idx_orders_created_at)
        // e Common Table Expressions para melhoria de performance
        $query = "WITH period_orders AS (
                    SELECT 
                        {$dateFormat} AS period_label,
                        {$groupBy} AS sort_key,
                        o.id,
                        o.user_id,
                        o.total
                    FROM orders o
                    WHERE o.created_at BETWEEN ? AND ?
                    AND o.status NOT IN ('canceled', 'refunded')
                  )
                  SELECT 
                    po.period_label,
                    COUNT(po.id) AS order_count,
                    SUM(po.total) AS sales_amount,
                    COUNT(DISTINCT po.user_id) AS unique_customers,
                    ROUND(AVG(po.total), 2) AS average_order_value,
                    COALESCE(SUM(oi.quantity), 0) AS items_sold
                  FROM period_orders po
                  LEFT JOIN order_items oi ON po.id = oi.order_id
                  GROUP BY po.period_label, po.sort_key
                  ORDER BY po.sort_key ASC";
        
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
            error_log('Erro na execução do relatório de vendas: ' . $e->getMessage());
            
            // Retornar array vazio em caso de erro - não expor exceção
            return [];
        }
    }
    
    /**
     * Obter produtos mais vendidos
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
        $endDate = date('Y-m-d');
        
        switch ($period) {
            case 'day':
                $startDate = date('Y-m-d', strtotime('-30 days'));
                break;
            case 'week':
                $startDate = date('Y-m-d', strtotime('-12 weeks'));
                break;
            case 'month':
                $startDate = date('Y-m-d', strtotime('-12 months'));
                break;
            case 'quarter':
                $startDate = date('Y-m-d', strtotime('-4 quarters'));
                break;
            case 'year':
                $startDate = date('Y-m-d', strtotime('-5 years'));
                break;
        }
        
        // Query otimizada utilizando índices específicos (idx_order_items_product_id, idx_orders_created_at, idx_products_category_id)
        // Implementa filtragem preliminar via subquery para melhorar a performance
        $query = "SELECT 
                    p.id,
                    p.name,
                    p.sku,
                    COALESCE(product_data.quantity, 0) AS quantity,
                    COALESCE(product_data.revenue, 0) AS revenue,
                    COALESCE(product_data.order_count, 0) AS order_count,
                    c.name AS category_name
                  FROM products p
                  LEFT JOIN (
                    SELECT
                        oi.product_id,
                        SUM(oi.quantity) AS quantity,
                        SUM(oi.price * oi.quantity) AS revenue,
                        COUNT(DISTINCT o.id) AS order_count
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.created_at BETWEEN ? AND ?
                    AND o.status NOT IN ('canceled', 'refunded')
                    GROUP BY oi.product_id
                  ) AS product_data ON p.id = product_data.product_id
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.deleted = 0
                  ORDER BY revenue DESC, quantity DESC
                  LIMIT ?";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59', $limit]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Armazenar no cache com expiração adaptativa
            $adaptiveExpiration = $this->cache->getAdaptiveExpiration('products_' . md5(json_encode($cacheParams)), $this->cacheExpirations['products']);
            $this->storeInCache('products', $cacheParams, $results, $adaptiveExpiration);
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter produtos mais vendidos: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter relatório de categorias de produtos
     *
     * @param string $period Período do relatório
     * @return array Dados do relatório
     */
    public function getProductCategoriesReport(string $period): array 
    {
        // Validação
        $validPeriods = ['day', 'week', 'month', 'quarter', 'year'];
        if (!in_array($period, $validPeriods)) {
            $period = 'month';
        }
        
        // Verificar cache
        $cacheParams = ['period' => $period];
        $cachedData = $this->getFromCache('product_categories', $cacheParams);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Determinar intervalo de data
        $endDate = date('Y-m-d');
        
        switch ($period) {
            case 'day':
                $startDate = date('Y-m-d', strtotime('-30 days'));
                break;
            case 'week':
                $startDate = date('Y-m-d', strtotime('-12 weeks'));
                break;
            case 'month':
                $startDate = date('Y-m-d', strtotime('-12 months'));
                break;
            case 'quarter':
                $startDate = date('Y-m-d', strtotime('-4 quarters'));
                break;
            case 'year':
                $startDate = date('Y-m-d', strtotime('-5 years'));
                break;
        }
        
        // Consulta otimizada com materialização temporária e indexação
        $query = "WITH category_sales AS (
                    SELECT 
                        p.category_id,
                        SUM(oi.quantity) AS sales_count,
                        SUM(oi.price * oi.quantity) AS revenue
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    JOIN products p ON oi.product_id = p.id
                    WHERE o.created_at BETWEEN ? AND ? 
                    AND o.status NOT IN ('canceled', 'refunded')
                    GROUP BY p.category_id
                  )
                  SELECT 
                    c.id,
                    c.name,
                    COUNT(DISTINCT p.id) AS product_count,
                    COALESCE(cs.sales_count, 0) AS sales_count,
                    COALESCE(cs.revenue, 0) AS revenue
                  FROM categories c
                  LEFT JOIN products p ON c.id = p.category_id AND p.deleted = 0
                  LEFT JOIN category_sales cs ON c.id = cs.category_id
                  GROUP BY c.id
                  ORDER BY revenue DESC";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Armazenar no cache
            $this->storeInCache('product_categories', $cacheParams, $results);
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter relatório de categorias: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter relatório de status de estoque
     *
     * @return array Dados do relatório
     */
    public function getStockStatusReport(): array 
    {
        // Verificar cache - este relatório tem dados mais atuais, expira mais rápido
        $cachedData = $this->getFromCache('stock_status', []);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Definir limites para cada status
        $lowStockThreshold = 5;
        $outOfStockThreshold = 0;
        
        // Consulta otimizada usando índice idx_products_stock_status
        $query = "WITH stock_categories AS (
                    SELECT
                        CASE
                            WHEN p.stock <= ? THEN 'Sem estoque'
                            WHEN p.stock <= ? THEN 'Estoque baixo'
                            WHEN p.stock > ? THEN 'Estoque normal'
                        END AS status,
                        COUNT(p.id) AS count
                    FROM products p
                    WHERE p.deleted = 0
                    GROUP BY 
                        CASE
                            WHEN p.stock <= ? THEN 'Sem estoque'
                            WHEN p.stock <= ? THEN 'Estoque baixo'
                            WHEN p.stock > ? THEN 'Estoque normal'
                        END
                  )
                  SELECT
                    status,
                    count,
                    CASE 
                        WHEN status = 'Sem estoque' THEN 1
                        WHEN status = 'Estoque baixo' THEN 2
                        WHEN status = 'Estoque normal' THEN 3
                    END AS display_order
                  FROM stock_categories
                  ORDER BY display_order";
        
        try {
            $stmt = $this->db->prepare($query);
            $params = [
                $outOfStockThreshold, 
                $lowStockThreshold, 
                $lowStockThreshold,
                $outOfStockThreshold, 
                $lowStockThreshold, 
                $lowStockThreshold
            ];
            
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Remover coluna auxiliar de ordenação
            foreach ($results as &$row) {
                unset($row['display_order']);
            }
            
            // Cache com duração mais curta (15 minutos) por ser mais dinâmico
            $this->storeInCache('stock_status', [], $results, 900);
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter relatório de status de estoque: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter relatório de novos clientes
     *
     * @param string $period Período do relatório
     * @return array Dados do relatório
     */
    public function getNewCustomersReport(string $period): array 
    {
        // Validação
        $validPeriods = ['day', 'week', 'month', 'quarter', 'year'];
        if (!in_array($period, $validPeriods)) {
            $period = 'month';
        }
        
        // Verificar cache
        $cacheParams = ['period' => $period];
        $cachedData = $this->getFromCache('new_customers', $cacheParams);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Determinar configurações baseadas no período
        switch ($period) {
            case 'day':
                $startDate = date('Y-m-d', strtotime('-30 days'));
                $groupBy = 'DATE(created_at)';
                $dateFormat = 'DATE(created_at)';
                $limit = 30;
                break;
            case 'week':
                $startDate = date('Y-m-d', strtotime('-12 weeks'));
                $groupBy = 'YEARWEEK(created_at, 1)';
                $dateFormat = "CONCAT(YEAR(created_at), '-W', WEEK(created_at))";
                $limit = 12;
                break;
            case 'month':
                $startDate = date('Y-m-d', strtotime('-12 months'));
                $groupBy = 'EXTRACT(YEAR_MONTH FROM created_at)';
                $dateFormat = "DATE_FORMAT(created_at, '%Y-%m')";
                $limit = 12;
                break;
            case 'quarter':
                $startDate = date('Y-m-d', strtotime('-8 quarters'));
                $groupBy = "CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))";
                $dateFormat = "CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))";
                $limit = 8;
                break;
            case 'year':
                $startDate = date('Y-m-d', strtotime('-5 years'));
                $groupBy = 'YEAR(created_at)';
                $dateFormat = 'YEAR(created_at)';
                $limit = 5;
                break;
        }
        
        $endDate = date('Y-m-d');
        
        // Consulta otimizada com índice em idx_users_created_at e materialização temporária para melhor performance
        $query = "WITH user_periods AS (
                    SELECT 
                        {$dateFormat} AS period_label,
                        {$groupBy} AS sort_order
                    FROM users
                    WHERE created_at BETWEEN ? AND ?
                    AND deleted = 0
                    GROUP BY {$groupBy}
                    ORDER BY {$groupBy} ASC
                    LIMIT ?
                  )
                  SELECT
                    up.period_label,
                    COUNT(u.id) AS count
                  FROM user_periods up
                  JOIN users u ON {$dateFormat} = up.period_label
                  WHERE u.created_at BETWEEN ? AND ?
                  AND u.deleted = 0
                  GROUP BY up.period_label, up.sort_order
                  ORDER BY up.sort_order ASC";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59', $limit, $startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Adicionar taxa de crescimento
            if (count($results) > 1) {
                for ($i = 1; $i < count($results); $i++) {
                    $prevCount = intval($results[$i-1]['count']);
                    $currentCount = intval($results[$i]['count']);
                    
                    if ($prevCount > 0) {
                        $growthRate = (($currentCount - $prevCount) / $prevCount) * 100;
                        $results[$i]['growth'] = round($growthRate, 1);
                    } else {
                        $results[$i]['growth'] = null;
                    }
                }
            }
            
            // Armazenar no cache com expiração adaptativa
            $adaptiveExpiration = $this->cache->getAdaptiveExpiration('new_customers_' . md5(json_encode($cacheParams)), $this->cacheExpirations['customers']);
            $this->storeInCache('new_customers', $cacheParams, $results, $adaptiveExpiration);
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter relatório de novos clientes: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter relatório de clientes ativos
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
        $endDate = date('Y-m-d');
        
        switch ($period) {
            case 'day':
                $startDate = date('Y-m-d', strtotime('-30 days'));
                break;
            case 'week':
                $startDate = date('Y-m-d', strtotime('-12 weeks'));
                break;
            case 'month':
                $startDate = date('Y-m-d', strtotime('-12 months'));
                break;
            case 'quarter':
                $startDate = date('Y-m-d', strtotime('-4 quarters'));
                break;
            case 'year':
                $startDate = date('Y-m-d', strtotime('-5 years'));
                break;
        }
        
        // Consulta otimizada usando índice idx_orders_user_id_created_at e pré-filtragem com Common Table Expression
        // Implementa materialização temporária para melhorar performance com grandes volumes de dados
        $query = "WITH active_customers AS (
                    SELECT 
                        o.user_id,
                        COUNT(o.id) AS orders_count,
                        SUM(o.total) AS total_value,
                        MAX(o.created_at) AS last_order_date
                    FROM orders o
                    WHERE o.created_at BETWEEN ? AND ?
                    AND o.status NOT IN ('canceled', 'refunded')
                    GROUP BY o.user_id
                  )
                  SELECT
                    u.id,
                    u.name,
                    u.email,
                    ac.orders_count,
                    ac.total_value,
                    ac.last_order_date
                  FROM active_customers ac
                  JOIN users u ON u.id = ac.user_id
                  WHERE u.deleted = 0
                  ORDER BY ac.orders_count DESC, ac.total_value DESC
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
            error_log('Erro ao obter relatório de clientes ativos: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter relatório de segmentação de clientes
     *
     * @param string $period Período do relatório
     * @return array Dados do relatório
     */
    public function getCustomerSegmentsReport(string $period): array 
    {
        // Validação
        $validPeriods = ['day', 'week', 'month', 'quarter', 'year'];
        if (!in_array($period, $validPeriods)) {
            $period = 'month';
        }
        
        // Verificar cache
        $cacheParams = ['period' => $period];
        $cachedData = $this->getFromCache('customer_segments', $cacheParams);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Determinar intervalo de data
        $endDate = date('Y-m-d');
        
        switch ($period) {
            case 'day':
                $startDate = date('Y-m-d', strtotime('-30 days'));
                break;
            case 'week':
                $startDate = date('Y-m-d', strtotime('-12 weeks'));
                break;
            case 'month':
                $startDate = date('Y-m-d', strtotime('-12 months'));
                break;
            case 'quarter':
                $startDate = date('Y-m-d', strtotime('-4 quarters'));
                break;
            case 'year':
                $startDate = date('Y-m-d', strtotime('-5 years'));
                break;
        }
        
        // Consulta otimizada para segmentação com materialização temporária
        $query = "WITH customer_stats AS (
                    SELECT 
                        u.id,
                        COUNT(o.id) AS order_count,
                        COALESCE(SUM(o.total), 0) AS total_spent
                    FROM users u
                    LEFT JOIN orders o ON u.id = o.user_id 
                          AND o.created_at BETWEEN ? AND ?
                          AND o.status NOT IN ('canceled', 'refunded')
                    WHERE u.deleted = 0
                    GROUP BY u.id
                  ),
                  customer_segments AS (
                    SELECT
                        CASE
                            WHEN total_spent >= 1000 THEN 'Cliente VIP'
                            WHEN total_spent >= 500 THEN 'Cliente Regular'
                            WHEN total_spent >= 100 THEN 'Cliente Ocasional'
                            ELSE 'Cliente Novo'
                        END AS segment_name,
                        id,
                        order_count,
                        total_spent
                    FROM customer_stats
                  )
                  SELECT
                    segment_name,
                    COUNT(*) AS customers_count,
                    SUM(total_spent) AS total_revenue,
                    ROUND(AVG(total_spent), 2) AS average_ticket,
                    ROUND(AVG(order_count), 1) AS average_orders
                  FROM customer_segments
                  GROUP BY segment_name
                  ORDER BY average_ticket DESC";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Armazenar no cache
            $this->storeInCache('customer_segments', $cacheParams, $results);
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter relatório de segmentação de clientes: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter relatório de retenção de clientes
     *
     * @return array Dados do relatório
     */
    public function getCustomerRetentionReport(): array 
    {
        // Verificar cache - dados menos voláteis
        $cachedData = $this->getFromCache('customer_retention', []);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Consulta otimizada com índices e materialização temporária CTEs
        // para análise de coortes
        $query = "WITH monthly_cohorts AS (
                    SELECT 
                        DATE_FORMAT(u.created_at, '%Y-%m') AS cohort,
                        u.id AS user_id
                    FROM users u
                    WHERE u.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                    AND u.deleted = 0
                    GROUP BY cohort, u.id
                  ),
                  user_activity AS (
                    SELECT 
                        mc.cohort,
                        mc.user_id,
                        DATE_FORMAT(o.created_at, '%Y-%m') AS activity_month
                    FROM monthly_cohorts mc
                    JOIN orders o ON mc.user_id = o.user_id
                    WHERE o.status NOT IN ('canceled', 'refunded')
                    GROUP BY mc.cohort, mc.user_id, activity_month
                  ),
                  cohort_metrics AS (
                    SELECT 
                        mc.cohort,
                        COUNT(DISTINCT mc.user_id) AS initial_count,
                        COUNT(DISTINCT CASE WHEN ua.activity_month = DATE_FORMAT(CURDATE(), '%Y-%m') THEN ua.user_id END) AS current_count
                    FROM monthly_cohorts mc
                    LEFT JOIN user_activity ua ON mc.cohort = ua.cohort AND mc.user_id = ua.user_id
                    GROUP BY mc.cohort
                  )
                  SELECT 
                    cohort,
                    initial_count,
                    current_count,
                    ROUND(current_count / initial_count * 100, 1) AS retention_rate
                  FROM cohort_metrics
                  ORDER BY cohort ASC";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Armazenar no cache com duração estendida (6 horas)
            // pois é com base em dados históricos/coortes que não mudam frequentemente
            $this->storeInCache('customer_retention', [], $results, 21600);
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter relatório de retenção de clientes: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter relatório de tendências de vendas
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
        switch ($period) {
            case 'quarter':
                $startDate = date('Y-m-d', strtotime('-2 years'));
                $groupBy = "CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))";
                $dateFormat = "CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))";
                break;
            case 'year':
                $startDate = date('Y-m-d', strtotime('-5 years'));
                $groupBy = 'YEAR(created_at)';
                $dateFormat = 'YEAR(created_at)';
                break;
            case 'all':
                $startDate = '2000-01-01'; // Data suficientemente antiga
                $groupBy = 'YEAR(created_at)';
                $dateFormat = 'YEAR(created_at)';
                break;
        }
        
        $endDate = date('Y-m-d');
        
        // Consulta otimizada usando índices específicos criados para análise temporal
        // Utiliza as CTEs (Common Table Expressions) para melhorar a legibilidade e performance
        $query = "WITH time_periods AS (
                    SELECT
                        {$groupBy} AS period_label,
                        MIN(created_at) AS min_date
                    FROM orders
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                    AND status NOT IN ('canceled', 'refunded')
                    GROUP BY {$groupBy}
                  )
                  SELECT
                    tp.period_label,
                    SUM(o.total) AS sales_amount,
                    COUNT(*) AS order_count
                  FROM time_periods tp
                  JOIN orders o ON {$groupBy} = tp.period_label
                  WHERE o.status NOT IN ('canceled', 'refunded')
                  GROUP BY tp.period_label
                  ORDER BY tp.min_date ASC";
        
        try {
            $stmt = $this->db->prepare($query);
            
            // Calcular meses para o intervalo 
            $months = 24; // Padrão 2 anos
            if ($period === 'year') {
                $months = 60; // 5 anos
            } else if ($period === 'all') {
                $months = 240; // 20 anos (essencialmente todos os dados)
            }
            
            $stmt->execute([$months]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular tendências
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
                for ($i = 0; $i < count($results); $i++) {
                    $sum = 0;
                    $count = 0;
                    
                    for ($j = max(0, $i - ($windowSize - 1)); $j <= $i; $j++) {
                        $sum += floatval($results[$j]['sales_amount']);
                        $count++;
                    }
                    
                    $results[$i]['moving_average'] = round($sum / $count, 2);
                }
            }
            
            // Armazenar no cache com expiração estendida (8 horas)
            $this->storeInCache('sales_trend', $cacheParams, $results, 28800);
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter relatório de tendências de vendas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter relatório de tendências de produtos
     *
     * @param string $period Período para análise
     * @return array Dados do relatório
     */
    public function getProductTrendsReport(string $period): array 
    {
        // Validação e sanitização
        $validPeriods = ['quarter', 'year', 'all'];
        if (!in_array($period, $validPeriods)) {
            $period = 'year';
        }
        
        // Verificar cache
        $cacheParams = ['period' => $period];
        $cachedData = $this->getFromCache('product_trends', $cacheParams);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Determinar intervalo de data
        switch ($period) {
            case 'quarter':
                $startDate = date('Y-m-d', strtotime('-2 years'));
                $compareStartDate = date('Y-m-d', strtotime('-1 years'));
                break;
            case 'year':
                $startDate = date('Y-m-d', strtotime('-3 years'));
                $compareStartDate = date('Y-m-d', strtotime('-1 years'));
                break;
            case 'all':
                $startDate = '2000-01-01'; // Data antiga o suficiente
                $compareStartDate = date('Y-m-d', strtotime('-1 years'));
                break;
        }
        
        $endDate = date('Y-m-d');
        $compareEndDate = $endDate;
        $limitProducts = 15; // Limitar para principais produtos
        
        // Consulta otimizada usando CTEs e índices específicos
        $query = "WITH current_period AS (
                    SELECT 
                        p.id,
                        p.name,
                        SUM(oi.quantity) AS quantity_sold,
                        SUM(oi.price * oi.quantity) AS revenue
                    FROM products p
                    JOIN order_items oi ON p.id = oi.product_id
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.created_at BETWEEN ? AND ?
                    AND o.status NOT IN ('canceled', 'refunded')
                    GROUP BY p.id
                ),
                previous_period AS (
                    SELECT 
                        p.id,
                        SUM(oi.quantity) AS quantity_sold,
                        SUM(oi.price * oi.quantity) AS revenue
                    FROM products p
                    JOIN order_items oi ON p.id = oi.product_id
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.created_at BETWEEN ? AND ?
                    AND o.status NOT IN ('canceled', 'refunded')
                    GROUP BY p.id
                ),
                trend_analysis AS (
                    SELECT 
                        cp.id,
                        cp.name,
                        cp.quantity_sold AS current_quantity,
                        cp.revenue AS current_revenue,
                        COALESCE(pp.quantity_sold, 0) AS previous_quantity,
                        COALESCE(pp.revenue, 0) AS previous_revenue,
                        CASE
                            WHEN COALESCE(pp.revenue, 0) = 0 THEN 100
                            ELSE ROUND(((cp.revenue - pp.revenue) / pp.revenue) * 100, 1)
                        END AS variation_percentage,
                        CASE
                            WHEN COALESCE(pp.revenue, 0) = 0 THEN 'up'
                            WHEN ((cp.revenue - pp.revenue) / pp.revenue) > 0.1 THEN 'up'
                            WHEN ((cp.revenue - pp.revenue) / pp.revenue) < -0.1 THEN 'down'
                            ELSE 'stable'
                        END AS trend_indicator
                    FROM current_period cp
                    LEFT JOIN previous_period pp ON cp.id = pp.id
                    ORDER BY cp.revenue DESC
                    LIMIT ?
                )
                SELECT * FROM trend_analysis";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $compareStartDate . ' 00:00:00', 
                $compareEndDate . ' 23:59:59', 
                $startDate . ' 00:00:00', 
                $compareStartDate . ' 00:00:00',
                $limitProducts
            ]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Adicionar projeções qualitativas baseadas nos dados
            foreach ($results as &$product) {
                $forecast = '';
                
                if ($product['trend_indicator'] === 'up' && $product['variation_percentage'] > 20) {
                    $forecast = 'Crescimento forte esperado';
                } elseif ($product['trend_indicator'] === 'up') {
                    $forecast = 'Crescimento moderado esperado';
                } elseif ($product['trend_indicator'] === 'stable') {
                    $forecast = 'Estabilidade esperada';
                } elseif ($product['trend_indicator'] === 'down' && $product['variation_percentage'] < -20) {
                    $forecast = 'Queda significativa esperada';
                } else {
                    $forecast = 'Leve queda esperada';
                }
                
                $product['forecast'] = $forecast;
            }
            
            // Armazenar no cache
            $this->storeInCache('product_trends', $cacheParams, $results);
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter relatório de tendências de produtos: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter relatório de sazonalidade
     *
     * @return array Dados do relatório
     */
    public function getSeasonalityReport(): array 
    {
        // Verificar cache - dados de sazonalidade não mudam frequentemente
        $cachedData = $this->getFromCache('seasonality', []);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Análise por mês do ano usando pelo menos 3 anos de dados
        // Consulta otimizada com materialização temporária
        $query = "WITH monthly_sales AS (
                    SELECT 
                        MONTH(created_at) AS month_num,
                        MONTHNAME(created_at) AS month_name,
                        SUM(total) AS monthly_sales,
                        COUNT(*) AS order_count
                    FROM orders
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 3 YEAR)
                    AND status NOT IN ('canceled', 'refunded')
                    GROUP BY MONTH(created_at), MONTHNAME(created_at)
                ),
                avg_sales AS (
                    SELECT AVG(monthly_sales) AS avg_monthly_sales
                    FROM monthly_sales
                ),
                seasonal_analysis AS (
                    SELECT 
                        ms.month_num,
                        ms.month_name,
                        ms.monthly_sales,
                        ms.order_count,
                        ROUND(ms.monthly_sales / a.avg_monthly_sales, 2) AS seasonal_index,
                        ms.monthly_sales > (a.avg_monthly_sales * 1.2) AS is_peak,
                        ms.monthly_sales < (a.avg_monthly_sales * 0.8) AS is_valley
                    FROM monthly_sales ms
                    CROSS JOIN avg_sales a
                ),
                top_products AS (
                    SELECT 
                        sa.month_num,
                        (
                            SELECT GROUP_CONCAT(p.name SEPARATOR ', ')
                            FROM (
                                SELECT 
                                    p.name,
                                    COUNT(*) AS sales_count
                                FROM orders o
                                JOIN order_items oi ON o.id = oi.order_id
                                JOIN products p ON oi.product_id = p.id
                                WHERE MONTH(o.created_at) = sa.month_num
                                AND o.status NOT IN ('canceled', 'refunded')
                                GROUP BY p.id
                                ORDER BY sales_count DESC
                                LIMIT 3
                            ) p
                        ) AS seasonal_products
                    FROM seasonal_analysis sa
                    GROUP BY sa.month_num
                )
                SELECT 
                    sa.month_num,
                    sa.month_name,
                    sa.monthly_sales,
                    sa.order_count,
                    sa.seasonal_index,
                    sa.is_peak,
                    sa.is_valley,
                    tp.seasonal_products
                FROM seasonal_analysis sa
                JOIN top_products tp ON sa.month_num = tp.month_num
                ORDER BY sa.month_num";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Armazenar no cache - com duração longa (1 dia)
            $this->storeInCache('seasonality', [], $results, 86400);
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter relatório de sazonalidade: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter relatório de previsão de vendas com algoritmo otimizado
     *
     * @param string $period Período para previsão
     * @return array Dados do relatório
     */
    public function getSalesForecastReport(string $period): array 
    {
        // Validação
        $validPeriods = ['quarter', 'year', 'all'];
        if (!in_array($period, $validPeriods)) {
            $period = 'year';
        }
        
        // Verificar cache com duração estendida
        $cacheParams = ['period' => $period];
        $cachedData = $this->getFromCache('sales_forecast', $cacheParams);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Configurar períodos baseados no parâmetro
        switch ($period) {
            case 'quarter':
                $historyMonths = 24; // 2 anos de histórico
                $forecastMonths = 3; // Previsão para 3 meses
                $groupBy = "DATE_FORMAT(created_at, '%Y-%m')"; // Mensal
                break;
            case 'year':
                $historyMonths = 36; // 3 anos de histórico
                $forecastMonths = 6; // Previsão para 6 meses
                $groupBy = "DATE_FORMAT(created_at, '%Y-%m')"; // Mensal
                break;
            case 'all':
                $historyMonths = 60; // 5 anos de histórico
                $forecastMonths = 12; // Previsão para 1 ano
                $groupBy = "CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))"; // Trimestral
                break;
        }
        
        // Consulta otimizada usando índice idx_orders_created_at
        // e materialização temporária para melhorar performance
        $query = "WITH historical_sales AS (
                    SELECT 
                        {$groupBy} AS period_label,
                        EXTRACT(YEAR_MONTH FROM MIN(created_at)) AS period_key,
                        SUM(total) AS sales_amount
                    FROM orders
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                    AND status NOT IN ('canceled', 'refunded')
                    GROUP BY {$groupBy}
                    ORDER BY period_key ASC
                  )
                  SELECT 
                    period_label,
                    sales_amount,
                    period_key
                  FROM historical_sales
                  ORDER BY period_key ASC";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$historyMonths]);
            $historicalData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Realizar previsão com algoritmo melhorado
            // Holt-Winters exponential smoothing simplificado
            $forecastData = [];
            $alpha = 0.3; // Fator de suavização para nível
            $beta = 0.2;  // Fator de suavização para tendência
            
            // Obter o último valor e calcular média e tendência inicial
            $count = count($historicalData);
            if ($count < 2) {
                // Dados insuficientes para previsão
                return [];
            }
            
            $lastValue = floatval(end($historicalData)['sales_amount']);
            $previousValue = floatval($historicalData[$count - 2]['sales_amount']);
            
            // Inicialização do nível e tendência
            $level = $lastValue;
            $trend = ($lastValue - $previousValue) / 1; // Ajustado para período
            
            // Calcular estatísticas para intervalo de confiança
            $sum = 0;
            $sumSquared = 0;
            
            foreach ($historicalData as $data) {
                $value = floatval($data['sales_amount']);
                $sum += $value;
                $sumSquared += $value * $value;
            }
            
            $mean = $sum / $count;
            $variance = ($sumSquared / $count) - ($mean * $mean);
            $stdDev = sqrt($variance);
            
            // Nível de confiança - para intervalo de 90%
            $zScore = 1.645;
            
            // Gerar previsões
            $currentDate = new \DateTime('first day of next month');
            
            for ($i = 0; $i < $forecastMonths; $i++) {
                // Holt-Winters passo de previsão simples
                $forecast = $level + $trend;
                
                // Atualizar nível e tendência
                $level = $alpha * $forecast + (1 - $alpha) * $level;
                $trend = $beta * ($level - $lastValue) + (1 - $beta) * $trend;
                
                // Formatar período com base no agrupamento
                if ($period === 'all') {
                    $year = $currentDate->format('Y');
                    $quarter = ceil($currentDate->format('n') / 3);
                    $periodLabel = $year . '-Q' . $quarter;
                    $currentDate->modify('+3 months');
                } else {
                    $periodLabel = $currentDate->format('Y-m');
                    $currentDate->modify('+1 month');
                }
                
                // Adicionar variação baseada na série histórica
                // Intervalo de confiança adaptativo (aumenta com distância temporal)
                $confidenceMultiplier = 1 + ($i * 0.2); // Aumenta 20% por período à frente
                $confidence = $stdDev * $zScore * $confidenceMultiplier;
                
                $confidenceMin = max(0, $forecast - $confidence);
                $confidenceMax = $forecast + $confidence;
                
                // Determinar nível de confiança com base na distância no futuro
                // Quanto mais distante, menor a confiança
                $confidenceLevel = 90 - ($i * 5);
                $confidenceLevel = max(50, $confidenceLevel); // Mínimo de 50%
                
                $forecastData[] = [
                    'period_label' => $periodLabel,
                    'forecast_amount' => round($forecast, 2),
                    'confidence_min' => round($confidenceMin, 2),
                    'confidence_max' => round($confidenceMax, 2),
                    'confidence_level' => $confidenceLevel
                ];
                
                // Atualizar o valor anterior para o próximo ciclo
                $lastValue = $forecast;
            }
            
            // Armazenar no cache com duração estendida (12 horas)
            $this->storeInCache('sales_forecast', $cacheParams, $forecastData, 43200);
            
            return $forecastData;
        } catch (PDOException $e) {
            error_log('Erro ao obter previsão de vendas: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter relatório de uso da impressora
     *
     * @param string $period Período do relatório
     * @return array Dados do relatório
     */
    public function getPrinterUsageReport(string $period): array 
    {
        // Validação
        $validPeriods = ['month', 'quarter', 'year', 'all'];
        if (!in_array($period, $validPeriods)) {
            $period = 'month';
        }
        
        // Verificar cache
        $cacheParams = ['period' => $period];
        $cachedData = $this->getFromCache('printer_usage', $cacheParams);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Determinar intervalo de data
        switch ($period) {
            case 'month':
                $startDate = date('Y-m-d', strtotime('-1 month'));
                break;
            case 'quarter':
                $startDate = date('Y-m-d', strtotime('-3 months'));
                break;
            case 'year':
                $startDate = date('Y-m-d', strtotime('-1 year'));
                break;
            case 'all':
                $startDate = '2000-01-01';
                break;
        }
        
        $endDate = date('Y-m-d');
        
        // Query otimizada usando índices específicos (idx_print_jobs_printer_id_created_at)
        // Implementa pré-cálculos em subqueries e filtragem precoce para melhorar performance
        $query = "WITH printer_usage AS (
                    SELECT
                        j.printer_id,
                        COUNT(j.id) AS job_count,
                        SUM(j.print_time_minutes) AS total_minutes,
                        SUM(CASE WHEN j.status = 'completed' THEN 1 ELSE 0 END) AS completed_jobs,
                        SUM(j.filament_usage_grams) AS filament_used_grams
                    FROM print_jobs j
                    WHERE j.created_at BETWEEN ? AND ?
                    GROUP BY j.printer_id
                  )
                  SELECT
                    p.id AS printer_id,
                    p.name AS printer_name,
                    p.model,
                    COALESCE(pu.job_count, 0) AS job_count,
                    COALESCE(pu.total_minutes / 60, 0) AS total_hours,
                    ROUND(COALESCE(pu.total_minutes, 0) / (
                        DATEDIFF(?, ?) * 24 * 60
                    ) * p.count * 100, 1) AS utilization_rate,
                    CASE 
                        WHEN COALESCE(pu.job_count, 0) = 0 THEN 0
                        ELSE (pu.completed_jobs / pu.job_count * 100)
                    END AS efficiency,
                    COALESCE(pu.filament_used_grams, 0) AS filament_used_grams
                  FROM printers p
                  LEFT JOIN printer_usage pu ON p.id = pu.printer_id
                  WHERE p.deleted = 0
                  ORDER BY total_hours DESC";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $startDate . ' 00:00:00', 
                $endDate . ' 23:59:59',
                $endDate, 
                $startDate
            ]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Armazenar no cache
            $this->storeInCache('printer_usage', $cacheParams, $results);
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter relatório de uso da impressora: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter relatório de uso de filamento
     *
     * @param string $period Período do relatório
     * @return array Dados do relatório
     */
    public function getFilamentUsageReport(string $period): array 
    {
        // Validação
        $validPeriods = ['month', 'quarter', 'year', 'all'];
        if (!in_array($period, $validPeriods)) {
            $period = 'month';
        }
        
        // Verificar cache
        $cacheParams = ['period' => $period];
        $cachedData = $this->getFromCache('filament_usage', $cacheParams);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Determinar intervalo de data
        switch ($period) {
            case 'month':
                $startDate = date('Y-m-d', strtotime('-1 month'));
                break;
            case 'quarter':
                $startDate = date('Y-m-d', strtotime('-3 months'));
                break;
            case 'year':
                $startDate = date('Y-m-d', strtotime('-1 year'));
                break;
            case 'all':
                $startDate = '2000-01-01';
                break;
        }
        
        $endDate = date('Y-m-d');
        
        // Query otimizada com materialização temporária e índice idx_print_jobs_material_id
        $query = "WITH material_usage AS (
                    SELECT 
                        j.material_id,
                        SUM(j.filament_usage_grams) AS weight_grams,
                        COUNT(j.id) AS job_count
                    FROM print_jobs j
                    WHERE j.created_at BETWEEN ? AND ?
                    GROUP BY j.material_id
                  )
                  SELECT 
                    m.id AS material_id,
                    m.name AS material_name,
                    m.color,
                    COALESCE(mu.weight_grams, 0) AS weight_grams,
                    COALESCE(mu.job_count, 0) AS job_count,
                    COALESCE(mu.weight_grams, 0) * m.price_per_gram AS cost
                  FROM filament_materials m
                  LEFT JOIN material_usage mu ON m.id = mu.material_id
                  WHERE m.deleted = 0
                  ORDER BY weight_grams DESC";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Armazenar no cache
            $this->storeInCache('filament_usage', $cacheParams, $results);
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter relatório de uso de filamento: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter relatório de tempo de impressão
     *
     * @param string $period Período do relatório
     * @return array Dados do relatório
     */
    public function getPrintTimeReport(string $period): array 
    {
        // Validação
        $validPeriods = ['month', 'quarter', 'year', 'all'];
        if (!in_array($period, $validPeriods)) {
            $period = 'month';
        }
        
        // Verificar cache
        $cacheParams = ['period' => $period];
        $cachedData = $this->getFromCache('print_time', $cacheParams);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Determinar intervalo de data
        switch ($period) {
            case 'month':
                $startDate = date('Y-m-d', strtotime('-1 month'));
                break;
            case 'quarter':
                $startDate = date('Y-m-d', strtotime('-3 months'));
                break;
            case 'year':
                $startDate = date('Y-m-d', strtotime('-1 year'));
                break;
            case 'all':
                $startDate = '2000-01-01';
                break;
        }
        
        $endDate = date('Y-m-d');
        
        // Query otimizada com materialização temporária e índices específicos
        $query = "WITH category_print_time AS (
                    SELECT 
                        p.category_id,
                        COUNT(j.id) AS job_count,
                        AVG(j.print_time_minutes) AS avg_print_time_minutes,
                        SUM(j.print_time_minutes) AS total_print_time_minutes,
                        AVG(j.estimated_print_time_minutes) / NULLIF(AVG(j.print_time_minutes), 0) * 100 AS estimated_vs_real
                    FROM print_jobs j
                    JOIN orders o ON j.order_id = o.id
                    JOIN order_items oi ON o.id = oi.order_id AND j.item_id = oi.id
                    JOIN products p ON oi.product_id = p.id
                    WHERE j.created_at BETWEEN ? AND ?
                    GROUP BY p.category_id
                  )
                  SELECT 
                    c.id AS category_id,
                    c.name AS category_name,
                    COALESCE(cpt.job_count, 0) AS job_count,
                    COALESCE(cpt.avg_print_time_minutes, 0) AS avg_print_time_minutes,
                    COALESCE(cpt.total_print_time_minutes, 0) AS total_print_time_minutes,
                    COALESCE(cpt.estimated_vs_real, 100) AS estimated_vs_real
                  FROM categories c
                  LEFT JOIN category_print_time cpt ON c.id = cpt.category_id
                  WHERE c.deleted = 0
                  ORDER BY total_print_time_minutes DESC";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatar tempos para exibição mais amigável
            foreach ($results as &$row) {
                $avgMinutes = $row['avg_print_time_minutes'];
                $totalMinutes = $row['total_print_time_minutes'];
                
                // Formatar tempo médio
                $avgHours = floor($avgMinutes / 60);
                $avgMins = $avgMinutes % 60;
                $row['avg_time_formatted'] = $avgHours . 'h ' . round($avgMins) . 'm';
                
                // Formatar tempo total
                $totalHours = floor($totalMinutes / 60);
                $totalMins = $totalMinutes % 60;
                $row['total_time_formatted'] = $totalHours . 'h ' . round($totalMins) . 'm';
            }
            
            // Armazenar no cache
            $this->storeInCache('print_time', $cacheParams, $results);
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter relatório de tempo de impressão: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter relatório de falhas de impressão
     *
     * @param string $period Período do relatório
     * @return array Dados do relatório
     */
    public function getPrintFailureReport(string $period): array 
    {
        // Validação
        $validPeriods = ['month', 'quarter', 'year', 'all'];
        if (!in_array($period, $validPeriods)) {
            $period = 'month';
        }
        
        // Verificar cache
        $cacheParams = ['period' => $period];
        $cachedData = $this->getFromCache('print_failure', $cacheParams);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // Determinar intervalo de data
        switch ($period) {
            case 'month':
                $startDate = date('Y-m-d', strtotime('-1 month'));
                break;
            case 'quarter':
                $startDate = date('Y-m-d', strtotime('-3 months'));
                break;
            case 'year':
                $startDate = date('Y-m-d', strtotime('-1 year'));
                break;
            case 'all':
                $startDate = '2000-01-01';
                break;
        }
        
        $endDate = date('Y-m-d');
        
        // Query otimizada com índice idx_print_job_failures_type_created_at
        // e materialização temporária
        $query = "WITH failure_counts AS (
                    SELECT 
                        f.failure_type,
                        COUNT(f.id) AS count,
                        SUM(j.filament_usage_grams) AS wasted_grams
                    FROM print_job_failures f
                    JOIN print_jobs j ON f.print_job_id = j.id
                    WHERE f.created_at BETWEEN ? AND ?
                    GROUP BY f.failure_type
                  ),
                  material_failures AS (
                    SELECT 
                        f.failure_type,
                        m.name AS material_name,
                        COUNT(*) AS material_count
                    FROM print_job_failures f
                    JOIN print_jobs j ON f.print_job_id = j.id
                    JOIN filament_materials m ON j.material_id = m.id
                    WHERE f.created_at BETWEEN ? AND ?
                    GROUP BY f.failure_type, m.id
                  ),
                  common_materials AS (
                    SELECT 
                        mf.failure_type,
                        mf.material_name AS most_common_material
                    FROM material_failures mf
                    JOIN (
                        SELECT 
                            failure_type,
                            MAX(material_count) AS max_count
                        FROM material_failures
                        GROUP BY failure_type
                    ) mf_max ON mf.failure_type = mf_max.failure_type AND mf.material_count = mf_max.max_count
                  )
                  SELECT 
                    fc.failure_type,
                    fc.count,
                    cm.most_common_material,
                    fc.wasted_grams * (SELECT AVG(price_per_gram) FROM filament_materials) AS estimated_loss
                  FROM failure_counts fc
                  LEFT JOIN common_materials cm ON fc.failure_type = cm.failure_type
                  ORDER BY fc.count DESC";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $startDate . ' 00:00:00', 
                $endDate . ' 23:59:59',
                $startDate . ' 00:00:00', 
                $endDate . ' 23:59:59'
            ]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Armazenar no cache
            $this->storeInCache('print_failure', $cacheParams, $results);
            
            return $results;
        } catch (PDOException $e) {
            error_log('Erro ao obter relatório de falhas de impressão: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Exportar dados para CSV com cabeçalhos traduzidos e formatação
     *
     * @param array $data Dados a serem exportados
     * @param array $headerMap Mapeamento de chaves para cabeçalhos traduzidos
     * @return string Conteúdo CSV
     */
    public function exportToCsv(array $data, array $headerMap = []): string 
    {
        if (empty($data)) {
            return '';
        }
        
        // Se não houver mapeamento de cabeçalhos, usar as chaves originais
        if (empty($headerMap)) {
            $headers = array_keys($data[0]);
            $headerMap = array_combine($headers, $headers);
        }
        
        // Iniciar saída com BOM para suporte a UTF-8 no Excel
        $output = "\xEF\xBB\xBF";
        
        // Adicionar cabeçalhos
        $output .= implode(';', array_values($headerMap)) . "\n";
        
        // Adicionar linhas de dados
        foreach ($data as $row) {
            $values = [];
            
            foreach (array_keys($headerMap) as $field) {
                $value = $row[$field] ?? '';
                
                // Formatar valores monetários
                if (strpos($field, 'price') !== false || 
                    strpos($field, 'revenue') !== false || 
                    strpos($field, 'total') !== false || 
                    strpos($field, 'cost') !== false || 
                    strpos($field, 'amount') !== false) {
                    // Usar vírgula para decimal e ponto para milhar (padrão brasileiro)
                    $value = number_format((float)$value, 2, ',', '.');
                }
                
                // Escapar aspas e encapsular com aspas se necessário
                if (is_string($value) && (
                    strpos($value, ';') !== false || 
                    strpos($value, '"') !== false || 
                    strpos($value, "\n") !== false
                )) {
                    $value = '"' . str_replace('"', '""', $value) . '"';
                }
                
                $values[] = $value;
            }
            
            $output .= implode(';', $values) . "\n";
        }
        
        return $output;
    }
    
    /**
     * Exportar dados para PDF usando biblioteca MPdf
     * 
     * @param array $data Dados a serem exportados
     * @param string $title Título do relatório
     * @param array $headerMap Mapeamento de cabeçalhos
     * @return string Conteúdo binário do PDF gerado
     * @throws \Exception Se a geração falhar
     */
    public function exportToPdf(array $data, string $title, array $headerMap = []): string 
    {
        // Usar a classe PdfExport que já foi implementada com mPDF
        $pdfExport = new \App\Lib\Export\PdfExport($title);
        $pdfExport->setData($data);
        return $pdfExport->generate();
    }
    
    /**
     * Exportar dados para Excel (CSV) 
     * 
     * @param array $data Dados a serem exportados
     * @param string $title Título do relatório
     * @param array $headerMap Mapeamento de cabeçalhos
     * @return string Conteúdo do arquivo CSV
     */
    public function exportToExcel(array $data, string $title, array $headerMap = []): string 
    {
        // Usar a classe ExcelExport que já foi implementada
        $excelExport = new \App\Lib\Export\ExcelExport($title);
        if (!empty($headerMap)) {
            $excelExport->setHeaders(array_values($headerMap));
        }
        $excelExport->setData($data);
        return $excelExport->generate();
    }
}
