# Documentação de Deployment - Ambiente de Homologação

## 1. Pré-requisitos

### 1.1 Requisitos de Sistema
- PHP 8.1 ou superior
- MySQL 8.0 ou superior
- Extensões PHP: PDO, PDO_MySQL, mbstring, gd, curl, json, libxml
- Memória alocada para PHP: mínimo 256MB
- Redis Server 6.0+ (opcional, recomendado para ambientes com maior carga)

### 1.2 Configurações Recomendadas
- `max_execution_time`: 300 segundos
- `post_max_size`: 50M
- `upload_max_filesize`: 50M
- `memory_limit`: 256M
- `display_errors`: Off

## 2. Processo de Deployment

### 2.1 Execução do Script `homolog_setup.php`

Este script inicializa o ambiente de homologação com configurações otimizadas para testes:

```bash
cd C:\MCP\taverna\taverna-impressao-ecommerce
php scripts/monitoring/homolog_setup.php
```

O script realizará as seguintes operações:
- Criação/atualização das tabelas de monitoramento
- Configuração de limiares de alerta otimizados para homologação
- Criação do arquivo de configuração do cron
- Preparação da estrutura de diretórios para logs

### 2.2 Configuração de Cron Jobs

Após a execução do script de setup, configure os cron jobs conforme gerado no arquivo `scripts/monitoring/crontab_config.txt`:

```bash
# Em sistemas Unix/Linux
crontab scripts/monitoring/crontab_config.txt

# Em ambientes Windows (usando Task Scheduler)
# Utilize o arquivo como referência para criar tarefas agendadas
```

#### Jobs Essenciais:
1. **Verificação de Processos Monitorados**:
   - Executa a cada 5 minutos
   - `php scripts/monitoring/check_monitored_processes.php`

2. **Limpeza de Dados Antigos**:
   - Executa diariamente às 2h00
   - `php scripts/cleanup/remove_old_alerts.php --days=14`

3. **Backup de Dados de Alertas**:
   - Executa diariamente às 3h00
   - `php scripts/backup/backup_alerting_data.php`

### 2.3 Verificação Inicial

Após a configuração, execute uma verificação manual:

```bash
php scripts/monitoring/check_monitored_processes.php
```

Verifique o output e os logs em `logs/monitoring/` para confirmar a inicialização correta.

## 3. Validação do Sistema de Alertas

### 3.1 Teste de Monitoramento

Execute os seguintes testes para validar o sistema de alertas:

1. **Simulação de Processo Lento**:
   ```php
   // Use a API interna para simular um processo lento
   php scripts/testing/simulate_slow_process.php
   ```

2. **Verificação de Alertas Gerados**:
   ```sql
   SELECT * FROM performance_alerts ORDER BY created_at DESC LIMIT 10;
   ```

3. **Validação de Notificações**:
   - Verifique o painel do administrador para alertas recebidos
   - Confirme recebimento de emails para alertas críticos
   - Verifique logs em `logs/monitoring/` para confirmação de entregas

### 3.2 Limiares Configurados para Homologação

| Contexto | Métrica | Valor Base | Warning | Critical |
|----------|---------|------------|---------|----------|
| async_process | execution_time | 1800s | 2160s | 3600s |
| async_process | memory_usage | 50MB | 55MB | 75MB |
| async_process | min_progress_rate | 15% | N/A | N/A |
| checkout_process | execution_time | 5s | 6s | 10s |
| checkout_process | database_queries | 30 | 39 | 60 |
| report_generation | execution_time | 30s | 45s | 90s |
| report_generation | memory_usage | 25MB | 32.5MB | 50MB |

## 4. Testes de Segurança

### 4.1 Validação CSRF

Realize os seguintes testes para validar proteção CSRF:

1. **Verificação de Tokens**:
   - Inspecione requisições via DevTools para confirmar presença do token CSRF
   - Tente submeter formulários com token CSRF inválido e observe rejeição
   - Verifique expiração de tokens após o período configurado

2. **Teste de Forja de Requisição**:
   ```bash
   # Utilize o script de teste CSRF 
   php scripts/security/test_csrf_protection.php
   ```

### 4.2 Validação de Entrada

Teste a validação de entrada em componentes críticos:

1. **Dados de Usuário**:
   - Tente diferentes tipos de injeção XSS
   - Teste caracteres especiais em campos de formulário
   - Verifique limites de comprimento de campo

2. **Upload de Arquivos**:
   - Tente fazer upload de arquivos com extensão modificada
   - Teste uploads com MIME type incorreto
   - Verifique proteção contra upload de arquivos executáveis

### 4.3 Headers de Segurança

Verifique a presença de headers de segurança em todas as respostas:

```bash
curl -I https://homolog.tavernaimpressao3d.com.br
```

Confirme a presença dos seguintes headers:
- Content-Security-Policy
- X-Content-Type-Options: nosniff
- X-Frame-Options: DENY
- X-XSS-Protection: 1; mode=block
- Strict-Transport-Security
- Referrer-Policy

## 5. Monitoramento de Performance

### 5.1 Métricas a Monitorar

Durante os testes, monitore as seguintes métricas:

1. **Tempo de Resposta**:
   - Tempo médio de resposta para operações críticas
   - Percentil 95 do tempo de resposta
   - Picos de tempo de resposta

2. **Utilização de Recursos**:
   - Uso de CPU durante operações intensivas
   - Consumo de memória durante geração de relatórios
   - Utilização de conexões de banco de dados

3. **Throughput**:
   - Número de requisições processadas por minuto
   - Taxa de sucesso/falha de requisições
   - Capacidade de processamento assíncrono

### 5.2 Testes de Carga

Execute os seguintes testes de carga:

```bash
# Teste de carga básico
php scripts/testing/load_test.php --users=50 --duration=300

# Teste específico para processamento assíncrono
php scripts/testing/async_load_test.php --processes=10 --concurrent=3
```

## 6. Procedimento de Rollback

Em caso de falhas críticas durante a homologação, siga o procedimento de rollback:

1. **Desativação de Componentes**:
   ```bash
   php scripts/deployment/disable_async_processing.php
   ```

2. **Restauração do Banco de Dados**:
   ```bash
   php scripts/backup/restore_db_snapshot.php --snapshot=pre_deployment
   ```

3. **Desativação de Cron Jobs**:
   ```bash
   php scripts/deployment/disable_monitoring_cron.php
   ```

## 7. Checklist Final de Deployment

- [ ] Script `homolog_setup.php` executado com sucesso
- [ ] Cron jobs configurados conforme documentação
- [ ] Verificação manual de processos executada e validada
- [ ] Testes de segurança CSRF completados
- [ ] Testes de validação de entrada realizados
- [ ] Verificação de headers de segurança concluída
- [ ] Testes de carga executados com métricas dentro dos limites
- [ ] Sistema de alertas validado com casos de teste
- [ ] Documentação de procedimentos de rollback confirmada

## 8. Contatos e Responsabilidades

- **Deploy em Homologação**: Equipe de DevOps
- **Validação Funcional**: Equipe de QA
- **Validação de Segurança**: Equipe de Segurança da Informação
- **Aprovação Final**: Gerente de Projeto + Arquiteto de Segurança

## 9. Documentação Relacionada

- [Arquitetura de Segurança](../security/SecurityArchitecture.md)
- [Modelo de Proteção CSRF](../security/CsrfProtectionModel.md)
- [Sistema de Alertas de Performance](../monitoring/PerformanceAlertingService.md)
- [Padrões de Codificação Segura](../security/SecureCodingStandards.md)
