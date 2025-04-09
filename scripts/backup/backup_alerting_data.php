<?php
/**
 * Script para backup diário de dados de alertas de performance
 * 
 * Este script realiza o backup dos alertas críticos e dados de performance
 * para manter histórico mesmo após a limpeza automática.
 * 
 * Uso:
 * php scripts/backup/backup_alerting_data.php [--force]
 * 
 * @package App\Scripts\Backup
 */

// Definir diretório raiz do projeto para carregamento correto de classes
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Carregar autoloader
require_once ROOT_DIR . '/app/autoload.php';

// Importar classes necessárias
use App\Lib\Security\SecurityManager;
use App\Lib\Validation\InputValidationTrait;

// Classe para backup de dados de alerta
class AlertDataBackupTool {
    use InputValidationTrait;
    
    /** @var PDO Conexão com banco de dados */
    private $db;
    
    /** @var string Diretório para backups */
    private $backupDir;
    
    /** @var bool Flag de forçar backup mesmo se já existir */
    private $force = false;
    
    /** @var string Data atual formatada para nomes de arquivo */
    private $dateStamp;
    
    /**
     * Construtor
     * 
     * @param PDO $db Conexão com banco de dados
     */
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->backupDir = ROOT_DIR . '/data/backups/alerts';
        $this->dateStamp = date('Y-m-d');
    }
    
    /**
     * Processa argumentos da linha de comando
     * 
     * @param array $args Argumentos da linha de comando
     * @return bool Sucesso do processamento
     */
    public function processArgs(array $args): bool {
        foreach ($args as $arg) {
            if ($arg === '--force') {
                $this->force = true;
            }
            elseif ($arg === '--help') {
                $this->showHelp();
                return false;
            }
        }
        return true;
    }
    
    /**
     * Exibe ajuda do script
     */
    private function showHelp(): void {
        echo "Uso: php " . basename(__FILE__) . " [opções]\n\n";
        echo "Opções:\n";
        echo "  --force        Forçar backup mesmo se já existir para hoje\n";
        echo "  --help         Exibir esta ajuda\n";
    }
    
    /**
     * Executa o backup dos dados de alerta
     * 
     * @return bool Sucesso da operação
     */
    public function execute(): bool {
        try {
            echo "[" . date('Y-m-d H:i:s') . "] Iniciando backup de dados de alerta...\n";
            
            // Verificar/criar diretório de backup
            if (!is_dir($this->backupDir)) {
                if (!mkdir($this->backupDir, 0755, true)) {
                    throw new Exception("Não foi possível criar o diretório de backup: {$this->backupDir}");
                }
                echo "Diretório de backup criado: {$this->backupDir}\n";
            }
            
            // Definir nomes de arquivos
            $criticalAlertsFile = $this->backupDir . "/critical_alerts_{$this->dateStamp}.json";
            $performanceLogsFile = $this->backupDir . "/performance_logs_{$this->dateStamp}.json";
            
            // Verificar se os arquivos já existem
            if (!$this->force) {
                if (file_exists($criticalAlertsFile) && file_exists($performanceLogsFile)) {
                    echo "Backup para hoje ({$this->dateStamp}) já existe. Use --force para substituir.\n";
                    return true;
                }
            }
            
            // 1. Backup de alertas críticos
            echo "Exportando alertas críticos...\n";
            $stmt = $this->db->prepare("
                SELECT * FROM performance_alerts 
                WHERE severity = 'critical'
                OR created_at > (NOW() - INTERVAL 7 DAY)
            ");
            $stmt->execute();
            $criticalAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->saveJsonBackup($criticalAlertsFile, [
                'backup_type' => 'critical_alerts',
                'backup_date' => date('Y-m-d H:i:s'),
                'backup_version' => '1.0',
                'alert_count' => count($criticalAlerts),
                'alerts' => $criticalAlerts
            ]);
            
            echo "Exportados " . count($criticalAlerts) . " alertas críticos.\n";
            
            // 2. Backup de logs de performance
            echo "Exportando logs de performance...\n";
            $stmt = $this->db->prepare("
                SELECT * FROM performance_check_logs 
                WHERE created_at > (NOW() - INTERVAL 3 DAY)
            ");
            $stmt->execute();
            $performanceLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->saveJsonBackup($performanceLogsFile, [
                'backup_type' => 'performance_logs',
                'backup_date' => date('Y-m-d H:i:s'),
                'backup_version' => '1.0',
                'log_count' => count($performanceLogs),
                'logs' => $performanceLogs
            ]);
            
            echo "Exportados " . count($performanceLogs) . " logs de performance.\n";
            
            // 3. Limpar backups antigos (manter apenas últimos 30 dias)
            $this->cleanupOldBackups(30);
            
            return true;
        } catch (Exception $e) {
            echo "ERRO: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Salva dados em um arquivo JSON formatado
     * 
     * @param string $filePath Caminho do arquivo
     * @param array $data Dados a serem salvos
     * @return bool Sucesso da operação
     */
    private function saveJsonBackup(string $filePath, array $data): bool {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($filePath, $json) === false) {
            throw new Exception("Não foi possível salvar o arquivo: {$filePath}");
        }
        echo "Arquivo de backup criado: {$filePath}\n";
        return true;
    }
    
    /**
     * Remove backups mais antigos que o número de dias especificado
     * 
     * @param int $keepDays Número de dias para manter
     * @return void
     */
    private function cleanupOldBackups(int $keepDays): void {
        echo "Removendo backups com mais de {$keepDays} dias...\n";
        
        $cutoffDate = new DateTime();
        $cutoffDate->modify("-{$keepDays} days");
        $cutoffDateStamp = $cutoffDate->format('Y-m-d');
        
        $deleted = 0;
        foreach (glob($this->backupDir . "/*_{*.json") as $file) {
            $filename = basename($file);
            if (preg_match('/\_(\d{4}\-\d{2}\-\d{2})\.json$/', $filename, $matches)) {
                $fileDate = $matches[1];
                if ($fileDate < $cutoffDateStamp) {
                    unlink($file);
                    $deleted++;
                }
            }
        }
        
        echo "Removidos {$deleted} arquivos de backup antigos.\n";
    }
}

// Configurar tratamento de erros
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // Este código de erro não está incluído na configuração de error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    // Inicializar conexão com o banco de dados
    $pdo = require ROOT_DIR . '/app/config/database.php';
    
    // Inicializar e executar a ferramenta de backup
    $backupTool = new AlertDataBackupTool($pdo);
    
    // Processar argumentos da linha de comando
    $args = array_slice($argv, 1);
    if (!$backupTool->processArgs($args)) {
        exit(1);
    }
    
    // Executar backup
    if ($backupTool->execute()) {
        echo "[" . date('Y-m-d H:i:s') . "] Backup de dados de alerta concluído com sucesso.\n";
        exit(0);
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Falha ao executar backup de dados de alerta.\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
