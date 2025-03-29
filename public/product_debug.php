<?php
// Arquivo de diagnóstico para produtos
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cabeçalho HTML para melhor visualização
echo '<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico de Produtos - TAVERNA DA IMPRESSÃO</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2, h3 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>Diagnóstico de Produtos - TAVERNA DA IMPRESSÃO</h1>';

// Carregar configurações
require_once '../app/config/config.php';
require_once '../app/helpers/Database.php';

echo '<div class="card">
    <h2>Configuração e Ambiente</h2>
    <p>Ambiente: <strong>' . ENVIRONMENT . '</strong></p>
    <p>Exibição de erros: <strong>' . (DISPLAY_ERRORS ? 'Ativada' : 'Desativada') . '</strong></p>
    <p>Base URL: <strong>' . BASE_URL . '</strong></p>
</div>';

// Verificar conexão com banco de dados
echo '<div class="card">
    <h2>Conexão com Banco de Dados</h2>';

try {
    $db = Database::getInstance();
    echo '<p class="success">✓ Conexão com banco de dados estabelecida com sucesso.</p>';
    
    // Verificar tabela products
    try {
        $result = $db->select("SHOW COLUMNS FROM products");
        echo '<p class="success">✓ Tabela products existe com ' . count($result) . ' colunas.</p>';
        
        echo '<h3>Estrutura da tabela products:</h3>';
        echo '<pre>';
        print_r($result);
        echo '</pre>';
        
        // Verificar tabela product_images
        $resultImages = $db->select("SHOW COLUMNS FROM product_images");
        echo '<p class="success">✓ Tabela product_images existe com ' . count($resultImages) . ' colunas.</p>';
        
        echo '<h3>Estrutura da tabela product_images:</h3>';
        echo '<pre>';
        print_r($resultImages);
        echo '</pre>';
        
        // Listar produtos
        $products = $db->select("SELECT * FROM products LIMIT 5");
        echo '<h3>Produtos disponíveis (primeiros 5):</h3>';
        
        if (count($products) > 0) {
            echo '<pre>';
            print_r($products);
            echo '</pre>';
            
            // Verificar relação com imagens
            $firstProductId = $products[0]['id'];
            $images = $db->select("SELECT * FROM product_images WHERE product_id = :id", ['id' => $firstProductId]);
            
            echo '<h3>Imagens para o produto ID ' . $firstProductId . ':</h3>';
            if (count($images) > 0) {
                echo '<pre>';
                print_r($images);
                echo '</pre>';
            } else {
                echo '<p class="warning">⚠ Nenhuma imagem encontrada para o produto ID ' . $firstProductId . '.</p>';
            }
            
        } else {
            echo '<p class="warning">⚠ Nenhum produto encontrado no banco de dados.</p>';
        }
        
    } catch (Exception $e) {
        echo '<p class="error">✗ Erro ao verificar tabelas: ' . $e->getMessage() . '</p>';
    }
    
} catch (Exception $e) {
    echo '<p class="error">✗ Erro na conexão com banco de dados: ' . $e->getMessage() . '</p>';
}

echo '</div>';

// Testar ProductModel
echo '<div class="card">
    <h2>Teste do ProductModel</h2>';

try {
    require_once '../app/models/Model.php';
    require_once '../app/models/ProductModel.php';
    
    echo '<p class="success">✓ Arquivos do modelo carregados com sucesso.</p>';
    
    try {
        $productModel = new ProductModel();
        echo '<p class="success">✓ ProductModel instanciado com sucesso.</p>';
        
        // Testar getFeatured
        try {
            $featuredProducts = $productModel->getFeatured(3);
            echo '<p class="success">✓ Método getFeatured executado com sucesso.</p>';
            echo '<h3>Produtos em destaque (primeiros 3):</h3>';
            echo '<pre>';
            print_r($featuredProducts);
            echo '</pre>';
        } catch (Exception $e) {
            echo '<p class="error">✗ Erro ao executar getFeatured: ' . $e->getMessage() . '</p>';
            echo '<pre>' . $e->getTraceAsString() . '</pre>';
        }
        
        // Obter um produto para testar getBySlug
        $products = $db->select("SELECT slug FROM products LIMIT 1");
        
        if (count($products) > 0) {
            $testSlug = $products[0]['slug'];
            echo '<h3>Testando getBySlug para "' . $testSlug . '":</h3>';
            
            try {
                // Acompanhar execução passo a passo
                echo '<p>Iniciando execução de getBySlug...</p>';
                
                // Testar a query SQL diretamente
                try {
                    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                            FROM products p
                            LEFT JOIN categories c ON p.category_id = c.id
                            WHERE p.slug = :slug AND p.is_active = 1";
                    
                    $directResult = $db->select($sql, ['slug' => $testSlug]);
                    echo '<p class="success">✓ Query SQL direta executada com sucesso.</p>';
                    echo '<pre>';
                    print_r($directResult);
                    echo '</pre>';
                    
                    if (empty($directResult)) {
                        echo '<p class="warning">⚠ A query SQL não retornou resultados. Verificando produto sem filtro de is_active...</p>';
                        
                        $sql2 = "SELECT p.*, c.name as category_name, c.slug as category_slug
                                FROM products p
                                LEFT JOIN categories c ON p.category_id = c.id
                                WHERE p.slug = :slug";
                        
                        $directResult2 = $db->select($sql2, ['slug' => $testSlug]);
                        
                        if (!empty($directResult2)) {
                            echo '<p class="warning">⚠ O produto existe mas está marcado como inativo (is_active = 0).</p>';
                            echo '<pre>';
                            print_r($directResult2);
                            echo '</pre>';
                        } else {
                            echo '<p class="error">✗ O produto não foi encontrado mesmo sem o filtro is_active.</p>';
                        }
                    }
                } catch (Exception $e) {
                    echo '<p class="error">✗ Erro na execução direta da query: ' . $e->getMessage() . '</p>';
                }
                
                // Agora executar o método do modelo
                $product = $productModel->getBySlug($testSlug);
                
                if ($product) {
                    echo '<p class="success">✓ Método getBySlug executado com sucesso.</p>';
                    echo '<h3>Detalhes do produto:</h3>';
                    echo '<pre>';
                    // Mostrar apenas campos principais para não sobrecarregar
                    echo "ID: " . $product['id'] . "\n";
                    echo "Nome: " . $product['name'] . "\n";
                    echo "Slug: " . $product['slug'] . "\n";
                    echo "Categoria: " . $product['category_name'] . "\n";
                    echo "Preço: " . $product['price'] . "\n";
                    
                    if (isset($product['images'])) {
                        echo "Número de imagens: " . count($product['images']) . "\n";
                    } else {
                        echo "Nenhuma imagem associada.\n";
                    }
                    
                    if (isset($product['customization_options'])) {
                        echo "Número de opções de personalização: " . count($product['customization_options']) . "\n";
                    }
                    echo '</pre>';
                } else {
                    echo '<p class="error">✗ O método getBySlug não retornou nenhum produto. Possíveis razões:</p>';
                    echo '<ul>';
                    echo '<li>O produto não existe no banco de dados</li>';
                    echo '<li>O produto está marcado como inativo (is_active = 0)</li>';
                    echo '<li>Há um problema na consulta SQL ou no join com categorias</li>';
                    echo '</ul>';
                }
            } catch (Exception $e) {
                echo '<p class="error">✗ Erro ao executar getBySlug: ' . $e->getMessage() . '</p>';
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
            }
        } else {
            echo '<p class="warning">⚠ Não foi possível testar getBySlug pois não há produtos disponíveis.</p>';
        }
        
    } catch (Exception $e) {
        echo '<p class="error">✗ Erro ao instanciar ProductModel: ' . $e->getMessage() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
    
} catch (Exception $e) {
    echo '<p class="error">✗ Erro ao carregar arquivos do modelo: ' . $e->getMessage() . '</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo '</div>';

// Testar Controller
echo '<div class="card">
    <h2>Teste do ProductController</h2>';

try {
    require_once '../app/models/CategoryModel.php';
    require_once '../app/controllers/ProductController.php';
    echo '<p class="success">✓ Arquivo do controller carregado com sucesso.</p>';
    
    try {
        $productController = new ProductController();
        echo '<p class="success">✓ ProductController instanciado com sucesso.</p>';
        
        // Não podemos testar os métodos diretamente pois eles renderizam views
        echo '<p>Note: Os métodos do controller não podem ser testados diretamente aqui pois eles renderizam views completas.</p>';
        
    } catch (Exception $e) {
        echo '<p class="error">✗ Erro ao instanciar ProductController: ' . $e->getMessage() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
    
} catch (Exception $e) {
    echo '<p class="error">✗ Erro ao carregar arquivo do controller: ' . $e->getMessage() . '</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo '</div>';

// Verificar template de produto
echo '<div class="card">
    <h2>Verificação da View de Produto</h2>';

$viewPath = VIEWS_PATH . '/product.php';
if (file_exists($viewPath)) {
    echo '<p class="success">✓ View de produto encontrada em: ' . $viewPath . '</p>';
    
    // Verificar conteúdo básico da view
    $viewContent = file_get_contents($viewPath);
    echo '<p>Primeiras 300 caracteres da view:</p>';
    echo '<pre>' . htmlspecialchars(substr($viewContent, 0, 300)) . '...</pre>';
    
} else {
    echo '<p class="error">✗ View de produto não encontrada em: ' . $viewPath . '</p>';
}

echo '</div>

</body>
</html>';
