<?php require_once VIEWS_PATH . '/partials/admin_header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4"><?= $title ?></h1>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Ferramentas de Análise</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Seleção de data -->
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Análise por Data</h5>
                                    <form action="<?= url('admin_performance/daily_report') ?>" method="get">
                                        <div class="form-group">
                                            <label for="date">Selecione a data:</label>
                                            <input type="date" id="date" name="date" class="form-control" value="<?= $date ?>" max="<?= date('Y-m-d') ?>">
                                        </div>
                                        <button type="submit" class="btn btn-primary mt-2">Analisar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Análise de modelo -->
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Análise de Modelo</h5>
                                    <form action="<?= url('admin_performance/analyze_model') ?>" method="get">
                                        <div class="form-group">
                                            <label for="model">Selecione o modelo:</label>
                                            <select id="model" name="model" class="form-control">
                                                <?php foreach ($models as $key => $name): ?>
                                                    <option value="<?= $key ?>"><?= $name ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary mt-2">Analisar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Teste de consulta -->
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Testar Consulta SQL</h5>
                                    <form action="<?= url('admin_performance/test_query') ?>" method="post">
                                        <div class="form-group">
                                            <label for="query">Consulta SQL:</label>
                                            <textarea id="query" name="query" class="form-control" rows="3" placeholder="SELECT * FROM products WHERE id = :id" required></textarea>
                                        </div>
                                        <div class="form-group mt-2">
                                            <label for="params">Parâmetros (JSON):</label>
                                            <input type="text" id="params" name="params" class="form-control" placeholder='{"id": 1}'>
                                        </div>
                                        <button type="submit" class="btn btn-primary mt-2">Testar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between">
                                <a href="<?= url('admin_performance/recommendations') ?>" class="btn btn-success">
                                    <i class="fa fa-list-check"></i> Ver Recomendações de Otimização
                                </a>
                                <a href="<?= url('admin_performance/optimization_guide') ?>" class="btn btn-info">
                                    <i class="fa fa-book"></i> Guia de Otimização
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($report) && !empty($report)): ?>
                <!-- Resumo do relatório -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Resumo - <?= $report['date'] ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h3 class="display-4"><?= $report['total_count'] ?></h3>
                                        <p class="text-muted">Consultas Lentas Registradas</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h3 class="display-4"><?= round($report['average_time'] * 1000, 2) ?></h3>
                                        <p class="text-muted">Tempo Médio (ms)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h3 class="display-4"><?= round($report['max_time'] * 1000, 2) ?></h3>
                                        <p class="text-muted">Tempo Máximo (ms)</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h3 class="display-4"><?= count($report['common_patterns']) ?></h3>
                                        <p class="text-muted">Padrões Identificados</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($report['common_patterns'])): ?>
                    <!-- Padrões comuns de consultas lentas -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Padrões Comuns de Consultas Lentas</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Padrão</th>
                                            <th>Ocorrências</th>
                                            <th>Tempo Total (ms)</th>
                                            <th>Tempo Médio (ms)</th>
                                            <th>Tempo Máximo (ms)</th>
                                            <th>Origem</th>
                                            <th>Sugestões</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report['common_patterns'] as $pattern): ?>
                                            <tr>
                                                <td><span class="badge bg-primary"><?= $pattern['type'] ?></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#pattern<?= md5($pattern['pattern']) ?>" aria-expanded="false">
                                                        Ver Padrão
                                                    </button>
                                                    <div class="collapse mt-2" id="pattern<?= md5($pattern['pattern']) ?>">
                                                        <div class="card card-body">
                                                            <pre class="mb-0"><?= htmlspecialchars($pattern['pattern']) ?></pre>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= $pattern['count'] ?></td>
                                                <td><?= round($pattern['total_time'] * 1000, 2) ?></td>
                                                <td><?= round($pattern['average_time'] * 1000, 2) ?></td>
                                                <td><?= round($pattern['max_time'] * 1000, 2) ?></td>
                                                <td><?= $pattern['file_origin'] ?></td>
                                                <td>
                                                    <?php if (!empty($pattern['optimization_suggestions'])): ?>
                                                        <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#suggestions<?= md5($pattern['pattern']) ?>" aria-expanded="false">
                                                            <?= count($pattern['optimization_suggestions']) ?> Sugestões
                                                        </button>
                                                        <div class="collapse mt-2" id="suggestions<?= md5($pattern['pattern']) ?>">
                                                            <div class="card card-body">
                                                                <ul class="mb-0">
                                                                    <?php foreach ($pattern['optimization_suggestions'] as $suggestion): ?>
                                                                        <li><?= $suggestion ?></li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Sem sugestões</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Exemplos de consultas lentas -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Exemplos de Consultas Lentas</h5>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="queriesAccordion">
                                <?php foreach ($report['common_patterns'] as $index => $pattern): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?= $index ?>">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                                                <span class="badge bg-primary me-2"><?= $pattern['type'] ?></span>
                                                <span class="me-3">Tempo Médio: <?= round($pattern['average_time'] * 1000, 2) ?> ms</span>
                                                <span>Ocorrências: <?= $pattern['count'] ?></span>
                                            </button>
                                        </h2>
                                        <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#queriesAccordion">
                                            <div class="accordion-body">
                                                <h6>Exemplo mais lento:</h6>
                                                <pre class="bg-light p-3 rounded"><?= htmlspecialchars($pattern['slowest_example']) ?></pre>
                                                
                                                <?php if (!empty($pattern['optimization_suggestions'])): ?>
                                                    <h6>Sugestões de Otimização:</h6>
                                                    <ul>
                                                        <?php foreach ($pattern['optimization_suggestions'] as $suggestion): ?>
                                                            <li><?= $suggestion ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                                
                                                <div class="mt-3">
                                                    <form action="<?= url('admin_performance/test_query') ?>" method="post">
                                                        <input type="hidden" name="query" value="<?= htmlspecialchars($pattern['slowest_example']) ?>">
                                                        <button type="submit" class="btn btn-sm btn-primary">Analisar esta consulta</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p>Não foram encontrados padrões de consultas lentas para a data selecionada.</p>
                        <p>Possíveis razões:</p>
                        <ul>
                            <li>Não há consultas lentas registradas nesta data</li>
                            <li>O limiar para considerar uma consulta lenta pode estar muito alto</li>
                            <li>O sistema não recebeu tráfego suficiente para gerar registros</li>
                        </ul>
                        <p>Você pode:</p>
                        <ul>
                            <li>Selecionar outra data para análise</li>
                            <li>Analisar diretamente os modelos para encontrar oportunidades de otimização</li>
                            <li>Executar testes de consultas específicas</li>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Scripts específicos desta página -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configuração da tabela de consultas
        const tables = document.querySelectorAll('.table');
        tables.forEach(table => {
            $(table).DataTable({
                responsive: true,
                order: [[3, 'desc']], // Ordenar pelo tempo total (coluna 3)
                language: {
                    url: '<?= BASE_URL ?>/public/assets/js/libs/dataTables.portuguese-brasil.json'
                }
            });
        });
    });
</script>

<?php require_once VIEWS_PATH . '/partials/admin_footer.php'; ?>