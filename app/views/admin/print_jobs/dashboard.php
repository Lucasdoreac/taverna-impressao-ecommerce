<?php
/**
 * View - Dashboard de Impressão (Admin)
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
                    <a href="/print-jobs" class="btn btn-info">
                        <i class="fa fa-print"></i> Todos os Trabalhos
                    </a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_type'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['flash_message'], ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            
            <!-- Resumo das Estatísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Impressões Ativas</h5>
                            <p class="card-text display-4"><?= htmlspecialchars($statistics['by_status']['printing'] ?? 0, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Impressões Hoje</h5>
                            <p class="card-text display-4"><?= htmlspecialchars($statistics['today_count'] ?? 0, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Tempo Médio</h5>
                            <p class="card-text display-4"><?= htmlspecialchars($statistics['avg_print_time'] ?? 0, ENT_QUOTES, 'UTF-8') ?>h</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">Rendimento do Mês</h5>
                            <p class="card-text display-4"><?= htmlspecialchars($statistics['month_success_rate'] ?? 0, ENT_QUOTES, 'UTF-8') ?>%</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Trabalhos Ativos -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Trabalhos em Andamento</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($activeJobs)): ?>
                        <div class="alert alert-info">
                            Não há trabalhos ativos no momento.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($activeJobs as $job): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 border-primary">
                                        <div class="card-header bg-primary text-white">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0"><?= htmlspecialchars($job['model_name'], ENT_QUOTES, 'UTF-8') ?></h6>
                                                <span class="badge badge-light">ID: <?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <strong>Impressora:</strong> <?= htmlspecialchars($job['printer_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <div class="mb-3">
                                                <strong>Cliente:</strong> <?= htmlspecialchars($job['user_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <div class="mb-3">
                                                <strong>Início:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['started_at'])), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <strong>Progresso:</strong>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                        role="progressbar" 
                                                        style="width: <?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>%"
                                                        aria-valuenow="<?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100">
                                                        <?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>%
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <strong>Tempo estimado restante:</strong>
                                                <?php
                                                    // Calcular tempo restante com base na porcentagem e tempo total estimado
                                                    $totalEstimated = isset($job['print_settings']['estimated_print_time_hours']) 
                                                                    ? floatval($job['print_settings']['estimated_print_time_hours']) 
                                                                    : 2.0;
                                                    $progress = floatval($job['progress']);
                                                    $remainingTime = $totalEstimated * (1 - ($progress / 100));
                                                    $remainingHours = floor($remainingTime);
                                                    $remainingMinutes = round(($remainingTime - $remainingHours) * 60);
                                                    
                                                    echo htmlspecialchars("{$remainingHours}h {$remainingMinutes}min", ENT_QUOTES, 'UTF-8');
                                                ?>
                                            </div>
                                        </div>
                                        <div class="card-footer">
                                            <div class="btn-group d-flex">
                                                <a href="/print-jobs/details/<?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-info">
                                                    <i class="fa fa-eye"></i> Detalhes
                                                </a>
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
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Status das Impressoras -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Status das Impressoras</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($printers as $printer): ?>
                            <div class="col-md-4 col-lg-3 mb-3">
                                <div class="card h-100 
                                    <?php if ($printer['status'] === 'available'): ?>
                                        border-success
                                    <?php elseif ($printer['status'] === 'printing'): ?>
                                        border-primary
                                    <?php elseif ($printer['status'] === 'maintenance'): ?>
                                        border-warning
                                    <?php else: ?>
                                        border-danger
                                    <?php endif; ?>">
                                    <div class="card-header 
                                        <?php if ($printer['status'] === 'available'): ?>
                                            bg-success text-white
                                        <?php elseif ($printer['status'] === 'printing'): ?>
                                            bg-primary text-white
                                        <?php elseif ($printer['status'] === 'maintenance'): ?>
                                            bg-warning text-dark
                                        <?php else: ?>
                                            bg-danger text-white
                                        <?php endif; ?>">
                                        <h6 class="mb-0"><?= htmlspecialchars($printer['name'], ENT_QUOTES, 'UTF-8') ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <strong>Modelo:</strong> <?= htmlspecialchars($printer['model'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Status:</strong>
                                            <?php
                                            $statusText = '';
                                            
                                            switch($printer['status']) {
                                                case 'available':
                                                    $statusText = 'Disponível';
                                                    break;
                                                case 'printing':
                                                    $statusText = 'Imprimindo';
                                                    break;
                                                case 'maintenance':
                                                    $statusText = 'Em Manutenção';
                                                    break;
                                                case 'error':
                                                    $statusText = 'Erro';
                                                    break;
                                                default:
                                                    $statusText = $printer['status'];
                                            }
                                            
                                            echo htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8');
                                            ?>
                                        </div>
                                        
                                        <?php if ($printer['status'] === 'printing'): ?>
                                            <div class="mb-2">
                                                <strong>Trabalho Atual:</strong>
                                                <?php
                                                    // Encontrar o trabalho atribuído a esta impressora
                                                    $currentJob = null;
                                                    foreach ($activeJobs as $job) {
                                                        if ($job['printer_id'] == $printer['id']) {
                                                            $currentJob = $job;
                                                            break;
                                                        }
                                                    }
                                                ?>
                                                <?php if ($currentJob): ?>
                                                    <a href="/print-jobs/details/<?= htmlspecialchars($currentJob['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars($currentJob['model_name'], ENT_QUOTES, 'UTF-8') ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Não encontrado</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mb-2">
                                            <strong>Material:</strong> <?= htmlspecialchars($printer['material'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        
                                        <?php if (!empty($printer['notes'])): ?>
                                            <div class="mt-3">
                                                <small class="text-muted"><?= htmlspecialchars($printer['notes'], ENT_QUOTES, 'UTF-8') ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Próximos Trabalhos Agendados -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">Próximos Trabalhos Agendados</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($scheduledJobs)): ?>
                        <div class="alert alert-info">
                            Não há trabalhos agendados no momento.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Modelo</th>
                                        <th>Cliente</th>
                                        <th>Impressora</th>
                                        <th>Agendado para</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($scheduledJobs as $job): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($job['model_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($job['user_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($job['printer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['scheduled_start_time'])), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="/print-jobs/details/<?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-info">
                                                        <i class="fa fa-eye"></i> Detalhes
                                                    </a>
                                                    
                                                    <?php 
                                                    // Se a data agendada já passou ou está próxima (menos de 1 hora)
                                                    $now = new DateTime();
                                                    $scheduledTime = new DateTime($job['scheduled_start_time']);
                                                    $diff = $now->diff($scheduledTime);
                                                    
                                                    $canStart = $now >= $scheduledTime || ($diff->days == 0 && $diff->h < 1);
                                                    
                                                    if ($canStart):
                                                    ?>
                                                        <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#startModal"
                                                                data-id="<?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-model="<?= htmlspecialchars($job['model_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="fa fa-play"></i> Iniciar Agora
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

<script>
document.addEventListener('DOMContentLoaded', function() {
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
    
    // Configurar modal de iniciar
    $('#startModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const jobId = button.data('id');
        const modelName = button.data('model');
        
        const modal = $(this);
        modal.find('#startJobId').val(jobId);
        modal.find('#startModelName').text(modelName);
    });
    
    // Atualizar a página a cada 5 minutos para manter os dados frescos
    setTimeout(function() {
        location.reload();
    }, 5 * 60 * 1000);
});
</script>
