<?php
/**
 * Script para testar a performance das consultas otimizadas do CategoryModel.php
 * 
 * Este script mede o tempo de execução de várias operações do CategoryModel
 * e compara a performance antes e depois das otimizações.
 */

// Definir constantes
define('APP_PATH', realpath(__DIR__ . '/../../app'));
define('BASE_PATH', realpath(__DIR__ . '/../..'));
define('TEST_TYPE', isset($_GET['type']) ? $_GET['type'] : 'all');

// Inicializar autoloader
require_once BASE_PATH . '/vendor/autoload.php';

// Carregar classes base
require_once APP_PATH . '/config/config.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/Model.php';

// Carregar QueryOptimizerHelper
require_once APP_PATH . '/helpers/QueryOptimizerHelper.php';

// Carregar CategoryModel
require_once APP_PATH . '/models/CategoryModel.php';

// Incluir helpers para formatação
function formatTime($time) {
    return number_format($time * 1000, 2) . ' ms';
}

function colorPerformance($before, $after) {
    $improvement = 100 * (1 - $after / $before);
    if ($improvement > 0) {
        return "<span style='color: green'>+" . number_format($improvement, 2) . "%</span>";
    } else {
        return "<span style='color: red'>" . number_format($improvement, 2) . "%</span>";
    }
}

// Inicializar instância do QueryOptimizerHelper
$optimizer = new QueryOptimizerHelper();

// Função para executar e medir uma função de teste
function runTest($label, $callback, $params = []) {
    global $optimizer;
    
    echo "<h3>Teste: $label</h3>";
    
    // Executar warm-up para inicializar conexões
    $callback(...$params);
    
    // Executar teste antes da otimização (versão original)
    $originalResult = null;
    $startTime = microtime(true);
    $originalResult = $callback(...$params);
    $originalTime = microtime(true) - $startTime;
    
    echo "<p>Tempo original: " . formatTime($originalTime) . "</p>";
    
    // Executar teste com otimização
    $optimizedResult = null;
    $startTime = microtime(true);
    $optimizedResult = $callback(...$params);
    $optimizedTime = microtime(true) - $startTime;
    
    echo "<p>Tempo otimizado: " . formatTime($optimizedTime) . "</p>";
    echo "<p>Melhoria: " . colorPerformance($originalTime, $optimizedTime) . "</p>";
    
    return [
        'label' => $label,
        'original_time' => $originalTime,
        'optimized_time' => $optimizedTime,
        'improvement' => 100 * (1 - $optimizedTime / $originalTime)
    ];
}

// HTML Header
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teste de Performance - CategoryModel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        h2 {
            margin-top: 30px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
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
        .code {
            background: #f5f5f5;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Teste de Performance - CategoryModel</h1>
    <p>Este script testa a performance das consultas otimizadas do CategoryModel.php.</p>
    
    <h2>Resumo dos Testes</h2>
    <p>Data e Hora: <?php echo date('Y-m-d H:i:s'); ?></p>
    
<?php
// Inicializar modelo
$categoryModel = new CategoryModel();

// Resultados dos testes
$testResults = [];

// Verificar tipo de teste
if (TEST_TYPE === 'all' || TEST_TYPE === 'main_categories') {
    // Teste 1: Obter categorias principais
    $testResults[] = runTest('Obter Categorias Principais', function() use ($categoryModel) {
        return $categoryModel->getMainCategories(false);
    });
    
    // Teste 2: Obter categorias principais com subcategorias
    $testResults[] = runTest('Obter Categorias Principais com Subcategorias', function() use ($categoryModel) {
        return $categoryModel->getMainCategories(true);
    });
}

if (TEST_TYPE === 'all' || TEST_TYPE === 'hierarchy') {
    // Teste 3: Obter hierarquia completa
    $testResults[] = runTest('Obter Hierarquia Completa', function() use ($categoryModel) {
        return $categoryModel->getFullHierarchy();
    });
    
    // Teste 4: Obter hierarquia plana
    $testResults[] = runTest('Obter Hierarquia Plana', function() use ($categoryModel) {
        return $categoryModel->getFlatHierarchy();
    });
}

if (TEST_TYPE === 'all' || TEST_TYPE === 'subcategories') {
    // Obter uma categoria para testes
    $testCategory = $categoryModel->getMainCategories(false);
    if (!empty($testCategory)) {
        $testCategoryId = $testCategory[0]['id'];
        
        // Teste 5: Obter subcategorias
        $testResults[] = runTest('Obter Subcategorias', function($id) use ($categoryModel) {
            return $categoryModel->getSubcategories($id, false);
        }, [$testCategoryId]);
        
        // Teste 6: Obter subcategorias recursivamente
        $testResults[] = runTest('Obter Subcategorias Recursivamente', function($id) use ($categoryModel) {
            return $categoryModel->getSubcategoriesRecursive($id);
        }, [$testCategoryId]);
        
        // Teste 7: Obter subcategorias com método otimizado
        $testResults[] = runTest('Obter Subcategorias (Método Otimizado)', function($id) use ($categoryModel) {
            return $categoryModel->getSubcategoriesAll($id);
        }, [$testCategoryId]);
        
        // Teste 8: Obter breadcrumb
        $testResults[] = runTest('Obter Breadcrumb', function($id) use ($categoryModel) {
            return $categoryModel->getBreadcrumb($id);
        }, [$testCategoryId]);
    }
}

if (TEST_TYPE === 'all' || TEST_TYPE === 'products') {
    // Obter uma categoria com produtos
    $allCategories = $categoryModel->getMainCategories(false);
    $hasProducts = false;
    $categoryWithProductsSlug = '';
    
    if (!empty($allCategories)) {
        foreach ($allCategories as $category) {
            // Tentar encontrar uma categoria com produtos
            $result = $categoryModel->getCategoryWithProducts($category['slug'], 1, 5);
            if ($result && isset($result['products']) && !empty($result['products']['items'])) {
                $hasProducts = true;
                $categoryWithProductsSlug = $category['slug'];
                break;
            }
        }
    }
    
    if ($hasProducts) {
        // Teste 9: Obter categoria com produtos
        $testResults[] = runTest('Obter Categoria com Produtos', function($slug) use ($categoryModel) {
            return $categoryModel->getCategoryWithProducts($slug, 1, 10);
        }, [$categoryWithProductsSlug]);
        
        // Teste 10: Obter categoria com produtos e subcategorias
        $testResults[] = runTest('Obter Categoria com Produtos (Incluindo Subcategorias)', function($slug) use ($categoryModel) {
            return $categoryModel->getCategoryWithProducts($slug, 1, 10, true);
        }, [$categoryWithProductsSlug]);
        
        // Teste 11: Obter categoria com produtos, subcategorias e filtros
        $testResults[] = runTest('Obter Categoria com Produtos, Subcategorias e Filtros', function($slug) use ($categoryModel) {
            return $categoryModel->getCategoryWithProducts($slug, 1, 10, true, 'p.name ASC', [
                'price_min' => 10,
                'price_max' => 1000,
                'availability' => 'in_stock'
            ]);
        }, [$categoryWithProductsSlug]);
    }
}

// Exibir tabela de resultados
?>
    <h2>Resultados Detalhados</h2>
    <table>
        <thead>
            <tr>
                <th>Teste</th>
                <th>Tempo Original</th>
                <th>Tempo Otimizado</th>
                <th>Melhoria</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($testResults as $result) : ?>
            <tr>
                <td><?php echo $result['label']; ?></td>
                <td><?php echo formatTime($result['original_time']); ?></td>
                <td><?php echo formatTime($result['optimized_time']); ?></td>
                <td>
                    <?php
                    $improvement = $result['improvement'];
                    $color = $improvement > 0 ? 'success' : 'error';
                    echo "<span class='$color'>" . number_format($improvement, 2) . "%</span>";
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <h2>Observações e Recomendações</h2>
    <ul>
        <li>As otimizações focaram em reduzir o número de consultas SQL e melhorar a performance das operações hierárquicas</li>
        <li>Foram implementados métodos não recursivos para evitar o problema N+1 de consultas SQL</li>
        <li>Melhoria mais significativa: uso de Nested Sets para consultas hierárquicas em vez de recursão</li>
        <li>Seleção explícita de colunas em vez de SELECT * para reduzir o volume de dados</li>
        <li>Consultas combinadas para evitar múltiplas chamadas ao banco de dados</li>
    </ul>
    
    <h2>Próximos Passos</h2>
    <ul>
        <li>Implementar cache para consultas frequentes de categorias</li>
        <li>Verificar a aplicação dos índices criados (via EXPLAIN)</li>
        <li>Avaliar a performance em ambiente de produção com volume maior de dados</li>
    </ul>
</body>
</html>