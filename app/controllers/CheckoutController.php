<?php
/**
 * CheckoutController - Controlador para o processo de checkout
 * 
 * @version     1.3.0
 * @author      Taverna da Impressão
 */
class CheckoutController {
    // Implementação do trait de validação de entrada
    use InputValidationTrait;
    
    private $cartModel;
    private $productModel;
    private $userModel;
    
    public function __construct() {
        try {
            // Carregar modelos
            $this->cartModel = new CartModel();
            $this->productModel = new ProductModel();
            $this->userModel = new UserModel();
            
            // Carregar bibliotecas de segurança
            require_once APP_PATH . '/lib/Security/SecurityManager.php';
            require_once APP_PATH . '/lib/Security/Validator.php';
            require_once APP_PATH . '/lib/Security/CsrfProtection.php';
            require_once APP_PATH . '/lib/Security/InputValidationTrait.php';
            
            // Verificar se o usuário está logado
            if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
                // Salvar URL atual para redirecionamento após login
                $_SESSION['redirect_after_login'] = BASE_URL . 'checkout';
                
                // Redirecionar para página de login
                $_SESSION['info'] = 'É necessário fazer login para finalizar a compra.';
                header('Location: ' . BASE_URL . 'login');
                return;
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
            // Verificar se o usuário está logado
            $userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
            if ($userId <= 0) {
                $_SESSION['error'] = 'É necessário fazer login para finalizar a compra.';
                header('Location: ' . BASE_URL . 'login');
                return;
            }
            
            $sessionId = session_id();
            
            // Obter carrinho
            $cart = $this->cartModel->getOrCreate($userId, $sessionId);
            
            // Verificar se o carrinho está vazio
            $cartItems = $this->cartModel->getItems($cart['id']);
            if (empty($cartItems)) {
                $_SESSION['error'] = 'Seu carrinho está vazio.';
                header('Location: ' . BASE_URL . 'carrinho');
                return;
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
            
            // Gerar token CSRF para o formulário
            $csrf_token = CsrfProtection::getToken();
            
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
                return;
            }
            
            // Verificar token CSRF
            if (!CsrfProtection::validateRequest()) {
                $_SESSION['error'] = 'Erro de segurança: Token inválido. Por favor, tente novamente.';
                header('Location: ' . BASE_URL . 'checkout');
                return;
            }
            
            // Verificar se o usuário está logado
            $userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
            if ($userId <= 0) {
                $_SESSION['error'] = 'É necessário fazer login para finalizar a compra.';
                header('Location: ' . BASE_URL . 'login');
                return;
            }
            
            $sessionId = session_id();
            
            // Obter e validar dados do formulário usando InputValidationTrait
            $addressId = $this->postValidatedParam('shipping_address_id', 'int', ['required' => true, 'min' => 1]);
            $shippingMethod = $this->postValidatedParam('shipping_method', 'string', ['required' => true, 'maxLength' => 100]);
            $paymentMethod = $this->postValidatedParam('payment_method', 'string', ['required' => true, 'maxLength' => 100]);
            $notes = $this->postValidatedParam('notes', 'string', ['required' => false, 'maxLength' => 2000]);
            
            // Verificar erros de validação
            $errors = [];
            
            if ($addressId === null) {
                $errors['address'] = 'Selecione um endereço para entrega.';
            }
            
            if ($shippingMethod === null) {
                $errors['shipping'] = 'Selecione um método de envio.';
            }
            
            if ($paymentMethod === null) {
                $errors['payment'] = 'Selecione um método de pagamento.';
            }
            
            // Verificar se há erros de validação no trait
            if ($this->hasValidationErrors()) {
                $validationErrors = $this->getValidationErrors();
                foreach ($validationErrors as $field => $fieldErrors) {
                    $errors[$field] = implode(', ', $fieldErrors);
                }
            }
            
            // Se houver erros, voltar para o checkout
            if (!empty($errors)) {
                $_SESSION['checkout_errors'] = $errors;
                header('Location: ' . BASE_URL . 'checkout');
                return;
            }
            
            // Validar que o endereço pertence ao usuário
            $userAddresses = $this->userModel->getUserAddresses($userId);
            $validAddress = false;
            foreach ($userAddresses as $address) {
                if ((int)$address['id'] === $addressId) {
                    $validAddress = true;
                    break;
                }
            }
            
            if (!$validAddress) {
                $_SESSION['error'] = 'Endereço inválido selecionado.';
                header('Location: ' . BASE_URL . 'checkout');
                return;
            }
            
            // Obter carrinho
            $cart = $this->cartModel->getOrCreate($userId, $sessionId);
            
            // Verificar se o carrinho está vazio
            $cartItems = $this->cartModel->getItems($cart['id']);
            if (empty($cartItems)) {
                $_SESSION['error'] = 'Seu carrinho está vazio.';
                header('Location: ' . BASE_URL . 'carrinho');
                return;
            }
            
            // Validar estoque dos produtos
            foreach ($cartItems as $item) {
                $product = $this->productModel->find($item['product_id']);
                if ($product && $product['is_tested'] && $product['stock'] < $item['quantity']) {
                    $_SESSION['error'] = "Quantidade solicitada do produto '{$item['product_name']}' não está disponível em estoque.";
                    header('Location: ' . BASE_URL . 'carrinho');
                    return;
                }
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
                            $shipping_cost = (float)$method['price'];
                            break;
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Erro ao obter custo de envio: " . $e->getMessage());
            }
            
            // Calcular total
            $total = $subtotal + $shipping_cost;
            
            // Validar total
            if ($total <= 0) {
                $_SESSION['error'] = 'Valor total inválido. Por favor, verifique os itens do carrinho.';
                header('Location: ' . BASE_URL . 'carrinho');
                return;
            }
            
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
            
            // Sanitizar notas - Já está sanitizado pelo InputValidationTrait
            
            // Criar pedido no banco de dados
            $orderId = Database::getInstance()->insert('orders', [
                'user_id' => $userId,
                'order_number' => $orderNumber,
                'status' => $initialStatus,
                'estimated_print_time_hours' => $print_time > 0 ? $print_time : null,
                'payment_method' => $paymentMethod, // Já sanitizado pelo trait
                'payment_status' => 'pending',
                'shipping_address_id' => $addressId,
                'shipping_method' => $shippingMethod, // Já sanitizado pelo trait
                'shipping_cost' => $shipping_cost,
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $total,
                'notes' => $notes, // Já sanitizado pelo trait
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$orderId) {
                throw new Exception("Falha ao criar pedido.");
            }
            
            // Adicionar itens ao pedido
            foreach ($cartItems as $item) {
                $is_stock_item = $item['is_tested'] && $item['stock'] >= $item['quantity'];
                
                // Valores já sanitizados pelo trait ou sanitizados aqui
                $productName = SecurityManager::sanitize($item['product_name']);
                $selectedScale = isset($item['selected_scale']) ? SecurityManager::sanitize($item['selected_scale']) : null;
                $selectedFilament = isset($item['selected_filament']) ? SecurityManager::sanitize($item['selected_filament']) : null;
                $selectedColor = isset($item['selected_color']) ? SecurityManager::sanitize($item['selected_color']) : null;
                $customizationData = isset($item['customization_data']) ? SecurityManager::sanitize($item['customization_data']) : null;
                
                $itemId = Database::getInstance()->insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => (int)$item['product_id'],
                    'product_name' => $productName,
                    'quantity' => (int)$item['quantity'],
                    'price' => $item['sale_price'] && $item['sale_price'] < $item['price'] ? (float)$item['sale_price'] : (float)$item['price'],
                    'selected_scale' => $selectedScale,
                    'selected_filament' => $selectedFilament,
                    'selected_color' => $selectedColor,
                    'customer_model_id' => isset($item['customer_model_id']) ? (int)$item['customer_model_id'] : null,
                    'print_time_hours' => $is_stock_item ? null : ((float)$item['print_time_hours'] * (int)$item['quantity']),
                    'is_stock_item' => $is_stock_item ? 1 : 0,
                    'customization_data' => $customizationData,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                if (!$itemId) {
                    throw new Exception("Falha ao adicionar item ao pedido: " . $productName);
                }
                
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
            
            // Registrar no log do sistema
            error_log("Pedido #{$orderNumber} criado com sucesso para o usuário #{$userId}. Total: " . number_format($total, 2, '.', ''));
            
            // Redirecionar para página de sucesso
            $_SESSION['success'] = 'Pedido realizado com sucesso!';
            header('Location: ' . BASE_URL . 'pedido/sucesso/' . $orderNumber);
            return;
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
        
        // Registrar detalhes adicionais para depuração
        error_log("POST data: " . print_r($_POST, true));
        
        // Sanitizar mensagem de erro para exibição
        $errorMessage = SecurityManager::sanitize($e->getMessage());
        
        // Variáveis para a view de erro (visíveis apenas em ambiente de desenvolvimento)
        $error_message = $errorMessage;
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
        return;
    }
}