<?php
/**
 * View para logs de webhooks de pagamento
 * 
 * Exibe e permite filtrar webhooks recebidos dos gateways de pagamento
 * 
 * @package     App\Views\Admin\Payment
 * @version     1.0.0
 * @author      Taverna da Impressão
 */
?>

<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?= htmlspecialchars($pageTitle) ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/pagamentos">Pagamentos</a></li>
                        <li class="breadcrumb-item active">Webhooks</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <!-- Filtros -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Filtros</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="get" action="<?= BASE_URL ?>admin/pagamentos/webhooks">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="gateway">Gateway</label>
                                            <select class="form-control" id="gateway" name="gateway">
                                                <option value="">Todos</option>
                                                <?php foreach ($availableGateways as $g): ?>
                                                    <option value="<?= htmlspecialchars($g) ?>" <?= $gateway === $g ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars(ucfirst($g)) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="event_type">Tipo de Evento</label>
                                            <select class="form-control" id="event_type" name="event_type">
                                                <option value="">Todos</option>
                                                <?php foreach ($availableEventTypes as $et): ?>
                                                    <option value="<?= htmlspecialchars($et) ?>" <?= $eventType === $et ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($et) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="status">Status</label>
                                            <select class="form-control" id="status" name="status">
                                                <option value="">Todos</option>
                                                <option value="success" <?= $status === 'success' ? 'selected' : '' ?>>Sucesso</option>
                                                <option value="error" <?= $status === 'error' ? 'selected' : '' ?>>Erro</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="start_date">Data Inicial</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate ?? '') ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="end_date">Data Final</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate ?? '') ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <div>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-search"></i> Filtrar
                                                </button>
                                                <a href="<?= BASE_URL ?>admin/pagamentos/webhooks" class="btn btn-default">
                                                    <i class="fas fa-times"></i> Limpar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Lista de Webhooks -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Webhooks Recebidos</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Gateway</th>
                                            <th>Tipo de Evento</th>
                                            <th>Transação</th>
                                            <th>Status</th>
                                            <th>Data</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($webhooks)): ?>
                                            <?php foreach ($webhooks as $webhook): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($webhook['id']) ?></td>
                                                    <td><?= htmlspecialchars(ucfirst($webhook['gateway'])) ?></td>
                                                    <td><?= htmlspecialchars($webhook['event_type']) ?></td>
                                                    <td>
                                                        <?php if (!empty($webhook['transaction_id'])): ?>
                                                            <?= htmlspecialchars($webhook['transaction_id']) ?>
                                                            <button type="button" class="btn btn-xs btn-default copy-btn" data-clipboard-text="<?= htmlspecialchars($webhook['transaction_id']) ?>">
                                                                <i class="fas fa-copy"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?= $webhook['success'] ? 'success' : 'danger' ?>">
                                                            <?= $webhook['success'] ? 'Sucesso' : 'Erro' ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('d/m/Y H:i:s', strtotime($webhook['created_at'])) ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-info view-webhook-data" data-toggle="modal" data-target="#webhookDataModal" data-webhook-id="<?= $webhook['id'] ?>">
                                                            <i class="fas fa-eye"></i> Visualizar
                                                        </button>
                                                        
                                                        <?php if (!empty($webhook['transaction_id'])): ?>
                                                            <a href="<?= BASE_URL ?>admin/pagamentos/buscar-transacao?transaction_id=<?= urlencode($webhook['transaction_id']) ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-link"></i> Ver Transação
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Nenhum webhook encontrado</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer clearfix">
                            <?php if ($totalPages > 1): ?>
                                <ul class="pagination pagination-sm m-0 float-right">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= BASE_URL ?>admin/pagamentos/webhooks?page=<?= $i ?>
                                                <?= !empty($gateway) ? '&gateway=' . urlencode($gateway) : '' ?>
                                                <?= !empty($eventType) ? '&event_type=' . urlencode($eventType) : '' ?>
                                                <?= $status !== null && $status !== '' ? '&status=' . urlencode($status) : '' ?>
                                                <?= !empty($startDate) ? '&start_date=' . urlencode($startDate) : '' ?>
                                                <?= !empty($endDate) ? '&end_date=' . urlencode($endDate) : '' ?>
                                            ">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Estatísticas de Webhooks -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Estatísticas de Webhooks</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="info-box bg-info">
                                        <span class="info-box-icon"><i class="fas fa-bell"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total de Webhooks</span>
                                            <span class="info-box-number"><?= $totalWebhooks ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="info-box bg-success">
                                        <span class="info-box-icon"><i class="fas fa-check"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Webhooks com Sucesso</span>
                                            <span class="info-box-number">
                                                <?php
                                                // Calcular quantidade de webhooks com sucesso
                                                $successCount = 0;
                                                foreach ($webhooks as $webhook) {
                                                    if ($webhook['success']) {
                                                        $successCount++;
                                                    }
                                                }
                                                
                                                echo $successCount;
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="info-box bg-danger">
                                        <span class="info-box-icon"><i class="fas fa-times"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Webhooks com Erro</span>
                                            <span class="info-box-number">
                                                <?php
                                                // Calcular quantidade de webhooks com erro
                                                $errorCount = 0;
                                                foreach ($webhooks as $webhook) {
                                                    if (!$webhook['success']) {
                                                        $errorCount++;
                                                    }
                                                }
                                                
                                                echo $errorCount;
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="info-box bg-warning">
                                        <span class="info-box-icon"><i class="fas fa-chart-pie"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Taxa de Sucesso</span>
                                            <span class="info-box-number">
                                                <?php
                                                // Calcular taxa de sucesso
                                                $successRate = $totalWebhooks > 0 ? ($successCount / $totalWebhooks * 100) : 0;
                                                echo number_format($successRate, 1) . '%';
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">Webhooks por Gateway</h3>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="gatewayChart" height="200"></canvas>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h3 class="card-title">Webhooks por Tipo de Evento</h3>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="eventTypeChart" height="200"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Documentação de Webhooks -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card collapsed-card">
                        <div class="card-header">
                            <h3 class="card-title">Documentação de Webhooks</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card card-outline card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title">MercadoPago</h3>
                                        </div>
                                        <div class="card-body">
                                            <p>O MercadoPago envia os seguintes tipos de webhook:</p>
                                            <ul>
                                                <li><code>payment.created</code> - Quando um pagamento é criado</li>
                                                <li><code>payment.updated</code> - Quando um pagamento é atualizado</li>
                                                <li><code>payment.approved</code> - Quando um pagamento é aprovado</li>
                                                <li><code>payment.rejected</code> - Quando um pagamento é rejeitado</li>
                                                <li><code>payment.refunded</code> - Quando um pagamento é reembolsado</li>
                                                <li><code>payment.cancelled</code> - Quando um pagamento é cancelado</li>
                                            </ul>
                                            
                                            <p>URL de Webhook:</p>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="mp-webhook-url" value="<?= BASE_URL ?>webhook/mercadopago" readonly>
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard-target="#mp-webhook-url">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <p class="mt-3">Para mais informações, consulte a <a href="https://www.mercadopago.com.br/developers/pt/docs/notifications/ipn" target="_blank">documentação oficial</a>.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card card-outline card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title">PayPal</h3>
                                        </div>
                                        <div class="card-body">
                                            <p>O PayPal envia os seguintes tipos de notificação (via IPN e Webhook):</p>
                                            <ul>
                                                <li><code>PAYMENT.AUTHORIZATION.CREATED</code> - Quando uma autorização é criada</li>
                                                <li><code>PAYMENT.AUTHORIZATION.VOIDED</code> - Quando uma autorização é anulada</li>
                                                <li><code>PAYMENT.CAPTURE.COMPLETED</code> - Quando uma captura é concluída</li>
                                                <li><code>PAYMENT.CAPTURE.DENIED</code> - Quando uma captura é negada</li>
                                                <li><code>PAYMENT.CAPTURE.REFUNDED</code> - Quando uma captura é reembolsada</li>
                                                <li><code>IPN message</code> - Notificações no formato legado (IPN)</li>
                                            </ul>
                                            
                                            <p>URLs de Notificação:</p>
                                            <p>Webhook (API v2):</p>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="pp-webhook-url" value="<?= BASE_URL ?>webhook/paypal" readonly>
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard-target="#pp-webhook-url">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <p class="mt-2">IPN (legado):</p>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="pp-ipn-url" value="<?= BASE_URL ?>payment/ipn/paypal" readonly>
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard-target="#pp-ipn-url">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <p class="mt-3">Para mais informações, consulte a <a href="https://developer.paypal.com/docs/api-basics/notifications/webhooks/" target="_blank">documentação oficial</a>.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="card card-outline card-info">
                                        <div class="card-header">
                                            <h3 class="card-title">Processamento de Webhooks</h3>
                                        </div>
                                        <div class="card-body">
                                            <p>O sistema processa os webhooks de acordo com o seguinte fluxo:</p>
                                            <ol>
                                                <li>Recebimento da notificação webhook em uma URL específica para cada gateway</li>
                                                <li>Verificação de autenticidade da notificação (assinatura, origem, etc.)</li>
                                                <li>Registro da notificação no banco de dados (tabela <code>payment_webhooks</code>)</li>
                                                <li>Processamento da notificação de acordo com o tipo de evento:</li>
                                                <ul>
                                                    <li>Localização da transação associada</li>
                                                    <li>Atualização do status da transação</li>
                                                    <li>Atualização do status do pedido associado</li>
                                                    <li>Registro de histórico de transações e pedidos</li>
                                                </ul>
                                                <li>Resposta ao gateway (HTTP 200) para evitar reenvios</li>
                                            </ol>
                                            
                                            <p>Em caso de erro no processamento:</p>
                                            <ul>
                                                <li>O erro é registrado no log do sistema</li>
                                                <li>O webhook é marcado como 'erro' no banco de dados</li>
                                                <li>O sistema ainda retorna HTTP 200 para o gateway para evitar reenvios automáticos</li>
                                                <li>O suporte pode verificar os erros nesta interface e tomar ações manuais se necessário</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Dados do Webhook -->
<div class="modal fade" id="webhookDataModal" tabindex="-1" role="dialog" aria-labelledby="webhookDataModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="webhookDataModalLabel">Dados do Webhook</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs" id="webhook-data-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="request-data-tab" data-toggle="pill" href="#request-data" role="tab" aria-controls="request-data" aria-selected="true">Dados Recebidos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="process-result-tab" data-toggle="pill" href="#process-result" role="tab" aria-controls="process-result" aria-selected="false">Resultado</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="webhook-data-content">
                        <div class="tab-pane fade show active" id="request-data" role="tabpanel" aria-labelledby="request-data-tab">
                            <pre><code id="webhook-request-data">Carregando...</code></pre>
                        </div>
                        <div class="tab-pane fade" id="process-result" role="tabpanel" aria-labelledby="process-result-tab">
                            <pre><code id="webhook-process-result">Carregando...</code></pre>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos para esta página -->
<script src="https://cdn.jsdelivr.net/npm/clipboard@2.0.8/dist/clipboard.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar Clipboard.js
    var clipboard = new ClipboardJS('.copy-btn');
    
    clipboard.on('success', function(e) {
        var btn = e.trigger;
        var originalText = btn.innerHTML;
        
        // Alterar texto do botão temporariamente
        btn.innerHTML = '<i class="fas fa-check"></i>';
        
        // Restaurar texto original
        setTimeout(function() {
            btn.innerHTML = originalText;
        }, 2000);
        
        e.clearSelection();
    });
    
    // Visualizar dados do webhook
    document.querySelectorAll('.view-webhook-data').forEach(function(button) {
        button.addEventListener('click', function() {
            var webhookId = this.getAttribute('data-webhook-id');
            
            // Limpar conteúdo anterior
            document.getElementById('webhook-request-data').textContent = 'Carregando...';
            document.getElementById('webhook-process-result').textContent = 'Carregando...';
            
            // Buscar dados do webhook
            fetch('<?= BASE_URL ?>admin/pagamentos/getWebhookData', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    csrf_token: '<?= htmlspecialchars($csrf_token) ?>',
                    webhook_id: webhookId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    try {
                        // Formatar JSON para exibição
                        var requestData = JSON.stringify(JSON.parse(data.webhook.request_data), null, 2);
                        var processResult = JSON.stringify(JSON.parse(data.webhook.process_result), null, 2);
                        
                        document.getElementById('webhook-request-data').textContent = requestData;
                        document.getElementById('webhook-process-result').textContent = processResult;
                    } catch (e) {
                        // Fallback para dados não-JSON
                        document.getElementById('webhook-request-data').textContent = data.webhook.request_data;
                        document.getElementById('webhook-process-result').textContent = data.webhook.process_result;
                    }
                } else {
                    document.getElementById('webhook-request-data').textContent = 'Erro ao carregar dados: ' + data.message;
                    document.getElementById('webhook-process-result').textContent = 'Erro ao carregar dados: ' + data.message;
                }
            })
            .catch(error => {
                document.getElementById('webhook-request-data').textContent = 'Erro ao carregar dados: ' + error.message;
                document.getElementById('webhook-process-result').textContent = 'Erro ao carregar dados: ' + error.message;
            });
        });
    });
    
    // Gráficos
    if (document.getElementById('gatewayChart')) {
        // Dados para o gráfico de gateways
        var gatewayData = {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de',
                    '#6f42c1', '#fd7e14', '#20c997', '#6c757d'
                ]
            }]
        };
        
        // Preparar dados do gráfico
        <?php
        // Calcular contagem por gateway
        $gatewayCount = [];
        foreach ($webhooks as $webhook) {
            $gateway = $webhook['gateway'];
            if (!isset($gatewayCount[$gateway])) {
                $gatewayCount[$gateway] = 0;
            }
            $gatewayCount[$gateway]++;
        }
        ?>
        
        <?php foreach ($gatewayCount as $gateway => $count): ?>
            gatewayData.labels.push('<?= ucfirst(htmlspecialchars($gateway)) ?>');
            gatewayData.datasets[0].data.push(<?= $count ?>);
        <?php endforeach; ?>
        
        // Criar gráfico de gateway
        var gatewayChartCtx = document.getElementById('gatewayChart').getContext('2d');
        var gatewayChart = new Chart(gatewayChartCtx, {
            type: 'doughnut',
            data: gatewayData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'right'
                }
            }
        });
    }
    
    if (document.getElementById('eventTypeChart')) {
        // Dados para o gráfico de tipos de evento
        var eventTypeData = {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de',
                    '#6f42c1', '#fd7e14', '#20c997', '#6c757d'
                ]
            }]
        };
        
        // Preparar dados do gráfico
        <?php
        // Calcular contagem por tipo de evento
        $eventTypeCount = [];
        foreach ($webhooks as $webhook) {
            $eventType = $webhook['event_type'];
            if (!isset($eventTypeCount[$eventType])) {
                $eventTypeCount[$eventType] = 0;
            }
            $eventTypeCount[$eventType]++;
        }
        ?>
        
        <?php foreach ($eventTypeCount as $eventType => $count): ?>
            eventTypeData.labels.push('<?= htmlspecialchars($eventType) ?>');
            eventTypeData.datasets[0].data.push(<?= $count ?>);
        <?php endforeach; ?>
        
        // Criar gráfico de tipo de evento
        var eventTypeChartCtx = document.getElementById('eventTypeChart').getContext('2d');
        var eventTypeChart = new Chart(eventTypeChartCtx, {
            type: 'doughnut',
            data: eventTypeData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'right'
                }
            }
        });
    }
});
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
