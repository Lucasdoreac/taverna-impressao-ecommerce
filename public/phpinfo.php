<?php
// Este arquivo deve ser removido após a resolução dos problemas

// Verificar autenticação básica para segurança
$auth_user = 'admin';
$auth_pass = 'TavernaAdmin2025'; // Alterar em produção e remover após diagnóstico

if ((!isset($_SERVER['PHP_AUTH_USER']) || 
     $_SERVER['PHP_AUTH_USER'] !== $auth_user || 
     $_SERVER['PHP_AUTH_PW'] !== $auth_pass) && 
    !isset($_GET['noauth'])) {
    header('WWW-Authenticate: Basic realm="Diagnóstico Taverna"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Autenticação necessária';
    exit;
}

// Para diagnóstico em caso de problemas de autenticação
if (isset($_GET['noauth'])) {
    echo '<h1>Página de diagnóstico sem autenticação</h1>';
    echo '<p>IMPORTANTE: Esta página deve ser acessada apenas para diagnóstico inicial. Remova o parâmetro "noauth" após o teste.</p>';
}

// Cabeçalho
echo '<html><head><title>Diagnóstico TAVERNA DA IMPRESSÃO</title>';
echo '<style>
    body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 1200px; margin: 0 auto; }
    h1, h2 { color: #333; }
    h2 { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { text-align: left; padding: 8px; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .success { color: green; }
    .warning { color: orange; }
    .error { color: red; }
    pre { background: #f5f5f5; padding: 10px; overflow: auto; }
</style>';
echo '</head><body>';

echo '<h1>Diagnóstico TAVERNA DA IMPRESSÃO</h1>';
echo '<p>Esta página exibe informações de diagnóstico do servidor e configurações. <strong>Deve ser removida após a configuração.</strong></p>';

// Variáveis do servidor relevantes para roteamento
echo '<h2>Variáveis de Servidor Importantes para Roteamento</h2>';
echo '<table>';
echo '<tr><th>Variável</th><th>Valor</th></tr>';
$server_vars = [
    'SERVER_NAME',
    'SERVER_ADDR',
    'SERVER_PORT',
    'DOCUMENT_ROOT',
    'SCRIPT_FILENAME',
    'SCRIPT_NAME',
    'REQUEST_URI',
    'QUERY_STRING',
    'PHP_SELF',
    'HTTP_HOST'
];

foreach ($server_vars as $var) {
    echo '<tr><td>' . $var . '</td><td>' . (isset($_SERVER[$var]) ? htmlspecialchars($_SERVER[$var]) : '<em>não definido</em>') . '</td></tr>';
}
echo '</table>';

// Teste de configuração de roteamento
echo '<h2>Teste de Roteamento</h2>';
echo '<p>Verificando se o módulo mod_rewrite está habilitado e configurado:</p>';

// Verificar se mod_rewrite está carregado
$mod_rewrite = in_array('mod_rewrite', apache_get_modules());
echo 'mod_rewrite: ' . ($mod_rewrite ? '<span class="success">Habilitado</span>' : '<span class="error">Não habilitado</span>');

// Verificação dos arquivos .htaccess
echo '<h2>Verificação de Arquivos .htaccess</h2>';
echo '<table>';
echo '<tr><th>Arquivo</th><th>Status</th></tr>';

$htaccess_root = file_exists(dirname(dirname(__FILE__)) . '/.htaccess');
$htaccess_public = file_exists(dirname(__FILE__) . '/.htaccess');

echo '<tr><td>/.htaccess (raiz)</td><td>' . 
     ($htaccess_root ? '<span class="success">Existe</span>' : '<span class="error">Não existe</span>') . 
     '</td></tr>';
     
echo '<tr><td>/public/.htaccess</td><td>' . 
     ($htaccess_public ? '<span class="success">Existe</span>' : '<span class="error">Não existe</span>') . 
     '</td></tr>';
echo '</table>';

// Verificar diretórios e permissões
echo '<h2>Diretórios e Permissões</h2>';
echo '<table>';
echo '<tr><th>Diretório</th><th>Existe</th><th>Permissões</th></tr>';

$dirs = [
    'app/controllers',
    'app/models',
    'app/views',
    'app/helpers',
    'public/uploads',
    'logs'
];

$root_path = dirname(dirname(__FILE__));

foreach ($dirs as $dir) {
    $full_path = $root_path . '/' . $dir;
    $exists = is_dir($full_path);
    $perms = $exists ? substr(sprintf('%o', fileperms($full_path)), -4) : 'N/A';
    $writable = $exists ? (is_writable($full_path) ? 'Sim' : 'Não') : 'N/A';
    
    echo '<tr>';
    echo '<td>' . $dir . '</td>';
    echo '<td>' . ($exists ? '<span class="success">Sim</span>' : '<span class="error">Não</span>') . '</td>';
    echo '<td>' . $perms . ' (Gravável: ' . $writable . ')</td>';
    echo '</tr>';
}
echo '</table>';

// Verificação do banco de dados
echo '<h2>Conexão com Banco de Dados</h2>';

// Carregar configurações
require_once dirname(dirname(__FILE__)) . '/app/config/config.php';

// Testes
echo '<table>';
echo '<tr><th>Teste</th><th>Resultado</th></tr>';

// Teste PDO
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = DB_OPTIONS ?? [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo '<tr><td>Conexão PDO</td><td><span class="success">Sucesso</span></td></tr>';
    
    // Verificar tabelas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo '<tr><td>Tabelas no banco</td><td><span class="success">' . count($tables) . ' tabelas encontradas</span></td></tr>';
    
    // Listar tabelas
    echo '<tr><td>Lista de tabelas</td><td>';
    echo implode(', ', $tables);
    echo '</td></tr>';
    
} catch (PDOException $e) {
    echo '<tr><td>Conexão PDO</td><td><span class="error">Falha: ' . $e->getMessage() . '</span></td></tr>';
}

echo '</table>';

// Informações PHP
echo '<h2>Informações do PHP</h2>';
echo '<table>';
echo '<tr><th>Item</th><th>Valor</th></tr>';
echo '<tr><td>Versão do PHP</td><td>' . phpversion() . '</td></tr>';
echo '<tr><td>Extensões carregadas</td><td>' . implode(', ', get_loaded_extensions()) . '</td></tr>';
echo '<tr><td>Limites de memória</td><td>' . ini_get('memory_limit') . '</td></tr>';
echo '<tr><td>Tempo máximo de execução</td><td>' . ini_get('max_execution_time') . ' segundos</td></tr>';
echo '<tr><td>Tamanho máximo de upload</td><td>' . ini_get('upload_max_filesize') . '</td></tr>';
echo '<tr><td>Tamanho máximo de POST</td><td>' . ini_get('post_max_size') . '</td></tr>';
echo '</table>';

// Logger info
echo '<h2>Logs</h2>';
$log_dir = dirname(dirname(__FILE__)) . '/logs';
$log_files = [];

if (is_dir($log_dir)) {
    $logs = scandir($log_dir);
    foreach ($logs as $log) {
        if ($log != '.' && $log != '..' && is_file($log_dir . '/' . $log)) {
            $log_files[] = $log;
        }
    }
}

if (count($log_files) > 0) {
    echo '<p>Arquivos de log disponíveis:</p>';
    echo '<ul>';
    foreach ($log_files as $log) {
        echo '<li>' . $log . ' - ' . filesize($log_dir . '/' . $log) . ' bytes</li>';
    }
    echo '</ul>';
    
    // Mostrar último log
    if (count($log_files) > 0) {
        $latest_log = $log_dir . '/' . $log_files[count($log_files) - 1];
        echo '<h3>Últimas entradas de log</h3>';
        echo '<pre>';
        if (filesize($latest_log) > 10000) {
            // Mostrar apenas as últimas 100 linhas
            $lines = file($latest_log);
            echo implode('', array_slice($lines, -100));
        } else {
            echo file_get_contents($latest_log);
        }
        echo '</pre>';
    }
} else {
    echo '<p>Nenhum arquivo de log encontrado. Verifique as permissões do diretório de logs.</p>';
}

// Links de diagnóstico
echo '<h2>Links de Diagnóstico</h2>';
echo '<ul>';
echo '<li><a href="/">Homepage</a> - Deve carregar a página inicial</li>';
echo '<li><a href="/login">Login</a> - Deve carregar a página de login</li>';
echo '<li><a href="/produtos">Produtos</a> - Deve carregar a lista de produtos</li>';
echo '<li><a href="/admin">Admin</a> - Deve carregar o painel administrativo</li>';
echo '</ul>';

echo '</body></html>';

// Evitar exibição do phpinfo() completo por questões de segurança
if (isset($_GET['fullinfo']) && $_GET['fullinfo'] === 'yes') {
    echo '<hr><h2>PHP Info Completo</h2>';
    phpinfo();
}
