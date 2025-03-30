<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<!-- Page Title -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Relatório de Teste de Performance</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="<?= BASE_URL ?>admin/performance_test" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer"></i> Imprimir
            </button>
        </div>
        <button type="button" class="btn btn-sm btn-primary" id="exportPDF">
            <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
        </button>
    </div>
</div>

<!-- Test Information Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <h5 class="card-title mb-0">Informações do Teste</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th style="width: 35%">ID do Teste:</th>
                        <td><?= $test['id'] ?></td>
                    </tr>
                    <tr>
                        <th>Tipo de Teste:</th>
                        <td>
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
                            
                            echo $icon . ' ' . $label;
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Data/Hora:</th>
                        <td><?= date('d/m/Y H:i:s', strtotime($test['timestamp'])) ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm">
                    <?php if ($test['test_type'] === 'page_load' && isset($test['params']['url'])): ?>
                    <tr>
                        <th style="width: 35%">URL Testada:</th>
                        <td>
                            <a href="<?= $test['params']['url'] ?>" target="_blank">
                                <?= $test['params']['url'] ?>
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if ($test['test_type'] === 'api_response' && isset($test['params']['endpoint'])): ?>
                    <tr>
                        <th style="width: 35%">Endpoint API:</th>
                        <td><?= $test['params']['endpoint'] ?></td>
                    </tr>
                    <tr>
                        <th>Método:</th>
                        <td><?= $test['params']['method'] ?? 'GET' ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if ($test['test_type'] === 'db_query' && isset($test['params']['query_type'])): ?>
                    <tr>
                        <th style="width: 35%">Tipo de Consulta:</th>
                        <td><?= $test['results']['summary']['query_description'] ?? $test['params']['query_type'] ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (isset($test['results']['summary']['performance_rating'])): ?>
                    <tr>
                        <th>Avaliação:</th>
                        <td>
                            <?php
                            $ratingClasses = [
                                'Excelente' => 'success',
                                'Bom' => 'success',
                                'Regular' => 'warning',
                                'Ruim' => 'danger'
                            ];
                            
                            $rating = $test['results']['summary']['performance_rating'];
                            $ratingClass = $ratingClasses[$rating] ?? 'secondary';
                            
                            echo '<span class="badge bg-' . $ratingClass . '">' . $rating . '</span>';
                            ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (isset($test['results']['summary']['iterations'])): ?>
                    <tr>
                        <th>Iterações:</th>
                        <td><?= $test['results']['summary']['iterations'] ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Performance Summary Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <h5 class="card-title mb-0">Resumo de Performance</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php if ($test['test_type'] === 'page_load' || $test['test_type'] === 'full_page'): ?>
            <!-- Page Load Metrics -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="card-subtitle mb-2 text-muted">Tempo Médio de Carregamento</h6>
                        <h2 class="display-4">
                            <?= isset($test['results']['summary']['avg_load_time']) ? number_format($test['results']['summary']['avg_load_time'], 1) : '0.0' ?>
                            <small class="text-muted fs-6">ms</small>
                        </h2>
                        <div class="mt-3">
                            <span class="badge bg-secondary">Min: <?= isset($test['results']['summary']['min_load_time']) ? number_format($test['results']['summary']['min_load_time'], 1) : '0.0' ?> ms</span>
                            <span class="badge bg-secondary">Max: <?= isset($test['results']['summary']['max_load_time']) ? number_format($test['results']['summary']['max_load_time'], 1) : '0.0' ?> ms</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($test['test_type'] === 'api_response'): ?>
            <!-- API Response Metrics -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="card-subtitle mb-2 text-muted">Tempo Médio de Resposta API</h6>
                        <h2 class="display-4">
                            <?= isset($test['results']['summary']['avg_response_time']) ? number_format($test['results']['summary']['avg_response_time'], 1) : '0.0' ?>
                            <small class="text-muted fs-6">ms</small>
                        </h2>
                        <div class="mt-3">
                            <span class="badge bg-secondary">Min: <?= isset($test['results']['summary']['min_response_time']) ? number_format($test['results']['summary']['min_response_time'], 1) : '0.0' ?> ms</span>
                            <span class="badge bg-secondary">Max: <?= isset($test['results']['summary']['max_response_time']) ? number_format($test['results']['summary']['max_response_time'], 1) : '0.0' ?> ms</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($test['test_type'] === 'db_query'): ?>
            <!-- Database Query Metrics -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="card-subtitle mb-2 text-muted">Tempo Médio de Consulta</h6>
                        <h2 class="display-4">
                            <?= isset($test['results']['summary']['avg_query_time']) ? number_format($test['results']['summary']['avg_query_time'], 1) : '0.0' ?>
                            <small class="text-muted fs-6">ms</small>
                        </h2>
                        <div class="mt-3">
                            <span class="badge bg-secondary">Min: <?= isset($test['results']['summary']['min_query_time']) ? number_format($test['results']['summary']['min_query_time'], 1) : '0.0' ?> ms</span>
                            <span class="badge bg-secondary">Max: <?= isset($test['results']['summary']['max_query_time']) ? number_format($test['results']['summary']['max_query_time'], 1) : '0.0' ?> ms</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($test['test_type'] === 'memory_usage'): ?>
            <!-- Memory Usage Metrics -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="card-subtitle mb-2 text-muted">Uso de Memória</h6>
                        <h2 class="display-4">
                            <?= isset($test['results']['summary']['memory_used']) ? $test['results']['summary']['memory_used'] : '0 MB' ?>
                        </h2>
                        <div class="mt-3">
                            <span class="badge bg-secondary">Pico: <?= isset($test['results']['summary']['memory_peak']) ? $test['results']['summary']['memory_peak'] : '0 MB' ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Visualization Placeholder -->
            <div class="col-md-8">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <canvas id="performanceChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Results -->
<?php if ($test['test_type'] === 'page_load' && isset($test['results']['iterations'])): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <h5 class="card-title mb-0">Detalhes por Iteração</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Tempo Total</th>
                        <th>Código HTTP</th>
                        <th>Tamanho</th>
                        <th>Tempo até Primeiro Byte</th>
                        <th>Tempo de Conexão</th>
                        <th>Redirecionamentos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($test['results']['iterations'] as $i => $iteration): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= number_format($iteration['time'], 1) ?> ms</td>
                        <td>
                            <?php 
                            $statusClass = 'success';
                            if ($iteration['http_code'] >= 400) {
                                $statusClass = 'danger';
                            } elseif ($iteration['http_code'] >= 300) {
                                $statusClass = 'warning';
                            }
                            ?>
                            <span class="badge bg-<?= $statusClass ?>"><?= $iteration['http_code'] ?></span>
                        </td>
                        <td><?= number_format($iteration['size'] / 1024, 1) ?> KB</td>
                        <td><?= number_format($iteration['time_to_first_byte'], 1) ?> ms</td>
                        <td><?= number_format($iteration['connect_time'], 1) ?> ms</td>
                        <td><?= $iteration['redirect_count'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($test['test_type'] === 'api_response' && isset($test['results']['iterations'])): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <h5 class="card-title mb-0">Detalhes por Iteração</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Tempo de Resposta</th>
                        <th>Código HTTP</th>
                        <th>Tamanho</th>
                        <th>Tempo até Primeiro Byte</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($test['results']['iterations'] as $i => $iteration): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= number_format($iteration['time'], 1) ?> ms</td>
                        <td>
                            <?php 
                            $statusClass = 'success';
                            if ($iteration['http_code'] >= 400) {
                                $statusClass = 'danger';
                            } elseif ($iteration['http_code'] >= 300) {
                                $statusClass = 'warning';
                            }
                            ?>
                            <span class="badge bg-<?= $statusClass ?>"><?= $iteration['http_code'] ?></span>
                        </td>
                        <td><?= number_format($iteration['size'] / 1024, 1) ?> KB</td>
                        <td><?= number_format($iteration['time_to_first_byte'], 1) ?> ms</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($test['test_type'] === 'db_query' && isset($test['results']['iterations'])): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <h5 class="card-title mb-0">Detalhes por Iteração</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Tempo de Consulta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($test['results']['iterations'] as $i => $iteration): ?>
                    <tr>
                        <td><?= $iteration['iteration'] ?></td>
                        <td><?= number_format($iteration['time'], 1) ?> ms</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recommendations -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <h5 class="card-title mb-0">Recomendações</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($recommendations)): ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($recommendations as $recommendation): ?>
            <li class="list-group-item">
                <i class="bi bi-lightbulb text-warning me-2"></i> <?= $recommendation ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p class="mb-0">Nenhuma recomendação disponível para este teste.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Raw Data Toggle -->
<div class="mb-4">
    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#rawDataCollapse" aria-expanded="false" aria-controls="rawDataCollapse">
        <i class="bi bi-code-slash"></i> Exibir Dados Brutos
    </button>
</div>

<!-- Raw Data Collapse -->
<div class="collapse mb-4" id="rawDataCollapse">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent">
            <h5 class="card-title mb-0">Dados Brutos</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <h6>Parâmetros:</h6>
                <pre class="bg-light p-3 rounded"><code><?= json_encode($test['params'], JSON_PRETTY_PRINT) ?></code></pre>
            </div>
            <div>
                <h6>Resultados:</h6>
                <pre class="bg-light p-3 rounded"><code><?= json_encode($test['results'], JSON_PRETTY_PRINT) ?></code></pre>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js initialization
    const ctx = document.getElementById('performanceChart');
    
    <?php if ($test['test_type'] === 'page_load' && isset($test['results']['iterations'])): ?>
    const pageLoadChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Array.from({length: <?= count($test['results']['iterations']) ?>}, (_, i) => `Iteração ${i+1}`),
            datasets: [{
                label: 'Tempo de Carregamento (ms)',
                data: [<?= implode(',', array_map(function($iteration) { return $iteration['time']; }, $test['results']['iterations'])) ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Tempo (ms)'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Tempo de Carregamento por Iteração'
                }
            }
        }
    });
    <?php elseif ($test['test_type'] === 'api_response' && isset($test['results']['iterations'])): ?>
    const apiResponseChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Array.from({length: <?= count($test['results']['iterations']) ?>}, (_, i) => `Iteração ${i+1}`),
            datasets: [{
                label: 'Tempo de Resposta (ms)',
                data: [<?= implode(',', array_map(function($iteration) { return $iteration['time']; }, $test['results']['iterations'])) ?>],
                backgroundColor: 'rgba(255, 99, 132, 0.5)',
                borderColor: 'rgb(255, 99, 132)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Tempo (ms)'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Tempo de Resposta API por Iteração'
                }
            }
        }
    });
    <?php elseif ($test['test_type'] === 'db_query' && isset($test['results']['iterations'])): ?>
    const dbQueryChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Array.from({length: <?= count($test['results']['iterations']) ?>}, (_, i) => `Iteração ${i+1}`),
            datasets: [{
                label: 'Tempo de Consulta (ms)',
                data: [<?= implode(',', array_map(function($iteration) { return $iteration['time']; }, $test['results']['iterations'])) ?>],
                backgroundColor: 'rgba(255, 206, 86, 0.5)',
                borderColor: 'rgb(255, 206, 86)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Tempo (ms)'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Tempo de Consulta BD por Iteração'
                }
            }
        }
    });
    <?php else: ?>
    // No chart data available
    <?php endif; ?>
    
    // Export to PDF functionality
    document.getElementById('exportPDF').addEventListener('click', function() {
        window.print();
    });
});
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>