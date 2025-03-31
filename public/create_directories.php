<?php
/**
 * Script para criar diretórios de upload ausentes
 * 
 * Este script deve ser executado uma vez no servidor para criar os diretórios
 * necessários para os uploads de arquivos.
 * 
 * ATENÇÃO: Remova este arquivo do servidor após a execução para evitar
 * problemas de segurança.
 */

// Inicializar log de resultados
$log = [];
$success = true;

// Definir caminho raiz dos uploads se não estiver definido
if (!defined('UPLOADS_PATH')) {
    // Obtém o caminho base do script
    $basePath = dirname(__DIR__);
    define('UPLOADS_PATH', $basePath . '/public/uploads');
    $log[] = "UPLOADS_PATH definido como: " . UPLOADS_PATH;
}

// Diretórios a serem criados
$directories = [
    UPLOADS_PATH . '/products',
    UPLOADS_PATH . '/categories',
    UPLOADS_PATH . '/customization',
    UPLOADS_PATH . '/customization/thumbs',
    UPLOADS_PATH . '/users',
    UPLOADS_PATH . '/temp'
];

// Função para criar diretório e definir permissões
function createDirectoryWithPermissions($path) {
    global $log, $success;
    
    // Verificar se o diretório já existe
    if (is_dir($path)) {
        $log[] = "✓ Diretório já existe: $path";
        return true;
    }
    
    // Tentar criar o diretório
    if (mkdir($path, 0755, true)) {
        $log[] = "✓ Diretório criado com sucesso: $path";
        
        // Verificar se foi possível definir permissões
        if (chmod($path, 0755)) {
            $log[] = "  ↳ Permissões definidas para 755";
        } else {
            $log[] = "  ⚠️ Não foi possível definir permissões para 755";
        }
        
        return true;
    } else {
        $log[] = "❌ ERRO: Não foi possível criar o diretório: $path";
        $success = false;
        return false;
    }
}

// Criar diretório principal de uploads
createDirectoryWithPermissions(UPLOADS_PATH);

// Criar cada subdiretório
foreach ($directories as $directory) {
    createDirectoryWithPermissions($directory);
}

// Criar arquivo .htaccess para proteger o diretório de uploads
$htaccessPath = UPLOADS_PATH . '/.htaccess';
$htaccessContent = <<<EOT
# Denies direct access to image/file extensions except thumbnails and previews
<FilesMatch "\.(stl|obj|raw|3mf|gcode|zip)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>

# Allow processing of PHP files if needed for thumbnail generation
<FilesMatch "\.php$">
    <IfModule mod_authz_core.c>
        Require all granted
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Allow from all
    </IfModule>
</FilesMatch>

# Prevent access to sensitive files
<FilesMatch "^\.">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>
EOT;

if (file_put_contents($htaccessPath, $htaccessContent)) {
    $log[] = "✓ Arquivo .htaccess criado em: $htaccessPath";
} else {
    $log[] = "❌ ERRO: Não foi possível criar arquivo .htaccess em: $htaccessPath";
    $success = false;
}

// Verificação final
if ($success) {
    $log[] = "✅ Todos os diretórios foram criados com sucesso!";
    $log[] = "⚠️ IMPORTANTE: Exclua este arquivo após a verificação.";
} else {
    $log[] = "⚠️ Ocorreram erros durante a criação dos diretórios. Verifique as permissões.";
}

// Exibir resultado
header("Content-Type: text/html; charset=utf-8");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criação de Diretórios de Upload</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .success {
            color: #4CAF50;
            font-weight: bold;
        }
        .error {
            color: #F44336;
            font-weight: bold;
        }
        .warning {
            color: #FF9800;
            font-weight: bold;
        }
        pre {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .security-notice {
            background-color: #FFECB3;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>Criação de Diretórios de Upload</h1>
    
    <div class="<?php echo $success ? 'success' : 'error'; ?>">
        <?php echo $success ? '✅ Processo concluído com sucesso!' : '❌ Ocorreram erros no processo!'; ?>
    </div>
    
    <h2>Log de Operações:</h2>
    <pre><?php echo implode("\n", $log); ?></pre>
    
    <div class="security-notice">
        <p class="warning">⚠️ AVISO DE SEGURANÇA</p>
        <p>Este script deve ser removido do servidor após a execução para evitar possíveis problemas de segurança.</p>
        <p>Você pode excluí-lo manualmente via FTP ou painel de controle da hospedagem.</p>
    </div>
    
    <h2>Próximos Passos:</h2>
    <ol>
        <li>Verifique se todos os diretórios foram criados corretamente</li>
        <li>Remova este arquivo do servidor</li>
        <li>Teste o upload de arquivos no sistema</li>
    </ol>
</body>
</html>
