<?php
/**
 * View - Detalhes da Notificação
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Views/Customer/Notifications
 * @version    1.0.0
 */

// Garantir que este arquivo não seja acessado diretamente
defined('BASEPATH') or exit('Acesso direto ao script não é permitido');

// Determinar classe de tipo
$typeClass = '';
$typeIcon = '';
$typeBadge = '';

switch($notification['type']) {
    case 'success':
        $typeClass = 'success';
        $typeIcon = 'fa-check-circle text-success';
        $typeBadge = 'badge-success';
        break;
    case 'warning':
        $typeClass = 'warning';
        $typeIcon = 'fa-exclamation-triangle text-warning';
        $typeBadge = 'badge-warning';
        break;
    case 'error':
        $typeClass = 'danger';
        $typeIcon = 'fa-times-circle text-danger';
        $typeBadge = 'badge-danger';
        break;
    default: // info
        $typeClass = 'info';
        $typeIcon = 'fa-info-circle text-info';
        $typeBadge = 'badge-info';
}

// Formatar data
$createdDate = new DateTime($notification['created_at']);
$formattedCreatedDate = $createdDate->format('d/m/Y H:i');

$readDate = null;
$formattedReadDate = '';
if ($notification['status'] === 'read' && $notification['read_at']) {
    $readDate = new DateTime($notification['read_at']);
    $formattedReadDate = $readDate->format('d/m/Y H:i');
}

// Contexto da notificação
$context = $notification['context'] ?? [];
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-transparent p-0">
                    <li class="breadcrumb-item"><a href="/notifications">Notificações</a></li>
                    <li class="breadcrumb-item active">Detalhes</li>
                </ol>
            </nav>
            
            <div class="card border-<?= $typeClass ?> mb-4">
                <div class="card-header bg-<?= $typeClass ?> bg-light">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <i class="fa <?= $typeIcon ?> fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="mb-0"><?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?></h5>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Metadados da notificação -->
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <span class="badge <?= $typeBadge ?>">
                                <?php switch($notification['type']) {
                                    case 'success': echo 'Sucesso'; break;
                                    case 'warning': echo 'Aviso'; break;
                                    case 'error': echo 'Erro'; break;
                                    default: echo 'Informação';
                                } ?>
                            </span>
                            
                            <?php if ($notification['status'] === 'unread'): ?>
                                <span class="badge badge-secondary">Não lida</span>
                            <?php else: ?>
                                <span class="badge badge-light">Lida</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-muted small">
                            <i class="fa fa-clock"></i> <?= htmlspecialchars($formattedCreatedDate, ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($formattedReadDate): ?>
                                <span class="ml-2"><i class="fa fa-check"></i> Lida em <?= htmlspecialchars($formattedReadDate, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Mensagem da notificação -->
                    <p class="lead"><?= htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8') ?></p>
                    
                    <!-- Contexto da notificação (se disponível) -->
                    <?php if (!empty($context)): ?>
                        <div class="mt-4">
                            <h6>Detalhes Adicionais:</h6>
                            <ul class="list-group">
                                <?php if (isset($context['model_name'])): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Modelo</span>
                                        <span class="font-weight-bold"><?= htmlspecialchars($context['model_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </li>
                                <?php endif; ?>
                                
                                <?php if (isset($context['current_status'])): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Status Atual</span>
                                        <?php
                                        $statusClass = '';
                                        $statusText = '';
                                        
                                        switch($context['current_status']) {
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
                                            case 'failed':
                                                $statusClass = 'badge-danger';
                                                $statusText = 'Falha';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'badge-secondary';
                                                $statusText = 'Cancelado';
                                                break;
                                            default:
                                                $statusClass = 'badge-light';
                                                $statusText = $context['current_status'];
                                        }
                                        ?>
                                        <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?></span>
                                    </li>
                                <?php endif; ?>
                                
                                <?php if (isset($context['previous_status'])): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Status Anterior</span>
                                        <?php
                                        $statusClass = '';
                                        $statusText = '';
                                        
                                        switch($context['previous_status']) {
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
                                            case 'failed':
                                                $statusClass = 'badge-danger';
                                                $statusText = 'Falha';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'badge-secondary';
                                                $statusText = 'Cancelado';
                                                break;
                                            default:
                                                $statusClass = 'badge-light';
                                                $statusText = $context['previous_status'];
                                        }
                                        ?>
                                        <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?></span>
                                    </li>
                                <?php endif; ?>
                                
                                <?php if (isset($context['priority'])): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Prioridade</span>
                                        <?php
                                        $priorityClass = '';
                                        
                                        if ($context['priority'] >= 8) {
                                            $priorityClass = 'badge-danger';
                                        } elseif ($context['priority'] >= 5) {
                                            $priorityClass = 'badge-warning';
                                        } else {
                                            $priorityClass = 'badge-info';
                                        }
                                        ?>
                                        <span class="badge <?= $priorityClass ?>"><?= htmlspecialchars($context['priority'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </li>
                                <?php endif; ?>
                                
                                <?php if (isset($context['notes'])): ?>
                                    <li class="list-group-item">
                                        <div class="font-weight-bold mb-1">Notas:</div>
                                        <div><?= htmlspecialchars($context['notes'], ENT_QUOTES, 'UTF-8') ?></div>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-footer bg-white d-flex justify-content-between">
                    <a href="/notifications" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Voltar para notificações
                    </a>
                    
                    <div>
                        <?php if ($notification['status'] === 'unread'): ?>
                            <button type="button" class="btn btn-primary" id="btn-mark-read" data-id="<?= htmlspecialchars($notification['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fa fa-check"></i> Marcar como lida
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-danger" id="btn-delete" data-id="<?= htmlspecialchars($notification['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fa fa-trash"></i> Excluir
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Conteúdo relacionado baseado no contexto -->
            <?php if (!empty($relatedContent)): ?>
                <!-- Detalhes do item da fila -->
                <?php if (isset($relatedContent['queue_item'])): ?>
                    <?php $item = $relatedContent['queue_item']; ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Detalhes do Item na Fila</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl>
                                        <dt>ID na Fila</dt>
                                        <dd><?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?></dd>
                                        
                                        <dt>Nome do Modelo</dt>
                                        <dd><?= htmlspecialchars($item['model_name'], ENT_QUOTES, 'UTF-8') ?></dd>
                                        
                                        <dt>Status</dt>
                                        <dd>
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
                                                case 'failed':
                                                    $statusClass = 'badge-danger';
                                                    $statusText = 'Falha';
                                                    break;
                                                case 'cancelled':
                                                    $statusClass = 'badge-secondary';
                                                    $statusText = 'Cancelado';
                                                    break;
                                                default:
                                                    $statusClass = 'badge-light';
                                                    $statusText = $item['status'];
                                            }
                                            ?>
                                            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?></span>
                                        </dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl>
                                        <dt>Prioridade</dt>
                                        <dd>
                                            <?php
                                            $priorityClass = '';
                                            
                                            if ($item['priority'] >= 8) {
                                                $priorityClass = 'text-danger font-weight-bold';
                                            } elseif ($item['priority'] >= 5) {
                                                $priorityClass = 'text-warning font-weight-bold';
                                            }
                                            ?>
                                            <span class="<?= $priorityClass ?>"><?= htmlspecialchars($item['priority'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </dd>
                                        
                                        <dt>Data de Criação</dt>
                                        <dd><?= htmlspecialchars(date('d/m/Y H:i', strtotime($item['created_at'])), ENT_QUOTES, 'UTF-8') ?></dd>
                                        
                                        <dt>Última Atualização</dt>
                                        <dd><?= htmlspecialchars(date('d/m/Y H:i', strtotime($item['updated_at'] ?? $item['created_at'])), ENT_QUOTES, 'UTF-8') ?></dd>
                                    </dl>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <a href="/print-queue/details/<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
                                    <i class="fa fa-eye"></i> Ver Detalhes Completos
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Histórico do item da fila -->
                <?php if (isset($relatedContent['queue_history']) && !empty($relatedContent['queue_history'])): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Histórico de Atividades</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($relatedContent['queue_history'] as $event): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <div class="font-weight-bold"><?= htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="text-muted small">
                                                    Por <?= htmlspecialchars($event['actor_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            </div>
                                            <div class="text-muted small">
                                                <?= htmlspecialchars(date('d/m/Y H:i', strtotime($event['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="delete-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Excluir Notificação</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir esta notificação?</p>
                <p class="text-danger small">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirm-delete">Excluir</button>
            </div>
        </div>
    </div>
</div>

<!-- CSRF Token para AJAX -->
<input type="hidden" id="csrf-token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Botão "Marcar como lida"
    const markReadBtn = document.getElementById('btn-mark-read');
    if (markReadBtn) {
        markReadBtn.addEventListener('click', function() {
            const notificationId = this.dataset.id;
            markAsRead(notificationId);
        });
    }
    
    // Botão "Excluir"
    const deleteBtn = document.getElementById('btn-delete');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            $('#delete-modal').modal('show');
        });
    }
    
    // Botão "Confirmar exclusão"
    const confirmDeleteBtn = document.getElementById('confirm-delete');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            const notificationId = document.getElementById('btn-delete').dataset.id;
            deleteNotification(notificationId);
        });
    }
    
    // Função para marcar como lida
    function markAsRead(notificationId) {
        const csrfToken = document.getElementById('csrf-token').value;
        
        fetch('/notifications/mark-as-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `notification_id=${notificationId}&csrf_token=${csrfToken}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Falha ao marcar como lida');
            }
            return response.json();
        })
        .then(data => {
            // Atualizar token CSRF
            document.getElementById('csrf-token').value = data.csrf_token;
            
            // Remover botão "Marcar como lida"
            const markReadBtn = document.getElementById('btn-mark-read');
            if (markReadBtn) {
                markReadBtn.remove();
            }
            
            // Atualizar status na página
            const statusBadge = document.querySelector('.badge-secondary');
            if (statusBadge && statusBadge.textContent === 'Não lida') {
                statusBadge.className = 'badge badge-light';
                statusBadge.textContent = 'Lida';
            }
            
            // Adicionar indicador de leitura
            const metadataDiv = document.querySelector('.text-muted.small');
            if (metadataDiv && !metadataDiv.querySelector('span')) {
                const now = new Date();
                const formattedDate = now.toLocaleDateString() + ' ' + now.toLocaleTimeString();
                
                const readIndicator = document.createElement('span');
                readIndicator.className = 'ml-2';
                readIndicator.innerHTML = `<i class="fa fa-check"></i> Lida em ${formattedDate}`;
                
                metadataDiv.appendChild(readIndicator);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao marcar notificação como lida. Por favor, tente novamente.');
        });
    }
    
    // Função para excluir notificação
    function deleteNotification(notificationId) {
        const csrfToken = document.getElementById('csrf-token').value;
        
        fetch('/notifications/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `notification_id=${notificationId}&csrf_token=${csrfToken}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Falha ao excluir notificação');
            }
            return response.json();
        })
        .then(data => {
            // Redirecionar para lista de notificações
            window.location.href = '/notifications';
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir notificação. Por favor, tente novamente.');
            $('#delete-modal').modal('hide');
        });
    }
});
</script>
