<?php require_once APP_ROOT . '/views/partials/header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4 text-center">Meus Trabalhos de Impressão 3D</h1>
            
            <!-- Notificações -->
            <?php if (!empty($notifications)): ?>
                <div class="card mb-4 border-left-info shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-bell mr-2"></i> Notificações
                        </h6>
                        <?php if (count($notifications) > 1): ?>
                            <button class="btn btn-sm btn-outline-primary mark-all-read">Marcar todas como lidas</button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach($notifications as $notification): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><?php echo $notification['title']; ?></h5>
                                        <small><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo $notification['message']; ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <?php if (!empty($notification['order_id'])): ?>
                                            <a href="<?php echo BASE_URL; ?>orders/view/<?php echo $notification['order_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                Ver Pedido
                                            </a>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-secondary mark-read" data-id="<?php echo $notification['id']; ?>">
                                            Marcar como lida
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Trabalhos de Impressão em Andamento -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-spinner mr-2"></i> Trabalhos em Andamento
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    $activeJobs = array_filter($queueItems, function($item) {
                        return in_array($item['status'], ['pending', 'scheduled', 'printing', 'paused']);
                    });
                    ?>
                    
                    <?php if (empty($activeJobs)): ?>
                        <div class="alert alert-info">
                            Você não possui trabalhos de impressão em andamento no momento.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Status</th>
                                        <th>Pedido</th>
                                        <th>Data de Adição</th>
                                        <th>Tempo Estimado</th>
                                        <th>Progresso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($activeJobs as $item): ?>
                                        <tr>
                                            <td><?php echo $item['product_name']; ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php 
                                                    if ($item['status'] === 'pending') echo 'badge-light';
                                                    elseif ($item['status'] === 'scheduled') echo 'badge-info';
                                                    elseif ($item['status'] === 'printing') echo 'badge-primary';
                                                    elseif ($item['status'] === 'paused') echo 'badge-warning';
                                                    ?>
                                                ">
                                                    <?php 
                                                    if ($item['status'] === 'pending') echo 'Pendente';
                                                    elseif ($item['status'] === 'scheduled') echo 'Agendado';
                                                    elseif ($item['status'] === 'printing') echo 'Imprimindo';
                                                    elseif ($item['status'] === 'paused') echo 'Pausado';
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>orders/view/<?php echo $item['order_id']; ?>" class="btn btn-sm btn-outline-dark">
                                                    #<?php echo $item['order_number']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></td>
                                            <td><?php echo $item['estimated_print_time_hours']; ?> horas</td>
                                            <td>
                                                <?php
                                                $progress = 0;
                                                $progressClass = 'bg-info';
                                                
                                                if ($item['status'] === 'pending') {
                                                    $progress = 5;
                                                    $progressClass = 'bg-light';
                                                } elseif ($item['status'] === 'scheduled') {
                                                    $progress = 20;
                                                    $progressClass = 'bg-info';
                                                } elseif ($item['status'] === 'printing') {
                                                    // Se tiver horário de início, calcular progresso baseado no tempo
                                                    if (!empty($item['print_start_date'])) {
                                                        $startTime = strtotime($item['print_start_date']);
                                                        $currentTime = time();
                                                        $elapsedHours = ($currentTime - $startTime) / 3600;
                                                        $totalHours = $item['estimated_print_time_hours'];
                                                        
                                                        $progress = min(95, round(($elapsedHours / $totalHours) * 100));
                                                    } else {
                                                        $progress = 50; // Valor padrão se não tiver data de início
                                                    }
                                                    $progressClass = 'bg-primary';
                                                } elseif ($item['status'] === 'paused') {
                                                    // Se pausado, manter o progresso atual
                                                    $progress = 50;
                                                    $progressClass = 'bg-warning';
                                                }
                                                ?>
                                                <div class="progress">
                                                    <div class="progress-bar <?php echo $progressClass; ?>" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $progress; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Histórico de Trabalhos -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history mr-2"></i> Histórico de Trabalhos
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    $completedJobs = array_filter($queueItems, function($item) {
                        return in_array($item['status'], ['completed', 'failed', 'canceled']);
                    });
                    ?>
                    
                    <?php if (empty($completedJobs)): ?>
                        <div class="alert alert-info">
                            Você ainda não possui histórico de trabalhos de impressão concluídos.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="historyTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Status</th>
                                        <th>Pedido</th>
                                        <th>Data de Adição</th>
                                        <th>Data de Conclusão</th>
                                        <th>Tempo Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($completedJobs as $item): ?>
                                        <tr>
                                            <td><?php echo $item['product_name']; ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php 
                                                    if ($item['status'] === 'completed') echo 'badge-success';
                                                    elseif ($item['status'] === 'failed') echo 'badge-danger';
                                                    elseif ($item['status'] === 'canceled') echo 'badge-secondary';
                                                    ?>
                                                ">
                                                    <?php 
                                                    if ($item['status'] === 'completed') echo 'Concluído';
                                                    elseif ($item['status'] === 'failed') echo 'Falha';
                                                    elseif ($item['status'] === 'canceled') echo 'Cancelado';
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>orders/view/<?php echo $item['order_id']; ?>" class="btn btn-sm btn-outline-dark">
                                                    #<?php echo $item['order_number']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></td>
                                            <td>
                                                <?php if (!empty($item['print_finish_date'])): ?>
                                                    <?php echo date('d/m/Y', strtotime($item['print_finish_date'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if (!empty($item['print_start_date']) && !empty($item['print_finish_date'])) {
                                                    $startTime = strtotime($item['print_start_date']);
                                                    $endTime = strtotime($item['print_finish_date']);
                                                    $totalHours = round(($endTime - $startTime) / 3600, 1);
                                                    echo $totalHours . ' horas';
                                                } else {
                                                    echo '<span class="text-muted">-</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Como Funciona -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-question-circle mr-2"></i> Como Funciona
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-4">
                            <div class="circle-icon mb-3">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h5>Pedido Recebido</h5>
                            <p>Seu pedido é recebido e adicionado à nossa fila de impressão.</p>
                        </div>
                        <div class="col-md-3 text-center mb-4">
                            <div class="circle-icon mb-3">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <h5>Preparação</h5>
                            <p>Preparamos o modelo e configuramos a impressora com o filamento escolhido.</p>
                        </div>
                        <div class="col-md-3 text-center mb-4">
                            <div class="circle-icon mb-3">
                                <i class="fas fa-print"></i>
                            </div>
                            <h5>Impressão</h5>
                            <p>Seu modelo é impresso com o máximo de cuidado e qualidade.</p>
                        </div>
                        <div class="col-md-3 text-center mb-4">
                            <div class="circle-icon mb-3">
                                <i class="fas fa-truck"></i>
                            </div>
                            <h5>Envio</h5>
                            <p>Após acabamento final, seu item é embalado e enviado para você.</p>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle mr-2"></i>
                        Você receberá notificações sobre o progresso do seu trabalho de impressão. Para mais informações, entre em contato com nosso suporte.
                    </div>
                </div>
            </div>
            
            <!-- Links Úteis -->
            <div class="row">
                <div class="col-md-6">
                    <a href="<?php echo BASE_URL; ?>orders" class="btn btn-primary btn-block mb-4">
                        <i class="fas fa-shopping-bag mr-2"></i> Meus Pedidos
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="<?php echo BASE_URL; ?>products" class="btn btn-success btn-block mb-4">
                        <i class="fas fa-cube mr-2"></i> Catálogo de Produtos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilos específicos para esta página -->
<style>
    .circle-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #4e73df;
        color: white;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto;
        font-size: 32px;
    }
    
    .badge {
        font-size: 90%;
    }
</style>

<!-- Script para marcar notificações como lidas -->
<script>
    $(document).ready(function() {
        // DataTable para o histórico
        $('#historyTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
            },
            "pageLength": 5,
            "order": [
                [4, 'desc']
            ]
        });
        
        // Marcar uma notificação como lida
        $('.mark-read').on('click', function() {
            var notificationId = $(this).data('id');
            
            $.ajax({
                url: '<?php echo BASE_URL; ?>print_queue/markNotificationRead',
                type: 'POST',
                data: {
                    notification_id: notificationId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erro: ' + response.message);
                    }
                },
                error: function() {
                    alert('Erro ao processar a solicitação.');
                }
            });
        });
        
        // Marcar todas as notificações como lidas
        $('.mark-all-read').on('click', function() {
            if (confirm('Marcar todas as notificações como lidas?')) {
                // Coletar todos os IDs de notificação
                var notificationIds = [];
                $('.mark-read').each(function() {
                    notificationIds.push($(this).data('id'));
                });
                
                // Criar função para marcar sequencialmente
                function markSequentially(index) {
                    if (index >= notificationIds.length) {
                        location.reload();
                        return;
                    }
                    
                    $.ajax({
                        url: '<?php echo BASE_URL; ?>print_queue/markNotificationRead',
                        type: 'POST',
                        data: {
                            notification_id: notificationIds[index]
                        },
                        dataType: 'json',
                        success: function() {
                            markSequentially(index + 1);
                        },
                        error: function() {
                            alert('Erro ao processar a solicitação.');
                        }
                    });
                }
                
                // Iniciar o processo
                markSequentially(0);
            }
        });
    });
</script>

<?php require_once APP_ROOT . '/views/partials/footer.php'; ?>