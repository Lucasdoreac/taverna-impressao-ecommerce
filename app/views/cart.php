<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <h1 class="h2 mb-4">Carrinho de Compras</h1>
    
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
    
    <?php if (empty($cart_items)): ?>
    <div class="card mb-4">
        <div class="card-body text-center py-5">
            <i class="bi bi-cart-x display-1 text-muted mb-3"></i>
            <h2 class="h4 mb-3">Seu carrinho está vazio</h2>
            <p class="mb-4">Adicione produtos ao seu carrinho para continuar.</p>
            <a href="<?= BASE_URL ?>produtos" class="btn btn-primary">Ver Produtos</a>
        </div>
    </div>
    <?php else: ?>
    
    <div class="row">
        <!-- Itens do Carrinho -->
        <div class="col-lg-8 mb-4">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Itens no Carrinho</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Produto</th>
                                    <th>Preço</th>
                                    <th>Quantidade</th>
                                    <th class="text-end pe-4">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <?php if ($item['image']): ?>
                                            <img src="<?= BASE_URL ?>uploads/products/<?= $item['image'] ?>" alt="<?= $item['name'] ?>" class="img-thumbnail me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                            <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div>
                                                <h6 class="mb-1">
                                                    <a href="<?= BASE_URL ?>produto/<?= $item['slug'] ?>" class="text-decoration-none text-dark">
                                                        <?= $item['name'] ?>
                                                    </a>
                                                </h6>
                                                
                                                <!-- Indicador de Disponibilidade (Pronta Entrega ou Sob Encomenda) -->
                                                <?php if (isset($item['availability'])): ?>
                                                <div class="mb-1">
                                                    <?php if ($item['availability'] === 'Pronta Entrega'): ?>
                                                    <span class="badge bg-success">Pronta Entrega</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Sob Encomenda</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <!-- Detalhes de Impressão 3D -->
                                                <?php if (isset($item['filament_type']) || isset($item['selected_scale']) || isset($item['selected_color'])): ?>
                                                <div class="small text-muted mb-2">
                                                    <?php if (isset($item['selected_scale'])): ?>
                                                    <span class="me-2"><i class="bi bi-rulers"></i> Escala: <?= $item['selected_scale'] ?></span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($item['selected_filament'])): ?>
                                                    <span class="me-2"><i class="bi bi-recycle"></i> Filamento: <?= $item['selected_filament'] ?></span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($item['selected_color'])): ?>
                                                    <span>
                                                        <i class="bi bi-palette"></i> Cor: 
                                                        <?php if (isset($item['color_name'])): ?>
                                                            <?= $item['color_name'] ?>
                                                        <?php else: ?>
                                                            ID: <?= $item['selected_color'] ?>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (isset($item['color_hex'])): ?>
                                                        <span class="d-inline-block rounded-circle" style="width: 12px; height: 12px; background-color: <?= $item['color_hex'] ?>; border: 1px solid #ccc;"></span>
                                                        <?php endif; ?>
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Botão para editar opções de impressão -->
                                                <button class="btn btn-sm btn-outline-secondary mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#printing-options-<?= $item['cart_item_id'] ?>">
                                                    <i class="bi bi-sliders"></i> Opções de Impressão
                                                </button>
                                                
                                                <!-- Formulário para editar opções de impressão -->
                                                <div class="collapse" id="printing-options-<?= $item['cart_item_id'] ?>">
                                                    <div class="card card-body bg-light mt-2 mb-2">
                                                        <form action="<?= BASE_URL ?>carrinho/update-options" method="post">
                                                            <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id'] ?>">
                                                            
                                                            <!-- Seleção de Escala -->
                                                            <div class="mb-2">
                                                                <label class="form-label fw-semibold small">Escala</label>
                                                                <select name="selected_scale" class="form-select form-select-sm">
                                                                    <?php foreach ($available_scales as $scale): ?>
                                                                    <option value="<?= $scale['id'] ?>" <?= (isset($item['selected_scale']) && $item['selected_scale'] === $scale['id']) ? 'selected' : '' ?>>
                                                                        <?= $scale['name'] ?>
                                                                    </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <div class="form-text small">A escala afeta o tamanho final da miniatura.</div>
                                                            </div>
                                                            
                                                            <!-- Seleção de Tipo de Filamento -->
                                                            <div class="mb-2">
                                                                <label class="form-label fw-semibold small">Tipo de Filamento</label>
                                                                <select name="selected_filament" class="form-select form-select-sm">
                                                                    <?php foreach ($filament_types as $type_id => $type_name): ?>
                                                                    <option value="<?= $type_id ?>" <?= (isset($item['selected_filament']) && $item['selected_filament'] === $type_id) ? 'selected' : '' ?>>
                                                                        <?= $type_name ?>
                                                                    </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <div class="form-text small">Diferentes materiais têm propriedades distintas.</div>
                                                            </div>
                                                            
                                                            <!-- Seleção de Cor -->
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold small">Cor do Filamento</label>
                                                                <div class="d-flex flex-wrap">
                                                                    <?php foreach ($filament_colors as $color): ?>
                                                                    <div class="form-check me-3 mb-2">
                                                                        <input class="form-check-input" type="radio" name="selected_color" 
                                                                               id="color-<?= $item['cart_item_id'] ?>-<?= $color['id'] ?>" 
                                                                               value="<?= $color['id'] ?>"
                                                                               <?= (isset($item['selected_color']) && $item['selected_color'] == $color['id']) ? 'checked' : '' ?>>
                                                                        <label class="form-check-label d-flex align-items-center" for="color-<?= $item['cart_item_id'] ?>-<?= $color['id'] ?>">
                                                                            <span class="d-inline-block rounded me-1" style="width: 16px; height: 16px; background-color: <?= $color['hex_code'] ?>; border: 1px solid #ccc;"></span>
                                                                            <?= $color['name'] ?>
                                                                        </label>
                                                                    </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                            
                                                            <button type="submit" class="btn btn-sm btn-primary">Atualizar Opções</button>
                                                        </form>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <!-- Opções de personalização existentes -->
                                                <?php if (!empty($item['customization'])): ?>
                                                <small class="d-block text-muted mb-1">Personalizado</small>
                                                
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#customization-<?= $item['cart_item_id'] ?>">
                                                    Detalhes da Personalização
                                                </button>
                                                
                                                <div class="collapse mt-2" id="customization-<?= $item['cart_item_id'] ?>">
                                                    <div class="card card-body bg-light small">
                                                        <ul class="list-unstyled mb-0">
                                                            <?php foreach ($item['customization'] as $option): ?>
                                                            <li class="mb-1">
                                                                <strong><?= $option['name'] ?>:</strong> 
                                                                <?php if ($option['type'] === 'file'): ?>
                                                                    <span class="text-primary">Arquivo enviado</span>
                                                                <?php else: ?>
                                                                    <?= htmlspecialchars($option['value']) ?>
                                                                <?php endif; ?>
                                                            </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="mt-2 d-block d-md-none">
                                                    <a href="<?= BASE_URL ?>carrinho/remover/<?= $item['cart_item_id'] ?>" class="text-danger small">
                                                        <i class="bi bi-trash"></i> Remover
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">R$ <?= number_format($item['price'], 2, ',', '.') ?></span>
                                    </td>
                                    <td>
                                        <form action="<?= BASE_URL ?>carrinho/atualizar" method="post" class="d-flex align-items-center quantity-form">
                                            <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id'] ?>">
                                            <div class="input-group input-group-sm" style="width: 100px;">
                                                <button class="btn btn-outline-secondary quantity-decrease" type="button">-</button>
                                                <input type="number" class="form-control text-center quantity-input" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="99">
                                                <button class="btn btn-outline-secondary quantity-increase" type="button">+</button>
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-end pe-4">
                                        <span class="fw-semibold">R$ <?= number_format($item['total'], 2, ',', '.') ?></span>
                                        
                                        <div class="mt-2 d-none d-md-block">
                                            <a href="<?= BASE_URL ?>carrinho/remover/<?= $item['cart_item_id'] ?>" class="text-danger small">
                                                <i class="bi bi-trash"></i> Remover
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?= BASE_URL ?>produtos" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Continuar Comprando
                        </a>
                        <a href="<?= BASE_URL ?>carrinho/clear" class="btn btn-outline-danger" onclick="return confirm('Tem certeza que deseja esvaziar o carrinho?')">
                            <i class="bi bi-trash"></i> Esvaziar Carrinho
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Resumo da Compra -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Resumo da Compra</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Subtotal</span>
                        <span class="fw-semibold">R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                    </div>
                    
                    <!-- Informações de Impressão 3D (sob encomenda) -->
                    <?php if ($printing_time > 0): ?>
                    <div class="card bg-light mb-3">
                        <div class="card-body p-3">
                            <h6 class="card-title">Informações de Impressão 3D</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Tempo estimado de impressão:</span>
                                <span class="fw-medium">
                                    <?php 
                                    $hours = floor($printing_time);
                                    $minutes = round(($printing_time - $hours) * 60);
                                    echo $hours . 'h ' . $minutes . 'min';
                                    ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Filamento estimado:</span>
                                <span class="fw-medium"><?= number_format($filament_usage, 0, '', '.') ?>g</span>
                            </div>
                            <div class="form-text small mt-2">
                                <i class="bi bi-info-circle"></i> 
                                Produtos sob encomenda serão impressos após a confirmação do pagamento.
                                O prazo para impressão e envio pode variar de acordo com a fila de produção.
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Cálculo de Frete -->
                    <div class="mb-3">
                        <label for="shipping-method" class="form-label">Método de Envio</label>
                        <select id="shipping-method" class="form-select" name="shipping_method">
                            <option value="">Selecione o método de envio</option>
                            <?php foreach ($shipping_methods as $method): ?>
                            <option value="<?= $method['name'] ?>" data-price="<?= $method['price'] ?>">
                                <?= $method['name'] ?> - R$ <?= number_format($method['price'], 2, ',', '.') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cep" class="form-label">CEP</label>
                        <div class="input-group">
                            <input type="text" id="cep" class="form-control" placeholder="00000-000" maxlength="9">
                            <button class="btn btn-outline-secondary" type="button" id="calculate-shipping">Calcular</button>
                        </div>
                        <div id="shipping-result" class="form-text"></div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span>Frete</span>
                        <span class="fw-semibold" id="shipping-cost">R$ 0,00</span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-4">
                        <span class="h5">Total</span>
                        <span class="h5" id="total-amount">R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                    </div>
                    
                    <a href="<?= BASE_URL ?>checkout" class="btn btn-primary w-100" id="checkout-button" disabled>
                        Finalizar Compra
                    </a>
                </div>
            </div>
            
            <!-- Cupom de Desconto -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Cupom de Desconto</h5>
                </div>
                <div class="card-body">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Código do cupom">
                        <button class="btn btn-outline-secondary" type="button">Aplicar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variáveis para cálculos
    const subtotal = <?= $subtotal ?>;
    let shippingCost = 0;
    let totalAmount = subtotal;
    
    // Elementos do DOM
    const shippingMethodSelect = document.getElementById('shipping-method');
    const shippingCostDisplay = document.getElementById('shipping-cost');
    const totalAmountDisplay = document.getElementById('total-amount');
    const checkoutButton = document.getElementById('checkout-button');
    const cepInput = document.getElementById('cep');
    const calculateShippingButton = document.getElementById('calculate-shipping');
    const shippingResult = document.getElementById('shipping-result');
    
    // Máscara para o CEP
    cepInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 5) {
            value = value.substring(0, 5) + '-' + value.substring(5, 8);
        }
        e.target.value = value;
    });
    
    // Atualizar preços ao selecionar método de envio
    shippingMethodSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value) {
            shippingCost = parseFloat(selectedOption.dataset.price);
            checkoutButton.disabled = false;
        } else {
            shippingCost = 0;
            checkoutButton.disabled = true;
        }
        
        // Atualizar exibição
        updatePriceDisplay();
    });
    
    // Simular cálculo de frete
    calculateShippingButton.addEventListener('click', function() {
        const cep = cepInput.value.replace(/\D/g, '');
        
        if (cep.length !== 8) {
            shippingResult.innerHTML = '<span class="text-danger">CEP inválido</span>';
            return;
        }
        
        // Simular carregamento
        shippingResult.innerHTML = '<span class="text-muted">Calculando...</span>';
        
        // Simular resposta após 1 segundo
        setTimeout(function() {
            shippingResult.innerHTML = '<span class="text-success">Frete calculado com sucesso!</span>';
            
            // Habilitar opções de frete (simular)
            shippingMethodSelect.disabled = false;
        }, 1000);
    });
    
    // Função para atualizar exibição de preços
    function updatePriceDisplay() {
        totalAmount = subtotal + shippingCost;
        
        // Formatar valores
        const formattedShipping = shippingCost.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        const formattedTotal = totalAmount.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        
        // Atualizar exibição
        shippingCostDisplay.textContent = formattedShipping;
        totalAmountDisplay.textContent = formattedTotal;
    }
    
    // Controles de quantidade
    document.querySelectorAll('.quantity-decrease').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentNode.querySelector('.quantity-input');
            const currentValue = parseInt(input.value);
            if (currentValue > 1) {
                input.value = currentValue - 1;
                // Submit do form ao mudar quantidade
                this.closest('form').submit();
            }
        });
    });
    
    document.querySelectorAll('.quantity-increase').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentNode.querySelector('.quantity-input');
            const currentValue = parseInt(input.value);
            if (currentValue < 99) {
                input.value = currentValue + 1;
                // Submit do form ao mudar quantidade
                this.closest('form').submit();
            }
        });
    });
    
    // Verificar alterações na quantidade manualmente
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            // Submit do form ao mudar quantidade
            this.closest('form').submit();
        });
    });
});
</script>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>