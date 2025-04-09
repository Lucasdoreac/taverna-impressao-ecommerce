<?php include_once VIEWS_PATH . '/partials/admin_header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h2 class="h4 mb-0">Modelos 3D Pendentes de Validação</h2>
                    <span class="badge bg-light text-dark fs-6"><?= count($models) ?> modelo(s) aguardando análise</span>
                </div>
                
                <div class="card-body">
                    <?php include_once VIEWS_PATH . '/partials/flash_messages.php'; ?>
                    
                    <?php if (empty($models)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Não há modelos pendentes de validação no momento.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuário</th>
                                        <th>Arquivo</th>
                                        <th>Tipo</th>
                                        <th>Tamanho</th>
                                        <th>Data de Envio</th>
                                        <th>Validação</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($models as $model): ?>
                                        <tr>
                                            <td><?= $model['id'] ?></td>
                                            <td>
                                                <div><?= htmlspecialchars($model['user_name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($model['user_email']) ?></small>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($model['original_name']) ?>">
                                                    <?= htmlspecialchars($model['original_name']) ?>
                                                </div>
                                                <small class="text-muted"><?= htmlspecialchars($model['file_name']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= strtoupper(htmlspecialchars($model['file_type'])) ?>
                                                </span>
                                            </td>
                                            <td><?= number_format($model['file_size'] / 1024 / 1024, 2) ?> MB</td>
                                            <td><?= date('d/m/Y H:i', strtotime($model['created_at'])) ?></td>
                                            <td>
                                                <?php
                                                $validationStatus = 'Pendente';
                                                $validationClass = 'warning';
                                                
                                                if (isset($model['validation_data']) && !empty($model['validation_data'])) {
                                                    if (is_array($model['validation_data']) && isset($model['validation_data']['valid']) && $model['validation_data']['valid']) {
                                                        $validationStatus = 'Válido';
                                                        $validationClass = 'success';
                                                    } elseif (is_array($model['validation_data']) && isset($model['validation_data']['security_checks'])) {
                                                        $checks = $model['validation_data']['security_checks'];
                                                        $allPassed = true;
                                                        
                                                        foreach ($checks as $check) {
                                                            if (isset($check['passed']) && !$check['passed']) {
                                                                $allPassed = false;
                                                                break;
                                                            }
                                                        }
                                                        
                                                        if ($allPassed) {
                                                            $validationStatus = 'Verificações OK';
                                                            $validationClass = 'info';
                                                        } else {
                                                            $validationStatus = 'Verificações Falhas';
                                                            $validationClass = 'danger';
                                                        }
                                                    }
                                                }
                                                ?>
                                                <span class="badge bg-<?= $validationClass ?>"><?= $validationStatus ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modelDetailsModal"
                                                            data-model-id="<?= $model['id'] ?>"
                                                            data-model-name="<?= htmlspecialchars($model['original_name']) ?>"
                                                            data-model-data="<?= htmlspecialchars(json_encode($model)) ?>">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-success" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#approveModelModal"
                                                            data-model-id="<?= $model['id'] ?>"
                                                            data-model-name="<?= htmlspecialchars($model['original_name']) ?>">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#rejectModelModal"
                                                            data-model-id="<?= $model['id'] ?>"
                                                            data-model-name="<?= htmlspecialchars($model['original_name']) ?>">
                                                        <i class="fas fa-times"></i>
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
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalhes do Modelo -->
<div class="modal fade" id="modelDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Detalhes do Modelo: <span id="detailModelName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">Informações Básicas</h6>
                        <dl class="row">
                            <dt class="col-sm-4">Arquivo:</dt>
                            <dd class="col-sm-8" id="detailFileName"></dd>
                            
                            <dt class="col-sm-4">Usuário:</dt>
                            <dd class="col-sm-8" id="detailUserName"></dd>
                            
                            <dt class="col-sm-4">E-mail:</dt>
                            <dd class="col-sm-8" id="detailUserEmail"></dd>
                            
                            <dt class="col-sm-4">Tipo:</dt>
                            <dd class="col-sm-8" id="detailFileType"></dd>
                            
                            <dt class="col-sm-4">Tamanho:</dt>
                            <dd class="col-sm-8" id="detailFileSize"></dd>
                            
                            <dt class="col-sm-4">Data de Envio:</dt>
                            <dd class="col-sm-8" id="detailCreatedAt"></dd>
                        </dl>
                        
                        <div id="detailNotes" class="mt-3 d-none">
                            <h6 class="border-bottom pb-2 mb-3">Observações do Usuário</h6>
                            <div class="p-3 bg-light rounded" id="detailNotesContent"></div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">Detalhes Técnicos</h6>
                        <div id="detailMetadata"></div>
                        
                        <h6 class="border-bottom pb-2 mb-3 mt-4">Verificações de Segurança</h6>
                        <div id="detailSecurityChecks"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-success" id="btnApproveFromDetails">Aprovar</button>
                <button type="button" class="btn btn-danger" id="btnRejectFromDetails">Rejeitar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Aprovação de Modelo -->
<div class="modal fade" id="approveModelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Aprovar Modelo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="approveForm" action="" method="post">
                <div class="modal-body">
                    <p>Você está prestes a aprovar o modelo <strong id="approveModelName"></strong>.</p>
                    <p>Este modelo ficará disponível para impressão pelo usuário.</p>
                    
                    <div class="mb-3">
                        <label for="approveNotes" class="form-label">Observações (opcional):</label>
                        <textarea class="form-control" id="approveNotes" name="admin_notes" rows="3" placeholder="Adicione qualquer observação relevante sobre este modelo..."></textarea>
                    </div>
                    
                    <input type="hidden" name="csrf_token" value="<?= CsrfProtection::getToken() ?>">
                    <input type="hidden" name="status" value="approved">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Confirmar Aprovação</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Rejeição de Modelo -->
<div class="modal fade" id="rejectModelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Rejeitar Modelo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="rejectForm" action="" method="post">
                <div class="modal-body">
                    <p>Você está prestes a rejeitar o modelo <strong id="rejectModelName"></strong>.</p>
                    <p>Este modelo será movido para o diretório de modelos rejeitados.</p>
                    
                    <div class="mb-3">
                        <label for="rejectReason" class="form-label">Motivo da Rejeição: <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectReason" name="admin_notes" rows="3" required placeholder="Explique o motivo da rejeição deste modelo..."></textarea>
                        <div class="form-text">Esta informação será exibida para o usuário.</div>
                    </div>
                    
                    <input type="hidden" name="csrf_token" value="<?= CsrfProtection::getToken() ?>">
                    <input type="hidden" name="status" value="rejected">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Rejeição</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal de Detalhes
    const modelDetailsModal = document.getElementById('modelDetailsModal');
    if (modelDetailsModal) {
        modelDetailsModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const modelId = button.getAttribute('data-model-id');
            const modelName = button.getAttribute('data-model-name');
            const modelDataStr = button.getAttribute('data-model-data');
            
            try {
                const modelData = JSON.parse(modelDataStr);
                
                // Preencher informações básicas
                document.getElementById('detailModelName').textContent = modelName;
                document.getElementById('detailFileName').textContent = modelData.file_name;
                document.getElementById('detailUserName').textContent = modelData.user_name;
                document.getElementById('detailUserEmail').textContent = modelData.user_email;
                document.getElementById('detailFileType').textContent = modelData.file_type.toUpperCase();
                document.getElementById('detailFileSize').textContent = `${(modelData.file_size / 1024 / 1024).toFixed(2)} MB`;
                document.getElementById('detailCreatedAt').textContent = new Date(modelData.created_at).toLocaleString();
                
                // Preencher observações se houver
                if (modelData.notes && modelData.notes.trim() !== '') {
                    document.getElementById('detailNotes').classList.remove('d-none');
                    document.getElementById('detailNotesContent').textContent = modelData.notes;
                } else {
                    document.getElementById('detailNotes').classList.add('d-none');
                }
                
                // Preencher metadados técnicos
                const metadataContainer = document.getElementById('detailMetadata');
                metadataContainer.innerHTML = '';
                
                if (modelData.validation_data && typeof modelData.validation_data === 'object') {
                    const metadata = modelData.validation_data.metadata || modelData.validation_data;
                    if (metadata) {
                        const metadataList = document.createElement('dl');
                        metadataList.className = 'row';
                        
                        for (const [key, value] of Object.entries(metadata)) {
                            if (typeof value !== 'object' && key !== 'file_size' && key !== 'file_extension') {
                                const dt = document.createElement('dt');
                                dt.className = 'col-sm-6';
                                dt.textContent = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) + ':';
                                
                                const dd = document.createElement('dd');
                                dd.className = 'col-sm-6';
                                
                                if (key.includes('size') || key.includes('width') || key.includes('height') || key.includes('depth')) {
                                    dd.textContent = typeof value === 'number' ? value.toFixed(2) : value;
                                } else if (key.includes('count') || key.includes('triangles') || key.includes('vertices') || key.includes('faces')) {
                                    dd.textContent = typeof value === 'number' ? value.toLocaleString() : value;
                                } else {
                                    dd.textContent = value;
                                }
                                
                                metadataList.appendChild(dt);
                                metadataList.appendChild(dd);
                            }
                        }
                        
                        metadataContainer.appendChild(metadataList);
                    } else {
                        metadataContainer.innerHTML = '<div class="alert alert-info">Não há metadados técnicos disponíveis.</div>';
                    }
                    
                    // Preencher verificações de segurança
                    const securityContainer = document.getElementById('detailSecurityChecks');
                    securityContainer.innerHTML = '';
                    
                    if (modelData.validation_data.security_checks) {
                        const checks = modelData.validation_data.security_checks;
                        
                        for (const [key, check] of Object.entries(checks)) {
                            const badge = document.createElement('div');
                            badge.className = `badge bg-${check.passed ? 'success' : 'danger'} d-block mb-2 text-start p-2`;
                            badge.innerHTML = `<div>${key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</div>
                                              <small>${check.message}</small>`;
                            
                            securityContainer.appendChild(badge);
                        }
                    } else {
                        securityContainer.innerHTML = '<div class="alert alert-info">Não há verificações de segurança disponíveis.</div>';
                    }
                } else {
                    metadataContainer.innerHTML = '<div class="alert alert-warning">Dados de validação não disponíveis.</div>';
                    document.getElementById('detailSecurityChecks').innerHTML = '<div class="alert alert-warning">Verificações de segurança não disponíveis.</div>';
                }
                
                // Configurar botões de ação
                document.getElementById('btnApproveFromDetails').onclick = function() {
                    const approveModal = new bootstrap.Modal(document.getElementById('approveModelModal'));
                    document.getElementById('approveModelName').textContent = modelName;
                    document.getElementById('approveForm').action = `<?= BASE_URL ?>admin/customer-models/update-status/${modelId}`;
                    
                    // Esconder o modal atual e mostrar o de aprovação
                    bootstrap.Modal.getInstance(modelDetailsModal).hide();
                    approveModal.show();
                };
                
                document.getElementById('btnRejectFromDetails').onclick = function() {
                    const rejectModal = new bootstrap.Modal(document.getElementById('rejectModelModal'));
                    document.getElementById('rejectModelName').textContent = modelName;
                    document.getElementById('rejectForm').action = `<?= BASE_URL ?>admin/customer-models/update-status/${modelId}`;
                    
                    // Esconder o modal atual e mostrar o de rejeição
                    bootstrap.Modal.getInstance(modelDetailsModal).hide();
                    rejectModal.show();
                };
                
            } catch (error) {
                console.error('Erro ao processar dados do modelo:', error);
            }
        });
    }
    
    // Modal de Aprovação
    const approveModelModal = document.getElementById('approveModelModal');
    if (approveModelModal) {
        approveModelModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button) {
                const modelId = button.getAttribute('data-model-id');
                const modelName = button.getAttribute('data-model-name');
                
                document.getElementById('approveModelName').textContent = modelName;
                document.getElementById('approveForm').action = `<?= BASE_URL ?>admin/customer-models/update-status/${modelId}`;
            }
        });
    }
    
    // Modal de Rejeição
    const rejectModelModal = document.getElementById('rejectModelModal');
    if (rejectModelModal) {
        rejectModelModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button) {
                const modelId = button.getAttribute('data-model-id');
                const modelName = button.getAttribute('data-model-name');
                
                document.getElementById('rejectModelName').textContent = modelName;
                document.getElementById('rejectForm').action = `<?= BASE_URL ?>admin/customer-models/update-status/${modelId}`;
            }
        });
    }
});
</script>

<?php include_once VIEWS_PATH . '/partials/admin_footer.php'; ?>
