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

// Verificar se diretórios de upload existem
$uploadDirs = [
    UPLOADS_PATH,
    UPLOADS_PATH . '/products',
    UPLOADS_PATH . '/categories',
    UPLOADS_PATH . '/customization',
    UPLOADS_PATH . '/models',
    UPLOADS_PATH . '/users',
    UPLOADS_PATH . '/temp'
];

$dirStatus = [];
foreach ($uploadDirs as $dir) {
    $dirStatus[$dir] = file_exists($dir);
}

// Verificar constante CURRENCY_SYMBOL
$currencySymbolNumeric = is_numeric(CURRENCY_SYMBOL);
$currencySymbolStr = getCurrencySymbol();

// Verificar carregamento de classes críticas
$criticalClasses = [
    'Database',
    'ProductModel',
    'CategoryModel',
    'FilamentModel'
];

$classStatus = [];
foreach ($criticalClasses as $class) {
    $classStatus[$class] = class_exists($class);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correções Aplicadas - Taverna da Impressão</title>
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
            max-width: 1000px;
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
        .actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .btn-secondary {
            background-color: #7f8c8d;
        }
        .btn-secondary:hover {
            background-color: #6c7a7d;
        }
        .code {
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            margin: 10px 0;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Correções Aplicadas - Taverna da Impressão</h1>
        
        <div class="card">
            <h2>1. Diretórios de Upload</h2>
            <p>
                Foram adicionados scripts para criar automaticamente os diretórios necessários para uploads.
                Status atual dos diretórios:
            </p>
            <table>
                <tr>
                    <th>Diretório</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($dirStatus as $dir => $exists): ?>
                <tr>
                    <td><?php echo htmlspecialchars($dir); ?></td>
                    <td class="<?php echo $exists ? 'success' : 'error'; ?>">
                        <?php echo $exists ? 'Existe' : 'Não Existe'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <div class="actions">
                <a href="create_upload_dirs.php" class="btn">Executar criação de diretórios</a>
            </div>
        </div>
        
        <div class="card">
            <h2>2. Constante CURRENCY_SYMBOL</h2>
            <p>
                A constante CURRENCY_SYMBOL foi corrigida para garantir que seja sempre uma string.
            </p>
            <table>
                <tr>
                    <th>Item</th>
                    <th>Valor</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>CURRENCY_SYMBOL</td>
                    <td><?php echo htmlspecialchars(CURRENCY_SYMBOL); ?></td>
                    <td class="<?php echo $currencySymbolNumeric ? 'error' : 'success'; ?>">
                        <?php echo $currencySymbolNumeric ? 'Numérico (problema)' : 'String (correto)'; ?>
                    </td>
                </tr>
                <tr>
                    <td>getCurrencySymbol()</td>
                    <td><?php echo htmlspecialchars($currencySymbolStr); ?></td>
                    <td class="success">String (sempre retorna 'R$')</td>
                </tr>
            </table>
            <p>
                Agora, mesmo se a constante for definida incorretamente, a função getCurrencySymbol() 
                sempre retornará a string 'R$', garantindo o funcionamento correto.
            </p>
        </div>
        
        <div class="card">
            <h2>3. Autoloader Robusto</h2>
            <p>
                Um novo sistema de autoloader foi implementado para garantir o carregamento correto das classes:
            </p>
            <div class="code">
                // Verificar carregamento de classes críticas
                <?php foreach ($criticalClasses as $class): ?>
                class_exists('<?php echo $class; ?>'); // <?php echo $classStatus[$class] ? 'OK' : 'FALHA'; ?>
                
                <?php endforeach; ?>
            </div>
            <table>
                <tr>
                    <th>Classe</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($classStatus as $class => $exists): ?>
                <tr>
                    <td><?php echo htmlspecialchars($class); ?></td>
                    <td class="<?php echo $exists ? 'success' : 'error'; ?>">
                        <?php echo $exists ? 'Carregada' : 'Não Encontrada'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <p>
                Um mapa de classes detalhado foi implementado em <code>app/autoload.php</code> e integrado ao
                arquivo <code>index.php</code> principal.
            </p>
            <div class="actions">
                <a href="diagnostico-classes.php" class="btn">Verificar todas as classes</a>
            </div>
        </div>
        
        <div class="card">
            <h2>4. Scripts de Diagnóstico</h2>
            <p>
                Foram adicionados scripts de diagnóstico para facilitar a identificação e correção de problemas:
            </p>
            <ul>
                <li><strong>diagnostico-classes.php</strong> - Verifica o carregamento de classes</li>
                <li><strong>create_upload_dirs.php</strong> - Cria diretórios de upload ausentes</li>
                <li><strong>correcoes-aplicadas.php</strong> - Este relatório</li>
            </ul>
            <p>
                Estes scripts ajudam a identificar problemas específicos e verificar se as correções foram
                aplicadas corretamente.
            </p>
        </div>
        
        <div class="card">
            <h2>5. Próximos Passos</h2>
            <p>
                Para completar a implementação das correções, siga estes passos:
            </p>
            <ol>
                <li>Execute <a href="create_upload_dirs.php">create_upload_dirs.php</a> para criar os diretórios ausentes</li>
                <li>Execute <a href="diagnostico-classes.php">diagnostico-classes.php</a> para verificar o carregamento de classes</li>
                <li>Teste a página principal para verificar se os produtos são exibidos corretamente</li>
                <li>Verifique a página de customizações em <a href="customization">/customization</a></li>
            </ol>
            <p>
                Se algum problema persistir, verifique os logs de erro do servidor e use os scripts de diagnóstico
                para identificar problemas específicos.
            </p>
        </div>
        
        <div class="actions">
            <a href="index.php" class="btn">Página Inicial</a>
            <a href="diagnostico-erros.php" class="btn btn-secondary">Diagnóstico de Erros</a>
            <a href="diagnostico-classes.php" class="btn btn-secondary">Diagnóstico de Classes</a>
            <a href="create_upload_dirs.php" class="btn btn-secondary">Criar Diretórios</a>
        </div>
    </div>
</body>
</html>
