# Prompt de Continuidade: Sistema de Processamento Assíncrono

## Contexto Atual

A implementação do sistema de processamento assíncrono foi concluída com êxito, abrangendo os seguintes componentes principais:

1. API de Verificação de Status (`StatusCheckApiController`, `StatusRepository`)
2. Sistema de Rate Limiting (`RateLimiter`, integração com `SecurityManager`)
3. Interface de Usuário para Acompanhamento (`status_tracking.php`, `StatusTrackingController`)
4. Testes de Concorrência e Carga (`ConcurrencyTest`, `k6-load-test.js`)

Todos os componentes foram desenvolvidos seguindo rigorosamente os guardrails de segurança estabelecidos, incluindo validação de entrada, proteção CSRF, prepared statements, sanitização de saída, verificação de permissões e cabeçalhos HTTP seguros.

## Próximas Tarefas Prioritárias

### 1. Sistema de Notificações (Próxima Implementação)

O foco imediato deve ser na implementação do sistema de notificações para o processamento assíncrono, que deve:

- Implementar notificações por email utilizando templates HTML responsivos
- Suportar notificações em tempo real na interface do usuário (via WebSockets ou polling)
- Garantir a entrega de notificações com sistema de persistência e retentativas
- Permitir configurações de preferência por usuário (canais, frequência, etc.)
- Integrar-se com o sistema de logging para auditoria completa
- Implementar todas as medidas de segurança conforme os guardrails estabelecidos

### 2. Deploy em Ambiente de Homologação

A preparação para deploy em homologação deve incluir:

- Verificação de dependências e compatibilidade entre componentes
- Configuração específica para ambiente de homologação (database, redis, etc.)
- Implementação de scripts de migração de dados (se necessário)
- Preparação de testes de aceitação automatizados
- Definição de métricas de monitoramento para avaliação de performance

### 3. Validação Final de Segurança

Antes da integração completa, é necessária uma validação rigorosa de segurança:

- Análise estática de código nos novos componentes
- Testes de penetração focados nas APIs assíncronas
- Verificação de configurações de CORS, CSP e outros cabeçalhos de segurança
- Análise de vulnerabilidades nas bibliotecas e dependências utilizadas
- Validação de registro e monitoramento adequados para detecção de abusos

## Instruções para Desenvolvimento

### Padrões de Design para Sistema de Notificações

```php
// Interface base para estratégias de notificação
interface NotificationStrategy {
    public function send(string $userId, array $data): bool;
    public function validatePayload(array $data): bool;
}

// Implementações específicas para cada canal
class EmailNotificationStrategy implements NotificationStrategy {
    use InputValidationTrait;
    
    private $mailer;
    private $templateEngine;
    
    public function __construct(Mailer $mailer, TemplateEngine $templateEngine) {
        $this->mailer = $mailer;
        $this->templateEngine = $templateEngine;
    }
    
    public function send(string $userId, array $data): bool {
        // Implementação com validação, templates e logging
    }
    
    public function validatePayload(array $data): bool {
        // Validação específica para emails
    }
}

// Manager central
class NotificationManager {
    private $strategies = [];
    private $securityManager;
    private $logger;
    
    public function registerStrategy(string $channel, NotificationStrategy $strategy): void {
        $this->strategies[$channel] = $strategy;
    }
    
    public function notify(string $userId, string $channel, array $data): bool {
        // Implementação com verificações de segurança, validação e logging
    }
}
```

### Estrutura de Banco de Dados para Notificações

```sql
CREATE TABLE notification_templates (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(50) NOT NULL UNIQUE,
    channel VARCHAR(20) NOT NULL,
    subject VARCHAR(255),
    body TEXT NOT NULL,
    variables JSON NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_key (template_key),
    INDEX idx_channel (channel)
) ENGINE=InnoDB;

CREATE TABLE notification_queue (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    channel VARCHAR(20) NOT NULL,
    template_key VARCHAR(50) NOT NULL,
    data JSON NOT NULL,
    status ENUM('pending', 'processing', 'delivered', 'failed') NOT NULL DEFAULT 'pending',
    attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_attempt TIMESTAMP NULL,
    next_attempt TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status_next_attempt (status, next_attempt),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

CREATE TABLE user_notification_preferences (
    user_id INT UNSIGNED NOT NULL,
    channel VARCHAR(20) NOT NULL,
    enabled BOOLEAN NOT NULL DEFAULT TRUE,
    frequency VARCHAR(20) NOT NULL DEFAULT 'immediate',
    quiet_hours_start TIME,
    quiet_hours_end TIME,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, channel),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;
```

## Considerações de Segurança Específicas

1. **Validação de Conteúdo de Notificações**
   - Sanitizar variáveis em templates para prevenir XSS
   - Validar URLs incluídos em notificações
   - Implementar limites de tamanho para mensagens

2. **Proteção de Informações Sensíveis**
   - Não incluir dados sensíveis completos em notificações
   - Utilizar tokens de acesso únicos para links em notificações
   - Implementar expiração de links de acesso

3. **Prevenção de Abuso**
   - Implementar rate limiting para envios de notificações
   - Monitorar padrões anômalos de notificações
   - Implementar confirmação de inscrição para canais externos

4. **Auditoria e Logging**
   - Registrar todas as tentativas de envio de notificações
   - Manter histórico de entregas e falhas
   - Implementar alertas para falhas recorrentes

## Observações Finais

O sistema de processamento assíncrono implementado fornece a base para uma arquitetura escalável e segura. A integração do sistema de notificações será o próximo passo crítico para completar a experiência do usuário e garantir transparência no fluxo de cotações.

Todo o desenvolvimento deve continuar seguindo os guardrails de segurança estabelecidos e o padrão de documentação técnica iniciado com os componentes atuais.
