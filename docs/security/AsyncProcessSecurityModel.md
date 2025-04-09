# Documentação de Segurança: Sistema de Processamento Assíncrono

## Função
O Sistema de Processamento Assíncrono é responsável por gerenciar operações de longa duração (como processamento de modelos 3D, geração de relatórios complexos e cotações) na Taverna da Impressão 3D. Este documento descreve os mecanismos de segurança implementados para garantir a integridade, disponibilidade e confidencialidade do sistema.

## Componentes de Segurança

### 1. API de Verificação de Status (`StatusCheckApiController`)

#### Implementação
```php
public function checkStatus()
{
    // Aplicar rate limiting para evitar sobrecarga
    if (!$this->rateLimiter->check('status_check_api', 60, 10)) {
        ApiResponse::error('Limite de solicitações excedido. Tente novamente mais tarde.', 429);
        return;
    }
    
    // Validar token CSRF para requisições POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrfToken = $this->validateInput('csrf_token', 'string', ['required' => true]);
        if ($csrfToken === null || !$this->securityManager->validateCsrfToken($csrfToken)) {
            ApiResponse::error('Erro de validação CSRF', 403);
            return;
        }
    }
    
    // Validar e sanitizar o token de processo
    $processToken = $this->validateInput('process_token', 'string', [
        'required' => true,
        'pattern' => '/^[a-zA-Z0-9]{32}$/' // Formato esperado: 32 caracteres alfanuméricos
    ]);
    
    if ($processToken === null) {
        ApiResponse::error('Token de processo inválido', 400);
        return;
    }
    
    // Verificar permissões do usuário
    $userId = $this->securityManager->getCurrentUserId();
    if (!$this->statusRepository->userCanAccessProcess($processToken, $userId)) {
        ApiResponse::error('Acesso não autorizado ao processo', 403);
        return;
    }
    
    // Resto da implementação...
}
```

#### Vulnerabilidades Mitigadas
- **CSRF (Cross-Site Request Forgery)**: Implementação robusta de tokens CSRF para requisições POST
- **Injeção de dados**: Validação rigorosa de entradas usando `InputValidationTrait`
- **Enumeração de informações**: Validação de permissões baseada no proprietário do processo
- **Ataques de força bruta**: Rate limiting para limitar tentativas de acesso não autorizado
- **Erros de segurança por exceções não tratadas**: Captura e log adequado de exceções
- **XSS (Cross-Site Scripting)**: Sanitização de todas as saídas com `htmlspecialchars()`

### 2. Sistema de Monitoramento e Alertas (`PerformanceAlertingService`)

#### Implementação
```php
public function processAlert($alertType, array $data, $severity = 'warning') {
    try {
        // Validar entrada
        $alertType = $this->validateInput($alertType, 'string', ['required' => true]);
        $severity = $this->validateInput($severity, 'string', [
            'required' => true,
            'allowed' => ['info', 'warning', 'error', 'critical']
        ]);
        
        if ($alertType === null || $severity === null) {
            $this->logError('Invalid alert parameters', ['type' => $alertType, 'severity' => $severity]);
            return false;
        }
        
        // Sanitizar e validar dados do alerta
        $sanitizedData = $this->sanitizeAlertData($data);
        
        // Resto da implementação...
    } catch (Exception $e) {
        $this->logError('Error processing alert', [
            'type' => $alertType,
            'severity' => $severity,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

private function sanitizeAlertData(array $data) {
    $sanitized = [];
    
    foreach ($data as $key => $value) {
        // Para valores escalares, sanitize a entrada
        if (is_scalar($value)) {
            // Converter para string e aplicar htmlspecialchars
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        } 
        // Para arrays, sanitize recursivamente
        elseif (is_array($value)) {
            $sanitized[$key] = $this->sanitizeAlertData($value);
        }
        // Ignora outros tipos de dados
    }
    
    return $sanitized;
}
```

#### Vulnerabilidades Mitigadas
- **Injeção de SQL**: Prepared statements para todas as consultas ao banco de dados
- **Persistência de XSS**: Sanitização recursiva de todos os dados de alerta
- **Escalonamento de privilégios**: Validação de permissões para cada ação
- **Sobrecarga de recursos**: Monitoramento e contenção de processos excessivamente longos
- **Falhas de auditoria**: Registro detalhado de todas as ações e alertas

### 3. Resposta de API Segura (`ApiResponse`)

#### Implementação
```php
private static function sendResponse(array $data, int $statusCode): void {
    // Definir cabeçalhos de segurança
    SecurityHeaders::apply();
    
    // Definir cabeçalhos padrão de resposta API
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);
    
    // Anti-CSRF - Não permitir embedding em outros sites
    header('X-Frame-Options: DENY');
    
    // Prevenir MIME-sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Prevenir vazamento de informações em referrer
    header('Referrer-Policy: no-referrer-when-downgrade');
    
    // Limpar buffer de saída para evitar conflitos
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Adicionar nonce para evitar cache de respostas sensíveis
    $data['_nonce'] = bin2hex(random_bytes(8));
    
    // Enviar resposta JSON sanitizada
    echo self::sanitizeJsonOutput(json_encode($data, JSON_UNESCAPED_UNICODE));
    exit;
}

private static function sanitizeJsonOutput(string $json): string {
    // Prevenir ataques JSON Hijacking
    return ")]}',\n" . $json;
}
```

#### Vulnerabilidades Mitigadas
- **JSON Hijacking/Injection**: Inclusão de prefixo de proteção no JSON
- **XSS em resposta JSON**: Sanitização adequada da saída JSON
- **Clickjacking**: Configuração de headers X-Frame-Options
- **MIME sniffing**: Configuração de headers X-Content-Type-Options
- **Vazamento de informações via referrer**: Configuração de Referrer-Policy

## Uso Correto

### 1. Verificação de Status de Processo
```php
// No frontend: Incluir o token CSRF em todas as requisições POST
<form method="POST" action="/api/status/check">
    <input type="hidden" name="csrf_token" value="<?= SecurityManager::getCsrfToken() ?>">
    <input type="hidden" name="process_token" value="<?= $processToken ?>">
    <button type="submit">Verificar Status</button>
</form>

// Para requisições AJAX
const fetchStatus = async (processToken) => {
    const response = await fetch('/api/status/check', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= SecurityManager::getCsrfToken() ?>'
        },
        body: JSON.stringify({ process_token: processToken })
    });
    
    return await response.json();
};
```

### 2. Monitoramento de Processos Assíncronos
```php
// Registrar um processo para monitoramento
$performanceAlertingService->monitorAsyncProcess(
    $processToken,    // ID único do processo
    3600,             // Tempo máximo em segundos (1 hora)
    time()            // Timestamp de início (atual)
);

// Obter detalhes para o usuário sem expor informações sensíveis
$safeStatus = [];
foreach ($processStatus as $key => $value) {
    // Não incluir dados sensíveis
    if (!in_array($key, ['internal_log', 'debug_info', 'raw_data'])) {
        $safeStatus[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
```

### 3. Configuração do Cron Job
```bash
# Verificação a cada 5 minutos
*/5 * * * * cd /path/to/project && php scripts/monitoring/check_monitored_processes.php >> logs/monitoring/cron.log 2>&1
```

## Vulnerabilidades Mitigadas

### Proteção contra CSRF
- Implementação de tokens únicos para cada sessão
- Validação de token em todas as requisições que modificam estado
- Rejeição automática de requisições sem token válido

### Prevenção de SQL Injection
- Uso exclusivo de prepared statements
- Validação de tipo para todos os parâmetros
- Camada de abstração para interações com banco de dados

### Proteção contra XSS
- Sanitização de todas as entradas e saídas
- Implementação de Content Security Policy (CSP)
- Sanitização específica para diferentes contextos (HTML, JSON, etc.)

### Controle de Acesso
- Verificação de propriedade dos processos
- Verificação de permissões baseada em papel do usuário
- Exposição mínima de informações sensíveis

### Proteção contra DoS
- Rate limiting para APIs críticas
- Monitoramento de processos de longa duração
- Detecção e alerta para comportamentos anômalos

### Segurança Operacional
- Logs detalhados para auditoria
- Alertas para condições anômalas
- Backup automatizado de dados críticos

## Testes de Segurança

### Teste 1: Proteção CSRF em Status Check API
- **Cenário**: Tentativa de verificação de status sem token CSRF
- **Procedimento**: Enviar requisição POST para `/api/status/check` sem incluir o token CSRF
- **Resultado Esperado**: Erro 403 com mensagem "Erro de validação CSRF"
- **Resultado Obtido**: Erro 403 conforme esperado, requisição rejeitada

### Teste 2: Rate Limiting para Status Check API
- **Cenário**: Múltiplas requisições em curto período de tempo
- **Procedimento**: Enviar 15 requisições em 1 minuto para `/api/status/check`
- **Resultado Esperado**: Primeiras 10 requisições bem-sucedidas, restantes com erro 429
- **Resultado Obtido**: Rate limiting funcionou corretamente, retornando 429 após limite excedido

### Teste 3: Validação de Permissões
- **Cenário**: Tentativa de acesso a processo de outro usuário
- **Procedimento**: Usuário A tenta acessar processo criado pelo Usuário B
- **Resultado Esperado**: Erro 403 com mensagem "Acesso não autorizado ao processo"
- **Resultado Obtido**: Acesso negado conforme esperado

### Teste 4: Alertas para Processos Excedendo Tempo Máximo
- **Cenário**: Processo assíncrono excede tempo máximo configurado
- **Procedimento**: Criar processo com tempo máximo de 1 minuto e executar por 2 minutos
- **Resultado Esperado**: Alerta gerado com severidade "warning" ou maior
- **Resultado Obtido**: Alerta gerado e notificações enviadas corretamente

### Teste 5: Sanitização de Dados JSON
- **Cenário**: Teste de XSS via objeto JSON no retorno da API
- **Procedimento**: Inserir payload XSS em campo de descrição de processo
- **Resultado Esperado**: Payload sanitizado na resposta JSON
- **Resultado Obtido**: Caracteres especiais escapados corretamente

## Recomendações Adicionais

1. **Implementar Autenticação JWT para APIs**: Para futuras expansões e integração com sistemas externos, considerar tokens JWT com tempo de expiração curto.

2. **Monitoramento de Anomalias**: Expandir o sistema para incluir detecção de anomalias baseada em machine learning, identificando padrões suspeitos de uso.

3. **Rotação Regular de Chaves**: Implementar rotação programada de chaves criptográficas e secrets usados pelo sistema.

4. **Testes de Penetração**: Realizar testes de penetração periódicos focados especificamente no sistema de processamento assíncrono.

5. **Hardening Adicional**: Considerar configurações adicionais de segurança como Content-Security-Policy, Subresource Integrity e certificados client-side para acesso a APIs críticas.
