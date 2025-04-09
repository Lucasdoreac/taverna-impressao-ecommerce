<?php
/**
 * Teste Unitário para AdvancedReportCache
 * 
 * Verifica o comportamento do sistema de cache avançado para relatórios, incluindo:
 * - Proteção contra path traversal
 * - Armazenamento seguro de dados
 * - Validação de chaves de cache
 * - Escrita atômica para prevenir race conditions
 * - Compressão e descompressão segura
 */

use PHPUnit\Framework\TestCase;

class AdvancedReportCacheTest extends TestCase
{
    /**
     * @var AdvancedReportCache
     */
    private $cache;
    
    /**
     * @var string
     */
    private $tempCacheDir;
    
    /**
     * @var LoggerInterface
     */
    private $mockLogger;
    
    /**
     * Configuração para testes
     */
    public function setUp(): void
    {
        // Criar diretório temporário para testes
        $this->tempCacheDir = sys_get_temp_dir() . '/report_cache_test_' . uniqid();
        mkdir($this->tempCacheDir, 0755, true);
        
        // Mock do logger
        $this->mockLogger = $this->createMock(\Psr\Log\LoggerInterface::class);
        
        // Configurar o sistema de cache
        $this->cache = new AdvancedReportCache($this->tempCacheDir, $this->mockLogger);
    }
    
    /**
     * Limpeza após testes
     */
    public function tearDown(): void
    {
        // Remover diretório temporário
        $this->removeDirectory($this->tempCacheDir);
    }
    
    /**
     * Testa proteção contra path traversal nas chaves de cache
     */
    public function testPathTraversalProtection(): void
    {
        // Tentativa de path traversal através da chave de cache
        $maliciousKey = '../../../etc/passwd';
        
        // Verificar se o logger recebe uma mensagem de aviso
        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('Tentativa potencial de path traversal'),
                $this->callback(function($context) use ($maliciousKey) {
                    return isset($context['original_key']) && 
                           $context['original_key'] === $maliciousKey;
                })
            );
        
        // Obter caminho do cache para a chave maliciosa
        $cachePath = $this->cache->getCachePath($maliciousKey);
        
        // Verificar que o caminho resultante não contém a sequência de path traversal
        $this->assertStringNotContainsString('../', $cachePath);
        $this->assertStringNotContainsString('passwd', $cachePath);
    }
    
    /**
     * Testa validação de chaves de cache
     */
    public function testCacheKeyValidation(): void
    {
        $invalidKeys = [
            'key with spaces',
            'key/with/slashes',
            'key\\with\\backslashes',
            'key:with:colons',
            'key"with"quotes',
            '<script>alert("xss")</script>'
        ];
        
        foreach ($invalidKeys as $key) {
            // Verificar se o logger recebe uma mensagem de aviso
            $this->mockLogger->expects($this->atLeastOnce())
                ->method('warning');
            
            // Obter caminho do cache
            $cachePath = $this->cache->getCachePath($key);
            
            // Verificar que o caminho resultante é sanitizado
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_\-]+\.cache$/', basename($cachePath));
        }
    }
    
    /**
     * Testa armazenamento e recuperação de dados do cache
     */
    public function testCacheSetAndGet(): void
    {
        $key = 'sales_report_2025_03';
        $data = [
            'total_sales' => 15000,
            'total_orders' => 150,
            'avg_order_value' => 100
        ];
        
        // Armazenar dados no cache
        $this->cache->set($key, $data, 3600);
        
        // Verificar existência do arquivo de cache
        $cachePath = $this->cache->getCachePath($key);
        $this->assertFileExists($cachePath);
        
        // Recuperar dados do cache
        $cachedData = $this->cache->get($key);
        
        // Verificar dados recuperados
        $this->assertEquals($data, $cachedData);
    }
    
    /**
     * Testa compressão e descompressão segura de dados
     */
    public function testSecureCompression(): void
    {
        $key = 'large_report_data';
        
        // Gerar grande conjunto de dados para forçar compressão
        $largeData = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeData[] = [
                'id' => $i,
                'name' => 'Product ' . $i,
                'price' => rand(1000, 10000) / 100,
                'description' => str_repeat('Lorem ipsum dolor sit amet ', 20)
            ];
        }
        
        // Armazenar dados com compressão
        $this->cache->set($key, $largeData, 3600, true);
        
        // Recuperar dados do cache
        $cachedData = $this->cache->get($key);
        
        // Verificar dados recuperados
        $this->assertCount(1000, $cachedData);
        $this->assertEquals($largeData[0]['id'], $cachedData[0]['id']);
        $this->assertEquals($largeData[999]['id'], $cachedData[999]['id']);
    }
    
    /**
     * Testa expiração de cache
     */
    public function testCacheExpiration(): void
    {
        $key = 'expiring_report';
        $data = ['value' => 'test'];
        
        // Armazenar dados com expiração curta
        $this->cache->set($key, $data, 1);
        
        // Verificar disponibilidade imediata
        $this->assertEquals($data, $this->cache->get($key));
        
        // Aguardar expiração
        sleep(2);
        
        // Verificar que os dados expiraram
        $this->assertNull($this->cache->get($key));
    }
    
    /**
     * Testa escrita atômica para prevenção de race conditions
     */
    public function testAtomicWrite(): void
    {
        $key = 'atomic_write_test';
        $data = ['value' => 'test'];
        
        // Mock do sistema de arquivos para verificar escrita atômica
        $fileSystem = $this->getMockBuilder(\App\Lib\FileSystem\FileSystemInterface::class)
            ->getMock();
            
        $fileSystem->expects($this->once())
            ->method('atomicWrite')
            ->with(
                $this->stringContains($key),
                $this->anything()
            )
            ->willReturn(true);
            
        // Injetar mock do sistema de arquivos
        $reflection = new \ReflectionClass($this->cache);
        $property = $reflection->getProperty('fileSystem');
        $property->setAccessible(true);
        $property->setValue($this->cache, $fileSystem);
        
        // Executar escrita
        $this->cache->set($key, $data, 3600);
    }
    
    /**
     * Testa manipulação segura de erros durante leitura/escrita
     */
    public function testSecureErrorHandling(): void
    {
        $key = 'error_handling_test';
        
        // Verificar manipulação de erro de escrita
        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Falha ao escrever cache'));
            
        // Injetar função de escrita que falha
        $reflection = new \ReflectionClass($this->cache);
        $method = $reflection->getMethod('writeCache');
        $method->setAccessible(true);
        
        // Executar método que deve gerar erro
        $method->invoke($this->cache, '/caminho/invalido/que/nao/existe', serialize(['data' => 'test']));
    }
    
    /**
     * Testa limpeza segura do cache
     */
    public function testSecureCacheClear(): void
    {
        // Criar alguns arquivos de cache para teste
        $testKeys = ['test1', 'test2', 'test3'];
        foreach ($testKeys as $key) {
            $this->cache->set($key, ['data' => $key], 3600);
        }
        
        // Verificar que os arquivos existem
        foreach ($testKeys as $key) {
            $this->assertFileExists($this->cache->getCachePath($key));
        }
        
        // Limpar o cache
        $result = $this->cache->clear();
        
        // Verificar resultado
        $this->assertTrue($result);
        
        // Verificar que os arquivos foram removidos
        foreach ($testKeys as $key) {
            $this->assertFileDoesNotExist($this->cache->getCachePath($key));
        }
    }
    
    /**
     * Testa recuperação eficiente de metadados de cache
     */
    public function testCacheMetadata(): void
    {
        // Criar alguns arquivos de cache com tempos diferentes
        $this->cache->set('recent', ['data' => 'recent'], 3600);
        sleep(1);
        $this->cache->set('older', ['data' => 'older'], 1800);
        
        // Obter metadados
        $metadata = $this->cache->getMetadata();
        
        // Verificar estrutura de metadados
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('count', $metadata);
        $this->assertArrayHasKey('size', $metadata);
        $this->assertArrayHasKey('oldest', $metadata);
        $this->assertArrayHasKey('newest', $metadata);
        
        // Verificar valores básicos
        $this->assertGreaterThanOrEqual(2, $metadata['count']);
        $this->assertGreaterThan(0, $metadata['size']);
        
        // Verificar ordem cronológica
        $this->assertLessThan($metadata['newest']['timestamp'], $metadata['oldest']['timestamp']);
    }
    
    /**
     * Método auxiliar para remover diretório recursivamente
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
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
            $this->testPathTraversalProtection();
            $this->testCacheKeyValidation();
            $this->testCacheSetAndGet();
            $this->testSecureCompression();
            $this->testCacheExpiration();
            $this->testAtomicWrite();
            $this->testSecureErrorHandling();
            $this->testSecureCacheClear();
            $this->testCacheMetadata();
            $this->tearDown();
            
            echo "✓ AdvancedReportCacheTest: Todos os testes passaram\n";
            return true;
        } catch (\Exception $e) {
            echo "✗ AdvancedReportCacheTest: Falha - " . $e->getMessage() . "\n";
            echo "  em " . $e->getFile() . ":" . $e->getLine() . "\n";
            return false;
        }
    }
}
