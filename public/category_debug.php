<?php
// Arquivo de diagnóstico para categorias
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cabeçalho HTML para melhor visualização
echo '<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico de Categorias - TAVERNA DA IMPRESSÃO</title>
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
    <h1>Diagnóstico de Categorias - TAVERNA DA IMPRESSÃO</h1>';

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
    
    // Verificar tabela categories
    try {
        $result = $db->select("SHOW COLUMNS FROM categories");
        echo '<p class="success">✓ Tabela categories existe com ' . count($result) . ' colunas.</p>';
        
        echo '<h3>Estrutura da tabela categories:</h3>';
        echo '<pre>';
        print_r($result);
        echo '</pre>';
        
        // Listar categorias
        $categories = $db->select("SELECT * FROM categories LIMIT 10");
        echo '<h3>Categorias disponíveis (primeiras 10):</h3>';
        
        if (count($categories) > 0) {
            echo '<pre>';
            print_r($categories);
            echo '</pre>';
        } else {
            echo '<p class="warning">⚠ Nenhuma categoria encontrada no banco de dados.</p>';
        }
        
    } catch (Exception $e) {
        echo '<p class="error">✗ Erro ao verificar tabela categories: ' . $e->getMessage() . '</p>';
    }
    
} catch (Exception $e) {
    echo '<p class="error">✗ Erro na conexão com banco de dados: ' . $e->getMessage() . '</p>';
}

echo '</div>';

// Testar CategoryModel
echo '<div class="card">
    <h2>Teste do CategoryModel</h2>';

try {
    require_once '../app/models/Model.php';
    require_once '../app/models/CategoryModel.php';
    
    echo '<p class="success">✓ Arquivos do modelo carregados com sucesso.</p>';
    
    try {
        $categoryModel = new CategoryModel();
        echo '<p class="success">✓ CategoryModel instanciado com sucesso.</p>';
        
        // Testar getMainCategories
        try {
            $mainCategories = $categoryModel->getMainCategories();
            echo '<p class="success">✓ Método getMainCategories executado com sucesso.</p>';
            echo '<h3>Categorias principais:</h3>';
            echo '<pre>';
            print_r($mainCategories);
            echo '</pre>';
        } catch (Exception $e) {
            echo '<p class="error">✗ Erro ao executar getMainCategories: ' . $e->getMessage() . '</p>';
            echo '<pre>' . $e->getTraceAsString() . '</pre>';
        }
        
        // Testar getBySlug com um slug existente
        if (count($mainCategories) > 0) {
            $testSlug = $mainCategories[0]['slug'];
            try {
                $category = $categoryModel->getBySlug($testSlug);
                echo '<p class="success">✓ Método getBySlug executado com sucesso para "' . $testSlug . '".</p>';
                echo '<h3>Detalhes da categoria:</h3>';
                echo '<pre>';
                print_r($category);
                echo '</pre>';
            } catch (Exception $e) {
                echo '<p class="error">✗ Erro ao executar getBySlug: ' . $e->getMessage() . '</p>';
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
            }
        } else {
            echo '<p class="warning">⚠ Não foi possível testar getBySlug pois não há categorias disponíveis.</p>';
        }
        
        // Testar getCategoryWithProducts
        if (count($mainCategories) > 0) {
            $testSlug = $mainCategories[0]['slug'];
            try {
                echo '<h3>Testando getCategoryWithProducts para "' . $testSlug . '":</h3>';
                $categoryWithProducts = $categoryModel->getCategoryWithProducts($testSlug);
                echo '<p class="success">✓ Método getCategoryWithProducts executado com sucesso.</p>';
                
                // Verificar apenas os dados básicos para não sobrecarregar a saída
                echo '<pre>';
                if ($categoryWithProducts) {
                    echo "ID: " . $categoryWithProducts['id'] . "\n";
                    echo "Nome: " . $categoryWithProducts['name'] . "\n";
                    echo "Slug: " . $categoryWithProducts['slug'] . "\n";
                    
                    if (isset($categoryWithProducts['products']) && isset($categoryWithProducts['products']['total'])) {
                        echo "Total de produtos: " . $categoryWithProducts['products']['total'] . "\n";
                        echo "Número de produtos na página atual: " . count($categoryWithProducts['products']['items']) . "\n";
                    } else {
                        echo "Dados de produtos não encontrados na resposta.\n";
                    }
                } else {
                    echo "Nenhuma categoria retornada.";
                }
                echo '</pre>';
            } catch (Exception $e) {
                echo '<p class="error">✗ Erro ao executar getCategoryWithProducts: ' . $e->getMessage() . '</p>';
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
            }
        }
        
    } catch (Exception $e) {
        echo '<p class="error">✗ Erro ao instanciar CategoryModel: ' . $e->getMessage() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
    
} catch (Exception $e) {
    echo '<p class="error">✗ Erro ao carregar arquivos do modelo: ' . $e->getMessage() . '</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo '</div>';

// Testar Controller
echo '<div class="card">
    <h2>Teste do CategoryController</h2>';

try {
    require_once '../app/controllers/CategoryController.php';
    echo '<p class="success">✓ Arquivo do controller carregado com sucesso.</p>';
    
    try {
        $categoryController = new CategoryController();
        echo '<p class="success">✓ CategoryController instanciado com sucesso.</p>';
        
        // Não podemos testar os métodos diretamente pois eles renderizam views
        echo '<p>Note: Os métodos do controller não podem ser testados diretamente aqui pois eles renderizam views completas.</p>';
        
    } catch (Exception $e) {
        echo '<p class="error">✗ Erro ao instanciar CategoryController: ' . $e->getMessage() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
    
} catch (Exception $e) {
    echo '<p class="error">✗ Erro ao carregar arquivo do controller: ' . $e->getMessage() . '</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo '</div>';

// Verificar template de categoria
echo '<div class="card">
    <h2>Verificação da View de Categoria</h2>';

$viewPath = VIEWS_PATH . '/category.php';
if (file_exists($viewPath)) {
    echo '<p class="success">✓ View de categoria encontrada em: ' . $viewPath . '</p>';
    
    // Verificar conteúdo básico da view
    $viewContent = file_get_contents($viewPath);
    echo '<p>Primeiras 300 caracteres da view:</p>';
    echo '<pre>' . htmlspecialchars(substr($viewContent, 0, 300)) . '...</pre>';
    
} else {
    echo '<p class="error">✗ View de categoria não encontrada em: ' . $viewPath . '</p>';
}

echo '</div>

</body>
</html>';
