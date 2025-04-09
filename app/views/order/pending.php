<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-header bg-warning text-dark">
                    <h1 class="h4 mb-0"><i class="bi bi-clock-history me-2"></i> Pagamento Pendente</h1>
                </div>
                
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="pending-icon mb-3">
                            <i class="bi bi-hourglass-split text-warning" style="font-size: 4rem;"></i>
                        </div>
                        
                        <h2 class="h4">Aguardando confirmação de pagamento</h2>
                        <p class="lead mb-0">Pedido #<?= htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') ?></p>
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
                                        <span class="badge bg-warning text-dark">Pagamento Pendente</span>
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
                                    <div class="detail-value fw-bold">R$ <?= number_format($order['total'], 2, ',', '.') ?></div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="detail-item">
                                    <div class="detail-label text-muted">Forma de pagamento</div>
                                    <div class="detail-value"><?= htmlspecialchars($order['payment_method_display'] ?? $order['payment_method'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="payment-status-box alert alert-warning mb-4">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="bi bi-info-circle-fill fs-4 me-2"></i>
                            </div>
                            <div>
                                <h4 class="alert-heading h5">Informações sobre o pagamento</h4>
                                
                                <?php if ($paymentMethod === 'pix'): ?>
                                <p>Seu pagamento via PIX está pendente de confirmação.</p>
                                <p>Caso ainda não tenha realizado o pagamento, você pode acessar novamente o QR Code através do link abaixo.</p>
                                
                                <?php elseif ($paymentMethod === 'boleto'): ?>
                                <p>Seu pagamento via Boleto está pendente de confirmação.</p>
                                <p>Caso ainda não tenha realizado o pagamento, você pode acessar novamente o boleto através do link abaixo.</p>
                                <p>A compensação do boleto pode levar até 3 dias úteis após o pagamento.</p>
                                
                                <?php else: ?>
                                <p>Seu pagamento está em processamento.</p>
                                <p>Isso pode levar alguns instantes. Você receberá uma notificação assim que for confirmado.</p>
                                <?php endif; ?>
                                
                                <p class="mb-0">O status do seu pedido será atualizado automaticamente após a confirmação do pagamento.</p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($paymentMethod === 'pix'): ?>
                    <div class="text-center mb-4">
                        <a href="<?= BASE_URL ?>pagamento/pix/<?= (int)$order['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-qr-code"></i> Acessar QR Code PIX
                        </a>
                    </div>
                    <?php elseif ($paymentMethod === 'boleto'): ?>
                    <div class="text-center mb-4">
                        <a href="<?= BASE_URL ?>pagamento/boleto/<?= (int)$order['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-receipt"></i> Acessar Boleto
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div id="status-check-section" class="bg-light rounded p-3 mb-4 text-center">
                        <p class="mb-2">Verificar status do pagamento</p>
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <div id="spinner" class="spinner-border spinner-border-sm text-primary me-2 d-none" role="status">
                                <span class="visually-hidden">Verificando...</span>
                            </div>
                            <span id="status-text">Clique no botão abaixo para verificar o status atual</span>
                        </div>
                        
                        <form id="check-status-form" action="<?= BASE_URL ?>pagamento/verificar-status" method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                            <button type="button" id="check-status-btn" class="btn btn-sm btn-primary">
                                <i class="bi bi-arrow-clockwise"></i> Verificar Status
                            </button>
                        </form>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkStatusBtn = document.getElementById('check-status-btn');
    const checkStatusForm = document.getElementById('check-status-form');
    const statusText = document.getElementById('status-text');
    const spinner = document.getElementById('spinner');
    
    if (checkStatusBtn && checkStatusForm) {
        checkStatusBtn.addEventListener('click', function() {
            // Mostrar spinner e desabilitar botão
            spinner.classList.remove('d-none');
            checkStatusBtn.disabled = true;
            statusText.innerText = 'Verificando status...';
            
            // Preparar dados do form
            const formData = new FormData(checkStatusForm);
            
            // Fazer requisição AJAX
            fetch('<?= BASE_URL ?>pagamento/verificar-status', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Atualizar status
                if (data.success) {
                    const status = data.status || 'pending';
                    
                    statusText.innerText = 'Status atual: ' + formatStatus(status);
                    
                    // Se pagamento foi aprovado, redirecionar
                    if (status === 'approved' || status === 'authorized') {
                        statusText.innerText = 'Pagamento aprovado! Redirecionando...';
                        
                        setTimeout(function() {
                            window.location.href = data.redirect_url || '<?= BASE_URL ?>pedido/sucesso/<?= htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') ?>';
                        }, 1500);
                    }
                    // Se status mudou e requer refresh
                    else if (data.needs_refresh) {
                        statusText.innerText = 'Status atualizado! Atualizando página...';
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    statusText.innerText = 'Erro ao verificar status: ' + (data.message || 'Tente novamente');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                statusText.innerText = 'Erro ao verificar status. Tente novamente.';
            })
            .finally(() => {
                // Restaurar botão e esconder spinner
                setTimeout(function() {
                    spinner.classList.add('d-none');
                    checkStatusBtn.disabled = false;
                }, 1000);
            });
        });
        
        // Verificação automática periódica (a cada 30 segundos)
        const checkInterval = 30000;
        let checkTimer = setInterval(function() {
            if (document.visibilityState === 'visible') {
                checkStatusBtn.click();
            }
        }, checkInterval);
        
        // Limpar timer quando usuário sair da página
        window.addEventListener('beforeunload', function() {
            clearInterval(checkTimer);
        });
        
        // Verificar status inicial após 3 segundos
        setTimeout(function() {
            checkStatusBtn.click();
        }, 3000);
    }
    
    // Formatar status para exibição
    function formatStatus(status) {
        const statusMap = {
            'pending': 'Pendente',
            'in_process': 'Em processamento',
            'approved': 'Aprovado',
            'authorized': 'Autorizado',
            'cancelled': 'Cancelado',
            'refunded': 'Reembolsado',
            'charged_back': 'Estornado',
            'failed': 'Falhou',
            'rejected': 'Rejeitado'
        };
        
        return statusMap[status] || status;
    }
});
</script>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>