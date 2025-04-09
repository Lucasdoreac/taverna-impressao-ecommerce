<?php
/**
 * View - Lista de Fila de Impressão do Cliente
 * 
 * @package    Taverna da Impressão 3D
 * @subpackage Views/Customer
 * @version    1.0.0
 */

// Garantir que este arquivo não seja acessado diretamente
defined('BASEPATH') or exit('Acesso direto ao script não é permitido');
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
            
            <?php if (isset($_SESSION['flash_message']) && isset($_SESSION['flash_type'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['flash_message'], ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            
            <!-- Filtros Simples -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Filtrar por Status</h5>
                </div>
                <div class="card-body">
                    <div class="btn-group mb-3 w-100">
                        <a href="/user/print-queue" class="btn <?= !isset($currentStatus) ? 'btn-primary' : 'btn-outline-primary' ?>">Todos</a>
                        <a href="/user/print-queue?status=pending" class="btn <?= isset($currentStatus) && $currentStatus === 'pending' ? 'btn-primary' : 'btn-outline-primary' ?>">Pendentes</a>
                        <a href="/user/print-queue?status=assigned" class="btn <?= isset($currentStatus) && $currentStatus === 'assigned' ? 'btn-primary' : 'btn-outline-primary' ?>">Atribuídos</a>
                        <a href="/user/print-queue?status=printing" class="btn <?= isset($currentStatus) && $currentStatus === 'printing' ? 'btn-primary' : 'btn-outline-primary' ?>">Em Impressão</a>
                        <a href="/user/print-queue?status=completed" class="btn <?= isset($currentStatus) && $currentStatus === 'completed' ? 'btn-primary' : 'btn-outline-primary' ?>">Concluídos</a>
                        <a href="/user/print-queue?status=cancelled" class="btn <?= isset($currentStatus) && $currentStatus === 'cancelled' ? 'btn-primary' : 'btn-outline-primary' ?>">Cancelados</a>
                    </div>
                </div>
            </div>
            
            <!-- Tabela da Fila de Impressão -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Meus Modelos na Fila</h5>
                    <?php if (!empty($availableModels)): ?>
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addToQueueModal">
                            <i class="fa fa-plus"></i> Adicionar à Fila
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($queueItems)): ?>
                        <div class="alert alert-info">
                            Você não tem nenhum modelo na fila de impressão.
                            <?php if (!empty($availableModels)): ?>
                                <button type="button" class="btn btn-sm btn-success ml-3" data-toggle="modal" data-target="#addToQueueModal">
                                    Adicionar modelo à fila
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Modelo</th>
                                        <th>Status</th>
                                        <th>Prioridade</th>
                                        <th>Data de Adição</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($queueItems as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['model_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                $statusText = '';
                                                
                                                switch($item['status']) {
                                                    case 'pending':
                                                        $statusClass = 'badge-warning';
                                                        $statusText = 'Pendente';
                                                        break;
                                                    case 'assigned':
                                                        $statusClass = 'badge-info';
                                                        $statusText = 'Atribuído';
                                                        break;
                                                    case 'printing':
                                                        $statusClass = 'badge-primary';
                                                        $statusText = 'Em Impressão';
                                                        break;
                                                    case 'completed':
                                                        $statusClass = 'badge-success';
                                                        $statusText = 'Concluído';
                                                        break;
                                                    case 'cancelled':
                                                        $statusClass = 'badge-secondary';
                                                        $statusText = 'Cancelado';
                                                        break;
                                                    case 'failed':
                                                        $statusClass = 'badge-danger';
                                                        $statusText = 'Falha';
                                                        break;
                                                    default:
                                                        $statusClass = 'badge-light';
                                                        $statusText = $item['status'];
                                                }
                                                ?>
                                                <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $priorityClass = '';
                                                
                                                if ($item['priority'] >= 8) {
                                                    $priorityClass = 'text-danger font-weight-bold';
                                                } elseif ($item['priority'] >= 5) {
                                                    $priorityClass = 'text-warning font-weight-bold';
                                                }
                                                ?>
                                                <span class="<?= $priorityClass ?>"><?= htmlspecialchars($item['priority'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </td>
                                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($item['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="/user/print-queue/details/<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-info">
                                                        <i class="fa fa-eye"></i> Detalhes
                                                    </a>
                                                    
                                                    <?php if ($item['status'] !== 'completed' && $item['status'] !== 'cancelled'): ?>
                                                        <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#cancelModal" 
                                                                data-id="<?= htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-model="<?= htmlspecialchars($item['model_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <i class="fa fa-times"></i> Cancelar
                                                        </button>
                                                    <?php endif; ?>
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
            
            <?php if (!empty($availableModels)): ?>
                <!-- Modelos Disponíveis para Adicionar à Fila -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Modelos Aprovados Disponíveis</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($availableModels as $model): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0"><?= htmlspecialchars($model['original_name'], ENT_QUOTES, 'UTF-8') ?></h6>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Formato:</strong> <?= htmlspecialchars($model['file_extension'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <p><strong>Enviado em:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($model['created_at'])), ENT_QUOTES, 'UTF-8') ?></p>
                                            
                                            <?php
                                            $validationData = isset($model['validation_data']) && !empty($model['validation_data'])
                                                            ? (is_array($model['validation_data']) ? $model['validation_data'] : json_decode($model['validation_data'], true))
                                                            : [];
                                            ?>
                                            <?php if (!empty($validationData) && isset($validationData['size'])): ?>
                                                <p><strong>Dimensões:</strong> 
                                                    <?= htmlspecialchars($validationData['size']['width'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> x 
                                                    <?= htmlspecialchars($validationData['size']['height'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> x 
                                                    <?= htmlspecialchars($validationData['size']['depth'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> mm
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-footer">
                                            <button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#addToQueueModal" 
                                                    data-id="<?= htmlspecialchars($model['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-name="<?= htmlspecialchars($model['original_name'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fa fa-plus"></i> Adicionar à Fila
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para Adicionar à Fila -->
<div class="modal fade" id="addToQueueModal" tabindex="-1" role="dialog" aria-labelledby="addToQueueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="post" action="/print-queue/addToQueue">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="model_id" id="addModelId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="addToQueueModalLabel">Adicionar Modelo à Fila de Impressão</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Modelo e prioridade -->
                            <div class="form-group">
                                <label for="selectModel">Modelo:</label>
                                <select name="model_id" id="selectModel" class="form-control" required>
                                    <option value="">Selecione um modelo...</option>
                                    <?php foreach ($availableModels as $model): ?>
                                        <option value="<?= htmlspecialchars($model['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($model['original_name'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="priority">Prioridade (1-10):</label>
                                <input type="range" class="custom-range" name="priority" id="priority" min="1" max="10" value="5">
                                <div class="d-flex justify-content-between">
                                    <span>Baixa (1)</span>
                                    <span id="priorityValue">5</span>
                                    <span>Alta (10)</span>
                                </div>
                                <small class="form-text text-muted">Maior prioridade pode reduzir o tempo de espera, mas pode ter custo adicional.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes">Notas ou Instruções Especiais:</label>
                                <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <!-- Configurações de impressão -->
                            <h5>Configurações de Impressão</h5>
                            
                            <div class="form-group">
                                <label for="scale">Escala:</label>
                                <select name="scale" id="scale" class="form-control">
                                    <option value="0.5">0.5x (50%)</option>
                                    <option value="0.75">0.75x (75%)</option>
                                    <option value="1.0" selected>1.0x (100%)</option>
                                    <option value="1.25">1.25x (125%)</option>
                                    <option value="1.5">1.5x (150%)</option>
                                    <option value="2.0">2.0x (200%)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="layer_height">Altura da Camada:</label>
                                <select name="layer_height" id="layer_height" class="form-control">
                                    <option value="0.1">0.1mm (Alta Qualidade)</option>
                                    <option value="0.15">0.15mm (Qualidade/Velocidade)</option>
                                    <option value="0.2" selected>0.2mm (Padrão)</option>
                                    <option value="0.3">0.3mm (Rascunho)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="infill">Preenchimento:</label>
                                <select name="infill" id="infill" class="form-control">
                                    <option value="10">10% (Mínimo)</option>
                                    <option value="20" selected>20% (Padrão)</option>
                                    <option value="50">50% (Resistente)</option>
                                    <option value="80">80% (Muito Resistente)</option>
                                    <option value="100">100% (Sólido)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="supports" name="supports" value="1" checked>
                                    <label class="custom-control-label" for="supports">Gerar Suportes</label>
                                </div>
                                <small class="form-text text-muted">Recomendado para modelos com partes suspensas.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="material">Material:</label>
                                <select name="material" id="material" class="form-control">
                                    <option value="PLA" selected>PLA (Padrão)</option>
                                    <option value="ABS">ABS (Resistente ao Calor)</option>
                                    <option value="PETG">PETG (Resistente)</option>
                                    <option value="TPU">TPU (Flexível)</option>
                                    <option value="Nylon">Nylon (Alta Resistência)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="color">Cor Desejada (Sujeito à disponibilidade):</label>
                                <input type="text" name="color" id="color" class="form-control" placeholder="Ex: Azul, Vermelho, Preto">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar à Fila</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Cancelar Item -->
<div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="/print-queue/cancel">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="queue_id" id="cancelQueueId" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">Cancelar Item da Fila</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Você está cancelando o modelo: <strong id="cancelModelName"></strong></p>
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i> Atenção: Esta ação não pode ser desfeita.
                    </div>
                    
                    <div class="form-group">
                        <label for="cancel_notes">Motivo do Cancelamento:</label>
                        <textarea name="notes" id="cancel_notes" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Voltar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Cancelamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar valor da prioridade no slider
    const prioritySlider = document.getElementById('priority');
    const priorityValue = document.getElementById('priorityValue');
    
    if (prioritySlider && priorityValue) {
        prioritySlider.addEventListener('input', function() {
            priorityValue.textContent = this.value;
        });
    }
    
    // Configurar modal de adição à fila
    $('#addToQueueModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const modelId = button.data('id');
        
        if (modelId) {
            // Se o botão tinha um ID de modelo definido
            const selectModel = document.getElementById('selectModel');
            if (selectModel) {
                selectModel.value = modelId;
            }
        }
    });
    
    // Configurar modal de cancelamento
    $('#cancelModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const queueId = button.data('id');
        const modelName = button.data('model');
        
        const modal = $(this);
        modal.find('#cancelQueueId').val(queueId);
        modal.find('#cancelModelName').text(modelName);
    });
    
    // Sincronizar os valores do select para ID do modelo
    const selectModel = document.getElementById('selectModel');
    const addModelId = document.getElementById('addModelId');
    
    if (selectModel && addModelId) {
        selectModel.addEventListener('change', function() {
            addModelId.value = this.value;
        });
    }
});
</script>
