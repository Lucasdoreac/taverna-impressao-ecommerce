<?php
/**
 * Teste Unitário para OptimizedReportModel
 * 
 * Verifica o comportamento do modelo otimizado de relatórios, incluindo:
 * - Consultas SQL seguras com prepared statements
 * - Processamento em chunks para grandes datasets
 * - Agregação e manipulação segura de dados
 * - Otimizações de performance
 */

use PHPUnit\Framework\TestCase;

class OptimizedReportModelTest extends TestCase
{
    /**
     * @var OptimizedReportModel
     */
    private $model;
    
    /**
     * @var PDO
     */
    private $mockDb;
    
    /**
     * @var PDOStatement
     */
    private $mockStmt;
    
    /**
     * Configuração para testes
     */
    public function setUp(): void
    {
        // Mock do PDO e PDOStatement
        $this->mockStmt = $this->createMock(PDOStatement::class);
        $this->mockDb = $this->createMock(PDO::class);
        
        // Configurar o modelo com o mock do banco de dados
        $this->model = new OptimizedReportModel($this->mockDb);
    }
    
    /**
     * Testa se getSalesData utiliza prepared statements corretamente
     */
    public function testGetSalesDataUsesPreparedStatements(): void
    {
        // Configurar expectativas para o mock
        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WITH filtered_orders AS'))
            ->willReturn($this->mockStmt);
            
        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->with($this->callback(function($params) {
                // Verificar se os parâmetros são passados corretamente
                return count($params) === 2 && 
                       strpos($params[0], '00:00:00') !== false &&
                       strpos($params[1], '23:59:59') !== false;
            }));
            
        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                ['period_label' => '2025-03', 'order_count' => 10, 'sales_amount' => 1000]
            ]);
            
        // Executar o método sendo testado
        $result = $this->model->getSalesData('2025-03-01', '2025-03-31', 'month');
        
        // Verificar resultado
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('2025-03', $result[0]['period_label']);
    }
    
    /**
     * Testa se a validação de parâmetros de agrupamento funciona corretamente
     */
    public function testValidatesGroupByParameter(): void
    {
        $this->expectException(\App\Lib\Security\SecurityException::class);
        
        // Tentativa de injeção SQL através do parâmetro de agrupamento
        $this->model->getSalesData('2025-03-01', '2025-03-31', "month' OR '1'='1");
    }
    
    /**
     * Testa processamento em chunks para grandes conjuntos de dados
     */
    public function testLargeDatasetProcessingWithChunks(): void
    {
        // Configurar mock para simular dados grandes
        $this->mockDb->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->mockStmt);
            
        // Primeiro chunk
        $this->mockStmt->expects($this->at(0))
            ->method('execute')
            ->with($this->anything());
            
        $this->mockStmt->expects($this->at(1))
            ->method('fetchAll')
            ->willReturn($this->generateMockData(1000));
            
        // Segundo chunk
        $this->mockStmt->expects($this->at(2))
            ->method('execute')
            ->with($this->anything());
            
        $this->mockStmt->expects($this->at(3))
            ->method('fetchAll')
            ->willReturn($this->generateMockData(1000));
            
        // Terceiro chunk (último)
        $this->mockStmt->expects($this->at(4))
            ->method('execute')
            ->with($this->anything());
            
        $this->mockStmt->expects($this->at(5))
            ->method('fetchAll')
            ->willReturn($this->generateMockData(500));
        
        // Executar método que processa dados grandes
        $result = $this->model->getCustomerActivityReport('2025-01-01', '2025-03-31', true);
        
        // Verificar resultados
        $this->assertIsArray($result);
        $this->assertCount(2500, $result); // Total de linhas de todos os chunks
    }
    
    /**
     * Testa proteção contra SQL injection em campos de data
     */
    public function testSQLInjectionProtectionInDateParams(): void
    {
        // Configurar expectativas para o mock
        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);
            
        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->with($this->callback(function($params) {
                // Os parâmetros de data devem ser tratados como strings
                // e não permitir execução de SQL malicioso
                return is_string($params[0]) && is_string($params[1]);
            }));
            
        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);
            
        // Tentativa de SQL injection nos parâmetros de data
        $result = $this->model->getSalesData("2025-03-01' OR '1'='1", "2025-03-31' OR DELETE FROM users; --", 'month');
        
        // A chamada deve ter sido feita com segurança com prepared statements
        $this->assertIsArray($result);
    }
    
    /**
     * Testa formatação e sanitização de saída de dados
     */
    public function testOutputSanitization(): void
    {
        // Configurar mocks
        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStmt);
            
        // Dados potencialmente perigosos com tags HTML
        $maliciousData = [
            [
                'period_label' => '<script>alert("XSS")</script>2025-03',
                'order_count' => '<b>10</b>',
                'sales_amount' => '1000; DROP TABLE orders;',
                'user_name' => "O'Malley <script>alert('XSS')</script>"
            ]
        ];
        
        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->with($this->anything());
            
        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->willReturn($maliciousData);
            
        // Executar o método sendo testado
        $result = $this->model->getFormattedReport('2025-03-01', '2025-03-31', 'month');
        
        // Verificar sanitização dos resultados
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        
        // Verificar se o conteúdo foi sanitizado (tags removidas, aspas escapadas)
        $this->assertStringNotContainsString('<script>', $result[0]['period_label']);
        $this->assertStringNotContainsString('<b>', $result[0]['order_count']);
        $this->assertStringNotContainsString('DROP TABLE', $result[0]['sales_amount']);
    }
    
    /**
     * Testa a eficiência das consultas usando CTEs
     */
    public function testQueryOptimizationWithCTE(): void
    {
        // Configurar expectativas para o mock
        $this->mockDb->expects($this->once())
            ->method('prepare')
            ->with($this->logicalAnd(
                $this->stringContains('WITH filtered_orders AS'),
                $this->stringContains('period_orders AS'),
                $this->stringContains('period_stats AS')
            ))
            ->willReturn($this->mockStmt);
            
        $this->mockStmt->expects($this->once())
            ->method('execute')
            ->with($this->anything());
            
        $this->mockStmt->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);
            
        // Executar o método sendo testado
        $this->model->getSalesData('2025-03-01', '2025-03-31', 'month');
        
        // O teste passa se a consulta foi construída com CTEs conforme esperado
    }
    
    /**
     * Gera dados mock para testes de grandes datasets
     *
     * @param int $count Número de registros a gerar
     * @return array
     */
    private function generateMockData(int $count): array
    {
        $data = [];
        for ($i = 0; $i < $count; $i++) {
            $data[] = [
                'id' => $i,
                'user_id' => rand(1, 1000),
                'order_count' => rand(1, 20),
                'total_spent' => rand(1000, 50000) / 100,
                'date' => '2025-' . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . 
                          str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT)
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
            $this->testGetSalesDataUsesPreparedStatements();
            $this->testValidatesGroupByParameter();
            $this->testLargeDatasetProcessingWithChunks();
            $this->testSQLInjectionProtectionInDateParams();
            $this->testOutputSanitization();
            $this->testQueryOptimizationWithCTE();
            
            echo "✓ OptimizedReportModelTest: Todos os testes passaram\n";
            return true;
        } catch (\Exception $e) {
            echo "✗ OptimizedReportModelTest: Falha - " . $e->getMessage() . "\n";
            echo "  em " . $e->getFile() . ":" . $e->getLine() . "\n";
            return false;
        }
    }
}
