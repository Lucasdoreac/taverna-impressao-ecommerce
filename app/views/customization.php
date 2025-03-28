<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>categoria/<?= $product['category_slug'] ?>"><?= $product['category_name'] ?></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>produto/<?= $product['slug'] ?>"><?= $product['name'] ?></a></li>
            <li class="breadcrumb-item active">Personalizar</li>
        </ol>
    </nav>
    
    <h1 class="h2 mb-4">Personalizar <?= $product['name'] ?></h1>
    
    <div class="row">
        <!-- Imagem do Produto -->
        <div class="col-md-4 mb-4">
            <?php if (!empty($product['images'])): ?>
            <img src="<?= BASE_URL ?>uploads/products/<?= $product['images'][0]['image'] ?>" class="img-fluid rounded" alt="<?= $product['name'] ?>">
            <?php else: ?>
            <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 300px;">
                <span class="text-muted">Sem imagem</span>
            </div>
            <?php endif; ?>
            
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">Resumo</h5>
                    <p class="mb-1"><strong>Produto:</strong> <?= $product['name'] ?></p>
                    <p class="mb-1"><strong>Preço:</strong> R$ <?= number_format($product['sale_price'] ?: $product['price'], 2, ',', '.') ?></p>
                </div>
            </div>
        </div>
        
        <!-- Formulário de Personalização -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Opções de Personalização</h5>
                </div>
                <div class="card-body">
                    <form id="customizationForm" method="post" action="<?= BASE_URL ?>carrinho/adicionar" enctype="multipart/form-data">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        
                        <!-- Opções de Personalização Dinâmicas -->
                        <?php if (!empty($product['customization_options'])): ?>
                        <?php foreach ($product['customization_options'] as $option): ?>
                        <div class="mb-4">
                            <label for="option_<?= $option['id'] ?>" class="form-label fw-bold"><?= $option['name'] ?></label>
                            
                            <?php if ($option['description']): ?>
                            <p class="text-muted small mb-2"><?= $option['description'] ?></p>
                            <?php endif; ?>
                            
                            <?php if ($option['type'] === 'upload'): ?>
                            <!-- Upload de Arquivo -->
                            <div class="custom-file-upload">
                                <div class="mb-2">
                                    <input type="file" class="form-control custom-file-input" id="option_<?= $option['id'] ?>" 
                                           name="customization[<?= $option['id'] ?>]" <?= $option['required'] ? 'required' : '' ?>>
                                </div>
                                <div class="file-preview d-none mb-2">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <img src="" alt="Preview" class="file-preview-image me-3" style="max-width: 100px; max-height: 100px;">
                                                <div>
                                                    <p class="file-name mb-1 fw-bold"></p>
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-file">Remover</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="progress d-none">
                                    <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <input type="hidden" name="customization_file[<?= $option['id'] ?>]" class="customization-file-input">
                            </div>
                            
                            <?php elseif ($option['type'] === 'text'): ?>
                            <!-- Campo de Texto -->
                            <textarea class="form-control" id="option_<?= $option['id'] ?>" 
                                      name="customization[<?= $option['id'] ?>]" rows="3" 
                                      <?= $option['required'] ? 'required' : '' ?>></textarea>
                            
                            <?php elseif ($option['type'] === 'select'): ?>
                            <!-- Seleção de Opções -->
                            <select class="form-select" id="option_<?= $option['id'] ?>" 
                                    name="customization[<?= $option['id'] ?>]" 
                                    <?= $option['required'] ? 'required' : '' ?>>
                                <option value="">Selecione uma opção</option>
                                <?php 
                                $selectOptions = json_decode($option['options'], true) ?: [];
                                foreach ($selectOptions as $value => $label): 
                                ?>
                                <option value="<?= $value ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php else: ?>
                        <!-- Opção Padrão de Upload Se Não Houver Opções Configuradas -->
                        <div class="mb-4">
                            <label for="default_upload" class="form-label fw-bold">Envie seu arquivo para impressão</label>
                            <p class="text-muted small mb-2">Envie arquivos PDF, JPG ou PNG com a melhor qualidade possível para garantir uma boa impressão.</p>
                            
                            <div class="custom-file-upload">
                                <div class="mb-2">
                                    <input type="file" class="form-control custom-file-input" id="default_upload" 
                                           name="customization[default]" required>
                                </div>
                                <div class="file-preview d-none mb-2">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <img src="" alt="Preview" class="file-preview-image me-3" style="max-width: 100px; max-height: 100px;">
                                                <div>
                                                    <p class="file-name mb-1 fw-bold"></p>
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-file">Remover</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="progress d-none">
                                    <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <input type="hidden" name="customization_file[default]" class="customization-file-input">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="additional_notes" class="form-label fw-bold">Instruções adicionais</label>
                            <p class="text-muted small mb-2">Informe quaisquer detalhes adicionais para a impressão.</p>
                            <textarea class="form-control" id="additional_notes" name="customization[notes]" rows="3"></textarea>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Quantidade -->
                        <div class="mb-4">
                            <label for="quantity" class="form-label fw-bold">Quantidade</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" required>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?= BASE_URL ?>produto/<?= $product['slug'] ?>" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Adicionar ao Carrinho</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript para Upload de Arquivo -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Processar todos os campos de upload
    document.querySelectorAll('.custom-file-input').forEach(function(input) {
        input.addEventListener('change', function() {
            const fileUploadBlock = this.closest('.custom-file-upload');
            const filePreview = fileUploadBlock.querySelector('.file-preview');
            const progressBar = fileUploadBlock.querySelector('.progress');
            const progressBarInner = progressBar.querySelector('.progress-bar');
            const fileNameDisplay = fileUploadBlock.querySelector('.file-name');
            const previewImage = fileUploadBlock.querySelector('.file-preview-image');
            const fileInput = fileUploadBlock.querySelector('.customization-file-input');
            
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Mostrar progress bar e esconder preview
                filePreview.classList.add('d-none');
                progressBar.classList.remove('d-none');
                progressBarInner.style.width = '0%';
                
                // Criar FormData
                const formData = new FormData();
                formData.append('custom_file', file);
                
                // Enviar arquivo via AJAX
                const xhr = new XMLHttpRequest();
                
                // Progresso do upload
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        progressBarInner.style.width = percent + '%';
                        progressBarInner.setAttribute('aria-valuenow', percent);
                    }
                });
                
                xhr.addEventListener('load', function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            
                            if (response.success) {
                                // Esconder progress bar e mostrar preview
                                progressBar.classList.add('d-none');
                                filePreview.classList.remove('d-none');
                                
                                // Atualizar preview
                                previewImage.src = response.previewUrl;
                                fileNameDisplay.textContent = response.originalName;
                                
                                // Armazenar nome do arquivo no input hidden
                                fileInput.value = response.fileName;
                            } else {
                                alert(response.error);
                                progressBar.classList.add('d-none');
                                input.value = '';
                            }
                        } catch (e) {
                            console.error('Erro ao processar resposta:', e);
                            alert('Erro ao processar o upload');
                            progressBar.classList.add('d-none');
                            input.value = '';
                        }
                    } else {
                        alert('Erro ao fazer upload: ' + xhr.status);
                        progressBar.classList.add('d-none');
                        input.value = '';
                    }
                });
                
                xhr.addEventListener('error', function() {
                    alert('Erro de rede ao fazer upload');
                    progressBar.classList.add('d-none');
                    input.value = '';
                });
                
                xhr.open('POST', '<?= BASE_URL ?>personalizar/upload', true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send(formData);
            }
        });
    });
    
    // Remover arquivo
    document.querySelectorAll('.remove-file').forEach(function(button) {
        button.addEventListener('click', function() {
            const fileUploadBlock = this.closest('.custom-file-upload');
            const filePreview = fileUploadBlock.querySelector('.file-preview');
            const fileInput = fileUploadBlock.querySelector('.custom-file-input');
            const hiddenInput = fileUploadBlock.querySelector('.customization-file-input');
            
            // Resetar campos
            filePreview.classList.add('d-none');
            fileInput.value = '';
            hiddenInput.value = '';
        });
    });
});
</script>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>