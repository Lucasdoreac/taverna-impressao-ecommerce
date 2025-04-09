<?php
/**
 * View para lista de transações de pagamento
 * 
 * Permite visualizar e filtrar transações de pagamento
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
                        <li class="breadcrumb-item active">Transações</li>
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
            
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Filtrar Transações</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>admin/pagamentos/transacoes" method="get" id="filter-form">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="gateway">Gateway</label>
                                    <select class="form-control" id="gateway" name="gateway">
                                        <option value="">Todos</option>
                                        <?php foreach ($availableGateways as $availableGateway): ?>
                                            <option value="<?= htmlspecialchars($availableGateway) ?>" <?= $gateway === $availableGateway ? 'selected' : '' ?>>
                                                <?= htmlspecialchars(ucfirst($availableGateway)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="method">Método de Pagamento</label>
                                    <select class="form-control" id="method" name="method">
                                        <option value="">Todos</option>
                                        <?php foreach ($availablePaymentMethods as $availableMethod): ?>
                                            <option value="<?= htmlspecialchars($availableMethod) ?>" <?= $method === $availableMethod ? 'selected' : '' ?>>
                                                <?= htmlspecialchars(ucfirst($availableMethod)) ?>
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
                                        <?php foreach ($availableStatuses as $availableStatus): ?>
                                            <option value="<?= htmlspecialchars($availableStatus) ?>" <?= $status === $availableStatus ? 'selected' : '' ?>>
                                                <?= htmlspecialchars(ucfirst($availableStatus)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="order_number">Número do Pedido</label>
                                    <input type="text" class="form-control" id="order_number" name="order_number" value="<?= htmlspecialchars($orderNumber ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
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
                            
                            <div class="col-md-6">
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                    
                                    <a href="<?= BASE_URL ?>admin/pagamentos/transacoes" class="btn btn-default">
                                        <i class="fas fa-times"></i> Limpar Filtros
                                    </a>
                                    
                                    <button type="button" id="export-excel" class="btn btn-success float-right">
                                        <i class="fas fa-file-excel"></i> Exportar para Excel
                                    </button>
                                    
                                    <button type="button" id="export-pdf" class="btn btn-danger float-right mr-2">
                                        <i class="fas fa-file-pdf"></i> Exportar para PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Lista de Transações</h3>
                    <div class="card-tools">
                        <span class="badge badge-info">Total: <?= number_format($totalTransactions, 0, ',', '.') ?></span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th>Gateway</th>
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
                                            <td>
                                                <a href="<?= BASE_URL ?>admin/pedidos/detalhes/<?= htmlspecialchars($transaction['order_id']) ?>">
                                                    <?= htmlspecialchars($transaction['order_number'] ?? 'N/A') ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($transaction['customer_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars(ucfirst($transaction['gateway_name'])) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($transaction['payment_method'])) ?></td>
                                            <td>R$ <?= number_format($transaction['amount'], 2, ',', '.') ?></td>
                                            <td>
                                                <?php
                                                $statusBadge = 'secondary';
                                                switch (strtolower($transaction['status'])) {
                                                    case 'approved':
                                                    case 'authorized':
                                                        $statusBadge = 'success';
                                                        break;
                                                    case 'pending':
                                                    case 'in_process':
                                                        $statusBadge = 'warning';
                                                        break;
                                                    case 'rejected':
                                                    case 'failed':
                                                    case 'cancelled':
                                                        $statusBadge = 'danger';
                                                        break;
                                                    case 'refunded':
                                                        $statusBadge = 'info';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge badge-<?= $statusBadge ?>">
                                                    <?= htmlspecialchars(ucfirst($transaction['status'])) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($transaction['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="<?= BASE_URL ?>admin/pagamentos/transacao/<?= htmlspecialchars($transaction['id']) ?>" class="btn btn-sm btn-info" title="Detalhes">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <?php if (in_array(strtolower($transaction['status']), ['approved', 'authorized', 'in_process', 'pending'])): ?>
                                                        <a href="<?= BASE_URL ?>admin/pagamentos/reembolsar/<?= htmlspecialchars($transaction['id']) ?>" class="btn btn-sm btn-warning" title="Reembolsar">
                                                            <i class="fas fa-undo"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (in_array(strtolower($transaction['status']), ['pending', 'in_process', 'authorized'])): ?>
                                                        <a href="<?= BASE_URL ?>admin/pagamentos/cancelar/<?= htmlspecialchars($transaction['id']) ?>" class="btn btn-sm btn-danger" title="Cancelar">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="<?= BASE_URL ?>admin/pagamentos/detalhes/<?= htmlspecialchars($transaction['order_id']) ?>" class="btn btn-sm btn-primary" title="Detalhes do Pedido">
                                                        <i class="fas fa-shopping-cart"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">Nenhuma transação encontrada</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer clearfix">
                    <ul class="pagination pagination-sm m-0 float-right">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= BASE_URL ?>admin/pagamentos/transacoes?page=<?= $i ?>&gateway=<?= htmlspecialchars($gateway ?? '') ?>&method=<?= htmlspecialchars($method ?? '') ?>&status=<?= htmlspecialchars($status ?? '') ?>&start_date=<?= htmlspecialchars($startDate ?? '') ?>&end_date=<?= htmlspecialchars($endDate ?? '') ?>&order_number=<?= htmlspecialchars($orderNumber ?? '') ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Exportar para Excel
    document.getElementById('export-excel').addEventListener('click', function() {
        // Obter parâmetros do formulário
        const form = document.getElementById('filter-form');
        const formData = new FormData(form);
        
        // Construir URL com os parâmetros
        let params = new URLSearchParams();
        for (let pair of formData.entries()) {
            if (pair[1]) {
                params.append(pair[0], pair[1]);
            }
        }
        
        // Adicionar parâmetro de exportação
        params.append('export', 'excel');
        
        // Redirecionar para a URL de exportação
        window.location.href = '<?= BASE_URL ?>admin/pagamentos/exportar?' + params.toString();
    });
    
    // Exportar para PDF
    document.getElementById('export-pdf').addEventListener('click', function() {
        // Obter parâmetros do formulário
        const form = document.getElementById('filter-form');
        const formData = new FormData(form);
        
        // Construir URL com os parâmetros
        let params = new URLSearchParams();
        for (let pair of formData.entries()) {
            if (pair[1]) {
                params.append(pair[0], pair[1]);
            }
        }
        
        // Adicionar parâmetro de exportação
        params.append('export', 'pdf');
        
        // Redirecionar para a URL de exportação
        window.location.href = '<?= BASE_URL ?>admin/pagamentos/exportar?' + params.toString();
    });
});
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
