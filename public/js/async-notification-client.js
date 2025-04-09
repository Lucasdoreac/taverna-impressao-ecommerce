/**
 * Async Notification Client
 * 
 * Cliente JavaScript para interagir com o sistema de notificações assíncronas
 * da Taverna da Impressão 3D. Implementa verificação de novas notificações,
 * atualização da interface e marcação de leitura.
 * 
 * Segue os guardrails de segurança estabelecidos para proteção contra XSS e CSRF.
 */
(function() {
    'use strict';
    
    // Configurações do cliente
    const CONFIG = {
        // Endpoints da API
        apiEndpoints: {
            getUserNotifications: '/api/async-notifications/user-notifications',
            markAsRead: '/api/async-notifications/mark-read',
            markAllAsRead: '/api/async-notifications/mark-all-read'
        },
        
        // Configurações de atualização
        refreshInterval: 30000,  // 30 segundos
        maxRetries: A3,
        retryDelay: 5000,        // 5 segundos
        
        // Seletores de elementos na página
        selectors: {
            notificationButton: '#notification-button',
            notificationCount: '#notification-count',
            notificationDropdown: '#notification-dropdown',
            notificationTemplate: '#notification-template',
            notificationList: '#notification-list',
            markAllAsReadButton: '#mark-all-read',
            processTokenFilter: '[data-process-token]'
        },
        
        // Classes CSS
        cssClasses: {
            unread: 'notification-unread',
            read: 'notification-read',
            high: 'notification-high',
            medium: 'notification-medium',
            low: 'notification-low',
            loading: 'loading'
        }
    };
    
    // Estado da aplicação
    let state = {
        notifications: [],
        unreadCount: 0,
        lastUpdate: null,
        csrfToken: '',
        processToken: null,  // Opcional: filtrar por processo específico
        isActive: false,     // Se o cliente está ativo
        isPolling: false,    // Se está verificando notificações
        pollingTimer: null,  // Timer de polling
        retryCount: 0        // Contador de tentativas de reconexão
    };
    
    /**
     * Inicializa o cliente de notificações
     * @public
     */
    function init() {
        // Obter token CSRF de meta tag (mais seguro que cookies)
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            state.csrfToken = metaToken.getAttribute('content');
        } else {
            console.error('CSRF token não encontrado. Notificações desativadas.');
            return;
        }
        
        // Verificar se existe filtro de processo na página
        const processTokenEl = document.querySelector(CONFIG.selectors.processTokenFilter);
        if (processTokenEl) {
            state.processToken = processTokenEl.dataset.processToken;
        }
        
        // Inicializar elementos da UI
        initializeUI();
        
        // Ativar cliente
        activateClient();
    }
    
    /**
     * Inicializa elementos da interface
     * @private
     */
    function initializeUI() {
        // Inicializar dropdown de notificações (se existir)
        const dropdown = document.querySelector(CONFIG.selectors.notificationDropdown);
        if (dropdown) {
            // Adicionar evento para marcar como lido ao clicar em notificação
            dropdown.addEventListener('click', handleNotificationClick);
            
            // Adicionar evento para marcar todas como lidas
            const markAllBtn = document.querySelector(CONFIG.selectors.markAllAsReadButton);
            if (markAllBtn) {
                markAllBtn.addEventListener('click', handleMarkAllAsRead);
            }
        }
        
        // Inicializar estado do contador (se existir)
        updateUnreadCount(0);
    }
    
    /**
     * Ativa o cliente de notificações
     * @private
     */
    function activateClient() {
        state.isActive = true;
        
        // Primeira verificação imediata
        fetchNotifications();
        
        // Iniciar polling
        startPolling();
    }
    
    /**
     * Inicia verificação periódica de notificações
     * @private
     */
    function startPolling() {
        if (state.pollingTimer !== null) {
            clearInterval(state.pollingTimer);
        }
        
        state.pollingTimer = setInterval(() => {
            if (!state.isPolling && state.isActive) {
                fetchNotifications();
            }
        }, CONFIG.refreshInterval);
    }
    
    /**
     * Busca notificações do servidor
     * @private
     */
    function fetchNotifications() {
        if (state.isPolling) {
            return;
        }
        
        state.isPolling = true;
        
        // Construir URL com filtro de processo se existir
        let url = CONFIG.apiEndpoints.getUserNotifications;
        if (state.processToken) {
            url += `?process_token=${encodeURIComponent(state.processToken)}`;
        }
        
        // Buscar notificações
        fetch(url, {
            method: 'GET',
            headers: {
                'X-CSRF-Token': state.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro na requisição: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                handleNewNotifications(data.notifications);
                state.lastUpdate = new Date();
                state.retryCount = 0;
            }
        })
        .catch(error => {
            console.error('Erro ao buscar notificações:', error);
            // Incrementar contador de tentativas
            state.retryCount++;
            
            // Se excedeu o limite, parar de tentar
            if (state.retryCount > CONFIG.maxRetries) {
                state.isActive = false;
                clearInterval(state.pollingTimer);
                console.error('Cliente de notificações desativado após múltiplas falhas.');
            }
        })
        .finally(() => {
            state.isPolling = false;
        });
    }
    
    /**
     * Processa novas notificações
     * @private
     * @param {Array} notifications Array de notificações
     */
    function handleNewNotifications(notifications) {
        // Verificar se temos notificações novas
        if (!Array.isArray(notifications)) {
            return;
        }
        
        // Atualizar estado
        state.notifications = notifications;
        
        // Contar notificações não lidas
        const unreadCount = notifications.filter(n => n.status === 'unread').length;
        updateUnreadCount(unreadCount);
        
        // Atualizar UI
        renderNotifications(notifications);
    }
    
    /**
     * Atualiza contador de notificações não lidas
     * @private
     * @param {number} count Número de notificações não lidas
     */
    function updateUnreadCount(count) {
        state.unreadCount = count;
        
        // Atualizar badge de contagem
        const countEl = document.querySelector(CONFIG.selectors.notificationCount);
        if (countEl) {
            if (count > 0) {
                countEl.textContent = count > 99 ? '99+' : count;
                countEl.classList.remove('d-none');
            } else {
                countEl.textContent = '0';
                countEl.classList.add('d-none');
            }
        }
    }
    
    /**
     * Renderiza notificações na interface
     * @private
     * @param {Array} notifications Array de notificações
     */
    function renderNotifications(notifications) {
        const listEl = document.querySelector(CONFIG.selectors.notificationList);
        if (!listEl) {
            return;
        }
        
        // Limpar lista
        listEl.innerHTML = '';
        
        // Se não tiver notificações, mostrar mensagem
        if (notifications.length === 0) {
            const emptyEl = document.createElement('div');
            emptyEl.className = 'notification-empty';
            emptyEl.textContent = 'Nenhuma notificação';
            listEl.appendChild(emptyEl);
            return;
        }
        
        // Renderizar cada notificação
        notifications.forEach(notification => {
            const notificationEl = createNotificationElement(notification);
            listEl.appendChild(notificationEl);
        });
    }
    
    /**
     * Cria elemento HTML para uma notificação
     * @private
     * @param {Object} notification Dados da notificação
     * @return {HTMLElement} Elemento da notificação
     */
    function createNotificationElement(notification) {
        // Sanitizar dados da notificação
        const safeNotification = {
            id: parseInt(notification.id, 10),
            title: sanitizeHTML(notification.title),
            message: sanitizeHTML(notification.message),
            created_at: sanitizeHTML(notification.created_at),
            status: notification.status === 'unread' ? 'unread' : 'read',
            priority: ['high', 'medium', 'low'].includes(notification.priority) ? notification.priority : 'medium',
            url: notification.url ? sanitizeURL(notification.url) : '#'
        };
        
        // Usar template se disponível
        const templateEl = document.querySelector(CONFIG.selectors.notificationTemplate);
        if (templateEl) {
            const template = templateEl.innerHTML;
            const html = template
                .replace(/\{id\}/g, safeNotification.id)
                .replace(/\{title\}/g, safeNotification.title)
                .replace(/\{message\}/g, safeNotification.message)
                .replace(/\{created_at\}/g, safeNotification.created_at)
                .replace(/\{status\}/g, safeNotification.status)
                .replace(/\{priority\}/g, safeNotification.priority)
                .replace(/\{url\}/g, safeNotification.url);
            
            const tempContainer = document.createElement('div');
            tempContainer.innerHTML = html.trim();
            return tempContainer.firstChild;
        }
        
        // Criação manual do elemento (fallback)
        const notificationEl = document.createElement('div');
        notificationEl.className = `notification ${CONFIG.cssClasses[safeNotification.status]} ${CONFIG.cssClasses[safeNotification.priority]}`;
        notificationEl.dataset.id = safeNotification.id;
        
        const titleEl = document.createElement('div');
        titleEl.className = 'notification-title';
        titleEl.textContent = safeNotification.title;
        
        const messageEl = document.createElement('div');
        messageEl.className = 'notification-message';
        messageEl.textContent = safeNotification.message;
        
        const timeEl = document.createElement('div');
        timeEl.className = 'notification-time';
        timeEl.textContent = formatDate(safeNotification.created_at);
        
        const linkEl = document.createElement('a');
        linkEl.href = safeNotification.url;
        linkEl.className = 'notification-link';
        linkEl.appendChild(titleEl);
        linkEl.appendChild(messageEl);
        
        notificationEl.appendChild(linkEl);
        notificationEl.appendChild(timeEl);
        
        return notificationEl;
    }
    
    /**
     * Manipula clique em uma notificação
     * @private
     * @param {Event} event Evento de clique
     */
    function handleNotificationClick(event) {
        // Encontrar elemento de notificação
        const notificationEl = findParentWithClass(event.target, 'notification');
        if (!notificationEl) {
            return;
        }
        
        // Obter ID da notificação
        const notificationId = parseInt(notificationEl.dataset.id, 10);
        if (isNaN(notificationId)) {
            return;
        }
        
        // Verificar se já está lida
        if (notificationEl.classList.contains(CONFIG.cssClasses.read)) {
            return;
        }
        
        // Marcar como lida
        markAsRead(notificationId)
            .then(success => {
                if (success) {
                    // Atualizar UI
                    notificationEl.classList.remove(CONFIG.cssClasses.unread);
                    notificationEl.classList.add(CONFIG.cssClasses.read);
                    
                    // Atualizar contador
                    updateUnreadCount(state.unreadCount - 1);
                }
            });
    }
    
    /**
     * Manipula clique no botão "Marcar todas como lidas"
     * @private
     * @param {Event} event Evento de clique
     */
    function handleMarkAllAsRead(event) {
        event.preventDefault();
        
        // Adicionar indicador de carregamento
        const button = event.target;
        button.classList.add(CONFIG.cssClasses.loading);
        
        // Marcar todas como lidas
        markAllAsRead()
            .then(success => {
                if (success) {
                    // Atualizar UI
                    const notifications = document.querySelectorAll(`.${CONFIG.cssClasses.unread}`);
                    notifications.forEach(el => {
                        el.classList.remove(CONFIG.cssClasses.unread);
                        el.classList.add(CONFIG.cssClasses.read);
                    });
                    
                    // Atualizar contador
                    updateUnreadCount(0);
                }
            })
            .finally(() => {
                // Remover indicador de carregamento
                button.classList.remove(CONFIG.cssClasses.loading);
            });
    }
    
    /**
     * Marca uma notificação como lida
     * @private
     * @param {number} notificationId ID da notificação
     * @return {Promise<boolean>} Promise que resolve para true em caso de sucesso
     */
    function markAsRead(notificationId) {
        const formData = new FormData();
        formData.append('notification_id', notificationId);
        formData.append('csrf_token', state.csrfToken);
        
        return fetch(CONFIG.apiEndpoints.markAsRead, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': state.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro na requisição: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            return data && data.success;
        })
        .catch(error => {
            console.error('Erro ao marcar notificação como lida:', error);
            return false;
        });
    }
    
    /**
     * Marca todas as notificações como lidas
     * @private
     * @return {Promise<boolean>} Promise que resolve para true em caso de sucesso
     */
    function markAllAsRead() {
        const formData = new FormData();
        formData.append('csrf_token', state.csrfToken);
        
        // Adicionar filtro de processo se existir
        if (state.processToken) {
            formData.append('process_token', state.processToken);
        }
        
        return fetch(CONFIG.apiEndpoints.markAllAsRead, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': state.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro na requisição: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            return data && data.success;
        })
        .catch(error => {
            console.error('Erro ao marcar todas notificações como lidas:', error);
            return false;
        });
    }
    
    /**
     * Sanitiza string HTML para prevenir XSS
     * @private
     * @param {string} html HTML a ser sanitizado
     * @return {string} HTML sanitizado
     */
    function sanitizeHTML(html) {
        if (typeof html !== 'string') {
            return '';
        }
        
        const tempEl = document.createElement('div');
        tempEl.textContent = html;
        return tempEl.innerHTML;
    }
    
    /**
     * Sanitiza URL para prevenir javascript: e outros protocolos perigosos
     * @private
     * @param {string} url URL a ser sanitizada
     * @return {string} URL sanitizada
     */
    function sanitizeURL(url) {
        if (typeof url !== 'string') {
            return '#';
        }
        
        try {
            // Verificar se é uma URL relativa ou absoluta válida
            if (url.startsWith('/')) {
                return url; // URL relativa é segura
            }
            
            const urlObj = new URL(url, window.location.origin);
            
            // Verificar se é uma URL do mesmo domínio
            if (urlObj.origin === window.location.origin) {
                return url;
            }
            
            // Se for externa, validar protocolo
            if (urlObj.protocol === 'http:' || urlObj.protocol === 'https:') {
                return url;
            }
            
            // Protocolo não seguro
            return '#';
        } catch (e) {
            // URL inválida
            return '#';
        }
    }
    
    /**
     * Formata data para exibição
     * @private
     * @param {string} dateStr String de data
     * @return {string} Data formatada
     */
    function formatDate(dateStr) {
        try {
            const date = new Date(dateStr);
            
            // Verificar se é hoje
            const today = new Date();
            if (date.toDateString() === today.toDateString()) {
                return `Hoje, ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
            }
            
            // Verificar se é ontem
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            if (date.toDateString() === yesterday.toDateString()) {
                return `Ontem, ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
            }
            
            // Formato padrão para outras datas
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            return dateStr;
        }
    }
    
    /**
     * Encontra o elemento pai com a classe especificada
     * @private
     * @param {HTMLElement} element Elemento inicial
     * @param {string} className Nome da classe
     * @return {HTMLElement|null} Elemento pai ou null
     */
    function findParentWithClass(element, className) {
        let current = element;
        while (current && !current.classList.contains(className)) {
            current = current.parentElement;
        }
        return current;
    }
    
    // Inicializar após carregamento do DOM
    document.addEventListener('DOMContentLoaded', init);
    
    // API pública
    window.AsyncNotificationClient = {
        refresh: fetchNotifications,
        markAsRead: markAsRead,
        markAllAsRead: markAllAsRead
    };
})();
