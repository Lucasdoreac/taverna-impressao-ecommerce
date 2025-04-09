/**
 * Utilitários para gerenciamento do estado do projeto
 * Taverna da Impressão 3D
 */

// Definir constantes
const BASE_PATH = "C:\\MCP\\taverna\\taverna-impressao-ecommerce\\";
const DEV_MODE = "local"; // Altere para "remote" se estiver usando GitHub API

/**
 * Carrega o estado atual do projeto
 * @returns {Promise<Object>} O estado atual do projeto
 */
async function loadProjectState() {
  try {
    if (DEV_MODE === "local") {
      // CORRETO: Use a ferramenta MCP read_file para acessar arquivos
      const result = await read_file({
        path: `${BASE_PATH}project-status.json`
      });
      
      return JSON.parse(result);
    } else {
      // Implementação para GitHub API se necessário
      throw new Error("Modo remoto não implementado");
    }
  } catch (error) {
    console.error("Erro ao carregar status do projeto:", error);
    throw new Error("Não foi possível carregar o status do projeto");
  }
}

/**
 * Verifica componentes críticos do projeto
 * @param {Object} currentStatus Estado atual do projeto
 * @returns {Promise<Object>} Resultado da verificação
 */
async function checkCriticalComponents(currentStatus) {
  const criticalFiles = currentStatus.context.criticalFiles || [];
  const results = {};
  
  for (const file of criticalFiles) {
    try {
      // CORRETO: Use a ferramenta MCP read_file para acessar arquivos
      await read_file({
        path: `${BASE_PATH}${file}`
      });
      
      results[file] = { status: "OK", exists: true };
    } catch (error) {
      results[file] = { status: "MISSING", exists: false };
    }
  }
  
  return results;
}

/**
 * Salva arquivo no projeto
 * @param {string} path Caminho relativo do arquivo
 * @param {string} content Conteúdo do arquivo
 * @param {string} description Descrição da modificação
 * @returns {Promise<Object>} Resultado da operação
 */
async function saveFile(path, content, description = "Atualização") {
  try {
    // CORRETO: Use a ferramenta MCP write_file para escrever arquivos
    await write_file({
      path: `${BASE_PATH}${path}`,
      content: content
    });
    
    // Verificar se o arquivo foi salvo corretamente
    const savedContent = await read_file({
      path: `${BASE_PATH}${path}`
    });
    
    if (savedContent === content) {
      console.log(`✅ Arquivo ${path} salvo com sucesso`);
      return { success: true, content: savedContent };
    } else {
      console.error(`❌ Verificação falhou: conteúdo do arquivo ${path} não corresponde ao esperado`);
      return { success: false, error: "Verificação de conteúdo falhou" };
    }
  } catch (error) {
    console.error(`❌ Erro ao salvar arquivo ${path}:`, error);
    return { success: false, error: error.toString() };
  }
}

/**
 * Lê arquivo do projeto
 * @param {string} path Caminho relativo do arquivo
 * @returns {Promise<Object>} Conteúdo do arquivo
 */
async function readFile(path) {
  try {
    // CORRETO: Use a ferramenta MCP read_file para acessar arquivos
    const content = await read_file({
      path: `${BASE_PATH}${path}`
    });
    
    return { success: true, content };
  } catch (error) {
    console.error(`❌ Erro ao ler arquivo ${path}:`, error);
    return { success: false, error: error.toString() };
  }
}

/**
 * Atualiza o status do projeto
 * @param {Object} updates Alterações a serem aplicadas
 * @returns {Promise<Object>} Resultado da operação
 */
async function updateProjectStatus(updates) {
  try {
    // Carregar status atual
    const currentStatus = await loadProjectState();
    
    // Mesclar atualizações com o status atual
    const updatedStatus = { ...currentStatus };
    
    // Aplicar atualizações de forma recursiva
    function mergeUpdates(target, source) {
      Object.keys(source).forEach(key => {
        if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
          if (!target[key]) target[key] = {};
          mergeUpdates(target[key], source[key]);
        } else {
          target[key] = source[key];
        }
      });
    }
    
    mergeUpdates(updatedStatus, updates);
    
    // Atualizar timestamp
    updatedStatus.projectInfo.lastUpdated = new Date().toISOString();
    
    // CORRETO: Use a ferramenta MCP write_file para escrever arquivos
    await write_file({
      path: `${BASE_PATH}project-status.json`,
      content: JSON.stringify(updatedStatus, null, 2)
    });
    
    console.log("✅ Status do projeto atualizado com sucesso");
    return { success: true, status: updatedStatus };
  } catch (error) {
    console.error("❌ Erro ao atualizar status do projeto:", error);
    return { success: false, error: error.toString() };
  }
}

/**
 * Salva o estado atual antes de uma interrupção
 * @param {Object} pendingChanges Informações sobre alterações pendentes
 * @returns {Promise<Object>} Resultado da operação
 */
async function saveInterruptionState(pendingChanges) {
  try {
    const currentStatus = await loadProjectState();
    
    await updateProjectStatus({
      context: {
        incompleteOperations: true,
        pendingChanges: pendingChanges,
        lastUpdated: new Date().toISOString()
      }
    });
    
    console.log("✅ Estado salvo antes da interrupção");
    return { success: true };
  } catch (error) {
    console.error("❌ Erro ao salvar estado de interrupção:", error);
    return { success: false, error: error.toString() };
  }
}

// Exportar funções para uso no REPL
module.exports = {
  loadProjectState,
  checkCriticalComponents,
  saveFile,
  readFile,
  updateProjectStatus,
  saveInterruptionState,
  BASE_PATH
};