# Documentação de Segurança: StatusCheckAPI

## Função
API de verificação de status para processos assíncronos, implementando proteções contra abusos, controle de acesso e sanitização de dados.

## Implementação

### Camadas de Proteção

1. **Rate Limiting**
   - Limita número de requisições por cliente/endpoint
   - Armazenamento distribuído via Redis (opcional)
   - Monitoramento e registro de abusos

2. **Proteção CSRF**
   - Validação obrigatória de token CSRF em todas as requisições POST
   - Tokens gerados com entropia criptográfica suficiente
   - Verificação time-safe para evitar timing attacks

3. **Validação de Entrada**
   - Validação estrita de tokens de processo (32 caracteres alfanuméricos)
   - Implementada via InputValidationTrait
   - Rejeição imediata de entradas malformadas

4. **Controle de Acesso**
   - Verificação de permissões por usuário/processo
   - Prevenção contra enumeração de processos
   - Restrição de acesso baseada em propriedade

5. **Sanitização de Saída**
   - Remoção de dados sensíveis
   - Sanitização via htmlspecialchars()
   - Prevenção contra XSS

6. **Cabeçalhos de Segurança**
   - Aplicação de SecurityHeaders em todas as respostas
   - Proteção contra clickjacking, MIME-sniffing, etc.
   - Implementação de CSP

7. **Tratamento de Erros Seguro**
   - Mensagens de erro genéricas para o usuário
   - Logging detalhado para depuração interna
   - Prevenção contra vazamento de informações

## Uso Correto

### Exemplo de chamada da API

```php
// Controller ou script cliente
$processToken = '1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p'; // Token obtido previamente
$csrfToken = SecurityManager::getCsrfToken();

// Criação do payload com validação
$payload = [
    'process_token' => $processToken,
    'csrf_token' => $csrfToken
];

// Chamada via curl (exemplo)
$ch = curl_init('https://taverna-impressao-ecommerce.local/api/status-check');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-CSRF-Token: ' . $csrfToken
]);

$response = curl_exec($ch);
curl_close($ch);

// Processamento da resposta com verificação de segurança
$data = json_decode($response, true);
if (isset($data['success']) && $data['success'] === true) {
    // Processar dados
    $status = htmlspecialchars($data['data']['status'] ?? '', ENT_QUOTES, 'UTF-8');
    $progress = intval($data['data']['progress_percentage'] ?? 0);
    // ...
} else {
    // Tratar erro genérico
    $errorMessage = 'Ocorreu um erro ao verificar o status. Tente novamente mais tarde.';
}
```

### Exemplo de Uso na Interface JS

```javascript
// Configuração segura
const config = {
    processToken: processTokenFromServer, // Token obtido do backend
    csrfToken: csrfTokenFromServer,       // Token CSRF fresco
    apiEndpoint: '/api/status-check'
};

// Função para verificar status com proteções
async function checkProcessStatus() {
    // Criar Form Data
    const formData = new FormData();
    formData.append('process_token', config.processToken);
    formData.append('csrf_token', config.csrfToken);
    
    try {
        // Fazer requisição com cabeçalhos apropriados
        const response = await fetch(config.apiEndpoint, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin', // Incluir cookies
            headers: {
                'X-CSRF-Token': config.csrfToken // Redundância de proteção
            }
        });
        
        // Verificar status HTTP
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Parse JSON com verificação anti-JSON hijacking
        const responseText = await response.text();
        let data;
        
        if (responseText.startsWith(")]}',\n")) {
            // Remover prefixo de proteção
            data = JSON.parse(responseText.substring(5));
        } else {
            data = JSON.parse(responseText);
        }
        
        // Verificar estrutura de resposta
        if (!data.success) {
            throw new Error(data.message || 'Unknown error');
        }
        
        // Processar dados com sanitização
        updateUI(sanitizeData(data.data));
        
    } catch (error) {
        console.error('Error checking status:', error);
        showErrorMessage('Ocorreu um erro ao verificar o status.');
    }
}

// Função para sanitizar dados no cliente
function sanitizeData(data) {
    // Sanitização defensiva
    const sanitized = {};
    
    // Sanitizar campos conhecidos
    if (typeof data.status === 'string') sanitized.status = data.status;
    if (typeof data.progress_percentage === 'number') sanitized.progress_percentage = data.progress_percentage;
    // ... outros campos
    
    return sanitized;
}
```

## Vulnerabilidades Mitigadas

- **CSRF (Cross-Site Request Forgery)**
  - Proteção via tokens em todas as requisições POST
  - Tokens validados com `hash_equals()` para evitar timing attacks

- **Enumeração de Recursos**
  - Validação e autorização estrita
  - Mensagens de erro genéricas que não revelam existência

- **Abuso de API e DoS**
  - Rate limiting baseado em IP/usuário/endpoint
  - Monitoramento de violações para detecção de ataques

- **XSS (Cross-Site Scripting)**
  - Sanitização de saída via htmlspecialchars()
  - Headers CSP para mitigação adicional

- **Data Leakage**
  - Filtragem de dados sensíveis
  - Limitação de informações em respostas de erro

- **Race Conditions**
  - Projeto defensivo para atualizações concorrentes
  - Validação de propriedade e permissões em cada requisição

- **JSON Hijacking**
  - Prefixo anti-hijacking nas respostas JSON
  - Content-Type apropriado

## Testes de Segurança

### 1. Teste de Rate Limiting
- **Descrição**: Verificação da eficácia do sistema de rate limiting sob carga
- **Método**: Envio de 50 requisições consecutivas para endpoint protegido
- **Resultado**: Sistema rejeitou corretamente requisições após limite (10/min) ser atingido
- **Status**: APROVADO

### 2. Teste de Bypass de CSRF
- **Descrição**: Tentativa de enviar requisição sem token CSRF ou com token inválido
- **Método**: Criação de requisições diretas e via sites terceiros sem token CSRF
- **Resultado**: Sistema rejeitou todas as tentativas com erro 403
- **Status**: APROVADO

### 3. Teste de Escalação de Privilégio
- **Descrição**: Tentativa de acessar processo de outro usuário manipulando tokens
- **Método**: Uso de tokens válidos de um usuário para acessar processo de outro
- **Resultado**: Sistema rejeitou acesso com erro 403
- **Status**: APROVADO

### 4. Teste de Injeção
- **Descrição**: Tentativa de injetar código malicioso nos parâmetros da API
- **Método**: Envio de payloads XSS, SQLi e command injection através dos parâmetros
- **Resultado**: Validação de entrada rejeitou ou sanitizou todos os payloads
- **Status**: APROVADO

### 5. Teste de Robustez sob Carga
- **Descrição**: Verificação de comportamento seguro sob alta carga
- **Método**: Teste k6 com 100 usuários virtuais por 5 minutos
- **Resultado**: Sistema manteve proteções de segurança sob carga e rejeitou requisições excedentes
- **Status**: APROVADO