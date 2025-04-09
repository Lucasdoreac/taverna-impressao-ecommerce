<?php
/**
 * Testes unitários para PayPalGateway
 * 
 * @package     App\Tests\Lib\Payment
 * @version     1.0.0
 * @author      Taverna da Impressão
 */

namespace App\Tests\Lib\Payment;

use PHPUnit\Framework\TestCase;
use App\Lib\Payment\PaymentGatewayInterface;
use App\Lib\Payment\Gateways\PayPalGateway;
use ReflectionClass;
use ReflectionMethod;

/**
 * @covers \App\Lib\Payment\Gateways\PayPalGateway
 */
class PayPalGatewayTest extends TestCase {
    /**
     * @var array Configuração para testes
     */
    private $config;
    
    /**
     * @var PayPalGateway Gateway para testes
     */
    private $gateway;
    
    /**
     * Configuração antes de cada teste
     */
    protected function setUp(): void {
        // Configuração padrão para testes
        $this->config = [
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'sandbox' => true,
            'settings' => [
                'currency' => 'BRL',
                'intent' => 'CAPTURE',
                'webhook_id' => 'test_webhook_id',
                'return_url' => 'https://example.com/return',
                'cancel_url' => 'https://example.com/cancel'
            ]
        ];
        
        // Criar instância do gateway
        $this->gateway = $this->getMockBuilder(PayPalGateway::class)
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
        $this->assertInstanceOf(PayPalGateway::class, $this->gateway);
    }
    
    /**
     * Testa validação de configuração com campos obrigatórios ausentes
     */
    public function testInvalidConfiguration(): void {
        $this->expectException(\Exception::class);
        
        // Criar configuração incompleta
        $invalidConfig = [
            'sandbox' => true
        ];
        
        new PayPalGateway($invalidConfig);
    }
    
    /**
     * Testa obtenção do nome do gateway
     */
    public function testGetGatewayName(): void {
        $this->assertEquals('paypal', PayPalGateway::getGatewayName());
    }
    
    /**
     * Testa função de obtenção de configuração para frontend
     */
    public function testGetFrontendConfig(): void {
        $config = $this->gateway->getFrontendConfig();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('client_id', $config);
        $this->assertArrayHasKey('currency', $config);
        $this->assertArrayHasKey('is_sandbox', $config);
        
        // Verificar sandbox configuration
        $this->assertTrue($config['is_sandbox']);
        $this->assertEquals('BRL', $config['currency']);
    }
    
    /**
     * Testa remoção de dados sensíveis
     */
    public function testRemoveSensitiveData(): void {
        // Acessar método privado via Reflection
        $reflectionClass = new ReflectionClass(PayPalGateway::class);
        $method = $reflectionClass->getMethod('removeSensitiveData');
        $method->setAccessible(true);
        
        // Dados sensíveis para teste
        $sensitiveData = [
            'client_id' => 'test',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'card_number' => '4111111111111111',
            'cvv' => '123',
            'access_token' => 'A1B2C3D4E5F6',
            'nested' => [
                'api_key' => 'secret_key',
                'public_data' => 'public'
            ]
        ];
        
        // Processar dados
        $safeData = $method->invoke($this->gateway, $sensitiveData);
        
        // Verificar resultado
        $this->assertIsArray($safeData);
        $this->assertEquals('John Doe', $safeData['name']); // Dados não sensíveis mantidos
        $this->assertEquals('john@example.com', $safeData['email']); // Emails não são sensíveis
        $this->assertEquals('******', $safeData['card_number']); // Dados sensíveis mascarados
        $this->assertEquals('******', $safeData['cvv']); // Dados sensíveis mascarados
        $this->assertEquals('******', $safeData['access_token']); // Dados sensíveis mascarados
        $this->assertEquals('******', $safeData['nested']['api_key']); // Dados sensíveis aninhados mascarados
        $this->assertEquals('public', $safeData['nested']['public_data']); // Dados públicos aninhados mantidos
    }
    
    /**
     * Testa mapeamento de status do PayPal para status interno
     */
    public function testMapStatus(): void {
        // Acessar método privado via Reflection
        $reflectionClass = new ReflectionClass(PayPalGateway::class);
        $method = $reflectionClass->getMethod('mapStatus');
        $method->setAccessible(true);
        
        // Testar diversos status
        $this->assertEquals('pending', $method->invoke($this->gateway, 'CREATED'));
        $this->assertEquals('approved', $method->invoke($this->gateway, 'COMPLETED'));
        $this->assertEquals('approved', $method->invoke($this->gateway, 'CAPTURED'));
        $this->assertEquals('failed', $method->invoke($this->gateway, 'DENIED'));
        $this->assertEquals('cancelled', $method->invoke($this->gateway, 'VOIDED'));
        $this->assertEquals('refunded', $method->invoke($this->gateway, 'REFUNDED'));
        
        // Testar status desconhecido - deve retornar pending
        $this->assertEquals('pending', $method->invoke($this->gateway, 'UNKNOWN_STATUS'));
    }
    
    /**
     * Testa validação robusta dos dados de pedido
     */
    public function testValidateOrderData(): void {
        // Acessar método privado via Reflection
        $reflectionClass = new ReflectionClass(PayPalGateway::class);
        $method = $reflectionClass->getMethod('validateOrderData');
        $method->setAccessible(true);
        
        // Dados de pedido válidos
        $validOrder = [
            'id' => 123,
            'order_number' => 'ORDER-123',
            'total' => 99.99,
            'items' => [
                ['name' => 'Product 1', 'price' => 49.99, 'quantity' => 1],
                ['name' => 'Product 2', 'price' => 50.00, 'quantity' => 1]
            ]
        ];
        
        // Verificar validação bem-sucedida
        $this->assertTrue($method->invoke($this->gateway, $validOrder));
        
        // Testar pedido sem ID
        $invalidOrder1 = $validOrder;
        unset($invalidOrder1['id']);
        
        $this->expectException(\Exception::class);
        $method->invoke($this->gateway, $invalidOrder1);
    }
    
    /**
     * Testa inicialização de transação
     */
    public function testInitiateTransaction(): void {
        // Configurar mock para simular resposta da API
        $this->gateway->method('sendRequest')
            ->willReturn([
                'id' => 'PAY-TEST123',
                'status' => 'CREATED',
                'links' => [
                    [
                        'rel' => 'self',
                        'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/PAY-TEST123'
                    ],
                    [
                        'rel' => 'approve',
                        'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAY-TEST123'
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
            'address' => 'Street Name',
            'number' => '123',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zipcode' => '01234-567'
        ];
        
        $paymentData = [
            'payment_method' => 'paypal'
        ];
        
        // Executar método
        $result = $this->gateway->initiateTransaction($orderData, $customerData, $paymentData);
        
        // Verificar resultado
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('PAY-TEST123', $result['transaction_id']);
        $this->assertEquals('pending', $result['status']);
        $this->assertArrayHasKey('redirect_url', $result);
    }
    
    /**
     * Testa verificação de status de transação
     */
    public function testCheckTransactionStatus(): void {
        // Configurar mock para simular resposta da API
        $this->gateway->method('sendRequest')
            ->willReturn([
                'id' => 'PAY-TEST123',
                'status' => 'COMPLETED',
                'purchase_units' => [
                    [
                        'reference_id' => 'ORDER-123',
                        'amount' => [
                            'currency_code' => 'BRL',
                            'value' => '99.99'
                        ],
                        'payments' => [
                            'captures' => [
                                [
                                    'id' => 'CAP-TEST123',
                                    'status' => 'COMPLETED',
                                    'amount' => [
                                        'currency_code' => 'BRL',
                                        'value' => '99.99'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
        
        // Executar método
        $result = $this->gateway->checkTransactionStatus('PAY-TEST123');
        
        // Verificar resultado
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('PAY-TEST123', $result['transaction_id']);
        $this->assertEquals('approved', $result['status']);
        $this->assertEquals('COMPLETED', $result['raw_status']);
        $this->assertEquals(99.99, $result['amount']);
        $this->assertEquals('BRL', $result['currency']);
        $this->assertIsArray($result['capture_ids']);
        $this->assertEquals('CAP-TEST123', $result['capture_ids'][0]);
    }
    
    /**
     * Testa processamento de webhook/callback
     */
    public function testHandleCallback(): void {
        // Configurar mock para simular resposta da API
        $this->gateway->method('sendRequest')
            ->willReturn([
                'id' => 'PAY-TEST123',
                'status' => 'COMPLETED'
            ]);
        
        // Dados de webhook para teste
        $webhookData = [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAP-TEST123',
                'status' => 'COMPLETED',
                'amount' => [
                    'value' => '99.99',
                    'currency_code' => 'BRL'
                ],
                'links' => [
                    [
                        'rel' => 'up',
                        'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/PAY-TEST123'
                    ]
                ]
            ]
        ];
        
        // Executar método
        $result = $this->gateway->handleCallback($webhookData);
        
        // Verificar resultado
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }
    
    /**
     * Testa cancelamento de transação
     */
    public function testCancelTransaction(): void {
        // Configurar mocks para simular respostas da API
        $this->gateway->expects($this->exactly(2))
            ->method('sendRequest')
            ->withConsecutive(
                [$this->equalTo('GET'), $this->stringContains('/checkout/orders/PAY-TEST123')],
                [$this->equalTo('POST'), $this->stringContains('/checkout/orders/PAY-TEST123/void')]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 'PAY-TEST123',
                    'status' => 'CREATED'
                ],
                [
                    'id' => 'PAY-TEST123',
                    'status' => 'VOIDED'
                ]
            );
        
        // Executar método
        $result = $this->gateway->cancelTransaction('PAY-TEST123', 'Cancelamento a pedido do cliente');
        
        // Verificar resultado
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('PAY-TEST123', $result['transaction_id']);
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
                [$this->equalTo('GET'), $this->stringContains('/checkout/orders/PAY-TEST123')],
                [$this->equalTo('POST'), $this->stringContains('/payments/captures/CAP-TEST123/refund')]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 'PAY-TEST123',
                    'status' => 'COMPLETED',
                    'purchase_units' => [
                        [
                            'payments' => [
                                'captures' => [
                                    [
                                        'id' => 'CAP-TEST123'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'id' => 'REF-TEST123',
                    'status' => 'COMPLETED',
                    'amount' => [
                        'value' => '99.99',
                        'currency_code' => 'BRL'
                    ]
                ]
            );
        
        // Executar método
        $result = $this->gateway->refundTransaction('PAY-TEST123', null, 'Reembolso por item defeituoso');
        
        // Verificar resultado
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('PAY-TEST123', $result['transaction_id']);
        $this->assertEquals('REF-TEST123', $result['refund_id']);
        $this->assertEquals('refunded', $result['status']);
    }
}
