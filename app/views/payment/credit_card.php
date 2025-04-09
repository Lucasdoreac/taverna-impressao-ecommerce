<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-header bg-primary text-white">
                    <h1 class="h4 mb-0">Pagamento com Cartão de Crédito</h1>
                </div>

                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <p class="mb-1"><strong>Pedido #<?= htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') ?></strong></p>
                        <p class="mb-0">Valor: <strong><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($total, 2, ',', '.') ?></strong></p>
                    </div>

                    <form id="payment-form" action="<?= BASE_URL ?>pagamento/processar" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="payment_method" value="credit_card">
                        <input type="hidden" name="card_token" id="card_token">
                        <input type="hidden" name="card_brand" id="card_brand">
                        
                        <div class="mb-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="card_number" class="form-label">Número do Cartão</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="card_number" placeholder="0000 0000 0000 0000" maxlength="19" autocomplete="cc-number" required>
                                        <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                                    </div>
                                    <div class="invalid-feedback" id="card_number_error"></div>
                                </div>
                                
                                <div class="col-12">
                                    <label for="card_holder" class="form-label">Nome no Cartão</label>
                                    <input type="text" class="form-control" id="card_holder" placeholder="Como está no cartão" autocomplete="cc-name" required>
                                    <div class="invalid-feedback" id="card_holder_error"></div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="card_expiry" class="form-label">Data de Validade</label>
                                    <input type="text" class="form-control" id="card_expiry" placeholder="MM/AA" maxlength="5" autocomplete="cc-exp" required>
                                    <div class="invalid-feedback" id="card_expiry_error"></div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="card_cvv" class="form-label">Código de Segurança (CVV)</label>
                                    <input type="text" class="form-control" id="card_cvv" placeholder="123" maxlength="4" autocomplete="cc-csc" required>
                                    <div class="invalid-feedback" id="card_cvv_error"></div>
                                </div>
                                
                                <div class="col-12">
                                    <label for="installments" class="form-label">Parcelas</label>
                                    <select class="form-select" id="installments" name="installments" required>
                                        <option value="">Selecione</option>
                                        <option value="1">1x de <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($total, 2, ',', '.') ?> sem juros</option>
                                        <option value="2">2x de <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($total/2, 2, ',', '.') ?> sem juros</option>
                                        <option value="3">3x de <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($total/3, 2, ',', '.') ?> sem juros</option>
                                        <option value="4">4x de <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($total/4, 2, ',', '.') ?> sem juros</option>
                                        <option value="5">5x de <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($total/5, 2, ',', '.') ?> sem juros</option>
                                        <option value="6">6x de <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($total/6, 2, ',', '.') ?> sem juros</option>
                                    </select>
                                    <div class="invalid-feedback" id="installments_error"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-secondary mb-4">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-shield-lock fs-2 me-3 text-primary"></i>
                                <div>
                                    <h5 class="alert-heading mb-1">Pagamento 100% Seguro</h5>
                                    <p class="mb-0 small">Seus dados são criptografados com segurança e não armazenamos informações do seu cartão.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>pedido/detalhes/<?= htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Voltar
                            </a>
                            <div id="submit-button-container">
                                <button type="submit" class="btn btn-primary" id="pay-button">
                                    <i class="bi bi-lock"></i> Pagar <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($total, 2, ',', '.') ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MercadoPago Script - Carregado dinamicamente com configurações seguras -->
<script id="mp-sdk-script">
document.addEventListener('DOMContentLoaded', function() {
    // Elementos do formulário
    const form = document.getElementById('payment-form');
    const cardNumber = document.getElementById('card_number');
    const cardHolder = document.getElementById('card_holder');
    const cardExpiry = document.getElementById('card_expiry');
    const cardCvv = document.getElementById('card_cvv');
    const installments = document.getElementById('installments');
    const cardToken = document.getElementById('card_token');
    const cardBrand = document.getElementById('card_brand');
    const payButton = document.getElementById('pay-button');
    
    // Aplicar máscaras nos campos
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
        
        // Detectar bandeira (implementação básica)
        const firstDigit = value.charAt(0);
        let brand = '';
        
        if (firstDigit === '4') {
            brand = 'visa';
        } else if (['51', '52', '53', '54', '55'].includes(value.substring(0, 2))) {
            brand = 'master';
        } else if (['34', '37'].includes(value.substring(0, 2))) {
            brand = 'amex';
        } else if (value.startsWith('6')) {
            brand = 'elo';
        }
        
        cardBrand.value = brand;
    });
    
    cardExpiry.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 0) {
            if (value.length <= 2) {
                e.target.value = value;
            } else {
                const month = value.substring(0, 2);
                const year = value.substring(2, 4);
                e.target.value = month + '/' + year;
            }
        }
    });
    
    cardCvv.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '');
    });
    
    // Carregar script do MercadoPago de maneira segura
    const loadMercadoPagoScript = () => {
        return new Promise((resolve, reject) => {
            // Verificar se já está carregado
            if (window.MercadoPago) {
                resolve(window.MercadoPago);
                return;
            }
            
            // Criar script
            const script = document.createElement('script');
            script.src = 'https://sdk.mercadopago.com/js/v2';
            script.integrity = 'sha384-GX/8/KgvPgQ2sRuYxXoiBfZ2LtSY7mY+/OfMP/4JH5r/FL7nrj/mu1PkmnmSgQ1U';
            script.crossOrigin = 'anonymous';
            
            script.onload = () => {
                resolve(window.MercadoPago);
            };
            
            script.onerror = () => {
                reject(new Error('Falha ao carregar script do MercadoPago'));
            };
            
            document.head.appendChild(script);
        });
    };
    
    // Inicializar MercadoPago e tokenizar cartão
    const initMercadoPago = async () => {
        try {
            const MercadoPago = await loadMercadoPagoScript();
            
            // Inicializar com chave pública
            const mp = new MercadoPago('<?= htmlspecialchars($publicKey, ENT_QUOTES, 'UTF-8') ?>', {
                locale: 'pt-BR'
            });
            
            // Adicionar handler de submit
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Validar formulário
                if (!validateForm()) {
                    return;
                }
                
                // Desabilitar botão de pagamento
                payButton.disabled = true;
                payButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processando...';
                
                try {
                    // Extrair mês e ano de validade
                    const expiryValue = cardExpiry.value.split('/');
                    const expiryMonth = expiryValue[0];
                    const expiryYear = '20' + expiryValue[1];
                    
                    // Criar objeto de identificação
                    const identification = {
                        type: 'CPF',
                        number: '00000000000' // Placeholder para CPF
                    };
                    
                    // Tokenizar o cartão
                    const cardData = {
                        card_number: cardNumber.value.replace(/\s/g, ''),
                        cardholder: {
                            name: cardHolder.value,
                            identification: identification
                        },
                        expiration_month: expiryMonth,
                        expiration_year: expiryYear,
                        security_code: cardCvv.value
                    };
                    
                    const response = await mp.createCardToken(cardData);
                    
                    if (response.id) {
                        // Guardar token do cartão no form
                        cardToken.value = response.id;
                        
                        // Enviar formulário
                        form.submit();
                    } else {
                        throw new Error('Não foi possível gerar o token do cartão');
                    }
                } catch (error) {
                    console.error('Erro ao processar pagamento:', error);
                    
                    // Exibir erro
                    alert('Ocorreu um erro ao processar o pagamento: ' + error.message);
                    
                    // Reativar botão
                    payButton.disabled = false;
                    payButton.innerHTML = '<i class="bi bi-lock"></i> Pagar <?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($total, 2, ',', '.') ?>';
                }
            });
            
        } catch (error) {
            console.error('Erro ao inicializar MercadoPago:', error);
            
            // Exibir mensagem de erro
            const errorAlert = document.createElement('div');
            errorAlert.className = 'alert alert-danger mt-3';
            errorAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i> Não foi possível carregar o processador de pagamentos. Por favor, tente novamente mais tarde.';
            
            form.appendChild(errorAlert);
            
            // Desabilitar botão de pagamento
            payButton.disabled = true;
        }
    };
    
    // Validar formulário
    const validateForm = () => {
        let isValid = true;
        
        // Validar número do cartão
        const cardNumberValue = cardNumber.value.replace(/\s/g, '');
        if (!cardNumberValue || cardNumberValue.length < 13 || cardNumberValue.length > 19) {
            showError(cardNumber, 'card_number_error', 'Número de cartão inválido');
            isValid = false;
        } else {
            hideError(cardNumber, 'card_number_error');
        }
        
        // Validar nome no cartão
        if (!cardHolder.value || cardHolder.value.length < 3) {
            showError(cardHolder, 'card_holder_error', 'Nome inválido');
            isValid = false;
        } else {
            hideError(cardHolder, 'card_holder_error');
        }
        
        // Validar validade
        const expiryPattern = /^\d{2}\/\d{2}$/;
        if (!cardExpiry.value || !expiryPattern.test(cardExpiry.value)) {
            showError(cardExpiry, 'card_expiry_error', 'Data de validade inválida');
            isValid = false;
        } else {
            const parts = cardExpiry.value.split('/');
            const month = parseInt(parts[0], 10);
            const year = parseInt('20' + parts[1], 10);
            
            const now = new Date();
            const currentYear = now.getFullYear();
            const currentMonth = now.getMonth() + 1;
            
            if (month < 1 || month > 12) {
                showError(cardExpiry, 'card_expiry_error', 'Mês inválido');
                isValid = false;
            } else if (year < currentYear || (year === currentYear && month < currentMonth)) {
                showError(cardExpiry, 'card_expiry_error', 'Cartão expirado');
                isValid = false;
            } else {
                hideError(cardExpiry, 'card_expiry_error');
            }
        }
        
        // Validar CVV
        if (!cardCvv.value || cardCvv.value.length < 3 || cardCvv.value.length > 4) {
            showError(cardCvv, 'card_cvv_error', 'Código de segurança inválido');
            isValid = false;
        } else {
            hideError(cardCvv, 'card_cvv_error');
        }
        
        // Validar parcelas
        if (!installments.value) {
            showError(installments, 'installments_error', 'Selecione o número de parcelas');
            isValid = false;
        } else {
            hideError(installments, 'installments_error');
        }
        
        return isValid;
    };
    
    // Mostrar erro
    const showError = (element, errorId, message) => {
        element.classList.add('is-invalid');
        document.getElementById(errorId).innerText = message;
    };
    
    // Esconder erro
    const hideError = (element, errorId) => {
        element.classList.remove('is-invalid');
        document.getElementById(errorId).innerText = '';
    };
    
    // Inicializar
    initMercadoPago();
});
</script>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>