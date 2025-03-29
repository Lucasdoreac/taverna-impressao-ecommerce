<?php include_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0"><i class="fas fa-upload me-2"></i> Upload de Modelo 3D</h2>
                </div>
                <div class="card-body">
                    <?php include_once VIEWS_PATH . '/partials/flash_messages.php'; ?>
                    
                    <p class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> 
                        Envie seu modelo 3D para impressão personalizada. Aceitamos arquivos STL e OBJ com até 50MB.
                        Nossa equipe irá analisar seu modelo para verificar a viabilidade de impressão.
                    </p>
                    
                    <form action="<?= BASE_URL ?>customer-models/process-upload" method="post" enctype="multipart/form-data" id="uploadForm">
                        <div class="mb-4">
                            <label for="model_file" class="form-label">Arquivo 3D (STL ou OBJ)</label>
                            <input type="file" class="form-control" id="model_file" name="model_file" accept=".stl, .obj" required>
                            <div class="form-text">Formatos aceitos: STL, OBJ. Tamanho máximo: 50MB</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="notes" class="form-label">Instruções ou Observações (opcional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Descreva detalhes importantes sobre seu modelo, como escala desejada, cor, etc."></textarea>
                        </div>
                        
                        <div class="mb-4 p-3 border rounded bg-light">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="terms_check" required>
                                <label class="form-check-label" for="terms_check">
                                    Confirmo que possuo os direitos de uso deste modelo 3D e concordo com os 
                                    <a href="<?= BASE_URL ?>termos-modelos-3d" target="_blank">termos de uso</a>.
                                </label>
                            </div>
                            
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="print_check" required>
                                <label class="form-check-label" for="print_check">
                                    Entendo que a TAVERNA DA IMPRESSÃO avaliará a viabilidade de impressão do modelo 
                                    e poderá recusar a impressão caso não seja tecnicamente possível.
                                </label>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Importante:</strong> Após enviar seu modelo, ele passará por uma análise técnica. 
                            Você receberá uma notificação quando a análise for concluída. 
                            Os modelos aprovados serão adicionados à fila de impressão.
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?= BASE_URL ?>customer-models/list" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-1"></i> Voltar
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-upload me-1"></i> Enviar Modelo
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="card-footer bg-light">
                    <h5 class="mb-3">Recomendações para modelos 3D:</h5>
                    <ul class="list-group list-group-flush mb-0">
                        <li class="list-group-item bg-transparent">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Certifique-se de que seu modelo é estanque (watertight) e não possui erros de geometria.
                        </li>
                        <li class="list-group-item bg-transparent">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Modelos com base plana são mais fáceis de imprimir e requerem menos suportes.
                        </li>
                        <li class="list-group-item bg-transparent">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Evite detalhes muito pequenos que podem não ser capturados pela impressora.
                        </li>
                        <li class="list-group-item bg-transparent">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Se possível, informe a escala desejada nas observações (padrão: 28mm).
                        </li>
                    </ul>
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
        const maxFileSize = 52428800; // 50MB em bytes
        
        fileInput.addEventListener('change', function() {
            if (this.files[0].size > maxFileSize) {
                alert('O arquivo é muito grande. O tamanho máximo permitido é 50MB.');
                this.value = ''; // Limpar o input
            }
        });
        
        // Progress para uploads grandes
        const uploadForm = document.getElementById('uploadForm');
        uploadForm.addEventListener('submit', function() {
            if (fileInput.files.length > 0) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Enviando...';
            }
        });
    });
</script>

<?php include_once VIEWS_PATH . '/partials/footer.php'; ?>
