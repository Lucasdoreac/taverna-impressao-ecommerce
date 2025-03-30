<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title . ' - ' : '' ?>Painel Administrativo | <?= STORE_NAME ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="d-flex align-items-center justify-content-center mb-4 px-3">
                        <a href="<?= BASE_URL ?>admin" class="text-white text-decoration-none">
                            <h1 class="h5 fw-bold m-0">TAVERNA DA IMPRESSÃO</h1>
                            <p class="small text-muted text-center">Painel Administrativo</p>
                        </a>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin') !== false && strpos($_SERVER['REQUEST_URI'], '/admin/') === false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin">
                                <i class="bi bi-speedometer2 me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/pedidos') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/pedidos">
                                <i class="bi bi-cart-check me-2"></i>
                                Pedidos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/produtos') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/produtos">
                                <i class="bi bi-box-seam me-2"></i>
                                Produtos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/categorias') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/categorias">
                                <i class="bi bi-tags me-2"></i>
                                Categorias
                            </a>
                        </li>
                        
                        <!-- Início - Menu de Impressão 3D -->
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/print_queue') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/print_queue">
                                <i class="bi bi-printer-fill me-2"></i>
                                Fila de Impressão 3D
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/print_queue/dashboard') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/print_queue/dashboard">
                                <i class="bi bi-grid-1x2-fill me-2"></i>
                                Dashboard 3D
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/print_queue/printers') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/print_queue/printers">
                                <i class="bi bi-tools me-2"></i>
                                Impressoras
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/print_queue/relatorio') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/print_queue/relatorio">
                                <i class="bi bi-file-earmark-bar-graph me-2"></i>
                                Relatório de Produção
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/print_queue/notificacoes') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/print_queue/notificacoes">
                                <i class="bi bi-bell-fill me-2"></i>
                                Notificações
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/customer-models/pending') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/customer-models/pending">
                                <i class="bi bi-file-earmark-check me-2"></i>
                                Validar Modelos
                            </a>
                        </li>
                        <!-- Fim - Menu de Impressão 3D -->
                        
                        <!-- Início - Menu de Performance e Otimização -->
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/performance') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/performance">
                                <i class="bi bi-speedometer me-2"></i>
                                Performance SQL
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/performance/recentOptimizations') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/performance/recentOptimizations">
                                <i class="bi bi-lightning-charge-fill me-2"></i>
                                <span>Otimizações Recentes</span>
                                <span class="badge bg-success ms-1">Novo</span>
                            </a>
                        </li>
                        <!-- Fim - Menu de Performance e Otimização -->
                        
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/usuarios') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/usuarios">
                                <i class="bi bi-people me-2"></i>
                                Usuários
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/relatorios') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/relatorios">
                                <i class="bi bi-graph-up me-2"></i>
                                Relatórios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>" target="_blank">
                                <i class="bi bi-shop me-2"></i>
                                Ver Loja
                            </a>
                        </li>
                    </ul>
                    
                    <hr class="text-light">
                    
                    <div class="px-3">
                        <span class="text-white-50 small d-block mb-1">Logado como:</span>
                        <div class="d-flex align-items-center text-white">
                            <i class="bi bi-person-circle fs-5 me-2"></i>
                            <div>
                                <span class="fw-bold"><?= $_SESSION['user']['name'] ?></span>
                                <small class="d-block text-muted"><?= $_SESSION['user']['email'] ?></small>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="<?= BASE_URL ?>logout" class="btn btn-sm btn-outline-light w-100">
                                <i class="bi bi-box-arrow-right me-1"></i> Sair
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Mobile header -->
                <div class="d-flex justify-content-between d-md-none p-2 mb-3 bg-dark text-white align-items-center sticky-top border-bottom">
                    <a href="<?= BASE_URL ?>admin" class="text-white text-decoration-none">
                        <h5 class="m-0">TAVERNA</h5>
                    </a>
                    <button class="btn btn-sm btn-outline-light" data-bs-toggle="collapse" data-bs-target="#sidebar">
                        <i class="bi bi-list"></i>
                    </button>
                </div>
                
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <!-- Page content starts here -->
