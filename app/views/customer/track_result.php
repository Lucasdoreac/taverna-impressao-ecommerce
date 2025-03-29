<?php require_once APP_ROOT . '/views/partials/header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-light">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Início</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>print_queue/customerTrack">Rastrear Impressão</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Resultado #<?php echo $order['order_number']; ?></li>
                </ol>
            </nav>
            
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-search-location mr-2"></i> Rastreamento do Pedido #<?php echo $order['order_number']; ?></h4>
                        <a href="<?php echo BASE_URL; ?>print_queue/customerTrack" class="btn btn-light btn-sm">
                            <i class="fas fa-search mr-2"></i> Nova Consulta
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2">Informações do Pedido</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Número do Pedido:</th>
                                    <td><strong>#<?php echo $order['order_number']; ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Data do Pedido:</th>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Status do Pedido:</th>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                            if ($order['status'] === 'pending') echo 'badge-light';
                                            elseif ($order['status'] === 'validating') echo 'badge-info';
                                            elseif ($order['status'] === 'printing') echo 'badge-primary';
                                            elseif ($order['status'] === 'finishing') echo 'badge-warning';
                                            elseif ($order['status'] === 'shipped') echo 'badge-success';
                                            elseif ($order['status'] === 'delivered') echo 'badge-success';
                                            elseif ($order['status'] === 'canceled') echo 'badge-danger';
                                            ?>
                                        ">
                                            <?php 
                                            if ($order['status'] === 'pending') echo 'Aguardando Pagamento';
                                            elseif ($order['status'] === 'validating') echo 'Validando';
                                            elseif ($order['status'] === 'printing') echo 'Em Impressão';
                                            elseif ($order['status'] === 'finishing') echo 'Finalizando';
                                            elseif ($order['status'] === 'shipped') echo 'Enviado';
                                            elseif ($order['status'] === 'delivered') echo 'Entregue';
                                            elseif ($order['status'] === 'canceled') echo 'Cancelado';
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Método de Envio:</th>
                                    <td><?php echo $order['shipping_method']; ?></td>
                                </tr>
                                <?php if (!empty($order['tracking_code'])): ?>
                                <tr>
                                    <th>Código de Rastreio:</th>
                                    <td><?php echo $order['tracking_code']; ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Valor Total:</th>
                                    <td><?php echo CURRENCY_SYMBOL . ' ' . number_format($order['total'], 2, ',', '.'); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2">Status da Impressão 3D</h5>
                            <?php if (empty($queueItems)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i> Nenhum item deste pedido foi adicionado à fila de impressão ainda.
                                </div>
                            <?php else: ?>
                                <div class="progress-tracker">
                                    <?php foreach($queueItems as $index => $item): ?>
                                        <div class="progress-item mb-4">
                                            <div class="d-flex justify-content-between">
                                                <strong><?php echo $item['product_name']; ?></strong>
                                                <span class="badge 
                                                    <?php 
                                                    if ($item['status'] === 'pending') echo 'badge-light';
                                                    elseif ($item['status'] === 'scheduled') echo 'badge-info';
                                                    elseif ($item['status'] === 'printing') echo 'badge-primary';
                                                    elseif ($item['status'] === 'paused') echo 'badge-warning';
                                                    elseif ($item['status'] === 'completed') echo 'badge-success';
                                                    elseif ($item['status'] === 'failed') echo 'badge-danger';
                                                    elseif ($item['status'] === 'canceled') echo 'badge-secondary';
                                                    ?>
                                                ">
                                                    <?php 
                                                    if ($item['status'] === 'pending') echo 'Pendente';
                                                    elseif ($item['status'] === 'scheduled') echo 'Agendado';
                                                    elseif ($item['status'] === 'printing') echo 'Imprimindo';
                                                    elseif ($item['status'] === 'paused') echo 'Pausado';
                                                    elseif ($item['status'] === 'completed') echo 'Concluído';
                                                    elseif ($item['status'] === 'failed') echo 'Falha';
                                                    elseif ($item['status'] === 'canceled') echo 'Cancelado';
                                                    ?>
                                                </span>
                                            </div>
                                            
                                            <!-- Barra de progresso -->
                                            <?php
                                            $progress = 0;
                                            $progressClass = 'bg-info';
                                            
                                            if ($item['status'] === 'pending') {
                                                $progress = 5;
                                                $progressClass = 'bg-light';
                                            } elseif ($item['status'] === 'scheduled') {
                                                $progress = 20;
                                                $progressClass = 'bg-info';
                                            } elseif ($item['status'] === 'printing') {
                                                // Se tiver horário de início, calcular progresso baseado no tempo
                                                if (!empty($item['print_start_date'])) {
                                                    $startTime = strtotime($item['print_start_date']);
                                                    $currentTime = time();
                                                    $elapsedHours = ($currentTime - $startTime) / 3600;
                                                    $totalHours = $item['estimated_print_time_hours'];
                                                    
                                                    $progress = min(95, round(($elapsedHours / $totalHours) * 100));
                                                } else {
                                                    $progress = 50; // Valor padrão se não tiver data de início
                                                }
                                                $progressClass = 'bg-primary';
                                            } elseif ($item['status'] === 'paused') {
                                                // Se pausado, manter o progresso atual
                                                $progress = 50;
                                                $progressClass = 'bg-warning';
                                            } elseif ($item['status'] === 'completed') {
                                                $progress = 100;
                                                $progressClass = 'bg-success';
                                            } elseif ($item['status'] === 'failed') {
                                                $progress = 100;
                                                $progressClass = 'bg-danger';
                                            } elseif ($item['status'] === 'canceled') {
                                                $progress = 100;
                                                $progressClass = 'bg-secondary';
                                            }
                                            ?>
                                            <div class="progress mt-2">
                                                <div class="progress-bar <?php echo $progressClass; ?>" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo $progress; ?>%
                                                </div>
                                            </div>
                                            
                                            <small class="text-muted">
                                                <?php if ($item['status'] === 'printing'): ?>
                                                    Tempo estimado: <?php echo $item['estimated_print_time_hours']; ?> horas
                                                <?php elseif ($item['status'] === 'completed' && !empty($item['print_start_date']) && !empty($item['print_finish_date'])): ?>
                                                    Concluído em: <?php echo date('d/m/Y H:i', strtotime($item['print_finish_date'])); ?>
                                                    (Duração: <?php 
                                                    $startTime = strtotime($item['print_start_date']);
                                                    $endTime = strtotime($item['print_finish_date']);
                                                    $totalHours = round(($endTime - $startTime) / 3600, 1);
                                                    echo $totalHours; ?> horas)
                                                <?php elseif ($item['status'] === 'scheduled' && !empty($item['scheduled_start_date'])): ?>
                                                    Agendado para: <?php echo date('d/m/Y H:i', strtotime($item['scheduled_start_date'])); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        
                                        <?php if ($index < count($queueItems) - 1): ?>
                                            <hr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Linha do Tempo -->
                    <h5 class="border-bottom pb-2 mb-3">Linha do Tempo do Pedido</h5>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Pedido Recebido</h6>
                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small>
                                <p>Seu pedido foi recebido com sucesso.</p>
                            </div>
                        </div>
                        
                        <!-- Status de Pagamento -->
                        <div class="timeline-item">
                            <div class="timeline-marker <?php echo $order['payment_status'] === 'paid' ? 'bg-success' : 'bg-secondary'; ?>"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Pagamento</h6>
                                <?php if ($order['payment_status'] === 'paid'): ?>
                                    <small class="text-muted">Confirmado</small>
                                    <p>Pagamento confirmado.</p>
                                <?php elseif ($order['payment_status'] === 'pending'): ?>
                                    <small class="text-muted">Aguardando</small>
                                    <p>Aguardando confirmação do pagamento.</p>
                                <?php else: ?>
                                    <small class="text-muted"><?php echo ucfirst($order['payment_status']); ?></small>
                                    <p>Status de pagamento: <?php echo ucfirst($order['payment_status']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Status de Impressão -->
                        <?php 
                        $printStarted = false;
                        $printFinished = false;
                        
                        foreach($queueItems as $item) {
                            if ($item['status'] === 'printing' || $item['status'] === 'completed') {
                                $printStarted = true;
                            }
                            if ($item['status'] === 'completed') {
                                $printFinished = true;
                            }
                        }
                        ?>
                        
                        <div class="timeline-item">
                            <div class="timeline-marker <?php echo $printStarted ? 'bg-primary' : 'bg-secondary'; ?>"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Impressão 3D</h6>
                                <?php if ($printStarted): ?>
                                    <small class="text-muted">Em andamento</small>
                                    <p>Seu(s) item(ns) está(ão) sendo impresso(s).</p>
                                <?php else: ?>
                                    <small class="text-muted">Aguardando</small>
                                    <p>Seu pedido ainda não entrou na fila de impressão.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-marker <?php echo $printFinished ? 'bg-success' : 'bg-secondary'; ?>"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Impressão Concluída</h6>
                                <?php if ($printFinished): ?>
                                    <small class="text-muted">Finalizado</small>
                                    <p>Todos os itens foram impressos com sucesso.</p>
                                <?php else: ?>
                                    <small class="text-muted">Aguardando</small>
                                    <p>A impressão ainda não foi concluída.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Status de Envio -->
                        <div class="timeline-item">
                            <div class="timeline-marker <?php echo $order['status'] === 'shipped' || $order['status'] === 'delivered' ? 'bg-success' : 'bg-secondary'; ?>"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Envio</h6>
                                <?php if ($order['status'] === 'shipped'): ?>
                                    <small class="text-muted">Enviado</small>
                                    <p>Seu pedido foi enviado.</p>
                                    <?php if (!empty($order['tracking_code'])): ?>
                                        <p>Código de rastreio: <strong><?php echo $order['tracking_code']; ?></strong></p>
                                    <?php endif; ?>
                                <?php elseif ($order['status'] === 'delivered'): ?>
                                    <small class="text-muted">Entregue</small>
                                    <p>Seu pedido foi entregue.</p>
                                <?php else: ?>
                                    <small class="text-muted">Aguardando</small>
                                    <p>Seu pedido ainda não foi enviado.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-marker <?php echo $order['status'] === 'delivered' ? 'bg-success' : 'bg-secondary'; ?>"></div>
                            <div class="timeline-content">
                                <h6 class="mb-0">Entrega</h6>
                                <?php if ($order['status'] === 'delivered'): ?>
                                    <small class="text-muted">Concluído</small>
                                    <p>Seu pedido foi entregue com sucesso.</p>
                                <?php else: ?>
                                    <small class="text-muted">Aguardando</small>
                                    <p>Seu pedido ainda não foi entregue.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <p class="mb-0"><i class="fas fa-info-circle text-info mr-2"></i> Esta página é atualizada automaticamente a cada 5 minutos.</p>
                        <button class="btn btn-primary" onclick="window.location.reload()">
                            <i class="fas fa-sync-alt mr-2"></i> Atualizar Agora
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Dúvidas e Contato -->
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-question-circle mr-2"></i> Dúvidas?</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3 mb-md-0">
                            <i class="fas fa-envelope fa-3x text-info mb-3"></i>
                            <h6>E-mail</h6>
                            <p><a href="mailto:contato@tavernaimpressao.com.br">contato@tavernaimpressao.com.br</a></p>
                        </div>
                        <div class="col-md-4 text-center mb-3 mb-md-0">
                            <i class="fas fa-phone fa-3x text-info mb-3"></i>
                            <h6>Telefone</h6>
                            <p>(00) 0000-0000</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-comments fa-3x text-info mb-3"></i>
                            <h6>Chat Online</h6>
                            <p>Disponível em horário comercial</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilos específicos para esta página -->
<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline:before {
        content: '';
        position: absolute;
        top: 0;
        left: 10px;
        height: 100%;
        width: 2px;
        background-color: #e0e0e0;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 30px;
    }
    
    .timeline-marker {
        position: absolute;
        top: 0;
        left: -30px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        border: 2px solid white;
    }
    
    .timeline-content {
        padding-bottom: 10px;
    }
    
    .progress {
        height: 10px;
    }
</style>

<!-- Script para atualização automática -->
<script>
    // Atualizar a página a cada 5 minutos (300000 ms)
    setTimeout(function() {
        window.location.reload();
    }, 300000);
</script>

<?php require_once APP_ROOT . '/views/partials/footer.php'; ?>