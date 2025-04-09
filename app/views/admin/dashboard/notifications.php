<?php
/**
 * View de gerenciamento de notificações do dashboard administrativo
 * 
 * Esta view exibe todas as notificações do sistema e permite criar novas notificações.
 */

// Incluir header
include_once APP_PATH . '/views/admin/partials/header.php';
include_once APP_PATH . '/views/admin/partials/sidebar.php';

// Obter token CSRF
$csrfToken = SecurityManager::getCsrfToken();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><?= htmlspecialchars($title) ?></h1>
        <div class="dashboard-actions">
            <a href="<?= BASE_URL ?>admin/dashboard/createNotification" class="btn btn-primary">
                <i class="fa fa-bell"></i> Nova Notificação
            </a>
        </div>
    </div>

    <!-- Filtros de notificações -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="<?= BASE_URL ?>admin/dashboard/notifications" method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="type" class="form-label">Tipo</label>
                    <select id="type" name="type" class="form-select">
                        <option value="all" <?= ($type === 'all') ? 'selected' : '' ?>>Todos</option>
                        <option value="info" <?= ($type === 'info') ? 'selected' : '' ?>>Informação</option>
                        <option value="success" <?= ($type === 'success') ? 'selected' : '' ?>>Sucesso</option>
                        <option value="warning" <?= ($type === 'warning') ? 'selected' : '' ?>>Alerta</option>
                        <option value="error" <?= ($type === 'error') ? 'selected' : '' ?>>Erro</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="limit" class="form-label">Itens por página</label>
                    <select id="limit" name="limit" class="form-select">
                        <option value="10" <?= ($limit === 10) ? 'selected' : '' ?>>10</option>
                        <option value="20" <?= ($limit === 20) ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= ($limit === 50) ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= ($limit === 100) ? 'selected' : '' ?>>100</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa fa-filter"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de notificações -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Destinatários</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Entrega</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($notifications)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Nenhuma notificação encontrada</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <tr>
                                    <td><?= $notification['id'] ?></td>
                                    <td><?= htmlspecialchars($notification['title']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= getNotificationTypeColor($notification['type']) ?>">
                                            <?= htmlspecialchars(getNotificationTypeName($notification['type'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (isset($notification['target_roles'])): ?>
                                            <?php foreach (json_decode($notification['target_roles'], true) as $role): ?>
                                                <span class="badge bg-secondary"><?= htmlspecialchars(getRoleName($role)) ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Usuário específico</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= getDeliveryStatusColor($notification['status']) ?>">
                                            <?= htmlspecialchars(getDeliveryStatusName($notification['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                            $stats = json_decode($notification['delivery_stats'] ?? '{}', true);
                                            $total = $stats['total'] ?? 0;
                                            $delivered = $stats['delivered'] ?? 0;
                                            $percentage = $total > 0 ? round(($delivered / $total) * 100) : 0;
                                        ?>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $percentage ?>%;" 
                                                 aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100">
                                                <?= $percentage ?>%
                                            </div>
                                        </div>
                                        <small><?= $delivered ?>/<?= $total ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-info btn-sm" 
                                                    onclick="viewNotificationDetails(<?= $notification['id'] ?>)">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                            <?php if ($notification['status'] !== 'completed'): ?>
                                                <button type="button" class="btn btn-warning btn-sm" 
                                                        onclick="resendNotification(<?= $notification['id'] ?>)">
                                                    <i class="fa fa-sync-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginação -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Navegação de página">
                    <ul class="pagination justify-content-center mt-4">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= BASE_URL ?>admin/dashboard/notifications?page=1&limit=<?= $limit ?>&type=<?= $type ?>">
                                    <i class="fa fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?= BASE_URL ?>admin/dashboard/notifications?page=<?= $page - 1 ?>&limit=<?= $limit ?>&type=<?= $type ?>">
                                    <i class="fa fa-angle-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $startPage + 4);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= BASE_URL ?>admin/dashboard/notifications?page=<?= $i ?>&limit=<?= $limit ?>&type=<?= $type ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= BASE_URL ?>admin/dashboard/notifications?page=<?= $page + 1 ?>&limit=<?= $limit ?>&type=<?= $type ?>">
                                    <i class="fa fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?= BASE_URL ?>admin/dashboard/notifications?page=<?= $totalPages ?>&limit=<?= $limit ?>&type=<?= $type ?>">
                                    <i class="fa fa-angle-double-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de detalhes da notificação -->
<div class="modal fade" id="notificationDetailModal" tabindex="-1" aria-labelledby="notificationDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationDetailModalLabel">Detalhes da Notificação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="notification-detail-content">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p>Carregando detalhes da notificação...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de reenvio de notificação -->
<div class="modal fade" id="resendNotificationModal" tabindex="-1" aria-labelledby="resendNotificationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resendNotificationModalLabel">Reenviar Notificação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja reenviar esta notificação para todos os destinatários que ainda não a receberam?</p>
                <form id="resendNotificationForm" action="<?= BASE_URL ?>admin/dashboard/resendNotification" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="notification_id" id="resendNotificationId" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-warning" form="resendNotificationForm">Reenviar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Visualizar detalhes da notificação
function viewNotificationDetails(notificationId) {
    // Abrir modal
    const modal = new bootstrap.Modal(document.getElementById('notificationDetailModal'));
    modal.show();
    
    // Carregar detalhes
    fetch(`<?= BASE_URL ?>admin/dashboard/api/notification_details?notification_id=${notificationId}`, {
        headers: {
            'X-CSRF-Token': '<?= $csrfToken ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            document.getElementById('notification-detail-content').innerHTML = 
                `<div class="alert alert-danger">${data.error}</div>`;
            return;
        }
        
        // Renderizar detalhes da notificação
        renderNotificationDetails(data);
    })
    .catch(error => {
        console.error('Erro ao carregar detalhes da notificação:', error);
        document.getElementById('notification-detail-content').innerHTML = 
            '<div class="alert alert-danger">Erro ao carregar detalhes da notificação.</div>';
    });
}

// Renderizar detalhes da notificação
function renderNotificationDetails(notification) {
    // Converter dados de contexto se necessário
    let context = notification.context;
    if (typeof context === 'string') {
        try {
            context = JSON.parse(context);
        } catch (e) {
            context = {};
        }
    }
    
    // Converter estatísticas de entrega se necessário
    let deliveryStats = notification.delivery_stats;
    if (typeof deliveryStats === 'string') {
        try {
            deliveryStats = JSON.parse(deliveryStats);
        } catch (e) {
            deliveryStats = {};
        }
    }
    
    // Criar HTML para os detalhes
    let html = `
        <div class="notification-details">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6 class="fw-bold">ID:</h6>
                    <p>#${notification.id}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold">Data de Criação:</h6>
                    <p>${formatDateTime(notification.created_at)}</p>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6 class="fw-bold">Tipo:</h6>
                    <p><span class="badge bg-${getNotificationTypeColorJs(notification.type)}">${getNotificationTypeNameJs(notification.type)}</span></p>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold">Status:</h6>
                    <p><span class="badge bg-${getDeliveryStatusColorJs(notification.status)}">${getDeliveryStatusNameJs(notification.status)}</span></p>
                </div>
            </div>
            
            <div class="mb-3">
                <h6 class="fw-bold">Título:</h6>
                <p>${escapeHtml(notification.title)}</p>
            </div>
            
            <div class="mb-3">
                <h6 class="fw-bold">Mensagem:</h6>
                <div class="p-3 bg-light rounded">
                    ${escapeHtml(notification.message).replace(/\n/g, '<br>')}
                </div>
            </div>
    `;
    
    // Adicionar informações de destinatários
    html += `
        <div class="mb-3">
            <h6 class="fw-bold">Destinatários:</h6>
            <div class="mb-2">
    `;
    
    if (notification.target_roles) {
        const roles = typeof notification.target_roles === 'string' 
            ? JSON.parse(notification.target_roles) 
            : notification.target_roles;
        
        roles.forEach(role => {
            html += `<span class="badge bg-secondary me-1">${getRoleNameJs(role)}</span>`;
        });
    } else if (notification.user_id) {
        html += `<p>Usuário específico ID: ${notification.user_id}</p>`;
    } else {
        html += `<p>Sem destinatários definidos</p>`;
    }
    
    html += `
            </div>
        </div>
    `;
    
    // Adicionar estatísticas de entrega
    html += `
        <div class="mb-3">
            <h6 class="fw-bold">Estatísticas de Entrega:</h6>
            <div class="row">
                <div class="col-md-6">
                    <p>Total de destinatários: ${deliveryStats.total || 0}</p>
                    <p>Entregas com sucesso: ${deliveryStats.delivered || 0}</p>
                    <p>Falhas na entrega: ${deliveryStats.failed || 0}</p>
                </div>
                <div class="col-md-6">
                    <div class="progress mb-2" style="height: 20px;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: ${deliveryStats.total > 0 ? (deliveryStats.delivered / deliveryStats.total) * 100 : 0}%;" 
                             aria-valuenow="${deliveryStats.delivered || 0}" 
                             aria-valuemin="0" 
                             aria-valuemax="${deliveryStats.total || 0}">
                            ${deliveryStats.total > 0 ? Math.round((deliveryStats.delivered / deliveryStats.total) * 100) : 0}%
                        </div>
                    </div>
                    <small>Última atualização: ${notification.updated_at ? formatDateTime(notification.updated_at) : 'N/A'}</small>
                </div>
            </div>
        </div>
    `;
    
    // Adicionar dados de contexto se disponíveis
    if (context && Object.keys(context).length > 0) {
        html += `
            <div class="mb-3">
                <h6 class="fw-bold">Dados de Contexto:</h6>
                <div class="p-3 bg-light rounded">
                    <pre class="mb-0">${JSON.stringify(context, null, 2)}</pre>
                </div>
            </div>
        `;
    }
    
    html += '</div>';
    
    // Atualizar conteúdo do modal
    document.getElementById('notification-detail-content').innerHTML = html;
}

// Configurar modal de reenvio de notificação
function resendNotification(notificationId) {
    document.getElementById('resendNotificationId').value = notificationId;
    
    const modal = new bootstrap.Modal(document.getElementById('resendNotificationModal'));
    modal.show();
}

// Funções utilitárias
function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    
    const date = new Date(dateString);
    return date.toLocaleString('pt-BR');
}

function escapeHtml(text) {
    if (!text) return '';
    
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getNotificationTypeColorJs(type) {
    const colors = {
        'info': 'info',
        'success': 'success',
        'warning': 'warning',
        'error': 'danger'
    };
    
    return colors[type] || 'secondary';
}

function getNotificationTypeNameJs(type) {
    const names = {
        'info': 'Informação',
        'success': 'Sucesso',
        'warning': 'Alerta',
        'error': 'Erro'
    };
    
    return names[type] || type;
}

function getDeliveryStatusColorJs(status) {
    const colors = {
        'pending': 'warning',
        'in_progress': 'info',
        'completed': 'success',
        'failed': 'danger'
    };
    
    return colors[status] || 'secondary';
}

function getDeliveryStatusNameJs(status) {
    const names = {
        'pending': 'Pendente',
        'in_progress': 'Em Progresso',
        'completed': 'Concluído',
        'failed': 'Falhou'
    };
    
    return names[status] || status;
}

function getRoleNameJs(role) {
    const names = {
        'admin': 'Administrador',
        'manager': 'Gerente',
        'printer_operator': 'Operador de Impressora',
        'customer': 'Cliente',
        'user': 'Usuário'
    };
    
    return names[role] || role;
}
</script>

<style>
.dashboard-container {
    padding: 20px;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

/* Ajustes para telas menores */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .dashboard-actions {
        margin-top: 15px;
        width: 100%;
    }
    
    .dashboard-actions .btn {
        width: 100%;
    }
}
</style>

<?php
/**
 * Retorna a cor para um tipo de notificação
 * 
 * @param string $type Tipo da notificação
 * @return string Classe CSS da cor
 */
function getNotificationTypeColor($type) {
    $colors = [
        'info' => 'info',
        'success' => 'success',
        'warning' => 'warning',
        'error' => 'danger'
    ];
    
    return $colors[$type] ?? 'secondary';
}

/**
 * Retorna o nome legível para um tipo de notificação
 * 
 * @param string $type Tipo da notificação
 * @return string Nome legível
 */
function getNotificationTypeName($type) {
    $names = [
        'info' => 'Informação',
        'success' => 'Sucesso',
        'warning' => 'Alerta',
        'error' => 'Erro'
    ];
    
    return $names[$type] ?? $type;
}

/**
 * Retorna a cor para um status de entrega
 * 
 * @param string $status Status de entrega
 * @return string Classe CSS da cor
 */
function getDeliveryStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'in_progress' => 'info',
        'completed' => 'success',
        'failed' => 'danger'
    ];
    
    return $colors[$status] ?? 'secondary';
}

/**
 * Retorna o nome legível para um status de entrega
 * 
 * @param string $status Status de entrega
 * @return string Nome legível
 */
function getDeliveryStatusName($status) {
    $names = [
        'pending' => 'Pendente',
        'in_progress' => 'Em Progresso',
        'completed' => 'Concluído',
        'failed' => 'Falhou'
    ];
    
    return $names[$status] ?? $status;
}

/**
 * Retorna o nome legível para um papel de usuário
 * 
 * @param string $role Papel de usuário
 * @return string Nome legível
 */
function getRoleName($role) {
    $names = [
        'admin' => 'Administrador',
        'manager' => 'Gerente',
        'printer_operator' => 'Operador de Impressora',
        'customer' => 'Cliente',
        'user' => 'Usuário'
    ];
    
    return $names[$role] ?? $role;
}

// Incluir footer
include_once APP_PATH . '/views/admin/partials/footer.php';
?>
