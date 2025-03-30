<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?= $title ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('admin_performance') ?>">Performance SQL</a></li>
                        <li class="breadcrumb-item active">Otimizações Recentes</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <!-- Resumo das otimizações -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Resumo de Otimizações Implementadas</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box bg-success">
                                        <span class="info-box-icon"><i class="fas fa-tachometer-alt"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Melhoria Geral de Performance</span>
                                            <span class="info-box-number"><?= $optimizations['stats']['overall_improvement'] ?>%</span>
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?= $optimizations['stats']['overall_improvement'] ?>%"></div>
                                            </div>
                                            <span class="progress-description">
                                                Redução média no tempo de execução das consultas
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box bg-info">
                                        <span class="info-box-icon"><i class="fas fa-bolt"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Melhor Otimização</span>
                                            <span class="info-box-number"><?= $optimizations['stats']['best_improvement']['impact'] ?>%</span>
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?= $optimizations['stats']['best_improvement']['impact'] ?>%"></div>
                                            </div>
                                            <span class="progress-description">
                                                <?= $optimizations['stats']['best_improvement']['technique'] ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="callout callout-info">
                                        <h5>Resumo das Melhorias:</h5>
                                        <p><?= $optimizations['stats']['summary'] ?></p>
                                        <ul class="mt-2">
                                            <li><strong>Data de implementação:</strong> <?= $optimizations['stats']['implementation_date'] ?></li>
                                            <li><strong>Modelos otimizados:</strong> <?= implode(', ', $optimizations['stats']['models_optimized']) ?></li>
                                            <li><strong>Total de métodos otimizados:</strong> <?= $optimizations['stats']['total_methods_optimized'] ?></li>
                                            <li><strong>Redução média por consulta:</strong> <?= $optimizations['stats']['average_query_reduction'] ?>%</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ProductModel Optimizations -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Otimizações no ProductModel</h3>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="accordionProductModel">
                                <?php $i = 0; foreach ($optimizations['models']['product'] as $method => $optimization): $i++; ?>
                                <div class="card">
                                    <div class="card-header" id="heading-product-<?= $i ?>">
                                        <h2 class="mb-0">
                                            <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse-product-<?= $i ?>" aria-expanded="<?= $i === 1 ? 'true' : 'false' ?>" aria-controls="collapse-product-<?= $i ?>">
                                                <strong><?= $method ?>:</strong> <?= $optimization['description'] ?> 
                                                <span class="badge badge-success"><?= $optimization['impact'] ?></span>
                                            </button>
                                        </h2>
                                    </div>

                                    <div id="collapse-product-<?= $i ?>" class="collapse <?= $i === 1 ? 'show' : '' ?>" aria-labelledby="heading-product-<?= $i ?>" data-parent="#accordionProductModel">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <p><strong>Técnica:</strong> <?= $optimization['technique'] ?></p>
                                                    <p><strong>Data de implementação:</strong> <?= $optimization['date'] ?></p>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header bg-warning">
                                                            <h5 class="card-title">Antes da Otimização</h5>
                                                        </div>
                                                        <div class="card-body">
                                                            <pre style="max-height: 300px; overflow: auto;"><code class="language-php"><?= htmlspecialchars($optimization['before_code']) ?></code></pre>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header bg-success">
                                                            <h5 class="card-title">Depois da Otimização</h5>
                                                        </div>
                                                        <div class="card-body">
                                                            <pre style="max-height: 300px; overflow: auto;"><code class="language-php"><?= htmlspecialchars($optimization['after_code']) ?></code></pre>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- CategoryModel Optimizations -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Otimizações no CategoryModel</h3>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="accordionCategoryModel">
                                <?php $i = 0; foreach ($optimizations['models']['category'] as $method => $optimization): $i++; ?>
                                <div class="card">
                                    <div class="card-header" id="heading-category-<?= $i ?>">
                                        <h2 class="mb-0">
                                            <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse-category-<?= $i ?>" aria-expanded="<?= $i === 1 ? 'true' : 'false' ?>" aria-controls="collapse-category-<?= $i ?>">
                                                <strong><?= $method ?>:</strong> <?= $optimization['description'] ?> 
                                                <span class="badge badge-success"><?= $optimization['impact'] ?></span>
                                            </button>
                                        </h2>
                                    </div>

                                    <div id="collapse-category-<?= $i ?>" class="collapse <?= $i === 1 ? 'show' : '' ?>" aria-labelledby="heading-category-<?= $i ?>" data-parent="#accordionCategoryModel">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <p><strong>Técnica:</strong> <?= $optimization['technique'] ?></p>
                                                    <p><strong>Data de implementação:</strong> <?= $optimization['date'] ?></p>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header bg-warning">
                                                            <h5 class="card-title">Antes da Otimização</h5>
                                                        </div>
                                                        <div class="card-body">
                                                            <pre style="max-height: 300px; overflow: auto;"><code class="language-php"><?= htmlspecialchars($optimization['before_code']) ?></code></pre>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <div class="card-header bg-success">
                                                            <h5 class="card-title">Depois da Otimização</h5>
                                                        </div>
                                                        <div class="card-body">
                                                            <pre style="max-height: 300px; overflow: auto;"><code class="language-php"><?= htmlspecialchars($optimization['after_code']) ?></code></pre>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gráfico de Melhorias -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Gráfico de Melhorias por Método</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="optimizationChart" style="height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Próximos Passos -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Próximos Passos</h3>
                        </div>
                        <div class="card-body">
                            <div class="callout callout-info">
                                <h5>Recomendações para futuras otimizações:</h5>
                                <ul>
                                    <li>Continuar aplicando SQL_CALC_FOUND_ROWS em consultas que precisam de paginação</li>
                                    <li>Usar UNION ALL para consolidar consultas semelhantes</li>
                                    <li>Implementar Nested Sets em todas as estruturas hierárquicas</li>
                                    <li>Verificar uso de índices FULLTEXT em todos os campos de busca</li>
                                    <li>Considerar o uso de caching para consultas frequentes após as otimizações</li>
                                    <li>Adicionar índices compostos para otimizar consultas com múltiplos filtros</li>
                                </ul>
                                
                                <div class="mt-3">
                                    <a href="<?= url('admin_performance/recommendations') ?>" class="btn btn-info">
                                        <i class="fas fa-cogs"></i> Verificar Recomendações de Otimização
                                    </a>
                                    <a href="<?= url('admin_performance/testPerformance') ?>" class="btn btn-success">
                                        <i class="fas fa-tachometer-alt"></i> Testar Performance
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dados para o gráfico
    const methods = [
        'getSubcategoriesAll', 
        'getCustomProducts', 
        'getBreadcrumb', 
        'getByCategory', 
        'search'
    ];
    
    const improvements = [
        76.56, // getSubcategoriesAll
        54.76, // getCustomProducts
        68.00, // getBreadcrumb 
        40.95, // getByCategory
        28.89  // search
    ];
    
    const colors = [
        'rgba(52, 152, 219, 0.6)', // azul
        'rgba(46, 204, 113, 0.6)', // verde
        'rgba(155, 89, 182, 0.6)', // roxo
        'rgba(230, 126, 34, 0.6)', // laranja
        'rgba(231, 76, 60, 0.6)'   // vermelho
    ];
    
    // Criar o gráfico
    const ctx = document.getElementById('optimizationChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: methods,
            datasets: [{
                label: 'Melhoria de Performance (%)',
                data: improvements,
                backgroundColor: colors,
                borderColor: colors.map(color => color.replace('0.6', '1')),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Redução de Tempo (%)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Método Otimizado'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Impacto das Otimizações SQL por Método',
                    font: {
                        size: 16
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw + '%';
                        }
                    }
                }
            }
        }
    });
});
</script>