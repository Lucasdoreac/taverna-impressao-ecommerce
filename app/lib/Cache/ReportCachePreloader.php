<?php
namespace App\Lib\Cache;

use App\Models\ReportModel;

/**
 * ReportCachePreloader
 * 
 * Classe responsável por pré-carregar relatórios frequentes em cache para
 * melhorar a responsividade do sistema em momentos de alta demanda.
 * Implementa mecanismos de priorização baseados em frequência de acesso
 * e importância operacional dos relatórios.
 * 
 * @package App\Lib\Cache
 * @version 1.0.0
 */
class ReportCachePreloader
{
    /**
     * @var ReportModel Instância do modelo de relatórios
     */
    private $reportModel;
    
    /**
     * @var AdvancedReportCache Sistema de cache avançado
     */
    private $cache;
    
    /**
     * @var array Configuração de relatórios prioritários
     */
    private $priorityReports = [
        // Relatórios críticos para o dashboard - alta prioridade
        ['type' => 'sales', 'params' => ['period' => 'month']],
        ['type' => 'products', 'params' => ['period' => 'month', 'limit' => 20]],
        ['type' => 'printer_usage', 'params' => ['period' => 'month']],
        
        // Relatórios de médio uso - prioridade média
        ['type' => 'customers', 'params' => ['period' => 'month']],
        ['type' => 'stock_status', 'params' => []],
        
        // Relatórios menos usados mas intensivos - baixa prioridade
        ['type' => 'sales_trend', 'params' => ['period' => 'year']],
        ['type' => 'seasonality', 'params' => []]
    ];
    
    /**
     * @var int Limite de tempo para execução em segundos
     */
    private $timeLimit = 30;
    
    /**
     * @var string Caminho para o arquivo de log
     */
    private $logFile;
    
    /**
     * Construtor
     * 
     * @param ReportModel|null $reportModel Instância do modelo de relatórios
     */
    public function __construct(ReportModel $reportModel = null)
    {
        $this->reportModel = $reportModel ?? new ReportModel();
        $this->cache = new AdvancedReportCache();
        $this->logFile = dirname(__DIR__, 3) . '/logs/report_cache_preloader.log';
    }
    
    /**
     * Inicia o processo de pré-carregamento de relatórios em cache
     * 
     * @param bool $highPriorityOnly Se true, processa apenas relatórios de alta prioridade
     * @param int|null $customTimeLimit Limite de tempo personalizado em segundos
     * @return array Estatísticas do processo
     */
    public function preloadReports(bool $highPriorityOnly = false, ?int $customTimeLimit = null): array
    {
        // Definir limite de tempo personalizado se fornecido
        $this->timeLimit = $customTimeLimit ?? $this->timeLimit;
        
        // Registrar início
        $this->log("Iniciando pré-carregamento de relatórios" . ($highPriorityOnly ? " prioritários" : ""));
        
        $startTime = microtime(true);
        $endTime = $startTime + $this->timeLimit;
        
        $stats = [
            'processed' => 0,
            'success' => 0,
            'failures' => 0,
            'skipped' => 0,
            'time_elapsed' => 0,
            'details' => []
        ];
        
        // Verificar se o cache está habilitado
        if (!$this->cache->isEnabled()) {
            $this->log("Cache desabilitado. Operação cancelada.");
            $stats['error'] = "Cache desabilitado";
            return $stats;
        }
        
        // Obter estatísticas atuais do cache
        $cacheStats = $this->cache->getStats();
        
        // Filtrar e ordenar relatórios por prioridade
        $reportsToProcess = $this->prioritizeReports($cacheStats, $highPriorityOnly);
        
        // Processar relatórios até o limite de tempo
        foreach ($reportsToProcess as $reportConfig) {
            // Verificar se atingiu o limite de tempo
            if (microtime(true) >= $endTime) {
                $this->log("Limite de tempo atingido. Interrompendo operação.");
                $stats['skipped'] += count($reportsToProcess) - $stats['processed'];
                break;
            }
            
            $reportType = $reportConfig['type'];
            $params = $reportConfig['params'];
            $cacheKey = $this->cache->generateKey($reportType, $params);
            
            $stats['processed']++;
            $startReportTime = microtime(true);
            
            // Adicionar detalhes para este relatório
            $reportDetail = [
                'type' => $reportType,
                'params' => $params,
                'status' => 'pending',
                'time' => 0
            ];
            
            try {
                // Verificar se o relatório já está em cache e ainda é válido
                if ($this->cache->has($cacheKey)) {
                    $this->log("Relatório {$reportType} já está em cache válido. Pulando.");
                    $reportDetail['status'] = 'skipped_valid';
                    $stats['details'][] = $reportDetail;
                    continue;
                }
                
                // Gerar o relatório
                $this->log("Gerando relatório {$reportType} com parâmetros: " . json_encode($params));
                
                // Chamar o método apropriado no modelo de relatório com base no tipo
                $report = $this->generateReport($reportType, $params);
                
                if (empty($report)) {
                    throw new \Exception("Relatório vazio gerado para {$reportType}");
                }
                
                // Calcular tempo de execução
                $executionTime = microtime(true) - $startReportTime;
                
                // Determinar tempo de expiração adaptativo mais longo para relatórios
                // que demoram mais para serem gerados
                $baseExpiration = $this->reportModel->getCacheExpirationForType($reportType);
                // Aumentar expiração proporcionalmente ao tempo de execução para relatórios caros
                $adaptiveExpiration = min($baseExpiration * 2, $baseExpiration + ($executionTime * 100));
                
                // Armazenar em cache com expiração adaptativa
                $this->cache->set($cacheKey, $report, (int)$adaptiveExpiration);
                
                $this->log("Relatório {$reportType} gerado e armazenado em cache em " . round($executionTime, 2) . "s");
                $stats['success']++;
                
                $reportDetail['status'] = 'success';
                $reportDetail['time'] = round($executionTime, 2);
                $reportDetail['expiration'] = $adaptiveExpiration;
                
            } catch (\Exception $e) {
                $this->log("Erro ao gerar relatório {$reportType}: " . $e->getMessage(), 'ERROR');
                $stats['failures']++;
                
                $reportDetail['status'] = 'error';
                $reportDetail['error'] = $e->getMessage();
            }
            
            $stats['details'][] = $reportDetail;
        }
        
        // Calcular tempo total
        $stats['time_elapsed'] = round(microtime(true) - $startTime, 2);
        
        $this->log("Pré-carregamento concluído em {$stats['time_elapsed']}s. " .
                  "Processados: {$stats['processed']}, " .
                  "Sucesso: {$stats['success']}, " .
                  "Falhas: {$stats['failures']}, " .
                  "Pulados: {$stats['skipped']}");
        
        return $stats;
    }
    
    /**
     * Prioriza relatórios com base nas estatísticas de acesso e configuração
     * 
     * @param array $cacheStats Estatísticas atuais do cache
     * @param bool $highPriorityOnly Se true, retorna apenas relatórios de alta prioridade
     * @return array Lista de relatórios priorizados
     */
    private function prioritizeReports(array $cacheStats, bool $highPriorityOnly): array
    {
        // Criar cópia da lista de relatórios prioritários
        $reports = $this->priorityReports;
        
        // Se disponível, usar estatísticas de hits para reprioritizar
        if (isset($cacheStats['hit_counts']) && !empty($cacheStats['hit_counts'])) {
            // Extrair tipos de relatório das chaves de cache
            $hitsByType = [];
            foreach ($cacheStats['hit_counts'] as $key => $hits) {
                // Tentar extrair o tipo do relatório da chave
                $keyParts = explode('_', $key);
                if (!empty($keyParts[0])) {
                    $reportType = $keyParts[0];
                    if (!isset($hitsByType[$reportType])) {
                        $hitsByType[$reportType] = 0;
                    }
                    $hitsByType[$reportType] += $hits;
                }
            }
            
            // Adicionar pontuação baseada em hits
            foreach ($reports as &$report) {
                $type = $report['type'];
                $report['score'] = isset($hitsByType[$type]) ? $hitsByType[$type] : 0;
                
                // Adicionar pontuação extra para relatórios de alta prioridade (primeiros 3)
                if (array_search($report, $this->priorityReports) < 3) {
                    $report['score'] += 100;
                }
            }
            
            // Ordenar por pontuação (decrescente)
            usort($reports, function($a, $b) {
                return $b['score'] - $a['score'];
            });
        }
        
        // Filtrar apenas relatórios de alta prioridade se solicitado
        if ($highPriorityOnly) {
            $reports = array_filter($reports, function($report) {
                return array_search($report, $this->priorityReports) < 3 || ($report['score'] ?? 0) > 10;
            });
        }
        
        return $reports;
    }
    
    /**
     * Gera um relatório específico com base no tipo e parâmetros
     * 
     * @param string $reportType Tipo de relatório
     * @param array $params Parâmetros do relatório
     * @return array Dados do relatório
     * @throws \Exception Se o tipo de relatório não for suportado
     */
    private function generateReport(string $reportType, array $params): array
    {
        switch ($reportType) {
            case 'sales':
                return $this->reportModel->getSalesReport($params['period'] ?? 'month');
                
            case 'products':
                return $this->reportModel->getTopProducts(
                    $params['period'] ?? 'month',
                    $params['limit'] ?? 20
                );
                
            case 'product_categories':
                return $this->reportModel->getProductCategoriesReport($params['period'] ?? 'month');
                
            case 'stock_status':
                return $this->reportModel->getStockStatusReport();
                
            case 'customers':
                return $this->reportModel->getActiveCustomersReport(
                    $params['period'] ?? 'month',
                    $params['limit'] ?? 20
                );
                
            case 'new_customers':
                return $this->reportModel->getNewCustomersReport($params['period'] ?? 'month');
                
            case 'customer_segments':
                return $this->reportModel->getCustomerSegmentsReport($params['period'] ?? 'month');
                
            case 'customer_retention':
                return $this->reportModel->getCustomerRetentionReport();
                
            case 'sales_trend':
                return $this->reportModel->getSalesTrendReport($params['period'] ?? 'year');
                
            case 'product_trends':
                return $this->reportModel->getProductTrendsReport($params['period'] ?? 'year');
                
            case 'seasonality':
                return $this->reportModel->getSeasonalityReport();
                
            case 'sales_forecast':
                return $this->reportModel->getSalesForecastReport($params['period'] ?? 'year');
                
            case 'printer_usage':
                return $this->reportModel->getPrinterUsageReport($params['period'] ?? 'month');
                
            case 'filament_usage':
                return $this->reportModel->getFilamentUsageReport($params['period'] ?? 'month');
                
            case 'print_time':
                return $this->reportModel->getPrintTimeReport($params['period'] ?? 'month');
                
            case 'print_failure':
                return $this->reportModel->getPrintFailureReport($params['period'] ?? 'month');
                
            default:
                throw new \Exception("Tipo de relatório não suportado: {$reportType}");
        }
    }
    
    /**
     * Registra mensagem de log com timestamp
     * 
     * @param string $message Mensagem de log
     * @param string $level Nível de log (INFO, WARNING, ERROR)
     * @return void
     */
    private function log(string $message, string $level = 'INFO'): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        // Garantir que o diretório de logs existe
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Anexar ao arquivo de log
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
}
