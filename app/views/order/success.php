<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-header bg-success text-white">
                    <h1 class="h4 mb-0"><i class="bi bi-check-circle me-2"></i> Pagamento Aprovado</h1>
                </div>
                
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="success-icon mb-3">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        </div>
                        
                        <h2 class="h4">Pedido realizado com sucesso!</h2>
                        <p class="lead mb-0">Seu pedido #<?= htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') ?> foi confirmado.</p>
                    </div>
                    
                    <div class="order-details bg-light p-4 rounded mb-4">
                        <h3 class="h5 mb-3">Detalhes do Pedido</h3>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="detail-item">
                                    <div class="detail-label text-muted">Número do pedido</div>
                                    <div class="detail-value fw-bold"><?= htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="detail-item">
                                    <div class="detail-label text-muted">Status</div>
                                    <div class="detail-value">
                                        <span class="badge bg-success">Pagamento Aprovado</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="detail-item">
                                    <div class="detail-label text-muted">Data</div>
                                    <div class="detail-value"><?= date('d/m/Y H:i') ?></div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="detail-item">
                                    <div class="detail-label text-muted">Valor Total</div>
                                    <div class="detail-value fw-bold">R$ <?= number_format($total, 2, ',', '.') ?></div>
                                </div>
                            </div>
                            
                            <?php if ($hasCustomItems && $estimatedDeliveryDate): ?>
                            <div class="col-md-6 mb-3">
                                <div class="detail-item">
                                    <div class="detail-label text-muted">Tipo de pedido</div>
                                    <div class="detail-value">Impressão sob demanda</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="detail-item">
                                    <div class="detail-label text-muted">Previsão de entrega</div>
                                    <div class="detail-value"><?= htmlspecialchars($estimatedDeliveryDate, ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="col-md-6 mb-3">
                                <div class="detail-item">
                                    <div class="detail-label text-muted">Forma de pagamento</div>
                                    <div class="detail-value"><?= htmlspecialchars($order['payment_method_display'] ?? $order['payment_method'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($hasCustomItems): ?>
                    <div class="alert alert-info mb-4">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="bi bi-info-circle-fill fs-4 me-2"></i>
                            </div>
                            <div>
                                <h4 class="alert-heading h5">Informações Importantes</h4>
                                <p class="mb-1">Seu pedido contém itens personalizados que serão impressos sob demanda.</p>
                                <p class="mb-0">Você receberá atualizações sobre o status da impressão e envio por e-mail.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="order-items mb-4">
                        <h3 class="h5 mb-3">Itens do Pedido</h3>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produto</th>
                                        <th class="text-center">Qtd</th>
                                        <th class="text-end">Preço</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?>
                                            <?php if (!$item['is_stock_item']): ?>
                                                <span class="badge bg-warning text-dark ms-2">Sob Demanda</span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($item['selected_scale'])): ?>
                                                <div class="small text-muted">Escala: <?= htmlspecialchars($item['selected_scale'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($item['selected_filament'])): ?>
                                                <div class="small text-muted">Filamento: <?= htmlspecialchars($item['selected_filament'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($item['selected_color'])): ?>
                                                <div class="small text-muted">Cor: <?= htmlspecialchars($item['selected_color'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= (int)$item['quantity'] ?></td>
                                        <td class="text-end">R$ <?= number_format((float)$item['price'] * (int)$item['quantity'], 2, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold">Subtotal</td>
                                        <td class="text-end">R$ <?= number_format($order['subtotal'], 2, ',', '.') ?></td>
                                    </tr>
                                    <?php if ($order['discount'] > 0): ?>
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold">Desconto</td>
                                        <td class="text-end">- R$ <?= number_format($order['discount'], 2, ',', '.') ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold">Frete</td>
                                        <td class="text-end">R$ <?= number_format($order['shipping_cost'], 2, ',', '.') ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold">Total</td>
                                        <td class="text-end fw-bold">R$ <?= number_format($order['total'], 2, ',', '.') ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    <div class="text-center mb-4">
                        <p>Uma confirmação foi enviada para o seu e-mail.</p>
                        <p class="mb-0">
                            <strong>Obrigado por comprar na Taverna da Impressão 3D!</strong>
                        </p>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>" class="btn btn-outline-primary">
                            <i class="bi bi-house"></i> Voltar para a loja
                        </a>
                        <a href="<?= BASE_URL ?>minha-conta/pedido/<?= (int)$order['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-eye"></i> Ver detalhes do pedido
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>