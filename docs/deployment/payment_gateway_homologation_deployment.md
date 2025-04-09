# Guia de Deploy da Integração com Gateways de Pagamento em Homologação

## 1. Objetivo

Este documento define os procedimentos de segurança e etapas necessárias para implantação dos componentes de integração com gateways de pagamento no ambiente de homologação, garantindo que todas as medidas de segurança sejam implementadas corretamente.

## 2. Pré-requisitos

### 2.1. Ambiente de Homologação

- [ ] Servidor dedicado com configuração similar à produção
- [ ] Rede segregada com proteção de firewall
- [ ] MySQL/MariaDB versão 8.0+ configurado
- [ ] PHP 8.1+ com extensões necessárias
- [ ] Certificado SSL válido para HTTPS
- [ ] Conexão externa para APIs dos gateways de pagamento

### 2.2. Repositório e Deployment

- [ ] Acesso ao repositório Git do projeto
- [ ] Pipeline de CI/CD configurado para homologação
- [ ] Diretório de backups para rollback se necessário
- [ ] Usuário de deploy com permissões limitadas

### 2.3. Credenciais e Recursos

- [ ] Contas de sandbox configuradas para todos os gateways
- [ ] Arquivo .env com variáveis de ambiente para homologação
- [ ] Credenciais seguras para os gateways de pagamento
- [ ] Acesso ao painel de administração em homologação

## 3. Procedimento de Deploy

### 3.1. Preparação

1. Verificar se todos os testes de sandbox foram executados com sucesso
2. Confirmar que não há vulnerabilidades de segurança pendentes
3. Revisar changelog de alterações a serem implantadas
4. Notificar equipe de testes sobre o deploy planejado
5. Criar backup da base de dados de homologação atual

### 3.2. Configuração do Ambiente

1. Configurar variáveis de ambiente:
   ```env
   # Ambiente
   APP_ENV=homologation
   
   # Configurações do banco de dados
   DB_HOST=homolog-db.tavernaimpressao.local
   DB_NAME=taverna_homolog
   DB_USER=[USUARIO_SEGURO]
   DB_PASS=[SENHA_SEGURA]
   
   # Configurações de gateways - SEMPRE em modo sandbox
   PAYMENT_SANDBOX_MODE=true
   
   # Mercado Pago
   MP_SANDBOX_PUBLIC_KEY=[CHAVE_PUBLICA_SANDBOX]
   MP_SANDBOX_ACCESS_TOKEN=[TOKEN_ACESSO_SANDBOX]
   
   # PayPal
   PAYPAL_SANDBOX_CLIENT_ID=[CLIENT_ID_SANDBOX]
   PAYPAL_SANDBOX_SECRET=[SECRET_SANDBOX]
   
   # Configurações de segurança
   CSRF_TOKEN_LIFETIME=3600
   SESSION_SECURE=true
   COOKIE_HTTPONLY=true
   COOKIE_SAMESITE=Lax
   ```

2. Configurar URLs de callback:
   ```
   # MercadoPago Webhook
   https://homologacao.tavernaimpressao.com.br/webhook/mercadopago
   
   # PayPal Webhook
   https://homologacao.tavernaimpressao.com.br/webhook/paypal
   
   # PayPal IPN
   https://homologacao.tavernaimpressao.com.br/payment/ipn/paypal
   ```

3. Configurar cabeçalhos de segurança no servidor web:
   ```
   # Apache (.htaccess ou vhost)
   Header set X-Content-Type-Options "nosniff"
   Header set X-Frame-Options "SAMEORIGIN"
   Header set X-XSS-Protection "1; mode=block"
   Header set Referrer-Policy "strict-origin-when-cross-origin"
   Header set Content-Security-Policy "default-src 'self'; script-src 'self' https://secure.mercadopago.com https://www.paypal.com https://www.sandbox.paypal.com 'unsafe-inline'; connect-src 'self' https://api.mercadopago.com https://api.sandbox.mercadopago.com https://api-m.sandbox.paypal.com; frame-src https://www.sandbox.paypal.com https://sandbox.mercadopago.com;"
   ```

### 3.3. Etapas de Implantação

1. Executar deploy do código no ambiente de homologação:
   ```bash
   # Exemplo para deploy manual
   cd /var/www/homologacao.tavernaimpressao.com.br
   git fetch origin
   git checkout homolog
   git pull
   composer install --no-dev --optimize-autoloader
   ```

2. Atualizar esquema do banco de dados:
   ```bash
   # Aplicar migrações pendentes
   php scripts/migrate.php --env=homologation
   ```

3. Atualizar configurações de gateways no banco:
   ```sql
   -- Exemplo para inserir/atualizar configurações
   INSERT INTO settings (setting_key, setting_value, created_at, updated_at)
   VALUES ('payment.mercadopago.config', '{"active":true,"sandbox":true,"display_name":"MercadoPago","public_key":"CHAVE_PUBLICA_SANDBOX"}', NOW(), NOW())
   ON DUPLICATE KEY UPDATE setting_value = '{"active":true,"sandbox":true,"display_name":"MercadoPago","public_key":"CHAVE_PUBLICA_SANDBOX"}', updated_at = NOW();
   ```

4. Limpar caches:
   ```bash
   # Limpar caches da aplicação
   php scripts/cache-clear.php --env=homologation
   ```

5. Ajustar permissões:
   ```bash
   # Garantir permissões corretas
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   chmod -R 775 storage
   chmod -R 775 logs
   chown -R www-data:www-data .
   ```

### 3.4. Verificação Pós-Deploy

1. Executar verificações de segurança:
   ```bash
   # Verificar configurações de segurança
   php scripts/security-check.php --env=homologation
   
   # Verificar conectividade com gateways
   php scripts/testing/gateway_sandbox_validator.php --verbose
   ```

2. Verificar logs de erros:
   ```bash
   # Verificar se não há erros após deploy
   tail -n 100 logs/homologation.log
   ```

3. Verificar funcionamento da interface administrativa:
   - Acessar painel administrativo
   - Verificar página de configuração de gateways
   - Validar conexões de teste

## 4. Testes de Aceitação

### 4.1. Testes de Fluxo Principal

1. Realizar testes de checkout com MercadoPago:
   - Cartão de crédito
   - Boleto
   - PIX

2. Realizar testes de checkout com PayPal:
   - Checkout padrão
   - Retorno e captura

### 4.2. Testes Administrativos

1. Verificar listagem de transações
2. Verificar detalhes de transação
3. Testar reembolso de transação
4. Testar cancelamento de transação
5. Verificar logs de webhooks recebidos

### 4.3. Testes de Segurança

1. Verificar implementação de CSRF em todos os formulários
2. Validar sanitização de entrada e saída
3. Confirmar que credenciais de produção não estão expostas
4. Verificar aplicação de cabeçalhos de segurança
5. Validar que apenas admins podem acessar interfaces administrativas

## 5. Configuração de Monitoramento

### 5.1. Logs

1. Configurar logs de transações:
   ```php
   // Nível de log para homologação
   $config['log_level'] = 'debug';
   
   // Rotação de logs
   $config['log_max_files'] = 30;
   ```

2. Configurar logs de webhooks:
   ```php
   // Armazenar payloads completos em homologação
   $config['webhook_logging'] = 'full';
   ```

### 5.2. Alertas

1. Configurar alertas para erros críticos:
   ```php
   // Notificações por email em homologação
   $config['payment_error_notifications'] = [
     'to' => 'equipe-pagamentos@tavernaimpressao.com.br',
     'subject_prefix' => '[HOMOLOG] Erro Pagamento - ',
     'include_payload' => true,
     'min_severity' => 'error'
   ];
   ```

2. Configurar dashboard de monitoramento:
   - Habilitar painel de status de transações
   - Configurar exibição de métricas de latência
   - Ativar notificações de webhooks falhos

## 6. Procedimento de Rollback

Em caso de problemas críticos, seguir este procedimento de rollback:

1. Desativar processamento de transações:
   ```sql
   -- Desativar gateways temporariamente
   UPDATE settings 
   SET setting_value = JSON_SET(setting_value, '$.active', false)
   WHERE setting_key LIKE 'payment.%.config';
   ```

2. Reverter código para versão anterior:
   ```bash
   git checkout [COMMIT_ANTERIOR]
   composer install --no-dev --optimize-autoloader
   ```

3. Restaurar banco de dados:
   ```bash
   mysql -u [USUARIO] -p [BANCO] < backups/pre_deploy_[DATA].sql
   ```

4. Notificar equipe de testes sobre o rollback

## 7. Aprovação Final

Após todos os testes e verificações, o processo formal de aprovação inclui:

1. Assinatura do documento de validação por:
   - Líder Técnico
   - QA Lead
   - Responsável por Segurança
   - Product Manager

2. Documentação dos resultados de testes

3. Verificação final da lista de pendências e issues

O sistema será considerado pronto para produção apenas após completar com sucesso todas as etapas de homologação e receber a aprovação formal de todas as partes envolvidas.

## 8. Contatos para Suporte

- **Suporte Técnico**: suporte-tecnico@tavernaimpressao.com.br
- **Segurança da Informação**: seguranca@tavernaimpressao.com.br
- **MercadoPago Developers**: developers.mercadopago.com
- **PayPal Developer Support**: developer.paypal.com/support

## 9. Referências

- [Documentação da API MercadoPago](https://www.mercadopago.com.br/developers/pt/docs/checkout-api/landing)
- [Documentação da API PayPal](https://developer.paypal.com/docs/api/overview/)
- [Guia de Segurança OWASP para Processamento de Pagamentos](https://owasp.org/www-project-web-security-testing-guide/latest/4-Web_Application_Security_Testing/09-Testing_for_Business_Logic/10-Test_Payment_Functionality)
- [PCI DSS Compliance Guidelines](https://www.pcisecuritystandards.org/document_library)