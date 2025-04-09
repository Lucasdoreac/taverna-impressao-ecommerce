/**
 * Script de verificação de segurança
 * Taverna da Impressão 3D
 * 
 * Este script verifica vulnerabilidades comuns em arquivos PHP.
 * Execute este script periodicamente para garantir a segurança do site.
 */

const BASE_PATH = "C:\\MCP\\taverna\\taverna-impressao-ecommerce\\";

/**
 * Verifica problemas de segurança em arquivos PHP
 */
async function checkSecurityIssues() {
  console.log("🔍 INICIANDO VERIFICAÇÃO DE SEGURANÇA");
  console.log("=====================================");
  
  try {
    // Listar todos os arquivos PHP da aplicação
    const controllersDir = `${BASE_PATH}app/controllers/`;
    const files = await listPhpFiles(controllersDir);
    
    console.log(`\nEncontrados ${files.length} arquivos para análise.\n`);
    
    // Resultados das verificações
    const results = {
      validationIssues: [],
      csrfIssues: [],
      sqlInjectionIssues: [],
      xssIssues: [],
      exitDieUsage: []
    };
    
    // Analisar cada arquivo
    for (const file of files) {
      console.log(`Analisando: ${file}`);
      
      try {
        // CORRETO: Use a ferramenta MCP read_file para acessar arquivos
        const content = await read_file({
          path: file
        });
        
        // Verificar uso de getValidatedParam
        const hasValidation = content.includes('getValidatedParam');
        if (!hasValidation && content.includes('_POST') || content.includes('_GET')) {
          results.validationIssues.push(file);
          console.log("  ⚠️ Não utiliza getValidatedParam para validação");
        }
        
        // Verificar proteção CSRF
        const hasCsrfCheck = content.includes('validateCsrfToken');
        if (!hasCsrfCheck && content.includes('method="post"') || content.includes('method="POST"')) {
          results.csrfIssues.push(file);
          console.log("  ⚠️ Não valida token CSRF em formulários POST");
        }
        
        // Verificar potencial SQL Injection
        const hasSqlInjection = /\$sql\s*\.=|\$sql\s*=.*\$_/g.test(content);
        if (hasSqlInjection) {
          results.sqlInjectionIssues.push(file);
          console.log("  ⚠️ Potencial SQL Injection detectado");
        }
        
        // Verificar potencial XSS
        const hasXssVulnerability = /echo\s+\$_(?:POST|GET|REQUEST|SESSION)/g.test(content);
        if (hasXssVulnerability) {
          results.xssIssues.push(file);
          console.log("  ⚠️ Potencial XSS detectado");
        }
        
        // Verificar uso de exit() ou die()
        const usesExit = content.includes('exit(') || content.includes('die(');
        if (usesExit) {
          results.exitDieUsage.push(file);
          console.log("  ⚠️ Uso de exit() ou die() detectado");
        }
        
      } catch (error) {
        console.log(`  ❌ Erro ao analisar arquivo: ${error.message}`);
      }
    }
    
    // Resumo dos resultados
    console.log("\n\n📊 RESUMO DE PROBLEMAS ENCONTRADOS");
    console.log("=====================================");
    console.log(`Problemas de validação: ${results.validationIssues.length}`);
    console.log(`Problemas de CSRF: ${results.csrfIssues.length}`);
    console.log(`Potenciais SQL Injection: ${results.sqlInjectionIssues.length}`);
    console.log(`Potenciais XSS: ${results.xssIssues.length}`);
    console.log(`Uso de exit()/die(): ${results.exitDieUsage.length}`);
    
    // Detalhes dos problemas
    if (results.validationIssues.length > 0) {
      console.log("\n⚠️ Arquivos sem validação adequada:");
      results.validationIssues.forEach(file => console.log(`  - ${file}`));
    }
    
    if (results.csrfIssues.length > 0) {
      console.log("\n⚠️ Arquivos sem proteção CSRF:");
      results.csrfIssues.forEach(file => console.log(`  - ${file}`));
    }
    
    if (results.sqlInjectionIssues.length > 0) {
      console.log("\n⚠️ Potenciais SQL Injection:");
      results.sqlInjectionIssues.forEach(file => console.log(`  - ${file}`));
    }
    
    if (results.xssIssues.length > 0) {
      console.log("\n⚠️ Potenciais XSS:");
      results.xssIssues.forEach(file => console.log(`  - ${file}`));
    }
    
    if (results.exitDieUsage.length > 0) {
      console.log("\n⚠️ Uso de exit()/die():");
      results.exitDieUsage.forEach(file => console.log(`  - ${file}`));
    }
    
    return results;
    
  } catch (error) {
    console.error("❌ Erro geral na verificação de segurança:", error);
    throw error;
  }
}

/**
 * Lista todos os arquivos PHP em um diretório e seus subdiretórios
 * @param {string} dir Diretório a ser verificado
 * @returns {Promise<string[]>} Lista de caminhos de arquivos PHP
 */
async function listPhpFiles(dir) {
  try {
    const files = [];
    
    // CORRETO: Use a ferramenta MCP list_directory para listar diretórios
    const items = await list_directory({
      path: dir
    });
    
    // Processar itens retornados
    for (const item of items) {
      const isDirectory = item.startsWith('[DIR]');
      const itemName = isDirectory ? item.substring(5).trim() : item.substring(6).trim();
      const fullPath = `${dir}${itemName}`;
      
      if (isDirectory) {
        // Recursivamente listar arquivos em subdiretórios
        const subFiles = await listPhpFiles(`${fullPath}/`);
        files.push(...subFiles);
      } else if (itemName.endsWith('.php')) {
        files.push(fullPath);
      }
    }
    
    return files;
  } catch (error) {
    console.error(`Erro ao listar arquivos em ${dir}:`, error);
    return [];
  }
}

// Exportar função para uso no REPL
module.exports = {
  checkSecurityIssues
};