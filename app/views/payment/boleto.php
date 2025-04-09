<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-header bg-primary text-white">
                    <h1 class="h4 mb-0">Pagamento via Boleto</h1>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <p class="mb-1"><strong>Pedido #<?= htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') ?></strong></p>
                        <p class="mb-0">Valor: <strong><?= htmlspecialchars($currencySymbol, ENT_QUOTES, 'UTF-8') ?> <?= number_format($total, 2, ',', '.') ?></strong></p>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="d-flex flex-column h-100">
                                <h5 class="text-dark">Instruções</h5>
                                <ol>
                                    <li>Clique no botão abaixo para visualizar o boleto</li>
                                    <li>Imprima ou salve o PDF gerado</li>
                                    <li>Pague em qualquer casa lotérica, agência bancária ou internet banking</li>
                                </ol>
                                
                                <?php if (!empty($expiresAt)): ?>
                                    <div class="alert alert-warning d-flex align-items-center mt-auto">
                                        <i class="bi bi-calendar-event me-2 fs-4"></i>
                                        <div>
                                            <strong>Atenção:</strong> Este boleto vence em<br>
                                            <span class="fw-bold"><?= htmlspecialchars($expiresAt, ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <h5 class="text-dark">Boleto</h5>
                            
                            <?php if (!empty($boletoUrl)): ?>
                                <div class="d-grid gap-2">
                                    <a href="<?= htmlspecialchars($boletoUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-primary">
                                        <i class="bi bi-file-earmark-pdf"></i> Visualizar Boleto
                                    </a>
                                    <a href="<?= htmlspecialchars($boletoUrl, ENT_QUOTES, 'UTF-8') ?>" download="boleto_<?= htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') ?>.pdf" class="btn btn-outline-primary">
                                        <i class="bi bi-download"></i> Baixar PDF
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    PDF do boleto não disponível.
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($barCode)): ?>
                                <div class="mt-3">
                                    <label for="bar-code" class="form-label fw-bold">Linha Digitável:</label>
                                    <div class="input-group">
                                        <input type="text" id="bar-code" class="form-control form-control-sm bg-light" 
                                            value="<?= htmlspecialchars($barCode, ENT_QUOTES, 'UTF-8') ?>" readonly>
                                        <button class="btn btn-primary btn-sm" type="button" id="copy-bar-code">
                                            <i class="bi bi-clipboard"></i> Copiar
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="alert alert-secondary mb-4">
                        <h5 class="alert-heading">Informações Importantes:</h5>
                        <ul class="mb-0">
                            <li>O pedido só será processado após a confirmação do pagamento</li>
                            <li>Após o pagamento, a compensação bancária pode levar até 3 dias úteis</li>
                            <li>Você receberá uma notificação por email quando o pagamento for confirmado</li>
                            <li>O boleto e as instruções também foram enviados para seu email</li>
                        </ul>
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
                            <p class="small text-muted mb-0">Você pode verificar o status manualmente clicando no botão abaixo.</p>
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
    // Copiar código de barras
    const copyBtn = document.getElementById('copy-bar-code');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const barCodeInput = document.getElementById('bar-code');
            barCodeInput.select();
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
        
        // Verificação automática a cada 5 minutos (boleto tem verificação menos frequente)
        const checkInterval = 300000; // 5 minutos
        let checkTimer = setInterval(function() {
            if (document.visibilityState === 'visible') {
                checkStatusBtn.click();
            }
        }, checkInterval);
        
        // Limpar timer quando usuário sair da página
        window.addEventListener('beforeunload', function() {
            clearInterval(checkTimer);
        });
        
        // Verificar status inicial
        setTimeout(function() {
            checkStatusBtn.click();
        }, 1000);
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