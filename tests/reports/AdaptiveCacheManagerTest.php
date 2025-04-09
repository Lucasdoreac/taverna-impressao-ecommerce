<?php
/**
 * Teste Unitário para AdaptiveCacheManager
 * 
 * Verifica o comportamento do gerenciador de cache adaptativo, incluindo:
 * - Ajuste dinâmico de tempos de expiração
 * - Prefetching inteligente baseado em padrões de acesso
 * - Estratégias de invalidação seletiva
 * - Gerenciamento seguro de cache
 */

use PHPUnit\Framework\TestCase;

class AdaptiveCacheManagerTest extends TestCase
{
    /**
     * @var AdaptiveCacheManager
     */
    private $cacheManager;
    
    /**
     * @var AdvancedReportCache
     */
    private $mockCache;
    
    /**
     * @var LoggerInterface
     */
    private $mockLogger;
    
    /**
     * @var ReportPerformanceMonitor
     */
    private $mockPerformanceMonitor;
    
    /**
     * Configuração para testes
     */
    public function setUp(): void
    {
        // Mock do sistema de cache
        $this->mockCache = $this->createMock(\App\Lib\Reports\AdvancedReportCache::class);
        
        // Mock do logger
        $this->mockLogger = $this->createMock(\Psr\Log\LoggerInterface::class);
        
        // Mock do monitor de performance
        $this->mockPerformanceMonitor = $this->createMock(\App\Lib\Reports\ReportPerformanceMonitor::class);
        
        // Configurar o gerenciador de cache
        $this->cacheManager = new \App\Lib\Reports\AdaptiveCacheManager(
            $this->mockCache,
            $this->mockPerformanceMonitor,
            $this->mockLogger
        );
    }
    
    /**
     * Testa cálculo adaptativo do tempo de expiração com base em frequência de acesso
     */
    public function testAdaptiveExpirationCalculation(): void
    {
        // Configurar dados de frequência de acesso simulados
        $accessFrequency = [
            'daily_sales' => 45,  // Alta frequência
            'weekly_sales' => 15, // Frequência média
            'annual_report' => 2  // Baixa frequência
        ];
        
        // Injetar dados no objeto
        $reflection = new \ReflectionClass($this->cacheManager);
        $property = $reflection->getProperty('accessFrequency');
        $property->setAccessible(true);
        $property->setValue($this->cacheManager, $accessFrequency);
        
        // Calcular tempo de expiração para diferentes relatórios
        $highFrequencyTTL = $this->cacheManager->calculateOptimalTTL('daily_sales');
        $mediumFrequencyTTL = $this->cacheManager->calculateOptimalTTL('weekly_sales');
        $lowFrequencyTTL = $this->cacheManager->calculateOptimalTTL('annual_report');
        $unknownReportTTL = $this->cacheManager->calculateOptimalTTL('unknown_report');
        
        // Verificar que relatórios acessados frequentemente têm TTL mais longo
        $this->assertGreaterThan($mediumFrequencyTTL, $highFrequencyTTL);
        $this->assertGreaterThan($lowFrequencyTTL, $mediumFrequencyTTL);
        
        // Verificar que relatórios desconhecidos têm TTL padrão
        $this->assertEquals(3600, $unknownReportTTL); // TTL padrão é 1 hora
    }
    
    /**
     * Testa prefetching inteligente com base em padrões de acesso
     */
    public function testIntelligentPrefetching(): void
    {
        // Configurar dados simulados de acesso correlacionado
        $correlatedAccess = [
            'daily_sales' => ['products_by_category', 'customer_activity'],
            'customer_activity' => ['customer_retention', 'sales_by_customer']
        ];
        
        // Injetar dados
        $reflection = new \ReflectionClass($this->cacheManager);
        $property = $reflection->getProperty('correlatedReports');
        $property->setAccessible(true);
        $property->setValue($this->cacheManager, $correlatedAccess);
        
        // Expectativas para o mock de cache - deve buscar relatórios correlacionados
        $this->mockCache->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$this->equalTo('products_by_category')],
                [$this->equalTo('customer_activity')]
            );
        
        // Executar prefetching
        $this->cacheManager->prefetchCorrelatedReports('daily_sales');
    }
    
    /**
     * Testa invalidação seletiva de cache
     */
    public function testSelectiveInvalidation(): void
    {
        // Configurar dependências simuladas entre relatórios
        $reportDependencies = [
            'product_data' => ['products_by_category', 'top_products', 'product_performance'],
            'customer_data' => ['customer_activity', 'customer_retention']
        ];
        
        // Injetar dados
        $reflection = new \ReflectionClass($this->cacheManager);
        $property = $reflection->getProperty('reportDependencies');
        $property->setAccessible(true);
        $property->setValue($this->cacheManager, $reportDependencies);
        
        // Expectativas para o mock de cache - deve invalidar relatórios dependentes
        $this->mockCache->expects($this->exactly(3))
            ->method('delete')
            ->withConsecutive(
                [$this->equalTo('products_by_category')],
                [$this->equalTo('top_products')],
                [$this->equalTo('product_performance')]
            );
        
        // Executar invalidação seletiva
        $this->cacheManager->invalidateDependentReports('product_data');
    }
    
    /**
     * Testa ajuste de prioridade de cache com base em tamanho e tempo de geração
     */
    public function testCachePriorityAdjustment(): void
    {
        // Configurar dados simulados de performance
        $performanceMetrics = [
            'daily_sales' => ['generation_time' => 0.5, 'size' => 50000],
            'annual_report' => ['generation_time' => 5.0, 'size' => 500000],
            'customer_activity' => ['generation_time' => 2.0, 'size' => 200000]
        ];
        
        // Mock do monitor de performance
        $this->mockPerformanceMonitor->expects($this->once())
            ->method('getReportMetrics')
            ->willReturn($performanceMetrics);
        
        // Calcular prioridades de cache
        $priorities = $this->cacheManager->calculateCachePriorities();
        
        // Verificar que relatórios mais caros de gerar têm maior prioridade
        $this->assertGreaterThan($priorities['daily_sales'], $priorities['annual_report']);
        $this->assertGreaterThan($priorities['daily_sales'], $priorities['customer_activity']);
        
        // O relatório anual deve ter prioridade mais alta devido ao tempo de geração
        $this->assertGreaterThan($priorities['customer_activity'], $priorities['annual_report']);
    }
    
    /**
     * Testa gerenciamento dinâmico de caminho de cache com proteção contra path traversal
     */
    public function testDynamicCachePathWithProtection(): void
    {
        // Recuperar método protegido para teste
        $reflection = new \ReflectionClass($this->cacheManager);
        $method = $reflection->getMethod('buildCacheKey');
        $method->setAccessible(true);
        
        // Parâmetros potencialmente maliciosos
        $reportType = 'sales_report';
        $params = [
            'start_date' => '2025-01-01',
            'end_date' => '2025-03-31',
            'group_by' => 'month',
            'malicious' => '../../../etc/passwd'
        ];
        
        // Gerar chave de cache
        $cacheKey = $method->invoke($this->cacheManager, $reportType, $params);
        
        // Verificar que a chave não contém caracteres perigosos
        $this->assertStringNotContainsString('../', $cacheKey);
        $this->assertStringNotContainsString('/', $cacheKey);
        $this->assertStringNotContainsString('\\', $cacheKey);
        
        // Verificar que os parâmetros autorizados foram incluídos
        $this->assertStringContainsString('sales_report', $cacheKey);
        $this->assertStringContainsString('2025-01-01', $cacheKey);
        $this->assertStringContainsString('2025-03-31', $cacheKey);
        $this->assertStringContainsString('month', $cacheKey);
    }
    
    /**
     * Testa registro de estatísticas de acesso de forma thread-safe
     */
    public function testThreadSafeAccessStatistics(): void
    {
        // Configurar dados iniciais simulados
        $initialFrequency = [
            'daily_sales' => 10,
            'weekly_sales' => 5
        ];
        
        // Injetar dados
        $reflection = new \ReflectionClass($this->cacheManager);
        $property = $reflection->getProperty('accessFrequency');
        $property->setAccessible(true);
        $property->setValue($this->cacheManager, $initialFrequency);
        
        // Executar registros concorrentes (simulados)
        $this->cacheManager->recordAccess('daily_sales');
        $this->cacheManager->recordAccess('daily_sales');
        $this->cacheManager->recordAccess('weekly_sales');
        $this->cacheManager->recordAccess('new_report'); // Relatório não registrado anteriormente
        
        // Verificar contadores atualizados
        $currentFrequency = $property->getValue($this->cacheManager);
        
        $this->assertEquals(12, $currentFrequency['daily_sales']);
        $this->assertEquals(6, $currentFrequency['weekly_sales']);
        $this->assertEquals(1, $currentFrequency['new_report']);
    }
    
    /**
     * Testa limpeza segura de estatísticas obsoletas
     */
    public function testSafeCleanupOfStaleStatistics(): void
    {
        // Configurar dados iniciais simulados com timestamps
        $accessFrequency = [
            'daily_sales' => 10,
            'weekly_sales' => 5,
            'annual_report' => 2
        ];
        
        $lastAccess = [
            'daily_sales' => time(),
            'weekly_sales' => time() - 86400 * 31, // 31 dias atrás
            'annual_report' => time() - 86400 * 91 // 91 dias atrás
        ];
        
        // Injetar dados
        $reflection = new \ReflectionClass($this->cacheManager);
        $propFreq = $reflection->getProperty('accessFrequency');
        $propFreq->setAccessible(true);
        $propFreq->setValue($this->cacheManager, $accessFrequency);
        
        $propLast = $reflection->getProperty('lastAccess');
        $propLast->setAccessible(true);
        $propLast->setValue($this->cacheManager, $lastAccess);
        
        // Executar limpeza
        $this->cacheManager->cleanupStaleStatistics(30); // Limpar mais antigos que 30 dias
        
        // Verificar que estatísticas obsoletas foram removidas
        $currentFrequency = $propFreq->getValue($this->cacheManager);
        $currentLastAccess = $propLast->getValue($this->cacheManager);
        
        $this->assertArrayHasKey('daily_sales', $currentFrequency);
        $this->assertArrayNotHasKey('weekly_sales', $currentFrequency);
        $this->assertArrayNotHasKey('annual_report', $currentFrequency);
        
        $this->assertArrayHasKey('daily_sales', $currentLastAccess);
        $this->assertArrayNotHasKey('weekly_sales', $currentLastAccess);
        $this->assertArrayNotHasKey('annual_report', $currentLastAccess);
    }
    
    /**
     * Testa persistência segura de estatísticas de acesso
     */
    public function testSecureStatisticsPersistence(): void
    {
        // Mock do sistema de arquivos
        $fileSystem = $this->getMockBuilder(\App\Lib\FileSystem\FileSystemInterface::class)
            ->getMock();
            
        $fileSystem->expects($this->once())
            ->method('atomicWrite')
            ->with(
                $this->stringContains('cache_statistics'),
                $this->callback(function($content) {
                    // Verificar que o conteúdo é um JSON válido e contém os dados esperados
                    $data = json_decode($content, true);
                    return is_array($data) && 
                           isset($data['accessFrequency']) && 
                           isset($data['correlatedReports']) &&
                           isset($data['lastAccess']);
                })
            )
            ->willReturn(true);
            
        // Injetar mock do sistema de arquivos
        $reflection = new \ReflectionClass($this->cacheManager);
        $property = $reflection->getProperty('fileSystem');
        $property->setAccessible(true);
        $property->setValue($this->cacheManager, $fileSystem);
        
        // Executar persistência
        $result = $this->cacheManager->persistStatistics();
        
        // Verificar resultado
        $this->assertTrue($result);
    }
    
    /**
     * Testa detecção e tratamento de anomalias de cache
     */
    public function testCacheAnomalyDetection(): void
    {
        // Configurar dados simulados com anomalia (alta taxa de miss em relatório frequente)
        $cacheHits = [
            'daily_sales' => 2,
            'weekly_sales' => 15,
            'monthly_sales' => 10
        ];
        
        $cacheMisses = [
            'daily_sales' => 18, // Alta taxa de miss (90%)
            'weekly_sales' => 2,
            'monthly_sales' => 3
        ];
        
        // Injetar dados
        $reflection = new \ReflectionClass($this->cacheManager);
        $propHits = $reflection->getProperty('cacheHits');
        $propHits->setAccessible(true);
        $propHits->setValue($this->cacheManager, $cacheHits);
        
        $propMisses = $reflection->getProperty('cacheMisses');
        $propMisses->setAccessible(true);
        $propMisses->setValue($this->cacheManager, $cacheMisses);
        
        // Configurar mock do logger para verificar aviso
        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('Alta taxa de cache miss'),
                $this->callback(function($context) {
                    return isset($context['report']) && 
                           $context['report'] === 'daily_sales' &&
                           isset($context['miss_rate']) &&
                           $context['miss_rate'] > 0.8;
                })
            );
        
        // Executar detecção de anomalias
        $anomalies = $this->cacheManager->detectCacheAnomalies();
        
        // Verificar resultado
        $this->assertIsArray($anomalies);
        $this->assertArrayHasKey('daily_sales', $anomalies);
        $this->assertGreaterThan(0.8, $anomalies['daily_sales']['miss_rate']);
    }
    
    /**
     * Executa todos os testes desta classe
     *
     * @return bool Resultado dos testes
     */
    public function runAllTests(): bool
    {
        try {
            $this->setUp();
            $this->testAdaptiveExpirationCalculation();
            $this->testIntelligentPrefetching();
            $this->testSelectiveInvalidation();
            $this->testCachePriorityAdjustment();
            $this->testDynamicCachePathWithProtection();
            $this->testThreadSafeAccessStatistics();
            $this->testSafeCleanupOfStaleStatistics();
            $this->testSecureStatisticsPersistence();
            $this->testCacheAnomalyDetection();
            
            echo "✓ AdaptiveCacheManagerTest: Todos os testes passaram\n";
            return true;
        } catch (\Exception $e) {
            echo "✗ AdaptiveCacheManagerTest: Falha - " . $e->getMessage() . "\n";
            echo "  em " . $e->getFile() . ":" . $e->getLine() . "\n";
            return false;
        }
    }
}
