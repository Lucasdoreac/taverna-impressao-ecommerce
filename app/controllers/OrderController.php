<?php
/**
 * OrderController - Controlador para gerenciamento de pedidos
 * 
 * @version     1.2.0
 * @author      Taverna da Impressão
 */

use App\Lib\Security\SecurityManager;
use App\Lib\Security\CsrfProtection;
use App\Lib\Validation\InputValidationTrait;
use App\Lib\Payment\PaymentManager;

class OrderController {
    // Implementação do trait de validação de entrada
    use InputValidationTrait;
    
    private $orderModel;
    private $userModel;
    private $paymentManager;
    
    /**
     * Construtor
     */
    public function __construct() {
        // Verificar se usuário está logado para certas ações
        if (in_array($_GET['action'] ?? '', ['details', 'list']) && 
            (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            $_SESSION['info'] = 'É necessário fazer login para acessar essa área.';
            header('Location: ' . BASE_URL . 'login');
            return;
        }
        
        // Carregar models
        $this->orderModel = new OrderModel();
        $this->userModel = new UserModel();
        
        // Carregar PaymentManager quando necessário
        if (in_array($_GET['action'] ?? '', ['paymentMethod', 'success', 'pending', 'failure', 'cancelled', 'refunded'])) {
            require_once APP_PATH . '/lib/Payment/PaymentManager.php';
            $this->paymentManager = PaymentManager::getInstance();
        }
        
        // Carregar bibliotecas de segurança
        require_once APP_PATH . '/lib/Security/SecurityManager.php';
        require_once APP_PATH . '/lib/Security/Validator.php';
        require_once APP_PATH . '/lib/Security/CsrfProtection.php';
        require_once APP_PATH . '/lib/Security/InputValidationTrait.php';
    }
    
    /**
     * Página de sucesso após finalização de pedido
     * 
     * @param string $orderNumber Número do pedido
     */
    public function success($orderNumber) {
        try {
            // Validar e sanitizar parâmetro
            $orderNumber = SecurityManager::sanitize($orderNumber);
            
            if (empty($orderNumber)) {
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Obter pedido pelo número
            $order = $this->orderModel->getByOrderNumber($orderNumber);
            
            if (!$order) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Verificar se o usuário tem permissão para ver este pedido
            if (isset($_SESSION['user']['id']) && $order['user_id'] != $_SESSION['user']['id']) {
                $_SESSION['error'] = 'Você não tem permissão para acessar este pedido.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Obter itens do pedido
            $items = $this->orderModel->getOrderItems($order['id']);
            
            // Verificar se o pedido possui itens impressos sob encomenda
            $hasCustomItems = false;
            foreach ($items as $item) {
                if (!$item['is_stock_item']) {
                    $hasCustomItems = true;
                    break;
                }
            }
            
            // Verificar status do pagamento
            $paymentStatus = $order['payment_status'];
            
            // Atualizar status do pagamento antes de exibir a página
            if ($order['payment_transaction_id'] && $paymentStatus != 'approved') {
                try {
                    $transactionId = $order['payment_transaction_id'];
                    $gatewayName = $order['payment_gateway'];
                    
                    if ($transactionId && $gatewayName) {
                        $result = $this->paymentManager->checkTransactionStatus($transactionId, $gatewayName);
                        
                        if (($result['success'] ?? false) && ($result['status'] ?? '') == 'approved') {
                            // Atualizar status do pedido
                            $this->orderModel->updatePaymentStatus($order['id'], 'approved');
                            $paymentStatus = 'approved';
                        }
                    }
                } catch (Exception $e) {
                    // Apenas registrar o erro, mas não interromper o fluxo
                    error_log("Erro ao verificar status de pagamento: " . $e->getMessage());
                }
            }
            
            // Verificar se podemos mostrar a página de sucesso
            if (!in_array($paymentStatus, ['approved', 'authorized'])) {
                // Se o pagamento não foi aprovado, redirecionar para o status apropriado
                if ($paymentStatus == 'pending') {
                    header('Location: ' . BASE_URL . 'pedido/pendente/' . $orderNumber);
                } else if ($paymentStatus == 'failed' || $paymentStatus == 'rejected') {
                    header('Location: ' . BASE_URL . 'pedido/falha/' . $orderNumber);
                } else if ($paymentStatus == 'cancelled') {
                    header('Location: ' . BASE_URL . 'pedido/cancelado/' . $orderNumber);
                } else if ($paymentStatus == 'refunded') {
                    header('Location: ' . BASE_URL . 'pedido/reembolsado/' . $orderNumber);
                } else {
                    header('Location: ' . BASE_URL . 'pedido/pendente/' . $orderNumber);
                }
                return;
            }
            
            // Preparar dados para a view
            $pageTitle = 'Pedido Realizado com Sucesso';
            $estimatedDeliveryDate = null;
            
            // Calcular tempo de impressão estimado para produtos sob encomenda
            $totalPrintTimeHours = 0;
            
            foreach ($items as $item) {
                if (!$item['is_stock_item'] && $item['print_time_hours'] > 0) {
                    $totalPrintTimeHours += $item['print_time_hours'];
                }
            }
            
            // Estimar dias de entrega para produtos sob encomenda
            if ($hasCustomItems && $totalPrintTimeHours > 0) {
                // Estimar dias de impressão (considerando 8 horas de impressão por dia)
                $estimatedPrintingDays = ceil($totalPrintTimeHours / 8);
                
                // Adicionar 1 dia para preparação (validação do pedido)
                // Adicionar dias de impressão
                // Adicionar 1 dia para acabamento e embalagem
                $totalProcessingDays = 1 + $estimatedPrintingDays + 1;
                
                // Calcular data estimada (dias úteis)
                $estimatedDeliveryDate = $this->calculateBusinessDays(date('Y-m-d'), $totalProcessingDays);
            }
            
            // Carregar view
            require_once VIEWS_PATH . '/order/success.php';
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro na página de sucesso: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao exibir as informações do pedido.';
            header('Location: ' . BASE_URL . 'minha-conta/pedidos');
            return;
        }
    }
    
    /**
     * Página de pedido pendente
     * 
     * @param string $orderNumber Número do pedido
     */
    public function pending($orderNumber) {
        try {
            // Validar e sanitizar parâmetro
            $orderNumber = SecurityManager::sanitize($orderNumber);
            
            if (empty($orderNumber)) {
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Obter pedido pelo número
            $order = $this->orderModel->getByOrderNumber($orderNumber);
            
            if (!$order) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Verificar se o usuário tem permissão para ver este pedido
            if (isset($_SESSION['user']['id']) && $order['user_id'] != $_SESSION['user']['id']) {
                $_SESSION['error'] = 'Você não tem permissão para acessar este pedido.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Obter itens do pedido
            $items = $this->orderModel->getOrderItems($order['id']);
            
            // Verificar status do pagamento
            $paymentStatus = $order['payment_status'];
            
            // Atualizar status do pagamento antes de exibir a página
            if ($order['payment_transaction_id']) {
                try {
                    $transactionId = $order['payment_transaction_id'];
                    $gatewayName = $order['payment_gateway'];
                    
                    if ($transactionId && $gatewayName) {
                        $result = $this->paymentManager->checkTransactionStatus($transactionId, $gatewayName);
                        
                        if (($result['success'] ?? false)) {
                            // Atualizar status do pedido se estiver diferente
                            $newStatus = $result['status'] ?? 'pending';
                            
                            if ($paymentStatus != $newStatus) {
                                $this->orderModel->updatePaymentStatus($order['id'], $newStatus);
                                $paymentStatus = $newStatus;
                                
                                // Redirecionar para a página apropriada se o status mudou
                                if ($newStatus == 'approved' || $newStatus == 'authorized') {
                                    header('Location: ' . BASE_URL . 'pedido/sucesso/' . $orderNumber);
                                    return;
                                } else if ($newStatus == 'failed' || $newStatus == 'rejected') {
                                    header('Location: ' . BASE_URL . 'pedido/falha/' . $orderNumber);
                                    return;
                                } else if ($newStatus == 'cancelled') {
                                    header('Location: ' . BASE_URL . 'pedido/cancelado/' . $orderNumber);
                                    return;
                                } else if ($newStatus == 'refunded') {
                                    header('Location: ' . BASE_URL . 'pedido/reembolsado/' . $orderNumber);
                                    return;
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Apenas registrar o erro, mas não interromper o fluxo
                    error_log("Erro ao verificar status de pagamento: " . $e->getMessage());
                }
            }
            
            // Verificar se o método de pagamento requer redirecionamento para página específica
            $paymentMethod = $order['payment_method'];
            
            if ($paymentMethod == 'pix' && $paymentStatus == 'pending') {
                header('Location: ' . BASE_URL . 'pagamento/pix/' . $order['id']);
                return;
            } else if ($paymentMethod == 'boleto' && $paymentStatus == 'pending') {
                header('Location: ' . BASE_URL . 'pagamento/boleto/' . $order['id']);
                return;
            }
            
            // Preparar dados para a view
            $pageTitle = 'Pagamento Pendente';
            
            // Obter detalhes do pagamento
            $paymentDetails = [];
            if (!empty($order['payment_details'])) {
                $paymentDetails = json_decode($order['payment_details'], true) ?? [];
            }
            
            // Carregar view
            require_once VIEWS_PATH . '/order/pending.php';
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro na página de pedido pendente: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao exibir as informações do pedido.';
            header('Location: ' . BASE_URL . 'minha-conta/pedidos');
            return;
        }
    }
    
    /**
     * Página de falha no pagamento
     * 
     * @param string $orderNumber Número do pedido
     */
    public function failure($orderNumber) {
        try {
            // Validar e sanitizar parâmetro
            $orderNumber = SecurityManager::sanitize($orderNumber);
            
            if (empty($orderNumber)) {
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Obter pedido pelo número
            $order = $this->orderModel->getByOrderNumber($orderNumber);
            
            if (!$order) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Verificar se o usuário tem permissão para ver este pedido
            if (isset($_SESSION['user']['id']) && $order['user_id'] != $_SESSION['user']['id']) {
                $_SESSION['error'] = 'Você não tem permissão para acessar este pedido.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Verificar status do pagamento
            $paymentStatus = $order['payment_status'];
            
            // Atualizar status do pagamento antes de exibir a página
            if ($order['payment_transaction_id']) {
                try {
                    $transactionId = $order['payment_transaction_id'];
                    $gatewayName = $order['payment_gateway'];
                    
                    if ($transactionId && $gatewayName) {
                        $result = $this->paymentManager->checkTransactionStatus($transactionId, $gatewayName);
                        
                        if (($result['success'] ?? false)) {
                            // Atualizar status do pedido se estiver diferente
                            $newStatus = $result['status'] ?? 'failed';
                            
                            if ($paymentStatus != $newStatus) {
                                $this->orderModel->updatePaymentStatus($order['id'], $newStatus);
                                $paymentStatus = $newStatus;
                                
                                // Redirecionar para a página apropriada se o status mudou
                                if ($newStatus == 'approved' || $newStatus == 'authorized') {
                                    header('Location: ' . BASE_URL . 'pedido/sucesso/' . $orderNumber);
                                    return;
                                } else if ($newStatus == 'pending') {
                                    header('Location: ' . BASE_URL . 'pedido/pendente/' . $orderNumber);
                                    return;
                                } else if ($newStatus == 'cancelled') {
                                    header('Location: ' . BASE_URL . 'pedido/cancelado/' . $orderNumber);
                                    return;
                                } else if ($newStatus == 'refunded') {
                                    header('Location: ' . BASE_URL . 'pedido/reembolsado/' . $orderNumber);
                                    return;
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Apenas registrar o erro, mas não interromper o fluxo
                    error_log("Erro ao verificar status de pagamento: " . $e->getMessage());
                }
            }
            
            // Preparar dados para a view
            $pageTitle = 'Falha no Pagamento';
            $errorDetails = '';
            
            // Obter detalhes do erro de pagamento
            if (!empty($order['payment_details'])) {
                $paymentDetails = json_decode($order['payment_details'], true) ?? [];
                $errorDetails = $paymentDetails['error_message'] ?? $paymentDetails['status_detail'] ?? '';
            }
            
            // Carregar view
            require_once VIEWS_PATH . '/order/failure.php';
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro na página de falha: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao exibir as informações do pedido.';
            header('Location: ' . BASE_URL . 'minha-conta/pedidos');
            return;
        }
    }
    
    /**
     * Página de pedido cancelado
     * 
     * @param string $orderNumber Número do pedido
     */
    public function cancelled($orderNumber) {
        try {
            // Validar e sanitizar parâmetro
            $orderNumber = SecurityManager::sanitize($orderNumber);
            
            if (empty($orderNumber)) {
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Obter pedido pelo número
            $order = $this->orderModel->getByOrderNumber($orderNumber);
            
            if (!$order) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Verificar se o usuário tem permissão para ver este pedido
            if (isset($_SESSION['user']['id']) && $order['user_id'] != $_SESSION['user']['id']) {
                $_SESSION['error'] = 'Você não tem permissão para acessar este pedido.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Verificar status do pagamento
            $paymentStatus = $order['payment_status'];
            
            // Atualizar status do pagamento antes de exibir a página
            if ($order['payment_transaction_id']) {
                try {
                    $transactionId = $order['payment_transaction_id'];
                    $gatewayName = $order['payment_gateway'];
                    
                    if ($transactionId && $gatewayName) {
                        $result = $this->paymentManager->checkTransactionStatus($transactionId, $gatewayName);
                        
                        if (($result['success'] ?? false)) {
                            // Atualizar status do pedido se estiver diferente
                            $newStatus = $result['status'] ?? 'cancelled';
                            
                            if ($paymentStatus != $newStatus) {
                                $this->orderModel->updatePaymentStatus($order['id'], $newStatus);
                                $paymentStatus = $newStatus;
                                
                                // Redirecionar para a página apropriada se o status mudou
                                if ($newStatus == 'approved' || $newStatus == 'authorized') {
                                    header('Location: ' . BASE_URL . 'pedido/sucesso/' . $orderNumber);
                                    return;
                                } else if ($newStatus == 'pending') {
                                    header('Location: ' . BASE_URL . 'pedido/pendente/' . $orderNumber);
                                    return;
                                } else if ($newStatus == 'failed' || $newStatus == 'rejected') {
                                    header('Location: ' . BASE_URL . 'pedido/falha/' . $orderNumber);
                                    return;
                                } else if ($newStatus == 'refunded') {
                                    header('Location: ' . BASE_URL . 'pedido/reembolsado/' . $orderNumber);
                                    return;
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Apenas registrar o erro, mas não interromper o fluxo
                    error_log("Erro ao verificar status de pagamento: " . $e->getMessage());
                }
            }
            
            // Preparar dados para a view
            $pageTitle = 'Pedido Cancelado';
            $cancellationReason = '';
            
            // Obter detalhes do cancelamento
            if (!empty($order['payment_details'])) {
                $paymentDetails = json_decode($order['payment_details'], true) ?? [];
                $cancellationReason = $paymentDetails['cancellation_reason'] ?? '';
            }
            
            // Carregar view
            require_once VIEWS_PATH . '/order/cancelled.php';
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro na página de cancelamento: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao exibir as informações do pedido.';
            header('Location: ' . BASE_URL . 'minha-conta/pedidos');
            return;
        }
    }
    
    /**
     * Página de pedido reembolsado
     * 
     * @param string $orderNumber Número do pedido
     */
    public function refunded($orderNumber) {
        try {
            // Validar e sanitizar parâmetro
            $orderNumber = SecurityManager::sanitize($orderNumber);
            
            if (empty($orderNumber)) {
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Obter pedido pelo número
            $order = $this->orderModel->getByOrderNumber($orderNumber);
            
            if (!$order) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Verificar se o usuário tem permissão para ver este pedido
            if (isset($_SESSION['user']['id']) && $order['user_id'] != $_SESSION['user']['id']) {
                $_SESSION['error'] = 'Você não tem permissão para acessar este pedido.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Verificar status do pagamento
            $paymentStatus = $order['payment_status'];
            
            // Atualizar status do pagamento antes de exibir a página
            if ($order['payment_transaction_id']) {
                try {
                    $transactionId = $order['payment_transaction_id'];
                    $gatewayName = $order['payment_gateway'];
                    
                    if ($transactionId && $gatewayName) {
                        $result = $this->paymentManager->checkTransactionStatus($transactionId, $gatewayName);
                        
                        if (($result['success'] ?? false)) {
                            // Atualizar status do pedido se estiver diferente
                            $newStatus = $result['status'] ?? 'refunded';
                            
                            if ($paymentStatus != $newStatus) {
                                $this->orderModel->updatePaymentStatus($order['id'], $newStatus);
                                $paymentStatus = $newStatus;
                                
                                // Redirecionar para a página apropriada se o status mudou
                                if ($newStatus == 'approved' || $newStatus == 'authorized') {
                                    header('Location: ' . BASE_URL . 'pedido/sucesso/' . $orderNumber);
                                    return;
                                } else if ($newStatus == 'pending') {
                                    header('Location: ' . BASE_URL . 'pedido/pendente/' . $orderNumber);
                                    return;
                                } else if ($newStatus == 'failed' || $newStatus == 'rejected') {
                                    header('Location: ' . BASE_URL . 'pedido/falha/' . $orderNumber);
                                    return;
                                } else if ($newStatus == 'cancelled') {
                                    header('Location: ' . BASE_URL . 'pedido/cancelado/' . $orderNumber);
                                    return;
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Apenas registrar o erro, mas não interromper o fluxo
                    error_log("Erro ao verificar status de pagamento: " . $e->getMessage());
                }
            }
            
            // Preparar dados para a view
            $pageTitle = 'Pedido Reembolsado';
            $refundReason = '';
            $refundAmount = 0;
            
            // Obter detalhes do reembolso
            if (!empty($order['payment_details'])) {
                $paymentDetails = json_decode($order['payment_details'], true) ?? [];
                $refundReason = $paymentDetails['refund_reason'] ?? '';
                $refundAmount = $paymentDetails['refund_amount'] ?? $order['total'];
            }
            
            // Carregar view
            require_once VIEWS_PATH . '/order/refunded.php';
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro na página de reembolso: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao exibir as informações do pedido.';
            header('Location: ' . BASE_URL . 'minha-conta/pedidos');
            return;
        }
    }
    
    /**
     * Página de seleção de método de pagamento
     * 
     * @param int $orderId ID do pedido a ser pago
     */
    public function paymentMethod($orderId) {
        try {
            // Verificar se o usuário está logado
            if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
                $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
                $_SESSION['info'] = 'É necessário fazer login para acessar essa área.';
                header('Location: ' . BASE_URL . 'login');
                return;
            }
            
            // Validar parâmetros
            $orderId = (int)$orderId;
            
            if ($orderId <= 0) {
                $_SESSION['error'] = 'Pedido inválido.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Verificar se pedido existe e pertence ao usuário logado
            $userID = (int)$_SESSION['user']['id'];
            $order = $this->orderModel->find($orderId);
            
            if (!$order || $order['user_id'] != $userID) {
                $_SESSION['error'] = 'Pedido não encontrado ou você não tem permissão para acessá-lo.';
                header('Location: ' . BASE_URL . 'minha-conta/pedidos');
                return;
            }
            
            // Verificar se pedido está apto para pagamento
            if (!in_array($order['payment_status'], ['pending', 'failed'])) {
                $_SESSION['error'] = 'Este pedido não está disponível para pagamento.';
                header('Location: ' . BASE_URL . 'minha-conta/pedido/' . $orderId);
                return;
            }
            
            // Obter itens do pedido
            $items = $this->orderModel->getOrderItems($orderId);
            
            // Obter métodos de pagamento disponíveis
            $paymentMethods = $this->paymentManager->listPaymentMethods();
            
            // Verificar se há transação anterior para este pedido
            $hasExistingTransaction = !empty($order['payment_transaction_id']);
            
            // Se já existe transação para pix ou boleto e ainda está pendente, redirecionar
            if ($hasExistingTransaction && $order['payment_status'] == 'pending') {
                if ($order['payment_method'] == 'pix') {
                    header('Location: ' . BASE_URL . 'pagamento/pix/' . $orderId);
                    return;
                } else if ($order['payment_method'] == 'boleto') {
                    header('Location: ' . BASE_URL . 'pagamento/boleto/' . $orderId);
                    return;
                }
            }
            
            // Preparar variáveis para a view
            $pageTitle = 'Escolha o Método de Pagamento';
            $orderNumber = $order['order_number'];
            $total = $order['total'];
            $currencySymbol = 'R$';
            
            // Gerar token CSRF para o formulário
            $csrf_token = CsrfProtection::getToken();
            
            // Carregar view
            require_once VIEWS_PATH . '/order/payment_method.php';
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro na página de método de pagamento: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao exibir as opções de pagamento: ' . SecurityManager::sanitize($e->getMessage());
            header('Location: ' . BASE_URL . 'minha-conta/pedidos');
            return;
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
}
