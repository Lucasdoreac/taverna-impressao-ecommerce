# Guia de Integração MercadoPago

Este documento detalha a implementação da integração com o gateway de pagamento MercadoPago na plataforma Taverna da Impressão 3D.

## Visão Geral

A integração com MercadoPago utiliza a API v2 para processar pagamentos, oferecendo aos clientes múltiplas opções de pagamento incluindo cartão de crédito, PIX e boleto bancário. A implementação segue o padrão de Preferências (Preferences API), que proporciona maior flexibilidade e segurança.

## Fluxo de Pagamento

### 1. Fluxo de Checkout Padrão

```
┌──────────┐    ┌───────────┐    ┌──────────┐    ┌────────────┐    ┌──────────┐
│  Cliente │───>│ Controller│───>│ Gateway  │───>│MercadoPago │───>│ Redirect │
└──────────┘    └───────────┘    └──────────┘    │    API     │    └──────────┘
      │                                          └────────────┘          │
      │                                                                  │
      │                                                                  ▼
      │                                                         ┌──────────────┐
      │                                                         │ MercadoPago  │
      │                                                         └──────────────┘
      │                                                                  │
      │                                                                  │
      ▼                              ┌───────────┐    ┌──────────┐       │
┌──────────┐                         │ Controller│<───│ Webhook  │<──────┘
│  Sucesso │<───────────────────────>│   Return  │    │ Receiver │
└──────────┘                         └───────────┘    └──────────┘
```

1. O cliente seleciona produtos e inicia o checkout
2. O controller chama `initiateTransaction()` do MercadoPagoGateway
3. O gateway cria uma preferência na API do MercadoPago
4. O cliente é redirecionado para a página de pagamento do MercadoPago
5. Após aprovação, o MercadoPago redireciona para URL de retorno
6. Paralelamente, o MercadoPago envia webhooks para atualizações de status

### 2. Fluxo do PIX

```
┌──────────┐    ┌───────────┐    ┌───────────┐    ┌────────────────┐
│  Cliente │───>│ Controller│───>│ Gateway   │───>│  MercadoPago   │
└──────────┘    └───────────┘    └───────────┘    └────────────────┘
      │                                │                   │
      │                                │<──────────────────┘
      │                                │    QR Code PIX
      │                                │
      │                                ▼
      │                         ┌─────────────┐
      │<────────────────────────┤ PIX QR Code │
      │                         └─────────────┘
      │
      │ Paga via App Bancário
      ▼
┌──────────────┐
│ App Bancário │
└──────────────┘
      │
      │ Confirmação
      │
      ▼
┌────────────────┐    ┌───────────┐    ┌──────────┐
│  MercadoPago   │───>│ Controller│───>│ Cliente  │
└────────────────┘    │  Webhook  │    └──────────┘
                      └───────────┘
```

### 3. Operações Administrativas

```
┌──────────┐    ┌─────────────────┐    ┌──────────┐    ┌────────────┐
│  Admin   │───>│ AdminController │───>│ Gateway  │───>│MercadoPago │
└──────────┘    └─────────────────┘    └──────────┘    │    API     │
                        ▲                   ▲          └────────────┘
                        │                   │                 │
                        └───────────────────┴─────────────────┘
                               Resposta/Resultado
```

Operações administrativas incluem:
- Verificação de status de transações
- Cancelamento de transações
- Processamento de reembolsos (totais/parciais)

## Pré-requisitos para Implementação

### 1. Credenciais MercadoPago

Para ambiente de sandbox:
- Access Token de teste
- Public Key de teste

Para produção:
- Access Token de produção
- Public Key de produção

### 2. Configuração de Webhooks

No painel do MercadoPago, configure webhooks para os seguintes eventos:
- `payment`
- `plan`
- `subscription`
- `invoice`
- `point_integration_wh`

URL do webhook: `https://seudomain.com/webhook/mercadopago`

## Implementação

### 1. Configuração Básica

No arquivo `app/config/payment_config.php`:

```php
'mercadopago' => [
    'display_name' => 'MercadoPago',
    'active' => true,
    'sandbox' => true, // true para testes, false para produção
    'webhook_url' => BASE_URL . 'webhook/mercadopago',
    'supported_methods' => ['credit_card', 'pix', 'boleto', 'debit_card'],
    
    // Credenciais (substituir pelos valores reais)
    'public_key' => 'TEST-00000000-0000-0000-0000-000000000000',
    'access_token' => 'TEST-0000000000000000-000000-00000000000000000000000000000000-000000000',
    
    // Configurações específicas
    'settings' => [
        'max_installments' => 12,
        'expiration_days_boleto' => 3,
        'expiration_hours_pix' => 24,
        'statement_descriptor' => 'TAVERNA3D',
        'notification_url' => BASE_URL . 'webhook/mercadopago'
    ]
]
```

### 2. Integração Frontend

#### 2.1 Checkout para Cartão de Crédito

```html
<!-- Incluir SDK do MercadoPago -->
<script src="https://sdk.mercadopago.com/js/v2"></script>

<div id="cardPaymentBrick_container"></div>

<script>
  document.addEventListener('DOMContentLoaded', async function() {
    // Obter public key do backend
    const response = await fetch('/payment/get-mercadopago-config');
    const config = await response.json();
    
    // Inicializar SDK
    const mp = new MercadoPago(config.public_key, {
      locale: 'pt-BR'
    });
    
    // Renderizar componente de cartão
    const cardPaymentBrick = mp.bricks.create('cardPayment', 'cardPaymentBrick_container', {
      initialization: {
        amount: document.getElementById('order_total').value,
        payer: {
          email: document.getElementById('customer_email').value
        }
      },
      customization: {
        visual: {
          style: {
            theme: 'default'
          }
        }
      },
      callbacks: {
        onReady: () => {
          // Brick carregado
        },
        onSubmit: async (cardFormData) => {
          // Processar pagamento com o backend
          try {
            const response = await fetch('/payment/process', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('input[name="csrf_token"]').value
              },
              body: JSON.stringify({
                order_id: document.getElementById('order_id').value,
                payment_method: 'credit_card',
                card_token: cardFormData.token,
                installments: cardFormData.installments,
                card_brand: cardFormData.payment_method_id
              })
            });
            
            const result = await response.json();
            
            if (result.success) {
              window.location.href = result.redirect_url || '/pedido/sucesso/' + document.getElementById('order_number').value;
            } else {
              alert('Erro: ' + result.error_message);
            }
          } catch (error) {
            alert('Erro ao processar pagamento: ' + error.message);
          }
        },
        onError: (error) => {
          alert('Erro: ' + error.message);
        }
      }
    });
  });
</script>
```

#### 2.2 Checkout para PIX

Para PIX, o backend gera o QR Code e o exibe ao usuário:

```html
<div class="pix-container">
  <h3>Pague com PIX</h3>
  <p>Escaneie o QR Code abaixo com seu aplicativo bancário</p>
  
  <div class="qr-code">
    <img src="data:image/png;base64,<?= $qrCode ?>" alt="QR Code PIX">
  </div>
  
  <div class="pix-code">
    <p>Ou copie o código PIX abaixo:</p>
    <div class="copy-code">
      <input type="text" readonly value="<?= $qrCodeText ?>">
      <button onclick="copyPixCode()">Copiar</button>
    </div>
  </div>
  
  <p>Validade: <?= $expiresAt ?></p>
  
  <script>
    // Verificação de status automática
    let checkStatusInterval = setInterval(async function() {
      const response = await fetch('/payment/check-status', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': document.querySelector('input[name="csrf_token"]').value
        },
        body: JSON.stringify({
          order_id: <?= $orderId ?>
        })
      });
      
      const result = await response.json();
      
      if (result.status === 'approved' || result.status === 'authorized') {
        clearInterval(checkStatusInterval);
        window.location.href = '/pedido/sucesso/<?= $orderNumber ?>';
      }
    }, 5000);
    
    function copyPixCode() {
      const input = document.querySelector('.copy-code input');
      input.select();
      document.execCommand('copy');
      alert('Código PIX copiado!');
    }
  </script>
</div>
```

## Requisitos de Segurança

### 1. Validação de Webhooks

Para garantir a autenticidade dos webhooks:

```php
/**
 * Verifica autenticidade do webhook
 * 
 * @param array $requestData Dados recebidos
 * @return bool Se request é válido
 */
protected function validateWebhook(array $requestData): bool {
    // Verificar tipo de notificação
    if (!isset($requestData['type']) && !isset($requestData['action'])) {
        error_log('MercadoPago webhook inválido: tipo ausente');
        return false;
    }
    
    // Verificar ID de recurso
    if (!isset($requestData['data']['id']) && !isset($requestData['id'])) {
        error_log('MercadoPago webhook inválido: ID ausente');
        return false;
    }
    
    // Verificar cabeçalhos
    $headers = getallheaders();
    $signature = $headers['X-Signature'] ?? null;
    
    if ($signature) {
        // Implementar verificação de assinatura quando disponível
        // MercadoPago está trabalhando nesta funcionalidade
    }
    
    return true;
}
```

### 2. Segurança de Dados

Nunca armazenar dados sensíveis de cartão:

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

## Testes e Validação

### Cenários de Teste

1. **Pagamento com Cartão de Crédito**
   - Criar pedido com valor específico
   - Pagar com cartão de teste
   - Verificar captura da transação
   - Confirmar atualizações de status no sistema

2. **Pagamento com PIX**
   - Criar pedido com valor específico
   - Gerar QR Code PIX
   - Simular pagamento via API
   - Verificar recebimento de webhook
   - Confirmar atualizações de status no sistema

3. **Pagamento com Boleto**
   - Criar pedido com valor específico
   - Gerar boleto
   - Simular pagamento via API
   - Verificar recebimento de webhook
   - Confirmar atualizações de status no sistema

4. **Cenários de Erro**
   - Pagamento com cartão recusado
   - Timeout em PIX
   - Boleto não pago (expirado)
   - Erro de servidor do MercadoPago

5. **Operações Administrativas**
   - Verificação de status de transações
   - Cancelamento de transações
   - Reembolso total e parcial

### Cartões de Teste

Cartões para testes em sandbox:

| Tipo      | Número               | CVV | Expiração | Status        |
|-----------|----------------------|-----|-----------|---------------|
| MasterCard| 5031 4332 1540 6351  | 123 | 11/25     | Aprovado      |
| Visa      | 4235 6477 2802 5682  | 123 | 11/25     | Aprovado      |
| Visa      | 4013 5406 8274 6260  | 123 | 11/25     | Recusado      |
| American Express | 3753 651535 56885 | 1234 | 11/25 | Aprovado     |

Para testar diferentes situações, use estes valores no nome do titular:

- `APRO`: Pagamento aprovado
- `OTHE`: Erro geral
- `CONT`: Pending (necessária verificação) 
- `CALL`: Recusado com validação
- `FUND`: Fundos insuficientes
- `SECU`: Falha na validação de segurança
- `EXPI`: Data de expiração inválida
- `FORM`: Erro de formulário

### Teste de PIX

Para testes de PIX, após gerar o QR Code, simule o pagamento via API:

```php
// Simulação de pagamento PIX para testes
public function simulatePixPayment($externalReference) {
    $paymentData = [
        'transaction_amount' => 100,
        'description' => 'Título do produto',
        'payment_method_id' => 'pix',
        'payer' => [
            'email' => 'test_user_123456@testuser.com'
        ],
        'external_reference' => $externalReference
    ];
    
    $result = $this->sendRequest('POST', '/payments', $paymentData);
    
    return $result;
}
```

## Tratamento de Erros

### Códigos de Erro Comuns

| Código | Descrição                      | Tratamento Recomendado |
|--------|--------------------------------|------------------------|
| 3034   | Cartão não autorizado          | Solicitar outro cartão |
| 2067   | Valor excede limite disponível | Solicitar outro cartão |
| 3033   | Dados de cartão inválidos      | Verificar dados        |
| 2040   | Usuário cancelou pagamento     | Reiniciar processo     |
| 2004   | Pagamento rejeitado pela operadora | Solicitar outro método |

### Logs e Depuração

Todos os erros e interações com a API são registrados em:

- Logs gerais do sistema
- Registros detalhados na tabela `payment_transactions`
- Registros de webhooks na tabela `payment_webhooks`

Para depuração em desenvolvimento, habilite modo verboso:

```php
// Em app/config/payment_config.php
'mercadopago' => [
    // ...outras configurações
    'debug' => true
]
```

## Migração para Produção

### Checklist para Implantação

1. **Atualizar Credenciais**
   - Substituir Public Key e Access Token de sandbox por versões de produção
   - Configurar `sandbox => false` na configuração

2. **Verificar Configurações**
   - Confirmar URLs de callback e webhook usando domínio de produção
   - Validar statement descriptor e outras configurações específicas
   - Testar com valor real pequeno antes de liberar totalmente

3. **Monitoramento**
   - Configurar alertas para erros de pagamento
   - Monitorar taxa de sucesso/falha de transações
   - Monitorar recebimento e processamento de webhooks

## Recursos Adicionais

- [Documentação oficial do MercadoPago](https://www.mercadopago.com.br/developers/pt/reference)
- [Guia de integração](https://www.mercadopago.com.br/developers/pt/guides)
- [Sandbox e Contas de Teste](https://www.mercadopago.com.br/developers/pt/guides/overview/test-integration)
- [Documentação de Webhooks](https://www.mercadopago.com.br/developers/pt/guides/notifications/webhook)