<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Gerenciamento de Opções de Personalização</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin">Dashboard</a></li>
                        <li class="breadcrumb-item active">Personalização</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-check"></i> Sucesso!</h5>
                    <?= $_SESSION['success'] ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Erro!</h5>
                    <?= $_SESSION['error'] ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Produtos Customizáveis</h3>
                    <div class="card-tools">
                        <a href="<?= BASE_URL ?>admin/customizacao/criar" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Nova Opção de Personalização
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($products)): ?>
                        <div class="alert alert-info">
                            Nenhum produto customizável encontrado. <a href="<?= BASE_URL ?>admin/produtos">Adicione produtos customizáveis</a> para gerenciar opções de personalização.
                        </div>
                    <?php else: ?>
                        <div class="accordion" id="customizableProducts">
                            <?php foreach ($products as $index => $product): ?>
                                <div class="card">
                                    <div class="card-header" id="heading<?= $product['id'] ?>">
                                        <h2 class="mb-0 d-flex justify-content-between align-items-center">
                                            <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse<?= $product['id'] ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $product['id'] ?>">
                                                <?= $product['name'] ?>
                                                <?php if (empty($product['customization_options'])): ?>
                                                    <span class="badge badge-warning ml-2">Sem opções</span>
                                                <?php else: ?>
                                                    <span class="badge badge-info ml-2"><?= count($product['customization_options']) ?> opções</span>
                                                <?php endif; ?>
                                            </button>
                                            <a href="<?= BASE_URL ?>admin/customizacao/criar?product_id=<?= $product['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-plus"></i> Adicionar Opção
                                            </a>
                                        </h2>
                                    </div>

                                    <div id="collapse<?= $product['id'] ?>" class="collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $product['id'] ?>" data-parent="#customizableProducts">
                                        <div class="card-body">
                                            <?php if (empty($product['customization_options'])): ?>
                                                <div class="alert alert-info">
                                                    Este produto não possui opções de personalização. Adicione opções para permitir personalização.
                                                </div>
                                            <?php else: ?>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Nome</th>
                                                                <th>Tipo</th>
                                                                <th>Obrigatório</th>
                                                                <th>Descrição</th>
                                                                <th>Ações</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="options-<?= $product['id'] ?>" class="options-sortable">
                                                            <?php foreach ($product['customization_options'] as $option): ?>
                                                                <tr data-option-id="<?= $option['id'] ?>">
                                                                    <td>
                                                                        <i class="fas fa-grip-vertical handle mr-2 text-muted"></i>
                                                                        <?= $option['name'] ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($option['type'] === 'text'): ?>
                                                                            <span class="badge badge-info">Texto</span>
                                                                        <?php elseif ($option['type'] === 'select'): ?>
                                                                            <span class="badge badge-success">Seleção</span>
                                                                        <?php elseif ($option['type'] === 'upload'): ?>
                                                                            <span class="badge badge-warning">Upload</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($option['required']): ?>
                                                                            <span class="badge badge-danger">Sim</span>
                                                                        <?php else: ?>
                                                                            <span class="badge badge-secondary">Não</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td><?= $option['description'] ?? 'Sem descrição' ?></td>
                                                                    <td>
                                                                        <div class="btn-group">
                                                                            <a href="<?= BASE_URL ?>admin/customizacao/editar/<?= $option['id'] ?>" class="btn btn-sm btn-info">
                                                                                <i class="fas fa-edit"></i>
                                                                            </a>
                                                                            <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="<?= $option['id'] ?>" data-name="<?= $option['name'] ?>">
                                                                                <i class="fas fa-trash"></i>
                                                                            </button>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a opção de personalização <strong id="optionName"></strong>?</p>
                <p class="text-danger">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <a href="#" id="confirmDelete" class="btn btn-danger">Excluir</a>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        // Inicializar sortable para reordenação de opções
        $('.options-sortable').sortable({
            handle: '.handle',
            update: function(event, ui) {
                const productId = $(this).attr('id').split('-')[1];
                const optionIds = $(this).find('tr').map(function() {
                    return $(this).data('option-id');
                }).get();
                
                // Enviar nova ordem para o servidor
                $.ajax({
                    url: '<?= BASE_URL ?>admin/customizacao/reordenar',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        options: JSON.stringify(optionIds)
                    },
                    success: function(response) {
                        if (response.success) {
                            // Exibir mensagem de sucesso
                            toastr.success('Ordem das opções atualizada com sucesso.');
                        } else {
                            // Exibir mensagem de erro
                            toastr.error(response.error || 'Erro ao atualizar ordem das opções.');
                        }
                    },
                    error: function() {
                        toastr.error('Erro de comunicação com o servidor.');
                    }
                });
            }
        });
        
        // Configurar modal de exclusão
        $('#deleteModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const id = button.data('id');
            const name = button.data('name');
            
            const modal = $(this);
            modal.find('#optionName').text(name);
            modal.find('#confirmDelete').attr('href', '<?= BASE_URL ?>admin/customizacao/excluir/' + id);
        });
    });
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>