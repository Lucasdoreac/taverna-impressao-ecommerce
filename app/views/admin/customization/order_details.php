<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Detalhes de Personalização do Pedido #<?= $order['order_number'] ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/pedidos">Pedidos</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/pedidos/visualizar/<?= $order['id'] ?>">Pedido #<?= $order['order_number'] ?></a></li>
                        <li class="breadcrumb-item active">Personalizações</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Informações do pedido -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informações do Pedido</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Número do Pedido:</strong> #<?= $order['order_number'] ?></p>
                            <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                            <p><strong>Status:</strong> 
                                <?php if ($order['status'] === 'pending'): ?>
                                    <span class="badge badge-warning">Pendente</span>
                                <?php elseif ($order['status'] === 'processing'): ?>
                                    <span class="badge badge-info">Em Processamento</span>
                                <?php elseif ($order['status'] === 'shipped'): ?>
                                    <span class="badge badge-primary">Enviado</span>
                                <?php elseif ($order['status'] === 'delivered'): ?>
                                    <span class="badge badge-success">Entregue</span>
                                <?php elseif ($order['status'] === 'canceled'): ?>
                                    <span class="badge badge-danger">Cancelado</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Total:</strong> R$ <?= number_format($order['total'], 2, ',', '.') ?></p>
                            <p><strong>Forma de Pagamento:</strong> 
                                <?php if ($order['payment_method'] === 'credit_card'): ?>
                                    Cartão de Crédito
                                <?php elseif ($order['payment_method'] === 'boleto'): ?>
                                    Boleto
                                <?php elseif ($order['payment_method'] === 'pix'): ?>
                                    PIX
                                <?php endif; ?>
                            </p>
                            <p><strong>Status do Pagamento:</strong> 
                                <?php if ($order['payment_status'] === 'pending'): ?>
                                    <span class="badge badge-warning">Pendente</span>
                                <?php elseif ($order['payment_status'] === 'paid'): ?>
                                    <span class="badge badge-success">Pago</span>
                                <?php elseif ($order['payment_status'] === 'refunded'): ?>
                                    <span class="badge badge-info">Reembolsado</span>
                                <?php elseif ($order['payment_status'] === 'canceled'): ?>
                                    <span class="badge badge-danger">Cancelado</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <?php if ($customer): ?>
                                <p><strong>Cliente:</strong> <?= $customer['name'] ?></p>
                                <p><strong>Email:</strong> <?= $customer['email'] ?></p>
                                <p><strong>Telefone:</strong> <?= $customer['phone'] ?? 'N/A' ?></p>
                            <?php else: ?>
                                <p><strong>Cliente:</strong> Cliente não registrado</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Itens do pedido com personalização -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Itens com Personalização</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($orderItems)): ?>
                        <div class="alert alert-info">
                            Este pedido não possui itens.
                        </div>
                    <?php else: ?>
                        <div class="accordion" id="orderItemsAccordion">
                            <?php 
                            $hasCustomization = false;
                            foreach ($orderItems as $index => $item): 
                                // Verificar se tem dados de personalização
                                if (empty($item['customization_data'])) {
                                    continue;
                                }
                                
                                $hasCustomization = true;
                            ?>
                                <div class="card">
                                    <div class="card-header" id="heading<?= $item['id'] ?>">
                                        <h2 class="mb-0">
                                            <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse<?= $item['id'] ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $item['id'] ?>">
                                                <?= $item['product_name'] ?> (<?= $item['quantity'] ?>x)
                                                <span class="badge badge-info ml-2">Personalizado</span>
                                            </button>
                                        </h2>
                                    </div>

                                    <div id="collapse<?= $item['id'] ?>" class="collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $item['id'] ?>" data-parent="#orderItemsAccordion">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h5>Detalhes da Personalização</h5>
                                                    <ul class="list-group">
                                                        <?php foreach ($item['customization_data'] as $customData): ?>
                                                            <li class="list-group-item">
                                                                <strong><?= $customData['option']['name'] ?>:</strong>
                                                                <?php if ($customData['option']['type'] === 'text'): ?>
                                                                    <p class="mb-0"><?= htmlspecialchars($customData['value']) ?></p>
                                                                <?php elseif ($customData['option']['type'] === 'select'): ?>
                                                                    <?php 
                                                                    $options = json_decode($customData['option']['options'], true);
                                                                    echo htmlspecialchars($options[$customData['value']] ?? $customData['value']);
                                                                    ?>
                                                                <?php elseif ($customData['option']['type'] === 'upload'): ?>
                                                                    <?php if (!empty($customData['value'])): ?>
                                                                        <?php
                                                                        $ext = strtolower(pathinfo($customData['value'], PATHINFO_EXTENSION));
                                                                        if (in_array($ext, ['jpg', 'jpeg', 'png'])):
                                                                        ?>
                                                                            <div class="mt-2">
                                                                                <img src="<?= BASE_URL ?>uploads/customization/thumbs/<?= $customData['value'] ?>" 
                                                                                    alt="Arquivo enviado" class="img-thumbnail" style="max-width: 150px;">
                                                                                <a href="<?= BASE_URL ?>uploads/customization/<?= $customData['value'] ?>" 
                                                                                   class="btn btn-sm btn-info mt-1" target="_blank">
                                                                                    <i class="fas fa-eye"></i> Ver Original
                                                                                </a>
                                                                                <a href="<?= BASE_URL ?>uploads/customization/<?= $customData['value'] ?>" 
                                                                                   class="btn btn-sm btn-secondary mt-1" download>
                                                                                    <i class="fas fa-download"></i> Download
                                                                                </a>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="mt-2">
                                                                                <span class="file-name"><?= $customData['value'] ?></span>
                                                                                <a href="<?= BASE_URL ?>uploads/customization/<?= $customData['value'] ?>" 
                                                                                   class="btn btn-sm btn-secondary" download>
                                                                                    <i class="fas fa-download"></i> Download
                                                                                </a>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    <?php else: ?>
                                                                        <p class="text-muted mb-0">Nenhum arquivo enviado</p>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                                
                                                                <?php if ($customData['option']['description']): ?>
                                                                    <small class="text-muted d-block mt-1">
                                                                        <i class="fas fa-info-circle"></i> 
                                                                        <?= $customData['option']['description'] ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5>Informações do Produto</h5>
                                                    <p><strong>Preço unitário:</strong> R$ <?= number_format($item['price'], 2, ',', '.') ?></p>
                                                    <p><strong>Quantidade:</strong> <?= $item['quantity'] ?></p>
                                                    <p><strong>Subtotal:</strong> R$ <?= number_format($item['price'] * $item['quantity'], 2, ',', '.') ?></p>
                                                    
                                                    <div class="alert alert-info mt-3">
                                                        <h5><i class="icon fas fa-info"></i> Nota</h5>
                                                        <p class="mb-0">As personalizações deste item devem ser aplicadas na produção.</p>
                                                    </div>
                                                    
                                                    <div class="btn-group mt-3">
                                                        <a href="<?= BASE_URL ?>admin/producao/visualizar/<?= $item['id'] ?>" class="btn btn-primary">
                                                            <i class="fas fa-cogs"></i> Enviar para Produção
                                                        </a>
                                                        <a href="<?= BASE_URL ?>admin/customizacao/imprimir/<?= $item['id'] ?>" class="btn btn-secondary" target="_blank">
                                                            <i class="fas fa-print"></i> Imprimir Detalhes
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (!$hasCustomization): ?>
                                <div class="alert alert-info">
                                    Este pedido não possui itens com personalizações.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="<?= BASE_URL ?>admin/pedidos/visualizar/<?= $order['id'] ?>" class="btn btn-default">
                        <i class="fas fa-arrow-left"></i> Voltar para Detalhes do Pedido
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>