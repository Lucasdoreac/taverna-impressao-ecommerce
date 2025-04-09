<?php
// ⚠️ SEGURANÇA: Verificação de autenticação e autorização
if (!$this->securityManager->isAuthenticated() || !$this->securityManager->hasPermission('admin_reports_view')) {
    $this->redirect('admin/login');
    return;
}

// ⚠️ SEGURANÇA: Headers HTTP de segurança
$this->securityHeaders->apply();

// ⚠️ SEGURANÇA: Obtenção de token CSRF
$csrfToken = $this->securityManager->getCsrfToken();

// ⚠️ SEGURANÇA: Validação e sanitização de parâmetros de entrada
$period = isset($_GET['period']) ? htmlspecialchars($_GET['period'], ENT_QUOTES, 'UTF-8') : 'month';
$validPeriods = ['month', 'quarter', 'year', 'all'];
if (!in_array($period, $validPeriods)) {
    $period = 'month'; // Valor padrão seguro
}

?>

<div class="admin-reports-container">
    <div class="report-header">
        <h1>Relatório de Impressão 3D</h1>
        <div class="report-actions">
            <form method="GET" action="<?= htmlspecialchars($this->url('admin/reports/printing'), ENT_QUOTES, 'UTF-8') ?>">
                <select name="period" class="form-control">
                    <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Mensal</option>
                    <option value="quarter" <?= $period === 'quarter' ? 'selected' : '' ?>>Trimestral</option>
                    <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Anual</option>
                    <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>Todo Histórico</option>
                </select>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </form>
            
            <!-- ⚠️ SEGURANÇA: Formulário POST com token CSRF -->
            <form method="POST" action="<?= htmlspecialchars($this->url('admin/reports/printing/export'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="period" value="<?= htmlspecialchars($period, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" name="format" value="pdf" class="btn btn-secondary">Exportar PDF</button>
                <button type="submit" name="format" value="excel" class="btn btn-secondary">Exportar Excel</button>
            </form>
        </div>
    </div>
    
    <div class="report-content">
        <!-- Utilização de Impressoras -->
        <div class="report-card">
            <h3>Utilização de Impressoras</h3>
            <div class="chart-container" id="printer-usage-chart">
                <?php if (empty($printerUsage)): ?>
                    <div class="no-data-message">Nenhum dado disponível para o período selecionado.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Impressora</th>
                                <th>Horas Totais</th>
                                <th>Trabalhos</th>
                                <th>Taxa de Utilização</th>
                                <th>Eficiência</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($printerUsage as $printer): ?>
                            <tr>
                                <td><?= htmlspecialchars($printer['printer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= number_format($printer['total_hours'], 1) ?>h</td>
                                <td><?= (int)$printer['job_count'] ?></td>
                                <td><?= number_format($printer['utilization_rate'], 1) ?>%</td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar 
                                            <?php if ($printer['efficiency'] >= 90): ?>
                                                bg-success
                                            <?php elseif ($printer['efficiency'] >= 70): ?>
                                                bg-info
                                            <?php elseif ($printer['efficiency'] >= 50): ?>
                                                bg-warning
                                            <?php else: ?>
                                                bg-danger
                                            <?php endif; ?>" 
                                            role="progressbar" 
                                            style="width: <?= (int)$printer['efficiency'] ?>%"
                                            aria-valuenow="<?= (int)$printer['efficiency'] ?>" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100">
                                            <?= number_format($printer['efficiency'], 1) ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Consumo de Filamento -->
        <div class="report-card">
            <h3>Consumo de Filamento</h3>
            <div class="chart-container" id="filament-usage-chart">
                <?php if (empty($filamentUsage)): ?>
                    <div class="no-data-message">Nenhum dado de consumo de filamento disponível.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th>Consumo (g)</th>
                                <th>Custo</th>
                                <th>Trabalhos</th>
                                <th>Percentual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalUsage = array_sum(array_column($filamentUsage, 'weight_grams'));
                            foreach ($filamentUsage as $filament): 
                                $percentage = $totalUsage > 0 ? ($filament['weight_grams'] / $totalUsage) * 100 : 0;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($filament['material_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= number_format($filament['weight_grams'], 0) ?> g</td>
                                <td>R$ <?= number_format($filament['cost'], 2, ',', '.') ?></td>
                                <td><?= (int)$filament['job_count'] ?></td>
                                <td><?= number_format($percentage, 1) ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tempo de Impressão -->
        <div class="report-card">
            <h3>Análise de Tempo de Impressão</h3>
            <div class="chart-container" id="print-time-chart">
                <?php if (empty($printTimeReport)): ?>
                    <div class="no-data-message">Nenhum dado de tempo de impressão disponível.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th>Tempo Médio</th>
                                <th>Tempo Total</th>
                                <th>Trabalhos</th>
                                <th>Estimativa vs. Real</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($printTimeReport as $category): ?>
                            <tr>
                                <td><?= htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($category['avg_time_formatted'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($category['total_time_formatted'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int)$category['job_count'] ?></td>
                                <td>
                                    <span class="<?= $category['estimated_vs_real'] <= 100 ? 'positive' : 'negative' ?>">
                                        <?= number_format($category['estimated_vs_real'], 1) ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Análise de Falhas -->
        <div class="report-card">
            <h3>Análise de Falhas</h3>
            <div class="chart-container" id="failure-analysis-chart">
                <?php if (empty($failureAnalysis)): ?>
                    <div class="no-data-message">Nenhum dado de análise de falhas disponível.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tipo de Falha</th>
                                <th>Ocorrências</th>
                                <th>Material</th>
                                <th>Perda Estimada</th>
                                <th>Taxa (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalFailures = array_sum(array_column($failureAnalysis, 'count'));
                            foreach ($failureAnalysis as $failure): 
                                $percentage = $totalFailures > 0 ? ($failure['count'] / $totalFailures) * 100 : 0;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($failure['failure_type'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int)$failure['count'] ?></td>
                                <td><?= htmlspecialchars($failure['most_common_material'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>R$ <?= number_format($failure['estimated_loss'], 2, ',', '.') ?></td>
                                <td><?= number_format($percentage, 1) ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ⚠️ SEGURANÇA: Script com nonce CSP -->
<script nonce="<?= htmlspecialchars($this->securityHeaders->getNonce(), ENT_QUOTES, 'UTF-8') ?>">
    document.addEventListener('DOMContentLoaded', function() {
        // Inicialização segura de gráficos e visualizações
        console.log('Carregando visualizações do relatório de impressão 3D...');
        
        // Qualquer lógica de visualização será executada aqui
        // utilizando apenas dados já sanitizados pelo servidor
    });
</script>
