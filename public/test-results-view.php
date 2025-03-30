<?php
/**
 * test-results-view.php
 * 
 * Página para visualizar e analisar os resultados dos testes 
 * do visualizador 3D em dispositivos móveis.
 */

// Incluir configurações
require_once '../app/config/config.php';

// Caminho para o arquivo de resultados
$results_file = ROOT_PATH . '/data/mobile_test_results.json';

// Verificar se existem resultados
$results_exist = file_exists($results_file);
$test_results = [];

if ($results_exist) {
    $file_content = file_get_contents($results_file);
    try {
        $test_results = json_decode($file_content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($test_results)) {
            $test_results = [];
            $results_exist = false;
        }
    } catch (Exception $e) {
        $test_results = [];
        $results_exist = false;
    }
}

// Agrupar resultados por caso de teste
$results_by_test_case = [];
if (!empty($test_results)) {
    foreach ($test_results as $result) {
        $test_case = $result['test_case'] ?? 'unknown';
        if (!isset($results_by_test_case[$test_case])) {
            $results_by_test_case[$test_case] = [];
        }
        $results_by_test_case[$test_case][] = $result;
    }
}

// Calcular estatísticas para cada caso de teste
$test_case_stats = [];
foreach ($results_by_test_case as $test_case => $results) {
    $pass_count = 0;
    $fail_count = 0;
    $partial_count = 0;
    $load_times = [];
    $fps_values = [];
    
    foreach ($results as $result) {
        if ($result['result'] === 'pass') {
            $pass_count++;
        } elseif ($result['result'] === 'fail') {
            $fail_count++;
        } elseif ($result['result'] === 'partial') {
            $partial_count++;
        }
        
        if (isset($result['metrics']['load_time']) && $result['metrics']['load_time'] > 0) {
            $load_times[] = $result['metrics']['load_time'];
        }
        
        if (isset($result['metrics']['fps']) && $result['metrics']['fps'] > 0) {
            $fps_values[] = $result['metrics']['fps'];
        }
    }
    
    $avg_load_time = count($load_times) > 0 ? array_sum($load_times) / count($load_times) : 0;
    $avg_fps = count($fps_values) > 0 ? array_sum($fps_values) / count($fps_values) : 0;
    
    $test_case_stats[$test_case] = [
        'total' => count($results),
        'pass' => $pass_count,
        'fail' => $fail_count,
        'partial' => $partial_count,
        'pass_rate' => count($results) > 0 ? ($pass_count / count($results) * 100) : 0,
        'avg_load_time' => $avg_load_time,
        'avg_fps' => $avg_fps,
        'min_load_time' => count($load_times) > 0 ? min($load_times) : 0,
        'max_load_time' => count($load_times) > 0 ? max($load_times) : 0,
        'min_fps' => count($fps_values) > 0 ? min($fps_values) : 0,
        'max_fps' => count($fps_values) > 0 ? max($fps_values) : 0
    ];
}

// Agrupar por dispositivo
$results_by_device = [];
if (!empty($test_results)) {
    foreach ($test_results as $result) {
        $device = $result['device'] ?? 'unknown';
        if (!isset($results_by_device[$device])) {
            $results_by_device[$device] = [];
        }
        $results_by_device[$device][] = $result;
    }
}

// Agrupar por navegador
$results_by_browser = [];
if (!empty($test_results)) {
    foreach ($test_results as $result) {
        $browser = $result['browser'] ?? 'unknown';
        if (!isset($results_by_browser[$browser])) {
            $results_by_browser[$browser] = [];
        }
        $results_by_browser[$browser][] = $result;
    }
}

// Agrupar por modelo
$results_by_model = [];
if (!empty($test_results)) {
    foreach ($test_results as $result) {
        $model = $result['model'] ?? 'unknown';
        if (!isset($results_by_model[$model])) {
            $results_by_model[$model] = [];
        }
        $results_by_model[$model][] = $result;
    }
}

// Calcular estatísticas gerais
$general_stats = [
    'total_tests' => count($test_results),
    'total_devices' => count($results_by_device),
    'total_browsers' => count($results_by_browser),
    'pass_rate' => 0,
    'avg_load_time' => 0,
    'avg_fps' => 0
];

if (!empty($test_results)) {
    $pass_count = 0;
    $load_times = [];
    $fps_values = [];
    
    foreach ($test_results as $result) {
        if ($result['result'] === 'pass') {
            $pass_count++;
        }
        
        if (isset($result['metrics']['load_time']) && $result['metrics']['load_time'] > 0) {
            $load_times[] = $result['metrics']['load_time'];
        }
        
        if (isset($result['metrics']['fps']) && $result['metrics']['fps'] > 0) {
            $fps_values[] = $result['metrics']['fps'];
        }
    }
    
    $general_stats['pass_rate'] = count($test_results) > 0 ? ($pass_count / count($test_results) * 100) : 0;
    $general_stats['avg_load_time'] = count($load_times) > 0 ? array_sum($load_times) / count($load_times) : 0;
    $general_stats['avg_fps'] = count($fps_values) > 0 ? array_sum($fps_values) / count($fps_values) : 0;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados dos Testes - Visualizador 3D</title>
    
    <!-- Incluir CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            padding-top: 65px;
        }
        
        .header-fixed {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stats-card {
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        .test-case-card {
            margin-bottom: 20px;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .device-browser-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .device-browser-item {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 5px 10px;
            font-size: 14px;
        }
        
        .metric-label {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .metric-value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .metric-progress {
            height: 5px;
            margin-top: 5px;
        }
        
        .results-table th, .results-table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <!-- Cabeçalho fixo -->
    <div class="header-fixed">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center py-2">
                <h4>Resultados dos Testes - Visualizador 3D</h4>
                <div>
                    <a href="<?= BASE_URL ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                    <a href="test-mobile-viewer.php" class="btn btn-primary btn-sm">
                        <i class="bi bi-phone"></i> Continuar Testes
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <?php if (!$results_exist || empty($test_results)): ?>
        <div class="alert alert-info mt-4">
            <h5><i class="bi bi-info-circle"></i> Nenhum resultado de teste encontrado</h5>
            <p>Nenhum teste foi registrado ainda. Para começar os testes, acesse a <a href="test-mobile-viewer.php" class="alert-link">página de testes</a>.</p>
        </div>
        <?php else: ?>
        
        <!-- Cards de Estatísticas Gerais -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Total de Testes</h6>
                        <h2 class="mb-0"><?= number_format($general_stats['total_tests']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Taxa de Sucesso</h6>
                        <h2 class="mb-0"><?= number_format($general_stats['pass_rate'], 1) ?>%</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">Tempo Médio de Carregamento</h6>
                        <h2 class="mb-0"><?= number_format($general_stats['avg_load_time'], 1) ?>s</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6 class="card-title text-muted">FPS Médio</h6>
                        <h2 class="mb-0"><?= number_format($general_stats['avg_fps'], 1) ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gráficos de Estatísticas -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Taxa de Sucesso por Caso de Teste
                    </div>
                    <div class="card-body">
                        <div class="chart-container" id="pass-rate-chart"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Performance por Dispositivo
                    </div>
                    <div class="card-body">
                        <div class="chart-container" id="device-performance-chart"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Resultados por Caso de Teste -->
        <div class="mt-5">
            <h4>Resultados por Caso de Teste</h4>
            
            <?php foreach ($results_by_test_case as $test_case => $results): ?>
            <div class="card test-case-card">
                <div class="card-header">
                    <h5 class="mb-0"><?= htmlspecialchars($test_case) ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-6">
                                    <div class="metric-label">Total de Testes</div>
                                    <div class="metric-value"><?= $test_case_stats[$test_case]['total'] ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="metric-label">Taxa de Sucesso</div>
                                    <div class="metric-value"><?= number_format($test_case_stats[$test_case]['pass_rate'], 1) ?>%</div>
                                    <div class="progress metric-progress">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?= $test_case_stats[$test_case]['pass_rate'] ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-6">
                                    <div class="metric-label">Tempo Médio de Carregamento</div>
                                    <div class="metric-value"><?= number_format($test_case_stats[$test_case]['avg_load_time'], 1) ?>s</div>
                                    <div class="small text-muted">
                                        Min: <?= number_format($test_case_stats[$test_case]['min_load_time'], 1) ?>s / 
                                        Max: <?= number_format($test_case_stats[$test_case]['max_load_time'], 1) ?>s
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="metric-label">FPS Médio</div>
                                    <div class="metric-value"><?= number_format($test_case_stats[$test_case]['avg_fps'], 1) ?></div>
                                    <div class="small text-muted">
                                        Min: <?= number_format($test_case_stats[$test_case]['min_fps'], 1) ?> / 
                                        Max: <?= number_format($test_case_stats[$test_case]['max_fps'], 1) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="metric-label">Dispositivos Testados</div>
                            <div class="device-browser-list">
                                <?php 
                                $tested_devices = [];
                                foreach ($results as $result) {
                                    $device = $result['device'] ?? 'unknown';
                                    if (!in_array($device, $tested_devices)) {
                                        $tested_devices[] = $device;
                                        echo "<div class='device-browser-item'>{$device}</div>";
                                    }
                                }
                                ?>
                            </div>
                            
                            <div class="metric-label mt-3">Navegadores Testados</div>
                            <div class="device-browser-list">
                                <?php 
                                $tested_browsers = [];
                                foreach ($results as $result) {
                                    $browser = $result['browser'] ?? 'unknown';
                                    if (!in_array($browser, $tested_browsers)) {
                                        $tested_browsers[] = $browser;
                                        echo "<div class='device-browser-item'>{$browser}</div>";
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Resultados Detalhados</h6>
                        <div class="table-responsive">
                            <table class="table table-sm results-table">
                                <thead>
                                    <tr>
                                        <th>Dispositivo</th>
                                        <th>Navegador</th>
                                        <th>Tempo de Carregamento</th>
                                        <th>FPS</th>
                                        <th>Resultado</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $result): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($result['device'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($result['browser'] ?? 'N/A') ?></td>
                                        <td><?= isset($result['metrics']['load_time']) ? number_format($result['metrics']['load_time'], 1) . 's' : 'N/A' ?></td>
                                        <td><?= isset($result['metrics']['fps']) ? number_format($result['metrics']['fps'], 1) : 'N/A' ?></td>
                                        <td>
                                            <?php if ($result['result'] === 'pass'): ?>
                                            <span class="badge bg-success">Passou</span>
                                            <?php elseif ($result['result'] === 'fail'): ?>
                                            <span class="badge bg-danger">Falhou</span>
                                            <?php elseif ($result['result'] === 'partial'): ?>
                                            <span class="badge bg-warning text-dark">Parcial</span>
                                            <?php else: ?>
                                            <span class="badge bg-secondary">Desconhecido</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= isset($result['timestamp']) ? date('d/m/Y H:i', strtotime($result['timestamp'])) : 'N/A' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    
    <?php if ($results_exist && !empty($test_results)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Dados para gráfico de taxa de sucesso por caso de teste
        const testCaseData = {
            labels: <?= json_encode(array_keys($test_case_stats)) ?>,
            datasets: [
                {
                    label: 'Taxa de Sucesso (%)',
                    data: <?= json_encode(array_map(function($stat) { return $stat['pass_rate']; }, $test_case_stats)) ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }
            ]
        };
        
        // Dados para gráfico de performance por dispositivo
        const deviceData = {
            labels: <?= json_encode(array_keys($results_by_device)) ?>,
            datasets: [
                {
                    label: 'FPS Médio',
                    data: <?= json_encode(array_map(function($device_results) {
                        $fps_values = array_filter(array_map(function($result) {
                            return isset($result['metrics']['fps']) ? $result['metrics']['fps'] : null;
                        }, $device_results));
                        return count($fps_values) > 0 ? array_sum($fps_values) / count($fps_values) : 0;
                    }, $results_by_device)) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: 'Tempo de Carregamento (s)',
                    data: <?= json_encode(array_map(function($device_results) {
                        $load_times = array_filter(array_map(function($result) {
                            return isset($result['metrics']['load_time']) ? $result['metrics']['load_time'] : null;
                        }, $device_results));
                        return count($load_times) > 0 ? array_sum($load_times) / count($load_times) : 0;
                    }, $results_by_device)) ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                    yAxisID: 'y1'
                }
            ]
        };
        
        // Renderizar gráfico de taxa de sucesso
        const passRateChart = new Chart(
            document.getElementById('pass-rate-chart').getContext('2d'),
            {
                type: 'bar',
                data: testCaseData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Taxa de Sucesso (%)'
                            }
                        }
                    }
                }
            }
        );
        
        // Renderizar gráfico de performance por dispositivo
        const devicePerformanceChart = new Chart(
            document.getElementById('device-performance-chart').getContext('2d'),
            {
                type: 'bar',
                data: deviceData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'FPS Médio'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            },
                            title: {
                                display: true,
                                text: 'Tempo de Carregamento (s)'
                            }
                        }
                    }
                }
            }
        );
    });
    </script>
    <?php endif; ?>
</body>
</html>
