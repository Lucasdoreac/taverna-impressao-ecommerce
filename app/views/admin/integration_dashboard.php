<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dashboard de Integração</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/dashboard">Home</a></li>
                        <li class="breadcrumb-item active">Integração Pedidos-Impressão</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Indicadores de Status -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $stats['successful_integrations'] ?></h3>
                            <p>Integrações Bem-sucedidas</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <a href="<?= BASE_URL ?>admin/integration/logs?status=success" class="small-box-footer">
                            Mais informações <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $stats['pending_jobs'] ?></h3>
                            <p>Jobs em Andamento</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <a href="<?= BASE_URL ?>admin/print-jobs?status=in_progress" class="small-box-footer">
                            Mais informações <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= $stats['integration_errors'] ?></h3>
                            <p>Erros de Integração</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <a href="<?= BASE_URL ?>admin/integration/logs?status=error" class="small-box-footer">
                            Mais informações <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $stats['orphaned_jobs'] ?></h3>
                            <p>Jobs Órfãos</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-unlink"></i>
                        </div>
                        <a href="<?= BASE_URL ?>admin/integration/orphaned" class="small-box-footer">
                            Mais informações <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Gráfico de Atividade -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Atividade de Integração (Últimos 7 dias)</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="integrationActivityChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Jobs Órfãos e Fluxos Incompletos -->
            <div class="row">
                <!-- Jobs Órfãos -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Jobs Potencialmente Órfãos</h3>
                            <div class="card-tools">
                                <span class="badge badge-danger"><?= count($orphanedJobs) ?> Encontrados</span>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Pedido</th>
                                        <th>Criado em</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orphanedJobs)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhum job órfão encontrado.</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($orphanedJobs as $job): ?>
                                        <tr>
                                            <td><?= $job['id'] ?></td>
                                            <td>
                                                <?php if ($job['order_id']): ?>
                                                <a href="<?= BASE_URL ?>admin/orders/view/<?= $job['order_id'] ?>">#<?= $job['order_id'] ?></a>
                                                <?php else: ?>
                                                <span class="badge badge-warning">Sem pedido</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($job['created_at'])) ?></td>
                                            <td>
                                                <span class="badge badge-<?= getStatusBadgeClass($job['status']) ?>"><?= $job['status'] ?></span>
                                            </td>
                                            <td>
                                                <a href="<?= BASE_URL ?>admin/print-jobs/view/<?= $job['id'] ?>" class="btn btn-xs btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>admin/print-jobs/fix/<?= $job['id'] ?>" class="btn btn-xs btn-warning">
                                                    <i class="fas fa-wrench"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            <a href="<?= BASE_URL ?>admin/integration/orphaned" class="btn btn-sm btn-default">Ver Todos</a>
                        </div>
                    </div>
                </div>
                
                <!-- Fluxos Incompletos -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Fluxos de Integração Incompletos</h3>
                            <div class="card-tools">
                                <span class="badge badge-warning"><?= count($incompleteFlows) ?> Encontrados</span>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Pedido</th>
                                        <th>Cliente</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($incompleteFlows)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhum fluxo incompleto encontrado.</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($incompleteFlows as $order): ?>
                                        <tr>
                                            <td><a href="<?= BASE_URL ?>admin/orders/view/<?= $order['id'] ?>">#<?= $order['id'] ?></a></td>
                                            <td><?= $order['customer_name'] ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                            <td>
                                                <span class="badge badge-<?= getStatusBadgeClass($order['status']) ?>"><?= $order['status'] ?></span>
                                            </td>
                                            <td>
                                                <a href="<?= BASE_URL ?>admin/orders/view/<?= $order['id'] ?>" class="btn btn-xs btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>admin/integration/fix-order/<?= $order['id'] ?>" class="btn btn-xs btn-warning">
                                                    <i class="fas fa-wrench"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            <a href="<?= BASE_URL ?>admin/integration/incomplete" class="btn btn-sm btn-default">Ver Todos</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Eventos Recentes de Integração -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Eventos Recentes de Integração</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Evento</th>
                                        <th>Pedido</th>
                                        <th>Job</th>
                                        <th>Status</th>
                                        <th>Detalhes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentEvents as $event): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i:s', strtotime($event['created_at'])) ?></td>
                                        <td><?= $event['event'] ?></td>
                                        <td>
                                            <?php if ($event['order_id']): ?>
                                            <a href="<?= BASE_URL ?>admin/orders/view/<?= $event['order_id'] ?>">#<?= $event['order_id'] ?></a>
                                            <?php else: ?>
                                            -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($event['print_job_id']): ?>
                                            <a href="<?= BASE_URL ?>admin/print-jobs/view/<?= $event['print_job_id'] ?>">#<?= $event['print_job_id'] ?></a>
                                            <?php else: ?>
                                            -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= getEventStatusBadgeClass($event['status']) ?>"><?= $event['status'] ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($event['details'])): ?>
                                            <button type="button" class="btn btn-xs btn-info view-details" 
                                                    data-toggle="modal" data-target="#eventDetailsModal" 
                                                    data-details='<?= htmlspecialchars($event['details']) ?>'>
                                                <i class="fas fa-search"></i> Ver
                                            </button>
                                            <?php else: ?>
                                            -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            <a href="<?= BASE_URL ?>admin/integration/logs" class="btn btn-sm btn-default">Ver Todos os Eventos</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Executar Testes de Integração -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Testes de Integração</h3>
                        </div>
                        <div class="card-body">
                            <p class="card-text">
                                Execute testes de integração para verificar se a comunicação entre o sistema de pedidos e a fila de impressão 3D está funcionando corretamente.
                            </p>
                            <a href="<?= BASE_URL ?>app/tests/order_to_print_queue_test.php" target="_blank" class="btn btn-primary">
                                <i class="fas fa-vial mr-1"></i> Executar Testes de Integração
                            </a>
                            <a href="<?= BASE_URL ?>admin/integration/repair" class="btn btn-warning ml-2">
                                <i class="fas fa-wrench mr-1"></i> Reparar Problemas de Integração
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal para exibir detalhes do evento -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1" role="dialog" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventDetailsModalLabel">Detalhes do Evento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <pre id="eventDetailsContent" class="bg-light p-3" style="max-height: 400px; overflow: auto;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para o dashboard -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de atividade de integração
    var activityCtx = document.getElementById('integrationActivityChart').getContext('2d');
    var activityChart = new Chart(activityCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartData['labels']) ?>,
            datasets: [
                {
                    label: 'Sucessos',
                    data: <?= json_encode($chartData['success']) ?>,
                    borderColor: 'rgba(40, 167, 69, 1)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.1
                },
                {
                    label: 'Avisos',
                    data: <?= json_encode($chartData['warning']) ?>,
                    borderColor: 'rgba(255, 193, 7, 1)',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.1
                },
                {
                    label: 'Erros',
                    data: <?= json_encode($chartData['error']) ?>,
                    borderColor: 'rgba(220, 53, 69, 1)',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
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
                    grid: {
                        drawOnChartArea: false
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Modal para exibir detalhes de eventos
    document.querySelectorAll('.view-details').forEach(function(button) {
        button.addEventListener('click', function() {
            var details = JSON.parse(this.dataset.details);
            var formattedDetails = JSON.stringify(details, null, 2);
            document.getElementById('eventDetailsContent').textContent = formattedDetails;
        });
    });
});
</script>

<?php 
/**
 * Função auxiliar para obter a classe de badge com base no status
 * 
 * @param string $status Status do job/ordem
 * @return string Classe CSS para o badge
 */
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'completed':
        case 'delivered':
            return 'success';
        case 'processing':
        case 'preparing':
        case 'printing':
        case 'in_production':
            return 'primary';
        case 'pending':
        case 'waiting':
        case 'in_queue':
            return 'warning';
        case 'cancelled':
        case 'failed':
            return 'danger';
        default:
            return 'secondary';
    }
}

/**
 * Função auxiliar para obter a classe de badge com base no status do evento
 * 
 * @param string $status Status do evento
 * @return string Classe CSS para o badge
 */
function getEventStatusBadgeClass($status) {
    switch ($status) {
        case 'success':
            return 'success';
        case 'warning':
            return 'warning';
        case 'error':
            return 'danger';
        case 'info':
        default:
            return 'info';
    }
}
?>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
