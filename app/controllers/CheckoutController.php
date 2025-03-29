<?php
/**
 * CheckoutController - Controlador para o processo de checkout
 */
class CheckoutController {
    private $cartModel;
    private $productModel;
    private $userModel;
    
    public function __construct() {
        try {
            $this->cartModel = new CartModel();
            $this->productModel = new ProductModel();
            $this->userModel = new UserModel();
            
            // Verificar se o usuário está logado
            if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
                // Salvar URL atual para redirecionamento após login
                $_SESSION['redirect_after_login'] = BASE_URL . 'checkout';
                
                // Redirecionar para página de login
                $_SESSION['info'] = 'É necessário fazer login para finalizar a compra.';
                header('Location: ' . BASE_URL . 'login');
                exit;
            }
            
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao inicializar CheckoutController");
        }
    }
    
    /**
     * Exibe a página de checkout
     */
    public function index() {
        try {
            $userId = $_SESSION['user']['id'];
            $sessionId = session_id();
            
            // Obter carrinho
            $cart = $this->cartModel->getOrCreate($userId, $sessionId);
            
            // Verificar se o carrinho está vazio
            $cartItems = $this->cartModel->getItems($cart['id']);
            if (empty($cartItems)) {
                $_SESSION['error'] = 'Seu carrinho está vazio.';
                header('Location: ' . BASE_URL . 'carrinho');
                exit;
            }
            
            // Calcular subtotal
            $subtotal = $this->cartModel->calculateSubtotal($cart['id']);
            
            // Obter endereços do usuário
            $addresses = $this->userModel->getUserAddresses($userId);
            
            // Obter métodos de envio
            $shipping_methods = [];
            try {
                $shippingMethodsResult = Database::getInstance()->select("SELECT setting_value FROM settings WHERE setting_key = 'shipping_methods'");
                if (!empty($shippingMethodsResult)) {
                    $shipping_methods = json_decode($shippingMethodsResult[0]['setting_value'], true) ?? [];
                }
            } catch (Exception $e) {
                error_log("Erro ao obter métodos de envio: " . $e->getMessage());
            }
            
            // Obter métodos de pagamento
            $payment_methods = [];
            try {
                $paymentMethodsResult = Database::getInstance()->select("SELECT setting_value FROM settings WHERE setting_key = 'payment_methods'");
                if (!empty($paymentMethodsResult)) {
                    $payment_methods = json_decode($paymentMethodsResult[0]['setting_value'], true) ?? [];
                }
            } catch (Exception $e) {
                error_log("Erro ao obter métodos de pagamento: " . $e->getMessage());
            }
            
            // Obter escalas disponíveis
            $available_scales = [];
            try {
                $scalesResult = Database::getInstance()->select("SELECT setting_value FROM settings WHERE setting_key = 'available_scales'");
                if (!empty($scalesResult)) {
                    $available_scales = json_decode($scalesResult[0]['setting_value'], true) ?? [];
                }
            } catch (Exception $e) {
                error_log("Erro ao obter escalas disponíveis: " . $e->getMessage());
            }
            
            // Calcular tempo total estimado de impressão para produtos sob encomenda
            $print_time = $this->cartModel->calculateEstimatedPrintTime($cart['id']);
            
            // Calcular uso total estimado de filamento para produtos sob encomenda
            $filament_usage = $this->cartModel->calculateEstimatedFilamentUsage($cart['id']);
            
            // Verificar se há produtos sob encomenda no carrinho
            $has_custom_order = false;
            foreach ($cartItems as $item) {
                if (!$item['is_tested'] || $item['stock'] < $item['quantity']) {
                    $has_custom_order = true;
                    break;
                }
            }

            // Calcular data estimada de entrega para produtos sob encomenda
            $estimated_delivery_date = null;
            $estimated_printing_days = 0;
            if ($has_custom_order && $print_time > 0) {
                // Estimar dias de impressão (considerando 8 horas de impressão por dia)
                $estimated_printing_days = ceil($print_time / 8);
                
                // Adicionar 1 dia para preparação (validação do pedido)
                // Adicionar dias de impressão
                // Adicionar 1 dia para acabamento e embalagem
                $total_processing_days = 1 + $estimated_printing_days + 1;
                
                // Calcular data estimada (dias úteis)
                $estimated_delivery_date = $this->calculateBusinessDays(date('Y-m-d'), $total_processing_days);
            }
            
            // Inicializar variáveis para a view
            $shipping_cost = 0;
            $total = $subtotal;
            
            // Renderizar view
            require_once VIEWS_PATH . '/checkout.php';
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao exibir página de checkout");
        }
    }
    
    /**
     * Processa a finalização da compra
     */
    public function finish() {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . 'checkout');
                exit;
            }
            
            $userId = $_SESSION['user']['id'];
            $sessionId = session_id();
            
            // Obter dados do formulário
            $addressId = isset($_POST['shipping_address_id']) ? intval($_POST['shipping_address_id']) : 0;
            $shippingMethod = $_POST['shipping_method'] ?? '';
            $paymentMethod = $_POST['payment_method'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            // Validações básicas
            $errors = [];
            
            if (empty($addressId)) {
                $errors['address'] = 'Selecione um endereço para entrega.';
            }
            
            if (empty($shippingMethod)) {
                $errors['shipping'] = 'Selecione um método de envio.';
            }
            
            if (empty($paymentMethod)) {
                $errors['payment'] = 'Selecione um método de pagamento.';
            }
            
            // Se houver erros, voltar para o checkout
            if (!empty($errors)) {
                $_SESSION['checkout_errors'] = $errors;
                header('Location: ' . BASE_URL . 'checkout');
                exit;
            }
            
            // Obter carrinho
            $cart = $this->cartModel->getOrCreate($userId, $sessionId);
            
            // Verificar se o carrinho está vazio
            $cartItems = $this->cartModel->getItems($cart['id']);
            if (empty($cartItems)) {
                $_SESSION['error'] = 'Seu carrinho está vazio.';
                header('Location: ' . BASE_URL . 'carrinho');
                exit;
            }
            
            // Calcular valores
            $subtotal = $this->cartModel->calculateSubtotal($cart['id']);
            
            // Obter custo de envio baseado no método selecionado
            $shipping_cost = 0;
            try {
                $shippingMethodsResult = Database::getInstance()->select("SELECT setting_value FROM settings WHERE setting_key = 'shipping_methods'");
                if (!empty($shippingMethodsResult)) {
                    $shipping_methods = json_decode($shippingMethodsResult[0]['setting_value'], true) ?? [];
                    foreach ($shipping_methods as $method) {
                        if ($method['name'] === $shippingMethod) {
                            $shipping_cost = $method['price'];
                            break;
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Erro ao obter custo de envio: " . $e->getMessage());
            }
            
            // Calcular total
            $total = $subtotal + $shipping_cost;
            
            // Gerar número do pedido
            $orderNumber = 'TI' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Verificar se há produtos sob encomenda
            $has_custom_order = false;
            foreach ($cartItems as $item) {
                if (!$item['is_tested'] || $item['stock'] < $item['quantity']) {
                    $has_custom_order = true;
                    break;
                }
            }
            
            // Definir status inicial do pedido
            $initialStatus = $has_custom_order ? 'validating' : 'pending';
            
            // Calcular tempo total estimado de impressão para produtos sob encomenda
            $print_time = $this->cartModel->calculateEstimatedPrintTime($cart['id']);
            
            // Criar pedido no banco de dados
            $orderId = Database::getInstance()->insert('orders', [
                'user_id' => $userId,
                'order_number' => $orderNumber,
                'status' => $initialStatus,
                'estimated_print_time_hours' => $print_time > 0 ? $print_time : null,
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'shipping_address_id' => $addressId,
                'shipping_method' => $shippingMethod,
                'shipping_cost' => $shipping_cost,
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $total,
                'notes' => $notes,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Adicionar itens ao pedido
            foreach ($cartItems as $item) {
                $is_stock_item = $item['is_tested'] && $item['stock'] >= $item['quantity'];
                
                Database::getInstance()->insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['sale_price'] && $item['sale_price'] < $item['price'] ? $item['sale_price'] : $item['price'],
                    'selected_scale' => $item['selected_scale'],
                    'selected_filament' => $item['selected_filament'],
                    'selected_color' => $item['selected_color'],
                    'customer_model_id' => $item['customer_model_id'],
                    'print_time_hours' => $is_stock_item ? null : ($item['print_time_hours'] * $item['quantity']),
                    'is_stock_item' => $is_stock_item ? 1 : 0,
                    'customization_data' => $item['customization_data'],
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                // Atualizar estoque do produto (apenas para produtos testados)
                if ($is_stock_item) {
                    $product = $this->productModel->find($item['product_id']);
                    if ($product) {
                        $newStock = max(0, $product['stock'] - $item['quantity']);
                        $this->productModel->update($item['product_id'], ['stock' => $newStock]);
                    }
                }
            }
            
            // Limpar carrinho
            $this->cartModel->clearItems($cart['id']);
            
            // Redirecionar para página de sucesso
            $_SESSION['success'] = 'Pedido realizado com sucesso!';
            header('Location: ' . BASE_URL . 'pedido/sucesso/' . $orderNumber);
            exit;
        } catch (Exception $e) {
            $this->handleError($e, "Erro ao finalizar pedido");
        }
    }
    
    /**
     * Calcula a data estimada considerando dias úteis
     * 
     * @param string $startDate Data inicial no formato Y-m-d
     * @param int $businessDays Número de dias úteis a adicionar
     * @return string Data estimada no formato d/m/Y
     */
    private function calculateBusinessDays($startDate, $businessDays) {
        $date = new DateTime($startDate);
        $daysAdded = 0;
        
        while ($daysAdded < $businessDays) {
            $date->modify('+1 day');
            
            // Se não for fim de semana (6 = sábado, 0 = domingo)
            $weekDay = $date->format('w');
            if ($weekDay != 0 && $weekDay != 6) {
                $daysAdded++;
            }
        }
        
        return $date->format('d/m/Y');
    }
    
    /**
     * Tratamento de erros centralizado
     */
    private function handleError(Exception $e, $context = '') {
        // Registrar erro no log
        error_log("{$context}: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Variáveis para a view de erro (visíveis apenas em ambiente de desenvolvimento)
        $error_message = $e->getMessage();
        $error_trace = $e->getTraceAsString();
        
        // Renderizar página de erro ou redirecionar
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            // Em desenvolvimento, mostrar detalhes do erro
            header("HTTP/1.0 500 Internal Server Error");
            include VIEWS_PATH . '/errors/500.php';
        } else {
            // Em produção, redirecionar para o carrinho
            $_SESSION['error'] = 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.';
            header('Location: ' . BASE_URL . 'carrinho');
        }
        exit;
    }
}