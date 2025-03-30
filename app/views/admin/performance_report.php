<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<!-- Page Title -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Relatório de Teste de Performance</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= BASE_URL ?>admin/performance_test" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
        <button type="button" class="btn btn-sm btn-primary" id="exportReportBtn">
            <i class="bi bi-download"></i> Exportar Relatório
        </button>
    </div>
</div>

<?php
// Verificar se o teste existe
if (empty($test)) {
    echo '<div class="alert alert-danger">Teste não encontrado</div>';
    require_once VIEWS_PATH . '/admin/partials/footer.php';
    exit;
}

// Decodificar dados
$results = isset($test['results']) ? json_decode($test['results'], true) : [];
$params = isset($test['params']) ? json_decode($test['params'], true) : [];
$summary = isset($results['summary']) ? $results['summary'] : [];

// Identificar tipo de teste
$testType = $test['test_type'];

// Verificar se PerformanceHelper está disponível
$helperAvailable = class_exists('PerformanceHelper');

// Funções de formatação (fallback se o helper não estiver disponível)
if (!$helperAvailable) {
    function formatTime($ms, $showUnit = true) {
        if ($ms === null || $ms === '') return 'N/A';
        $unit = $showUnit ? ' ms' : '';
        if ($ms < 0.1) return '< 0,1' . $unit;
        return number_format($ms, 1, ',', '.') . $unit;
    }
    
    function formatSize($bytes) {
        if ($bytes === null || $bytes === '') return 'N/A';
        if ($bytes === 0) return '0 Bytes';
        $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $base = 1024;
        $i = floor(log($bytes, $base));
        return number_format($bytes / pow($base, $i), 2, ',', '.') . ' ' . $units[$i];
    }
    
    function getPerformanceClass($value, $type = 'page_load') {
        // Valores padrão simplificados
        $thresholds = [
            'page_load' => [1000, 2000],
            'api_response' => [300, 500],
            'db_query' => [100, 200]
        ];
        
        $current = $thresholds[$type] ?? $thresholds['page_load'];
        
        if ($value < $current[0]) return 'success';
        if ($value < $current[1]) return 'warning';
        return 'danger';
    }
}
?>

<!-- Report Header Card -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h5 class="card-title">
                    <?php
                    $testTypeIcons = [
                        'page_load' => '<i class="bi bi-file-earmark-text text-primary"></i>',
                        'resource_load' => '<i class="bi bi-file-earmark-image text-success"></i>',
                        'api_response' => '<i class="bi bi-hdd-network text-info"></i>',
                        'db_query' => '<i class="bi bi-database text-warning"></i>',
                        'render_time' => '<i class="bi bi-display text-danger"></i>',
                        'memory_usage' => '<i class="bi bi-memory text-secondary"></i>',
                        'network' => '<i class="bi bi-wifi text-dark"></i>',
                        'full_page' => '<i class="bi bi-window-fullscreen text-primary"></i>'
                    ];
                    
                    $testTypeLabels = [
                        'page_load' => 'Carregamento de Página',
                        'resource_load' => 'Carregamento de Recursos',
                        'api_response' => 'Resposta API',
                        'db_query' => 'Consulta BD',
                        'render_time' => 'Tempo de Renderização',
                        'memory_usage' => 'Uso de Memória',
                        'network' => 'Rede',
                        'full_page' => 'Página Completa'
                    ];
                    
                    $icon = $testTypeIcons[$test['test_type']] ?? '<i class="bi bi-question-circle"></i>';
                    $label = $testTypeLabels[$test['test_type']] ?? $test['test_type'];
                    
                    echo $icon . ' ' . $label . ' <span class="text-muted">(Teste #' . $test['id'] . ')</span>';
                    ?>
                </h5>
                
                <div class="mb-3">
                    <strong>Data do teste:</strong> <?= date('d/m/Y H:i:s', strtotime($test['timestamp'])) ?>
                </div>
                
                <?php if (!empty($params)): ?>
                    <div class="mb-3">
                        <strong>Parâmetros:</strong>
                        <ul class="list-unstyled ms-3 mb-0">
                            <?php foreach ($params as $key => $value): ?>
                                <?php if ($key !== 'type' && !is_array($value)): ?>
                                    <li>
                                        <span class="text-muted"><?= ucfirst(str_replace('_', ' ', $key)) ?>:</span>
                                        <?= is_bool($value) ? ($value ? 'Sim' : 'Não') : $value ?>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4 text-center">
                <?php
                // Rating and Score
                $rating = isset($summary['performance_rating']) ? $summary['performance_rating'] : 'N/A';
                $ratingClass = '';
                
                switch ($rating) {
                    case 'Excelente':
                        $ratingClass = 'success';
                        $ratingIcon = 'bi-check-circle-fill';
                        break;
                    case 'Bom':
                        $ratingClass = 'success';
                        $ratingIcon = 'bi-check-circle';
                        break;
                    case 'Regular':
                        $ratingClass = 'warning';
                        $ratingIcon = 'bi-exclamation-triangle-fill';
                        break;
                    case 'Ruim':
                        $ratingClass = 'danger';
                        $ratingIcon = 'bi-exclamation-circle-fill';
                        break;
                    default:
                        $ratingClass = 'secondary';
                        $ratingIcon = 'bi-question-circle';
                }
                ?>
                
                <!-- Avaliação Global -->
                <div class="mb-3">
                    <div class="display-6 mb-2 text-<?= $ratingClass ?>">
                        <i class="bi <?= $ratingIcon ?> me-2"></i><?= $rating ?>
                    </div>
                    
                    <?php if ($testType === 'page_load' && isset($summary['avg_load_time'])): ?>
                        <h2 class="mb-2">
                            <?= $helperAvailable ? PerformanceHelper::formatTime($summary['avg_load_time']) : formatTime($summary['avg_load_time']) ?>
                        </h2>
                        <div class="text-muted">Tempo médio de carregamento</div>
                    <?php elseif ($testType === 'api_response' && isset($summary['avg_response_time'])): ?>
                        <h2 class="mb-2">
                            <?= $helperAvailable ? PerformanceHelper::formatTime($summary['avg_response_time']) : formatTime($summary['avg_response_time']) ?>
                        </h2>
                        <div class="text-muted">Tempo médio de resposta</div>
                    <?php elseif ($testType === 'db_query' && isset($summary['avg_query_time'])): ?>
                        <h2 class="mb-2">
                            <?= $helperAvailable ? PerformanceHelper::formatTime($summary['avg_query_time']) : formatTime($summary['avg_query_time']) ?>
                        </h2>
                        <div class="text-muted">Tempo médio de consulta</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Performance Metrics -->
<div class="row">
    <?php if ($testType === 'page_load'): ?>
        <!-- Page Load Specific Metrics -->
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Métricas de Tempo de Carregamento</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($summary['avg_load_time'], $summary['min_load_time'], $summary['max_load_time'])): ?>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Tempo Médio</h6>
                                    <h3>
                                        <?= $helperAvailable ? PerformanceHelper::formatTime($summary['avg_load_time']) : formatTime($summary['avg_load_time']) ?>
                                    </h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Tempo Mínimo</h6>
                                    <h3>
                                        <?= $helperAvailable ? PerformanceHelper::formatTime($summary['min_load_time']) : formatTime($summary['min_load_time']) ?>
                                    </h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Tempo Máximo</h6>
                                    <h3>
                                        <?= $helperAvailable ? PerformanceHelper::formatTime($summary['max_load_time']) : formatTime($summary['max_load_time']) ?>
                                    </h3>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                    <?php endif; ?>
                    
                    <?php if (isset($results['iterations']) && is_array($results['iterations'])): ?>
                        <h6 class="mb-3">Detalhes por Iteração</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tempo Total</th>
                                        <th>Tempo até Primeiro Byte</th>
                                        <th>Tempo de Conexão</th>
                                        <th>Tamanho</th>
                                        <th>Código HTTP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results['iterations'] as $index => $iteration): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <?php 
                                                $timeClass = $helperAvailable ? 
                                                    PerformanceHelper::getPerformanceClass($iteration['time'], 'page_load') : 
                                                    getPerformanceClass($iteration['time'], 'page_load');
                                                ?>
                                                <span class="text-<?= $timeClass ?>">
                                                    <?= $helperAvailable ? PerformanceHelper::formatTime($iteration['time']) : formatTime($iteration['time']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (isset($iteration['time_to_first_byte'])): ?>
                                                    <?php 
                                                    $ttfbClass = $helperAvailable ? 
                                                        PerformanceHelper::getPerformanceClass($iteration['time_to_first_byte'], 'ttfb') : 
                                                        getPerformanceClass($iteration['time_to_first_byte'], 'page_load');
                                                    ?>
                                                    <span class="text-<?= $ttfbClass ?>">
                                                        <?= $helperAvailable ? PerformanceHelper::formatTime($iteration['time_to_first_byte']) : formatTime($iteration['time_to_first_byte']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($iteration['connect_time'])): ?>
                                                    <?= $helperAvailable ? PerformanceHelper::formatTime($iteration['connect_time']) : formatTime($iteration['connect_time']) ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($iteration['size'])): ?>
                                                    <?= $helperAvailable ? PerformanceHelper::formatSize($iteration['size']) : formatSize($iteration['size']) ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($iteration['http_code'])): ?>
                                                    <?php
                                                    $codeClass = 'success';
                                                    if ($iteration['http_code'] >= 400) {
                                                        $codeClass = 'danger';
                                                    } elseif ($iteration['http_code'] >= 300) {
                                                        $codeClass = 'warning';
                                                    }
                                                    ?>
                                                    <span class="text-<?= $codeClass ?>">
                                                        <?= $iteration['http_code'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Chart -->
                    <div id="pageLoadChart" style="height: 300px;" class="mt-4"></div>
                </div>
            </div>
        </div>
    <?php elseif ($testType === 'api_response'): ?>
        <!-- API Response Specific Metrics -->
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Métricas de Resposta da API</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($summary['avg_response_time'], $summary['min_response_time'], $summary['max_response_time'])): ?>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Tempo Médio</h6>
                                    <h3>
                                        <?= $helperAvailable ? PerformanceHelper::formatTime($summary['avg_response_time']) : formatTime($summary['avg_response_time']) ?>
                                    </h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Tempo Mínimo</h6>
                                    <h3>
                                        <?= $helperAvailable ? PerformanceHelper::formatTime($summary['min_response_time']) : formatTime($summary['min_response_time']) ?>
                                    </h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Tempo Máximo</h6>
                                    <h3>
                                        <?= $helperAvailable ? PerformanceHelper::formatTime($summary['max_response_time']) : formatTime($summary['max_response_time']) ?>
                                    </h3>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                    <?php endif; ?>
                    
                    <?php if (isset($summary['endpoint'])): ?>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Endpoint</h6>
                                    <h5><?= $summary['endpoint'] ?></h5>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Método</h6>
                                    <h5>
                                        <?php
                                        $methodColors = [
                                            'GET' => 'success',
                                            'POST' => 'primary',
                                            'PUT' => 'warning',
                                            'DELETE' => 'danger'
                                        ];
                                        $method = isset($summary['method']) ? $summary['method'] : 'GET';
                                        $methodColor = $methodColors[$method] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $methodColor ?>"><?= $method ?></span>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($results['iterations']) && is_array($results['iterations'])): ?>
                        <h6 class="mb-3">Detalhes por Iteração</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tempo Total</th>
                                        <th>Tempo até Primeiro Byte</th>
                                        <th>Tamanho</th>
                                        <th>Código HTTP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results['iterations'] as $index => $iteration): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <?php 
                                                $timeClass = $helperAvailable ? 
                                                    PerformanceHelper::getPerformanceClass($iteration['time'], 'api_response') : 
                                                    getPerformanceClass($iteration['time'], 'api_response');
                                                ?>
                                                <span class="text-<?= $timeClass ?>">
                                                    <?= $helperAvailable ? PerformanceHelper::formatTime($iteration['time']) : formatTime($iteration['time']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (isset($iteration['time_to_first_byte'])): ?>
                                                    <?= $helperAvailable ? PerformanceHelper::formatTime($iteration['time_to_first_byte']) : formatTime($iteration['time_to_first_byte']) ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($iteration['size'])): ?>
                                                    <?= $helperAvailable ? PerformanceHelper::formatSize($iteration['size']) : formatSize($iteration['size']) ?>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($iteration['http_code'])): ?>
                                                    <?php
                                                    $codeClass = 'success';
                                                    if ($iteration['http_code'] >= 400) {
                                                        $codeClass = 'danger';
                                                    } elseif ($iteration['http_code'] >= 300) {
                                                        $codeClass = 'warning';
                                                    }
                                                    ?>
                                                    <span class="text-<?= $codeClass ?>">
                                                        <?= $iteration['http_code'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Chart -->
                    <div id="apiResponseChart" style="height: 300px;" class="mt-4"></div>
                </div>
            </div>
        </div>
    <?php elseif ($testType === 'db_query'): ?>
        <!-- DB Query Specific Metrics -->
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Métricas de Consulta ao Banco de Dados</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($summary['avg_query_time'], $summary['min_query_time'], $summary['max_query_time'])): ?>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Tempo Médio</h6>
                                    <h3>
                                        <?= $helperAvailable ? PerformanceHelper::formatTime($summary['avg_query_time']) : formatTime($summary['avg_query_time']) ?>
                                    </h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Tempo Mínimo</h6>
                                    <h3>
                                        <?= $helperAvailable ? PerformanceHelper::formatTime($summary['min_query_time']) : formatTime($summary['min_query_time']) ?>
                                    </h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Tempo Máximo</h6>
                                    <h3>
                                        <?= $helperAvailable ? PerformanceHelper::formatTime($summary['max_query_time']) : formatTime($summary['max_query_time']) ?>
                                    </h3>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                    <?php endif; ?>
                    
                    <?php if (isset($summary['query_type'])): ?>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Tipo de Consulta</h6>
                                    <h5><?= isset($summary['query_description']) ? $summary['query_description'] : $summary['query_type'] ?></h5>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Iterações</h6>
                                    <h5><?= isset($summary['iterations']) ? $summary['iterations'] : 'N/A' ?></h5>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($results['iterations']) && is_array($results['iterations'])): ?>
                        <h6 class="mb-3">Detalhes por Iteração</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tempo de Execução</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results['iterations'] as $index => $iteration): ?>
                                        <tr>
                                            <td><?= $iteration['iteration'] ?? ($index + 1) ?></td>
                                            <td>
                                                <?php 
                                                $timeClass = $helperAvailable ? 
                                                    PerformanceHelper::getPerformanceClass($iteration['time'], 'db_query') : 
                                                    getPerformanceClass($iteration['time'], 'db_query');
                                                ?>
                                                <span class="text-<?= $timeClass ?>">
                                                    <?= $helperAvailable ? PerformanceHelper::formatTime($iteration['time']) : formatTime($iteration['time']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Chart -->
                    <div id="dbQueryChart" style="height: 300px;" class="mt-4"></div>
                </div>
            </div>
        </div>
    <?php elseif ($testType === 'full_page'): ?>
        <!-- Full Page Test Metrics -->
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Resultados do Teste Completo</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($summary['url'])): ?>
                        <div class="mb-4">
                            <h6 class="text-muted mb-1">URL Testada</h6>
                            <h5><?= $summary['url'] ?></h5>
                        </div>
                        
                        <hr class="my-4">
                    <?php endif; ?>
                    
                    <h6 class="mb-3">Resumo dos Componentes</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Componente</th>
                                    <th>Métrica Principal</th>
                                    <th>Avaliação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($summary['page_load'])): ?>
                                    <tr>
                                        <td>
                                            <i class="bi bi-file-earmark-text text-primary me-2"></i>
                                            Carregamento de Página
                                        </td>
                                        <td>
                                            <?php 
                                            $avgLoadTime = $summary['page_load']['avg_load_time'] ?? 0;
                                            $timeClass = $helperAvailable ? 
                                                PerformanceHelper::getPerformanceClass($avgLoadTime, 'page_load') : 
                                                getPerformanceClass($avgLoadTime, 'page_load');
                                            ?>
                                            <span class="text-<?= $timeClass ?>">
                                                <?= $helperAvailable ? PerformanceHelper::formatTime($avgLoadTime) : formatTime($avgLoadTime) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $rating = $summary['page_load']['performance_rating'] ?? 'N/A';
                                            $ratingClass = '';
                                            
                                            switch ($rating) {
                                                case 'Excelente':
                                                case 'Bom':
                                                    $ratingClass = 'success';
                                                    $ratingIcon = 'bi-check-circle';
                                                    break;
                                                case 'Regular':
                                                    $ratingClass = 'warning';
                                                    $ratingIcon = 'bi-exclamation-triangle';
                                                    break;
                                                case 'Ruim':
                                                    $ratingClass = 'danger';
                                                    $ratingIcon = 'bi-exclamation-circle';
                                                    break;
                                                default:
                                                    $ratingClass = 'secondary';
                                                    $ratingIcon = 'bi-question-circle';
                                            }
                                            ?>
                                            <span class="text-<?= $ratingClass ?>">
                                                <i class="bi <?= $ratingIcon ?> me-1"></i><?= $rating ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                
                                <?php if (isset($summary['db_query'])): ?>
                                    <tr>
                                        <td>
                                            <i class="bi bi-database text-warning me-2"></i>
                                            Consulta ao Banco de Dados
                                        </td>
                                        <td>
                                            <?php 
                                            $avgQueryTime = $summary['db_query']['avg_query_time'] ?? 0;
                                            $timeClass = $helperAvailable ? 
                                                PerformanceHelper::getPerformanceClass($avgQueryTime, 'db_query') : 
                                                getPerformanceClass($avgQueryTime, 'db_query');
                                            ?>
                                            <span class="text-<?= $timeClass ?>">
                                                <?= $helperAvailable ? PerformanceHelper::formatTime($avgQueryTime) : formatTime($avgQueryTime) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $rating = $summary['db_query']['performance_rating'] ?? 'N/A';
                                            $ratingClass = '';
                                            
                                            switch ($rating) {
                                                case 'Excelente':
                                                case 'Bom':
                                                    $ratingClass = 'success';
                                                    $ratingIcon = 'bi-check-circle';
                                                    break;
                                                case 'Regular':
                                                    $ratingClass = 'warning';
                                                    $ratingIcon = 'bi-exclamation-triangle';
                                                    break;
                                                case 'Ruim':
                                                    $ratingClass = 'danger';
                                                    $ratingIcon = 'bi-exclamation-circle';
                                                    break;
                                                default:
                                                    $ratingClass = 'secondary';
                                                    $ratingIcon = 'bi-question-circle';
                                            }
                                            ?>
                                            <span class="text-<?= $ratingClass ?>">
                                                <i class="bi <?= $ratingIcon ?> me-1"></i><?= $rating ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                
                                <?php if (isset($summary['memory_usage'])): ?>
                                    <tr>
                                        <td>
                                            <i class="bi bi-memory text-secondary me-2"></i>
                                            Uso de Memória
                                        </td>
                                        <td>
                                            <?= $summary['memory_usage']['memory_used'] ?? 'N/A' ?>
                                        </td>
                                        <td>N/A</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Generic Metrics for other test types -->
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Resultados do Teste</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Visualização detalhada não disponível para este tipo de teste. Verifique os dados brutos abaixo.
                    </div>
                    
                    <pre class="bg-light p-3 rounded"><?= json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Recommendations -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-lightbulb text-warning me-2"></i>
                    Recomendações
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recommendations)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recommendations as $recommendation): ?>
                            <li class="list-group-item border-0 ps-0">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <?= $recommendation ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Não foi possível gerar recomendações para este teste.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript to generate charts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Extract data for charts based on test type
    <?php if ($testType === 'page_load' && isset($results['iterations'])): ?>
        // Page Load Chart
        const pageLoadChartEl = document.getElementById('pageLoadChart');
        if (pageLoadChartEl) {
            const iterations = <?= json_encode(array_column($results['iterations'], 'time')) ?>;
            const labels = iterations.map((_, index) => `Iteração ${index + 1}`);
            
            const ttfb = <?= json_encode(array_map(function($it) {
                return isset($it['time_to_first_byte']) ? $it['time_to_first_byte'] : null;
            }, $results['iterations'])) ?>;
            
            const ctx = pageLoadChartEl.getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Tempo Total (ms)',
                            data: iterations,
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgb(54, 162, 235)',
                            borderWidth: 1
                        },
                        {
                            label: 'Tempo até Primeiro Byte (ms)',
                            data: ttfb,
                            backgroundColor: 'rgba(255, 99, 132, 0.5)',
                            borderColor: 'rgb(255, 99, 132)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Tempo (ms)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Iterações'
                            }
                        }
                    }
                }
            });
        }
    <?php elseif ($testType === 'api_response' && isset($results['iterations'])): ?>
        // API Response Chart
        const apiResponseChartEl = document.getElementById('apiResponseChart');
        if (apiResponseChartEl) {
            const iterations = <?= json_encode(array_column($results['iterations'], 'time')) ?>;
            const labels = iterations.map((_, index) => `Iteração ${index + 1}`);
            
            const ctx = apiResponseChartEl.getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Tempo de Resposta (ms)',
                            data: iterations,
                            backgroundColor: 'rgba(255, 99, 132, 0.5)',
                            borderColor: 'rgb(255, 99, 132)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Tempo (ms)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Iterações'
                            }
                        }
                    }
                }
            });
        }
    <?php elseif ($testType === 'db_query' && isset($results['iterations'])): ?>
        // DB Query Chart
        const dbQueryChartEl = document.getElementById('dbQueryChart');
        if (dbQueryChartEl) {
            const iterations = <?= json_encode(array_column($results['iterations'], 'time')) ?>;
            const labels = iterations.map((_, index) => `Iteração ${index + 1}`);
            
            const ctx = dbQueryChartEl.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Tempo de Consulta (ms)',
                            data: iterations,
                            backgroundColor: 'rgba(255, 205, 86, 0.5)',
                            borderColor: 'rgb(255, 205, 86)',
                            borderWidth: 2,
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Tempo (ms)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Iterações'
                            }
                        }
                    }
                }
            });
        }
    <?php endif; ?>
    
    // Export Report Button
    const exportBtn = document.getElementById('exportReportBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            // Get report content
            const reportTitle = document.title;
            const testInfo = {
                id: <?= $test['id'] ?>,
                type: '<?= $testType ?>',
                date: '<?= date('Y-m-d H:i:s', strtotime($test['timestamp'])) ?>'
            };
            
            // Create HTML content for export
            let content = `
                <html>
                <head>
                    <title>${reportTitle}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        h1 { color: #0d6efd; }
                        h2 { color: #6c757d; margin-top: 30px; }
                        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
                        th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
                        th { background-color: #f8f9fa; }
                        .text-success { color: #198754; }
                        .text-warning { color: #ffc107; }
                        .text-danger { color: #dc3545; }
                        .recommendation { margin: 5px 0; }
                    </style>
                </head>
                <body>
                    <h1>Relatório de Teste de Performance #${testInfo.id}</h1>
                    <p><strong>Tipo:</strong> ${testInfo.type}</p>
                    <p><strong>Data:</strong> ${testInfo.date}</p>
                    
                    <h2>Resumo</h2>
            `;
            
            // Add summary based on test type
            <?php if ($testType === 'page_load'): ?>
                if (<?= isset($summary['avg_load_time']) ? 'true' : 'false' ?>) {
                    content += `
                        <p><strong>Tempo Médio de Carregamento:</strong> 
                            <?= isset($summary['avg_load_time']) ? $summary['avg_load_time'] . ' ms' : 'N/A' ?></p>
                        <p><strong>Avaliação:</strong> 
                            <?= isset($summary['performance_rating']) ? $summary['performance_rating'] : 'N/A' ?></p>
                    `;
                }
            <?php elseif ($testType === 'api_response'): ?>
                if (<?= isset($summary['avg_response_time']) ? 'true' : 'false' ?>) {
                    content += `
                        <p><strong>Tempo Médio de Resposta:</strong> 
                            <?= isset($summary['avg_response_time']) ? $summary['avg_response_time'] . ' ms' : 'N/A' ?></p>
                        <p><strong>Endpoint:</strong> 
                            <?= isset($summary['endpoint']) ? $summary['endpoint'] : 'N/A' ?></p>
                        <p><strong>Método:</strong> 
                            <?= isset($summary['method']) ? $summary['method'] : 'GET' ?></p>
                        <p><strong>Avaliação:</strong> 
                            <?= isset($summary['performance_rating']) ? $summary['performance_rating'] : 'N/A' ?></p>
                    `;
                }
            <?php elseif ($testType === 'db_query'): ?>
                if (<?= isset($summary['avg_query_time']) ? 'true' : 'false' ?>) {
                    content += `
                        <p><strong>Tempo Médio de Consulta:</strong> 
                            <?= isset($summary['avg_query_time']) ? $summary['avg_query_time'] . ' ms' : 'N/A' ?></p>
                        <p><strong>Tipo de Consulta:</strong> 
                            <?= isset($summary['query_description']) ? $summary['query_description'] : (isset($summary['query_type']) ? $summary['query_type'] : 'N/A') ?></p>
                        <p><strong>Avaliação:</strong> 
                            <?= isset($summary['performance_rating']) ? $summary['performance_rating'] : 'N/A' ?></p>
                    `;
                }
            <?php endif; ?>
            
            // Add recommendations
            content += `
                <h2>Recomendações</h2>
                <ul>
            `;
            
            <?php if (!empty($recommendations)): ?>
                <?php foreach ($recommendations as $recommendation): ?>
                    content += `<li class="recommendation"><?= $recommendation ?></li>`;
                <?php endforeach; ?>
            <?php else: ?>
                content += `<li>Não foram geradas recomendações para este teste.</li>`;
            <?php endif; ?>
            
            content += `
                </ul>
                
                <h2>Detalhes</h2>
                <pre>${JSON.stringify(<?= json_encode($results) ?>, null, 2)}</pre>
                
                <footer>
                    <p>Gerado por Taverna da Impressão 3D - Ferramenta de Testes de Performance</p>
                    <p>Data do relatório: ${new Date().toLocaleString()}</p>
                </footer>
                </body>
                </html>
            `;
            
            // Create blob and download
            const blob = new Blob([content], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `teste-performance-${testInfo.id}.html`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    }
});
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
