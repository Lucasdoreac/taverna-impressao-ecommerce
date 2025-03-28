<?php require_once VIEWS_PATH . '/admin/partials/header.php'; ?>

<!-- Page Title -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Relatórios e Estatísticas</h1>
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin">Dashboard</a></li>
        <li class="breadcrumb-item active">Relatórios</li>
    </ol>
</div>

<div class="row g-4">
    <!-- Relatório de Vendas -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Relatório de Vendas</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Gere relatórios de vendas por período. Visualize totais de vendas, número de pedidos e evolução de performance.</p>
                
                <form action="<?= BASE_URL ?>admin/relatorios/vendas" method="get" class="mb-3">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label for="sales_start_date" class="form-label">Data Inicial</label>
                            <input type="date" id="sales_start_date" name="start_date" class="form-control" value="<?= date('Y-m-01') ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label for="sales_end_date" class="form-label">Data Final</label>
                            <input type="date" id="sales_end_date" name="end_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label for="sales_group_by" class="form-label">Agrupar</label>
                            <select id="sales_group_by" name="group_by" class="form-select">
                                <option value="daily">Por Dia</option>
                                <option value="weekly">Por Semana</option>
                                <option value="monthly">Por Mês</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i> Visualizar
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-download me-1"></i> Exportar
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <button type="submit" class="dropdown-item" name="format" value="csv">
                                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV
                                    </button>
                                </li>
                                <li>
                                    <button type="submit" class="dropdown-item" name="format" value="pdf">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Relatório de Produtos -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Relatório de Produtos</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Visualize o desempenho dos produtos. Veja os mais vendidos, rentabilidade por produto e níveis de estoque.</p>
                
                <form action="<?= BASE_URL ?>admin/relatorios/produtos" method="get" class="mb-3">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label for="products_start_date" class="form-label">Data Inicial</label>
                            <input type="date" id="products_start_date" name="start_date" class="form-control" value="<?= date('Y-m-01') ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label for="products_end_date" class="form-label">Data Final</label>
                            <input type="date" id="products_end_date" name="end_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label for="products_limit" class="form-label">Limite</label>
                            <select id="products_limit" name="limit" class="form-select">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="0">Todos</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i> Visualizar
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-download me-1"></i> Exportar
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <button type="submit" class="dropdown-item" name="format" value="csv">
                                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV
                                    </button>
                                </li>
                                <li>
                                    <button type="submit" class="dropdown-item" name="format" value="pdf">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Relatório de Clientes -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Relatório de Clientes</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Analise o comportamento dos clientes. Visualize clientes mais ativos, valor médio de compra e frequência.</p>
                
                <form action="<?= BASE_URL ?>admin/relatorios/clientes" method="get" class="mb-3">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label for="customers_start_date" class="form-label">Data Inicial</label>
                            <input type="date" id="customers_start_date" name="start_date" class="form-control" value="<?= date('Y-m-01') ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label for="customers_end_date" class="form-label">Data Final</label>
                            <input type="date" id="customers_end_date" name="end_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label for="customers_limit" class="form-label">Limite</label>
                            <select id="customers_limit" name="limit" class="form-select">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="0">Todos</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i> Visualizar
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-download me-1"></i> Exportar
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <button type="submit" class="dropdown-item" name="format" value="csv">
                                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV
                                    </button>
                                </li>
                                <li>
                                    <button type="submit" class="dropdown-item" name="format" value="pdf">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Relatório de Categorias -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Relatório de Categorias</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Analise o desempenho por categoria de produto. Identifique as categorias mais lucrativas e em crescimento.</p>
                
                <form action="<?= BASE_URL ?>admin/relatorios/categorias" method="get" class="mb-3">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label for="categories_start_date" class="form-label">Data Inicial</label>
                            <input type="date" id="categories_start_date" name="start_date" class="form-control" value="<?= date('Y-m-01') ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label for="categories_end_date" class="form-label">Data Final</label>
                            <input type="date" id="categories_end_date" name="end_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label for="categories_parent_only" class="form-label">Filtro</label>
                            <select id="categories_parent_only" name="parent_only" class="form-select">
                                <option value="0">Todas</option>
                                <option value="1">Principais</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i> Visualizar
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-download me-1"></i> Exportar
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <button type="submit" class="dropdown-item" name="format" value="csv">
                                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV
                                    </button>
                                </li>
                                <li>
                                    <button type="submit" class="dropdown-item" name="format" value="pdf">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once VIEWS_PATH . '/admin/partials/footer.php'; ?>