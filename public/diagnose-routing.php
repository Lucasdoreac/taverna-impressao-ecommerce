<?php
/**
 * diagnose-routing.php - Ferramenta de diagnóstico de roteamento independente do framework
 * 
 * Esta ferramenta verifica os componentes críticos do sistema de roteamento
 * e fornece informações detalhadas sobre possíveis problemas, sem depender
 * do funcionamento correto do próprio framework.
 */

// Configuração básica
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');

// Inicializar variáveis para rastreamento do fluxo
$diagLog = [];
$issues = [];
$projectRoot = dirname(__DIR__);

// Função para registrar informações de diagnóstico
function logDiag($message, $type = 'info') {
    global $diagLog;
    $diagLog[] = ['type' => $type, 'message' => $message];
    error_log("[DIAGNOSE-ROUTING] [$type] $message");
}

// Função para registrar problemas
function logIssue($message, $severity = 'error', $recommendation = null) {
    global $issues;
    $issues[] = [
        'severity' => $severity,
        'message' => $message,
        'recommendation' => $recommendation
    ];
    logDiag($message, $severity);
}

// Função para verificar se um arquivo existe
function checkFile($path, $description, $required = true) {
    global $projectRoot;
    $fullPath = $projectRoot . '/' . $path;
    
    if (file_exists($fullPath)) {
        logDiag("$description encontrado em: $path");
        return true;
    } else {
        $severity = $required ? 'error' : 'warning';
        $message = "$description não encontrado em: $path";
        $recommendation = $required 
            ? "Verifique se o arquivo existe e se os caminhos estão configurados corretamente."
            : "Este arquivo não é obrigatório, mas pode melhorar o funcionamento do sistema.";
        
        logIssue($message, $severity, $recommendation);
        return false;
    }
}

// Função para verificar se uma constante está definida
function checkConstant($name, $description) {
    if (defined($name)) {
        $value = constant($name);
        logDiag("$description definida como: " . (is_string($value) ? "'$value'" : $value));
        return true;
    } else {
        logIssue(
            "$description não está definida", 
            'error', 
            "Verifique o arquivo config.php e certifique-se de que a constante $name está sendo definida corretamente."
        );
        return false;
    }
}

// Função para verificar o carregamento de uma classe
function checkClass($class, $description, $requiredForRouting = true) {
    if (class_exists($class)) {
        logDiag("$description carregada com sucesso");
        return true;
    } else {
        $severity = $requiredForRouting ? 'error' : 'warning';
        logIssue(
            "$description não está disponível", 
            $severity, 
            "Verifique se o arquivo da classe está presente e se o autoloader está funcionando corretamente."
        );
        return false;
    }
}

// Verificar ambiente e obter informações do servidor
logDiag("Início da verificação de diagnóstico de roteamento");
logDiag("PHP Version: " . phpversion());
logDiag("Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Não disponível'));
logDiag("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Não disponível'));
logDiag("Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'Não disponível'));

// 1. Verificar arquivos críticos
logDiag("=== Verificando arquivos críticos ===", 'section');
$configExists = checkFile('app/config/config.php', 'Arquivo de configuração');
$routesExists = checkFile('app/config/routes.php', 'Arquivo de rotas');
$autoloadExists = checkFile('app/autoload.php', 'Autoloader');
$routerExists = checkFile('app/helpers/Router.php', 'Classe de roteamento');
$indexExists = checkFile('public/index.php', 'Arquivo de entrada');
$htaccessExists = checkFile('.htaccess', 'Arquivo .htaccess principal');
$publicHtaccessExists = checkFile('public/.htaccess', 'Arquivo .htaccess da pasta public');

// Verificar arquivos de classes cruciais
$controllerExists = checkFile('app/core/Controller.php', 'Classe base Controller');
$modelExists = checkFile('app/core/Model.php', 'Classe base Model');
$databaseExists = checkFile('app/helpers/Database.php', 'Classe Database');

// 2. Carregar configurações se disponíveis
logDiag("=== Carregando configurações ===", 'section');
if ($configExists) {
    try {
        include_once $projectRoot . '/app/config/config.php';
        logDiag("Arquivo config.php carregado com sucesso");
        
        // Verificar constantes críticas
        checkConstant('APP_PATH', 'Caminho da aplicação');
        checkConstant('VIEWS_PATH', 'Caminho das views');
        checkConstant('BASE_URL', 'URL base');
        
        // Verificar CURRENCY_SYMBOL que causou problemas
        if (defined('CURRENCY_SYMBOL')) {
            $symbol = CURRENCY_SYMBOL;
            $type = gettype($symbol);
            logDiag("CURRENCY_SYMBOL definido como: " . $symbol . " (tipo: $type)");
            
            if ($type !== 'string') {
                logIssue(
                    "CURRENCY_SYMBOL não é uma string, é um $type", 
                    'error', 
                    "Modifique a definição de CURRENCY_SYMBOL no config.php para garantir que seja definido como uma string: define('CURRENCY_SYMBOL', 'R$');"
                );
            }
        }
    } catch (Exception $e) {
        logIssue(
            "Erro ao carregar config.php: " . $e->getMessage(),
            'error',
            "Verifique se o arquivo config.php está sintaticamente correto e não contém erros."
        );
    }
}

// 3. Tentar carregar autoloader e verificar classes
logDiag("=== Verificando autoloader e classes ===", 'section');
if ($autoloadExists) {
    try {
        include_once $projectRoot . '/app/autoload.php';
        logDiag("Autoloader carregado com sucesso");
        
        // Verificar classes críticas para o roteamento
        checkClass('Controller', 'Classe base Controller');
        checkClass('Router', 'Classe de roteamento');
        checkClass('Database', 'Classe de banco de dados');
        
        // Verificar outras classes importantes
        checkClass('ProductModel', 'Modelo de produtos', false);
        checkClass('CategoryModel', 'Modelo de categorias', false);
        
        // Listar classes disponíveis
        $declaredClasses = get_declared_classes();
        $appClasses = array_filter($declaredClasses, function($class) {
            return !strpos($class, '\\') && 
                   !in_array($class, ['stdClass', 'Exception', 'Error', 'PDO', 'DateTime']);
        });
        
        logDiag("Classes disponíveis: " . implode(', ', $appClasses));
    } catch (Exception $e) {
        logIssue(
            "Erro ao carregar autoloader: " . $e->getMessage(),
            'error',
            "Verifique o autoloader.php para garantir que está sintaticamente correto."
        );
    }
}

// 4. Verificar configuração de roteamento
logDiag("=== Analisando configuração de roteamento ===", 'section');
if ($routesExists) {
    try {
        // Analisar o conteúdo sem executar
        $routesContent = file_get_contents($projectRoot . '/app/config/routes.php');
        
        // Verificar se a variável $routes é definida
        if (strpos($routesContent, '$routes') !== false) {
            logDiag("Arquivo de rotas define a variável \$routes");
            
            // Contar aproximadamente o número de rotas
            $routeCount = substr_count($routesContent, '=>');
            logDiag("Aproximadamente $routeCount rotas definidas");
            
            // Verificar rotas básicas
            $essentialRoutes = ['/', '/produtos', '/produto/:slug', '/categoria/:slug'];
            foreach ($essentialRoutes as $route) {
                if (strpos($routesContent, "'$route'") !== false || strpos($routesContent, "\"$route\"") !== false) {
                    logDiag("Rota essencial encontrada: $route");
                } else {
                    logIssue(
                        "Rota essencial não encontrada: $route",
                        'warning',
                        "Adicione esta rota ao arquivo routes.php"
                    );
                }
            }
        } else {
            logIssue(
                "Arquivo de rotas não define a variável \$routes",
                'error',
                "Garanta que o arquivo routes.php define a variável \$routes como um array"
            );
        }
    } catch (Exception $e) {
        logIssue(
            "Erro ao analisar arquivo de rotas: " . $e->getMessage(),
            'error',
            "Verifique o arquivo routes.php para garantir que está sintaticamente correto."
        );
    }
}

// 5. Verificar configuração do .htaccess
logDiag("=== Analisando configuração do .htaccess ===", 'section');
if ($publicHtaccessExists) {
    try {
        $htaccessContent = file_get_contents($projectRoot . '/public/.htaccess');
        
        // Verificar regras de rewrite
        if (strpos($htaccessContent, 'RewriteEngine On') !== false) {
            logDiag("RewriteEngine está ativado no .htaccess");
        } else {
            logIssue(
                "RewriteEngine não está ativado no .htaccess",
                'error',
                "Adicione 'RewriteEngine On' ao arquivo .htaccess"
            );
        }
        
        // Verificar regra principal de rewrite para index.php
        if (strpos($htaccessContent, 'RewriteRule ^(.*)$ index.php') !== false) {
            logDiag("Regra de rewrite para index.php encontrada");
        } else {
            logIssue(
                "Regra de rewrite para index.php não encontrada ou pode estar incorreta",
                'error',
                "Verifique se há uma regra RewriteRule que direciona para index.php"
            );
        }
        
        // Verificar condições de rewrite
        if (strpos($htaccessContent, 'RewriteCond %{REQUEST_FILENAME} !-f') !== false &&
            strpos($htaccessContent, 'RewriteCond %{REQUEST_FILENAME} !-d') !== false) {
            logDiag("Condições de rewrite para verificar arquivos e diretórios encontradas");
        } else {
            logIssue(
                "Condições de rewrite para verificar arquivos e diretórios não encontradas",
                'warning',
                "Adicione 'RewriteCond %{REQUEST_FILENAME} !-f' e 'RewriteCond %{REQUEST_FILENAME} !-d' antes da regra de rewrite para index.php"
            );
        }
    } catch (Exception $e) {
        logIssue(
            "Erro ao analisar .htaccess: " . $e->getMessage(),
            'error',
            "Verifique o arquivo .htaccess para garantir que está sintaticamente correto."
        );
    }
}

// 6. Verificar permissões de diretórios
logDiag("=== Verificando permissões de diretórios ===", 'section');
$dirsToCheck = [
    '' => 'Raiz do projeto',
    'app' => 'Diretório da aplicação',
    'app/core' => 'Diretório de classes base',
    'app/controllers' => 'Diretório de controladores',
    'app/models' => 'Diretório de modelos',
    'app/views' => 'Diretório de views',
    'app/helpers' => 'Diretório de helpers',
    'public' => 'Diretório público',
];

foreach ($dirsToCheck as $dir => $description) {
    $path = $projectRoot . ($dir ? "/$dir" : '');
    if (is_dir($path)) {
        $perms = fileperms($path);
        $permsOctal = substr(sprintf('%o', $perms), -4);
        logDiag("$description - Permissões: $permsOctal");
        
        if (($perms & 0x0400) || ($perms & 0x0100) || ($perms & 0x0040)) { // Owner/Group/World Read
            // Permissões adequadas
        } else {
            logIssue(
                "$description não tem permissões de leitura adequadas",
                'error',
                "Ajuste as permissões para pelo menos 0755 (drwxr-xr-x)"
            );
        }
    } else {
        logIssue(
            "$description não existe ou não é um diretório: $path",
            'error',
            "Verifique a estrutura de diretórios do projeto"
        );
    }
}

// 7. Verificar arquivo index.php
logDiag("=== Analisando arquivo index.php ===", 'section');
if ($indexExists) {
    try {
        $indexContent = file_get_contents($projectRoot . '/public/index.php');
        
        // Verificar carregamento do config
        if (strpos($indexContent, "require_once __DIR__ . '/../app/config/config.php'") !== false) {
            logDiag("index.php carrega o arquivo de configuração");
        } else {
            logIssue(
                "index.php pode não estar carregando corretamente o arquivo de configuração",
                'error',
                "Verifique se index.php contém algo como: require_once __DIR__ . '/../app/config/config.php'"
            );
        }
        
        // Verificar carregamento do autoloader
        if (strpos($indexContent, "require_once __DIR__ . '/../app/autoload.php'") !== false) {
            logDiag("index.php carrega o autoloader");
        } else {
            logIssue(
                "index.php pode não estar carregando corretamente o autoloader",
                'error',
                "Verifique se index.php contém algo como: require_once __DIR__ . '/../app/autoload.php'"
            );
        }
        
        // Verificar inicialização do roteador
        if (strpos($indexContent, 'new Router') !== false && 
            strpos($indexContent, 'dispatch') !== false) {
            logDiag("index.php inicializa o roteador e chama o método dispatch");
        } else {
            logIssue(
                "index.php pode não estar inicializando corretamente o roteador",
                'error',
                "Verifique se index.php contém algo como: \$router = new Router(); \$router->dispatch();"
            );
        }
    } catch (Exception $e) {
        logIssue(
            "Erro ao analisar index.php: " . $e->getMessage(),
            'error',
            "Verifique o arquivo index.php para garantir que está sintaticamente correto."
        );
    }
}

// 8. Verificar possíveis problemas na classe Router
logDiag("=== Analisando classe Router ===", 'section');
if ($routerExists) {
    try {
        $routerContent = file_get_contents($projectRoot . '/app/helpers/Router.php');
        
        // Verificar se o Router acessa a variável global $routes
        if (strpos($routerContent, 'global $routes') !== false) {
            logDiag("Router acessa a variável global \$routes");
        } else {
            logIssue(
                "Router pode não estar acessando corretamente a variável global \$routes",
                'warning',
                "Verifique se a classe Router usa 'global \$routes' para acessar as rotas definidas"
            );
        }
        
        // Verificar o método dispatch
        if (strpos($routerContent, 'function dispatch') !== false) {
            logDiag("Router possui o método dispatch");
        } else {
            logIssue(
                "Router pode não ter o método dispatch definido",
                'error',
                "Verifique se a classe Router possui um método dispatch"
            );
        }
        
        // Verificar extração de URI
        if (strpos($routerContent, 'getUri') !== false) {
            logDiag("Router possui um método para extrair a URI");
        } else {
            logIssue(
                "Router pode não ter um método para extrair a URI",
                'warning',
                "Verifique se a classe Router possui um método para extrair a URI da requisição"
            );
        }
        
        // Verificar a chamada para controladores
        if (strpos($routerContent, 'callAction') !== false) {
            logDiag("Router possui um método para chamar ações de controladores");
        } else {
            logIssue(
                "Router pode não ter um método para chamar ações de controladores",
                'error',
                "Verifique se a classe Router possui um método para chamar ações de controladores"
            );
        }
    } catch (Exception $e) {
        logIssue(
            "Erro ao analisar Router.php: " . $e->getMessage(),
            'error',
            "Verifique o arquivo Router.php para garantir que está sintaticamente correto."
        );
    }
}

// 9. Análise de problemas identificados
logDiag("=== Resumo dos problemas ===", 'section');
$errorCount = 0;
$warningCount = 0;

foreach ($issues as $issue) {
    if ($issue['severity'] === 'error') {
        $errorCount++;
    } else if ($issue['severity'] === 'warning') {
        $warningCount++;
    }
}

logDiag("Total de problemas identificados: " . count($issues));
logDiag("Erros críticos: $errorCount");
logDiag("Avisos: $warningCount");

// 10. Geração de recomendações finais
logDiag("=== Recomendações ===", 'section');
if ($errorCount === 0 && $warningCount === 0) {
    logDiag("Nenhum problema identificado no sistema de roteamento!");
} else {
    // Recomendar soluções para problemas comuns
    if (!$autoloadExists || !checkClass('Controller', '', false) || !checkClass('Router', '', false)) {
        logDiag("1. Verificar o autoloader e garantir que ele está carregando corretamente as classes necessárias.", 'recommendation');
    }
    
    if (!$routesExists || !$publicHtaccessExists) {
        logDiag("2. Verificar a configuração de rotas e o arquivo .htaccess da pasta public.", 'recommendation');
    }
    
    if (isset($symbol) && gettype($symbol) !== 'string') {
        logDiag("3. Corrigir o problema com a constante CURRENCY_SYMBOL, garantindo que seja definida como string.", 'recommendation');
    }
    
    logDiag("4. Verificar os logs de erro do servidor para identificar problemas específicos de PHP.", 'recommendation');
    logDiag("5. Utilizar as ferramentas de diagnóstico específicas para classes e constantes.", 'recommendation');
}

// Exibir resultados em HTML
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Roteamento - TAVERNA DA IMPRESSÃO</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2, h3 { 
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .log-entry {
            margin-bottom: 5px;
            padding: 8px 12px;
            border-radius: 4px;
        }
        .log-info {
            background-color: #f0f8ff;
            border-left: 4px solid #007bff;
        }
        .log-error {
            background-color: #fff0f0;
            border-left: 4px solid #dc3545;
        }
        .log-warning {
            background-color: #fff9e6;
            border-left: 4px solid #ffc107;
        }
        .log-success {
            background-color: #f0fff0;
            border-left: 4px solid #28a745;
        }
        .log-section {
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
            font-weight: bold;
            margin-top: 15px;
        }
        .log-recommendation {
            background-color: #e6f7ff;
            border-left: 4px solid #17a2b8;
            font-weight: bold;
        }
        .issue {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 4px;
        }
        .issue-error {
            background-color: #fff0f0;
            border: 1px solid #ffcccc;
        }
        .issue-warning {
            background-color: #fff9e6;
            border: 1px solid #ffe0b2;
        }
        .issue-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .issue-recommendation {
            margin-top: 10px;
            font-style: italic;
            color: #28a745;
        }
        .summary {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow: auto;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Diagnóstico de Roteamento - TAVERNA DA IMPRESSÃO</h1>
        
        <div class="summary">
            <h3>Resumo</h3>
            <p>Total de problemas identificados: <strong><?= count($issues) ?></strong></p>
            <p>Erros críticos: <strong><?= $errorCount ?></strong></p>
            <p>Avisos: <strong><?= $warningCount ?></strong></p>
            
            <?php if ($errorCount > 0): ?>
                <div class="alert alert-danger">
                    <strong>Atenção!</strong> Foram encontrados erros críticos que afetam o funcionamento do sistema de roteamento.
                </div>
            <?php elseif ($warningCount > 0): ?>
                <div class="alert alert-warning">
                    <strong>Atenção!</strong> Foram encontrados avisos que podem afetar o funcionamento do sistema.
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <strong>Sucesso!</strong> Nenhum problema identificado no sistema de roteamento.
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (count($issues) > 0): ?>
            <h2>Problemas Identificados</h2>
            
            <?php foreach ($issues as $issue): ?>
                <div class="issue issue-<?= $issue['severity'] ?>">
                    <div class="issue-title">
                        <?= $issue['severity'] === 'error' ? '⛔ Erro' : '⚠️ Aviso' ?>: 
                        <?= htmlspecialchars($issue['message']) ?>
                    </div>
                    
                    <?php if ($issue['recommendation']): ?>
                        <div class="issue-recommendation">
                            <strong>Recomendação:</strong> <?= htmlspecialchars($issue['recommendation']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <h2>Recomendações Gerais</h2>
            <ol>
                <?php
                $foundRecommendations = false;
                foreach ($diagLog as $entry) {
                    if ($entry['type'] === 'recommendation') {
                        $foundRecommendations = true;
                        echo '<li>' . htmlspecialchars($entry['message']) . '</li>';
                    }
                }
                
                if (!$foundRecommendations) {
                    echo '<li>Corrija os problemas listados acima seguindo as recomendações específicas.</li>';
                    echo '<li>Consulte os logs de erro do servidor para mais detalhes (geralmente em /var/log/apache2/error.log ou similar).</li>';
                    echo '<li>Utilize as ferramentas de diagnóstico específicas: diagnóstico-classes.php e verificar-constantes.php.</li>';
                }
                ?>
            </ol>
            
            <?php
            // Exibir possíveis soluções específicas para problemas comuns
            if (!$autoloadExists || !checkClass('Controller', '', false) || !checkClass('Router', '', false)) {
            ?>
                <h3>Solução para problemas de autoload</h3>
                <p>Verificar o arquivo autoload.php e confirmar que as classes estão sendo carregadas corretamente:</p>
                <pre>
// Verificar se o autoloader está registrando a função corretamente
spl_autoload_register('app_autoload');

// Verificar se a função app_autoload está procurando nos diretórios corretos
function app_autoload($class) {
    $directories = [
        APP_PATH . '/models/',
        APP_PATH . '/controllers/',
        APP_PATH . '/helpers/',
        APP_PATH . '/core/',
        // Caminhos alternativos
        dirname(APP_PATH) . '/app/models/',
        dirname(APP_PATH) . '/app/controllers/',
        dirname(APP_PATH) . '/app/helpers/',
        dirname(APP_PATH) . '/app/core/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    return false;
}
                </pre>
            <?php
            }
            
            if (isset($symbol) && gettype($symbol) !== 'string') {
            ?>
                <h3>Solução para problema da constante CURRENCY_SYMBOL</h3>
                <p>Adicione o seguinte código ao arquivo config.php:</p>
                <pre>
// Restaurar CURRENCY_SYMBOL com definição explícita como string
if (defined('CURRENCY_SYMBOL')) {
    // Não podemos remover constantes definidas, mas podemos evitar redefini-la
    error_log("ATENÇÃO: CURRENCY_SYMBOL já está definido com valor: " . CURRENCY_SYMBOL);
    error_log("Tipo atual: " . gettype(CURRENCY_SYMBOL));
} else {
    // Define apenas se não estiver definido ainda
    define('CURRENCY_SYMBOL', 'R$');  // Valor correto como string literal
}

// Função para obter o símbolo da moeda de forma segura
function getCurrencySymbol() {
    // Retornar diretamente a string 'R$' em vez de usar a constante
    return 'R$';
}
                </pre>
            <?php
            }
            ?>
        <?php endif; ?>
        
        <h2>Log de Diagnóstico Detalhado</h2>
        <?php foreach ($diagLog as $entry): ?>
            <div class="log-entry log-<?= $entry['type'] ?>">
                <?= htmlspecialchars($entry['message']) ?>
            </div>
        <?php endforeach; ?>
        
        <div class="alert alert-warning" style="margin-top: 30px;">
            <strong>IMPORTANTE:</strong> Por segurança, remova este arquivo após concluir o diagnóstico.
        </div>
    </div>
</body>
</html>
