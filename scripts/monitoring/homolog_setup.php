<?php
/**
 * Script para configuração do ambiente de homologação do sistema assíncrono
 * 
 * Este script:
 * 1. Configura limiares de alertas otimizados para o ambiente de homologação
 * 2. Prepara as tabelas necessárias para o sistema de monitoramento
 * 3. Configura parâmetros iniciais para o monitoramento contínuo
 * 
 * @package App\Scripts\Monitoring
 */

// Definir diretório raiz do projeto para carregamento correto de classes
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Carregar autoloader
require_once ROOT_DIR . '/app/autoload.php';

// Importar classes necessárias
use App\Lib\Security\SecurityManager;
use App\Lib\Validation\InputValidationTrait;

// Configurar tratamento de erros
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // Este código de erro não está incluído na configuração de error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    echo "[" . date('Y-m-d H:i:s') . "] Iniciando configuração do ambiente de homologação...\n";
    
    // Inicializar conexão com o banco de dados
    $pdo = require ROOT_DIR . '/app/config/database.php';
    
    // Verificar se as tabelas necessárias existem
    echo "Verificando estrutura do banco de dados...\n";
    
    // Verificar se a tabela monitored_processes existe
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'monitored_processes'");
    $tablesExist = $tableCheck->rowCount() > 0;
    
    if (!$tablesExist) {
        echo "Tabelas não encontradas. Executando script de criação...\n";
        
        // Executar script de criação das tabelas
        $sqlFile = file_get_contents(ROOT_DIR . '/database/migrations/create_performance_alerting_tables.sql');
        
        // Dividir o arquivo SQL em múltiplas consultas
        $queries = array_filter(array_map('trim', explode(';', $sqlFile)));
        
        // Executar cada consulta separadamente
        foreach ($queries as $query) {
            if (!empty($query)) {
                $pdo->exec($query);
            }
        }
        
        echo "Tabelas criadas com sucesso.\n";
    } else {
        echo "Tabelas já existem. Pulando criação.\n";
    }
    
    // Configurar limiares otimizados para homologação
    echo "Configurando limiares para ambiente de homologação...\n";
    
    // Limpar limiares existentes
    $pdo->exec("DELETE FROM performance_thresholds");
    
    // Inserir limiares para homologação (mais sensíveis que produção)
    $thresholds = [
        // Limiares para processamento assíncrono
        ['async_process', 'execution_time', 1800, 1.2, 2],           // 30 minutos (metade do padrão de produção)
        ['async_process', 'memory_usage', 52428800, 1.1, 1.5],       // 50 MB (metade do padrão de produção)
        ['async_process', 'min_progress_rate', 15, 1.3, 2],          // 15% (mais sensível que produção)
        
        // Limiares para processamento de pedidos
        ['checkout_process', 'execution_time', 5, 1.2, 2],           // 5 segundos (metade do padrão de produção)
        ['checkout_process', 'database_queries', 30, 1.3, 2],        // 30 consultas (mais restritivo que produção)
        
        // Limiares para geração de relatórios
        ['report_generation', 'execution_time', 30, 1.5, 3],         // 30 segundos (metade do padrão de produção)
        ['report_generation', 'memory_usage', 26214400, 1.3, 2],     // 25 MB (metade do padrão de produção)
        ['report_generation', 'database_queries', 50, 1.3, 2.5]      // 50 consultas (metade do padrão de produção)
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO performance_thresholds 
        (context, metric, threshold_value, warning_multiplier, error_multiplier, is_active, created_at, updated_at, created_by)
        VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW(), 1)
    ");
    
    foreach ($thresholds as $threshold) {
        $stmt->execute($threshold);
    }
    
    echo "Limiares configurados com sucesso.\n";
    
    // Criar diretório para logs de monitoramento se não existir
    $logDir = ROOT_DIR . '/logs/monitoring';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
        echo "Diretório de logs criado: {$logDir}\n";
    }
    
    // Verificar permissões do script de monitoramento
    $scriptPath = ROOT_DIR . '/scripts/monitoring/check_monitored_processes.php';
    if (!is_executable($scriptPath)) {
        chmod($scriptPath, 0755);
        echo "Permissões do script de monitoramento atualizadas.\n";
    }
    
    // Criar arquivo de configuração para execução do cron
    $cronConfig = <<<EOT
# Configuração de cron para o sistema de monitoramento - Homologação
# Executar: crontab < crontab_config.txt

# Verificação de processos monitorados a cada 5 minutos
*/5 * * * * cd " . ROOT_DIR . " && php scripts/monitoring/check_monitored_processes.php >> logs/monitoring/cron.log 2>&1

# Limpeza diária de dados antigos (mantém apenas 14 dias para homologação)
0 2 * * * cd " . ROOT_DIR . " && php scripts/cleanup/remove_old_alerts.php --days=14 >> logs/monitoring/cleanup.log 2>&1

# Backup diário de dados de alertas
0 3 * * * cd " . ROOT_DIR . " && php scripts/backup/backup_alerting_data.php >> logs/monitoring/backup.log 2>&1

EOT;
    
    file_put_contents(ROOT_DIR . '/scripts/monitoring/crontab_config.txt', $cronConfig);
    echo "Arquivo de configuração do cron criado: " . ROOT_DIR . "/scripts/monitoring/crontab_config.txt\n";
    
    echo "\n[" . date('Y-m-d H:i:s') . "] Configuração do ambiente de homologação concluída com sucesso.\n";
    echo "\nPróximos passos:\n";
    echo "1. Configure o cron no servidor de homologação usando o arquivo crontab_config.txt\n";
    echo "2. Faça o deploy dos arquivos para o ambiente de homologação\n";
    echo "3. Execute uma verificação manual para validar o funcionamento: php scripts/monitoring/check_monitored_processes.php\n";
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
