<?php
/**
 * Script para execução de testes de carga na Fila de Impressão
 * 
 * Este script executa testes de carga no Sistema de Fila de Impressão,
 * seguindo os guardrails de segurança estabelecidos.
 * 
 * @package Tests\Load
 * @version 1.0.0
 * @author Taverna da Impressão
 */

// Definir limite de tempo adequado para execução dos testes
set_time_limit(300); // 5 minutos

// Carregar classes necessárias
require_once dirname(__FILE__) . '/../../app/lib/Testing/LoadTest/PrintQueueLoadTest.php';

// Verificar argumentos da linha de comando
$options = getopt('c:r:t:', ['config:', 'results:', 'test:']);
$configFile = $options['c'] ?? $options['config'] ?? null;
$resultsFile = $options['r'] ?? $options['results'] ?? "load_test_results_" . date('YmdHis') . ".json";
$testType = $options['t'] ?? $options['test'] ?? 'full';

// Verificar caminho absoluto para resultados
if (!preg_match('/^C:\\\\MCP\\\\taverna\\\\taverna-impressao-ecommerce\\\\/', $resultsFile)) {
    $resultsFile = "C:\\MCP\\taverna\\taverna-impressao-ecommerce\\tests\\load\\results\\" . $resultsFile;
}

// Carregar configuração personalizada, se fornecida
$config = [];
if ($configFile) {
    if (!file_exists($configFile)) {
        die("Arquivo de configuração não encontrado: $configFile\n");
    }
    
    $config = json_decode(file_get_contents($configFile), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Erro ao decodificar arquivo de configuração: " . json_last_error_msg() . "\n");
    }
}

// Validar e sanitizar o tipo de teste
$validTestTypes = ['full', 'peak', 'query', 'all'];
if (!in_array($testType, $validTestTypes)) {
    die("Tipo de teste inválido. Use um dos seguintes: " . implode(', ', $validTestTypes) . "\n");
}

try {
    // Criar instância do teste de carga
    $loadTest = new PrintQueueLoadTest($config);
    
    echo "Iniciando testes de carga do Sistema de Fila de Impressão\n";
    echo "Tipo de teste: $testType\n";
    echo "Arquivo de resultados: $resultsFile\n\n";
    
    $startTime = microtime(true);
    $results = [];
    
    // Executar o tipo de teste selecionado
    switch ($testType) {
        case 'full':
            echo "Executando teste completo de fluxo...\n";
            $results['full'] = $loadTest->runFullQueueTest();
            break;
            
        case 'peak':
            echo "Executando teste de pico de adição à fila...\n";
            $results['peak'] = $loadTest->runQueueAdditionPeakTest();
            break;
            
        case 'query':
            echo "Executando teste de consulta em massa...\n";
            $results['query'] = $loadTest->runBulkQueryTest();
            break;
            
        case 'all':
            echo "Executando todos os testes (completo, pico, consulta)...\n";
            echo "1. Teste completo de fluxo...\n";
            $results['full'] = $loadTest->runFullQueueTest();
            
            echo "2. Teste de pico de adição à fila...\n";
            $results['peak'] = $loadTest->runQueueAdditionPeakTest();
            
            echo "3. Teste de consulta em massa...\n";
            $results['query'] = $loadTest->runBulkQueryTest();
            break;
    }
    
    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;
    
    // Adicionar metadados aos resultados
    $results['metadata'] = [
        'test_type' => $testType,
        'start_time' => date('Y-m-d H:i:s', (int)$startTime),
        'end_time' => date('Y-m-d H:i:s', (int)$endTime),
        'total_time' => $totalTime,
        'php_version' => PHP_VERSION,
        'memory_peak' => memory_get_peak_usage(true)
    ];
    
    // Assegurar que o diretório de resultados existe
    $resultsDir = dirname($resultsFile);
    if (!is_dir($resultsDir)) {
        if (!mkdir($resultsDir, 0755, true)) {
            throw new \Exception("Não foi possível criar o diretório de resultados: $resultsDir");
        }
    }
    
    // Salvar resultados em arquivo
    $jsonResults = json_encode($results, JSON_PRETTY_PRINT);
    file_put_contents($resultsFile, $jsonResults);
    
    echo "\nTestes concluídos em " . number_format($totalTime, 2) . " segundos\n";
    echo "Resultados salvos em: $resultsFile\n";
    
    // Exibir resumo dos resultados
    echo "\nResumo dos resultados:\n";
    
    foreach ($results as $key => $result) {
        if ($key === 'metadata') continue;
        
        echo "- Teste '$key':\n";
        if (isset($result['analysis'])) {
            $analysis = $result['analysis'];
            echo "  * Total de requisições: " . $analysis['total_requests'] . "\n";
            echo "  * Taxa de erro: " . number_format($analysis['error_rate'], 2) . "%\n";
            echo "  * Tempo médio de resposta: " . number_format($analysis['average_response_time'], 2) . "ms\n";
            echo "  * Throughput: " . number_format($analysis['throughput'], 2) . " req/s\n";
            echo "  * Percentil 95: " . number_format($analysis['percentiles']['p95'], 2) . "ms\n";
        }
    }
    
    exit(0);
} catch (\Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}