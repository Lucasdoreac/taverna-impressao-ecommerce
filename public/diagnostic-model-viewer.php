<?php
// Configurações de debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Definir cabeçalho como HTML
header('Content-Type: text/html; charset=UTF-8');

// Definir ROOT_PATH
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Carregar classes necessárias
require_once APP_PATH . '/config/config.php';
require_once APP_PATH . '/helpers/Database.php';
require_once APP_PATH . '/models/ProductModel.php';
require_once APP_PATH . '/helpers/ModelViewerHelper.php';

echo '<html><head><title>Diagnóstico do Visualizador 3D</title>';
echo '<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    h1, h2, h3 { color: #2c3e50; }
    .section { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; padding: 10px; border-radius: 4px; }
    .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; padding: 10px; border-radius: 4px; }
    .warning { background-color: #fff3cd; border-color: #ffeeba; color: #856404; padding: 10px; border-radius: 4px; }
    .info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; padding: 10px; border-radius: 4px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
    table { border-collapse: collapse; width: 100%; }
    table, th, td { border: 1px solid #ddd; }
    th, td { padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>';
echo '</head><body>';

echo '<h1>Diagnóstico do Visualizador 3D - Taverna da Impressão</h1>';
echo '<p>Esta ferramenta verifica a configuração e funcionamento do visualizador 3D no site.</p>';

// Seção 1: Verificar configurações e dependências
echo '<div class="section">';
echo '<h2>1. Verificação de Dependências</h2>';

// Verificar se o diretório de uploads/products/models existe
$modelsDir = ROOT_PATH . '/public/uploads/products/models';
if (is_dir($modelsDir)) {
    echo '<p class="success">✅ Diretório de modelos 3D encontrado: ' . $modelsDir . '</p>';
} else {
    echo '<p class="error">❌ Diretório de modelos 3D não encontrado: ' . $modelsDir . '</p>';
    echo '<p>Criando diretório...</p>';
    if (mkdir($modelsDir, 0755, true)) {
        echo '<p class="success">✅ Diretório criado com sucesso!</p>';
    } else {
        echo '<p class="error">❌ Falha ao criar diretório.</p>';
    }
}

// Verificar arquivos JavaScript e CSS
$jsFile = ROOT_PATH . '/public/assets/js/model-viewer.js';
$cssFile = ROOT_PATH . '/public/assets/css/model-viewer.css';

if (file_exists($jsFile)) {
    echo '<p class="success">✅ Arquivo JavaScript do visualizador encontrado</p>';
} else {
    echo '<p class="error">❌ Arquivo JavaScript do visualizador não encontrado: ' . $jsFile . '</p>';
}

if (file_exists($cssFile)) {
    echo '<p class="success">✅ Arquivo CSS do visualizador encontrado</p>';
} else {
    echo '<p class="error">❌ Arquivo CSS do visualizador não encontrado: ' . $cssFile . '</p>';
}

// Verificar se a classe ModelViewerHelper está funcionando
if (class_exists('ModelViewerHelper')) {
    echo '<p class="success">✅ Classe ModelViewerHelper carregada com sucesso</p>';
} else {
    echo '<p class="error">❌ Classe ModelViewerHelper não encontrada ou não carregada</p>';
}

echo '</div>';

// Seção 2: Verificar produtos com modelos 3D
echo '<div class="section">';
echo '<h2>2. Verificação de Produtos com Modelos 3D</h2>';

try {
    // Conectar ao banco de dados
    $db = new Database();
    $connection = $db->getConnection();
    
    if ($connection) {
        echo '<p class="success">✅ Conexão com o banco de dados estabelecida</p>';
        
        // Verificar estrutura da tabela products
        $query = "SHOW COLUMNS FROM products LIKE 'model_file'";
        $stmt = $connection->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo '<p class="success">✅ Coluna model_file encontrada na tabela products</p>';
            
            // Verificar produtos que têm modelos 3D
            $productModel = new ProductModel($db);
            $query = "SELECT id, name, slug, model_file FROM products WHERE model_file IS NOT NULL AND model_file != ''";
            $stmt = $connection->prepare($query);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($products) > 0) {
                echo '<p class="success">✅ ' . count($products) . ' produto(s) com modelo 3D encontrado(s)</p>';
                
                echo '<table>';
                echo '<tr><th>ID</th><th>Nome</th><th>Slug</th><th>Arquivo do Modelo</th><th>Arquivo Existe?</th></tr>';
                
                foreach ($products as $product) {
                    $modelPath = $modelsDir . '/' . $product['model_file'];
                    $fileExists = file_exists($modelPath);
                    
                    echo '<tr>';
                    echo '<td>' . $product['id'] . '</td>';
                    echo '<td>' . $product['name'] . '</td>';
                    echo '<td>' . $product['slug'] . '</td>';
                    echo '<td>' . $product['model_file'] . '</td>';
                    echo '<td>' . ($fileExists ? '<span style="color:green">Sim</span>' : '<span style="color:red">Não</span>') . '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
                
                // Testar visualizador com o primeiro produto
                $testProduct = $products[0];
                echo '<h3>Teste do Visualizador 3D</h3>';
                
                if (file_exists($modelsDir . '/' . $testProduct['model_file'])) {
                    echo '<p class="info">Testando visualizador com o produto: ' . $testProduct['name'] . '</p>';
                    
                    // Configurar produto para teste
                    $testProduct['id'] = $testProduct['id'];
                    $testProduct['name'] = $testProduct['name'];
                    $testProduct['model_file'] = $testProduct['model_file'];
                    
                    // Gerar código do visualizador
                    $viewerCode = ModelViewerHelper::createProductModelViewer($testProduct, [
                        'height' => '300px',
                        'backgroundColor' => '#f8f9fa',
                        'modelColor' => '#6c757d',
                        'autoRotate' => true,
                        'showGrid' => true,
                        'optimizeForMobile' => true
                    ]);
                    
                    echo '<div>';
                    echo $viewerCode;
                    echo '</div>';
                    
                    echo '<p class="info">Código do visualizador:</p>';
                    echo '<pre>' . htmlspecialchars($viewerCode) . '</pre>';
                } else {
                    echo '<p class="warning">⚠️ Não foi possível testar o visualizador porque o arquivo do modelo não existe.</p>';
                    echo '<p>É necessário fazer upload de arquivos STL ou OBJ para o diretório: ' . $modelsDir . '</p>';
                }
            } else {
                echo '<p class="warning">⚠️ Nenhum produto com modelo 3D encontrado no banco de dados</p>';
                echo '<p>Verifique se existem produtos com o campo model_file preenchido.</p>';
            }
            
        } else {
            echo '<p class="error">❌ Coluna model_file não encontrada na tabela products</p>';
            echo '<p>Você precisa adicionar esta coluna ao banco de dados para armazenar os caminhos dos arquivos 3D.</p>';
            echo '<p>SQL sugerido: ALTER TABLE products ADD COLUMN model_file VARCHAR(255) AFTER image;</p>';
        }
    } else {
        echo '<p class="error">❌ Não foi possível conectar ao banco de dados</p>';
    }
} catch (Exception $e) {
    echo '<p class="error">❌ Erro ao verificar produtos: ' . $e->getMessage() . '</p>';
}

echo '</div>';

// Seção 3: Verificar o visualizador com um modelo de teste
echo '<div class="section">';
echo '<h2>3. Teste do Visualizador com Modelo Padrão</h2>';

// Verificar se temos um modelo de teste
$testModelPath = ROOT_PATH . '/public/assets/models/test-cube.stl';

// Criar modelo de teste se não existir
if (!file_exists($testModelPath)) {
    echo '<p class="warning">⚠️ Modelo de teste não encontrado. Tentando criar um modelo básico...</p>';
    
    // Diretório para modelos de teste
    $testModelDir = dirname($testModelPath);
    if (!is_dir($testModelDir)) {
        mkdir($testModelDir, 0755, true);
    }
    
    // Conteúdo de um cubo STL básico (formato ASCII)
    $cubeStl = <<<STL
solid Cube
  facet normal 0.0 0.0 1.0
    outer loop
      vertex 0.0 0.0 0.0
      vertex 1.0 0.0 0.0
      vertex 1.0 1.0 0.0
    endloop
  endfacet
  facet normal 0.0 0.0 1.0
    outer loop
      vertex 0.0 0.0 0.0
      vertex 1.0 1.0 0.0
      vertex 0.0 1.0 0.0
    endloop
  endfacet
  facet normal 0.0 0.0 -1.0
    outer loop
      vertex 0.0 0.0 1.0
      vertex 1.0 1.0 1.0
      vertex 1.0 0.0 1.0
    endloop
  endfacet
  facet normal 0.0 0.0 -1.0
    outer loop
      vertex 0.0 0.0 1.0
      vertex 0.0 1.0 1.0
      vertex 1.0 1.0 1.0
    endloop
  endfacet
  facet normal 0.0 1.0 0.0
    outer loop
      vertex 0.0 0.0 0.0
      vertex 0.0 0.0 1.0
      vertex 1.0 0.0 1.0
    endloop
  endfacet
  facet normal 0.0 1.0 0.0
    outer loop
      vertex 0.0 0.0 0.0
      vertex 1.0 0.0 1.0
      vertex 1.0 0.0 0.0
    endloop
  endfacet
  facet normal 0.0 -1.0 0.0
    outer loop
      vertex 0.0 1.0 0.0
      vertex 1.0 1.0 1.0
      vertex 0.0 1.0 1.0
    endloop
  endfacet
  facet normal 0.0 -1.0 0.0
    outer loop
      vertex 0.0 1.0 0.0
      vertex 1.0 1.0 0.0
      vertex 1.0 1.0 1.0
    endloop
  endfacet
  facet normal 1.0 0.0 0.0
    outer loop
      vertex 0.0 0.0 0.0
      vertex 0.0 1.0 0.0
      vertex 0.0 1.0 1.0
    endloop
  endfacet
  facet normal 1.0 0.0 0.0
    outer loop
      vertex 0.0 0.0 0.0
      vertex 0.0 1.0 1.0
      vertex 0.0 0.0 1.0
    endloop
  endfacet
  facet normal -1.0 0.0 0.0
    outer loop
      vertex 1.0 0.0 0.0
      vertex 1.0 0.0 1.0
      vertex 1.0 1.0 1.0
    endloop
  endfacet
  facet normal -1.0 0.0 0.0
    outer loop
      vertex 1.0 0.0 0.0
      vertex 1.0 1.0 1.0
      vertex 1.0 1.0 0.0
    endloop
  endfacet
endsolid Cube
STL;
    
    // Salvar o modelo de teste
    if (file_put_contents($testModelPath, $cubeStl)) {
        echo '<p class="success">✅ Modelo de teste criado com sucesso</p>';
    } else {
        echo '<p class="error">❌ Não foi possível criar o modelo de teste</p>';
    }
}

// Testar o visualizador com modelo padrão
if (file_exists($testModelPath)) {
    echo '<p class="success">✅ Modelo de teste encontrado: ' . $testModelPath . '</p>';
    
    // URLs para os arquivos
    $baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $baseUrl .= "://".$_SERVER['HTTP_HOST'];
    $baseUrl .= dirname(str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']));
    
    $testModelUrl = $baseUrl . '/assets/models/test-cube.stl';
    
    // Criar um produto de teste
    $testProduct = [
        'id' => 9999,
        'name' => 'Produto de Teste',
        'model_file' => 'test-cube.stl'
    ];
    
    echo '<p class="info">URL do modelo de teste: ' . htmlspecialchars($testModelUrl) . '</p>';
    
    // Exibir visualizador de teste
    echo '<h3>Visualizador com Modelo de Teste</h3>';
    echo ModelViewerHelper::includeThreeJs();
    
    echo '<div style="width: 100%; height: 300px; border: 1px solid #ddd; margin-bottom: 20px;">';
    echo ModelViewerHelper::createViewer('test-viewer', $testModelUrl, 'stl', [
        'height' => '300px',
        'backgroundColor' => '#f0f0f0',
        'modelColor' => '#2980b9',
        'autoRotate' => true,
        'showGrid' => true,
        'showControls' => true
    ]);
    echo '</div>';
    
    echo ModelViewerHelper::getResponsiveOrientationScript();
    
} else {
    echo '<p class="error">❌ Não foi possível encontrar ou criar um modelo de teste</p>';
}

echo '</div>';

// Seção 4: Verificar a integração do ModelViewerHelper com a view do produto
echo '<div class="section">';
echo '<h2>4. Análise da Integração com a View do Produto</h2>';

// Verificar o arquivo product.php
$productViewPath = APP_PATH . '/views/product.php';
if (file_exists($productViewPath)) {
    $productView = file_get_contents($productViewPath);
    
    echo '<p class="success">✅ View do produto encontrada</p>';
    
    // Procurar por padrões importantes
    $patterns = [
        'ModelViewerHelper' => '/ModelViewerHelper::/',
        'Inclusão do Three.js' => '/ModelViewerHelper::includeThreeJs/',
        'Criação do visualizador' => '/ModelViewerHelper::createProductModelViewer/',
        'Verificação de model_file' => '/isset\(\$product\[\'model_file\'\]\)/',
    ];
    
    foreach ($patterns as $name => $pattern) {
        if (preg_match($pattern, $productView)) {
            echo '<p class="success">✅ ' . $name . ' encontrado na view</p>';
        } else {
            echo '<p class="error">❌ ' . $name . ' não encontrado na view</p>';
        }
    }
    
    // Analisar como a view verifica a existência do modelo 3D
    echo '<h3>Lógica de Verificação do Modelo 3D</h3>';
    preg_match('/if\s*\((.*?)model_file(.*?)\):/s', $productView, $matches);
    
    if (!empty($matches)) {
        echo '<p class="info">Condição encontrada: <code>' . htmlspecialchars($matches[0]) . '</code></p>';
        
        if (stripos($matches[0], 'empty') !== false) {
            echo '<p class="success">✅ View verifica corretamente se o campo model_file está vazio</p>';
        } else {
            echo '<p class="warning">⚠️ View pode não estar verificando completamente se o campo model_file está vazio</p>';
        }
    } else {
        echo '<p class="error">❌ Não foi possível encontrar a condição de verificação do modelo 3D</p>';
    }
    
} else {
    echo '<p class="error">❌ View do produto não encontrada: ' . $productViewPath . '</p>';
}

echo '</div>';

// Seção 5: Verificar a localização dos arquivos de modelo
echo '<div class="section">';
echo '<h2>5. Análise dos Diretórios de Modelos 3D</h2>';

// Verificar os diretórios onde podem estar os modelos
$possibleDirs = [
    ROOT_PATH . '/public/uploads/products/models',
    ROOT_PATH . '/public/uploads/models',
    ROOT_PATH . '/public/assets/models',
];

echo '<h3>Verificação de Diretórios</h3>';
echo '<ul>';

foreach ($possibleDirs as $dir) {
    if (is_dir($dir)) {
        echo '<li class="success">✅ ' . $dir . ' (Existe)</li>';
        
        // Listar arquivos STL e OBJ neste diretório
        $files = glob($dir . '/*.{stl,obj}', GLOB_BRACE);
        
        if (count($files) > 0) {
            echo '<ul>';
            foreach ($files as $file) {
                echo '<li>' . basename($file) . ' (' . filesize($file) . ' bytes)</li>';
            }
            echo '</ul>';
        } else {
            echo '<ul><li class="warning">⚠️ Nenhum arquivo STL ou OBJ encontrado neste diretório</li></ul>';
        }
        
    } else {
        echo '<li class="warning">⚠️ ' . $dir . ' (Não existe)</li>';
    }
}

echo '</ul>';

// Verificar como o ModelViewerHelper constrói os caminhos para os arquivos
$helperPath = APP_PATH . '/helpers/ModelViewerHelper.php';
if (file_exists($helperPath)) {
    $helperCode = file_get_contents($helperPath);
    
    preg_match('/filePath\s*=\s*BASE_URL\s*\.\s*\'([^\']+)\'/', $helperCode, $matches);
    
    if (!empty($matches)) {
        echo '<p class="info">Caminho de arquivo usado pelo helper: <code>BASE_URL . \'' . $matches[1] . '\'</code></p>';
        
        // Verificar se o diretório referenciado existe
        $relativePath = $matches[1];
        $absolutePath = ROOT_PATH . '/public' . str_replace(BASE_URL, '', $relativePath);
        
        echo '<p>Caminho absoluto correspondente: <code>' . $absolutePath . '</code></p>';
        
        if (is_dir(dirname($absolutePath))) {
            echo '<p class="success">✅ O diretório pai do caminho referenciado existe</p>';
        } else {
            echo '<p class="warning">⚠️ O diretório pai do caminho referenciado não existe</p>';
        }
    } else {
        echo '<p class="warning">⚠️ Não foi possível identificar como o helper constrói os caminhos para os arquivos</p>';
    }
} else {
    echo '<p class="error">❌ Arquivo ModelViewerHelper.php não encontrado</p>';
}

echo '</div>';

// Seção 6: Recomendações
echo '<div class="section">';
echo '<h2>6. Recomendações para Correção</h2>';

echo '<h3>Com base na análise, aqui estão as possíveis correções:</h3>';
echo '<ol>';
echo '<li>Certifique-se de que os produtos no banco de dados tenham o campo <code>model_file</code> preenchido corretamente.</li>';
echo '<li>Verifique se o caminho para os arquivos de modelo no ModelViewerHelper está correto e aponta para o diretório onde os arquivos estão realmente armazenados.</li>';
echo '<li>Confira se a verificação na view do produto está correta e considera todos os casos possíveis.</li>';
echo '<li>Verifique se os arquivos 3D existem fisicamente nos diretórios referenciados.</li>';
echo '<li>Teste um arquivo STL ou OBJ simples para garantir que o visualizador está funcionando corretamente.</li>';
echo '</ol>';

// Crie um exemplo mínimo para corrigir o problema
echo '<h3>Exemplo de Implementação Correta:</h3>';

echo '<pre>';
echo htmlspecialchars('
// Em ProductController.php
public function view($id) {
    // Obter dados do produto
    $product = $this->productModel->getProductById($id);
    
    // Verificar se o produto tem um modelo 3D
    if (!isset($product["model_file"])) {
        $product["model_file"] = ""; // Garantir que a chave exista
    }
    
    // Verificar explicitamente se o caminho do arquivo existe
    if (!empty($product["model_file"])) {
        $modelPath = ROOT_PATH . "/public/uploads/products/models/" . $product["model_file"];
        if (!file_exists($modelPath)) {
            $product["model_file"] = ""; // Resetar se o arquivo não existir
        }
    }
    
    // Carregar a view
    include VIEWS_PATH . "/product.php";
}

// Na view product.php (trecho relevante)
<?php if (!empty($product["model_file"])): ?>
    <?= ModelViewerHelper::createProductModelViewer($product, [
        "height" => "model-viewer-height-md",
        "backgroundColor" => "#ffffff",
        "modelColor" => "#5a5a5a",
        "showGrid" => true,
        "showControls" => true,
        "autoRotate" => true,
        "optimizeForMobile" => true,
        "progressiveLoading" => true
    ]); ?>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-cube fa-3x mb-3 text-muted"></i>
            <h4>Visualização 3D não disponível</h4>
            <p class="text-muted">Este produto não possui um modelo 3D para visualização.</p>
        </div>
    </div>
<?php endif; ?>
');
echo '</pre>';

echo '</div>';

// Seção 7: Conclusão
echo '<div class="section">';
echo '<h2>7. Conclusão</h2>';

echo '<p>Esta ferramenta de diagnóstico verificou a configuração e funcionamento do visualizador 3D no site Taverna da Impressão.</p>';
echo '<p>Use as informações acima para identificar e corrigir os problemas que impedem o visualizador 3D de funcionar corretamente.</p>';
echo '<p>Se necessário, adicione entradas no banco de dados e faça upload de arquivos STL ou OBJ para os diretórios apropriados.</p>';

echo '</div>';

echo '</body></html>';
?>
