<?php
/**
 * Logger - Funções auxiliares para logging da aplicação
 */

/**
 * Registra uma mensagem de log
 *
 * @param string $message Mensagem a ser registrada
 * @param string $level Nível do log (debug, info, warning, error)
 * @return void
 */
function app_log($message, $level = 'info') {
    // Definir caminho para arquivo de log
    $logDir = ROOT_PATH . '/logs';
    
    // Criar diretório de logs se não existir
    if (!is_dir($logDir)) {
        if (!mkdir($logDir, 0755, true)) {
            // Se não puder criar o diretório, apenas retorne sem erro
            return;
        }
    }
    
    // Nome do arquivo de log baseado na data atual
    $logFile = $logDir . '/app_' . date('Y-m-d') . '.log';
    
    // Formatar mensagem de log
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    // Tentar escrever no arquivo de log
    // Usar error suppression (@) para evitar erros se o arquivo não for gravável
    @file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    
    // Em ambiente de desenvolvimento, exibir também no console se for um erro
    if (ENVIRONMENT === 'development' && isset($_GET['debug']) && ($level === 'error' || $level === 'warning')) {
        echo "<!-- LOG: {$formattedMessage} -->\n";
    }
}
