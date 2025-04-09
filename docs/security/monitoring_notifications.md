# Documentação de Segurança: Sistema de Monitoramento e Notificações

## Função
O sistema de monitoramento e notificações fornece infraestrutura para acompanhamento em tempo real do desempenho da aplicação, detecção precoce de anomalias, alerta para eventos de segurança e comunicação com os usuários através de múltiplos canais.

## Componentes

### 1. Sistema de Monitoramento

#### 1.1 PerformanceMonitor
Classe responsável pela coleta e registro de métricas de desempenho durante a execução da aplicação:

- **Medição de tempo de resposta**: Captura detalhada do tempo de processamento das requisições
- **Medição de uso de memória**: Monitoramento de alocação e pico de memória
- **Rastreamento de consultas SQL**: Tempo de execução e contagem de consultas
- **Checkpoints de execução**: Análise detalhada de pontos específicos no fluxo de processamento

#### 1.2 PrintQueueMonitor
Especialização para o monitoramento da fila de impressão 3D:

- **Estatísticas da fila**: Métricas detalhadas por status, prioridade e tempo de espera
- **Alertas de SLA**: Detecção de itens com tempo de espera excedendo limites predefinidos
- **Dashboard em tempo real**: Visualização atualizada do estado da fila de impressão
- **Tendências e previsões**: Análise estatística para planejamento de capacidade

### 2. Sistema de Notificações

#### 2.1 NotificationManager
Gerencia o envio de notificações para usuários através de múltiplos canais:

- **Entrega multi-canal**: Banco de dados, push e email
- **Persistência**: Armazenamento de todas as notificações para consulta futura
- **Personalização**: Suporte a tipos de notificação (info, warning, success, error)
- **Filtros por grupo**: Direcionamento para grupos específicos de usuários (admin, manager, printer_operator, customer)

#### 2.2 PushService
Implementação específica para notificações push via WebSockets e Service Workers:

- **Gerenciamento de inscrições**: Registro e manutenção de endpoints push
- **Payload seguro**: Sanitização e validação de dados enviados por push
- **Registro de entrega**: Rastreamento detalhado de sucessos e falhas
- **Confiabilidade**: Detecção e recuperação automática de endpoints expirados

## Implementação

### Monitoramento de Performance

```php
// Inicialização do monitoramento no início da requisição
PerformanceMonitor::initialize();

// Registro de checkpoints durante o processamento
$checkpoint = PerformanceMonitor::addCheckpoint('after_validation');

// Medição de operações específicas
$opId = PerformanceMonitor::startOperation('database_query');
// Executar operação...
$executionTime = PerformanceMonitor::endOperation($opId, $success);

// Registro de consultas SQL
PerformanceMonitor::logSqlQuery($query, $executionTime, $rowCount);

// Registro de uso de memória
PerformanceMonitor::logMemoryUsage('after_template_rendering');

// Finalização e registro de métricas (via register_shutdown_function)
PerformanceMonitor::finalize();
```

### Notificações Push

```php
// Envio de notificação a um usuário específico
$notificationManager = NotificationManager::getInstance();
$notificationId = $notificationManager->createNotification(
    $userId,
    'Impressão concluída',
    'Seu modelo "Suporte para Headphone" foi impresso com sucesso!',
    'success',
    ['model_id' => 456, 'print_job_id' => 789],
    ['database', 'push', 'email']
);

// Envio de notificação para um grupo de usuários
$notificationManager->createSystemNotification(
    'Manutenção programada',
    'O sistema ficará indisponível entre 02:00 e 04:00 para manutenção.',
    'warning',
    ['customer', 'printer_operator']
);
```

## Camadas de Segurança

### Entrada de Dados

- **Sanitização**: Todos os dados de entrada são validados e sanitizados antes do uso
- **Validação de tokens**: Verificação rigorosa de tokens CSRF para operações administrativas
- **Validação de permissões**: Verificação de permissões para acesso a métricas e envio de notificações

### Saída de Dados

- **Sanitização de saída**: Todas as mensagens e dados são sanitizados antes da exibição
- **Dados sensíveis**: Exclusão automática de informações sensíveis de logs e notificações
- **Segurança de payload**: Limitação de tamanho e conteúdo de notificações push para prevenir ataques

### Persistência

- **Prepared statements**: Uso exclusivo de prepared statements para operações de banco de dados
- **Isolação**: Uso de transações para garantir integridade dos dados
- **Auditoria**: Registro detalhado de todas as operações para fins de auditoria

## Mitigação de Vulnerabilidades

### XSS (Cross-Site Scripting)
- Sanitização de todos os dados em mensagens de notificação
- Validação de payloads para notificações push
- Uso de Content-Security-Policy para limitar execução de scripts

### CSRF (Cross-Site Request Forgery)
- Tokens CSRF para todas as operações administrativas
- Validação de origem de requisições para endpoints de API
- Headers de segurança para prevenir ataques de clickjacking

### Vazamento de Informações
- Mensagens de erro genéricas para usuários finais
- Validação de permissões para acesso a métricas de performance
- Filtragem de dados sensíveis em logs

## Testes de Segurança

### Proteção contra Injeção
- **Test**: Inserção de payloads XSS em campos de notificação
- **Resultado**: Codificação apropriada de caracteres especiais, prevenindo execução de scripts

### Validação de Entrada
- **Test**: Envio de dados malformados para API de notificações
- **Resultado**: Rejeição de dados inválidos com mensagens de erro apropriadas

### Autenticação e Autorização
- **Test**: Tentativa de acesso a métricas sem permissões adequadas
- **Resultado**: Redirecionamento para página de acesso negado

### Proteção de Dados
- **Test**: Verificação de dados sensíveis em respostas JSON
- **Resultado**: Informações sensíveis corretamente omitidas ou mascaradas

## Configuração e Implantação

### Requisitos de Sistema
- PHP 7.4+
- MySQL 5.7+
- Suporte a Service Workers nos navegadores dos clientes (para notificações push)

### Tabelas de Banco de Dados
- `performance_logs`: Métricas de desempenho geral
- `error_logs`: Registro de erros da aplicação
- `resource_metrics`: Métricas de recursos do sistema
- `database_metrics`: Métricas de desempenho do banco de dados
- `security_events`: Eventos de segurança
- `notifications`: Notificações do sistema
- `notification_targets`: Destinatários das notificações
- `notification_deliveries`: Registro de entregas de notificações
- `user_notifications`: Relação entre usuários e notificações
- `push_subscriptions`: Inscrições para notificações push
- `push_delivery_log`: Registro de entregas push

### Considerações de Performance
- Implementação de limpeza automática de dados antigos
- Índices otimizados para consultas frequentes
- Cache para métricas solicitadas frequentemente
- Processamento assíncrono para envio de notificações em massa

## Manutenção e Monitoramento

### Monitoramento Contínuo
- Verificação periódica de taxas de erro
- Alertas para anomalias de desempenho
- Análise de tendências de uso de recursos

### Procedimentos de Manutenção
- Limpeza regular de dados históricos não essenciais
- Verificação periódica de subscrições push inativas
- Validação regular de integridade do banco de dados

### Plano de Recuperação
- Procedimentos para reinicialização do sistema de monitoramento
- Recuperação de falhas no envio de notificações
- Protocolos para situações de degradação de desempenho
