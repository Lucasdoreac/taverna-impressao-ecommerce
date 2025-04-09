<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-header bg-primary text-white">
                    <h1 class="h4 mb-0">Escolha a Forma de Pagamento</h1>
                </div>

                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="bi bi-info-circle-fill fs-3 text-primary"></i>
                            </div>
                            <div class="ms-3">
                                <p class="mb-1"><strong>Pedido #<?= htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') ?></strong></p>
                                <p class="mb-0">Valor total: <strong><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($total, 2, ',', '.') ?></strong></p>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>pagamento/processar" method="post" id="payment-method-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8') ?>">
                        
                        <div class="mb-4">
                            <h5 class="mb-3">Selecione como deseja pagar:</h5>
                            
                            <?php if (empty($paymentMethods)): ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Não há métodos de pagamento disponíveis no momento. Por favor, tente novamente mais tarde.
                                </div>
                            <?php else: ?>
                                <div class="payment-methods-list">
                                    <?php foreach ($paymentMethods as $method): ?>
                                        <?php if (!isset($method['active']) || !$method['active']) continue; ?>
                                        
                                        <div class="payment-method-option mb-3">
                                            <div class="form-check payment-method-card">
                                                <input class="form-check-input" type="radio" name="payment_method" 
                                                       id="payment-<?= htmlspecialchars($method['id'], ENT_QUOTES, 'UTF-8') ?>" 
                                                       value="<?= htmlspecialchars($method['id'], ENT_QUOTES, 'UTF-8') ?>" required>
                                                <label class="form-check-label payment-method-label" 
                                                       for="payment-<?= htmlspecialchars($method['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                    
                                                    <div class="d-flex align-items-center">
                                                        <?php if (isset($method['icon']) && !empty($method['icon'])): ?>
                                                            <div class="payment-icon me-3">
                                                                <i class="bi bi-<?= htmlspecialchars($method['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <div>
                                                            <span class="payment-method-name"><?= htmlspecialchars($method['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                            
                                                            <?php if ($method['id'] === 'credit_card'): ?>
                                                                <div class="payment-method-detail small text-muted mt-1">
                                                                    Aceitamos Visa, Mastercard, American Express e outros
                                                                </div>
                                                            <?php elseif ($method['id'] === 'pix'): ?>
                                                                <div class="payment-method-detail small text-muted mt-1">
                                                                    Pagamento instantâneo 24h
                                                                </div>
                                                            <?php elseif ($method['id'] === 'boleto'): ?>
                                                                <div class="payment-method-detail small text-muted mt-1">
                                                                    Processamento em até 3 dias úteis
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="alert alert-secondary mb-4">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-shield-lock fs-2 me-3 text-primary"></i>
                                <div>
                                    <h5 class="alert-heading mb-1">Pagamento 100% Seguro</h5>
                                    <p class="mb-0 small">Seus dados são criptografados com segurança e não armazenamos informações de pagamento.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>minha-conta/pedido/<?= htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Voltar ao Pedido
                            </a>
                            
                            <button type="submit" class="btn btn-primary" id="proceed-button">
                                Continuar <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-method-card {
    padding: 16px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.payment-method-card:hover {
    border-color: #b3d7ff;
    background-color: #f8f9fa;
}

.form-check-input:checked ~ .payment-method-label .payment-method-card {
    border-color: #0d6efd;
    background-color: #f0f7ff;
}

.payment-method-name {
    font-weight: 600;
    font-size: 1.05rem;
}

.payment-icon {
    font-size: 1.8rem;
    color: #0d6efd;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('payment-method-form');
    const proceedButton = document.getElementById('proceed-button');
    
    // Validar formulário antes de enviar
    form.addEventListener('submit', function(e) {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        
        if (!selectedMethod) {
            e.preventDefault();
            alert('Por favor, selecione um método de pagamento.');
            return;
        }
        
        // Desabilitar botão para evitar múltiplos envios
        proceedButton.disabled = true;
        proceedButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processando...';
    });
    
    // Estilização para opções de pagamento
    const paymentOptions = document.querySelectorAll('.payment-method-option');
    
    paymentOptions.forEach(option => {
        const radio = option.querySelector('input[type="radio"]');
        const label = option.querySelector('label');
        
        label.addEventListener('click', function() {
            // Simular clique no radio button
            radio.checked = true;
            
            // Disparar evento change para atualizar estilos
            const event = new Event('change');
            radio.dispatchEvent(event);
        });
        
        radio.addEventListener('change', function() {
            // Atualizar estilos baseados na seleção
            paymentOptions.forEach(opt => {
                opt.querySelector('.payment-method-card').classList.remove('border-primary', 'bg-light');
            });
            
            if (this.checked) {
                option.querySelector('.payment-method-card').classList.add('border-primary', 'bg-light');
            }
        });
    });
});
</script>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>