<?php
/**
 * View administrativa para gerenciar preferências de notificação
 * 
 * Esta página permite aos administradores:
 * 1. Gerenciar tipos de notificação disponíveis
 * 2. Configurar canais de entrega
 * 3. Visualizar métricas de preferências de usuários
 */

// Configurar o título da página
$title = isset($title) ? $title : 'Administração de Preferências de Notificação';
?>

<?php require_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- Menu Lateral -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <?php require_once VIEWS_PATH . '/admin/partials/sidebar.php'; ?>
        </div>
        
        <!-- Conteúdo Principal -->
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Administração de Preferências de Notificação</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="<?= BASE_URL ?>admin/notificacoes/preferencias/metricas" class="btn btn-sm btn-outline-primary">Ver Métricas Detalhadas</a>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <!-- Resumo de Métricas -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-primary h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Tipos de Notificação</h5>
                        </div>
                        <div class="card-body">
                            <h2 class="display-4 text-center"><?= count($notificationTypes) ?></h2>
                            <p class="card-text text-center">Tipos configurados</p>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addTypeModal">
                                    <i class="fas fa-plus-circle"></i> Adicionar Tipo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card border-success h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">Canais de Entrega</h5>
                        </div>
                        <div class="card-body">
                            <h2 class="display-4 text-center"><?= count($notificationChannels) ?></h2>
                            <p class="card-text text-center">Canais ativos</p>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#addChannelModal">
                                    <i class="fas fa-plus-circle"></i> Adicionar Canal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card border-info h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">Métricas de Preferências</h5>
                        </div>
                        <div class="card-body">
                            <h2 class="display-4 text-center"><?= isset($metrics['totalUsers']) ? $metrics['totalUsers'] : 0 ?></h2>
                            <p class="card-text text-center">Usuários com preferências configuradas</p>
                            <div class="d-grid gap-2">
                                <a href="<?= BASE_URL ?>admin/notificacoes/preferencias/metricas" class="btn btn-outline-info">
                                    <i class="fas fa-chart-bar"></i> Ver Relatório Completo
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Abas para Gerenciamento -->
            <ul class="nav nav-tabs" id="adminTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="types-tab" data-bs-toggle="tab" data-bs-target="#types" type="button" role="tab" aria-controls="types" aria-selected="true">Tipos de Notificação</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="channels-tab" data-bs-toggle="tab" data-bs-target="#channels" type="button" role="tab" aria-controls="channels" aria-selected="false">Canais de Entrega</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="metrics-tab" data-bs-toggle="tab" data-bs-target="#metrics" type="button" role="tab" aria-controls="metrics" aria-selected="false">Métricas Resumidas</button>
                </li>
            </ul>
            
            <div class="tab-content" id="adminTabsContent">
                <!-- Tipos de Notificação -->
                <div class="tab-pane fade show active" id="types" role="tabpanel" aria-labelledby="types-tab">
                    <div class="card border-0">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nome</th>
                                            <th>Descrição</th>
                                            <th>Crítico</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($notificationTypes as $type): ?>
                                            <tr>
                                                <td><?= $type['id'] ?></td>
                                                <td><?= $type['name'] ?></td>
                                                <td><?= $type['description'] ?></td>
                                                <td>
                                                    <?php if (isset($type['is_critical']) && $type['is_critical']): ?>
                                                        <span class="badge bg-danger">Sim</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Não</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($type['is_active']) && $type['is_active']): ?>
                                                        <span class="badge bg-success">Ativo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">Inativo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-primary edit-type-btn" 
                                                                data-id="<?= $type['id'] ?>"
                                                                data-name="<?= htmlspecialchars($type['name']) ?>"
                                                                data-description="<?= htmlspecialchars($type['description']) ?>"
                                                                data-is-critical="<?= isset($type['is_critical']) && $type['is_critical'] ? '1' : '0' ?>"
                                                                data-is-active="<?= isset($type['is_active']) && $type['is_active'] ? '1' : '0' ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger delete-type-btn" data-id="<?= $type['id'] ?>">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Canais de Entrega -->
                <div class="tab-pane fade" id="channels" role="tabpanel" aria-labelledby="channels-tab">
                    <div class="card border-0">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nome</th>
                                            <th>Descrição</th>
                                            <th>Suporta Frequência</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($notificationChannels as $channel): ?>
                                            <tr>
                                                <td><?= $channel['id'] ?></td>
                                                <td><?= $channel['name'] ?></td>
                                                <td><?= $channel['description'] ?></td>
                                                <td>
                                                    <?php if (isset($channel['supports_frequency']) && $channel['supports_frequency']): ?>
                                                        <span class="badge bg-success">Sim</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Não</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($channel['is_active']) && $channel['is_active']): ?>
                                                        <span class="badge bg-success">Ativo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">Inativo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-primary edit-channel-btn" 
                                                                data-id="<?= $channel['id'] ?>"
                                                                data-name="<?= htmlspecialchars($channel['name']) ?>"
                                                                data-description="<?= htmlspecialchars($channel['description']) ?>"
                                                                data-supports-frequency="<?= isset($channel['supports_frequency']) && $channel['supports_frequency'] ? '1' : '0' ?>"
                                                                data-is-active="<?= isset($channel['is_active']) && $channel['is_active'] ? '1' : '0' ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger delete-channel-btn" data-id="<?= $channel['id'] ?>">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Métricas Resumidas -->
                <div class="tab-pane fade" id="metrics" role="tabpanel" aria-labelledby="metrics-tab">
                    <div class="card border-0">
                        <div class="card-body">
                            <div class="row">
                                <!-- Gráfico de Preferências por Canal -->
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5 class="card-title">Preferências por Canal</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="channelPreferencesChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Gráfico de Preferências por Tipo -->
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5 class="card-title">Preferências por Tipo</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="typePreferencesChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Preferências Mais Populares -->
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5 class="card-title">Combinações Mais Populares</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Tipo</th>
                                                            <th>Canal</th>
                                                            <th>Usuários</th>
                                                            <th>%</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (isset($metrics['popularCombinations'])): ?>
                                                            <?php foreach ($metrics['popularCombinations'] as $combo): ?>
                                                                <tr>
                                                                    <td><?= $combo['type_name'] ?></td>
                                                                    <td><?= $combo['channel_name'] ?></td>
                                                                    <td><?= $combo['user_count'] ?></td>
                                                                    <td><?= number_format(($combo['user_count'] / $metrics['totalUsers']) * 100, 1) ?>%</td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td colspan="4" class="text-center">Dados não disponíveis</td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Frequências Escolhidas -->
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h5 class="card-title">Frequências Escolhidas</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="frequencyPreferencesChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Adicionar Tipo de Notificação -->
<div class="modal fade" id="addTypeModal" tabindex="-1" aria-labelledby="addTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addTypeForm" action="<?= BASE_URL ?>admin/notificacoes/preferencias/add-type" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTypeModalLabel">Adicionar Tipo de Notificação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="typeName" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="typeName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="typeDescription" class="form-label">Descrição</label>
                        <textarea class="form-control" id="typeDescription" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="typeCritical" name="is_critical">
                        <label class="form-check-label" for="typeCritical">Crítico (não pode ser desativado)</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="typeActive" name="is_active" checked>
                        <label class="form-check-label" for="typeActive">Ativo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Tipo de Notificação -->
<div class="modal fade" id="editTypeModal" tabindex="-1" aria-labelledby="editTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editTypeForm" action="<?= BASE_URL ?>admin/notificacoes/preferencias/edit-type" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTypeModalLabel">Editar Tipo de Notificação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editTypeId" name="id">
                    <div class="mb-3">
                        <label for="editTypeName" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="editTypeName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editTypeDescription" class="form-label">Descrição</label>
                        <textarea class="form-control" id="editTypeDescription" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="editTypeCritical" name="is_critical">
                        <label class="form-check-label" for="editTypeCritical">Crítico (não pode ser desativado)</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="editTypeActive" name="is_active">
                        <label class="form-check-label" for="editTypeActive">Ativo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Adicionar Canal de Entrega -->
<div class="modal fade" id="addChannelModal" tabindex="-1" aria-labelledby="addChannelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addChannelForm" action="<?= BASE_URL ?>admin/notificacoes/preferencias/add-channel" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="addChannelModalLabel">Adicionar Canal de Entrega</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="channelName" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="channelName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="channelDescription" class="form-label">Descrição</label>
                        <textarea class="form-control" id="channelDescription" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="channelFrequency" name="supports_frequency" checked>
                        <label class="form-check-label" for="channelFrequency">Suporta Configuração de Frequência</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="channelActive" name="is_active" checked>
                        <label class="form-check-label" for="channelActive">Ativo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Canal de Entrega -->
<div class="modal fade" id="editChannelModal" tabindex="-1" aria-labelledby="editChannelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editChannelForm" action="<?= BASE_URL ?>admin/notificacoes/preferencias/edit-channel" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="editChannelModalLabel">Editar Canal de Entrega</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editChannelId" name="id">
                    <div class="mb-3">
                        <label for="editChannelName" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="editChannelName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editChannelDescription" class="form-label">Descrição</label>
                        <textarea class="form-control" id="editChannelDescription" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="editChannelFrequency" name="supports_frequency">
                        <label class="form-check-label" for="editChannelFrequency">Suporta Configuração de Frequência</label>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="editChannelActive" name="is_active">
                        <label class="form-check-label" for="editChannelActive">Ativo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Código JavaScript para manipulação da interface -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manipuladores para botões de edição de tipos
    document.querySelectorAll('.edit-type-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const description = this.dataset.description;
            const isCritical = this.dataset.isCritical === '1';
            const isActive = this.dataset.isActive === '1';
            
            document.getElementById('editTypeId').value = id;
            document.getElementById('editTypeName').value = name;
            document.getElementById('editTypeDescription').value = description;
            document.getElementById('editTypeCritical').checked = isCritical;
            document.getElementById('editTypeActive').checked = isActive;
            
            const modal = new bootstrap.Modal(document.getElementById('editTypeModal'));
            modal.show();
        });
    });
    
    // Manipuladores para botões de edição de canais
    document.querySelectorAll('.edit-channel-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const description = this.dataset.description;
            const supportsFrequency = this.dataset.supportsFrequency === '1';
            const isActive = this.dataset.isActive === '1';
            
            document.getElementById('editChannelId').value = id;
            document.getElementById('editChannelName').value = name;
            document.getElementById('editChannelDescription').value = description;
            document.getElementById('editChannelFrequency').checked = supportsFrequency;
            document.getElementById('editChannelActive').checked = isActive;
            
            const modal = new bootstrap.Modal(document.getElementById('editChannelModal'));
            modal.show();
        });
    });
    
    // Confirmação para exclusão de tipo
    document.querySelectorAll('.delete-type-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            if (confirm('Tem certeza que deseja excluir este tipo de notificação? Esta ação não pode ser desfeita e pode afetar preferências de usuários.')) {
                window.location.href = `<?= BASE_URL ?>admin/notificacoes/preferencias/delete-type/${id}`;
            }
        });
    });
    
    // Confirmação para exclusão de canal
    document.querySelectorAll('.delete-channel-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            if (confirm('Tem certeza que deseja excluir este canal de entrega? Esta ação não pode ser desfeita e pode afetar preferências de usuários.')) {
                window.location.href = `<?= BASE_URL ?>admin/notificacoes/preferencias/delete-channel/${id}`;
            }
        });
    });
    
    // Inicializar gráficos se dados estiverem disponíveis
    if (typeof Chart !== 'undefined') {
        // Dados para os gráficos (estes seriam fornecidos pelo PHP com base em $metrics)
        const channelData = <?= isset($metrics['channelPreferences']) ? json_encode($metrics['channelPreferences']) : '[]' ?>;
        const typeData = <?= isset($metrics['typePreferences']) ? json_encode($metrics['typePreferences']) : '[]' ?>;
        const frequencyData = <?= isset($metrics['frequencyPreferences']) ? json_encode($metrics['frequencyPreferences']) : '[]' ?>;
        
        // Configurar gráfico de preferências por canal
        if (channelData.length > 0) {
            const channelCtx = document.getElementById('channelPreferencesChart').getContext('2d');
            new Chart(channelCtx, {
                type: 'bar',
                data: {
                    labels: channelData.map(item => item.channel_name),
                    datasets: [{
                        label: 'Usuários',
                        data: channelData.map(item => item.user_count),
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Número de Usuários'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Canal de Notificação'
                            }
                        }
                    }
                }
            });
        }
        
        // Configurar gráfico de preferências por tipo
        if (typeData.length > 0) {
            const typeCtx = document.getElementById('typePreferencesChart').getContext('2d');
            new Chart(typeCtx, {
                type: 'bar',
                data: {
                    labels: typeData.map(item => item.type_name),
                    datasets: [{
                        label: 'Usuários',
                        data: typeData.map(item => item.user_count),
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Número de Usuários'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Tipo de Notificação'
                            }
                        }
                    }
                }
            });
        }
        
        // Configurar gráfico de frequências escolhidas
        if (frequencyData.length > 0) {
            const frequencyCtx = document.getElementById('frequencyPreferencesChart').getContext('2d');
            new Chart(frequencyCtx, {
                type: 'pie',
                data: {
                    labels: frequencyData.map(item => {
                        switch(item.frequency) {
                            case 'realtime': return 'Tempo Real';
                            case 'daily': return 'Diário';
                            case 'weekly': return 'Semanal';
                            default: return item.frequency;
                        }
                    }),
                    datasets: [{
                        data: frequencyData.map(item => item.user_count),
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(255, 206, 86, 0.6)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }
});
</script>

<?php require_once VIEWS_PATH . '/partials/footer.php'; ?>
