<?php
/**
 * Script para remoção de alertas antigos do banco de dados
 * 
 * Este script remove registros de alertas e verificações com mais de X dias,
 * mantendo apenas alertas críticos para histórico.
 * 
 * Uso:
 * php scripts/cleanup/remove_old_alerts.php --days=14
 * 
 * @package App\Scripts\Cleanup
 */

// Definir diretório raiz do projeto para carregamento correto de classes
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Carregar autoloader
require_once ROOT_DIR . '/app/autoload.php';

// Importar classes necessárias
use App\Lib\Security\SecurityManager;
use App\Lib\Validation\InputValidationTrait;

// Classe para remoção de alertas antigos
class AlertCleanupTool {
    use InputValidationTrait;
    
    /** @var PDO Conexão com banco de dados */
    private $db;
    
    /** @var int Dias para manter alertas */
    private $days = 30;
    
    /** @var bool Flag de modo dry-run (simulação) */
    private $dryRun = false;
    
    /** @var array Contadores de operações */
    private $counters = [
        'deleted_alerts' => 0,
        'deleted_checks' => 0,
        'kept_critical' => 0
    ];
    
    /**
     * Construtor
     * 
     * @param PDO $db Conexão com banco de dados
     */
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Processa argumentos da linha de comando
     * 
     * @param array $args Argumentos da linha de comando
     * @return bool Sucesso do processamento
     */
    public function processArgs(array $args): bool {
        foreach ($args as $arg) {
            if (preg_match('/^--days=(\d+)$/', $arg, $matches)) {
                $this->days = (int) $matches[1];
                if ($this->days < 1) {
                    echo "ERRO: O número de dias deve ser maior que zero.\n";
                    return false;
                }
            } 
            elseif ($arg === '--dry-run') {
                $this->dryRun = true;
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
        echo "  --days=N       Manter alertas dos últimos N dias (padrão: 30)\n";
        echo "  --dry-run      Simular a execução sem fazer alterações\n";
        echo "  --help         Exibir esta ajuda\n";
    }
    
    /**
     * Executa a limpeza de alertas antigos
     * 
     * @return bool Sucesso da operação
     */
    public function execute(): bool {
        try {
            echo "[" . date('Y-m-d H:i:s') . "] Iniciando limpeza de alertas antigos...\n";
            
            // Se for dry-run, avisar
            if ($this->dryRun) {
                echo "MODO SIMULAÇÃO: Nenhuma alteração será feita no banco de dados.\n";
            }
            
            echo "Removendo alertas com mais de {$this->days} dias, exceto críticos...\n";
            
            // Calcular data limite
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$this->days} days"));
            
            // Remover alertas antigos, exceto críticos
            $sql = "DELETE FROM performance_alerts 
                    WHERE created_at < ? 
                    AND severity != 'critical'
                    AND acknowledged = 1";
            
            if (!$this->dryRun) {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$cutoffDate]);
                $this->counters['deleted_alerts'] = $stmt->rowCount();
            } else {
                // Em modo simulação, apenas contar
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM performance_alerts 
                                          WHERE created_at < ? 
                                          AND severity != 'critical'
                                          AND acknowledged = 1");
                $stmt->execute([$cutoffDate]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->counters['deleted_alerts'] = $result['count'];
            }
            
            // Contar alertas críticos mantidos
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM performance_alerts 
                                      WHERE created_at < ? 
                                      AND severity = 'critical'");
            $stmt->execute([$cutoffDate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->counters['kept_critical'] = $result['count'];
            
            // Remover logs de verificação antigos
            echo "Removendo logs de verificação com mais de {$this->days} dias...\n";
            
            $sql = "DELETE FROM performance_check_logs 
                    WHERE created_at < ?";
            
            if (!$this->dryRun) {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$cutoffDate]);
                $this->counters['deleted_checks'] = $stmt->rowCount();
            } else {
                // Em modo simulação, apenas contar
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM performance_check_logs 
                                          WHERE created_at < ?");
                $stmt->execute([$cutoffDate]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->counters['deleted_checks'] = $result['count'];
            }
            
            // Registrar operação no log de performance
            echo "Operação concluída.\n";
            echo "Estatísticas:\n";
            echo "- Alertas removidos: {$this->counters['deleted_alerts']}\n";
            echo "- Logs de verificação removidos: {$this->counters['deleted_checks']}\n";
            echo "- Alertas críticos mantidos: {$this->counters['kept_critical']}\n";
            
            return true;
        } catch (Exception $e) {
            echo "ERRO: " . $e->getMessage() . "\n";
            return false;
        }
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
    
    // Inicializar e executar a ferramenta de limpeza
    $cleanupTool = new AlertCleanupTool($pdo);
    
    // Processar argumentos da linha de comando
    $args = array_slice($argv, 1);
    if (!$cleanupTool->processArgs($args)) {
        exit(1);
    }
    
    // Executar limpeza
    if ($cleanupTool->execute()) {
        echo "[" . date('Y-m-d H:i:s') . "] Limpeza de alertas concluída com sucesso.\n";
        exit(0);
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Falha ao executar limpeza de alertas.\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
