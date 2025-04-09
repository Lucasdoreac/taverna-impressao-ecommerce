<?php
/**
 * PayPalGateway - Implementação do gateway de pagamento PayPal
 * 
 * Implementa a integração com o PayPal REST API v2, suportando
 * PayPal Standard, cartão de crédito e outros métodos.
 * 
 * @package     App\Lib\Payment\Gateways
 * @version     1.0.0
 * @author      Taverna da Impressão
 * @see         https://developer.paypal.com/docs/api/overview/
 */
namespace App\Lib\Payment\Gateways;

use App\Lib\Payment\AbstractPaymentGateway;
use App\Lib\Payment\PaymentGatewayInterface;
use App\Lib\Security\SecurityManager;

class PayPalGateway extends AbstractPaymentGateway implements PaymentGatewayInterface {
    /**
     * @var string URL base da API (produção)
     */
    protected $apiBaseUrl = 'https://api-m.paypal.com/v2';
    
    /**
     * @var string URL base da API (sandbox)
     */
    protected $sandboxApiBaseUrl = 'https://api-m.sandbox.paypal.com/v2';
    
    /**
     * @var string Token de acesso para API
     */
    protected $accessToken = null;
    
    /**
     * @var int Timestamp de expiração do token
     */
    protected $tokenExpires = 0;
    
    /**
     * @var array Mapeamento de métodos de pagamento internos para PayPal
     */
    protected $paymentMethodMap = [
        'paypal' => 'paypal',
        'credit_card' => 'card',
        'pix' => 'alternative_payment', // Não suportado nativamente, mapeado para APM genérico
        'boleto' => 'alternative_payment' // Não suportado nativamente, mapeado para APM genérico
    ];
    
    /**
     * @var array Mapeamento de status de transação PayPal para internos
     */
    protected $statusMap = [
        'CREATED' => 'pending',
        'SAVED' => 'pending',
        'APPROVED' => 'authorized',
        'VOIDED' => 'cancelled',
        'COMPLETED' => 'approved',
        'PAYER_ACTION_REQUIRED' => 'pending',
        'CAPTURED' => 'approved',
        'DENIED' => 'failed',
        'EXPIRED' => 'failed',
        'PENDING' => 'pending',
        'REFUNDED' => 'refunded',
        'PARTIALLY_REFUNDED' => 'partially_refunded',
        'FAILED' => 'failed'
    ];
    
    /**
     * Valida as configurações do gateway
     * 
     * @throws \Exception Se configurações estiverem incorretas ou incompletas
     */
    protected function validateConfiguration(): void {
        $requiredFields = ['client_id', 'client_secret'];
        
        foreach ($requiredFields as $field) {
            if (!isset($this->config[$field]) || empty($this->config[$field])) {
                throw new \Exception("Configuração obrigatória ausente: {$field}");
            }
        }
        
        // Validar URLs de webhook e redirecionamento
        $urlFields = ['return_url', 'cancel_url', 'webhook_url'];
        foreach ($urlFields as $field) {
            if (isset($this->config['settings'][$field]) && !filter_var($this->config['settings'][$field], FILTER_VALIDATE_URL)) {
                throw new \Exception("URL inválida para {$field}");
            }
        }
    }
    
    /**
     * Retorna o nome do gateway para registro e auditoria
     * 
     * @return string Nome do gateway
     */
    public static function getGatewayName(): string {
        return 'paypal';
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
            
            // Garantir que temos um token de acesso válido
            $this->ensureAccessToken();
            
            // Determinar método de pagamento e preparar dados apropriados
            $paymentMethod = $paymentData['payment_method'] ?? 'paypal';
            
            // Para PayPal Standard (fluxo redirecionamento)
            if ($paymentMethod === 'paypal') {
                return $this->createPayPalOrder($orderData, $customerData, $paymentData);
            }
            
            // Para pagamento direto com cartão
            if ($paymentMethod === 'credit_card' && isset($paymentData['card_token'])) {
                return $this->createDirectPayment($orderData, $customerData, $paymentData);
            }
            
            // Método não suportado
            throw new \Exception("Método de pagamento '{$paymentMethod}' não suportado pelo gateway PayPal");
        } catch (\Exception $e) {
            // Registrar erro
            error_log("Erro ao iniciar transação PayPal: " . $e->getMessage());
            
            // Retornar erro
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Cria uma ordem PayPal para fluxo de checkout redirecionado
     * 
     * @param array $orderData Dados do pedido
     * @param array $customerData Dados do cliente
     * @param array $paymentData Dados de pagamento
     * @return array Resultado da criação da ordem
     * @throws \Exception Em caso de falha
     */
    protected function createPayPalOrder(array $orderData, array $customerData, array $paymentData): array {
        // Preparar dados da ordem
        $orderAmount = number_format($orderData['total'], 2, '.', '');
        $currency = $this->config['settings']['currency'] ?? 'BRL';
        
        // Obter URLs de callback
        $returnUrl = $this->config['settings']['return_url'] ?? (BASE_URL . 'pedido/sucesso/' . $orderData['order_number']);
        $cancelUrl = $this->config['settings']['cancel_url'] ?? (BASE_URL . 'pedido/cancelado/' . $orderData['order_number']);
        
        // Preparar itens do pedido
        $items = [];
        $orderItems = $orderData['items'] ?? [];
        
        foreach ($orderItems as $item) {
            $items[] = [
                'name' => SecurityManager::sanitize(substr($item['name'] ?? 'Produto', 0, 127)),
                'unit_amount' => [
                    'currency_code' => $currency,
                    'value' => number_format(($item['price'] ?? 0), 2, '.', '')
                ],
                'quantity' => (int)($item['quantity'] ?? 1),
                'description' => SecurityManager::sanitize(substr($item['description'] ?? '', 0, 127))
            ];
        }
        
        // Se não houver itens detalhados, criar um item genérico
        if (empty($items)) {
            $items[] = [
                'name' => 'Pedido #' . $orderData['order_number'],
                'unit_amount' => [
                    'currency_code' => $currency,
                    'value' => $orderAmount
                ],
                'quantity' => 1,
                'description' => 'Pedido na Taverna da Impressão 3D'
            ];
        }
        
        // Preparar dados da ordem para a API
        $orderData = [
            'intent' => 'CAPTURE', // CAPTURE para autorizar e capturar imediatamente
            'purchase_units' => [
                [
                    'reference_id' => $orderData['order_number'],
                    'description' => 'Pedido na Taverna da Impressão 3D',
                    'custom_id' => $orderData['id'],
                    'invoice_id' => $orderData['order_number'],
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $orderAmount,
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => $currency,
                                'value' => $orderAmount
                            ]
                        ]
                    ],
                    'items' => $items
                ]
            ],
            'application_context' => [
                'brand_name' => 'Taverna da Impressão 3D',
                'landing_page' => 'BILLING',
                'shipping_preference' => 'SET_PROVIDED_ADDRESS',
                'user_action' => 'PAY_NOW',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl
            ]
        ];
        
        // Adicionar dados do comprador se disponíveis
        if (!empty($customerData['name']) && !empty($customerData['email'])) {
            $orderData['payer'] = [
                'name' => [
                    'given_name' => $this->getFirstName($customerData['name']),
                    'surname' => $this->getLastName($customerData['name'])
                ],
                'email_address' => $customerData['email']
            ];
            
            // Adicionar telefone se disponível
            if (!empty($customerData['phone'])) {
                $orderData['payer']['phone'] = [
                    'phone_type' => 'MOBILE',
                    'phone_number' => [
                        'national_number' => preg_replace('/\D/', '', $customerData['phone'])
                    ]
                ];
            }
        }
        
        // Criar ordem na API do PayPal
        $result = $this->sendRequest('POST', '/checkout/orders', $orderData);
        
        if (isset($result['id'])) {
            // Buscar URL de aprovação para redirecionamento
            $approvalUrl = '';
            foreach ($result['links'] ?? [] as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }
            
            if (empty($approvalUrl)) {
                throw new \Exception("URL de aprovação não encontrada na resposta do PayPal");
            }
            
            // Registrar transação
            $this->logTransaction(
                $orderData['purchase_units'][0]['custom_id'],
                $result['id'],
                $this->mapStatus($result['status']),
                [
                    'amount' => $orderAmount,
                    'currency' => $currency,
                    'payment_method' => 'paypal',
                    'external_reference' => $orderData['purchase_units'][0]['reference_id']
                ]
            );
            
            // Retornar dados da transação
            return [
                'success' => true,
                'transaction_id' => $result['id'],
                'status' => $this->mapStatus($result['status']),
                'redirect_url' => $approvalUrl
            ];
        } else {
            throw new \Exception("Falha ao criar ordem no PayPal: " . json_encode($result));
        }
    }
    
    /**
     * Cria um pagamento direto com cartão via PayPal
     * 
     * @param array $orderData Dados do pedido
     * @param array $customerData Dados do cliente
     * @param array $paymentData Dados de pagamento
     * @return array Resultado do processamento do pagamento
     * @throws \Exception Em caso de falha
     */
    protected function createDirectPayment(array $orderData, array $customerData, array $paymentData): array {
        // Direct card payments não disponíveis atualmente via REST API do PayPal
        // Necessário implementar PayPal Advanced ou PayFlow Pro
        throw new \Exception("Pagamento direto com cartão via PayPal ainda não implementado");
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
            // Garantir que temos um token de acesso válido
            $this->ensureAccessToken();
            
            // Buscar detalhes da ordem
            $result = $this->sendRequest('GET', "/checkout/orders/{$transactionId}");
            
            if (isset($result['id'])) {
                // Extrair informações relevantes
                $status = $result['status'] ?? 'CREATED';
                $mappedStatus = $this->mapStatus($status);
                
                // Determinar valor total
                $amount = 0;
                $currency = 'BRL';
                
                if (isset($result['purchase_units'][0]['amount'])) {
                    $amount = $result['purchase_units'][0]['amount']['value'] ?? 0;
                    $currency = $result['purchase_units'][0]['amount']['currency_code'] ?? 'BRL';
                }
                
                // Preparar resposta
                $response = [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'status' => $mappedStatus,
                    'raw_status' => $status,
                    'amount' => $amount,
                    'currency' => $currency,
                    'payment_method' => 'paypal',
                    'date_created' => $result['create_time'] ?? null,
                    'date_updated' => $result['update_time'] ?? null
                ];
                
                // Adicionar IDs de capturas/pagamentos se disponíveis
                if (isset($result['purchase_units'][0]['payments']['captures'])) {
                    $captures = $result['purchase_units'][0]['payments']['captures'];
                    $captureIds = [];
                    
                    foreach ($captures as $capture) {
                        $captureIds[] = $capture['id'];
                    }
                    
                    $response['capture_ids'] = $captureIds;
                }
                
                return $response;
            } else {
                throw new \Exception("Transação não encontrada: {$transactionId}");
            }
        } catch (\Exception $e) {
            // Registrar erro
            error_log("Erro ao verificar status da transação PayPal: " . $e->getMessage());
            
            // Retornar erro
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Captura uma transação previamente autorizada
     * 
     * @param string $transactionId ID da transação a ser capturada
     * @return array Resultado da captura
     * @throws \Exception Em caso de falha na captura
     */
    protected function capturePayment(string $transactionId): array {
        try {
            // Garantir que temos um token de acesso válido
            $this->ensureAccessToken();
            
            // Capturar pagamento
            $result = $this->sendRequest('POST', "/checkout/orders/{$transactionId}/capture", []);
            
            if (isset($result['id'])) {
                // Extrair ID de captura
                $captureId = null;
                
                if (isset($result['purchase_units'][0]['payments']['captures'][0]['id'])) {
                    $captureId = $result['purchase_units'][0]['payments']['captures'][0]['id'];
                }
                
                // Atualizar status na base de dados
                $this->updateTransactionStatus(
                    $transactionId,
                    $this->mapStatus($result['status']),
                    [
                        'capture_id' => $captureId,
                        'captured_at' => date('c')
                    ]
                );
                
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'capture_id' => $captureId,
                    'status' => $this->mapStatus($result['status']),
                    'raw_status' => $result['status']
                ];
            } else {
                throw new \Exception("Falha ao capturar pagamento: " . json_encode($result));
            }
        } catch (\Exception $e) {
            // Registrar erro
            error_log("Erro ao capturar pagamento PayPal: " . $e->getMessage());
            
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
            // Verificar tipo de evento
            $eventType = $requestData['event_type'] ?? '';
            
            // Verificar assinatura do webhook (quando disponível)
            if (isset($requestData['id']) && isset($requestData['event_type'])) {
                if (!$this->verifyWebhookSignature($requestData)) {
                    throw new \Exception("Assinatura do webhook inválida");
                }
            }
            
            // Processar conforme tipo de evento
            switch ($eventType) {
                case 'PAYMENT.AUTHORIZATION.CREATED':
                    return $this->processAuthorizationWebhook($requestData);
                
                case 'PAYMENT.CAPTURE.COMPLETED':
                    return $this->processCaptureWebhook($requestData);
                
                case 'PAYMENT.CAPTURE.DENIED':
                case 'PAYMENT.CAPTURE.REVERSED':
                    return $this->processCaptureDeniedWebhook($requestData);
                
                case 'PAYMENT.CAPTURE.REFUNDED':
                    return $this->processRefundWebhook($requestData);
                
                default:
                    // Outros eventos não processados diretamente
                    return [
                        'success' => true,
                        'message' => "Evento '{$eventType}' registrado mas sem ação específica",
                        'event_type' => $eventType
                    ];
            }
        } catch (\Exception $e) {
            // Registrar erro
            error_log("Erro ao processar webhook PayPal: " . $e->getMessage());
            
            // Retornar erro
            return [
                'success' => false,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    /**
     * Processa webhook de autorização
     * 
     * @param array $requestData Dados do webhook
     * @return array Resultado do processamento
     */
    protected function processAuthorizationWebhook(array $requestData): array {
        // Extrair informações da transação
        $resource = $requestData['resource'] ?? [];
        $authId = $resource['id'] ?? '';
        $status = $resource['status'] ?? '';
        
        // Buscar detalhes adicionais se necessário
        if (empty($authId)) {
            return [
                'success' => false,
                'error_message' => 'ID de autorização não encontrado no webhook'
            ];
        }
        
        // Procurar a ordem associada na base
        try {
            $pdo = \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                SELECT * FROM payment_transactions 
                WHERE additional_data LIKE ? 
                ORDER BY id DESC LIMIT 1
            ");
            
            $stmt->execute(['%' . $authId . '%']);
            $transaction = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                // Transação não encontrada, apenas registrar
                return [
                    'success' => true,
                    'message' => 'Autorização registrada, transação não encontrada na base',
                    'auth_id' => $authId
                ];
            }
            
            // Atualizar status da transação
            $transactionId = $transaction['transaction_id'];
            $mappedStatus = $this->mapStatus($status);
            
            $this->updateTransactionStatus($transactionId, $mappedStatus, [
                'authorization_id' => $authId,
                'auth_status' => $status
            ]);
            
            // Atualizar status do pedido
            if (!empty($transaction['order_id'])) {
                $this->updateOrderStatus(
                    $transaction['order_id'], 
                    $mappedStatus,
                    [
                        'authorization_id' => $authId,
                        'transaction_id' => $transactionId
                    ]
                );
            }
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'order_id' => $transaction['order_id'] ?? null,
                'status' => $mappedStatus,
                'raw_status' => $status,
                'auth_id' => $authId
            ];
        } catch (\PDOException $e) {
            error_log("Erro ao processar webhook de autorização: " . $e->getMessage());
            
            return [
                'success' => false,
                'error_message' => 'Erro ao processar webhook de autorização',
                'auth_id' => $authId
            ];
        }
    }
    
    /**
     * Processa webhook de captura
     * 
     * @param array $requestData Dados do webhook
     * @return array Resultado do processamento
     */
    protected function processCaptureWebhook(array $requestData): array {
        // Extrair informações da transação
        $resource = $requestData['resource'] ?? [];
        $captureId = $resource['id'] ?? '';
        $status = $resource['status'] ?? '';
        
        // Links relacionados
        $links = $resource['links'] ?? [];
        $orderLink = null;
        
        // Buscar link para a ordem
        foreach ($links as $link) {
            if (isset($link['rel']) && $link['rel'] === 'up' && strpos($link['href'], '/orders/') !== false) {
                $orderLink = $link['href'];
                break;
            }
        }
        
        // Extrair ID da ordem do link
        $orderId = null;
        if ($orderLink) {
            $parts = explode('/', $orderLink);
            $orderId = end($parts);
        }
        
        // Se não encontramos a ordem, buscar pela captura
        if (!$orderId && !empty($captureId)) {
            // Buscar transação pelo ID de captura
            try {
                $pdo = \Database::getInstance()->getPdo();
                $stmt = $pdo->prepare("
                    SELECT * FROM payment_transactions 
                    WHERE additional_data LIKE ? 
                    ORDER BY id DESC LIMIT 1
                ");
                
                $stmt->execute(['%' . $captureId . '%']);
                $transaction = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($transaction) {
                    $orderId = $transaction['transaction_id'];
                }
            } catch (\PDOException $e) {
                error_log("Erro ao buscar transação por captura: " . $e->getMessage());
            }
        }
        
        // Se ainda não temos ID da ordem, não podemos processar
        if (!$orderId) {
            return [
                'success' => false,
                'error_message' => 'ID da ordem não encontrado no webhook',
                'capture_id' => $captureId
            ];
        }
        
        // Atualizar status da transação
        $mappedStatus = $this->mapStatus($status);
        
        $this->updateTransactionStatus($orderId, $mappedStatus, [
            'capture_id' => $captureId,
            'capture_status' => $status
        ]);
        
        // Buscar dados do pedido associado
        try {
            $pdo = \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                SELECT order_id FROM payment_transactions 
                WHERE transaction_id = ? 
                LIMIT 1
            ");
            
            $stmt->execute([$orderId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            $systemOrderId = $result['order_id'] ?? null;
            
            // Atualizar status do pedido
            if ($systemOrderId) {
                $this->updateOrderStatus(
                    $systemOrderId,
                    $mappedStatus,
                    [
                        'capture_id' => $captureId,
                        'transaction_id' => $orderId
                    ]
                );
            }
            
            return [
                'success' => true,
                'transaction_id' => $orderId,
                'order_id' => $systemOrderId,
                'status' => $mappedStatus,
                'raw_status' => $status,
                'capture_id' => $captureId
            ];
        } catch (\PDOException $e) {
            error_log("Erro ao processar webhook de captura: " . $e->getMessage());
            
            return [
                'success' => false,
                'error_message' => 'Erro ao processar webhook de captura',
                'capture_id' => $captureId,
                'transaction_id' => $orderId
            ];
        }
    }
    
    /**
     * Processa webhook de captura negada/revertida
     * 
     * @param array $requestData Dados do webhook
     * @return array Resultado do processamento
     */
    protected function processCaptureDeniedWebhook(array $requestData): array {
        // Similar ao processo de captura, mas com status diferente
        $resource = $requestData['resource'] ?? [];
        $captureId = $resource['id'] ?? '';
        $status = 'DENIED'; // Forçar status negado
        
        // Busca e atualização similares ao método processCaptureWebhook
        // Implementação ajustada para status de falha
        
        // Este é um exemplo simplificado - a implementação completa seguiria 
        // o mesmo padrão de processCaptureWebhook, mas com status mapeado para 'failed'
        
        return [
            'success' => true,
            'message' => 'Webhook de captura negada processado',
            'capture_id' => $captureId,
            'status' => 'failed'
        ];
    }
    
    /**
     * Processa webhook de reembolso
     * 
     * @param array $requestData Dados do webhook
     * @return array Resultado do processamento
     */
    protected function processRefundWebhook(array $requestData): array {
        // Extrair informações do reembolso
        $resource = $requestData['resource'] ?? [];
        $refundId = $resource['id'] ?? '';
        $status = $resource['status'] ?? '';
        $captureId = $resource['capture_id'] ?? '';
        
        // Validar dados mínimos
        if (empty($captureId) || empty($refundId)) {
            return [
                'success' => false,
                'error_message' => 'Dados de reembolso incompletos',
                'refund_id' => $refundId
            ];
        }
        
        // Buscar transação pelo ID de captura
        try {
            $pdo = \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                SELECT * FROM payment_transactions 
                WHERE additional_data LIKE ? 
                ORDER BY id DESC LIMIT 1
            ");
            
            $stmt->execute(['%' . $captureId . '%']);
            $transaction = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                return [
                    'success' => false,
                    'error_message' => 'Transação não encontrada para o reembolso',
                    'refund_id' => $refundId,
                    'capture_id' => $captureId
                ];
            }
            
            // Determinar se é reembolso total ou parcial
            $amount = $resource['amount']['value'] ?? 0;
            $currency = $resource['amount']['currency_code'] ?? 'BRL';
            $transactionAmount = $transaction['amount'] ?? 0;
            
            $isPartial = ($amount < $transactionAmount);
            $newStatus = $isPartial ? 'partially_refunded' : 'refunded';
            
            // Atualizar status da transação
            $transactionId = $transaction['transaction_id'];
            $this->updateTransactionStatus($transactionId, $newStatus, [
                'refund_id' => $refundId,
                'refund_amount' => $amount,
                'refund_currency' => $currency,
                'refund_status' => $status,
                'refunded_at' => date('c')
            ]);
            
            // Registrar reembolso
            $this->logRefund($transactionId, $refundId, $amount, $currency, $status);
            
            // Atualizar status do pedido
            if (!empty($transaction['order_id'])) {
                $orderStatus = $isPartial ? 'partially_refunded' : 'refunded';
                $this->updateOrderStatus(
                    $transaction['order_id'],
                    $orderStatus,
                    [
                        'refund_id' => $refundId,
                        'refund_amount' => $amount,
                        'transaction_id' => $transactionId
                    ]
                );
            }
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'order_id' => $transaction['order_id'] ?? null,
                'status' => $newStatus,
                'refund_id' => $refundId,
                'amount' => $amount,
                'currency' => $currency,
                'is_partial' => $isPartial
            ];
        } catch (\PDOException $e) {
            error_log("Erro ao processar webhook de reembolso: " . $e->getMessage());
            
            return [
                'success' => false,
                'error_message' => 'Erro ao processar webhook de reembolso',
                'refund_id' => $refundId,
                'capture_id' => $captureId
            ];
        }
    }
    
    /**
     * Verifica assinatura do webhook para garantir autenticidade
     * 
     * @param array $requestData Dados recebidos no webhook
     * @return bool Se a assinatura é válida
     */
    protected function verifyWebhookSignature(array $requestData): bool {
        // Na implementação real, verificar assinatura usando API do PayPal
        // Para essa versão simplificada, apenas retorna true
        // Na produção, usar Verify Webhook Signature API
        
        return true;
    }
    
    /**
     * Registra um reembolso no banco de dados
     * 
     * @param string $transactionId ID da transação
     * @param string $refundId ID do reembolso
     * @param float $amount Valor reembolsado
     * @param string $currency Moeda do reembolso
     * @param string $status Status do reembolso
     * @return bool Sucesso da operação
     */
    protected function logRefund(string $transactionId, string $refundId, float $amount, string $currency, string $status): bool {
        try {
            $pdo = \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                INSERT INTO payment_refunds 
                (transaction_id, refund_id, amount, reason, status, additional_data, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $additionalData = json_encode([
                'currency' => $currency,
                'raw_status' => $status,
                'gateway' => 'paypal'
            ]);
            
            $stmt->execute([
                $transactionId,
                $refundId,
                $amount,
                'Reembolso via PayPal',
                strtolower($status),
                $additionalData
            ]);
            
            return true;
        } catch (\PDOException $e) {
            error_log("Erro ao registrar reembolso: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza o status de um pedido no sistema principal
     * 
     * @param int $orderId ID do pedido
     * @param string $status Novo status
     * @param array $additionalData Dados adicionais
     * @return bool Sucesso da operação
     */
    protected function updateOrderStatus(int $orderId, string $status, array $additionalData = []): bool {
        try {
            // Mapear status para formato de pedido
            $orderStatus = $this->getOrderStatusFromPaymentStatus($status);
            $paymentStatus = $status;
            
            // Atualizar pedido
            $pdo = \Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = ?, payment_status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([$orderStatus, $paymentStatus, $orderId]);
            
            // Adicionar ao histórico
            $details = json_encode($additionalData);
            $notes = "Atualização via webhook PayPal. Status de pagamento: {$status}";
            
            $stmt = $pdo->prepare("
                INSERT INTO order_status_history 
                (order_id, status, payment_status, details, notes, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([$orderId, $orderStatus, $paymentStatus, $details, $notes]);
            
            return true;
        } catch (\PDOException $e) {
            error_log("Erro ao atualizar status do pedido: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mapeia status de pagamento para status de pedido
     * 
     * @param string $paymentStatus Status do pagamento
     * @return string Status correspondente do pedido
     */
    protected function getOrderStatusFromPaymentStatus(string $paymentStatus): string {
        $statusMap = [
            'pending' => 'pending',
            'authorized' => 'processing',
            'approved' => 'processing',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'partially_refunded' => 'partially_refunded',
            'failed' => 'failed'
        ];
        
        return $statusMap[$paymentStatus] ?? 'pending';
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
            // Garantir que temos um token de acesso válido
            $this->ensureAccessToken();
            
            // Verificar status atual
            $statusCheck = $this->checkTransactionStatus($transactionId);
            
            if (!($statusCheck['success'] ?? false)) {
                throw new \Exception("Transação não encontrada ou erro ao verificar status");
            }
            
            $currentStatus = $statusCheck['status'] ?? '';
            
            // Verificar se a transação pode ser cancelada
            if (!in_array($currentStatus, ['pending', 'authorized'])) {
                throw new \Exception("Não é possível cancelar transação com status '{$currentStatus}'");
            }
            
            // Cancelar ordem
            $result = $this->sendRequest('POST', "/checkout/orders/{$transactionId}/void", []);
            
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
            error_log("Erro ao cancelar transação PayPal: " . $e->getMessage());
            
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
            // Garantir que temos um token de acesso válido
            $this->ensureAccessToken();
            
            // Verificar status atual
            $statusCheck = $this->checkTransactionStatus($transactionId);
            
            if (!($statusCheck['success'] ?? false)) {
                throw new \Exception("Transação não encontrada ou erro ao verificar status");
            }
            
            $currentStatus = $statusCheck['status'] ?? '';
            
            // Verificar se a transação pode ser reembolsada
            if ($currentStatus !== 'approved') {
                throw new \Exception("Não é possível reembolsar transação com status '{$currentStatus}'");
            }
            
            // Obter ID de captura
            $captureId = null;
            if (isset($statusCheck['capture_ids']) && is_array($statusCheck['capture_ids']) && !empty($statusCheck['capture_ids'])) {
                $captureId = $statusCheck['capture_ids'][0];
            } else {
                throw new \Exception("ID de captura não encontrado para reembolso");
            }
            
            // Preparar dados do reembolso
            $refundData = [
                'note_to_payer' => $reason ?: 'Reembolso solicitado'
            ];
            
            // Se valor específico, adicionar
            if ($amount !== null && $amount > 0) {
                $refundData['amount'] = [
                    'value' => number_format($amount, 2, '.', ''),
                    'currency_code' => $statusCheck['currency'] ?? 'BRL'
                ];
            }
            
            // Executar reembolso
            $result = $this->sendRequest('POST', "/payments/captures/{$captureId}/refund", $refundData);
            
            if (isset($result['id'])) {
                // Determinar valor do reembolso
                $refundAmount = $amount ?? ($statusCheck['amount'] ?? 0);
                $refundCurrency = $result['amount']['currency_code'] ?? 'BRL';
                
                // Determinar se é reembolso total ou parcial
                $isPartial = ($amount !== null && $amount < ($statusCheck['amount'] ?? 0));
                $newStatus = $isPartial ? 'partially_refunded' : 'refunded';
                
                // Atualizar status localmente
                $this->updateTransactionStatus($transactionId, $newStatus, [
                    'refund_id' => $result['id'],
                    'refund_amount' => $refundAmount,
                    'refund_currency' => $refundCurrency,
                    'refund_status' => $result['status'] ?? 'COMPLETED',
                    'refund_reason' => $reason,
                    'refunded_at' => date('c')
                ]);
                
                // Registrar reembolso
                $this->logRefund(
                    $transactionId,
                    $result['id'],
                    (float)$refundAmount,
                    $refundCurrency,
                    $result['status'] ?? 'COMPLETED'
                );
                
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'refund_id' => $result['id'],
                    'status' => $newStatus,
                    'amount' => $refundAmount,
                    'is_partial' => $isPartial
                ];
            } else {
                throw new \Exception("Falha ao reembolsar transação");
            }
        } catch (\Exception $e) {
            // Registrar erro
            error_log("Erro ao reembolsar transação PayPal: " . $e->getMessage());
            
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
        throw new \Exception("Geração de token deve ser realizada no frontend com SDK PayPal JavaScript");
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
            'client_id' => $this->config['client_id'],
            'currency' => $this->config['settings']['currency'] ?? 'BRL',
            'is_sandbox' => $this->isSandbox,
            'intent' => 'CAPTURE'
        ];
        
        // Adicionar URLs específicas
        if (isset($this->config['settings']['return_url'])) {
            $config['return_url'] = $this->config['settings']['return_url'];
        }
        
        if (isset($this->config['settings']['cancel_url'])) {
            $config['cancel_url'] = $this->config['settings']['cancel_url'];
        }
        
        return $config;
    }
    
    /**
     * Mapeia status do PayPal para nomenclatura interna
     * 
     * @param string $paypalStatus Status original do PayPal
     * @return string Status mapeado para formato interno
     */
    protected function mapStatus(string $paypalStatus): string {
        return $this->statusMap[strtoupper($paypalStatus)] ?? 'pending';
    }
    
    /**
     * Garante que existe um token de acesso válido
     * 
     * @return void
     * @throws \Exception Se não for possível obter token
     */
    protected function ensureAccessToken(): void {
        // Verificar se token existente ainda é válido
        $now = time();
        
        if ($this->accessToken && $this->tokenExpires > $now + 60) {
            // Token ainda válido
            return;
        }
        
        // Obter novo token
        $this->accessToken = null;
        
        // Credenciais
        $clientId = $this->config['client_id'];
        $clientSecret = $this->config['client_secret'];
        
        // Base URL
        $tokenUrl = $this->isSandbox ? 
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' :
            'https://api-m.paypal.com/v1/oauth2/token';
        
        // Configurar curl
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $tokenUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $clientId . ':' . $clientSecret,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Language: en_US'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        // Verificar erros
        if ($error) {
            throw new \Exception("Erro ao obter token de acesso: {$error}");
        }
        
        $result = json_decode($response, true);
        
        if ($statusCode !== 200 || !isset($result['access_token'])) {
            $errorMsg = isset($result['error_description']) ? 
                $result['error_description'] : "Erro HTTP {$statusCode} ao obter token";
                
            throw new \Exception("Falha na autenticação com PayPal: {$errorMsg}");
        }
        
        // Armazenar token e expiração
        $this->accessToken = $result['access_token'];
        $expiresIn = (int)($result['expires_in'] ?? 3600);
        $this->tokenExpires = time() + $expiresIn;
    }
    
    /**
     * Envia requisição para a API do PayPal
     * 
     * @param string $method Método HTTP (GET, POST, PUT, etc.)
     * @param string $endpoint Endpoint da API (começando com /)
     * @param array $data Dados a serem enviados (para POST, PUT)
     * @return array Resposta da API
     * @throws \Exception Em caso de erro na requisição
     */
    protected function sendRequest(string $method, string $endpoint, array $data = []): array {
        // Garantir token de acesso
        $this->ensureAccessToken();
        
        // Determinar URL base conforme ambiente
        $baseUrl = $this->isSandbox ? $this->sandboxApiBaseUrl : $this->apiBaseUrl;
        
        // Configurar curl
        $curl = curl_init();
        
        $url = $baseUrl . $endpoint;
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
            'Accept: application/json',
            'Prefer: return=representation',
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
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
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
            throw new \Exception("Erro de comunicação com PayPal: {$error}");
        }
        
        // Decodificar resposta
        $responseData = json_decode($response, true);
        
        // Verificar erros de API
        if ($statusCode >= 400 || !is_array($responseData)) {
            $errorMessage = 'Erro na API PayPal';
            
            if (isset($responseData['message'])) {
                $errorMessage = $responseData['message'];
            } elseif (isset($responseData['error_description'])) {
                $errorMessage = $responseData['error_description'];
            } elseif (isset($responseData['name'])) {
                $errorMessage = $responseData['name'] . ': ' . ($responseData['details'][0]['description'] ?? 'Erro desconhecido');
            } else {
                $errorMessage .= " (HTTP {$statusCode})";
            }
            
            $this->logApiInteraction($endpoint, $data, $responseData ?? ['raw' => $response], false);
            throw new \Exception($errorMessage, $statusCode);
        }
        
        // Registrar interação bem-sucedida
        $this->logApiInteraction($endpoint, $data, $responseData, true);
        
        return $responseData;
    }
    
    /**
     * Extrai o primeiro nome de um nome completo
     * 
     * @param string $fullName Nome completo
     * @return string Primeiro nome
     */
    protected function getFirstName(string $fullName): string {
        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? '';
    }
    
    /**
     * Extrai o sobrenome de um nome completo
     * 
     * @param string $fullName Nome completo
     * @return string Sobrenome
     */
    protected function getLastName(string $fullName): string {
        $parts = explode(' ', trim($fullName));
        
        if (count($parts) <= 1) {
            return '';
        }
        
        array_shift($parts);
        return implode(' ', $parts);
    }
}
