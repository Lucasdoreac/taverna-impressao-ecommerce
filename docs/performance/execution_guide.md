# Guia de Execução de Testes de Performance do Sistema de Cotação

Este documento apresenta instruções detalhadas para execução dos testes de performance para o Sistema de Cotação Automatizada da Taverna da Impressão 3D. Os testes são projetados para avaliar o desempenho do sistema sob diferentes condições e fornecer métricas objetivas para otimização.

## Pré-requisitos

1. Ambiente PHP 7.4 ou superior
2. Extensões PHP habilitadas:
   - `pdo_mysql`
   - `json`
   - `zip` (para processamento de arquivos .3mf)
3. Acesso ao banco de dados de teste (configurado com permissões de leitura/escrita)
4. Arquivos de modelos 3D de teste preparados nas categorias: small, medium, large, complex

## Estrutura de Diretórios

Certifique-se que os seguintes diretórios estejam preparados antes da execução:

```
/tests/performance/             # Script de teste principal
/tests/test_data/sample_models/ # Modelos 3D de teste por tamanho
  /small/                       # Modelos pequenos
  /medium/                      # Modelos médios
  /large/                       # Modelos grandes
  /complex/                     # Modelos complexos
/logs/performance/              # Diretório para logs e resultados
```

## Execução dos Testes

### 1. Execução Básica

Para executar todos os testes com configurações padrão:

```bash
php tests/performance/QuotationSystemPerformanceTest.php
```

### 2. Execução com Parâmetros Personalizados

Você pode personalizar a execução dos testes com os seguintes parâmetros:

```bash
php tests/performance/QuotationSystemPerformanceTest.php \
  --iterations=200 \
  --concurrentUsers=20 \
  --outputFormat=json \
  --timeLimit=600 \
  --memoryLimit=256
```

### 3. Parâmetros Disponíveis

| Parâmetro        | Descrição                              | Valor Padrão |
|------------------|----------------------------------------|--------------|
| `iterations`     | Número de iterações por teste          | 100          |
| `concurrentUsers`| Usuários simultâneos no teste          | 10           |
| `modelSizes`     | Tipos de modelos a testar (separados por vírgula) | small,medium,large,complex |
| `outputFormat`   | Formato de saída (json ou txt)         | json         |
| `timeLimit`      | Tempo máximo de execução (segundos)    | 300          |
| `memoryLimit`    | Limite de memória (MB)                 | 128          |

### 4. Execução de Testes Individuais

Para executar fases específicas de teste, você pode usar o modo interativo:

```php
<?php
// Carregar o script de teste
require_once 'tests/performance/QuotationSystemPerformanceTest.php';

// Criar instância com configurações personalizadas
$tester = new App\Tests\Performance\QuotationSystemPerformanceTest([
    'iterations' => 50,
    'modelSizes' => ['medium', 'large']
]);

// Executar teste específico
$complexityResults = $tester->testModelComplexityAnalyzer();

// Salvar e exibir resultados
echo json_encode($complexityResults, JSON_PRETTY_PRINT);
```

## Interpretação dos Resultados

Os resultados são salvos no diretório `/logs/performance/` no formato especificado (json ou txt). Em formato JSON, a estrutura é a seguinte:

```json
{
  "complexityAnalysis": { ... },  // Resultados de análise de complexidade
  "quotationCalculation": { ... }, // Resultados de cálculo de cotação
  "fullQuotation": { ... },       // Resultados de fluxo completo
  "concurrency": { ... },         // Resultados de testes de concorrência
  "memoryUsage": { ... },         // Resultados de consumo de memória
  "database": { ... },            // Resultados de interação com BD
  "summary": {                    // Resumo consolidado
    "averageExecutionTimes": { ... },
    "memoryUsage": { ... },
    "databasePerformance": { ... },
    "bottlenecks": [ ... ],
    "recommendations": [ ... ]
  },
  "meta": { ... }                 // Metadados da execução
}
```

### Análise Crítica

Ao analisar os resultados, observe em particular:

1. **Gargalos Identificados**: Lista de componentes que não atingiram os objetivos de performance
2. **Tempos de Resposta P95/P99**: Indicadores críticos da experiência do usuário sob carga
3. **Consumo de Memória de Pico**: Importante para dimensionamento de recursos
4. **Vazamentos de Memória**: Detectados se o uso final após ciclo completo for >110% do início

## Monitoramento em Produção

Após as otimizações, utilize o seguinte comando para executar testes periódicos em ambiente de produção:

```bash
php tests/performance/QuotationSystemPerformanceTest.php \
  --iterations=50 \
  --outputFormat=json \
  --monitorMode=true \
  --alertThreshold=high
```

O modo de monitoramento (`monitorMode=true`) executa um conjunto reduzido de testes e compara com a linha de base estabelecida, gerando alertas se os limites forem excedidos.

## Resolução de Problemas

### Erro de Memória
Se ocorrer um erro "Allowed memory size exhausted", aumente o limite de memória:

```bash
php -d memory_limit=512M tests/performance/QuotationSystemPerformanceTest.php
```

### Tempo Limite Excedido
Se os testes ultrapassarem o tempo limite, aumente o parâmetro `timeLimit` ou reduza o escopo do teste:

```bash
php tests/performance/QuotationSystemPerformanceTest.php --timeLimit=600 --modelSizes=small,medium
```

### Erros de Banco de Dados
Para isolar problemas de banco de dados, execute apenas os testes de interação com BD:

```php
<?php
require_once 'tests/performance/QuotationSystemPerformanceTest.php';
$tester = new App\Tests\Performance\QuotationSystemPerformanceTest();
$results = $tester->testDatabaseInteraction();
echo json_encode($results, JSON_PRETTY_PRINT);
```

## Considerações de Segurança

- Os testes utilizam entradas controladas e não devem representar riscos de segurança
- As operações de banco de dados são realizadas em modo de transação e revertidas após o teste
- Arquivos temporários são limpos automaticamente ao final da execução
- Em ambiente de produção, execute com privilégios mínimos necessários

## Integração Contínua

Para integrar os testes de performance ao seu pipeline CI/CD:

1. Adicione ao arquivo de configuração do CI (exemplo para GitHub Actions):

```yaml
performance-tests:
  runs-on: ubuntu-latest
  steps:
    - uses: actions/checkout@v2
    - name: Set up PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '7.4'
        extensions: pdo_mysql, json, zip
    - name: Run Performance Tests
      run: php tests/performance/QuotationSystemPerformanceTest.php --outputFormat=json
    - name: Archive test results
      uses: actions/upload-artifact@v2
      with:
        name: performance-test-results
        path: logs/performance/*.json
```

2. Configure limites para falha automática de build em caso de regressão de performance

## Mais Informações

Consulte a documentação completa em `/docs/performance/quotation_system_performance.md` para análise detalhada dos resultados e recomendações de otimização.