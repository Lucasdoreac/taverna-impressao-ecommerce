# Testes de Performance do Sistema de Cotação Automatizada
Versão 1.0.0

## Visão Geral

O sistema de testes de performance tem como objetivo medir, analisar e documentar o desempenho do Sistema de Cotação Automatizada da Taverna da Impressão 3D em diferentes cenários de uso, cargas e configurações.

Os testes são projetados para identificar:
- Gargalos de desempenho em componentes específicos
- Limites de escalabilidade do sistema
- Comportamento sob diferentes cargas
- Uso de recursos (CPU, memória, etc.)
- Tempos de resposta para diferentes tipos de operações

## Arquitetura dos Testes

A estrutura de testes é composta por:

1. **QuotationSystemPerformanceTest**: Classe principal que implementa os diferentes tipos de testes de performance
2. **PerformanceMonitor**: Classe auxiliar para medição e registro de métricas de desempenho
3. **Modelos de teste**: Arquivos STL de diferentes complexidades para testar o analisador
4. **Script de execução**: Facilita a execução dos testes com diferentes configurações

## Tipos de Testes

### 1. Análise de Complexidade de Modelos

Testa o desempenho do componente `ModelComplexityAnalyzer` com modelos 3D de diferentes complexidades:

- **Simples** (cube.stl): Cubo básico com 12 triângulos
- **Moderado** (figurine.stl): Figurino simples com geometria média
- **Complexo** (mechanical.stl): Peça mecânica com detalhes
- **Muito Complexo** (organic.stl): Modelo orgânico com formas complexas

Métricas coletadas:
- Tempo de execução (média, mínimo, máximo)
- Uso de memória (média, pico)

### 2. Cálculo de Cotações

Testa o desempenho do componente `QuotationCalculator` para diferentes combinações de:
- Complexidade do modelo
- Tipo de material
- Configurações de qualidade

Métricas coletadas:
- Tempo de execução (média, mínimo, máximo)

### 3. Geração Completa de Cotação

Testa o fluxo completo de geração de cotação, incluindo:
- Integração com banco de dados
- Análise de complexidade
- Cálculo de preços
- Geração de resultado formatado

Métricas coletadas:
- Tempo de execução (média, mínimo, máximo)
- Tempo por etapa do processo

### 4. Cotações Concorrentes

Simula múltiplas solicitações de cotação simultâneas com diferentes níveis de concorrência:
- 1, 5, 10, 20 e 50 requisições simultâneas

Métricas coletadas:
- Tempo total
- Tempo médio por cotação
- Taxa de falha

### 5. Uso de Memória

Analisa o comportamento de memória do sistema em diferentes cenários:
- Análises sequenciais sem liberação de memória
- Análises sequenciais com garbage collection
- Análise de modelos grandes

Métricas coletadas:
- Crescimento de memória
- Pico de memória
- Uso de memória por etapa

### 6. Interação com Banco de Dados

Testa o desempenho das operações de banco de dados:
- Carregamento de parâmetros
- Salvamento de cotações
- Busca de cotações
- Geração de estatísticas

Métricas coletadas:
- Tempo de execução (média, mínimo, máximo)

## Como Executar os Testes

### Pré-requisitos

- PHP 7.4 ou superior
- Extensões PHP: json, pdo, pdo_mysql
- Banco de dados MySQL configurado conforme especificações do projeto
- Modelos 3D de teste disponíveis

### Execução Básica

```bash
php tests/run_performance_tests.php
```

### Opções de Configuração

- `--iterations=N`: Número de iterações por teste (padrão: 10)
- `--warmup=N`: Iterações de aquecimento (padrão: 2)
- `--format=FORMAT`: Formato de saída (console, json, csv)
- `--log=FILE`: Arquivo de log personalizado
- `--test=TEST`: Teste específico a ser executado

### Exemplos

Executar todos os testes com configuração padrão:
```bash
php tests/run_performance_tests.php
```

Executar apenas o teste de análise de complexidade:
```bash
php tests/run_performance_tests.php --test=complexity
```

Executar testes com mais iterações e saída em CSV:
```bash
php tests/run_performance_tests.php --iterations=20 --format=csv
```

## Interpretação dos Resultados

Os resultados dos testes são apresentados em diferentes formatos conforme a configuração:

### Saída em Console

Apresenta um resumo dos resultados:
- Resultados por tipo de teste
- Médias, mínimos e máximos
- Alertas para valores que excedem os limites definidos

### Saída em JSON

Fornece dados estruturados completos para análise programática:
- Todos os resultados brutos
- Todas as métricas coletadas
- Informações de ambiente e configuração

### Saída em CSV

Gera um arquivo CSV com os principais resultados para análise em ferramentas como Excel:
- Uma linha por métrica/teste
- Colunas para categoria, teste, métrica e valor

## Métricas e Limiares

Os testes definem limiares para diferentes métricas, que quando excedidos geram alertas:

| Métrica | Nível de Alerta | Valor Limite |
|---------|-----------------|--------------|
| Tempo de execução | Warning | 1.0 segundos |
| Tempo de execução | Critical | 3.0 segundos |
| Uso de memória | Warning | 10 MB |
| Uso de memória | Critical | 50 MB |
| Tempo de consulta DB | Warning | 0.5 segundos |
| Tempo de consulta DB | Critical | 2.0 segundos |
| Tempo de resposta | Warning | 2.0 segundos |
| Tempo de resposta | Critical | 5.0 segundos |

## Diretórios e Arquivos Relevantes

```
taverna-impressao-ecommerce/
├── app/
│   └── lib/
│       ├── Analysis/
│       │   ├── ModelComplexityAnalyzer.php
│       │   ├── QuotationCalculator.php
│       │   └── QuotationManager.php
│       └── Performance/
│           └── PerformanceMonitor.php
├── tests/
│   ├── performance/
│   │   └── QuotationSystemPerformanceTest.php
│   ├── testdata/
│   │   └── models/
│   │       ├── cube.stl
│   │       ├── figurine.stl
│   │       ├── mechanical.stl
│   │       └── organic.stl
│   └── run_performance_tests.php
└── docs/
    └── performance/
        └── performance_testing.md
```

## Manutenção e Extensão

### Adicionando Novos Testes

Para adicionar novos testes de performance:

1. Adicione um novo método na classe `QuotationSystemPerformanceTest`
2. Implemente a lógica de teste e coleta de métricas
3. Atualize o método `runAllTests()` para incluir o novo teste
4. Atualize o script `run_performance_tests.php` para suportar a execução isolada do novo teste

### Adicionando Novos Modelos de Teste

Para adicionar novos modelos 3D para teste:

1. Adicione o arquivo STL na pasta `tests/testdata/models/`
2. Atualize a configuração `test_models` na classe `QuotationSystemPerformanceTest`

## Boas Práticas

1. **Consistência**: Execute os testes em condições similares para resultados comparáveis
2. **Isolamento**: Evite executar outros processos intensivos durante os testes
3. **Aquecimento**: Use as iterações de aquecimento para estabilizar o ambiente
4. **Várias Iterações**: Execute múltiplas iterações para resultados mais confiáveis
5. **Análise de Tendências**: Compare os resultados ao longo do tempo para identificar regressões
6. **Ambiente**: Documente o ambiente de teste para referência futura

## Integração Contínua

Recomenda-se integrar os testes de performance ao pipeline de CI/CD com as seguintes considerações:

1. **Testes de Base**: Execute testes básicos em cada commit
2. **Testes Completos**: Execute a suíte completa em merges para branches principais
3. **Histórico**: Mantenha histórico de resultados para análise de tendências
4. **Alertas**: Configure alertas para degradações significativas de performance
5. **Timeouts**: Defina limites de tempo para prevenir bloqueios no pipeline

## Monitoramento Contínuo

Além dos testes específicos, a classe `PerformanceMonitor` pode ser integrada ao código de produção para monitoramento contínuo:

```php
// Exemplo de uso em código de produção
$monitor = new PerformanceMonitor();
$measurementId = $monitor->startMeasurement('quotation_generation', ['model_id' => $modelId]);

// Código a ser monitorado
$result = $quotationManager->generateQuotation($modelId, $material);

// Finalizar medição
$metrics = $monitor->endMeasurement($measurementId);
```

Esta abordagem permite a coleta de métricas reais em ambiente de produção, complementando os testes de laboratório.