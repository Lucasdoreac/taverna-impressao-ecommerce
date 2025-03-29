<?php
// Debug para ProductController
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inicializar sessão
session_start();

// Carregar configurações
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/Database.php';
require_once __DIR__ . '/../app/models/Model.php';
require_once __DIR__ . '/../app/models/ProductModel.php';

// Testar método getBySlug
try {
    $slug = 'ficha-personagem-dd-5e-basica'; // Slug conhecido de um produto
    $productModel = new ProductModel();
    
    echo "<h2>Testando busca de produto por slug</h2>";
    $product = $productModel->getBySlug($slug);
    
    if ($product) {
        echo "<p style='color:green'>✓ Produto encontrado: " . $product['name'] . "</p>";
        echo "<pre>";
        print_r($product);
        echo "</pre>";
    } else {
        echo "<p style='color:red'>✗ Produto não encontrado com o slug: {$slug}</p>";
        
        // Verificar todos os produtos
        echo "<h3>Lista de produtos disponíveis:</h3>";
        $allProducts = $productModel->all();
        if (!empty($allProducts)) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Nome</th><th>Slug</th><th>Categoria</th></tr>";
            foreach ($allProducts as $p) {
                echo "<tr>";
                echo "<td>" . $p['id'] . "</td>";
                echo "<td>" . $p['name'] . "</td>";
                echo "<td>" . $p['slug'] . "</td>";
                echo "<td>" . $p['category_id'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:red'>✗ Nenhum produto encontrado no banco de dados.</p>";
        }
    }
    
    // Testar o método getRelated (frequente causa de erros)
    echo "<h2>Testando método getRelated</h2>";
    if ($product) {
        try {
            $related = $productModel->getRelated($product['id'], $product['category_id']);
            echo "<p style='color:green'>✓ Produtos relacionados encontrados: " . count($related) . "</p>";
            if (!empty($related)) {
                echo "<ul>";
                foreach ($related as $rel) {
                    echo "<li>" . $rel['name'] . " (ID: " . $rel['id'] . ")</li>";
                }
                echo "</ul>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>✗ Erro ao buscar produtos relacionados: " . $e->getMessage() . "</p>";
            echo "<p>Stack trace:</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }
    
    // Testar o SQL direto
    echo "<h2>Testando SQL direto</h2>";
    try {
        $db = Database::getInstance();
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.slug = :slug AND p.is_active = 1";
        
        $result = $db->select($sql, ['slug' => $slug]);
        
        if (!empty($result)) {
            echo "<p style='color:green'>✓ SQL direto funciona corretamente</p>";
        } else {
            echo "<p style='color:red'>✗ SQL direto não retornou resultados</p>";
        }
        
        // Verificar SQL para imagens
        echo "<h3>Testando SQL para imagens</h3>";
        if (!empty($result)) {
            $productId = $result[0]['id'];
            $sql = "SELECT * FROM product_images WHERE product_id = :id ORDER BY is_main DESC, display_order ASC";
            $images = $db->select($sql, ['id' => $productId]);
            
            if (!empty($images)) {
                echo "<p style='color:green'>✓ Imagens encontradas: " . count($images) . "</p>";
            } else {
                echo "<p style='color:orange'>⚠ Nenhuma imagem encontrada para o produto.</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Erro ao executar SQL direto: " . $e->getMessage() . "</p>";
        echo "<p>Stack trace:</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    // Testar estrutura da tabela produtos
    echo "<h2>Verificando estrutura da tabela products</h2>";
    try {
        $db = Database::getInstance();
        $columns = $db->select("SHOW COLUMNS FROM products");
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ Erro ao verificar estrutura da tabela: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color:red'>Erro ao inicializar teste:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
