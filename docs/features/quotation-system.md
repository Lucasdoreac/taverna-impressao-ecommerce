# Sistema de Cotação Automatizada

## Visão Geral

O Sistema de Cotação Automatizada da Taverna da Impressão 3D permite calcular preços para impressão de modelos 3D com base em análise automática de complexidade. O sistema analisa características como volume, densidade de triângulos, superfícies em balanço e paredes finas para determinar o nível de complexidade e, consequentemente, o custo de impressão.

## Componentes Principais

### 1. ModelComplexityAnalyzer

Classe responsável pela análise técnica dos modelos 3D, extraindo métricas relevantes e determinando o nível de complexidade.

- Suporta formatos STL, OBJ e 3MF
- Extrai métricas como volume, contagem de triângulos e densidade de detalhes
- Calcula métricas derivadas como porcentagem de superfícies em balanço e paredes finas
- Classifica os modelos em quatro níveis de complexidade:
  - Simples: Modelos básicos com baixa densidade de triângulos
  - Moderada: Modelos com nível médio de detalhes
  - Complexa: Modelos detalhados com alta densidade de triângulos
  - Muito Complexa: Modelos extremamente detalhados ou difíceis de imprimir

### 2. QuotationCalculator

Calcula cotações com base na análise de complexidade, considerando diferentes materiais e parâmetros configuráveis.

- Suporta cinco tipos de material (PLA, ABS, PETG, Flexível, Resina)
- Calcula custos com base em:
  - Material utilizado (g)
  - Tempo de impressão (min)
  - Complexidade do modelo
  - Risco de falha de impressão
  - Urgência do pedido
- Fornece detalhamento completo dos custos
- Estima prazo de entrega baseado na complexidade e tempo de impressão

### 3. QuotationManager

Gerencia o armazenamento, recuperação e manipulação de cotações e parâmetros do sistema.

- Persistência de cotações no banco de dados
- Armazenamento de parâmetros configuráveis
- Geração de estatísticas de cotações
- Interface unificada para os controllers

## Fluxo de Operação

1. **Análise de Complexidade**:
   - O modelo 3D é analisado para extrair métricas como volume, contagem de triângulos, etc.
   - Métricas derivadas são calculadas, como densidade de triângulos e superfícies em balanço
   - O modelo é classificado em um dos quatro níveis de complexidade

2. **Cálculo de Cotação**:
   - O custo do material é calculado com base no peso estimado e tipo de material
   - O custo de operação é calculado com base no tempo estimado de impressão
   - Fatores de complexidade são aplicados ao custo base
   - Taxas de risco e urgência são adicionadas, se aplicáveis
   - Markup é aplicado para determinar o preço final
   - Prazo de entrega é estimado com base na complexidade e tempo de impressão

3. **Persistência**:
   - A cotação é armazenada no banco de dados com todos os detalhes
   - O cliente pode visualizar, salvar ou confirmar o pedido
   - O administrador pode acessar todas as cotações para análise

## Parâmetros Configuráveis

O sistema de cotação é altamente configurável, permitindo ajustes em:

- Custo de cada tipo de material (por grama)
- Custo horário de operação da máquina
- Fatores de multiplicação para cada nível de complexidade
- Percentual de markup padrão
- Valor mínimo de cotação
- Taxa de risco por ponto percentual de risco de falha
- Fator de urgência para pedidos urgentes

Estes parâmetros podem ser ajustados pelos administradores através da interface administrativa.

## Interface do Cliente

O cliente pode:

- Solicitar cotações para seus modelos 3D aprovados
- Escolher o material desejado
- Indicar se o pedido é urgente
- Visualizar o preço final e detalhamento dos custos
- Confirmar a cotação para iniciar o pedido
- Visualizar todas as suas cotações anteriores

## Interface do Administrador

Os administradores podem:

- Configurar todos os parâmetros do sistema de cotação
- Gerar cotações de teste para validar configurações
- Visualizar listagem completa de cotações com filtros
- Acessar estatísticas de cotações (média de preços, distribuição por material, etc.)
- Analisar o detalhamento completo de cada cotação

## Considerações de Segurança

O sistema implementa as seguintes medidas de segurança:

1. **Validação de entrada**:
   - Todos os parâmetros de entrada são validados via InputValidationTrait
   - Verificações de tipo, formato e limites em todos os campos

2. **Proteção CSRF**:
   - Tokens CSRF em todos os formulários
   - Validação de token em todas as submissões

3. **Controle de acesso**:
   - Verificação de autenticação em todas as rotas
   - Verificação de propriedade de cotações (usuário só pode ver suas próprias cotações)
   - Verificação de permissão de administrador para áreas restritas

4. **Sanitização de saída**:
   - Todos os dados exibidos são sanitizados via htmlspecialchars()
   - Valores monetários são formatados adequadamente

5. **Prepared statements**:
   - Todas as consultas SQL utilizam prepared statements
   - Parâmetros são tratados adequadamente para evitar SQL injection

## Limitações Atuais

1. As estimativas de uso de material e tempo de impressão são aproximadas, baseadas em heurísticas.
2. A análise de superfícies em balanço e paredes finas é estimada, não baseada em análise geométrica precisa.
3. O sistema não leva em consideração configurações específicas de impressora (ex: diâmetro do bico, altura de camada).
4. A integração com o sistema de pagamento ainda não está implementada.

## Extensões Futuras

1. Implementar análise geométrica mais precisa para superfícies em balanço e paredes finas.
2. Integrar com software de slicing para estimativas mais precisas de tempo e material.
3. Permitir configurações específicas de impressora na cotação.
4. Integrar com sistema de pagamento para finalização completa do pedido.
5. Implementar cotação em tempo real durante o upload do modelo.
6. Adicionar recomendações automáticas de materiais baseadas nas características do modelo.

## Requisitos Técnicos

- PHP 7.4+
- MySQL 5.7+
- Extensão ZipArchive para análise de arquivos 3MF
- Extensão FileInfo para detecção de tipo MIME
- Pelo menos 64MB de memória PHP para análise de modelos grandes

## Tabelas do Banco de Dados

### quotation_parameters
Armazena os parâmetros configuráveis do sistema de cotação.

### model_quotations
Armazena as cotações geradas para os modelos 3D.

### print_orders
Armazena as ordens de serviço geradas a partir das cotações confirmadas.

## Apêndice: Lógica de Classificação de Complexidade

A classificação de complexidade é determinada por uma combinação de fatores:

1. **Densidade de triângulos** (triângulos/mm³):
   - Simples: ≤ 0.5
   - Moderada: ≤ 2.0
   - Complexa: ≤ 5.0
   - Muito Complexa: > 5.0

2. **Porcentagem de superfícies em balanço**:
   - Simples: ≤ 5%
   - Moderada: ≤ 15%
   - Complexa: ≤ 30%
   - Muito Complexa: > 30%

3. **Porcentagem de paredes finas**:
   - Simples: ≤ 5%
   - Moderada: ≤ 15%
   - Complexa: ≤ 30%
   - Muito Complexa: > 30%

O nível de complexidade final é determinado pela média ponderada destes fatores.