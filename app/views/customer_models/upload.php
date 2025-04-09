<?php include_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0"><i class="fas fa-upload me-2"></i> Upload de Modelo 3D</h2>
                </div>
                <div class="card-body">
                    <?php include_once VIEWS_PATH . '/partials/flash_messages.php'; ?>
                    
                    <!-- Informações de Cota e Taxa -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body p-3">
                                    <h6 class="card-title mb-2">Espaço Disponível</h6>
                                    <div class="progress mb-2" style="height: 10px;">
                                        <div class="progress-bar <?= $quotaInfo['percentUsed'] > 80 ? 'bg-danger' : 'bg-success' ?>" 
                                             role="progressbar" 
                                             style="width: <?= $quotaInfo['percentUsed'] ?>%;" 
                                             aria-valuenow="<?= $quotaInfo['percentUsed'] ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?= $quotaInfo['usedSpaceFormatted'] ?> usado de <?= $quotaInfo['maxQuotaFormatted'] ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body p-3">
                                    <h6 class="card-title mb-2">Uploads Disponíveis</h6>
                                    <div class="d-flex align-items-center">
                                        <div class="display-4 me-3 text-<?= $rateLimitInfo['remainingUploads'] > 2 ? 'success' : 'warning' ?>">
                                            <?= $rateLimitInfo['remainingUploads'] ?>
                                        </div>
                                        <div class="small text-muted">
                                            restantes na última hora<br>
                                            (limite de <?= $rateLimitInfo['maxUploadsPerHour'] ?> por hora)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mb-4">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fas fa-info-circle fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading mb-1">Informações sobre Upload de Modelos 3D</h5>
                                <p class="mb-0">
                                    Envie seu modelo 3D para impressão personalizada. Formatos aceitos: <strong>STL</strong>, <strong>OBJ</strong> e <strong>3MF</strong>. 
                                    Tamanho máximo: <strong>50 MB</strong>.
                                </p>
                                <p class="mb-0">
                                    Seu modelo passará por uma validação automática e análise técnica antes de ser aprovado para impressão.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <form action="<?= BASE_URL ?>customer-models/process-upload" method="post" enctype="multipart/form-data" id="uploadForm">
                        <div class="mb-4">
                            <label for="model_file" class="form-label fw-bold">Arquivo 3D (STL, OBJ ou 3MF) <span class="text-danger">*</span></label>
                            <div class="input-group mb-1">
                                <input type="file" class="form-control" id="model_file" name="model_file" accept=".stl,.obj,.3mf" required>
                            </div>
                            <div id="file-feedback" class="form-text">Formatos aceitos: STL, OBJ, 3MF. Tamanho máximo: 50MB</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="notes" class="form-label fw-bold">Instruções ou Observações</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Descreva detalhes importantes sobre seu modelo, como escala desejada, cor, material preferido ou outras especificações..."></textarea>
                            <div class="form-text">
                                Estas informações serão úteis para nossa equipe durante a análise e impressão do seu modelo.
                            </div>
                        </div>
                        
                        <div class="mb-4 p-3 border rounded bg-light">
                            <h5 class="mb-3 h6">Termos e Condições</h5>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="rights_check" name="rights_confirmation" value="1" required>
                                <label class="form-check-label" for="rights_check">
                                    <span class="fw-bold">Confirmação de Direitos:</span> Confirmo que possuo os direitos de uso deste modelo 3D ou tenho permissão para reproduzi-lo.
                                </label>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="terms_check" name="terms_confirmation" value="1" required>
                                <label class="form-check-label" for="terms_check">
                                    <span class="fw-bold">Termos de Uso:</span> Concordo com os 
                                    <a href="<?= BASE_URL ?>termos-modelos-3d" target="_blank">termos de uso</a> da Taverna da Impressão 3D.
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="review_check" name="review_confirmation" value="1" required>
                                <label class="form-check-label" for="review_check">
                                    <span class="fw-bold">Avaliação Técnica:</span> Entendo que meu modelo passará por uma avaliação técnica e poderá ser rejeitado caso não seja adequado para impressão.
                                </label>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mb-4">
                            <div class="d-flex">
                                <div class="me-3">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                                <div>
                                    <h5 class="alert-heading mb-1">Importante</h5>
                                    <p class="mb-0">
                                        Após o envio, seu modelo passará por uma análise técnica que pode levar até 24 horas úteis.
                                        Você receberá uma notificação assim que a análise for concluída.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Token CSRF -->
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>customer-models/list" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Voltar
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-upload me-1"></i> Enviar Modelo
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="card-footer bg-light">
                    <h5 class="mb-3 h6 fw-bold">Recomendações para modelos 3D:</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item bg-transparent py-1 ps-0 border-0">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Verifique se o modelo é estanque (watertight)
                                </li>
                                <li class="list-group-item bg-transparent py-1 ps-0 border-0">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Modelos com base plana requerem menos suportes
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-group list-group-flush mb-0">
                                <li class="list-group-item bg-transparent py-1 ps-0 border-0">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Evite detalhes muito pequenos (< 0.8mm)
                                </li>
                                <li class="list-group-item bg-transparent py-1 ps-0 border-0">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Informe a escala desejada nas observações
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4 shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="h5 mb-0">Especificações Técnicas</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">Formatos Suportados</h6>
                            <ul class="list-unstyled ms-3">
                                <li><i class="fas fa-file-code me-2 text-primary"></i><strong>STL</strong> - Formato padrão para impressão 3D</li>
                                <li><i class="fas fa-file-code me-2 text-primary"></i><strong>OBJ</strong> - Vertices, faces e texturas</li>
                                <li><i class="fas fa-file-code me-2 text-primary"></i><strong>3MF</strong> - Formato aberto baseado em XML</li>
                            </ul>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">Limites Técnicos</h6>
                            <ul class="list-unstyled ms-3">
                                <li><i class="fas fa-ruler me-2 text-primary"></i><strong>Tamanho máximo:</strong> 50 MB</li>
                                <li><i class="fas fa-cubes me-2 text-primary"></i><strong>Volume de impressão:</strong> até 220x220x250mm</li>
                                <li><i class="fas fa-th me-2 text-primary"></i><strong>Resolução mínima:</strong> 0.1mm</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validação de tamanho do arquivo
    const fileInput = document.getElementById('model_file');
    const submitBtn = document.getElementById('submitBtn');
    const feedbackElement = document.getElementById('file-feedback');
    const maxFileSize = <?= $maxFileSize ?>;
    
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            const fileExt = file.name.split('.').pop().toLowerCase();
            
            // Verificar tamanho
            if (file.size > maxFileSize) {
                this.value = ''; // Limpar o input
                feedbackElement.innerHTML = '<span class="text-danger">O arquivo é muito grande. O tamanho máximo permitido é 50MB.</span>';
                return;
            }
            
            // Verificar extensão
            const allowedExtensions = <?= json_encode($allowedExtensions) ?>;
            if (!allowedExtensions.includes(fileExt)) {
                this.value = ''; // Limpar o input
                feedbackElement.innerHTML = '<span class="text-danger">Formato de arquivo não permitido. Use apenas STL, OBJ ou 3MF.</span>';
                return;
            }
            
            // Arquivo válido
            feedbackElement.innerHTML = `<span class="text-success">Arquivo válido: ${(file.size / (1024 * 1024)).toFixed(2)} MB</span>`;
        }
    });
    
    // Configurar progresso para uploads grandes
    const uploadForm = document.getElementById('uploadForm');
    uploadForm.addEventListener('submit', function(e) {
        if (fileInput.files.length > 0) {
            // Verificar se todos os checkboxes estão marcados
            const checkboxes = document.querySelectorAll('input[type="checkbox"][required]');
            let allChecked = true;
            
            checkboxes.forEach(function(checkbox) {
                if (!checkbox.checked) {
                    allChecked = false;
                }
            });
            
            if (!allChecked) {
                return; // Deixe o navegador lidar com a validação
            }
            
            // Desativar o botão e mostrar progresso
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Enviando...';
            
            // Adicionar progresso visual
            const progressContainer = document.createElement('div');
            progressContainer.className = 'progress mt-3';
            progressContainer.style.height = '20px';
            
            const progressBar = document.createElement('div');
            progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated';
            progressBar.style.width = '100%';
            progressBar.setAttribute('role', 'progressbar');
            
            progressContainer.appendChild(progressBar);
            uploadForm.appendChild(progressContainer);
        }
    });
});
</script>

<?php include_once VIEWS_PATH . '/partials/footer.php'; ?>
