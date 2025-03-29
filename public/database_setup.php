<?php
// Script para verificação e setup do banco de dados
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Carregar configurações
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Database.php';

echo "<h1>Setup e Verificação do Banco de Dados - TAVERNA DA IMPRESSÃO</h1>";

// Verificar se o usuário confirmou o setup
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

// Verificar conexão com banco de dados
echo "<h2>1. Verificação de Conexão</h2>";
try {
    $db = Database::getInstance();
    echo "<p style='color:green'>✓ Conexão com banco de dados estabelecida com sucesso!</p>";
    echo "<p>Host: " . DB_HOST . "</p>";
    echo "<p>Banco de Dados: " . DB_NAME . "</p>";
    echo "<p>Usuário: " . DB_USER . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro de conexão: " . htmlspecialchars($e->getMessage()) . "</p>";
    die("Script interrompido devido a erro de conexão.");
}

// Verificar tabelas existentes
echo "<h2>2. Verificação de Tabelas Existentes</h2>";

$requiredTables = [
    'users', 'addresses', 'categories', 'products', 'product_images', 
    'customization_options', 'carts', 'cart_items', 'orders', 'order_items',
    'coupons', 'settings'
];

$existingTables = [];
$missingTables = [];

foreach ($requiredTables as $table) {
    try {
        $result = $db->select("SHOW TABLES LIKE '{$table}'");
        if (count($result) > 0) {
            echo "<p style='color:green'>✓ Tabela {$table} existe</p>";
            $existingTables[] = $table;
        } else {
            echo "<p style='color:orange'>⚠️ Tabela {$table} não existe</p>";
            $missingTables[] = $table;
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Erro ao verificar tabela {$table}: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Se não há tabelas faltando, apenas mostrar mensagem
if (empty($missingTables)) {
    echo "<h2>3. Resultado</h2>";
    echo "<p style='color:green'>✓ Todas as tabelas necessárias já existem no banco de dados!</p>";
} else {
    // Se há tabelas faltando, mostrar opção de criar
    echo "<h2>3. Criação de Tabelas</h2>";
    echo "<p>As seguintes tabelas estão faltando: <strong>" . implode(", ", $missingTables) . "</strong></p>";
    
    if (!$confirmed) {
        echo "<p>Clique abaixo para criar as tabelas faltantes:</p>";
        echo "<p><a href='?confirm=yes' class='btn btn-primary' style='padding: 8px 16px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Criar Tabelas Faltantes</a></p>";
    } else {
        // Script de criação de tabelas
        echo "<h3>Executando script de criação de tabelas...</h3>";
        
        try {
            $sqlScript = file_get_contents(__DIR__ . '/../database/schema.sql');
            
            if (!$sqlScript) {
                throw new Exception("Não foi possível ler o arquivo schema.sql");
            }
            
            // Dividir o script em consultas individuais
            $queries = explode(';', $sqlScript);
            
            foreach ($queries as $query) {
                $query = trim($query);
                
                if (empty($query)) {
                    continue;
                }
                
                // Verificar se a query é para criar uma tabela
                if (preg_match('/CREATE\s+TABLE\s+\`?(\w+)\`?/i', $query, $matches)) {
                    $tableName = $matches[1];
                    
                    // Se a tabela já existe, pular
                    if (in_array($tableName, $existingTables)) {
                        echo "<p>Pulando tabela já existente: {$tableName}</p>";
                        continue;
                    }
                    
                    echo "<p>Criando tabela: {$tableName}...</p>";
                    try {
                        $db->query($query);
                        echo "<p style='color:green'>✓ Tabela {$tableName} criada com sucesso!</p>";
                    } catch (Exception $e) {
                        echo "<p style='color:red'>✗ Erro ao criar tabela {$tableName}: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                } elseif (preg_match('/INSERT\s+INTO/i', $query)) {
                    // É uma instrução INSERT, verificar em qual tabela
                    if (preg_match('/INSERT\s+INTO\s+\`?(\w+)\`?/i', $query, $matches)) {
                        $tableName = $matches[1];
                        echo "<p>Inserindo dados na tabela: {$tableName}...</p>";
                    } else {
                        echo "<p>Executando INSERT...</p>";
                    }
                    
                    try {
                        $db->query($query);
                        echo "<p style='color:green'>✓ Dados inseridos com sucesso!</p>";
                    } catch (Exception $e) {
                        echo "<p style='color:orange'>⚠️ Aviso ao inserir dados: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                } else {
                    // Outra instrução SQL
                    echo "<p>Executando consulta...</p>";
                    try {
                        $db->query($query);
                        echo "<p style='color:green'>✓ Consulta executada com sucesso!</p>";
                    } catch (Exception $e) {
                        echo "<p style='color:red'>✗ Erro ao executar consulta: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                }
            }
            
            echo "<h3>Script de criação concluído!</h3>";
            
            // Verificar tabelas novamente após criação
            echo "<h3>Verificando tabelas após criação:</h3>";
            $stillMissing = [];
            
            foreach ($missingTables as $table) {
                try {
                    $result = $db->select("SHOW TABLES LIKE '{$table}'");
                    if (count($result) > 0) {
                        echo "<p style='color:green'>✓ Tabela {$table} criada e verificada</p>";
                    } else {
                        echo "<p style='color:red'>✗ Tabela {$table} ainda não existe após tentativa de criação</p>";
                        $stillMissing[] = $table;
                    }
                } catch (Exception $e) {
                    echo "<p style='color:red'>✗ Erro ao verificar tabela {$table}: " . htmlspecialchars($e->getMessage()) . "</p>";
                    $stillMissing[] = $table;
                }
            }
            
            if (empty($stillMissing)) {
                echo "<h3 style='color:green'>Todas as tabelas foram criadas com sucesso!</h3>";
            } else {
                echo "<h3 style='color:red'>Ainda faltam tabelas: " . implode(", ", $stillMissing) . "</h3>";
                echo "<p>Por favor, verifique o arquivo schema.sql e execute-o manualmente se necessário.</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>✗ Erro durante a execução do script: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
?>

<!-- Adicionar links para outras ferramentas de diagnóstico -->
<hr>
<h2>Outras Ferramentas de Diagnóstico</h2>
<ul>
    <li><a href="<?= BASE_URL ?>system_check.php">Verificação Completa do Sistema</a></li>
    <li><a href="<?= BASE_URL ?>debug.php">Debug Geral</a></li>
    <li><a href="<?= BASE_URL ?>product_debug.php">Debug de Produto</a></li>
    <li><a href="<?= BASE_URL ?>auth_debug.php">Debug de Autenticação</a></li>
    <li><a href="<?= BASE_URL ?>order_debug.php">Debug de Pedidos</a></li>
    <li><a href="<?= BASE_URL ?>route_debug.php">Debug de Rotas</a></li>
</ul>
