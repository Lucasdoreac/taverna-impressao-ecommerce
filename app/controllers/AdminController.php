<?php
/**
 * AdminController - Controlador base para o painel administrativo
 */
class AdminController {
    
    /**
     * Construtor - verifica autenticação e permissões de administrador
     */
    public function __construct() {
        // Verificar se o usuário está logado
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            $_SESSION['error'] = 'É necessário fazer login para acessar o painel administrativo.';
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        
        // Verificar se o usuário tem permissões de administrador
        if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
            $_SESSION['error'] = 'Você não tem permissão para acessar esta área.';
            header('Location: ' . BASE_URL);
            exit;
        }
    }
    
    /**
     * Exibe o dashboard principal do painel administrativo
     */
    public function index() {
        try {
            // Obter estatísticas básicas para o dashboard
            $db = Database::getInstance();
            
            // Total de pedidos
            $sql = "SELECT COUNT(*) as total FROM orders";
            $result = $db->select($sql);
            $totalOrders = $result[0]['total'];
            
            // Pedidos pendentes
            $sql = "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'";
            $result = $db->select($sql);
            $pendingOrders = $result[0]['total'];
            
            // Total de produtos
            $sql = "SELECT COUNT(*) as total FROM products";
            $result = $db->select($sql);
            $totalProducts = $result[0]['total'];
            
            // Total de usuários
            $sql = "SELECT COUNT(*) as total FROM users";
            $result = $db->select($sql);
            $totalUsers = $result[0]['total'];
            
            // Faturamento total
            $sql = "SELECT SUM(total) as total FROM orders WHERE status != 'canceled'";
            $result = $db->select($sql);
            $totalRevenue = $result[0]['total'] ?: 0;
            
            // Pedidos recentes
            $sql = "SELECT * FROM orders ORDER BY created_at DESC LIMIT 5";
            $recentOrders = $db->select($sql);
            
            // Renderizar a view do dashboard
            require_once VIEWS_PATH . '/admin/dashboard.php';
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao carregar dashboard do admin: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Exibir mensagem de erro
            $_SESSION['error'] = 'Ocorreu um erro ao carregar o dashboard. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
    }
    
    /**
     * Renderiza a view do layout administrativo
     * 
     * @param string $content Caminho para o arquivo de conteúdo
     * @param array $data Dados para passar para a view
     */
    protected function renderAdminView($content, $data = []) {
        // Extrair dados para uso nas views
        extract($data);
        
        // Incluir cabeçalho do admin
        require_once VIEWS_PATH . '/admin/partials/header.php';
        
        // Incluir o conteúdo específico
        require_once $content;
        
        // Incluir rodapé do admin
        require_once VIEWS_PATH . '/admin/partials/footer.php';
    }
}
