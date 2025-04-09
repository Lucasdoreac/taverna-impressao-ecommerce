<?php
/**
 * View - Detalhes do Item na Fila de Impressão (Admin)
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Views/Admin
 * @version    1.0.0
 */

// Garantir que este arquivo não seja acessado diretamente
defined('BASEPATH') or exit('Acesso direto ao script não é permitido');
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                <a href="/print-queue" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Voltar para Fila
                </a>
            </div>
            
            <?php if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_type'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['flash_message'], ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            
            <!-- Informações do Item na Fila -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informações do Item</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl>
                                        <dt>ID do Item</dt>
                                        <dd><?= htmlspecialchars($queueItem['id'], ENT_QUOTES, 'UTF-8') ?></dd>
                                        
                                        <dt>Nome do Modelo</dt>
                                        <dd><?= htmlspecialchars($queueItem['model_name'], ENT_QUOTES, 'UTF-8') ?></dd>
                                        
                                        <dt>Cliente</dt>
                                        <dd>
                                            <?= htmlspecialchars($queueItem['user_name'], ENT_QUOTES, 'UTF-8') ?><br>
                                            <small><?= htmlspecialchars($queueItem['user_email'], ENT_QUOTES, 'UTF-8') ?></small>
                                        </dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl>
                                        <dt>Status</dt>
                                        <dd>
                                            <?php
                                            $statusClass = '';
                                            $statusText = '';
                                            
                                            switch($queueItem['status']) {
                                                case 'pending':
                                                    $statusClass = 'badge-warning';
                                                    $statusText = 'Pendente';
                                                    break;
                                                case 'assigned':
                                                    $statusClass = 'badge-info';
                                                    $statusText = 'Atribuído';
                                                    break;
                                                case 'printing':
                                                    $statusClass = 'badge-primary';
                                                    $statusText = 'Em Impressão';
                                                    break;
                                                case 'completed':
                                                    $statusClass = 'badge-success';
                                                    $statusText = 'Concluído';
                                                    break;
                                                case 'cancelled':
                                                    $statusClass = 'badge-secondary';
                                                    $statusText = 'Cancelado';
                                                    break;
                                                case 'failed':
                                                    $statusClass = 'badge-danger';
                                                    $statusText = 'Falha';
                                                    break;
                                                default:
                                                    $statusClass = 'badge-light';
                                                    $statusText = $queueItem['status'];
                                            }
                                            ?>
                                            <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                        </dd>
                                        
                                        <dt>Prioridade</dt>
                                        <dd><?= htmlspecialchars($queueItem['priority'], ENT_QUOTES, 'UTF-8') ?> / 10</dd>
                                        
                                        <dt>Data de Criação</dt>
                                        <dd><?= htmlspecialchars(date('d/m/Y H:i', strtotime($queueItem['created_at'])), ENT_QUOTES, 'UTF-8') ?></dd>
                                        
                                        <?php if (isset($queueItem['updated_at']) && $queueItem['updated_at']): ?>
                                            <dt>Última Atualização</dt>
                                            <dd><?= htmlspecialchars(date('d/m/Y H:i', strtotime($queueItem['updated_at'])), ENT_QUOTES, 'UTF-8') ?></dd>
                                        <?php endif; ?>
                                    </dl>
                                </div>
                            </div>
                            
                            <?php if (!empty($queueItem['notes'])): ?>
                                <div class="row">
                                    <div class="col-12">
                                        <dl>
                                            <dt>Notas</dt>
                                            <dd><?= nl2br(htmlspecialchars($queueItem['notes'], ENT_QUOTES, 'UTF-8')) ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Ações disponíveis com base no status -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="btn-group">
                                        <?php if ($queueItem['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#assignModal">
                                                <i class="fa fa-print"></i> Atribuir à Impressora
                                            </button>
                                            
                                            <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#priorityModal">
                                                <i class="fa fa-sort-amount-up"></i> Ajustar Prioridade
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($queueItem['status'] !== 'completed' && $queueItem['status'] !== 'cancelled'): ?>
                                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#cancelModal">
                                                <i class="fa fa-times"></i> Cancelar Item
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Detalhes do Modelo 3D</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl>
                                        <dt>ID do Modelo</dt>
                                        <dd><?= htmlspecialchars($model['id'], ENT_QUOTES, 'UTF-8') ?></dd>
                                        
                                        <dt>Nome Original</dt>
                                        <dd><?= htmlspecialchars($model['original_name'], ENT_QUOTES, 'UTF-8') ?></dd>
                                        
                                        <dt>Formato</dt>
                                        <dd><?= htmlspecialchars($model['file_extension'], ENT_QUOTES, 'UTF-8') ?></dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <?php
                                    $validationData = isset($model['validation_data']) && !empty($model['validation_data'])
                                                    ? (is_array($model['validation_data']) ? $model['validation_data'] : json_decode($model['validation_data'], true))
                                                    : [];
                                    ?>
                                    <?php if (!empty($validationData) && isset($validationData['size'])): ?>
                                        <dl>
                                            <dt>Dimensões</dt>
                                            <dd>
                                                <?= htmlspecialchars($validationData['size']['width'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> x 
                                                <?= htmlspecialchars($validationData['size']['height'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> x 
                                                <?= htmlspecialchars($validationData['size']['depth'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> mm
                                            </dd>
                                            
                                            <dt>Volume</dt>
                                            <dd><?= htmlspecialchars($validationData['size']['volume'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> cm³</dd>
                                            
                                            <dt>Triangulos</dt>
                                            <dd><?= htmlspecialchars(isset($validationData['mesh']) ? number_format($validationData['mesh']['triangles']) : 'N/A', ENT_QUOTES, 'UTF-8') ?></dd>
                                        </dl>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Configurações de Impressão -->
                            <?php if (isset($queueItem['print_settings']) && !empty($queueItem['print_settings'])): ?>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6>Configurações de Impressão</h6>
                                        <table class="table table-sm table-bordered">
                                            <tbody>
                                                <?php if (isset($queueItem['print_settings']['scale'])): ?>
                                                    <tr>
                                                        <th>Escala</th>
                                                        <td><?= htmlspecialchars($queueItem['print_settings']['scale'], ENT_QUOTES, 'UTF-8') ?>x</td>
                                                    </tr>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($queueItem['print_settings']['layer_height'])): ?>
                                                    <tr>
                                                        <th>Altura da Camada</th>
                                                        <td><?= htmlspecialchars($queueItem['print_settings']['layer_height'], ENT_QUOTES, 'UTF-8') ?> mm</td>
                                                    </tr>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($queueItem['print_settings']['infill'])): ?>
                                                    <tr>
                                                        <th>Preenchimento</th>
                                                        <td><?= htmlspecialchars($queueItem['print_settings']['infill'], ENT_QUOTES, 'UTF-8') ?>%</td>
                                                    </tr>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($queueItem['print_settings']['supports'])): ?>
                                                    <tr>
                                                        <th>Suportes</th>
                                                        <td><?= $queueItem['print_settings']['supports'] ? 'Sim' : 'Não' ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($queueItem['print_settings']['material'])): ?>
                                                    <tr>
                                                        <th>Material</th>
                                                        <td><?= htmlspecialchars($queueItem['print_settings']['material'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($queueItem['print_settings']['color'])): ?>
                                                    <tr>
                                                        <th>Cor</th>
                                                        <td><?= htmlspecialchars($queueItem['print_settings']['color'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($queueItem['print_settings']['estimated_print_time_hours'])): ?>
                                                    <tr>
                                                        <th>Tempo Est. de Impressão</th>
                                                        <td><?= htmlspecialchars($queueItem['print_settings']['estimated_print_time_hours'], ENT_QUOTES, 'UTF-8') ?> horas</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Histórico de Atividades -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Histórico de Atividades</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($history)): ?>
                        <div class="alert alert-info">
                            Nenhum evento registrado para este item.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Tipo</th>
                                        <th>Descrição</th>
                                        <th>Realizado por</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $event): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($event['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?php
                                                $eventTypeClass = '';
                                                $eventTypeText = '';
                                                
                                                switch($event['event_type']) {
                                                    case 'creation':
                                                        $eventTypeClass = 'badge-success';
                                                        $eventTypeText = 'Criação';
                                                        break;
                                                    case 'status_change':
                                                        $eventTypeClass = 'badge-primary';
                                                        $eventTypeText = 'Mudança de Status';
                                                        break;
                                                    case 'priority_change':
                                                        $eventTypeClass = 'badge-warning';
                                                        $eventTypeText = 'Mudança de Prioridade';
                                                        break;
                                                    default:
                                                        $eventTypeClass = 'badge-secondary';
                                                        $eventTypeText = $event['event_type'];
                                                }
                                                ?>
                                                <span class="badge <?= $eventTypeClass ?>"><?= $eventTypeText ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($event['user_name'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?></td>
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

<!-- Modal para Atribuir à Impressora -->
<div class="modal fade" id="assignModal" tabindex="-1" role="dialog" aria-labelledby="assignModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="/print-job/assign">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="queue_id" value="<?= htmlspecialchars($queueItem['id'], ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="assignModalLabel">Atribuir à Impressora</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="printer_id">Selecione a Impressora:</label>
                        <select name="printer_id" id="printer_id" class="form-control" required>
                            <option value="">Selecione uma impressora...</option>
                            <?php foreach ($printers as $printer): ?>
                                <?php if ($printer['status'] === 'available'): ?>
                                    <option value="<?= htmlspecialchars($printer['id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($printer['name'], ENT_QUOTES, 'UTF-8') ?> 
                                        (<?= htmlspecialchars($printer['model'], ENT_QUOTES, 'UTF-8') ?>)
                                    </option>
                                <?php else: ?>
                                    <option value="<?= htmlspecialchars($printer['id'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                                        <?= htmlspecialchars($printer['name'], ENT_QUOTES, 'UTF-8') ?> 
                                        (<?= htmlspecialchars($printer['model'], ENT_QUOTES, 'UTF-8') ?>) - 
                                        <?= htmlspecialchars($printer['status'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="scheduled_start_time">Agendamento (opcional):</label>
                        <input type="datetime-local" name="scheduled_start_time" id="scheduled_start_time" class="form-control">
                        <small class="form-text text-muted">Deixe em branco para iniciar assim que possível.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notas:</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Atribuir à Impressora</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Ajustar Prioridade -->
<div class="modal fade" id="priorityModal" tabindex="-1" role="dialog" aria-labelledby="priorityModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="/print-queue/updatePriority">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="queue_id" value="<?= htmlspecialchars($queueItem['id'], ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="priorityModalLabel">Ajustar Prioridade</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="priority">Prioridade (1-10):</label>
                        <input type="range" class="custom-range" name="priority" id="priority" min="1" max="10" value="<?= htmlspecialchars($queueItem['priority'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="d-flex justify-content-between">
                            <span>Baixa (1)</span>
                            <span id="priorityValue"><?= htmlspecialchars($queueItem['priority'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span>Alta (10)</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Prioridade</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Cancelar Item -->
<div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="/print-queue/cancel">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="queue_id" value="<?= htmlspecialchars($queueItem['id'], ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">Cancelar Item da Fila</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-danger">Esta ação não pode ser desfeita. O item será marcado como cancelado.</p>
                    
                    <div class="form-group">
                        <label for="cancel_notes">Motivo do Cancelamento:</label>
                        <textarea name="notes" id="cancel_notes" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Voltar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Cancelamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar valor da prioridade no slider
    const prioritySlider = document.getElementById('priority');
    const priorityValue = document.getElementById('priorityValue');
    
    if (prioritySlider && priorityValue) {
        prioritySlider.addEventListener('input', function() {
            priorityValue.textContent = this.value;
        });
    }
});
</script>
