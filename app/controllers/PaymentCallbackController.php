<?php
/**
 * PaymentCallbackController - Controlador para retornos de gateways de pagamento
 * 
 * Gerencia callbacks e redirecionamentos após operações de pagamento,
 * incluindo URLs de sucesso, cancelamento, falha e processamento de 
 * webhooks específicos.
 * 
 * @package     App\Controllers
 * @version     1.0.0
 * @author      Taverna da Impressão
 */

use App\Lib\Payment\PaymentManager;
use App\Lib\Security\CsrfProtection;
use App\Lib\Security\SecurityManager;
use App\Lib\Validation\InputValidationTrait;

class PaymentCallbackController {
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
        // Inicializar componentes
        $this->paymentManager = PaymentManager::getInstance();
        $this->db = Database::getInstance();
        
        // Carregar dependências
        require_once APP_PATH . '/lib/Payment/PaymentManager.php';
        require_once APP_PATH . '/lib/Security/SecurityManager.php';
        require_once APP_PATH . '/lib/Security/CsrfProtection.php';
    }
    
    /**
     * Callback para PayPal após pagamento bem-sucedido
     * 
     * @param string $orderNumber Número do pedido
     */
    public function paypalSuccess() {
        try {
            // Verificar parâmetros do PayPal
            $paymentId = $this->getValidatedParam('paymentId', 'string');
            $payerId = $this->getValidatedParam('PayerID', 'string');
            $token = $this->getValidatedParam('token', 'string');
            
            if ($this->hasValidationErrors()) {
                $_SESSION['error'] = 'Parâmetros inválidos na URL de retorno.';
                header('Location: ' . BASE_URL . 'meus-pedidos');
                exit;
            }
            
            // Verificar token/transactionId
            if (!empty($token)) {
                // Token presente, usá-lo como transactionId
                $transactionId = $token;
            } elseif (!empty($paymentId)) {
                // PaymentId presente, usá-lo
                $transactionId = $paymentId;
            } else {
                // Nenhum identificador presente, erro
                $_SESSION['error'] = 'Identificador de transação ausente.';
                header('Location: ' . BASE_URL . 'meus-pedidos');
                exit;
            }
            
            // Verificar status da transação
            $transaction = $this->findTransactionByPaypalToken($transactionId);
            
            if (!$transaction) {
                $_SESSION['error'] = 'Transação não encontrada.';
                header('Location: ' . BASE_URL . 'meus-pedidos');
                exit;
            }
            
            // Verificar se já não foi processada
            if (in_array($transaction['status'], ['approved', 'refunded', 'cancelled'])) {
                // Já processada, redirecionar para página de sucesso
                $_SESSION['success'] = 'Pagamento já foi processado anteriormente.';
                header('Location: ' . BASE_URL . 'pedido/sucesso/' . $transaction['order_number']);
                exit;
            }
            
            // Capturar pagamento se necessário
            if ($transaction['status'] === 'pending' || $transaction['status'] === 'authorized') {
                // Capturar via PaymentManager
                $captureResult = $this->paymentManager->capturePayPalPayment($transactionId, $payerId);
                
                if (!($captureResult['success'] ?? false)) {
                    $_SESSION['error'] = 'Erro ao capturar pagamento: ' . 
                        SecurityManager::sanitize($captureResult['error_message'] ?? 'Erro desconhecido');
                    
                    header('Location: ' . BASE_URL . 'pedido/detalhes/' . $transaction['order_id']);
                    exit;
                }
                
                // Atualizar status da transação
                $this->updateTransactionStatus(
                    $transaction['id'], 
                    'approved', 
                    [
                        'payer_id' => $payerId,
                        'capture_date' => date('Y-m-d H:i:s')
                    ]
                );
                
                // Atualizar status do pedido
                $this->updateOrderStatus(
                    $transaction['order_id'],
                    'processing',
                    'approved',
                    'Pagamento aprovado e capturado via PayPal.'
                );
            }
            
            // Redirecionar para página de sucesso
            $_SESSION['success'] = 'Pagamento processado com sucesso!';
            header('Location: ' . BASE_URL . 'pedido/sucesso/' . $transaction['order_number']);
            exit;
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro no callback PayPal: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao processar o retorno do pagamento. Por favor, verifique o status do seu pedido.';
            header('Location: ' . BASE_URL . 'meus-pedidos');
            exit;
        }
    }
    
    /**
     * Callback para PayPal após cancelamento
     */
    public function paypalCancel() {
        try {
            // Verificar parâmetros do PayPal
            $token = $this->getValidatedParam('token', 'string');
            
            if ($this->hasValidationErrors() || empty($token)) {
                $_SESSION['error'] = 'Parâmetros inválidos na URL de retorno.';
                header('Location: ' . BASE_URL . 'meus-pedidos');
                exit;
            }
            
            // Buscar transação pelo token
            $transaction = $this->findTransactionByPaypalToken($token);
            
            if (!$transaction) {
                $_SESSION['error'] = 'Transação não encontrada.';
                header('Location: ' . BASE_URL . 'meus-pedidos');
                exit;
            }
            
            // Atualizar status se ainda não for final
            if (in_array($transaction['status'], ['pending', 'authorized'])) {
                // Atualizar status da transação
                $this->updateTransactionStatus(
                    $transaction['id'], 
                    'cancelled', 
                    [
                        'cancel_reason' => 'Cancelado pelo usuário',
                        'cancel_date' => date('Y-m-d H:i:s')
                    ]
                );
                
                // Atualizar status do pedido
                $this->updateOrderStatus(
                    $transaction['order_id'],
                    'cancelled',
                    'cancelled',
                    'Pagamento cancelado pelo usuário no PayPal.'
                );
            }
            
            // Redirecionar para página de pedido
            $_SESSION['warning'] = 'Pagamento cancelado.';
            header('Location: ' . BASE_URL . 'pedido/detalhes/' . $transaction['order_id']);
            exit;
        } catch (Exception $e) {
            // Registrar erro
            error_log("Erro no callback de cancelamento PayPal: " . $e->getMessage());
            
            // Exibir mensagem para o usuário
            $_SESSION['error'] = 'Ocorreu um erro ao processar o cancelamento do pagamento.';
            header('Location: ' . BASE_URL . 'meus-pedidos');
            exit;
        }
    }

    /**
     * Processa notificações IPN do PayPal
     */
    public function paypalIPN() {
        try {
            // Verificar método da requisição
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo "Method Not Allowed";
                exit;
            }
            
            // IPN não usa CSRF token, mas precisa ser validado de outra forma
            // Notificações vêm diretamente do PayPal, não do navegador do usuário
            
            // Capturar dados brutos do POST para processamento
            $ipnData = $_POST;
            
            // Se não há dados, tentar corpo da requisição
            if (empty($ipnData)) {
                $rawData = file_get_contents('php://input');
                parse_str($rawData, $ipnData);
            }
            
            // Validar que existem dados mínimos
            if (empty($ipnData)) {
                error_log("PayPal IPN recebido sem dados");
                http_response_code(400);
                echo "Bad Request - No Data";
                exit;
            }
            
            // Registrar no log para depuração
            $safeData = $this->removeSensitiveData($ipnData);
            error_log("PayPal IPN recebido: " . json_encode($safeData));
            
            // Processar IPN via PaymentManager
            $result = $this->paymentManager->processPayPalIPN($ipnData);
            
            // Sempre responder com sucesso, mesmo em caso de erros
            // PayPal continuará tentando reenviar em caso de erro HTTP
            http_response_code(200);
            
            if ($result['success']) {
                echo "IPN Processed Successfully";
            } else {
                echo "IPN Received";
                error_log("Erro no processamento de IPN: " . ($result['error_message'] ?? 'Erro desconhecido'));
            }
            
            exit;
        } catch (Exception $e) {
            // Registrar erro
            error_log("Exceção ao processar IPN PayPal: " . $e->getMessage());
            
            // Sempre responder com sucesso para evitar reenvios
            http_response_code(200);
            echo "IPN Received (Error Logged)";
            exit;
        }
    }
    
    /**
     * Remove dados sensíveis para log
     * 
     * @param array $data Dados a serem higienizados
     * @return array Dados sem informações sensíveis
     */
    private function removeSensitiveData(array $data): array {
        $sensitiveKeys = [
            'card_number', 'cvv', 'cvc', 'security_code', 'password', 'secret',
            'token', 'access_token', 'api_key', 'private_key', 'authorization'
        ];
        
        $result = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->removeSensitiveData($value);
            } else {
                // Verificar se a chave contém alguma das palavras sensíveis
                $isSensitive = false;
                foreach ($sensitiveKeys as $sensitiveKey) {
                    if (stripos($key, $sensitiveKey) !== false) {
                        $isSensitive = true;
                        break;
                    }
                }
                
                if ($isSensitive) {
                    // Mascarar dado sensível
                    $result[$key] = '******';
                } else {
                    $result[$key] = $value;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Pesquisa transação pelo token do PayPal
     * 
     * @param string $token Token do PayPal (order ID)
     * @return array|false Dados da transação ou false se não encontrada
     */
    private function findTransactionByPaypalToken(string $token) {
        try {
            $pdo = $this->db->getPdo();
            
            // Primeiro verificar pelo transaction_id direto
            $stmt = $pdo->prepare("
                SELECT t.*, o.order_number 
                FROM payment_transactions t 
                INNER JOIN orders o ON t.order_id = o.id 
                WHERE t.transaction_id = ? 
                LIMIT 1
            ");
            
            $stmt->execute([$token]);
            $transaction = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($transaction) {
                return $transaction;
            }
            
            // Depois procurar nos dados adicionais (pode estar em JSON)
            $stmt = $pdo->prepare("
                SELECT t.*, o.order_number 
                FROM payment_transactions t 
                INNER JOIN orders o ON t.order_id = o.id 
                WHERE t.additional_data LIKE ? 
                LIMIT 1
            ");
            
            $stmt->execute(['%' . $token . '%']);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erro ao buscar transação: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza status de uma transação de pagamento
     * 
     * @param int $transactionId ID da transação
     * @param string $status Novo status
     * @param array $additionalData Dados adicionais
     * @return bool Sucesso da operação
     */
    private function updateTransactionStatus(int $transactionId, string $status, array $additionalData = []): bool {
        try {
            $pdo = $this->db->getPdo();
            
            // Obter dados adicionais existentes
            $stmt = $pdo->prepare("
                SELECT additional_data FROM payment_transactions WHERE id = ?
            ");
            
            $stmt->execute([$transactionId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$result) {
                return false;
            }
            
            // Mesclar dados adicionais
            $existingData = json_decode($result['additional_data'], true) ?? [];
            $mergedData = array_merge($existingData, $additionalData);
            
            // Atualizar transação
            $stmt = $pdo->prepare("
                UPDATE payment_transactions 
                SET status = ?, additional_data = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([
                SecurityManager::sanitize($status),
                json_encode($mergedData),
                $transactionId
            ]);
            
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Erro ao atualizar status da transação: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza status de um pedido
     * 
     * @param int $orderId ID do pedido
     * @param string $orderStatus Novo status do pedido
     * @param string $paymentStatus Novo status de pagamento
     * @param string $notes Notas sobre a mudança
     * @return bool Sucesso da operação
     */
    private function updateOrderStatus(int $orderId, string $orderStatus, string $paymentStatus, string $notes): bool {
        try {
            $pdo = $this->db->getPdo();
            
            // Atualizar pedido
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = ?, payment_status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([
                SecurityManager::sanitize($orderStatus),
                SecurityManager::sanitize($paymentStatus),
                $orderId
            ]);
            
            // Registrar histórico
            $stmt = $pdo->prepare("
                INSERT INTO order_status_history 
                (order_id, status, payment_status, notes, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $orderId,
                SecurityManager::sanitize($orderStatus),
                SecurityManager::sanitize($paymentStatus),
                SecurityManager::sanitize($notes)
            ]);
            
            return true;
        } catch (\PDOException $e) {
            error_log("Erro ao atualizar status do pedido: " . $e->getMessage());
            return false;
        }
    }
}
