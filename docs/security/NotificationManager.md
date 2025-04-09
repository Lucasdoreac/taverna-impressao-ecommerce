# Documentação de Segurança: NotificationManager

## Função
O `NotificationManager` é um componente central responsável pelo gerenciamento e entrega de notificações para usuários do sistema, incluindo alertas de performance. Ele implementa mecanismos robustos para garantir que todas as notificações sejam processadas de forma segura, validando entradas, sanitizando conteúdo e aplicando controle de acesso.

## Implementação

### Padrões de Segurança Aplicados

1. **Validação de Entrada**
```php
use InputValidationTrait;

// Exemplo de validação
$title = $this->validateString($title, ['maxLength' => 255, 'required' => true]);
$message = $this->validateString($message, ['required' => true]);
```

2. **Proteção contra SQL Injection**
```php
// Uso consistente de prepared statements
$sql = "SELECT id FROM notifications WHERE id = :id AND user_id = :user_id";
$params = [':id' => $notificationId, ':user_id' => $userId];
$result = $this->db->fetchSingle($sql, $params);
```

3. **Sanitização de Saída**
```php
// Em e-mails
$emailBody .= "<h2>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</h2>";
$emailBody .= "<p>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>";
```

4. **Proteção de Dados Sensíveis**
```php
// Remoção de dados sensíveis antes do armazenamento
unset($context['password']);
unset($context['token']);
unset($context['csrf']);
```

5. **Prevenção de Vazamento de Informações**
```php
// Log de erros sem exposição ao usuário
try {
    // Operações críticas
} catch (Exception $e) {
    error_log('Erro ao criar notificação: ' . $e->getMessage());
    return false; // Retorno genérico ao usuário
}
```

6. **Verificação de Propriedade**
```php
// Verificar se a notificação pertence ao usuário antes de permitir operações
$sql = "SELECT id FROM notifications WHERE id = :id AND user_id = :user_id";
$params = [':id' => $notificationId, ':user_id' => $userId];
$result = $this->db->fetchSingle($sql, $params);

if (!$result) {
    // Notificação não encontrada ou não pertence ao usuário
    return false;
}
```

7. **Validação de Tipos e Limites**
```php
// Validação de parâmetros numéricos
$limit = max(1, min(100, (int)$limit)); // Limitar entre 1 e 100
$offset = max(0, (int)$offset);
```

## Uso Correto

### Criação de Notificação Padrão
```php
$notificationManager = NotificationManager::getInstance();
$notificationId = $notificationManager->createNotification(
    $userId,
    "Título da notificação",
    "Mensagem detalhada para o usuário",
    "info", // Tipo: info, warning, success, error, performance
    ['contextKey' => 'contextValue'], // Dados adicionais de contexto
    ['database', 'push'] // Canais de entrega
);
```

### Criação de Alerta de Performance
```php
$notificationManager = NotificationManager::getInstance();
$success = $notificationManager->createPerformanceAlert(
    'response_time', // Nome da métrica
    1.75,            // Valor atual
    'ReportModule',  // Componente monitorado
    ['admin']        // Roles que devem receber o alerta
);
```

### Registro de Métricas de Performance
```php
$notificationManager = NotificationManager::getInstance();
$success = $notificationManager->recordPerformanceMetrics(
    'AdminReportController',
    [
        'response_time' => 1.25,
        'memory_usage' => 64.5,
        'query_time' => 0.35
    ]
);
```

### Controle de Notificações por Usuário
```php
$notificationManager = NotificationManager::getInstance();

// Obter notificações não lidas
$notifications = $notificationManager->getUserNotifications($userId, 'unread', 10, 0);

// Marcar como lida
$notificationManager->markAsRead($notificationId, $userId);

// Marcar todas como lidas
$notificationManager->markAllAsRead($userId);
```

## Vulnerabilidades Mitigadas

1. **SQL Injection**
   - Uso consistente de prepared statements em todas as consultas
   - Validação e tipagem de parâmetros antes de uso em consultas
   - Abstração do acesso ao banco de dados através de métodos seguros

2. **XSS (Cross-Site Scripting)**
   - Sanitização de todas as saídas com `htmlspecialchars()`
   - Validação rigorosa de entradas antes do processamento
   - Sanitização especial para conteúdo enviado por e-mail

3. **Escalação de Privilégios**
   - Verificação de propriedade antes de permitir operações em notificações
   - Validação de permissões de usuário para administrar alertas de performance
   - Separação lógica entre notificações regulares e alertas de sistema

4. **Vazamento de Informações**
   - Limpeza de dados sensíveis antes do armazenamento
   - Mensagens de erro genéricas para o usuário
   - Logging detalhado para depuração apenas no backend

5. **Ataques de Negação de Serviço**
   - Limitação de tamanho para contexto JSON
   - Paginação em consultas de notificações
   - Limpeza automática de notificações antigas

## Testes de Segurança

1. **Teste de Injeção SQL**: Verificada resistência a ataques de injeção SQL, inserindo caracteres especiais e comandos SQL em parâmetros como títulos e mensagens.
   - Resultado: Nenhuma vulnerabilidade detectada, todas as entradas são adequadamente tratadas pelos prepared statements.

2. **Teste de XSS**: Inserção de scripts maliciosos em mensagens de notificação.
   - Resultado: Todo conteúdo é sanitizado antes da exibição, evitando execução de scripts.

3. **Teste de Escalação de Privilégios**: Tentativa de acessar ou modificar notificações de outros usuários.
   - Resultado: O sistema verifica corretamente a propriedade antes de permitir operações.

4. **Teste de Sobrecarga de Sistema**: Geração de grande volume de notificações para testar limites.
   - Resultado: O sistema implementa limites adequados e paginação para evitar sobrecarga.

5. **Teste de Sanitização de E-mail**: Verificação da correta sanitização de conteúdo enviado por e-mail.
   - Resultado: Todo conteúdo é adequadamente sanitizado para prevenir ataques via e-mail.

6. **Teste de Armazenamento de Contexto**: Tentativa de armazenar dados maliciosos ou excessivamente grandes no contexto.
   - Resultado: Dados sensíveis são filtrados e o tamanho é limitado para evitar abusos.