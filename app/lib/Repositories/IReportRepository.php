<?php
namespace App\Lib\Repositories;

/**
 * Interface IReportRepository
 * 
 * Define o contrato para repositórios de relatórios, permitindo implementações
 * intercambiáveis que podem ser substituídas sem modificar o código cliente.
 *
 * @package App\Lib\Repositories
 * @version 1.0.0
 */
interface IReportRepository
{
    /**
     * Obter relatório de vendas baseado no período
     *
     * @param string $period Período do relatório (day, week, month, quarter, year)
     * @return array Dados do relatório
     */
    public function getSalesReport(string $period): array;
    
    /**
     * Obter relatório de vendas por status
     *
     * @param string $startDate Data de início (Y-m-d)
     * @param string $endDate Data de fim (Y-m-d)
     * @return array Dados do relatório
     */
    public function getSalesByStatusReport(string $startDate, string $endDate): array;
    
    /**
     * Obter relatório de vendas por método de pagamento
     *
     * @param string $startDate Data de início (Y-m-d)
     * @param string $endDate Data de fim (Y-m-d)
     * @return array Dados do relatório
     */
    public function getSalesByPaymentMethodReport(string $startDate, string $endDate): array;
    
    /**
     * Obter relatório de vendas por região
     *
     * @param string $startDate Data de início (Y-m-d)
     * @param string $endDate Data de fim (Y-m-d)
     * @return array Dados do relatório
     */
    public function getSalesByRegionReport(string $startDate, string $endDate): array;
    
    /**
     * Obter relatório de vendas por cidade
     *
     * @param string $startDate Data de início (Y-m-d)
     * @param string $endDate Data de fim (Y-m-d)
     * @param int $limit Limite de resultados
     * @return array Dados do relatório
     */
    public function getSalesByCityReport(string $startDate, string $endDate, int $limit): array;
    
    /**
     * Obter produtos mais vendidos
     *
     * @param string $period Período do relatório
     * @param int $limit Limite de resultados
     * @return array Dados do relatório
     */
    public function getTopProducts(string $period, int $limit): array;
    
    /**
     * Obter relatório de categorias de produtos
     *
     * @param string $period Período do relatório
     * @return array Dados do relatório
     */
    public function getProductCategoriesReport(string $period): array;
    
    /**
     * Obter relatório de status de estoque
     *
     * @return array Dados do relatório
     */
    public function getStockStatusReport(): array;
    
    /**
     * Obter relatório de novos clientes
     *
     * @param string $period Período do relatório
     * @return array Dados do relatório
     */
    public function getNewCustomersReport(string $period): array;
    
    /**
     * Obter relatório de clientes ativos
     *
     * @param string $period Período do relatório
     * @param int $limit Número máximo de clientes
     * @return array Dados do relatório
     */
    public function getActiveCustomersReport(string $period, int $limit): array;
    
    /**
     * Obter relatório de segmentação de clientes
     *
     * @param string $period Período do relatório
     * @return array Dados do relatório
     */
    public function getCustomerSegmentsReport(string $period): array;
    
    /**
     * Obter relatório de retenção de clientes
     *
     * @return array Dados do relatório
     */
    public function getCustomerRetentionReport(): array;
    
    /**
     * Obter relatório de tendências de vendas
     *
     * @param string $period Período para análise
     * @return array Dados do relatório
     */
    public function getSalesTrendReport(string $period): array;
    
    /**
     * Obter relatório de tendências de produtos
     *
     * @param string $period Período para análise
     * @return array Dados do relatório
     */
    public function getProductTrendsReport(string $period): array;
    
    /**
     * Obter relatório de sazonalidade
     *
     * @return array Dados do relatório
     */
    public function getSeasonalityReport(): array;
    
    /**
     * Obter relatório de previsão de vendas
     *
     * @param string $period Período para previsão
     * @return array Dados do relatório
     */
    public function getSalesForecastReport(string $period): array;
    
    /**
     * Obter relatório de uso da impressora
     *
     * @param string $period Período do relatório
     * @return array Dados do relatório
     */
    public function getPrinterUsageReport(string $period): array;
    
    /**
     * Obter relatório de uso de filamento
     *
     * @param string $period Período do relatório
     * @return array Dados do relatório
     */
    public function getFilamentUsageReport(string $period): array;
    
    /**
     * Obter relatório de tempo de impressão
     *
     * @param string $period Período do relatório
     * @return array Dados do relatório
     */
    public function getPrintTimeReport(string $period): array;
    
    /**
     * Obter relatório de falhas de impressão
     *
     * @param string $period Período do relatório
     * @return array Dados do relatório
     */
    public function getPrintFailureReport(string $period): array;
    
    /**
     * Invalida cache para um tipo de relatório
     * 
     * @param string $reportType Tipo de relatório
     * @return int Número de itens invalidados
     */
    public function invalidateCache(string $reportType): int;
    
    /**
     * Limpa todos os caches de relatórios expirados
     * 
     * @return int Número de itens removidos
     */
    public function clearExpiredCaches(): int;
    
    /**
     * Limpa todo o cache de relatórios
     * 
     * @return array Estatísticas da operação
     */
    public function clearAllCaches(): array;
    
    /**
     * Retorna estatísticas de uso do cache de relatórios
     * 
     * @return array Estatísticas de uso
     */
    public function getCacheStats(): array;
    
    /**
     * Retorna estatísticas detalhadas do cache com métricas de performance
     * 
     * @return array Estatísticas detalhadas
     */
    public function getDetailedCacheStats(): array;
    
    /**
     * Retorna métricas de performance do repositório
     * 
     * @return array Métricas de performance
     */
    public function getPerformanceMetrics(): array;
    
    /**
     * Verifica se um relatório específico foi servido do cache
     * 
     * @param string $reportType Tipo de relatório
     * @return bool True se foi servido do cache
     */
    public function wasCacheUsed(string $reportType = ''): bool;
}