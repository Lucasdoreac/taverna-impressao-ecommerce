<?php
/**
 * Página de checkout com PayPal
 * 
 * @var int $orderId ID do pedido atual
 * @var string $orderNumber Número do pedido
 * @var float $total Valor total do pedido
 * @var string $clientId ID de cliente do PayPal
 * @var boolean $isSandbox Se está em ambiente sandbox
 * @var string $currency Código da moeda (BRL, USD, etc)
 * @var string $returnUrl URL de retorno após pagamento
 * @var string $cancelUrl URL de cancelamento
 * @var string $csrf_token Token CSRF para segurança
 */

// Garantir definição das variáveis necessárias
$clientId = $clientId ?? '';
$isSandbox = $isSandbox ?? true;
$currency = $currency ?? 'BRL';
$returnUrl = $returnUrl ?? (BASE_URL . 'payment/callback/paypal-success');
$cancelUrl = $cancelUrl ?? (BASE_URL . 'payment/callback/paypal-cancel');

// Definir ambiente
$environment = $isSandbox ? 'sandbox' : 'production';

// Formatação segura de valores
$formattedTotal = number_format($total, 2, '.', '');
$orderNumberSafe = htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8');
$orderIdSafe = (int)$orderId;
?>

<!-- Breadcrumb e Título da Página -->
<div class="container mt-4 mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>meus-pedidos">Meus Pedidos</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>pedido/detalhes/<?= $orderIdSafe ?>">Pedido #<?= $orderNumberSafe ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Pagamento via PayPal</li>
        </ol>
    </nav>

    <h1 class="h3 mb-4">Pagamento do Pedido #<?= $orderNumberSafe ?> via PayPal</h1>
</div>

<!-- Instruções e Botão de Pagamento -->
<div class="container mb-5">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Instruções para Pagamento</h5>
                </div>
                <div class="card-body">
                    <p>Você está prestes a finalizar o pagamento do seu pedido através do PayPal. Siga os passos abaixo:</p>
                    
                    <ol>
                        <li>Clique no botão "Pagar com PayPal" abaixo</li>
                        <li>Você será redirecionado para o ambiente seguro do PayPal</li>
                        <li>Faça login na sua conta PayPal ou pague como visitante</li>
                        <li>Confirme o pagamento</li>
                        <li>Você será redirecionado de volta à nossa loja automaticamente</li>
                    </ol>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> O processamento do pagamento ocorre em ambiente seguro do PayPal. Seus dados financeiros não são armazenados em nossos servidores.
                    </div>
                    
                    <!-- Container para o botão do PayPal -->
                    <div id="paypal-button-container" class="mt-4 mb-3"></div>
                    
                    <!-- Link para cancelar -->
                    <div class="text-center mt-3">
                        <a href="<?= BASE_URL ?>pedido/detalhes/<?= $orderIdSafe ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar para detalhes do pedido
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Resumo do Pedido -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Resumo do Pedido</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <span>Número do Pedido:</span>
                        <strong><?= $orderNumberSafe ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Total a Pagar:</span>
                        <strong class="text-primary" id="payment-total">R$ <?= number_format($total, 2, ',', '.') ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Método de Pagamento:</span>
                        <span><img src="<?= BASE_URL ?>assets/img/paypal-logo.png" alt="PayPal" height="20"> PayPal</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script do PayPal SDK -->
<script src="https://www.paypal.com/sdk/js?client-id=<?= $clientId ?>&currency=<?= $currency ?>&intent=capture&commit=true"></script>

<!-- Script de integração -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // CSRF Token para segurança
    const csrfToken = "<?= $csrf_token ?>";
    
    // Dados do pedido
    const orderId = <?= $orderIdSafe ?>;
    const orderTotal = <?= $formattedTotal ?>;
    
    // Inicializar botões do PayPal
    paypal.Buttons({
        // Estilo dos botões
        style: {
            layout: 'vertical',
            color: 'blue',
            shape: 'rect',
            label: 'pay'
        },
        
        // Criar ordem PayPal
        createOrder: function(data, actions) {
            return fetch('<?= BASE_URL ?>payment/create-paypal-order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    order_id: orderId,
                    payment_method: 'paypal'
                })
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Erro na comunicação com o servidor');
                }
                return response.json();
            })
            .then(function(responseData) {
                if (!responseData.success) {
                    throw new Error(responseData.error_message || 'Erro ao criar ordem de pagamento');
                }
                
                // Retornar ID da transação para o PayPal
                return responseData.transaction_id;
            })
            .catch(function(error) {
                // Mostrar mensagem de erro ao usuário
                alert('Erro: ' + error.message);
                console.error('Erro ao criar ordem PayPal:', error);
                
                // Registrar erro para análise
                logPaymentError('create_order_error', error.message);
                
                // Redirecionar para página de erro após delay
                setTimeout(function() {
                    window.location.href = '<?= BASE_URL ?>pedido/falha/' + orderId;
                }, 1500);
            });
        },
        
        // Quando o pagamento é aprovado
        onApprove: function(data, actions) {
            // Mostrar loading state
            showProcessingMessage();
            
            // Atualizar pedido no sistema
            return fetch('<?= BASE_URL ?>payment/capture-paypal-order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    order_id: orderId,
                    paypal_order_id: data.orderID,
                    payment_method: 'paypal'
                })
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Erro na comunicação com o servidor');
                }
                return response.json();
            })
            .then(function(responseData) {
                if (!responseData.success) {
                    throw new Error(responseData.error_message || 'Erro ao capturar pagamento');
                }
                
                // Redirecionar para página de sucesso
                window.location.href = responseData.redirect_url || '<?= BASE_URL ?>pedido/sucesso/<?= $orderNumberSafe ?>';
            })
            .catch(function(error) {
                // Registrar erro para análise
                logPaymentError('capture_error', error.message);
                
                // Mostrar mensagem de erro
                alert('Erro ao finalizar pagamento: ' + error.message);
                
                // Redirecionar para verificação manual
                window.location.href = '<?= BASE_URL ?>pedido/status/<?= $orderNumberSafe ?>';
            });
        },
        
        // Quando o usuário cancela
        onCancel: function(data) {
            console.log('Pagamento cancelado pelo usuário');
            
            // Opcional: Notificar o servidor do cancelamento
            fetch('<?= BASE_URL ?>payment/cancel-paypal-order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    order_id: orderId,
                    reason: 'user_cancelled'
                })
            });
            
            // Redirecionar após um breve delay
            setTimeout(function() {
                window.location.href = '<?= BASE_URL ?>pedido/detalhes/' + orderId;
            }, 1000);
        },
        
        // Quando ocorre erro
        onError: function(err) {
            console.error('Erro no PayPal:', err);
            
            // Registrar erro para análise
            logPaymentError('paypal_sdk_error', err.message || JSON.stringify(err));
            
            // Mostrar mensagem ao usuário
            alert('Ocorreu um erro no processamento do pagamento. Por favor, tente novamente ou use outro método de pagamento.');
            
            // Opcional: Recarregar página ou redirecionar
            setTimeout(function() {
                window.location.reload();
            }, 2000);
        }
    }).render('#paypal-button-container');
    
    // Função para mostrar mensagem de processamento
    function showProcessingMessage() {
        const container = document.getElementById('paypal-button-container');
        container.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Processando pagamento, não feche esta janela...</p></div>';
    }
    
    // Função para registrar erros de pagamento
    function logPaymentError(errorType, errorMessage) {
        fetch('<?= BASE_URL ?>payment/log-error', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                order_id: orderId,
                payment_method: 'paypal',
                error_type: errorType,
                error_message: errorMessage
            })
        }).catch(console.error);
    }
});
</script>
