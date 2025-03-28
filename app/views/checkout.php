<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <h1 class="h2 mb-4">Finalizar Compra</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <form action="<?= BASE_URL ?>checkout/finalizar" method="post" id="checkout-form">
        <div class="row">
            <!-- Formulário de Checkout -->
            <div class="col-lg-8">
                <!-- Endereço de Entrega -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Endereço de Entrega</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($addresses)): ?>
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Você ainda não tem endereços cadastrados.
                        </div>
                        
                        <div id="new-address-form">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="address" class="form-label">Endereço</label>
                                    <input type="text" class="form-control" id="address" name="address" required>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="number" class="form-label">Número</label>
                                    <input type="text" class="form-control" id="number" name="number" required>
                                </div>
                                
                                <div class="col-md-8">
                                    <label for="complement" class="form-label">Complemento</label>
                                    <input type="text" class="form-control" id="complement" name="complement">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="neighborhood" class="form-label">Bairro</label>
                                    <input type="text" class="form-control" id="neighborhood" name="neighborhood" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="city" class="form-label">Cidade</label>
                                    <input type="text" class="form-control" id="city" name="city" required>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="state" class="form-label">Estado</label>
                                    <select class="form-select" id="state" name="state" required>
                                        <option value="">Selecione...</option>
                                        <option value="AC">Acre</option>
                                        <option value="AL">Alagoas</option>
                                        <option value="AP">Amapá</option>
                                        <option value="AM">Amazonas</option>
                                        <option value="BA">Bahia</option>
                                        <option value="CE">Ceará</option>
                                        <option value="DF">Distrito Federal</option>
                                        <option value="ES">Espírito Santo</option>
                                        <option value="GO">Goiás</option>
                                        <option value="MA">Maranhão</option>
                                        <option value="MT">Mato Grosso</option>
                                        <option value="MS">Mato Grosso do Sul</option>
                                        <option value="MG">Minas Gerais</option>
                                        <option value="PA">Pará</option>
                                        <option value="PB">Paraíba</option>
                                        <option value="PR">Paraná</option>
                                        <option value="PE">Pernambuco</option>
                                        <option value="PI">Piauí</option>
                                        <option value="RJ">Rio de Janeiro</option>
                                        <option value="RN">Rio Grande do Norte</option>
                                        <option value="RS">Rio Grande do Sul</option>
                                        <option value="RO">Rondônia</option>
                                        <option value="RR">Roraima</option>
                                        <option value="SC">Santa Catarina</option>
                                        <option value="SP">São Paulo</option>
                                        <option value="SE">Sergipe</option>
                                        <option value="TO">Tocantins</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="zipcode" class="form-label">CEP</label>
                                    <input type="text" class="form-control" id="zipcode" name="zipcode" required>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="save_address" name="save_address" value="1" checked>
                                        <label class="form-check-label" for="save_address">
                                            Salvar como endereço padrão
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php else: ?>
                        <div class="mb-3">
                            <?php foreach ($addresses as $address): ?>
                            <div class="form-check mb-3 border p-3 rounded <?= $address['is_default'] ? 'border-primary' : '' ?>">
                                <input class="form-check-input" type="radio" name="shipping_address_id" id="address-<?= $address['id'] ?>" value="<?= $address['id'] ?>" <?= $address['is_default'] ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="address-<?= $address['id'] ?>">
                                    <strong><?= $address['address'] ?>, <?= $address['number'] ?></strong>
                                    <?= $address['complement'] ? ' - ' . $address['complement'] : '' ?><br>
                                    <?= $address['neighborhood'] ?>, <?= $address['city'] ?> - <?= $address['state'] ?><br>
                                    CEP: <?= $address['zipcode'] ?>
                                    <?php if ($address['is_default']): ?>
                                    <span class="badge bg-primary ms-2">Endereço padrão</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#new-address-form">
                            <i class="bi bi-plus-circle"></i> Adicionar novo endereço
                        </button>
                        
                        <div class="collapse mt-3" id="new-address-form">
                            <div class="card card-body">
                                <h6 class="mb-3">Novo Endereço</h6>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="address" class="form-label">Endereço</label>
                                        <input type="text" class="form-control" id="address" name="address">
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="number" class="form-label">Número</label>
                                        <input type="text" class="form-control" id="number" name="number">
                                    </div>
                                    
                                    <div class="col-md-8">
                                        <label for="complement" class="form-label">Complemento</label>
                                        <input type="text" class="form-control" id="complement" name="complement">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="neighborhood" class="form-label">Bairro</label>
                                        <input type="text" class="form-control" id="neighborhood" name="neighborhood">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="city" class="form-label">Cidade</label>
                                        <input type="text" class="form-control" id="city" name="city">
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="state" class="form-label">Estado</label>
                                        <select class="form-select" id="state" name="state">
                                            <option value="">Selecione...</option>
                                            <option value="AC">Acre</option>
                                            <option value="AL">Alagoas</option>
                                            <option value="AP">Amapá</option>
                                            <option value="AM">Amazonas</option>
                                            <option value="BA">Bahia</option>
                                            <option value="CE">Ceará</option>
                                            <option value="DF">Distrito Federal</option>
                                            <option value="ES">Espírito Santo</option>
                                            <option value="GO">Goiás</option>
                                            <option value="MA">Maranhão</option>
                                            <option value="MT">Mato Grosso</option>
                                            <option value="MS">Mato Grosso do Sul</option>
                                            <option value="MG">Minas Gerais</option>
                                            <option value="PA">Pará</option>
                                            <option value="PB">Paraíba</option>
                                            <option value="PR">Paraná</option>
                                            <option value="PE">Pernambuco</option>
                                            <option value="PI">Piauí</option>
                                            <option value="RJ">Rio de Janeiro</option>
                                            <option value="RN">Rio Grande do Norte</option>
                                            <option value="RS">Rio Grande do Sul</option>
                                            <option value="RO">Rondônia</option>
                                            <option value="RR">Roraima</option>
                                            <option value="SC">Santa Catarina</option>
                                            <option value="SP">São Paulo</option>
                                            <option value="SE">Sergipe</option>
                                            <option value="TO">Tocantins</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="zipcode" class="form-label">CEP</label>
                                        <input type="text" class="form-control" id="zipcode" name="zipcode">
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" id="save_address" name="save_address" value="1">
                                            <label class="form-check-label" for="save_address">
                                                Salvar como endereço padrão
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Método de Envio -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Método de Envio</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($shipping_methods)): ?>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Nenhum método de envio disponível no momento.
                        </div>
                        <?php else: ?>
                        <div class="mb-3">
                            <?php foreach ($shipping_methods as $method): ?>
                            <div class="form-check mb-3 border p-3 rounded">
                                <input class="form-check-input shipping-method" type="radio" name="shipping_method" id="shipping-<?= $method['name'] ?>" value="<?= $method['name'] ?>" data-price="<?= $method['price'] ?>" required>
                                <label class="form-check-label d-flex justify-content-between align-items-center" for="shipping-<?= $method['name'] ?>">
                                    <span><?= $method['name'] ?></span>
                                    <span class="fw-semibold">R$ <?= number_format($method['price'], 2, ',', '.') ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="shipping_cost" id="shipping-cost-input" value="0">
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Método de Pagamento -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Método de Pagamento</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($payment_methods)): ?>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Nenhum método de pagamento disponível no momento.
                        </div>
                        <?php else: ?>
                        <div class="mb-3">
                            <?php foreach ($payment_methods as $method): ?>
                            <?php if (!$method['active']) continue; ?>
                            <div class="form-check mb-3 border p-3 rounded">
                                <input class="form-check-input payment-method" type="radio" name="payment_method" id="payment-<?= $method['id'] ?>" value="<?= $method['id'] ?>" required>
                                <label class="form-check-label" for="payment-<?= $method['id'] ?>">
                                    <span><?= $method['name'] ?></span>
                                </label>
                                
                                <!-- Formulários de pagamento específicos -->
                                <div class="payment-details mt-3 d-none" id="payment-details-<?= $method['id'] ?>">
                                    <?php if ($method['id'] === 'credit_card'): ?>
                                    <!-- Formulário de Cartão de Crédito -->
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="card_number" class="form-label">Número do Cartão</label>
                                            <input type="text" class="form-control" id="card_number" name="card_number" placeholder="0000 0000 0000 0000">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="card_name" class="form-label">Nome no Cartão</label>
                                            <input type="text" class="form-control" id="card_name" name="card_name" placeholder="Nome como está no cartão">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="card_expiry" class="form-label">Validade</label>
                                            <input type="text" class="form-control" id="card_expiry" name="card_expiry" placeholder="MM/AA">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="card_cvv" class="form-label">CVV</label>
                                            <input type="text" class="form-control" id="card_cvv" name="card_cvv" placeholder="000">
                                        </div>
                                        <div class="col-12">
                                            <label for="card_installments" class="form-label">Parcelas</label>
                                            <select class="form-select" id="card_installments" name="card_installments">
                                                <option value="1">1x de R$ <?= number_format($total, 2, ',', '.') ?> sem juros</option>
                                                <?php for ($i = 2; $i <= 6; $i++): ?>
                                                <option value="<?= $i ?>"><?= $i ?>x de R$ <?= number_format($total / $i, 2, ',', '.') ?> sem juros</option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <?php elseif ($method['id'] === 'boleto'): ?>
                                    <!-- Informações de Boleto -->
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        O boleto será gerado após a confirmação do pedido e terá vencimento em 3 dias úteis. 
                                        O pedido será processado somente após a confirmação do pagamento.
                                    </div>
                                    
                                    <?php elseif ($method['id'] === 'pix'): ?>
                                    <!-- Informações de PIX -->
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        O QR Code do PIX será gerado após a confirmação do pedido. 
                                        O pedido será processado imediatamente após a confirmação do pagamento.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Resumo do Pedido -->
            <div class="col-lg-4">
                <div class="card mb-4 checkout-summary">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Resumo do Pedido</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal (<?= count($cart_items) ?> <?= count($cart_items) > 1 ? 'itens' : 'item' ?>)</span>
                            <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Frete</span>
                            <span id="shipping-display">R$ 0,00</span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <span class="h5">Total</span>
                            <span class="h5" id="total-display">R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100" id="place-order-btn">
                            Finalizar Pedido
                        </button>
                        
                        <div class="mt-3 small text-center">
                            <p class="mb-1">
                                <i class="bi bi-shield-lock me-1"></i>
                                Pagamento 100% seguro
                            </p>
                            <img src="<?= BASE_URL ?>assets/images/payment-methods.png" alt="Métodos de Pagamento" height="24" class="mt-2">
                        </div>
                    </div>
                </div>
                
                <!-- Itens do Carrinho -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Itens no Carrinho</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($cart_items as $item): ?>
                            <li class="list-group-item py-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <?php if ($item['image']): ?>
                                        <img src="<?= BASE_URL ?>uploads/products/<?= $item['image'] ?>" alt="<?= $item['name'] ?>" width="60" height="60" class="img-thumbnail">
                                        <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1"><?= $item['name'] ?></h6>
                                        <p class="mb-1 small text-muted">
                                            Quantidade: <?= $item['quantity'] ?>
                                            <?php if (!empty($item['customization'])): ?>
                                            <span class="ms-2 badge bg-info">Personalizado</span>
                                            <?php endif; ?>
                                        </p>
                                        <span class="fw-semibold">R$ <?= number_format($item['total'], 2, ',', '.') ?></span>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="<?= BASE_URL ?>carrinho" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-left me-1"></i> Voltar ao Carrinho
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variáveis para cálculos
    const subtotal = <?= $subtotal ?>;
    let shippingCost = 0;
    let totalAmount = subtotal;
    
    // Elementos do DOM
    const shippingMethodInputs = document.querySelectorAll('.shipping-method');
    const shippingDisplay = document.getElementById('shipping-display');
    const shippingCostInput = document.getElementById('shipping-cost-input');
    const totalDisplay = document.getElementById('total-display');
    const paymentMethodInputs = document.querySelectorAll('.payment-method');
    const checkoutForm = document.getElementById('checkout-form');
    const placeOrderBtn = document.getElementById('place-order-btn');
    
    // Máscara para inputs
    const zipcode = document.getElementById('zipcode');
    if (zipcode) {
        zipcode.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 5) {
                value = value.substring(0, 5) + '-' + value.substring(5, 8);
            }
            e.target.value = value;
        });
    }
    
    // Máscaras para campos de cartão
    const cardNumber = document.getElementById('card_number');
    if (cardNumber) {
        cardNumber.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            let formattedValue = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            e.target.value = formattedValue;
        });
    }
    
    const cardExpiry = document.getElementById('card_expiry');
    if (cardExpiry) {
        cardExpiry.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });
    }
    
    const cardCvv = document.getElementById('card_cvv');
    if (cardCvv) {
        cardCvv.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 3);
        });
    }
    
    // Atualizar preços ao selecionar método de envio
    shippingMethodInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.checked) {
                shippingCost = parseFloat(this.dataset.price);
                shippingCostInput.value = shippingCost;
                updatePriceDisplay();
            }
        });
    });
    
    // Mostrar/ocultar detalhes de pagamento
    paymentMethodInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Esconder todos os detalhes
            document.querySelectorAll('.payment-details').forEach(detail => {
                detail.classList.add('d-none');
            });
            
            // Mostrar detalhes do método selecionado
            if (this.checked) {
                const detailsElement = document.getElementById('payment-details-' + this.value);
                if (detailsElement) {
                    detailsElement.classList.remove('d-none');
                }
            }
        });
    });
    
    // Verificar formulário antes de enviar
    checkoutForm.addEventListener('submit', function(e) {
        // Verificar se um endereço foi selecionado
        const addressSelected = document.querySelector('input[name="shipping_address_id"]:checked');
        
        if (!addressSelected && !document.getElementById('address').value) {
            e.preventDefault();
            alert('Por favor, selecione ou informe um endereço de entrega.');
            return;
        }
        
        // Verificar se um método de envio foi selecionado
        const shippingSelected = document.querySelector('input[name="shipping_method"]:checked');
        if (!shippingSelected) {
            e.preventDefault();
            alert('Por favor, selecione um método de envio.');
            return;
        }
        
        // Verificar se um método de pagamento foi selecionado
        const paymentSelected = document.querySelector('input[name="payment_method"]:checked');
        if (!paymentSelected) {
            e.preventDefault();
            alert('Por favor, selecione um método de pagamento.');
            return;
        }
        
        // Validar campos específicos de pagamento
        if (paymentSelected.value === 'credit_card') {
            const cardNumber = document.getElementById('card_number');
            const cardName = document.getElementById('card_name');
            const cardExpiry = document.getElementById('card_expiry');
            const cardCvv = document.getElementById('card_cvv');
            
            if (!cardNumber.value || !cardName.value || !cardExpiry.value || !cardCvv.value) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos do cartão de crédito.');
                return;
            }
        }
    });
    
    // Função para atualizar exibição de preços
    function updatePriceDisplay() {
        totalAmount = subtotal + shippingCost;
        
        // Formatar valores
        const formattedShipping = shippingCost.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        const formattedTotal = totalAmount.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        
        // Atualizar exibição
        shippingDisplay.textContent = formattedShipping;
        totalDisplay.textContent = formattedTotal;
        
        // Atualizar opções de parcelamento, se existir
        const installmentsSelect = document.getElementById('card_installments');
        if (installmentsSelect) {
            installmentsSelect.innerHTML = '';
            
            // Adicionar opções de parcelamento
            for (let i = 1; i <= 6; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = `${i}x de R$ ${(totalAmount / i).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} sem juros`;
                installmentsSelect.appendChild(option);
            }
        }
    }
});
</script>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>