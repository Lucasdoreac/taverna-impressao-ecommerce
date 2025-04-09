# Documentação de Segurança: PayPalGateway

## Função
Componente responsável pela integração segura com a API do PayPal, gerenciando transações de pagamento com validação robusta, sanitização de dados e proteção contra vulnerabilidades comuns.

## Vetores de Ameaça Mitigados

| Vetor | Estratégia de Mitigação | Implementação |
|-------|-------------------------|---------------|
| Manipulação de Parâmetros | Validação Estrita | Validação de tipo e formato para todos os parâmetros de entrada |
| Injeção de Dados | Sanitização | Uso de `SecurityManager::sanitize()` para todos os dados expostos em logs ou banco de dados |
| Sequestro de Sessão | Tokens de Segurança | Proteção CSRF em todas as operações iniciadas pelo browser |
| Ataques de Timing | Verificação Time-Safe | Uso de funções `hash_equals()` para comparação de tokens e assinaturas |
| Dados Sensíveis | Isolamento de Credenciais | Armazenamento seguro das credenciais API, nunca expostas ao cliente |
| Callbacks Falsificados | Verificação de Assinatura | Validação criptográfica da autenticidade de webhooks/IPNs |
| Man-in-the-Middle | TLS | Comunicações sempre via HTTPS com verificação de certificados |
| Vulnerabilidades em Dependências | Sandbox | Processamento realizado em ambiente de sandbox durante desenvolvimento |

## Implementação

### Autenticação Segura com API PayPal

```php
/**
 * Garante que existe um token de acesso válido
 * 
 * @return void
 * @throws \Exception Se não for possível obter token
 */
protected function ensureAccessToken(): void {
    // Verificar se token existente ainda é válido
    $now = time();
    
    if ($this->accessToken && $this->tokenExpires > $now + 60) {
        // Token ainda válido
        return;
    }
    
    // Obter novo token
    $this->accessToken = null;
    
    // Credenciais
    $clientId = $this->config['client_id'];
    $clientSecret = $this->config['client_secret'];
    
    // Base URL
    $tokenUrl = $this->isSandbox ? 
        'https://api-m.sandbox.paypal.com/v1/oauth2/token' :
        'https://api-m.paypal.com/v1/oauth2/token';
    
    // Configurar curl com práticas seguras
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $tokenUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $clientId . ':' . $clientSecret,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Accept-Language: en_US'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,  // Verificação SSL para evitar MITM
        CURLOPT_SSL_VERIFYHOST => 2      // Verificação estrita de certificado
    ]);
    
    // Processamento da resposta com verificação de erros
    // ...
}
```

### Verificação de Webhook

```php
/**
 * Verifica a autenticidade de uma notificação IPN do PayPal
 * 
 * @param array $ipnData Dados recebidos na notificação
 * @return bool Resultado da verificação
 */
private function verifyPayPalIPN(array $ipnData): bool {
    // Verificação segura para garantir que a notificação veio realmente do PayPal
    
    // Determinar endpoint para verificação
    $isSandbox = $this->gatewayConfigs['paypal']['sandbox'] ?? true;
    $verifyEndpoint = $isSandbox ? 
        'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr' : 
        'https://ipnpb.paypal.com/cgi-bin/webscr';
    
    // Preparar dados para verificação, sem alterações
    $verifyData = 'cmd=_notify-validate&' . http_build_query($ipnData);
    
    // Configurar curl com práticas seguras
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $verifyEndpoint,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $verifyData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => [
            'Connection: close',
            'User-Agent: Taverna-da-Impressao-3D/1.0'
        ],
        CURLOPT_SSL_VERIFYPEER => true,  // Obrigatório para segurança
        CURLOPT_SSL_VERIFYHOST => 2,     // Verificação estrita
        CURLOPT_CONNECTTIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Verificação da resposta
    if ($response === 'VERIFIED') {
        return true;
    } else {
        error_log("Verificação de IPN falhou. Resposta: {$response}");
        return false;
    }
}
```

### Proteção de Dados Sensíveis

```php
/**
 * Remove dados sensíveis de arrays para logs
 * 
 * @param array $data Dados a serem higienizados
 * @return array Dados sem informações sensíveis
 */
protected function removeSensitiveData(array $data): array {
    $sensitiveKeys = [
        'card_number', 'cvv', 'cvc', 'security_code', 'password', 'secret',
        'token', 'access_token', 'api_key', 'private_key', 'authorization'
    ];
    
    $result = [];
    
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $result[$key] = $this->removeSensitiveData($value);
        } else {
            // Verificar se a chave contém alguma das palavras sensíveis
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (stripos($key, $sensitiveKey) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            if ($isSensitive) {
                // Mascarar dado sensível
                $result[$key] = '******';
            } else {
                $result[$key] = $value;
            }
        }
    }
    
    return $result;
}
```

### Validação Robusta de Entradas

```php
/**
 * Valida dados do pedido conforme requisitos do gateway
 * 
 * @param array $orderData Dados do pedido a serem validados
 * @return bool Se dados são válidos
 * @throws \Exception Se dados forem inválidos
 */
protected function validateOrderData(array $orderData): bool {
    // Verificar campos obrigatórios
    $requiredFields = ['id', 'order_number', 'total'];
    foreach ($requiredFields as $field) {
        if (!isset($orderData[$field]) || empty($orderData[$field])) {
            throw new \Exception("Campo obrigatório ausente nos dados do pedido: {$field}");
        }
    }
    
    // Validar formatos
    if (!is_numeric($orderData['total']) || (float)$orderData['total'] <= 0) {
        throw new \Exception("Valor total inválido");
    }
    
    if (!preg_match('/^[a-zA-Z0-9-]{1,64}$/', $orderData['order_number'])) {
        throw new \Exception("Formato de número de pedido inválido");
    }
    
    // Sanitizar dados
    foreach ($orderData as $key => $value) {
        if (is_string($value)) {
            $orderData[$key] = SecurityManager::sanitize($value);
        }
    }
    
    return true;
}
```

## Uso Correto

### Frontend (Iniciando Pagamento)

```php
// No controller
$paypalConfig = $gateway->getFrontendConfig();
$csrf_token = CsrfProtection::getToken();

// No JavaScript (client-side)
paypal.Buttons({
    createOrder: function(data, actions) {
        return fetch('/payment/create-paypal-order', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken // Token CSRF para proteção
            },
            body: JSON.stringify({
                order_id: orderId,
                payment_method: 'paypal'
            })
        })
        .then(function(response) { 
            // Processamento da resposta
            // ...
        });
    }
}).render('#paypal-button-container');
```

### Processamento de Callbacks (Backend)

```php
// No controller de webhook/callback
public function paypalIPN() {
    try {
        // Capturar dados brutos do POST para processamento
        $ipnData = $_POST;
        
        // Verificar autenticidade com PayPal (segurança crítica)
        $verificationResult = $this->paymentManager->verifyPayPalIPN($ipnData);
        
        if (!$verificationResult) {
            throw new \Exception("Verificação de IPN falhou");
        }
        
        // Processamento seguro após verificação
        // ...
    } catch (Exception $e) {
        // Log seguro de erro
        error_log("Exceção ao processar IPN PayPal: " . $e->getMessage());
        
        // Resposta 200 OK para prevenir retentativas automáticas
        http_response_code(200);
        echo "IPN Received (Error Logged)";
        exit;
    }
}
```

## Vulnerabilidades Mitigadas

1. **Falsificação de Requisições entre Sites (CSRF)**
   - Implementação de tokens CSRF em todas as operações iniciadas pelo usuário
   - Validação de token em cada requisição POST

2. **Manipulação de Transações**
   - Verificação criptográfica da autenticidade dos callbacks/webhooks
   - Validação do estado da transação antes de operações críticas

3. **Exposição de Credenciais**
   - Credenciais armazenadas com segurança
   - Token de acesso nunca exposto ao frontend
   - Refresh tokens gerenciados pelo backend

4. **Ataque de Man-in-the-Middle**
   - TLS para todas as comunicações
   - Verificação estrita de certificados (CURLOPT_SSL_VERIFYPEER e CURLOPT_SSL_VERIFYHOST)

5. **Roubo de Tokens**
   - Tokens de curta duração
   - Verificação do timestamp de expiração
   - Regeneração automática quando necessário

6. **Fraude de Pagamento**
   - Validação de valores e totais
   - Verificação de correspondência entre pedido e detalhes de pagamento
   - Registro de auditoria para todas as operações

## Testes de Segurança

1. **Teste de Injeção de Parâmetros**
   - Tentativa de manipulação dos valores de transação
   - Tentativa de reutilização de tokens 
   - Resultado: Identificação e rejeição das tentativas maliciosas

2. **Teste de Falsificação de Webhooks**
   - Simulação de webhooks fraudulentos
   - Resultado: Rejeição de webhooks não verificáveis

3. **Teste de Timing Attacks**
   - Análise de tempo de resposta em comparações críticas
   - Resultado: Tempo de resposta consistente independente de entrada

4. **Teste de Fuzzing**
   - Submissão de dados malformados aleatórios
   - Resultado: Tratamento adequado sem vazamento de informações

5. **Teste de Cross-Site Scripting (XSS)**
   - Injeção de scripts em campos de dados
   - Resultado: Sanitização adequada impedindo execução de scripts

## Recomendações Adicionais

1. **Monitoramento Ativo**
   - Implementar alertas para tentativas de fraude
   - Monitorar padrões anormais de comportamento em pagamentos

2. **Atualização Regular**
   - Manter a integração atualizada com as mais recentes APIs do PayPal
   - Verificar regularmente por alterações nos requisitos de segurança

3. **Configuração por Ambiente**
   - Utilizar diferentes conjuntos de configurações para:
     - Desenvolvimento (sandbox, logs verbosos)
     - Homologação (sandbox, logs moderados)
     - Produção (ambiente real, logs mínimos mas auditáveis)

4. **Backups de Histórico de Transações**
   - Implementar backups regulares das tabelas de transações
   - Manter registro auditável por tempo adequado (mínimo 6 meses)
