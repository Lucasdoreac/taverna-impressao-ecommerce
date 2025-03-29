<?php include_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0"><i class="fas fa-tasks me-2"></i> Modelos 3D Pendentes de Validação</h2>
            <a href="<?= BASE_URL ?>admin/dashboard" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Voltar para Dashboard
            </a>
        </div>
        
        <div class="card-body">
            <?php include_once VIEWS_PATH . '/partials/flash_messages.php'; ?>
            
            <?php if (empty($models)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Não há modelos 3D pendentes de validação no momento.
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong><?= count($models) ?> modelo(s)</strong> aguardando validação. Verifique cuidadosamente cada modelo 
                antes de aprovar ou rejeitar.
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Arquivo</th>
                            <th>Tipo</th>
                            <th>Tamanho</th>
                            <th>Data de Envio</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($models as $model): ?>
                        <tr>
                            <td>#<?= $model['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user text-secondary me-2"></i>
                                    <?= htmlspecialchars($model['user_name']) ?>
                                    <span class="badge bg-secondary ms-2"><?= $model['user_email'] ?></span>
                                </div>
                            </td>
                            <td class="text-truncate" style="max-width: 200px;">
                                <i class="fas fa-file-<?= $model['file_type'] === 'stl' ? 'code' : 'image' ?> text-primary me-2"></i>
                                <?= htmlspecialchars($model['original_name']) ?>
                            </td>
                            <td class="text-uppercase"><?= $model['file_type'] ?></td>
                            <td><?= formatFileSize($model['file_size']) ?></td>
                            <td><?= formatDate($model['created_at']) ?></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>customer-models/details/<?= $model['id'] ?>" class="btn btn-sm btn-primary" title="Ver detalhes">
                                    <i class="fas fa-eye me-1"></i> Verificar
                                </a>
                                
                                <div class="btn-group ms-1">
                                    <button type="button" class="btn btn-sm btn-success" title="Aprovar modelo"
                                            onclick="approveModel(<?= $model['id'] ?>, '<?= htmlspecialchars($model['original_name']) ?>')">
                                        <i class="fas fa-check me-1"></i> Aprovar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" title="Rejeitar modelo"
                                            onclick="rejectModel(<?= $model['id'] ?>, '<?= htmlspecialchars($model['original_name']) ?>')">
                                        <i class="fas fa-times me-1"></i> Rejeitar
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
        
        <div class="card-footer bg-light">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Verifique os modelos cuidadosamente quanto a problemas de impressão.
                    </small>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="<?= BASE_URL ?>admin/print-queue" class="btn btn-primary btn-sm">
                        <i class="fas fa-print me-1"></i> Gerenciar Fila de Impressão
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de aprovação -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="approveModalLabel">Aprovar Modelo 3D</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= BASE_URL ?>admin/customer-models/update-status/" id="approveForm" method="post">
                <div class="modal-body">
                    <p>Você está aprovando o modelo <strong id="approveModelName"></strong>.</p>
                    
                    <div class="mb-3">
                        <label for="approveNotes" class="form-label">Observações para o cliente (opcional):</label>
                        <textarea class="form-control" id="approveNotes" name="notes" rows="3" 
                                  placeholder="Inclua detalhes sobre o processo de impressão, sugestões de cores, etc."></textarea>
                    </div>
                    
                    <input type="hidden" name="status" value="approved">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Aprovar Modelo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de rejeição -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectModalLabel">Rejeitar Modelo 3D</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?= BASE_URL ?>admin/customer-models/update-status/" id="rejectForm" method="post">
                <div class="modal-body">
                    <p>Você está rejeitando o modelo <strong id="rejectModelName"></strong>.</p>
                    
                    <div class="mb-3">
                        <label for="rejectNotes" class="form-label">Motivo da rejeição (obrigatório):</label>
                        <textarea class="form-control" id="rejectNotes" name="notes" rows="3" required
                                  placeholder="Explique por que o modelo não é viável para impressão e, se possível, ofereça sugestões para correção."></textarea>
                    </div>
                    
                    <input type="hidden" name="status" value="rejected">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Rejeitar Modelo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function approveModel(modelId, modelName) {
        document.getElementById('approveModelName').textContent = modelName;
        document.getElementById('approveForm').action = '<?= BASE_URL ?>admin/customer-models/update-status/' + modelId;
        
        const approveModal = new bootstrap.Modal(document.getElementById('approveModal'));
        approveModal.show();
    }
    
    function rejectModel(modelId, modelName) {
        document.getElementById('rejectModelName').textContent = modelName;
        document.getElementById('rejectForm').action = '<?= BASE_URL ?>admin/customer-models/update-status/' + modelId;
        
        const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
        rejectModal.show();
    }
</script>

<?php include_once VIEWS_PATH . '/partials/footer.php'; ?>

<?php
// Funções de formatação para uso na view
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . " GB";
    } elseif ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . " MB";
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . " KB";
    } else {
        return $bytes . " bytes";
    }
}

function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}
?>
