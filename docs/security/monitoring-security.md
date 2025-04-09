# Documentação de Segurança: Sistema de Monitoramento e Notificações

## Função

O Sistema de Monitoramento e Notificações implementa monitoramento de desempenho em tempo real, testes de carga e notificações push, fornecendo visibilidade sobre o estado da aplicação, especialmente para o Sistema de Fila de Impressão, enquanto mantém os padrões de segurança estabelecidos.

## Componentes de Segurança

### 1. PerformanceMonitor

Classe responsável por coletar e analisar métricas de desempenho, com mecanismos para alertar sobre condições anômalas.

#### Proteções Implementadas:
- **Mitigação de DoS**: Implementa taxa de amostragem configurável para evitar sobrecarga do sistema
- **Limites de alertas**: Thresholds configuráveis para evitar fluxo excessivo de alertas
- **Isolamento de erros**: Supressão silenciosa de falhas na instrumentação para evitar impacto na aplicação principal
- **Logs sanitizados**: Toda saída é sanitizada para evitar injeção nos logs

### 2. PrintQueueMonitor

Especialização do monitor para o Sistema de Fila de Impressão com proteções específicas para este domínio.

#### Proteções Implementadas:
- **Autenticação em endpoints**: Verificação rigorosa antes do acesso a dados sensíveis
- **Validação estruturada**: Verificação sistemática de estado da fila e consistência
- **Níveis de alerta graduados**: Alertas com escalabilidade (ALERTA → CRÍTICO) para evitar alarme falso
- **Tratamento seguro de falhas de consulta**: Tratamento adequado de exceções de banco de dados

### 3. NotificationManager

Gerencia a distribuição de notificações por diferentes canais, mantendo proteção contra vazamento de informações.

#### Proteções Implementadas:
- **Sanitização de conteúdo**: Todo conteúdo é sanitizado antes da exibição
- **Isolamento por usuário**: Notificações só são visíveis ao destinatário
- **Verificação de propriedade**: Verificação adicional antes de marcar notificações como lidas
- **Controle granular de canais**: Configuração detalhada por tipo de notificação

### 4. PushNotificationProvider

Implementa o protocolo Web Push para notificações em navegadores com múltiplas camadas de segurança.

#### Proteções Implementadas:
- **Validação criptográfica**: Uso adequado de chaves VAPID para autenticação
- **Limite de payload**: Restrição de tamanho para evitar DoS (4KB)
- **Desativação automática**: Endpoints inválidos são desativados após falhas recorrentes
- **Armazenamento seguro de chaves**: Chaves de autenticação são armazenadas de forma segura

## Fluxo de Autenticação e Autorização

### Monitoramento Administrativo

1. O usuário se autentica na aplicação através do SecurityManager
2. CsrfProtection valida token CSRF em todas as requisições POST e AJAX
3. MonitoringController verifica permissões administrativas através do flag `user_is_admin` na sessão
4. Acesso negado (403) é retornado caso as verificações falhem

### API de Notificações

1. NotificationsApiController verifica autenticação através do SecurityManager
2. Token CSRF é validado via cabeçalho HTTP X-CSRF-Token para proteger contra CSRF em APIs
3. Verificação de propriedade é realizada para garantir que o usuário só acesse suas próprias notificações
4. Validação rigorosa de parâmetros de entrada usando InputValidationTrait

### Push API

1. JavaScript de cliente valida suporte do navegador antes de tentar registrar service worker
2. Token CSRF é incluído em todas as requisições para API de push
3. Chave pública VAPID é obtida do servidor de forma segura
4. O servidor verifica a autenticidade das subscrições antes de enviar notificações

## Proteções Específicas para Testes de Carga

1. **Limitação de recursos**:
   - Número máximo de usuários virtuais configurável (limite 100)
   - Número máximo de iterações configurável (limite 1000)
   - Tempo mínimo de rampa configurável (mínimo 1s)
   - Timeout máximo configurável (limite 120s)

2. **Registro de auditoria**:
   - Logs detalhados de testes executados
   - Armazenamento de resultados com timestamp
   - Saída formatada em JSON para análise posterior

3. **Prevenção de sobrecarga**:
   - Validação de configuração antes da execução
   - Execução controlada de iterações
   - Monitoramento de memória durante testes

## Proteções em Banco de Dados

1. **Prepared Statements**:
   - Todas as queries utilizam prepared statements para prevenir SQL Injection
   - Parâmetros são fortemente tipados (PARAM_INT para IDs)

2. **Índices de Performance**:
   - Índices apropriados em tabelas de monitoramento e notificações
   - Índices compostos para consultas frequentes (ex: uq_endpoint_user)

3. **Limitação de Resultados**:
   - Limites explícitos em consultas de histórico para evitar sobrecarga

4. **Transações**:
   - Uso de transações para garantir integridade (especialmente em migrations)

## Mitigação de Vulnerabilidades Específicas

### XSS (Cross-Site Scripting)

- Sanitização de saída em todas as notificações com htmlspecialchars
- Content-Security-Policy através de SecurityHeaders
- Sanitização adicional no cliente antes da exibição

### CSRF (Cross-Site Request Forgery)

- Tokens CSRF validados em todas as requisições POST
- Tokens específicos para APIs via X-CSRF-Token header
- Cabeçalhos apropriados (X-Content-Type-Options: nosniff)

### Information Disclosure

- Mensagens de erro genéricas para o usuário
- Logs detalhados apenas para administradores
- Queries de monitoramento limitadas ao escopo do usuário

### DoS (Denial of Service)

- Rate limiting implícito através de taxa de amostragem
- Validação de tamanho em payloads de notificação push
- Validação de carga em testes para prevenir sobrecarga

## Testes de Segurança Realizados

| Teste | Descrição | Resultado |
|-------|-----------|-----------|
| Validação CSRF | Tentativa de enviar requisição sem token CSRF | PASSOU - Requisição rejeitada com 403 |
| Isolamento de Notificações | Tentativa de acessar notificações de outro usuário | PASSOU - Notificações filtradas por usuário_id |
| Validação de Entrada | Envio de dados inválidos para API | PASSOU - Rejeição com mensagem genérica |
| Carga Excessiva | Testes com configuração além dos limites | PASSOU - Configuração rejeitada com erro específico |
| XSS em Notificações | Tentativa de injetar HTML em notificações | PASSOU - Conteúdo HTML é sanitizado |

## Recomendações Futuras

1. **Aprimoramento de Monitoramento**:
   - Implementar detecção de anomalias baseada em machine learning
   - Expandir cobertura de métricas para componentes adicionais
   - Integrar com sistemas de monitoramento externos

2. **Segurança Avançada**:
   - Implementar assinatura criptográfica de notificações push
   - Adicionar verificação de integridade em resultados de monitoramento
   - Implementar logging imutável para auditoria

3. **Performance e Escalabilidade**:
   - Implementar sharding de tabelas de monitoramento para alta escala
   - Adicionar TTL (Time-To-Live) para limpeza automática de dados antigos
   - Implementar cache para métricas frequentemente acessadas

## Uso Correto

### Monitoramento de Performance

```php
// Iniciar monitoramento no início do script
$monitor = PerformanceMonitor::getInstance();

// Inicializar métricas da requisição
$monitor->initializeRequestMetrics();

// Monitorar operação específica
$monitor->startOperation('database_query');
// ... executar operação ...
$elapsedTime = $monitor->endOperation('database_query');

// Registrar erro
if ($error) {
    $monitor->logError('Mensagem de erro', 'database', __FILE__, __LINE__);
}

// Finalizar ao fim do script
$monitor->finalizeRequest();
```

### Monitoramento da Fila de Impressão

```php
// Obter monitor da fila
$queueMonitor = PrintQueueMonitor::getInstance();

// Atualizar estado
$queueState = $queueMonitor->updateQueueState();

// Verificar métricas específicas
if ($queueState['pending_items'] > 50) {
    // Ação para fila grande
}

// Obter histórico de alertas
$alerts = $queueMonitor->getAlertHistory(10);
```

### Envio de Notificações

```php
// Obter gerenciador de notificações
$notificationManager = NotificationManager::getInstance();

// Enviar notificação para usuário
$notificationManager->sendUserNotification(
    $userId,
    'print_status',
    'Impressão Concluída',
    'Seu modelo foi impresso com sucesso',
    [
        'link' => "/minha-conta/impressoes/{$printJobId}",
        'priority' => 'high'
    ]
);

// Enviar notificação sobre status de impressão
$notificationManager->sendPrintStatusNotification(
    $userId,
    $printJobId,
    'completed'
);
```

## Vulnerabilidades Mitigadas

- **XSS Persistente**: Sanitização rigorosa de conteúdo de notificações
- **CSRF em APIs**: Implementação de tokens específicos para APIs
- **Vazamento de Informações**: Isolamento de dados por usuário
- **DoS por Sobrecarga**: Limites e validação em testes de carga
- **SQL Injection**: Uso consistente de prepared statements
- **Acesso Não Autorizado**: Verificações de autenticação e autorização
- **Push Notification Spoofing**: Validação criptográfica de endpoints

## Referências

1. OWASP Top 10 Web Application Security Risks
2. Web Push API Security Considerations (MDN)
3. NIST Guidelines for Web Performance Monitoring
4. CWE-352: Cross-Site Request Forgery
5. CWE-79: Improper Neutralization of Input During Web Page Generation ('Cross-site Scripting')
