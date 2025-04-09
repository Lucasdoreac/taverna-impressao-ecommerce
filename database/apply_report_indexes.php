<?php
/**
 * Script de aplicação de índices para otimização de relatórios
 * 
 * Este script deve ser executado pelo administrador do sistema
 * após análise cuidadosa do impacto nos ambientes de produção.
 * 
 * @package Database\Migrations
 * @version 1.0.0
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use App\Lib\Database\Database;

// Função para registrar logs
function logMessage($message) {
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
    
    // Também registrar em arquivo
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents(
        $logDir . '/migration_' . date('Y-m-d') . '.log',
        "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL,
        FILE_APPEND
    );
}

// Banner inicial
echo "========================================================\n";
echo "    Otimização de Índices para Relatórios - v1.0.0      \n";
echo "    Taverna da Impressão 3D                             \n";
echo "========================================================\n\n";

// Confirmar execução
echo "ATENÇÃO: Este script criará índices otimizados para consultas de relatórios.\n";
echo "Em bancos de dados grandes, isso pode gerar carga significativa.\n";
echo "Recomenda-se executar durante período de baixo uso do sistema.\n\n";
echo "Deseja continuar? (S/N): ";

$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
if (strtoupper($line) !== 'S') {
    echo "Operação cancelada pelo usuário.\n";
    exit;
}

// Iniciar aplicação dos índices
try {
    // Obter conexão com banco de dados
    $db = Database::getInstance()->getConnection();
    
    // Medir tempo de execução
    $startTime = microtime(true);
    
    // Carregar SQL de índices
    $sqlFile = __DIR__ . '/migrations/report_optimization_indexes.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo de SQL não encontrado: " . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Dividir e executar cada declaração SQL
    $statements = array_filter(
        explode(';', $sql),
        function ($statement) {
            return trim($statement) !== '';
        }
    );
    
    logMessage("Iniciando criação de " . count($statements) . " índices...");
    
    foreach ($statements as $i => $statement) {
        $startStatementTime = microtime(true);
        
        // Executar comando SQL
        try {
            $db->exec($statement);
            $duration = round(microtime(true) - $startStatementTime, 2);
            logMessage("Índice " . ($i + 1) . " criado com sucesso (" . $duration . "s)");
        } catch (PDOException $e) {
            // Se já existir, não é erro
            if (strpos($e->getMessage(), 'Duplicate key name') !== false ||
                strpos($e->getMessage(), 'already exists') !== false) {
                logMessage("Índice " . ($i + 1) . " já existe - ignorando");
            } else {
                throw $e;
            }
        }
    }
    
    // Forçar atualização de estatísticas de índices para otimizador de consultas
    logMessage("Executando ANALYZE TABLE para atualizar estatísticas...");
    
    $tables = ['orders', 'order_items', 'products', 'users', 'print_jobs', 'print_job_failures'];
    foreach ($tables as $table) {
        $db->exec("ANALYZE TABLE " . $table);
    }
    
    $totalDuration = round(microtime(true) - $startTime, 2);
    logMessage("Migração concluída com sucesso em " . $totalDuration . " segundos");
    
    echo "\n========================================================\n";
    echo "    Índices criados com sucesso!                         \n";
    echo "    Tempo total: " . $totalDuration . " segundos         \n";
    echo "========================================================\n\n";
    
} catch (Exception $e) {
    logMessage("ERRO: " . $e->getMessage());
    echo "\n========================================================\n";
    echo "    ERRO DURANTE A CRIAÇÃO DOS ÍNDICES                   \n";
    echo "    Verifique os logs para mais detalhes                 \n";
    echo "========================================================\n\n";
    exit(1);
}
