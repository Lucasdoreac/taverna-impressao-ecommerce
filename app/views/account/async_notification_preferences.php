<?php
/**
 * View de Preferências de Notificação para Processos Assíncronos
 * 
 * @var array $preferences Array de preferências atuais do usuário
 * @var array $notificationTypes Tipos de notificação disponíveis
 * @var string $csrfToken Token CSRF para o formulário
 */

// Garantir que nenhum dado sensível seja exposto
defined('SECURE_ACCESS') or die('Acesso direto negado');
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-3">
            <?php include dirname(__DIR__) . '/account/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Preferências de Notificação - Processos Assíncronos</h5>
                </div>
                
                <div class="card-body">
                    <?php if (isset($success) && $success): ?>
                        <div class="alert alert-success">
                            Suas preferências foram atualizadas com sucesso.
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error) && $error): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($errorMessage ?? 'Ocorreu um erro ao atualizar suas preferências.', ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="/conta/notification-preferences/async" id="notification-prefs-form">
                        <!-- Campo CSRF hidden -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        
                        <p class="text-muted mb-4">
                            Configure como e quando deseja receber notificações sobre seus processos em andamento.
                        </p>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tipo de Notificação</th>
                                        <th class="text-center">Ativar</th>
                                        <th class="text-center">Web</th>
                                        <th class="text-center">Email</th>
                                        <th class="text-center">Push</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notificationTypes as $type): ?>
                                        <?php 
                                        // Verificar se esse tipo de notificação é crítico (não pode ser desativado)
                                        $isCritical = isset($type['is_critical']) && $type['is_critical']; 
                                        
                                        // Obter preferências atuais desse tipo
                                        $pref = $preferences[$type['code']] ?? [
                                            'is_enabled' => true,
                                            'email_enabled' => true,
                                            'push_enabled' => true
                                        ];
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($type['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                <p class="text-muted small mb-0"><?= htmlspecialchars($type['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                            </td>
                                            
                                            <!-- Botão Ativar/Desativar -->
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" 
                                                        name="preferences[<?= htmlspecialchars($type['code'], ENT_QUOTES, 'UTF-8'); ?>][is_enabled]" 
                                                        id="enable_<?= htmlspecialchars($type['code'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        value="1"
                                                        <?= $pref['is_enabled'] ? 'checked' : ''; ?>
                                                        <?= $isCritical ? 'disabled checked' : ''; ?>>
                                                    <?php if ($isCritical): ?>
                                                        <input type="hidden" name="preferences[<?= htmlspecialchars($type['code'], ENT_QUOTES, 'UTF-8'); ?>][is_enabled]" value="1">
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            
                                            <!-- Opção Web (sempre ativada, apenas visual) -->
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input" type="checkbox" value="1" checked disabled>
                                                    <input type="hidden" name="preferences[<?= htmlspecialchars($type['code'], ENT_QUOTES, 'UTF-8'); ?>][web_enabled]" value="1">
                                                </div>
                                            </td>
                                            
                                            <!-- Opção Email -->
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input notification-channel" type="checkbox" 
                                                        name="preferences[<?= htmlspecialchars($type['code'], ENT_QUOTES, 'UTF-8'); ?>][email_enabled]" 
                                                        id="email_<?= htmlspecialchars($type['code'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        value="1"
                                                        <?= $pref['email_enabled'] ? 'checked' : ''; ?>
                                                        <?= !$pref['is_enabled'] && !$isCritical ? 'disabled' : ''; ?>>
                                                </div>
                                            </td>
                                            
                                            <!-- Opção Push -->
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input notification-channel" type="checkbox" 
                                                        name="preferences[<?= htmlspecialchars($type['code'], ENT_QUOTES, 'UTF-8'); ?>][push_enabled]" 
                                                        id="push_<?= htmlspecialchars($type['code'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        value="1"
                                                        <?= $pref['push_enabled'] ? 'checked' : ''; ?>
                                                        <?= !$pref['is_enabled'] && !$isCritical ? 'disabled' : ''; ?>>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <a href="/conta" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Salvar Preferências</button>
                        </div>
                    </form>
                </div>
                
                <div class="card-footer bg-light">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-0">Notificações Push</h6>
                            <p class="text-muted small mb-0">
                                Status: <span id="push-status">Verificando...</span>
                            </p>
                        </div>
                        <button id="push-toggle" class="btn btn-sm btn-outline-primary">
                            Ativar Notificações Push
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle para habilitar/desabilitar canais ao ativar/desativar tipo
    const toggles = document.querySelectorAll('input[id^="enable_"]');
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const typeCode = this.id.replace('enable_', '');
            const channels = document.querySelectorAll(`input[id^="email_${typeCode}"], input[id^="push_${typeCode}"]`);
            
            channels.forEach(channel => {
                channel.disabled = !this.checked;
            });
        });
    });
    
    // Verificar suporte a notificações push
    const pushToggle = document.getElementById('push-toggle');
    const pushStatus = document.getElementById('push-status');
    
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        // Verificar permissão atual
        if (Notification.permission === 'granted') {
            pushStatus.textContent = 'Ativado';
            pushToggle.textContent = 'Desativar Notificações Push';
            pushToggle.classList.replace('btn-outline-primary', 'btn-primary');
        } else if (Notification.permission === 'denied') {
            pushStatus.textContent = 'Bloqueado no navegador';
            pushToggle.textContent = 'Desbloquear (Configurações do Navegador)';
            pushToggle.classList.replace('btn-outline-primary', 'btn-outline-danger');
            pushToggle.disabled = true;
        } else {
            pushStatus.textContent = 'Desativado';
            pushToggle.textContent = 'Ativar Notificações Push';
        }
        
        // Evento para alternar notificações push
        pushToggle.addEventListener('click', function() {
            if (Notification.permission === 'granted') {
                // Já concedidas, exibir instruções para desativar no navegador
                alert('Para desativar as notificações push, acesse as configurações do seu navegador.');
            } else if (Notification.permission === 'denied') {
                // Já negadas, exibir instruções para permitir no navegador
                alert('Para permitir notificações push, acesse as configurações do seu navegador e permita notificações para este site.');
            } else {
                // Solicitar permissão
                Notification.requestPermission()
                    .then(permission => {
                        if (permission === 'granted') {
                            pushStatus.textContent = 'Ativado';
                            pushToggle.textContent = 'Desativar Notificações Push';
                            pushToggle.classList.replace('btn-outline-primary', 'btn-primary');
                            
                            // Registrar para notificações push
                            registerForPushNotifications();
                        } else {
                            pushStatus.textContent = permission === 'denied' ? 'Bloqueado no navegador' : 'Desativado';
                            pushToggle.textContent = permission === 'denied' ? 'Desbloquear (Configurações do Navegador)' : 'Ativar Notificações Push';
                            pushToggle.disabled = permission === 'denied';
                            
                            if (permission === 'denied') {
                                pushToggle.classList.replace('btn-outline-primary', 'btn-outline-danger');
                            }
                        }
                    });
            }
        });
    } else {
        // Navegador não suporta notificações push
        pushStatus.textContent = 'Não suportado neste navegador';
        pushToggle.disabled = true;
        pushToggle.textContent = 'Não disponível';
    }
});

// Função para registrar para notificações push
async function registerForPushNotifications() {
    try {
        // Obter token CSRF
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        
        // Registrar service worker se ainda não estiver registrado
        const registration = await navigator.serviceWorker.register('/service-worker.js');
        
        // Obter chave pública VAPID do servidor
        const response = await fetch('/api/notifications/vapid-public-key', {
            headers: {
                'X-CSRF-Token': csrfToken
            }
        });
        
        if (!response.ok) {
            throw new Error('Falha ao obter chave pública VAPID');
        }
        
        const data = await response.json();
        
        if (!data.publicKey) {
            throw new Error('Chave pública VAPID não encontrada');
        }
        
        // Converter chave para Uint8Array
        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/-/g, '+')
                .replace(/_/g, '/');
                
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            
            return outputArray;
        }
        
        // Inscrever para notificações push
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(data.publicKey)
        });
        
        // Enviar subscrição para o servidor
        await fetch('/api/notifications/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                subscription,
                csrf_token: csrfToken
            })
        });
        
        console.log('Notificações push registradas com sucesso');
    } catch (error) {
        console.error('Erro ao registrar para notificações push:', error);
    }
}
</script>
