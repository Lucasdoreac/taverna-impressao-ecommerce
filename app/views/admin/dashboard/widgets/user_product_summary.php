<?php
/**
 * Widget de resumo de usuários e produtos para o dashboard
 * 
 * Este widget exibe um resumo rápido de usuários e produtos com links para o gerenciamento centralizado
 */

// Obter token CSRF para formulários/links
$csrfToken = SecurityManager::getCsrfToken();
?>

<div class="dashboard-widgets row mt-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Gerenciamento Centralizado</h5>
                
                <a href="<?= BASE_URL ?>admin/dashboard/users-products" class="btn btn-sm btn-light">
                    <i class="bi bi-grid-3x3-gap-fill me-1"></i> Dashboard de Gerenciamento
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h6>Usuários</h6>
                                <div class="text-muted small">Gerencie todos os usuários da plataforma</div>
                            </div>
                            <div>
                                <a href="<?= BASE_URL ?>admin/usuarios" class="btn btn-outline-primary btn-sm me-2">
                                    <i class="bi bi-people-fill me-1"></i> Listar
                                </a>
                                <a href="<?= BASE_URL ?>admin/usuarios/create" class="btn btn-success btn-sm">
                                    <i class="bi bi-person-plus-fill me-1"></i> Novo
                                </a>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between text-center small">
                            <div class="py-2 px-3 rounded bg-light">
                                <div class="fw-bold">Total</div>
                                <div id="userTotalCount" class="fs-5"><?= $userMetrics['total'] ?? 0 ?></div>
                            </div>
                            <div class="py-2 px-3 rounded bg-light">
                                <div class="fw-bold">Novos (30 dias)</div>
                                <div id="userNewCount" class="fs-5"><?= $userMetrics['newLastMonth'] ?? 0 ?></div>
                            </div>
                            <div class="py-2 px-3 rounded bg-light">
                                <div class="fw-bold">Ativos</div>
                                <div id="userActiveCount" class="fs-5"><?= $userMetrics['active'] ?? 0 ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h6>Produtos</h6>
                                <div class="text-muted small">Gerencie todos os produtos da loja</div>
                            </div>
                            <div>
                                <a href="<?= BASE_URL ?>admin/produtos" class="btn btn-outline-primary btn-sm me-2">
                                    <i class="bi bi-box-seam me-1"></i> Listar
                                </a>
                                <a href="<?= BASE_URL ?>admin/produtos/create" class="btn btn-success btn-sm">
                                    <i class="bi bi-plus-circle-fill me-1"></i> Novo
                                </a>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between text-center small">
                            <div class="py-2 px-3 rounded bg-light">
                                <div class="fw-bold">Total</div>
                                <div id="productTotalCount" class="fs-5"><?= $productMetrics['total'] ?? 0 ?></div>
                            </div>
                            <div class="py-2 px-3 rounded bg-light">
                                <div class="fw-bold">Ativos</div>
                                <div id="productActiveCount" class="fs-5"><?= $productMetrics['active'] ?? 0 ?></div>
                            </div>
                            <div class="py-2 px-3 rounded bg-light">
                                <div class="fw-bold">Estoque Baixo</div>
                                <div id="productLowStockCount" class="fs-5 text-warning"><?= $productMetrics['lowStock'] ?? 0 ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar métricas de usuários e produtos a cada 60 segundos
    setInterval(updateUserProductMetrics, 60000);
    
    // Função para atualizar as métricas
    function updateUserProductMetrics() {
        fetch('<?= BASE_URL ?>admin/dashboard/api/dashboard_stats')
            .then(response => response.json())
            .then(data => {
                if (data.users) {
                    document.getElementById('userTotalCount').innerText = data.users.total;
                    document.getElementById('userNewCount').innerText = data.users.new_today;
                    document.getElementById('userActiveCount').innerText = data.users.active;
                }
                
                if (data.products) {
                    document.getElementById('productTotalCount').innerText = data.products.total;
                    document.getElementById('productActiveCount').innerText = data.products.total - data.products.out_of_stock;
                    document.getElementById('productLowStockCount').innerText = data.products.low_stock;
                }
            })
            .catch(error => {
                console.error('Erro ao atualizar métricas:', error);
            });
    }
});
</script>
