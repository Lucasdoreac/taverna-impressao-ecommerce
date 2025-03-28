/*
 * JavaScript para o painel administrativo do TAVERNA DA IMPRESSÃO
 */

document.addEventListener('DOMContentLoaded', function() {
    // Toggle do Sidebar
    const sidebarToggle = document.getElementById('sidebarCollapse');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('active');
        });
    }

    // Auto-dismiss para alertas
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Confirmação para ações de exclusão
    const deleteButtons = document.querySelectorAll('.btn-delete');
    if (deleteButtons) {
        deleteButtons.forEach(function(button) {
            button.addEventListener('click', function(event) {
                if (!confirm('Tem certeza que deseja excluir este item? Esta ação não pode ser desfeita.')) {
                    event.preventDefault();
                }
            });
        });
    }

    // Preview de imagem para uploads
    const imageInputs = document.querySelectorAll('.image-upload');
    if (imageInputs) {
        imageInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                const preview = document.querySelector(input.dataset.preview);
                if (preview) {
                    if (input.files && input.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                        };
                        reader.readAsDataURL(input.files[0]);
                    } else {
                        preview.src = '';
                        preview.style.display = 'none';
                    }
                }
            });
        });
    }

    // Inicializar Select2 se estiver disponível
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            theme: 'bootstrap-5'
        });
    }

    // Inicializar Summernote se estiver disponível
    if (typeof $.fn.summernote !== 'undefined') {
        $('.summernote').summernote({
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    }

    // Inicializar Dropzone se estiver disponível
    if (typeof Dropzone !== 'undefined') {
        // Configurações do Dropzone serão aplicadas aos elementos com a classe 'dropzone'
        Dropzone.autoDiscover = false;
        const dropzoneElements = document.querySelectorAll('.dropzone');
        
        dropzoneElements.forEach(function(element) {
            const myDropzone = new Dropzone(element, {
                url: element.dataset.url,
                maxFilesize: 5, // MB
                acceptedFiles: 'image/*',
                addRemoveLinks: true,
                dictDefaultMessage: 'Arraste imagens aqui ou clique para enviar',
                dictRemoveFile: 'Remover',
                dictCancelUpload: 'Cancelar',
                dictFileTooBig: 'O arquivo é muito grande ({{filesize}}MB). Tamanho máximo: {{maxFilesize}}MB.',
                dictInvalidFileType: 'Tipo de arquivo inválido.',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            myDropzone.on('success', function(file, response) {
                // Adicionar ID do arquivo ao elemento para uso posterior
                file.id = response.id;
                
                // Adicionar ID à lista de imagens (se existir)
                const imageListInput = document.getElementById('product_images');
                if (imageListInput) {
                    let currentImages = [];
                    if (imageListInput.value) {
                        currentImages = JSON.parse(imageListInput.value);
                    }
                    currentImages.push(response.id);
                    imageListInput.value = JSON.stringify(currentImages);
                }
            });
            
            myDropzone.on('removedfile', function(file) {
                if (file.id) {
                    // Remover ID da lista de imagens (se existir)
                    const imageListInput = document.getElementById('product_images');
                    if (imageListInput) {
                        let currentImages = [];
                        if (imageListInput.value) {
                            currentImages = JSON.parse(imageListInput.value);
                        }
                        const index = currentImages.indexOf(file.id);
                        if (index > -1) {
                            currentImages.splice(index, 1);
                            imageListInput.value = JSON.stringify(currentImages);
                        }
                    }
                    
                    // Enviar requisição para excluir o arquivo no servidor
                    fetch(element.dataset.deleteUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        },
                        body: JSON.stringify({ id: file.id })
                    });
                }
            });
        });
    }

    // Inicializar máscaras de input se inputmask estiver disponível
    if (typeof Inputmask !== 'undefined') {
        const inputElements = document.querySelectorAll('[data-inputmask]');
        inputElements.forEach(function(input) {
            const mask = input.dataset.inputmask;
            Inputmask(mask).mask(input);
        });
    }

    // Adicionar opções de personalização dinâmicas
    const addCustomizationButton = document.getElementById('add-customization');
    if (addCustomizationButton) {
        addCustomizationButton.addEventListener('click', function() {
            const container = document.getElementById('customization-options');
            const index = document.querySelectorAll('.customization-option').length;
            
            const template = `
                <div class="customization-option">
                    <div class="customization-option-header">
                        <h6>Nova Opção</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-customization">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" name="customization[${index}][name]" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="customization[${index}][type]" required>
                                <option value="text">Texto</option>
                                <option value="select">Seleção</option>
                                <option value="upload">Upload</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="customization[${index}][description]" rows="2"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="customization[${index}][required]" value="1" id="required_${index}">
                                <label class="form-check-label" for="required_${index}">
                                    Obrigatório
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="option-fields mb-3" style="display: none;">
                        <label class="form-label">Opções (uma por linha no formato "valor=texto")</label>
                        <textarea class="form-control" name="customization[${index}][options]" rows="3"></textarea>
                        <div class="form-text">Exemplo: 1=Azul, 2=Vermelho, 3=Verde</div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', template);
            initCustomizationEvents();
        });
    }

    // Inicializar eventos para opções de personalização
    function initCustomizationEvents() {
        // Remover opção de personalização
        document.querySelectorAll('.remove-customization').forEach(function(button) {
            button.addEventListener('click', function() {
                button.closest('.customization-option').remove();
            });
        });
        
        // Mostrar/ocultar campos de opções com base no tipo selecionado
        document.querySelectorAll('.customization-option select').forEach(function(select) {
            select.addEventListener('change', function() {
                const optionFields = select.closest('.customization-option').querySelector('.option-fields');
                if (select.value === 'select') {
                    optionFields.style.display = 'block';
                } else {
                    optionFields.style.display = 'none';
                }
            });
            
            // Trigger no carregamento
            if (select.value === 'select') {
                select.closest('.customization-option').querySelector('.option-fields').style.display = 'block';
            }
        });
    }

    // Inicializar eventos de personalização existentes
    initCustomizationEvents();

    // Geração de slug automática
    const slugSource = document.getElementById('slug-source');
    const slugTarget = document.getElementById('slug-target');
    
    if (slugSource && slugTarget) {
        slugSource.addEventListener('input', function() {
            const value = slugSource.value;
            
            // Gerar slug apenas se o campo de destino estiver vazio ou se tiver a classe 'auto-slug'
            if (slugTarget.value === '' || slugTarget.classList.contains('auto-slug')) {
                slugTarget.value = generateSlug(value);
                slugTarget.classList.add('auto-slug');
            }
        });
        
        // Remover a classe 'auto-slug' quando o usuário editar manualmente o slug
        slugTarget.addEventListener('input', function() {
            slugTarget.classList.remove('auto-slug');
        });
    }
    
    // Função auxiliar para gerar slug
    function generateSlug(text) {
        return text
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '') // Remover acentos
            .toLowerCase()
            .trim()
            .replace(/\s+/g, '-') // Espaços para hífens
            .replace(/[^\w\-]+/g, '') // Remover caracteres não alfanuméricos
            .replace(/\-\-+/g, '-') // Remover múltiplos hífens
            .replace(/^-+/, '') // Remover hífens do início
            .replace(/-+$/, ''); // Remover hífens do final
    }
});
