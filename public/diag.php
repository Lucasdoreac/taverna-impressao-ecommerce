<?php
/**
 * Diagnóstico Independente - Não requer sistema de roteamento ou autoloader
 * 
 * Este arquivo é projetado para funcionar mesmo quando o sistema principal está com problemas.
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

// Obter caminhos básicos
$root_path = dirname(__DIR__); // Um nível acima de public
$public_path = __DIR__;
$app_path = $root_path . '/app';

// Verificar arquivos críticos
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

// Adicionais arquivos de diagnóstico
$diagnostic_files = [
    'public/diagnostico-classes.php' => $root_path,
    'public/verificar-constantes.php' => $root_path,
    'public/status.php' => $root_path
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

// Verificar se o mod_rewrite está disponível
$mod_rewrite_enabled = in_array('mod_rewrite', apache_get_modules());

// Saída HTML
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Independente - TAVERNA DA IMPRESSÃO</title>
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
    </style>
</head>
<body>
    <h1>Diagnóstico Independente - TAVERNA DA IMPRESSÃO</h1>
    <p>Este diagnóstico independente não requer o sistema de roteamento ou autoloader.</p>
    
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
                    <?php if ($mod_rewrite_enabled): ?>
                        <span class="success">Sim</span>
                    <?php else: ?>
                        <span class="error">Não</span> (Necessário para o sistema de roteamento)
                    <?php endif; ?>
                </td>
            </tr>
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
        <h2>Arquivos de Diagnóstico</h2>
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
            </tr>
            <tr>
                <td>Root Path</td>
                <td><?php echo htmlspecialchars($root_path); ?></td>
                <td>
                    <?php if (is_dir($root_path)): ?>
                        <span class="success">Existe</span>
                    <?php else: ?>
                        <span class="error">Não existe</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>Public Path</td>
                <td><?php echo htmlspecialchars($public_path); ?></td>
                <td>
                    <?php if (is_dir($public_path)): ?>
                        <span class="success">Existe</span>
                    <?php else: ?>
                        <span class="error">Não existe</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>App Path</td>
                <td><?php echo htmlspecialchars($app_path); ?></td>
                <td>
                    <?php if (is_dir($app_path)): ?>
                        <span class="success">Existe</span>
                    <?php else: ?>
                        <span class="error">Não existe</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>App/Helpers</td>
                <td><?php echo htmlspecialchars($app_path . '/helpers'); ?></td>
                <td>
                    <?php if (is_dir($app_path . '/helpers')): ?>
                        <span class="success">Existe</span>
                    <?php else: ?>
                        <span class="error">Não existe</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>App/Core</td>
                <td><?php echo htmlspecialchars($app_path . '/core'); ?></td>
                <td>
                    <?php if (is_dir($app_path . '/core')): ?>
                        <span class="success">Existe</span>
                    <?php else: ?>
                        <span class="error">Não existe</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="container">
        <h2>Conteúdo do .htaccess Principal</h2>
        <?php
        $htaccess_path = $root_path . '/.htaccess';
        if (file_exists($htaccess_path) && is_readable($htaccess_path)) {
            echo '<pre>' . htmlspecialchars(file_get_contents($htaccess_path)) . '</pre>';
        } else {
            echo '<p class="error">Não foi possível ler o arquivo .htaccess principal.</p>';
        }
        ?>
    </div>
    
    <div class="container">
        <h2>Conteúdo do .htaccess Public</h2>
        <?php
        $htaccess_path = $public_path . '/.htaccess';
        if (file_exists($htaccess_path) && is_readable($htaccess_path)) {
            echo '<pre>' . htmlspecialchars(file_get_contents($htaccess_path)) . '</pre>';
        } else {
            echo '<p class="error">Não foi possível ler o arquivo .htaccess na pasta public.</p>';
        }
        ?>
    </div>
    
    <div class="actions">
        <a href="/">Voltar para Home</a>
        <a href="/diag.php?phpinfo=1">Ver phpinfo()</a>
    </div>
    
    <?php if (isset($_GET['phpinfo'])): ?>
    <div class="container">
        <h2>PHP Info</h2>
        <?php phpinfo(); ?>
    </div>
    <?php endif; ?>
</body>
</html>