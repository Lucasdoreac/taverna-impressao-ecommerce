# Documentação de Segurança: Model3DValidator

## Função
O `Model3DValidator` é um componente crítico de segurança que realiza validação e sanitização robusta de arquivos de modelos 3D carregados no sistema da Taverna da Impressão 3D. Ele implementa múltiplas camadas de verificação para garantir que apenas arquivos legítimos e seguros sejam processados.

## Vulnerabilidades Mitigadas

### SEC-47: Validação Inadequada de Tipos MIME para Uploads de Modelos 3D
**Severidade:** Média

**Descrição da Vulnerabilidade:**
A versão anterior aceitava o tipo MIME genérico `application/octet-stream` para arquivos STL sem validação estrutural adicional, permitindo que atacantes potencialmente fizessem upload de arquivos maliciosos disfarçados com extensão STL.

**Mitigação Implementada:**
1. Validação multi-camada com verificação baseada em assinaturas de arquivo (magic bytes)
2. Validação estrutural avançada antes de aceitar arquivos com tipo MIME genérico
3. Verificação extensiva de coerência interna para arquivos binários
4. Análise de estrutura semanticamente válida para cada formato suportado

### Outras Vulnerabilidades Mitigadas
- **Injeção de Código:** Detecção de padrões maliciosos em arquivos que podem conter código (OBJ, 3MF)
- **Explosão de Compressão:** Validação contra arquivos ZIP malformados em 3MF que poderiam causar ataques DoS
- **Bypass de Validação:** Verificação robusta contra arquivos que tentam confundir validadores com cabeçalhos falsos
- **Corrupção de Memória:** Proteção contra arquivos estruturalmente corrompidos que poderiam causar falhas em processadores downstream

## Implementação

### Principais Melhorias de Segurança

#### 1. Verificação de Assinaturas de Arquivo (Magic Bytes)
```php
private static function checkFileSignature($filePath, $extension) {
    // Implementação robusta que verifica padrões de bytes específicos 
    // para cada formato de arquivo 3D suportado
}
```

#### 2. Validação Estrutural Rigorosa para STL Binário
```php
// Verificação de consistência entre contagem de triângulos e tamanho do arquivo
$expectedSize = 84 + ($triangleCount * 50);
$actualSize = filesize($filePath);
$tolerance = $expectedSize * 0.01;
if (abs($actualSize - $expectedSize) > $tolerance) {
    return ['valid' => false, 'message' => 'Estrutura de arquivo STL binário inválida'];
}
```

#### 3. Validação Especializada por Tipo de Arquivo
Para cada formato de arquivo (STL, OBJ, 3MF), foram implementadas verificações específicas que analisam a estrutura interna para garantir que o conteúdo corresponda ao formato declarado, mesmo quando o tipo MIME não é específico.

#### 4. Detecção de Conteúdo Malicioso
```php
// Verificação por padrões maliciosos em formatos que podem conter texto
$maliciousPatterns = [
    '/<script\b[^>]*>(.*?)<\/script>/is',
    '/eval\s*\(/is',
    '/exec\s*\(/is',
    // ... diversos outros padrões ...
];
```

#### 5. Verificação de Valores Numéricos Válidos
Para formatos binários, todos os valores de coordenadas são verificados para garantir que sejam números válidos (não NaN, não infinitos), evitando problemas ao processar o modelo:

```php
// Verificar se os valores são válidos (não NaN ou infinito)
if (is_finite($x) && is_finite($y) && is_finite($z) && 
    !is_nan($x) && !is_nan($y) && !is_nan($z)) {
    // Processamento seguro
}
```

## Uso Correto

### Exemplo Básico

```php
$uploadedFile = $_FILES['model3d_file'];

// Validar o arquivo com opção de validação estrita
$validator = new Model3DValidator();
$validationResult = $validator->validate($uploadedFile, [
    'strictValidation' => true,
    'maxSize' => 20 * 1024 * 1024 // 20MB limite
]);

if ($validationResult['valid']) {
    // Arquivo válido - processar o modelo 3D
    $metadata = $validationResult['data']['metadata'];
    
    // Exemplo: registrar metadados do modelo no banco de dados
    $modelId = $db->registerModel([
        'triangles' => $metadata['triangles'] ?? 0,
        'dimensions' => [
            'width' => $metadata['width'] ?? 0,
            'height' => $metadata['height'] ?? 0,
            'depth' => $metadata['depth'] ?? 0
        ],
        'format' => $metadata['format'] ?? $validationResult['data']['extension']
    ]);
    
    // Mover para localização permanente
    move_uploaded_file(
        $uploadedFile['tmp_name'], 
        "models/$modelId." . $validationResult['data']['extension']
    );
} else {
    // Arquivo inválido - registrar erro e notificar usuário
    error_log("Erro de validação de modelo 3D: " . $validationResult['message']);
    echo "O arquivo enviado não é um modelo 3D válido: " . htmlspecialchars($validationResult['message']);
}
```

### Exemplo de Integração com Controller

```php
use App\Lib\Security\Model3DValidator;
use App\Lib\Security\SecurityManager;

class ModelUploadController {
    use InputValidationTrait;
    
    public function uploadModel() {
        // Validar token CSRF
        $csrfToken = $this->postValidatedParam('csrf_token', 'string', ['required' => true]);
        if (!SecurityManager::validateCsrfToken($csrfToken)) {
            return $this->renderError('Erro de validação CSRF', 403);
        }
        
        // Validar arquivo enviado
        $file = $this->fileValidatedParam('model_file', [
            'required' => true,
            'allowedExtensions' => ['stl', 'obj', '3mf']
        ]);
        
        if (!$file) {
            return $this->renderError('Arquivo inválido ou não enviado', 400);
        }
        
        // Validar o modelo 3D
        $validator = new Model3DValidator();
        $validationResult = $validator->validate($file, [
            'strictValidation' => true
        ]);
        
        if (!$validationResult['valid']) {
            return $this->renderError('Modelo 3D inválido: ' . $validationResult['message'], 400);
        }
        
        // Processar o modelo validado
        $secureFilename = SecurityManager::generateSecureFilename(
            $file['name'], 
            $validationResult['data']['extension']
        );
        
        $modelStoragePath = APP_ROOT . '/uploads/models/' . $secureFilename;
        
        // Mover para localização permanente com permissões restritas
        if (move_uploaded_file($file['tmp_name'], $modelStoragePath)) {
            chmod($modelStoragePath, 0644);
            
            // Registrar no banco de dados
            $modelId = $this->modelRepository->createModel([
                'filename' => $secureFilename,
                'originalName' => $file['name'],
                'metadata' => json_encode($validationResult['data']['metadata']),
                'userId' => SecurityManager::getCurrentUserId()
            ]);
            
            return $this->renderSuccess([
                'message' => 'Modelo carregado com sucesso',
                'modelId' => $modelId
            ]);
        } else {
            return $this->renderError('Falha ao salvar o arquivo', 500);
        }
    }
}
```

## Testes de Segurança

### Teste 1: Identificação de STL Falso
**Descrição:** Upload de um executável (.exe) renomeado para .stl  
**Resultado:** Bloqueado pela verificação de assinatura de arquivo, identificado como tipo incorreto  
**Logs gerados:**
```
[ERROR] Validação de arquivo falhou: Assinatura de arquivo inválida para stl
Tipo MIME detectado: application/x-dosexec
```

### Teste 2: Detecção de XSS em Arquivos OBJ
**Descrição:** Upload de arquivo OBJ contendo script XSS embutido  
**Resultado:** Bloqueado pela verificação de conteúdo malicioso  
**Logs gerados:**
```
[ERROR] Falha na verificação de segurança: Detectado conteúdo potencialmente malicioso
Padrão detectado: <script>alert('XSS')</script>
```

### Teste 3: Resistência a Ataques de Zip Bomb em 3MF
**Descrição:** Upload de arquivo 3MF contendo arquivos ZIP aninhados recursivamente  
**Resultado:** Bloqueado pela validação estrutural de 3MF  
**Logs gerados:**
```
[ERROR] Validação de arquivo falhou: Estrutura 3MF inválida: falta arquivo 3dmodel.model
```

### Teste 4: Validação Contra Injeção de XML em 3MF
**Descrição:** Upload de arquivo 3MF contendo XXE (XML External Entity)  
**Resultado:** Bloqueado pela verificação de conteúdo malicioso  
**Logs gerados:**
```
[ERROR] Falha na verificação de segurança: Detectado conteúdo potencialmente malicioso
Padrão detectado: <!ENTITY xxe SYSTEM "file:///etc/passwd">
```

### Teste 5: Verificação de Integridade de STL Binário
**Descrição:** Upload de arquivo STL binário com contagem de triângulos inconsistente com o tamanho  
**Resultado:** Bloqueado pela verificação estrutural  
**Logs gerados:**
```
[ERROR] Validação de arquivo falhou: Tamanho de arquivo inconsistente com a contagem de triângulos
```

## Recomendações de Segurança Adicionais

1. **Quarentena de Arquivos:** Implementar um sistema de quarentena para arquivos recém-carregados, permitindo verificações adicionais antes da disponibilização.

2. **Limitação de Taxa:** Implementar limitação de taxa (rate limiting) para uploads de arquivos para mitigar ataques de DoS.

3. **Verificação de Malware:** Integrar o sistema com uma solução de verificação de malware para arquivos carregados.

4. **Monitoramento:** Implementar monitoramento especial para uploads de arquivos 3D, registrando e alertando sobre padrões suspeitos.

5. **Processamento Isolado:** Processar arquivos 3D em ambiente sandbox para limitar o impacto de possíveis explorações.

## Changelog
- **v1.1.0** (08/04/2025): Implementação de verificação de assinaturas de arquivo; validação estrutural aprimorada; mitigação de SEC-47
- **v1.0.0** (25/02/2025): Versão inicial com validação básica de formato e tipo MIME