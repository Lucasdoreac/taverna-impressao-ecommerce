<?php require_once APP_ROOT . '/views/partials/admin_header.php'; ?>

<div class="container-fluid my-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-light">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>admin">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>print_queue">Fila de Impressão</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Detalhes do Item #<?php echo $queueItem['id']; ?></li>
                </ol>
            </nav>
            
            <h1 class="h2 mb-4 text-gray-800">
                <i class="fas fa-print mr-2"></i> Detalhes do Item na Fila
            </h1>
            
            <!-- Informações Básicas e Ações -->
            <div class="row mb-4">
                <!-- Informações Básicas -->
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Detalhes do Item de Impressão</h6>
                            <span class="badge badge-<?php 
                                if ($queueItem['status'] === 'pending') echo 'light';
                                elseif ($queueItem['status'] === 'scheduled') echo 'info';
                                elseif ($queueItem['status'] === 'printing') echo 'primary';
                                elseif ($queueItem['status'] === 'paused') echo 'warning';
                                elseif ($queueItem['status'] === 'completed') echo 'success';
                                elseif ($queueItem['status'] === 'failed') echo 'danger';
                                elseif ($queueItem['status'] === 'canceled') echo 'secondary';
                            ?>">
                                <?php 
                                if ($queueItem['status'] === 'pending') echo 'Pendente';
                                elseif ($queueItem['status'] === 'scheduled') echo 'Agendado';
                                elseif ($queueItem['status'] === 'printing') echo 'Imprimindo';
                                elseif ($queueItem['status'] === 'paused') echo 'Pausado';
                                elseif ($queueItem['status'] === 'completed') echo 'Concluído';
                                elseif ($queueItem['status'] === 'failed') echo 'Falha';
                                elseif ($queueItem['status'] === 'canceled') echo 'Cancelado';
                                ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="font-weight-bold">Produto</h5>
                                    <p><?php echo $queueItem['product_name']; ?></p>
                                    
                                    <h5 class="font-weight-bold mt-4">Cliente</h5>
                                    <p><?php echo $queueItem['customer_name']; ?></p>
                                    <p>
                                        <a href="mailto:<?php echo $queueItem['customer_email']; ?>"><?php echo $queueItem['customer_email']; ?></a><br>
                                        <?php if (!empty($queueItem['customer_phone'])): ?>
                                            <a href="tel:<?php echo $queueItem['customer_phone']; ?>"><?php echo $queueItem['customer_phone']; ?></a>
                                        <?php endif; ?>
                                    </p>
                                    
                                    <h5 class="font-weight-bold mt-4">Pedido</h5>
                                    <p>
                                        <a href="<?php echo BASE_URL; ?>admin/orders/details/<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-dark">
                                            #<?php echo $order['order_number']; ?> - Ver Detalhes
                                        </a>
                                    </p>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5 class="font-weight-bold">Detalhes da Impressão</h5>
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Prioridade:</th>
                                            <td>
                                                <span class="badge badge-pill <?php 
                                                    if ($queueItem['priority'] <= 3) echo 'badge-danger';
                                                    else if ($queueItem['priority'] <= 6) echo 'badge-warning';
                                                    else echo 'badge-info';
                                                ?>">
                                                    <?php echo $queueItem['priority']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Tempo Estimado:</th>
                                            <td><?php echo $queueItem['estimated_print_time_hours']; ?> horas</td>
                                        </tr>
                                        <tr>
                                            <th>Tipo de Filamento:</th>
                                            <td><?php echo $queueItem['filament_type']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Cor:</th>
                                            <td>
                                                <?php if (!empty($queueItem['filament_hex_code'])): ?>
                                                    <div class="d-flex align-items-center">
                                                        <div class="color-sample mr-2" style="background-color: <?php echo $queueItem['filament_hex_code']; ?>"></div>
                                                        <span><?php echo $queueItem['filament_color']; ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Não especificada</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Quantidade de Filamento:</th>
                                            <td><?php echo $queueItem['filament_usage_grams']; ?> gramas</td>
                                        </tr>
                                        <tr>
                                            <th>Escala:</th>
                                            <td><?php echo $queueItem['scale']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Impressora:</th>
                                            <td>
                                                <?php if (!empty($queueItem['printer_name'])): ?>
                                                    <?php echo $queueItem['printer_name']; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Não atribuída</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Adicionado em:</th>
                                            <td><?php echo date('d/m/Y H:i', strtotime($queueItem['created_at'])); ?></td>
                                        </tr>
                                        <?php if (!empty($queueItem['scheduled_start_date'])): ?>
                                        <tr>
                                            <th>Agendado para:</th>
                                            <td><?php echo date('d/m/Y H:i', strtotime($queueItem['scheduled_start_date'])); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($queueItem['print_start_date'])): ?>
                                        <tr>
                                            <th>Iniciado em:</th>
                                            <td><?php echo date('d/m/Y H:i', strtotime($queueItem['print_start_date'])); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php if (!empty($queueItem['print_finish_date'])): ?>
                                        <tr>
                                            <th>Finalizado em:</th>
                                            <td><?php echo date('d/m/Y H:i', strtotime($queueItem['print_finish_date'])); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modelo 3D do Cliente -->
                    <?php if (!empty($queueItem['customer_model_id'])): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Modelo 3D do Cliente</h6>
                        </div>
                        <div class="card-body">
                            <p>
                                <a href="<?php echo BASE_URL; ?>admin/customer_models/view/<?php echo $queueItem['customer_model_id']; ?>" class="btn btn-info">
                                    <i class="fas fa-cube mr-2"></i> Ver Modelo do Cliente
                                </a>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Histórico do Item -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Histórico</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Data/Hora</th>
                                            <th>Alteração</th>
                                            <th>Usuário</th>
                                            <th>Observações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($history)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Nenhum histórico disponível</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach($history as $record): ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($record['created_at'])); ?></td>
                                                    <td>
                                                        <?php
                                                        switch($record['action_type']) {
                                                            case 'status_update':
                                                                echo 'Status alterado para: <span class="badge badge-';
                                                                switch($record['new_value']) {
                                                                    case 'pending': echo 'light">Pendente'; break;
                                                                    case 'scheduled': echo 'info">Agendado'; break;
                                                                    case 'printing': echo 'primary">Imprimindo'; break;
                                                                    case 'paused': echo 'warning">Pausado'; break;
                                                                    case 'completed': echo 'success">Concluído'; break;
                                                                    case 'failed': echo 'danger">Falha'; break;
                                                                    case 'canceled': echo 'secondary">Cancelado'; break;
                                                                    default: echo 'secondary">' . $record['new_value'];
                                                                }
                                                                echo '</span>';
                                                                break;
                                                            case 'printer_assigned':
                                                                echo 'Impressora atribuída: <strong>' . $record['new_value'] . '</strong>';
                                                                break;
                                                            case 'priority_update':
                                                                echo 'Prioridade alterada para: <strong>' . $record['new_value'] . '</strong>';
                                                                break;
                                                            case 'item_created':
                                                                echo 'Item adicionado à fila';
                                                                break;
                                                            default:
                                                                echo $record['action_type'] . ': ' . $record['new_value'];
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo $record['user_name']; ?></td>
                                                    <td><?php echo $record['notes']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ações -->
                <div class="col-lg-4">
                    <!-- Card de Ações -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Ações</h6>
                        </div>
                        <div class="card-body">
                            <h5 class="font-weight-bold">Atualizar Status</h5>
                            <div class="mb-4">
                                <?php if (in_array($queueItem['status'], ['pending', 'scheduled'])): ?>
                                    <button class="btn btn-primary mb-2 status-update" data-id="<?php echo $queueItem['id']; ?>" data-status="printing">
                                        <i class="fas fa-print mr-2"></i> Iniciar Impressão
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($queueItem['status'] === 'pending'): ?>
                                    <button class="btn btn-info mb-2 status-update" data-id="<?php echo $queueItem['id']; ?>" data-status="scheduled">
                                        <i class="fas fa-calendar-check mr-2"></i> Agendar
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($queueItem['status'] === 'printing'): ?>
                                    <button class="btn btn-warning mb-2 status-update" data-id="<?php echo $queueItem['id']; ?>" data-status="paused">
                                        <i class="fas fa-pause mr-2"></i> Pausar
                                    </button>
                                    
                                    <button class="btn btn-success mb-2 status-update" data-id="<?php echo $queueItem['id']; ?>" data-status="completed">
                                        <i class="fas fa-check mr-2"></i> Marcar como Concluído
                                    </button>
                                    
                                    <button class="btn btn-danger mb-2 status-update" data-id="<?php echo $queueItem['id']; ?>" data-status="failed">
                                        <i class="fas fa-times mr-2"></i> Marcar como Falha
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($queueItem['status'] === 'paused'): ?>
                                    <button class="btn btn-primary mb-2 status-update" data-id="<?php echo $queueItem['id']; ?>" data-status="printing">
                                        <i class="fas fa-play mr-2"></i> Retomar
                                    </button>
                                    
                                    <button class="btn btn-danger mb-2 status-update" data-id="<?php echo $queueItem['id']; ?>" data-status="failed">
                                        <i class="fas fa-times mr-2"></i> Marcar como Falha
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (in_array($queueItem['status'], ['pending', 'scheduled'])): ?>
                                    <button class="btn btn-secondary mb-2 status-update" data-id="<?php echo $queueItem['id']; ?>" data-status="canceled">
                                        <i class="fas fa-ban mr-2"></i> Cancelar
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (in_array($queueItem['status'], ['pending', 'scheduled'])): ?>
                                <h5 class="font-weight-bold mt-4">Atribuir Impressora</h5>
                                <form id="printerForm" class="mb-4">
                                    <input type="hidden" name="queue_id" value="<?php echo $queueItem['id']; ?>">
                                    
                                    <div class="form-group">
                                        <select class="form-control" id="printerId" name="printer_id" required>
                                            <option value="">Escolha uma impressora...</option>
                                            <?php foreach($printers as $printer): ?>
                                                <?php if($printer['status'] === 'available'): ?>
                                                    <option value="<?php echo $printer['id']; ?>"<?php echo (!empty($queueItem['printer_id']) && $queueItem['printer_id'] == $printer['id']) ? ' selected' : ''; ?>>
                                                        <?php echo $printer['name']; ?> (<?php echo $printer['model']; ?>)
                                                        - <?php echo $printer['max_width']; ?>x<?php echo $printer['max_depth']; ?>x<?php echo $printer['max_height']; ?>mm
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <button type="button" class="btn btn-info" id="assignPrinterBtn">
                                        <i class="fas fa-desktop mr-2"></i> Atribuir Impressora
                                    </button>
                                </form>
                                
                                <h5 class="font-weight-bold mt-4">Atualizar Prioridade</h5>
                                <form id="priorityForm" class="mb-4">
                                    <input type="hidden" name="queue_id" value="<?php echo $queueItem['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label>Prioridade (1 = mais alta, 10 = mais baixa):</label>
                                        <input type="number" class="form-control" name="priority" min="1" max="10" value="<?php echo $queueItem['priority']; ?>" required>
                                        <small class="form-text text-muted">
                                            Prioridades mais altas (números menores) serão impressas primeiro.
                                        </small>
                                    </div>
                                    
                                    <button type="button" class="btn btn-warning" id="updatePriorityBtn">
                                        <i class="fas fa-sort-numeric-down mr-2"></i> Atualizar Prioridade
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <h5 class="font-weight-bold mt-4">Notificar Cliente</h5>
                            <p class="text-muted">Funcionalidade em desenvolvimento</p>
                            
                            <div class="mt-4">
                                <a href="<?php echo BASE_URL; ?>print_queue" class="btn btn-secondary btn-block">
                                    <i class="fas fa-arrow-left mr-2"></i> Voltar para a Fila
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Status -->
<div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">Confirmar Alteração de Status</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="statusForm">
                    <input type="hidden" id="queueIdStatus" name="queue_id" value="">
                    <input type="hidden" id="statusValue" name="status" value="">
                    
                    <p id="statusConfirmText">Tem certeza que deseja alterar o status deste item?</p>
                    
                    <div class="form-group">
                        <label for="statusNotes">Observações (opcional):</label>
                        <textarea class="form-control" id="statusNotes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="updateStatusBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<style>
    .color-sample {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        border: 1px solid #ccc;
    }
</style>

<script>
    $(document).ready(function() {
        // Atualização de Status
        $('.status-update').on('click', function(e) {
            e.preventDefault();
            var queueId = $(this).data('id');
            var status = $(this).data('status');
            var statusText = '';
            
            switch(status) {
                case 'scheduled':
                    statusText = 'agendar';
                    break;
                case 'printing':
                    statusText = 'iniciar impressão';
                    break;
                case 'paused':
                    statusText = 'pausar impressão';
                    break;
                case 'completed':
                    statusText = 'marcar como concluído';
                    break;
                case 'failed':
                    statusText = 'marcar como falha';
                    break;
                case 'canceled':
                    statusText = 'cancelar';
                    break;
                default:
                    statusText = 'alterar o status para ' + status;
            }
            
            $('#queueIdStatus').val(queueId);
            $('#statusValue').val(status);
            $('#statusConfirmText').text('Tem certeza que deseja ' + statusText + ' este item?');
            
            $('#statusModal').modal('show');
        });
        
        $('#updateStatusBtn').on('click', function() {
            $.ajax({
                url: '<?php echo BASE_URL; ?>print_queue/updateStatus',
                type: 'POST',
                data: $('#statusForm').serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#statusModal').modal('hide');
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
        
        // Atribuição de Impressora
        $('#assignPrinterBtn').on('click', function() {
            if ($('#printerId').val() === '') {
                alert('Por favor, selecione uma impressora.');
                return;
            }
            
            $.ajax({
                url: '<?php echo BASE_URL; ?>print_queue/assignPrinter',
                type: 'POST',
                data: $('#printerForm').serialize(),
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
        
        // Atualização de Prioridade
        $('#updatePriorityBtn').on('click', function() {
            var priority = $('#priorityForm input[name="priority"]').val();
            
            if (priority === '' || priority < 1 || priority > 10) {
                alert('Por favor, informe uma prioridade válida (1-10).');
                return;
            }
            
            $.ajax({
                url: '<?php echo BASE_URL; ?>print_queue/updatePriority',
                type: 'POST',
                data: $('#priorityForm').serialize(),
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
    });
</script>

<?php require_once APP_ROOT . '/views/partials/admin_footer.php'; ?>