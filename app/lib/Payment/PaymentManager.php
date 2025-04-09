<?php
/**
 * PaymentManager - Gerenciador central de pagamentos
 * 
 * Responsável por abstrair a interface com diferentes gateways de pagamento,
 * gerenciar configurações, logs e processamento de operações de pagamento.
 * 
 * @package     App\Lib\Payment
 * @version     1.0.0
 * @author      Taverna da Impressão
 */
namespace App\Lib\Payment;

use App\Lib\Security\SecurityManager;
use App\Lib\Validation\InputValidationTrait;

class PaymentManager {
    use InputValidationTrait;
    
    /**
     * @var array Instâncias de gateways de pagamento
     */
    private $gateways = [];
    
    /**
     * @var array Configurações dos gateways disponíveis
     */
    private $gatewayConfigs = [];
    
    /**
     * @var array Métodos de pagamento por gateway
     */
    private $paymentMethods = [];
    
    /**
     * @var array Registros de erros para depuração
     */
    private $errors = [];
    
    /**
     * @var PaymentManager Instância única (Singleton)
     */
    private static $instance = null;
    
    /**
     * Obtém instância única do PaymentManager
     * 
     * @return PaymentManager
     */
    public static function getInstance(): PaymentManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Construtor privado para Singleton
     */
    private function __construct() {
        $this->loadConfigurations();
        $this->initializeGateways();
    }
    
    /**
     * Carrega configurações de gateways
     * 
     * @return void
     */
    private function loadConfigurations(): void {
        try {
            // Carregar configurações da base de dados
            $pdo = \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'payment.%'");
            $stmt->execute();
            $settings = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($settings as $setting) {
                $key = $setting['setting_key'];
                $value = json_decode($setting['setting_value'], true);
                
                // Extrair nome do gateway do formato payment.{gateway_name}.config
                $parts = explode('.', $key);
                if (count($parts) >= 2) {
                    $gatewayName = $parts[1];
                    
                    if (!isset($this->gatewayConfigs[$gatewayName])) {
                        $this->gatewayConfigs[$gatewayName] = [];
                    }
                    
                    // Se for configuração específica (parts[2]), armazenar separadamente
                    if (count($parts) >= 3) {
                        $configType = $parts[2];
                        $this->gatewayConfigs[$gatewayName][$configType] = $value;
                    } else {
                        // Caso seja configuração genérica
                        $this->gatewayConfigs[$gatewayName] = array_merge($this->gatewayConfigs[$gatewayName], $value);
                    }
                }
            }
            
            // Carregar métodos de pagamento
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'payment_methods'");
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($result) {
                $this->paymentMethods = json_decode($result['setting_value'], true) ?? [];
            }
        } catch (\PDOException $e) {
            $this->errors[] = "Erro ao carregar configurações de pagamento: " . $e->getMessage();
        }
    }
    
    /**
     * Inicializa instâncias de gateways configurados
     * 
     * @return void
     */
    private function initializeGateways(): void {
        foreach ($this->gatewayConfigs as $gatewayName => $config) {
            // Pular gateways desativados
            if (isset($config['active']) && $config['active'] === false) {
                continue;
            }
            
            try {
                // Verificar se classe do gateway existe
                $gatewayClass = $this->getGatewayClassByName($gatewayName);
                
                // Verificar se a classe implementa a interface
                if (class_exists($gatewayClass) && in_array(PaymentGatewayInterface::class, class_implements($gatewayClass))) {
                    $this->gateways[$gatewayName] = new $gatewayClass($config);
                }
            } catch (\Exception $e) {
                $this->errors[] = "Erro ao inicializar gateway {$gatewayName}: " . $e->getMessage();
            }
        }
    }
    
    /**
     * Obtém a classe correspondente ao nome do gateway
     * 
     * @param string $gatewayName Nome do gateway (ex: "mercadopago")
     * @return string Nome completo da classe, incluindo namespace
     */
    private function getGatewayClassByName(string $gatewayName): string {
        // Capitalizar palavras e remover underscores para o nome da classe
        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $gatewayName)));
        
        return "App\\Lib\\Payment\\Gateways\\{$className}Gateway";
    }
    
    /**
     * Obtém instância de um gateway específico
     * 
     * @param string $gatewayName Nome do gateway desejado
     * @return PaymentGatewayInterface Instância do gateway
     * @throws \Exception Se gateway não estiver disponível
     */
    public function getGateway(string $gatewayName): PaymentGatewayInterface {
        if (!isset($this->gateways[$gatewayName])) {
            throw new \Exception("Gateway de pagamento '{$gatewayName}' não está disponível ou não está configurado");
        }
        
        return $this->gateways[$gatewayName];
    }
    
    /**
     * Obtém gateway associado a um método de pagamento
     * 
     * @param string $paymentMethodId ID do método de pagamento
     * @return PaymentGatewayInterface Instância do gateway
     * @throws \Exception Se método ou gateway não estiver disponível
     */
    public function getGatewayByPaymentMethod(string $paymentMethodId): PaymentGatewayInterface {
        // Encontrar método de pagamento
        $gatewayName = null;
        
        foreach ($this->paymentMethods as $method) {
            if ($method['id'] === $paymentMethodId && isset($method['gateway'])) {
                $gatewayName = $method['gateway'];
                break;
            }
        }
        
        if (!$gatewayName) {
            throw new \Exception("Método de pagamento '{$paymentMethodId}' não está configurado ou não possui gateway associado");
        }
        
        return $this->getGateway($gatewayName);
    }
    
    /**
     * Lista todos os gateways disponíveis
     * 
     * @param bool $activeOnly Se deve retornar apenas gateways ativos
     * @return array Lista de gateways configurados
     */
    public function listAvailableGateways(bool $activeOnly = true): array {
        $available = [];
        
        foreach ($this->gatewayConfigs as $gatewayName => $config) {
            if ($activeOnly && (!isset($config['active']) || $config['active'] === false)) {
                continue;
            }
            
            $available[] = [
                'name' => $gatewayName,
                'display_name' => $config['display_name'] ?? ucfirst($gatewayName),
                'is_active' => $config['active'] ?? false,
                'is_sandbox' => $config['sandbox'] ?? false,
                'payment_methods' => $config['payment_methods'] ?? []
            ];
        }
        
        return $available;
    }
    
    /**
     * Lista métodos de pagamento disponíveis
     * 
     * @param bool $activeOnly Se deve retornar apenas métodos ativos
     * @return array Lista de métodos de pagamento configurados
     */
    public function listPaymentMethods(bool $activeOnly = true): array {
        if ($activeOnly) {
            return array_filter($this->paymentMethods, function($method) {
                return isset($method['active']) && $method['active'] === true;
            });
        }
        
        return $this->paymentMethods;
    }
    
    /**
     * Processa pagamento de um pedido
     * 
     * @param array $orderData Dados do pedido
     * @param array $customerData Dados do cliente
     * @param array $paymentData Dados do pagamento
     * @return array Resultado da operação de pagamento
     * @throws \Exception Em caso de erro no processamento
     */
    public function processPayment(array $orderData, array $customerData, array $paymentData): array {
        // Validar parâmetros obrigatórios
        $this->validateAndSanitizeOrderData($orderData);
        $this->validateAndSanitizeCustomerData($customerData);
        $this->validateAndSanitizePaymentData($paymentData);
        
        try {
            // Obter gateway apropriado para o método de pagamento
            $paymentMethod = $paymentData['payment_method'] ?? '';
            $gateway = $this->getGatewayByPaymentMethod($paymentMethod);
            
            // Iniciar transação no gateway
            $transaction = $gateway->initiateTransaction($orderData, $customerData, $paymentData);
            
            // Registrar transação no banco independente do resultado
            $this->recordPaymentAttempt($orderData['id'], $paymentMethod, $transaction);
            
            return $transaction;
        } catch (\Exception $e) {
            // Registrar erro
            $this->errors[] = "Erro ao processar pagamento: " . $e->getMessage();
            
            // Registrar tentativa falha
            $errorData = ['error' => $e->getMessage(), 'error_code' => $e->getCode()];
            $this->recordPaymentAttempt($orderData['id'], $paymentData['payment_method'] ?? '', [
                'success' => false,
                'error_message' => $e->getMessage()
            ], $errorData);
            
            throw $e;
        }
    }
    
    /**
     * Registra tentativa de pagamento no sistema
     * 
     * @param string $orderId ID do pedido
     * @param string $paymentMethod Método de pagamento utilizado
     * @param array $result Resultado da operação de pagamento
     * @param array $additionalData Dados adicionais
     * @return int ID do registro inserido
     */
    private function recordPaymentAttempt(string $orderId, string $paymentMethod, array $result, array $additionalData = []): int {
        try {
            $success = $result['success'] ?? false;
            $transactionId = $result['transaction_id'] ?? null;
            $amount = $additionalData['amount'] ?? 0;
            $gateway = $additionalData['gateway'] ?? '';
            $status = $success ? 'pending' : 'failed';
            
            // Merging dos dados adicionais
            $mergedData = array_merge($additionalData, $result);
            $sanitizedData = json_encode($mergedData);
            
            $pdo = \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                INSERT INTO payment_attempts 
                (order_id, payment_method, gateway, transaction_id, status, amount, success, additional_data, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $orderId,
                $paymentMethod,
                $gateway,
                $transactionId,
                $status,
                $amount,
                $success ? 1 : 0,
                $sanitizedData
            ]);
            
            return (int)$pdo->lastInsertId();
        } catch (\PDOException $e) {
            // Registrar erro, mas não interromper fluxo
            error_log("Erro ao registrar tentativa de pagamento: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Processa webhooks/callbacks de gateways de pagamento
     * 
     * @param string $gatewayName Nome do gateway de origem
     * @param array $requestData Dados recebidos no webhook
     * @return array Resultado do processamento
     * @throws \Exception Em caso de erro no processamento
     */
    public function processWebhook(string $gatewayName, array $requestData): array {
        try {
            $gateway = $this->getGateway($gatewayName);
            $result = $gateway->handleCallback($requestData);
            
            // Registrar processamento do webhook
            $this->logWebhook($gatewayName, $requestData, $result);
            
            return $result;
        } catch (\Exception $e) {
            // Registrar erro
            $this->errors[] = "Erro ao processar webhook do gateway {$gatewayName}: " . $e->getMessage();
            $this->logWebhook($gatewayName, $requestData, ['success' => false, 'error' => $e->getMessage()]);
            
            throw $e;
        }
    }
    
    /**
     * Registra recebimento e processamento de webhook
     * 
     * @param string $gatewayName Nome do gateway
     * @param array $requestData Dados recebidos
     * @param array $processResult Resultado do processamento
     * @return void
     */
    private function logWebhook(string $gatewayName, array $requestData, array $processResult): void {
        try {
            // Remover dados sensíveis
            $safeRequestData = $this->removeSensitiveData($requestData);
            
            $pdo = \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                INSERT INTO payment_webhooks 
                (gateway, event_type, transaction_id, request_data, process_result, success, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $success = $processResult['success'] ?? false;
            $eventType = $requestData['event_type'] ?? $requestData['type'] ?? 'unknown';
            $transactionId = $requestData['transaction_id'] ?? $requestData['id'] ?? null;
            
            $stmt->execute([
                $gatewayName,
                $eventType,
                $transactionId,
                json_encode($safeRequestData),
                json_encode($processResult),
                $success ? 1 : 0
            ]);
        } catch (\PDOException $e) {
            // Registrar erro, mas não interromper fluxo
            error_log("Erro ao registrar webhook: " . $e->getMessage());
        }
    }
    
    /**
     * Remove dados sensíveis de arrays para logs
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
     * Valida e sanitiza dados do pedido
     * 
     * @param array &$orderData Dados do pedido (passados por referência para sanitização)
     * @return bool Resultado da validação
     * @throws \Exception Se dados forem inválidos
     */
    private function validateAndSanitizeOrderData(array &$orderData): bool {
        // Verificar campos obrigatórios
        $requiredFields = ['id', 'order_number', 'total'];
        foreach ($requiredFields as $field) {
            if (!isset($orderData[$field]) || empty($orderData[$field])) {
                throw new \Exception("Campo obrigatório ausente nos dados do pedido: {$field}");
            }
        }
        
        // Sanitizar dados
        $orderData['id'] = SecurityManager::sanitize($orderData['id']);
        $orderData['order_number'] = SecurityManager::sanitize($orderData['order_number']);
        $orderData['total'] = (float)$orderData['total'];
        
        // Validar total
        if ($orderData['total'] <= 0) {
            throw new \Exception("Valor total do pedido deve ser maior que zero");
        }
        
        return true;
    }
    
    /**
     * Valida e sanitiza dados do cliente
     * 
     * @param array &$customerData Dados do cliente (passados por referência para sanitização)
     * @return bool Resultado da validação
     * @throws \Exception Se dados forem inválidos
     */
    private function validateAndSanitizeCustomerData(array &$customerData): bool {
        // Verificar campos obrigatórios
        $requiredFields = ['name', 'email'];
        foreach ($requiredFields as $field) {
            if (!isset($customerData[$field]) || empty($customerData[$field])) {
                throw new \Exception("Campo obrigatório ausente nos dados do cliente: {$field}");
            }
        }
        
        // Sanitizar dados
        $customerData['name'] = SecurityManager::sanitize($customerData['name']);
        $customerData['email'] = SecurityManager::sanitize($customerData['email']);
        
        // Validar email
        if (!filter_var($customerData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Endereço de e-mail inválido");
        }
        
        return true;
    }
    
    /**
     * Valida e sanitiza dados de pagamento
     * 
     * @param array &$paymentData Dados de pagamento (passados por referência para sanitização)
     * @return bool Resultado da validação
     * @throws \Exception Se dados forem inválidos
     */
    private function validateAndSanitizePaymentData(array &$paymentData): bool {
        // Verificar campos obrigatórios
        $requiredFields = ['payment_method'];
        foreach ($requiredFields as $field) {
            if (!isset($paymentData[$field]) || empty($paymentData[$field])) {
                throw new \Exception("Campo obrigatório ausente nos dados de pagamento: {$field}");
            }
        }
        
        // Sanitizar dados
        $paymentData['payment_method'] = SecurityManager::sanitize($paymentData['payment_method']);
        
        // Verificar se método de pagamento é válido
        $validMethod = false;
        foreach ($this->paymentMethods as $method) {
            if ($method['id'] === $paymentData['payment_method'] && isset($method['active']) && $method['active'] === true) {
                $validMethod = true;
                break;
            }
        }
        
        if (!$validMethod) {
            throw new \Exception("Método de pagamento inválido ou inativo: {$paymentData['payment_method']}");
        }
        
        return true;
    }
    
    /**
     * Verifica status de uma transação existente
     * 
     * @param string $transactionId ID da transação
     * @param string $gatewayName Nome do gateway (opcional)
     * @return array Status atualizado da transação
     * @throws \Exception Se transação não for encontrada ou ocorrer erro
     */
    public function checkTransactionStatus(string $transactionId, ?string $gatewayName = null): array {
        try {
            // Se não foi especificado gateway, buscar da base
            if ($gatewayName === null) {
                $pdo = \Database::getInstance()->getPdo();
                $stmt = $pdo->prepare("
                    SELECT gateway_name FROM payment_transactions 
                    WHERE transaction_id = ? 
                    ORDER BY id DESC LIMIT 1
                ");
                $stmt->execute([$transactionId]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if (!$result) {
                    throw new \Exception("Transação não encontrada: {$transactionId}");
                }
                
                $gatewayName = $result['gateway_name'];
            }
            
            // Obter gateway e verificar status
            $gateway = $this->getGateway($gatewayName);
            return $gateway->checkTransactionStatus($transactionId);
        } catch (\Exception $e) {
            $this->errors[] = "Erro ao verificar status da transação: " . $e->getMessage();
            throw $e;
        }
    }
    
    /**
     * Cancela uma transação existente
     * 
     * @param string $transactionId ID da transação
     * @param string $gatewayName Nome do gateway (opcional)
     * @param string $reason Motivo do cancelamento (opcional)
     * @return array Resultado do cancelamento
     * @throws \Exception Se ocorrer erro no cancelamento
     */
    public function cancelTransaction(string $transactionId, ?string $gatewayName = null, string $reason = ''): array {
        try {
            // Se não foi especificado gateway, buscar da base
            if ($gatewayName === null) {
                $pdo = \Database::getInstance()->getPdo();
                $stmt = $pdo->prepare("
                    SELECT gateway_name FROM payment_transactions 
                    WHERE transaction_id = ? 
                    ORDER BY id DESC LIMIT 1
                ");
                $stmt->execute([$transactionId]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if (!$result) {
                    throw new \Exception("Transação não encontrada: {$transactionId}");
                }
                
                $gatewayName = $result['gateway_name'];
            }
            
            // Sanitizar razão
            $reason = SecurityManager::sanitize($reason);
            
            // Obter gateway e cancelar
            $gateway = $this->getGateway($gatewayName);
            $result = $gateway->cancelTransaction($transactionId, $reason);
            
            // Atualizar status na base se cancelamento bem-sucedido
            if ($result['success'] ?? false) {
                $pdo = \Database::getInstance()->getPdo();
                $stmt = $pdo->prepare("
                    UPDATE payment_transactions 
                    SET status = 'cancelled', updated_at = NOW() 
                    WHERE transaction_id = ?
                ");
                $stmt->execute([$transactionId]);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->errors[] = "Erro ao cancelar transação: " . $e->getMessage();
            throw $e;
        }
    }
    
    /**
     * Reembolsa uma transação existente
     * 
     * @param string $transactionId ID da transação
     * @param float|null $amount Valor a ser reembolsado (null para reembolso total)
     * @param string $gatewayName Nome do gateway (opcional)
     * @param string $reason Motivo do reembolso (opcional)
     * @return array Resultado do reembolso
     * @throws \Exception Se ocorrer erro no reembolso
     */
    public function refundTransaction(string $transactionId, ?float $amount = null, ?string $gatewayName = null, string $reason = ''): array {
        try {
            // Se não foi especificado gateway, buscar da base
            if ($gatewayName === null) {
                $pdo = \Database::getInstance()->getPdo();
                $stmt = $pdo->prepare("
                    SELECT gateway_name FROM payment_transactions 
                    WHERE transaction_id = ? 
                    ORDER BY id DESC LIMIT 1
                ");
                $stmt->execute([$transactionId]);
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if (!$result) {
                    throw new \Exception("Transação não encontrada: {$transactionId}");
                }
                
                $gatewayName = $result['gateway_name'];
            }
            
            // Sanitizar razão
            $reason = SecurityManager::sanitize($reason);
            
            // Obter gateway e reembolsar
            $gateway = $this->getGateway($gatewayName);
            $result = $gateway->refundTransaction($transactionId, $amount, $reason);
            
            // Atualizar status na base se reembolso bem-sucedido
            if ($result['success'] ?? false) {
                $status = $amount === null ? 'refunded' : 'partially_refunded';
                
                $pdo = \Database::getInstance()->getPdo();
                $stmt = $pdo->prepare("
                    UPDATE payment_transactions 
                    SET status = ?, updated_at = NOW() 
                    WHERE transaction_id = ?
                ");
                $stmt->execute([$status, $transactionId]);
                
                // Registrar reembolso
                $stmt = $pdo->prepare("
                    INSERT INTO payment_refunds 
                    (transaction_id, amount, reason, status, refund_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $transactionId,
                    $amount ?? 0,
                    $reason,
                    'completed',
                    $result['refund_id'] ?? null
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->errors[] = "Erro ao reembolsar transação: " . $e->getMessage();
            throw $e;
        }
    }
    
    /**
     * Obtém erros ocorridos durante operações
     * 
     * @return array Lista de erros
     */
    public function getErrors(): array {
        return $this->errors;
    }
    
    /**
     * Captura um pagamento PayPal previamente autorizado
     * 
     * @param string $transactionId ID da transação/ordem do PayPal
     * @param string $payerId ID do pagador (recebido no retorno do PayPal)
     * @return array Resultado da captura
     * @throws \Exception Em caso de falha
     */
    public function capturePayPalPayment(string $transactionId, string $payerId): array {
        try {
            // Validar parâmetros
            if (empty($transactionId) || empty($payerId)) {
                throw new \Exception("Parâmetros inválidos para captura de pagamento PayPal");
            }
            
            // Obter gateway PayPal
            $gateway = $this->getGateway('paypal');
            
            // Efetuar captura de pagamento usando API não-pública do gateway
            $reflectionMethod = new \ReflectionMethod($gateway, 'capturePayment');
            $reflectionMethod->setAccessible(true);
            $result = $reflectionMethod->invoke($gateway, $transactionId);
            
            // Registrar resultado
            $success = isset($result['success']) && $result['success'];
            error_log("Captura de pagamento PayPal " . ($success ? "bem-sucedida" : "falhou") . ": " . json_encode($result));
            
            return $result;
        } catch (\Exception $e) {
            error_log("Erro ao capturar pagamento PayPal: " . $e->getMessage());
            
            // Retornar erro estruturado
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Verifica se um pedido tem transação de PayPal associada
     * 
     * @param int $orderId ID do pedido a ser verificado
     * @return array|null Informações da transação PayPal ou null se não encontrada
     */
    public function getPayPalTransactionForOrder(int $orderId): ?array {
        try {
            // Buscar transação de pagamento associada ao pedido
            $pdo = \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                SELECT * FROM payment_transactions 
                WHERE order_id = ? AND gateway_name = 'paypal'
                ORDER BY id DESC LIMIT 1
            ");
            
            $stmt->execute([$orderId]);
            $transaction = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                return null;
            }
            
            // Processar dados adicionais
            if (!empty($transaction['additional_data'])) {
                $additionalData = json_decode($transaction['additional_data'], true);
                $transaction = array_merge($transaction, $additionalData ?? []);
            }
            
            return $transaction;
        } catch (\PDOException $e) {
            error_log("Erro ao buscar transação PayPal para pedido: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Processa uma notificação IPN (Instant Payment Notification) do PayPal
     * 
     * @param array $ipnData Dados recebidos na notificação IPN
     * @return array Resultado do processamento
     */
    public function processPayPalIPN(array $ipnData): array {
        try {
            // Validar dados mínimos
            if (empty($ipnData) || !isset($ipnData['txn_id'])) {
                throw new \Exception("Dados IPN inválidos ou incompletos");
            }
            
            // Verificar autenticidade com PayPal
            $verificationResult = $this->verifyPayPalIPN($ipnData);
            
            if (!$verificationResult) {
                throw new \Exception("Verificação de IPN falhou");
            }
            
            // Log completo dos dados de IPN para auditoria
            $safeData = $this->removeSensitiveData($ipnData);
            error_log("IPN PayPal válido recebido: " . json_encode($safeData));
            
            // Processa de acordo com o tipo de notificação
            $transactionType = $ipnData['txn_type'] ?? '';
            $paymentStatus = $ipnData['payment_status'] ?? '';
            
            // Identificar transação
            $txnId = $ipnData['txn_id'] ?? '';
            $orderId = $this->getOrderIdFromPayPalTransaction($txnId, $ipnData);
            
            if (!$orderId) {
                error_log("Pedido não encontrado para IPN PayPal: " . json_encode($safeData));
                return [
                    'success' => false,
                    'message' => 'Pedido não encontrado'
                ];
            }
            
            // Atualizar status de acordo com a notificação
            switch ($paymentStatus) {
                case 'Completed':
                    $this->updateOrderPaymentStatus($orderId, 'approved', $ipnData);
                    break;
                    
                case 'Refunded':
                case 'Reversed':
                    $this->updateOrderPaymentStatus($orderId, 'refunded', $ipnData);
                    break;
                    
                case 'Failed':
                case 'Denied':
                    $this->updateOrderPaymentStatus($orderId, 'failed', $ipnData);
                    break;
                    
                case 'Pending':
                    $pendingReason = $ipnData['pending_reason'] ?? '';
                    $this->updateOrderPaymentStatus($orderId, 'pending', $ipnData, 
                        "Pagamento pendente: {$pendingReason}");
                    break;
                    
                default:
                    error_log("Status de pagamento IPN não mapeado: {$paymentStatus}");
                    return [
                        'success' => true,
                        'message' => 'Status não processado: ' . $paymentStatus
                    ];
            }
            
            return [
                'success' => true,
                'order_id' => $orderId,
                'txn_id' => $txnId,
                'payment_status' => $paymentStatus
            ];
        } catch (\Exception $e) {
            error_log("Erro ao processar IPN PayPal: " . $e->getMessage());
            
            return [
                'success' => false,
                'error_message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verifica a autenticidade de uma notificação IPN do PayPal
     * 
     * @param array $ipnData Dados recebidos na notificação
     * @return bool Resultado da verificação
     */
    private function verifyPayPalIPN(array $ipnData): bool {
        // Determinar endpoint para verificação
        $isSandbox = $this->gatewayConfigs['paypal']['sandbox'] ?? true;
        $verifyEndpoint = $isSandbox ? 
            'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr' : 
            'https://ipnpb.paypal.com/cgi-bin/webscr';
        
        // Preparar dados para verificação
        $verifyData = 'cmd=_notify-validate&' . http_build_query($ipnData);
        
        // Configurar chamada curl com práticas seguras
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $verifyEndpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $verifyData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => [
                'Connection: close',
                'User-Agent: Taverna-da-Impressao-3D/1.0'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Verificar erros de curl
        if ($error) {
            error_log("Erro de comunicação na verificação de IPN: {$error}");
            return false;
        }
        
        // Verificar resposta
        if ($response === 'VERIFIED') {
            return true;
        } else {
            error_log("Verificação de IPN falhou. Resposta: {$response}");
            return false;
        }
    }
    
    /**
     * Obtém ID do pedido a partir de dados de transação PayPal
     * 
     * @param string $txnId ID da transação PayPal
     * @param array $ipnData Dados completos da notificação
     * @return int|null ID do pedido ou null se não encontrado
     */
    private function getOrderIdFromPayPalTransaction(string $txnId, array $ipnData): ?int {
        try {
            // Verificar custom field que deve conter o ID do pedido
            if (!empty($ipnData['custom'])) {
                $customData = json_decode($ipnData['custom'], true);
                if (isset($customData['order_id'])) {
                    return (int)$customData['order_id'];
                }
            }
            
            // Verificar reference (invoice) que pode conter o número do pedido
            if (!empty($ipnData['invoice'])) {
                $pdo = \Database::getInstance()->getPdo();
                $stmt = $pdo->prepare("SELECT id FROM orders WHERE order_number = ? LIMIT 1");
                $stmt->execute([$ipnData['invoice']]);
                $order = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($order) {
                    return (int)$order['id'];
                }
            }
            
            // Buscar por transação existente com o mesmo ID
            $pdo = \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                SELECT order_id FROM payment_transactions 
                WHERE transaction_id = ? OR additional_data LIKE ? 
                LIMIT 1
            ");
            $stmt->execute([$txnId, '%' . $txnId . '%']);
            $transaction = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($transaction) {
                return (int)$transaction['order_id'];
            }
            
            return null;
        } catch (\PDOException $e) {
            error_log("Erro ao buscar pedido para transação PayPal: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Atualiza status de pagamento de um pedido a partir de IPN
     * 
     * @param int $orderId ID do pedido
     * @param string $paymentStatus Status de pagamento
     * @param array $ipnData Dados completos da notificação
     * @param string $notes Notas adicionais (opcional)
     * @return bool Resultado da operação
     */
    private function updateOrderPaymentStatus(int $orderId, string $paymentStatus, array $ipnData, string $notes = ''): bool {
        try {
            // Preparar dados adicionais seguros
            $additionalData = $this->removeSensitiveData($ipnData);
            
            // Determinar status do pedido
            $orderStatus = $this->getOrderStatusFromPaymentStatus($paymentStatus);
            
            // Atualizar pedido
            $pdo = \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = ?, payment_status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$orderStatus, $paymentStatus, $orderId]);
            
            // Registrar histórico
            $stmt = $pdo->prepare("
                INSERT INTO order_status_history 
                (order_id, status, payment_status, details, notes, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $notesText = $notes ?: "Atualização via IPN PayPal: {$paymentStatus}";
            $stmt->execute([
                $orderId, 
                $orderStatus, 
                $paymentStatus, 
                json_encode($additionalData), 
                $notesText
            ]);
            
            // Atualizar ou criar registro de transação se necessário
            $this->updateOrCreatePayPalTransaction($orderId, $paymentStatus, $ipnData);
            
            return true;
        } catch (\PDOException $e) {
            error_log("Erro ao atualizar status do pedido via IPN: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza ou cria registro de transação PayPal
     * 
     * @param int $orderId ID do pedido
     * @param string $status Status de pagamento
     * @param array $ipnData Dados completos da notificação
     * @return bool Resultado da operação
     */
    private function updateOrCreatePayPalTransaction(int $orderId, string $status, array $ipnData): bool {
        try {
            $pdo = \Database::getInstance()->getPdo();
            $txnId = $ipnData['txn_id'] ?? '';
            
            // Verificar se transação já existe
            $stmt = $pdo->prepare("
                SELECT id FROM payment_transactions 
                WHERE order_id = ? AND (transaction_id = ? OR additional_data LIKE ?)
                LIMIT 1
            ");
            $stmt->execute([$orderId, $txnId, '%' . $txnId . '%']);
            $transaction = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Sanitizar dados
            $amount = isset($ipnData['mc_gross']) ? (float)$ipnData['mc_gross'] : 0;
            $currency = SecurityManager::sanitize($ipnData['mc_currency'] ?? 'BRL');
            $paymentMethod = SecurityManager::sanitize($ipnData['payment_type'] ?? 'paypal');
            $additionalData = json_encode($this->removeSensitiveData($ipnData));
            
            if ($transaction) {
                // Atualizar transação existente
                $stmt = $pdo->prepare("
                    UPDATE payment_transactions 
                    SET status = ?, additional_data = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$status, $additionalData, $transaction['id']]);
            } else {
                // Criar nova transação
                $stmt = $pdo->prepare("
                    INSERT INTO payment_transactions 
                    (order_id, gateway_name, transaction_id, status, amount, currency, payment_method, additional_data, created_at) 
                    VALUES (?, 'paypal', ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$orderId, $txnId, $status, $amount, $currency, $paymentMethod, $additionalData]);
            }
            
            return true;
        } catch (\PDOException $e) {
            error_log("Erro ao atualizar transação PayPal: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém o status do pedido a partir do status de pagamento
     * 
     * @param string $paymentStatus Status do pagamento
     * @return string Status correspondente do pedido
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
}
