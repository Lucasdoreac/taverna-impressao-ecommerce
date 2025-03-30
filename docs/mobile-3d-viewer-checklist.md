# Checklist de Teste Rápido do Visualizador 3D em Dispositivos Móveis

Este checklist é uma versão simplificada do plano de testes completo, destinado a membros da equipe para testar rapidamente o visualizador 3D em seus próprios dispositivos móveis.

## Informações do Dispositivo

- **Nome do dispositivo**: ______________________
- **Sistema Operacional**: ______________________ (versão: ______)
- **Navegador**: ______________________ (versão: ______)
- **Data do teste**: ______________________
- **Testador**: ______________________

## Passos Preliminares

1. [ ] Acessar o site em produção (https://darkblue-cattle-647559.hostingersite.com)
2. [ ] Navegar para a ferramenta de diagnóstico `/diagnostic-model-viewer.php`
3. [ ] Verificar se todas as dependências são carregadas corretamente
4. [ ] Verificar se a ferramenta consegue carregar o modelo de teste

## Testes Básicos

### 1. Carregamento do Visualizador
- [ ] Navegar para um produto com modelo 3D
- [ ] Clicar na aba "Modelo 3D"
- [ ] O modelo é exibido corretamente? Sim ( ) Não ( )
- [ ] A animação de carregamento aparece e desaparece? Sim ( ) Não ( )
- **Tempo aproximado de carregamento**: ______ segundos

### 2. Interação Básica
- [ ] Consegue rotacionar o modelo arrastando? Sim ( ) Não ( )
- [ ] Consegue aplicar zoom (pinch/spread)? Sim ( ) Não ( )
- [ ] A rotação automática está funcionando? Sim ( ) Não ( )
- [ ] O botão de reset reposiciona o modelo? Sim ( ) Não ( )

### 3. Resposta do Sistema
- [ ] A interação é fluida (sem travamentos)? Sim ( ) Não ( )
- [ ] O navegador permanece responsivo? Sim ( ) Não ( )
- [ ] Performance estimada: Boa ( ) Aceitável ( ) Ruim ( )

### 4. Orientação do Dispositivo
- [ ] Teste em orientação retrato:
  - [ ] O visualizador se ajusta corretamente? Sim ( ) Não ( )
  - [ ] Os controles estão acessíveis? Sim ( ) Não ( )
- [ ] Teste em orientação paisagem:
  - [ ] O visualizador se ajusta corretamente? Sim ( ) Não ( )
  - [ ] Os controles estão acessíveis? Sim ( ) Não ( )
- [ ] Ao girar o dispositivo, o visualizador se adapta? Sim ( ) Não ( )

### 5. Integração com a Interface
- [ ] A alternância entre abas de imagens e 3D funciona? Sim ( ) Não ( )
- [ ] Ao selecionar uma cor de filamento diferente, a cor do modelo muda? Sim ( ) Não ( )

## Testes Avançados (se possível)

### 6. Diferentes Modelos
- [ ] Teste com pelo menos 3 produtos diferentes com modelos 3D
- [ ] Todos os produtos exibiram seus modelos corretamente? Sim ( ) Não ( )
- [ ] Observou diferença na performance entre modelos? Sim ( ) Não ( )

### 7. Condições de Rede Diferentes
- [ ] Teste com Wi-Fi: Funciona bem ( ) Regular ( ) Mal ( )
- [ ] Teste com dados móveis: Funciona bem ( ) Regular ( ) Mal ( )

### 8. Uso Prolongado
- [ ] Após interagir com vários modelos 3D por 5+ minutos:
  - [ ] O dispositivo esquenta significativamente? Sim ( ) Não ( )
  - [ ] A performance degrada? Sim ( ) Não ( )
  - [ ] O navegador apresenta lentidão geral? Sim ( ) Não ( )

## Problemas Encontrados

Descreva qualquer problema encontrado durante o teste:

1. **Problema**:
   - **Passos para reproduzir**:
   - **Frequência**: Sempre ( ) Às vezes ( ) Raramente ( )
   - **Severidade**: Crítica ( ) Alta ( ) Média ( ) Baixa ( )

2. **Problema**:
   - **Passos para reproduzir**:
   - **Frequência**: Sempre ( ) Às vezes ( ) Raramente ( )
   - **Severidade**: Crítica ( ) Alta ( ) Média ( ) Baixa ( )

3. **Problema**:
   - **Passos para reproduzir**:
   - **Frequência**: Sempre ( ) Às vezes ( ) Raramente ( )
   - **Severidade**: Crítica ( ) Alta ( ) Média ( ) Baixa ( )

## Sugestões de Melhoria

Liste quaisquer sugestões para melhorar a experiência do visualizador 3D:

1. 
2. 
3. 

## Impressão Geral

- **Facilidade de uso**: Excelente ( ) Boa ( ) Regular ( ) Ruim ( )
- **Performance**: Excelente ( ) Boa ( ) Regular ( ) Ruim ( )
- **Estabilidade**: Excelente ( ) Boa ( ) Regular ( ) Ruim ( )
- **Experiência geral**: Excelente ( ) Boa ( ) Regular ( ) Ruim ( )

## Comentários Adicionais

_Utilize este espaço para comentários adicionais sobre o teste:_

____________________________________________________________
____________________________________________________________
____________________________________________________________
____________________________________________________________
____________________________________________________________

---

### Instruções para submissão dos resultados

1. Tire screenshots dos problemas encontrados (quando possível)
2. Preencha este formulário
3. Envie para o email do desenvolvedor responsável ou faça upload no diretório compartilhado do projeto
4. Se possível, gravar um curto vídeo da interação com o visualizador 3D

**Obrigado por sua contribuição para melhorar o visualizador 3D da Taverna da Impressão!**
