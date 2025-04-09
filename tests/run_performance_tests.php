<?php
/**
 * Script Executor de Testes de Performance
 * 
 * Este script executa os testes de performance para o Sistema de Cotação Automatizada
 * da Taverna da Impressão 3D com configurações otimizadas para ambiente de produção.
 * 
 * Uso:
 *   php tests/run_performance_tests.php [--profile=baseline|full|critical|database]
 * 
 * @package App\Tests\Performance
 * @author Taverna da Impressão 3D Team
 * @version 1.0.0
 */

// Configurar limites de memória e tempo
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 900); // 15 minutos

// Carregar autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Importar classe de teste
require_once __DIR__ . '/performance/QuotationSystemPerformanceTest.php';

// Definir perfis de teste
$profiles = [
    'baseline' => [
        'description' => 'Testes básicos para validação rápida',
        'iterations' => 20,
        'concurrentUsers' => 5,
        'modelSizes' => ['small', 'medium'],
        'timeLimit' => 120,
        'memoryLimit' => 64 * 1024 * 1024,
    ],
    'full' => [
        'description' => 'Conjunto completo de testes para validação abrangente',
        'iterations' => 100,
        'concurrentUsers' => 10,
        'modelSizes' => ['small', 'medium', 'large', 'complex'],
        'timeLimit' => 600,
        'memoryLimit' => 256 * 1024 * 1024,
    ],
    'critical' => [
        'description' => 'Foco em áreas críticas e testes de concorrência',
        'iterations' => 50,
        'concurrentUsers' => 20,
        'modelSizes' => ['medium', 'large'],
        'timeLimit' => 300,
        'memoryLimit' => 128 * 1024 * 1024,
    ],
    'database' => [
        'description' => 'Foco em interações com o banco de dados',
        'iterations' => 30,
        'concurrentUsers' => 10,
        'modelSizes' => ['medium'],
        'timeLimit' => 180,
        'memoryLimit' => 64 * 1024 * 1024,
        'focusDatabase' => true,
    ],
];

// Processar argumentos da linha de comando
$options = getopt('', ['profile::', 'help', 'list-profiles', 'verbose']);

// Exibir ajuda se solicitado
if (isset($options['help'])) {
    echo "Executor de Testes de Performance - Taverna da Impressão 3D\n";
    echo "Uso: php tests/run_performance_tests.php [--profile=baseline|full|critical|database] [--verbose] [--list-profiles]\n";
    echo "\nOpções disponíveis:\n";
    echo "  --profile=NOME    Define o perfil de teste a ser executado (padrão: baseline)\n";
    echo "  --verbose         Exibe informações detalhadas durante a execução\n";
    echo "  --list-profiles   Lista os perfis de teste disponíveis\n";
    echo "  --help            Exibe esta mensagem de ajuda\n";
    exit(0);
}

// Listar perfis disponíveis
if (isset($options['list-profiles'])) {
    echo "Perfis de teste disponíveis:\n";
    foreach ($profiles as $name => $config) {
        echo "  - $name: {$config['description']}\n";
    }
    exit(0);
}

// Definir perfil padrão se não especificado
$profileName = $options['profile'] ?? 'baseline';

// Verificar se o perfil existe
if (!isset($profiles[$profileName])) {
    echo "Erro: Perfil '$profileName' não encontrado. Use --list-profiles para ver os perfis disponíveis.\n";
    exit(1);
}

// Verificar se o modo verboso está ativado
$verbose = isset($options['verbose']);

// Exibir informações iniciais
echo "Iniciando testes de performance com perfil: $profileName\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n";

if ($verbose) {
    echo "\nConfiguração do perfil '$profileName':\n";
    print_r($profiles[$profileName]);
    echo "\n";
}

// Configurar teste
$config = $profiles[$profileName];
$start = microtime(true);

try {
    echo "Inicializando ambiente de teste...\n";
    
    // Criar diretórios necessários se não existirem
    $directories = [
        __DIR__ . '/../logs/performance/',
        __DIR__ . '/test_data/sample_models/small',
        __DIR__ . '/test_data/sample_models/medium',
        __DIR__ . '/test_data/sample_models/large',
        __DIR__ . '/test_data/sample_models/complex',
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new \Exception("Não foi possível criar o diretório: $dir");
            }
            echo "Diretório criado: $dir\n";
        }
    }
    
    // Verificar existência de arquivos de teste
    $modelSizes = $config['modelSizes'];
    $missingModels = [];
    
    foreach ($modelSizes as $size) {
        $path = __DIR__ . "/test_data/sample_models/$size";
        $files = glob("$path/model_{$size}_*.stl");
        
        if (empty($files)) {
            $missingModels[] = $size;
        }
    }
    
    if (!empty($missingModels)) {
        echo "\nAtenção: Modelos de teste não encontrados para os seguintes tamanhos: " . 
             implode(', ', $missingModels) . "\n";
        echo "Será utilizada a geração de modelos fictícios para estes tamanhos.\n";
    }
    
    // Iniciar testes
    echo "\nIniciando execução dos testes...\n";
    
    // Criar instância de teste com o perfil selecionado
    $tester = new \App\Tests\Performance\QuotationSystemPerformanceTest($config);
    
    // Executar testes completos ou apenas banco de dados
    if (isset($config['focusDatabase']) && $config['focusDatabase']) {
        echo "Executando testes focados em banco de dados...\n";
        $results = $tester->testDatabaseInteraction();
        
        echo "\nResultados do teste de banco de dados:\n";
        if ($verbose) {
            print_r($results);
        } else {
            echo "INSERT: " . number_format($results['inserts']['averageTime'] * 1000, 2) . " ms (média)\n";
            echo "SELECT: " . number_format($results['selects']['averageTime'] * 1000, 2) . " ms (média)\n";
            echo "UPDATE: " . number_format($results['updates']['averageTime'] * 1000, 2) . " ms (média)\n";
            echo "DELETE: " . number_format($results['deletes']['averageTime'] * 1000, 2) . " ms (média)\n";
        }
    } else {
        echo "Executando conjunto completo de testes...\n";
        $results = $tester->runAllTests();
        
        // Exibir resumo dos resultados
        if (isset($results['summary'])) {
            echo "\nResumo dos resultados:\n";
            
            // Exibir gargalos identificados
            if (!empty($results['summary']['bottlenecks'])) {
                echo "\nGargalos identificados:\n";
                foreach ($results['summary']['bottlenecks'] as $i => $bottleneck) {
                    echo ($i + 1) . ". {$bottleneck['component']}: {$bottleneck['issue']} ({$bottleneck['impact']})\n";
                }
            } else {
                echo "Nenhum gargalo crítico identificado.\n";
            }
            
            // Exibir recomendações
            if (!empty($results['summary']['recommendations'])) {
                echo "\nRecomendações:\n";
                foreach ($results['summary']['recommendations'] as $i => $rec) {
                    echo ($i + 1) . ". {$rec}\n";
                }
            }
        }
    }
    
    // Informações finais
    $duration = microtime(true) - $start;
    echo "\nTestes concluídos em " . number_format($duration, 2) . " segundos.\n";
    
    $resultPath = __DIR__ . '/../logs/performance/';
    $files = glob($resultPath . 'quotation_perf_*.json');
    if (!empty($files)) {
        $latestFile = end($files);
        echo "Resultados detalhados disponíveis em: $latestFile\n";
    }
    
    exit(0);
    
} catch (\Exception $e) {
    echo "\nErro durante a execução dos testes: " . $e->getMessage() . "\n";
    echo "Linha: " . $e->getLine() . " em " . $e->getFile() . "\n";
    
    if ($verbose) {
        echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
    exit(1);
}
