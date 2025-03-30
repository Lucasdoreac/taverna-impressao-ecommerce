<?php 
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * View para visualização de relatórios de monitoramento de performance em produção
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

// Incluir partial do header do admin
include(ROOT_PATH . '/app/views/partials/admin_header.php'); 
?>

<div class="admin-content">
    <div class="admin-header">
        <h1>Monitoramento de Performance</h1>
        <p class="description">Análise de métricas de desempenho coletadas em ambiente de produção.</p>
    </div>
    
    <div class="admin-card">
        <div class="card-header">
            <h2>Relatórios Disponíveis</h2>
            
            <div class="date-filter">
                <form action="<?= BASE_URL ?>admin/performance/reports" method="GET">
                    <label for="date">Selecionar Data:</label>
                    <select name="date" id="date" onchange="this.form.submit()">
                        <?php if (empty($availableDates)): ?>
                            <option value="">Nenhum dado disponível</option>
                        <?php else: ?>
                            <?php foreach ($availableDates as $date): ?>
                                <option value="<?= $date ?>" <?= $date === $selectedDate ? 'selected' : '' ?>>
                                    <?= date('d/m/Y', strtotime($date)) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </form>
            </div>
        </div>
        
        <?php if (!empty($analysis) && isset($analysis['results']) && !empty($analysis['results'])): ?>
            <div class="card-content">
                <div class="stats-summary">
                    <div class="stat-card">
                        <h3>Total de Amostras</h3>
                        <span class="stat-value"><?= $analysis['total_samples'] ?></span>
                    </div>
                    <div class="stat-card">
                        <h3>URLs Analisadas</h3>
                        <span class="stat-value"><?= $analysis['urls_analyzed'] ?></span>
                    </div>
                    <div class="stat-card">
                        <h3>Período</h3>
                        <span class="stat-value"><?= date('d/m/Y', strtotime($analysis['period']['start_date'])) ?></span>
                    </div>
                </div>
                
                <h3 class="section-title">Desempenho por URL</h3>
                
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>URL</th>
                                <th>Amostras</th>
                                <th>Tempo Médio (ms)</th>
                                <th>Tempo Mínimo (ms)</th>
                                <th>Tempo Máximo (ms)</th>
                                <th>Memória (MB)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analysis['results'] as $result): ?>
                                <tr>
                                    <td><?= htmlspecialchars($result['url']) ?></td>
                                    <td><?= $result['sample_count'] ?></td>
                                    <td>
                                        <?php 
                                        $avgTime = round($result['avg_execution_time_ms'], 2);
                                        $timeClass = $avgTime > 500 ? 'text-danger' : ($avgTime > 300 ? 'text-warning' : 'text-success');
                                        echo '<span class="' . $timeClass . '">' . $avgTime . '</span>';
                                        ?>
                                    </td>
                                    <td><?= round($result['min_execution_time_ms'], 2) ?></td>
                                    <td><?= round($result['max_execution_time_ms'], 2) ?></td>
                                    <td><?= round($result['avg_memory_used_bytes'] / (1024 * 1024), 2) ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = $avgTime > 500 ? 'status-danger' : ($avgTime > 300 ? 'status-warning' : 'status-success');
                                        $statusText = $avgTime > 500 ? 'Lento' : ($avgTime > 300 ? 'Médio' : 'Rápido');
                                        echo '<span class="status-badge ' . $statusClass . '">' . $statusText . '</span>';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (!empty($recommendations)): ?>
                    <div class="recommendations-section">
                        <h3 class="section-title">Recomendações de Otimização</h3>
                        <div class="recommendation-content">
                            <ul class="recommendation-list">
                                <?php foreach ($recommendations as $recommendation): ?>
                                    <li><?= htmlspecialchars($recommendation) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="chart-container">
                    <h3 class="section-title">Gráfico de Desempenho</h3>
                    <canvas id="performanceChart" width="800" height="400"></canvas>
                </div>
            </div>
            
            <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
            <script>
                // Preparar dados para o gráfico
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('performanceChart').getContext('2d');
                    
                    // Extrair dados da análise
                    const urls = <?= json_encode(array_map(function($result) {
                        // Simplificar URLs longas para exibição
                        $url = $result['url'];
                        return strlen($url) > 30 ? '...' . substr($url, -30) : $url;
                    }, $analysis['results'])) ?>;
                    
                    const avgTimes = <?= json_encode(array_map(function($result) {
                        return round($result['avg_execution_time_ms'], 2);
                    }, $analysis['results'])) ?>;
                    
                    const maxTimes = <?= json_encode(array_map(function($result) {
                        return round($result['max_execution_time_ms'], 2);
                    }, $analysis['results'])) ?>;
                    
                    // Configurar gráfico
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: urls,
                            datasets: [
                                {
                                    label: 'Tempo Médio (ms)',
                                    data: avgTimes,
                                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Tempo Máximo (ms)',
                                    data: maxTimes,
                                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                                    borderColor: 'rgba(255, 99, 132, 1)',
                                    borderWidth: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Tempo de Execução (ms)'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'URLs'
                                    }
                                }
                            },
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Tempos de Execução por URL'
                                },
                                legend: {
                                    position: 'top',
                                },
                                tooltip: {
                                    callbacks: {
                                        title: function(tooltipItems) {
                                            // Mostrar URL completa no tooltip
                                            const urlIndex = tooltipItems[0].dataIndex;
                                            return <?= json_encode(array_map(function($result) {
                                                return $result['url'];
                                            }, $analysis['results'])) ?>[urlIndex];
                                        }
                                    }
                                }
                            }
                        }
                    });
                });
            </script>
        <?php else: ?>
            <div class="card-content">
                <div class="no-data-message">
                    <i class="fas fa-chart-line"></i>
                    <p>Nenhum dado de performance disponível para a data selecionada.</p>
                    <p>O monitoramento de performance coleta dados de uma amostra de usuários reais em ambiente de produção.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
// Incluir partial do footer do admin
include(ROOT_PATH . '/app/views/partials/admin_footer.php'); 
?>