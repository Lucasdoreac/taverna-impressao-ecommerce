# Documentação de Segurança - Taverna da Impressão 3D

## Visão Geral

Este documento detalha as implementações de segurança incorporadas no projeto Taverna da Impressão 3D, com foco em:

1. Proteção CSRF (Cross-Site Request Forgery)
2. Cabeçalhos HTTP de Segurança
3. Boas práticas para desenvolvedores

## 1. Proteção CSRF

### 1.1 Funcionamento

A proteção CSRF foi implementada através da classe `CsrfProtection` que gerencia tokens de segurança para formulários e requisições. Esta proteção impede ataques onde sites maliciosos poderiam forçar os usuários a executar ações não desejadas em nosso sistema.

### 1.2 Implementação

A classe `CsrfProtection` está localizada em `app/lib/Security/CsrfProtection.php` e oferece:

- Geração de tokens seguros baseados em criptografia
- Validação de tokens em requisições
- Integração com formulários HTML
- Suporte a requisições AJAX
- Tempo de expiração configurável para tokens

### 1.3 Como utilizar

#### Em formulários HTML

Para adicionar proteção CSRF a um formulário:

```php
<form method="post" action="/alguma-acao">
    <?php echo CsrfProtection::getFormField(); ?>
    <!-- Outros campos do formulário -->
    <button type="submit">Enviar</button>
</form>
```

#### Em requisições AJAX

Para requisições AJAX, adicione o token CSRF ao payload:

```javascript
const csrfToken = <?php echo json_encode(CsrfProtection::getToken()); ?>;

fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify(data)
});
```

#### Validação no Controller

Em cada método de controller que processa dados POST, verifique o token:

```php
public function save() {
    // Verificar token CSRF
    if (!CsrfProtection::validateRequest()) {
        $_SESSION['error'] = 'Erro de validação de segurança. Por favor, tente novamente.';
        header('Location: ' . BASE_URL . 'pagina-anterior');
        exit;
    }
    
    // Prosseguir com o processamento normal
    // ...
}
```

Para links que alteram estado (GET dinâmicos), adicione o token na URL:

```php
<a href="<?= BASE_URL ?>admin/produtos/delete/<?= $id ?>?csrf_token=<?= CsrfProtection::getToken() ?>">Excluir</a>
```

E valide no controlador:

```php
public function delete($params) {
    // Verificar token CSRF na URL
    if (!isset($_GET['csrf_token']) || !CsrfProtection::validateToken($_GET['csrf_token'])) {
        $_SESSION['error'] = 'Erro de validação de segurança. Por favor, tente novamente.';
        header('Location: ' . BASE_URL . 'admin/produtos');
        exit;
    }
    
    // Prosseguir com a exclusão
    // ...
}
```

## 2. Cabeçalhos HTTP de Segurança

### 2.1 Funcionamento

Os cabeçalhos HTTP de segurança protegem contra vários tipos de ataques, como XSS, clickjacking, injection e ataques baseados em MIME sniffing. A implementação foi feita através da classe `SecurityHeaders`.

### 2.2 Implementação

A classe `SecurityHeaders` está localizada em `app/lib/Security/SecurityHeaders.php` e já é carregada automaticamente no `index.php`. Os cabeçalhos implementados incluem:

- **Content-Security-Policy (CSP)**: Restringe fontes de conteúdo
- **X-XSS-Protection**: Proteção contra Cross-Site Scripting
- **X-Content-Type-Options**: Evita MIME sniffing
- **Strict-Transport-Security (HSTS)**: Força conexões HTTPS
- **X-Frame-Options**: Previne clickjacking
- **Referrer-Policy**: Controla informações de referência
- **Feature-Policy/Permissions-Policy**: Restringe recursos do navegador
- **Cache-Control**: Controle de cache

### 2.3 Como utilizar

Os cabeçalhos são aplicados automaticamente em cada requisição através do `index.php`. Não é necessário nenhuma ação adicional.

Para personalizar as configurações, você pode modificar os valores padrão:

```php
// Em um arquivo de configuração ou no início da aplicação
SecurityHeaders::init([
    'csp' => [
        'directives' => [
            'script-src' => ["'self'", "https://example.com"],
            // Outras diretivas...
        ]
    ],
    // Outras configurações...
]);

// Aplicar os cabeçalhos com as novas configurações
SecurityHeaders::applyAll();
```

Para desativar um cabeçalho específico:

```php
SecurityHeaders::disableHeader('hsts');
```

## 3. Boas Práticas de Segurança

### 3.1 Validação de Entrada

- Sempre use `getValidatedParam()` para validar entrada do usuário
- Nunca confie em dados enviados pelo cliente
- Defina tipos e restrições explícitas para cada parâmetro
- Implemente validação tanto no cliente quanto no servidor

```php
// Exemplo de validação de entrada
$userId = $this->validateInput('user_id', 'int', ['required' => true, 'min' => 1]);
$name = $this->validateInput('name', 'string', ['required' => true, 'maxLength' => 100]);
```

### 3.2 Proteção contra SQL Injection

- Use sempre prepared statements
- Nunca concatene SQL com entrada do usuário
- Utilize abstrações de banco de dados sempre que possível

```php
// Correto
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);

// Incorreto - NUNCA FAÇA ISSO
$query = "SELECT * FROM users WHERE id = " . $_GET['id'];
```

### 3.3 Proteção XSS

- Sempre sanitize saídas com `htmlspecialchars()`
- Use a classe `SecurityManager::sanitize()` para simplificar
- Considere utilizar bibliotecas de templates com escape automático

```php
// Sanitização de saída
<div><?= SecurityManager::sanitize($userInput) ?></div>
```

### 3.4 Upload de Arquivos

- Valide tipo MIME e extensão dos arquivos
- Limite o tamanho máximo dos uploads
- Armazene os arquivos fora da raiz web ou use um CDN
- Renomeie arquivos para evitar sobreescrita e ataques de path traversal

```php
// Upload seguro de arquivos
$result = SecurityManager::processFileUpload($_FILES['arquivo'], $uploadDir, [
    'allowedExtensions' => ['jpg', 'png', 'pdf'],
    'maxSize' => 5 * 1024 * 1024, // 5MB
    'validateContent' => true
]);
```

## 4. Checklist de Segurança

Antes de enviar código para produção, verifique:

- [ ] Todos os formulários têm token CSRF
- [ ] Todos os controllers validam token CSRF para ações que modificam estado
- [ ] Toda entrada de usuário é validada
- [ ] Toda saída é sanitizada contra XSS
- [ ] Todas as consultas SQL usam prepared statements
- [ ] Uploads de arquivos são devidamente validados e limitados
- [ ] Senhas são armazenadas com hash seguro (bcrypt/Argon2)
- [ ] Os cabeçalhos HTTP de segurança estão configurados corretamente

## 5. Referências

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [OWASP CSRF Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)
- [Content Security Policy Reference](https://content-security-policy.com/)
- [Security Headers](https://securityheaders.com/)
- [PHP Security Best Practices](https://phptherightway.com/#security)