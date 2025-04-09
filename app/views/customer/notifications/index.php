<?php
/**
 * View - Lista de Notificações do Usuário
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Views/Customer/Notifications
 * @version    1.0.0
 */

// Garantir que este arquivo não seja acessado diretamente
defined('BASEPATH') or exit('Acesso direto ao script não é permitido');
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-3"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
            
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <ul class="nav nav-pills nav-sm">
                            <li class="nav-item">
                                <a class="nav-link <?= $status === 'all' ? 'active' : '' ?>" href="/notifications">Todas</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $status === 'unread' ? 'active' : '' ?>" href="/notifications?status=unread">
                                    Não lidas
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="badge badge-light ml-1"><?= htmlspecialchars($unreadCount, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $status === 'read' ? 'active' : '' ?>" href="/notifications?status=read">Lidas</a>
                            </li>
                        </ul>
                    </div>
                    
                    <?php if ($unreadCount > 0): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="mark-all-read">
                            <i class="fa fa-check-double"></i> Marcar todas como lidas
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="list-group list-group-flush" id="notifications-list">
                    <?php if (empty($notifications)): ?>
                        <div class="list-group-item text-center py-4">
                            <div class="text-muted mb-2">
                                <i class="fa fa-bell-slash fa-3x"></i>
                            </div>
                            <h5>Nenhuma notificação encontrada</h5>
                            <p class="mb-0">Você não tem notificações <?= $status === 'unread' ? 'não lidas' : ($status === 'read' ? 'lidas' : '') ?> no momento.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <?php
                            // Determinar classe de tipo
                            $typeClass = '';
                            $typeIcon = '';
                            
                            switch($notification['type']) {
                                case 'success':
                                    $typeClass = 'border-success';
                                    $typeIcon = 'fa-check-circle text-success';
                                    break;
                                case 'warning':
                                    $typeClass = 'border-warning';
                                    $typeIcon = 'fa-exclamation-triangle text-warning';
                                    break;
                                case 'error':
                                    $typeClass = 'border-danger';
                                    $typeIcon = 'fa-times-circle text-danger';
                                    break;
                                default: // info
                                    $typeClass = 'border-info';
                                    $typeIcon = 'fa-info-circle text-info';
                            }
                            
                            // Formatar data
                            $date = new DateTime($notification['created_at']);
                            $formattedDate = $date->format('d/m/Y H:i');
                            
                            // Status não lido
                            $unreadClass = $notification['status'] === 'unread' ? 'bg-light font-weight-bold' : '';
                            ?>
                            
                            <div class="list-group-item list-group-item-action <?= $unreadClass ?>" data-id="<?= htmlspecialchars($notification['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <div class="d-flex">
                                        <div class="mr-3">
                                            <i class="fa <?= $typeIcon ?> fa-2x"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1"><?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?></h5>
                                            <p class="mb-1"><?= htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($formattedDate, ENT_QUOTES, 'UTF-8') ?>
                                                <?php if ($notification['status'] === 'read' && $notification['read_at']): ?>
                                                    · Lida em <?= htmlspecialchars(date('d/m/Y H:i', strtotime($notification['read_at'])), ENT_QUOTES, 'UTF-8') ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="dropdown ml-2">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                            <i class="fa fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="/notifications/<?= htmlspecialchars($notification['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fa fa-eye"></i> Ver detalhes
                                            </a>
                                            
                                            <?php if ($notification['status'] === 'unread'): ?>
                                                <button class="dropdown-item btn-mark-read" data-id="<?= htmlspecialchars($notification['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fa fa-check"></i> Marcar como lida
                                                </button>
                                            <?php endif; ?>
                                            
                                            <div class="dropdown-divider"></div>
                                            <button class="dropdown-item text-danger btn-delete" data-id="<?= htmlspecialchars($notification['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fa fa-trash"></i> Excluir
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (isset($notification['context']) && !empty($notification['context'])): ?>
                                    <?php
                                    // Exibir informações relevantes do contexto
                                    $contextHtml = '';
                                    
                                    if (isset($notification['context']['model_name'])) {
                                        $contextHtml .= '<span class="badge badge-light mr-2">Modelo: ' . htmlspecialchars($notification['context']['model_name'], ENT_QUOTES, 'UTF-8') . '</span>';
                                    }
                                    
                                    if (isset($notification['context']['current_status'])) {
                                        $statusClass = '';
                                        switch ($notification['context']['current_status']) {
                                            case 'pending': $statusClass = 'badge-warning'; break;
                                            case 'assigned': $statusClass = 'badge-info'; break;
                                            case 'printing': $statusClass = 'badge-primary'; break;
                                            case 'completed': $statusClass = 'badge-success'; break;
                                            case 'failed': $statusClass = 'badge-danger'; break;
                                            case 'cancelled': $statusClass = 'badge-secondary'; break;
                                            default: $statusClass = 'badge-light';
                                        }
                                        
                                        $contextHtml .= '<span class="badge ' . $statusClass . ' mr-2">Status: ' . htmlspecialchars($notification['context']['current_status'], ENT_QUOTES, 'UTF-8') . '</span>';
                                    }
                                    
                                    if (isset($notification['context']['priority']) && $notification['context']['priority'] >= 8) {
                                        $contextHtml .= '<span class="badge badge-danger mr-2">Prioridade: ' . htmlspecialchars($notification['context']['priority'], ENT_QUOTES, 'UTF-8') . '</span>';
                                    }
                                    
                                    // Exibir apenas se tiver algum conteúdo relevante
                                    if (!empty($contextHtml)) {
                                        echo '<div class="mt-2">' . $contextHtml . '</div>';
                                    }
                                    ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Paginação simples -->
                <div class="card-footer bg-white">
                    <nav>
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/notifications?status=<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>&page=<?= $page - 1 ?>">Anterior</a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">Anterior</span>
                                </li>
                            <?php endif; ?>
                            
                            <li class="page-item active">
                                <span class="page-link"><?= htmlspecialchars($page, ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                            
                            <?php if (count($notifications) >= $limit): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/notifications?status=<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>&page=<?= $page + 1 ?>">Próxima</a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">Próxima</span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modais de confirmação -->
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
    // Variáveis para armazenar ID da notificação selecionada
    let selectedNotificationId = null;
    
    // Manipuladores de eventos
    const notificationsList = document.getElementById('notifications-list');
    
    // Delegação de eventos para a lista de notificações
    if (notificationsList) {
        notificationsList.addEventListener('click', function(e) {
            // Botões "Marcar como lida"
            if (e.target.classList.contains('btn-mark-read') || e.target.closest('.btn-mark-read')) {
                const button = e.target.classList.contains('btn-mark-read') ? e.target : e.target.closest('.btn-mark-read');
                const notificationId = button.dataset.id;
                markAsRead(notificationId);
                e.preventDefault();
                e.stopPropagation();
            }
            
            // Botões "Excluir"
            if (e.target.classList.contains('btn-delete') || e.target.closest('.btn-delete')) {
                const button = e.target.classList.contains('btn-delete') ? e.target : e.target.closest('.btn-delete');
                selectedNotificationId = button.dataset.id;
                $('#delete-modal').modal('show');
                e.preventDefault();
                e.stopPropagation();
            }
            
            // Clique nos itens da lista (exceto nos botões)
            const listItem = e.target.closest('.list-group-item');
            if (listItem && !e.target.closest('.dropdown') && !e.target.closest('button')) {
                const notificationId = listItem.dataset.id;
                window.location.href = '/notifications/' + notificationId;
            }
        });
    }
    
    // Botão "Marcar todas como lidas"
    const markAllReadBtn = document.getElementById('mark-all-read');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            markAllAsRead();
        });
    }
    
    // Botão "Confirmar exclusão"
    const confirmDeleteBtn = document.getElementById('confirm-delete');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            deleteNotification(selectedNotificationId);
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
            
            // Atualizar contagem de não lidas
            updateUnreadCount(data.unread_count);
            
            // Atualizar aparência do item na lista
            const item = document.querySelector(`.list-group-item[data-id="${notificationId}"]`);
            if (item) {
                item.classList.remove('bg-light', 'font-weight-bold');
                
                // Remover botão "Marcar como lida"
                const markReadBtn = item.querySelector('.btn-mark-read');
                if (markReadBtn) {
                    markReadBtn.remove();
                }
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao marcar notificação como lida. Por favor, tente novamente.');
        });
    }
    
    // Função para marcar todas como lidas
    function markAllAsRead() {
        const csrfToken = document.getElementById('csrf-token').value;
        
        fetch('/notifications/mark-all-as-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `csrf_token=${csrfToken}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Falha ao marcar todas como lidas');
            }
            return response.json();
        })
        .then(data => {
            // Atualizar token CSRF
            document.getElementById('csrf-token').value = data.csrf_token;
            
            // Atualizar contagem de não lidas
            updateUnreadCount(0);
            
            // Atualizar aparência dos itens na lista
            const items = document.querySelectorAll('.list-group-item.bg-light');
            items.forEach(item => {
                item.classList.remove('bg-light', 'font-weight-bold');
                
                // Remover botões "Marcar como lida"
                const markReadBtn = item.querySelector('.btn-mark-read');
                if (markReadBtn) {
                    markReadBtn.remove();
                }
            });
            
            // Esconder botão "Marcar todas como lidas"
            document.getElementById('mark-all-read').style.display = 'none';
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao marcar todas as notificações como lidas. Por favor, tente novamente.');
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
            // Atualizar token CSRF
            document.getElementById('csrf-token').value = data.csrf_token;
            
            // Atualizar contagem de não lidas
            updateUnreadCount(data.unread_count);
            
            // Remover item da lista
            const item = document.querySelector(`.list-group-item[data-id="${notificationId}"]`);
            if (item) {
                item.remove();
            }
            
            // Verificar se a lista ficou vazia
            const remainingItems = notificationsList.querySelectorAll('.list-group-item');
            if (remainingItems.length === 0) {
                notificationsList.innerHTML = `
                    <div class="list-group-item text-center py-4">
                        <div class="text-muted mb-2">
                            <i class="fa fa-bell-slash fa-3x"></i>
                        </div>
                        <h5>Nenhuma notificação encontrada</h5>
                        <p class="mb-0">Você não tem notificações no momento.</p>
                    </div>
                `;
            }
            
            // Fechar modal
            $('#delete-modal').modal('hide');
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir notificação. Por favor, tente novamente.');
            $('#delete-modal').modal('hide');
        });
    }
    
    // Função para atualizar contagem de não lidas
    function updateUnreadCount(count) {
        const unreadTab = document.querySelector('.nav-link[href="/notifications?status=unread"]');
        if (unreadTab) {
            const badge = unreadTab.querySelector('.badge');
            
            if (count > 0) {
                if (badge) {
                    badge.textContent = count;
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'badge badge-light ml-1';
                    newBadge.textContent = count;
                    unreadTab.appendChild(newBadge);
                }
            } else {
                if (badge) {
                    badge.remove();
                }
            }
        }
    }
});
</script>
