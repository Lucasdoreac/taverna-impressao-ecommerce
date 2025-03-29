<?php
// Script de diagnóstico para verificar a conexão com o banco de dados e estrutura das tabelas
// IMPORTANTE: Remova este arquivo após o diagnóstico por motivos de segurança

// Desabilitar exibição para cliente final, mas manter os logs
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Função para log detalhado
function log_diagnostic($message, $type = 'info') {
    $log_file = __DIR__ . '/../logs/db_diagnostic.log';
    $dir = dirname($log_file);
    
    // Criar diretório de logs se não existir
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
    
    // Escrever no arquivo de log
    file_put_contents($log_file, $log_message, FILE_APPEND);
    
    // Exibir na tela se for uma requisição autorizada
    if (isset($_GET['show_log']) && $_GET['show_log'] === 'true') {
        echo $log_message . "<br>";
    }
}

// Autenticação básica para o diagnóstico
$auth_code = isset($_GET['auth']) ? $_GET['auth'] : '';
$expected_code = 'taverna2025diagnostic'; // Código de autenticação para acessar o diagnóstico

if ($auth_code !== $expected_code) {
    http_response_code(403);
    echo "Acesso não autorizado";
    exit;
}

// Carregar configurações
try {
    require_once __DIR__ . '/../app/config/config.php';
    log_diagnostic("Configurações carregadas com sucesso");
} catch (Exception $e) {
    log_diagnostic("Erro ao carregar configurações: " . $e->getMessage(), 'error');
    if (isset($_GET['show_log']) && $_GET['show_log'] === 'true') {
        echo "Erro ao carregar configurações: " . $e->getMessage();
    }
    exit;
}

// Verificar conexão com o banco de dados
try {
    require_once __DIR__ . '/../app/helpers/Database.php';
    $db = Database::getInstance();
    log_diagnostic("Conexão com o banco de dados estabelecida com sucesso");
} catch (Exception $e) {
    log_diagnostic("Erro na conexão com o banco de dados: " . $e->getMessage(), 'error');
    if (isset($_GET['show_log']) && $_GET['show_log'] === 'true') {
        echo "Erro na conexão com o banco de dados: " . $e->getMessage();
    }
    exit;
}

// Testar tabelas principais
$tables = [
    'users' => 'Usuários',
    'categories' => 'Categorias',
    'products' => 'Produtos',
    'orders' => 'Pedidos',
    'order_items' => 'Itens de Pedido',
    'carts' => 'Carrinhos',
    'cart_items' => 'Itens de Carrinho',
    'addresses' => 'Endereços'
];

$table_status = [];

foreach ($tables as $table => $description) {
    try {
        $result = $db->select("SELECT COUNT(*) as count FROM {$table}");
        $count = $result[0]['count'];
        $table_status[$table] = [
            'status' => 'ok',
            'count' => $count,
            'message' => "Tabela {$table} ({$description}) encontrada com {$count} registros"
        ];
        log_diagnostic("Tabela {$table} ({$description}) encontrada com {$count} registros");
    } catch (Exception $e) {
        $table_status[$table] = [
            'status' => 'error',
            'message' => "Erro ao acessar tabela {$table} ({$description}): " . $e->getMessage()
        ];
        log_diagnostic("Erro ao acessar tabela {$table} ({$description}): " . $e->getMessage(), 'error');
    }
}

// Verificar tabelas específicas com mais detalhes
$detailed_checks = [
    // Verificar estrutura da tabela products
    'products_structure' => function($db) {
        try {
            $result = $db->select("DESCRIBE products");
            $columns = array_column($result, 'Field');
            $required_columns = ['id', 'category_id', 'name', 'slug', 'description', 'price', 'stock', 'is_active'];
            $missing_columns = array_diff($required_columns, $columns);
            
            if (empty($missing_columns)) {
                log_diagnostic("Estrutura da tabela products está completa");
                return [
                    'status' => 'ok',
                    'message' => "Estrutura da tabela products está completa"
                ];
            } else {
                $missing = implode(', ', $missing_columns);
                log_diagnostic("Estrutura da tabela products está incompleta. Colunas faltando: {$missing}", 'warning');
                return [
                    'status' => 'warning',
                    'message' => "Estrutura da tabela products está incompleta. Colunas faltando: {$missing}"
                ];
            }
        } catch (Exception $e) {
            log_diagnostic("Erro ao verificar estrutura da tabela products: " . $e->getMessage(), 'error');
            return [
                'status' => 'error',
                'message' => "Erro ao verificar estrutura da tabela products: " . $e->getMessage()
            ];
        }
    },
    
    // Verificar relação entre products e categories
    'products_categories' => function($db) {
        try {
            $result = $db->select("SELECT p.id, p.name, p.category_id, c.name as category_name 
                                 FROM products p 
                                 LEFT JOIN categories c ON p.category_id = c.id 
                                 WHERE p.category_id IS NOT NULL 
                                 LIMIT 5");
            
            if (empty($result)) {
                log_diagnostic("Nenhum produto com categoria encontrado", 'warning');
                return [
                    'status' => 'warning',
                    'message' => "Nenhum produto com categoria encontrado"
                ];
            } else {
                $count = count($result);
                log_diagnostic("{$count} produtos com categorias encontrados");
                return [
                    'status' => 'ok',
                    'message' => "{$count} produtos com categorias encontrados"
                ];
            }
        } catch (Exception $e) {
            log_diagnostic("Erro ao verificar relação produtos-categorias: " . $e->getMessage(), 'error');
            return [
                'status' => 'error',
                'message' => "Erro ao verificar relação produtos-categorias: " . $e->getMessage()
            ];
        }
    },
    
    // Verificar usuários
    'users_check' => function($db) {
        try {
            $result = $db->select("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
            $admin_count = $result[0]['count'];
            
            if ($admin_count > 0) {
                log_diagnostic("{$admin_count} usuários administradores encontrados");
                return [
                    'status' => 'ok',
                    'message' => "{$admin_count} usuários administradores encontrados"
                ];
            } else {
                log_diagnostic("Nenhum usuário administrador encontrado", 'warning');
                return [
                    'status' => 'warning',
                    'message' => "Nenhum usuário administrador encontrado"
                ];
            }
        } catch (Exception $e) {
            log_diagnostic("Erro ao verificar usuários: " . $e->getMessage(), 'error');
            return [
                'status' => 'error',
                'message' => "Erro ao verificar usuários: " . $e->getMessage()
            ];
        }
    }
];

$detailed_status = [];
foreach ($detailed_checks as $check_name => $check_function) {
    $detailed_status[$check_name] = $check_function($db);
}

// Verificar consultas específicas que estão causando erros
$problematic_queries = [
    // Consulta do ProductController::show()
    'product_by_slug' => function($db) {
        try {
            $slug = 'ficha-personagem-dd-5e-basica'; // Slug de exemplo
            
            $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                    FROM products p
                    JOIN categories c ON p.category_id = c.id
                    WHERE p.slug = :slug AND p.is_active = 1";
            
            $result = $db->select($sql, ['slug' => $slug]);
            
            if (empty($result)) {
                log_diagnostic("Produto com slug '{$slug}' não encontrado", 'warning');
                return [
                    'status' => 'warning',
                    'message' => "Produto com slug '{$slug}' não encontrado"
                ];
            } else {
                log_diagnostic("Consulta product_by_slug funcionando corretamente");
                return [
                    'status' => 'ok',
                    'message' => "Consulta product_by_slug funcionando corretamente"
                ];
            }
        } catch (Exception $e) {
            log_diagnostic("Erro na consulta product_by_slug: " . $e->getMessage(), 'error');
            return [
                'status' => 'error',
                'message' => "Erro na consulta product_by_slug: " . $e->getMessage()
            ];
        }
    },
    
    // Consulta do OrderController
    'orders_by_user' => function($db) {
        try {
            // Usar um ID de usuário de exemplo
            $user_id = 1;
            
            $sql = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC";
            $result = $db->select($sql, ['user_id' => $user_id]);
            
            log_diagnostic("Consulta orders_by_user funcionando corretamente");
            return [
                'status' => 'ok',
                'message' => "Consulta orders_by_user funcionando corretamente"
            ];
        } catch (Exception $e) {
            log_diagnostic("Erro na consulta orders_by_user: " . $e->getMessage(), 'error');
            return [
                'status' => 'error',
                'message' => "Erro na consulta orders_by_user: " . $e->getMessage()
            ];
        }
    }
];

$query_status = [];
foreach ($problematic_queries as $query_name => $query_function) {
    $query_status[$query_name] = $query_function($db);
}

// Gerar resumo final
$summary = [
    'database_connection' => true,
    'tables_ok' => count(array_filter($table_status, function($item) { return $item['status'] === 'ok'; })),
    'tables_error' => count(array_filter($table_status, function($item) { return $item['status'] === 'error'; })),
    'details' => [
        'tables' => $table_status,
        'detailed_checks' => $detailed_status,
        'problematic_queries' => $query_status
    ]
];

// Salvar resultado completo em formato JSON
$result_file = __DIR__ . '/../logs/db_diagnostic_result.json';
file_put_contents($result_file, json_encode($summary, JSON_PRETTY_PRINT));

log_diagnostic("Diagnóstico concluído. Resultado completo salvo em {$result_file}");

// Exibir resultado em formato HTML se solicitado
if (isset($_GET['show_log']) && $_GET['show_log'] === 'true') {
    echo "<h1>Diagnóstico do Banco de Dados</h1>";
    echo "<p><strong>Conexão com o banco de dados:</strong> " . ($summary['database_connection'] ? 'OK' : 'FALHA') . "</p>";
    echo "<p><strong>Tabelas OK:</strong> {$summary['tables_ok']}</p>";
    echo "<p><strong>Tabelas com erro:</strong> {$summary['tables_error']}</p>";
    
    echo "<h2>Status das Tabelas</h2>";
    echo "<ul>";
    foreach ($table_status as $table => $status) {
        $status_class = $status['status'] === 'ok' ? 'color:green' : 'color:red';
        echo "<li style='{$status_class}'>{$table}: {$status['message']}</li>";
    }
    echo "</ul>";
    
    echo "<h2>Verificações Detalhadas</h2>";
    echo "<ul>";
    foreach ($detailed_status as $check => $status) {
        $status_class = $status['status'] === 'ok' ? 'color:green' : ($status['status'] === 'warning' ? 'color:orange' : 'color:red');
        echo "<li style='{$status_class}'>{$check}: {$status['message']}</li>";
    }
    echo "</ul>";
    
    echo "<h2>Consultas Problemáticas</h2>";
    echo "<ul>";
    foreach ($query_status as $query => $status) {
        $status_class = $status['status'] === 'ok' ? 'color:green' : ($status['status'] === 'warning' ? 'color:orange' : 'color:red');
        echo "<li style='{$status_class}'>{$query}: {$status['message']}</li>";
    }
    echo "</ul>";
}
