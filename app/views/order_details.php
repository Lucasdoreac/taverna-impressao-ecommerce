<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <div class="row">
        <!-- Menu lateral da conta -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Minha Conta</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>minha-conta" class="list-group-item list-group-item-action">
                        <i class="bi bi-person me-2"></i> Dados Pessoais
                    </a>
                    <a href="<?= BASE_URL ?>minha-conta/endereco" class="list-group-item list-group-item-action">
                        <i class="bi bi-geo-alt me-2"></i> Endereços
                    </a>
                    <a href="<?= BASE_URL ?>minha-conta/pedidos" class="list-group-item list-group-item-action active">
                        <i class="bi bi-box me-2"></i> Meus Pedidos
                    </a>
                    <a href="<?= BASE_URL ?>logout" class="list-group-item list-group-item-action text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> Sair
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Conteúdo principal -->
        <div class="col-lg-9">
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Pedido #<?= $order['order_number'] ?></h5>
                        <a href="<?= BASE_URL ?>minha-conta/pedidos" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Voltar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <h6 class="text-muted mb-2">Informações do Pedido</h6>
                            <p class="mb-1"><strong>Número do Pedido:</strong> #<?= $order['order_number'] ?></p>
                            <p class="mb-1"><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                            <p class="mb-1">
                                <strong>Status:</strong> 
                                <?php 
                                $statusClass = '';
                                $statusText = '';
                                
                                switch($order['status']) {
                                    case 'pending':
                                        $statusClass = 'bg-warning';
                                        $statusText = 'Pendente';
                                        break;
                                    case 'processing':
                                        $statusClass = 'bg-info';
                                        $statusText = 'Em Processamento';
                                        break;
                                    case 'shipped':
                                        $statusClass = 'bg-primary';
                                        $statusText = 'Enviado';
                                        break;
                                    case 'delivered':
                                        $statusClass = 'bg-success';
                                        $statusText = 'Entregue';
                                        break;
                                    case 'canceled':
                                        $statusClass = 'bg-danger';
                                        $statusText = 'Cancelado';
                                        break;
                                    default:
                                        $statusClass = 'bg-secondary';
                                        $statusText = ucfirst($order['status']);
                                }
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                            </p>
                            <p class="mb-0">
                                <strong>Pagamento:</strong> 
                                <?php 
                                $paymentStatus = '';
                                switch($order['payment_status']) {
                                    case 'pending':
                                        $paymentStatus = '<span class="badge bg-warning">Pendente</span>';
                                        break;
                                    case 'paid':
                                        $paymentStatus = '<span class="badge bg-success">Pago</span>';
                                        break;
                                    case 'refunded':
                                        $paymentStatus = '<span class="badge bg-info">Reembolsado</span>';
                                        break;
                                    case 'canceled':
                                        $paymentStatus = '<span class="badge bg-danger">Cancelado</span>';
                                        break;
                                    default:
                                        $paymentStatus = '<span class="badge bg-secondary">'.ucfirst($order['payment_status']).'</span>';
                                }
                                
                                $paymentMethod = '';
                                switch($order['payment_method']) {
                                    case 'credit_card':
                                        $paymentMethod = 'Cartão de Crédito';
                                        break;
                                    case 'boleto':
                                        $paymentMethod = 'Boleto';
                                        break;
                                    case 'pix':
                                        $paymentMethod = 'PIX';
                                        break;
                                    default:
                                        $paymentMethod = ucfirst($order['payment_method']);
                                }
                                ?>
                                <?= $paymentMethod ?> - <?= $paymentStatus ?>
                            </p>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Endereço de Entrega</h6>
                            <?php if ($address): ?>
                            <p class="mb-1"><?= $address['address'] ?>, <?= $address['number'] ?></p>
                            <?php if (!empty($address['complement'])): ?>
                            <p class="mb-1"><?= $address['complement'] ?></p>
                            <?php endif; ?>
                            <p class="mb-1"><?= $address['neighborhood'] ?></p>
                            <p class="mb-1"><?= $address['city'] ?> - <?= $address['state'] ?></p>
                            <p class="mb-0">CEP: <?= $address['zipcode'] ?></p>
                            <?php else: ?>
                            <p class="text-muted">Endereço não disponível</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h6 class="text-muted mb-3">Itens do Pedido</h6>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="table-light">
                                <tr>
                                    <th>Produto</th>
                                    <th class="text-center">Quantidade</th>
                                    <th class="text-end">Preço</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?= $item['product_name'] ?></strong>
                                            <?php if (!empty($item['customization_data'])): ?>
                                            <span class="badge bg-info ms-2">Personalizado</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center"><?= $item['quantity'] ?></td>
                                    <td class="text-end">R$ <?= number_format($item['price'], 2, ',', '.') ?></td>
                                    <td class="text-end">R$ <?= number_format($item['price'] * $item['quantity'], 2, ',', '.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal</strong></td>
                                    <td class="text-end">R$ <?= number_format($order['subtotal'], 2, ',', '.') ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Frete (<?= $order['shipping_method'] ?>)</strong></td>
                                    <td class="text-end">R$ <?= number_format($order['shipping_cost'], 2, ',', '.') ?></td>
                                </tr>
                                <?php if ($order['discount'] > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Desconto</strong></td>
                                    <td class="text-end">- R$ <?= number_format($order['discount'], 2, ',', '.') ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total</strong></td>
                                    <td class="text-end"><strong>R$ <?= number_format($order['total'], 2, ',', '.') ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <?php if ($order['status'] !== 'canceled' && in_array($order['status'], ['pending', 'processing'])): ?>
                    <div class="mt-4 text-end">
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                            <i class="bi bi-x-circle me-1"></i> Cancelar Pedido
                        </button>
                    </div>
                    
                    <!-- Modal de Cancelamento -->
                    <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="cancelOrderModalLabel">Confirmar Cancelamento</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Tem certeza que deseja cancelar o pedido #<?= $order['order_number'] ?>?</p>
                                    <p class="text-danger"><strong>Atenção:</strong> Esta ação não pode ser desfeita.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Não, manter pedido</button>
                                    <form action="<?= BASE_URL ?>pedido/cancelar/<?= $order['order_number'] ?>" method="post">
                                        <button type="submit" class="btn btn-danger">Sim, cancelar pedido</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($order['tracking_code']): ?>
                    <div class="mt-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><i class="bi bi-truck me-2"></i>Informações de Rastreio</h6>
                                <p class="mb-1"><strong>Código de Rastreio:</strong> <?= $order['tracking_code'] ?></p>
                                <p class="mb-0">
                                    <a href="https://www.correios.com.br/rastreamento" target="_blank" class="btn btn-sm btn-primary mt-2">
                                        Rastrear Pedido
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($order['payment_method'] === 'boleto' && $order['payment_status'] === 'pending'): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0"><i class="bi bi-cash me-2"></i>Pagamento Pendente</h5>
                </div>
                <div class="card-body text-center">
                    <p>Seu boleto foi gerado e está aguardando pagamento.</p>
                    <a href="#" class="btn btn-primary" onclick="alert('Boleto simulado. Em uma implementação real, este link levaria para o boleto.')">
                        <i class="bi bi-file-earmark-text me-2"></i>Visualizar Boleto
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($order['payment_method'] === 'pix' && $order['payment_status'] === 'pending'): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0"><i class="bi bi-cash me-2"></i>Pagamento Pendente</h5>
                </div>
                <div class="card-body text-center">
                    <p>Seu código PIX foi gerado e está aguardando pagamento.</p>
                    <div class="my-3">
                        <img src="<?= BASE_URL ?>assets/images/pix-qrcode-example.png" alt="QR Code PIX" style="max-width: 200px;">
                    </div>
                    <div class="input-group mb-3 mx-auto" style="max-width: 500px;">
                        <input type="text" class="form-control" value="00020126580014br.gov.bcb.pix0136example.com/pix/v2/cobv/7269c736" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="alert('Código PIX copiado!')">Copiar</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>