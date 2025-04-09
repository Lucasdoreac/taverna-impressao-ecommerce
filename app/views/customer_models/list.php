<?php include_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-3">
            <!-- Sidebar de navegação -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="h5 mb-0">Meus Modelos 3D</h3>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>customer-models/list" class="list-group-item list-group-item-action <?= !isset($status) ? 'active' : '' ?>">
                        Todos os Modelos
                    </a>
                    <a href="<?= BASE_URL ?>customer-models/list?status=pending_validation" class="list-group-item list-group-item-action <?= $status === 'pending_validation' ? 'active' : '' ?>">
                        Aguardando Aprovação
                    </a>
                    <a href="<?= BASE_URL ?>customer-models/list?status=approved" class="list-group-item list-group-item-action <?= $status === 'approved' ? 'active' : '' ?>">
                        Aprovados
                    </a>
                    <a href="<?= BASE_URL ?>customer-models/list?status=rejected" class="list-group-item list-group-item-action <?= $status === 'rejected' ? 'active' : '' ?>">
                        Rejeitados
                    </a>
                    <a href="<?= BASE_URL ?>customer-models/upload" class="list-group-item list-group-item-action bg-light">
                        <i class="fas fa-upload me-2"></i> Enviar Novo Modelo
                    </a>
                </div>
            </div>
            
            <!-- Informações de cota de armazenamento -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="h5 mb-0">Uso de Armazenamento</h3>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Espaço Utilizado:</span>
                            <span class="fw-bold"><?= $quotaInfo['usedSpaceFormatted'] ?></span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar <?= $quotaInfo['percentUsed'] > 80 ? 'bg-danger' : 'bg-success' ?>" 
                                 role="progressbar" 
                                 style="width: <?= $quotaInfo['percentUsed'] ?>%;" 
                                 aria-valuenow="<?= $quotaInfo['percentUsed'] ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted">
                            <?= $quotaInfo['usedSpaceFormatted'] ?> de <?= $quotaInfo['maxQuotaFormatted'] ?> (<?= number_format($quotaInfo['percentUsed'], 1) ?>%)
                        </small>
                    </div>
                    
                    <?php if ($quotaInfo['percentUsed'] > 80): ?>
                        <div class="alert alert-warning mt-3 mb-0 py-2 small">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Seu espaço está quase esgotado. Considere remover modelos antigos.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <!-- Cabeçalho da página -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h3 mb-0">
                    <?php if ($status === 'pending_validation'): ?>
                        Modelos Aguardando Aprovação
                    <?php elseif ($status === 'approved'): ?>
                        Modelos Aprovados
                    <?php elseif ($status === 'rejected'): ?>
                        Modelos Rejeitados
                    <?php else: ?>
                        Todos os Meus Modelos
                    <?php endif; ?>
                </h2>
                
                <a href="<?= BASE_URL ?>customer-models/upload" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Novo Modelo
                </a>
            </div>
            
            <?php include_once VIEWS_PATH . '/partials/flash_messages.php'; ?>
            
            <?php if (empty($models)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> 
                    Você ainda não possui modelos 
                    <?php if ($status === 'pending_validation'): ?>
                        aguardando aprovação.
                    <?php elseif ($status === 'approved'): ?>
                        aprovados.
                    <?php elseif ($status === 'rejected'): ?>
                        rejeitados.
                    <?php else: ?>
                        cadastrados.
                    <?php endif; ?>
                </div>
                
                <div class="text-center py-4">
                    <p class="mb-3">Comece enviando seu primeiro modelo 3D para impressão!</p>
                    <a href="<?= BASE_URL ?>customer-models/upload" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i> Enviar Modelo
                    </a>
                </div>
            <?php else: ?>
                <!-- Lista de modelos -->
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php foreach ($models as $model): ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h3 class="h6 mb-0 text-truncate" title="<?= htmlspecialchars($model['original_name']) ?>">
                                        <i class="fas <?= $model['file_type'] === 'stl' ? 'fa-cube' : 'fa-object-group' ?> me-2"></i>
                                        <?= htmlspecialchars($model['original_name']) ?>
                                    </h3>
                                    <span class="badge <?= 
                                        $model['status'] === 'pending_validation' ? 'bg-warning' : 
                                        ($model['status'] === 'approved' ? 'bg-success' : 'bg-danger') 
                                    ?>">
                                        <?= 
                                            $model['status'] === 'pending_validation' ? 'Pendente' : 
                                            ($model['status'] === 'approved' ? 'Aprovado' : 'Rejeitado') 
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="card-body">
                                    <div class="mb-2">
                                        <small class="text-muted">Enviado em:</small>
                                        <div><?= date('d/m/Y H:i', strtotime($model['created_at'])) ?></div>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted">Formato:</small>
                                        <div class="text-uppercase"><?= htmlspecialchars($model['file_type']) ?></div>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted">Tamanho:</small>
                                        <div><?= number_format($model['file_size'] / 1024 / 1024, 2) ?> MB</div>
                                    </div>
                                    
                                    <?php if (!empty($model['notes'])): ?>
                                        <div class="mb-2">
                                            <small class="text-muted">Observações:</small>
                                            <div class="text-truncate" style="max-height: 60px; overflow: hidden;">
                                                <?= htmlspecialchars($model['notes']) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($model['status'] === 'rejected' && !empty($model['admin_notes'])): ?>
                                        <div class="alert alert-danger p-2 mt-2 mb-0">
                                            <small class="d-block fw-bold">Motivo da rejeição:</small>
                                            <?= htmlspecialchars($model['admin_notes']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-footer bg-white border-top d-flex justify-content-between">
                                    <div>
                                        <a href="<?= BASE_URL ?>customer-models/details/<?= $model['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-info-circle me-1"></i> Detalhes
                                        </a>
                                        
                                        <?php if ($model['status'] === 'approved'): ?>
                                        <a href="<?= BASE_URL ?>viewer3d/view/<?= $model['id'] ?>" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-cube me-1"></i> Ver 3D
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="confirmDelete(<?= $model['id'] ?>, '<?= htmlspecialchars($model['original_name']) ?>')">
                                        <i class="fas fa-trash-alt me-1"></i> Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
