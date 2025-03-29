<?php
// Debug para verificação do banco de dados
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inicializar sessão
session_start();

// Carregar configurações
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Database.php';

echo "<h1>Diagnóstico de Banco de Dados</h1>";

// Verificar conexão
try {
    $db = Database::getInstance();
    echo "<p style='color:green'>✓ Conexão com banco de dados estabelecida.</p>";
    
    echo "<h2>Detalhes da Conexão</h2>";
    echo "<ul>";
    echo "<li>Host: " . DB_HOST . "</li>";
    echo "<li>Banco de dados: " . DB_NAME . "</li>";
    echo "<li>Usuário: " . DB_USER . "</li>";
    echo "</ul>";
    
    // Verificar tabelas críticas
    echo "<h2>Verificação de Tabelas</h2>";
    $tables = [
        'users' => ['id', 'name', 'email', 'password', 'role', 'created_at'],
        'categories' => ['id', 'parent_id', 'name', 'slug', 'description', 'image', 'is_active'],
        'products' => ['id', 'category_id', 'name', 'slug', 'description', 'price', 'stock', 'is_active'],
        'product_images' => ['id', 'product_id', 'image', 'is_main'],
        'carts' => ['id', 'user_id', 'session_id', 'created_at'],
        'cart_items' => ['id', 'cart_id', 'product_id', 'quantity', 'customization_data'],
        'orders' => ['id', 'user_id', 'order_number', 'status', 'total', 'created_at'],
        'order_items' => ['id', 'order_id', 'product_id', 'product_name', 'quantity', 'price']
    ];
    
    foreach ($tables as $table => $requiredColumns) {
        echo "<h3>Tabela: {$table}</h3>";
        
        try {
            // Verificar se a tabela existe
            $columnCheck = $db->select("SHOW TABLES LIKE :table", ['table' => $table]);
            
            if (empty($columnCheck)) {
                echo "<p style='color:red'>✗ Tabela {$table} não existe!</p>";
                continue;
            }
            
            // Obter estrutura da tabela
            $columns = $db->select("SHOW COLUMNS FROM {$table}");
            
            if (empty($columns)) {
                echo "<p style='color:red'>✗ Não foi possível obter colunas da tabela {$table}!</p>";
                continue;
            }
            
            echo "<p style='color:green'>✓ Tabela {$table} existe com " . count($columns) . " colunas.</p>";
            
            // Verificar se todas as colunas obrigatórias existem
            $columnNames = array_column($columns, 'Field');
            $missingColumns = array_diff($requiredColumns, $columnNames);
            
            if (!empty($missingColumns)) {
                echo "<p style='color:red'>✗ Colunas obrigatórias ausentes: " . implode(', ', $missingColumns) . "</p>";
            } else {
                echo "<p style='color:green'>✓ Todas as colunas obrigatórias estão presentes.</p>";
            }
            
            // Mostrar estrutura completa
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
            
            foreach ($columns as $column) {
                $style = in_array($column['Field'], $requiredColumns) ? "background-color: #f0f8ff;" : "";
                
                echo "<tr style='{$style}'>";
                echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
                echo "<td>" . (isset($column['Default']) ? htmlspecialchars($column['Default']) : "NULL") . "</td>";
                echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            // Verificar quantidade de registros
            $count = $db->select("SELECT COUNT(*) as count FROM {$table}")[0]['count'];
            echo "<p>Registros: " . $count . "</p>";
            
            // Mostrar amostra de registros se existirem
            if ($count > 0) {
                echo "<h4>Amostra de Dados:</h4>";
                
                $sample = $db->select("SELECT * FROM {$table} LIMIT 3");
                
                echo "<table border='1' cellpadding='3'>";
                // Cabeçalho
                echo "<tr>";
                foreach (array_keys($sample[0]) as $key) {
                    echo "<th>" . htmlspecialchars($key) . "</th>";
                }
                echo "</tr>";
                
                // Dados
                foreach ($sample as $row) {
                    echo "<tr>";
                    foreach ($row as $key => $value) {
                        // Limitar texto longo
                        if (is_string($value) && strlen($value) > 50) {
                            $value = substr($value, 0, 47) . '...';
                        }
                        
                        // Não mostrar senhas completas
                        if ($key === 'password' && !empty($value)) {
                            $value = '********';
                        }
                        
                        echo "<td>" . (is_null($value) ? "NULL" : htmlspecialchars($value)) . "</td>";
                    }
                    echo "</tr>";
                }
                
                echo "</table>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>✗ Erro ao verificar tabela {$table}: " . $e->getMessage() . "</p>";
        }
    }
    
    // Verificar chaves estrangeiras
    echo "<h2>Verificação de Chaves Estrangeiras</h2>";
    
    $foreign_keys = [
        ['products', 'category_id', 'categories', 'id'],
        ['product_images', 'product_id', 'products', 'id'],
        ['cart_items', 'cart_id', 'carts', 'id'],
        ['cart_items', 'product_id', 'products', 'id'],
        ['order_items', 'order_id', 'orders', 'id'],
        ['order_items', 'product_id', 'products', 'id']
    ];
    
    foreach ($foreign_keys as $fk) {
        list($table, $column, $ref_table, $ref_column) = $fk;
        
        echo "<h3>Relação: {$table}.{$column} -> {$ref_table}.{$ref_column}</h3>";
        
        try {
            // Verificar se há registros órfãos
            $orphans = $db->select(
                "SELECT COUNT(*) as count FROM {$table} t " .
                "LEFT JOIN {$ref_table} r ON t.{$column} = r.{$ref_column} " .
                "WHERE t.{$column} IS NOT NULL AND r.{$ref_column} IS NULL"
            );
            
            $orphan_count = $orphans[0]['count'];
            
            if ($orphan_count > 0) {
                echo "<p style='color:red'>✗ Encontrados {$orphan_count} registros órfãos em {$table}.{$column} que não correspondem a registros em {$ref_table}.{$ref_column}!</p>";
                
                // Mostrar exemplos de registros órfãos
                $orphan_examples = $db->select(
                    "SELECT t.* FROM {$table} t " .
                    "LEFT JOIN {$ref_table} r ON t.{$column} = r.{$ref_column} " .
                    "WHERE t.{$column} IS NOT NULL AND r.{$ref_column} IS NULL " .
                    "LIMIT 3"
                );
                
                echo "<h4>Exemplos de Registros Órfãos:</h4>";
                echo "<table border='1' cellpadding='3'>";
                
                // Cabeçalho
                echo "<tr>";
                foreach (array_keys($orphan_examples[0]) as $header) {
                    echo "<th>{$header}</th>";
                }
                echo "</tr>";
                
                // Registros
                foreach ($orphan_examples as $row) {
                    echo "<tr>";
                    foreach ($row as $key => $value) {
                        // Destacar a coluna com chave estrangeira inválida
                        $style = ($key === $column) ? "color:red;font-weight:bold;" : "";
                        echo "<td style='{$style}'>" . (is_null($value) ? "NULL" : htmlspecialchars($value)) . "</td>";
                    }
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p style='color:green'>✓ Não foram encontrados registros órfãos.</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>✗ Erro ao verificar chave estrangeira: " . $e->getMessage() . "</p>";
        }
    }
    
    // Verificar índices
    echo "<h2>Verificação de Índices</h2>";
    
    $important_indices = [
        ['products', 'slug', 'UNIQUE'],
        ['categories', 'slug', 'UNIQUE'],
        ['cart_items', ['cart_id', 'product_id'], 'INDEX'],
        ['orders', 'order_number', 'UNIQUE']
    ];
    
    foreach ($important_indices as $idx) {
        $table = $idx[0];
        $columns = is_array($idx[1]) ? $idx[1] : [$idx[1]];
        $type = $idx[2];
        $columns_str = implode(', ', $columns);
        
        echo "<h3>Tabela {$table}: Índice {$type} em ({$columns_str})</h3>";
        
        try {
            $indices = $db->select("SHOW INDEX FROM {$table}");
            $found = false;
            $matching_indices = [];
            
            // Procurar índices que correspondam às colunas
            foreach ($indices as $index) {
                if (in_array($index['Column_name'], $columns)) {
                    $matching_indices[] = $index;
                }
            }
            
            // Agrupar por nome de índice
            $index_groups = [];
            foreach ($matching_indices as $index) {
                $index_groups[$index['Key_name']][] = $index['Column_name'];
            }
            
            // Verificar se todas as colunas estão presentes em algum índice
            foreach ($index_groups as $key_name => $index_columns) {
                $missing = array_diff($columns, $index_columns);
                
                if (empty($missing)) {
                    $found = true;
                    echo "<p style='color:green'>✓ Índice '{$key_name}' encontrado para colunas: " . implode(', ', $index_columns) . "</p>";
                    
                    // Verificar tipo (UNIQUE, etc)
                    $is_unique = false;
                    foreach ($matching_indices as $index) {
                        if ($index['Key_name'] === $key_name && $index['Non_unique'] == 0) {
                            $is_unique = true;
                            break;
                        }
                    }
                    
                    if ($type === 'UNIQUE' && !$is_unique) {
                        echo "<p style='color:orange'>⚠️ Índice encontrado, mas não é UNIQUE como recomendado.</p>";
                    } else if ($type === 'UNIQUE' && $is_unique) {
                        echo "<p style='color:green'>✓ Índice é UNIQUE como recomendado.</p>";
                    }
                }
            }
            
            if (!$found) {
                echo "<p style='color:red'>✗ Índice não encontrado para: " . implode(', ', $columns) . "</p>";
                
                // Se não encontrou, sugerir SQL para criar
                $columns_sql = implode(', ', $columns);
                $index_name = $table . '_' . implode('_', $columns) . '_idx';
                $index_type = ($type === 'UNIQUE') ? 'UNIQUE' : '';
                
                echo "<p>SQL sugerido para criar o índice:</p>";
                echo "<pre>CREATE {$index_type} INDEX {$index_name} ON {$table} ({$columns_sql});</pre>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>✗ Erro ao verificar índices da tabela {$table}: " . $e->getMessage() . "</p>";
        }
    }
    
    // Verificar integridade entre categorias e subcategorias
    echo "<h2>Verificação de Integridade de Categorias e Subcategorias</h2>";
    
    try {
        // Obter categorias com parent_id inválido
        $invalid_parents = $db->select(
            "SELECT c.id, c.name, c.parent_id FROM categories c " .
            "LEFT JOIN categories p ON c.parent_id = p.id " .
            "WHERE c.parent_id IS NOT NULL AND p.id IS NULL"
        );
        
        if (empty($invalid_parents)) {
            echo "<p style='color:green'>✓ Todas as subcategorias têm parent_id válido.</p>";
        } else {
            echo "<p style='color:red'>✗ Encontradas " . count($invalid_parents) . " subcategorias com parent_id inválido!</p>";
            
            echo "<table border='1' cellpadding='3'>";
            echo "<tr><th>ID</th><th>Nome</th><th>parent_id inválido</th></tr>";
            
            foreach ($invalid_parents as $cat) {
                echo "<tr>";
                echo "<td>" . $cat['id'] . "</td>";
                echo "<td>" . htmlspecialchars($cat['name']) . "</td>";
                echo "<td style='color:red'>" . $cat['parent_id'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Erro ao verificar integridade das categorias: " . $e->getMessage() . "</p>";
    }
    
    // Verificar produtos sem imagens
    echo "<h2>Verificação de Produtos sem Imagens</h2>";
    
    try {
        $products_without_images = $db->select(
            "SELECT p.id, p.name, p.slug FROM products p " .
            "LEFT JOIN product_images pi ON p.id = pi.product_id " .
            "WHERE pi.id IS NULL"
        );
        
        if (empty($products_without_images)) {
            echo "<p style='color:green'>✓ Todos os produtos têm pelo menos uma imagem.</p>";
        } else {
            echo "<p style='color:orange'>⚠️ Encontrados " . count($products_without_images) . " produtos sem imagens.</p>";
            
            echo "<table border='1' cellpadding='3'>";
            echo "<tr><th>ID</th><th>Nome</th><th>Slug</th></tr>";
            
            foreach ($products_without_images as $product) {
                echo "<tr>";
                echo "<td>" . $product['id'] . "</td>";
                echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                echo "<td>" . htmlspecialchars($product['slug']) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Erro ao verificar produtos sem imagens: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color:red'>Erro de Conexão</h2>";
    echo "<p>Não foi possível conectar ao banco de dados: " . $e->getMessage() . "</p>";
    
    echo "<h3>Diagnóstico:</h3>";
    echo "<ul>";
    echo "<li>Verifique se as credenciais em config.php estão corretas.</li>";
    echo "<li>Verifique se o servidor MySQL está em execução.</li>";
    echo "<li>Verifique se o banco de dados " . DB_NAME . " existe.</li>";
    echo "<li>Verifique se o usuário " . DB_USER . " tem permissões para acessar o banco.</li>";
    echo "</ul>";
}

// Botão de retorno
echo "<p><a href='" . BASE_URL . "' style='display:inline-block; margin-top:20px; padding:10px 15px; background-color:#4CAF50; color:white; text-decoration:none; border-radius:4px;'>Voltar para o Site</a></p>";
