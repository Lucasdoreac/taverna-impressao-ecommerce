<?php
namespace App\Lib\Repositories;

use App\Models\OptimizedReportModel;
use App\Lib\Security\InputValidator;
use App\Lib\Security\Logger;

/**
 * OptimizedReportRepository
 * 
 * Implementação otimizada do repositório de relatórios, utilizando o OptimizedReportModel
 * com suporte a cache avançado, compressão de dados e métricas de performance.
 *
 * @package App\Lib\Repositories
 * @version 1.0.0
 */
class OptimizedReportRepository extends AbstractReportRepository
{
    /**
     * @var OptimizedReportModel Modelo otimizado para relatórios
     */
    private $reportModel;
    
    /**
     * @var Logger Logger para registrar informações e erros
     */
    private $logger;
    
    /**
     * Construtor
     */
    public function __construct()
    {
        $this->reportModel = new OptimizedReportModel();
        $this->logger = new Logger('report_repository');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSalesReport(string $period): array
    {
        // Validar período
        $validPeriods = ['day', 'week', 'month', 'quarter', 'year'];
        $period = $this->validatePeriod($period, $validPeriods, 'month');
        
        // Executar com medição de métricas
        $params = ['period' => $period];
        $result = $this->executeWithMetrics('getSalesReport', function() use ($period) {
            $result = $this->reportModel->getSalesReport($period);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getSalesReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSalesByStatusReport(string $startDate, string $endDate): array
    {
        // Validar datas
        $startDate = InputValidator::validateDate($startDate, 'Y-m-d', date('Y-m-d', strtotime('-30 days')));
        $endDate = InputValidator::validateDate($endDate, 'Y-m-d', date('Y-m-d'));
        
        // Executar com medição de métricas
        $params = ['startDate' => $startDate, 'endDate' => $endDate];
        $result = $this->executeWithMetrics('getSalesByStatusReport', function() use ($startDate, $endDate) {
            $result = $this->reportModel->getSalesByStatusReport($startDate, $endDate);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getSalesByStatusReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSalesByPaymentMethodReport(string $startDate, string $endDate): array
    {
        // Validar datas
        $startDate = InputValidator::validateDate($startDate, 'Y-m-d', date('Y-m-d', strtotime('-30 days')));
        $endDate = InputValidator::validateDate($endDate, 'Y-m-d', date('Y-m-d'));
        
        // Executar com medição de métricas
        $params = ['startDate' => $startDate, 'endDate' => $endDate];
        $result = $this->executeWithMetrics('getSalesByPaymentMethodReport', function() use ($startDate, $endDate) {
            $result = $this->reportModel->getSalesByPaymentMethodReport($startDate, $endDate);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getSalesByPaymentMethodReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSalesByRegionReport(string $startDate, string $endDate): array
    {
        // Validar datas
        $startDate = InputValidator::validateDate($startDate, 'Y-m-d', date('Y-m-d', strtotime('-30 days')));
        $endDate = InputValidator::validateDate($endDate, 'Y-m-d', date('Y-m-d'));
        
        // Executar com medição de métricas
        $params = ['startDate' => $startDate, 'endDate' => $endDate];
        $result = $this->executeWithMetrics('getSalesByRegionReport', function() use ($startDate, $endDate) {
            $result = $this->reportModel->getSalesByRegionReport($startDate, $endDate);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getSalesByRegionReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSalesByCityReport(string $startDate, string $endDate, int $limit): array
    {
        // Validar datas e limite
        $startDate = InputValidator::validateDate($startDate, 'Y-m-d', date('Y-m-d', strtotime('-30 days')));
        $endDate = InputValidator::validateDate($endDate, 'Y-m-d', date('Y-m-d'));
        $limit = max(1, min($limit, 100)); // Limitar entre 1 e 100 para evitar sobrecarga
        
        // Executar com medição de métricas
        $params = ['startDate' => $startDate, 'endDate' => $endDate, 'limit' => $limit];
        $result = $this->executeWithMetrics('getSalesByCityReport', function() use ($startDate, $endDate, $limit) {
            $result = $this->reportModel->getSalesByCityReport($startDate, $endDate, $limit);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getSalesByCityReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getTopProducts(string $period, int $limit): array
    {
        // Validar período e limite
        $validPeriods = ['day', 'week', 'month', 'quarter', 'year'];
        $period = $this->validatePeriod($period, $validPeriods, 'month');
        $limit = max(1, min($limit, 100)); // Limitar entre 1 e 100 para evitar sobrecarga
        
        // Executar com medição de métricas
        $params = ['period' => $period, 'limit' => $limit];
        $result = $this->executeWithMetrics('getTopProducts', function() use ($period, $limit) {
            $result = $this->reportModel->getTopProducts($period, $limit);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getTopProducts', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getProductCategoriesReport(string $period): array
    {
        // Validar período
        $validPeriods = ['day', 'week', 'month', 'quarter', 'year'];
        $period = $this->validatePeriod($period, $validPeriods, 'month');
        
        // Executar com medição de métricas
        $params = ['period' => $period];
        $result = $this->executeWithMetrics('getProductCategoriesReport', function() use ($period) {
            $result = $this->reportModel->getProductCategoriesReport($period);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getProductCategoriesReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getStockStatusReport(): array
    {
        // Executar com medição de métricas
        $result = $this->executeWithMetrics('getStockStatusReport', function() {
            $result = $this->reportModel->getStockStatusReport();
            
            // Registrar uso de cache
            $this->registerCacheUsage('getStockStatusReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        });
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getNewCustomersReport(string $period): array
    {
        // Validar período
        $validPeriods = ['day', 'week', 'month', 'quarter', 'year'];
        $period = $this->validatePeriod($period, $validPeriods, 'month');
        
        // Executar com medição de métricas
        $params = ['period' => $period];
        $result = $this->executeWithMetrics('getNewCustomersReport', function() use ($period) {
            $result = $this->reportModel->getNewCustomersReport($period);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getNewCustomersReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getActiveCustomersReport(string $period, int $limit): array
    {
        // Validar período e limite
        $validPeriods = ['day', 'week', 'month', 'quarter', 'year'];
        $period = $this->validatePeriod($period, $validPeriods, 'month');
        $limit = max(1, min($limit, 100)); // Limitar entre 1 e 100 para evitar sobrecarga
        
        // Executar com medição de métricas
        $params = ['period' => $period, 'limit' => $limit];
        $result = $this->executeWithMetrics('getActiveCustomersReport', function() use ($period, $limit) {
            $result = $this->reportModel->getActiveCustomersReport($period, $limit);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getActiveCustomersReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCustomerSegmentsReport(string $period): array
    {
        // Validar período
        $validPeriods = ['day', 'week', 'month', 'quarter', 'year'];
        $period = $this->validatePeriod($period, $validPeriods, 'month');
        
        // Executar com medição de métricas
        $params = ['period' => $period];
        $result = $this->executeWithMetrics('getCustomerSegmentsReport', function() use ($period) {
            $result = $this->reportModel->getCustomerSegmentsReport($period);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getCustomerSegmentsReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCustomerRetentionReport(): array
    {
        // Executar com medição de métricas
        $result = $this->executeWithMetrics('getCustomerRetentionReport', function() {
            $result = $this->reportModel->getCustomerRetentionReport();
            
            // Registrar uso de cache
            $this->registerCacheUsage('getCustomerRetentionReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        });
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSalesTrendReport(string $period): array
    {
        // Validar período
        $validPeriods = ['quarter', 'year', 'all'];
        $period = $this->validatePeriod($period, $validPeriods, 'year');
        
        // Executar com medição de métricas
        $params = ['period' => $period];
        $result = $this->executeWithMetrics('getSalesTrendReport', function() use ($period) {
            $result = $this->reportModel->getSalesTrendReport($period);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getSalesTrendReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getProductTrendsReport(string $period): array
    {
        // Validar período
        $validPeriods = ['quarter', 'year', 'all'];
        $period = $this->validatePeriod($period, $validPeriods, 'year');
        
        // Executar com medição de métricas
        $params = ['period' => $period];
        $result = $this->executeWithMetrics('getProductTrendsReport', function() use ($period) {
            $result = $this->reportModel->getProductTrendsReport($period);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getProductTrendsReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSeasonalityReport(): array
    {
        // Executar com medição de métricas
        $result = $this->executeWithMetrics('getSeasonalityReport', function() {
            $result = $this->reportModel->getSeasonalityReport();
            
            // Registrar uso de cache
            $this->registerCacheUsage('getSeasonalityReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        });
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSalesForecastReport(string $period): array
    {
        // Validar período
        $validPeriods = ['quarter', 'year', 'all'];
        $period = $this->validatePeriod($period, $validPeriods, 'year');
        
        // Executar com medição de métricas
        $params = ['period' => $period];
        $result = $this->executeWithMetrics('getSalesForecastReport', function() use ($period) {
            $result = $this->reportModel->getSalesForecastReport($period);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getSalesForecastReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPrinterUsageReport(string $period): array
    {
        // Validar período
        $validPeriods = ['month', 'quarter', 'year', 'all'];
        $period = $this->validatePeriod($period, $validPeriods, 'month');
        
        // Executar com medição de métricas
        $params = ['period' => $period];
        $result = $this->executeWithMetrics('getPrinterUsageReport', function() use ($period) {
            $result = $this->reportModel->getPrinterUsageReport($period);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getPrinterUsageReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFilamentUsageReport(string $period): array
    {
        // Validar período
        $validPeriods = ['month', 'quarter', 'year', 'all'];
        $period = $this->validatePeriod($period, $validPeriods, 'month');
        
        // Executar com medição de métricas
        $params = ['period' => $period];
        $result = $this->executeWithMetrics('getFilamentUsageReport', function() use ($period) {
            $result = $this->reportModel->getFilamentUsageReport($period);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getFilamentUsageReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPrintTimeReport(string $period): array
    {
        // Validar período
        $validPeriods = ['month', 'quarter', 'year', 'all'];
        $period = $this->validatePeriod($period, $validPeriods, 'month');
        
        // Executar com medição de métricas
        $params = ['period' => $period];
        $result = $this->executeWithMetrics('getPrintTimeReport', function() use ($period) {
            $result = $this->reportModel->getPrintTimeReport($period);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getPrintTimeReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPrintFailureReport(string $period): array
    {
        // Validar período
        $validPeriods = ['month', 'quarter', 'year', 'all'];
        $period = $this->validatePeriod($period, $validPeriods, 'month');
        
        // Executar com medição de métricas
        $params = ['period' => $period];
        $result = $this->executeWithMetrics('getPrintFailureReport', function() use ($period) {
            $result = $this->reportModel->getPrintFailureReport($period);
            
            // Registrar uso de cache
            $this->registerCacheUsage('getPrintFailureReport', $this->reportModel->wasCacheUsed());
            
            return $result;
        }, $params);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function invalidateCache(string $reportType): int
    {
        // Executar com medição de métricas
        $params = ['reportType' => $reportType];
        $result = $this->executeWithMetrics('invalidateCache', function() use ($reportType) {
            return $this->reportModel->invalidateCache($reportType);
        }, $params);
        
        // Registrar operação
        $this->logger->info("Cache invalidado para {$reportType}: {$result} itens removidos");
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function clearExpiredCaches(): int
    {
        // Executar com medição de métricas
        $result = $this->executeWithMetrics('clearExpiredCaches', function() {
            return $this->reportModel->clearExpiredCaches();
        });
        
        // Registrar operação
        $this->logger->info("Caches expirados removidos: {$result} itens");
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function clearAllCaches(): array
    {
        // Executar com medição de métricas
        $result = $this->executeWithMetrics('clearAllCaches', function() {
            return $this->reportModel->clearAllCaches();
        });
        
        // Registrar operação
        $this->logger->info("Todos os caches foram limpos: " . json_encode($result));
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCacheStats(): array
    {
        // Executar com medição de métricas
        $result = $this->executeWithMetrics('getCacheStats', function() {
            return $this->reportModel->getCacheStats();
        });
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDetailedCacheStats(): array
    {
        // Executar com medição de métricas
        $result = $this->executeWithMetrics('getDetailedCacheStats', function() {
            return $this->reportModel->getDetailedCacheStats();
        });
        
        return $result;
    }
}