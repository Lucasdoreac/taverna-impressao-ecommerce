# Documentação de Segurança: Dashboard de Administração

## Função
O Dashboard de Administração centraliza o gerenciamento da plataforma, fornecendo acesso a dados sensíveis e funções administrativas. Esta documentação detalha as medidas de segurança implementadas para garantir que este componente esteja protegido contra ameaças comuns.

## Implementação de Segurança

### Controle de Acesso
O dashboard implementa controle de acesso rigoroso em múltiplas camadas:

1. **Verificação de Autenticação**: Todas as rotas do dashboard verificam se o usuário está autenticado.
```php
// No construtor do AdminDashboardController
public function __construct() {
    // Verificar autenticação para todas as ações deste controller
    $this->requireAuth(['admin', 'manager']);
    
    // Resto do código...
}
```

2. **Verificação Baseada em Função (RBAC)**: Cada seção do dashboard verifica se o usuário tem a função apropriada antes de exibir ou processar dados.
```php
private function canUserAccessUsers() {
    $userId = $_SESSION['user_id'] ?? 0;
    $userRole = $_SESSION['user_role'] ?? '';
    
    // Apenas admins e gerentes podem acessar
    if ($userRole === 'admin' || $userRole === 'manager') {
        return true;
    }
    
    // Verificar permissão específica
    return AccessControl::canUserAccessObject($userId, 0, 'user_management', 'view');
}
```

3. **Verificação de Objetos Específicos**: Para operações em objetos específicos (como visualizar detalhes de um usuário), são implementadas verificações adicionais para evitar IDOR (Insecure Direct Object References).
```php
private function canUserAccessUser($targetUserId) {
    $userId = $_SESSION['user_id'] ?? 0;
    
    // Verificar permissão específica para este objeto
    return AccessControl::canUserAccessObject($userId, $targetUserId, 'user', 'view');
}
```

### Proteção CSRF
Todos os formulários e requisições AJAX do dashboard são protegidos contra CSRF:

1. **Geração de Token**: Um token único é gerado para cada sessão.
```php
$csrfToken = SecurityManager::getCsrfToken();
```

2. **Inclusão em Formulários**:
```php
<input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
```

3. **Inclusão em Requisições AJAX**:
```javascript
const configureAjax = () => {
    const csrfToken = '<?= $csrfToken ?>';
    
    // Adicionar token CSRF a todas as requisições AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    });
};
```

4. **Validação de Token**:
```php
if (!CsrfProtection::validateRequest()) {
    $this->setFlashMessage('error', 'Erro de validação de segurança. Por favor, tente novamente.');
    $this->redirect('admin/dashboard');
    return;
}
```

### Validação de Entrada
Todas as entradas de usuário são validadas usando o InputValidationTrait:

1. **Parâmetros GET**:
```php
$page = $this->getValidatedParam('page', 'int', [
    'default' => 1,
    'min' => 1
]);
```

2. **Parâmetros POST**:
```php
$title = $this->postValidatedParam('title', 'string', ['required' => true, 'maxLength' => 255]);
```

### Sanitização de Saída
Toda saída no dashboard é sanitizada para prevenir XSS:

```php
<td><?= htmlspecialchars($user['name']) ?></td>
```

### Proteção de API
O endpoint da API do dashboard implementa várias camadas de proteção:

1. **Verificação de Requisição AJAX**:
```php
if (!$this->isAjaxRequest()) {
    $this->redirect('admin/dashboard');
    return;
}
```

2. **Validação CSRF para Todas as Ações**:
```php
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!SecurityManager::validateCsrfToken($csrfToken)) {
    echo json_encode(['error' => 'Token de segurança inválido']);
    return;
}
```

3. **Verificação de Permissão para Cada Ação**:
```php
switch ($action) {
    case 'dashboard_stats':
        $hasPermission = $this->canUserAccessUsers() || $this->canUserAccessProducts();
        break;
    // ...
}

if (!$hasPermission) {
    echo json_encode(['error' => 'Você não tem permissão para acessar estes dados']);
    return;
}
```

### Headers de Segurança HTTP
Todas as respostas incluem headers de segurança HTTP apropriados:

```php
SecurityHeaders::apply();
```

Isto aplica:
- Content-Security-Policy
- X-XSS-Protection
- X-Content-Type-Options
- Strict-Transport-Security
- Referrer-Policy
- X-Frame-Options

## Vulnerabilidades Mitigadas
- **Cross-Site Scripting (XSS)**: Através de sanitização rigorosa de saída.
- **Cross-Site Request Forgery (CSRF)**: Através de tokens CSRF em todas as operações de alteração de estado.
- **Insecure Direct Object References (IDOR)**: Através de verificações de permissão específicas para cada objeto.
- **SQL Injection**: Através do uso exclusivo de prepared statements.
- **Escalada de Privilégios**: Através de verificações de função (RBAC) em todas as operações sensíveis.
- **Quebra de Autenticação**: Através de verificações obrigatórias de autenticação em todas as rotas.

## Testes de Segurança
- **Teste 1: Bypass de Controle de Acesso**: Tentativa de acessar rotas de administração com usuário sem permissão - BLOQUEADO
- **Teste 2: CSRF em Formulários**: Tentativa de submeter formulário administrativo sem token CSRF - BLOQUEADO
- **Teste 3: CSRF em API**: Tentativa de chamar API do dashboard sem header CSRF - BLOQUEADO
- **Teste 4: IDOR em Detalhes de Usuário**: Tentativa de acessar detalhes de usuário sem permissão - BLOQUEADO
- **Teste 5: XSS em Dados Dinâmicos**: Tentativa de injetar script via campos de filtro - BLOQUEADO
- **Teste 6: SQLi em Parâmetros de Filtro**: Tentativa de injetar SQL via parâmetros de URL - BLOQUEADO

## Melhores Práticas de Uso
1. Sempre verifique permissões antes de qualquer operação sensível.
2. Sempre use o InputValidationTrait para validar entrada de usuário.
3. Sempre use tokens CSRF em formulários POST e chamadas AJAX.
4. Sempre sanitize saída com htmlspecialchars().
5. Sempre use prepared statements para consultas SQL.
6. Nunca exiba mensagens de erro detalhadas ao usuário final.
7. Sempre registre tentativas de acesso não autorizado.