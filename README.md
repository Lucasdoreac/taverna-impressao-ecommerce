# Taverna da Impressão 3D

E-commerce para venda de modelos 3D para impressão, com foco em segurança e usabilidade.

## Status do Projeto

- **Versão**: 0.2.1
- **Site de produção**: [https://darkblue-cattle-647559.hostingersite.com/](https://darkblue-cattle-647559.hostingersite.com/)
- **Foco atual**: Correções de segurança e validação de dados
- **Sprint**: Sprint 2 - Segurança e Validação

## Estrutura do Projeto

```
taverna-impressao-ecommerce/
├── app/
│   ├── controllers/       # Controllers da aplicação
│   ├── models/            # Modelos de dados
│   ├── views/             # Templates de visualização
│   ├── lib/               # Bibliotecas e classes utilitárias
│   │   ├── Security/      # Classes de segurança
│   └── config/            # Configurações da aplicação
├── public/                # Arquivos públicos (CSS, JS, imagens)
├── uploads/               # Diretório de uploads (não versionado)
└── utils/                 # Scripts utilitários para desenvolvimento
```

## Componentes Principais

### Concluídos
- Interface Básica da Loja
- Catálogo de Produtos
- Visualização de Produtos
- Carrinho de Compras
- SecurityManager (Implementação Base)
- CustomerModelController (Correção de Erro Fatal)

### Em Desenvolvimento
- Proteção CSRF em Formulários
- Validação de Entrada em Controllers
- Sistema de Upload de Modelos 3D

### Pendentes
- Área de Usuário Completa
- Dashboard de Administração
- Configuração de Headers HTTP de Segurança

## Padrões de Desenvolvimento

### Segurança

1. **Validação de Entrada**
   - Sempre usar `getValidatedParam()` para validar entradas do usuário
   - Nunca confiar em dados enviados pelo cliente

2. **Proteção CSRF**
   - Todos os formulários devem conter token CSRF
   - Validar token em todas as operações POST

3. **Upload de Arquivos**
   - Usar `SecurityManager::processFileUpload()` para todos os uploads
   - Validar extensões e tamanho de arquivos
   - Verificar tipo MIME real dos arquivos

4. **Autenticação**
   - Usar `SecurityManager::checkAuthentication()` para verificar autenticação
   - Implementar `requireAuthentication()` nos controllers que necessitam autenticação

### Codificação

1. **Controllers**
   - Estender a classe base `Controller`
   - Usar métodos de validação para todos os inputs
   - Nunca usar `exit()` ou `die()` nos controllers

2. **Models**
   - Usar prepared statements para todas as operações SQL
   - Validar dados antes de inserir no banco

3. **Views**
   - Sanitizar todas as saídas com `htmlspecialchars()`
   - Incluir token CSRF em todos os formulários

## Ferramentas de Desenvolvimento

1. **Inicialização do Projeto**
   ```javascript
   // Inicializar projeto e carregar estado
   await loadProjectState();
   ```

2. **Verificação de Segurança**
   ```javascript
   // Verificar segurança dos controllers
   await checkSecurityIssues();
   ```

3. **Gerenciamento de Estado**
   ```javascript
   // Atualizar status do projeto
   await updateProjectStatus({
     components: {
       completed: [...currentStatus.components.completed, "NovoComponente"]
     }
   });
   ```

## Colaboração

Para colaborar com o projeto:

1. Clone o repositório
2. Carregue o estado atual do projeto com `loadProjectState()`
3. Implemente suas alterações seguindo os padrões de segurança
4. Atualize o estado do projeto com `updateProjectStatus()`
5. Valide suas alterações com `checkSecurityIssues()`

## Contato

Para questões sobre o projeto, entre em contato através de issues no repositório ou diretamente com a equipe de desenvolvimento.