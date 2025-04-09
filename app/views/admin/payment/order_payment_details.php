<?php
/**
 * View para detalhes de pagamento de um pedido específico
 * 
 * Exibe histórico de transações, tentativas de pagamento e status
 * de um pedido específico
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
                        <li class="breadcrumb-item active">Detalhes de Pagamento</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-check"></i> Sucesso!</h5>
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Erro!</h5>
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="btn-group">
                        <a href="<?= BASE_URL ?>admin/pagamentos" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar para Pagamentos
                        </a>
                        <a href="<?= BASE_URL ?>admin/pedidos/detalhes/<?= htmlspecialchars($order['id']) ?>" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i> Ver Detalhes do Pedido
                        </a>
                        <a href="<?= BASE_URL ?>admin/pedidos/editar/<?= htmlspecialchars($order['id']) ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar Pedido
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Resumo do Pedido -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Resumo do Pedido #<?= htmlspecialchars($order['order_number']) ?></h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Status do Pedido</span>
                                            <span class="info-box-number text-muted">
                                                <?php
                                                $statusClass = 'secondary';
                                                switch (strtolower($order['status'])) {
                                                    case 'processing':
                                                    case 'approved':
                                                        $statusClass = 'success';
                                                        break;
                                                    case 'pending':
                                                    case 'in_process':
                                                        $statusClass = 'warning';
                                                        break;
                                                    case 'cancelled':
                                                    case 'failed':
                                                        $statusClass = 'danger';
                                                        break;
                                                    case 'refunded':
                                                        $statusClass = 'info';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge badge-<?= $statusClass ?>">
                                                    <?= htmlspecialchars(ucfirst($order['status'])) ?>
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Status do Pagamento</span>
                                            <span class="info-box-number text-muted">
                                                <?php
                                                $paymentStatusClass = 'secondary';
                                                switch (strtolower($order['payment_status'])) {
                                                    case 'approved':
                                                    case 'authorized':
                                                        $paymentStatusClass = 'success';
                                                        break;
                                                    case 'pending':
                                                    case 'in_process':
                                                        $paymentStatusClass = 'warning';
                                                        break;
                                                    case 'cancelled':
                                                    case 'failed':
                                                    case 'rejected':
                                                        $paymentStatusClass = 'danger';
                                                        break;
                                                    case 'refunded':
                                                        $paymentStatusClass = 'info';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge badge-<?= $paymentStatusClass ?>">
                                                    <?= htmlspecialchars(ucfirst($order['payment_status'])) ?>
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Valor Total</span>
                                            <span class="info-box-number text-muted">
                                                R$ <?= number_format($order['total'], 2, ',', '.') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Data do Pedido</span>
                                            <span class="info-box-number text-muted">
                                                <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h5>Cliente</h5>
                                    <p>
                                        <strong>Nome:</strong> <?= htmlspecialchars($customer['name'] ?? 'N/A') ?><br>
                                        <strong>Email:</strong> <?= htmlspecialchars($customer['email'] ?? 'N/A') ?><br>
                                        <strong>Telefone:</strong> <?= htmlspecialchars($customer['phone'] ?? 'N/A') ?><br>
                                        <strong>Documento:</strong> 
                                        <?= htmlspecialchars(($customer['document_type'] ?? '') . ' ' . ($customer['document_number'] ?? 'N/A')) ?>
                                    </p>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>Gateway Atual</h5>
                                    <?php if (!empty($order['payment_gateway'])): ?>
                                        <p>
                                            <strong>Gateway:</strong> <?= htmlspecialchars(ucfirst($order['payment_gateway'])) ?><br>
                                            <strong>Método:</strong> 
                                            <?php
                                            // Mapeamento de métodos de pagamento
                                            $methodDisplay = '';
                                            switch ($order['payment_method'] ?? '') {
                                                case 'credit_card':
                                                    $methodDisplay = 'Cartão de Crédito';
                                                    break;
                                                case 'boleto':
                                                    $methodDisplay = 'Boleto';
                                                    break;
                                                case 'pix':
                                                    $methodDisplay = 'PIX';
                                                    break;
                                                case 'paypal':
                                                    $methodDisplay = 'PayPal';
                                                    break;
                                                default:
                                                    $methodDisplay = ucfirst($order['payment_method'] ?? 'N/A');
                                            }
                                            ?>
                                            <?= htmlspecialchars($methodDisplay) ?><br>
                                            <strong>Transação:</strong> 
                                            <?php if (!empty($order['payment_transaction_id'])): ?>
                                                <?= htmlspecialchars($order['payment_transaction_id']) ?>
                                                <button type="button" class="btn btn-xs btn-default copy-btn" data-clipboard-text="<?= htmlspecialchars($order['payment_transaction_id']) ?>">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-muted">Nenhum gateway configurado para este pedido.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Transações de Pagamento -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Transações de Pagamento</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Gateway</th>
                                            <th>ID da Transação</th>
                                            <th>Método</th>
                                            <th>Valor</th>
                                            <th>Status</th>
                                            <th>Data</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($transactions)): ?>
                                            <?php foreach ($transactions as $transaction): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($transaction['id']) ?></td>
                                                    <td><?= htmlspecialchars(ucfirst($transaction['gateway_name'])) ?></td>
                                                    <td>
                                                        <?= htmlspecialchars($transaction['transaction_id']) ?>
                                                        <button type="button" class="btn btn-xs btn-default copy-btn" data-clipboard-text="<?= htmlspecialchars($transaction['transaction_id']) ?>">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        // Mapear métodos de pagamento
                                                        $methodDisplay = '';
                                                        switch ($transaction['payment_method']) {
                                                            case 'credit_card':
                                                                $methodDisplay = 'Cartão de Crédito';
                                                                break;
                                                            case 'boleto':
                                                                $methodDisplay = 'Boleto';
                                                                break;
                                                            case 'pix':
                                                                $methodDisplay = 'PIX';
                                                                break;
                                                            case 'paypal':
                                                                $methodDisplay = 'PayPal';
                                                                break;
                                                            default:
                                                                $methodDisplay = ucfirst($transaction['payment_method']);
                                                        }
                                                        ?>
                                                        <?= htmlspecialchars($methodDisplay) ?>
                                                    </td>
                                                    <td>R$ <?= number_format($transaction['amount'], 2, ',', '.') ?></td>
                                                    <td>
                                                        <?php
                                                        $statusClass = 'secondary';
                                                        switch (strtolower($transaction['status'])) {
                                                            case 'approved':
                                                            case 'authorized':
                                                                $statusClass = 'success';
                                                                break;
                                                            case 'pending':
                                                            case 'in_process':
                                                                $statusClass = 'warning';
                                                                break;
                                                            case 'rejected':
                                                            case 'failed':
                                                            case 'cancelled':
                                                                $statusClass = 'danger';
                                                                break;
                                                            case 'refunded':
                                                                $statusClass = 'info';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge badge-<?= $statusClass ?>">
                                                            <?= htmlspecialchars(ucfirst($transaction['status'])) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('d/m/Y H:i', strtotime($transaction['created_at'])) ?></td>
                                                    <td>
                                                        <a href="<?= BASE_URL ?>admin/pagamentos/transacao/<?= htmlspecialchars($transaction['id']) ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i> Detalhes
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">Nenhuma transação encontrada</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tentativas de Pagamento -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Tentativas de Pagamento</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Gateway</th>
                                            <th>Método</th>
                                            <th>Status</th>
                                            <th>Sucesso</th>
                                            <th>Data</th>
                                            <th>Detalhes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($attempts)): ?>
                                            <?php foreach ($attempts as $attempt): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($attempt['id']) ?></td>
                                                    <td><?= htmlspecialchars(ucfirst($attempt['gateway'])) ?></td>
                                                    <td>
                                                        <?php
                                                        // Mapear métodos de pagamento
                                                        $methodDisplay = '';
                                                        switch ($attempt['payment_method']) {
                                                            case 'credit_card':
                                                                $methodDisplay = 'Cartão de Crédito';
                                                                break;
                                                            case 'boleto':
                                                                $methodDisplay = 'Boleto';
                                                                break;
                                                            case 'pix':
                                                                $methodDisplay = 'PIX';
                                                                break;
                                                            case 'paypal':
                                                                $methodDisplay = 'PayPal';
                                                                break;
                                                            default:
                                                                $methodDisplay = ucfirst($attempt['payment_method']);
                                                        }
                                                        ?>
                                                        <?= htmlspecialchars($methodDisplay) ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $statusClass = 'secondary';
                                                        switch (strtolower($attempt['status'])) {
                                                            case 'approved':
                                                            case 'authorized':
                                                                $statusClass = 'success';
                                                                break;
                                                            case 'pending':
                                                            case 'in_process':
                                                                $statusClass = 'warning';
                                                                break;
                                                            case 'rejected':
                                                            case 'failed':
                                                            case 'cancelled':
                                                            case 'error':
                                                                $statusClass = 'danger';
                                                                break;
                                                            case 'refunded':
                                                                $statusClass = 'info';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge badge-<?= $statusClass ?>">
                                                            <?= htmlspecialchars(ucfirst($attempt['status'])) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($attempt['success']): ?>
                                                            <span class="badge badge-success">Sim</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">Não</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= date('d/m/Y H:i:s', strtotime($attempt['created_at'])) ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-info view-attempt-data" data-toggle="modal" data-target="#attemptDataModal" data-attempt-id="<?= $attempt['id'] ?>">
                                                            <i class="fas fa-eye"></i> Detalhes
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Nenhuma tentativa de pagamento registrada</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Histórico de Status -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Histórico de Status</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Status do Pedido</th>
                                            <th>Status do Pagamento</th>
                                            <th>Notas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($statusHistory)): ?>
                                            <?php foreach ($statusHistory as $history): ?>
                                                <tr>
                                                    <td><?= date('d/m/Y H:i:s', strtotime($history['created_at'])) ?></td>
                                                    <td>
                                                        <?php
                                                        $statusClass = 'secondary';
                                                        switch (strtolower($history['status'])) {
                                                            case 'processing':
                                                            case 'approved':
                                                                $statusClass = 'success';
                                                                break;
                                                            case 'pending':
                                                            case 'in_process':
                                                                $statusClass = 'warning';
                                                                break;
                                                            case 'cancelled':
                                                            case 'failed':
                                                                $statusClass = 'danger';
                                                                break;
                                                            case 'refunded':
                                                                $statusClass = 'info';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge badge-<?= $statusClass ?>">
                                                            <?= htmlspecialchars(ucfirst($history['status'])) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $paymentStatusClass = 'secondary';
                                                        switch (strtolower($history['payment_status'])) {
                                                            case 'approved':
                                                            case 'authorized':
                                                                $paymentStatusClass = 'success';
                                                                break;
                                                            case 'pending':
                                                            case 'in_process':
                                                                $paymentStatusClass = 'warning';
                                                                break;
                                                            case 'cancelled':
                                                            case 'failed':
                                                            case 'rejected':
                                                                $paymentStatusClass = 'danger';
                                                                break;
                                                            case 'refunded':
                                                                $paymentStatusClass = 'info';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge badge-<?= $paymentStatusClass ?>">
                                                            <?= htmlspecialchars(ucfirst($history['payment_status'])) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($history['notes'] ?? 'N/A') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Nenhum histórico de status encontrado</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detalhes de Pagamento -->
            <?php if (!empty($order['payment_details'])): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card collapsed-card">
                            <div class="card-header">
                                <h3 class="card-title">Detalhes de Pagamento (Dados Técnicos)</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="icon fas fa-info-circle"></i>
                                    Esta seção contém dados técnicos do pagamento, úteis para depuração. Dados sensíveis foram removidos.
                                </div>
                                
                                <div class="form-group">
                                    <label>Dados Adicionais</label>
                                    <textarea class="form-control" rows="10" readonly><?php
                                        $paymentDetails = json_decode($order['payment_details'] ?? '{}', true);
                                        
                                        // Remover dados sensíveis
                                        if (is_array($paymentDetails)) {
                                            unset($paymentDetails['card_token']);
                                            unset($paymentDetails['token']);
                                            unset($paymentDetails['access_token']);
                                        }
                                        
                                        echo json_encode($paymentDetails, JSON_PRETTY_PRINT);
                                    ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Ações de Gerenciamento -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Ações de Gerenciamento</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Verificar Status no Gateway</label>
                                        <?php if (!empty($order['payment_transaction_id']) && !empty($order['payment_gateway'])): ?>
                                            <div>
                                                <button type="button" class="btn btn-info" id="btnCheckGatewayStatus">
                                                    <i class="fas fa-sync"></i> Verificar Status no Gateway
                                                </button>
                                                <p class="text-muted mt-2">
                                                    <small>Consulta o status atual da transação diretamente no gateway de pagamento.</small>
                                                </p>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">
                                                Nenhuma transação ou gateway configurado para este pedido.
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Gerenciar Status de Pagamento</label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <select class="form-control" id="paymentStatusSelect">
                                                    <option value="">Selecione um status...</option>
                                                    <option value="pending">Pendente</option>
                                                    <option value="approved">Aprovado</option>
                                                    <option value="cancelled">Cancelado</option>
                                                    <option value="refunded">Reembolsado</option>
                                                    <option value="failed">Falha</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <button type="button" class="btn btn-warning" id="btnUpdatePaymentStatus" disabled>
                                                    <i class="fas fa-save"></i> Atualizar Status
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-muted mt-2">
                                            <small>Atualiza manualmente o status de pagamento do pedido. Use com cautela!</small>
                                        </p>
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

<!-- Modal de Detalhes da Tentativa -->
<div class="modal fade" id="attemptDataModal" tabindex="-1" role="dialog" aria-labelledby="attemptDataModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attemptDataModalLabel">Detalhes da Tentativa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Dados Adicionais</label>
                    <pre><code id="attempt-data">Carregando...</code></pre>
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
    
    // Modal de detalhes da tentativa
    document.querySelectorAll('.view-attempt-data').forEach(function(button) {
        button.addEventListener('click', function() {
            var attemptId = this.getAttribute('data-attempt-id');
            
            // Limpar conteúdo anterior
            document.getElementById('attempt-data').textContent = 'Carregando...';
            
            // Buscar dados da tentativa
            fetch('<?= BASE_URL ?>admin/pagamentos/getAttemptData', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    csrf_token: '<?= htmlspecialchars($csrf_token) ?>',
                    attempt_id: attemptId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    try {
                        // Formatar JSON para exibição
                        var attemptData = JSON.stringify(JSON.parse(data.attempt.additional_data), null, 2);
                        document.getElementById('attempt-data').textContent = attemptData;
                    } catch (e) {
                        // Fallback para dados não-JSON
                        document.getElementById('attempt-data').textContent = data.attempt.additional_data;
                    }
                } else {
                    document.getElementById('attempt-data').textContent = 'Erro ao carregar dados: ' + data.message;
                }
            })
            .catch(error => {
                document.getElementById('attempt-data').textContent = 'Erro ao carregar dados: ' + error.message;
            });
        });
    });
    
    // Verificar status no gateway
    document.getElementById('btnCheckGatewayStatus')?.addEventListener('click', function() {
        var btn = this;
        var originalText = btn.innerHTML;
        
        // Alterar texto do botão temporariamente
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
        btn.disabled = true;
        
        // Fazer a requisição para verificar status
        fetch('<?= BASE_URL ?>admin/pagamentos/checkOrderTransactionStatus', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                csrf_token: '<?= htmlspecialchars($csrf_token) ?>',
                order_id: <?= $order['id'] ?>,
                transaction_id: '<?= htmlspecialchars($order['payment_transaction_id'] ?? '') ?>',
                gateway: '<?= htmlspecialchars($order['payment_gateway'] ?? '') ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Status verificado: ' + data.status + '\n\nDeseja atualizar o status do pedido?');
                location.reload();
            } else {
                alert('Erro ao verificar status: ' + data.message);
            }
        })
        .catch(error => {
            alert('Erro: ' + error.message);
        })
        .finally(() => {
            // Restaurar texto original
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
    
    // Habilitar/desabilitar botão de atualização de status
    document.getElementById('paymentStatusSelect').addEventListener('change', function() {
        var btn = document.getElementById('btnUpdatePaymentStatus');
        btn.disabled = this.value === '';
    });
    
    // Atualizar status de pagamento
    document.getElementById('btnUpdatePaymentStatus').addEventListener('click', function() {
        var status = document.getElementById('paymentStatusSelect').value;
        
        if (!status) {
            alert('Selecione um status de pagamento.');
            return;
        }
        
        if (confirm('Tem certeza que deseja atualizar o status de pagamento para "' + status + '"?\n\nEsta ação pode afetar o processamento do pedido.')) {
            // Fazer a requisição para atualizar status
            fetch('<?= BASE_URL ?>admin/pagamentos/updateOrderPaymentStatus', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    csrf_token: '<?= htmlspecialchars($csrf_token) ?>',
                    order_id: <?= $order['id'] ?>,
                    status: status,
                    notes: 'Atualização manual pelo administrador'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Status atualizado com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao atualizar status: ' + data.message);
                }
            })
            .catch(error => {
                alert('Erro: ' + error.message);
            });
        }
    });
});
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
