<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<!-- Page Title -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Testes de Performance</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="<?= BASE_URL ?>admin/performance_test/settings" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-gear"></i> Configurações
            </a>
        </div>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newTestModal">
            <i class="bi bi-plus-circle"></i> Novo Teste
        </button>
    </div>
</div>

<!-- Resumo de Performance -->
<div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded">
                        <i class="bi bi-speedometer2 fs-3 text-primary"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-title text-muted mb-0">Tempo Médio de Carregamento</h6>
                        <h2 class="my-2"><?= number_format($summary['page_load'], 2) ?> ms</h2>
                        <p class="card-text text-muted mb-0">
                            <?php
                            $pageLoadClass = 'text-success';
                            $pageLoadIcon = 'check-circle';
                            
                            if ($summary['page_load'] > 2000) {
                                $pageLoadClass = 'text-danger';
                                $pageLoadIcon = 'exclamation-circle';
                            } elseif ($summary['page_load'] > 1000) {
                                $pageLoadClass = 'text-warning';
                                $pageLoadIcon = 'exclamation-triangle';
                            }
                            ?>
                            <span class="<?= $pageLoadClass ?>">
                                <i class="bi bi-<?= $pageLoadIcon ?>"></i> 
                                <?= $summary['page_load'] <= 1000 ? 'Bom' : ($summary['page_load'] <= 2000 ? 'Regular' : 'Ruim') ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-success bg-opacity-10 p-3 rounded">
                        <i class="bi bi-hdd-network fs-3 text-success"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-title text-muted mb-0">Tempo Médio de Resposta API</h6>
                        <h2 class="my-2"><?= number_format($summary['api_response'], 2) ?> ms</h2>
                        <p class="card-text text-muted mb-0">
                            <?php
                            $apiClass = 'text-success';
                            $apiIcon = 'check-circle';
                            
                            if ($summary['api_response'] > 300) {
                                $apiClass = 'text-danger';
                                $apiIcon = 'exclamation-circle';
                            } elseif ($summary['api_response'] > 150) {
                                $apiClass = 'text-warning';
                                $apiIcon = 'exclamation-triangle';
                            }
                            ?>
                            <span class="<?= $apiClass ?>">
                                <i class="bi bi-<?= $apiIcon ?>"></i> 
                                <?= $summary['api_response'] <= 150 ? 'Bom' : ($summary['api_response'] <= 300 ? 'Regular' : 'Ruim') ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-info bg-opacity-10 p-3 rounded">
                        <i class="bi bi-database fs-3 text-info"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-title text-muted mb-0">Tempo Médio de Consulta BD</h6>
                        <h2 class="my-2"><?= number_format($summary['db_query'], 2) ?> ms</h2>
                        <p class="card-text text-muted mb-0">
                            <?php
                            $dbClass = 'text-success';
                            $dbIcon = 'check-circle';
                            
                            if ($summary['db_query'] > 150) {
                                $dbClass = 'text-danger';
                                $dbIcon = 'exclamation-circle';
                            } elseif ($summary['db_query'] > 80) {
                                $dbClass = 'text-warning';
                                $dbIcon = 'exclamation-triangle';
                            }
                            ?>
                            <span class="<?= $dbClass ?>">
                                <i class="bi bi-<?= $dbIcon ?>"></i> 
                                <?= $summary['db_query'] <= 80 ? 'Bom' : ($summary['db_query'] <= 150 ? 'Regular' : 'Ruim') ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Performance Analytics Cards -->
<div class="row mb-4">
    <!-- Worst Performing Pages -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">Páginas com Pior Desempenho</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>URL</th>
                                <th class="text-end">Tempo Médio</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($summary['worst_performing_pages'])): ?>
                                <?php foreach ($summary['worst_performing_pages'] as $page): ?>
                                <tr>
                                    <td class="text-truncate" style="max-width: 250px;"><?= $page['url'] ?></td>
                                    <td class="text-end"><?= number_format($page['avg_time'], 2) ?> ms</td>
                                    <td class="text-center">
                                        <?php
                                        $pageClass = 'success';
                                        
                                        if ($page['avg_time'] > 2000) {
                                            $pageClass = 'danger';
                                        } elseif ($page['avg_time'] > 1000) {
                                            $pageClass = 'warning';
                                        }
                                        ?>
                                        <span class="badge bg-<?= $pageClass ?>">
                                            <?= $page['avg_time'] <= 1000 ? 'Bom' : ($page['avg_time'] <= 2000 ? 'Regular' : 'Ruim') ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3">Nenhum dado disponível</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Best Performing Pages -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="card-title mb-0">Páginas com Melhor Desempenho</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>URL</th>
                                <th class="text-end">Tempo Médio</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($summary['best_performing_pages'])): ?>
                                <?php foreach ($summary['best_performing_pages'] as $page): ?>
                                <tr>
                                    <td class="text-truncate" style="max-width: 250px;"><?= $page['url'] ?></td>
                                    <td class="text-end"><?= number_format($page['avg_time'], 2) ?> ms</td>
                                    <td class="text-center">
                                        <?php
                                        $pageClass = 'success';
                                        
                                        if ($page['avg_time'] > 2000) {
                                            $pageClass = 'danger';
                                        } elseif ($page['avg_time'] > 1000) {
                                            $pageClass = 'warning';
                                        }
                                        ?>
                                        <span class="badge bg-<?= $pageClass ?>">
                                            <?= $page['avg_time'] <= 1000 ? 'Bom' : ($page['avg_time'] <= 2000 ? 'Regular' : 'Ruim') ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3">Nenhum dado disponível</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Tests -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Testes Recentes</h5>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#compareTestsModal">
                    <i class="bi bi-bar-chart-line"></i> Comparar
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="testsTable">
                <thead class="table-light">
                    <tr>
                        <th>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAllTests">
                            </div>
                        </th>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Data</th>
                        <th>Avaliação</th>
                        <th>Tempo Médio</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tests)): ?>
                        <?php foreach ($tests as $test): ?>
                        <tr>
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input test-checkbox" type="checkbox" value="<?= $test['id'] ?>">
                                </div>
                            </td>
                            <td><?= $test['id'] ?></td>
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
                            <td><?= date('d/m/Y H:i', strtotime($test['timestamp'])) ?></td>
                            <td>
                                <?php
                                $ratingClasses = [
                                    'Excelente' => 'success',
                                    'Bom' => 'success',
                                    'Regular' => 'warning',
                                    'Ruim' => 'danger'
                                ];
                                
                                $results = json_decode($test['results'], true);
                                $rating = isset($results['summary']['performance_rating']) ? $results['summary']['performance_rating'] : 'N/A';
                                $ratingClass = $ratingClasses[$rating] ?? 'secondary';
                                
                                echo '<span class="badge bg-' . $ratingClass . '">' . $rating . '</span>';
                                ?>
                            </td>
                            <td>
                                <?php
                                $avgTime = '';
                                if (isset($results['summary'])) {
                                    if (isset($results['summary']['avg_load_time'])) {
                                        $avgTime = $results['summary']['avg_load_time'] . ' ms';
                                    } elseif (isset($results['summary']['avg_response_time'])) {
                                        $avgTime = $results['summary']['avg_response_time'] . ' ms';
                                    } elseif (isset($results['summary']['avg_query_time'])) {
                                        $avgTime = $results['summary']['avg_query_time'] . ' ms';
                                    }
                                }
                                echo $avgTime;
                                ?>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>admin/performance_test/view_report/<?= $test['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-graph-up"></i> Relatório
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-3">Nenhum teste encontrado</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Novo Teste -->
<div class="modal fade" id="newTestModal" tabindex="-1" aria-labelledby="newTestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newTestModalLabel">Novo Teste de Performance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="testTypeTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="page-load-tab" data-bs-toggle="tab" data-bs-target="#page-load" type="button" role="tab" aria-controls="page-load" aria-selected="true">
                            <i class="bi bi-file-earmark-text"></i> Carregamento de Página
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="api-response-tab" data-bs-toggle="tab" data-bs-target="#api-response" type="button" role="tab" aria-controls="api-response" aria-selected="false">
                            <i class="bi bi-hdd-network"></i> Resposta API
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="db-query-tab" data-bs-toggle="tab" data-bs-target="#db-query" type="button" role="tab" aria-controls="db-query" aria-selected="false">
                            <i class="bi bi-database"></i> Consulta BD
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="full-page-tab" data-bs-toggle="tab" data-bs-target="#full-page" type="button" role="tab" aria-controls="full-page" aria-selected="false">
                            <i class="bi bi-window-fullscreen"></i> Página Completa
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content pt-3" id="testTypeTabsContent">
                    <!-- Formulário para Teste de Carregamento de Página -->
                    <div class="tab-pane fade show active" id="page-load" role="tabpanel" aria-labelledby="page-load-tab">
                        <form id="pageLoadForm" class="needs-validation" novalidate>
                            <input type="hidden" name="type" value="page_load">
                            
                            <div class="mb-3">
                                <label for="page_url" class="form-label">URL da Página</label>
                                <select class="form-select" id="page_url" name="url" required>
                                    <option value="">Selecione uma página</option>
                                    <?php foreach ($pages as $key => $page): ?>
                                    <option value="<?= $page['url'] ?>"><?= $page['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Selecione a página para testar o tempo de carregamento.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="page_iterations" class="form-label">Iterações</label>
                                <input type="number" class="form-control" id="page_iterations" name="iterations" value="3" min="1" max="10" required>
                                <div class="form-text">Número de vezes que o teste será executado para calcular a média.</div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-play"></i> Executar Teste
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Formulário para Teste de Resposta de API -->
                    <div class="tab-pane fade" id="api-response" role="tabpanel" aria-labelledby="api-response-tab">
                        <form id="apiResponseForm" class="needs-validation" novalidate>
                            <input type="hidden" name="type" value="api_response">
                            
                            <div class="mb-3">
                                <label for="api_endpoint" class="form-label">Endpoint da API</label>
                                <input type="text" class="form-control" id="api_endpoint" name="endpoint" required>
                                <div class="form-text">Endpoint da API relativo à base da URL (ex: api/products).</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="api_method" class="form-label">Método</label>
                                        <select class="form-select" id="api_method" name="method">
                                            <option value="GET">GET</option>
                                            <option value="POST">POST</option>
                                            <option value="PUT">PUT</option>
                                            <option value="DELETE">DELETE</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="api_iterations" class="form-label">Iterações</label>
                                        <input type="number" class="form-control" id="api_iterations" name="iterations" value="5" min="1" max="10" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="api_data" class="form-label">Dados (JSON)</label>
                                <textarea class="form-control" id="api_data" name="data" rows="3"></textarea>
                                <div class="form-text">Dados em formato JSON para enviar (apenas para POST/PUT).</div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-play"></i> Executar Teste
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Formulário para Teste de Consulta ao Banco de Dados -->
                    <div class="tab-pane fade" id="db-query" role="tabpanel" aria-labelledby="db-query-tab">
                        <form id="dbQueryForm" class="needs-validation" novalidate>
                            <input type="hidden" name="type" value="db_query">
                            
                            <div class="mb-3">
                                <label for="query_type" class="form-label">Tipo de Consulta</label>
                                <select class="form-select" id="query_type" name="query_type" required>
                                    <option value="">Selecione um tipo de consulta</option>
                                    <option value="products_all">Todos os produtos</option>
                                    <option value="products_category">Produtos por categoria</option>
                                    <option value="products_search">Busca de produtos</option>
                                    <option value="order_details">Detalhes de pedido</option>
                                    <option value="dashboard_stats">Estatísticas do dashboard</option>
                                </select>
                            </div>
                            
                            <div id="queryParams" class="d-none">
                                <div class="mb-3 category-param d-none">
                                    <label for="category_id" class="form-label">ID da Categoria</label>
                                    <input type="number" class="form-control" id="category_id" name="category_id" value="1">
                                </div>
                                
                                <div class="mb-3 search-param d-none">
                                    <label for="search_term" class="form-label">Termo de Busca</label>
                                    <input type="text" class="form-control" id="search_term" name="search_term" value="miniatura">
                                </div>
                                
                                <div class="mb-3 order-param d-none">
                                    <label for="order_id" class="form-label">ID do Pedido</label>
                                    <input type="number" class="form-control" id="order_id" name="order_id" value="1">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="query_iterations" class="form-label">Iterações</label>
                                <input type="number" class="form-control" id="query_iterations" name="iterations" value="10" min="1" max="20" required>
                                <div class="form-text">Número de vezes que a consulta será executada para calcular a média.</div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-play"></i> Executar Teste
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Formulário para Teste Completo de Página -->
                    <div class="tab-pane fade" id="full-page" role="tabpanel" aria-labelledby="full-page-tab">
                        <form id="fullPageForm" class="needs-validation" novalidate>
                            <input type="hidden" name="type" value="full_page">
                            
                            <div class="mb-3">
                                <label for="full_page_url" class="form-label">URL da Página</label>
                                <select class="form-select" id="full_page_url" name="url" required>
                                    <option value="">Selecione uma página</option>
                                    <?php foreach ($pages as $key => $page): ?>
                                    <option value="<?= $page['url'] ?>"><?= $page['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Selecione a página para executar o teste completo.</div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> O teste completo executa múltiplos testes em sequência: carregamento de página, consultas ao banco de dados e uso de memória.
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-play"></i> Executar Teste Completo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Comparar Testes -->
<div class="modal fade" id="compareTestsModal" tabindex="-1" aria-labelledby="compareTestsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="compareTestsModalLabel">Comparar Testes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="compareTestsForm" action="<?= BASE_URL ?>admin/performance_test/compare_tests" method="get">
                    <p>Selecione dois ou mais testes na tabela para comparar e clique em "Comparar Selecionados".</p>
                    
                    <div id="selectedTests" class="mb-3">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> Nenhum teste selecionado
                        </div>
                    </div>
                    
                    <div class="form-text mb-3">
                        <i class="bi bi-info-circle"></i> Apenas testes do mesmo tipo podem ser comparados (ex: todos os testes de carregamento de página).
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="compareSelectedTests" disabled>
                    <i class="bi bi-bar-chart-line"></i> Comparar Selecionados
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts Específicos para a Página -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // DataTables
    $('#testsTable').DataTable({
        order: [[1, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/pt-BR.json'
        }
    });
    
    // Formulários de Teste
    const forms = [
        document.getElementById('pageLoadForm'),
        document.getElementById('apiResponseForm'),
        document.getElementById('dbQueryForm'),
        document.getElementById('fullPageForm')
    ];
    
    forms.forEach(form => {
        if (form) {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                if (form.checkValidity()) {
                    executeTest(form);
                }
                form.classList.add('was-validated');
            });
        }
    });
    
    // Mostrar/ocultar parâmetros de consulta com base no tipo
    const queryTypeSelect = document.getElementById('query_type');
    if (queryTypeSelect) {
        queryTypeSelect.addEventListener('change', function() {
            const queryParams = document.getElementById('queryParams');
            const categoryParam = document.querySelector('.category-param');
            const searchParam = document.querySelector('.search-param');
            const orderParam = document.querySelector('.order-param');
            
            // Reset
            queryParams.classList.add('d-none');
            categoryParam.classList.add('d-none');
            searchParam.classList.add('d-none');
            orderParam.classList.add('d-none');
            
            // Show relevant params
            if (this.value) {
                queryParams.classList.remove('d-none');
                
                if (this.value === 'products_category') {
                    categoryParam.classList.remove('d-none');
                } else if (this.value === 'products_search') {
                    searchParam.classList.remove('d-none');
                } else if (this.value === 'order_details') {
                    orderParam.classList.remove('d-none');
                }
            }
        });
    }
    
    // Seleção de testes para comparação
    const testCheckboxes = document.querySelectorAll('.test-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllTests');
    const selectedTestsDiv = document.getElementById('selectedTests');
    const compareButton = document.getElementById('compareSelectedTests');
    
    // Selecionar/desselecionar todos
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            testCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedTests();
        });
    }
    
    // Atualizar seleção individual
    testCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedTests);
    });
    
    // Botão para comparar testes selecionados
    if (compareButton) {
        compareButton.addEventListener('click', function() {
            const form = document.getElementById('compareTestsForm');
            form.submit();
        });
    }
    
    // Função para atualizar a lista de testes selecionados
    function updateSelectedTests() {
        const selected = [];
        testCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                selected.push(checkbox.value);
            }
        });
        
        if (selected.length === 0) {
            selectedTestsDiv.innerHTML = `
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> Nenhum teste selecionado
                </div>
            `;
            compareButton.disabled = true;
        } else {
            let html = '<ul class="list-group">';
            testCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    const row = checkbox.closest('tr');
                    const testId = row.cells[1].textContent;
                    const testType = row.cells[2].textContent.trim();
                    
                    html += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Teste #${testId}</strong>
                                <span class="text-muted"> - ${testType}</span>
                            </div>
                            <input type="hidden" name="test_ids[]" value="${checkbox.value}">
                        </li>
                    `;
                }
            });
            html += '</ul>';
            
            selectedTestsDiv.innerHTML = html;
            compareButton.disabled = false;
        }
    }
    
    // Função para executar o teste
    function executeTest(form) {
        const formData = new FormData(form);
        const params = {};
        
        for (const [key, value] of formData.entries()) {
            params[key] = value;
        }
        
        // Mostrar indicador de carregamento
        const submitBtn = form.querySelector('button[type="submit"]');
        const btnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Executando...';
        
        // Fazer a requisição AJAX
        fetch('<?= BASE_URL ?>admin/performance_test/run_test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(params)
        })
        .then(response => response.json())
        .then(data => {
            // Restaurar botão
            submitBtn.disabled = false;
            submitBtn.innerHTML = btnText;
            
            if (data.error) {
                alert('Erro ao executar o teste: ' + data.error);
            } else {
                // Fechar o modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('newTestModal'));
                modal.hide();
                
                // Redirecionar para a página de relatório
                window.location.href = '<?= BASE_URL ?>admin/performance_test/view_report/' + data.test_id;
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            submitBtn.disabled = false;
            submitBtn.innerHTML = btnText;
            alert('Erro ao executar o teste. Verifique o console para mais detalhes.');
        });
    }
});
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
