<?php include_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0"><i class="fas fa-cube me-2"></i> Detalhes do Modelo 3D</h2>
            <a href="<?= BASE_URL ?>customer-models/list" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Voltar para Lista
            </a>
        </div>
        
        <div class="card-body">
            <?php include_once VIEWS_PATH . '/partials/flash_messages.php'; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h3 class="h5 mb-0">Informações do Arquivo</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 40%">Nome Original:</th>
                                    <td><?= htmlspecialchars($model['original_name']) ?></td>
                                </tr>
                                <tr>
                                    <th>Tipo de Arquivo:</th>
                                    <td><span class="badge bg-primary"><?= strtoupper($model['file_type']) ?></span></td>
                                </tr>
                                <tr>
                                    <th>Tamanho:</th>
                                    <td><?= formatFileSize($model['file_size']) ?></td>
                                </tr>
                                <tr>
                                    <th>Data de Envio:</th>
                                    <td><?= formatDate($model['created_at']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h3 class="h5 mb-0">Status e Avaliação</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6>Status atual:</h6>
                                <?php if ($model['status'] === 'pending_validation'): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-clock me-2"></i>
                                    <strong>Pendente de Validação</strong>
                                    <p class="mb-0 mt-1 small">Seu modelo está aguardando análise pela nossa equipe técnica.</p>
                                </div>
                                <?php elseif ($model['status'] === 'approved'): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Aprovado</strong>
                                    <p class="mb-0 mt-1 small">Seu modelo foi aprovado e está disponível para impressão.</p>
                                </div>
                                <?php elseif ($model['status'] === 'rejected'): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-times-circle me-2"></i>
                                    <strong>Rejeitado</strong>
                                    <p class="mb-0 mt-1 small">Infelizmente seu modelo não pôde ser aprovado. Verifique os comentários abaixo.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($model['notes'])): ?>
                            <div>
                                <h6>Comentários:</h6>
                                <div class="border p-3 rounded bg-light">
                                    <?= nl2br(htmlspecialchars($model['notes'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($model['status'] === 'approved'): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h3 class="h5 mb-0">Disponibilidade para Uso</h3>
                </div>
                <div class="card-body">
                    <p>
                        <i class="fas fa-info-circle me-2"></i>
                        Este modelo está disponível para uso em seus pedidos. Você pode selecioná-lo ao personalizar
                        itens que permitem o upload de modelos próprios.
                    </p>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <a href="<?= BASE_URL ?>produtos?categoria=modelos-personalizados" class="btn btn-primary">
                            <i class="fas fa-shopping-cart me-1"></i> Pedir Impressão deste Modelo
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($model['status'] === 'rejected'): ?>
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h3 class="h5 mb-0">Recomendações</h3>
                </div>
                <div class="card-body">
                    <p>
                        <i class="fas fa-lightbulb me-2 text-warning"></i>
                        Se seu modelo foi rejeitado, considere as seguintes recomendações antes de enviar novamente:
                    </p>
                    
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item">Certifique-se de que seu modelo é estanque (watertight) e não possui erros de geometria.</li>
                        <li class="list-group-item">Corrija erros de malha utilizando softwares como Meshmixer, Blender ou 3D Builder.</li>
                        <li class="list-group-item">Modelos muito pequenos ou com detalhes muito finos podem não ser impressíveis.</li>
                        <li class="list-group-item">Objetos com partes muito finas podem quebrar facilmente durante a impressão ou após.</li>
                    </ul>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <a href="<?= BASE_URL ?>customer-models/upload" class="btn btn-primary">
                            <i class="fas fa-upload me-1"></i> Enviar Novo Modelo
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($isAdmin): ?>
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h3 class="h5 mb-0">Área do Administrador</h3>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>admin/customer-models/update-status/<?= $model['id'] ?>" method="post">
                        <div class="mb-3">
                            <label for="status" class="form-label">Atualizar Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Selecione um status</option>
                                <option value="approved" <?= $model['status'] === 'approved' ? 'selected' : '' ?>>Aprovar</option>
                                <option value="rejected" <?= $model['status'] === 'rejected' ? 'selected' : '' ?>>Rejeitar</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Notas / Comentários</label>
                            <textarea class="form-control" id="admin_notes" name="notes" rows="3"><?= htmlspecialchars($model['notes']) ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-save me-1"></i> Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="card-footer bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <?php if ($model['status'] === 'pending_validation'): ?>
                <a href="<?= BASE_URL ?>customer-models/delete/<?= $model['id'] ?>" class="btn btn-sm btn-outline-danger" 
                   onclick="return confirm('Tem certeza que deseja excluir este modelo?')">
                    <i class="fas fa-trash-alt me-1"></i> Excluir Modelo
                </a>
                <?php else: ?>
                <div></div>
                <?php endif; ?>
                
                <a href="<?= BASE_URL ?>customer-models/list" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Voltar para Lista
                </a>
            </div>
        </div>
    </div>
</div>

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
