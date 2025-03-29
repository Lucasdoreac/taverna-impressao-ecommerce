<?php
// Arquivo temporário para diagnóstico de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Carregar configurações do sistema
require_once __DIR__ . '/../app/config/config.php';

// Testar conexão com banco de dados
echo "<h2>Verificando conexão com banco de dados</h2>";
try {
    require_once __DIR__ . '/../app/helpers/Database.php';
    $db = Database::getInstance();
    echo "<p style='color:green'>✓ Conexão com banco de dados estabelecida.</p>";
    
    // Testar tabelas críticas
    $tables = ['users', 'products', 'categories', 'orders', 'order_items', 'cart_items', 'carts', 'addresses'];
    echo "<h3>Verificando tabelas:</h3>";
    foreach ($tables as $table) {
        try {
            $query = "SHOW COLUMNS FROM {$table}";
            $result = $db->select($query);
            echo "<p style='color:green'>✓ Tabela {$table} existe e possui " . count($result) . " colunas.</p>";
        } catch (Exception $e) {
            echo "<p style='color:red'>✗ Erro na tabela {$table}: " . $e->getMessage() . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro de conexão: " . $e->getMessage() . "</p>";
}

// Verificar rotas e controllers
echo "<h2>Verificando controllers</h2>";
$controllers = ['ProductController', 'CategoryController', 'CartController', 'CheckoutController', 'OrderController'];
foreach ($controllers as $controller) {
    try {
        $controllerPath = __DIR__ . "/../app/controllers/{$controller}.php";
        if (file_exists($controllerPath)) {
            echo "<p style='color:green'>✓ Controller {$controller} existe.</p>";
            require_once $controllerPath;
            echo "<p style='color:green'>✓ Controller {$controller} carregado com sucesso.</p>";
        } else {
            echo "<p style='color:red'>✗ Controller {$controller} não encontrado em {$controllerPath}.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Erro ao carregar {$controller}: " . $e->getMessage() . "</p>";
    }
}

// Verificar views críticas
echo "<h2>Verificando views críticas</h2>";
$views = ['product.php', 'category.php', 'checkout.php', 'orders.php', 'order_details.php', 'order_success.php'];
foreach ($views as $view) {
    if (file_exists(__DIR__ . "/../app/views/{$view}")) {
        echo "<p style='color:green'>✓ View {$view} existe.</p>";
    } else {
        echo "<p style='color:red'>✗ View {$view} não encontrada.</p>";
    }
}

// Verificar variáveis de ambiente e sessão
echo "<h2>Variáveis de ambiente</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";

echo "<h2>Variáveis de sessão</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Verificar constantes definidas
echo "<h2>Constantes definidas</h2>";
$constants = [
    'ENVIRONMENT', 'BASE_URL', 'ROOT_PATH', 'APP_PATH', 'VIEWS_PATH', 'UPLOADS_PATH',
    'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
    'STORE_NAME', 'STORE_EMAIL', 'STORE_PHONE',
    'CURRENCY', 'CURRENCY_SYMBOL',
    'ITEMS_PER_PAGE', 'MAX_UPLOAD_SIZE'
];

foreach ($constants as $constant) {
    if (defined($constant)) {
        echo "<p><strong>{$constant}:</strong> " . (is_string(constant($constant)) ? constant($constant) : var_export(constant($constant), true)) . "</p>";
    } else {
        echo "<p style='color:red'>✗ Constante {$constant} não está definida!</p>";
    }
}
