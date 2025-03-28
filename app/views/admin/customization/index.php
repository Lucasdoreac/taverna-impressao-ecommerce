<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Gerenciar Opções de Personalização</h1>
        <a href="<?= BASE_URL ?>admin/customization/create" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Nova Opção
        </a>
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

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <?php if (isset($product)): ?>
                    Opções de Personalização para: <?= $product['name'] ?>
                <?php else: ?>
                    Todas as Opções de Personalização
                <?php endif; ?>
            </h6>
            
            <div>
                <!-- Filtro por Produto -->
                <div class="dropdown d-inline-block">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-funnel me-1"></i> Filtrar por Produto
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/customization">Todos os Produtos</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php foreach ($customizableProducts as $prod): ?>
                        <li>
                            <a class="dropdown-item <?= isset($productId) && $productId == $prod['id'] ? 'active' : '' ?>" 
                               href="<?= BASE_URL ?>admin/customization?product_id=<?= $prod['id'] ?>">
                                <?= $prod['name'] ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($options['items'])): ?>
            <div class="text-center py-4">
                <p class="text-muted mb-0">
                    <?php if (isset($productId)): ?>
                        Nenhuma opção de personalização encontrada para este produto.
                    <?php else: ?>
                        Nenhuma opção de personalização cadastrada.
                    <?php endif; ?>
                </p>
                
                <?php if (isset($productId)): ?>
                <div class="mt-3">
                    <a href="<?= BASE_URL ?>admin/customization/create?product_id=<?= $productId ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> Adicionar Opção para este Produto
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">Produto</th>
                            <th width="20%">Nome</th>
                            <th width="10%">Tipo</th>
                            <th width="10%">Obrigatório</th>
                            <th width="25%">Descrição</th>
                            <th width="15%">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($options['items'] as $option): ?>
                        <tr>
                            <td><?= $option['id'] ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>admin/produtos/edit/<?= $option['product_id'] ?>" class="text-decoration-none">
                                    <?= $option['product_name'] ?>
                                </a>
                            </td>
                            <td><?= $option['name'] ?></td>
                            <td>
                                <?php if ($option['type'] === 'upload'): ?>
                                    <span class="badge bg-info">Upload</span>
                                <?php elseif ($option['type'] === 'text'): ?>
                                    <span class="badge bg-secondary">Texto</span>
                                <?php elseif ($option['type'] === 'select'): ?>
                                    <span class="badge bg-primary">Seleção</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($option['required']): ?>
                                    <span class="text-success"><i class="bi bi-check-circle-fill"></i> Sim</span>
                                <?php else: ?>
                                    <span class="text-secondary"><i class="bi bi-x-circle"></i> Não</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($option['description']): ?>
                                    <?= mb_strimwidth($option['description'], 0, 100, '...') ?>
                                <?php else: ?>
                                    <span class="text-muted">Sem descrição</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="<?= BASE_URL ?>admin/customization/show/<?= $option['id'] ?>" class="btn btn-sm btn-info" title="Visualizar">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>admin/customization/edit/<?= $option['id'] ?>" class="btn btn-sm btn-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>admin/customization/confirm-delete/<?= $option['id'] ?>" class="btn btn-sm btn-danger" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginação -->
            <?php if ($options['lastPage'] > 1): ?>
            <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Navegação de página">
                    <ul class="pagination">
                        <?php 
                        $queryParams = isset($productId) ? "product_id={$productId}&" : "";
                        
                        // Link Anterior
                        if ($options['currentPage'] > 1): 
                        ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= BASE_URL ?>admin/customization?<?= $queryParams ?>page=<?= $options['currentPage'] - 1 ?>" aria-label="Anterior">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php else: ?>
                        <li class="page-item disabled">
                            <a class="page-link" href="#" aria-label="Anterior">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <!-- Números das Páginas -->
                        <?php
                        $startPage = max(1, $options['currentPage'] - 2);
                        $endPage = min($options['lastPage'], $options['currentPage'] + 2);
                        
                        // Mostrar primeira página se não estiver no range
                        if ($startPage > 1): 
                        ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= BASE_URL ?>admin/customization?<?= $queryParams ?>page=1">1</a>
                        </li>
                        <?php 
                        // Mostrar "..." se houver páginas ocultas entre a primeira e o range atual
                        if ($startPage > 2): 
                        ?>
                        <li class="page-item disabled">
                            <a class="page-link" href="#">...</a>
                        </li>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Páginas no range atual -->
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i == $options['currentPage'] ? 'active' : '' ?>">
                            <a class="page-link" href="<?= BASE_URL ?>admin/customization?<?= $queryParams ?>page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <!-- Mostrar última página se não estiver no range -->
                        <?php if ($endPage < $options['lastPage']): ?>
                        <!-- Mostrar "..." se houver páginas ocultas entre o range atual e a última página -->
                        <?php if ($endPage < $options['lastPage'] - 1): ?>
                        <li class="page-item disabled">
                            <a class="page-link" href="#">...</a>
                        </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= BASE_URL ?>admin/customization?<?= $queryParams ?>page=<?= $options['lastPage'] ?>"><?= $options['lastPage'] ?></a>
                        </li>
                        <?php endif; ?>
                        
                        <!-- Link Próximo -->
                        <?php if ($options['currentPage'] < $options['lastPage']): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= BASE_URL ?>admin/customization?<?= $queryParams ?>page=<?= $options['currentPage'] + 1 ?>" aria-label="Próximo">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <?php else: ?>
                        <li class="page-item disabled">
                            <a class="page-link" href="#" aria-label="Próximo">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
