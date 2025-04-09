# Arquitetura do Sistema de Pagamento - Taverna da Impressão 3D

## Visão Geral

O sistema de pagamento da Taverna da Impressão 3D implementa uma arquitetura modular e extensível, projetada para integrar múltiplos gateways de pagamento enquanto mantém um alto nível de segurança, rastreabilidade e isolamento de responsabilidades.

## Componentes Principais

### 1. Interface e Contratos

- **`PaymentGatewayInterface`**: Contrato principal que define as operações obrigatórias para qualquer gateway de pagamento, garantindo consistência e substituibilidade.
- **`AbstractPaymentGateway`**: Implementação abstrata com funcionalidades comuns a todos os gateways, incluindo logging, validação e tratamento de erros.

### 2. Gerenciamento Centralizado

- **`PaymentManager`**: Singleton que atua como fachada para todos os gateways, gerenciando configurações, roteamento de operações e tratamento unificado de webhooks.
- **Tratamento de Configurações**: Carrega configurações do banco de dados com fallback para valores padrão.

### 3. Gateways Específicos

- Implementações concretas para cada provedor de pagamento:
  - **`MercadoPagoGateway`**: Integração com API v2 do MercadoPago.
  - **`PayPalGateway`**: Integração com API REST do PayPal.
  - **`PagSeguroGateway`**: Integração com API do PagSeguro.

### 4. Controladores e Fluxo de Usuário

- **`PaymentController`**: Gerencia o fluxo de pagamento do lado do cliente.
- **`AdminPaymentController`**: Gerencia configurações e operações administrativas.
- **Endpoints de Webhook**: Processamento assíncrono de notificações dos gateways.

### 5. Persistência e Auditoria

- **Tabelas Relacionais**: Armazenam configurações, transações, tentativas e webhooks.
- **Logs Estruturados**: Registro detalhado de todas as operações com remoção de dados sensíveis.

## Fluxo de Processamento de Pagamento

```
┌────────────┐    ┌───────────────┐    ┌─────────────────┐    ┌──────────────┐
│  Cliente   │───>│ PaymentManager│───>│ Gateway         │───>│ API Externa  │
└────────────┘    └───────────────┘    └─────────────────┘    └──────────────┘
        ▲                 │                    │                      │
        │                 │                    │                      │
        └─────────────────┴────────────────────┴──────────────────────┘
                            Resposta/Callback
```

1. **Iniciação de Pagamento**:
   - Validação rigorosa de entrada
   - Seleção do gateway apropriado
   - Preparação de dados conforme requisitos do gateway
   - Execução da transação via API do gateway

2. **Processamento Assíncrono**:
   - Recebimento de webhooks/callbacks
   - Validação de assinaturas e autenticidade
   - Atualização de status da transação
   - Notificação para sistemas internos

3. **Verificação de Status**:
   - API para consulta de status atual
   - Implementação de polling com rate limiting
   - Atualização em tempo real via WebSockets (futuro)

## Medidas de Segurança Implementadas

1. **Proteção de Dados**:
   - Nenhum dado sensível de pagamento é armazenado
   - Tokens temporários para operações
   - Sanitização de logs para remoção de dados sensíveis

2. **Validação e Proteção de Entrada**:
   - `InputValidationTrait` para validação consistente
   - Filtros específicos por tipo de dado
   - Proteção contra injeção em todas as camadas

3. **Proteção Contra Ataques**:
   - Tokens CSRF em todas as operações POST
   - Comparação time-safe para dados sensíveis (`hash_equals`)
   - Rate limiting para prevenção de abusos

4. **Segurança na Comunicação**:
   - TLS para todas as comunicações externas
   - Validação de assinaturas de webhooks
   - Verificação de IPs para callbacks quando aplicável

5. **Auditoria e Rastreabilidade**:
   - Registro detalhado de todas as operações
   - IDs únicos para correlação entre sistemas
   - Histórico completo do ciclo de vida de transações

## Extensibilidade e Manutenção

O sistema foi projetado para fácil adição de novos gateways, seguindo princípios SOLID:

1. **Adicionando Novo Gateway**:
   - Implementar classe concreta baseada em `AbstractPaymentGateway`
   - Configurar métodos específicos do gateway
   - Registrar no sistema via banco de dados/painel admin

2. **Modificação de Comportamento**:
   - Comportamentos comuns centralizados na classe abstrata
   - Injeção de configuração para personalização sem modificar código
   - Estratégia de fallback para resiliência

## Considerações para Transações de Alto Valor

Para transações acima de determinados limiares, o sistema implementa verificações adicionais:

1. **Aprovação Administrativa**: Transações acima de R$ 5.000,00 ficam pendentes de aprovação manual.
2. **Verificações Anti-Fraude**: Integração com sistema de pontuação de risco.
3. **Tokenização**: Todas as transações de cartão usam tokenização para evitar manipulação de dados de cartão.

## Referências de Implementação

- [Documentação MercadoPago](https://www.mercadopago.com.br/developers/pt/reference)
- [Documentação PayPal](https://developer.paypal.com/docs/api/overview/)
- [Documentação PagSeguro](https://dev.pagseguro.uol.com.br/reference)