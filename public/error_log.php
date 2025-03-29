<?php
/**
 * Arquivo de visualização de logs de erro
 * Este arquivo exibe os logs de erro do PHP para ajudar na depuração de problemas.
 * IMPORTANTE: Remova este arquivo após a depuração para evitar exposição de informações sensíveis.
 */

// Definir exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificação de autenticação básica para proteger o acesso ao arquivo
$auth_user = 'admin';
$auth_pass = 'taverna2025';

// Função para verificar autenticação
function check_auth() {
    global $auth_user, $auth_pass;
    
    if (!isset($_SERVER['PHP_AUTH_USER']) || 
        !isset($_SERVER['PHP_AUTH_PW']) ||
        $_SERVER['PHP_AUTH_USER'] !== $auth_user || 
        $_SERVER['PHP_AUTH_PW'] !== $auth_pass) {
        
        header('WWW-Authenticate: Basic realm="Acesso Restrito"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Acesso não autorizado.';
        exit;
    }
}

// Verificar autenticação
check_auth();

// Carregar configurações
require_once __DIR__ . '/../app/config/config.php';

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Logs de Erro - TAVERNA DA IMPRESSÃO</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 1200px; margin: 0 auto; }
        h1, h2 { color: #333; }
        pre { background: #f5f5f5; padding: 15px; overflow-x: auto; border-radius: 4px; }
        .controls { margin-bottom: 20px; }
        .timestamp { color: #777; font-size: 0.9em; }
        .error-entry { margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 15px; }
        .error-type { font-weight: bold; color: #d9534f; }
        .filter-box { margin: 15px 0; padding: 10px; background: #f9f9f9; border-radius: 4px; }
        button, select { padding: 8px 12px; margin-right: 10px; cursor: pointer; }
        .centered { text-align: center; }
    </style>
</head>
<body>
    <h1>Logs de Erro - TAVERNA DA IMPRESSÃO</h1>
    <p>Este arquivo exibe os logs de erro do PHP para ajudar na depuração de problemas.</p>";

// Buscar caminho do arquivo de log
$error_log_path = ini_get('error_log');
$default_log_locations = [
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    '/var/log/php_errors.log',
    __DIR__ . '/../logs/error.log',
    __DIR__ . '/../app/logs/error.log'
];

echo "<div class='filter-box'>
    <h3>Informações do Sistema</h3>
    <p><strong>Versão PHP:</strong> " . phpversion() . "</p>
    <p><strong>Configuração error_log:</strong> " . ($error_log_path ?: 'Não definido') . "</p>
    <p><strong>log_errors:</strong> " . (ini_get('log_errors') ? 'Ativado' : 'Desativado') . "</p>
    <p><strong>Ambiente:</strong> " . ENVIRONMENT . "</p>
</div>";

// Função para buscar logs de erro
function get_error_logs($path) {
    if (!file_exists($path) || !is_readable($path)) {
        return false;
    }
    
    // Ler últimas 500 linhas (ajuste conforme necessário)
    $lines = [];
    $fp = fopen($path, 'r');
    
    // Se o arquivo for muito grande, pular para o final menos aproximadamente 50KB
    $filesize = filesize($path);
    if ($filesize > 50000) {
        fseek($fp, $filesize - 50000);
        // Descartar a primeira linha parcial
        fgets($fp);
    }
    
    // Ler as linhas
    while (!feof($fp)) {
        $line = fgets($fp);
        if ($line) {
            $lines[] = $line;
        }
    }
    fclose($fp);
    
    // Manter apenas as últimas 500 linhas
    if (count($lines) > 500) {
        $lines = array_slice($lines, -500);
    }
    
    return $lines;
}

// Processar os logs
$logs = [];
$found_log = false;

// Verificar o log configurado
if ($error_log_path && file_exists($error_log_path)) {
    $logs = get_error_logs($error_log_path);
    if ($logs !== false) {
        $found_log = true;
        echo "<div class='centered'><h2>Exibindo logs de erro de: {$error_log_path}</h2></div>";
    }
}

// Se não encontrou o log configurado, tentar locais padrão
if (!$found_log) {
    foreach ($default_log_locations as $log_path) {
        if (file_exists($log_path) && is_readable($log_path)) {
            $logs = get_error_logs($log_path);
            if ($logs !== false) {
                $found_log = true;
                echo "<div class='centered'><h2>Exibindo logs de erro de: {$log_path}</h2></div>";
                break;
            }
        }
    }
}

// Se ainda não encontrou nenhum log, tentar criar manualmente
if (!$found_log) {
    echo "<div class='centered'><h2>Nenhum arquivo de log encontrado</h2></div>";
    echo "<p>Não foi possível encontrar os arquivos de log do PHP. Vamos tentar gerar um erro para obter informações:</p>";
    
    // Redirecionar logs para a saída padrão temporariamente
    ini_set('log_errors', 1);
    ini_set('error_log', 'php://output');
    
    echo "<pre>";
    // Gerar um erro de propósito para testar
    try {
        $test = new NonExistentClass(); // Isso vai gerar um erro
    } catch (Error $e) {
        error_log("Erro de teste gerado: " . $e->getMessage());
        echo "Um erro de teste foi registrado: " . $e->getMessage();
    }
    echo "</pre>";
    
    echo "<p>Tente verificar o log de erro diretamente no servidor ou entre em contato com seu provedor de hospedagem para obter o caminho correto para os logs de erro.</p>";
} else {
    // Exibir logs encontrados
    echo "<div class='error-logs'>";
    
    if (empty($logs)) {
        echo "<p>Nenhum log de erro encontrado no período recente.</p>";
    } else {
        // Organizar os logs em entradas
        $current_entry = "";
        $entries = [];
        
        foreach ($logs as $line) {
            // Se a linha começa com data (formato comum de logs PHP/Apache)
            if (preg_match('/^\[[0-9]{2}-[A-Za-z]{3}-[0-9]{4}/', $line) || 
                preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}/', $line)) {
                
                if (!empty($current_entry)) {
                    $entries[] = $current_entry;
                }
                $current_entry = $line;
            } else {
                $current_entry .= $line;
            }
        }
        
        // Adicionar a última entrada
        if (!empty($current_entry)) {
            $entries[] = $current_entry;
        }
        
        // Exibir as entradas na ordem inversa (mais recentes primeiro)
        $entries = array_reverse($entries);
        
        echo "<p>Exibindo " . count($entries) . " entradas de log mais recentes:</p>";
        
        foreach ($entries as $index => $entry) {
            echo "<div class='error-entry'>";
            echo "<pre>" . htmlspecialchars($entry) . "</pre>";
            echo "</div>";
        }
    }
    
    echo "</div>";
}

echo "</body></html>";
