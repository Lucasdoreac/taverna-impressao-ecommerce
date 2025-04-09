# Guia de Integração de Novos Gateways de Pagamento

Este guia detalha o processo de integração de novos gateways de pagamento na plataforma Taverna da Impressão 3D.

## Pré-requisitos

1. Familiaridade com o padrão de arquitetura do sistema de pagamento
2. Documentação da API do gateway a ser integrado
3. Acesso ao ambiente de desenvolvimento com credenciais de sandbox

## Etapas de Implementação

### 1. Criar classe concreta do gateway

Crie uma nova classe em `app/lib/Payment/Gateways/{NomeGateway}Gateway.php` que estenda `AbstractPaymentGateway` e implemente `PaymentGatewayInterface`.

```php
<?php
namespace App\Lib\Payment\Gateways;

use App\Lib\Payment\AbstractPaymentGateway;
use App\Lib\Payment\PaymentGatewayInterface;
use App\Lib\Security\SecurityManager;

class NomeGatewayGateway extends AbstractPaymentGateway implements PaymentGatewayInterface {
    // Implementação específica
}
```

### 2. Implementar métodos obrigatórios

Implemente obrigatoriamente os seguintes métodos:

- `getGatewayName()`: Nome único para identificação do gateway
- `validateConfiguration()`: Validação das credenciais e configurações
- `initiateTransaction()`: Início de transação de pagamento
- `checkTransactionStatus()`: Verificação de status atual
- `handleCallback()`: Manipulação de webhooks/callbacks
- `cancelTransaction()`: Cancelamento de transações
- `refundTransaction()`: Reembolso de transações
- `getFrontendConfig()`: Configurações para o frontend

### 3. Implementar métodos específicos do gateway

Adicione métodos privados para funcionalidades específicas do gateway:

- Comunicação com a API (autenticação, endpoints)
- Mapeamento de status entre o gateway e sistema interno
- Formatação específica de dados
- Validação de assinaturas/callbacks

### 4. Configurar webhook

No arquivo de rotas, adicione o endpoint para o webhook:

```php
// Em app/config/routes.php
$router->add('webhook/{gateway}', ['controller' => 'Payment', 'action' => 'webhook']);
```

### 5. Adicionar configuração padrão

No arquivo `app/config/payment_config.php`, adicione configuração padrão para o novo gateway:

```php
'nome_gateway' => [
    'display_name' => 'Nome do Gateway',
    'active' => false,
    'sandbox' => true,
    'webhook_url' => BASE_URL . 'webhook/nome_gateway',
    'supported_methods' => ['credit_card', 'other_methods'],
    
    // Chaves de teste
    'api_key' => 'TEST_KEY',
    'api_secret' => 'TEST_SECRET',
    
    // Configurações específicas
    'settings' => [
        'max_installments' => 12,
        'other_settings' => 'value'
    ]
]
```

### 6. Implementar testes unitários

Crie testes unitários para a nova integração:

- Teste de validação de configuração
- Teste de inicialização de transação
- Teste de manipulação de webhooks
- Testes de cenários de erro comuns

## Requisitos de Segurança

Ao implementar um novo gateway, assegure-se de seguir os seguintes requisitos de segurança:

### 1. Proteção de Dados Sensíveis

- **NUNCA armazene** credenciais de cartão de crédito (número, CVV, data) em logs ou banco de dados
- **Sanitize** todos os dados antes de armazenar ou logar usando `removeSensitiveData()`
- **Use** a função `logApiInteraction()` para registrar comunicações com a API externa

### 2. Validação Robusta

- **Valide** rigorosamente todos os parâmetros recebidos do usuário
- **Verifique** formatos, tamanhos e tipos de dados antes de enviar para API externa
- **Implemente** validações específicas para os requisitos do gateway

### 3. Segurança em Comunicações

- **Utilize** HTTPS para todas as chamadas externas (forçado em todos os ambientes)
- **Verifique** assinaturas/tokens em webhooks usando métodos time-safe
- **Implemente** retry com backoff para chamadas que possam falhar temporariamente

### 4. Tratamento de Erros

- **Capture** e registre todos os erros de forma estruturada
- **Forneça** mensagens de erro úteis mas sem expor detalhes técnicos ao usuário final
- **Registre** IDs de correlação em todas as transações para rastreabilidade

## Lista de Verificação para Homologação

✅ Todos os métodos de `PaymentGatewayInterface` estão implementados  
✅ Configurações são validadas adequadamente  
✅ Dados sensíveis são protegidos e não aparecem em logs  
✅ Endpoint de webhook está configurado e funcional  
✅ Transações de teste foram processadas com sucesso no ambiente de sandbox  
✅ Callbacks/webhooks são processados corretamente  
✅ Cenários de erro são tratados adequadamente  
✅ Documentação do gateway está completa

## Exemplos

### Exemplo de validação de configuração

```php
protected function validateConfiguration(): void {
    $requiredFields = ['api_key', 'api_secret', 'merchant_id'];
    
    foreach ($requiredFields as $field) {
        if (!isset($this->config[$field]) || empty($this->config[$field])) {
            throw new \Exception("Configuração obrigatória ausente: {$field}");
        }
    }
    
    // Validações específicas adicionais
    if (isset($this->config['webhook_url']) && !filter_var($this->config['webhook_url'], FILTER_VALIDATE_URL)) {
        throw new \Exception("URL de webhook inválida");
    }
}
```

### Exemplo de comunicação com API externa

```php
protected function sendRequest(string $method, string $endpoint, array $data = []): array {
    // Determinar URL base conforme ambiente
    $baseUrl = $this->isSandbox ? $this->sandboxApiBaseUrl : $this->apiBaseUrl;
    
    // Configurar curl com práticas seguras
    $curl = curl_init();
    
    $url = $baseUrl . $endpoint;
    $headers = [
        'Authorization: Bearer ' . $this->config['api_key'],
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: TavernaDaImpressao3D/1.0'
    ];
    
    // Configurações seguras de CURL
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => true,  // Sempre verificar certificados
        CURLOPT_SSL_VERIFYHOST => 2      // Verificar nome do host
    ];
    
    // Adicionar dados para métodos POST e PUT
    if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($data)) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }
    
    curl_setopt_array($curl, $options);
    
    // Executar requisição e capturar resultados
    $response = curl_exec($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    // Registrar interação com API (removendo dados sensíveis)
    $this->logApiInteraction($endpoint, $data, 
        json_decode($response, true) ?? ['raw' => $response], 
        $statusCode >= 200 && $statusCode < 300);
    
    // Verificar erros e retornar resultados
    // ...
}
```

## Documentação Adicional

- [Arquitetura do Sistema de Pagamento](architecture.md)
- [Guia de Implementação MercadoPago](mercadopago_integration.md)
- [Guia de Implementação PayPal](paypal_integration.md)
- [Melhores Práticas de Segurança](security_best_practices.md)