# Proteção CSRF (Cross-Site Request Forgery)

**Atualizado em:** 2025-04-02
**Status:** Implementado nos formulários principais

## Visão Geral

A proteção CSRF (Cross-Site Request Forgery) impede que atacantes forcem usuários autenticados a executar ações indesejadas em nossa aplicação. A implementação usa tokens únicos em formulários e requisições AJAX para validar que a solicitação veio de uma fonte legítima.

### Implementações Recentes

Em 2 de abril de 2025, a proteção CSRF foi implementada nos seguintes formulários e controllers:

- **Autenticação**: login.php, register.php, recover_password.php, reset_password.php (controller: AuthController.php)
- **Checkout/Pagamento**: checkout.php (controller: CheckoutController.php)

Em cada um desses formulários, o token CSRF foi adicionado usando `CsrfProtection::getFormField()` e a validação foi implementada nos controllers correspondentes usando `CsrfProtection::validateRequest()`.

## Classe CsrfProtection

A classe `CsrfProtection` oferece métodos para gerar, validar e gerenciar tokens CSRF:

```php
<?php
// Namespace real: App\Lib\Security
class CsrfProtection {
    // Métodos principais
    public static function generateToken();
    public static function getToken($forceNew = false);
    public static function validateToken($token, $regenerateOnSuccess = true);
    public static function validateRequest($regenerateOnSuccess = true);
    public static function getFormField($forceNew = false);
    public static function getAjaxToken($forceNew = false);
}
```

## Uso em Formulários HTML

### Método 1: Usando getFormField()

```php
<form method="post" action="/produto/adicionar">
    <?php echo CsrfProtection::getFormField(); ?>
    <!-- Outros campos do formulário -->
    <input type="text" name="nome" value="">
    <button type="submit">Enviar</button>
</form>
```

### Método 2: Obtendo o token manualmente

```php
<?php $csrfToken = CsrfProtection::getToken(); ?>
<form method="post" action="/produto/adicionar">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <!-- Outros campos do formulário -->
    <input type="text" name="nome" value="">
    <button type="submit">Enviar</button>
</form>
```

## Uso em Requisições AJAX

### Com jQuery

```javascript
$.ajax({
    url: '/api/produtos',
    type: 'POST',
    data: {
        nome: 'Novo Produto',
        preco: 99.90,
        csrf_token: '<?php echo CsrfProtection::getToken(); ?>'
    },
    success: function(response) {
        console.log('Produto adicionado com sucesso!');
    },
    error: function(xhr) {
        console.error('Erro ao adicionar produto:', xhr.responseText);
    }
});
```

### Com fetch API

```javascript
const csrfToken = '<?php echo CsrfProtection::getToken(); ?>';

fetch('/api/produtos', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify({
        nome: 'Novo Produto',
        preco: 99.90
    })
})
.then(response => response.json())
.then(data => console.log('Produto adicionado com sucesso!', data))
.catch(error => console.error('Erro ao adicionar produto:', error));
```

## Validação no Controller

```php
<?php
class ProdutoController extends Controller {
    public function adicionar() {
        // Validar token CSRF antes de processar
        if (!CsrfProtection::validateRequest()) {
            // Token inválido ou ausente
            $this->view->render('error', [
                'message' => 'Erro de validação do formulário. Por favor, tente novamente.'
            ]);
            return;
        }
        
        // Token válido, processar formulário normalmente
        $nome = $this->input->post('nome');
        // Resto do código...
    }
}
```

## Middleware de CSRF

Para aplicar proteção CSRF automaticamente a todas as rotas POST, PUT, DELETE:

```php
<?php
class CsrfMiddleware {
    public function handle($request, $next) {
        // Verificar se é um método que altera estado
        $method = $_SERVER['REQUEST_METHOD'];
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            // Verificar token CSRF
            if (!CsrfProtection::validateRequest()) {
                // Token inválido
                header('HTTP/1.1 403 Forbidden');
                echo json_encode(['error' => 'CSRF token inválido']);
                return;
            }
        }
        
        // Continuar para o próximo middleware ou controller
        return $next($request);
    }
}
```

## Configurações Avançadas

```php
<?php
// Alterar tempo de vida do token (padrão: 7200 segundos = 2 horas)
CsrfProtection::setTokenLifetime(3600); // 1 hora

// Alterar nome do token (padrão: csrf_token)
CsrfProtection::setTokenName('my_csrf_token');
```

## Considerações de Segurança

- Tokens CSRF são regerados automaticamente após cada validação bem-sucedida
- O mecanismo usa comparação de tempo constante (`hash_equals`) para evitar timing attacks
- Os tokens têm um tempo de vida limitado para mitigar roubo de token
- A validação funciona com múltiplos métodos de envio (POST, GET, headers, JSON)

## Implementação em Formulários Existentes

Lista de verificação para garantir que todos os formulários estejam protegidos:

- [x] Formulários de login e registro
- [x] Formulários de recuperação de senha
- [x] Formulários de checkout/pagamento
- [ ] Formulários de alteração de dados pessoais
- [ ] Formulários de upload
- [ ] Formulários de administração
- [ ] APIs que modificam dados
- [ ] Requisições AJAX

## Troubleshooting

### Tokens Expirados

Se os usuários estão recebendo erros de token expirado frequentemente, considere:

1. Aumentar o tempo de vida do token (`setTokenLifetime`)
2. Verificar se a aplicação está armazenando sessões corretamente
3. Garantir que o JavaScript não está cacheando tokens antigos

### Problemas com SPA (Single Page Applications)

Para aplicações SPA, considere:

1. Usar o header `X-CSRF-Token` para validação
2. Implementar um endpoint para renovar tokens
3. Utilizar `getAjaxToken()` para obter tokens via AJAX

## Teste de Segurança

Você pode testar a proteção CSRF com:

```php
<?php
// Simular uma requisição sem token CSRF
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['nome' => 'Produto Teste'];

// Verificar se a validação falha corretamente
$result = CsrfProtection::validateRequest();
var_dump($result); // Deve retornar false
```