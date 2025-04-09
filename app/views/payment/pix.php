<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-header bg-primary text-white">
                    <h1 class="h4 mb-0">Pagamento via PIX</h1>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <p class="mb-1"><strong>Pedido #<?= htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') ?></strong></p>
                        <p class="mb-0">Valor: <strong><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($total, 2, ',', '.') ?></strong></p>
                    </div>

                    <div class="row align-items-center mb-4">
                        <div class="col-md-6 mb-4 mb-md-0 text-center">
                            <?php if (!empty($qrCode)): ?>
                                <div class="qr-code-container bg-white p-4 rounded shadow-sm d-inline-block">
                                    <img src="data:image/png;base64,<?= htmlspecialchars($qrCode, ENT_QUOTES, 'UTF-8') ?>" 
                                         alt="QR Code de pagamento PIX" 
                                         class="img-fluid" id="pix-qrcode">
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    QR Code não disponível.
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h5 class="text-dark">Como pagar com PIX</h5>
                                <ol class="mb-0">
                                    <li>Abra o aplicativo do seu banco</li>
                                    <li>Busque a opção de pagamento por PIX</li>
                                    <li>Escaneie o QR Code ao lado ou copie e cole o código</li>
                                    <li>Confirme os dados e finalize o pagamento</li>
                                </ol>
                            </div>

                            <?php if (!empty($qrCodeText)): ?>
                                <div class="mb-3">
                                    <label for="pix-code" class="form-label fw-bold">Código PIX:</label>
                                    <div class="input-group">
                                        <input type="text" id="pix-code" class="form-control form-control-sm bg-light" 
                                               value="<?= htmlspecialchars($qrCodeText, ENT_QUOTES, 'UTF-8') ?>" readonly>
                                        <button class="btn btn-primary btn-sm" type="button" id="copy-pix-code">
                                            <i class="bi bi-clipboard"></i> Copiar
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($expiresAt)): ?>
                                <div class="alert alert-warning d-flex align-items-center mt-3 mb-0">
                                    <i class="bi bi-clock me-2 fs-4"></i>
                                    <div>
                                        <strong>Atenção:</strong> Este PIX expira em<br>
                                        <span class="fw-bold"><?= htmlspecialchars($expiresAt, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-4 text-center" id="status-section">
                        <h5 class="mb-3">Status do Pagamento</h5>
                        <div class="bg-light rounded p-3">
                            <p class="mb-2">Aguardando confirmação do pagamento.</p>
                            <div class="d-flex justify-content-center align-items-center mb-2">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                    <span class="visually-hidden">Verificando...</span>
                                </div>
                                <span id="status-text">Verificando pagamento...</span>
                            </div>
                            <p class="small text-muted mb-0">A página será atualizada automaticamente após a confirmação.</p>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>pedido/detalhes/<?= htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Voltar ao Pedido
                        </a>
                        <form action="<?= BASE_URL ?>pagamento/verificar-status" method="post" id="check-status-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="button" class="btn btn-primary" id="check-status-btn">
                                <i class="bi bi-arrow-clockwise"></i> Verificar Status
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copiar código PIX
    const copyBtn = document.getElementById('copy-pix-code');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const pixCodeInput = document.getElementById('pix-code');
            pixCodeInput.select();
            document.execCommand('copy');
            
            // Feedback visual
            copyBtn.innerHTML = '<i class="bi bi-check2"></i> Copiado';
            copyBtn.classList.remove('btn-primary');
            copyBtn.classList.add('btn-success');
            
            setTimeout(function() {
                copyBtn.innerHTML = '<i class="bi bi-clipboard"></i> Copiar';
                copyBtn.classList.remove('btn-success');
                copyBtn.classList.add('btn-primary');
            }, 2000);
        });
    }

    // Verificar status do pagamento
    const checkStatusBtn = document.getElementById('check-status-btn');
    const checkStatusForm = document.getElementById('check-status-form');
    const statusText = document.getElementById('status-text');
    
    if (checkStatusBtn && checkStatusForm) {
        checkStatusBtn.addEventListener('click', function() {
            // Alterar texto do botão
            checkStatusBtn.disabled = true;
            checkStatusBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verificando...';
            
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
                    statusText.innerText = 'Status: ' + formatStatus(data.status);
                    
                    // Se pagamento foi aprovado, redirecionar
                    if (data.status === 'approved' || data.status === 'authorized') {
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
                // Restaurar botão
                setTimeout(function() {
                    checkStatusBtn.disabled = false;
                    checkStatusBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Verificar Status';
                }, 1000);
            });
        });
        
        // Verificação automática periódica
        const checkInterval = 20000; // 20 segundos
        let checkTimer = setInterval(function() {
            if (document.visibilityState === 'visible') {
                checkStatusBtn.click();
            }
        }, checkInterval);
        
        // Limpar timer quando usuário sair da página
        window.addEventListener('beforeunload', function() {
            clearInterval(checkTimer);
        });
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