<?php
// Arquivo de diagnóstico para teste de conexão com o banco de dados
// IMPORTANTE: Remover este arquivo após o diagnóstico

// Definir exibição de erros para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico de Conexão ao Banco de Dados</h1>";
echo "<p>Este arquivo deve ser removido após o diagnóstico!</p>";

// Informações de conexão - usando IP e porta explícitos
$db_host = '127.0.0.1:3306';  // Conexão TCP/IP explícita
$db_name = 'u135851624_taverna';
$db_user = 'u135851624_teverna'; // Corrigido para teverna com "v"
$db_pass = '#Taverna1'; // Senha completa correta

echo "<h2>Configurações Atuais</h2>";
echo "<ul>";
echo "<li>Host: " . $db_host . "</li>";
echo "<li>Database: " . $db_name . "</li>";
echo "<li>Username: " . $db_user . "</li>";
echo "<li>Password: " . str_repeat('*', strlen($db_pass)) . "</li>";
echo "</ul>";

echo "<h2>Teste de Conexão</h2>";

// Tentar conexão com PDO
try {
    echo "<h3>Conexão PDO</h3>";
    $start_time = microtime(true);
    
    // Tentar conexão
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]
    );
    
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000; // em ms
    
    echo "<div style='color: green; font-weight: bold;'>";
    echo "✓ Conexão PDO estabelecida com sucesso em " . number_format($execution_time, 2) . "ms";
    echo "</div>";
    
    // Informações do servidor
    echo "<h3>Informações do Servidor</h3>";
    $server_info = $pdo->getAttribute(PDO::ATTR_SERVER_INFO);
    $server_version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    $connection_status = $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
    
    echo "<ul>";
    echo "<li>Versão do Servidor: " . $server_version . "</li>";
    echo "<li>Status da Conexão: " . $connection_status . "</li>";
    echo "</ul>";
    
    // Testar consulta simples
    echo "<h3>Teste de Consulta</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<div style='color: green;'>";
        echo "✓ Banco de dados contém " . count($tables) . " tabelas:";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div style='color: orange;'>";
        echo "! Banco de dados existe, mas não contém tabelas.";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color: red; font-weight: bold;'>";
    echo "✗ Erro na conexão PDO: " . $e->getMessage();
    echo "</div>";
    
    echo "<h3>Diagnóstico de Erro</h3>";
    
    // Analisar o erro
    if (strpos($e->getMessage(), "Access denied") !== false) {
        echo "<p>O erro parece estar relacionado às credenciais de acesso. Possíveis causas:</p>";
        echo "<ul>";
        echo "<li>Senha incorreta</li>";
        echo "<li>Usuário não tem permissão para acessar este banco de dados</li>";
        echo "<li>Usuário não tem permissão para acessar a partir deste host</li>";
        echo "</ul>";
        
        echo "<p>Sugestões:</p>";
        echo "<ul>";
        echo "<li>Verifique a senha no painel de controle da Hostinger</li>";
        echo "<li>Verifique se o usuário tem permissões no banco de dados</li>";
        echo "<li>Tente recriar o usuário e banco de dados no painel da Hostinger</li>";
        echo "<li>Confirme se o banco de dados permite acesso via endereço IP (127.0.0.1)</li>";
        echo "</ul>";
    } elseif (strpos($e->getMessage(), "Unknown database") !== false) {
        echo "<p>O banco de dados especificado não existe. Possíveis causas:</p>";
        echo "<ul>";
        echo "<li>Nome do banco de dados incorreto</li>";
        echo "<li>Banco de dados não foi criado</li>";
        echo "</ul>";
        
        echo "<p>Sugestões:</p>";
        echo "<ul>";
        echo "<li>Verifique o nome do banco de dados no painel da Hostinger</li>";
        echo "<li>Crie o banco de dados no painel da Hostinger</li>";
        echo "</ul>";
    } elseif (strpos($e->getMessage(), "Could not find driver") !== false) {
        echo "<p>Driver PDO MySQL não está disponível. Contate o suporte da Hostinger.</p>";
    } elseif (strpos($e->getMessage(), "Connection refused") !== false) {
        echo "<p>O servidor está recusando conexões. Possíveis causas:</p>";
        echo "<ul>";
        echo "<li>Servidor MySQL não está rodando</li>";
        echo "<li>Firewall bloqueando a conexão</li>";
        echo "<li>Host incorreto ou porta incorreta</li>";
        echo "</ul>";
    } else {
        echo "<p>Erro não específico. Contate o suporte da Hostinger para mais informações.</p>";
    }
}

// Teste alternativo com mysqli
echo "<h3>Teste Alternativo (MySQLi)</h3>";
try {
    // Separar host e porta
    $host_parts = explode(':', $db_host);
    $mysqli_host = $host_parts[0];
    $mysqli_port = isset($host_parts[1]) ? (int)$host_parts[1] : 3306;
    
    $mysqli = new mysqli($mysqli_host, $db_user, $db_pass, $db_name, $mysqli_port);
    
    if ($mysqli->connect_errno) {
        throw new Exception("Erro MySQLi: " . $mysqli->connect_error);
    }
    
    echo "<div style='color: green; font-weight: bold;'>";
    echo "✓ Conexão MySQLi estabelecida com sucesso";
    echo "</div>";
    
    $mysqli->close();
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>";
    echo "✗ " . $e->getMessage();
    echo "</div>";
}

// Remover link para este arquivo após uso
echo "<div style='margin-top: 30px; padding: 10px; background-color: #ffe6e6; border: 1px solid #ff9999;'>";
echo "<strong>ATENÇÃO:</strong> Por segurança, remova este arquivo após o diagnóstico!";
echo "</div>";
