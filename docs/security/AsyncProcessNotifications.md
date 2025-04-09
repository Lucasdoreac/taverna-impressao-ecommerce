# Documentação de Segurança: Sistema de Notificações para Processos Assíncronos

## Função

O Sistema de Notificações para Processos Assíncronos implementa a integração segura entre o processamento assíncrono e o sistema de notificações, permitindo alertas em tempo real para usuários sobre o progresso, conclusão, falhas e resultados de processos de longa duração.

## Implementação

### Camadas de Proteção

1. **Autenticação e Autorização**
   - Validação robusta de propriedade dos processos
   - Verificação de permissões de acesso por usuário
   - Validação da API Key para comunicações entre serviços

2. **Validação de Entrada**
   - Validação rigorosa de todos os parâmetros via InputValidationTrait
   - Verificação de formatos e padrões específicos (ex: token de processo)
   - Sanitização de dados de contexto antes do processamento

3. **Proteção CSRF**
   - Validação obrigatória de tokens CSRF para endpoints acessíveis via navegador
   - Implementação time-safe de comparações via hash_equals()
   - Verificação de cabeçalhos X-CSRF-Token e campos de formulário

4. **Rate Limiting**
   - Limitação de requisições por cliente/endpoint
   - Proteção contra abusos e ataques de negação de serviço
   - Escalonamento de limites por tipo de operação

5. **Sanitização de Saída**
   - Remoção de dados sensíveis antes da saída
   - Codificação HTML via htmlspecialchars() para prevenir XSS
   - Filtragem de informações internas em contextos de notificação

6. **Controle de Canais**
   - Seleção segura de canais de notificação com base no tipo/prioridade
   - Validação de preferências de usuário para entrega
   - Entrega multi-canal para notificações críticas (database, push, email)

## Arquitetura Segura

### Component: AsyncProcessNotificationHandler

Classe principal responsável pela lógica de negócios das notificações, implementando:

- **Validação de Parâmetros**: Implementada através do trait InputValidationTrait
- **Verificação de Propriedade**: Validação de que o usuário é dono ou tem acesso ao processo
- **Proteção de Contexto**: Sanitização e validação de dados de contexto
- **Geração de URLs Seguras**: Criação de URLs baseadas no tipo e permissões do processo

### Component: AsyncNotificationsController

Controlador de API para endpoints expostos, implementando:

- **Rate Limiting**: Integração com RateLimiter para proteção contra abusos
- **Validação de API Key**: Verificação de chave para comunicações entre serviços
- **Validação de CSRF**: Proteção contra Cross-Site Request Forgery
- **Sanitização de Saída**: Prevenção contra XSS em respostas JSON

## Uso Correto

### Enviando Notificações de Mudança de Status

```php
// Exemplo para workers e jobs em background
$apiKey = getenv('ASYNC_NOTIFICATIONS_API_KEY');

$data = [
    'process_token' => $processToken,
    'old_status' => $oldStatus,
    'new_status' => $newStatus,
    'user_id' => $userId,
    'context' => [
        'completion_percentage' => $percent,
        'estimated_time_remaining' => $timeRemaining,
        'error_message' => $errorMessage // apenas para status 'failed'
    ]
];

$ch = curl_init(BASE_URL . '/api/async-notifications/status-change');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $apiKey
]);

$response = curl_exec($ch);
curl_close($ch);
```

### Exemplo para Uso Interno (PHP)

```php
// Em um controlador ou job interno
$notificationHandler = new AsyncProcessNotificationHandler(
    NotificationManager::getInstance(),
    new StatusRepository($pdo),
    $pdo
);

$notificationHandler->handleStatusChange(
    $processToken,
    'processing',
    'completed',
    $userId,
    [
        'completion_percentage' => 100,
        'processing_time' => $elapsedSeconds
    ]
);
```

### Exemplo para Requisição JavaScript (Cliente)

```javascript
// Cliente JavaScript com proteção CSRF
async function markNotificationAsRead(notificationId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    try {
        const response = await fetch('/api/async-notifications/mark-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': csrfToken
            },
            body: new URLSearchParams({
                'notification_id': notificationId,
                'csrf_token': csrfToken
            }),
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        return result.success;
        
    } catch (error) {
        console.error('Error marking notification as read:', error);
        return false;
    }
}
```

## Vulnerabilidades Mitigadas

### 1. CSRF (Cross-Site Request Forgery)
- **Mitigação**: Tokens CSRF validados em todas as requisições POST
- **Implementação**: CsrfProtection::validateToken() com comparação time-safe
- **Verificação**: Cabeçalhos X-CSRF-Token e campos de formulário

### 2. Enumeração de Processos
- **Mitigação**: Verificação rigorosa de propriedade e permissões
- **Implementação**: statusRepository->userCanAccessProcess()
- **Proteção**: Respostas genéricas quando processos não encontrados ou sem acesso

### 3. Injection via Contexto
- **Mitigação**: Validação rigorosa de todos os parâmetros e contextos
- **Implementação**: InputValidationTrait para validação tipo-específica
- **Sanitização**: Remoção de dados sensíveis e sanitização antes do armazenamento

### 4. XSS (Cross-Site Scripting)
- **Mitigação**: Sanitização de saída via htmlspecialchars()
- **Implementação**: sanitizeNotifications() para todas as respostas JSON
- **Proteção**: Codificação de entidades HTML em todos os campos de texto

### 5. Information Disclosure
- **Mitigação**: Remoção de dados sensíveis antes da resposta
- **Implementação**: Filtragem de campos como internal_log, debug_info
- **Mensagens**: Erros genéricos para cliente, logs detalhados para depuração interna

### 6. Race Conditions
- **Mitigação**: Atomicidade de operações críticas via transações
- **Implementação**: Operações de banco de dados em blocos de transação
- **Verificação**: Verificações de estado antes de atualizações

## Testes de Segurança

### 1. Teste de Autenticação/Autorização
- **Descrição**: Tentativa de acessar processos de outros usuários
- **Método**: Envio de requisições com tokens válidos mas de usuários diferentes
- **Resultado**: Sistema rejeitou acessos não autorizados com erro 403
- **Status**: APROVADO

### 2. Teste de Validação de Entrada
- **Descrição**: Tentativa de injetar dados maliciosos via parâmetros
- **Método**: Envio de caracteres especiais, scripts e valores fora de intervalo
- **Resultado**: Todas as entradas foram validadas e sanitizadas corretamente
- **Status**: APROVADO

### 3. Teste de Rate Limiting
- **Descrição**: Verificação de proteção contra abusos
- **Método**: Envio de múltiplas requisições em sequência rápida
- **Resultado**: Sistema limitou corretamente após exceder o limite
- **Status**: APROVADO

### 4. Teste de CSRF
- **Descrição**: Tentativa de forjar requisições sem token CSRF
- **Método**: Envio direto e via sites de terceiros sem token CSRF
- **Resultado**: Todas as requisições sem token válido foram rejeitadas
- **Status**: APROVADO

### 5. Teste de XSS
- **Descrição**: Tentativa de injetar scripts via contexto de notificação
- **Método**: Inclusão de payloads XSS nos campos de título e mensagem
- **Resultado**: Conteúdo sanitizado corretamente na saída
- **Status**: APROVADO

## Logs e Monitoramento

O sistema implementa logs detalhados para auditoria e depuração:

1. **Logs de Operações**
   - Registro detalhado de todas as operações de notificação
   - Timestamp, usuário, processo e canais utilizados
   - Armazenamento em tabela dedicada `async_notification_logs`

2. **Logs de Erros**
   - Detalhes de erros registrados via error_log()
   - Informações de debugging não expostas ao cliente
   - Rastreamento de stacktrace para depuração interna

3. **Métricas de Desempenho**
   - Tempo de resposta para operações de notificação
   - Taxa de sucesso/falha de entregas por canal
   - Distribuição de tipos de notificação

## Limites e Configurações de Segurança

```php
// Configurações de segurança configuráveis
$config = [
    // Rate Limiting
    'rate_limit_window' => 60, // segundos
    'rate_limit_max_requests' => 30, // requisições por janela
    
    // Canais
    'send_email_notifications' => true,
    'send_push_on_high_progress' => true,
    
    // Marcos de Progresso (evitar spam de notificações)
    'progress_milestones' => [25, 50, 75, 90], // percentuais para notificar
    'milestone_margin' => 2, // margem de erro (%)
    
    // Alertas
    'alert_admins_on_failure' => true,
    'expiration_warning_hours' => 24, // alertar sobre expiração 24h antes
    
    // Timeouts e Expiração
    'token_lifetime' => 3600 // segundos
];
```

## Considerações para Implantação

1. **Chaves de API**
   - Gerar chaves API com entropia adequada (256 bits)
   - Armazenar em variáveis de ambiente, não no código
   - Rotacionar periodicamente

2. **Canais de Notificação**
   - Implementar rate-limiting por canal para evitar spam
   - Validar e-mails antes de enviar notificações
   - Implementar fallback quando canais falham

3. **Performance**
   - Implementar jobs em background para envio de notificações
   - Usar filas para operações assíncronas intensivas
   - Monitorar tempos de resposta e ajustar configurações

4. **Depuração em Produção**
   - Garantir que logs detalhados não exponham dados sensíveis
   - Implementar monitoramento de falhas em tempo real
   - Configurar alertas para falhas críticas do sistema