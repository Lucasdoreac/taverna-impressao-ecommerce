<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * View de Detalhes de Alerta de Performance
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
                    <h1 class="m-0">Detalhes do Alerta</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/performance_monitoring_dashboard">Monitoramento de Performance</a></li>
                        <li class="breadcrumb-item active">Detalhes do Alerta</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if (!$alert): ?>
                <div class="alert alert-danger">
                    <h5><i class="icon fas fa-ban"></i> Erro!</h5>
                    Alerta não encontrado.
                </div>
            <?php else: ?>
                <!-- Cartão de informações básicas -->
                <div class="card">
                    <div class="card-header">
                        <?php
                        $headerClass = 'bg-info';
                        switch ($alert['severity']) {
                            case 'warning':
                                $headerClass = 'bg-warning';
                                break;
                            case 'error':
                                $headerClass = 'bg-danger';
                                break;
                            case 'critical':
                                $headerClass = 'bg-dark';
                                break;
                        }
                        ?>
                        <h3 class="card-title <?= $headerClass ?>">
                            <?php
                            $icon = 'info-circle';
                            $title = 'Alerta de Informação';
                            
                            switch ($alert['alert_type']) {
                                case 'performance':
                                    $icon = 'tachometer-alt';
                                    $title = 'Alerta de Performance';
                                    break;
                                case 'timeout':
                                    $icon = 'clock';
                                    $title = 'Alerta de Timeout';
                                    break;
                                case 'slow_progress':
                                    $icon = 'hourglass-half';
                                    $title = 'Alerta de Progresso Lento';
                                    break;
                                case 'error':
                                    $icon = 'exclamation-triangle';
                                    $title = 'Alerta de Erro';
                                    break;
                            }
                            ?>
                            <i class="fas fa-<?= $icon ?> mr-2"></i> <?= htmlspecialchars($title) ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">ID do Alerta:</dt>
                                    <dd class="col-sm-8"><?= htmlspecialchars($alert['id']) ?></dd>
                                    
                                    <dt class="col-sm-4">Tipo:</dt>
                                    <dd class="col-sm-8"><?= htmlspecialchars(ucfirst($alert['alert_type'])) ?></dd>
                                    
                                    <dt class="col-sm-4">Severidade:</dt>
                                    <dd class="col-sm-8">
                                        <?php
                                        $severityClass = 'info';
                                        switch ($alert['severity']) {
                                            case 'warning':
                                                $severityClass = 'warning';
                                                break;
                                            case 'error':
                                                $severityClass = 'danger';
                                                break;
                                            case 'critical':
                                                $severityClass = 'dark';
                                                break;
                                        }
                                        ?>
                                        <span class="badge bg-<?= $severityClass ?>"><?= htmlspecialchars(ucfirst($alert['severity'])) ?></span>
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-4">Data:</dt>
                                    <dd class="col-sm-8"><?= date('d/m/Y H:i:s', strtotime($alert['created_at'])) ?></dd>
                                    
                                    <?php if (isset($alert['data']['context'])): ?>
                                        <dt class="col-sm-4">Contexto:</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($alert['data']['context']) ?></dd>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($alert['data']['user_id']) && $alert['data']['user_id'] > 0): ?>
                                        <dt class="col-sm-4">Usuário:</dt>
                                        <dd class="col-sm-8">
                                            <a href="<?= BASE_URL ?>admin/users/view/<?= htmlspecialchars($alert['data']['user_id']) ?>">
                                                ID: <?= htmlspecialchars($alert['data']['user_id']) ?>
                                            </a>
                                        </dd>
                                    <?php endif; ?>
                                </dl>
                            </div>
                        </div>

                        <!-- Detalhes específicos do tipo de alerta -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-muted">Detalhes do Alerta</h5>
                                
                                <?php if ($alert['alert_type'] === 'performance'): ?>
                                    <div class="callout callout-info">
                                        <h5>Alerta de Performance - <?= htmlspecialchars($alert['data']['metric'] ?? 'Desconhecido') ?></h5>
                                        <p>
                                            A métrica <strong><?= htmlspecialchars($alert['data']['metric'] ?? 'desconhecida') ?></strong> 
                                            atingiu o valor <strong><?= formatMetricValue($alert['data']['metric'] ?? '', $alert['data']['value'] ?? 0) ?></strong>, 
                                            excedendo o limite configurado de <strong><?= formatMetricValue($alert['data']['metric'] ?? '', $alert['data']['threshold'] ?? 0) ?></strong>.
                                        </p>
                                        
                                        <?php if (isset($alert['data']['url'])): ?>
                                            <p>URL: <code><?= htmlspecialchars($alert['data']['url']) ?></code></p>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($alert['data']['method'])): ?>
                                            <p>Método HTTP: <strong><?= htmlspecialchars($alert['data']['method']) ?></strong></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                <?php elseif ($alert['alert_type'] === 'timeout'): ?>
                                    <div class="callout callout-warning">
                                        <h5>Alerta de Timeout de Processo</h5>
                                        <p>
                                            O processo <strong><?= htmlspecialchars($alert['data']['process_name'] ?? 'desconhecido') ?></strong> 
                                            (ID: <?= htmlspecialchars($alert['data']['process_id'] ?? 'n/a') ?>) 
                                            excedeu o tempo máximo de execução.
                                        </p>
                                        <dl class="row">
                                            <dt class="col-sm-3">Tipo de Processo:</dt>
                                            <dd class="col-sm-9"><?= htmlspecialchars($alert['data']['process_type'] ?? 'Desconhecido') ?></dd>
                                            
                                            <dt class="col-sm-3">Tempo Decorrido:</dt>
                                            <dd class="col-sm-9"><?= formatDuration($alert['data']['elapsed_time'] ?? 0) ?></dd>
                                            
                                            <dt class="col-sm-3">Tempo Máximo:</dt>
                                            <dd class="col-sm-9"><?= formatDuration($alert['data']['max_duration'] ?? 0) ?></dd>
                                            
                                            <dt class="col-sm-3">Excesso:</dt>
                                            <dd class="col-sm-9"><?= htmlspecialchars(number_format(($alert['data']['overage_ratio'] ?? 1) * 100 - 100, 1)) ?>%</dd>
                                            
                                            <dt class="col-sm-3">Status Atual:</dt>
                                            <dd class="col-sm-9"><?= htmlspecialchars(ucfirst($alert['data']['current_status'] ?? 'desconhecido')) ?></dd>
                                            
                                            <dt class="col-sm-3">Início:</dt>
                                            <dd class="col-sm-9"><?= htmlspecialchars($alert['data']['started_at'] ?? 'Desconhecido') ?></dd>
                                        </dl>
                                    </div>
                                    
                                <?php elseif ($alert['alert_type'] === 'slow_progress'): ?>
                                    <div class="callout callout-warning">
                                        <h5>Alerta de Progresso Lento</h5>
                                        <p>
                                            O processo <strong><?= htmlspecialchars($alert['data']['process_name'] ?? 'desconhecido') ?></strong> 
                                            (ID: <?= htmlspecialchars($alert['data']['process_id'] ?? 'n/a') ?>) 
                                            está progredindo mais lentamente do que o esperado.
                                        </p>
                                        <dl class="row">
                                            <dt class="col-sm-3">Tipo de Processo:</dt>
                                            <dd class="col-sm-9"><?= htmlspecialchars($alert['data']['process_type'] ?? 'Desconhecido') ?></dd>
                                            
                                            <dt class="col-sm-3">Progresso Atual:</dt>
                                            <dd class="col-sm-9"><?= htmlspecialchars($alert['data']['actual_progress'] ?? 0) ?>%</dd>
                                            
                                            <dt class="col-sm-3">Progresso Esperado:</dt>
                                            <dd class="col-sm-9"><?= htmlspecialchars($alert['data']['expected_progress'] ?? 0) ?>%</dd>
                                            
                                            <dt class="col-sm-3">Defasagem:</dt>
                                            <dd class="col-sm-9"><?= htmlspecialchars($alert['data']['progress_gap'] ?? 0) ?>%</dd>
                                            
                                            <dt class="col-sm-3">Tempo Decorrido:</dt>
                                            <dd class="col-sm-9"><?= formatDuration($alert['data']['elapsed_time'] ?? 0) ?></dd>
                                            
                                            <dt class="col-sm-3">Status Atual:</dt>
                                            <dd class="col-sm-9"><?= htmlspecialchars(ucfirst($alert['data']['current_status'] ?? 'desconhecido')) ?></dd>
                                        </dl>
                                    </div>
                                    
                                <?php elseif ($alert['alert_type'] === 'error'): ?>
                                    <div class="callout callout-danger">
                                        <h5>Alerta de Erro</h5>
                                        <?php if (isset($alert['data']['process_id'])): ?>
                                            <p>
                                                Ocorreu um erro no processo <strong><?= htmlspecialchars($alert['data']['process_name'] ?? 'desconhecido') ?></strong> 
                                                (ID: <?= htmlspecialchars($alert['data']['process_id'] ?? 'n/a') ?>).
                                            </p>
                                            <dl class="row">
                                                <dt class="col-sm-3">Tipo de Processo:</dt>
                                                <dd class="col-sm-9"><?= htmlspecialchars($alert['data']['process_type'] ?? 'Desconhecido') ?></dd>
                                                
                                                <dt class="col-sm-3">Mensagem de Erro:</dt>
                                                <dd class="col-sm-9"><code><?= htmlspecialchars($alert['data']['error_message'] ?? 'Erro não especificado') ?></code></dd>
                                                
                                                <?php if (isset($alert['data']['error_code'])): ?>
                                                    <dt class="col-sm-3">Código de Erro:</dt>
                                                    <dd class="col-sm-9"><?= htmlspecialchars($alert['data']['error_code']) ?></dd>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($alert['data']['error_trace'])): ?>
                                                    <dt class="col-sm-3">Stack Trace:</dt>
                                                    <dd class="col-sm-9">
                                                        <pre class="bg-light p-2" style="max-height: 200px; overflow-y: auto;"><?= htmlspecialchars($alert['data']['error_trace']) ?></pre>
                                                    </dd>
                                                <?php endif; ?>
                                            </dl>
                                        <?php else: ?>
                                            <p>
                                                Ocorreu um erro no sistema.
                                            </p>
                                            <dl class="row">
                                                <dt class="col-sm-3">Mensagem de Erro:</dt>
                                                <dd class="col-sm-9"><code><?= htmlspecialchars($alert['data']['error_message'] ?? 'Erro não especificado') ?></code></dd>
                                                
                                                <?php if (isset($alert['data']['url'])): ?>
                                                    <dt class="col-sm-3">URL:</dt>
                                                    <dd class="col-sm-9"><code><?= htmlspecialchars($alert['data']['url']) ?></code></dd>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($alert['data']['error_code'])): ?>
                                                    <dt class="col-sm-3">Código de Erro:</dt>
                                                    <dd class="col-sm-9"><?= htmlspecialchars($alert['data']['error_code']) ?></dd>
                                                <?php endif; ?>
                                            </dl>
                                        <?php endif; ?>
                                    </div>
                                    
                                <?php else: ?>
                                    <div class="callout callout-info">
                                        <h5>Detalhes do Alerta</h5>
                                        <pre class="bg-light p-2"><?= htmlspecialchars(json_encode($alert['data'], JSON_PRETTY_PRINT)) ?></pre>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Dados completos do alerta (raw) -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card collapsed-card">
                                    <div class="card-header">
                                        <h3 class="card-title">Dados Brutos do Alerta</h3>
                                        <div class="card-tools">
                                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body" style="display: none;">
                                        <pre><?= htmlspecialchars(json_encode($alert, JSON_PRETTY_PRINT)) ?></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="<?= BASE_URL ?>admin/performance_monitoring_dashboard" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Voltar para o Dashboard
                        </a>
                        
                        <!-- Botões específicos para cada tipo de alerta -->
                        <?php if ($alert['alert_type'] === 'timeout' || $alert['alert_type'] === 'slow_progress'): ?>
                            <?php if (isset($alert['data']['process_id'])): ?>
                                <a href="<?= BASE_URL ?>admin/async_process/view/<?= htmlspecialchars($alert['data']['process_id']) ?>" class="btn btn-primary float-right">
                                    <i class="fas fa-eye mr-1"></i> Ver Processo
                                </a>
                            <?php endif; ?>
                        <?php elseif ($alert['alert_type'] === 'performance'): ?>
                            <?php if (isset($alert['data']['context']) && $alert['data']['context'] === 'url_performance' && isset($alert['data']['url'])): ?>
                                <a href="<?= BASE_URL ?>admin/performance_monitoring_dashboard/urlReport?url=<?= urlencode($alert['data']['url']) ?>" class="btn btn-primary float-right">
                                    <i class="fas fa-chart-line mr-1"></i> Análise de URL
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- Incluir footer administrativo -->
<?php require_once 'app/views/admin/partials/footer.php'; ?>

<?php
/**
 * Formata o valor de uma métrica com base em seu tipo
 * 
 * @param string $metric Nome da métrica
 * @param mixed $value Valor
 * @return string Valor formatado
 */
function formatMetricValue($metric, $value) {
    switch ($metric) {
        case 'execution_time':
            return formatDuration($value);
            
        case 'memory_usage':
            return formatBytes($value);
            
        case 'database_queries':
            return $value . ' consultas';
            
        default:
            return $value;
    }
}

/**
 * Formata duração em segundos para formato legível
 * 
 * @param int $seconds Duração em segundos
 * @return string Duração formatada
 */
function formatDuration($seconds) {
    if ($seconds < 60) {
        return $seconds . ' segundos';
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        $sec = $seconds % 60;
        return $minutes . ' minuto' . ($minutes > 1 ? 's' : '') . 
               ($sec > 0 ? ' e ' . $sec . ' segundo' . ($sec > 1 ? 's' : '') : '');
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . ' hora' . ($hours > 1 ? 's' : '') . 
               ($minutes > 0 ? ' e ' . $minutes . ' minuto' . ($minutes > 1 ? 's' : '') : '');
    }
}

/**
 * Formata bytes para formato legível
 * 
 * @param int $bytes Tamanho em bytes
 * @return string Tamanho formatado
 */
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>
