<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?= isset($option) ? 'Editar' : 'Nova' ?> Opção de Personalização</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/customizacao">Personalização</a></li>
                        <li class="breadcrumb-item active"><?= isset($option) ? 'Editar' : 'Nova' ?> Opção</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
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
                    <h3 class="card-title"><?= isset($option) ? 'Editar' : 'Nova' ?> Opção de Personalização</h3>
                </div>
                <form action="<?= BASE_URL ?>admin/customizacao/<?= isset($option) ? 'atualizar' : 'salvar' ?>" method="post">
                    <?php if (isset($option)): ?>
                        <input type="hidden" name="id" value="<?= $option['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <div class="form-group">
                            <label for="product_id">Produto</label>
                            <select name="product_id" id="product_id" class="form-control select2" required>
                                <option value="">Selecione um produto</option>
                                <?php foreach ($products as $prod): ?>
                                    <option value="<?= $prod['id'] ?>" <?= (isset($product) && $product['id'] == $prod['id']) || (isset($option) && $option['product_id'] == $prod['id']) ? 'selected' : '' ?>>
                                        <?= $prod['name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="name">Nome da Opção</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= $option['name'] ?? '' ?>" required>
                            <small class="form-text text-muted">Nome que será exibido para o cliente, ex: "Cor da borda", "Texto personalizado", etc.</small>
                        </div>

                        <div class="form-group">
                            <label for="description">Descrição</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= $option['description'] ?? '' ?></textarea>
                            <small class="form-text text-muted">Descrição opcional para explicar a opção ao cliente.</small>
                        </div>

                        <div class="form-group">
                            <label for="type">Tipo de Campo</label>
                            <select name="type" id="type" class="form-control" required onchange="toggleOptionsField()">
                                <option value="text" <?= (isset($option) && $option['type'] == 'text') ? 'selected' : '' ?>>Campo de Texto</option>
                                <option value="select" <?= (isset($option) && $option['type'] == 'select') ? 'selected' : '' ?>>Seleção de Opções</option>
                                <option value="upload" <?= (isset($option) && $option['type'] == 'upload') ? 'selected' : '' ?>>Upload de Arquivo</option>
                            </select>
                        </div>

                        <div class="form-group" id="options-group" style="display: <?= (isset($option) && $option['type'] == 'select') ? 'block' : 'none' ?>;">
                            <label for="options">Opções de Seleção</label>
                            <textarea class="form-control" id="options" name="options" rows="5" placeholder="valor:Descrição"><?= $option['options_text'] ?? '' ?></textarea>
                            <small class="form-text text-muted">
                                Uma opção por linha, no formato "valor: Descrição". Exemplo:<br>
                                red: Vermelho<br>
                                blue: Azul<br>
                                green: Verde
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="required" name="required" <?= (isset($option) && $option['required']) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="required">Obrigatório</label>
                            </div>
                            <small class="form-text text-muted">Se marcado, o cliente deve preencher este campo para adicionar o produto ao carrinho.</small>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Salvar</button>
                        <a href="<?= BASE_URL ?>admin/customizacao" class="btn btn-default">Cancelar</a>
                        
                        <?php if (isset($option)): ?>
                            <button type="button" class="btn btn-danger float-right" data-toggle="modal" data-target="#deleteModal">
                                Excluir
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <?php if (isset($product)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Sobre o Produto</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Nome:</strong> <?= $product['name'] ?></p>
                                <p><strong>Preço:</strong> R$ <?= number_format($product['price'], 2, ',', '.') ?></p>
                                <p><strong>SKU:</strong> <?= $product['sku'] ?? 'N/A' ?></p>
                            </div>
                            <div class="col-md-6">
                                <?php if (isset($product['images'][0])): ?>
                                    <img src="<?= BASE_URL ?>uploads/products/<?= $product['images'][0]['image'] ?>" alt="<?= $product['name'] ?>" class="img-fluid" style="max-height: 150px;">
                                <?php else: ?>
                                    <div class="alert alert-info">Sem imagem disponível</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($option)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Prévia da Opção de Personalização</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="preview-field"><?= $option['name'] ?></label>
                                    <?php if ($option['description']): ?>
                                        <p class="text-muted mb-2"><?= $option['description'] ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($option['type'] === 'text'): ?>
                                        <textarea class="form-control" id="preview-field" rows="3" placeholder="Este é um campo de exemplo" <?= $option['required'] ? 'required' : '' ?>></textarea>
                                    <?php elseif ($option['type'] === 'select'): ?>
                                        <select class="form-control" id="preview-field" <?= $option['required'] ? 'required' : '' ?>>
                                            <option value="">Selecione uma opção</option>
                                            <?php 
                                            $options = json_decode($option['options'], true);
                                            if (is_array($options)): 
                                                foreach ($options as $value => $label): 
                                            ?>
                                                <option value="<?= $value ?>"><?= $label ?></option>
                                            <?php 
                                                endforeach; 
                                            endif; 
                                            ?>
                                        </select>
                                    <?php elseif ($option['type'] === 'upload'): ?>
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="preview-field" <?= $option['required'] ? 'required' : '' ?>>
                                            <label class="custom-file-label" for="preview-field">Escolher arquivo</label>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> Informação</h5>
                                    Esta é uma prévia de como a opção aparecerá para o cliente. Os campos não são funcionais nesta tela.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php if (isset($option)): ?>
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
                <p>Tem certeza que deseja excluir a opção de personalização <strong><?= $option['name'] ?></strong>?</p>
                <p class="text-danger">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <a href="<?= BASE_URL ?>admin/customizacao/excluir/<?= $option['id'] ?>" class="btn btn-danger">Excluir</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    function toggleOptionsField() {
        const type = document.getElementById('type').value;
        const optionsGroup = document.getElementById('options-group');
        
        if (type === 'select') {
            optionsGroup.style.display = 'block';
        } else {
            optionsGroup.style.display = 'none';
        }
    }
    
    $(function () {
        // Inicializar select2
        $('.select2').select2({
            theme: 'bootstrap4'
        });
        
        // Inicializar BS custom file
        bsCustomFileInput.init();
    });
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>