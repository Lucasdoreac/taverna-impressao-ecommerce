<?php
/**
 * View - Detalhes do Item na Fila de Impressão (Cliente)
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Views/Customer
 * @version    1.0.0
 */

// Garantir que este arquivo não seja acessado diretamente
defined('BASEPATH') or exit('Acesso direto ao script não é permitido');
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                <a href="/user/print-queue" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Voltar para Minha Fila
                </a>
            </div>
            
            <?php if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_type'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['flash_message'], ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            
            <!-- Status Atual -->
            <div class="card mb-4">
                <div class="card-body">
                    <?php
                    $statusClass = '';
                    $statusText = '';
                    $bgClass = '';
                    
                    switch($queueItem['status']) {
                        case 'pending':
                            $statusClass = 'text-warning';
                            $statusText = 'Pendente';
                            $bgClass = 'bg-warning-light';
                            break;
                        case 'assigned':
                            $statusClass = 'text-info';
                            $statusText = 'Atribuído';
                            $bgClass = 'bg-info-light';
                            break;
                        case 'printing':
                            $statusClass = 'text-primary';
                            $statusText = 'Em Impressão';
                            $bgClass = 'bg-primary-light';
                            break;
                        case 'completed':
                            $statusClass = 'text-success';
                            $statusText = 'Concluído';
                            $bgClass = 'bg-success-light';
                            break;
                        case 'cancelled':
                            $statusClass = 'text-secondary';
                            $statusText = 'Cancelado';
                            $bgClass = 'bg-secondary-light';
                            break;
                        case 'failed':
                            $statusClass = 'text-danger';
                            $statusText = 'Falha';
                            $bgClass = 'bg-danger-light';
                            break;
                        default:
                            $statusClass = 'text-dark';
                            $statusText = $queueItem['status'];
                            $bgClass = '';
                    }
                    ?>
                    <div class="row">
                        <div class="col-md-8">
                            <h4>Status Atual: <span class="<?= $statusClass ?>"><?= $statusText ?></span></h4>
                            <p class="lead">Modelo: <strong><?= htmlspecialchars($model['original_name'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                            
                            <?php if ($queueItem['status'] === 'printing'): ?>
                                <div class="mt-3">
                                    <p><strong>Previsão de Conclusão:</strong>
                                    <?php
                                        // Calcular previsão com base no status do trabalho vinculado, se existir
                                        if (isset($job) && !empty($job)) {
                                            $startTime = new DateTime($job['started_at']);
                                            $totalEstimated = isset($queueItem['print_settings']['estimated_print_time_hours']) 
                                                            ? floatval($queueItem['print_settings']['estimated_print_time_hours']) 
                                                            : 2.0;
                                            $progress = floatval($job['progress']);
                                            $remainingTime = $totalEstimated * (1 - ($progress / 100));
                                            
                                            $estimatedEnd = clone $startTime;
                                            $estimatedEnd->add(new DateInterval('PT' . ceil($totalEstimated * 60) . 'M'));
                                            
                                            echo htmlspecialchars(date_format($estimatedEnd, 'd/m/Y H:i'), ENT_QUOTES, 'UTF-8');
                                        } else {
                                            echo "Não disponível";
                                        }
                                    ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-md-right">
                            <p><strong>Adicionado em:</strong><br><?= htmlspecialchars(date('d/m/Y H:i', strtotime($queueItem['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
                            
                            <?php if ($queueItem['status'] !== 'pending' && $queueItem['status'] !== 'cancelled'): ?>
                                <p><strong>Última Atualização:</strong><br>
                                    <?php if (isset($queueItem['updated_at']) && $queueItem['updated_at']): ?>
                                        <?= htmlspecialchars(date('d/m/Y H:i', strtotime($queueItem['updated_at'])), ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($queueItem['status'] !== 'completed' && $queueItem['status'] !== 'cancelled'): ?>
                        <div class="mt-3">
                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#cancelModal">
                                <i class="fa fa-times"></i> Cancelar Item
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Informações do Item na Fila -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informações do Modelo</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <tbody>
                                    <tr>
                                        <th>Nome do Arquivo</th>
                                        <td><?= htmlspecialchars($model['original_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Formato</th>
                                        <td><?= htmlspecialchars($model['file_extension'], ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                    
                                    <?php
                                    $validationData = isset($model['validation_data']) && !empty($model['validation_data'])
                                                    ? (is_array($model['validation_data']) ? $model['validation_data'] : json_decode($model['validation_data'], true))
                                                    : [];
                                    ?>
                                    
                                    <?php if (!empty($validationData) && isset($validationData['size'])): ?>
                                        <tr>
                                            <th>Dimensões</th>
                                            <td>
                                                <?= htmlspecialchars($validationData['size']['width'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> x 
                                                <?= htmlspecialchars($validationData['size']['height'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> x 
                                                <?= htmlspecialchars($validationData['size']['depth'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> mm
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Volume</th>
                                            <td><?= htmlspecialchars($validationData['size']['volume'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> cm³</td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($validationData) && isset($validationData['mesh'])): ?>
                                        <tr>
                                            <th>Triangulos</th>
                                            <td><?= htmlspecialchars(number_format($validationData['mesh']['triangles']), ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <tr>
                                        <th>Data de Envio</th>
                                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($model['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Data de Aprovação</th>
                                        <td>
                                            <?php if (isset($model['approved_at']) && $model['approved_at']): ?>
                                                <?= htmlspecialchars(date('d/m/Y', strtotime($model['approved_at'])), ENT_QUOTES, 'UTF-8') ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Configurações de Impressão</h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($queueItem['print_settings']) && !empty($queueItem['print_settings'])): ?>
                                <table class="table table-striped">
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
                            <?php else: ?>
                                <div class="alert alert-info">
                                    Não há configurações específicas de impressão para este modelo.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($queueItem['notes'])): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Notas Adicionais</h5>
                            </div>
                            <div class="card-body">
                                <p><?= nl2br(htmlspecialchars($queueItem['notes'], ENT_QUOTES, 'UTF-8')) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
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
                        <div class="timeline">
                            <?php foreach ($history as $index => $event): ?>
                                <div class="timeline-item <?= $index % 2 === 0 ? 'left' : 'right' ?>">
                                    <div class="timeline-date">
                                        <?= htmlspecialchars(date('d/m/Y H:i', strtotime($event['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="timeline-content">
                                        <?php
                                        $eventIcon = '';
                                        $eventClass = '';
                                        
                                        switch($event['event_type']) {
                                            case 'creation':
                                                $eventIcon = 'fa-plus-circle';
                                                $eventClass = 'bg-success text-white';
                                                break;
                                            case 'status_change':
                                                $eventIcon = 'fa-exchange-alt';
                                                $eventClass = 'bg-primary text-white';
                                                break;
                                            case 'priority_change':
                                                $eventIcon = 'fa-sort-amount-up';
                                                $eventClass = 'bg-warning text-dark';
                                                break;
                                            default:
                                                $eventIcon = 'fa-info-circle';
                                                $eventClass = 'bg-secondary text-white';
                                        }
                                        ?>
                                        <div class="card">
                                            <div class="card-header <?= $eventClass ?>">
                                                <i class="fa <?= $eventIcon ?>"></i>
                                                <?= htmlspecialchars($event['event_type'], ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <div class="card-body">
                                                <p><?= htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i> Atenção: Esta ação não pode ser desfeita.
                    </div>
                    <p>Ao cancelar este item, ele será removido da fila de impressão e não poderá ser retomado.</p>
                    
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

<style>
.bg-warning-light { background-color: rgba(255, 193, 7, 0.1); }
.bg-info-light { background-color: rgba(23, 162, 184, 0.1); }
.bg-primary-light { background-color: rgba(0, 123, 255, 0.1); }
.bg-success-light { background-color: rgba(40, 167, 69, 0.1); }
.bg-secondary-light { background-color: rgba(108, 117, 125, 0.1); }
.bg-danger-light { background-color: rgba(220, 53, 69, 0.1); }

/* Timeline Styles */
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline:before {
    content: '';
    position: absolute;
    height: 100%;
    width: 2px;
    background-color: #e9ecef;
    left: 50%;
    transform: translateX(-50%);
}

.timeline-item {
    margin-bottom: 30px;
    position: relative;
    width: 50%;
}

.timeline-item.left {
    left: 0;
    padding-right: 40px;
}

.timeline-item.right {
    left: 50%;
    padding-left: 40px;
}

.timeline-date {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 10px;
}

.timeline-content {
    position: relative;
}

.timeline-item.left .timeline-content:before {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background-color: #007bff;
    right: -48px;
    top: 10px;
    z-index: 1;
}

.timeline-item.right .timeline-content:before {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background-color: #007bff;
    left: -48px;
    top: 10px;
    z-index: 1;
}

@media (max-width: 767.98px) {
    .timeline:before {
        left: 20px;
    }
    
    .timeline-item {
        width: 100%;
        padding-left: 50px;
        padding-right: 0;
    }
    
    .timeline-item.left, .timeline-item.right {
        left: 0;
    }
    
    .timeline-item.left .timeline-content:before,
    .timeline-item.right .timeline-content:before {
        left: -38px;
        right: auto;
    }
}
</style>
