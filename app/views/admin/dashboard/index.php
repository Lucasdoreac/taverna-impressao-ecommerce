<?php
/**
 * View principal do dashboard administrativo
 * 
 * Esta view exibe o dashboard com métricas gerais, pedidos recentes,
 * status da fila de impressão e produtos mais vendidos.
 */

// Incluir header
include_once APP_PATH . '/views/admin/includes/header.php';
include_once APP_PATH . '/views/admin/includes/sidebar.php';
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><?= $title ?></h1>
        <div class="dashboard-period">
            <label for="period-selector">Período: </label>
            <select id="period-selector" class="form-select">
                <option value="7">Últimos 7 dias</option>
                <option value="30" selected>Últimos 30 dias</option>
                <option value="90">Últimos 3 meses</option>
                <option value="365">Último ano</option>
            </select>
        </div>
    </div>

    <!-- Métricas principais -->
    <div class="metrics-cards row">
        <div class="col-md-3">
            <div class="card metric-card">
                <div class="card-body">
                    <div class="metric-icon">
                        <i class="fa fa-shopping-cart"></i>
                    </div>
                    <h5 class="card-title">Pedidos</h5>
                    <h2 class="metric-value"><?= number_format($metrics['totalOrders'], 0, ',', '.') ?></h2>
                    <p class="metric-label">Total de pedidos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card">
                <div class="card-body">
                    <div class="metric-icon">
                        <i class="fa fa-dollar-sign"></i>
                    </div>
                    <h5 class="card-title">Vendas</h5>
                    <h2 class="metric-value"><?= getCurrencySymbol() ?> <?= number_format($metrics['totalSales'], 2, ',', '.') ?></h2>
                    <p class="metric-label">Volume de vendas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card">
                <div class="card-body">
                    <div class="metric-icon">
                        <i class="fa fa-user"></i>
                    </div>
                    <h5 class="card-title">Clientes</h5>
                    <h2 class="metric-value"><?= number_format($metrics['activeUsers'], 0, ',', '.') ?></h2>
                    <p class="metric-label">Clientes ativos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card metric-card">
                <div class="card-body">
                    <div class="metric-icon">
                        <i class="fa fa-print"></i>
                    </div>
                    <h5 class="card-title">Impressões</h5>
                    <h2 class="metric-value"><?= number_format($metrics['pendingPrintJobs'], 0, ',', '.') ?></h2>
                    <p class="metric-label">Trabalhos pendentes</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos e dados detalhados -->
    <div class="dashboard-widgets row mt-4">
        <!-- Gráfico de vendas -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Evolução de Vendas</h5>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" width="100%" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Filamentos -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Uso de Filamentos</h5>
                </div>
                <div class="card-body">
                    <canvas id="filamentChart" width="100%" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-widgets row mt-4">
        <!-- Pedidos recentes -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Pedidos Recentes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Data</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentOrders)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhum pedido recente encontrado</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td><a href="<?= BASE_URL ?>admin/orders/edit/<?= $order['id'] ?>">#<?= $order['id'] ?></a></td>
                                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                            <td><?= getCurrencySymbol() ?> <?= number_format($order['total'], 2, ',', '.') ?></td>
                                            <td><span class="badge bg-<?= getStatusColor($order['status']) ?>"><?= getStatusName($order['status']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <a href="<?= BASE_URL ?>admin/orders" class="btn btn-outline-primary btn-sm">Ver todos os pedidos</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila de impressão -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Fila de Impressão</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Cliente</th>
                                    <th>Impressora</th>
                                    <th>Status</th>
                                    <th>Progresso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($printQueue)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhum item na fila de impressão</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($printQueue as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                                            <td><?= htmlspecialchars($item['customer_name']) ?></td>
                                            <td><?= htmlspecialchars($item['printer_name']) ?></td>
                                            <td><span class="badge bg-<?= getPrintStatusColor($item['status']) ?>"><?= getPrintStatusName($item['status']) ?></span></td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" style="width: <?= $item['progress'] ?>%;" 
                                                         aria-valuenow="<?= $item['progress'] ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <?= $item['progress'] ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <a href="<?= BASE_URL ?>admin/print-queue" class="btn btn-outline-primary btn-sm">Gerenciar fila de impressão</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Produtos mais vendidos e status das impressoras -->
    <div class="dashboard-widgets row mt-4">
        <!-- Produtos mais vendidos -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Produtos Mais Vendidos</h5>
                    <a href="<?= BASE_URL ?>admin/produtos" class="btn btn-sm btn-primary">
                        <i class="bi bi-box-seam me-1"></i> Gerenciar Produtos
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Categoria</th>
                                    <th>Preço</th>
                                    <th>Vendidos</th>
                                    <th>Impressões</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topProducts)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhum produto vendido no período</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($topProducts as $product): ?>
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
                                            <td><?= htmlspecialchars($product['category_name'] ?? 'N/A') ?></td>
                                            <td><?= getCurrencySymbol() ?> <?= number_format($product['price'], 2, ',', '.') ?></td>
                                            <td><?= number_format($product['quantity_sold'], 0, ',', '.') ?></td>
                                            <td><?= isset($product['print_count']) ? number_format($product['print_count'], 0, ',', '.') : 'N/A' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status das impressoras -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Status das Impressoras</h5>
                </div>
                <div class="card-body">
                    <div id="printerStatus" class="printer-status-container">
                        <!-- O conteúdo será carregado via AJAX -->
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p>Carregando status das impressoras...</p>
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <a href="<?= BASE_URL ?>admin/printers" class="btn btn-outline-primary btn-sm">Gerenciar impressoras</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Widget de resumo de usuários e produtos -->
<?php include APP_PATH . '/views/admin/dashboard/widgets/user_product_summary.php'; ?>

<script>
// Carregar dados do gráfico de vendas via AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Configuração Chart.js
    Chart.defaults.font.family = "'Nunito', 'Segoe UI', 'Arial'";
    Chart.defaults.color = '#555';
    
    // Carregar gráfico de vendas
    loadSalesChart();
    
    // Carregar gráfico de filamentos
    loadFilamentChart();
    
    // Carregar status das impressoras
    loadPrinterStatus();
    
    // Atualizar gráficos quando o período for alterado
    document.getElementById('period-selector').addEventListener('change', function() {
        loadSalesChart();
        loadFilamentChart();
    });
    
    // Atualizar status das impressoras a cada 60 segundos
    setInterval(loadPrinterStatus, 60000);
});

// Função para carregar o gráfico de vendas
function loadSalesChart() {
    const periodDays = document.getElementById('period-selector').value;
    const endDate = new Date().toISOString().split('T')[0];
    const startDate = new Date(Date.now() - (periodDays * 24 * 60 * 60 * 1000)).toISOString().split('T')[0];
    
    fetch(`<?= BASE_URL ?>admin/dashboard/api/sales_chart_data?start_date=${startDate}&end_date=${endDate}&period=day`)
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
    
    // Criar o gráfico
    window.salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Volume de Vendas (R$)',
                    data: salesData,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y'
                },
                {
                    label: 'Número de Pedidos',
                    data: orderCountData,
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    tension: 0.3,
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
    });
}

// Função para carregar o gráfico de filamentos
function loadFilamentChart() {
    const periodDays = document.getElementById('period-selector').value;
    
    fetch(`<?= BASE_URL ?>admin/dashboard/api/print_queue_status`)
        .then(response => response.json())
        .then(data => {
            renderFilamentChart(data);
        })
        .catch(error => {
            console.error('Erro ao carregar dados de filamentos:', error);
        });
}

// Função para renderizar o gráfico de filamentos
function renderFilamentChart(data) {
    const ctx = document.getElementById('filamentChart').getContext('2d');
    
    // Se já existe um gráfico, destruí-lo
    if (window.filamentChart instanceof Chart) {
        window.filamentChart.destroy();
    }
    
    // Cores para diferentes status
    const statusColors = {
        'pending': '#f6c23e',
        'scheduled': '#36b9cc',
        'printing': '#4e73df',
        'paused': '#e74a3b'
    };
    
    // Preparar dados para o gráfico
    const labels = data.map(item => item.status);
    const counts = data.map(item => parseInt(item.count));
    const backgroundColors = data.map(item => statusColors[item.status] || '#858796');
    
    // Criar o gráfico
    window.filamentChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels.map(status => getPrintStatusName(status)),
            datasets: [{
                data: counts,
                backgroundColor: backgroundColors,
                hoverBackgroundColor: backgroundColors,
                hoverBorderColor: 'white',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.formattedValue;
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

// Função para carregar o status das impressoras
function loadPrinterStatus() {
    fetch(`<?= BASE_URL ?>admin/dashboard/api/printer_status`)
        .then(response => response.json())
        .then(data => {
            renderPrinterStatus(data);
        })
        .catch(error => {
            console.error('Erro ao carregar status das impressoras:', error);
        });
}

// Função para renderizar o status das impressoras
function renderPrinterStatus(data) {
    const container = document.getElementById('printerStatus');
    
    if (!data || data.length === 0) {
        container.innerHTML = '<div class="alert alert-info">Nenhuma impressora cadastrada.</div>';
        return;
    }
    
    let html = '';
    
    data.forEach(printer => {
        const statusClass = getPrinterStatusClass(printer.status);
        const hasJob = printer.current_job ? true : false;
        
        html += `
            <div class="printer-item">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">${printer.name}</h6>
                    <span class="badge ${statusClass}">${getPrinterStatusName(printer.status)}</span>
                </div>
                
                ${hasJob ? `
                <div class="printer-job">
                    <div class="small text-muted">Imprimindo: ${printer.current_job}</div>
                    <div class="progress mt-1 mb-1">
                        <div class="progress-bar" role="progressbar" style="width: ${printer.current_job_progress}%;" 
                             aria-valuenow="${printer.current_job_progress}" aria-valuemin="0" aria-valuemax="100">
                            ${printer.current_job_progress}%
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="small">${printer.current_filament_type} · ${printer.current_filament_color}</span>
                        <span class="small">${printer.current_job_time}h restantes</span>
                    </div>
                </div>
                ` : `
                <div class="printer-job">
                    <div class="small text-muted">Impressora ociosa</div>
                </div>
                `}
            </div>
            <hr>
        `;
    });
    
    // Remover o último <hr>
    if (html.endsWith('<hr>')) {
        html = html.substring(0, html.length - 4);
    }
    
    container.innerHTML = html;
}

// Funções auxiliares para formatação de status
function getPrintStatusName(status) {
    const statusMap = {
        'pending': 'Pendente',
        'scheduled': 'Agendado',
        'printing': 'Imprimindo',
        'paused': 'Pausado',
        'completed': 'Concluído',
        'failed': 'Falhou',
        'canceled': 'Cancelado'
    };
    
    return statusMap[status] || status;
}

function getPrintStatusColor(status) {
    const colorMap = {
        'pending': 'warning',
        'scheduled': 'info',
        'printing': 'primary',
        'paused': 'danger',
        'completed': 'success',
        'failed': 'danger',
        'canceled': 'secondary'
    };
    
    return colorMap[status] || 'secondary';
}

function getPrinterStatusName(status) {
    const statusMap = {
        'active': 'Ativo',
        'idle': 'Ocioso',
        'printing': 'Imprimindo',
        'maintenance': 'Manutenção',
        'offline': 'Offline',
        'error': 'Erro'
    };
    
    return statusMap[status] || status;
}

function getPrinterStatusClass(status) {
    const classMap = {
        'active': 'bg-success',
        'idle': 'bg-info',
        'printing': 'bg-primary',
        'maintenance': 'bg-warning',
        'offline': 'bg-secondary',
        'error': 'bg-danger'
    };
    
    return classMap[status] || 'bg-secondary';
}

function getStatusColor(status) {
    const colorMap = {
        'pending': 'warning',
        'processing': 'info',
        'shipped': 'primary',
        'delivered': 'success',
        'canceled': 'danger',
        'refunded': 'secondary'
    };
    
    return colorMap[status] || 'secondary';
}

function getStatusName(status) {
    const statusMap = {
        'pending': 'Pendente',
        'processing': 'Processando',
        'shipped': 'Enviado',
        'delivered': 'Entregue',
        'canceled': 'Cancelado',
        'refunded': 'Reembolsado'
    };
    
    return statusMap[status] || status;
}
</script>

<style>
.dashboard-container {
    padding: 20px;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.dashboard-period {
    display: flex;
    align-items: center;
}

.dashboard-period label {
    margin-right: 10px;
    white-space: nowrap;
}

.metrics-cards .card {
    transition: all 0.3s ease;
    border-left: 4px solid #4e73df;
}

.metrics-cards .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.metrics-cards .card:nth-child(1) {
    border-left-color: #4e73df; /* Azul */
}

.metrics-cards .card:nth-child(2) {
    border-left-color: #1cc88a; /* Verde */
}

.metrics-cards .card:nth-child(3) {
    border-left-color: #36b9cc; /* Ciano */
}

.metrics-cards .card:nth-child(4) {
    border-left-color: #f6c23e; /* Amarelo */
}

.metric-card {
    height: 100%;
}

.metric-icon {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 2rem;
    opacity: 0.3;
}

.metric-value {
    margin-bottom: 0;
    font-weight: 700;
}

.metric-label {
    font-size: 0.8rem;
    color: #888;
    margin-bottom: 0;
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

.printer-status-container {
    max-height: 350px;
    overflow-y: auto;
}

.printer-item {
    padding: 10px 0;
}

/* Ajustes para telas menores */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .dashboard-period {
        margin-top: 15px;
    }
    
    .metrics-cards .col-md-3 {
        margin-bottom: 15px;
    }
}
</style>

<?php
// Incluir footer
include_once APP_PATH . '/views/admin/includes/footer.php';
?>
