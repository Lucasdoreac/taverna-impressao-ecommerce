<?php
/**
 * Ferramenta de Diagnóstico para Produtos
 * 
 * Esta ferramenta analisa os produtos no banco de dados e identifica potenciais
 * problemas relacionados à exibição de produtos nos ambientes local e de produção.
 * 
 * Verifica:
 * - Estrutura da tabela products
 * - Presença de produtos no banco
 * - Funcionamento dos métodos de busca de produtos
 * - Consultas SQL executadas
 */

// Habilitar exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir arquivos essenciais
require_once '../app/config/config.php';
require_once '../app/core/Database.php';
require_once '../app/models/Model.php';
require_once '../app/models/ProductModel.php';

echo "<h1>Diagnóstico de Produtos</h1>";
echo "<p>Ambiente: " . ENVIRONMENT . "</p>";
echo "<p>URL do Site: " . (defined('SITE_URL') ? SITE_URL : 'Não definida') . "</p>";

// Inicializar ProductModel
$productModel = new ProductModel();

// Função para exibir produtos
function displayProducts($products, $title) {
    echo "<h2>$title</h2>";
    
    if (empty($products)) {
        echo "<p style='color: red; font-weight: bold;'>Nenhum produto encontrado</p>";
    } else {
        echo "<p>Total: <strong>" . count($products) . "</strong> produtos</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f2f2f2;'><th>ID</th><th>Nome</th><th>Preço</th><th>Estoque</th><th>Is Featured</th><th>Is Tested</th><th>Is Customizable</th><th>Is Active</th></tr>";
        
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . $product['id'] . "</td>";
            echo "<td>" . $product['name'] . "</td>";
            echo "<td>" . $product['price'] . "</td>";
            echo "<td>" . $product['stock'] . "</td>";
            echo "<td>" . (isset($product['is_featured']) ? $product['is_featured'] : 'N/A') . "</td>";
            echo "<td>" . (isset($product['is_tested']) ? $product['is_tested'] : 'N/A') . "</td>";
            echo "<td>" . (isset($product['is_customizable']) ? $product['is_customizable'] : 'N/A') . "</td>";
            echo "<td>" . (isset($product['is_active']) ? $product['is_active'] : 'N/A') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
}

// Verificar produtos no banco de dados
try {
    // Obter instância do banco de dados
    $db = $productModel->getDb();
    
    // Verificar se a tabela products existe
    $tableCheck = $db->select("SHOW TABLES LIKE 'products'");
    if (empty($tableCheck)) {
        echo "<h2 style='color: red;'>ERRO: Tabela 'products' não encontrada!</h2>";
        exit;
    }
    
    echo "<hr>";
    
    // Obter todos os produtos (consulta direta)
    $allProducts = $db->select("SELECT * FROM products");
    displayProducts($allProducts, "Todos os Produtos (consulta direta)");
    
    // Verificar produtos por método do ProductModel
    displayProducts($productModel->getFeatured(50), "Produtos em Destaque (getFeatured)");
    displayProducts($productModel->getTestedProducts(50), "Produtos Testados (getTestedProducts)");
    displayProducts($productModel->getCustomProducts(50), "Produtos Sob Encomenda (getCustomProducts)");
    displayProducts($productModel->getCustomizableProducts(50), "Produtos Personalizáveis (getCustomizableProducts)");
    
    echo "<hr>";
    
    // Verificar estrutura da tabela
    echo "<h2>Estrutura da tabela 'products'</h2>";
    $tableInfo = $db->select("DESCRIBE products");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f2f2f2;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    
    // Variáveis para verificar a presença de campos críticos
    $hasIsFeatured = false;
    $hasIsTested = false;
    $hasIsCustomizable = false;
    $hasIsActive = false;
    
    foreach ($tableInfo as $column) {
        // Verificar campos críticos
        if ($column['Field'] == 'is_featured') $hasIsFeatured = true;
        if ($column['Field'] == 'is_tested') $hasIsTested = true;
        if ($column['Field'] == 'is_customizable') $hasIsCustomizable = true;
        if ($column['Field'] == 'is_active') $hasIsActive = true;
        
        echo "<tr>";
        foreach ($column as $key => $value) {
            echo "<td>" . $value . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Verificar e destacar problemas com campos críticos
    echo "<h2>Verificação de Campos Críticos</h2>";
    echo "<ul>";
    echo "<li>is_featured: " . ($hasIsFeatured ? "<span style='color: green;'>Presente</span>" : "<span style='color: red;'>AUSENTE!</span>") . "</li>";
    echo "<li>is_tested: " . ($hasIsTested ? "<span style='color: green;'>Presente</span>" : "<span style='color: red;'>AUSENTE!</span>") . "</li>";
    echo "<li>is_customizable: " . ($hasIsCustomizable ? "<span style='color: green;'>Presente</span>" : "<span style='color: red;'>AUSENTE!</span>") . "</li>";
    echo "<li>is_active: " . ($hasIsActive ? "<span style='color: green;'>Presente</span>" : "<span style='color: red;'>AUSENTE!</span>") . "</li>";
    echo "</ul>";
    
    // Se algum campo crítico estiver faltando, mostrar mensagem de aviso
    if (!$hasIsFeatured || !$hasIsTested || !$hasIsCustomizable || !$hasIsActive) {
        echo "<div style='background-color: #ffe0e0; padding: 10px; border: 1px solid red;'>";
        echo "<h3 style='color: red;'>ATENÇÃO: Campos críticos ausentes!</h3>";
        echo "<p>Os métodos de busca de produtos podem estar filtrando por campos que não existem neste ambiente.</p>";
        echo "<p>Recomendação: Modifique os métodos em ProductModel.php para não depender de campos ausentes.</p>";
        echo "</div>";
    }
    
    // Verificar valores dos campos críticos (se existirem)
    if ($hasIsFeatured || $hasIsTested || $hasIsCustomizable) {
        echo "<h2>Análise de Valores dos Campos Críticos</h2>";
        $flagsAnalysis = $db->select("SELECT 
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as total_active,
            " . ($hasIsFeatured ? "SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as total_featured," : "") . "
            " . ($hasIsTested ? "SUM(CASE WHEN is_tested = 1 THEN 1 ELSE 0 END) as total_tested," : "") . "
            " . ($hasIsCustomizable ? "SUM(CASE WHEN is_customizable = 1 THEN 1 ELSE 0 END) as total_customizable," : "") . "
            COUNT(*) as total
            FROM products");
        
        if (!empty($flagsAnalysis)) {
            $analysis = $flagsAnalysis[0];
            echo "<ul>";
            echo "<li>Total de produtos: <strong>" . $analysis['total'] . "</strong></li>";
            echo "<li>Produtos ativos: <strong>" . $analysis['total_active'] . "</strong></li>";
            if ($hasIsFeatured) echo "<li>Produtos em destaque: <strong>" . $analysis['total_featured'] . "</strong></li>";
            if ($hasIsTested) echo "<li>Produtos testados: <strong>" . $analysis['total_tested'] . "</strong></li>";
            if ($hasIsCustomizable) echo "<li>Produtos personalizáveis: <strong>" . $analysis['total_customizable'] . "</strong></li>";
            echo "</ul>";
            
            // Verificar se existem produtos com flags setadas
            $hasFeaturedProducts = $hasIsFeatured && $analysis['total_featured'] > 0;
            $hasTestedProducts = $hasIsTested && $analysis['total_tested'] > 0;
            $hasCustomizableProducts = $hasIsCustomizable && $analysis['total_customizable'] > 0;
            
            // Mostrar alerta se não houver produtos com flags setadas
            if ($hasIsFeatured && !$hasFeaturedProducts) {
                echo "<div style='background-color: #ffe0e0; padding: 10px; border: 1px solid red;'>";
                echo "<p><strong>ALERTA:</strong> Nenhum produto está marcado como destaque (is_featured = 1)!</p>";
                echo "<p>Se o método getFeatured() filtrar por is_featured = 1, nenhum produto será exibido.</p>";
                echo "</div>";
            }
            
            if ($hasIsTested && !$hasTestedProducts) {
                echo "<div style='background-color: #ffe0e0; padding: 10px; border: 1px solid red;'>";
                echo "<p><strong>ALERTA:</strong> Nenhum produto está marcado como testado (is_tested = 1)!</p>";
                echo "<p>Se o método getTestedProducts() filtrar por is_tested = 1, nenhum produto será exibido.</p>";
                echo "</div>";
            }
            
            if ($hasIsCustomizable && !$hasCustomizableProducts) {
                echo "<div style='background-color: #ffe0e0; padding: 10px; border: 1px solid red;'>";
                echo "<p><strong>ALERTA:</strong> Nenhum produto está marcado como personalizável (is_customizable = 1)!</p>";
                echo "<p>Se o método getCustomizableProducts() filtrar por is_customizable = 1, nenhum produto será exibido.</p>";
                echo "</div>";
            }
        }
    }
    
    echo "<hr>";
    
    // Recomendações para solução de problemas
    echo "<h2>Recomendações</h2>";
    echo "<ol>";
    echo "<li>Verifique se os campos críticos (is_featured, is_tested, is_customizable) existem nos dois ambientes.</li>";
    echo "<li>Modifique os métodos em ProductModel.php para não depender de campos que possam não existir.</li>";
    echo "<li>Use ORDER BY para priorizar produtos com flags setadas, em vez de filtrar por elas.</li>";
    echo "<li>Adicione logs de diagnóstico aos métodos para facilitar a identificação de problemas.</li>";
    echo "<li>Implemente consultas SQL mais tolerantes que funcionem mesmo quando campos não existem.</li>";
    echo "</ol>";
    
    echo "<h2>Exemplo de Correção (para getFeatured)</h2>";
    echo "<pre style='background-color: #f8f8f8; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars('
public function getFeatured($limit = 8) {
    try {
        // Adicionar log de diagnóstico
        error_log("ProductModel::getFeatured - Iniciando busca de produtos em destaque (limit: $limit)");
        
        // CORREÇÃO: Remover filtro is_featured para mostrar todos os produtos quando não houver destacados
        $sql = "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.stock, 
                       pi.image, 
                       CASE WHEN p.stock > 0 THEN \'Pronta Entrega\' ELSE \'Sob Encomenda\' END as availability
                FROM {$this->table} p
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE p.is_active = 1
                ORDER BY p.created_at DESC
                LIMIT :limit";
        
        $result = $this->db()->select($sql, [\'limit\' => $limit]);
        
        // Adicionar log com o resultado
        error_log("ProductModel::getFeatured - Encontrados " . count($result) . " produtos em destaque");
        
        return $result;
    } catch (Exception $e) {
        error_log("Erro ao buscar produtos em destaque: " . $e->getMessage());
        return [];
    }
}');
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Erro</h2>";
    echo "<p>Ocorreu um erro: <strong>" . $e->getMessage() . "</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>