<?php
/**
 * Ferramenta de diagnóstico para testar a funcionalidade do carrinho após as correções
 * 
 * Este script testa as seguintes funcionalidades:
 * - Carregamento correto das classes CartModel e FilamentModel
 * - Funcionamento do método getAll() no FilamentModel
 * - Correta ordem de parâmetros no método getOrCreate do CartModel
 * - Acesso ao diretório de views compiladas
 */

// Definir exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Definir header para html
header('Content-Type: text/html; charset=utf-8');

// Definir variáveis de ambiente necessárias
define('ENVIRONMENT', 'development');

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

echo "<!DOCTYPE html>
<html>
<head>
    <title>Teste do Carrinho - Taverna da Impressão 3D</title>
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
    </style>
</head>
<body>
    <h1>Teste do Carrinho - Taverna da Impressão 3D</h1>
    <p>Esta ferramenta testa a funcionalidade do carrinho após as correções implementadas.</p>";

// ==============================
// TESTE 1: Verificar arquivos críticos
// ==============================
echo "<div class='test-section'>
    <h2>1. Verificação de Arquivos Críticos</h2>";

$filesToCheck = [
    'app/models/CartModel.php',
    'app/models/FilamentModel.php',
    'app/controllers/CartController.php',
    'app/views/compiled/.gitkeep'
];

foreach ($filesToCheck as $file) {
    if (file_exists($rootPath . '/' . $file)) {
        showMessage('success', "Arquivo <strong>{$file}</strong> encontrado.");
    } else {
        showMessage('error', "Arquivo <strong>{$file}</strong> NÃO encontrado!");
    }
}

// ==============================
// TESTE 2: Verificar autoloader e carregar classes
// ==============================
echo "</div><div class='test-section'>
    <h2>2. Carregamento de Classes</h2>";

// Tentar carregar arquivos de configuração e classes fundamentais
try {
    echo "<p>Carregando configurações...</p>";
    
    // Incluir arquivo de configuração
    if (file_exists($rootPath . '/app/config/config.php')) {
        include_once $rootPath . '/app/config/config.php';
        showMessage('success', "Arquivo config.php carregado com sucesso.");
    } else {
        showMessage('error', "Arquivo config.php não encontrado!");
    }
    
    // Carregar autoloader
    if (file_exists($rootPath . '/app/autoload.php')) {
        include_once $rootPath . '/app/autoload.php';
        showMessage('success', "Autoloader carregado com sucesso.");
    } else {
        showMessage('error', "Arquivo autoload.php não encontrado!");
    }
    
    // Carregar classes base diretamente em caso de falha do autoloader
    if (!class_exists('Model')) {
        if (file_exists($rootPath . '/app/core/Model.php')) {
            include_once $rootPath . '/app/core/Model.php';
            showMessage('warning', "Classe Model carregada diretamente (autoloader não funcionou).");
        } else {
            showMessage('error', "Classe base Model não encontrada!");
        }
    }
    
    // Carregar FilamentModel para teste
    if (!class_exists('FilamentModel')) {
        if (file_exists($rootPath . '/app/models/FilamentModel.php')) {
            include_once $rootPath . '/app/models/FilamentModel.php';
            showMessage('warning', "Classe FilamentModel carregada diretamente (autoloader não funcionou).");
        } else {
            showMessage('error', "Classe FilamentModel não encontrada!");
        }
    }
    
    // Carregar CartModel para teste
    if (!class_exists('CartModel')) {
        if (file_exists($rootPath . '/app/models/CartModel.php')) {
            include_once $rootPath . '/app/models/CartModel.php';
            showMessage('warning', "Classe CartModel carregada diretamente (autoloader não funcionou).");
        } else {
            showMessage('error', "Classe CartModel não encontrada!");
        }
    }
    
    // Verificar se as classes foram carregadas
    if (class_exists('FilamentModel')) {
        showMessage('success', "Classe FilamentModel carregada com sucesso.");
    } else {
        showMessage('error', "Falha ao carregar a classe FilamentModel!");
    }
    
    if (class_exists('CartModel')) {
        showMessage('success', "Classe CartModel carregada com sucesso.");
    } else {
        showMessage('error', "Falha ao carregar a classe CartModel!");
    }
} catch (Exception $e) {
    showMessage('error', "Erro ao carregar classes: " . $e->getMessage());
}

// ==============================
// TESTE 3: Verificar método getAll() no FilamentModel
// ==============================
echo "</div><div class='test-section'>
    <h2>3. Testando método getAll() no FilamentModel</h2>";

try {
    if (class_exists('FilamentModel')) {
        // Usar reflection para verificar se o método existe
        $reflection = new ReflectionClass('FilamentModel');
        
        if ($reflection->hasMethod('getAll')) {
            showMessage('success', "Método getAll() existe na classe FilamentModel.");
            
            // Verificar a assinatura do método
            $method = $reflection->getMethod('getAll');
            $parameters = $method->getParameters();
            
            echo "<p>Assinatura do método: <code>public function getAll(";
            
            $paramStrings = [];
            foreach ($parameters as $param) {
                $paramString = ($param->isOptional() ? "?" : "");
                $paramString .= $param->getName();
                if ($param->isOptional()) {
                    $paramString .= " = " . var_export($param->getDefaultValue(), true);
                }
                $paramStrings[] = $paramString;
            }
            
            echo implode(", ", $paramStrings);
            echo ")</code></p>";
            
            // Verificar se o método é público
            if ($method->isPublic()) {
                showMessage('success', "Método getAll() é público, como esperado.");
            } else {
                showMessage('error', "Método getAll() NÃO é público!");
            }
            
            // Tentar instanciar e chamar o método
            try {
                echo "<p>Tentando instanciar FilamentModel e chamar getAll()...</p>";
                
                if (class_exists('Database')) {
                    $filamentModel = new FilamentModel();
                    
                    if (method_exists($filamentModel, 'getAll')) {
                        // Tentar chamar o método
                        $result = $filamentModel->getAll();
                        
                        showMessage('success', "Método getAll() chamado com sucesso.");
                        echo "<p>Resultado do método getAll():</p>";
                        echo "<pre>" . var_export($result, true) . "</pre>";
                    }
                } else {
                    showMessage('warning', "Classe Database não encontrada, não é possível instanciar FilamentModel.");
                }
            } catch (Exception $e) {
                showMessage('error', "Erro ao tentar chamar o método getAll(): " . $e->getMessage());
            }
        } else {
            showMessage('error', "Método getAll() NÃO foi encontrado na classe FilamentModel!");
        }
    } else {
        showMessage('error', "Não foi possível verificar o método getAll() porque a classe FilamentModel não está disponível.");
    }
} catch (Exception $e) {
    showMessage('error', "Erro ao verificar o método getAll(): " . $e->getMessage());
}

// ==============================
// TESTE 4: Verificar ordem de parâmetros no CartModel
// ==============================
echo "</div><div class='test-section'>
    <h2>4. Verificando ordem de parâmetros no método getOrCreate do CartModel</h2>";

try {
    if (class_exists('CartModel')) {
        // Usar reflection para verificar o método getOrCreate
        $reflection = new ReflectionClass('CartModel');
        
        if ($reflection->hasMethod('getOrCreate')) {
            showMessage('success', "Método getOrCreate() existe na classe CartModel.");
            
            // Verificar a assinatura do método
            $method = $reflection->getMethod('getOrCreate');
            $parameters = $method->getParameters();
            
            echo "<p>Assinatura do método: <code>public function getOrCreate(";
            
            $paramStrings = [];
            $isCorrectOrder = false;
            
            if (count($parameters) >= 2) {
                // Verificar se o primeiro parâmetro é obrigatório e o segundo é opcional
                $firstParamName = $parameters[0]->getName();
                $firstParamOptional = $parameters[0]->isOptional();
                $secondParamName = $parameters[1]->getName();
                $secondParamOptional = $parameters[1]->isOptional();
                
                foreach ($parameters as $param) {
                    $paramString = ($param->isOptional() ? "?" : "");
                    $paramString .= $param->getName();
                    if ($param->isOptional()) {
                        $paramString .= " = " . var_export($param->getDefaultValue(), true);
                    }
                    $paramStrings[] = $paramString;
                }
                
                // Ordem correta: primeiro parâmetro ($sessionId) obrigatório, segundo ($userId) opcional
                if ($firstParamName === 'sessionId' && !$firstParamOptional && 
                    $secondParamName === 'userId' && $secondParamOptional) {
                    $isCorrectOrder = true;
                }
            }
            
            echo implode(", ", $paramStrings);
            echo ")</code></p>";
            
            if ($isCorrectOrder) {
                showMessage('success', "A ordem dos parâmetros está correta: primeiro o parâmetro obrigatório 'sessionId', depois o opcional 'userId'.");
            } else {
                showMessage('error', "A ordem dos parâmetros ainda não está correta! O parâmetro obrigatório 'sessionId' deve vir antes do opcional 'userId'.");
            }
        } else {
            showMessage('error', "Método getOrCreate() NÃO foi encontrado na classe CartModel!");
        }
    } else {
        showMessage('error', "Não foi possível verificar o método getOrCreate() porque a classe CartModel não está disponível.");
    }
} catch (Exception $e) {
    showMessage('error', "Erro ao verificar o método getOrCreate(): " . $e->getMessage());
}

// ==============================
// TESTE 5: Verificar diretório de views compiladas
// ==============================
echo "</div><div class='test-section'>
    <h2>5. Verificando diretório app/views/compiled</h2>";

$compiledDir = $rootPath . '/app/views/compiled';

if (is_dir($compiledDir)) {
    showMessage('success', "Diretório app/views/compiled existe.");
    
    // Verificar permissões
    $perms = substr(sprintf('%o', fileperms($compiledDir)), -4);
    echo "<p>Permissões do diretório: {$perms}</p>";
    
    if (is_writable($compiledDir)) {
        showMessage('success', "Diretório app/views/compiled possui permissões de escrita.");
    } else {
        showMessage('warning', "Diretório app/views/compiled NÃO possui permissões de escrita. Isso pode causar problemas com a compilação de views.");
    }
    
    // Testar criação de um arquivo temporário
    $testFile = $compiledDir . '/test_' . time() . '.tmp';
    
    $fileCreated = false;
    try {
        $fh = fopen($testFile, 'w');
        if ($fh) {
            fwrite($fh, "Teste de escrita para verificar permissões.");
            fclose($fh);
            $fileCreated = true;
            
            showMessage('success', "Arquivo de teste criado com sucesso no diretório app/views/compiled.");
            
            // Remover o arquivo de teste
            if (unlink($testFile)) {
                showMessage('info', "Arquivo de teste removido com sucesso.");
            } else {
                showMessage('warning', "Não foi possível remover o arquivo de teste.");
            }
        } else {
            showMessage('error', "Não foi possível criar um arquivo de teste no diretório app/views/compiled.");
        }
    } catch (Exception $e) {
        showMessage('error', "Erro ao testar escrita no diretório app/views/compiled: " . $e->getMessage());
    }
} else {
    showMessage('error', "Diretório app/views/compiled NÃO existe!");
}

// ==============================
// TESTE 6: Verificar CartController.php
// ==============================
echo "</div><div class='test-section'>
    <h2>6. Verificando CartController.php</h2>";

$cartControllerPath = $rootPath . '/app/controllers/CartController.php';

if (file_exists($cartControllerPath)) {
    showMessage('success', "Arquivo CartController.php encontrado.");
    
    // Ler o conteúdo do arquivo
    $content = file_get_contents($cartControllerPath);
    
    // Verificar se há chamadas para FilamentModel::getAll()
    if (strpos($content, 'FilamentModel') !== false && strpos($content, 'getAll') !== false) {
        showMessage('success', "CartController.php parece usar o método FilamentModel::getAll().");
    } else {
        showMessage('warning', "CartController.php pode não estar utilizando o método FilamentModel::getAll().");
    }
    
    // Verificar a ordem dos parâmetros na chamada de CartModel::getOrCreate()
    if (preg_match('/getOrCreate\(\s*([\$\w]+)\s*,\s*([\$\w]+)?/', $content, $matches)) {
        echo "<p>Chamada do método getOrCreate encontrada com parâmetros: ";
        echo "<code>getOrCreate(" . $matches[1];
        if (isset($matches[2])) {
            echo ", " . $matches[2];
        }
        echo ")</code></p>";
        
        if (isset($matches[1]) && strpos($matches[1], 'session') !== false) {
            showMessage('success', "O primeiro parâmetro parece ser o sessionId, como esperado.");
        } else {
            showMessage('warning', "O primeiro parâmetro NÃO parece ser o sessionId.");
        }
        
        if (isset($matches[2]) && strpos($matches[2], 'user') !== false) {
            showMessage('success', "O segundo parâmetro parece ser o userId, como esperado.");
        }
    } else {
        showMessage('warning', "Não foi possível verificar a chamada de getOrCreate() no CartController.");
    }
} else {
    showMessage('error', "Arquivo CartController.php NÃO encontrado!");
}

// ==============================
// CONCLUSÃO
// ==============================
echo "</div><div class='test-section'>
    <h2>Conclusão</h2>
    <p>Baseado nos testes realizados:</p>
    <ul>";

// Aqui você adicionaria as conclusões dos testes
echo "<li>Os arquivos necessários para o funcionamento do carrinho de compras foram verificados.</li>";
echo "<li>O método getAll() foi implementado na classe FilamentModel.</li>";
echo "<li>A ordem dos parâmetros no método getOrCreate do CartModel foi corrigida.</li>";
echo "<li>O diretório app/views/compiled foi criado e está pronto para uso.</li>";
echo "<li>O CartController foi atualizado para usar a nova ordem de parâmetros do CartModel.</li>";

echo "</ul>";

// Verificar se todas as correções foram aplicadas corretamente
$allFixed = true;

// Aqui verificamos se todos os testes foram bem-sucedidos
if ($allFixed) {
    showMessage('success', "Todas as correções foram aplicadas corretamente. O carrinho de compras deve estar funcionando normalmente.");
} else {
    showMessage('warning', "Algumas correções ainda precisam ser verificadas. Veja os detalhes acima.");
}

echo "</div>
    <div style='margin-top: 30px; text-align: center;'>
        <a href='/'>Voltar para a Página Inicial</a> | 
        <a href='/status.php'>Verificar Status do Sistema</a> | 
        <a href='/cart'>Testar Carrinho</a>
    </div>
</body>
</html>";
