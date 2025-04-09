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
$validPeriods = ['day', 'week', 'month', 'quarter', 'year'];
if (!in_array($period, $validPeriods)) {
    $period = 'month'; // Valor padrão seguro
}

?>

<div class="admin-reports-container">
    <div class="report-header">
        <h1>Relatório de Clientes</h1>
        <div class="report-actions">
            <form method="GET" action="<?= htmlspecialchars($this->url('admin/reports/customers'), ENT_QUOTES, 'UTF-8') ?>">
                <select name="period" class="form-control">
                    <option value="day" <?= $period === 'day' ? 'selected' : '' ?>>Diário</option>
                    <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Semanal</option>
                    <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Mensal</option>
                    <option value="quarter" <?= $period === 'quarter' ? 'selected' : '' ?>>Trimestral</option>
                    <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Anual</option>
                </select>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </form>
            
            <!-- ⚠️ SEGURANÇA: Formulário POST com token CSRF -->
            <form method="POST" action="<?= htmlspecialchars($this->url('admin/reports/customers/export'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="period" value="<?= htmlspecialchars($period, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" name="format" value="pdf" class="btn btn-secondary">Exportar PDF</button>
                <button type="submit" name="format" value="excel" class="btn btn-secondary">Exportar Excel</button>
            </form>
        </div>
    </div>
    
    <div class="report-content">
        <!-- Novos Clientes -->
        <div class="report-card">
            <h3>Novos Clientes</h3>
            <div class="chart-container" id="new-customers-chart">
                <?php if (empty($newCustomers)): ?>
                    <div class="no-data-message">Nenhum dado disponível para o período selecionado.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Período</th>
                                <th>Novos Registros</th>
                                <th>Taxa de Crescimento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($newCustomers as $entry): ?>
                            <tr>
                                <td><?= htmlspecialchars($entry['period_label'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int)$entry['count'] ?></td>
                                <td>
                                    <?php if (isset($entry['growth'])): ?>
                                        <span class="<?= $entry['growth'] >= 0 ? 'positive' : 'negative' ?>">
                                            <?= ($entry['growth'] >= 0 ? '+' : '') . number_format($entry['growth'], 1) ?>%
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
        
        <!-- Clientes Mais Ativos -->
        <div class="report-card">
            <h3>Clientes Mais Ativos</h3>
            <div class="chart-container" id="active-customers-chart">
                <?php if (empty($activeCustomers)): ?>
                    <div class="no-data-message">Nenhum dado disponível para o período selecionado.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Pedidos</th>
                                <th>Valor Total</th>
                                <th>Último Pedido</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeCustomers as $customer): ?>
                            <tr>
                                <td><?= htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int)$customer['orders_count'] ?></td>
                                <td>R$ <?= number_format($customer['total_value'], 2, ',', '.') ?></td>
                                <td><?= htmlspecialchars($customer['last_order_date'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Segmentação de Clientes -->
        <div class="report-card">
            <h3>Segmentação de Clientes</h3>
            <div class="chart-container" id="customer-segments-chart">
                <?php if (empty($customerSegments)): ?>
                    <div class="no-data-message">Nenhum dado de segmentação disponível.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Segmento</th>
                                <th>Clientes</th>
                                <th>Receita</th>
                                <th>Ticket Médio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customerSegments as $segment): ?>
                            <tr>
                                <td><?= htmlspecialchars($segment['segment_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int)$segment['customers_count'] ?></td>
                                <td>R$ <?= number_format($segment['total_revenue'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($segment['average_ticket'], 2, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Retenção de Clientes -->
        <div class="report-card">
            <h3>Retenção de Clientes</h3>
            <div class="chart-container" id="customer-retention-chart">
                <?php if (empty($customerRetention)): ?>
                    <div class="no-data-message">Nenhum dado de retenção disponível.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Coorte</th>
                                <th>Clientes Iniciais</th>
                                <th>Ativos Atualmente</th>
                                <th>Taxa de Retenção</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customerRetention as $retention): ?>
                            <tr>
                                <td><?= htmlspecialchars($retention['cohort'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int)$retention['initial_count'] ?></td>
                                <td><?= (int)$retention['current_count'] ?></td>
                                <td><?= number_format($retention['retention_rate'], 1) ?>%</td>
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
        console.log('Carregando visualizações do relatório de clientes...');
        
        // Qualquer lógica de visualização será executada aqui
        // utilizando apenas dados já sanitizados pelo servidor
    });
</script>
