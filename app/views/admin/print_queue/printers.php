<?php require_once APP_ROOT . '/views/partials/admin_header.php'; ?>

<div class="container-fluid my-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-light">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>admin">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>print_queue">Fila de Impressão</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Gerenciamento de Impressoras</li>
                </ol>
            </nav>
            
            <h1 class="h2 mb-4 text-gray-800">
                <i class="fas fa-desktop mr-2"></i> Gerenciamento de Impressoras 3D
            </h1>
            
            <!-- Alertas -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Operação realizada com sucesso!
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    Erro ao processar a operação. Por favor, tente novamente.
                </div>
            <?php endif; ?>
            
            <div class="row mb-4">
                <!-- Lista de Impressoras -->
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Impressoras Disponíveis</h6>
                            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addPrinterModal">
                                <i class="fas fa-plus mr-2"></i> Adicionar Nova Impressora
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($printers)): ?>
                                <div class="alert alert-info">
                                    Nenhuma impressora cadastrada. Adicione uma impressora para começar.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="printersTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th width="5%">ID</th>
                                                <th width="20%">Nome</th>
                                                <th width="15%">Modelo</th>
                                                <th width="15%">Dimensões (mm)</th>
                                                <th width="15%">Tipos de Filamento</th>
                                                <th width="10%">Status</th>
                                                <th width="20%">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($printers as $printer): ?>
                                                <tr>
                                                    <td><?php echo $printer['id']; ?></td>
                                                    <td><?php echo $printer['name']; ?></td>
                                                    <td><?php echo $printer['model']; ?></td>
                                                    <td>
                                                        <?php echo $printer['max_width']; ?> x 
                                                        <?php echo $printer['max_depth']; ?> x 
                                                        <?php echo $printer['max_height']; ?>
                                                    </td>
                                                    <td><?php echo $printer['filament_types']; ?></td>
                                                    <td>
                                                        <span class="badge 
                                                            <?php 
                                                            if ($printer['status'] === 'available') echo 'badge-success';
                                                            elseif ($printer['status'] === 'printing') echo 'badge-primary';
                                                            elseif ($printer['status'] === 'maintenance') echo 'badge-warning';
                                                            else echo 'badge-secondary';
                                                            ?>
                                                        ">
                                                            <?php 
                                                            if ($printer['status'] === 'available') echo 'Disponível';
                                                            elseif ($printer['status'] === 'printing') echo 'Imprimindo';
                                                            elseif ($printer['status'] === 'maintenance') echo 'Manutenção';
                                                            else echo ucfirst($printer['status']);
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-info edit-printer" 
                                                                data-id="<?php echo $printer['id']; ?>"
                                                                data-name="<?php echo $printer['name']; ?>"
                                                                data-model="<?php echo $printer['model']; ?>"
                                                                data-width="<?php echo $printer['max_width']; ?>"
                                                                data-depth="<?php echo $printer['max_depth']; ?>"
                                                                data-height="<?php echo $printer['max_height']; ?>"
                                                                data-filament="<?php echo $printer['filament_types']; ?>"
                                                                data-notes="<?php echo $printer['notes']; ?>"
                                                                data-toggle="modal" data-target="#editPrinterModal">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            
                                                            <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <i class="fas fa-cog"></i>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-right">
                                                                <!-- Alterar Status -->
                                                                <?php if ($printer['status'] !== 'available'): ?>
                                                                    <a class="dropdown-item status-update" href="#" data-id="<?php echo $printer['id']; ?>" data-status="available">
                                                                        <i class="fas fa-check text-success"></i> Marcar como Disponível
                                                                    </a>
                                                                <?php endif; ?>
                                                                
                                                                <?php if ($printer['status'] !== 'maintenance'): ?>
                                                                    <a class="dropdown-item status-update" href="#" data-id="<?php echo $printer['id']; ?>" data-status="maintenance">
                                                                        <i class="fas fa-tools text-warning"></i> Enviar para Manutenção
                                                                    </a>
                                                                <?php endif; ?>
                                                                
                                                                <?php if ($printer['status'] !== 'inactive'): ?>
                                                                    <a class="dropdown-item status-update" href="#" data-id="<?php echo $printer['id']; ?>" data-status="inactive">
                                                                        <i class="fas fa-power-off text-danger"></i> Desativar
                                                                    </a>
                                                                <?php endif; ?>
                                                                
                                                                <div class="dropdown-divider"></div>
                                                                
                                                                <a class="dropdown-item delete-printer" href="#" data-id="<?php echo $printer['id']; ?>" data-name="<?php echo $printer['name']; ?>">
                                                                    <i class="fas fa-trash text-danger"></i> Excluir
                                                                </a>
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
                
                <!-- Resumo e Estatísticas -->
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Resumo</h6>
                        </div>
                        <div class="card-body">
                            <?php 
                                $totalPrinters = count($printers);
                                $availablePrinters = count(array_filter($printers, function($p) { return $p['status'] === 'available'; }));
                                $printingPrinters = count(array_filter($printers, function($p) { return $p['status'] === 'printing'; }));
                                $maintenancePrinters = count(array_filter($printers, function($p) { return $p['status'] === 'maintenance'; }));
                                $inactivePrinters = count(array_filter($printers, function($p) { return $p['status'] === 'inactive'; }));
                            ?>
                            
                            <div class="mb-4">
                                <h4>Total de Impressoras: <?php echo $totalPrinters; ?></h4>
                            </div>
                            
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Disponíveis:</span>
                                    <span class="font-weight-bold text-success"><?php echo $availablePrinters; ?></span>
                                </div>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $totalPrinters > 0 ? ($availablePrinters / $totalPrinters * 100) : 0; ?>%" aria-valuenow="<?php echo $availablePrinters; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalPrinters; ?>"></div>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Em Uso:</span>
                                    <span class="font-weight-bold text-primary"><?php echo $printingPrinters; ?></span>
                                </div>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $totalPrinters > 0 ? ($printingPrinters / $totalPrinters * 100) : 0; ?>%" aria-valuenow="<?php echo $printingPrinters; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalPrinters; ?>"></div>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Em Manutenção:</span>
                                    <span class="font-weight-bold text-warning"><?php echo $maintenancePrinters; ?></span>
                                </div>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $totalPrinters > 0 ? ($maintenancePrinters / $totalPrinters * 100) : 0; ?>%" aria-valuenow="<?php echo $maintenancePrinters; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalPrinters; ?>"></div>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Inativas:</span>
                                    <span class="font-weight-bold text-secondary"><?php echo $inactivePrinters; ?></span>
                                </div>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-secondary" role="progressbar" style="width: <?php echo $totalPrinters > 0 ? ($inactivePrinters / $totalPrinters * 100) : 0; ?>%" aria-valuenow="<?php echo $inactivePrinters; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalPrinters; ?>"></div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <a href="<?php echo BASE_URL; ?>print_queue" class="btn btn-secondary btn-block">
                                    <i class="fas fa-arrow-left mr-2"></i> Voltar para a Fila
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Dicas</h6>
                        </div>
                        <div class="card-body">
                            <p>
                                <i class="fas fa-info-circle text-info mr-2"></i>
                                <strong>Impressoras em uso</strong> estão atualmente imprimindo um item da fila.
                            </p>
                            <p>
                                <i class="fas fa-info-circle text-info mr-2"></i>
                                Impressoras em <strong>manutenção</strong> não estarão disponíveis para novos trabalhos até serem marcadas como disponíveis.
                            </p>
                            <p>
                                <i class="fas fa-info-circle text-info mr-2"></i>
                                Impressoras <strong>inativas</strong> não aparecem nas opções de atribuição.
                            </p>
                            <p>
                                <i class="fas fa-info-circle text-info mr-2"></i>
                                Uma impressora em uso só ficará disponível novamente quando o trabalho for concluído ou falhar.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Adicionar Impressora -->
<div class="modal fade" id="addPrinterModal" tabindex="-1" role="dialog" aria-labelledby="addPrinterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPrinterModalLabel">Adicionar Nova Impressora</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addPrinterForm" action="<?php echo BASE_URL; ?>print_queue/addPrinter" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nome da Impressora*</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <small class="form-text text-muted">Um nome único para identificar esta impressora</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="model">Modelo*</label>
                                <input type="text" class="form-control" id="model" name="model" required>
                                <small class="form-text text-muted">Ex: Ender 3, Prusa i3, etc.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="max_width">Largura Máx. (mm)*</label>
                                <input type="number" class="form-control" id="max_width" name="max_width" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="max_depth">Profundidade Máx. (mm)*</label>
                                <input type="number" class="form-control" id="max_depth" name="max_depth" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="max_height">Altura Máx. (mm)*</label>
                                <input type="number" class="form-control" id="max_height" name="max_height" min="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="filament_types">Tipos de Filamento Compatíveis</label>
                        <input type="text" class="form-control" id="filament_types" name="filament_types">
                        <small class="form-text text-muted">Separados por vírgula. Ex: PLA, PETG, ABS</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Observações</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        <small class="form-text text-muted">Informações adicionais sobre a impressora</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" form="addPrinterForm" class="btn btn-primary">Adicionar Impressora</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Editar Impressora -->
<div class="modal fade" id="editPrinterModal" tabindex="-1" role="dialog" aria-labelledby="editPrinterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPrinterModalLabel">Editar Impressora</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editPrinterForm" action="<?php echo BASE_URL; ?>print_queue/updatePrinter" method="POST">
                    <input type="hidden" id="edit_printer_id" name="printer_id" value="">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_name">Nome da Impressora*</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_model">Modelo*</label>
                                <input type="text" class="form-control" id="edit_model" name="model" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_max_width">Largura Máx. (mm)*</label>
                                <input type="number" class="form-control" id="edit_max_width" name="max_width" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_max_depth">Profundidade Máx. (mm)*</label>
                                <input type="number" class="form-control" id="edit_max_depth" name="max_depth" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_max_height">Altura Máx. (mm)*</label>
                                <input type="number" class="form-control" id="edit_max_height" name="max_height" min="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_filament_types">Tipos de Filamento Compatíveis</label>
                        <input type="text" class="form-control" id="edit_filament_types" name="filament_types">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_notes">Observações</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" form="editPrinterForm" class="btn btn-primary">Salvar Alterações</button>
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
                    <input type="hidden" id="printerIdStatus" name="printer_id" value="">
                    <input type="hidden" id="statusValue" name="status" value="">
                    
                    <p id="statusConfirmText">Tem certeza que deseja alterar o status desta impressora?</p>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="updateStatusBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="deleteConfirmText">Tem certeza que deseja excluir esta impressora?</p>
                <p class="text-danger">Esta ação não pode ser desfeita!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Excluir</a>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // DataTable
        $('#printersTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json"
            },
            "pageLength": 10,
            "order": []
        });
        
        // Editar Impressora - Preencher Modal
        $('.edit-printer').on('click', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var model = $(this).data('model');
            var width = $(this).data('width');
            var depth = $(this).data('depth');
            var height = $(this).data('height');
            var filament = $(this).data('filament');
            var notes = $(this).data('notes');
            
            $('#edit_printer_id').val(id);
            $('#edit_name').val(name);
            $('#edit_model').val(model);
            $('#edit_max_width').val(width);
            $('#edit_max_depth').val(depth);
            $('#edit_max_height').val(height);
            $('#edit_filament_types').val(filament);
            $('#edit_notes').val(notes);
        });
        
        // Atualização de Status
        $('.status-update').on('click', function(e) {
            e.preventDefault();
            var printerId = $(this).data('id');
            var status = $(this).data('status');
            var statusText = '';
            
            switch(status) {
                case 'available':
                    statusText = 'marcar como disponível';
                    break;
                case 'maintenance':
                    statusText = 'enviar para manutenção';
                    break;
                case 'inactive':
                    statusText = 'desativar';
                    break;
                default:
                    statusText = 'alterar o status para ' + status;
            }
            
            $('#printerIdStatus').val(printerId);
            $('#statusValue').val(status);
            $('#statusConfirmText').text('Tem certeza que deseja ' + statusText + ' esta impressora?');
            
            $('#statusModal').modal('show');
        });
        
        $('#updateStatusBtn').on('click', function() {
            $.ajax({
                url: '<?php echo BASE_URL; ?>print_queue/updatePrinterStatus',
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
        
        // Exclusão de Impressora
        $('.delete-printer').on('click', function(e) {
            e.preventDefault();
            var printerId = $(this).data('id');
            var printerName = $(this).data('name');
            
            $('#deleteConfirmText').text('Tem certeza que deseja excluir a impressora "' + printerName + '"?');
            $('#confirmDeleteBtn').attr('href', '<?php echo BASE_URL; ?>print_queue/deletePrinter/' + printerId);
            
            $('#deleteModal').modal('show');
        });
    });
</script>

<?php require_once APP_ROOT . '/views/partials/admin_footer.php'; ?>