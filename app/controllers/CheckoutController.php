<?php
/**
 * CheckoutController - Controlador para processo de checkout e pagamento
 */
class CheckoutController {
    private $userModel;
    private $productModel;
    
    public function __construct() {
        $this->userModel = new UserModel();
        $this->productModel = new ProductModel();
        
        // Verificar se o usuário está logado
        $this->checkAuthentication();
        
        // Verificar se o carrinho tem itens
        $this->checkCart();
    }
    
    /**
     * Exibe a página de checkout
     */
    public function index() {
        // Obter dados do usuário
        $user_id = $_SESSION['user_id'];
        $user = $this->userModel->find($user_id);
        
        // Obter endereços do usuário
        $addresses = $this->userModel->getAddresses($user_id);
        
        // Obter itens do carrinho
        $cart_items = [];
        $subtotal = 0;
        
        // Processar itens do carrinho
        if (!empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $product = $this->productModel->find($item['product_id']);
                
                if ($product) {
                    // Calcular preço (considerando promoções)
                    $price = $product['sale_price'] && $product['sale_price'] < $product['price'] 
                           ? $product['sale_price'] 
                           : $product['price'];
                    
                    // Obter imagem principal
                    $sql = "SELECT image FROM product_images WHERE product_id = :id AND is_main = 1 LIMIT 1";
                    $imageResult = Database::getInstance()->select($sql, ['id' => $product['id']]);
                    $image = !empty($imageResult) ? $imageResult[0]['image'] : null;
                    
                    // Calcular total do item
                    $itemTotal = $price * $item['quantity'];
                    $subtotal += $itemTotal;
                    
                    // Adicionar ao array de itens formatados
                    $cart_items[] = [
                        'cart_item_id' => $item['cart_item_id'],
                        'product_id' => $product['id'],
                        'name' => $product['name'],
                        'slug' => $product['slug'],
                        'price' => $price,
                        'quantity' => $item['quantity'],
                        'image' => $image,
                        'customization' => $item['customization'] ?? null,
                        'total' => $itemTotal
                    ];
                }
            }
        }
        
        // Obter métodos de envio
        $shipping_methods = json_decode(Database::getInstance()->select("SELECT setting_value FROM settings WHERE setting_key = 'shipping_methods'")[0]['setting_value'], true) ?? [];
        
        // Obter métodos de pagamento
        $payment_methods = json_decode(Database::getInstance()->select("SELECT setting_value FROM settings WHERE setting_key = 'payment_methods'")[0]['setting_value'], true) ?? [];
        
        // Obter informações do frete (se disponível na sessão)
        $shipping_method = $_SESSION['shipping_method'] ?? null;
        $shipping_cost = $_SESSION['shipping_cost'] ?? 0;
        
        // Calcular total
        $total = $subtotal + $shipping_cost;
        
        // Renderizar a view
        require_once VIEWS_PATH . '/checkout.php';
    }
    
    /**
     * Processa a finalização do pedido
     */
    public function finish() {
        // Verificar método da requisição
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'checkout');
            exit;
        }
        
        // Obter dados do formulário
        $shipping_address_id = isset($_POST['shipping_address_id']) ? intval($_POST['shipping_address_id']) : 0;
        $shipping_method = isset($_POST['shipping_method']) ? $_POST['shipping_method'] : '';
        $shipping_cost = isset($_POST['shipping_cost']) ? floatval($_POST['shipping_cost']) : 0;
        $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
        
        // Validar dados
        $errors = [];
        
        if (!$shipping_address_id) {
            $errors[] = 'Selecione um endereço de entrega.';
        }
        
        if (!$shipping_method) {
            $errors[] = 'Selecione um método de envio.';
        }
        
        if (!$payment_method) {
            $errors[] = 'Selecione um método de pagamento.';
        }
        
        // Se houver erros, retornar para checkout
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: ' . BASE_URL . 'checkout');
            exit;
        }
        
        // Processar itens do carrinho
        $cart_items = [];
        $subtotal = 0;
        
        if (!empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $product = $this->productModel->find($item['product_id']);
                
                if ($product) {
                    // Calcular preço (considerando promoções)
                    $price = $product['sale_price'] && $product['sale_price'] < $product['price'] 
                           ? $product['sale_price'] 
                           : $product['price'];
                    
                    // Calcular total do item
                    $itemTotal = $price * $item['quantity'];
                    $subtotal += $itemTotal;
                    
                    // Adicionar ao array de itens
                    $cart_items[] = [
                        'product_id' => $product['id'],
                        'product_name' => $product['name'],
                        'price' => $price,
                        'quantity' => $item['quantity'],
                        'customization_data' => !empty($item['customization']) ? json_encode($item['customization']) : null
                    ];
                }
            }
        }
        
        // Calcular totais
        $total = $subtotal + $shipping_cost;
        
        // Gerar número do pedido
        $order_number = 'RP' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Criar pedido no banco de dados
        $order_data = [
            'user_id' => $_SESSION['user_id'],
            'order_number' => $order_number,
            'status' => 'pending',
            'payment_method' => $payment_method,
            'payment_status' => 'pending',
            'shipping_address_id' => $shipping_address_id,
            'shipping_method' => $shipping_method,
            'shipping_cost' => $shipping_cost,
            'subtotal' => $subtotal,
            'discount' => 0, // Implementar cupons futuramente
            'total' => $total
        ];
        
        // Conectar ao banco de dados
        $db = Database::getInstance();
        
        try {
            // Iniciar transação
            $db->getConnection()->beginTransaction();
            
            // Inserir pedido
            $order_id = $db->insert('orders', $order_data);
            
            // Inserir itens do pedido
            foreach ($cart_items as $item) {
                $item_data = [
                    'order_id' => $order_id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'customization_data' => $item['customization_data']
                ];
                
                $db->insert('order_items', $item_data);
                
                // Atualizar estoque do produto
                $product = $this->productModel->find($item['product_id']);
                $new_stock = $product['stock'] - $item['quantity'];
                $db->update('products', ['stock' => $new_stock], 'id = :id', ['id' => $item['product_id']]);
            }
            
            // Confirmar transação
            $db->getConnection()->commit();
            
            // Limpar carrinho
            $_SESSION['cart'] = [];
            $_SESSION['cart_count'] = 0;
            
            // Redirecionar para página de sucesso
            $_SESSION['success'] = 'Pedido realizado com sucesso!';
            header('Location: ' . BASE_URL . 'pedido/sucesso/' . $order_id);
            exit;
            
        } catch (Exception $e) {
            // Reverter transação em caso de erro
            $db->getConnection()->rollBack();
            
            $_SESSION['error'] = 'Erro ao processar o pedido. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'checkout');
            exit;
        }
    }
    
    /**
     * Exibe a página de sucesso do pedido
     */
    public function success($params) {
        $order_id = $params['id'] ?? null;
        
        if (!$order_id) {
            header('Location: ' . BASE_URL);
            exit;
        }
        
        // Buscar dados do pedido
        $db = Database::getInstance();
        $order = $db->select(
            "SELECT o.*, a.* 
            FROM orders o
            LEFT JOIN addresses a ON o.shipping_address_id = a.id
            WHERE o.id = :id AND o.user_id = :user_id",
            ['id' => $order_id, 'user_id' => $_SESSION['user_id']]
        );
        
        if (empty($order)) {
            header('Location: ' . BASE_URL);
            exit;
        }
        
        $order = $order[0];
        
        // Buscar itens do pedido
        $order_items = $db->select(
            "SELECT * FROM order_items WHERE order_id = :order_id",
            ['order_id' => $order_id]
        );
        
        // Renderizar a view
        require_once VIEWS_PATH . '/order-success.php';
    }
    
    /**
     * Verifica se o usuário está autenticado
     */
    private function checkAuthentication() {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['redirect_after_login'] = BASE_URL . 'checkout';
            $_SESSION['error'] = 'Faça login para continuar com a compra.';
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
    }
    
    /**
     * Verifica se o carrinho tem itens
     */
    private function checkCart() {
        if (empty($_SESSION['cart'])) {
            $_SESSION['error'] = 'Seu carrinho está vazio.';
            header('Location: ' . BASE_URL . 'carrinho');
            exit;
        }
    }
}