# Sistema de Controle de Acesso

## Visão Geral

O sistema de controle de acesso implementa um modelo baseado em papéis (RBAC - Role-Based Access Control) para gerenciar permissões em toda a aplicação. Isso previne vulnerabilidades como Insecure Direct Object References (IDOR - CWE-639), onde um usuário poderia acessar ou modificar recursos sem a devida autorização.

## Classe AccessControl

A classe `AccessControl` é o componente central do sistema de controle de acesso:

```php
<?php
// Namespace real: App\Lib\Security
class AccessControl {
    // Métodos principais
    public static function canUserAccessObject($userId, $objectId, $objectType, $permissionType = 'view');
    public static function isUserAdmin($userId);
    public static function isUserManager($userId);
    public static function initUserPermissions($userId);
    public static function addPermission($userId, $objectId, $objectType, $permissionType);
    public static function removePermission($userId, $objectId, $objectType, $permissionType);
    public static function getUserPermissions($userId);
}
```

## Papéis de Usuário

O sistema define os seguintes papéis:

1. **admin**: Acesso completo a todos os recursos e funcionalidades
2. **manager/supervisor**: Acesso amplo a recursos operacionais e relatórios
3. **printer_operator**: Acesso específico a trabalhos de impressão
4. **customer**: Acesso limitado a recursos próprios (pedidos, modelos 3D)

## Tipos de Objeto

O sistema controla acesso a diversos tipos de objetos:

- **order**: Pedidos de impressão
- **product**: Produtos disponíveis para compra
- **user**: Usuários da plataforma
- **customer_model**: Modelos 3D enviados pelos clientes
- **print_job**: Trabalhos de impressão na fila
- **report**: Relatórios gerenciais

## Uso em Controllers

### Verificação de Acesso a Objetos

```php
<?php
class OrderController extends Controller {
    public function view($orderId) {
        // Verificar se o usuário pode acessar o pedido
        $userId = $_SESSION['user_id'];
        
        if (!AccessControl::canUserAccessObject($userId, $orderId, 'order', 'view')) {
            // Acesso negado
            $this->view->render('error', [
                'message' => 'Você não tem permissão para acessar este pedido.'
            ]);
            return;
        }
        
        // Acesso permitido, obter e renderizar pedido
        $orderModel = new OrderModel();
        $order = $orderModel->getById($orderId);
        
        $this->view->render('order/view', [
            'order' => $order
        ]);
    }
}
```

### Verificação de Papéis

```php
<?php
class ReportController extends Controller {
    public function sales() {
        $userId = $_SESSION['user_id'];
        
        // Verificar se o usuário tem papel de admin ou gerente
        if (!AccessControl::isUserAdmin($userId) && !AccessControl::isUserManager($userId)) {
            // Acesso negado
            $this->view->render('error', [
                'message' => 'Acesso restrito a administradores e gerentes.'
            ]);
            return;
        }
        
        // Acesso permitido, gerar e exibir relatório
        $reportModel = new ReportModel();
        $salesReport = $reportModel->generateSalesReport();
        
        $this->view->render('report/sales', [
            'report' => $salesReport
        ]);
    }
}
```

## Gerenciamento de Permissões

### Adicionar Permissão

```php
<?php
// Conceder permissão a um usuário para acessar um objeto específico
AccessControl::addPermission(
    $userId,     // ID do usuário
    $objectId,   // ID do objeto (ex: ID do pedido)
    'order',     // Tipo do objeto
    'view'       // Tipo de permissão
);
```

### Remover Permissão

```php
<?php
// Remover permissão de um usuário
AccessControl::removePermission(
    $userId,     // ID do usuário
    $objectId,   // ID do objeto
    'order',     // Tipo do objeto
    'view'       // Tipo de permissão
);
```

### Listar Permissões

```php
<?php
// Obter todas as permissões de um usuário
$permissions = AccessControl::getUserPermissions($userId);

// Exibir permissões
foreach ($permissions as $permission) {
    echo "Objeto: {$permission['object_type']} #{$permission['object_id']}, ";
    echo "Permissão: {$permission['permission_type']}<br>";
}
```

## Middleware de Controle de Acesso

```php
<?php
class AccessControlMiddleware {
    public function handle($request, $next) {
        // Verificar se o usuário está autenticado
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $path = $_SERVER['REQUEST_URI'];
        
        // Inicializar permissões na sessão (se ainda não estiverem)
        if (!isset($_SESSION['user_role'])) {
            AccessControl::initUserPermissions($userId);
        }
        
        // Verificar acesso baseado na rota
        if (strpos($path, '/admin') === 0 && !AccessControl::isUserAdmin($userId)) {
            header('HTTP/1.1 403 Forbidden');
            echo "Acesso negado";
            return;
        }
        
        // Continuar para o próximo middleware ou controller
        return $next($request);
    }
}
```

## Implementação em Templates

```php
<!-- Exibir ou ocultar elementos baseado em permissões -->
<?php if (AccessControl::isUserAdmin($_SESSION['user_id'])): ?>
    <a href="/admin/dashboard" class="admin-link">Painel de Administração</a>
<?php endif; ?>

<?php if (AccessControl::isUserManager($_SESSION['user_id'])): ?>
    <a href="/reports" class="reports-link">Relatórios</a>
<?php endif; ?>
```

## Controle de Acesso por Tipo de Objeto

### Pedidos (orders)

- **admin/manager**: Acesso a todos os pedidos
- **customer**: Acesso apenas a pedidos próprios

### Produtos (products)

- **Visualização**: Público para todos
- **Edição/Exclusão**: Apenas admin e manager

### Usuários (users)

- **Próprio perfil**: Cada usuário pode acessar e editar seu próprio perfil
- **Outros perfis**: Apenas admin pode acessar e editar

### Modelos 3D (customer_models)

- **admin/manager**: Acesso a todos os modelos
- **customer**: Acesso apenas a modelos próprios

### Trabalhos de Impressão (print_jobs)

- **admin/manager/printer_operator**: Acesso a todos os trabalhos
- **customer**: Acesso apenas a trabalhos relacionados a seus pedidos

### Relatórios (reports)

- **admin/manager**: Acesso a todos os relatórios
- **Outros usuários**: Sem acesso

## Logs e Auditoria

O sistema mantém registros de tentativas de acesso negadas para auditoria:

```php
<?php
// No método canUserAccessObject quando acesso é negado
error_log("Acesso negado: usuário {$userId} tentou acessar {$objectType} #{$objectId} com permissão {$permissionType}");
```

## Testes de Segurança

Para testar o sistema de controle de acesso:

```php
<?php
// Simular tentativa de acesso não autorizado
$userId = 123;      // Usuário comum
$orderId = 456;     // Pedido que não pertence a este usuário

// Verificar se o acesso é negado corretamente
$result = AccessControl::canUserAccessObject($userId, $orderId, 'order', 'view');
var_dump($result); // Deve retornar false
```

## Boas Práticas

1. **Verificação em múltiplas camadas**: Implementar controle de acesso tanto no frontend quanto no backend
2. **Negar por padrão**: Negar acesso por padrão e permitir explicitamente quando necessário
3. **Princípio do menor privilégio**: Conceder apenas as permissões mínimas necessárias
4. **Verificação granular**: Verificar permissões para cada operação específica, não apenas por rota
5. **Revisão regular**: Auditar e revisar permissões periodicamente