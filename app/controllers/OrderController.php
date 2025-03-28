<?php
/**
 * OrderController - Controlador para gerenciamento de pedidos
 */
class OrderController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
        
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
            $sql = "SELECT * FROM orders WHERE order_number = :order_number AND user_id = :user_id LIMIT 1";
            $order = Database::getInstance()->select($sql, [
                'order_number' => $orderNumber,
                'user_id' => $userId
            ]);
            
            if (empty($order)) {
                header('Location: ' . BASE_URL);
                exit;
            }
            
            $order = $order[0];
            
            // Obter itens do pedido
            $sql = "SELECT * FROM order_items WHERE order_id = :order_id";
            $items = Database::getInstance()->select($sql, ['order_id' => $order['id']]);
            
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
            $sql = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC";
            $orders = Database::getInstance()->select($sql, ['user_id' => $userId]);
            
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
            $sql = "SELECT * FROM orders WHERE order_number = :order_number AND user_id = :user_id LIMIT 1";
            $order = Database::getInstance()->select($sql, [
                'order_number' => $orderNumber,
                'user_id' => $userId
            ]);
            
            if (empty($order)) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                exit;
            }
            
            $order = $order[0];
            
            // Obter itens do pedido
            $sql = "SELECT * FROM order_items WHERE order_id = :order_id";
            $items = Database::getInstance()->select($sql, ['order_id' => $order['id']]);
            
            // Obter endereço de entrega
            $sql = "SELECT * FROM addresses WHERE id = :id";
            $address = Database::getInstance()->select($sql, ['id' => $order['shipping_address_id']]);
            $address = !empty($address) ? $address[0] : null;
            
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
            
            // Obter dados do pedido
            $sql = "SELECT * FROM orders WHERE order_number = :order_number AND user_id = :user_id LIMIT 1";
            $order = Database::getInstance()->select($sql, [
                'order_number' => $orderNumber,
                'user_id' => $userId
            ]);
            
            if (empty($order)) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                exit;
            }
            
            $order = $order[0];
            
            // Verificar se o pedido pode ser cancelado
            $cancelableStatuses = ['pending', 'processing']; // Apenas pedidos pendentes ou em processamento podem ser cancelados
            
            if (!in_array($order['status'], $cancelableStatuses)) {
                $_SESSION['error'] = 'Este pedido não pode ser cancelado devido ao seu status atual.';
                header('Location: ' . BASE_URL . 'minha-conta/pedido/' . $orderNumber);
                exit;
            }
            
            // Cancelar o pedido
            Database::getInstance()->update(
                'orders',
                [
                    'status' => 'canceled',
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'id = :id',
                ['id' => $order['id']]
            );
            
            // Retornar os itens ao estoque
            $sql = "SELECT * FROM order_items WHERE order_id = :order_id";
            $items = Database::getInstance()->select($sql, ['order_id' => $order['id']]);
            
            $productModel = new ProductModel();
            
            foreach ($items as $item) {
                $product = $productModel->find($item['product_id']);
                
                if ($product) {
                    $newStock = $product['stock'] + $item['quantity'];
                    $productModel->update($item['product_id'], ['stock' => $newStock]);
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