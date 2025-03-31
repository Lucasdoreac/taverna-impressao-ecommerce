<?php
/**
 * View para configurações de preferências de notificação
 * 
 * Esta página permite aos usuários personalizar suas preferências de notificação,
 * incluindo quais tipos de notificações desejam receber e por quais canais.
 */

// Configurar o título da página
$title = isset($title) ? $title : 'Preferências de Notificação';
?>

<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-3">
            <!-- Menu lateral da conta -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Minha Conta</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>account" class="list-group-item list-group-item-action">Visão Geral</a>
                    <a href="<?= BASE_URL ?>account/orders" class="list-group-item list-group-item-action">Meus Pedidos</a>
                    <a href="<?= BASE_URL ?>account/customizations" class="list-group-item list-group-item-action">Minhas Customizações</a>
                    <a href="<?= BASE_URL ?>account/print-jobs" class="list-group-item list-group-item-action">Impressões 3D</a>
                    <a href="<?= BASE_URL ?>preferencias-notificacao" class="list-group-item list-group-item-action active">Preferências de Notificação</a>
                    <a href="<?= BASE_URL ?>account/settings" class="list-group-item list-group-item-action">Configurações</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <!-- Conteúdo principal -->
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Preferências de Notificação</h4>
                    <button class="btn btn-light btn-sm" id="reset-preferences">Restaurar Padrões</button>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $_SESSION['success'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <p class="text-muted mb-4">
                        Configure quais notificações você deseja receber e por quais canais.
                        Notificações críticas relacionadas aos seus pedidos e status de impressão não podem ser desativadas.
                    </p>
                    
                    <form id="notification-preferences-form" action="<?= BASE_URL ?>preferencias-notificacao/salvar" method="post">
                        <!-- Categorias de notificações -->
                        <?php foreach ($notificationTypes as $type): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><?= $type['name'] ?></h5>
                                    <p class="text-muted small mb-0"><?= $type['description'] ?></p>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($notificationChannels as $channel): ?>
                                            <?php 
                                                // Encontrar a preferência atual para este tipo e canal
                                                $currentPreference = null;
                                                foreach ($preferences as $pref) {
                                                    if ($pref['type_id'] == $type['id'] && $pref['channel_id'] == $channel['id']) {
                                                        $currentPreference = $pref;
                                                        break;
                                                    }
                                                }
                                                
                                                $isEnabled = $currentPreference && $currentPreference['is_enabled'] ? true : false;
                                                $frequency = $currentPreference ? $currentPreference['frequency'] : 'realtime';
                                                $isCritical = isset($type['is_critical']) && $type['is_critical'];
                                            ?>
                                            <div class="col-md-4 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-header bg-light">
                                                        <div class="form-check form-switch">
                                                            <input 
                                                                class="form-check-input notification-toggle" 
                                                                type="checkbox" 
                                                                name="preferences[<?= $type['id'] ?>][<?= $channel['id'] ?>][enabled]" 
                                                                id="pref_<?= $type['id'] ?>_<?= $channel['id'] ?>"
                                                                data-type-id="<?= $type['id'] ?>"
                                                                data-channel-id="<?= $channel['id'] ?>"
                                                                <?= $isEnabled ? 'checked' : '' ?>
                                                                <?= $isCritical ? 'disabled checked' : '' ?>
                                                            >
                                                            <label class="form-check-label" for="pref_<?= $type['id'] ?>_<?= $channel['id'] ?>">
                                                                <strong><?= $channel['name'] ?></strong>
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="card-body">
                                                        <p class="card-text small text-muted"><?= $channel['description'] ?></p>
                                                        
                                                        <?php if ($channel['supports_frequency']): ?>
                                                            <div class="form-group mt-2">
                                                                <label class="form-label small">Frequência:</label>
                                                                <select 
                                                                    class="form-select form-select-sm frequency-select" 
                                                                    name="preferences[<?= $type['id'] ?>][<?= $channel['id'] ?>][frequency]"
                                                                    data-type-id="<?= $type['id'] ?>"
                                                                    data-channel-id="<?= $channel['id'] ?>"
                                                                    <?= !$isEnabled ? 'disabled' : '' ?>
                                                                >
                                                                    <option value="realtime" <?= $frequency == 'realtime' ? 'selected' : '' ?>>Tempo real</option>
                                                                    <option value="daily" <?= $frequency == 'daily' ? 'selected' : '' ?>>Diário</option>
                                                                    <option value="weekly" <?= $frequency == 'weekly' ? 'selected' : '' ?>>Semanal</option>
                                                                </select>
                                                            </div>
                                                        <?php else: ?>
                                                            <input type="hidden" name="preferences[<?= $type['id'] ?>][<?= $channel['id'] ?>][frequency]" value="realtime">
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">Salvar Preferências</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para confirmar restauração de padrões -->
<div class="modal fade" id="resetConfirmModal" tabindex="-1" aria-labelledby="resetConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetConfirmModalLabel">Confirmação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja restaurar todas as preferências de notificação para os valores padrão?</p>
                <p class="text-muted small">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="<?= BASE_URL ?>preferencias-notificacao/inicializar" class="btn btn-primary">Restaurar Padrões</a>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript para manipulação da interface -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar modal de confirmação ao clicar no botão de restaurar padrões
    document.getElementById('reset-preferences').addEventListener('click', function() {
        const resetModal = new bootstrap.Modal(document.getElementById('resetConfirmModal'));
        resetModal.show();
    });
    
    // Habilitar/desabilitar campos de frequência quando o toggle é alterado
    document.querySelectorAll('.notification-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const typeId = this.dataset.typeId;
            const channelId = this.dataset.channelId;
            const frequencySelect = document.querySelector(`.frequency-select[data-type-id="${typeId}"][data-channel-id="${channelId}"]`);
            
            if (frequencySelect) {
                frequencySelect.disabled = !this.checked;
            }
            
            // Se o toggle for alterado, enviar atualização via AJAX
            updatePreference(typeId, channelId, this.checked, frequencySelect ? frequencySelect.value : 'realtime');
        });
    });
    
    // Atualizar preferência quando a frequência for alterada
    document.querySelectorAll('.frequency-select').forEach(function(select) {
        select.addEventListener('change', function() {
            const typeId = this.dataset.typeId;
            const channelId = this.dataset.channelId;
            const toggle = document.querySelector(`.notification-toggle[data-type-id="${typeId}"][data-channel-id="${channelId}"]`);
            
            if (toggle && toggle.checked) {
                updatePreference(typeId, channelId, true, this.value);
            }
        });
    });
    
    // Função para atualizar preferência via AJAX
    function updatePreference(typeId, channelId, isEnabled, frequency) {
        const data = {
            typeId: typeId,
            channelId: channelId,
            isEnabled: isEnabled,
            frequency: frequency
        };
        
        fetch('<?= BASE_URL ?>preferencias-notificacao/atualizar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar notificação de sucesso temporária
                const notification = document.createElement('div');
                notification.className = 'position-fixed bottom-0 end-0 p-3';
                notification.style.zIndex = 5;
                notification.innerHTML = `
                    <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header bg-success text-white">
                            <strong class="me-auto">Sucesso</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            Preferência atualizada com sucesso!
                        </div>
                    </div>
                `;
                document.body.appendChild(notification);
                
                // Remover notificação após 3 segundos
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            } else {
                // Mostrar mensagem de erro
                alert('Erro ao atualizar preferência: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao processar a solicitação.');
        });
    }
});
</script>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
