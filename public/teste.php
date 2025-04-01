<?php
// Arquivo extremamente simples para diagnóstico
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Simples</title>
</head>
<body>
    <h1>Teste de Acesso Direto</h1>
    <p>Se você está vendo este conteúdo, o acesso direto a arquivos PHP na pasta public está funcionando.</p>
    
    <h2>Informações do Servidor</h2>
    <pre>
    PHP Version: <?php echo phpversion(); ?>
    
    Document Root: <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Não disponível'; ?>
    
    Script Path: <?php echo $_SERVER['SCRIPT_FILENAME'] ?? 'Não disponível'; ?>
    
    URI: <?php echo $_SERVER['REQUEST_URI'] ?? 'Não disponível'; ?>
    </pre>
    
    <p>Data e hora atual: <?php echo date('Y-m-d H:i:s'); ?></p>
</body>
</html>