<?php include_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>print-monitor">Monitoramento</a></li>
                    <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>account/orders">Pedidos</a></li>
                    <li class="breadcrumb-item active">Pedido #<?= $order['order_number']; ?></li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h2">Status de Impressão - Pedido #<?= $order['order_number']; ?></h1>
                <a href="<?= BASE_URL; ?>account/orders/view/<?= $order['id']; ?>" class="btn btn-outline-primary">
                    <i class="bi bi-bag"></i> Detalhes do Pedido
                </a>
            </div>
            
            <p class="lead">
                Acompanhe o status de impressão dos itens deste pedido.
                <small class="text-muted d-block">Data do pedido: <?= date('d/m/Y', strtotime($order['created_at'])); ?></small>
            </p>
        </div>
    </div>
    
    <?php if (empty($printStatuses)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Não há impressões vinculadas a este pedido.
        </div>
    <?php else: ?>
        <!-- Sumário do Pedido -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h2 class="h5 mb-0">Resumo do Pedido</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="d-flex flex-column">
                                    <span class="text-muted">Total de Itens</span>
                                    <span class="h4"><?= count($printStatuses); ?></span>
                                </div>
                            </div>
                            
                            <?php
                            // Contadores para estatísticas
                            $totalActive = 0;
                            $totalCompleted = 0;
                            $totalWithIssues = 0;
                            
                            foreach ($printStatuses as $status) {
                                if (in_array($status['status'], ['printing', 'preparing', 'pending', 'paused'])) {
                                    $totalActive++;
                                } else if ($status['status'] === 'completed') {
                                    $totalCompleted++;
                                } else if (in_array($status['status'], ['failed', 'canceled'])) {
                                    $totalWithIssues++;
                                }
                            }
                            ?>
                            
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="d-flex flex-column">
                                    <span class="text-muted">Em Andamento</span>
                                    <span class="h4 text-primary"><?= $totalActive; ?></span>
                                </div>
                            </div>
                            
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="d-flex flex-column">
                                    <span class="text-muted">Concluídos</span>
                                    <span class="h4 text-success"><?= $totalCompleted; ?></span>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="d-flex flex-column">
                                    <span class="text-muted">Com Problemas</span>
                                    <span class="h4 <?= $totalWithIssues > 0 ? 'text-danger' : 'text-muted'; ?>"><?= $totalWithIssues; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($totalActive > 0): ?>
                            <div class="alert alert-primary mt-3 mb-0">
                                <i class="bi bi-info-circle"></i> Você tem <?= $totalActive; ?> impressão(ões) em andamento neste pedido.
                                <?php if ($totalWithIssues > 0): ?>
                                    <span class="ms-2 badge bg-danger">
                                        <i class="bi bi-exclamation-triangle"></i> <?= $totalWithIssues; ?> problema(s)
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lista de Impressões -->
        <?php foreach ($printStatuses as $index => $printStatus): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="h5 mb-0">
                                <?= htmlspecialchars($printStatus['product_name'] ?? "Item #" . ($index + 1)); ?>
                            </h3>
                            <?= PrintStatusHelper::getStatusBadge($printStatus['status'], $printStatus['progress_percentage'] ?? 0); ?>
                        </div>
                        
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3 mb-md-0">
                                    <!-- Informações básicas -->
                                    <p class="mb-2">
                                        <strong>Status:</strong> <?= $printStatus['formatted_status']; ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Iniciado em:</strong> <?= PrintStatusHelper::formatDate($printStatus['started_at'] ?? null); ?>
                                    </p>
                                    <?php if ($printStatus['status'] === 'completed'): ?>
                                        <p class="mb-2">
                                            <strong>Concluído em:</strong> <?= PrintStatusHelper::formatDate($printStatus['completed_at'] ?? null); ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="mb-2">
                                            <strong>Previsão:</strong> <?= PrintStatusHelper::formatDate($printStatus['estimated_completion'] ?? null); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <!-- Progresso -->
                                    <p class="mb-2"><strong>Progresso:</strong></p>
                                    <div class="print-status-progress-<?= $printStatus['id']; ?>">
                                        <?= PrintStatusHelper::getProgressBar(
                                            $printStatus['progress_percentage'] ?? 0, 
                                            $printStatus['status']
                                        ); ?>
                                    </div>
                                    
                                    <!-- Mensagem amigável -->
                                    <p class="mt-3 text-center">
                                        <?= PrintStatusHelper::getFriendlyStatusMessage(
                                            $printStatus['status'], 
                                            $printStatus['progress_percentage'] ?? 0, 
                                            $printStatus['product_name'] ?? ''
                                        ); ?>
                                    </p>
                                </div>
                                
                                <div class="col-md-3 text-center">
                                    <!-- Botão de detalhes -->
                                    <a href="<?= BASE_URL; ?>print-monitor/details/<?= $printStatus['id']; ?>" 
                                       class="btn btn-primary btn-lg d-block mb-2">
                                        <i class="bi bi-graph-up"></i> Acompanhar
                                    </a>
                                    
                                    <?php if (in_array($printStatus['status'], ['printing', 'preparing', 'pending', 'paused'])): ?>
                                        <!-- Script para atualização em tempo real -->
                                        <script>
                                            document.addEventListener('DOMContentLoaded', function() {
                                                const elementId = 'print-status-progress-<?= $printStatus['id']; ?>';
                                                startAutoUpdate(<?= $printStatus['id']; ?>, elementId);
                                            });
                                        </script>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Seção de Ajuda -->
    <div class="row mt-5">
        <div class="col-md-6 offset-md-3">
            <div class="card">
                <div class="card-header bg-light">
                    <h2 class="h5 mb-0">Precisa de Ajuda?</h2>
                </div>
                <div class="card-body">
                    <p>Se você tiver dúvidas sobre o status de impressão do seu pedido ou precisar de suporte adicional, entre em contato conosco:</p>
                    
                    <div class="d-flex justify-content-around mt-4">
                        <a href="<?= BASE_URL; ?>contact" class="btn btn-outline-primary">
                            <i class="bi bi-chat-dots"></i> Fale Conosco
                        </a>
                        
                        <a href="<?= BASE_URL; ?>documentation/impressao3d" class="btn btn-outline-secondary">
                            <i class="bi bi-book"></i> Documentação
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Incluir CSS e JavaScript do monitor -->
<?php echo $css; ?>
<?php echo $js; ?>

<?php include_once VIEWS_PATH . '/partials/footer.php'; ?>
