<?php
/**
 * Script de execução de testes de carga
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Tests\Load
 * @version    1.0.0
 */

// Definir constante BASEPATH para acesso aos scripts
define('BASEPATH', true);

// Incluir o framework de testes
require_once __DIR__ . '/PrintQueueLoadTest.php';

// Verificar argumentos da linha de comando
$scale = isset($argv[1]) ? (int)$argv[1] : 5;
$scale = max(1, min(10, $scale)); // Garantir que escala está entre 1 e 10

// Iniciar teste
echo "Iniciando testes de carga com escala {$scale}...\n";
$startTime = microtime(true);

$tester = new PrintQueueLoadTest();
$results = $tester->runAllTests($scale);

// Calcular tempo total de execução
$endTime = microtime(true);
$executionTime = $endTime - $startTime;
echo "Testes concluídos em " . number_format($executionTime, 2) . " segundos.\n\n";

// Mostrar resumo básico
echo "=== RESUMO DOS TESTES ===\n";
echo "Operações por segundo (média): " . number_format($results['overall_performance']['average_operations_per_second'], 2) . "\n";
echo "Tempo médio de resposta: " . number_format($results['overall_performance']['average_response_time'] * 1000, 2) . " ms\n";
echo "Capacidade estimada: " . $results['overall_performance']['estimated_max_users'] . " usuários simultâneos\n\n";

// Mostrar gargalos identificados
if (!empty($results['overall_performance']['bottlenecks'])) {
    echo "Gargalos identificados:\n";
    foreach ($results['overall_performance']['bottlenecks'] as $bottleneck) {
        echo "- " . $bottleneck['operation'] . ": " . $bottleneck['suggestion'] . "\n";
    }
    echo "\n";
}

// Salvar resultados detalhados em arquivo
$timestamp = date('Y-m-d_H-i-s');
$outputFile = __DIR__ . "/results/load_test_results_{$timestamp}.json";

// Criar diretório de resultados se não existir
if (!is_dir(__DIR__ . "/results")) {
    mkdir(__DIR__ . "/results", 0755, true);
}

if ($tester->saveResults($results, $outputFile)) {
    echo "Resultados detalhados salvos em: {$outputFile}\n";
} else {
    echo "ERRO: Não foi possível salvar os resultados detalhados.\n";
}

// Adicionar informações ao log de testes
$logFile = __DIR__ . "/results/load_tests.log";
$logEntry = date('Y-m-d H:i:s') . " - Escala: {$scale} - OPS: " . 
            number_format($results['overall_performance']['average_operations_per_second'], 2) . 
            " - Tempo de resposta: " . number_format($results['overall_performance']['average_response_time'] * 1000, 2) . 
            " ms - Arquivo de resultados: " . basename($outputFile) . "\n";

file_put_contents($logFile, $logEntry, FILE_APPEND);

echo "\nTestes concluídos.\n";
