# Plano de Teste do Visualizador 3D em Dispositivos Móveis

Este documento descreve uma metodologia estruturada para testar o visualizador 3D da Taverna da Impressão em diferentes dispositivos móveis.

## 1. Escopo do Teste

### 1.1 Objetivos
- Verificar se o visualizador 3D funciona corretamente em diferentes dispositivos móveis
- Avaliar a performance em termos de FPS e responsividade
- Validar otimizações implementadas (LOD dinâmico, Web Workers, etc.)
- Confirmar compatibilidade com diferentes versões de navegadores móveis
- Identificar problemas específicos de dispositivos ou navegadores

### 1.2 Entregáveis
- Relatório de resultados dos testes
- Identificação de problemas/bugs
- Recomendações para melhorias

## 2. Ambiente de Teste

### 2.1 Dispositivos para Teste
#### Android
- Dispositivo de entrada (ex: Android 9 ou inferior, 2GB RAM)
- Dispositivo intermediário (ex: Android 10-11, 4GB RAM)
- Dispositivo de alta performance (ex: Android 12+, 6GB+ RAM)

#### iOS
- iPhone mais antigo (ex: iPhone 8/X)
- iPhone intermediário (ex: iPhone 11/12)
- iPhone recente (ex: iPhone 13/14/15)
- iPad (se possível)

### 2.2 Navegadores
- Chrome (Android)
- Safari (iOS)
- Firefox Mobile
- Samsung Internet (Android)

### 2.3 Condições de Rede
- Wi-Fi de alta velocidade
- Conexão 4G
- Conexão 3G/lenta (simulada)

## 3. Metodologia de Teste

### 3.1 Ferramenta de Diagnóstico
Utilizar o script de diagnóstico `/public/diagnostic-model-viewer.php` para verificar:
- Carregamento de dependências
- Verificação de arquivos de modelo
- Teste com modelo padrão

### 3.2 Testes Funcionais

#### Carregamento Básico
- Verificar carregamento inicial do visualizador
- Confirmar exibição correta do modelo
- Checar se a animação de carregamento aparece e desaparece corretamente

#### Interatividade
- Rotação do modelo (arrastar)
- Zoom (pinch/spread)
- Controles na interface (botões de rotação, reset, etc.)

#### Integração na Página de Produto
- Alternar entre abas de imagens e modelo 3D
- Verificar se o visualizador se ajusta corretamente em diferentes orientações do dispositivo
- Testar integração com opção de cores de filamento

### 3.3 Testes de Performance

#### Métricas de Performance
- FPS (frames por segundo) - Alvo: 30+ FPS
- Tempo de carregamento - Alvo: <5s em Wi-Fi, <10s em 4G
- Uso de memória - Monitorar se causa problemas em dispositivos limitados
- Responsividade da interface durante manipulação do modelo

#### Otimizações Específicas
- Verificar adaptação de LOD (Level of Detail) em diferentes dispositivos
- Confirmar funcionamento de Web Workers quando disponíveis
- Testar adaptação de qualidade durante interação

### 3.4 Testes de Compatibilidade
- Verificar comportamento em diferentes orientações (retrato/paisagem)
- Testar em diferentes tamanhos de tela
- Validar em diferentes versões de navegadores

### 3.5 Testes de Carga
- Carregar modelo complexo (>100K triângulos)
- Testar com múltiplas instâncias do visualizador na mesma página
- Verificar comportamento após uso prolongado (memória, bateria)

## 4. Cenários de Teste Específicos

### 4.1 Modelos de Diferentes Complexidades
- Modelo simples (<10K triângulos)
- Modelo intermediário (10K-50K triângulos)
- Modelo complexo (50K-100K triângulos)
- Modelo muito complexo (>100K triângulos)

### 4.2 Comportamento em Recursos Limitados
- Verificar degradação controlada em dispositivos de baixa performance
- Testar comportamento com bateria baixa
- Verificar comportamento em dispositivos com restrições de WebGL

### 4.3 Integração com UI
- Testar ajuste do tamanho do container em diferentes layouts
- Verificar interação entre controles de UI e visualizador 3D
- Verificar atualização de cor ao selecionar filamento diferente

## 5. Procedimento de Teste

### 5.1 Preparação
1. Acessar site da Taverna da Impressão em cada dispositivo de teste
2. Acessar a ferramenta de diagnóstico `/public/diagnostic-model-viewer.php`
3. Verificar resultados do diagnóstico e resolver problemas encontrados
4. Preparar planilha para registro de resultados

### 5.2 Execução dos Testes
1. Para cada dispositivo e navegador, acessar a página de um produto com modelo 3D
2. Testar cenários listados no item 3 (Metodologia)
3. Registrar resultados, problemas e observações
4. Capturar screenshots ou vídeos que demonstrem problemas
5. Repetir testando diferentes modelos 3D (simples/complexos)

### 5.3 Registro de Resultados
Para cada teste, registrar:
- Dispositivo/Navegador/Versão
- Resultado (Sucesso/Falha/Parcial)
- Observações (comportamento específico, problemas, etc.)
- FPS médio durante uso
- Tempo de carregamento
- Problemas específicos encontrados

## 6. Critérios de Aceitação

### 6.1 Critérios Essenciais
- Visualizador 3D carrega e exibe o modelo em todos os dispositivos testados
- Usuário consegue interagir com o modelo (rotação, zoom)
- Performance aceitável (mínimo 20 FPS) em dispositivos intermediários
- Sem travamentos ou crashes
- Adaptação correta ao tamanho da tela e orientação

### 6.2 Critérios Desejáveis
- Performance boa (30+ FPS) em dispositivos intermediários
- Carregamento rápido (<5s) em conexões boas
- Funcionamento adequado em dispositivos de entrada
- Boa integração com UI (cores de filamento, etc.)
- Resposta adequada às limitações do dispositivo (LOD adaptativo, etc.)

## 7. Relatório de Resultados

### 7.1 Estrutura do Relatório
- Resumo executivo
- Dispositivos e ambientes testados
- Resultados por categoria de teste
- Problemas encontrados (organizados por severidade)
- Recomendações para melhorias
- Capturas de tela/evidências

### 7.2 Classificação de Problemas
- **Crítico**: Impede o uso do visualizador 3D (crash, não carrega, etc.)
- **Grave**: Afeta significativamente a experiência (FPS muito baixo, controles não funcionam, etc.)
- **Moderado**: Afeta parcialmente a experiência (performance irregular, problemas visuais menores)
- **Menor**: Problemas que não afetam funcionalidade (pequenos glitches visuais, atrasos leves, etc.)

## 8. Ferramentas e Recursos

### 8.1 Ferramentas de Diagnóstico
- Ferramenta integrada `/public/diagnostic-model-viewer.php`
- Painel de desenvolvedor do navegador (Chrome DevTools, Safari Web Inspector)
- Remote debugging com Chrome/Safari para dispositivos físicos

### 8.2 Ferramentas de Performance
- FPS meters (via Chrome DevTools)
- Monitoramento de memória
- Ferramentas de profiling para WebGL

### 8.3 Recursos Necessários
- Acesso a diversos dispositivos móveis para teste
- Diferentes versões de navegadores
- Modelos 3D de diferentes complexidades
- Conexões de rede de diferentes velocidades

## 9. Timeline e Execução

### 9.1 Fases de Teste
1. **Preparação** (1 dia): Configuração do ambiente, dispositivos e ferramentas
2. **Testes Iniciais** (2 dias): Testes básicos em dispositivos primários
3. **Testes Expandidos** (3-4 dias): Testes em dispositivos adicionais e cenários
4. **Análise e Documentação** (2 dias): Compilação de resultados e recomendações

### 9.2 Equipe Sugerida
- 1-2 testadores com acesso a diferentes dispositivos
- 1 desenvolvedor para suporte técnico e correções imediatas
- 1 coordenador para documentação e análise

---

## Apêndice A: Estrutura do Visualizador 3D

### Arquivos Principais
- `public/assets/js/model-viewer.js` - Classe principal do visualizador
- `public/assets/js/model-loader-worker.js` - Web Worker para processamento assíncrono
- `app/helpers/ModelViewerHelper.php` - Helper PHP para integração
- `app/views/product.php` - Integração na página de produto

### Recursos Principais
- Suporte para formatos STL e OBJ
- Level of Detail (LOD) dinâmico
- Otimizações para dispositivos móveis
- Web Workers para processamento assíncrono
- Adaptação de qualidade baseada em FPS

## Apêndice B: Exemplos de Resultados Esperados

| Dispositivo | Carregamento | FPS (Simples) | FPS (Complexo) | Adaptação LOD | Resultado |
|-------------|--------------|---------------|----------------|---------------|-----------|
| iPhone 13   | 2-3s         | 50-60         | 30-40          | Funcional     | ✅        |
| Pixel 5     | 3-4s         | 45-55         | 25-35          | Funcional     | ✅        |
| Galaxy A50  | 5-7s         | 30-40         | 15-25          | Funcional     | ⚠️        |
| iPhone 8    | 4-6s         | 35-45         | 20-30          | Funcional     | ✅        |
| Tablet LG   | 3-5s         | 40-50         | 20-30          | Funcional     | ✅        |
