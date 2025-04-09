# Guia de Integração das Otimizações do Sistema de Cotação

Este documento fornece instruções detalhadas para integração dos componentes otimizados do Sistema de Cotação Automatizada ao código existente da Taverna da Impressão 3D, seguindo rigorosamente os guardrails de segurança estabelecidos.

## 1. Pré-requisitos

Antes de iniciar a integração, verifique se os seguintes componentes estão presentes e funcionais:

- `ModelComplexityAnalyzer.php` (classe base para análise de modelos)
- `QuotationCalculator.php` (cálculo de preços e estimativas)
- `QuotationManager.php` (gerenciamento central de cotações)

## 2. Estrutura de Diretórios e Configuração

### 2.1 Criar Diretórios de Cache

Execute os seguintes comandos para criar os diretórios necessários:

```bash
# Criar diretório principal de cache
mkdir -p C:\MCP\taverna\taverna-impressao-ecommerce\cache

# Criar subdiretório para cotações
mkdir -p C:\MCP\taverna\taverna-impressao-ecommerce\cache\quotation

# Configurar permissões adequadas (em ambiente Linux/Unix)
# chmod 755 C:\MCP\taverna\taverna-impressao-ecommerce\cache
# chmod 755 C:\MCP\taverna\taverna-impressao-ecommerce\cache\quotation
```

### 2.2 Configuração de Cache

Adicione as seguintes configurações ao arquivo `C:\MCP\taverna\taverna-impressao-ecommerce\config\app.php`:

```php
// Configurações de cache para sistema de cotação
'cache' => [
    'quotation' => [
        'dir' => dirname(__DIR__) . '/cache/quotation',
        'memory_size' => 50,         // Tamanho do cache em memória
        'default_ttl' => 3600,       // TTL padrão em segundos (1 hora)
        'purge_interval' => 86400,   // Intervalo de limpeza (24 horas)
        'key_prefix' => 'quotation_' // Prefixo para chaves de cache
    ]
]
```

## 3. Integração do Sistema de Cache

### 3.1 Inicialização no QuotationManager

Atualize o construtor da classe `QuotationManager` para incluir o cache:

```php
<?php
// app/lib/Analysis/QuotationManager.php

// Adicionar use statement
use App\Lib\Analysis\QuotationCache;

class QuotationManager {
    /** @var ModelComplexityAnalyzer */
    private $analyzer;
    
    /** @var QuotationCalculator */
    private $calculator;
    
    /** @var QuotationCache */
    private $cache;
    
    public function __construct(ModelComplexityAnalyzer $analyzer, QuotationCalculator $calculator) {
        $this->analyzer = $analyzer;
        $this->calculator = $calculator;
        
        // Inicializar cache
        $this->cache = QuotationCache::getInstance();
    }
    
    // Métodos existentes...
}
```

### 3.2 Implementação do Fluxo com Cache

Modifique o método `generateQuotation()` para incorporar o cache:

```php
/**
 * Gera cotação para um modelo com cache
 * 
 * @param array $params Parâmetros de cotação
 * @return array Resultados da cotação
 */
public function generateQuotation(array $params): array {
    // Validar entradas (segurança)
    $this->validateQuotationParams($params);
    
    try {
        // Gerar chave de cache
        $cacheKey = $this->cache->generateKey($params);
        
        // Verificar cache
        $cachedResult = $this->cache->get($cacheKey);
        if ($cachedResult !== null) {
            // Registrar hit no cache em log para análise
            $this->logger->info('Cache hit para cotação', [
                'key' => $cacheKey,
                'params' => $this->sanitizeLogData($params)
            ]);
            
            return $cachedResult;
        }
        
        // Cache miss - realizar análise
        $this->logger->info('Cache miss para cotação', [
            'key' => $cacheKey,
            'params' => $this->sanitizeLogData($params)
        ]);
        
        // Realizar análise de complexidade
        $analysisResult = $this->performAnalysis($params);
        
        // Calcular cotação
        $quotation = $this->calculator->calculate($analysisResult, $params);
        
        // Construir resultado
        $result = [
            'quotation' => $quotation,
            'analysis' => $analysisResult,
            'timestamp' => time()
        ];
        
        // Calcular TTL adaptativo baseado na complexidade
        $ttl = $this->cache->calculateAdaptiveTtl(
            $cacheKey, 
            $analysisResult['complexity_score']
        );
        
        // Armazenar no cache
        $this->cache->set($cacheKey, $result, $ttl);
        
        return $result;
    } catch (\Exception $e) {
        // Registrar erro detalhado (apenas log interno)
        $this->logger->error('Erro ao gerar cotação', [
            'message' => $e->getMessage(),
            'params' => $this->sanitizeLogData($params),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Retornar erro genérico para o usuário (segurança)
        throw new \Exception('Não foi possível processar a cotação. Por favor, tente novamente.');
    }
}

/**
 * Sanitiza dados para log (segurança)
 * 
 * @param array $data Dados a sanitizar
 * @return array Dados sanitizados
 */
private function sanitizeLogData(array $data): array {
    // Criar cópia para evitar modificar o original
    $sanitized = [];
    
    // Copiar apenas campos seguros, remover dados sensíveis
    $safeFields = ['model_id', 'file_type', 'material', 'quality'];
    
    foreach ($safeFields as $field) {
        if (isset($data[$field])) {
            $sanitized[$field] = $data[$field];
        }
    }
    
    // Substituir caminhos absolutos por relativos (segurança)
    if (isset($data['file_path'])) {
        $sanitized['file_path'] = '[FILE_PATH_REDACTED]';
    }
    
    return $sanitized;
}
```

### 3.3 Método de Análise Otimizada

Adicione o método `performAnalysis()` utilizando o otimizador:

```php
/**
 * Realiza análise otimizada de complexidade
 * 
 * @param array $params Parâmetros de cotação
 * @return array Resultados da análise
 */
private function performAnalysis(array $params): array {
    // Criar otimizador sob demanda
    $optimizer = new ModelAnalysisOptimizer($this->analyzer);
    
    // Configurar otimizador com base no tipo de modelo
    if (isset($params['file_type'])) {
        switch (strtolower($params['file_type'])) {
            case 'stl':
                // Configuração para STL
                $optimizer->setBatchSize(10000);
                $optimizer->setPrecisionLevel(0.95);
                break;
                
            case 'obj':
                // Configuração para OBJ (mais complexo)
                $optimizer->setBatchSize(5000);
                $optimizer->setPrecisionLevel(0.90);
                break;
                
            case '3mf':
                // Configuração para 3MF (formato complexo)
                $optimizer->setBatchSize(2000);
                $optimizer->setPrecisionLevel(0.85);
                break;
        }
    }
    
    // Executar análise otimizada com monitoramento de performance
    $result = $optimizer->analyzeModelOptimized($params, true);
    
    // Registrar métricas de performance para monitoramento
    $this->recordPerformanceMetrics($optimizer->getPerformanceMetrics());
    
    return $result;
}

/**
 * Registra métricas de performance para análise
 * 
 * @param array $metrics Métricas coletadas
 */
private function recordPerformanceMetrics(array $metrics): void {
    // Registrar métricas em log estruturado
    $this->logger->info('Métricas de performance da análise', [
        'duration_seconds' => $metrics['duration_seconds'] ?? 0,
        'memory_used_mb' => $metrics['memory_used_mb'] ?? 0,
        'processed_triangles' => $metrics['processed_triangles'] ?? 0,
        'total_triangles' => $metrics['total_triangles'] ?? 0,
        'early_stopped' => $metrics['early_stopped'] ?? false,
        'batches_processed' => $metrics['batches_processed'] ?? 0
    ]);
    
    // TODO: Integrar com sistema de métricas central na Fase 3
}
```

## 4. Integração com Controllers

### 4.1 Atualização do CustomerQuotationController

Modifique o controller para utilizar o QuotationManager otimizado:

```php
<?php
// app/controllers/CustomerQuotationController.php

use App\Lib\Validation\InputValidationTrait;

class CustomerQuotationController {
    use InputValidationTrait;
    
    /** @var QuotationManager */
    private $quotationManager;
    
    /**
     * Endpoint para cotação rápida com modelo existente
     */
    public function quickQuote() {
        // Validar token CSRF (segurança)
        if (!SecurityManager::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->handleError('Erro de validação de formulário', 403);
            return;
        }
        
        // Validar entrada do usuário (segurança)
        $modelId = $this->validateInput('model_id', 'integer', ['required' => true]);
        $material = $this->validateInput('material', 'string', ['required' => true]);
        $quality = $this->validateInput('quality', 'string', ['required' => true]);
        
        if ($modelId === null || $material === null || $quality === null) {
            $this->handleError('Parâmetros inválidos', 400);
            return;
        }
        
        try {
            // Construir parâmetros
            $params = [
                'model_id' => $modelId,
                'material' => $material,
                'quality' => $quality
            ];
            
            // Gerar cotação usando o sistema otimizado
            $result = $this->quotationManager->generateQuotation($params);
            
            // Renderizar resposta
            $this->renderJson([
                'success' => true,
                'quotation' => [
                    'estimated_time' => $result['quotation']['estimated_print_time_minutes'],
                    'material_cost' => $result['quotation']['material_cost'],
                    'printing_cost' => $result['quotation']['printing_cost'],
                    'total_cost' => $result['quotation']['total_cost'],
                    'currency' => 'BRL'
                ],
                'complexity' => round($result['analysis']['complexity_score'], 1)
            ]);
        } catch (\Exception $e) {
            // Erro genérico para o usuário (segurança)
            $this->handleError('Não foi possível gerar a cotação', 500);
            
            // Log detalhado do erro (apenas interno)
            $this->logger->error('Erro na cotação rápida', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'params' => [
                    'model_id' => $modelId,
                    'material' => $material,
                    'quality' => $quality
                ]
            ]);
        }
    }
    
    /**
     * Endpoint para cotação com upload de modelo
     */
    public function uploadAndQuote() {
        // Validar token CSRF (segurança)
        if (!SecurityManager::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->handleError('Erro de validação de formulário', 403);
            return;
        }
        
        // Processar upload com validação (segurança)
        $uploadResult = $this->processValidatedFileUpload('model_file', [
            'allowedTypes' => ['stl', 'obj', '3mf'],
            'maxSize' => 50 * 1024 * 1024, // 50MB
            'destination' => 'uploads/temp_models'
        ]);
        
        if (!$uploadResult['success']) {
            $this->handleError($uploadResult['message'], 400);
            return;
        }
        
        $filePath = $uploadResult['path'];
        $fileType = $uploadResult['extension'];
        
        // Validar outros parâmetros
        $material = $this->validateInput('material', 'string', ['required' => true]);
        $quality = $this->validateInput('quality', 'string', ['required' => true]);
        
        if ($material === null || $quality === null) {
            // Remover arquivo temporário (segurança/limpeza)
            @unlink($filePath);
            $this->handleError('Parâmetros inválidos', 400);
            return;
        }
        
        try {
            // Construir parâmetros
            $params = [
                'file_path' => $filePath,
                'file_type' => $fileType,
                'material' => $material,
                'quality' => $quality
            ];
            
            // Gerar cotação usando o sistema otimizado
            $result = $this->quotationManager->generateQuotation($params);
            
            // Remover arquivo temporário após uso (segurança/limpeza)
            @unlink($filePath);
            
            // Renderizar resposta
            $this->renderJson([
                'success' => true,
                'quotation' => [
                    'estimated_time' => $result['quotation']['estimated_print_time_minutes'],
                    'material_cost' => $result['quotation']['material_cost'],
                    'printing_cost' => $result['quotation']['printing_cost'],
                    'total_cost' => $result['quotation']['total_cost'],
                    'currency' => 'BRL'
                ],
                'complexity' => round($result['analysis']['complexity_score'], 1),
                'model_stats' => [
                    'polygon_count' => $result['analysis']['metrics']['polygon_count'],
                    'dimensions' => $result['analysis']['metrics']['dimensions']
                ]
            ]);
        } catch (\Exception $e) {
            // Remover arquivo temporário em caso de erro (segurança/limpeza)
            @unlink($filePath);
            
            // Erro genérico para o usuário (segurança)
            $this->handleError('Não foi possível gerar a cotação', 500);
            
            // Log detalhado do erro (apenas interno)
            $this->logger->error('Erro na cotação com upload', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file_type' => $fileType,
                'material' => $material,
                'quality' => $quality
            ]);
        }
    }
    
    /**
     * Manipula erro e retorna resposta apropriada
     * 
     * @param string $message Mensagem de erro
     * @param int $code Código HTTP
     */
    private function handleError(string $message, int $code = 400): void {
        http_response_code($code);
        $this->renderJson([
            'success' => false,
            'error' => $message
        ]);
    }
    
    /**
     * Renderiza resposta JSON com headers de segurança
     * 
     * @param array $data Dados a serem renderizados
     */
    private function renderJson(array $data): void {
        // Aplicar headers de segurança
        SecurityHeaders::applySecureHeaders();
        
        // Configurar header de conteúdo
        header('Content-Type: application/json; charset=utf-8');
        
        // Codificar resposta (com controle de erros)
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($json === false) {
            // Fallback em caso de erro de codificação
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao processar resposta'
            ]);
            return;
        }
        
        echo $json;
    }
}
```

## 5. Testes de Integração

### 5.1 Script de Teste de Cache

Crie um script de teste para validar a integração:

```php
<?php
// tests/integration/QuotationCacheTest.php

require_once __DIR__ . '/../../config/bootstrap.php';

use App\Lib\Analysis\QuotationCache;
use App\Lib\Analysis\ModelComplexityAnalyzer;
use App\Lib\Analysis\Optimizer\ModelAnalysisOptimizer;

// Teste de cache e otimizador
echo "Testando integração de cache e otimização...\n";

// Inicializar componentes
$analyzer = new ModelComplexityAnalyzer();
$optimizer = new ModelAnalysisOptimizer($analyzer);
$cache = QuotationCache::getInstance();

// Modelo de teste
$testModel = [
    'file_path' => __DIR__ . '/../test_data/sample_models/small/model_small_1.stl',
    'file_type' => 'stl',
    'material' => 'PLA',
    'quality' => 'medium'
];

// Gerar chave de cache
$key = $cache->generateKey($testModel);
echo "Chave de cache gerada: {$key}\n";

// Verificar cache inicial (deve ser miss)
$cachedResult = $cache->get($key);
if ($cachedResult === null) {
    echo "Cache miss (esperado para primeira execução)\n";
    
    // Executar análise otimizada
    echo "Executando análise otimizada...\n";
    $start = microtime(true);
    $result = $optimizer->analyzeModelOptimized($testModel, true);
    $duration = microtime(true) - $start;
    
    echo "Análise concluída em " . round($duration, 2) . " segundos\n";
    echo "Complexidade calculada: " . $result['complexity_score'] . "\n";
    echo "Polígonos: " . $result['metrics']['polygon_count'] . "\n";
    
    // Armazenar no cache
    echo "Armazenando resultado no cache...\n";
    $ttl = $cache->calculateAdaptiveTtl($key, $result['complexity_score']);
    $cache->set($key, $result, $ttl);
    
    // Verificar armazenamento
    $cachedResult = $cache->get($key);
    if ($cachedResult !== null) {
        echo "Cache hit após armazenamento (sucesso)\n";
    } else {
        echo "ERRO: Falha ao armazenar no cache\n";
    }
    
    // Segunda execução (deve usar cache)
    echo "\nExecutando segunda análise (deve usar cache)...\n";
    $start = microtime(true);
    $cachedResult = $cache->get($key);
    $duration = microtime(true) - $start;
    
    if ($cachedResult !== null) {
        echo "Cache hit (sucesso)\n";
        echo "Recuperado do cache em " . round($duration * 1000, 2) . " ms\n";
        echo "Complexidade recuperada: " . $cachedResult['complexity_score'] . "\n";
    } else {
        echo "ERRO: Cache miss inesperado na segunda execução\n";
    }
} else {
    echo "Cache já contém resultado para este modelo\n";
    echo "Complexidade em cache: " . $cachedResult['complexity_score'] . "\n";
    
    // Limpar cache para teste completo
    echo "Limpando cache para teste completo...\n";
    $cache->invalidate($key);
    
    // Verificar limpeza
    $cachedResult = $cache->get($key);
    if ($cachedResult === null) {
        echo "Cache limpo com sucesso\n";
        echo "Execute o teste novamente para ciclo completo\n";
    } else {
        echo "ERRO: Falha ao limpar cache\n";
    }
}

// Estatísticas de cache
$stats = $cache->getStats();
echo "\nEstatísticas de cache:\n";
echo "Total de requisições: {$stats['total_requests']}\n";
echo "Hit ratio: {$stats['hit_ratio']}%\n";
echo "Hits em memória: {$stats['memory_hits']}\n";
echo "Hits em disco: {$stats['disk_hits']}\n";
echo "Misses: {$stats['misses']}\n";
echo "Escritas: {$stats['writes']}\n";
echo "Erros: {$stats['errors']}\n";

echo "\nTeste concluído.\n";
```

### 5.2 Execução do Teste

Execute o teste de integração para validar a implementação:

```bash
php tests/integration/QuotationCacheTest.php
```

## 6. Considerações de Segurança

### 6.1 Validação de Entradas

Sempre utilize `InputValidationTrait` para validar todas as entradas:

```php
// Exemplo de validação de parâmetros de cotação
private function validateQuotationParams(array $params): void {
    // Validar model_id OU file_path
    if (isset($params['model_id'])) {
        if (!is_int($params['model_id']) && !is_string($params['model_id'])) {
            throw new \InvalidArgumentException('model_id deve ser um inteiro ou string');
        }
    } else if (isset($params['file_path'])) {
        if (!is_string($params['file_path']) || !file_exists($params['file_path'])) {
            throw new \InvalidArgumentException('file_path inválido ou não encontrado');
        }
        
        // Validar extensão de arquivo
        $extension = strtolower(pathinfo($params['file_path'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['stl', 'obj', '3mf', 'gcode'])) {
            throw new \InvalidArgumentException('Tipo de arquivo não suportado: ' . $extension);
        }
    } else {
        throw new \InvalidArgumentException('model_id ou file_path deve ser fornecido');
    }
    
    // Validar outros parâmetros
    if (isset($params['material']) && !is_string($params['material'])) {
        throw new \InvalidArgumentException('material deve ser uma string');
    }
    
    if (isset($params['quality']) && !is_string($params['quality'])) {
        throw new \InvalidArgumentException('quality deve ser uma string');
    }
}
```

### 6.2 Proteção CSRF

Sempre valide tokens CSRF em todas as requisições que modificam estado:

```php
// Exemplo em formulário
<form method="POST" action="/quotation/upload">
    <input type="hidden" name="csrf_token" value="<?= SecurityManager::getCsrfToken() ?>">
    <!-- Outros campos do formulário -->
</form>

// Exemplo em controller
if (!SecurityManager::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    // Rejeitar requisição
    http_response_code(403);
    echo "Erro de validação de formulário";
    return;
}
```

### 6.3 Limpeza de Arquivos Temporários

Sempre remova arquivos temporários após uso:

```php
// Após processamento de arquivo enviado
try {
    // Processar arquivo
    $result = processUploadedFile($filePath);
    
    // Remover após processamento bem-sucedido
    @unlink($filePath);
    
    // Retornar resultado
    return $result;
} catch (\Exception $e) {
    // Remover em caso de erro
    @unlink($filePath);
    throw $e;
}
```

### 6.4 Sanitização de Logs

Nunca inclua dados sensíveis em logs:

```php
private function sanitizeLogData(array $data): array {
    // Remover dados sensíveis
    $sanitized = $data;
    
    // Remover caminhos completos
    if (isset($sanitized['file_path'])) {
        $sanitized['file_path'] = basename($sanitized['file_path']);
    }
    
    // Remover outros dados sensíveis
    unset($sanitized['user_ip']);
    unset($sanitized['session_id']);
    
    return $sanitized;
}
```

## 7. Verificação Pós-Integração

Após completar a integração, execute a seguinte lista de verificação:

- [ ] Diretórios de cache criados e com permissões corretas
- [ ] Validação de entrada implementada em todos os pontos de entrada
- [ ] Proteção CSRF em todas as requisições POST
- [ ] Limpeza adequada de arquivos temporários
- [ ] Logs apropriados para monitoramento
- [ ] Tratamento adequado de erros sem exposição de detalhes sensíveis
- [ ] Verificação de performance com e sem cache
- [ ] Monitoramento de uso de recursos (CPU, memória)

## 8. Troubleshooting

### 8.1 Problemas Comuns e Soluções

| Problema | Possível Causa | Solução |
|----------|----------------|---------|
| Erros de permissão no cache | Diretório sem permissão de escrita | Verificar permissões do diretório `cache/quotation` |
| Cache miss inesperado | TTL muito curto ou limpeza automática | Aumentar TTL ou verificar `purge_interval` |
| Erro de memória em modelos grandes | Tamanho de lote inadequado | Reduzir `batchSize` no ModelAnalysisOptimizer |
| Performance inconsistente | Configuração inadequada para formato | Ajustar parâmetros de otimização por tipo de arquivo |
| Alto uso de disco | Cache não está sendo limpo | Verificar tarefa de limpeza periódica |

### 8.2 Logs de Diagnóstico

Ative logs detalhados temporariamente para diagnosticar problemas:

```php
// Ativar logs detalhados em QuotationCache
public function enableDetailedLogging(): void {
    $this->detailedLogging = true;
}

// Método interno para log detalhado
private function logDetailed(string $message, array $context = []): void {
    if ($this->detailedLogging) {
        $this->logger->debug('[QuotationCache] ' . $message, $context);
    }
}
```

---

Este guia fornece as instruções necessárias para integrar os componentes otimizados do Sistema de Cotação Automatizada à aplicação existente. Ao seguir estas diretrizes, você garantirá uma integração segura e eficiente que aproveita todas as otimizações implementadas na Fase 1.
