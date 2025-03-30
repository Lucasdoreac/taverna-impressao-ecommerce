<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Partial para incorporação do monitoramento de performance em ambiente de produção
 * Esta partial deve ser incluída no footer das páginas para ativar o monitoramento
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

// Verificar se estamos em ambiente de produção
$isProd = defined('ENVIRONMENT') && ENVIRONMENT === 'production';

// Obter configurações de monitoramento do banco de dados
$monitorSettings = [];
if (function_exists('getMonitorSettings')) {
    $monitorSettings = getMonitorSettings();
} else {
    // Configurações padrão caso a função não esteja disponível
    $monitorSettings = [
        'enabled' => true,
        'sampling_rate' => 10, // 10% dos usuários
        'ignore_paths' => ['/admin', '/api', '/login'],
        'min_time_between_sends' => 3600000 // 1 hora em ms
    ];
}

// Verificar se o monitoramento está habilitado para este ambiente
$isEnabled = $isProd && isset($monitorSettings['enabled']) && $monitorSettings['enabled'];

// Se não estiver habilitado, não incluir o script
if (!$isEnabled) {
    return;
}

// Determinar a taxa de amostragem
$samplingRate = isset($monitorSettings['sampling_rate']) ? (int)$monitorSettings['sampling_rate'] : 10;

// Caminhos a ignorar
$ignorePaths = isset($monitorSettings['ignore_paths']) ? $monitorSettings['ignore_paths'] : ['/admin', '/api', '/login'];
$ignorePathsJson = json_encode($ignorePaths);

// Tempo mínimo entre envios
$minTimeBetweenSends = isset($monitorSettings['min_time_between_sends']) 
    ? (int)$monitorSettings['min_time_between_sends'] 
    : 3600000;

// Ambiente de depuração
$debug = defined('DEBUG_MODE') && DEBUG_MODE;
?>

<!-- Monitoramento de Performance em Produção -->
<script src="<?= BASE_URL ?>assets/js/production-monitor-client.js" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurações personalizadas para o monitoramento
        const monitorConfig = {
            endpoint: '<?= BASE_URL ?>api/performance/monitor',
            samplingRate: <?= $samplingRate ?>,
            ignorePaths: <?= $ignorePathsJson ?>,
            minTimeBetweenSends: <?= $minTimeBetweenSends ?>,
            debug: <?= $debug ? 'true' : 'false' ?>
        };
        
        // Inicializar o monitor com as configurações personalizadas
        if (window.ProductionMonitor) {
            window.ProductionMonitor.init(monitorConfig);
        }
    });
</script>
