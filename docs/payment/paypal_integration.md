# Guia de Integração PayPal

Este documento detalha a implementação da integração com o gateway de pagamento PayPal na plataforma Taverna da Impressão 3D.

## Visão Geral

A integração com PayPal utiliza a PayPal REST API v2 para processar pagamentos, oferecendo aos clientes a opção de pagar diretamente via conta PayPal ou cartão de crédito. Esta implementação segue o fluxo de Order API, que proporciona maior flexibilidade e conformidade com as regulamentações mais recentes.

## Fluxo de Pagamento

### 1. Fluxo de Checkout Padrão

```
┌──────────┐    ┌───────────┐    ┌──────────┐    ┌───────────┐    ┌──────────┐
│  Cliente │───>│ Controller│───>│ Gateway  │───>│ PayPal API│───>│ Redirect │
└──────────┘    └───────────┘    └──────────┘    └───────────┘    └──────────┘
      │                                                                 │
      │                                                                 │
      │                                                                 ▼
      │                                                          ┌──────────┐
      │                                                          │  PayPal  │
      │                                                          └──────────┘
      │                                                                 │
      │                                                                 │
      ▼                             ┌───────────┐    ┌──────────┐       │
┌──────────┐                        │ Controller│<───│ Webhook  │<──────┘
│  Sucesso │<──────────────────────>│   Return  │    │ Receiver │
└──────────┘                        └───────────┘    └──────────┘
```

1. O cliente seleciona produtos e inicia o checkout
2. O controller chama `initiateTransaction()` do PayPalGateway
3. O gateway cria uma ordem na API do PayPal
4. O cliente é redirecionado para a página de pagamento do PayPal
5. Após aprovação, o PayPal redireciona para URL de retorno
6. Paralelamente, o PayPal envia webhooks para atualizações de status

### 2. Operações Administrativas

```
┌──────────┐    ┌─────────────────┐    ┌──────────┐    ┌───────────┐
│  Admin   │───>│ AdminController │───>│ Gateway  │───>│ PayPal API│
└──────────┘    └─────────────────┘    └──────────┘    └───────────┘
                        ▲                   ▲                 │
                        │                   │                 │
                        └───────────────────┴─────────────────┘
                               Resposta/Resultado
```

Operações administrativas incluem:
- Verificação de status de transações
- Cancelamento de transações
- Processamento de reembolsos (totais/parciais)

## Pré-requisitos para Implementação

### 1. Credenciais PayPal

Para ambiente de sandbox:
- Client ID
- Client Secret
- Webhook ID (para validação de assinatura)

Para produção:
- Client ID de produção
- Client Secret de produção
- Webhook ID de produção

### 2. Configuração de Webhooks

No painel do PayPal, configure webhooks para os seguintes eventos:
- `PAYMENT.AUTHORIZATION.CREATED`
- `PAYMENT.CAPTURE.COMPLETED`
- `PAYMENT.CAPTURE.DENIED`
- `PAYMENT.CAPTURE.REFUNDED`
- `CHECKOUT.ORDER.APPROVED`
- `CHECKOUT.ORDER.COMPLETED`

URL do webhook: `https://seudomain.com/webhook/paypal`

## Implementação

### 1. Configuração Básica

No arquivo `app/config/payment_config.php`:

```php
'paypal' => [
    'display_name' => 'PayPal',
    'active' => true,
    'sandbox' => true, // true para testes, false para produção
    'webhook_url' => BASE_URL . 'webhook/paypal',
    'supported_methods' => ['paypal', 'credit_card'],
    
    // Credenciais (substituir pelos valores reais)
    'client_id' => 'YOUR_SANDBOX_CLIENT_ID',
    'client_secret' => 'YOUR_SANDBOX_CLIENT_SECRET',
    
    // Configurações específicas
    'settings' => [
        'currency' => 'BRL',
        'intent' => 'CAPTURE',
        'webhook_id' => 'YOUR_WEBHOOK_ID',
        'return_url' => BASE_URL . 'pedido/sucesso/',
        'cancel_url' => BASE_URL . 'pedido/cancelado/'
    ]
]
```

### 2. Integração Frontend

Adicione o SDK do PayPal ao seu template de checkout:

```html
<script src="https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID&currency=BRL"></script>
<div id="paypal-button-container"></div>

<script>
  paypal.Buttons({
    createOrder: function(data, actions) {
      // Executado quando o botão é clicado
      return fetch('/payment/create-order', {
        method: 'post',
        headers: {
          'content-type': 'application/json',
          'X-CSRF-Token': document.querySelector('input[name="csrf_token"]').value
        },
        body: JSON.stringify({
          order_id: document.getElementById('order_id').value,
          payment_method: 'paypal'
        })
      })
      .then(function(res) { return res.json(); })
      .then(function(data) { return data.transaction_id; });
    },
    onApprove: function(data, actions) {
      // Executado quando o pagamento é aprovado
      return fetch('/payment/capture-order', {
        method: 'post',
        headers: {
          'content-type': 'application/json',
          'X-CSRF-Token': document.querySelector('input[name="csrf_token"]').value
        },
        body: JSON.stringify({
          order_id: document.getElementById('order_id').value,
          transaction_id: data.orderID
        })
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.success) {
          window.location.href = data.redirect_url;
        } else {
          alert('Erro ao processar pagamento: ' + data.error_message);
        }
      });
    }
  }).render('#paypal-button-container');
</script>
```

## Requisitos de Segurança

### 1. Validação de Webhooks

Para evitar fraudes, todos os webhooks devem ser validados:

```php
/**
 * Verifica assinatura do webhook para garantir autenticidade
 * 
 * @param array $requestData Dados recebidos no webhook
 * @return bool Se a assinatura é válida
 */
protected function verifyWebhookSignature(array $requestData): bool {
    // Obter webhook ID
    $webhookId = $this->config['settings']['webhook_id'] ?? '';
    
    if (empty($webhookId)) {
        error_log('Webhook ID não configurado para validação');
        return false;
    }
    
    // Obter cabeçalhos de assinatura da requisição
    $headers = getallheaders();
    
    $paypalSignature = $headers['Paypal-Transmission-Sig'] ?? '';
    $paypalCertUrl = $headers['Paypal-Cert-Url'] ?? '';
    $paypalTransmissionId = $headers['Paypal-Transmission-Id'] ?? '';
    $paypalTransmissionTime = $headers['Paypal-Transmission-Time'] ?? '';
    
    // Verificar se todos os cabeçalhos necessários estão presentes
    if (empty($paypalSignature) || empty($paypalCertUrl) || 
        empty($paypalTransmissionId) || empty($paypalTransmissionTime)) {
        error_log('Cabeçalhos de webhook incompletos');
        return false;
    }
    
    // Implementar verificação criptográfica da assinatura
    // Na implementação real, usar API do PayPal para verificação
    
    return true;
}
```

### 2. Validação de IP

Como medida adicional de segurança, você pode verificar se a requisição veio de um IP do PayPal:

```php
/**
 * Verifica se IP de origem é do PayPal
 * 
 * @return bool Se IP é legítimo
 */
protected function verifyIpAddress(): bool {
    $paypalIps = [
        '64.4.240.0/22',
        '66.211.168.0/22',
        '173.0.80.0/20',
        '173.0.80.0/24',
        // Adicionar outros ranges conforme documentação do PayPal
    ];
    
    $clientIp = $_SERVER['REMOTE_ADDR'];
    
    foreach ($paypalIps as $range) {
        if ($this->ipInRange($clientIp, $range)) {
            return true;
        }
    }
    
    error_log("IP suspeito tentando acessar webhook PayPal: {$clientIp}");
    return false;
}

/**
 * Verifica se IP está em range CIDR
 */
protected function ipInRange($ip, $range): bool {
    // Implementação de verificação de IP em range CIDR
    // ...
}
```

### 3. Gestão Segura de Tokens

Para proteger os tokens de acesso:

```php
/**
 * Garante que existe um token de acesso válido
 */
protected function ensureAccessToken(): void {
    // Verificar se token existente ainda é válido
    $now = time();
    
    if ($this->accessToken && $this->tokenExpires > $now + 60) {
        // Token ainda válido, verificar se está em cache seguro
        return;
    }
    
    // Implementar armazenamento seguro do token
    // Nunca expor token no frontend ou em logs
}
```

## Testes e Validação

### Cenários de Teste

1. **Pagamento Básico**
   - Criar pedido com valor específico
   - Pagar com conta de teste do PayPal
   - Verificar se webhooks são recebidos e processados
   - Confirmar atualizações de status no sistema

2. **Cenários de Erro**
   - Pagamento cancelado pelo usuário
   - Pagamento rejeitado por insuficiência de fundos
   - Timeout em callbacks/webhooks

3. **Operações Administrativas**
   - Verificação de status de transações
   - Cancelamento de transações pendentes
   - Reembolso total e parcial

### Contas de Teste

Para testes no ambiente de sandbox:

1. **Conta Comercial (Recebe Pagamentos)**
   - Email: `sb-merchant@business.example.com`
   - Senha: Use a senha gerada no painel de sandbox

2. **Conta Pessoal (Realiza Pagamentos)**
   - Email: `sb-buyer@personal.example.com`
   - Senha: Use a senha gerada no painel de sandbox

### Cartões de Teste

Cartões para testes em sandbox (usados com contas de teste):

| Tipo      | Número               | Expiração | CVV |
|-----------|----------------------|-----------|-----|
| Visa      | 4111111111111111     | Qualquer data futura | Qualquer |
| MasterCard| 5555555555554444     | Qualquer data futura | Qualquer |
| Amex      | 378282246310005      | Qualquer data futura | Qualquer |
| Discover  | 6011111111111117     | Qualquer data futura | Qualquer |

## Tratamento de Erros

### Códigos de Erro Comuns

| Código | Descrição                | Tratamento Recomendado |
|--------|--------------------------|------------------------|
| INSTRUMENT_DECLINED | Cartão ou meio de pagamento recusado | Solicitar método alternativo |
| PAYER_ACTION_REQUIRED | Ação adicional do pagador necessária | Redirecionar para URL especificada |
| UNPROCESSABLE_ENTITY | Dados inválidos na requisição | Corrigir dados e reenviar |
| INTERNAL_SERVER_ERROR | Erro interno do PayPal | Tentar novamente mais tarde |

### Logs e Depuração

Todos os erros e interações com a API são registrados em:

- Logs gerais do sistema
- Registros de transação na tabela `payment_transactions`
- Registros detalhados na tabela `payment_webhooks`

Para depuração em desenvolvimento, habilite modo verboso:

```php
// Em app/config/payment_config.php
'paypal' => [
    // ...outras configurações
    'debug' => true
]
```

## Migração para Produção

### Checklist para Implantação

1. **Atualizar Credenciais**
   - Substituir Client ID e Secret de sandbox por versões de produção
   - Configurar Webhook ID de produção
   - Definir `sandbox => false` na configuração

2. **Verificar Configurações**
   - Confirmar URLs de callback e webhook usando domínio de produção
   - Validar moeda e outras configurações específicas
   - Testar com valor real pequeno antes de liberar totalmente

3. **Monitoramento**
   - Configurar alertas para erros de pagamento
   - Monitorar taxa de sucesso/falha de transações
   - Monitorar recebimento e processamento de webhooks

## Recursos Adicionais

- [Documentação oficial da PayPal REST API](https://developer.paypal.com/docs/api/overview/)
- [Guia de webhooks do PayPal](https://developer.paypal.com/api/webhooks/)
- [Painel de desenvolvedor do PayPal](https://developer.paypal.com/dashboard/)
- [Fórum de suporte ao desenvolvedor](https://developer.paypal.com/support/)