<?php
/**
 * View para detalhes de uma transação de pagamento
 * 
 * Exibe informações detalhadas sobre uma transação, incluindo status,
 * histórico, webhooks e opções de gerenciamento
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
                        <li class="breadcrumb-item active">Detalhes da Transação</li>
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
                        <a href="<?= BASE_URL ?>admin/pedidos/detalhes/<?= htmlspecialchars($transaction['order_id']) ?>" class="btn btn-info">
                            <i class="fas fa-shopping-cart"></i> Ver Pedido
                        </a>
                        
                        <?php if (in_array(strtolower($transaction['status']), ['approved', 'authorized', 'pending'])): ?>
                            <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#cancelModal">
                                <i class="fas fa-ban"></i> Cancelar Transação
                            </button>
                        <?php endif; ?>
                        
                        <?php if (in_array(strtolower($transaction['status']), ['approved', 'authorized'])): ?>
                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#refundModal">
                                <i class="fas fa-undo"></i> Reembolsar
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Visão Geral da Transação -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Detalhes da Transação</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">ID da Transação</span>
                                            <span class="info-box-number text-muted">
                                                <?= htmlspecialchars($transaction['transaction_id']) ?>
                                                <button type="button" class="btn btn-xs btn-default copy-btn" data-clipboard-text="<?= htmlspecialchars($transaction['transaction_id']) ?>">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Valor</span>
                                            <span class="info-box-number text-muted">
                                                R$ <?= number_format($transaction['amount'], 2, ',', '.') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Número do Pedido</span>
                                            <span class="info-box-number text-muted">
                                                <a href="<?= BASE_URL ?>admin/pedidos/detalhes/<?= htmlspecialchars($transaction['order_id']) ?>">
                                                    <?= htmlspecialchars($order['order_number']) ?>
                                                </a>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Gateway</span>
                                            <span class="info-box-number text-muted">
                                                <?= htmlspecialchars(ucfirst($transaction['gateway_name'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Método de Pagamento</span>
                                            <span class="info-box-number text-muted">
                                                <?php
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
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Moeda</span>
                                            <span class="info-box-number text-muted">
                                                <?= htmlspecialchars(strtoupper($transaction['currency'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Data de Criação</span>
                                            <span class="info-box-number text-muted">
                                                <?= date('d/m/Y H:i:s', strtotime($transaction['created_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="info-box bg-light">
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted">Última Atualização</span>
                                            <span class="info-box-number text-muted">
                                                <?= date('d/m/Y H:i:s', strtotime($transaction['updated_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="info-box">
                                        <div class="info-box-content">
                                            <span class="info-box-text">Status da Transação</span>
                                            <span class="info-box-number">
                                                <?php
                                                $statusLabel = ucfirst($transaction['status']);
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
                                                <span class="badge badge-<?= $statusClass ?> badge-lg">
                                                    <?= htmlspecialchars($statusLabel) ?>
                                                </span>
                                                
                                                <?php if (strtolower($transaction['status']) === 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-warning ml-2" id="btnCheckStatus">
                                                        <i class="fas fa-sync"></i> Verificar Status
                                                    </button>
                                                <?php endif; ?>
                                            </span>
                                            
                                            <?php if ($gatewayStatus): ?>
                                                <div class="mt-2">
                                                    <small>Status no Gateway: <strong><?= htmlspecialchars($gatewayStatus['status'] ?? 'N/A') ?></strong></small>
                                                    
                                                    <?php if (($gatewayStatus['status'] ?? '') !== $transaction['status']): ?>
                                                        <div class="alert alert-warning mt-2">
                                                            <i class="icon fas fa-exclamation-triangle"></i>
                                                            O status no gateway é diferente do status no sistema. Considere atualizar o status.
                                                            
                                                            <button type="button" class="btn btn-xs btn-success ml-2" id="btnUpdateStatus" data-status="<?= htmlspecialchars($gatewayStatus['status']) ?>">
                                                                Atualizar Status
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Informações do Cliente e Pedido</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Cliente</h5>
                                    <p>
                                        <strong>Nome:</strong> <?= htmlspecialchars($customer['name'] ?? 'N/A') ?><br>
                                        <strong>Email:</strong> <?= htmlspecialchars($customer['email'] ?? 'N/A') ?><br>
                                        <strong>Telefone:</strong> <?= htmlspecialchars($customer['phone'] ?? 'N/A') ?><br>
                                        <strong>Documento:</strong> <?= htmlspecialchars($customer['document_number'] ?? 'N/A') ?>
                                    </p>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>Pedido</h5>
                                    <p>
                                        <strong>Número:</strong> <?= htmlspecialchars($order['order_number'] ?? 'N/A') ?><br>
                                        <strong>Status:</strong> <?= htmlspecialchars(ucfirst($order['status'] ?? 'N/A')) ?><br>
                                        <strong>Data:</strong> <?= isset($order['created_at']) ? date('d/m/Y H:i', strtotime($order['created_at'])) : 'N/A' ?><br>
                                        <strong>Total:</strong> R$ <?= isset($order['total']) ? number_format($order['total'], 2, ',', '.') : 'N/A' ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h5>Itens do Pedido</h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Quantidade</th>
                                                    <th class="text-right">Preço</th>
                                                    <th class="text-right">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($orderItems)): ?>
                                                    <?php foreach ($orderItems as $item): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($item['name']) ?></td>
                                                            <td><?= $item['quantity'] ?></td>
                                                            <td class="text-right">R$ <?= number_format($item['price'], 2, ',', '.') ?></td>
                                                            <td class="text-right">R$ <?= number_format($item['price'] * $item['quantity'], 2, ',', '.') ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">Nenhum item encontrado</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                            <?php if (!empty($orderItems)): ?>
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="3" class="text-right">Subtotal:</th>
                                                        <th class="text-right">R$ <?= number_format($order['subtotal'] ?? 0, 2, ',', '.') ?></th>
                                                    </tr>
                                                    <?php if (isset($order['discount']) && $order['discount'] > 0): ?>
                                                        <tr>
                                                            <th colspan="3" class="text-right">Desconto:</th>
                                                            <th class="text-right">-R$ <?= number_format($order['discount'], 2, ',', '.') ?></th>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <tr>
                                                        <th colspan="3" class="text-right">Frete:</th>
                                                        <th class="text-right">R$ <?= number_format($order['shipping_cost'] ?? 0, 2, ',', '.') ?></th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="3" class="text-right">Total:</th>
                                                        <th class="text-right">R$ <?= number_format($order['total'] ?? 0, 2, ',', '.') ?></th>
                                                    </tr>
                                                </tfoot>
                                            <?php endif; ?>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Reembolsos -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Reembolsos</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID do Reembolso</th>
                                            <th>Data</th>
                                            <th>Valor</th>
                                            <th>Status</th>
                                            <th>Motivo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($refunds)): ?>
                                            <?php foreach ($refunds as $refund): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($refund['refund_id']) ?></td>
                                                    <td><?= date('d/m/Y H:i:s', strtotime($refund['created_at'])) ?></td>
                                                    <td>
                                                        <?php if ($refund['amount'] > 0): ?>
                                                            R$ <?= number_format($refund['amount'], 2, ',', '.') ?>
                                                            <small class="text-muted">(Parcial)</small>
                                                        <?php else: ?>
                                                            Total
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?= $refund['status'] === 'completed' ? 'success' : 'warning' ?>">
                                                            <?= htmlspecialchars(ucfirst($refund['status'])) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($refund['reason'] ?? 'N/A') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Nenhum reembolso registrado</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Histórico e Webhooks -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Histórico da Transação</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Status</th>
                                            <th>Detalhes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($transactionHistory)): ?>
                                            <?php foreach ($transactionHistory as $history): ?>
                                                <tr>
                                                    <td><?= date('d/m/Y H:i:s', strtotime($history['created_at'])) ?></td>
                                                    <td>
                                                        <?php
                                                        $statusClass = 'secondary';
                                                        switch (strtolower($history['status'])) {
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
                                                            <?= htmlspecialchars(ucfirst($history['status'])) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        // Extrair detalhes do additional_data (JSON)
                                                        $details = [];
                                                        $additionalData = json_decode($history['additional_data'] ?? '{}', true);
                                                        
                                                        if (!empty($additionalData)) {
                                                            // Filtrar informações sensíveis
                                                            unset($additionalData['card_token']);
                                                            unset($additionalData['token']);
                                                            unset($additionalData['access_token']);
                                                            
                                                            foreach ($additionalData as $key => $value) {
                                                                if (is_scalar($value)) {
                                                                    $details[] = "<strong>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . "</strong>: " . htmlspecialchars($value);
                                                                }
                                                            }
                                                        }
                                                        
                                                        if (!empty($details)) {
                                                            echo implode('<br>', $details);
                                                        } else {
                                                            echo '<span class="text-muted">Sem detalhes</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">Nenhum histórico disponível</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Webhooks Relacionados</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Evento</th>
                                            <th>Status</th>
                                            <th>Detalhes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($relatedWebhooks)): ?>
                                            <?php foreach ($relatedWebhooks as $webhook): ?>
                                                <tr>
                                                    <td><?= date('d/m/Y H:i:s', strtotime($webhook['created_at'])) ?></td>
                                                    <td><?= htmlspecialchars($webhook['event_type']) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $webhook['success'] ? 'success' : 'danger' ?>">
                                                            <?= $webhook['success'] ? 'Processado' : 'Falha' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-xs btn-info view-webhook-data" data-toggle="modal" data-target="#webhookDataModal" data-webhook-id="<?= $webhook['id'] ?>">
                                                            <i class="fas fa-eye"></i> Visualizar
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Nenhum webhook recebido</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dados Técnicos -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card collapsed-card">
                        <div class="card-header">
                            <h3 class="card-title">Dados Técnicos</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="icon fas fa-info-circle"></i>
                                Esta seção contém dados técnicos da transação, úteis para depuração. Dados sensíveis foram removidos.
                            </div>
                            
                            <div class="form-group">
                                <label>Dados Adicionais</label>
                                <textarea class="form-control" rows="10" readonly><?php
                                    $additionalData = json_decode($transaction['additional_data'] ?? '{}', true);
                                    
                                    // Remover dados sensíveis
                                    if (is_array($additionalData)) {
                                        unset($additionalData['card_token']);
                                        unset($additionalData['token']);
                                        unset($additionalData['access_token']);
                                    }
                                    
                                    echo json_encode($additionalData, JSON_PRETTY_PRINT);
                                ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Reembolso -->
<div class="modal fade" id="refundModal" tabindex="-1" role="dialog" aria-labelledby="refundModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>admin/pagamentos/reembolsar/<?= htmlspecialchars($transaction['id']) ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="refundModalLabel">Reembolsar Transação</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="icon fas fa-exclamation-triangle"></i>
                        Atenção! O reembolso não pode ser desfeito. Confirme os detalhes antes de prosseguir.
                    </div>
                    
                    <div class="form-group">
                        <label>Tipo de Reembolso</label>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="refund-type-total" name="refund_type" value="total" class="custom-control-input" checked>
                            <label class="custom-control-label" for="refund-type-total">Reembolso Total</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="refund-type-partial" name="refund_type" value="partial" class="custom-control-input">
                            <label class="custom-control-label" for="refund-type-partial">Reembolso Parcial</label>
                        </div>
                    </div>
                    
                    <div class="form-group" id="partial-refund-amount-group" style="display: none;">
                        <label for="refund-amount">Valor a Reembolsar</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">R$</span>
                            </div>
                            <input type="number" class="form-control" id="refund-amount" name="refund_amount" step="0.01" min="0.01" max="<?= $transaction['amount'] ?>">
                        </div>
                        <small class="form-text text-muted">
                            O valor máximo para reembolso é R$ <?= number_format($transaction['amount'], 2, ',', '.') ?>
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="refund-reason">Motivo do Reembolso</label>
                        <textarea class="form-control" id="refund-reason" name="refund_reason" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Reembolsar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Cancelamento -->
<div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>admin/pagamentos/cancelar/<?= htmlspecialchars($transaction['id']) ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">Cancelar Transação</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="icon fas fa-exclamation-triangle"></i>
                        Atenção! O cancelamento não pode ser desfeito. Confirme os detalhes antes de prosseguir.
                    </div>
                    
                    <div class="form-group">
                        <label for="cancel-reason">Motivo do Cancelamento</label>
                        <textarea class="form-control" id="cancel-reason" name="cancel_reason" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-warning">Cancelar Transação</button>
                </div>
            </form>
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
    
    // Mostrar/ocultar campo de valor para reembolso parcial
    document.querySelectorAll('input[name="refund_type"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var amountGroup = document.getElementById('partial-refund-amount-group');
            amountGroup.style.display = (this.value === 'partial') ? 'block' : 'none';
        });
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
    
    // Verificar status da transação
    document.getElementById('btnCheckStatus')?.addEventListener('click', function() {
        var btn = this;
        var originalText = btn.innerHTML;
        
        // Alterar texto do botão temporariamente
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
        btn.disabled = true;
        
        // Fazer a requisição para verificar status
        fetch('<?= BASE_URL ?>admin/pagamentos/checkTransactionStatus', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                csrf_token: '<?= htmlspecialchars($csrf_token) ?>',
                transaction_id: '<?= htmlspecialchars($transaction['transaction_id']) ?>',
                gateway: '<?= htmlspecialchars($transaction['gateway_name']) ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Status atualizado: ' + data.status);
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
    
    // Atualizar status da transação
    document.getElementById('btnUpdateStatus')?.addEventListener('click', function() {
        var status = this.getAttribute('data-status');
        
        if (confirm('Tem certeza que deseja atualizar o status da transação para "' + status + '"?')) {
            // Fazer a requisição para atualizar status
            fetch('<?= BASE_URL ?>admin/pagamentos/updateTransactionStatus', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    csrf_token: '<?= htmlspecialchars($csrf_token) ?>',
                    transaction_id: '<?= htmlspecialchars($transaction['id']) ?>',
                    status: status
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
