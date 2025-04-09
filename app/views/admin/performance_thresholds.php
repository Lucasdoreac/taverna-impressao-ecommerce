<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * View de Configuração de Limiares de Alertas de Performance
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

// Incluir header administrativo
require_once 'app/views/admin/partials/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Configuração de Limiares de Alerta</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/performance_monitoring_dashboard">Monitoramento de Performance</a></li>
                        <li class="breadcrumb-item active">Limiares de Alerta</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Mensagens de alerta -->
            <?php if (isset($_SESSION['flash_messages'])): ?>
                <?php foreach ($_SESSION['flash_messages'] as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <?php unset($_SESSION['flash_messages']); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Configuração de Limiares</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Configure os limiares para alertas de performance. Quando uma métrica exceder o limiar configurado, um alerta será gerado.
                        Os limiares devem ser definidos com base nas características específicas do sistema e nos requisitos de performance.
                    </p>
                    
                    <form action="<?= BASE_URL ?>admin/performance_monitoring_dashboard/thresholds" method="post" id="thresholds-form">
                        <!-- Token CSRF -->
                        <input type="hidden" name="csrf_token" value="<?= SecurityManager::getCsrfToken() ?>">
                        
                        <!-- Limiares por contexto -->
                        <?php if (empty($thresholds)): ?>
                            <div class="alert alert-info">
                                Nenhuma configuração de limiar encontrada. Os valores padrão serão utilizados.
                            </div>
                        <?php else: ?>
                            <div class="nav-tabs-custom">
                                <ul class="nav nav-tabs" id="threshold-tabs" role="tablist">
                                    <?php $firstTab = true; ?>
                                    <?php foreach ($thresholds as $context => $metrics): ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?= $firstTab ? 'active' : '' ?>" id="tab-<?= htmlspecialchars($context) ?>" 
                                               data-toggle="pill" href="#content-<?= htmlspecialchars($context) ?>" role="tab">
                                                <?= htmlspecialchars(formatContextName($context)) ?>
                                            </a>
                                        </li>
                                        <?php $firstTab = false; ?>
                                    <?php endforeach; ?>
                                </ul>
                                
                                <div class="tab-content p-0" id="threshold-content">
                                    <?php $firstTab = true; ?>
                                    <?php foreach ($thresholds as $context => $metrics): ?>
                                        <div class="tab-pane fade <?= $firstTab ? 'show active' : '' ?>" id="content-<?= htmlspecialchars($context) ?>" 
                                             role="tabpanel" aria-labelledby="tab-<?= htmlspecialchars($context) ?>">
                                            <div class="card-body">
                                                <h5 class="mb-3"><?= htmlspecialchars(formatContextName($context)) ?></h5>
                                                
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 40%">Métrica</th>
                                                                <th style="width: 20%">Valor Atual</th>
                                                                <th style="width: 40%">Descrição</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($metrics as $metric => $config): ?>
                                                                <tr>
                                                                    <td>
                                                                        <label for="threshold_<?= htmlspecialchars($context) ?>_<?= htmlspecialchars($metric) ?>">
                                                                            <?= htmlspecialchars(formatMetricName($metric)) ?>
                                                                        </label>
                                                                    </td>
                                                                    <td>
                                                                        <div class="input-group">
                                                                            <input type="number" class="form-control" 
                                                                                   id="threshold_<?= htmlspecialchars($context) ?>_<?= htmlspecialchars($metric) ?>" 
                                                                                   name="threshold_<?= htmlspecialchars($context) ?>_<?= htmlspecialchars($metric) ?>" 
                                                                                   value="<?= htmlspecialchars($config['value']) ?>" 
                                                                                   step="0.01" min="0" required>
                                                                            <div class="input-group-append">
                                                                                <span class="input-group-text"><?= getMetricUnit($metric) ?></span>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <small class="text-muted">
                                                                            <?= htmlspecialchars($config['description'] ?? getMetricDescription($metric)) ?>
                                                                        </small>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <?php $firstTab = false; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                            <a href="<?= BASE_URL ?>admin/performance_monitoring_dashboard" class="btn btn-default">Voltar</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Histórico de Alertas -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Histórico de Alertas Recentes</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 40px">Tipo</th>
                                <th>Métrica / Contexto</th>
                                <th>Valor</th>
                                <th>Limiar</th>
                                <th style="width: 100px">Data</th>
                            </tr>
                        </thead>
                        <tbody id="recent-alerts">
                            <tr>
                                <td colspan="5" class="text-center">Carregando dados...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- CSRF Token para requisições AJAX -->
<input type="hidden" id="csrf_token" value="<?= SecurityManager::getCsrfToken() ?>">

<script>
document.addEventListener('DOMContentLoaded', function() {
    // CSRF token para requisições AJAX
    const csrfToken = document.getElementById('csrf_token').value;
    
    // Carregar alertas recentes
    loadRecentAlerts();
    
    // Função para carregar alertas recentes via AJAX
    function loadRecentAlerts() {
        fetch('<?= BASE_URL ?>admin/performance_monitoring_dashboard/getAlerts?type=performance&limit=10', {
            method: 'GET',
            headers: {
                'X-CSRF-Token': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            const alertsTable = document.getElementById('recent-alerts');
            alertsTable.innerHTML = '';
            
            if (data.alerts.length === 0) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 5;
                cell.textContent = 'Nenhum alerta recente.';
                cell.className = 'text-center';
                row.appendChild(cell);
                alertsTable.appendChild(row);
            } else {
                data.alerts.forEach(alert => {
                    if (alert.data && alert.data.metric) {
                        const row = document.createElement('tr');
                        
                        // Ícone
                        const cellIcon = document.createElement('td');
                        cellIcon.innerHTML = '<i class="fas fa-tachometer-alt"></i>';
                        row.appendChild(cellIcon);
                        
                        // Métrica / Contexto
                        const cellMetric = document.createElement('td');
                        const metricLink = document.createElement('a');
                        metricLink.href = `<?= BASE_URL ?>admin/performance_monitoring_dashboard/alertDetail?id=${alert.id}`;
                        metricLink.textContent = `${formatMetricName(alert.data.metric)} (${formatContextName(alert.data.context)})`;
                        cellMetric.appendChild(metricLink);
                        row.appendChild(cellMetric);
                        
                        // Valor
                        const cellValue = document.createElement('td');
                        cellValue.textContent = formatMetricValue(alert.data.metric, alert.data.value);
                        row.appendChild(cellValue);
                        
                        // Limiar
                        const cellThreshold = document.createElement('td');
                        cellThreshold.textContent = formatMetricValue(alert.data.metric, alert.data.threshold);
                        row.appendChild(cellThreshold);
                        
                        // Data
                        const cellDate = document.createElement('td');
                        const date = new Date(alert.created_at);
                        cellDate.innerHTML = `<small class="text-muted">${date.toLocaleDateString()} ${date.toLocaleTimeString()}</small>`;
                        row.appendChild(cellDate);
                        
                        alertsTable.appendChild(row);
                    }
                });
            }
        })
        .catch(error => {
            console.error('Erro ao carregar alertas:', error);
            document.getElementById('recent-alerts').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Erro ao carregar dados.</td></tr>';
        });
    }
    
    // Função para formatar nome de métricas
    function formatMetricName(metric) {
        const names = {
            'execution_time': 'Tempo de Execução',
            'memory_usage': 'Uso de Memória',
            'database_queries': 'Consultas de BD',
            'slow_queries': 'Consultas Lentas',
            'request_rate': 'Taxa de Requisições',
            'error_rate': 'Taxa de Erro',
            'process_duration': 'Duração de Processo',
            'min_progress_rate': 'Taxa Mínima de Progresso'
        };
        
        return names[metric] || metric;
    }
    
    // Função para formatar nome de contextos
    function formatContextName(context) {
        const names = {
            'global': 'Global',
            'url_performance': 'Performance de URL',
            'database': 'Banco de Dados',
            'async_process': 'Processos Assíncronos',
            'report_generation': 'Geração de Relatórios',
            '3d_model_processing': 'Processamento de Modelos 3D',
            'api': 'APIs',
            'admin': 'Admin',
            'checkout': 'Checkout'
        };
        
        return names[context] || context;
    }
    
    // Função para formatar valores de métricas
    function formatMetricValue(metric, value) {
        switch (metric) {
            case 'execution_time':
                return value.toFixed(2) + ' s';
            case 'memory_usage':
                return (value / (1024 * 1024)).toFixed(2) + ' MB';
            case 'database_queries':
            case 'slow_queries':
                return value.toString();
            case 'request_rate':
            case 'error_rate':
            case 'min_progress_rate':
                return value.toFixed(2) + '%';
            default:
                return value.toString();
        }
    }
});
</script>

<!-- Incluir footer administrativo -->
<?php require_once 'app/views/admin/partials/footer.php'; ?>

<?php
/**
 * Funções auxiliares para formatação dos nomes das métricas
 */

/**
 * Formata o nome de exibição do contexto
 * 
 * @param string $context Nome do contexto
 * @return string Nome formatado
 */
function formatContextName($context) {
    $names = [
        'global' => 'Global',
        'url_performance' => 'Performance de URL',
        'database' => 'Banco de Dados',
        'async_process' => 'Processos Assíncronos',
        'report_generation' => 'Geração de Relatórios',
        '3d_model_processing' => 'Processamento de Modelos 3D',
        'api' => 'APIs',
        'admin' => 'Admin',
        'checkout' => 'Checkout'
    ];
    
    return $names[$context] ?? $context;
}

/**
 * Formata o nome de exibição da métrica
 * 
 * @param string $metric Nome da métrica
 * @return string Nome formatado
 */
function formatMetricName($metric) {
    $names = [
        'execution_time' => 'Tempo de Execução',
        'memory_usage' => 'Uso de Memória',
        'database_queries' => 'Consultas de BD',
        'slow_queries' => 'Consultas Lentas',
        'request_rate' => 'Taxa de Requisições',
        'error_rate' => 'Taxa de Erro',
        'process_duration' => 'Duração de Processo',
        'min_progress_rate' => 'Taxa Mínima de Progresso'
    ];
    
    return $names[$metric] ?? $metric;
}

/**
 * Retorna a unidade de medida para a métrica
 * 
 * @param string $metric Nome da métrica
 * @return string Unidade de medida
 */
function getMetricUnit($metric) {
    $units = [
        'execution_time' => 's',
        'memory_usage' => 'MB',
        'database_queries' => '',
        'slow_queries' => '',
        'request_rate' => 'req/s',
        'error_rate' => '%',
        'process_duration' => 's',
        'min_progress_rate' => '%'
    ];
    
    return $units[$metric] ?? '';
}

/**
 * Retorna a descrição para a métrica
 * 
 * @param string $metric Nome da métrica
 * @return string Descrição
 */
function getMetricDescription($metric) {
    $descriptions = [
        'execution_time' => 'Tempo máximo de execução antes de gerar um alerta (em segundos).',
        'memory_usage' => 'Uso máximo de memória antes de gerar um alerta (em MB). O valor deve ser informado em bytes.',
        'database_queries' => 'Número máximo de consultas de banco de dados antes de gerar um alerta.',
        'slow_queries' => 'Número máximo de consultas lentas antes de gerar um alerta.',
        'request_rate' => 'Taxa máxima de requisições por segundo antes de gerar um alerta.',
        'error_rate' => 'Taxa máxima de erros (percentual) antes de gerar um alerta.',
        'process_duration' => 'Duração máxima de um processo assíncrono antes de gerar um alerta de timeout (em segundos).',
        'min_progress_rate' => 'Taxa mínima de progresso esperada como proporção do tempo decorrido/tempo total. Valores abaixo deste limiar geram alertas.'
    ];
    
    return $descriptions[$metric] ?? 'Limiar para a métrica.';
}
?>
