<?php
/**
 * Gateway Sandbox Validator - Script para verificação de sandbox dos gateways de pagamento
 * 
 * Este script realiza verificações de configuração e conectividade
 * com os ambientes sandbox dos gateways integrados.
 * 
 * Uso: php gateway_sandbox_validator.php [--gateway=all|mercadopago|paypal] [--verbose]
 * 
 * @package    Scripts\Testing
 * @version    1.0.0
 * @author     Taverna da Impressão
 */

// Iniciar output buffering para formatar saída
ob_start();

// Definir constantes
define('ROOT_PATH', realpath(__DIR__ . '/../../'));
define('APP_PATH', ROOT_PATH . '/app');
define('LIB_PATH', APP_PATH . '/lib');

// Carregar configurações básicas
require_once ROOT_PATH . '/app/config/config.php';

// Definir modo teste explicitamente
$_ENV['APP_ENV'] = 'testing';
$_ENV['SANDBOX_MODE'] = 'true';

// Configurar autoloader
spl_autoload_register(function ($class) {
    $class = str_replace('\\', '/', $class);
    $class = str_replace('App/', '', $class);
    $file = APP_PATH . '/' . $class . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    return false;
});

// Importar classes necessárias
use App\Lib\Payment\PaymentManager;
use App\Lib\Security\SecurityManager;

// Processar argumentos de linha de comando
$options = getopt('', ['gateway::', 'verbose::']);
$gateway = $options['gateway'] ?? 'all';
$verbose = isset($options['verbose']);

/**
 * Classe ValidationResult para armazenar resultados dos testes
 */
class ValidationResult {
    public $test;
    public $success;
    public $message;
    public $details;
    
    public function __construct(string $test, bool $success, string $message, array $details = []) {
        $this->test = $test;
        $this->success = $success;
        $this->message = $message;
        $this->details = $details;
    }
}

/**
 * Classe GatewaySandboxValidator para executar validações
 */
class GatewaySandboxValidator {
    private $paymentManager;
    private $results = [];
    private $verbose;
    
    /**
     * Construtor
     * 
     * @param bool $verbose Se deve mostrar mensagens detalhadas
     */
    public function __construct(bool $verbose = false) {
        $this->verbose = $verbose;
        $this->initPaymentManager();
    }
    
    /**
     * Inicializa o PaymentManager
     */
    private function initPaymentManager() {
        try {
            $this->paymentManager = PaymentManager::getInstance();
            $this->addResult('init', true, 'PaymentManager inicializado com sucesso');
        } catch (\Exception $e) {
            $this->addResult('init', false, 'Falha ao inicializar PaymentManager: ' . $e->getMessage());
            exit(1);
        }
    }
    
    /**
     * Adiciona um resultado de teste
     */
    private function addResult(string $test, bool $success, string $message, array $details = []) {
        $result = new ValidationResult($test, $success, $message, $details);
        $this->results[] = $result;
        
        if ($this->verbose || !$success) {
            $this->printResult($result);
        }
    }
    
    /**
     * Imprime um resultado de teste
     */
    private function printResult(ValidationResult $result) {
        $status = $result->success ? "\e[32m✓\e[0m" : "\e[31m✗\e[0m";
        echo "{$status} {$result->test}: {$result->message}\n";
        
        if (!empty($result->details) && $this->verbose) {
            foreach ($result->details as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value, JSON_PRETTY_PRINT);
                }
                echo "  - {$key}: {$value}\n";
            }
        }
    }
    
    /**
     * Imprime resumo da validação
     */
    public function printSummary() {
        $total = count($this->results);
        $passed = count(array_filter($this->results, fn($r) => $r->success));
        $failed = $total - $passed;
        
        echo "\n========================================\n";
        echo "RESUMO DA VALIDAÇÃO\n";
        echo "========================================\n";
        echo "Total de testes: {$total}\n";
        echo "Sucesso: \e[32m{$passed}\e[0m\n";
        echo "Falhas: " . ($failed > 0 ? "\e[31m{$failed}\e[0m" : "{$failed}") . "\n";
        echo "========================================\n";
        
        if ($failed > 0) {
            echo "\nFalhas:\n";
            foreach ($this->results as $result) {
                if (!$result->success) {
                    echo "- {$result->test}: {$result->message}\n";
                }
            }
            echo "\n";
        }
    }
    
    /**
     * Executa validação do MercadoPago
     */
    public function validateMercadoPago() {
        echo "\n\e[1m[ Validando Gateway MercadoPago ]\e[0m\n";
        
        try {
            // Obter gateway
            $gateway = $this->paymentManager->getGateway('mercadopago');
            $this->addResult('mp_load', true, 'Gateway MercadoPago carregado com sucesso');
            
            // Verificar se está em modo sandbox
            $config = $gateway->getFrontendConfig();
            $isSandbox = $config['sandbox'] ?? false;
            
            if (!$isSandbox) {
                $this->addResult('mp_sandbox_mode', false, 'MercadoPago NÃO está em modo sandbox! Abortando testes.');
                return;
            }
            
            $this->addResult('mp_sandbox_mode', true, 'MercadoPago está em modo sandbox', ['sandbox' => $isSandbox]);
            
            // Verificar configuração
            $publicKey = !empty($config['public_key']);
            $this->addResult('mp_config', $publicKey, 
                $publicKey ? 'Configuração básica do MercadoPago presente' : 'Configuração incompleta do MercadoPago',
                ['public_key_present' => $publicKey]
            );
            
            // Testar conectividade básica
            $connected = false;
            $details = [];
            
            try {
                // Utilizar método protegido via Reflection para teste de conexão
                $reflectionMethod = new \ReflectionMethod($gateway, 'testConnection');
                $reflectionMethod->setAccessible(true);
                $result = $reflectionMethod->invoke($gateway);
                
                $connected = $result['success'] ?? false;
                $details = $result;
            } catch (\Exception $e) {
                $this->addResult('mp_connection', false, 'Erro ao testar conexão: ' . $e->getMessage());
                return;
            }
            
            $this->addResult('mp_connection', $connected, 
                $connected ? 'Conexão com MercadoPago estabelecida' : 'Falha na conexão com MercadoPago',
                $details
            );
            
            // Verificar endpoints de webhook
            $config = $gateway->getFrontendConfig();
            $webhookUrl = $config['webhook_url'] ?? '';
            
            $this->addResult('mp_webhook_endpoint', !empty($webhookUrl), 
                !empty($webhookUrl) ? 'Endpoint de webhook configurado' : 'Endpoint de webhook não configurado',
                ['webhook_url' => $webhookUrl]
            );
            
            echo "\nValidação do MercadoPago concluída.\n";
        } catch (\Exception $e) {
            $this->addResult('mp_validation', false, 'Erro ao validar MercadoPago: ' . $e->getMessage());
        }
    }
    
    /**
     * Executa validação do PayPal
     */
    public function validatePayPal() {
        echo "\n\e[1m[ Validando Gateway PayPal ]\e[0m\n";
        
        try {
            // Obter gateway
            $gateway = $this->paymentManager->getGateway('paypal');
            $this->addResult('pp_load', true, 'Gateway PayPal carregado com sucesso');
            
            // Verificar se está em modo sandbox
            $config = $gateway->getFrontendConfig();
            $isSandbox = $config['sandbox'] ?? false;
            
            if (!$isSandbox) {
                $this->addResult('pp_sandbox_mode', false, 'PayPal NÃO está em modo sandbox! Abortando testes.');
                return;
            }
            
            $this->addResult('pp_sandbox_mode', true, 'PayPal está em modo sandbox', ['sandbox' => $isSandbox]);
            
            // Verificar configuração
            $clientId = !empty($config['client_id']);
            $this->addResult('pp_config', $clientId, 
                $clientId ? 'Configuração básica do PayPal presente' : 'Configuração incompleta do PayPal',
                ['client_id_present' => $clientId]
            );
            
            // Testar conectividade básica
            $connected = false;
            $details = [];
            
            try {
                // Utilizar método protegido via Reflection para teste de conexão
                $reflectionMethod = new \ReflectionMethod($gateway, 'testConnection');
                $reflectionMethod->setAccessible(true);
                $result = $reflectionMethod->invoke($gateway);
                
                $connected = $result['success'] ?? false;
                $details = $result;
            } catch (\Exception $e) {
                $this->addResult('pp_connection', false, 'Erro ao testar conexão: ' . $e->getMessage());
                return;
            }
            
            $this->addResult('pp_connection', $connected, 
                $connected ? 'Conexão com PayPal estabelecida' : 'Falha na conexão com PayPal',
                $details
            );
            
            // Verificar endpoints de webhook e IPN
            $config = $gateway->getFrontendConfig();
            $webhookUrl = $config['webhook_url'] ?? '';
            $ipnUrl = $config['ipn_url'] ?? '';
            
            $this->addResult('pp_webhook_endpoint', !empty($webhookUrl), 
                !empty($webhookUrl) ? 'Endpoint de webhook configurado' : 'Endpoint de webhook não configurado',
                ['webhook_url' => $webhookUrl]
            );
            
            $this->addResult('pp_ipn_endpoint', !empty($ipnUrl), 
                !empty($ipnUrl) ? 'Endpoint de IPN configurado' : 'Endpoint de IPN não configurado',
                ['ipn_url' => $ipnUrl]
            );
            
            echo "\nValidação do PayPal concluída.\n";
        } catch (\Exception $e) {
            $this->addResult('pp_validation', false, 'Erro ao validar PayPal: ' . $e->getMessage());
        }
    }
    
    /**
     * Executa todas as validações
     */
    public function validateAll() {
        $this->validateMercadoPago();
        $this->validatePayPal();
    }
}

// Imprime cabeçalho
echo "\n";
echo "=======================================================\n";
echo "       GATEWAY SANDBOX VALIDATOR - v1.0.0              \n";
echo "=======================================================\n";
echo "Executando validação dos gateways em ambiente sandbox\n";
echo "Ambiente: " . ($_ENV['APP_ENV'] ?? 'não definido') . "\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
echo "=======================================================\n";

// Executar validações
$validator = new GatewaySandboxValidator($verbose);

switch ($gateway) {
    case 'mercadopago':
        $validator->validateMercadoPago();
        break;
    case 'paypal':
        $validator->validatePayPal();
        break;
    case 'all':
    default:
        $validator->validateAll();
        break;
}

// Imprimir resumo
$validator->printSummary();

// Finalizar e imprimir saída formatada
$output = ob_get_clean();
echo $output;

exit(0);
