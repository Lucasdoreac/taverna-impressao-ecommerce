# Validação e Sanitização de Entrada

## Visão Geral

A validação e sanitização adequadas de entrada de dados são fundamentais para prevenir múltiplas vulnerabilidades, incluindo Cross-Site Scripting (XSS), injeção SQL, e outros ataques baseados em entrada maliciosa. Este documento descreve a abordagem padronizada para validar e sanitizar entrada de usuário no projeto Taverna da Impressão 3D.

## Componentes

### Classe Validator

A classe `Validator` fornece métodos para validar diferentes tipos de dados:

```php
<?php
// Namespace real: App\Lib\Security
class Validator {
    // Métodos principais
    public static function validate($input, $type, $options = []);
    public static function sanitize($input, $type);
    public static function isValid($input, $type, $options = []);
    public static function getErrors();
}
```

### Classe Sanitizer

A classe `Sanitizer` fornece métodos para sanitizar dados para uso seguro:

```php
<?php
// Namespace real: App\Lib\Security
class Sanitizer {
    // Métodos principais
    public static function html($input);
    public static function url($input);
    public static function email($input);
    public static function filename($input);
    public static function sql($input); // Não substitui prepared statements!
}
```

## Tipos de Validação Suportados

O método `Validator::validate()` suporta os seguintes tipos:

| Tipo | Descrição | Opções |
|------|-----------|--------|
| `string` | Valida strings | `min_length`, `max_length`, `regex` |
| `email` | Valida endereço de email | `check_mx` (verificar MX record) |
| `url` | Valida URL | `protocols` (lista de protocolos permitidos) |
| `integer` | Valida número inteiro | `min`, `max` |
| `float` | Valida número decimal | `min`, `max`, `precision` |
| `date` | Valida data | `format`, `min_date`, `max_date` |
| `boolean` | Valida booleano | - |
| `array` | Valida array | `min_items`, `max_items` |
| `file` | Valida arquivo | `allowed_types`, `max_size` |
| `creditcard` | Valida cartão de crédito | `card_types` |
| `cpf` | Valida CPF brasileiro | - |
| `cnpj` | Valida CNPJ brasileiro | - |
| `cep` | Valida CEP brasileiro | - |
| `phone` | Valida número de telefone | `country` |
| `password` | Valida senha | `min_length`, `require_numbers`, `require_special`, `require_uppercase` |

## Uso Básico

### Validação Simples

```php
<?php
// Validar um endereço de email
$email = $_POST['email'];
if (Validator::isValid($email, 'email')) {
    // Email é válido, prosseguir
} else {
    // Email inválido, exibir erro
    $errors = Validator::getErrors();
    echo $errors[0]; // "Email inválido"
}
```

### Validação com Opções Personalizadas

```php
<?php
// Validar um nome de usuário
$username = $_POST['username'];
$isValid = Validator::isValid($username, 'string', [
    'min_length' => 3,
    'max_length' => 20,
    'regex' => '/^[a-zA-Z0-9_]+$/' // Apenas letras, números e underscore
]);

if (!$isValid) {
    $errors = Validator::getErrors();
    foreach ($errors as $error) {
        echo "<div class='error'>{$error}</div>";
    }
}
```

### Validação e Sanitização Combinadas

```php
<?php
// Validar e sanitizar um comentário
$comment = $_POST['comment'];
$validComment = Validator::validate($comment, 'string', [
    'min_length' => 1,
    'max_length' => 1000
]);

if ($validComment !== false) {
    // Comentário válido, sanitizar para exibição
    $safeComment = Sanitizer::html($validComment);
    
    // Salvar no banco de dados (usar prepared statements!)
    $stmt = $db->prepare("INSERT INTO comments (user_id, content) VALUES (?, ?)");
    $stmt->execute([$userId, $validComment]);
    
    // Exibir confirmação
    echo "Comentário adicionado com sucesso!";
} else {
    // Comentário inválido
    $errors = Validator::getErrors();
    foreach ($errors as $error) {
        echo "<div class='error'>{$error}</div>";
    }
}
```

## Implementação em Controllers

### Exemplo Completo de Controller

```php
<?php
class ProductController extends Controller {
    private $productModel;
    
    public function __construct() {
        parent::__construct();
        $this->productModel = new ProductModel();
    }
    
    public function add() {
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!CsrfProtection::validateRequest()) {
                $this->view->render('error', [
                    'message' => 'Erro de validação do formulário. Por favor, tente novamente.'
                ]);
                return;
            }
            
            // Validar dados do produto
            $name = Validator::validate($_POST['name'] ?? '', 'string', [
                'min_length' => 3,
                'max_length' => 100
            ]);
            
            $price = Validator::validate($_POST['price'] ?? '', 'float', [
                'min' => 0.01,
                'max' => 9999.99
            ]);
            
            $description = Validator::validate($_POST['description'] ?? '', 'string', [
                'max_length' => 5000
            ]);
            
            $category_id = Validator::validate($_POST['category_id'] ?? '', 'integer', [
                'min' => 1
            ]);
            
            // Verificar se houve erros de validação
            if ($name === false || $price === false || $description === false || $category_id === false) {
                $errors = Validator::getErrors();
                $this->view->render('product/add', [
                    'errors' => $errors,
                    'input' => $_POST // Para repreencher o formulário
                ]);
                return;
            }
            
            // Todos os dados são válidos, adicionar produto
            $result = $this->productModel->add([
                'name' => $name,
                'price' => $price,
                'description' => $description,
                'category_id' => $category_id
            ]);
            
            if ($result) {
                // Redirecionar para lista de produtos
                header('Location: ' . BASE_URL . 'admin/products');
                return;
            } else {
                $this->view->render('product/add', [
                    'errors' => ['Erro ao adicionar produto.'],
                    'input' => $_POST
                ]);
                return;
            }
        }
        
        // Exibir formulário
        $this->view->render('product/add', [
            'categories' => $this->productModel->getAllCategories()
        ]);
    }
}
```

## Validação de Arquivos

### Validação Básica de Upload

```php
<?php
// Validar um arquivo enviado
if (isset($_FILES['modelo_3d'])) {
    $file = $_FILES['modelo_3d'];
    
    $isValid = Validator::isValid($file, 'file', [
        'allowed_types' => ['stl', 'obj', '3mf'],
        'max_size' => 10 * 1024 * 1024 // 10MB
    ]);
    
    if ($isValid) {
        // Arquivo válido, processar upload
        $uploadManager = new FileUploadManager();
        $uploadedFile = $uploadManager->store($file, 'uploads/models');
        
        if ($uploadedFile) {
            echo "Modelo 3D enviado com sucesso! Caminho: {$uploadedFile}";
        } else {
            echo "Erro ao salvar arquivo.";
        }
    } else {
        $errors = Validator::getErrors();
        foreach ($errors as $error) {
            echo "<div class='error'>{$error}</div>";
        }
    }
}
```

### Integração com FileUploadManager

```php
<?php
// Validação e gerenciamento avançado de uploads
class ModelUploader {
    public function processUpload($file) {
        // Validar arquivo
        $isValid = Validator::isValid($file, 'file', [
            'allowed_types' => ['stl', 'obj', '3mf'],
            'max_size' => 20 * 1024 * 1024 // 20MB
        ]);
        
        if (!$isValid) {
            return [
                'success' => false,
                'errors' => Validator::getErrors()
            ];
        }
        
        // Configurar opções de upload
        $options = [
            'destination' => 'uploads/models',
            'allowed_extensions' => ['stl', 'obj', '3mf'],
            'max_size' => 20 * 1024 * 1024,
            'rename' => true,
            'preserve_extension' => true
        ];
        
        // Processar upload
        $uploadManager = new FileUploadManager();
        $result = $uploadManager->upload($file, $options);
        
        if ($result['success']) {
            // Salvar metadados no banco de dados
            $modelId = $this->saveModelMetadata([
                'filename' => $result['filename'],
                'original_name' => $file['name'],
                'filepath' => $result['filepath'],
                'size' => $file['size'],
                'extension' => $result['extension']
            ]);
            
            return [
                'success' => true,
                'model_id' => $modelId,
                'filename' => $result['filename'],
                'filepath' => $result['filepath']
            ];
        } else {
            return [
                'success' => false,
                'errors' => [$result['error']]
            ];
        }
    }
    
    private function saveModelMetadata($data) {
        // Código para salvar metadados no banco de dados
        // ...
        
        return $modelId;
    }
}
```

## Validação de API

### Validação de Entrada JSON

```php
<?php
class ApiProductController extends Controller {
    public function create() {
        // Verificar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(['error' => 'Método não permitido'], 405);
            return;
        }
        
        // Obter e decodificar dados JSON
        $jsonInput = file_get_contents('php://input');
        $data = json_decode($jsonInput, true);
        
        if ($data === null) {
            $this->sendJsonResponse(['error' => 'JSON inválido'], 400);
            return;
        }
        
        // Validar dados
        $name = isset($data['name']) ? Validator::validate($data['name'], 'string', [
            'min_length' => 3,
            'max_length' => 100
        ]) : false;
        
        $price = isset($data['price']) ? Validator::validate($data['price'], 'float', [
            'min' => 0.01,
            'max' => 9999.99
        ]) : false;
        
        $description = isset($data['description']) ? Validator::validate($data['description'], 'string', [
            'max_length' => 5000
        ]) : '';
        
        $category_id = isset($data['category_id']) ? Validator::validate($data['category_id'], 'integer', [
            'min' => 1
        ]) : false;
        
        // Verificar erros de validação
        if ($name === false || $price === false || $category_id === false) {
            $this->sendJsonResponse([
                'error' => 'Validação falhou',
                'details' => Validator::getErrors()
            ], 400);
            return;
        }
        
        // Dados válidos, criar produto
        $productModel = new ProductModel();
        $productId = $productModel->add([
            'name' => $name,
            'price' => $price,
            'description' => $description,
            'category_id' => $category_id
        ]);
        
        if ($productId) {
            $this->sendJsonResponse([
                'success' => true,
                'product_id' => $productId,
                'message' => 'Produto criado com sucesso'
            ], 201);
        } else {
            $this->sendJsonResponse([
                'error' => 'Erro ao criar produto',
                'details' => $productModel->getLastError()
            ], 500);
        }
    }
    
    private function sendJsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
    }
}
```

## Sanitização para Saída

### HTML

```php
<?php
// Sanitizar dados para exibição em HTML
$userName = $_SESSION['user_name'];
$safeUserName = Sanitizer::html($userName);

echo "<h1>Bem-vindo, {$safeUserName}!</h1>";
```

### JavaScript

```php
<?php
// Sanitizar dados para uso em JavaScript
$userPreferences = [
    'theme' => 'dark',
    'language' => 'pt-BR'
];

$safePreferences = json_encode($userPreferences);
?>

<script>
const userPreferences = <?php echo $safePreferences; ?>;
console.log('Preferências carregadas:', userPreferences);
</script>
```

### URL

```php
<?php
// Sanitizar parâmetros para URLs
$category = $_GET['category'];
$safeCategorySlug = Sanitizer::url($category);

$redirectUrl = BASE_URL . 'products/category/' . $safeCategorySlug;
header('Location: ' . $redirectUrl);
```

## Boas Práticas

1. **Validar e sanitizar em todas as entradas**: Nunca confiar em dados de entrada, independentemente da fonte
2. **Validar no servidor**: Mesmo com validação no cliente, sempre validar novamente no servidor
3. **Validar por tipo**: Verificar não apenas formato, mas também tipo (string, int, float)
4. **Sanitizar para contexto**: Usar sanitização apropriada para cada contexto (HTML, SQL, URL)
5. **Usar prepared statements**: Complementar validação com prepared statements para consultas SQL
6. **Validar arrays**: Validar não apenas valores escalares, mas também arrays e estruturas complexas
7. **Limitar tamanhos**: Definir limites máximos para strings, arrays e outros tipos
8. **Validar nos controllers**: Implementar validação no início dos métodos de controller
9. **Centralizar validação**: Usar a biblioteca de validação em vez de implementações ad-hoc
10. **Mostrar mensagens claras**: Fornecer mensagens de erro úteis para o usuário

## Referências

- [OWASP Input Validation Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Input_Validation_Cheat_Sheet.html)
- [PHP Filter Functions](https://www.php.net/manual/en/book.filter.php)
- [CWE-20: Improper Input Validation](https://cwe.mitre.org/data/definitions/20.html)