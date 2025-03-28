# Instruções para Continuidade de Desenvolvimento do Projeto TAVERNA DA IMPRESSÃO

## 1. Recuperação do Estado do Projeto

```javascript
// Primeiro comando a ser executado em cada novo chat
const projectStatus = await window.fs.readFile('project-status.json', { encoding: 'utf8' });
console.log(JSON.parse(projectStatus));
```

## 2. Análise do Estado Atual

```javascript
await sequentialthinking({
  thought: "Analisando o estado atual do projeto TAVERNA DA IMPRESSÃO e verificando problemas pendentes após implantação na Hostinger",
  thoughtNumber: 1,
  totalThoughts: 5,
  nextThoughtNeeded: true
});
```

## 3. Resolução dos Problemas de Roteamento e Views

### 3.1 Verificação do Estado de Roteamento
```javascript
// Verificar estado atual do roteamento
const router = await read_file({path: 'app/helpers/Router.php'});
const htaccessPublic = await read_file({path: 'public/.htaccess'});
const htaccessRoot = await read_file({path: '.htaccess'});
console.log("Verificação do sistema de roteamento completa");
```

### 3.2 Implementação de Views e Controllers Pendentes
```javascript
// Verificar views pendentes
await create_or_update_file({
  owner: "Lucasdoreac",
  repo: "taverna-impressao-ecommerce",
  branch: "main",
  path: "app/views/products.php",
  content: `<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <h1 class="h2 mb-4">Produtos</h1>
    
    <!-- Conteúdo da página de produtos -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        <?php foreach ($products['items'] as $product): ?>
        <div class="col">
            <div class="card h-100 product-card">
                <div class="position-relative">
                    <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                    <span class="position-absolute badge bg-danger top-0 start-0 m-2">OFERTA</span>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['image'])): ?>
                    <img src="<?= BASE_URL ?>uploads/products/<?= $product['image'] ?>" class="card-img-top" alt="<?= $product['name'] ?>">
                    <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                        <span class="text-muted">Sem imagem</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-body">
                    <h3 class="card-title h6"><?= $product['name'] ?></h3>
                    <p class="card-text small"><?= mb_strimwidth($product['short_description'] ?? '', 0, 60, '...') ?></p>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                            <span class="text-decoration-line-through text-muted small">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                            <span class="ms-1 text-danger fw-bold">R$ <?= number_format($product['sale_price'], 2, ',', '.') ?></span>
                            <?php else: ?>
                            <span class="fw-bold">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <a href="<?= BASE_URL ?>produto/<?= $product['slug'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Paginação -->
    <?php if ($products['lastPage'] > 1): ?>
    <nav aria-label="Paginação" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($products['currentPage'] > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= BASE_URL ?>produtos?page=<?= $products['currentPage'] - 1 ?>">Anterior</a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">Anterior</span>
            </li>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $products['lastPage']; $i++): ?>
            <li class="page-item <?= $i == $products['currentPage'] ? 'active' : '' ?>">
                <a class="page-link" href="<?= BASE_URL ?>produtos?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            
            <?php if ($products['currentPage'] < $products['lastPage']): ?>
            <li class="page-item">
                <a class="page-link" href="<?= BASE_URL ?>produtos?page=<?= $products['currentPage'] + 1 ?>">Próxima</a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">Próxima</span>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>`,
  message: "Adicionar view pendente: products.php"
});

// Implementar CategoryController
await create_or_update_file({
  owner: "Lucasdoreac",
  repo: "taverna-impressao-ecommerce",
  branch: "main",
  path: "app/controllers/CategoryController.php",
  content: `<?php
class CategoryController {
    public function show($params) {
        $slug = $params['slug'] ?? '';
        
        if (empty($slug)) {
            header('Location: ' . BASE_URL);
            exit;
        }
        
        $categoryModel = new CategoryModel();
        $category = $categoryModel->getBySlug($slug);
        
        if (!$category) {
            header("HTTP/1.0 404 Not Found");
            include VIEWS_PATH . '/errors/404.php';
            exit;
        }
        
        $productModel = new ProductModel();
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $products = $productModel->getByCategory($category['id'], $page);
        
        require_once VIEWS_PATH . '/category.php';
    }
}`,
  message: "Implementar CategoryController pendente"
});
```

### 3.3 Implementação de View para Categorias
```javascript
await create_or_update_file({
  owner: "Lucasdoreac",
  repo: "taverna-impressao-ecommerce",
  branch: "main",
  path: "app/views/category.php",
  content: `<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
            <li class="breadcrumb-item active"><?= $category['name'] ?></li>
        </ol>
    </nav>
    
    <h1 class="h2 mb-4"><?= $category['name'] ?></h1>
    
    <?php if (!empty($category['description'])): ?>
    <p class="mb-4"><?= $category['description'] ?></p>
    <?php endif; ?>
    
    <!-- Subcategorias -->
    <?php if (!empty($category['subcategories'])): ?>
    <h2 class="h4 mb-3">Subcategorias</h2>
    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <?php foreach ($category['subcategories'] as $subcategory): ?>
        <div class="col">
            <a href="<?= BASE_URL ?>categoria/<?= $subcategory['slug'] ?>" class="text-decoration-none">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="card-title h5"><?= $subcategory['name'] ?></h3>
                        <?php if (!empty($subcategory['description'])): ?>
                        <p class="card-text"><?= mb_strimwidth($subcategory['description'], 0, 100, '...') ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Produtos -->
    <h2 class="h4 mb-3">Produtos nesta categoria</h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        <?php if (count($products['items']) > 0): ?>
            <?php foreach ($products['items'] as $product): ?>
            <div class="col">
                <div class="card h-100 product-card">
                    <div class="position-relative">
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                        <span class="position-absolute badge bg-danger top-0 start-0 m-2">OFERTA</span>
                        <?php endif; ?>
                        
                        <?php if (!empty($product['image'])): ?>
                        <img src="<?= BASE_URL ?>uploads/products/<?= $product['image'] ?>" class="card-img-top" alt="<?= $product['name'] ?>">
                        <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <span class="text-muted">Sem imagem</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body">
                        <h3 class="card-title h6"><?= $product['name'] ?></h3>
                        <p class="card-text small"><?= mb_strimwidth($product['short_description'] ?? '', 0, 60, '...') ?></p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                <span class="text-decoration-line-through text-muted small">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                                <span class="ms-1 text-danger fw-bold">R$ <?= number_format($product['sale_price'], 2, ',', '.') ?></span>
                                <?php else: ?>
                                <span class="fw-bold">R$ <?= number_format($product['price'], 2, ',', '.') ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a href="<?= BASE_URL ?>produto/<?= $product['slug'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info">
                    Nenhum produto encontrado nesta categoria.
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Paginação -->
    <?php if ($products['lastPage'] > 1): ?>
    <nav aria-label="Paginação" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($products['currentPage'] > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= BASE_URL ?>categoria/<?= $category['slug'] ?>?page=<?= $products['currentPage'] - 1 ?>">Anterior</a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">Anterior</span>
            </li>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $products['lastPage']; $i++): ?>
            <li class="page-item <?= $i == $products['currentPage'] ? 'active' : '' ?>">
                <a class="page-link" href="<?= BASE_URL ?>categoria/<?= $category['slug'] ?>?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            
            <?php if ($products['currentPage'] < $products['lastPage']): ?>
            <li class="page-item">
                <a class="page-link" href="<?= BASE_URL ?>categoria/<?= $category['slug'] ?>?page=<?= $products['currentPage'] + 1 ?>">Próxima</a>
            </li>
            <?php else: ?>
            <li class="page-item disabled">
                <span class="page-link">Próxima</span>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>`,
  message: "Implementar view pendente: category.php"
});
```

### 3.4 Configuração Final
```javascript
// Após testes bem-sucedidos, reverter configurações de debug
await create_or_update_file({
  owner: "Lucasdoreac",
  repo: "taverna-impressao-ecommerce",
  branch: "main",
  path: "app/config/config.php",
  // Atualizar para production quando pronto
  content: `<?php
// Configurações globais da aplicação
define('BASE_URL', 'https://darkblue-cattle-647559.hostingersite.com/');
define('ROOT_PATH', dirname(dirname(__DIR__)));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEWS_PATH', APP_PATH . '/views');
define('UPLOADS_PATH', ROOT_PATH . '/public/uploads');
define('LOG_PATH', ROOT_PATH . '/logs');

// Configurações do banco de dados para Hostinger
define('DB_HOST', '127.0.0.1:3306');
define('DB_NAME', 'u135851624_taverna');
define('DB_USER', 'u135851624_teverna');
define('DB_PASS', '#Taverna1');

// Adicionando opções de PDO específicas para Hostinger
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 10
]);

// Configurações de e-mail
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_USER', 'contato@tavernaimpressao.com.br');
define('SMTP_PASS', 'sua-senha-aqui'); // Alterar para senha real em produção
define('SMTP_PORT', 587);

// Configurações da loja
define('STORE_NAME', 'TAVERNA DA IMPRESSÃO');
define('STORE_EMAIL', 'contato@tavernaimpressao.com.br');
define('STORE_PHONE', '(21) 98765-4321');

// Configurações de segurança
define('CSRF_TOKEN_NAME', 'taverna_token');
define('SESSION_NAME', 'TAVERNA_SESSION');
define('COOKIE_DOMAIN', '.hostingersite.com');
define('COOKIE_SECURE', true);
define('COOKIE_HTTP_ONLY', true);

// Configurações de ambiente
define('ENVIRONMENT', 'production'); // 'development', 'testing', 'production'
define('DISPLAY_ERRORS', false);
define('LOG_ERRORS', true);

// Configuração do diretório de logs
if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

// Inicializar sessão com configuração explícita para compatibilidade
ini_set('session.cookie_domain', COOKIE_DOMAIN);
ini_set('session.cookie_secure', COOKIE_SECURE);
ini_set('session.cookie_httponly', COOKIE_HTTP_ONLY);
session_name(SESSION_NAME);
session_start();

// Configurar exibição de erros conforme ambiente
if (DISPLAY_ERRORS) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Função de log
function app_log($message, $level = 'info') {
    if (!LOG_ERRORS && $level != 'error') return;
    
    $log_file = LOG_PATH . '/app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$level] $message" . PHP_EOL;
    
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}`,
  message: "Configurar ambiente de produção após finalização dos testes"
});
```

## 4. Fluxo para Desenvolvimento de Novas Funcionalidades

Para cada nova funcionalidade ou modificação:

1. **Planejamento**
```javascript
// Definir objetivos da sessão atual
const objetivoSessao = "Implementar [componente específico]";
console.log(`Foco atual: ${objetivoSessao}`);
```

2. **Implementação**
```javascript
// Implementar ou atualizar arquivo
await create_or_update_file({
  owner: "Lucasdoreac",
  repo: "taverna-impressao-ecommerce",
  branch: "main",
  path: "caminho/do/arquivo.php",
  content: `<?php
  // Código do componente
  ?>`,
  message: "Implementar [nome do componente]"
});
```

3. **Teste**
```javascript
// Testar código no REPL antes de finalizar
await repl({
  code: `
    // Testar a função ou componente
    console.log("Testando funcionalidade...");
  `
});
```

4. **Atualização de Status**
```javascript
// Atualizar status após progresso significativo
const status = JSON.parse(await read_file({path: 'project-status.json'}));
status.development.currentFile = "novo/arquivo.php";
status.development.currentComponent = "Componente Atual";
status.context.lastThought = "Implementação do componente X concluída";

await create_or_update_file({
  owner: "Lucasdoreac",
  repo: "taverna-impressao-ecommerce",
  branch: "main",
  path: "project-status.json",
  content: JSON.stringify(status, null, 2),
  message: "Atualizar status do projeto"
});
```

## 5. Ferramentas MCP Disponíveis

- read_file: Leitura de arquivos
- write_file: Escrita de arquivos
- create_or_update_file: Modificações no GitHub
- search_repositories: Busca de referências
- repl: Testes de código
- artifacts: Componentes visuais
- list_commits: Histórico de mudanças
- search_code: Busca de exemplos

## 6. Monitoramento e Depuração em Produção

### 6.1 Logs e Diagnóstico
```javascript
// Criar uma rotina para acompanhar os logs
async function checkServerLogs() {
  // Verificar logs de erro recentes
  await fetch({
    url: "https://darkblue-cattle-647559.hostingersite.com/phpinfo.php?noauth"
  });
  
  // Analisar os resultados e fazer ajustes conforme necessário
}
```

### 6.2 Segurança após Finalização
```javascript
// Remover arquivos de diagnóstico após implementação
await create_or_update_file({
  owner: "Lucasdoreac",
  repo: "taverna-impressao-ecommerce",
  branch: "main",
  path: "docs/POST_DEPLOYMENT.md",
  content: `# Tarefas Pós-Implantação

1. Remover arquivo phpinfo.php
2. Configurar ENVIRONMENT para 'production'
3. Desativar DISPLAY_ERRORS
4. Verificar permissões de diretórios
5. Configurar backups automáticos
6. Implementar monitoramento`,
  message: "Adicionar guia de tarefas pós-implantação"
});
```

## 7. Resolução de Problemas Específicos

Para problemas frequentes:

1. **Rotas não funcionando**:
   - Verificar .htaccess (raiz e public)
   - Confirmar que mod_rewrite está ativo
   - Examinar logs para identificar erro exato

2. **Erros de View**:
   - Validar caminho das views
   - Verificar variáveis passadas do controller
   - Testar templates individualmente

3. **Problemas de Banco de Dados**:
   - Usar db_test.php para verificar conexão 
   - Validar estrutura das tabelas
   - Verificar queries com valores explícitos

## 8. Planejamento de Funcionalidades Pendentes

Priorização:

1. **Catálogo de Produtos**:
   - Completar listagem e filtros
   - Implementar busca avançada
   - Adicionar paginação

2. **Sistema de Personalização**:
   - Finalizar upload e preview
   - Implementar opções de customização
   - Associar ao carrinho de compras

3. **Checkout e Pagamento**:
   - Integrar com APIs de pagamento
   - Implementar cálculo de frete
   - Gerar emails de confirmação

## 9. Boas Práticas de Desenvolvimento

- **Organização do Código**:
  - Seguir padrão MVC
  - Documentar com PHPDoc
  - Manter componentes reutilizáveis
  - Validar entradas de usuário

- **Controle de Versão**:
  - Commits frequentes e atômicos
  - Mensagens descritivas
  - Atualizar project-status.json
  - Documentar decisões importantes

- **Continuidade**:
  - Salvar estado antes de interrupções
  - Documentar pontos de parada
  - Manter contexto recuperável

## 10. Em Caso de Interrupção

```javascript
// Execute antes de qualquer pausa prevista
await create_or_update_file({
  owner: "Lucasdoreac",
  repo: "taverna-impressao-ecommerce",
  branch: "main",
  path: "project-status.json",
  content: JSON.stringify({
    ...estadoAtual,
    context: {
      ...estadoAtual.context,
      lastThought: "Parado em: [descrição exata do ponto]",
      nextSteps: ["Próximo passo 1", "Próximo passo 2"]
    }
  }, null, 2),
  message: "Save: ponto de continuidade"
});
```

---

Para iniciar uma nova sessão:

1. Cole estas instruções
2. Execute recuperação de estado: `const projectStatus = await window.fs.readFile('project-status.json', { encoding: 'utf8' });`
3. Use sequentialthinking para análise do estado atual
4. Continue a partir do ponto registrado em context.lastThought