<?php
/**
 * Ferramenta de diagnóstico para problemas com produtos
 * 
 * Este script verifica conexões com banco de dados, tabelas e consultas
 * para ajudar a identificar problemas com a exibição de produtos.
 */

// Definir exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Carregar configurações
require_once '../app/config/config.php';

// Testar conexão com banco de dados
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
            'category_id', 'is_active', 'is_tested', 'stock', 
            'filament_type'
        ];
        
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (!empty($missingColumns)) {
            echo "<div class='error'>❌ Colunas obrigatórias ausentes na tabela 'products': " . implode(', ', $missingColumns) . "</div>";
            return false;
        }
        
        echo "<div class='success'>✅ Estrutura da tabela 'products' verificada com sucesso.</div>";
        
        // Contar produtos
        $stmt = $pdo->query("SELECT COUNT(*) FROM products");
        $count = $stmt->fetchColumn();
        
        echo "<div class='info'>📊 Total de produtos no banco: " . $count . "</div>";
        
        // Verificar produtos ativos
        $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
        $activeCount = $stmt->fetchColumn();
        
        echo "<div class='info'>📊 Total de produtos ativos: " . $activeCount . "</div>";
        
        if ($activeCount === 0) {
            echo "<div class='warning'>⚠️ Não há produtos ativos no banco de dados.</div>";
        }
        
        return true;
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Erro ao verificar tabela 'products': " . $e->getMessage() . "</div>";
        return false;
    }
}

// Testar consulta para buscar produtos
function testProductQueries($pdo) {
    echo "<h3>Testando consultas de produtos</h3>";
    
    try {
        // 1. Teste básico - buscar todos os produtos ativos
        $stmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 LIMIT 5");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($products)) {
            echo "<div class='warning'>⚠️ Não foram encontrados produtos ativos.</div>";
        } else {
            echo "<div class='success'>✅ Consulta básica de produtos funcionou. Encontrados " . count($products) . " produtos ativos.</div>";
            
            // Exibir primeiro produto como exemplo
            echo "<div class='info'>📝 Exemplo do primeiro produto: ";
            echo "<pre>" . print_r($products[0], true) . "</pre></div>";
        }
        
        // 2. Teste de JOIN com imagens
        $stmt = $pdo->query("
            SELECT p.id, p.name, p.slug, p.price, pi.image 
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
            WHERE p.is_active = 1
            LIMIT 5
        ");
        
        $productsWithImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($productsWithImages)) {
            echo "<div class='warning'>⚠️ Não foram encontrados produtos com JOIN de imagens.</div>";
        } else {
            echo "<div class='success'>✅ Consulta de produtos com JOIN de imagens funcionou.</div>";
            
            // Verificar se algum produto tem imagem
            $hasImage = false;
            foreach ($productsWithImages as $product) {
                if (!empty($product['image'])) {
                    $hasImage = true;
                    break;
                }
            }
            
            if (!$hasImage) {
                echo "<div class='warning'>⚠️ Nenhum dos produtos possui imagem principal definida.</div>";
            }
        }
        
        // 3. Teste de busca por categoria
        if (!empty($products) && isset($products[0]['category_id'])) {
            $categoryId = $products[0]['category_id'];
            
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM products 
                WHERE category_id = ? AND is_active = 1
            ");
            $stmt->execute([$categoryId]);
            $categoryProductCount = $stmt->fetchColumn();
            
            echo "<div class='info'>📊 Produtos na categoria ID " . $categoryId . ": " . $categoryProductCount . "</div>";
        }
        
        // 4. Teste de FilamentModel
        echo "<h4>Testando tabela de filamentos</h4>";
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'filament_colors'");
        if ($stmt->rowCount() === 0) {
            echo "<div class='error'>❌ Tabela 'filament_colors' não encontrada.</div>";
            echo "<div class='info'>ℹ️ Esta tabela é necessária para o funcionamento da classe FilamentModel.</div>";
        } else {
            echo "<div class='success'>✅ Tabela 'filament_colors' encontrada.</div>";
            
            // Verificar dados na tabela
            $stmt = $pdo->query("SELECT COUNT(*) FROM filament_colors");
            $filamentCount = $stmt->fetchColumn();
            
            echo "<div class='info'>📊 Total de cores de filamento: " . $filamentCount . "</div>";
            
            if ($filamentCount === 0) {
                echo "<div class='warning'>⚠️ Não há cores de filamento cadastradas.</div>";
                
                // Sugerir SQL para criar dados básicos
                echo "<div class='info'>ℹ️ Sugestão de SQL para inserir cores básicas:";
                echo "<pre>
INSERT INTO filament_colors (name, hex_code, filament_type, is_active, display_order) VALUES
('Preto', '#000000', 'PLA', 1, 1),
('Branco', '#FFFFFF', 'PLA', 1, 2),
('Vermelho', '#FF0000', 'PLA', 1, 3),
('Azul', '#0000FF', 'PLA', 1, 4),
('Verde', '#00FF00', 'PLA', 1, 5);
                </pre></div>";
            } else {
                // Mostrar exemplo de cores
                $stmt = $pdo->query("SELECT * FROM filament_colors LIMIT 3");
                $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<div class='info'>📝 Exemplo de cores de filamento: ";
                echo "<pre>" . print_r($colors, true) . "</pre></div>";
            }
        }
        
        // 5. Teste de script personalizado para diagnóstico completo
        echo "<h4>Script de diagnóstico completo</h4>";
        echo "<div class='info'>ℹ️ Execute este SQL para um diagnóstico completo. Cole no phpMyAdmin ou ferramenta similar:";
        echo "<pre>
-- Diagnóstico de tabelas principais
SELECT 'products' as table_name, COUNT(*) as total, 
       SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
FROM products
UNION
SELECT 'product_images' as table_name, COUNT(*) as total, 
       SUM(CASE WHEN is_main = 1 THEN 1 ELSE 0 END) as main_images
FROM product_images
UNION
SELECT 'filament_colors' as table_name, COUNT(*) as total, 
       SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
FROM filament_colors
UNION
SELECT 'categories' as table_name, COUNT(*) as total, 
       SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
FROM categories;

-- Verificar produtos sem categoria ou com categoria inválida
SELECT id, name, category_id 
FROM products 
WHERE category_id IS NULL OR category_id = 0 
   OR category_id NOT IN (SELECT id FROM categories);

-- Verificar produtos sem imagens
SELECT p.id, p.name, COUNT(pi.id) as image_count
FROM products p
LEFT JOIN product_images pi ON p.id = pi.product_id
WHERE p.is_active = 1
GROUP BY p.id
HAVING image_count = 0
LIMIT 10;
        </pre></div>";
        
        return true;
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Erro ao testar consultas de produtos: " . $e->getMessage() . "</div>";
        return false;
    }
}

// Função para testar a classe FilamentModel diretamente
function testFilamentModel() {
    echo "<h3>Testando classe FilamentModel</h3>";
    
    try {
        // Verificar se a classe existe
        if (!class_exists('FilamentModel')) {
            echo "<div class='error'>❌ Classe FilamentModel não existe.</div>";
            return false;
        }
        
        echo "<div class='success'>✅ Classe FilamentModel encontrada.</div>";
        
        // Verificar se a classe estende Model
        $reflector = new ReflectionClass('FilamentModel');
        if (!$reflector->isSubclassOf('Model')) {
            echo "<div class='error'>❌ FilamentModel não estende a classe Model.</div>";
            return false;
        }
        
        echo "<div class='success'>✅ FilamentModel estende corretamente a classe Model.</div>";
        
        // Testar instanciação
        try {
            $filamentModel = new FilamentModel();
            echo "<div class='success'>✅ FilamentModel instanciado com sucesso.</div>";
            
            // Testar método getColors
            try {
                $colors = $filamentModel->getColors('PLA');
                echo "<div class='success'>✅ Método getColors() executado com sucesso.</div>";
                echo "<div class='info'>📊 Total de cores retornadas: " . count($colors) . "</div>";
                
                if (empty($colors)) {
                    echo "<div class='warning'>⚠️ Não foram encontradas cores para o tipo 'PLA'.</div>";
                } else {
                    echo "<div class='info'>📝 Exemplo da primeira cor: ";
                    echo "<pre>" . print_r($colors[0], true) . "</pre></div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>❌ Erro ao executar método getColors(): " . $e->getMessage() . "</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao instanciar FilamentModel: " . $e->getMessage() . "</div>";
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro ao testar classe FilamentModel: " . $e->getMessage() . "</div>";
        return false;
    }
}

// Função para testar classe ProductModel
function testProductModel() {
    echo "<h3>Testando classe ProductModel</h3>";
    
    try {
        // Verificar se a classe existe
        if (!class_exists('ProductModel')) {
            echo "<div class='error'>❌ Classe ProductModel não existe ou não foi carregada.</div>";
            
            // Verificar se o arquivo existe
            $productModelPath = APP_PATH . '/models/ProductModel.php';
            if (!file_exists($productModelPath)) {
                echo "<div class='error'>❌ Arquivo ProductModel.php não encontrado em: " . $productModelPath . "</div>";
            } else {
                echo "<div class='success'>✅ Arquivo ProductModel.php existe, mas a classe não está sendo carregada corretamente.</div>";
            }
            
            return false;
        }
        
        echo "<div class='success'>✅ Classe ProductModel encontrada.</div>";
        
        // Testar instanciação
        try {
            $productModel = new ProductModel();
            echo "<div class='success'>✅ ProductModel instanciado com sucesso.</div>";
            
            // Testar métodos principais
            try {
                // 1. getFeatured
                $featured = $productModel->getFeatured(5);
                echo "<div class='success'>✅ Método getFeatured() executado.</div>";
                echo "<div class='info'>📊 Produtos em destaque encontrados: " . count($featured) . "</div>";
                
                // 2. getTestedProducts
                $tested = $productModel->getTestedProducts(5);
                echo "<div class='success'>✅ Método getTestedProducts() executado.</div>";
                echo "<div class='info'>📊 Produtos testados encontrados: " . count($tested) . "</div>";
                
                // 3. getCustomizableProducts
                $customizable = $productModel->getCustomizableProducts(5);
                echo "<div class='success'>✅ Método getCustomizableProducts() executado.</div>";
                echo "<div class='info'>📊 Produtos personalizáveis encontrados: " . count($customizable) . "</div>";
                
                // Verificar se getBySlug funciona
                if (!empty($featured)) {
                    $firstProduct = $featured[0];
                    $slug = $firstProduct['slug'];
                    
                    echo "<div class='info'>ℹ️ Testando getBySlug() com o slug: " . $slug . "</div>";
                    
                    $product = $productModel->getBySlug($slug);
                    if ($product) {
                        echo "<div class='success'>✅ Método getBySlug() funcionou corretamente.</div>";
                    } else {
                        echo "<div class='error'>❌ Método getBySlug() falhou ao recuperar o produto com slug: " . $slug . "</div>";
                    }
                }
            } catch (Exception $e) {
                echo "<div class='error'>❌ Erro ao testar métodos do ProductModel: " . $e->getMessage() . "</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao instanciar ProductModel: " . $e->getMessage() . "</div>";
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro ao testar classe ProductModel: " . $e->getMessage() . "</div>";
        return false;
    }
}

// Verificar autoloader e classes necessárias
function testAutoloader() {
    echo "<h3>Verificando sistema de autoload</h3>";
    
    try {
        // Carregar classes essenciais
        require_once '../app/core/Database.php';
        require_once '../app/models/Model.php';
        require_once '../app/models/ProductModel.php';
        require_once '../app/models/FilamentModel.php';
        
        echo "<div class='success'>✅ Arquivos de classes essenciais carregados manualmente com sucesso.</div>";
        
        // Verificar classes
        $requiredClasses = ['Database', 'Model', 'ProductModel', 'FilamentModel'];
        
        foreach ($requiredClasses as $class) {
            if (class_exists($class)) {
                echo "<div class='success'>✅ Classe $class encontrada.</div>";
            } else {
                echo "<div class='error'>❌ Classe $class não encontrada após carregamento manual.</div>";
            }
        }
        
        return true;
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro ao verificar autoloader: " . $e->getMessage() . "</div>";
        return false;
    }
}

// Exibir página HTML com resultados
function renderPage() {
    $title = "Diagnóstico de Produtos - Taverna da Impressão";
    
    // Iniciar HTML
    echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>$title</title>
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
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
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
    <div class='container'>
        <h1>$title</h1>
        <div class='timestamp'>Executado em: " . date('d/m/Y H:i:s') . "</div>
        
        <div class='info'>ℹ️ <strong>Atenção:</strong> Esta ferramenta ajuda a identificar problemas com a exibição de produtos.
        Todas as informações são exibidas apenas nesta página e não são salvas ou compartilhadas.</div>";
    
    // Executar os testes
    try {
        testAutoloader();
        
        $pdo = testDatabase();
        if ($pdo) {
            testProductsTable($pdo);
            testProductQueries($pdo);
        }
        
        testFilamentModel();
        testProductModel();
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro fatal durante a execução dos testes: " . $e->getMessage() . "</div>";
    }
    
    // Recomendações finais
    echo "<h3>Recomendações e próximos passos</h3>
        <ol>
            <li>Verifique se há produtos ativos no banco de dados</li>
            <li>Confirme se todas as tabelas relacionadas existem e possuem dados</li>
            <li>Verifique se as classes do modelo (ProductModel, FilamentModel) estão funcionando corretamente</li>
            <li>Verifique os logs de erro do PHP para identificar problemas não detectados aqui</li>
            <li>Se necessário, crie dados de exemplo para testar a exibição de produtos</li>
        </ol>
        
        <div class='footer'>
            <p>Ferramenta de diagnóstico criada para Taverna da Impressão 3D</p>
        </div>
    </div>
</body>
</html>";
}

// Executar a página de diagnóstico
renderPage();
