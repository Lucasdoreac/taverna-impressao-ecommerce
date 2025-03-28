<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - <?= STORE_NAME ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>assets/css/admin.css" rel="stylesheet">
    <!-- Colocar os outros CSS específicos de páginas aqui -->
    <?php if (isset($page_css)): ?>
    <?php foreach ($page_css as $css): ?>
    <link href="<?= BASE_URL ?>assets/css/<?= $css ?>" rel="stylesheet">
    <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="bg-dark text-white">
            <div class="sidebar-header p-3 border-bottom border-secondary">
                <h3 class="m-0">TAVERNA da IMPRESSÃO</h3>
                <p class="text-muted small mb-0">Painel Administrativo</p>
            </div>

            <ul class="list-unstyled components p-3">
                <li class="<?= AdminHelper::getActiveMenu() === 'dashboard' ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>admin" class="d-flex align-items-center text-decoration-none mb-3">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="<?= AdminHelper::getActiveMenu() === 'produtos' ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>admin/produtos" class="d-flex align-items-center text-decoration-none mb-3">
                        <i class="bi bi-box-seam me-2"></i> Produtos
                    </a>
                </li>
                <li class="<?= AdminHelper::getActiveMenu() === 'categorias' ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>admin/categorias" class="d-flex align-items-center text-decoration-none mb-3">
                        <i class="bi bi-tags me-2"></i> Categorias
                    </a>
                </li>
                <li class="<?= AdminHelper::getActiveMenu() === 'pedidos' ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>admin/pedidos" class="d-flex align-items-center text-decoration-none mb-3">
                        <i class="bi bi-cart-check me-2"></i> Pedidos
                    </a>
                </li>
                <li class="<?= AdminHelper::getActiveMenu() === 'usuarios' ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>admin/usuarios" class="d-flex align-items-center text-decoration-none mb-3">
                        <i class="bi bi-people me-2"></i> Usuários
                    </a>
                </li>
                <li class="<?= AdminHelper::getActiveMenu() === 'configuracoes' ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>admin/configuracoes" class="d-flex align-items-center text-decoration-none mb-3">
                        <i class="bi bi-gear me-2"></i> Configurações
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>" target="_blank" class="d-flex align-items-center text-decoration-none mb-3">
                        <i class="bi bi-house me-2"></i> Ver Loja
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>logout" class="d-flex align-items-center text-decoration-none text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> Sair
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-dark">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <div class="ms-auto d-flex align-items-center">
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i> <?= $_SESSION['user']['name'] ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>minha-conta"><i class="bi bi-person me-2"></i> Meu Perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout"><i class="bi bi-box-arrow-right me-2"></i> Sair</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Content Area -->
            <div class="container-fluid p-4">
                <!-- Mensagens de alerta -->
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
