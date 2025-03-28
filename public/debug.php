<?php
// Script de diagnóstico para TAVERNA DA IMPRESSÃO

// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cabeçalho para saída em texto plano
header('Content-Type: text/plain');

echo "=== DIAGNÓSTICO DA TAVERNA DA IMPRESSÃO ===\n\n";

// Informações do PHP
echo "-- VERSÃO DO PHP --\n";
echo "PHP version: " . phpversion() . "\n";
echo "Loaded extensions: " . implode(", ", get_loaded_extensions()) . "\n\n";

// Verificar conexão com banco de dados
echo "-- VERIFICAÇÃO DO BANCO DE DADOS --\n";
try {
    // Carregar configurações
    if (file_exists('../app/config/config.php')) {
        include_once '../app/config/config.php';
        echo "Arquivo de configuração carregado com sucesso.\n";
    } else {
        echo "ERRO: Arquivo de configuração não encontrado.\n";
    }
    
    // Tentar conexão
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
        $db = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "Conexão com banco de dados estabelecida com sucesso.\n";
        
        // Verificar tabelas
        $tables = [
            'users', 'products', 'categories', 'product_images', 
            'customization_options', 'carts', 'cart_items'
        ];
        
        echo "\nVerificando tabelas:\n";
        foreach ($tables as $table) {
            try {
                $query = $db->query("SELECT COUNT(*) FROM $table");
                $count = $query->fetchColumn();
                echo "- $table: $count registros\n";
            } catch (PDOException $e) {
                echo "- $table: ERRO (" . $e->getMessage() . ")\n";
            }
        }
    } else {
        echo "ERRO: Constantes de banco de dados não definidas corretamente.\n";
    }
} catch (PDOException $e) {
    echo "ERRO na conexão com banco de dados: " . $e->getMessage() . "\n";
}

// Verificar estrutura de diretórios
echo "\n-- VERIFICAÇÃO DE DIRETÓRIOS --\n";
$directories = [
    '../app',
    '../app/controllers',
    '../app/models',
    '../app/views',
    '../app/helpers',
    '../app/config',
    '../public/assets',
    '../public/uploads'
];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "$dir: Existe\n";
    } else {
        echo "$dir: NÃO EXISTE\n";
    }
}

// Verificar arquivos críticos
echo "\n-- VERIFICAÇÃO DE ARQUIVOS CRÍTICOS --\n";
$files = [
    '../app/config/config.php',
    '../app/config/routes.php',
    '../app/helpers/Router.php',
    '../app/helpers/Database.php',
    '../app/models/ProductModel.php',
    '../app/models/CategoryModel.php',
    '../app/controllers/ProductController.php',
    '../app/controllers/CategoryController.php',
    '../public/.htaccess',
    '../.htaccess'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "$file: Existe (" . filesize($file) . " bytes)\n";
    } else {
        echo "$file: NÃO EXISTE\n";
    }
}

// Verificar configuração .htaccess
echo "\n-- CONTEÚDO DO .HTACCESS --\n";
if (file_exists('../public/.htaccess')) {
    echo "public/.htaccess:\n" . file_get_contents('../public/.htaccess') . "\n\n";
} else {
    echo "ERRO: public/.htaccess não encontrado\n\n";
}

if (file_exists('../.htaccess')) {
    echo ".htaccess (raiz):\n" . file_get_contents('../.htaccess') . "\n";
} else {
    echo "ERRO: .htaccess (raiz) não encontrado\n";
}

// Registrar caminhos SERVER
echo "\n-- VARIÁVEIS SERVER --\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";

// Tentativa de obter log de erros
echo "\n-- ÚLTIMOS ERROS LOG --\n";
$errorLog = ini_get('error_log');
echo "Caminho do log de erros: $errorLog\n";
if (file_exists($errorLog) && is_readable($errorLog)) {
    echo "Últimas 20 linhas do log:\n";
    $logs = file($errorLog);
    $logs = array_slice($logs, -20);
    echo implode("", $logs);
} else {
    echo "Não foi possível ler o arquivo de log\n";
    
    // Tentar logs alternativos
    $altLogs = [
        '/var/log/apache2/error.log',
        '/var/log/httpd/error_log',
        '/var/log/php-errors.log'
    ];
    
    foreach ($altLogs as $log) {
        if (file_exists($log) && is_readable($log)) {
            echo "Encontrado log alternativo: $log\n";
            break;
        }
    }
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
?>