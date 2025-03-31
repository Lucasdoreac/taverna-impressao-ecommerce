<?php
// Arquivo para diagnóstico específico de problemas com carregamento de classes
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Definir cabeçalhos para página HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Classes - TAVERNA DA IMPRESSÃO</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow: auto; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Diagnóstico de Classes - TAVERNA DA IMPRESSÃO</h1>
    
    <h2>Informações do Ambiente</h2>
    <?php
    // Exibir informações do PHP
    echo "<p>PHP Version: " . phpversion() . "</p>";
    echo "<p>Sistema Operacional: " . PHP_OS . "</p>";
    echo "<p>Diretório Atual: " . getcwd() . "</p>";
    ?>

    <h2>Estrutura do Projeto</h2>
    <?php
    // Definir diretórios importantes a verificar
    $directories = [
        'ROOT' => dirname(__DIR__),
        'app' => dirname(__DIR__) . '/app',
        'app/core' => dirname(__DIR__) . '/app/core',
        'app/models' => dirname(__DIR__) . '/app/models',
        'app/controllers' => dirname(__DIR__) . '/app/controllers',
        'app/views' => dirname(__DIR__) . '/app/views',
        'app/helpers' => dirname(__DIR__) . '/app/helpers',
        'public' => dirname(__DIR__) . '/public',
    ];

    echo "<table>";
    echo "<tr><th>Diretório</th><th>Caminho</th><th>Existe</th><th>Permissões</th></tr>";
    
    foreach ($directories as $name => $path) {
        echo "<tr>";
        echo "<td>{$name}</td>";
        echo "<td>{$path}</td>";
        
        if (file_exists($path)) {
            echo "<td class='success'>Sim</td>";
            echo "<td>" . substr(sprintf('%o', fileperms($path)), -4) . "</td>";
        } else {
            echo "<td class='error'>Não</td>";
            echo "<td>-</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    ?>

    <h2>Verificação de Arquivos de Classe</h2>
    <?php
    // Classes essenciais a verificar
    $classes = [
        'Controller' => 'app/core/Controller.php',
        'Model' => 'app/core/Model.php',
        'Database' => 'app/helpers/Database.php',
        'ProductModel' => 'app/models/ProductModel.php',
        'CategoryModel' => 'app/models/CategoryModel.php',
        'Router' => 'app/helpers/Router.php',
    ];

    echo "<table>";
    echo "<tr><th>Classe</th><th>Arquivo</th><th>Existe</th><th>Tamanho</th><th>Última Modificação</th></tr>";
    
    foreach ($classes as $class => $file) {
        $fullPath = dirname(__DIR__) . '/' . $file;
        echo "<tr>";
        echo "<td>{$class}</td>";
        echo "<td>{$file}</td>";
        
        if (file_exists($fullPath)) {
            echo "<td class='success'>Sim</td>";
            echo "<td>" . filesize($fullPath) . " bytes</td>";
            echo "<td>" . date("Y-m-d H:i:s", filemtime($fullPath)) . "</td>";
        } else {
            echo "<td class='error'>Não</td>";
            echo "<td>-</td>";
            echo "<td>-</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";
    ?>

    <h2>Testes de Include</h2>
    <?php
    // Primeiro vamos incluir o config.php para definir constantes
    include_once dirname(__DIR__) . '/app/config/config.php';

    echo "<p>APP_PATH definido como: " . (defined('APP_PATH') ? APP_PATH : 'Não definido') . "</p>";
    
    // Agora verifica se o autoloader existe
    $autoloaderPath = dirname(__DIR__) . '/app/autoload.php';
    if (file_exists($autoloaderPath)) {
        echo "<p class='success'>✓ Autoloader encontrado em {$autoloaderPath}</p>";
        include_once $autoloaderPath;
        echo "<p class='success'>✓ Autoloader incluído</p>";
    } else {
        echo "<p class='error'>✗ Autoloader não encontrado em {$autoloaderPath}</p>";
    }

    // Testar carregamento direto das classes essenciais
    echo "<h3>Carregamento direto sem autoloader:</h3>";
    
    // Testar Controller e Model diretamente
    $controllerPath = dirname(__DIR__) . '/app/core/Controller.php';
    if (file_exists($controllerPath)) {
        echo "<p>Tentando carregar Controller de {$controllerPath}...</p>";
        try {
            include_once $controllerPath;
            echo "<p class='success'>✓ Controller.php incluído com sucesso</p>";
            if (class_exists('Controller')) {
                echo "<p class='success'>✓ Classe Controller encontrada</p>";
            } else {
                echo "<p class='error'>✗ Classe Controller não encontrada após inclusão</p>";
                // Verificar o conteúdo do arquivo
                echo "<p>Primeiras 200 caracteres do arquivo Controller.php:</p>";
                echo "<pre>" . htmlspecialchars(substr(file_get_contents($controllerPath), 0, 200)) . "...</pre>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Erro ao incluir Controller.php: " . $e->getMessage() . "</p>";
        }
    }

    $modelPath = dirname(__DIR__) . '/app/core/Model.php';
    if (file_exists($modelPath)) {
        echo "<p>Tentando carregar Model de {$modelPath}...</p>";
        try {
            include_once $modelPath;
            echo "<p class='success'>✓ Model.php incluído com sucesso</p>";
            if (class_exists('Model')) {
                echo "<p class='success'>✓ Classe Model encontrada</p>";
            } else {
                echo "<p class='error'>✗ Classe Model não encontrada após inclusão</p>";
                // Verificar o conteúdo do arquivo
                echo "<p>Primeiras 200 caracteres do arquivo Model.php:</p>";
                echo "<pre>" . htmlspecialchars(substr(file_get_contents($modelPath), 0, 200)) . "...</pre>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Erro ao incluir Model.php: " . $e->getMessage() . "</p>";
        }
    }

    // Testar carregamento de uma classe de modelo e controlador que dependem de Model e Controller
    if (class_exists('Model') && class_exists('Controller')) {
        echo "<h3>Testando classes dependentes:</h3>";
        
        // Testar ProductModel
        try {
            include_once dirname(__DIR__) . '/app/models/ProductModel.php';
            echo "<p class='success'>✓ ProductModel.php incluído com sucesso</p>";
        } catch (Exception $e) {
            echo "<p class='error'>✗ Erro ao incluir ProductModel.php: " . $e->getMessage() . "</p>";
        }
        
        // Testar CategoryController
        try {
            include_once dirname(__DIR__) . '/app/controllers/CategoryController.php';
            echo "<p class='success'>✓ CategoryController.php incluído com sucesso</p>";
        } catch (Exception $e) {
            echo "<p class='error'>✗ Erro ao incluir CategoryController.php: " . $e->getMessage() . "</p>";
        }
    }
    ?>
    
    <h2>Classes Declaradas</h2>
    <?php
    $declaredClasses = get_declared_classes();
    $appClasses = array_filter($declaredClasses, function($class) {
        return !strpos($class, '\\') && 
               !in_array($class, ['stdClass', 'Exception', 'Error', 'PDO', 'DateTime']);
    });
    
    echo "<p>Total de classes declaradas: " . count($declaredClasses) . "</p>";
    echo "<p>Classes relevantes encontradas: " . count($appClasses) . "</p>";
    echo "<pre>" . print_r($appClasses, true) . "</pre>";
    ?>

    <p><strong>IMPORTANTE:</strong> Por segurança, remova este arquivo após concluir o diagnóstico.</p>
</body>
</html>
