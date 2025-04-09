# Instruções para Continuidade de Desenvolvimento Local - Taverna da Impressão 3D

## 1. Recuperação do Estado do Projeto

```javascript
// OBRIGATÓRIO: Primeiro comando a ser executado em cada novo chat
const projectStatus = await window.fs.readFile('C:/MCP/taverna/taverna-impressao-ecommerce/project-status.json', { encoding: 'utf8' });
const currentStatus = JSON.parse(projectStatus);
console.log("STATUS ATUAL:", currentStatus);

// Verificar componentes críticos
const criticalComponents = [
  "app/models/ProductModel.php",
  "app/views/product.php",
  "app/config/config.php",
  "app/controllers/AdminDashboardController.php",
  "app/controllers/IntegrationController.php",
  "app/controllers/NotificationController.php",
  "app/controllers/NotificationPreferenceController.php",
  "app/controllers/PrintQueueController.php",
  "app/lib/Security/SecurityManager.php",
  "public/assets/js/model-viewer.js"
];

for (const file of criticalComponents) {
  try {
    const fileContent = await window.fs.readFile('C:/MCP/taverna/taverna-impressao-ecommerce/' + file, { encoding: 'utf8' });
    console.log(`Arquivo ${file} verificado com sucesso.`);
  } catch (error) {
    console.log(`ALERTA: Arquivo ${file} não encontrado ou não acessível.`);
  }
}
```

## 2. Análise do Estado Atual

Use sequentialthinking para analisar o estado:

```javascript
await sequentialthinking({
  thought: "Analisando o estado atual do projeto baseado no project-status.json. Verificando componentes concluídos, em progresso e próximas prioridades no roadmap, com foco especial nas recomendações de segurança.",
  thoughtNumber: 1,
  totalThoughts: 3,
  nextThoughtNeeded: true
});
```

## 3. Fluxo de Desenvolvimento

Para cada nova funcionalidade ou modificação:

1. Planejamento

   ```javascript
   // Definição do componente e suas dependências
   const componentDefinition = {
     name: "Nome do Componente",
     description: "Descrição detalhada da funcionalidade",
     dependencies: ["Componente1", "Componente2"],
     files: ["caminho/para/arquivo1.php", "caminho/para/arquivo2.js"],
     tasks: [
       "Tarefa específica 1",
       "Tarefa específica 2"
     ]
   };
   
   // Atualizar o project-status.json
   let updatedStatus = { ...currentStatus };
   updatedStatus.components.inProgress.push(componentDefinition.name);
   updatedStatus.development.currentComponent = componentDefinition.name;
   updatedStatus.development.inProgress = true;
   updatedStatus.projectInfo.lastUpdated = new Date().toISOString();
   
   // Salvar o status atualizado
   await write_file({
     path: "C:/MCP/taverna/taverna-impressao-ecommerce/project-status.json",
     content: JSON.stringify(updatedStatus, null, 2)
   });
   ```

2. Implementação

   - Usar write_file para cada arquivo de forma atômica
   - NUNCA modificar múltiplos arquivos em uma única operação
   - Testar no REPL antes de commit final
   - Verificar SE o arquivo foi modificado corretamente antes de prosseguir

3. Atualização do Status

   - Atualizar project-status.json após CADA modificação significativa
   - Registrar apenas conclusões práticas e próximos passos
   - Mover componentes concluídos de "inProgress" para "completed"

## 4. Padrões de Segurança (Prioridade Atual)

1. **Proteção contra XSS**
   ```php
   // Implementar em todos os controllers
   private function sanitizeInput($input) {
     return htmlentities($input, ENT_QUOTES, 'UTF-8');
   }
   
   // Usar em todas as entradas de usuário
   $sanitizedValue = $this->sanitizeInput($_POST['field']);
   ```

2. **Evitar exit() e die()**
   ```php
   // Em vez de:
   header('Location: ' . BASE_URL . 'admin/dashboard');
   exit;
   
   // Usar:
   private function redirect($path) {
     header('Location: ' . BASE_URL . $path);
   }
   
   $this->redirect('admin/dashboard');
   return;
   ```

3. **Validação de Uploads**
   ```php
   // Validar e sanitizar todos os dados recebidos
   $typeId = (int)$data['typeId'];
   $channelId = (int)$data['channelId'];
   
   // Validar contra listas permitidas
   $allowedTypes = ['jpg', 'png', 'pdf'];
   if (!in_array($fileExtension, $allowedTypes)) {
     throw new Exception("Tipo de arquivo não permitido");
   }
   ```

4. **Proteção CSRF**
   ```php
   // Gerar token
   function generateCsrfToken() {
     if (empty($_SESSION['csrf_token'])) {
       $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
     }
     return $_SESSION['csrf_token'];
   }
   
   // Validar token
   function validateCsrfToken($token) {
     if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
       throw new Exception('CSRF token inválido');
     }
     return true;
   }
   ```

## 5. Melhorias de Segurança Prioritárias

1. **Implementar CSRF tokens em todos os formulários**
   - Adicionar geração de token em cada formulário
   - Validar token em todos os endpoints POST
   - Regenerar token após cada uso

2. **Adicionar validação de entrada em todos os controllers**
   - Criar biblioteca centralizada de validação
   - Definir regras específicas por tipo de dado
   - Aplicar validação em cascata (tipo → formato → conteúdo)

3. **Implementar Content Security Policy (CSP)**
   - Configurar cabeçalhos CSP adequados
   - Definir política de recursos confiáveis
   - Testar compatibilidade com funcionalidades existentes

## 6. Boas Práticas de Desenvolvimento

1. **Verificações**
   - SEMPRE verificar se o arquivo foi modificado corretamente
   - SEMPRE verificar os arquivos críticos após atualizações
   - NUNCA assumir que um arquivo foi salvo sem confirmar

2. **Documentação**
   - Adicionar comentários em funções complexas
   - Atualizar a documentação após cada nova feature
   - Documentar todas as correções de segurança implementadas
   - Manter project-status.json atualizado

3. **Organização**
   - Manter o código modular e reutilizável
   - Documentar decisões importantes
   - Usar componentes reutilizáveis

## 7. Em Caso de Interrupção

1. Salvar estado atual:

```javascript
// EXECUTAR URGENTEMENTE se houver risco de interrupção
let safeStatus = { ...currentStatus };
safeStatus.context.incompleteOperations = true;
safeStatus.context.pendingChanges = {
  file: "caminho/do/arquivo-atual", // arquivo sendo modificado
  description: "Descrição da alteração em andamento",
  codeFragment: "// Código em progresso que pode ser perdido",
  nextSteps: [
    "Próximo passo detalhado 1",
    "Próximo passo detalhado 2"
  ]
};
safeStatus.context.lastUpdated = new Date().toISOString();

await write_file({
  path: "C:/MCP/taverna/taverna-impressao-ecommerce/project-status.json",
  content: JSON.stringify(safeStatus, null, 2)
});
```

2. No novo chat:
   - Colar estas instruções
   - Executar recuperação de estado
   - Verificar project-status.json para contexto.incompleteOperations
   - Continuar do ponto de parada

## 8. Resolução de Problemas

1. **Erro no código:**
   - Verificar logs no REPL
   - Consultar implementações anteriores em arquivos similares
   - Consultar commits anteriores
   - Testar em ambiente isolado

2. **Problemas de segurança:**
   - Usar análise do Amazon Q
   - Realizar revisão manual de código
   - Analisar logs de erro em busca de padrões suspeitos

3. **Erros comuns:**
   - XSS: Falta de sanitização de entrada/saída
   - CSRF: Ausência de tokens de validação
   - Injeção SQL: Consultas sem prepared statements
   - Upload inseguro: Falta de validação de tipo/tamanho

4. **Verificações de segurança:**
   
   ```javascript
   // Usando REPL para buscar código problemático
   await repl({
     code: `
       // Pesquisar por uso de exit()
       const fileContent = await window.fs.readFile('C:/MCP/taverna/taverna-impressao-ecommerce/app/controllers/IntegrationController.php', { encoding: 'utf8' });
       const exitCount = (fileContent.match(/exit\(/g) || []).length;
       console.log("Ocorrências de exit():", exitCount);
       
       // Pesquisar por potenciais problemas de XSS
       const xssPattern = /echo\s+\$_(?:POST|GET|REQUEST)/g;
       const xssMatches = fileContent.match(xssPattern) || [];
       console.log("Potenciais problemas de XSS:", xssMatches);
     `
   });
   ```

## 9. Ferramentas Disponíveis

1. **Leitura e Escrita de Arquivos**
   - `window.fs.readFile`: Leitura de arquivos
   - `write_file`: Escrita de arquivos

2. **Testes e Desenvolvimento**
   - `repl`: Execução de código JavaScript para testes
   - `artifacts`: Criação de componentes visuais para UI
   - `search_files`: Busca de arquivos por padrão
   - `list_directory`: Lista conteúdo de diretórios
   - `create_directory`: Cria diretórios

## 10. Testes de Segurança

```javascript
await repl({
  code: `
    // Testar sanitização de entradas
    const testSanitization = () => {
      const maliciousInputs = [
        '<script>alert("XSS")</script>',
        'SELECT * FROM users',
        '../../etc/passwd',
        '<?php echo "backdoor"; ?>'
      ];
      
      const sanitizedResults = maliciousInputs.map(input => {
        return {
          original: input,
          sanitized: htmlentities(input, { flags: 'ENT_QUOTES', encoding: 'UTF-8' })
        };
      });
      
      console.log("Resultados de sanitização:");
      console.table(sanitizedResults);
      
      return {
        success: true,
        safe: sanitizedResults.every(r => r.sanitized !== r.original)
      };
    };
    
    // Função de simulação de htmlentities
    function htmlentities(str, options = {}) {
      const flags = options.flags || 'ENT_QUOTES';
      const encoding = options.encoding || 'UTF-8';
      
      // Simular comportamento de htmlentities
      return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }
    
    console.log(testSanitization());
  `
});
```

## 11. Regras Obrigatórias de Segurança

### SEMPRE:
- **SEMPRE sanitize toda entrada de usuário antes de processá-la ou exibi-la**
- **SEMPRE use prepared statements para consultas SQL**
- **SEMPRE valide tipos de arquivo e tamanho antes de permitir uploads**
- **SEMPRE use CSRF tokens em todos os formulários**
- **SEMPRE valide todas as entradas de dados (tipo, formato, tamanho)**
- **SEMPRE limite permissões de arquivos e diretórios ao mínimo necessário**
- **SEMPRE utilize os componentes da biblioteca Security para operações sensíveis**

### NUNCA:
- **NUNCA confie em dados provenientes do cliente (incluindo cookies e headers)**
- **NUNCA armazene senhas em texto plano**
- **NUNCA concatene entrada de usuário diretamente em consultas SQL**
- **NUNCA use eval() ou funções similares com entrada de usuário**
- **NUNCA use exit() ou die() em controllers**
- **NUNCA exiba mensagens de erro detalhadas para o usuário final**
- **NUNCA permita acesso a arquivos fora dos diretórios permitidos**

## 12. Workflow de Desenvolvimento Local

1. **Análise e planejamento**
   - Verificar o status do projeto
   - Identificar a próxima tarefa a ser implementada
   - Validar a existência e o estado dos arquivos necessários

2. **Implementação**
   - Desenvolver a funcionalidade no filesystem local
   - Testar exaustivamente com o REPL
   - Verificar conformidade com padrões de segurança

3. **Documentação e atualização de status**
   - Atualizar documentação na biblioteca de segurança
   - Registrar as alterações no project-status.json
   - Preparar os comentários para o futuro commit no GitHub

4. **Fila de commits para GitHub**
   - Registrar as alterações na seção pendingCommits do project-status.json
   - Quando as implementações atingirem completude, sincronizar com GitHub

## 13. Comandos Essenciais para Desenvolvimento Local

```javascript
// 1. Ler arquivo
const fileContent = await window.fs.readFile('C:/MCP/taverna/taverna-impressao-ecommerce/caminho/arquivo.php', { encoding: 'utf8' });

// 2. Escrever arquivo
await write_file({
  path: "C:/MCP/taverna/taverna-impressao-ecommerce/caminho/arquivo.php",
  content: "conteúdo do arquivo"
});

// 3. Listar diretório
const files = await list_directory({
  path: "C:/MCP/taverna/taverna-impressao-ecommerce/caminho/diretorio"
});

// 4. Criar diretório
await create_directory({
  path: "C:/MCP/taverna/taverna-impressao-ecommerce/caminho/novo-diretorio"
});

// 5. Buscar arquivos por padrão
const searchResults = await search_files({
  path: "C:/MCP/taverna/taverna-impressao-ecommerce/app",
  pattern: "*.php"
});

// 6. Testar código JavaScript
await repl({
  code: `// Código JavaScript para testar`
});
```

---

Para iniciar uma nova sessão:

1. Cole estas instruções
2. Execute recuperação de estado
3. Use sequentialthinking para análise
4. Continue o desenvolvimento baseado no estado atual do projeto
5. Lembre-se: todas as mudanças são feitas LOCALMENTE, sem envio para GitHub
