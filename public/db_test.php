<?php
/**
 * Arquivo de diagnóstico de conexão com o banco de dados
 * Este arquivo testa a conexão com o banco de dados e exibe informações 
 * relevantes para diagnóstico de problemas.
 */

// Definir exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Carregar configurações
require_once __DIR__ . '/../app/config/config.php';

echo "<h1>Diagnóstico de Conexão com Banco de Dados</h1>";
echo "<h2>Configurações</h2>";
echo "<pre>";
echo "HOST: " . DB_HOST . "\n";
echo "DATABASE: " . DB_NAME . "\n";
echo "USER: " . DB_USER . "\n";
echo "AMBIENTE: " . ENVIRONMENT . "\n";
echo "</pre>";

echo "<h2>Teste de Conexão</h2>";
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<div style='color: green; font-weight: bold;'>✓ Conexão com o banco de dados estabelecida com sucesso!</div>";

    // Verificar tabelas
    echo "<h2>Verificação de Tabelas</h2>";
    $tables = [
        'users', 'categories', 'products', 'product_images',
        'customization_options', 'carts', 'cart_items',
        'orders', 'order_items', 'coupons', 'addresses', 'settings'
    ];
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Tabela</th><th>Existe?</th><th>Registros</th></tr>";
    
    foreach ($tables as $table) {
        echo "<tr>";
        echo "<td>{$table}</td>";
        
        // Verificar se a tabela existe
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $tableExists = $stmt->rowCount() > 0;
        
        echo "<td>" . ($tableExists ? "<span style='color:green'>Sim</span>" : "<span style='color:red'>Não</span>") . "</td>";
        
        // Contar registros se a tabela existir
        if ($tableExists) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `{$table}`");
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<td>{$count}</td>";
        } else {
            echo "<td>-</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Exibir estrutura de algumas tabelas críticas
    $criticalTables = ['orders', 'users', 'carts', 'cart_items'];
    
    echo "<h2>Estrutura de Tabelas Críticas</h2>";
    
    foreach ($criticalTables as $table) {
        // Verificar se a tabela existe antes de mostrar sua estrutura
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        
        if ($stmt->rowCount() > 0) {
            echo "<h3>Estrutura da tabela: {$table}</h3>";
            
            $stmt = $pdo->prepare("DESCRIBE `{$table}`");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "<td>{$column['Extra']}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<h3>Tabela {$table} não existe</h3>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div style='color: red; font-weight: bold;'>✗ Erro na conexão com o banco de dados!</div>";
    echo "<div>Mensagem de erro: " . $e->getMessage() . "</div>";
}

// Verificar extensões PHP necessárias
echo "<h2>Extensões PHP</h2>";
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'session', 'mbstring'];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Extensão</th><th>Carregada?</th></tr>";

foreach ($requiredExtensions as $ext) {
    echo "<tr>";
    echo "<td>{$ext}</td>";
    $loaded = extension_loaded($ext);
    echo "<td>" . ($loaded ? "<span style='color:green'>Sim</span>" : "<span style='color:red'>Não</span>") . "</td>";
    echo "</tr>";
}

echo "</table>";

// Verificar permissões de diretórios
echo "<h2>Permissões de Diretórios</h2>";
$directories = [
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/products',
    __DIR__ . '/uploads/categories',
    __DIR__ . '/uploads/customization'
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Diretório</th><th>Existe?</th><th>Gravável?</th></tr>";

foreach ($directories as $dir) {
    echo "<tr>";
    echo "<td>{$dir}</td>";
    
    $exists = is_dir($dir);
    echo "<td>" . ($exists ? "<span style='color:green'>Sim</span>" : "<span style='color:red'>Não</span>") . "</td>";
    
    $writable = is_writable($dir);
    echo "<td>" . ($writable ? "<span style='color:green'>Sim</span>" : "<span style='color:red'>Não</span>") . "</td>";
    
    echo "</tr>";
}

echo "</table>";

// Informações sobre a configuração PHP
echo "<h2>Configuração PHP</h2>";
echo "<pre>";
echo "Versão PHP: " . phpversion() . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "error_reporting: " . ini_get('error_reporting') . "\n";
echo "log_errors: " . ini_get('log_errors') . "\n";
echo "error_log: " . ini_get('error_log') . "\n";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "</pre>";
