<?php
/**
 * View administrativa para métricas detalhadas de preferências de notificação
 * 
 * Esta página exibe métricas e análises detalhadas sobre as preferências de notificação
 * dos usuários, incluindo gráficos, tendências e recomendações.
 */

// Configurar o título da página
$title = isset($title) ? $title : 'Métricas de Preferências de Notificação';
?>

<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- Menu Lateral -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <?php require_once VIEWS_PATH . '/admin/partials/sidebar.php'; ?>
        </div>
        
        <!-- Conteúdo Principal -->
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Métricas de Preferências de Notificação</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="downloadReportBtn">
                            <i class="fas fa-download"></i> Exportar Relatório
                        </button>
                        <a href="<?= BASE_URL ?>admin/notificacoes/preferencias" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Voltar para Administração
                        </a>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-calendar"></i> Período
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <li><a class="dropdown-item" href="#" data-period="7">Últimos 7 dias</a></li>
                            <li><a class="dropdown-item" href="#" data-period="30">Últimos 30 dias</a></li>
                            <li><a class="dropdown-item" href="#" data-period="90">Últimos 90 dias</a></li>
                            <li><a class="dropdown-item" href="#" data-period="365">Último ano</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-period="0">Todo o período</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Cartões de Métricas Resumidas -->
            <div class="row mb-4">
                <div class="col-md-3 mb-4">
                    <div class="card border-primary h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title text-primary">Total de Usuários</h5>
                            <p class="display-4"><?= isset($metrics['totalUsers']) ? number_format($metrics['totalUsers']) : 0 ?></p>
                            <p class="card-text text-muted">com preferências configuradas</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card border-success h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title text-success">Taxa de Adesão</h5>
                            <p class="display-4">
                                <?= isset($metrics['adoptionRate']) ? number_format($metrics['adoptionRate'], 1) : 0 ?>%
                            </p>
                            <p class="card-text text-muted">de usuários ativos</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card border-info h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title text-info">Canal Mais Popular</h5>
                            <p class="h4">
                                <?= isset($metrics['mostPopularChannel']) ? $metrics['mostPopularChannel'] : 'N/A' ?>
                            </p>
                            <p class="card-text text-muted">
                                <?= isset($metrics['mostPopularChannelPercentage']) ? number_format($metrics['mostPopularChannelPercentage'], 1) . '% de usuários' : '' ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card border-warning h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title text-warning">Frequência Preferida</h5>
                            <p class="h4">
                                <?php 
                                if (isset($metrics['mostPopularFrequency'])) {
                                    switch ($metrics['mostPopularFrequency']) {
                                        case 'realtime':
                                            echo 'Tempo Real';
                                            break;
                                        case 'daily':
                                            echo 'Diário';
                                            break;
                                        case 'weekly':
                                            echo 'Semanal';
                                            break;
                                        default:
                                            echo $metrics['mostPopularFrequency'];
                                    }
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </p>
                            <p class="card-text text-muted">
                                <?= isset($metrics['mostPopularFrequencyPercentage']) ? number_format($metrics['mostPopularFrequencyPercentage'], 1) . '% de preferências' : '' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gráficos de Análise -->
            <div class="row mb-4">
                <!-- Evolução ao Longo do Tempo -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title">Evolução de Preferências</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="timeSeriesChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Distribuição de Canais x Tipos -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title">Matriz de Preferências</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="heatmapChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Análises Detalhadas -->
            <div class="row mb-4">
                <!-- Tabela de Preferências Populares -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title">Combinações Mais Populares</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Canal</th>
                                            <th>Frequência</th>
                                            <th>Usuários</th>
                                            <th>%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (isset($metrics['popularCombinations'])): ?>
                                            <?php foreach ($metrics['popularCombinations'] as $combo): ?>
                                                <tr>
                                                    <td><?= $combo['type_name'] ?></td>
                                                    <td><?= $combo['channel_name'] ?></td>
                                                    <td>
                                                        <?php
                                                        switch ($combo['frequency']) {
                                                            case 'realtime':
                                                                echo 'Tempo Real';
                                                                break;
                                                            case 'daily':
                                                                echo 'Diário';
                                                                break;
                                                            case 'weekly':
                                                                echo 'Semanal';
                                                                break;
                                                            default:
                                                                echo $combo['frequency'];
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?= $combo['user_count'] ?></td>
                                                    <td><?= number_format(($combo['user_count'] / $metrics['totalUsers']) * 100, 1) ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Dados não disponíveis</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Distribuição de Frequências por Canal -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title">Frequências por Canal</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="frequencyByChannelChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Análise de Correlação e Insights -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Insights e Recomendações</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h6 class="alert-heading"><i class="fas fa-lightbulb"></i> Insights:</h6>
                                <ul>
                                    <?php if (isset($metrics['insights']) && is_array($metrics['insights'])): ?>
                                        <?php foreach ($metrics['insights'] as $insight): ?>
                                            <li><?= $insight ?></li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li>A maioria dos usuários prefere notificações por e-mail (75%)</li>
                                        <li>Notificações de pedidos têm a maior taxa de ativação (92%)</li>
                                        <li>Apenas 34% dos usuários personalizam suas preferências</li>
                                        <li>Usuários frequentes tendem a preferir notificações em tempo real</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="alert alert-success">
                                <h6 class="alert-heading"><i class="fas fa-chart-line"></i> Recomendações:</h6>
                                <ul>
                                    <?php if (isset($metrics['recommendations']) && is_array($metrics['recommendations'])): ?>
                                        <?php foreach ($metrics['recommendations'] as $recommendation): ?>
                                            <li><?= $recommendation ?></li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li>Promover as opções de personalização durante o checkout</li>
                                        <li>Adicionar notificação via aplicativo móvel como novo canal</li>
                                        <li>Simplificar a interface de preferências para aumentar adesão</li>
                                        <li>Enviar lembrete para usuários sem personalização definida</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pesquisa de Usuários -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Pesquisa de Preferências por Usuário</h5>
                </div>
                <div class="card-body">
                    <form id="userSearchForm" class="row g-3">
                        <div class="col-md-4">
                            <label for="userId" class="form-label">ID do Usuário</label>
                            <input type="text" class="form-control" id="userId" placeholder="Pesquisar por ID">
                        </div>
                        <div class="col-md-4">
                            <label for="userEmail" class="form-label">E-mail do Usuário</label>
                            <input type="email" class="form-control" id="userEmail" placeholder="Pesquisar por e-mail">
                        </div>
                        <div class="col-md-4">
                            <label for="userName" class="form-label">Nome do Usuário</label>
                            <input type="text" class="form-control" id="userName" placeholder="Pesquisar por nome">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Pesquisar
                            </button>
                        </div>
                    </form>
                    
                    <div id="userSearchResults" class="mt-4" style="display: none;">
                        <h6>Resultados da Pesquisa</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>E-mail</th>
                                        <th>Preferências Configuradas</th>
                                        <th>Última Atualização</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="userSearchResultsBody">
                                    <!-- Resultados serão inseridos aqui via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalhes do Usuário -->
<div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userDetailsModalLabel">Preferências de Notificação - Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Informações do Usuário</h6>
                                <p><strong>ID:</strong> <span id="userDetailId">-</span></p>
                                <p><strong>Nome:</strong> <span id="userDetailName">-</span></p>
                                <p><strong>E-mail:</strong> <span id="userDetailEmail">-</span></p>
                                <p><strong>Cadastro:</strong> <span id="userDetailCreated">-</span></p>
                                <p><strong>Última Configuração:</strong> <span id="userDetailLastUpdate">-</span></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Resumo de Preferências</h6>
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <div class="d-inline-block rounded-circle bg-light p-3">
                                            <h3 id="userDetailActiveCount">-</h3>
                                        </div>
                                        <p class="mt-2">Notificações Ativas</p>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="d-inline-block rounded-circle bg-light p-3">
                                            <h3 id="userDetailInactiveCount">-</h3>
                                        </div>
                                        <p class="mt-2">Notificações Inativas</p>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="d-inline-block rounded-circle bg-light p-3">
                                            <h3 id="userDetailChannelCount">-</h3>
                                        </div>
                                        <p class="mt-2">Canais Utilizados</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-3 text-muted">Preferências Detalhadas</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Tipo de Notificação</th>
                                                <th>Canal</th>
                                                <th>Status</th>
                                                <th>Frequência</th>
                                                <th>Última Atualização</th>
                                            </tr>
                                        </thead>
                                        <tbody id="userDetailPreferencesBody">
                                            <!-- Preferências serão inseridas aqui via JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <a href="#" id="userDetailEditLink" class="btn btn-primary">Editar Preferências</a>
            </div>
        </div>
    </div>
</div>

<!-- Incluir script para JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dados para os gráficos - normalmente estes seriam fornecidos pelo PHP
    // Estamos usando dados fictícios para este exemplo
    const timeSeriesData = <?= isset($metrics['timeSeries']) ? json_encode($metrics['timeSeries']) : '[]' ?>;
    const heatmapData = <?= isset($metrics['heatmapData']) ? json_encode($metrics['heatmapData']) : '[]' ?>;
    const frequencyByChannelData = <?= isset($metrics['frequencyByChannel']) ? json_encode($metrics['frequencyByChannel']) : '[]' ?>;
    
    // Inicializar gráficos se estiver disponível
    if (typeof Chart !== 'undefined') {
        // Gráfico de evolução temporal
        if (timeSeriesData.length > 0) {
            const labels = timeSeriesData.map(item => item.date);
            const datasets = [];
            
            // Preparar datasets com base nas categorias de dados
            if (timeSeriesData[0].email !== undefined) {
                datasets.push({
                    label: 'E-mail',
                    data: timeSeriesData.map(item => item.email),
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: true
                });
            }
            
            if (timeSeriesData[0].sms !== undefined) {
                datasets.push({
                    label: 'SMS',
                    data: timeSeriesData.map(item => item.sms),
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: true
                });
            }
            
            if (timeSeriesData[0].site !== undefined) {
                datasets.push({
                    label: 'No Site',
                    data: timeSeriesData.map(item => item.site),
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true
                });
            }
            
            // Criar gráfico
            const timeSeriesCtx = document.getElementById('timeSeriesChart').getContext('2d');
            new Chart(timeSeriesCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Evolução de Preferências ao Longo do Tempo'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        },
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Data'
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Número de Usuários'
                            }
                        }
                    }
                }
            });
        } else {
            // Dados de exemplo para o gráfico de evolução
            const exampleLabels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'];
            const exampleTimeSeriesCtx = document.getElementById('timeSeriesChart').getContext('2d');
            new Chart(exampleTimeSeriesCtx, {
                type: 'line',
                data: {
                    labels: exampleLabels,
                    datasets: [
                        {
                            label: 'E-mail',
                            data: [120, 132, 145, 162, 184, 210],
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            fill: true
                        },
                        {
                            label: 'SMS',
                            data: [85, 92, 98, 105, 113, 126],
                            borderColor: 'rgba(255, 99, 132, 1)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            fill: true
                        },
                        {
                            label: 'No Site',
                            data: [98, 110, 124, 145, 168, 195],
                            borderColor: 'rgba(75, 192, 192, 1)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Evolução de Preferências ao Longo do Tempo (Exemplo)'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        },
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Mês'
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Número de Usuários'
                            }
                        }
                    }
                }
            });
        }
        
        // Dados de exemplo para o mapa de calor
        const exampleTypes = ['Pedidos', 'Promoções', 'Fila de Impressão', 'Novos Produtos', 'Dicas'];
        const exampleChannels = ['E-mail', 'SMS', 'No Site'];
        const exampleHeatmapData = [
            [95, 65, 78], // Pedidos
            [72, 48, 63], // Promoções
            [87, 42, 74], // Fila de Impressão
            [65, 30, 58], // Novos Produtos
            [52, 25, 45]  // Dicas
        ];
        
        // Criar mapa de calor
        const heatmapCtx = document.getElementById('heatmapChart').getContext('2d');
        new Chart(heatmapCtx, {
            type: 'heatmap',
            data: {
                labels: exampleTypes,
                datasets: exampleChannels.map((channel, i) => ({
                    label: channel,
                    data: exampleTypes.map((_, j) => ({
                        x: j,
                        y: i,
                        v: exampleHeatmapData[j][i]
                    }))
                }))
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Matriz de Preferências por Tipo e Canal (Exemplo)'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const v = context.dataset.data[context.dataIndex].v;
                                return `${context.dataset.label} - ${exampleTypes[context.dataIndex]}: ${v} usuários`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'category',
                        labels: exampleTypes,
                        title: {
                            display: true,
                            text: 'Tipo de Notificação'
                        }
                    },
                    y: {
                        type: 'category',
                        labels: exampleChannels,
                        title: {
                            display: true,
                            text: 'Canal'
                        }
                    }
                }
            }
        });
        
        // Dados de exemplo para o gráfico de frequências por canal
        const frequencyByChannelCtx = document.getElementById('frequencyByChannelChart').getContext('2d');
        new Chart(frequencyByChannelCtx, {
            type: 'bar',
            data: {
                labels: ['E-mail', 'SMS', 'No Site'],
                datasets: [
                    {
                        label: 'Tempo Real',
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        data: [42, 68, 82]
                    },
                    {
                        label: 'Diário',
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        data: [35, 21, 14]
                    },
                    {
                        label: 'Semanal',
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        data: [23, 11, 4]
                    }
                ]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribuição de Frequências por Canal (Exemplo)'
                    },
                },
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Canal'
                        }
                    },
                    y: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Número de Usuários'
                        }
                    }
                }
            }
        });
    }
    
    // Manipulador para o formulário de pesquisa de usuário
    const userSearchForm = document.getElementById('userSearchForm');
    if (userSearchForm) {
        userSearchForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Normalmente, aqui você faria uma solicitação AJAX para buscar os resultados
            // Para este exemplo, vamos apenas mostrar alguns dados fictícios
            
            // Mostrar resultados
            document.getElementById('userSearchResults').style.display = 'block';
            
            // Limpar resultados anteriores
            const resultsBody = document.getElementById('userSearchResultsBody');
            resultsBody.innerHTML = '';
            
            // Adicionar alguns resultados de exemplo
            const exampleUsers = [
                { id: 1, name: 'João Silva', email: 'joao@example.com', preferences: 8, lastUpdate: '2025-03-15' },
                { id: 2, name: 'Maria Oliveira', email: 'maria@example.com', preferences: 5, lastUpdate: '2025-03-10' },
                { id: 3, name: 'Carlos Santos', email: 'carlos@example.com', preferences: 10, lastUpdate: '2025-03-25' }
            ];
            
            exampleUsers.forEach(user => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.name}</td>
                    <td>${user.email}</td>
                    <td>${user.preferences}</td>
                    <td>${user.lastUpdate}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary view-user-btn" data-id="${user.id}">
                            <i class="fas fa-eye"></i> Ver
                        </button>
                    </td>
                `;
                resultsBody.appendChild(row);
            });
            
            // Adicionar manipuladores para botões de detalhes
            document.querySelectorAll('.view-user-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    showUserDetails(userId);
                });
            });
        });
    }
    
    // Função para mostrar detalhes do usuário
    function showUserDetails(userId) {
        // Simular dados de usuário - normalmente, você buscaria esses dados do servidor
        const userDetails = {
            id: userId,
            name: userId == 1 ? 'João Silva' : (userId == 2 ? 'Maria Oliveira' : 'Carlos Santos'),
            email: userId == 1 ? 'joao@example.com' : (userId == 2 ? 'maria@example.com' : 'carlos@example.com'),
            created: '2024-10-15',
            lastUpdate: '2025-03-20',
            activeCount: 8,
            inactiveCount: 4,
            channelCount: 3,
            preferences: [
                { type: 'Pedidos', channel: 'E-mail', status: 'Ativo', frequency: 'Tempo Real', lastUpdate: '2025-03-20' },
                { type: 'Pedidos', channel: 'SMS', status: 'Ativo', frequency: 'Tempo Real', lastUpdate: '2025-03-20' },
                { type: 'Promoções', channel: 'E-mail', status: 'Ativo', frequency: 'Diário', lastUpdate: '2025-03-15' },
                { type: 'Promoções', channel: 'SMS', status: 'Inativo', frequency: 'N/A', lastUpdate: '2025-03-15' },
                { type: 'Fila de Impressão', channel: 'E-mail', status: 'Ativo', frequency: 'Tempo Real', lastUpdate: '2025-03-20' },
                { type: 'Fila de Impressão', channel: 'No Site', status: 'Ativo', frequency: 'Tempo Real', lastUpdate: '2025-03-20' },
                { type: 'Novos Produtos', channel: 'E-mail', status: 'Ativo', frequency: 'Semanal', lastUpdate: '2025-03-10' },
                { type: 'Novos Produtos', channel: 'SMS', status: 'Inativo', frequency: 'N/A', lastUpdate: '2025-03-10' },
                { type: 'Novos Produtos', channel: 'No Site', status: 'Inativo', frequency: 'N/A', lastUpdate: '2025-03-10' },
                { type: 'Dicas', channel: 'E-mail', status: 'Ativo', frequency: 'Semanal', lastUpdate: '2025-03-10' },
                { type: 'Dicas', channel: 'SMS', status: 'Inativo', frequency: 'N/A', lastUpdate: '2025-03-10' },
                { type: 'Dicas', channel: 'No Site', status: 'Ativo', frequency: 'Diário', lastUpdate: '2025-03-10' }
            ]
        };
        
        // Preencher detalhes do usuário no modal
        document.getElementById('userDetailId').textContent = userDetails.id;
        document.getElementById('userDetailName').textContent = userDetails.name;
        document.getElementById('userDetailEmail').textContent = userDetails.email;
        document.getElementById('userDetailCreated').textContent = userDetails.created;
        document.getElementById('userDetailLastUpdate').textContent = userDetails.lastUpdate;
        document.getElementById('userDetailActiveCount').textContent = userDetails.activeCount;
        document.getElementById('userDetailInactiveCount').textContent = userDetails.inactiveCount;
        document.getElementById('userDetailChannelCount').textContent = userDetails.channelCount;
        document.getElementById('userDetailEditLink').href = `<?= BASE_URL ?>admin/notificacoes/preferencias/editar-usuario/${userDetails.id}`;
        
        // Preencher tabela de preferências
        const preferencesBody = document.getElementById('userDetailPreferencesBody');
        preferencesBody.innerHTML = '';
        
        userDetails.preferences.forEach(pref => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${pref.type}</td>
                <td>${pref.channel}</td>
                <td>
                    <span class="badge ${pref.status === 'Ativo' ? 'bg-success' : 'bg-secondary'}">
                        ${pref.status}
                    </span>
                </td>
                <td>${pref.frequency}</td>
                <td>${pref.lastUpdate}</td>
            `;
            preferencesBody.appendChild(row);
        });
        
        // Exibir modal
        const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
        modal.show();
    }
    
    // Manipulador para botão de exportação
    document.getElementById('downloadReportBtn').addEventListener('click', function() {
        alert('Relatório será gerado e baixado como PDF. Esta funcionalidade estaria implementada em um ambiente de produção.');
    });
    
    // Manipuladores para dropdown de período
    document.querySelectorAll('.dropdown-item[data-period]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const period = this.getAttribute('data-period');
            
            // Atualizar texto do botão dropdown
            const dropdownButton = document.getElementById('dropdownMenuButton');
            dropdownButton.innerHTML = `<i class="fas fa-calendar"></i> ${this.textContent}`;
            
            // Aqui você normalmente faria uma solicitação AJAX para atualizar os dados com base no período selecionado
            alert(`Período alterado para: ${this.textContent}. Os dados seriam atualizados via AJAX em um ambiente de produção.`);
        });
    });
});
</script>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
