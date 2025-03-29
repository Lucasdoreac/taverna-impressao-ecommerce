<?php require_once APP_ROOT . '/views/partials/admin_header.php'; ?>

<div class="container-fluid my-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h2 mb-4 text-gray-800">
                <i class="fas fa-print mr-2"></i> Gerenciamento da Fila de Impressão 3D
            </h1>
            
            <!-- Estatísticas Rápidas -->
            <div class="row mb-4">
                <!-- Total de Itens -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total de Itens
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['total']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-cube fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Em Impressão -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Em Impressão
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['printing']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-spinner fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pendentes -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Pendentes
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['pending']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Impressoras Disponíveis -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Impressoras Disponíveis
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['printers_available']; ?> / <?php echo $stats['printers_total']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-desktop fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estatísticas Adicionais -->
            <div class="row mb-4">
                <!-- Tempo Estimado Total -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-gray-600 text-uppercase mb-1">
                                        Tempo Total Estimado
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php 
                                        $hours = floor($stats['estimated_time_total']);
                                        $minutes = round(($stats['estimated_time_total'] - $hours) * 60);
                                        echo $hours . 'h ' . $minutes . 'min'; 
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filamento Estimado Total -->
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-gray-600 text-uppercase mb-1">
                                        Filamento Total Estimado
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php 
                                        $kg = floor($stats['estimated_filament_total'] / 1000);
                                        $g = $stats['estimated_filament_total'] % 1000;
                                        echo $kg . 'kg ' . $g . 'g'; 
                                        ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-weight fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status das Impressoras -->
                <div class="col-xl-4 col-md-12 mb-4">
                    <div class="card shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-gray-600 text-uppercase mb-1">
                                        Status das Impressoras
                                    </div>
                                    <div class="mb-0 text-gray-800">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Disponíveis:</span>
                                            <span class="font-weight-bold"><?php echo $stats['printers_available']; ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Em Uso:</span>
                                            <span class="font-weight-bold"><?php echo $stats['printers_printing']; ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Em Manutenção:</span>
                                            <span class="font-weight-bold"><?php echo $stats['printers_maintenance']; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-tools fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ações Rápidas -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Ações Rápidas</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <a href="<?php echo BASE_URL; ?>print_queue/printers" class="btn btn-info btn-block">
                                        <i class="fas fa-desktop mr-2"></i> Gerenciar Impressoras
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="dropdown">
                                        <button class="btn btn-success btn-block dropdown-toggle" type="button" id="filterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-filter mr-2"></i> Filtrar por Status
                                        </button>
                                        <div class="dropdown-menu w-100" aria-labelledby="filterDropdown">
                                            <a class="dropdown-item <?php echo empty($filters['status']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>print_queue">Todos</a>
                                            <a class="dropdown-item <?php echo $filters['status'] === 'pending' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>print_queue?status=pending">Pendentes</a>
                                            <a class="dropdown-item <?php echo $filters['status'] === 'scheduled' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>print_queue?status=scheduled">Agendados</a>
                                            <a class="dropdown-item <?php echo $filters['status'] === 'printing' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>print_queue?status=printing">Em Impressão</a>
                                            <a class="dropdown-item <?php echo $filters['status'] === 'completed' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>print_queue?status=completed">Concluídos</a>
                                            <a class="dropdown-item <?php echo $filters['status'] === 'failed' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>print_queue?status=failed">Falhas</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="dropdown">
                                        <button class="btn btn-primary btn-block dropdown-toggle" type="button" id="sortDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-sort mr-2"></i> Ordenar Por
                                        </button>
                                        <div class="dropdown-menu w-100" aria-labelledby="sortDropdown">
                                            <a class="dropdown-item <?php echo $filters['order_by'] === 'priority' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>print_queue?order_by=priority&order_dir=asc<?php echo !empty($filters['status']) ? '&status=' . $filters['status'] : ''; ?>">Prioridade (Asc)</a>
                                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>print_queue?order_by=priority&order_dir=desc<?php echo !empty($filters['status']) ? '&status=' . $filters['status'] : ''; ?>">Prioridade (Desc)</a>
                                            <a class="dropdown-item <?php echo $filters['order_by'] === 'created_at' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>print_queue?order_by=created_at&order_dir=asc<?php echo !empty($filters['status']) ? '&status=' . $filters['status'] : ''; ?>">Data de Adição (Asc)</a>
                                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>print_queue?order_by=created_at&order_dir=desc<?php echo !empty($filters['status']) ? '&status=' . $filters['status'] : ''; ?>">Data de Adição (Desc)</a>
                                            <a class="dropdown-item <?php echo $filters['order_by'] === 'scheduled_start_date' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>print_queue?order_by=scheduled_start_date&order_dir=asc<?php echo !empty($filters['status']) ? '&status=' . $filters['status'] : ''; ?>">Data Agendada (Asc)</a>
                                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>print_queue?order_by=scheduled_start_date&order_dir=desc<?php echo !empty($filters['status']) ? '&status=' . $filters['status'] : ''; ?>">Data Agendada (Desc)</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabela da Fila de Impressão -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Fila de Impressão</h6>
                    <span class="badge badge-<?php echo empty($filters['status']) ? 'primary' : 'info'; ?>">
                        <?php if (!empty($filters['status'])): ?>
                            Filtrando: <?php echo ucfirst($filters['status']); ?>
                        <?php else: ?>
                            Mostrando todos os itens
                        <?php endif; ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($queueItems)): ?>
                        <div class="alert alert-info">
                            Não há itens na fila de impressão<?php echo !empty($filters['status']) ? ' com o status selecionado' : ''; ?>.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="queueTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th width="5%">ID</th>
                                        <th width="5%">Prioridade</th>
                                        <th width="15%">Produto</th>
                                        <th width="8%">Status</th>
                                        <th width="10%">Impressora</th>
                                        <th width="12%">Cliente</th>
                                        <th width="10%">Pedido</th>
                                        <th width="10%">Tempo Est.</th>
                                        <th width="10%">Filamento</th>
                                        <th width="15%">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($queueItems as $item): ?>
                                        <tr class="
                                            <?php if($item['status'] === 'pending'): ?>bg-light
                                            <?php elseif($item['status'] === 'printing'): ?>table-primary
                                            <?php elseif($item['status'] === 'scheduled'): ?>table-info
                                            <?php elseif($item['status'] === 'completed'): ?>table-success
                                            <?php elseif($item['status'] === 'failed'): ?>table-danger
                                            <?php elseif($item['status'] === 'canceled'): ?>table-secondary
                                            <?php endif; ?>
                                        ">
                                            <td><?php echo $item['id']; ?></td>
                                            <td>
                                                <span class="badge badge-pill 
                                                    <?php 
                                                    if ($item['priority'] <= 3) echo 'badge-danger';
                                                    else if ($item['priority'] <= 6) echo 'badge-warning';
                                                    else echo 'badge-info';
                                                    ?>">
                                                    <?php echo $item['priority']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $item['product_name']; ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php 
                                                    if ($item['status'] === 'pending') echo 'badge-light';
                                                    elseif ($item['status'] === 'scheduled') echo 'badge-info';
                                                    elseif ($item['status'] === 'printing') echo 'badge-primary';
                                                    elseif ($item['status'] === 'paused') echo 'badge-warning';
                                                    elseif ($item['status'] === 'completed') echo 'badge-success';
                                                    elseif ($item['status'] === 'failed') echo 'badge-danger';
                                                    elseif ($item['status'] === 'canceled') echo 'badge-secondary';
                                                    ?>">
                                                    <?php 
                                                    if ($item['status'] === 'pending') echo 'Pendente';
                                                    elseif ($item['status'] === 'scheduled') echo 'Agendado';
                                                    elseif ($item['status'] === 'printing') echo 'Imprimindo';
                                                    elseif ($item['status'] === 'paused') echo 'Pausado';
                                                    elseif ($item['status'] === 'completed') echo 'Concluído';
                                                    elseif ($item['status'] === 'failed') echo 'Falha';
                                                    elseif ($item['status'] === 'canceled') echo 'Cancelado';
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($item['printer_name'])): ?>
                                                    <?php echo $item['printer_name']; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Não atribuída</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $item['customer_name']; ?></td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>admin/orders/details/<?php echo $item['order_id']; ?>" class="btn btn-sm btn-outline-dark">
                                                    #<?php echo $item['order_number']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo $item['estimated_print_time_hours']; ?> h</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($item['filament_hex_code'])): ?>
                                                        <div class="color-sample mr-2" style="background-color: <?php echo $item['filament_hex_code']; ?>"></div>
                                                    <?php endif; ?>
                                                    <span>
                                                        <?php echo $item['filament_type']; ?>
                                                        <?php if (!empty($item['filament_color'])): ?>
                                                            (<?php echo $item['filament_color']; ?>)
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="<?php echo BASE_URL; ?>print_queue/details/<?php echo $item['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <!-- Dropdown para ações rápidas -->
                                                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <!-- Atualizar Status -->
                                                        <?php if ($item['status'] === 'pending'): ?>
                                                            <a class="dropdown-item status-update" href="#" data-id="<?php echo $item['id']; ?>" data-status="scheduled">
                                                                <i class="fas fa-calendar-check text-info"></i> Agendar
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($item['status'] === 'scheduled' || $item['status'] === 'pending'): ?>
                                                            <a class="dropdown-item status-update" href="#" data-id="<?php echo $item['id']; ?>" data-status="printing">
                                                                <i class="fas fa-print text-primary"></i> Iniciar Impressão
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($item['status'] === 'printing'): ?>
                                                            <a class="dropdown-item status-update" href="#" data-id="<?php echo $item['id']; ?>" data-status="paused">
                                                                <i class="fas fa-pause text-warning"></i> Pausar
                                                            </a>
                                                            <a class="dropdown-item status-update" href="#" data-id="<?php echo $item['id']; ?>" data-status="completed">
                                                                <i class="fas fa-check text-success"></i> Marcar como Concluído
                                                            </a>
                                                            <a class="dropdown-item status-update" href="#" data-id="<?php echo $item['id']; ?>" data-status="failed">
                                                                <i class="fas fa-times text-danger"></i> Marcar como Falha
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($item['status'] === 'paused'): ?>
                                                            <a class="dropdown-item status-update" href="#" data-id="<?php echo $item['id']; ?>" data-status="printing">
                                                                <i class="fas fa-play text-primary"></i> Retomar
                                                            </a>
                                                            <a class="dropdown-item status-update" href="#" data-id="<?php echo $item['id']; ?>" data-status="failed">
                                                                <i class="fas fa-times text-danger"></i> Marcar como Falha
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($item['status'] === 'pending' || $item['status'] === 'scheduled'): ?>
                                                            <div class="dropdown-divider"></div>
                                                            
                                                            <!-- Atribuir Impressora -->
                                                            <a class="dropdown-item printer-assignment" href="#" data-id="<?php echo $item['id']; ?>" data-toggle="modal" data-target="#printerModal">
                                                                <i class="fas fa-desktop text-info"></i> Atribuir Impressora
                                                            </a>
                                                            
                                                            <!-- Alterar Prioridade -->
                                                            <a class="dropdown-item priority-update" href="#" data-id="<?php echo $item['id']; ?>" data-priority="<?php echo $item['priority']; ?>" data-toggle="modal" data-target="#priorityModal">
                                                                <i class="fas fa-sort-numeric-down text-warning"></i> Alterar Prioridade
                                                            </a>
                                                            
                                                            <div class="dropdown-divider"></div>
                                                            
                                                            <!-- Cancelar -->
                                                            <a class="dropdown-item status-update text-danger" href="#" data-id="<?php echo $item['id']; ?>" data-status="canceled">
                                                                <i class="fas fa-ban"></i> Cancelar
                                                            </a>
                                                        <?php endif; ?>
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
        </div>
    </div>
</div>

<!-- Modal de Atribuição de Impressora -->
<div class="modal fade" id="printerModal" tabindex="-1" role="dialog" aria-labelledby="printerModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="printerModalLabel">Atribuir Impressora</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="printerForm">
                    <input type="hidden" id="queueIdPrinter" name="queue_id" value="">
                    
                    <div class="form-group">
                        <label for="printerId">Selecione a Impressora:</label>
                        <select class="form-control" id="printerId" name="printer_id" required>
                            <option value="">Escolha uma impressora...</option>
                            <?php foreach($printers as $printer): ?>
                                <?php if($printer['status'] === 'available'): ?>
                                    <option value="<?php echo $printer['id']; ?>">
                                        <?php echo $printer['name']; ?> (<?php echo $printer['model']; ?>)
                                        - <?php echo $printer['max_width']; ?>x<?php echo $printer['max_depth']; ?>x<?php echo $printer['max_height']; ?>mm
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        
                        <?php if (empty(array_filter($printers, function($p) { return $p['status'] === 'available'; }))): ?>
                            <div class="alert alert-warning mt-3">
                                Não há impressoras disponíveis no momento. Por favor, aguarde ou libere uma impressora existente.
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="assignPrinterBtn" <?php if (empty(array_filter($printers, function($p) { return $p['status'] === 'available'; }))): ?>disabled<?php endif; ?>>Atribuir</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Alteração de Prioridade -->
<div class="modal fade" id="priorityModal" tabindex="-1" role="dialog" aria-labelledby="priorityModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="priorityModalLabel">Alterar Prioridade</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="priorityForm">
                    <input type="hidden" id="queueIdPriority" name="queue_id" value="">
                    
                    <div class="form-group">
                        <label for="priorityValue">Prioridade (1 = mais alta, 10 = mais baixa):</label>
                        <input type="number" class="form-control" id="priorityValue" name="priority" min="1" max="10" required>
                        <small class="form-text text-muted">
                            Prioridades mais altas (números menores) serão impressas primeiro.
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="updatePriorityBtn">Atualizar</button>
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
        // DataTable
        $('#queueTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
            },
            "pageLength": 25,
            "order": []
        });
        
        // Atribuição de Impressora
        $('.printer-assignment').on('click', function() {
            var queueId = $(this).data('id');
            $('#queueIdPrinter').val(queueId);
        });
        
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
                        $('#printerModal').modal('hide');
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
        $('.priority-update').on('click', function() {
            var queueId = $(this).data('id');
            var priority = $(this).data('priority');
            $('#queueIdPriority').val(queueId);
            $('#priorityValue').val(priority);
        });
        
        $('#updatePriorityBtn').on('click', function() {
            if ($('#priorityValue').val() === '' || $('#priorityValue').val() < 1 || $('#priorityValue').val() > 10) {
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
                        $('#priorityModal').modal('hide');
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
    });
</script>

<?php require_once APP_ROOT . '/views/partials/admin_footer.php'; ?>
