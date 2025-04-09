<?php
/**
 * View - Gerenciamento de Trabalhos de Impressão (Admin)
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
                <div>
                    <a href="/print-queue" class="btn btn-secondary mr-2">
                        <i class="fa fa-list"></i> Fila de Impressão
                    </a>
                    <a href="/print-jobs/dashboard" class="btn btn-primary">
                        <i class="fa fa-tachometer-alt"></i> Dashboard
                    </a>
                </div>
            </div>
            
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
                            <h5 class="card-title">Total de Trabalhos</h5>
                            <p class="card-text display-4"><?= htmlspecialchars($statistics['total'] ?? 0, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Em Andamento</h5>
                            <p class="card-text display-4"><?= htmlspecialchars(($statistics['by_status']['printing'] ?? 0) + ($statistics['by_status']['preparing'] ?? 0), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Concluídos</h5>
                            <p class="card-text display-4"><?= htmlspecialchars($statistics['by_status']['completed'] ?? 0, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h5 class="card-title">Falhas</h5>
                            <p class="card-text display-4"><?= htmlspecialchars($statistics['by_status']['failed'] ?? 0, ENT_QUOTES, 'UTF-8') ?></p>
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
                    <form method="get" action="/print-jobs" class="form-row align-items-end">
                        <div class="form-group col-md-3">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">Todos</option>
                                <option value="pending" <?= isset($currentFilters['status']) && $currentFilters['status'] === 'pending' ? 'selected' : '' ?>>Pendente</option>
                                <option value="preparing" <?= isset($currentFilters['status']) && $currentFilters['status'] === 'preparing' ? 'selected' : '' ?>>Preparando</option>
                                <option value="printing" <?= isset($currentFilters['status']) && $currentFilters['status'] === 'printing' ? 'selected' : '' ?>>Imprimindo</option>
                                <option value="post-processing" <?= isset($currentFilters['status']) && $currentFilters['status'] === 'post-processing' ? 'selected' : '' ?>>Pós-processamento</option>
                                <option value="completed" <?= isset($currentFilters['status']) && $currentFilters['status'] === 'completed' ? 'selected' : '' ?>>Concluído</option>
                                <option value="failed" <?= isset($currentFilters['status']) && $currentFilters['status'] === 'failed' ? 'selected' : '' ?>>Falha</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="printer_id">Impressora</label>
                            <select name="printer_id" id="printer_id" class="form-control">
                                <option value="">Todas</option>
                                <?php foreach ($printers as $printer): ?>
                                    <option value="<?= htmlspecialchars($printer['id'], ENT_QUOTES, 'UTF-8') ?>" <?= isset($currentFilters['printer_id']) && $currentFilters['printer_id'] == $printer['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($printer['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                        <div class="form-group col-md-2">
                            <a href="/print-jobs" class="btn btn-secondary w-100">Limpar Filtros</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tabela de Trabalhos de Impressão -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Trabalhos de Impressão</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($jobs)): ?>
                        <div class="alert alert-info">
                            Nenhum trabalho de impressão encontrado com os critérios selecionados.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Modelo</th>
                                        <th>Cliente</th>
                                        <th>Impressora</th>
                                        <th>Status</th>
                                        <th>Progresso</th>
                                        <th>Início</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jobs as $job): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($job['model_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?= htmlspecialchars($job['user_name'], ENT_QUOTES, 'UTF-8') ?><br>
                                                <small><?= htmlspecialchars($job['user_email'], ENT_QUOTES, 'UTF-8') ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($job['printer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                $statusText = '';
                                                
                                                switch($job['status']) {
                                                    case 'pending':
                                                        $statusClass = 'badge-secondary';
                                                        $statusText = 'Pendente';
                                                        break;
                                                    case 'preparing':
                                                        $statusClass = 'badge-warning';
                                                        $statusText = 'Preparando';
                                                        break;
                                                    case 'printing':
                                                        $statusClass = 'badge-primary';
                                                        $statusText = 'Imprimindo';
                                                        break;
                                                    case 'post-processing':
                                                        $statusClass = 'badge-info';
                                                        $statusText = 'Pós-processamento';
                                                        break;
                                                    case 'completed':
                                                        $statusClass = 'badge-success';
                                                        $statusText = 'Concluído';
                                                        break;
                                                    case 'failed':
                                                        $statusClass = 'badge-danger';
                                                        $statusText = 'Falha';
                                                        break;
                                                    default:
                                                        $statusClass = 'badge-light';
                                                        $statusText = $job['status'];
                                                }
                                                ?>
                                                <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                            </td>
                                            <td>
                                                <?php if ($job['status'] === 'printing'): ?>
                                                    <div class="progress">
                                                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                            role="progressbar" 
                                                            style="width: <?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>%"
                                                            aria-valuenow="<?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>" 
                                                            aria-valuemin="0" 
                                                            aria-valuemax="100">
                                                            <?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>%
                                                        </div>
                                                    </div>
                                                <?php elseif ($job['status'] === 'completed'): ?>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-success" 
                                                            role="progressbar" 
                                                            style="width: 100%"
                                                            aria-valuenow="100" 
                                                            aria-valuemin="0" 
                                                            aria-valuemax="100">
                                                            100%
                                                        </div>
                                                    </div>
                                                <?php elseif ($job['status'] === 'failed'): ?>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-danger" 
                                                            role="progressbar" 
                                                            style="width: <?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>%"
                                                            aria-valuenow="<?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>" 
                                                            aria-valuemin="0" 
                                                            aria-valuemax="100">
                                                            <?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>%
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($job['started_at']) && $job['started_at']): ?>
                                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['started_at'])), ENT_QUOTES, 'UTF-8') ?>
                                                <?php elseif (isset($job['scheduled_start_time']) && $job['scheduled_start_time']): ?>
                                                    <span class="text-muted">Agendado para:</span><br>
                                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['scheduled_start_time'])), ENT_QUOTES, 'UTF-8') ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="/print-jobs/details/<?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-info">
                                                        <i class="fa fa-eye"></i> Detalhes
                                                    </a>
                                                    
                                                    <?php if ($job['status'] === 'pending' || $job['status'] === 'preparing'): ?>
                                                        <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#startModal"
                                                                data-id="<?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-model="<?= htmlspecialchars($job['model_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="fa fa-play"></i> Iniciar
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($job['status'] === 'printing'): ?>
                                                        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#progressModal"
                                                                data-id="<?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-model="<?= htmlspecialchars($job['model_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-progress="<?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="fa fa-tasks"></i> Atualizar
                                                        </button>
                                                        
                                                        <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#completeModal"
                                                                data-id="<?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-model="<?= htmlspecialchars($job['model_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="fa fa-check"></i> Concluir
                                                        </button>
                                                        
                                                        <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#failModal"
                                                                data-id="<?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-model="<?= htmlspecialchars($job['model_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="fa fa-exclamation-triangle"></i> Falha
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

<!-- Modal para Iniciar Impressão -->
<div class="modal fade" id="startModal" tabindex="-1" role="dialog" aria-labelledby="startModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="/print-job/start">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="job_id" id="startJobId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="startModalLabel">Iniciar Impressão</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Você está iniciando a impressão do modelo: <strong id="startModelName"></strong></p>
                    <p>Certifique-se de que a impressora está preparada e com material suficiente.</p>
                    
                    <div class="form-group">
                        <label for="notes">Notas de Início:</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Iniciar Impressão</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Atualizar Progresso -->
<div class="modal fade" id="progressModal" tabindex="-1" role="dialog" aria-labelledby="progressModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="/print-job/updateProgress">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="job_id" id="progressJobId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="progressModalLabel">Atualizar Progresso</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Atualizando progresso do modelo: <strong id="progressModelName"></strong></p>
                    
                    <div class="form-group">
                        <label for="progress">Progresso Atual (%):</label>
                        <input type="range" class="custom-range" name="progress" id="progress" min="0" max="100" value="0">
                        <div class="d-flex justify-content-between">
                            <span>0%</span>
                            <span id="progressValue">0%</span>
                            <span>100%</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Atualizar Progresso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Concluir Impressão -->
<div class="modal fade" id="completeModal" tabindex="-1" role="dialog" aria-labelledby="completeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="/print-job/complete">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="job_id" id="completeJobId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="completeModalLabel">Concluir Impressão</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Você está marcando como concluída a impressão do modelo: <strong id="completeModelName"></strong></p>
                    
                    <div class="form-group">
                        <label for="material_used">Material Utilizado (g):</label>
                        <input type="number" name="material_used" id="material_used" class="form-control" min="0" step="0.1" placeholder="Quantidade em gramas">
                    </div>
                    
                    <div class="form-group">
                        <label for="complete_notes">Notas de Conclusão:</label>
                        <textarea name="notes" id="complete_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Marcar como Concluído</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Registrar Falha -->
<div class="modal fade" id="failModal" tabindex="-1" role="dialog" aria-labelledby="failModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="/print-job/fail">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="job_id" id="failJobId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="failModalLabel">Registrar Falha na Impressão</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Você está registrando uma falha na impressão do modelo: <strong id="failModelName"></strong></p>
                    
                    <div class="form-group">
                        <label for="reason">Motivo da Falha:</label>
                        <textarea name="reason" id="reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Registrar Falha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar modal de iniciar
    $('#startModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const jobId = button.data('id');
        const modelName = button.data('model');
        
        const modal = $(this);
        modal.find('#startJobId').val(jobId);
        modal.find('#startModelName').text(modelName);
    });
    
    // Configurar modal de progresso
    $('#progressModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const jobId = button.data('id');
        const modelName = button.data('model');
        const progress = button.data('progress');
        
        const modal = $(this);
        modal.find('#progressJobId').val(jobId);
        modal.find('#progressModelName').text(modelName);
        modal.find('#progress').val(progress);
        modal.find('#progressValue').text(progress + '%');
    });
    
    // Atualizar valor de progresso no slider
    const progressSlider = document.getElementById('progress');
    const progressValue = document.getElementById('progressValue');
    
    if (progressSlider && progressValue) {
        progressSlider.addEventListener('input', function() {
            progressValue.textContent = this.value + '%';
        });
    }
    
    // Configurar modal de conclusão
    $('#completeModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const jobId = button.data('id');
        const modelName = button.data('model');
        
        const modal = $(this);
        modal.find('#completeJobId').val(jobId);
        modal.find('#completeModelName').text(modelName);
    });
    
    // Configurar modal de falha
    $('#failModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const jobId = button.data('id');
        const modelName = button.data('model');
        
        const modal = $(this);
        modal.find('#failJobId').val(jobId);
        modal.find('#failModelName').text(modelName);
    });
});
</script>
