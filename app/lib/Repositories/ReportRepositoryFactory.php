<?php
namespace App\Lib\Repositories;

use App\Lib\Config\ConfigManager;
use App\Lib\Security\Logger;

/**
 * ReportRepositoryFactory
 * 
 * Factory responsável por criar instâncias de repositórios de relatórios.
 * Permite troca transparente entre implementações através de configuração.
 *
 * @package App\Lib\Repositories
 * @version 1.0.0
 */
class ReportRepositoryFactory
{
    /**
     * Cria uma instância do repositório de relatórios com base na configuração
     *
     * @param string $type Tipo específico de repositório (opcional)
     * @return IReportRepository Implementação do repositório
     */
    public static function create(string $type = ''): IReportRepository
    {
        // Se um tipo específico for solicitado, ignorar configuração
        if (!empty($type)) {
            return self::createByType($type);
        }
        
        // Obter configuração
        $config = ConfigManager::getInstance();
        $useOptimized = $config->get('reports.use_optimized', true);
        
        // Criar repositório com base na configuração
        return $useOptimized 
            ? new OptimizedReportRepository() 
            : new LegacyReportRepository();
    }
    
    /**
     * Cria uma instância específica do repositório de relatórios
     *
     * @param string $type Tipo do repositório ('optimized' ou 'legacy')
     * @return IReportRepository Implementação do repositório
     * @throws \InvalidArgumentException Se o tipo for inválido
     */
    public static function createByType(string $type): IReportRepository
    {
        $logger = new Logger('report_repository_factory');
        
        switch (strtolower($type)) {
            case 'optimized':
                $logger->info("Criando repositório otimizado manualmente");
                return new OptimizedReportRepository();
                
            case 'legacy':
                $logger->info("Criando repositório legado manualmente");
                return new LegacyReportRepository();
                
            default:
                $logger->error("Tipo de repositório desconhecido: {$type}. Usando o otimizado como fallback.");
                return new OptimizedReportRepository();
        }
    }
    
    /**
     * Retorna uma instância para comparação de performance
     * Com ambas as implementações para benchmarking
     *
     * @return array Array com ambas as implementações
     */
    public static function createBothForBenchmark(): array
    {
        return [
            'optimized' => new OptimizedReportRepository(),
            'legacy' => new LegacyReportRepository()
        ];
    }
}