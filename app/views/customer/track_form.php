<?php require_once APP_ROOT . '/views/partials/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0 text-center"><i class="fas fa-search mr-2"></i> Rastrear Impressão 3D</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="<?php echo BASE_URL; ?>public/assets/img/logo.png" alt="Taverna da Impressão" class="img-fluid" style="max-height: 100px;">
                        <p class="lead mt-3">Acompanhe o status do seu pedido de impressão 3D</p>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo BASE_URL; ?>print_queue/customerTrack" method="GET">
                        <?php echo CsrfProtection::getFormField(); ?>
                        <div class="form-group">
                            <label for="order_number"><i class="fas fa-hashtag mr-2"></i> Número do Pedido:</label>
                            <input type="text" class="form-control" id="order_number" name="order_number" placeholder="Ex: ORD12345" required>
                            <small class="form-text text-muted">Insira o número do pedido que você recebeu por e-mail</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope mr-2"></i> E-mail:</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Seu e-mail de contato" required>
                            <small class="form-text text-muted">Insira o e-mail utilizado na realização do pedido</small>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-search mr-2"></i> Rastrear Pedido
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <i class="fas fa-cube fa-2x text-primary mb-2"></i>
                            <p class="mb-0">Produtos de alta qualidade</p>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-print fa-2x text-primary mb-2"></i>
                            <p class="mb-0">Impressão 3D personalizada</p>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-headset fa-2x text-primary mb-2"></i>
                            <p class="mb-0">Suporte especializado</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i> Como funciona o rastreamento?</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Status de Impressão 3D:</h6>
                            <ul class="list-unstyled">
                                <li><span class="badge badge-light">Pendente</span> - Aguardando preparação</li>
                                <li><span class="badge badge-info">Agendado</span> - Programado para impressão</li>
                                <li><span class="badge badge-primary">Imprimindo</span> - Em processo de impressão</li>
                                <li><span class="badge badge-warning">Pausado</span> - Impressão temporariamente pausada</li>
                                <li><span class="badge badge-success">Concluído</span> - Impressão finalizada com sucesso</li>
                                <li><span class="badge badge-danger">Falha</span> - Ocorreu um problema durante a impressão</li>
                                <li><span class="badge badge-secondary">Cancelado</span> - Impressão cancelada</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Dicas:</h6>
                            <ul>
                                <li>Digite o número do pedido exatamente como foi informado no e-mail de confirmação.</li>
                                <li>O e-mail deve ser o mesmo utilizado para realizar o pedido.</li>
                                <li>Caso tenha dúvidas, entre em contato com nosso suporte.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="<?php echo BASE_URL; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-home mr-2"></i> Voltar para a Página Inicial
                </a>
                <a href="<?php echo BASE_URL; ?>contact" class="btn btn-outline-primary ml-2">
                    <i class="fas fa-envelope mr-2"></i> Contato
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once APP_ROOT . '/views/partials/footer.php'; ?>