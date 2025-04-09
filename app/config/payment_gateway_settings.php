<?php
/**
 * Configurações dos gateways de pagamento
 * 
 * Este arquivo contém as configurações padrão iniciais para gateways de pagamento.
 * Valores de produção NÃO devem ser armazenados aqui, mas configurados via
 * painel administrativo e armazenados de forma segura no banco de dados.
 * 
 * @package     App\Config
 * @version     1.0.0
 * @author      Taverna da Impressão
 */

// Configurações de sandbox para PayPal
$paypal_sandbox_settings = [
    'active' => true,
    'sandbox' => true,
    'display_name' => 'PayPal',
    'client_id' => 'TEST_CLIENT_ID', // Substituir por ID de sandbox para desenvolvimento
    'client_secret' => 'TEST_CLIENT_SECRET', // Substituir por Secret de sandbox para desenvolvimento
    'webhook_url' => BASE_URL . 'payment/ipn/paypal',
    'supported_methods' => ['paypal'],
    'settings' => [
        'currency' => 'BRL',
        'intent' => 'CAPTURE', 
        'webhook_id' => '', // Obtido ao configurar webhook no painel do PayPal Developer
        'return_url' => BASE_URL . 'payment/callback/paypal-success',
        'cancel_url' => BASE_URL . 'payment/callback/paypal-cancel'
    ]
];

// Inserir configurações no banco de dados durante instalação/atualização
function initialize_paypal_gateway() {
    global $paypal_sandbox_settings;
    
    try {
        $db = Database::getInstance();
        
        // Verificar se já existe configuração para PayPal
        $sql = "SELECT COUNT(*) FROM settings WHERE setting_key = 'payment.paypal.config'";
        $count = $db->query($sql)->fetchColumn();
        
        if ($count == 0) {
            // Inserir nova configuração
            $sql = "
                INSERT INTO settings (setting_key, setting_value, created_at, updated_at)
                VALUES ('payment.paypal.config', ?, NOW(), NOW())
            ";
            
            $db->query($sql, [json_encode($paypal_sandbox_settings)]);
            
            // Registrar método de pagamento PayPal
            registerPayPalPaymentMethod();
            
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Erro ao inicializar configurações do PayPal: " . $e->getMessage());
        return false;
    }
}

// Registrar método de pagamento PayPal
function registerPayPalPaymentMethod() {
    try {
        $db = Database::getInstance();
        
        // Buscar métodos de pagamento existentes
        $sql = "SELECT setting_value FROM settings WHERE setting_key = 'payment_methods'";
        $result = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $paymentMethods = json_decode($result['setting_value'], true) ?? [];
            
            // Verificar se já existe método PayPal
            $paypalExists = false;
            foreach ($paymentMethods as $method) {
                if ($method['id'] === 'paypal') {
                    $paypalExists = true;
                    break;
                }
            }
            
            if (!$paypalExists) {
                // Adicionar método PayPal
                $paymentMethods[] = [
                    'id' => 'paypal',
                    'name' => 'PayPal',
                    'description' => 'Pagamento seguro via PayPal',
                    'active' => true,
                    'gateway' => 'paypal',
                    'icon' => 'paypal',
                    'sort_order' => count($paymentMethods) + 1
                ];
                
                // Atualizar registro
                $sql = "
                    UPDATE settings 
                    SET setting_value = ?, updated_at = NOW() 
                    WHERE setting_key = 'payment_methods'
                ";
                
                $db->query($sql, [json_encode($paymentMethods)]);
            }
        } else {
            // Criar registro inicial
            $paymentMethods = [
                [
                    'id' => 'paypal',
                    'name' => 'PayPal',
                    'description' => 'Pagamento seguro via PayPal',
                    'active' => true,
                    'gateway' => 'paypal',
                    'icon' => 'paypal',
                    'sort_order' => 1
                ]
            ];
            
            $sql = "
                INSERT INTO settings (setting_key, setting_value, created_at, updated_at) 
                VALUES ('payment_methods', ?, NOW(), NOW())
            ";
            
            $db->query($sql, [json_encode($paymentMethods)]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao registrar método de pagamento PayPal: " . $e->getMessage());
        return false;
    }
}
