<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>categoria/<?= $product['category_slug'] ?>">
                <?= $product['category_name'] ?>
            </a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>produto/<?= $product['slug'] ?>">
                <?= $product['name'] ?>
            </a></li>
            <li class="breadcrumb-item active">Personalizar Impressão 3D</li>
        </ol>
    </nav>
    
    <h1 class="h2 mb-4">Personalizar Impressão 3D: <?= $product['name'] ?></h1>
    
    <!-- Indicador de disponibilidade -->
    <?php if ($product['is_tested']): ?>
    <div class="alert alert-success mb-4">
        <i class="fa fa-check-circle"></i> 
        <strong>Produto testado e aprovado</strong> - Disponível para pronta entrega!
    </div>
    <?php else: ?>
    <div class="alert alert-info mb-4">
        <i class="fa fa-info-circle"></i>
        <strong>Produto sob encomenda</strong> - Será impresso especialmente para você após a confirmação do pedido.
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Imagem e Detalhes do Produto -->
        <div class="col-md-4 mb-4">
            <?php if (!empty($product['images'])): ?>
            <div id="product-carousel" class="carousel slide mb-3" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php foreach($product['images'] as $index => $image): ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                        <img src="<?= BASE_URL ?>uploads/products/<?= $image['image'] ?>" 
                             class="d-block w-100 rounded" alt="<?= $product['name'] ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($product['images']) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#product-carousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Anterior</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#product-carousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Próximo</span>
                </button>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="bg-light d-flex align-items-center justify-content-center rounded" style="height: 300px;">
                <span class="text-muted">Sem imagem</span>
            </div>
            <?php endif; ?>
            
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Especificações Técnicas</h5>
                    <p class="mb-1"><strong>Escala Padrão:</strong> <?= $product['scale'] ?></p>
                    <p class="mb-1"><strong>Dimensões:</strong> <?= $product['dimensions'] ?></p>
                    <p class="mb-1"><strong>Uso de Filamento:</strong> <?= $product['filament_usage_grams'] ?>g</p>
                    <p class="mb-1"><strong>Tempo de Impressão:</strong> <?= $product['print_time_hours'] ?> horas</p>
                    <p class="mb-1"><strong>Filamento Recomendado:</strong> <?= $product['filament_type'] ?></p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Resumo</h5>
                    <p class="mb-1"><strong>Produto:</strong> <?= $product['name'] ?></p>
                    <p class="mb-1"><strong>Preço Base:</strong> <?= getCurrencySymbol() ?> <?= number_format($product['sale_price'] ?: $product['price'], 2, ',', '.') ?></p>
                    <div id="customization-total" class="mt-3">
                        <p class="mb-1"><strong>Total com customizações:</strong> <span id="total-price"><?= getCurrencySymbol() ?> <?= number_format($product['sale_price'] ?: $product['price'], 2, ',', '.') ?></span></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Formulário de Personalização 3D -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Personalização 3D</h5>
                </div>
                <div class="card-body">
                    <form id="customization3dForm" method="post" action="<?= BASE_URL ?>carrinho/adicionar" enctype="multipart/form-data">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="is_3d_printing" value="1">

                        <!-- Escala -->
                        <div class="mb-4">
                            <label for="scale" class="form-label fw-bold">Escala</label>
                            <p class="text-muted small mb-2">Escolha a escala da sua miniatura. A escala padrão é <?= $product['scale'] ?>.</p>
                            <select class="form-select" id="scale" name="customization[scale]" data-price-factor="1">
                                <option value="28mm" data-price-factor="1.0" <?= $product['scale'] === '28mm' ? 'selected' : '' ?>>28mm (Padrão)</option>
                                <option value="32mm" data-price-factor="1.2" <?= $product['scale'] === '32mm' ? 'selected' : '' ?>>32mm (Heroic) +20%</option>
                                <option value="54mm" data-price-factor="2.5" <?= $product['scale'] === '54mm' ? 'selected' : '' ?>>54mm (Colecionável) +150%</option>
                            </select>
                        </div>

                        <!-- Tipo de Filamento -->
                        <div class="mb-4">
                            <label for="filament_type" class="form-label fw-bold">Tipo de Filamento</label>
                            <p class="text-muted small mb-2">Escolha o tipo de filamento para sua impressão.</p>
                            <select class="form-select" id="filament_type" name="customization[filament_type]" data-price-factor="1">
                                <option value="PLA" data-price-factor="1.0" <?= $product['filament_type'] === 'PLA' ? 'selected' : '' ?>>PLA - Material padrão, biodegradável e fácil de imprimir</option>
                                <option value="PETG" data-price-factor="1.25" <?= $product['filament_type'] === 'PETG' ? 'selected' : '' ?>>PETG - Mais durável e resistente a impactos (+25%)</option>
                                <option value="ABS" data-price-factor="1.4" <?= $product['filament_type'] === 'ABS' ? 'selected' : '' ?>>ABS - Alta resistência a impactos e temperatura (+40%)</option>
                                <option value="TPU" data-price-factor="1.6" <?= $product['filament_type'] === 'TPU' ? 'selected' : '' ?>>TPU - Flexível e elástico (+60%)</option>
                            </select>
                        </div>

                        <!-- Cores de Filamento -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Cor do Filamento</label>
                            <p class="text-muted small mb-2">Escolha a cor principal do seu modelo.</p>
                            <div class="filament-colors-grid">
                                <?php 
                                $filamentModel = new FilamentModel();
                                $selectedFilamentType = $product['filament_type'] ?? 'PLA';
                                $colors = $filamentModel->getColors($selectedFilamentType);
                                foreach ($colors as $color):
                                ?>
                                <div class="form-check filament-color-option">
                                    <input class="form-check-input filament-color-radio" type="radio" 
                                           name="customization[color]" id="color_<?= $color['id'] ?>" 
                                           value="<?= $color['id'] ?>" data-hex="<?= $color['hex_code'] ?>"
                                           <?= $color['id'] == 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="color_<?= $color['id'] ?>">
                                        <span class="color-swatch" style="background-color: <?= $color['hex_code'] ?>;"></span>
                                        <span class="color-name"><?= $color['name'] ?></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Qualidade de Impressão -->
                        <div class="mb-4">
                            <label for="quality" class="form-label fw-bold">Qualidade de Impressão</label>
                            <p class="text-muted small mb-2">Escolha a qualidade de impressão. Maior qualidade significa camadas mais finas e mais detalhes, mas tempo de impressão maior.</p>
                            <select class="form-select" id="quality" name="customization[quality]" data-price-factor="1">
                                <option value="standard" data-price-factor="1.0">Padrão - 0.2mm (Recomendado)</option>
                                <option value="high" data-price-factor="1.3">Alta - 0.12mm (+30%)</option>
                                <option value="ultra" data-price-factor="1.5">Ultra - 0.08mm (+50%)</option>
                            </select>
                        </div>

                        <!-- Opções Adicionais -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Opções Adicionais</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input price-addon" type="checkbox" id="support" name="customization[support]" data-price-addon="5">
                                <label class="form-check-label" for="support">
                                    Suporte/Base personalizada (+R$ 5,00)
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input price-addon" type="checkbox" id="polishing" name="customization[polishing]" data-price-addon="10">
                                <label class="form-check-label" for="polishing">
                                    Polimento e acabamento adicional (+R$ 10,00)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input price-addon" type="checkbox" id="painting" name="customization[painting]" data-price-addon="25">
                                <label class="form-check-label" for="painting">
                                    Pintura básica (+R$ 25,00)
                                </label>
                            </div>
                        </div>

                        <!-- Customizações Especiais -->
                        <?php if ($product['is_customizable']): ?>
                        <div class="mb-4">
                            <label for="special_requests" class="form-label fw-bold">Customizações Especiais</label>
                            <p class="text-muted small mb-2">Informe quaisquer customizações especiais que você deseja para este modelo. Nossa equipe entrará em contato para confirmar a viabilidade.</p>
                            <textarea class="form-control" id="special_requests" name="customization[special_requests]" rows="3"></textarea>
                        </div>
                        
                        <!-- Upload de Modelo Próprio -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Upload de Modelo Próprio</label>
                            <p class="text-muted small mb-2">Se você possui seu próprio modelo 3D, faça o upload aqui. Formatos aceitos: STL, OBJ (máx. 50MB)</p>
                            <div class="custom-file-upload">
                                <input type="file" class="form-control custom-file-input" id="model_file" 
                                       name="customization[model_file]" accept=".stl,.obj">
                                <div class="form-text">Ao enviar seu modelo, você confirma que possui os direitos necessários para sua reprodução.</div>
                                <div class="form-text"><a href="<?= BASE_URL ?>pagina/termos-modelos-3d" target="_blank">Leia nossos termos para impressão de modelos personalizados</a></div>
                                
                                <!-- Preview do arquivo -->
                                <div class="file-preview d-none mt-2">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <i class="fa fa-file-code fa-2x text-primary"></i>
                                                </div>
                                                <div>
                                                    <p class="file-name mb-1 fw-bold"></p>
                                                    <p class="file-size mb-1 text-muted small"></p>
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-file">Remover</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Quantidade -->
                        <div class="mb-4">
                            <label for="quantity" class="form-label fw-bold">Quantidade</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" data-price-multiplier="1" required>
                        </div>
                        
                        <!-- Botões -->
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

<!-- Estilos específicos para personalização 3D -->
<style>
.filament-colors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.filament-color-option label {
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
}

.color-swatch {
    display: block;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    margin-bottom: 5px;
    border: 2px solid #ddd;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.filament-color-radio:checked + label .color-swatch {
    border-color: #0d6efd;
    box-shadow: 0 0 0 2px #0d6efd;
}

.color-name {
    font-size: 0.8rem;
    text-align: center;
}
</style>

<!-- JavaScript para Personalização 3D -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos do DOM
    const form = document.getElementById('customization3dForm');
    const scaleSelect = document.getElementById('scale');
    const filamentTypeSelect = document.getElementById('filament_type');
    const qualitySelect = document.getElementById('quality');
    const quantityInput = document.getElementById('quantity');
    const totalPriceElement = document.getElementById('total-price');
    const addonCheckboxes = document.querySelectorAll('.price-addon');
    
    // Preço base do produto
    const basePrice = <?= ($product['sale_price'] ?: $product['price']) ?>;
    
    // Atualizar cores disponíveis quando o tipo de filamento mudar
    filamentTypeSelect.addEventListener('change', function() {
        updateFilamentColors(this.value);
    });
    
    // Calcular preço com base nas opções selecionadas
    function calculateTotalPrice() {
        // Fatores de preço
        const scaleFactor = parseFloat(scaleSelect.options[scaleSelect.selectedIndex].dataset.priceFactor);
        const filamentFactor = parseFloat(filamentTypeSelect.options[filamentTypeSelect.selectedIndex].dataset.priceFactor);
        const qualityFactor = parseFloat(qualitySelect.options[qualitySelect.selectedIndex].dataset.priceFactor);
        
        // Calcular preço base com fatores
        let totalPrice = basePrice * scaleFactor * filamentFactor * qualityFactor;
        
        // Adicionar extras (checkboxes)
        addonCheckboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                totalPrice += parseFloat(checkbox.dataset.priceAddon);
            }
        });
        
        // Multiplicar pela quantidade
        const quantity = parseInt(quantityInput.value) || 1;
        totalPrice *= quantity;
        
        // Atualizar exibição
        totalPriceElement.textContent = '<?= getCurrencySymbol() ?> ' + totalPrice.toFixed(2).replace('.', ',');
    }
    
    // Atualizar cores disponíveis para o tipo de filamento selecionado
    function updateFilamentColors(filamentType) {
        // Aqui seria ideal fazer uma chamada AJAX para buscar as cores disponíveis
        // Para este exemplo, apenas simularemos desabilitando algumas cores
        const colorOptions = document.querySelectorAll('.filament-color-option');
        
        // Por enquanto, apenas simule diferentes disponibilidades
        if (filamentType === 'PLA') {
            colorOptions.forEach(option => option.style.display = 'block');
        } else if (filamentType === 'TPU') {
            // Supondo que TPU tenha menos opções de cores
            colorOptions.forEach((option, index) => {
                if (index > 5) option.style.display = 'none';
                else option.style.display = 'block';
            });
        } else {
            // Para outros tipos, mostrar um subconjunto diferente
            colorOptions.forEach((option, index) => {
                if (index % 3 === 0) option.style.display = 'none';
                else option.style.display = 'block';
            });
        }
        
        // Garantir que pelo menos uma opção esteja selecionada
        let hasSelected = false;
        document.querySelectorAll('.filament-color-radio:checked').forEach(radio => {
            if (radio.closest('.filament-color-option').style.display !== 'none') {
                hasSelected = true;
            }
        });
        
        if (!hasSelected) {
            const firstVisible = document.querySelector('.filament-color-option[style="display: block"] .filament-color-radio');
            if (firstVisible) firstVisible.checked = true;
        }
    }
    
    // Configurar tratamento de arquivos
    const modelFileInput = document.getElementById('model_file');
    if (modelFileInput) {
        modelFileInput.addEventListener('change', function() {
            const filePreview = this.closest('.custom-file-upload').querySelector('.file-preview');
            const fileName = filePreview.querySelector('.file-name');
            const fileSize = filePreview.querySelector('.file-size');
            
            if (this.files && this.files[0]) {
                const file = this.files[0];
                filePreview.classList.remove('d-none');
                fileName.textContent = file.name;
                
                // Formatar tamanho do arquivo
                const size = file.size;
                let formattedSize;
                if (size < 1024) formattedSize = size + ' bytes';
                else if (size < 1048576) formattedSize = (size / 1024).toFixed(2) + ' KB';
                else formattedSize = (size / 1048576).toFixed(2) + ' MB';
                
                fileSize.textContent = formattedSize;
            } else {
                filePreview.classList.add('d-none');
            }
        });
        
        // Botão remover arquivo
        const removeButton = document.querySelector('.remove-file');
        if (removeButton) {
            removeButton.addEventListener('click', function() {
                const fileUpload = this.closest('.custom-file-upload');
                const fileInput = fileUpload.querySelector('input[type="file"]');
                const filePreview = fileUpload.querySelector('.file-preview');
                
                fileInput.value = '';
                filePreview.classList.add('d-none');
            });
        }
    }
    
    // Adicionar listeners para cálculo de preço
    [scaleSelect, filamentTypeSelect, qualitySelect, ...addonCheckboxes, quantityInput].forEach(element => {
        element.addEventListener('change', calculateTotalPrice);
    });
    
    // Calcular preço inicial
    calculateTotalPrice();
    
    // Se o formulário for enviado, garantir que todo o cálculo esteja correto
    form.addEventListener('submit', function(e) {
        calculateTotalPrice();  // Recalcular para garantir
        
        // Você pode adicionar validações adicionais aqui
        // Por exemplo, verificar se o tamanho do arquivo é válido, etc.
    });
});
</script>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>