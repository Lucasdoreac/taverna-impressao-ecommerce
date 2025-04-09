<?php
/**
 * Testes unitários para MercadoPagoGateway
 * 
 * @package     App\Tests\Lib\Payment
 * @version     1.0.0
 * @author      Taverna da Impressão
 */

namespace App\Tests\Lib\Payment;

use PHPUnit\Framework\TestCase;
use App\Lib\Payment\PaymentGatewayInterface;
use App\Lib\Payment\Gateways\MercadoPagoGateway;
use ReflectionClass;
use ReflectionMethod;

/**
 * @covers \App\Lib\Payment\Gateways\MercadoPagoGateway
 */
class MercadoPagoGatewayTest extends TestCase {
    /**
     * @var array Configuração para testes
     */
    private $config;
    
    /**
     * @var MercadoPagoGateway Gateway para testes
     */
    private $gateway;
    
    /**
     * Configuração antes de cada teste
     */
    protected function setUp(): void {
        // Configuração padrão para testes
        $this->config = [
            'access_token' => 'TEST_ACCESS_TOKEN',
            'public_key' => 'TEST_PUBLIC_KEY',
            'sandbox' => true,
            'statement_descriptor' => 'TAVERNA3D',
            'settings' => [
                'max_installments' => 12,
                'expiration_days_boleto' => 3,
                'expiration_hours_pix' => 24,
                'notification_url' => 'https://example.com/webhook/mercadopago'
            ]
        ];
        
        // Criar instância do gateway
        $this->gateway = $this->getMockBuilder(MercadoPagoGateway::class)
            ->setConstructorArgs([$this->config])
            ->onlyMethods(['sendRequest'])
            ->getMock();
    }
    
    /**
     * Testa implementação da interface
     */
    public function testImplementsInterface(): void {
        $this->assertInstanceOf(PaymentGatewayInterface::class, $this->gateway);
    }
    
    /**
     * Testa validação de configuração correta
     */
    public function testValidConfiguration(): void {
        // Verificar se construtor foi executado sem exception
        $this->assertInstanceOf(MercadoPagoGateway::class, $this->gateway);
    }
    
    /**
     * Testa validação de configuração com campos obrigatórios ausentes
     */
    public function testInvalidConfiguration(): void {
        $this->expectException(\Exception::class);
        
        // Criar configuração incompleta
        $invalidConfig = [
            'sandbox' => true,
            'public_key' => 'TEST_PUBLIC_KEY'
            // access_token está faltando
        ];
        
        new MercadoPagoGateway($invalidConfig);
    }
    
    /**
     * Testa obtenção do nome do gateway
     */
    public function testGetGatewayName(): void {
        $this->assertEquals('mercadopago', MercadoPagoGateway::getGatewayName());
    }
    
    /**
     * Testa função de obtenção de configuração para frontend
     */
    public function testGetFrontendConfig(): void {
        $config = $this->gateway->getFrontendConfig();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('public_key', $config);
        $this->assertArrayHasKey('is_sandbox', $config);
        
        // Verificar sandbox configuration
        $this->assertTrue($config['is_sandbox']);
    }
    
    /**
     * Testa mapeamento de status do MercadoPago para status interno
     */
    public function testMapStatus(): void {
        // Acessar método privado via Reflection
        $reflectionClass = new ReflectionClass(MercadoPagoGateway::class);
        $method = $reflectionClass->getMethod('mapStatus');
        $method->setAccessible(true);
        
        // Testar diversos status
        $this->assertEquals('pending', $method->invoke($this->gateway, 'pending'));
        $this->assertEquals('approved', $method->invoke($this->gateway, 'approved'));
        $this->assertEquals('authorized', $method->invoke($this->gateway, 'authorized'));
        $this->assertEquals('in_process', $method->invoke($this->gateway, 'in_process'));
        $this->assertEquals('failed', $method->invoke($this->gateway, 'rejected'));
        $this->assertEquals('cancelled', $method->invoke($this->gateway, 'cancelled'));
        $this->assertEquals('refunded', $method->invoke($this->gateway, 'refunded'));
        $this->assertEquals('charged_back', $method->invoke($this->gateway, 'charged_back'));
        
        // Testar status desconhecido - deve retornar o mesmo valor
        $this->assertEquals('unknown_status', $method->invoke($this->gateway, 'unknown_status'));
    }
    
    /**
     * Testa preparação de dados específicos para cada método de pagamento
     */
    public function testPrepareMethodSpecificData(): void {
        // Acessar método privado via Reflection
        $reflectionClass = new ReflectionClass(MercadoPagoGateway::class);
        $method = $reflectionClass->getMethod('prepareMethodSpecificData');
        $method->setAccessible(true);
        
        // Testar método para cartão de crédito
        $creditCardData = $method->invoke($this->gateway, 'credit_card', [
            'payment_method' => 'credit_card',
            'card_token' => 'abc123',
            'installments' => 3,
            'card_brand' => 'visa'
        ]);
        
        $this->assertIsArray($creditCardData);
        $this->assertArrayHasKey('payment_methods', $creditCardData);
        $this->assertEquals(3, $creditCardData['payment_methods']['installments']);
        $this->assertArrayHasKey('payment', $creditCardData);
        $this->assertEquals('abc123', $creditCardData['payment']['token']);
        
        // Testar método para PIX
        $pixData = $method->invoke($this->gateway, 'pix', [
            'payment_method' => 'pix'
        ]);
        
        $this->assertIsArray($pixData);
        $this->assertArrayHasKey('payment_methods', $pixData);
        $this->assertEquals('pix', $pixData['payment_methods']['default_payment_method_id']);
        $this->assertTrue($pixData['expires']);
        
        // Testar método para boleto
        $boletoData = $method->invoke($this->gateway, 'boleto', [
            'payment_method' => 'boleto'
        ]);
        
        $this->assertIsArray($boletoData);
        $this->assertArrayHasKey('payment_methods', $boletoData);
        $this->assertEquals('bolbradesco', $boletoData['payment_methods']['default_payment_method_id']);
        $this->assertTrue($boletoData['expires']);
    }
    
    /**
     * Testa inicialização de transação
     */
    public function testInitiateTransaction(): void {
        // Configurar mock para simular resposta da API
        $this->gateway->method('sendRequest')
            ->willReturn([
                'id' => 'PREF123456',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=PREF123456',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=PREF123456',
                'point_of_interaction' => [
                    'transaction_data' => [
                        'qr_code' => 'data:image/png;base64,QRCODE_BASE64',
                        'qr_code_base64' => 'QRCODE_BASE64'
                    ]
                ]
            ]);
        
        // Dados para teste
        $orderData = [
            'id' => 123,
            'order_number' => 'ORDER-123',
            'total' => 99.99,
            'items' => [
                ['name' => 'Product 1', 'price' => 49.99, 'quantity' => 1],
                ['name' => 'Product 2', 'price' => 50.00, 'quantity' => 1]
            ]
        ];
        
        $customerData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '5511987654321',
            'document' => '123.456.789-00',
            'address' => 'Street Name',
            'number' => '123',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zipcode' => '01234-567'
        ];
        
        // Testar com método PIX
        $paymentData = [
            'payment_method' => 'pix'
        ];
        
        $result = $this->gateway->initiateTransaction($orderData, $customerData, $paymentData);
        
        // Verificar resultado
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('PREF123456', $result['transaction_id']);
        $this->assertEquals('pending', $result['status']);
        $this->assertArrayHasKey('init_point', $result);
        
        // Verificar dados específicos do PIX
        $this->assertArrayHasKey('qr_code', $result);
        $this->assertArrayHasKey('qr_code_text', $result);
        
        // Testar com método cartão de crédito
        $paymentData = [
            'payment_method' => 'credit_card',
            'card_token' => 'abc123',
            'installments' => 3,
            'card_brand' => 'visa'
        ];
        
        $result = $this->gateway->initiateTransaction($orderData, $customerData, $paymentData);
        
        // Verificar resultado
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('PREF123456', $result['transaction_id']);
        $this->assertEquals('pending', $result['status']);
        $this->assertArrayHasKey('init_point', $result);
    }
    
    /**
     * Testa verificação de status de transação (preferência)
     */
    public function testCheckTransactionStatusForPreference(): void {
        // Configurar mocks para simular respostas da API
        $this->gateway->expects($this->exactly(2))
            ->method('sendRequest')
            ->withConsecutive(
                [$this->equalTo('GET'), $this->stringContains('/checkout/preferences/pref_123')],
                [$this->equalTo('GET'), $this->stringContains('/checkout/preferences/pref_123/payments')]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 'pref_123',
                    'external_reference' => 'ORDER-123'
                ],
                [
                    'elements' => [
                        [
                            'id' => 'payment_123',
                            'status' => 'approved',
                            'transaction_amount' => 99.99
                        ]
                    ]
                ]
            );
        
        // Executar método
        $result = $this->gateway->checkTransactionStatus('pref_123');
        
        // Verificar resultado
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('pref_123', $result['transaction_id']);
        $this->assertEquals('approved', $result['status']);
        $this->assertEquals('payment_123', $result['payment_id']);
    }
    
    /**
     * Testa verificação de status de transação (pagamento direto)
     */
    public function testCheckTransactionStatusForPayment(): void {
        // Configurar mock para simular resposta da API
        $this->gateway->method('sendRequest')
            ->willReturn([
                'id' => 'payment_123',
                'status' => 'approved',
                'transaction_amount' => 99.99,
                'payment_method_id' => 'visa',
                'payment_type_id' => 'credit_card',
                'external_reference' => 'ORDER-123',
                'date_created' => '2025-04-01T10:00:00.000-03:00',
                'date_approved' => '2025-04-01T10:01:00.000-03:00',
                'date_last_updated' => '2025-04-01T10:01:00.000-03:00'
            ]);
        
        // Executar método
        $result = $this->gateway->checkTransactionStatus('payment_123');
        
        // Verificar resultado
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('payment_123', $result['transaction_id']);
        $this->assertEquals('approved', $result['status']);
        $this->assertEquals('approved', $result['raw_status']);
        $this->assertEquals(99.99, $result['amount']);
        $this->assertEquals('visa', $result['payment_method']);
        $this->assertEquals('credit_card', $result['payment_type']);
        $this->assertEquals('ORDER-123', $result['external_reference']);
    }
    
    /**
     * Testa processamento de webhook para pagamento
     */
    public function testHandlePaymentWebhook(): void {
        // Configurar mock para simular resposta da API
        $this->gateway->method('sendRequest')
            ->willReturn([
                'id' => 'payment_123',
                'status' => 'approved',
                'external_reference' => 'ORDER-123',
                'transaction_amount' => 99.99,
                'payment_method_id' => 'visa',
                'payment_type_id' => 'credit_card'
            ]);
        
        // Dados de webhook para teste
        $webhookData = [
            'type' => 'payment',
            'data' => [
                'id' => 'payment_123'
            ]
        ];
        
        // Executar método
        $result = $this->gateway->handleCallback($webhookData);
        
        // Verificar resultado
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('payment_123', $result['payment_id']);
        $this->assertEquals('approved', $result['status']);
        $this->assertEquals('ORDER-123', $result['external_reference']);
    }
    
    /**
     * Testa cancelamento de transação
     */
    public function testCancelTransaction(): void {
        // Configurar mocks para simular respostas da API
        $this->gateway->expects($this->exactly(2))
            ->method('sendRequest')
            ->withConsecutive(
                [$this->equalTo('GET'), $this->stringContains('/payments/payment_123')],
                [$this->equalTo('PUT'), $this->stringContains('/payments/payment_123')]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 'payment_123',
                    'status' => 'pending'
                ],
                [
                    'id' => 'payment_123',
                    'status' => 'cancelled'
                ]
            );
        
        // Executar método
        $result = $this->gateway->cancelTransaction('payment_123', 'Cancelamento a pedido do cliente');
        
        // Verificar resultado
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('payment_123', $result['transaction_id']);
        $this->assertEquals('cancelled', $result['status']);
    }
    
    /**
     * Testa reembolso de transação
     */
    public function testRefundTransaction(): void {
        // Configurar mocks para simular respostas da API
        $this->gateway->expects($this->exactly(2))
            ->method('sendRequest')
            ->withConsecutive(
                [$this->equalTo('GET'), $this->stringContains('/payments/payment_123')],
                [$this->equalTo('POST'), $this->stringContains('/payments/payment_123/refunds')]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 'payment_123',
                    'status' => 'approved'
                ],
                [
                    'id' => 'refund_123',
                    'payment_id' => 'payment_123',
                    'amount' => 99.99,
                    'status' => 'approved'
                ]
            );
        
        // Executar método
        $result = $this->gateway->refundTransaction('payment_123', null, 'Reembolso por item defeituoso');
        
        // Verificar resultado
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('payment_123', $result['transaction_id']);
        $this->assertEquals('refund_123', $result['refund_id']);
        $this->assertEquals('refunded', $result['status']);
    }
    
    /**
     * Testa reembolso parcial de transação
     */
    public function testPartialRefundTransaction(): void {
        // Configurar mocks para simular respostas da API
        $this->gateway->expects($this->exactly(2))
            ->method('sendRequest')
            ->withConsecutive(
                [$this->equalTo('GET'), $this->stringContains('/payments/payment_123')],
                [$this->equalTo('POST'), $this->stringContains('/payments/payment_123/refunds')]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 'payment_123',
                    'status' => 'approved',
                    'transaction_amount' => 99.99
                ],
                [
                    'id' => 'refund_123',
                    'payment_id' => 'payment_123',
                    'amount' => 49.99,
                    'status' => 'approved'
                ]
            );
        
        // Executar método com valor parcial
        $result = $this->gateway->refundTransaction('payment_123', 49.99, 'Reembolso parcial');
        
        // Verificar resultado
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('payment_123', $result['transaction_id']);
        $this->assertEquals('refund_123', $result['refund_id']);
        $this->assertEquals('partially_refunded', $result['status']);
        $this->assertEquals(49.99, $result['amount']);
    }
}
