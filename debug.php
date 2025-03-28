<?php
/**
 * Script de diagnóstico TAVERNA DA IMPRESSÃO
 * 
 * Este script exibe informações detalhadas sobre o ambiente, configurações,
 * diretórios e conexão com o banco de dados para ajudar na depuração.
 * 
 * ATENÇÃO: Remova este arquivo após resolver os problemas!
 */

// Exibir todos os erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir configurações, se disponíveis
$configPath = __DIR__ . '/app/config/config.php';
$configExists = file_exists($configPath);
if ($configExists) {
    require_once $configPath;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - TAVERNA DA IMPRESSÃO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 30px; }
        .debug-section { margin-bottom: 30px; }
        .error { color: #dc3545; }
        .success { color: #198754; }
        .warning { color: #ffc107; }
        pre { background-color: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Diagnóstico TAVERNA DA IMPRESSÃO</h1>
        
        <div class="alert alert-warning">
            <strong>Atenção!</strong> Este arquivo expõe informações sensíveis sobre o sistema. Remova após o uso.
        </div>
        
        <div class="debug-section">
            <h2>Informações do Servidor</h2>
            <div class="row">
                <div class="col-md-6">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            PHP Version
                            <span class="badge bg-primary"><?= phpversion() ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Server Software
                            <span class="badge bg-secondary"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Document Root
                            <span class="badge bg-secondary"><?= $_SERVER['DOCUMENT_ROOT'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Script Filename
                            <span class="badge bg-secondary"><?= $_SERVER['SCRIPT_FILENAME'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Server Name
                            <span class="badge bg-secondary"><?= $_SERVER['SERVER_NAME'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Request URI
                            <span class="badge bg-secondary"><?= $_SERVER['REQUEST_URI'] ?></span>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">Constantes definidas</div>
                        <div class="card-body">
                            <?php if ($configExists): ?>
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Constante</th>
                                            <th>Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (['BASE_URL', 'ROOT_PATH', 'APP_PATH', 'VIEWS_PATH', 'UPLOADS_PATH', 'ENVIRONMENT'] as $const): ?>
                                            <?php if (defined($const)): ?>
                                                <tr>
                                                    <td><code><?= $const ?></code></td>
                                                    <td><code><?= htmlspecialchars(constant($const)) ?></code></td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    Arquivo de configuração não encontrado: <?= $configPath ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="debug-section">
            <h2>Diretórios e Arquivos Críticos</h2>
            
            <div class="card mb-3">
                <div class="card-header">Verificação de Diretórios</div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Diretório</th>
                                <th>Existe</th>
                                <th>Permissões</th>
                                <th>É Gravável</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $dirs = [
                                'app',
                                'app/controllers',
                                'app/models',
                                'app/views',
                                'app/views/errors',
                                'public',
                                'public/uploads'
                            ];
                            
                            foreach ($dirs as $dir): 
                                $fullPath = realpath($dir);
                                $exists = $fullPath !== false;
                                $permissions = $exists ? substr(sprintf('%o', fileperms($fullPath)), -4) : 'N/A';
                                $writable = $exists ? (is_writable($fullPath) ? 'Sim' : 'Não') : 'N/A';
                            ?>
                                <tr>
                                    <td><?= $dir ?></td>
                                    <td><?= $exists ? '<span class="success">Sim</span>' : '<span class="error">Não</span>' ?></td>
                                    <td><?= $permissions ?></td>
                                    <td><?= $writable === 'Sim' ? '<span class="success">Sim</span>' : '<span class="error">Não</span>' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Verificação de Arquivos Críticos</div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Arquivo</th>
                                <th>Existe</th>
                                <th>Tamanho</th>
                                <th>Data de Modificação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $files = [
                                'public/index.php',
                                'app/config/config.php',
                                'app/config/routes.php',
                                'app/helpers/Router.php',
                                'app/helpers/Database.php',
                                '.htaccess',
                                'public/.htaccess',
                                'app/views/errors/404.php',
                                'app/views/errors/500.php'
                            ];
                            
                            foreach ($files as $file): 
                                $exists = file_exists($file);
                                $size = $exists ? filesize($file) : 'N/A';
                                $modified = $exists ? date("Y-m-d H:i:s", filemtime($file)) : 'N/A';
                            ?>
                                <tr>
                                    <td><?= $file ?></td>
                                    <td><?= $exists ? '<span class="success">Sim</span>' : '<span class="error">Não</span>' ?></td>
                                    <td><?= $exists ? number_format($size) . ' bytes' : 'N/A' ?></td>
                                    <td><?= $modified ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="debug-section">
            <h2>Extensões PHP Carregadas</h2>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Extensões Críticas</div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php 
                                $criticalExtensions = ['pdo', 'pdo_mysql', 'mysqli', 'gd', 'fileinfo', 'curl', 'json', 'session'];
                                foreach ($criticalExtensions as $ext): 
                                    $loaded = extension_loaded($ext);
                                ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= $ext ?>
                                        <?php if ($loaded): ?>
                                            <span class="badge bg-success">Carregada</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Não carregada</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">Todas as Extensões (<?= count(get_loaded_extensions()) ?>)</div>
                        <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                            <?php 
                            $extensions = get_loaded_extensions();
                            sort($extensions);
                            
                            echo '<div class="row">';
                            $numCols = 3;
                            $perCol = ceil(count($extensions) / $numCols);
                            
                            for ($i = 0; $i < $numCols; $i++) {
                                echo '<div class="col-md-4"><ul class="list-group">';
                                for ($j = $i * $perCol; $j < min(($i + 1) * $perCol, count($extensions)); $j++) {
                                    echo '<li class="list-group-item small py-1">' . $extensions[$j] . '</li>';
                                }
                                echo '</ul></div>';
                            }
                            
                            echo '</div>';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="debug-section">
            <h2>Conexão com o Banco de Dados</h2>
            
            <?php if (!$configExists || !defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')): ?>
                <div class="alert alert-danger">
                    Configurações de banco de dados não encontradas. Verifique o arquivo config.php.
                </div>
            <?php else: ?>
                <?php
                $dbConnectSuccess = false;
                $dbErrorMsg = '';
                $tables = [];
                
                try {
                    $conn = new PDO(
                        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                        DB_USER,
                        DB_PASS,
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                    $dbConnectSuccess = true;
                    
                    // Verificar tabelas
                    $stmt = $conn->query("SHOW TABLES");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                } catch (PDOException $e) {
                    $dbErrorMsg = $e->getMessage();
                }
                ?>
                
                <?php if ($dbConnectSuccess): ?>
                    <div class="alert alert-success">
                        <strong>Sucesso!</strong> Conexão com o banco de dados estabelecida corretamente.
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header">Configurações do Banco de Dados</div>
                        <div class="card-body">
                            <table class="table">
                                <tr>
                                    <th>Host</th>
                                    <td><?= DB_HOST ?></td>
                                </tr>
                                <tr>
                                    <th>Banco de Dados</th>
                                    <td><?= DB_NAME ?></td>
                                </tr>
                                <tr>
                                    <th>Usuário</th>
                                    <td><?= DB_USER ?></td>
                                </tr>
                                <tr>
                                    <th>Senha</th>
                                    <td>[REDACTED]</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">Tabelas do Banco de Dados (<?= count($tables) ?>)</div>
                        <div class="card-body">
                            <?php if (empty($tables)): ?>
                                <div class="alert alert-warning">
                                    Nenhuma tabela encontrada no banco de dados.
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php 
                                    $expectedTables = ['users', 'products', 'categories', 'orders', 'order_items', 'carts', 'cart_items', 'customization_options'];
                                    $missingTables = array_diff($expectedTables, $tables);
                                    
                                    if (!empty($missingTables)):
                                    ?>
                                        <div class="col-12 mb-3">
                                            <div class="alert alert-warning">
                                                <strong>Tabelas esperadas não encontradas:</strong> <?= implode(', ', $missingTables) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="col-md-6">
                                        <div class="list-group">
                                            <?php foreach (array_slice($tables, 0, ceil(count($tables) / 2)) as $table): ?>
                                                <div class="list-group-item">
                                                    <?= $table ?>
                                                    <?php if (in_array($table, $expectedTables)): ?>
                                                        <span class="badge bg-primary float-end">Esperada</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="list-group">
                                            <?php foreach (array_slice($tables, ceil(count($tables) / 2)) as $table): ?>
                                                <div class="list-group-item">
                                                    <?= $table ?>
                                                    <?php if (in_array($table, $expectedTables)): ?>
                                                        <span class="badge bg-primary float-end">Esperada</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <strong>Erro de conexão:</strong> <?= htmlspecialchars($dbErrorMsg) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="debug-section">
            <h2>Logs de Erro</h2>
            
            <?php
            $errorLogPath = ini_get('error_log');
            $logExists = false;
            $logContent = '';
            
            if (!empty($errorLogPath) && file_exists($errorLogPath) && is_readable($errorLogPath)) {
                $logExists = true;
                $logSize = filesize($errorLogPath);
                
                if ($logSize > 0) {
                    // Ler últimas 50 linhas do log
                    $file = new SplFileObject($errorLogPath, 'r');
                    $file->seek(PHP_INT_MAX); // Ir para o final do arquivo
                    $totalLines = $file->key();
                    
                    $linesToRead = min(50, $totalLines);
                    $lineStart = max(0, $totalLines - $linesToRead);
                    
                    $file->seek($lineStart);
                    
                    $logContent = '';
                    for ($i = 0; $i < $linesToRead; $i++) {
                        $logContent .= $file->current();
                        $file->next();
                    }
                }
            }
            ?>
            
            <?php if ($logExists): ?>
                <div class="card">
                    <div class="card-header">
                        Últimas entradas do log (<?= $errorLogPath ?>)
                    </div>
                    <div class="card-body">
                        <?php if (empty($logContent)): ?>
                            <div class="alert alert-info">
                                O arquivo de log existe, mas está vazio ou não contém entradas recentes.
                            </div>
                        <?php else: ?>
                            <pre><?= htmlspecialchars($logContent) ?></pre>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    Não foi possível acessar o arquivo de log (<?= $errorLogPath ?: 'caminho não configurado' ?>).
                </div>
            <?php endif; ?>
        </div>
        
        <div class="debug-section">
            <h2>Testes de Inclusão</h2>
            
            <div class="card">
                <div class="card-header">Testes de include para arquivos críticos</div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php
                        $includeTests = [
                            'app/helpers/Router.php' => 'Router',
                            'app/helpers/Database.php' => 'Database',
                            'app/controllers/ProductController.php' => 'ProductController',
                            'app/controllers/CategoryController.php' => 'CategoryController',
                            'app/models/ProductModel.php' => 'ProductModel',
                            'app/models/CategoryModel.php' => 'CategoryModel'
                        ];
                        
                        foreach ($includeTests as $file => $className):
                            $result = '';
                            $success = false;
                            
                            ob_start();
                            try {
                                if (!class_exists($className, false)) { // Não carregue automaticamente
                                    if (file_exists($file)) {
                                        require_once $file;
                                        if (class_exists($className, false)) {
                                            $result = "Incluído com sucesso e classe '$className' encontrada";
                                            $success = true;
                                        } else {
                                            $result = "Arquivo incluído, mas classe '$className' não encontrada";
                                        }
                                    } else {
                                        $result = "Arquivo não encontrado";
                                    }
                                } else {
                                    $result = "Classe '$className' já carregada";
                                    $success = true;
                                }
                            } catch (Throwable $e) {
                                $result = "Erro ao incluir: " . $e->getMessage();
                            }
                            $output = ob_get_clean();
                            
                            if (!empty($output)) {
                                $result .= " (Saída: " . htmlspecialchars(substr($output, 0, 100)) . ")";
                            }
                        ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><?= $file ?></span>
                                    <?php if ($success): ?>
                                        <span class="badge bg-success">OK</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Falha</span>
                                    <?php endif; ?>
                                </div>
                                <div class="small <?= $success ? 'text-success' : 'text-danger' ?>"><?= $result ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="alert alert-warning mt-4">
            <strong>Segurança!</strong> Lembre-se de remover este arquivo após o diagnóstico.
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
