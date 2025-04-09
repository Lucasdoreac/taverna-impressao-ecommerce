# Documentação de Segurança: QuotationNotifier

## Função
O QuotationNotifier é responsável por enviar notificações seguras aos usuários quando suas cotações assíncronas são concluídas ou falham. Este componente implementa canais múltiplos de notificação (sistema e e-mail) e segue rigorosas práticas de segurança para garantir que dados sensíveis não sejam expostos e que apenas usuários autorizados recebam notificações.

## Implementação

### Proteções Implementadas

1. **Validação de Entrada Rigorosa**
   - Utiliza `InputValidationTrait` para validação consistente
   - Filtragem e sanitização de todos os dados de entrada
   - Verificação de tipos em todos os parâmetros

2. **Sanitização de Saída**
   - Sanitização HTML com `htmlspecialchars()` em todos os dados apresentados ao usuário
   - Filtragem específica de campos como e-mail com `filter_var()`
   - Sanitização de caminhos e URLs para prevenir injection

3. **Verificação de Autorização**
   - Verificação de propriedade dos dados (usuário só recebe notificações sobre suas próprias cotações)
   - Validação de existência e acesso antes do envio de notificações

4. **Tratamento Seguro de Erros**
   - Sanitização de mensagens de erro para remoção de informações técnicas sensíveis
   - Mensagens genéricas para o usuário final em caso de falhas técnicas
   - Registro detalhado para depuração interna

5. **Prevenção contra Vazamento de Informações**
   - Remoção de caminhos de arquivos completos
   - Ocultação de stack traces e detalhes de implementação
   - Verificação de permissões antes de enviar detalhes específicos

## Uso Correto

### Envio de Notificação de Conclusão
```php
// Após processamento bem-sucedido de uma cotação
$notifier = new QuotationNotifier();
$success = $notifier->sendCompletionNotification($task, $quotationResult);

if (!$success) {
    // Tratamento de falha no envio da notificação
    error_log("Falha ao notificar usuário sobre a cotação {$task['task_id']}");
}
```

### Envio de Notificação de Erro
```php
// Quando ocorre um erro durante o processamento de cotação
try {
    // Código de processamento...
} catch (Exception $e) {
    $notifier = new QuotationNotifier();
    $notifier->sendErrorNotification($task, $e->getMessage());
    
    // Continuar com tratamento de erro...
}
```

## Vulnerabilidades Mitigadas

- **Exposição de Informações Sensíveis**: Sanitização de todas as mensagens de erro e dados de saída para prevenir vazamento de detalhes de implementação, caminhos de arquivo ou informações do sistema.

- **XSS (Cross-Site Scripting)**: Uso consistente de `htmlspecialchars()` em todos os dados exibidos ao usuário, incluindo mensagens de notificação, nomes de modelo e mensagens de erro.

- **Template Injection**: Validação rigorosa dos dados antes de inserção em templates de e-mail ou mensagens de notificação.

- **Enumeração de Usuários**: Uso de mensagens genéricas que não confirmam a existência de usuários específicos para entidades não autenticadas.

- **Phishing via Notificações**: Verificação robusta da origem e conteúdo das notificações para evitar que atacantes enviem notificações falsas.

## Testes de Segurança

- **Teste 1: Proteção contra XSS em Notificações**  
  Descrição: Tentativa de injeção de código JavaScript em nomes de modelo para explorar XSS  
  Resultado: Sanitização bem-sucedida, todos os caracteres especiais são escapados

- **Teste 2: Verificação de Autorização**  
  Descrição: Tentativa de enviar notificação para ID de usuário diferente do proprietário do modelo  
  Resultado: Verificação impede o envio, registra tentativa de acesso não autorizado

- **Teste 3: Sanitização de Mensagens de Erro**  
  Descrição: Verificação da remoção de informações sensíveis de stack traces e caminhos de arquivo  
  Resultado: Mensagens de erro são devidamente sanitizadas antes de envio para usuários

- **Teste 4: Validação de Tipo de Notificação**  
  Descrição: Teste de injeção de tipos de notificação inválidos para forçar comportamentos inesperados  
  Resultado: Validação adequada, tipos inválidos são normalizados para valores padrão seguros

- **Teste 5: Prevenção de Envio em Massa**  
  Descrição: Tentativa de disparar múltiplas notificações simultaneamente para sobrecarregar o sistema  
  Resultado: O sistema de fila e registro de notificações impede abusos, limitando adequadamente solicitações

## Considerações de Segurança Adicionais

1. **Armazenamento de Logs**: Os logs de notificação contêm apenas informações essenciais para fins de auditoria, sem incluir conteúdo completo das mensagens ou dados sensíveis.

2. **Rate Limiting**: Implementação de limites de taxa para prevenir abuso do sistema de notificações.

3. **Expiração de Notificações**: Notificações do sistema têm prazo de validade para reduzir o risco de ações baseadas em informações desatualizadas.

4. **Tratamento de Falhas**: Sistema de retry para notificações importantes, com backoff exponencial para evitar sobrecarga.

5. **Monitoramento**: Registro de todas as tentativas de notificação para detecção de padrões anômalos que possam indicar tentativas de ataque.

## Logs e Auditoria

O componente mantém logs detalhados de todas as notificações enviadas, incluindo:
- ID da tarefa relacionada
- Canal de notificação utilizado
- Sucesso ou falha da operação
- ID do usuário destinatário
- Timestamps para análise de auditoria

Estes logs são armazenados na tabela `quotation_notification_log` e podem ser consultados para investigação de problemas ou para fins de compliance.