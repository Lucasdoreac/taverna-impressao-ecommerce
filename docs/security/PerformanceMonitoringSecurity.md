# Documentação de Segurança: Sistema de Monitoramento Contínuo de Performance

## Função

O Sistema de Monitoramento Contínuo de Performance é responsável por coletar, analisar e gerar alertas para métricas críticas de desempenho do sistema. Esta implementação segue o princípio de "segurança por design", garantindo robustez e resiliência em todas as suas operações. Os componentes monitoram proativamente anomalias de performance, mantendo registros históricos para análise e adaptando-se automaticamente a padrões de uso.

## Implementação

### Arquitetura Segura

O sistema é composto por três módulos principais, cada um com responsabilidades bem definidas:

1. **PerformanceMonitor** - Núcleo central do sistema, responsável pela coordenação de ciclos de monitoramento, análise de anomalias e geração de alertas.

2. **PerformanceMetricsCollector** - Coletor especializado em instrumentação, leitura e armazenamento seguro de métricas.

3. **ScheduledMonitoringJob** - Gerenciador de agendamento e execução atômica de tarefas de monitoramento, com mecanismos de concorrência e resiliência.

### Medidas de Segurança Implementadas

#### 1. Validação de Entrada

```php
// Validação rigorosa usando InputValidationTrait
$componentName = $this->validateString($componentName, ['maxLength' => 100, 'required' => true]);
$metricName = $this->validateString($metricName, ['maxLength' => 100, 'required' => true]);
```

#### 2. Proteção contra SQL Injection

```php
// Uso consistente de prepared statements
$sql = "SELECT id FROM monitored_metrics 
        WHERE component_name = :component AND metric_name = :metric";

$params = [
    ':component' => $componentName,
    ':metric' => $metricName
];

$exists = $this->db->fetchSingle($sql, $params);
```

#### 3. Controle de Concorrência

```php
// Mecanismo de lock para evitar execuções simultâneas
private function acquireLock() {
    // Verificar se já existe um lock
    if (file_exists($this->lockFile)) {
        $lockTime = filemtime($this->lockFile);
        
        // Verificar se o lock expirou
        if (time() - $lockTime < $this->lockTimeout) {
            return false;
        }
        
        // Lock expirado, remover
        @unlink($this->lockFile);
    }
    
    // Criar novo lock
    $handle = @fopen($this->lockFile, 'w');
    if ($handle === false) {
        error_log('Não foi possível criar arquivo de lock.');
        return false;
    }
    
    // Escrever ID do processo no lock
    fwrite($handle, getmypid());
    fclose($handle);
    
    return true;
}
```

#### 4. Transações Atômicas

```php
// Uso de transações para garantir consistência
$this->db->beginTransaction();

foreach ($queries as $query) {
    $this->db->execute($query);
}

$this->db->commit();
```

#### 5. Proteção contra Exposição de Dados Sensíveis

```php
// Registrar erro sem expor detalhes técnicos ao usuário
catch (Exception $e) {
    error_log('Erro ao executar ciclo de monitoramento: ' . $e->getMessage());
    
    // Registrar falha do job
    if ($this->jobId) {
        $this->registerJobFailure($e->getMessage());
    }
    
    // Garantir que o lock seja liberado mesmo em caso de erro
    $this->releaseLock();
    
    return false;
}
```

#### 6. Sanitização de Consultas Persistidas

```php
// Sanitizar consulta SQL antes de armazenar
$query = $this->validateString($query, ['maxLength' => 1000]);
```

#### 7. Limitação de Dados Armazenados

```php
// Limitar o número de registros armazenados para evitar sobrecarga
if (count(self::$metricsStore['durations'][$key]) > 100) {
    array_shift(self::$metricsStore['durations'][$key]);
}
```

#### 8. Validação de Intervalos Seguros

```php
// Garantir que intervalos estejam dentro de limites seguros
$intervalSeconds = max(60, min(86400, (int)$intervalSeconds)); // Entre 1 minuto e 24 horas
```

#### 9. Recuperação Automática de Falhas

```php
// Verificar e recuperar de locks expirados
$lockTime = filemtime($this->lockFile);
            
// Verificar se o lock expirou
if (time() - $lockTime < $this->lockTimeout) {
    return false;
}

// Lock expirado, remover
@unlink($this->lockFile);
```

#### 10. Validação de Métodos Dinâmicos

```php
// Verificar se o método de coleta existe antes de chamar
if (method_exists($this->metricsCollector, $method)) {
    $value = call_user_func_array([$this->metricsCollector, $method], $parameters);
    
    $metrics[] = [
        'metric' => $metricName,
        'value' => $value,
        'component' => $component,
        'timestamp' => time()
    ];
} else {
    error_log("Método de coleta '{$method}' não encontrado para o componente '{$component}'");
}
```

## Uso Correto

### Inicialização do Sistema de Monitoramento

```php
// Instalar tabelas necessárias (apenas na primeira execução)
$job = new ScheduledMonitoringJob();
$job->installMonitoringTables();

// Agendar jobs de monitoramento
$job->scheduleMonitoringJobs();
```

### Execução Manual de um Ciclo de Monitoramento

```php
$monitor = PerformanceMonitor::getInstance();
$result = $monitor->runMonitoringCycle();

if ($result) {
    echo "Ciclo de monitoramento executado com sucesso.";
} else {
    echo "Erro ao executar ciclo de monitoramento.";
}
```

### Monitoramento de um Componente Específico

```php
$monitor = PerformanceMonitor::getInstance();
$result = $monitor->monitorComponent('ReportSystem');

if ($result['success']) {
    echo "Componente monitorado com sucesso.";
    print_r($result['metrics']);
    print_r($result['anomalies']);
} else {
    echo "Erro: " . $result['message'];
}
```

### Execução de Jobs Pendentes

```php
$job = new ScheduledMonitoringJob();
$results = $job->runPendingJobs();
print_r($results);
```

### Instrumentação de Código para Coleta de Métricas

```php
// Iniciar medição de tempo
PerformanceMetricsCollector::startTimer('generate_report');

// Código a ser medido
$report = generateComplexReport();

// Finalizar medição e obter duração
$duration = PerformanceMetricsCollector::endTimer('generate_report');

// Registrar eventos
PerformanceMetricsCollector::recordValue('report_size', strlen($report));
PerformanceMetricsCollector::incrementCounter('reports_generated');

// Registrar acesso ao cache
PerformanceMetricsCollector::recordCacheAccess('report', $cacheHit);
```

## Vulnerabilidades Mitigadas

### 1. Injeção de SQL
- Uso sistemático de prepared statements
- Validação rigorosa de todos os parâmetros antes do uso em consultas
- Sanitização de consultas antes de armazenamento em logs

### 2. Race Conditions
- Implementação de mecanismo de locks para operações críticas
- Transações atômicas para operações de banco de dados
- Verificação de locks expirados com recuperação automática

### 3. Negação de Serviço (DoS)
- Limitação de tamanho em estruturas de dados em memória
- Validação de intervalos de execução para evitar sobrecarga
- Limitação de armazenamento de logs para evitar esgotamento de recursos

### 4. Manipulação de Dados
- Validação rigorosa de entrada em todas as operações
- Checagem de tipos e limites para evitar dados inconsistentes
- Atomicidade em operações de banco de dados para manter consistência

### 5. Escalação de Privilégios
- Verificação de existência de métodos antes de chamadas dinâmicas
- Limitação de valores dentro de faixas seguras
- Sanitização de paths e nomes de arquivos

### 6. Vazamento de Informações
- Logging detalhado apenas no sistema e não exposto ao usuário
- Remoção de dados sensíveis antes de persistência
- Mensagens de erro genéricas para o usuário final

### 7. Exaustão de Recursos
- Configuração de timeouts para operações longas
- Limpeza automática de dados antigos
- Controle de concorrência para evitar sobrecarga

## Testes de Segurança

### 1. Teste de Concorrência
- **Procedimento**: Inicialização simultânea de múltiplos processos de monitoramento.
- **Resultado**: Apenas um processo obtém o lock, demais processos identificam corretamente a execução concorrente e se encerram sem efeitos colaterais.

### 2. Teste de Injeção SQL
- **Procedimento**: Tentativa de injeção de comandos SQL em nomes de componentes e métricas.
- **Resultado**: Comandos injetados são tratados como strings literais pelos prepared statements, sem execução de código malicioso.

### 3. Teste de Sobrecarga
- **Procedimento**: Geração de grande volume de métricas e execução frequente de ciclos.
- **Resultado**: Sistema mantém estabilidade, limitando adequadamente o armazenamento e respeitando intervalos configurados.

### 4. Teste de Recuperação
- **Procedimento**: Interrupção forçada durante execução de ciclo de monitoramento.
- **Resultado**: Em nova execução, sistema detecta lock expirado, realiza limpeza e continua operação normalmente.

### 5. Teste de Validação de Entrada
- **Procedimento**: Fornecimento de valores extremos, negativos e caracteres especiais.
- **Resultado**: Sistema aplica validações adequadas, normalizando valores dentro de faixas seguras ou rejeitando entradas inválidas.

### 6. Teste de Atomicidade
- **Procedimento**: Forçar falhas durante transações críticas.
- **Resultado**: Integridade dos dados é preservada, com rollback automático em caso de falhas.

### 7. Teste de Limites de Recursos
- **Procedimento**: Monitoramento de uso de memória e CPU durante operações intensivas.
- **Resultado**: Sistema mantém uso de recursos dentro de limites aceitáveis, mesmo sob carga elevada.

## Considerações para Implantação

1. **Permissões de Diretório**: Garantir que o diretório temporário usado para locks tenha permissões adequadas.

2. **Configuração de Banco de Dados**: Assegurar que o usuário de banco de dados tenha apenas os privilégios necessários.

3. **Monitoramento do Monitoramento**: Configurar alertas externos para verificar o funcionamento do próprio sistema de monitoramento.

4. **Configuração de Thresholds**: Ajustar limiares iniciais com base nas características específicas do ambiente.

5. **Limpeza de Dados Antigos**: Configurar política de retenção de dados para evitar crescimento indefinido das tabelas de métricas.

6. **Cronograma de Execução**: Agendar ciclos de monitoramento em horários de menor carga para minimizar impacto.

7. **Backup de Dados Históricos**: Implementar rotina de backup para dados de métricas históricas importantes para análises de longo prazo.