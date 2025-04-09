# Plano de Testes em Ambiente Sandbox para Gateways de Pagamento

## 1. Objetivo

Este documento define o plano estruturado para validação completa das integrações com gateways de pagamento em ambiente sandbox antes da implantação em homologação, garantindo a segurança e integridade das transações.

## 2. Pré-requisitos

### 2.1. Contas e Credenciais

- [x] Conta de desenvolvedor no MercadoPago com credenciais de sandbox
- [x] Conta de desenvolvedor no PayPal com credenciais de sandbox
- [x] Configuração do ambiente de testes com variáveis de ambiente seguras
- [x] Clientes e cartões de teste configurados em ambas as plataformas

### 2.2. Ambiente de Testes

- [ ] Ambiente isolado com configuração idêntica ao de produção
- [ ] Base de dados de teste com esquema atualizado
- [ ] Logs configurados para capturar informações detalhadas
- [ ] Ferramentas de monitoramento de requisições HTTP configuradas

### 2.3. Ferramentas

- [ ] Postman ou equivalente para testes de API
- [ ] Browser com ferramentas de desenvolvedor para depuração
- [ ] Proxy de depuração (opcional, como Charles Proxy)
- [ ] Ferramenta de automação para testes repetitivos

## 3. Casos de Teste

### 3.1. MercadoPago

#### 3.1.1. Configuração

- [ ] **CFG-MP-01**: Validar conexão de teste usando credenciais de sandbox
- [ ] **CFG-MP-02**: Verificar persistência das configurações no banco de dados
- [ ] **CFG-MP-03**: Validar configuração de URLs de webhook
- [ ] **CFG-MP-04**: Testar interface administrativa com diferentes configurações

#### 3.1.2. Cartão de Crédito

- [ ] **CC-MP-01**: Iniciar transação com cartão válido
- [ ] **CC-MP-02**: Testar tokenização de cartão
- [ ] **CC-MP-03**: Testar diferentes bandeiras de cartão
- [ ] **CC-MP-04**: Testar cartão rejeitado (fundos insuficientes)
- [ ] **CC-MP-05**: Testar cartão rejeitado (problema genérico)
- [ ] **CC-MP-06**: Testar cartão com necessidade de autenticação adicional
- [ ] **CC-MP-07**: Cancelar transação aprovada
- [ ] **CC-MP-08**: Reembolso total de transação
- [ ] **CC-MP-09**: Reembolso parcial de transação

#### 3.1.3. PIX

- [ ] **PIX-MP-01**: Gerar código PIX para pagamento
- [ ] **PIX-MP-02**: Verificar renderização correta do QR Code
- [ ] **PIX-MP-03**: Simular pagamento bem-sucedido
- [ ] **PIX-MP-04**: Verificar timeout de pagamento
- [ ] **PIX-MP-05**: Cancelar transação pendente

#### 3.1.4. Boleto

- [ ] **BOL-MP-01**: Gerar boleto para pagamento
- [ ] **BOL-MP-02**: Verificar renderização de informações do boleto
- [ ] **BOL-MP-03**: Simular pagamento bem-sucedido
- [ ] **BOL-MP-04**: Verificar expiração do boleto
- [ ] **BOL-MP-05**: Cancelar boleto pendente

#### 3.1.5. Webhooks

- [ ] **WH-MP-01**: Receber notificação de pagamento aprovado
- [ ] **WH-MP-02**: Receber notificação de pagamento rejeitado
- [ ] **WH-MP-03**: Receber notificação de pagamento pendente
- [ ] **WH-MP-04**: Receber notificação de estorno/reembolso
- [ ] **WH-MP-05**: Testar retry de webhooks em caso de falha
- [ ] **WH-MP-06**: Validar assinatura de webhooks recebidos

### 3.2. PayPal

#### 3.2.1. Configuração

- [ ] **CFG-PP-01**: Validar conexão de teste usando credenciais de sandbox
- [ ] **CFG-PP-02**: Verificar persistência das configurações no banco de dados
- [ ] **CFG-PP-03**: Validar configuração de URLs de webhook e IPN
- [ ] **CFG-PP-04**: Testar interface administrativa com diferentes configurações

#### 3.2.2. Checkout PayPal

- [ ] **CO-PP-01**: Iniciar checkout PayPal com redirecionamento
- [ ] **CO-PP-02**: Completar autorização no PayPal
- [ ] **CO-PP-03**: Verificar captura de pagamento no retorno
- [ ] **CO-PP-04**: Testar cancelamento durante checkout
- [ ] **CO-PP-05**: Testar retorno com erro de pagamento
- [ ] **CO-PP-06**: Cancelar transação aprovada
- [ ] **CO-PP-07**: Reembolso total de transação
- [ ] **CO-PP-08**: Reembolso parcial de transação

#### 3.2.3. IPN (Instant Payment Notification)

- [ ] **IPN-PP-01**: Receber notificação IPN de pagamento completo
- [ ] **IPN-PP-02**: Receber notificação IPN de pagamento negado
- [ ] **IPN-PP-03**: Receber notificação IPN de pagamento pendente
- [ ] **IPN-PP-04**: Receber notificação IPN de reembolso
- [ ] **IPN-PP-05**: Validar autenticidade de IPN recebido

#### 3.2.4. Webhooks (API REST v2)

- [ ] **WH-PP-01**: Receber webhook de pagamento autorizado
- [ ] **WH-PP-02**: Receber webhook de captura completa
- [ ] **WH-PP-03**: Receber webhook de pagamento negado
- [ ] **WH-PP-04**: Receber webhook de reembolso
- [ ] **WH-PP-05**: Validar assinatura de webhooks recebidos

### 3.3. Interface Administrativa

- [ ] **ADM-01**: Visualização de transações com filtragem
- [ ] **ADM-02**: Detalhes de transação individual
- [ ] **ADM-03**: Gerenciamento de métodos de pagamento
- [ ] **ADM-04**: Configuração de gateways
- [ ] **ADM-05**: Visualização de webhooks recebidos
- [ ] **ADM-06**: Cancelamento de transação via painel
- [ ] **ADM-07**: Reembolso de transação via painel
- [ ] **ADM-08**: Verificação manual de status no gateway

### 3.4. Tratamento de Erros e Cenários de Borda

- [ ] **ERR-01**: Falha de conexão com gateway de pagamento
- [ ] **ERR-02**: Timeout na resposta do gateway
- [ ] **ERR-03**: Resposta mal-formada do gateway
- [ ] **ERR-04**: Token de autenticação expirado
- [ ] **ERR-05**: Webhook com assinatura inválida
- [ ] **ERR-06**: Tentativa de reembolso além do valor original
- [ ] **ERR-07**: Tentativa de cancelamento de transação já finalizada
- [ ] **ERR-08**: Múltiplas notificações para mesma transação

## 4. Validação de Segurança

### 4.1. Proteção de Dados

- [ ] **SEC-01**: Verificar sanitização de entrada em todos os endpoints
- [ ] **SEC-02**: Confirmar remoção de dados sensíveis em logs
- [ ] **SEC-03**: Validar proteção CSRF em todas as operações
- [ ] **SEC-04**: Verificar armazenamento seguro de credenciais
- [ ] **SEC-05**: Confirmar sanitização de saída em todas as interfaces

### 4.2. Autenticação e Autorização

- [ ] **SEC-06**: Validar controle de acesso à interface administrativa
- [ ] **SEC-07**: Verificar verificação de autenticidade de webhooks
- [ ] **SEC-08**: Confirmar que apenas administradores podem cancelar/reembolsar
- [ ] **SEC-09**: Validar proteção contra manipulação de parâmetros

### 4.3. Resistência a Ataques

- [ ] **SEC-10**: Teste de resistência a injection (SQL, JavaScript)
- [ ] **SEC-11**: Verificação de cabecalhos de segurança HTTP
- [ ] **SEC-12**: Teste de rate limiting para endpoints sensíveis
- [ ] **SEC-13**: Validação de idempotência de operações críticas

## 5. Métricas de Sucesso

- [ ] **MET-01**: 100% dos casos de teste críticos (core flow) com sucesso
- [ ] **MET-02**: 90%+ de todos os casos de teste com sucesso
- [ ] **MET-03**: 0 vulnerabilidades de segurança críticas ou altas
- [ ] **MET-04**: Tempo de processamento abaixo de 2 segundos para operações síncronas
- [ ] **MET-05**: Acurácia de 100% na reconciliação de status de transações

## 6. Processo de Teste

1. Configurar ambiente e credenciais de sandbox
2. Executar testes básicos de conectividade
3. Executar testes de fluxo principal para cada gateway
4. Executar testes de webhook e callbacks
5. Executar testes de interface administrativa
6. Executar testes de cenários de erro
7. Executar testes de segurança
8. Documentar resultados e correções necessárias
9. Resolver problemas identificados
10. Repetir testes até atingir métricas de sucesso

## 7. Responsabilidades

- **Configuração de Ambiente**: Equipe de DevOps
- **Testes Funcionais**: Equipe de QA
- **Testes de Segurança**: Equipe de Segurança
- **Correção de Bugs**: Equipe de Desenvolvimento
- **Aprovação Final**: Líder Técnico e PM

## 8. Cronograma

- **Configuração de Ambiente**: 1 dia
- **Testes Funcionais MercadoPago**: 2 dias
- **Testes Funcionais PayPal**: 2 dias
- **Testes de Interface Administrativa**: 1 dia
- **Testes de Segurança**: 2 dias
- **Correção de Bugs e Reteste**: 2 dias
- **Aprovação Final**: 1 dia

**Duração Total Estimada**: 8-10 dias úteis

## 9. Resultados e Documentação

- [ ] Lista completa de casos de teste com resultados
- [ ] Documentação de bugs encontrados e correções aplicadas
- [ ] Relatório de segurança
- [ ] Aprovação formal para prosseguir com deploy em homologação
- [ ] Documentação de configuração de ambiente para replicar em homologação