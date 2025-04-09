<?php
/**
 * View - Administração da Fila de Impressão
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
            <h1 class="h3 mb-4"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
            
            <?php if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_type'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['flash_message'], ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            
            <!-- Resumo das estatísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total na Fila</h5>
                            <p class="card-text display-4"><?= htmlspecialchars($statistics['total'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">Pendentes</h5>
                            <p class="card-text display-4"><?= htmlspecialchars($statistics['by_status']['pending'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Em Impressão</h5>
                            <p class="card-text display-4"><?= htmlspecialchars($statistics['by_status']['printing'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Concluídos</h5>
                            <p class="card-text display-4"><?= htmlspecialchars($statistics['by_status']['completed'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Filtros</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="/print-queue" class="form-row align-items-end">
                        <div class="form-group col-md-3">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">Todos</option>
                                <option value="pending" <?= isset($currentFilters['status']) && $currentFilters['status'] === 'pending' ? 'selected' : '' ?>>Pendente</option>
                                <option value="assigned" <?= isset($currentFilters['status']) && $currentFilters['status'] === 'assigned' ? 'selected' : '' ?>>Atribuído</option>
                                <option value="printing" <?= isset($currentFilters['status']) && $currentFilters['status'] === 'printing' ? 'selected' : '' ?>>Em Impressão</option>
                                <option value="completed" <?= isset($currentFilters['status']) && $currentFilters['status'] === 'completed' ? 'selected' : '' ?>>Concluído</option>
                                <option value="cancelled" <?= isset($currentFilters['status']) && $currentFilters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelado</option>
                                <option value="failed" <?= isset($currentFilters['status']) && $currentFilters['status'] === 'failed' ? 'selected' : '' ?>>Falha</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="user_id">Usuário</label>
                            <input type="text" name="user_id" id="user_id" class="form-control" placeholder="ID do usuário" value="<?= isset($currentFilters['user_id']) ? htmlspecialchars($currentFilters['user_id'], ENT_QUOTES, 'UTF-8') : '' ?>">
                        </div>
                        <div class="form-group col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                        <div class="form-group col-md-2">
                            <a href="/print-queue" class="btn btn-secondary w-100">Limpar Filtros</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabela da Fila de Impressão -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Itens na Fila de Impressão</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($queueItems)): ?>
                        <div class="alert alert-info">
                            Nenhum item encontrado na fila de impressão com os critérios selecionados.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Modelo</th>
                                        <th>Usuário</th>
                                        <th>Status</th>
                                        <th>Prioridade</th>
                                        <th>Data de Criação</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($queueItems as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($item['model_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?= htmlspecialchars($item['user_name'], ENT_QUOTES, 'UTF-8') ?><br>
                                                <small><?= htmlspecialchars($item['user_email'], ENT_QUOTES, 'UTF-8') ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                $statusText = '';
                                                
                                                switch($item['status']) {
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
                                                        $statusText = $item['status'];
                                                }
                                                ?>
                                                <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $priorityClass = '';
                                                
                                                if ($item['priority'] >= 8) {
                                                    $priorityClass = 'text-danger font-weight-bold';
                                                } elseif ($item['priority'] >= 5) {
                                                    $priorityClass = 'text-warning font-weight-bold';
                                                }
                                                ?>
                                                <span class="<?= $priorityClass ?>"><?= htmlspecialchars($item['priority'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </td>
                                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($item['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="/print-queue/details/<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-info">
                                                        <i class="fa fa-eye"></i> Detalhes
                                                    </a>
                                                    
                                                    <?php if ($item['status'] === 'pending'): ?>
                                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#assignModal" 
                                                                data-id="<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-model="<?= htmlspecialchars($item['model_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="fa fa-print"></i> Atribuir
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($item['status'] !== 'completed' && $item['status'] !== 'cancelled'): ?>
                                                        <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#cancelModal" 
                                                                data-id="<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-model="<?= htmlspecialchars($item['model_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="fa fa-times"></i> Cancelar
                                                        </button>
                                                    <?php endif; ?>
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

<!-- Modal para Atribuir Modelo a uma Impressora -->
<div class="modal fade" id="assignModal" tabindex="-1" role="dialog" aria-labelledby="assignModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="/print-job/assign">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="queue_id" id="assignQueueId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="assignModalLabel">Atribuir à Impressora</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Você está atribuindo o modelo: <strong id="assignModelName"></strong></p>
                    
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

<!-- Modal para Cancelar Item da Fila -->
<div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="/print-queue/cancel">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="queue_id" id="cancelQueueId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">Cancelar Item da Fila</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Você está cancelando o modelo: <strong id="cancelModelName"></strong></p>
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
    // Configurar modais de atribuição
    $('#assignModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const queueId = button.data('id');
        const modelName = button.data('model');
        
        const modal = $(this);
        modal.find('#assignQueueId').val(queueId);
        modal.find('#assignModelName').text(modelName);
    });
    
    // Configurar modais de cancelamento
    $('#cancelModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const queueId = button.data('id');
        const modelName = button.data('model');
        
        const modal = $(this);
        modal.find('#cancelQueueId').val(queueId);
        modal.find('#cancelModelName').text(modelName);
    });
});
</script>
