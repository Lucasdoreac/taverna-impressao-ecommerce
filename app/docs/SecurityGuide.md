# Guia de Segurança - Taverna da Impressão 3D

Este documento descreve as implementações de segurança utilizadas no sistema Taverna da Impressão 3D, incluindo proteção CSRF e headers HTTP de segurança.

## Sumário

1. [Proteção CSRF](#proteção-csrf)
   - [O que é CSRF](#o-que-é-csrf)
   - [Implementação](#implementação-csrf)
   - [Uso em Formulários](#uso-em-formulários)
   - [Validação de Tokens](#validação-de-tokens)

2. [Headers HTTP de Segurança](#headers-http-de-segurança)
   - [Content-Security-Policy](#content-security-policy)
   - [X-XSS-Protection](#x-xss-protection)
   - [X-Content-Type-Options](#x-content-type-options)
   - [Strict-Transport-Security](#strict-transport-security)
   - [Implementação](#implementação-headers)

3. [Validação de Entrada](#validação-de-entrada)
   - [Classe InputValidator](#classe-inputvalidator)
   - [InputValidationTrait](#inputvalidationtrait)
   - [Uso nos Controllers](#uso-nos-controllers)

4. [Melhores Práticas](#melhores-práticas)
   - [Sanitização de Saída](#sanitização-de-saída)
   - [Padrões de Codificação Segura](#padrões-de-codificação-segura)

---

## Proteção CSRF

### O que é CSRF

CSRF (Cross-Site Request Forgery) é um tipo de ataque onde um site malicioso induz o navegador do usuário a realizar ações não autorizadas em um site onde o usuário está autenticado. A proteção CSRF impede que um atacante execute ações não autorizadas em nome do usuário.

### Implementação CSRF

A proteção CSRF no sistema Taverna da Impressão 3D é implementada através da classe `CsrfProtection`, que gerencia tokens de validação únicos para cada sessão de usuário.

A classe está localizada em `app/lib/Security/CsrfProtection.php` e possui os seguintes métodos principais:

- `generateToken()`: Gera um novo token CSRF
- `validateToken($token)`: Valida um token CSRF
- `validateRequest()`: Valida o token da requisição atual
- `getTokenInput()`: Retorna campo hidden HTML com token

O sistema utiliza tokens baseados em HMAC com timestamp para uma proteção mais robusta contra ataques de força bruta e replay.

#### Fluxo de Funcionamento

O diagrama abaixo ilustra o fluxo completo de proteção CSRF no sistema, desde a geração do token até sua validação:

![Fluxo de Proteção CSRF](../docs/artifacts/csrf-flow-diagram.png)

1. Quando um usuário acessa uma página com formulário, o sistema gera um token CSRF único
2. O token é incluído como campo hidden no formulário HTML
3. Ao submeter o formulário, o token é enviado junto com os dados
4. O controller valida o token antes de processar a requisição
5. Se o token for inválido ou ausente, a requisição é rejeitada

### Uso em Formulários

Todos os formulários POST devem incluir um token CSRF para validação. Exemplo de implementação:

```php
<form method="post" action="/processar">
    <?php echo CsrfProtection::getTokenInput(); ?>
    <!-- Outros campos do formulário -->
    <button type="submit">Enviar</button>
</form>
```

Para links que realizam ações sensíveis (como excluir, aprovar, etc.), adicione o token como parâmetro de URL:

```php
<a href="/admin/usuario/desativar/123?csrf_token=<?php echo SecurityManager::getCsrfToken(); ?>">
    Desativar Usuário
</a>
```

### Validação de Tokens

Nos controllers, a validação de tokens deve ser realizada antes de processar qualquer ação que modifique dados:

```php
// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    if (!CsrfProtection::validateRequest()) {
        $_SESSION['error'] = 'Erro de validação de segurança. Por favor, tente novamente.';
        header('Location: ' . BASE_URL . 'pagina-anterior');
        exit;
    }
    
    // Processar o formulário...
}
```

Para validar tokens em parâmetros de URL:

```php
$csrfToken = isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '';
if (!CsrfProtection::validateToken($csrfToken)) {
    // Token inválido, redirecionar ou exibir erro
}
```

---

## Headers HTTP de Segurança

Os headers HTTP de segurança fornecem uma camada adicional de proteção contra diversos ataques web, como XSS, clickjacking, e injeção de conteúdo.

### Content-Security-Policy

Content-Security-Policy (CSP) é um mecanismo que ajuda a mitigar ataques XSS e injeção de conteúdo, permitindo que o servidor declare quais fontes de conteúdo são consideradas seguras.

Configuração atual:

```
Content-Security-Policy: default-src 'self'; 
                         script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; 
                         style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; 
                         font-src 'self' https://fonts.gstatic.com; 
                         img-src 'self' data:; 
                         connect-src 'self';
```

### X-XSS-Protection

Este header ativa o filtro XSS integrado do navegador, que pode ajudar a bloquear alguns ataques XSS.

```
X-XSS-Protection: 1; mode=block
```

### X-Content-Type-Options

Impede que o navegador faça MIME-sniffing e execute arquivos como um tipo diferente do declarado.

```
X-Content-Type-Options: nosniff
```

### Strict-Transport-Security

Força o navegador a se comunicar com o site apenas através de HTTPS, mesmo se o usuário tentar usar HTTP.

```
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

### Implementação Headers

Os headers de segurança são implementados através da classe `SecurityHeaders`, localizada em `app/lib/Security/SecurityHeaders.php`. Os headers são aplicados automaticamente em todas as respostas HTTP do sistema.

Exemplo de implementação no arquivo `index.php`:

```php
// Carregar SecurityHeaders
require_once APP_PATH . '/lib/Security/SecurityHeaders.php';

// Aplicar headers de segurança
SecurityHeaders::apply();

// Continuar com o processamento da requisição...
```

A classe `SecurityHeaders` permite personalização dos headers através do método `init()`:

```php
SecurityHeaders::init([
    'csp' => [
        'directives' => [
            'script-src' => ["'self'", "https://exemplo.com"],
        ]
    ],
    'hsts' => [
        'max-age' => 63072000, // 2 anos
    ]
]);
```

---

## Validação de Entrada

### Classe InputValidator

A classe `InputValidator` é responsável pela validação e sanitização centralizada de todos os inputs do sistema. Está localizada em `app/lib/Security/InputValidator.php` e fornece métodos para validar dados de diferentes fontes (GET, POST, FILES).

Principais métodos:

- `validate($source, $field, $type, $options)`: Valida e sanitiza um campo
- `validateAll($source, $validations)`: Valida múltiplos campos
- `hasErrors()`: Verifica se houve erros de validação
- `getErrors()`: Retorna todos os erros de validação
- `addError($field, $message)`: Adiciona um erro de validação

### InputValidationTrait

O trait `InputValidationTrait` fornece métodos convenientes para validação de entrada nos controllers. Está localizado em `app/lib/Security/InputValidationTrait.php`.

Principais métodos:

- `getValidatedParam($field, $type, $options)`: Valida parâmetro GET
- `postValidatedParam($field, $type, $options)`: Valida parâmetro POST
- `requestValidatedParam($field, $type, $options)`: Valida parâmetro REQUEST
- `fileValidatedParam($field, $options)`: Valida upload de arquivo
- `jsonValidatedParam($field, $type, $options)`: Valida dados JSON
- `getValidatedParams($validations)`: Valida múltiplos parâmetros GET
- `postValidatedParams($validations)`: Valida múltiplos parâmetros POST
- `hasValidationErrors()`: Verifica se houve erros de validação
- `getValidationErrors()`: Obtém todos os erros de validação

### Uso nos Controllers

Todos os controllers do sistema devem implementar o `InputValidationTrait` para garantir a validação consistente de todas as entradas:

```php
class ExemploController {
    // Implementação do trait de validação
    use InputValidationTrait;
    
    public function __construct() {
        // Carregar InputValidationTrait
        require_once APP_PATH . '/lib/Security/InputValidationTrait.php';
    }
    
    public function processar() {
        // Validação de parâmetros
        $nome = $this->postValidatedParam('nome', 'string', ['required' => true, 'minLength' => 3]);
        $email = $this->postValidatedParam('email', 'email', ['required' => true]);
        $idade = $this->postValidatedParam('idade', 'int', ['min' => 18, 'max' => 120]);
        
        // Verificar erros de validação
        if ($this->hasValidationErrors()) {
            $_SESSION['error'] = implode('<br>', $this->getValidationErrors());
            // Redirecionar de volta para o formulário
            return;
        }
        
        // Processar dados validados...
    }
}
```

Para validação em massa de múltiplos campos:

```php
$validations = [
    'nome' => ['type' => 'string', 'required' => true, 'minLength' => 3],
    'email' => ['type' => 'email', 'required' => true],
    'idade' => ['type' => 'int', 'min' => 18, 'max' => 120]
];

$data = $this->postValidatedParams($validations);

if (!$data || $this->hasValidationErrors()) {
    // Tratar erros...
}
```

---

## Melhores Práticas

### Sanitização de Saída

Além da validação de entrada, é essencial sanitizar todas as saídas para prevenir ataques XSS:

```php
// Exibir texto sanitizado
echo SecurityManager::sanitize($text);

// Ou usando a função helper
echo h($text);
```

A função `h()` é um alias para `htmlspecialchars()` e deve ser usada para todo conteúdo dinâmico exibido nas views.

### Padrões de Codificação Segura

- **Sempre** valide e sanitize entrada do usuário
- **Sempre** inclua tokens CSRF em formulários e ações sensíveis
- **Sempre** utilize prepared statements para consultas SQL
- **Sempre** sanitize saída antes de exibir ao usuário
- **Nunca** concatene SQL com input direto do usuário
- **Nunca** armazene senhas em texto plano (use bcrypt)
- **Nunca** confie em validação do lado do cliente
- **Sempre** valide permissões de acesso em todas as ações

### Atualizações de Segurança

O sistema de segurança é constantemente atualizado para mitigar novas vulnerabilidades. Consulte a documentação mais recente e mantenha-se atualizado sobre as melhores práticas de segurança.

---

## Checklist de Segurança para Novos Recursos

- [ ] Validação de entrada em todos os parâmetros expostos
- [ ] Proteção CSRF em todos os formulários e ações sensíveis
- [ ] Headers HTTP de segurança aplicados
- [ ] Sanitização de saída em todo conteúdo dinâmico
- [ ] Controle de acesso e validação de permissões
- [ ] Logging adequado para auditoria
- [ ] Tratamento seguro de arquivos (se aplicável)
- [ ] Uso de prepared statements em consultas SQL