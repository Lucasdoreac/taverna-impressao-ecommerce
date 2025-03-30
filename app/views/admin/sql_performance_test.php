<?php include_once(VIEWS_PATH . '/partials/admin_header.php'); ?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Testes de Performance de Otimizações SQL</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success) && $success): ?>
                            <div class="alert alert-success">
                                <strong>Sucesso!</strong> Os testes foram executados com êxito.
                            </div>
                        <?php elseif (isset($error)): ?>
                            <div class="alert alert-danger">
                                <strong>Erro!</strong> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5>Iniciar Testes de Performance</h5>
                                    </div>
                                    <div class="card-body">
                                        <form action="<?php echo BASE_URL; ?>admin_performance/run_performance_tests" method="post" class="form-horizontal">
                                            <div class="form-group row">
                                                <label class="col-md-3 col-form-label">Modelos a Testar</label>
                                                <div class="col-md-9">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="models[]" value="product" id="model-product" checked>
                                                        <label class="form-check-label" for="model-product">
                                                            ProductModel
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="models[]" value="category" id="model-category" checked>
                                                        <label class="form-check-label" for="model-category">
                                                            CategoryModel
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-md-3 col-form-label">Iterações por Teste</label>
                                                <div class="col-md-3">
                                                    <select name="iterations" class="form-control">
                                                        <option value="5">5 iterações</option>
                                                        <option value="10" selected>10 iterações</option>
                                                        <option value="20">20 iterações</option>
                                                        <option value="50">50 iterações</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-md-3 col-form-label">Testes de Carga</label>
                                                <div class="col-md-9">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="include_load_test" id="include-load-test">
                                                        <label class="form-check-label" for="include-load-test">
                                                            Incluir testes de carga simulando acessos múltiplos
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-md-3 col-form-label">Opções Avançadas</label>
                                                <div class="col-md-9">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="save_results" id="save-results" checked>
                                                        <label class="form-check-label" for="save-results">
                                                            Salvar resultados para comparação futura
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="use_baseline" id="use-baseline">
                                                        <label class="form-check-label" for="use-baseline">
                                                            Comparar com baseline salvo (se disponível)
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group row mt-4">
                                                <div class="col-md-9 offset-md-3">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-play"></i> Executar Testes
                                                    </button>
                                                    <button type="reset" class="btn btn-secondary">
                                                        <i class="fas fa-redo"></i> Redefinir
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (isset($testResults)): ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header bg-info text-white">
                                            <h5>Resultados dos Testes</h5>
                                        </div>
                                        <div class="card-body">
                                            <?php echo $testResults; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5>Testes Anteriores</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (isset($previousTests) && !empty($previousTests)): ?>
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Data/Hora</th>
                                                        <th>Modelos Testados</th>
                                                        <th>Iterações</th>
                                                        <th>Resultados Médios</th>
                                                        <th>Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($previousTests as $test): ?>
                                                    <tr>
                                                        <td><?php echo $test['timestamp']; ?></td>
                                                        <td><?php echo $test['models']; ?></td>
                                                        <td><?php echo $test['iterations']; ?></td>
                                                        <td><?php echo $test['avg_results']; ?></td>
                                                        <td>
                                                            <a href="<?php echo BASE_URL; ?>admin_performance/view_test_result/<?php echo $test['id']; ?>" class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i> Ver
                                                            </a>
                                                            <?php if (isset($test['can_compare']) && $test['can_compare']): ?>
                                                            <a href="<?php echo BASE_URL; ?>admin_performance/compare_tests/<?php echo $test['id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-balance-scale"></i> Comparar
                                                            </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php else: ?>
                                            <p>Nenhum teste anterior encontrado. Execute um teste para começar a coletar dados.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5>Informações Sobre as Otimizações SQL</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info">
                                            <h5><i class="fas fa-info-circle"></i> Sobre as Otimizações</h5>
                                            <p>As otimizações SQL implementadas incluem:</p>
                                            <ul>
                                                <li><strong>SQL_CALC_FOUND_ROWS</strong> - Para eliminar consultas COUNT(*) separadas</li>
                                                <li><strong>UNION ALL</strong> - Para substituir múltiplas consultas separadas</li>
                                                <li><strong>Algoritmo Nested Sets</strong> - Para consultas hierárquicas eficientes</li>
                                                <li><strong>Índices Otimizados</strong> - Para melhorar tempos de resposta</li>
                                            </ul>
                                            
                                            <p>Para mais detalhes sobre as otimizações específicas, consulte a seção 
                                            <a href="<?php echo BASE_URL; ?>admin_performance/recent_optimizations">Otimizações SQL Recentes</a>.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script para gráficos de performance -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se container do gráfico existe
    const chartContainer = document.getElementById('performance-chart-container');
    if (!chartContainer) return;
    
    // Verificar se há resultados de testes
    if (!chartContainer.dataset.results) return;
    
    try {
        // Tentar carregar e renderizar gráficos se disponíveis
        const results = JSON.parse(chartContainer.dataset.results);
        
        // Implementar visualização com Chart.js ou outra biblioteca
        // Este é apenas um placeholder para a implementação real
        chartContainer.innerHTML = '<div class="alert alert-info">Os gráficos serão carregados quando disponíveis.</div>';
    } catch (e) {
        console.error('Erro ao renderizar gráficos:', e);
    }
});
</script>

<?php include_once(VIEWS_PATH . '/partials/admin_footer.php'); ?>
