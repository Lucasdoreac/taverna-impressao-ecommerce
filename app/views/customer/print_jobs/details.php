<?php
/**
 * View - Detalhes do Trabalho de Impressão (Cliente)
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
                <a href="/user/print-jobs" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Voltar para Meus Trabalhos
                </a>
            </div>
            
            <?php if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_type'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['flash_message'], ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            
            <!-- Status do Trabalho -->
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
                    <div class="row align-items-center <?= $statusBg ?>">
                        <div class="col-md-8">
                            <h4>Status: <span class="<?= $statusClass ?>"><?= $statusText ?></span></h4>
                            <p class="lead">Modelo: <strong><?= htmlspecialchars($queueItem['model_name'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                            <p>
                                <strong>Impressora:</strong> <?= htmlspecialchars($printer['name'], ENT_QUOTES, 'UTF-8') ?> 
                                (<?= htmlspecialchars($printer['model'], ENT_QUOTES, 'UTF-8') ?>)
                            </p>
                        </div>
                        <div class="col-md-4">
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
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detalhes do Trabalho -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informações do Trabalho</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <tbody>
                                    <tr>
                                        <th>ID do Trabalho</th>
                                        <td><?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                    <tr>
                                        <th>ID do Item na Fila</th>
                                        <td>
                                            <a href="/user/print-queue/details/<?= htmlspecialchars($job['queue_id'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($job['queue_id'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Data de Criação</th>
                                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                    
                                    <?php if (isset($job['scheduled_start_time']) && $job['scheduled_start_time']): ?>
                                        <tr>
                                            <th>Agendado para</th>
                                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['scheduled_start_time'])), ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($job['started_at']) && $job['started_at']): ?>
                                        <tr>
                                            <th>Início da Impressão</th>
                                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['started_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($job['completed_at']) && $job['completed_at']): ?>
                                        <tr>
                                            <th>Conclusão</th>
                                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($job['completed_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($job['material_used']) && $job['material_used']): ?>
                                        <tr>
                                            <th>Material Utilizado</th>
                                            <td><?= htmlspecialchars($job['material_used'], ENT_QUOTES, 'UTF-8') ?> g</td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php if ($job['status'] === 'completed'): ?>
                                        <tr>
                                            <th>Disponível para Retirada</th>
                                            <td>
                                                <?php
                                                    $completedDate = new DateTime($job['completed_at']);
                                                    $pickupDate = clone $completedDate;
                                                    $pickupDate->add(new DateInterval('P1D')); // Adiciona 1 dia
                                                    
                                                    $now = new DateTime();
                                                    if ($now >= $pickupDate) {
                                                        echo '<span class="badge badge-success">Disponível Agora</span>';
                                                    } else {
                                                        echo 'A partir de ' . htmlspecialchars($pickupDate->format('d/m/Y H:i'), ENT_QUOTES, 'UTF-8');
                                                    }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <?php if (!empty($job['notes'])): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Notas do Trabalho</h5>
                            </div>
                            <div class="card-body">
                                <?= nl2br(htmlspecialchars($job['notes'], ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Configurações de Impressão</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $printSettings = isset($queueItem['print_settings']) && !empty($queueItem['print_settings'])
                                        ? (is_array($queueItem['print_settings']) ? $queueItem['print_settings'] : json_decode($queueItem['print_settings'], true))
                                        : [];
                            ?>
                            
                            <?php if (!empty($printSettings)): ?>
                                <table class="table table-striped">
                                    <tbody>
                                        <?php if (isset($printSettings['scale'])): ?>
                                            <tr>
                                                <th>Escala</th>
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
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    Não há configurações específicas para este trabalho.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informações da Impressora</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Nome:</strong><br><?= htmlspecialchars($printer['name'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p><strong>Modelo:</strong><br><?= htmlspecialchars($printer['model'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Volume de Impressão:</strong><br>
                                    <?= htmlspecialchars($printer['build_volume_x'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> x 
                                    <?= htmlspecialchars($printer['build_volume_y'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> x 
                                    <?= htmlspecialchars($printer['build_volume_z'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> mm
                                    </p>
                                    <p><strong>Materiais Suportados:</strong><br><?= htmlspecialchars($printer['supported_materials'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Instruções para Retirada -->
            <?php if ($job['status'] === 'completed'): ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0"><i class="fa fa-check-circle"></i> Instruções para Retirada</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Como Retirar seu Modelo</h6>
                                <ol>
                                    <li>Visite nossa loja durante o horário comercial (Seg-Sex, 09:00-18:00)</li>
                                    <li>Informe ao atendente o ID do seu trabalho: <strong><?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?></strong></li>
                                    <li>Apresente um documento de identificação com foto</li>
                                    <li>Efetue o pagamento (caso ainda não tenha sido realizado)</li>
                                </ol>
                                <p><strong>Prazo para Retirada:</strong> 30 dias a partir da data de conclusão</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Informações Adicionais</h6>
                                <ul>
                                    <li>Você receberá um e-mail com a confirmação e detalhes para retirada</li>
                                    <li>Caso não possa comparecer pessoalmente, você pode autorizar outra pessoa enviando um e-mail para contato@tavernaimpressao3d.com.br</li>
                                    <li>Em caso de dúvidas, entre em contato pelo telefone (11) 5555-5555</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Instruções para Falha -->
            <?php if ($job['status'] === 'failed'): ?>
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0"><i class="fa fa-exclamation-triangle"></i> Informações sobre a Falha</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Motivo da Falha:</strong></p>
                        <div class="alert alert-danger">
                            <?= nl2br(htmlspecialchars($job['failure_reason'] ?? 'Não especificado', ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <h6>Próximos Passos</h6>
                                <p>Nossa equipe entrará em contato em breve para discutir as opções disponíveis, que podem incluir:</p>
                                <ul>
                                    <li>Reagendamento da impressão com ajustes no modelo</li>
                                    <li>Reembolso (caso aplicável)</li>
                                    <li>Sugestões de modificações no design para torná-lo mais adequado para impressão 3D</li>
                                </ul>
                                <p>Se preferir, você pode entrar em contato conosco pelo e-mail <strong>suporte@tavernaimpressao3d.com.br</strong> mencionando o ID do trabalho <strong><?= htmlspecialchars($job['id'], ENT_QUOTES, 'UTF-8') ?></strong>.</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Para trabalhos em andamento, atualizar a página a cada 2 minutos
    <?php if ($job['status'] === 'printing'): ?>
    setTimeout(function() {
        location.reload();
    }, 2 * 60 * 1000);
    <?php endif; ?>
});
</script>
