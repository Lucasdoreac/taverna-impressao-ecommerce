<?php
/**
 * Template parcial para exibição de notificações
 * 
 * @package Taverna da Impressão 3D
 * @version 1.0.0
 * 
 * Variáveis esperadas:
 * @var array $notification Dados da notificação
 */

// Garantir que a notificação existe
if (empty($notification) || !is_array($notification)) {
    return;
}

// Sanitizar dados (defesa em profundidade - mesmo que já sanitizados no controller)
$id = isset($notification['id']) ? (int)$notification['id'] : 0;
$title = isset($notification['title']) ? htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') : '';
$message = isset($notification['message']) ? htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8') : '';
$status = isset($notification['status']) && $notification['status'] === 'read' ? 'read' : 'unread';
$type = isset($notification['type']) ? htmlspecialchars($notification['type'], ENT_QUOTES, 'UTF-8') : 'info';
$createdAt = isset($notification['created_at']) ? htmlspecialchars($notification['created_at'], ENT_QUOTES, 'UTF-8') : '';
$readAt = isset($notification['read_at']) ? htmlspecialchars($notification['read_at'], ENT_QUOTES, 'UTF-8') : '';

// Obter dados de contexto
$context = isset($notification['context']) && is_array($notification['context']) ? $notification['context'] : [];
$priority = isset($context['priority']) ? htmlspecialchars($context['priority'], ENT_QUOTES, 'UTF-8') : 'normal';
$url = isset($context['url']) ? htmlspecialchars($context['url'], ENT_QUOTES, 'UTF-8') : '';
$processToken = isset($context['process_token']) ? htmlspecialchars($context['process_token'], ENT_QUOTES, 'UTF-8') : '';

// Formatar a data relativa
$formattedDate = formatRelativeDate($createdAt);

// Determinar classes CSS com base no tipo e status
$classes = [
    'notification-item',
    'notification-' . $status,
    'notification-' . $type,
    'notification-priority-' . $priority
];
$classString = implode(' ', $classes);

// Ícone com base no tipo de notificação
$icon = getNotificationIcon($type);

// Determinar se deve marcar como lida automaticamente
$autoMarkAsRead = $status === 'unread' ? 'true' : 'false';
?>
<div class="<?= $classString ?>" 
     data-notification-id="<?= $id ?>" 
     data-process-token="<?= $processToken ?>"
     data-auto-mark-read="<?= $autoMarkAsRead ?>">
    
    <div class="notification-icon">
        <i class="<?= $icon ?>"></i>
    </div>
    
    <div class="notification-content">
        <div class="notification-header">
            <h4 class="notification-title"><?= $title ?></h4>
            <span class="notification-time" title="<?= $createdAt ?>"><?= $formattedDate ?></span>
        </div>
        
        <div class="notification-body">
            <p><?= $message ?></p>
            
            <?php if (!empty($context['completion_percentage'])): ?>
            <div class="notification-progress">
                <div class="progress">
                    <div class="progress-bar" role="progressbar" 
                         style="width: <?= (int)$context['completion_percentage'] ?>%"
                         aria-valuenow="<?= (int)$context['completion_percentage'] ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        <?= (int)$context['completion_percentage'] ?>%
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($url)): ?>
            <div class="notification-actions">
                <a href="<?= $url ?>" class="btn btn-sm btn-primary">
                    Visualizar Detalhes
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="notification-controls">
        <button type="button" class="btn-close notification-dismiss" 
                aria-label="Marcar como lida" 
                data-notification-id="<?= $id ?>">
        </button>
    </div>
</div>

<?php
/**
 * Retorna um ícone com base no tipo de notificação
 * 
 * @param string $type Tipo de notificação
 * @return string Classe CSS do ícone
 */
function getNotificationIcon($type) {
    $icons = [
        'process_status' => 'fas fa-sync-alt',
        'process_progress' => 'fas fa-tasks',
        'process_completed' => 'fas fa-check-circle',
        'process_failed' => 'fas fa-exclamation-circle',
        'process_results' => 'fas fa-file-download',
        'process_expiration' => 'fas fa-clock',
        'info' => 'fas fa-info-circle',
        'success' => 'fas fa-check-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'error' => 'fas fa-times-circle'
    ];
    
    return isset($icons[$type]) ? $icons[$type] : $icons['info'];
}

/**
 * Formata uma data em formato relativo
 * 
 * @param string $dateStr String de data no formato Y-m-d H:i:s
 * @return string Data formatada em formato relativo
 */
function formatRelativeDate($dateStr) {
    $date = new DateTime($dateStr);
    $now = new DateTime();
    $diff = $date->diff($now);
    
    if ($diff->y > 0) {
        return $diff->y . ' ano' . ($diff->y > 1 ? 's' : '') . ' atrás';
    }
    
    if ($diff->m > 0) {
        return $diff->m . ' mês' . ($diff->m > 1 ? 'es' : '') . ' atrás';
    }
    
    if ($diff->d > 6) {
        return floor($diff->d / 7) . ' semana' . (floor($diff->d / 7) > 1 ? 's' : '') . ' atrás';
    }
    
    if ($diff->d > 0) {
        return $diff->d . ' dia' . ($diff->d > 1 ? 's' : '') . ' atrás';
    }
    
    if ($diff->h > 0) {
        return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '') . ' atrás';
    }
    
    if ($diff->i > 0) {
        return $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '') . ' atrás';
    }
    
    return 'Agora';
}
?>
