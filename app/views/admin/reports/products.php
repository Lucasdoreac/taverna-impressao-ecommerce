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
        <h1>Relatório de Produtos</h1>
        <div class="report-actions">
            <form method="GET" action="<?= htmlspecialchars($this->url('admin/reports/products'), ENT_QUOTES, 'UTF-8') ?>">
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
            <form method="POST" action="<?= htmlspecialchars($this->url('admin/reports/products/export'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="period" value="<?= htmlspecialchars($period, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" name="format" value="pdf" class="btn btn-secondary">Exportar PDF</button>
                <button type="submit" name="format" value="excel" class="btn btn-secondary">Exportar Excel</button>
            </form>
        </div>
    </div>
    
    <div class="report-content">
        <!-- Gráfico de Produtos Mais Vendidos -->
        <div class="report-card">
            <h3>Produtos Mais Vendidos</h3>
            <div class="chart-container" id="top-products-chart">
                <!-- Conteúdo dinâmico -->
                <?php if (empty($topProducts)): ?>
                    <div class="no-data-message">Nenhum dado disponível para o período selecionado.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Quantidade</th>
                                <th>Receita</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int)$product['quantity'] ?></td>
                                <td>R$ <?= number_format($product['revenue'], 2, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Análise de Categorias -->
        <div class="report-card">
            <h3>Desempenho por Categoria</h3>
            <div class="chart-container" id="categories-chart">
                <?php if (empty($productCategories)): ?>
                    <div class="no-data-message">Nenhum dado de categoria disponível.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th>Produtos</th>
                                <th>Vendas</th>
                                <th>Receita</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productCategories as $category): ?>
                            <tr>
                                <td><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int)$category['product_count'] ?></td>
                                <td><?= (int)$category['sales_count'] ?></td>
                                <td>R$ <?= number_format($category['revenue'], 2, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Status de Estoque -->
        <div class="report-card">
            <h3>Status de Estoque</h3>
            <div class="chart-container" id="stock-status-chart">
                <?php if (empty($stockStatus)): ?>
                    <div class="no-data-message">Nenhum dado de estoque disponível.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Quantidade</th>
                                <th>Percentual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total = array_sum(array_column($stockStatus, 'count'));
                            foreach ($stockStatus as $status): 
                                $percentage = $total > 0 ? ($status['count'] / $total) * 100 : 0;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($status['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int)$status['count'] ?></td>
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
        console.log('Carregando visualizações do relatório de produtos...');
        
        // Qualquer lógica de visualização será executada aqui
        // utilizando apenas dados já sanitizados pelo servidor
    });
</script>
