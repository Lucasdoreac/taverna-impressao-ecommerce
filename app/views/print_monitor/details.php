<?php include_once VIEWS_PATH . '/partials/header.php'; ?>

<div class="container py-4">
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL; ?>print-monitor">Monitoramento</a></li>
                    <li class="breadcrumb-item active">Detalhes da Impressão</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2 mb-0">
                    <?= htmlspecialchars($printStatus['product']['name'] ?? 'Produto'); ?>
                </h1>
                <div>
                    <a href="<?= BASE_URL; ?>account/orders/view/<?= $printStatus['order_id']; ?>" 
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-bag"></i> Ver Pedido #<?= $printStatus['order']['order_number'] ?? ''; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Status e Progresso -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body" id="print-status-main-panel">
                    <!-- O conteúdo será atualizado dinamicamente -->
                    <?= PrintStatusHelper::renderMiniDashboard($printStatus, false); ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Métricas e Gráficos -->
        <div class="col-md-7 mb-4">
            <div class="card">
                <div class="card-header">
                    <h2 class="h5 mb-0">Métricas em Tempo Real</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($metrics['temperatures']) && empty($metrics['progress'])): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Nenhuma métrica disponível no momento.
                        </div>
                    <?php else: ?>
                        <!-- Temperatura -->
                        <?php if (!empty($metrics['temperatures'])): ?>
                            <div class="mb-4">
                                <h3 class="h6">Temperatura</h3>
                                <div class="chart-container" style="position: relative; height: 200px;">
                                    <canvas id="temperatureChart"></canvas>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Progresso por Camada -->
                        <?php if (!empty($metrics['progress'])): ?>
                            <div>
                                <h3 class="h6">Progresso por Camada</h3>
                                <div class="chart-container" style="position: relative; height: 200px;">
                                    <canvas id="layerChart"></canvas>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Adicionar script para inicializar os gráficos -->
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                // Dados para os gráficos
                                const temperatureData = <?= json_encode($metrics['temperatures']); ?>;
                                const progressData = <?= json_encode($metrics['progress']); ?>;
                                const timeLabels = <?= json_encode($metrics['times']); ?>;
                                
                                // Inicializar gráficos se houver dados
                                if (temperatureData.length > 0) {
                                    initTemperatureChart(timeLabels, temperatureData);
                                }
                                
                                if (progressData.length > 0) {
                                    initLayerChart(timeLabels, progressData);
                                }
                            });
                            
                            // Função para inicializar gráfico de temperatura
                            function initTemperatureChart(labels, data) {
                                const ctx = document.getElementById('temperatureChart').getContext('2d');
                                new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: labels,
                                        datasets: [
                                            {
                                                label: 'Extrusor (°C)',
                                                data: data.map(item => item.hotend),
                                                borderColor: '#dc3545',
                                                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                                tension: 0.3,
                                                pointRadius: 1
                                            },
                                            {
                                                label: 'Mesa (°C)',
                                                data: data.map(item => item.bed),
                                                borderColor: '#007bff',
                                                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                                                tension: 0.3,
                                                pointRadius: 1
                                            }
                                        ]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        scales: {
                                            y: {
                                                beginAtZero: false,
                                                title: {
                                                    display: true,
                                                    text: 'Temperatura (°C)'
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                            
                            // Função para inicializar gráfico de camadas
                            function initLayerChart(labels, data) {
                                const ctx = document.getElementById('layerChart').getContext('2d');
                                new Chart(ctx, {
                                    type: 'line',
                                    data: {
                                        labels: labels,
                                        datasets: [
                                            {
                                                label: 'Camada Atual',
                                                data: data.map(item => item.layer),
                                                borderColor: '#28a745',
                                                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                                tension: 0.1,
                                                pointRadius: 1,
                                                yAxisID: 'y'
                                            },
                                            {
                                                label: 'Progresso (%)',
                                                data: data.map(item => item.percentage),
                                                borderColor: '#6f42c1',
                                                backgroundColor: 'rgba(111, 66, 193, 0.1)',
                                                tension: 0.1,
                                                pointRadius: 1,
                                                yAxisID: 'y1',
                                                hidden: true
                                            }
                                        ]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                title: {
                                                    display: true,
                                                    text: 'Camada'
                                                },
                                                position: 'left'
                                            },
                                            y1: {
                                                beginAtZero: true,
                                                max: 100,
                                                title: {
                                                    display: true,
                                                    text: 'Progresso (%)'
                                                },
                                                position: 'right',
                                                grid: {
                                                    drawOnChartArea: false
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                        </script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Detalhes e Histórico -->
        <div class="col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Informações do Pedido</h2>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Pedido:</span>
                            <strong>#<?= $printStatus['order']['order_number'] ?? ''; ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Produto:</span>
                            <strong><?= htmlspecialchars($printStatus['product']['name'] ?? ''); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Material:</span>
                            <strong><?= htmlspecialchars($printStatus['product']['filament_type'] ?? 'PLA'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Início:</span>
                            <strong><?= PrintStatusHelper::formatDate($printStatus['started_at'] ?? null); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Conclusão Estimada:</span>
                            <strong><?= PrintStatusHelper::formatDate($printStatus['estimated_completion'] ?? null); ?></strong>
                        </li>
                        <?php if ($printStatus['status'] === 'completed'): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Concluído em:</span>
                            <strong><?= PrintStatusHelper::formatDate($printStatus['completed_at'] ?? null); ?></strong>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Mensagens -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Mensagens</h2>
                    <span class="badge bg-secondary"><?= count($messages); ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($messages)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Nenhuma mensagem disponível.
                        </div>
                    <?php else: ?>
                        <div class="messages-container mb-3" style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($messages as $message): ?>
                                <?php 
                                $alertClass = 'alert-' . ($message['type'] ?? 'info');
                                $time = PrintStatusHelper::formatDate($message['created_at'] ?? null);
                                ?>
                                <div class="alert <?= $alertClass; ?> py-2 mb-2">
                                    <?= htmlspecialchars($message['message']); ?>
                                    <div class="small text-muted mt-1"><?= $time; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($printStatus['status'] !== 'completed' && $printStatus['status'] !== 'failed' && $printStatus['status'] !== 'canceled'): ?>
                        <!-- Formulário para enviar mensagem -->
                        <form id="messageForm" class="mt-3">
                            <div class="input-group">
                                <input type="hidden" name="print_status_id" value="<?= $printStatus['id']; ?>">
                                <input type="text" name="message" class="form-control" placeholder="Enviar mensagem...">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send"></i>
                                </button>
                            </div>
                        </form>
                        
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const form = document.getElementById('messageForm');
                                
                                form.addEventListener('submit', function(e) {
                                    e.preventDefault();
                                    
                                    const printStatusId = form.querySelector('[name="print_status_id"]').value;
                                    const message = form.querySelector('[name="message"]').value.trim();
                                    
                                    if (!message) return;
                                    
                                    // Enviar mensagem via AJAX
                                    const formData = new FormData();
                                    formData.append('print_status_id', printStatusId);
                                    formData.append('message', message);
                                    
                                    fetch('<?= BASE_URL; ?>print-monitor/api-add-message', {
                                        method: 'POST',
                                        body: formData
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            // Limpar campo de mensagem
                                            form.querySelector('[name="message"]').value = '';
                                            
                                            // Atualizar painel principal
                                            updatePrintStatus(printStatusId, 'print-status-main-panel');
                                            
                                            // Adicionar mensagem à lista (opcional)
                                            const messagesContainer = document.querySelector('.messages-container');
                                            if (messagesContainer) {
                                                const messageElement = document.createElement('div');
                                                messageElement.className = 'alert alert-info py-2 mb-2';
                                                messageElement.innerHTML = `
                                                    ${data.message}
                                                    <div class="small text-muted mt-1">${data.timestamp}</div>
                                                `;
                                                messagesContainer.appendChild(messageElement);
                                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                            }
                                        } else {
                                            alert('Erro ao enviar mensagem: ' + (data.error || 'Erro desconhecido'));
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Erro ao enviar mensagem:', error);
                                        alert('Erro ao enviar mensagem. Por favor, tente novamente.');
                                    });
                                });
                            });
                        </script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Histórico de Atualizações -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h2 class="h5 mb-0">Histórico de Atualizações</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($updates)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Nenhuma atualização disponível.
                        </div>
                    <?php else: ?>
                        <?= PrintStatusHelper::renderTimeline($updates); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Atualizar os dados automaticamente -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Iniciar atualização automática
        startAutoUpdate(<?= $printStatus['id']; ?>, 'print-status-main-panel');
    });
</script>

<!-- Incluir CSS e JavaScript do monitor -->
<?php echo $css; ?>
<?php echo $js; ?>

<?php include_once VIEWS_PATH . '/partials/footer.php'; ?>
