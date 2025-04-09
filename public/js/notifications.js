/**
 * Gerenciador de Notificações Push para Cliente
 * 
 * Este arquivo contém a lógica para registrar service workers,
 * subscrever notificações push e gerenciar interações do usuário
 * com as notificações, seguindo as melhores práticas de segurança.
 * 
 * @author Taverna da Impressão
 * @version 1.0.0
 */

// IIFE para isolar o escopo
(function() {
    'use strict';

    // Verificar suporte a Service Worker e Push API
    const isPushSupported = 'serviceWorker' in navigator && 'PushManager' in window;
    
    // Armazenar objetos importantes
    let swRegistration = null;
    let isSubscribed = false;
    let applicationServerKey = null;
    
    // Elementos da UI
    const notificationButton = document.getElementById('notification-button');
    const notificationStatus = document.getElementById('notification-status');
    const notificationCount = document.getElementById('notification-count');
    const notificationDropdown = document.getElementById('notification-dropdown');
    
    /**
     * Inicializa o gerenciador de notificações
     */
    function initializeNotifications() {
        // Verificar suporte
        if (!isPushSupported) {
            console.log('Este navegador não suporta notificações push.');
            updateUIForPushUnsupported();
            return;
        }
        
        // Obter chave pública VAPID
        getPublicKey().then(() => {
            // Registrar service worker
            registerServiceWorker();
            
            // Inicializar UI
            initializeDOMElements();
            
            // Carregar notificações não lidas
            loadUnreadNotifications();
            
            // Configurar polling para verificar novas notificações
            setInterval(loadUnreadNotifications, 60000); // A cada minuto
        }).catch(error => {
            console.error('Erro ao inicializar notificações:', error);
        });
    }
    
    /**
     * Obtém a chave pública VAPID do servidor
     */
    function getPublicKey() {
        // Obter token CSRF da metatag
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        return fetch('/api/notifications/vapid-key', {
            method: 'GET',
            headers: {
                'X-CSRF-Token': csrfToken,
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao obter chave pública VAPID');
            }
            return response.json();
        })
        .then(data => {
            if (data.publicKey) {
                applicationServerKey = urlB64ToUint8Array(data.publicKey);
            } else {
                throw new Error('Chave pública VAPID não encontrada');
            }
        });
    }
    
    /**
     * Registra o service worker para notificações push
     */
    function registerServiceWorker() {
        navigator.serviceWorker.register('/service-worker.js')
        .then(function(registration) {
            swRegistration = registration;
            
            // Verificar status de subscrição
            return swRegistration.pushManager.getSubscription();
        })
        .then(function(subscription) {
            isSubscribed = !(subscription === null);
            
            updateSubscriptionUI();
        })
        .catch(function(error) {
            console.error('Erro ao registrar service worker:', error);
        });
    }
    
    /**
     * Inicializa os elementos do DOM
     */
    function initializeDOMElements() {
        if (notificationButton) {
            notificationButton.addEventListener('click', function() {
                if (Notification.permission === 'denied') {
                    // Notificações foram bloqueadas pelo usuário
                    showNotificationDialog('Notificações bloqueadas', 
                        'Você bloqueou as notificações. Por favor, altere as configurações do seu navegador para permitir notificações deste site.',
                        'error');
                    return;
                }
                
                if (isSubscribed) {
                    unsubscribeFromPush();
                } else {
                    subscribeUserToPush();
                }
            });
        }
        
        // Inicializar botão "Marcar todas como lidas"
        const markAllAsReadButton = document.getElementById('mark-all-as-read');
        if (markAllAsReadButton) {
            markAllAsReadButton.addEventListener('click', markAllNotificationsAsRead);
        }
        
        // Adicionar listeners para os botões de cada notificação
        document.addEventListener('click', function(event) {
            // Verificar se é um botão de "marcar como lida"
            if (event.target.classList.contains('mark-notification-read')) {
                const notificationId = event.target.getAttribute('data-notification-id');
                if (notificationId) {
                    markNotificationAsRead(notificationId);
                }
            }
        });
    }
    
    /**
     * Atualiza a UI com base no status de subscrição
     */
    function updateSubscriptionUI() {
        if (!notificationButton) return;
        
        if (Notification.permission === 'denied') {
            notificationButton.textContent = 'Notificações bloqueadas';
            notificationButton.disabled = true;
            updateSubscriptionOnServer(null);
            return;
        }
        
        if (isSubscribed) {
            notificationButton.textContent = 'Desativar notificações';
            notificationButton.classList.remove('btn-primary');
            notificationButton.classList.add('btn-secondary');
        } else {
            notificationButton.textContent = 'Ativar notificações';
            notificationButton.classList.remove('btn-secondary');
            notificationButton.classList.add('btn-primary');
        }
        
        notificationButton.disabled = false;
    }
    
    /**
     * Atualiza a UI quando o navegador não suporta notificações push
     */
    function updateUIForPushUnsupported() {
        if (!notificationButton) return;
        
        notificationButton.textContent = 'Notificações não suportadas';
        notificationButton.disabled = true;
    }
    
    /**
     * Subscreve o usuário para notificações push
     */
    function subscribeUserToPush() {
        const applicationServerKey = urlB64ToUint8Array(applicationServerPublicKey);
        
        swRegistration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: applicationServerKey
        })
        .then(function(subscription) {
            console.log('Usuário inscrito para notificações push');
            
            updateSubscriptionOnServer(subscription);
            isSubscribed = true;
            updateSubscriptionUI();
        })
        .catch(function(error) {
            console.error('Falha ao subscrever usuário:', error);
            updateSubscriptionUI();
        });
    }
    
    /**
     * Cancela a subscrição de notificações push
     */
    function unsubscribeFromPush() {
        swRegistration.pushManager.getSubscription()
        .then(function(subscription) {
            if (subscription) {
                return subscription.unsubscribe();
            }
        })
        .then(function() {
            updateSubscriptionOnServer(null);
            
            console.log('Usuário cancelou subscrição de notificações push');
            isSubscribed = false;
            
            updateSubscriptionUI();
        })
        .catch(function(error) {
            console.error('Erro ao cancelar subscrição:', error);
            updateSubscriptionUI();
        });
    }
    
    /**
     * Atualiza a subscrição no servidor
     * 
     * @param {PushSubscription} subscription Objeto de subscrição ou null para cancelar
     */
    function updateSubscriptionOnServer(subscription) {
        // Obter token CSRF da metatag
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        const endpoint = subscription ? '/api/notifications/subscribe' : '/api/notifications/unsubscribe';
        
        return fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                subscription: subscription
            })
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Erro ao atualizar subscrição no servidor');
            }
            
            return response.json();
        })
        .then(function(responseData) {
            console.log(responseData.message);
        })
        .catch(function(error) {
            console.error('Erro ao atualizar subscrição:', error);
        });
    }
    
    /**
     * Carrega notificações não lidas do servidor
     */
    function loadUnreadNotifications() {
        // Verificar se estamos logados (existência do contador de notificações)
        if (!notificationCount) return;
        
        // Obter token CSRF da metatag
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch('/api/notifications/unread', {
            method: 'GET',
            headers: {
                'X-CSRF-Token': csrfToken,
                'Content-Type': 'application/json'
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Erro ao carregar notificações');
            }
            
            return response.json();
        })
        .then(function(data) {
            updateNotificationUI(data.notifications, data.count);
        })
        .catch(function(error) {
            console.error('Erro ao carregar notificações:', error);
        });
    }
    
    /**
     * Atualiza a UI com as notificações carregadas
     * 
     * @param {Array} notifications Array de notificações
     * @param {Number} count Número total de notificações
     */
    function updateNotificationUI(notifications, count) {
        // Atualizar contador
        if (notificationCount) {
            if (count > 0) {
                notificationCount.textContent = count;
                notificationCount.classList.remove('d-none');
            } else {
                notificationCount.classList.add('d-none');
            }
        }
        
        // Atualizar dropdown de notificações
        if (notificationDropdown) {
            const notificationContent = document.createElement('div');
            
            if (notifications.length === 0) {
                notificationContent.innerHTML = '<div class="dropdown-item text-center">Nenhuma notificação não lida</div>';
            } else {
                // Adicionar cabeçalho
                notificationContent.innerHTML = `
                    <div class="dropdown-header d-flex justify-content-between align-items-center">
                        <span>Notificações (${count})</span>
                        <button id="mark-all-as-read" class="btn btn-sm btn-link">Marcar todas como lidas</button>
                    </div>
                    <div class="dropdown-divider"></div>
                `;
                
                // Adicionar cada notificação
                notifications.forEach(function(notification) {
                    const notificationDate = new Date(notification.created_at);
                    const formattedDate = notificationDate.toLocaleDateString() + ' ' + 
                                         notificationDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    
                    const notificationItem = document.createElement('div');
                    notificationItem.className = 'dropdown-item notification-item';
                    notificationItem.setAttribute('data-notification-id', notification.id);
                    
                    notificationItem.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>${notification.title}</strong>
                                <p class="mb-0">${notification.message}</p>
                                <small class="text-muted">${formattedDate}</small>
                            </div>
                            <button class="btn btn-sm btn-link mark-notification-read" data-notification-id="${notification.id}">
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                    `;
                    
                    // Adicionar link se houver
                    if (notification.link) {
                        notificationItem.addEventListener('click', function(event) {
                            // Não seguir link se clicou no botão de marcar como lida
                            if (!event.target.classList.contains('mark-notification-read') && 
                                !event.target.parentElement.classList.contains('mark-notification-read')) {
                                window.location.href = notification.link;
                            }
                        });
                        notificationItem.style.cursor = 'pointer';
                    }
                    
                    notificationContent.appendChild(notificationItem);
                    notificationContent.appendChild(document.createElement('div')).className = 'dropdown-divider';
                });
                
                // Adicionar link para ver todas
                const viewAllLink = document.createElement('a');
                viewAllLink.className = 'dropdown-item text-center';
                viewAllLink.href = '/notifications';
                viewAllLink.textContent = 'Ver todas as notificações';
                notificationContent.appendChild(viewAllLink);
            }
            
            // Substituir conteúdo atual
            notificationDropdown.innerHTML = '';
            notificationDropdown.appendChild(notificationContent);
            
            // Reinicializar botão "Marcar todas como lidas"
            const markAllAsReadButton = document.getElementById('mark-all-as-read');
            if (markAllAsReadButton) {
                markAllAsReadButton.addEventListener('click', markAllNotificationsAsRead);
            }
        }
    }
    
    /**
     * Marca uma notificação como lida
     * 
     * @param {String} notificationId ID da notificação
     */
    function markNotificationAsRead(notificationId) {
        // Obter token CSRF da metatag
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch('/api/notifications/mark-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                notification_id: notificationId
            })
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Erro ao marcar notificação como lida');
            }
            
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                // Remover a notificação da UI
                const notificationItem = document.querySelector(`.notification-item[data-notification-id="${notificationId}"]`);
                if (notificationItem) {
                    const divider = notificationItem.nextElementSibling;
                    if (divider && divider.classList.contains('dropdown-divider')) {
                        divider.remove();
                    }
                    notificationItem.remove();
                }
                
                // Recarregar notificações para atualizar contador
                loadUnreadNotifications();
            }
        })
        .catch(function(error) {
            console.error('Erro ao marcar notificação como lida:', error);
        });
    }
    
    /**
     * Marca todas as notificações como lidas
     */
    function markAllNotificationsAsRead(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Obter token CSRF da metatag
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch('/api/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Erro ao marcar todas notificações como lidas');
            }
            
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                // Recarregar notificações
                loadUnreadNotifications();
            }
        })
        .catch(function(error) {
            console.error('Erro ao marcar todas notificações como lidas:', error);
        });
    }
    
    /**
     * Mostra um diálogo para o usuário sobre notificações
     * 
     * @param {String} title Título do diálogo
     * @param {String} message Mensagem do diálogo
     * @param {String} type Tipo do diálogo (success, info, warning, error)
     */
    function showNotificationDialog(title, message, type = 'info') {
        // Verificar se temos a função de alerta (depende da UI utilizada)
        if (typeof showAlert === 'function') {
            showAlert(title, message, type);
        } else {
            alert(`${title}\n\n${message}`);
        }
    }
    
    /**
     * Converte string base64 URL-safe para Uint8Array
     * (Necessário para a chave VAPID)
     * 
     * @param {String} base64String String base64 URL-safe
     * @return {Uint8Array} Array convertido
     */
    function urlB64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
          .replace(/\-/g, '+')
          .replace(/_/g, '/');
      
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
      
        for (let i = 0; i < rawData.length; ++i) {
          outputArray[i] = rawData.charCodeAt(i);
        }
        
        return outputArray;
    }
    
    // Inicializar quando o DOM estiver carregado
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeNotifications);
    } else {
        initializeNotifications();
    }
    
})();