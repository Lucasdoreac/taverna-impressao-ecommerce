# Documentação de Segurança: Sistema de Fila de Impressão 3D

## Visão Geral

O Sistema de Fila de Impressão 3D gerencia o ciclo de vida completo dos trabalhos de impressão, desde a submissão até a conclusão ou falha. Este documento detalha as medidas de segurança implementadas para proteger a integridade dos dados, prevenir ataques e garantir o controle adequado de acesso.

## Componentes de Segurança

### 1. Validação de Entrada

Todos os controladores do sistema utilizam o `InputValidationTrait` para garantir validação rigorosa de parâmetros de entrada:

```php
class PrintQueueController extends Controller {
    use InputValidationTrait;
    
    // Exemplo de validação com parâmetros específicos
    public function addToQueue() {
        $modelId = $this->postValidatedParam('model_id', 'integer', ['required' => true]);
        $priority = $this->postValidatedParam('priority', 'integer', [
            'required' => false,
            'default' => 5,
            'min' => 1,
            'max' => 10
        ]);
    }
}
```

### 2. Proteção CSRF

Todos os formulários POST no sistema implementam tokens CSRF para prevenir ataques Cross-Site Request Forgery:

```php
// Geração de token no formulário
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

// Validação do token no controlador
$csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
if (!CsrfProtection::validateToken($csrfToken)) {
    $this->setFlashMessage('error', 'Token de segurança inválido. Por favor, tente novamente.');
    $this->redirect('/print-queue');
    return;
}
```

### 3. Controle de Acesso

O sistema implementa verificações de autenticação e autorização em múltiplos níveis:

```php
// Verificação de autenticação geral
if (!SecurityManager::checkAuthentication()) {
    $this->setFlashMessage('error', 'Você precisa estar logado para acessar esta página.');
    $this->redirect('/login');
    return;
}

// Verificação de permissão específica
if (!$this->isAdmin()) {
    $this->setFlashMessage('error', 'Você não tem permissão para acessar esta funcionalidade.');
    $this->redirect('/');
    return;
}

// Verificação de propriedade do recurso
if ($queueItem['user_id'] != $_SESSION['user_id'] && !$this->isAdmin()) {
    $this->setFlashMessage('error', 'Você não tem permissão para visualizar este item.');
    $this->redirect('/');
    return;
}
```

### 4. Sanitização de Saída

Todas as saídas são rigorosamente sanitizadas para prevenir ataques XSS:

```php
<?= htmlspecialchars($variable, ENT_QUOTES, 'UTF-8') ?>
```

### 5. Validação de Transições de Estado

O sistema implementa validação rigorosa de transições de estado para garantir integridade do fluxo de trabalho:

```php
private function isValidStatusTransition($currentStatus, $newStatus) {
    // Definir transições válidas
    $validTransitions = [
        'pending' => ['assigned', 'cancelled'],
        'assigned' => ['printing', 'pending', 'cancelled'],
        'printing' => ['completed', 'failed', 'cancelled'],
        'completed' => [], // Status final, não permite transição
        'failed' => ['pending'], // Permitir reenvio para a fila
        'cancelled' => ['pending'] // Permitir reativação
    ];
    
    // Verificar se a transição é válida
    if (isset($validTransitions[$currentStatus]) && in_array($newStatus, $validTransitions[$currentStatus])) {
        return true;
    }
    
    // Verificar se é o mesmo status (não é uma transição)
    if ($currentStatus === $newStatus) {
        return true;
    }
    
    return false;
}
```

### 6. Prepared Statements

Todas as consultas SQL utilizam prepared statements para prevenir injeção SQL:

```php
$sql = "SELECT * FROM print_queue WHERE id = ?";
$stmt = $this->db->prepare($sql);
$stmt->execute([$queueId]);
```

### 7. Registro de Auditoria

O sistema mantém registros detalhados de todas as ações para fins de auditoria:

```php
public function addHistoryEvent($queueId, $eventType, $description, $previousValue = null, $newValue = null, $userId = null) {
    $sql = "INSERT INTO print_queue_history (queue_id, event_type, description, previous_value, new_value, created_by, created_at)
            VALUES (:queue_id, :event_type, :description, :previous_value, :new_value, :created_by, NOW())";
    
    $params = [
        ':queue_id' => $queueId,
        ':event_type' => $eventType,
        ':description' => $description,
        ':previous_value' => $previousValue !== null ? json_encode($previousValue) : null,
        ':new_value' => $newValue !== null ? json_encode($newValue) : null,
        ':created_by' => $userId
    ];
    
    $this->db->execute($sql, $params);
}
```

## Fluxo de Segurança

### 1. Submissão de Modelo

1. O usuário deve estar autenticado
2. Os dados do formulário são validados com `InputValidationTrait`
3. O token CSRF é validado
4. As permissões do usuário são verificadas
5. As configurações de impressão são validadas
6. O modelo é adicionado à fila com um evento de auditoria registrado
7. Uma notificação é enviada ao usuário

### 2. Atribuição a uma Impressora

1. Apenas administradores podem atribuir trabalhos
2. O token CSRF é validado
3. O ID do item na fila é validado
4. O ID da impressora é validado
5. Verificação se o item está pendente
6. Verificação se a impressora está disponível
7. Criação do trabalho de impressão com um evento de auditoria registrado
8. Uma notificação é enviada ao usuário

### 3. Atualização de Status

1. Verificação de permissão (administrador)
2. Validação do token CSRF
3. Validação dos parâmetros
4. Verificação da transição de estado válida
5. Atualização do status com um evento de auditoria registrado
6. Notificação para o usuário

## Mitigação de Vulnerabilidades

### 1. Cross-Site Scripting (XSS)

- Sanitização de todas as saídas com `htmlspecialchars()`
- Validação rigorosa de entradas com `InputValidationTrait`
- Headers de segurança adequados configurados

### 2. Cross-Site Request Forgery (CSRF)

- Tokens CSRF em todos os formulários POST
- Validação de token no lado do servidor
- Verificação de expiração de token

### 3. Injeção SQL

- Uso exclusivo de prepared statements
- Parâmetros validados antes de uso em consultas
- Uso do método `validateForDatabase` para dados não sanitizados

### 4. Manipulação de Estado

- Validação rigorosa de transições de estado
- Verificações de propriedade do recurso
- Registro de auditoria para todas as alterações de estado

### 5. Escalação de Privilégios

- Verificação em múltiplos níveis:
  - Autenticação geral
  - Autorização específica (Admin vs. Usuário)
  - Verificação de propriedade do recurso
  - Validação de permissões para operações sensíveis

### 6. Divulgação de Informações

- Mensagens de erro genéricas para o usuário final
- Logs detalhados apenas para administradores
- Exposição seletiva de informações baseada no papel do usuário

## Testes de Segurança

### 1. Validação de Entrada

- **Teste**: Injeção de valores maliciosos nos parâmetros de formulário
- **Resultado**: Os valores são rejeitados ou sanitizados adequadamente

### 2. Proteção CSRF

- **Teste**: Tentativa de submissão sem token CSRF ou com token inválido
- **Resultado**: A requisição é rejeitada com uma mensagem apropriada

### 3. Controle de Acesso

- **Teste**: Tentativa de acesso não autorizado a funcionalidades administrativas
- **Resultado**: Redirecionamento para a página de login ou mensagem de permissão negada

### 4. Validação de Estado

- **Teste**: Tentativa de transição de estado inválida (ex: de 'completed' para 'printing')
- **Resultado**: A transição é rejeitada e um erro é registrado

### 5. Injeção SQL

- **Teste**: Submissão de caracteres especiais SQL nos parâmetros
- **Resultado**: Os parâmetros são tratados como dados, não como código SQL

## Boas Práticas Implementadas

1. **Defense in Depth**: Múltiplas camadas de segurança em cada operação
2. **Princípio do Privilégio Mínimo**: Acesso baseado estritamente nas necessidades da função
3. **Validação Completa**: Validação rigorosa de todas as entradas de usuário
4. **Sanitização de Saída**: Sanitização consistente de todas as saídas para o usuário
5. **Registro e Monitoramento**: Registro detalhado de ações sensíveis para auditoria
6. **Tratamento de Erros Seguro**: Mensagens de erro genéricas para usuários, logs detalhados para administradores

## Conclusão

O Sistema de Fila de Impressão 3D implementa práticas robustas de segurança em várias camadas para proteger contra ameaças comuns da web. A implementação segue os princípios de segurança por design, incorporando controles de validação, sanitização e autorização em cada etapa do processo. A auditoria abrangente e o monitoramento garantem que quaisquer problemas possam ser rapidamente identificados e resolvidos.
