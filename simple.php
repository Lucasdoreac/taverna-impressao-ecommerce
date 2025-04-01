<?php
// Arquivo extremamente simples de diagnóstico na raiz do projeto
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Simples - Raiz</title>
</head>
<body>
    <h1>Diagnóstico Simples na Raiz</h1>
    <p>Este é um arquivo de diagnóstico ultra simplificado na raiz do projeto.</p>
    
    <h2>Informações do Servidor</h2>
    <pre>
PHP Version: <?php echo phpversion(); ?>

Document Root: <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Não disponível'; ?>

Script Path: <?php echo $_SERVER['SCRIPT_FILENAME'] ?? 'Não disponível'; ?>

URI: <?php echo $_SERVER['REQUEST_URI'] ?? 'Não disponível'; ?>

Server Name: <?php echo $_SERVER['SERVER_NAME'] ?? 'Não disponível'; ?>

Server Software: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Não disponível'; ?>
    </pre>
    
    <h2>Diretórios e Caminhos</h2>
    <pre>
__DIR__: <?php echo __DIR__; ?>

dirname(__DIR__): <?php echo dirname(__DIR__); ?>

__FILE__: <?php echo __FILE__; ?>

getcwd(): <?php echo getcwd(); ?>
    </pre>
    
    <h2>Verificação de Arquivos Críticos</h2>
    <table border="1" cellpadding="5">
        <tr>
            <th>Arquivo</th>
            <th>Existe</th>
            <th>Tamanho</th>
        </tr>
        <?php
        $files_to_check = [
            '.htaccess',
            'public/.htaccess',
            'public/index.php',
            'app/config/config.php',
            'app/config/routes.php',
            'app/helpers/Router.php',
            'app/core/Router.php'
        ];
        
        foreach ($files_to_check as $file) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($file) . "</td>";
            
            if (file_exists($file)) {
                echo "<td style='color:green'>Sim</td>";
                echo "<td>" . filesize($file) . " bytes</td>";
            } else {
                echo "<td style='color:red'>Não</td>";
                echo "<td>-</td>";
            }
            
            echo "</tr>";
        }
        ?>
    </table>
    
    <h2>Configurações PHP</h2>
    <pre>
display_errors: <?php echo ini_get('display_errors'); ?>

error_reporting: <?php echo ini_get('error_reporting'); ?>

memory_limit: <?php echo ini_get('memory_limit'); ?>

max_execution_time: <?php echo ini_get('max_execution_time'); ?>
    </pre>
    
    <p>Data e hora atual: <?php echo date('Y-m-d H:i:s'); ?></p>
    
    <?php if (isset($_GET['phpinfo'])): ?>
    <h2>PHP Info</h2>
    <?php phpinfo(); ?>
    <?php endif; ?>
    
    <p><a href="simple.php?phpinfo=1">Ver phpinfo()</a></p>
</body>
</html>