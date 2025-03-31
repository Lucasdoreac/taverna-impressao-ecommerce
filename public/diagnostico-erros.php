<?php
/**
 * Ferramenta de Diagnóstico - Taverna da Impressão 3D
 * 
 * Essa ferramenta analisa e corrige problemas comuns no sistema, incluindo:
 * - Problemas de conexão com banco de dados
 * - Verificação de classes e estruturas
 * - Problemas com a exibição de produtos
 * - Correção das condições is_active nas consultas
 */

// Definir exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Constantes de caminho 
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEWS_PATH', APP_PATH . '/views');

require_once '../app/config/config.php';

// Função para testar a conexão com o banco de dados
function testDatabase() {
    echo "<h3>Testando conexão com o banco de dados</h3>";
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        echo "<div class='success'>✅ Conexão com o banco de dados estabelecida com sucesso.</div>";
        return $pdo;
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Erro ao conectar ao banco de dados: " . $e->getMessage() . "</div>";
        return null;
    }
}

// Verificar estrutura da tabela de produtos
function testProductsTable($pdo) {
    echo "<h3>Verificando tabela de produtos</h3>";
    
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
        if ($stmt->rowCount() === 0) {
            echo "<div class='error'>❌ Tabela 'products' não encontrada.</div>";
            return false;
        }
        
        echo "<div class='success'>✅ Tabela 'products' encontrada.</div>";
        
        // Verificar estrutura da tabela
        $stmt = $pdo->query("DESCRIBE products");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = [
            'id', 'name', 'slug', 'description', 'price', 
            'category_id', 'is_active', 'is_tested', 'stock'
        ];
        
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (!empty($missingColumns)) {
            echo "<div class='error'>❌ Colunas obrigatórias ausentes na tabela 'products': " . implode(', ', $missingColumns) . "</div>";
            return false;
        }
        
        echo "<div class='success'>✅ Estrutura da tabela 'products' verificada com sucesso.</div>";
        
        // Verificar o campo is_active
        $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active FROM products");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<div class='info'>📊 Total de produtos no banco: " . $result['total'] . "</div>";
        echo "<div class='info'>📊 Total de produtos ativos (is_active = 1): " . $result['active'] . "</div>";
        
        if ($result['active'] == 0 && $result['total'] > 0) {
            echo "<div class='warning'>⚠️ Nenhum produto está ativo (is_active = 1). Isso pode ser a causa do problema.</div>";
        }
        
        return true;
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Erro ao verificar tabela 'products': " . $e->getMessage() . "</div>";
        return false;
    }
}

// Verificar arquivos de modelo e controller
function testClassFiles() {
    echo "<h3>Verificando arquivos de classe</h3>";
    
    $requiredFiles = [
        APP_PATH . '/core/Database.php',
        APP_PATH . '/models/Model.php',
        APP_PATH . '/models/ProductModel.php',
        APP_PATH . '/controllers/ProductController.php'
    ];
    
    $allFound = true;
    
    foreach ($requiredFiles as $file) {
        if (file_exists($file)) {
            echo "<div class='success'>✅ Arquivo encontrado: " . basename($file) . "</div>";
        } else {
            echo "<div class='error'>❌ Arquivo não encontrado: " . basename($file) . "</div>";
            $allFound = false;
        }
    }
    
    return $allFound;
}

// Verificar consultas SQL no ProductModel
function testProductModels() {
    echo "<h3>Analisando ProductModel.php</h3>";
    
    $file = APP_PATH . '/models/ProductModel.php';
    
    if (!file_exists($file)) {
        echo "<div class='error'>❌ Arquivo ProductModel.php não encontrado.</div>";
        return false;
    }
    
    $content = file_get_contents($file);
    
    // Verificar condições is_active
    $isActiveConditionCount = substr_count($content, "p.is_active = 1");
    $isActiveMainCount = substr_count($content, "is_active = 1");
    
    echo "<div class='info'>📊 Número de ocorrências de 'p.is_active = 1': " . $isActiveConditionCount . "</div>";
    echo "<div class='info'>📊 Número de ocorrências de 'is_active = 1': " . $isActiveMainCount . "</div>";
    
    // Verificar consultas principais
    $hasFeaturedMethod = strpos($content, "function getFeatured") !== false;
    $hasTestedProductsMethod = strpos($content, "function getTestedProducts") !== false;
    $hasCustomProductsMethod = strpos($content, "function getCustomProducts") !== false;
    
    if ($hasFeaturedMethod) {
        echo "<div class='success'>✅ Método getFeatured() encontrado.</div>";
    } else {
        echo "<div class='error'>❌ Método getFeatured() não encontrado.</div>";
    }
    
    if ($hasTestedProductsMethod) {
        echo "<div class='success'>✅ Método getTestedProducts() encontrado.</div>";
    } else {
        echo "<div class='error'>❌ Método getTestedProducts() não encontrado.</div>";
    }
    
    if ($hasCustomProductsMethod) {
        echo "<div class='success'>✅ Método getCustomProducts() encontrado.</div>";
    } else {
        echo "<div class='error'>❌ Método getCustomProducts() não encontrado.</div>";
    }
    
    return true;
}

// Verificar e corrigir todos os problemas encontrados
function checkAndFixProblems($pdo) {
    echo "<h3>Verificando e corrigindo problemas</h3>";
    
    $problems = [];
    $fixes = [];
    
    // Verificar problema de consultas SQL (is_active ausente ou incorreto)
    $productModelFile = APP_PATH . '/models/ProductModel.php';
    
    if (!file_exists($productModelFile)) {
        $problems[] = "Arquivo ProductModel.php não encontrado";
    } else {
        $content = file_get_contents($productModelFile);
        
        // Verificar se todas as consultas aos produtos têm a condição is_active = 1
        $methodsToCheck = ['getFeatured', 'getTestedProducts', 'getCustomProducts', 'getByCategory', 'getCustomizableProducts'];
        
        foreach ($methodsToCheck as $method) {
            if (preg_match('/function\s+' . $method . '\s*\([^)]*\)\s*\{(.*?)}/s', $content, $matches)) {
                $methodContent = $matches[1];
                
                // Verificar se o método contém a condição is_active
                if (strpos($methodContent, "is_active = 1") === false && strpos($methodContent, "p.is_active = 1") === false) {
                    $problems[] = "Método $method não contém condição is_active = 1";
                    
                    // Gerar sugestão de correção
                    $fixes[] = [
                        'method' => $method,
                        'description' => "Adicionar condição is_active = 1 ao método $method"
                    ];
                }
            }
        }
    }
    
    // Verificar Database.php para problemas potenciais
    $databaseFile = APP_PATH . '/core/Database.php';
    
    if (!file_exists($databaseFile)) {
        $problems[] = "Arquivo Database.php não encontrado";
    } else {
        $content = file_get_contents($databaseFile);
        
        // Verificar se o método getInstance() existe
        if (strpos($content, "function getInstance") === false) {
            $problems[] = "Método getInstance() não encontrado em Database.php";
        }
        
        // Verificar se o método select() existe
        if (strpos($content, "function select") === false) {
            $problems[] = "Método select() não encontrado em Database.php";
        }
    }
    
    // Verificar produtos inativos no banco
    if ($pdo) {
        $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active FROM products");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['active'] == 0 && $result['total'] > 0) {
            $problems[] = "Nenhum produto está ativo no banco de dados (is_active = 1)";
            $fixes[] = [
                'method' => 'database',
                'description' => "Ativar produtos no banco de dados",
                'sql' => "UPDATE products SET is_active = 1 WHERE is_active = 0"
            ];
        }
    }
    
    // Exibir problemas encontrados
    if (empty($problems)) {
        echo "<div class='success'>✅ Nenhum problema crítico encontrado que possa estar causando a falha na exibição de produtos.</div>";
    } else {
        echo "<div class='error'>❌ Problemas encontrados que podem estar causando a falha na exibição de produtos:</div>";
        echo "<ul>";
        foreach ($problems as $problem) {
            echo "<li>" . htmlspecialchars($problem) . "</li>";
        }
        echo "</ul>";
        
        // Exibir sugestões de correção
        if (!empty($fixes)) {
            echo "<div class='info'>🔧 Sugestões de correção:</div>";
            echo "<ul>";
            foreach ($fixes as $fix) {
                echo "<li><strong>" . htmlspecialchars($fix['method']) . "</strong>: " . htmlspecialchars($fix['description']);
                
                if (isset($fix['sql'])) {
                    echo "<br><code>" . htmlspecialchars($fix['sql']) . "</code>";
                    
                    // Adicionar botão para aplicar correção SQL
                    echo "<form method='post' action='' style='margin-top: 10px;'>";
                    echo "<input type='hidden' name='fix_action' value='execute_sql'>";
                    echo "<input type='hidden' name='sql' value='" . htmlspecialchars($fix['sql']) . "'>";
                    echo "<button type='submit' class='btn btn-fix'>Aplicar esta correção</button>";
                    echo "</form>";
                }
                
                echo "</li>";
            }
            echo "</ul>";
        }
    }
}

// Executar correções SQL
function executeSQL($sql, $pdo) {
    echo "<h3>Executando correção SQL</h3>";
    
    try {
        $rowCount = $pdo->exec($sql);
        echo "<div class='success'>✅ SQL executado com sucesso. $rowCount registros afetados.</div>";
        echo "<div class='info'>SQL: <code>" . htmlspecialchars($sql) . "</code></div>";
        return true;
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Erro ao executar SQL: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<div class='info'>SQL: <code>" . htmlspecialchars($sql) . "</code></div>";
        return false;
    }
}

// Função principal que executa todos os testes
function runDiagnostics() {
    // Conectar ao banco de dados
    $pdo = testDatabase();
    
    if ($pdo) {
        // Testar tabelas
        testProductsTable($pdo);
        
        // Verificar arquivos de classe
        testClassFiles();
        
        // Testar modelos
        testProductModels();
        
        // Verificar e corrigir problemas
        checkAndFixProblems($pdo);
    } else {
        echo "<div class='error'>❌ Não foi possível continuar os testes sem conexão com o banco de dados.</div>";
    }
    
    // Verificar se há uma ação de correção para executar
    if (isset($_POST['fix_action']) && $_POST['fix_action'] === 'execute_sql' && isset($_POST['sql']) && $pdo) {
        executeSQL($_POST['sql'], $pdo);
    }
}

// HTML de layout
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Erros - Taverna da Impressão 3D</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2, h3, h4 {
            color: #333;
        }
        .success, .error, .warning, .info {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        pre, code {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            display: block;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: .375rem .75rem;
            font-size: .9rem;
            line-height: 1.5;
            border-radius: .25rem;
            cursor: pointer;
        }
        .btn-fix {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-fix:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        .timestamp {
            color: #6c757d;
            font-size: 0.9em;
            margin-bottom: 20px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Diagnóstico de Erros - Taverna da Impressão 3D</h1>
        <div class="timestamp">Executado em: <?= date('d/m/Y H:i:s') ?></div>
        
        <div class="info">ℹ️ <strong>Objetivo:</strong> Esta ferramenta diagnostica e corrige problemas relacionados à exibição de produtos no site.</div>
        
        <?php runDiagnostics(); ?>
        
        <div class="footer">
            <p>Ferramenta de diagnóstico para o projeto Taverna da Impressão 3D</p>
        </div>
    </div>
</body>
</html>
