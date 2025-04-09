<?php
/**
 * View para dashboard administrativo de pagamentos
 * 
 * Exibe estatísticas, transações recentes e webhooks recentes
 * 
 * @package     App\Views\Admin\Payment
 * @version     1.0.0
 * @author      Taverna da Impressão
 */
?>

<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?= htmlspecialchars($pageTitle) ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin">Dashboard</a></li>
                        <li class="breadcrumb-item active">Pagamentos</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-check"></i> Sucesso!</h5>
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Erro!</h5>
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <!-- Widgets de estatísticas -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= number_format($stats['total_transactions'], 0, ',', '.') ?></h3>
                            <p>Transações Totais</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <a href="<?= BASE_URL ?>admin/pagamentos/transacoes" class="small-box-footer">
                            Mais informações <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= number_format($stats['total_approved'], 0, ',', '.') ?></h3>
                            <p>Transações Aprovadas</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <a href="#" class="small-box-footer">
                            <span>Taxa: <?= number_format(($stats['total_transactions'] > 0 ? ($stats['total_approved'] / $stats['total_transactions'] * 100) : 0), 1, ',', '.') ?>%</span>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= number_format($stats['total_pending'], 0, ',', '.') ?></h3>
                            <p>Transações Pendentes</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <a href="#" class="small-box-footer">
                            <span>Taxa: <?= number_format(($stats['total_transactions'] > 0 ? ($stats['total_pending'] / $stats['total_transactions'] * 100) : 0), 1, ',', '.') ?>%</span>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= number_format($stats['total_failed'], 0, ',', '.') ?></h3>
                            <p>Transações Falhas</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <a href="#" class="small-box-footer">
                            <span>Taxa: <?= number_format(($stats['total_transactions'] > 0 ? ($stats['total_failed'] / $stats['total_transactions'] * 100) : 0), 1, ',', '.') ?>%</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <!-- Gráfico de transações -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Transações por Dia (Últimos 30 dias)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="transactionChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Transações recentes -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Transações Recentes</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Pedido</th>
                                            <th>Cliente</th>
                                            <th>Gateway</th>
                                            <th>Valor</th>
                                            <th>Status</th>
                                            <th>Data</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recentTransactions)): ?>
                                            <?php foreach ($recentTransactions as $transaction): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($transaction['id']) ?></td>
                                                    <td>
                                                        <a href="<?= BASE_URL ?>admin/pedidos/detalhes/<?= htmlspecialchars($transaction['order_id']) ?>">
                                                            <?= htmlspecialchars($transaction['order_number'] ?? 'N/A') ?>
                                                        </a>
                                                    </td>
                                                    <td><?= htmlspecialchars($transaction['customer_name'] ?? 'N/A') ?></td>
                                                    <td><?= htmlspecialchars(ucfirst($transaction['gateway_name'])) ?></td>
                                                    <td>R$ <?= number_format($transaction['amount'], 2, ',', '.') ?></td>
                                                    <td>
                                                        <?php
                                                        $statusBadge = 'secondary';
                                                        switch (strtolower($transaction['status'])) {
                                                            case 'approved':
                                                            case 'authorized':
                                                                $statusBadge = 'success';
                                                                break;
                                                            case 'pending':
                                                            case 'in_process':
                                                                $statusBadge = 'warning';
                                                                break;
                                                            case 'rejected':
                                                            case 'failed':
                                                            case 'cancelled':
                                                                $statusBadge = 'danger';
                                                                break;
                                                            case 'refunded':
                                                                $statusBadge = 'info';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge badge-<?= $statusBadge ?>">
                                                            <?= htmlspecialchars(ucfirst($transaction['status'])) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('d/m/Y H:i', strtotime($transaction['created_at'])) ?></td>
                                                    <td>
                                                        <a href="<?= BASE_URL ?>admin/pagamentos/transacao/<?= htmlspecialchars($transaction['id']) ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">Nenhuma transação encontrada</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <a href="<?= BASE_URL ?>admin/pagamentos/transacoes" class="btn btn-primary">Ver Todas as Transações</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- Estatísticas por gateway -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Transações por Gateway</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="chart-responsive">
                                <canvas id="gatewayPieChart" height="200"></canvas>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <a href="<?= BASE_URL ?>admin/pagamentos/gateways" class="btn btn-primary">Gerenciar Gateways</a>
                        </div>
                    </div>
                    
                    <!-- Webhooks recentes -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Webhooks Recentes</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php if (!empty($recentWebhooks)): ?>
                                    <?php foreach ($recentWebhooks as $webhook): ?>
                                        <li class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge badge-<?= $webhook['success'] ? 'success' : 'danger' ?> mr-2">
                                                        <?= $webhook['success'] ? 'Sucesso' : 'Falha' ?>
                                                    </span>
                                                    <strong><?= htmlspecialchars(ucfirst($webhook['gateway'])) ?></strong>
                                                </div>
                                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($webhook['created_at'])) ?></small>
                                            </div>
                                            <div class="mt-1">
                                                <small>Evento: <span class="text-primary"><?= htmlspecialchars($webhook['event_type']) ?></span></small>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-center">Nenhum webhook recebido</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="card-footer text-center">
                            <a href="<?= BASE_URL ?>admin/pagamentos/webhooks" class="btn btn-primary">Ver Todos os Webhooks</a>
                        </div>
                    </div>
                    
                    <!-- Links rápidos -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Links Rápidos</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group">
                                <a href="<?= BASE_URL ?>admin/pagamentos/configuracoes" class="list-group-item list-group-item-action">
                                    <i class="fas fa-cog mr-2"></i> Configurações de Pagamento
                                </a>
                                <a href="<?= BASE_URL ?>admin/pagamentos/gateways" class="list-group-item list-group-item-action">
                                    <i class="fas fa-credit-card mr-2"></i> Gerenciar Gateways
                                </a>
                                <a href="<?= BASE_URL ?>admin/pagamentos/webhooks" class="list-group-item list-group-item-action">
                                    <i class="fas fa-exchange-alt mr-2"></i> Logs de Webhooks
                                </a>
                                <a href="<?= BASE_URL ?>admin/relatorios/financeiro" class="list-group-item list-group-item-action">
                                    <i class="fas fa-chart-line mr-2"></i> Relatórios Financeiros
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos para esta página -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuração do gráfico de transações por dia
    var transactionChartCanvas = document.getElementById('transactionChart').getContext('2d');
    
    // Preparar dados para o gráfico
    var transactionDates = [];
    var transactionCounts = [];
    var transactionAmounts = [];
    
    <?php
    // Criar array com datas dos últimos 30 dias
    $dates = [];
    $today = new DateTime();
    for ($i = 29; $i >= 0; $i--) {
        $date = clone $today;
        $date->modify("-$i days");
        $dateStr = $date->format('Y-m-d');
        $dates[] = $dateStr;
    }
    ?>
    
    // Preencher arrays de datas e valores
    <?php foreach ($dates as $date): ?>
        transactionDates.push('<?= date('d/m', strtotime($date)) ?>');
        <?php 
        $count = $stats['by_day'][$date]['total'] ?? 0;
        $amount = $stats['by_day'][$date]['amount'] ?? 0;
        ?>
        transactionCounts.push(<?= $count ?>);
        transactionAmounts.push(<?= $amount ?>);
    <?php endforeach; ?>
    
    // Criação do gráfico
    var transactionChart = new Chart(transactionChartCanvas, {
        type: 'line',
        data: {
            labels: transactionDates,
            datasets: [
                {
                    label: 'Quantidade',
                    data: transactionCounts,
                    borderColor: 'rgba(60,141,188,1)',
                    backgroundColor: 'rgba(60,141,188,0.2)',
                    pointRadius: 3,
                    pointColor: 'rgba(60,141,188,1)',
                    pointStrokeColor: '#c1c7d1',
                    pointHighlightFill: '#fff',
                    pointHighlightStroke: 'rgba(60,141,188,1)',
                    yAxisID: 'y-axis-1'
                },
                {
                    label: 'Valor Total (R$)',
                    data: transactionAmounts,
                    borderColor: 'rgba(210, 214, 222, 1)',
                    backgroundColor: 'rgba(210, 214, 222, 0.2)',
                    pointRadius: 3,
                    pointColor: 'rgba(210, 214, 222, 1)',
                    pointStrokeColor: '#c1c7d1',
                    pointHighlightFill: '#fff',
                    pointHighlightStroke: 'rgba(220,220,220,1)',
                    yAxisID: 'y-axis-2'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                xAxes: [{
                    gridLines: {
                        display: false,
                    }
                }],
                yAxes: [
                    {
                        id: 'y-axis-1',
                        position: 'left',
                        ticks: {
                            beginAtZero: true
                        },
                        gridLines: {
                            display: true,
                        }
                    },
                    {
                        id: 'y-axis-2',
                        position: 'right',
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        },
                        gridLines: {
                            display: false,
                        }
                    }
                ]
            }
        }
    });
    
    // Configuração do gráfico de pizza para gateways
    var gatewayPieChartCanvas = document.getElementById('gatewayPieChart').getContext('2d');
    
    // Preparar dados para o gráfico
    var gatewayLabels = [];
    var gatewayData = [];
    var backgroundColors = [
        '#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de',
        '#6f42c1', '#fd7e14', '#20c997', '#6c757d'
    ];
    
    <?php 
    $counter = 0;
    foreach ($stats['by_gateway'] as $gateway => $data): 
    ?>
        gatewayLabels.push('<?= ucfirst(htmlspecialchars($gateway)) ?>');
        gatewayData.push(<?= $data['total'] ?>);
    <?php 
    $counter++;
    endforeach; 
    ?>
    
    // Criação do gráfico
    var gatewayPieChart = new Chart(gatewayPieChartCanvas, {
        type: 'doughnut',
        data: {
            labels: gatewayLabels,
            datasets: [
                {
                    data: gatewayData,
                    backgroundColor: backgroundColors.slice(0, gatewayLabels.length)
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'bottom'
            }
        }
    });
});
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
