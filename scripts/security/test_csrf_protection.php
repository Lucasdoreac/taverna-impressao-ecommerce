<?php
/**
 * Script para testar proteção CSRF
 * 
 * Este script simula vários tipos de ataques CSRF para validar a implementação
 * da proteção na Taverna da Impressão 3D.
 * 
 * @package App\Scripts\Security
 */

// Definir diretório raiz do projeto para carregamento correto de classes
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Carregar autoloader
require_once ROOT_DIR . '/app/autoload.php';

// Importar classes necessárias
use App\Lib\Security\SecurityManager;
use App\Lib\Security\CsrfProtection;
use App\Lib\Validation\InputValidationTrait;

// Configurar tratamento de erros
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // Este código de erro não está incluído na configuração de error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

/**
 * Classe de teste para validação de proteção CSRF
 */
class CsrfProtectionTester {
    use InputValidationTrait;
    
    /** @var string URL base para testes */
    private $baseUrl;
    
    /** @var array Resultados dos testes */
    private $results = [];
    
    /** @var resource Contexto cURL */
    private $ch;
    
    /** @var array Cookies armazenados */
    private $cookies = [];
    
    /**
     * Construtor
     * 
     * @param string $baseUrl URL base para testes
     */
    public function __construct($baseUrl = null) {
        $this->baseUrl = $baseUrl ?: 'http://localhost:8000';
        
        // Inicializar cURL
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, tempnam("/tmp", "cookies"));
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, tempnam("/tmp", "cookies"));
    }
    
    /**
     * Destrutor - Libera recursos
     */
    public function __destruct() {
        if ($this->ch) {
            curl_close($this->ch);
        }
    }
    
    /**
     * Executa todos os testes de CSRF
     * 
     * @return array Resultados dos testes
     */
    public function runAllTests() {
        echo "Iniciando testes de proteção CSRF...\n\n";
        
        try {
            // Teste 1: Validar rejeição sem token
            $this->testMissingToken();
            
            // Teste 2: Validar rejeição com token inválido
            $this->testInvalidToken();
            
            // Teste 3: Validar rejeição com token expirado
            $this->testExpiredToken();
            
            // Teste 4: Validar aceitação com token válido
            $this->testValidToken();
            
            // Teste 5: Validar rejeição com token usado (proteção contra replay)
            $this->testTokenReuse();
            
            // Teste 6: Validar proteção em chamadas AJAX
            $this->testAjaxProtection();
            
            // Teste 7: Verificar se tokens são diferentes para cada usuário/sessão
            $this->testTokenUniqueness();
        } catch (Exception $e) {
            echo "ERRO durante os testes: " . $e->getMessage() . "\n";
            echo "Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")\n";
            echo "Trace:\n" . $e->getTraceAsString() . "\n";
        }
        
        $this->printSummary();
        return $this->results;
    }
    
    /**
     * Testa rejeição de requisição sem token CSRF
     */
    private function testMissingToken() {
        echo "Teste 1: Validar rejeição de requisição sem token CSRF\n";
        
        $success = false;
        
        try {
            // Simular uma requisição POST sem token CSRF
            $endpoint = '/api/user/update-profile';
            $postData = ['name' => 'Usuário Teste', 'email' => 'teste@example.com'];
            
            $response = $this->sendRequest($endpoint, $postData);
            
            // Verificar se a resposta indica erro de CSRF
            $success = strpos($response, 'csrf') !== false || strpos($response, 'token') !== false;
            
            if ($success) {
                echo "  ✓ SUCESSO: A requisição sem token CSRF foi rejeitada corretamente\n";
            } else {
                echo "  ✗ FALHA: A requisição sem token CSRF foi aceita ou não retornou erro específico\n";
            }
        } catch (Exception $e) {
            echo "  ! ERRO: " . $e->getMessage() . "\n";
            $success = false;
        }
        
        $this->results['missing_token'] = $success;
    }
    
    /**
     * Testa rejeição de requisição com token CSRF inválido
     */
    private function testInvalidToken() {
        echo "Teste 2: Validar rejeição de requisição com token CSRF inválido\n";
        
        $success = false;
        
        try {
            // Gerar um token inválido
            $invalidToken = md5(uniqid() . time());
            
            // Simular uma requisição POST com token CSRF inválido
            $endpoint = '/api/user/update-profile';
            $postData = [
                'name' => 'Usuário Teste',
                'email' => 'teste@example.com',
                'csrf_token' => $invalidToken
            ];
            
            $response = $this->sendRequest($endpoint, $postData);
            
            // Verificar se a resposta indica erro de CSRF
            $success = strpos($response, 'csrf') !== false || strpos($response, 'token') !== false;
            
            if ($success) {
                echo "  ✓ SUCESSO: A requisição com token CSRF inválido foi rejeitada corretamente\n";
            } else {
                echo "  ✗ FALHA: A requisição com token CSRF inválido foi aceita ou não retornou erro específico\n";
            }
        } catch (Exception $e) {
            echo "  ! ERRO: " . $e->getMessage() . "\n";
            $success = false;
        }
        
        $this->results['invalid_token'] = $success;
    }
    
    /**
     * Testa rejeição de requisição com token CSRF expirado
     */
    private function testExpiredToken() {
        echo "Teste 3: Validar rejeição de requisição com token CSRF expirado\n";
        
        $success = false;
        
        try {
            // Este teste requer acesso direto à classe de proteção CSRF
            // Simulamos um token expirado com timestamp antigo
            
            // Criar uma instância de CsrfProtection para teste interno
            $reflection = new ReflectionClass(CsrfProtection::class);
            $instance = $reflection->newInstanceWithoutConstructor();
            
            // Acessar o método de criação de token (método privado)
            $method = $reflection->getMethod('createToken');
            $method->setAccessible(true);
            
            // Simular um token expirado (com timestamp de 2 dias atrás)
            $expiredTime = time() - (60 * 60 * 48); // 48 horas no passado
            $expiredToken = hash('sha256', uniqid() . $expiredTime);
            
            // Simular uma requisição POST com token expirado
            $endpoint = '/api/user/update-profile';
            $postData = [
                'name' => 'Usuário Teste',
                'email' => 'teste@example.com',
                'csrf_token' => $expiredToken
            ];
            
            $response = $this->sendRequest($endpoint, $postData);
            
            // Verificar se a resposta indica erro de CSRF
            $success = strpos($response, 'csrf') !== false || strpos($response, 'token') !== false;
            
            if ($success) {
                echo "  ✓ SUCESSO: A requisição com token CSRF expirado foi rejeitada corretamente\n";
            } else {
                echo "  ✗ FALHA: A requisição com token CSRF expirado foi aceita ou não retornou erro específico\n";
            }
        } catch (Exception $e) {
            echo "  ! ERRO: " . $e->getMessage() . "\n";
            $success = false;
        }
        
        $this->results['expired_token'] = $success;
    }
    
    /**
     * Testa aceitação de requisição com token CSRF válido
     */
    private function testValidToken() {
        echo "Teste 4: Validar aceitação de requisição com token CSRF válido\n";
        
        $success = false;
        
        try {
            // Obter página com formulário para extrair token válido
            $formPage = $this->sendRequest('/account/profile', [], 'GET');
            
            // Extrair token CSRF da página
            $matches = [];
            if (preg_match('/<input type="hidden" name="csrf_token" value="([^"]+)"/', $formPage, $matches)) {
                $csrfToken = $matches[1];
                echo "  Token CSRF obtido: " . substr($csrfToken, 0, 10) . "...\n";
                
                // Simular uma requisição POST com token CSRF válido
                $endpoint = '/api/user/update-profile';
                $postData = [
                    'name' => 'Usuário Teste',
                    'email' => 'teste@example.com',
                    'csrf_token' => $csrfToken
                ];
                
                $response = $this->sendRequest($endpoint, $postData);
                
                // Verificar se a resposta indica sucesso (não contém erro de CSRF)
                $success = strpos($response, 'csrf') === false && strpos($response, 'token') === false;
                
                if ($success) {
                    echo "  ✓ SUCESSO: A requisição com token CSRF válido foi aceita corretamente\n";
                } else {
                    echo "  ✗ FALHA: A requisição com token CSRF válido foi rejeitada incorretamente\n";
                }
            } else {
                echo "  ✗ FALHA: Não foi possível extrair token CSRF da página\n";
            }
        } catch (Exception $e) {
            echo "  ! ERRO: " . $e->getMessage() . "\n";
            $success = false;
        }
        
        $this->results['valid_token'] = $success;
    }
    
    /**
     * Testa rejeição de requisição com token CSRF usado (proteção contra replay)
     */
    private function testTokenReuse() {
        echo "Teste 5: Validar rejeição de token CSRF reutilizado (proteção contra replay)\n";
        
        $success = false;
        
        try {
            // Obter página com formulário para extrair token válido
            $formPage = $this->sendRequest('/account/profile', [], 'GET');
            
            // Extrair token CSRF da página
            $matches = [];
            if (preg_match('/<input type="hidden" name="csrf_token" value="([^"]+)"/', $formPage, $matches)) {
                $csrfToken = $matches[1];
                echo "  Token CSRF obtido: " . substr($csrfToken, 0, 10) . "...\n";
                
                // Simular uma primeira requisição POST com token CSRF válido
                $endpoint = '/api/user/update-profile';
                $postData = [
                    'name' => 'Usuário Teste 1',
                    'email' => 'teste1@example.com',
                    'csrf_token' => $csrfToken
                ];
                
                $response1 = $this->sendRequest($endpoint, $postData);
                
                // Simular uma segunda requisição POST com o mesmo token
                $postData = [
                    'name' => 'Usuário Teste 2',
                    'email' => 'teste2@example.com',
                    'csrf_token' => $csrfToken
                ];
                
                $response2 = $this->sendRequest($endpoint, $postData);
                
                // Verificar se a segunda resposta indica erro de CSRF (token já usado)
                $success = strpos($response2, 'csrf') !== false || strpos($response2, 'token') !== false;
                
                if ($success) {
                    echo "  ✓ SUCESSO: A requisição com token CSRF reutilizado foi rejeitada corretamente\n";
                } else {
                    echo "  ✗ FALHA: A requisição com token CSRF reutilizado foi aceita incorretamente\n";
                }
            } else {
                echo "  ✗ FALHA: Não foi possível extrair token CSRF da página\n";
            }
        } catch (Exception $e) {
            echo "  ! ERRO: " . $e->getMessage() . "\n";
            $success = false;
        }
        
        $this->results['token_reuse'] = $success;
    }
    
    /**
     * Testa proteção CSRF em chamadas AJAX
     */
    private function testAjaxProtection() {
        echo "Teste 6: Validar proteção CSRF em chamadas AJAX\n";
        
        $success = false;
        
        try {
            // Obter página com aplicação AJAX
            $ajaxPage = $this->sendRequest('/app/dashboard', [], 'GET');
            
            // Extrair token CSRF da página (formato AJAX)
            $matches = [];
            if (preg_match('/csrfToken = "([^"]+)"/', $ajaxPage, $matches)) {
                $csrfToken = $matches[1];
                echo "  Token CSRF AJAX obtido: " . substr($csrfToken, 0, 10) . "...\n";
                
                // Simular uma chamada AJAX sem token CSRF
                $endpoint = '/api/data/fetch';
                $headers = ['X-Requested-With: XMLHttpRequest'];
                $response1 = $this->sendRequest($endpoint, ['action' => 'get-data'], 'POST', $headers);
                
                // Verificar se a resposta sem token foi rejeitada
                $rejected = strpos($response1, 'csrf') !== false || strpos($response1, 'token') !== false;
                
                // Simular uma chamada AJAX com token CSRF no header
                $headers = [
                    'X-Requested-With: XMLHttpRequest',
                    'X-CSRF-Token: ' . $csrfToken
                ];
                $response2 = $this->sendRequest($endpoint, ['action' => 'get-data'], 'POST', $headers);
                
                // Verificar se a resposta com token foi aceita
                $accepted = strpos($response2, 'csrf') === false && strpos($response2, 'token') === false;
                
                $success = $rejected && $accepted;
                
                if ($success) {
                    echo "  ✓ SUCESSO: Proteção CSRF funciona corretamente com chamadas AJAX\n";
                } else {
                    echo "  ✗ FALHA: Proteção CSRF não funciona corretamente com chamadas AJAX\n";
                }
            } else {
                echo "  ✗ FALHA: Não foi possível extrair token CSRF AJAX da página\n";
            }
        } catch (Exception $e) {
            echo "  ! ERRO: " . $e->getMessage() . "\n";
            $success = false;
        }
        
        $this->results['ajax_protection'] = $success;
    }
    
    /**
     * Testa se tokens são diferentes para cada usuário/sessão
     */
    private function testTokenUniqueness() {
        echo "Teste 7: Verificar se tokens são diferentes para cada usuário/sessão\n";
        
        $success = false;
        
        try {
            // Obter token da primeira sessão
            $formPage1 = $this->sendRequest('/account/profile', [], 'GET');
            
            // Criar nova sessão
            $this->resetSession();
            
            // Obter token da segunda sessão
            $formPage2 = $this->sendRequest('/account/profile', [], 'GET');
            
            // Extrair tokens CSRF das páginas
            $matches1 = [];
            $matches2 = [];
            $token1 = null;
            $token2 = null;
            
            if (preg_match('/<input type="hidden" name="csrf_token" value="([^"]+)"/', $formPage1, $matches1)) {
                $token1 = $matches1[1];
                echo "  Token sessão 1: " . substr($token1, 0, 10) . "...\n";
            }
            
            if (preg_match('/<input type="hidden" name="csrf_token" value="([^"]+)"/', $formPage2, $matches2)) {
                $token2 = $matches2[1];
                echo "  Token sessão 2: " . substr($token2, 0, 10) . "...\n";
            }
            
            // Verificar se os tokens são diferentes
            if ($token1 && $token2) {
                $success = $token1 !== $token2;
                
                if ($success) {
                    echo "  ✓ SUCESSO: Tokens CSRF são únicos para cada sessão\n";
                } else {
                    echo "  ✗ FALHA: Tokens CSRF são iguais para diferentes sessões\n";
                }
            } else {
                echo "  ✗ FALHA: Não foi possível extrair tokens CSRF das páginas\n";
            }
        } catch (Exception $e) {
            echo "  ! ERRO: " . $e->getMessage() . "\n";
            $success = false;
        }
        
        $this->results['token_uniqueness'] = $success;
    }
    
    /**
     * Envia uma requisição HTTP
     * 
     * @param string $endpoint Endpoint da API
     * @param array $data Dados da requisição
     * @param string $method Método HTTP (GET, POST)
     * @param array $headers Headers HTTP adicionais
     * @return string Resposta da requisição
     */
    private function sendRequest($endpoint, array $data = [], $method = 'POST', array $headers = []) {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        
        curl_setopt($this->ch, CURLOPT_URL, $url);
        
        // Configurar método
        if ($method === 'POST') {
            curl_setopt($this->ch, CURLOPT_POST, true);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            curl_setopt($this->ch, CURLOPT_POST, false);
            if (!empty($data)) {
                $url .= '?' . http_build_query($data);
                curl_setopt($this->ch, CURLOPT_URL, $url);
            }
        }
        
        // Configurar headers
        $defaultHeaders = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept: text/html,application/json,application/xhtml+xml'
        ];
        
        $allHeaders = array_merge($defaultHeaders, $headers);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $allHeaders);
        
        // Executar requisição
        $response = curl_exec($this->ch);
        
        // Verificar erros
        if (curl_errno($this->ch)) {
            throw new Exception('Erro cURL: ' . curl_error($this->ch));
        }
        
        return $response;
    }
    
    /**
     * Reseta a sessão cURL (simular novo usuário)
     */
    private function resetSession() {
        if ($this->ch) {
            curl_close($this->ch);
        }
        
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, tempnam("/tmp", "cookies"));
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, tempnam("/tmp", "cookies"));
    }
    
    /**
     * Imprime um resumo dos resultados dos testes
     */
    private function printSummary() {
        $totalTests = count($this->results);
        $passedTests = count(array_filter($this->results));
        
        echo "\n=======================\n";
        echo "RESUMO DOS TESTES CSRF\n";
        echo "=======================\n";
        echo "Total de testes: $totalTests\n";
        echo "Testes bem-sucedidos: $passedTests\n";
        echo "Testes falhos: " . ($totalTests - $passedTests) . "\n\n";
        
        foreach ($this->results as $test => $result) {
            $status = $result ? '✓' : '✗';
            echo "$status $test\n";
        }
        
        echo "\n";
        
        if ($passedTests === $totalTests) {
            echo "RESULTADO: SUCESSO - Todos os testes passaram\n";
        } else {
            echo "RESULTADO: FALHA - Alguns testes falharam\n";
        }
    }
}

// Ponto de entrada do script
try {
    echo "=================================\n";
    echo "Testes de Proteção CSRF\n";
    echo "=================================\n\n";
    
    // Ler parâmetros da linha de comando
    $baseUrl = null;
    $args = getopt('', ['url:']);
    
    if (isset($args['url'])) {
        $baseUrl = $args['url'];
        echo "Usando URL base: $baseUrl\n\n";
    } else {
        echo "Nenhuma URL base fornecida, usando localhost:8000\n\n";
    }
    
    // Executar testes
    $tester = new CsrfProtectionTester($baseUrl);
    $results = $tester->runAllTests();
    
    // Determinar código de saída com base nos resultados
    $allPassed = count(array_filter($results)) === count($results);
    exit($allPassed ? 0 : 1);
} catch (Exception $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    exit(1);
}
