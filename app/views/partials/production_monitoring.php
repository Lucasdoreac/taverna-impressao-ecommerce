<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * Partial para incorporação do monitoramento de performance em ambiente de produção.
 * Integra o ProductionMonitoringHelper.php com o script client-side.
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

// Verificar se o monitoramento está habilitado (apenas em ambiente de produção)
$monitoringEnabled = defined('ENVIRONMENT') && ENVIRONMENT === 'production';

// Verificar se o usuário deve ser monitorado (amostragem seletiva)
$shouldMonitor = false;
if ($monitoringEnabled && class_exists('ProductionMonitoringHelper')) {
    $shouldMonitor = ProductionMonitoringHelper::shouldMonitorUser();
}

// Se for para monitorar, incluir scripts necessários
if ($shouldMonitor):
?>
<!-- Monitoramento de Performance de Produção -->
<script>
    // Configurações específicas para esta página
    window.tavernaMonitorConfig = {
        endpoint: '<?= BASE_URL ?>api/performance/collect',
        debug: <?= defined('DEBUG_MONITORING') && DEBUG_MONITORING ? 'true' : 'false' ?>,
        pageInfo: {
            path: '<?= isset($currentPage) ? htmlspecialchars($currentPage) : '' ?>',
            category: '<?= isset($pageCategory) ? htmlspecialchars($pageCategory) : '' ?>',
            template: '<?= isset($pageTemplate) ? htmlspecialchars($pageTemplate) : '' ?>'
        }
    };
</script>

<?php
    // Adicionar script de monitoramento
    if (class_exists('AssetOptimizerHelper')) {
        // Carregamento via AssetOptimizerHelper
        echo AssetOptimizerHelper::js(['production-monitor-client.js'], true, true);
    } else {
        // Fallback para carregamento direto
        echo '<script src="' . BASE_URL . 'assets/js/production-monitor-client.js" defer></script>' . PHP_EOL;
    }
    
    // Iniciar monitoramento no lado do servidor
    ProductionMonitoringHelper::startMonitoring();
endif;
?>

<?php
// Adicionar script para registrar evento de finalização quando a página for descarregada
if ($shouldMonitor):
?>
<script>
    // Registrar evento para finalizar monitoramento ao descarregar a página
    window.addEventListener('beforeunload', function() {
        if (window.ProductionMonitor && typeof window.ProductionMonitor.sendMetrics === 'function') {
            window.ProductionMonitor.sendMetrics();
        }
        
        // Usar Beacon API para enviar dados ao servidor
        try {
            const data = new FormData();
            data.append('action', 'page_unload');
            navigator.sendBeacon('<?= BASE_URL ?>api/performance/finalize', data);
        } catch (e) {
            console.error('Erro ao enviar beacon:', e);
        }
    });
</script>
<?php endif; ?>
