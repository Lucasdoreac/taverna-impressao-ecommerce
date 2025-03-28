<?php
/**
 * db_test.php - Arquivo para testar conexão com o banco de dados
 */

// Cabeçalho para exibir texto puro
header('Content-Type: text/plain');

echo "=== TESTE DE CONEXÃO COM BANCO DE DADOS ===\n\n";

// Tentar carregar o arquivo de configuração
try {
    require_once __DIR__ . '/../app/config/config.php';
    echo "Arquivo de configuração carregado com sucesso.\n";
    
    // Exibir configurações do banco (sem exibir a senha completa)
    echo "Configurações de Banco de Dados:\n";
    echo "- Host: " . DB_HOST . "\n";
    echo "- Nome do banco: " . DB_NAME . "\n";
    echo "- Usuário: " . DB_USER . "\n";
    echo "- Senha: " . (empty(DB_PASS) ? "Não definida" : "Definida (não exibida por segurança)") . "\n\n";
    
} catch (Exception $e) {
    echo "ERRO ao carregar configurações: " . $e->getMessage() . "\n";
    exit(1);
}

// Tentar conectar ao banco de dados
try {
    echo "Tentando conectar ao banco de dados...\n";
    
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    echo "SUCESSO! Conexão com o banco de dados estabelecida.\n\n";
    
    // Testar uma consulta simples
    echo "Verificando tabelas no banco de dados:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "Tabelas encontradas: " . implode(", ", $tables) . "\n";
    } else {
        echo "Nenhuma tabela encontrada no banco de dados.\n";
    }
    
} catch (PDOException $e) {
    echo "ERRO na conexão com banco de dados: " . $e->getMessage() . "\n";
    
    // Sugestões para resolução de problemas comuns
    echo "\nSugestões para solução:\n";
    
    if (strpos($e->getMessage(), "Access denied") !== false) {
        echo "- O usuário ou senha do banco de dados podem estar incorretos.\n";
        echo "- Verifique as credenciais do banco de dados.\n";
    }
    
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        echo "- O banco de dados especificado não existe.\n";
        echo "- Verifique se você criou o banco de dados no painel Hostinger.\n";
    }
    
    if (strpos($e->getMessage(), "Connection refused") !== false) {
        echo "- O servidor de banco de dados está recusando conexões.\n";
        echo "- Verifique se o host está correto.\n";
    }
}

echo "\n=== FIM DO TESTE ===\n";
