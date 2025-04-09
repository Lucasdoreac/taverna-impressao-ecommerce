<?php
/**
 * Histórico de Alertas - View
 * 
 * Exibe o histórico completo de alertas de performance com filtragem e paginação.
 * Implementa controles de segurança e sanitização de saída.
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Views\Admin\Monitoring
 * @version    1.0.0
 */

// Incluir cabeçalho e menu da área administrativa
require_once __DIR__ . '/../../partials/admin/header.php';
require_once __DIR__ . '/../../partials/admin/sidebar.php';

// Parâmetros de filtragem atuais
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$currentComponent = isset($_GET['component']) ? htmlspecialchars($_GET['component']) : '';
$currentSeverity = isset($_GET['severity']) ? htmlspecialchars($_GET['severity']) : '';

// Severidades válidas
$validSeverities = ['critical', 'high', 'medium', 'low'];
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Histórico de Alertas</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                        <li class="breadcrumb-item"><a href="/admin/monitoring">Monitoramento</a></li>
                        <li class="breadcrumb-item active">Histórico de Alertas</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <!-- Filtros -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Filtros</h3>
                        </div>
                        <div class="card-body">
                            <form action="/admin/monitoring/alert-history" method="get" id="filter-form">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="component">Componente</label>
                                            <select class="form-control" id="component" name="component">
                                                <option value="">Todos os Componentes</option>
                                                <?php foreach ($availableComponents as $component): ?>
                                                    <option value="<?= htmlspecialchars($component) ?>" 
                                                            <?= $currentComponent === $component ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($component) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="severity">Severidade</label>
                                            <select class="form-control" id="severity" name="severity">
                                                <option value="">Todas as Severidades</option>
                                                <?php foreach ($validSeverities as $severity): ?>
                                                    <option value="<?= htmlspecialchars($severity) ?>"
                                                            <?= $currentSeverity === $severity ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars(ucfirst($severity)) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <div class="d-flex">
                                                <button type="submit" class="btn btn-primary btn-block">
                                                    <i class="fas fa-filter mr-1"></i> Filtrar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Histórico de Alertas -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-1"></i>
                                Histórico de Alertas de Performance
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($alertHistory)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                                    <p class="lead">Nenhum alerta encontrado com os filtros selecionados.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Métrica</th>
                                                <th>Componente</th>
                                                <th>Valor</th>
                                                <th>Threshold</th>
                                                <th>Severidade</th>
                                                <th>Detalhes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($alertHistory as $alert): ?>
                                                <?php
                                                $severityClass = '';
                                                switch ($alert['severity']) {
                                                    case 'critical': $severityClass = 'badge-danger'; break;
                                                    case 'high': $severityClass = 'badge-warning'; break;
                                                    case 'medium': $severityClass = 'badge-primary'; break;
                                                    case 'low': $severityClass = 'badge-info'; break;
                                                    default: $severityClass = 'badge-secondary';
                                                }
                                                
                                                // Decodificar contexto JSON
                                                $context = [];
                                                if (!empty($alert['context'])) {
                                                    if (is_array($alert['context'])) {
                                                        $context = $alert['context'];
                                                    } else {
                                                        $context = json_decode($alert['context'], true) ?: [];
                                                    }
                                                }
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($alert['created_at']))) ?></td>
                                                    <td><?= htmlspecialchars($alert['metric']) ?></td>
                                                    <td><?= htmlspecialchars($alert['component']) ?></td>
                                                    <td><?= htmlspecialchars(number_format($alert['value'], 2)) ?></td>
                                                    <td><?= htmlspecialchars(number_format($alert['threshold'], 2)) ?></td>
                                                    <td>
                                                        <span class="badge <?= $severityClass ?>">
                                                            <?= htmlspecialchars(ucfirst($alert['severity'])) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-info"
                                                                data-toggle="modal"
                                                                data-target="#alertDetailsModal"
                                                                data-alert-id="<?= (int)$alert['id'] ?>"
                                                                data-alert-metric="<?= htmlspecialchars($alert['metric']) ?>"
                                                                data-alert-component="<?= htmlspecialchars($alert['component']) ?>"
                                                                data-alert-value="<?= htmlspecialchars(number_format($alert['value'], 2)) ?>"
                                                                data-alert-threshold="<?= htmlspecialchars(number_format($alert['threshold'], 2)) ?>"
                                                                data-alert-severity="<?= htmlspecialchars(ucfirst($alert['severity'])) ?>"
                                                                data-alert-timestamp="<?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($alert['created_at']))) ?>"
                                                                data-alert-context='<?= htmlspecialchars(json_encode($context, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>
                                                            <i class="fas fa-info-circle"></i> Detalhes
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Paginação -->
                                <?php if ($totalPages > 1): ?>
                                    <div class="d-flex justify-content-center mt-4">
                                        <ul class="pagination">
                                            <?php if ($currentPage > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="<?= $this->buildPaginationUrl($currentPage - 1, $currentComponent, $currentSeverity) ?>">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </a>
                                                </li>
                                            <?php else: ?>
                                                <li class="page-item disabled">
                                                    <a class="page-link" href="#"><i class="fas fa-chevron-left"></i></a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php
                                            // Determinar quais páginas mostrar
                                            $startPage = max(1, $currentPage - 2);
                                            $endPage = min($totalPages, $startPage + 4);
                                            
                                            if ($endPage - $startPage < 4) {
                                                $startPage = max(1, $endPage - 4);
                                            }
                                            
                                            for ($i = $startPage; $i <= $endPage; $i++):
                                            ?>
                                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                                    <a class="page-link" href="<?= $this->buildPaginationUrl($i, $currentComponent, $currentSeverity) ?>">
                                                        <?= $i ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($currentPage < $totalPages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="<?= $this->buildPaginationUrl($currentPage + 1, $currentComponent, $currentSeverity) ?>">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </a>
                                                </li>
                                            <?php else: ?>
                                                <li class="page-item disabled">
                                                    <a class="page-link" href="#"><i class="fas fa-chevron-right"></i></a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Detalhes do Alerta -->
<div class="modal fade" id="alertDetailsModal" tabindex="-1" role="dialog" aria-labelledby="alertDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="alertDetailsModalLabel">Detalhes do Alerta</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Métrica</label>
                            <p id="modalAlertMetric" class="form-control-static"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Componente</label>
                            <p id="modalAlertComponent" class="form-control-static"></p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Valor</label>
                            <p id="modalAlertValue" class="form-control-static"></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Threshold</label>
                            <p id="modalAlertThreshold" class="form-control-static"></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Severidade</label>
                            <p id="modalAlertSeverity" class="form-control-static"></p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Data e Hora</label>
                            <p id="modalAlertTimestamp" class="form-control-static"></p>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <h6>Contexto Adicional</h6>
                <div id="modalAlertContext" class="bg-light p-3 rounded pre-scrollable mb-0" style="max-height: 200px;">
                    <!-- Contexto será inserido aqui via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Função de escape de HTML para prevenir XSS
    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    // Configurar modal para detalhes do alerta
    $('#alertDetailsModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const modal = $(this);
        
        // Definir valores básicos
        modal.find('#modalAlertMetric').text(button.data('alert-metric'));
        modal.find('#modalAlertComponent').text(button.data('alert-component'));
        modal.find('#modalAlertValue').text(button.data('alert-value'));
        modal.find('#modalAlertThreshold').text(button.data('alert-threshold'));
        modal.find('#modalAlertTimestamp').text(button.data('alert-timestamp'));
        
        // Definir a severidade com a classe de cor apropriada
        const severity = button.data('alert-severity');
        let severityClass = '';
        
        switch (severity.toLowerCase()) {
            case 'critical': severityClass = 'text-danger'; break;
            case 'high': severityClass = 'text-warning'; break;
            case 'medium': severityClass = 'text-primary'; break;
            case 'low': severityClass = 'text-info'; break;
            default: severityClass = 'text-secondary';
        }
        
        modal.find('#modalAlertSeverity').html(`<span class="${severityClass}">${severity}</span>`);
        
        // Processar e exibir o contexto
        let contextHtml = '';
        try {
            const contextData = button.data('alert-context');
            if (contextData && typeof contextData === 'object') {
                contextHtml = '<table class="table table-sm table-striped mb-0">';
                for (const [key, value] of Object.entries(contextData)) {
                    contextHtml += `<tr>
                        <td><strong>${escapeHtml(key)}</strong></td>
                        <td>${escapeHtml(JSON.stringify(value, null, 2))}</td>
                    </tr>`;
                }
                contextHtml += '</table>';
            } else {
                contextHtml = '<div class="text-muted">Nenhum contexto adicional disponível.</div>';
            }
        } catch (e) {
            contextHtml = `<div class="text-danger">Erro ao processar contexto: ${escapeHtml(e.message)}</div>`;
        }
        
        modal.find('#modalAlertContext').html(contextHtml);
    });
});
</script>

<?php
// Incluir rodapé da área administrativa
require_once __DIR__ . '/../../partials/admin/footer.php';

/**
 * Método auxiliar para construir URLs de paginação
 * 
 * @param int $page Número da página
 * @param string $component Filtro de componente
 * @param string $severity Filtro de severidade
 * @return string URL formatada
 */
function buildPaginationUrl($page, $component, $severity) {
    $url = '/admin/monitoring/alert-history?page=' . $page;
    
    if (!empty($component)) {
        $url .= '&component=' . urlencode($component);
    }
    
    if (!empty($severity)) {
        $url .= '&severity=' . urlencode($severity);
    }
    
    return $url;
}
?>
