<?php
/**
 * Dropdown de notificações para inclusão no cabeçalho
 * 
 * @package Taverna da Impressão 3D
 * @version 1.0.0
 */

// Verificar se o usuário está autenticado
if (!SecurityManager::checkAuthentication()) {
    return;
}

// Obter contador de notificações não lidas - inicialmente vazio
$unreadCount = 0;
$latestNotifications = [];

// Criar token CSRF
$csrfToken = SecurityManager::getCsrfToken();
?>

<div class="notification-wrapper dropdown">
    <button class="btn btn-icon notification-toggle" type="button" 
            id="notificationDropdownToggle" 
            data-bs-toggle="dropdown" 
            aria-expanded="false"
            aria-haspopup="true">
        <i class="fas fa-bell"></i>
        <span class="notification-badge" id="notification-count" 
              <?= $unreadCount > 0 ? '' : 'style="display: none;"' ?>>
            <?= $unreadCount ?>
        </span>
    </button>
    
    <div class="dropdown-menu notification-dropdown dropdown-menu-end" 
         id="notification-dropdown" 
         aria-labelledby="notificationDropdownToggle">
        
        <div class="notification-header d-flex justify-content-between">
            <h6 class="dropdown-header">Notificações</h6>
            <a href="#" id="mark-all-read" class="text-primary small">Marcar todas como lidas</a>
        </div>
        
        <div class="notification-list" id="notification-list">
            <div class="notification-loading d-flex justify-content-center py-3">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
            </div>
        </div>
        
        <div class="dropdown-divider"></div>
        
        <a class="dropdown-item text-center" href="/notifications">
            Ver todas
        </a>
    </div>
</div>

<!-- Template para notificações geradas via JavaScript -->
<script type="text/template" id="notification-template">
    <a href="{url}" class="dropdown-item notification-item notification-{status} notification-priority-{priority}" data-id="{id}">
        <div class="notification-content">
            <div class="notification-title">{title}</div>
            <div class="notification-text">{message}</div>
            <div class="notification-time">{time_ago}</div>
        </div>
    </a>
</script>

<script type="text/template" id="notification-empty-template">
    <div class="dropdown-item notification-empty text-center text-muted py-3">
        <i class="fas fa-bell-slash mb-2"></i>
        <p class="mb-0">Nenhuma notificação</p>
    </div>
</script>

<!-- Meta tag com CSRF token para uso pelo JavaScript -->
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
