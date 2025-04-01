<?php
/**
 * Status - Página de diagnóstico completo do sistema
 * 
 * Esta ferramenta fornece uma visão detalhada do status do sistema,
 * incluindo verificação de arquivos, banco de dados, configurações e mais.
 */

// Desabilitar cache para sempre obter versão atualizada
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Habilitar exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Definir mime type para HTML
header('Content-Type: text/html; charset=utf-8');

// Definir caminhos básicos
$root_path = dirname(__DIR__); // Um nível acima de public
$public_path = __DIR__;
$app_path = $root_path . '/app';

// Função para verificar se um arquivo existe e é legível
function check_file($path, $base_path = '') {
    $full_path = $base_path ? $base_path . '/' . $path : $path;
    if (file_exists($full_path)) {
        $status = is_readable($full_path) ? 'Legível' : 'Não legível';
        $size = filesize($full_path);
        $modified = date("Y-m-d H:i:s", filemtime($full_path));
        return [
            'exists' => true,
            'status' => $status,
            'size' => $size,
            'modified' => $modified
        ];
    }
    return ['exists' => false];
}

// Verificar arquivos críticos do sistema
$critical_files = [
    '.htaccess' => $root_path,
    'public/.htaccess' => $root_path,
    'app/config/config.php' => $root_path,
    'app/autoload.php' => $root_path,
    'app/helpers/Router.php' => $root_path,
    'app/core/Router.php' => $root_path,
    'public/index.php' => $root_path,
    'app/config/routes.php' => $root_path
];

// Arquivos de modelo e banco de dados
$model_files = [
    'app/models/Model.php' => $root_path,
    'app/models/ProductModel.php' => $root_path,
    'app/models/CategoryModel.php' => $root_path,
    'app/models/UserModel.php' => $root_path,
    'app/core/Database.php' => $root_path,
    'app/helpers/Database.php' => $root_path
];

// Arquivos de controlador
$controller_files = [
    'app/core/Controller.php' => $root_path,
    'app/controllers/HomeController.php' => $root_path,
    'app/controllers/ProductController.php' => $root_path,
    'app/controllers/CategoryController.php' => $root_path,
    'app/controllers/CartController.php' => $root_path
];

// Arquivos de diagnóstico
$diagnostic_files = [
    'public/diag.php' => $root_path,
    'public/diagnostico-classes.php' => $root_path,
    'public/verificar-constantes.php' => $root_path,
    'public/diagnostico-produtos.php' => $root_path,
    'public/diagnostico-erros.php' => $root_path,
    'public/product_debug.php' => $root_path
];

// Verificar informações do servidor
$server_info = [
    'PHP Version' => phpversion(),
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido',
    'Server Name' => $_SERVER['SERVER_NAME'] ?? 'Desconhecido',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Desconhecido',
    'Script Name' => $_SERVER['SCRIPT_NAME'] ?? 'Desconhecido',
    'Request URI' => $_SERVER['REQUEST_URI'] ?? 'Desconhecido',
    'PHP SAPI' => php_sapi_name()
];

// Verificar variáveis de ambiente e configurações
$php_config = [
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => ini_get('error_reporting'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size')
];

// Verificar extensões do PHP importantes
$required_extensions = [
    'pdo', 'pdo_mysql', 'gd', 'curl', 'json', 'mbstring', 'fileinfo', 'zip'
];

// Verificar se o mod_rewrite está disponível
$mod_rewrite_enabled = function_exists('apache_get_modules') ? in_array('mod_rewrite', apache_get_modules()) : 'Desconhecido';

// Verificar constantes críticas
$check_constants = [];
if (file_exists($root_path . '/app/config/config.php')) {
    include_once $root_path . '/app/config/config.php';
    
    $critical_constants = [
        'ENVIRONMENT', 'ROOT_PATH', 'APP_PATH', 'VIEWS_PATH', 
        'UPLOADS_PATH', 'CURRENCY', 'CURRENCY_SYMBOL'
    ];
    
    foreach ($critical_constants as $constant) {
        $check_constants[$constant] = [
            'defined' => defined($constant),
            'value' => defined($constant) ? constant($constant) : null,
            'type' => defined($constant) ? gettype(constant($constant)) : null
        ];
    }
}

// Função para tentar conectar ao banco de dados
function test_database_connection() {
    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
        return [
            'success' => false,
            'message' => 'Constantes de banco de dados não definidas'
        ];
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Testar a conexão
        $pdo->query("SELECT 1");
        
        return [
            'success' => true,
            'message' => 'Conexão com o banco de dados estabelecida com sucesso'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Erro na conexão com o banco de dados: ' . $e->getMessage()
        ];
    }
}

// Testar a existência das tabelas principais
function check_database_tables() {
    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
        return [
            'success' => false,
            'message' => 'Constantes de banco de dados não definidas',
            'tables' => []
        ];
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Lista de tabelas para verificar
        $tables_to_check = [
            'products', 'categories', 'users', 'orders', 'order_items',
            'product_images', 'customization_options', 'filaments'
        ];
        
        $table_status = [];
        
        foreach ($tables_to_check as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
                $result = $stmt->fetch();
                
                $table_status[$table] = [
                    'exists' => true,
                    'count' => $result['count']
                ];
            } catch (PDOException $e) {
                $table_status[$table] = [
                    'exists' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return [
            'success' => true,
            'message' => 'Verificação de tabelas concluída',
            'tables' => $table_status
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Erro na conexão com o banco de dados: ' . $e->getMessage(),
            'tables' => []
        ];
    }
}

$db_connection = null;
$db_tables = null;

// Realizar testes de banco de dados apenas se solicitado
if (isset($_GET['check_db'])) {
    $db_connection = test_database_connection();
    $db_tables = check_database_tables();
}

// Verificar diretórios importantes
$important_dirs = [
    'public/uploads' => $root_path,
    'public/uploads/products' => $root_path,
    'public/uploads/categories' => $root_path,
    'public/uploads/customization' => $root_path,
    'app/views/compiled' => $root_path,
    'logs' => $root_path
];

// Saída HTML
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status do Sistema - TAVERNA DA IMPRESSÃO</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 1200px; margin: 0 auto; }
        h1, h2, h3 { color: #333; margin-top: 25px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .container { margin-bottom: 40px; }
        .actions { margin-top: 30px; }
        .actions a { display: inline-block; padding: 10px 15px; background: #4CAF50; color: white; text-decoration: none; margin-right: 10px; border-radius: 4px; }
        .btn-danger { background: #f44336; }
        .btn-warning { background: #ff9800; }
    </style>
</head>
<body>
    <h1>Status do Sistema - TAVERNA DA IMPRESSÃO</h1>
    <p>Página de diagnóstico completo do sistema que verifica arquivos, configurações, banco de dados e muito mais.</p>
    
    <div class="container">
        <h2>Informações do Servidor</h2>
        <table>
            <tr>
                <th>Item</th>
                <th>Valor</th>
            </tr>
            <?php foreach ($server_info as $key => $value): ?>
            <tr>
                <td><?php echo htmlspecialchars($key); ?></td>
                <td><?php echo htmlspecialchars($value); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td>mod_rewrite Ativado</td>
                <td>
                    <?php if ($mod_rewrite_enabled === true): ?>
                        <span class="success">Sim</span>
                    <?php elseif ($mod_rewrite_enabled === false): ?>
                        <span class="error">Não</span> (Necessário para o sistema de roteamento)
                    <?php else: ?>
                        <span class="warning">Desconhecido</span> (Não foi possível verificar)
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="container">
        <h2>Extensões PHP</h2>
        <table>
            <tr>
                <th>Extensão</th>
                <th>Status</th>
            </tr>
            <?php foreach ($required_extensions as $ext): ?>
            <tr>
                <td><?php echo htmlspecialchars($ext); ?></td>
                <td>
                    <?php if (extension_loaded($ext)): ?>
                        <span class="success">Instalada</span>
                    <?php else: ?>
                        <span class="error">Não instalada</span> (Necessária para o funcionamento do sistema)
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="container">
        <h2>Configurações PHP</h2>
        <table>
            <tr>
                <th>Diretiva</th>
                <th>Valor</th>
            </tr>
            <?php foreach ($php_config as $key => $value): ?>
            <tr>
                <td><?php echo htmlspecialchars($key); ?></td>
                <td><?php echo htmlspecialchars($value); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="container">
        <h2>Verificação de Constantes</h2>
        <table>
            <tr>
                <th>Constante</th>
                <th>Definida</th>
                <th>Valor</th>
                <th>Tipo</th>
                <th>Status</th>
            </tr>
            <?php foreach ($check_constants as $constant => $info): ?>
            <tr>
                <td><?php echo htmlspecialchars($constant); ?></td>
                <td><?php echo $info['defined'] ? 'Sim' : 'Não'; ?></td>
                <td>
                    <?php 
                    if ($info['defined']) {
                        // Ocultar informações sensíveis
                        if (in_array($constant, ['DB_PASS'])) {
                            echo '********';
                        } else {
                            echo htmlspecialchars(is_string($info['value']) || is_numeric($info['value']) ? $info['value'] : var_export($info['value'], true));
                        }
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
                <td><?php echo $info['defined'] ? htmlspecialchars($info['type']) : '-'; ?></td>
                <td>
                    <?php
                    if (!$info['defined']) {
                        echo '<span class="error">Não definida</span>';
                    } else {
                        // Verificações específicas para cada constante
                        $status_class = 'success';
                        $status_text = 'OK';
                        
                        if ($constant === 'CURRENCY_SYMBOL' && !is_string($info['value'])) {
                            $status_class = 'error';
                            $status_text = 'Erro: Deveria ser string';
                        } elseif ($constant === 'ENVIRONMENT' && !in_array($info['value'], ['development', 'production'])) {
                            $status_class = 'warning';
                            $status_text = 'Aviso: Valor não reconhecido';
                        }
                        
                        echo '<span class="' . $status_class . '">' . $status_text . '</span>';
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <?php if (isset($check_constants['CURRENCY_SYMBOL']) && $check_constants['CURRENCY_SYMBOL']['defined'] && !is_string($check_constants['CURRENCY_SYMBOL']['value'])): ?>
        <div class="container">
            <h3>Problemas com CURRENCY_SYMBOL</h3>
            <p>A constante CURRENCY_SYMBOL está definida como <?php echo gettype($check_constants['CURRENCY_SYMBOL']['value']); ?> (<?php echo $check_constants['CURRENCY_SYMBOL']['value']; ?>) em vez de string. Isso pode causar problemas na exibição dos preços.</p>
            
            <p><strong>Solução:</strong> Adicione o seguinte código em <code>app/config/config.php</code> ou em um arquivo carregado antes:</p>
            
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow: auto;">
// Função auxiliar para obter o símbolo da moeda de forma segura
function getCurrencySymbol() {
    // Retornar diretamente a string 'R$' em vez de usar a constante
    // para garantir que o símbolo correto seja sempre exibido
    return 'R$';
}
            </pre>
            
            <p>E utilize <code>getCurrencySymbol()</code> em vez de <code>CURRENCY_SYMBOL</code> em todos os lugares do código.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="container">
        <h2>Arquivos Críticos do Sistema</h2>
        <table>
            <tr>
                <th>Arquivo</th>
                <th>Status</th>
                <th>Tamanho</th>
                <th>Última Modificação</th>
            </tr>
            <?php foreach ($critical_files as $file => $base): ?>
                <?php $check = check_file($file, $base); ?>
                <tr>
                    <td><?php echo htmlspecialchars($file); ?></td>
                    <td>
                        <?php if ($check['exists']): ?>
                            <span class="success">Existe (<?php echo $check['status']; ?>)</span>
                        <?php else: ?>
                            <span class="error">Não existe</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo isset($check['size']) ? number_format($check['size']) . ' bytes' : '-'; ?></td>
                    <td><?php echo isset($check['modified']) ? $check['modified'] : '-'; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="container">
        <h2>Arquivos de Modelo</h2>
        <table>
            <tr>
                <th>Arquivo</th>
                <th>Status</th>
                <th>Tamanho</th>
                <th>Última Modificação</th>
            </tr>
            <?php foreach ($model_files as $file => $base): ?>
                <?php $check = check_file($file, $base); ?>
                <tr>
                    <td><?php echo htmlspecialchars($file); ?></td>
                    <td>
                        <?php if ($check['exists']): ?>
                            <span class="success">Existe (<?php echo $check['status']; ?>)</span>
                        <?php else: ?>
                            <span class="error">Não existe</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo isset($check['size']) ? number_format($check['size']) . ' bytes' : '-'; ?></td>
                    <td><?php echo isset($check['modified']) ? $check['modified'] : '-'; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="container">
        <h2>Arquivos de Controlador</h2>
        <table>
            <tr>
                <th>Arquivo</th>
                <th>Status</th>
                <th>Tamanho</th>
                <th>Última Modificação</th>
            </tr>
            <?php foreach ($controller_files as $file => $base): ?>
                <?php $check = check_file($file, $base); ?>
                <tr>
                    <td><?php echo htmlspecialchars($file); ?></td>
                    <td>
                        <?php if ($check['exists']): ?>
                            <span class="success">Existe (<?php echo $check['status']; ?>)</span>
                        <?php else: ?>
                            <span class="error">Não existe</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo isset($check['size']) ? number_format($check['size']) . ' bytes' : '-'; ?></td>
                    <td><?php echo isset($check['modified']) ? $check['modified'] : '-'; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="container">
        <h2>Ferramentas de Diagnóstico</h2>
        <table>
            <tr>
                <th>Arquivo</th>
                <th>Status</th>
                <th>Tamanho</th>
                <th>Última Modificação</th>
                <th>Ação</th>
            </tr>
            <?php foreach ($diagnostic_files as $file => $base): ?>
                <?php $check = check_file($file, $base); ?>
                <tr>
                    <td><?php echo htmlspecialchars($file); ?></td>
                    <td>
                        <?php if ($check['exists']): ?>
                            <span class="success">Existe (<?php echo $check['status']; ?>)</span>
                        <?php else: ?>
                            <span class="error">Não existe</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo isset($check['size']) ? number_format($check['size']) . ' bytes' : '-'; ?></td>
                    <td><?php echo isset($check['modified']) ? $check['modified'] : '-'; ?></td>
                    <td>
                        <?php if ($check['exists']): ?>
                            <a href="/<?php echo htmlspecialchars(str_replace($root_path . '/', '', $base . '/' . $file)); ?>" target="_blank">Acessar</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <div class="container">
        <h2>Diretórios Importantes</h2>
        <table>
            <tr>
                <th>Diretório</th>
                <th>Caminho Absoluto</th>
                <th>Status</th>
                <th>Permissões</th>
            </tr>
            <?php foreach ($important_dirs as $dir => $base): ?>
                <?php $path = $base . '/' . $dir; ?>
                <tr>
                    <td><?php echo htmlspecialchars($dir); ?></td>
                    <td><?php echo htmlspecialchars($path); ?></td>
                    <td>
                        <?php if (is_dir($path)): ?>
                            <span class="success">Existe</span>
                        <?php else: ?>
                            <span class="error">Não existe</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        if (is_dir($path)) {
                            $perms = substr(sprintf('%o', fileperms($path)), -4);
                            echo $perms;
                            
                            if (!is_writable($path)) {
                                echo ' <span class="warning">(Não gravável)</span>';
                            }
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <?php if ($db_connection !== null): ?>
    <div class="container">
        <h2>Conexão com Banco de Dados</h2>
        <p>
            <?php if ($db_connection['success']): ?>
                <span class="success"><?php echo htmlspecialchars($db_connection['message']); ?></span>
            <?php else: ?>
                <span class="error"><?php echo htmlspecialchars($db_connection['message']); ?></span>
            <?php endif; ?>
        </p>
        
        <?php if ($db_tables !== null && $db_tables['success']): ?>
        <h3>Tabelas do Banco de Dados</h3>
        <table>
            <tr>
                <th>Tabela</th>
                <th>Status</th>
                <th>Número de Registros</th>
            </tr>
            <?php foreach ($db_tables['tables'] as $table => $status): ?>
            <tr>
                <td><?php echo htmlspecialchars($table); ?></td>
                <td>
                    <?php if ($status['exists']): ?>
                        <span class="success">Existe</span>
                    <?php else: ?>
                        <span class="error">Não existe: <?php echo htmlspecialchars($status['error']); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php echo $status['exists'] ? number_format($status['count']) : '-'; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="actions">
        <a href="/">Voltar para Home</a>
        <a href="/status.php?check_db=1">Verificar Banco de Dados</a>
        <a href="/diag.php">Ir para Diagnóstico Independente</a>
        <a href="/status.php?phpinfo=1">Ver phpinfo()</a>
        <a href="/verificar-constantes.php" class="btn-warning">Verificar Constantes</a>
    </div>
    
    <?php if (isset($_GET['phpinfo'])): ?>
    <div class="container">
        <h2>PHP Info</h2>
        <?php phpinfo(); ?>
    </div>
    <?php endif; ?>
</body>
</html>