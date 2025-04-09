<?php
/**
 * Gerenciamento de Thresholds - View
 * 
 * Interface para visualização, configuração e ajuste de thresholds de métricas de performance.
 * Implementa controles de segurança CSRF e validação de entrada.
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Views\Admin\Monitoring
 * @version    1.0.0
 */

// Incluir cabeçalho e menu da área administrativa
require_once __DIR__ . '/../../partials/admin/header.php';
require_once __DIR__ . '/../../partials/admin/sidebar.php';

// CSRF Token para formulários
$csrfToken = $this->securityManager->generateCsrfToken();
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Gerenciamento de Thresholds</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                        <li class="breadcrumb-item"><a href="/admin/monitoring">Monitoramento</a></li>
                        <li class="breadcrumb-item active">Thresholds</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <?php
                    $message = '';
                    switch ($_GET['message']) {
                        case 'threshold_updated':
                            $message = 'Threshold atualizado com sucesso!';
                            break;
                        case 'metric_silenced':
                            $message = 'Métrica silenciada com sucesso!';
                            break;
                        case 'metric_unsilenced':
                            $message = 'Silenciamento da métrica removido com sucesso!';
                            break;
                        default:
                            $message = 'Operação realizada com sucesso!';
                    }
                    echo htmlspecialchars($message);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <?php
                    $error = '';
                    switch ($_GET['error']) {
                        case 'invalid_csrf':
                            $error = 'Token de segurança inválido. Tente novamente.';
                            break;
                        case 'invalid_metric':
                            $error = 'Métrica inválida ou não especificada.';
                            break;
                        case 'failed_to_update':
                            $error = 'Não foi possível atualizar o threshold. Verifique o log de erros.';
                            break;
                        case 'failed_to_silence':
                            $error = 'Não foi possível silenciar a métrica. Verifique o log de erros.';
                            break;
                        case 'failed_to_unsilence':
                            $error = 'Não foi possível remover o silenciamento da métrica. Verifique o log de erros.';
                            break;
                        default:
                            $error = 'Ocorreu um erro durante a operação.';
                    }
                    echo htmlspecialchars($error);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Thresholds Ativos -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-sliders-h mr-1"></i>
                                Thresholds Ativos
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($allThresholds)): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Nenhum threshold definido. Utilize o botão abaixo para configurar thresholds padrão.
                                </div>
                                <div class="text-center">
                                    <form action="/admin/monitoring/set-default-thresholds" method="post" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-magic mr-1"></i> Configurar Thresholds Padrão
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Métrica</th>
                                                <th>Threshold</th>
                                                <th>Operador</th>
                                                <th>Descrição</th>
                                                <th>Última Atualização</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($allThresholds as $metricName => $threshold): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($metricName) ?></td>
                                                    <td><?= htmlspecialchars(number_format($threshold['value'], 2)) ?></td>
                                                    <td><code><?= htmlspecialchars($threshold['operator']) ?></code></td>
                                                    <td><?= htmlspecialchars($threshold['description']) ?></td>
                                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($threshold['updated_at']))) ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-primary"
                                                                    data-toggle="modal"
                                                                    data-target="#editThresholdModal"
                                                                    data-metric="<?= htmlspecialchars($metricName) ?>"
                                                                    data-value="<?= htmlspecialchars($threshold['value']) ?>"
                                                                    data-operator="<?= htmlspecialchars($threshold['operator']) ?>"
                                                                    data-description="<?= htmlspecialchars($threshold['description']) ?>">
                                                                <i class="fas fa-edit"></i> Editar
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-warning"
                                                                    data-toggle="modal"
                                                                    data-target="#silenceMetricModal"
                                                                    data-metric="<?= htmlspecialchars($metricName) ?>">
                                                                <i class="fas fa-bell-slash"></i> Silenciar
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-right mt-3">
                                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addThresholdModal">
                                        <i class="fas fa-plus mr-1"></i> Adicionar Novo Threshold
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Métricas Silenciadas -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-bell-slash mr-1"></i>
                                Métricas Silenciadas
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php
                            // Obter métricas silenciadas do banco de dados
                            $silencedMetrics = [];
                            try {
                                $db = Database::getInstance();
                                $sql = "SELECT metric, component, created_at, expires_at, created_by, u.name as user_name 
                                        FROM performance_alert_silencing s
                                        LEFT JOIN users u ON s.created_by = u.id
                                        WHERE expires_at > NOW()
                                        ORDER BY expires_at ASC";
                                $silencedMetrics = $db->fetchAll($sql) ?: [];
                            } catch (Exception $e) {
                                error_log('Erro ao obter métricas silenciadas: ' . $e->getMessage());
                            }
                            ?>
                            
                            <?php if (empty($silencedMetrics)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                                    <p class="lead">Nenhuma métrica silenciada no momento.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Métrica</th>
                                                <th>Componente</th>
                                                <th>Silenciado Por</th>
                                                <th>Silenciado Em</th>
                                                <th>Expira Em</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($silencedMetrics as $metric): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($metric['metric']) ?></td>
                                                    <td><?= $metric['component'] ? htmlspecialchars($metric['component']) : '<em>Todos</em>' ?></td>
                                                    <td><?= htmlspecialchars($metric['user_name']) ?></td>
                                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($metric['created_at']))) ?></td>
                                                    <td>
                                                        <?php
                                                        $expiresAt = strtotime($metric['expires_at']);
                                                        $now = time();
                                                        $remainingSeconds = max(0, $expiresAt - $now);
                                                        $remainingHours = floor($remainingSeconds / 3600);
                                                        $remainingMinutes = floor(($remainingSeconds % 3600) / 60);
                                                        
                                                        echo htmlspecialchars(date('d/m/Y H:i', $expiresAt));
                                                        echo ' <span class="text-muted">(' . $remainingHours . 'h ' . $remainingMinutes . 'min)</span>';
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <form action="/admin/monitoring/unsilence-metric" method="post" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                            <input type="hidden" name="metric" value="<?= htmlspecialchars($metric['metric']) ?>">
                                                            <?php if ($metric['component']): ?>
                                                                <input type="hidden" name="component" value="<?= htmlspecialchars($metric['component']) ?>">
                                                            <?php endif; ?>
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-bell"></i> Remover Silenciamento
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Histórico de Ajustes -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-1"></i>
                                Histórico de Ajustes de Threshold
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($thresholdAdjustments)): ?>
                                <div class="text-center py-4">
                                    <p class="lead">Nenhum ajuste de threshold registrado.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Métrica</th>
                                                <th>Novo Valor</th>
                                                <th>Operador</th>
                                                <th>Tipo de Ajuste</th>
                                                <th>Realizado Por</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($thresholdAdjustments as $adjustment): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($adjustment['timestamp']))) ?></td>
                                                    <td><?= htmlspecialchars($adjustment['metric']) ?></td>
                                                    <td><?= htmlspecialchars(number_format($adjustment['new_value'], 2)) ?></td>
                                                    <td><code><?= htmlspecialchars($adjustment['operator']) ?></code></td>
                                                    <td>
                                                        <?php if ($adjustment['adjustment_type'] === 'auto'): ?>
                                                            <span class="badge badge-info">Automático</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-primary">Manual</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($adjustment['adjustment_type'] === 'auto'): ?>
                                                            <em>Sistema</em>
                                                        <?php else: ?>
                                                            <?= htmlspecialchars($adjustment['user_name'] ?? 'Usuário Desconhecido') ?>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Editar Threshold -->
<div class="modal fade" id="editThresholdModal" tabindex="-1" role="dialog" aria-labelledby="editThresholdModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editThresholdModalLabel">Editar Threshold</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="/admin/monitoring/update-threshold" method="post">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="metric" id="editMetricName">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="editThresholdValue">Valor do Threshold</label>
                        <input type="number" class="form-control" id="editThresholdValue" name="threshold" 
                               step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="editOperator">Operador</label>
                        <select class="form-control" id="editOperator" name="operator" required>
                            <option value=">">Maior que (&gt;)</option>
                            <option value="<">Menor que (&lt;)</option>
                            <option value=">=">Maior ou igual a (&gt;=)</option>
                            <option value="<=">Menor ou igual a (&lt;=)</option>
                            <option value="==">Igual a (==)</option>
                        </select>
                        <small class="form-text text-muted">
                            Define como o valor da métrica será comparado com o threshold.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="editDescription">Descrição</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Adicionar Novo Threshold -->
<div class="modal fade" id="addThresholdModal" tabindex="-1" role="dialog" aria-labelledby="addThresholdModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addThresholdModalLabel">Adicionar Novo Threshold</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="/admin/monitoring/update-threshold" method="post">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="newMetricName">Nome da Métrica</label>
                        <input type="text" class="form-control" id="newMetricName" name="metric" 
                               required maxlength="255" pattern="[a-zA-Z0-9_]+">
                        <small class="form-text text-muted">
                            Apenas letras, números e underscore (_).
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="newThresholdValue">Valor do Threshold</label>
                        <input type="number" class="form-control" id="newThresholdValue" name="threshold" 
                               step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="newOperator">Operador</label>
                        <select class="form-control" id="newOperator" name="operator" required>
                            <option value=">">Maior que (&gt;)</option>
                            <option value="<">Menor que (&lt;)</option>
                            <option value=">=">Maior ou igual a (&gt;=)</option>
                            <option value="<=">Menor ou igual a (&lt;=)</option>
                            <option value="==">Igual a (==)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="newDescription">Descrição</label>
                        <textarea class="form-control" id="newDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Adicionar Threshold</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Silenciar Métrica -->
<div class="modal fade" id="silenceMetricModal" tabindex="-1" role="dialog" aria-labelledby="silenceMetricModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="silenceMetricModalLabel">Silenciar Métrica</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="/admin/monitoring/silence-metric" method="post">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="metric" id="silenceMetricName">
                
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-bell-slash mr-2"></i>
                        <strong>Atenção:</strong> Silenciar uma métrica irá desativar temporariamente todos os alertas relacionados a ela.
                    </div>
                    
                    <div class="form-group">
                        <label for="silenceComponent">Componente (opcional)</label>
                        <input type="text" class="form-control" id="silenceComponent" name="component" 
                               maxlength="255">
                        <small class="form-text text-muted">
                            Deixe em branco para silenciar a métrica em todos os componentes.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="silenceDuration">Duração</label>
                        <select class="form-control" id="silenceDuration" name="duration" required>
                            <option value="900">15 minutos</option>
                            <option value="1800">30 minutos</option>
                            <option value="3600" selected>1 hora</option>
                            <option value="7200">2 horas</option>
                            <option value="14400">4 horas</option>
                            <option value="43200">12 horas</option>
                            <option value="86400">1 dia</option>
                            <option value="604800">7 dias</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Silenciar Métrica</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar modal para editar threshold
    $('#editThresholdModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const metric = button.data('metric');
        const value = button.data('value');
        const operator = button.data('operator');
        const description = button.data('description');
        
        const modal = $(this);
        modal.find('#editMetricName').val(metric);
        modal.find('#editThresholdValue').val(value);
        modal.find('#editOperator').val(operator);
        modal.find('#editDescription').val(description);
    });
    
    // Configurar modal para silenciar métrica
    $('#silenceMetricModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const metric = button.data('metric');
        
        const modal = $(this);
        modal.find('#silenceMetricName').val(metric);
    });
});
</script>

<?php
// Incluir rodapé da área administrativa
require_once __DIR__ . '/../../partials/admin/footer.php';
?>
