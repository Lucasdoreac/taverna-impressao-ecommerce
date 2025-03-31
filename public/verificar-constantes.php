<?php
/**
 * Ferramenta para verificar e corrigir a definição de constantes críticas
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Definir cabeçalhos para página HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Constantes - TAVERNA DA IMPRESSÃO</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow: auto; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Verificação de Constantes - TAVERNA DA IMPRESSÃO</h1>
    
    <h2>Estado Atual</h2>
    <?php
    // Lista de constantes críticas para verificar
    $criticalConstants = [
        'ENVIRONMENT', 
        'ROOT_PATH', 
        'APP_PATH', 
        'VIEWS_PATH', 
        'UPLOADS_PATH',
        'CURRENCY',
        'CURRENCY_SYMBOL'
    ];
    
    echo "<table>";
    echo "<tr><th>Constante</th><th>Definida</th><th>Valor</th><th>Tipo</th></tr>";
    
    foreach ($criticalConstants as $constant) {
        echo "<tr>";
        echo "<td>{$constant}</td>";
        
        $defined = defined($constant);
        echo "<td>" . ($defined ? '<span class="success">Sim</span>' : '<span class="error">Não</span>') . "</td>";
        
        if ($defined) {
            $value = constant($constant);
            echo "<td>" . htmlspecialchars(is_string($value) ? $value : var_export($value, true)) . "</td>";
            echo "<td>" . gettype($value) . "</td>";
        } else {
            echo "<td>-</td>";
            echo "<td>-</td>";
        }
        
        echo "</tr>";
    }
    
    echo "</table>";

    // Carregar config.php
    $configPath = dirname(__DIR__) . '/app/config/config.php';
    ?>
    
    <h2>Análise do Config</h2>
    <?php
    if (file_exists($configPath)) {
        echo "<p class='success'>✓ Arquivo config.php encontrado em {$configPath}</p>";
        
        $configContent = file_get_contents($configPath);
        
        // Procurar por definições de constantes críticas
        $constantsInConfig = [];
        
        foreach ($criticalConstants as $constant) {
            if (preg_match("/define\(['\"]" . preg_quote($constant, '/') . "['\"],\s*(.*?)\);/s", $configContent, $matches)) {
                $constantsInConfig[$constant] = $matches[1];
                echo "<p>Definição de {$constant} encontrada: " . htmlspecialchars($matches[0]) . "</p>";
            } else {
                echo "<p class='warning'>! Definição de {$constant} não encontrada no config.php</p>";
            }
        }
        
        // Verificar especificamente CURRENCY_SYMBOL
        if (isset($constantsInConfig['CURRENCY_SYMBOL'])) {
            if (strpos($constantsInConfig['CURRENCY_SYMBOL'], "'R$'") !== false || 
                strpos($constantsInConfig['CURRENCY_SYMBOL'], '"R$"') !== false) {
                echo "<p class='success'>✓ CURRENCY_SYMBOL está sendo definido corretamente como string 'R$'</p>";
            } else {
                echo "<p class='error'>✗ CURRENCY_SYMBOL NÃO está sendo definido como string 'R$'</p>";
            }
        }
    } else {
        echo "<p class='error'>✗ Arquivo config.php NÃO encontrado em {$configPath}</p>";
    }
    ?>
    
    <h2>Teste de Carregamento</h2>
    <?php
    // Recarregar config.php e verificar se os valores estão corretos
    if (file_exists($configPath)) {
        echo "<p>Tentando incluir config.php novamente...</p>";
        
        // Salvar valores antigos para comparação
        $oldValues = [];
        foreach ($criticalConstants as $constant) {
            if (defined($constant)) {
                $oldValues[$constant] = constant($constant);
            }
        }
        
        // Incluir config.php novamente
        include_once $configPath;
        
        echo "<table>";
        echo "<tr><th>Constante</th><th>Valor Antes</th><th>Valor Depois</th><th>Alterado</th></tr>";
        
        foreach ($criticalConstants as $constant) {
            echo "<tr>";
            echo "<td>{$constant}</td>";
            
            $oldValue = isset($oldValues[$constant]) ? 
                (is_string($oldValues[$constant]) ? htmlspecialchars($oldValues[$constant]) : var_export($oldValues[$constant], true)) : 
                "Não definido";
            
            $newValue = defined($constant) ? 
                (is_string(constant($constant)) ? htmlspecialchars(constant($constant)) : var_export(constant($constant), true)) : 
                "Não definido";
            
            $changed = $oldValue !== $newValue;
            
            echo "<td>{$oldValue}</td>";
            echo "<td>{$newValue}</td>";
            echo "<td>" . ($changed ? '<span class="warning">Sim</span>' : 'Não') . "</td>";
            
            echo "</tr>";
        }
        
        echo "</table>";
    }
    ?>
    
    <h2>Correção de CURRENCY_SYMBOL</h2>
    <?php
    // Verificar se CURRENCY_SYMBOL é um número
    if (defined('CURRENCY_SYMBOL') && is_int(CURRENCY_SYMBOL)) {
        echo "<p class='error'>✗ CURRENCY_SYMBOL ainda está definido como inteiro: " . CURRENCY_SYMBOL . "</p>";
        
        // Exibir solução
        echo "<h3>Solução recomendada:</h3>";
        echo "<p>1. Verifique se há outras definições de CURRENCY_SYMBOL além da que está em config.php</p>";
        echo "<p>2. Adicione este código no início do config.php ou em um arquivo carregado antes:</p>";
        
        echo "<pre>";
        echo "// Verificar se CURRENCY_SYMBOL já está definido como inteiro
if (defined('CURRENCY_SYMBOL') && is_int(CURRENCY_SYMBOL)) {
    // Não podemos remover a constante, mas podemos fornecer um getter alternativo
    function getCurrencySymbol() {
        return 'R$';
    }
    
    // Log para diagnóstico
    error_log('CURRENCY_SYMBOL detectado como inteiro. Usando função getCurrencySymbol() como alternativa');
} else if (!defined('CURRENCY_SYMBOL')) {
    // Definir apenas se não estiver definido
    define('CURRENCY_SYMBOL', 'R$');
}";
        echo "</pre>";
        
        echo "<p>3. Certifique-se de usar getCurrencySymbol() em vez de CURRENCY_SYMBOL diretamente no código</p>";
    } else if (defined('CURRENCY_SYMBOL') && is_string(CURRENCY_SYMBOL)) {
        echo "<p class='success'>✓ CURRENCY_SYMBOL está definido corretamente como string: '" . CURRENCY_SYMBOL . "'</p>";
    } else {
        echo "<p class='warning'>! CURRENCY_SYMBOL não está definido ou tem um tipo inesperado</p>";
    }
    
    // Verificar e exibir o uso da função getCurrencySymbol
    if (function_exists('getCurrencySymbol')) {
        echo "<p class='success'>✓ Função getCurrencySymbol() está disponível</p>";
        echo "<p>getCurrencySymbol() retorna: '" . getCurrencySymbol() . "'</p>";
    } else {
        echo "<p class='warning'>! Função getCurrencySymbol() não está disponível</p>";
    }
    ?>
    
    <p><strong>IMPORTANTE:</strong> Por segurança, remova este arquivo após concluir o diagnóstico.</p>
</body>
</html>
