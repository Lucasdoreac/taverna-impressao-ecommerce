# Instruções para Deploy do Sistema de Processamento Assíncrono
## Ambiente de Homologação

Este documento contém instruções detalhadas para a configuração e deploy do sistema de processamento assíncrono no ambiente de homologação da Taverna da Impressão 3D.

## 1. Pré-requisitos

- Ambiente de homologação configurado conforme documentação base
- Banco de dados MySQL 5.7+ com usuário e permissões configuradas
- PHP 7.4+ com extensões requeridas
- Acesso a cron jobs no servidor de homologação
- Diretórios de log com permissões de escrita

## 2. Preparação do Ambiente

### 2.1. Estrutura de Banco de Dados

Execute o script de configuração para preparar o ambiente de homologação:

```bash
# Executar na raiz do projeto
php scripts/monitoring/homolog_setup.php
```

Este script irá:
- Verificar e criar tabelas necessárias se não existirem
- Configurar os limiares de alerta otimizados para homologação
- Criar estrutura de diretórios para logs
- Gerar arquivo de configuração para cron

### 2.2. Configuração de Cron Jobs

Após a execução do script, configure os cron jobs no servidor de homologação:

```bash
# Método 1: Carregar do arquivo de configuração
crontab < scripts/monitoring/crontab_config.txt

# Método 2: Adicionar manualmente (substitua PATH pela raiz do projeto)
# Verificação a cada 5 minutos
*/5 * * * * cd PATH && php scripts/monitoring/check_monitored_processes.php >> logs/monitoring/cron.log 2>&1
# Limpeza diária de dados antigos
0 2 * * * cd PATH && php scripts/cleanup/remove_old_alerts.php --days=14 >> logs/monitoring/cleanup.log 2>&1
# Backup diário
0 3 * * * cd PATH && php scripts/backup/backup_alerting_data.php >> logs/monitoring/backup.log 2>&1
```

### 2.3. Diretórios e Permissões

Certifique-se de que estes diretórios existem e têm permissões adequadas:

```bash
# Diretórios de log
mkdir -p logs/monitoring
chmod 755 logs/monitoring

# Diretórios de backup
mkdir -p data/backups/alerts
chmod 755 data/backups/alerts
```

## 3. Validação de Segurança

Antes de finalizar o deploy, verifique os seguintes requisitos de segurança:

### 3.1. Proteção CSRF

- Todos os endpoints da API de verificação de status estão protegidos com tokens CSRF
- A validação de token ocorre para requisições POST
- Os tokens são validados usando `SecurityManager::validateCsrfToken()`

### 3.2. Rate Limiting

- O limitador de taxa está configurado para prevenir abuso da API
- A configuração padrão limita a 10 requisições por minuto
- Ajuste estes valores se necessário para o ambiente de homologação

### 3.3. Validação de Entrada

- Todas as entradas são validadas usando `InputValidationTrait`
- Os tokens de processo são validados contra padrões específicos
- Parâmetros obrigatórios são verificados em cada endpoint

### 3.4. Sanitização de Saída

- Toda saída é sanitizada para prevenir XSS
- `htmlspecialchars()` é aplicado a todos os dados de saída
- A classe `ApiResponse` gerencia resposta segura com headers apropriados

### 3.5. Proteção contra Exposição de Informações

- Mensagens de erro detalhadas são registradas apenas em logs internos
- Mensagens genéricas são exibidas para o usuário final
- Dados sensíveis não são retornados nos endpoints de API

## 4. Teste Inicial

Execute uma verificação manual para validar o funcionamento:

```bash
# Executar verificação manual
php scripts/monitoring/check_monitored_processes.php
```

O script deve executar sem erros e registrar seu funcionamento no log.

## 5. Limiares de Alerta

Os limiares configurados para o ambiente de homologação são mais restritivos que produção:

| Contexto | Métrica | Limiar (Homologação) | Limiar (Produção) |
|----------|---------|:--------------------:|:-----------------:|
| async_process | execution_time | 1800s (30 min) | 3600s (1h) |
| async_process | memory_usage | 50MB | 100MB |
| async_process | min_progress_rate | 15% | 20% |
| checkout_process | execution_time | 5s | 10s |
| checkout_process | database_queries | 30 | 50 |
| report_generation | execution_time | 30s | 60s |
| report_generation | memory_usage | 25MB | 50MB |
| report_generation | database_queries | 50 | 100 |

Ajuste estes valores no banco de dados se necessário para seu ambiente específico.

## 6. Verificação Pós-Deploy

Após o deploy, realize estas verificações:

1. **Verificar logs**: Examine `logs/monitoring/cron.log` após alguns ciclos de verificação
2. **Testar API de status**: Use o endpoint `/api/status/check` com um token de processo válido
3. **Verificar rate limiting**: Envie múltiplas requisições para confirmar que o limitador funciona
4. **Testar notificações**: Crie um processo que exceda o tempo máximo para testar alertas

## 7. Troubleshooting

### Problemas comuns e soluções:

#### Cron não está executando
- Verifique permissões do script: `chmod 755 scripts/monitoring/check_monitored_processes.php`
- Verifique o caminho completo no crontab
- Teste execução manual para verificar erros

#### Alertas não estão sendo gerados
- Verifique os limiares configurados na tabela `performance_thresholds`
- Confirme se os processos estão sendo registrados na tabela `monitored_processes`
- Verifique logs de erro para possíveis problemas

#### Erros de permissão nos logs
- Verifique permissões do diretório de logs: `chmod -R 755 logs/`
- Verifique usuário do processo web vs. usuário do cron

## 8. Monitoramento Contínuo

Após o deploy bem-sucedido, monitore o sistema por 24-48 horas para garantir:

- Funcionamento contínuo das verificações programadas
- Geração e processamento adequado de alertas
- Consumo correto de recursos (CPU, memória, IO)
- Funcionamento do mecanismo de limpeza de dados antigos

## 9. Próximos Passos

Após validação bem-sucedida em homologação, prepare-se para:

1. Ajustar limiares com base na experiência em homologação
2. Documentar quaisquer problemas encontrados e soluções aplicadas
3. Preparar script de migração para ambiente de produção
4. Atualizar a documentação técnica com base nos resultados da homologação
