<?php
/**
 * ConcurrencyTest - Testes de concorrência e carga para o sistema de processamento assíncrono
 * 
 * @package Tests
 * @category Testing
 * @author Taverna da Impressão 3D Dev Team
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Models\AsyncProcess\StatusRepository;
use App\Lib\Security\RateLimiter;

class ConcurrencyTest extends TestCase
{
    /**
     * @var \PDO
     */
    private $db;
    
    /**
     * @var StatusRepository
     */
    private $statusRepository;
    
    /**
     * @var array
     */
    private $testProcesses = [];
    
    /**
     * Configuração inicial para todos os testes
     */
    protected function setUp(): void
    {
        // Conexão com banco de dados de teste
        $this->db = new \PDO(
            'mysql:host=localhost;dbname=taverna_test;charset=utf8mb4',
            'test_user',
            'test_password',
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ]
        );
        
        // Limpar tabelas de teste
        $this->cleanupTestData();
        
        // Criar instância do repositório
        $this->statusRepository = new StatusRepository($this->db);
    }
    
    /**
     * Limpeza após os testes
     */
    protected function tearDown(): void
    {
        // Limpar todos os processos de teste criados
        $this->cleanupTestData();
        
        // Fechar conexão com o banco
        $this->db = null;
    }
    
    /**
     * Testa a criação simultânea de múltiplos processos assíncronos
     */
    public function testConcurrentProcessCreation()
    {
        $numProcesses = 10;
        $processTokens = [];
        $userId = 1;
        
        // Criar processos simultaneamente
        for ($i = 0; $i < $numProcesses; $i++) {
            $processTokens[] = $this->statusRepository->createProcess(
                $userId,
                'test_process',
                false
            );
        }
        
        // Registrar para limpeza
        $this->testProcesses = array_merge($this->testProcesses, $processTokens);
        
        // Verificar se todos os processos foram criados corretamente
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM async_processes 
            WHERE process_token IN (" . implode(',', array_fill(0, count($processTokens), '?')) . ")
        ");
        $stmt->execute($processTokens);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals($numProcesses, $result['count'], 'Nem todos os processos foram criados corretamente');
    }
    
    /**
     * Testa atualizações concorrentes do mesmo processo
     */
    public function testConcurrentStatusUpdates()
    {
        // Criar um processo de teste
        $processToken = $this->statusRepository->createProcess(1, 'test_process', false);
        $this->testProcesses[] = $processToken;
        
        // Simular atualizações concorrentes
        $updateCount = 20;
        $threads = [];
        
        for ($i = 0; $i < $updateCount; $i++) {
            // Em um ambiente real, usaríamos threads/processos paralelos
            // Aqui simulamos com atualizações sequenciais rápidas
            $progress = ($i + 1) * 5; // 5%, 10%, 15%, etc.
            
            $this->statusRepository->updateProcessStatus($processToken, 'processing', [
                'progress_percentage' => $progress,
                'current_step' => "Etapa de teste {$i}"
            ]);
        }
        
        // Verificar valor final
        $processStatus = $this->statusRepository->getProcessStatus($processToken);
        
        $this->assertEquals(100, $processStatus['progress_percentage'], 'Valor final do progresso incorreto');
        $this->assertEquals("Etapa de teste " . ($updateCount - 1), $processStatus['current_step'], 'Valor final da etapa incorreto');
    }
    
    /**
     * Testa o comportamento do RateLimiter sob carga
     */
    public function testRateLimiterUnderLoad()
    {
        // Criar instância do rate limiter
        $rateLimiter = new RateLimiter($this->db);
        
        // Parâmetros de teste
        $key = 'test_endpoint';
        $windowSeconds = 10;
        $maxAttempts = 5;
        $identifier = 'test_user_123';
        
        // Primeiras requisições devem ser aceitas
        for ($i = 0; $i < $maxAttempts; $i++) {
            $result = $rateLimiter->check($key, $windowSeconds, $maxAttempts, $identifier);
            $this->assertTrue($result, "Requisição {$i} deveria ser aceita");
        }
        
        // Próximas requisições devem ser rejeitadas
        for ($i = 0; $i < 3; $i++) {
            $result = $rateLimiter->check($key, $windowSeconds, $maxAttempts, $identifier);
            $this->assertFalse($result, "Requisição excedente {$i} deveria ser rejeitada");
        }
        
        // Verificar contagem no banco de dados
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM rate_limit_entries 
            WHERE rate_key = ? AND ip_address = ?
        ");
        $stmt->execute(["rate_limit:{$key}:{$identifier}", $identifier]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals($maxAttempts, $result['count'], 'Número incorreto de entradas de rate limit');
        
        // Verificar registro de violações
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM rate_limit_violations 
            WHERE rate_key = ? AND ip_address = ?
        ");
        $stmt->execute(["rate_limit:{$key}:{$identifier}", $identifier]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals(3, $result['count'], 'Número incorreto de violações de rate limit');
    }
    
    /**
     * Testa comportamento da API sob alta concorrência
     */
    public function testApiConcurrency()
    {
        // Este teste requer uma ferramenta externa como k6 ou JMeter
        // Aqui vamos simular com um número menor de requisições
        
        // Criar um processo de teste
        $processToken = $this->statusRepository->createProcess(1, 'test_process', false);
        $this->testProcesses[] = $processToken;
        
        // Configurar número de requisições e concorrência
        $totalRequests = 50;
        $successCount = 0;
        $failureCount = 0;
        
        // Simular múltiplas requisições
        for ($i = 0; $i < $totalRequests; $i++) {
            try {
                $processStatus = $this->statusRepository->getProcessStatus($processToken);
                if ($processStatus !== null) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $failureCount++;
            }
        }
        
        $this->assertEquals($totalRequests, $successCount, 'Todas as requisições devem ser bem-sucedidas');
        $this->assertEquals(0, $failureCount, 'Não deve haver falhas nas requisições');
    }
    
    /**
     * Limpa dados de teste do banco de dados
     */
    private function cleanupTestData()
    {
        if (!empty($this->testProcesses)) {
            // Remover processos de teste
            $placeholders = implode(',', array_fill(0, count($this->testProcesses), '?'));
            
            $stmt = $this->db->prepare("DELETE FROM async_process_steps WHERE process_id IN (SELECT id FROM async_processes WHERE process_token IN ({$placeholders}))");
            $stmt->execute($this->testProcesses);
            
            $stmt = $this->db->prepare("DELETE FROM async_processes WHERE process_token IN ({$placeholders})");
            $stmt->execute($this->testProcesses);
        }
        
        // Limpar entradas de rate limit
        $stmt = $this->db->prepare("DELETE FROM rate_limit_entries WHERE rate_key LIKE 'rate_limit:test%'");
        $stmt->execute();
        
        $stmt = $this->db->prepare("DELETE FROM rate_limit_violations WHERE rate_key LIKE 'rate_limit:test%'");
        $stmt->execute();
    }
}
