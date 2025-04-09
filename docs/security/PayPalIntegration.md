# Documentação de Segurança: Integração PayPal

## Função
Implementação segura da integração com o gateway de pagamento PayPal, utilizando a API REST v2, com foco em proteção contra CSRF, validação robusta de dados, e verificação de autenticidade de notificações IPN.

## Arquitetura e Fluxo de Dados

### Fluxo de Pagamento
1. O usuário seleciona PayPal como método de pagamento
2. O sistema gera um token CSRF para a sessão
3. O frontend inicia a criação de uma ordem PayPal via AJAX (com token CSRF)
4. O backend valida o token CSRF e cria a ordem no PayPal
5. O PayPal gera um ID de transação e URL de aprovação
6. O frontend redireciona o usuário para a URL de aprovação do PayPal
7. O usuário autoriza o pagamento no site do PayPal
8. PayPal redireciona o usuário de volta para a loja
9. O frontend captura o pagamento via AJAX (com token CSRF)
10. O backend valida o token CSRF e captura o pagamento via API do PayPal
11. O pagamento é registrado no banco de dados com status "approved"

### Fluxo de Notificação IPN
1. PayPal envia uma notificação IPN para o endpoint configurado
2. O sistema verifica a autenticidade da notificação com o servidor do PayPal
3. Se autêntica, o sistema atualiza o status do pedido e da transação
4. O sistema registra a notificação para auditoria

## Implementação

### Controllers
- `PaymentController`: Gerencia a interface com o usuário e AJAX
  - `paypal()`: Renderiza a página de checkout
  - `createPayPalOrder()`: Endpoint AJAX para criação de ordem
  - `capturePayPalOrder()`: Endpoint AJAX para captura de pagamento
  - `cancelPayPalOrder()`: Endpoint AJAX para cancelamento
  - `logError()`: Endpoint AJAX para registro de erros

- `PaymentCallbackController`: Processa callbacks assíncronos
  - `paypalIPN()`: Processa notificações IPN
  - `removeSensitiveData()`: Sanitiza dados para logs

### PaymentManager
- `capturePayPalPayment()`: Captura pagamento autorizado
- `processPayPalIPN()`: Processa notificações IPN
- `verifyPayPalIPN()`: Verifica autenticidade de notificações
- `getOrderIdFromPayPalTransaction()`: Associa transação a pedido
- `updateOrderPaymentStatus()`: Atualiza status do pedido
- `updateOrCreatePayPalTransaction()`: Atualiza ou cria registro de transação

### Validação de Dados
- Todos os inputs de usuário são validados via `InputValidationTrait`
- Dados sensíveis são removidos para logs via `removeSensitiveData()`
- IDs de transação são sanitizados antes de uso em consultas SQL
- Requisições são validadas com tokens CSRF

### Gateway PayPal
- `PayPalGateway`: Implementa `PaymentGatewayInterface`
- Comunica diretamente com a API REST v2 do PayPal
- Gerencia tokens de autenticação OAuth

## Uso Correto

### Iniciar Pagamento no Frontend
```javascript
// Obter token CSRF da sessão (via data-attribute ou var PHP)
const csrfToken = document.getElementById('csrf-token').value;

// Solicitar criação da ordem
fetch('/payment/create-paypal-order', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify({
        order_id: orderId,
        payment_method: 'paypal'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        // Redirecionar para PayPal ou mostrar botões
        window.location.href = data.redirect_url;
    } else {
        // Mostrar erro
        showError(data.error_message);
    }
});
```

### Verificar Pagamento no Backend
```php
// No controller (após validação CSRF)
public function capturePayPalOrder() {
    // Validar dados
    if (!$paypalOrderId) {
        return $this->jsonResponse(['success' => false, 'message' => 'Dados incompletos']);
    }
    
    // Capturar pagamento
    $result = $this->paymentManager->capturePayPalPayment($paypalOrderId, $payerId);
    
    // Verificar resultado
    if (!$result['success']) {
        $this->logError('capture_error', $result['error_message']);
        return $this->jsonResponse(['success' => false, 'error_message' => $result['error_message']]);
    }
    
    // Atualizar pedido
    $this->updateOrderPaymentInfo($orderId, $result, 'paypal');
    
    return $this->jsonResponse([
        'success' => true,
        'status' => $result['status'] ?? 'approved'
    ]);
}
```

### Configuração de IPN
```php
// No arquivo de configuração ou banco de dados
$paypalSettings = [
    'webhook_url' => BASE_URL . 'payment/ipn/paypal',
    'client_id' => 'CLIENT_ID_AQUI',
    'client_secret' => 'CLIENT_SECRET_AQUI',
    'sandbox' => true // Mudar para false em produção
];
```

## Vulnerabilidades Mitigadas

### CSRF (Cross-Site Request Forgery)
- Todos os endpoints AJAX requerem tokens CSRF válidos
- Tokens são invalidados após uso bem-sucedido
- Tokens são associados à sessão do usuário

### XSS (Cross-Site Scripting)
- Todos os dados de entrada são sanitizados antes do uso
- Todos os dados de saída são sanitizados com `htmlspecialchars()`
- Headers de segurança incluem Content-Security-Policy

### Injeção SQL
- Todas as consultas usam prepared statements
- Parâmetros são tipados e validados antes do uso
- IDs e outros identificadores são convertidos para o tipo adequado

### Adulteração de Requisições
- Notificações IPN são verificadas com o servidor do PayPal
- A comunicação com a API do PayPal usa TLS 1.2+
- Cabeçalhos de requisição incluem proteções contra adulteração

### Exposição de Dados Sensíveis
- Credenciais do PayPal são armazenadas em configuração segura
- Logs removem dados sensíveis como tokens
- Erros detalhados são registrados para depuração, mas não expostos ao usuário

## Testes de Segurança

1. **Teste de CSRF**:
   - Descrição: Tentativa de enviar requisição sem token CSRF
   - Resultado: Requisição rejeitada com código HTTP 403 (Forbidden)

2. **Teste de Autenticidade IPN**:
   - Descrição: Envio de notificação IPN falsificada
   - Resultado: Notificação rejeitada após verificação com servidor PayPal

3. **Teste de Verificação de Token**:
   - Descrição: Envio de requisição com token CSRF expirado
   - Resultado: Requisição rejeitada, usuário redirecionado para página de erro

4. **Teste de Injeção SQL**:
   - Descrição: Tentativa de injeção SQL via parâmetros da transação
   - Resultado: Parâmetros sanitizados, consulta preparada bloqueia a injeção

5. **Teste de Elevação de Privilégios**:
   - Descrição: Tentativa de acessar pedidos de outro usuário
   - Resultado: Verificação de propriedade do pedido bloqueia o acesso

## Monitoramento e Auditoria

- Todas as requisições à API do PayPal são registradas para auditoria
- Todas as notificações IPN são registradas com dados sanitizados
- Tentativas de fraude e erros são registrados com detalhes para análise
- O status de pagamentos é registrado com timestamp em cada alteração

## Considerações para Produção

1. Configurar modo de produção (sandbox = false)
2. Atualizar URLs de callback para domínio de produção
3. Implementar certificados SSL válidos e HSTS
4. Configurar monitoramento de transações em tempo real
5. Estabelecer processo de verificação manual para transações suspeitas
6. Configurar alertas para padrões anômalos de transações
7. Implementar limites de valor e frequência para mitigar abuso

## Referências

- [Documentação da API PayPal REST v2](https://developer.paypal.com/docs/api/overview/)
- [Guia de Segurança PayPal](https://developer.paypal.com/docs/api/reference/security/)
- [Documentação de IPN PayPal](https://developer.paypal.com/docs/api-basics/notifications/ipn/)
- [OWASP Top 10 Web Application Security Risks](https://owasp.org/www-project-top-ten/)
