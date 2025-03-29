<?php
// Script de verificação completa do sistema
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Carregar configurações
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Database.php';

echo "<h1>Verificação de Sistema - TAVERNA DA IMPRESSÃO</h1>";
echo "<p>Data/Hora: " . date('Y-m-d H:i:s') . "</p>";

// Verificar constantes essenciais
echo "<h2>1. Constantes</h2>";
$requiredConstants = ['BASE_URL', 'CURRENCY', 'CURRENCY_SYMBOL', 'STORE_NAME', 'STORE_EMAIL', 'DB_HOST', 'DB_NAME', 'DB_USER'];
foreach ($requiredConstants as $constant) {
    if (defined($constant)) {
        $value = constant($constant);
        // Ocultar senhas
        if ($constant === 'DB_PASS') {
            $value = str_repeat('*', strlen($value));
        }
        echo "<p style='color:green'>✓ {$constant} = " . htmlspecialchars($value) . "</p>";
    } else {
        echo "<p style='color:red'>✗ {$constant} não está definida</p>";
    }
}

// Verificar conexão com banco de dados
echo "<h2>2. Conexão com Banco de Dados</h2>";
try {
    $db = Database::getInstance();
    echo "<p style='color:green'>✓ Conexão com banco de dados OK</p>";
    
    // Verificar tabelas
    $requiredTables = ['users', 'products', 'categories', 'orders', 'carts', 'cart_items'];
    echo "<h3>Tabelas:</h3>";
    foreach ($requiredTables as $table) {
        try {
            $result = $db->select("SHOW COLUMNS FROM {$table}");
            echo "<p style='color:green'>✓ Tabela {$table} OK - " . count($result) . " colunas</p>";
            
            // Mostrar primeiras 5 linhas para tabelas importantes
            if (in_array($table, ['products', 'categories'])) {
                $rows = $db->select("SELECT * FROM {$table} LIMIT 5");
                if (count($rows) > 0) {
                    echo "<ul>";
                    foreach ($rows as $row) {
                        echo "<li>" . htmlspecialchars(isset($row['name']) ? $row['name'] : 'ID: ' . $row['id']) . "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p style='color:orange'>⚠️ Tabela {$table} está vazia</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>✗ Tabela {$table} erro: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro de conexão: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Verificar Router
echo "<h2>3. Teste de Router</h2>";
require_once __DIR__ . '/../app/helpers/Router.php';

try {
    $router = new Router();
    echo "<p style='color:green'>✓ Router carregado com sucesso</p>";
    
    // Testar rotas específicas
    echo "<h3>Teste de rotas específicas:</h3>";
    $testRoutes = [
        '/produto/ficha-personagem-dd-5e-basica' => '/produto/:slug',
        '/categoria/fichas-de-personagem' => '/categoria/:slug',
        '/carrinho/remover/123' => '/carrinho/remover/:id',
        '/minha-conta/pedido/ABC123' => '/minha-conta/pedido/:id'
    ];
    
    require_once __DIR__ . '/../app/config/routes.php';
    
    foreach ($testRoutes as $testUri => $expectedRoute) {
        $params = [];
        $routeFound = false;
        
        foreach ($routes as $route => $handler) {
            if ($router->matchRoute($route, $testUri, $params)) {
                echo "<p style='color:green'>✓ URI: {$testUri} corresponde à rota {$route}</p>";
                echo "<p>Parâmetros extraídos:</p>";
                echo "<pre>" . print_r($params, true) . "</pre>";
                $routeFound = true;
                break;
            }
        }
        
        if (!$routeFound) {
            echo "<p style='color:red'>✗ Nenhuma rota corresponde a {$testUri}</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro ao inicializar Router: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Verificar Controllers
echo "<h2>4. Verificação de Controllers</h2>";
$controllers = [
    'HomeController',
    'ProductController',
    'CategoryController',
    'CartController',
    'CheckoutController',
    'OrderController',
    'AuthController',
    'AccountController',
    'CustomizationController'
];

foreach ($controllers as $controller) {
    $controllerPath = APP_PATH . '/controllers/' . $controller . '.php';
    if (file_exists($controllerPath)) {
        echo "<p style='color:green'>✓ {$controller} encontrado</p>";
        
        // Tentar carregar o controller para verificar erros de sintaxe
        try {
            require_once $controllerPath;
            if (class_exists($controller)) {
                echo "<p style='margin-left:20px; color:green'>✓ Classe {$controller} carregada com sucesso</p>";
                
                // Listar métodos
                $methods = get_class_methods($controller);
                echo "<p style='margin-left:20px;'>Métodos disponíveis: " . implode(', ', $methods) . "</p>";
            } else {
                echo "<p style='margin-left:20px; color:red'>✗ Classe {$controller} não encontrada no arquivo</p>";
            }
        } catch (Error $e) {
            echo "<p style='margin-left:20px; color:red'>✗ Erro ao carregar {$controller}: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color:orange'>⚠️ {$controller} não encontrado em {$controllerPath}</p>";
    }
}

// Verificar Views
echo "<h2>5. Verificação de Views</h2>";
$views = [
    'home.php',
    'products.php',
    'product.php',
    'category.php',
    'cart.php',
    'checkout.php',
    'login.php',
    'register.php',
    'account.php',
    'orders.php',
    'order_details.php',
    'customization.php',
    'errors/404.php',
    'errors/500.php',
];

foreach ($views as $view) {
    $viewPath = VIEWS_PATH . '/' . $view;
    if (file_exists($viewPath)) {
        echo "<p style='color:green'>✓ View {$view} encontrada</p>";
    } else {
        echo "<p style='color:orange'>⚠️ View {$view} não encontrada em {$viewPath}</p>";
    }
}

// Verificar Constantes Populadas
echo "<h2>6. Verificação de Constantes Populadas</h2>";
echo "<p>BASE_URL = " . htmlspecialchars(BASE_URL) . "</p>";
echo "<p>CURRENCY = " . htmlspecialchars(CURRENCY) . "</p>";
echo "<p>CURRENCY_SYMBOL = " . htmlspecialchars(CURRENCY_SYMBOL) . "</p>";
echo "<p>ENVIRONMENT = " . htmlspecialchars(ENVIRONMENT) . "</p>";

// Verificar Helpers
echo "<h2>7. Verificação de Helpers</h2>";
$helpers = [
    'Database.php',
    'Router.php',
    'Logger.php'
];

foreach ($helpers as $helper) {
    $helperPath = APP_PATH . '/helpers/' . $helper;
    if (file_exists($helperPath)) {
        echo "<p style='color:green'>✓ Helper {$helper} encontrado</p>";
    } else {
        echo "<p style='color:red'>✗ Helper {$helper} não encontrado em {$helperPath}</p>";
    }
}

// Verificar Status de Sessão
echo "<h2>8. Status da Sessão</h2>";
echo "<p>session_id: " . session_id() . "</p>";
echo "<p>session_status: " . (session_status() === PHP_SESSION_ACTIVE ? "Ativa" : "Inativa") . "</p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Links para diagnósticos específicos
echo "<h2>9. Links para Diagnósticos Específicos</h2>";
echo "<ul>";
echo "<li><a href='" . BASE_URL . "debug.php' target='_blank'>Debug Geral</a></li>";
echo "<li><a href='" . BASE_URL . "product_debug.php' target='_blank'>Debug de Produto</a></li>";
echo "<li><a href='" . BASE_URL . "auth_debug.php' target='_blank'>Debug de Autenticação</a></li>";
echo "<li><a href='" . BASE_URL . "order_debug.php' target='_blank'>Debug de Pedidos</a></li>";
echo "<li><a href='" . BASE_URL . "route_debug.php' target='_blank'>Debug de Rotas</a></li>";
echo "</ul>";

// Próximos Passos
echo "<h2>10. Próximos Passos</h2>";
echo "<p>Com base na verificação, estes são os próximos passos recomendados:</p>";
echo "<ol>";
echo "<li>Verificar e corrigir quaisquer problemas identificados acima</li>";
echo "<li>Executar o script de criação de tabelas se elas estiverem faltando</li>";
echo "<li>Testar as páginas principais do site manualmente</li>";
echo "<li>Verificar o sistema em ambiente de produção</li>";
echo "</ol>";

echo "<hr>";
echo "<p>Verificação concluída em: " . date('Y-m-d H:i:s') . "</p>";
