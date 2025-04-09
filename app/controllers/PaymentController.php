<?php
/**
 * PaymentController - Controlador para processamento de pagamentos
 * 
 * Responsável por iniciar, processar, verificar e gerenciar pagamentos
 * através da integração com diversos gateways.
 * 
 * @package     App\Controllers
 * @version     1.0.0
 * @author      Taverna da Impressão
 */

use App\Lib\Payment\PaymentManager;
use App\Lib\Security\CsrfProtection;
use App\Lib\Security\SecurityManager;
use App\Lib\Validation\InputValidationTrait;

class PaymentController {
    use InputValidationTrait;
    
    /**
     * @var PaymentManager Instância do gerenciador de pagamentos
     */
    private $paymentManager;
    
    /**
     * @var Database Instância do banco de dados
     */
    private $db;
    
    /**
     * Construtor
     */
    public function __construct() {
        // Verificar se usuário está logado
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            $_SESSION['error'] = 'É necessário fazer login para acessar esta área.';
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        
        // Inicializar componentes
        $this->paymentManager = PaymentManager::getInstance();
        $this->db = Database::getInstance();
        
        // Carregar dependências
        require_once APP_PATH . '/lib/Payment/PaymentManager.php';
        require_once APP_PATH . '/lib/Security/SecurityManager.php';
        require_once APP_PATH . '/lib/Security/CsrfProtection.php';
    }
    
    /**
     * Processa um pagamento para um pedido
     */
    public function process() {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . 'meus-pedidos');
                exit;
            }
            
            // Verificar token CSRF
            if (!CsrfProtection::validateRequest()) {
                $_SESSION['error'] = 'Erro de segurança: Token inválido. Por favor, tente novamente.';
                header('Location: ' . BASE_URL . 'meus-pedidos');
                exit;
            }
            
            // Obter e validar dados do formulário
            $orderId = $this->postValidatedParam('order_id', 'int', ['required' => true, 'min' => 1]);
            $paymentMethod = $this->postValidatedParam('payment_method', 'string', ['required' => true, 'maxLength' => 50]);
            
            // Verificar erros de validação
            if ($this->hasValidationErrors()) {
                $errors = $this->getValidationErrors();
                $errorMessages = [];
                
                foreach ($errors as $field => $fieldErrors) {
                    $errorMessages[] = implode(', ', $fieldErrors);
                }
                
                $_SESSION['error'] = 'Erro de validação: ' . implode('; ', $errorMessages);
                header('Location: ' . BASE_URL . 'pedido/pagar/' . $orderId);
                exit;
            }
            
            // Verificar se pedido existe e pertence ao usuário logado
            $userID = (int)$_SESSION['user']['id'];
            $order = $this->getOrder($orderId, $userID);
            
            if (!$order) {
                $_SESSION['error'] = 'Pedido não encontrado ou você não tem permissão para acessá-lo.';
                header('Location: ' . BASE_URL . 'meus-pedidos');
                exit;
            }
            
            // Verificar se pedido está apto para pagamento
            if (!in_array($order['payment_status'], ['pending', 'failed'])) {
                $_SESSION['error'] = 'Este pedido não está disponível para pagamento.';
                header('Location: ' . BASE_URL . 'pedido/detalhes/' . $orderId);
                exit;
            }
            
            // Preparar dados para o processamento de pagamento
            $orderData = [
                'id' => $order['id'],
                'order_number' => $order['order_number'],
                'total' => $order['total'],
                'subtotal' => $order['subtotal'],
                'shipping_cost' => $order['shipping_cost'],
                'discount' => $order['discount'],
                'items' => $this->getOrderItems($order['id'])
            ];
            
            // Obter dados do cliente
            $customer = $this->getCustomerData($userID);
            
            // Obter endereço de entrega
            $shippingAddress = $this->getShippingAddress($order['shipping_address_id']);
            
            // Mesclar dados do cliente com endereço
            $customerData = array_merge($customer, $shippingAddress);
            
            // Preparar dados de pagamento
            $paymentData = [
                'payment_method' => $paymentMethod
            ];
            
            // Adicionar dados específicos por método de pagamento
            switch ($paymentMethod) {
                case 'credit_card':
                    // Campos para cartão de crédito
                    $cardToken = $this->postValidatedParam('card_token', 'string', ['required' => true]);
                    $installments = $this->postValidatedParam('installments', 'int', ['required' => true, 'min' => 1, 'max' => 12]);
                    $cardBrand = $this->postValidatedParam('card_brand', 'string', ['required' => true]);
                    
                    if ($this->hasValidationErrors()) {
                        $_SESSION['error'] = 'Dados do cartão inválidos ou incompletos.';
                        header('Location: ' . BASE_URL . 'pedido/pagar/' . $orderId);
                        exit;
                    }
                    
                    $paymentData['card_token'] = $cardToken;
                    $paymentData['installments'] = $installments;
                    $paymentData['card_brand'] = $cardBrand;
                    break;
                    
                case 'pix':
                case 'boleto':
                    // Não requer dados adicionais específicos
                    break;
                
                case 'paypal':
                    // Redirecionar para página do PayPal
                    header('Location: ' . BASE_URL . 'pagamento/paypal/' . $orderId);
                    exit;
                    
                default:
                    $_SESSION['error'] = 'Método de pagamento não suportado.';
                    header('Location: ' . BASE_URL . 'pedido/pagar/' . $orderId);
                    exit;
            }
            
            // Processar pagamento através do PaymentManager
            $result = $this->paymentManager->processPayment($orderData, $customerData, $paymentData);
            
            // Verificar resultado
            if (!($result['success'] ?? false)) {
                $_SESSION['error'] = 'Erro ao processar pagamento: ' . ($result['error_message'] ?? 'Erro desconhecido');
                header('Location: ' . BASE_URL . 'pedido/pagar/' . $orderId);
                exit;
            }
            
            // Atualizar pedido com dados da transação
            $this->updateOrderPaymentInfo($orderId, $result, $paymentMethod);
            
            // Redirecionar conforme resultado e método de pagamento
            if ($paymentMethod === 'credit_card') {
                // Cartão geralmente é processado imediatamente
                $_SESSION['success'] = 'Pagamento processado com sucesso!';
                header('Location: ' . BASE_URL . 'pedido/sucesso/' . $order['order_number']);
                exit;
            } elseif ($paymentMethod === 'pix') {
                // Redirecionar para página de PIX
                header('Location: ' . BASE_URL . 'pagamento/pix/' . $orderId);
                exit;
            } elseif ($paymentMethod === 'boleto') {
                // Redirecionar para página de boleto
                header('Location: ' . BASE_URL . 'pagamento/boleto/' . $orderId);
                exit;
            } else {
                // Outros métodos - verificar se há URL de redirecionamento
                if (isset($result['redirect_url']) && !empty($result['redirect_url'])) {
                    header('Location: ' . $result['redirect_url']);
                    exit;
                } else {
                    // Fallback para página de sucesso
                    $_SESSION['success'] = 'Pagamento iniciado com sucesso!';
                    header('Location: ' . BASE_URL . 'pedido/pendente/' . $order['order_number']);
                    exit;
                }
            }
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro no processamento de pagamento: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao processar seu pagamento: ' . SecurityManager::sanitize($e->getMessage());
            header('Location: ' . BASE_URL . 'meus-pedidos');
            exit;
        }
    }
    
    /**
     * Exibe a página de pagamento PIX
     * 
     * @param int $orderId ID do pedido
     */
    public function pix($orderId) {
        try {
            // Validar parâmetros
            $orderId = (int)$orderId;
            
            if ($orderId <= 0) {
                $_SESSION['error'] = 'Pedido inválido.';
                header('Location: ' . BASE_URL . 'meus-pedidos');
                exit;
            }
            
            // Verificar se pedido existe e pertence ao usuário logado
            $userID = (int)$_SESSION['user']['id'];
            $order = $this->getOrder($orderId, $userID);
            
            if (!$order) {
                $_SESSION['error'] = 'Pedido não encontrado ou você não tem permissão para acessá-lo.';
                header('Location: ' . BASE_URL . 'meus-pedidos');
                exit;
            }
            
            // Verificar se há transação de pagamento para o pedido
            if (empty($order['payment_transaction_id'])) {
                $_SESSION['error'] = 'Não há pagamento processado para este pedido.';
                header('Location: ' . BASE_URL . 'pedido/pagar/' . $orderId);
                exit;
            }
            
            // Verificar transação no gateway
            $transactionId = $order['payment_transaction_id'];
            $gatewayName = $order['payment_gateway'];
            
            $transactionInfo = $this->paymentManager->checkTransactionStatus($transactionId, $gatewayName);
            
            if (!($transactionInfo['success'] ?? false)) {
                $_SESSION['error'] = 'Erro ao verificar transação de pagamento.';
                header('Location: ' . BASE_URL . 'pedido/detalhes/' . $orderId);
                exit;
            }
            
            // Extrair informações do PIX
            $pixInfo = [
                'qr_code' => $transactionInfo['qr_code'] ?? null,
                'qr_code_text' => $transactionInfo['qr_code_text'] ?? null,
                'expires_at' => null
            ];
            
            // Processar dados de validade se disponíveis
            if (isset($transactionInfo['date_of_expiration'])) {
                $expirationDate = new DateTime($transactionInfo['date_of_expiration']);
                $pixInfo['expires_at'] = $expirationDate->format('d/m/Y H:i:s');
            }
            
            // Verificar se temos dados de QR Code
            if (empty($pixInfo['qr_code']) && empty($pixInfo['qr_code_text'])) {
                // Tentar obter a partir dos dados adicionais do pedido
                if (!empty($order['payment_details'])) {
                    $paymentDetails = json_decode($order['payment_details'], true);
                    
                    $pixInfo['qr_code'] = $paymentDetails['qr_code'] ?? null;
                    $pixInfo['qr_code_text'] = $paymentDetails['qr_code_text'] ?? null;
                    
                    if (isset($paymentDetails['expires_at'])) {
                        $pixInfo['expires_at'] = $paymentDetails['expires_at'];
                    }
                }
            }
            
            // Se ainda não temos dados do PIX, redirecionar
            if (empty($pixInfo['qr_code']) && empty($pixInfo['qr_code_text'])) {
                $_SESSION['error'] = 'Dados do PIX não disponíveis.';
                header('Location: ' . BASE_URL . 'pedido/detalhes/' . $orderId);
                exit;
            }
            
            // Variáveis para template
            $pageTitle = 'Pagamento PIX';
            $qrCode = $pixInfo['qr_code'];
            $qrCodeText = $pixInfo['qr_code_text'];
            $expiresAt = $pixInfo['expires_at'];
            $orderNumber = $order['order_number'];
            $total = $order['total'];
            $currencySymbol = 'R$';
            
            // Gerar token CSRF para verificação de status
            $csrf_token = CsrfProtection::getToken();
            
            // Carregar view
            require_once VIEWS_PATH . '/payment/pix.php';
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro ao exibir página de PIX: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao exibir o pagamento PIX: ' . SecurityManager::sanitize($e->getMessage());
            header('Location: ' . BASE_URL . 'meus-pedidos');
            exit;
        }
    }
    
    /**
     * Exibe a página de pagamento com boleto
     * 
     * @param int $orderId ID do pedido
     */
    public function boleto($orderId) {
        try {
            // Validar parâmetros
            $orderId = (int)$orderId;
            
            if ($orderId <= 0) {
                $_SESSION['error'] = 'Pedido inválido.';
                header('Location: ' . BASE_URL . 'meus-pedidos');
                exit;
            }
            
            // Verificar se pedido existe e pertence ao usuário logado
            $userID = (int)$_SESSION['user']['id'];
            $order = $this->getOrder($orderId, $userID);
            
            if (!$order) {
                $_SESSION['error'] = 'Pedido não encontrado ou você não tem permissão para acessá-lo.';
                header('Location: ' . BASE_URL . 'meus-pedidos');
                exit;
            }
            
            // Verificar se há transação de pagamento para o pedido
            if (empty($order['payment_transaction_id'])) {
                $_SESSION['error'] = 'Não há pagamento processado para este pedido.';
                header('Location: ' . BASE_URL . 'pedido/pagar/' . $orderId);
                exit;
            }
            
            // Verificar transação no gateway
            $transactionId = $order['payment_transaction_id'];
            $gatewayName = $order['payment_gateway'];
            
            $transactionInfo = $this->paymentManager->checkTransactionStatus($transactionId, $gatewayName);
            
            if (!($transactionInfo['success'] ?? false)) {
                $_SESSION['error'] = 'Erro ao verificar transação de pagamento.';
                header('Location: ' . BASE_URL . 'pedido/detalhes/' . $orderId);
                exit;
            }
            
            // Extrair informações do boleto
            $boletoInfo = [
                'barcode' => $transactionInfo['barcode'] ?? null,
                'pdf_url' => $transactionInfo['pdf_url'] ?? null,
                'expires_at' => null
            ];
            
            // Processar dados de validade se disponíveis
            if (isset($transactionInfo['date_of_expiration'])) {
                $expirationDate = new DateTime($transactionInfo['date_of_expiration']);
                $boletoInfo['expires_at'] = $expirationDate->format('d/m/Y');
            }
            
            // Verificar se temos dados de boleto
            if (empty($boletoInfo['pdf_url'])) {
                // Tentar obter a partir dos dados adicionais do pedido
                if (!empty($order['payment_details'])) {
                    $paymentDetails = json_decode($order['payment_details'], true);
                    
                    $boletoInfo['pdf_url'] = $paymentDetails['pdf_url'] ?? null;
                    $boletoInfo['barcode'] = $paymentDetails['barcode'] ?? null;
                    
                    if (isset($paymentDetails['expires_at'])) {
                        $boletoInfo['expires_at'] = $paymentDetails['expires_at'];
                    }
                }
            }
            
            // Se ainda não temos dados do boleto, redirecionar
            if (empty($boletoInfo['pdf_url']) && empty($boletoInfo['barcode'])) {
                $_SESSION['error'] = 'Dados do boleto não disponíveis.';
                header('Location: ' . BASE_URL . 'pedido/detalhes/' . $orderId);
                exit;
            }
            
            // Variáveis para template
            $pageTitle = 'Pagamento com Boleto';
            $boletoUrl = $boletoInfo['pdf_url'];
            $barCode = $boletoInfo['barcode'];
            $expiresAt = $boletoInfo['expires_at'];
            $orderNumber = $order['order_number'];
            $total = $order['total'];
            $currencySymbol = 'R$';
            
            // Gerar token CSRF para verificação de status
            $csrf_token = CsrfProtection::getToken();
            
            // Carregar view
            require_once VIEWS_PATH . '/payment/boleto.php';
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro ao exibir página de boleto: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao exibir o boleto: ' . SecurityManager::sanitize($e->getMessage());
            header('Location: ' . BASE_URL . 'meus-pedidos');
            exit;
        }
    }
    
    /**
     * Exibe página de pagamento com PayPal
     * 
     * @param int $orderId ID do pedido
     */
    public function paypal($orderId) {
        try {
            // Validar parâmetros
            $orderId = (int)$orderId;
            
            if ($orderId <= 0) {
                $_SESSION['error'] = 'Pedido inválido.';
                header('Location: ' . BASE_URL . 'meus-pedidos');
                exit;
            }
            
            // Verificar se pedido existe e pertence ao usuário logado
            $userID = (int)$_SESSION['user']['id'];
            $order = $this->getOrder($orderId, $userID);
            
            if (!$order) {
                $_SESSION['error'] = 'Pedido não encontrado ou você não tem permissão para acessá-lo.';
                header('Location: ' . BASE_URL . 'meus-pedidos');
                exit;
            }
            
            // Verificar se pedido está apto para pagamento
            if (!in_array($order['payment_status'], ['pending', 'failed'])) {
                $_SESSION['error'] = 'Este pedido não está disponível para pagamento.';
                header('Location: ' . BASE_URL . 'pedido/detalhes/' . $orderId);
                exit;
            }
            
            // Obter configurações do PayPal
            $paypalConfig = [];
            
            try {
                $gateway = $this->paymentManager->getGateway('paypal');
                $paypalConfig = $gateway->getFrontendConfig();
            } catch (\Exception $e) {
                // Registrar erro
                error_log("Erro ao obter configurações do PayPal: " . $e->getMessage());
                
                // Exibir mensagem para o usuário
                $_SESSION['error'] = 'Pagamento via PayPal não está disponível no momento. Por favor, tente outro método.';
                header('Location: ' . BASE_URL . 'pedido/pagar/' . $orderId);
                exit;
            }
            
            // Variáveis para template
            $pageTitle = 'Pagamento com PayPal';
            $orderNumber = $order['order_number'];
            $total = $order['total'];
            $clientId = $paypalConfig['client_id'] ?? '';
            $isSandbox = $paypalConfig['is_sandbox'] ?? true;
            $currency = $paypalConfig['currency'] ?? 'BRL';
            
            // Gerar token CSRF para requisições AJAX
            $csrf_token = CsrfProtection::getToken();
            
            // Carregar view
            require_once VIEWS_PATH . '/payment/paypal_checkout.php';
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro ao exibir página de PayPal: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao carregar o pagamento via PayPal: ' . SecurityManager::sanitize($e->getMessage());
            header('Location: ' . BASE_URL . 'meus-pedidos');
            exit;
        }
    }
    
    /**
     * Cria uma ordem de pagamento no PayPal (endpoint AJAX)
     */
    public function createPayPalOrder() {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
                return;
            }
            
            // Verificar token CSRF
            if (!CsrfProtection::validateRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
                return;
            }
            
            // Obter e decodificar dados JSON
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            // Validar dados
            if (!$data || !isset($data['order_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Dados incompletos'], 400);
                return;
            }
            
            $orderId = (int)$data['order_id'];
            
            // Verificar se pedido existe e pertence ao usuário logado
            $userID = (int)$_SESSION['user']['id'];
            $order = $this->getOrder($orderId, $userID);
            
            if (!$order) {
                $this->jsonResponse(['success' => false, 'message' => 'Pedido não encontrado'], 404);
                return;
            }
            
            // Obter dados do cliente
            $customer = $this->getCustomerData($userID);
            
            // Obter endereço de entrega
            $shippingAddress = $this->getShippingAddress($order['shipping_address_id']);
            
            // Mesclar dados do cliente com endereço
            $customerData = array_merge($customer, $shippingAddress);
            
            // Preparar dados do pedido
            $orderData = [
                'id' => $order['id'],
                'order_number' => $order['order_number'],
                'total' => $order['total'],
                'subtotal' => $order['subtotal'],
                'shipping_cost' => $order['shipping_cost'],
                'discount' => $order['discount'],
                'items' => $this->getOrderItems($order['id'])
            ];
            
            // Preparar dados de pagamento
            $paymentData = [
                'payment_method' => 'paypal'
            ];
            
            // Processar pagamento via PaymentManager
            $result = $this->paymentManager->processPayment($orderData, $customerData, $paymentData);
            
            // Verificar resultado
            if (!($result['success'] ?? false)) {
                $this->jsonResponse([
                    'success' => false, 
                    'error_message' => $result['error_message'] ?? 'Erro ao processar pagamento'
                ], 400);
                return;
            }
            
            // Retornar dados da transação
            $this->jsonResponse([
                'success' => true,
                'transaction_id' => $result['transaction_id'],
                'status' => $result['status'] ?? 'pending'
            ]);
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro ao criar ordem PayPal: " . $e->getMessage());
            
            // Retornar erro em JSON
            $this->jsonResponse([
                'success' => false,
                'error_message' => 'Erro interno ao processar pagamento',
                'debug' => DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Captura um pagamento PayPal aprovado (endpoint AJAX)
     */
    public function capturePayPalOrder() {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
                return;
            }
            
            // Verificar token CSRF
            if (!CsrfProtection::validateRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
                return;
            }
            
            // Obter e decodificar dados JSON
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            // Validar dados
            if (!$data || !isset($data['order_id']) || !isset($data['paypal_order_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Dados incompletos'], 400);
                return;
            }
            
            $orderId = (int)$data['order_id'];
            $paypalOrderId = SecurityManager::sanitize($data['paypal_order_id']);
            
            // Verificar se pedido existe e pertence ao usuário logado
            $userID = (int)$_SESSION['user']['id'];
            $order = $this->getOrder($orderId, $userID);
            
            if (!$order) {
                $this->jsonResponse(['success' => false, 'message' => 'Pedido não encontrado'], 404);
                return;
            }
            
            // Capturar pagamento via PaymentManager
            $result = $this->paymentManager->capturePayPalPayment($paypalOrderId, 'user-' . $userID);
            
            // Verificar resultado
            if (!($result['success'] ?? false)) {
                $this->jsonResponse([
                    'success' => false, 
                    'error_message' => $result['error_message'] ?? 'Erro ao capturar pagamento'
                ], 400);
                return;
            }
            
            // Atualizar pedido com dados da transação
            $this->updateOrderPaymentInfo($orderId, $result, 'paypal');
            
            // Retornar resultado
            $this->jsonResponse([
                'success' => true,
                'status' => $result['status'] ?? 'approved',
                'redirect_url' => BASE_URL . 'pedido/sucesso/' . $order['order_number']
            ]);
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro ao capturar pagamento PayPal: " . $e->getMessage());
            
            // Retornar erro em JSON
            $this->jsonResponse([
                'success' => false,
                'error_message' => 'Erro interno ao processar pagamento',
                'debug' => DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Registra cancelamento de pagamento PayPal (endpoint AJAX)
     */
    public function cancelPayPalOrder() {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
                return;
            }
            
            // Verificar token CSRF
            if (!CsrfProtection::validateRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
                return;
            }
            
            // Obter e decodificar dados JSON
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            // Validar dados
            if (!$data || !isset($data['order_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Dados incompletos'], 400);
                return;
            }
            
            $orderId = (int)$data['order_id'];
            $reason = isset($data['reason']) ? SecurityManager::sanitize($data['reason']) : 'user_cancelled';
            
            // Verificar se pedido existe e pertence ao usuário logado
            $userID = (int)$_SESSION['user']['id'];
            $order = $this->getOrder($orderId, $userID);
            
            if (!$order) {
                $this->jsonResponse(['success' => false, 'message' => 'Pedido não encontrado'], 404);
                return;
            }
            
            // Registrar cancelamento
            $pdo = $this->db->getPdo();
            $stmt = $pdo->prepare("
                INSERT INTO payment_attempts 
                (order_id, payment_method, gateway, status, amount, success, additional_data, created_at) 
                VALUES (?, 'paypal', 'paypal', 'cancelled', ?, 0, ?, NOW())
            ");
            
            $additionalData = json_encode(['reason' => $reason, 'cancelled_by' => 'user']);
            $stmt->execute([$orderId, $order['total'], $additionalData]);
            
            // Retornar resultado
            $this->jsonResponse([
                'success' => true,
                'message' => 'Cancelamento registrado com sucesso'
            ]);
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro ao cancelar pagamento PayPal: " . $e->getMessage());
            
            // Retornar erro em JSON
            $this->jsonResponse([
                'success' => false,
                'error_message' => 'Erro interno ao registrar cancelamento',
                'debug' => DEBUG ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Registra erro de pagamento (endpoint AJAX)
     */
    public function logError() {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
                return;
            }
            
            // Verificar token CSRF
            if (!CsrfProtection::validateRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
                return;
            }
            
            // Obter e decodificar dados JSON
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            // Validar dados mínimos
            if (!$data || !isset($data['order_id']) || !isset($data['error_type'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Dados incompletos'], 400);
                return;
            }
            
            // Sanitizar dados
            $orderId = (int)$data['order_id'];
            $paymentMethod = SecurityManager::sanitize($data['payment_method'] ?? 'unknown');
            $errorType = SecurityManager::sanitize($data['error_type']);
            $errorMessage = SecurityManager::sanitize($data['error_message'] ?? 'No details provided');
            
            // Registrar erro no log
            error_log("Erro de pagamento [{$errorType}] para pedido #{$orderId}: {$errorMessage}");
            
            // Registrar na base de dados para análise
            $pdo = $this->db->getPdo();
            $stmt = $pdo->prepare("
                INSERT INTO payment_attempts 
                (order_id, payment_method, gateway, status, success, additional_data, created_at) 
                VALUES (?, ?, ?, 'error', 0, ?, NOW())
            ");
            
            $additionalData = json_encode([
                'error_type' => $errorType,
                'error_message' => $errorMessage,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'ip' => $this->getClientIp()
            ]);
            
            $stmt->execute([$orderId, $paymentMethod, $paymentMethod, $additionalData]);
            
            // Retornar resultado
            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro ao registrar erro de pagamento: " . $e->getMessage());
            
            // Retornar sempre sucesso para não complicar fluxo do cliente
            $this->jsonResponse(['success' => true]);
        }
    }
    
    /**
     * Verifica o status de um pagamento via AJAX
     */
    public function checkStatus() {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
                return;
            }
            
            // Verificar token CSRF
            if (!CsrfProtection::validateRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Token inválido'], 403);
                return;
            }
            
            // Obter e validar dados do formulário
            $orderId = $this->postValidatedParam('order_id', 'int', ['required' => true, 'min' => 1]);
            
            // Verificar erros de validação
            if ($this->hasValidationErrors()) {
                $this->jsonResponse(['success' => false, 'message' => 'Parâmetros inválidos'], 400);
                return;
            }
            
            // Verificar se pedido existe e pertence ao usuário logado
            $userID = (int)$_SESSION['user']['id'];
            $order = $this->getOrder($orderId, $userID);
            
            if (!$order) {
                $this->jsonResponse(['success' => false, 'message' => 'Pedido não encontrado'], 404);
                return;
            }
            
            // Verificar se há transação de pagamento
            if (empty($order['payment_transaction_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Transação não encontrada'], 404);
                return;
            }
            
            // Verificar status no gateway
            $transactionId = $order['payment_transaction_id'];
            $gatewayName = $order['payment_gateway'];
            
            $result = $this->paymentManager->checkTransactionStatus($transactionId, $gatewayName);
            
            if (!($result['success'] ?? false)) {
                $this->jsonResponse(['success' => false, 'message' => 'Erro ao verificar status'], 500);
                return;
            }
            
            // Atualizar status do pedido se necessário
            $currentStatus = $order['payment_status'];
            $newStatus = $result['status'] ?? 'pending';
            
            if ($currentStatus !== $newStatus) {
                $this->updateOrderPaymentStatus($orderId, $newStatus);
            }
            
            // Retornar status atual
            $this->jsonResponse([
                'success' => true,
                'order_id' => $orderId,
                'status' => $newStatus,
                'payment_status' => $order['payment_status'],
                'order_status' => $order['status'],
                'needs_refresh' => ($currentStatus !== $newStatus),
                'redirect_url' => $this->getRedirectUrlForStatus($newStatus, $order['order_number'])
            ]);
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro ao verificar status: " . $e->getMessage());
            
            // Retornar erro
            $this->jsonResponse(['success' => false, 'message' => 'Erro interno'], 500);
        }
    }
    
    /**
     * Processa callbacks/webhooks de gateways de pagamento
     * 
     * @param string $gateway Nome do gateway
     */
    public function webhook($gateway) {
        try {
            // Verificar gateway
            $gateway = SecurityManager::sanitize($gateway);
            
            if (empty($gateway)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Gateway não especificado']);
                exit;
            }
            
            // Obter dados da requisição
            $rawData = file_get_contents('php://input');
            $requestData = json_decode($rawData, true) ?? [];
            
            // Em caso de dados form-encoded
            if (empty($requestData) && !empty($_POST)) {
                $requestData = $_POST;
            }
            
            // Combinar com parâmetros da URL
            $requestData = array_merge($requestData, $_GET);
            
            // Processar webhook
            $result = $this->paymentManager->processWebhook($gateway, $requestData);
            
            // Registrar no log
            error_log("Webhook {$gateway} processado: " . json_encode($result));
            
            // Retornar resposta
            http_response_code(200);
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro ao processar webhook {$gateway}: " . $e->getMessage());
            error_log("Dados recebidos: " . print_r($_REQUEST, true));
            
            // Retornar erro
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro interno']);
            exit;
        }
    }
    
    /**
     * Obtém dados de um pedido
     * 
     * @param int $orderId ID do pedido
     * @param int $userId ID do usuário
     * @return array|false Dados do pedido ou false se não encontrado
     */
    private function getOrder(int $orderId, int $userId) {
        try {
            $sql = "
                SELECT * FROM orders 
                WHERE id = ? AND user_id = ? 
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId, $userId]);
            
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $order ?: false;
        } catch (PDOException $e) {
            error_log("Erro ao obter pedido: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém itens de um pedido
     * 
     * @param int $orderId ID do pedido
     * @return array Lista de itens do pedido
     */
    private function getOrderItems(int $orderId): array {
        try {
            $sql = "
                SELECT * FROM order_items 
                WHERE order_id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao obter itens do pedido: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém dados do cliente
     * 
     * @param int $userId ID do usuário
     * @return array Dados do cliente
     */
    private function getCustomerData(int $userId): array {
        try {
            $sql = "
                SELECT u.id, u.name, u.email, ud.phone, ud.document_type, ud.document_number 
                FROM users u 
                LEFT JOIN user_details ud ON u.id = ud.user_id 
                WHERE u.id = ? 
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                return [
                    'name' => '',
                    'email' => '',
                    'phone' => '',
                    'document' => ''
                ];
            }
            
            // Formatar telefone para padrão de área + número
            $phone = preg_replace('/\D/', '', $customer['phone'] ?? '');
            $phoneAreaCode = '';
            $phoneNumber = '';
            
            if (strlen($phone) >= 10) {
                $phoneAreaCode = substr($phone, 0, 2);
                $phoneNumber = substr($phone, 2);
            }
            
            return [
                'name' => $customer['name'] ?? '',
                'email' => $customer['email'] ?? '',
                'phone' => $phone,
                'phone_area_code' => $phoneAreaCode,
                'phone_number' => $phoneNumber,
                'document_type' => $customer['document_type'] ?? 'CPF',
                'document' => $customer['document_number'] ?? ''
            ];
        } catch (PDOException $e) {
            error_log("Erro ao obter dados do cliente: " . $e->getMessage());
            return [
                'name' => '',
                'email' => '',
                'phone' => '',
                'document' => ''
            ];
        }
    }
    
    /**
     * Obtém endereço de entrega
     * 
     * @param int $addressId ID do endereço
     * @return array Dados do endereço
     */
    private function getShippingAddress(int $addressId): array {
        try {
            $sql = "
                SELECT * FROM user_addresses 
                WHERE id = ? 
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$addressId]);
            
            $address = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$address) {
                return [
                    'address' => '',
                    'number' => '',
                    'complement' => '',
                    'neighborhood' => '',
                    'city' => '',
                    'state' => '',
                    'zipcode' => ''
                ];
            }
            
            return [
                'address' => $address['address'] ?? '',
                'number' => $address['number'] ?? '',
                'complement' => $address['complement'] ?? '',
                'neighborhood' => $address['neighborhood'] ?? '',
                'city' => $address['city'] ?? '',
                'state' => $address['state'] ?? '',
                'zipcode' => $address['zipcode'] ?? ''
            ];
        } catch (PDOException $e) {
            error_log("Erro ao obter endereço de entrega: " . $e->getMessage());
            return [
                'address' => '',
                'number' => '',
                'complement' => '',
                'neighborhood' => '',
                'city' => '',
                'state' => '',
                'zipcode' => ''
            ];
        }
    }
    
    /**
     * Atualiza informações de pagamento de um pedido
     * 
     * @param int $orderId ID do pedido
     * @param array $paymentResult Resultado do pagamento
     * @param string $paymentMethod Método de pagamento
     * @return bool Sucesso da operação
     */
    private function updateOrderPaymentInfo(int $orderId, array $paymentResult, string $paymentMethod): bool {
        try {
            // Sanitizar dados
            $transactionId = SecurityManager::sanitize($paymentResult['transaction_id'] ?? '');
            $status = SecurityManager::sanitize($paymentResult['status'] ?? 'pending');
            $gateway = SecurityManager::sanitize($paymentResult['gateway'] ?? $this->getGatewayFromMethod($paymentMethod));
            
            // Converter detalhes para JSON
            $details = json_encode($paymentResult);
            
            // Mapear status para o formato do pedido
            $orderStatus = $this->getOrderStatusFromPaymentStatus($status);
            
            // Atualizar pedido
            $sql = "
                UPDATE orders 
                SET payment_transaction_id = ?, 
                    payment_status = ?, 
                    payment_gateway = ?, 
                    payment_details = ?,
                    status = ?,
                    updated_at = NOW() 
                WHERE id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$transactionId, $status, $gateway, $details, $orderStatus, $orderId]);
            
            // Registrar histórico de status
            $this->addOrderStatusHistory($orderId, $orderStatus, $status);
            
            return true;
        } catch (PDOException $e) {
            error_log("Erro ao atualizar informações de pagamento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza o status de pagamento de um pedido
     * 
     * @param int $orderId ID do pedido
     * @param string $paymentStatus Novo status de pagamento
     * @return bool Sucesso da operação
     */
    private function updateOrderPaymentStatus(int $orderId, string $paymentStatus): bool {
        try {
            // Sanitizar dados
            $status = SecurityManager::sanitize($paymentStatus);
            
            // Mapear status para o formato do pedido
            $orderStatus = $this->getOrderStatusFromPaymentStatus($status);
            
            // Atualizar pedido
            $sql = "
                UPDATE orders 
                SET payment_status = ?, 
                    status = ?,
                    updated_at = NOW() 
                WHERE id = ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status, $orderStatus, $orderId]);
            
            // Registrar histórico de status
            $this->addOrderStatusHistory($orderId, $orderStatus, $status);
            
            return true;
        } catch (PDOException $e) {
            error_log("Erro ao atualizar status de pagamento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Adiciona entrada no histórico de status do pedido
     * 
     * @param int $orderId ID do pedido
     * @param string $orderStatus Status do pedido
     * @param string $paymentStatus Status do pagamento
     * @return bool Sucesso da operação
     */
    private function addOrderStatusHistory(int $orderId, string $orderStatus, string $paymentStatus): bool {
        try {
            $sql = "
                INSERT INTO order_status_history 
                (order_id, status, payment_status, notes, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ";
            
            $notes = "Atualização automática de status de pagamento para '{$paymentStatus}'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId, $orderStatus, $paymentStatus, $notes]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Erro ao adicionar histórico de status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém o gateway a partir do método de pagamento
     * 
     * @param string $paymentMethod Método de pagamento
     * @return string Nome do gateway
     */
    private function getGatewayFromMethod(string $paymentMethod): string {
        // Mapear métodos para gateways
        $gatewayMap = [
            'credit_card' => 'mercadopago',
            'boleto' => 'mercadopago',
            'pix' => 'mercadopago',
            'paypal' => 'paypal'
        ];
        
        return $gatewayMap[$paymentMethod] ?? 'mercadopago';
    }
    
    /**
     * Mapeia status de pagamento para status de pedido
     * 
     * @param string $paymentStatus Status do pagamento
     * @return string Status do pedido
     */
    private function getOrderStatusFromPaymentStatus(string $paymentStatus): string {
        $statusMap = [
            'pending' => 'pending',
            'in_process' => 'pending',
            'approved' => 'processing',
            'authorized' => 'processing',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'charged_back' => 'disputed',
            'failed' => 'failed',
            'rejected' => 'failed'
        ];
        
        return $statusMap[$paymentStatus] ?? 'pending';
    }
    
    /**
     * Obtém URL de redirecionamento baseada no status
     * 
     * @param string $status Status do pagamento
     * @param string $orderNumber Número do pedido
     * @return string URL para redirecionamento
     */
    private function getRedirectUrlForStatus(string $status, string $orderNumber): string {
        switch ($status) {
            case 'approved':
            case 'authorized':
                return BASE_URL . 'pedido/sucesso/' . $orderNumber;
                
            case 'failed':
            case 'rejected':
                return BASE_URL . 'pedido/falha/' . $orderNumber;
                
            case 'cancelled':
                return BASE_URL . 'pedido/cancelado/' . $orderNumber;
                
            case 'refunded':
                return BASE_URL . 'pedido/reembolsado/' . $orderNumber;
                
            default:
                return '';
        }
    }
    
    /**
     * Obtém IP do cliente com suporte a proxies
     * 
     * @return string IP do cliente
     */
    private function getClientIp(): string {
        $ipAddress = '';
        
        // Verificar IP encaminhado por proxy
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddresses = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ipAddress = trim($ipAddresses[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        
        // Sanitizar e validar
        $ipAddress = filter_var($ipAddress, FILTER_VALIDATE_IP) ?: 'Unknown';
        
        return $ipAddress;
    }
    
    /**
     * Envia resposta em formato JSON
     * 
     * @param array $data Dados a serem enviados
     * @param int $statusCode Código HTTP de status
     */
    private function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
