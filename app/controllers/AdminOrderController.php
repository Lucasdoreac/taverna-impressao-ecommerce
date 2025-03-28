<?php
/**
 * AdminOrderController - Controlador para gerenciamento de pedidos no painel administrativo
 */
class AdminOrderController {
    private $orderModel;
    private $userModel;
    
    /**
     * Construtor - verifica se o usuário é administrador e inicializa modelos
     */
    public function __construct() {
        // Verificar se o usuário está logado e é administrador
        AdminHelper::checkAdminAccess();
        
        // Inicializar modelos
        $this->orderModel = new OrderModel();
        $this->userModel = new UserModel();
    }
    
    /**
     * Exibe a lista de pedidos
     */
    public function index() {
        // Parâmetros de filtro e paginação
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        
        // Filtros
        $filters = [
            'order_number' => $_GET['order_number'] ?? '',
            'status' => $_GET['status'] ?? '',
            'payment_status' => $_GET['payment_status'] ?? '',
            'payment_method' => $_GET['payment_method'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'customer' => $_GET['customer'] ?? ''
        ];
        
        // Buscar pedidos com paginação e filtros
        $orders = $this->orderModel->getWithFilters($filters, $page, $limit);
        
        // Renderizar view
        require_once VIEWS_PATH . '/admin/orders/index.php';
    }
    
    /**
     * Exibe detalhes de um pedido
     */
    public function view($params) {
        // Obter ID do pedido
        $id = $params['id'] ?? 0;
        
        // Buscar pedido
        $order = $this->orderModel->find($id);
        
        if (!$order) {
            $_SESSION['error'] = 'Pedido não encontrado.';
            header('Location: ' . BASE_URL . 'admin/pedidos');
            exit;
        }
        
        // Buscar itens do pedido
        $items = $this->orderModel->getItems($id);
        
        // Buscar informações do cliente
        $customer = null;
        if ($order['user_id']) {
            $customer = $this->userModel->find($order['user_id']);
        }
        
        // Buscar endereço de entrega
        $shippingAddress = null;
        if ($order['shipping_address_id']) {
            $shippingAddress = $this->orderModel->getShippingAddress($order['shipping_address_id']);
        }
        
        // Renderizar view
        require_once VIEWS_PATH . '/admin/orders/view.php';
    }
    
    /**
     * Atualiza o status de um pedido
     */
    public function updateStatus() {
        // Verificar se o formulário foi submetido
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'admin/pedidos');
            exit;
        }
        
        // Obter dados do formulário
        $orderId = $_POST['order_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        // Validação básica
        if (empty($orderId) || empty($status)) {
            $_SESSION['error'] = 'Dados inválidos.';
            header('Location: ' . BASE_URL . 'admin/pedidos');
            exit;
        }
        
        // Buscar pedido
        $order = $this->orderModel->find($orderId);
        
        if (!$order) {
            $_SESSION['error'] = 'Pedido não encontrado.';
            header('Location: ' . BASE_URL . 'admin/pedidos');
            exit;
        }
        
        // Atualizar status
        $this->orderModel->updateStatus($orderId, $status);
        
        // Adicionar notas se houver
        if (!empty($notes)) {
            $this->orderModel->addNote($orderId, $notes);
        }
        
        // Enviar notificação ao cliente (implementação futura)
        
        // Redirecionar com mensagem de sucesso
        $_SESSION['success'] = 'Status do pedido atualizado com sucesso!';
        header('Location: ' . BASE_URL . 'admin/pedidos/view/' . $orderId);
        exit;
    }
    
    /**
     * Atualiza o status de pagamento de um pedido
     */
    public function updatePaymentStatus() {
        // Verificar se o formulário foi submetido
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'admin/pedidos');
            exit;
        }
        
        // Obter dados do formulário
        $orderId = $_POST['order_id'] ?? 0;
        $paymentStatus = $_POST['payment_status'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        // Validação básica
        if (empty($orderId) || empty($paymentStatus)) {
            $_SESSION['error'] = 'Dados inválidos.';
            header('Location: ' . BASE_URL . 'admin/pedidos');
            exit;
        }
        
        // Buscar pedido
        $order = $this->orderModel->find($orderId);
        
        if (!$order) {
            $_SESSION['error'] = 'Pedido não encontrado.';
            header('Location: ' . BASE_URL . 'admin/pedidos');
            exit;
        }
        
        // Atualizar status de pagamento
        $this->orderModel->updatePaymentStatus($orderId, $paymentStatus);
        
        // Adicionar notas se houver
        if (!empty($notes)) {
            $this->orderModel->addNote($orderId, $notes);
        }
        
        // Redirecionar com mensagem de sucesso
        $_SESSION['success'] = 'Status de pagamento atualizado com sucesso!';
        header('Location: ' . BASE_URL . 'admin/pedidos/view/' . $orderId);
        exit;
    }
    
    /**
     * Adiciona código de rastreamento a um pedido
     */
    public function addTrackingCode() {
        // Verificar se o formulário foi submetido
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'admin/pedidos');
            exit;
        }
        
        // Obter dados do formulário
        $orderId = $_POST['order_id'] ?? 0;
        $trackingCode = $_POST['tracking_code'] ?? '';
        
        // Validação básica
        if (empty($orderId) || empty($trackingCode)) {
            $_SESSION['error'] = 'Dados inválidos.';
            header('Location: ' . BASE_URL . 'admin/pedidos');
            exit;
        }
        
        // Buscar pedido
        $order = $this->orderModel->find($orderId);
        
        if (!$order) {
            $_SESSION['error'] = 'Pedido não encontrado.';
            header('Location: ' . BASE_URL . 'admin/pedidos');
            exit;
        }
        
        // Adicionar código de rastreamento e atualizar status para 'shipped'
        $this->orderModel->addTrackingCode($orderId, $trackingCode);
        
        // Notificar cliente (implementação futura)
        
        // Redirecionar com mensagem de sucesso
        $_SESSION['success'] = 'Código de rastreamento adicionado e pedido marcado como enviado!';
        header('Location: ' . BASE_URL . 'admin/pedidos/view/' . $orderId);
        exit;
    }
    
    /**
     * Cancela um pedido
     */
    public function cancel() {
        // Verificar se o formulário foi submetido
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'admin/pedidos');
            exit;
        }
        
        // Obter dados do formulário
        $orderId = $_POST['order_id'] ?? 0;
        $reason = $_POST['reason'] ?? '';
        
        // Validação básica
        if (empty($orderId)) {
            $_SESSION['error'] = 'Dados inválidos.';
            header('Location: ' . BASE_URL . 'admin/pedidos');
            exit;
        }
        
        // Buscar pedido
        $order = $this->orderModel->find($orderId);
        
        if (!$order) {
            $_SESSION['error'] = 'Pedido não encontrado.';
            header('Location: ' . BASE_URL . 'admin/pedidos');
            exit;
        }
        
        // Cancelar pedido
        $this->orderModel->updateStatus($orderId, 'canceled');
        
        // Adicionar motivo do cancelamento como nota
        if (!empty($reason)) {
            $this->orderModel->addNote($orderId, 'Motivo do cancelamento: ' . $reason);
        }
        
        // Notificar cliente (implementação futura)
        
        // Redirecionar com mensagem de sucesso
        $_SESSION['success'] = 'Pedido cancelado com sucesso!';
        header('Location: ' . BASE_URL . 'admin/pedidos/view/' . $orderId);
        exit;
    }
}
