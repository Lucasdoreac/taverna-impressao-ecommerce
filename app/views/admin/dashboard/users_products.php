<?php
/**
 * Dashboard de Administração - Integração de Gerenciamento de Usuários e Produtos
 * 
 * @version 1.0.0
 * @author Taverna da Impressão
 */

// Verificar permissões
if (!isset($currentUser) || !in_array($currentUser['role'], ['admin', 'manager'])) {
    echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
    return;
}

// Obter token CSRF para formulários
$csrfToken = SecurityManager::getCsrfToken();
?>

<div class="dashboard-section">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Gerenciamento Centralizado</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-secondary text-white">
                                        <h5 class="card-title mb-0">Usuários</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Gerencie todos os usuários da plataforma em um único lugar.</p>
                                        <div class="d-flex justify-content-between">
                                            <a href="<?= BASE_URL ?>admin/usuarios" class="btn btn-outline-primary">
                                                <i class="fas fa-users"></i> Listar Usuários
                                            </a>
                                            <a href="<?= BASE_URL ?>admin/usuarios/create" class="btn btn-success">
                                                <i class="fas fa-user-plus"></i> Novo Usuário
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-light">
                                        <div class="small text-muted">Total de usuários: <?= htmlspecialchars($totalUsers) ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-secondary text-white">
                                        <h5 class="card-title mb-0">Produtos</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Gerencie todos os produtos da loja em um único lugar.</p>
                                        <div class="d-flex justify-content-between">
                                            <a href="<?= BASE_URL ?>admin/produtos" class="btn btn-outline-primary">
                                                <i class="fas fa-box"></i> Listar Produtos
                                            </a>
                                            <a href="<?= BASE_URL ?>admin/produtos/create" class="btn btn-success">
                                                <i class="fas fa-plus-circle"></i> Novo Produto
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-light">
                                        <div class="small text-muted">Total de produtos: <?= htmlspecialchars($totalProducts) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usuários Recentes -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Usuários Recentes</h5>
                        <a href="<?= BASE_URL ?>admin/usuarios" class="btn btn-sm btn-light">Ver Todos</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Função</th>
                                        <th>Data Registro</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recentUsers)): ?>
                                        <?php foreach ($recentUsers as $user): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['name']) ?></td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td>
                                                    <span class="badge <?= $user['role'] === 'admin' ? 'bg-danger' : ($user['role'] === 'manager' ? 'bg-warning' : 'bg-info') ?>">
                                                        <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($user['created_at']))) ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="<?= BASE_URL ?>admin/usuarios/view/<?= (int)$user['id'] ?>" class="btn btn-info" title="Visualizar">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="<?= BASE_URL ?>admin/usuarios/edit/<?= (int)$user['id'] ?>" class="btn btn-primary" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Nenhum usuário cadastrado recentemente.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Produtos Populares -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Produtos Populares</h5>
                        <a href="<?= BASE_URL ?>admin/produtos" class="btn btn-sm btn-light">Ver Todos</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Preço</th>
                                        <th>Estoque</th>
                                        <th>Vendas</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($popularProducts)): ?>
                                        <?php foreach ($popularProducts as $product): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($product['name']) ?></td>
                                                <td>R$ <?= htmlspecialchars(number_format($product['price'], 2, ',', '.')) ?></td>
                                                <td>
                                                    <span class="badge <?= $product['stock'] > 10 ? 'bg-success' : ($product['stock'] > 0 ? 'bg-warning' : 'bg-danger') ?>">
                                                        <?= (int)$product['stock'] ?>
                                                    </span>
                                                </td>
                                                <td><?= (int)$product['total_sales'] ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="<?= BASE_URL ?>admin/produtos/view/<?= (int)$product['id'] ?>" class="btn btn-info" title="Visualizar">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="<?= BASE_URL ?>admin/produtos/edit/<?= (int)$product['id'] ?>" class="btn btn-primary" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Nenhum produto cadastrado.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Estatísticas e Gráficos -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Estatísticas de Usuários e Produtos</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Estatísticas de Usuários -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Usuários por Tipo</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container" style="position: relative; height:200px; width:100%">
                                            <canvas id="userTypeChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Novos Usuários</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <div class="d-flex justify-content-around">
                                            <div class="text-center">
                                                <h3 class="text-primary"><?= (int)$newUsers['day'] ?></h3>
                                                <div class="text-muted">Hoje</div>
                                            </div>
                                            <div class="text-center">
                                                <h3 class="text-primary"><?= (int)$newUsers['week'] ?></h3>
                                                <div class="text-muted">Esta Semana</div>
                                            </div>
                                            <div class="text-center">
                                                <h3 class="text-primary"><?= (int)$newUsers['month'] ?></h3>
                                                <div class="text-muted">Este Mês</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Estatísticas de Produtos -->
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Produtos por Categoria</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container" style="position: relative; height:200px; width:100%">
                                            <canvas id="productCategoryChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Status do Estoque</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <div class="d-flex justify-content-around">
                                            <div class="text-center">
                                                <h3 class="text-success"><?= (int)$stockStatus['normal'] ?></h3>
                                                <div class="text-muted">Normal</div>
                                            </div>
                                            <div class="text-center">
                                                <h3 class="text-warning"><?= (int)$stockStatus['low'] ?></h3>
                                                <div class="text-muted">Baixo</div>
                                            </div>
                                            <div class="text-center">
                                                <h3 class="text-danger"><?= (int)$stockStatus['out'] ?></h3>
                                                <div class="text-muted">Esgotado</div>
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
        
        <!-- Ações Rápidas -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0">Ações Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <a href="<?= BASE_URL ?>admin/usuarios/create" class="btn btn-primary btn-lg btn-block w-100 mb-2">
                                    <i class="fas fa-user-plus"></i> Novo Usuário
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="<?= BASE_URL ?>admin/produtos/create" class="btn btn-success btn-lg btn-block w-100 mb-2">
                                    <i class="fas fa-plus-circle"></i> Novo Produto
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="<?= BASE_URL ?>admin/relatorios/usuarios" class="btn btn-info btn-lg btn-block w-100 mb-2">
                                    <i class="fas fa-chart-bar"></i> Relatório de Usuários
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="<?= BASE_URL ?>admin/relatorios/produtos" class="btn btn-warning btn-lg btn-block w-100 mb-2">
                                    <i class="fas fa-chart-line"></i> Relatório de Produtos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para os gráficos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuração com proteção CSRF para AJAX
    const configureAjax = () => {
        const csrfToken = '<?= $csrfToken ?>';
        
        // Adicionar token CSRF a todas as requisições AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });
    };
    
    // Inicializar configuração AJAX
    configureAjax();
    
    // Gráfico de Tipos de Usuário
    const userTypeCtx = document.getElementById('userTypeChart').getContext('2d');
    const userTypeChart = new Chart(userTypeCtx, {
        type: 'pie',
        data: {
            labels: ['Clientes', 'Administradores', 'Gerentes', 'Operadores'],
            datasets: [{
                data: [
                    <?= (int)$usersByType['customer'] ?>, 
                    <?= (int)$usersByType['admin'] ?>, 
                    <?= (int)$usersByType['manager'] ?>, 
                    <?= (int)$usersByType['operator'] ?>
                ],
                backgroundColor: ['#36a2eb', '#ff6384', '#ffcd56', '#4bc0c0']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });
    
    // Gráfico de Produtos por Categoria
    const productCategoryCtx = document.getElementById('productCategoryChart').getContext('2d');
    const productCategoryChart = new Chart(productCategoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($productsByCategory, 'category_name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($productsByCategory, 'count')) ?>,
                backgroundColor: [
                    '#36a2eb', '#ff6384', '#ffcd56', '#4bc0c0', '#9966ff', 
                    '#ff9f40', '#c9cbcf', '#7e57c2', '#26a69a', '#ec407a'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });
    
    // Atualizar dados em tempo real
    const updateRealTimeData = () => {
        $.ajax({
            url: '<?= BASE_URL ?>admin/dashboard/api/dashboard_stats',
            method: 'GET',
            success: function(response) {
                if (response.error) {
                    console.error('Erro ao carregar dados:', response.error);
                    return;
                }
                
                // Atualizar estatísticas
                // Implementação futura...
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição AJAX:', error);
            }
        });
    };
    
    // Atualizar dados a cada 60 segundos
    setInterval(updateRealTimeData, 60000);
});
</script>
