<?php
/**
 * Script para verificação periódica de processos monitorados
 * 
 * Este script deve ser executado periodicamente por um job do sistema (cron)
 * para verificar o estado de processos assíncronos monitorados e gerar alertas
 * quando necessário.
 * 
 * Uso:
 * php scripts/monitoring/check_monitored_processes.php
 * 
 * @package App\Scripts\Monitoring
 */

// Definir diretório raiz do projeto para carregamento correto de classes
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Carregar autoloader
require_once ROOT_DIR . '/app/autoload.php';

// Importar classes necessárias
use App\Lib\Monitoring\PerformanceAlertingService;
use App\Lib\Performance\PerformanceMonitor;
use App\Lib\Notification\NotificationManager;
use App\Lib\Notification\NotificationThresholds;
use App\Lib\Security\SecurityManager;

// Configurar tratamento de erros
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // Este código de erro não está incluído na configuração de error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    echo "[" . date('Y-m-d H:i:s') . "] Iniciando verificação de processos monitorados...\n";
    
    // Inicializar conexão com o banco de dados
    $pdo = require ROOT_DIR . '/app/config/database.php';
    
    // Inicializar logger
    $logger = new \Monolog\Logger('performance_monitoring');
    $logger->pushHandler(new \Monolog\Handler\StreamHandler(
        ROOT_DIR . '/logs/performance_monitoring.log',
        \Monolog\Logger::INFO
    ));
    
    // Inicializar serviço de alertas
    $performanceAlertingService = new PerformanceAlertingService(
        PerformanceMonitor::getInstance(),
        NotificationManager::getInstance(),
        new NotificationThresholds($pdo),
        $pdo,
        $logger
    );
    
    // Registrar início da verificação
    $logger->info('Verificação de processos iniciada');
    
    // Executar verificação
    $startTime = microtime(true);
    $result = $performanceAlertingService->checkMonitoredProcesses();
    $executionTime = microtime(true) - $startTime;
    
    // Registrar resultado
    if ($result['success']) {
        echo "[" . date('Y-m-d H:i:s') . "] Verificação concluída com sucesso:\n";
        echo "- Processos verificados: {$result['checked']}\n";
        echo "- Alertas gerados: {$result['alerts']}\n";
        echo "- Verificações bem-sucedidas: {$result['successful_checks']}\n";
        echo "- Tempo de execução: " . number_format($executionTime, 4) . " segundos\n";
        
        $logger->info('Verificação de processos concluída', [
            'checked_count' => $result['checked'],
            'alert_count' => $result['alerts'],
            'successful_checks' => $result['successful_checks'],
            'execution_time' => $executionTime
        ]);
        
        exit(0); // Saída com sucesso
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Falha na verificação de processos:\n";
        echo "- Erro: " . ($result['error'] ?? 'Erro desconhecido') . "\n";
        echo "- Processos parcialmente verificados: {$result['checked']}\n";
        echo "- Alertas gerados: {$result['alerts']}\n";
        echo "- Verificações bem-sucedidas: {$result['successful_checks']}\n";
        
        $logger->error('Falha na verificação de processos', [
            'error' => $result['error'] ?? 'Erro desconhecido',
            'checked_count' => $result['checked'],
            'alert_count' => $result['alerts'],
            'successful_checks' => $result['successful_checks'],
            'execution_time' => $executionTime
        ]);
        
        exit(1); // Saída com erro
    }
} catch (Exception $e) {
    // Tratar exceções não capturadas
    $errorMessage = 'Exceção não tratada: ' . $e->getMessage();
    echo "[" . date('Y-m-d H:i:s') . "] ERRO: {$errorMessage}\n";
    
    if (isset($logger)) {
        $logger->critical($errorMessage, [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    } else {
        error_log($errorMessage);
    }
    
    exit(2); // Saída com erro crítico
}
