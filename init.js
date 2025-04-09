/**
 * Script de inicialização do projeto
 * Taverna da Impressão 3D
 * 
 * Este script deve ser executado no início de cada sessão de desenvolvimento
 * para recuperar o estado atual do projeto e verificar componentes críticos.
 */

// Definir constantes
const BASE_PATH = "C:\\MCP\\taverna\\taverna-impressao-ecommerce\\";

/**
 * Carrega o estado do projeto a partir do project-status.json
 */
async function initializeProject() {
  console.log("🔄 Iniciando recuperação do estado do projeto...");
  
  try {
    // CORRETO: Use a ferramenta MCP read_file para acessar arquivos
    const projectStatusJson = await read_file({
      path: `${BASE_PATH}project-status.json`
    });
    
    const currentStatus = JSON.parse(projectStatusJson);
    
    console.log("✅ Estado do projeto carregado com sucesso");
    console.log("\n📋 INFORMAÇÕES DO PROJETO:");
    console.log(`Nome: ${currentStatus.projectInfo.name}`);
    console.log(`Versão: ${currentStatus.projectInfo.version}`);
    console.log(`Última atualização: ${new Date(currentStatus.projectInfo.lastUpdated).toLocaleString()}`);
    console.log(`URL de produção: ${currentStatus.projectInfo.productionUrl}`);
    
    console.log("\n🚧 DESENVOLVIMENTO ATUAL:");
    console.log(`Foco atual: ${currentStatus.development.currentFocus}`);
    console.log(`Componente atual: ${currentStatus.development.currentComponent}`);
    console.log(`Sprint: ${currentStatus.development.sprint}`);
    
    // Verificar componentes críticos
    console.log("\n🔍 VERIFICANDO COMPONENTES CRÍTICOS:");
    const criticalFiles = currentStatus.context.criticalFiles || [];
    let missingFiles = [];
    
    for (const file of criticalFiles) {
      try {
        // CORRETO: Use a ferramenta MCP read_file para acessar arquivos
        await read_file({
          path: `${BASE_PATH}${file}`
        });
        
        console.log(`  ✅ ${file}`);
      } catch (error) {
        console.log(`  ❌ ${file} - NÃO ENCONTRADO`);
        missingFiles.push(file);
      }
    }
    
    // Verificar problemas críticos
    console.log("\n⚠️ PROBLEMAS CRÍTICOS:");
    currentStatus.issues.critical.forEach(issue => {
      console.log(`  [${issue.id}] ${issue.description} - Status: ${issue.status}`);
    });
    
    // Verificar se há operações incompletas
    if (currentStatus.context.incompleteOperations) {
      console.log("\n⚠️ ATENÇÃO: Existem operações incompletas da sessão anterior!");
      console.log("Detalhes:");
      console.log(`  Arquivo: ${currentStatus.context.pendingChanges.file}`);
      console.log(`  Descrição: ${currentStatus.context.pendingChanges.description}`);
      console.log("  Próximos passos:");
      currentStatus.context.pendingChanges.nextSteps.forEach(step => {
        console.log(`    - ${step}`);
      });
    }
    
    // Roadmap imediato
    console.log("\n🛣️ PRÓXIMOS PASSOS IMEDIATOS:");
    currentStatus.roadmap.immediate.forEach(step => {
      console.log(`  - ${step}`);
    });
    
    // Mostrar instruções de uso das ferramentas MCP
    if (currentStatus.developmentEnvironment && currentStatus.developmentEnvironment.warnings) {
      console.log("\n⚠️ LEMBRETES IMPORTANTES:");
      currentStatus.developmentEnvironment.warnings.forEach(warning => {
        console.log(`  - ${warning}`);
      });
    }
    
    // Retornar o estado para uso posterior
    return currentStatus;
    
  } catch (error) {
    console.error("❌ Erro ao inicializar o projeto:", error);
    throw new Error("Não foi possível inicializar o projeto. Verifique se o arquivo project-status.json existe.");
  }
}

/**
 * Carregar PLANNING.md e TASK.md
 */
async function loadProjectDocumentation() {
  try {
    console.log("\n📚 CARREGANDO DOCUMENTAÇÃO DO PROJETO...");
    
    // Carregar PLANNING.md
    let planningMd;
    try {
      planningMd = await read_file({
        path: `${BASE_PATH}PLANNING.md`
      });
      console.log("✅ Planejamento do projeto carregado");
    } catch (error) {
      console.log("⚠️ Arquivo PLANNING.md não encontrado");
    }
    
    // Carregar TASK.md
    let taskMd;
    try {
      taskMd = await read_file({
        path: `${BASE_PATH}TASK.md`
      });
      console.log("✅ Lista de tarefas carregada");
    } catch (error) {
      console.log("⚠️ Arquivo TASK.md não encontrado");
    }
    
    return {
      planning: planningMd,
      tasks: taskMd
    };
  } catch (error) {
    console.error("❌ Erro ao carregar documentação:", error);
    return { planning: null, tasks: null };
  }
}

/**
 * Análise estruturada do estado do projeto usando sequentialthinking
 */
async function analyzeProjectState() {
  console.log("\n🧠 INICIANDO ANÁLISE ESTRUTURADA DO PROJETO...");
  
  // Carregar estado atual
  const currentStatus = await loadProjectState();
  
  // Preparar dados para análise
  const criticalIssues = currentStatus.issues.critical.filter(issue => issue.status !== "Resolvido");
  const inProgressComponents = currentStatus.components.inProgress;
  const immediateTasks = currentStatus.roadmap.immediate;
  
  // Etapa 1: Analisar estado geral
  await sequentialthinking({
    thought: `Analisando estado atual do projeto Taverna da Impressão 3D, versão ${currentStatus.projectInfo.version}. O foco atual é ${currentStatus.development.currentFocus}, com sprint "${currentStatus.development.sprint}". Existem ${criticalIssues.length} problemas críticos não resolvidos e ${inProgressComponents.length} componentes em desenvolvimento.`,
    thoughtNumber: 1,
    totalThoughts: 5,
    nextThoughtNeeded: true
  });
  
  // Etapa 2: Analisar componentes críticos
  await sequentialthinking({
    thought: `Verificando componentes críticos: ${currentStatus.components.inProgress.join(", ")}. O mais importante para foco é ${currentStatus.development.currentComponent}, que deve ser priorizado. Isso é fundamental para a segurança da aplicação.`,
    thoughtNumber: 2,
    totalThoughts: 5,
    nextThoughtNeeded: true
  });
  
  // Etapa 3: Analisar problemas críticos
  await sequentialthinking({
    thought: `Identificando problemas críticos pendentes: ${criticalIssues.map(i => i.id + ' (' + i.description + ')').join(", ")}. A prioridade mais alta é resolver CRIT-002 (Proteção CSRF) pois está relacionado à segurança dos formulários de dados.`,
    thoughtNumber: 3,
    totalThoughts: 5,
    nextThoughtNeeded: true
  });
  
  // Etapa 4: Definir prioridades
  await sequentialthinking({
    thought: `Baseado na análise, as prioridades devem ser: 1) Implementar CsrfProtection completa, 2) Atualizar formulários com tokens CSRF, 3) Configurar headers HTTP de segurança. Esta ordem maximiza a segurança da aplicação.`,
    thoughtNumber: 4,
    totalThoughts: 5,
    nextThoughtNeeded: true
  });
  
  // Etapa 5: Recomendar próximos passos
  await sequentialthinking({
    thought: `Recomendação para próximo passo: Implementar a classe CsrfProtection.php com métodos para gerar e validar tokens. Em seguida, atualizar todos os formulários com campos ocultos contendo tokens CSRF. Depois, integrar a validação CSRF nos controladores para rejeitar requisições sem tokens válidos.`,
    thoughtNumber: 5,
    totalThoughts: 5,
    nextThoughtNeeded: false
  });
  
  console.log("✅ Análise estruturada concluída");
}

/**
 * Função para inicialização completa
 */
async function loadProjectState() {
  const projectState = await initializeProject();
  const documentation = await loadProjectDocumentation();
  
  console.log("\n🚀 PROJETO PRONTO PARA CONTINUIDADE");
  return projectState;
}

// Exportar função para uso no REPL
module.exports = {
  loadProjectState,
  analyzeProjectState
};