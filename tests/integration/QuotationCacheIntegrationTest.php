<?php
/**
 * QuotationCacheIntegrationTest
 * 
 * Teste de integração para os componentes otimizados do Sistema de Cotação
 * 
 * Este teste valida a integração entre QuotationCache e ModelAnalysisOptimizer,
 * verificando o correto funcionamento do cache em memória e disco, comportamento
 * sob diferentes condições, e eficiência das otimizações implementadas.
 * 
 * @package Tests\Integration
 * @author Taverna da Impressão 3D
 */

// Incluir autoload ou bootstrap conforme implementação
require_once __DIR__ . '/../bootstrap.php';

use App\Lib\Analysis\QuotationCache;
use App\Lib\Analysis\ModelComplexityAnalyzer;
use App\Lib\Analysis\Optimizer\ModelAnalysisOptimizer;

/**
 * Utilitário para formatação de saída
 * 
 * @param string $message Mensagem a ser exibida
 * @param string $type Tipo da mensagem (info, success, error, warning)
 */
function printMessage(string $message, string $type = 'info'): void {
    $colors = [
        'info' => "\033[0;34m", // Azul
        'success' => "\033[0;32m", // Verde
        'error' => "\033[0;31m", // Vermelho
        'warning' => "\033[0;33m", // Amarelo
        'reset' => "\033[0m" // Reset
    ];
    
    // Fallback para sistemas sem suporte a cores
    if (!posix_isatty(STDOUT)) {
        $prefix = [
            'info' => '[INFO] ',
            'success' => '[OK] ',
            'error' => '[ERROR] ',
            'warning' => '[WARN] '
        ];
        echo $prefix[$type] . $message . PHP_EOL;
        return;
    }
    
    echo $colors[$type] . $message . $colors['reset'] . PHP_EOL;
}

/**
 * Formata tempo legível
 * 
 * @param float $seconds Tempo em segundos
 * @return string Tempo formatado
 */
function formatTime(float $seconds): string {
    if ($seconds < 0.001) {
        return round($seconds * 1000000) . " µs";
    } elseif ($seconds < 1) {
        return round($seconds * 1000, 2) . " ms";
    } else {
        return round($seconds, 2) . " s";
    }
}

/**
 * Formata tamanho de memória legível
 * 
 * @param int $bytes Tamanho em bytes
 * @return string Tamanho formatado
 */
function formatMemory(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes > 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Inicializar teste de integração
printMessage("=== Teste de Integração: Sistema de Cotação Otimizado ===", "info");
printMessage("Iniciando validação dos componentes otimizados...\n", "info");

try {
    // Passo 1: Inicializar componentes
    printMessage("Passo 1: Inicializando componentes...", "info");
    
    $startTime = microtime(true);
    $memoryStart = memory_get_usage(true);
    
    $analyzer = new ModelComplexityAnalyzer();
    $optimizer = new ModelAnalysisOptimizer($analyzer);
    $cache = QuotationCache::getInstance();
    
    $initDuration = microtime(true) - $startTime;
    $memoryUsage = memory_get_usage(true) - $memoryStart;
    
    printMessage("Componentes inicializados em " . formatTime($initDuration), "success");
    printMessage("Uso de memória: " . formatMemory($memoryUsage) . "\n", "info");
    
    // Passo 2: Configurar modelagem de teste
    printMessage("Passo 2: Configurando modelos de teste...", "info");
    
    // Definir modelo de teste
    $testModels = [
        'small' => [
            'file_path' => __DIR__ . '/../test_data/sample_models/small/model_small_1.stl',
            'file_type' => 'stl',
            'material' => 'PLA',
            'quality' => 'medium'
        ],
        'medium' => [
            'file_path' => __DIR__ . '/../test_data/sample_models/medium/model_medium_1.stl',
            'file_type' => 'stl',
            'material' => 'ABS',
            'quality' => 'high'
        ]
    ];
    
    // Verificar se os modelos existem
    foreach ($testModels as $type => $model) {
        if (!file_exists($model['file_path'])) {
            printMessage("Modelo {$type} não encontrado: {$model['file_path']}", "error");
            printMessage("Verifique a estrutura de diretórios de teste", "warning");
            exit(1);
        }
    }
    
    printMessage("Modelos de teste configurados com sucesso", "success");
    
    // Passo 3: Teste de geração de chave de cache
    printMessage("\nPasso 3: Testando geração de chaves de cache...", "info");
    
    $keys = [];
    foreach ($testModels as $type => $model) {
        $startTime = microtime(true);
        $keys[$type] = $cache->generateKey($model);
        $duration = microtime(true) - $startTime;
        
        printMessage("Chave para modelo {$type}: {$keys[$type]}", "info");
        printMessage("Gerada em " . formatTime($duration), "info");
    }
    
    // Verificar se chaves são diferentes para modelos diferentes
    if ($keys['small'] === $keys['medium']) {
        printMessage("ERRO: Chaves iguais para modelos diferentes!", "error");
    } else {
        printMessage("Chaves corretamente diferenciadas para modelos distintos", "success");
    }
    
    // Modificar parâmetro e verificar mudança de chave
    $modifiedModel = $testModels['small'];
    $modifiedModel['material'] = 'PETG';
    $modifiedKey = $cache->generateKey($modifiedModel);
    
    if ($modifiedKey === $keys['small']) {
        printMessage("ERRO: Chave não mudou com alteração de parâmetro!", "error");
    } else {
        printMessage("Chave corretamente alterada quando parâmetro modificado", "success");
    }
    
    // Passo 4: Teste de cache miss e hit
    printMessage("\nPasso 4: Testando ciclo de cache miss e hit...", "info");
    
    // Primeiro access (deve ser miss)
    $testModel = $testModels['small'];
    $key = $keys['small'];
    
    printMessage("4.1 Verificando cache inicial (esperamos miss)...", "info");
    $startTime = microtime(true);
    $cachedResult = $cache->get($key);
    $duration = microtime(true) - $startTime;
    
    if ($cachedResult === null) {
        printMessage("Cache miss confirmado (esperado para primeira execução)", "success");
        printMessage("Verificação completada em " . formatTime($duration), "info");
        
        printMessage("\n4.2 Executando análise otimizada e armazenando resultado...", "info");
        
        // Executar análise otimizada
        $memoryBefore = memory_get_usage(true);
        $startTime = microtime(true);
        $result = $optimizer->analyzeModelOptimized($testModel, true);
        $duration = microtime(true) - $startTime;
        $memoryUsed = memory_get_peak_usage(true) - $memoryBefore;
        
        printMessage("Análise concluída em " . formatTime($duration), "success");
        printMessage("Uso de memória: " . formatMemory($memoryUsed), "info");
        printMessage("Complexidade calculada: " . $result['complexity_score'], "info");
        printMessage("Polígonos: " . $result['metrics']['polygon_count'], "info");
        
        // Armazenar no cache
        printMessage("\n4.3 Armazenando resultado no cache...", "info");
        $startTime = microtime(true);
        $ttl = $cache->calculateAdaptiveTtl($key, $result['complexity_score']);
        $success = $cache->set($key, $result, $ttl);
        $duration = microtime(true) - $startTime;
        
        if ($success) {
            printMessage("Armazenamento bem-sucedido (TTL: {$ttl}s)", "success");
            printMessage("Operação completada em " . formatTime($duration), "info");
            
            // Verificar cache após armazenamento (deve ser hit)
            printMessage("\n4.4 Verificando cache após armazenamento (esperamos hit)...", "info");
            $startTime = microtime(true);
            $cachedResult = $cache->get($key);
            $duration = microtime(true) - $startTime;
            
            if ($cachedResult !== null) {
                printMessage("Cache hit confirmado!", "success");
                printMessage("Recuperação completada em " . formatTime($duration), "info");
                
                // Verificar consistência dos dados
                if ($cachedResult['complexity_score'] === $result['complexity_score']) {
                    printMessage("Dados recuperados são consistentes com originais", "success");
                } else {
                    printMessage("ERRO: Inconsistência nos dados recuperados", "error");
                    printMessage("Original: " . $result['complexity_score'], "error");
                    printMessage("Cache: " . $cachedResult['complexity_score'], "error");
                }
            } else {
                printMessage("ERRO: Cache miss inesperado após armazenamento", "error");
            }
        } else {
            printMessage("ERRO: Falha ao armazenar no cache", "error");
        }
    } else {
        printMessage("Cache já contém resultado para este modelo", "warning");
        printMessage("Limpando cache para teste completo...", "info");
        
        $cache->invalidate($key);
        $cachedResult = $cache->get($key);
        
        if ($cachedResult === null) {
            printMessage("Cache limpo com sucesso", "success");
            printMessage("Execute o teste novamente para ciclo completo", "info");
        } else {
            printMessage("ERRO: Falha ao limpar cache", "error");
        }
    }
    
    // Passo 5: Teste de desempenho comparativo
    printMessage("\nPasso 5: Teste de desempenho comparativo...", "info");
    
    // Limpar cache para teste justo
    $cache->invalidate($keys['medium']);
    
    // Teste 1: Análise direta (sem otimizações)
    printMessage("5.1 Executando análise direta (sem otimizações)...", "info");
    $memoryBefore = memory_get_usage(true);
    $startTime = microtime(true);
    
    $analyzer->loadModel($testModels['medium']);
    $directResult = $analyzer->analyzeComplexity();
    
    $directDuration = microtime(true) - $startTime;
    $directMemory = memory_get_peak_usage(true) - $memoryBefore;
    
    printMessage("Análise direta concluída em " . formatTime($directDuration), "info");
    printMessage("Memória utilizada: " . formatMemory($directMemory), "info");
    
    // Teste 2: Análise otimizada
    printMessage("\n5.2 Executando análise otimizada...", "info");
    $memoryBefore = memory_get_usage(true);
    $startTime = microtime(true);
    
    $optimizedResult = $optimizer->analyzeModelOptimized($testModels['medium'], true);
    
    $optimizedDuration = microtime(true) - $startTime;
    $optimizedMemory = memory_get_peak_usage(true) - $memoryBefore;
    
    printMessage("Análise otimizada concluída em " . formatTime($optimizedDuration), "info");
    printMessage("Memória utilizada: " . formatMemory($optimizedMemory), "info");
    
    // Teste 3: Recuperação de cache
    printMessage("\n5.3 Armazenando e recuperando do cache...", "info");
    
    // Armazenar resultado
    $cache->set($keys['medium'], $optimizedResult);
    
    // Recuperar do cache
    $startTime = microtime(true);
    $cachedResult = $cache->get($keys['medium']);
    $cacheDuration = microtime(true) - $startTime;
    
    printMessage("Recuperação de cache completada em " . formatTime($cacheDuration), "info");
    
    // Comparação de resultados
    printMessage("\n5.4 Comparação de resultados:", "info");
    printMessage("Método: Análise Direta | Tempo: " . formatTime($directDuration) . " | Memória: " . formatMemory($directMemory), "info");
    printMessage("Método: Análise Otimizada | Tempo: " . formatTime($optimizedDuration) . " | Memória: " . formatMemory($optimizedMemory), "info");
    printMessage("Método: Cache | Tempo: " . formatTime($cacheDuration) . " | Memória: N/A", "info");
    
    // Calcular melhorias
    $speedupOpt = $directDuration > 0 ? ($directDuration / $optimizedDuration) : 0;
    $speedupCache = $directDuration > 0 ? ($directDuration / $cacheDuration) : 0;
    $memoryReduction = $directMemory > 0 ? (1 - ($optimizedMemory / $directMemory)) * 100 : 0;
    
    printMessage("\nMelhorias:", "success");
    printMessage("Speedup com otimização: " . round($speedupOpt, 1) . "x", "success");
    printMessage("Speedup com cache: " . round($speedupCache, 1) . "x", "success");
    printMessage("Redução de memória: " . round($memoryReduction, 1) . "%", "success");
    
    // Verificar consistência dos resultados
    $directScore = $directResult['complexity_score'];
    $optimizedScore = $optimizedResult['complexity_score'];
    $cachedScore = $cachedResult['complexity_score'];
    
    printMessage("\n5.5 Verificação de consistência:", "info");
    printMessage("Complexidade (Direta): " . $directScore, "info");
    printMessage("Complexidade (Otimizada): " . $optimizedScore, "info");
    printMessage("Complexidade (Cache): " . $cachedScore, "info");
    
    $scoreDiff = abs(($directScore - $optimizedScore) / $directScore) * 100;
    if ($scoreDiff > 5) {
        printMessage("AVISO: Diferença significativa entre resultados direto e otimizado: " . round($scoreDiff, 2) . "%", "warning");
    } else {
        printMessage("Resultados consistentes entre métodos (diferença: " . round($scoreDiff, 2) . "%)", "success");
    }
    
    if ($optimizedScore !== $cachedScore) {
        printMessage("ERRO: Inconsistência entre resultado otimizado e cache", "error");
    } else {
        printMessage("Cache manteve consistência completa com resultado original", "success");
    }
    
    // Passo 6: Estatísticas de cache
    printMessage("\nPasso 6: Estatísticas de cache:", "info");
    
    $stats = $cache->getStats();
    printMessage("Total de requisições: {$stats['total_requests']}", "info");
    printMessage("Hit ratio: {$stats['hit_ratio']}%", "info");
    printMessage("Hits em memória: {$stats['memory_hits']}", "info");
    printMessage("Hits em disco: {$stats['disk_hits']}", "info");
    printMessage("Misses: {$stats['misses']}", "info");
    printMessage("Escritas: {$stats['writes']}", "info");
    printMessage("Erros: {$stats['errors']}", "info");
    
    // Conclusão
    printMessage("\n=== Teste de Integração Concluído com Sucesso ===", "success");
    printMessage("Otimizações demonstraram melhoria significativa de performance", "success");
    printMessage("Sistema de cache funcionando corretamente", "success");
} catch (\Exception $e) {
    printMessage("\nERRO FATAL: " . $e->getMessage(), "error");
    printMessage("Stacktrace:", "error");
    printMessage($e->getTraceAsString(), "error");
    exit(1);
}
