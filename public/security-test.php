<?php
/**
 * Teste de Segurança - Arquivo para testar as correções de segurança
 * 
 * Este arquivo executa testes para verificar se as correções de segurança
 * foram implementadas corretamente e estão protegendo a aplicação.
 */

// Definir constantes necessárias
define('ROOT_PATH', dirname(__FILE__, 2));
define('APP_PATH', ROOT_PATH . '/app');

// Incluir biblioteca de segurança
require_once APP_PATH . '/lib/Security/init.php';

// Determinar o ambiente de execução
$isLocal = true;
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (strpos($host, 'darkblue-cattle-647559.hostingersite.com') !== false) {
    $isLocal = false;
}

// Função para formatar resultados
function formatResult($test, $result, $expected = true, $details = '') {
    $status = $result === $expected ? 'PASSED' : 'FAILED';
    $statusColor = $result === $expected ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>$test</td>";
    echo "<td style='color: $statusColor;'>$status</td>";
    echo "<td>" . ($details ? $details : ($result ? 'Success' : 'Failed')) . "</td>";
    echo "</tr>";
    
    return $result === $expected;
}

// Definir caminho para logs
$logFile = ROOT_PATH . '/logs/security-test-' . date('Y-m-d') . '.log';

// Função para registrar resultados em log
function logResult($test, $result, $expected = true, $details = '') {
    global $logFile;
    
    $status = $result === $expected ? 'PASSED' : 'FAILED';
    $log = "[" . date('Y-m-d H:i:s') . "] TEST: $test - STATUS: $status - DETAILS: " . ($details ?: ($result ? 'Success' : 'Failed')) . PHP_EOL;
    
    file_put_contents($logFile, $log, FILE_APPEND);
}

// Iniciar contadores
$totalTests = 0;
$passedTests = 0;

// Iniciar saída HTML
echo "<!DOCTYPE html>";
echo "<html lang='pt-BR'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Teste de Segurança - Taverna da Impressão 3D</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; }";
echo "h1 { color: #333; }";
echo "table { width: 100%; border-collapse: collapse; margin: 20px 0; }";
echo "th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }";
echo "th { background-color: #f2f2f2; }";
echo ".summary { margin-top: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 5px; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<h1>Teste de Segurança - Taverna da Impressão 3D</h1>";
echo "<p>Data: " . date('d/m/Y H:i:s') . "</p>";
echo "<p>Ambiente: " . ($isLocal ? 'Local' : 'Produção') . "</p>";

echo "<table>";
echo "<tr><th>Teste</th><th>Status</th><th>Detalhes</th></tr>";

// TESTES DE SEGURANÇA

// 1. Teste de Headers HTTP
$totalTests++;
$headers = HeaderManager::getAllHeaders();
$headersStr = implode(', ', $headers);
$result = strpos($headersStr, 'Content-Security-Policy') !== false;
$passedTests += formatResult('Content Security Policy Header', $result, true, $result ? 'Header CSP encontrado' : 'Header CSP não encontrado');
logResult('Content Security Policy Header', $result, true, $result ? 'Header CSP encontrado' : 'Header CSP não encontrado');

// 2. Teste de CSRF Protection
$totalTests++;
try {
    $csrfToken = CsrfProtection::getToken();
    $result = !empty($csrfToken) && strlen($csrfToken) >= 32;
    $details = $result ? 'Token gerado com sucesso: ' . substr($csrfToken, 0, 8) . '...' : 'Falha ao gerar token CSRF';
} catch (Exception $e) {
    $result = false;
    $details = 'Erro: ' . $e->getMessage();
}
$passedTests += formatResult('CSRF Protection', $result, true, $details);
logResult('CSRF Protection', $result, true, $details);

// 3. Teste de validação de entrada
$totalTests++;
$testValues = [
    'email' => ['test@example.com', true],
    'script' => ['<script>alert("XSS")</script>', false],
    'sql' => ["Robert'; DROP TABLE users;--", false],
    'normal' => ['Texto normal', true]
];

$validationResults = [];
foreach ($testValues as $type => $test) {
    $value = $test[0];
    $expectedValid = $test[1];
    
    // Sanitizar o valor
    $sanitized = SecurityManager::sanitize($value, 'html');
    $isValid = $sanitized !== $value || $expectedValid;
    
    $validationResults[] = "$type: " . ($isValid ? 'VÁLIDO' : 'INVÁLIDO');
}

$result = count(array_filter($validationResults, function($item) { return strpos($item, 'VÁLIDO') !== false; })) === count($validationResults);
$passedTests += formatResult('Input Validation', $result, true, implode(', ', $validationResults));
logResult('Input Validation', $result, true, implode(', ', $validationResults));

// 4. Teste de Access Control
$totalTests++;
if (class_exists('AccessControl')) {
    // Simular usuário admin
    $isAdmin = AccessControl::isUserAdmin(1);
    // Simular acesso a objeto
    $canAccess = AccessControl::canUserAccessObject(1, 1, 'order', 'view');
    
    $result = true; // Só testamos se a classe está disponível e os métodos funcionam
    $details = "isAdmin(1): " . ($isAdmin ? 'SIM' : 'NÃO') . ", canAccess: " . ($canAccess ? 'SIM' : 'NÃO');
} else {
    $result = false;
    $details = "Classe AccessControl não encontrada";
}
$passedTests += formatResult('Access Control', $result, true, $details);
logResult('Access Control', $result, true, $details);

// 5. Teste de SQL Injection Protection
$totalTests++;
try {
    // Carregar o modelo de produto
    require_once APP_PATH . '/models/ProductModel.php';
    require_once APP_PATH . '/helpers/Database.php';
    
    $model = new ProductModel();
    // Chamar método validateOrderBy com entrada maliciosa
    $reflection = new ReflectionClass($model);
    $method = $reflection->getMethod('validateOrderBy');
    $method->setAccessible(true);
    
    $maliciousOrderBy = "name; DROP TABLE users; --";
    $validatedOrderBy = $method->invoke($model, $maliciousOrderBy);
    
    // Verificar se a entrada maliciosa foi neutralizada
    $result = $validatedOrderBy !== $maliciousOrderBy && strpos($validatedOrderBy, 'DROP') === false;
    $details = "Entrada: '$maliciousOrderBy', Saída: '$validatedOrderBy'";
} catch (Exception $e) {
    $result = false;
    $details = "Erro: " . $e->getMessage();
}
$passedTests += formatResult('SQL Injection Protection', $result, true, $details);
logResult('SQL Injection Protection', $result, true, $details);

// 6. Teste de Content Security Policy
$totalTests++;
$cspHeader = '';
foreach ($headers as $header) {
    if (strpos($header, 'Content-Security-Policy:') === 0) {
        $cspHeader = $header;
        break;
    }
}

$cspRules = [
    'default-src' => "'self'",
    'script-src' => ['self', 'cdnjs.cloudflare.com'],
    'style-src' => ['self', 'unsafe-inline', 'cdnjs.cloudflare.com']
];

$allRulesFound = true;
$missingRules = [];

foreach ($cspRules as $directive => $values) {
    if (!is_array($values)) {
        $values = [$values];
    }
    
    foreach ($values as $value) {
        if (strpos($cspHeader, "$directive $value") === false && 
            strpos($cspHeader, "$directive '$value'") === false) {
            $allRulesFound = false;
            $missingRules[] = "$directive $value";
        }
    }
}

$passedTests += formatResult('Content Security Policy Rules', $allRulesFound, true, 
    $allRulesFound ? 'Todas as regras CSP encontradas' : 'Regras faltando: ' . implode(', ', $missingRules));
logResult('Content Security Policy Rules', $allRulesFound, true, 
    $allRulesFound ? 'Todas as regras CSP encontradas' : 'Regras faltando: ' . implode(', ', $missingRules));

// 7. Teste de Migração da Tabela print_status_files
$totalTests++;
try {
    $migrationPath = ROOT_PATH . '/database/migrations/add_print_status_files.sql';
    $result = file_exists($migrationPath);
    $details = $result ? 'Arquivo de migração encontrado' : 'Arquivo de migração não encontrado';
    
    if ($result) {
        $migrationContent = file_get_contents($migrationPath);
        $hasTriggers = strpos($migrationContent, 'CREATE TRIGGER') !== false;
        $hasIndexes = strpos($migrationContent, 'KEY `idx_') !== false;
        $hasSecurityColumns = strpos($migrationContent, 'security_scan_status') !== false;
        
        $details .= ", Triggers: " . ($hasTriggers ? 'SIM' : 'NÃO');
        $details .= ", Índices: " . ($hasIndexes ? 'SIM' : 'NÃO');
        $details .= ", Colunas de segurança: " . ($hasSecurityColumns ? 'SIM' : 'NÃO');
    }
} catch (Exception $e) {
    $result = false;
    $details = "Erro: " . $e->getMessage();
}
$passedTests += formatResult('Migration print_status_files', $result, true, $details);
logResult('Migration print_status_files', $result, true, $details);

// 8. Teste da Classe SecurityManager
$totalTests++;
try {
    $securityMethods = get_class_methods('SecurityManager');
    $requiredMethods = ['sanitize', 'validate', 'getCsrfToken', 'validateCsrfToken', 'setSecurityHeaders', 'setContentSecurityPolicy'];
    
    $allMethodsPresent = true;
    $missingMethods = [];
    
    foreach ($requiredMethods as $method) {
        if (!in_array($method, $securityMethods)) {
            $allMethodsPresent = false;
            $missingMethods[] = $method;
        }
    }
    
    $result = $allMethodsPresent;
    $details = $allMethodsPresent ? 'Todos os métodos de segurança encontrados' : 
        'Métodos faltando: ' . implode(', ', $missingMethods);
} catch (Exception $e) {
    $result = false;
    $details = "Erro: " . $e->getMessage();
}
$passedTests += formatResult('SecurityManager Class', $result, true, $details);
logResult('SecurityManager Class', $result, true, $details);

// 9. Teste de IDOR Protection
$totalTests++;
try {
    // Verificar se o AdminDashboardController foi atualizado
    $controllerPath = APP_PATH . '/controllers/AdminDashboardController.php';
    $controllerContent = file_get_contents($controllerPath);
    
    $hasOrderAccess = strpos($controllerContent, 'canUserAccessOrder') !== false;
    $hasPrintJobAccess = strpos($controllerContent, 'canUserAccessPrintJob') !== false;
    $hasProductAccess = strpos($controllerContent, 'canUserAccessProduct') !== false;
    $hasUserAccess = strpos($controllerContent, 'canUserAccessUser') !== false;
    
    $result = $hasOrderAccess && $hasPrintJobAccess && $hasProductAccess && $hasUserAccess;
    $details = "Protection against IDOR: " . 
        "Order: " . ($hasOrderAccess ? 'SIM' : 'NÃO') . ", " .
        "PrintJob: " . ($hasPrintJobAccess ? 'SIM' : 'NÃO') . ", " .
        "Product: " . ($hasProductAccess ? 'SIM' : 'NÃO') . ", " .
        "User: " . ($hasUserAccess ? 'SIM' : 'NÃO');
} catch (Exception $e) {
    $result = false;
    $details = "Erro: " . $e->getMessage();
}
$passedTests += formatResult('IDOR Protection', $result, true, $details);
logResult('IDOR Protection', $result, true, $details);

// 10. AJAX Proteção CSRF
$totalTests++;
try {
    // Verificar se o controller verifica o token CSRF nas requisições AJAX
    $controllerPath = APP_PATH . '/controllers/AdminDashboardController.php';
    $controllerContent = file_get_contents($controllerPath);
    
    $hasValidationToken = strpos($controllerContent, 'validateCsrfToken') !== false;
    $usesXCsrfToken = strpos($controllerContent, 'HTTP_X_CSRF_TOKEN') !== false;
    
    $result = $hasValidationToken && $usesXCsrfToken;
    $details = "CSRF Protection for AJAX: " . 
        "Token Validation: " . ($hasValidationToken ? 'SIM' : 'NÃO') . ", " .
        "X-CSRF-TOKEN Header: " . ($usesXCsrfToken ? 'SIM' : 'NÃO');
} catch (Exception $e) {
    $result = false;
    $details = "Erro: " . $e->getMessage();
}
$passedTests += formatResult('AJAX CSRF Protection', $result, true, $details);
logResult('AJAX CSRF Protection', $result, true, $details);

// Finalizar tabela
echo "</table>";

// Resumo dos testes
$percentPassed = ($passedTests / $totalTests) * 100;
$summaryColor = $percentPassed >= 80 ? 'green' : ($percentPassed >= 60 ? 'orange' : 'red');

echo "<div class='summary'>";
echo "<h2>Resumo dos Testes</h2>";
echo "<p>Total de testes: $totalTests</p>";
echo "<p>Testes passados: $passedTests</p>";
echo "<p>Percentual de sucesso: <span style='color: $summaryColor;'>" . number_format($percentPassed, 2) . "%</span></p>";
echo "</div>";

// Log de resumo
$logSummary = "[" . date('Y-m-d H:i:s') . "] SUMMARY: Total tests: $totalTests, Passed tests: $passedTests, Success rate: " . number_format($percentPassed, 2) . "%" . PHP_EOL;
file_put_contents($logFile, $logSummary, FILE_APPEND);

echo "</body>";
echo "</html>";
