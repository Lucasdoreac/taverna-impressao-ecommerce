<?php
/**
 * Taverna da Impressão - Sistema de E-commerce
 * 
 * View para visualização de relatórios de monitoramento de performance
 * 
 * @version 1.0
 * @author Desenvolvimento Taverna da Impressão
 */

// Incluir header administrativo
require_once 'app/views/admin/partials/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Monitoramento de Performance</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin">Dashboard</a></li>
                        <li class="breadcrumb-item active">Monitoramento de Performance</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Seletor de data -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Selecionar Data de Análise</h3>
                        </div>
                        <div class="card-body">
                            <form method="get" action="<?= BASE_URL ?>admin/performance/reports">
                                <div class="form-group">
                                    <label>Data:</label>
                                    <select name="date" class="form-control" onchange="this.form.submit()">
                                        <?php if (empty($availableDates)): ?>
                                            <option value="">Nenhuma data disponível</option>
                                        <?php else: ?>
                                            <?php foreach ($availableDates as $date): ?>
                                                <option value="<?= $date ?>" <?= $date === $selectedDate ? 'selected' : '' ?>>
                                                    <?= $date ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($analysis) && !isset($analysis['error'])): ?>
                <!-- Resumo da análise -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-info">
                                <h3 class="card-title">Resumo da Análise</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-info"><i class="fas fa-chart-bar"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total de Amostras</span>
                                                <span class="info-box-number"><?= $analysis['total_samples'] ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-success"><i class="fas fa-globe"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">URLs Analisadas</span>
                                                <span class="info-box-number"><?= $analysis['urls_analyzed'] ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box">
                                            <span class="info-box-icon bg-warning"><i class="fas fa-calendar-day"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Período</span>
                                                <span class="info-box-number"><?= $analysis['period']['start_date'] ?> a <?= $analysis['period']['end_date'] ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resultados por URL -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Resultados por URL</h3>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-hover text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>URL</th>
                                            <th>Amostras</th>
                                            <th>Tempo Médio (ms)</th>
                                            <th>Tempo Mínimo (ms)</th>
                                            <th>Tempo Máximo (ms)</th>
                                            <th>Memória Média</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($analysis['results'] as $result): ?>
                                            <tr>
                                                <td>
                                                    <span title="<?= $result['url'] ?>">
                                                        <?= strlen($result['url']) > 50 ? substr($result['url'], 0, 47) . '...' : $result['url'] ?>
                                                    </span>
                                                </td>
                                                <td><?= $result['sample_count'] ?></td>
                                                <td>
                                                    <?php
                                                    $avgTime = round($result['avg_execution_time_ms'], 2);
                                                    $timeClass = $avgTime > 500 ? 'text-danger' : ($avgTime > 200 ? 'text-warning' : 'text-success');
                                                    echo "<span class=\"{$timeClass}\">{$avgTime}</span>";
                                                    ?>
                                                </td>
                                                <td><?= round($result['min_execution_time_ms'], 2) ?></td>
                                                <td><?= round($result['max_execution_time_ms'], 2) ?></td>
                                                <td><?= round($result['avg_memory_used_bytes'] / (1024 * 1024), 2) ?> MB</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recomendações -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h3 class="card-title">Recomendações</h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recommendations)): ?>
                                    <p>Nenhuma recomendação disponível.</p>
                                <?php else: ?>
                                    <ul class="list-group">
                                        <?php foreach ($recommendations as $recommendation): ?>
                                            <li class="list-group-item">
                                                <?= $recommendation ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráfico de Desempenho -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Gráfico de Tempos de Execução</h3>
                            </div>
                            <div class="card-body">
                                <canvas id="performanceChart" style="min-height: 400px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Preparar dados para o gráfico
                        const ctx = document.getElementById('performanceChart').getContext('2d');
                        
                        // Extrair até 10 urls mais lentas
                        const urlData = <?= json_encode(array_slice($analysis['results'], 0, 10)) ?>;
                        
                        // Preparar labels e dados
                        const labels = urlData.map(item => {
                            // Recortar URL longa
                            const url = item.url;
                            return url.length > 30 ? url.substring(0, 27) + '...' : url;
                        });
                        
                        const avgTimes = urlData.map(item => item.avg_execution_time_ms);
                        const minTimes = urlData.map(item => item.min_execution_time_ms);
                        const maxTimes = urlData.map(item => item.max_execution_time_ms);
                        
                        // Criar gráfico
                        const chart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [
                                    {
                                        label: 'Tempo Médio (ms)',
                                        data: avgTimes,
                                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                        borderColor: 'rgb(54, 162, 235)',
                                        borderWidth: 1
                                    },
                                    {
                                        label: 'Tempo Mínimo (ms)',
                                        data: minTimes,
                                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                                        borderColor: 'rgb(75, 192, 192)',
                                        borderWidth: 1
                                    },
                                    {
                                        label: 'Tempo Máximo (ms)',
                                        data: maxTimes,
                                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                                        borderColor: 'rgb(255, 99, 132)',
                                        borderWidth: 1
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    x: {
                                        stacked: false,
                                        title: {
                                            display: true,
                                            text: 'URLs'
                                        }
                                    },
                                    y: {
                                        stacked: false,
                                        title: {
                                            display: true,
                                            text: 'Tempo (ms)'
                                        },
                                        beginAtZero: true
                                    }
                                },
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Tempos de Execução por URL'
                                    },
                                    tooltip: {
                                        callbacks: {
                                            title: function(tooltipItems) {
                                                const index = tooltipItems[0].dataIndex;
                                                return urlData[index].url;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    });
                </script>
            <?php elseif (!empty($analysis) && isset($analysis['error'])): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-danger">
                            <h5><i class="icon fas fa-ban"></i> Erro!</h5>
                            <?= $analysis['error'] ?>
                        </div>
                    </div>
                </div>
            <?php elseif (empty($availableDates)): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h5><i class="icon fas fa-info"></i> Informação</h5>
                            Nenhum dado de monitoramento disponível. Os dados serão coletados automaticamente à medida que os usuários navegam pelo site.
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<!-- Incluir footer administrativo -->
<?php require_once 'app/views/admin/partials/footer.php'; ?>
