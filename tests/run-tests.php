<?php
/**
 * Executor de testes para Taverna da Impressão 3D
 * 
 * Este script executa todos os testes unitários do projeto.
 */

// Configurar ambiente de teste
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Registrar o tempo de início
$startTime = microtime(true);

// Banner
echo "====================================\n";
echo "Testes - Taverna da Impressão 3D\n";
echo "====================================\n\n";

// Carregar arquivos de teste
$testFiles = [];
$testFiles[] = __DIR__ . '/SecurityManagerTest.php';
$testFiles[] = __DIR__ . '/UserValidationTest.php';

// Testes do módulo de relatórios otimizado
$testFiles[] = __DIR__ . '/reports/OptimizedReportModelTest.php';
$testFiles[] = __DIR__ . '/reports/AdvancedReportCacheTest.php';
$testFiles[] = __DIR__ . '/reports/AdaptiveCacheManagerTest.php';
$testFiles[] = __DIR__ . '/reports/ReportPerformanceTest.php';

// Executar cada arquivo de teste
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

foreach ($testFiles as $testFile) {
    if (file_exists($testFile)) {
        echo "Executando " . basename($testFile) . "...\n\n";
        include_once $testFile;
        
        // Extrair o nome da classe do teste a partir do nome do arquivo
        $className = pathinfo($testFile, PATHINFO_FILENAME);
        
        if (class_exists($className)) {
            $tester = new $className();
            
            // Verificar se o método runAllTests existe
            if (method_exists($tester, 'runAllTests')) {
                $result = $tester->runAllTests();
                
                // Incrementar contadores
                if ($result === true) {
                    $passedTests++;
                } else {
                    $failedTests++;
                }
                $totalTests++;
            } else {
                echo "ERRO: Método runAllTests não encontrado em $className\n";
                $failedTests++;
                $totalTests++;
            }
        } else {
            echo "ERRO: Classe $className não encontrada em $testFile\n";
            $failedTests++;
            $totalTests++;
        }
    } else {
        echo "ERRO: Arquivo de teste não encontrado: $testFile\n";
        $failedTests++;
        $totalTests++;
    }
    
    echo "\n------------------------------------\n\n";
}

// Calcular tempo de execução
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

// Exibir resumo
echo "Resumo dos Testes:\n";
echo "- Total de arquivos de teste: $totalTests\n";
echo "- Testes passados: $passedTests\n";
echo "- Testes falhos: $failedTests\n";
echo "- Tempo de execução: {$executionTime}s\n\n";

if ($failedTests > 0) {
    echo "ATENÇÃO: Nem todos os testes passaram. Verifique os erros acima.\n";
    exit(1);
} else {
    echo "SUCESSO: Todos os testes passaram!\n";
    exit(0);
}