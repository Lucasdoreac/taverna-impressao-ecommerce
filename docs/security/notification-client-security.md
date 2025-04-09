# Documentação de Segurança: Implementação Cliente de Notificações Push

## Função

O sistema cliente de notificações push implementa mecanismos para registrar o navegador do usuário para receber notificações em tempo real, gerenciar o ciclo de vida das notificações e interagir seguramente com a API de notificações no servidor.

## Componentes

### 1. Service Worker (service-worker.js)

Service worker responsável por processar notificações push, gerenciar o cache para funcionamento offline e interceptar requisições de rede.

#### Proteções Implementadas:
- **Validação de Origem**: Verificação implícita da origem das mensagens push
- **Sanitização de Dados**: Validação dos dados recebidos antes de criar notificações
- **Valores Padrão Seguros**: Utilização de valores padrão seguros quando dados estão ausentes
- **Controle de Cache**: Isolamento e versão explícita para recursos em cache

### 2. Cliente JavaScript (notifications.js)

Script responsável por registrar o service worker, gerenciar subscrições push e interagir com a API de notificações.

#### Proteções Implementadas:
- **Validação de Suporte**: Verificação de suporte do navegador antes de inicializar funcionalidades
- **Proteção CSRF**: Inclusão de token CSRF em todas as requisições AJAX
- **Encapsulamento de Escopo**: Uso de IIFE para isolar variáveis do escopo global
- **Validação de Resposta**: Verificação da resposta do servidor antes de processar
- **Tratamento de Erros**: Captura e registro adequado de erros nas operações

## Fluxo de Trabalho Seguro

### Registro do Service Worker

1. O script `notifications.js` verifica se o navegador suporta Service Worker e Push API.
2. Se suportado, o script registra o service worker `/service-worker.js`.
3. O service worker é instalado e pré-cacheia recursos essenciais.
4. O script verifica o status atual de subscrição.

### Subscrição de Notificações Push

1. O usuário clica no botão "Ativar notificações".
2. O script obtém a chave pública VAPID do servidor via requisição segura com CSRF token.
3. O script solicita permissão do usuário para notificações através da API do navegador.
4. Se concedida, o script registra a subscrição push junto ao navegador.
5. A subscrição é enviada ao servidor com CSRF token para armazenamento.

### Recebimento de Notificações

1. O servidor envia mensagem push para o endpoint registrado no navegador.
2. O service worker é ativado pelo navegador quando a mensagem é recebida.
3. O service worker valida e processa os dados da notificação.
4. Uma notificação é exibida com os dados sanitizados.
5. Quando clicada, o service worker gerencia a navegação para a URL apropriada.

## Mitigação de Vulnerabilidades Específicas

### XSS (Cross-Site Scripting)

- Sanitização de conteúdo antes da exibição na UI
- Uso de createElement e métodos seguros para manipulação do DOM
- Validação de dados antes de processamento

### CSRF (Cross-Site Request Forgery)

- Token CSRF incluído em todas as requisições AJAX
- Token CSRF obtido de meta tag para maior segurança
- Cabeçalho `X-CSRF-Token` em requisições para a API

### Information Disclosure

- Ocultação de informações sensíveis nas notificações
- Prevenção de leakage de URLs internas
- Tratamento adequado de erros sem exposição de detalhes técnicos

### Clickjacking

- Proteção implícita através da arquitetura do Service Worker
- Notificações do sistema operam fora do contexto da página web

### Insecure Data Storage

- Não armazenamento de dados sensíveis em localStorage/IndexedDB
- Uso do sistema de cache nativo do Service Worker com isolamento apropriado

## Considerações de Permissões

### Permissões do Navegador

- Solicitação clara de permissão para notificações
- Tratamento adequado quando permissões são negadas
- Feedback visual sobre o estado atual das permissões

### Permissões da Aplicação

- Verificação de autenticação antes de registrar subscrições
- Validação de propriedade da subscrição em operações
- Feedback adequado sobre o processo de subscrição

## Implementação Segura de Chaves VAPID

As chaves VAPID (Voluntary Application Server Identification) são utilizadas para estabelecer confiança entre servidores push e serviços push dos navegadores.

### Geração Segura de Chaves

```bash
# Exemplo de geração usando web-push (Node.js)
npx web-push generate-vapid-keys --json
```

### Armazenamento de Chaves

- Chave pública exposta apenas através de endpoint seguro
- Chave privada armazenada em variável de ambiente
- Nunca armazenar a chave privada em código-fonte ou repositório
- Validação da chave em tempo de inicialização

### Conversão Base64 URL-Safe

```javascript
// Função para converter string base64 URL-safe para Uint8Array
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
```

## Testes de Segurança Realizados

| Teste | Descrição | Resultado |
|-------|-----------|-----------|
| CSRF Protection | Tentativa de enviar requisições sem token CSRF | PASSOU - Requisições rejeitadas |
| XSS em Notificações | Injeção de payload malicioso em notificações | PASSOU - Conteúdo sanitizado |
| Permissões Negadas | Comportamento quando usuário nega permissões | PASSOU - Feedback adequado e funcionalidade desabilitada |
| Dados Inválidos | Envio de dados inválidos para subscrição | PASSOU - Validação adequada |
| Integridade de Service Worker | Modificação do Service Worker | PASSOU - Verificação de integridade pelo navegador |

## Uso Correto do Cliente JavaScript

### Inicialização

O script `notifications.js` deve ser incluído em todas as páginas onde notificações são necessárias:

```html
<!-- Incluir meta tag CSRF -->
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">

<!-- Incluir script no final do body -->
<script src="/js/notifications.js"></script>
```

### Configuração de Elementos UI

O script espera encontrar os seguintes elementos no DOM (opcional):

```html
<!-- Botão para assinar/cancelar notificações -->
<button id="notification-button" class="btn btn-primary">
    Ativar notificações
</button>

<!-- Contador de notificações não lidas -->
<span id="notification-count" class="badge badge-danger d-none">0</span>

<!-- Dropdown de notificações -->
<div id="notification-dropdown" class="dropdown-menu"></div>
```

### Personalização

Para personalizar o comportamento, modifique as configurações no início do script:

```javascript
// Em notifications.js
const CONFIG = {
    refreshInterval: 60000, // Intervalo de verificação de novas notificações (ms)
    vapidPublicKeyEndpoint: '/api/notifications/vapid-key',
    subscribeEndpoint: '/api/notifications/subscribe',
    unsubscribeEndpoint: '/api/notifications/unsubscribe',
    getNotificationsEndpoint: '/api/notifications/unread',
    markAsReadEndpoint: '/api/notifications/mark-read',
    markAllAsReadEndpoint: '/api/notifications/mark-all-read'
};
```

## Uso Correto do Service Worker

O service worker não deve ser modificado pelo desenvolvedor final. Caso sejam necessárias customizações, elas devem ser realizadas seguindo estas diretrizes:

1. **Manter Versão**: Atualizar CACHE_VERSION ao modificar o service worker
2. **Validar Assets**: Verificar integridade dos CACHE_ASSETS
3. **Sanitizar Dados**: Manter as rotinas de sanitização de dados
4. **Testar Exaustivamente**: Verificar comportamento em diversos navegadores

## Vulnerabilidades Mitigadas

- **XSS em Notificações**: Sanitização rigorosa do conteúdo
- **CSRF em Operações AJAX**: Implementação de token CSRF
- **Injection via Push Payload**: Validação de dados recebidos
- **Information Disclosure**: Controle rigoroso de logs e erros
- **Service Worker Hijacking**: Proteções nativas do navegador
- **Navegação Insegura**: Validação de URLs de destino

## Limitações Conhecidas

1. **Suporte de Navegadores**:
   - Safari tem suporte limitado à Push API
   - Implementações variam entre navegadores

2. **Permissões**:
   - O usuário pode revogar permissões externamente às notificações
   - Algumas plataformas móveis têm comportamento inconsistente

3. **Confiabilidade**:
   - A entrega de notificações push não é garantida
   - Sistemas operacionais podem limitar notificações

## Referências

1. [MDN Web Push API](https://developer.mozilla.org/en-US/docs/Web/API/Push_API)
2. [MDN Service Worker API](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)
3. [OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
4. [Web Push Protocol RFC8030](https://datatracker.ietf.org/doc/html/rfc8030)
5. [Application Server Keys (VAPID) RFC8292](https://datatracker.ietf.org/doc/html/rfc8292)