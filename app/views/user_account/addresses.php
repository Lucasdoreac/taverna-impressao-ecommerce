<div class="container my-5">
    <div class="row">
        <!-- Menu lateral -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Minha Conta</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo BASE_URL; ?>minha-conta" class="list-group-item list-group-item-action">
                        <i class="fas fa-home me-2"></i> Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>minha-conta/perfil" class="list-group-item list-group-item-action">
                        <i class="fas fa-user me-2"></i> Meu Perfil
                    </a>
                    <a href="<?php echo BASE_URL; ?>minha-conta/pedidos" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-bag me-2"></i> Meus Pedidos
                    </a>
                    <a href="<?php echo BASE_URL; ?>minha-conta/enderecos" class="list-group-item list-group-item-action active">
                        <i class="fas fa-map-marker-alt me-2"></i> Meus Endereços
                    </a>
                    <a href="<?php echo BASE_URL; ?>logout" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Sair
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Conteúdo principal -->
        <div class="col-md-9">
            <?php if (isset($success) && $success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo h($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error) && $error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo h($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Meus Endereços</h4>
                    <a href="<?php echo BASE_URL; ?>minha-conta/enderecos/adicionar" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> Adicionar Endereço
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($addresses)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Você ainda não possui endereços cadastrados.
                        </div>
                        <div class="text-center mt-4">
                            <a href="<?php echo BASE_URL; ?>minha-conta/enderecos/adicionar" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Adicionar Endereço
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            <?php foreach ($addresses as $address): ?>
                                <div class="col">
                                    <div class="card h-100 <?php echo $address['is_default'] ? 'border-primary' : ''; ?>">
                                        <?php if ($address['is_default']): ?>
                                            <div class="card-header bg-primary text-white">
                                                <strong><i class="fas fa-check-circle me-1"></i> Endereço Principal</strong>
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <address>
                                                <strong><?php echo h($_SESSION['user']['name']); ?></strong><br>
                                                <?php echo h($address['street']); ?>, <?php echo h($address['number']); ?><br>
                                                <?php if (!empty($address['complement'])): ?>
                                                    <?php echo h($address['complement']); ?><br>
                                                <?php endif; ?>
                                                <?php echo h($address['neighborhood']); ?><br>
                                                <?php echo h($address['city']); ?> - <?php echo h($address['state']); ?><br>
                                                CEP: <?php echo h($address['postal_code']); ?>
                                            </address>
                                        </div>
                                        <div class="card-footer bg-transparent d-flex justify-content-between">
                                            <a href="<?php echo BASE_URL; ?>minha-conta/enderecos/editar/<?php echo h($address['id']); ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit me-1"></i> Editar
                                            </a>
                                            <?php if (!$address['is_default']): ?>
                                                <a href="<?php echo BASE_URL; ?>minha-conta/enderecos/excluir/<?php echo h($address['id']); ?>?csrf_token=<?php echo SecurityManager::getCsrfToken(); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja excluir este endereço?');">
                                                    <i class="fas fa-trash-alt me-1"></i> Excluir
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled title="Não é possível excluir o endereço principal">
                                                    <i class="fas fa-trash-alt me-1"></i> Excluir
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4">
                            <div class="alert alert-light border">
                                <i class="fas fa-info-circle me-2 text-primary"></i> 
                                <strong>Dica:</strong> O endereço principal é utilizado por padrão em suas compras. Para alterar o endereço principal, edite um endereço e marque a opção "Definir como endereço principal".
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>