# Dashboard de Monitoramento de Performance

## Visão Geral

O Dashboard de Monitoramento de Performance é uma solução integrada para monitorar, analisar e otimizar o desempenho da aplicação Taverna da Impressão 3D. Desenvolvido para fornecer insights em tempo real sobre métricas críticas de desempenho, este dashboard permite identificar proativamente problemas de performance, facilitando a tomada de decisões baseadas em dados para melhorar a experiência do usuário e a eficiência operacional.

## Arquitetura

O sistema segue uma arquitetura de múltiplas camadas:

1. **Coleta de Dados**: Instrumentação de código que captura métricas de desempenho em pontos críticos da aplicação.
2. **Armazenamento**: Persistência de métricas em banco de dados relacional para análise histórica.
3. **Processamento**: Análise de métricas para detecção de anomalias e geração de alertas.
4. **Visualização**: Interface web interativa para exploração de dados e monitoramento em tempo real.

## Componentes Principais

### Classes de Backend

- **PerformanceMonitoringDashboardController**: Controller principal que gerencia todas as interações com o dashboard
- **PerformanceMetrics**: Classe responsável pela coleta e armazenamento de métricas
- **PerformanceAlertingService**: Serviço de detecção de anomalias e geração de alertas
- **PerformanceMonitor**: Classe de monitoramento em baixo nível que instrumenta o código da aplicação

### Views

- **performance_monitoring_dashboard.php**: Dashboard principal com gráficos e métricas em tempo real
- **performance_url_report.php**: Relatório detalhado de performance por URL
- **performance_alert_detail.php**: Detalhes de alertas específicos
- **performance_thresholds.php**: Configuração de limiares para alertas

## Métricas Monitoradas

### Métricas de Performance de Aplicação

- **Tempo de Execução**: Tempo total para processar uma requisição
- **Uso de Memória**: Consumo de memória durante o processamento de requisições
- **Consultas de Banco de Dados**: Número e duração de consultas SQL
- **Taxa de Erro**: Percentual de requisições que resultam em erro

### Métricas de Recursos do Sistema

- **Uso de CPU**: Percentual de utilização de CPU
- **Uso de Memória do Sistema**: Uso total de memória RAM
- **Uso de Disco**: Espaço em disco utilizado/disponível

### Métricas de Usuário

- **Usuários Ativos**: Número de usuários ativos no sistema
- **Processos Ativos**: Número de processos assíncronos em execução

## Sistema de Alertas

O dashboard integra um sistema avançado de alertas que monitora constantemente as métricas e gera notificações quando valores atípicos são detectados:

### Tipos de Alertas

- **Performance**: Disparado quando métricas de desempenho excedem limiares configurados
- **Timeout**: Disparado quando processos assíncronos excedem tempo máximo de execução
- **Progresso Lento**: Disparado quando processos assíncronos progridem mais lentamente que o esperado
- **Erro**: Disparado quando ocorrem erros em componentes monitorados

### Níveis de Severidade

- **Info**: Alertas informativos que não requerem ação imediata
- **Warning**: Alertas que indicam possíveis problemas que devem ser monitorados
- **Error**: Alertas críticos que requerem atenção em breve
- **Critical**: Alertas de emergência que requerem atenção imediata

### Configuração de Limiares

O sistema permite configurar limiares de alerta para diferentes contextos e métricas, possibilitando ajuste fino baseado nas características específicas de cada componente:

- **Contextos Configuráveis**: Configurações específicas para diferentes áreas da aplicação
- **Métricas Personalizáveis**: Limiares distintos para diferentes tipos de métricas
- **Persistência**: Configurações salvas no banco de dados para consistência entre deploys

## Funcionalidades

### Dashboard Principal

- **Métricas em Tempo Real**: Monitoramento ao vivo de CPU, memória e outras métricas do sistema
- **Gráficos Interativos**: Visualizações de tendências históricas para métricas-chave
- **Alertas Recentes**: Visualização dos alertas mais recentes com severidade destacada
- **URLs Mais Lentas**: Identificação das rotas com pior desempenho

### Relatórios Detalhados

- **Análise por URL**: Performance detalhada para rotas específicas
- **Distribuição Temporal**: Visualização de padrões de desempenho ao longo do tempo
- **Amostras Extremas**: Identificação de requisições mais lentas e mais rápidas
- **Recomendações Automáticas**: Sugestões geradas automaticamente para melhorias

### Configuração

- **Gerenciamento de Limiares**: Interface para ajustar limites de alertas
- **Histórico de Alertas**: Visualização de alertas passados para análise de tendências
- **Exports**: Exportação de dados para análise offline (em desenvolvimento)

## Segurança

O dashboard incorpora várias camadas de segurança para garantir operação segura:

- **Validação de Entrada**: Utilização de `InputValidationTrait` para validar todos os parâmetros
- **Proteção CSRF**: Tokens CSRF em todas as requisições POST e AJAX
- **Controle de Acesso**: Restrição a administradores autenticados
- **Sanitização de Saída**: Uso consistente de `htmlspecialchars()` para prevenir XSS
- **Cabeçalhos de Segurança**: Implementação de cabeçalhos HTTP de segurança

## Instalação e Configuração

O Dashboard de Monitoramento de Performance é um componente integrado da aplicação Taverna da Impressão 3D e não requer instalação separada. Entretanto, algumas tabelas no banco de dados são necessárias:

```sql
-- Tabelas principais para o dashboard
CREATE TABLE IF NOT EXISTS `performance_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` VARCHAR(50) NOT NULL,
  `request_uri` VARCHAR(255) NOT NULL,
  `method` VARCHAR(10) NOT NULL,
  `execution_time` FLOAT NOT NULL COMMENT 'Tempo de execução em segundos',
  `memory_start` INT UNSIGNED NOT NULL COMMENT 'Memória inicial em bytes',
  `memory_end` INT UNSIGNED NOT NULL COMMENT 'Memória final em bytes',
  `memory_peak` INT UNSIGNED NOT NULL COMMENT 'Pico de memória em bytes',
  `timestamp` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_performance_logs_timestamp` (`timestamp`),
  INDEX `idx_performance_logs_request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `performance_alerts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `alert_type` VARCHAR(50) NOT NULL,
  `severity` ENUM('info', 'warning', 'error', 'critical') NOT NULL,
  `alert_data` JSON NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_performance_alerts_type` (`alert_type`),
  INDEX `idx_performance_alerts_severity` (`severity`),
  INDEX `idx_performance_alerts_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Uso Típico

### Monitoramento Proativo

1. Acesse o dashboard via menu de administração ou diretamente em `/admin/performance_monitoring_dashboard`
2. Visualize métricas em tempo real e gráficos de tendência
3. Verifique alertas recentes e URLs com problemas de performance
4. Explore detalhes de alertas ou URLs específicas para análise profunda

### Análise de Incidentes

1. Após identificar um problema de performance, acesse o dashboard
2. Filtre por período para isolar o momento do incidente
3. Identifique URLs afetadas através da seção "URLs Mais Lentas"
4. Analise detalhes da URL específica para compreender padrões e causas
5. Verifique recomendações automáticas para possíveis soluções

### Ajuste de Configurações

1. Acesse a seção de Limiares via menu ou em `/admin/performance_monitoring_dashboard/thresholds`
2. Ajuste os valores para contextos específicos baseado em padrões observados
3. Consulte o histórico de alertas para refinar configurações

## Integração com Outros Sistemas

O Dashboard de Monitoramento de Performance integra-se com:

- **Sistema de Notificações**: Alerta administradores sobre problemas críticos
- **Sistema de Logs**: Armazena dados detalhados para análise forense
- **Sistema de Monitoramento de Recursos**: Complementa métricas de aplicação com dados de infraestrutura

## Manutenção

### Rotina de Limpeza de Dados

Para evitar crescimento excessivo do banco de dados, uma rotina de limpeza de dados históricos deve ser executada periodicamente:

```php
// Remover dados de performance mais antigos que 90 dias
$cutoffDate = date('Y-m-d', strtotime('-90 days'));
$db->prepare("DELETE FROM performance_logs WHERE DATE(timestamp) < ?")->execute([$cutoffDate]);

// Remover alertas resolvidos mais antigos que 180 dias
$cutoffDate = date('Y-m-d', strtotime('-180 days'));
$db->prepare("DELETE FROM performance_alerts WHERE DATE(created_at) < ?")->execute([$cutoffDate]);
```

### Verificação de Integridade

Verificações periódicas de integridade de dados devem ser realizadas para garantir o funcionamento correto do dashboard:

```sql
-- Verificar integridade dos dados
SELECT COUNT(*) FROM performance_logs WHERE execution_time <= 0; -- Deve retornar 0
SELECT COUNT(*) FROM performance_logs WHERE memory_peak <= 0; -- Deve retornar 0
```

## Considerações de Segurança

1. **Proteção de Acesso**: O dashboard contém informações sensíveis sobre a infraestrutura e deve ser acessível apenas a administradores autenticados.
2. **Validação de Dados**: Todos os parâmetros de entrada devem ser rigorosamente validados para prevenir injeção SQL e XSS.
3. **Limitação de Dados**: A quantidade de dados retornados deve ser limitada para evitar DoS.
4. **Sanitização de Saída**: Todos os dados exibidos devem ser devidamente sanitizados para prevenir XSS.
5. **Logs de Auditoria**: Acesso ao dashboard deve ser registrado para auditoria de segurança.
