<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>customer-models/list">Meus Modelos 3D</a></li>
            <li class="breadcrumb-item active">Detalhes do Modelo</li>
        </ol>
    </nav>
    
    <?php include_once VIEWS_PATH . '/partials/flash_messages.php'; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">
                        <i class="fas fa-cube me-2"></i> <?= htmlspecialchars($model['original_name']) ?>
                    </h2>
                    <span class="badge 
                        <?= $model['status'] === 'approved' ? 'bg-success' : 
                            ($model['status'] === 'rejected' ? 'bg-danger' : 
                             ($model['status'] === 'needs_repair' ? 'bg-warning' : 'bg-primary')) ?>">
                        <?= $model['status'] === 'pending_validation' ? 'Pendente de Validação' : 
                            ($model['status'] === 'approved' ? 'Aprovado' : 
                             ($model['status'] === 'rejected' ? 'Rejeitado' : 'Precisa de Reparo')) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Data de Envio:</strong> <?= date('d/m/Y H:i', strtotime($model['created_at'])) ?></p>
                            <p><strong>Tipo de Arquivo:</strong> <?= strtoupper($model['file_type']) ?></p>
                            <p><strong>Tamanho:</strong> <?= number_format($model['file_size'] / 1024 / 1024, 2) ?> MB</p>
                        </div>
                        <div class="col-md-6">
                            <?php if (isset($model['validation_data']) && is_array($model['validation_data'])): ?>
                                <p><strong>Validação:</strong> 
                                    <?php if ($model['validation_data']['is_valid']): ?>
                                        <span class="text-success">
                                            <i class="fas fa-check-circle me-1"></i> Válido para impressão
                                        </span>
                                    <?php else: ?>
                                        <span class="text-danger">
                                            <i class="fas fa-times-circle me-1"></i> Problemas detectados
                                        </span>
                                    <?php endif; ?>
                                </p>
                                
                                <?php if (isset($model['validation_data']['stats']['dimensions'])): ?>
                                    <p><strong>Dimensões:</strong> 
                                        <?= number_format($model['validation_data']['stats']['dimensions']['width'], 2) ?> x 
                                        <?= number_format($model['validation_data']['stats']['dimensions']['height'], 2) ?> x 
                                        <?= number_format($model['validation_data']['stats']['dimensions']['depth'], 2) ?> mm
                                    </p>
                                <?php endif; ?>
                                
                                <?php if (isset($model['validation_data']['stats']['triangles'])): ?>
                                    <p><strong>Polígonos:</strong> <?= number_format($model['validation_data']['stats']['triangles']) ?></p>
                                <?php elseif (isset($model['validation_data']['stats']['faces'])): ?>
                                    <p><strong>Faces:</strong> <?= number_format($model['validation_data']['stats']['faces']) ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($model['notes']): ?>
                        <div class="card bg-light mb-3">
                            <div class="card-header">
                                <h3 class="h6 mb-0">Notas e Observações</h3>
                            </div>
                            <div class="card-body">
                                <pre class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($model['notes']) ?></pre>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($model['validation_data']) && is_array($model['validation_data'])): ?>
                        <div class="card mb-3">
                            <div class="card-header">
                                <h3 class="h6 mb-0">Resultados da Validação</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php if (!empty($model['validation_data']['errors'])): ?>
                                        <div class="col-md-12 mb-3">
                                            <h4 class="h6 text-danger"><i class="fas fa-exclamation-circle me-1"></i> Erros</h4>
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($model['validation_data']['errors'] as $error): ?>
                                                    <li class="list-group-item list-group-item-danger"><?= htmlspecialchars($error) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($model['validation_data']['warnings'])): ?>
                                        <div class="col-md-12 mb-3">
                                            <h4 class="h6 text-warning"><i class="fas fa-exclamation-triangle me-1"></i> Avisos</h4>
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($model['validation_data']['warnings'] as $warning): ?>
                                                    <li class="list-group-item list-group-item-warning"><?= htmlspecialchars($warning) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($model['validation_data']['info'])): ?>
                                        <div class="col-md-12 mb-3">
                                            <h4 class="h6 text-info"><i class="fas fa-info-circle me-1"></i> Informações</h4>
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($model['validation_data']['info'] as $info): ?>
                                                    <li class="list-group-item list-group-item-info"><?= htmlspecialchars($info) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($model['validation_data']['repair_suggestions'])): ?>
                                        <div class="col-md-12 mb-3">
                                            <h4 class="h6 text-primary"><i class="fas fa-tools me-1"></i> Sugestões de Reparo</h4>
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($model['validation_data']['repair_suggestions'] as $suggestion): ?>
                                                    <li class="list-group-item list-group-item-primary"><?= htmlspecialchars($suggestion) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (isset($model['validation_data']['stats']) && !empty($model['validation_data']['stats'])): ?>
                                    <div class="mt-3">
                                        <h4 class="h6"><i class="fas fa-chart-bar me-1"></i> Estatísticas Detalhadas</h4>
                                        <table class="table table-sm table-bordered">
                                            <tbody>
                                                <?php foreach ($model['validation_data']['stats'] as $key => $value): ?>
                                                    <?php if (is_array($value)): ?>
                                                        <?php if ($key === 'dimensions'): ?>
                                                            <tr>
                                                                <th>Dimensões</th>
                                                                <td>
                                                                    Largura: <?= number_format($value['width'], 2) ?> mm<br>
                                                                    Altura: <?= number_format($value['height'], 2) ?> mm<br>
                                                                    Profundidade: <?= number_format($value['depth'], 2) ?> mm
                                                                </td>
                                                            </tr>
                                                        <?php elseif ($key === 'bounding_box'): ?>
                                                            <tr>
                                                                <th>Bounding Box</th>
                                                                <td>
                                                                    Min: [<?= number_format($value['min'][0], 2) ?>, <?= number_format($value['min'][1], 2) ?>, <?= number_format($value['min'][2], 2) ?>]<br>
                                                                    Max: [<?= number_format($value['max'][0], 2) ?>, <?= number_format($value['max'][1], 2) ?>, <?= number_format($value['max'][2], 2) ?>]
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <th><?= ucfirst(str_replace('_', ' ', $key)) ?></th>
                                                            <td><?= is_numeric($value) ? number_format($value) : htmlspecialchars($value) ?></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <?php if ($model['status'] === 'approved'): ?>
                            <a href="<?= BASE_URL ?>carrinho/adicionar-modelo/<?= $model['id'] ?>" class="btn btn-success">
                                <i class="fas fa-cart-plus me-1"></i> Adicionar ao Carrinho
                            </a>
                        <?php elseif ($model['status'] === 'needs_repair'): ?>
                            <div>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#repairInfoModal">
                                    <i class="fas fa-tools me-1"></i> Ver Instruções de Reparo
                                </button>
                            </div>
                        <?php elseif ($model['status'] === 'pending_validation'): ?>
                            <div class="text-muted">
                                <i class="fas fa-clock me-1"></i> Aguardando validação pela nossa equipe
                            </div>
                        <?php endif; ?>
                        
                        <a href="<?= BASE_URL ?>customer-models/delete/<?= $model['id'] ?>" 
                           class="btn btn-outline-danger" 
                           onclick="return confirm('Tem certeza que deseja excluir este modelo?')">
                            <i class="fas fa-trash-alt me-1"></i> Excluir
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Painel lateral para administradores -->
            <?php if (isset($isAdmin) && $isAdmin): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h3 class="h5 mb-0"><i class="fas fa-cogs me-2"></i> Ações Administrativas</h3>
                    </div>
                    <div class="card-body">
                        <!-- Formulário para alterar status do modelo -->
                        <form action="<?= BASE_URL ?>admin/customer-models/update-status/<?= $model['id'] ?>" method="post">
                            <div class="mb-3">
                                <label for="status" class="form-label">Alterar Status:</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="pending_validation" <?= $model['status'] === 'pending_validation' ? 'selected' : '' ?>>Pendente de Validação</option>
                                    <option value="approved" <?= $model['status'] === 'approved' ? 'selected' : '' ?>>Aprovado</option>
                                    <option value="needs_repair" <?= $model['status'] === 'needs_repair' ? 'selected' : '' ?>>Precisa de Reparo</option>
                                    <option value="rejected" <?= $model['status'] === 'rejected' ? 'selected' : '' ?>>Rejeitado</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="admin_notes" class="form-label">Notas Administrativas:</label>
                                <textarea name="notes" id="admin_notes" class="form-control" rows="4" placeholder="Observações sobre o modelo ou instruções para o cliente"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-1"></i> Salvar Alterações
                            </button>
                        </form>
                        
                        <hr>
                        
                        <!-- Ações adicionais -->
                        <div class="d-grid gap-2">
                            <a href="<?= BASE_URL ?>admin/customer-models/revalidate/<?= $model['id'] ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-sync-alt me-1"></i> Revalidar Modelo
                            </a>
                            
                            <a href="<?= BASE_URL ?>admin/print-queue/add/<?= $model['id'] ?>" class="btn btn-outline-success">
                                <i class="fas fa-print me-1"></i> Adicionar à Fila de Impressão
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Informações sobre validação e impressão 3D -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h3 class="h5 mb-0"><i class="fas fa-info-circle me-2"></i> Informações sobre Validação</h3>
                </div>
                <div class="card-body">
                    <p>
                        Todos os modelos 3D enviados passam por um processo de validação para garantir que possam ser impressos corretamente. 
                        Este processo verifica:
                    </p>
                    
                    <ul class="mb-3">
                        <li>Integridade da malha 3D</li>
                        <li>Dimensões adequadas para impressão</li>
                        <li>Possíveis problemas na geometria</li>
                        <li>Otimização para impressão FDM</li>
                    </ul>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb me-1"></i> <strong>Dica:</strong> Para melhores resultados, certifique-se de que seu modelo tenha uma base plana para impressão e evite detalhes muito pequenos que podem ser difíceis de imprimir.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Instruções de Reparo -->
<?php if ($model['status'] === 'needs_repair'): ?>
<div class="modal fade" id="repairInfoModal" tabindex="-1" aria-labelledby="repairInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="repairInfoModalLabel">
                    <i class="fas fa-tools me-2"></i> Instruções para Reparo do Modelo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <p><i class="fas fa-exclamation-triangle me-1"></i> <strong>Atenção:</strong> Seu modelo requer algumas correções antes de poder ser impresso.</p>
                </div>
                
                <?php if (isset($model['validation_data']) && is_array($model['validation_data'])): ?>
                    <?php if (!empty($model['validation_data']['errors'])): ?>
                        <h5>Problemas Detectados:</h5>
                        <ul class="list-group mb-4">
                            <?php foreach ($model['validation_data']['errors'] as $error): ?>
                                <li class="list-group-item list-group-item-danger"><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                            
                            <?php if (!empty($model['validation_data']['warnings'])): ?>
                                <?php foreach ($model['validation_data']['warnings'] as $warning): ?>
                                    <li class="list-group-item list-group-item-warning"><?= htmlspecialchars($warning) ?></li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <?php if (!empty($model['validation_data']['repair_suggestions'])): ?>
                        <h5>Sugestões para Correção:</h5>
                        <ul class="list-group mb-4">
                            <?php foreach ($model['validation_data']['repair_suggestions'] as $suggestion): ?>
                                <li class="list-group-item list-group-item-primary"><?= htmlspecialchars($suggestion) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
                
                <h5>Próximos Passos:</h5>
                <ol>
                    <li>Faça as correções necessárias no seu modelo 3D usando seu software de modelagem.</li>
                    <li>Depois de corrigido, exclua este modelo e envie a versão atualizada.</li>
                    <li>Caso tenha dúvidas sobre como corrigir algum problema específico, entre em contato conosco pelo formulário de suporte.</li>
                </ol>
                
                <div class="alert alert-info">
                    <p><i class="fas fa-info-circle me-1"></i> <strong>Dica:</strong> Software como o <a href="https://www.meshmixer.com/" target="_blank">Meshmixer</a> (gratuito) ou o <a href="https://www.netfabb.com/" target="_blank">Netfabb</a> podem ajudar na correção de muitos problemas comuns em modelos 3D.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <a href="<?= BASE_URL ?>suporte" class="btn btn-primary">
                    <i class="fas fa-question-circle me-1"></i> Pedir Ajuda
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
