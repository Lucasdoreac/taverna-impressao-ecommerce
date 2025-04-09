<?php
/**
 * AbstractPaymentGateway - Classe base para implementações de gateway de pagamento
 * 
 * Implementa funcionalidades comuns a todos os gateways de pagamento,
 * reduzindo duplicação de código e garantindo consistência.
 * 
 * @package     App\Lib\Payment
 * @version     1.0.0
 * @author      Taverna da Impressão
 */
namespace App\Lib\Payment;

use App\Lib\Security\SecurityManager;
use App\Lib\Validation\InputValidationTrait;

abstract class AbstractPaymentGateway implements PaymentGatewayInterface {
    use InputValidationTrait;
    
    /**
     * @var array Configurações do gateway
     */
    protected $config;
    
    /**
     * @var bool Indica se ambiente é de teste/sandbox
     */
    protected $isSandbox = false;
    
    /**
     * @var array Registro de todas as requisições e respostas para auditoria
     */
    protected $transactionLog = [];
    
    /**
     * Construtor
     * 
     * @param array $config Configurações específicas do gateway
     */
    public function __construct(array $config) {
        $this->config = $config;
        $this->isSandbox = (bool)($config['sandbox'] ?? false);
        
        // Verificar configurações mínimas
        $this->validateConfiguration();
    }
    
    /**
     * Valida as configurações do gateway
     * 
     * @throws \Exception Se configurações estiverem incorretas ou incompletas
     */
    protected function validateConfiguration(): void {
        // Implementação específica em cada gateway
    }
    
    /**
     * Registra uma transação no banco de dados para rastreabilidade
     * 
     * @param string $orderId ID do pedido
     * @param string $transactionId ID da transação no gateway
     * @param string $status Status inicial da transação
     * @param array $additionalData Dados adicionais sobre a transação
     * @return int ID do registro de transação
     */
    protected function logTransaction(string $orderId, string $transactionId, string $status, array $additionalData = []): int {
        try {
            // Sanitizar dados
            $sanitizedData = json_encode($additionalData);
            
            // Registrar na tabela payment_transactions
            $pdo = \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                INSERT INTO payment_transactions 
                (order_id, gateway_name, transaction_id, status, amount, currency, payment_method, additional_data, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $gatewayName = static::getGatewayName();
            $amount = $additionalData['amount'] ?? 0;
            $currency = $additionalData['currency'] ?? 'BRL';
            $paymentMethod = $additionalData['payment_method'] ?? '';
            
            $stmt->execute([
                $orderId,
                $gatewayName,
                $transactionId,
                $status,
                $amount,
                $currency,
                $paymentMethod,
                $sanitizedData
            ]);
            
            return (int)$pdo->lastInsertId();
        } catch (\PDOException $e) {
            // Registrar erro em log, mas não interromper o fluxo
            error_log("Erro ao registrar transação: " . $e->getMessage());
            error_log("Dados: " . print_r([
                'order_id' => $orderId,
                'transaction_id' => $transactionId,
                'status' => $status
            ], true));
            
            return 0;
        }
    }
    
    /**
     * Atualiza o status de uma transação no banco de dados
     * 
     * @param string $transactionId ID da transação no gateway
     * @param string $newStatus Novo status da transação
     * @param array $additionalData Dados adicionais a serem adicionados/atualizados
     * @return bool Sucesso da operação
     */
    protected function updateTransactionStatus(string $transactionId, string $newStatus, array $additionalData = []): bool {
        try {
            $pdo = \Database::getInstance()->getPdo();
            
            // Obter registro existente para mesclar dados adicionais
            $stmt = $pdo->prepare("SELECT additional_data FROM payment_transactions WHERE transaction_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$transactionId]);
            $existingRecord = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($existingRecord) {
                $existingData = json_decode($existingRecord['additional_data'], true) ?? [];
                $mergedData = array_merge($existingData, $additionalData);
                $sanitizedData = json_encode($mergedData);
                
                // Atualizar registro
                $stmt = $pdo->prepare("
                    UPDATE payment_transactions 
                    SET status = ?, additional_data = ?, updated_at = NOW() 
                    WHERE transaction_id = ?
                ");
                $stmt->execute([$newStatus, $sanitizedData, $transactionId]);
                
                return true;
            }
            
            return false;
        } catch (\PDOException $e) {
            // Registrar erro em log, mas não interromper o fluxo
            error_log("Erro ao atualizar status da transação: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra requisição e resposta para auditoria
     * 
     * @param string $endpoint Endpoint chamado
     * @param array $requestData Dados enviados
     * @param array $responseData Dados recebidos
     * @param bool $success Indica se requisição foi bem-sucedida
     * @return void
     */
    protected function logApiInteraction(string $endpoint, array $requestData, array $responseData, bool $success): void {
        // Remover dados sensíveis
        $safeRequestData = $this->removeSensitiveData($requestData);
        $safeResponseData = $this->removeSensitiveData($responseData);
        
        // Adicionar ao log de transação
        $this->transactionLog[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoint' => $endpoint,
            'request' => $safeRequestData,
            'response' => $safeResponseData,
            'success' => $success
        ];
        
        // Registrar em debug log se em ambiente de desenvolvimento
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            error_log("API Gateway [{$this->getGatewayName()}] - Endpoint: {$endpoint} - Sucesso: " . ($success ? 'Sim' : 'Não'));
        }
    }
    
    /**
     * Remove dados sensíveis antes de registrar logs
     * 
     * @param array $data Dados a serem higienizados
     * @return array Dados sem informações sensíveis
     */
    protected function removeSensitiveData(array $data): array {
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
     * Valida dados do pedido conforme requisitos do gateway
     * 
     * @param array $orderData Dados do pedido a serem validados
     * @return bool Se dados são válidos
     * @throws \Exception Se dados forem inválidos
     */
    protected function validateOrderData(array $orderData): bool {
        // Implementação específica em cada gateway
        return true;
    }
    
    /**
     * Valida dados do cliente conforme requisitos do gateway
     * 
     * @param array $customerData Dados do cliente a serem validados
     * @return bool Se dados são válidos
     * @throws \Exception Se dados forem inválidos
     */
    protected function validateCustomerData(array $customerData): bool {
        // Implementação específica em cada gateway
        return true;
    }
    
    /**
     * Valida dados de pagamento conforme requisitos do gateway
     * 
     * @param array $paymentData Dados de pagamento a serem validados
     * @return bool Se dados são válidos
     * @throws \Exception Se dados forem inválidos
     */
    protected function validatePaymentData(array $paymentData): bool {
        // Implementação específica em cada gateway
        return true;
    }
    
    /**
     * Gera uma assinatura segura para comunicação com o gateway
     * 
     * @param array $data Dados a serem assinados
     * @param string $secret Chave secreta para assinatura
     * @return string Assinatura gerada
     */
    protected function generateSignature(array $data, string $secret): string {
        $dataString = json_encode($data);
        return hash_hmac('sha256', $dataString, $secret);
    }
    
    /**
     * Verifica assinatura recebida em callbacks/webhooks
     * 
     * @param array $data Dados recebidos
     * @param string $signature Assinatura recebida
     * @param string $secret Chave secreta para verificação
     * @return bool Se assinatura é válida
     */
    protected function verifySignature(array $data, string $signature, string $secret): bool {
        $calculatedSignature = $this->generateSignature($data, $secret);
        return hash_equals($calculatedSignature, $signature);
    }
    
    /**
     * Retorna o nome do gateway para registro e auditoria
     * 
     * @return string Nome do gateway
     */
    abstract public static function getGatewayName(): string;
}
