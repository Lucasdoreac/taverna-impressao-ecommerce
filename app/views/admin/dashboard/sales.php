<?php
/**
 * View de métricas de vendas do dashboard administrativo
 * 
 * Esta view exibe métricas detalhadas de vendas, gráficos de evolução,
 * vendas por categoria e produtos mais vendidos.
 */

// Incluir header
include_once APP_PATH . '/views/admin/includes/header.php';
include_once APP_PATH . '/views/admin/includes/sidebar.php';
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><?= $title ?></h1>
        
        <div class="date-range-selector">
            <form id="date-range-form" class="form-inline">
                <div class="input-group">
                    <span class="input-group-text">Período</span>
                    <select name="period" id="period-selector" class="form-select">
                        <option value="day" <?= $period == 'day' ? 'selected' : '' ?>>Diário</option>
                        <option value="week" <?= $period == 'week' ? 'selected' : '' ?>>Semanal</option>
                        <option value="month" <?= $period == 'month' ? 'selected' : '' ?>>Mensal</option>
                        <option value="year" <?= $period == 'year' ? 'selected' : '' ?>>Anual</option>
                    </select>
                </div>
                
                <div class="date-inputs">
                    <div class="input-group">
                        <span class="input-group-text">De</span>
                        <input type="date" id="start-date" name="start_date" class="form-control" value="<?= $startDate ?>">
                    </div>
                    
                    <div class="input-group">
                        <span class="input-group-text">Até</span>
                        <input type="date" id="end-date" name="end_date" class="form-control" value="<?= $endDate ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Métricas principais -->
    <div class="metrics-summary row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="metric-icon">
                        <i class="fa fa-dollar-sign"></i>
                    </div>
                    <h5 class="card-title">Volume de Vendas</h5>
                    <h2 class="metric-value"><?= getCurrencySymbol() ?> <?= number_format($salesMetrics['totalSales'], 2, ',', '.') ?></h2>
                    
                    <?php if ($salesMetrics['growthPercent'] > 0): ?>
                        <p class="metric-change positive">
                            <i class="fa fa-arrow-up"></i> <?= number_format($salesMetrics['growthPercent'], 1, ',', '.') ?>%
                            <span class="text-muted">vs. período anterior</span>
                        </p>
                    <?php elseif ($salesMetrics['growthPercent'] < 0): ?>
                        <p class="metric-change negative">
                            <i class="fa fa-arrow-down"></i> <?= number_format(abs($salesMetrics['growthPercent']), 1, ',', '.') ?>%
                            <span class="text-muted">vs. período anterior</span>
                        </p>
                    <?php else: ?>
                        <p class="metric-change neutral">
                            <i class="fa fa-minus"></i> 0%
                            <span class="text-muted">vs. período anterior</span>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="metric-icon">
                        <i class="fa fa-shopping-cart"></i>
                    </div>
                    <h5 class="card-title">Pedidos</h5>
                    <h2 class="metric-value"><?= number_format($salesMetrics['totalOrders'], 0, ',', '.') ?></h2>
                    <p class="metric-label">Total de pedidos no período</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="metric-icon">
                        <i class="fa fa-tags"></i>
                    </div>
                    <h5 class="card-title">Ticket Médio</h5>
                    <h2 class="metric-value"><?= getCurrencySymbol() ?> <?= number_format($salesMetrics['avgOrderValue'], 2, ',', '.') ?></h2>
                    <p class="metric-label">Valor médio por pedido</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="metric-icon">
                        <i class="fa fa-calendar"></i>
                    </div>
                    <h5 class="card-title">Período</h5>
                    <h2 class="metric-value"><?= date('d/m/Y', strtotime($startDate)) ?> - <?= date('d/m/Y', strtotime($endDate)) ?></h2>
                    <p class="metric-label"><?= dateDiffDescription($startDate, $endDate) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico principal de vendas -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title">Evolução de Vendas</h5>
            <div class="card-tools">
                <div class="btn-group chart-type-selector" role="group">
                    <button type="button" class="btn btn-sm btn-outline-secondary active" data-chart-type="line">Linha</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-chart-type="bar">Barras</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-chart-type="area">Área</button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="salesChart" width="100%" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Vendas por categoria e produtos mais vendidos -->
    <div class="row mt-4">
        <!-- Vendas por categoria -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Vendas por Categoria</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="categoryChart" width="100%" height="300"></canvas>
                    </div>
                    
                    <div class="category-list mt-3">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Categoria</th>
                                    <th class="text-end">Vendas</th>
                                    <th class="text-end">Pedidos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($salesByCategory)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Nenhuma venda no período</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($salesByCategory as $category): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($category['category_name']) ?></td>
                                            <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($category['total_sales'], 2, ',', '.') ?></td>
                                            <td class="text-end"><?= number_format($category['order_count'], 0, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Produtos mais vendidos -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Produtos Mais Vendidos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th class="text-end">Qtd. Vendida</th>
                                    <th class="text-end">Total Vendas</th>
                                    <th class="text-end">Pedidos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($salesByProduct)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Nenhum produto vendido no período</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($salesByProduct as $product): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($product['image'])): ?>
                                                        <img src="<?= BASE_URL ?>uploads/products/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" class="product-thumbnail me-2">
                                                    <?php else: ?>
                                                        <div class="product-thumbnail-placeholder me-2"></div>
                                                    <?php endif; ?>
                                                    <span><?= htmlspecialchars($product['product_name']) ?></span>
                                                </div>
                                            </td>
                                            <td class="text-end"><?= number_format($product['quantity_sold'], 0, ',', '.') ?></td>
                                            <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($product['total_sales'], 2, ',', '.') ?></td>
                                            <td class="text-end"><?= number_format($product['order_count'], 0, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Relatório de vendas diárias -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title">Relatório de Vendas Diárias</h5>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-outline-primary" id="export-sales-report">
                    <i class="fa fa-download"></i> Exportar
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="daily-sales-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th class="text-end">Pedidos</th>
                            <th class="text-end">Vendas</th>
                            <th class="text-end">Ticket Médio</th>
                            <th class="text-end">Itens Vendidos</th>
                        </tr>
                    </thead>
                    <tbody id="daily-sales-body">
                        <!-- Dados carregados via JavaScript -->
                        <tr>
                            <td colspan="5" class="text-center">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                                Carregando dados...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <th>Total</th>
                            <th class="text-end" id="total-orders">-</th>
                            <th class="text-end" id="total-sales">-</th>
                            <th class="text-end" id="avg-ticket">-</th>
                            <th class="text-end" id="total-items">-</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuração Chart.js
    Chart.defaults.font.family = "'Nunito', 'Segoe UI', 'Arial'";
    Chart.defaults.color = '#555';
    
    // Carregar dados do gráfico de vendas
    loadSalesChartData();
    
    // Carregar dados do gráfico de categorias
    loadCategoryChartData();
    
    // Carregar relatório de vendas diárias
    loadDailySalesReport();
    
    // Alternar tipo de gráfico
    document.querySelectorAll('.chart-type-selector button').forEach(button => {
        button.addEventListener('click', function() {
            // Remover a classe 'active' de todos os botões
            document.querySelectorAll('.chart-type-selector button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Adicionar a classe 'active' ao botão clicado
            this.classList.add('active');
            
            // Atualizar o tipo de gráfico
            const chartType = this.getAttribute('data-chart-type');
            updateChartType(chartType);
        });
    });
    
    // Exportar relatório de vendas
    document.getElementById('export-sales-report').addEventListener('click', function() {
        exportSalesReport();
    });
});

// Função para carregar dados do gráfico de vendas
function loadSalesChartData() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    const period = document.getElementById('period-selector').value;
    
    fetch(`<?= BASE_URL ?>admin/dashboard/api/sales_chart_data?start_date=${startDate}&end_date=${endDate}&period=${period}`)
        .then(response => response.json())
        .then(data => {
            renderSalesChart(data);
        })
        .catch(error => {
            console.error('Erro ao carregar dados de vendas:', error);
        });
}

// Função para renderizar o gráfico de vendas
function renderSalesChart(data) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    // Se já existe um gráfico, destruí-lo
    if (window.salesChart instanceof Chart) {
        window.salesChart.destroy();
    }
    
    // Preparar os dados para o gráfico
    const labels = data.map(item => item.date_group);
    const salesData = data.map(item => parseFloat(item.total_sales));
    const orderCountData = data.map(item => parseInt(item.order_count));
    
    // Determinar o tipo de gráfico selecionado
    let chartType = 'line';
    const activeButton = document.querySelector('.chart-type-selector button.active');
    if (activeButton) {
        chartType = activeButton.getAttribute('data-chart-type');
    }
    
    // Configurar o tipo de gráfico
    const chartConfig = {
        type: chartType === 'area' ? 'line' : chartType,
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Volume de Vendas (R$)',
                    data: salesData,
                    borderColor: '#4e73df',
                    backgroundColor: chartType === 'area' ? 'rgba(78, 115, 223, 0.1)' : 'rgba(78, 115, 223, 0.8)',
                    borderWidth: 2,
                    fill: chartType === 'area',
                    tension: chartType === 'line' || chartType === 'area' ? 0.3 : 0,
                    yAxisID: 'y'
                },
                {
                    label: 'Número de Pedidos',
                    data: orderCountData,
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.8)',
                    borderWidth: 2,
                    fill: false,
                    tension: chartType === 'line' || chartType === 'area' ? 0.3 : 0,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                if (context.datasetIndex === 0) {
                                    label += '<?= getCurrencySymbol() ?> ' + context.parsed.y.toFixed(2).replace('.', ',');
                                } else {
                                    label += context.parsed.y;
                                }
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        drawBorder: false,
                        display: false
                    }
                },
                y: {
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Volume de Vendas (R$)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '<?= getCurrencySymbol() ?> ' + value.toFixed(0);
                        }
                    }
                },
                y1: {
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Número de Pedidos'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    };
    
    // Criar o gráfico
    window.salesChart = new Chart(ctx, chartConfig);
}

// Função para atualizar o tipo de gráfico
function updateChartType(chartType) {
    if (!window.salesChart) return;
    
    const isArea = chartType === 'area';
    
    // Atualizar tipo de gráfico
    window.salesChart.config.type = isArea ? 'line' : chartType;
    
    // Atualizar configurações de datasets
    window.salesChart.data.datasets.forEach((dataset, index) => {
        if (index === 0) { // Apenas para o dataset de vendas
            dataset.fill = isArea;
            dataset.backgroundColor = isArea ? 'rgba(78, 115, 223, 0.1)' : 'rgba(78, 115, 223, 0.8)';
        }
        
        dataset.tension = (chartType === 'line' || chartType === 'area') ? 0.3 : 0;
    });
    
    // Atualizar o gráfico
    window.salesChart.update();
}

// Função para carregar dados do gráfico de categorias
function loadCategoryChartData() {
    const categoryData = <?= json_encode($salesByCategory) ?>;
    renderCategoryChart(categoryData);
}

// Função para renderizar o gráfico de categorias
function renderCategoryChart(data) {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    
    // Se já existe um gráfico, destruí-lo
    if (window.categoryChart instanceof Chart) {
        window.categoryChart.destroy();
    }
    
    // Preparar os dados para o gráfico
    const labels = data.map(item => item.category_name);
    const salesData = data.map(item => parseFloat(item.total_sales));
    
    // Gerar cores para categorias
    const backgroundColors = generateColors(data.length);
    
    // Criar o gráfico
    window.categoryChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: salesData,
                backgroundColor: backgroundColors,
                hoverBackgroundColor: backgroundColors,
                hoverBorderColor: 'white',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = '<?= getCurrencySymbol() ?> ' + parseFloat(context.raw).toFixed(2).replace('.', ',');
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.raw / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Função para gerar cores para categorias
function generateColors(count) {
    const baseColors = [
        '#4e73df', // Azul
        '#1cc88a', // Verde
        '#36b9cc', // Ciano
        '#f6c23e', // Amarelo
        '#e74a3b', // Vermelho
        '#6f42c1', // Roxo
        '#fd7e14', // Laranja
        '#20c9a6', // Turquesa
        '#5a5c69', // Cinza
        '#858796'  // Cinza escuro
    ];
    
    // Se temos menos categorias que cores base, retornar as cores necessárias
    if (count <= baseColors.length) {
        return baseColors.slice(0, count);
    }
    
    // Caso contrário, gerar cores adicionais
    const colors = [...baseColors];
    
    for (let i = baseColors.length; i < count; i++) {
        // Gerar cor aleatória
        const r = Math.floor(Math.random() * 200) + 55;
        const g = Math.floor(Math.random() * 200) + 55;
        const b = Math.floor(Math.random() * 200) + 55;
        colors.push(`rgb(${r}, ${g}, ${b})`);
    }
    
    return colors;
}

// Função para carregar relatório de vendas diárias
function loadDailySalesReport() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    
    fetch(`<?= BASE_URL ?>admin/reports/daily-sales?start_date=${startDate}&end_date=${endDate}&format=json`)
        .then(response => response.json())
        .then(data => {
            renderDailySalesReport(data);
        })
        .catch(error => {
            console.error('Erro ao carregar relatório de vendas diárias:', error);
            document.getElementById('daily-sales-body').innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        Erro ao carregar relatório. Tente novamente.
                    </td>
                </tr>
            `;
        });
}

// Função para renderizar relatório de vendas diárias
function renderDailySalesReport(data) {
    const tableBody = document.getElementById('daily-sales-body');
    
    // Limpar tabela
    tableBody.innerHTML = '';
    
    if (data.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center">
                    Nenhuma venda encontrada no período selecionado.
                </td>
            </tr>
        `;
        return;
    }
    
    // Variáveis para totais
    let totalOrders = 0;
    let totalSales = 0;
    let totalItems = 0;
    
    // Preencher tabela com dados
    data.forEach(item => {
        const row = document.createElement('tr');
        
        // Calcular ticket médio
        const avgTicket = item.order_count > 0 ? item.total_sales / item.order_count : 0;
        
        // Atualizar totais
        totalOrders += item.order_count;
        totalSales += item.total_sales;
        totalItems += item.item_count;
        
        row.innerHTML = `
            <td>${formatDate(item.date)}</td>
            <td class="text-end">${item.order_count}</td>
            <td class="text-end">${formatCurrency(item.total_sales)}</td>
            <td class="text-end">${formatCurrency(avgTicket)}</td>
            <td class="text-end">${item.item_count}</td>
        `;
        
        tableBody.appendChild(row);
    });
    
    // Atualizar rodapé com totais
    document.getElementById('total-orders').textContent = totalOrders;
    document.getElementById('total-sales').textContent = formatCurrency(totalSales);
    document.getElementById('avg-ticket').textContent = formatCurrency(totalOrders > 0 ? totalSales / totalOrders : 0);
    document.getElementById('total-items').textContent = totalItems;
}

// Função para exportar relatório de vendas
function exportSalesReport() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    
    window.location.href = `<?= BASE_URL ?>admin/reports/daily-sales?start_date=${startDate}&end_date=${endDate}&format=csv`;
}

// Função auxiliar para formatar data
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR');
}

// Função auxiliar para formatar moeda
function formatCurrency(value) {
    return '<?= getCurrencySymbol() ?> ' + value.toFixed(2).replace('.', ',');
}
</script>

<style>
.dashboard-container {
    padding: 20px;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.date-range-selector {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.date-range-selector form {
    display: flex;
    align-items: center;
    gap: 10px;
}

.date-inputs {
    display: flex;
    align-items: center;
    gap: 10px;
}

.metrics-summary .card {
    height: 100%;
    transition: all 0.3s ease;
    border-left: 4px solid #4e73df;
}

.metrics-summary .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.metrics-summary .card:nth-child(1) {
    border-left-color: #4e73df; /* Azul */
}

.metrics-summary .card:nth-child(2) {
    border-left-color: #1cc88a; /* Verde */
}

.metrics-summary .card:nth-child(3) {
    border-left-color: #36b9cc; /* Ciano */
}

.metrics-summary .card:nth-child(4) {
    border-left-color: #f6c23e; /* Amarelo */
}

.metric-icon {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 2rem;
    opacity: 0.3;
}

.metric-value {
    margin-bottom: 5px;
    font-weight: 700;
}

.metric-label {
    font-size: 0.8rem;
    color: #888;
    margin-bottom: 0;
}

.metric-change {
    font-size: 0.9rem;
    margin-bottom: 0;
}

.metric-change.positive {
    color: #1cc88a;
}

.metric-change.negative {
    color: #e74a3b;
}

.metric-change.neutral {
    color: #858796;
}

.metric-change .text-muted {
    font-size: 0.8rem;
    color: #888 !important;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

.product-thumbnail, .product-thumbnail-placeholder {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}

.product-thumbnail-placeholder {
    background-color: #e9ecef;
}

.category-list {
    max-height: 250px;
    overflow-y: auto;
}

/* Ajustes para telas menores */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .date-range-selector {
        margin-top: 15px;
        width: 100%;
    }
    
    .date-range-selector form {
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
    }
    
    .date-inputs {
        flex-direction: column;
        width: 100%;
    }
    
    .input-group {
        width: 100%;
        margin-bottom: 10px;
    }
    
    .metrics-summary .col-md-3 {
        margin-bottom: 15px;
    }
}
</style>

<?php
/**
 * Função auxiliar para calcular a descrição do intervalo de datas
 */
function dateDiffDescription($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $diff = $start->diff($end);
    
    $days = $diff->days;
    
    if ($days == 0) {
        return 'Hoje';
    } elseif ($days == 1) {
        return '1 dia';
    } elseif ($days <= 7) {
        return $days . ' dias';
    } elseif ($days <= 31) {
        $weeks = floor($days / 7);
        return $weeks . ($weeks == 1 ? ' semana' : ' semanas');
    } elseif ($days <= 365) {
        $months = $diff->m;
        return $months . ($months == 1 ? ' mês' : ' meses');
    } else {
        $years = $diff->y;
        return $years . ($years == 1 ? ' ano' : ' anos');
    }
}

// Incluir footer
include_once APP_PATH . '/views/admin/includes/footer.php';
?>
