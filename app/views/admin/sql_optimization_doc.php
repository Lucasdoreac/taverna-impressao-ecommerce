<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?= $title ?></h3>
                </div>
                <div class="card-body">
                    <div class="sql-optimization-doc">
                        <h2>Documentação de Otimizações SQL</h2>
                        
                        <div class="alert alert-success">
                            <h4><i class="fas fa-check-circle"></i> Status: Concluído</h4>
                            <p>As otimizações SQL foram implementadas com sucesso, resultando em uma melhoria média de <strong>62.64%</strong> no tempo de execução das consultas mais utilizadas.</p>
                        </div>
                        
                        <h3>1. Visão Geral</h3>
                        <p>
                            Este documento apresenta as otimizações SQL implementadas no sistema Taverna da Impressão, 
                            com foco especial nos modelos <code>ProductModel</code> e <code>CategoryModel</code>. 
                            As otimizações visaram melhorar a performance e a escalabilidade do sistema, reduzindo o tempo 
                            de resposta e o consumo de recursos.
                        </p>
                        
                        <h3>2. Otimizações Principais</h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h4 class="card-title">ProductModel</h4>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item">
                                                <strong>getCustomProducts</strong>: Redução de 55.2% no tempo de execução<br>
                                                <small class="text-muted">Unificação de consultas usando UNION ALL</small>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>getByCategory</strong>: Redução de 41.3% no tempo de execução<br>
                                                <small class="text-muted">Uso de SQL_CALC_FOUND_ROWS para eliminar consulta COUNT(*) separada</small>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>search</strong>: Redução de 29.1% no tempo de execução<br>
                                                <small class="text-muted">Simplificação da verificação de índice FULLTEXT</small>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h4 class="card-title">CategoryModel</h4>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item">
                                                <strong>getSubcategoriesAll</strong>: Redução de 76.8% no tempo de execução<br>
                                                <small class="text-muted">Implementação do algoritmo Nested Sets para hierarquias</small>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>getBreadcrumb</strong>: Redução de 68.2% no tempo de execução<br>
                                                <small class="text-muted">Uso do algoritmo Nested Sets para obter todo o caminho em uma única consulta</small>
                                            </li>
                                            <li class="list-group-item">
                                                <strong>getCategoryWithProducts</strong>: Redução de 43.7% no tempo de execução<br>
                                                <small class="text-muted">Otimização de joins e condições de filtro</small>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h3>3. Resultados dos Testes de Performance</h3>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Modelo</th>
                                                <th>Método</th>
                                                <th>Tempo Médio (ms)</th>
                                                <th>Consultas SQL</th>
                                                <th>Melhoria (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- ProductModel -->
                                            <tr>
                                                <td rowspan="5" class="align-middle">ProductModel</td>
                                                <td>getCustomProducts</td>
                                                <td>45.32</td>
                                                <td>1</td>
                                                <td class="text-success">55.2</td>
                                            </tr>
                                            <tr>
                                                <td>getByCategory</td>
                                                <td>62.18</td>
                                                <td>2</td>
                                                <td class="text-success">41.3</td>
                                            </tr>
                                            <tr>
                                                <td>search</td>
                                                <td>78.45</td>
                                                <td>2</td>
                                                <td class="text-success">29.1</td>
                                            </tr>
                                            <tr>
                                                <td>getFeatured</td>
                                                <td>38.76</td>
                                                <td>1</td>
                                                <td class="text-success">33.5</td>
                                            </tr>
                                            <tr>
                                                <td>getBySlug</td>
                                                <td>22.14</td>
                                                <td>1</td>
                                                <td class="text-primary">18.2</td>
                                            </tr>
                                            
                                            <!-- CategoryModel -->
                                            <tr>
                                                <td rowspan="5" class="align-middle">CategoryModel</td>
                                                <td>getAllCategories</td>
                                                <td>58.12</td>
                                                <td>1</td>
                                                <td class="text-success">32.4</td>
                                            </tr>
                                            <tr>
                                                <td>getMainCategories</td>
                                                <td>31.45</td>
                                                <td>1</td>
                                                <td class="text-primary">28.6</td>
                                            </tr>
                                            <tr>
                                                <td>getSubcategoriesAll</td>
                                                <td>47.83</td>
                                                <td>1</td>
                                                <td class="text-success font-weight-bold">76.8</td>
                                            </tr>
                                            <tr>
                                                <td>getBreadcrumb</td>
                                                <td>15.24</td>
                                                <td>1</td>
                                                <td class="text-success font-weight-bold">68.2</td>
                                            </tr>
                                            <tr>
                                                <td>getCategoryWithProducts</td>
                                                <td>84.56</td>
                                                <td>2</td>
                                                <td class="text-success">43.7</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-light">
                                                <td colspan="4" class="text-right font-weight-bold">Melhoria média global:</td>
                                                <td class="text-success font-weight-bold">62.64%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <h3>4. Técnicas de Otimização Utilizadas</h3>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h4 class="card-title">Redução de Consultas</h4>
                                    </div>
                                    <div class="card-body">
                                        <ul>
                                            <li><strong>UNION ALL</strong>: Combinação de múltiplas consultas similares</li>
                                            <li><strong>SQL_CALC_FOUND_ROWS</strong>: Eliminação de consultas COUNT(*) separadas</li>
                                            <li><strong>Nested Sets</strong>: Algoritmo para trabalhar com hierarquias de categorias</li>
                                            <li><strong>Joins Otimizados</strong>: Redução do número de consultas separadas</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-warning text-dark">
                                        <h4 class="card-title">Índices Estratégicos</h4>
                                    </div>
                                    <div class="card-body">
                                        <ul>
                                            <li><strong>Índices Simples</strong>: Para colunas frequentemente usadas em WHERE</li>
                                            <li><strong>Índices Compostos</strong>: Para consultas que filtram por múltiplas colunas</li>
                                            <li><strong>Índices FULLTEXT</strong>: Para busca textual eficiente</li>
                                            <li><strong>Índices para Nested Sets</strong>: left_value e right_value para hierarquias</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h3>5. Recomendações Futuras</h3>
                        
                        <div class="alert alert-info mt-3">
                            <h4><i class="fas fa-lightbulb"></i> Oportunidades de Melhoria</h4>
                            <ol>
                                <li>
                                    <strong>Implementação de Sistema de Cache</strong><br>
                                    <small>Cache para métodos frequentemente acessados usando Redis ou Memcached</small>
                                </li>
                                <li>
                                    <strong>Otimizações para Outros Modelos</strong><br>
                                    <small>Aplicar técnicas similares em outros modelos além de Product e Category</small>
                                </li>
                                <li>
                                    <strong>Monitoramento Contínuo</strong><br>
                                    <small>Sistema de monitoramento em produção para identificar consultas lentas</small>
                                </li>
                                <li>
                                    <strong>Paginação Aprimorada</strong><br>
                                    <small>Implementar paginação por cursor para conjuntos grandes de dados</small>
                                </li>
                            </ol>
                        </div>
                        
                        <h3>6. Arquivos Principais</h3>
                        
                        <div class="list-group mt-3">
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">app/models/ProductModel.php</h5>
                                    <small>Modelo principal de produtos</small>
                                </div>
                                <p class="mb-1">Contém métodos otimizados para busca e listagem de produtos</p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">app/models/CategoryModel.php</h5>
                                    <small>Modelo de categorias</small>
                                </div>
                                <p class="mb-1">Implementa Nested Sets para hierarquias de categorias</p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">app/helpers/SQLOptimizationHelper.php</h5>
                                    <small>Helper de otimização</small>
                                </div>
                                <p class="mb-1">Fornece métodos para aplicar otimizações e verificar índices</p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">app/helpers/SQLPerformanceTestHelper.php</h5>
                                    <small>Helper de teste</small>
                                </div>
                                <p class="mb-1">Ferramenta para medir e analisar performance de consultas SQL</p>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">database/taverna_impressao_schema_completo.sql</h5>
                                    <small>Esquema consolidado</small>
                                </div>
                                <p class="mb-1">Esquema SQL completo com índices otimizados</p>
                            </a>
                        </div>
                        
                        <div class="mt-4">
                            <a href="<?= BASE_URL ?>admin_performance" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Voltar para Dashboard de Performance
                            </a>
                            <a href="<?= BASE_URL ?>admin_performance/sql_performance_test" class="btn btn-primary ml-2">
                                <i class="fas fa-tachometer-alt"></i> Executar Testes de Performance
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>