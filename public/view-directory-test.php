<?php
/**
 * Ferramenta de diagnóstico para testar a funcionalidade do diretório app/views/compiled
 * 
 * Este script testa as seguintes funcionalidades:
 * - Existência do diretório app/views/compiled
 * - Permissões corretas do diretório
 * - Capacidade de escrita no diretório
 * - Criação e leitura de arquivos de teste
 */

// Definir exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Definir header para html
header('Content-Type: text/html; charset=utf-8');

// Função utilitária para exibir mensagens formatadas
function showMessage($type, $message) {
    $colors = [
        'success' => 'green',
        'error' => 'red',
        'info' => 'blue',
        'warning' => 'orange'
    ];
    
    echo "<div style=\"color: {$colors[$type]}; margin: 10px 0; padding: 10px; border: 1px solid {$colors[$type]}; border-radius: 5px;\">";
    echo "<strong>".strtoupper($type).":</strong> {$message}";
    echo "</div>";
}

// Obter caminho absoluto para o diretório raiz
$rootPath = dirname(__FILE__, 2);
$compiledDir = $rootPath . '/app/views/compiled';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Teste do Diretório de Views Compiladas - Taverna da Impressão 3D</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 1000px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px; }
        h2 { color: #444; margin-top: 25px; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow: auto; }
        .test-section { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .warning { color: orange; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Teste do Diretório de Views Compiladas - Taverna da Impressão 3D</h1>
    <p>Esta ferramenta verifica se o diretório <code>app/views/compiled</code> está configurado corretamente.</p>";

// ==============================
// TESTE 1: Verificar existência do diretório
// ==============================
echo "<div class='test-section'>
    <h2>1. Verificação da Existência do Diretório</h2>";

if (is_dir($compiledDir)) {
    showMessage('success', "O diretório <code>app/views/compiled</code> existe.");
    
    // Verificar arquivo .gitkeep
    if (file_exists($compiledDir . '/.gitkeep')) {
        showMessage('success', "O arquivo <code>.gitkeep</code> está presente, garantindo que o diretório seja versionado corretamente.");
    } else {
        showMessage('warning', "O arquivo <code>.gitkeep</code> não foi encontrado. Recomenda-se adicionar este arquivo para garantir que o diretório seja versionado corretamente.");
    }
} else {
    showMessage('error', "O diretório <code>app/views/compiled</code> NÃO existe!");
    
    // Tentar criar o diretório automaticamente
    try {
        if (mkdir($compiledDir, 0755, true)) {
            showMessage('success', "O diretório <code>app/views/compiled</code> foi criado automaticamente.");
            
            // Criar arquivo .gitkeep
            if (file_put_contents($compiledDir . '/.gitkeep', '') !== false) {
                showMessage('success', "O arquivo <code>.gitkeep</code> foi criado automaticamente.");
            } else {
                showMessage('error', "Não foi possível criar o arquivo <code>.gitkeep</code>.");
            }
        } else {
            showMessage('error', "Não foi possível criar o diretório <code>app/views/compiled</code> automaticamente.");
        }
    } catch (Exception $e) {
        showMessage('error', "Erro ao tentar criar o diretório: " . $e->getMessage());
    }
}

// ==============================
// TESTE 2: Verificar permissões do diretório
// ==============================
echo "</div><div class='test-section'>
    <h2>2. Verificação das Permissões do Diretório</h2>";

if (is_dir($compiledDir)) {
    // Obter informações detalhadas
    $perms = substr(sprintf('%o', fileperms($compiledDir)), -4);
    $owner = function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($compiledDir))['name'] : fileowner($compiledDir);
    $group = function_exists('posix_getgrgid') ? posix_getgrgid(filegroup($compiledDir))['name'] : filegroup($compiledDir);
    
    echo "<table>
        <tr>
            <th>Propriedade</th>
            <th>Valor</th>
        </tr>
        <tr>
            <td>Permissões</td>
            <td><code>{$perms}</code></td>
        </tr>
        <tr>
            <td>Proprietário</td>
            <td><code>{$owner}</code></td>
        </tr>
        <tr>
            <td>Grupo</td>
            <td><code>{$group}</code></td>
        </tr>
    </table>";
    
    // Verificar permissões adequadas
    if (is_writable($compiledDir)) {
        showMessage('success', "O diretório <code>app/views/compiled</code> possui permissões de escrita.");
    } else {
        showMessage('error', "O diretório <code>app/views/compiled</code> NÃO possui permissões de escrita!");
        
        // Tentar corrigir permissões
        try {
            if (chmod($compiledDir, 0755)) {
                showMessage('success', "As permissões do diretório foram corrigidas para <code>0755</code>.");
                
                // Verificar novamente
                if (is_writable($compiledDir)) {
                    showMessage('success', "O diretório agora possui permissões de escrita.");
                } else {
                    showMessage('warning', "O diretório ainda não possui permissões de escrita após a correção.");
                }
            } else {
                showMessage('error', "Não foi possível corrigir as permissões do diretório.");
            }
        } catch (Exception $e) {
            showMessage('error', "Erro ao tentar corrigir permissões: " . $e->getMessage());
        }
    }
} else {
    showMessage('error', "Não é possível verificar permissões porque o diretório não existe.");
}

// ==============================
// TESTE 3: Testar escrita e leitura no diretório
// ==============================
echo "</div><div class='test-section'>
    <h2>3. Teste de Escrita e Leitura no Diretório</h2>";

if (is_dir($compiledDir) && is_writable($compiledDir)) {
    // Criar arquivo de teste
    $testFile = $compiledDir . '/test_' . time() . '.tmp';
    $testContent = "Este é um arquivo de teste criado em " . date('Y-m-d H:i:s') . "\nPara verificar a funcionalidade do diretório app/views/compiled.";
    
    try {
        if (file_put_contents($testFile, $testContent) !== false) {
            showMessage('success', "Arquivo de teste criado com sucesso em <code>{$testFile}</code>.");
            
            // Ler o arquivo de teste
            if (($readContent = file_get_contents($testFile)) !== false) {
                showMessage('success', "Arquivo de teste lido com sucesso.");
                
                // Verificar conteúdo
                if ($readContent === $testContent) {
                    showMessage('success', "O conteúdo lido corresponde ao conteúdo escrito.");
                } else {
                    showMessage('error', "O conteúdo lido NÃO corresponde ao conteúdo escrito!");
                }
                
                // Mostrar conteúdo
                echo "<pre>" . htmlspecialchars($readContent) . "</pre>";
                
                // Remover arquivo de teste
                if (unlink($testFile)) {
                    showMessage('success', "Arquivo de teste removido com sucesso.");
                } else {
                    showMessage('warning', "Não foi possível remover o arquivo de teste.");
                }
            } else {
                showMessage('error', "Não foi possível ler o arquivo de teste!");
            }
        } else {
            showMessage('error', "Não foi possível criar o arquivo de teste!");
        }
    } catch (Exception $e) {
        showMessage('error', "Erro durante o teste de escrita/leitura: " . $e->getMessage());
    }
} else {
    showMessage('error', "Não é possível realizar teste de escrita porque o diretório não existe ou não possui permissões adequadas.");
}

// ==============================
// TESTE 4: Simulação de criação de template compilado
// ==============================
echo "</div><div class='test-section'>
    <h2>4. Simulação de Criação de Template Compilado</h2>";

if (is_dir($compiledDir) && is_writable($compiledDir)) {
    // Template de exemplo
    $templateContent = <<<'EOT'
<div class="product-card">
    <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
    <h3><?php echo $product['name']; ?></h3>
    <p class="price"><?php echo getCurrencySymbol(); ?> <?php echo number_format($product['price'], 2, ',', '.'); ?></p>
    <div class="actions">
        <button class="add-to-cart" data-id="<?php echo $product['id']; ?>">Adicionar ao Carrinho</button>
    </div>
</div>
EOT;

    // Versão compilada simulada
    $compiledContent = <<<'EOT'
<?php
// Template compilado automaticamente em <?php echo date('Y-m-d H:i:s'); ?>
?>
<div class="product-card">
    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
    <p class="price"><?php echo getCurrencySymbol(); ?> <?php echo number_format($product['price'], 2, ',', '.'); ?></p>
    <div class="actions">
        <button class="add-to-cart" data-id="<?php echo (int)$product['id']; ?>">Adicionar ao Carrinho</button>
    </div>
</div>
EOT;

    // Salvar ambos os arquivos
    $templateFile = $compiledDir . '/product_card_template.php';
    $compiledFile = $compiledDir . '/product_card_compiled_' . time() . '.php';
    
    try {
        if (file_put_contents($templateFile, $templateContent) !== false) {
            showMessage('success', "Arquivo de template de exemplo criado com sucesso em <code>{$templateFile}</code>.");
            
            if (file_put_contents($compiledFile, $compiledContent) !== false) {
                showMessage('success', "Arquivo compilado de exemplo criado com sucesso em <code>{$compiledFile}</code>.");
                
                // Mostrar o processo
                echo "<div style='margin: 20px 0;'>";
                echo "<h3>Template Original:</h3>";
                echo "<pre>" . htmlspecialchars($templateContent) . "</pre>";
                
                echo "<h3>Versão Compilada:</h3>";
                echo "<pre>" . htmlspecialchars($compiledContent) . "</pre>";
                echo "</div>";
                
                // Remover os arquivos após o teste
                unlink($templateFile);
                unlink($compiledFile);
                showMessage('info', "Arquivos de exemplo removidos após o teste.");
            } else {
                showMessage('error', "Não foi possível criar o arquivo compilado de exemplo!");
            }
        } else {
            showMessage('error', "Não foi possível criar o arquivo de template de exemplo!");
        }
    } catch (Exception $e) {
        showMessage('error', "Erro durante a simulação de compilação: " . $e->getMessage());
    }
} else {
    showMessage('error', "Não é possível realizar a simulação porque o diretório não existe ou não possui permissões adequadas.");
}

// ==============================
// CONCLUSÃO
// ==============================
echo "</div><div class='test-section'>
    <h2>Conclusão</h2>";

$success = is_dir($compiledDir) && is_writable($compiledDir);

if ($success) {
    showMessage('success', "O diretório <code>app/views/compiled</code> existe e está configurado corretamente.");
    echo "<p>Este diretório está pronto para ser usado pelo sistema de templates da aplicação para armazenar versões compiladas dos templates.</p>";
} else {
    showMessage('error', "O diretório <code>app/views/compiled</code> NÃO está configurado corretamente. Verifique os detalhes acima.");
    echo "<p>Ações recomendadas:</p>";
    echo "<ol>";
    
    if (!is_dir($compiledDir)) {
        echo "<li>Criar o diretório <code>app/views/compiled</code></li>";
        echo "<li>Adicionar um arquivo <code>.gitkeep</code> para garantir que o diretório seja versionado</li>";
    }
    
    if (is_dir($compiledDir) && !is_writable($compiledDir)) {
        echo "<li>Ajustar as permissões do diretório para <code>0755</code> ou similares que permitam escrita</li>";
        echo "<li>Verificar o proprietário/grupo do diretório para garantir que o servidor web tenha acesso de escrita</li>";
    }
    
    echo "</ol>";
}

echo "</div>
    <div style='margin-top: 30px; text-align: center;'>
        <a href='/'>Voltar para a Página Inicial</a> | 
        <a href='/status.php'>Verificar Status do Sistema</a> | 
        <a href='/cart-test.php'>Testar Carrinho</a> |
        <a href='/checkout-test.php'>Testar Checkout</a>
    </div>
</body>
</html>";
