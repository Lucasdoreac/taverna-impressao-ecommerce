<?php
// Definir exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir configurações
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/autoload.php';

// Headers para evitar cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Funções auxiliares
function prettyJson($data) {
    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// Inicializar teste de banco de dados
$dbTest = [];
try {
    // Usar o método getInstance() em vez de construtor direto
    $db = Database::getInstance();
    $dbTest['connection'] = "OK";
    
    // Verificar a versão do MySQL
    $versionResult = $db->select("SELECT VERSION() as version");
    $dbTest['version'] = $versionResult[0]['version'] ?? 'Não disponível';
    
    // Verificar tabelas existentes
    $tablesResult = $db->select("SHOW TABLES");
    $tables = [];
    foreach ($tablesResult as $row) {
        $tables[] = reset($row); // Pega o primeiro valor do array associativo
    }
    $dbTest['tables'] = $tables;
    $dbTest['tables_count'] = count($tables);
    
    // Verificar se a tabela products existe
    $dbTest['products_table_exists'] = in_array('products', $tables);
    
    // Contar número de produtos na tabela
    if ($dbTest['products_table_exists']) {
        $countResult = $db->select("SELECT COUNT(*) as total FROM products");
        $dbTest['products_count'] = $countResult[0]['total'] ?? 0;
        
        // Listar alguns produtos para amostra
        $productsResult = $db->select("SELECT id, name, price, is_active, is_tested, stock FROM products LIMIT 5");
        $dbTest['sample_products'] = $productsResult;
    }
} catch (Exception $e) {
    $dbTest['connection'] = "FALHA: " . $e->getMessage();
}

// Testar carregamento da classe ProductModel
$modelTest = [];
try {
    $modelTest['class_exists'] = class_exists('ProductModel') ? 'SIM' : 'NÃO';
    
    if (class_exists('ProductModel')) {
        $productModel = new ProductModel();
        $modelTest['instantiation'] = 'OK';
        
        // Testar métodos principais
        // getFeatured
        try {
            $featured = $productModel->getFeatured(4);
            $modelTest['getFeatured'] = [
                'status' => 'OK',
                'count' => count($featured),
                'items' => $featured
            ];
        } catch (Exception $e) {
            $modelTest['getFeatured'] = [
                'status' => 'FALHA',
                'message' => $e->getMessage()
            ];
        }
        
        // getTestedProducts
        try {
            $tested = $productModel->getTestedProducts(4);
            $modelTest['getTestedProducts'] = [
                'status' => 'OK',
                'count' => count($tested),
                'items' => $tested
            ];
        } catch (Exception $e) {
            $modelTest['getTestedProducts'] = [
                'status' => 'FALHA',
                'message' => $e->getMessage()
            ];
        }
        
        // getCustomProducts
        try {
            $custom = $productModel->getCustomProducts(4);
            $modelTest['getCustomProducts'] = [
                'status' => 'OK',
                'count' => count($custom),
                'items' => $custom
            ];
        } catch (Exception $e) {
            $modelTest['getCustomProducts'] = [
                'status' => 'FALHA',
                'message' => $e->getMessage()
            ];
        }
        
        // Verificar se o método getDb() existe e retorna um objeto
        try {
            $db = $productModel->getDb();
            $modelTest['db_method'] = $db ? 'OK' : 'FALHA (retornou null)';
        } catch (Exception $e) {
            $modelTest['db_method'] = 'FALHA: ' . $e->getMessage();
        } catch (Error $e) {
            $modelTest['db_method'] = 'ERRO: ' . $e->getMessage();
        }
    }
} catch (Exception $e) {
    $modelTest['instantiation'] = 'FALHA: ' . $e->getMessage();
} catch (Error $e) {
    $modelTest['instantiation'] = 'ERRO: ' . $e->getMessage();
}

// Testar acesso direto ao banco de dados para produtos
$directDbTest = [];
try {
    $db = Database::getInstance();
    
    // Contar produtos totais
    $totalResult = $db->select("SELECT COUNT(*) as total FROM products");
    $directDbTest['total_products'] = $totalResult[0]['total'] ?? 0;
    
    // Contar produtos ativos
    $activeResult = $db->select("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
    $directDbTest['active_products'] = $activeResult[0]['total'] ?? 0;
    
    // Contar produtos testados
    $testedResult = $db->select("SELECT COUNT(*) as total FROM products WHERE is_tested = 1");
    $directDbTest['tested_products'] = $testedResult[0]['total'] ?? 0;
    
    // Verificar produtos com estoque
    $stockResult = $db->select("SELECT COUNT(*) as total FROM products WHERE stock > 0");
    $directDbTest['products_with_stock'] = $stockResult[0]['total'] ?? 0;
    
    // Obter amostra de produtos diretamente
    $sampleResult = $db->select("
        SELECT p.id, p.name, p.price, p.is_active, p.is_tested, p.stock,
               pi.image
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
        WHERE p.is_active = 1
        LIMIT 5
    ");
    $directDbTest['sample_products'] = $sampleResult;
} catch (Exception $e) {
    $directDbTest['error'] = $e->getMessage();
}

// Verificar queries SQL dos métodos críticos
$sqlAnalysis = [];

// Analisar todas as tabelas relacionadas a produtos
$relatedTables = ['products', 'product_images', 'categories', 'customization_options'];
foreach ($relatedTables as $table) {
    try {
        $db = Database::getInstance();
        
        // Verificar estrutura da tabela
        $structResult = $db->select("DESCRIBE {$table}");
        $sqlAnalysis[$table]['structure'] = $structResult;
        
        // Contar registros
        $countResult = $db->select("SELECT COUNT(*) as total FROM {$table}");
        $sqlAnalysis[$table]['count'] = $countResult[0]['total'] ?? 0;
    } catch (Exception $e) {
        $sqlAnalysis[$table]['error'] = $e->getMessage();
    }
}

// Recomendações baseadas na análise
$recommendations = [];

// Verificar problema com ProductModel
if ($modelTest['class_exists'] == 'SIM' && isset($modelTest['db_method']) && $modelTest['db_method'] != 'OK') {
    $recommendations[] = "Verifique se a classe ProductModel estende corretamente a classe Model e se o método getDb() está disponível.";
}

// Verificar problema com produtos retornados
if (isset($modelTest['getFeatured']['count']) && $modelTest['getFeatured']['count'] == 0 && 
    isset($directDbTest['active_products']) && $directDbTest['active_products'] > 0) {
    $recommendations[] = "Existe um problema nas consultas SQL. Produtos existem no banco de dados, mas não são retornados pelos métodos do ProductModel.";
    $recommendations[] = "Verifique a condição WHERE nas consultas SQL do ProductModel, especialmente no método getFeatured().";
}

// Verificar estoque zerado
if (isset($directDbTest['products_with_stock']) && $directDbTest['products_with_stock'] == 0 && 
    isset($directDbTest['total_products']) && $directDbTest['total_products'] > 0) {
    $recommendations[] = "Todos os produtos têm estoque zero. Isso pode estar causando produtos não aparecerem com disponibilidade 'Pronta Entrega'.";
    $recommendations[] = "Atualize o estoque de alguns produtos para testar a exibição correta.";
}

// Verificar imagens de produtos
if (isset($directDbTest['sample_products']) && !empty($directDbTest['sample_products'])) {
    $hasImages = false;
    foreach ($directDbTest['sample_products'] as $product) {
        if (!empty($product['image'])) {
            $hasImages = true;
            break;
        }
    }
    
    if (!$hasImages) {
        $recommendations[] = "Nenhum produto possui imagem principal definida. Verifique a tabela product_images.";
    }
}

// Recomendação para depuração no ProductModel
$recommendations[] = "Adicione logs detalhados nos métodos do ProductModel para verificar exatamente onde está o problema.";
$recommendations[] = "Verifique se a extensão PDO do PHP está ativada e funcionando corretamente.";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Produtos - Taverna da Impressão</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            padding: 0;
            color: #333;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card h2 {
            margin-top: 0;
            color: #2c3e50;
        }
        pre {
            background-color: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
        .warning {
            color: #f39c12;
            font-weight: bold;
        }
        .info {
            color: #3498db;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .actions {
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Diagnóstico de Produtos - Taverna da Impressão</h1>
        
        <div class="card">
            <h2>1. Conexão com Banco de Dados</h2>
            <table>
                <tr>
                    <th>Item</th>
                    <th>Resultado</th>
                </tr>
                <tr>
                    <td>Conexão</td>
                    <td class="<?php echo strpos($dbTest['connection'] ?? '', 'FALHA') === false ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($dbTest['connection'] ?? 'N/A'); ?>
                    </td>
                </tr>
                <tr>
                    <td>Versão MySQL</td>
                    <td><?php echo htmlspecialchars($dbTest['version'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <td>Tabelas encontradas</td>
                    <td><?php echo htmlspecialchars($dbTest['tables_count'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <td>Tabela products</td>
                    <td class="<?php echo ($dbTest['products_table_exists'] ?? false) ? 'success' : 'error'; ?>">
                        <?php echo ($dbTest['products_table_exists'] ?? false) ? 'Existe' : 'Não existe'; ?>
                    </td>
                </tr>
                <tr>
                    <td>Quantidade de produtos</td>
                    <td>
                        <?php 
                        $count = $dbTest['products_count'] ?? 0;
                        $class = $count > 0 ? 'success' : 'warning';
                        echo "<span class='{$class}'>{$count}</span>";
                        ?>
                    </td>
                </tr>
            </table>
            
            <?php if (!empty($dbTest['sample_products'])): ?>
            <h3>Amostra de Produtos</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Preço</th>
                    <th>Ativo</th>
                    <th>Testado</th>
                    <th>Estoque</th>
                </tr>
                <?php foreach ($dbTest['sample_products'] as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['id'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($product['name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($product['price'] ?? 'N/A'); ?></td>
                    <td class="<?php echo ($product['is_active'] ?? 0) ? 'success' : 'error'; ?>">
                        <?php echo ($product['is_active'] ?? 0) ? 'Sim' : 'Não'; ?>
                    </td>
                    <td class="<?php echo ($product['is_tested'] ?? 0) ? 'success' : 'warning'; ?>">
                        <?php echo ($product['is_tested'] ?? 0) ? 'Sim' : 'Não'; ?>
                    </td>
                    <td class="<?php echo ($product['stock'] ?? 0) > 0 ? 'success' : 'warning'; ?>">
                        <?php echo htmlspecialchars($product['stock'] ?? '0'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>2. Classe ProductModel</h2>
            <table>
                <tr>
                    <th>Item</th>
                    <th>Resultado</th>
                </tr>
                <tr>
                    <td>Classe existe</td>
                    <td class="<?php echo $modelTest['class_exists'] == 'SIM' ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($modelTest['class_exists'] ?? 'N/A'); ?>
                    </td>
                </tr>
                <tr>
                    <td>Instanciação</td>
                    <td class="<?php echo strpos($modelTest['instantiation'] ?? '', 'OK') === 0 ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($modelTest['instantiation'] ?? 'N/A'); ?>
                    </td>
                </tr>
                <tr>
                    <td>Método getDb()</td>
                    <td class="<?php echo strpos($modelTest['db_method'] ?? '', 'OK') === 0 ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($modelTest['db_method'] ?? 'N/A'); ?>
                    </td>
                </tr>
            </table>
            
            <?php if (isset($modelTest['getFeatured'])): ?>
            <h3>Teste do método getFeatured()</h3>
            <p>Status: 
                <span class="<?php echo $modelTest['getFeatured']['status'] == 'OK' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($modelTest['getFeatured']['status']); ?>
                </span>
            </p>
            <?php if ($modelTest['getFeatured']['status'] == 'OK'): ?>
                <p>Produtos encontrados: 
                    <span class="<?php echo $modelTest['getFeatured']['count'] > 0 ? 'success' : 'warning'; ?>">
                        <?php echo htmlspecialchars($modelTest['getFeatured']['count']); ?>
                    </span>
                </p>
                <?php if ($modelTest['getFeatured']['count'] > 0): ?>
                <pre><?php echo htmlspecialchars(prettyJson($modelTest['getFeatured']['items'])); ?></pre>
                <?php else: ?>
                <p class="warning">Nenhum produto em destaque encontrado.</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="error">Erro: <?php echo htmlspecialchars($modelTest['getFeatured']['message'] ?? 'Erro desconhecido'); ?></p>
            <?php endif; ?>
            <?php endif; ?>
            
            <?php if (isset($modelTest['getTestedProducts'])): ?>
            <h3>Teste do método getTestedProducts()</h3>
            <p>Status: 
                <span class="<?php echo $modelTest['getTestedProducts']['status'] == 'OK' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($modelTest['getTestedProducts']['status']); ?>
                </span>
            </p>
            <?php if ($modelTest['getTestedProducts']['status'] == 'OK'): ?>
                <p>Produtos encontrados: 
                    <span class="<?php echo $modelTest['getTestedProducts']['count'] > 0 ? 'success' : 'warning'; ?>">
                        <?php echo htmlspecialchars($modelTest['getTestedProducts']['count']); ?>
                    </span>
                </p>
                <?php if ($modelTest['getTestedProducts']['count'] > 0): ?>
                <pre><?php echo htmlspecialchars(prettyJson($modelTest['getTestedProducts']['items'])); ?></pre>
                <?php else: ?>
                <p class="warning">Nenhum produto testado encontrado.</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="error">Erro: <?php echo htmlspecialchars($modelTest['getTestedProducts']['message'] ?? 'Erro desconhecido'); ?></p>
            <?php endif; ?>
            <?php endif; ?>
            
            <?php if (isset($modelTest['getCustomProducts'])): ?>
            <h3>Teste do método getCustomProducts()</h3>
            <p>Status: 
                <span class="<?php echo $modelTest['getCustomProducts']['status'] == 'OK' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($modelTest['getCustomProducts']['status']); ?>
                </span>
            </p>
            <?php if ($modelTest['getCustomProducts']['status'] == 'OK'): ?>
                <p>Produtos encontrados: 
                    <span class="<?php echo $modelTest['getCustomProducts']['count'] > 0 ? 'success' : 'warning'; ?>">
                        <?php echo htmlspecialchars($modelTest['getCustomProducts']['count']); ?>
                    </span>
                </p>
                <?php if ($modelTest['getCustomProducts']['count'] > 0): ?>
                <pre><?php echo htmlspecialchars(prettyJson($modelTest['getCustomProducts']['items'])); ?></pre>
                <?php else: ?>
                <p class="warning">Nenhum produto sob encomenda encontrado.</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="error">Erro: <?php echo htmlspecialchars($modelTest['getCustomProducts']['message'] ?? 'Erro desconhecido'); ?></p>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>3. Acesso Direto aos Dados</h2>
            <?php if (!isset($directDbTest['error'])): ?>
            <table>
                <tr>
                    <th>Item</th>
                    <th>Resultado</th>
                </tr>
                <tr>
                    <td>Total de produtos</td>
                    <td><?php echo htmlspecialchars($directDbTest['total_products'] ?? 0); ?></td>
                </tr>
                <tr>
                    <td>Produtos ativos</td>
                    <td><?php echo htmlspecialchars($directDbTest['active_products'] ?? 0); ?></td>
                </tr>
                <tr>
                    <td>Produtos testados</td>
                    <td><?php echo htmlspecialchars($directDbTest['tested_products'] ?? 0); ?></td>
                </tr>
                <tr>
                    <td>Produtos com estoque</td>
                    <td><?php echo htmlspecialchars($directDbTest['products_with_stock'] ?? 0); ?></td>
                </tr>
            </table>
            
            <?php if (!empty($directDbTest['sample_products'])): ?>
            <h3>Amostra de Produtos (Acesso Direto)</h3>
            <pre><?php echo htmlspecialchars(prettyJson($directDbTest['sample_products'])); ?></pre>
            <?php endif; ?>
            
            <?php else: ?>
            <p class="error">Erro ao acessar diretamente o banco de dados: <?php echo htmlspecialchars($directDbTest['error']); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>4. Análise das Tabelas</h2>
            <?php foreach ($sqlAnalysis as $table => $data): ?>
            <h3>Tabela: <?php echo htmlspecialchars($table); ?></h3>
            <?php if (!isset($data['error'])): ?>
            <p>Registros: <strong><?php echo htmlspecialchars($data['count']); ?></strong></p>
            <h4>Estrutura:</h4>
            <pre><?php echo htmlspecialchars(prettyJson($data['structure'])); ?></pre>
            <?php else: ?>
            <p class="error">Erro: <?php echo htmlspecialchars($data['error']); ?></p>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <div class="card">
            <h2>5. Recomendações</h2>
            <?php if (!empty($recommendations)): ?>
            <ol>
                <?php foreach ($recommendations as $recommendation): ?>
                <li><?php echo htmlspecialchars($recommendation); ?></li>
                <?php endforeach; ?>
            </ol>
            <?php else: ?>
            <p>Nenhuma recomendação específica baseada na análise atual.</p>
            <?php endif; ?>
        </div>
        
        <div class="actions">
            <a href="index.php" class="btn">Página Inicial</a>
            <a href="correcoes-aplicadas.php" class="btn">Correções Aplicadas</a>
            <a href="diagnostico-classes.php" class="btn">Diagnóstico de Classes</a>
        </div>
    </div>
</body>
</html>