# Documentação de Segurança: NotificationThresholds

## Função
O componente `NotificationThresholds` é responsável por definir, gerenciar e validar os limiares (thresholds) para as métricas de performance do sistema. Ele determina quando um valor de métrica deve gerar um alerta, calcula níveis de severidade e permite ajustes automáticos ou manuais dos limiares com base em dados históricos.

## Implementação

### Padrões de Segurança Aplicados

1. **Validação de Entrada**
```php
use InputValidationTrait;

// Validação de parâmetros
$metric = $this->validateString($metric, ['maxLength' => 255, 'required' => true]);
$description = $this->validateString($description, ['maxLength' => 1000]);
```

2. **Proteção contra SQL Injection**
```php
// Uso de prepared statements em todas as consultas
$sql = "SELECT id FROM performance_thresholds WHERE metric = :metric";
$exists = $this->db->fetchSingle($sql, [':metric' => $metric]);
```

3. **Validação de Tipos e Faixas de Valores**
```php
// Validação de valores numéricos dentro de faixas seguras
$days = max(7, min(90, (int)$days));
$stdDevFactor = max(1.0, min(5.0, (float)$stdDevFactor));
```

4. **Operadores Restritos**
```php
// Lista aprovada de operadores de comparação
private static $validOperators = ['>', '<', '>=', '<=', '=='];

// Validação de operador
if (!in_array($operator, self::$validOperators)) {
    $operator = '>'; // Valor padrão seguro
}
```

5. **Proteção contra Expiração do Cache**
```php
// Verificação de tempo de expiração do cache
if ($this->lastCacheUpdate > 0 && 
    ($currentTime - $this->lastCacheUpdate) < $this->cacheExpiration &&
    !empty($this->thresholdsCache)) {
    return;
}
```

6. **Tratamento de Erros Detalhado**
```php
try {
    // Operações críticas
} catch (Exception $e) {
    error_log('Erro ao carregar thresholds: ' . $e->getMessage());
    
    // Manter estado seguro em caso de falha
    if (empty($this->thresholdsCache)) {
        $this->thresholdsCache = [];
    }
}
```

## Uso Correto

### Obtenção de Threshold para uma Métrica
```php
$thresholds = NotificationThresholds::getInstance();
$threshold = $thresholds->getThresholdForMetric('response_time');

if ($threshold) {
    $value = $threshold['value'];        // Valor do threshold
    $operator = $threshold['operator'];  // Operador de comparação (>, <, etc.)
    $description = $threshold['description']; // Descrição do threshold
}
```

### Verificação de Valor Contra Threshold
```php
$thresholds = NotificationThresholds::getInstance();
$isExceeded = $thresholds->isThresholdExceeded('response_time', 2.5);

if ($isExceeded) {
    // Valor excede o threshold, tomar ação apropriada
    $severity = $thresholds->determineSeverity('response_time', 2.5);
    $percentExceeded = $thresholds->calculatePercentExceeded('response_time', 2.5);
}
```

### Atualização de um Threshold
```php
$thresholds = NotificationThresholds::getInstance();
$success = $thresholds->updateThreshold(
    'response_time',      // Nome da métrica
    1.5,                  // Novo valor do threshold
    '>',                  // Operador de comparação
    'Tempo máximo de resposta em segundos' // Descrição
);
```

### Ajuste Automático de Thresholds
```php
$thresholds = NotificationThresholds::getInstance();
$success = $thresholds->autoAdjustThreshold(
    'response_time',  // Nome da métrica
    30,               // Dias de dados históricos a considerar
    2.0               // Fator de desvio padrão
);
```

### Definição de Thresholds Padrão
```php
$thresholds = NotificationThresholds::getInstance();
$success = $thresholds->setDefaultThresholds();
```

## Vulnerabilidades Mitigadas

1. **SQL Injection**
   - Uso consistente de prepared statements
   - Validação rigorosa de parâmetros antes do uso em consultas
   - Tipagem forte para todos os valores numéricos

2. **Manipulação de Valores de Threshold**
   - Validação de faixas de valores para evitar thresholds extremos
   - Operadores restritos a um conjunto seguro e predefinido
   - Logging de todas as alterações para auditoria

3. **Cache Poisoning**
   - Verificação de validade temporal do cache
   - Reconstrução segura do cache em caso de falha
   - Valores padrão seguros quando o cache não está disponível

4. **Ataques de Negação de Serviço**
   - Limite no tamanho máximo de descrições
   - Tempos de expiração configuráveis para minimizar carga de banco de dados
   - Tratamento robusto de erros para manter operação mesmo em condições adversas

5. **Vazamento de Informações**
   - Mensagens de erro detalhadas apenas no log do servidor
   - Retornos genéricos para o usuário em caso de falha
   - Nenhuma informação sensível armazenada nos thresholds

## Testes de Segurança

1. **Teste de Injeção SQL**: Tentativa de injeção de comandos SQL em nomes de métricas e descrições.
   - Resultado: Nenhuma vulnerabilidade detectada, todas as entradas são tratadas com prepared statements.

2. **Teste de Manipulação de Cache**: Tentativa de manipular o cache para obter thresholds incorretos.
   - Resultado: O sistema verifica corretamente a validade temporal do cache e o reconstrói quando necessário.

3. **Teste de Valores Extremos**: Tentativa de definir thresholds com valores extremamente altos ou baixos.
   - Resultado: O sistema aplica validações de faixa para garantir valores razoáveis.

4. **Teste de Persistência**: Verificação da recuperação correta dos thresholds após reinicialização.
   - Resultado: Todos os thresholds são adequadamente persistidos e recuperados do banco de dados.

5. **Teste de Concorrência**: Tentativa de atualizar o mesmo threshold simultaneamente.
   - Resultado: O sistema lida corretamente com atualizações concorrentes através de transações de banco de dados.

6. **Teste de Desempenho**: Análise do impacto do cache no desempenho do sistema.
   - Resultado: O cache reduz significativamente a carga no banco de dados, evitando potenciais problemas de desempenho.