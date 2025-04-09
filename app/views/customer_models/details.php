<?php include_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="h4 mb-0">
                            <i class="fas <?= $model['file_type'] === 'stl' ? 'fa-cube' : 'fa-object-group' ?> me-2"></i>
                            Detalhes do Modelo
                        </h2>
                        <span class="badge fs-6 <?= 
                            $model['status'] === 'pending_validation' ? 'bg-warning' : 
                            ($model['status'] === 'approved' ? 'bg-success' : 'bg-danger') 
                        ?>">
                            <?= 
                                $model['status'] === 'pending_validation' ? 'Aguardando Validação' : 
                                ($model['status'] === 'approved' ? 'Aprovado' : 'Rejeitado') 
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php include_once VIEWS_PATH . '/partials/flash_messages.php'; ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h3 class="h5 mb-3">Informações do Modelo</h3>
                            
                            <div class="row mb-2">
                                <div class="col-md-4 fw-bold">Nome Original:</div>
                                <div class="col-md-8"><?= htmlspecialchars($model['original_name']) ?></div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-md-4 fw-bold">Tipo de Arquivo:</div>
                                <div class="col-md-8"><?= strtoupper(htmlspecialchars($model['file_type'])) ?></div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-md-4 fw-bold">Tamanho:</div>
                                <div class="col-md-8"><?= number_format($model['file_size'] / 1024 / 1024, 2) ?> MB</div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-md-4 fw-bold">Data de Envio:</div>
                                <div class="col-md-8"><?= date('d/m/Y H:i', strtotime($model['created_at'])) ?></div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-md-4 fw-bold">Status Atual:</div>
                                <div class="col-md-8">
                                    <span class="badge <?= 
                                        $model['status'] === 'pending_validation' ? 'bg-warning' : 
                                        ($model['status'] === 'approved' ? 'bg-success' : 'bg-danger') 
                                    ?>">
                                        <?= 
                                            $model['status'] === 'pending_validation' ? 'Aguardando Validação' : 
                                            ($model['status'] === 'approved' ? 'Aprovado' : 'Rejeitado') 
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if (!empty($model['updated_at'])): ?>
                                <div class="row mb-2">
                                    <div class="col-md-4 fw-bold">Última Atualização:</div>
                                    <div class="col-md-8"><?= date('d/m/Y H:i', strtotime($model['updated_at'])) ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($model['notes'])): ?>
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="fw-bold mb-2">Observações:</div>
                                        <div class="p-3 bg-light rounded">
                                            <?= nl2br(htmlspecialchars($model['notes'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($model['status'] === 'rejected' && !empty($model['admin_notes'])): ?>
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="alert alert-danger">
                                            <h4 class="h6">Motivo da Rejeição:</h4>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($model['admin_notes'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4">
                            <?php if (isset($model['validation_data']) && !empty($model['validation_data'])): ?>
                                <h3 class="h5 mb-3">Detalhes Técnicos</h3>
                                
                                <?php 
                                $metadata = isset($model['validation_data']['metadata']) ? 
                                    $model['validation_data']['metadata'] : 
                                    $model['validation_data'];
                                    
                                if (is_array($metadata)):
                                ?>
                                    <div class="card">
                                        <div class="list-group list-group-flush">
                                            <?php if (isset($metadata['format'])): ?>
                                                <div class="list-group-item">
                                                    <small class="text-muted d-block">Formato:</small>
                                                    <strong><?= htmlspecialchars($metadata['format']) ?></strong>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($metadata['triangles'])): ?>
                                                <div class="list-group-item">
                                                    <small class="text-muted d-block">Triângulos:</small>
                                                    <strong><?= number_format($metadata['triangles']) ?></strong>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($metadata['vertices'])): ?>
                                                <div class="list-group-item">
                                                    <small class="text-muted d-block">Vértices:</small>
                                                    <strong><?= number_format($metadata['vertices']) ?></strong>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($metadata['faces'])): ?>
                                                <div class="list-group-item">
                                                    <small class="text-muted d-block">Faces:</small>
                                                    <strong><?= number_format($metadata['faces']) ?></strong>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($metadata['width']) && isset($metadata['height']) && isset($metadata['depth'])): ?>
                                                <div class="list-group-item">
                                                    <small class="text-muted d-block">Dimensões (unidades do modelo):</small>
                                                    <strong>
                                                        <?= number_format($metadata['width'], 2) ?> x 
                                                        <?= number_format($metadata['height'], 2) ?> x 
                                                        <?= number_format($metadata['depth'], 2) ?>
                                                    </strong>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($metadata['model_name'])): ?>
                                                <div class="list-group-item">
                                                    <small class="text-muted d-block">Nome do Modelo:</small>
                                                    <strong><?= htmlspecialchars($metadata['model_name']) ?></strong>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($model['status'] === 'pending_validation'): ?>
                                <div class="alert alert-info mt-4">
                                    <h4 class="h6"><i class="fas fa-info-circle me-2"></i>Aguardando Verificação</h4>
                                    <p class="small mb-0">Seu modelo está na fila para análise técnica. Este processo geralmente leva até 24 horas úteis.</p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($model['status'] === 'approved'): ?>
                                <div class="alert alert-success mt-4">
                                    <h4 class="h6"><i class="fas fa-check-circle me-2"></i>Modelo Aprovado</h4>
                                    <p class="small mb-0">Seu modelo foi aprovado e está pronto para impressão. Você pode solicitar a impressão a partir da sua área de cliente.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer bg-light d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>customer-models/list" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Voltar para Lista
                    </a>
                    
                    <div>
                        <?php if ($model['status'] === 'approved'): ?>
                            <a href="<?= BASE_URL ?>viewer3d/view/<?= $model['id'] ?>" class="btn btn-primary me-2">
                                <i class="fas fa-cube me-1"></i> Visualizar em 3D
                            </a>
                            <a href="<?= BASE_URL ?>print-queue/add/<?= $model['id'] ?>" class="btn btn-success me-2">
                                <i class="fas fa-print me-1"></i> Solicitar Impressão
                            </a>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-danger" 
                                onclick="confirmDelete(<?= $model['id'] ?>, '<?= htmlspecialchars($model['original_name']) ?>')">
                            <i class="fas fa-trash-alt me-1"></i> Excluir Modelo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="deleteModelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o modelo <strong id="modelName"></strong>?</p>
                <p class="mb-0">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" action="" method="post">
                    <input type="hidden" name="csrf_token" value="<?= CsrfProtection::getToken() ?>">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(modelId, modelName) {
        document.getElementById('modelName').textContent = modelName;
        document.getElementById('deleteForm').action = '<?= BASE_URL ?>customer-models/delete/' + modelId;
        
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModelModal'));
        deleteModal.show();
    }
</script>

<?php include_once VIEWS_PATH . '/partials/footer.php'; ?>
