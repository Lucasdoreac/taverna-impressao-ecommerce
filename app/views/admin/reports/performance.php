<?php 
/**
 * View para o dashboard de performance de relatórios
 * 
 * @var array $metrics Métricas de performance do repositório
 * @var array $cacheStats Estatísticas detalhadas de cache
 * @var array|null $benchmarkResults Resultados de benchmark comparativo (opcional)
 */

// Garantir que variáveis existam
$metrics = $metrics ?? [];
$cacheStats = $cacheStats ?? [];
$benchmarkResults = $benchmarkResults ?? null;
$csrfToken = $csrfToken ?? '';
$title = $title ?? 'Dashboard de Performance de Relatórios';

// Sanitização para saída
$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
?>

<!-- Cabeçalho da página -->
<div class="container-fluid px-4">
    <h1 class="mt-4"><?= $title ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/reports">Relatórios</a></li>
        <li class="breadcrumb-item active">Performance</li>
    </ol>
    
    <!-- Alertas -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_message'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Resumo de Performance -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Taxa de Acerto de Cache</h5>
                    <h2 class="text-center">
                        <?= isset($metrics['summary']['cache_hit_rate_percentage']) ? 
                            number_format($metrics['summary']['cache_hit_rate_percentage'], 1) . '%' : 
                            'N/A' ?>
                    </h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <span>Total de execuções: <?= $metrics['summary']['total_executions'] ?? 0 ?></span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Tempo Médio de Resposta</h5>
                    <h2 class="text-center">
                        <?= isset($metrics['summary']['average_time_ms']) ? 
                            number_format($metrics['summary']['average_time_ms'], 2) . ' ms' : 
                            'N/A' ?>
                    </h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <span>Tempo total: <?= isset($metrics['summary']['total_time_ms']) ? 
                        number_format($metrics['summary']['total_time_ms'], 2) . ' ms' : 'N/A' ?></span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Itens em Cache</h5>
                    <h2 class="text-center">
                        <?= isset($cacheStats['items']) ? 
                            number_format($cacheStats['items']) : 
                            'N/A' ?>
                    </h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <span>Tamanho total: <?= isset($cacheStats['size_bytes']) ? 
                        number_format($cacheStats['size_bytes'] / 1024, 2) . ' KB' : 'N/A' ?></span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Uso de Memória Médio</h5>
                    <h2 class="text-center">
                        <?= isset($metrics['summary']['total_memory_usage_bytes']) ? 
                            number_format($metrics['summary']['total_memory_usage_bytes'] / 1024, 2) . ' KB' : 
                            'N/A' ?>
                    </h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <span>Por relatório: <?= isset($metrics['summary']['total_memory_usage_bytes'], $metrics['summary']['total_executions']) && $metrics['summary']['total_executions'] > 0 ? 
                        number_format(($metrics['summary']['total_memory_usage_bytes'] / $metrics['summary']['total_executions']) / 1024, 2) . ' KB' : 'N/A' ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Relatórios mais lentos e mais rápidos -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i>
                    Relatórios Mais Lentos
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Relatório</th>
                                <th>Tempo (ms)</th>
                                <th>Memória (KB)</th>
                                <th>Cache</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (isset($metrics['summary']['slowest_reports']) && is_array($metrics['summary']['slowest_reports'])):
                                foreach ($metrics['summary']['slowest_reports'] as $reportType => $data): 
                                    // Extrair o nome do método a partir da chave completa
                                    $methodName = substr($reportType, strrpos($reportType, '::') + 2);
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($methodName, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= number_format($data['execution_time_ms'], 2) ?></td>
                                    <td><?= isset($data['memory_usage_bytes']) ? 
                                        number_format($data['memory_usage_bytes'] / 1024, 2) : 'N/A' ?></td>
                                    <td>
                                        <?php if (isset($data['cache_used'])): ?>
                                            <span class="badge bg-<?= $data['cache_used'] ? 'success' : 'danger' ?>">
                                                <?= $data['cache_used'] ? 'Sim' : 'Não' ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php 
                                endforeach;
                            else: 
                            ?>
                                <tr>
                                    <td colspan="4" class="text-center">Nenhum dado disponível</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i>
                    Relatórios Mais Rápidos
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Relatório</th>
                                <th>Tempo (ms)</th>
                                <th>Memória (KB)</th>
                                <th>Cache</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (isset($metrics['summary']['fastest_reports']) && is_array($metrics['summary']['fastest_reports'])):
                                foreach ($metrics['summary']['fastest_reports'] as $reportType => $data): 
                                    // Extrair o nome do método a partir da chave completa
                                    $methodName = substr($reportType, strrpos($reportType, '::') + 2);
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($methodName, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= number_format($data['execution_time_ms'], 2) ?></td>
                                    <td><?= isset($data['memory_usage_bytes']) ? 
                                        number_format($data['memory_usage_bytes'] / 1024, 2) : 'N/A' ?></td>
                                    <td>
                                        <?php if (isset($data['cache_used'])): ?>
                                            <span class="badge bg-<?= $data['cache_used'] ? 'success' : 'danger' ?>">
                                                <?= $data['cache_used'] ? 'Sim' : 'Não' ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php 
                                endforeach;
                            else: 
                            ?>
                                <tr>
                                    <td colspan="4" class="text-center">Nenhum dado disponível</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Estatísticas de Cache -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-database me-1"></i>
                    Estatísticas Detalhadas de Cache
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Cache em Memória</h5>
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <td>Itens em Memória:</td>
                                                <td><?= $cacheStats['memory_cache']['items'] ?? 'N/A' ?></td>
                                            </tr>
                                            <tr>
                                                <td>Limite Configurado:</td>
                                                <td><?= $cacheStats['memory_cache']['limit'] ?? 'N/A' ?></td>
                                            </tr>
                                            <tr>
                                                <td>Ocupação:</td>
                                                <td>
                                                    <?php if (isset($cacheStats['memory_cache']['items'], $cacheStats['memory_cache']['limit']) && $cacheStats['memory_cache']['limit'] > 0): ?>
                                                        <?= number_format(($cacheStats['memory_cache']['items'] / $cacheStats['memory_cache']['limit']) * 100, 1) ?>%
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Compressão de Dados</h5>
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <td>Compressão Ativada:</td>
                                                <td>
                                                    <?php if (isset($cacheStats['compression']['enabled'])): ?>
                                                        <span class="badge bg-<?= $cacheStats['compression']['enabled'] ? 'success' : 'danger' ?>">
                                                            <?= $cacheStats['compression']['enabled'] ? 'Sim' : 'Não' ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Nível de Compressão:</td>
                                                <td><?= $cacheStats['compression']['level'] ?? 'N/A' ?></td>
                                            </tr>
                                            <tr>
                                                <td>Taxa Média de Compressão:</td>
                                                <td><?= isset($cacheStats['compression_ratio']) ? 
                                                    number_format($cacheStats['compression_ratio'], 1) . 'x' : 'N/A' ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Expiração Adaptativa</h5>
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <td>Modo Adaptativo:</td>
                                                <td>
                                                    <?php if (isset($cacheStats['adaptive_expiration']['enabled'])): ?>
                                                        <span class="badge bg-<?= $cacheStats['adaptive_expiration']['enabled'] ? 'success' : 'danger' ?>">
                                                            <?= $cacheStats['adaptive_expiration']['enabled'] ? 'Ativo' : 'Inativo' ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Tempo Médio de Expiração:</td>
                                                <td><?= isset($cacheStats['avg_expiration_time']) ? 
                                                    number_format($cacheStats['avg_expiration_time'] / 60, 1) . ' min' : 'N/A' ?></td>
                                            </tr>
                                            <tr>
                                                <td>Taxa de Expiração:</td>
                                                <td><?= isset($cacheStats['expired_items'], $cacheStats['items']) && $cacheStats['items'] > 0 ? 
                                                    number_format(($cacheStats['expired_items'] / $cacheStats['items']) * 100, 1) . '%' : 'N/A' ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Relatórios em Cache -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Relatórios Frequentes</h5>
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Relatório</th>
                                        <th>Hits</th>
                                        <th>Último Acesso</th>
                                        <th>Próxima Expiração</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (isset($cacheStats['hit_counts']) && !empty($cacheStats['hit_counts'])): 
                                        // Ordenar por número de hits (decrescente)
                                        arsort($cacheStats['hit_counts']);
                                        $top10 = array_slice($cacheStats['hit_counts'], 0, 10, true);
                                        
                                        foreach ($top10 as $key => $hits): 
                                            // Extrair informação do tipo de relatório a partir da chave de cache
                                            $reportType = preg_match('/^([a-z_]+)_/', $key, $matches) ? $matches[1] : 'desconhecido';
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($reportType, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= number_format($hits) ?></td>
                                            <td>
                                                <?php if (isset($cacheStats['cache_access_timestamps'][$key]) && !empty($cacheStats['cache_access_timestamps'][$key])): 
                                                    $lastAccess = max($cacheStats['cache_access_timestamps'][$key]);
                                                    $lastAccessTime = date('d/m/Y H:i:s', $lastAccess);
                                                    $timeSince = time() - $lastAccess;
                                                    
                                                    if ($timeSince < 60) {
                                                        $timeAgo = 'agora mesmo';
                                                    } elseif ($timeSince < 3600) {
                                                        $timeAgo = floor($timeSince / 60) . ' min atrás';
                                                    } elseif ($timeSince < 86400) {
                                                        $timeAgo = floor($timeSince / 3600) . ' h atrás';
                                                    } else {
                                                        $timeAgo = floor($timeSince / 86400) . ' dias atrás';
                                                    }
                                                ?>
                                                    <span title="<?= $lastAccessTime ?>"><?= $timeAgo ?></span>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($cacheStats['expiration_times'][$key])): 
                                                    $expiresIn = $cacheStats['expiration_times'][$key] - time();
                                                    
                                                    if ($expiresIn <= 0) {
                                                        echo '<span class="text-danger">Expirado</span>';
                                                    } elseif ($expiresIn < 60) {
                                                        echo '<span class="text-warning">' . $expiresIn . ' seg</span>';
                                                    } elseif ($expiresIn < 3600) {
                                                        echo round($expiresIn / 60, 0) . ' min';
                                                    } elseif ($expiresIn < 86400) {
                                                        echo round($expiresIn / 3600, 1) . ' h';
                                                    } else {
                                                        echo round($expiresIn / 86400, 1) . ' dias';
                                                    }
                                                else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php 
                                        endforeach;
                                    else: 
                                    ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Nenhum dado de cache disponível</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Benchmark Comparativo -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-tachometer-alt me-1"></i>
                    Comparativo de Performance: Otimizado vs. Legacy
                </div>
                <div class="card-body">
                    <?php if ($benchmarkResults): ?>
                        <!-- Resultados do Benchmark -->
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5>Resumo do Benchmark</h5>
                                <div class="alert alert-info">
                                    Benchmark realizado em <?= isset($benchmarkResults['summary']['timestamp']) ? 
                                        htmlspecialchars($benchmarkResults['summary']['timestamp'], ENT_QUOTES, 'UTF-8') : 
                                        date('Y-m-d H:i:s') ?>
                                    <?= isset($benchmarkResults['summary']['test_count']) ? 
                                        ' com ' . $benchmarkResults['summary']['test_count'] . ' testes comparativos.' : 
                                        '.' ?>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">Melhoria de Tempo</h5>
                                                <h2 class="text-success">
                                                    <?= isset($benchmarkResults['summary']['time_improvement_percentage']) ? 
                                                        number_format($benchmarkResults['summary']['time_improvement_percentage'], 1) . '%' : 
                                                        'N/A' ?>
                                                </h2>
                                                <p class="card-text">
                                                    <?php if (isset($benchmarkResults['summary']['optimized_avg_time_ms'], $benchmarkResults['summary']['legacy_avg_time_ms'])): ?>
                                                        Em média: <?= number_format($benchmarkResults['summary']['optimized_avg_time_ms'], 2) ?> ms vs 
                                                        <?= number_format($benchmarkResults['summary']['legacy_avg_time_ms'], 2) ?> ms
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-body text-center">
                                                <h5 class="card-title">Melhoria de Memória</h5>
                                                <h2 class="text-success">
                                                    <?= isset($benchmarkResults['summary']['memory_improvement_percentage']) ? 
                                                        number_format($benchmarkResults['summary']['memory_improvement_percentage'], 1) . '%' : 
                                                        'N/A' ?>
                                                </h2>
                                                <p class="card-text">
                                                    <?php if (isset($benchmarkResults['summary']['optimized_avg_memory_kb'], $benchmarkResults['summary']['legacy_avg_memory_kb'])): ?>
                                                        Em média: <?= number_format($benchmarkResults['summary']['optimized_avg_memory_kb'], 2) ?> KB vs 
                                                        <?= number_format($benchmarkResults['summary']['legacy_avg_memory_kb'], 2) ?> KB
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabela comparativa detalhada -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5>Resultados Detalhados</h5>
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th rowspan="2">Método</th>
                                            <th colspan="2" class="text-center">Tempo de Execução (ms)</th>
                                            <th colspan="2" class="text-center">Uso de Memória (KB)</th>
                                            <th colspan="2" class="text-center">Cache</th>
                                        </tr>
                                        <tr>
                                            <th class="text-center">Otimizado</th>
                                            <th class="text-center">Legacy</th>
                                            <th class="text-center">Otimizado</th>
                                            <th class="text-center">Legacy</th>
                                            <th class="text-center">Otimizado</th>
                                            <th class="text-center">Legacy</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (isset($benchmarkResults['tests']) && !empty($benchmarkResults['tests'])): 
                                            foreach ($benchmarkResults['tests'] as $method => $results): 
                                                $hasOpt = isset($results['optimized']);
                                                $hasLeg = isset($results['legacy']);
                                                
                                                if ($hasOpt && $hasLeg) {
                                                    $timeImprovement = (($results['legacy']['execution_time_ms'] - $results['optimized']['execution_time_ms']) / $results['legacy']['execution_time_ms']) * 100;
                                                    $memoryImprovement = (($results['legacy']['memory_usage_kb'] - $results['optimized']['memory_usage_kb']) / $results['legacy']['memory_usage_kb']) * 100;
                                                } else {
                                                    $timeImprovement = null;
                                                    $memoryImprovement = null;
                                                }
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($method, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-center">
                                                    <?= $hasOpt ? number_format($results['optimized']['execution_time_ms'], 2) : 'N/A' ?>
                                                    <?php if ($timeImprovement !== null && $timeImprovement > 0): ?>
                                                        <span class="badge bg-success">-<?= number_format($timeImprovement, 1) ?>%</span>
                                                    <?php elseif ($timeImprovement !== null): ?>
                                                        <span class="badge bg-danger"><?= number_format($timeImprovement, 1) ?>%</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center"><?= $hasLeg ? number_format($results['legacy']['execution_time_ms'], 2) : 'N/A' ?></td>
                                                <td class="text-center">
                                                    <?= $hasOpt ? number_format($results['optimized']['memory_usage_kb'], 2) : 'N/A' ?>
                                                    <?php if ($memoryImprovement !== null && $memoryImprovement > 0): ?>
                                                        <span class="badge bg-success">-<?= number_format($memoryImprovement, 1) ?>%</span>
                                                    <?php elseif ($memoryImprovement !== null): ?>
                                                        <span class="badge bg-danger"><?= number_format($memoryImprovement, 1) ?>%</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center"><?= $hasLeg ? number_format($results['legacy']['memory_usage_kb'], 2) : 'N/A' ?></td>
                                                <td class="text-center">
                                                    <?php if ($hasOpt): ?>
                                                        <span class="badge bg-<?= $results['optimized']['cache_used'] ? 'success' : 'danger' ?>">
                                                            <?= $results['optimized']['cache_used'] ? 'Sim' : 'Não' ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($hasLeg): ?>
                                                        <span class="badge bg-<?= $results['legacy']['cache_used'] ? 'success' : 'danger' ?>">
                                                            <?= $results['legacy']['cache_used'] ? 'Sim' : 'Não' ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php 
                                            endforeach;
                                        else: 
                                        ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Nenhum dado de benchmark disponível</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Formulário para executar benchmark -->
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="alert alert-info">
                                    <p>O benchmark comparativo permite avaliar a diferença de performance entre o sistema de relatórios otimizado e a implementação legacy.</p>
                                    <p>Ele executará um conjunto de testes idênticos em ambas as implementações e comparará:</p>
                                    <ul>
                                        <li>Tempo de execução</li>
                                        <li>Uso de memória</li>
                                        <li>Eficiência de cache</li>
                                    </ul>
                                    <p><strong>Nota:</strong> Este processo pode levar alguns segundos para ser concluído.</p>
                                </div>
                                
                                <form action="<?= BASE_URL ?>admin/reports/performance" method="post" class="text-center mt-4">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="run_benchmark" value="1">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-tachometer-alt me-2"></i>
                                        Executar Benchmark Comparativo
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ações de Cache -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-tools me-1"></i>
                    Manutenção de Cache
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Limpar Cache Expirado</h5>
                                    <p class="card-text">Remove apenas os itens de cache que já expiraram, mantendo os válidos.</p>
                                    <form action="<?= BASE_URL ?>admin/reports/manageCache" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="action" value="clear_expired">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-broom me-1"></i>
                                            Limpar Expirados
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Limpar Todo o Cache</h5>
                                    <p class="card-text">Remove todos os itens de cache, forçando a regeneração de todos os relatórios.</p>
                                    <form action="<?= BASE_URL ?>admin/reports/manageCache" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="action" value="clear_all">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-trash-alt me-1"></i>
                                            Limpar Tudo
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Invalidar por Tipo</h5>
                                    <p class="card-text">Remove apenas o cache para um tipo específico de relatório.</p>
                                    <form action="<?= BASE_URL ?>admin/reports/manageCache" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="action" value="clear_type">
                                        <div class="input-group mb-3">
                                            <select name="report_type" class="form-select" required>
                                                <option value="">Selecione um tipo</option>
                                                <option value="sales">Vendas</option>
                                                <option value="products">Produtos</option>
                                                <option value="customers">Clientes</option>
                                                <option value="trends">Tendências</option>
                                                <option value="printing">Impressão 3D</option>
                                            </select>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-times me-1"></i>
                                                Invalidar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para gráficos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Código JavaScript para gráficos e visualizações será adicionado aqui
    console.log('Dashboard de Performance inicializado');
});
</script>
