<?php
/**
 * View - Detalhes do Trabalho de Impressão (Admin)
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
                    <a href="/print-jobs" class="btn btn-secondary mr-2">
                        <i class="fa fa-list"></i> Todos os Trabalhos
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
            
            <!-- Status e Ações -->
            <div class="card mb-4">
                <div class="card-body">
                    <?php
                    $statusClass = '';
                    $statusText = '';
                    $statusBg = '';
                    
                    switch($job['status']) {
                        case 'pending':
                            $statusClass = 'text-secondary';
                            $statusText = 'Pendente';
                            $statusBg = 'bg-secondary-light';
                            break;
                        case 'preparing':
                            $statusClass = 'text-warning';
                            $statusText = 'Preparando';
                            $statusBg = 'bg-warning-light';
                            break;
                        case 'printing':
                            $statusClass = 'text-primary';
                            $statusText = 'Imprimindo';
                            $statusBg = 'bg-primary-light';
                            break;
                        case 'post-processing':
                            $statusClass = 'text-info';
                            $statusText = 'Pós-processamento';
                            $statusBg = 'bg-info-light';
                            break;
                        case 'completed':
                            $statusClass = 'text-success';
                            $statusText = 'Concluído';
                            $statusBg = 'bg-success-light';
                            break;
                        case 'failed':
                            $statusClass = 'text-danger';
                            $statusText = 'Falha';
                            $statusBg = 'bg-danger-light';
                            break;
                        default:
                            $statusClass = 'text-dark';
                            $statusText = $job['status'];
                            $statusBg = '';
                    }
                    ?>
                    <div class="row align-items-center <?= $statusBg ?> py-3">
                        <div class="col-md-6">
                            <h4>Status: <span class="<?= $statusClass ?>"><?= $statusText ?></span></h4>
                            <p class="lead mb-1">Modelo: <strong><?= htmlspecialchars($queueItem['model_name'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                            <p class="mb-1">
                                <strong>Cliente:</strong> <?= htmlspecialchars($queueItem['user_name'], ENT_QUOTES, 'UTF-8') ?> 
                                <a href="mailto:<?= htmlspecialchars($queueItem['user_email'], ENT_QUOTES, 'UTF-8') ?>" class="text-muted">
                                    <small>(<?= htmlspecialchars($queueItem['user_email'], ENT_QUOTES, 'UTF-8') ?>)</small>
                                </a>
                            </p>
                            <p>
                                <strong>Impressora:</strong> <?= htmlspecialchars($printer['name'], ENT_QUOTES, 'UTF-8') ?> 
                                (<?= htmlspecialchars($printer['model'], ENT_QUOTES, 'UTF-8') ?>)
                            </p>
                        </div>
                        <div class="col-md-6">
                            <?php if ($job['status'] === 'printing'): ?>
                                <div class="text-center">
                                    <div class="progress mb-3" style="height: 25px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                            role="progressbar" 
                                            style="width: <?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>%"
                                            aria-valuenow="<?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100">
                                            <?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>%
                                        </div>
                                    </div>
                                    
                                    <div class="btn-group mb-3">
                                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#progressModal">
                                            <i class="fa fa-tasks"></i> Atualizar Progresso
                                        </button>
                                        
                                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#completeModal">
                                            <i class="fa fa-check"></i> Marcar como Concluído
                                        </button>
                                        
                                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#failModal">
                                            <i class="fa fa-exclamation-triangle"></i> Registrar Falha
                                        </button>
                                    </div>
                                    
                                    <?php
                                    // Calcular tempo estimado restante
                                    $printSettings = isset($queueItem['print_settings']) && !empty($queueItem['print_settings'])
                                                ? (is_array($queueItem['print_settings']) ? $queueItem['print_settings'] : json_decode($queueItem['print_settings'], true))
                                                : [];
                                    
                                    $totalEstimated = isset($printSettings['estimated_print_time_hours']) 
                                                    ? floatval($printSettings['estimated_print_time_hours']) 
                                                    : 2.0;
                                    $progress = floatval($job['progress']);
                                    $remainingTime = $totalEstimated * (1 - ($progress / 100));
                                    $remainingHours = floor($remainingTime);
                                    $remainingMinutes = round(($remainingTime - $remainingHours) * 60);
                                    ?>
                                    
                                    <p>
                                        <strong>Tempo restante estimado:</strong><br>
                                        <?= htmlspecialchars("{$remainingHours}h {$remainingMinutes}min", ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                            <?php elseif ($job['status'] === 'pending' || $job['status'] === 'preparing'): ?>
                                <div class="text-center">
                                    <button type="button" class="btn btn-success btn-lg" data-toggle="modal" data-target="#startModal">
                                        <i class="fa fa-play"></i> Iniciar Impressão
                                    </button>
                                    
                                    <?php if (isset($job['scheduled_start_time']) && $job['scheduled_start_time']): ?>
                                        <p class="mt-3">
                                            <strong>Agendado para:</strong><br>
                                            <?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['scheduled_start_time'])), ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($job['status'] === 'post-processing'): ?>
                                <div class="text-center">
                                    <button type="button" class="btn btn-success btn-lg" data-toggle="modal" data-target="#completeModal">
                                        <i class="fa fa-check"></i> Marcar como Concluído
                                    </button>
                                    
                                    <button type="button" class="btn btn-danger ml-2" data-toggle="modal" data-target="#failModal">
                                        <i class="fa fa-exclamation-triangle"></i> Registrar Falha
                                    </button>
                                </div>
                            <?php elseif ($job['status'] === 'completed'): ?>
                                <div class="text-center">
                                    <div class="progress mb-3" style="height: 25px;">
                                        <div class="progress-bar bg-success" 
                                            role="progressbar" 
                                            style="width: 100%"
                                            aria-valuenow="100" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100">
                                            100%
                                        </div>
                                    </div>
                                    
                                    <?php if (isset($job['completed_at']) && $job['completed_at']): ?>
                                        <p>
                                            <strong>Concluído em:</strong><br>
                                            <?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['completed_at'])), ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($job['material_used']) && $job['material_used']): ?>
                                        <p>
                                            <strong>Material utilizado:</strong><br>
                                            <?= htmlspecialchars($job['material_used'], ENT_QUOTES, 'UTF-8') ?> gramas
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($job['status'] === 'failed'): ?>
                                <div class="text-center">
                                    <div class="alert alert-danger">
                                        <strong>Motivo da Falha:</strong><br>
                                        <?= nl2br(htmlspecialchars($job['failure_reason'] ?? 'Não especificado', ENT_QUOTES, 'UTF-8')) ?>
                                    </div>
                                    
                                    <?php if (isset($job['failed_at']) && $job['failed_at']): ?>
                                        <p>
                                            <strong>Falha registrada em:</strong><br>
                                            <?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['failed_at'])), ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detalhes e Configurações -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="jobDetailsTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="details-tab" data-toggle="tab" href="#details" role="tab" aria-controls="details" aria-selected="true">
                                        Detalhes do Trabalho
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="settings-tab" data-toggle="tab" href="#settings" role="tab" aria-controls="settings" aria-selected="false">
                                        Configurações de Impressão
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="notes-tab" data-toggle="tab" href="#notes" role="tab" aria-controls="notes" aria-selected="false">
                                        Notas
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="logs-tab" data-toggle="tab" href="#logs" role="tab" aria-controls="logs" aria-selected="false">
                                        Logs do Sistema
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="jobDetailsTabsContent">
                                <!-- Detalhes do Trabalho -->
                                <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
                                    <h5 class="card-title">Informações Detalhadas</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th style="width: 30%">ID do Trabalho</th>
                                                    <td><?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>
                                                <tr>
                                                    <th>ID do Item na Fila</th>
                                                    <td>
                                                        <a href="/print-queue/details/<?= htmlspecialchars($job['queue_id'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <?= htmlspecialchars($job['queue_id'], ENT_QUOTES, 'UTF-8') ?>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Cliente</th>
                                                    <td>
                                                        <?= htmlspecialchars($queueItem['user_name'], ENT_QUOTES, 'UTF-8') ?> 
                                                        (<a href="mailto:<?= htmlspecialchars($queueItem['user_email'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <?= htmlspecialchars($queueItem['user_email'], ENT_QUOTES, 'UTF-8') ?>
                                                        </a>)
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Impressora</th>
                                                    <td>
                                                        <?= htmlspecialchars($printer['name'], ENT_QUOTES, 'UTF-8') ?> 
                                                        (<?= htmlspecialchars($printer['model'], ENT_QUOTES, 'UTF-8') ?>)
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Data de Criação</th>
                                                    <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($job['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>
                                                <?php if (isset($job['scheduled_start_time']) && $job['scheduled_start_time']): ?>
                                                    <tr>
                                                        <th>Agendado para</th>
                                                        <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($job['scheduled_start_time'])), ENT_QUOTES, 'UTF-8') ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                <?php if (isset($job['started_at']) && $job['started_at']): ?>
                                                    <tr>
                                                        <th>Início da Impressão</th>
                                                        <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($job['started_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                <?php if (isset($job['completed_at']) && $job['completed_at']): ?>
                                                    <tr>
                                                        <th>Conclusão</th>
                                                        <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($job['completed_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                <?php if (isset($job['failed_at']) && $job['failed_at']): ?>
                                                    <tr>
                                                        <th>Falha Registrada</th>
                                                        <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($job['failed_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                <?php if ($job['status'] === 'printing'): ?>
                                                    <tr>
                                                        <th>Progresso Atual</th>
                                                        <td><?= htmlspecialchars($job['progress'], ENT_QUOTES, 'UTF-8') ?>%</td>
                                                    </tr>
                                                <?php endif; ?>
                                                <?php if (isset($job['material_used']) && $job['material_used']): ?>
                                                    <tr>
                                                        <th>Material Utilizado</th>
                                                        <td><?= htmlspecialchars($job['material_used'], ENT_QUOTES, 'UTF-8') ?> gramas</td>
                                                    </tr>
                                                <?php endif; ?>
                                                <?php if ($job['status'] === 'completed'): ?>
                                                    <tr>
                                                        <th>Tempo Total de Impressão</th>
                                                        <td>
                                                            <?php
                                                                $startTime = new DateTime($job['started_at']);
                                                                $endTime = new DateTime($job['completed_at']);
                                                                $interval = $startTime->diff($endTime);
                                                                
                                                                $hours = $interval->h + ($interval->days * 24);
                                                                echo htmlspecialchars("{$hours}h {$interval->i}m {$interval->s}s", ENT_QUOTES, 'UTF-8');
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                                <tr>
                                                    <th>Criado por</th>
                                                    <td><?= htmlspecialchars($job['created_by_name'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>
                                                <?php if (isset($job['last_updated_by_name']) && $job['last_updated_by_name']): ?>
                                                    <tr>
                                                        <th>Última Atualização por</th>
                                                        <td><?= htmlspecialchars($job['last_updated_by_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Configurações de Impressão -->
                                <div class="tab-pane fade" id="settings" role="tabpanel" aria-labelledby="settings-tab">
                                    <h5 class="card-title">Configurações de Impressão</h5>
                                    <?php
                                    $printSettings = isset($queueItem['print_settings']) && !empty($queueItem['print_settings'])
                                                ? (is_array($queueItem['print_settings']) ? $queueItem['print_settings'] : json_decode($queueItem['print_settings'], true))
                                                : [];
                                    ?>
                                    
                                    <?php if (!empty($printSettings)): ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered">
                                                <tbody>
                                                    <?php if (isset($printSettings['scale'])): ?>
                                                        <tr>
                                                            <th style="width: 30%">Escala</th>
                                                            <td><?= htmlspecialchars($printSettings['scale'], ENT_QUOTES, 'UTF-8') ?>x</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($printSettings['layer_height'])): ?>
                                                        <tr>
                                                            <th>Altura da Camada</th>
                                                            <td><?= htmlspecialchars($printSettings['layer_height'], ENT_QUOTES, 'UTF-8') ?> mm</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($printSettings['infill'])): ?>
                                                        <tr>
                                                            <th>Preenchimento</th>
                                                            <td><?= htmlspecialchars($printSettings['infill'], ENT_QUOTES, 'UTF-8') ?>%</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($printSettings['supports'])): ?>
                                                        <tr>
                                                            <th>Suportes</th>
                                                            <td><?= $printSettings['supports'] ? 'Sim' : 'Não' ?></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($printSettings['material'])): ?>
                                                        <tr>
                                                            <th>Material</th>
                                                            <td><?= htmlspecialchars($printSettings['material'], ENT_QUOTES, 'UTF-8') ?></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($printSettings['color'])): ?>
                                                        <tr>
                                                            <th>Cor</th>
                                                            <td><?= htmlspecialchars($printSettings['color'], ENT_QUOTES, 'UTF-8') ?></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($printSettings['estimated_print_time_hours'])): ?>
                                                        <tr>
                                                            <th>Tempo Est. de Impressão</th>
                                                            <td><?= htmlspecialchars($printSettings['estimated_print_time_hours'], ENT_QUOTES, 'UTF-8') ?> horas</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    
                                                    <?php
                                                    // Exibir quaisquer outras configurações personalizadas
                                                    foreach ($printSettings as $key => $value) {
                                                        if (!in_array($key, ['scale', 'layer_height', 'infill', 'supports', 'material', 'color', 'estimated_print_time_hours'])) {
                                                            echo '<tr>';
                                                            echo '<th>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $key)), ENT_QUOTES, 'UTF-8') . '</th>';
                                                            
                                                            // Formatação adequada do valor com base no tipo
                                                            if (is_bool($value)) {
                                                                echo '<td>' . ($value ? 'Sim' : 'Não') . '</td>';
                                                            } elseif (is_array($value) || is_object($value)) {
                                                                echo '<td><pre>' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') . '</pre></td>';
                                                            } else {
                                                                echo '<td>' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</td>';
                                                            }
                                                            
                                                            echo '</tr>';
                                                        }
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <?php
                                        // Se tivermos dados de validação do modelo, exibi-los
                                        $validationData = isset($model['validation_data']) && !empty($model['validation_data'])
                                                        ? (is_array($model['validation_data']) ? $model['validation_data'] : json_decode($model['validation_data'], true))
                                                        : [];
                                                        
                                        if (!empty($validationData)):
                                        ?>
                                            <h5 class="card-title mt-4">Dados Técnicos do Modelo</h5>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-bordered">
                                                    <tbody>
                                                        <?php if (isset($validationData['size'])): ?>
                                                            <tr>
                                                                <th style="width: 30%">Dimensões</th>
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
                                                        
                                                        <?php if (isset($validationData['mesh'])): ?>
                                                            <tr>
                                                                <th>Triângulos</th>
                                                                <td><?= htmlspecialchars(number_format($validationData['mesh']['triangles']), ENT_QUOTES, 'UTF-8') ?></td>
                                                            </tr>
                                                            
                                                            <?php if (isset($validationData['mesh']['manifold'])): ?>
                                                                <tr>
                                                                    <th>Manifold (Fechado)</th>
                                                                    <td><?= $validationData['mesh']['manifold'] ? 'Sim' : 'Não' ?></td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (isset($validationData['printability'])): ?>
                                                            <tr>
                                                                <th>Imprimível</th>
                                                                <td>
                                                                    <?php if ($validationData['printability']['printable']): ?>
                                                                        <span class="text-success">Sim</span>
                                                                    <?php else: ?>
                                                                        <span class="text-danger">Não</span>
                                                                        <br>
                                                                        <small class="text-danger">
                                                                            <?= htmlspecialchars($validationData['printability']['issues'] ?? 'Razão não especificada', ENT_QUOTES, 'UTF-8') ?>
                                                                        </small>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            Não há configurações específicas para este trabalho.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Notas -->
                                <div class="tab-pane fade" id="notes" role="tabpanel" aria-labelledby="notes-tab">
                                    <h5 class="card-title">Notas do Trabalho</h5>
                                    
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Notas do Cliente</h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (!empty($queueItem['notes'])): ?>
                                                <?= nl2br(htmlspecialchars($queueItem['notes'], ENT_QUOTES, 'UTF-8')) ?>
                                            <?php else: ?>
                                                <p class="text-muted">Nenhuma nota fornecida pelo cliente.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="card mb-3">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Notas Internas</h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (!empty($job['notes'])): ?>
                                                <?= nl2br(htmlspecialchars($job['notes'], ENT_QUOTES, 'UTF-8')) ?>
                                            <?php else: ?>
                                                <p class="text-muted">Nenhuma nota interna registrada.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($job['status'] === 'failed' && !empty($job['failure_reason'])): ?>
                                        <div class="card mb-3">
                                            <div class="card-header bg-danger text-white">
                                                <h6 class="mb-0">Motivo da Falha</h6>
                                            </div>
                                            <div class="card-body">
                                                <?= nl2br(htmlspecialchars($job['failure_reason'], ENT_QUOTES, 'UTF-8')) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Formulário para adicionar nota -->
                                    <div class="card">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0">Adicionar Nova Nota</h6>
                                        </div>
                                        <div class="card-body">
                                            <form method="post" action="/print-job/addNote">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                
                                                <div class="form-group">
                                                    <label for="note_content">Conteúdo da Nota:</label>
                                                    <textarea name="note_content" id="note_content" class="form-control" rows="4" required></textarea>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input" id="notify_customer" name="notify_customer" value="1">
                                                        <label class="custom-control-label" for="notify_customer">Notificar cliente sobre esta nota</label>
                                                    </div>
                                                </div>
                                                
                                                <button type="submit" class="btn btn-primary">Adicionar Nota</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Logs do Sistema -->
                                <div class="tab-pane fade" id="logs" role="tabpanel" aria-labelledby="logs-tab">
                                    <h5 class="card-title">Logs do Sistema</h5>
                                    
                                    <div class="logs-container">
                                        <table class="table table-sm table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Data/Hora</th>
                                                    <th>Tipo</th>
                                                    <th>Descrição</th>
                                                    <th>Usuário</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($logs as $log): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td>
                                                            <?php
                                                            $logTypeClass = '';
                                                            switch($log['log_type']) {
                                                                case 'status_change':
                                                                    $logTypeClass = 'badge-primary';
                                                                    break;
                                                                case 'progress_update':
                                                                    $logTypeClass = 'badge-info';
                                                                    break;
                                                                case 'note_added':
                                                                    $logTypeClass = 'badge-secondary';
                                                                    break;
                                                                case 'error':
                                                                    $logTypeClass = 'badge-danger';
                                                                    break;
                                                                case 'completion':
                                                                    $logTypeClass = 'badge-success';
                                                                    break;
                                                                case 'failure':
                                                                    $logTypeClass = 'badge-danger';
                                                                    break;
                                                                default:
                                                                    $logTypeClass = 'badge-secondary';
                                                            }
                                                            ?>
                                                            <span class="badge <?= $logTypeClass ?>">
                                                                <?= htmlspecialchars($log['log_type'], ENT_QUOTES, 'UTF-8') ?>
                                                            </span>
                                                        </td>
                                                        <td><?= htmlspecialchars($log['description'], ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td><?= htmlspecialchars($log['user_name'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- Informações da Impressora -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Detalhes da Impressora</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Nome:</strong><br><?= htmlspecialchars($printer['name'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p><strong>Modelo:</strong><br><?= htmlspecialchars($printer['model'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p><strong>Status:</strong><br>
                                        <?php
                                        $printerStatusClass = '';
                                        $printerStatusText = '';
                                        
                                        switch($printer['status']) {
                                            case 'available':
                                                $printerStatusClass = 'badge-success';
                                                $printerStatusText = 'Disponível';
                                                break;
                                            case 'printing':
                                                $printerStatusClass = 'badge-primary';
                                                $printerStatusText = 'Imprimindo';
                                                break;
                                            case 'maintenance':
                                                $printerStatusClass = 'badge-warning';
                                                $printerStatusText = 'Manutenção';
                                                break;
                                            case 'error':
                                                $printerStatusClass = 'badge-danger';
                                                $printerStatusText = 'Erro';
                                                break;
                                            default:
                                                $printerStatusClass = 'badge-secondary';
                                                $printerStatusText = $printer['status'];
                                        }
                                        ?>
                                        <span class="badge <?= $printerStatusClass ?>"><?= $printerStatusText ?></span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Materiais:</strong><br><?= htmlspecialchars($printer['supported_materials'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
                                    <p><strong>Volume:</strong><br>
                                        <?= htmlspecialchars($printer['build_volume_x'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> x 
                                        <?= htmlspecialchars($printer['build_volume_y'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> x 
                                        <?= htmlspecialchars($printer['build_volume_z'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> mm
                                    </p>
                                    <p><strong>Última calibração:</strong><br>
                                        <?= htmlspecialchars(date('d/m/Y', strtotime($printer['last_calibration'] ?? 'now')), ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                            </div>
                            
                            <?php if (!empty($printer['notes'])): ?>
                                <div class="alert alert-info mt-3">
                                    <strong>Notas da Impressora:</strong><br>
                                    <?= nl2br(htmlspecialchars($printer['notes'], ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Cliente -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informações do Cliente</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Nome:</strong><br><?= htmlspecialchars($queueItem['user_name'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p><strong>Email:</strong><br><a href="mailto:<?= htmlspecialchars($queueItem['user_email'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($queueItem['user_email'], ENT_QUOTES, 'UTF-8') ?></a></p>
                            <p><strong>Telefone:</strong><br><?= htmlspecialchars($userInfo['phone'] ?? 'Não informado', ENT_QUOTES, 'UTF-8') ?></p>
                            
                            <?php if (isset($userInfo['address']) && !empty($userInfo['address'])): ?>
                                <p><strong>Endereço:</strong><br>
                                    <?= nl2br(htmlspecialchars($userInfo['address'], ENT_QUOTES, 'UTF-8')) ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <a href="/admin/users/view/<?= htmlspecialchars($queueItem['user_id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fa fa-user"></i> Ver Perfil Completo
                                </a>
                                <a href="mailto:<?= htmlspecialchars($queueItem['user_email'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-info btn-sm">
                                    <i class="fa fa-envelope"></i> Enviar Email
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ações Rápidas -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Ações Rápidas</h5>
                        </div>
                        <div class="card-body">
                            <div class="btn-group-vertical w-100">
                                <a href="/print-queue/details/<?= htmlspecialchars($job['queue_id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary">
                                    <i class="fa fa-list"></i> Ver Item na Fila
                                </a>
                                
                                <?php if ($job['status'] === 'printing'): ?>
                                    <button type="button" class="btn btn-primary mt-2" data-toggle="modal" data-target="#progressModal">
                                        <i class="fa fa-tasks"></i> Atualizar Progresso
                                    </button>
                                    
                                    <button type="button" class="btn btn-outline-secondary mt-2" data-toggle="modal" data-target="#pauseModal">
                                        <i class="fa fa-pause"></i> Pausar Impressão
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($job['status'] === 'pending' || $job['status'] === 'preparing'): ?>
                                    <button type="button" class="btn btn-success mt-2" data-toggle="modal" data-target="#startModal">
                                        <i class="fa fa-play"></i> Iniciar Impressão
                                    </button>
                                    
                                    <button type="button" class="btn btn-outline-danger mt-2" data-toggle="modal" data-target="#cancelJobModal">
                                        <i class="fa fa-times"></i> Cancelar Trabalho
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($job['status'] === 'printing' || $job['status'] === 'post-processing'): ?>
                                    <button type="button" class="btn btn-success mt-2" data-toggle="modal" data-target="#completeModal">
                                        <i class="fa fa-check"></i> Marcar como Concluído
                                    </button>
                                    
                                    <button type="button" class="btn btn-danger mt-2" data-toggle="modal" data-target="#failModal">
                                        <i class="fa fa-exclamation-triangle"></i> Registrar Falha
                                    </button>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-outline-primary mt-2" data-toggle="modal" data-target="#notifyModal">
                                    <i class="fa fa-bell"></i> Notificar Cliente
                                </button>
                                
                                <a href="/admin/print-jobs" class="btn btn-secondary mt-2">
                                    <i class="fa fa-arrow-left"></i> Voltar para Lista
                                </a>
                            </div>
                        </div>
                    </div>
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
                <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="startModalLabel">Iniciar Impressão</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Você está iniciando a impressão do modelo: <strong><?= htmlspecialchars($queueItem['model_name'], ENT_QUOTES, 'UTF-8') ?></strong></p>
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
                <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="progressModalLabel">Atualizar Progresso</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Atualizando progresso do modelo: <strong><?= htmlspecialchars($queueItem['model_name'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                    
                    <div class="form-group">
                        <label for="progress">Progresso Atual (%):</label>
                        <input type="range" class="custom-range" name="progress" id="progress" min="0" max="100" value="<?= htmlspecialchars($job['progress'] ?? 0, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="d-flex justify-content-between">
                            <span>0%</span>
                            <span id="progressValue"><?= htmlspecialchars($job['progress'] ?? 0, ENT_QUOTES, 'UTF-8') ?>%</span>
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
                <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="completeModalLabel">Concluir Impressão</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Você está marcando como concluída a impressão do modelo: <strong><?= htmlspecialchars($queueItem['model_name'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                    
                    <div class="form-group">
                        <label for="material_used">Material Utilizado (g):</label>
                        <input type="number" name="material_used" id="material_used" class="form-control" min="0" step="0.1" placeholder="Quantidade em gramas">
                    </div>
                    
                    <div class="form-group">
                        <label for="complete_notes">Notas de Conclusão:</label>
                        <textarea name="notes" id="complete_notes" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="notify_completion" name="notify_completion" value="1" checked>
                            <label class="custom-control-label" for="notify_completion">Notificar cliente sobre a conclusão</label>
                        </div>
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
                <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="failModalLabel">Registrar Falha na Impressão</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Você está registrando uma falha na impressão do modelo: <strong><?= htmlspecialchars($queueItem['model_name'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                    
                    <div class="form-group">
                        <label for="reason">Motivo da Falha:</label>
                        <textarea name="reason" id="reason" class="form-control" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="notify_failure" name="notify_failure" value="1" checked>
                            <label class="custom-control-label" for="notify_failure">Notificar cliente sobre a falha</label>
                        </div>
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

<!-- Modal para Notificar Cliente -->
<div class="modal fade" id="notifyModal" tabindex="-1" role="dialog" aria-labelledby="notifyModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="/print-job/notifyCustomer">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="job_id" value="<?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="notifyModalLabel">Notificar Cliente</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Enviar notificação para: <strong><?= htmlspecialchars($queueItem['user_name'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($queueItem['user_email'], ENT_QUOTES, 'UTF-8') ?>)</p>
                    
                    <div class="form-group">
                        <label for="notification_type">Tipo de Notificação:</label>
                        <select name="notification_type" id="notification_type" class="form-control" required>
                            <option value="update">Atualização Geral</option>
                            <option value="question">Pergunta / Solicitação de Informação</option>
                            <option value="delay">Atraso na Impressão</option>
                            <option value="pickup">Informações de Retirada</option>
                            <option value="payment">Informações de Pagamento</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notification_subject">Assunto:</label>
                        <input type="text" name="notification_subject" id="notification_subject" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="notification_message">Mensagem:</label>
                        <textarea name="notification_message" id="notification_message" class="form-control" rows="5" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="email_copy" name="email_copy" value="1" checked>
                            <label class="custom-control-label" for="email_copy">Enviar cópia por e-mail</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar Notificação</button>
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

.logs-container {
    max-height: 400px;
    overflow-y: auto;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar valor da porcentagem no slider de progresso
    const progressSlider = document.getElementById('progress');
    const progressValue = document.getElementById('progressValue');
    
    if (progressSlider && progressValue) {
        progressSlider.addEventListener('input', function() {
            progressValue.textContent = this.value + '%';
        });
    }
    
    // Preencher campos de notificação com valores padrão
    const notificationType = document.getElementById('notification_type');
    const notificationSubject = document.getElementById('notification_subject');
    const notificationMessage = document.getElementById('notification_message');
    
    if (notificationType && notificationSubject && notificationMessage) {
        notificationType.addEventListener('change', function() {
            const modelName = '<?= htmlspecialchars(addslashes($queueItem['model_name']), ENT_QUOTES, 'UTF-8') ?>';
            const jobId = '<?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?>';
            
            switch(this.value) {
                case 'update':
                    notificationSubject.value = `Atualização sobre sua impressão: ${modelName}`;
                    notificationMessage.value = `Olá,\n\nEstamos entrando em contato para fornecer uma atualização sobre seu modelo "${modelName}" (ID do trabalho: ${jobId}).\n\n[Insira detalhes da atualização aqui]\n\nSe tiver alguma dúvida, não hesite em nos contatar.\n\nAtenciosamente,\nEquipe da Taverna da Impressão 3D`;
                    break;
                case 'question':
                    notificationSubject.value = `Informação necessária sobre seu modelo: ${modelName}`;
                    notificationMessage.value = `Olá,\n\nPrecisamos de algumas informações adicionais sobre seu modelo "${modelName}" (ID do trabalho: ${jobId}) para prosseguir com a impressão.\n\n[Insira suas perguntas aqui]\n\nPor favor, responda assim que possível para evitar atrasos na impressão.\n\nAtenciosamente,\nEquipe da Taverna da Impressão 3D`;
                    break;
                case 'delay':
                    notificationSubject.value = `Atraso na impressão do seu modelo: ${modelName}`;
                    notificationMessage.value = `Olá,\n\nLamentamos informar que houve um atraso na impressão do seu modelo "${modelName}" (ID do trabalho: ${jobId}).\n\n[Insira motivo do atraso aqui]\n\n[Forneça nova estimativa de tempo, se disponível]\n\nPedimos desculpas pelo inconveniente e estamos trabalhando para resolver a situação o mais rápido possível.\n\nAtenciosamente,\nEquipe da Taverna da Impressão 3D`;
                    break;
                case 'pickup':
                    notificationSubject.value = `Seu modelo está pronto para retirada: ${modelName}`;
                    notificationMessage.value = `Olá,\n\nTemos o prazer de informar que seu modelo "${modelName}" (ID do trabalho: ${jobId}) está pronto e disponível para retirada.\n\nVocê pode retirá-lo em nossa loja durante nosso horário de funcionamento:\nSegunda a Sexta: 09:00 às 18:00\nSábados: 09:00 às 13:00\n\nPor favor, traga um documento de identificação com foto e o código de rastreamento.\n\nSeu modelo ficará disponível para retirada por 30 dias.\n\nAtenciosamente,\nEquipe da Taverna da Impressão 3D`;
                    break;
                case 'payment':
                    notificationSubject.value = `Informações de pagamento: ${modelName}`;
                    notificationMessage.value = `Olá,\n\nGostaríamos de fornecer informações de pagamento referentes à impressão do seu modelo "${modelName}" (ID do trabalho: ${jobId}).\n\n[Insira detalhes de pagamento aqui]\n\nSe tiver alguma dúvida, não hesite em nos contatar.\n\nAtenciosamente,\nEquipe da Taverna da Impressão 3D`;
                    break;
            }
        });
        
        // Disparar evento change para preencher com valores iniciais
        const event = new Event('change');
        notificationType.dispatchEvent(event);
    }
    
    // Para trabalhos em andamento, atualizar a página a cada 5 minutos
    <?php if ($job['status'] === 'printing'): ?>
    setTimeout(function() {
        location.reload();
    }, 5 * 60 * 1000);
    <?php endif; ?>
});
</script>
