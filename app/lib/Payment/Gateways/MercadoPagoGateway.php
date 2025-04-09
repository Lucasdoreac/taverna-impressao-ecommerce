<?php
/**
 * MercadoPagoGateway - Implementação do gateway de pagamento MercadoPago
 * 
 * Implementa a integração com o MercadoPago API v2, suportando
 * cartão de crédito, boleto, PIX e outros métodos.
 * 
 * @package     App\Lib\Payment\Gateways
 * @version     1.0.0
 * @author      Taverna da Impressão
 * @see         https://www.mercadopago.com.br/developers/pt/reference
 */
namespace App\Lib\Payment\Gateways;

use App\Lib\Payment\AbstractPaymentGateway;
use App\Lib\Payment\PaymentGatewayInterface;
use App\Lib\Security\SecurityManager;

class MercadoPagoGateway extends AbstractPaymentGateway implements PaymentGatewayInterface {
    /**
     * @var string URL base da API (produção)
     */
    protected $apiBaseUrl = 'https://api.mercadopago.com/v2';
    
    /**
     * @var string URL base da API (sandbox)
     */
    protected $sandboxApiBaseUrl = 'https://api.mercadopago.com/v2';
    
    /**
     * @var array Mapeamento de métodos de pagamento internos para MercadoPago
     */
    protected $paymentMethodMap = [
        'credit_card' => 'credit_card',
        'boleto' => 'ticket',
        'pix' => 'pix',
        'debit_card' => 'debit_card'
    ];
    
    /**
     * @var array Mapeamento de status de transação MercadoPago para internos
     */
    protected $statusMap = [
        'pending' => 'pending',
        'approved' => 'approved',
        'authorized' => 'authorized',
        'in_process' => 'in_process',
        'in_mediation' => 'in_dispute',
        'rejected' => 'failed',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded',
        'charged_back' => 'charged_back'
    ];
    
    /**
     * Valida as configurações do gateway
     * 
     * @throws \Exception Se configurações estiverem incorretas ou incompletas
     */
    protected function validateConfiguration(): void {
        $requiredFields = ['access_token', 'public_key'];
        
        foreach ($requiredFields as $field) {
            if (!isset($this->config[$field]) || empty($this->config[$field])) {
                throw new \Exception("Configuração obrigatória ausente: {$field}");
            }
        }
    }
    
    /**
     * Retorna o nome do gateway para registro e auditoria
     * 
     * @return string Nome do gateway
     */
    public static function getGatewayName(): string {
        return 'mercadopago';
    }
    
    /**
     * Inicializa uma transação de pagamento
     * 
     * @param array $orderData Dados do pedido (id, número, total, items, etc.)
     * @param array $customerData Dados do cliente (nome, email, cpf, telefone, etc.)
     * @param array $paymentData Dados específicos do pagamento (método, parcelas, etc.)
     * @return array Dados da transação inicializada com chaves 'success', 'transaction_id' e 'redirect_url' (se aplicável)
     * @throws \Exception Em caso de falha na inicialização
     */
    public function initiateTransaction(array $orderData, array $customerData, array $paymentData): array {
        try {
            // Validar dados
            $this->validateOrderData($orderData);
            $this->validateCustomerData($customerData);
            $this->validatePaymentData($paymentData);
            
            // Mapear método de pagamento para MercadoPago
            $paymentMethod = $paymentData['payment_method'] ?? '';
            $mercadoPagoMethod = $this->paymentMethodMap[$paymentMethod] ?? $paymentMethod;
            
            // Preparar dados de pagamento específicos para o método
            $methodSpecificData = $this->prepareMethodSpecificData($paymentMethod, $paymentData);
            
            // Preparar dados da preferência
            $preferenceData = [
                'items' => [
                    [
                        'id' => $orderData['id'],
                        'title' => 'Pedido #' . $orderData['order_number'],
                        'description' => 'Pedido na Taverna da Impressão 3D',
                        'category_id' => 'services',
                        'quantity' => 1,
                        'currency_id' => 'BRL',
                        'unit_price' => (float)$orderData['total']
                    ]
                ],
                'external_reference' => $orderData['order_number'],
                'notification_url' => BASE_URL . 'webhook/mercadopago',
                'back_urls' => [
                    'success' => BASE_URL . 'pedido/sucesso/' . $orderData['order_number'],
                    'pending' => BASE_URL . 'pedido/pendente/' . $orderData['order_number'],
                    'failure' => BASE_URL . 'pedido/falha/' . $orderData['order_number']
                ],
                'auto_return' => 'approved',
                'payment_methods' => [
                    'excluded_payment_methods' => [],
                    'excluded_payment_types' => [],
                    'installments' => 6
                ],
                'payer' => [
                    'name' => $customerData['name'] ?? '',
                    'email' => $customerData['email'] ?? '',
                    'identification' => [
                        'type' => 'CPF',
                        'number' => $customerData['document'] ?? ''
                    ],
                    'phone' => [
                        'area_code' => $customerData['phone_area_code'] ?? '',
                        'number' => $customerData['phone_number'] ?? ''
                    ],
                    'address' => [
                        'zip_code' => $customerData['zipcode'] ?? '',
                        'street_name' => $customerData['address'] ?? '',
                        'street_number' => $customerData['number'] ?? '',
                        'neighborhood' => $customerData['neighborhood'] ?? '',
                        'city' => $customerData['city'] ?? '',
                        'federal_unit' => $customerData['state'] ?? ''
                    ]
                ],
                'statement_descriptor' => 'TAVERNA3D',
                'binary_mode' => true
            ];
            
            // Adicionar dados específicos do método
            $preferenceData = array_merge($preferenceData, $methodSpecificData);
            
            // Criar preferência de pagamento
            $result = $this->sendRequest('POST', '/checkout/preferences', $preferenceData);
            
            if (isset($result['id'])) {
                $responseData = [
                    'success' => true,
                    'transaction_id' => $result['id'],
                    'status' => 'pending'
                ];
                
                // Adicionar URLs específicas baseadas no ambiente
                if ($this->isSandbox) {
                    $responseData['init_point'] = $result['sandbox_init_point'];
                } else {
                    $responseData['init_point'] = $result['init_point'];
                }
                
                // Se for PIX, adicionar QR Code
                if ($paymentMethod === 'pix' && isset($result['point_of_interaction']['transaction_data']['qr_code_base64'])) {
                    $responseData['qr_code'] = $result['point_of_interaction']['transaction_data']['qr_code_base64'];
                    $responseData['qr_code_text'] = $result['point_of_interaction']['transaction_data']['qr_code'];
                }
                
                // Registrar transação
                $this->logTransaction(
                    $orderData['id'],
                    $result['id'],
                    'pending',
                    [
                        'amount' => $orderData['total'],
                        'currency' => 'BRL',
                        'payment_method' => $paymentMethod,
                        'external_reference' => $orderData['order_number']
                    ]
                );
                
                return $responseData;
            } else {
                throw new \Exception("Falha ao criar preferência de pagamento");
            }
        } catch (\Exception $e) {
            // Registrar erro
            error_log("Erro ao iniciar transação MercadoPago: " . $e->getMessage());
            
            // Retornar erro
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Prepara dados específicos para cada método de pagamento
     * 
     * @param string $method Método de pagamento
     * @param array $paymentData Dados de pagamento
     * @return array Dados específicos para o método
     */
    protected function prepareMethodSpecificData(string $method, array $paymentData): array {
        $methodSpecificData = [];
        
        switch ($method) {
            case 'credit_card':
                // Para cartão de crédito
                if (isset($paymentData['installments'])) {
                    $methodSpecificData['payment_methods']['installments'] = $paymentData['installments'];
                }
                
                // Se houver dados do cartão, processar via API de pagamentos direta
                if (isset($paymentData['card_token'])) {
                    $methodSpecificData['payment'] = [
                        'payment_method_id' => $paymentData['card_brand'] ?? 'master',
                        'token' => $paymentData['card_token'],
                        'installments' => $paymentData['installments'] ?? 1,
                        'capture' => true
                    ];
                }
                break;
                
            case 'boleto':
                // Para boleto
                $methodSpecificData['payment_methods']['default_payment_method_id'] = 'bolbradesco';
                
                // Data de vencimento (3 dias úteis)
                $dueDate = new \DateTime();
                $dueDate->modify('+3 weekdays');
                $methodSpecificData['expires'] = true;
                $methodSpecificData['expiration_date_from'] = date('c');
                $methodSpecificData['expiration_date_to'] = $dueDate->format('c');
                break;
                
            case 'pix':
                // Para PIX
                $methodSpecificData['payment_methods']['default_payment_method_id'] = 'pix';
                
                // Expiração do PIX (24 horas)
                $pixExpiration = new \DateTime();
                $pixExpiration->modify('+24 hours');
                $methodSpecificData['expires'] = true;
                $methodSpecificData['expiration_date_from'] = date('c');
                $methodSpecificData['expiration_date_to'] = $pixExpiration->format('c');
                break;
                
            default:
                // Nenhum dado específico adicional
                break;
        }
        
        return $methodSpecificData;
    }
    
    /**
     * Verifica o status de uma transação existente
     * 
     * @param string $transactionId ID da transação a ser verificada
     * @return array Informações atualizadas sobre a transação
     * @throws \Exception Em caso de falha na consulta
     */
    public function checkTransactionStatus(string $transactionId): array {
        try {
            // Verificar se é um pagamento ou preferência
            if (strpos($transactionId, 'pref_') === 0) {
                // É uma preferência
                $result = $this->sendRequest('GET', "/checkout/preferences/{$transactionId}");
                
                if (!isset($result['id'])) {
                    throw new \Exception("Preferência não encontrada: {$transactionId}");
                }
                
                // Buscar pagamentos associados à preferência
                $payments = $this->sendRequest('GET', "/checkout/preferences/{$transactionId}/payments");
                
                $status = 'pending';
                $paymentId = null;
                
                // Processar múltiplos pagamentos, se houver
                if (!empty($payments['elements'])) {
                    // Usar o último pagamento como referência de status
                    $lastPayment = $payments['elements'][0];
                    $status = $lastPayment['status'] ?? 'pending';
                    $paymentId = $lastPayment['id'] ?? null;
                }
                
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'payment_id' => $paymentId,
                    'status' => $this->mapStatus($status),
                    'raw_status' => $status,
                    'additional_info' => $result
                ];
            } else {
                // É um pagamento
                $result = $this->sendRequest('GET', "/payments/{$transactionId}");
                
                if (!isset($result['id'])) {
                    throw new \Exception("Pagamento não encontrado: {$transactionId}");
                }
                
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'status' => $this->mapStatus($result['status']),
                    'raw_status' => $result['status'],
                    'payment_method' => $result['payment_method_id'] ?? null,
                    'payment_type' => $result['payment_type_id'] ?? null,
                    'amount' => $result['transaction_amount'] ?? 0,
                    'refunded_amount' => $result['refunded_amount'] ?? 0,
                    'external_reference' => $result['external_reference'] ?? null,
                    'date_created' => $result['date_created'] ?? null,
                    'date_approved' => $result['date_approved'] ?? null,
                    'date_last_updated' => $result['date_last_updated'] ?? null
                ];
            }
        } catch (\Exception $e) {
            // Registrar erro
            error_log("Erro ao verificar status da transação MercadoPago: " . $e->getMessage());
            
            // Retornar erro
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Processa um callback/webhook do gateway de pagamento
     * 
     * @param array $requestData Dados recebidos no callback (geralmente $_POST ou corpo da requisição)
     * @return array Informações processadas com chave 'success' indicando resultado
     * @throws \Exception Em caso de falha no processamento
     */
    public function handleCallback(array $requestData): array {
        try {
            // Verificar tipo de notificação
            $type = $requestData['type'] ?? $requestData['action'] ?? '';
            
            if ($type === 'payment') {
                // Notificação de pagamento
                $paymentId = $requestData['data']['id'] ?? $requestData['id'] ?? null;
                
                if (!$paymentId) {
                    throw new \Exception("ID de pagamento não fornecido no webhook");
                }
                
                // Obter detalhes do pagamento
                $payment = $this->sendRequest('GET', "/payments/{$paymentId}");
                
                if (!isset($payment['id'])) {
                    throw new \Exception("Pagamento não encontrado: {$paymentId}");
                }
                
                // Extrair informações relevantes
                $status = $payment['status'] ?? 'pending';
                $externalReference = $payment['external_reference'] ?? '';
                $transactionAmount = $payment['transaction_amount'] ?? 0;
                $paymentMethod = $payment['payment_method_id'] ?? '';
                $paymentType = $payment['payment_type_id'] ?? '';
                
                // Mapear status para formato interno
                $mappedStatus = $this->mapStatus($status);
                
                // Registrar dados do callback
                $callbackData = [
                    'payment_id' => $paymentId,
                    'status' => $mappedStatus,
                    'raw_status' => $status,
                    'external_reference' => $externalReference,
                    'transaction_amount' => $transactionAmount,
                    'payment_method' => $paymentMethod,
                    'payment_type' => $paymentType
                ];
                
                // Atualizar registro de transação
                $this->updateTransactionStatus($paymentId, $mappedStatus, $callbackData);
                
                // Atualizar status do pedido no sistema principal
                if (!empty($externalReference)) {
                    $this->updateOrderStatus($externalReference, $mappedStatus, $payment);
                }
                
                return [
                    'success' => true,
                    'status' => $mappedStatus,
                    'payment_id' => $paymentId,
                    'external_reference' => $externalReference
                ];
            } else {
                // Outro tipo de notificação (merchant_order, chargebacks, etc.)
                return [
                    'success' => true,
                    'message' => "Notificação do tipo '{$type}' recebida",
                    'action' => 'no_action_required'
                ];
            }
        } catch (\Exception $e) {
            // Registrar erro
            error_log("Erro ao processar webhook MercadoPago: " . $e->getMessage());
            
            // Retornar erro
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Atualiza o status do pedido no sistema principal
     * 
     * @param string $orderNumber Número do pedido
     * @param string $paymentStatus Status do pagamento (formato interno)
     * @param array $paymentDetails Detalhes completos do pagamento
     * @return bool Sucesso da operação
     */
    protected function updateOrderStatus(string $orderNumber, string $paymentStatus, array $paymentDetails): bool {
        try {
            // Mapear status de pagamento para status de pedido
            $orderStatus = 'pending';
            $paymentStatusDb = 'pending';
            
            switch ($paymentStatus) {
                case 'approved':
                case 'authorized':
                    $orderStatus = 'processing';
                    $paymentStatusDb = 'approved';
                    break;
                    
                case 'pending':
                case 'in_process':
                    $orderStatus = 'pending';
                    $paymentStatusDb = 'pending';
                    break;
                    
                case 'failed':
                case 'rejected':
                    $orderStatus = 'failed';
                    $paymentStatusDb = 'failed';
                    break;
                    
                case 'cancelled':
                    $orderStatus = 'cancelled';
                    $paymentStatusDb = 'cancelled';
                    break;
                    
                case 'refunded':
                    $orderStatus = 'refunded';
                    $paymentStatusDb = 'refunded';
                    break;
                    
                case 'charged_back':
                    $orderStatus = 'disputed';
                    $paymentStatusDb = 'charged_back';
                    break;
            }
            
            // Atualizar pedido na base de dados
            $pdo = \Database::getInstance()->getPdo();
            
            // Obter ID do pedido pelo número
            $stmt = $pdo->prepare("SELECT id FROM orders WHERE order_number = ?");
            $stmt->execute([$orderNumber]);
            $order = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$order) {
                error_log("Pedido não encontrado para atualização: {$orderNumber}");
                return false;
            }
            
            $orderId = $order['id'];
            
            // Atualizar status do pedido
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = ?, payment_status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$orderStatus, $paymentStatusDb, $orderId]);
            
            // Registrar log de mudança de status
            $stmt = $pdo->prepare("
                INSERT INTO order_status_history 
                (order_id, status, payment_status, notes, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $notes = "Atualização automática via webhook MercadoPago. ID Pagamento: " . ($paymentDetails['id'] ?? '');
            $stmt->execute([$orderId, $orderStatus, $paymentStatusDb, $notes]);
            
            return true;
        } catch (\PDOException $e) {
            error_log("Erro ao atualizar status do pedido: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancela uma transação existente
     * 
     * @param string $transactionId ID da transação a ser cancelada
     * @param string $reason Motivo do cancelamento (opcional)
     * @return array Resultado do cancelamento com chave 'success' indicando resultado
     * @throws \Exception Em caso de falha no cancelamento
     */
    public function cancelTransaction(string $transactionId, string $reason = ''): array {
        try {
            // Verificar status atual
            $statusCheck = $this->checkTransactionStatus($transactionId);
            
            if (!($statusCheck['success'] ?? false)) {
                throw new \Exception("Transação não encontrada ou erro ao verificar status");
            }
            
            $currentStatus = $statusCheck['status'] ?? '';
            
            // Só é possível cancelar transações pendentes ou em processo
            if (!in_array($currentStatus, ['pending', 'in_process'])) {
                throw new \Exception("Não é possível cancelar transação com status '{$currentStatus}'");
            }
            
            // Cancelar via API
            $result = $this->sendRequest('PUT', "/payments/{$transactionId}", [
                'status' => 'cancelled'
            ]);
            
            if (isset($result['id'])) {
                // Atualizar status localmente
                $this->updateTransactionStatus($transactionId, 'cancelled', [
                    'cancellation_reason' => $reason,
                    'cancelled_at' => date('c')
                ]);
                
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'status' => 'cancelled'
                ];
            } else {
                throw new \Exception("Falha ao cancelar transação");
            }
        } catch (\Exception $e) {
            // Registrar erro
            error_log("Erro ao cancelar transação MercadoPago: " . $e->getMessage());
            
            // Retornar erro
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Reembolsa uma transação existente (total ou parcial)
     * 
     * @param string $transactionId ID da transação a ser reembolsada
     * @param float|null $amount Valor a ser reembolsado (opcional, se null faz reembolso total)
     * @param string $reason Motivo do reembolso (opcional)
     * @return array Resultado do reembolso com chave 'success' indicando resultado
     * @throws \Exception Em caso de falha no reembolso
     */
    public function refundTransaction(string $transactionId, ?float $amount = null, string $reason = ''): array {
        try {
            // Verificar status atual
            $statusCheck = $this->checkTransactionStatus($transactionId);
            
            if (!($statusCheck['success'] ?? false)) {
                throw new \Exception("Transação não encontrada ou erro ao verificar status");
            }
            
            $currentStatus = $statusCheck['status'] ?? '';
            
            // Só é possível reembolsar transações aprovadas
            if ($currentStatus !== 'approved') {
                throw new \Exception("Não é possível reembolsar transação com status '{$currentStatus}'");
            }
            
            $requestData = [];
            
            // Se amount estiver definido, é reembolso parcial
            if ($amount !== null && $amount > 0) {
                $requestData['amount'] = $amount;
                $endpoint = "/payments/{$transactionId}/refunds";
            } else {
                // Reembolso total
                $endpoint = "/payments/{$transactionId}/refunds";
            }
            
            // Executar reembolso
            $result = $this->sendRequest('POST', $endpoint, $requestData);
            
            if (isset($result['id'])) {
                // Determinar novo status (total ou parcial)
                $newStatus = ($amount === null) ? 'refunded' : 'partially_refunded';
                
                // Atualizar status localmente
                $this->updateTransactionStatus($transactionId, $newStatus, [
                    'refund_id' => $result['id'],
                    'refund_amount' => $amount ?? $statusCheck['amount'] ?? 0,
                    'refund_reason' => $reason,
                    'refunded_at' => date('c')
                ]);
                
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'refund_id' => $result['id'],
                    'status' => $newStatus,
                    'amount' => $amount
                ];
            } else {
                throw new \Exception("Falha ao reembolsar transação");
            }
        } catch (\Exception $e) {
            // Registrar erro
            error_log("Erro ao reembolsar transação MercadoPago: " . $e->getMessage());
            
            // Retornar erro
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Gera um token para uso futuro (comum em cartões de crédito)
     * 
     * @param array $cardData Dados do cartão a ser tokenizado
     * @return string Token gerado
     * @throws \Exception Em caso de falha na tokenização
     */
    public function generateToken(array $cardData): string {
        throw new \Exception("Geração de token deve ser realizada no frontend com SDK MercadoPago");
    }
    
    /**
     * Obtém dados de configuração necessários para o frontend
     * 
     * @param string $paymentMethod Método de pagamento específico (opcional)
     * @return array Configurações para o frontend (chaves públicas, IDs, etc.)
     */
    public function getFrontendConfig(?string $paymentMethod = null): array {
        // Configurações base
        $config = [
            'public_key' => $this->config['public_key'],
            'site_id' => 'MLB', // Brasil
            'is_sandbox' => $this->isSandbox
        ];
        
        // Adicionar configurações específicas por método
        if ($paymentMethod !== null) {
            switch ($paymentMethod) {
                case 'credit_card':
                    $config['installments_max'] = 12;
                    $config['supported_brands'] = ['visa', 'mastercard', 'amex', 'elo', 'hipercard'];
                    break;
                    
                case 'pix':
                    $config['expiration_minutes'] = 1440; // 24 horas
                    break;
                    
                case 'boleto':
                    $config['expiration_days'] = 3;
                    break;
            }
        }
        
        return $config;
    }
    
    /**
     * Mapeia status do MercadoPago para nomenclatura interna
     * 
     * @param string $mercadoPagoStatus Status original do MercadoPago
     * @return string Status mapeado para formato interno
     */
    protected function mapStatus(string $mercadoPagoStatus): string {
        return $this->statusMap[$mercadoPagoStatus] ?? $mercadoPagoStatus;
    }
    
    /**
     * Envia requisição para a API do MercadoPago
     * 
     * @param string $method Método HTTP (GET, POST, PUT, etc.)
     * @param string $endpoint Endpoint da API (começando com /)
     * @param array $data Dados a serem enviados (para POST, PUT)
     * @return array Resposta da API
     * @throws \Exception Em caso de erro na requisição
     */
    protected function sendRequest(string $method, string $endpoint, array $data = []): array {
        // Determinar URL base conforme ambiente
        $baseUrl = $this->isSandbox ? $this->sandboxApiBaseUrl : $this->apiBaseUrl;
        
        // Configurar curl
        $curl = curl_init();
        
        $url = $baseUrl . $endpoint;
        $headers = [
            'Authorization: Bearer ' . $this->config['access_token'],
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: TavernaDaImpressao3D/1.0'
        ];
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true
        ];
        
        // Adicionar dados para métodos POST e PUT
        if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($data)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        
        curl_setopt_array($curl, $options);
        
        // Executar requisição
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        // Verificar erros de curl
        if ($error) {
            $this->logApiInteraction($endpoint, $data, ['error' => $error], false);
            throw new \Exception("Erro de comunicação com MercadoPago: {$error}");
        }
        
        // Decodificar resposta
        $responseData = json_decode($response, true);
        
        // Verificar erros de API
        if ($statusCode >= 400 || !is_array($responseData)) {
            $errorMessage = isset($responseData['message']) ? $responseData['message'] : "Erro na API MercadoPago (HTTP {$statusCode})";
            $errorCode = isset($responseData['error']) ? $responseData['error'] : $statusCode;
            
            $this->logApiInteraction($endpoint, $data, $responseData ?? ['raw' => $response], false);
            throw new \Exception($errorMessage, $errorCode);
        }
        
        // Registrar interação bem-sucedida
        $this->logApiInteraction($endpoint, $data, $responseData, true);
        
        return $responseData;
    }
}
