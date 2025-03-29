<?php
// Arquivo de diagnóstico para erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir configuração
require_once __DIR__ . '/../app/config/config.php';

echo "<h1>Diagnóstico de Erros - TAVERNA DA IMPRESSÃO</h1>";

// Verificar função app_log
echo "<h2>Verificação de função app_log</h2>";
if (function_exists('app_log')) {
    echo "<p style='color:green'>✓ Função app_log está disponível</p>";
    // Testar a função
    app_log("Teste de log via diagnóstico", "info");
    echo "<p>Log de teste criado. Verifique o diretório de logs.</p>";
} else {
    echo "<p style='color:red'>✗ Função app_log NÃO está disponível!</p>";
    echo "<p>Verifique se o arquivo app/helpers/Logger.php está carregado corretamente.</p>";
}

// Verificar constantes
echo "<h2>Verificação de constantes</h2>";
$constants = [
    'ENVIRONMENT', 'BASE_URL', 'CURRENCY', 'CURRENCY_SYMBOL',
    'DB_HOST', 'DB_NAME', 'DB_USER'
];

foreach ($constants as $constant) {
    if (defined($constant)) {
        echo "<p style='color:green'>✓ Constante {$constant} está definida: " . constant($constant) . "</p>";
    } else {
        echo "<p style='color:red'>✗ Constante {$constant} NÃO está definida!</p>";
    }
}

// Verificar autoloader
echo "<h2>Verificação do autoloader</h2>";
try {
    // Tentar carregar alguns arquivos importantes
    $files = [
        'app/helpers/Router.php',
        'app/helpers/Logger.php',
        'app/config/routes.php'
    ];
    
    foreach ($files as $file) {
        $fullPath = ROOT_PATH . '/' . $file;
        if (file_exists($fullPath)) {
            echo "<p style='color:green'>✓ Arquivo {$file} existe</p>";
        } else {
            echo "<p style='color:red'>✗ Arquivo {$file} NÃO existe!</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Erro ao verificar arquivos: " . $e->getMessage() . "</p>";
}

// Verificar permissões
echo "<h2>Verificação de permissões</h2>";
$directories = [
    'logs',
    'public/uploads',
    'app/views/errors'
];

foreach ($directories as $dir) {
    $fullPath = ROOT_PATH . '/' . $dir;
    if (!is_dir($fullPath)) {
        echo "<p style='color:orange'>⚠ Diretório {$dir} não existe. Tentando criar...</p>";
        if (mkdir($fullPath, 0755, true)) {
            echo "<p style='color:green'>✓ Diretório {$dir} criado com sucesso!</p>";
        } else {
            echo "<p style='color:red'>✗ Não foi possível criar o diretório {$dir}!</p>";
        }
    } else {
        if (is_writable($fullPath)) {
            echo "<p style='color:green'>✓ Diretório {$dir} tem permissões de escrita</p>";
        } else {
            echo "<p style='color:red'>✗ Diretório {$dir} NÃO tem permissões de escrita!</p>";
        }
    }
}

// Verificar conexão com o banco de dados
echo "<h2>Verificação da conexão com o banco de dados</h2>";
if (class_exists('Database')) {
    try {
        require_once ROOT_PATH . '/app/helpers/Database.php';
        $db = Database::getInstance();
        echo "<p style='color:green'>✓ Conexão com o banco de dados estabelecida com sucesso!</p>";
        
        // Testar uma consulta simples
        $result = $db->select("SHOW TABLES");
        echo "<p>Tabelas no banco de dados:</p>";
        echo "<ul>";
        foreach ($result as $row) {
            echo "<li>" . current($row) . "</li>";
        }
        echo "</ul>";
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Erro na conexão com o banco de dados: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>✗ Classe Database não encontrada!</p>";
}

// Verificar roteamento
echo "<h2>Informações de Roteamento</h2>";
echo "<p>SERVER_NAME: " . $_SERVER['SERVER_NAME'] . "</p>";
echo "<p>REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p>PHP_SELF: " . $_SERVER['PHP_SELF'] . "</p>";
echo "<p>QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? '') . "</p>";

// Próximos passos
echo "<h2>Próximos Passos</h2>";
echo "<p>Se todos os testes acima passaram, o site deve estar funcionando corretamente. Caso contrário, corrija os erros indicados.</p>";
echo "<p>Links úteis:</p>";
echo "<ul>";
echo "<li><a href='" . BASE_URL . "'>Página Inicial</a></li>";
echo "<li><a href='" . BASE_URL . "?debug=1'>Página Inicial (com Debug)</a></li>";
echo "</ul>";
