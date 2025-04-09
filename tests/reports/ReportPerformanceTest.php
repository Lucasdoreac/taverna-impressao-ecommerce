<?php
/**
 * Teste de Performance para Sistema de Relatórios Otimizado
 * 
 * Este teste mede o desempenho real das otimizações implementadas no sistema de relatórios,
 * comparando o desempenho do sistema original com o sistema otimizado em termos de:
 * - Tempo de execução
 * - Uso de memória
 * - Eficiência de cache
 * - Resposta sob carga
 */

use PHPUnit\Framework\TestCase;

class ReportPerformanceTest extends TestCase
{
    /**
     * @var array Métricas de performance coletadas durante os testes
     */
    private $metrics = [];
    
    /**
     * @var \App\Lib\Reports\ReportModel Modelo de relatório original
     */
    private $originalModel;
    
    /**
     * @var \App\Lib\Reports\OptimizedReportModel Modelo de relatório otimizado
     */
    private $optimizedModel;
    
    /**
     * @var \App\Lib\Reports\AdvancedReportCache Sistema de cache avançado
     */
    private $advancedCache;
    
    /**
     * @var \App\Lib\Reports\ReportCache Sistema de cache original
     */
    private $originalCache;
    
    /**
     * @var \PDO Instância do banco de dados
     */
    private $db;
    
    /**
     * Configuração para testes
     */
    public function setUp(): void
    {
        // Configurar conexão com o banco de dados
        $this->db = new \PDO(
            'mysql:host=localhost;dbname=taverna_test;charset=utf8',
            'test_user',
            'test_password',
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ]
        );
        
        // Inicializar modelos
        $this->originalModel = new \App\Lib\Reports\ReportModel($this->db);
        $this->optimizedModel = new \App\Lib\Reports\OptimizedReportModel($this->db);
        
        // Inicializar sistemas de cache
        $tempDir = sys_get_temp_dir() . '/performance_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        
        $this->originalCache = new \App\Lib\Reports\ReportCache($tempDir . '/original');
        $this->advancedCache = new \App\Lib\Reports\AdvancedReportCache($tempDir . '/optimized');
        
        // Limpar métricas
        $this->metrics = [];
    }
    
    /**
     * Testa o desempenho da geração de relatório de vendas
     * Compara o modelo original com o otimizado
     */
    public function testSalesReportPerformance(): void
    {
        $startDate = '2025-01-01';
        $endDate = '2025-03-31';
        $period = 'month';
        
        // Testar modelo original
        $this->measurePerformance('original_sales', function() use ($startDate, $endDate, $period) {
            return $this->originalModel->getSalesData($startDate, $endDate, $period);
        });
        
        // Testar modelo otimizado
        $this->measurePerformance('optimized_sales', function() use ($startDate, $endDate, $period) {
            return $this->optimizedModel->getSalesData($startDate, $endDate, $period);
        });
        
        // Verificar métricas
        $this->assertMetricImprovement('original_sales', 'optimized_sales', 'time');
        $this->assertMetricImprovement('original_sales', 'optimized_sales', 'memory');
    }
    
    /**
     * Testa o desempenho do sistema de cache
     * Compara o cache original com o cache avançado
     */
    public function testCachePerformance(): void
    {
        $key = 'performance_test_' . uniqid();
        $data = $this->generateLargeDataset(5000);
        
        // Medir desempenho do cache original
        $this->measurePerformance('original_cache_write', function() use ($key, $data) {
            return $this->originalCache->set($key, $data, 3600);
        });
        
        $this->measurePerformance('original_cache_read', function() use ($key) {
            return $this->originalCache->get($key);
        });
        
        // Medir desempenho do cache avançado
        $this->measurePerformance('advanced_cache_write', function() use ($key, $data) {
            return $this->advancedCache->set($key, $data, 3600, true); // Com compressão
        });
        
        $this->measurePerformance('advanced_cache_read', function() use ($key) {
            return $this->advancedCache->get($key);
        });
        
        // Verificar métricas
        $this->assertMetricImprovement('original_cache_write', 'advanced_cache_write', 'memory');
        $this->assertMetricImprovement('original_cache_read', 'advanced_cache_read', 'time');
        
        // Verificar tamanho do arquivo de cache (compressão)
        $originalSize = filesize($this->originalCache->getCachePath($key));
        $advancedSize = filesize($this->advancedCache->getCachePath($key));
        
        $this->assertLessThan($originalSize, $advancedSize, 'O cache avançado deve ter um tamanho de arquivo menor devido à compressão');
    }
    
    /**
     * Testa o desempenho de carregamento sob condições de contêiner
     * Simula múltiplas consultas concorrentes
     */
    public function testConcurrentLoadPerformance(): void
    {
        $startDate = '2025-01-01';
        $endDate = '2025-03-31';
        $iterations = 10; // Número de consultas "concorrentes"
        
        // Testar modelo original sob carga
        $this->measurePerformance('original_concurrent', function() use ($startDate, $endDate, $iterations) {
            $results = [];
            for ($i = 0; $i < $iterations; $i++) {
                $offset = $i * 7; // Deslocar datas para simular consultas diferentes
                $adjustedStart = date('Y-m-d', strtotime($startDate . " +$offset days"));
                $adjustedEnd = date('Y-m-d', strtotime($endDate . " +$offset days"));
                
                $results[] = $this->originalModel->getSalesData($adjustedStart, $adjustedEnd, 'day');
            }
            return $results;
        });
        
        // Testar modelo otimizado sob carga
        $this->measurePerformance('optimized_concurrent', function() use ($startDate, $endDate, $iterations) {
            $results = [];
            for ($i = 0; $i < $iterations; $i++) {
                $offset = $i * 7;
                $adjustedStart = date('Y-m-d', strtotime($startDate . " +$offset days"));
                $adjustedEnd = date('Y-m-d', strtotime($endDate . " +$offset days"));
                
                $results[] = $this->optimizedModel->getSalesData($adjustedStart, $adjustedEnd, 'day');
            }
            return $results;
        });
        
        // Verificar métricas
        $this->assertMetricImprovement('original_concurrent', 'optimized_concurrent', 'time');
        $this->assertMetricImprovement('original_concurrent', 'optimized_concurrent', 'memory');
        
        // A melhoria de performance em carga deve ser mais significativa
        $timeSingle = $this->getImprovementPercentage('original_sales', 'optimized_sales', 'time');
        $timeConcurrent = $this->getImprovementPercentage('original_concurrent', 'optimized_concurrent', 'time');
        
        $this->assertGreaterThan($timeSingle, $timeConcurrent, 'A melhoria de performance deve ser mais significativa sob carga');
    }
    
    /**
     * Testa o desempenho da geração de relatórios grandes
     * Compara o processamento em chunks vs. carregamento completo
     */
    public function testLargeReportPerformance(): void
    {
        $startDate = '2025-01-01';
        $endDate = '2025-03-31';
        
        // Testar relatório completo (sem chunks)
        $this->measurePerformance('no_chunks', function() use ($startDate, $endDate) {
            return $this->originalModel->getCustomerActivityReport($startDate, $endDate, false);
        });
        
        // Testar relatório com processamento em chunks
        $this->measurePerformance('with_chunks', function() use ($startDate, $endDate) {
            return $this->optimizedModel->getCustomerActivityReport($startDate, $endDate, true);
        });
        
        // Verificar métricas
        $this->assertMetricImprovement('no_chunks', 'with_chunks', 'memory');
        // Tempo pode ser ligeiramente maior devido ao overhead de múltiplas consultas
        
        // Verificar uso máximo de memória - esse é o ponto crítico para datasets grandes
        $this->assertLessThan(
            $this->metrics['no_chunks']['peak_memory'],
            $this->metrics['with_chunks']['peak_memory'],
            'O processamento em chunks deve usar significativamente menos memória de pico'
        );
    }
    
    /**
     * Testa o desempenho do sistema de relatórios sob cargas complexas
     * Incluindo filtragem, agrupamento e ordenação
     */
    public function testComplexQueryPerformance(): void
    {
        // Parâmetros complexos
        $filters = [
            'start_date' => '2025-01-01',
            'end_date' => '2025-03-31',
            'min_value' => 1000,
            'product_category' => 'impressão-3d',
            'exclude_statuses' => ['canceled', 'refunded'],
            'group_by' => 'week',
            'sort' => 'total_desc'
        ];
        
        // Testar modelo original com consulta complexa
        $this->measurePerformance('original_complex', function() use ($filters) {
            return $this->originalModel->getFilteredReport($filters);
        });
        
        // Testar modelo otimizado com consulta complexa
        $this->measurePerformance('optimized_complex', function() use ($filters) {
            return $this->optimizedModel->getFilteredReport($filters);
        });
        
        // Verificar métricas
        $this->assertMetricImprovement('original_complex', 'optimized_complex', 'time');
        $this->assertMetricImprovement('original_complex', 'optimized_complex', 'memory');
        
        // Para consultas complexas, a melhoria de tempo deve ser mais significativa
        $timeSimple = $this->getImprovementPercentage('original_sales', 'optimized_sales', 'time');
        $timeComplex = $this->getImprovementPercentage('original_complex', 'optimized_complex', 'time');
        
        $this->assertGreaterThan($timeSimple, $timeComplex, 'A melhoria de performance deve ser mais significativa para consultas complexas');
    }
    
    /**
     * Testa o desempenho do sistema de relatórios para exportação de arquivos grandes
     */
    public function testLargeExportPerformance(): void
    {
        $startDate = '2025-01-01';
        $endDate = '2025-03-31';
        $format = 'csv';
        
        // Testar exportação com modelo original
        $this->measurePerformance('original_export', function() use ($startDate, $endDate, $format) {
            $data = $this->originalModel->getSalesData($startDate, $endDate, 'day');
            return (new \App\Lib\Export\ExcelExport())->generate($data, 'Vendas Diárias', $format);
        });
        
        // Testar exportação com modelo otimizado
        $this->measurePerformance('optimized_export', function() use ($startDate, $endDate, $format) {
            $data = $this->optimizedModel->getSalesData($startDate, $endDate, 'day');
            return (new \App\Lib\Export\ExcelExport())->generate($data, 'Vendas Diárias', $format);
        });
        
        // Verificar métricas
        $this->assertMetricImprovement('original_export', 'optimized_export', 'time');
        $this->assertMetricImprovement('original_export', 'optimized_export', 'memory');
    }
    
    /**
     * Método auxiliar para medir desempenho de uma função
     *
     * @param string $label Identificador para a métrica
     * @param callable $callback Função a ser executada e medida
     * @return mixed Resultado da função
     */
    private function measurePerformance($label, callable $callback)
    {
        // Registrar métricas iniciais
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Executar função
        $result = $callback();
        
        // Registrar métricas finais
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();
        
        // Calcular métricas
        $this->metrics[$label] = [
            'time' => $endTime - $startTime,
            'memory' => $endMemory - $startMemory,
            'peak_memory' => $peakMemory
        ];
        
        return $result;
    }
    
    /**
     * Método auxiliar para verificar se houve melhoria na métrica entre o original e o otimizado
     *
     * @param string $originalLabel Identificador para a métrica original
     * @param string $optimizedLabel Identificador para a métrica otimizada
     * @param string $metric Nome da métrica (time, memory)
     * @return void
     */
    private function assertMetricImprovement($originalLabel, $optimizedLabel, $metric)
    {
        $original = $this->metrics[$originalLabel][$metric];
        $optimized = $this->metrics[$optimizedLabel][$metric];
        
        $this->assertLessThan(
            $original,
            $optimized,
            sprintf(
                "A versão otimizada deve ter um %s menor que a original. Original: %.2f, Otimizado: %.2f",
                $metric === 'time' ? 'tempo de execução' : 'uso de memória',
                $original,
                $optimized
            )
        );
    }
    
    /**
     * Calcula a porcentagem de melhoria entre duas métricas
     *
     * @param string $originalLabel Identificador para a métrica original
     * @param string $optimizedLabel Identificador para a métrica otimizada
     * @param string $metric Nome da métrica (time, memory)
     * @return float Porcentagem de melhoria
     */
    private function getImprovementPercentage($originalLabel, $optimizedLabel, $metric)
    {
        $original = $this->metrics[$originalLabel][$metric];
        $optimized = $this->metrics[$optimizedLabel][$metric];
        
        return ($original - $optimized) / $original * 100;
    }
    
    /**
     * Gera um conjunto de dados grande para testes
     *
     * @param int $size Tamanho do conjunto de dados
     * @return array Dataset gerado
     */
    private function generateLargeDataset($size)
    {
        $data = [];
        for ($i = 0; $i < $size; $i++) {
            $data[] = [
                'id' => $i,
                'date' => date('Y-m-d', strtotime('2025-01-01') + $i * 86400),
                'customer_id' => rand(1, 1000),
                'order_id' => 'ORD-' . str_pad($i, 8, '0', STR_PAD_LEFT),
                'product_id' => rand(1, 500),
                'product_name' => 'Produto de Teste ' . rand(1, 100),
                'category' => 'Categoria ' . rand(1, 10),
                'quantity' => rand(1, 5),
                'unit_price' => rand(1000, 50000) / 100,
                'total_price' => rand(1000, 50000) / 100,
                'status' => ['completed', 'processing', 'shipped', 'canceled', 'refunded'][rand(0, 4)],
                'payment_method' => ['credit_card', 'boleto', 'pix', 'bank_transfer'][rand(0, 3)],
                'shipping_method' => ['standard', 'express', 'pickup'][rand(0, 2)],
                'notes' => str_repeat('Lorem ipsum dolor sit amet ', rand(1, 10))
            ];
        }
        return $data;
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
            $this->testSalesReportPerformance();
            $this->testCachePerformance();
            $this->testConcurrentLoadPerformance();
            $this->testLargeReportPerformance();
            $this->testComplexQueryPerformance();
            $this->testLargeExportPerformance();
            
            // Exibir métricas de performance
            echo "\nMétricas de Performance:\n";
            foreach ($this->metrics as $label => $metrics) {
                echo "- $label:\n";
                echo "  - Tempo: " . number_format($metrics['time'], 4) . " segundos\n";
                echo "  - Memória: " . number_format($metrics['memory'] / 1024 / 1024, 2) . " MB\n";
                echo "  - Memória de pico: " . number_format($metrics['peak_memory'] / 1024 / 1024, 2) . " MB\n";
            }
            
            // Calcular melhorias percentuais
            $this->displayImprovements();
            
            echo "\n✓ ReportPerformanceTest: Todos os testes passaram\n";
            return true;
        } catch (\Exception $e) {
            echo "✗ ReportPerformanceTest: Falha - " . $e->getMessage() . "\n";
            echo "  em " . $e->getFile() . ":" . $e->getLine() . "\n";
            return false;
        }
    }
    
    /**
     * Exibe as melhorias percentuais entre versões originais e otimizadas
     */
    private function displayImprovements()
    {
        $comparisons = [
            ['original_sales', 'optimized_sales', 'Relatório de Vendas Básico'],
            ['original_cache_write', 'advanced_cache_write', 'Escrita em Cache'],
            ['original_cache_read', 'advanced_cache_read', 'Leitura de Cache'],
            ['original_concurrent', 'optimized_concurrent', 'Carga Concorrente'],
            ['no_chunks', 'with_chunks', 'Processamento em Chunks'],
            ['original_complex', 'optimized_complex', 'Consulta Complexa'],
            ['original_export', 'optimized_export', 'Exportação de Relatório']
        ];
        
        echo "\nMelhorias de Performance:\n";
        
        foreach ($comparisons as [$original, $optimized, $label]) {
            // Verificar se as métricas existem
            if (!isset($this->metrics[$original]) || !isset($this->metrics[$optimized])) {
                continue;
            }
            
            $timeImprovement = $this->getImprovementPercentage($original, $optimized, 'time');
            $memoryImprovement = $this->getImprovementPercentage($original, $optimized, 'memory');
            
            echo "- $label:\n";
            echo "  - Tempo: " . number_format($timeImprovement, 2) . "% de melhoria\n";
            echo "  - Memória: " . number_format($memoryImprovement, 2) . "% de melhoria\n";
        }
    }
}
