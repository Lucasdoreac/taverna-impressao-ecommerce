<?php
/**
 * Diagnóstico de carregamento de classes
 * 
 * Este script verifica se as classes principais do sistema estão sendo
 * carregadas corretamente. Útil para identificar problemas com o autoloader.
 */

// Definir exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir configurações e autoloader
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/autoload.php';

/**
 * Testa o carregamento de uma classe
 * 
 * @param string $className Nome da classe a ser testada
 * @return array Resultado do teste
 */
function testClass($className) {
    $result = [
        'class' => $className,
        'exists' => class_exists($className),
        'file' => null,
        'methods' => []
    ];
    
    if ($result['exists']) {
        $reflector = new ReflectionClass($className);
        $result['file'] = $reflector->getFileName();
        $methods = $reflector->getMethods(ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if ($method->class === $className) {
                $result['methods'][] = $method->name;
            }
        }
    }
    
    return $result;
}

// Classes a serem testadas
$classes = [
    'Database',
    'ProductModel',
    'CategoryModel',
    'FilamentModel',
    'Model',
    'Controller',
    'ProductController',
    'CategoryController',
    'CustomizationController',
    'Router',
    'Request',
    'Response'
];

$results = [];
foreach ($classes as $class) {
    $results[] = testClass($class);
}

// Headers para evitar cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Classes - Taverna da Impressão</title>
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
        .diagnostics {
            margin: 20px 0;
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
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
        .methods {
            margin-top: 5px;
            font-size: 0.9em;
            color: #7f8c8d;
        }
        .summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>Diagnóstico de Classes - Taverna da Impressão</h1>
    
    <div class="diagnostics">
        <h2>Status de Carregamento das Classes</h2>
        
        <table>
            <tr>
                <th>Classe</th>
                <th>Status</th>
                <th>Arquivo</th>
                <th>Métodos Públicos</th>
            </tr>
            <?php foreach ($results as $result): ?>
            <tr>
                <td><?php echo htmlspecialchars($result['class']); ?></td>
                <td class="<?php echo $result['exists'] ? 'success' : 'error'; ?>">
                    <?php echo $result['exists'] ? 'Carregada' : 'Não Encontrada'; ?>
                </td>
                <td>
                    <?php echo $result['file'] ? htmlspecialchars($result['file']) : 'N/A'; ?>
                </td>
                <td>
                    <?php if (!empty($result['methods'])): ?>
                        <?php echo htmlspecialchars(implode(', ', $result['methods'])); ?>
                    <?php else: ?>
                        <span class="error">Nenhum método público encontrado</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <div class="summary">
            <h3>Resumo do Diagnóstico</h3>
            <?php
            $totalClasses = count($classes);
            $loadedClasses = array_filter($results, function($item) { return $item['exists']; });
            $loadedCount = count($loadedClasses);
            $failedCount = $totalClasses - $loadedCount;
            $successRate = ($loadedCount / $totalClasses) * 100;
            ?>
            <p>
                <strong>Total de classes testadas:</strong> <?php echo $totalClasses; ?><br>
                <strong>Classes carregadas com sucesso:</strong> <?php echo $loadedCount; ?><br>
                <strong>Classes não encontradas:</strong> <?php echo $failedCount; ?><br>
                <strong>Taxa de sucesso:</strong> <?php echo number_format($successRate, 2); ?>%
            </p>
            
            <?php if ($failedCount > 0): ?>
            <div class="error">
                <h4>Classes com problemas:</h4>
                <ul>
                    <?php foreach ($results as $result): ?>
                        <?php if (!$result['exists']): ?>
                        <li><?php echo htmlspecialchars($result['class']); ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <p>
                    <strong>Possíveis soluções:</strong>
                </p>
                <ol>
                    <li>Verifique se os arquivos das classes existem nos diretórios corretos</li>
                    <li>Confirme se o autoloader está configurado corretamente em <code>app/autoload.php</code></li>
                    <li>Verifique se há erros de sintaxe nos arquivos das classes</li>
                    <li>Certifique-se de que a declaração da classe corresponde ao nome do arquivo</li>
                </ol>
            </div>
            <?php else: ?>
            <p class="success">
                Todas as classes estão sendo carregadas corretamente!
            </p>
            <?php endif; ?>
        </div>
    </div>
    
    <h2>Informações do Sistema</h2>
    <table>
        <tr>
            <th>Item</th>
            <th>Valor</th>
        </tr>
        <tr>
            <td>PHP Version</td>
            <td><?php echo phpversion(); ?></td>
        </tr>
        <tr>
            <td>Web Server</td>
            <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></td>
        </tr>
        <tr>
            <td>Document Root</td>
            <td><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?></td>
        </tr>
        <tr>
            <td>Autoloader Path</td>
            <td><?php echo realpath(__DIR__ . '/../app/autoload.php'); ?></td>
        </tr>
        <tr>
            <td>Include Path</td>
            <td><?php echo get_include_path(); ?></td>
        </tr>
    </table>
    
    <p><a href="index.php">Voltar para a página inicial</a></p>
</body>
</html>
