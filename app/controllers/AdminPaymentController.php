<?php
/**
 * AdminPaymentController - Controlador administrativo para gerenciamento de pagamentos
 * 
 * Permite aos administradores configurar gateways, visualizar transações, 
 * processar reembolsos e cancelamentos, além de monitorar webhooks.
 * 
 * @package     App\Controllers\Admin
 * @version     1.0.0
 * @author      Taverna da Impressão
 */

use App\Lib\Security\SecurityManager;
use App\Lib\Security\CsrfProtection;
use App\Lib\Validation\InputValidationTrait;
use App\Lib\Payment\PaymentManager;

class AdminPaymentController {
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
        // Verificar se usuário é administrador
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in'] || 
            !isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
            $_SESSION['error'] = 'Acesso restrito a administradores.';
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        
        // Inicializar componentes
        $this->db = \Database::getInstance();
        
        // Carregar PaymentManager
        require_once APP_PATH . '/lib/Payment/PaymentManager.php';
        $this->paymentManager = PaymentManager::getInstance();
        
        // Carregar dependências de segurança
        require_once APP_PATH . '/lib/Security/SecurityManager.php';
        require_once APP_PATH . '/lib/Security/CsrfProtection.php';
    }
    
    /**
     * Exibe o dashboard de pagamentos
     */
    public function index() {
        try {
            // Obter estatísticas
            $stats = $this->getPaymentStats();
            
            // Obter transações recentes
            $recentTransactions = $this->getRecentTransactions(10);
            
            // Obter webhooks recentes
            $recentWebhooks = $this->getRecentWebhooks(5);
            
            // Gerar token CSRF
            $csrf_token = CsrfProtection::getToken();
            
            // Variáveis para a view
            $pageTitle = 'Gerenciamento de Pagamentos';
            $activeMenu = 'pagamentos';
            
            // Carregar view
            require_once VIEWS_PATH . '/admin/payment/dashboard.php';
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro no dashboard de pagamentos: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao carregar o dashboard de pagamentos: ' . SecurityManager::sanitize($e->getMessage());
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
    }
    
    /**
     * Exibe configurações dos gateways de pagamento
     */
    public function settings() {
        try {
            // Obter métodos de pagamento configurados
            $paymentMethods = $this->getPaymentMethods();
            
            // Obter gateways disponíveis
            $gateways = $this->paymentManager->listAvailableGateways(false);
            
            // Verificar se há mensagens de sucesso ou erro
            $success = $_SESSION['success'] ?? null;
            $error = $_SESSION['error'] ?? null;
            
            // Limpar mensagens da sessão
            unset($_SESSION['success'], $_SESSION['error']);
            
            // Gerar token CSRF
            $csrf_token = CsrfProtection::getToken();
            
            // Variáveis para a view
            $pageTitle = 'Configurações de Pagamento';
            $activeMenu = 'pagamentos';
            $activeSubMenu = 'configuracoes';
            
            // Carregar view
            require_once VIEWS_PATH . '/admin/payment/settings.php';
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro nas configurações de pagamento: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao carregar as configurações de pagamento: ' . SecurityManager::sanitize($e->getMessage());
            header('Location: ' . BASE_URL . 'admin/pagamentos');
            exit;
        }
    }
    
    /**
     * Salva configurações de pagamento
     */
    public function saveSettings() {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . 'admin/pagamentos/configuracoes');
                exit;
            }
            
            // Verificar token CSRF
            if (!CsrfProtection::validateRequest()) {
                $_SESSION['error'] = 'Erro de segurança: Token inválido. Por favor, tente novamente.';
                header('Location: ' . BASE_URL . 'admin/pagamentos/configuracoes');
                exit;
            }
            
            // Validar dados do formulário
            $mode = $this->postValidatedParam('mode', 'string', ['required' => true]);
            
            if ($mode === 'payment_methods') {
                // Salvar métodos de pagamento
                $methods = $this->postValidatedParam('methods', 'array', ['required' => true]);
                $this->savePaymentMethods($methods);
                
                $_SESSION['success'] = 'Métodos de pagamento atualizados com sucesso.';
            } elseif ($mode === 'gateway_config') {
                // Salvar configurações de gateway
                $gateway = $this->postValidatedParam('gateway', 'string', ['required' => true]);
                $config = $this->postValidatedParam('config', 'array', ['required' => true]);
                
                $this->saveGatewayConfig($gateway, $config);
                
                $_SESSION['success'] = 'Configurações do gateway atualizadas com sucesso.';
            } else {
                throw new Exception("Modo de atualização inválido.");
            }
            
            // Redirecionar de volta para a página de configurações
            header('Location: ' . BASE_URL . 'admin/pagamentos/configuracoes');
            exit;
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro ao salvar configurações de pagamento: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao salvar as configurações: ' . SecurityManager::sanitize($e->getMessage());
            header('Location: ' . BASE_URL . 'admin/pagamentos/configuracoes');
            exit;
        }
    }
    
    /**
     * Exibe lista de transações
     */
    public function transacoes() {
        try {
            // Obter parâmetros de filtro
            $gateway = $this->getValidatedParam('gateway', 'string', ['required' => false]);
            $method = $this->getValidatedParam('method', 'string', ['required' => false]);
            $status = $this->getValidatedParam('status', 'string', ['required' => false]);
            $startDate = $this->getValidatedParam('start_date', 'string', ['required' => false]);
            $endDate = $this->getValidatedParam('end_date', 'string', ['required' => false]);
            $orderNumber = $this->getValidatedParam('order_number', 'string', ['required' => false]);
            $page = max(1, (int)$this->getValidatedParam('page', 'int', ['required' => false, 'default' => 1]));
            $limit = 20;
            
            // Obter transações
            $transactions = $this->getTransactions($gateway, $method, $status, $startDate, $endDate, $orderNumber, $page, $limit);
            
            // Obter total de registros para paginação
            $totalTransactions = $this->countTransactions($gateway, $method, $status, $startDate, $endDate, $orderNumber);
            $totalPages = ceil($totalTransactions / $limit);
            
            // Obter gateways disponíveis para filtro
            $availableGateways = $this->getUniqueGateways();
            
            // Obter métodos de pagamento disponíveis para filtro
            $availablePaymentMethods = $this->getUniquePaymentMethods();
            
            // Obter status disponíveis para filtro
            $availableStatuses = $this->getUniqueStatuses();
            
            // Variáveis para a view
            $pageTitle = 'Transações de Pagamento';
            $activeMenu = 'pagamentos';
            $activeSubMenu = 'transacoes';
            
            // Gerar token CSRF
            $csrf_token = CsrfProtection::getToken();
            
            // Carregar view
            require_once VIEWS_PATH . '/admin/payment/transacoes.php';
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro na lista de transações: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao carregar a lista de transações: ' . SecurityManager::sanitize($e->getMessage());
            header('Location: ' . BASE_URL . 'admin/pagamentos');
            exit;
        }
    }
    
    /**
     * Obter transações com filtros
     * 
     * @param string|null $gateway Filtro por gateway
     * @param string|null $method Filtro por método de pagamento
     * @param string|null $status Filtro por status
     * @param string|null $startDate Data inicial
     * @param string|null $endDate Data final
     * @param string|null $orderNumber Número do pedido
     * @param int $page Página atual
     * @param int $limit Limite de registros por página
     * @return array Lista de transações
     */
    private function getTransactions(
        $gateway = null, 
        $method = null, 
        $status = null, 
        $startDate = null, 
        $endDate = null, 
        $orderNumber = null, 
        $page = 1, 
        $limit = 20
    ) {
        try {
            $params = [];
            $conditions = [];
            
            if ($gateway) {
                $conditions[] = "t.gateway_name = ?";
                $params[] = SecurityManager::sanitize($gateway);
            }
            
            if ($method) {
                $conditions[] = "t.payment_method = ?";
                $params[] = SecurityManager::sanitize($method);
            }
            
            if ($status) {
                $conditions[] = "t.status = ?";
                $params[] = SecurityManager::sanitize($status);
            }
            
            if ($startDate) {
                $conditions[] = "t.created_at >= ?";
                $params[] = SecurityManager::sanitize($startDate) . ' 00:00:00';
            }
            
            if ($endDate) {
                $conditions[] = "t.created_at <= ?";
                $params[] = SecurityManager::sanitize($endDate) . ' 23:59:59';
            }
            
            if ($orderNumber) {
                $conditions[] = "o.order_number LIKE ?";
                $params[] = '%' . SecurityManager::sanitize($orderNumber) . '%';
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $offset = ($page - 1) * $limit;
            $params[] = $limit;
            $params[] = $offset;
            
            $sql = "
                SELECT t.*, o.order_number, u.name as customer_name
                FROM payment_transactions t
                LEFT JOIN orders o ON t.order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                {$whereClause}
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            return $this->db->select($sql, $params);
        } catch (PDOException $e) {
            error_log("Erro ao obter transações: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Contar total de transações com filtros
     * 
     * @param string|null $gateway Filtro por gateway
     * @param string|null $method Filtro por método de pagamento
     * @param string|null $status Filtro por status
     * @param string|null $startDate Data inicial
     * @param string|null $endDate Data final
     * @param string|null $orderNumber Número do pedido
     * @return int Total de registros
     */
    private function countTransactions(
        $gateway = null, 
        $method = null, 
        $status = null, 
        $startDate = null, 
        $endDate = null, 
        $orderNumber = null
    ) {
        try {
            $params = [];
            $conditions = [];
            
            if ($gateway) {
                $conditions[] = "t.gateway_name = ?";
                $params[] = SecurityManager::sanitize($gateway);
            }
            
            if ($method) {
                $conditions[] = "t.payment_method = ?";
                $params[] = SecurityManager::sanitize($method);
            }
            
            if ($status) {
                $conditions[] = "t.status = ?";
                $params[] = SecurityManager::sanitize($status);
            }
            
            if ($startDate) {
                $conditions[] = "t.created_at >= ?";
                $params[] = SecurityManager::sanitize($startDate) . ' 00:00:00';
            }
            
            if ($endDate) {
                $conditions[] = "t.created_at <= ?";
                $params[] = SecurityManager::sanitize($endDate) . ' 23:59:59';
            }
            
            if ($orderNumber) {
                $conditions[] = "o.order_number LIKE ?";
                $params[] = '%' . SecurityManager::sanitize($orderNumber) . '%';
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT COUNT(*) as total
                FROM payment_transactions t
                LEFT JOIN orders o ON t.order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                {$whereClause}
            ";
            
            $result = $this->db->select($sql, $params);
            
            return (int)($result[0]['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Erro ao contar transações: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obter lista de métodos de pagamento únicos
     * 
     * @return array Lista de métodos de pagamento
     */
    private function getUniquePaymentMethods() {
        try {
            $sql = "SELECT DISTINCT payment_method FROM payment_transactions ORDER BY payment_method";
            $result = $this->db->select($sql);
            
            $methods = [];
            foreach ($result as $row) {
                $methods[] = $row['payment_method'];
            }
            
            return $methods;
        } catch (PDOException $e) {
            error_log("Erro ao obter métodos de pagamento únicos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter lista de status únicos
     * 
     * @return array Lista de status
     */
    private function getUniqueStatuses() {
        try {
            $sql = "SELECT DISTINCT status FROM payment_transactions ORDER BY status";
            $result = $this->db->select($sql);
            
            $statuses = [];
            foreach ($result as $row) {
                $statuses[] = $row['status'];
            }
            
            return $statuses;
        } catch (PDOException $e) {
            error_log("Erro ao obter status únicos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Exibe lista de gateways configurados
     */
    public function gateways() {
        try {
            // Obter gateways disponíveis
            $gateways = $this->paymentManager->listAvailableGateways(false);
            
            // Variáveis para a view
            $pageTitle = 'Gateways de Pagamento';
            $activeMenu = 'pagamentos';
            $activeSubMenu = 'gateways';
            
            // Gerar token CSRF
            $csrf_token = CsrfProtection::getToken();
            
            // Carregar view
            require_once VIEWS_PATH . '/admin/payment/gateways.php';
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro na lista de gateways: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao carregar a lista de gateways: ' . SecurityManager::sanitize($e->getMessage());
            header('Location: ' . BASE_URL . 'admin/pagamentos');
            exit;
        }
    }
    
    /**
     * Exibe detalhes de uma transação de pagamento
     * 
     * @param int $id ID da transação no sistema
     */
    public function transaction($id) {
        try {
            // Validar parâmetro
            $id = (int)$id;
            
            if ($id <= 0) {
                $_SESSION['error'] = 'ID de transação inválido.';
                header('Location: ' . BASE_URL . 'admin/pagamentos');
                exit;
            }
            
            // Obter dados da transação
            $transaction = $this->getTransaction($id);
            
            if (!$transaction) {
                $_SESSION['error'] = 'Transação não encontrada.';
                header('Location: ' . BASE_URL . 'admin/pagamentos');
                exit;
            }
            
            // Obter pedido associado
            $order = $this->getOrder($transaction['order_id']);
            
            // Obter itens do pedido
            $orderItems = $this->getOrderItems($transaction['order_id']);
            
            // Obter detalhes do cliente
            $customer = $this->getCustomer($order['user_id']);
            
            // Obter histórico de atualizações da transação
            $transactionHistory = $this->getTransactionHistory($transaction['transaction_id']);
            
            // Obter webhooks relacionados
            $relatedWebhooks = $this->getRelatedWebhooks($transaction['transaction_id']);
            
            // Obter reembolsos
            $refunds = $this->getTransactionRefunds($transaction['transaction_id']);
            
            // Consultar status atual no gateway
            $gatewayStatus = null;
            
            try {
                $gatewayStatus = $this->paymentManager->checkTransactionStatus(
                    $transaction['transaction_id'],
                    $transaction['gateway_name']
                );
            } catch (Exception $e) {
                // Apenas registrar o erro, mas não interromper o fluxo
                error_log("Erro ao verificar status no gateway: " . $e->getMessage());
            }
            
            // Gerar token CSRF
            $csrf_token = CsrfProtection::getToken();
            
            // Variáveis para a view
            $pageTitle = 'Detalhes da Transação #' . $id;
            $activeMenu = 'pagamentos';
            
            // Carregar view
            require_once VIEWS_PATH . '/admin/payment/transaction_details.php';
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro nos detalhes da transação: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao carregar os detalhes da transação: ' . SecurityManager::sanitize($e->getMessage());
            header('Location: ' . BASE_URL . 'admin/pagamentos');
            exit;
        }
    }
    
    /**
     * Exibe detalhes de um pagamento para pedido específico
     * 
     * @param int $id ID do pedido
     */
    public function details($id) {
        try {
            // Validar parâmetro
            $id = (int)$id;
            
            if ($id <= 0) {
                $_SESSION['error'] = 'ID de pedido inválido.';
                header('Location: ' . BASE_URL . 'admin/pagamentos');
                exit;
            }
            
            // Obter pedido
            $order = $this->getOrder($id);
            
            if (!$order) {
                $_SESSION['error'] = 'Pedido não encontrado.';
                header('Location: ' . BASE_URL . 'admin/pagamentos');
                exit;
            }
            
            // Obter itens do pedido
            $orderItems = $this->getOrderItems($id);
            
            // Obter detalhes do cliente
            $customer = $this->getCustomer($order['user_id']);
            
            // Obter transações de pagamento para o pedido
            $transactions = $this->getOrderTransactions($id);
            
            // Obter tentativas de pagamento
            $attempts = $this->getPaymentAttempts($id);
            
            // Obter histórico de status do pedido
            $statusHistory = $this->getOrderStatusHistory($id);
            
            // Gerar token CSRF
            $csrf_token = CsrfProtection::getToken();
            
            // Variáveis para a view
            $pageTitle = 'Detalhes de Pagamento - Pedido #' . $order['order_number'];
            $activeMenu = 'pagamentos';
            
            // Carregar view
            require_once VIEWS_PATH . '/admin/payment/order_payment_details.php';
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro nos detalhes do pagamento: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao carregar os detalhes do pagamento: ' . SecurityManager::sanitize($e->getMessage());
            header('Location: ' . BASE_URL . 'admin/pagamentos');
            exit;
        }
    }
    
    /**
     * Processa o reembolso de uma transação
     * 
     * @param int $id ID da transação no sistema
     */
    public function refund($id) {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . 'admin/pagamentos');
                exit;
            }
            
            // Verificar token CSRF
            if (!CsrfProtection::validateRequest()) {
                $_SESSION['error'] = 'Erro de segurança: Token inválido. Por favor, tente novamente.';
                header('Location: ' . BASE_URL . 'admin/pagamentos');
                exit;
            }
            
            // Validar parâmetros
            $id = (int)$id;
            
            if ($id <= 0) {
                $_SESSION['error'] = 'ID de transação inválido.';
                header('Location: ' . BASE_URL . 'admin/pagamentos');
                exit;
            }
            
            // Obter transação
            $transaction = $this->getTransaction($id);
            
            if (!$transaction) {
                $_SESSION['error'] = 'Transação não encontrada.';
                header('Location: ' . BASE_URL . 'admin/pagamentos');
                exit;
            }
            
            // Validar dados do formulário
            $refundAmount = $this->postValidatedParam('refund_amount', 'float', ['required' => false]);
            $refundReason = $this->postValidatedParam('refund_reason', 'string', ['required' => false, 'maxLength' => 500]);
            $refundType = $this->postValidatedParam('refund_type', 'string', ['required' => true]);
            
            // Determinar valor do reembolso
            if ($refundType === 'partial' && ($refundAmount === null || $refundAmount <= 0)) {
                $_SESSION['error'] = 'Para reembolsos parciais, o valor deve ser especificado e maior que zero.';
                header('Location: ' . BASE_URL . 'admin/pagamentos/transacao/' . $id);
                exit;
            }
            
            // Para reembolsos totais, definir amount como null
            $amount = ($refundType === 'partial') ? $refundAmount : null;
            
            // Processar reembolso
            $result = $this->paymentManager->refundTransaction(
                $transaction['transaction_id'],
                $amount,
                $transaction['gateway_name'],
                $refundReason
            );
            
            if (!($result['success'] ?? false)) {
                throw new Exception("Falha ao processar reembolso: " . ($result['error_message'] ?? 'Erro desconhecido'));
            }
            
            // Registrar ação no log
            $this->logAdminAction(
                'refund_transaction',
                "Reembolso de transação {$transaction['transaction_id']} (" . ($amount ? 'parcial' : 'total') . ")",
                [
                    'transaction_id' => $transaction['transaction_id'],
                    'amount' => $amount,
                    'reason' => $refundReason,
                    'refund_id' => $result['refund_id'] ?? null
                ]
            );
            
            // Redirecionar com mensagem de sucesso
            $_SESSION['success'] = 'Reembolso processado com sucesso. ID do reembolso: ' . ($result['refund_id'] ?? 'N/A');
            header('Location: ' . BASE_URL . 'admin/pagamentos/transacao/' . $id);
            exit;
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro ao processar reembolso: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao processar o reembolso: ' . SecurityManager::sanitize($e->getMessage());
            header('Location: ' . BASE_URL . 'admin/pagamentos/transacao/' . $id);
            exit;
        }
    }
    
    /**
     * Testa a conexão com um gateway de pagamento
     */
    public function testGateway() {
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
            if (!$data || !isset($data['gateway'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Dados incompletos'], 400);
                return;
            }
            
            $gateway = SecurityManager::sanitize($data['gateway']);
            
            // Testar gateway conforme o tipo
            if ($gateway === 'mercadopago') {
                if (!isset($data['access_token']) || empty($data['access_token'])) {
                    $this->jsonResponse(['success' => false, 'message' => 'Access Token é obrigatório'], 400);
                    return;
                }
                
                $result = $this->testMercadoPagoConnection($data['access_token'], $data['sandbox'] ?? true);
            } else if ($gateway === 'paypal') {
                if (!isset($data['client_id']) || empty($data['client_id']) || !isset($data['client_secret']) || empty($data['client_secret'])) {
                    $this->jsonResponse(['success' => false, 'message' => 'Client ID e Client Secret são obrigatórios'], 400);
                    return;
                }
                
                $result = $this->testPayPalConnection($data['client_id'], $data['client_secret'], $data['sandbox'] ?? true);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Gateway não suportado'], 400);
                return;
            }
            
            // Retornar resultado do teste
            $this->jsonResponse($result);
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro ao testar gateway: " . $e->getMessage());
            
            // Retornar erro
            $this->jsonResponse([
                'success' => false,
                'message' => 'Erro interno ao testar gateway: ' . SecurityManager::sanitize($e->getMessage())
            ], 500);
        }
    }
    
    /**
     * Testa a conexão com o MercadoPago
     * 
     * @param string $accessToken Token de acesso do MercadoPago
     * @param bool $sandbox Usar ambiente sandbox
     * @return array Resultado do teste
     */
    private function testMercadoPagoConnection($accessToken, $sandbox = true) {
        try {
            // Obter gateway do PaymentManager
            $gateway = $this->paymentManager->getGateway('mercadopago');
            
            // Configurar temporariamente com as credenciais de teste
            $gateway->setCredentials(['access_token' => $accessToken, 'sandbox' => $sandbox]);
            
            // Realizar um teste de conexão (verificação de credenciais)
            $testResult = $gateway->testConnection();
            
            if ($testResult['success']) {
                // Registrar ação no log
                $this->logAdminAction(
                    'test_gateway_connection',
                    "Teste de conexão com MercadoPago realizado com sucesso",
                    ['gateway' => 'mercadopago', 'sandbox' => $sandbox]
                );
                
                return [
                    'success' => true,
                    'message' => 'Conexão com MercadoPago estabelecida com sucesso',
                    'data' => $testResult['data'] ?? null
                ];
            } else {
                // Registrar falha
                $this->logAdminAction(
                    'test_gateway_connection_failed',
                    "Teste de conexão com MercadoPago falhou: " . ($testResult['message'] ?? 'Erro desconhecido'),
                    ['gateway' => 'mercadopago', 'sandbox' => $sandbox, 'error' => $testResult['message'] ?? 'Erro desconhecido']
                );
                
                return [
                    'success' => false,
                    'message' => $testResult['message'] ?? 'Falha ao testar conexão com MercadoPago'
                ];
            }
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro ao testar conexão com MercadoPago: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao testar conexão com MercadoPago: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Testa a conexão com o PayPal
     * 
     * @param string $clientId Client ID do PayPal
     * @param string $clientSecret Client Secret do PayPal
     * @param bool $sandbox Usar ambiente sandbox
     * @return array Resultado do teste
     */
    private function testPayPalConnection($clientId, $clientSecret, $sandbox = true) {
        try {
            // Obter gateway do PaymentManager
            $gateway = $this->paymentManager->getGateway('paypal');
            
            // Configurar temporariamente com as credenciais de teste
            $gateway->setCredentials([
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'sandbox' => $sandbox
            ]);
            
            // Realizar um teste de conexão (verificação de credenciais)
            $testResult = $gateway->testConnection();
            
            if ($testResult['success']) {
                // Registrar ação no log
                $this->logAdminAction(
                    'test_gateway_connection',
                    "Teste de conexão com PayPal realizado com sucesso",
                    ['gateway' => 'paypal', 'sandbox' => $sandbox]
                );
                
                return [
                    'success' => true,
                    'message' => 'Conexão com PayPal estabelecida com sucesso',
                    'data' => $testResult['data'] ?? null
                ];
            } else {
                // Registrar falha
                $this->logAdminAction(
                    'test_gateway_connection_failed',
                    "Teste de conexão com PayPal falhou: " . ($testResult['message'] ?? 'Erro desconhecido'),
                    ['gateway' => 'paypal', 'sandbox' => $sandbox, 'error' => $testResult['message'] ?? 'Erro desconhecido']
                );
                
                return [
                    'success' => false,
                    'message' => $testResult['message'] ?? 'Falha ao testar conexão com PayPal'
                ];
            }
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro ao testar conexão com PayPal: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao testar conexão com PayPal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Processa o cancelamento de uma transação
     * 
     * @param int $id ID da transação no sistema
     */
    public function cancel($id) {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . 'admin/pagamentos');
                exit;
            }
            
            // Verificar token CSRF
            if (!CsrfProtection::validateRequest()) {
                $_SESSION['error'] = 'Erro de segurança: Token inválido. Por favor, tente novamente.';
                header('Location: ' . BASE_URL . 'admin/pagamentos');
                exit;
            }
            
            // Validar parâmetros
            $id = (int)$id;
            
            if ($id <= 0) {
                $_SESSION['error'] = 'ID de transação inválido.';
                header('Location: ' . BASE_URL . 'admin/pagamentos');
                exit;
            }
            
            // Obter transação
            $transaction = $this->getTransaction($id);
            
            if (!$transaction) {
                $_SESSION['error'] = 'Transação não encontrada.';
                header('Location: ' . BASE_URL . 'admin/pagamentos');
                exit;
            }
            
            // Validar dados do formulário
            $cancelReason = $this->postValidatedParam('cancel_reason', 'string', ['required' => false, 'maxLength' => 500]);
            
            // Processar cancelamento
            $result = $this->paymentManager->cancelTransaction(
                $transaction['transaction_id'],
                $transaction['gateway_name'],
                $cancelReason
            );
            
            if (!($result['success'] ?? false)) {
                throw new Exception("Falha ao processar cancelamento: " . ($result['error_message'] ?? 'Erro desconhecido'));
            }
            
            // Registrar ação no log
            $this->logAdminAction(
                'cancel_transaction',
                "Cancelamento de transação {$transaction['transaction_id']}",
                [
                    'transaction_id' => $transaction['transaction_id'],
                    'reason' => $cancelReason
                ]
            );
            
            // Redirecionar com mensagem de sucesso
            $_SESSION['success'] = 'Transação cancelada com sucesso.';
            header('Location: ' . BASE_URL . 'admin/pagamentos/transacao/' . $id);
            exit;
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro ao processar cancelamento: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao cancelar a transação: ' . SecurityManager::sanitize($e->getMessage());
            header('Location: ' . BASE_URL . 'admin/pagamentos/transacao/' . $id);
            exit;
        }
    }
    
    /**
     * Exibe logs de webhooks recebidos
     */
    public function webhooks() {
        try {
            // Obter parâmetros de filtro
            $gateway = $this->getValidatedParam('gateway', 'string', ['required' => false]);
            $startDate = $this->getValidatedParam('start_date', 'string', ['required' => false]);
            $endDate = $this->getValidatedParam('end_date', 'string', ['required' => false]);
            $eventType = $this->getValidatedParam('event_type', 'string', ['required' => false]);
            $status = $this->getValidatedParam('status', 'string', ['required' => false]);
            $page = max(1, (int)$this->getValidatedParam('page', 'int', ['required' => false, 'default' => 1]));
            $limit = 20;
            
            // Obter webhooks
            $webhooks = $this->getWebhooks($gateway, $startDate, $endDate, $eventType, $status, $page, $limit);
            
            // Obter total de registros para paginação
            $totalWebhooks = $this->countWebhooks($gateway, $startDate, $endDate, $eventType, $status);
            $totalPages = ceil($totalWebhooks / $limit);
            
            // Obter gateways disponíveis para filtro
            $availableGateways = $this->getUniqueGateways();
            
            // Obter tipos de eventos disponíveis para filtro
            $availableEventTypes = $this->getUniqueEventTypes();
            
            // Variáveis para a view
            $pageTitle = 'Logs de Webhooks';
            $activeMenu = 'pagamentos';
            $activeSubMenu = 'webhooks';
            
            // Gerar token CSRF
            $csrf_token = CsrfProtection::getToken();
            
            // Carregar view
            require_once VIEWS_PATH . '/admin/payment/webhooks.php';
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro na página de webhooks: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao carregar os logs de webhooks: ' . SecurityManager::sanitize($e->getMessage());
            header('Location: ' . BASE_URL . 'admin/pagamentos');
            exit;
        }
    }
    
    /**
     * Obter estatísticas de pagamentos
     * 
     * @return array Estatísticas de pagamentos
     */
    private function getPaymentStats() {
        try {
            $stats = [
                'total_transactions' => 0,
                'total_approved' => 0,
                'total_pending' => 0,
                'total_failed' => 0,
                'total_amount' => 0,
                'average_amount' => 0,
                'by_gateway' => [],
                'by_payment_method' => [],
                'by_day' => []
            ];
            
            // Total de transações por status
            $sql = "
                SELECT COUNT(*) as total, status, SUM(amount) as total_amount
                FROM payment_transactions
                GROUP BY status
            ";
            
            $result = $this->db->select($sql);
            
            foreach ($result as $row) {
                $status = strtolower($row['status']);
                $total = (int)$row['total'];
                $amount = (float)$row['total_amount'];
                
                $stats['total_transactions'] += $total;
                $stats['total_amount'] += $amount;
                
                if (in_array($status, ['approved', 'authorized'])) {
                    $stats['total_approved'] += $total;
                } elseif (in_array($status, ['pending', 'in_process'])) {
                    $stats['total_pending'] += $total;
                } elseif (in_array($status, ['failed', 'rejected', 'cancelled'])) {
                    $stats['total_failed'] += $total;
                }
            }
            
            // Calcular média
            if ($stats['total_transactions'] > 0) {
                $stats['average_amount'] = $stats['total_amount'] / $stats['total_transactions'];
            }
            
            // Transações por gateway
            $sql = "
                SELECT gateway_name, COUNT(*) as total, SUM(amount) as total_amount
                FROM payment_transactions
                GROUP BY gateway_name
                ORDER BY total DESC
            ";
            
            $result = $this->db->select($sql);
            
            foreach ($result as $row) {
                $stats['by_gateway'][$row['gateway_name']] = [
                    'total' => (int)$row['total'],
                    'amount' => (float)$row['total_amount']
                ];
            }
            
            // Transações por método de pagamento
            $sql = "
                SELECT payment_method, COUNT(*) as total, SUM(amount) as total_amount
                FROM payment_transactions
                GROUP BY payment_method
                ORDER BY total DESC
            ";
            
            $result = $this->db->select($sql);
            
            foreach ($result as $row) {
                $stats['by_payment_method'][$row['payment_method']] = [
                    'total' => (int)$row['total'],
                    'amount' => (float)$row['total_amount']
                ];
            }
            
            // Transações por dia (últimos 30 dias)
            $sql = "
                SELECT DATE(created_at) as date, COUNT(*) as total, SUM(amount) as total_amount
                FROM payment_transactions
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ";
            
            $result = $this->db->select($sql);
            
            foreach ($result as $row) {
                $stats['by_day'][$row['date']] = [
                    'total' => (int)$row['total'],
                    'amount' => (float)$row['total_amount']
                ];
            }
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Erro ao obter estatísticas de pagamentos: " . $e->getMessage());
            return [
                'total_transactions' => 0,
                'total_approved' => 0,
                'total_pending' => 0,
                'total_failed' => 0
            ];
        }
    }
    
    /**
     * Obter transações recentes
     * 
     * @param int $limit Limite de registros
     * @return array Lista de transações
     */
    private function getRecentTransactions($limit = 10) {
        try {
            $sql = "
                SELECT t.*, o.order_number, o.user_id, u.name as customer_name
                FROM payment_transactions t
                LEFT JOIN orders o ON t.order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                ORDER BY t.created_at DESC
                LIMIT ?
            ";
            
            return $this->db->select($sql, [$limit]);
        } catch (PDOException $e) {
            error_log("Erro ao obter transações recentes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter webhooks recentes
     * 
     * @param int $limit Limite de registros
     * @return array Lista de webhooks
     */
    private function getRecentWebhooks($limit = 5) {
        try {
            $sql = "
                SELECT *
                FROM payment_webhooks
                ORDER BY created_at DESC
                LIMIT ?
            ";
            
            return $this->db->select($sql, [$limit]);
        } catch (PDOException $e) {
            error_log("Erro ao obter webhooks recentes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter webhooks com filtros
     * 
     * @param string|null $gateway Filtro por gateway
     * @param string|null $startDate Data inicial
     * @param string|null $endDate Data final
     * @param string|null $eventType Tipo de evento
     * @param string|null $status Status de sucesso
     * @param int $page Página atual
     * @param int $limit Limite de registros por página
     * @return array Lista de webhooks
     */
    private function getWebhooks($gateway = null, $startDate = null, $endDate = null, $eventType = null, $status = null, $page = 1, $limit = 20) {
        try {
            $params = [];
            $conditions = [];
            
            if ($gateway) {
                $conditions[] = "gateway = ?";
                $params[] = SecurityManager::sanitize($gateway);
            }
            
            if ($startDate) {
                $conditions[] = "created_at >= ?";
                $params[] = SecurityManager::sanitize($startDate) . ' 00:00:00';
            }
            
            if ($endDate) {
                $conditions[] = "created_at <= ?";
                $params[] = SecurityManager::sanitize($endDate) . ' 23:59:59';
            }
            
            if ($eventType) {
                $conditions[] = "event_type = ?";
                $params[] = SecurityManager::sanitize($eventType);
            }
            
            if ($status !== null && $status !== '') {
                $conditions[] = "success = ?";
                $params[] = ($status === 'success') ? 1 : 0;
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $offset = ($page - 1) * $limit;
            $params[] = $limit;
            $params[] = $offset;
            
            $sql = "
                SELECT *
                FROM payment_webhooks
                {$whereClause}
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            return $this->db->select($sql, $params);
        } catch (PDOException $e) {
            error_log("Erro ao obter webhooks: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Contar total de webhooks com filtros
     * 
     * @param string|null $gateway Filtro por gateway
     * @param string|null $startDate Data inicial
     * @param string|null $endDate Data final
     * @param string|null $eventType Tipo de evento
     * @param string|null $status Status de sucesso
     * @return int Total de registros
     */
    private function countWebhooks($gateway = null, $startDate = null, $endDate = null, $eventType = null, $status = null) {
        try {
            $params = [];
            $conditions = [];
            
            if ($gateway) {
                $conditions[] = "gateway = ?";
                $params[] = SecurityManager::sanitize($gateway);
            }
            
            if ($startDate) {
                $conditions[] = "created_at >= ?";
                $params[] = SecurityManager::sanitize($startDate) . ' 00:00:00';
            }
            
            if ($endDate) {
                $conditions[] = "created_at <= ?";
                $params[] = SecurityManager::sanitize($endDate) . ' 23:59:59';
            }
            
            if ($eventType) {
                $conditions[] = "event_type = ?";
                $params[] = SecurityManager::sanitize($eventType);
            }
            
            if ($status !== null && $status !== '') {
                $conditions[] = "success = ?";
                $params[] = ($status === 'success') ? 1 : 0;
            }
            
            $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
            
            $sql = "
                SELECT COUNT(*) as total
                FROM payment_webhooks
                {$whereClause}
            ";
            
            $result = $this->db->select($sql, $params);
            
            return (int)($result[0]['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Erro ao contar webhooks: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obter métodos de pagamento configurados
     * 
     * @return array Lista de métodos de pagamento
     */
    private function getPaymentMethods() {
        try {
            $sql = "SELECT setting_value FROM settings WHERE setting_key = 'payment_methods'";
            $result = $this->db->select($sql);
            
            if (!empty($result)) {
                return json_decode($result[0]['setting_value'], true) ?? [];
            }
            
            return [];
        } catch (PDOException $e) {
            error_log("Erro ao obter métodos de pagamento: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Salvar métodos de pagamento
     * 
     * @param array $methods Lista de métodos de pagamento
     * @return bool Sucesso da operação
     */
    private function savePaymentMethods(array $methods) {
        try {
            // Preparar dados sanitizados
            $sanitizedMethods = [];
            
            foreach ($methods as $method) {
                $sanitizedMethod = [
                    'id' => SecurityManager::sanitize($method['id'] ?? ''),
                    'name' => SecurityManager::sanitize($method['name'] ?? ''),
                    'active' => isset($method['active']) && $method['active'] ? true : false,
                    'gateway' => SecurityManager::sanitize($method['gateway'] ?? ''),
                    'icon' => SecurityManager::sanitize($method['icon'] ?? '')
                ];
                
                if (empty($sanitizedMethod['id']) || empty($sanitizedMethod['name']) || empty($sanitizedMethod['gateway'])) {
                    continue;
                }
                
                $sanitizedMethods[] = $sanitizedMethod;
            }
            
            // Salvar na base de dados
            $json = json_encode($sanitizedMethods);
            
            $sql = "
                INSERT INTO settings (setting_key, setting_value, created_at, updated_at)
                VALUES ('payment_methods', ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ";
            
            $this->db->query($sql, [$json, $json]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Erro ao salvar métodos de pagamento: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Salvar configurações de gateway
     * 
     * @param string $gateway Nome do gateway
     * @param array $config Configurações do gateway
     * @return bool Sucesso da operação
     */
    private function saveGatewayConfig(string $gateway, array $config) {
        try {
            // Sanitizar dados
            $gateway = SecurityManager::sanitize($gateway);
            
            // Preparar configurações sanitizadas
            $sanitizedConfig = [];
            
            foreach ($config as $key => $value) {
                $sanitizedKey = SecurityManager::sanitize($key);
                
                if (is_array($value)) {
                    $sanitizedValue = [];
                    
                    foreach ($value as $subKey => $subValue) {
                        if (is_scalar($subValue)) {
                            $sanitizedValue[SecurityManager::sanitize($subKey)] = SecurityManager::sanitize($subValue);
                        }
                    }
                    
                    $sanitizedConfig[$sanitizedKey] = $sanitizedValue;
                } else if (is_scalar($value)) {
                    $sanitizedConfig[$sanitizedKey] = SecurityManager::sanitize($value);
                }
            }
            
            // Converter para JSON
            $json = json_encode($sanitizedConfig);
            
            // Salvar na base de dados
            $settingKey = "payment.{$gateway}.config";
            
            $sql = "
                INSERT INTO settings (setting_key, setting_value, created_at, updated_at)
                VALUES (?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ";
            
            $this->db->query($sql, [$settingKey, $json, $json]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Erro ao salvar configurações de gateway: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter detalhes de uma transação
     * 
     * @param int $id ID da transação
     * @return array|false Dados da transação ou false se não encontrada
     */
    private function getTransaction($id) {
        try {
            $sql = "SELECT * FROM payment_transactions WHERE id = ? LIMIT 1";
            $result = $this->db->select($sql, [(int)$id]);
            
            return !empty($result) ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Erro ao obter transação: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter detalhes de um pedido
     * 
     * @param int $id ID do pedido
     * @return array|false Dados do pedido ou false se não encontrado
     */
    private function getOrder($id) {
        try {
            $sql = "SELECT * FROM orders WHERE id = ? LIMIT 1";
            $result = $this->db->select($sql, [(int)$id]);
            
            return !empty($result) ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Erro ao obter pedido: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter itens de um pedido
     * 
     * @param int $orderId ID do pedido
     * @return array Lista de itens do pedido
     */
    private function getOrderItems($orderId) {
        try {
            $sql = "SELECT * FROM order_items WHERE order_id = ?";
            return $this->db->select($sql, [(int)$orderId]);
        } catch (PDOException $e) {
            error_log("Erro ao obter itens do pedido: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter detalhes de um cliente
     * 
     * @param int $userId ID do usuário
     * @return array|false Dados do cliente ou false se não encontrado
     */
    private function getCustomer($userId) {
        try {
            $sql = "
                SELECT u.*, ud.phone, ud.document_type, ud.document_number
                FROM users u
                LEFT JOIN user_details ud ON u.id = ud.user_id
                WHERE u.id = ?
                LIMIT 1
            ";
            
            $result = $this->db->select($sql, [(int)$userId]);
            
            return !empty($result) ? $result[0] : false;
        } catch (PDOException $e) {
            error_log("Erro ao obter cliente: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter histórico de uma transação
     * 
     * @param string $transactionId ID externo da transação
     * @return array Lista de registros de histórico
     */
    private function getTransactionHistory($transactionId) {
        try {
            $sql = "
                SELECT *
                FROM payment_transactions
                WHERE transaction_id = ?
                ORDER BY created_at ASC
            ";
            
            return $this->db->select($sql, [SecurityManager::sanitize($transactionId)]);
        } catch (PDOException $e) {
            error_log("Erro ao obter histórico da transação: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter webhooks relacionados a uma transação
     * 
     * @param string $transactionId ID externo da transação
     * @return array Lista de webhooks
     */
    private function getRelatedWebhooks($transactionId) {
        try {
            $sql = "
                SELECT *
                FROM payment_webhooks
                WHERE transaction_id = ?
                ORDER BY created_at DESC
            ";
            
            return $this->db->select($sql, [SecurityManager::sanitize($transactionId)]);
        } catch (PDOException $e) {
            error_log("Erro ao obter webhooks da transação: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter reembolsos de uma transação
     * 
     * @param string $transactionId ID externo da transação
     * @return array Lista de reembolsos
     */
    private function getTransactionRefunds($transactionId) {
        try {
            $sql = "
                SELECT *
                FROM payment_refunds
                WHERE transaction_id = ?
                ORDER BY created_at DESC
            ";
            
            return $this->db->select($sql, [SecurityManager::sanitize($transactionId)]);
        } catch (PDOException $e) {
            error_log("Erro ao obter reembolsos da transação: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter transações de um pedido
     * 
     * @param int $orderId ID do pedido
     * @return array Lista de transações
     */
    private function getOrderTransactions($orderId) {
        try {
            $sql = "
                SELECT *
                FROM payment_transactions
                WHERE order_id = ?
                ORDER BY created_at DESC
            ";
            
            return $this->db->select($sql, [(int)$orderId]);
        } catch (PDOException $e) {
            error_log("Erro ao obter transações do pedido: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter tentativas de pagamento de um pedido
     * 
     * @param int $orderId ID do pedido
     * @return array Lista de tentativas
     */
    private function getPaymentAttempts($orderId) {
        try {
            $sql = "
                SELECT *
                FROM payment_attempts
                WHERE order_id = ?
                ORDER BY created_at DESC
            ";
            
            return $this->db->select($sql, [(int)$orderId]);
        } catch (PDOException $e) {
            error_log("Erro ao obter tentativas de pagamento: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter histórico de status de um pedido
     * 
     * @param int $orderId ID do pedido
     * @return array Lista de registros de histórico
     */
    private function getOrderStatusHistory($orderId) {
        try {
            $sql = "
                SELECT *
                FROM order_status_history
                WHERE order_id = ?
                ORDER BY created_at DESC
            ";
            
            return $this->db->select($sql, [(int)$orderId]);
        } catch (PDOException $e) {
            error_log("Erro ao obter histórico de status: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter lista de gateways únicos registrados
     * 
     * @return array Lista de gateways
     */
    private function getUniqueGateways() {
        try {
            $sql = "SELECT DISTINCT gateway FROM payment_webhooks ORDER BY gateway";
            $result = $this->db->select($sql);
            
            $gateways = [];
            foreach ($result as $row) {
                $gateways[] = $row['gateway'];
            }
            
            return $gateways;
        } catch (PDOException $e) {
            error_log("Erro ao obter gateways únicos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obter lista de tipos de eventos únicos registrados
     * 
     * @return array Lista de tipos de eventos
     */
    private function getUniqueEventTypes() {
        try {
            $sql = "SELECT DISTINCT event_type FROM payment_webhooks ORDER BY event_type";
            $result = $this->db->select($sql);
            
            $eventTypes = [];
            foreach ($result as $row) {
                $eventTypes[] = $row['event_type'];
            }
            
            return $eventTypes;
        } catch (PDOException $e) {
            error_log("Erro ao obter tipos de eventos únicos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Registrar ação administrativa no log
     * 
     * @param string $action Nome da ação
     * @param string $description Descrição da ação
     * @param array $data Dados relacionados à ação
     * @return bool Sucesso da operação
     */
    private function logAdminAction($action, $description, $data = []) {
        try {
            $userId = $_SESSION['user']['id'] ?? 0;
            $username = $_SESSION['user']['name'] ?? 'System';
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            
            $sql = "
                INSERT INTO admin_activity_log 
                (user_id, username, action, description, data, ip_address, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ";
            
            $this->db->query($sql, [
                $userId,
                SecurityManager::sanitize($username),
                SecurityManager::sanitize($action),
                SecurityManager::sanitize($description),
                json_encode($data),
                SecurityManager::sanitize($ip)
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Erro ao registrar ação administrativa: " . $e->getMessage());
            return false;
        }
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
