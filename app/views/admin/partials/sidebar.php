<?php
// ⚠️ SEGURANÇA: Verificação de autenticação
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header('Location: ' . BASE_URL . 'admin/login');
    exit;
}

// ⚠️ SEGURANÇA: Verificar permissões
$userPermissions = isset($_SESSION['user']['permissions']) ? $_SESSION['user']['permissions'] : [];
?>

<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="d-flex align-items-center justify-content-center mb-4 px-3">
            <a href="<?= BASE_URL ?>admin" class="text-white text-decoration-none">
                <h1 class="h5 fw-bold m-0">TAVERNA DA IMPRESSÃO</h1>
                <p class="small text-muted text-center">Painel Administrativo</p>
            </a>
        </div>
        
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin') !== false && strpos($_SERVER['REQUEST_URI'], '/admin/') === false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>
            
            <!-- Gerenciamento de Pedidos -->
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/pedidos') !== false ? 'active' : '' ?>" 
                   href="<?= BASE_URL ?>admin/pedidos">
                    <i class="bi bi-cart-check me-2"></i>
                    Pedidos
                </a>
            </li>
            
            <!-- Gerenciamento de Produtos -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle <?= strpos($_SERVER['REQUEST_URI'], '/admin/produtos') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/categorias') !== false ? 'active' : '' ?>" 
                   href="#" 
                   id="produtosDropdown" 
                   role="button" 
                   data-bs-toggle="dropdown" 
                   aria-expanded="false">
                    <i class="bi bi-box-seam me-2"></i>
                    Produtos
                </a>
                <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="produtosDropdown">
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/produtos') !== false && strpos($_SERVER['REQUEST_URI'], '/admin/produtos/create') === false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/produtos">
                            <i class="bi bi-list-ul me-2"></i>
                            Listar Produtos
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/produtos/create') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/produtos/create">
                            <i class="bi bi-plus-circle me-2"></i>
                            Novo Produto
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/categorias') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/categorias">
                            <i class="bi bi-tags me-2"></i>
                            Categorias
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- Impressão 3D -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle <?= strpos($_SERVER['REQUEST_URI'], '/admin/print') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/customer-models') !== false ? 'active' : '' ?>" 
                   href="#" 
                   id="impressaoDropdown" 
                   role="button" 
                   data-bs-toggle="dropdown" 
                   aria-expanded="false">
                    <i class="bi bi-printer-fill me-2"></i>
                    Impressão 3D
                </a>
                <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="impressaoDropdown">
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/print_queue') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/print_queue">
                            <i class="bi bi-list-check me-2"></i>
                            Fila de Impressão
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/print_queue/dashboard') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/print_queue/dashboard">
                            <i class="bi bi-grid-1x2-fill me-2"></i>
                            Dashboard 3D
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/print_queue/printers') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/print_queue/printers">
                            <i class="bi bi-tools me-2"></i>
                            Impressoras
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/print_queue/relatorio') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/print_queue/relatorio">
                            <i class="bi bi-file-earmark-bar-graph me-2"></i>
                            Relatório de Produção
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/customer-models/pending') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/customer-models/pending">
                            <i class="bi bi-file-earmark-check me-2"></i>
                            Validar Modelos
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- Gerenciamento de Usuários -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle <?= strpos($_SERVER['REQUEST_URI'], '/admin/usuarios') !== false ? 'active' : '' ?>" 
                   href="#" 
                   id="usuariosDropdown" 
                   role="button" 
                   data-bs-toggle="dropdown" 
                   aria-expanded="false">
                    <i class="bi bi-people me-2"></i>
                    Usuários
                </a>
                <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="usuariosDropdown">
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/usuarios') !== false && strpos($_SERVER['REQUEST_URI'], '/admin/usuarios/create') === false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/usuarios">
                            <i class="bi bi-list-ul me-2"></i>
                            Listar Usuários
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/usuarios/create') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/usuarios/create">
                            <i class="bi bi-plus-circle me-2"></i>
                            Novo Usuário
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/login_logs') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/login_logs">
                            <i class="bi bi-clock-history me-2"></i>
                            Log de Logins
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- Notificações e Monitoramento -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle <?= strpos($_SERVER['REQUEST_URI'], '/admin/dashboard/notificacoes') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/dashboard/monitoring') !== false ? 'active' : '' ?>" 
                   href="#" 
                   id="monitoramentoDropdown" 
                   role="button" 
                   data-bs-toggle="dropdown" 
                   aria-expanded="false">
                    <i class="bi bi-bell me-2"></i>
                    Monitoramento
                    <?php if (isset($alertsCount) && $alertsCount > 0): ?>
                    <span class="badge bg-danger ms-1"><?= htmlspecialchars($alertsCount, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="monitoramentoDropdown">
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/dashboard/notifications') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/dashboard/notifications">
                            <i class="bi bi-bell-fill me-2"></i>
                            Notificações
                            <?php if (isset($alertsCount) && $alertsCount > 0): ?>
                            <span class="badge bg-danger ms-1"><?= htmlspecialchars($alertsCount, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/dashboard/monitoring') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/dashboard/monitoring">
                            <i class="bi bi-graph-up me-2"></i>
                            Monitoramento do Sistema
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/performance') !== false && strpos($_SERVER['REQUEST_URI'], '/admin/performance_monitoring_dashboard') === false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/performance">
                            <i class="bi bi-speedometer me-2"></i>
                            Performance SQL
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/performance_monitoring_dashboard') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/performance_monitoring_dashboard">
                            <i class="bi bi-display me-2"></i>
                            Dashboard de Performance
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- Relatórios -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle <?= strpos($_SERVER['REQUEST_URI'], '/admin/reports') !== false ? 'active' : '' ?>" 
                   href="#" 
                   id="relatoriosDropdown" 
                   role="button" 
                   data-bs-toggle="dropdown" 
                   aria-expanded="false">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i>
                    Relatórios
                </a>
                <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="relatoriosDropdown">
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/reports') !== false && strpos($_SERVER['REQUEST_URI'], '/admin/reports/') === false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/reports">
                            <i class="bi bi-grid-1x2-fill me-2"></i>
                            Visão Geral
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/reports/sales') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/reports/sales">
                            <i class="bi bi-graph-up me-2"></i>
                            Vendas
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/reports/products') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/reports/products">
                            <i class="bi bi-box-seam me-2"></i>
                            Produtos
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/reports/customers') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/reports/customers">
                            <i class="bi bi-people me-2"></i>
                            Clientes
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/reports/trends') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/reports/trends">
                            <i class="bi bi-arrow-up-right me-2"></i>
                            Tendências
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/admin/reports/printing') !== false ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>admin/reports/printing">
                            <i class="bi bi-printer me-2"></i>
                            Impressão 3D
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- Ver Loja -->
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>" target="_blank">
                    <i class="bi bi-shop me-2"></i>
                    Ver Loja
                </a>
            </li>
        </ul>
        
        <hr class="text-light my-3">
        
        <!-- Informações do Usuário Logado -->
        <div class="px-3">
            <span class="text-white-50 small d-block mb-1">Logado como:</span>
            <div class="d-flex align-items-center text-white">
                <i class="bi bi-person-circle fs-5 me-2"></i>
                <div>
                    <span class="fw-bold"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></span>
                    <small class="d-block text-muted"><?= htmlspecialchars($_SESSION['user']['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                </div>
            </div>
            <div class="mt-2">
                <!-- ⚠️ SEGURANÇA: Formulário POST com token CSRF para logout -->
                <form method="POST" action="<?= BASE_URL ?>admin/logout">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-sm btn-outline-light w-100">
                        <i class="bi bi-box-arrow-right me-1"></i> Sair
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

<style>
/* Estilos para o menu lateral */
#sidebar {
    min-height: 100vh;
    transition: all 0.3s;
    z-index: 1000;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
}

#sidebar .nav-link {
    color: rgba(255, 255, 255, 0.8);
    padding: 0.5rem 1rem;
    margin: 0.2rem 0;
    border-radius: 0.25rem;
    transition: all 0.2s;
}

#sidebar .nav-link:hover {
    color: #fff;
    background-color: rgba(255, 255, 255, 0.1);
}

#sidebar .nav-link.active {
    color: #fff;
    background-color: #0d6efd;
}

#sidebar .dropdown-menu {
    padding: 0.5rem;
    margin-top: 0;
    inset: 0px auto auto 0px !important;
    transform: translate(100%, 0) !important;
    border-radius: 0 0.25rem 0.25rem 0;
    border-left: none;
}

#sidebar .dropdown-item {
    padding: 0.5rem 1rem;
    margin: 0.2rem 0;
    border-radius: 0.25rem;
}

#sidebar .dropdown-item:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

#sidebar .dropdown-item.active {
    background-color: #0d6efd;
}

/* Indicadores para tendências */
.trend-indicator.up {
    color: #28a745;
}

.trend-indicator.down {
    color: #dc3545;
}

.trend-indicator.stable {
    color: #ffc107;
}

.positive {
    color: #28a745;
}

.negative {
    color: #dc3545;
}

@media (max-width: 767.98px) {
    #sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        margin-left: -100%;
    }
    
    #sidebar.show {
        margin-left: 0;
    }
    
    #sidebar .dropdown-menu {
        position: static !important;
        transform: none !important;
        border-radius: 0.25rem;
        margin: 0.5rem 0 0.5rem 1rem;
        padding: 0.5rem;
        background-color: rgba(0, 0, 0, 0.2);
        border: none;
    }
}
</style>

<!-- ⚠️ SEGURANÇA: Script com nonce para proteção CSP -->
<script nonce="<?= htmlspecialchars($_SESSION['csp_nonce'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
// Ativar dropdowns do Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar todos os dropdowns
    var dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(function(dropdown) {
        dropdown.addEventListener('click', function(e) {
            e.preventDefault();
            var dropdownMenu = this.nextElementSibling;
            
            // Fechar outros dropdowns
            var openDropdowns = document.querySelectorAll('.dropdown-menu.show');
            openDropdowns.forEach(function(menu) {
                if (menu !== dropdownMenu) {
                    menu.classList.remove('show');
                }
            });
            
            // Alternar o dropdown atual
            dropdownMenu.classList.toggle('show');
        });
    });
    
    // Fechar dropdown ao clicar fora
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-item.dropdown')) {
            var openDropdowns = document.querySelectorAll('.dropdown-menu.show');
            openDropdowns.forEach(function(menu) {
                menu.classList.remove('show');
            });
        }
    });
});
</script>
