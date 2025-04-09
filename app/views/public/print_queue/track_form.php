<?php
/**
 * View - Formulário de Rastreamento Público de Impressão
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Views/Public
 * @version    1.0.0
 */

// Garantir que este arquivo não seja acessado diretamente
defined('BASEPATH') or exit('Acesso direto ao script não é permitido');
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">Rastreamento de Impressão 3D</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_type'])): ?>
                        <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show">
                            <?= htmlspecialchars($_SESSION['flash_message'], ENT_QUOTES, 'UTF-8') ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mb-4">
                        <img src="/assets/images/logo.png" alt="Taverna da Impressão 3D" class="img-fluid" style="max-height: 100px;">
                        <p class="lead mt-3">Digite o código de rastreamento para acompanhar o status da sua impressão 3D</p>
                    </div>
                    
                    <form method="get" action="/track" class="mb-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                                </div>
                                <input type="text" name="code" id="tracking_code" class="form-control form-control-lg" 
                                    placeholder="Ex: ABCD123456" pattern="[A-Z0-9]{10}" maxlength="10" required
                                    oninput="this.value = this.value.toUpperCase();">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">Rastrear</button>
                                </div>
                            </div>
                            <small class="form-text text-muted">O código deve conter 10 caracteres (letras maiúsculas e números)</small>
                        </div>
                    </form>
                    
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title">Não tem um código de rastreamento?</h5>
                            <p>O código de rastreamento é fornecido por e-mail quando sua impressão 3D é confirmada. 
                               Se você não recebeu seu código ou precisa de assistência, entre em contato conosco.</p>
                            <div class="text-center">
                                <a href="/contact" class="btn btn-outline-primary">Fale Conosco</a>
                                <a href="/login" class="btn btn-outline-secondary ml-2">Login de Cliente</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">
                        Os códigos de rastreamento são válidos por 60 dias após a conclusão da impressão.
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Informações Adicionais -->
    <div class="row justify-content-center mt-4">
        <div class="col-md-8">
            <div class="card-deck">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fa fa-print fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Imprimimos Seu Modelo</h5>
                        <p class="card-text">Utilizamos tecnologia de ponta para imprimir seus modelos 3D com alta precisão e qualidade.</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fa fa-shipping-fast fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Acompanhe em Tempo Real</h5>
                        <p class="card-text">Acompanhe todo o processo de impressão, desde a preparação até a conclusão.</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fa fa-check-circle fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Garanta Qualidade</h5>
                        <p class="card-text">Nossa equipe verifica cada impressão para garantir a máxima qualidade antes da entrega.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const trackingCodeInput = document.getElementById('tracking_code');
    
    // Formatar o código durante a digitação
    trackingCodeInput.addEventListener('input', function(e) {
        // Remover caracteres não permitidos
        this.value = this.value.replace(/[^A-Z0-9]/g, '');
        
        // Limitar o comprimento a 10 caracteres
        if (this.value.length > 10) {
            this.value = this.value.substring(0, 10);
        }
    });
});
</script>
