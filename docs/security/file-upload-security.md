# Segurança para Upload de Arquivos

## Visão Geral

O upload de arquivos representa uma área crítica de segurança na aplicação Taverna da Impressão 3D, especialmente considerando que o sistema lida com modelos 3D que serão processados pelo servidor. A implementação segura previne diversas vulnerabilidades, incluindo execução remota de código (RCE), negação de serviço (DoS), e armazenamento de conteúdo malicioso.

## Classe FileUploadManager

A classe `FileUploadManager` fornece métodos para gerenciar uploads de arquivo de forma segura:

```php
<?php
// Namespace real: App\Lib\Security
class FileUploadManager {
    // Métodos principais
    public function upload($file, array $options = []);
    public function validate($file, array $options = []);
    public function store($file, $destination, $filename = null);
    public function sanitizeFilename($filename);
    public function generateSecureFilename($originalName, $preserveExtension = true);
    public function getFileExtension($filename);
    public function isAllowedExtension($extension, array $allowedExtensions);
    public function isAllowedMimeType($mimeType, array $allowedMimeTypes);
    public function detectRealMimeType($filePath);
    public function scanFileForMalware($filePath);
}
```

## Fluxo de Upload Seguro

O processo completo de upload seguro segue estas etapas:

1. **Validação inicial** (formato, tamanho, tipo)
2. **Sanitização do nome do arquivo**
3. **Armazenamento em local seguro**
4. **Verificação de conteúdo**
5. **Processamento de metadados**
6. **Registro no banco de dados**

### Exemplo de Implementação Completa

```php
<?php
class ModelUploadController extends Controller {
    private $fileUploadManager;
    private $modelRepository;
    
    public function __construct() {
        parent::__construct();
        $this->fileUploadManager = new FileUploadManager();
        $this->modelRepository = new ModelRepository();
    }
    
    public function upload() {
        // Verificar se o usuário está autenticado
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->view->render('model/upload');
            return;
        }
        
        // Verificar token CSRF
        if (!CsrfProtection::validateRequest()) {
            $this->view->render('error', [
                'message' => 'Erro de validação do formulário. Por favor, tente novamente.'
            ]);
            return;
        }
        
        // Verificar se um arquivo foi enviado
        if (!isset($_FILES['model_file']) || $_FILES['model_file']['error'] !== UPLOAD_ERR_OK) {
            $this->view->render('model/upload', [
                'error' => 'Selecione um arquivo para upload.'
            ]);
            return;
        }
        
        $file = $_FILES['model_file'];
        
        // 1. Validação inicial
        $validationOptions = [
            'allowed_extensions' => ['stl', 'obj', '3mf'],
            'max_size' => 50 * 1024 * 1024, // 50MB
            'allowed_mime_types' => [
                'application/sla',
                'model/stl',
                'application/vnd.ms-pki.stl',
                'application/object',
                'model/obj',
                'application/vnd.ms-package.3dmanufacturing-3dmodel+xml'
            ]
        ];
        
        $validationResult = $this->fileUploadManager->validate($file, $validationOptions);
        
        if (!$validationResult['valid']) {
            $this->view->render('model/upload', [
                'error' => $validationResult['error']
            ]);
            return;
        }
        
        // 2. Sanitização e 3. Armazenamento
        $uploadOptions = [
            'destination' => MODELS_UPLOAD_PATH,
            'filename' => null, // Gerar nome seguro automaticamente
            'preserve_extension' => true,
            'permissions' => 0644 // Permissões restritas
        ];
        
        $uploadResult = $this->fileUploadManager->upload($file, $uploadOptions);
        
        if (!$uploadResult['success']) {
            $this->view->render('model/upload', [
                'error' => $uploadResult['error']
            ]);
            return;
        }
        
        // 4. Verificação de conteúdo (opcional para modelos 3D)
        $malwareScanResult = $this->fileUploadManager->scanFileForMalware($uploadResult['filepath']);
        
        if (!$malwareScanResult['clean']) {
            // Remover arquivo potencialmente malicioso
            unlink($uploadResult['filepath']);
            
            $this->view->render('model/upload', [
                'error' => 'Arquivo bloqueado por medidas de segurança.'
            ]);
            
            // Registrar tentativa suspeita
            error_log("Possível malware detectado em upload: User ID {$_SESSION['user_id']}, IP {$_SERVER['REMOTE_ADDR']}, Arquivo original: {$file['name']}");
            
            return;
        }
        
        // 5. Processar metadados (específico para modelos 3D)
        $metadataExtractor = new ModelMetadataExtractor();
        $metadata = $metadataExtractor->extract($uploadResult['filepath']);
        
        // 6. Registrar no banco de dados
        $userId = $_SESSION['user_id'];
        $modelName = Validator::validate($_POST['model_name'] ?? '', 'string', [
            'min_length' => 3,
            'max_length' => 100
        ]);
        
        if ($modelName === false) {
            $modelName = pathinfo($file['name'], PATHINFO_FILENAME);
            $modelName = Sanitizer::html($modelName);
        }
        
        $description = Validator::validate($_POST['description'] ?? '', 'string', [
            'max_length' => 2000
        ]);
        
        if ($description === false) {
            $description = '';
        }
        
        $modelData = [
            'user_id' => $userId,
            'name' => $modelName,
            'description' => $description,
            'filename' => $uploadResult['filename'],
            'original_filename' => $file['name'],
            'filepath' => $uploadResult['filepath'],
            'filesize' => $file['size'],
            'filetype' => $uploadResult['mime_type'],
            'extension' => $uploadResult['extension'],
            'hash' => hash_file('sha256', $uploadResult['filepath']),
            'metadata' => json_encode($metadata),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $modelId = $this->modelRepository->create($modelData);
        
        if (!$modelId) {
            // Erro ao salvar no banco de dados
            unlink($uploadResult['filepath']);
            
            $this->view->render('model/upload', [
                'error' => 'Erro ao processar o modelo. Por favor, tente novamente.'
            ]);
            return;
        }
        
        // Upload completo e bem-sucedido
        $this->redirect('models/view/' . $modelId);
    }
}
```

## Estratégias de Validação

### Validação de Extensão

```php
<?php
// Lista de extensões permitidas para modelos 3D
$allowedExtensions = ['stl', 'obj', '3mf'];

// Obter extensão do arquivo carregado
$fileExtension = strtolower(pathinfo($_FILES['model_file']['name'], PATHINFO_EXTENSION));

// Verificar se a extensão é permitida
if (!in_array($fileExtension, $allowedExtensions)) {
    echo "Tipo de arquivo não permitido.";
    exit;
}
```

### Validação de Tamanho

```php
<?php
// Tamanho máximo (em bytes)
$maxFileSize = 50 * 1024 * 1024; // 50MB

// Verificar tamanho do arquivo
if ($_FILES['model_file']['size'] > $maxFileSize) {
    echo "Arquivo muito grande. O tamanho máximo permitido é 50MB.";
    exit;
}
```

### Validação de Tipo MIME

```php
<?php
// Lista de tipos MIME permitidos
$allowedMimeTypes = [
    'application/sla',
    'model/stl',
    'application/vnd.ms-pki.stl',
    'application/object',
    'model/obj',
    'application/vnd.ms-package.3dmanufacturing-3dmodel+xml'
];

// Verificar tipo MIME usando finfo
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($_FILES['model_file']['tmp_name']);

if (!in_array($mimeType, $allowedMimeTypes)) {
    echo "Tipo de arquivo não permitido.";
    exit;
}
```

## Sanitização de Nome de Arquivo

```php
<?php
// Método seguro para gerar nome de arquivo único
function generateSecureFilename($originalName, $preserveExtension = true) {
    // Remover caracteres especiais e sanitizar
    $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalName);
    
    // Separar nome e extensão
    $info = pathinfo($filename);
    $name = $info['filename'];
    $extension = $preserveExtension && isset($info['extension']) ? '.' . $info['extension'] : '';
    
    // Gerar nome único baseado em timestamp e valor aleatório
    $uniqueName = substr(md5(uniqid(rand(), true)), 0, 10);
    
    // Limitar tamanho do nome original
    $name = substr($name, 0, 40);
    
    // Concatenar para formar nome final
    return $name . '_' . time() . '_' . $uniqueName . $extension;
}

// Uso
$secureFilename = generateSecureFilename($_FILES['model_file']['name']);
```

## Armazenamento Seguro

```php
<?php
// Define o destino fora da raiz web
define('SECURE_UPLOAD_PATH', dirname(__DIR__) . '/secure_uploads');

// Garante que o diretório existe e tem permissões corretas
if (!file_exists(SECURE_UPLOAD_PATH)) {
    mkdir(SECURE_UPLOAD_PATH, 0755, true);
}

// Move o arquivo para o destino seguro
$destination = SECURE_UPLOAD_PATH . '/' . $secureFilename;
if (move_uploaded_file($_FILES['model_file']['tmp_name'], $destination)) {
    // Define permissões restritas
    chmod($destination, 0644);
    echo "Upload concluído com sucesso!";
} else {
    echo "Erro ao salvar arquivo.";
}
```

## Detecção de Conteúdo Malicioso

```php
<?php
// Verificação básica de conteúdo malicioso em arquivos STL
function checkStlFile($filePath) {
    // Verificar se o arquivo parece ser um STL válido
    $handle = fopen($filePath, 'rb');
    if (!$handle) {
        return ['valid' => false, 'error' => 'Não foi possível abrir o arquivo para análise'];
    }
    
    // Ler o cabeçalho (80 bytes)
    $header = fread($handle, 80);
    
    // Verificar se é um STL binário (após o cabeçalho vem o número de triângulos como uint32)
    $triangleCount = unpack('V', fread($handle, 4));
    
    // Verificar coerência: cada triângulo tem 50 bytes
    $expectedSize = 84 + (50 * $triangleCount[1]); // 80 bytes header + 4 bytes count + 50 bytes per triangle
    
    // Obter tamanho real do arquivo
    $actualSize = filesize($filePath);
    
    fclose($handle);
    
    // Tolerância de 1% para possíveis metadados adicionais
    $tolerance = $expectedSize * 0.01;
    if (abs($actualSize - $expectedSize) > $tolerance) {
        return [
            'valid' => false, 
            'error' => 'Estrutura de arquivo STL inválida'
        ];
    }
    
    return ['valid' => true];
}
```

## Gerenciamento de Arquivos Enviados

### Tabela `print_status_files`

Esta tabela armazena metadados de segurança e informações sobre os arquivos enviados:

```sql
CREATE TABLE `print_status_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `model_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `filepath` varchar(512) NOT NULL,
  `filesize` int(11) NOT NULL,
  `filetype` varchar(100) NOT NULL,
  `extension` varchar(20) NOT NULL,
  `hash` varchar(64) NOT NULL,
  `metadata` text DEFAULT NULL,
  `security_scan_status` enum('pending','clean','suspicious','infected') DEFAULT 'pending',
  `security_scan_details` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_model_id` (`model_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_hash` (`hash`),
  CONSTRAINT `fk_file_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_file_model_id` FOREIGN KEY (`model_id`) REFERENCES `customer_models` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_file_order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Registro de Upload

```php
<?php
// Registrar upload no banco de dados com metadados de segurança
function recordFileUpload($userId, $modelId, $fileData) {
    global $db;
    
    $hash = hash_file('sha256', $fileData['filepath']);
    
    $sql = "INSERT INTO print_status_files (
                user_id, model_id, filename, original_filename, 
                filepath, filesize, filetype, extension,
                hash, created_at
            ) VALUES (
                :user_id, :model_id, :filename, :original_filename,
                :filepath, :filesize, :filetype, :extension,
                :hash, NOW()
            )";
    
    $params = [
        ':user_id' => $userId,
        ':model_id' => $modelId,
        ':filename' => $fileData['filename'],
        ':original_filename' => $fileData['original_name'],
        ':filepath' => $fileData['filepath'],
        ':filesize' => $fileData['size'],
        ':filetype' => $fileData['mime_type'],
        ':extension' => $fileData['extension'],
        ':hash' => $hash
    ];
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log("Erro ao registrar arquivo: " . $e->getMessage());
        return false;
    }
}
```

## Recuperação e Entrega Segura

```php
<?php
class FileDownloadController extends Controller {
    public function download($fileId) {
        // Verificar se o usuário está autenticado
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        // Obter informações do arquivo
        $fileRepository = new FileRepository();
        $file = $fileRepository->getById($fileId);
        
        if (!$file) {
            $this->view->render('error', [
                'message' => 'Arquivo não encontrado.'
            ]);
            return;
        }
        
        // Verificar permissão
        if ($file['user_id'] != $userId && !AccessControl::isUserAdmin($userId)) {
            $this->view->render('error', [
                'message' => 'Você não tem permissão para acessar este arquivo.'
            ]);
            return;
        }
        
        // Verificar se o arquivo existe
        if (!file_exists($file['filepath'])) {
            $this->view->render('error', [
                'message' => 'Arquivo não encontrado no servidor.'
            ]);
            return;
        }
        
        // Verificar hash para garantir integridade
        $currentHash = hash_file('sha256', $file['filepath']);
        if ($currentHash !== $file['hash']) {
            error_log("Integridade de arquivo comprometida: File ID {$fileId}, Hash esperado: {$file['hash']}, Hash atual: {$currentHash}");
            
            $this->view->render('error', [
                'message' => 'Erro ao verificar integridade do arquivo.'
            ]);
            return;
        }
        
        // Configurar cabeçalhos para download seguro
        $filesize = filesize($file['filepath']);
        HeaderManager::setDownloadHeaders(
            $file['original_filename'],
            $file['filetype'],
            $filesize,
            false // Download forçado em vez de exibição inline
        );
        
        // Entregar o arquivo
        readfile($file['filepath']);
        exit;
    }
}
```

## Considerações Adicionais

### Limitar Taxa de Upload

```php
<?php
class UploadRateLimiter {
    private $db;
    private $maxUploadsPerHour = 10;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function checkRateLimit($userId) {
        $sql = "SELECT COUNT(*) AS count FROM print_status_files 
                WHERE user_id = :user_id 
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch();
        
        if ($result['count'] >= $this->maxUploadsPerHour) {
            return [
                'allowed' => false,
                'message' => "Limite de uploads excedido. Tente novamente mais tarde.",
                'remaining' => 0,
                'reset_after' => '1 hora'
            ];
        }
        
        return [
            'allowed' => true,
            'remaining' => $this->maxUploadsPerHour - $result['count']
        ];
    }
}
```

### Verificação de Quota de Armazenamento

```php
<?php
class StorageQuotaManager {
    private $db;
    private $defaultQuota = 200 * 1024 * 1024; // 200MB por usuário
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function checkQuota($userId) {
        // Obter quota personalizada (se existir)
        $sql = "SELECT storage_quota FROM users WHERE id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch();
        
        $userQuota = $user && $user['storage_quota'] ? $user['storage_quota'] : $this->defaultQuota;
        
        // Calcular uso atual
        $sql = "SELECT SUM(filesize) AS total_used FROM print_status_files WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $usage = $stmt->fetch();
        
        $totalUsed = $usage && $usage['total_used'] ? $usage['total_used'] : 0;
        $remaining = $userQuota - $totalUsed;
        
        return [
            'quota' => $userQuota,
            'used' => $totalUsed,
            'remaining' => $remaining,
            'percentage' => $userQuota > 0 ? round(($totalUsed / $userQuota) * 100, 2) : 0
        ];
    }
    
    public function hasQuotaForFile($userId, $fileSize) {
        $quotaInfo = $this->checkQuota($userId);
        return $quotaInfo['remaining'] >= $fileSize;
    }
}
```

## Boas Práticas

1. **Validar antes de processar**: Sempre validar arquivos antes de processá-los
2. **Usar nomes de arquivo seguros**: Nunca confiar no nome original do arquivo
3. **Armazenar fora da raiz web**: Salvar arquivos em diretório não acessível diretamente pela web
4. **Restringir permissões**: Definir permissões mínimas necessárias para os arquivos
5. **Verificar tipo real**: Não confiar apenas na extensão, verificar o conteúdo real
6. **Implementar limites**: Restringir tamanho, frequência e quantidade de uploads
7. **Manter registros**: Armazenar metadados e informações de segurança para cada arquivo
8. **Verificar integridade**: Calcular e verificar hashes dos arquivos
9. **Entregar com segurança**: Usar cabeçalhos apropriados ao servir arquivos

## Referências

- [OWASP File Upload Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html)
- [CWE-434: Unrestricted Upload of File with Dangerous Type](https://cwe.mitre.org/data/definitions/434.html)
- [PHP File Upload Security](https://www.php.net/manual/en/features.file-upload.php)