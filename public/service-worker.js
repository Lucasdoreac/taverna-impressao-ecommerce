/**
 * Service Worker para Notificações Push
 * 
 * Este service worker processa notificações push e gerencia eventos
 * de ciclo de vida do service worker.
 * 
 * @author Taverna da Impressão
 * @version 1.0.0
 */

'use strict';

// Versão do cache para controle de atualizações
const CACHE_VERSION = 'v1';
const CACHE_NAME = `taverna-impressao-${CACHE_VERSION}`;

// Assets para cache offline (opcional)
const CACHE_ASSETS = [
  '/',
  '/offline',
  '/css/styles.css',
  '/js/app.js',
  '/images/logo.png',
  '/images/notification-icon.png',
  '/images/offline.png'
];

// Evento de instalação
self.addEventListener('install', event => {
  console.log('[Service Worker] Instalando Service Worker...');
  
  // Fazer pré-cache dos assets principais
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('[Service Worker] Pré-cacheando arquivos essenciais');
      return cache.addAll(CACHE_ASSETS);
    })
  );
  
  // Ativar imediatamente, sem esperar por refresh
  self.skipWaiting();
});

// Evento de ativação
self.addEventListener('activate', event => {
  console.log('[Service Worker] Ativando Service Worker...');
  
  // Limpar caches antigos
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('[Service Worker] Removendo cache antigo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  
  // Garantir que o service worker assuma o controle imediatamente
  return self.clients.claim();
});

// Evento push (notificações)
self.addEventListener('push', event => {
  console.log('[Service Worker] Notificação push recebida');
  
  // Se não houver dados, usar um padrão
  let notificationData = {
    title: 'Taverna da Impressão 3D',
    body: 'Você tem uma nova notificação',
    icon: '/images/notification-icon.png',
    badge: '/images/badge.png',
    data: {
      url: '/'
    }
  };
  
  // Tentar extrair dados da notificação recebida
  try {
    if (event.data) {
      notificationData = event.data.json();
    }
  } catch (e) {
    console.error('[Service Worker] Erro ao processar dados da notificação:', e);
  }
  
  // Garantir que a notificação tenha propriedades mínimas
  const title = notificationData.title || 'Taverna da Impressão 3D';
  const options = {
    body: notificationData.body || 'Você tem uma nova notificação',
    icon: notificationData.icon || '/images/notification-icon.png',
    badge: notificationData.badge || '/images/badge.png',
    tag: notificationData.tag || 'default',
    data: notificationData.data || { url: '/' },
    renotify: notificationData.renotify || false,
    requireInteraction: notificationData.requireInteraction || false,
    actions: notificationData.actions || []
  };
  
  // Registrar o evento - precisa completar antes do service worker parar
  const promiseChain = self.registration.showNotification(title, options);
  event.waitUntil(promiseChain);
});

// Evento de clique na notificação
self.addEventListener('notificationclick', event => {
  console.log('[Service Worker] Clique em notificação', event);
  
  // Fechar a notificação
  event.notification.close();
  
  // Extrair URL de destino dos dados da notificação
  let url = '/';
  if (event.notification.data && event.notification.data.url) {
    url = event.notification.data.url;
  }
  
  // Quando clica em uma ação específica da notificação
  if (event.action) {
    // Ações personalizadas podem ser tratadas aqui
    switch (event.action) {
      case 'view-details':
        // URL já está configurada
        break;
      case 'mark-read':
        // Adicionar parâmetro para marcar como lida automaticamente
        url += (url.includes('?') ? '&' : '?') + 'mark_read=1';
        break;
      default:
        // Ação desconhecida - usar URL padrão
        break;
    }
  }
  
  // Abrir uma janela existente ou criar uma nova com a URL
  const promiseChain = self.clients.matchAll({
    type: 'window',
    includeUncontrolled: true
  })
  .then(clientList => {
    // Verificar se já temos uma janela aberta para focar
    for (let client of clientList) {
      // Verificar se a URL já está aberta com tolerância para variações de protocolo e parâmetros
      const clientUrl = new URL(client.url);
      const targetUrl = new URL(url, self.location.origin);
      
      if (clientUrl.pathname === targetUrl.pathname) {
        // Já temos uma janela compatível, focar nela
        return client.focus();
      }
    }
    
    // Se não encontramos uma janela compatível, abrir uma nova
    return self.clients.openWindow(url);
  });
  
  event.waitUntil(promiseChain);
});

// Evento de fechamento da notificação
self.addEventListener('notificationclose', event => {
  console.log('[Service Worker] Notificação fechada', event);
  
  // Aqui poderíamos registrar métricas ou eventos de fechamento
  // mas não precisamos fazer nada especial por enquanto
});

// Interceptar requisições (para funcionalidade offline)
self.addEventListener('fetch', event => {
  // Responder com o recurso em cache ou buscar da rede
  event.respondWith(
    caches.match(event.request).then(response => {
      // Retornar do cache se disponível
      if (response) {
        return response;
      }
      
      // Caso contrário, buscar da rede
      return fetch(event.request)
        .then(networkResponse => {
          // Se for uma requisição que queremos cachear (ex: imagens estáticas)
          if (event.request.method === 'GET' && 
              (event.request.url.includes('/images/') || 
               event.request.url.includes('/css/') || 
               event.request.url.includes('/js/'))) {
            
            // Precisamos clonar a resposta para armazenar no cache
            const clonedResponse = networkResponse.clone();
            
            caches.open(CACHE_NAME).then(cache => {
              cache.put(event.request, clonedResponse);
            });
          }
          
          return networkResponse;
        })
        .catch(error => {
          // Em caso de falha na rede, tentar página offline
          if (event.request.mode === 'navigate') {
            return caches.match('/offline');
          }
          
          // Retornar erro para outras requisições
          console.error('[Service Worker] Erro de fetch:', error);
          throw error;
        });
    })
  );
});