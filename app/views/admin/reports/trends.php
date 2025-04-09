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
$period = isset($_GET['period']) ? htmlspecialchars($_GET['period'], ENT_QUOTES, 'UTF-8') : 'year';
$validPeriods = ['quarter', 'year', 'all'];
if (!in_array($period, $validPeriods)) {
    $period = 'year'; // Valor padrão seguro
}

?>

<div class="admin-reports-container">
    <div class="report-header">
        <h1>Análise de Tendências</h1>
        <div class="report-actions">
            <form method="GET" action="<?= htmlspecialchars($this->url('admin/reports/trends'), ENT_QUOTES, 'UTF-8') ?>">
                <select name="period" class="form-control">
                    <option value="quarter" <?= $period === 'quarter' ? 'selected' : '' ?>>Trimestral</option>
                    <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Anual</option>
                    <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>Todo Histórico</option>
                </select>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </form>
            
            <!-- ⚠️ SEGURANÇA: Formulário POST com token CSRF -->
            <form method="POST" action="<?= htmlspecialchars($this->url('admin/reports/trends/export'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="period" value="<?= htmlspecialchars($period, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" name="format" value="pdf" class="btn btn-secondary">Exportar PDF</button>
                <button type="submit" name="format" value="excel" class="btn btn-secondary">Exportar Excel</button>
            </form>
        </div>
    </div>
    
    <div class="report-content">
        <!-- Tendência de Vendas -->
        <div class="report-card">
            <h3>Tendência de Vendas</h3>
            <div class="chart-container" id="sales-trend-chart">
                <?php if (empty($salesTrend)): ?>
                    <div class="no-data-message">Nenhum dado disponível para o período selecionado.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Período</th>
                                <th>Vendas</th>
                                <th>Tendência</th>
                                <th>Variação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salesTrend as $period): ?>
                            <tr>
                                <td><?= htmlspecialchars($period['period_label'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>R$ <?= number_format($period['sales_amount'], 2, ',', '.') ?></td>
                                <td>
                                    <?php if (isset($period['trend_indicator'])): ?>
                                        <span class="trend-indicator <?= $period['trend_indicator'] ?>">
                                            <?php if ($period['trend_indicator'] === 'up'): ?>
                                                <i class="fa fa-arrow-up"></i>
                                            <?php elseif ($period['trend_indicator'] === 'down'): ?>
                                                <i class="fa fa-arrow-down"></i>
                                            <?php else: ?>
                                                <i class="fa fa-minus"></i>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($period['variation_percentage'])): ?>
                                        <span class="<?= $period['variation_percentage'] >= 0 ? 'positive' : 'negative' ?>">
                                            <?= ($period['variation_percentage'] >= 0 ? '+' : '') . number_format($period['variation_percentage'], 1) ?>%
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Tendências de Produtos -->
        <div class="report-card">
            <h3>Tendências de Produtos</h3>
            <div class="chart-container" id="product-trends-chart">
                <?php if (empty($productTrends)): ?>
                    <div class="no-data-message">Nenhum dado de tendência de produtos disponível.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Tendência</th>
                                <th>Variação</th>
                                <th>Projeção</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productTrends as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="trend-indicator <?= $product['trend_indicator'] ?>">
                                        <?php if ($product['trend_indicator'] === 'up'): ?>
                                            <i class="fa fa-arrow-up"></i> Em alta
                                        <?php elseif ($product['trend_indicator'] === 'down'): ?>
                                            <i class="fa fa-arrow-down"></i> Em queda
                                        <?php else: ?>
                                            <i class="fa fa-minus"></i> Estável
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="<?= $product['variation_percentage'] >= 0 ? 'positive' : 'negative' ?>">
                                        <?= ($product['variation_percentage'] >= 0 ? '+' : '') . number_format($product['variation_percentage'], 1) ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php if (isset($product['forecast'])): ?>
                                        <?= htmlspecialchars($product['forecast'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sazonalidade -->
        <div class="report-card">
            <h3>Análise de Sazonalidade</h3>
            <div class="chart-container" id="seasonality-chart">
                <?php if (empty($seasonalityData)): ?>
                    <div class="no-data-message">Nenhum dado de sazonalidade disponível.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Mês</th>
                                <th>Índice Sazonal</th>
                                <th>Pico/Vale</th>
                                <th>Produtos Sazonais</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($seasonalityData as $season): ?>
                            <tr>
                                <td><?= htmlspecialchars($season['month_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= number_format($season['seasonal_index'], 2) ?></td>
                                <td>
                                    <?php if ($season['is_peak']): ?>
                                        <span class="peak"><i class="fa fa-arrow-up"></i> Pico</span>
                                    <?php elseif ($season['is_valley']): ?>
                                        <span class="valley"><i class="fa fa-arrow-down"></i> Vale</span>
                                    <?php else: ?>
                                        <span class="normal">Normal</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($season['seasonal_products'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Previsões -->
        <div class="report-card">
            <h3>Projeções Futuras</h3>
            <div class="chart-container" id="forecast-chart">
                <?php if (empty($forecastData)): ?>
                    <div class="no-data-message">Nenhum dado de previsão disponível.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Período</th>
                                <th>Previsão</th>
                                <th>Intervalo de Confiança</th>
                                <th>Certeza</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($forecastData as $forecast): ?>
                            <tr>
                                <td><?= htmlspecialchars($forecast['period_label'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>R$ <?= number_format($forecast['forecast_amount'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($forecast['confidence_min'], 2, ',', '.') ?> - R$ <?= number_format($forecast['confidence_max'], 2, ',', '.') ?></td>
                                <td><?= number_format($forecast['confidence_level'], 0) ?>%</td>
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
        console.log('Carregando visualizações de análise de tendências...');
        
        // Qualquer lógica de visualização será executada aqui
        // utilizando apenas dados já sanitizados pelo servidor
    });
</script>
