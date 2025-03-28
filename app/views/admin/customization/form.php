<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <?= isset($option) ? 'Editar' : 'Nova' ?> Opção de Personalização
        </h1>
        <a href="<?= BASE_URL ?>admin/customization" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <?= isset($option) ? 'Editar' : 'Cadastrar' ?> Opção de Personalização
            </h6>
        </div>
        <div class="card-body">
            <form method="post" action="<?= isset($option) ? BASE_URL . 'admin/customization/update/' . $option['id'] : BASE_URL . 'admin/customization/store' ?>">
                <!-- Token CSRF -->
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                
                <!-- Recuperar dados do formulário caso haja erro -->
                <?php
                $formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
                unset($_SESSION['form_data']);
                
                // Definir valores
                $productIdValue = isset($formData['product_id']) ? $formData['product_id'] : 
                                  (isset($option) ? $option['product_id'] : 
                                  (isset($selectedProductId) ? $selectedProductId : ''));
                                  
                $nameValue = isset($formData['name']) ? $formData['name'] : 
                             (isset($option) ? $option['name'] : '');
                             
                $descriptionValue = isset($formData['description']) ? $formData['description'] : 
                                   (isset($option) ? $option['description'] : '');
                                   
                $typeValue = isset($formData['type']) ? $formData['type'] : 
                             (isset($option) ? $option['type'] : 'text');
                             
                $requiredValue = isset($formData['required']) ? $formData['required'] : 
                                (isset($option) ? $option['required'] : false);
                                
                $optionsValue = isset($formData['options']) ? $formData['options'] : 
                               (isset($formattedOptions) ? $formattedOptions : '');
                ?>
                
                <div class="row">
                    <!-- Produto -->
                    <div class="col-md-6 mb-4">
                        <label for="product_id" class="form-label">Produto <span class="text-danger">*</span></label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value="">Selecione um produto...</option>
                            <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>" <?= $productIdValue == $product['id'] ? 'selected' : '' ?>>
                                <?= $product['name'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            <small>Apenas produtos marcados como "Personalizável" são exibidos.</small>
                        </div>
                    </div>
                    
                    <!-- Nome da Opção -->
                    <div class="col-md-6 mb-4">
                        <label for="name" class="form-label">Nome da Opção <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?= $nameValue ?>" required maxlength="100">
                        <div class="form-text">
                            <small>Este nome será exibido para o cliente.</small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Tipo -->
                    <div class="col-md-6 mb-4">
                        <label for="type" class="form-label">Tipo de Campo <span class="text-danger">*</span></label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="text" <?= $typeValue === 'text' ? 'selected' : '' ?>>Campo de Texto</option>
                            <option value="upload" <?= $typeValue === 'upload' ? 'selected' : '' ?>>Upload de Arquivo</option>
                            <option value="select" <?= $typeValue === 'select' ? 'selected' : '' ?>>Seleção de Opções</option>
                        </select>
                    </div>
                    
                    <!-- Obrigatório -->
                    <div class="col-md-6 mb-4">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="required" name="required" 
                                   <?= $requiredValue ? 'checked' : '' ?>>
                            <label class="form-check-label" for="required">
                                Campo Obrigatório
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Descrição -->
                <div class="mb-4">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= $descriptionValue ?></textarea>
                    <div class="form-text">
                        <small>Esta descrição ajudará o cliente a entender o propósito deste campo.</small>
                    </div>
                </div>
                
                <!-- Opções (para tipo "select") -->
                <div id="options-container" class="mb-4 <?= $typeValue !== 'select' ? 'd-none' : '' ?>">
                    <label for="options" class="form-label">Opções de Seleção <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="options" name="options" rows="5"><?= $optionsValue ?></textarea>
                    <div class="form-text">
                        <small>
                            Digite uma opção por linha no formato "valor: texto de exibição".<br>
                            Exemplo:<br>
                            <code>azul: Azul Royal</code><br>
                            <code>vermelho: Vermelho Escuro</code><br>
                            <code>preto: Preto Fosco</code>
                        </small>
                    </div>
                </div>
                
                <!-- Upload Info (para tipo "upload") -->
                <div id="upload-info" class="mb-4 alert alert-info <?= $typeValue !== 'upload' ? 'd-none' : '' ?>">
                    <h6 class="alert-heading">Informações sobre Upload</h6>
                    <p class="mb-0">
                        <small>
                            Para campos de upload, o cliente poderá enviar arquivos nos formatos:
                            <ul class="mb-0">
                                <li>PDF (recomendado para material impresso)</li>
                                <li>JPG/JPEG (resolução mínima recomendada: 300 DPI)</li>
                                <li>PNG (com transparência se necessário)</li>
                            </ul>
                            O tamanho máximo permitido é de 10MB por arquivo.
                        </small>
                    </p>
                </div>
                
                <hr class="mt-4 mb-4">
                
                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>admin/customization" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <?= isset($option) ? 'Atualizar' : 'Salvar' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const optionsContainer = document.getElementById('options-container');
    const uploadInfo = document.getElementById('upload-info');
    const optionsField = document.getElementById('options');
    
    // Função para mostrar/esconder campos com base no tipo selecionado
    function toggleFields() {
        const type = typeSelect.value;
        
        if (type === 'select') {
            optionsContainer.classList.remove('d-none');
            uploadInfo.classList.add('d-none');
            optionsField.setAttribute('required', 'required');
        } else if (type === 'upload') {
            optionsContainer.classList.add('d-none');
            uploadInfo.classList.remove('d-none');
            optionsField.removeAttribute('required');
        } else {
            optionsContainer.classList.add('d-none');
            uploadInfo.classList.add('d-none');
            optionsField.removeAttribute('required');
        }
    }
    
    // Inicializar com base no valor atual
    toggleFields();
    
    // Atualizar quando o tipo mudar
    typeSelect.addEventListener('change', toggleFields);
});
</script>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>
