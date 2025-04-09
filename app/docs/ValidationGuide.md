# Guia de Validação de Entrada - Taverna da Impressão 3D

Este documento descreve o sistema padronizado de validação de entrada implementado na Taverna da Impressão 3D, incluindo exemplos de uso e referência para desenvolvedores.

## Índice

1. [Visão Geral](#visão-geral)
2. [Integração em Controllers](#integração-em-controllers)
3. [Tipos de Validação](#tipos-de-validação)
4. [Opções de Validação](#opções-de-validação)
5. [Tratamento de Erros](#tratamento-de-erros)
6. [Exemplos Práticos](#exemplos-práticos)
7. [Validação de Uploads](#validação-de-uploads)
8. [Melhores Práticas](#melhores-práticas)

## Visão Geral

O sistema de validação de entrada é composto por:

1. **InputValidator**: Classe principal que implementa a validação e sanitização.
2. **InputValidationTrait**: Trait para integração em controllers, fornecendo métodos auxiliares.

Este sistema permite validar e sanitizar todos os tipos de entrada (GET, POST, FILES, etc.) de forma consistente e segura, prevenindo vulnerabilidades como SQL Injection e XSS.

## Integração em Controllers

Para usar o sistema de validação em um controller:

1. Inclua o InputValidationTrait:

```php
class MeuController {
    /**
     * Incluir o trait de validação de entrada
     */
    use InputValidationTrait;
    
    public function __construct() {
        // Incluir o trait
        require_once dirname(__FILE__) . '/../lib/Security/InputValidationTrait.php';
        
        // Resto do construtor...
    }
    
    // Métodos do controller...
}
```

2. Utilize os métodos de validação:

```php
public function minhaAction() {
    // Validar parâmetro único
    $id = $this->getValidatedParam('id', 'int', ['required' => true, 'min' => 1]);
    
    // Validar múltiplos parâmetros
    $validatedParams = $this->getValidatedParams([
        'nome' => [
            'type' => 'string',
            'required' => true,
            'maxLength' => 100
        ],
        'email' => [
            'type' => 'email',
            'required' => true
        ],
        'idade' => [
            'type' => 'int',
            'min' => 18,
            'max' => 120,
            'default' => 18
        ]
    ]);
    
    // Usar os parâmetros validados
    $nome = $validatedParams['nome'];
    $email = $validatedParams['email'];
    $idade = $validatedParams['idade'];
    
    // Resto do código...
}
```

## Tipos de Validação

Os seguintes tipos são suportados pelo sistema:

| Tipo | Descrição | Uso Típico |
|------|-----------|------------|
| `string` | Valida e sanitiza strings | Nomes, descrições, textos |
| `int` | Valida e converte inteiros | IDs, quantidades, idades |
| `float` | Valida e converte números de ponto flutuante | Preços, medidas |
| `email` | Valida endereços de e-mail | E-mails de usuários |
| `url` | Valida URLs | Links, referências externas |
| `date` | Valida datas | Datas de nascimento, datas de eventos |
| `datetime` | Valida data e hora | Timestamps, agendamentos |
| `bool` | Valida e converte booleanos | Opções sim/não, flags, estados |
| `array` | Valida arrays | Listas, múltiplas seleções |
| `file` | Valida uploads de arquivos | Imagens, documentos, modelos 3D |
| `slug` | Valida slugs | URLs amigáveis |
| `enum` | Valida valor de uma lista | Seleções de opções fixas |
| `phone` | Valida números de telefone | Contatos |
| `cpf` | Valida CPF brasileiro | Documentos de usuários |
| `cnpj` | Valida CNPJ brasileiro | Documentos de empresas |
| `cep` | Valida CEP brasileiro | Endereços |

## Opções de Validação

Cada tipo de validação aceita opções específicas:

### Opções Gerais (todos os tipos)

| Opção | Tipo | Descrição |
|-------|------|-----------|
| `required` | boolean | Define se o campo é obrigatório |
| `default` | mixed | Valor padrão se o campo não for fornecido |
| `requiredMessage` | string | Mensagem de erro personalizada para campo obrigatório |

### Opções para Strings

| Opção | Tipo | Descrição |
|-------|------|-----------|
| `minLength` | int | Tamanho mínimo da string |
| `maxLength` | int | Tamanho máximo da string |
| `pattern` | string | Padrão regex que a string deve seguir |
| `sanitize` | boolean | Se deve aplicar htmlspecialchars (padrão: true) |

### Opções para Números (int/float)

| Opção | Tipo | Descrição |
|-------|------|-----------|
| `min` | number | Valor mínimo permitido |
| `max` | number | Valor máximo permitido |

### Opções para Datas

| Opção | Tipo | Descrição |
|-------|------|-----------|
| `format` | string | Formato da data (padrão: Y-m-d) |
| `minDate` | string | Data mínima permitida |
| `maxDate` | string | Data máxima permitida |

### Opções para Arrays

| Opção | Tipo | Descrição |
|-------|------|-----------|
| `minItems` | int | Número mínimo de itens |
| `maxItems` | int | Número máximo de itens |
| `itemType` | string | Tipo de validação para cada item |
| `delimiter` | string | Delimitador para converter string em array |

### Opções para Arquivos

| Opção | Tipo | Descrição |
|-------|------|-----------|
| `maxSize` | int | Tamanho máximo em bytes |
| `allowedExtensions` | array | Array com extensões permitidas |
| `allowedMimeTypes` | array | Array com tipos MIME permitidos |

### Opções para Enum

| Opção | Tipo | Descrição |
|-------|------|-----------|
| `allowedValues` | array | Lista de valores permitidos |

## Tratamento de Erros

O sistema de validação registra erros que podem ser verificados:

```php
// Validar dados
$dados = $this->postValidatedParams([
    'nome' => ['type' => 'string', 'required' => true]
]);

// Verificar se houve erros
if ($this->hasValidationErrors()) {
    // Obter todos os erros
    $erros = $this->getValidationErrors();
    
    // Exibir erros ao usuário ou redirecionar
    // ...
    
    // Limpar erros para próximas validações
    $this->clearValidationErrors();
}
```

## Exemplos Práticos

### Formulário de Cadastro

```php
public function cadastrar() {
    // Processar apenas se for POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validar todos os campos do formulário
        $usuario = $this->postValidatedParams([
            'nome' => [
                'type' => 'string',
                'required' => true,
                'minLength' => 3,
                'maxLength' => 100
            ],
            'email' => [
                'type' => 'email',
                'required' => true
            ],
            'senha' => [
                'type' => 'string',
                'required' => true,
                'minLength' => 8,
                'sanitize' => false // Não aplicar htmlspecialchars em senhas
            ],
            'confirmacao_senha' => [
                'type' => 'string',
                'required' => true,
                'sanitize' => false
            ],
            'data_nascimento' => [
                'type' => 'date',
                'required' => true,
                'format' => 'Y-m-d'
            ],
            'termos' => [
                'type' => 'bool',
                'required' => true,
                'requiredMessage' => 'Você deve aceitar os termos de uso'
            ]
        ]);
        
        // Verificar erros de validação
        if ($this->hasValidationErrors()) {
            // Tratar erros...
            return;
        }
        
        // Verificar se senhas coincidem
        if ($usuario['senha'] !== $usuario['confirmacao_senha']) {
            // Erro de senha...
            return;
        }
        
        // Salvar usuário no banco...
    }
    
    // Exibir formulário
    require_once VIEWS_PATH . '/cadastro.php';
}
```

### Filtragem de Produtos

```php
public function listarProdutos() {
    // Validar parâmetros de filtro
    $filtros = $this->getValidatedParams([
        'categoria' => [
            'type' => 'int',
            'default' => null
        ],
        'preco_min' => [
            'type' => 'float',
            'min' => 0,
            'default' => null
        ],
        'preco_max' => [
            'type' => 'float',
            'min' => 0,
            'default' => null
        ],
        'ordenar' => [
            'type' => 'enum',
            'allowedValues' => ['preco_asc', 'preco_desc', 'nome', 'data'],
            'default' => 'data'
        ],
        'pagina' => [
            'type' => 'int',
            'min' => 1,
            'default' => 1
        ]
    ]);
    
    // Construir query baseada nos filtros...
    
    // Exibir produtos...
}
```

## Validação de Uploads

Para arquivos, utilize o método `processValidatedFileUpload`:

```php
public function uploadModelo() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validar e processar upload
        $arquivo = $this->processValidatedFileUpload('modelo_3d', UPLOADS_PATH . '/modelos', [
            'maxSize' => 20 * 1024 * 1024, // 20MB
            'allowedExtensions' => ['stl', 'obj', 'gcode'],
            'allowedMimeTypes' => [
                'application/octet-stream', // para STL e OBJ
                'text/plain' // para GCODE
            ]
        ]);
        
        if (!$arquivo) {
            // Tratar erro de upload...
            $erros = $this->getValidationErrors();
            return;
        }
        
        // Upload bem-sucedido, salvar referência no banco...
    }
    
    // Exibir formulário de upload
    require_once VIEWS_PATH . '/upload.php';
}
```

## Melhores Práticas

1. **Sempre valide todas as entradas do usuário**, mesmo campos opcionais.
2. **Defina valores padrão** para campos opcionais.
3. **Use mensagens de erro personalizadas** para melhorar a experiência do usuário.
4. **Verifique erros de validação** antes de prosseguir com o processamento.
5. **Sanitize todas as saídas** para prevenir XSS.
6. **Use tipos específicos** (email, date, url) em vez de simplesmente string.
7. **Configure corretamente os limites** (min, max, minLength, maxLength) para adicionar segurança.
8. **Não desabilite sanitização** a menos que seja absolutamente necessário.
9. **Valide uploads de arquivos** para evitar armazenamento de conteúdo malicioso.
10. **Documente validações complexas** para facilitar a manutenção do código.

---

Documento elaborado por: Claude Sonnet  
Última atualização: 03/04/2025