/**
 * Utilitários de visualização para o projeto Taverna da Impressão 3D
 * 
 * Este arquivo contém funções para gerar visualizações do projeto
 * usando artifacts.
 */

/**
 * Gera um diagrama de arquitetura do sistema
 * @returns {Promise<void>}
 */
async function generateArchitectureDiagram() {
  console.log("🔨 Gerando diagrama de arquitetura...");
  
  try {
    // Usar artifacts para criar um diagrama Mermaid
    await artifacts({
      command: "create",
      id: "architecture-diagram",
      type: "application/vnd.ant.mermaid",
      title: "Arquitetura do Sistema",
      content: `
graph TD
    A[Cliente Web] --> B[Controllers]
    B --> C[Models]
    B --> D[Views]
    C --> E[Database]
    F[SecurityManager] --> B
    F --> G[CsrfProtection]
    F --> H[Authentication]
    I[Controller Base] --> B
    
    %% Estilos
    classDef controller fill:#f9f,stroke:#333,stroke-width:2px;
    classDef model fill:#bbf,stroke:#333,stroke-width:2px;
    classDef view fill:#bfb,stroke:#333,stroke-width:2px;
    classDef security fill:#fbb,stroke:#333,stroke-width:2px;
    
    class B,I controller;
    class C,E model;
    class D view;
    class F,G,H security;
      `
    });
    
    console.log("✅ Diagrama de arquitetura gerado com sucesso");
  } catch (error) {
    console.error("❌ Erro ao gerar diagrama:", error);
  }
}

/**
 * Gera um mockup da loja
 * @returns {Promise<void>}
 */
async function generateStorefrontMockup() {
  console.log("🔨 Gerando mockup da loja...");
  
  try {
    // Usar artifacts para criar um componente React
    await artifacts({
      command: "create",
      id: "storefront-mockup",
      type: "application/vnd.ant.react",
      title: "Mockup da Loja Taverna da Impressão 3D",
      content: `
import React from 'react';

const StorefrontMockup = () => {
  return (
    <div className="p-4 border rounded">
      <header className="p-4 bg-gray-800 text-white flex justify-between items-center">
        <h1 className="text-xl font-bold">Taverna da Impressão 3D</h1>
        <nav className="flex space-x-4">
          <a href="#" className="hover:underline">Produtos</a>
          <a href="#" className="hover:underline">Categorias</a>
          <a href="#" className="hover:underline">Minha Conta</a>
          <a href="#" className="hover:underline">Carrinho (3)</a>
        </nav>
      </header>
      
      <main className="p-4">
        <div className="flex mb-4">
          <div className="w-1/4 pr-4">
            <div className="bg-gray-100 p-4 rounded">
              <h2 className="font-bold mb-2">Categorias</h2>
              <ul className="space-y-1">
                <li className="hover:bg-gray-200 p-1 cursor-pointer">Modelos 3D</li>
                <li className="hover:bg-gray-200 p-1 cursor-pointer">Filamentos</li>
                <li className="hover:bg-gray-200 p-1 cursor-pointer">Acessórios</li>
                <li className="hover:bg-gray-200 p-1 cursor-pointer">Ferramentas</li>
                <li className="hover:bg-gray-200 p-1 cursor-pointer">Peças</li>
                <li className="hover:bg-gray-200 p-1 cursor-pointer">Impressoras</li>
              </ul>
            </div>
          </div>
          
          <div className="w-3/4">
            <h2 className="text-xl font-bold mb-4">Modelos 3D em Destaque</h2>
            <div className="grid grid-cols-3 gap-4">
              {[1, 2, 3, 4, 5, 6].map(i => (
                <div key={i} className="border p-4 rounded hover:shadow-lg">
                  <div className="bg-gray-200 h-32 mb-2 rounded"></div>
                  <h3 className="font-medium">Modelo 3D #{i}</h3>
                  <p className="text-sm text-gray-600">Categoria: Fantasia</p>
                  <div className="flex justify-between mt-2">
                    <span className="text-green-600 font-bold">R$ {(Math.random() * 100 + 20).toFixed(2)}</span>
                    <button className="bg-blue-500 text-white px-2 py-1 rounded text-sm">
                      Adicionar
                    </button>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </main>
      
      <footer className="p-4 bg-gray-800 text-white text-center">
        <p>&copy; 2025 Taverna da Impressão 3D - Todos os direitos reservados</p>
      </footer>
    </div>
  );
};

export default StorefrontMockup;
      `
    });
    
    console.log("✅ Mockup da loja gerado com sucesso");
  } catch (error) {
    console.error("❌ Erro ao gerar mockup:", error);
  }
}

/**
 * Gera um diagrama de fluxo de proteção CSRF
 * @returns {Promise<void>}
 */
async function generateCsrfFlowDiagram() {
  console.log("🔨 Gerando diagrama de fluxo CSRF...");
  
  try {
    // Usar artifacts para criar um diagrama Mermaid
    await artifacts({
      command: "create",
      id: "csrf-flow-diagram",
      type: "application/vnd.ant.mermaid",
      title: "Fluxo de Proteção CSRF",
      content: `
sequenceDiagram
    participant U as Usuário
    participant B as Browser
    participant F as Formulário
    participant C as Controller
    participant S as SecurityManager
    
    U->>B: Acessa página com formulário
    B->>C: GET /formulario
    C->>S: generateCsrfToken()
    S->>S: Gera token aleatório
    S->>S: Armazena na sessão
    S-->>C: Retorna token
    C-->>B: Renderiza página com token oculto
    B-->>U: Exibe formulário
    
    U->>B: Submete formulário
    B->>C: POST /formulario (com token)
    C->>S: validateCsrfToken(token)
    
    alt Token válido
        S-->>C: true
        C->>C: Processa solicitação
        C-->>B: Resposta de sucesso
        B-->>U: Exibe confirmação
    else Token inválido
        S-->>C: false
        C-->>B: Rejeita solicitação
        B-->>U: Exibe erro
    end
      `
    });
    
    console.log("✅ Diagrama de fluxo CSRF gerado com sucesso");
  } catch (error) {
    console.error("❌ Erro ao gerar diagrama:", error);
  }
}

/**
 * Gera diagrama de componentes de segurança
 * @returns {Promise<void>}
 */
async function generateSecurityComponentsDiagram() {
  console.log("🔨 Gerando diagrama de componentes de segurança...");
  
  try {
    // Usar artifacts para criar um diagrama Mermaid
    await artifacts({
      command: "create",
      id: "security-components-diagram",
      type: "application/vnd.ant.mermaid",
      title: "Componentes de Segurança",
      content: `
classDiagram
    class SecurityManager {
        +checkAuthentication() bool
        +isUserLoggedIn() bool
        +generateCsrfToken() string
        +validateCsrfToken(token) bool
        +sanitize(value) string
        +logout() void
        +processFileUpload(fileData, destDir, options) array
    }

    class CsrfProtection {
        +generateToken() string
        +validateToken(token) bool
        +getTokenField() string
        +applyToForm(form) void
    }

    class Authentication {
        +login(username, password) bool
        +logout() void
        +isLoggedIn() bool
        +getCurrentUser() User
        +hashPassword(password) string
        +verifyPassword(hash, password) bool
    }

    class InputValidation {
        +validateInput(input, type) bool
        +sanitizeInput(input) string
        +validateUpload(file) bool
        +getErrors() array
    }

    SecurityManager <|-- CsrfProtection
    SecurityManager <|-- Authentication
    SecurityManager <|-- InputValidation
      `
    });
    
    console.log("✅ Diagrama de componentes de segurança gerado com sucesso");
  } catch (error) {
    console.error("❌ Erro ao gerar diagrama:", error);
  }
}

/**
 * Gera todos os diagramas e visualizações
 * @returns {Promise<void>}
 */
async function generateAllVisualizations() {
  await generateArchitectureDiagram();
  await generateStorefrontMockup();
  await generateCsrfFlowDiagram();
  await generateSecurityComponentsDiagram();
  
  console.log("✅ Todas as visualizações foram geradas com sucesso");
}

// Exportar funções para uso no REPL
module.exports = {
  generateArchitectureDiagram,
  generateStorefrontMockup,
  generateCsrfFlowDiagram,
  generateSecurityComponentsDiagram,
  generateAllVisualizations
};