<?php
/**
 * View de relatório detalhado de vendas
 * 
 * Esta view exibe relatórios avançados de vendas com múltiplas visualizações
 * e opções de exportação.
 */

// Incluir header
include_once APP_PATH . '/views/admin/includes/header.php';
include_once APP_PATH . '/views/admin/includes/sidebar.php';

// Obter token CSRF para requisições AJAX
$csrfToken = SecurityManager::getCsrfToken();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1><?= htmlspecialchars($title) ?></h1>
        
        <div class="report-actions">
            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa fa-download"></i> Exportar
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                <li><a class="dropdown-item" href="#" onclick="exportReport('csv'); return false;">CSV</a></li>
                <li><a class="dropdown-item" href="#" onclick="exportReport('pdf'); return false;">PDF</a></li>
                <li><a class="dropdown-item" href="#" onclick="exportReport('excel'); return false;">Excel</a></li>
            </ul>
        </div>
    </div>

    <!-- Seleção de relatório e filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="report-filter-form" method="get" action="<?= BASE_URL ?>admin/reports/sales" class="row g-3">
                <div class="col-md-3">
                    <label for="report-type" class="form-label">Tipo de Relatório</label>
                    <select id="report-type" name="report_type" class="form-select">
                        <option value="overview" <?= $reportType === 'overview' ? 'selected' : '' ?>>Visão Geral</option>
                        <option value="by_date" <?= $reportType === 'by_date' ? 'selected' : '' ?>>Por Data</option>
                        <option value="by_status" <?= $reportType === 'by_status' ? 'selected' : '' ?>>Por Status</option>
                        <option value="by_payment" <?= $reportType === 'by_payment' ? 'selected' : '' ?>>Por Método de Pagamento</option>
                        <option value="by_customer" <?= $reportType === 'by_customer' ? 'selected' : '' ?>>Por Cliente</option>
                        <option value="by_region" <?= $reportType === 'by_region' ? 'selected' : '' ?>>Por Região</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="period" class="form-label">Período</label>
                    <select id="period" name="period" class="form-select">
                        <option value="day" <?= $period === 'day' ? 'selected' : '' ?>>Diário</option>
                        <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Semanal</option>
                        <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Mensal</option>
                        <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Anual</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="start-date" class="form-label">Data Inicial</label>
                    <input type="date" id="start-date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="end-date" class="form-label">Data Final</label>
                    <input type="date" id="end-date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fa fa-filter"></i> Filtrar
                    </button>
                    <button type="button" id="btn-predefined-periods" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        Períodos
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="btn-predefined-periods">
                        <li><a class="dropdown-item" href="#" data-period="today">Hoje</a></li>
                        <li><a class="dropdown-item" href="#" data-period="yesterday">Ontem</a></li>
                        <li><a class="dropdown-item" href="#" data-period="last7days">Últimos 7 dias</a></li>
                        <li><a class="dropdown-item" href="#" data-period="last30days">Últimos 30 dias</a></li>
                        <li><a class="dropdown-item" href="#" data-period="thismonth">Este mês</a></li>
                        <li><a class="dropdown-item" href="#" data-period="lastmonth">Mês passado</a></li>
                        <li><a class="dropdown-item" href="#" data-period="thisyear">Este ano</a></li>
                        <li><a class="dropdown-item" href="#" data-period="lastyear">Ano passado</a></li>
                    </ul>
                </div>
            </form>
        </div>
    </div>

    <?php if ($reportType === 'overview'): ?>
        <!-- Visão Geral -->
        <div class="report-overview">
            <!-- Métricas principais -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="metric-icon">
                                <i class="fa fa-dollar-sign"></i>
                            </div>
                            <h5 class="card-title">Volume de Vendas</h5>
                            <h2 class="metric-value"><?= getCurrencySymbol() ?> <?= number_format($data['salesMetrics']['totalSales'], 2, ',', '.') ?></h2>
                            
                            <?php if ($data['salesMetrics']['growthPercent'] > 0): ?>
                                <p class="metric-change positive">
                                    <i class="fa fa-arrow-up"></i> <?= number_format($data['salesMetrics']['growthPercent'], 1, ',', '.') ?>%
                                    <span class="text-muted">vs. período anterior</span>
                                </p>
                            <?php elseif ($data['salesMetrics']['growthPercent'] < 0): ?>
                                <p class="metric-change negative">
                                    <i class="fa fa-arrow-down"></i> <?= number_format(abs($data['salesMetrics']['growthPercent']), 1, ',', '.') ?>%
                                    <span class="text-muted">vs. período anterior</span>
                                </p>
                            <?php else: ?>
                                <p class="metric-change neutral">
                                    <i class="fa fa-minus"></i> 0%
                                    <span class="text-muted">vs. período anterior</span>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="metric-icon">
                                <i class="fa fa-shopping-cart"></i>
                            </div>
                            <h5 class="card-title">Pedidos</h5>
                            <h2 class="metric-value"><?= number_format($data['salesMetrics']['totalOrders'], 0, ',', '.') ?></h2>
                            <p class="metric-label">Total de pedidos no período</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="metric-icon">
                                <i class="fa fa-tags"></i>
                            </div>
                            <h5 class="card-title">Ticket Médio</h5>
                            <h2 class="metric-value"><?= getCurrencySymbol() ?> <?= number_format($data['salesMetrics']['avgOrderValue'], 2, ',', '.') ?></h2>
                            <p class="metric-label">Valor médio por pedido</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="metric-icon">
                                <i class="fa fa-calendar"></i>
                            </div>
                            <h5 class="card-title">Período</h5>
                            <h2 class="metric-value"><?= date('d/m/Y', strtotime($startDate)) ?> - <?= date('d/m/Y', strtotime($endDate)) ?></h2>
                            <p class="metric-label"><?= dateDiffDescription($startDate, $endDate) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gráfico principal de vendas -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Evolução de Vendas</h5>
                    <div class="btn-group chart-type-selector" role="group">
                        <button type="button" class="btn btn-sm btn-outline-secondary active" data-chart-type="line">Linha</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-chart-type="bar">Barras</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-chart-type="area">Área</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="salesChart" width="100%" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Vendas por categoria e produtos -->
            <div class="row">
                <div class="col-md-5">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Vendas por Categoria</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="categoryChart" width="100%" height="300"></canvas>
                            </div>
                            
                            <div class="category-list mt-3">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Categoria</th>
                                            <th class="text-end">Vendas</th>
                                            <th class="text-end">Pedidos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data['salesByCategory'])): ?>
                                            <tr>
                                                <td colspan="3" class="text-center">Nenhuma venda no período</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($data['salesByCategory'] as $category): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($category['category_name']) ?></td>
                                                    <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($category['total_sales'], 2, ',', '.') ?></td>
                                                    <td class="text-end"><?= number_format($category['order_count'], 0, ',', '.') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-7">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Produtos Mais Vendidos</h5>
                            <a href="<?= BASE_URL ?>admin/reports/products?report_type=top_performers&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn btn-sm btn-outline-primary">
                                Ver Relatório Completo
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Produto</th>
                                            <th class="text-end">Qtd. Vendida</th>
                                            <th class="text-end">Total Vendas</th>
                                            <th class="text-end">Pedidos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data['salesByProduct'])): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Nenhum produto vendido no período</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($data['salesByProduct'] as $product): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if (!empty($product['image'])): ?>
                                                                <img src="<?= BASE_URL ?>uploads/products/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" class="product-thumbnail me-2">
                                                            <?php else: ?>
                                                                <div class="product-thumbnail-placeholder me-2"></div>
                                                            <?php endif; ?>
                                                            <span><?= htmlspecialchars($product['product_name']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="text-end"><?= number_format($product['quantity_sold'], 0, ',', '.') ?></td>
                                                    <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($product['total_sales'], 2, ',', '.') ?></td>
                                                    <td class="text-end"><?= number_format($product['order_count'], 0, ',', '.') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Relatório de vendas diárias -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Relatório de Vendas Diárias</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="export-daily-sales">
                        <i class="fa fa-download"></i> Exportar
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="daily-sales-table">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th class="text-end">Pedidos</th>
                                    <th class="text-end">Vendas</th>
                                    <th class="text-end">Ticket Médio</th>
                                    <th class="text-end">Itens Vendidos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data['dailySales'])): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhuma venda registrada no período.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $totalOrders = 0;
                                    $totalSales = 0;
                                    $totalItems = 0;
                                    
                                    foreach ($data['dailySales'] as $dailySale): 
                                        $totalOrders += $dailySale['order_count'];
                                        $totalSales += $dailySale['total_sales'];
                                        $totalItems += $dailySale['item_count'];
                                        
                                        $avgTicket = $dailySale['order_count'] > 0 ? $dailySale['total_sales'] / $dailySale['order_count'] : 0;
                                    ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($dailySale['date'])) ?></td>
                                            <td class="text-end"><?= number_format($dailySale['order_count'], 0, ',', '.') ?></td>
                                            <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($dailySale['total_sales'], 2, ',', '.') ?></td>
                                            <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($avgTicket, 2, ',', '.') ?></td>
                                            <td class="text-end"><?= number_format($dailySale['item_count'], 0, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($data['dailySales'])): ?>
                                <tfoot>
                                    <tr class="table-active fw-bold">
                                        <td>Total</td>
                                        <td class="text-end"><?= number_format($totalOrders, 0, ',', '.') ?></td>
                                        <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($totalSales, 2, ',', '.') ?></td>
                                        <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($totalOrders > 0 ? $totalSales / $totalOrders : 0, 2, ',', '.') ?></td>
                                        <td class="text-end"><?= number_format($totalItems, 0, ',', '.') ?></td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif ($reportType === 'by_date'): ?>
        <!-- Relatório por Data -->
        <div class="report-by-date">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Vendas por <?= $period === 'day' ? 'Dia' : 'Mês' ?></h5>
                    <div class="btn-group chart-type-selector" role="group">
                        <button type="button" class="btn btn-sm btn-outline-secondary active" data-chart-type="line">Linha</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-chart-type="bar">Barras</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-chart-type="area">Área</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="dateChart" width="100%" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Relatório Detalhado por <?= $period === 'day' ? 'Dia' : 'Mês' ?></h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="export-date-report">
                        <i class="fa fa-download"></i> Exportar
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="date-sales-table">
                            <thead>
                                <tr>
                                    <th><?= $period === 'day' ? 'Data' : 'Mês' ?></th>
                                    <th class="text-end">Pedidos</th>
                                    <th class="text-end">Vendas</th>
                                    <th class="text-end">Ticket Médio</th>
                                    <th class="text-end">Itens Vendidos</th>
                                    <?php if ($period !== 'day'): ?>
                                        <th class="text-end">Média Diária</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($period === 'day' && empty($data['dailySales'])): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhuma venda registrada no período.</td>
                                    </tr>
                                <?php elseif ($period !== 'day' && empty($data['monthlySales'])): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Nenhuma venda registrada no período.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $totalOrders = 0;
                                    $totalSales = 0;
                                    $totalItems = 0;
                                    
                                    if ($period === 'day'):
                                        foreach ($data['dailySales'] as $sale): 
                                            $totalOrders += $sale['order_count'];
                                            $totalSales += $sale['total_sales'];
                                            $totalItems += $sale['item_count'];
                                            
                                            $avgTicket = $sale['order_count'] > 0 ? $sale['total_sales'] / $sale['order_count'] : 0;
                                    ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($sale['date'])) ?></td>
                                            <td class="text-end"><?= number_format($sale['order_count'], 0, ',', '.') ?></td>
                                            <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($sale['total_sales'], 2, ',', '.') ?></td>
                                            <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($avgTicket, 2, ',', '.') ?></td>
                                            <td class="text-end"><?= number_format($sale['item_count'], 0, ',', '.') ?></td>
                                        </tr>
                                    <?php 
                                        endforeach;
                                    else:
                                        foreach ($data['monthlySales'] as $sale): 
                                            $totalOrders += $sale['order_count'];
                                            $totalSales += $sale['total_sales'];
                                            $totalItems += $sale['item_count'];
                                            
                                            // Extrair ano e mês
                                            list($year, $month) = explode('-', $sale['month']);
                                            $monthName = dateMonthName($month, $year);
                                            
                                            // Calcular dias no mês
                                            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                                            $dailyAvg = $sale['total_sales'] / $daysInMonth;
                                    ?>
                                        <tr>
                                            <td><?= $monthName ?></td>
                                            <td class="text-end"><?= number_format($sale['order_count'], 0, ',', '.') ?></td>
                                            <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($sale['total_sales'], 2, ',', '.') ?></td>
                                            <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($sale['avg_order_value'], 2, ',', '.') ?></td>
                                            <td class="text-end"><?= number_format($sale['item_count'], 0, ',', '.') ?></td>
                                            <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($dailyAvg, 2, ',', '.') ?></td>
                                        </tr>
                                    <?php 
                                        endforeach;
                                    endif;
                                    ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (($period === 'day' && !empty($data['dailySales'])) || ($period !== 'day' && !empty($data['monthlySales']))): ?>
                                <tfoot>
                                    <tr class="table-active fw-bold">
                                        <td>Total</td>
                                        <td class="text-end"><?= number_format($totalOrders, 0, ',', '.') ?></td>
                                        <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($totalSales, 2, ',', '.') ?></td>
                                        <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($totalOrders > 0 ? $totalSales / $totalOrders : 0, 2, ',', '.') ?></td>
                                        <td class="text-end"><?= number_format($totalItems, 0, ',', '.') ?></td>
                                        <?php if ($period !== 'day'): ?>
                                            <td class="text-end">-</td>
                                        <?php endif; ?>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif ($reportType === 'by_status'): ?>
        <!-- Relatório por Status -->
        <div class="report-by-status">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Vendas por Status</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="statusChart" width="100%" height="300"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="status-table">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th class="text-end">Pedidos</th>
                                            <th class="text-end">Vendas</th>
                                            <th class="text-end">% do Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data['salesByStatus'])): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Nenhum pedido registrado no período.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php 
                                            $totalOrders = 0;
                                            $totalSales = 0;
                                            
                                            // Primeiro loop para calcular totais
                                            foreach ($data['salesByStatus'] as $status) {
                                                $totalOrders += $status['order_count'];
                                                $totalSales += $status['total_sales'];
                                            }
                                            
                                            // Segundo loop para exibir com porcentagens
                                            foreach ($data['salesByStatus'] as $status): 
                                                $percentOfTotal = $totalSales > 0 ? ($status['total_sales'] / $totalSales) * 100 : 0;
                                            ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-<?= getOrderStatusColor($status['status']) ?>">
                                                            <?= getOrderStatusName($status['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end"><?= number_format($status['order_count'], 0, ',', '.') ?></td>
                                                    <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($status['total_sales'], 2, ',', '.') ?></td>
                                                    <td class="text-end"><?= number_format($percentOfTotal, 1, ',', '.') ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <?php if (!empty($data['salesByStatus'])): ?>
                                        <tfoot>
                                            <tr class="table-active fw-bold">
                                                <td>Total</td>
                                                <td class="text-end"><?= number_format($totalOrders, 0, ',', '.') ?></td>
                                                <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($totalSales, 2, ',', '.') ?></td>
                                                <td class="text-end">100%</td>
                                            </tr>
                                        </tfoot>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif ($reportType === 'by_payment'): ?>
        <!-- Relatório por Método de Pagamento -->
        <div class="report-by-payment">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Vendas por Método de Pagamento</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="chart-container">
                                <canvas id="paymentChart" width="100%" height="300"></canvas>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="payment-table">
                                    <thead>
                                        <tr>
                                            <th>Método de Pagamento</th>
                                            <th class="text-end">Pedidos</th>
                                            <th class="text-end">Vendas</th>
                                            <th class="text-end">Ticket Médio</th>
                                            <th class="text-end">% do Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data['salesByPayment'])): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Nenhum pedido registrado no período.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php 
                                            $totalOrders = 0;
                                            $totalSales = 0;
                                            
                                            // Primeiro loop para calcular totais
                                            foreach ($data['salesByPayment'] as $payment) {
                                                $totalOrders += $payment['order_count'];
                                                $totalSales += $payment['total_sales'];
                                            }
                                            
                                            // Segundo loop para exibir com porcentagens
                                            foreach ($data['salesByPayment'] as $payment): 
                                                $percentOfTotal = $totalSales > 0 ? ($payment['total_sales'] / $totalSales) * 100 : 0;
                                            ?>
                                                <tr>
                                                    <td><?= getPaymentMethodName($payment['payment_method']) ?></td>
                                                    <td class="text-end"><?= number_format($payment['order_count'], 0, ',', '.') ?></td>
                                                    <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($payment['total_sales'], 2, ',', '.') ?></td>
                                                    <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($payment['avg_order_value'], 2, ',', '.') ?></td>
                                                    <td class="text-end"><?= number_format($percentOfTotal, 1, ',', '.') ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <?php if (!empty($data['salesByPayment'])): ?>
                                        <tfoot>
                                            <tr class="table-active fw-bold">
                                                <td>Total</td>
                                                <td class="text-end"><?= number_format($totalOrders, 0, ',', '.') ?></td>
                                                <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($totalSales, 2, ',', '.') ?></td>
                                                <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($totalOrders > 0 ? $totalSales / $totalOrders : 0, 2, ',', '.') ?></td>
                                                <td class="text-end">100%</td>
                                            </tr>
                                        </tfoot>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif ($reportType === 'by_customer'): ?>
        <!-- Relatório por Cliente -->
        <div class="report-by-customer">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Vendas por Cliente</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="export-customer-report">
                        <i class="fa fa-download"></i> Exportar
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="customer-table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Email</th>
                                    <th class="text-end">Pedidos</th>
                                    <th class="text-end">Total Gasto</th>
                                    <th class="text-end">Ticket Médio</th>
                                    <th>Último Pedido</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data['salesByCustomer'])): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Nenhum cliente registrado no período.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($data['salesByCustomer'] as $customer): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($customer['customer_name']) ?></td>
                                            <td><?= htmlspecialchars($customer['customer_email']) ?></td>
                                            <td class="text-end"><?= number_format($customer['order_count'], 0, ',', '.') ?></td>
                                            <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($customer['total_sales'], 2, ',', '.') ?></td>
                                            <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($customer['avg_order_value'], 2, ',', '.') ?></td>
                                            <td><?= date('d/m/Y', strtotime($customer['last_order_date'])) ?></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>admin/users/view/<?= $customer['user_id'] ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif ($reportType === 'by_region'): ?>
        <!-- Relatório por Região -->
        <div class="report-by-region">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Vendas por Estado</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="regionChart" width="100%" height="300"></canvas>
                            </div>
                            
                            <div class="table-responsive mt-4">
                                <table class="table table-sm table-striped" id="region-table">
                                    <thead>
                                        <tr>
                                            <th>Estado</th>
                                            <th class="text-end">Vendas</th>
                                            <th class="text-end">Pedidos</th>
                                            <th class="text-end">Clientes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data['salesByRegion'])): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Nenhuma venda registrada no período.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($data['salesByRegion'] as $region): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($region['state']) ?></td>
                                                    <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($region['total_sales'], 2, ',', '.') ?></td>
                                                    <td class="text-end"><?= number_format($region['order_count'], 0, ',', '.') ?></td>
                                                    <td class="text-end"><?= number_format($region['customer_count'], 0, ',', '.') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Top Cidades</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="city-table">
                                    <thead>
                                        <tr>
                                            <th>Cidade</th>
                                            <th>Estado</th>
                                            <th class="text-end">Pedidos</th>
                                            <th class="text-end">Vendas</th>
                                            <th class="text-end">Clientes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data['salesByCity'])): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Nenhuma venda registrada no período.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($data['salesByCity'] as $city): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($city['city']) ?></td>
                                                    <td><?= htmlspecialchars($city['state']) ?></td>
                                                    <td class="text-end"><?= number_format($city['order_count'], 0, ',', '.') ?></td>
                                                    <td class="text-end"><?= getCurrencySymbol() ?> <?= number_format($city['total_sales'], 2, ',', '.') ?></td>
                                                    <td class="text-end"><?= number_format($city['customer_count'], 0, ',', '.') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Configuração global do Chart.js
document.addEventListener('DOMContentLoaded', function() {
    // Configuração Chart.js
    Chart.defaults.font.family = "'Nunito', 'Segoe UI', 'Arial'";
    Chart.defaults.color = '#555';
    
    // Configurar token CSRF para requisições AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': '<?= $csrfToken ?>'
        }
    });
    
    // Inicializar os gráficos apropriados para o tipo de relatório
    initializeCharts();
    
    // Listeners para ações do usuário
    setupEventListeners();
});

// Função para inicializar os gráficos apropriados
function initializeCharts() {
    const reportType = '<?= $reportType ?>';
    
    switch(reportType) {
        case 'overview':
            loadSalesChart();
            loadCategoryChart();
            break;
            
        case 'by_date':
            loadDateChart();
            break;
            
        case 'by_status':
            loadStatusChart();
            break;
            
        case 'by_payment':
            loadPaymentChart();
            break;
            
        case 'by_region':
            loadRegionChart();
            break;
    }
}

// Configurar listeners de eventos
function setupEventListeners() {
    // Alternar tipo de gráfico para gráficos de linha
    document.querySelectorAll('.chart-type-selector button').forEach(button => {
        button.addEventListener('click', function() {
            // Remover a classe 'active' de todos os botões no mesmo grupo
            this.parentNode.querySelectorAll('button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Adicionar a classe 'active' ao botão clicado
            this.classList.add('active');
            
            // Atualizar o tipo de gráfico
            const chartType = this.getAttribute('data-chart-type');
            updateChartType(chartType);
        });
    });
    
    // Listener para botão de exportação diária
    document.getElementById('export-daily-sales')?.addEventListener('click', function() {
        exportDailySales();
    });
    
    // Listener para botão de exportação por data
    document.getElementById('export-date-report')?.addEventListener('click', function() {
        exportDateReport();
    });
    
    // Listener para botão de exportação por cliente
    document.getElementById('export-customer-report')?.addEventListener('click', function() {
        exportCustomerReport();
    });
    
    // Tratamento de períodos predefinidos
    document.querySelectorAll('[data-period]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            setPredefinedPeriod(this.getAttribute('data-period'));
        });
    });
}

// Carregar gráfico de vendas para a visão geral
function loadSalesChart() {
    const startDate = '<?= $startDate ?>';
    const endDate = '<?= $endDate ?>';
    const period = '<?= $period ?>';
    
    fetch(`<?= BASE_URL ?>admin/reports/api/sales_data?start_date=${startDate}&end_date=${endDate}&period=${period}`)
        .then(response => response.json())
        .then(data => {
            renderSalesChart(data);
        })
        .catch(error => {
            console.error('Erro ao carregar dados de vendas:', error);
        });
}

// Carregar gráfico de vendas por categoria
function loadCategoryChart() {
    const categoryData = <?= json_encode($data['salesByCategory'] ?? []) ?>;
    renderCategoryChart(categoryData);
}

// Carregar gráfico por data
function loadDateChart() {
    const period = '<?= $period ?>';
    let data;
    
    if (period === 'day') {
        data = <?= json_encode($data['dailySales'] ?? []) ?>;
    } else {
        data = <?= json_encode($data['monthlySales'] ?? []) ?>;
    }
    
    renderDateChart(data, period);
}

// Carregar gráfico por status
function loadStatusChart() {
    const statusData = <?= json_encode($data['salesByStatus'] ?? []) ?>;
    renderStatusChart(statusData);
}

// Carregar gráfico por método de pagamento
function loadPaymentChart() {
    const paymentData = <?= json_encode($data['salesByPayment'] ?? []) ?>;
    renderPaymentChart(paymentData);
}

// Carregar gráfico por região
function loadRegionChart() {
    const regionData = <?= json_encode($data['salesByRegion'] ?? []) ?>;
    renderRegionChart(regionData);
}

// Renderizar gráfico de vendas
function renderSalesChart(data) {
    const ctx = document.getElementById('salesChart')?.getContext('2d');
    if (!ctx) return;
    
    // Se já existe um gráfico, destruí-lo
    if (window.salesChart instanceof Chart) {
        window.salesChart.destroy();
    }
    
    // Preparar os dados para o gráfico
    const labels = data.map(item => {
        if ('month' in item) {
            // Para meses, exibir nome do mês
            const [year, month] = item.month.split('-');
            return dateMonthName(month, year);
        } else if ('date' in item) {
            // Para dias, formatar data
            return formatDate(item.date);
        } else {
            return item.date_group;
        }
    });
    
    const salesData = data.map(item => parseFloat(item.total_sales || 0));
    const orderCountData = data.map(item => parseInt(item.order_count || 0));
    
    // Determinar o tipo de gráfico selecionado
    let chartType = 'line';
    const activeButton = document.querySelector('.chart-type-selector button.active');
    if (activeButton) {
        chartType = activeButton.getAttribute('data-chart-type');
    }
    
    // Configurar o tipo de gráfico
    const chartConfig = {
        type: chartType === 'area' ? 'line' : chartType,
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Volume de Vendas (R$)',
                    data: salesData,
                    borderColor: '#4e73df',
                    backgroundColor: chartType === 'area' ? 'rgba(78, 115, 223, 0.1)' : 'rgba(78, 115, 223, 0.8)',
                    borderWidth: 2,
                    fill: chartType === 'area',
                    tension: chartType === 'line' || chartType === 'area' ? 0.3 : 0,
                    yAxisID: 'y'
                },
                {
                    label: 'Número de Pedidos',
                    data: orderCountData,
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.8)',
                    borderWidth: 2,
                    fill: false,
                    tension: chartType === 'line' || chartType === 'area' ? 0.3 : 0,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                if (context.datasetIndex === 0) {
                                    label += '<?= getCurrencySymbol() ?> ' + context.parsed.y.toFixed(2).replace('.', ',');
                                } else {
                                    label += context.parsed.y;
                                }
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        drawBorder: false,
                        display: false
                    }
                },
                y: {
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Volume de Vendas (R$)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '<?= getCurrencySymbol() ?> ' + value.toFixed(0);
                        }
                    }
                },
                y1: {
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Número de Pedidos'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    };
    
    // Criar o gráfico
    window.salesChart = new Chart(ctx, chartConfig);
}

// Renderizar gráfico de categorias
function renderCategoryChart(data) {
    const ctx = document.getElementById('categoryChart')?.getContext('2d');
    if (!ctx) return;
    
    // Se já existe um gráfico, destruí-lo
    if (window.categoryChart instanceof Chart) {
        window.categoryChart.destroy();
    }
    
    // Preparar os dados para o gráfico
    const labels = data.map(item => item.category_name);
    const salesData = data.map(item => parseFloat(item.total_sales));
    
    // Gerar cores para categorias
    const backgroundColors = generateColors(data.length);
    
    // Criar o gráfico
    window.categoryChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: salesData,
                backgroundColor: backgroundColors,
                hoverBackgroundColor: backgroundColors,
                hoverBorderColor: 'white',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = '<?= getCurrencySymbol() ?> ' + parseFloat(context.raw).toFixed(2).replace('.', ',');
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.raw / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Renderizar gráfico por data
function renderDateChart(data, period) {
    const ctx = document.getElementById('dateChart')?.getContext('2d');
    if (!ctx) return;
    
    // Se já existe um gráfico, destruí-lo
    if (window.dateChart instanceof Chart) {
        window.dateChart.destroy();
    }
    
    // Preparar os dados para o gráfico
    const labels = data.map(item => {
        if (period === 'day') {
            return formatDate(item.date);
        } else {
            // Extrair ano e mês
            const [year, month] = item.month.split('-');
            return dateMonthName(month, year);
        }
    });
    
    const salesData = data.map(item => parseFloat(item.total_sales || 0));
    const orderCountData = data.map(item => parseInt(item.order_count || 0));
    
    // Determinar o tipo de gráfico selecionado
    let chartType = 'line';
    const activeButton = document.querySelector('.chart-type-selector button.active');
    if (activeButton) {
        chartType = activeButton.getAttribute('data-chart-type');
    }
    
    // Configurar o tipo de gráfico
    const chartConfig = {
        type: chartType === 'area' ? 'line' : chartType,
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Volume de Vendas (R$)',
                    data: salesData,
                    borderColor: '#4e73df',
                    backgroundColor: chartType === 'area' ? 'rgba(78, 115, 223, 0.1)' : 'rgba(78, 115, 223, 0.8)',
                    borderWidth: 2,
                    fill: chartType === 'area',
                    tension: chartType === 'line' || chartType === 'area' ? 0.3 : 0,
                    yAxisID: 'y'
                },
                {
                    label: 'Número de Pedidos',
                    data: orderCountData,
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.8)',
                    borderWidth: 2,
                    fill: false,
                    tension: chartType === 'line' || chartType === 'area' ? 0.3 : 0,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                if (context.datasetIndex === 0) {
                                    label += '<?= getCurrencySymbol() ?> ' + context.parsed.y.toFixed(2).replace('.', ',');
                                } else {
                                    label += context.parsed.y;
                                }
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        drawBorder: false,
                        display: false
                    }
                },
                y: {
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Volume de Vendas (R$)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '<?= getCurrencySymbol() ?> ' + value.toFixed(0);
                        }
                    }
                },
                y1: {
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Número de Pedidos'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    };
    
    // Criar o gráfico
    window.dateChart = new Chart(ctx, chartConfig);
}

// Renderizar gráfico por status
function renderStatusChart(data) {
    const ctx = document.getElementById('statusChart')?.getContext('2d');
    if (!ctx) return;
    
    // Se já existe um gráfico, destruí-lo
    if (window.statusChart instanceof Chart) {
        window.statusChart.destroy();
    }
    
    // Preparar os dados para o gráfico
    const labels = data.map(item => getOrderStatusName(item.status));
    const salesData = data.map(item => parseFloat(item.total_sales));
    
    // Cores para status de pedido
    const statusColors = [
        '#f6c23e', // pending
        '#4e73df', // processing
        '#1cc88a', // completed
        '#36b9cc', // shipped
        '#e74a3b', // canceled
        '#858796'  // outros
    ];
    
    // Criar o gráfico
    window.statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: salesData,
                backgroundColor: statusColors.slice(0, data.length),
                hoverBackgroundColor: statusColors.slice(0, data.length),
                hoverBorderColor: 'white',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = '<?= getCurrencySymbol() ?> ' + parseFloat(context.raw).toFixed(2).replace('.', ',');
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.raw / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Renderizar gráfico por método de pagamento
function renderPaymentChart(data) {
    const ctx = document.getElementById('paymentChart')?.getContext('2d');
    if (!ctx) return;
    
    // Se já existe um gráfico, destruí-lo
    if (window.paymentChart instanceof Chart) {
        window.paymentChart.destroy();
    }
    
    // Preparar os dados para o gráfico
    const labels = data.map(item => getPaymentMethodName(item.payment_method));
    const salesData = data.map(item => parseFloat(item.total_sales));
    
    // Cores para métodos de pagamento
    const paymentColors = [
        '#4e73df', // credit_card
        '#1cc88a', // boleto
        '#f6c23e', // pix
        '#36b9cc', // bank_transfer
        '#e74a3b', // paypal
        '#6f42c1', // outros
    ];
    
    // Criar o gráfico
    window.paymentChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: salesData,
                backgroundColor: paymentColors.slice(0, data.length),
                hoverBackgroundColor: paymentColors.slice(0, data.length),
                hoverBorderColor: 'white',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = '<?= getCurrencySymbol() ?> ' + parseFloat(context.raw).toFixed(2).replace('.', ',');
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.raw / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

// Renderizar gráfico por região
function renderRegionChart(data) {
    const ctx = document.getElementById('regionChart')?.getContext('2d');
    if (!ctx) return;
    
    // Se já existe um gráfico, destruí-lo
    if (window.regionChart instanceof Chart) {
        window.regionChart.destroy();
    }
    
    // Preparar os dados para o gráfico
    const labels = data.map(item => item.state);
    const salesData = data.map(item => parseFloat(item.total_sales));
    
    // Cores para estados
    const backgroundColors = generateColors(data.length);
    
    // Criar o gráfico
    window.regionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Vendas por Estado',
                data: salesData,
                backgroundColor: backgroundColors,
                borderColor: backgroundColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '<?= getCurrencySymbol() ?> ' + parseFloat(context.raw).toFixed(2).replace('.', ',');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '<?= getCurrencySymbol() ?> ' + value.toFixed(0);
                        }
                    }
                }
            }
        }
    });
}

// Atualizar o tipo de gráfico
function updateChartType(chartType) {
    // Determinar qual gráfico atualizar
    const reportType = '<?= $reportType ?>';
    
    if ((reportType === 'overview' && window.salesChart instanceof Chart) ||
        (reportType === 'by_date' && window.dateChart instanceof Chart)) {
        
        let currentChart;
        
        if (reportType === 'overview') {
            currentChart = window.salesChart;
        } else if (reportType === 'by_date') {
            currentChart = window.dateChart;
        }
        
        const isArea = chartType === 'area';
        
        // Atualizar tipo de gráfico
        currentChart.config.type = isArea ? 'line' : chartType;
        
        // Atualizar configurações de datasets
        currentChart.data.datasets.forEach((dataset, index) => {
            if (index === 0) { // Apenas para o dataset de vendas
                dataset.fill = isArea;
                dataset.backgroundColor = isArea ? 'rgba(78, 115, 223, 0.1)' : 'rgba(78, 115, 223, 0.8)';
            }
            
            dataset.tension = (chartType === 'line' || chartType === 'area') ? 0.3 : 0;
        });
        
        // Atualizar o gráfico
        currentChart.update();
    }
}

// Função para gerar cores para gráficos
function generateColors(count) {
    const baseColors = [
        '#4e73df', // Azul
        '#1cc88a', // Verde
        '#36b9cc', // Ciano
        '#f6c23e', // Amarelo
        '#e74a3b', // Vermelho
        '#6f42c1', // Roxo
        '#fd7e14', // Laranja
        '#20c9a6', // Turquesa
        '#5a5c69', // Cinza
        '#858796'  // Cinza escuro
    ];
    
    // Se temos menos categorias que cores base, retornar as cores necessárias
    if (count <= baseColors.length) {
        return baseColors.slice(0, count);
    }
    
    // Caso contrário, gerar cores adicionais
    const colors = [...baseColors];
    
    for (let i = baseColors.length; i < count; i++) {
        // Gerar cor aleatória
        const r = Math.floor(Math.random() * 200) + 55;
        const g = Math.floor(Math.random() * 200) + 55;
        const b = Math.floor(Math.random() * 200) + 55;
        colors.push(`rgb(${r}, ${g}, ${b})`);
    }
    
    return colors;
}

// Exportar relatório em vários formatos
function exportReport(format) {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    const reportType = document.getElementById('report-type').value;
    
    let url = `<?= BASE_URL ?>admin/reports/export?format=${format}&report_type=${reportType}&start_date=${startDate}&end_date=${endDate}`;
    
    window.location.href = url;
}

// Exportar relatório de vendas diárias
function exportDailySales() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    
    window.location.href = `<?= BASE_URL ?>admin/reports/daily-sales?format=csv&start_date=${startDate}&end_date=${endDate}`;
}

// Exportar relatório por data
function exportDateReport() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    const period = document.getElementById('period').value;
    
    window.location.href = `<?= BASE_URL ?>admin/reports/daily-sales?format=csv&start_date=${startDate}&end_date=${endDate}&period=${period}`;
}

// Exportar relatório por cliente
function exportCustomerReport() {
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    
    window.location.href = `<?= BASE_URL ?>admin/reports/customer-sales?format=csv&start_date=${startDate}&end_date=${endDate}`;
}

// Configurar períodos predefinidos
function setPredefinedPeriod(periodType) {
    const today = new Date();
    let startDate, endDate;
    
    switch(periodType) {
        case 'today':
            startDate = formatDateForInput(today);
            endDate = formatDateForInput(today);
            break;
            
        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            startDate = formatDateForInput(yesterday);
            endDate = formatDateForInput(yesterday);
            break;
            
        case 'last7days':
            const last7Days = new Date(today);
            last7Days.setDate(last7Days.getDate() - 6);
            startDate = formatDateForInput(last7Days);
            endDate = formatDateForInput(today);
            break;
            
        case 'last30days':
            const last30Days = new Date(today);
            last30Days.setDate(last30Days.getDate() - 29);
            startDate = formatDateForInput(last30Days);
            endDate = formatDateForInput(today);
            break;
            
        case 'thismonth':
            const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            startDate = formatDateForInput(firstDayOfMonth);
            endDate = formatDateForInput(today);
            break;
            
        case 'lastmonth':
            const firstDayOfLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const lastDayOfLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
            startDate = formatDateForInput(firstDayOfLastMonth);
            endDate = formatDateForInput(lastDayOfLastMonth);
            break;
            
        case 'thisyear':
            const firstDayOfYear = new Date(today.getFullYear(), 0, 1);
            startDate = formatDateForInput(firstDayOfYear);
            endDate = formatDateForInput(today);
            break;
            
        case 'lastyear':
            const firstDayOfLastYear = new Date(today.getFullYear() - 1, 0, 1);
            const lastDayOfLastYear = new Date(today.getFullYear() - 1, 11, 31);
            startDate = formatDateForInput(firstDayOfLastYear);
            endDate = formatDateForInput(lastDayOfLastYear);
            break;
    }
    
    // Atualizar campos de data
    document.getElementById('start-date').value = startDate;
    document.getElementById('end-date').value = endDate;
    
    // Submeter o formulário automaticamente
    document.getElementById('report-filter-form').submit();
}

// Formatar data para campo input
function formatDateForInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Função auxiliar para formatar data
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('pt-BR');
}

// Função para obter nome do mês e ano
function dateMonthName(month, year) {
    const monthNames = [
        'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
    ];
    
    return monthNames[parseInt(month) - 1] + '/' + year;
}

// Função para obter nome do status de pedido
function getOrderStatusName(status) {
    const statusMap = {
        'pending': 'Pendente',
        'processing': 'Processando',
        'shipped': 'Enviado',
        'delivered': 'Entregue',
        'canceled': 'Cancelado',
        'refunded': 'Reembolsado'
    };
    
    return statusMap[status] || status;
}

// Função para obter cor do status de pedido
function getOrderStatusColor(status) {
    const colorMap = {
        'pending': 'warning',
        'processing': 'info',
        'shipped': 'primary',
        'delivered': 'success',
        'canceled': 'danger',
        'refunded': 'secondary'
    };
    
    return colorMap[status] || 'secondary';
}

// Função para obter nome do método de pagamento
function getPaymentMethodName(method) {
    const methodMap = {
        'credit_card': 'Cartão de Crédito',
        'boleto': 'Boleto',
        'pix': 'PIX',
        'bank_transfer': 'Transferência Bancária',
        'paypal': 'PayPal',
        'cash_on_delivery': 'Pagamento na Entrega'
    };
    
    return methodMap[method] || method;
}
</script>

<style>
.dashboard-container {
    padding: 20px;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.metric-icon {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 2rem;
    opacity: 0.3;
}

.metric-value {
    margin-bottom: 5px;
    font-weight: 700;
}

.metric-label {
    font-size: 0.8rem;
    color: #888;
    margin-bottom: 0;
}

.metric-change {
    font-size: 0.9rem;
    margin-bottom: 0;
}

.metric-change.positive {
    color: #1cc88a;
}

.metric-change.negative {
    color: #e74a3b;
}

.metric-change.neutral {
    color: #858796;
}

.metric-change .text-muted {
    font-size: 0.8rem;
    color: #888 !important;
}

.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

.product-thumbnail, .product-thumbnail-placeholder {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}

.product-thumbnail-placeholder {
    background-color: #e9ecef;
}

.category-list {
    max-height: 250px;
    overflow-y: auto;
}

@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .report-actions {
        margin-top: 15px;
    }
}
</style>

<?php
/**
 * Função auxiliar para calcular a descrição do intervalo de datas
 */
function dateDiffDescription($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $diff = $start->diff($end);
    
    $days = $diff->days;
    
    if ($days == 0) {
        return 'Hoje';
    } elseif ($days == 1) {
        return '1 dia';
    } elseif ($days <= 7) {
        return $days . ' dias';
    } elseif ($days <= 31) {
        $weeks = floor($days / 7);
        return $weeks . ($weeks == 1 ? ' semana' : ' semanas');
    } elseif ($days <= 365) {
        $months = $diff->m;
        return $months . ($months == 1 ? ' mês' : ' meses');
    } else {
        $years = $diff->y;
        return $years . ($years == 1 ? ' ano' : ' anos');
    }
}

/**
 * Função auxiliar para obter nome do mês e ano
 */
function dateMonthName($month, $year) {
    $monthNames = [
        '01' => 'Janeiro',
        '02' => 'Fevereiro',
        '03' => 'Março',
        '04' => 'Abril',
        '05' => 'Maio',
        '06' => 'Junho',
        '07' => 'Julho',
        '08' => 'Agosto',
        '09' => 'Setembro',
        '10' => 'Outubro',
        '11' => 'Novembro',
        '12' => 'Dezembro'
    ];
    
    return $monthNames[$month] . '/' . $year;
}

/**
 * Função auxiliar para obter símbolo da moeda
 */
function getCurrencySymbol() {
    return 'R$';
}

/**
 * Função auxiliar para obter nome do status de pedido
 */
function getOrderStatusName($status) {
    $statusMap = [
        'pending' => 'Pendente',
        'processing' => 'Processando',
        'shipped' => 'Enviado',
        'delivered' => 'Entregue',
        'canceled' => 'Cancelado',
        'refunded' => 'Reembolsado'
    ];
    
    return $statusMap[$status] ?? $status;
}

/**
 * Função auxiliar para obter cor do status de pedido
 */
function getOrderStatusColor($status) {
    $colorMap = [
        'pending' => 'warning',
        'processing' => 'info',
        'shipped' => 'primary',
        'delivered' => 'success',
        'canceled' => 'danger',
        'refunded' => 'secondary'
    ];
    
    return $colorMap[$status] ?? 'secondary';
}

/**
 * Função auxiliar para obter nome do método de pagamento
 */
function getPaymentMethodName($method) {
    $methodMap = [
        'credit_card' => 'Cartão de Crédito',
        'boleto' => 'Boleto',
        'pix' => 'PIX',
        'bank_transfer' => 'Transferência Bancária',
        'paypal' => 'PayPal',
        'cash_on_delivery' => 'Pagamento na Entrega'
    ];
    
    return $methodMap[$method] ?? $method;
}

// Incluir footer
include_once APP_PATH . '/views/admin/includes/footer.php';
?>
