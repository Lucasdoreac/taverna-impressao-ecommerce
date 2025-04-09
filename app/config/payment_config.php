<?php
/**
 * Configurações de pagamento para a Taverna da Impressão 3D
 * 
 * Este arquivo contém configurações padrão para os gateways de pagamento,
 * que são utilizadas como fallback caso não existam configurações no banco de dados.
 * 
 * IMPORTANTE: Este arquivo NÃO deve conter chaves de API de produção.
 * As chaves reais devem ser configuradas apenas via painel administrativo.
 * 
 * @version 1.0.0
 * @author Taverna da Impressão
 */

// Configurações padrão para todos os gateways
$default_payment_config = [
    // Configuração global
    'sandbox' => true, // Ambiente de testes por padrão
    'debug' => false,  // Log detalhado
    'webhook_timeout' => 30, // Timeout para webhooks em segundos
    
    // Lista de métodos de pagamento padrão
    'payment_methods' => [
        [
            'id' => 'credit_card',
            'name' => 'Cartão de Crédito',
            'active' => true,
            'gateway' => 'mercadopago',
            'icon' => 'credit-card'
        ],
        [
            'id' => 'pix',
            'name' => 'PIX',
            'active' => true,
            'gateway' => 'mercadopago',
            'icon' => 'qr-code'
        ],
        [
            'id' => 'boleto',
            'name' => 'Boleto Bancário',
            'active' => true,
            'gateway' => 'mercadopago',
            'icon' => 'receipt'
        ]
    ],
    
    // Lista de gateways disponíveis
    'gateways' => [
        'mercadopago' => [
            'display_name' => 'MercadoPago',
            'active' => true,
            'sandbox' => true,
            'webhook_url' => BASE_URL . 'webhook/mercadopago',
            'supported_methods' => ['credit_card', 'pix', 'boleto', 'debit_card'],
            
            // Chaves de teste - substitua por suas chaves de sandbox
            'public_key' => 'TEST-00000000-0000-0000-0000-000000000000',
            'access_token' => 'TEST-0000000000000000-000000-00000000000000000000000000000000-000000000',
            
            // Configurações específicas
            'settings' => [
                'max_installments' => 12,
                'expiration_days_boleto' => 3,
                'expiration_hours_pix' => 24,
                'statement_descriptor' => 'TAVERNA3D',
                'notification_url' => BASE_URL . 'webhook/mercadopago'
            ]
        ],
        
        'pagseguro' => [
            'display_name' => 'PagSeguro',
            'active' => false,
            'sandbox' => true,
            'webhook_url' => BASE_URL . 'webhook/pagseguro',
            'supported_methods' => ['credit_card', 'pix', 'boleto'],
            
            // Chaves de teste - substitua por suas chaves de sandbox
            'email' => 'seu-email@dominio.com',
            'token' => 'sua-token-sandbox',
            
            // Configurações específicas
            'settings' => [
                'max_installments' => 12,
                'expiration_days_boleto' => 3,
                'expiration_hours_pix' => 24,
                'notification_url' => BASE_URL . 'webhook/pagseguro'
            ]
        ],
        
        'paypal' => [
            'display_name' => 'PayPal',
            'active' => false,
            'sandbox' => true,
            'webhook_url' => BASE_URL . 'webhook/paypal',
            'supported_methods' => ['paypal'],
            
            // Chaves de teste - substitua por suas chaves de sandbox
            'client_id' => 'sua-client-id-sandbox',
            'client_secret' => 'sua-client-secret-sandbox',
            
            // Configurações específicas
            'settings' => [
                'currency' => 'BRL',
                'return_url' => BASE_URL . 'pedido/sucesso/',
                'cancel_url' => BASE_URL . 'pedido/cancelado/',
                'webhook_url' => BASE_URL . 'webhook/paypal'
            ]
        ]
    ]
];

// Definir constantes de status de pagamento
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_APPROVED', 'approved');
define('PAYMENT_STATUS_AUTHORIZED', 'authorized');
define('PAYMENT_STATUS_IN_PROCESS', 'in_process');
define('PAYMENT_STATUS_IN_MEDIATION', 'in_mediation');
define('PAYMENT_STATUS_REJECTED', 'rejected');
define('PAYMENT_STATUS_CANCELLED', 'cancelled');
define('PAYMENT_STATUS_REFUNDED', 'refunded');
define('PAYMENT_STATUS_CHARGED_BACK', 'charged_back');
define('PAYMENT_STATUS_FAILED', 'failed');

// Mapeamento de status de pagamento para status de pedido
$payment_status_order_mapping = [
    PAYMENT_STATUS_PENDING => 'pending',
    PAYMENT_STATUS_APPROVED => 'processing',
    PAYMENT_STATUS_AUTHORIZED => 'processing',
    PAYMENT_STATUS_IN_PROCESS => 'pending',
    PAYMENT_STATUS_IN_MEDIATION => 'disputed',
    PAYMENT_STATUS_REJECTED => 'failed',
    PAYMENT_STATUS_CANCELLED => 'cancelled',
    PAYMENT_STATUS_REFUNDED => 'refunded',
    PAYMENT_STATUS_CHARGED_BACK => 'disputed',
    PAYMENT_STATUS_FAILED => 'failed'
];

/**
 * Cria ou atualiza configurações iniciais no banco de dados
 * Esta função é executada durante a instalação ou atualização do sistema
 */
function initialize_payment_settings() {
    global $default_payment_config;
    
    try {
        $db = Database::getInstance();
        
        // Inserir métodos de pagamento padrão
        $paymentMethods = $default_payment_config['payment_methods'];
        $paymentMethodsJson = json_encode($paymentMethods);
        
        $sql = "
            INSERT INTO settings (setting_key, setting_value, created_at, updated_at)
            VALUES ('payment_methods', ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
        ";
        
        $db->query($sql, [$paymentMethodsJson, $paymentMethodsJson]);
        
        // Inserir configurações de gateways
        foreach ($default_payment_config['gateways'] as $gatewayName => $gatewayConfig) {
            $gatewayConfigJson = json_encode($gatewayConfig);
            $settingKey = "payment.{$gatewayName}.config";
            
            $sql = "
                INSERT INTO settings (setting_key, setting_value, created_at, updated_at)
                VALUES (?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE updated_at = NOW()
            ";
            
            // Observe que não atualizamos setting_value para não sobrescrever configurações existentes
            $db->query($sql, [$settingKey, $gatewayConfigJson]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao inicializar configurações de pagamento: " . $e->getMessage());
        return false;
    }
}
