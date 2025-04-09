<?php
/**
 * Script de configuração inicial para o ambiente Hostinger
 * 
 * Este script deve ser executado uma única vez após o upload dos arquivos
 * para configurar corretamente o ambiente na hospedagem compartilhada Hostinger.
 * 
 * ATENÇÃO: Exclua este arquivo após a execução!
 */

// Verificação de segurança - código de acesso
$accessCode = isset($_GET['code']) ? $_GET['code'] : '';

if ($accessCode !== 'setup_taverna_3d') {
    die('Acesso negado. Código de acesso inválido.');
}

// Função para verificar e criar diretórios
function checkAndCreateDirectory($path) {
    if (!file_exists($path)) {
        if (mkdir($path, 0755, true)) {
            echo "✅ Diretório criado: $path<br>";
        } else {
            echo "❌ ERRO: Não foi possível criar o diretório: $path<br>";
        }
    } else {
        echo "ℹ️ Diretório já existe: $path<br>";
        
        // Verificar permissões
        if (is_writable($path)) {
            echo "✅ Diretório tem permissões de escrita<br>";
        } else {
            echo "❌ ERRO: Diretório não tem permissões de escrita: $path<br>";
            if (!chmod($path, 0755)) {
                echo "❌ ERRO: Não foi possível modificar as permissões do diretório<br>";
            } else {
                echo "✅ Permissões atualizadas para o diretório<br>";
            }
        }
    }
}

// Função para verificar arquivo de configuração
function checkConfig() {
    if (file_exists(__DIR__ . '/app/config/config.php')) {
        echo "✅ Arquivo de configuração encontrado<br>";
        
        // Verificar se o ambiente está configurado para produção
        include __DIR__ . '/app/config/config.php';
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            echo "✅ Ambiente configurado para produção<br>";
        } else {
            echo "⚠️ AVISO: Ambiente não está configurado para produção<br>";
        }
    } else {
        echo "❌ ERRO: Arquivo de configuração não encontrado<br>";
        if (file_exists(__DIR__ . '/app/config/config.sample.php')) {
            echo "ℹ️ Arquivo de configuração de exemplo encontrado. Copie para config.php e configure-o<br>";
        } else {
            echo "❌ ERRO: Arquivo de configuração de exemplo não encontrado<br>";
        }
    }
}

// Função para verificar requisitos do PHP
function checkPhpRequirements() {
    echo "<h3>Verificando Requisitos do PHP</h3>";
    
    // Versão do PHP
    echo "Versão do PHP: " . phpversion();
    if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
        echo " ✅<br>";
    } else {
        echo " ❌ (Requer PHP 7.4 ou superior)<br>";
    }
    
    // Extensões necessárias
    $requiredExtensions = [
        'pdo',
        'pdo_mysql',
        'json',
        'mbstring',
        'fileinfo',
        'gd'
    ];
    
    foreach ($requiredExtensions as $ext) {
        if (extension_loaded($ext)) {
            echo "Extensão $ext: ✅<br>";
        } else {
            echo "Extensão $ext: ❌ (Não instalada)<br>";
        }
    }
    
    // Configurações recomendadas
    $recommendedSettings = [
        'file_uploads' => true,
        'post_max_size' => '10M',
        'upload_max_filesize' => '10M',
        'memory_limit' => '128M',
        'max_execution_time' => 30
    ];
    
    foreach ($recommendedSettings as $setting => $recommended) {
        $current = ini_get($setting);
        echo "$setting: $current";
        
        if ($setting === 'file_uploads') {
            if ($current == $recommended) {
                echo " ✅<br>";
            } else {
                echo " ❌ (Recomendado: " . ($recommended ? 'On' : 'Off') . ")<br>";
            }
        } else if (in_array($setting, ['post_max_size', 'upload_max_filesize', 'memory_limit'])) {
            // Converter para bytes para comparação
            $currentBytes = preg_replace('/[^0-9]/', '', $current);
            $recommendedBytes = preg_replace('/[^0-9]/', '', $recommended);
            
            if ($currentBytes >= $recommendedBytes) {
                echo " ✅<br>";
            } else {
                echo " ❌ (Recomendado: $recommended)<br>";
            }
        } else {
            if ($current >= $recommended) {
                echo " ✅<br>";
            } else {
                echo " ❌ (Recomendado: $recommended ou maior)<br>";
            }
        }
    }
}

// Função para verificar .htaccess
function checkHtaccess() {
    if (file_exists(__DIR__ . '/.htaccess')) {
        echo "✅ Arquivo .htaccess encontrado<br>";
        
        // Verificar se mod_rewrite está ativo
        if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {
            echo "✅ mod_rewrite está ativo<br>";
        } else {
            echo "⚠️ Não foi possível verificar se mod_rewrite está ativo<br>";
        }
    } else {
        echo "❌ ERRO: Arquivo .htaccess não encontrado<br>";
    }
}

// Função para verificar conexão com o banco de dados
function checkDatabase() {
    if (!file_exists(__DIR__ . '/app/config/config.php')) {
        echo "❌ ERRO: Arquivo de configuração não encontrado. Não é possível testar a conexão com o banco de dados<br>";
        return;
    }
    
    include __DIR__ . '/app/config/config.php';
    
    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
        echo "❌ ERRO: Configurações de banco de dados incompletas<br>";
        return;
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "✅ Conexão com o banco de dados estabelecida com sucesso<br>";
        
        // Verificar tabelas
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "Tabelas encontradas: " . count($tables) . "<br>";
        if (count($tables) === 0) {
            echo "⚠️ AVISO: Não foram encontradas tabelas no banco de dados<br>";
        } else {
            echo "Tabelas: " . implode(', ', $tables) . "<br>";
        }
    } catch (PDOException $e) {
        echo "❌ ERRO: Não foi possível conectar ao banco de dados: " . $e->getMessage() . "<br>";
    }
}

// Iniciar verificação
echo "<!DOCTYPE html>
<html>
<head>
    <title>Configuração do Taverna da Impressão 3D</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 0 20px;
            line-height: 1.6;
        }
        h1, h2, h3 {
            color: #333;
        }
        .success {
            color: green;
        }
        .warning {
            color: orange;
        }
        .error {
            color: red;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Script de Configuração - Taverna da Impressão 3D</h1>
    <h2>Verificação do Ambiente</h2>";

// Verificar estrutura de diretórios
echo "<h3>Verificando Diretórios</h3>";
checkAndCreateDirectory(__DIR__ . '/uploads');
checkAndCreateDirectory(__DIR__ . '/uploads/customer_models');
checkAndCreateDirectory(__DIR__ . '/logs');
checkAndCreateDirectory(__DIR__ . '/cache');

// Verificar arquivo de configuração
echo "<h3>Verificando Configuração</h3>";
checkConfig();

// Verificar requisitos do PHP
checkPhpRequirements();

// Verificar .htaccess
echo "<h3>Verificando .htaccess</h3>";
checkHtaccess();

// Verificar conexão com o banco de dados
echo "<h3>Verificando Banco de Dados</h3>";
checkDatabase();

// Dicas finais
echo "<h2>Próximos Passos</h2>
<ol>
    <li>Resolva quaisquer erros ou avisos mostrados acima</li>
    <li>Certifique-se de que o arquivo config.php está configurado corretamente</li>
    <li>Verifique se as permissões de diretório estão corretas</li>
    <li>Remova este arquivo após a configuração</li>
</ol>

<h2>Informações do Servidor</h2>
<pre>";
print_r($_SERVER);
echo "</pre>

<p class='warning'><strong>ATENÇÃO:</strong> Por razões de segurança, exclua este arquivo (setup-hostinger.php) após a configuração!</p>
</body>
</html>";
