<?php
/**
 * OrderController - Controlador para gerenciamento de pedidos
 */
class OrderController {
    private $userModel;
    private $orderModel;
    private $productModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->orderModel = new OrderModel();
        $this->productModel = new ProductModel();
        
        // Verificar se o usuário está logado para todas as ações exceto success
        if ($this->getCurrentAction() !== 'success') {
            $this->checkAuthentication();
        }
    }
    
    /**
     * Exibe a página de sucesso após finalização do pedido
     * 
     * @param array $params Parâmetros da rota (id do pedido)
     */
    public function success($params) {
        try {
            $orderNumber = $params['id'] ?? '';
            
            if (empty($orderNumber)) {
                header('Location: ' . BASE_URL);
                exit;
            }
            
            // Verificar se o usuário está logado
            if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
                header('Location: ' . BASE_URL);
                exit;
            }
            
            // Obter dados do pedido
            $userId = $_SESSION['user']['id'];
            $order = $this->orderModel->findByOrderNumber($orderNumber);
            
            if (empty($order) || $order['user_id'] != $userId) {
                header('Location: ' . BASE_URL);
                exit;
            }
            
            // Obter itens do pedido
            $items = $this->orderModel->getOrderItems($order['id']);
            
            // Verificar se há itens sob encomenda no pedido
            $has_custom_order = false;
            foreach ($items as $item) {
                if (!$item['is_stock_item']) {
                    $has_custom_order = true;
                    break;
                }
            }
            
            // Calcular data estimada de entrega para produtos sob encomenda
            $estimated_delivery_date = null;
            if ($has_custom_order && $order['estimated_print_time_hours'] > 0) {
                $estimated_delivery_date = $this->orderModel->calculateEstimatedCompletionDate($order['id']);
                if ($estimated_delivery_date) {
                    $date = new DateTime($estimated_delivery_date);
                    $estimated_delivery_date = $date->format('d/m/Y');
                }
            }
            
            // Obter endereço de entrega
            $sql = "SELECT * FROM addresses WHERE id = :id";
            $address = Database::getInstance()->select($sql, ['id' => $order['shipping_address_id']]);
            $address = !empty($address) ? $address[0] : null;
            
            // Renderizar view
            require_once VIEWS_PATH . '/order_success.php';
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao exibir página de sucesso do pedido: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Redirecionar para a página inicial
            $_SESSION['error'] = 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.';
            header('Location: ' . BASE_URL);
            exit;
        }
    }
    
    /**
     * Lista todos os pedidos do usuário logado
     */
    public function index() {
        try {
            $userId = $_SESSION['user']['id'];
            
            // Obter todos os pedidos do usuário
            $orders = $this->orderModel->getOrdersByUser($userId);
            
            // Status traduzidos para exibição
            $statusLabels = [
                'pending' => 'Aguardando Envio',
                'validating' => 'Validando Modelo 3D',
                'printing' => 'Em Impressão',
                'finishing' => 'Em Acabamento',
                'shipped' => 'Enviado',
                'delivered' => 'Entregue',
                'canceled' => 'Cancelado'
            ];
            
            // Renderizar view
            require_once VIEWS_PATH . '/orders.php';
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao listar pedidos: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Redirecionar para a página inicial
            $_SESSION['error'] = 'Ocorreu um erro ao carregar seus pedidos. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'minha-conta');
            exit;
        }
    }
    
    /**
     * Exibe detalhes de um pedido específico
     * 
     * @param array $params Parâmetros da rota (id do pedido)
     */
    public function view($params) {
        try {
            $orderNumber = $params['id'] ?? '';
            
            if (empty($orderNumber)) {
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                exit;
            }
            
            $userId = $_SESSION['user']['id'];
            
            // Obter dados do pedido
            $order = $this->orderModel->findByOrderNumber($orderNumber);
            
            if (empty($order) || $order['user_id'] != $userId) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                exit;
            }
            
            // Obter itens do pedido
            $items = $this->orderModel->getOrderItems($order['id']);
            
            // Verificar se há itens sob encomenda no pedido
            $has_custom_order = false;
            foreach ($items as $item) {
                if (!$item['is_stock_item']) {
                    $has_custom_order = true;
                    break;
                }
            }
            
            // Status traduzidos para exibição
            $statusLabels = [
                'pending' => 'Aguardando Envio',
                'validating' => 'Validando Modelo 3D',
                'printing' => 'Em Impressão',
                'finishing' => 'Em Acabamento',
                'shipped' => 'Enviado',
                'delivered' => 'Entregue',
                'canceled' => 'Cancelado'
            ];
            
            // Calcular progresso da impressão se o pedido estiver sendo impresso
            $printing_progress = 0;
            $remaining_time = null;
            if ($order['status'] === 'printing' && $order['print_start_date']) {
                $start_time = new DateTime($order['print_start_date']);
                $now = new DateTime();
                $elapsed_seconds = $now->getTimestamp() - $start_time->getTimestamp();
                $total_seconds = $order['estimated_print_time_hours'] * 3600;
                
                if ($total_seconds > 0) {
                    $printing_progress = min(100, round(($elapsed_seconds / $total_seconds) * 100));
                    $remaining_seconds = max(0, $total_seconds - $elapsed_seconds);
                    $remaining_hours = floor($remaining_seconds / 3600);
                    $remaining_minutes = floor(($remaining_seconds % 3600) / 60);
                    $remaining_time = sprintf("%02d:%02d", $remaining_hours, $remaining_minutes);
                }
            }
            
            // Calcular data estimada de entrega para produtos sob encomenda
            $estimated_delivery_date = null;
            if ($has_custom_order && $order['estimated_print_time_hours'] > 0) {
                $estimated_delivery_date = $this->orderModel->calculateEstimatedCompletionDate($order['id']);
                if ($estimated_delivery_date) {
                    $date = new DateTime($estimated_delivery_date);
                    $estimated_delivery_date = $date->format('d/m/Y');
                }
            }
            
            // Obter endereço de entrega
            $sql = "SELECT * FROM addresses WHERE id = :id";
            $address = Database::getInstance()->select($sql, ['id' => $order['shipping_address_id']]);
            $address = !empty($address) ? $address[0] : null;
            
            // Obter histórico do pedido
            $notes = $this->orderModel->getNotes($order['id']);
            
            // Renderizar view
            require_once VIEWS_PATH . '/order_details.php';
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao exibir detalhes do pedido: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Redirecionar para a lista de pedidos
            $_SESSION['error'] = 'Ocorreu um erro ao carregar os detalhes do pedido. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'minha-conta/pedidos');
            exit;
        }
    }
    
    /**
     * Cancela um pedido (se permitido pelo status atual)
     * 
     * @param array $params Parâmetros da rota (id do pedido)
     */
    public function cancel($params) {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                exit;
            }
            
            $orderNumber = $params['id'] ?? '';
            
            if (empty($orderNumber)) {
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                exit;
            }
            
            $userId = $_SESSION['user']['id'];
            $reason = $_POST['reason'] ?? 'Cancelado pelo cliente';
            
            // Obter dados do pedido
            $order = $this->orderModel->findByOrderNumber($orderNumber);
            
            if (empty($order) || $order['user_id'] != $userId) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                exit;
            }
            
            // Verificar se o pedido pode ser cancelado
            $cancelableStatuses = ['pending', 'validating']; // Apenas pedidos pendentes ou em validação podem ser cancelados
            
            if (!in_array($order['status'], $cancelableStatuses)) {
                $_SESSION['error'] = 'Este pedido não pode ser cancelado devido ao seu status atual.';
                header('Location: ' . BASE_URL . 'minha-conta/pedido/' . $orderNumber);
                exit;
            }
            
            // Obter itens do pedido para retornar ao estoque
            $items = $this->orderModel->getOrderItems($order['id']);
            
            // Cancelar o pedido
            $this->orderModel->cancelOrder($order['id'], $reason);
            
            // Retornar os itens ao estoque (apenas itens de pronta entrega)
            foreach ($items as $item) {
                if ($item['is_stock_item']) {
                    $product = $this->productModel->find($item['product_id']);
                    
                    if ($product) {
                        $newStock = $product['stock'] + $item['quantity'];
                        $this->productModel->update($item['product_id'], ['stock' => $newStock]);
                    }
                }
            }
            
            $_SESSION['success'] = 'Pedido cancelado com sucesso.';
            header('Location: ' . BASE_URL . 'minha-conta/pedido/' . $orderNumber);
            exit;
        } catch (Exception $e) {
            // Registrar erro no log
            error_log("Erro ao cancelar pedido: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Redirecionar para a página do pedido
            $_SESSION['error'] = 'Ocorreu um erro ao cancelar o pedido. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'minha-conta/pedido/' . $orderNumber);
            exit;
        }
    }
    
    /**
     * Obtém a ação atual da requisição
     */
    private function getCurrentAction() {
        $uri = $_SERVER['REQUEST_URI'];
        $parts = explode('/', trim($uri, '/'));
        
        // Verificar se é a ação 'success'
        if (count($parts) >= 2 && $parts[0] === 'pedido' && $parts[1] === 'sucesso') {
            return 'success';
        }
        
        return '';
    }
    
    /**
     * Verifica se o usuário está autenticado
     */
    private function checkAuthentication() {
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            $_SESSION['error'] = 'É necessário fazer login para acessar seus pedidos.';
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
    }
}