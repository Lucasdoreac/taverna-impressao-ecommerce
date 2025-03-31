<?php include_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h2 mb-3">Monitoramento de Impressões 3D</h1>
            <p class="lead">Acompanhe o status das suas impressões em tempo real.</p>
        </div>
    </div>
    
    <!-- Impressões Ativas -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">Impressões em Andamento</h2>
                <span class="badge bg-primary"><?= count($activePrints); ?> ativa(s)</span>
            </div>
            <hr>
            
            <?php if (empty($activePrints)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Você não possui impressões em andamento no momento.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($activePrints as $print): ?>
                        <div class="col-12 col-md-6 mb-4">
                            <div id="print-status-card-<?= $print['id']; ?>" class="print-status-dashboard">
                                <?= PrintStatusHelper::renderMiniDashboard($print, false); ?>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-2">
                                <a href="<?= BASE_URL; ?>print-monitor/details/<?= $print['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-info-circle"></i> Detalhes
                                </a>
                                <small class="text-muted">
                                    Pedido #<?= $print['order_number']; ?>
                                </small>
                            </div>
                            
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    startAutoUpdate(<?= $print['id']; ?>, 'print-status-card-<?= $print['id']; ?>');
                                });
                            </script>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Impressões Recentes Concluídas -->
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">Impressões Recentes</h2>
                <span class="badge bg-secondary"><?= count($completedPrints); ?> concluída(s)</span>
            </div>
            <hr>
            
            <?php if (empty($completedPrints)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Você não possui impressões concluídas recentemente.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Produto</th>
                                <th>Pedido</th>
                                <th>Status</th>
                                <th>Concluído em</th>
                                <th>Tempo Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completedPrints as $print): ?>
                                <tr>
                                    <td><?= htmlspecialchars($print['product_name']); ?></td>
                                    <td>#<?= $print['order_number']; ?></td>
                                    <td><?= PrintStatusHelper::getStatusBadge($print['status']); ?></td>
                                    <td><?= PrintStatusHelper::formatDate($print['completed_at']); ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($print['total_print_time_seconds'])) {
                                            echo PrintStatusHelper::formatTimeInterval($print['total_print_time_seconds']);
                                        } else if (!empty($print['elapsed_print_time_seconds'])) {
                                            echo PrintStatusHelper::formatTimeInterval($print['elapsed_print_time_seconds']);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL; ?>print-monitor/details/<?= $print['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-info-circle"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Histórico e Ajuda -->
    <div class="row mt-5">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="h5 mb-0">Histórico Completo</h3>
                </div>
                <div class="card-body">
                    <p>Para visualizar o histórico completo de impressões dos seus pedidos, acesse a seção de pedidos na sua conta.</p>
                    <a href="<?= BASE_URL; ?>account/orders" class="btn btn-primary">
                        <i class="bi bi-clock-history"></i> Ver Histórico de Pedidos
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="h5 mb-0">Ajuda</h3>
                </div>
                <div class="card-body">
                    <p>Tem dúvidas sobre o processo de impressão ou precisa de ajuda?</p>
                    <a href="<?= BASE_URL; ?>contact" class="btn btn-outline-primary">
                        <i class="bi bi-question-circle"></i> Fale Conosco
                    </a>
                    <a href="<?= BASE_URL; ?>documentation/impressao3d" class="btn btn-outline-secondary ms-2">
                        <i class="bi bi-book"></i> Documentação
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Incluir CSS e JavaScript do monitor -->
<?php echo $css; ?>
<?php echo $js; ?>

<?php include_once VIEWS_PATH . '/partials/footer.php'; ?>
