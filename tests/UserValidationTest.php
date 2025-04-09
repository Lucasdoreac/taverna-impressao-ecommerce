<?php
/**
 * UserValidationTest - Classe para testar a validação de entrada na área de usuário
 * 
 * Este script realiza testes automatizados para verificar se a validação
 * de entrada na área de usuário está funcionando corretamente, incluindo
 * casos extremos e tentativas de bypass de segurança.
 * 
 * @package Taverna\Tests
 * @author Taverna da Impressão
 * @version 1.1.0
 */

// Definir constantes necessárias se não estiverem definidas
if (!defined('APP_PATH')) {
    define('APP_PATH', __DIR__ . '/../app');
}

// Carregar classes necessárias
require_once APP_PATH . '/lib/Security/InputValidator.php';
require_once APP_PATH . '/lib/Security/InputValidationTrait.php';
require_once APP_PATH . '/lib/Security/CsrfProtection.php';

/**
 * Mock simples para Database
 */
class Database {
    public static function getInstance() {
        return new self();
    }
    
    public function query($sql, $params = []) {
        return [];
    }
}

/**
 * Classe para testar validação
 */
class UserValidationTest {
    use InputValidationTrait;
    
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    
    /**
     * Construtor
     */
    public function __construct() {
        // Carregar trait
        require_once APP_PATH . '/lib/Security/InputValidationTrait.php';
    }
    
    /**
     * Executa todos os testes
     */
    public function runAllTests() {
        echo "========================================\n";
        echo "Iniciando testes de validação de entrada\n";
        echo "========================================\n\n";
        
        $this->testProfileValidation();
        $this->testAddressValidation();
        $this->testPasswordValidation();
        $this->testXssAttempts();
        $this->testAdvancedXssAttempts();
        $this->testSqlInjectionAttempts();
        $this->testCsrfTokenValidation();
        
        // Resumo dos testes
        echo "\n========================================\n";
        echo "Resumo dos testes: {$this->passedTests}/{$this->totalTests} passou(aram)\n";
        
        // Exibir resultados de falha
        if ($this->passedTests < $this->totalTests) {
            echo "\nTestes que falharam:\n";
            foreach ($this->results as $testName => $result) {
                if (!$result['passed']) {
                    echo "- {$testName}: {$result['message']}\n";
                }
            }
        }
        
        echo "========================================\n";
    }
    
    /**
     * Testa validação de dados do perfil
     */
    private function testProfileValidation() {
        echo "Testando validação de dados do perfil...\n";
        
        // Teste 1: Nome muito curto
        $this->runTest(
            'profile_name_too_short',
            function() {
                $name = $this->validateString('a', 'string', [
                    'required' => true,
                    'minLength' => 3,
                    'maxLength' => 100
                ], 'name');
                
                return $name === null && $this->hasValidationErrors();
            },
            'Nome muito curto deve ser rejeitado'
        );
        
        // Teste 2: Nome muito longo
        $this->runTest(
            'profile_name_too_long',
            function() {
                $name = $this->validateString(str_repeat('a', 101), 'string', [
                    'required' => true,
                    'minLength' => 3,
                    'maxLength' => 100
                ], 'name');
                
                return $name === null && $this->hasValidationErrors();
            },
            'Nome muito longo deve ser rejeitado'
        );
        
        // Teste 3: Email inválido
        $this->runTest(
            'profile_invalid_email',
            function() {
                $email = InputValidator::validate('POST', 'email', 'email', [
                    'required' => true
                ]);
                
                $_POST['email'] = 'invalidemail';
                $email = InputValidator::validate('POST', 'email', 'email', [
                    'required' => true
                ]);
                
                return $email === null && InputValidator::hasErrors();
            },
            'Email inválido deve ser rejeitado'
        );
        
        // Teste 4: Telefone inválido
        $this->runTest(
            'profile_invalid_phone',
            function() {
                $_POST['phone'] = 'abc123';
                $phone = InputValidator::validate('POST', 'phone', 'phone', [
                    'required' => false
                ]);
                
                return $phone === null && InputValidator::hasErrors();
            },
            'Telefone inválido deve ser rejeitado'
        );
        
        echo "Testes de validação de perfil concluídos.\n\n";
    }
    
    /**
     * Testa validação de dados de endereço
     */
    private function testAddressValidation() {
        echo "Testando validação de dados de endereço...\n";
        
        // Teste 1: CEP inválido
        $this->runTest(
            'address_invalid_postal_code',
            function() {
                $_POST['postal_code'] = 'abc123';
                $postalCode = InputValidator::validate('POST', 'postal_code', 'cep', [
                    'required' => true
                ]);
                
                return $postalCode === null && InputValidator::hasErrors();
            },
            'CEP inválido deve ser rejeitado'
        );
        
        // Teste 2: Estado inválido
        $this->runTest(
            'address_invalid_state',
            function() {
                $_POST['state'] = 'XYZ';
                $state = InputValidator::validate('POST', 'state', 'string', [
                    'required' => true,
                    'maxLength' => 2,
                    'pattern' => '/^[A-Z]{2}$/'
                ]);
                
                return $state === null && InputValidator::hasErrors();
            },
            'Estado inválido deve ser rejeitado'
        );
        
        // Teste 3: Cidade vazia
        $this->runTest(
            'address_empty_city',
            function() {
                $_POST['city'] = '';
                $city = InputValidator::validate('POST', 'city', 'string', [
                    'required' => true
                ]);
                
                return $city === null && InputValidator::hasErrors();
            },
            'Cidade vazia deve ser rejeitada'
        );
        
        echo "Testes de validação de endereço concluídos.\n\n";
    }
    
    /**
     * Testa validação de senha
     */
    private function testPasswordValidation() {
        echo "Testando validação de senha...\n";
        
        // Teste 1: Senha muito curta
        $this->runTest(
            'password_too_short',
            function() {
                $_POST['password'] = '12345';
                $password = InputValidator::validate('POST', 'password', 'string', [
                    'required' => true,
                    'minLength' => 6,
                    'sanitize' => false
                ]);
                
                return $password === null && InputValidator::hasErrors();
            },
            'Senha muito curta deve ser rejeitada'
        );
        
        // Teste 2: Senha e confirmação diferentes
        $this->runTest(
            'password_mismatch',
            function() {
                $_POST['password'] = 'password123';
                $_POST['confirm_password'] = 'password456';
                
                $password = InputValidator::validate('POST', 'password', 'string', [
                    'required' => true,
                    'minLength' => 6,
                    'sanitize' => false
                ]);
                
                $confirmPassword = InputValidator::validate('POST', 'confirm_password', 'string', [
                    'required' => true,
                    'sanitize' => false
                ]);
                
                return $password !== $confirmPassword;
            },
            'Senha e confirmação diferentes devem ser detectadas'
        );
        
        echo "Testes de validação de senha concluídos.\n\n";
    }
    
    /**
     * Testa tentativas de XSS
     */
    private function testXssAttempts() {
        echo "Testando proteção contra XSS...\n";
        
        // Teste 1: Script básico
        $this->runTest(
            'xss_basic_script',
            function() {
                $xssPayload = '<script>alert("XSS")</script>';
                $_POST['name'] = $xssPayload;
                $sanitized = InputValidator::validate('POST', 'name', 'string', [
                    'required' => true,
                    'sanitize' => true
                ]);
                
                return $sanitized !== $xssPayload && strpos($sanitized, '<script>') === false;
            },
            'Script básico deve ser sanitizado'
        );
        
        echo "Testes de proteção contra XSS concluídos.\n\n";
    }
    
    /**
     * Testa tentativas avançadas de XSS (SEC-001)
     */
    private function testAdvancedXssAttempts() {
        echo "Testando proteção contra XSS avançado...\n";
        
        // Teste 1: XSS com codificação de entidades HTML
        $this->runTest(
            'xss_encoded_entities',
            function() {
                $xssPayload = '&lt;img src=x onerror=alert(&#39;XSS&#39;)&gt;';
                $_POST['name'] = $xssPayload;
                $sanitized = InputValidator::validate('POST', 'name', 'string', [
                    'required' => true,
                    'sanitize' => true
                ]);
                
                // Verificar se após sanitização, o onerror foi removido
                return strpos(strtolower($sanitized), 'onerror') === false;
            },
            'XSS com entidades HTML codificadas deve ser sanitizado'
        );
        
        // Teste 2: XSS com atributos de evento
        $this->runTest(
            'xss_event_attributes',
            function() {
                $xssPayload = '<img src="x" onerror="alert(\'XSS\')">';
                $_POST['name'] = $xssPayload;
                $sanitized = InputValidator::validate('POST', 'name', 'string', [
                    'required' => true,
                    'sanitize' => true
                ]);
                
                // Verificar se após sanitização, o onerror foi removido
                return strpos(strtolower($sanitized), 'onerror') === false;
            },
            'XSS com atributos de evento deve ser sanitizado'
        );
        
        // Teste 3: XSS com javascript: em URLs
        $this->runTest(
            'xss_javascript_url',
            function() {
                $xssPayload = '<a href="javascript:alert(\'XSS\')">Click me</a>';
                $_POST['name'] = $xssPayload;
                $sanitized = InputValidator::validate('POST', 'name', 'string', [
                    'required' => true,
                    'sanitize' => true
                ]);
                
                // Verificar se após sanitização, o javascript: foi removido
                return strpos(strtolower($sanitized), 'javascript:') === false;
            },
            'XSS com javascript: em URLs deve ser sanitizado'
        );
        
        // Teste 4: XSS com múltiplas camadas de codificação
        $this->runTest(
            'xss_multiple_encoding',
            function() {
                // Codificação dupla para tentar bypass
                $xssPayload = '&amp;lt;img src=x onerror=alert(&amp;#39;XSS&amp;#39;)&amp;gt;';
                $_POST['name'] = $xssPayload;
                $sanitized = InputValidator::validate('POST', 'name', 'string', [
                    'required' => true,
                    'sanitize' => true
                ]);
                
                // Verificar se após sanitização, o conteúdo continua seguro
                $decodedSanitized = html_entity_decode($sanitized);
                return strpos(strtolower($decodedSanitized), 'onerror') === false &&
                       strpos(strtolower($decodedSanitized), '<img') === false;
            },
            'XSS com múltiplas camadas de codificação deve ser sanitizado'
        );
        
        echo "Testes de proteção contra XSS avançado concluídos.\n\n";
    }
    
    /**
     * Testa tentativas de injeção SQL (SEC-002)
     */
    private function testSqlInjectionAttempts() {
        echo "Testando proteção contra injeção SQL...\n";
        
        // Teste 1: Injeção SQL básica
        $this->runTest(
            'sql_injection_basic',
            function() {
                $sqlPayload = "admin' OR '1'='1";
                $_POST['email'] = $sqlPayload;
                
                // Novo método específico para banco de dados que não sanitiza
                $sanitized = InputValidator::validateForDatabase('POST', 'email', 'string', [
                    'required' => true
                ]);
                
                // Deve preservar o valor original para uso em prepared statements
                return $sanitized === $sqlPayload;
            },
            'Injeção SQL básica deve ser preservada para prepared statements'
        );
        
        // Teste 2: Injeção SQL com comentários
        $this->runTest(
            'sql_injection_comments',
            function() {
                $sqlPayload = "admin'; DROP TABLE users; --";
                $_POST['email'] = $sqlPayload;
                
                // Novo método específico para banco de dados que não sanitiza
                $sanitized = InputValidator::validateForDatabase('POST', 'email', 'string', [
                    'required' => true
                ]);
                
                // Deve preservar o valor original para uso em prepared statements
                return $sanitized === $sqlPayload;
            },
            'Injeção SQL com comentários deve ser preservada para prepared statements'
        );
        
        echo "Testes de proteção contra injeção SQL concluídos.\n\n";
    }
    
    /**
     * Testa validação de token CSRF (SEC-003)
     */
    private function testCsrfTokenValidation() {
        echo "Testando validação de token CSRF...\n";
        
        // Teste 1: Token vazio
        $this->runTest(
            'csrf_empty_token',
            function() {
                return !CsrfProtection::validateToken('');
            },
            'Token vazio deve ser rejeitado'
        );
        
        // Teste 2: Token inválido
        $this->runTest(
            'csrf_invalid_token',
            function() {
                return !CsrfProtection::validateToken('invalid-token-format');
            },
            'Token com formato inválido deve ser rejeitado'
        );
        
        // Teste 3: Token com tamanho incorreto
        $this->runTest(
            'csrf_wrong_length',
            function() {
                return !CsrfProtection::validateToken('abc123');
            },
            'Token com tamanho incorreto deve ser rejeitado'
        );
        
        // Teste 4: Token válido
        $this->runTest(
            'csrf_valid_token',
            function() {
                // Gerar token e limpar regeneração para teste
                $token = CsrfProtection::getToken(true);
                
                // Validar sem regenerar para não invalidar o token
                return CsrfProtection::validateToken($token, false);
            },
            'Token válido deve ser aceito'
        );
        
        echo "Testes de validação de token CSRF concluídos.\n\n";
    }
    
    /**
     * Método auxiliar para testar a sanitização avançada XSS
     */
    private function testAdvancedXssSanitization($payload) {
        return InputValidator::sanitizeAdvancedXSS($payload);
    }
    
    /**
     * Executa um teste e registra o resultado
     * 
     * @param string $testName Nome do teste
     * @param callable $testFunction Função de teste
     * @param string $description Descrição do teste
     */
    private function runTest($testName, $testFunction, $description) {
        $this->totalTests++;
        $passed = false;
        $message = '';
        
        // Limpar variáveis de sessão e POST
        $_SESSION = [];
        $_POST = [];
        InputValidator::clearErrors();
        
        try {
            $result = $testFunction();
            $passed = $result === true;
            $message = $passed ? 'Passou' : 'Falhou';
        } catch (Exception $e) {
            $passed = false;
            $message = 'Exceção: ' . $e->getMessage();
        }
        
        // Registrar resultado
        $this->results[$testName] = [
            'passed' => $passed,
            'message' => $message,
            'description' => $description
        ];
        
        if ($passed) {
            $this->passedTests++;
            echo "  ✓ {$description}\n";
        } else {
            echo "  ✗ {$description} - {$message}\n";
        }
    }
    
    /**
     * Método auxiliar para validar strings
     * 
     * @param string $value Valor a ser validado
     * @param string $type Tipo de validação
     * @param array $options Opções de validação
     * @param string $field Nome do campo
     * @return mixed Valor validado ou null
     */
    private function validateString($value, $type, $options, $field) {
        $_POST[$field] = $value;
        return InputValidator::validate('POST', $field, $type, $options);
    }
}

// Executar testes
$tester = new UserValidationTest();
$tester->runAllTests();
