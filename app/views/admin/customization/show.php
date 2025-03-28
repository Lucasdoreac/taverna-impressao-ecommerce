<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detalhes da Opção de Personalização</h1>
        <div>
            <a href="<?= BASE_URL ?>admin/customization/edit/<?= $option['id'] ?>" class="btn btn-primary btn-sm me-2">
                <i class="bi bi-pencil me-1"></i> Editar
            </a>
            <a href="<?= BASE_URL ?>admin/customization" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Voltar
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <!-- Informações Básicas -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informações da Opção</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th class="bg-light" width="30%">ID</th>
                                <td><?= $option['id'] ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Nome</th>
                                <td><?= $option['name'] ?></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Produto</th>
                                <td>
                                    <a href="<?= BASE_URL ?>admin/produtos/edit/<?= $option['product_id'] ?>" class="text-decoration-none">
                                        <?= $option['product_name'] ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light">Tipo</th>
                                <td>
                                    <?php if ($option['type'] === 'upload'): ?>
                                        <span class="badge bg-info">Upload de Arquivo</span>
                                    <?php elseif ($option['type'] === 'text'): ?>
                                        <span class="badge bg-secondary">Campo de Texto</span>
                                    <?php elseif ($option['type'] === 'select'): ?>
                                        <span class="badge bg-primary">Seleção de Opções</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light">Obrigatório</th>
                                <td>
                                    <?php if ($option['required']): ?>
                                        <span class="text-success"><i class="bi bi-check-circle-fill"></i> Sim</span>
                                    <?php else: ?>
                                        <span class="text-secondary"><i class="bi bi-x-circle"></i> Não</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light">Descrição</th>
                                <td>
                                    <?php if ($option['description']): ?>
                                        <?= nl2br($option['description']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sem descrição</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-light">Data de Criação</th>
                                <td><?= date('d/m/Y H:i', strtotime($option['created_at'])) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Opções de Seleção (se aplicável) -->
            <?php if ($option['type'] === 'select' && isset($option['parsed_options']) && !empty($option['parsed_options'])): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Opções de Seleção</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th width="30%">Valor</th>
                                    <th>Texto Exibido</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($option['parsed_options'] as $value => $text): ?>
                                <tr>
                                    <td><code><?= $value ?></code></td>
                                    <td><?= $text ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Instruções para Upload (se aplicável) -->
            <?php if ($option['type'] === 'upload'): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Configuração de Upload</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <h6 class="alert-heading">Formatos Aceitos</h6>
                        <p>Esta opção permite que os clientes façam upload dos seguintes tipos de arquivo:</p>
                        <ul>
                            <li><strong>PDF</strong> (recomendado para material impresso)</li>
                            <li><strong>JPG/JPEG</strong> (resolução mínima recomendada: 300 DPI)</li>
                            <li><strong>PNG</strong> (com transparência se necessário)</li>
                        </ul>
                        <p class="mb-0">O tamanho máximo permitido é de 10MB por arquivo.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Prévia na Loja (como o cliente verá) -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Prévia na Loja</h6>
                </div>
                <div class="card-body">
                    <p class="mb-3">Assim é como o cliente verá esta opção na página de personalização:</p>
                    
                    <div class="border rounded p-3 bg-light">
                        <div class="mb-3">
                            <label for="preview_option" class="form-label fw-bold">
                                <?= $option['name'] ?>
                                <?php if ($option['required']): ?>
                                <span class="text-danger">*</span>
                                <?php endif; ?>
                            </label>
                            
                            <?php if ($option['description']): ?>
                            <p class="text-muted small mb-2"><?= $option['description'] ?></p>
                            <?php endif; ?>
                            
                            <?php if ($option['type'] === 'upload'): ?>
                            <!-- Upload de Arquivo -->
                            <div class="mb-2">
                                <input type="file" class="form-control" id="preview_option" disabled>
                            </div>
                            <?php elseif ($option['type'] === 'text'): ?>
                            <!-- Campo de Texto -->
                            <textarea class="form-control" id="preview_option" rows="3" disabled></textarea>
                            <?php elseif ($option['type'] === 'select'): ?>
                            <!-- Seleção de Opções -->
                            <select class="form-select" id="preview_option" disabled>
                                <option value="">Selecione uma opção</option>
                                <?php 
                                if (isset($option['parsed_options']) && !empty($option['parsed_options'])) {
                                    foreach ($option['parsed_options'] as $value => $text) {
                                        echo "<option value=\"{$value}\">{$text}</option>";
                                    }
                                }
                                ?>
                            </select>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Estatísticas de Uso -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Estatísticas de Uso</h6>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <div class="me-3">
                                <i class="bi bi-cart-check text-primary fs-3"></i>
                            </div>
                            <div>
                                <div class="small text-muted">Pedidos Usando Esta Opção</div>
                                <div class="h4 mb-0"><?= $option['usage_stats']['order_count'] ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <div class="me-3">
                                <i class="bi bi-cart fs-3 text-info"></i>
                            </div>
                            <div>
                                <div class="small text-muted">Itens em Carrinhos Ativos</div>
                                <div class="h4 mb-0"><?= $option['usage_stats']['cart_count'] ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <div class="me-3">
                                <i class="bi bi-graph-up text-success fs-3"></i>
                            </div>
                            <div>
                                <div class="small text-muted">Total de Usos</div>
                                <div class="h4 mb-0"><?= $option['usage_stats']['total_usage'] ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <?php if ($option['usage_stats']['total_usage'] > 0): ?>
                    <div class="alert alert-warning mb-0">
                        <p class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i> Esta opção está em uso. Altere com cuidado.</p>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success mb-0">
                        <p class="mb-0"><i class="bi bi-check-circle me-2"></i> Esta opção ainda não está em uso e pode ser alterada com segurança.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Ações -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ações</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>admin/customization/edit/<?= $option['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-pencil me-1"></i> Editar Opção
                        </a>
                        <a href="<?= BASE_URL ?>produto/<?= $option['product_slug'] ?>" target="_blank" class="btn btn-outline-primary">
                            <i class="bi bi-eye me-1"></i> Ver Produto na Loja
                        </a>
                        <a href="<?= BASE_URL ?>admin/customization?product_id=<?= $option['product_id'] ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-list-ul me-1"></i> Ver Todas as Opções deste Produto
                        </a>
                        <a href="<?= BASE_URL ?>admin/customization/confirm-delete/<?= $option['id'] ?>" class="btn btn-outline-danger">
                            <i class="bi bi-trash me-1"></i> Excluir Opção
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
