<?php
/**
 * Script para testes de performance de otimizações SQL
 * 
 * Este script executa testes de performance para avaliar o impacto das otimizações
 * implementadas nos modelos ProductModel e CategoryModel. Gera um relatório detalhado
 * com métricas de desempenho e recomendações para melhorias adicionais.
 */

// Definir constantes essenciais
define('APP_PATH', dirname(__DIR__) . '/app');
define('DATA_PATH', dirname(__DIR__) . '/data');
define('MODEL_PATH', APP_PATH . '/models');
define('CONTROLLER_PATH', APP_PATH . '/controllers');
define('VIEW_PATH', APP_PATH . '/views');
define('HELPER_PATH', APP_PATH . '/helpers');
define('CONFIG_PATH', APP_PATH . '/config');

// Carregar configurações
require_once CONFIG_PATH . '/config.php';

// Carregar classes essenciais
require_once MODEL_PATH . '/BaseModel.php';
require_once MODEL_PATH . '/ProductModel.php';
require_once MODEL_PATH . '/CategoryModel.php';
require_once HELPER_PATH . '/DatabaseHelper.php';
require_once HELPER_PATH . '/SQLOptimizationHelper.php';
require_once HELPER_PATH . '/QueryOptimizerHelper.php';
require_once HELPER_PATH . '/SQLPerformanceTestHelper.php';

// Criar instância do helper de teste de performance
$performanceTest = new SQLPerformanceTestHelper();

// Definir número de iterações para resultados mais confiáveis
$performanceTest->setIterations(20);

echo "==========================================================\n";
echo "TESTE DE PERFORMANCE DE OTIMIZAÇÕES SQL\n";
echo "==========================================================\n";
echo "Data e hora: " . date('Y-m-d H:i:s') . "\n";
echo "Iterações por teste: 20\n";
echo "==========================================================\n\n";

// Testar ProductModel
echo "Testando ProductModel...\n";
$productResults = $performanceTest->testProductModelPerformance();

// Exibir resultados para ProductModel
echo "\nResultados para ProductModel:\n";
echo "----------------------------------------------------------\n";
foreach ($productResults as $method => $result) {
    echo "Método: " . $method . "\n";
    echo "  Tempo médio: " . number_format($result['avg_time'] * 1000, 2) . " ms\n";
    echo "  Consultas SQL: " . $result['query_count'] . "\n";
    echo "  Resultados retornados: " . $result['result_count'] . "\n";
    echo "----------------------------------------------------------\n";
}

// Testar CategoryModel
echo "\nTestando CategoryModel...\n";
$categoryResults = $performanceTest->testCategoryModelPerformance();

// Exibir resultados para CategoryModel
echo "\nResultados para CategoryModel:\n";
echo "----------------------------------------------------------\n";
foreach ($categoryResults as $method => $result) {
    echo "Método: " . $method . "\n";
    echo "  Tempo médio: " . number_format($result['avg_time'] * 1000, 2) . " ms\n";
    echo "  Consultas SQL: " . $result['query_count'] . "\n";
    echo "  Resultados retornados: " . $result['result_count'] . "\n";
    echo "----------------------------------------------------------\n";
}

// Salvar resultados em JSON para uso futuro
$testResults = [
    'timestamp' => date('Y-m-d H:i:s'),
    'ProductModel' => $productResults,
    'CategoryModel' => $categoryResults
];

// Criar diretório de resultados se não existir
if (!file_exists(DATA_PATH . '/performance_tests')) {
    mkdir(DATA_PATH . '/performance_tests', 0755, true);
}

// Salvar resultados
$testId = time();
$filePath = DATA_PATH . '/performance_tests/test_' . $testId . '.json';
file_put_contents($filePath, json_encode($testResults, JSON_PRETTY_PRINT));

echo "\nTestes concluídos. Resultados salvos em: " . $filePath . "\n";

// Gerar relatório para referência
echo "\nGerando relatório HTML...\n";
$reportHtml = $performanceTest->generatePerformanceReport([
    'ProductModel' => $productResults,
    'CategoryModel' => $categoryResults
]);

// Salvar relatório HTML
$reportPath = DATA_PATH . '/performance_tests/report_' . $testId . '.html';
file_put_contents($reportPath, $reportHtml);

echo "Relatório HTML salvo em: " . $reportPath . "\n";
echo "==========================================================\n";

// Análise de resultados e recomendações
echo "ANÁLISE DE RESULTADOS:\n";
echo "==========================================================\n";

// Identificar métodos mais lentos
$slowMethods = [];
foreach (['ProductModel' => $productResults, 'CategoryModel' => $categoryResults] as $modelName => $modelResults) {
    foreach ($modelResults as $methodName => $methodResult) {
        if ($methodResult['avg_time'] > 0.1) { // Mais de 100ms
            $slowMethods[] = "$modelName::$methodName (" . number_format($methodResult['avg_time'] * 1000, 2) . " ms)";
        }
    }
}

if (!empty($slowMethods)) {
    echo "Métodos que ainda podem precisar de otimização adicional:\n";
    foreach ($slowMethods as $method) {
        echo "- $method\n";
    }
} else {
    echo "Todos os métodos testados apresentam boa performance (menos de 100ms).\n";
}

// Identificar métodos com muitas consultas SQL
$highQueryMethods = [];
foreach (['ProductModel' => $productResults, 'CategoryModel' => $categoryResults] as $modelName => $modelResults) {
    foreach ($modelResults as $methodName => $methodResult) {
        if ($methodResult['query_count'] > 2) { // Mais de 2 consultas
            $highQueryMethods[] = "$modelName::$methodName ({$methodResult['query_count']} consultas)";
        }
    }
}

if (!empty($highQueryMethods)) {
    echo "\nMétodos que executam mais de 2 consultas SQL:\n";
    foreach ($highQueryMethods as $method) {
        echo "- $method\n";
    }
} else {
    echo "\nTodos os métodos testados executam no máximo 2 consultas SQL.\n";
}

echo "\nRECOMENDAÇÕES:\n";
echo "- Implementar sistema de cache para métodos frequentemente utilizados\n";
echo "- Considerar a criação de índices compostos para consultas complexas\n";
echo "- Avaliar a implementação de consultas preparadas para uso repetitivo\n";
echo "- Revisar estratégias de paginação para grandes conjuntos de dados\n";
echo "- Implementar monitoramento contínuo de performance em ambiente de produção\n";

echo "\n==========================================================\n";
echo "FIM DO TESTE\n";
echo "==========================================================\n";
