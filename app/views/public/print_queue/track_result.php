<?php
/**
 * View - Resultado do Rastreamento Público de Impressão
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Views/Public
 * @version    1.0.0
 */

// Garantir que este arquivo não seja acessado diretamente
defined('BASEPATH') or exit('Acesso direto ao script não é permitido');
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Resultado do Rastreamento</h4>
                        <a href="/track" class="btn btn-light btn-sm">
                            <i class="fa fa-search"></i> Nova Consulta
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_type'])): ?>
                        <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show">
                            <?= htmlspecialchars($_SESSION['flash_message'], ENT_QUOTES, 'UTF-8') ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mb-4">
                        <div class="tracking-code-display py-2 px-4 d-inline-block border rounded bg-light">
                            <span class="font-weight-bold">Código de Rastreamento:</span> 
                            <span class="tracking-code"><?= htmlspecialchars($trackingCode, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    
                    <?php if (!$queueItem): ?>
                        <!-- Item não encontrado -->
                        <div class="alert alert-warning">
                            <h5><i class="fa fa-exclamation-triangle"></i> Item não encontrado</h5>
                            <p>Não foi possível encontrar informações para o código de rastreamento informado. Verifique se o código foi digitado corretamente e tente novamente.</p>
                            <p>Se o problema persistir, entre em contato com nosso suporte.</p>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="/track" class="btn btn-primary">
                                <i class="fa fa-arrow-left"></i> Voltar
                            </a>
                            <a href="/contact" class="btn btn-outline-secondary ml-2">
                                <i class="fa fa-envelope"></i> Contato
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Item encontrado - Mostrar Status -->
                        <?php
                        $statusClass = '';
                        $statusText = '';
                        $statusIcon = '';
                        $bgClass = '';
                        
                        switch($queueItem['status']) {
                            case 'pending':
                                $statusClass = 'text-warning';
                                $statusText = 'Pendente';
                                $statusIcon = 'fa-clock';
                                $bgClass = 'bg-warning-light';
                                break;
                            case 'assigned':
                                $statusClass = 'text-info';
                                $statusText = 'Atribuído';
                                $statusIcon = 'fa-thumbtack';
                                $bgClass = 'bg-info-light';
                                break;
                            case 'printing':
                                $statusClass = 'text-primary';
                                $statusText = 'Em Impressão';
                                $statusIcon = 'fa-print';
                                $bgClass = 'bg-primary-light';
                                break;
                            case 'completed':
                                $statusClass = 'text-success';
                                $statusText = 'Concluído';
                                $statusIcon = 'fa-check-circle';
                                $bgClass = 'bg-success-light';
                                break;
                            case 'cancelled':
                                $statusClass = 'text-secondary';
                                $statusText = 'Cancelado';
                                $statusIcon = 'fa-ban';
                                $bgClass = 'bg-secondary-light';
                                break;
                            case 'failed':
                                $statusClass = 'text-danger';
                                $statusText = 'Falha';
                                $statusIcon = 'fa-exclamation-triangle';
                                $bgClass = 'bg-danger-light';
                                break;
                            default:
                                $statusClass = 'text-dark';
                                $statusText = $queueItem['status'];
                                $statusIcon = 'fa-question-circle';
                                $bgClass = '';
                        }
                        ?>
                        
                        <div class="status-container text-center mb-4 py-3 <?= $bgClass ?>">
                            <i class="fa <?= $statusIcon ?> fa-3x <?= $statusClass ?> mb-2"></i>
                            <h3 class="<?= $statusClass ?>">Status: <?= $statusText ?></h3>
                            
                            <?php if ($queueItem['status'] === 'printing' && isset($queueItem['progress'])): ?>
                                <div class="progress mt-3 mb-2" style="height: 25px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                        role="progressbar" 
                                        style="width: <?= htmlspecialchars($queueItem['progress'], ENT_QUOTES, 'UTF-8') ?>%"
                                        aria-valuenow="<?= htmlspecialchars($queueItem['progress'], ENT_QUOTES, 'UTF-8') ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                        <?= htmlspecialchars($queueItem['progress'], ENT_QUOTES, 'UTF-8') ?>%
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Detalhes da Impressão -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Informações da Impressão</h5>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-striped table-bordered">
                                            <tbody>
                                                <tr>
                                                    <th>Modelo</th>
                                                    <td><?= htmlspecialchars($queueItem['model_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Data de Adição</th>
                                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($queueItem['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>
                                                
                                                <?php if (isset($queueItem['started_at']) && $queueItem['started_at']): ?>
                                                    <tr>
                                                        <th>Início da Impressão</th>
                                                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($queueItem['started_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($queueItem['completed_at']) && $queueItem['completed_at']): ?>
                                                    <tr>
                                                        <th>Conclusão</th>
                                                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($queueItem['completed_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                
                                                <?php if ($queueItem['status'] === 'printing'): ?>
                                                    <tr>
                                                        <th>Tempo Restante Est.</th>
                                                        <td>
                                                            <?php
                                                            $printSettings = isset($queueItem['print_settings']) && !empty($queueItem['print_settings'])
                                                                        ? (is_array($queueItem['print_settings']) ? $queueItem['print_settings'] : json_decode($queueItem['print_settings'], true))
                                                                        : [];
                                                            
                                                            $totalEstimated = isset($printSettings['estimated_print_time_hours']) 
                                                                            ? floatval($printSettings['estimated_print_time_hours']) 
                                                                            : 2.0;
                                                            $progress = floatval($queueItem['progress'] ?? 0);
                                                            $remainingTime = $totalEstimated * (1 - ($progress / 100));
                                                            $remainingHours = floor($remainingTime);
                                                            $remainingMinutes = round(($remainingTime - $remainingHours) * 60);
                                                            
                                                            echo htmlspecialchars("{$remainingHours}h {$remainingMinutes}min", ENT_QUOTES, 'UTF-8');
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                                
                                                <?php if ($queueItem['status'] === 'completed'): ?>
                                                    <tr>
                                                        <th>Disponível para Retirada</th>
                                                        <td>
                                                            <?php
                                                                $completedDate = new DateTime($queueItem['completed_at']);
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
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Histórico de Status -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Histórico de Status</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($history)): ?>
                                            <div class="alert alert-info">
                                                Nenhum evento de histórico encontrado.
                                            </div>
                                        <?php else: ?>
                                            <ul class="timeline">
                                                <?php foreach ($history as $event): ?>
                                                    <li class="timeline-item">
                                                        <div class="timeline-marker"></div>
                                                        <div class="timeline-content">
                                                            <h6 class="timeline-date">
                                                                <?= htmlspecialchars(date('d/m/Y H:i', strtotime($event['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                                                            </h6>
                                                            <p><?= htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Instruções com base no Status -->
                        <?php if ($queueItem['status'] === 'completed'): ?>
                            <div class="card mt-3">
                                <div class="card-header bg-success text-white">
                                    <h5 class="card-title mb-0"><i class="fa fa-info-circle"></i> Instruções para Retirada</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Local de Retirada:</strong></p>
                                            <p>
                                                Taverna da Impressão 3D<br>
                                                Rua Exemplo, 123 - Centro<br>
                                                São Paulo - SP<br>
                                                CEP: 01234-567
                                            </p>
                                            <p><strong>Horário de Funcionamento:</strong></p>
                                            <p>Segunda a Sexta: 09:00 às 18:00<br>
                                               Sábados: 09:00 às 13:00</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Documentos Necessários:</strong></p>
                                            <ul>
                                                <li>Documento de identificação com foto</li>
                                                <li>Código de rastreamento (<?= htmlspecialchars($trackingCode, ENT_QUOTES, 'UTF-8') ?>)</li>
                                            </ul>
                                            <p><strong>Observações:</strong></p>
                                            <ul>
                                                <li>Seu modelo ficará disponível para retirada por 30 dias</li>
                                                <li>Para retirada por terceiros, é necessário autorização prévia por e-mail</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Texto de atualização automática para impressões em andamento -->
                        <?php if ($queueItem['status'] === 'printing'): ?>
                            <div class="alert alert-info text-center mt-3">
                                <i class="fa fa-sync-alt"></i> Esta página será atualizada automaticamente a cada 3 minutos para mostrar o progresso atual da impressão.
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4">
                            <a href="/track" class="btn btn-primary">
                                <i class="fa fa-search"></i> Consultar Outro Código
                            </a>
                            <a href="/contact" class="btn btn-outline-secondary ml-2">
                                <i class="fa fa-question-circle"></i> Precisa de Ajuda?
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">
                        Taverna da Impressão 3D - Acompanhamento de Impressões
                    </small>
                </div>
            </div>
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

.tracking-code {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    letter-spacing: 2px;
    font-size: 1.2rem;
}

/* Timeline Styles */
.timeline {
    list-style: none;
    padding: 0;
    position: relative;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
    left: 10px;
    margin-left: -1px;
}

.timeline-item {
    position: relative;
    padding-left: 30px;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #007bff;
    left: 0;
    top: 5px;
    margin-left: 4px;
}

.timeline-date {
    margin-bottom: 5px;
    color: #6c757d;
    font-size: 0.85rem;
}

.timeline-content {
    padding-bottom: 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Para impressões em andamento, atualizar a página a cada 3 minutos
    <?php if (isset($queueItem) && $queueItem && $queueItem['status'] === 'printing'): ?>
    setTimeout(function() {
        location.reload();
    }, 3 * 60 * 1000);
    <?php endif; ?>
});
</script>
