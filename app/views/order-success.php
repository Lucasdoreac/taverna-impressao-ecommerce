<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <div class="card mb-4 border-success">
        <div class="card-body text-center py-5">
            <div class="mb-4">
                <i class="bi bi-check-circle-fill display-1 text-success"></i>
            </div>
            
            <h1 class="h2 mb-3">Pedido Realizado com Sucesso!</h1>
            <p class="mb-0">Seu pedido <strong>#<?= $order['order_number'] ?></strong> foi registrado e está sendo processado.</p>
            <p class="mb-4">Agradecemos pela sua compra e iremos enviar atualizações por e-mail.</p>
            
            <?php if ($order['payment_method'] === 'boleto'): ?>
            <div class="alert alert-info mx-auto" style="max-width: 600px;">
                <h5 class="mb-2"><i class="bi bi-info-circle me-2"></i> Instruções para Pagamento</h5>
                <p class="mb-2">Seu boleto já foi gerado. Você receberá as instruções de pagamento por e-mail.</p>
                <a href="#" class="btn btn-primary mt-2" onclick="alert('Boleto simulado. Em produção, este link levaria para o boleto real.')">
                    <i class="bi bi-file-earmark-text me-2"></i>Visualizar Boleto
                </a>
            </div>
            <?php elseif ($order['payment_method'] === 'pix'): ?>
            <div class="alert alert-info mx-auto" style="max-width: 600px;">
                <h5 class="mb-2"><i class="bi bi-info-circle me-2"></i> Instruções para Pagamento</h5>
                <p class="mb-2">Escaneie o QR Code abaixo ou copie o código PIX para realizar o pagamento.</p>
                <div class="text-center my-3">
                    <img src="<?= BASE_URL ?>assets/images/pix-qrcode-example.png" alt="QR Code PIX" style="max-width: 200px;">
                </div>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" value="00020126580014br.gov.bcb.pix0136example.com/pix/v2/cobv/7269c736" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="alert('Código PIX copiado!')">Copiar</button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="row">
        <!-- Detalhes do Pedido -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Detalhes do Pedido</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Informações do Pedido</h6>
                            <p class="mb-1"><strong>Número do Pedido:</strong> #<?= $order['order_number'] ?></p>
                            <p class="mb-1"><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                            <p class="mb-1">
                                <strong>Status:</strong> 
                                <span class="badge bg-primary"><?= ucfirst($order['status']) ?></span>
                            </p>
                            <p class="mb-0">
                                <strong>Pagamento:</strong> 
                                <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                    <?= $order['payment_status'] === 'paid' ? 'Pago' : 'Pendente' ?>
                                </span>
                            </p>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Endereço de Entrega</h6>
                            <p class="mb-1"><?= $order['address'] ?>, <?= $order['number'] ?></p>
                            <?php if (!empty($order['complement'])): ?>
                            <p class="mb-1"><?= $order['complement'] ?></p>
                            <?php endif; ?>
                            <p class="mb-1"><?= $order['neighborhood'] ?></p>
                            <p class="mb-1"><?= $order['city'] ?> - <?= $order['state'] ?></p>
                            <p class="mb-0">CEP: <?= $order['zipcode'] ?></p>
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
                                <?php foreach ($order_items as $item): ?>
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
                </div>
            </div>
        </div>
        
        <!-- Resumo e Próximos Passos -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Pagamento</h5>
                </div>
                <div class="card-body">
                    <p><strong>Método:</strong> 
                        <?php 
                            switch($order['payment_method']) {
                                case 'credit_card':
                                    echo 'Cartão de Crédito';
                                    break;
                                case 'boleto':
                                    echo 'Boleto Bancário';
                                    break;
                                case 'pix':
                                    echo 'PIX';
                                    break;
                                default:
                                    echo $order['payment_method'];
                            }
                        ?>
                    </p>
                    <p class="mb-0"><strong>Status:</strong> 
                        <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                            <?php 
                                switch($order['payment_status']) {
                                    case 'paid':
                                        echo 'Pago';
                                        break;
                                    case 'pending':
                                        echo 'Pendente';
                                        break;
                                    case 'refunded':
                                        echo 'Reembolsado';
                                        break;
                                    case 'canceled':
                                        echo 'Cancelado';
                                        break;
                                    default:
                                        echo ucfirst($order['payment_status']);
                                }
                            ?>
                        </span>
                    </p>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Entrega</h5>
                </div>
                <div class="card-body">
                    <p><strong>Método:</strong> <?= $order['shipping_method'] ?></p>
                    <p class="mb-0"><strong>Status:</strong> 
                        <span class="badge bg-<?= $order['status'] === 'delivered' ? 'success' : 'info' ?>">
                            <?php 
                                switch($order['status']) {
                                    case 'pending':
                                        echo 'Em processamento';
                                        break;
                                    case 'processing':
                                        echo 'Preparando envio';
                                        break;
                                    case 'shipped':
                                        echo 'Enviado';
                                        break;
                                    case 'delivered':
                                        echo 'Entregue';
                                        break;
                                    case 'canceled':
                                        echo 'Cancelado';
                                        break;
                                    default:
                                        echo ucfirst($order['status']);
                                }
                            ?>
                        </span>
                    </p>
                    
                    <?php if ($order['tracking_code']): ?>
                    <div class="mt-3">
                        <p class="mb-1"><strong>Código de Rastreio:</strong></p>
                        <code><?= $order['tracking_code'] ?></code>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Próximos Passos</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php if ($order['payment_status'] !== 'paid'): ?>
                        <li class="list-group-item">
                            <i class="bi bi-credit-card me-2 text-primary"></i>
                            Concluir pagamento
                        </li>
                        <?php endif; ?>
                        
                        <li class="list-group-item">
                            <i class="bi bi-box-seam me-2 text-primary"></i>
                            Aguardar confirmação de envio
                        </li>
                        
                        <li class="list-group-item">
                            <i class="bi bi-truck me-2 text-primary"></i>
                            Acompanhar entrega
                        </li>
                    </ul>
                    
                    <div class="d-grid gap-2 mt-3">
                        <a href="<?= BASE_URL ?>minha-conta/pedidos" class="btn btn-primary">
                            Acompanhar Pedido
                        </a>
                        <a href="<?= BASE_URL ?>" class="btn btn-outline-secondary">
                            Continuar Comprando
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>