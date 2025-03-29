<?php include_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0"><i class="fas fa-cube me-2"></i> Meus Modelos 3D</h2>
            <a href="<?= BASE_URL ?>customer-models/upload" class="btn btn-light btn-sm">
                <i class="fas fa-plus-circle me-1"></i> Novo Upload
            </a>
        </div>
        
        <div class="card-body">
            <?php include_once VIEWS_PATH . '/partials/flash_messages.php'; ?>
            
            <!-- Filtros de status -->
            <div class="mb-4">
                <div class="btn-group" role="group" aria-label="Filtrar por status">
                    <a href="<?= BASE_URL ?>customer-models/list" class="btn btn-outline-secondary <?= !isset($status) ? 'active' : '' ?>">
                        Todos
                    </a>
                    <a href="<?= BASE_URL ?>customer-models/list?status=pending_validation" class="btn btn-outline-secondary <?= $status === 'pending_validation' ? 'active' : '' ?>">
                        Pendentes
                    </a>
                    <a href="<?= BASE_URL ?>customer-models/list?status=approved" class="btn btn-outline-secondary <?= $status === 'approved' ? 'active' : '' ?>">
                        Aprovados
                    </a>
                    <a href="<?= BASE_URL ?>customer-models/list?status=rejected" class="btn btn-outline-secondary <?= $status === 'rejected' ? 'active' : '' ?>">
                        Rejeitados
                    </a>
                </div>
            </div>
            
            <?php if (empty($models)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Você ainda não enviou nenhum modelo 3D
                <?php if (isset($status)): ?>
                com o status "<?= $status === 'pending_validation' ? 'Pendente' : ($status === 'approved' ? 'Aprovado' : 'Rejeitado') ?>"
                <?php endif; ?>.
                <a href="<?= BASE_URL ?>customer-models/upload" class="alert-link">Clique aqui para enviar seu primeiro modelo</a>.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Nome Original</th>
                            <th>Tipo</th>
                            <th>Tamanho</th>
                            <th>Data de Envio</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($models as $model): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file-<?= $model['file_type'] === 'stl' ? 'code' : 'image' ?> text-primary me-2 fa-lg"></i>
                                    <?= htmlspecialchars($model['original_name']) ?>
                                </div>
                            </td>
                            <td class="text-uppercase"><?= $model['file_type'] ?></td>
                            <td><?= formatFileSize($model['file_size']) ?></td>
                            <td><?= formatDate($model['created_at']) ?></td>
                            <td>
                                <?php if ($model['status'] === 'pending_validation'): ?>
                                <span class="badge bg-warning text-dark">Pendente</span>
                                <?php elseif ($model['status'] === 'approved'): ?>
                                <span class="badge bg-success">Aprovado</span>
                                <?php elseif ($model['status'] === 'rejected'): ?>
                                <span class="badge bg-danger">Rejeitado</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>customer-models/details/<?= $model['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <?php if ($model['status'] === 'pending_validation'): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="confirmDelete(<?= $model['id'] ?>, '<?= htmlspecialchars($model['original_name']) ?>')" 
                                        title="Excluir modelo">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card-footer bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Os modelos aprovados estarão disponíveis para uso em pedidos personalizados.
                </small>
                <a href="<?= BASE_URL ?>customer-models/upload" class="btn btn-primary btn-sm">
                    <i class="fas fa-upload me-1"></i> Enviar Novo Modelo
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">Confirmação de Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o modelo <strong id="modelName"></strong>?</p>
                <p class="text-danger mb-0">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="deleteLink" class="btn btn-danger">Excluir</a>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDelete(modelId, modelName) {
        document.getElementById('modelName').textContent = modelName;
        document.getElementById('deleteLink').href = '<?= BASE_URL ?>customer-models/delete/' + modelId;
        
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
    
    // Função auxiliar para formatar o tamanho do arquivo
    function formatFileSize(bytes) {
        if (bytes >= 1073741824) {
            return (bytes / 1073741824).toFixed(2) + " GB";
        } else if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + " MB";
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + " KB";
        } else {
            return bytes + " bytes";
        }
    }
    
    // Função auxiliar para formatar a data
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
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
