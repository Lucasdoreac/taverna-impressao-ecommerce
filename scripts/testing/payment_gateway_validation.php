<?php
/**
 * Script de validação dos gateways de pagamento
 * 
 * Este script executa uma série de testes para validar o funcionamento
 * dos gateways de pagamento implementados no sistema.
 * 
 * @package     App\Scripts\Testing
 * @version     1.0.0
 * @author      Taverna da Impressão
 */

// Inicializar ambiente
require_once __DIR__ . '/../../app/bootstrap.php';

use App\Lib\Payment\PaymentManager;
use App\Lib\Payment\PaymentGatewayInterface;
use App\Lib\Payment\Gateways\MercadoPagoGateway;
use App\Lib\Payment\Gateways\PayPalGateway;

// Classe para executar testes
class PaymentGatewayValidator {
    /**
     * @var array Registros dos testes executados
     */
    private $testResults = [];
    
    /**
     * @var PaymentManager Instância do gerenciador de pagamentos
     */
    private $paymentManager;
    
    /**
     * @var array Dados de pedido para testes
     */
    private $orderData;
    
    /**
     * @var array Dados de cliente para testes
     */
    private $customerData;
    
    /**
     * Construtor
     */
    public function __construct() {
        // Obter PaymentManager
        $this->paymentManager = PaymentManager::getInstance();
        
        // Dados de teste
        $this->orderData = [
            'id' => 999999,
            'order_number' => 'TEST-' . date('YmdHis'),
            'total' => 9.99,
            'subtotal' => 9.99,
            'shipping_cost' => 0,
            'discount' => 0,
            'items' => [
                [
                    'id' => 1,
                    'name' => 'Produto de Teste',
                    'description' => 'Produto para validação dos gateways',
                    'price' => 9.99,
                    'quantity' => 1
                ]
            ]
        ];
        
        $this->customerData = [
            'name' => 'Usuário de Teste',
            'email' => 'test@example.com',
            'phone' => '5511999999999',
            'document' => '12345678909',
            'document_type' => 'CPF',
            'address' => 'Rua de Teste',
            'number' => '123',
            'complement' => 'Apto 45',
            'neighborhood' => 'Bairro Teste',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zipcode' => '01234567'
        ];
    }
    
    /**
     * Executa todos os testes
     * 
     * @return void
     */
    public function runAllTests(): void {
        $this->log("Iniciando validação dos gateways de pagamento...");
        
        // Listar gateways disponíveis
        $this->testAvailableGateways();
        
        // Testar gateways específicos
        $this->testMercadoPagoGateway();
        $this->testPayPalGateway();
        
        // Exibir resultados
        $this->displayResults();
    }
    
    /**
     * Testa listagem de gateways disponíveis
     * 
     * @return void
     */
    private function testAvailableGateways(): void {
        $this->log("Testando listagem de gateways disponíveis...");
        
        try {
            // Listar gateways disponíveis
            $gateways = $this->paymentManager->listAvailableGateways();
            
            if (empty($gateways)) {
                $this->recordResult('available_gateways', false, "Nenhum gateway disponível");
            } else {
                $gatewayNames = array_map(function($gateway) {
                    return $gateway['name'] . ' (' . ($gateway['is_active'] ? 'ativo' : 'inativo') . ')';
                }, $gateways);
                
                $this->recordResult('available_gateways', true, "Gateways disponíveis: " . implode(', ', $gatewayNames));
                
                // Verificar métodos de pagamento
                $methods = $this->paymentManager->listPaymentMethods();
                $methodNames = array_map(function($method) {
                    return $method['id'] . ' (' . ($method['active'] ? 'ativo' : 'inativo') . ')';
                }, $methods);
                
                $this->recordResult('payment_methods', true, "Métodos de pagamento: " . implode(', ', $methodNames));
            }
        } catch (Exception $e) {
            $this->recordResult('available_gateways', false, "Erro ao listar gateways: " . $e->getMessage());
        }
    }
    
    /**
     * Testa gateway MercadoPago
     * 
     * @return void
     */
    private function testMercadoPagoGateway(): void {
        $this->log("\nTestando MercadoPago Gateway...");
        
        try {
            // Obter gateway
            $gateway = $this->paymentManager->getGateway('mercadopago');
            
            if (!$gateway instanceof MercadoPagoGateway) {
                $this->recordResult('mercadopago_instance', false, "Gateway MercadoPago não disponível");
                return;
            }
            
            $this->recordResult('mercadopago_instance', true, "Gateway MercadoPago disponível");
            
            // Testar obtenção de configuração para frontend
            $frontendConfig = $gateway->getFrontendConfig();
            
            if (empty($frontendConfig) || !isset($frontendConfig['public_key'])) {
                $this->recordResult('mercadopago_frontend_config', false, "Configuração de frontend inválida");
            } else {
                $this->recordResult('mercadopago_frontend_config', true, "Configuração de frontend válida");
            }
            
            // Testar iniciação de transação com PIX
            $this->log("Testando iniciação de transação PIX (simulação)...");
            $paymentData = [
                'payment_method' => 'pix'
            ];
            
            // Desabilitar para não criar transações reais
            if (false) {
                $result = $gateway->initiateTransaction($this->orderData, $this->customerData, $paymentData);
                
                if (!isset($result['success']) || !$result['success']) {
                    $this->recordResult('mercadopago_initiate_pix', false, "Falha ao iniciar transação PIX: " . ($result['error_message'] ?? 'Erro desconhecido'));
                } else {
                    $this->recordResult('mercadopago_initiate_pix', true, "Transação PIX iniciada com sucesso. ID: " . $result['transaction_id']);
                    
                    // Testar verificação de status
                    $status = $gateway->checkTransactionStatus($result['transaction_id']);
                    
                    if (!isset($status['success']) || !$status['success']) {
                        $this->recordResult('mercadopago_check_status', false, "Falha ao verificar status da transação: " . ($status['error_message'] ?? 'Erro desconhecido'));
                    } else {
                        $this->recordResult('mercadopago_check_status', true, "Status da transação: " . $status['status']);
                    }
                }
            } else {
                $this->recordResult('mercadopago_initiate_pix', 'skipped', "Teste de iniciação de transação PIX desabilitado");
                $this->recordResult('mercadopago_check_status', 'skipped', "Teste de verificação de status desabilitado");
            }
        } catch (Exception $e) {
            $this->recordResult('mercadopago_test', false, "Erro ao testar MercadoPago: " . $e->getMessage());
        }
    }
    
    /**
     * Testa gateway PayPal
     * 
     * @return void
     */
    private function testPayPalGateway(): void {
        $this->log("\nTestando PayPal Gateway...");
        
        try {
            // Obter gateway
            $gateway = $this->paymentManager->getGateway('paypal');
            
            if (!$gateway instanceof PayPalGateway) {
                $this->recordResult('paypal_instance', false, "Gateway PayPal não disponível");
                return;
            }
            
            $this->recordResult('paypal_instance', true, "Gateway PayPal disponível");
            
            // Testar obtenção de configuração para frontend
            $frontendConfig = $gateway->getFrontendConfig();
            
            if (empty($frontendConfig) || !isset($frontendConfig['client_id'])) {
                $this->recordResult('paypal_frontend_config', false, "Configuração de frontend inválida");
            } else {
                $this->recordResult('paypal_frontend_config', true, "Configuração de frontend válida");
            }
            
            // Testar iniciação de transação
            $this->log("Testando iniciação de transação PayPal (simulação)...");
            $paymentData = [
                'payment_method' => 'paypal'
            ];
            
            // Desabilitar para não criar transações reais
            if (false) {
                $result = $gateway->initiateTransaction($this->orderData, $this->customerData, $paymentData);
                
                if (!isset($result['success']) || !$result['success']) {
                    $this->recordResult('paypal_initiate', false, "Falha ao iniciar transação: " . ($result['error_message'] ?? 'Erro desconhecido'));
                } else {
                    $this->recordResult('paypal_initiate', true, "Transação iniciada com sucesso. ID: " . $result['transaction_id']);
                    
                    // Testar verificação de status
                    $status = $gateway->checkTransactionStatus($result['transaction_id']);
                    
                    if (!isset($status['success']) || !$status['success']) {
                        $this->recordResult('paypal_check_status', false, "Falha ao verificar status da transação: " . ($status['error_message'] ?? 'Erro desconhecido'));
                    } else {
                        $this->recordResult('paypal_check_status', true, "Status da transação: " . $status['status']);
                    }
                }
            } else {
                $this->recordResult('paypal_initiate', 'skipped', "Teste de iniciação de transação desabilitado");
                $this->recordResult('paypal_check_status', 'skipped', "Teste de verificação de status desabilitado");
            }
        } catch (Exception $e) {
            $this->recordResult('paypal_test', false, "Erro ao testar PayPal: " . $e->getMessage());
        }
    }
    
    /**
     * Registra resultado de teste
     * 
     * @param string $test Nome do teste
     * @param bool|string $success Resultado (true, false ou 'skipped')
     * @param string $message Mensagem descritiva
     * @return void
     */
    private function recordResult(string $test, $success, string $message): void {
        $this->testResults[$test] = [
            'success' => $success,
            'message' => $message
        ];
        
        $status = $success === true ? '✅ SUCESSO' : ($success === 'skipped' ? '⚠️ PULADO' : '❌ FALHA');
        $this->log(" - {$status}: {$message}");
    }
    
    /**
     * Registra mensagem no log
     * 
     * @param string $message Mensagem a ser registrada
     * @return void
     */
    private function log(string $message): void {
        echo $message . PHP_EOL;
    }
    
    /**
     * Exibe resultados finais dos testes
     * 
     * @return void
     */
    private function displayResults(): void {
        $this->log("\n\n=== RESULTADOS DA VALIDAÇÃO ===\n");
        
        $success = 0;
        $failed = 0;
        $skipped = 0;
        
        foreach ($this->testResults as $test => $result) {
            if ($result['success'] === true) {
                $success++;
            } elseif ($result['success'] === 'skipped') {
                $skipped++;
            } else {
                $failed++;
            }
        }
        
        $total = count($this->testResults);
        
        $this->log("Total de testes: {$total}");
        $this->log("Sucesso: {$success}");
        $this->log("Falhas: {$failed}");
        $this->log("Pulados: {$skipped}");
        
        if ($failed > 0) {
            $this->log("\nDetalhes das falhas:");
            
            foreach ($this->testResults as $test => $result) {
                if ($result['success'] === false) {
                    $this->log(" - {$test}: {$result['message']}");
                }
            }
        }
        
        $this->log("\nValidação concluída " . ($failed === 0 ? 'com sucesso' : 'com falhas') . ".");
    }
}

// Executar validação
(new PaymentGatewayValidator())->runAllTests();
