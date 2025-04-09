# Documentação de Segurança: PerformanceAlertingService

## Função

O `PerformanceAlertingService` é um componente crítico que funciona como ponte entre o sistema de monitoramento de desempenho e o sistema de notificações. Sua principal função é monitorar processos assíncronos, detectar anomalias de performance e gerar alertas apropriados para administradores e usuários afetados.

## Implementação

O serviço implementa múltiplas camadas de segurança:

### 1. Validação de Entrada
- Utiliza o `InputValidationTrait` para validação rigorosa de todos os parâmetros
- Implementa verificações de tipo e intervalo para todas as entradas
- Garante que apenas valores válidos são processados

### 2. Proteção contra Injeção
- Uso exclusivo de prepared statements para consultas SQL
- Sanitização de dados antes do armazenamento e exibição
- Validação estrita de IDs de processos

### 3. Proteção contra Ataques XSS
- Sanitização completa de todos os campos de texto via `htmlspecialchars()`
- Implementação de método dedicado `sanitizeAlertData()` para garantir sanitização recursiva
- Codificação segura de entidades HTML em todas as mensagens de notificação

### 4. Mitigação de Exposição de Dados Sensíveis
- Filtragem de dados sensíveis antes da geração de notificações
- Mensagens de erro genéricas para usuários finais
- Logs detalhados apenas para administradores e sistemas internos

### 5. Proteção contra Abusos
- Limitação de verificações via intervalo mínimo (`$checkInterval`)
- Cache de informações para reduzir consultas redundantes
- Transações para garantir atomicidade de operações críticas

## Diagrama de Sequência

```
sequenceDiagram
    participant CP as CronProcess
    participant PAS as PerformanceAlertingService
    participant PM as PerformanceMonitor
    participant NM as NotificationManager
    participant DB as Database
    
    Note over CP,DB: Verificação de processos monitorados
    CP->>PAS: checkMonitoredProcesses()
    PAS->>DB: Consulta processos ativos
    DB-->>PAS: Retorna detalhes dos processos
    
    loop Para cada processo
        PAS->>PAS: Verifica timeout e progresso
        
        alt Se detectar anomalia
            PAS->>PAS: processAlert()
            PAS->>NM: createNotification()
            NM-->>PAS: confirmação
            PAS->>DB: Registra alerta
        end
    end
    
    PAS->>DB: Atualiza status de verificação
    PAS-->>CP: Retorna estatísticas
```

## Uso Correto

### Monitorando um Processo Assíncrono

```php
// Obter instância do serviço
$performanceAlertingService = new PerformanceAlertingService(
    PerformanceMonitor::getInstance(),
    NotificationManager::getInstance(),
    new NotificationThresholds($pdo),
    $pdo,
    $logger
);

// Iniciar monitoramento de um processo
$processId = 'proc_1234567890';
$maxDuration = 3600; // 1 hora em segundos
$startTime = time();

$success = $performanceAlertingService->monitorAsyncProcess(
    $processId,
    $maxDuration,
    $startTime
);

if (!$success) {
    // Tratar erro de monitoramento
    error_log("Falha ao iniciar monitoramento do processo {$processId}");
}
```

### Verificação de Processos em Cron Job

```php
// Em um script executado periodicamente por cron
$alertingService = new PerformanceAlertingService(
    PerformanceMonitor::getInstance(),
    NotificationManager::getInstance(),
    new NotificationThresholds($pdo),
    $pdo,
    $logger
);

// Verificar todos os processos monitorados
$result = $alertingService->checkMonitoredProcesses();

// Registrar estatísticas
if ($result['success']) {
    error_log("Verificação concluída: {$result['checked']} processos verificados, {$result['alerts']} alertas gerados");
} else {
    error_log("Falha na verificação de processos: " . ($result['error'] ?? 'erro desconhecido'));
}
```

### Processamento de Medições de Performance

```php
// Em um controlador ou serviço que captura métricas
$metrics = [
    'execution_time' => 2.5, // segundos
    'memory_usage' => 15728640, // bytes (15MB)
    'database_queries' => 35,
    'request_id' => 'req_9876543210'
];

$alertingService->processPerformanceMeasurement(
    'checkout_process', // contexto
    $metrics
);
```

## Vulnerabilidades Mitigadas

### 1. Injeção SQL
- **Mitigação**: Uso exclusivo de prepared statements para todas as consultas
- **Implementação**: Parâmetros sempre passados como argumentos separados para `execute()`
- **Verificação**: Nenhuma concatenação direta de valores em strings SQL

### 2. Cross-Site Scripting (XSS)
- **Mitigação**: Sanitização robusta de todos os dados de saída
- **Implementação**: Método dedicado `sanitizeAlertData()` com `htmlspecialchars()`
- **Impacto**: Impede a execução de scripts maliciosos em notificações e alertas

### 3. Exposição de Informações Sensíveis
- **Mitigação**: Filtragem de dados sensíveis em logs e notificações
- **Implementação**: Mensagens de erro detalhadas apenas em logs internos
- **Impacto**: Previne vazamento de detalhes da implementação e dados confidenciais

### 4. Race Conditions
- **Mitigação**: Uso de transações para operações críticas de banco de dados
- **Implementação**: Método `saveMonitoredProcesses()` com controle de transação
- **Impacto**: Garante atomicidade e consistência dos dados

### 5. Denial of Service (DoS)
- **Mitigação**: Limitação de verificações por intervalo de tempo
- **Implementação**: Verificação do timestamp `lastCheck` antes de processamento
- **Impacto**: Previne sobrecarga do sistema por verificações excessivas

## Testes de Segurança

### 1. Teste de Validação de Entrada
- **Descrição**: Verificação da eficácia da validação de parâmetros
- **Método**: Envio de parâmetros maliciosos, nulos e valores fora de intervalo
- **Resultado**: Todos os parâmetros inválidos foram rejeitados ou sanitizados
- **Status**: APROVADO

### 2. Teste de Proteção contra SQL Injection
- **Descrição**: Verificação da resistência contra ataques de injeção SQL
- **Método**: Tentativa de injeção de comandos SQL em IDs de processo
- **Resultado**: Todas as tentativas foram bloqueadas pela validação de entrada e uso de prepared statements
- **Status**: APROVADO

### 3. Teste de Proteção contra XSS
- **Descrição**: Verificação da sanitização de dados em notificações
- **Método**: Inclusão de scripts e tags HTML em mensagens de alerta
- **Resultado**: Todo conteúdo malicioso foi sanitizado e renderizado como texto simples
- **Status**: APROVADO

### 4. Teste de Tratamento de Erros
- **Descrição**: Verificação da exposição de informações em erros
- **Método**: Provocar erros internos e verificar mensagens retornadas
- **Resultado**: Mensagens de erro genéricas para usuários, detalhes apenas em logs
- **Status**: APROVADO

### 5. Teste de Concorrência
- **Descrição**: Verificação de race conditions em atualizações
- **Método**: Execução de múltiplas verificações simultâneas
- **Resultado**: Integridade dos dados mantida graças às transações
- **Status**: APROVADO

## Considerações de Deploy

1. **Configuração do Banco de Dados**
   - Garantir que a tabela `monitored_processes` tenha o índice único em `process_id`
   - Implementar limpeza periódica de dados antigos para evitar crescimento excessivo

2. **Configuração de Limites**
   - Ajustar `$checkInterval` com base na carga e capacidade do servidor
   - Configurar `NotificationThresholds` com valores apropriados para o ambiente

3. **Monitoramento**
   - Implementar alertas para falhas no próprio serviço de monitoramento
   - Configurar logging detalhado para depuração em ambientes de desenvolvimento/homologação

4. **Performance**
   - Considerar implementação de cache distribuído para ambientes escaláveis
   - Otimizar consultas SQL para tabelas com grande volume de dados

## Configuração do Banco de Dados

```sql
-- Tabela para armazenar processos monitorados
CREATE TABLE monitored_processes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    process_id VARCHAR(64) NOT NULL,
    start_time INT NOT NULL,
    max_duration INT NOT NULL,
    last_check INT NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (process_id)
);

-- Tabela para registro de alertas
CREATE TABLE performance_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_type VARCHAR(32) NOT NULL,
    alert_data TEXT NOT NULL,
    severity VARCHAR(16) NOT NULL,
    acknowledged TINYINT(1) NOT NULL DEFAULT 0,
    acknowledged_by INT NULL,
    acknowledged_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices recomendados
CREATE INDEX idx_monitored_processes_active ON monitored_processes(active);
CREATE INDEX idx_performance_alerts_type ON performance_alerts(alert_type, severity);
CREATE INDEX idx_performance_alerts_created ON performance_alerts(created_at);
```

## Checklist de Segurança

- [x] Validação de entrada robusta com `InputValidationTrait`
- [x] Sanitização de saída com `htmlspecialchars()`
- [x] Uso exclusivo de prepared statements para consultas SQL
- [x] Transações para operações críticas de banco de dados
- [x] Mensagens de erro genéricas para usuários finais
- [x] Sanitização recursiva para dados complexos
- [x] Controle de acesso baseado em propriedade/permissões
- [x] Proteção contra rate limiting implícita (intervalo mínimo)
- [x] Logs detalhados para auditoria e troubleshooting
- [x] Tratamento adequado de exceções
