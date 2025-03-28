# Instruções para Continuidade de Desenvolvimento do Projeto TAVERNA DA IMPRESSÃO

## 1. Informações do Projeto

- **Nome**: TAVERNA DA IMPRESSÃO E-commerce
- **Repositório GitHub**: https://github.com/Lucasdoreac/taverna-impressao-ecommerce
- **Caminho Local**: C:\MCP\taverna-impressao
- **Estrutura**: Framework MVC customizado para PHP

## 2. Recuperação do Estado do Projeto

```javascript
// Primeiro comando a ser executado em cada novo chat
const projectStatus = await read_file({path: 'C:\\MCP\\taverna-impressao\\project-status.json'});
console.log("Estado atual:", JSON.parse(projectStatus));
```

## 3. Análise do Estado Atual

```javascript
// Analisar o repositório para entender estrutura atual
await sequentialthinking({
  thought: "Analisando estado atual do projeto Taverna da Impressão",
  totalThoughts: 3,
  thoughtNumber: 1,
  nextThoughtNeeded: true
});
```

## 4. Fluxo de Desenvolvimento

### 4.1 Planejamento
```javascript
// Definir objetivo da sessão
const objetivoSessao = "Implementar [componente específico]";
console.log(`Foco atual: ${objetivoSessao}`);
```

### 4.2 Implementação Local
```javascript
// Criar/atualizar arquivo local
await write_file({
  path: "C:\\MCP\\taverna-impressao\\caminho\\arquivo.php",
  content: `// Código do componente`
});
```

### 4.3 Atualização no GitHub
```javascript
// Sincronizar alterações com GitHub
await create_or_update_file({
  owner: "Lucasdoreac",
  repo: "taverna-impressao-ecommerce",
  branch: "main",
  path: "caminho/arquivo.php",
  content: `// Código do componente`,
  message: "Add: [descrição clara]"
});
```

### 4.4 Teste Rápido
```javascript
// Testar lógica antes do commit final
await repl({
  code: `
    // Teste do componente atual
    console.log("Testando funcionalidade X");
  `
});
```

## 5. Atualização de Estado

```javascript
// Após cada milestone significativo
// 1. Ler o estado atual
const estadoAtual = JSON.parse(await read_file({path: 'C:\\MCP\\taverna-impressao\\project-status.json'}));

// 2. Atualizar campos relevantes
estadoAtual.development.currentFile = "caminho/atual.php";
estadoAtual.projectInfo.lastUpdated = new Date().toISOString();
estadoAtual.context.lastThought = "Implementação do componente X concluída";

// 3. Salvar localmente
await write_file({
  path: "C:\\MCP\\taverna-impressao\\project-status.json",
  content: JSON.stringify(estadoAtual, null, 2)
});

// 4. Sincronizar com GitHub
await create_or_update_file({
  owner: "Lucasdoreac",
  repo: "taverna-impressao-ecommerce",
  branch: "main",
  path: "project-status.json",
  content: JSON.stringify(estadoAtual, null, 2),
  message: "Update: estado do projeto"
});
```

## 6. Prevenção de Interrupções

```javascript
// Execute antes de qualquer pausa prevista
const estadoAtual = JSON.parse(await read_file({path: 'C:\\MCP\\taverna-impressao\\project-status.json'}));

estadoAtual.context.lastThought = "Parado em: [descrição exata do ponto]";
estadoAtual.context.nextSteps = ["Próximo passo 1", "Próximo passo 2"];
estadoAtual.projectInfo.lastUpdated = new Date().toISOString();

// Salvar localmente
await write_file({
  path: "C:\\MCP\\taverna-impressao\\project-status.json",
  content: JSON.stringify(estadoAtual, null, 2)
});

// Sincronizar com GitHub
await create_or_update_file({
  owner: "Lucasdoreac",
  repo: "taverna-impressao-ecommerce",
  branch: "main",
  path: "project-status.json",
  content: JSON.stringify(estadoAtual, null, 2),
  message: "Save: ponto de continuidade"
});
```

## 7. Desenvolvimento por Componentes

### Ordem Recomendada
1. Model base e models específicos
2. Controllers básicos
3. Views principais e sistema de templates
4. Sistema de autenticação
5. Catálogo de produtos
6. Carrinho de compras
7. Checkout e pagamento
8. Sistema de personalização
9. Painel administrativo

### Verificações por Componente
- Funcionamento isolado
- Compatibilidade com PHP 7.4
- Segurança (SQL injection, XSS)
- Responsividade (para views)
- Documentação PHPDoc

## 8. Resolução de Problemas

### Código com Erro
```javascript
// Depurar em isolamento
await repl({
  code: `
    // Versão isolada da função problemática
    try {
      // Código suspeito
    } catch (e) {
      console.error("Erro específico:", e.message);
    }
  `
});
```

### Perda de Contexto
1. Reconstruir contexto do project-status.json local
2. Verificar últimos commits via list_commits
3. Revisar arquivos principais

## 9. Ferramentas MCP Disponíveis

- **read_file/write_file**: Para manipulação local
- **create_or_update_file**: Para GitHub
- **list_commits**: Para histórico de mudanças
- **search_code**: Para buscar exemplos
- **repl**: Para testar código
- **sequentialthinking**: Para análise passo a passo

## 10. Finalização de Sessão

```javascript
// Resumo de progresso e próximos passos
const estadoAtual = JSON.parse(await read_file({path: 'C:\\MCP\\taverna-impressao\\project-status.json'}));

console.log("Progresso na sessão:");
console.log("- Componentes concluídos:", estadoAtual.components.completed);
console.log("- Próximos passos:", estadoAtual.context.nextSteps);
console.log("Continuidade garantida via project-status.json local e GitHub");
```

---

**Início rápido para nova sessão:**
1. Cole este prompt
2. Execute recuperação de estado local
3. Use sequentialthinking para análise
4. Continue do ponto registrado em context.lastThought